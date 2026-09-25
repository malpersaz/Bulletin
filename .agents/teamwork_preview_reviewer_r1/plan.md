# Reviewer Plan (Round 1)

## Steps
1. **Independent Requirement Derivation & Static Analysis**:
   - Verify `Run::execute($a, $b, $ext = NULL)` signature and implementation.
   - Verify `Validator::add($data)` and `Validator::execute($class, $method, $data)`.
   - Identify edge case weaknesses in argument parsing, scalar arguments, and error handling.
2. **Adversarial Break & Defect Identification**:
   - Test `Run::execute` with scalar strings, integers, associative arrays, and non-existent class strings.
   - Test legacy validator callers across `mod/` (`->valid->ekle` vs `->valid->add`).
   - Audit `mod/mod_acl/index.php`, `mod_gallery_mod`, `mod_headline`, `mod_popup`, `mod_survey` for legacy validator loaders.
   - Audit forbidden commercial module isolation.
3. **Targeted Remediation**:
   - Fix `Run::execute` to handle scalar values, empty strings, and associative array unpacking cleanly.
   - Fix `RunException` to handle string class names without `TypeError` in `get_class()`.
   - Add `__callStatic('calistir')` in `Run` and `__call('ekle')` in `Validator` for seamless dynamic backward compatibility.
   - Migrate 28 legacy validator files from `->valid->ekle()` to `->valid->add()` with English parameter keys (`func`, `message`).
   - Clean up obsolete duplicate Turkish validator files (`ekle.validator.php`, `duzenle.validator.php`).
   - Modernize controller validator loader calls to `add`/`edit`.
   - Update `Load::validatorfile` with automatic fallback resolution.
4. **Re-Verification**:
   - Run custom adversarial test suite `php tests/run_and_validator_adversarial_test.php`.
   - Run full E2E test suite `php tests/e2e/run.php` (236/236 passed, 100%).
   - Run `i18n_guard.py` on touched core files (100% clean).
