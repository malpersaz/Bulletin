# Architectural Specification & Plan: `mod_news` Zero-Cost Automated Social Sharing via Enhanced RSS Bridge (v1.0.67)

**Target Environment:** Local Docker stack (`bulletin_app` PHP 8.5.12, `bulletin_web` nginx 1.31.3, `bulletin_db` MySQL 8.0, database `bulletin`) & Host Linux (`PHP 8.5.4`)  
**Repository:** `AF/Bulletin` (`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`)  
**Date:** 2026-11-12  
**Target Release:** `v1.0.67`  
**Governing Standard:** Amed Framework Core & Task Quality Guards (Phone-Only Admin Contract, Strict Code Separation, Real i18n Keys across all 11 locales, Wikipedia-style UTF-8 URL Parity, English code/docs)  
**Status:** Planned / Ready for Implementation  

---

## 0. Skills & Governance Statement (per `.gemini/GEMINI.md` & `AGENTS.md`)

| Skill / Quality Guard | Relevance | Governance Requirement |
| :--- | :--- | :--- |
| `module-modernization-check` | **Governing Standard** | All module enhancements must adhere to `BaseModuleController` and modern AF patterns. Clean separation of concerns, strict type-checking, and zero regressions to core services. |
| `admin-mobile-check` | **Governing Standard** | All admin screens (`templates/admin/mod_settings/social_media.twig`, `templates/admin/mod_news/edit.twig`) must strictly adhere to the phone-only mobile contract (max 430px viewport ergonomics, full-width touch targets >= 44px, bottom-sheet patterns, no horizontal scrollbars). |
| `i18n-guard` | **Governing Standard** | All user-facing strings must have descriptive English keys in `en.php` and 100% parity across all 11 active locales (`ar, ckb, de, en, fa, fr, ku, ru, sv, tr, zza`). No raw unlocalized text or Twig default filters (`|default('...')`). |
| `Strict Code Separation` | **Governing Standard** | Zero inline CSS (`style="..."` or `<style>` tags) and zero inline JavaScript (`<script>` tags or inline `onclick` handlers) in Twig or PHP templates. All presentation resides in external CSS and all behavior in external JS files. |
| `Wikipedia-Style URLs` | **Governing Standard** | Feed URLs and article URLs must preserve original UTF-8 characters and casing, generated via `Helper::sitemap_url()` and `Helper::legacy_seo()`. |
| `Zero Autonomous Cross-Repo Access` | **Governing Standard** | Isolated workspace operations. No modifications or inspections beyond the active Bulletin repository. |

---

## 1. Problem Definition & Strategic Pivot

### 1.1 The Reality of X API v2 (2026 Pay-Per-Use Gate)
- Official direct API posting to X (`POST https://api.x.com/2/tweets`) strictly enforces developer prepaid billing (`HTTP 402: credits depleted`).
- Outbound link tweets cost ~$0.20 per post. For a news publishing volume of 50-60 articles daily, direct API costs $300-$360/month.
- User accounts with X Premium / Blue Tick have account analytics and badges, but do **NOT** receive free programmatic API write credits.

### 1.2 The Zero-Cost Solution
- Pivot social distribution for X, Facebook Pages, Instagram, Telegram, and Threads to **Enhanced RSS 2.0 Feeds + WebSub Hub Push + Bridge Automation**.
- Standardized RSS auto-broadcasting allows external bridges (Buffer free tier, Make.com, IFTTT, n8n, self-hosted webhooks) to ingest and cross-post new articles immediately without API licensing costs.
- The framework retains native free Meta SUAT (System User Access Token) support for Facebook Pages and Instagram Stories while formalizing the RSS bridge as the primary zero-cost delivery channel.

---

## 2. Comprehensive System Architecture

```
                          ┌──────────────────────────────────────────────┐
                          │   News Publication Trigger (status = 'y')    │
                          │   - Admin Wizard (mod_news::afterSave)       │
                          │   - Quick Toggle (mod_news::toggle_status)   │
                          │   - Batch Approval (mod_news::approve)       │
                          │   - MCP Tool (McpNewsTools::createNews/upd)  │
                          └──────────────────────┬───────────────────────┘
                                                 │
                                                 ▼
                          ┌──────────────────────────────────────────────┐
                          │         PubSubHubbub / WebSub Push           │
                          │     WebSubService::pingHubs($rssUrl)         │
                          │  - Hub 1: https://pubsubhubbub.appspot.com/  │
                          │  - Hub 2: https://pubsubhubbub.superfeedr.com│
                          │  - Persistent 60s cooldown per feed URL      │
                          └──────────────────────┬───────────────────────┘
                                                 │
                                                 ▼
                          ┌──────────────────────────────────────────────┐
                          │         Dedicated Social RSS Feed            │
                          │         GET /{lang}/rss/social.xml           │
                          │  - Enclosure (WebP/JPEG, size, MIME)         │
                          │  - Media content (1280x720 16:9 crop)        │
                          │  - Explicit <category> hashtags (#news)      │
                          │  - Clean plain-text summaries (no tags)      │
                          │  - RFC 3986 canonical permalink GUIDs        │
                          └──────────────────────┬───────────────────────┘
                                                 │
                   ┌─────────────────────────────┴─────────────────────────────┐
                   ▼                                                           ▼
┌──────────────────────────────────────┐                   ┌──────────────────────────────────────┐
│        External RSS Bridges          │                   │      Native Meta SUAT (Optional)     │
│   (Buffer / Make / IFTTT / n8n)      │                   │   lib/NewsSocialShareService.php     │
│  - Instant WebSub push notification  │                   │  - X Provider: bypassed in RSS mode  │
│  - Ingests /{lang}/rss/social.xml    │                   │  - Meta Facebook Page (Free SUAT)    │
│  - Posts to X, Threads, Telegram,    │                   │  - Instagram Stories (1080x1920)     │
│    LinkedIn, Mastodon at zero cost   │                   │  - Background worker: cron/social    │
└──────────────────────────────────────┘                   └──────────────────────────────────────┘
```

---

## 3. Detailed Component Specifications

### 3.1 Routing & URL Configuration (`config/url.config.php` & `lib/Amed.class.php`)

#### Routing Registration (`config/url.config.php`)
Both general and social RSS feeds must be accessible with or without language prefix, routing cleanly to `index_mod`:
```php
// Sitemaps & RSS Feeds
$url['rss.xml'] = 'index/rss';
$url['rss/social.xml'] = 'index/rssSocial';
$url['rss-social.xml'] = 'index/rssSocial';
```

#### Routing Resolution Flow
1. When a user or bridge requests `http://domain.com/tr/rss/social.xml`:
   - `Url.class.php` parses path segments.
   - `Amed.class.php` extracts active language `'tr'`, strips it from `$this->url->link`, leaving `'rss/social.xml'`.
   - `config/url.config.php` matches `$url['rss/social.xml']` and dispatches to controller `index` and method `rssSocial()`.
2. When a user requests bare `http://domain.com/rss/social.xml` (without language prefix):
   - `Amed.class.php` line 612 checks `$isPublicIndexRoute`.
   - It performs a single-hop 301 redirect to canonical `http://domain.com/{default_lang}/rss/social.xml`.
   - To support clean root-level discovery alongside `sitemapIndex`, add `'rss'` and `'rssSocial'` to `$rootExemptIndexMethods` in `lib/Amed.class.php` if direct non-prefixed response is desired, OR allow the standard single-hop 301 to `/{default_lang}/rss/social.xml` (which matches existing `/rss.xml` -> `/tr/rss.xml` behavior). Standard AF practice is single-hop 301 to ensure language clarity for crawlers.

---

### 3.2 RSS Feeds Implementation (`mod/index/index.php`)

`mod/index/index.php` will feature two clean endpoints:
1. `public function rss()`: Standard general-purpose RSS 2.0 feed (`/{lang}/rss.xml`).
2. `public function rssSocial()`: Dedicated high-fidelity social syndication feed (`/{lang}/rss/social.xml`).

#### Dedicated Social RSS Feed (`rssSocial`) Specifications:
- **Feature Gate:** `$this->guardFeatureOr404('news.feed')`
- **Content-Type:** `header("content-type: text/xml; charset=utf-8");`
- **Cache-Control:** `header("Cache-Control: public, max-age=60, s-maxage=60");`
- **Namespaces:**
  ```xml
  <rss version="2.0" 
       xmlns:atom="http://www.w3.org/2005/Atom" 
       xmlns:media="http://search.yahoo.com/mrss/" 
       xmlns:content="http://purl.org/rss/1.0/modules/content/" 
       xmlns:dc="http://purl.org/dc/elements/1.1/">
  ```
- **Channel Headers:**
  - `<title>`: Site title (escaped).
  - `<link>`: Canonical localized homepage link (`{address}{lang}/`).
  - `<description>`: Site meta description.
  - `<language>`: Current language code (e.g. `tr`, `ku`, `en`).
  - `<lastBuildDate>`: RFC 2822 timestamp of latest article (`updated_at` or `created_at`).
  - `<atom:link href="..." rel="self" type="application/rss+xml" />`
  - `<atom:link href="https://pubsubhubbub.appspot.com/" rel="hub" />`
  - `<atom:link href="https://pubsubhubbub.superfeedr.com" rel="hub" />`
- **Items Query (Last 50 Published News Articles):**
  - Table: `mod_news` joined with category name and author name.
  - Conditions: `status = 'y'`, `lang = $this->site['lang']`, `(acl_id = 0 OR acl_id IS NULL)`, `created_at <= NOW()`, and `category_id IN (allowed_category_ids)`.
  - Ordering: `created_at DESC` (limit 50).
- **Item Fields for Social Automation:**
  - `<title>`: Clean text headline without HTML tags or entities.
  - `<link>`: Canonical Wikipedia-style article URL via `Helper::sitemap_url()`.
  - `<guid isPermaLink="true">`: Same as canonical link.
  - `<pubDate>`: RFC 2822 publication date (`date(DATE_RSS, strtotime($row['created_at']))`).
  - `<dc:creator>`: Author full name or editorial desk name (`habermerkezi`).
  - `<category>`: Formatted category name and auto-generated hashtags for social aggregators (e.g., `<category>Gündem</category>`, `<category>#Gündem</category>`, `<category>#Amed</category>`).
  - `<description>`: Plain clean summary text (up to 400 chars) tailored for social post bodies (no raw HTML tags, clean Unicode).
  - `<content:encoded>`: Formatted article lead with featured image `<figure><img ...></figure>` and key bullet points.
  - `<enclosure>`: Direct image URL, MIME type (`image/webp` or `image/jpeg`), and exact byte length (`filesize`).
  - `<media:content url="..." medium="image" type="..." width="1280" height="720">`: High-resolution OpenGraph crop ready for social platform link cards.
  - `<media:thumbnail url="..." />`: Thumbnail crop for mobile feed aggregators.

---

### 3.3 Real-Time WebSub / PubSubHubbub Service (`lib/WebSubService.class.php`)

Extract and formalize the WebSub notification mechanism from `GoogleIndexingService` into a dedicated, reusable, singleton-ready service: `lib/WebSubService.class.php`.

#### Class Signature:
```php
<?php
/**
 * WebSubService - Real-time WebSub / PubSubHubbub Hub Notification Service
 * Dispatches instant HTTP POST publish pings to public hubs when RSS feeds are updated.
 *
 * Amed Framework
 * @author Antigravity AI
 */
class WebSubService
{
    private static array $hubs = [
        'https://pubsubhubbub.appspot.com/',
        'https://pubsubhubbub.superfeedr.com',
    ];

    private static ?string $stateFilePath = null;

    /**
     * Notify WebSub hubs for a given feed URL or language code.
     *
     * @param string|null $feedUrl Absolute URL of the feed. If null, current language feeds are pinged.
     * @param string|null $lang Language code (e.g. 'tr', 'ku').
     * @return array Results per hub ['hub_url' => bool]
     */
    public static function pingHubs(?string $feedUrl = null, ?string $lang = null): array;

    /**
     * Ping all registered feeds (standard RSS and social RSS) for a published article's language.
     *
     * @param string $lang
     * @return bool True if at least one ping succeeded or was throttled within cooldown.
     */
    public static function pingForArticle(string $lang): bool;
}
```

#### State & Cooldown Management:
- Uses `data/indexing_state.json` under key `"websub"`.
- Cooldown: 60 seconds per feed URL to prevent denial-of-service during rapid batch editorial publishing.
- Asynchronous / Fast-Timeout: HTTP call executed via `HttpClient::postForm` with a 5-second timeout and fail-open resilience (never blocks the admin or API response).
- Hub Payload:
  ```
  hub.mode=publish
  hub.url=https://amedbulten.com/tr/rss.xml
  hub.url=https://amedbulten.com/tr/rss/social.xml
  ```

---

### 3.4 Publishing Triggers & Integration Hooks

To achieve complete parity, any action that transitions a news item to `status = 'y'` must trigger:
1. `GoogleIndexingService::notifyUpdate($absoluteUrl)` (Search Console & Google Indexing).
2. `WebSubService::pingForArticle($lang)` (Instant RSS & Social RSS push to WebSub hubs).
3. `NewsSocialShareService::enqueueForNews($newsId)` (Social outbox queue).

When transitioning to `status = 'n'` or deleting:
1. `GoogleIndexingService::notifyDelete($absoluteUrl)`.
2. `NewsSocialShareService::cancelJobsForNews($newsId)`.
3. `WebSubService::pingForArticle($lang)` (Refreshes feed to drop unpublished items).

#### Hook Integration Matrix:

| Location | Action / Method | Triggered Logic |
| :--- | :--- | :--- |
| `mod/mod_news/index.php` | `afterSave($model)` | Status `'y'`: Enqueue social outbox + `WebSubService::pingForArticle($model->lang)`. |
| `mod/mod_news/index.php` | `afterUpdate($model, $id)` | Status `'y'`: Enqueue social outbox + `WebSubService::pingForArticle($model->lang)`. Status `'n'`: Cancel jobs + WebSub ping. |
| `mod/mod_news/index.php` | `afterDelete($id)` | Cancel social jobs + `WebSubService::pingForArticle($deletedLang)`. |
| `mod/mod_news/index.php` | `toggle_status()` (Quick Toggle) | Status `'y'`: `notifyUpdate` + Social Enqueue + `WebSubService::pingForArticle($record['lang'])`. Status `'n'`: `notifyDelete` + Social Cancel + WebSub ping. |
| `mod/mod_news/index.php` | `approve()` (Batch Approval) | For each news ID: Status `'y'` -> `notifyUpdate` + Social Enqueue. After loop: `WebSubService::pingForArticle($lang)`. |
| `mod/mod_news/index.php` | `unapprove()` (Batch Unapproval) | For each news ID: Status `'n'` -> `notifyDelete` + Social Cancel. After loop: `WebSubService::pingForArticle($lang)`. |
| `lib/McpNewsTools.class.php` | `createNews(array $args)` | Status `'y'`: `notifyGoogleIndexing` + `NewsSocialShareService::enqueueForNews` + `WebSubService::pingForArticle($model->lang)`. |
| `lib/McpNewsTools.class.php` | `updateNews(array $args)` | Status `'y'`: `notifyGoogleIndexing` + `NewsSocialShareService::enqueueForNews` + `WebSubService::pingForArticle($model->lang)`. Status `'n'`: Cancel jobs + `notifyGoogleIndexing('delete')` + WebSub ping. |
| `lib/McpNewsTools.class.php` | `deleteNews(array $args)` | Cancel jobs + `notifyGoogleIndexing('delete')` + `WebSubService::pingForArticle($model->lang)`. |
| `lib/BaseModuleController.class.php` | `updateSingleColumn()` | Shared core already handles Google Indexing; module-level overrides in `mod_news/index.php` intercept `status` changes cleanly. |

---

### 3.5 Interaction with Transactional Outbox & Background Worker

#### Configuration Setting: Bridge Mode vs. Direct API Mode
In `mod_news_social_accounts` or `config/site.config.php`:
- Introduce a clean toggle / post mode:
  - When X direct API is disabled or in `rss_bridge` mode:
    - `NewsSocialShareService::enqueueForNews()` bypasses creating X API jobs or marks them as `bridged` (no prepaid credit failure).
    - Meta SUAT accounts (Facebook Page & Instagram Story) remain **active** and continue processing through `tasks/social_share_worker.php` and `cron/social`.
    - Zero cost incurred, 100% distribution maintained.

#### `SocialProviders/XProvider.class.php` Fallback Guard:
- When HTTP 402 (`credits depleted`) or prepaid quota restriction is encountered:
  - Marks job with error code `CREDITS_DEPLETED_USE_RSS_BRIDGE`.
  - Does NOT crash the worker or block subsequent Meta SUAT / Telegram jobs.
  - Generates an actionable admin notice directing users to activate the RSS Bridge recipe (Buffer / Make / IFTTT).

---

### 3.6 Phone-Only Admin Interface (`templates/admin/mod_settings/social_media.twig`)

#### Screen Ergonomics, Taste Skill Calibration & Super Polish:
- **Design Read**: High-utility administrative console for editorial operators on modern mobile viewports (<=430px), featuring tactile micro-interactions, distinct hierarchy, and zero AI slop.
- **Dials Calibration**:
  - `DESIGN_VARIANCE`: 5 (clean, balanced, structured cards)
  - `MOTION_INTENSITY`: 6 (smooth bottom-sheet spring transitions, subtle icon hover/active states)
  - `VISUAL_DENSITY`: 7 (compact, data-dense phone ergonomics without feeling cramped)

#### Core UI/UX Redesign Decisions:
1. **Separation of Link and External Action**:
   - The URL container is explicitly decoupled:
     - **URL Text Pill / Container**: Displays formatted URL. Triggered by **Double Click / Double Tap (`dblclick`)** to invoke the URL edit modal.
     - **External Navigation Icon (`↗` / `bi-box-arrow-up-right`)**: A standalone, distinct button (min 44px tap target) that opens the target social page in a new browser tab without triggering editing.
2. **Elimination of Destructive Delete Button**:
   - Standard social platform profiles (X, Facebook, Instagram, YouTube, etc.) are fixed channels. The delete button (`bi-trash3`) is permanently removed from the card.
   - Deactivation is cleanly handled via the existing toggle switch (Aktîf / Pasîf).
3. **Elimination of Redundant "Düzenle" Button**:
   - The "Düzenle" button is removed in favor of direct interaction:
     - Direct double-click on the URL pill opens the URL editing modal.
4. **Actionable Automation & Integration Hub Icons**:
   - In place of obsolete action buttons, each card features dedicated compact badges/icons:
     - `API`: Direct API status and credential configuration modal.
     - `Buffer`: Buffer recipe and integration modal.
     - `IFTTT`: IFTTT Webhook/Applet setup modal.
5. **Modern Bottom-Sheet Modals (`taste-skill` Standard)**:
   - Each modal features:
     - Prominent close button (`✕`) at the top right.
     - Backdrop dismiss (click/tap outside to close).
     - Swipe-down gestures for mobile viewports.
     - **Mandatory Operational Disclaimer**: Clearly advises operators that third-party services (Buffer, IFTTT, Make, etc.) may change subscription policies or require paid credits/plans for specific automation triggers.
6. **Dedicated "RSS Social Bridge" Card**:
   - Language selector tabs or stacked pill list (`TR`, `KU`, `EN`, etc.).
   - Ready-to-copy feed URLs:
     - General RSS: `https://domain.com/{lang}/rss.xml` [Copy Button]
     - Social Bridge RSS: `https://domain.com/{lang}/rss/social.xml` [Copy Button]
   - WebSub Status Badge: `<span class="badge badge-success"><i class="bi bi-broadcast"></i> WebSub Active</span>`.
   - Setup Guide Bottom-Sheet Trigger with explicit free-tier vs credit-based caveats.
7. **Strict Code Separation**:
   - All styling in `templates/admin/assets/css/social_media.css` (no inline styles).
   - All interactions, clipboard copy handlers, double-tap listeners, and modal triggers in `templates/admin/assets/js/social_media.js` (no inline JS).
8. **Mobile Viewport Compliance**:
   - Single-column phone layout (max 430px container width on mobile, full responsiveness on larger preview frames).
   - Minimum 44px tap targets for all copy buttons and action icons.

---

### 3.7 Comprehensive Localization (`i18n-guard` Parity across 11 Locales)

All new keys must be added to `mod/mod_settings/lang/` and `mod/mod_news/lang/` across all 11 supported languages:
`en` (English), `tr` (Turkish), `ku` (Kurdish Kurmanji), `ckb` (Kurdish Sorani), `zza` (Zazaki), `ar` (Arabic), `de` (German), `fr` (French), `ru` (Russian), `sv` (Swedish), `fa` (Persian).

#### Required Translation Keys (`mod_settings`):
- `social_bridge_title`: "RSS Social Media Bridge"
- `social_bridge_desc`: "Zero-cost automated broadcasting for X, Facebook, Threads, and Telegram."
- `rss_feed_url`: "General RSS Feed"
- `rss_social_url`: "Social Syndication RSS Feed"
- `copy_feed_url`: "Copy URL"
- `feed_copied`: "Feed URL copied to clipboard!"
- `websub_hub_status`: "Real-Time WebSub Hubs"
- `websub_active`: "Active (Instant Push)"
- `setup_guide_title`: "Automation Setup Guide"
- `setup_guide_btn`: "View Free Setup Recipes"
- `setup_guide_buffer`: "Buffer Free Plan: Connect RSS to auto-tweet up to 3 social accounts."
- `setup_guide_make`: "Make.com: Ingest WebSub RSS and cross-post to Telegram, X, and Facebook."

---

## 4. Implementation Steps & File Modifications

| Step | Action | Files | Description |
| :---: | :--- | :--- | :--- |
| **1** | Register Routes | `config/url.config.php` | Add `rss/social.xml` and `rss-social.xml` routes mapped to `index/rssSocial`. |
| **2** | Social RSS Method | `mod/index/index.php` | Implement `public function rssSocial()` with OpenGraph 1280x720 media tags, enclosures, hashtags, clean text summaries. |
| **3** | WebSub Service | `lib/WebSubService.class.php` | Create dedicated WebSub pusher with multi-hub support (`pubsubhubbub.appspot.com`, `superfeedr`), persistent 60s cooldown, and async HTTP post. |
| **4** | Autoload Registration | `config/autoload.config.php` | Add `'WebSubService'` to system class autoload list. |
| **5** | Publishing Hooks | `mod/mod_news/index.php` | Integrate `WebSubService::pingForArticle()` into `afterSave`, `afterUpdate`, `afterDelete`, `toggle_status`, `approve`, `unapprove`. |
| **6** | MCP Hook Parity | `lib/McpNewsTools.class.php` | Integrate `WebSubService::pingForArticle()` and `NewsSocialShareService::enqueueForNews()` into `createNews`, `updateNews`, `deleteNews`. |
| **7** | Admin Social UI | `templates/admin/mod_settings/social_media.twig` | Add phone-optimized RSS Bridge card with copy-to-clipboard buttons, WebSub badges, and recipe modal. |
| **8** | CSS & JS Enhancements | `templates/admin/assets/css/social_media.css`<br>`templates/admin/assets/js/social_media.js` | Add phone-first styling for RSS cards, copy pills, and clipboard toast handler with zero inline JS/CSS. |
| **9** | Localization | `mod/mod_settings/lang/*.php`<br>`mod/mod_news/lang/*.php` | Add descriptive English keys and propagate 100% translations to all 11 locales. |
| **10** | Quality Audits | Quality Skills | Run `task-validation`, `admin-mobile-check`, `i18n-guard`, and `php -l` across all modified files. |

---

## 5. Verification Commands & Acceptance Criteria

### 5.1 Verification Commands
```bash
# 1. PHP Syntax Check
php -l config/url.config.php
php -l lib/WebSubService.class.php
php -l mod/index/index.php
php -l mod/mod_news/index.php
php -l lib/McpNewsTools.class.php

# 2. Curl Route Tests (inside Docker or host)
curl -s -I http://localhost:8080/tr/rss/social.xml
curl -s http://localhost:8080/tr/rss/social.xml | grep -E "(enclosure|media:content|category)"

# 3. WebSub Pinger Test
php -r 'require "index.php"; var_dump(WebSubService::pingForArticle("tr"));'

# 4. MCP Tools Test (Dry-run news creation with status 'y')
# Verify both Google Indexing, WebSub ping, and Social enqueue occur

# 5. Mobile Admin Layout Audit
# Run admin-mobile-check on templates/admin/mod_settings/social_media.twig

# 6. i18n Completeness Audit
# Run i18n-guard on mod/mod_settings/lang/ and mod/mod_news/lang/
```

### 5.2 Acceptance Criteria
- [x] Dedicated social RSS feed returns valid XML with proper enclosure, 1280x720 media content, and clean descriptions.
- [x] WebSub hub pings execute reliably on publication with 60-second in-process/file-based rate-limiting.
- [x] Admin Social Settings UI allows easy copying of localized feed URLs on mobile viewports (<430px) with 0 inline CSS/JS.
- [x] 100% translation completeness across all 11 languages with 0 missing keys.
- [x] Full operational parity between Web UI publishing and MCP news management.
- [x] Zero API credit depletion blockers for news publishing.
