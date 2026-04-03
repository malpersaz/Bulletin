# Modernization, Tracking, and Google Integration Plan

This document outlines the objectives and implementation strategy for the modernization of Amida Kurd, focusing on homepage design, internal tracking logic, and Google services integration.

## 1. Objectives

### 1.1 Homepage Modernization (NYT Style)
- **Goal:** Implement a minimalist, high-contrast news layout inspired by the New York Times.
- **Categorization:** Separate news into distinct category sections with modular layout patterns (Layout A, B, C).
- **Indicators:** Add visual cues for media-rich content and engagement metrics.

### 1.2 Content Indicators & Rich Media Integration
- **Goal:** Provide dynamic visual markers to inform users about the content type and engagement metrics before they click.
- **Detailed Logic & Integration Plan:**
    - **Attachment Indicator (`bi-paperclip`):**
        - **Logic:** Triggered if `file_id > 0` or if there are related entries in `mod_attachment_mod`.
        - **Integration:** Directly linked to the `mod_attachment` system.
        - **Utility:** Highlighting downloadable resources (PDFs, docs) increases the professional value of the article.
    - **Gallery Indicator (`bi-images`):**
        - **Logic:** Triggered if `gallery_id > 0` or if the associated `mod_gallery` count is greater than zero.
        - **Integration:** Synchronized with `mod_gallery`.
        - **Utility:** Users seeking visual content can easily identify photo-essays or image-rich reports.
    - **Video Indicator (`bi-play-circle-fill`):**
        - **Logic:** Checks the `video` column (unserialized array) or links from `mod_video_embed`.
        - **Integration:** Connected to `mod_video` and `mod_video_embed` modules.
        - **Utility:** Increases Click-Through Rate (CTR) for users preferring multimedia consumption.
    - **View Count (`bi-eye`):**
        - **Logic:** Displays the `view_count` value stored in the database.
        - **Integration:** Powered by the newly implemented **IP-Based Tracking System** to ensure data integrity.
        - **Utility:** Provides "Social Proof," showing the popularity and relevance of the news item.
    - **Comment Count (`bi-chat-dots`):**
        - **Logic:** Retrieves the count of approved entries from the comment system.
        - **Integration:** Planned for integration with a future local comment module or external API.
        - **Utility:** Encourages user participation and community discussion.

### 1.3 Modern Sitemap System
- **Status:** **Verified & Active**
- **Completed Tasks:**
    - Developed dynamic XML sitemap generators in `mod/index/index.php` for all languages.
    - Implemented **Master Sitemap Index** (`sitemap.xml`) to link all language-specific sub-sitemaps.
    - Standardized URLs to use **lowercase language codes** (e.g., `/ku/` instead of `/KU/`) across the entire system.
    - Created **`robots.txt`** to point Google directly to the master sitemap.
    - Implemented `.htaccess` rewrite rules for both global and language-specific XML URLs.
    - Registered sitemap routing in `config/url.config.php` for Amed Framework compatibility.

### 1.4 Google Services & Legacy SEO Preservation
- **Status:** **In Progress (Active Redirection)**
- **Completed Tasks:**
    - **301 Redirect Strategy:** Implemented `legacyRedirect()` and `legacyRedirectPublication()` to preserve 21 years of SEO authority.
    - **URL Normalization:** Automated redirection of legacy `/nuce/` and `/tekst/` paths to the new `/news/` and `/publication/` structure.
    - **Google Search Console:** Verified domain ownership and submitted the master sitemap.
- **Pending Tasks (To Do):**
    - **Monitoring:** Monitor indexation rates and 404 error reduction in Search Console.
    - **Bulk Removal:** Manually request removal of indexed `/nuce/` URLs if they persist alongside new `/news/` URLs.

## 2. Technical Implementation

### 2.1 IP-Based View Tracking (Anti-F5 Spam)
- **Status:** **Robust & Stable**
- **Logic:** Track views per IP address per content item per day using the `sys_view_logs` table.
- **Race Condition Protection:** Implemented **`INSERT IGNORE`** and `affected_rows()` logic to prevent database exceptions during simultaneous requests.
- **Framework Integration:** Added `affected_rows()` method to core `Database` wrapper for full PDO/PHP8 support.
- **Scope:** Active across `mod_news`, `mod_publication`, `mod_video_embed`, `mod_page`, and `mod_attachment`.

### 2.2 Database Schema Changes
- Created `sys_view_logs` table to store IP-based access logs.
- Added `view_count` column to all content-related tables.
- Synchronized these changes into the `installer/` package for future deployments.

### 2.3 Centralized Logic
- Implemented `_update_view_count()` in `indexBase.php` and `indexMain.php` as a core framework feature.
- Integrated the tracking call into individual module helpers to ensure seamless data collection.

### 2.4 URL Routing & SEO
- Updated `.htaccess` to handle `.xml` sitemap requests.
- Prepared rules for "Force Non-WWW" and legacy URL redirection to consolidate SEO authority.

## 3. Current Status

| Feature | Status | Notes |
| :--- | :--- | :--- |
| NYT Homepage Template | **Completed** | Available in `templates/default/index/mod_news/category_sections.twig` |
| IP-Based Tracking Logic | **Completed** | Active in `mod_news`, `mod_publication`, `mod_video`, `mod_page`, `mod_attachment` |
| New XML Sitemaps | **Completed** | `newsSitemap.xml`, `publicationSitemap.xml`, `categorySitemap.xml`, `pageSitemap.xml` |
| Installer Sync | **Completed** | SQL files updated with `view_count` and log tables |
| GA4 Integration | **Completed** | Script added to `base.twig`, dynamic via `google_analytics_id` |
| Google Ownership | **Completed** | Verified for `amidakurd.net` via Cloudflare DNS |

## 4. Maintenance & Future Steps

1. **Backend Wiring:** Connect the homepage template sections to live database queries.
2. **Bulk Removal:** Finalize the removal of `/nuce/` prefixed URLs in Google Search Console.
3. **Data Analysis:** After 1 week of tracking, review `sys_view_logs` to analyze reader behavior and most popular categories.
## 5. Implementation Roadmap (Step-by-Step)

Based on the Amed Framework architecture, the following steps will be taken to complete the pending tasks:

### 5.1 Master Sitemap Index & SEO (Phased Goal 1)
- **Action:** Add `sitemap()` method to `index_mod` class in `mod/index/index.php`. This method will output a `<sitemapindex>` linking to all existing sub-sitemaps.
- **Routing:** Update `.htaccess` to include `RewriteRule ^sitemap.xml$ index.php?method=sitemap [L]`.
- **410 Gone:** Add `gone()` method to `index_mod` and map legacy patterns (`/nuce/.*`) to it in `.htaccess`.

### 5.2 NYT Homepage Wiring (Phased Goal 2)
- **Logic:** Implement `_get_category_news($category_seo, $limit)` in `indexBase.php` to fetch news for specific sections.
- **Integration:** Call this method within `index_mod::index()` and assign results to Twig context (e.g., `kurdi_news`, `cihan_news`).
- **Template:** Update `category_sections.twig` to iterate over these variables using Twig loops.

### 5.3 Rich Media Indicators (Phased Goal 3)
- **Helper Update:** Modify `mod/index/helper/mod_news.helper.php` (and others) to check for `gallery_id`, `file_id`, and `video` fields.
- **UI Logic:** Pass flags like `has_gallery`, `has_files`, `has_video` to the template context.
- **Visuals:** Add Bootstrap icons with conditional logic in `templates/default/index/mod_news/newses.twig`.

### 5.4 Analytics & Verification
- **Log Review:** Use SQL queries to monitor `sys_view_logs` for accuracy.
- **Search Console:** Finalize manual submission of `sitemap.xml` and monitor indexation.

---
*Updated on: April 3, 2026*
