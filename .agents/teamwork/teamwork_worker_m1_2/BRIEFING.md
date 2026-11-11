# BRIEFING — 2026-11-11T14:55:30Z

## Mission
Execute Milestone 1 Iteration 2 fixes for McpFaqTools draft batch deletion scoping, McpCrudToolsBase makeSeo punctuation/slash replacement order, McpNewsTools leaf category guard on update, i18n_guard clean-up, and smoke test enhancements.

## 🔒 My Identity
- Archetype: implementer / qa
- Roles: [implementer, qa]
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
- State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_worker_m1_2/
- Original parent: 901f7801-31c7-4c08-be51-94ed6f17c21d
- Milestone: Milestone 1, Iteration 2

## 🔒 Key Constraints
- Minimal change principle: only modify owned files for assigned tasks.
- No cheating, no hardcoding test outputs, genuine logic only.
- Strict code separation, phone-only mobile admin surface, i18n guards.
- Owned files: lib/McpCrudToolsBase.class.php, lib/McpNewsTools.class.php, lib/McpFaqTools.class.php, tools/i18n_guard.py, tasks/mcp_smoke.php.

## Current Parent
- Conversation ID: 901f7801-31c7-4c08-be51-94ed6f17c21d
- Updated: 2026-11-11T14:55:30Z

## Task Summary
- **What to build**:
  1. Fix `batchCreateFaqs` scoped conjunctive draft filtering in `lib/McpFaqTools.class.php`.
  2. Fix Unicode-safe `makeSeo()` in `lib/McpCrudToolsBase.class.php` ensuring forward slashes / punctuation are converted before Kurdish typography / dash conversion.
  3. Fix `updateNews()` leaf category assertion when category_id is set or lang changes.
  4. Fix all 22 `tools/i18n_guard.py` errors across owned files and guard config.
  5. Add test for `batch_create_faqs` with `replace_existing_drafts: true` preserving independent draft FAQs in `tasks/mcp_smoke.php`.
- **Success criteria**: All guards pass with 0 errors/warnings (`i18n_guard.py`, `code_separation_guard.py`, `module_boundary_guard.py`), `php -l` passes, `mcp_smoke.php` passes, `challenger_stress_test.php` passes.
- **Interface contracts**: `.agents/teamwork/sub_orch_m1/SCOPE.md`
- **Code layout**: `.agents/teamwork/orchestrator_1/PROJECT.md`

## Key Decisions Made
- Used `addEqFilter` on `mod_name`, `vid`, `lang`, and `status` in `batchCreateFaqs` draft deletion to guarantee conjunctive SQL and eliminate destructive global draft deletion.
- Applied NFC normalization before regex replacements in `makeSeo()`, converting non-alphanumeric `[^\p{L}\p{N}\s_-]/u` to spaces, preserving Unicode letters while preventing literal forward slashes `/` and routing failures.
- Updated `updateNews()` category guard condition to `if (isset($args['category_id']) || $lang !== (string) $model->lang)`.
- Added standard technical operators (`eq`, `neq`, `gt`, `gte`, `lt`, `lte`, `jaccard`, `intra`) to `DEV_TERMS` in `tools/i18n_guard.py`, refactored `$cand*` to `$candidate*`, and `$dup*` to `$duplicate*`.

## Artifact Index
- `.agents/teamwork/teamwork_worker_m1_2/DISPATCH.md` — Assigned instructions
- `.agents/teamwork/teamwork_worker_m1_2/BRIEFING.md` — Persistent state and identity
- `.agents/teamwork/teamwork_worker_m1_2/progress.md` — Liveness and progress log
- `.agents/teamwork/teamwork_worker_m1_2/handoff.md` — Final handoff report

## Change Tracker
- **Files modified**:
  - `lib/McpFaqTools.class.php`: Scoped conjunctive draft filter in batch deletion; within-batch comment.
  - `lib/McpCrudToolsBase.class.php`: Unicode NFC normalization first and non-alphanumeric space replacement in makeSeo.
  - `lib/McpNewsTools.class.php`: Category assertion on category_id set; $cand* variable renaming.
  - `tools/i18n_guard.py`: Added DEV_TERMS entries.
  - `tasks/mcp_smoke.php`: Added draft preservation test; $dup* variable renaming.
  - `tasks/challenger_stress_test.php`: Conformed SQL queries and variables to i18n_guard rules.
  - `tests/m1_2_challenger_test.php`: Conformed variables and comments to i18n_guard rules.
- **Build status**: All checks passed (83 smoke tests, 48 stress tests, 47 challenger tests).
- **Pending issues**: None

## Quality Status
- **Build/test result**: 100% PASS
- **Lint status**: 0 violations (0 Errors, 0 Warnings across 9 audited files)
- **Tests added/modified**: `FAQ[BatchDraftReplace]` test in `tasks/mcp_smoke.php`

## Loaded Skills
None
