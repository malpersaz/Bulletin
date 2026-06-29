## v1.0.77
- **Secure Password Requirements & Complexity Enforcements**: Upgraded password length requirements to min 8 and max 64 characters across password reset and register workflows. Added backend regex validations in `password_reset.validator.php` and `register.validator.php` to enforce uppercase, lowercase, numeric, and special character criteria. Added backend password mismatch check on registration.
- **Modern Password Input & Strength Checkers**: Modernized password reset and registration interfaces with visibility toggles, dynamic rules checklists, password strength meters, and real-time password match indicators.
- **Multilingual Localization Alignment**: Expanded translation coverage for the new secure password complexity error keys and validation rules across all 9 active languages (`ku`, `en`, `tr`, `de`, `fr`, `sv`, `zza`, `ar`, `ckb`) without using default fallbacks. Mapped complexity keys to register validation JSON within the controller.

## v1.0.76
- **Fix Database Query Builder Side-Effects**: Replaced raw `$db->query` followed by `$db->gRo()` with `$db->fetch_array($sql)` and `$db->getResultOne()` in `password_reset.validator.php`. This bypasses `$db->build()` query generation defaults which previously caused `SELECT * FROM ` syntax errors because builder properties were uninitialized for custom raw SQL strings.

## v1.0.75
- **Fix Undefined Database::escape() Call**: Replaced the undefined `$db->escape()` method call with PHP-native regex sanitization (`preg_replace('/[^A-Za-z0-9]/', '', ...)`) in `password_reset.validator.php`. This fixes the runtime PHP error on the password reset validation stage while keeping the database query secure against SQL injection.

## v1.0.74
- **Timezone-Safe Password Reset Link Expiration**: Replaced PHP-side timestamp age calculations (`strtotime` compared to local `time()`) with a MySQL-side timezone-safe check utilizing `TIMESTAMPDIFF` between the connection `NOW()` and `created_at` timestamp. This eliminates validation failures and premature link expiration warnings caused by timezone offsets between the PHP runtime environment and the MySQL server.

## v1.0.73
- **Forgot Password Success View Cleanup**: Removed the conditional email check info string and the back-to-login link from the success view in `forgot-password.twig`. This simplifies the success screen, leaving only the check inbox guidelines and the primary return homepage button.

## v1.0.72
- **Password Reset View APP-Style Layout**: Optimized the password reset page (`password_reset.twig`) for mobile devices by implementing responsive styles to remove margins, borders, and shadows on small viewports. This matches native mobile application shells (APP-style) for optimized screen space.
- **CAPTCHA Refresh & Layout Parity**: Redesigned the CAPTCHA section in `password_reset.twig` to use the same compact input group layout as the forgot password view. The CAPTCHA image and refresh button are unified within a single input-group addon block. Added a micro-animation that triggers a 360-degree spin on the refresh icon when clicked.

## v1.0.71
- **Forgot Password Success Layout & Redirect Redesign**: Removed the automatic redirect spinner and redirect timer from the forgot password success screen (`forgot-password.twig`) to prevent confusing users before they receive their password reset emails. Redesigned the success view into a clean, professional card with an email status check icon and clear user guidance. Added return actions to direct the user back to the Homepage or the Login Modal. Mapped missing translation keys (`back_to_homepage`, `check_inbox_instruction`) in English, Kurdish, and Turkish.

## v1.0.70
- **Forgot Password JS Safeness Fix**: Wrapped the client-side email input validation and check logic inside a conditional `if (emailInput)` check to prevent JavaScript TypeError on page load when the user is on the success redirect page. This ensures the 3-second auto-redirection timer executes successfully.
- **Password Reset View Modernization & Localization**: Redesigned the password reset page (`password_reset.twig`) using the modern Bootstrap 5 layout system to match `forgot-password.twig`. Fixed input names (`new_password`, `confirm_password`, `captcha`) to align with the backend validator and added a hidden input for the reset `code`. Created and mapped missing translation keys (`write_new_password`, `new_password`, `confirm_new_password`, `change_button`, `new_password_placeholder`, `confirm_password_placeholder`, `captcha_placeholder`, `captcha_refresh`) in English, Kurdish, and Turkish.
- **Password Reset Controller Captcha Wire-up**: Wired the `Captcha` library inside the controller's `password_reset` method to dynamically generate and supply the security code image (`captcha_image`) to the reset template.

## v1.0.69
- **Email Template Modernization & Localization**: Refactored the email templates (`email_password_reset.twig`, `email_activation.twig`, `email_new_password.twig`, and `email_admin_activation.twig`) to remove layout inheritance (`{% extends "layout.twig" %}`) and debug printing blocks (`{{ ctx_get(...) }}`). This eliminates the unintended printing of `"0"`, debug values, and HTML interface components (like navigation drawers or FABs) from the email bodies. Wired all template text to localized translation keys under the `email_templates` active dictionary to support multi-language mailing.

## v1.0.68
- **Password Reset Redirect Enablement**: Uncommented and restored the automatic page redirection logic inside the forgot password Twig template. Configured it to route the user back to the login page (`login`) via the correct registry site address (`site.saddress`) after 3 seconds upon a successful submission, preventing the loading spinner from running endlessly.

## v1.0.67
- **SwiftMailer Constructor Deprecation Fixes**: Resolved constructor deprecation warning in `Swift_Message` by replacing the `call_user_func_array` constructor reference with native `parent::__construct` and modern argument unpacking (`...`).
- **SwiftMailer StreamBuffer Property Warning Fix**: Declared the private `$_sequence` property in `Swift_Transport_StreamBuffer` to eliminate PHP 8.x dynamic property warnings and undefined property errors.
- **SwiftMailer AuthHandler Argument Casting Fix**: Cast the private `$_auth_mode` property to string in `Swift_Transport_Esmtp_AuthHandler` before passing it to `strtolower()` to prevent deprecation warnings when it is null.
- **SwiftMailer SendEvent Property Alignment**: Renamed the declared `result` property to `_result` in `Swift_Events_SendEvent` to match the instance usage and eliminate PHP 8.x dynamic property warnings.
- **Global Error Reporting Safeguard**: Excluded `E_DEPRECATED` from `error_reporting` in `index.php` to prevent minor legacy deprecation warnings in external libraries (like Twig and SwiftMailer) from being outputted and disrupting redirects or AJAX responses on the production site.

## v1.0.66
- **Template Engine Fetch Method Support**: Added a `fetch($tpl)` method to `TwigTemplate` to support rendering Twig templates into string variables without immediate direct output. This resolves the fatal `Call to undefined method TwigTemplate::fetch()` error encountered during user password reset notifications.
- **Twig Engine Post-Initialization Globals Safeguard**: Safeguarded variable assignment in `TwigTemplate::veri()`, `setGlobal()`, and `lang()` methods by storing variables in `assing_data` and catching Twig's `LogicException` thrown when trying to add globals after the environment is initialized. Passed `$this->assing_data` to all rendering pipelines (`fetch()`, `fetch_tpl()`) to ensure variable availability.
- **SwiftMailer SMTP Transport Constructor Fix**: Resolved PHP 8.x deprecated constructor warning in `Swift_SmtpTransport` by replacing the `call_user_func_array` constructor reference with native `parent::__construct` and modern argument unpacking (`...`).

## v1.0.65
- **Forgot Password Localization and Icon Fixes**: Localized missing language keys and resolved Turkish hard-strings within the forgot password Twig template. Unified all 9 language files (English, Kurdish, Turkish, German, French, Swedish, Zazaki, Arabic, and Sorani Kurdish) with the new `js` verification translation strings under `forgot_password`. Corrected mismatched variable calls for email labels, placeholders, CAPTCHA, and submit button texts. Replaced all FontAwesome icon classes (which are not loaded in the public template) with Bootstrap Icons (`bi` classes) to restore and display all form field and button icons. Integrated a real dynamic CAPTCHA image instead of hardcoded text, and implemented standard asynchronous (AJAX) CAPTCHA refreshing to reload only the security code without reloading the entire page, styled with active scale transitions.

## v1.0.64
- **Increase Password Column Length for Hashing Upgrades**: Expanded the `password` column size in `mod_user` and `mod_user_log` installer tables from `varchar(50)` to `varchar(255)` and migrated the active database tables. This resolves the MySQL truncation error during login, which occurs when the system attempts to upgrade legacy MD5 password hashes to modern PHP `PASSWORD_DEFAULT` (bcrypt) hashes exceeding 50 characters.
- **Modernization of mod_attachment and mod_attachment_mod Modules**: Fully modernized the `mod_attachment` and `mod_attachment_mod` modules to extend `BaseModuleController` and integrate with framework standards. Eliminated massive CRUD boilerplates in the `mod_attachment_mod` controller by delegating them to the base class, and enabled automatic DataTable JSON generation using `DataTableService`. Preserved all specialized features, routing contexts, and access control flows to maintain project compatibility and uniqueness.

## v1.0.63
- Active version bucket derived from the current user request.
- **FormData.entries Compatibility Fix**: Wrapped the `formData.entries()` loop inside `script.js` in a capability check to prevent runtime `TypeError` crashes on older browsers and devices that do not support this API, resolving the article publication hang issue.
- **Safe Language Checks in Add Templates**: Safe-guarded inline language checks in `mod_publication/add.twig`, `mod_content/add.twig`, and `mod_news/add.twig` to verify `adminLang` and `adminLang.languages` existence, preventing potential `TypeError` or `ReferenceError` crashes.

## v1.0.62
- Active version bucket derived from the current user request.
- **English-Only Rules for Version Log and Code**: Updated agent guidelines (`AGENTS.md`, `INDEX.md`, and `quality/manifest.md`) and the `task-validation` skill to enforce that `/docs/VERSION.md` is strictly updated in English. Added clear directives prohibiting Turkish or Kurdish in release notes, code comments, function names, variable names, translation keys, and overall coding elements.
- **Mandatory Task Validation**: Reinforced that the `task-validation` skill checklist must be run before completing any development task.

## v1.0.61
- Active version bucket derived from the current user request.
- **Translation of Release Notes**: Translated historical version notes (v1.0.57-v1.0.60) to English to conform to international documentation standards.

## v1.0.60
- Active version bucket derived from the current user request.
- **Modernization and Synchronization of Publication Modules (mod_publication)**: Completely modernized the `mod_publication` module in Rojname to extend `BaseModuleController`, while preserving the AJAX-based hero slider toggle feature (`toggle_hero_slider`) and mock data functionality. Merged the module's language files to include complete `image` keys for the drag-and-drop uploader.
- **Modernization of Publication Category Module (mod_publication_category)**: Synchronized `mod_publication_category` with Business, integrating robust validation logic (e.g. parent category language matching, cycle loop prevention, and menu sync). Modernized the admin category templates to use a recursive tree-based layout powered by SortableJS, and cleaned up all `|default` fallbacks by providing proper dictionary keys.

## v1.0.59
- Active version bucket derived from the current user request.
- **Modernization of mod_news Module in Rojname**: Completely modernized the `mod_news` module to extend `BaseModuleController` and cleaned up all CRUD boilerplates. Preserved custom menu integrations (`$menu_url_pattern`) and slider controls. Updated the Kurdish localization keys according to the project guidelines and corrected admin templates to filter active languages.

## v1.0.58
- Active version bucket derived from the current user request.
- **Synchronization of Core Config Changes and Related Files**: Synchronized 100% of core configuration changes (e.g., config key changes: 'adres' -> 'address', 'sadres' -> 'saddress', 'radres' -> 'iaddress', 'tadres' -> 'taddress') from Business to Rojname. Added logo and symbol columns to the Rojname MySQL database.

## v1.0.57
- Active version bucket derived from the current user request.
- **Synchronization and Cleanup of mod_news_category**: Synchronized the news category module (`mod_news_category`) and its installer between the `Rojname` and `Business` projects.
- **Rojname Installer Modernization**: Cleaned up the extra property definition `public $model;` and unnecessary comments from the `Rojname` installer script. Added the category seed bootstrap function (`installer_seed_category_bootstrap`) to seed default categories automatically.
- **Removal of Boundary Restrictions in Rojname**: Removed boundary restrictions for `mod_news_category` and seed restrictions for the `Rojname` installer in `AGENTS.md` to keep both projects synchronized.
- **Translation and Cleanup of Turkish Comments in Business**: Cleaned up Turkish comments in `Business` validator files (`add.validator.php` and `edit.validator.php`) and translated them to English to align with the clean code standards in Rojname.

## v1.0.56
- Active version bucket derived from the current user request.
- **Nûjenkirin û Xweserkirina Pergala Agentan**: Rêbername, sîyaset, manifest, û skillên agentan (li bin `.agents/theme`, `.agents/design`, û `.agents/skills`) li gorî asta nû ya Business CMS hatin nûjenkirin û bi tevahî ji bo taybetmendiyên portalê nûçe û weşanê yên `Rojname` hatin xweserkirin (bêyî tu nav an modula `product`).
- **Siyasetên Templates û Sêwiranê yên Nû**: Pelên `templates/AGENTS.md` û `templates/admin/AGENTS.md` yên nû hatin afirandin, û rêgezên visual design contract û sêwirana mobîlê (phone-only admin surface) bi rêkûpêk hatin pênasekirin.
- **News Category Module Modernization**: Modernized the admin news category module (`mod_news_category`) to use a recursive tree-based card view. Integrated SortableJS for drag-and-drop reordering, direct homepage visibility toggling, and recursive subcategory deletion with SweetAlert2 confirmation prompts.
- **Hierarchical Loop and Language Validation**: Enhanced the category controller to prevent cycle loops (descendant cannot become parent of ancestor) and enforce that child categories belong to the same language tree as their parents.
- **Menu and Attachment Auto-Synchronization**: Reworked category creation/updating to automatically sync changes to menu slots (`mod_menu`) and ensure cascade recursive deletion of attachments, menu entries, and gallery records.
- **Video Module Modernization and Sister Project Sync**: Modernized the admin video panel (`mod_video`) to sync with the phone-first Material Design 3 card-based layout from Business CMS. Replaced the legacy flexigrid and Flash-based uploader with standard HTML5 video inputs, real-time AJAX XMLHttpRequest upload progress bars, and SweetAlert2-based confirmation dialogues.
- **Controller-Level Field Standardizations**: Standardized the backend database and ORM properties to use `duration` consistently instead of the legacy mismatched `sure`.
- **Safe Dynamic Video Conversion**: Upgraded the backend `cevir` endpoint to dynamically detect server-side `ffmpeg` and `ffprobe` binaries, converting uploaded videos to web-ready mp4 files, with a safe dynamic fallback to accept the original format if the FFMPEG utility is missing on the host.
- **Multilingual Localization and Comment Cleanup**: Ported complete localization coverage for English, Kurdish, Turkish, and Zazaki language files without any raw fallbacks. Cleaned up Turkish and Kurdish comments from validator and template files, replacing them with English inline documentation.

## v1.0.55
- Active version bucket derived from the current user-provided upload version code.
- **Admin User Panel Modernization and Synchronization**: Modernized the admin user dashboard (`mod_user/panel`) to sync with the phone-first Material Design 3 layout of the AKM Business CMS twin project. Replaced the legacy grid layout with modern MD3 cards, added a primary navigation header with context links, and implemented an interactive bottom sheet ("Zêdetir") for system tools.
- **Dynamic Module Pinning and Navigation History**: Integrated client-side state management using local storage to allow administrators to pin/unpin specific CMS modules and maintain a history of recently accessed workspace modules in JavaScript background state for parity with the Business CMS shared framework logic.
- **Multilingual Support and Kurdish Localization**: Ensured full multilingual coverage across all 9 active languages (`ku`, `en`, `tr`, `de`, `fr`, `zza`, `ckb`, `ar`, `sv`) for all new UI strings, completely avoiding raw fallback or hardcoded text. Adhered to Kurdish grammatical conventions, prioritizing "nûjenkirin" over "nûve" for update operations.
- **Controller-Level Localization Safety Assertions**: Added strict validation inside `mod_user` controller (`_assert_panel_language_keys()`) that ensures all required localization keys exist in the active dictionary, triggering a clear error response if any key is missing rather than rendering broken templates.
- **Minimalist Dashboard Color Styling**: Refined the dashboard user interface with a custom minimalist pastel palette for all active workspace cards, transitioned the bottom floating action button (FAB) to a soft, minimalist pastel Terracotta/Salmon theme, styled all speed dial mini FAB buttons with individual minimalist pastel themes (Slate, Sage Green, Lavender, Amber), and restructured the "Zêdetir" navigation bar with a glassmorphic Indigo/Lavender theme to eliminate bright gradients while preserving a colorful yet clean aesthetic.
- **Global Navigation Drawer Modernization**: Refactored the global admin navigation drawer/sidebar (`templates/admin/partials/sidebar.twig`) and its related styling in `style.css` to adopt standard Material Design 3 floating pill-shape menu items and accordion groups, synchronizing the visual presentation, spacing, hover effects, and active state styles with the twin Business CMS project. Integrated a real-time module filter search bar (`filterDrawerNav`), a drawer close button, and dynamic empty search states with complete multilingual support across all 9 languages.

## v1.0.54.13
- Active version bucket derived from the next upload after Git-published `v1.0.54.12`.
- **MySQL Database Configuration for Docker**: Updated the local database configuration in `config/db.config.local.php` to point to the MySQL `db` service container inside Docker, setting adapter to `PDO_MYSQL` with credentials matched to the Docker environment.
- **Installer Auto-Redirect on Uninstalled Database**: Added an automatic installation state check on the homepage index to detect incomplete database configurations (e.g. empty SQLite database file or missing core tables) and redirect visitors to the installer panel instead of throwing raw database exceptions when loading Amed core libraries.
- **Professional Paste Standards Implementation**: Hardened TinyMCE editor configurations across the general editor, page wizard, and modular edit flows. This includes disabling the problematic `smart_paste` logic and the `link_context_toolbar` badge that triggered unwanted link dialogs in Microsoft Edge, while removing the `autolink` plugin and enforcing a strict "Clean Paste" policy that strips foreign colors, fonts, and background styles from clipboard content to preserve the CMS's premium typography and design consistency.

## v1.0.54.12
- Active version bucket derived from the next upload after Git-published `v1.0.54.11`.
- **Admin User Edit AJAX Submit Flag Fix**: Added the missing `edit` submit field to the AJAX payload for the admin user edit form so the backend enters the real update branch and returns structured JSON instead of rendering the full HTML page as an unexpected response.



## v1.0.54.11
- Active version bucket derived from the next upload after Git-published `v1.0.54.10`.
- **Admin User Edit Error-Key Fix**: Added the missing top-level `mod_user.error` username validation keys used by the admin `mod_user/list` edit flow so localized update errors resolve through the correct runtime branch instead of falling through to raw `Undefined array key` warnings.
- **Admin User Edit AJAX Error Surface**: Upgraded the phone-first admin user edit form to submit through AJAX with structured JSON responses, a copyable error panel, and safe fallback handling for unexpected server output so operators no longer need screenshots to report validation or runtime failures.

## v1.0.54.10
- Active version bucket derived from the current user-provided upload version code.
- **Profile Update Username Validation Fix**: Added the missing `username_length_error` localization key across all active `mod_user` language files so self-profile and admin edit validators no longer trigger raw `Undefined array key` warnings when a username falls outside the allowed edit length.

## v1.0.54.9
- Active version bucket derived from the current user-provided upload version code.
- **Installer User Social Profiles**: Added a dedicated `mod_user_social` installer table and runtime model so each user can store platform-specific social links independently from the main `mod_user` record without future schema churn.
- **User Admin Social Fields**: Extended the phone-first `mod_user` add, edit, and self-info admin flows with a reusable social-media input block that saves per-user links for X, Facebook, Instagram, LinkedIn, YouTube, Spotify, and TikTok.
- **Author Page Social Wiring**: Updated the publication author flow to load user social links from `mod_user_social` and render all active author platforms on the public author page instead of relying on missing hard-coded `twitter` / `facebook` fields.
- **Installer SQLite Path Fallback Hardening**: Reworked the installer's SQLite target selection so it still prefers `../storage/sqlite/` when that external directory can be prepared, but now automatically falls back to the protected in-project `data/sqlite/` area when sibling storage is not writable, and the installer no longer emits raw `mkdir(): Permission denied` warnings during directory creation failures.
- **Installer SQLite Session Path Recovery**: Updated SQLite path normalization so an old installer session or posted form value that still carries `../storage/sqlite/...` now auto-recovers to the writable protected fallback path instead of failing with `Directory could not be created: /var/www/storage/sqlite`.

## v1.0.54.8
- Active version bucket derived from the latest Git history; latest uploaded commit is `10bd86c` with version `v1.0.54.8`.

## v1.0.54.5
- Active version bucket derived from the current user request.
- **Author Share Meta Data**: Updated `author.twig` and `authors.twig` in `mod_publication` to explicitly override base metadata tags, utilizing context-aware user profile / featured images for better Open Graph and Twitter Share previews.
- **Author Share Planning**: Documented the full strategy for social metadata handling in `docs/AUTHOR_SHARE_META_PLAN.md`.
- **View Log Query-State Fix**: Corrected `_update_view_count()` in `mod/index/indexBase.php` and `mod/index/indexMain.php` to evaluate `sys_view_logs` existence through `fetch_array()` instead of stale shared `Database` object state, restoring view-log inserts on detail pages such as `publication`, `news`, `page`, and `video_embed`.
- **View Log Real-IP Hardening**: Updated detail-page view logging to prefer a public client IP from trusted proxy-style headers only when `REMOTE_ADDR` is not already a public address, reducing undercounting on proxied hosting while preserving the direct-host behavior.
- **SQLite Storage Hardening**: Moved the installer's SQLite default out of the web root to `../storage/sqlite/`, introduced `config/db.config.local.php` as the environment-owned database config target, added `data/.htaccess` as a defense-in-depth block for legacy in-root database files, and ignored local runtime DB artifacts from Git.

## v1.0.54.3
- Active version bucket derived from the latest Git history; latest uploaded commit is `e97291a` with version `v1.0.54.3`.
- **I18n Bootstrap Demotion**: Refactored [`config/i18n.config.php`](/home/malpersaz/Desktop/projects/htdocs/Rojname/config/i18n.config.php) so it no longer publishes a canonical `valid_lang` runtime list and now serves only as fallback bootstrap state through `i18n_bootstrap`.
- **Runtime Language Registry Keys**: Updated [`lib/Amed.class.php`](/home/malpersaz/Desktop/projects/htdocs/Rojname/lib/Amed.class.php) to publish runtime language state through `active_langs`, `default_lang`, and `site.available_languages`, while keeping database-backed language rows authoritative whenever the registry is reachable.
- **Admin Language State Consistency**: Updated [`mod/lang/index.php`](/home/malpersaz/Desktop/projects/htdocs/Rojname/mod/lang/index.php) so language-management actions refresh the new runtime keys without overwriting the current interface language, preventing the language panel from collapsing current-language state into the default-language state.
- **Default-Language Consumer Cleanup**: Reworked active consumers in [`mod/mod_settings/index.php`](/home/malpersaz/Desktop/projects/htdocs/Rojname/mod/mod_settings/index.php) and [`mod/index/index.php`](/home/malpersaz/Desktop/projects/htdocs/Rojname/mod/index/index.php) to stop deriving defaults from the first active language and instead use the explicit runtime `default_lang` plus `active_langs` registry keys.

## v1.0.54.1
- Active version bucket derived from the latest Git history and reserved for upcoming development notes until a newer Git upload version appears or the user explicitly assigns another version code.
- Git history re-verified against the current repository state; latest uploaded commit is `8badf3b` with version `v1.0.54.1`.
- **Installer SQLite Support Implementation**: Introduced full SQLite support for the phone-first installer, including a new database-configuration step, automatic `.sqlite` database creation in `data/`, and a MySQL-to-SQLite DDL transformer that enables module SQL files to run seamlessly on SQLite.
- **Cross-Database SQL Transformation**: Implemented `SqliteSqlTransformer` to bixweber translate MySQL-specific syntax (`AUTO_INCREMENT`, `ENUM`, `ENGINE`) into SQLite-compatible DDL during the module installation phase.
- **Installer Config Persistence**: Added `InstallerConfigWriter` to safely persist selected database credentials and adapters into `config/db.config.php`, ensuring a clean transition from installer state to application runtime.
- **Installer UI Enhancements**: Optimized the database setup panel for the phone-only admin shell, including dynamic selector-based toggles for database fields, native Kurdish/English localization without fallback text, and non-blocking connection validation.

## v1.0.54.0
- Active version bucket derived from the latest Git history; latest uploaded commit is `a34258e` with version `v1.0.54.0`.

## v1.0.53.56
- Active version bucket derived from the latest Git history; latest uploaded commit is `b3e4187` with version `v1.0.53.56`.

## v1.0.53.55
- Active version bucket derived from the latest Git history; latest uploaded commit is `3983277` with version `v1.0.53.55`.

## v1.0.53.54
- Active version bucket derived from the latest Git history and reserved for upcoming development notes until a newer Git upload version appears or the user explicitly assigns another version code.
- Git history re-verified against the current repository state; latest uploaded commit is `f75f3f9` with version `v1.0.53.54`.
- **Agent Policy Update For Installer Languages**: Removed the old repository-local agent instruction that excluded `tr` and `ar` from installer default languages so future agent work follows the expanded installer language scope.
- **Installer TR/FA/AR Localization Completion**: Added dedicated installer language payloads for `tr`, `fa`, and `ar`, extended the installer language registry to expose them in the phone-first flow, and repaired the project default language selector so the expanded language set renders real labels instead of array output.

## v1.0.53.52
- Active version bucket derived from the latest Git history and confirmed by the current task override.
- Git history re-verified against the current repository state; latest uploaded commit remains `76cae6d` with version `v1.0.53.52`.
- **Installer Module Selection Flow**: Extended the phone-first installer with a dedicated module-selection step, manifest-backed module grouping, dependency-aware auto-selection, required-versus-optional visual treatment, and persistent requested-versus-resolved module state.
- **Installer Execution State Screens**: Replaced the old single result screen with structured install progress cards, per-module runtime states, recoverable retry behavior, a post-install review step, and a distinct finish handoff screen for successful runs.
- **Installer Localization Coverage Expansion**: Updated the installer mobile language payload across `en`, `de`, `fr`, `ku`, `ckb`, `sv`, and `zza` so the expanded setup, module, progress, review, and finish flow ships without visible fallback text.
- **Installer Sample-State Reporting**: Added manifest-level sample-content modes plus module-by-module sample-status summaries in the sample, progress, and post-install review steps so legacy inline seed behavior and unsupported sample paths are now surfaced explicitly instead of being hidden behind a single global toggle.
- **Installer Safe Resume Flow**: Retry now resumes from previously completed modules instead of rerunning the whole selected set blindly, and the progress screen explicitly lists the carried-forward modules in every supported installer language.
- **Installer Project-Rule Seed Cleanup**: Added post-install enforcement for the `Rojname` rule that strips the default category and content seed records after installation, so legacy inline installer seeders no longer leave those defaults behind.
- **Installer Clean-Install Page Cleanup**: When sample content is disabled, the installer now removes legacy sample page records created by `mod_page`, while still preserving them when the user explicitly keeps sample content enabled.
- **Installer Recovery Messaging Hardening**: Replaced hard-coded failure-reason text with localized recovery reason keys and split non-recoverable failure exit behavior from the normal module-edit path.
- **Installer Module Label Localization**: Moved installer-visible module names into the multilingual installer language payload so module cards, dependency chips, progress summaries, and review screens now follow the selected installer language instead of fixed English labels.
- **Installer Native Language Cards**: Reworked the installer language step to show native language names with code badges instead of bare uppercase codes, keeping the phone-first chooser aligned with the modernization plan.
- **Installer Environment Meta Cleanup**: Replaced the database-check string sentinel with structured environment metadata so the phone-first environment screen no longer relies on English-only internal detail parsing.
- **Installer Environment Item Labels**: Moved required extension names and writable-directory names behind installer language keys so blocked environment summaries no longer surface raw internal identifiers in the mobile UI.
- **Installer Settings Dependency Fix**: Moved `mod_settings` behind `mod_user` and `mod_user_group` in installer execution order and declared the dependency explicitly so the `settings` SQL view is created only after `mod_user` exists.

## v1.0.53.51
- Active version bucket derived from the latest Git history and reserved for upcoming development notes until a newer Git upload version appears or the user explicitly assigns another version code.
- **Installer Mobile Foundation Shell**: Reworked `installer/index.php` into a phone-first guided setup shell with persistent step state, explicit installer-language selection, structured environment checks, compact project-setup capture, review and reset flows, and a mobile completion screen that wraps the existing legacy installer execution.
- **Installer Planning Services**: Added `InstallerStateStore`, `InstallerModuleCatalog`, `InstallerDependencyResolver`, and `InstallerEnvironmentCheck` under `installer/app/` so the installer now has dedicated state, manifest, dependency, and readiness services instead of relying only on a print-driven single script.
- **Installer Loader Path Fix**: Corrected the installer module loader so module file existence is validated against the real `installer/` path before the module class is required.
- **Installer UI Localization Wiring**: Replaced hard-coded installer shell text with `mod/lang` language keys across the active installer flow and localized the new phone-first setup surface for the installer languages currently exposed by the repository-specific installer configuration.

## v1.0.53.45
- Active version bucket derived from the latest Git history and reserved for upcoming development notes until a newer Git upload version appears or the user explicitly assigns another version code.
- **News List Image Auto-Upload Flow**: Simplified the `mod_news` list-image modal by removing the manual save action, starting uploads immediately after file selection, keeping the modal action state passive during transfer, and adding a request lock so repeated taps cannot start duplicate image uploads.
- **Gallery Upload Progress And Sheet Locking**: Upgraded the `mod_gallery` phone upload sheet with per-file progress bars, live queue status totals, automatic queue start after file selection, retry-aware upload state handling, and a close guard that prevents the sheet from being dismissed while an upload is still in progress.
- **Gallery Upload Reliability Repair**: Hardened the `mod_gallery` bulk upload queue by exposing `GalleryManager` globally for inline sheet controls, scheduling auto-start more reliably after multi-file selection, preventing missing `Amed.notify` from breaking successful uploads, continuing through the full queue instead of aborting at the first error, and cache-busting the active `list_images` script include so the repaired upload flow loads immediately in the admin panel.
- **Gallery Upload Monitoring And Exit Safety**: Reworked the phone upload sheet for long-running queues with a persistent progress summary card, stronger upload-state color semantics, warning-toned safety messaging during active transfers, snackbar-based notifications instead of inconsistent legacy toasts, disabled close controls while uploads are running, and a `beforeunload` guard so accidental page exits are warned before the queue is interrupted.
- **Footer Site Title Font Uniformity**: Unified the `site.title` typography in the footer with the brand's primary Gothic identity used in the top menu. Implemented the `brand-text-gothic` class in `footer.twig` and added dedicated CSS support in `footer.css` to ensure consistent `'Grenze Gotisch'` rendering across light and dark theme modes.
- **Google Favicon Indexing Optimization**: Resolved the issue where the site icon was not being indexed by Google. Moved `favicon.ico`, `favicon-96x96.png`, and `apple-touch-icon.png` to the root directory to prevent CMS-driven HTML error responses on direct `/favicon.ico` requests. Updated `base.twig` to prioritize these root-level assets and included explicit `sizes` definitions (96x96, 32x32) to align with Google Search's multiple-of-48px recommendation.

## v1.0.53.39
- Active version bucket derived from the latest Git history and reserved for upcoming development notes until a newer Git upload version appears or the user explicitly assigns another version code.
- **Gallery Phone-First Image Surface**: Reworked the `mod_gallery` image list for the phone-only admin shell by switching the gallery feed to a single-column vertical flow, removing rounded image framing, hover-style visual effects, overlay shading, blur, and card shadows from the image surface, and moving image actions into a flatter content-first block so gallery items read as full images instead of icon-like tiles.
- **Gallery Tooling Ergonomics Repair**: Removed the context-breaking back action from the gallery tools sheet, forced upload opening to exit selection mode and close competing sheets, reduced the detail sheet density for quicker phone use, moved destructive delete off the image surface into the detail flow, enlarged touch targets for remaining in-image controls, and compressed the fixed bottom action bar to icon-only phone navigation.
- **Gallery Selection Sheet Flow**: Replaced the in-page bulk-selection action row with a dedicated bottom sheet for selection mode, so bulk actions no longer consume gallery content space and the fixed bottom bar now yields completely to a focused selection workflow on phones.

## v1.0.53.36
- Active version bucket derived from the latest Git history and reserved for upcoming development notes until a newer Git upload version appears or the user explicitly assigns another version code.
- **Gallery Module M3 Repair**: Repaired the active `mod_gallery` admin flow by aligning controller/JSON contracts, restoring full language coverage across `en`, `tr`, `ku`, and `zza`, replacing hidden double-click-only actions with explicit preview/rename/delete controls, fixing full gallery-list loading and client-side search behavior, wiring meaningful record labels into image-page headings, and removing the dead standalone M3 upload template in favor of the completed inline upload sheet flow.
- **News List Gallery Shortcut Fix**: Corrected the `mod_news/list` gallery action so the toolbar and context-menu `Add Gallery to the News` entry now open the news item's own gallery manager at `mod_gallery/list/mod_news/{id}` instead of falling into the existing-gallery picker flow that surfaced gallery-category records.
- **Gallery Image Workspace Phase 1**: Upgraded the active gallery image-management screen with a structured context header, sticky command bar, live in-page image search, visible image counters, cleaner image cards, and a more editorial M3 workspace layout for `mod_news` gallery management without changing the underlying gallery data contract.
- **Gallery Image Workspace Phase 2**: Added second-phase management tooling to the active gallery image workspace, including client-side sort controls, explicit selection mode, visible selection counters, select-visible and clear-selection actions, and sequential bulk delete built on the existing gallery delete endpoint.
- **Gallery Image Workspace Phase 3**: Expanded the active gallery image workspace with a persistent detail panel for the current image, visible previous/next navigation, direct panel-based preview/rename/delete actions, and a richer upload queue with per-file previews and waiting/uploading/success/error states, while extending full multilingual coverage for the new UI layer across `en`, `tr`, `ku`, and `zza`.
- **Gallery Mobile Workspace Fit Fix**: Revalidated `mod_gallery/list/mod_news/{id}` in the live admin simulator with MCP browser checks and corrected the image-management surface for the app-like mobile shell by removing the broken side-by-side grid/detail split, switching the workspace to a single-column mobile flow, forcing a two-column image grid inside the simulator width, and compressing card and toolbar controls so gallery tools remain usable inside the 412px device frame.
- **Gallery Detail Bottom Sheet**: Reworked the mobile gallery image detail experience into an app-style bottom sheet, moved the active image actions out of the inline flow, cache-busted the `mod_gallery.js` include so the new sheet controller loads reliably, and validated the open-state behavior in the live admin simulator with MCP browser checks.
- **Gallery Mobile Header Compression**: Revalidated the live `mod_gallery/list/mod_news/{id}` mobile simulator after cache clearing and reduced the top chrome density by compacting the hero, stats, and toolbar layout while also shrinking the gallery detail bottom sheet preview and height, restoring usable image-management space on the first screen inside the fixed app-style device frame.
- **Admin Phone-Only Agent Policy**: Tightened repository agent guidance so the entire admin panel is now explicitly treated as a phone-only product surface, with implementation and validation required to target phone-sized viewports or the repository phone simulator only, while tablet/desktop-first admin reasoning is treated as a policy violation across the main agent instructions, quality strategy, manifest, examples, and review prompts.
- **Gallery Record Label And Bottom Action Model**: Corrected `mod_gallery` record label resolution so image-management screens now show the real source record title instead of a broken numeric placeholder, and replaced the top-heavy phone toolbar pattern with a bottom action bar plus secondary tools sheet so gallery controls follow the repository's phone-only admin ergonomics.
- **Gallery Header Density Cleanup**: Reduced the overemphasized phone-page headline to a compact scale, removed the duplicated record title from the gallery context card, and hid the bottom action bar while the secondary tools sheet is open so the image workspace stays focused on content instead of stacked chrome.

## v1.0.53.35
- Active version bucket derived from the latest Git history and reserved for upcoming development notes until a newer Git upload version appears or the user explicitly assigns another version code.
- **Admin Localization Synchronization**: Synchronized `mod_user`, `mod_index`, `mod_management`, `mod_acl`, `mod_user_group`, `mod_settings`, and `mod_page` language coverage for the touched admin/user-menu flows, removing confirmed extra keys and filling missing keys from the English reference layer.
- **Panel Menu Language Bootstrap Fix**: Updated the management bootstrap so the admin user panel request also loads `mod_user_pm`, `mod_user_group`, and `mod_acl` language blocks, fixing blank labels in the personal menu and administration submenu.
- **Admin Template Hard-String Cleanup**: Replaced visible hard-coded labels in admin header, dashboard, wizard, under-construction, ACL, settings, news, publication, page, and video-embed templates with language keys, and removed newly introduced fallback-based UI output from the touched flows.

## v1.0.53.34
- Active version bucket derived from the latest Git history and reserved for upcoming development notes until a newer Git upload version appears or the user explicitly assigns another version code.
- **Agent Version Bucket Detection Fix**: Updated repository agent guidance so version logging no longer stays pinned to `v1.0.49.8`; agents must now inspect Git history, detect the latest upload version, and write notes under that newest section.

## v1.0.49.8
- Active version bucket reserved for upcoming development notes until the next Git upload version code is assigned.
- **Attachment Module Shared-Core Sync**: Re-established `mod_attachment` as the first shared dependency before relation-module validation, normalized the active controller/template contract to `list/add/upload/edit/delete`, restored localized error handling, and aligned the active attachment workspace across `Rojname` and `AKMbusiness`.
- **Attachment Mapping Relation Revalidation**: Revalidated `mod_attachment_mod` after the attachment-module sync, keeping its modern `add/edit/list` registry flow aligned with the new attachment workspace contract and confirming shared active parity across both CMS repositories.
- **Menu Module Shared-Core Audit**: Reviewed `mod_menu` across both CMS repositories and confirmed the active controller, validator, and admin template contract is already aligned; only intentional product-specific package headers and frontend theme surfaces remain different.
- **Document Module Source Syntax Repair**: Corrected the duplicated `image_remove_image()` controller declaration in `mod_document` by restoring the missing `edit($id = NULL)` action at source level, then revalidated shared parity across both repositories while preserving intentional product-specific package headers.
- **Content Module Route Fix**: Corrected the admin content quick actions so Gallery and Attachment shortcuts now open `mod_content`-scoped records instead of incorrectly routing through `mod_news`.
- **Content Module Localization Cleanup**: Removed visible fallback text from the modern `mod_content` admin add, edit, and list flows, added the missing `wizard_loading` language key, and aligned AJAX success/error responses with the English-first admin refactor.
- **Shared Image Uploader Consolidation**: Replaced the duplicated `mod_news` image uploader partial with the shared `admin/partials/image_uploader.twig` across content, news, publication, and wizard-based admin flows.
- **Version Log Enforcement Hook**: Added a tracked Git `pre-commit` hook under `.githooks/` so commits that stage repository changes without also staging `docs/VERSION.md` are blocked locally.
- **Agent Version Logging Policy Sync**: Synchronized the repository agent guidance (`AGENTS.md`, `.agents/*`, and quality strategy docs) so the active version bucket stays fixed at `v1.0.49.8` until the user explicitly assigns a new Git upload version code.
- **AJAX Login Modal Redirection**: Implemented a seamless redirection from the `/en/login` page to the homepage with a `?login=modal` query parameter. Updated the `mod_user` controller to handle unauthorized access by triggering the login modal instead of a full-page redirect.
- **Login Modal Auto-Trigger & UX**: Enhanced `login-modal.js` to automatically detect the `login=modal` parameter, trigger the Bootstrap modal, and clean the URL via `window.history.replaceState` for a professional, "hidden URL" experience.
- **Login Modal Localization**: Fully localized the AJAX login modal flow by removing all hardcoded UI strings (EN/KU) from JavaScript. Added missing language keys (`user_not_found`, `username_required`) and updated the `top_menu.twig` bridge to ensure consistent multilingual support.
- **PHP Error Correction**: Resolved a critical "Too few arguments" error in `Req::is_Post()` within `mod_user/index.php` by switching to a more direct and robust `$_SERVER['REQUEST_METHOD']` check.

## v1.0.49 (2026-03-22)
- **Standalone Social Management**: Refactored the `mod_settings` module to abstract "Social Media" configuration away from general settings, hosting it inside its own dedicated, modernized standalone template (`social_media.twig`).
- **Material Design 3 (M3) Overhaul**: Completely modernized the user interface for social media links management using M3-styled cards, brand distinct colors, and elegant hover-state CSS micro-animations.
- **Dynamic Localization Fix**: Extracted all hardcoded UI text (e.g., "No records found", "URL not configured") from the JavaScript logic, completely replacing them with centralized translated variables mapped in English and Kurdish language files.
- **Customized Bottom Navigation**: Overrode the default app navigation (Navbar and FAB) exclusively for the Social Media screen, replacing it with a tailor-made standard bottom toolbar (`fixed_bottom`) comprising contextual quick actions (Home, Profile, Settings, History Back).
- **Intelligent Social Icon Rendering**: Updated the `soul`, `default`, and `artist` frontend templates to enforce strict URL validation (`link.url|trim is not empty`). This ensures partially configured but actively toggled social media platforms do not accidentally render broken icons on the public site footer.
- **Holistic Footer Contact Sync**: Synchronized the public-facing contact dropdown on all three themes (`soul`, `default`, `artist`) to dynamically pull comprehensive contact methods saved via `mod_settings`, successfully incorporating properly-iconized Email, Work Address, Office Address, WhatsApp, and Phone listings automatically.
## v1.0.48 (2026-03-20)
- **Dynamic Logo Display Framework**: Added a new `show_logo` configuration to `mod_settings` allowing administrators to toggle between displaying the site's logo or the site title dynamically across all themes (`soul`, `default`, `artist`). 
- **Global Settings Synchronization**: Upgraded the settings module so that global configuration fields (like `show_logo`) sync instantly across all multilingual database rows, preventing language-specific fragmentation for universal UI settings.
- **Asynchronous Toggle Experience**: Replaced the standard form submission for the "Show Logo" setting with a modern, independent AJAX endpoint (`ajax_toggle_logo`) and Javascript EventListener, saving and applying the visual choice asynchronously without requiring a page reload.
- **Logo Visual Refinements**: Refactored the structural and CSS presentation of site logos to fit within an aesthetic, perfect-circle container (`42px x 42px`) that properly handles PNG transparency by utilizing a solid white background and preventing "polygonal" distortions.
- **Slider Module UI Refinement**: Improved the slider module's user interface by implementing consistent empty state messages (differentiating between "no data" and "no search results"), aligning its behavior and language key usage with standard modules like `mod_content`.
- **User Panel Redesign**: Streamlined and modernized the user panel (dashboard) interface. Redesigned the "quick access" links section (`mod_user/panel`) to reduce clutter, making it more concise, efficient, and visually appealing.
- **Dashboard Quick Access Expansion**: Added a direct quick access link for the "Slider" module to the dashboard's action list, placing it at the end of the row for easier user navigation.
- **FAB Blur Effect Correction**: Resolved a visual issue with the Floating Action Button (FAB) where the backdrop blur effect incorrectly applied to the FAB elements. Adjusted CSS `z-index` and stacking contexts to ensure the background blurs correctly while the menu items remain crisp and distinct.
- **User Menu Reordering**: Adjusted the order of items in the user's creation menu to prioritize common actions. The new logical order presents "Add Content" first, followed by "Add Video", and finally "Add Announcement".
- **Slider Module Installer Sync**: Updated the module installer (`installer/mod_slider/mod_slider.sql`) to include the newly introduced `image_mobile` column, allowing fresh installations to build the fully modernized database schema automatically.

## v1.0.47 (2026-03-19)
- **Asset Cache Optimization**: Replaced dynamic timestamp-based cache busting (`?v={{ "now"|date("U") }}`) with a stable version identifier (`site.version`) in `soul_top_menu.twig`. This ensures efficient browser caching for static assets (CSS/JS) and improves page load performance.

## v1.0.46 (2026-03-18)
- **DOM ID Deduplication**: Resolved duplicate `id="mainNavbar"` and `id="navbarMain"` attributes across `soul`, `default`, and `artist` themes. Assigned unique IDs (`soulMainHeader`, `defaultMobileMenu`, etc.) to prevent Bootstrap collapse conflicts and improve DOM reliability.

## v1.0.45 (2026-03-13)
- **Documentation Consolidation**: Replaced the fragmented planning structure with a single master planning document, `ADMIN_MODERNIZATION_MASTER_PLAN.md`, and archived older planning/task notes under `docs/archive/`. From this version forward, root-level project documentation is reduced to two active files: the master plan and `VERSION.md`.
- **Admin Modernization Status Sync**: Synchronized the written project status with the live `templates/admin/` implementation. The master plan now accurately reflects the active hybrid architecture: modern shell in `templates/admin/layout.twig` plus adapter-based legacy compatibility through `templates/admin/management/admin-layout.twig`.
- **Menu Module Modernization Recorded**: Captured the already-implemented modernization of `mod_menu`, including the M3-style tree list, mobile-friendly reorder mode with SortableJS, modern add/edit forms, Tom Select integration, and SweetAlert2 action flows.
- **Category Module Modernization Recorded**: Captured the active modernization state of `mod_category`, including the tree-based list UI, reorder workflow, enhanced smart-form direction, and SweetAlert2-based delete/reorder confirmations.
- **Private Messaging Inbox Modernization Recorded**: Added the missing changelog record for the modern `mod_user_pm` inbox experience, including the dynamic message list container, empty state, infinite loader, dedicated toolbar, and standalone JS manager (`mod_user_pm.js`).
- **Settings Module Migration Started**: Added the first direct modern admin template for `mod_settings` in `templates/admin/mod_settings/edit.twig`. The new screen now uses the shared M3 admin shell, grouped settings panels, multilingual switching, modern asset cards for logo/favicon/manifest management, and SweetAlert-backed file removal flow while preserving the existing backend `edit` route.
- **User Group Module Migration Started**: Added direct modern admin templates for `mod_user_group` list, add, and edit flows. The new UI introduces a mobile-first group tree, toolbar-based actions, drag-and-drop reorder support, and modern form screens while preserving the current backend `tasi`, `sira`, and `sil` contracts.
- **Attachment Module Migration Started**: Added direct modern admin templates for `mod_attachment` list and upload flows. The new UI treats attachments as a record-bound file workspace with searchable file cards, inline rename/delete/download actions, and a modern upload surface wired to the existing `up`, `addegis`, and `sil` backend endpoints.
- **Attachment Mapping Module Migration Started**: Added direct modern admin templates for `mod_attachment_mod` list, add, and edit flows. The new UI replaces the old DataTable/portlet CRUD surface with a card-based registry view and simplified modern forms while keeping the current `datatable_json` and `sil` backend contracts in place.
- **Admin Menu Route Cleanup**: Updated the user menu entry for site settings to use `mod_settings/edit` instead of the legacy `mod_settings/duzenle` route.
- **Changelog Reliability Fix**: Re-established `VERSION.md` as the single historical log of completed work. The previous gap between the branded application version (`v1.0.45`) and the recorded changelog history is now explicitly closed.
- **Settings Route Naming Cleanup**: Renamed the active modern settings template to `templates/admin/mod_settings/edit.twig`, updated the controller to render the English `edit` template directly, localized remaining hardcoded error strings in `mod_settings`, and kept `mod_settings/duzenle` as a compatibility alias during the migration.
- **Dynamic Language Switcher**: Replaced the hard-coded language list in the frontend header (`soul_top_menu.twig`) with a dynamic PHP/Twig workflow. The system now fetches only active and valid languages (`valid_lang`) directly from the database's `lang` table via the `Amed.class.php` bootstrapper. It injects them correctly into the `site.available_languages` configuration, resolving broken links and ensuring the switcher stays synchronized when administrators add or remove languages.
- **Language File Updates**: Corrected the spelling of "Zazakî" to "Zazakî" in `installer/lang/index.php`.

## v1.0.23 (2025-12-28)
- **Automatic WebP Conversion**: Updated `mod/image` module to automatically convert all uploaded images (JPG, PNG, GIF) to the modern **WebP** format. This significantly reduces file size while maintaining high quality, adhering to Google's next-gen image recommendations.
- **Optimized Image Quality**: Enforced a standardized quality setting of **80%** for all WebP conversions, ensuring the perfect balance between visual fidelity and page load speed.
- **High-Res Homepage Images**: Updated the homepage `latest_content.twig` template to serve the original high-resolution (max 1200px) WebP images instead of low-quality 250px thumbnails. This dramatically improves visual appeal on high-DPI (Retina) screens and mobile devices without sacrificing performance, thanks to WebP optimization.
- **SEO Alignment**: Verified that `mod_content` strictly enforces a 1200px width limit for main article images, meeting Google News and Discover requirements for optimal indexing and display.

## v1.0.22 (2025-12-28)
- **Wizard Date Auto-fill**: Implemented smart logic to automatically populate the publish date field with the current local time when navigating to Step 4 of the content wizard. This streamlines the workflow while still allowing manual adjustments if needed.
- **Timezone Awareness**: The auto-fill logic correctly calculates the local time based on the user's timezone offset, ensuring the date and time match the user's actual environment.

## v1.0.21 (2025-12-28)
- **Content List Sorting Fix**: Resolved the critical issue where "Newest" and "Oldest" sorting filters were ignoring user selection. The system now correctly respects the selected date order.
- **Sort Column Index Correction**: Fixed a logic mismatch between Frontend (JS) and Backend (PHP) where sort column indices were off by one. This corrected the behavior of "Title A-Z/Z-A" (Index 1 -> 2) and "Date" (Index 5 -> 6) sorting.
- **Filter Logic Synchronization**: Updated `filter_sheet.twig` and `script.js` to strictly enforce sort column and direction parameters via dedicated hidden inputs (`filterSortColumn`, `filterSortDir`), ensuring reliable data transmission to the `datatable_json` endpoint.

## v1.0.20 (2025-12-28)
- **"All Languages" Filter**: Added an "All" (Hemû) option to the content list filter, allowing users to view content from all languages simultaneously. This resolved the limitation where admins could only view content in their interface's language.
- **Filter Sheet Scroll Fix**: Resolved a critical UI issue where the language selection sheet was not scrollable on smaller screens or with many languages. Implemented a robust CSS fix in `sheets_extra.twig` to ensure smooth vertical scrolling.
- **Wizard Isolation**: Implemented logic to ensure the "All" language option appears ONLY in the filter menu and NOT in the "New Content" wizard, preserving data integrity during content creation.

## v1.0.19 (2025-12-28)
- **Global Publish/Unpublish Refactoring**: Completed a system-wide rename of legacy Turkish terminology (`yayinla` → `publish`, `yayindan_kaldir` → `unpublish`) to English, ensuring consistency across all modules and languages.
- **Controller Standardization**: Refactored `mod_popup`, `mod_slider`, `mod_video`, `mod_headline`, and `mod_video_embed` controllers. Replaced legacy methods with standardized `publish()` and `unpublish()` methods while maintaining backward compatibility where necessary.
- **Template Cleanup**: Updated active administrative templates (`wizard_content.twig`, `mod_content/edit.twig`) to use the new `context_menu.publish` keys. Also cleaned up legacy keys in the `templates/admin_old/` directory.
- **Language File Synchronization**: Updated `tr.php`, `en.php`, and `ku.php` files for all affected modules to strictly use `publish` and `unpublish` keys, removing ambiguous Turkish variations.
- **Frontend Logic Update**: Updated `layout.twig` and `script.js` to reference the new English keys in the global `adminLang` object, ensuring context menus work correctly with the new backend logic.

## v1.0.18 (2025-12-27)
- **Admin Panel Search Bar**: Removed unnecessary search bar and filter button from `mod_user/panel` (Dashboard) page by setting `hide_search = true`. The search functionality is only needed on list pages, not on the welcome dashboard.
- **Content List Search Fix**: Fixed search functionality on `mod_content/list` page. Changed filter input name from `search[value]` to `arama[name]` to match the backend's expected parameter format in `datatable_json()` method.
- **Kurdish Language Improvements**: Updated multiple Kurdish (KU) language files with improved translations:
  - `mod_content/lang/ku.php`: Changed "Rêz Bike" → "Naverok" for list action, improved wizard step labels and validation messages
  - `mod_headline/lang/ku.php`: Updated validator required message
  - `mod_popup/lang/ku.php`: Updated validator required message
  - Standardized deck length message: "herî zêde" → "herî pir"

## v1.0.17 (2025-12-27)
- **Wizard Double Submission Fix**: Removed duplicate `onclick` handler from `btnWizardFinish` button in `wizard_content.twig`. The button had both inline onclick and JavaScript addEventListener, causing `prepareAndSubmitWizard()` to be called twice - first submission succeeded, second failed with "SEO URL already exists" error.
- **Snackbar Notifications**: Added M3-style `showSnackbar()` function to replace browser's native `alert()` dialogs. Notifications are now color-coded (green=success, red=error) and non-blocking, eliminating the Turkish "Tamam" button issue.
- **Wizard State Protection**: Added `wizardSubmitted` flag to prevent TinyMCE sync interval from re-saving localStorage after successful submission. Added `wizardJustSubmitted` localStorage flag to prevent auto-restore after page reload (expires after 10 seconds).
- **Code Comment Translation**: Translated all Kurdish code comments in `script.js` to English for international readability and maintainability.

## v1.0.16 (2025-12-27)
- **Wizard Language Default Fix**: Fixed the Content Wizard to automatically select the language based on the URL (e.g., `/ku/` → Kurdish). Previously, it always selected the first language (Arabic) regardless of URL.
- **Dynamic Language Fallback**: Changed the hidden language field from hardcoded `'TR'` to dynamic `lang_lang[0].seo` for automatic fallback based on database configuration.
- **Title Length Standards**: Updated content title validation from min=2/max=40 to min=1/max=70 characters, following international SEO and social media headline standards (Google SEO ~60-70, Twitter ~100).
- **Consistent Validation**: Synchronized title length rules across all files: `ku.php`, `en.php`, `tr.php` (lang files), `wizard_content.twig`, `add.validator.php`, `edit.validator.php`, and `script.js`.

## v1.0.15 (2025-12-27)
- **Wizard AJAX Debugging & Fix**: Fixed the Content Wizard submission that was failing with "Bersiva ne JSON e" error. The root cause was missing form fields (`gallery_id`, `dosya_id`, `acl_id`, `menu`, `video`) in the JavaScript FormData.
- **Database Exception Handling**: Enhanced `DatabaseException::show()` to return JSON responses for AJAX requests instead of HTML, making debugging easier.
- **Global Exception Handler**: Added a global exception handler in `index.php` that catches all uncaught exceptions and returns proper JSON for AJAX requests.
- **Error Logging**: Implemented automatic error logging to `logs/ajax_errors.log` for all database and system exceptions.
- **Validation Fix**: Updated `wizard_content.twig` and `script.js` to use `maxlength="255"` for the intro field, matching the backend validator's maximum length requirement.
- **FormData Completion**: Added all required hidden form fields to JavaScript FormData in `prepareAndSubmitWizard()` function.


## v1.0.14 (2025-12-26)
- **Wizard State Persistence**: Implemented localStorage-based state preservation for the Content Wizard. When users press F5 (page refresh) during the 8-step wizard, all entered data (language, category, title, date, intro, content) is now preserved and automatically restored. State expires after 24 hours for security.
- **Auto-Restore Feature**: Added automatic wizard restoration on page load. If a saved wizard state exists, the wizard opens automatically at the last step with all data intact.
- **Submit Button Fix**: Removed duplicate `prepareAndSubmitWizard()` function from `add.twig` that was overriding the correct implementation in `script.js` and causing the submit button to fail due to incorrect element ID (`wizardContentInput` instead of `wizardContentTextarea`).
- **Date Input Tracking**: Added change event listener for the date input to save wizard state when users select a date.
- **Cache Busting**: Updated `script.js` version from v1.25 to v1.26 in `layout.twig` to ensure browsers load the updated JavaScript.

## v1.0.13 (2025-12-26)
- **Controller Refactoring**: Renamed legacy `duzenle` references in `mod_content` to `edit`, ensuring correct permission checks (`moderate_edit`) and method calls, solving the "Method Not Found" error.
- **Validator Fix**: Renamed validator classes from `duzenle_validator` to `edit_validator` and `ekle_validator` to `add_validator` in `mod_content` to match their filenames and fix `ClassException` crashes.
- **Legacy Compatibility**: Retained `sil` and `onayla` methods as per user request to ensure backward compatibility with existing workflows while modernizing the core edit functionality.
- **Form Submission Fix**: Added missing `submitEditForm` JS function to `edit.twig` and implemented a proper redirect in `mod_content` controller's `_edit` method to fix the unresponsive "Edit" button.
- **Database Schema Sync**: Updated `mod_content` model, controller, and template to consistently use `deck` instead of `spot`, aligning with the internationalization plan and resolving database column mismatches.
- **JavaScript Console Error Fix**: Removed duplicate `const SITE_URL` declaration in `list.twig` that was blocking script execution and causing "Tê daxistin..." (Loading) to hang indefinitely.

- **Internationalization Check**: Fixed legacy `listele` links in Admin Panel (Dashboard and Navbar) by updating them to `list`, effectively resolving the "Method Not Found" error.
- **System Error Localization**: Updated the core `RunException` handler to support multi-language error messages (English, Kurdish, Turkish), ensuring system errors are displayed in the user's preferred language.
- **Database Permission Fix**: Updated database permission roles for `mod_content` (renamed `listele` -> `list`, `ekle` -> `add`, `duzenle` -> `edit`) to align with the new English codebase, resolving "Authorization Error" issues.
- **Language Key Synchronization**: Replaced deprecated `lang.mod_content.listele` keys with `lang.mod_content.list` across all admin templates (`navbar.twig`, `panel.twig`, `dashboard`, etc.), ensuring full consistency and fixing missing translation issues.
- **Legacy Template Synchronization**: Renamed legacy template files in `templates/admin_old` (`listele.twig` -> `list.twig`, `ekle.twig` -> `add.twig`, `duzenle.twig` -> `edit.twig`) to ensure consistent naming across the entire codebase.

## v1.0.12 (2025-12-25)
- **Module Content Refactoring**: Fully renamed `mod_content` template files from Turkish (`ekle`, `duzenle`, `listele`) to English (`add`, `edit`, `list`) to standardize the codebase.
- **Codebase Standardization**: Updated all controller logic, validator names, and method calls within `mod_content` to use English naming conventions, ensuring consistency with other modules.
- **Global Reference Updates**: Systematically updated all references in Admin templates (`sidebar.twig`, `fab.twig`, `wizard_content.twig`), Frontend templates (`top_menu.twig`), and JavaScript files (`mod_content.js`) to point to the new English routes.
- **Language File Synchronization**: Updated key names in `tr.php`, `en.php`, and `ku.php` to match the new English method names (`add`, `edit`, `list`), ensuring correct localization behavior.
- **Installer Permission Updates**: Updated the installer configuration for `mod_content` to use English permission keys (`add`, `edit`, `list`) instead of the legacy Turkish ones.

## v1.0.11 (2025-12-19)
- **Module Professionalization**: Renamed the remote video module to `mod_video_embed` and its category module to `mod_video_embed_category` to clarify source distinction (Remote vs Local).
- **Database Column Standardization**: Renamed `ad` column to `name` in `mod_video_embed` table and updated the `videos` SQL view for international compatibility.
- **Robust Refactoring**: Successfully resolved naming conflicts between `mod_video` and `mod_video_embed` across PHP classes, Twig templates, and SQL schemas.
- **KU Language Localization**: Fully translated the Kurdish (KU) language file for the Video Embed module, including system and DataTable AJAX error messages.
- **Git & GitHub Integration**: Initialized the local Git repository and synchronized the project with the RojnameCMS GitHub repository for version control security.
- **AJAX & DataTable Fixes**: Standardized JSON output and fixed column mapping issues in the administrative video listing interface.

## v1.0.10 (2025-12-18)
- **SEO & UX Overhaul**: Reorganized content flow (Deck > Image > Content > Video) to meet Google's professional article standards.
- **Performance Optimization**: Improved LCP with `fetchpriority="high"` for main images and added `loading="lazy"` to video iframes for faster page speeds.
- **Enhanced Structural Data**: Integrated advanced JSON-LD Schema for `Article` (including `dateModified`) and `VideoObject` to improve search engine visibility.
- **Readability Improvements**: Optimized typography with increased font sizes (`fs-4` for deck, `fs-5` for content) and improved line-height for better user experience.
- **Global Internationalization**: Removed all hardcoded strings from templates and synchronized 8 language files (TR, EN, KU, AR, CKB, DE, FR, SV) with new UX keys.
- **Database & Path Fixes**: Resolved missing `mod_attachment_mod` table issues and corrected legacy template directory paths.
- **Attachment UI Modernization**: Redesigned the download section with file format badges and a cleaner, more intuitive list layout.

## v1.0.9 (2025-12-18)
- **Module Renaming & Refactoring**: Renamed `mod/mod_file` to `mod/mod_attachment` (Attachments) and `mod/mod_files` to `mod/mod_document` (Documents) to eliminate naming confusion.
- **Global Reference Update**: Updated all class names, model names, language keys, and template references across the entire codebase to match the new module names.
- **Installer Synchronization**: Renamed installer directories and corrected class definitions within the installer to ensure seamless new installations.
- **Permissions & Cache Fix**: Resolved "Permission denied" errors in the captcha system by ensuring `cache/captcha` directory is created automatically with correct write permissions.

## v1.0.8 (2025-12-18)
- **Module & Service Refactoring**: Renamed `mod/resim` to `mod/image` and `mod/dosya` to `mod/file`. Standardized class and model names to English.
- **Service Name Standardization**: Updated all system-wide `Load::mod` and `Load::model` references to use the new English naming convention.
- **Configuration Cleanup**: Removed unused Turkish configuration keys and paths from `site.config.php`.
- **Module Specific Improvements**: Refactored `mod_video` to use `image/` directory instead of `resim/` and updated its templates and methods accordingly.
- **Docker & CLI Support**: Enhanced `db.config.php` for seamless database connection in Docker and CLI environments.
- **Bug Fix**: Resolved a Twig syntax fatal error in the search results template (`ara.twig`).
- **Filesystem Cleanup**: Removed the redundant and empty `static/mod_pictures` directory.

## v1.0.7 (2025-12-15)
- Fixed search functionality: Corrected column names, fixed Twig syntax, resolved cache issues, and improved data passing to templates. The search results page was also redesigned with Bootstrap 5.3.8.
- Modernized frontend footer: Redesigned the main footer with Bootstrap 5.3.8 components and added theme (dark/light mode) compatibility, including dedicated CSS for flexible styling.
- Cookie policy texts have been integrated into the multilingual structure (tr, en, ku, ar, de, fr, sv, ckb).

## v1.0.6 (2025-12-06)
- CSS, JS and IMAGE folders in static/ were moved to templates/base_static/ and references were updated.

## v1.0.5 (2025-12-05)
- Mobile compatibility has been improved when adding content.
- Language improvements have been made to the Video Embed page.
- Visual display has been improved when viewing content.
- Multilingual support has been improved for many sections.

## v1.0.4 (2025-11-26)
- Logo and Favicon have been reconfigured to be deleted from all languages ​​when deleted from one language, and to be loaded and displayed in all languages ​​when installed in one language.
- Fixed PHP 8.3+ deprecation warning for dynamic properties in mod_user_group module.

## v1.0.3 (2025-11-25)
- The Settings module has been added, which manages the Site Name, Description, Institution Name, Logo, Favicon, Email, Phone, WhatsApp, Established Year, Office Address, and Business Address information.
- An improvement has been made to load the .ico type in the images section.
- A correction has been made so that when uploading a logo, the file name will be saved as logo.$filetype, and when uploading a favicon, the file name will be saved as favicon.ico.
- The installer package has been enhanced to support MySQL and SQLite.
- **Cache system optimized**: Removed redundant `/cached/` directory, unified all cache operations under `/cache/` for better organization and performance.
- The background property is preserved while images are being loaded.

## v1.0.2 (2025-11-17)
- Upgraded to Boostrap 5.3.8.
- Upgraded to the Boostrap 1.13.1 icon set.
- Night and Day mode issues have been resolved.
- Removed nearly 500 lines of CSS that were unnecessary with Boostrap 5.3.8.
- Standardized the language menu, eliminating appearance issues.
- Hardcoded text that doesn't come from the language file has been added to the content.twig files. Lines that don't match day-night mode on the same pages have been corrected and tested.

## v1.0.1 (2025-11-08)

### 🚀 **Major Frontend Overhaul**

#### **Complete Menu System Modernization**
- **Implemented modern responsive navigation**: Fully redesigned top menu with mobile-first approach
- **Added comprehensive language support**: 6-language internationalization (TR, EN, DE, FR, KU, AR) with country code badges
- **Enhanced dark mode functionality**: Smooth theme switching with localStorage persistence
- **Optimized search interface**: Multi-language search with improved UX
- **Advanced user interaction**: Hover effects, transitions, and accessibility improvements
- **Mobile-optimized experience**: Touch-friendly off-canvas menu with swipe gestures

#### **Navigation Architecture**
- **Desktop navigation**: Horizontal menu with dropdown functionality
- **Mobile navigation**: Slide-out panel with overlay and gesture support
- **Language selector**: Globe icon + country badge system (TR, EN, DE, FR, KU, AR)
- **Search integration**: Compact search bar with multi-language placeholders
- **User interface**: Avatar system, dark mode toggle, and contextual actions

#### **Technical Enhancements**
- **Responsive breakpoints**: Optimized for all screen sizes (992px, 576px)
- **CSS animations**: Smooth transitions and micro-interactions throughout
- **Accessibility compliance**: WCAG 2.1 standards with ARIA labels
- **Performance optimization**: Efficient CSS and JavaScript implementation
- **Cross-browser compatibility**: Modern browser support with fallbacks

### 🔧 **Bug Fixes & Improvements**

#### **Authentication System**
- **Fixed logout functionality**: Resolved "SSID geçersiz" error by updating logout method to work without session ID parameter
- **Enhanced session management**: Improved logout method to handle both URL parameter and session-based approaches
- **Updated routing**: Fixed logout URL routing for proper functionality

#### **URL Internationalization**
- **Converted Turkish URLs to English**:
  - `cikis` → `logout`
  - `sifremiunuttum` → `forgot-password`
  - `aktivasyon` → `activation`
  - `re_activation` → `resend-activation`
  - `sactivation` → `reset-password`
  - `rkat` → `gallery`
  - `d/` → `announcement/`
  - `s/` → `page/`
  - `i/` → `article/`
  - `kat` → `category`
  - `indir/` → `download/`

#### **Method Name Standardization**
- **Updated PHP method names**:
  - `kayit()` → `register()`
  - `_kayit()` → `_register()`
  - Updated form handling to use `name="register"` instead of `name="kayit"`

#### **Template Updates**
- **Updated all navigation links**: Modified 6 different template files for logout links
- **Fixed form actions**: Updated login form links for password reset and activation
- **Updated email templates**: Fixed password reset and activation email links
- **Modernized menu templates**: Updated top menu navigation for consistency

#### **Modern Menu System Overhaul**
- **Implemented responsive navigation**: Complete mobile-first responsive design with off-canvas slide menu
- **Added language selector**: Integrated 6-language dropdown (TR, EN, DE, FR, KU, AR) with proper country codes
- **Enhanced dark mode toggle**: Added seamless dark mode switching with persistent preferences
- **Improved search functionality**: Integrated search with multi-language support and optimized UI
- **Professional avatar system**: Added avatar upload/change functionality with preview and progress
- **Optimized user experience**: Implemented hover effects, smooth transitions, and accessibility features
- **Mobile-optimized design**: Created touch-friendly mobile menu with swipe gestures and overlay

#### **Routing Configuration**
- **Updated url.config.php**: All routes now use consistent English naming
- **Fixed redirect handling**: Updated PHP redirects to use new URL structure
- **Maintained backward compatibility**: System handles both old and new URL patterns

### 🌐 **Improved User Experience**
- **SEO-friendly URLs**: English URLs are more international and search engine friendly
- **Better navigation**: Consistent URL structure across the application
- **Enhanced accessibility**: More intuitive URL structure for international users

### 📝 **Technical Details**
- **Files modified**: 20+ files including routing, templates, controllers, and language files
- **Routes updated**: 15+ URL routes converted to English
- **Template files updated**: 6 navigation templates, 2 email templates, 1 main menu template
- **Language files added**: Complete internationalization with 6 languages (TR, EN, DE, FR, KU, AR)
- **No breaking changes**: All functionality preserved with improved structure

### 🎨 **Frontend Improvements**
- **Responsive Design**: Mobile-first approach with breakpoints for all devices
- **Modern UI Components**: Bootstrap 5 components with custom styling
- **Animation System**: Smooth CSS transitions and micro-interactions
- **Accessibility**: WCAG 2.1 compliant with proper ARIA labels and keyboard navigation
- **Performance**: Optimized CSS and JavaScript for fast loading

#### **Theme Selector System**
- **Front-end theme switcher**: Added theme selection to user dropdown menu for logged-in users
- **No admin panel access required**: Users can change themes directly from the front-end interface
- **Available themes**: Default (Classic) and Modern (New) themes with visual indicators
- **Cookie-based persistence**: 7-day theme storage using `tema` cookie (consistent with admin system)
- **Visual feedback**: Gradient theme indicators, hover effects, and active state animations
- **Mobile responsive**: Touch-friendly theme buttons with optimized mobile experience
- **Loading states**: Smooth transitions with pulse animations during theme switching
- **Dark mode support**: Automatic color adaptation for dark/light mode users
- **Real-time switching**: Page reload after 600ms delay for immediate visual feedback
- **Integration**: Seamless integration with existing user authentication and profile system
