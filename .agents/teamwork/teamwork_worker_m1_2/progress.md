# Progress — Worker M1-2

- Last visited: 2026-11-11T14:55:30Z
- Status: Completed all 5 remediation tasks and independent verifications

## Completed Tasks
1. [DONE] Task 1: Fixed `batchCreateFaqs` in `lib/McpFaqTools.class.php:579–584` with scoped conjunctive filters via `addEqFilter` on `mod_name`, `vid`, `lang`, and `status`.
2. [DONE] Task 2: Fixed `makeSeo()` in `lib/McpCrudToolsBase.class.php:742–777` with Unicode-safe order of operations (NFC normalization, orphan dot stripping, punctuation stripping, non-alphanumeric replacement with spaces to eliminate slashes, Kurdish typo mapping, whitespace-to-underscore conversion, underscore collapsing and trimming, 255 char clamp).
3. [DONE] Task 3: Fixed `updateNews()` in `lib/McpNewsTools.class.php:981–983` by asserting leaf category when `isset($args['category_id']) || $lang !== (string) $model->lang`.
4. [DONE] Task 4: Fixed all 22 `tools/i18n_guard.py` errors across `tools/i18n_guard.py`, `lib/McpNewsTools.class.php`, `tasks/mcp_smoke.php`, and `lib/McpFaqTools.class.php`. Also remediated adversarial challenge test scripts to achieve repository-wide 0 errors, 0 warnings.
5. [DONE] Task 5: Added independent draft FAQ preservation test for `batch_create_faqs` with `replace_existing_drafts: true` in `tasks/mcp_smoke.php`.

## Verification Results
- `php -l`: All modified PHP files syntax valid.
- `python3 tools/i18n_guard.py`: 0 Errors, 0 Warnings (Exit code 0).
- `python3 tools/code_separation_guard.py`: 0 violations across all scanned files (Exit code 0).
- `python3 tools/module_boundary_guard.py`: 0 violations across 43 scanned files (Exit code 0).
- `docker exec bulletin_app php tasks/mcp_smoke.php`: 83 passed, 0 failed.
- `docker exec bulletin_app php tasks/challenger_stress_test.php`: 48 passed, 0 failed.
- `docker exec bulletin_app php tests/m1_2_challenger_test.php`: 47 passed, 0 failed.
