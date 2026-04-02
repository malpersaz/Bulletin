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
- **Status:** **In Progress / Partially Completed**
- **Completed Tasks:**
    - Developed dynamic XML sitemap generators in `mod/index/index.php` for `news`, `publications`, `categories`, and `pages`.
    - Implemented `.htaccess` rewrite rules to support standard `.xml` extensions (e.g., `newsSitemap.xml`).
    - Verified domain ownership in Google Search Console via Cloudflare integration.
- **Pending Tasks (To Do):**
    - **Submission:** Submit the new XML URLs (`newsSitemap.xml`, `publicationSitemap.xml`, etc.) to Google Search Console.
    - **Cleanup:** Manually remove legacy sitemap entries (like `contentSitemap`) from Search Console.
    - **Sitemap Index:** (Recommended) Create a master `sitemap.xml` index file that points to all individual sitemaps for easier management.
    - **Legacy Redirection:** Implement `410 Gone` status for `/nuce/` prefixed legacy URLs to force Google to prioritize the new sitemap structure.

### 1.4 Google Services Ecosystem
- **Google Analytics 4 (GA4):** Integrate real-time behavior tracking (scroll depth, clicks, time on page).
- **Google Search Console:** Verify domain ownership and synchronize sitemaps for rapid indexing.
- **Legacy Cleanup:** Implement a strategy to remove thousands of dead URLs from the old framework using `410 Gone` status codes and Search Console bulk removals.

## 2. Technical Implementation

### 2.1 IP-Based View Tracking (Anti-F5 Spam)
- **Goal:** Replace simple refresh-based tracking with a robust system to ensure accurate data.
- **Logic:** Track views per IP address per content item per day using the `sys_view_logs` table.
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
4. **Google Indexing API:** (Optional) Implement the Indexing API for instant news submission.

---
*Documented on: April 2, 2026*
