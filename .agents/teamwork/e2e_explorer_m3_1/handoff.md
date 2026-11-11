# Handoff Report: Tier 2 Boundary & Corner Cases Test Blueprints (Features 1-12)

**Milestone**: `E2E-M3`  
**Agent**: Explorer 1 (`e2e_explorer_m3_1`)  
**Parent**: Orchestrator (`edcb65c1-513d-4652-b8c2-8bf42bd47eb3`)  
**Scope**: Features 1 through 12 in `PROJECT.md § Feature Inventory`  
**Date**: 2026-11-11  

---

## 1. Observation

Direct code examination and live environment probes conducted within `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`:

1. **Feature 1 (`McpCrudToolsBase::addEqFilter` & `addLikeSearchFilter`)**:
   - Located in `lib/McpCrudToolsBase.class.php:365-409`.
   - `addEqFilter()` checks `/^[a-zA-Z_][a-zA-Z0-9_]*$/`. If numeric, uses `{$column} = {$value}`; if string, escapes via `Obuilder::cc($value)` producing doubled single quotes `''` (`Obuilder::cc("val' OR '1'='1")` -> `val'' OR ''1''=''1`). Calls `$model->addWhere($clause)`.
   - `addLikeSearchFilter()` checks `trim($rawSearch) === '' || empty($columns)`. If empty/whitespace, immediately returns without modifying `$model->_where`. Otherwise, escapes via `Obuilder::cc()` and wraps conditions with `($col1 LIKE '%...%' OR $col2 LIKE '%...%')` before calling `$model->addWhere()`.

2. **Feature 2 (`McpServer::resolveEnvironment` DB fetch)**:
   - Located in `lib/McpServer.class.php:90-162`.
   - Checks `getenv('AF_MCP_ENVIRONMENT')` first. If string and non-empty, uses it directly.
   - Next checks `$site['mcp_environment']` (ignoring `'auto'`).
   - Next strips port via `preg_replace('/:\d+$/', '', ...)` from `$_SERVER['HTTP_HOST']`. If empty, parses `$site['address']`.
   - Database fetch uses `Database::fetch_array("SELECT site_status FROM mod_settings LIMIT 1")` followed by `toArray()`.
   - Wrapped in `try { ... } catch (\Throwable)` returning `site_status => 'unknown'` on empty rows, missing column, null status, or database exceptions. Normalizes `'closed'|'offline'` -> `'closed'`, `'open'|'online'` -> `'open'`.

3. **Feature 3 (`McpCrudToolsBase::getRecordAuditMeta` DB fetch & draft FAQs)**:
   - Located in `lib/McpCrudToolsBase.class.php:843-899`.
   - Queries `status FROM lang WHERE seo = '{$safeLang}' LIMIT 1`. If found, sets `lang_status = (string)$langRows[0]['status']`.
   - Formulates `$faqStatusSql`: if `$recordStatus === 'n'`, uses `status IN ('y', 'n')`; otherwise strictly `status = 'y'`.
   - Iterates results to populate `faq_count`, `faq_items`, `faq_published_count`, and `faq_draft_count`.
   - Wrapped in `try { ... } catch (\Throwable)` returning safe defaults (`lang_status => 'n'`, zero counts, empty items) if query fails or table does not exist.

4. **Feature 4 (`McpHeadlineTools` string argument fix)**:
   - Located in `lib/McpHeadlineTools.class.php:278-285` and `340-341`.
   - `createHeadline()`: enforces `mb_strlen($name) >= 2 && mb_strlen($name) <= 255`. Calls `$this->requireActiveLang($args)`. Calls `$this->assertContentMatchesLang($lang, $name)` passing string `$name`.
   - `updateHeadline()`: loads headline, updates `$name` if provided (2-255 chars), updates `$lang` if provided, and invokes `$this->assertContentMatchesLang($lang, (string) $model->name)`.

5. **Feature 5 (`McpNewsTools` leaf category check)**:
   - Located in `lib/McpNewsTools.class.php:794-817`.
   - `assertSelectableNewsCategory(int $categoryId, string $lang)` calls `assertRecordMatchesLang('mod_news_category', $categoryId, $lang, 'Category')`.
   - If `((string) ($category->root ?? 'n')) === 'y'`, queries children where `parent_id = $categoryId AND lang = $lang AND root != 'y'`.
   - Throws `McpToolException("Category '{$categoryId}' ('{$category->name}') is a structural root container (root='y') and cannot contain news directly.{$hintStr}")`.

6. **Feature 6 (`McpNewsTools` mandatory `makeSeo()`)**:
   - Located in `lib/McpCrudToolsBase.class.php:744-785`.
   - Normalizes Unicode to NFC Form C. Strips combining dot above `\u{0307}`.
   - Strips quotation marks, punctuation, slashes, brackets. In Kurdish locales (`ku`, `ckb`, `zza`), maps Turkish typos `ı -> i` and `İ -> I` while preserving Kurdish characters (`ç, ş, î, û, ê`).
   - Converts whitespace and dashes to underscores `_`, collapses repeated underscores, trims underscores, and truncates cleanly at `255` characters via `mb_substr`.
   - Invoked unconditionally on `$name` / `$seo` in `createNews`, `updateNews`, and `checkDuplicateNews`.

7. **Feature 7 (`McpNewsTools::getNews` enrichment)**:
   - Located in `lib/McpNewsTools.class.php:740-788`.
   - Enriches envelope with `username` and `author_fullname` from `mod_user` (falling back to username if full name empty, or empty string if user not found).
   - Enriches `category_name` and `category_seo` from `mod_news_category` (falling back to empty strings if category not found).
   - Returns both top-level flat fields and nested envelope under `$row['item']`.

8. **Feature 8 (`McpNewsTools::checkDuplicateNews`)**:
   - Located in `lib/McpNewsTools.class.php:1214-1284`.
   - Checks `created_at >= $cutoff` where `$cutoff = date('Y-m-d H:i:s', time() - ($hours * 3600))`. Clamps `$hours` to `[1, 168]` (default 48) and `$threshold` to `[0.1, 1.0]` (default 0.55).
   - Computes Jaccard word token similarity and exact `seo` slug match. Flags duplicate if `isSeoMatch || isTitleMatch`. Supports `$excludeId` to avoid self-match during edits.

9. **Feature 9 (`McpFaqTools::batchCreateFaqs`)**:
   - Located in `lib/McpFaqTools.class.php:493-627`.
   - Enforces batch limits: items must be non-empty array, `count($rawItems) <= 25`.
   - Phase 1: in-memory validation of each item (question 1-255, answer 1-65535, `assertContentMatchesLang`, case-insensitive duplicate check within batch).
   - Phase 2: validates cumulative ceiling (`count($activeExisting) + count($validItems) <= 25`) and checks against existing DB questions.
   - Phase 3: starts transaction (`$db->start_transaction()`), optional purge of existing drafts (`status='n'`), inserts items, commits or rolls back on any error (`$db->rollback()`).

10. **Feature 10 (`McpFaqTools::listFaqModules`)**:
    - Located in `lib/McpFaqTools.class.php:632-658`.
    - Normalizes `limit` to `[1, 50]` (default 10) and `offset` to `>= 0`.
    - Queries `mod_faq_mod` with optional LIKE filter across `['name', 'mod_name', 'mod_key', 'mod_value']`. Returns `items`, `total`, `limit`, `offset`.

11. **Feature 11 (`McpFaqTools::listFaqs` `include_answer`)**:
    - Located in `lib/McpFaqTools.class.php:312-340`.
    - Base select columns: `id, lang, mod_name, vid, question, sort_order, status, created_at, updated_at`.
    - If `!empty($args['include_answer'])`, appends `, answer` to select query. Returns items without answer by default to minimize payload.

12. **Feature 12 (Nginx `.well-known` passthrough)**:
    - Located in `docker/nginx/prod.conf:83-93`.
    - `location ^~ /.well-known/ { try_files $uri /index.php?$query_string; }` precedes `location ~ /\. { deny all; access_log off; log_not_found off; }`.
    - Probed live: `curl http://localhost:8080/.well-known/` -> HTTP 404 (routed through index.php), `curl http://localhost:8080/.env` -> HTTP 403 Forbidden, `curl http://localhost:8080/.well-known/../.env` -> HTTP 403 Forbidden.

---

## 2. Logic Chain

1. **Test Coverage Hierarchy**:
   - Tier 1 established baseline happy-path coverage (5 tests per feature).
   - Tier 2 requires exhaustive boundary testing: edge inputs, zero/negative quantities, overflow conditions, Unicode stress, SQL injection resistance, transaction rollbacks, and error resiliency.

2. **Blueprint Construction Strategy**:
   - Each feature is allocated a dedicated test module under `tests/e2e/tier2/test_f01_*.py` through `test_f12_*.py`.
   - Each module implements ≥5 concrete, isolated test cases (target ≥60, achieved 76).
   - Tests execute via standard opaque-box execution: CLI subprocess (`docker exec -i bulletin_app php`), HTTP requests, or static AST/regex parsers.
   - Every test case specifies exact inputs, execution code, expected exit codes, JSON keys, and exception assertions.

---

## 3. Comprehensive Feature-by-Feature Test Blueprints (76 Test Cases)

### Feature 1: `McpCrudToolsBase::addEqFilter` & `addLikeSearchFilter`
**File**: `tests/e2e/tier2/test_f01_orm_conjunctive_filters.py`  
**Class**: `@tier(2) @feature(1) class TestF01OrmConjunctiveFiltersTier2(E2ETestCase)`

| Test ID | Method Name | Boundary Condition | Input Fixture / Action | Expected Result & Assertion |
|---|---|---|---|---|
| F01-01 | `test_f01_boundary_empty_string_value_in_add_eq_filter` | Empty string value | `$h->testEq($model, 'status', ''); $h->testEq($model, 'lang', 'tr');` | `$model->_where == "status = '' AND lang = 'tr'"`. Empty string is preserved inside quotes. |
| F01-02 | `test_f01_boundary_sql_injection_in_value_properly_escaped` | SQL injection in value | `$h->testEq($model, 'title', "test' OR 1=1 --");` | Single quotes doubled by `Obuilder::cc()` -> `title = 'test'' OR 1=1 --'`. No unescaped breakout. |
| F01-03 | `test_f01_boundary_zero_and_negative_numeric_values` | Zero & negative numbers | `$h->testEq($model, 'cat_id', 0); $h->testEq($model, 'parent_id', -1); $h->testEq($model, 'score', 0.0);` | Generates `cat_id = 0 AND parent_id = -1 AND score = 0`. Numeric values unquoted and zero not dropped. |
| F01-04 | `test_f01_boundary_invalid_column_names_validation` | Malicious column names | Test columns: `'status; DROP TABLE'`, `'col name'`, `'table.col'`, `'1column'`, `''`. | Throws `McpToolException` matching `"Invalid filter column name"` or `"Invalid search column name"`. |
| F01-05 | `test_f01_boundary_like_filter_empty_and_whitespace_no_op` | Empty/whitespace search query | Calls `testLike($model, ['name'], '')`, `testLike($model, ['name'], '   ')`, `testLike($model, [], 'test')`. | `$model->_where` remains empty string `""` (strict no-op, no dangling AND). |
| F01-06 | `test_f01_boundary_like_filter_extreme_unicode_and_quotes` | Unicode & quotes in search | `testLike($model, ['name', 'summary'], "Diyarbakır'da Kürtçe & Türkçe %100")`. | Generates `(name LIKE '%Diyarbakır''da Kürtçe & Türkçe %100%' OR summary LIKE '%Diyarbakır''da Kürtçe & Türkçe %100%')`. |
| F01-07 | `test_f01_boundary_complex_multi_condition_conjunction_chain` | 5+ mixed conditions | Interleaves 4 `addEqFilter` calls (numeric, string, 0) and 1 `addLikeSearchFilter` (2 columns). | Strict `AND` chaining preserving parenthesized OR: `status = 'y' AND cat_id = 0 AND lang = 'ku' AND deleted = 0 AND (name LIKE '%Amed%' OR summary LIKE '%Amed%')`. |

---

### Feature 2: `McpServer::resolveEnvironment` DB fetch
**File**: `tests/e2e/tier2/test_f02_mcpserver_db_fetch.py`  
**Class**: `@tier(2) @feature(2) class TestF02McpServerDbFetchTier2(E2ETestCase)`

| Test ID | Method Name | Boundary Condition | Input Fixture / Action | Expected Result & Assertion |
|---|---|---|---|---|
| F02-01 | `test_f02_boundary_empty_db_result_defaults_to_unknown_site_status` | Zero rows returned from DB | Mock DB returns `$rows = []` for `mod_settings` query. | Returns `site_status => 'unknown'`, exit code 0, no PHP warning or undefined index notice. |
| F02-02 | `test_f02_boundary_null_site_status_value_handled` | Null `site_status` column | DB returns `[['site_status' => null]]`. | `isset($row['site_status'])` is false -> returns `site_status => 'unknown'`. |
| F02-03 | `test_f02_boundary_db_exception_resilience` | DB throws `\Throwable` | DB `fetch_array()` throws `new \Exception("Connection failed")`. | Caught by `catch (\Throwable)` -> returns safe fallback `site_status => 'unknown'`. |
| F02-04 | `test_f02_boundary_env_var_highest_precedence` | Custom `AF_MCP_ENVIRONMENT` | Set `putenv('AF_MCP_ENVIRONMENT=custom_staging_node')`. Call `resolveEnvironment()`. | Returns `'name' => 'custom_staging_node'`, overriding `site_config` and `HTTP_HOST`. |
| F02-05 | `test_f02_boundary_whitespace_env_var_falls_back` | Whitespace `AF_MCP_ENVIRONMENT` | Set `putenv('AF_MCP_ENVIRONMENT=   ')`. | Whitespace ignored, falls back to `site_config` or host resolution (returns `'development'`). |
| F02-06 | `test_f02_boundary_host_port_stripping_and_ipv6` | Port suffix & IPv6 | `$_SERVER['HTTP_HOST'] = 'localhost:8080'`, `$_SERVER['HTTP_HOST'] = '[::1]:443'`. | Port stripped: resolves cleanly to `endpoint_host => 'localhost'`, `name => 'development'`. |
| F02-07 | `test_f02_boundary_case_insensitive_status_and_custom_values` | Case variants & custom statuses | DB returns `'CLOSED'`, `'Online'`, `'maintenance'`. | `'CLOSED'` -> `'closed'`, `'Online'` -> `'open'`, `'maintenance'` -> `'maintenance'`. Lowercase normalized. |

---

### Feature 3: `McpCrudToolsBase::getRecordAuditMeta` DB fetch & draft FAQs
**File**: `tests/e2e/tier2/test_f03_audit_meta_draft_faqs.py`  
**Class**: `@tier(2) @feature(3) class TestF03AuditMetaDraftFaqsTier2(E2ETestCase)`

| Test ID | Method Name | Boundary Condition | Input Fixture / Action | Expected Result & Assertion |
|---|---|---|---|---|
| F03-01 | `test_f03_boundary_zero_faqs_for_valid_entity` | Record with 0 FAQs | Query audit meta for entity `vid = 999991` with zero entries in `mod_faq`. | `faq_count = 0`, `faq_items = []`, `faq_published_count = 0`, `faq_draft_count = 0`, `lang_status = 'y'`. |
| F03-02 | `test_f03_boundary_record_status_other_than_n_defaults_to_published_only` | Unexpected `recordStatus` values | Entity with 1 published (`y`) and 1 draft (`n`) FAQ. Query with `recordStatus = 'draft'`, `''`, `'unknown'`. | Only `recordStatus === 'n'` queries drafts. All other values default to `status = 'y'`, returning `faq_draft_count = 0`. |
| F03-03 | `test_f03_boundary_sql_injection_in_mod_name_and_lang` | Injection strings in arguments | Call with `$modName = "mod_news' OR 1=1 --"`, `$lang = "tr' UNION SELECT 1 --"`. | Escaped via `Obuilder::cc()`; zero SQL syntax error; returns default safe envelope. |
| F03-04 | `test_f03_boundary_non_existent_or_negative_record_id` | `recordId` = 0, -1, 2147483647 | Query with `recordId = 0` and `recordId = -1`. | Coerced to `(int)$recordId`; returns clean envelope with `faq_count = 0`, no SQL crash. |
| F03-05 | `test_f03_boundary_non_existent_lang_returns_lang_status_n` | Inactive/unregistered `lang` | Query with `lang = 'non_existent_locale'`. | `lang_status` is `'n'`, `faq_count = 0`. |
| F03-06 | `test_f03_boundary_database_exception_resilience` | DB throws exception | Simulate DB exception during FAQ fetch. | Caught by `catch (\Throwable $e)` non-blocking block; returns standard safe defaults. |

---

### Feature 4: `McpHeadlineTools` string argument fix
**File**: `tests/e2e/tier2/test_f04_headline_type_assertion.py`  
**Class**: `@tier(2) @feature(4) class TestF04HeadlineTypeAssertionTier2(E2ETestCase)`

| Test ID | Method Name | Boundary Condition | Input Fixture / Action | Expected Result & Assertion |
|---|---|---|---|---|
| F04-01 | `test_f04_boundary_name_empty_or_whitespace_only` | Empty/whitespace `name` | Call `createHeadline(['name' => '   ', 'lang' => 'tr'])`. | Throws `McpToolException("Field 'name' is required (2-255 characters).")`. |
| F04-02 | `test_f04_boundary_name_length_lower_boundary_1_and_2_chars` | Length 1 vs 2 chars | Test length 1 (`"A"`) vs length 2 (`"AB"`). | 1 char rejected with length error; 2 chars succeeds without TypeError. |
| F04-03 | `test_f04_boundary_name_length_upper_boundary_255_and_256_chars` | Length 255 vs 256 chars | Test length 255 (valid UTF-8 string) vs length 256. | 255 chars succeeds; 256 chars rejected with `Field 'name' is required (2-255 characters).` |
| F04-04 | `test_f04_boundary_numeric_and_null_name_type_coercion` | Numeric/null `name` parameter | Call `createHeadline(['name' => 12345, 'lang' => 'tr'])`. | Explicit string cast `(string)` converts to `"12345"`; passes to `assertContentMatchesLang` as string without PHP 8 TypeError. |
| F04-05 | `test_f04_boundary_missing_or_inactive_language` | Missing / inactive language | Call with omitted `lang` or `lang => 'de'`. | Missing throws `Field 'lang' is required.`; inactive throws `Language 'de' is not an active site language.` |
| F04-06 | `test_f04_boundary_update_headline_language_mismatch_cross_detection` | Language mismatch on update | Headline in `ku`, updated with Turkish title containing Turkish stopwords under `lang='ku'`. | Throws `McpToolException` ("Content language mismatch: target record language is 'ku', but the supplied text appears to be Turkish ('tr')."). |

---

### Feature 5: `McpNewsTools` leaf category check
**File**: `tests/e2e/tier2/test_f05_leaf_category_check.py`  
**Class**: `@tier(2) @feature(5) class TestF05LeafCategoryCheckTier2(E2ETestCase)`

| Test ID | Method Name | Boundary Condition | Input Fixture / Action | Expected Result & Assertion |
|---|---|---|---|---|
| F05-01 | `test_f05_boundary_category_id_zero_and_negative_rejection` | `category_id <= 0` | Call `createNews` with `category_id => 0` and `category_id => -5`. | Throws `McpToolException("Field 'category_id' is required and must be a positive integer.")`. |
| F05-02 | `test_f05_boundary_non_existent_category_id` | Non-existent category ID | Call `createNews` with `category_id => 9999999`. | Throws `McpToolException("Category record '9999999' not found.")`. |
| F05-03 | `test_f05_boundary_category_language_mismatch` | Cross-language category | Category belongs to Kurdish (`ku`), news created in Turkish (`tr`). | Throws `McpToolException("Category 'X' belongs to language 'ku', content language is 'tr'. They must match.")`. |
| F05-04 | `test_f05_boundary_root_category_with_no_children_hint` | Root category without children | Query root category with 0 child categories. | Throws message ending with: `"Please select a leaf category (root != 'y')."` |
| F05-05 | `test_f05_boundary_root_category_with_multiple_children_hints` | Root category with children | Category 17 (root='y') with children. | Throws message listing children: `"Selectable child categories: ID 18 (Haberler)..."` |
| F05-06 | `test_f05_boundary_update_news_rejects_switching_to_root_category` | Update category to root | Call `updateNews(['id' => $validId, 'category_id' => 17])`. | Throws `McpToolException` rejecting structural root container; DB record unchanged. |

---

### Feature 6: `McpNewsTools` mandatory `makeSeo()`
**File**: `tests/e2e/tier2/test_f06_mandatory_make_seo.py`  
**Class**: `@tier(2) @feature(6) class TestF06MandatoryMakeSeoTier2(E2ETestCase)`

| Test ID | Method Name | Boundary Condition | Input Fixture / Action | Expected Result & Assertion |
|---|---|---|---|---|
| F06-01 | `test_f06_boundary_empty_string_and_whitespace_returns_empty` | Empty/whitespace string | `makeSeo("")`, `makeSeo("   ")`, `makeSeo("\t\n")`. | Returns empty string `""`. |
| F06-02 | `test_f06_boundary_all_punctuation_and_symbols_returns_empty` | Only punctuation/symbols | `makeSeo("::: /// ??? !!! '' \"\" [[ ]]")`. | Returns empty string `""` after symbol stripping and underscore trimming. |
| F06-03 | `test_f06_boundary_extreme_unicode_decomposed_u0307_combining_dots` | Decomposed combining dot above | `makeSeo("Dı\u0307yarbakı\u0307r", "tr")`. | Strips `\u0307`, composes into clean precomposed NFC `"Diyarbakır"`. No trailing `\u0307`. |
| F06-04 | `test_f06_boundary_consecutive_mixed_slashes_and_dashes_collapsed` | Mixed repeated separators | `makeSeo("A///---___\\\\---B", "tr")`. | Collapses into single underscore `"A_B"`. |
| F06-05 | `test_f06_boundary_max_length_255_multibyte_truncation` | 300+ UTF-8 characters | Long title with 300 multibyte characters. | `mb_strlen($slug) <= 255`, valid UTF-8, no broken byte sequences at boundary. |
| F06-06 | `test_f06_boundary_kurdish_typo_normalizations_in_ku_locale` | Kurdish locale typo mapping | `makeSeo("Pırsên Înternetê", "ku")`. | Maps `ı -> i` and `İ -> I` -> `"Pirsen_Internete"`. Preserves Kurdish characters `ê, î`. |
| F06-07 | `test_f06_boundary_path_traversal_and_directory_injection` | Traversal sequences | `makeSeo("../../../../etc/shadow")`. | Slashes and dots stripped/converted -> `"etc_shadow"`. Zero traversal hazard. |

---

### Feature 7: `McpNewsTools::getNews` enrichment
**File**: `tests/e2e/tier2/test_f07_get_news_enrichment.py`  
**Class**: `@tier(2) @feature(7) class TestF07GetNewsEnrichmentTier2(E2ETestCase)`

| Test ID | Method Name | Boundary Condition | Input Fixture / Action | Expected Result & Assertion |
|---|---|---|---|---|
| F07-01 | `test_f07_boundary_missing_or_deleted_user_record` | `user_id` non-existent | News with `user_id = 9999999`. Call `getNews`. | Returns `username => ""` and `author_fullname => ""`. Does not throw exception. |
| F07-02 | `test_f07_boundary_user_with_empty_name_falls_back_to_username` | Empty `name` & `last_name` in `mod_user` | User has `name=''`, `last_name=''`, `username='amed_editor'`. | `author_fullname` falls back to `'amed_editor'`. |
| F07-03 | `test_f07_boundary_missing_or_deleted_category_record` | `category_id` non-existent | News with `category_id = 9999999`. Call `getNews`. | Returns `category_name => ""` and `category_seo => ""`. Does not throw exception. |
| F07-04 | `test_f07_boundary_non_existent_news_id_0_and_negative_rejection` | `id <= 0` or missing | Call with `id => 0`, `id => -1`, `id => 99999999`. | `id <= 0` throws required positive id exception; `99999999` throws `"News record '99999999' not found."` |
| F07-05 | `test_f07_boundary_seo_lookup_with_non_matching_lang` | Valid slug with mismatched `lang` | Query news by existing `seo` slug, but with mismatched `lang='ku'`. | Throws `"News record with seo '...' (lang 'ku') not found."` |
| F07-06 | `test_f07_boundary_dual_envelope_structure_integrity` | Envelope parity | Call `getNews(['id' => 1])`. | Strict equality between top-level flat fields and `$res['item']` envelope for all enriched fields. |

---

### Feature 8: `McpNewsTools::checkDuplicateNews`
**File**: `tests/e2e/tier2/test_f08_check_duplicate_news.py`  
**Class**: `@tier(2) @feature(8) class TestF08CheckDuplicateNewsTier2(E2ETestCase)`

| Test ID | Method Name | Boundary Condition | Input Fixture / Action | Expected Result & Assertion |
|---|---|---|---|---|
| F08-01 | `test_f08_boundary_exact_48h_boundary_47h59m_vs_48h01m` | Exact cutoff boundary | Record 1 at 47h 59m ago; Record 2 at 48h 01m ago. | Record 1 detected as duplicate (`has_duplicate=true`); Record 2 NOT detected (`has_duplicate=false`). |
| F08-02 | `test_f08_boundary_whitespace_and_punctuation_token_jaccard_similarity` | Whitespace/punctuation variance | Existing: `"Diyarbakır Surları Onarımı"`; Proposed: `"  DİYARBAKIR   Surları   ONARIMI!!!  "`. | Tokenizer strips symbols, lowercase normalizes; similarity is `1.0`, flagged as duplicate. |
| F08-03 | `test_f08_boundary_exact_seo_slug_match_with_different_title_tokens` | Exact SEO collision with different title | Title has Jaccard similarity < 0.55, but explicit `seo` parameter matches existing record's `seo`. | Detected with `is_seo_match = true`, `match_reasons = ['exact_seo_match']`. |
| F08-04 | `test_f08_boundary_hours_clamping_limits` | Extreme `hours` parameter | Pass `hours => 0` and `hours => 500`. | Clamped to `hours_window = 1` and `hours_window = 168` respectively. |
| F08-05 | `test_f08_boundary_threshold_clamping_limits` | Extreme `threshold` parameter | Pass `threshold => 0.0` and `threshold => 2.0`. | Clamped to `threshold = 0.1` and `threshold = 1.0` respectively. |
| F08-06 | `test_f08_boundary_cross_language_isolation` | Identical headline across languages | Seed news in `tr`. Query `checkDuplicateNews` with same title under `lang='ku'`. | Scoped by language query; returns `has_duplicate = false`. |

---

### Feature 9: `McpFaqTools::batchCreateFaqs`
**File**: `tests/e2e/tier2/test_f09_batch_create_faqs.py`  
**Class**: `@tier(2) @feature(9) class TestF09BatchCreateFaqsTier2(E2ETestCase)`

| Test ID | Method Name | Boundary Condition | Input Fixture / Action | Expected Result & Assertion |
|---|---|---|---|---|
| F09-01 | `test_f09_boundary_empty_batch_rejection` | Empty array or non-array `items` | Call `batchCreateFaqs` with `items => []` or `items => null`. | Throws `McpToolException("Field 'items' must be a non-empty array of FAQ objects.")`. |
| F09-02 | `test_f09_boundary_exact_25_items_accepted_and_26_items_overflow_rejected` | 25 vs 26 items | Batch of 25 items vs batch of 26 items. | 25 items created successfully (`created=true, count=25`); 26 items throws `"Field 'items' exceeds maximum batch size of 25 items."` |
| F09-03 | `test_f09_boundary_cumulative_ceiling_overflow_against_existing_faqs` | Existing + batch > 25 | 20 existing FAQs in DB. Attempt batch of 6 items (total 26 > 25). | Throws `"Total FAQs would exceed ceiling of 25 (current: 20, batch: 6)."`. |
| F09-04 | `test_f09_boundary_duplicate_question_case_insensitive_within_batch` | Duplicate question in batch | Item 0: `"Proje nedir?"`; Item 1: `"  proje NEDİR?  "`. | Throws `McpToolException("Duplicate question within batch: 'proje NEDİR?'.")`. |
| F09-05 | `test_f09_boundary_question_and_answer_length_boundaries` | Question 256 chars, empty answer | Item with 256 chars question; item with answer `""`. | Throws validation error for question (1-255 characters) and answer (1-65535 characters). |
| F09-06 | `test_f09_boundary_atomic_transaction_rollback_preserves_db_state` | Rollback on invalid item | Batch of 5 items; item 5 fails validation. | Transaction rolls back; DB has 0 items persisted; previous DB count unchanged. |
| F09-07 | `test_f09_boundary_replace_existing_drafts_purges_only_drafts` | `replace_existing_drafts` flag | Entity has 2 published (`y`) and 2 draft (`n`) FAQs. Batch with `replace_existing_drafts = true`. | Published FAQs remain untouched; old drafts purged; new drafts inserted. |

---

### Feature 10: `McpFaqTools::listFaqModules`
**File**: `tests/e2e/tier2/test_f10_list_faq_modules.py`  
**Class**: `@tier(2) @feature(10) class TestF10ListFaqModulesTier2(E2ETestCase)`

| Test ID | Method Name | Boundary Condition | Input Fixture / Action | Expected Result & Assertion |
|---|---|---|---|---|
| F10-01 | `test_f10_boundary_limit_clamping_zero_negative_and_overflow` | Limit <= 0 or > 50 | Pass `limit => 0`, `limit => -5`, `limit => 100`. | Clamped to `limit = 1` and `limit = 50`. Returned items size matches clamped limit. |
| F10-02 | `test_f10_boundary_offset_clamping_negative_and_excessive` | Offset < 0 or large | Pass `offset => -10` and `offset => 99999`. | Negative offset clamped to 0; large offset returns `items => []`, `total` accurate. |
| F10-03 | `test_f10_boundary_search_sql_injection_and_special_chars` | SQL injection in search | Search string `"mod_%' OR '1'='1"`, `"'; DROP TABLE..."`. | Escaped via `Obuilder::cc()`; zero SQL syntax error; returns 0 matches safely. |
| F10-04 | `test_f10_boundary_empty_and_whitespace_search_no_op` | Empty/whitespace search | Search `""` or `"   "`. | Search filter not applied; returns all registered modules. |
| F10-05 | `test_f10_boundary_search_unicode_and_exact_module_match` | Unicode search query | Search `"Haber"` or `"Yayın"`. | Matches target registered module (`mod_news` / `mod_publication`). |

---

### Feature 11: `McpFaqTools::listFaqs` `include_answer`
**File**: `tests/e2e/tier2/test_f11_list_faqs_include_answer.py`  
**Class**: `@tier(2) @feature(11) class TestF11ListFaqsIncludeAnswerTier2(E2ETestCase)`

| Test ID | Method Name | Boundary Condition | Input Fixture / Action | Expected Result & Assertion |
|---|---|---|---|---|
| F11-01 | `test_f11_boundary_truthy_values_for_include_answer` | Truthy values | Pass `include_answer` as `true`, `1`, `"1"`, `"true"`. | `answer` key is present in all returned items. |
| F11-02 | `test_f11_boundary_falsy_values_for_include_answer` | Falsy values | Pass `include_answer` as `false`, `0`, `"0"`, `""`, `null`. | `answer` key is strictly omitted from returned items. |
| F11-03 | `test_f11_boundary_massive_html_answer_preservation` | Massive 20KB HTML answer | Seed FAQ with 20KB HTML answer. Query with `include_answer=false` vs `true`. | Payload when false is small; when true, 20KB HTML is returned complete without truncation. |
| F11-04 | `test_f11_boundary_unicode_special_characters_in_answer` | Kurdish characters & emojis in answer | Seed FAQ with answer containing `ê, î, û, 💡, ❓`. | Retrieved answer matches original unicode string without corruption. |
| F11-05 | `test_f11_boundary_pagination_and_sorting_with_include_answer` | Pagination + include_answer | `include_answer=true`, `limit=2`, `offset=1`. | Items sorted by `sort_order ASC, id ASC`; answers included for paginated slice. |
| F11-06 | `test_f11_boundary_zero_matching_results_with_include_answer` | Zero matching records | Query non-matching filter (`vid = 9999999`) with `include_answer=true`. | Returns `items => []`, `total => 0`, clean envelope without warnings. |

---

### Feature 12: Nginx `.well-known` passthrough
**File**: `tests/e2e/tier2/test_f12_nginx_well_known.py`  
**Class**: `@tier(2) @feature(12) class TestF12NginxWellKnownTier2(E2ETestCase)`

| Test ID | Method Name | Boundary Condition | Input Fixture / Action | Expected Result & Assertion |
|---|---|---|---|---|
| F12-01 | `test_f12_boundary_well_known_without_trailing_slash` | Missing trailing slash | HTTP GET `http://localhost:8080/.well-known`. | Returns 301/302 redirect or routes to index.php (200/404); NOT 403 Forbidden. |
| F12-02 | `test_f12_boundary_acme_challenge_subpath_routing` | Deep ACME challenge path | HTTP GET `http://localhost:8080/.well-known/acme-challenge/arbitrary-token-123`. | Routes via `try_files` to index.php (status 404/200); NOT 403 Forbidden. |
| F12-03 | `test_f12_boundary_directory_traversal_via_well_known_blocked` | Traversal via `.well-known/..` | HTTP GET `http://localhost:8080/.well-known/../.env`. | Nginx normalizes URI to `/.env`; matches `location ~ /\. ` -> returns 403 Forbidden! |
| F12-04 | `test_f12_boundary_standard_rfc_discovery_endpoints` | RFC 8414 & RFC 9728 endpoints | Query `/.well-known/oauth-authorization-server`, `/.well-known/openid-configuration`, `/.well-known/security.txt`. | All pass through without 403 Forbidden. Routed to PHP router. |
| F12-05 | `test_f12_boundary_non_existent_resource_returns_404_not_403` | Non-existent file | HTTP GET `http://localhost:8080/.well-known/nonexistent_token_999`. | Returns HTTP 404 (handled by router); strictly NOT 403 Forbidden. |
| F12-06 | `test_f12_boundary_sensitive_dotfiles_outside_well_known_strictly_forbidden` | Sensitive dotfiles | HTTP GET `/.env`, `/.git/config`, `/.htaccess`, `/.user.ini`. | All return HTTP 403 Forbidden. |
| F12-07 | `test_f12_boundary_static_nginx_conf_directives_inspection` | Static configuration parser | Audit `docker/nginx/prod.conf`. | `location ^~ /.well-known/` has `^~` modifier, includes `/index.php?$query_string`, precedes `location ~ /\.`. |

---

## 4. Caveats

1. **Docker Environment Dependency**:
   - The test executions for PHP MCP components run inside the `bulletin_app` container via `docker exec -i bulletin_app php`. The Docker container must be in the `running` state.
   - Live HTTP tests for Feature 12 rely on Nginx serving at `http://localhost:8080`. If port 8080 is temporarily unreachable, tests gracefully fall back to static configuration AST verification.
2. **Database Cleanliness & Sandbox Isolation**:
   - Tests that insert temporary seed records (e.g. `mod_news`, `mod_faq`) clean up after themselves in `finally` blocks or immediate post-assertion deletes to ensure zero database pollution.
3. **Strict Read-Only Investigation**:
   - Per explorer rules, no source files or test scripts under `tests/e2e/tier2/` were created or modified during this investigation. Implementation is reserved for the implementation track/agent.

---

## 5. Conclusion

- **Scope Coverage**: All 12 assigned features thoroughly analyzed across error cases, boundary limits, negative quantities, Unicode diacritics, SQL injection sanitization, and concurrency/transaction rollbacks.
- **Test Quantity**: 76 distinct, high-fidelity test blueprints designed across 12 test files (`test_f01_*.py` through `test_f12_*.py`), exceeding the target of ≥60 test cases (average >6 tests/feature).
- **Readiness**: The blueprints are fully actionable and ready for immediate implementation by the implementation subagents.

---

## 6. Verification Method

To verify these blueprints when implemented:

```bash
# 1. Run all Tier 2 tests for Features 1-12
python3 tests/e2e/run_e2e_tests.py --tier 2 -f 1-12

# 2. Run specific feature boundary test (e.g. Feature 1: conjunctive filters)
python3 tests/e2e/run_e2e_tests.py --tier 2 -f 1

# 3. Verify exit code is 0 and all tests pass
echo "Exit code: $?"
```
