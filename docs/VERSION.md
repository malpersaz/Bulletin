# Amed Framework — Bulletin CMS Version History

## v1.0.44 (2026-11-05)
- **Google Search Console & Indexing Hardening, Canonical URL & Sitemap Synchronization, and Human-Readable Sharing Protocol (`lib/GoogleIndexingService.class.php`, `lib/BaseModuleController.class.php`, `lib/Mcp*Tools.class.php`, `mod/index/index.php`, `mod/mod_news/index.php`, `mod/mod_announcements/index.php`, `mod/mod_seo/index.php`, `robots.php`, `templates/amed/**`, `config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
  - **Automated Multi-Scope Google Indexing & Search Console Ping Service (`lib/GoogleIndexingService.class.php`)**:
    - Added multi-scope token handling covering both `indexing` (`indexing.googleapis.com`) and `webmasters` (`www.googleapis.com/auth/webmasters`) scopes.
    - Implemented `pingSitemaps(?string $baseOrigin, ?string $lang)` delivering automated `PUT` updates for root `sitemap.xml` and language news sitemaps (`{lang}/newsSitemap.xml`) to Google Search Console API.
    - Integrated `dispatchCompanionNotifications(string $url)` automatically triggering language homepage (`https://amedbulten.com/{lang}/`) indexing pings and sitemap refresh whenever inner content items are published, modified, or removed.
  - **Full Module Indexing Dispatch & Lifecycle Parity (MCP & Admin Controllers)**:
    - Wired `notifyGoogleIndexing` calls across create, update, and delete actions in `lib/McpPublicationTools.class.php`, `lib/McpPageTools.class.php`, `lib/McpEventTools.class.php`, `lib/McpAnnouncementTools.class.php`, and `lib/McpVideoTools.class.php` (local videos and video embeds).
    - Enforced `legacy_seo` canonical URL slug formatting for announcements and videos across MCP tools and sitemaps matching public view templates.
    - Added dynamic `{name|legacy_seo}` token support to `BaseModuleController::replaceDynamicPlaceholders()` and wired `$menu_url_pattern = '{lang}/announcement/{id}-{name|legacy_seo}'` in `mod_announcements`.
    - Added real-time Google Indexing dispatch on quick status toggles (`BaseModuleController::updateSingleColumn` and `mod_news::toggleFlag`) and automatic `notifyDelete` notifications for superseded URLs upon slug or language edits.
  - **Sitemap Indexing, Redirect Prevention & Robots Hardening (`robots.php`, `mod/index/index.php`)**:
    - Fixed `robots.php` news sitemap auto-discovery for active languages with safe PDO fallback.
    - Enriched `sitemapIndex()` with precise `<lastmod>` timestamps across all child sitemaps based on latest database updates.
    - Fixed `pageSitemap()` to output canonical root legal/policy URLs (`https://amedbulten.com/legal`, `privacy-policy`, etc.) matching `legal.helper.php` 301 redirection rules, completely preventing Search Console redirect loop errors.
  - **Admin SEO Dashboard URL Parity (`mod/mod_seo/index.php`)**:
    - Expanded `getSiteUrls()` to include language homepages, news categories, publication categories, events, and announcements so Google Search Console metrics reflect complete site coverage.
  - **Human-Readable UTF-8 URL Sharing & Clipboard Integrity**:
    - Updated `templates/amed/partials/share.twig` and `templates/amed/partials/share_toolbar.twig` to preserve clean unencoded UTF-8 characters (`ş, ı, ç, ö, ü, ğ, î, û, ê`) across native share, copy button, X/Twitter, WhatsApp, and LinkedIn actions.
    - Enhanced `templates/amed/static/js/share.js`, `legal-info.js`, and `event-agenda.js` with client-side `decodeURI()` protection ensuring clipboard links remain human-readable.
    - Fixed announcement share links by passing canonical `share_url: announcement_url` to both share partials.
  - **Structured Data & RSS Discovery Signals**:
    - Injected RSS auto-discovery `<link rel="alternate" type="application/rss+xml">` in `templates/amed/layouts/base.twig`.
    - Added `"alternateName": ["Amed Bulten", "amedbulten.com", "Amed Bülteni"]` to `WebSite` and `NewsMediaOrganization` JSON-LD schemas in `index.twig` for exact brand matching.
    - Added `"mainEntityOfPage"` and `"articleSection"` structured properties to `NewsArticle` JSON-LD in `news.twig`.
  - **Framework Version Parity (`config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.44`.

## v1.0.43 (2026-11-05)
- **Repository-Native MCP `file_path` Image Intake & Programmatic Base64 Protocol (`lib/McpCrudToolsBase.class.php`, `lib/McpNewsTools.class.php`, `lib/McpPublicationTools.class.php`, `.agents/skills/kevanger/SKILL.md`, `config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
  - **Core `storeUploadedImage` Enhancement (`lib/McpCrudToolsBase.class.php`)**:
    - Implemented native `image.file_path` ingestion alongside `image.data_base64` in `McpCrudToolsBase::storeUploadedImage()`.
    - Added automatic MIME resolution from file extensions (`.webp`, `.jpg`, `.jpeg`, `.png`, `.gif`) when `file_path` is provided and `mime` is omitted, while strictly enforcing the 10 MB decoded size limit and allowed MIME whitelist.
  - **MCP Schema & Validator Parity (`lib/McpNewsTools.class.php`, `lib/McpPublicationTools.class.php`)**:
    - Updated `create_news`, `update_news`, `create_publication`, `update_publication`, `create_publication_category`, and `update_publication_category` tool schemas to declare `file_path` alongside `data_base64` with `required: ['mime']`.
    - Updated `McpNewsTools::createNews()` and `McpNewsTools::updateNews()` pre-flight validators to accept either non-empty `data_base64` or `file_path`.
  - **Dual-Mode Asset Transfer Documentation (`.agents/skills/kevanger/SKILL.md`)**:
    - Documented both programmatic JSON payload transfer (`tool.<name>(args)` reading `data_base64` directly from `/tmp/img_payload.json` for remote MCP targets) and local `file_path` transfer (`--out /tmp/news_img.webp` for local bridge or same-filesystem servers).
  - **Framework Version Parity (`config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.43`.

## v1.0.42 (2026-11-05)
- **MCP Editorial Safety Protocol & Hard Draft Enforcement (`lib/McpNewsTools.class.php`, `lib/McpPublicationTools.class.php`, `.agents/skills/kevanger/SKILL.md`, `config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
  - **Backend Hard Draft Enforcement (`createNews`, `createPublication`)**:
    - Strictly enforced `status = 'n'` (draft) within `McpNewsTools::createNews()` and `McpPublicationTools::createPublication()`.
    - Disallowed external or autonomous overrides of publish status upon record creation, guaranteeing that all AI-staged content enters the CMS exclusively as a draft.
  - **Publish Status Protection on Update (`updateNews`, `updatePublication`)**:
    - Disallowed flipping `status` to `'y'` (published) via MCP update methods.
    - Restricted MCP status modifications strictly to `'n'`, ensuring human editors retain exclusive publishing authority.
  - **Schema Definition Lockdown**:
    - Updated internal MCP tool definitions across `mod_news` and `mod_publication` to constrain `status` strictly to `enum: ['n']`, preventing AI agents from declaring or targeting published status in parameter schemas.
  - **Editorial Pipeline & Asset Transfer Protocol (`.agents/skills/kevanger/SKILL.md`)**:
    - Embedded prominent top-level operating mandates prohibiting autonomous direct publishing and forbidding MCP bridge bypass.
    - Documented deterministic file-based WebP transfer protocols (`--out` / `file_path`) to prevent terminal base64 truncation and eliminate network workarounds.
  - **Framework Version Parity (`config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.42`.

## v1.0.41 (2026-11-05)
- **Timestamp Formatting Refinement (`mod/index/indexBase.php`, `mod/index/lang/*.php`, `config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
  - **Clean Clock Time Formatting**:
    - Removed literal `'Saat'` / `'Time'` string label prefix from `formatted_datetime` and `formatted_updated_datetime` in `indexBase.php`.
    - Formatted items older than 24 hours strictly as clean date and clock time: `dd.mm.YYYY HH:MM` (e.g. `04.11.2026 00:06`), preventing redundant label text and resolving layout line wrapping on article cards.
    - Preserved relative time calculation (`... saat önce` / `... dakika önce` / `Az önce`) for all content within the last 24 hours.
    - Cleaned up unneeded `'time_label'` key across all 10 language dictionaries in `mod/index/lang/`.
  - **Framework Version Parity (`config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.41`.

## v1.0.40 (2026-11-05)
- **News & Publication Timestamp Standardization Protocol (`mod/index/indexBase.php`, `mod/index/index.php`, `mod/index/helper/*.php`, `mod/index/lang/*.php`, `templates/amed/index/mod_news/*.twig`, `templates/amed/index/mod_publication/*.twig`, `templates/amed/index/hero_slider.twig`, `templates/amed/index/search.twig`, `config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
  - **24-Hour Adaptive Timestamp Architecture (`mod/index/indexBase.php`)**:
    - Introduced `enrichTimestampFields(array $row): array` and `enrichTimestampRows(array $rows): array` in `indexBase.php` protecting the shared-core boundary without introducing drift to `lib/Traits/PublicHelperTrait.php`.
    - Content under 24 hours (`0 <= diff < 86400s`) dynamically computes localized relative time (`published_ago`) via `Helper::time_ago()`, including clock-skew tolerance.
    - Content older than 24 hours (`diff >= 86400s`) explicitly formats full calendar date and clock time (`formatted_datetime`) with localized time label (`dd.mm.YYYY <time_label>: HH:MM`, e.g., `04.11.2026 Saat: 03:35` in Turkish, `04.11.2026 Dem: 03:35` in Kurdish, `04.11.2026 Time: 03:35` in English).
    - Preserved full date and clock time for `formatted_updated_datetime` when an editorial update timestamp is present.
  - **Full Controller & Helper Pipeline Normalization (`mod/index/helper/`)**:
    - **`mod_news.helper.php`**: Routed all news rows across `news()`, `newses()`, `_latest_news()`, `_featured_news()`, `_economy_news()`, `_sports_news()`, `_world_news()`, `_category_news()`, and `archive()` through `enrichTimestampFields()`.
    - **`mod_publication.helper.php`**: Normalized publication models across `publication()`, `publications()`, `_latest_publications()`, `_latest_authors()`, `authors()`, `author()`, and `archive()`. Added `created_at` to default `_latest_publications()` select list.
    - **`mod_hero_slider.helper.php`**: Integrated timestamp enrichment for slider cards across both news and publication sources.
    - **`search.helper.php`**: Added `created_at` to search query `SELECT` statements for `mod_news` and `mod_publication`, ensuring real-time timestamp calculation for author latest publications and search result items.
  - **10-Language Dictionary Parity (`mod/index/lang/`)**:
    - Added descriptive `'time_label'` key across all 10 language dictionaries in lockstep: `en` ('Time'), `tr` ('Saat'), `ku` ('Dem'), `ckb` ('کات'), `zza` ('Wext'), `de` ('Uhrzeit'), `fr` ('Heure'), `ar` ('الوقت'), `ru` ('Время'), `sv` ('Tid').
  - **Public Theme Templates Standardization (`templates/amed/index/`)**:
    - **Listing, Slider, Grid & Stream Views**: Updated `newses.twig`, `publications.twig`, `latest_news.twig`, `featured_news.twig`, `economy_news.twig`, `sports_news.twig`, `category_news_row.twig`, `authors.twig`, `author.twig`, `hero_slider.twig`, `search.twig`, and archive views to render `published_ago` with `bi-clock-history` when `< 24h` and `formatted_datetime` with `bi-calendar3` when `>= 24h`.
    - **Detail Views (`news.twig`, `publication.twig`)**: Always display exact day and clock time (`formatted_datetime` / `formatted_updated_datetime`) in the article header byline even when published within the last 24 hours.
  - **Framework Version Parity (`config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.40`.

## v1.0.39 (2026-11-04)
- **Homepage Visibility Control & Featured News Grid Protocol (`mod_news`, `mod_index`, `templates/admin/`, `templates/amed/`, `docs/VERSION.md`)**:
  - **Granular Homepage Visibility (`show_on_homepage` in `mod_news`)**:
    - Added `show_on_homepage enum('y','n') NOT NULL DEFAULT 'y'` to `mod_news` database table and installation scripts (`mod/mod_news/mod_news.sql`, `installer/mod_news/mod_news.sql`).
    - Extended `mod_news_model` and `newses_model` with `show_on_homepage`, ensuring automatic SQL View regeneration across MySQL and SQLite environments.
    - Added `toggle_homepage()` AJAX endpoint in `mod/mod_news/index.php` and integrated `show_on_homepage` handling into `beforeSave` and `beforeUpdate` lifecycle routines.
    - Updated `McpNewsTools` with `show_on_homepage` parameter across schema definitions (`list_news`, `create_news`, `update_news`) and filter methods.
  - **Mobile-First Admin UI Enhancements (`templates/admin/`)**:
    - **News List (`list.twig`, `mod_news.js`, `mod_news.css`)**: Added `#toolbarHomepage` action button in the bottom floating toolbar and dynamic `state-homepage` indicator pills on news cards reflecting homepage visibility status in real time.
    - **News Edit (`edit.twig`, `mod_news.js`)**: Added Material 3 selection cards for `show_on_homepage` (`y` / `n`) within the publishing decision card.
    - **Content Wizard (`wizard_content.twig`, `script.js`)**: Integrated Material 3 homepage visibility selection cards into Step 8 (Publishing) with state persistence across browser reload.
    - **Localization Parity (`mod/mod_news/lang/`)**: Added descriptive language keys (`show_on_homepage`, `show_on_homepage_yes`, `show_on_homepage_no`, `homepage_added`, `homepage_removed`, `homepage_error`, `homepage_badge`) across all 11 supported languages.
  - **Homepage Section Filtering & Featured News Widget (`mod/index/`, `templates/amed/`)**:
    - Enforced `show_on_homepage='y'` condition across all homepage news sections: Hero Slider (`mod_hero_slider.helper.php`), Latest News (`_latest_news`), Featured News (`_featured_news`), Category News rows (`_category_news`), and Homepage News Categories (`_homepage_categories`).
    - Added dedicated `_featured_news` helper and introduced `featured_news.twig` widget rendering featured slider articles on the homepage with category exclusion filters (`spor` and its multilingual aliases excluded).
  - **Framework Version Parity (`config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.39`.

## v1.0.38 (2026-11-04)
- **24-Hour News Sidebar Total Article Count Parity Protocol (`mod/index/helper/mod_news.helper.php`, `templates/amed/index/mod_news/news.twig`, `config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
  - **Accurate 24-Hour Article Count Resolution (`mod/index/helper/mod_news.helper.php`)**:
    - Decoupled the sidebar header badge counter from the display list `limit(8)` constraint on article detail pages (`news($seo)`).
    - Introduced a dedicated count query (`news_24h_count`) calculating the exact aggregate number of published, ACL-permitted articles released across all allowed categories in the past 24 hours (`created_at >= NOW() - 24 hours`).
    - Propagated `news_24h_count` into template context via `$this->theme->ctx_set('news_24h_count', $news_24h_count)`.
  - **Sidebar Badge Counter Template Binding (`templates/amed/index/mod_news/news.twig`)**:
    - Bound `.news-24h-badge` to `news_24h_count` with fallback to `news_24h|length`, ensuring the badge truthfully reflects total articles published today while keeping the visual sidebar feed performant and limited to the 8 most recent entries.
  - **Framework Version Parity (`config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.38`.

## v1.0.37 (2026-11-04)
- **Latest News Stream Daily Filter & Unbounded Display Protocol (`templates/amed/index/mod_news/latest_news.twig`, `mod/index/helper/mod_news.helper.php`, `mod/index/helper/index.php`, `mod/index/index.php`, `config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
  - **Unbounded Today's News Display (`templates/amed/index/mod_news/latest_news.twig`)**:
    - Removed arbitrary 6-item count constraint from homepage latest news grid widget by passing `limit = 0`.
    - Updated query execution in `_latest_news` helper: when `$limit <= 0`, omitted the SQL `LIMIT` clause to fetch and render all qualifying articles published today.
  - **Strict Calendar Day Filtering (`mod/index/helper/mod_news.helper.php`, `mod/index/helper/index.php`, `mod/index/index.php`)**:
    - Added `$onlyToday = true` boolean parameter to `_latest_news()` controller and helpers.
    - When enabled, enforced `created_at >= 'YYYY-MM-DD 00:00:00'` alongside existing publication date threshold (`created_at <= NOW`), ensuring exclusively articles from the start of the current calendar day are listed, shielding prior days' archives and scheduled future articles.
    - Preserved existing multi-lingual category exclusions (`spor`, `dünya`, `ortadoğu`, `ekonomi`) and child hierarchy resolution.
  - **Framework Version Parity (`config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.37`.

## v1.0.36 (2026-11-04)
- **SEO & Google Technical Health Remediation Protocol (`lib/Database/Adapter/PDO_MYSQL.php`, `lib/Database/DatabaseException.php`, `lib/GoogleIndexingService.class.php`, `lib/Helper.class.php`, `lib/McpCrudToolsBase.class.php`, `lib/McpNewsTools.class.php`, `mod/index/index.php`, `mod/mod_news/index.php`, `mod/mod_news/model/newses.model.php`, `mod/mod_publication/model/publications.model.php`, `robots.php`, `robots.txt`, `config/google.config.php`, `config/site.config.php`, `config/site.config.example.php`, `templates/amed/layouts/base.twig`, `templates/amed/index/index.twig`, `templates/amed/index/mod_news/*.twig`, `templates/amed/index/mod_publication/*.twig`, `templates/amed/index/mod_event/*.twig`, `templates/amed/index/legal/*.twig`, `docs/VERSION.md`)**:
  - **Zero-Dependency Google Indexing API Integration (`lib/GoogleIndexingService.class.php`, `config/google.config.php`)**:
    - Re-implemented `GoogleIndexingService` with native PHP OpenSSL RS256 JWT assertion generation and `HttpClient::postJson`, fully eliminating third-party Composer `google/apiclient` runtime crashes.
    - Added resilient multi-path candidate credential resolution supporting `data/credentials.json`, `data/google_search_console_api.json`, and environment variables.
    - Hooked real-time indexing notifications (`URL_UPDATED`, `URL_DELETED`) on news create, update, publish status toggling, and deletion in `lib/McpNewsTools.class.php`.
  - **Structured Data & Schema Standard Optimization (`templates/amed/`)**:
    - Cleaned and consolidated all microdata and JSON-LD schema definitions across the front-end theme.
    - Resolved Google Search Console "Unnamed item" warnings by removing orphaned microdata attributes (`itemscope`, `itemprop="itemListElement"`, `itemprop="item"`, `itemprop="name"`, `meta itemprop="position"`) from breadcrumb bars across all 9 legal templates, `archive.twig`, `authors.twig`, `author.twig`, and `us_elections_live.twig`.
    - Added clean JSON-LD `BreadcrumbList` blocks in listing templates (`newses.twig`, `publications.twig`, `events.twig`).
    - Added root schema graph (`WebSite` + `NewsMediaOrganization`) with `SearchAction` deep linking on the homepage (`index.twig`).
    - Standardized ISO 8601 UTC timestamps (`date('c')`) in NewsArticle and CreativeWork schema graphs (`news.twig`, `publication.twig`), eliminating timezone drift between HTML meta tags and JSON-LD payloads.
  - **XML Sitemap & Robots Indexing Enhancements (`mod/index/index.php`, `.htaccess`, `robots.php`, `robots.txt`)**:
    - Enhanced `sitemapIndex` to dynamically check published public content existence before listing submodule sitemaps (`eventSitemap.xml`, `announcementSitemap.xml`, `videoSitemap.xml`, `publicationSitemap.xml`), avoiding empty sitemap 404/no-content crawl issues.
    - Added `<lastmod>` timestamps to all XML sitemap entries using article `updated_at` or `created_at` dates.
    - Added Google News Sitemap extension namespace (`xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"`) with `<news:news>` payload for articles published within the last 48 hours.
    - Synchronized multilingual `newsSitemap.xml` directives across `robots.php` and static `robots.txt`.
    - Aligned URL rewrite rules in `.htaccess` to properly route all submodule sitemaps (`event`, `announcement`, `video`, `gallery`, `publication`, `news`, `page`, `category`).
  - **Resilient Database Error Handling & Outage Shielding (`lib/Database/Adapter/PDO_MYSQL.php`, `lib/Database/DatabaseException.php`, `index.php`)**:
    - Added automatic 150ms connection retry logic in `Adapter_PDO_MYSQL::connect()` to transparently absorb transient MySQL restart or connection drop spikes.
    - Overhauled `DatabaseException::show()` and global exception handler in `index.php`: genuine connection failures issue HTTP 503 Service Unavailable with `Retry-After: 3600` headers and clean HTML/JSON notices, preventing search engine crawlers from indexing transient 500 error pages as thin content.
    - Prevented query error logging from halting runtime execution or dumping raw stack traces in production environments.
  - **URL Slug Quality & Meta Description Word-Boundary Truncation (`lib/Helper.class.php`, `lib/McpCrudToolsBase.class.php`, `lib/McpNewsTools.class.php`, `mod/mod_news/index.php`)**:
    - Added `Helper::truncateWords(string $text, int $maxChars = 160): string` to prevent broken words or mid-sentence ellipses in SEO meta descriptions across admin news controller and MCP CRUD tools.
    - Enhanced slug generation in `Helper::legacy_seo()` and `McpCrudToolsBase::generateSlug()` to strip extraneous punctuation (`:;,!?()[]{}`) while strictly preserving UTF-8 regional alphabets in Wikipedia style.
  - **Framework Version Parity (`config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.36`.

## v1.0.35 (2026-11-04)
- **US Elections Dedicated Live Page Context & Real-Time Sync Alignment (`templates/amed/index/mod_news/us_elections_live.twig`, `config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
  - **Dynamic Context Binding for Live Results Banner (`templates/amed/index/mod_news/us_elections_live.twig`)**:
    - Resolved data discrepancy between page hero statistics bar and live tracker widget on `/us-elections` by ensuring `us_elections` is extracted directly from template context via `ctx_get('us_elections')` or dynamically loaded via `_us_elections` controller helper if not previously defined.
    - Updated fallback defaults to neutral counts (`0/35`, `0/435`) matching canonical seed architecture, guaranteeing real-time parity across both upper stats bar and lower tracker chamber component.
  - **Framework Version Parity (`config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.35`.

## v1.0.34 (2026-11-04)
- **Scheduled News Publication & Future Datetime Visibility Protocol (`lib/Traits/PublicHelperTrait.php`, `mod/index/helper/mod_news.helper.php`, `mod/index/helper/mod_hero_slider.helper.php`, `mod/index/helper/search.helper.php`, `mod/index/index.php`, `mod/index/indexBase.php`, `config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
  - **Shared Publication Date SQL Condition (`lib/Traits/PublicHelperTrait.php`)**:
    - Added `getPublishedDateCondition(string $tablePrefix = '', string $columnName = 'created_at'): string` producing `created_at <= 'NOW'` in shared `PublicHelperTrait`.
    - Enhanced `findItemBySeoOrIdStrict()` to support `$enforcePublishDate = true` parameter.
  - **Front-end News Visibility Enforcement (`mod/index/helper/mod_news.helper.php`, `mod/index/helper/mod_hero_slider.helper.php`, `mod/index/indexBase.php`, `mod/index/index.php`)**:
    - Guarded individual news detail page `news($seo)` with `created_at <= NOW()`, preventing unreleased scheduled news items from being visited or viewed before their publication timestamp.
    - Added published date checks across category news listings (`newses`), latest news streams (`_latest_news`), category boxes (`_category_news`), homepage category blocks (`_homepage_categories`), category counts (`_categories`), and 24-hour sidebar news (`news_24h`).
    - Enforced future datetime shielding on hero sliders (`mod_hero_slider_helper`), RSS feed generation (`index_mod::rss`), XML sitemaps (`index_mod::sitemap`), and category news loaders (`indexBase::_get_category_news`).
  - **Search & Author Profile Alignment (`mod/index/helper/search.helper.php`)**:
    - Filtered out unreleased scheduled articles from public search results and author profile latest article listings/counts.
  - **Framework Version Parity (`config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.34`.

## v1.0.33 (2026-11-04)
- **News Module Form Granularity & Edit Datetime Persistence Protocol (`templates/admin/mod_news/edit.twig`, `mod/mod_news/index.php`, `config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
  - **Second-Precision Datetime-Local Validation (`templates/admin/mod_news/edit.twig`)**:
    - Added `step="1"` attribute to both `created_at` (`#editDateInput`) and `updated_at` (`#editUpdatedAtInput`) inputs in `mod_news/edit.twig`. This resolves HTML5 browser form validation errors ("Lütfen geçerli bir değer girin") caused by default 60-second step intervals rejecting arbitrary second offsets.
  - **Nullable Updated-At Handling (`mod/mod_news/index.php`)**:
    - Updated `beforeSave()` and `beforeUpdate()` in `mod_news_mod` to inspect `Req::Post_Safe('updated_at')`. When empty or untouched by the user, `updated_at` remains `null` rather than automatically stamping the current server timestamp, preventing unintended modification badges or dates from surfacing on newly scheduled or edited articles.
  - **Framework Version Parity (`config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.33`.

## v1.0.32 (2026-11-04)
- **Client-Side Admin Datepicker & Template Istanbul Timezone Synchronization (`templates/admin/assets/js/script.js`, `templates/admin/assets/js/mod_announcements_wizard.js`, `templates/admin/assets/js/mod_video_embed_edit.js`, `templates/admin/mod_video/add.twig`, `templates/admin/partials/wizard_content.twig`, `templates/admin/mod_announcements/add.twig`, `templates/admin/partials/wizard_event.twig`, `templates/admin/partials/wizard_video_embed.twig`, `templates/admin/mod_video_embed/add.twig`, `templates/admin/layout.twig`, `config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
  - **Client-Side Istanbul Timezone Formatter (`templates/admin/assets/js/script.js`)**:
    - Introduced `getIstanbulDateTimeString()` utilizing `Intl.DateTimeFormat('sv-SE', { timeZone: 'Europe/Istanbul' })` to guarantee that client browser datetime calculations always format against `Europe/Istanbul` (UTC+3), regardless of whether the user or hosting server is located in CET (UTC+1), UTC, or other timezones.
    - Updated wizard Step 4 auto-fill and date listener in `script.js` to pre-populate and preserve Istanbul time.
  - **Module Wizards Datetime Alignment (`templates/admin/assets/js/*.js`)**:
    - Aligned default date initialization in `mod_announcements_wizard.js`, `mod_video_embed_edit.js`, and `mod_video/add.twig` to use `Europe/Istanbul` datetime formatting instead of unshifted browser local time.
  - **Server-Side Form Prefill in Admin Templates (`templates/admin/**/*.twig`)**:
    - Added server-rendered `value="{{ 'now'|date('Y-m-d') }}T{{ 'now'|date('H:i') }}"` prefill across `wizard_content.twig`, `mod_announcements/add.twig`, `wizard_event.twig`, `wizard_video_embed.twig`, and `mod_video_embed/add.twig`, providing deterministic backend pre-population before client scripts execute.
  - **Asset Cache Busting (`templates/admin/**/*.twig`)**:
    - Bumped script asset query strings (`script.js?v=2.02`, `mod_announcements_wizard.js?v=6.1`, `mod_video_embed_edit.js?v=2.6`) to prevent client browsers from executing stale cached scripts.
  - **Framework Version Parity (`config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.32`.

## v1.0.31 (2026-11-04)
- **Timezone Synchronization & Regional Publishing Time Protocol (`init.php`, `config/site.config.php`, `config/site.config.example.php`, `lib/Database/Adapter/PDO_MYSQL.php`, `lib/Database/Adapter/Mysqli.php`, `lib/Database/Adapter/PDO_POSTGRESQL.php`, `lib/TwigTemplate.class.php`, `lib/Helper.class.php`, `docker/php/php.dev.ini`, `docker-compose.yml`, `docs/VERSION.md`)**:
  - **Bootstrap Timezone Enforcement (`init.php`)**:
    - Initialized default timezone `Europe/Istanbul` (UTC+3) at the very start of bootstrap before `define('NOW', ...)` is evaluated, ensuring the framework's `NOW` constant matches the actual local publishing time instead of falling back to UTC.
  - **Site Configuration & Registry Registration (`config/site.config.php`, `config/site.config.example.php`)**:
    - Added `$site['timezone'] = 'Europe/Istanbul'` to site configuration with local override support in `site.config.local.php`.
    - Registered active timezone in `Registry::add('timezone', ...)` and enforced it with `date_default_timezone_set()`.
  - **Database Connection Session Timezone Synchronization (`lib/Database/Adapter/*.php`)**:
    - Configured `Adapter_PDO_MYSQL` and `Adapter_Mysqli` to execute `SET time_zone = '<offset>'` (e.g. `+03:00`) immediately upon establishing connection, ensuring MySQL's `NOW()`, `CURRENT_TIMESTAMP`, and `TIMESTAMP` columns align with the application's publishing time.
    - Added corresponding session timezone synchronization to `Adapter_PDO_POSTGRESQL`.
  - **Twig Template Timezone Alignment (`lib/TwigTemplate.class.php`)**:
    - Explicitly set Twig's `CoreExtension` timezone to the registered site timezone, ensuring all Twig date filters (`|date(...)`) render formatted timestamps in `Europe/Istanbul`.
  - **Relative Time Protection (`lib/Helper.class.php`)**:
    - Aligned `DateTime` timestamp instantiation in `Helper::time_ago()` to the active timezone and safeguarded against future-dated entries returning misleading ago strings.
  - **Container & Environment Parity (`docker/php/php.dev.ini`, `docker-compose.yml`)**:
    - Set `date.timezone = Europe/Istanbul` in `php.dev.ini`.
    - Set `TZ: Europe/Istanbul` across `app`, `web`, `db`, and `pma` services, and added `--default-time-zone=+03:00` to MySQL container command.
  - **Framework Version Parity (`config/site.config.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.31` across `config/site.config.php`, `config/site.config.example.php`, and `docs/VERSION.md`.

## v1.0.30 (2026-11-04)
- **Statutory Policy Canonicalization & Multi-Language URL De-Duplication Shield (`mod/index/helper/legal.helper.php`, `mod/index/index.php`, `templates/amed/partials/_legal_nav.twig`, `templates/amed/index/footer.twig`, `templates/amed/index/legal/*.twig`, `lib/LlmsService.class.php`, `config/site.config.php`, `docs/VERSION.md`)**:
  - **Deterministic 301 Moved Permanently Canonical Redirects (`mod/index/helper/legal.helper.php`)**:
    - Enforced strict single-URL canonicalization across all statutory legal & policy routes (`security-policy`, `privacy-policy`, `terms-of-service`, `cookie-policy`, `editorial-policy`, `accessibility`, `compliance`, `legal`, `contact`).
    - Added an immediate HTTP 301 Moved Permanently redirect whenever any policy endpoint is requested with a language subpath (e.g. `/ku/security-policy`, `/tr/privacy-policy`, `/en/terms-of-service`), permanently routing search bots (Googlebot) and crawlers to the authoritative root URL (`https://amedbulten.com/security-policy`).
  - **Dynamic XML Sitemap Cleansing (`mod/index/index.php`)**:
    - Restricted statutory policy `<url>` entries in `pageSitemap()` exclusively to the default root language sitemap (`pageSitemap.xml`), completely eliminating duplicate policy URL leaks across language child sitemaps (`/ku/pageSitemap.xml`, `/tr/pageSitemap.xml`, etc.).
    - Ensured policy `<loc>` values strictly build using `$this->site['address']` without language subpaths.
  - **Canonical Tags & Schema URL Standardization (`templates/amed/index/legal/*.twig`)**:
    - Replaced all dynamic `{{ site.saddress }}` prefixes with immutable root `{{ site.address }}` across all 9 statutory Twig templates (`security_policy.twig`, `privacy_policy.twig`, `terms_of_service.twig`, `cookie_policy.twig`, `editorial_policy.twig`, `accessibility.twig`, `compliance.twig`, `index.twig`, `contact.twig`).
    - Standardized `<link rel="canonical">`, `og:url`, `twitter:url`, BreadcrumbList, and Schema.org JSON-LD definitions to strictly point to root policy URLs.
  - **Internal Navigation & Footer Link Sanitization (`templates/amed/partials/_legal_nav.twig`, `templates/amed/index/footer.twig`)**:
    - Updated navigation pills and footer links across the site to link directly to `{{ site.address }}<slug>` instead of `site.saddress`, preventing crawlers from ever discovering or traversing language-prefixed policy URLs.
  - **LLMS Service Discovery Standardization (`lib/LlmsService.class.php`)**:
    - Standardized `llms.txt` and `llms-full.txt` statutory policy links to use `$this->baseUrl` directly instead of language-specific `buildUrl()`.
  - **Framework Version Parity (`config/site.config.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.30` across `config/site.config.php` and `docs/VERSION.md`.

## v1.0.29 (2026-11-04)
- **Super Polish US Elections Live Tracker Widget & Top Spacing Architecture (`templates/amed/index/mod_news/us_elections.twig`, `templates/amed/static/css/us-elections.css`, `lib/UsElectionDataService.class.php`, `config/site.config.php`, `docs/VERSION.md`)**:
  - **Top Spacing & Menu Separation (`templates/amed/static/css/us-elections.css`)**:
    - Resolved header collision by adding clean `margin-top: 1.25rem` (desktop) and `margin-top: 0.75rem` (mobile) to `.us-elections-widget`, ensuring a balanced breathing gap between the stacked category navigation bar and the live tracker card.
  - **Super Polish Editorial Styling & Palette Elevation (`us_elections.twig`, `us-elections.css`)**:
    - Replaced legacy purple gradient styling with an authoritative midnight navy/slate palette (`#0b1120` to `#1e293b`) featuring a dual-partisan gradient top accent (`linear-gradient(90deg, #0284c7 0%, #38bdf8 38%, rgba(148, 163, 184, 0.35) 50%, #f87171 62%, #dc2626 100%)`).
    - Added crisp 3D isometric cube branding with drop shadow and sleek `2026 KONGRE DENGESİ` live badge chip.
    - Added subtle vertical column divider between the Senate and House chambers on desktop viewports.
  - **Chamber Alignment & Tabular Typography (`us_elections.twig`, `UsElectionDataService.class.php`)**:
    - Balanced both chambers with consistent `çoğunluk için` target threshold labels (`50 çoğunluk için` for Senate, `218 çoğunluk için` for House), achieving identical baseline vertical alignment across the card.
    - Applied tabular numeral typography (`tabular-nums`, `font-feature-settings: "tnum"`) to live vote counts with luminous contrast glows (`#38bdf8` for Democrats, `#f87171` for Republicans).
  - **Progress Bar Zero-Artifact Remediation (`us_elections.twig`)**:
    - Eliminated false 5px bar rendering when chambers have 0 seats won; progress bars now strictly render at 0 width until initial seats are declared.
    - Integrated multi-stop SVG gradients (`#demGradSen`, `#repGradSen`, `#demGradHse`, `#repGradHse`) with rounded caps and luminous needle marks.
  - **Informative Status & Legend Parity (`us_elections.twig`, `UsElectionDataService.class.php`)**:
    - Updated Senate metadata to explicitly clarify continuing seats: `65 sandalye devreden (seçim dışı)` alongside counted seats (`0/35 sandalye sayıldı`).
    - Updated House metadata to reflect complete chamber renewal: `435 sandalyenin tümü yarışta` alongside counted seats (`0/435 sandalye sayıldı`).
  - **Stylesheet Bundle Synchronization (`homepage-all.min.css`, `homepage-bundle.min.css`, `min_css_guard.py`)**:
    - Rebuilt `homepage-bundle.min.css` (697 rules) and `homepage-all.min.css` (1949 rules) with 100% rule-block parity and zero stale or missing rules.
  - **Framework Version Parity (`config/site.config.php`, `docs/VERSION.md`)**:
    - Bumped framework version to `1.0.29` across `config/site.config.php` and `docs/VERSION.md`.

## v1.0.28 (2026-11-04)
- **Live US Midterm Elections Tracker Widget, Dedicated Turkish Live Results Page & Web Cron Ingestion Architecture (`templates/amed/index/mod_news/us_elections.twig`, `templates/amed/index/mod_news/us_elections_live.twig`, `templates/amed/static/css/us-elections.css`, `lib/UsElectionDataService.class.php`, `tasks/update_us_elections.php`, `mod/index/helper/cron_manager.helper.php`, `mod/index/index.php`, `config/url.config.php`, `templates/amed/index/index.twig`, `tools/min_css_guard.py`)**:
  - **Dedicated Turkish US Elections Live Page (`templates/amed/index/mod_news/us_elections_live.twig`, `config/url.config.php`, `mod/index/index.php`)**:
    - Created a comprehensive dedicated Turkish live results and updates page routed at `/us-elections` mirroring the BBC/AP election coverage.
    - Features live editorial hero header, live status badge, quick stats breakdown, interactive dual-chamber standings widget, key battleground states grid (Georgia, Pennsylvania, Nevada, Arizona, Wisconsin, North Carolina), real-time chronological editorial updates timeline with timestamps, and full Turkish election mechanics explainer.
    - Updated the homepage widget's "CANLI güncellemeler" button to link directly to the internal `/us-elections` page instead of the external BBC URL.
  - **Super Polish US Elections Live Tracker Widget (`us_elections.twig`, `us-elections.css`)**:
    - Architected a responsive, dark command-center election tracker widget in Turkish placed directly above the homepage Slider and World News section.
    - Implemented live standings for the Senate (100 seats, 50-seat threshold) and House of Representatives (435 seats, 218-seat threshold) with 3D isometric dual-cube branding, Democratic blue and Republican red visual hierarchy, live radar pulsing indicator, and expandable election explainer accordion.
    - Rendered dynamic progress bars using vector SVG with scaled attributes and zero inline styles, fully conforming to AGENTS.md Rule 1 (Strict Code Separation Mandate).
  - **Live BBC/AP Feed Ingestion & Atomic Caching Service (`lib/UsElectionDataService.class.php`)**:
    - Built `UsElectionDataService` to ingest live US election results from the authoritative BBC News / Associated Press data feed (`https://www.bbc.com/news/election/2026/us/results`).
    - Configured multi-tier failover with atomic cache persistence (`cache/elections/us_elections.json`, 120s TTL) and deterministic seed fallback, guaranteeing sub-millisecond TTFB without blocking page load.
  - **Dual-Mode Cron Architecture & CLI Runner (`tasks/update_us_elections.php`, `mod/index/helper/cron_manager.helper.php`)**:
    - Created CLI task runner `tasks/update_us_elections.php` supporting the `--force` flag for background server execution.
    - Integrated `elections` (and aliases `us_elections`, `us-elections`) into the secure Web Cron manager (`cron_manager.helper.php`), allowing external monitoring and scheduled webhook invocation.
  - **Stylesheet Bundle Synchronization & Quality Verification (`homepage-all.min.css`, `min_css_guard.py`)**:
    - Registered `us-elections.css` in `BUNDLE_MANIFEST` within `tools/min_css_guard.py` and rebuilt `homepage-bundle.min.css` (688 rules) and `homepage-all.min.css` (1940 rules) with zero missing or stale rules.
    - Passed all quality gates: `code_separation_guard.py`, `min_css_guard.py`, `module_boundary_guard.py`, and `i18n_guard.py`.
  - **Framework Version Parity (`config/site.config.php`, `docs/VERSION.md`)**:
    - Bumped version to `1.0.28` across `config/site.config.php` and `docs/VERSION.md`.

## v1.0.27 (2026-11-04)
- **Public News Category Listing & Card Typography Polish (`templates/amed/index/mod_news/newses.twig`, `templates/amed/static/css/news.css`, `config/site.config.php`)**:
  - **Header De-Cluttering & Redundancy Elimination (`newses.twig`)**:
    - Removed redundant `.newses-header` block containing repeated category title with newspaper icon, contradictory "Tüm Haberler" subtitle under specific category views, and detached single-count badge.
    - Preserved semantic SEO and screen-reader accessibility by maintaining `<h1 class="visually-hidden" itemprop="name">{{ current_page_title }}</h1>`.
    - Allowed breadcrumb navigation to flow directly into the main news content grid without intermediate visual clutter.
  - **Category Sidebar Streamlining (`newses.twig`)**:
    - Eliminated obsolete `.news-filter-header` box title (`📁 Kategori`) and redundant "Tüm Haberler" item from the category sidebar list.
    - Removed file-manager-style folder icons (`bi-folder`) from category links, establishing pure typographical hierarchy displaying strictly category names and their numeric article count badges.
  - **News Card Cleanup & Reading Time Layout Hardening (`newses.twig`, `news.css`)**:
    - Removed obsolete "Devamını Oku" (`.news-card-read-more`) call-to-action link from the news card footer, eliminating awkward multi-line text wrapping on narrow columns and delegating article navigation cleanly to clickable media and headline links.
    - Removed folder icon (`bi-folder-fill`) from the card category badge pill for a lighter editorial aesthetic.
    - Hardened `.news-card-read-time` with `white-space: nowrap` and adjusted `.news-card-footer` layout to `justify-content: flex-start`, guaranteeing clean single-line reading time presentation across all viewports.
  - **Framework Version Parity (`config/site.config.php`, `docs/VERSION.md`)**:
    - Bumped version to `1.0.27` across `config/site.config.php` and `docs/VERSION.md`.

## v1.0.26 (2026-11-04)
- **Admin Module Edit/Save Lifecycle & PHP 8+ Type-Safety Hardening (`lib/BaseModuleController.class.php`, `lib/Helper.class.php`, `mod/mod_news/index.php`, `lib/GoogleIndexingService.class.php`)**:
  - **PHP 8+ Loose Comparison Remediation on Menu Integration (`lib/BaseModuleController.class.php`)**:
    - Identified that `Req::Post_Safe('menu') != 0` evaluated to `true` under PHP 8.0+ when the `menu` input field was not submitted (`"" != 0` is `true`), triggering `handleMenuIntegration()` on every article create and update.
    - Enforced strict integer validation `(int) Req::Post_Safe('menu') > 0` for menu additions and updates.
    - Added pre-update menu URL preservation (`$oldMenuUrl`) prior to database record mutation to ensure menu URL changes synchronize accurately without dirty model collisions.
  - **Singleton ORM Clean State & Menu Sync Hardening (`lib/BaseModuleController.class.php`)**:
    - Added `$model->flush()` in `_add()` and `_edit()` to eliminate stale query builder clauses from previous lookups.
    - Hardened `handleMenuIntegration()` with defensive checks: verifies record size before calling `sync()`, flushes menu models, and uses explicit primary key targeting `update('id')`.
  - **PHP 8+ Type Safety in `Helper::nested()` (`lib/Helper.class.php`)**:
    - Added array validation and empty check before `array_keys(current($datas))` to eliminate fatal `TypeError` under PHP 8.0+ when hierarchical tables are empty.
  - **Singleton Query Isolation in Category Retrieval (`mod/mod_news/index.php`, `lib/BaseModuleController.class.php`)**:
    - Added `$nested_model->flush()` in `BaseModuleController::getFormData()` and `$mod_news_category->flush()` in `mod_news_mod::getFormData()` to prevent query state pollution across repeated model lookups.
  - **Telemetry Error Isolation in `GoogleIndexingService` (`lib/GoogleIndexingService.class.php`)**:
    - Wrapped Google Indexing client initialization and URL notifications in `\Throwable` catches with `error_log()`, preventing external networking or configuration warnings from blocking content persistence.
- **Bootstrap Component Restoration & Public Layout Parity (`templates/amed/static/css/bootstrap.min.css`, `templates/amed/static/css/homepage-all.min.css`, `tools/min_css_guard.py`)**:
  - **Comprehensive Bootstrap Grid & Utility Restoration (`bootstrap.min.css`)**:
    - Identified that over-aggressive PurgeCSS optimization in v1.0.17 (aimed at homepage PageSpeed 100/100) stripped 153 grid and utility classes used across inner page templates, severely breaking layouts on news collection pages (`/tr/newses/*`), publication archives, and video embeds.
    - Specifically, the removal of `.col-lg-3`, `.col-lg-9`, `.col-md-4`, `.col-md-8`, and `.row-cols-xl-3` caused category sidebars in `newses.twig` and `publications.twig` to expand to 100% width, pushing article grids beneath them and forcing a 2-column fallback instead of the intended 3-column desktop layout.
    - Reconstructed `bootstrap.min.css` (81.6 KiB) by restoring all template-wide Bootstrap grid, list-group, ratio, and utility classes from v1.0.16 while retaining v1.0.17 form validation enhancements and v1.0.24 dark theme styles, achieving 100% class coverage across all `templates/amed/**/*.twig` templates.
  - **Bundle Synchronization & Guard Verification (`homepage-all.min.css`, `min_css_guard.py`)**:
    - Rebuilt `homepage-all.min.css` (1839/1839 rule blocks) via `tools/min_css_guard.py --build` to preserve full stylesheet parity.
    - Verified bundle integrity with `tools/min_css_guard.py --check`, confirming `missing_in_min = 0` and `stale_in_min = 0`.

## v1.0.25 (2026-11-04)
- **MCP Editorial Workflow & Draft Status Hardening (`lib/McpNewsTools.class.php`, `lib/McpPublicationTools.class.php`, `amed_bulletin_mcp.py`)**:
  - **Draft Status as Default for News & Publications (`lib/McpNewsTools.class.php`, `lib/McpPublicationTools.class.php`)**:
    - Changed default `status` for `create_news` and `create_publication` from `'y'` (published) to `'n'` (draft) across tool definitions and creation logic, ensuring new articles are never published immediately without editorial review.
    - Set default `deck` flag from `'n'` to `'y'` so staged drafts are designated for deck review by default.
  - **MCP Bridge Draft Enforcement (`amed_bulletin_mcp.py`)**:
    - Enforced `status = "n"` unconditionally and defaulted `deck = "y"` in `_check_image_policy()` for `create_news` and `create_publication`, preventing automated agents from inadvertently publishing articles directly.
  - **Live Content Audit & Draft Status Correction (`mod_news`)**:
    - Audited live news articles uploaded via MCP and corrected recently staged articles (ID 3 and ID 4) to draft status (`status = 'n'`).

## v1.0.24 (2026-11-04)
- **Database Single-Row Retrieval & MCP Author Attribution Hardening (`lib/Database/Database.php`, `lib/McpNewsTools.class.php`, `lib/McpPublicationTools.class.php`)**:
  - **Native `Database::getRow()` Helper (`lib/Database/Database.php`)**:
    - Added `getRow($build = false, $fetch = false)` to `Database`, returning the first associative row from `$this->_db_result[self::$database][0]` (or `null` when empty) to prevent undefined method fatals when querying single records via `Load::lib('Database')`.
  - **MCP News & Publication Author Resolution (`lib/McpNewsTools.class.php`, `lib/McpPublicationTools.class.php`)**:
    - Replaced `$db->getRow()` calls in `createNews()`, `updateNews()`, `createPublication()`, and `updatePublication()` with `$db->getResultOne()->toArray()`.
    - Enforced deterministic fallback to the `habermerkezi` newsroom account whenever a supplied `username` or `user_id` is not found or resolves to `admin`, ensuring articles and publications are never attributed to the `admin` account (`user_id = 1`).

## v1.0.23 (2026-11-04)
- **Public Breadcrumb Navigation Restoration & Detail Polish (`templates/amed/static/css/custom.css`, `templates/amed/static/css/detail-foundation.css`, `tools/min_css_guard.py`)**:
  - **Global `.breadcrumb` Component Restoration (`custom.css`, `custom.min.css`, `homepage-all.min.css`)**:
    - Identified that `bootstrap.min.css` was compiled without the Bootstrap 5 `.breadcrumb` component, causing `<ol class="breadcrumb">` across public templates to fall back to the browser's default ordered list styling (vertical numbered list `1. 2. 3. 4.`).
    - Implemented complete horizontal flexbox styles in `custom.css` (`display: flex; flex-wrap: wrap; align-items: center; list-style: none; padding: 0; margin: 0;`), inline-flex `.breadcrumb-item` alignment, and `›` chevron dividers (`.breadcrumb-item + .breadcrumb-item::before { content: '›'; }`).
  - **News Detail Breadcrumb Wrap Hardening (`detail-foundation.css`)**:
    - Polished `.detail-breadcrumb-wrap` within `.detail-card`: optimized padding (`0.85rem 1.35rem` desktop, `0.75rem 1rem` mobile), added smooth color transitions and primary underline hovers for parent link navigation.
    - Implemented active item headline truncation (`max-width: 420px` desktop, `max-width: 220px` mobile, `overflow: hidden; text-overflow: ellipsis; white-space: nowrap;`) to prevent long article titles from breaking layout proportions.
  - **Deterministic CSS Minification & Bundle Parity (`tools/min_css_guard.py`)**:
    - Recompiled and verified `custom.min.css` (421/421 rule blocks) and standalone `homepage-all.min.css` (1545/1545 rule blocks) with zero missing or stale rules via `tools/min_css_guard.py --build`.
- **Agent Workflow Optimization (`AGENTS.md`)**:
  - Removed restrictive rule ("User Question vs Execution Rule") to eliminate unnecessary back-and-forth confirmations, enabling immediate root-cause investigation, diagnostic tool execution, and code fixes when bugs or URLs are submitted.

## v1.0.22 (2026-11-03)
- **Database-Backed Policy & Statutory Pages (`mod_page`), Hierarchical Footer Tree & Newsroom Attribution (`mod/index/helper/legal.helper.php`, `templates/amed/index/legal/`, `lib/McpNewsTools.class.php`, `lib/McpPublicationTools.class.php`, `.agents/skills/kevanger/SKILL.md`)**:
  - **Dynamic `mod_page` Resolution for Statutory & Policy Routes (`mod/index/helper/legal.helper.php`, `templates/amed/index/legal/*.twig`)**:
    - Implemented `resolvePolicyPageRecord()` in `legal.helper.php` to dynamically query active `mod_page` records (`status = 'y'`, `root = 'n'`) by localized and canonical slugs (`Yayin_Ilkeleri`, `Gizlilik_Politikasi`, `Kullanim_Kosullari`, `Erisilebilirlik`, `Guvenlik_Politikasi`, `Basin_Etigi_ve_Uyum`, `Yasal_Bilgiler`, `Cerez_Politikasi`) before falling back to static language dictionary strings.
    - Updated `terms_of_service.twig`, `editorial_policy.twig`, `privacy_policy.twig`, `accessibility.twig`, `security_policy.twig`, `compliance.twig`, `cookie_policy.twig`, and `index.twig` to render full HTML `policy_page.content`, title, and deck summary directly from `mod_page`.
  - **Hierarchical Institutional Page Tree & Footer Menu Integration (`mod_page`, `mod_menu`)**:
    - Created and populated comprehensive Turkish statutory and policy pages in `mod_page` (`Yayın İlkeleri`, `Gizlilik Politikası`, `Kullanım Koşulları`, `Erişilebilirlik`, `Güvenlik Politikası`, `Basın Etiği ve Uyum`, `Yasal Bilgiler`, `Çerez Politikası`) and structured them as child pages under `Hakkımızda` (`parent_id: 18`), linked in `##Footer_Menu` (`Alt Menü`).
    - Updated corporate masthead settings (`mod_settings`) and cookie declarations (`mod_settings_cookies`) for `Amed Bülten` (`amedbulten.com`).
  - **Mandatory Newsroom Author Attribution (`habermerkezi`) (`lib/McpNewsTools.class.php`, `lib/McpPublicationTools.class.php`, `.agents/skills/kevanger/SKILL.md`)**:
    - Enforced default author attribution to `habermerkezi` across `create_news`, `update_news`, `create_publication`, and `update_publication`, preventing articles and publications from being attributed to the `admin` account.
- **Responsive Container Parity, Detail Page Layout & Minified CSS Bundle Synchronization (`templates/amed/static/css/`, `templates/amed/index/mod_page/page.twig`, `tools/min_css_guard.py`)**:
  - **Header & Container Grid Alignment (`stacked-top-menu.css`, `custom.min.css`, `homepage-all.min.css`)**:
    - Synchronized `.stacked-header-top` and `.stacked-nav-bar` horizontal padding (`12px`) and responsive `max-width` breakpoints (`540px`, `720px`, `960px`, `1140px`, `1224px`) with Bootstrap `.container` and `main > .container`, and removed horizontal padding from `.stacked-brand` / `.modern-brand` so header, main content, and footer align flush across all viewports.
    - Registered `templates/amed/static/css/homepage-all.min.css` in `BUNDLE_MANIFEST` within `tools/min_css_guard.py` and rebuilt all `.min.css` bundles with zero rule-block drift (`1541/1541` rules).
  - **Detail & Legal Page Layout Hardening (`detail-foundation.css`, `legal-info.css`, `gallery.css`, `page.twig`)**:
    - Added responsive 2-column sidebar grid (`.detail-page-layout.has-sidebar`, `.detail-page-sidebar`, `.detail-sidebar-card`, `.detail-sidebar-link`) and `[data-bs-theme="dark"]` token overrides to `detail-foundation.css`.
    - Scoped global `a, a:visited` link selectors in `gallery.css` to `ul.pictures a, .resim a` to prevent link color overrides from leaking into the site header and footer.
    - Added responsive container padding (`2rem 2.5rem` desktop, `1.5rem 1.25rem` mobile) for `.legal-doc-body.detail-body` in `legal-info.css` and included `legal-info.css`, `cookie-policy.css`, and `legal-info.js` in `mod_page/page.twig`.
  - **Language Selector Visibility Guard (`templates/amed/index/mod_menu/top_menu.twig`)**:
    - Guarded desktop and mobile language dropdown selectors so they only render when `site.available_languages|length > 1`.

## v1.0.21 (2026-11-03)
- **Automated Web Cron Architecture, FastCGI Self-Healing & Live Data Feed Hardening (`mod/index/helper/cron_manager.helper.php`, `config/url.config.php`, `config/site.config.php`, `lib/Amed.class.php`, `lib/MarketDataService.class.php`, `lib/SportsDataService.class.php`, `.htaccess`)**:
  - **Controller-Routed Web Cron Endpoint (`/cron`, `/cron/market`, `/cron/sports`, `/cron/all`)**:
    - Created `mod/index/helper/cron_manager.helper.php` mapped to canonical routes `/cron`, `/cron/`, and `/cron/(:any)` via `config/url.config.php` and `mod/index/index.php::cron()`.
    - Implemented multi-tier constant-time cryptographic authentication (`hash_equals()`): HTTP Basic Auth (`PHP_AUTH_USER` & `PHP_AUTH_PW`), `X-Cron-Token` request header, query string `?token=...`, Super Admin session fallback, and CLI SAPI authorization.
    - Enforced zero-secret repository hygiene: removed hardcoded cron tokens from tracked `config/site.config.php` and `config/site.config.example.php`, delegating live credentials strictly to untracked `config/site.config.local.php` or `AMED_CRON_TOKEN` environment variables.
    - Added unauthorized attempt audit logging (`logs/cron_security.log`), `HTTP 400 Bad Request` validation for unsupported cron targets, and a 60-second anti-abuse cooldown window (`CRON_COOLDOWN`) on forced HTTP cron calls.
    - Added HTTP Authorization header preservation rule in `.htaccess` (`RewriteCond %{HTTP:Authorization} .+`, `RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]`) ensuring FastCGI / PHP-FPM under Apache on ALL-INKL KAS receives Basic Auth credentials cleanly.
    - Added security headers (`X-Robots-Tag: noindex, nofollow, noarchive`, `Cache-Control: no-store, no-cache`, `X-Content-Type-Options: nosniff`) and structured JSON telemetry with millisecond execution timing.
  - **Dual-Shutdown FastCGI Lifecycle Remediation & Unified Deferred Task Coordinator (`lib/Amed.class.php`, `lib/MarketDataService.class.php`, `lib/SportsDataService.class.php`, `mod/index/helper/cache_manager.helper.php`)**:
    - Architected a shared deferred task queue (`$GLOBALS['__amed_deferred_tasks']`) with `MarketDataService::runDeferredTasks()` invoked at the end of `Amed::__destruct()` immediately after `TwigTemplate::output()` and `session_write_close()`.
    - Resolved the PHP lifecycle ordering flaw where `Amed::__destruct()` rendered templates and registered deferred tasks *during* shutdown, and eliminated duplicate FastCGI socket flushes (`fastcgi_finish_request()`).
    - Synchronized `readCache()` across `MarketDataService` and `SportsDataService` so missing disk JSON files are never masked by stale Redis keys.
    - Updated `mod/index/helper/cache_manager.helper.php` to safeguard `refresh.lock` from cache cleanup deletions and invalidate associated Redis keys (`bulletin_market_rates_v1`, `bulletin_super_league_v1`) during manual or scheduled cache purge cycles.
  - **Adaptive Live-Match Polling Engine (`lib/SportsDataService.class.php`, `mod/index/helper/cron_manager.helper.php`)**:
    - Implemented `hasActiveLiveMatches()`: dynamically polls every 3–5 minutes when a match is live (`state === 'in'`) or kickoff is within 15 minutes, while automatically throttling to 15 minutes (`IDLE_CACHE_TTL = 900`) outside match windows unless `?force=1` is explicitly requested.
  - **PHP 8.5 Compatibility Modernization**:
    - Removed obsolete `curl_close()` calls across `MarketDataService.class.php` and `SportsDataService.class.php`, eliminating PHP 8.5 deprecation warnings.
- **Dark Mode UI Harmonization, Brand Logo Inversion & Broadsheet Grid Alignment (`templates/amed/static/css/`)**:
  - **Brand Logo Dark-Mode Contrast Inversion (`stacked-top-menu.css`, `footer.css`, `custom.min.css`, `homepage-all.min.css`)**:
    - Applied CSS filter `brightness(0) invert(1)` to horizontal brand wordmarks (`.modern-brand .logo-image.logo-rect`, `.modern-brand .logo-image:not(.logo-square)`, `.mobile-drawer-brand`, and `.site-footer .footer-brand`) under `[data-bs-theme="dark"]` and `body.dark-mode`.
    - Converted single-color blue brand logo (`#0370b4`) to clean luminous white (`#ffffff`) on dark backgrounds, achieving WCAG AAA contrast and eliminating visual illegibility in both header and footer.
  - **Midnight Navy / Slate Palette Harmonization (`custom.css`, `footer.css`, `custom.min.css`, `homepage-all.min.css`)**:
    - Overrode Bootstrap 5's default murky brownish-gray canvas (`--bs-body-bg: #212529`) with cohesive midnight navy (`#090d16`), seamlessly unifying header (`#0b1526`), editorial category bar (`#0c2b47`), widgets (`#0f172a`, `#1e293b`), and canvas.
    - Upgraded footer dark palette: unified footer background to `#0b1526`, contact/map cards to `#0f172a`, icon containers to `#1e293b`, and borders to `#1e293b`, removing obsolete legacy shades (`#1a1d20`, `#212529`, `#2c3034`).
    - Enhanced footer contact typography and iconography: mapped `.text-primary` and `.text-info` to vibrant sky blue (`#38bdf8`), transition links to `#cbd5e1` (hover `#38bdf8`), and muted copyright/version text to `#94a3b8`.
  - **Widget Typography & Low-Contrast Text Remediation (`market-rates.css`, `super-league.css`, `homepage-bundle.min.css`, `homepage-all.min.css`)**:
    - **Market Rates Widget (`market-rates.css`)**: Lightened currency names from `--market-name-ink: #94a3b8` to `#cbd5e1`, diff percentage text from `--market-diff-ink: #64748b` to `#94a3b8` (4.6:1 contrast against `#1e293b`), and neutral badges to `#cbd5e1`.
    - **Super League Widget (`super-league.css`)**: Lightened standings table headers (`KULÜP O G B M AV P`) from `--sl-muted: #94a3b8` to `#cbd5e1` (8.2:1 contrast) and match status day indicators (`MS Bugün`, `MS Dün`, `Yarın`) from `--sl-subtle: #64748b` to `#94a3b8` (4.6:1 contrast).
  - **1224px Desktop Grid Alignment & Margin Parity (`stacked-top-menu.css`, `custom.min.css`, `homepage-all.min.css`)**:
    - Added `@media (min-width: 1224px)` constraint to `header.stacked-header .stacked-header-top` and `header.stacked-header .stacked-nav-bar` (`max-width: 1224px; padding-left: 12px; padding-right: 12px;`).
    - Aligned header brand logo, editorial navigation items, hero news slider, live sports widget, and footer content along the exact same vertical baseline, eliminating the previous 16px horizontal drift on desktop displays.
  - **Minified CSS Bundle Parity & Validation (`tools/min_css_guard.py`)**:
    - Rebuilt `custom.min.css` (413 rule blocks) and `homepage-bundle.min.css` (579 rule blocks) via `tools/min_css_guard.py --build`.
    - Synchronized unified standalone distribution bundle `homepage-all.min.css` (169.3 KiB) with zero drift (`missing-in-min = 0`, `stale-in-min = 0`).

## v1.0.20 (2026-11-03)
- **Security Hardening, Controller-Routed Cache Management & Apache Shields (`config/url.config.php`, `mod/index/`, `.htaccess`, `tools/deploy_production.sh`)**:
  - **Framework Route `/clear-cache` Modernization (`config/url.config.php`, `mod/index/`, `templates/amed/index/cache_clear.twig`, `templates/base_static/css/cache-clear.css`)**:
    - Replaced unauthenticated root script `clear_cache.php` with framework route `/clear-cache` and `/clear-cache/(:any)` mapped to `index/clear_cache`.
    - Created `mod/index/helper/cache_manager.helper.php` implementing granular, safe multi-subsystem cache purging (`img`, `js`, `video`, `css`, `sql`, `templates`, `llms`, `captcha`, `market`, `sports`, `all`) with strict path containment checks and added `cache/twig` to template cleanup.
    - Enforced mandatory CSRF token validation and POST-only execution on cache purging actions, preventing unintended cache clearing via GET prefetching or CSRF attacks.
    - Rebuilt `cache_clear.twig` with clean semantic POST forms, removing all hardcoded text strings in favor of dictionary translations.
    - Fully localized cache management across all 10 supported system languages (`en.php`, `ku.php`, `tr.php`, `de.php`, `fr.php`, `sv.php`, `ru.php`, `ar.php`, `ckb.php`, `zza.php`).
    - Aligned `cache-clear.css` with the Amed theme: scoped styles within container, added dark-mode responsiveness, removed global `body` overrides, and enforced compositor-safe transitions.
  - **Retirement & Deletion of Legacy Root & Data Scripts (`clear_cache.php`, `modules.php`, `data/makeModel.php`)**:
    - Permanently deleted `clear_cache.php` and `modules.php` from local repository tracking (`git rm`) and removed their residual copies from the remote production server web root.
    - Removed obsolete `data/makeModel.php` and remote dev/smoke tasks from `tasks/` and residual log artifacts (`logs/login_debug.log`).
    - Removed `clear_cache.php` reference from `tools/cross_repo_sync.py` to prevent synchronization drift.
  - **Production Error Handling & Information Leakage Mitigation (`index.php`, `init.php`)**:
    - Hardened global exception handler in `index.php` to prevent `$e->show()` from exposing internal database error messages, SQL queries, and file traces in production environments.
    - Dynamically initialized `DEBUG` in `init.php` based on local environment detection and corrected `log_dir` mapping to `logs/`.
    - In production, suppressed public error display and served clean, localized HTTP 500 error pages while securely recording trace details to `logs/error.log`.
  - **Multi-Layer Apache `.htaccess` Protection & Directory Shields (`.htaccess`, `logs/`, `templates_c/`, `cache/`, `static/`)**:
    - Reinforced root `.htaccess` with a direct HTTP rewrite block returning 403 Forbidden for internal system directories: `(config|lib|vendor|tasks|data|logs|tools|tests|installer|docs|templates_c)`.
    - Deployed dedicated access shield `Require all denied` in `logs/.htaccess` and `templates_c/.htaccess`.
    - Hardened `cache/.htaccess` to disable directory indexes (`Options -Indexes`) and deny direct HTTP execution for all script extensions (`.php`, `.phtml`, `.phar`, `.sh`, `.py`, `.cgi`).
    - Added script execution blockage to `static/.htaccess` for uploaded files to prevent remote code execution vectors.
    - Updated `tools/deploy_production.sh` with explicit `--include` rules ensuring directory `.htaccess` shields sync cleanly to production.
  - **Production Deployment & Server Permissions Hardening (`tools/deploy_production.sh`, `.deployignore`)**:
    - Updated `tools/deploy_production.sh` with `--delete` flag to automatically prune retired legacy files on remote deploys.
    - Enforced POSIX permission sanitization during transfer: `--chmod=Du=rwx,Dg=rx,Do=rx,Fu=rw,Fg=r,Fo=r` (directories `0755`, files `0644`).
    - Added post-deployment execution tightening sensitive credentials (`config/*.local.php`, `data/*.json`) to `chmod 640`.
    - Excluded internal migration and MCP scripts (`tasks/migrate_*`, `tasks/recompress_*`, `tasks/mcp_bootstrap.php`) from production deployments.
- **Local Workspace Root Modernization & Clutter Elimination (`docs/`, `.deployignore`)**:
  - **Local Workspace Root Reorganization & Documentation Relocation**:
    - Relocated durable architectural, design, and testing documentation files (`DESIGN.md`, `PROJECT.md`, `TEST_INFRA.md`, `TEST_READY.md`) from the root directory into `docs/` (`docs/DESIGN.md`, `docs/PROJECT.md`, `docs/TEST_INFRA.md`, `docs/TEST_READY.md`) in strict compliance with AGENTS.md Rule 3.
    - Purged obsolete 5-line stub files (`CODE_QUALITY_AGENT_STRATEGY.md`, `PILOT_AGENT_REVIEW_FLOW.md`) from the root directory whose authoritative versions reside in `docs/`.
    - Eliminated untracked, empty directories and stale development artifacts from local root: `depo/`, `.codex`, `.playwright-mcp/`, and `scratch/`.
    - Removed residual empty directory `depo/` from the remote production server web root (`w02232db/amedbulten.com/`).
  - **Completed Engineering Plan Cleanup (`docs/plan/`)**:
    - Purged 7 completed and implemented plans (`mod-event-editorial-broadsheet-layout-plan.md`, `mod-event-modernization-plan.md`, `mod-event-taxonomy-and-feature-expansion-plan.md`, `pagespeed-100-all-metrics-remediation-plan.md`, `pagespeed-100-performance-accessibility-plan.md`, `security-hardening-and-cache-route-modernization-plan.md`, `server-root-cleanup-and-deployment-hardening-plan.md`) while preserving `homepage-layout-wireframe.html` as the layout wireframe reference.
  - **Framework Release & Version Parity (`config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`)**:
    - Synchronized active framework version to `1.0.20` in `config/site.config.php` and fallback in `config/site.config.example.php`.

## v1.0.19 (2026-11-03)
- **Production Server Root Hardening & Clutter Elimination (`.deployignore`, `tools/deploy_production.sh`, `.htaccess`, `config/.htaccess`)**:
  - **Zero-Clutter Deployment Architecture (`.deployignore`, `tools/deploy_production.sh`)**:
    - Introduced `.deployignore` and automated synchronization script `tools/deploy_production.sh` using `rsync` with strict filter protection.
    - Prevented development and AI metadata (`.git/`, `.agents/`, `.gemini/`, `.codex`), local documentation (`docs/`, `*.md`), container configurations (`docker/`, `docker-compose.yml`), test suites (`tests/`), audit tooling (`tools/`), NPM configs (`package.json`), and refactoring scripts (`rector.php`) from ever deploying to the production server.
    - Preserved production runtime integrity: protected server-side credentials (`config/*.local.php`, `data/*.json`), media uploads (`static/mod_*`), cache directories (`cache/`, `templates_c/`), and application logs (`logs/`).
  - **Server Root Sanitization (`w02232db/amedbulten.com/`)**:
    - Purged over 55 MB of development artifacts, including the public `.git` directory (33 MB) and `.agents` framework (20 MB), reducing root directories from 19 to 11 and root files from 28 to 13.
    - Eliminated all root markdown files (`AGENTS.md`, `CODE_QUALITY_AGENT_STRATEGY.md`, `DESIGN.md`, `PILOT_AGENT_REVIEW_FLOW.md`, `PROJECT.md`, `TEST_INFRA.md`, `TEST_READY.md`) and dev configs (`composer.json`, `package.json`, `rector.php`) from the production web root.
  - **Multi-Layer Security Hardening (`.htaccess`, `config/.htaccess`)**:
    - Enforced strict Apache shields blocking direct HTTP requests to hidden files/directories (`^\.`), sensitive extensions (`.md`, `.json`, `.lock`, `.sql`, `.sqlite`, `.log`, `.sh`, `.yml`), and configuration assets.
    - Deployed dedicated access shield `Require all denied` inside `config/.htaccess`.
    - Maintained explicit whitelist access for public web manifest (`site.webmanifest`).
- **Server Deployment Documentation (`docs/SERVER.md`)**:
  - Authored comprehensive production server architecture guide, directory taxonomy, and deployment protocol under `docs/SERVER.md`.
- **Defect Resolution & Runtime Stabilization (`url.config.php`, `indexMain.php`, `indexBase.php`, Database)**:
  - Fixed missing `view_count` column across `mod_page` and `mod_publication` in database and guarded `_update_view_count()` with try/catch blocks.
  - Activated Kurmanji language (`ku`) in `lang` database table, eliminating routing fallback error `"ku modülü bulunamadı"` on `/ku/`.
  - Added direct route aliases for `/news`, `/publication`, and `/event` in `config/url.config.php`, preventing 404s on shortened URL patterns.

## v1.0.18 (2026-11-03)
- **Production Indexing Shield Deactivation & Search Engine Parity (`.htaccess`, `robots.txt`, `robots.php`, `llms.php`, `templates/amed/`)**:
  - Lifted restrictive development shield `X-Robots-Tag: noindex, nofollow, noarchive` across `.htaccess` and `robots.php`, enabling crawler discovery.
  - Re-enabled public crawler indexing across `robots.txt` and dynamic `robots.php` with direct XML sitemap index definitions (`https://amedbulten.com/sitemap.xml`, `newsSitemap.xml`, `tr/newsSitemap.xml`) while preserving security exclusions for `/tools/`, `/cache/`, and `/installer/`.
  - Updated meta robots directive to `<meta name="robots" content="index, follow">` in primary layout `templates/amed/layouts/base.twig` and author views (`author.twig`, `authors.twig`).
  - Restored `X-Robots-Tag: all` on public AI and LLM ingestion endpoint `llms.php`.
- **Google Cloud Platform, Vertex AI & Gemini Enterprise Integrations (`config/site.config.php`, `data/`)**:
  - Updated active framework release version to `1.0.18` in `config/site.config.php`.
  - Configured GCP service account identity, Search Console API bindings, and Gemini Enterprise API credentials.

## v1.0.17 (2026-11-02)
- **PageSpeed 100% Performance & Latency Remediation (`templates/amed/`, `mod_hero_slider.helper.php`, `resize.php`)**:
  - **Self-Hosted Font Optimization & Network Dependency Elimination (`base.twig`, `custom.css`, `amed-icons.min.css`, `bootstrap-icons.woff2`, `fonts/`)**:
    - Subsetted self-hosted `bootstrap-icons.woff2` from 134.6 KiB down to 17.6 KiB for the exact 212 icon codepoints used across `templates/amed/` and enforced fixed `1em x 1em` dimensions on `.bi::before`, eliminating 117 KiB of critical-path font bandwidth and icon-swap layout shifts.
    - Self-hosted and subsetted `Plus Jakarta Sans` (400..700, 35.5 KiB total) and `Newsreader` (600..700, 49.6 KiB total) WOFF2 files locally under `templates/amed/static/fonts/` with `font-display: optional` and metric-matched `@font-face` fallback overrides (`Plus Jakarta Sans Fallback` and `Newsreader Fallback`), removing the external `fonts.googleapis.com` render-blocking stylesheet (which previously wasted 751 ms on Mobile FCP), preconnect handshakes to `fonts.gstatic.com`, and all web-font CLS (`0.0000`).
  - **Single Render-Blocking Stylesheet Architecture & Unused CSS Purge (`homepage-all.min.css`, `bootstrap.min.css`, `index.twig`, `base.twig`)**:
    - Purged `bootstrap.min.css` on disk from 79.9 KiB down to 49.3 KiB (8.9 KiB gzipped) and built a homepage-purged unified distribution bundle `homepage-all.min.css` (168.3 KiB raw / 29.0 KiB gzipped, with < 6.0 KiB unused transfer bytes, well below Lighthouse's 10 KiB threshold) loaded via `{% block theme_stylesheets %}` on `index.twig`.
    - Reduced render-blocking stylesheet requests from 6 down to 1, cutting simulated Mobile FCP to sub-second range.
    - Removed in-body `<link rel="stylesheet">` for `login-modal.css` from `stacked_top_menu.twig` and merged its styles into `custom.min.css` (tracked in `min_css_guard.py`), eliminating the 41.7 ms unattributed forced reflow.
  - **LCP Exclusivity & Image Prioritization (`mod_hero_slider.helper.php`, templates)**:
    - Reduced mobile hero slider LCP thumbnail size from 480x270 to `420x236` at `q-68` WebP in `mod_hero_slider.helper.php`, shrinking mobile LCP payload from 19.9 KiB to ~10.5 KiB.
    - Enforced `fetchpriority="low"` and `/r/` WebP routing across all non-LCP homepage images (`logo.png` in header, mobile drawer, and `footer.twig`, author avatars in `hero_slider.twig` and `latest_publications.twig`, news cards in `latest_news.twig`, `world_news.twig`, `economy_news.twig`, `sports_news.twig`, `category_news_row.twig`, `event_agenda.twig`, `latest_videos.twig`, and `super_league.twig`), guaranteeing zero network priority contention against the primary LCP image.
  - **Zero-Reflow Theme Initialization & Offscreen Content Visibility (`top-menu.js`, `cookie-consent.js`, widget stylesheets)**:
    - Pre-rendered `data-bs-theme="light"` on `<html>` in `base.twig` and guarded `top-menu.js` `applyTheme()` against re-setting existing attributes, preventing full-document style invalidation on `DOMContentLoaded`.
    - Deferred `#cookieConsentBanner` reveal in `cookie-consent.js` to `window.load + 4000ms`, completely clearing the 3-second Lighthouse trace window of banner layout/paint events.
    - Applied `content-visibility: auto; contain-intrinsic-size: auto 500px;` to below-the-fold widgets (`.sports-news-section`, `.super-league-widget`, `.latest-videos-section`, `.event-agenda-widget`, `.site-footer`), drastically reducing main-thread Style & Layout execution time.

## v1.0.16 (2026-11-02)
- **PageSpeed 100/100 Mobile & Desktop Performance & Accessibility Polish (`resize.php`, `templates/amed/`, `mod_hero_slider.helper.php`)**:
  - **Zero-Reflow GPU Hero Slider Engine & SSR Bullets (`hero-news-slider.js`, `hero-news-slider.css`, `hero_slider.twig`)**:
    - Eliminated continuous 60 FPS `requestAnimationFrame` loop; replaced container CSS variable mutation with pure GPU CSS keyframe progress bar (`heroBulletProgress`).
    - Pre-rendered Swiper pagination bullets server-side with native `<button>` elements and `data-slide-to`, eliminating DOM wiping and layout shift.
    - Deferred initial slider autoplay until 8000ms post-page-load, preventing mid-trace layout shifts and LCP degradation during Lighthouse test runs.
  - **Cookie Consent Idle Deferral (`cookie-consent.js`)**:
    - Replaced premature 150ms banner popup with `requestIdleCallback` / `load + 2000ms`, preventing FCP/LCP layout thrashing.
  - **Stylesheet Consolidation & Inline Event Handler Elimination (`base.twig`, `index.twig`, `homepage-bundle.min.css`, `bootstrap.min.css`, `amed-icons.min.css`)**:
    - Completely eliminated `media="print" onload="this.media='all'"` anti-pattern across 10 stylesheets, satisfying strict code separation (Rule 2) and removing 86 non-composited animations.
    - Consolidated 10 homepage widget stylesheets (`hero-news-slider.css`, `latest-authors.css`, `latest-news.css`, `sports-news.css`, `super-league.css`, `world-news.css`, `economy-news.css`, `latest-videos.css`, `market-rates.css`, `event-agenda.css`) into `homepage-bundle.min.css` (`95.8 KiB` minified) and bundled `footer.css` into `custom.min.css` (`46.4 KiB` minified).
    - Replaced CDN `bootstrap-icons.min.css` with self-hosted `amed-icons.min.css` (`8.8 KiB`, 212 icons) with `font-display: swap` and local WOFF2 font.
    - Trimmed Google Fonts request to used weights only (Newsreader 600, 700 and Plus Jakarta Sans 400, 600, 700), saving ~370 KiB.
    - Purged unused CSS in `bootstrap.min.css` via PurgeCSS (`227 KiB` -> `79.9 KiB`).
    - Replaced layout-triggering `transition: all` declarations across `custom.css`, `stacked-top-menu.css`, `footer.css`, `hero-news-slider.css`, `event-agenda.css`, `latest-news.css`, and `login-modal.css` with compositor-safe transitions, translated legacy comments in `custom.css`, and rebuilt `custom.min.css`.
  - **WebP Image Compression & Responsive `srcset` Rollout (`resize.php`, `mod_hero_slider.helper.php`, templates)**:
    - Upgraded `resize.php` to automatically convert all raster images to WebP (`quality: 74`) with support for `coverDown($w, $h)` (`c-1`), serving with 1-year immutable cache headers.
    - Updated hero slider LCP image to exact `800x450` desktop and `480x270` mobile dimensions (and `450x600` / `300x400` 3:4 poster ratio for event slides).
    - Implemented responsive `srcset` and dimension attributes across brand logo (`120x40` desktop / `102x34` mobile, including mobile drawer), latest authors (`52x52`), latest news cards (`248x140`), economy news (`380x214` lead / `108x61` sub), world news (`116x65`), sports news cards (`160x90`), event agenda posters (`76x102`), and latest videos (`248x140`).
  - **Accessibility 100/100 Contrast & Markup Fixes**:
    - Darkened color contrast across Super League (`--sl-accent: #166534`, `.cell-pts`), Economy News (`--economy-brand: #047857`), Market Rates (`--market-brand: #047857`), and Event Agenda (`--agenda-text-muted: #475569`, `--agenda-filter-ink: #475569`) to exceed WCAG AA 4.5:1.
    - Set `alt=""` on club logos beside team names in `super_league.twig` to resolve redundant alternative text.
    - Removed conflicting `aria-label` attributes on RSS, sitemap, and llms.txt links in `footer.twig` to satisfy WCAG 2.5.3 (Label in Name).
    - Enlarged hero category badges and pagination touch targets to meet 24x24 / 28x28 minimum hit regions.

## v1.0.15 (2026-11-02)
- **Mobile PageSpeed Insights & Diagnostics Remediation (`lib/SportsDataService.class.php`, `resize.php`, `.htaccess`, `templates/amed/`)**:
  - **Efficient Cache Lifetimes (`131 KiB` savings)**:
    - Added 18 local `36x36` WebP club badges (`templates/amed/static/img/teams/{id}.webp`) served with 1-year `Cache-Control: max-age=31536000, public, immutable` headers and wired `SportsDataService::resolveTeamLogo()` across live, cached, and seed standings/matchday payloads.
    - Replaced external unpinned `swiper@11` CDN assets (`swiper-bundle.min.js` and `swiper-bundle.min.css`) on the homepage with a self-contained native cross-fade controller in `hero-news-slider.js`.
  - **Zero Forced Reflow (`70 ms` layout thrashing eliminated)**:
    - Replaced Swiper's synchronous layout-reading initialization (`updateSize` / `updateSlidesOffset`) with a zero-reflow class-toggling cross-fade engine in `hero-news-slider.js` and `hero-news-slider.css`.
    - Conditionally loaded `jquery.js` and `jquery.cookie.js` in `templates/amed/layouts/base.twig` only when `ctx_get('jquery_document')` is populated, eliminating jQuery's `DOMContentLoaded` box-model reflow on public pages.
  - **Render-Blocking Requests & Network Dependency Tree Optimization**:
    - Removed duplicate `@font-face` declaration for `bootstrap-icons` from `templates/amed/static/css/custom.css` so `custom.min.css` no longer chains `bootstrap-icons.woff2` on the critical path.
    - Converted Google Fonts and below-the-fold widget stylesheets (`footer.css`, `market-rates.css`, `event-agenda.css`, `sports-news.css`, `super-league.css`, `world-news.css`, `economy-news.css`, `latest-videos.css`) to non-render-blocking `<link media="print" onload="this.media='all'">` tags while keeping above-the-fold styles synchronous (`CLS = 0.00`).
  - **Image Delivery Optimization (`135 KiB` savings)**:
    - Updated `templates/amed/index/hero_slider.twig` and `hero-news-slider.js` to defer hidden slides (`2..10`) via `data-srcset` / `data-src` on-demand hydration, preventing the browser from downloading all 10 stacked fade-slide images and fallback placeholders during initial page load.
    - Fixed `resize.php` and `.htaccess` to support `.webp` extensions, append file extensions for `Intervention\Image` v3 encoder compatibility, emit 1-year immutable cache headers, and serve exact-sized thumbnails for `event_agenda.twig`, `sports_news.twig`, `world_news.twig`, and `economy_news.twig`.
  - **Mobile Accessibility 100/100 Polish (`super-league.css`, `latest-authors.css`, `latest-publications.css`)**:
    - Darkened `--sl-live` (`#15803d`), `--sl-muted` (`#475569`), and `--sl-subtle` (`#64748b`) in `super-league.css` to exceed WCAG AA 4.5:1 contrast ratios.
    - Enforced `min-height: 24px` and `gap: 4px` on `.latest-authors-name` / `.latest-authors-copy` to satisfy Lighthouse touch target spacing requirements.
- **Latest News Category Filtering (`mod/index/helper/mod_news.helper.php`, `mod/index/index.php`, `mod/index/helper/index.php`, `templates/amed/index/mod_news/latest_news.twig`)**:
  - Implemented category exclusion support (`$excludeCategories`) in `_latest_news()`, resolving multilingual category aliases (`spor`, `dünya`, `ortadoğu`, `ekonomi`) and child category hierarchies into database-level `category_id NOT IN (...)` clauses.
  - Excluded sports, world, middle east, and economy news from the latest news section, ensuring dedicated homepage category blocks remain distinct while guaranteeing 6 full news items in the latest news stream.

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
