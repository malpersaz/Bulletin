# Modernization, Tracking, and Google Integration Plan

This document outlines the objectives and implementation strategy for the modernization of Amida Kurd, focusing on homepage design, internal tracking logic, and Google services integration.

## 1. Objectives

### 1.1 Homepage Modernization (NYT Style)
- **Goal:** Implement a minimalist, high-contrast news layout inspired by the New York Times.
- **Categorization:** Separate news into distinct category sections based on the `show_on_homepage` attribute.
- **Dynamic Selection:** Editors can now "Pin" categories to the homepage via the Admin Panel.

### 1.2 Content Indicators & Rich Media Integration
- **Goal:** Provide dynamic visual markers for gallery, video, and attachments.
- **Status:** Logic implemented in helpers; UI integration in progress for the new homepage.

### 1.3 Modern Sitemap & RSS System
- **Status:** **Verified & Active**
- **Sitemap Index:** Implemented `sitemap.xml` linking language-specific sitemaps. Successfully submitted to Google Search Console.
- **RSS Feed:** Implemented a robust RSS 2.0 system (`/rss.xml`) for all languages. Includes:
    - High-quality image integration (`media:content` and `enclosure`).
    - Standardized summaries.
    - Automatic routing via `.htaccess`.
    - Language-specific feeds (e.g., `/ku/rss.xml`).

### 1.4 Google Services & Legacy SEO Preservation
- **Status:** **In Progress (Active Redirection)**
- **Completed:** 301 Redirects for legacy `/nuce/` and `/tekst/` paths.
- **Monitoring:** Monitoring indexation via Search Console.

## 2. Technical Implementation

### 2.1 Database & Schema Standardization
- **Column Addition:** Added `show_on_homepage` (ENUM 'y', 'n') to `mod_news_category` and `mod_publication_category`.
- **Property Mapping:** Updated PHP Models (`mod_news_category_model`, `mod_publication_category_model`) to recognize the new attribute.
- **Installer Sync:** Updated SQL files in `installer/` to include the new homepage pinning logic.

### 2.2 Engineering Standards (Purification)
- **Language Keys:** Replaced all occurrences of non-English keys (e.g., `baslik`) with standardized English keys (`title`).
- **De-colonial Approach:** Ensured all UI labels are pulled from `lang` files, allowing pure Kurdish/Zazakî translations without hardcoded defaults.
- **Global Actions:** Implemented "Bulk Pin" functionality in the category list with visual indicators (Eye icons).

### 2.3 IP-Based View Tracking
- **Status:** **Robust & Stable**
- **Scope:** Active across all content modules.

## 3. Current Status

| Feature | Status | Notes |
| :--- | :--- | :--- |
| Sitemap Index | **Completed** | Submitted to Google Search Console |
| RSS Feed System | **Completed** | Active with image support for all languages |
| Category Pinning (Admin) | **Completed** | UI and Controller logic for `show_on_homepage` |
| DB Schema Update | **Completed** | `show_on_homepage` column added and mapped |
| Language Standardization | **Completed** | Replaced 'baslik' with 'title' and added std keys |
| NYT Homepage Wiring | **Completed** | Fetches and displays news from pinned categories |

## 4. Maintenance & Future Steps

1.  **Rich Media Indicators:** Finalize Bootstrap icon integration in news listings (gallery, video, attachments).
2.  **Publication Categories:** Apply the same logic (Pinning) to Publication categories if needed in the future.
3.  **Bulk Removal:** Continue requesting removal of legacy URLs in Search Console.

---
*Updated on: April 3, 2026 (RSS & Category Management Update)*
