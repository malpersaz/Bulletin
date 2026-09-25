# Adversarial Reviewer Round 2 Progress

- [x] Uncovered Defect 1: `mod_slider/validator/edit.validator.php` (and other validator files) omitted `public $errors = []`, causing `__set('errors', ...)` to swallow assignments and drop error arrays.
  - **Fix**: Added explicit `public $errors = []` and robust `__get`/`__set` dynamic property handling across all 64 validator classes.
- [x] Uncovered Defect 2: Non-English variables (`public $hatalar = []`) and Turkish comments remained in 13+ module validator files (`mod_news_category`, `mod_announcements`, `mod_category`, `mod_user_pm`, etc.), failing `i18n_guard.py`.
  - **Fix**: Removed `public $hatalar = []`, replaced with `$errors = []` + `__get('hatalar')`/`__set('hatalar')`, and translated comments.
- [x] Uncovered Defect 3: `mod/mod_acl/index.php` called `$this->tema->tpl('mod_acl/ekle')` while the template file was renamed to `mod_acl/add.twig`.
  - **Fix**: Updated to `$this->tema->tpl('mod_acl/add')`.
- [x] Uncovered Defect 4: Legacy `/ekle` route paths remained in `templates/admin/mod_user/panel.twig`, `templates/admin/partials/fab.twig`, `templates/admin/partials/wizard_video_embed.twig`, and `templates/admin/assets/js/script.js`.
  - **Fix**: Modernized to `/add`.
- [x] Verified: All 64 validator files dynamically loaded and verified in isolated PHP runtimes with 100% pass rate.
- [x] Verified: 43/43 adversarial assertions passed in `tests/run_and_validator_adversarial_test.php`.
- [x] Verified: Full E2E suite (`php tests/e2e/run.php`) passed with 236/236 tests (100.00% pass rate, 506 assertions).
- [x] Verified: Zero Turkish identifiers in touched core files (`i18n_guard.py` clean across all 68 files).
