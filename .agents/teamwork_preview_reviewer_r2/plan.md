# Adversarial Reviewer Round 2 Plan

## 1. Independent Requirements Derivation
- Modernize `Run::execute()` and `lib/Run.class.php`.
- Zero Turkish identifiers in touched core files (`lib/Run.class.php`, `lib/Validator.class.php`, `lib/Load.class.php`, and all module validators).
- Maintain 100% backward compatibility for legacy callers.
- Ensure strict repository boundary isolation.
- Verify 100% pass rate in the E2E test suite (236 tests).

## 2. Adversarial Probing & Defect Discovery
- Probe scalar arguments, associative arrays, nulls, booleans, objects in `Run::execute()`.
- Probe dynamic property set/get behavior on all 64 validator classes (`$validator->hatalar` and `$validator->errors`).
- Audit `i18n_guard.py` across all touched files.
- Audit `/ekle` and `/duzenle` references in templates and controllers.

## 3. Implementation Hardening
- Fix magic method `__set` on validator files to support dynamic properties and explicit `public $errors = []`.
- Fix Turkish comments in validator files.
- Fix `/ekle` routes in admin templates (`panel.twig`, `fab.twig`, `wizard_video_embed.twig`, `script.js`).
- Fix template rendering in `mod_acl/index.php`.

## 4. Empirical Verification
- Re-run all 236 E2E tests (`php tests/e2e/run.php`).
- Run expanded 43-assertion adversarial suite (`php tests/run_and_validator_adversarial_test.php`).
- Run isolated PHP runtime validation across all 64 validator files.
- Run `i18n_guard.py` across all touched files.
