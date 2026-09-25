# Implementation & Review Plan - Round 3

## Phase 1: Core Class Modernization
- Update `lib/Registry.class.php` to replace explicit Turkish method definitions (`getir`, `ekle`, `varmi`, `sil`, `yenile`) with clean `__callStatic` fallback, eliminating `i18n_guard.py` non-English function violations.
- Verify `lib/Run.class.php` and `lib/Validator.class.php` are fully modernized with zero Turkish identifiers.

## Phase 2: Complete `Kayit::` Purge Across Modules
- Replace all legacy `Kayit::` calls across all 27 files with canonical `Registry::` methods:
  1. `mod/index/helper/mod_hero_slider.helper.php` (`Kayit::getir` -> `Registry::get`)
  2. `mod/index/helper/mod_menu.helper.php` (`Kayit::getir` -> `Registry::get`)
  3. `mod/index/index.php` (`Kayit::getir` -> `Registry::get`)
  4. `mod/index/indexBase.php` (`Kayit::getir` -> `Registry::get`)
  5. `mod/index/indexMain.php` (`Kayit::getir` -> `Registry::get`, clean comments & identifiers)
  6. `mod/lang/index.php` (`Kayit::getir` -> `Registry::get`, `Kayit::yenile` -> `Registry::set`)
  7. `mod/mod_acl/index.php` (`Kayit::getir` -> `Registry::get`)
  8. `mod/mod_attachment/index.php` (`Kayit::ekle` -> `Registry::add`)
  9. `mod/mod_category/index.php` (`Kayit::getir` -> `Registry::get`)
  10. `mod/mod_document/index.php` (`Kayit::getir` -> `Registry::get`)
  11. `mod/mod_gallery/index.php` (`Kayit::ekle` -> `Registry::add`)
  12. `mod/mod_gallery_mod/index.php` (`Kayit::getir` -> `Registry::get`)
  13. `mod/mod_headline/index.php` (`Kayit::getir` -> `Registry::get`)
  14. `mod/mod_imagecategories/index.php` (`Kayit::getir` -> `Registry::get`)
  15. `mod/mod_menu/index.php` (`Kayit::getir` -> `Registry::get`)
  16. `mod/mod_news_category/index.php` (`Kayit::getir` -> `Registry::get`)
  17. `mod/mod_popup/index.php` (`Kayit::getir` -> `Registry::get`)
  18. `mod/mod_publication_category/index.php` (`Kayit::getir` -> `Registry::get`)
  19. `mod/mod_settings/index.php` (`Kayit::getir` -> `Registry::get`)
  20. `mod/mod_slider/index.php` (`Kayit::getir` -> `Registry::get`)
  21. `mod/mod_survey/index.php` (`Kayit::getir` -> `Registry::get`)
  22. `mod/mod_user/index.php` (`Kayit::getir` -> `Registry::get`)
  23. `mod/mod_user/model/user.model.php` (`Kayit::getir` -> `Registry::get`)
  24. `mod/mod_user_group/index.php` (`Kayit::getir` -> `Registry::get`)
  25. `mod/mod_user_pm/index.php` (`Kayit::getir` -> `Registry::get`)
  26. `mod/mod_video/index.php` (`Kayit::ekle` -> `Registry::add`)
  27. `mod/mod_video_embed_category/index.php` (`Kayit::getir` -> `Registry::get`)

## Phase 3: Adversarial Tests & Edge Case Hardening
- Expand adversarial tests in `tests/run_and_validator_adversarial_test.php` or dedicated suites to test `Registry` methods, `__callStatic` fallback, `indexBase` instantiation, and `Amed` bootstrapping.
- Verify zero syntax errors with `php -l`.
- Verify zero residual `Kayit::` calls across all files.
- Verify `i18n_guard.py` on touched core files.

## Phase 4: Full E2E & Final Verification
- Run `php tests/e2e/run.php` (must be 100% pass rate, 236/236).
- Run `php tests/run_and_validator_adversarial_test.php`.
- Send final report to parent via `send_message`.
