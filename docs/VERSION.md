# Amed Framework — Bulletin CMS Version History

## v1.0.14 (2026-11-02)
- **Mobile PageSpeed & Lighthouse Optimization (`lib/SportsDataService.class.php`, `templates/amed/`)**:
  - **Network Payload & Image Delivery Optimization**:
    - Replaced unconstrained 500x500 ESPN team badges with dimension-constrained, high-efficiency ESPN combiner thumbnails (`w=40&h=40&scale=crop`), shrinking asset weight by 98.8% (from ~1.25 MB to ~15 KB total) and enabling 24-hour cache TTL.
    - Updated `hero_slider.twig` `<picture>` structure to prevent mobile devices from downloading raw desktop hero images (`image_url`), serving optimized mobile thumbnails (`thumb_url`) across all mobile viewport breakpoints.
    - Removed redundant duplicate stylesheet inclusion (`latest-publications.css`) from `templates/amed/index/index.twig`.
    - Converted non-critical third-party fonts and vendor stylesheets (`bootstrap-icons.min.css`, `swiper-bundle.min.css`) to asynchronous non-render-blocking link tags.
  - **Mobile Accessibility (a11y) & UX Remediation**:
    - Fixed contrast ratio violation on columnists/publications action link (`--authors-action-color`) in `latest-authors.css` and `latest-publications.css`, elevating contrast ratio to 5.2:1 (exceeding WCAG AA requirements).
    - Expanded mobile touch target sizes on hero slider pagination bullets (`.swiper-pagination-bullet`) with 48x48px pseudo-element tap areas and enlarged tactile navigation buttons to 36x36px.
    - Added contextual `aria-label` attributes to identical "View All" links across news categories (`latest_news.twig`, `economy_news.twig`, `sports_news.twig`, `latest_publications.twig`), resolving the `identical-links-same-purpose` WCAG audit.
    - Removed redundant `alt` text on author avatar images inside named links, preventing repetitive screen reader announcements.
  - **Compositor Animation Performance**:
    - Refactored `.event-agenda-live-pulse` in `event-agenda.css` to use GPU-accelerated composited properties (`transform: scale()`, `opacity`) via `::after`, eliminating main-thread repaint overhead caused by non-composited `box-shadow` animations.
- **Latest News Category Filtering (`mod/index/helper/mod_news.helper.php`, `mod/index/index.php`, `mod/index/helper/index.php`, `templates/amed/index/mod_news/latest_news.twig`)**:
  - Implemented category exclusion support (`$excludeCategories`) in `_latest_news()`, resolving multilingual category aliases (`spor`, `dünya`, `ortadoğu`, `ekonomi`) and child category hierarchies into database-level `category_id NOT IN (...)` clauses.
  - Excluded sports, world, middle east, and economy news from the latest news section, ensuring dedicated homepage category blocks remain distinct while guaranteeing 6 full news items in the latest news stream.

## v1.0.13 (2026-11-02)
- **Event & Cultural Agenda System Modernization (`mod_event`, `mod_event_category`, `installer/mod_event/`, `installer/mod_event_category/`, `templates/admin/mod_event/`, `templates/admin/mod_event_category/`, `templates/amed/index/mod_event/`, `lib/McpEventTools.class.php`)**:
  - **Dual-Database Storage & Installation Parity**:
    - Created complete MySQL 5.7+ / SQLite schemas for `mod_event` and `mod_event_category` with zero-alias SQL queries and proper index coverage (`idx_event_lang_seo`, `idx_event_start_date`, `idx_event_category`).
    - Seeded module registries, bridge installer mappings (`mod_gallery_mod`, `mod_attachment_mod`, `mod_faq_mod`, `mod_menu`), and catalogs (`InstallerModuleCatalog.php`, `ModuleRegistry.class.php`).
  - **11-Language i18n Localization**:
    - Authored and verified all 22 module language dictionaries across 11 languages (`ar`, `ckb`, `de`, `en`, `fa`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`), adhering to descriptive English keys and zero missing translations.
    - Added public event and search localization to `mod/index/lang/*.php`.
    - Added event management localization keys across all 10 language dictionaries in `mod/management/lang/` and `mod/mod_user/lang/`.
  - **Phone-Only Admin Surface & Modernization**:
    - Implemented `mod_event` and `mod_event_category` admin controllers extending `BaseModuleController` and `indexMain`.
    - Integrated mobile card streams, stat badges, 9-step full-screen add wizard (`wizard_event.twig`), 9-button quick toolbar, inline image upload modal, and category drag-and-drop tree reordering with zero inline styles and zero inline scripts.
    - Wired layout inclusions into `templates/admin/layout.twig`, `sidebar.twig`, and `fab.twig` with `fab-row-event`.
  - **Public Broadsheet Theme (`templates/amed/index/mod_event/`)**:
    - Created `event_agenda.twig` broadsheet sidebar widget (Block R4 in `index.twig`) featuring 148px vertical 3:4 poster previews and responsive agenda lists.
    - Created `event.twig` detail view with full `Schema.org/Event` JSON-LD microdata, quick info ribbon, hero poster, HTML details, video embeds, gallery, attachments, FAQ, and related events.
    - Created `events.twig` category-filterable card stream with responsive 3:4 poster aspect ratio and broadsheet pagination.
    - Created `calendar.twig` interactive event calendar with month navigation and dynamic JSON feed (`/event-calendar/json`).
    - Integrated event search indexing in `mod/index/helper/search.helper.php` and `templates/amed/index/search.twig`.
  - **Hero Slider Integration & 3:4 Poster Presentation (`mod/index/helper/mod_hero_slider.helper.php`, `templates/amed/index/hero_slider.twig`, `templates/amed/static/css/hero-news-slider.css`, `templates/amed/static/css/hero-news-slider.min.css`)**:
    - Integrated `mod_event` into homepage hero slider with `loadEventItems($limit)` honoring module feature gate (`event.front`), publication status, slider toggle, category permissions, and language filtering.
    - Built 3:4 vertical poster presentation mode (`.hero-news-slide--event`) within the 16:9 hero slider container featuring ambient dark backdrop (`#090d16`), contained aspect-ratio scaling, and depth shadowing (`0 8px 30px rgba(0, 0, 0, 0.6)`).
    - Synchronized compiled CSS bundle `templates/amed/static/css/hero-news-slider.min.css` passing `min_css_guard`.
  - **Clean Wikipedia-Style URL Routing & XML Sitemaps (`config/url.config.php`, `mod/index/index.php`)**:
    - Registered clean SEO routes for event details (`event/:any`, `event/detail/:any`), event archive (`events`, `events/:category`), calendar views (`event-calendar`, `event-calendar/json`), and event sitemap (`eventSitemap.xml`).
    - Implemented dedicated XML sitemap feed `eventSitemap()` in `mod/index/index.php` with 50,000-entry pagination, language scoping, and ACL-isolated public visibility checks.
    - Integrated event sitemap and category URLs into the master sitemap index (`sitemap.xml`) and categories sitemap index.
  - **AI Discovery & MCP Integration (`lib/LlmsService.class.php`, `lib/McpEventTools.class.php`, `lib/McpResourceManager.class.php`, `tasks/mcp_smoke.php`)**:
    - Registered active events and event full-text in `lib/LlmsService.class.php` (`/llms.txt` and `/llms-full.txt`).
    - Created `lib/McpEventTools.class.php` implementing 10 MCP CRUD tools (`list_events`, `get_event`, `create_event`, `update_event`, `delete_event`, `list_event_categories`, `get_event_category`, `create_event_category`, `update_event_category`, `delete_event_category`) and registered `cms://event/{id}` resource in `McpResourceManager.class.php`.
    - Added MCP discovery assertions to `tasks/mcp_smoke.php`.

- **MCP Admin Audit Dashboard Remediation & UI Super Polish (`mod/mod_mcp/index.php`, `lib/McpAuditLogger.class.php`, `templates/admin/mod_mcp/audit.twig`, `templates/admin/mod_mcp/list.twig`, `templates/admin/assets/css/mod_mcp.css`, `templates/admin/assets/js/mod_mcp.js`, `tasks/mcp_smoke.php`)**:
  - **Resolved Fatal `Database::toRow()` Runtime Exception**:
    - Eliminated undefined `$this->db->toRow()` calls in `mod_mcp_mod::audit()` by implementing a centralized, PDO-prepared `McpAuditLogger::getSummaryStats($filters)` helper.
    - Enforced zero-alias SQL policy in strict compliance with `i18n_guard` using positional row fetching (`\PDO::FETCH_NUM`).
  - **Multi-Tenant User Scope Isolation**:
    - Integrated `resolveCurrentUserScope()` into `mod_mcp_mod::audit()`, restricting non-root administrators (`group_id !== 1`) to their scoped user audit logs, summary metrics, and personal token consumption (`getDailyTokenUsage($scopedUserId)`).
  - **Tactile Phone-First UI Polish**:
    - Enhanced audit dashboard with interactive search bar and clear button (`.mcp-filter-search-icon`, `.mcp-btn-filter-reset`), status dot indicators (`.mcp-status-dot`), avatar icons (`.mcp-avatar-oauth`, `.mcp-avatar-audit`, `.mcp-avatar-error`, `.mcp-avatar-auth`), protocol badge pill (`.mcp-proto-tag`), and tactile stat cards with tap scaling (`scale(0.97)`).
    - Preserved `filters.caller_type`, `filters.tool_name`, and `filters.is_error` across pagination links.
    - Sanitized caller identifier formatting to prevent trailing delimiters when `caller_id` is null or empty.
  - **DOM Identifier & Data-Attribute Synchronization**:
    - Synchronized `#mcpKeyList` and `#mcpOauthList` DOM selectors and unified `data-copied-msg` / `data-copy-msg` attributes between `templates/admin/mod_mcp/list.twig` and `templates/admin/assets/js/mod_mcp.js`, restoring real-time KPI count synchronization upon key or client deletion.
  - **Automated Regression Coverage**:
    - Extended `tasks/mcp_smoke.php` with regression tests verifying `McpAuditLogger::getSummaryStats()`, filtered log queries, and repository-wide elimination of `Database::toRow()` calls (63/63 smoke tests passing).

- **Installer Modernization & Full 11-Language Russian Localization (`installer/installerbase.php`, `installer/mods/index.php`, `installer/mod_slider/index.php`, `installer/lang/ru.php`, `installer/lang/`)**:
  - **Modernized Permission & Role Lifecycle**:
    - Refactored legacy role and permission methods (`insert_roll()` $\rightarrow$ `insert_roles()`, `remove_rool()` $\rightarrow$ `delete_roles()`), while maintaining backward-compatible aliases.
    - Purified installer codebase and comments to standard English.
  - **11-Language Parity with Russian Addition**:
    - Authored complete Russian (`installer/lang/ru.php`) language pack for the installer wizard.
    - Registered `mod_event` and `mod_event_category` across all 11 installer language dictionaries (`ar`, `ckb`, `de`, `en`, `fa`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`).

- **Pre-Release Search Engine Directives & Frontend Strict Code Separation (`.htaccess`, `robots.php`, `robots.txt`, `llms.php`, `templates/amed/mod_user/login.twig`, `templates/amed/static/js/login.js`)**:
  - **Strict Crawling & Indexing Shield**:
    - Enforced `X-Robots-Tag: noindex, nofollow, noarchive` and `Disallow: /` across `.htaccess`, `robots.php`, `robots.txt`, and `llms.php` to prevent pre-release staging instances from premature search indexing.
  - **Strict Code Separation on Login Surface**:
    - Refactored standalone login view `templates/amed/mod_user/login.twig` by decoupling inline JavaScript into external `templates/amed/static/js/login.js`.
    - Bound AJAX endpoint, redirect URL, and localized error messages via HTML data attributes, eliminating forbidden translation fallbacks and achieving 100% `code_separation_guard` and `i18n_guard` compliance (0 errors).

## v1.0.12 (2026-11-01)
- **MCP Protocol Hardening, Target Pinning & Google Gemini OAuth 2.1 Compatibility (`lib/McpServer.class.php`, `lib/McpOAuth.class.php`, `mcp.php`, `index.php`, `config/site.config.php`, `config/site.config.example.php`, `mod/mod_mcp/index.php`)**:
  - **Positive Target Pinning & HTTP 421 Misdirected Request Gate**:
    - Implemented multi-instance target binding via `X-MCP-Expected-Server` and `X-MCP-Expected-Resource` headers.
    - Added case, hyphen, and underscore normalization (`amed-bulletin-mcp` $\leftrightarrow$ `amed_bulletin_mcp`) for seamless Gemini compatibility.
    - Added strict HTTP 421 Misdirected Request rejection with host and port validation across loopback (`localhost`, `127.0.0.1`, `::1`) and remote origins on target mismatches, preventing cross-tenant request hijacking.
  - **Google Gemini OAuth 2.1 & Managed Agent Credentials**:
    - Enabled both `application/x-www-form-urlencoded` and `application/json` payload parsing on `/oauth/token`.
    - Added `client_secret_basic` alongside `client_secret_post` support by parsing and RFC 6749 URL-decoding `Authorization: Basic` headers for automated credential exchange.
    - Configured multi-vendor CIMD allowlist (`$site['mcp_cimd_allowed_hosts']`) supporting Google Gemini (`*.google.com`, `*.googleapis.com`, `aistudio.google.com`, `gemini.google.com`, `developers.google.com`), OpenAI (`chatgpt.com`), and custom providers.
    - Enforced complete RFC 9728 `WWW-Authenticate: Bearer` emission across all 401 Unauthorized branches and automatic self-healing OAuth/API key table provisioning (`McpOAuth::ensureTables()`, `mod_mcp_mod::ensureInfrastructure()`).
  - **MCP `2026-07-28` Stateless Protocol Standard**:
    - Added `'2026-07-28'` to `SUPPORTED_PROTOCOL_VERSIONS` and implemented `server/discover` method routing to `handleInitialize`.
    - Added `Mcp-Method` and `Mcp-Name` to CORS headers, and emitted `X-MCP-Server-Name` and `X-MCP-Resource` response headers across both JSON (`sendJson()`) and empty-body notification/preflight (`sendEmptyStatus()`) responses.
    - Pre-parsed request payloads to preserve exact scalar (`int|string`) JSON-RPC `$requestId` values on error responses and suppressed response bodies (HTTP 202 Accepted) on JSON-RPC notifications to prevent agent hangs.

- **Mandatory Editorial Featured Image Contract (`lib/McpNewsTools.class.php`, `lib/McpFaqTools.class.php`, `.agents/skills/kevanger/SKILL.md`)**:
  - Enforced required, non-nullable featured image with valid base64 payload and MIME (`image/webp`, `image/jpeg`, `image/png`, `image/gif`) on `create_news`.
  - Prohibited removing featured images (`image: null`) on `update_news`, ensuring production editorial visual quality.
  - Clarified cross-module FAQ relationships (`mod_news`, `mod_publication`, `mod_page`).

- **Docker Environment & Infrastructure Resilience (`docker-compose.yml`, `docker/nginx/default.conf`, `docker/php/php.dev.ini`)**:
  - Added `restart: unless-stopped`, parameterized ports (`${BULLETIN_WEB_PORT:-8080}:80`), and MySQL ping healthchecks.
  - Secured uploads folder against arbitrary script execution in Nginx.
  - Configured 300s FastCGI timeouts and buffers, and enabled `/.well-known/` routing for RFC 8414 and RFC 9728 discovery before dotfile blocking.
  - Set `xdebug.start_with_request = trigger`.

- **Positive-Pinned Stdio Bridges, Auto-Discovering Health Check & Smoke Suite (`~/.gemini/antigravity/bin/amed_bulletin_mcp.py`, `~/.gemini/antigravity-cli/bin/amed_bulletin_mcp.py`, `tools/mcp_health_check.py`, `tasks/mcp_smoke.php`, `mod/mod_gallery/index.php`)**:
  - Pinned stdio bridges to `EXPECTED_SERVER_NAME = "amed-bulletin-mcp"` with strict missing/mismatched `X-MCP-Server-Name` validation and image pre-flight checks.
  - Synchronized byte-for-byte parity across both runtime bridge paths.
  - Built 7-stage automated health checker `tools/mcp_health_check.py` with zero hardcoded URLs (passing all 24 verification checks) and expanded `tasks/mcp_smoke.php` to 56 checks with zero PHP 8.2+ deprecations.

- **Admin `mod_mcp` Taste Skill & Super Polish Mobile Modernization (`templates/admin/mod_mcp/`, `templates/admin/assets/css/mod_mcp.css`, `templates/admin/assets/js/mod_mcp.js`, `mod/mod_mcp/lang/`)**:
  - Modernized `/mod_mcp/list` and `/mod_mcp/audit` with phone-only mobile application aesthetics, tactile interactive endpoint pill and key-prefix one-tap copy buttons, real-time KPI count synchronization (`syncSummaryCounts()`), and filter-preserving audit pagination.
  - Rewrote 100% of Kurmanji (`ku.php`), Zazaki (`zza.php`), and Sorani (`ckb.php`) localization dictionaries and aligned `oauth_hint` across all 10 supported languages (`63` keys each).
  - Enforced strict zero inline styles, zero inline event handlers, and `--md-sys-color-*` token wrappers (`code_separation_guard` and `i18n_guard` 100% clean).

- **Public Theme Canonicalization (`templates/amida/` $\rightarrow$ `templates/amed/`, `lib/Amed.class.php`, `lib/TwigTemplate.class.php`, `mod/mod_settings/`, `installer/mod_settings/`, `tools/min_css_guard.py`, `tests/e2e/`)**:
  - Renamed the canonical public frontend theme directory and default theme identifiers from `amida` to `amed` across framework libraries, installer and module SQL seeds, CSS bundle guards, E2E test suites, and repository documentation.

- **Super League Live Standings & Fixtures Sidebar Widget (`lib/SportsDataService.class.php`, `tasks/update_sports_data.php`, `mod/index/index.php`, `mod/index/lang/*.php`, `templates/amed/index/mod_news/super_league.twig`, `templates/amed/static/css/super-league.css`, `clear_cache.php`)**:
  - Built `SportsDataService` with multi-source live standings and fixture aggregation, local JSON caching (`cache/sports/`), background CLI updater `tasks/update_sports_data.php`, cache flushing integration (`cache_sports` in `clear_cache.php` and `index.php`), and 10-language localization in `mod/index/lang/*.php`.

- **Live Market Board Widget — Typography, Rhythm & Surface Polish (`templates/amed/index/mod_news/market_rates.twig`, `templates/amed/static/css/market-rates.css`)**:
  - **Restrained Broadsheet Financial Header (`templates/amed/index/mod_news/market_rates.twig`, `templates/amed/static/css/market-rates.css`)**:
    - Refined `.market-head` with clean horizontal alignment (`justify-content: space-between`), pairing `.market-head-title` (`bi-graph-up-arrow` + `Plus Jakarta Sans` 800 uppercase) on the left with `.market-head-live` (pulsing `.market-live-dot` + live status label) on the right over a crisp `1px` divider.
  - **2×4 Financial Instrument Cards & Tabular Hierarchy (`templates/amed/index/mod_news/market_rates.twig`, `templates/amed/static/css/market-rates.css`)**:
    - Tuned `.market-item` card spacing, border contrast, and `Plus Jakarta Sans` `tabular-nums` hierarchy (`line-height: 1.3` on `.market-name` preventing diacritic clipping on `GRAM GÜMÜŞ`, `0.84rem` extra-bold `.market-val`, and soft semantic `.market-badge` pills) across light, dark, and RTL modes.

- **Footer Brand & Logo Management Parity with Header (`templates/amed/index/footer.twig`, `templates/amed/static/css/footer.css`)**:
  - **Dynamic 6-Mode Admin Logo & Title Synchronization (`templates/amed/index/footer.twig`)**:
    - Synchronized the footer brand section with the header's dynamic logo management system (`mod_settings`), supporting all 6 layout modes (`square`, `rectangle`, `square_name`, `rectangle_name`, `square_rectangle`, and `name_only`), `show_logo` visibility toggles, and animated logo configurations (`webm`, `mp4`, `json` Lottie, image, blend modes, and loop settings).
    - Preserved resilient icon fallback order (`site_icon` -> `favicon_96` -> `favicon_path` -> monogram) and automatic `/thumb/` fallback via `top-menu.js`.
  - **Modern Brand Styling & Dark Mode Parity (`templates/amed/static/css/footer.css`)**:
    - Aligned `.site-footer .footer-brand` with `.modern-brand` layout rules and dark/light theme typography.

## v1.0.11 (2026-10-31)
- **Homepage Economy News Sidebar Block R2 & Zero-Latency Live Market Data Architecture (`lib/MarketDataService.class.php`, `tasks/update_market_rates.php`, `mod/index/helper/mod_news.helper.php`, `mod/index/index.php`, `mod/index/lang/*.php`, `templates/amida/index/mod_news/economy_news.twig`, `templates/amida/index/mod_news/market_rates.twig`, `templates/amida/static/css/economy-news.css`, `templates/amida/static/css/market-rates.css`, `clear_cache.php`)**:
  - **Zero-Latency Multi-Source Market Data Engine (`lib/MarketDataService.class.php`, `tasks/update_market_rates.php`)**:
    - Architected `MarketDataService` with field-level multi-source redundancy across verified, key-free public endpoints (Truncgil, CNBC, TCMB, Binance, CryptoCompare), compound cross-rate derivation (Gram Gold from USD gold spot and USD/TRY, Gram Silver, Brent Crude Oil, BIST 100), circuit breaker backoff, and local caching in `cache/market/market_rates.json` ensuring `< 1ms` TTFB on public HTTP requests.
    - Added scheduled CLI task `tasks/update_market_rates.php` for automated background rate updates.
    - Integrated market cache flushing (`cache_market`) into `clear_cache.php` and `index.php` (`cacheMarket()`).
  - **Multilingual Economy News Controller & Exclusion Filter (`mod/index/helper/mod_news.helper.php`, `mod/index/index.php`)**:
    - Added `index_mod::_economy_news()` with candidate resolution across 10 languages (`Ekonomi`, `Aborî`, `Economy`, `Wirtschaft`, `Économie`, `Экономика`, `اقتصاد`, `ئابووری`, `İqtisad`), multi-category query exclusion (`$excludeIds`), and passing of live formatted market rates.
  - **Broadsheet Right Sidebar Block R2 UI & 8-Instrument Ticker Board (`templates/amida/index/mod_news/economy_news.twig`, `templates/amida/index/mod_news/market_rates.twig`, `templates/amida/static/css/economy-news.css`, `templates/amida/static/css/market-rates.css`)**:
    - Built `economy_news.twig` featuring 1 lead story card (`160 × 90 px` `16:9` thumbnail + bold headline + 120-character summary) and 2 horizontal sub-story cards (`104 × 58 px` `16:9` thumbnail + headline + 2-line clamped summary).
    - Built `market_rates.twig` featuring a responsive `2 × 4` financial instruments grid with localized names, real-time rates, formatted percentage badges (`up`/`down`/`flat`), nominal difference values, and live data indicator.
  - **10-Language Localization Parity (`mod/index/lang/*.php`)**:
    - Added comprehensive `$lang['index']['mod_news']['economy_news']` (`title`, `all_items`, `kicker`, `featured`, `markets_title`, `live_data`, `gram_gold`, `dollar`, `euro`, `sterling`, `bist100`, `bitcoin`, `gram_silver`, `brent`) and cache keys across all 10 language dictionaries (`ar`, `ckb`, `de`, `en`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`).

- **Hero News Slider Modernization & Interactive Autoplay Progress (`templates/amida/index/hero_slider.twig`, `templates/amida/static/css/hero-news-slider.css`, `templates/amida/static/css/hero-news-slider.min.css`, `templates/amida/static/js/hero-news-slider.js`, `mod/index/helper/mod_hero_slider.helper.php`)**:
  - **Expanded Rotation Capacity (`mod/index/helper/mod_hero_slider.helper.php`)**:
    - Increased default hero slider item count from 6 to 10 (`hero_slider($limit = 10)`), providing deeper editorial rotation.
  - **Tactile Fractional Counter & Dynamic Autoplay Ring/Bar (`templates/amida/index/hero_slider.twig`, `templates/amida/static/js/hero-news-slider.js`, `templates/amida/static/css/hero-news-slider.css`, `templates/amida/static/css/hero-news-slider.min.css`)**:
    - Added fractional counter HUD (`01 / 10`) synced dynamically to `swiper.realIndex` on initialization and slide changes (`updateCounter()`).
    - Added live linear autoplay progress indicator driven by `autoplayTimeLeft` event via `--hero-autoplay-progress` CSS custom property.
    - Added strict `prefers-reduced-motion` detection disabling autoplay and transitions for accessibility compliance.
  - **Slide Typography & Minified Stylesheet Parity (`templates/amida/index/hero_slider.twig`, `templates/amida/static/css/hero-news-slider.css`, `templates/amida/static/css/hero-news-slider.min.css`)**:
    - Re-architected slide overlay into an integrated kicker bar (`category` + `timestamp`), multi-line serif headline, deck summary, and reading-time indicator with subtle gradient shielding.
    - Synchronized `hero-news-slider.min.css` to 100% rule-set parity (105 blocks).

- **Admin `mod_video_embed` Full Modernization, Strict Code Separation & Native Mobile UX (`mod/mod_video_embed/index.php`, `templates/admin/mod_video_embed/`, `templates/admin/assets/css/mod_video_embed.css`, `templates/admin/assets/js/mod_video_embed*.js`)**:
  - **AJAX Submissions, Sanitization & Resilient Embed Parsers (`mod/mod_video_embed/index.php`)**:
    - Added JSON responses to `_add()` and `_edit()` for asynchronous form submission without page reloading.
    - Extended video URL matching to support `youtube-nocookie.com`, YouTube Live (`youtube.com/live/{id}`), YouTube Shorts (`youtube.com/shorts/{id}`), HTML entity decoded inputs, and multi-platform video URL resolution.
    - Implemented `_stripEmojiAndUnsupportedChars()` removing 4-byte UTF-8 supplementary symbols to prevent database write crashes on MySQL standard utf8 collations.
    - Added auto-filled thumbnail synchronization and automated file cleanup on image replacement.
  - **Strict Code Separation & Declarative Data Attributes (`templates/admin/mod_video_embed/{list,add,edit}.twig`)**:
    - Completely eliminated inline `<script>` tags, inline JavaScript object configurations (`VIDEO_LANG`, `MODULE_URL`), and inline styles across all `mod_video_embed` templates.
    - Migrated all configuration and localized UI strings to declarative `data-*` attributes (`data-module-url`, `data-data-url`, `data-i18n-*`) on the container.
  - **Dynamic Metadata Fetching & Textless Indeterminate Progress Track (`templates/admin/assets/css/mod_video_embed.css`, `templates/admin/assets/js/mod_video_embed_edit.js`)**:
    - Built textless indeterminate loading bar (`.embed-fetch-bar-track`, `.embed-fetch-bar-indeterminate`) for real-time video metadata lookups.
    - Added interactive duration formatted badges, automatic category mapping, dynamic thumbnail preview updates, and modal preview engine integration.
  - **Card Actions, Toolbar & Quick Status Toggles (`templates/admin/assets/js/mod_video_embed.js`)**:
    - Upgraded `mod_video_embed.js` to v2.1 with pull-to-refresh spinner, infinite scroll / load more, batch selection, quick status toggle (`publish`/`unpublish`), and hero slider toggle with real-time UI reflection.

- **Admin `mod_news` Card State Indicators & Interactive Floating Toolbar (`templates/admin/assets/css/mod_news.css`, `templates/admin/assets/js/mod_news.js`, `templates/admin/mod_news/list.twig`, `mod/mod_news/index.php`)**:
  - **Visual State Indicator Pills (`templates/admin/assets/css/mod_news.css`, `templates/admin/assets/js/mod_news.js`, `templates/admin/mod_news/list.twig`)**:
    - Added tactile state indicator chips (`.card-state-indicators`, `.card-state-pill`) to every news list card displaying real-time Status (Published/Draft), Deck (Featured/Standard), and Hero Slider (In Slider/Excluded) states.
    - Updated interactive toggles in `mod_news.js` to immediately update state pills without requiring a full card or datatable refresh.
  - **Floating Contextual Toolbar Elevation & Snackbar Coordination (`templates/admin/assets/css/mod_news.css`)**:
    - Coordinated `#snackbar-container` with floating contextual action bar (`bottom: calc(142px + env(safe-area-inset-bottom, 0px)) !important` when items are selected or toolbar is visible) to prevent UI overlapping.
    - Normalized `toggleFlag()` JSON responses in `mod/mod_news/index.php` for status, deck, and slider actions.

- **Admin Core Modernization & Bulk Workflow Enhancements (`lib/BaseModuleController.class.php`, `lib/AdminTemplate.class.php`, `mod/mod_publication/index.php`, `templates/admin/assets/js/modals/`, `templates/admin/assets/css/modals/core_modals.css`)**:
  - **Base Controller Bulk Approve / Unapprove Operations (`lib/BaseModuleController.class.php`)**:
    - Implemented `approve()` and `unapprove()` actions across `BaseModuleController`, supporting single or array `$ids` with granular permission checks (`approval` vs `publish`/`unpublish`).
  - **Publication Datatable & Ajax Controller Hardening (`mod/mod_publication/index.php`)**:
    - Upgraded `datatable_json()` with column mapping, ordering, and filtering across `slider` and `deck` properties.
  - **Admin Template Ajax Detection & Modal Engine (`lib/AdminTemplate.class.php`, `templates/admin/assets/js/modals/af_modal_engine.js`, `modal_theme_logo.js`, `core_modals.css`)**:
    - Added `$this->set('is_ajax', $isAjax)` in `AdminTemplate.class.php`.
    - Enhanced `af_modal_engine.js` with focus trapping, backdrop handling, and smooth sheet animations.
    - Added support for `square_rectangle` layout in logo customization modals and `McpSettingsTools.class.php`.

- **Frontend Navigation, Brand Architecture & Authors Layout (`templates/amida/`)**:
  - **Top Navigation Brand Fallback & Logo Typography (`templates/amida/index/mod_menu/`, `templates/amida/static/css/top-menu.css`, `templates/amida/static/css/stacked-top-menu.css`, `templates/amida/static/js/top-menu.js`)**:
    - Modernized brand logo rendering across standard and stacked navigation headers with support for `square_rectangle` logo layouts and SVG/PNG icon combos.
    - Added resilient JavaScript fallback handler in `top-menu.js` switching from `/thumb/` to original source if thumbnail generation fails or hiding gracefully on broken asset URLs.
  - **Authors Strip & Publication Grid Polish (`templates/amida/index/mod_publication/`, `templates/amida/static/css/latest-authors.css`, `templates/amida/static/css/latest-publications.css`)**:
    - Polished author profile card styling, circular avatars with initials fallbacks, column counts, typography, and section title keys.
    - Added `authors_section_title` and `all_authors` localization keys across all 10 language dictionaries.
  - **Modern Brand Presentation in Footer (`templates/amida/index/footer.twig`, `templates/amida/static/css/footer.css`)**:
    - Enhanced footer brand presentation with responsive Gothic typography, dynamic favicon/icon fallback chain, and clean feed link alignments.

- **News Agent Editorial Tools & SEO Guard Script (`.agents/skills/kevanger/`, `.agents/skills/kevkolin/`, `.agents/skills/kevseo/`)**:
  - Added automated image processing helper `process_news_image.py` in `.agents/skills/kevanger/scripts/`.
  - Updated editorial workflows, source verification rules in `kevkolin`, and SEO auditing script `audit_seo.py` with 16:9 aspect ratio and deck length enforcement.

- **Clean Cache Management & Port/Environment Hygiene (`clear_cache.php`, `docker-compose.yml`, `templates/base_static/css/cache-clear.css`)**:
  - Extended `clear_cache.php` with direct market data cache flushing (`cache_market`), styled interactive feedback cards, and cache clear styling in `templates/base_static/css/cache-clear.css`.
  - Standardized Docker web port configuration in `docker-compose.yml`.

## v1.0.10 (2026-10-26)
- **Admin `mod_user` Form Modernization, Native Mobile App UI & Full Parity Sync (`mod/mod_user/`, `templates/admin/mod_user/`, `templates/admin/assets/css/mod_user.css`, `templates/admin/assets/js/mod_user.js`, `templates/admin/layout.twig`)**:
  - **Form Submission, Case-Sensitivity Parity Fix & Asynchronous AJAX (`mod/mod_user/index.php`)**:
    - Fixed the root cause where submitting `mod_user/add` reset back to an empty form without feedback, caused by a case mismatch between `$this->theme->set('Success', true)` in controller helpers and Twig's `{% if success is defined %}`. Standardized both `_message()` and `_app_message()` to assign both `Success` and `success`.
    - Added AJAX request handling in `add()` and `edit()` returning structured JSON (`status`, `message`, `redirect_url`) with HTTP 422 for validation errors and automatic redirect to `/{lang}/mod_user/list` upon success.
    - Normalized `$d` view data binding across `add()`, `edit()`, `settings()`, `info()`, and `change_password()`, ensuring `group_id` and all form values persist accurately on validation failures.
  - **Native Mobile App Surface & Super Polish UI (`templates/admin/mod_user/{add,edit,settings,info,change_password}.twig`, `templates/admin/assets/css/mod_user.css`, `templates/admin/assets/js/mod_user.js`, `templates/admin/layout.twig`)**:
    - Re-architected `add.twig`, `edit.twig`, and `settings.twig` into a tactile MD3 Native Mobile App layout featuring a Hero Identity Avatar card with instant preview and live title sync, 3-tab segmented navigation (`Identity & Account`, `Author Profile`, `Social Media`), and a sticky bottom action bar with visible labels and loading spinner.
    - Added live password visibility toggle, 4-bar password strength meter, and character counters.
    - Trimmed `page_headline` in `templates/admin/layout.twig` and registered centralized `mod_user.css?v=2.0` and `mod_user.js?v=2.0`.
  - **International ITU-T E.164 Phone Selector (`mod/mod_user/index.php`, `mod/mod_user/validator/`, `templates/admin/mod_user/parts/phone_input.twig`)**:
    - Implemented a searchable bottom-sheet country dial-code picker with 240+ countries and country flags, normalizing international numbers cleanly into `country_code` and `phone` fields while keeping phone optional on creation/editing with ITU-T format validation if entered.
    - Updated password length requirement in `mod_user.validator.php` and `edit.validator.php` to modern 8–64 character limits.
  - **Social Links Interactive Inline Drawer (`templates/admin/mod_user/parts/social_links.twig`, `mod/mod_user/index.php`)**:
    - Built interactive drawer toggles for 8 social platforms with clean URL input, active status toggle (`mod_user_social.social_active`), and real-time status chips.
  - **Strict Code Separation & 100% i18n Key Parity (`templates/admin/mod_user/`, `mod/mod_user/lang/{en,tr,ku}.php`)**:
    - Extracted all inline styles, `<style>` blocks, and inline JavaScript into `mod_user.css` and `mod_user.js`.
    - Added comprehensive descriptive English keys in `en.php` mirrored across `tr.php` and `ku.php`, eliminating forbidden fallbacks and passing `code_separation_guard.py` and `i18n_guard.py` with 0 errors.
- **Framework Version Baseline & Completed Plan Cleanup (`config/site.config.php`, `config/site.config.example.php`, `docs/plan/`)**:
  - Synchronized `config/site.config.php` and `config/site.config.example.php` fallback version to `1.0.10`, and removed completed remediation plan documentation.

## v1.0.9 (2026-10-26)
- **Homepage Right Sidebar Block R1 — World & Middle East (`Dünya & Ortadoğu`) Module & 10-Language Localization (`mod/index/helper/mod_news.helper.php`, `mod/index/index.php`, `mod/index/lang/*.php`, `templates/amida/index/index.twig`, `templates/amida/index/mod_news/world_news.twig`, `templates/amida/static/css/world-news.css`)**:
  - **Multi-Category Aggregation & Exclusion Filter in `mod_news_helper::_category_news()` (`mod/index/helper/mod_news.helper.php`)**:
    - Extended `_category_news()` to accept an optional `$excludeIds` array, aggregate descendant category IDs across all matched root categories, and return `matched_categories` metadata (`id`, `name`, `seo`, `url`) for multi-category header links.
  - **World & Middle East Sidebar Controller (`mod/index/index.php`)**:
    - Added `index_mod::_world_news($t, $limit = 3)` combining localized World (`Dünya`, `Cîhan`, `Dinya`, `World`, `Welt`, `Monde`, `Världen`, `Мир`, `العالم`, `جیهان`) and Middle East (`Ortadoğu`, `Rojhilata Navîn`, `Rojhelato Mîyanên`, `Middle East`, `Naher Osten`, `Moyen-Orient`, `Mellanöstern`, `Ближний Восток`, `الشرق الأوسط`, `ڕۆژهەڵاتی ناوەڕاست`) categories with dual category header URLs (`world_category_links`), and suppressed duplicate rendering of those categories in `_homepage_news_categories()`.
  - **Broadsheet Right Sidebar Block R1 UI & `450 px` Hero Alignment (`templates/amida/index/index.twig`, `templates/amida/index/mod_news/world_news.twig`, `templates/amida/static/css/world-news.css`)**:
    - Created `world_news.twig` and `world-news.css` matching the `800 × 450 px` Left Hero Slider height 1:1 on desktop (`height: 450px` at `>= 992px`), featuring a slanted red editorial ribbon header with dual clickable category links (`Dünya` & `Ortadoğu`) and 3 equal-height stacked cards (`full-width` 2-line clamped headline + `108 × 61 px` `16:9` thumbnail + 3-line summary, dark mode, RTL support, and `prefers-reduced-motion` guards).
  - **10-Language Localization Parity (`mod/index/lang/*.php`)**:
    - Added `$lang['index']['mod_news']['world_news']` (`title`, `world_title`, `middle_east_title`) across all 10 language packs (`ar`, `ckb`, `de`, `en`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`).
- **Admin `mod_user_group` Breadcrumb Hierarchy, Controller/Validator Fixes & Strict M3 Code Separation (`templates/admin/partials/header.twig`, `templates/admin/mod_user_group/`, `templates/admin/assets/css/mod_user_group.css`, `templates/admin/assets/js/mod_user_group.js`, `mod/mod_user_group/`)**:
  - **Intermediate Parent Breadcrumb Support (`templates/admin/partials/header.twig`, `templates/admin/mod_user_group/{list,add,edit}.twig`)**:
    - Extended `templates/admin/partials/header.twig` with optional `breadcrumb_parent_url` and `breadcrumb_parent_title` slots between `mod_user/panel` and `breadcrumb_current`.
    - Replaced dead numeric language keys (`lang.mod_user_group[17]`, `lang.mod_user_group[19]`) in `mod_user_group/list.twig` with `lang.mod_user_group.admin.list` and `lang.mod_user_group.reorder_active`, and wired `breadcrumb_parent_url = site.saddress ~ 'mod_user_group/list'` in `add.twig` and `edit.twig` so sub-pages link back to the User Groups list.
  - **Strict Code Separation & M3 Mobile Assets (`templates/admin/mod_user_group/{list,add,edit,script}.twig`, `templates/admin/assets/css/mod_user_group.css`, `templates/admin/assets/js/mod_user_group.js`)**:
    - Extracted all inline `<style>` blocks, `style="..."` attributes, inline `<script>` logic, and `onclick="..."` event handlers from `mod_user_group` templates into dedicated `mod_user_group.css` and `mod_user_group.js` using `data-action` delegation (`toggle-reorder`, `delete-group`).
  - **Validator, Controller & Localization Fixes (`mod/mod_user_group/index.php`, `mod/mod_user_group/validator/{add,edit}.validator.php`, `mod/mod_user_group/lang/{ar,fr,sv,zza}.php`)**:
    - Updated `add.validator.php` and `edit.validator.php` to reference `$this->lang['mod_user_group']['hata']['name_required']` instead of legacy numeric key `'1'`, refreshed `$d` after POST update in `mod_user_group_mod::edit()`, and added missing `'reorder_active'` translations to `ar`, `fr`, `sv`, and `zza`.
- **Local Runtime Port 8080 Standardization & Completed Plan Cleanup (`docker-compose.yml`, `.env.example`, `.agents/skills/kevanger/SKILL.md`, `tools/mobile_simulator.html`, `tools/i18n_guard.py`, `docs/plan/`, `config/`)**:
  - Standardized `docker-compose.yml` (`8080:80` web, `8081:80` pma), `.agents/skills/kevanger/SKILL.md`, and `tools/mobile_simulator.html` to port `8080`, aligned `tools/i18n_guard.py` directory exclusions (`tools`) with `tools/code_separation_guard.py`, synchronized `config/site.config.php` and `config/site.config.example.php` fallback version to `1.0.9`, and removed `.env.example` and completed plan docs (`docs/plan/HOMEPAGE_RIGHT_SIDEBAR_PLAN.md`, `docs/plan/HOMEPAGE_SLIDER_MODERNIZATION_PLAN.md`).

## v1.0.8 (2026-10-25)
- **Bundle-Aware CSS Guard, Minified Hero Stylesheet Activation, 34-Template Cache-Buster & 404 Asset Sweep, Hero Slider ORM/SQL Hardening, Uniform Empty-State Hiding & Port 8082 Isolation (`tools/`, `templates/`, `mod/`, `config/`, `docker-compose.yml`, `.env.example`, `.agents/skills/`)**:
  - **Bundle-Aware `*.min.css` Guard & String-Safe Deterministic Pipeline (`tools/min_css_guard.py`, `templates/amida/static/css/`, `.agents/skills/task-validation/SKILL.md`)**:
    - Added `tools/min_css_guard.py` enforcing `@media`-aware rule-set parity (`missing-in-min = 0`, `stale-in-min = 0`) across the 2-into-1 core bundle (`custom.css` + `stacked-top-menu.css` → `custom.min.css`, 307 rule blocks) and `hero-news-slider.css` → `hero-news-slider.min.css` (56 rule blocks), with a string-preserving `--build` mode and orphan detection, wired into `.agents/skills/task-validation/SKILL.md`.
    - Removed the unreferenced `templates/amida/static/css/stacked-top-menu.min.css` orphan left over from the `v1.1.31` navigation bundle consolidation.
  - **Minified Hero Stylesheet Activation (`templates/amida/index/index.twig`)**:
    - Switched `index.twig` from unminified `hero-news-slider.css` (17,941 B) to `hero-news-slider.min.css` (10,991 B), saving ~7 KB per homepage request.
  - **34-Template Cache-Buster Sweep, 404 Stylesheet Remediation & English Comment Hygiene (`templates/amida/`, `templates/admin/mod_attachment/`, `config/site.config.example.php`)**:
    - Removed all 76 stale `site.version|default('...')` fallbacks across 34 Twig templates (and `site.app_template|default('amida')` in `mod_publication/archive.twig`) in favor of `{{ site.version }}`, and synchronized `config/site.config.example.php` fallback version to `1.0.8`.
    - Resolved two HTTP 404 stylesheet requests on detail views by removing non-existent `static/css/page-detail.css` from `mod_page/page.twig` and replacing non-existent `static/css/publication.css` with `static/css/content.css` in `mod_publication/publication.twig`.
    - Translated legacy Turkish comments in `layouts/base.twig`, `mod_video_embed/videos.twig`, and `static/css/content.css` into English.
  - **Hero Slider ORM Singleton Flush & SQL `LIMIT` Bounds (`mod/index/helper/mod_hero_slider.helper.php`, `mod/mod_publication/model/publications.model.php`)**:
    - Added `->flush()` calls before query construction on singleton ORM models (`mod_news.newses`, `mod_publication.publications`, `mod_user.user`), applied per-source `$news_model->limit($limit)` and `$publication_model->limit($limit)` before `gR()`, and declared `public $slider;` on `publications_model`.
  - **Uniform Hide-When-Empty Widget Policy (`templates/amida/index/mod_news/latest_news.twig`, `templates/amida/index/mod_video_embed/latest_videos.twig`, `templates/amida/index/mod_publication/latest_publications.twig`)**:
    - Removed the `{% else %}` empty-state branches from `latest_news.twig`, `latest_videos.twig`, and `latest_publications.twig` (and scoped `latest_publications.twig` stylesheet preloads inside the non-empty guard) so all homepage and widget blocks uniformly hide when their dataset is empty.
  - **Dedicated Docker Host Ports & MCP/Local Host Alignment (`docker-compose.yml`, `.env.example`, `.agents/skills/kevanger/SKILL.md`, `tools/mobile_simulator.html`, `tools/code_separation_guard.py`)**:
    - Parameterized `web` and `pma` ports (`BULLETIN_WEB_PORT:-8082`, `BULLETIN_PMA_PORT:-8083`) and aligned local runtime configuration (`config/site.config.local.php`) and the `amed-bulletin` MCP bridge (`~/.gemini/antigravity/bin/amed_bulletin_mcp.py`, `.agents/skills/kevanger/SKILL.md`) to port `8082`, allowing Bulletin (`8082`/`8083`) and Rojname (`8080`/`8081`) stacks to run concurrently with zero cross-stack traffic.

## v1.0.7 (2026-10-25)
- **MCP Multi-Repository Domain Isolation & Dedicated Bridge Architecture (`.gemini/settings.json`, `~/.gemini/`)**:
  - **Zero Cross-Repo Collision Policy**: Cleaned global MCP configuration namespaces (`amed-business`, `amidakurd`) that caused ambiguous targeting across sibling repositories (`AF/Business`, `AF/Rojname`, `AF/Bulletin`).
  - **Dedicated Bulletin MCP Bridge**: Provisioned `/home/malpersaz/.gemini/antigravity/bin/amed_bulletin_mcp.py` and registered local workspace server `amed-bulletin` in `.gemini/settings.json`, strictly binding endpoint authorization (`amcp_1f4a7d...`) and tooling to `AF/Bulletin` without risk of cross-repository data pollution or mutation.
- **Homepage Hero Slider & Left Editorial Column Modernization (`lib/`, `mod/index/`, `templates/amida/`, `mod/index/lang/`)**:
  - **16:9 Aspect Ratio Contract & MCP Upload Pipeline Alignment (`lib/McpNewsTools.class.php`)**:
    - Aligned `McpNewsTools::NEWS_IMAGE_SIZES` with admin controller specifications: `/` (`1280 × 720 px`, `option => 'cover'`) and `/thumb` (`480 × 270 px`, `option => 'cover'`), ensuring all images uploaded or curated via MCP preserve the exact `16:9` (`1.7778:1`) broadsheet aspect ratio.
  - **Hero Slider ACL Hardening & Runtime Feature Readiness (`mod/index/helper/mod_hero_slider.helper.php`)**:
    - Added `isFeatureReady('news.front')` and `isFeatureReady('publication.front')` guards to `loadNewsItems()` and `loadPublicationItems()`.
    - Integrated cached hierarchical ACL category resolution (`getAllowedCategoryIds()`) and session-aware SQL conditions (`getAllowedAclCondition('')`), guaranteeing restricted category contents never leak into the public hero slider.
  - **Category News Helper & Controller Architecture (`mod/index/helper/mod_news.helper.php`, `mod/index/index.php`)**:
    - Updated `_latest_news()` to query `Load::model('mod_news.newses')` (joining `mod_news_category`), enabling `category_name` and `category_seo` in column projections.
    - Implemented `_category_news(array $categoryCandidates, int $limit, ...)` in `mod_news_helper`, resolving localized candidate category names/slugs against active language settings, including category descendants via `getCategoryDescendantIds()`, enforcing ACL permissions, and producing 120-character truncated summaries (`summary_short`).
    - Added `_sports_news($t, $limit = 2)` widget method in `mod/index/index.php` with multilingual candidate resolution (`['Spor', 'Werzîş', 'Werzis', 'Sports', 'Sport', 'رياضة', 'وەرزش']`), populating both `sports_category_seo` and `sports_category_url`.
  - **Hero Slider Dual-Layer Backdrop Elimination & Exact `800 × 450 px` (16:9) Geometry (`templates/amida/index/hero_slider.twig`, `templates/amida/static/css/hero-news-slider.css`, `templates/amida/static/css/hero-news-slider.min.css`, `templates/amida/static/css/custom.css`, `templates/amida/static/css/custom.min.css`)**:
    - Removed redundant `.hero-slide__backdrop` blurred duplicate image element and dead CSS rules, eliminating dual-image network overhead for every hero slide.
    - Updated cover image tag dimensions to native `1280 × 720 px` (`width="1280" height="720" sizes="(max-width: 991px) 100vw, 800px"`).
    - Modernized `.hero-news-slider` and `.hero-news-swiper` to exact `800 × 450 px` (`aspect-ratio: 16 / 9; max-height: 450px; border: 0` with inset ring shadow so outer and inner boxes measure `800.00 × 450.00 px` on desktop, aligning 1:1 horizontally with the `384 × 450 px` Right Sidebar Block R1).
    - Enabled tactile bottom-right bullet pagination (`.hero-news-swiper-pagination`) with expanded active bullet animation and RTL positioning.
    - Synchronized both `hero-news-slider.min.css` and `custom.min.css` (loaded by `base.twig`).
  - **Exact `1200 px` (`800 px` Left + `16 px` Gap + `384 px` Right) Spatial Grid & 5-Block Broadsheet Stack (`templates/amida/index/index.twig`, `templates/amida/static/css/hero-news-slider.css`, `templates/amida/static/css/custom.css`)**:
    - Enforced deterministic CSS Grid geometry on `#homepageHeroRow` (`grid-template-columns: 800px 384px; gap: 16px; align-items: stretch;` at `>= 1224px` inside a `1200 px` content container, scaling proportionally via `minmax(0, 800fr) minmax(0, 384fr)` at `992px–1223px` and single-column below `992px`).
    - Eliminated duplicate `_hero_slider` invocation and standardized stylesheet loading in `{% block stylesheets %}` to prevent FOUC/CLS.
    - Arranged `#homepageHeroMain` (`800 px`) and `#homepageHeroSidebar` (`384 px`) as `display: flex; flex-direction: column; gap: 16px;` stacks with zero stray child margins (`#homepageHeroMain > *`, `#homepageHeroSidebar > *`):
      - **Block L1**: Hero News Slider (`800 × 450 px`, `16:9`, 10 items).
      - **Block L2**: Horizontal Authors Strip (`latest_authors.twig`, 3 columns, `12 px` box padding & `12 px` grid gap -> `250 px` cards).
      - **Block L3**: Latest News Grid (`latest_news.twig`, `.news-box-grow` with `flex: 1`, 6 cards in `3 × 2` grid, `12 px` box padding & `12 px` grid gap -> exact `248 × 140 px` `16:9` card media).
      - **Block L4**: Sports News Section (`sports_news.twig`, 2 horizontal split cards, `12 px` box padding & `12 px` grid gap -> `381 px` cards with `160 × 90 px` `16:9` media).
      - **Block L5**: Video Gallery (`latest_videos.twig`, 3 cards in `3 × 1` grid, `12 px` box padding & `12 px` grid gap -> `250 px` cards with `248 × 140 px` `16:9` media).
  - **Taste Skill & Impeccable Super Polish (`templates/amida/index/mod_publication/latest_authors.twig`, `templates/amida/index/mod_news/latest_news.twig`, `templates/amida/index/mod_news/sports_news.twig`, `templates/amida/index/mod_video_embed/latest_videos.twig`, `templates/amida/static/css/latest-authors.css`, `templates/amida/static/css/latest-news.css`, `templates/amida/static/css/sports-news.css`, `templates/amida/static/css/latest-videos.css`)**:
    - Aligned all Left Column blocks (`L2`–`L5`) to a unified `12 px` bordered editorial box system with `2 px` bottom-accented `.section-head` headers (title on left, uppercase view-all link on right).
    - Reordered `.latest-authors-item` in `latest_authors.twig` to place the `48 × 48 px` circular avatar (`width="48" height="48"`) on the left and author/publication copy on the right, matching `docs/plan/homepage-layout-wireframe.html`.
    - Added reading-time metadata (`reading_minutes`) to `sports_news.twig` alongside publication date, matching wireframe specifications.
    - Enforced WCAG AA contrast ratios (`>= 4.5:1`), `font-variant-numeric: tabular-nums` on all dates/timestamps/durations, `text-wrap: balance` on headlines, `:focus-visible` keyboard outlines, `@media (prefers-reduced-motion: reduce)` guards, and verified `0` anti-patterns under `impeccable detect --json`.
  - **10-Language Localization Parity (`mod/index/lang/*.php`)**:
    - Added `$lang['index']['mod_news']['sports_news']` (`title` and `all_items`) across all 10 language dictionaries (`ar`, `ckb`, `de`, `en`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`), maintaining 100% key parity under `tools/i18n_guard.py`.

## v1.0.6 (2026-10-25)
- **Public Legal & Statutory Policy Dossier Redesign — Taste Skill & Impeccable Super Polish (`templates/amida/index/legal/`, `templates/amida/partials/_legal_info.twig`, `templates/amida/partials/_legal_nav.twig`, `templates/amida/static/css/legal-info.css`, `templates/amida/static/css/cookie-policy.css`)**:
  - **Asymmetric 2-Column Editorial Dossier Architecture (`templates/amida/index/legal/*.twig`, `templates/amida/partials/_legal_nav.twig`, `templates/amida/static/css/legal-info.css`)**:
    - Replaced the single-column nested card layout (`detail-card` > `policy-section-card` > `policy-clause-item`) across all 8 statutory & policy endpoints (`/legal`, `/editorial-policy`, `/privacy-policy`, `/cookie-policy`, `/terms-of-service`, `/accessibility`, `/security-policy`, `/compliance`) with a unified 2-column Modern Press Editorial Dossier (`.legal-dossier-shell`, `.legal-dossier-grid`, `.legal-dossier-sidebar`, `.legal-document-sheet`).
    - Upgraded `templates/amida/partials/_legal_nav.twig` into a sticky vertical directory rail on desktop (`>= 992px`) that adapts into a horizontal scrollable navigation strip on tablet and mobile viewports.
    - Eliminated the missing `static/css/page-detail.css` asset reference across all 8 templates, resolving HTTP 404 stylesheet requests.
  - **Typographic Hierarchy, Craft-Floor Anti-Slop Discipline & Swiss Institutional Ledger (`templates/amida/partials/_legal_info.twig`, `templates/amida/static/css/legal-info.css`, `templates/amida/static/css/cookie-policy.css`)**:
    - Removed redundant pill badges above `<h1>` headings and banned duplicate section kickers (`.policy-section-kicker`, `.legal-card-kicker`) across `index.twig`, `_legal_info.twig`, `editorial_policy.twig`, `privacy_policy.twig`, `terms_of_service.twig`, `accessibility.twig`, `security_policy.twig`, `compliance.twig`, and `cookie_policy.twig`.
    - Established an editorial typographic scale pairing optical-size `Newsreader` serif display/section headings (`text-wrap: balance`, `-0.025em` tracking) with `Plus Jakarta Sans` body prose (`68ch` measure, `1.72` line-height) and `tabular-nums` registry values.
    - Refactored `templates/amida/partials/_legal_info.twig` into a flush 2-column Swiss Institutional Ledger with `1px solid` hairline dividers, conditional `hide_intro_desc` deduplication on `/legal`, and high-contrast light/dark theme tokens (`>= 4.5:1`).
    - Refactored policy chapters (`.policy-section-card`) into an asymmetric 2-column desktop ledger (`240px` header column + `1fr` prose and clause rows) and streamlined `.policy-footer-card` to eliminate verbatim paragraph duplication.
    - Verified zero findings with `impeccable detect --json`, `tools/code_separation_guard.py`, and `tools/i18n_guard.py`.
  - **Public Contact Page Route & `mod_settings`-Driven Contact Ledger (`config/url.config.php`, `mod/index/index.php`, `mod/index/helper/legal.helper.php`, `templates/amida/index/legal/contact.twig`, `templates/amida/partials/_legal_nav.twig`, `templates/amida/index/footer.twig`, `templates/amida/static/css/legal-info.css`, `config/site.config.php`, `static/mod_settings/`)**:
    - Registered `/contact` (`$url['contact'] = 'index/contact'`) in `config/url.config.php`, added `index_mod::contact()` and XML sitemap discovery in `mod/index/index.php`, and registered the `contact` (`ContactPage`) entry and multi-row `mod_settings` contact field fallback in `mod/index/helper/legal.helper.php`.
    - Created `templates/amida/index/legal/contact.twig` using the unified 2-column Editorial Dossier & Swiss Institutional Ledger design, dynamically rendering `mod_settings` communication channels (`site.phone`, `site.whatsapp`, `site.mail`, `site.office_address`, `site.work_address`), statutory & editorial contacts (`site.legal_company_name`, `site.authorized_person`, `site.managing_director`, `site.editor_in_chief`, `site.registered_email`, `site.legal_contact_phone`, `site.dpo_email`, `site.contact_address`, `site.corporate_address`), social links (`site.social_links`), and embedded location map (`site.map_embed_code`).
    - Added the `/contact` navigation link to `templates/amida/partials/_legal_nav.twig` and `templates/amida/index/footer.twig`, refreshed `static/mod_settings/` web manifest & icon assets, removed completed `docs/plan/SYSTEM_LEGAL_POLICY_PAGES_PLAN.md`, and synchronized `config/site.config.php` version to `1.0.6`.

## v1.0.5 (2026-10-25)
- **Statutory Publishing Credentials, Masthead (Künye), Corporate Schema & Comprehensive Legal Policy Pages Ecosystem (`installer/`, `mod/mod_settings/`, `lib/`, `mod/index/`, `config/url.config.php`, `templates/admin/mod_settings/`, `templates/amida/`)**:
  - **Database Schema & Publishing Governance Expansion (`installer/mod_settings/mod_settings.sql`, `mod/mod_settings/model/mod_settings.model.php`, `mod/mod_settings/index.php`)**:
    - Added 10 statutory columns to `mod_settings`: `legal_trading_name`, `company_registration_number`, `corporate_address`, `contact_address`, `managing_director`, `editor_in_chief`, `press_code_license`, `dpo_email`, `hosting_provider`, `compliance_jurisdiction`.
    - Added corresponding model properties, getters/setters in `mod_settings.model.php`, and automated runtime schema migration in `mod_settings_mod::ensureLegalInfoColumns()`.
  - **Phone-First Admin Künye & Legal Info Interface (`templates/admin/mod_settings/legal_info.twig`, `templates/admin/assets/css/legal_info.css`, `mod/mod_settings/lang/*.php`)**:
    - Reorganized `legal_info.twig` with Section 0: "Publishing Credentials & Masthead (Künye)" covering `managing_director`, `editor_in_chief`, `press_code_license`, `dpo_email`, `hosting_provider`, and `compliance_jurisdiction`.
    - Replaced empty `#company_type` dropdown with a text input and added fields for trading name, company registration number, corporate address, and contact address.
    - Synchronized all 10 language dictionaries in `mod/mod_settings/lang/` (`ar`, `ckb`, `de`, `en`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`), validated 100% clean with `tools/i18n_guard.py` and `tools/code_separation_guard.py`.
  - **MCP `update_site_settings` Tool Schema, Global Sync & Validation (`lib/McpSettingsTools.class.php`, `tasks/mcp_smoke.php`)**:
    - Synchronized `McpSettingsTools::definitions()` and `update_site_settings` handler with all 10 publishing and corporate fields, including strict email format checks (`dpo_email`, `registered_email`, `email`), automatic runtime column migration (`ensureSettingsColumns()`), global synchronization of `GLOBAL_LEGAL_FIELDS` across all language rows (admin `_save_legal_fields()` parity), and round-trip smoke assertions in `tasks/mcp_smoke.php` (49 passing checks).
  - **Canonical Policy Routing & Multi-Policy Controller Architecture (`config/url.config.php`, `mod/index/index.php`, `mod/index/helper/legal.helper.php`)**:
    - Added canonical routes and aliases in `config/url.config.php` for `legal`, `imprint`, `cookie-policy`, `cookies`, `privacy-policy`, `privacy`, `terms-of-service`, `terms`, `editorial-policy`, `editorial-guidelines`, `corrections-policy`, `accessibility`, `a11y`, `security-policy`, `security`, `compliance`, `ethics-policy`.
    - Extended `mod/index/index.php` with direct action delegates routing to `index.legal`.
    - Refactored `legal_helper::render($type)` into a unified deterministic registry handling all 8 policy archetypes (`info`, `cookies`, `privacy`, `terms`, `editorial`, `accessibility`, `security`, `compliance`) with runtime schema verification (`ensureLegalInfoColumns()`), metadata, canonical URLs, schema types, and dynamic context assignment via `$this->theme->set()`.
  - **Multilingual Policy Localization Parity (`mod/index/lang/*.php`)**:
    - Created `mod/index/lang/ru.php` and synchronized navigation keys, cookie fallbacks, feed/index labels (`llms_index`), and 6 dedicated policy section keys across all 10 language packs (`ar`, `ckb`, `de`, `en`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`), achieving 100% key parity with `en.php` (`0 missing`) and zero violations under `tools/i18n_guard.py`.
  - **Public Theme Templates, Policy Navigation Bar & Bento Card Design (`templates/amida/index/legal/`, `templates/amida/partials/`, `templates/amida/static/css/legal-info.css`, `templates/amida/index/footer.twig`)**:
    - Fixed meta description defect and enriched `NewsMediaOrganization` JSON-LD schema in `templates/amida/index/legal/index.twig` with `publishingPrinciples`, `correctionsPolicy` (`editorial-policy#corrections`), `actionableFeedbackPolicy`, `ethicsPolicy`, `diversityPolicy`, and `masthead`, plus `company_type` rendering in `templates/amida/partials/_legal_info.twig`.
    - Removed hardcoded English fallback strings from `templates/amida/index/legal/cookie_policy.twig`, `templates/amida/index/footer.twig`, and all 6 new policy templates, binding dynamic institutional contact/registry attributes (`dpo_email`, `registered_email`, `legal_contact_phone`, `corporate_address`, `contact_address`, `hosting_provider`, `compliance_jurisdiction`).
    - Created reusable responsive navigation component `templates/amida/partials/_legal_nav.twig` featuring horizontal scrolling pills and active page highlighting.
    - Created 6 dedicated semantic policy templates: `privacy_policy.twig`, `terms_of_service.twig`, `editorial_policy.twig` (with `#corrections` anchor), `accessibility.twig`, `security_policy.twig`, and `compliance.twig` with zero inline CSS/JS violations.
    - Extended `templates/amida/static/css/legal-info.css` with responsive navigation pills, card stack layouts, icon badges, clause lists, dark mode styles, and RTL support.
    - Enhanced `templates/amida/index/footer.twig` with a dedicated legal & policy links bar linking all 8 policies.
  - **XML Sitemap & llms.txt Machine-Readable Discovery (`mod/index/index.php`, `lib/LlmsService.class.php`)**:
    - Appended all 8 canonical policy URLs to `pageSitemap.xml` on page 1 with priority `0.60`.
    - Updated `## Optional` section in `lib/LlmsService.class.php` to include machine-readable discovery links for all 8 statutory policy endpoints.

## v1.0.4 (2026-10-24)
- **Active-Language Parity, Editorial Pipeline Hardening, MCP `definitions()` Architecture, `mod_settings/google_settings` Consolidation & Agent Timeout/Streaming Resilience (`lib/`, `mod/`, `templates/admin/`, `tasks/`, `tools/`, `.agents/skills/`)**:
  - **Active Language Discovery & Cross-Language Contamination Gate (`lib/McpCrudToolsBase.class.php`, `lib/McpSettingsTools.class.php`, `lib/McpNewsTools.class.php`, `lib/McpPublicationTools.class.php`, `lib/McpPageTools.class.php`, `lib/McpAnnouncementTools.class.php`, `lib/McpFaqTools.class.php`, `lib/McpHeadlineTools.class.php`, `lib/McpSurveyTools.class.php`, `.agents/skills/kevanger/SKILL.md`, `tools/audit_content_seo.py`)**:
    - Enriched `list_site_settings`, `get_site_settings`, and `listCategoriesGeneric()` with live `lang` table metadata (`lang_status`, `lang_name`, `is_default_lang`, `active_languages`, `inactive_languages`, `default_language`) and `active_only` filtering so agents distinguish `lang.status` from `site_settings.site_status`.
    - Implemented `McpCrudToolsBase::assertContentMatchesLang()` across all editorial create/update MCP handlers (`mod_news`, `mod_news_category`, `mod_publication`, `mod_publication_category`, `mod_page`, `mod_announcements`, `mod_faq`, `mod_headline`, `mod_survey`) to deterministically reject cross-language prose contamination before database writes.
    - Added `tools/audit_content_seo.py` (100-point editorial & SEO rubric for `mod_news`, `mod_publication`, `mod_page`, `mod_announcements`) and updated `.agents/skills/kevanger/SKILL.md` with pre-authoring active-language verification (`list_site_settings(active_only='y')`).
  - **MCP `definitions()` Decentralization, Curator Hardening, Resources, Prompts & Audit Dashboard (`lib/Mcp*.php`, `mod/mod_mcp/`, `templates/admin/mod_mcp/`, `templates/oauth/`, `lib/Amed.class.php`, `tasks/mcp_smoke.php`)**:
    - Refactored `lib/McpServer.class.php` to dynamically aggregate tool schemas via `public static function definitions(): array` across all 18 Bulletin domain tool classes (`news`, `publication`, `announcement`, `settings`, `menu`, `acl`, `attachment`, `document`, `faq`, `gallery`, `headline`, `imagecategories`, `page`, `popup`, `redirect`, `seo`, `slider`, `survey`, `video`), adding `McpPromptManager`, `McpResourceManager`, `McpAuditLogger`, and `structuredContent` responses.
    - Upgraded `McpCrudToolsBase`, `McpNewsTools`, `McpPublicationTools`, `McpPageTools`, and `McpAnnouncementTools` with `loadRecordByIdOrSeo()` (`seo` + `lang` resolution), `sanitizeBodyHtml()`, `synchronizeSummaryAndDescription()` (90–160 char window), and `registerSlugRedirectIfChanged()` (automatic HTTP 301 redirect creation and chain collapsing in `mod_redirect`), alongside language-prefixed `checkRedirects()` matching in `lib/Amed.class.php`.
    - Added `get_google_settings` Gemini API key masking (`has_gemini_api_key`), `mod_mcp/audit` Phone-First Audit Logs & Metrics dashboard (`mod/mod_mcp/index.php`, `templates/admin/mod_mcp/audit.twig`, `mod/mod_mcp/lang/*.php`), and expanded `tasks/mcp_smoke.php` to 47 passing assertions.
  - **Google Integration & Gemini AI Settings Consolidation (`mod/mod_settings/`, `mod/mod_seo/index.php`, `mod/mod_user/index.php`, `templates/admin/mod_settings/google_settings.twig`, `templates/admin/partials/header.twig`)**:
    - Moved the primary `google_settings` action and mobile-first admin view (`templates/admin/mod_settings/google_settings.twig`) into `mod_settings/google_settings`, converting `mod_seo/google_settings` into a backward-compatible HTTP 302 redirect and synchronizing all 10 `mod/mod_settings/lang/*.php` dictionaries.
  - **Agent Runner Timeout Ladder, SSE Streaming, Background Worker & Transaction Depth Tracking (`lib/Database/`, `lib/GeminiTransport.php`, `lib/AgentRunner.class.php`, `mod/mod_user/`, `templates/admin/mod_user/chat.twig`, `tasks/agent_worker.php`, `tasks/agent_timeout_smoke.php`)**:
    - Added `in_transaction()` and transaction depth tracking across `Database` and `PDO_MYSQL`/`PDO_POSTGRESQL`/`PDO_SQLITE` adapters.
    - Introduced `GeminiTransport`, upgraded `AgentRunner` (with Bulletin-scoped `INTENT_KEYWORDS`, 3-tier execution profiles `blocking`/`stream`/`job`, strike-based model health failover, single-flight lock, and `mod_agent_state`/`mod_agent_jobs` persistence), added SSE/background job endpoints in `mod/mod_user/index.php` and `templates/admin/assets/js/mod_user_agent.js`, and verified all 29 assertions in `tasks/agent_timeout_smoke.php`.

## v1.0.3 (2026-10-24)
- **Shared Core Traits, `BridgeCleanupService`, `Dbuilder`, `mod_attachment` Modernization, Public ACL Hardening & Admin UI/i18n Synchronization (`init.php`, `config/autoload.config.php`, `lib/`, `mod/mod_attachment/`, `mod/index/`, `templates/admin/`)**:
  - **Shared Validator, Public Helper Traits, `BridgeCleanupService` & SQL `ORDER BY` Escaping (`init.php`, `config/autoload.config.php`, `lib/Traits/ModuleValidatorTrait.php`, `lib/Traits/PublicHelperTrait.php`, `lib/BridgeCleanupService.class.php`, `lib/Database/Dbuilder.php`, `mod/index/indexBase.php`, `lib/BaseModuleController.class.php`)**:
    - Added `ModuleValidatorTrait` and `PublicHelperTrait` under `lib/Traits/` and registered them in `init.php` to support `mod_news` validator rules, cached ACL SQL conditions (`getAllowedAclCondition()`), ancestor-aware category ACL filtering (`getAllowedCategoryIds()`), and safe deserialization (`allowed_classes => false`).
    - Ported `lib/BridgeCleanupService.class.php` and registered `BridgeCleanupService` in `config/autoload.config.php` to resolve fatal `Class "BridgeCleanupService" not found` errors in `mod_news/all_video`, `mod_news/all_video_get`, and module uninstall routines.
    - Updated `Dbuilder::order()` (`lib/Database/Dbuilder.php`) to split comma-separated column strings and wrap each identifier individually with backticks, resolving MySQL 8.0 `1064` syntax errors on reserved SQL keywords (`parent_id, order` in `mod_news.helper.php` and `mod_publication.helper.php` archive pages).
    - Synchronized `mod/index/indexBase.php` and `lib/BaseModuleController.class.php` with `PublicHelperTrait`, `_mod_faq()`, `_normalizeAttachmentRows()`, attachment display-name resolution, null-safe `need_content_refs` iteration (preventing pre-doctype PHP warnings on empty `mod_attachment` tables so HTML5 Standards Mode and TinyMCE initialize cleanly), and hardened `unserialize()` calls (`['allowed_classes' => false]`).
  - **Attachment Module & Select-Picker Sheet Modernization (`mod/mod_attachment/`, `templates/admin/mod_attachment/`, `templates/admin/partials/sheets_extra.twig`, `templates/admin/assets/css/mod_attachment.css`, `templates/admin/assets/js/mod_attachment.js`)**:
    - Synchronized `mod/mod_attachment/index.php`, `mod/mod_attachment/lang/{ku,tr}.php`, `templates/admin/mod_attachment/{list,add}.twig`, `templates/admin/partials/sheets_extra.twig`, `templates/admin/assets/css/mod_attachment.css`, and `templates/admin/assets/js/mod_attachment.js` with `AF/Rojname`, eliminating all 27 inline `<style>` / `<script>` / `onclick` violations and resolving the `mod_attachment.css` 404 on admin pages.
  - **Public RSS & XML Sitemap ACL Filtering (`mod/index/index.php`)**:
    - Enforced anonymous ACL filtering (`(acl_id = 0 OR acl_id IS NULL)`) and allowed-category hierarchy checks (`getAllowedCategoryIds(..., true)`) across `rss()`, `newsSitemap()`, `publicationSitemap()`, `announcementSitemap()`, `videoSitemap()`, `gallerySitemap()`, `categorySitemap()`, and `pageSitemap()`.
  - **Phone-First Admin Styles & Public Index Language Cleanup (`templates/admin/assets/css/style.css`, `mod/index/lang/*.php`)**:
    - Synchronized `.standard-toolbar #toolbarDeck` active/selected states, `.main-fab.is-cancel` hover/active scoping (`body:not(.has-selection)`), and `#select-picker-sheet` mobile layout rules in `templates/admin/assets/css/style.css`.
    - Removed legacy unused keys (`add_content`, `mod_publication.latest_content`) and non-English error keys (`ad`, `mesaj`) across all 9 `mod/index/lang/*.php` dictionaries (`ar`, `ckb`, `de`, `en`, `fr`, `ku`, `sv`, `tr`, `zza`), standardizing `$lang['index']['error']` on `title` and `message`.

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
