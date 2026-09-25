# BRIEFING - Teamwork Preview Reviewer Round 3

## Role & Mission
- Role: `teamwork_preview_reviewer` (Round 3)
- Target Workspace: `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname`
- Mission: Modernize `Run` and `Validator` classes, purge all legacy `Kayit::` invocations across all modules (`mod/index`, `mod/lang`, `mod/mod_category`, `mod/mod_settings`, `mod/mod_user`, `mod/mod_attachment`, `mod/mod_gallery`, `mod/mod_video`, `mod/mod_imagecategories`, etc.) replacing them with canonical `Registry` calls (`Registry::get`, `Registry::add`, `Registry::set`), ensure zero Turkish identifiers in touched core files (`lib/Run.class.php`, `lib/Validator.class.php`, `lib/Registry.class.php`), maintain strict module boundary isolation, and achieve 100% test pass rate across all suites.

## Identified Issues & Required Actions
1. **Fatal Error: Class 'Kayit' not found**:
   - `Kayit::` calls remain in 27 files across `mod/` and `mod/index/helper/`.
   - `mod/index/indexBase.php` fails on instantiation when accessing `Kayit::getir('site_config')`.
   - Action: Replace all `Kayit::getir`, `Kayit::ekle`, `Kayit::yenile` with canonical `Registry::get`, `Registry::add`, `Registry::set`.
2. **`lib/Registry.class.php` Direct Turkish Methods**:
   - `Registry.class.php` has explicit `public static function getir`, `ekle`, `varmi`, `sil`, `yenile` triggering `i18n_guard.py` non-English function errors.
   - Action: Transition backward compatibility to `__callStatic` matching `Run.class.php` and `AF/Business`.
3. **Turkish Comments & Identifiers in Touched Files**:
   - `mod/index/indexMain.php` contains `$secenek` and Turkish comments.
   - Action: Clean and standardize touched files.
4. **Boundary Isolation & Quality Verification**:
   - Confirm zero active dependency on excluded modules (`mod_client`, `mod_service`, `mod_product`, `mod_reference`).
   - Run full E2E test suite (236+ tests) and adversarial suites.
