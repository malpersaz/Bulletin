# Handoff Report: Milestone 1 Independent Review & Adversarial Audit

**Reviewer**: Reviewer M1-1 (`teamwork_reviewer_m1_1`)  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_reviewer_m1_1/`  
**Recipient**: Sub-Orchestrator M1 (`sub_orch_m1`, conversation ID `901f7801-31c7-4c08-be51-94ed6f17c21d`)  
**Verdict**: **REQUEST_CHANGES**  
**Date**: 2026-11-11  

---

## 1. Observation

### 1.1 Critical Finding: Catastrophic WHERE Clause Clobbering in `batchCreateFaqs` Draft Deletion
- **Location**: `lib/McpFaqTools.class.php:579–584`
- **Verbatim Code**:
  ```php
  if ($replaceDrafts) {
      $draftModel = $this->freshModel(self::FAQ_MODULE);
      $draftModel->where_mod_name_and_vid_and_lang($modName, $vid, $lang);
      $draftModel->where_status('n');
      $draftModel->delete();
  }
  ```
- **Observed Behavior (Runtime Verification)**:
  Executing the sequence in PHP against the framework runtime:
  ```bash
  docker exec bulletin_app php -r '
  require_once "/var/www/html/init.php";
  require_once "/var/www/html/lib/McpCrudToolsBase.class.php";
  require_once "/var/www/html/lib/McpFaqTools.class.php";
  $tool = new McpFaqTools(1, []);
  $ref = new ReflectionObject($tool);
  $fm = $ref->getMethod("freshModel");
  $m = $fm->invoke($tool, "mod_faq");
  $m->where_mod_name_and_vid_and_lang("mod_news", 123, "tr");
  echo "After first where: " . $m->_where . "\n";
  $m->where_status("n");
  echo "After where_status: " . $m->_where . "\n";
  '
  ```
  **Output**:
  ```
  After first where:  mod_name = 'mod_news' AND  vid = '123' AND  lang = 'tr' 
  After where_status:  status = 'n' 
  ```
- **Database Query Result**:
  When `$draftModel->delete()` is invoked, `Database::delete()` evaluates:
  ```php
  $this->_sql_text = "DELETE FROM " . $this->_from;
  $this->_sql_text .= isset($this->_where) ? " WHERE " . $this->_where : '';
  ```
  Which generates and executes:
  ```sql
  DELETE FROM mod_faq WHERE status = 'n'
  ```
  This unconditionally purges **ALL draft FAQs across the ENTIRE system** (all news articles, pages, and publications in all languages), rather than scoping deletion to the specific `$modName`, `$vid`, and `$lang`.

### 1.2 Major Finding: Acceptance Criteria Quality Gate Failure (`tools/i18n_guard.py`)
- **Location**: Multiple touched files across the milestone.
- **Observed Command & Output**:
  `python3 tools/i18n_guard.py`
  ```
  📁 Files Audited: 7
  🚨 Total Violations: 22 (22 Errors, 0 Warnings)

  ❌ ERROR [NON_ENGLISH_FUNCTION] lib/McpCrudToolsBase.class.php:365
     Message: Non-English function name 'addEqFilter': Unrecognized non-English token: 'Eq'
     Snippet: protected function addEqFilter(object $model, string $column, string|int|float $value): void

  ❌ ERROR [NON_ENGLISH_COMMENT] lib/McpFaqTools.class.php:511
     Message: Non-English comment word 'intra' detected. Comments must be strictly in English: Unrecognized non-English token: 'intra'
     Snippet: // Phase 1: In-memory structure & intra-batch uniqueness validation

  ❌ ERROR [NON_ENGLISH_COMMENT] lib/McpNewsTools.class.php:1177
     Message: Non-English comment word 'Jaccard' detected. Comments must be strictly in English: Unrecognized non-English token: 'Jaccard'
     Snippet: * Compares headline token Jaccard similarity and exact SEO match.

  ❌ ERROR [NON_ENGLISH_VARIABLE] lib/McpNewsTools.class.php:1207-1234 (13 occurrences)
     Message: Non-English variable '$cand' / '$candName' / '$candSeo' / '$candTokens': Unrecognized non-English token: 'cand'

  ❌ ERROR [NON_ENGLISH_VARIABLE] tasks/mcp_smoke.php:886-895 (6 occurrences)
     Message: Non-English variable '$resDup' / '$dupPayload': Unrecognized non-English token: 'dup'
  ```
  Exit code: `1`.
- **Contract Reference**:
  `sub_orch_m1/SCOPE.md` line 28 explicitly specifies:
  > `All quality guards pass (code separation, i18n, module boundary).`
  The worker executed only `code_separation_guard.py` and `module_boundary_guard.py`, omitting `i18n_guard.py` from their reported verification.

### 1.3 Major Finding: Test Coverage Gap on Destructive Operation (`replace_existing_drafts`)
- **Location**: `tasks/mcp_smoke.php:763–789`
- **Observed Behavior**:
  Section 6.3 of `tasks/mcp_smoke.php` invokes `batch_create_faqs` with `replace_existing_drafts` omitted (defaulting to `false`). It never tests the `replace_existing_drafts: true` execution path, nor does it assert that pre-existing draft FAQs for other entities remain untouched. As a result, the destructive `DELETE FROM mod_faq WHERE status = 'n'` bug completely evaded detection in automated testing.

### 1.4 Minor Finding: Leaf Category Guard Incomplete in `updateNews()`
- **Location**: `lib/McpNewsTools.class.php:981–983`
- **Verbatim Code**:
  ```php
  if ($categoryId !== (int) $model->category_id || $lang !== (string) $model->lang) {
      $this->assertSelectableNewsCategory($categoryId, $lang);
  }
  ```
- **Observed Behavior**:
  If an existing article was historically created under a root category (`root = 'y'`) prior to v1.0.63, or if an update payload explicitly submits `['category_id' => $existingRootId]`, the condition evaluates to `false` and skips `assertSelectableNewsCategory()`. To prevent perpetuating root category associations, any update that explicitly provides `category_id` in its payload (`isset($args['category_id'])`) should be validated.

### 1.5 Verification of Genuine Implementation & Functional Fixes
Independent testing verified that the following features were genuinely implemented with real logic and no dummy or facade shortcuts:
- `php -l` passed on all 7 touched PHP files (`lib/McpCrudToolsBase.class.php`, `lib/McpServer.class.php`, `lib/McpNewsTools.class.php`, `lib/McpHeadlineTools.class.php`, `lib/McpFaqTools.class.php`, `lib/McpSurveyTools.class.php`, `tasks/mcp_smoke.php`).
- `python3 -m py_compile tools/mcp_health_check.py` passed with 0 errors.
- `docker exec bulletin_app php tasks/mcp_smoke.php` executed cleanly: **82 passed, 0 failed**.
- Conjunctive filtering via `addEqFilter()` and `addLikeSearchFilter()` correctly prevents filter clobbering in `applyCategoryFilters()`, `applyFaqFilters()`, and `applyNewsFilters()`.
- Adapter_Mysqli fatal `fetch()` errors in `resolveEnvironment()` and `getRecordAuditMeta()` are resolved using `fetch_array()` and `toArray()`.
- `createHeadline()` and `createSurvey()` type errors are fixed by passing strings instead of arrays into `assertContentMatchesLang()`.
- Leaf category check in `createNews()` correctly blocks structural root categories.
- Mandatory `makeSeo()` normalizes explicit dirty slugs and title fallback slugs.
- `getNews()` envelopes are correctly enriched with author and category details.
- `checkDuplicateNews()` performs genuine Jaccard token comparison and exact SEO slug matching.
- `docker/nginx/prod.conf` adds `location ^~ /.well-known/` preferential prefix matching before `location ~ /\.`.
- `tools/mcp_health_check.py` Stage 7 removes remote 403 masking.

---

## 2. Logic Chain

1. **Catastrophic WHERE Clause Clobbering**:
   - `Orm::_call_where` takes any `where_*` call and executes `$this->where($sql)`.
   - `Database::where($arg)` overwrites `$this->_where` unconditionally with the new argument.
   - In `batchCreateFaqs()`, lines 581–582 execute `$draftModel->where_mod_name_and_vid_and_lang(...)` followed by `$draftModel->where_status('n')`.
   - The second call unconditionally overwrites `$this->_where` to `status = 'n'`.
   - Line 583 calls `$draftModel->delete()`, executing `DELETE FROM mod_faq WHERE status = 'n'`.
   - Consequently, calling `batch_create_faqs` with `replace_existing_drafts: true` deletes every single draft FAQ across all modules in the entire database.
   - This directly recreates the B1 clobbering defect in new code and constitutes a critical blocker.

2. **Acceptance Criteria Failure on Quality Guards**:
   - `SCOPE.md` lines 23–28 establish the acceptance criteria for Milestone 1. Criterion 5 explicitly mandates that all quality guards pass, specifically naming `i18n`.
   - `python3 tools/i18n_guard.py` failed with exit code 1 and 22 critical errors.
   - 19 of the 22 errors are due to colloquial abbreviations (`$cand`, `$dup`) instead of standard English words (`$candidate`, `$duplicate`).
   - 2 errors are due to vocabulary in comments (`intra`, `Jaccard`).
   - 1 error is due to `Eq` in `addEqFilter`, which is mandated by the project contract but missing from `DEV_TERMS` in `tools/i18n_guard.py`.
   - Omitting `i18n_guard.py` from the worker verification allowed this gate failure to escape to review.

3. **Coverage Blind Spot**:
   - Smoke tests in `tasks/mcp_smoke.php` achieved 82 passing tests, but omitted testing `replace_existing_drafts: true` while independent draft FAQs exist in the table.
   - Without test assertions verifying table-wide draft isolation, destructive regressions in `batchCreateFaqs` remain unverified.

---

## 3. Caveats

- No integrity violations or cheating patterns (e.g. hardcoded return values, facade stubs, fabricated logs) were detected in the source code; the implementation consists of genuine application and database logic.
- The 22 violations reported by `i18n_guard.py` are strictly lexical (variable naming, comment vocabulary, and an unlisted abbreviation `eq` in `DEV_TERMS`), but because `SCOPE.md` explicitly lists `i18n` guard passage as an acceptance criterion, it must be resolved.

---

## 4. Conclusion

**Verdict: REQUEST_CHANGES**

Milestone 1 cannot be approved in its current state due to:
1. **Critical Data Loss Defect**: `batchCreateFaqs` executes `DELETE FROM mod_faq WHERE status = 'n'` when `replace_existing_drafts: true`, destroying all system draft FAQs.
2. **Acceptance Criteria Failure**: `python3 tools/i18n_guard.py` fails with 22 critical errors.
3. **Smoke Test Coverage Gap**: Missing verification for scoped draft replacement and root category update rejection.

### Required Remediations for Worker M1-1:
1. **Fix Draft Deletion Scoping in `lib/McpFaqTools.class.php:579–584`**:
   Replace the sequential where calls with a conjunctive clause:
   ```php
   if ($replaceDrafts) {
       $draftModel = $this->freshModel(self::FAQ_MODULE);
       $draftModel->where_mod_name_and_vid_and_lang_and_status($modName, $vid, $lang, 'n');
       $draftModel->delete();
   }
   ```
   (or use `$this->addEqFilter()` for `mod_name`, `vid`, `lang`, and `status`).
2. **Resolve All 22 `i18n_guard.py` Errors**:
   - Rename `$cand*` to `$candidate*` in `lib/McpNewsTools.class.php`.
   - Rename `$resDup` / `$dupPayload` to `$resDuplicate` / `$duplicatePayload` in `tasks/mcp_smoke.php`.
   - In `lib/McpFaqTools.class.php:511`, replace `intra-batch` with `within-batch`.
   - In `lib/McpNewsTools.class.php:1177`, adjust docblock phrasing.
   - In `tools/i18n_guard.py`, add `eq`, `jaccard` (and standard math/comparison terms) to `DEV_TERMS` so `addEqFilter` complies with the interface contract.
3. **Add Test Assertions in `tasks/mcp_smoke.php`**:
   - Seed a dummy draft FAQ on a secondary entity (e.g. `vid = 999999`).
   - Call `batch_create_faqs` on the test entity with `replace_existing_drafts: true`.
   - Assert that the secondary entity's draft FAQ was NOT deleted.
4. **Harden Leaf Category Check in `lib/McpNewsTools.class.php:981–983`**:
   Check `if (isset($args['category_id']) || $lang !== (string) $model->lang)`.

---

## 5. Verification Method

### 1. Verification of the Critical Bug
Run inside the container:
```bash
docker exec bulletin_app php -r '
require_once "/var/www/html/init.php";
require_once "/var/www/html/lib/McpCrudToolsBase.class.php";
require_once "/var/www/html/lib/McpFaqTools.class.php";
$tool = new McpFaqTools(1, []);
$ref = new ReflectionObject($tool);
$fm = $ref->getMethod("freshModel");
$m = $fm->invoke($tool, "mod_faq");
$m->where_mod_name_and_vid_and_lang("mod_news", 123, "tr");
$m->where_status("n");
echo "Current WHERE: " . $m->_where . "\n";
'
```
*Expected after fix*: The where clause must contain both entity filters (`mod_name = 'mod_news' AND vid = '123' AND lang = 'tr'`) AND `status = 'n'`.

### 2. Quality Guard Execution
```bash
python3 tools/i18n_guard.py
python3 tools/code_separation_guard.py
python3 tools/module_boundary_guard.py
```
*Expected after fix*: All three quality guard tools exit 0 with 0 violations.

### 3. Full Smoke Suite
```bash
docker exec bulletin_app php tasks/mcp_smoke.php
```
*Expected*: All tests pass, including the new assertion verifying draft isolation.
