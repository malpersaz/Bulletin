# Google Search, Google News & Real-Time Indexing Hardening Plan

## 1. Objective

Eliminate all crawl, discovery, and indexing bottlenecks across `AF/Bulletin` so that:
1. Newly published or updated content (`mod_news`, `mod_publication`, `mod_page`, `mod_event`, `mod_announcements`, `mod_video`) is immediately pushed to **Google Web Search Indexing API (`urlNotifications:publish`)** AND triggers an automatic **Google Search Console Sitemap Resubmission (`PUT /webmasters/v3/sites/.../sitemaps/...`)** plus homepage crawl notification.
2. `robots.txt` (`robots.php`) and `sitemap.xml` (`mod/index/index.php`) provide 100% accurate, standards-compliant, and `<lastmod>`-enriched discovery feeds without requiring full framework routing bootstrap.
3. RSS discovery (`<link rel="alternate" type="application/rss+xml">`) and JSON-LD structured data (`WebSite`, `NewsMediaOrganization`, `NewsArticle`) provide unambiguous entity, `alternateName` (`Amed Bülten`, `Amed Bulten`, `amedbulten.com`), and `mainEntityOfPage` signals for Google Search, Google News, and Google Discover.

---

## 2. Root Cause & Gap Analysis

### 2.1. Completed Operational Fixes (Already Applied in Cloud / GSC)
- **Google Cloud `indexing.googleapis.com`:** Previously disabled (`HTTP 403 SERVICE_DISABLED`); now **Enabled**.
- **Google Search Console Service Account Ownership:** `amedbulten-ai@amedbulten.iam.gserviceaccount.com` was previously set to `Full` (`Tam`), which caused `HTTP 403 Permission denied. Failed to verify the URL ownership` on `urlNotifications:publish`. It is now promoted to **`Owner` (`Sahibi`)**, and all 98 existing URLs have been pushed (`98/98 HTTP 200 OK`).

### 2.2. Remaining Codebase Gaps Requiring Permanent Fixes

1. **`robots.php` Active Language Resolution Bug (`Registry::get('active_langs')` is `NULL`):**
   - [`robots.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/robots.php#L27-L34) only includes `init.php` without instantiating `Amed`. Because `active_langs` is populated inside [`Amed::buildLanguageRegistryState()`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Amed.class.php#L66-L130), `Registry::get('active_langs')` returns `null` inside `robots.php`.
   - As a result, `https://amedbulten.com/robots.txt` outputs `Sitemap: https://amedbulten.com/newsSitemap.xml` instead of `Sitemap: https://amedbulten.com/tr/newsSitemap.xml`. Furthermore, [`.htaccess`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.htaccess#L25) only has a rewrite rule for `^([a-z]{2,3})/(news|...)Sitemap\.xml$`, not root `/newsSitemap.xml`.

2. **Missing Automatic Sitemap Resubmission & Homepage Ping in [`GoogleIndexingService.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/GoogleIndexingService.class.php):**
   - `GoogleIndexingService` only calls `https://indexing.googleapis.com/v3/urlNotifications:publish` for the single item URL.
   - It never notifies Google that the homepage (`https://amedbulten.com/{lang}/`) has changed when a new article is published, nor does it ping the Google Search Console Sitemaps API (`PUT https://www.googleapis.com/webmasters/v3/sites/{property}/sitemaps/{sitemapUrl}`) to force Googlebot to re-fetch `sitemap.xml` and `{lang}/newsSitemap.xml`.

3. **Incomplete Indexing Triggers Across MCP Tools & Admin Controllers:**
   - In MCP tools, only [`McpNewsTools.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/McpNewsTools.class.php#L832) calls `$this->notifyGoogleIndexing(...)`.
   - [`McpPublicationTools.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/McpPublicationTools.class.php), [`McpPageTools.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/McpPageTools.class.php), [`McpEventTools.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/McpEventTools.class.php), and [`McpAnnouncementTools.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/McpAnnouncementTools.class.php) do not call `notifyGoogleIndexing()` on create/update/delete.
   - In Admin controllers, [`mod/mod_announcements/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_announcements/index.php) and [`mod/mod_video/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_video/index.php) do not define `$menu_url_pattern`, so [`BaseModuleController`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/BaseModuleController.class.php#L1920) skips `GoogleIndexingService` notifications when announcements or videos are published.

4. **Missing `<lastmod>` in Master Sitemap Index (`sitemapIndex()`) and `pageSitemap()`:**
   - [`mod/index/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/index.php#L748-L844) (`sitemapIndex()`) outputs `<sitemap><loc>...</loc></sitemap>` without `<lastmod>`. Googlebot relies on `<lastmod>` in `<sitemapindex>` to know which child sitemap (`tr/newsSitemap.xml`, `tr/publicationSitemap.xml`, etc.) has new content since its last crawl.
   - `pageSitemap()` (line 1208) outputs the homepage `<loc>https://amedbulten.com/tr/</loc>` without `<lastmod>`, and policy pages use bare root URLs (`/legal`) instead of the active language prefix (`/tr/legal`), causing a 301 redirect hop during sitemap crawling.

5. **Missing `<link rel="alternate" type="application/rss+xml">` & RSS Full-Size Image Enclosure:**
   - [`templates/amed/layouts/base.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/layouts/base.twig) does not include `<link rel="alternate" type="application/rss+xml" ... href="{{ site.saddress }}rss.xml">` in `<head>`. Googlebot and Google News Reader use this tag for high-frequency RSS feed discovery (which we saw in GSC `referringUrls` as a primary discovery source).
   - [`mod/index/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/index.php#L723) (`rss()`) uses `mod_news/thumb/` (480x270) instead of full-resolution `mod_news/` (1280x720) in `<media:content>`, and hardcodes `type="image/jpeg"` even for `.webp` images.

6. **Brand Query Disambiguation (`alternateName`) & `NewsArticle` Schema Completeness:**
   - [`templates/amed/index/index.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/index.twig#L12-L38) defines `WebSite` and `NewsMediaOrganization` JSON-LD with only `"name": "Amed Bülten"`. Adding `"alternateName": ["Amed Bulten", "amedbulten.com", "Amed Bülteni"]` explicitly links unaccented brand searches (`amed bulten`) to the canonical entity.
   - [`templates/amed/index/mod_news/news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/news.twig#L101-L131) `NewsArticle` JSON-LD is missing `"mainEntityOfPage"` and `"articleSection"`, which are recommended by Google News / Discover structured data guidelines.

---

## 3. Implementation Steps

### Step 1: Fix `robots.php` Active Language & Sitemap Discovery
**File:** [`robots.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/robots.php)
- Query active languages directly from the `lang` model (`status = 'y'`) if `Registry::get('active_langs')` is empty, falling back to `$site['default_lang'] ?? 'tr'`.
- Output:
  - `Sitemap: https://amedbulten.com/sitemap.xml`
  - `Sitemap: https://amedbulten.com/{lang}/newsSitemap.xml` for each active language.

### Step 2: Upgrade `GoogleIndexingService` with Sitemap Resubmission & Homepage Refresh
**File:** [`lib/GoogleIndexingService.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/GoogleIndexingService.class.php)
- Add support for requesting an OAuth2 token with combined scopes:
  - `https://www.googleapis.com/auth/indexing`
  - `https://www.googleapis.com/auth/webmasters`
- Add `pingSitemaps(?string $lang = null): bool`:
  - Reads `property_url` from `data/seo_settings.json` (or derives `sc-domain:{host}` from `site_config`).
  - Sends HTTP `PUT` to `https://www.googleapis.com/webmasters/v3/sites/{property}/sitemaps/{sitemapUrl}` for both `sitemap.xml` and `{lang}/newsSitemap.xml`.
- Enhance `notifyUpdate(string $url)` and `notifyDelete(string $url)`:
  - Dispatches `URL_UPDATED` / `URL_DELETED` for the target `$url`.
  - When a content URL under `/{lang}/news/...` or `/{lang}/publication/...` is updated/deleted, also dispatches `URL_UPDATED` for the canonical language homepage (`https://amedbulten.com/{lang}/`) and calls `pingSitemaps($lang)`.

### Step 3: Wire Indexing Notifications Across All Content Modules (MCP & Admin)
**Files:**
- [`lib/McpPublicationTools.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/McpPublicationTools.class.php): Call `$this->notifyGoogleIndexing($model->lang . '/publication/' . $model->seo, ...)` on update/publish and delete.
- [`lib/McpPageTools.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/McpPageTools.class.php): Call `$this->notifyGoogleIndexing($model->lang . '/page/' . $model->seo, ...)` on create, update, and delete.
- [`lib/McpEventTools.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/McpEventTools.class.php): Call `$this->notifyGoogleIndexing($model->lang . '/event/' . $model->seo, ...)` on create, update, and delete.
- [`lib/McpAnnouncementTools.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/McpAnnouncementTools.class.php): Call `$this->notifyGoogleIndexing($model->lang . '/announcement/' . $model->id . '-' . Helper::legacy_seo($model->name), ...)` on create, update, and delete.
- [`lib/McpVideoTools.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/McpVideoTools.class.php): Call `$this->notifyGoogleIndexing(...)` on create, update, and delete for both local videos (`mod_video`) and embedded videos (`mod_video_embed`).
- [`lib/BaseModuleController.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/BaseModuleController.class.php), [`mod/mod_news/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_news/index.php), and [`mod/mod_announcements/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_announcements/index.php):
  - Support `{name|legacy_seo}` in `BaseModuleController::replaceDynamicPlaceholders()` and configure `$menu_url_pattern = '{lang}/announcement/{id}-{name|legacy_seo}'` in `mod_announcements`.
  - Dispatch `GoogleIndexingService` notifications on quick status toggles (`updateSingleColumn` and `mod_news::toggleFlag`) and notify `URL_DELETED` for the previous URL when a slug or language changes during edit.
- [`mod/mod_seo/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_seo/index.php): Expand `getSiteUrls()` to include language homepages, news/publication categories, events, and announcements so the Admin SEO Dashboard matches Google Search Console URLs.

### Step 4: Enrich `sitemapIndex()`, `pageSitemap()`, `announcementSitemap()`, `videoSitemap()`, and `rss()` in `mod/index/index.php`
**File:** [`mod/index/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/index.php)
1. **`sitemapIndex()`:**
   - Compute `<lastmod>` for `newsSitemap.xml` from the latest published news item (`MAX( COALESCE(updated_at, created_at) )`).
   - Compute `<lastmod>` for `publicationSitemap.xml`, `eventSitemap.xml`, `announcementSitemap.xml`, and `pageSitemap.xml` so every entry in `<sitemapindex>` carries an accurate ISO-8601 `<lastmod>` timestamp.
2. **`pageSitemap()`, `announcementSitemap()`, and `videoSitemap()`:**
   - Add `<lastmod>` to the homepage `<url>` entry based on the latest published news timestamp.
   - Keep statutory legal/policy URLs at root (`https://amedbulten.com/legal`, `https://amedbulten.com/privacy-policy`, etc.) matching `legal.helper.php` (which issues a 301 redirect from `/<lang>/<policy>` to `/<policy>`), ensuring zero 301-redirecting URLs in `pageSitemap.xml`.
   - Use `Helper::legacy_seo($o['name'])` instead of `Helper::sefurl($o['name'])` in `announcementSitemap()` and `videoSitemap()` for 100% canonical URL parity with `announcement.twig`, `videos.twig`, and `LlmsService`.
3. **`rss()`:**
   - Fix `<link>` to point to `$this->site['saddress']` (with trailing slash) instead of `$this->site['address'] . $this->site['lang']` (which caused a 301 redirect).
   - Use full-resolution `mod_news/{image}` (1280x720) for `<media:content>` and detect the proper MIME type (`image/webp`, `image/jpeg`, `image/png`) for `<enclosure>`.

### Step 5: Add RSS Auto-Discovery & Structured Data Signals (`base.twig`, `index.twig`, `news.twig`)
**Files:**
- [`templates/amed/layouts/base.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/layouts/base.twig):
  - Add `<link rel="alternate" type="application/rss+xml" title="{{ site.title }} RSS" href="{{ site.saddress }}rss.xml">` inside `<head>`.
- [`templates/amed/index/index.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/index.twig):
  - Add `"alternateName": ["Amed Bulten", "amedbulten.com", "Amed Bülteni"]` to both `WebSite` and `NewsMediaOrganization` JSON-LD blocks so Google Search associates unaccented `"amed bulten"` queries with `amedbulten.com`.
- [`templates/amed/index/mod_news/news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/news.twig):
  - Add `"mainEntityOfPage": { "@type": "WebPage", "@id": "{{ site.saddress }}news/{{ d.seo }}" }` and `"articleSection"` (when `has_news_category` is true) to the `NewsArticle` JSON-LD block.

### Step 6: Clean Human-Readable UTF-8 URLs in Share & Copy Actions (`share.twig`, `share_toolbar.twig`, `announcement.twig`, `announcements.twig`, `share.js`, `legal-info.js`, `event-agenda.js`)
**Files:**
- [`templates/amed/partials/share.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/partials/share.twig) and [`templates/amed/partials/share_toolbar.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/partials/share_toolbar.twig):
  - Ensure share links (`WhatsApp`, `X/Twitter`, `LinkedIn`, `Email`) and `data-share-url` / `data-copy-url` attributes preserve clean human-readable UTF-8 characters (`ş, ı, ç, ö, ü, ğ, î, û, ê`) instead of double-encoding path characters into `%C5%9F%C4%B1`.
- [`templates/amed/index/mod_announcements/announcement.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_announcements/announcement.twig) and [`templates/amed/index/mod_announcements/announcements.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_announcements/announcements.twig):
  - Pass `share_url: announcement_url` to both `share.twig` and `share_toolbar.twig` and align announcement list links to `|legacy_seo`.
- [`templates/amed/static/js/share.js`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/static/js/share.js), [`templates/amed/static/js/legal-info.js`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/static/js/legal-info.js), and [`templates/amed/static/js/event-agenda.js`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/static/js/event-agenda.js):
  - Decode any percent-encoded fallback URLs via `decodeURI(...)` before copying to clipboard or invoking `navigator.share`, guaranteeing clean readable URLs everywhere.

---

## 4. Verification & Guard Validation Protocol

1. **Syntax & Guard Checks:**
   - Run `php -l` on all modified PHP files (`robots.php`, `lib/GoogleIndexingService.class.php`, `lib/Mcp*Tools.class.php`, `mod/index/index.php`).
   - Run `python3 tools/code_separation_guard.py` and `python3 tools/i18n_guard.py` to verify zero inline CSS/JS and 100% i18n compliance.
2. **Live Endpoint & Google API Verification:**
   - Verify `https://amedbulten.com/robots.txt` outputs `Sitemap: https://amedbulten.com/tr/newsSitemap.xml`.
   - Verify `https://amedbulten.com/sitemap.xml` includes `<lastmod>` tags on child sitemaps.
   - Verify `https://amedbulten.com/tr/pageSitemap.xml` contains zero 301-redirecting URLs.
   - Trigger `GoogleIndexingService` test notification and verify `HTTP 200` on `Indexing API` + `HTTP 204` on `Search Console Sitemaps API`.
