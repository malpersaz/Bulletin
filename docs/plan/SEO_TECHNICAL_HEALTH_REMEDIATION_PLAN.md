# Google SEO Technical Health Remediation Plan

> Source audit: full codebase + live-site + live Google Search Console API (`sc-domain:amedbulten.com`) verification performed on **2026-11-04** (`amedbulten.com`).
> Scope: structured data rendering, SQL view timestamp parity, crawler-safe `503` vs `404` error semantics, Google News sitemap compliance, empty sitemap hygiene, Google Indexing API repair, Search Console & GA4 template wiring, and meta/artifact hygiene.

## 1. Executive Summary & Root Cause Analysis

### Context & Live Google Index Status (`site:amedbulten.com` Evaluation)
Amed Bulletin (`amedbulten.com`, default/only active language `tr`) serves Google via a master
`sitemap.xml` index, per-language child sitemaps, dynamic `robots.txt` (rewritten to `robots.php` via
`.htaccess:7`), RSS, and `llms.txt` AI feeds.

**Why `site:amedbulten.com` currently shows 0 results in public web search despite active Google indexing:**
- Direct inspection of `sc-domain:amedbulten.com` via the **Google Search Console API** (`webmasters/v3` and `v1/urlInspection/index:inspect`) confirms that **55 of 77 sitemap URLs are already `PASS` (`Submitted and indexed`) in Google's index**, including:
  - Both root `https://amedbulten.com/` and `https://amedbulten.com/tr/`
  - **26 of 34 published news articles** (the remaining 8 were published within 20–45 minutes of the audit; Googlebot's most recent crawl was at `2026-11-04T10:14:07Z` / `13:14` local time)
  - All **14 news category pages** (`/tr/newses/...`) and all **9 root statutory/policy pages** (`/legal`, `/editorial-policy`, `/privacy-policy`, etc.)
- The domain property `sc-domain:amedbulten.com` was first crawled on **2026-11-03 at 17:17:58 UTC** (~18 hours ago), `sitemap.xml` was first submitted on **2026-11-03 at 21:32:54 UTC** (~13.5 hours ago), and the earliest news article (`id=1`) was published on **2026-11-04 at 00:02:46 (+03:00)** (~14 hours ago).
- For domains and URLs indexed less than 24–72 hours ago, Google's URL Inspection index marks URLs as `PASS (Submitted and indexed)` immediately while public `site:` query shards and Search Console `searchAnalytics` impression tables take 24–72 hours to propagate.

### Audit Scorecard

| Area | Status | Evidence |
|---|---|---|
| Google Index Coverage (GSC API) | 🟢 55/77 Indexed | 26/34 news articles, 14/14 news categories, 9/9 legal pages, and homepage are `PASS (Submitted and indexed)` within 14h of launch |
| `robots.txt` / `robots.php` | 🟡 Minor drift | Live `200`; disallows `/tools/`, `/cache/`, `/installer/`; `robots.php:27` declares bare `/newsSitemap.xml` (without `/tr/`), and static `robots.txt` drifts from `robots.php` |
| Sitemap architecture & empty sitemaps | 🟡 Needs polish | `sitemap.xml` index unconditionally lists `publicationSitemap.xml`, `eventSitemap.xml`, `announcementSitemap.xml`, and `videoSitemap.xml` even when they have **0 items** (emitting empty `<urlset></urlset>`); `.htaccess:25` omits `event\|announcement\|video\|gallery\|content` |
| Canonical discipline (`/` vs `/tr/`) | 🟡 Split canonical | `/` issues a `301` to `/tr/` (`lib/Amed.class.php:437`), but `pageSitemap.xml` (`mod/index/index.php:1110`) lists `<loc>https://amedbulten.com/</loc>` and `base.twig:30` sets `og:url` to `https://amedbulten.com/`, causing GSC to report `userCanonical: https://amedbulten.com/tr/` vs `googleCanonical: https://amedbulten.com/` |
| Structured data (JSON-LD) | 🔴 Broken | `application/ld+json` count is **0** across all page types because `base.twig` omits `{% block schema %}`; GSC URL Inspection only sees HTML microdata `Breadcrumbs` with `"name": "Unnamed item"` |
| `article:modified_time` & SQL Views | 🔴 Broken | `newses` (`mod/mod_news/model/newses.model.php`) and `publications` (`mod/mod_publication/model/publications.model.php`) SQL views omit `updated_at`; in `news.twig:81`, `d.updated_at\|date('c')` evaluates `null\|date('c')`, emitting the **current request timestamp on every crawl** |
| Server & crawler error semantics | 🔴 At risk | Transient DB query failures are swallowed by `Database::query()` (`lib/Database/Database.php:103-108`), causing `ModuleRuntime::isReady()` / `guardFeatureOr404()` or `mod_news_helper::news()` to emit HTTP `404` + `error.twig` (`noindex, nofollow, noarchive`) |
| Google News sitemap | 🔴 Non-compliant | `mod/index/index.php:784-820` emits a plain `<urlset>` with `<priority>0.80</priority>` and no `xmlns:news`, no `<news:news>`, and no `<lastmod>` |
| Google Indexing API (`lib/GoogleIndexingService.class.php`) | 🔴 Broken | Throws `Error: Class "Google\Client" not found` (`logs/php-error-log.txt`) because `google/apiclient` is not in `vendor/`; MCP tools (`lib/McpCrudToolsBase.class.php`) never call it; and `indexing.googleapis.com` is `SERVICE_DISABLED` in GCP project `128050673733` |
| GSC + GA4 template wiring | 🔴 Broken | `base.twig:84-87` checks `site.google_analytics_id` while DB column is `site.google_analytics`, never calls `gtag('config', ...)`, and never renders `site.google_search_console` or `site.meta_tags` |
| Article `<meta name="robots">` | 🟡 Missing on detail | `news.twig:50`, `publication.twig:47`, `event.twig:33`, and `announcement.twig:30` override `{% block meta_tags %}` without including `<meta name="robots" content="index, follow, max-image-preview:large">` |

---

### Root Causes (Codebase + Live GSC API Verified)

1. **JSON-LD never rendered across templates + missing `updated_at` in SQL views (`article:modified_time` bug):**
   - Multiple templates define `{% block schema %}` with full JSON-LD payloads:
     - `templates/amed/index/mod_news/news.twig:96` (`NewsArticle`, `VideoObject`, `BreadcrumbList`)
     - `templates/amed/index/mod_publication/publication.twig:92` (`Article`, `BreadcrumbList`)
     - `templates/amed/index/mod_announcements/announcement.twig:85` (`Article`, `BreadcrumbList`)
     - `templates/amed/index/mod_imagecategories/gallery.twig:70` (`ImageGallery`, `BreadcrumbList`)
     - `templates/amed/index/mod_page/page.twig:81` (`WebPage`, `BreadcrumbList`, `FAQPage`)
     - `templates/amed/index/legal/index.twig:51` (`WebPage`, `BreadcrumbList`, `NewsMediaOrganization` / `ContactPage`)
   - The active layout `templates/amed/layouts/base.twig` **never invokes `{% block schema %}{% endblock %}`** inside `<head>`. As a result, Twig silently discards every `{% block schema %}` block, and Google Search Console URL Inspection detects **0** `NewsArticle` schemas on live articles.
   - **Inconsistent block in `event.twig`:** `templates/amed/index/mod_event/event.twig:60` places its `Event` and `BreadcrumbList` JSON-LD inside `{% block head %}` instead of `{% block schema %}`.
   - **Missing Homepage JSON-LD:** `templates/amed/index/index.twig` has no `{% block schema %}` (`WebSite` and `NewsMediaOrganization` structured data are absent on the homepage).
   - **Missing `updated_at` in `newses` and `publications` SQL `VIEW` definitions (causes fake `article:modified_time` on every crawl):**
     - `mod/mod_news/model/newses.model.php:18-45` (`CREATE VIEW newses`) selects `mod_news.created_at` but **omits `mod_news.updated_at`**.
     - `mod/mod_publication/model/publications.model.php:17-42` (`CREATE VIEW publications`) selects `mod_publication.created_at` but **omits `mod_publication.updated_at`**.
     - Because `d.updated_at` is `null` in Twig, `{{ d.updated_at|date('c') }}` in `news.twig:81` and `publication.twig:78` passes `null` to Twig's `date()` filter, which returns the **current wall-clock request time** on every page load! Both views must include `updated_at`, and templates must use `(d.updated_at ?: d.created_at)|date('c')`.

2. **Microdata Breadcrumb `"Unnamed item"` & Missing `max-image-preview:large` on Detail Pages:**
   - Detail templates (`news.twig:50`, `publication.twig:47`, `event.twig:33`, `announcement.twig:30`, `page.twig:70`, `gallery.twig:42`) override `{% block meta_tags %}` from `base.twig` without outputting `<meta name="robots" content="index, follow, max-image-preview:large">`. For Google News and Google Discover eligibility, `max-image-preview:large` is a critical signal.
   - In `templates/amed/layouts/base.twig:24`, the default `<meta name="robots" content="index, follow">` should also be upgraded to `<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">`.

3. **Split Homepage Canonical (`/` vs `/tr/`) in Google Search Console:**
   - `https://amedbulten.com/` returns HTTP `301` → `https://amedbulten.com/tr/` (`lib/Amed.class.php:437`).
   - However, `pageSitemap()` in `mod/index/index.php:1108-1113` emits `<loc>https://amedbulten.com/</loc>` (the 301-redirecting root URL) instead of `<loc>https://amedbulten.com/tr/</loc>` (`$this->site['saddress']`), and `base.twig:30,45` sets `og:url` and `twitter:url` to `{{ site.address }}` (`https://amedbulten.com/`).
   - Because the sitemap and `og:url` point to `/` while `<link rel="canonical">` on `/tr/` points to `/tr/`, Google URL Inspection reports a canonical split (`userCanonical: "https://amedbulten.com/tr/"`, `googleCanonical: "https://amedbulten.com/"`).
   - **Fix:** `pageSitemap()` must emit `<loc>` using `$this->site['saddress']` (or `$this->site['address'] . $this->site['lang'] . '/'`) for each active language's homepage, and `base.twig:30,45` must use `{{ site.saddress|default(site.address) }}`.

4. **Transient database failures masquerading as HTTP `404` + `noindex` (Critical Deindexing Vector):**
   - `lib/Error.class.php` is legacy unused code; active routing uses `lib/AmedError.class.php`, which sends `HTTP/1.1 404 Not Found` (`lib/AmedError.class.php:40`) and renders `templates/amed/index/error.twig` (`<meta name="robots" content="noindex, nofollow, noarchive">` at line 8).
   - **How a transient DB fault triggers a false `404` + `noindex` on a valid article URL:**
     1. `Database::query()` (`lib/Database/Database.php:103-108`) catches `DatabaseException`, calls `$e->show()`, and returns `false` without halting execution.
     2. In `mod/index/indexBase.php:375-388`, `guardFeatureOr404('news.front')` calls `ModuleRuntime::requireReady('news.front')`. If a transient DB error occurs, `ModuleRuntime::isReady()` returns `false`, and `guardFeatureOr404()` calls `$this->_404()` → `AmedError::error('not', ...)` → **HTTP `404 Not Found` + `noindex, nofollow, noarchive` on a valid article URL!**
     3. Similarly, in `mod_news_helper::news($seo)` (`mod/index/helper/mod_news.helper.php:135-149`), if the query fails without halting, `$news->getSize()` is `0`, falling into `else { return $this->_404(); }`.
     4. Furthermore, `DatabaseException::show()` (`lib/Database/DatabaseException.php:25-134`) never sets HTTP `503` or `Retry-After`, and `index.php:140` outputs `<meta name="robots" content="noindex, nofollow">` on uncaught exceptions.

5. **`newsSitemap.xml` non-compliance, empty child sitemaps in `sitemap.xml`, and `.htaccess` / `robots.php` drift:**
   - `mod/index/index.php:784-820` (`newsSitemap()`) outputs a generic `<urlset>` without `xmlns:news`, `<news:news>`, or `<lastmod>`.
   - **Empty Child Sitemaps in `sitemapIndex()` (`mod/index/index.php:735-782`):**
     - `publicationSitemap.xml`, `eventSitemap.xml`, `announcementSitemap.xml`, and `videoSitemap.xml` currently have **0 published rows** in the database, so they output empty `<urlset xmlns="..."></urlset>` documents (which Google Search Console flags as invalid/empty sitemaps).
     - `sitemapIndex()` should only list a child sitemap when that module has at least 1 published public item for the language (or `gallerySitemap`, `categorySitemap`, `pageSitemap`, `newsSitemap` which have items).
   - **Database schema constraint for child sitemaps (Task T5):**
     - Tables **with** timestamp columns: `mod_news` (`created_at`, `updated_at`), `mod_publication` (`created_at`, `updated_at`), `mod_event` (`created_at`, `updated_at`, `start_date`), `mod_announcements` (`created_at`), `mod_video` / `mod_video_embed` (`created_at`).
     - Tables **without** timestamp columns: `mod_imagecategories`, `mod_news_category`, `mod_publication_category`, `mod_event_category`, `mod_page`.
   - **`.htaccess` Rewrite Rule Gap:** `.htaccess:25` omits `event`, `announcement`, `video`, `gallery`, and `content`.
   - **Slug Sanitization (`Helper::sefurl` / `McpNewsTools`):** 5 early articles had literal `:` or `,` in their `seo` slug because `McpNewsTools` / `mod_news` allowed punctuation when generating slugs.

6. **`GoogleIndexingService` Crash (`Class "Google\Client" not found`), GSC/GA4 Template Bugs, and Session Cache Headers:**
   - **`lib/GoogleIndexingService.class.php` Crash:** Uses `Google\Client` (`google/apiclient`), which is **not installed in `vendor/`**, logging `Google Indexing API initialization failed: Class "Google\Client" not found` in `logs/php-error-log.txt`. Meanwhile, `mod/mod_seo/index.php:218-267` already implements native JWT `openssl_sign` + `HttpClient` OAuth2 token exchange without `google/apiclient`! Refactoring `lib/GoogleIndexingService.class.php` to use native `openssl_sign` + `HttpClient` (and hooking it into `lib/McpCrudToolsBase.class.php` as well as `BaseModuleController`) eliminates the fatal class error.
   - **GA4 & GSC Template Bugs (`base.twig:84-87`):**
     - `base.twig:84` checks `site.google_analytics_id` instead of `site.google_analytics`, never initializes `gtag('config', ...)`, never renders `site.google_search_console` or `site.meta_tags`, and has minor multiline tag formatting issues on `<meta name="theme-color">` (`base.twig:25-26`) and `<base href="...">` (`base.twig:51-52`).

---

## 2. Proposed Remediation Strategy

### Phase 0 — Restore Structured Data, SQL View Timestamps & Robot Directives (P0, Highest ROI)
1. **Wire `{% block schema %}` in `templates/amed/layouts/base.twig`:**
   - Add `{% block schema %}{% endblock %}` inside `<head>` immediately after `{% block meta_tags %}` (before `<base href="{{ site.address }}">`).
   - Fix multiline `<meta name="theme-color">` (`base.twig:25-26`) and `<base href="{{ site.address }}">` (`base.twig:51-52`) onto clean single lines.
   - Upgrade default robots meta in `base.twig:24` to `<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">`.
2. **Add `<meta name="robots" content="index, follow, max-image-preview:large">` & Safe `updated_at` Fallback in Detail Templates:**
   - In `templates/amed/index/mod_news/news.twig`, `templates/amed/index/mod_publication/publication.twig`, `templates/amed/index/mod_event/event.twig`, `templates/amed/index/mod_announcements/announcement.twig`, `templates/amed/index/mod_page/page.twig`, and `templates/amed/index/mod_imagecategories/gallery.twig`, include `<meta name="robots" content="index, follow, max-image-preview:large">` inside `{% block meta_tags %}`.
   - In `news.twig:81,124` and `publication.twig:78,113`, use `{{ (d.updated_at ?: d.created_at)|date('c') }}` so `article:modified_time` and `dateModified` never default to current wall-clock time if `updated_at` is null.
3. **Standardize `event.twig` schema block & Add Homepage JSON-LD:**
   - In `templates/amed/index/mod_event/event.twig:60`, change `{% block head %}` to `{% block schema %}`.
   - In `templates/amed/index/index.twig`, add `{% block schema %}` emitting `@graph` with `WebSite` (with `SearchAction`) and `NewsMediaOrganization`.
4. **Add `updated_at` to `newses` and `publications` SQL views:**
   - In `mod/mod_news/model/newses.model.php` and `mod/mod_publication/model/publications.model.php`, add `mod_news.updated_at` / `mod_publication.updated_at` to the `CREATE VIEW` SQL and add a `hasUpdatedAtColumn()` check inside `viewExists()` so existing views are automatically recreated with `updated_at`.

### Phase 1 — Crawler-Safe `503` Error Semantics & DB Resilience (P0)
1. **Prevent Transient DB Faults from Emitting `404` + `noindex`:**
   - In `lib/Database/DatabaseException.php` (`show()`):
     - For non-CLI, non-AJAX web requests, send `HTTP/1.1 503 Service Unavailable`, `Retry-After: 3600`, and `Cache-Control: no-store, no-cache, must-revalidate` — **without** `noindex` meta tags and **without** leaking SQL queries or stack traces — and terminate execution (`exit`) so `Database::query()` never returns `false` to `ModuleRuntime::isReady()` or `mod_news_helper::news()` to trigger a false `404` + `noindex`.
     - Log CLI SAPI errors clearly (`SAPI: cli`) in `logs/ajax_errors.log`.
   - In `index.php:131-196` (global exception handler):
     - Emit HTTP `503 Service Unavailable` + `Retry-After: 3600` for `DatabaseException` / `\PDOException` and omit `<meta name="robots" content="noindex, nofollow">` on `503` responses.
   - In `lib/Database/Adapter/PDO_MYSQL.php:40-48`:
     - Add a single connection retry (`usleep(150000)` — 150ms backoff) on initial `new PDO(...)` failure before throwing `AdapterException`.

### Phase 2 — Google News Sitemap, Canonical Alignment & Empty Sitemap Guard (P1)
1. **Upgrade `newsSitemap()` in `mod/index/index.php:784-820`:**
   - Add `xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"`.
   - Select `['name', 'seo', 'lang', 'created_at', 'updated_at']` from `mod_news.newses`.
   - Emit `<lastmod>` (W3C ISO 8601 format `c` from `updated_at ?: created_at`) and XML-escaped `<news:news>` (`<news:publication>`, `<news:publication_date>`, `<news:title>`) for articles published within the last 48 hours (falling back to recent articles if none are in the 48h window).
2. **Fix Canonical Homepage URL in `pageSitemap()` (`mod/index/index.php:1108-1113`):**
   - Emit `<loc>` as `$this->site['address'] . $this->site['lang'] . '/'` (e.g. `https://amedbulten.com/tr/`) instead of bare `https://amedbulten.com/` so the sitemap URL matches `<link rel="canonical" href="https://amedbulten.com/tr/"/>` and eliminates the `301` redirect and GSC canonical split.
3. **Exclude Empty Child Sitemaps in `sitemapIndex()` (`mod/index/index.php:735-782`):**
   - Only include `publicationSitemap.xml`, `eventSitemap.xml`, `announcementSitemap.xml`, and `videoSitemap.xml` in `sitemap.xml` when they contain ≥ 1 active public item for that language, preventing empty `<urlset>` warnings in GSC.
4. **Add `<lastmod>` to Child Sitemaps with Timestamp Columns (`mod/index/index.php`) & Fix `.htaccess`:**
   - Add `<lastmod>` to `publicationSitemap()`, `eventSitemap()`, `announcementSitemap()`, and `videoSitemap()`.
   - Update `.htaccess:25` to include `event|announcement|video|gallery|content`.

### Phase 3 — Native `GoogleIndexingService` (Zero `Google\Client` Dependency) & GSC/GA4 Wiring (P1)
1. **Refactor `lib/GoogleIndexingService.class.php` to Native JWT + `HttpClient`:**
   - Replace the missing `Google\Client` / `Google\Service\Indexing` dependency with native `openssl_sign` RS256 JWT + `HttpClient::postJson` (matching `mod/mod_seo/index.php:218-267`), with automatic fallback between `data/credentials.json` and `data/google_search_console_api.json`.
   - Note for GCP Admin: To use the Web Search Indexing API, `indexing.googleapis.com` must also be enabled in GCP Project `128050673733` (`amedbulten`).
2. **Fix GA4 & GSC Rendering in `templates/amed/layouts/base.twig` (Strict Code Separation Compliant):**
   - Support `site.google_analytics` (and `site.google_analytics_id` fallback), pass `data-ga-id` to external JS (`templates/amed/static/js/top-menu.js`), and render `site.google_search_console` and `site.meta_tags` in `<head>`.

### Phase 4 — Meta, Slug & Artifact Hygiene (P2)
1. **Homepage `<title>` Enhancement (`templates/amed/index/index.twig`):**
   - Include `site.site_name` alongside `site.title` when non-empty and distinct.
2. **`og:url`, `twitter:url`, and `og:locale` in `templates/amed/layouts/base.twig`:**
   - Use `{{ site.saddress|default(site.address) }}` for `og:url` and `twitter:url` (resolving the GSC `/` vs `/tr/` canonical split) and lift the full 10-language `og_locale` map into `base.twig`.
3. **Robots & Slug/Description Hygiene:**
   - Fix language-prefixed `/tr/newsSitemap.xml` URL in `robots.php` and sync `robots.txt`.
   - Strip colons/commas when generating slugs in `McpNewsTools` / `Helper::sefurl` and enforce word-boundary trimming (`≤ 160` chars) on auto-generated meta descriptions.

---

## 3. Step-by-Step Implementation Tasks

| Task ID | Priority | Component | File Path | Action |
|---------|----------|-----------|-----------|--------|
| **T1** | P0 | Layout & Templates | `templates/amed/layouts/base.twig`, `templates/amed/index/mod_event/event.twig`, `templates/amed/index/index.twig`, `templates/amed/index/mod_news/news.twig`, `templates/amed/index/mod_publication/publication.twig` | Add `{% block schema %}` to `base.twig`; standardize `event.twig` to `{% block schema %}`; add homepage JSON-LD to `index.twig`; add `<meta name="robots" content="index, follow, max-image-preview:large">` and `(d.updated_at ?: d.created_at)\|date('c')` fallback to detail templates |
| **T1b** | P0 | SQL Views | `mod/mod_news/model/newses.model.php`, `mod/mod_publication/model/publications.model.php` | Add `updated_at` to `newses` and `publications` SQL views with `hasUpdatedAtColumn()` auto-recreation check |
| **T2** | P0 | Error Semantics | `lib/Database/DatabaseException.php`, `index.php` | Emit HTTP `503 Service Unavailable` + `Retry-After: 3600` (without `noindex`) and halt on web `DatabaseException` so transient DB errors never fall through to `_404()` |
| **T3** | P0 | DB Resilience | `lib/Database/Adapter/PDO_MYSQL.php` | Add 1-step connection retry with 150ms backoff on initial `PDO` connect failure |
| **T4** | P1 | Google News Sitemap | `mod/index/index.php` → `newsSitemap()` | Add `xmlns:news`, select `['name', 'seo', 'lang', 'created_at', 'updated_at']`, emit XML-escaped `<news:news>` and `<lastmod>` |
| **T5** | P1 | Sitemaps & Canonical | `mod/index/index.php`, `.htaccess` | Use `/tr/` canonical homepage URL in `pageSitemap()`; omit empty child sitemaps in `sitemapIndex()`; add `<lastmod>` to `publication`, `event`, `announcement`, and `video` sitemaps; update `.htaccess:25` |
| **T6** | P1 | GSC, GA4 & Indexing API | `lib/GoogleIndexingService.class.php`, `config/google.config.php`, `templates/amed/layouts/base.twig`, `templates/amed/static/js/top-menu.js` | Replace missing `Google\Client` in `GoogleIndexingService` with native JWT + `HttpClient`; wire `site.google_analytics`, `site.google_search_console`, and `site.meta_tags` |
| **T7** | P2 | Homepage Title | `templates/amed/index/index.twig` | Override `{% block title %}` to include `site.site_name` alongside `site.title` when distinct |
| **T8** | P2 | Layout Meta Hygiene | `templates/amed/layouts/base.twig` | Use `{{ site.saddress\|default(site.address) }}` for `og:url`/`twitter:url`; use full 10-language `og_locale` map |
| **T9** | P2 | Robots Hygiene | `robots.php`, `robots.txt` | Fix language-prefixed `newsSitemap.xml` URL in `robots.php` and sync `robots.txt` |
| **T10** | P2 | Description & Slug Hygiene | `mod/mod_news/index.php`, `lib/McpNewsTools.class.php` | Enforce word-boundary trimming (`≤ 160` chars) on auto-generated descriptions and strip `:` / `,` from generated slugs |

---

## 4. Verification Matrix

| Check | Method | Expected |
|---|---|---|
| JSON-LD on articles, events, homepage, legal | Render templates / inspect `<head>` | `application/ld+json` present on homepage (`WebSite`, `NewsMediaOrganization`), article (`NewsArticle`, `BreadcrumbList`, accurate `dateModified`), event (`Event`), legal (`WebPage`) |
| SQL View `updated_at` parity | Inspect `newses` and `publications` view columns | `SHOW COLUMNS FROM newses LIKE 'updated_at'` and `SHOW COLUMNS FROM publications LIKE 'updated_at'` both return 1 row |
| Crawler-safe `503` on DB failure | Simulate `DatabaseException` on web request | Returns `HTTP/1.1 503 Service Unavailable` + `Retry-After: 3600`, zero `noindex` directives, and never falls through to `404` `error.twig` |
| Google News sitemap | Request `/tr/newsSitemap.xml` | Valid XML with `xmlns:news`, `<news:news>`, `<news:publication_date>`, `<news:title>`, and `<lastmod>` |
| Sitemap Index & Homepage Canonical | Request `/sitemap.xml` and `/tr/pageSitemap.xml` | `sitemap.xml` omits empty 0-item child sitemaps; `pageSitemap.xml` lists `https://amedbulten.com/tr/` matching homepage canonical |
| `GoogleIndexingService` | Instantiate `new GoogleIndexingService()` | Initializes cleanly via native JWT + `HttpClient` with zero `Class "Google\Client" not found` errors |
| Regression guards | `php -l` on all modified PHP files; `python3 tools/code_separation_guard.py`; `python3 tools/i18n_guard.py` | All pass with 0 errors |
