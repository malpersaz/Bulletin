# Survey & Architecture Analysis Report: R1 & R2 Modernization

**Author**: Explorer 1 (Codebase & Architecture Explorer)  
**Date**: 2026-08-23  
**Target Codebase**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**Reference Codebase**: `/home/malpersaz/Desktop/projects/htdocs/AF/Business`  
**Scope**: 
1. **R1**: Strict Code Separation & Admin User ACL Modernization (`mod_user/acl.twig`, `mod_acl/`, `mod_user/index.php`, admin partials `header.twig`, `filter_sheet.twig`, `sheets_extra.twig`, `modal_user_menu.js`, `core_modals.css`)
2. **R2**: Core Framework, ORM & Twig SEO Unicode Preservation (`lib/TwigLibs.class.php`, `lib/Helper.class.php`, `lib/Orm/Orm.php`, `lib/Orm/Obuilder.php`, `lib/BaseModuleController.class.php`)

---

## 1. Observations

### 1.1 R1: Strict Code Separation & Admin User ACL Modernization

#### A. `templates/admin/mod_user/acl.twig`
- **Location**: `templates/admin/mod_user/acl.twig`
- **Violations & Existing Code**:
  - **Inline `<style>` Block**: Lines 10–132 contain 123 lines of inline CSS (`.acl-container`, `.acl-module-card`, `.acl-table`, etc.).
  - **Inline `<script>` Tags**: 
    - Lines 135–145 & 148–158: Inline Swal alerts for error and success flash messages.
    - Lines 245–279: Inline JavaScript functions `toggleAclModule()`, `toggleGroup()`, and form submit injection (`gonder`).
  - **Inline Event Handlers**:
    - Line 173: `onclick="toggleAclModule(this)"`
    - Line 186: `onchange="toggleGroup(this, '{{ m.mod_id }}', '{{ grup.group_id }}')"`
    - Line 228: `onclick="window.history.back()"`
    - Line 233: `onclick="document.getElementById('aclForm').submit()"`
    - Line 237: `onclick="document.getElementById('aclForm').submit()"`
  - **Inline `style="..."` Attributes**: Lines 178, 184, 203.
  - **Synchronous Legacy Form Workflow**: Wraps table in `<form action="" method="POST" id="aclForm">` requiring manual full-page reload on submit.
- **Reference Standard (`AF/Business/templates/admin/mod_user/acl.twig`)**:
  - Completely purged of inline CSS and JavaScript.
  - Includes `<link rel="stylesheet" href="/templates/admin/assets/css/mod_user_acl.css?v=2.0">` in `{% block styles %}`.
  - Includes `<script src="/templates/admin/assets/js/mod_user_acl.js?v=1.1"></script>` in `{% block scripts %}`.
  - Mobile phone-first UI featuring:
    - Top asynchronous indeterminate progress bar (`#aclProgressBar`).
    - Sticky horizontal user group carousel (`.acl-group-carousel-container`).
    - Live search input (`#aclSearchInput`) with highlight filtering and clear button.
    - Summary metrics pill (`#totalEnabledCount`, `#totalAvailableCount`, `#activeGroupLabel`).
    - Module accordion cards with "Grant All" / "Revoke All" actions and reactive switch controls (`role="switch"`).
    - Hydration container `#aclInitialData` passing `data-permissions`, `data-groups`, and `data-counter-template`.

#### B. `mod/mod_user/index.php` & `mod/mod_user/model/`
- **Location**: `mod/mod_user/index.php`
- **Existing `acl()` Action (Lines 1948–2010)**:
  - Passes Turkish variable names to template: `gruplar`, `modlar`, `roller`, `permler`.
  - Missing `toggle_permission()` AJAX controller action.
- **Legacy `_acl()` Method (Lines 2013–2045)**:
  - Executes full table truncate `DELETE FROM mod_user_perm` and re-inserts permissions synchronously from POST array.
  - Calls deprecated method `$this->user->_roll(...)` instead of `$this->user->_loadPermissions(...)`.
- **Target Modernization**:
  - Implement `public function toggle_permission()` with:
    - POST method check & ACL permission verification (`mod_user.acl`).
    - Database transaction support (`$db->start_transaction()`, `$db->commit()`).
    - Batch support (`role_id[]` array) and single role toggle (`role_id` integer).
    - Immediate session synchronization for current user (`if ((int)$this->session->get('group_id') === $groupId) { $this->user->_loadPermissions($groupId); }`).
    - JSON response via `$this->_respond_with_json(['success' => true, ...])`.
  - Update `public function acl()` to pass English variables (`groups`, `modules`, `roles`, `permissions`) while maintaining backward-compatible aliases.

#### C. `templates/admin/mod_acl/` & `mod/mod_acl/`
- **Templates in `templates/admin/mod_acl/`**:
  - `list.twig`: Contains inline `<style>` (lines 12–164), inline `<script>` (lines 168–177, 251–255), Turkish route `href=".../mod_acl/ekle"` (line 201), Turkish permission check `mod_acl.sil` (line 191), and `{% include mod ~ '/script.twig' %}`.
  - `script.twig`: 239 lines of inline JavaScript containing hardcoded Turkish strings (`İşleniyor...`, `Kayıt Bulunamadı`, `Kayıt Yok`, `Lütfen Bir İşlem Seçin`, `sil[]=`), legacy Metronic/Datatables code, and Turkish callback names (`processSil`, `processDosya`).
  - `add.twig`: Contains inline `<style>` (lines 17–153), inline `<style>` in `fixed_bottom` (lines 238–288), inline `<script>` tags (lines 160–170, 173–189, 304–341), inline `onclick` handlers, and Turkish lang key `vazgec`.
  - `edit.twig`: Contains inline `<style>` (lines 17–153, 246–295), inline `<script>` tags (lines 160–170, 173–189, 311–337), inline `onclick` handlers, and Turkish lang key `vazgec`.
- **Controller `mod/mod_acl/index.php`**:
  - In `datatable_json()` (lines 311–327), concatenates HTML action buttons with inline `onclick="mod_acl_table_ajax.akm.processSil(...)"` and `processDosya(...)`.
  - Method `_delete($sil)` uses parameter name `$sil`.
- **Language Dictionary `mod/mod_acl/lang/en.php`**:
  - Contains Turkish keys: `ziyaretci`, `hata`, `yenile`, `vazgec`, `kaldir`, `evet`, `hayir`, `admin.columns.ad`.
- **Target Modernization**:
  - Purge `templates/admin/mod_acl/script.twig`.
  - Modernize `list.twig`, `add.twig`, `edit.twig` to use external `templates/admin/assets/css/mod_acl.css` and `templates/admin/assets/js/mod_acl.js`.
  - Modernize `AclManager` in `mod_acl.js` with debounced search, touch-friendly list cards, infinite load/pagination, and standard modal/dialog events.
  - Modernize `mod/mod_acl/index.php` and `mod/mod_acl/lang/en.php` with 100% English identifiers and clean JSON payloads.

#### D. Admin Partials (`header.twig`, `filter_sheet.twig`, `sheets_extra.twig`, `modal_user_menu.js`, `core_modals.css`)
- **`templates/admin/partials/header.twig`**:
  - Multiple inline styles and inline `onclick` attributes: `onclick="openUserMenuSheet()"`, `onclick="closeUserMenuSheet()"`, `onclick="showUserSubmenu(...)"`, `onclick="clearSearch()"`, `onclick="openSheet()"`, `onclick="exitSelectionMode()"`.
- **`templates/admin/partials/filter_sheet.twig`**:
  - Lines 68–90 contain an inline `<style>` tag.
  - Inline styles throughout lines 8, 9, 11, 18, 27, 36, 38, 39, 41, 46, 47, 48, 49, 52, 53, 61, 62, 63.
  - Inline `onclick` handlers: `onclick="closeSheet()"`, `onclick="selectChip(this, ...)"`, `onclick="openLangSheet()"`, `onclick="clearFilters()"`, `onclick="applyFilters()"`.
- **`templates/admin/assets/js/modals/modal_user_menu.js` & `core_modals.css`**:
  - `modal_user_menu.js` already contains a comprehensive centralized `document.addEventListener('click', ...)` delegator handling all `data-action` attributes (`open-user-menu`, `close-user-menu`, `user-submenu`, `user-menu-main`, `open-filter-sheet`, `clear-search`, `close-sheet`, `select-chip`, `clear-filters`, `apply-filters`, `open-lang-sheet`, etc.).
  - `core_modals.css` contains all class definitions for dialogs, modals, and sheets.
- **Target Modernization**:
  - Replace all inline styles and `onclick` handlers in `header.twig` and `filter_sheet.twig` with class names and `data-action` attributes matching `modal_user_menu.js` and `core_modals.css`.

---

### 1.2 R2: Core Framework, ORM & Twig SEO Unicode Preservation

#### A. Twig SEO Filter & Unicode Kurdish Preservation
- **Location**: `lib/TwigLibs.class.php` (lines 404–419)
- **Current Code**:
  ```php
  public function legacySeoFilter(?string $s): string
  {
      if ($s === null) {
          throw new \InvalidArgumentException('legacy_seo modifier error: parameter cannot be null');
      }

      $s = preg_replace('/[^a-zA-Z1234567890"\' ]/', ' ', $s);
      $s = trim($s);
      $s = preg_replace('/\s+/', '_', $s);

      for ($i = 0; $i <= 5; $i++) {
          $s = str_replace('__', '_', $s);
      }

      return $s;
  }
  ```
- **Flaw**: The regex `/[^a-zA-Z1234567890"\' ]/` strips all non-ASCII characters, replacing Kurdish characters (`Ç`, `Ê`, `Î`, `Ş`, `Û`, `ç`, `ê`, `î`, `ş`, `û`) with spaces and collapsing them into underscores (`_`), causing serious URL corruption.
- **Helper Reference (`lib/Helper.class.php` lines 172–181)**:
  ```php
  static function legacy_seo($s)
  {
      $s = preg_replace('/[^\p{L}\p{N}"\' ]/u', " ", $s);
      $s = trim($s);
      $s = preg_replace('/\s+/', '_', $s);
      for ($i = 0; $i <= 5; $i++) {
          $s = str_replace("__", "_", $s);
      }
      return $s;
  }
  ```
- **Target Modernization (`lib/TwigLibs.class.php`)**:
  Standardize `legacySeoFilter` to delegate directly to `Helper::legacy_seo($s)`:
  ```php
  public function legacySeoFilter(?string $s): string
  {
      if ($s === null) {
          throw new \InvalidArgumentException('legacy_seo modifier error: parameter cannot be null');
      }

      return Helper::legacy_seo($s);
  }
  ```

#### B. `lib/Orm/Orm.php` & `lib/Orm/Obuilder.php` (Case-Insensitive Resolution)
- **Location**: `lib/Orm/Orm.php` & `lib/Orm/Obuilder.php`
- **Current Issue**:
  - `Obuilder::check_key_exists` uses strict `in_array($key, $_orm_coloms)` without case normalization.
  - In `lib/Orm/Obuilder.php` (lines 239–257), exception messages contain hardcoded Turkish text (`"colom'u geçerli değil\nÇünkü model dosyasında tanımlanmamış!"`).
  - In `lib/Orm/Orm.php`, `save()`, `update()`, and auto-increment exclusion must handle primary keys and column names consistently regardless of SQLite / MySQL schema casing differences.
- **Target Modernization**:
  - Normalize column name checks to case-insensitive lookup (or ensure model column list array is normalized).
  - Translate all exception messages and comments in `lib/Orm/` to English.

#### C. `lib/BaseModuleController.class.php` (Centralized AJAX Relation Helpers)
- **Location**: `lib/BaseModuleController.class.php`
- **Current State**:
  - `BaseModuleController` lacks generic implementations of `ajax_get_files()`, `ajax_get_images()`, `ajax_get_current_media()`, and `ajax_update_relation()`.
  - Modules like `mod_news`, `mod_publication`, `mod_content`, `mod_page`, `mod_announcements` each either duplicate relation handlers or have missing relation endpoints for modal media pickers (file attachment, gallery, video embed).
- **Target Modernization**:
  - Implement centralized, reusable methods in `BaseModuleController.class.php`:
    - `public function ajax_get_files()`: Retrieves files matching module relation or search query.
    - `public function ajax_get_images()`: Retrieves galleries/images matching module relation or search query.
    - `public function ajax_get_current_media()`: Returns JSON `{ file_id: X, gallery_id: Y, video: [...] }` for a record.
    - `public function ajax_update_relation()`: Handles updating `file_id`, `gallery_id`, or `video` with permission check, ORM sync, and JSON response.

---

## 2. Logic Chain & Step-by-Step Rationale

```
Observation 1: templates/admin/mod_user/acl.twig has 123 lines of inline <style> and inline <script>
  └─ Logic: Violates Primary Rule 2 (Strict Code Separation) and Rule 6 (Admin Mobile Surface).
  └─ Action: Port modern acl.twig from AF/Business and link mod_user_acl.css / mod_user_acl.js.

Observation 2: mod/mod_user/index.php has no toggle_permission() endpoint
  └─ Logic: The modern reactive switch UI in mod_user_acl.js sends POST requests to /mod_user/toggle_permission.
  └─ Action: Add toggle_permission() to mod_user controller with DB transaction and _loadPermissions() session sync.

Observation 3: templates/admin/mod_acl/ has inline <style>, <script>, script.twig, and Turkish strings
  └─ Logic: Violates Rule 2 (Code Separation) and Rule 7 (English Identifiers). script.twig is obsolete Metronic legacy.
  └─ Action: Purge script.twig; modernize list.twig, add.twig, edit.twig with mod_acl.css and mod_acl.js (AclManager class); sanitize mod_acl/index.php and lang/en.php.

Observation 4: header.twig and filter_sheet.twig use inline styles and inline onclick handlers
  └─ Logic: modal_user_menu.js is already listening to data-action delegations, and core_modals.css has the necessary CSS.
  └─ Action: Refactor header.twig and filter_sheet.twig to use data-action attributes and pure CSS classes.

Observation 5: TwigLibs::legacySeoFilter uses ASCII-only regex /[^a-zA-Z1234567890"' ]/
  └─ Logic: Strips Kurdish letters (Ç, Ê, Î, Ş, Û), violating Rule 10 (Wikipedia-style URL parity).
  └─ Action: Update TwigLibs::legacySeoFilter to delegate directly to Helper::legacy_seo($s), which uses /\p{L}\p{N}/u.

Observation 6: BaseModuleController lacks centralized AJAX relation handlers
  └─ Logic: Modal pickers for files, galleries, and videos require standard JSON endpoints across all modules.
  └─ Action: Implement ajax_get_files(), ajax_get_images(), ajax_get_current_media(), and ajax_update_relation() in BaseModuleController.class.php.
```

---

## 3. Caveats & Assumptions

1. **Database Schema Parity**:
   - `mod_user_perm` and `mod_user_role` table column names (`role_id` vs `roll_id`): Models in `AF/Bulletin` support `role_id` / `roll_id` via ORM mappings, but the SQL queries in `toggle_permission()` should use `role_id` (or verify active table column name via schema helper).
2. **Excluded Modules Assurance**:
   - All investigations strictly confirmed zero inclusion of excluded commercial modules (`mod_client`, `mod_service`, `mod_product`, `mod_reference`).
3. **External Dependencies**:
   - `mod_user_acl.js` and `mod_acl.js` rely on Bootstrap Icons (`bi bi-*`) and Material Design 3 tokens already defined in `templates/admin/assets/css/style.css` and `core_modals.css`.

---

## 4. Conclusion & Required Implementation Actions

| Scope | Target File | Action Required |
|---|---|---|
| **R1** | `templates/admin/mod_user/acl.twig` | Replace with modern zero-inline template using `mod_user_acl.css` & `mod_user_acl.js`. |
| **R1** | `mod/mod_user/index.php` | Add `toggle_permission()` AJAX method with DB transaction and `_loadPermissions()`; modernize `acl()`. |
| **R1** | `templates/admin/mod_acl/` | Purge `script.twig`; modernize `list.twig`, `add.twig`, `edit.twig` using `mod_acl.css` & `mod_acl.js`. |
| **R1** | `mod/mod_acl/index.php` & `lang/en.php` | Remove `processSil`/`processDosya`; replace Turkish strings/keys with standard English. |
| **R1** | `templates/admin/partials/header.twig` & `filter_sheet.twig` | Replace all inline styles and `onclick` handlers with `data-action` attributes and CSS classes. |
| **R2** | `lib/TwigLibs.class.php` | Refactor `legacySeoFilter` to delegate to `Helper::legacy_seo($s)`. |
| **R2** | `lib/Orm/Orm.php` & `Obuilder.php` | Case-insensitive column resolution and English error exceptions. |
| **R2** | `lib/BaseModuleController.class.php` | Add `ajax_get_files()`, `ajax_get_images()`, `ajax_get_current_media()`, and `ajax_update_relation()`. |

---

## 5. Verification Method

### 5.1 Code Separation & Quality Guard Audits
```bash
# 1. Check for inline CSS and JS in templates
python3 tools/code_separation_guard.py

# 2. Check for non-English identifiers and language standards
python3 tools/i18n_guard.py

# 3. Check for mobile viewport layout compliance
python3 tools/admin_mobile_check.py
```

### 5.2 PHP Syntax Validation
```bash
find lib/ mod/ templates/ -name "*.php" -exec php -l {} +
```

### 5.3 JavaScript Syntax Validation
```bash
node --check templates/admin/assets/js/mod_user_acl.js
node --check templates/admin/assets/js/mod_acl.js
node --check templates/admin/assets/js/modals/modal_user_menu.js
```

### 5.4 Functional Test Scenarios
1. **ACL Toggle Endpoint**: Send POST to `/mod_user/toggle_permission` with `group_id=1&mod_id=1&role_id=1&state=1` and verify JSON response `{"success": true, ...}` and immediate session permission update.
2. **Kurdish Unicode SEO**: Test `Helper::legacy_seo('Çavdêrî û Şîrove')` -> Expect output `Çavdêrî_û_Şîrove` without underscore corruption.
3. **Admin ACL UI**: Open `/mod_acl/list`, `/mod_user/acl` in mobile viewport simulation -> Verify responsive carousel, search filter, switches, and 0 console errors.
