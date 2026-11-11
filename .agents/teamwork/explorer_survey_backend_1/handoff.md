# Handoff Report: Investigation of Backend, MCP Tools, ORM, Nginx & mod_news (v1.0.63)

**Author:** `teamwork_explorer`  
**State Folder:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_survey_backend_1/`  
**Reference Plan:** `docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md`  
**Date:** 2026-11-11  

---

## 1. Observation

Direct code observations across the eight designated focus areas:

### 1.1 ORM Filter Clobbering Across 15 MCP Tool Classes (B1)
- **`lib/Database/Database.php:603-611`**:
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
  ```
  `Database::where($arg)` directly assigns and replaces `$this->_where`.
- **`lib/Database/Database.php:613-628`**:
  ```php
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
  `addWhere()` exists on `Database` and chains conditions using `AND` (or specified condition).
- **`lib/Orm/Orm.php:109-126`**:
  ```php
  private function _call_where($result, $value)
  {
      try {
          ...
          $where = Obuilder::where($parsed_clause, $value, $this->_orm_coloms, $this->_orm_table);
          $this->where($where);
      }
  ```
  Dynamic `$model->where_<column>($value)` magic calls invoke `_call_where()`, which calls `$this->where($where)`. Because `ORM extends Database`, this overwrites `$this->_where`.
- **15 MCP Domain Tool Classes with Sequential Overwriting Filters**:
  1. `lib/McpNewsTools.class.php:1252-1272` (`applyNewsFilters`):
     Sequential calls to `where_category_id`, `where_lang`, `where_status`, `where_show_on_homepage`, `where_seo`, followed by `$model->where('name LIKE ...')`.
  2. `lib/McpCrudToolsBase.class.php:355-370` (`applyCategoryFilters`):
     Sequential calls to `where_lang`, `where_root`, `where_seo`, followed by `$model->where("(name LIKE ... OR seo LIKE ...)")`.
  3. `lib/McpFaqTools.class.php:443-460` (`applyFaqFilters`):
     Sequential calls to `where_mod_name`, `where_vid`, `where_lang`, `where_status`, followed by `$model->where('question LIKE ...')`.
  4. `lib/McpPublicationTools.class.php:922-942` (`applyPublicationFilters`):
     Sequential calls to `where_category_id`, `where_lang`, `where_status`, `where_deck`, `where_slider`, followed by `$model->where('name LIKE ...')`.
  5. `lib/McpPageTools.class.php:786-810` (`applyPageFilters`):
     Sequential calls to `where_lang`, `where_seo`, `where_parent_id`, `where_root`, `where_status`, `where_deck`, followed by `$model->where(...)`.
  6. `lib/McpAnnouncementTools.class.php:383-397` (`applyAnnouncementFilters`):
     Sequential calls to `where_lang`, `where_status`, `where_deck`, followed by `$model->where('name LIKE ...')`.
  7. `lib/McpAttachmentTools.class.php:429-445` (`applyAttachmentFilters`):
     Sequential calls to `where_mod_name`, `where_vid`, followed by `$model->where('ext LIKE ...')` and `$model->where('original_name LIKE ...')`.
  8. `lib/McpDocumentTools.class.php:555-566` (`applyDocumentFilters`):
     Sequential calls to `where_lang`, `where_status`, followed by `$model->where('name LIKE ...')`.
  9. `lib/McpHeadlineTools.class.php:398-409` (`applyHeadlineFilters`):
     Sequential calls to `where_lang`, `where_status`, followed by `$model->where('name LIKE ...')`.
  10. `lib/McpMenuTools.class.php:387-398, 420-431` (`listMenuItems`):
      Sequential calls to `where_lang`, `where_parent_id`, `where_status`, followed by `$model->where('name LIKE ...')`.
  11. `lib/McpPopupTools.class.php:527-538` (`applyPopupFilters`):
      Sequential calls to `where_lang`, `where_status`, followed by `$model->where('name LIKE ...')`.
  12. `lib/McpRedirectTools.class.php:385-394` (`applyRedirectFilters`):
      `where_status_code`, followed by `$model->where("(old_url LIKE ... OR new_url LIKE ...)")`.
  13. `lib/McpSettingsTools.class.php:1356-1365` (`listCookieDeclarations`) & `1532-1556` (`listAffiliations`):
      `where_lang` -> `where_category`; `where_is_active` -> `where('(name LIKE ... OR url LIKE ...)')`.
  14. `lib/McpSliderTools.class.php:607-621` (`applySliderFilters`):
      Sequential calls to `where_lang`, `where_status`, `where_target`, followed by `$model->where('name LIKE ...')`.
  15. `lib/McpSurveyTools.class.php:555-566` (`applySurveyFilters`):
      Sequential calls to `where_lang`, `where_status`, followed by `$model->where('question LIKE ...')`.
- Contrast with `lib/McpEventTools.class.php:731-750` and `lib/McpVideoTools.class.php:1854-1864`, which already use `$model->addWhere(...)`.

### 1.2 `Adapter_Mysqli` Swallowed Fatal Errors & `TypeError` (B2)
- **`lib/Database/Adapter/Mysqli.php:61-71`**:
  ```php
  public function query($sql = null)
  {
      ...
      $result = $this->_con->query($sql);
      ...
      return $result;
  }
  ```
  `Adapter_Mysqli::query()` returns a native PHP `mysqli_result` object.
- `mysqli_result` does not have `->fetch()` or `->fetchAll()`. It provides `fetch_assoc()`, `fetch_row()`, `fetch_all()`.
- **`lib/McpServer.class.php:136-138`**:
  ```php
  $db = Load::lib('Database');
  if ($db && method_exists($db, 'query')) {
      $row = $db->query("SELECT site_status FROM mod_settings LIMIT 1")->fetch();
  ```
  Calling `->fetch()` on `mysqli_result` throws fatal `Error: Call to undefined method mysqli_result::fetch()`. Caught by `catch (\Throwable)`, causing `site_status` to default permanently to `'unknown'`.
- **`lib/McpCrudToolsBase.class.php:789-815` (`getRecordAuditMeta`)**:
  ```php
  $langRow = $db->query("SELECT status FROM lang WHERE seo = '{$safeLang}' LIMIT 1")->fetch();
  ```
  Throws fatal `Error: Call to undefined method mysqli_result::fetch()`. Swallowed by `catch (\Throwable)`, leaving `$langStatus = 'n'`, `$faqCount = 0`, `$faqItems = []`.
- **`lib/McpCrudToolsBase.class.php:143-145` (`getSiteLanguageMap`)**:
  Canonical framework Database fetch pattern:
  ```php
  $db = Load::lib('Database');
  $db->fetch_array('SELECT seo, name, status, is_default FROM lang ORDER BY is_default DESC, seo ASC');
  $rows = $db->toArray();
  ```
- **`lib/McpHeadlineTools.class.php:284, 341`**:
  Calls `$this->assertContentMatchesLang($lang, ['name' => $name]);` and `$this->assertContentMatchesLang($lang, ['name' => (string) $model->name]);`.
  `McpCrudToolsBase::assertContentMatchesLang` has signature:
  `protected function assertContentMatchesLang(string $lang, string $name, string $summary = '', string $content = ''): void`
  Passing an array triggers fatal `TypeError: Argument #2 ($name) must be of type string, array given`.

### 1.3 `McpNewsTools` Root Category Guard, SEO Normalization & `get_news` Enrichment (B3)
- **`lib/McpNewsTools.class.php:710, 871`**:
  `$this->assertRecordMatchesLang('mod_news_category', $categoryId, $lang, 'Category');` checks language match, but does not check `($record->root ?? 'n') === 'y'`.
  In `lib/Traits/ModuleValidatorTrait.php:77`, admin validator rejects root categories:
  ```php
  if (isset($catModel->root) && $catModel->root === 'y') { ... return false; }
  ```
- **`lib/McpNewsTools.class.php:725-728, 894-898`**:
  ```php
  $seo = trim((string) ($args['seo'] ?? ''));
  if ($seo === '') {
      $seo = self::makeSeo($name, $lang);
  }
  ```
  If explicit `$args['seo']` is passed, `self::makeSeo()` is bypassed.
- **`lib/McpNewsTools.class.php:670-686` (`getNews`)**:
  `$model = $this->loadRecordByIdOrSeo('mod_news', $args, 'News');`
  Returns `user_id` and `category_id`, but lacks `username`, `author_fullname`, `category_name`, and `category_seo`. Also, `getRecordAuditMeta` does not receive record status (`$row['status']`), causing draft FAQs on draft articles to be ignored (`status = 'y'` filter in audit meta).

### 1.4 `McpFaqTools` Enhancements (B3)
- `lib/McpFaqTools.class.php` currently defines: `list_faqs`, `get_faq`, `create_faq`, `update_faq`, `delete_faq`.
- `listFaqs()` hardcodes select columns without `answer`:
  `$model->select('id, lang, mod_name, vid, question, sort_order, status, created_at, updated_at');`
- No `batch_create_faqs` tool exists (staging 6 FAQs requires 6 sequential network calls).
- No `list_faq_modules` tool exists to inspect registered `mod_faq_mod` host modules (unlike `McpGalleryTools::listGalleryModules()`).

### 1.5 `mod_news` Visual Schema Modernization (`image_alt`, `image_credit`) (B5)
- **`mod/mod_news/mod_news.sql:20`**: Contains `image varchar(255) DEFAULT NULL`, but lacks `image_alt` and `image_credit`.
- **`mod/mod_news/model/mod_news.model.php`**: Lacks properties `$image_alt`, `$image_credit`.
- **`mod/mod_news/model/newses.model.php`**: `CREATE VIEW newses AS SELECT ...` lacks `image_alt` and `image_credit`.
- **`mod/mod_news/index.php:107`**: `cleanNewsTextEntities()` list of sanitized text fields does not include `image_alt` and `image_credit`.
- **`templates/amed/index/mod_news/news.twig:160-177, 411-417`**:
  Hero rendering:
  ```twig
  <div class="detail-hero">
      <a href="{{ site.iaddress }}mod_news/{{ d.image }}" ...>
          <img ... alt="{{ d.name }}" title="{{ d.name }}">
      </a>
  </div>
  ```
  Uses `<div>` without `<figure>`, lacks `<figcaption class="news-hero-caption">`, and Schema.org `ImageObject` lacks `caption` and `creditText`.

### 1.6 Nginx Configuration `docker/nginx/prod.conf` (A6)
- **`docker/nginx/prod.conf:84-88`**:
  ```nginx
  # Block access to sensitive files
  location ~ /\. {
      deny all;
      access_log off;
      log_not_found off;
  }
  ```
  Nginx regex `location ~ /\. ` intercepts all URIs containing `/.`, including `/.well-known/oauth-authorization-server`, returning HTTP 403 Forbidden.
- In contrast, `.htaccess:23, 28` was patched in v1.0.61, but production runs Nginx.

### 1.7 `tasks/mcp_smoke.php` and `mcp_health_check.py` Stage 7 (A6, B1, B2)
- **`tools/mcp_health_check.py:525-530, 551-556`**:
  When HTTP 403 is received on `/.well-known/oauth-*`, it calls `self.log_pass()` with a warning about `.htaccess deployment`, masking the Nginx 403 failure.
- **`tasks/mcp_smoke.php:675`**:
  Asserts `in_array($initRes['result']['_meta']['environment']['site_status'], ['open', 'closed', 'unknown'], true)`. Accepts `'unknown'` as valid because of the B2 bug.
- `tasks/mcp_smoke.php` has no tests for multi-filter conjunctive behavior on `list_faqs` or `list_news`, no assertions on `get_news` `lang_status === 'y'`, and no tests for `create_headline`, `batch_create_faqs`, or `list_faq_modules`.

---

## 2. Logic Chain

1. **Filter Clobbering Mechanics**:
   - Step 1: `Database::where($arg)` assigns `$this->_where = Dbuilder::where($arg)`.
   - Step 2: Every dynamic `$model->where_<col>($val)` call delegates via `ORM::_call_where` directly to `Database::where()`, overwriting `$this->_where`.
   - Step 3: When multiple filter conditions are passed (e.g. `category_id`, `lang`, `status`, `search`), each call replaces the previous condition rather than chaining with `AND`. Only the final filter executed survives.
   - Step 4: `Database::addWhere($arg, $condition = 'AND')` appends `$this->_where = $this->_where . " " . $condition . " " . Dbuilder::where($arg)`.
   - Step 5: Implementing `addEqFilter($model, $column, $value)` and `addLikeSearchFilter($model, $columns, $rawSearch)` on `McpCrudToolsBase` allows all 15 MCP tool classes to safely chain conditions using `$model->addWhere(...)` with parenthesized `OR` clauses, while avoiding any changes to `ORM::_call_where` that could destabilize legacy admin/frontend controllers.

2. **Database Adapter & Return Type Handling**:
   - Step 1: `Load::lib('Database')` utilizes `Adapter_Mysqli`. `Adapter_Mysqli::query()` returns a native PHP `mysqli_result`.
   - Step 2: `mysqli_result` has no `fetch()` method. Calling `->fetch()` triggers an uncaught `Error` in `McpServer::resolveEnvironment()` and `McpCrudToolsBase::getRecordAuditMeta()`.
   - Step 3: The canonical, tested way to fetch tabular data in Amed Framework is `$db->fetch_array($sql); $rows = $db->toArray();`.
   - Step 4: Using `$db->fetch_array(...)` resolves `site_status` cleanly from `mod_settings` and retrieves `status` from `lang` and FAQ records from `mod_faq`.
   - Step 5: For draft records (`status = 'n'`), `getRecordAuditMeta` must query `status IN ('y', 'n')` so that staged draft FAQs are counted during pre-publication auditing.

3. **Content Integrity & Validation in `McpNewsTools` and `McpHeadlineTools`**:
   - Step 1: Admin controllers enforce that `root = 'y'` categories are organizational containers that cannot hold articles. Checking `($record->root ?? 'n') === 'y'` in `assertRecordMatchesLang` or `McpNewsTools` prevents orphan/invisible articles.
   - Step 2: Explicit `seo` arguments must always pass through `self::makeSeo($rawSeo !== '' ? $rawSeo : $name, $lang)` to ensure valid characters, dash-to-underscore conversion, and `U+0307` strip.
   - Step 3: `getNews()` can enrich author information from `mod_user` and category details from `mod_news_category`, returning a single-record envelope where `payload.item` contains all enriched fields without recursive nesting.
   - Step 4: `McpHeadlineTools` calling `assertContentMatchesLang($lang, ['name' => $name])` must pass string `$name` directly to avoid `TypeError`.

4. **Nginx Location Precedence**:
   - Step 1: Nginx directive `location ~ /\. { deny all; }` matches any request with `/.`.
   - Step 2: Under Nginx matching rules, prefix location with `^~` modifier (`location ^~ /.well-known/ { try_files $uri /index.php?$query_string; }`) takes precedence over regular expression locations and halts further regex searches.
   - Step 3: This routes all `/.well-known/*` endpoints to the CMS router while preserving the sensitive dotfile deny rule for `/.git`, `/.env`, etc.

---

## 3. Caveats

- **No Code Modifications Made**: Investigation was strictly read-only in accordance with role constraints.
- **Production Staging Environment**: Testing of `docker/nginx/prod.conf` requires Nginx reload in the container environment.
- **SQLite vs MySQL in `newses` View**: `newses_model::ensureViewExists()` dynamically creates a MySQL `VIEW newses`. When adding `image_alt` and `image_credit`, the view definition query and `hasViewColumn` checks must be updated so existing views are dropped and regenerated.
- No caveats on backend architecture or root-cause identification.

---

## 4. Conclusion

All 8 focus areas specified in the dispatch have been investigated with exact file locations, line numbers, and root causes confirmed.

Key implementation requirements for the implementer:
1. **`lib/McpCrudToolsBase.class.php`**:
   - Implement `addEqFilter(object $model, string $column, string|int $value): void` using regex column validation `/^[a-zA-Z_][a-zA-Z0-9_]*$/` and `$model->addWhere(...)`.
   - Implement `addLikeSearchFilter(object $model, array $columns, string $rawSearch): void` escaping search terms via `\Obuilder::cc()` and appending parenthesized `(` . implode(' OR ', ...) . `)`.
   - Fix `getRecordAuditMeta()` to use `$db->fetch_array(...)` and `$db->toArray()`, accepting `$recordStatus = 'y'` and counting draft FAQs when `$recordStatus === 'n'`.
2. **15 MCP Domain Tool Classes**:
   - Replace sequential `$model->where_*()` and `$model->where(...)` calls with `$this->addEqFilter(...)` and `$this->addLikeSearchFilter(...)`.
3. **`lib/McpServer.class.php`**:
   - Replace `$db->query(...)->fetch()` in `resolveEnvironment()` with `$db->fetch_array(...)` and `$db->toArray()`.
4. **`lib/McpHeadlineTools.class.php`**:
   - Fix lines 284 and 341 to pass string `$name` to `assertContentMatchesLang`.
5. **`lib/McpNewsTools.class.php`**:
   - Enforce `($record->root ?? 'n') !== 'y'` on category validation.
   - Always run `$seo = self::makeSeo($rawSeo !== '' ? $rawSeo : $name, $lang)`.
   - Enrich `getNews()` with `username`, `author_fullname`, `category_name`, and `category_seo`.
   - Add schema support for `image_alt` and `image_credit`.
6. **`lib/McpFaqTools.class.php`**:
   - Add `batch_create_faqs` with pre-commit transactional validation.
   - Add `list_faq_modules` querying `mod_faq_mod`.
   - Add `include_answer: bool` parameter to `list_faqs`.
7. **`mod_news` and Templates**:
   - Add `image_alt` and `image_credit` to `mod/mod_news/mod_news.sql`, `mod_news_model`, `newses_model`, controller, admin `edit.twig`, and public `news.twig` (`<figure class="detail-hero">`, `<figcaption class="news-hero-caption">`, JSON-LD `caption` and `creditText`).
8. **`docker/nginx/prod.conf` & Health Check**:
   - Add `location ^~ /.well-known/ { try_files $uri /index.php?$query_string; }` before `location ~ /\.`.
   - Remove 403 mask in `tools/mcp_health_check.py` Stage 7.
   - Add regression tests in `tasks/mcp_smoke.php`.

---

## 5. Verification Method

To independently verify after implementation:

1. **Syntax Verification**:
   ```bash
   php -l lib/McpServer.class.php
   php -l lib/McpCrudToolsBase.class.php
   php -l lib/McpNewsTools.class.php
   php -l lib/McpFaqTools.class.php
   php -l lib/McpHeadlineTools.class.php
   php -l lib/McpPublicationTools.class.php
   php -l lib/McpPageTools.class.php
   php -l lib/McpAnnouncementTools.class.php
   php -l lib/McpAttachmentTools.class.php
   php -l lib/McpDocumentTools.class.php
   php -l lib/McpMenuTools.class.php
   php -l lib/McpPopupTools.class.php
   php -l lib/McpRedirectTools.class.php
   php -l lib/McpSettingsTools.class.php
   php -l lib/McpSliderTools.class.php
   php -l lib/McpSurveyTools.class.php
   php -l mod/mod_news/index.php
   php -l mod/mod_news/model/mod_news.model.php
   php -l mod/mod_news/model/newses.model.php
   php -l tasks/mcp_smoke.php
   ```

2. **Template Separation Guard**:
   ```bash
   python3 tools/code_separation_guard.py
   ```

3. **Smoke Tests (inside app container or test DB)**:
   ```bash
   docker exec bulletin_app php tasks/mcp_smoke.php
   ```
   Assert:
   - `Environment: site_status in ['open', 'closed']` (NOT `'unknown'`).
   - `list_faqs(mod_name: 'mod_news', vid: <id>, lang: 'tr')` returns only records matching all 3 conditions.
   - `get_news` returns `lang_status: 'y'` for active Turkish records.
   - `create_headline` succeeds without `TypeError`.
   - `batch_create_faqs` and `list_faq_modules` pass.

4. **Remote MCP Health Check**:
   ```bash
   python3 tools/mcp_health_check.py
   ```
   Assert Stage 7: `/.well-known/oauth-authorization-server` and `/.well-known/oauth-protected-resource` return `HTTP 200` with valid JSON metadata (no 403 errors).
