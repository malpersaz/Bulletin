# Handoff Report: Milestone 1, Iteration 2 Review & Adversarial Stress Test

**Reviewer**: Reviewer M1-R2-2 (`teamwork_reviewer_m1_r2_2`)  
**Roles**: Reviewer, Adversarial Critic  
**Working Directory**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_reviewer_m1_r2_2/`  
**Recipient**: Sub-Orchestrator M1 (`sub_orch_m1`, conversation ID `901f7801-31c7-4c08-be51-94ed6f17c21d`)  
**Verdict**: **APPROVE**  
**Date**: 2026-11-11  

---

## 1. Observation

Direct observations from independent test execution, source code inspection, and adversarial probing:

### 1.1 Quality Guards
Commands executed from workspace root:
- `python3 tools/i18n_guard.py`:
  ```
  ========================================================
   🌐 Amed Framework i18n-guard Quality Audit Report
  ========================================================
  📁 Files Audited: 11
  ✅ 100% CLEAN! Zero violations found in audited files.
  Exit code 0.
  ```
- `python3 tools/code_separation_guard.py`:
  ```
  [PASS] Code Separation Guard: 0 violations found across 5 files.
  Exit code 0.
  ```
- `python3 tools/module_boundary_guard.py`:
  ```
  ============================================================
    Amed Framework - Module Boundary Guard Report
  ============================================================
  Mode: Git Touched Files Audit
  Forbidden Modules Protected: 18
  Scanned Files: 46
  Violations Found: 0
  ------------------------------------------------------------
  ✅ PASSED: No forbidden module files or bindings detected.
  ============================================================
  Exit code 0.
  ```

### 1.2 PHP Syntax Validation
Command: `php -l lib/McpCrudToolsBase.class.php && php -l lib/McpFaqTools.class.php && php -l lib/McpNewsTools.class.php && php -l tasks/mcp_smoke.php && php -l tasks/challenger_stress_test.php && php -l tests/m1_2_challenger_test.php`
- All 6 files reported `No syntax errors detected` with exit code 0.

### 1.3 Automated Test Suites
- `docker exec bulletin_app php tasks/mcp_smoke.php`:
  `Smoke test complete: 83 passed, 0 failed.` (Exit code 0).
- `docker exec bulletin_app php tasks/challenger_stress_test.php`:
  `CHALLENGER SUMMARY: 48 passed, 0 failed.` (Exit code 0).
- `docker exec bulletin_app php tests/m1_2_challenger_test.php`:
  `CHALLENGER SUITE COMPLETE: 47 passed, 0 failed.` (Exit code 0).

### 1.4 Code Inspection
1. **Scoped Draft FAQ Deletion (`lib/McpFaqTools.class.php:578–587`)**:
   ```php
   if ($replaceDrafts) {
       $draftModel = $this->freshModel(self::FAQ_MODULE);
       $this->addEqFilter($draftModel, 'mod_name', $modName);
       $this->addEqFilter($draftModel, 'vid', $vid);
       $this->addEqFilter($draftModel, 'lang', $lang);
       $this->addEqFilter($draftModel, 'status', 'n');
       $draftModel->delete();
   }
   ```
   Each condition is chained via `$this->addEqFilter()` which delegates to `$model->addWhere($clause)`, compiling into `WHERE mod_name = '...' AND vid = ... AND lang = '...' AND status = 'n'`.
2. **Unicode and Slug Normalization (`lib/McpCrudToolsBase.class.php:744–785`)**:
   - `\Normalizer::normalize($text, \Normalizer::FORM_C)` executes first.
   - Decomposed combining marks (`\u{0308}`, `\u{0327}`, etc.) compose into base letter characters (`\p{L}`).
   - Orphan combining dot `\u{0307}` is stripped via `str_replace`.
   - `preg_replace('/[^\p{L}\p{N}\s_-]/u', ' ', $value)` eliminates forward slashes `/`, backslashes `\`, angle brackets, hashtags, query marks, emojis, and symbols.
   - Kurdish locale checks map `ı`/`İ` -> `i`/`I` under `ku`, while preserving them under `tr`.
   - Whitespace and dashes collapse to single underscores, trimmed, and capped at `mb_substr($value, 0, 255)`.
3. **Category Validation on Update (`lib/McpNewsTools.class.php:978–983`)**:
   ```php
   $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;
   $categoryId = isset($args['category_id']) ? (int) $args['category_id'] : (int) $model->category_id;

   if (isset($args['category_id']) || $lang !== (string) $model->lang) {
       $this->assertSelectableNewsCategory($categoryId, $lang);
   }
   ```
   Whenever `category_id` is supplied in the arguments, `assertSelectableNewsCategory` runs unconditionally, preventing an article from cementing or keeping a structural root category.

---

## 2. Logic Chain

1. **Defect 1 (FAQ Deletion Scoping)**:
   - *Observation*: In Iteration 1, `$draftModel->where_mod_name_and_vid_and_lang()` followed by `$draftModel->where_status('n')` overwrote `$this->_where`, executing `DELETE FROM mod_faq WHERE status = 'n'`.
   - *Fix Verification*: `lib/McpFaqTools.class.php` now calls `addEqFilter` 4 times. In `Database.php:613`, `addWhere()` joins clauses with SQL `AND`.
   - *Empirical Stress Test*: An independent adversarial test seeded 2 news records (`vid=888881` and `vid=888882`) and a page record (`mod_page`, `vid=888881`) with mixed draft and published FAQs. Running `batchCreateFaqs` on `vid=888881` with `replace_existing_drafts: true` deleted ONLY the 2 drafts on `vid=888881`. The published FAQ on `888881`, the draft on `888882`, the published on `888882`, and the draft on `mod_page` ALL survived intact.
2. **Defect 2 (makeSeo Unicode & Route Splitting)**:
   - *Observation*: In Iteration 1, slashes passed through `makeSeo`, producing URLs like `/news/foo/bar` that broke `Amed.class.php` controller dispatching, and combining diacritics were stripped prior to NFC composition.
   - *Fix Verification*: `makeSeo` normalizes to NFC first, then replaces any non-`\p{L}\p{N}\s_-` with space before converting to underscores.
   - *Empirical Stress Test*:
     - Decomposed `u + \u{0308}` successfully composed into `ü` (`gül`).
     - Slashes `news/article` and `news///category//slug` were converted to `news_article` and `news_category_slug`.
     - Traversal `../../etc/passwd` converted to `etc_passwd`.
     - Script tags `<script>alert('xss')</script>` converted to `script_alertxss_script` (zero tag injection).
     - Kurdish mapping correctly converted `Diyarbekır` -> `Diyarbekir` under `ku`, but preserved `Diyarbekır` and `İstanbul` under `tr`.
     - Length was safely truncated to 255 multi-byte characters.
3. **Defect 3 (updateNews Root Category Bypass)**:
   - *Observation*: In Iteration 1, `updateNews` only checked `if ($categoryId !== (int)$model->category_id ...)`. Updating an article that was already on a root category with that same root category bypassed validation.
   - *Fix Verification*: `lib/McpNewsTools.class.php:981` checks `isset($args['category_id']) || $lang !== (string) $model->lang`.
   - *Empirical Stress Test*:
     - Forcing an article to hold root category 17 (`##Türkçe_Kategoriler`) and calling `updateNews(['id' => 777771, 'category_id' => 17])` triggered `McpToolException: Category '17' ('##Türkçe_Kategoriler') is a structural root container (root='y') and cannot contain news directly...`.
     - Updating to valid leaf category 18 succeeded.
     - Updating to non-existent category 999999 was rejected.
     - Changing language from `tr` to inactive language was rejected before database touch.
4. **Defect 4 (i18n Guard & Dev Terms)**:
   - *Observation*: Iteration 1 had 22 i18n_guard violations due to sub-tokens `eq`, `intra`, `jaccard`, and `$cand*`.
   - *Fix Verification*: Valid dev tokens were registered in `DEV_TERMS`, variables were renamed to full descriptive identifiers (`$candidate*`, `$duplicate*`), and comments were cleaned. `tools/i18n_guard.py` exits 0 with 0 violations across 11 files.
5. **Defect 5 (Regression Testing in Smoke Test)**:
   - *Observation*: Smoke test previously lacked an assertion that `replace_existing_drafts` does not purge unrelated drafts.
   - *Fix Verification*: Step 9.1 `FAQ[BatchDraftReplace]` in `tasks/mcp_smoke.php` seeds a sentinel draft with `vid = 999999`, runs batch creation with replacement on the test entity, verifies sentinel survival, and cleans up.

---

## 3. Caveats

- In `tasks/mcp_smoke.php`, API keys are named `'Smoke test key'`. If multiple subagents run `mcp_smoke.php` simultaneously against the shared container database, one run's teardown will delete the active key of the other run and cause 401 errors. When executed without concurrent interference, `mcp_smoke.php` passes with 83/83.
- `makeSeo()` relies on PHP `ext-intl`'s `\Normalizer`. `ext-intl` is active in the production Docker image and host environment.
- No other caveats identified.

---

## 4. Integrity Assessment

- **Hardcoded test returns**: None detected. Code performs genuine ORM, Database, and Unicode operations.
- **Dummy/Facade implementations**: None detected. All methods implement production business logic.
- **Shortcuts / Task Bypasses**: None detected.
- **Fabricated verification outputs**: None detected. Verified independently via CLI commands and custom test harnesses.
- **Self-certifying work**: All worker claims were empirically reproduced and independently validated.

---

## 5. Conclusion

Milestone 1, Iteration 2 remediations are robust, complete, and verified.
- Draft FAQ replacement is strictly scoped to the targeted entity and language.
- Unicode handling in `makeSeo` preserves diacritics and eliminates all routing-breaking symbols.
- Leaf category validation in `updateNews` prevents root categories from being cemented.
- All three quality guards pass with zero violations.
- All automated test suites (83 smoke tests, 48 stress tests, 47 challenger tests) pass with 100% success.

**Final Verdict: APPROVE**.

---

## 6. Verification Method

To independently verify this verdict:

```bash
# 1. Quality Guards
python3 tools/i18n_guard.py
python3 tools/code_separation_guard.py
python3 tools/module_boundary_guard.py

# 2. PHP Syntax
php -l lib/McpCrudToolsBase.class.php
php -l lib/McpFaqTools.class.php
php -l lib/McpNewsTools.class.php
php -l tasks/mcp_smoke.php
php -l tasks/challenger_stress_test.php
php -l tests/m1_2_challenger_test.php

# 3. Test Suites
docker exec bulletin_app php tasks/mcp_smoke.php
docker exec bulletin_app php tasks/challenger_stress_test.php
docker exec bulletin_app php tests/m1_2_challenger_test.php
```
