## 2026-08-23T21:59:48Z
Resolve the fatal error `Call to undefined method TwigTemplate::veri()` in AF/Bulletin by modernizing and replacing all legacy `->veri()` method calls across all modules (`mod/mod_user/index.php`, `mod/index/helper/search.helper.php`, `mod/mod_acl/index.php`, `mod/lang/index.php`, `mod/mod_attachment/index.php`, etc.) with canonical English `->set(...)` method on `TwigTemplate` / `AdminTemplate`.

Requirements:
1. Modernize and purge all legacy `->veri()` method calls across all modules replacing them with canonical English `->set(...)` method on `TwigTemplate` / `AdminTemplate`.
2. Update `lib/TwigTemplate.class.php` to include backward-compatibility forwarding (`veri` -> `set`) via `public function veri($key, $value) { return $this->set($key, $value); }` or `__call` so any edge cases never crash.
3. Eliminate non-English identifiers (`veri`, `tema` properties where applicable) matching AF/Business standard (which purged `veri` completely).
4. Strictly respect repository boundaries: keep out-of-scope modules (`mod_client`, `mod_service`, `mod_product`, `mod_reference`) absent from AF/Bulletin.
5. Re-run all E2E test suites (`php tests/e2e/run.php`) and adversarial checks to ensure 100% pass rate.
