# Implementation Plan - Run & Validator Modernization

## Phase 1: Investigation & Comparison
- Inspect `lib/Run.class.php` and `lib/Validator.class.php` in `AF/Bulletin` and compare with `AF/Business`.
- Check all callers of `Run::` and `Validator::` across `AF/Bulletin`.
- Check for any leftover Turkish identifiers in core library files.

## Phase 2: Implementation
- Modernize `lib/Run.class.php`:
  - Change `calistir($a, $b, $ext = NULL)` to `execute($a, $b, $ext = NULL)`.
  - Update `import($mod, $method, $array = NULL)` to call `self::execute($mod, $method, $array)`.
- Modernize `lib/Validator.class.php`:
  - Change `calistir` to `execute($class, $method, $data)`.
  - Ensure `add($data)` is the clean English method with parameter compatibility.
  - Eliminate all Turkish identifiers (`calistir`, `ekle`, `veri`, `fonk`, `mesaj`).

## Phase 3: Verification & Boundary Check
- Check strict boundary isolation (confirm 0 references to `mod_client`, `mod_service`, `mod_product`, `mod_reference`).
- Run `php tests/e2e/run.php` to verify 100% pass rate.
- Run dedicated unit/integration tests verifying `Run::execute()` and `Validator::add()`/`Validator::check()`.
