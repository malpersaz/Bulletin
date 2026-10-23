# Amed Framework — Bulletin CMS Version History

## v1.0.2 (2026-10-23)
- **`mod_news` End-to-End Modernization, 100% English Codebase Purification & Multilingual i18n Synchronization (`mod/mod_news/`, `installer/mod_news/`, `config/url.config.php`, `mod/index/`, `templates/admin/`, `templates/amida/`)**:
  - **Backend Controller, Installer, Models, Schema & Validators (`mod/mod_news/`, `installer/mod_news/`, `config/url.config.php`)**:
    - Upgraded `mod/mod_news/index.php` (`mod_news_mod`) with `toggleFlag()`, `toggle_hero_slider()`, `toggle_deck()`, `toggle_status()`, `deck` column projection in `datatable_json()` (`$columns`), standardized `$this->jsonResponse()` payloads in `ajax_check_title()` and `ajax_ai_assist()`, and localized `ai.invalid_action` handling.
    - Synchronized `mod/mod_news/mod_news.sql`, `installer/mod_news/{index.php,mod_news.sql,newses.sql}`, `config/url.config.php`, `mod/mod_news/model/mod_news.model.php`, `mod/mod_news/model/newses.model.php`, and `mod/mod_news/validator/{add,edit}.validator.php` (enforcing active language validation, root-category prohibition, category-language parity, and 90–120 character deck bounds), and removed dead legacy `mod/mod_news/model/{datatable,flexigrid}.model.php`.
    - Synchronized all 11 `mod/mod_news/lang/*.php` dictionaries (`ar`, `ckb`, `de`, `en`, `fa`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`), completing native German (`de.php`) translations and adding `deck_*` and `ai.invalid_action` keys.
  - **Phone-Only Admin Views, Wizard, Image Uploader & Floating 9-Button Contextual Toolbar (`templates/admin/`)**:
    - Modernized `templates/admin/mod_news/{list,add,edit}.twig`, `templates/admin/assets/js/mod_news.js`, `templates/admin/assets/css/mod_news.css`, `templates/admin/layout.twig`, and `templates/admin/assets/js/script.js` with the 9-button floating contextual action bar (`Edit`, `Gallery`, `File`, `Video`, `FAQ`, `Status`, `Slider`, `Deck`, `Delete`), AI assistant triggers, and zero `|default(...)` fallbacks or inline `<script>` / `style="..."` violations.
    - Eliminated inline `onclick` / `onchange` / `style` attributes and foreign `lang.mod_content` references in `templates/admin/partials/image_uploader.twig` and `templates/admin/partials/wizard_content.twig`, wiring `data-action` delegates in `templates/admin/assets/js/script.js`.
  - **Public Frontend Helper, Archive Route & Broadsheet Templates (`mod/index/`, `templates/amida/`)**:
    - Purified `mod/index/helper/mod_news.helper.php`, `mod/index/index.php`, and `mod/index/helper/index.php` to 100% English identifiers (`$this->theme`, `_permission_check`, `archive()`, `archive_years`, `min_year`, `max_year`, `year`, `month`, `not_found`).
    - Replaced legacy `templates/amida/index/mod_news/arsiv.twig` with `templates/amida/index/mod_news/archive.twig`, removed unreferenced prototype `category_sections.twig`, synchronized `news.twig`, `newses.twig`, `latest_news.twig`, `category_news_row.twig`, and `templates/amida/static/css/news.css`, and normalized `$lang['index']['mod_news']` across all 9 `mod/index/lang/*.php` files to pure descriptive English keys with zero `??` / `|default(...)` fallbacks.
- **Repository Governance, Documentation Parity & Domain Alignment (`docs/`, `config/site.config.php`, `config/site.config.example.php`)**:
  - Restored and adapted core governance, quality-agent, and architectural strategy documents (`CODE_QUALITY_AGENT_STRATEGY.md`, `PILOT_AGENT_REVIEW_FLOW.md`, `CROSS_REPO_SYNC_POLICY.md`, `NEW_THEME_CREATION_GUIDE.md`, `MEDIA_LIBRARY_STRATEGY.md`, `SYSTEM_LEGAL_POLICY_PAGES_PLAN.md`, `CURATOR_LANGUAGE_PARITY_AND_NEWS_PIPELINE_PLAN.md`) to match `AF/Bulletin` domain boundaries (`mod_news`, `mod_headline`, `mod_publication`, `amida` public theme, and full multilingual i18n standards).
  - Updated `docs/MOD_NEWS_MODERNIZATION_PLAN.md` and advanced framework version baseline to `1.0.2`.

## v1.0.1 (2026-10-21)
- **PHP 8.5 Deep Modernization & Runtime Compliance (`^8.4 || ^8.5`)**:
  - **Composer Platform & Automated Refactoring Infrastructure (`composer.json`, `composer.lock`, `rector.php`)**:
    - Configured Composer platform constraint `"php": "^8.4 || ^8.5"` and synchronized `composer.lock` and `vendor/composer/platform_check.php` to support PHP 8.5 CLI/production and PHP 8.4/8.5 Docker FPM environments.
    - Added standardized Composer scripts (`test:runtime`, `lint:syntax`, `refactor:dry`) and root `rector.php` configuration (Rector 2.6.7) targeting PHP 8.4/8.5 sets and strict type declarations.
  - **Elimination of PHP 8.2–8.5 Compile-Time & Runtime Deprecations**:
    - Removed deprecated `curl_close()` calls (PHP 8.5 RFC) across `lib/AIService.class.php`, `mod/mod_user/index.php`, `mod/mod_seo/index.php`, and `mod/mod_video_embed/index.php`.
    - Replaced deprecated `imagedestroy()` calls (PHP 8.5 RFC) with `unset()` / `null` assignment across `mod/mod_settings/index.php`, `lib/BrandAssetGenerator.class.php`, and `lib/AmedResize.class.php`.
    - Removed deprecated `ReflectionProperty::setAccessible()` call (PHP 8.5 RFC) in `tests/module_management_runtime_test.php`.
    - Added explicit nullable parameter types (`?Type $param = null`) across 13 classes in `lib/swift/`.
    - Replaced deprecated `E_USER_ERROR` usage in `index.php` and `lib/swift/classes/Swift/Transport/EsmtpTransport.php` (`BadMethodCallException`).
    - Added explicit `$escape = ''` argument to `fputcsv()` calls in `mod/mod_newsletter/index.php`.
    - Added `#[\AllowDynamicProperties]` to `BaseModuleController` (`lib/BaseModuleController.class.php`) and modernized `ModuleRuntime::checkModuleFilesExist()` (`lib/ModuleRuntime.class.php`) using PHP 8.4+ `array_any()`.

- **Active Threat & CVE Hardening (`php2shell` & 2026 PHP Security Mitigations)**:
  - **Zero-Day Memory-Limit & Upload Progress Hardening (`CVE-2026-109618` & `CVE-2026-109619`)**:
    - Enforced `session.upload_progress.enabled = Off`, `fatal_error_backtraces = Off`, and `expose_php = Off` in `docker/php/php.dev.ini` to neutralize the `php2shell` exploitation chain (`longjmp()` memory-limit corruption and stale global pointer Use-After-Free).
    - Verified host Ubuntu security update `8.5.4-0ubuntu1.5` (`resolute-security`) backporting fixes for `CVE-2026-109618`, `CVE-2026-109619`, `CVE-2026-92842`, `CVE-2026-91768`, and `CVE-2026-91769`.
  - **Database Session Handler & Fingerprint Hardening (`lib/session/Session.db.php`, `lib/Session.class.php`)**:
    - Modernized `Session_Db` to formally implement `\SessionHandlerInterface` (`open`, `close`, `read`, `write`, `destroy`, `gc`) with strict return types.
    - Eliminated raw string interpolation SQL injection risks in `Session_Db` by routing all queries through `Database::quote()`, `Database::db_insert()`, `Database::make_delete()`, and `Database::affected_rows()`.
    - Replaced `@session_start()` error suppression with explicit `session_status() === PHP_SESSION_NONE && !headers_sent()` checks and upgraded session user-agent fingerprinting from `md5()` to `hash('sha256', ...)`.
  - **Centralized cURL Transport & Protocol Pinning (`lib/HttpClient.class.php`, `lib/AIService.class.php`, `mod/mod_user/index.php`, `mod/mod_seo/index.php`, `mod/mod_video_embed/index.php`)**:
    - Introduced `HttpClient` (`postJson`, `postForm`) enforcing TLS peer/host verification (`CURLOPT_SSL_VERIFYPEER`, `CURLOPT_SSL_VERIFYHOST`), scheme validation (`^https?://`), and protocol pinning (`CURLOPT_PROTOCOLS` and `CURLOPT_REDIR_PROTOCOLS` restricted to `CURLPROTO_HTTP | CURLPROTO_HTTPS`) to mitigate `CVE-2026-91769` and SSRF redirect attacks.
    - Consolidated outbound Gemini API and IndexNow HTTP requests through `HttpClient` and eliminated duplicated cURL logic in `mod_user_mod::_call_gemini_api()`.
  - **Deserialization Gadget Chain (POP Chain) & Legacy Code Removal (`lib/Cache.class.php`, `plugins/htmleditor/tiny_mce/`, `lib/Yaml/`)**:
    - Hardened `unserialize()` in `Cache::get()` with `['allowed_classes' => false]` to block PHP Object Injection / POP gadget chains, and added `Cache::getCacheDir()` / `Cache::setCacheDir()` accessors.
    - Removed unused legacy `plugins/htmleditor/tiny_mce/` (containing exposed `phpinfo()`, `eval()`, and PHP 4/5 constructs) and unused legacy Symfony 1 `lib/Yaml/` (`lib/Yaml.class.php`) in favor of `symfony/yaml` v7.2.
  - **Docker Runtime & Web Server Execution Isolation (`docker/php/Dockerfile`, `docker/nginx/*.conf`)**:
    - Updated `docker/php/Dockerfile` base image to `FROM php:8.5-fpm` (removing redundant `opcache` build step, as OPcache is built into PHP 8.5 core).
    - Added Nginx execution-denial rules (`location ~* ^/(static|cache|data|templates_c)/.*\.php$ { deny all; }`) across `docker/nginx/default.conf`, `docker/nginx/dev.conf`, and `docker/nginx/prod.conf`.

- **Top Menu "Modern Press" Editorial Polish, Brand Identity & Quality Guards**:
  - **Modern Press Header & Navigation Architecture (`templates/amida/index/mod_menu/stacked_top_menu.twig`, `templates/amida/static/css/stacked-top-menu.css`, `templates/amida/static/js/top-menu.js`, `docs/top-menu-modern-press/`)**:
    - Delivered the 2-row "Modern Press" editorial masthead with optical vertical alignment, press-column hairlines, bottom active indicators, tactile search controls (`/` shortcut and clear button), synchronized sticky-scroll compaction (`40px` to `36px`), `:focus-visible` accessibility rings, and `prefers-reduced-motion` support.
  - **Bulletin CMS Identity, Localization & Editorial Pipeline (`config/`, `installer/`, `mod/`, `.agents/skills/`)**:
    - Configured `Bulletin CMS` branding and Turkish (`tr`) default language baseline across `config/site.config.php`, `config/site.config.example.php`, installer seeds, and language dictionaries.
    - Added the `kevseo` Google News, Discover, and Semantic HTML SEO skill (`.agents/skills/kevseo/SKILL.md`) and aligned editorial skills (`kevanger`, `kevkolin`, `kevrast`).
  - **English-Only Codebase & Test Suite Alignment (`tests/twig_template_adversarial_test.php`, `tools/i18n_guard.py`)**:
    - Aligned `BaseModuleController` assertions in `tests/twig_template_adversarial_test.php` with Rule 7 (English-only properties `$theme` and `$view`) and `Cache::getCacheDir()`.

## v1.0.0 (2026-10-07)
- **Initial Bulletin CMS Baseline Release**:
  - Established standalone `AF/Bulletin` journalism and newsroom repository baseline from Amed Framework core (`mod_news`, `mod_news_category`, `mod_headline`, `mod_publication`, `mod_publication_category`, `mod_video`, `mod_video_embed`, `mod_announcements`, `mod_survey`, `mod_page`, `mod_seo`, `mod_settings`, and `amida` public theme).
