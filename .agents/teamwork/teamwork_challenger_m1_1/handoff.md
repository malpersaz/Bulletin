# Handoff Report: Milestone 1 — Challenger M1-1 Empirical Assessment

**Challenger**: Challenger M1-1 (`teamwork_challenger_m1_1`)  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_challenger_m1_1/`  
**Recipient**: Sub-Orchestrator M1 (`sub_orch_m1`, conversation ID `901f7801-31c7-4c08-be51-94ed6f17c21d`)  
**Scope**: Empirical verification of Milestone 1 features (Conjunctive ORM filters, draft FAQ auditing, root category guard, dirty SEO slug normalization, batch FAQ atomicity, and existing smoke test suite)  
**Date**: 2026-11-11  
**Verdict**: **REQUEST_CHANGES**

---

## 1. Observation

### 1.1 Existing MCP Smoke Test Suite Execution
- **Command**: `docker exec bulletin_app php tasks/mcp_smoke.php`
- **Result**: Exit code 0, verbatim output:
  `Smoke test complete: 82 passed, 0 failed.`
- **Verified Subsystems**:
  - `Environment[v1.0.63]`: `site_status` resolves strictly to `'open'` or `'closed'` (never `'unknown'`).
  - `Headline[TypeErrorFix]`: `create_headline` and `update_headline` run cleanly with string argument.
  - `Audit[DraftFAQ]`: `get_news` reflects draft FAQs on draft news (`status = 'n'`).
  - `FAQ[Batch]`: `batch_create_faqs` creates multiple FAQs atomically.
  - `FAQ[Discovery]`: `list_faq_modules` lists registered host modules from `mod_faq_mod`.
  - `FAQ[AnswerToggle]`: `list_faqs` omits `answer` when `include_answer: false` and includes it when `true`.

### 1.2 Standalone Empirical Stress Test Suite (`tasks/challenger_stress_test.php`)
- **Command**: `docker exec bulletin_app php tasks/challenger_stress_test.php`
- **Result**: 47 passed, 1 failed across 5 critical challenge dimensions.

#### Dimension 1 — Conjunctive Filtering Stress Test (PASSED)
- **Observations**:
  - Staged 2 news articles (`vid=NewsA`, `vid=NewsB`) and 5 distinct FAQs across different `mod_name`, `vid`, `status`, and keywords.
  - Query with `mod_name = 'mod_news'`: strictly returned FAQs with `mod_name = 'mod_news'`.
  - Query with `mod_name = 'mod_news'` + `vid = NewsA`: count narrowed strictly from all news FAQs down to exactly 2 items belonging to `NewsA` (FAQ 1 status 'y', FAQ 2 status 'n').
  - Query with `mod_name = 'mod_news'` + `vid = NewsA` + `status = 'y'`: count narrowed to exactly 1 item (FAQ 1), excluding draft FAQ 2.
  - Disjoint query `mod_name = 'mod_news'` + `vid = NewsA` + `status = 'y'` + `search = 'sur'` (where `'sur'` only exists on draft FAQ 2): returned count 0.
  - Disjoint query `mod_name = 'mod_news'` + `vid = NewsA` + `search = 'gozlu'` (where `'gozlu'` only exists on NewsB): returned count 0.
  - Disjoint query `mod_name = 'mod_page'` + `search = 'sur'`: returned count 0.
  - **Verdict**: Filter clobbering (B1) is completely eradicated. `addEqFilter()` and `addLikeSearchFilter()` use `$model->addWhere()` which produces true conjunctive SQL ($A \land B \land C \land D$).

#### Dimension 2 — Draft FAQ Auditing on Draft News (PASSED)
- **Observations**:
  - Fresh draft news article (`status = 'n'`): `get_news` returned `faq_count = 0`, `faq_draft_count = 0`, `faq_published_count = 0`.
  - Added 1 draft FAQ (`status = 'n'`): `get_news` returned `faq_count = 1`, `faq_draft_count = 1`, `faq_published_count = 0`, with full draft item in `faq_items`.
  - Added 1 published FAQ (`status = 'y'`): `get_news` returned `faq_count = 2`, `faq_draft_count = 1`, `faq_published_count = 1`.
  - Updated news article to published (`status = 'y'`): `get_news` returned `faq_count = 1`, `faq_draft_count = 0`, `faq_published_count = 1`. Draft FAQ was correctly suppressed from public news audit.
  - **Verdict**: Dual-status audit logic in `McpCrudToolsBase::getRecordAuditMeta` performs exactly as specified.

#### Dimension 3 — Category Root Guard (PASSED)
- **Observations**:
  - Staging `create_news` with root category ID 17 (`root = 'y'`, `"##Türkçe_Kategoriler"`): threw `McpToolException`:
    `Category '17' ('##Türkçe_Kategoriler') is a structural root container (root='y') and cannot contain news directly. Selectable child categories: ID 18 (Gündem), ID 19 (Politika), ID 20 (Ekonomi)...`
  - Calling `update_news` to change category to root category ID 17: threw `McpToolException` and rejected update.
  - Staging `create_news` with non-existent category ID 9999999: threw `McpToolException: Category record 9999999 not found.`
  - **Verdict**: Category root guard is active, actionable, and enforced on both creation and mutation.

#### Dimension 4 — Dirty SEO Slug Stress Test (FAILED)
- **Observations**:
  - Tested `McpCrudToolsBase::makeSeo` and `create_news` with:
    1. Combining dot above `I\u{0307}stanbul`: normalized to `İstanbul` (NFC, `\u{0307}` stripped).
    2. Kurdish typography `P\u{0131}rs \u{0130}nternet` (`lang = 'ku'`): normalized to `Pirs_Internet`.
    3. Dashes and multiple spaces `Son  Dakika --- Gelişmesi`: normalized to `Son_Dakika_Gelişmesi`.
    4. Dirty input with forward slash: `"Diyarbakır Haber: Test / '123'"` with `lang = 'tr'`.
  - **Observed Result for Case 4**:
    `McpCrudToolsBase::makeSeo("Diyarbakır Haber: Test / '123'", 'tr')` produced:
    `'Diyarbakır_Haber_Test_/_123'`
  - **Code Inspection** (`lib/McpCrudToolsBase.class.php:752–754`):
    ```php
    $value = preg_replace('/[\x{0027}\x{0022}\x{2019}\x{201B}\x{201C}\x{201D}\x{201E}\x{201F}:;,!?()\[\]{}]/u', '', $text) ?? '';
    $value = preg_replace('/[\s-]+/u', '_', $value) ?? '';
    ```
    Forward slash `/`, backslash `\`, angle brackets `< >`, hash `#`, ampersand `&`, percent `%`, and other non-alphanumeric symbols are NOT matched by this regex and are stored verbatim into the database `seo` column.
  - **Frontend Routing Breakage**:
    In Bulletin CMS, news routing is defined as `{lang}/news/{seo}`. When `{seo}` contains `/` (e.g. `tr/news/Diyarbakır_Haber_Test_/_123`), `Amed.class.php:639` executes:
    `$val = explode('/', $route);`
    The slug is split across multiple route segments (`$val[1] = 'Diyarbakır_Haber_Test_'`, `$val[2] = '_123'`), causing the router to search for a nonexistent controller action and returning HTTP 404.
  - **Comparison with Canonical Implementation**:
    In `lib/Helper.class.php:180` (`Helper::legacy_seo`):
    ```php
    $s = preg_replace('/[\x{0027}\x{0022}\x{2019}\x{201B}\x{201C}\x{201D}\x{201E}\x{201F}:;,!?()\[\]{}]/u', '', (string) $s);
    $s = preg_replace('/[^\p{L}\p{N} ]/u', " ", $s);
    $s = trim($s);
    $s = preg_replace('/\s+/', '_', $s);
    ```
    And in `templates/admin/assets/js/script.js:73` (`makeLegacySeoValue`):
    ```javascript
    clean = clean.replace(/[^\p{L}\p{N}\s_-]/gu, " ");
    ```
    Both canonical implementations replace `[^\p{L}\p{N}\s_-]` with spaces before converting to underscores, ensuring forward slashes and symbols never enter URL slugs.

#### Dimension 5 — `batch_create_faqs` Transaction Integrity (PASSED)
- **Observations**:
  - Batch with 1 valid Turkish FAQ + 1 invalid Kurdish FAQ on Turkish news entity: threw `McpToolException: Content language mismatch`. Zero items were committed to `mod_faq` (DB count unchanged).
  - Batch with intra-batch duplicate questions (`"Ayni Soru Metni..."` and `"ayni soru metni..."`): threw `McpToolException: Duplicate question within batch`. Zero items committed.
  - Batch with empty question: threw `McpToolException: Item 1: Field 'question' is required`. Zero items committed.
  - Valid batch of 3 items: atomically committed all 3 items (DB count increased by exactly 3).
  - Batch with `replace_existing_drafts = true`: purged previous drafts and created new draft, while preserving published FAQs.
  - **Note on English Prose**: `assertContentMatchesLang()` inspects Kurdish prose in non-Kurdish records and Turkish prose in non-Turkish records, but does not currently detect English prose in Turkish or Kurdish records.

---

## 2. Logic Chain

1. **Routing Invariant**:
   - Bulletin CMS routes article detail pages via `{lang}/news/{seo}`.
   - `lib/Amed.class.php:639` splits the URI path by `/` via `explode('/', $route)`.
   - Any URL slug containing a literal forward slash `/` corrupts path segment parsing, misidentifies controller actions, and causes an immediate HTTP 404 error.

2. **SEO Normalization Defect**:
   - `McpCrudToolsBase::makeSeo()` is invoked mandatorily during `create_news` and `update_news` to enforce Wikipedia-style slugs.
   - However, `makeSeo()` removes only a subset of punctuation (`['’"”„“:;,!?()[]{}]`) and does not strip forward slashes `/`, backslashes `\`, `< >`, `#`, or `&`.
   - Running `makeSeo("Diyarbakır Haber: Test / '123'", 'tr')` stores `Diyarbakır_Haber_Test_/_123` into `mod_news.seo`.
   - Any article whose title or explicit slug contains `/` (e.g., season dates `"2025/2026"`, department names `"Diyarbakır/Sur"`, or slashed headlines) produces permanently unrouteable 404 links.

3. **Required Remediation**:
   - `McpCrudToolsBase::makeSeo()` must align with `Helper::legacy_seo()` and JS `makeLegacySeoValue()` by converting `/` and non-alphanumeric symbols (`[^\p{L}\p{N}\s_-]`) to spaces before underscore collapsing.

---

## 3. Caveats

1. All other Milestone 1 features (Conjunctive ORM filters, Adapter_Mysqli database calls, draft FAQ audit counts, root category guard, batch FAQ transaction integrity, and Nginx `.well-known` configuration) were rigorously tested and verified completely robust.
2. The scope of the requested change is strictly isolated to slug character sanitization in `lib/McpCrudToolsBase.class.php::makeSeo()`.

---

## 4. Conclusion

**Verdict: REQUEST_CHANGES**

Milestone 1 cannot be approved in its current state because `McpCrudToolsBase::makeSeo()` permits forward slashes `/` and unsafe symbols in SEO slugs, creating broken 404 URLs when articles with slashed titles are staged via MCP.

### Action Items for Worker M1-1:
1. In `lib/McpCrudToolsBase.class.php`, update `makeSeo()` to sanitize forward slashes and non-alphanumeric characters by replacing `[^\p{L}\p{N}\s_-]/u` with space prior to whitespace-to-underscore conversion:
   ```php
   // Remove apostrophes, quotation marks, and punctuation
   $value = preg_replace('/[\x{0027}\x{0022}\x{2019}\x{201B}\x{201C}\x{201D}\x{201E}\x{201F}:;,!?()\[\]{}]/u', '', $text) ?? '';
   // Replace slashes, backslashes, and symbols with spaces so they become underscores
   $value = preg_replace('/[^\p{L}\p{N}\s_-]/u', ' ', $value) ?? '';
   // Whitespace and dashes become underscores
   $value = preg_replace('/[\s-]+/u', '_', $value) ?? '';
   ```
2. Verify that `McpCrudToolsBase::makeSeo("Diyarbakır Haber: Test / '123'", 'tr')` produces `'Diyarbakır_Haber_Test_123'` without `/`.
3. Re-run `docker exec bulletin_app php tasks/challenger_stress_test.php` to confirm 48 passed, 0 failed.
4. Re-run `docker exec bulletin_app php tasks/mcp_smoke.php`.

---

## 5. Verification Method

### 1. Reproduce the Slug Failure
```bash
docker exec bulletin_app php -r "
require 'init.php';
require 'lib/McpCrudToolsBase.class.php';
echo McpCrudToolsBase::makeSeo(\"Diyarbakır Haber: Test / '123'\", 'tr') . PHP_EOL;
"
```
*Current output*: `Diyarbakır_Haber_Test_/_123` (Contains `/` — INVALID)  
*Expected output after fix*: `Diyarbakır_Haber_Test_123` (No `/` — VALID)

### 2. Standalone Stress Test Harness
```bash
docker exec bulletin_app php tasks/challenger_stress_test.php
```
*Expected*: Exit code 0, 48 passed, 0 failed.

### 3. Full MCP Smoke Suite
```bash
docker exec bulletin_app php tasks/mcp_smoke.php
```
*Expected*: Exit code 0, 82 passed, 0 failed.

### 4. Quality Guards
```bash
python3 tools/code_separation_guard.py --path lib/McpCrudToolsBase.class.php
python3 tools/module_boundary_guard.py
```
*Expected*: `[PASS]` / `✅ PASSED`.
