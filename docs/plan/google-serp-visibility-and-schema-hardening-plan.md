# Google SERP Visibility, Rich Result Disambiguation & Cache/Timestamp Hardening Plan

## 1. Objective

Resolve the post-`v1.0.44` organic SERP and Google News / Top Stories suppression where Google Search Console reports pages (such as `https://amedbulten.com/tr/news/galatasaray_amedspor_maçının_hakemi_belli_oldu`) as **"Submitted and indexed" (`PASS`)**, yet Google Search and Google News omit them from organic search results, Top Stories, and snippet caches.

This plan has been audited from both the **Amed Framework architecture** perspective and the **Google Search / Google News / Google Discover / GSC API** technical SEO engineering perspective, grounded in live diagnostics from:
1. **Google Search Console URL Inspection API (`urlInspection/index:inspect`)**
2. **Google Search Console Search Analytics & Sitemaps API (`webmasters/v3`)**
3. **Live Production HTTP Header Inspection (`https://amedbulten.com/...`)**
4. **Live Production Database Dump (`data/sqlite/d048d282.sql`)**

---

## 2. Comprehensive Technical SEO & Google Services Root-Cause Analysis

### 2.1. Live Database Audit (`data/sqlite/d048d282.sql`) — Security & Timestamp Forensics
- **No Rogue APIs or Malicious Injections:** Audited all tables (`mod_settings`, `mod_redirect`, `mod_mcp_api_key`, `mod_agent_state`, `mod_mcp_audit_log`, `mod_user`, `mod_page`, `mod_news`). Only one legitimate MCP key (`Kevanger`, `id=1`) exists, `mod_redirect` is empty, and no hidden `noindex` or unauthorized third-party scripts exist in the database.
- **Timestamp Timezone Drift (`created_at` in UTC vs `updated_at` in UTC+3) & `datePublished > dateModified` Violations:**
  - In `mod_news` (rows 22–61), `created_at` was stored in UTC (e.g., ID=60 `Galatasaray-Amedspor maçının hakemi belli oldu` has `created_at = '2026-11-05 09:10:00'`), whereas MySQL's `ON UPDATE current_timestamp()` wrote `updated_at = '2026-11-05 12:13:36'` in `Europe/Istanbul` (`+03:00`).
  - Worse, for rows **12, 18, and 21**, `created_at` is chronologically **later** than `updated_at` (`datePublished > dateModified`), which is a hard schema validation error in Google `NewsArticle` processing.
  - In [`mod/mod_news/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_news/index.php#L139), `beforeUpdate()` sets `$model->updated_at = !empty($updatedAt) ? $this->normalizeDateTime($updatedAt) : null;` when editing via the Admin form, leaving `updated_at` null or dependent on raw MySQL clock rather than PHP's configured timezone (`Europe/Istanbul`).

### 2.2. Root Cause 1: `NewsArticle` Rich Result Failure & Microdata Entity Pollution
- **Live GSC URL Inspection Evidence:** Inspecting `https://amedbulten.com/tr/news/galatasaray_amedspor_maçının_hakemi_belli_oldu` via the GSC URL Inspection API returned:
  - `verdict`: `PASS` (`Submitted and indexed`)
  - `richResultsResult`: Only detected `Breadcrumbs` (with warning `Unnamed item`) and **completely failed to detect `NewsArticle`!**
- **Why Google Ignored `NewsArticle` on Detail Pages:**
  1. **9 Conflicting `NewsArticle` Entities on Every Article Page:** In [`templates/amed/index/mod_news/news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/news.twig#L506), every sidebar card in the "Son 24 Saat" widget (`news-24h-item`) declares `<article class="news-24h-item" itemscope itemtype="https://schema.org/NewsArticle">` without required `NewsArticle` properties (`author`, `publisher`, `image`, `dateModified`). Listing partials (`newses.twig`, `latest_news.twig`, `featured_news.twig`, `economy_news.twig`, `sports_news.twig`, `world_news.twig`, `category_news_row.twig`) also inject `itemscope itemtype="https://schema.org/NewsArticle"` on summary cards.
  2. **Escaped Slashes and Unicode in JSON-LD (`|json_encode|raw`):** Twig's default `json_encode` escapes slashes (`\/`) and UTF-8 characters (`\u00e7`, `\u0131`). Consequently, `@id` in `mainEntityOfPage` (`https:\/\/amedbulten.com\/tr\/news\/galatasaray_amedspor_ma\u00e7\u0131n\u0131n_hakemi_belli_oldu`) does not match `<link rel="canonical" href="https://amedbulten.com/tr/news/galatasaray_amedspor_maçının_hakemi_belli_oldu">`.
  3. **Missing `item` URL on Final Breadcrumb ListItem Across Templates:** In [`news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/news.twig#L175-L179), [`newses.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/newses.twig#L59-L70), [`publication.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_publication/publication.twig#L139-L143), [`publications.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_publication/publications.twig#L58-L69), [`event.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_event/event.twig#L90-L115), [`events.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_event/events.twig#L63-L75), [`announcement.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_announcements/announcement.twig#L131-L135), and [`gallery.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_imagecategories/gallery.twig#L98-L102), the final `BreadcrumbList` item omits `"item"`, triggering GSC's `Unnamed item` issue.
  4. **Missing `isAccessibleForFree`, `inLanguage`, and Structured `ImageObject`:** Google News `NewsArticle` guidelines require `"isAccessibleForFree": true`, `"inLanguage": "{{ site.lang }}"`, and structured `ImageObject` dimensions (`width: 1280`, `height: 720`).
  5. **Broken Author E-E-A-T Link (`404 Not Found` on `/tr/author/admin`):**
     - In `d048d282.sql`, all 61 news articles have `user_id = 1` (`username = 'admin'`, `group_id = 1`).
     - In [`news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/news.twig#L114-L115) (and line 229 in HTML), `NewsArticle` JSON-LD outputs `"author": {"@type": "Person", "name": "Devlet Yılmaz", "url": "https://amedbulten.com/tr/author/admin"}`.
     - However, in [`mod/index/helper/mod_publication.helper.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/mod_publication.helper.php#L46) (`author()`), line 326 checks `if (!$this->guardFeatureOr404('publication.front'))`, which returns **`404 Not Found`** if `publication.front` is disabled, or if an author only has news articles! Worse, let's ensure `/tr/author/{username}` always resolves cleanly with HTTP 200 and includes `ProfilePage` / `Person` JSON-LD in [`author.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_publication/author.twig) for Google News E-E-A-T author entity verification.

### 2.3. Root Cause 2: `Cache-Control: no-store, no-cache, must-revalidate` & `Expires: 1981` Sent to Googlebot
- **Live HTTP Header Evidence:** Every anonymous `GET` request to `https://amedbulten.com/tr/...` returns:
  ```http
  Expires: Thu, 19 Nov 1981 08:52:00 GMT
  Cache-Control: no-store, no-cache, must-revalidate
  Pragma: no-cache
  ```
- **Why:** [`lib/Session.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Session.class.php#L42) calls `session_start()` on every request without disabling PHP's default `nocache` limiter (`session_cache_limiter('')`). Sending `no-store` and a 1981 expiration header on public news articles instructs upstream caches and search snippet buffers that the response must not be stored.

### 2.4. Root Cause 3: Dropped `max-snippet:-1, max-image-preview:large, max-video-preview:-1` Directives Across 19 Templates
- [`templates/amed/layouts/base.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/layouts/base.twig#L25) defines:
  `<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">`
- However, **19 child templates** (`news.twig`, `newses.twig`, `publication.twig`, `publications.twig`, `author.twig`, `authors.twig`, `event.twig`, `events.twig`, `calendar.twig`, `page.twig`, `announcement.twig`, and the 8 legal templates) override `{% block meta_tags %}` and either strip `max-snippet:-1, max-video-preview:-1` or omit `<meta name="robots">` entirely!
- **Google Discover & SERP Impact:** Without `max-image-preview:large` and `max-snippet:-1` on category/listing/author pages (`newses.twig`, `publications.twig`, `author.twig`, `events.twig`) and without `max-snippet:-1` on `news.twig` / `publication.twig`, Google suppresses rich text snippets and large Discover cards.

### 2.5. Root Cause 4: RFC 3986 Percent-Encoding Missing in XML Sitemaps & RSS (`mod/index/index.php`)
- **Critical Google Sitemap Parser Bug:** In [`mod/index/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/index.php#L938) (`newsSitemap()`, `publicationSitemap()`, `eventSitemap()`, `announcementSitemap()`, `videoSitemap()`, `gallerySitemap()`, `categorySitemap()`, `pageSitemap()`, and `rss()`), URLs containing Turkish/Kurdish UTF-8 characters (`ç, ğ, ı, ö, ş, ü, î, ê, û`) are echoed raw inside `<loc>`:
  `<loc>https://amedbulten.com/tr/news/galatasaray_amedspor_maçının_hakemi_belli_oldu</loc>`
- **Google Sitemap Specification Requirement:** Google's XML Sitemap & Google News Sitemap parser requires `<loc>` URLs to conform to RFC 3986 (non-ASCII characters in path segments must be percent-encoded via `rawurlencode()`, or normalized consistently) AND properly XML-escaped. Furthermore, in Google Search Console Sitemaps API, `tr/newsSitemap.xml` currently reports **`61 submitted / 0 indexed`** because of raw unencoded UTF-8 characters in `<loc>` and `<news:publication_date>` vs `lastmod` timezone discrepancies!
- Wait: let's verify how `<link rel="canonical">` and `newsSitemap.xml` interact! Browsers and Googlebot automatically resolve `<link rel="canonical" href="https://amedbulten.com/tr/news/galatasaray_amedspor_maçının_hakemi_belli_oldu">` to its RFC 3986 URI form (`https://amedbulten.com/tr/news/galatasaray_amedspor_ma%C3%A7%C4%B1n%C4%B1n_hakemi_belli_oldu`), while `Amed::parseUri()` (`Req::Url_Clear()`) calls `urldecode()` so both percent-encoded and raw UTF-8 requests route to the exact same article!

### 2.6. Root Cause 5: Canonical Split (`/` vs `/tr/`), Disabled Language (`/ku/*`) Orphans, and Duplicate Legal Pages in `pageSitemap.xml`
- **GSC Search Analytics Evidence:** Over 60% of the domain's impressions were consumed by duplicate statutory policy URLs (`/tr/page/Cerez_Politikasi` vs `/cookie-policy`) and legacy `/ku/*` URLs (`/ku/cookie-policy`, `/ku/security-policy`, `/ku/videos`, `/ku/newses`) from when Kurdish (`ku`) was briefly active.
- **Duplicate Legal Pages in Sitemap & Missing 301 Redirect from `/tr/page/{statutory_slug}`:**
  - [`mod/index/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/index.php#L1246-L1313) (`pageSitemap()`) outputs all `mod_page` records (including `/tr/page/Cerez_Politikasi`, `/tr/page/Gizlilik_Politikasi`, etc.) AND all root statutory policy URLs (`/cookie-policy`, `/privacy-policy`, etc.), creating 9 duplicate legal page pairs.
  - Furthermore, when Googlebot visits `/tr/page/Cerez_Politikasi`, [`mod_page.helper.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/mod_page.helper.php#L19) renders `page.twig` at `/tr/page/Cerez_Politikasi` instead of 301-redirecting to the canonical `/cookie-policy`!
- **Disabled Language (`/ku/*`) Soft-404 / Orphans:** In [`lib/Amed.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Amed.class.php#L382-L409), when Googlebot crawls an old `/ku/...` URL while `ku` is inactive (`status = 'n'`), `Amed` treats `ku` as the controller/method name instead of a disabled language prefix—resulting in a 404 instead of a clean `301 Moved Permanently` redirect to `/tr/...`.
- **Missing `<h1>` on Homepage:** When `site.logo_type == 'rectangle'` (the current live setting in `d048d282.sql`), [`stacked_top_menu.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_menu/stacked_top_menu.twig#L28) sets `show_site_title = false` and [`templates/amed/index/index.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/index.twig) renders no `<h1>` element anywhere on the homepage.

### 2.7. Root Cause 6: Unindexed Articles (`ID=8, 42, 59, 61`), Partial Sitemap Pings, and HTTP/HTTPS Canonical Enforcement
- **Partial Sitemap Ping in `GoogleIndexingService::pingSitemaps()`:** Currently [`lib/GoogleIndexingService.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/GoogleIndexingService.class.php#L125-L128) only submits `sitemap.xml` and `{lang}/newsSitemap.xml`. It never submits `{lang}/pageSitemap.xml`, `{lang}/categorySitemap.xml`, `{lang}/publicationSitemap.xml`, or `{lang}/gallerySitemap.xml`.
- **HTTP to HTTPS & `www` Canonical Redirect in `.htaccess`:** In GSC Search Analytics, `http://amedbulten.com/` (non-SSL) is still receiving impressions separately from `https://amedbulten.com/tr/`. Adding a single-hop HTTP/www to HTTPS canonical rewrite in `.htaccess` (for non-localhost hosts) eliminates protocol splitting.

---

## 3. Step-by-Step Implementation Plan

### Step 1: Fix Public HTTP Cache Headers in `lib/Session.class.php` & `.htaccess` Canonical Protocol Redirect
**Files:**
- [`lib/Session.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Session.class.php):
  - Call `session_cache_limiter('');` immediately before `session_start();` in `Session::init()`.
  - This stops PHP from emitting `Expires: Thu, 19 Nov 1981 08:52:00 GMT`, `Cache-Control: no-store, no-cache, must-revalidate`, and `Pragma: no-cache` on public pages crawled by Googlebot.
- [`.htaccess`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.htaccess):
  - Add a production-safe rewrite rule (excluding `localhost` and `127.0.0.1`) that 301-redirects `http://` or `www.` requests directly to `https://amedbulten.com/...` in a single hop.

### Step 2: Add Unescaped `json_ld` Twig Filter & Harden Structured Data (`NewsArticle`, `BreadcrumbList`, `ProfilePage`, `WebSite`)
**Files:**
- [`lib/TwigLibs.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/TwigLibs.class.php):
  - Register a custom `json_ld` Twig filter using `json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)` so URLs and Turkish/Kurdish UTF-8 text in JSON-LD are output cleanly without `\/` or `\u00e7` escaping.
- [`templates/amed/index/mod_news/news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/news.twig):
  - Restore `<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">`.
  - Upgrade `NewsArticle` JSON-LD to use `|json_ld|raw`, include `"@id": "{{ site.saddress }}news/{{ d.seo }}#article"`, `"url": "{{ site.saddress }}news/{{ d.seo }}"`, `"inLanguage": "{{ site.lang }}"`, `"isAccessibleForFree": true`, and structured `"image": [{"@type": "ImageObject", "url": "...", "width": 1280, "height": 720}]`.
  - Clamp `dateModified` so that if `d.updated_at` is empty or chronologically earlier than `d.created_at`, `dateModified` equals `datePublished`.
  - Add `"item": {{ (site.saddress ~ 'news/' ~ d.seo)|json_ld|raw }}` to the final `ListItem` in `BreadcrumbList`.
  - Remove `itemscope itemtype="https://schema.org/NewsArticle"` and microdata attributes (`itemprop="headline"`, `itemprop="datePublished"`, `itemprop="image"`) from the sidebar `<article class="news-24h-item">` loop so the page has **one single, unambiguous `NewsArticle` entity**.
- **All Other Content & Listing Templates (`newses.twig`, `publication.twig`, `publications.twig`, `author.twig`, `authors.twig`, `event.twig`, `events.twig`, `calendar.twig`, `page.twig`, `announcement.twig`, `gallery.twig`, and `legal/*.twig`):**
  - Ensure `<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">` is present in every public indexable template that overrides `{% block meta_tags %}`.
  - Ensure every `BreadcrumbList` JSON-LD includes `"item"` on the final `ListItem` and uses `|json_ld|raw`.
  - Add `ProfilePage` / `Person` JSON-LD to [`templates/amed/index/mod_publication/author.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_publication/author.twig) so Google News can validate author entities linked from `NewsArticle.author.url`.
  - Remove incomplete `itemscope itemtype="https://schema.org/NewsArticle"` microdata from summary card partials (`newses.twig`, `latest_news.twig`, `featured_news.twig`, `economy_news.twig`, `sports_news.twig`, `world_news.twig`, `category_news_row.twig`).
- [`templates/amed/index/index.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/index.twig):
  - Add `<h1 class="visually-hidden">{{ site.title }}{% if site.site_name is defined and site.site_name is not empty and site.site_name != site.title %} — {{ site.site_name }}{% endif %}</h1>` at the top of `{% block content %}` so the homepage always has a semantic `<h1>` heading even when `logo_type == 'rectangle'`.

### Step 3: De-duplicate `pageSitemap.xml`, 301-Redirect Statutory `mod_page` Duplicates & Inactive Languages (`/ku/*`)
**Files:**
- [`mod/index/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/index.php):
  - In `pageSitemap()`, exclude `mod_page` records whose `seo` matches statutory legal/policy pages (`Yasal_Bilgiler`, `Cerez_Politikasi`, `Gizlilik_Politikasi`, `Kullanim_Kosullari`, `Yayin_Ilkeleri`, `Erisilebilirlik`, `Guvenlik_Politikasi`, `Basin_Etigi_ve_Uyum`, `Kunye`, etc.) so they do not duplicate `/legal`, `/cookie-policy`, `/privacy-policy`, `/terms-of-service`, `/editorial-policy`, `/accessibility`, `/security-policy`, `/compliance`.
  - Ensure `newsSitemap()` clamps `$updatedTime = max($createdTime, $updatedTime)` so `<lastmod>` is never earlier than `<news:publication_date>`.
- [`mod/index/helper/mod_page.helper.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/mod_page.helper.php):
  - When a request arrives for `/<lang>/page/<seo>` where `<seo>` is one of the statutory legal slugs (`Cerez_Politikasi`, `Gizlilik_Politikasi`, `Yasal_Bilgiler`, `Kullanim_Kosullari`, `Yayin_Ilkeleri`, `Erisilebilirlik`, `Guvenlik_Politikasi`, `Basin_Etigi_ve_Uyum`), issue a `301 Moved Permanently` redirect to the canonical root statutory route (`/cookie-policy`, `/privacy-policy`, `/legal`, etc.), eliminating the duplicate pages currently indexed in Google.
- [`lib/Amed.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Amed.class.php):
  - Track all known language codes from the `lang` table (e.g. `inactive_codes`) alongside `active_codes`.
  - When the first URI segment matches an **inactive** language code (`ku`, `en`, `de`, etc.), strip that inactive language segment and issue a `301 Moved Permanently` redirect to the active default language URL (`/tr/...` or root statutory slug), cleanly consolidating all legacy `/ku/*` URLs in Google's index.

### Step 4: Enforce Timestamp Consistency (`created_at` / `updated_at`) in `mod_news`, `McpNewsTools`, and Database
**Files:**
- [`mod/mod_news/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_news/index.php):
  - In `beforeUpdate($model, $id)`, when `updated_at` is not explicitly provided in POST, set `$model->updated_at = date('Y-m-d H:i:s');` (in `Europe/Istanbul` time) instead of `null`.
  - Ensure `$model->updated_at` is never earlier than `$model->created_at`.
- [`lib/GoogleIndexingService.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/GoogleIndexingService.class.php):
  - Expand `pingSitemaps()` to submit `sitemap.xml`, `{lang}/newsSitemap.xml`, `{lang}/pageSitemap.xml`, `{lang}/categorySitemap.xml`, and `{lang}/gallerySitemap.xml`.

### Step 5: Live Google Search Console Sitemap Registration & Batch Indexing API Dispatch
- Submit all active sitemaps (`sitemap.xml`, `tr/newsSitemap.xml`, `tr/pageSitemap.xml`, `tr/categorySitemap.xml`, `tr/gallerySitemap.xml`, `tr/publicationSitemap.xml`) to Google Search Console API.
- Dispatch `GoogleIndexingService` notifications for `https://amedbulten.com/tr/` and all published articles (especially `ID=8, 42, 59, 60, 61`).

---

## 4. Verification Criteria
1. `php -l` syntax check passes on all modified PHP files.
2. `python3 tools/code_separation_guard.py` and `python3 tools/i18n_guard.py` pass with zero violations.
3. Rendered HTML of `/tr/news/galatasaray_amedspor_maçının_hakemi_belli_oldu` contains:
   - Exactly **1** `NewsArticle` schema entity (clean unescaped JSON-LD with `@id` and `url` matching `<link rel="canonical">` and zero sidebar `itemtype="https://schema.org/NewsArticle"` microdata).
   - Valid `BreadcrumbList` where all items have `"item"` URLs.
   - `<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">`.
   - Zero `Cache-Control: no-store` / `Expires: 1981` response headers.
4. Requests to `/tr/page/Cerez_Politikasi` and `/ku/cookie-policy` return `301 Moved Permanently` to `/cookie-policy`, and `/ku/videos` returns `301 Moved Permanently` to `/tr/videos`.
5. Version bumped to `v1.0.45` in `config/site.config.php`, `config/site.config.example.php`, and `docs/VERSION.md`.

---

## 5. Argon Audit Findings & Mandatory Remediation Checklist for Flash (`v1.0.45`)

During the post-implementation audit of Flash's `v1.0.45` changes across the 38 modified files, **9 critical/high issues and omissions** were identified that **Flash MUST resolve** before `v1.0.45` can pass verification:

### Defect 1 (CRITICAL — Broken `%1` Regex Backreference & Reverse-Proxy Redirect Loop in `.htaccess` and `config/site.config.php`)
- **Files:** [`.htaccess`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.htaccess#L3-L10), [`config/site.config.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/site.config.php#L33-L42), [`config/site.config.example.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/site.config.example.php#L33-L42)
- **What Flash Did Wrong:**
  1. In `.htaccess` lines 3–10, Flash combined `RewriteCond %{HTTPS} off [OR]` with `RewriteCond %{HTTP_HOST} ^www\.(.+) [NC]` and used `RewriteRule ^ https://%1%{REQUEST_URI} [L,R=301,NE]`. In Apache `mod_rewrite`, `%1` references the capture group of the **last matched `RewriteCond`**. When a request arrives at `http://amedbulten.com/tr/...` (where `HTTPS` is `off` and `HTTP_HOST` has no `www.`), `%1` is empty or matches condition 1, redirecting to a broken URL `https:///tr/...`! Moreover, lines 8–10 duplicate lines 4–7 and are dead code.
  2. Furthermore, `.htaccess` checks `RewriteCond %{HTTPS} off` without checking reverse-proxy headers (`X-Forwarded-Proto` and `CF-Visitor`). On production behind Cloudflare or a reverse proxy, Apache's `%{HTTPS}` is `off` even on HTTPS requests, causing an **infinite 301 redirect loop (`ERR_TOO_MANY_REDIRECTS`)**!
- **Exact Fix Required from Flash:**
  - In [`.htaccess`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.htaccess#L3-L10), separate the `www.` strip rule and make the HTTPS check proxy-aware:
    ```apache
    RewriteCond %{HTTP_HOST} !^(localhost|127\.0\.0\.1)(:[0-9]+)?$ [NC]
    RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]
    RewriteRule ^ https://%1%{REQUEST_URI} [L,R=301,NE]

    RewriteCond %{HTTP_HOST} !^(localhost|127\.0\.0\.1)(:[0-9]+)?$ [NC]
    RewriteCond %{HTTPS} off
    RewriteCond %{HTTP:X-Forwarded-Proto} !https [NC]
    RewriteCond %{HTTP:CF-Visitor} !"scheme":"https" [NC]
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301,NE]
    ```
  - In [`config/site.config.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/site.config.php#L33-L38) and [`config/site.config.example.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/site.config.example.php#L33-L38), also enforce single-hop `www.` stripping alongside `$isHttps`:
    ```php
    $hasWww = (stripos($host, 'www.') === 0);
    $cleanHost = $hasWww ? substr($host, 4) : $host;
    if (!$isLocal && (!$isHttps || $hasWww) && PHP_SAPI !== 'cli') {
        header('Location: https://' . $cleanHost . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit();
    }
    ```

### Defect 2 (CRITICAL — Wikipedia-Style UTF-8 URL Parity Violation via `$transMap` in `lib/Amed.class.php`, `mod_page.helper.php` & `mod/index/index.php`)
- **Files:** [`lib/Amed.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Amed.class.php#L422-L436), [`mod/index/helper/mod_page.helper.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/mod_page.helper.php#L74-L85), [`mod/index/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/index.php#L1307-L1320)
- **What Went Wrong:**
  1. Using an ASCII `$transMap` (`'Ç'=>'c'`, `'Ş'=>'s'`, `'Î'=>'i'`, etc.) violates **Primary Rule 9 (`AGENTS.md` — URL Generation Parity / Wikipedia Style)**. Neither Wikipedia nor Amed Framework ever transliterates UTF-8 characters into ASCII.
- **Exact Fix Required (Detailed in [`docs/plan/wikipedia-utf8-url-parity-remediation-plan.md`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/docs/plan/wikipedia-utf8-url-parity-remediation-plan.md)):**
  - Completely remove `$transMap` from `lib/Amed.class.php`, `mod/index/helper/mod_page.helper.php`, and `mod/index/index.php`.
  - Centralize pure UTF-8 multibyte case-folding (`mb_strtolower(..., 'UTF-8')`) and statutory alias resolution via `Helper::utf8_slug_key()` and `Helper::resolve_statutory_slug()` in [`lib/Helper.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Helper.class.php), matching native UTF-8 slugs (`çerez_politikası`, `kullanım_koşulları`, `yayın_ilkeleri`, `polîtîkaya_çerezan`, `agahiyên_qanûnî`, etc.) directly in UTF-8 without stripping diacritics.
  - Update `mod/index/lang/*.php` (`tr.php`, `ku.php`, `zza.php`, `fr.php`, `sv.php`, `de.php`), `templates/amed/index/search.twig` (`|legacy_seo`), and statutory `mod_page` records (`ID=37, 39, 40, 41, 42, 43, 44`) to native Wikipedia-style UTF-8 slugs.

### Defect 3 (HIGH — Inactive Language Redirect in `lib/Amed.class.php` Causes 2-Hop Redirect Chains & Lacks Method/Admin Guards)
- **File:** [`lib/Amed.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Amed.class.php#L393-L416)
- **What Flash Did Wrong:**
  1. The redirect block runs on all HTTP methods (could break `POST` or AJAX requests) and does not check `($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'`.
  2. When a request arrives for `/ku/page/Cerez_Politikasi` or `/ku/page/Polîtîkaya_Çerezan`, `$remainder` is `page/Cerez_Politikasi`. Flash redirects to `/tr/page/Cerez_Politikasi` (Hop 1), which then hits `mod_page.helper.php` and redirects to `/cookie-policy` (Hop 2). Google Search Console penalizes multi-hop redirect chains!
- **Exact Fix Required from Flash:**
  - Restrict the inactive-language redirect to `GET`/`HEAD` requests.
  - Check if `$remainder` starts with `page/` followed by a statutory slug; if so, redirect **directly in 1 hop** to the root statutory route (`https://amedbulten.com/cookie-policy`, etc.) instead of `/tr/page/...`.

### Defect 4 (HIGH — Incomplete `json_ld` Migration in Homepage, Legal Templates, and FAQ Macro)
- **Files:** [`templates/amed/index/index.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/index.twig#L16-L32), [`templates/amed/macros/faq.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/macros/faq.twig#L50), and all 9 templates under `templates/amed/index/legal/*.twig` (`accessibility.twig`, `compliance.twig`, `contact.twig`, `cookie_policy.twig`, `editorial_policy.twig`, `index.twig`, `privacy_policy.twig`, `security_policy.twig`, `terms_of_service.twig`).
- **What Flash Did Wrong:**
  - Flash left `|json_encode|raw` in all 9 `templates/amed/index/legal/*.twig` files and in `templates/amed/index/index.twig` (lines 16, 18, 32), which outputs escaped slashes (`https:\/\/amedbulten.com\/...`) and escaped Unicode (`\u00e7`) in JSON-LD on the homepage and all statutory trust pages!
  - In `templates/amed/macros/faq.twig` line 50, replace the verbose `constant(...)` expression with `{{ faq_schema|json_ld|raw }}`.

### Defect 5 (HIGH — Leftover Orphaned `itemprop` Microdata Attributes in `economy_news.twig` and `publications.twig`)
- **Files:** [`templates/amed/index/mod_news/economy_news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/economy_news.twig#L58-L138), [`templates/amed/index/mod_publication/publications.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_publication/publications.twig#L209-L246)
- **What Flash Did Wrong:**
  - Flash removed `itemscope itemtype="..."` from the outer `<article>` tags in `economy_news.twig` and `publications.twig`, but forgot to remove `itemprop="name"`, `itemprop="datePublished"`, `itemprop="genre"`, `itemprop="headline"`, `itemprop="url"`, and `itemprop="description"` from the inner child elements! Orphaned `itemprop` attributes without an enclosing `itemscope` are invalid HTML5 microdata.
- **Exact Fix Required from Flash:**
  - Strip all remaining `itemprop="..."` attributes from `templates/amed/index/mod_news/economy_news.twig` and `templates/amed/index/mod_publication/publications.twig`.

### Defect 6 (MEDIUM — Missing Canonical, Robots Meta, and JSON-LD Schema in Video, Announcement, Archive, and Search Templates)
- **Files:**
  - [`templates/amed/index/mod_video_embed/videos.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_video_embed/videos.twig)
  - [`templates/amed/index/mod_video_embed/video.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_video_embed/video.twig) (also has an inline `style="top: 2rem;"` on line 13 violating Rule 1!)
  - [`templates/amed/index/mod_announcements/announcements.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_announcements/announcements.twig)
  - [`templates/amed/index/mod_news/archive.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/archive.twig)
  - [`templates/amed/index/mod_publication/archive.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_publication/archive.twig)
  - [`templates/amed/index/search.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/search.twig)
  - [`templates/amed/index/mod_imagecategories/gallery.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_imagecategories/gallery.twig)
- **What Flash Did Wrong:**
  - In `gallery.twig`, Flash added `canonical`, `title`, `description`, and `schema`, but did not override `meta_tags`—so `og:url` and `twitter:url` fall back to `base.twig`'s `https://amedbulten.com/tr/` instead of `https://amedbulten.com/tr/gallery/...`!
  - In `videos.twig`, `video.twig`, `announcements.twig`, `mod_news/archive.twig`, and `mod_publication/archive.twig`, neither `{% block canonical %}` nor `{% block title %}` nor `{% block meta_tags %}` is defined—meaning `/tr/videos`, `/tr/video/...`, `/tr/announcements`, `/tr/news-archive`, and `/tr/publication-archive` all emit `<link rel="canonical" href="https://amedbulten.com/tr/"/>` (telling Google they are duplicates of the homepage!).
  - Furthermore, `video.twig` line 13 has `style="top: 2rem;"` which violates Primary Rule 1 (Strict Code Separation), and `search.twig` should emit `<meta name="robots" content="noindex, follow">` so internal search result pages do not dilute crawl budget.

### Defect 7 (MEDIUM — `announcement.twig` Uses Date-Only `Y-m-d` Instead of ISO-8601 `c` in `Article` JSON-LD & Lacks Timestamp Clamping)
- **File:** [`templates/amed/index/mod_announcements/announcement.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_announcements/announcement.twig#L109-L110)
- **What Flash Did Wrong:**
  - Lines 109–110 output `"datePublished": "{{ d.created_at ? d.created_at|date('Y-m-d') : "now"|date('Y-m-d') }}"` without timezone offset (`date('c')`) and without clamping `dateModified >= datePublished` (unlike `news.twig` and `publication.twig`).

### Defect 8 (MEDIUM — Version Parity Omission in `config/site.config.example.php`)
- **File:** [`config/site.config.example.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/site.config.example.php#L62)
- **What Flash Did Wrong:**
  - Flash updated `$site['version'] = '1.0.45';` in `config/site.config.php` and `docs/VERSION.md`, but left `$site['version'] = '1.0.44';` in `config/site.config.example.php` line 62.

### Defect 9 (HIGH — Live MCP Article Timestamp Fix & Complete 61-Article Indexing Dispatch Not Finished)
- **What Flash Did Wrong:**
  - In Step 4 & Step 5, Flash only dispatched 7 URLs in its test script instead of all 61 published articles, and did **not** fix the inverted `updated_at < created_at` timestamps on the live server via MCP (`amed-bulletin:update_news` for `ID=12, 18, 21`) or in the local SQLite database.
- **Exact Fix Required from Flash:**
  - Update the anomalous article timestamps (`ID=12, 18, 21` where `updated_at < created_at`) in local SQLite and via `amed-bulletin` MCP `update_news`.
  - Run a batch script via `GoogleIndexingService::notifyUrl()` for all 61 active news URLs (or specifically ensure `ID=8, 12, 18, 21, 42, 43, 59, 60, 61` and all category/author/statutory URLs are pushed).
  - Clean up any untracked temporary test directories (`static/banner/` created during testing) before finishing.

