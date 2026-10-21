# Project: AF/Bulletin (AKM Bulletin CMS) Modernization

## Architecture
- **Framework**: Amed Framework / CMS (PHP 8+, SQLite/MySQL, Twig 3 templates)
- **Repository Separation**: Strictly isolated news repository (`AF/Bulletin`). Zero inclusion of commercial business modules (`mod_client`, `mod_service`, `mod_product`, `mod_reference`).
- **Admin UI**: Phone-only mobile-first responsive surface (`templates/admin/`), touch targets ≥ 44x44px, zero inline CSS/JS.
- **Frontend UI**: Default theme (`templates/amida/`), responsive desktop/mobile, brand fallback hierarchy (`Logo -> Symbol -> Radial Gradient Pattern`), zero inline CSS/JS.
- **Data Flow & Relations**: ORM-backed models, centralized BaseModuleController AJAX relation helpers (`ajax_get_files()`, `ajax_get_images()`, `ajax_get_current_media()`, `ajax_update_relation()`).

## Feature Inventory
| # | Feature | Description | Milestone | Source |
|---|---------|-------------|-----------|--------|
| 1 | Twig Unicode SEO Filter | `TwigLibs::legacySeoFilter` delegates to `Helper::legacy_seo` preserving Kurdish `\p{L}\p{N}` without corrupting characters to underscores | M1 | Survey / R2 |
| 2 | ORM Case-Insensitive Columns | Case-insensitive column resolution in `Orm.php` and `Obuilder.php` with English error exceptions | M1 | Survey / R2 |
| 3 | BaseController Relation Helpers | Centralized `ajax_get_files()`, `ajax_get_images()`, `ajax_get_current_media()`, `ajax_update_relation()` in `BaseModuleController` | M1 | Survey / R2 |
| 4 | Admin User ACL Modernization | Zero-inline `templates/admin/mod_user/acl.twig`, external `mod_user_acl.css`/`mod_user_acl.js`, mobile carousel & live search | M2 | Survey / R1 |
| 5 | Asynchronous Permission Toggle | `toggle_permission()` AJAX endpoint in `mod/mod_user/index.php` with DB transactions & `_loadPermissions()` session sync | M2 | Survey / R1 |
| 6 | Admin ACL Modernization & AclManager | Purge `script.twig`, modernize `mod_acl` templates with `mod_acl.css`/`mod_acl.js` (`AclManager`), purge `processSil`/`processDosya` | M2 | Survey / R1 |
| 7 | Admin Partials Code Separation | Decouple inline styles/handlers from `header.twig`, `filter_sheet.twig`, `sheets_extra.twig` to `modal_user_menu.js` & `core_modals.css` | M2 | Survey / R1 |
| 8 | Reusable Share Toolbar Component | `templates/amida/partials/share_toolbar.twig`, `static/css/share.css`, `static/js/share.js` with `data-action="print"` & TikTok | M3 | Survey / R3 |
| 9 | Compact Share Partial Modernization | Zero-inline `templates/amida/partials/share.twig`, external CSS/JS, clipboard confirmation tooltip | M3 | Survey / R3 |
| 10 | Detail Views Share Toolbar Integration | Replace legacy toolbars in `mod_news`, `mod_publication`, `mod_page`, `mod_announcements`, `mod_imagecategories` | M3 | Survey / R3 |
| 11 | Document Export & Tools Cleanliness | Modernize `mod/tools/index.php` (`_check_permission`, clean `$output`), add dictionary keys in `mod/index/lang/` across 9 languages | M3 | Survey / R3 |
| 12 | Corporate & Fiscal Settings Schema | 16 corporate fields added to `installer/mod_settings/mod_settings.sql` and `mod/mod_settings/mod_settings.sql` | M4 | Survey / R4 |
| 13 | Multilingual Legal & Cookie Page Fixtures | 9 Legal Information + 9 Cookie Policy page seeds in `installer/mod_page/index.php` | M4 | Survey / R4 |
| 14 | E-E-A-T Corporate Frontend Integration | `templates/amida/partials/_legal_info.twig`, dynamic cookie policy slug in `_cookie_consent.twig`, footer links in `footer.twig` | M4 | Survey / R4 |
| 15 | Sorani Kurdish Display Normalization | Standardize display name to `سۆرانی` across `installer/lang/index.php`, `InstallerLang.php`, and `installer/lang/*.php` | M4 | Survey / R4 |
| 16 | Brand Placeholder Fallback Hierarchy | `Logo -> Symbol -> Radial Gradient Pattern` in detail views and hero slider; `detail-foundation.css` / `hero-news-slider.css` | M5 | Survey / R5 |
| 17 | Backend Disk Asset Verification | `file_exists()` & `is_file()` checks in `mod/index/helper/` (`mod_news`, `mod_publication`, `mod_announcements`, `mod_page`, `mod_hero_slider`) | M5 | Survey / R5 |
| 18 | Client-Side Error Interception | Runtime broken image interception in `templates/amida/static/js/app.js` and `hero-news-slider.js` | M5 | Survey / R5 |
| 19 | 9-Language Module Dictionaries | Add missing `ar.php`, `ckb.php`, `fr.php`, `sv.php` in `mod_user_pm`, `mod_video_embed`, `mod_acl` | M6 | Survey / R6 |
| 20 | Codebase Language & Grammar Parity | Purge legacy Turkish keys (`vazgec`, `hata`, `processSil`, etc.), enforce Kurdish `add => 'Lêke'`, `nûve` -> `nûjenkirin` | M6 | Survey / R6 |
| 21 | Excluded Module Cleanliness | Purge lingering polymorphic seeds in `installer/mod_faq_mod/index.php` (`mod_product`, `mod_service`, `mod_project`) | M6 | Survey / Exclusions |
| 22 | E2E Regression & Quality Suite | Automated multi-tier verification (syntax, code separation, i18n, Unicode URLs, mobile layout, exclusions) | E2E | Testing Track |

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| M1 | Core Framework, ORM & Unicode Parity | `lib/TwigLibs.class.php`, `lib/Orm/`, `lib/BaseModuleController.class.php` | none | DONE |
| M2 | Admin User ACL & Code Separation | `mod_user/acl.twig`, `mod_acl/`, `mod_user/index.php`, `header.twig`, `filter_sheet.twig` | M1 | DONE |
| M3 | Universal Share Toolbar & Export | `share_toolbar.twig`, `share.css`, `share.js`, detail views, `mod/tools/index.php`, `mod/index/lang/` | none | DONE |
| M4 | Corporate Legal (E-E-A-T) & Cookie Policy | `mod_settings` SQL, `installer/mod_page`, `_legal_info.twig`, `_cookie_consent.twig`, Sorani `سۆرانی` | none | DONE |
| M5 | Brand Placeholder & Disk Verification | `detail-foundation.css`, detail views, `hero_slider`, helpers disk check, `app.js`, `hero-news-slider.js` | none | DONE |
| M6 | Multilingual Parity, Standards & Exclusions | Missing dictionaries (`mod_user_pm`, `mod_video_embed`, `mod_acl`), Kurdish verbs, `mod_faq_mod` cleanup | M4 | DONE |
| E2E | E2E Testing Track & Final Hardening | Multi-tier test suite (Tiers 1-4) + adversarial hardening (Tier 5) | M1, M2, M3, M4, M5, M6 | DONE |

## Verification Results
- `php tests/e2e/run.php`: **236/236 Tests Passed (100.00%, 506 assertions)**
- `python3 tools/code_separation_guard.py`: **0 violations**
- `python3 tools/i18n_guard.py`: **0 violations**
- `php -l`: **0 syntax errors**
- Excluded modules check: **0 occurrences of mod_client, mod_service, mod_product, mod_reference**
