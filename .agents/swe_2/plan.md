# Plan: TwigTemplate::veri Modernization & Refactoring

## Objectives
1. Modernize and replace all legacy `->veri(...)` calls with `->set(...)` across all modules in AF/Rojname.
2. Update `lib/TwigTemplate.class.php` to include backward compatibility forwarding (`veri` -> `set`).
3. Eliminate non-English identifiers matching AF/Business standard.
4. Ensure out-of-scope modules remain absent.
5. Verify test suite pass rate (`php tests/e2e/run.php` and unit/e2e tests).

## Execution Sequence (SWE Light)
1. **Round 0 (Implementation)**: Dispatch `teamwork_preview_implementer` to analyze, implement the modernization across all modules and `lib/TwigTemplate.class.php`, and verify tests.
2. **Round 1 (Reviewer 1)**: Dispatch `teamwork_preview_reviewer` to find edge cases, remaining `veri` usages, breaking regressions, and fix them.
3. **Round 2 (Reviewer 2)**: Dispatch `teamwork_preview_reviewer` to check for non-English identifiers, backward compatibility, and test suite completeness.
4. **Round 3 (Reviewer 3)**: Dispatch `teamwork_preview_reviewer` to ensure final polish and verify all test suites.
5. **Round 4 (Victory Auditor)**: Dispatch `teamwork_preview_victory_auditor` for independent verification.
