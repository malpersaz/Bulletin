# Production Plan: Admin Module Edit/Save Lifecycle & PHP 8+ Type-Safety Hardening

- **Document Path:** `docs/plan/ADMIN_EDIT_SAVE_REMEDIATION_PLAN.md`
- **Target Version:** `v1.0.26`
- **Target Production Host:** `amedbulten.com` (`AF/Bulletin`)
- **Status:** Implemented

---

## 1. Executive Summary & Root Cause Analysis

When accessing or submitting the admin edit route for news articles (e.g., `https://amedbulten.com/tr/mod_news/edit/6`) and other `BaseModuleController`-backed modules (`mod_news`, `mod_publication`, `mod_event`, `mod_document`), an unhandled PHP exception/error triggers the global exception handler in `index.php` (lines 98–143), returning a generic `500 Internal Server Error` ("An unexpected error occurred. Please try again later.").

Empirical code inspection and container reproduction identified **four interconnected defects** in the admin controller, helper, and ORM lifecycle:

### 1.1 Defect 1 — PHP 8+ Loose Comparison Trap on Missing `menu` Field (`"" != 0` Evaluates to `true`)
- **Location:** `lib/BaseModuleController.class.php` — `executeAfterSaveCallback()` (line 1884) and `executeAfterUpdateCallback()` (line 1934).
- **Current Code:**
  ```php
  if ($this->menu_url_pattern && Req::Post_Safe('menu') != 0) {
      $this->handleMenuIntegration($model, 'update', $id);
  }
  ```
- **Mechanism:**
  - `mod_news`, `mod_publication`, `mod_event`, and `mod_document` define `$menu_url_pattern` (e.g., `'{lang}/news/{seo}'`), which is used both for Google Indexing API URL generation and optional menu item synchronization.
  - However, the admin templates (`templates/admin/mod_news/edit.twig`, `templates/admin/mod_news/add.twig`, etc.) do **not** include a `<select name="menu">` input field.
  - When the form is submitted, `$_POST['menu']` is undefined, causing `Req::Post_Safe('menu')` to return `""` (empty string).
  - Under PHP 8.0+, non-numeric string to integer loose comparison rules changed: `"" == 0` evaluates to `false`, meaning `"" != 0` evaluates to **`true`**.
  - Consequently, `handleMenuIntegration()` is erroneously executed on every `add` and `edit` request:
    - On `add`: `handleMenuIntegration($model, 'add')` attempts to insert `parent_id = ''` into `mod_menu`, which throws a fatal `DatabaseException` under MySQL strict mode (`Incorrect integer value: '' for column 'parent_id'`).
    - On `edit`: `handleMenuIntegration($model, 'update', $id)` unnecessarily queries and mutates the singleton ORM instance.

### 1.2 Defect 2 — Singleton ORM State Pollution & Unsafe `sync()` / `update()` in `handleMenuIntegration()`
- **Location:** `lib/BaseModuleController.class.php` — `handleMenuIntegration()` (lines 1999–2038) and `_edit()` (lines 1384–1416).
- **Mechanism:**
  1. `Load::model($this->model_name)` returns a **singleton** ORM object stored in `Registry`.
  2. In `BaseModuleController::edit($id)` (line 370), `$model->exists_id($id)` sets `$model->_where = " id = '6' "` and populates `$model->_result`.
  3. In `BaseModuleController::_edit($id)` (line 1384), the same singleton `$model` is retrieved, populated with POST fields, and `$model->update('id')` is executed.
  4. Immediately after `update('id')`, `handleMenuIntegration($model, 'update', $id)` calls:
     ```php
     $original_model = Load::model($this->model_name);
     $original_model->where_id($original_id)->gRo()->sync();
     ```
     Because `$original_model` is the exact same singleton as `$model`, calling `where_id($original_id)->gRo()->sync()` without first calling `flush()` reuses dirty query builder state (`_coloms`, `_order`, `_limit`), overwrites `$model`'s in-memory properties after the update already happened (meaning `$old_url` is actually computed from the *new* DB row rather than the pre-update row!), and throws `Uncaught OrmException: Result to be synced not found!` if the query returns empty.
  5. Furthermore, if `$mod_menu_model->exists_url($old_url)` matches a menu row, `$mod_menu_model->getResultOne()->sync()` is called and `$mod_menu_model->update()` is executed while `$mod_menu_model->_where` still holds `url = '...'` and without passing `'id'` to `update('id')`.

### 1.3 Defect 3 — PHP 8+ Fatal `TypeError` in `Helper::nested()` When Input Array Is Empty
- **Location:** `lib/Helper.class.php` — `Helper::nested($datas, $parentKey, $idKey)` (lines 306–322) and `lib/BaseModuleController.class.php` — `getFormData()` (lines 289–294).
- **Current Code:**
  ```php
  public static function nested($datas, $parentKey, $idKey)
  {
      if (!is_array($datas)) {
          return FALSE;
      }
      $keys = array_keys(current($datas));
  ```
- **Mechanism:**
  - When a table queried for nested data is empty, `$nested_model->toArray()` returns `null` or `[]` (empty array).
  - If `$datas` is `[]` (an empty array), `is_array($datas)` is `true`, and `current($datas)` returns `false`.
  - In PHP 8.0+, passing `false` to `array_keys()` throws an uncatchable `TypeError: array_keys(): Argument #1 ($array) must be of type array, bool given`, immediately crashing the request with a `500 Internal Server Error`.
  - Additionally, in `BaseModuleController::getFormData()` (line 290):
    ```php
    foreach ($this->need_nested_data as $model_name => $variable_name) {
        $nested_model = Load::model($model_name);
        $nested_model->order('order', 'ASC')->all();
        $nested_result = Helper::nested($nested_model->toArray(), 'parent_id', 'id');
        $data[$variable_name] = $nested_result;
    }
    ```
    `$nested_model` is a singleton retrieved via `Load::model($model_name)`. In `mod_news`, `getFormData()` first queries `mod_news_category` with `$mod_news_category->select('id, name, lang')->addWhere("root != 'y'")` (lines 68–71) or `parent::getFormData()` runs after a previous query on the singleton without calling `$nested_model->flush()`, leaving residual `_where` or `_coloms` clauses on the singleton model.

### 1.4 Defect 4 — Uncaught `Throwable` / `Google\Exception` in `GoogleIndexingService`
- **Location:** `lib/GoogleIndexingService.class.php` (lines 29–37, 53–62, 77–86).
- **Mechanism:**
  - `GoogleIndexingService` wraps Google API client initialization and `urlNotifications->publish()` in `catch (Exception $e)` and calls `trigger_error(..., E_USER_WARNING)`.
  - If the credentials file or HTTP transport throws a `\Throwable` / `\TypeError` or if `set_error_handler` escalates warnings during AJAX/POST requests, it can interrupt the post-save/post-update callback flow before the redirect completes. Catching `\Throwable` and logging cleanly via `error_log()` guarantees that external indexing telemetry never breaks content creation or editing.

---

## 2. Architectural Remediation Specifications

### 2.1 Strict Integer Validation for Menu Integration (`lib/BaseModuleController.class.php`)
1. Replace the loose `Req::Post_Safe('menu') != 0` comparison in both `executeAfterSaveCallback()` and `executeAfterUpdateCallback()` with strict positive integer validation:
   ```php
   $menuParentId = (int) Req::Post_Safe('menu');
   if ($this->menu_url_pattern && $menuParentId > 0) {
       $this->handleMenuIntegration($model, 'add');
   }
   ```
2. For `update` operations, capture the **pre-update** menu URL *before* `$model->update('id')` mutates the database row (or pass the pre-update record snapshot `$d` into `_edit($id, $existingRecord)`), and only invoke `handleMenuIntegration($model, 'update', $id, $oldUrl)` when `$this->menu_url_pattern` is configured and either a valid menu parent ID is posted or an existing menu item matches `$oldUrl`.

### 2.2 Safe Singleton Flushing & Defensive `handleMenuIntegration()` (`lib/BaseModuleController.class.php`)
1. In `BaseModuleController::_edit($id)`:
   - Before modifying `$model = Load::model($this->model_name)`, capture the original record's URL (`$oldMenuUrl`) while `$model` still holds the pre-update state (or from the pre-fetched `$d` array), then call `$model->flush()` before setting `$model->id = $id` and processing POST fields so no stale `_where` condition from `exists_id($id)` remains attached to the singleton.
2. In `BaseModuleController::handleMenuIntegration($model, $operation, $original_id = null, $old_url = null)`:
   - For `'add'`:
     - Verify `$parentId = (int) Req::Post_Safe('menu');` is `> 0`.
     - Call `$menu_model->flush()` before assigning properties, set `$menu_model->order = 0` if not set, and save safely.
   - For `'update'`:
     - Resolve `$old_url` from the pre-update state if provided; otherwise safely query `$original_model->flush()->where_id($original_id)->gRo()` and verify `$original_model->getSize() > 0` before calling `sync()`.
     - Call `$mod_menu_model->flush()` before `exists_url($old_url)`.
     - If `$mod_menu_model->exists_url($old_url)` is true and `$mod_menu_model->getSize() > 0`, call `$mod_menu_model->getResultOne()->sync()`, update fields, and call `$mod_menu_model->update('id')`.

### 2.3 Type-Safe `Helper::nested()` & Singleton Flushing in `getFormData()` (`lib/Helper.class.php`, `lib/BaseModuleController.class.php`)
1. In `lib/Helper.class.php` (`Helper::nested()`):
   - Guard against empty arrays and non-array elements before calling `array_keys()`:
     ```php
     public static function nested($datas, $parentKey, $idKey)
     {
         if (!is_array($datas) || empty($datas)) {
             return false;
         }
         $firstRow = current($datas);
         if (!is_array($firstRow)) {
             return false;
         }
         $keys = array_keys($firstRow);
         if (!in_array($parentKey, $keys, true) || !in_array($idKey, $keys, true)) {
             return false;
         }
         $array = [];
         foreach ($datas as $data) {
             if (!is_array($data) || !array_key_exists($parentKey, $data) || !array_key_exists($idKey, $data)) {
                 continue;
             }
             foreach ($keys as $k) {
                 $array[$data[$parentKey]][$data[$idKey]][$k] = $data[$k] ?? null;
             }
         }
         return $array;
     }
     ```
2. In `lib/BaseModuleController.class.php` (`getFormData()`):
   - Call `$nested_model->flush()` before `$nested_model->order('order', 'ASC')->all()` so previous queries on the same model singleton never leak `SELECT` column restrictions or `WHERE` filters into nested tree building.
   - In `mod/mod_news/index.php` (`getFormData()`), call `$mod_news_category->flush()` before `$mod_news_category->select('id, name, lang')`.

### 2.4 Non-Blocking Error Isolation in `GoogleIndexingService` (`lib/GoogleIndexingService.class.php`)
- Replace `catch (Exception $e)` with `catch (\Throwable $e)` and log failures via `error_log()` instead of `trigger_error(..., E_USER_WARNING)` so external network/configuration issues never trigger custom error handlers during article save/update redirects.

---

## 3. Files to Be Modified

| File | Scope of Change |
| :--- | :--- |
| `lib/BaseModuleController.class.php` | Fix PHP 8+ `Req::Post_Safe('menu')` integer check; flush singleton models in `getFormData()`, `_add()`, `_edit()`, and `handleMenuIntegration()`; capture pre-update URL before DB update; guard `sync()` with `getSize() > 0`. |
| `lib/Helper.class.php` | Harden `Helper::nested()` against empty arrays and non-array rows under PHP 8+. |
| `mod/mod_news/index.php` | Call `$mod_news_category->flush()` in `getFormData()` before building the flat category list. |
| `lib/GoogleIndexingService.class.php` | Catch `\Throwable` and use `error_log()` instead of `trigger_error()`. |
| `docs/VERSION.md` | Document `v1.0.27` release notes in English. |

---

## 4. Verification & Quality Gate Checklist

1. **Syntax Validation:**
   - `php -l lib/BaseModuleController.class.php`
   - `php -l lib/Helper.class.php`
   - `php -l mod/mod_news/index.php`
   - `php -l lib/GoogleIndexingService.class.php`
2. **Functional Reproduction & Verification Test (`bulletin_app` Container):**
   - Test `Helper::nested([], 'parent_id', 'id')` and `Helper::nested(null, 'parent_id', 'id')` under PHP 8+ (must return `false` with zero `TypeError`).
   - Test `GET /tr/mod_news/edit/6` and `POST /tr/mod_news/edit/6` (both with and without `menu` in `$_POST`, and with empty/populated tables) to confirm clean `302` redirect / `200` JSON response and zero exceptions.
3. **Repository Quality Guards:**
   - `python3 tools/code_separation_guard.py`
   - `python3 tools/i18n_audit.py`
   - `python3 tools/module_boundary_guard.py`
