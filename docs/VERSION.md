# Version History

## Active Version Rule
- `docs/VERSION.md` is the single source of truth for Git upload version notes.
- Before appending any new completed development note, inspect the latest Git upload version code from the current repository history.
- The current Git-detected active version code is `v1.0.53.34`.
- Until the user explicitly provides a different version code, new completed development notes must be appended under the newest Git-uploaded version section.
- If the latest Git-uploaded version section is missing here, agents must create that section first and then append the new notes under it.

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
- **Language File Updates**: Corrected the spelling of "Zazaki" to "Zazakî" in `installer/lang/index.php`.

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
