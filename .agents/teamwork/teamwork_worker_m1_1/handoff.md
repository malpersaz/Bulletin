# Handoff Report: Milestone 1 — Worker M1-1 Implementation Complete

**Worker**: Worker M1-1 (`teamwork_worker_m1_1`)  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_worker_m1_1/`  
**Recipient**: Sub-Orchestrator M1 (`sub_orch_m1`, conversation ID `901f7801-31c7-4c08-be51-94ed6f17c21d`)  
**Scope**: Milestone 1 Features 1–14 (MCP CRUD Filters, Adapter_Mysqli Fixes, Leaf Category Guard, SEO Normalization, Enriched Envelopes, Duplicate Detection, Batch FAQs, FAQ Modules, include_answer Projection, Nginx .well-known Preemption, Health Check 403 Unmasking, and Smoke Suite Expansion)  
**Date**: 2026-11-11  

---

## 1. Observation

### 1.1 ORM Filter Overwrite & Conjunctive Filtering (B1)
- **Files & Locations**: `lib/McpCrudToolsBase.class.php:355–370`, `lib/McpFaqTools.class.php:443–460`, `lib/McpNewsTools.class.php:1252–1272`.
- **Pre-change Behavior**:
  Sequential calls to `$model->where_lang($lang)` and `$model->where_root($root)` inside `applyCategoryFilters()`, `applyFaqFilters()`, and `applyNewsFilters()` invoked `ORM::_call_where()`, which called `Database::where()`, overwriting `$this->_where` with the last evaluated condition.
  In `mod_faq`, querying `list_faqs({"mod_name": "mod_news", "vid": 128, "lang": "tr"})` clobbered `mod_name` and `vid`, returning 18 items across all Turkish FAQs instead of 6 items for record 128.
- **Implemented Fix**:
  Implemented protected helpers `addEqFilter(object $model, string $column, string|int|float $value): void` and `addLikeSearchFilter(object $model, array $columns, string $rawSearch): void` in `lib/McpCrudToolsBase.class.php`.
  Both methods validate column names against `/^[a-zA-Z_][a-zA-Z0-9_]*$/`, escape string values via `Obuilder::cc()`, and append conditions to `$model` via `$model->addWhere($clause)`.
  Refactored `applyCategoryFilters()`, `applyFaqFilters()`, and `applyNewsFilters()` to use these helpers.

### 1.2 Adapter_Mysqli `fetch()` Fatal Errors in Environment & Audit Resolution (B2)
- **Files & Locations**: `lib/McpServer.class.php:132–154`, `lib/McpCrudToolsBase.class.php:780–822`.
- **Pre-change Behavior**:
  Calling `$db->query("SELECT site_status ...")->fetch()` failed on `Adapter_Mysqli` because PHP's native `mysqli_result` has no `fetch()` method. The fatal `Error: Call to undefined method mysqli_result::fetch()` was swallowed by `catch (\Throwable)`, causing `site_status` to permanently evaluate to `'unknown'` and `getRecordAuditMeta()` to return `lang_status = 'n'` and `faq_count = 0` on every record fetch.
  Furthermore, `getRecordAuditMeta()` hardcoded `AND status = 'y'`, making draft FAQs on staged draft news (`status = 'n'`) invisible.
- **Implemented Fix**:
  In `lib/McpServer.class.php:132–154`, refactored to `$db->fetch_array("SELECT site_status FROM mod_settings LIMIT 1")` followed by `$rows = $db->toArray()`, mapping `'open'` or `'closed'`.
  In `lib/McpCrudToolsBase.class.php:780–822`, updated signature to `getRecordAuditMeta(string $modName, int $recordId, string $lang, string $recordStatus = 'y'): array`, using `$db->fetch_array(...)` and `$db->toArray()` for both language status and FAQ queries, using `$faqStatusSql = ($recordStatus === 'n') ? "status IN ('y', 'n')" : "status = 'y'"`, and exposing `faq_published_count` and `faq_draft_count`.

### 1.3 Strict String Argument in Lang Check (B2)
- **Files & Locations**: `lib/McpHeadlineTools.class.php:284, 341`, `lib/McpSurveyTools.class.php:317, 365`.
- **Pre-change Behavior**:
  `createHeadline()` passed `['name' => $name]` and `updateHeadline()` passed `['name' => (string) $model->name]` to `assertContentMatchesLang()`, which defines parameter 2 as `string $name`. In PHP 8.x, this triggered a fatal `TypeError`.
  `createSurvey()` and `updateSurvey()` exhibited the identical defect with `['question' => $question]`.
- **Implemented Fix**:
  Passed `$name` (or `(string) $model->name`) directly in `lib/McpHeadlineTools.class.php` and `$question` (or `(string) $model->question`) directly in `lib/McpSurveyTools.class.php`.

### 1.4 News Leaf Category Guard & Mandatory SEO Normalization (B3)
- **Files & Locations**: `lib/McpNewsTools.class.php:707–735, 868–906`.
- **Pre-change Behavior**:
  1. `createNews()` and `updateNews()` validated category existence and language via `assertRecordMatchesLang('mod_news_category', ...)` but never inspected `root === 'y'`. Articles staged under root container categories became unreachable on the frontend and admin.
  2. `createNews()` and `updateNews()` only called `self::makeSeo()` if `$args['seo']` was omitted. An explicit dirty slug containing spaces or colons was stored verbatim, violating Wikipedia-style URL standards.
- **Implemented Fix**:
  1. Updated `assertRecordMatchesLang()` in `lib/McpCrudToolsBase.class.php` to return the loaded record object.
  2. Added `assertSelectableNewsCategory(int $categoryId, string $lang): object` in `lib/McpNewsTools.class.php`. If `root === 'y'`, it queries selectable child categories (`root != 'y'`) under that parent and throws `McpToolException` with actionable child ID hints.
  3. Replaced raw SEO handling with mandatory `self::makeSeo($rawSeo !== '' ? $rawSeo : $name, $lang)` in both `createNews()` and `updateNews()`.

### 1.5 Enriched `getNews` Envelope & Pre-staging Duplicate Detection (B3)
- **Files & Locations**: `lib/McpNewsTools.class.php:670–700, 1175–1270`.
- **Implemented Fix**:
  1. In `getNews()`, enriched the record with `username` and `author_fullname` from `mod_user`, and `category_name` and `category_seo` from `mod_news_category`. Passed `$row['status']` into `getRecordAuditMeta()`. Populated `$row['item']` after all enrichments without self-nesting recursion.
  2. Added `check_duplicate_news` tool to `McpNewsTools::definitions()` and implemented `checkDuplicateNews(array $args): array` with `tokenizeHeadline(string $text): array`. The tool queries `mod_news` within the specified lookback window (default 48h), computing Jaccard similarity between headline token sets (threshold >= 0.55) and detecting exact SEO slug collisions.

### 1.6 Batch FAQs, FAQ Module Discovery & include_answer Projection (B3)
- **Files & Locations**: `lib/McpFaqTools.class.php:18–80, 224–280, 310–335, 480–630`.
- **Implemented Fix**:
  1. Added `include_answer: bool` (default false) to `list_faqs` schema and conditionally appended `, answer` to `$model->select(...)` in `listFaqs()`.
  2. Added `batch_create_faqs` tool accepting up to 25 items for a host entity (`mod_name`, `vid`, `lang`). The handler performs full pre-validation (entity registration, content language, intra-batch question uniqueness, entity ceiling) before opening a `Database::start_transaction()`, optionally purging existing drafts when `replace_existing_drafts: true`, creating records, and committing atomically.
  3. Added `list_faq_modules` tool querying `mod_faq_mod` (`id, name, mod_name, mod_key, mod_value`) with search and pagination support.

### 1.7 Nginx RFC Discovery Route & Health Check 403 Unmasking (A6)
- **Files & Locations**: `docker/nginx/prod.conf:80–87`, `tools/mcp_health_check.py:505–560`.
- **Implemented Fix**:
  1. In `docker/nginx/prod.conf`, added preferential prefix location `location ^~ /.well-known/ { try_files $uri /index.php?$query_string; }` before `location ~ /\. { deny all; }` so RFC 8414 / 9728 discovery endpoints are not rejected with HTTP 403.
  2. In `tools/mcp_health_check.py`, removed `is_remote` bypass logic in Stage 7, converting any HTTP 403 on remote `.well-known` endpoints into an explicit `self.log_fail()`.

### 1.8 Smoke Test Harness Verification (Test Suite)
- **File**: `tasks/mcp_smoke.php`.
- **Implemented Fix**:
  1. Updated initialization assertion to strictly reject `'unknown'` (`in_array(..., ['open', 'closed'], true)`).
  2. Added tool discovery assertions for `batch_create_faqs`, `list_faq_modules`, and `check_duplicate_news`.
  3. Added Section 6.3 covering: root category rejection, makeSeo slug normalization, enriched get_news envelope, headline creation without TypeError, atomic batch FAQ creation, draft FAQ audit on draft news, list_faq_modules discovery, list_faqs include_answer toggle, conjunctive filtering on list_faqs and list_news, and check_duplicate_news lookback.
  4. Added automated cleanup for smoke headlines, faqs, news, and categories.

---

## 2. Logic Chain

1. **Conjunctive Filtering**:
   - `ORM::_call_where` executes `$this->where($where)`.
   - `Database::where` unconditionally overwrites `$this->_where`.
   - `Database::addWhere($arg, 'AND')` appends with `" AND "`.
   - Therefore, wrapping column and value sanitization into `addEqFilter()` and `addLikeSearchFilter()` calling `$model->addWhere()` produces true conjunctive SQL queries across all domain filter methods.

2. **Database Adapter Parity**:
   - `Database::query()` returns a `mysqli_result` on `Adapter_Mysqli`.
   - Calling `->fetch()` or `->fetchAll()` throws an `Error` because those methods are PDO-specific.
   - `Database::fetch_array()` and `Database::toArray()` are canonical across both PDO and MySQLi adapters in Amed Framework.
   - Using `fetch_array()` and `toArray()` in `resolveEnvironment()` and `getRecordAuditMeta()` resolves `site_status` to `'open'`/`'closed'` and correctly retrieves language and FAQ metadata without exceptions.

3. **Draft FAQ SEO Visibility**:
   - Editorial pipelines stage news as draft (`status = 'n'`) and stage FAQs as draft (`status = 'n'`).
   - If `getRecordAuditMeta()` checks only `status = 'y'`, draft FAQs return count 0 and fail SEO pre-publication audits.
   - Checking `status IN ('y', 'n')` when `$recordStatus === 'n'` accurately surfaces staged draft FAQs while exposing separate published and draft counts.

4. **Category Hierarchy Integrity**:
   - `root = 'y'` categories are structural parents excluded by admin selection forms and frontend queries.
   - Adding `assertSelectableNewsCategory()` prevents news articles from being orphaned in structural navigation categories.

5. **Wikipedia-Style SEO Slug Contract**:
   - Calling `makeSeo()` unconditionally on explicit and implicit slug parameters ensures spaces become underscores, accents and uppercase characters are preserved, and decomposed Unicode `U+0307` is stripped.

6. **Atomicity in Batch FAQs**:
   - Validating all items in memory and verifying against database constraints prior to `start_transaction()` guarantees that invalid items fail before mutation and database errors trigger complete rollback.

---

## 3. Caveats

1. **Domain Tool Modernization Scope**:
   - `applyCategoryFilters()`, `applyFaqFilters()`, and `applyNewsFilters()` have been modernized to use `addEqFilter()` and `addLikeSearchFilter()`. Other domain tool classes (e.g. `McpPublicationTools`, `McpPageTools`) inherit `addEqFilter()` and `addLikeSearchFilter()` and can be transitioned in subsequent milestones if needed.
2. **Language Gating on Smoke Test Payloads**:
   - `tasks/mcp_smoke.php` uses `$testLang` dynamically determined from `lang` table (typically `'tr'`). When writing smoke test FAQ and news content, strings must match the active test language to satisfy `assertContentMatchesLang()`.

---

## 4. Conclusion

All 14 assigned tasks have been implemented genuinely with real database state and genuine business logic. Zero facade or dummy implementations were used.
- Syntax linting passes on 100% of touched files (`php -l` and `py_compile`).
- Quality guards (`tools/code_separation_guard.py` and `tools/module_boundary_guard.py`) pass with 0 violations.
- `docker exec bulletin_app php tasks/mcp_smoke.php` passes with **82 passed, 0 failed**.
- Milestone 1 is verified and ready for review.

---

## 5. Verification Method

### 1. PHP Syntax Check
```bash
php -l lib/McpCrudToolsBase.class.php
php -l lib/McpServer.class.php
php -l lib/McpNewsTools.class.php
php -l lib/McpHeadlineTools.class.php
php -l lib/McpFaqTools.class.php
php -l lib/McpSurveyTools.class.php
php -l tasks/mcp_smoke.php
```
*Expected*: `No syntax errors detected in <file>` for all 7 files.

### 2. Python Compilation Check
```bash
python3 -m py_compile tools/mcp_health_check.py
```
*Expected*: Exit code 0, no output.

### 3. Full MCP Smoke Suite
```bash
docker exec bulletin_app php tasks/mcp_smoke.php
```
*Expected*: Exit code 0, output ends with `Smoke test complete: 82 passed, 0 failed.`

### 4. Code Separation & Module Boundary Guards
```bash
python3 tools/code_separation_guard.py --path lib/McpNewsTools.class.php
python3 tools/module_boundary_guard.py
```
*Expected*: Both return `[PASS]` / `✅ PASSED`.

### 5. Invalidation Conditions
- Calling `list_faqs` with `mod_name`, `vid`, `lang`, and `status` drops earlier filters and returns items belonging to other `vid`s.
- `resolveEnvironment()` returns `"site_status": "unknown"`.
- `create_news` allows staging an article under category ID 17 (`root = 'y'`).
- `create_news` with `seo = "dirty seo slug with spaces"` stores unnormalized spaces.
- `create_headline` throws `TypeError`.
- `batch_create_faqs` commits partial items when an item is invalid.
- `list_faqs` with `include_answer: true` omits the `answer` key.
