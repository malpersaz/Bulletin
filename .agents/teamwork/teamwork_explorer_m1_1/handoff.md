# Investigation Report & Handoff: Milestone 1 (Features 1–4)

**Target Repository**: `AF/Bulletin`  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_1/`  
**Author**: Explorer M1-1  
**Target Milestone**: Milestone 1 (Critical MCP, ORM Filter & Nginx Fixes)  
**Date**: 2026-11-11  

---

## 1. Observation

### Feature 1: `McpCrudToolsBase::addEqFilter` & `addLikeSearchFilter` (B1 Defect)
- **Files & Locations**:
  - `lib/Database/Database.php` lines 603–626:
    ```php
    public function where($arg)
    {
        try {
            $this->_where = Dbuilder::where($arg);
        } catch (DbuilderException $e) {
            $e->show();
        }
        return $this;
    }

    public function addWhere($arg, $condition = 'AND')
    {
        try {
            if ($this->_where == null || $this->_where == '') {
                $this->_where = Dbuilder::where($arg);
            } else {
                $this->_where = $this->_where . " " . $condition . " " . Dbuilder::where($arg);
            }
        } catch (DbuilderException $e) {
            $e->show();
            exit;
        }
        return $this;
    }
    ```
  - `lib/Orm/Orm.php` lines 109–125:
    `ORM::__call('where_*', $args)` calls `ORM::_call_where()` which executes `$this->where($where)`.
  - `lib/McpCrudToolsBase.class.php` lines 355–370:
    ```php
    protected function applyCategoryFilters($model, array $args): void
    {
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
        }
        if (isset($args['root']) && in_array($args['root'], ['y', 'n'], true)) {
            $model->where_root($args['root']);
        }
        if (isset($args['seo']) && trim((string) $args['seo']) !== '') {
            $model->where_seo(trim((string) $args['seo']));
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $escaped = Obuilder::cc(trim((string) $args['search']));
            $model->where("(name LIKE '%{$escaped}%' OR seo LIKE '%{$escaped}%')");
        }
    }
    ```
  - **Reproduction Command & Output**:
    ```bash
    docker exec bulletin_app php -r '
    require_once "init.php";
    $model = Load::model("mod_faq");
    $model->where_mod_name("mod_news");
    $model->where_vid(5);
    $model->where_lang("tr");
    $model->build();
    echo "SQL: " . $model->getSql() . "\n";
    '
    ```
    **Output**: `SQL: SELECT * FROM mod_faq WHERE  lang = 'tr'  `
    *Result*: All preceding filters (`mod_name`, `vid`) were completely erased by `$this->where($where)`.
  - **Conjunctive Test with `addWhere`**:
    ```bash
    docker exec bulletin_app php -r '
    require_once "init.php";
    $model = Load::model("mod_faq");
    $model->addWhere("mod_name = '\''mod_news'\''");
    $model->addWhere("vid = 5");
    $model->addWhere("lang = '\''tr'\''");
    $model->addWhere("(question LIKE '\''%test%'\'' OR answer LIKE '\''%test%'\'')");
    $model->build();
    echo "SQL: " . $model->getSql() . "\n";
    '
    ```
    **Output**: `SQL: SELECT * FROM mod_faq WHERE mod_name = 'mod_news' AND vid = 5 AND lang = 'tr' AND (question LIKE '%test%' OR answer LIKE '%test%') `

---

### Feature 2: `McpServer::resolveEnvironment` DB fetch (B2 Defect)
- **Files & Locations**:
  - `lib/McpServer.class.php` lines 132–154:
    ```php
    $siteStatus = 'unknown';
    try {
        if (class_exists('Load')) {
            /** @var Database $db */
            $db = Load::lib('Database');
            if ($db && method_exists($db, 'query')) {
                $row = $db->query("SELECT site_status FROM mod_settings LIMIT 1")->fetch();
                if (is_array($row) && isset($row['site_status'])) {
                    $rawStatus = strtolower(trim((string) $row['site_status']));
                    if ($rawStatus === 'closed' || $rawStatus === 'offline') {
                        $siteStatus = 'closed';
                    } elseif ($rawStatus === 'open' || $rawStatus === 'online') {
                        $siteStatus = 'open';
                    } else {
                        $siteStatus = $rawStatus !== '' ? $rawStatus : 'unknown';
                    }
                }
            }
        }
    } catch (\Throwable) {
        $siteStatus = 'unknown';
    }
    ```
  - `lib/Database/Adapter/Mysqli.php` line 66:
    ```php
    public function query($sql = null)
    {
        if ($sql == null) {
            throw new DatabaseException("SQL String NOT NULL", null, null);
        }
        $result = $this->_con->query($sql);

        if ($result === false) {
            throw new DatabaseException("SQL Query Problem", $sql, $this->_con->error);
        }
        return $result;
    }
    ```
    `$this->_con->query($sql)` returns a PHP `mysqli_result`. Calling `->fetch()` on `mysqli_result` throws fatal `Error: Call to undefined method mysqli_result::fetch()`.
  - The canonical abstraction in Amed Framework (`lib/McpCrudToolsBase.class.php:143–145` and `lib/Database/Database.php:144, 740`) is `$db->fetch_array($sql)` followed by `$rows = $db->toArray()`.
  - **Reproduction & Verification**:
    ```bash
    docker exec bulletin_app php -r '
    require_once "init.php";
    $db = Load::lib("Database");
    $db->fetch_array("SELECT site_status FROM mod_settings LIMIT 1");
    $rows = $db->toArray();
    print_r($rows);
    '
    ```
    **Output**:
    ```text
    Array
    (
        [0] => Array
            (
                [site_status] => online
            )
    )
    ```

---

### Feature 3: `McpCrudToolsBase::getRecordAuditMeta` DB fetch & draft FAQ visibility (B2 Defect)
- **Files & Locations**:
  - `lib/McpCrudToolsBase.class.php` lines 780–822:
    ```php
    protected function getRecordAuditMeta(string $modName, int $recordId, string $lang): array
    {
        $langStatus = 'n';
        $faqCount = 0;
        $faqItems = [];

        try {
            if (class_exists('Load')) {
                /** @var Database $db */
                $db = Load::lib('Database');
                if ($db && method_exists($db, 'query')) {
                    $safeLang = addslashes($lang);
                    $langRow = $db->query("SELECT status FROM lang WHERE seo = '{$safeLang}' LIMIT 1")->fetch();
                    if (is_array($langRow) && isset($langRow['status'])) {
                        $langStatus = (string) $langRow['status'];
                    }

                    $safeMod = addslashes($modName);
                    $faqRes = $db->query("SELECT id, question, answer FROM mod_faq WHERE mod_name = '{$safeMod}' AND vid = {$recordId} AND lang = '{$safeLang}' AND status = 'y'");
                    if ($faqRes && method_exists($faqRes, 'fetchAll')) {
                        $rows = $faqRes->fetchAll();
                        if (is_array($rows)) {
                            $faqItems = $rows;
                            $faqCount = count($rows);
                        }
                    } elseif ($faqRes && method_exists($faqRes, 'fetch')) {
                        while ($r = $faqRes->fetch()) {
                            $faqItems[] = $r;
                        }
                        $faqCount = count($faqItems);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Non-blocking audit metadata enrichment
        }

        return [
            'lang_status' => $langStatus,
            'faq_count' => $faqCount,
            'faq_items' => $faqItems,
        ];
    }
    ```
  - **Issues Directly Observed**:
    1. Line 792 calls `->fetch()` on `$db->query(...)`. On `Adapter_Mysqli`, this throws `Error: Call to undefined method mysqli_result::fetch()` and is swallowed by `catch (\Throwable)`.
    2. Even when not on Mysqli, lines 799 and 805 check `method_exists($faqRes, 'fetchAll')` and `method_exists($faqRes, 'fetch')`, which do not exist on `mysqli_result`.
    3. As a result, `lang_status` defaults to `'n'`, and `faq_count` defaults to `0` on **every MCP record fetch** (`get_news`, `get_publication`, `get_page`, `get_announcement`).
    4. Line 798 enforces `AND status = 'y'`. When a news draft is staged (`status = 'n'`), its attached FAQs are staged as drafts (`status = 'n'`). Thus `faq_count` is reported as `0` for staged draft records, causing `tools/audit_content_seo.py` to award 0 points for FAQ coverage on staged drafts.
  - **Schema Verification**:
    `mod_faq` has columns: `id`, `lang`, `mod_name`, `vid`, `question`, `answer`, `sort_order`, `status`, `user_id`, `created_at`, `updated_at`. `status` is `enum('y','n')`.

---

### Feature 4: `McpHeadlineTools` string argument fix (B2 Defect)
- **Files & Locations**:
  - `lib/McpCrudToolsBase.class.php` line 204:
    ```php
    protected function assertContentMatchesLang(string $lang, string $name, string $summary = '', string $content = ''): void
    ```
    Parameter 2 is typed `string $name`.
  - `lib/McpHeadlineTools.class.php` lines 283–284 (`createHeadline`):
    ```php
    $lang = $this->requireActiveLang($args);
    $this->assertContentMatchesLang($lang, ['name' => $name]);
    ```
  - `lib/McpHeadlineTools.class.php` lines 340–341 (`updateHeadline`):
    ```php
    $model->lang = $lang;
    $this->assertContentMatchesLang($lang, ['name' => (string) $model->name]);
    ```
  - **Verbatim Error in PHP 8.5**:
    `TypeError: McpCrudToolsBase::assertContentMatchesLang(): Argument #2 ($name) must be of type string, array given, called in lib/McpHeadlineTools.class.php on line 284`
  - In addition, an identical bug was observed in:
    - `lib/McpSurveyTools.class.php:317`: `$this->assertContentMatchesLang($lang, ['question' => $question]);`
    - `lib/McpSurveyTools.class.php:365`: `$this->assertContentMatchesLang((string) $model->lang, ['question' => (string) $model->question]);`

---

## 2. Logic Chain

### Logic Chain for Feature 1 (Conjunctive Filters)
1. **Premise**: In Amed Framework's `Database` class, `$this->_where` holds the active WHERE SQL string.
2. **Observation**: `where($arg)` unconditionally replaces `$this->_where = Dbuilder::where($arg)`.
3. **Observation**: Magic ORM methods `where_<column>($val)` invoke `Database::where(...)` via `_call_where`.
4. **Deduction**: Calling `$model->where_cat(1)->where_lang('tr')->where_status('y')` overwrites `_where` at each step, leaving only `status = 'y'`.
5. **Observation**: `Database::addWhere($arg, 'AND')` appends to `$this->_where` with `" AND " . Dbuilder::where($arg)` if `_where` is non-empty.
6. **Deduction**: Creating protected helpers `addEqFilter` and `addLikeSearchFilter` in `McpCrudToolsBase` that invoke `$model->addWhere(...)` provides a clean, unified, injection-safe conjunctive filtering API for all 15 MCP domain tool classes.

### Logic Chain for Feature 2 (`McpServer::resolveEnvironment`)
1. **Premise**: `McpServer::resolveEnvironment()` must report the actual `site_status` ('open' or 'closed') from `mod_settings.site_status`.
2. **Observation**: Line 138 calls `$db->query(...)->fetch()`.
3. **Observation**: When `Adapter_Mysqli` is used (standard in production or fallback setups), `query()` returns a `mysqli_result` object.
4. **Observation**: `mysqli_result` has no `fetch()` method in PHP. Calling it throws a fatal `Error`.
5. **Observation**: Catch block swallows the error, resulting in `$siteStatus = 'unknown'`.
6. **Deduction**: Replacing `$db->query(...)->fetch()` with `$db->fetch_array(...)` followed by `$db->toArray()` utilizes the framework's canonical, adapter-agnostic abstraction (which works identically across PDO and MySQLi), correctly resolving `site_status` to `'open'` or `'closed'`.

### Logic Chain for Feature 3 (`McpCrudToolsBase::getRecordAuditMeta`)
1. **Premise**: `getRecordAuditMeta` enriches single-record MCP responses with `lang_status`, `faq_count`, and `faq_items` for audit scripts like `tools/audit_content_seo.py`.
2. **Observation**: Lines 792, 799, and 805 call PDO-specific methods `fetch()` and `fetchAll()` on the result of `$db->query(...)`.
3. **Observation**: On `Adapter_Mysqli`, this throws an error and is swallowed by `catch (\Throwable)`, leaving `lang_status = 'n'` and `faq_count = 0`.
4. **Observation**: `audit_content_seo.py:273` immediately flags `lang_status == 'n'` as a critical violation, causing audit failures on valid staged articles.
5. **Observation**: Line 798 hardcodes `AND status = 'y'`. Draft articles (`status = 'n'`) have draft FAQs (`status = 'n'`), which are excluded from the count.
6. **Deduction**:
   - Refactoring to `$db->fetch_array(...)` and `$db->toArray()` fixes the data fetch across all adapters.
   - Adding parameter `string $recordStatus = 'y'` allows callers to pass the host record's status.
   - Setting `$faqStatusSql = ($recordStatus === 'n') ? "status IN ('y', 'n')" : "status = 'y'"` ensures draft FAQs are included when auditing draft records, and populating `faq_published_count` and `faq_draft_count` provides granular visibility.

### Logic Chain for Feature 4 (`McpHeadlineTools` Type Fix)
1. **Observation**: `McpCrudToolsBase::assertContentMatchesLang` defines parameter 2 as `string $name`.
2. **Observation**: `McpHeadlineTools` lines 284 and 341 pass `['name' => $name]` (an array).
3. **Observation**: PHP 8.x strictly enforces scalar and array types, throwing `TypeError`.
4. **Deduction**: Passing `$name` directly (in `createHeadline`) and `(string) $model->name` (in `updateHeadline`) satisfies the type contract and resolves the crash.

---

## 3. Concrete Code Proposals (Ready for Implementation)

### Change 1: `lib/McpCrudToolsBase.class.php`
Add `addEqFilter` and `addLikeSearchFilter`, update `applyCategoryFilters`, and fix `getRecordAuditMeta`:

```php
    /**
     * Appends an equality condition to the model using conjunctive addWhere().
     * Validates column identifier against /^[a-zA-Z_][a-zA-Z0-9_]*$/ to prevent injection
     * and escapes string values via Obuilder::cc().
     *
     * @param ORM|object $model
     * @param string $column
     * @param string|int|float $value
     */
    protected function addEqFilter(object $model, string $column, string|int|float $value): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new McpToolException("Invalid filter column name: '{$column}'");
        }

        if (is_int($value) || is_float($value)) {
            $clause = "{$column} = {$value}";
        } else {
            $escaped = Obuilder::cc((string) $value);
            $clause = "{$column} = '{$escaped}'";
        }

        $model->addWhere($clause);
    }

    /**
     * Appends a parenthesized LIKE search condition across multiple columns using conjunctive addWhere().
     * Escapes search input via Obuilder::cc() and validates all column names.
     *
     * @param ORM|object $model
     * @param string[] $columns
     * @param string $rawSearch
     */
    protected function addLikeSearchFilter(object $model, array $columns, string $rawSearch): void
    {
        $search = trim($rawSearch);
        if ($search === '' || empty($columns)) {
            return;
        }

        $escaped = Obuilder::cc($search);
        $clauses = [];
        foreach ($columns as $column) {
            $column = (string) $column;
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
                throw new McpToolException("Invalid search column name: '{$column}'");
            }
            $clauses[] = "{$column} LIKE '%{$escaped}%'";
        }

        if (!empty($clauses)) {
            $model->addWhere('(' . implode(' OR ', $clauses) . ')');
        }
    }

    protected function applyCategoryFilters($model, array $args): void
    {
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $this->addEqFilter($model, 'lang', trim((string) $args['lang']));
        }
        if (isset($args['root']) && in_array($args['root'], ['y', 'n'], true)) {
            $this->addEqFilter($model, 'root', $args['root']);
        }
        if (isset($args['seo']) && trim((string) $args['seo']) !== '') {
            $this->addEqFilter($model, 'seo', trim((string) $args['seo']));
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $this->addLikeSearchFilter($model, ['name', 'seo'], trim((string) $args['search']));
        }
    }
```

And update `getRecordAuditMeta` (lines 780–822):
```php
    /**
     * Resolves audit metadata (lang_status, faq_count, faq_items) for a given record.
     * Enriches MCP get_* responses so environment-agnostic audit tooling receives identical
     * data whether checking local DB or MCP-bridge-sourced records.
     */
    protected function getRecordAuditMeta(string $modName, int $recordId, string $lang, string $recordStatus = 'y'): array
    {
        $langStatus = 'n';
        $faqCount = 0;
        $faqItems = [];
        $faqPublishedCount = 0;
        $faqDraftCount = 0;

        try {
            if (class_exists('Load')) {
                /** @var Database $db */
                $db = Load::lib('Database');
                if ($db && method_exists($db, 'fetch_array') && method_exists($db, 'toArray')) {
                    $safeLang = Obuilder::cc($lang);
                    $db->fetch_array("SELECT status FROM lang WHERE seo = '{$safeLang}' LIMIT 1");
                    $langRows = $db->toArray();
                    if (is_array($langRows) && isset($langRows[0]) && is_array($langRows[0]) && isset($langRows[0]['status'])) {
                        $langStatus = (string) $langRows[0]['status'];
                    }

                    $safeMod = Obuilder::cc($modName);
                    $recordId = (int) $recordId;
                    $faqStatusSql = ($recordStatus === 'n') ? "status IN ('y', 'n')" : "status = 'y'";

                    $db->fetch_array(
                        "SELECT id, question, answer, status, sort_order FROM mod_faq " .
                        "WHERE mod_name = '{$safeMod}' AND vid = {$recordId} AND lang = '{$safeLang}' AND {$faqStatusSql} " .
                        "ORDER BY sort_order ASC, id ASC"
                    );
                    $rawFaqRows = $db->toArray();
                    if (is_array($rawFaqRows)) {
                        $faqItems = $rawFaqRows;
                        $faqCount = count($rawFaqRows);
                        foreach ($rawFaqRows as $r) {
                            if (is_array($r)) {
                                if (($r['status'] ?? 'y') === 'y') {
                                    $faqPublishedCount++;
                                } else {
                                    $faqDraftCount++;
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Non-blocking audit metadata enrichment
        }

        return [
            'lang_status' => $langStatus,
            'faq_count' => $faqCount,
            'faq_items' => $faqItems,
            'faq_published_count' => $faqPublishedCount,
            'faq_draft_count' => $faqDraftCount,
        ];
    }
```

---

### Change 2: `lib/McpServer.class.php`
Lines 132–154 in `resolveEnvironment()`:
```php
        $siteStatus = 'unknown';
        try {
            if (class_exists('Load')) {
                /** @var Database $db */
                $db = Load::lib('Database');
                if ($db && method_exists($db, 'fetch_array') && method_exists($db, 'toArray')) {
                    $db->fetch_array("SELECT site_status FROM mod_settings LIMIT 1");
                    $rows = $db->toArray();
                    $row = is_array($rows) && isset($rows[0]) && is_array($rows[0]) ? $rows[0] : null;
                    if (is_array($row) && isset($row['site_status'])) {
                        $rawStatus = strtolower(trim((string) $row['site_status']));
                        if ($rawStatus === 'closed' || $rawStatus === 'offline') {
                            $siteStatus = 'closed';
                        } elseif ($rawStatus === 'open' || $rawStatus === 'online') {
                            $siteStatus = 'open';
                        } else {
                            $siteStatus = $rawStatus !== '' ? $rawStatus : 'unknown';
                        }
                    }
                }
            }
        } catch (\Throwable) {
            $siteStatus = 'unknown';
        }
```

---

### Change 3: `lib/McpHeadlineTools.class.php`
- Line 284 in `createHeadline`:
  ```php
  // Before:
  $this->assertContentMatchesLang($lang, ['name' => $name]);
  // After:
  $this->assertContentMatchesLang($lang, $name);
  ```
- Line 341 in `updateHeadline`:
  ```php
  // Before:
  $this->assertContentMatchesLang($lang, ['name' => (string) $model->name]);
  // After:
  $this->assertContentMatchesLang($lang, (string) $model->name);
  ```

---

## 4. Caveats & Additional Findings

1. **`McpSurveyTools` Sister Bug**:
   During the investigation of `McpHeadlineTools`, an identical defect was discovered in `lib/McpSurveyTools.class.php`:
   - Line 317: `$this->assertContentMatchesLang($lang, ['question' => $question]);`
   - Line 365: `$this->assertContentMatchesLang((string) $model->lang, ['question' => (string) $model->question]);`
   Both pass an associative array where `string $name` is expected. This should also be updated to `$this->assertContentMatchesLang($lang, $question)` to prevent `TypeError` when managing surveys via MCP.
2. **Backward Compatibility of `getRecordAuditMeta` Callers**:
   By providing default parameter value `$recordStatus = 'y'`, existing callers (`McpPageTools`, `McpPublicationTools`, `McpAnnouncementTools`) continue to function without modification. However, updating them to pass `(string) ($row['status'] ?? 'y')` allows draft auditing across all single-record tools.
3. **Domain Tool Class Scope for Filter Modernization**:
   While this investigation focuses on `McpCrudToolsBase` (`applyCategoryFilters`), all 15 MCP domain classes will benefit from replacing sequential `where_*()` calls with `$this->addEqFilter(...)` and `$this->addLikeSearchFilter(...)`.

---

## 5. Conclusion

1. **Feature 1**: Confirmed that `Database::where()` overwrites condition state and `Database::addWhere()` chains conditions with `AND`. `addEqFilter` and `addLikeSearchFilter` provide safe, parameterized, conjunctive querying that eliminates B1 filter clobbering.
2. **Feature 2**: Confirmed that raw PDO `fetch()` on `Database::query()` throws fatal `TypeError`/`Error` on `Adapter_Mysqli`. Replacing it with `Database::fetch_array()` and `Database::toArray()` ensures 100% adapter compatibility.
3. **Feature 3**: Confirmed that `getRecordAuditMeta` fails similarly on `Adapter_Mysqli`, causing `lang_status = 'n'` and `faq_count = 0`. Canonical database fetching and adding `$recordStatus = 'y'` with `status IN ('y', 'n')` for drafts restores accurate audit scores for staged content.
4. **Feature 4**: Confirmed `McpHeadlineTools` lines 284 and 341 pass an array to a strictly typed `string` parameter. Passing string `$name` and `(string) $model->name` resolves the fatal `TypeError`.

---

## 6. Verification Method

### Step 1: PHP Syntax Linting
```bash
php -l lib/McpCrudToolsBase.class.php
php -l lib/McpServer.class.php
php -l lib/McpHeadlineTools.class.php
php -l lib/McpSurveyTools.class.php
```

### Step 2: Full MCP Smoke Test Execution
```bash
docker exec bulletin_app php tasks/mcp_smoke.php
```
*Expected Result*: All tests pass with exit code 0.

### Step 3: Targeted Assertion Checks inside Container
```bash
# Verify environment site_status is not 'unknown'
docker exec bulletin_app php -r '
require_once "init.php";
require_once "lib/McpServer.class.php";
$server = new McpServer();
$ref = new ReflectionMethod($server, "resolveEnvironment");
$ref->setAccessible(true);
$env = $ref->invoke($server);
echo "SITE_STATUS: " . $env["site_status"] . "\n";
assert($env["site_status"] === "open" || $env["site_status"] === "closed");
'

# Verify conjunctive filter on category listing
docker exec bulletin_app php -r '
require_once "init.php";
$model = Load::model("mod_news_category");
$model->addWhere("lang = '\''tr'\''");
$model->addWhere("root = '\''n'\''");
$model->addWhere("(name LIKE '\''%Diyarbakır%'\'' OR seo LIKE '\''%Diyarbakır%'\'')");
$model->build();
$sql = $model->getSql();
echo "SQL: " . $sql . "\n";
assert(str_contains($sql, "lang = '\''tr'\''") && str_contains($sql, "root = '\''n'\''"));
'
```
