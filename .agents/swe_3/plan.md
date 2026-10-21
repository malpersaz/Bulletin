# Plan — AF/Bulletin Commercial Module Purge & StaticAuditor Integration

## Objective
Purge all remaining references, dead routes, sitemaps, helper calls, and admin actions to `mod_client`, `mod_service`, `mod_product`, and `mod_reference` across `mod/`, integrate `StaticAuditor::checkDeprecatedModuleReferences()` into the E2E test suite, and run SWE Light refinement loop with verification.

## Steps
1. **Dispatch Implementer** (`teamwork_preview_implementer`):
   - Clean up `mod/index/index.php`, `mod/mod_user/index.php`, `mod/mod_seo/index.php`, `mod/mod_slider/index.php`, `mod/index/helper/mod_hero_slider.helper.php`, `mod/management/index.php`.
   - Wire `StaticAuditor::checkDeprecatedModuleReferences()` into test suite (e.g. `Tier0QualityContractsTest.php` or `Tier1FeatureCoverageTest.php`).
   - Run tests and verify zero matches in `mod/`.
2. **Dispatch Reviewer Round 1** (`teamwork_preview_reviewer`):
   - Adversarial check for missed references, route regressions, or broken helper calls.
3. **Dispatch Reviewer Round 2** (`teamwork_preview_reviewer`):
   - Deep inspection and edge-case verification.
4. **Dispatch Reviewer Round 3** (`teamwork_preview_reviewer`):
   - Regression and test coverage audit.
5. **Dispatch Victory Auditor** (`teamwork_preview_victory_auditor`):
   - Independent verification across timeline, integrity, and test execution.
6. **Final Synthesis & Reporting**:
   - Report results back to parent orchestrator.
