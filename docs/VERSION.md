## v1.1.18 (2026-10-02)
- **Database-Driven Favicon & Brand Asset Resolution (`templates/amida/layouts/base.twig`, `templates/admin/layout.twig`, `lib/Amed.class.php`, `.htaccess`)**:
  - Dynamically resolved favicon, shortcut icon, apple touch icon, and web manifest tags from `mod_settings` database values (`site.favicon_96`, `site.favicon_path`, `site.apple_touch_icon`, `site.site_webmanifest`) and standard image prefix (`site.iaddress ~ 'mod_settings/'`) instead of hardcoding non-existent root-level URLs.
  - Resolved browser favicon disappearance glitch where browsers rendered initial history cache icons then immediately wiped them out due to 404 responses from hardcoded root `/favicon-96x96.png` and `/favicon.ico` paths.
  - Updated Open Graph (`og:image`) and Twitter (`twitter:image`) metadata tags to resolve dynamically from `site.logo_path` with graceful fallback to `logo.png`.
  - Added robust cross-language brand asset fallback in `lib/Amed.class.php` ensuring brand asset attributes are populated from active or fallback `mod_settings` records across all language contexts.
  - Added root rewrite rules in `.htaccess` mapping direct root requests (`/favicon.ico`, `/favicon-96x96.png`, `/apple-touch-icon.png`, `/site.webmanifest`) directly to `static/mod_settings/` to protect search engine crawlers and browser direct fallbacks from CMS 404 errors.

## v1.1.17 (2026-10-02)
- **Author Spotlight & Dedicated Search Integration (`mod/index/helper/search.helper.php`, `templates/amida/index/search.twig`, `templates/amida/static/css/search.css`, `static/css/search.css`, `mod/index/lang/*.php`)**:
  - Implemented author discovery in site search: queries active authors and contributors (`mod_user` table) matching author first names, last names, combined full names, usernames, and column blog names.
  - Added dedicated caching layer (`Cache::set` / `Cache::get`) for author search results with standard cache key naming convention (`<keyword>-search-mod_authors`).
  - Created prominent "Spotlight" author presentation card in `search.twig` rendered ahead of news/publication results with author avatar rendering (and graceful fallback to site brand icon `mod_settings.site_icon` / `logo_square.png` when no custom photo is present), role badge, handle, location, biography excerpt, article/news statistics, latest article link, and direct link to the author page (`/author/<username>`).
  - Added dedicated `search.css` stylesheet for search page layout and author spotlight styling with comprehensive dark mode support and responsive mobile layout.
  - Enforced AGENTS.md Rule 2 (Strict Code Separation Mandate): eradicated inline styles and inline script tags from `search.twig`.
  - Added localized author search dictionary entries (`authors`, `view_profile`, `author`, `articles`, `news`, `latest_publication`) across all 9 supported languages (`ku`, `en`, `tr`, `de`, `sv`, `fr`, `ar`, `ckb`, `zza`).
- **Cross-Module Image Search Results Integration (`mod/index/helper/search.helper.php`, `templates/amida/index/search.twig`, `templates/amida/static/css/search.css`, `static/css/search.css`, `mod/index/lang/*.php`)**:
  - Implemented unified cross-module image search (`searchImages`) querying `mod_gallery`, `mod_news`, and `mod_publication` when matching search keywords or image file names.
  - Added automated deduplication preventing identical image entries across multiple modules from appearing repeatedly.
  - Resolved parent entity targets (`news/<seo>`, `publication/<seo>`, `page/<seo>`) dynamically based on origin module and record ID for direct navigation.
  - Added dedicated caching layer (`Cache::set` / `Cache::get`) with standard key convention (`<keyword>-search-mod_images`).
  - Added responsive image grid presentation in `search.twig` with 4:3 aspect-ratio thumbnails, source badges (`Gallery`, `News`, `Publication`), title captions, hover zoom effect, and links pointing directly to the target article or media detail page.
  - Synchronized image search CSS styles with full dark mode support across `templates/amida/static/css/search.css` and `static/css/search.css`.
  - Added localized translation keys (`images`, `image`, `gallery`) across all 9 supported languages (`ku`, `en`, `tr`, `de`, `sv`, `fr`, `ar`, `ckb`, `zza`) and verified 100% compliance with `tools/i18n_guard.py` and `tools/code_separation_guard.py`.
- **Header Search UI & Form Input Accessibility (`templates/amida/index/mod_menu/stacked_top_menu.twig`, `templates/amida/index/mod_menu/top_menu.twig`)**:
  - Replaced hardcoded ellipsis and repetitive `lang.index.search.search` placeholders with descriptive, internationalized `lang.index.search.placeholder` ("Li nûçe, nivîs, nivîskar û hwd bigere") across desktop and mobile search form inputs.
  - Resolved accessibility label association with dedicated input element IDs and matching label `for` targets.
- **Header Navigation Desktop Layout & Mobile Drawer Toggler Isolation (`templates/amida/static/css/stacked-top-menu.css`)**:
  - Resolved visual layout defect where the mobile hamburger drawer toggler (`.btn-header-toggler` / `#mobileDrawerOpenBtn`) incorrectly rendered on desktop screens due to CSS specificity overriding Bootstrap's `d-lg-none`.
  - Enforced `display: none !important` on `.btn-header-toggler` for desktop screens (`min-width: 992px`) while preserving responsive `inline-flex` presentation on mobile viewports (`max-width: 991.98px`).

## v1.1.16 (2026-10-01)
- **User Profile Avatar Management & Instant Upload (`mod/mod_user/index.php`, `templates/admin/assets/js/modals/modal_user_profile.js`, `templates/admin/layout.twig`)**:
  - Implemented `ajax_upload_avatar` endpoint in `mod_user` enabling instant avatar upload and persistence upon file selection without requiring current password verification or form submission.
  - Resolved profile image deletion failure in admin user profile modal (`mod_user/info`) by binding delegated event listeners for `data-action="delete-avatar"`.
  - Upgraded `delete_image` backend action to robustly handle authenticated session comparison with type safety, defaulting missing payload IDs to the authenticated user ID and returning structured JSON responses for AJAX clients.
  - Modernized `modal_user_profile.js` to strictly follow the Code Separation Mandate: eradicated inline CSS styles and inline `onclick` handlers in `previewAvatar` and container renderers.
  - Added interactive avatar cancellation (`data-action="cancel-avatar"`), file picker triggering (`data-action="select-avatar"`), and segmented tab switching (`data-action="switch-tab"`).
  - Registered module dynamically with `window.AFModalEngine.loadedModules['modal_user_profile'] = true` to preserve modal lifecycle parity with `AF/Business`.
  - Integrated real-time synchronization across all avatar presentation surfaces (`.user-avatar`, `.user-avatar-sm`, `.avatar-img`) upon instant upload or deletion.
  - Integrated password visibility toggles (`data-action="toggle-password"`), real-time password strength / matching listeners, and character counters without inline handlers.
  - Bumped modal script cache asset version to `v=1.07` in `templates/admin/layout.twig`.
- **Session Image Synchronization & Frontend Avatar Parity (`mod/mod_user/model/user.model.php`, `templates/amida/index/mod_menu/stacked_top_menu.twig`, `templates/amida/index/mod_menu/top_menu.twig`)**:
  - Enhanced `checklogin()` in `user.model.php` to query `image` alongside status credentials, automatically synchronizing `$_SESSION['image']` with the MySQL database on page loads or session checks if any external or out-of-band updates occurred.
  - Aligned public frontend theme menu avatar image paths across `stacked_top_menu.twig` and `top_menu.twig` with `AF/Business` standards (`site.address ~ 'static/mod_user/' ~ session.image`).
  - Added user profile identity card (avatar, display name, handle, and account portal button) inside the mobile drawer navigation for authenticated sessions.

## v1.1.15 (2026-10-01)
- **User Registration Architecture & Interactive Security (`mod/mod_user/`, `templates/amida/mod_user/register.twig`, `templates/amida/static/css/register.css`, `templates/amida/static/js/register.js`)**:
  - Refactored user registration to strictly comply with the Code Separation Mandate by extracting inline styles and scripts into dedicated `templates/amida/static/css/register.css` and `templates/amida/static/js/register.js` assets.
  - Implemented interactive real-time password strength meter and validation requirements checklist (length, uppercase, lowercase, number, special character) with live visual feedback.
  - Added password match confirmation checking, phone/email validation, and real-time form state indicators.
  - Integrated internationalized UI copy (`have_account`, `login_now`, `back_to_home`, `password_requirements`, `rule_min_length`, `rule_uppercase`, `rule_lowercase`, `rule_number`, `rule_special`, `strength_label`) across Kurmanji (`ku`), English (`en`), and Turkish (`tr`).
  - Cleaned embedded `<br/>` HTML tags from core translation strings in `mod_user/lang/` to maintain clean separation between presentation and localized text.
  - Provided client-side localized validation dictionaries injected cleanly via data attributes and decoded in JavaScript.
- **Stacked Header & Broadsheet Navigation Architecture (`templates/amida/index/mod_menu/stacked_top_menu.twig`, `templates/amida/static/css/stacked-top-menu.css`, `templates/amida/static/js/top-menu.js`, `templates/amida/static/fonts/`)**:
  - Designed and integrated `stacked_top_menu.twig` and `stacked-top-menu.css` featuring an authentic newspaper masthead layout with embedded vector SVG brand identity.
  - Embedded local `Cloister Black` Gothic typography (`CloisterBlack.woff`, `CloisterBlack.ttf`) for authentic editorial branding without external font dependencies.
  - Enhanced headroom scroll physics in `top-menu.js` with directional scroll thresholds (`downThreshold`, `upThreshold`, `safeZone`) to eliminate header jitter and bounce during fast navigation.
  - Refactored mobile drawer gesture controls with touch swipe support, backdrop overlay handling, and keyboard accessibility (Escape to dismiss).
  - Integrated dual-input authentication bindings (`loginUsername`/`stackedLoginUsername`, `loginPassword`/`stackedLoginPassword`) in `login-modal.js`.
- **Homepage Layout Modernization & Code Cleanliness (`templates/amida/index/index.twig`, `templates/amida/layouts/base.twig`)**:
  - Replaced legacy non-English layout selectors (`orta`, `orta-sol`, `orta-sag`) with semantic English identifiers (`homepageHeroRow`, `homepageHeroMain`, `homepageHeroSidebar`) to align with repository design and naming standards.
  - Updated `templates/amida/layouts/base.twig` to incorporate the stacked broadsheet header and typography assets.
- **Brand Asset Modernization & Web Manifest (`static/mod_settings/`, `templates/amida/index/footer.twig`, `templates/amida/static/css/footer.css`)**:
  - Modernized website brand identities with vector SVGs (`amida_kurd_logo.svg`, `amida_kurd_logo.png`, `amida_kurd_full_logo.png`, `logo_square.png`, `logo_symbol.png`).
  - Updated `site.webmanifest` name and short name to "Amîda Kurd" with synchronized PWA icons (`favicon-96x96.png`, `apple-touch-icon.png`, `web-app-manifest-192x192.png`, `web-app-manifest-512x512.png`).
  - Upgraded footer brand masthead in `footer.twig` and `footer.css` using Cloister Black typography and dark mode inverted SVG filtering.
  - Staged deletion of obsolete legacy logo/icon assets (`templates/amida/static/img/logo*.png`, `templates/amida/static/img/logo*.svg`, `templates/amida/static/img/favicon.ico`).
- **Visual Polish & Card Styling (`templates/amida/static/css/hero-news-slider.css`, `templates/amida/static/css/latest-videos.css`)**:
  - Refined hero news slider with rounded corner radii (14px desktop / 10px mobile), refined borders, and dark mode box-shadow depth.
  - Aligned card border radius across latest video embed cards for visual consistency.
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.1.15`.

## v1.1.14 (2026-09-30)
- **Hero News Slider Swiper Runtime & Parity (`templates/amida/static/js/hero-news-slider.js`, `templates/amida/index/hero_slider.twig`, `templates/amida/static/css/hero-news-slider.css`)**:
  - Implemented standalone `templates/amida/static/js/hero-news-slider.js` providing full Swiper initialization with smooth fade transitions, autoplay (with pause on hover and tab visibility change), and full keyboard/touch support.
  - Added event delegation on slider controls (`.hero-news-swiper-button-prev`, `.hero-news-swiper-button-next`) ensuring navigation clicks work reliably across all rendered slide instances.
  - Added retry polling to safely instantiate Swiper when the library bundle finishes loading asynchronously in the browser.
  - Added `defer` attribute to `hero_slider.twig` script tag for clean non-blocking document evaluation.
  - Enhanced `hero-news-slider.css` with `pointer-events: none` on `.hero-slide__overlay` and automated control suppression for single-slide contexts (`.is-single-slide`).
- **Editorial Skills Suite (`.agents/skills/`)**:
  - Integrated specialized editorial skills `kevkolin` (multi-source research engine), `kevrast` (anti-hallucination and live citation gate), and `kevheng` (Kurmanji lexicon, orthography, and grammar verification).
- **Public Theme JavaScript & i18n Quality (`templates/amida/static/js/custom.js`, `templates/admin/mod_news/list.twig`)**:
  - Refactored legacy non-English script comments in `custom.js` into clean English documentation to pass repository `i18n-guard` with 0 errors.
  - Normalized translation key usages in admin templates to eliminate forbidden inline fallbacks.
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.1.14`.

## v1.1.13 (2026-09-28)
- **Optional Keywords & Validation Modernization (`mod_news`, `mod_news_category`)**:
  - Removed obsolete mandatory `required` constraint from `keywords` in both `add.validator.php` and `edit.validator.php` across `mod_news` and `mod_news_category`.
  - Upgraded `keywords` length validation to conditionally execute only when populated, allowing empty inputs while strictly enforcing the 255-character maximum.
  - Eliminated outdated `#Check Tcno` comments from validator classes to ensure 100% compliance with repository code separation and i18n language guards.
- **Kevanger Skill Metadata Specification (`.agents/skills/kevanger/SKILL.md`)**:
  - Refined draft staging guidelines to populate 3–5 relevant contextual keywords for complete search engine indexing and metadata parity while preserving optionality on the CMS form layer.
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.1.13`.

## v1.1.12 (2026-09-28)
- **Unified Author & Editor Profile Architecture (`mod/index/helper/mod_publication.helper.php`, `templates/`, `static/css/author.css`)**:
  - Refactored the `author()` controller action to authenticate and resolve profile records directly against the user model (`mod_user` joined with `mod_user_group`) rather than strictly depending on pre-existing records in `mod_publication`.
  - Prevented false 404 responses for editors, journalists, and registered contributors who author news articles or maintain accounts without prior column publications in the active language.
  - Implemented multi-card and tabbed layout separating published articles (`mod_publication`) and editorial news stories (`mod_news`) into dedicated presentation sections with live badge counts.
  - Designed automated tab heuristics that prioritize relevant content cards based on user group classification (e.g., editorial news for editors, opinion columns for authors) with explicit query parameter override support (`?tab=news`, `?tab=articles`).
  - Added semantic role badges to profile headers dynamically resolved from user group identifiers (`Site Management`, `Editor`, `Member`, `Author`).
  - Added localization keys across supported interface languages (`ku`, `en`, `tr`) for new user group roles and tab descriptors.
  - Enforced strict code separation with responsive and dark-mode compliant CSS in `author.css` free of inline style attributes and embedded script logic.

## v1.1.11 (2026-09-28)
- **News Detail Author Avatar Schema & Model Parity (`mod/mod_news/model/newses.model.php`, `installer/mod_news/newses.sql`)**:
  - Added explicit `$user_image`, `$group_name`, and `$category_name` properties to `newses_model` so the ORM dynamically hydrates user avatar images and group metadata from the SQL view.
  - Hardened `ensureViewExists()` with automatic column detection (`hasUserImageColumn()`) for both MySQL and SQLite, ensuring legacy `newses` views lacking `user_image` are seamlessly upgraded with `DROP VIEW IF EXISTS` and re-created with `mod_user.image AS user_image`.
  - Removed legacy non-English properties (`$groupad`, `$rcount`) to comply with strict English identifier standards and repository i18n guards.
  - Aligned installer view definition (`installer/mod_news/newses.sql`) with dual `category_name` and `categoryname` aliases for seamless backwards compatibility.

## v1.1.10 (2026-09-28)
- **Model Context Protocol (MCP) News Author Delegation (`lib/McpNewsTools.class.php`, `lib/McpServer.class.php`)**:
  - Added support for explicit author attribution (`user_id` and `username`) in `create_news` and `update_news` tool endpoints.
  - Enabled dynamic resolution of target author accounts from registered system users (`mod_user`) with active status verification, maintaining backward-compatible fallback to the acting authenticated user.
  - Updated tool schemas in `McpServer.class.php` and MCP server manifests to expose author delegation parameters.
- **Kevanger News Drafting & Curation Skill (`.agents/skills/kevanger/SKILL.md`, `.agents/INDEX.md`)**:
  - Implemented repository-local skill for automated fact-checking, multi-source Kurdish and international media investigation, and draft staging via MCP.
  - Integrated structured source verification matrix across verified Kurdish regional outlets (Rûdaw, Kurdistan24, VOA Kurdî, 964media, NRT, Shafaq, NPA, Hawar News, Bianet Kurdî, Hengaw, Mezopotamya Ajansı, official KRG portals) and international verification quotas across US (DYE), Continental Europe, UK, Israel, Russia, Turkey, and Iran.
  - Built-in automated Kurmanji linguistic pre-flight gate enforcing Bedirxan Latin orthography, diacritics, vocabulary conventions, and deck summary constraints (90–120 characters).
  - Integrated ethical image labeling distinguishing verified source photojournalism from synthetic illustrations.
  - Enforced human-in-the-loop draft staging (`status: "n"`) to prevent unverified publication.
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.1.10`.

## v1.1.9 (2026-09-26)
- **News Detail 24-Hour Editorial Sidebar Card (`templates/amida/index/mod_news/news.twig`, `static/css/news.css`)**:
  - Implemented real-time 24-hour news filtering (`news_24h`) displaying exclusively news published within the past 24 hours on the right side of the news detail page.
  - Excluded the current active article (`d.id`) from the 24-hour sidebar to prevent redundant display while reading.
  - Added dedicated right-hand sidebar card (`.news-24h-card`) featuring an animated live pulsing indicator dot (`.news-24h-pulse-dot`), header badge with total article count, and a beautifully formatted title `"24 Saetên Dawî"`.
  - Conditional rendering enforcement: if no news has been published within the last 24 hours, the card does not render and the layout gracefully falls back to the centered article shell (`justify-content-center`).
  - Added thumbnail media, headline links, and publication timeago indicators (`.news-24h-meta`) for every 24-hour story.
  - Added multi-language dictionary key `'last_24_hours'` across all 9 localized index language bundles (`ku`, `en`, `tr`, `de`, `fr`, `ar`, `ckb`, `sv`, `zza`).
  - Styled with sticky desktop positioning (`position: sticky; top: 1.5rem;`), mobile-friendly responsive breakpoints, dark mode token support, and impeccable design standards.
- **Publications Broadsheet Super Polish (`templates/amida/index/mod_publication/publications.twig`, `static/css/publications.css`)**:
  - Implemented semantic page hierarchy with accessible primary `h1` (`.publications-page-title`), subtitle, and publication counter badge (`.publications-count-badge`).
  - Integrated complete Open Graph and Twitter Card social metadata blocks (`og:type`, `og:title`, `og:description`, `og:image`, `twitter:card`).
  - Added Schema.org structured microdata for collection page (`CollectionPage`), breadcrumbs (`BreadcrumbList`), creative works (`CreativeWork`), and author profiles (`Person`).
  - Enforced zero CLS (Cumulative Layout Shift) with explicit aspect-ratio (16/9), image dimensions (`width="640"`, `height="360"`), and asynchronous lazy loading (`loading="lazy"`, `decoding="async"`).
  - Modernized category sidebar filter with desktop sticky positioning (`position: sticky; top: 1.5rem;`), active category indicator, pill badges, and mobile horizontal scrolling chips for touch devices.
  - Implemented comprehensive CSS design tokens (`--pub-ink`, `--pub-card-bg`, `--pub-accent`, etc.) with full dark mode (`html[data-bs-theme="dark"]`), high-contrast focus rings (`:focus-visible`), and reduced motion media queries.
  - Synchronized `static/css/publications.css` with active theme stylesheet `templates/amida/static/css/publications.css`.
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.1.9`.

## v1.1.8 (2026-09-26)
- **Brand Asset Generation & Logo Management Modal Parity (`lib/BrandAssetGenerator`, `modal_theme_logo.js`, `mod_settings`)**:
  - Resolved fatal uncaught `Error: Call to undefined method BrandAssetGenerator::ensureThumbnailsExist()` triggering `Çewtiyek çêbû.` failure in admin logo and icon management modal (`#logo-icon-management-modal`).
  - Upgraded `lib/BrandAssetGenerator.class.php` to include complete multi-size thumbnail pipeline (`ensureThumbnailsExist`, `generateMultiSizeThumbnails`, `generateRectThumbnail`, `generateSquarePng`, `generateRectPng`, `ensureThumbDirectory`, `ensureManifestExists`) with robust GD library fallback when Imagick is unavailable.
  - Aligned `mod/mod_settings/index.php` try-catch blocks to catch `Throwable` instead of `Exception`, ensuring unexpected image processing errors fail gracefully without crashing JSON AJAX responses.
  - Updated `templates/admin/assets/js/modals/modal_theme_logo.js` with module registration (`loadedModules['modal_theme_logo']`), URL/locale-aware language resolution (`getLogoModalLang()`), POST request handling with `FormData`, Material 3 design token styling, and asset hierarchy alignment.
- **Latest News Broadsheet Super Polish (`templates/amida/index/mod_news/latest_news.twig`, `templates/amida/static/css/latest-news.css`)**:
  - Elevated editorial card grid with semantic Schema.org microdata (`NewsArticle`, `headline`, `image`, `datePublished`, `description`, `url`).
  - Integrated dynamic media type indicator badges (`.latest-news-type-badges`) for video (`.latest-news-type-badge-video`) and gallery (`.latest-news-type-badge-gallery`) content.
  - Enforced CLS layout stability with responsive dimension attributes (`width="640"`, `height="360"`), `loading="lazy"`, and `decoding="async"`.
  - Refined editorial card action with accessible text link `.latest-news-read-more` (`Zêdetir Bixwîne`), high-contrast focus rings (`:focus-visible`), and reduced motion media queries (`prefers-reduced-motion`).
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.1.8`.

## v1.1.7 (2026-09-26)
- **Open Graph & Social Share Preview Image Parity (`templates/amida/index/`)**:
  - Resolved missing social media preview image issue on WhatsApp, Facebook, X (Twitter), and Telegram by eliminating artificial `.webp` to `.jpg` string replacements across detail templates (`mod_news/news.twig`, `mod_content/content.twig`, `mod_page/page.twig`, and `mod_announcements/announcement.twig`).
  - Switched `og:image`, `og:image:secure_url`, `link[rel="image_src"]`, and `twitter:image` to reference authentic uploaded image assets directly (`.webp`), resolving CDN 500 errors caused by requests for non-existent `.jpg` files.
  - Dynamically configured `og:image:type` to match actual asset MIME types (`image/webp`, `image/png`, `image/jpeg`).
  - Standardized landscape Open Graph dimensions (`og:image:width: 1200`, `og:image:height: 630`) for primary content images and square fallback dimensions (`300x300`) for site logo fallbacks, in full parity with `mod_publication`.
  - Added accessible `og:image:alt` tag mapped to content title for crawler and accessibility compliance.
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.1.7`.

## v1.1.6 (2026-09-26)
- **Production MCP Deployment & Admin Search Keywords Parity (`amidakurd.net`)**:
  - Expanded search index keywords for `mod_mcp` in `mod/mod_user/index.php` (`mcp`, `api`, `key`, `keys`, `mifte`, `anahtar`, `token`, `ai`, `gemini`, `claude`, `antigravity`) to ensure instant discovery in the mobile administration control panel (`/mod_user/panel`).
  - Executed automated database bootstrap and schema synchronization on production (`tasks/mcp_bootstrap.php`), registering `mod_mcp` tables, ACL roles, and super-admin group permissions.
  - Successfully connected Antigravity MCP bridge to production endpoint `https://amidakurd.net/mcp.php`, verifying real-time JSON-RPC 2.0 communication across 140 tools and live article querying (`list_news` returning 8,080 articles).
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.1.6`.

## v1.1.5 (2026-09-26)
- **Model Context Protocol (MCP) Streamable HTTP Server & Domain Tooling**:
  - Implemented root machine-facing MCP endpoint `mcp.php` over Streamable HTTP transport, strictly conforming to JSON-RPC 2.0 and MCP specifications with error suppression and output buffering recovery.
  - Implemented core MCP helper `lib/McpCrudToolsBase.class.php` providing ORM model lookups, permission checks, base64 image decoding/resizing pipeline, and bridge relations cleanup.
  - Implemented editorial news domain tooling `lib/McpNewsTools.class.php` for `mod_news` and `mod_news_category`, featuring 10 full CRUD tools, 90-120 character deck summary enforcement, SEO slug generation, and `{lang}/newses/{seo}` menu integration.
  - Added domain tool implementations `lib/McpAnnouncementTools.class.php` and `lib/McpImageCategoryTools.class.php`.
  - Implemented comprehensive `lib/McpServer.class.php` configured as `amed-rojname-mcp`, registering 140 tools across all active Rojname CMS domains (`news`, `publication`, `content`, `announcements`, `page`, `slider`, `headline`, `gallery`, `video`, `faq`, `document`, `attachment`, `imagecategories`, `survey`, `popup`, `redirect`, `menu`, `settings`, `seo`, and `acl`).
  - Added self-healing admin module `mod/mod_mcp` with database models, installer schemas (`installer/mod_mcp/`), mobile-first phone templates (`templates/admin/mod_mcp/list.twig`), and decoupled assets (`templates/admin/assets/css/mod_mcp.css`, `templates/admin/assets/js/mod_mcp.js`).
  - Integrated admin sidebar navigation in `mod/mod_user/index.php` and complete multi-language label coverage across all 9 locales in `mod/management/lang/`.
  - Added automated test harnesses `tasks/mcp_bootstrap.php` and `tasks/mcp_smoke.php` (verifying 30/30 test assertions passed with 0 failures).
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.1.5`.

## v1.1.4 (2026-09-26)
- **Framework Parity & Fatal Visibility Error Resolution (`Helper::time_ago`, `mod_news_helper`)**:
  - Resolved fatal inheritance visibility error `Fatal error: Access level to mod_news_helper::formatRelativeTime() must be public (as in class indexBase)` in `mod/index/helper/mod_news.helper.php`.
  - Harmonized with `AF/Business` framework core standard by removing `formatRelativeTime()` from `mod/index/indexBase.php` to prevent restrictive method signatures on subclasses.
  - Implemented centralized `Helper::time_ago($datetime, $lang = null)` in `lib/Helper.class.php` in strict parity with `AF/Business`, supporting `DateTime` instances, unix timestamps, and string dates with automated fallback dictionary lookup.
  - Refactored `mod_news_helper`, `mod_hero_slider_helper`, and `mod_publication_helper` to delegate cleanly to `Helper::time_ago($datetime, $this->lang['index'] ?? null)` with `private` visibility matching `AF/Business`.
  - Aligned Twig filter `time_ago` in `lib/TwigLibs.class.php` to supply template context language directly to `Helper::time_ago($datetime, $lang)`.
  - Standardized relative time localization dictionaries (`time_ago`) across all 9 supported languages (`ku`, `en`, `tr`, `de`, `fr`, `ar`, `ckb`, `sv`, `zza`) in `mod/index/lang/` using canonical `AF/Business` keys (`second`, `seconds`, `minute`, `minutes`, `hour`, `hours`, `day`, `days`, `month`, `months`, `year`, `years`, `just_now`).
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.1.4`.

## v1.1.3 (2026-09-26)
- **Internationalization & Relative Time Localization (`time_ago` / "Hour Ago")**:
  - Added standardized multi-language relative time dictionary (`time_ago`) across all 9 supported languages (`ku`, `en`, `tr`, `ar`, `ckb`, `de`, `fr`, `sv`, `zza`) in `mod/index/lang/`, providing localized formats for minutes, hours, days, months, years, and just now.
  - Implemented `Helper::format_relative_time($datetime, $trans = null)` in `lib/Helper.class.php`, supporting `DateTime` objects, timestamp strings, and integer epochs with automated dictionary resolution and English fallback.
  - Registered `time_ago` Twig filter in `lib/TwigLibs.class.php` for seamless template-level relative time formatting (`{{ date|time_ago }}`).
  - Centralized `formatRelativeTime()` in `mod/index/indexBase.php` and refactored `mod_publication.helper.php` and `mod_hero_slider.helper.php` to eliminate duplicated hardcoded English time strings.
  - Updated `@templates/amida/index/mod_publication/latest_authors.twig` to display fully localized relative timestamps with graceful fallback to `author.created_at|time_ago`.
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.1.3`.

## v1.1.2 (2026-09-26)
- **Editorial Public Theme Homepage & Latest Videos Super Polish & Strict Code Separation (`templates/amida/index/`)**:
  - Overhauled `latest_videos.twig` into a high-taste broadsheet editorial video showcase featuring responsive 16:9 media viewports, centered glassmorphic play button overlays with crimson hover expansion (`#dc2626`), duration badges, and clamped editorial typography (`Literata`, serif).
  - Linked `latest_videos.twig` directly into homepage hero column (`templates/amida/index/index.twig`) with `{ limit: 3 }`, and forwarded legacy `hero_followup.twig` for full backwards compatibility.
  - Perfectly eliminated the blank vertical space underneath the authors column (`#orta-sag`) by expanding active latest authors from 4 to 6 items (`_latest_authors(6)`) in `latest_authors.twig` and configuring `height: 100%` with flex distribution on `#latest-publication-authors` in `latest-authors.css`.
  - Removed the unprofessional ~112px gap between `#orta` (videos/authors) and the news category columns by removing redundant `p-4` padding from `.category-homepage-section` in `index.twig`, stripping duplicate `border-top` and margin from `.category-columns-row` in `latest-news.css`, and calibrating `#orta` `margin-bottom` to a clean `1.25rem`.
  - Overhauled the public newspaper homepage (`http://localhost:8080/ku/`) into a modern broadsheet editorial layout with strict code separation (Rule 2), eliminating all inline `<style>` and `<script>` blocks from Twig templates.
  - Removed 140 lines of embedded `<style>` in `hero_followup.twig` and migrated all styles into decoupled stylesheet `templates/amida/static/css/latest-videos.css` and `static/css/latest-videos.css`.
  - Removed 56 lines of repeated `<style>` blocks in `category_news_row.twig` and consolidated all broadsheet category column styles, vertical desktop dividers, and responsive article layouts into `templates/amida/static/css/latest-news.css` and `static/css/latest-news.css`.
  - Cleaned up `index.twig`: removed inline `style="margin-top: 20px !important;"` on `#orta`, removed inline `<style>` media query for `#heroNewsSlider`, and cleared obsolete jQuery `#mainSlider` initialization block.
  - Replaced stretched fallback logo with an editorial gradient book emblem (`hero-slide__placeholder`) in `hero_slider.twig` and `hero-news-slider.css` for publication slides without featured images.
  - Unified article metadata in `category_news_row.twig`: replaced duplicate timestamps with clean semantic tags (`.nyt-article-date`, `.nyt-article-reading-time`, `.nyt-article-timeago`).
  - Refactored `latest-authors.css` and `latest_authors.twig` with theme-adaptive CSS custom properties, removing hardcoded black `#121212` / `#000` colors and `.text-dark` classes to ensure full dark mode parity.
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.1.2`.

## v1.1.1 (2026-09-26)
- **Publication Open Graph & Social Share Preview Author Thumbnail Optimization (`templates/amida/index/mod_publication/publication.twig`)**:
  - Added dedicated fallback Open Graph dimensions (`og:image:width: 300`, `og:image:height: 300`) for author profile pictures and site fallback assets, enabling instant thumbnail display on WhatsApp and Meta link scrapers without requiring asynchronous dimensions fetching.
  - Added `og:image:alt` tag mapped to the article title for improved accessibility and crawler compliance.
  - Dynamically switched `twitter:card` between `summary_large_image` (for wide publication images) and `summary` (for square author avatars and logos) to prevent distorted cropping on social platforms.
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.1.1`.

## v1.1.0 (2026-09-26)
- **Publication Open Graph & Social Share Preview Author Image Fallback (`templates/amida/index/mod_publication/publication.twig`)**:
  - Enhanced social share metadata resolution for publication articles: when a publication has no dedicated cover image (`d.image`), the template now automatically resolves and prioritizes the author's profile photo (`d.user_image`) before falling back to the site logo.
  - Resolved missing writer preview on WhatsApp, Telegram, Facebook, and Twitter link sharing for author column articles and essays.
  - Eliminated broken `.webp` to `.jpg` string replacements that produced HTTP 500 / 404 dead image links, preserving canonical WebP asset paths supported across modern crawlers.
  - Conditionally rendered landscape Open Graph dimensions (`og:image:width: 1200`, `og:image:height: 630`) only when dedicated publication images exist, preventing invalid aspect ratio stretching for portrait author photos.
  - Enriched Schema.org `Article` structured data to dynamically reference the resolved share image and populated `author.image` with the author's avatar URL.
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.1.0`.

## v1.0.99 (2026-09-25)
- **Editorial Public Theme Latest News Grid Super Polish (`templates/amida/index/mod_news/latest_news.twig`, `templates/amida/static/css/latest-news.css`, `static/css/latest-news.css`)**:
  - Overhauled the latest news widget with modern semantic HTML5 markup, strict separation of concerns, and full dark mode styling.
  - Extracted all inline styles and inline JavaScript into dedicated modern CSS stylesheets (`templates/amida/static/css/latest-news.css` and `static/css/latest-news.css`), strictly adhering to repository code separation mandates.
  - Replaced client-side script date calculations with native backend relative time support (`published_ago`), and calculated estimated reading time cleanly.
  - Implemented responsive cards with 16:9 media viewports, image hover scaling, timeago overlay badges, metadata badges, and clean editorial typography.
  - Added dedicated fallback empty state representation with localized strings.
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.0.99`.

## v1.0.98 (2026-09-25)
- **Editorial Public Theme Latest Videos Grid Super Polish (`templates/amida/index/mod_video_embed/latest_videos.twig`, `templates/amida/static/css/latest-videos.css`, `static/css/latest-videos.css`)**:
  - Overhauled the latest videos widget with modern semantic HTML5 markup, full accessibility semantics (`aria-labelledby`, `<article>`, `<header>`, `<time>`), and dark mode theme compatibility.
  - Implemented decoupled, responsive modern CSS card styling featuring 16:9 media viewports, smooth hover micro-interactions, centered glassmorphic play button overlays, duration badges, and clean metadata.
  - Removed legacy non-English class markers (`koberz_3_2_2_1`, `index_videolar_*`) and obsolete element attributes.
  - Added dedicated empty state representation with localized fallback strings.
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.0.98`.

## v1.0.97 (2026-09-25)
- **Fix Database Column Mismatch on Publication Insert (`mod/mod_publication/model/mod_publication.model.php`, `installer/mod_publication/mod_publication.sql`)**:
  - Removed undeclared `menu` property from `mod_publication_model` to resolve the fatal `Database Error: SQL Query Problem: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'menu' in 'INSERT INTO'` encountered on `mod_publication/add`.
  - Cleaned up installer schema definition in `installer/mod_publication/mod_publication.sql` to match production table columns without the phantom `menu` column.
- **Robust Field Mapping Defensive Guard in Base Controller (`lib/BaseModuleController.class.php`)**:
  - Restored `property_exists($model, $field)` checks in `BaseModuleController::processFieldData` to prevent form fields submitted from UI overlays or wizards (such as `menu` input) from being dynamically assigned to models when not defined as valid model columns.
  - Eliminated PHP 8.2+ dynamic property deprecation notices.
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Maintained framework version constant and fallback baseline at `1.0.97`.

## v1.0.96 (2026-09-25)
- **Admin Publication Category Binding & Wizard State Fix (`mod/mod_publication/index.php`, `templates/admin/`)**:
  - Resolved missing category options in the mobile admin publication wizard overlay (`mod_publication/add`, `wizard_step_2`).
  - Updated `mod_publication_mod::getFormData()` to populate `$data['categories']` alongside `$data['categories_flat']`, ensuring template bindings in `sheets_extra.twig`, `edit.twig`, and `list.twig` receive the category dataset.
  - Excluded language-level root container categories (`root != 'y'`) so that only actionable editorial categories are rendered.
  - Preserved original category name casing in `templates/admin/partials/sheets_extra.twig` and `templates/admin/assets/js/modals/modal_user_menu.js` during selection and label synchronization.
- **Bridge Integration Defensive Database Guard (`lib/BaseModuleController.class.php`)**:
  - Implemented adapter-aware `tableExists()` check in `BaseModuleController::ensureBridgeModule()` prior to querying bridge tables.
  - Eliminated fatal unhandled `DatabaseException: Base table or view not found: 1146 Table doesn't exist` when registering bridge integrations (`mod_faq_mod`, `mod_gallery_mod`, `mod_attachment_mod`) on fresh or unmigrated databases.
  - Cached table existence results in-memory to prevent redundant schema introspection queries during lifecycle initialization.
  - Ensured graceful fallback and silent skipping when optional bridge tables have not yet been provisioned in the database.
- **Material 3 Admin User List Super Polish & Architecture Modernization (`templates/admin/mod_user/`, `templates/admin/assets/`)**:
  - Overhauled user administration list view (`mod_user/list`) with a phone-first Material 3 card interface.
  - Implemented real-time interactive status filter chips (All, Active, Pending) with live count indicators.
  - Redesigned user cards featuring monogram fallback avatars, group role badges, contact metadata (username, email, phone, registration date), and tap-safe action menus (min 44x44px target area).
  - Enforced strict code separation by extracting all styles into `templates/admin/assets/css/mod_user.css` and all interaction logic into `templates/admin/assets/js/mod_user.js`, completely eradicating inline `<style>`, `<script>`, `style="..."`, and `onclick="..."` attributes.
  - Wired mobile action sheet context menu and bottom action toolbar with SweetAlert2 confirmation dialogs for activation, deactivation, and deletion.
- **Backend User Statistics & Dataset Enrichment (`mod/mod_user/index.php`)**:
  - Enhanced `list()` method to compute real-time user metrics (`total_users`, `active_users`, `pending_users`).
  - Enriched `datatable_json()` payload to return user group names, registration timestamps, and phone numbers alongside core identity fields.
  - Enhanced query handling for composite identity searches and flexible radio status filters (`pending`, `y`, `n`, `a`).
- **Framework AJAX Request Detection & Exception Handling (`lib/Req.class.php`, `mod/mod_user/model/user.model.php`)**:
  - Implemented canonical `Req::is_Ajax()` utility method detecting XMLHttpRequest headers, JSON Accept headers, and explicit AJAX query flags.
  - Resolved fatal `Call to undefined method Req::is_Ajax()` error when handling unauthenticated or expired session redirects.
  - Enforced clean JSON 401 unauthorized envelopes for asynchronous API requests.
- **Framework Version Baseline (`config/site.config.php`, `config/site.config.example.php`)**:
  - Maintained framework version constant and fallback baseline at `1.0.96`.

## v1.0.95
- **Unicode Multilingual Name Validation & Member Registration Fix (`lib/Valid_Core.php`, `mod/mod_user/validator/`)**:
  - Resolved `valid_trchar Validator Function Not Found` fatal error encountered during new user registration (`mod_user/add` and `mod_user/register`).
  - Standardized `mod/mod_user/validator/mod_user.validator.php` and `mod/mod_user/validator/register.validator.php` to use the canonical multilingual `alpha_unicode` rule for `name` and `last_name` validation.
  - Implemented backward-compatibility bridge `valid_trchar($arg)` in `lib/Valid_Core.php` delegating directly to `valid_alpha_unicode($arg)` to guard against legacy validator invocations across the ecosystem.
  - Standardized comments and removed obsolete Turkish markers in `register.validator.php`.
- **Editorial Public Theme Latest Publications Grid Modernization (`templates/amida/index/mod_publication/latest_publications.twig`)**:
  - Overhauled latest publications section with decoupled stylesheet (`templates/amida/static/css/latest-publications.css`, `static/css/latest-publications.css`).
  - Enhanced layout with clean responsive cards, article metadata (creation date, summary/deck fallback excerpts, placeholder states for missing images, and mobile footer action).
  - Enforced strict separation of concerns with zero inline styles and semantic accessible markup.
- **Framework Version Bump (`config/site.config.php`, `config/site.config.example.php`)**:
  - Bumped framework version constant and fallback baseline to `1.0.95`.

## v1.0.94
- **Proxy-Aware HTTPS Detection & Infinite Redirect Loop Resolution (`config/site.config.php`, `config/site.config.example.php`)**:
  - Implemented comprehensive proxy-aware HTTPS detection inspecting `HTTP_X_FORWARDED_PROTO`, `HTTP_X_FORWARDED_SSL`, `HTTP_CF_VISITOR`, `SERVER_PORT == 443`, and `HTTPS !== 'off'`.
  - Prevented infinite 301 redirection loops (`ERR_TOO_MANY_REDIRECTS`) in production environments operating behind SSL-terminating reverse proxies, Cloudflare, load balancers, and KasServer (All-Inkl).
  - Maintained automated HTTP-to-HTTPS 301 enforcement for genuine unencrypted HTTP requests while safeguarding localized environment configurations.
  - Bumped framework version constant and fallback baseline to `1.0.94`.
- **Installation Gate Modernization & AF/Business Architecture Parity (`index.php`)**:
  - Aligned entry point architecture with `AF/Business` standards by introducing output buffer clearing (`ob_end_clean()`) prior to redirect and error response headers.
  - Dynamically resolved canonical `$installerUrl` using configured `$siteConfig['address']` with clean trailing slash trimming.
  - Implemented structured JSON 503 error envelope for asynchronous AJAX requests when installation state is unfulfilled.
  - Standardized all entry point metadata headers, error handler comments, and configuration comments into clean canonical English.

## v1.0.93
- **Configuration Architecture Modernization & AF/Business System Parity**:
  - **Dynamic Environment & HTTPS Enforcement (`config/site.config.php`, `config/site.config.example.php`)**:
    - Introduced robust `$isLocal` detection supporting `localhost`, `127.0.0.1`, and `pinggy.link`.
    - Added automated HTTP-to-HTTPS 301 redirection for live non-CLI requests in production environments.
    - Implemented dynamic CDN subdomain generation (`static.` and `assets.`) derived from active host `$baseDomain`.
    - Integrated automated active framework version resolution dynamically parsed from `docs/VERSION.md` with graceful fallback.
    - Preserved project identity baselines (`$site['lang'] = 'ku'`, `$site['build'] = 'Rojname CMS'`, `$site['app_template'] = 'amida'`) and localized environment overrides via `site.config.local.php`.
  - **Routing Enhancements for Existing Modules (`config/url.config.php`)**:
    - Enhanced announcement slug routing with trailing dash edge-case support (`announcement/(:num)-`).
    - Fixed single video routing contract from legacy plural `index/videos/$1` to canonical `index/video/$1`, adding `video/`, `video/(:num)-`, and `video/(:any)`.
    - Expanded content routing with `content/detail/(:any)`, `content/(:any)`, `contents/(:num)`, and `category/(:num)`.
    - Added canonical detail routes for news (`news/detail/(:any)`) and publications (`publication/detail/(:any)`).
    - Registered asynchronous AJAX endpoints for `mod_redirect` (`ajax_get_redirects`, `ajax_add_redirect`, `ajax_edit_redirect`).
    - Strictly excluded uninstalled foreign modules and routes (`mod_product`, `mod_service`, `mod_project`, `mod_client`, `mod_reference`, `mod_appointment`, `mod_reservation`, `mod_test`, legal, cookie policy, LLMs).
  - **Localization & Autoload Standardization (`config/i18n.config.php`, `config/autoload.config.php`, `config/session.config.php`, `config/db.config.example.php`)**:
    - Added `'ru'` (Russian) to `active_langs` in `config/i18n.config.php` matching existing repository translations.
    - Standardized metadata headers (`User: AKM`) and clarified English comments across autoload, session, and database example configuration files.

## v1.0.92
- **Super Polish Top Navigation Masthead & Public Theme Baseline**:
  - Fully modernized `templates/amida/index/mod_menu/top_menu.twig` with clean semantic markup, accessible ARIA attributes, and strict code separation (zero inline styles or inline scripts).
  - Redesigned `templates/amida/static/css/top-menu.css` adhering to high-end editorial newspaper masthead aesthetics:
    - Balanced Gothic brand typography (`Grenze Gotisch`) with proportional image logo rendering.
    - Sleek pill search input with smooth focus expansion, nested icon button, and accessible keyboard interactions.
    - Polished `.btn-header-action` controls for desktop language selection and dark mode toggle.
    - Editorial-grade auth action buttons (`.btn-header-login` ghost button and `.btn-header-register` solid ink button) with unified 38px touch/click target heights.
    - Polished glassmorphic dropdown cards with refined shadows, subtle borders, and smooth entrance transitions.
    - Responsive mobile off-canvas drawer with smooth slide-in, blurred backdrop overlay, and zero horizontal viewport peeking (`transform: translateX(100%)`).
  - Added template fallback guard in `mod/index/indexBase.php` to ensure `app_template` dynamically falls back to an existing theme folder (e.g. `amida`) when database contains legacy values.
  - Scoped primary button styles in `templates/amida/static/css/login-modal.css` to `#loginModal` to prevent CSS leaking.
- **Cache System Modernization & Undeclared Static Property Fix**:
  - Replaced legacy undeclared static property `Cache::$sure` with standard `Cache::$ttl` (`Cache::$ttl = 3600;` and `Cache::$ttl = 86400;`) across `mod/index/helper/search.helper.php` and `mod/index/indexBase.php`.
  - Migrated legacy `Cache::exists_cache()` and `Cache::get_cache()` to canonical `Cache::exists()` and `Cache::get()` with explicit key passing.
  - Standardized search cache keys to English `-search-` format.
  - Standardized ACL cache keys to `"permission-acl-" . $acl_id` matching `mod_acl` cache invalidation rules.
- **RedisCache Autoload & Search Query Standardization (AF/Business Parity)**:
  - Registered `RedisCache` and `GoogleIndexingService` in `$autoload['libfile']` and `google` in `$autoload['config']` in `config/autoload.config.php`.
  - Created `config/google.config.php` providing Redis connection and Google API configurations.
  - Added defensive bootstrap check in `lib/Cache.class.php` ensuring `RedisCache` is loaded prior to static calls.
  - Standardized search input name from legacy `kelime` to canonical `query` across `templates/amida/index/mod_menu/top_menu.twig`, `templates/amida/index/banner.twig`, and `templates/amida/index/search.twig`.
  - Updated `mod/index/helper/search.helper.php` and `mod/index/index.php` to accept canonical `query`, `keyword`, and `q` parameters with graceful fallback support for legacy `kelime`.
  - Declared `public $url;` property in `mod/index/indexBase.php`.

## v1.0.91
- **Frontend Theme Modernization & Baseline Theme Renamed to "amida"**:
  - Renamed the baseline public frontend theme directory from `templates/default` to `templates/amida`.
  - Updated configuration in `config/site.config.php` to use `amida` as `$site['app_template']`.
  - Updated framework template loader fallbacks in `lib/Amed.class.php` and `lib/TwigTemplate.class.php` to resolve `templates/amida`.
  - Updated database installer schemas and controller defaults across `mod/mod_settings/` and `installer/mod_settings/` to default `app_template` to `'amida'`.
  - Standardized theme markup IDs (`#amidaMainHeader`, `#amidaMobileMenu`, `#amidaBottomMenu`) and synchronized top menu navigation scripts in `templates/amida/static/js/top-menu.js`.
  - Translated Turkish documentation in `templates/amida/layouts/base.txt` to English and updated theme references across agent documentation and E2E test suites.
- **Full Editorial & Core System Modernization Parity with AF/Business**:
  - **Phase 1 Editorial Modules Synced**: Synchronized controllers, models, installer definitions, and phone-first administrative view templates for `mod_news`, `mod_news_category`, `mod_publication`, `mod_publication_category`, `mod_content`, `mod_content_category`, `mod_page`, and `mod_announcements`. Migrated to standard `Role::` and `$this->theme` / `$this->view`.
  - **Phase 2 Media Modules Synced**: Synchronized controllers, models, installer definitions, and templates for `mod_video`, `mod_video_embed`, `mod_video_embed_category`, `mod_gallery`, `mod_gallery_mod`, `mod_attachment`, `mod_attachment_mod`, `mod_document`, `mod_imagecategories`, and `mod_headline`.
  - **Phase 3 System & Settings Modules Modernization**:
    - Ported canonical English-only settings controller (`mod_settings/index.php`) and sub-page templates: `cookie_policy.twig`, `working_hours.twig`, and `affiliations.twig`.
    - Modernized `mod_menu`, `mod_slider` (adapted to link editorial articles `mod_publication` instead of forbidden `mod_product`), `mod_user`, `mod_user_group`, `mod_user_pm`, `mod_acl`, `mod_seo`, `mod_redirect`, `mod_survey`, and `mod_popup`.
    - Modernized `mod/tools/index.php` with `Role::` permission verification and standard robots indexing directives.
  - **Phase 4 Administrative Partials & Navigation Layout Cleanliness**:
    - Synchronized administrative partials (`fab.twig`, `header.twig`, `sheets_extra.twig`, `wizard_content.twig`) and completely cleansed them of forbidden commercial module bindings (`mod_product`, `mod_service`, `mod_project`, `mod_appointment`, `mod_test`, `mod_hr`).
    - Standardized `templates/admin/partials/sidebar.twig` and `mod/management/lang/en.php` to zero-alias canonical English keys (`site_home`, `remote_video_category`, `headline_management`, `full_headline_management`, `gallery_modules`, `file_modules`).
    - Cleaned residual references to `mod_reservation` in `header.twig`, `mod_organization_type` in `legal_info.twig` and `mod_settings/index.php`, `mod_assessment` routes in `url.config.php`, `mod_category` health checks in `management/index.php`, and commercial seeds (`mod_service`, `mod_product`, `mod_project`, `mod_reference`) in `mod_gallery_mod` and `mod_attachment_mod` installers.
    - Updated `AdminTemplate.class.php` to automatically bootstrap required management and editorial language dictionaries.
  - **Quality Assurance & Verification**:
    - `tools/module_boundary_guard.py`: 0 violations across 16 forbidden modules.
    - `tools/cross_repo_sync.py`: 1,250 identical files, 0 drift, 0 missing.
    - `tools/audit_modernization.py`: 100% compliance across all active modules.
    - Zero active forbidden module occurrences, zero `Roll::` occurrences, and zero syntax errors across all application PHP files.

## v1.0.90
- **AF/Business Canonical Parity & Editorial Modernization Overhaul**:
  - **Shared-Core Synchronization & Zero-Drift Parity**: Synchronized all core libraries (`lib/`), installer engine (`installer/app/`, `installer/index.php`), and quality tooling with `AF/Business`, achieving 100% parity (808 identical files, 0 drift, 0 missing in `cross_repo_sync.py`).
  - **Root Workspace Security & Hygiene Cleanup**: Permanently eradicated security hazards and binary bloat from the repository root: purged standalone `adminer.php` (507 KB), `composer.phar` (3.2 MB), `ip.php`, `clear_cache.php`, and `rector.php`.
  - **Domain Boundary Guard & Exclusion Enforcement**: Configured `tools/module_boundary_guard.py` enforcing strict boundary isolation and forbidding 14 commercial, enterprise, and corporate modules (`mod_product`, `mod_product_category`, `mod_service`, `mod_service_category`, `mod_client`, `mod_reference`, `mod_project`, `mod_project_category`, `mod_appointment`, `mod_reservation`, `mod_hr`, `mod_organization_type`, `mod_test`, `mod_assessment`).
  - **Legacy Module Purge**: Deprecated and purged obsolete generic `mod_category` (migrated to dedicated module taxonomies) and incomplete `mod_hero_slider` model directory; purged forbidden `mod_assessment` references from `InstallerModuleCatalog.php`.
  - **Core Library Modernization**: Eradicated legacy `$this->tema` property from `BaseModuleController.class.php`, integrated polymorphic `$has_faq` bridge and `ensureBridgeIntegrations()`, synchronized dynamic cookie theme negotiation (`$_COOKIE['theme']`) and URL bounds checking in `Amed.class.php`, added PDO `get_connection()` accessor in `Database.php`, and ported `GoogleIndexingService.class.php` and `RedisCache.class.php`.
  - **Admin View Restoration & Strict Code Separation**: Ported missing administrative templates for `mod_survey`, `mod_popup`, `mod_faq_mod`, and `layout_blank.twig`. Modernized `mod_survey` controller to extend `BaseModuleController`. Extracted all inline styles and event handlers into external `mod_survey.css` and `mod_survey.js`, achieving zero violations in `code_separation_guard.py`.
  - **Admin UI & Decoupled Assets Synchronization**: Synchronized mobile-first `layout.twig` with `window.adminLang` context and ported modular administrative CSS/JS stylesheets across all editorial modules.

## v1.0.89
- **FAQ Duplicate Question Prevention & Validation**: Enforced duplicate question prevention across both backend controllers (`ajax_save`, `ajax_batch_save`) and frontend JavaScript (`saveSingleItem`, `saveAll`) in `mod_faq`:
  - Added duplicate question check per entity (`mod_name`, `vid`, `lang`) before saving single or batch FAQ items.
  - Added localized validation strings (`question_duplicate` and `duplicate_question`) across all supported language files (`en`, `tr`, `ku`, `ar`, `ckb`, `de`, `fr`, `zza`).
  - Added instant client-side notification toast and input focus on duplicate question attempts.
- **Framework Core Synchronization, Non-English Element Eradication & Sibling Parity**: Synchronized shared-core architecture and eradicated legacy Turkish identifiers, files, and classes across the codebase in full parity with `Business`:
  - Modernized framework initialization entrypoint: replaced `koberz.init.php` with canonical `init.php` across all entrypoint scripts (`index.php`, `modules.php`, `js.php`, `cron.php`, `resize.php`, `search.php`, `sitemap.php`, `installer/index.php`).
  - Standardized Role architecture: created canonical `lib/Role.class.php`, `mod/mod_user/model/mod_user_role.model.php`, and `installer/mod_user/mod_user_role.sql`, and eradicated deprecated `lib/Roll.class.php`, `mod_user_roll.model.php`, and `mod_user_roll.sql`.
  - Survey module architecture & model overhaul: created canonical `mod/mod_survey/model/survey_option.model.php` and `surveyselect.model.php`, updated validators (`add.validator.php`, `edit.validator.php`), and purged legacy Turkish model `secenek.model.php` and methods (`soru`, `secenekler`, `anket`, `oyver()`).
  - Attachment module schema standardization: renamed schema definition to canonical `mod_attachment_mod.sql` and purged `mod_file_mod.sql`.
  - Validator naming standardization: renamed all Turkish validator files (`ekle.validator.php`, `duzenle.validator.php`) to standard English (`add.validator.php`, `edit.validator.php`) across `mod_acl`, `mod_gallery_mod`, `mod_headline`, `mod_document`, `mod_popup`, and `mod_survey`.
  - Flexigrid vector SVG standard & raster asset purge: replaced legacy PNG icons (`takip.png`, `vasif.png`, `yenile.png`, `yonet.png`) with modern vector SVGs (`attribute.svg`, `manage.svg`, `refresh.svg`, `tracking.svg`), updated `flexigrid.css`, and purged stale backup files (`confirm copy-yedek.css`, `datepicker111.css`, `theme11.css`, `index-test.php`, `untitled-1.png`).
  - Legacy word & alias purge: completely eliminated `Kayit::` class aliases from `config/db.config.php` and codebase, standardized `$toplam` to `$total` across datatable models, updated `public $tablolar` / `$this->tablolar` to `public $tables` / `$this->tables` in installer module classes, and replaced `oke()` with `success()`.
  - Twig hero slider syntax fix: standardized background video embed dynamic URL query parameter binding across all public frontend themes (`'?' in item.embed_url ? '&' : '?'`).
  - Automated quality guard: added `tools/code_separation_guard.py` to enforce strict code separation.


- **Synchronized Forgot-Password and Login Template**: Synchronized the default login template (`templates/default/mod_user/login.twig`) from Property to clean up hardcoded inline language fallbacks and Turkish checks, aligning with repository localization and quality standards.
- **Robust Captcha Image Rendering Fallback**: Synchronized the updated Captcha utility (`lib/Captcha.class.php`) from Property to enable in-memory PHP output buffering as a fallback if the server lacks cache directory write permissions, ensuring captcha images render successfully on all environments.

## v1.0.88
- **Fix Crash on Invalid Module Routes**: Added the missing `index/error.twig` template to the default templates theme. This ensures that when a user or administrator attempts to load an invalid module route (such as the non-existent `mod_file/list`), the application gracefully renders a localized 404/not found error page using Bootstrap 5 instead of crashing with a filesystem loader Twig exception.

## v1.0.87
- **Improve Browser Compatibility for Publication List and Modal**: Replaced `inset: 0` layout directives with explicit `top`, `right`, `bottom`, and `left` properties in the admin list template for the publication module. Added the `-webkit-backdrop-filter` style prefix to the modal background scrim to ensure the glassmorphic backdrop blur renders correctly on Safari and WebKit-based iOS/macOS browsers.

## v1.0.86
- **Fix Direct Attachment Upload ID Synchronization**: Passed the sync parameter (`true`) to `$file_model->save()` in `mod_attachment_mod::upload()` to ensure the generated database insert ID is synchronized back to the model instance. This resolves the bug where successful uploads returned a null ID in the JSON response, triggering false failure alerts on the client side.
- **Auto-Refresh Module Lists on Direct Attachment Upload**: Updated the client-side `window.handleDirectAttachmentUpload` function to trigger the `loadData(true)` refresh method on active module managers (`pubListManager`, `newsListManager`, `productListManager`) upon successful relation mapping, ensuring the table list immediately reflects the newly associated file state.

## v1.0.85
- **Redesign Media Picker Search and Actions Layout**: Repositioned the search filter field to the top of the media selector bottom sheet, moved the upload/add button to the bottom-right corner as a Floating Action Button (FAB) labeled with a single plus sign (+), and expanded list bottom padding to avoid FAB content overlap.
- **Improve Selection Visibility and Dynamic Checkmarks**: Upgraded the list checkmark icons to always remain visible, styling them as passive/cold outline check circles (`bi-check-circle` in Slate `#94a3b8`) for unselected options and transitioning them to active filled check circles (`bi-check-circle-fill` in Green `#34a853`) when selected.
- **Normalize Kurdish Translation of Select Picker Title**: Updated language dictionaries to translate the file picker sheet title as "Peleke heyî hilbijêre" instead of legacy typos.

## v1.0.84
- **Prevent Media Picker Upload Race Conditions**: Ensured media picker state variables (`window.currentMediaPickerType` and corresponding record IDs) are initialized immediately at the start of `openMediaRelationPicker`, `openFilesPicker`, and `openVideoPicker` functions. This prevents race conditions where selecting or uploading files was processed with old or uninitialized picker states, resolving redirect bugs when clicking the add button.
- **Admin Assets Cache-Busting**: Incremented version query strings for `script.js` and module-specific JavaScript files (`mod_publication.js`, `mod_news.js`, `mod_content.js`) in administrative Twig templates to force browser cache refresh and ensure instant deployment of background uploader upgrades.

## v1.0.83
- **Direct Attachment Upload and Auto-Association**: Simplified the file attachment workflow on administration list views. Clicking the "Add" button inside the attachment selection sheet now directly launches the native file picker on the user's device. The selected file is uploaded in the background via JSON-enabled AJAX and automatically associated with the target record (e.g. publication, news, content, or product) without redirecting the user to a complex management interface.

## v1.0.82
- **Fix mod_attachment PHP 8.x Compatibility**: Updated `edit()` method signature in `mod_attachment_mod` to accept `$id = null` optional argument to match the parent `BaseModuleController::edit($id = null)` method declaration, resolving PHP fatal incompatibility error when adding or managing attachments.

## v1.0.81
- **Fix Publication File and Gallery Pickers**: Implemented missing AJAX endpoints (`ajax_get_files`, `ajax_get_galleries`, `ajax_get_current_media`, and `ajax_update_relation`) in `mod_publication_mod` controller to resolve the "Error loading modal" dialog error triggered when opening attachment or gallery relation pickers on the publications administration list view.

## v1.0.80
- **Fix Database Column Mismatch on News and Publication Save**: Removed the non-existent `menu` column declaration from `mod_news_model` and `mod_publication_model` to resolve the SQLSTATE[42S22] column not found database insert errors.
- **Robust Field Mapping in Base Controller**: Safeguarded `BaseModuleController::processFieldData` to verify `property_exists` on the model before assigning properties. This prevents database insert queries from including form fields (like `menu`) that are not actual database columns, and eliminates dynamic property assignment deprecation warnings under PHP 8.2+.

## v1.0.79
- **Fix mod_news Visibility Compatibility**: Changed the visibility of the `_valid_date()` method in `mod_news_mod` from `private` to `public` to match the inheritance signature of `BaseModuleController`, resolving a fatal PHP incompatibility error under PHP 8.x/8.5.

## v1.0.78
- **Fix Password Reset Expiration Bug**: Resolved the password reset link expiration bug. Ensured `created_at` timestamp is updated in both INSERT and UPDATE paths during password reset token generation. Replaced the MySQL-side `TIMESTAMPDIFF` timezone-sensitive comparison with timezone-independent PHP `time()` and `strtotime()` validation checks to eliminate false expiration errors.

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