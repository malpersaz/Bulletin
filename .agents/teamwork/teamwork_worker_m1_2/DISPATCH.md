## 2026-11-11T11:42:32Z

DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

You are Worker M1-2 for Milestone 1, Iteration 2.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_worker_m1_2/

MANDATORY: Read ORIGINAL_REQUEST.md first at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
Also read:
- Milestone Scope: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m1/SCOPE.md
- Project Spec: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
- Gate Status (Iteration 1 Failure): /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m1/GATE_STATUS.md
- Iteration 2 Explorer Reports:
  - /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_r2_1/handoff.md
  - /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_r2_2/handoff.md
  - /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_r2_3/handoff.md

Your Owned Files:
- lib/McpCrudToolsBase.class.php
- lib/McpNewsTools.class.php
- lib/McpFaqTools.class.php
- tools/i18n_guard.py
- tasks/mcp_smoke.php

Specific Tasks:
1. Fix `batchCreateFaqs` in `lib/McpFaqTools.class.php:579–584`:
   Replace `$draftModel->where_mod_name_and_vid_and_lang(...)` and `$draftModel->where_status('n')` with scoped conjunctive filters:
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
2. Fix `makeSeo()` in `lib/McpCrudToolsBase.class.php:742–777`:
   Implement exact Unicode-safe order of operations:
   - Normalize via `Normalizer::normalize($text, Normalizer::FORM_C)` first.
   - Strip orphan `\u{0307}`.
   - Strip quotes/specific punctuation.
   - Replace non-alphanumeric `[^\p{L}\p{N}\s_-]/u` with space `' '` (so forward slashes `/`, backslashes, and symbols are replaced with space).
   - Apply Kurdish typography mapping.
   - Convert whitespace/dashes `[\s-]+/u` to `'_'`.
   - Collapse multiple underscores `/_+/` to `'_'` and `trim($value, '_')`.
   - Clamp to 255 chars.
3. Fix `updateNews()` leaf category guard in `lib/McpNewsTools.class.php:981–983`:
   Validate whenever category_id is set:
   ```php
   if (isset($args['category_id']) || $lang !== (string) $model->lang) {
       $this->assertSelectableNewsCategory($categoryId, $lang);
   }
   ```
4. Fix all 22 `tools/i18n_guard.py` errors:
   - In `tools/i18n_guard.py`: add `"eq"`, `"neq"`, `"gt"`, `"gte"`, `"lt"`, `"lte"`, `"jaccard"`, `"intra"` to `DEV_TERMS`.
   - In `lib/McpNewsTools.class.php`: replace `$cand` with `$candidate`, `$candName` with `$candidateName`, `$candSeo` with `$candidateSeo`, `$candTokens` with `$candidateTokens` (use word-boundary replacement so `$candidates` is NOT affected).
   - In `tasks/mcp_smoke.php`: replace `$resDup` with `$resDuplicate`, `$dupPayload` with `$duplicatePayload`.
   - In `lib/McpFaqTools.class.php:511`: rephrase comment `intra-batch` to `within-batch`.
5. In `tasks/mcp_smoke.php`:
   Add test for `batch_create_faqs` with `replace_existing_drafts: true` while an independent draft FAQ exists for another entity, asserting that independent draft FAQs are preserved.

Verification:
- Run `php -l` on all modified PHP files.
- Run `python3 tools/i18n_guard.py` (must output `0 Errors, 0 Warnings`, exit 0).
- Run `python3 tools/code_separation_guard.py`.
- Run `python3 tools/module_boundary_guard.py`.
- Run `docker exec bulletin_app php tasks/mcp_smoke.php`.
- Run `docker exec bulletin_app php tasks/challenger_stress_test.php`.
- Write your full report to `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_worker_m1_2/handoff.md`.
- Send message to parent when done.
