# Original User Request

## 2026-08-23T19:19:17Z

Synchronize AF/Rojname with the latest modernization standards and module state from AF/Business by renaming/modernizing mod_content and mod_content_category, porting and syncing mod_seo, mod_faq, mod_newsletter, and mod_settings, and completely removing all remnants of mod_client, mod_service, mod_product, and mod_reference modules across the repository.

Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Rojname
Integrity mode: development

## Reference Repositories & Paths
- Source / Reference: `/home/malpersaz/Desktop/projects/htdocs/AF/Business`
- Target Workspace: `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname`

## Requirements

### R1. Update and Modernize Content Module Naming & Structure
- Align `mod_content` and `mod_category` naming with the latest AF/Business standard (`mod_content` and `mod_content_category`).
- Ensure all controllers, models, validators, language definitions, and admin template bindings for content and category management conform to the modernized naming and standards.
- Update routes, database queries, and permission keys from deprecated names to the unified structure.

### R2. Port and Sync Core Modules from AF/Business
- **mod_seo**: Port/sync `mod_seo` controllers, models, validators, language files, and templates from `AF/Business` into `AF/Rojname`.
- **mod_faq & mod_faq_mod**: Port/sync `mod_faq` and `mod_faq_mod` from `AF/Business` into `AF/Rojname` (including models, sql schemas, installer configs, admin templates, and frontend helpers).
- **mod_newsletter**: Port/sync `mod_newsletter` from `AF/Business` into `AF/Rojname` with full mobile-first admin panel UI, models, and language files.
- **mod_settings**: Sync and modernize `mod_settings` from `AF/Business` to ensure complete configuration support, proper language definitions, and clean separation of concerns.

### R3. Thorough Cleanup of Deprecated Business Modules in AF/Rojname
- Completely remove all code, files, templates, assets, installer scripts, helper functions, and database schemas associated with:
  1. `mod_client`
  2. `mod_service` & `mod_service_category`
  3. `mod_product` & `mod_product_category`
  4. `mod_reference` & `mod_reference_category`
- Clean up references to these deleted modules in:
  - `installer/app/InstallerModuleCatalog.php` and `installer/lang/*.php`
  - `lib/ModuleRegistry.class.php` and `mod/management/index.php`
  - `mod/index/helper/*` (e.g. `search.helper.php`, slider helpers, etc.)
  - Admin templates (`templates/admin/partials/sidebar.twig`, `templates/admin/assets/js/`, `templates/admin/mod_user/panel.twig`)
  - Any ACL permissions or navigation hooks.

### R4. Architectural & Repository Policy Compliance
- Comply strictly with repository rules:
  - No inline CSS or JavaScript in PHP or Twig files (Strict Code Separation).
  - All codebase comments and identifiers must be in English.
  - Admin UI must follow the Phone-Only Mobile First contract (`templates/admin/`).
  - Use `Registry` class methods instead of legacy storage keys.
  - Zero syntax errors or broken references across all affected files.

## Acceptance Criteria

### Module Porting & Naming Parity
- [ ] `mod_content` and `mod_content_category` match the latest architecture of `AF/Business`.
- [ ] `mod_seo`, `mod_faq` / `mod_faq_mod`, `mod_newsletter`, and `mod_settings` are fully ported/synced and load without PHP errors or missing dependencies.
- [ ] Language files for all ported/renamed modules exist and are complete in `en.php` and other supported languages.

### Deprecated Module Cleanliness
- [ ] Grep searches for `mod_client`, `mod_service`, `mod_product`, and `mod_reference` return zero active references in `mod/`, `installer/`, `lib/`, `templates/admin/`, and helper scripts.
- [ ] Installer catalog and ModuleRegistry do not register or require any of the removed modules.
- [ ] Sidebar and admin navigation render cleanly without broken links or orphaned icons.

### Verification & Linting
- [ ] PHP syntax check (`php -l`) passes with zero errors on all new, modified, or ported files.
- [ ] No inline scripts or style tags exist within newly added/modified Twig templates.

## 2026-08-23T20:14:39Z

Modernize and synchronize the AF/Rojname codebase with the latest architectural, code quality, and security standards from AF/Business (such as AJAX permission management, strict code separation, universal share toolbar, Unicode SEO filtering, base controller relation endpoints, ORM resilience, and 9-language parity) while strictly ensuring excluded commercial modules remain completely absent from the news repository.

Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Rojname
Integrity mode: development

## Boundaries & Strict Exclusions
The following modules and ANY references, templates, routes, or installer fixtures related to them MUST NOT exist or be introduced into AF/Rojname:
1. `mod_client`
2. `mod_service` & `mod_service_category`
3. `mod_product` & `mod_product_category`
4. `mod_reference` & `mod_reference_category`

## Requirements

### R1. Strict Code Separation & Admin User ACL Modernization
- Refactor `templates/admin/mod_user/acl.twig` and `templates/admin/mod_acl/` (`add.twig`, `edit.twig`, `list.twig`) by removing all inline `<style>`, inline `style="..."`, and inline `<script>` tags.
- Move styles and scripts into external assets (`templates/admin/assets/css/mod_user_acl.css`, `mod_user_acl.js`, `mod_acl.css`, `mod_acl.js`).
- Implement the asynchronous `toggle_permission()` AJAX endpoint in `mod/mod_user/index.php` with database transaction support and instant session synchronization (`_loadPermissions()`), removing legacy form submission wrappers and manual save buttons.
- Modernize `mod_acl` using the `AclManager` class with debounced live search and mobile touch targets (min 44x44px), purging `script.twig` and legacy Turkish references (`processSil`, `processDosya`).
- Decouple inline styles and inline event handlers from `templates/admin/partials/header.twig`, `sheets_extra.twig`, and `filter_sheet.twig`, routing all interactions through `data-action` delegated handlers in `modal_user_menu.js` and consolidated CSS in `core_modals.css`.

### R2. Core Framework, ORM & Twig SEO Unicode Preservation
- Standardize `legacySeoFilter` in `lib/TwigLibs.class.php` to delegate to `Helper::legacy_seo($s)`, strictly preserving Unicode letters (`\p{L}\p{N}`) and casing (Wikipedia-style URLs) without corrupting Kurdish characters into underscores.
- Add case-insensitive column resolution to `lib/Orm/Orm.php` within `Orm::set()` to eliminate schema casing mismatch errors on SQLite and MySQL.
- Implement standard AJAX relation helper methods (`ajax_get_files()`, `ajax_get_images()`, `ajax_update_relation()`) in `lib/BaseModuleController.class.php`.

### R3. Universal Social Share & Document Export Toolbar
- Implement the reusable frontend component `templates/partials/share_toolbar.twig` along with `static/css/share.css` and `static/js/share.js`.
- Provide zero-inline `data-action="print"`, Native Web Share API, social intents (WhatsApp, X, Facebook, LinkedIn, TikTok with clipboard copy fallback), and document export actions (HTML, Text, ZIP).
- Integrate the share toolbar across active detail templates (`mod_news`, `mod_publication`, `mod_content`, `mod_page`, `mod_announcements`).

### R4. Corporate Legal & Tax Info (E-E-A-T) and Cookie Policy
- Extend `mod_settings` and `mod_settings_model` with corporate and fiscal fields (`legal_company_name`, `tax_office`, `tax_number`, `trade_registry_number`, `central_registration_number`, `registered_email`, etc.).
- Add multilingual `Legal Information` and `Cookie Policy` page fixtures across all 9 languages in `installer/mod_page` and link them in footer and cookie consent partials (`_legal_info.twig`, `_cookie_consent.twig`).
- Normalize the display name of Sorani Kurdish to `سۆرانی` across database fixtures, installer, and language registries.

### R5. Brand Placeholder Fallback & Disk Asset Verification
- Implement the `Logo -> Symbol -> Radial Gradient Pattern` fallback hierarchy across all frontend helper queries and detail views.
- Ensure backend helpers verify `file_exists()` and `is_file()` for image records before passing paths to Twig templates.
- Integrate runtime client-side error interception in frontend scripts (`app.js`, slider scripts) to seamlessly swap broken image elements with crisp brand placeholders.

### R6. Multilingual 9-Language Parity & Code Language Standards
- Populate missing language dictionaries (`ar.php`, `fr.php`, `sv.php`) in `mod_user_pm`, `mod_video_embed`, `mod_acl`, and `mod_seo`.
- Standardize role permission keys and eliminate legacy non-English identifiers or comments across touched modules.
- Ensure Kurdish UI action verbs follow correct grammar (`add => 'Lêke'`).

## Acceptance Criteria

### Automated & Quality Guard Verification
- [ ] `python3 tools/code_separation_guard.py` passes with 0 violations across all admin and frontend templates.
- [ ] `python3 tools/i18n_guard.py` passes with 0 violations across all touched files.
- [ ] `find . -name "*.php" ! -path "./vendor/*" -exec php -l {} +` reports 0 syntax errors.
- [ ] All JavaScript files pass `node --check` syntax validation.
- [ ] No references to excluded modules (`mod_client`, `mod_service`, `mod_product`, `mod_reference`) exist in active routes, sitemaps, or templates.
- [ ] Kurdish characters in `legacy_seo` (e.g., `Çavdêrî`, `Şîrove`) generate clean Wikipedia-style slugs without corruption into `_`.

## 2026-08-23T21:11:04Z

This is a single self-contained fix; keep it small and focused.

Fix the fatal error `Call to undefined method Run::execute()` in `AF/Rojname` by modernizing the `Run` class and aligning core execution patterns with `AF/Business`, ensuring zero Turkish identifiers/methods remain in touched core files while respecting strict repository boundaries (no `mod_client`, `mod_service`, `mod_product`, `mod_reference` or related code).

Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Rojname
Integrity mode: development

## Requirements

### R1. Fix Run Class & Execution Dispatch
- Update `lib/Run.class.php` in `AF/Rojname` to define `public static function execute($a, $b, $ext = NULL)` instead of legacy Turkish `calistir`.
- Ensure all calls across the codebase use `Run::execute()` cleanly without errors.
- Modernize any remaining Turkish method names in related touched validator/core helpers (e.g. `Validator::execute` vs `calistir`, `ekle` vs `add`) ensuring full backward/forward compatibility and English standard compliance.

### R2. Strict Repository Boundary Isolation
- Do NOT import, migrate, or reference any out-of-scope modules from `AF/Business`: specifically `mod_client`, `mod_service`, `mod_service_category`, `mod_product`, `mod_product_category`, `mod_reference`, `mod_reference_category`.
- Keep `AF/Rojname` cleanly isolated as a news/publishing CMS.

### R3. Quality and Test Suite Verification
- Verify that running `php tests/e2e/run.php` passes with 100% success rate (236+ tests).
- Ensure no PHP syntax errors, no undefined methods, and zero regressions in dispatching requests via `Amed.class.php`.

## Acceptance Criteria

### Execution & Core Functionality
- [ ] `lib/Run.class.php` provides `Run::execute()` matching AF/Business signature.
- [ ] Invoking `Amed->__destruct()` or running web requests executes cleanly without `Call to undefined method Run::execute()`.
- [ ] No Turkish identifiers (`calistir`, `ekle`, etc.) in touched core library files (`lib/Run.class.php`, `lib/Validator.class.php`).

### Boundaries & Guardrails
- [ ] Zero presence or referencing of `mod_client`, `mod_service`, `mod_product`, `mod_reference` in `AF/Rojname`.
- [ ] Full e2e test suite (`php tests/e2e/run.php`) passes with 0 failures.

## 2026-08-23T21:24:32Z

Additional issue reported by user:
Fatal Error: Class 'Kayit' not found in mod/index/index.php(9): indexBase->__construct() -> lib/Load.class.php(290) -> lib/Amed.class.php(547): Load::mod('index') -> Amed->__destruct()

Please ensure all legacy `Kayit::` calls (such as `Kayit::getir`, `Kayit::ekle`, `Kayit::yenile`) across all modules (mod/index, mod/lang, mod/mod_category, mod/mod_settings, mod/mod_user, mod/mod_attachment, mod/mod_gallery, mod/mod_video, mod/mod_imagecategories, etc.) are purged and modernized to canonical `Registry` methods (`Registry::get`, `Registry::add`, `Registry::set`), matching AF/Business standards, while respecting repository boundaries (no out-of-scope modules). Verify that all tests pass cleanly.

## 2026-08-23T21:41:00Z

New issue reported by user:
Error: Call to undefined method TwigTemplate::veri()
#0 /var/www/html/lib/Run.class.php(30): mod_user_mod->register()
#1 /var/www/html/lib/Amed.class.php(565): Run::execute(Object(mod_user_mod), 'register', NULL)
#2 [internal function]: Amed->__destruct()
#3 {main}

Requirements:
1. Modernize and purge all legacy `->veri()` method calls across all modules (including `mod/mod_user/index.php`, `mod/index/helper/search.helper.php`, `mod/mod_acl/index.php`, `mod/lang/index.php`, `mod/mod_attachment/index.php`, etc.) replacing them with the canonical English `->set(...)` method on `TwigTemplate` / `AdminTemplate`.
2. Update `lib/TwigTemplate.class.php` to include backward-compatibility forwarding (`veri` -> `set`) via `public function veri($key, $value) { return $this->set($key, $value); }` or `__call` so any edge cases never crash.
3. Eliminate non-English identifiers (`veri`, `tema` properties where applicable) matching AF/Business standard (which purged `veri` completely).
4. Strictly respect repository boundaries: keep out-of-scope modules (`mod_client`, `mod_service`, `mod_product`, `mod_reference`) absent from AF/Rojname.
5. Re-run all E2E test suites and verification checks to ensure 100% pass rate.
