# BRIEFING: teamwork_preview_reviewer (Round 1)

## Context
Victory Auditor 4 previously identified 30 lingering occurrences of deprecated commercial modules (`mod_client`, `mod_service`, `mod_product`, `mod_reference`) across `mod/`.
Implementer 1 claimed to have purged these across `mod/index/index.php`, `mod/mod_user/index.php`, `mod/mod_seo/index.php`, `mod/mod_slider/index.php`, `mod/index/helper/mod_hero_slider.helper.php`, and `mod/management/index.php`, and wired `StaticAuditor::checkDeprecatedModuleReferences()` into E2E tests.

## Adversarial Review Checklist
1. Verify 0 occurrences of `mod_client`, `mod_service`, `mod_product`, `mod_reference` in `mod/`, `templates/`, `lib/`, `inc/`, `installer/`, etc.
2. Check if any routes or handlers broke, or if any other files still mention deprecated modules or routes (`product`, `service`, `client`, `reference`).
3. Check `tests/e2e/suites/Tier0QualityContractsTest.php` and `tests/e2e/suites/Tier1FeatureCoverageTest.php` to verify test logic is not bypassed, mocked away, or weakened.
4. Check template files and language files for any orphaned references or broken translation keys.
5. Run full test suite:
   - `php tests/e2e/run.php`
   - `php tests/twig_template_adversarial_test.php`
   - `php tests/m1_adversarial_challenge_test.php`
   - `php tests/m2_adversarial_challenge_test.php`
   - `php tests/m2_toggle_permission_adversarial_simulator.php`
   - `php tests/registry_adversarial_stress_test.php`
   - `php tests/run_and_validator_adversarial_test.php`
   - `python3 tests/m2_css_touch_target_auditor.py`
   - `php tests/module_management_runtime_test.php`
   - `php -l` on all PHP files
6. Check for regressions or unhandled edge cases.
