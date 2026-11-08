# Google News Freshness, Crawl Budget Rebalance & SERP Dominance Restoration Plan

**Target Domain:** `https://amedbulten.com`
**Repository:** `AF/Bulletin`
**Date:** 2026-11-07
**Author:** Antigravity AI — Autonomous Engineering & Quality Agent

---

## 1. Goal Description & Executive Summary

### Problem Statement
When running queries such as `site:amedbulten.com` or analyzing live Google Search Console (GSC) organic performance, static statutory/utility pages (`/legal`, `/contact`, `/compliance`, `/cookie-policy`, `/tr/page/Basin_Etigi_ve_Uyum`) dominate top positions (`1.0` to `4.5`), while freshly published news articles from `mod_news` are either omitted, relegated to deep positions (`30.0` to `70.0+`), or delayed in indexation.

Furthermore, while Google Publisher Center publication `CAowv8TNDA` is active, Google's 2024–2025 platform migration removed RSS/section ingestion and transitioned Google News entirely to an automated, web-crawler and sitemap-driven discovery pipeline.

### Objective
Rebalance internal link equity, crawl budget, and indexation directives so that Google's crawlers (`Googlebot` and `Googlebot-News`) prioritize fresh news journalism over static utility pages, accelerate fresh article indexing to within minutes of publication, and restore organic search visibility for editorial content.

```mermaid
flowchart TD
    subgraph Current_State["Current State (Defective)"]
        F1["Footer & Header on 100+ News Articles"] -->|100+ Dofollow Links| L1["/legal, /cookie-policy, /compliance, /accessibility"]
        L1 -->|High Internal PageRank| G1["Googlebot Indexes Policy Pages in Top Positions (1-4)"]
        S1["tr/newsSitemap.xml (83 URLs)"] -->|47 Stale URLs >48h Without news:news Tag| W1["Google News Crawler Dilution / Lag"]
        N1["Fresh News Articles"] -->|Delayed Indexation (24-48h)| D1["Omitted from Top SERP"]
    end

    subgraph Proposed_State["Target State (Restored)"]
        F2["Footer Links to Utility Pages"] -->|rel='nofollow' + meta noindex| L2["Utility Pages (cookie-policy, accessibility)"]
        F2 -->|Essential E-E-A-T Dofollow| E2["Core Transparency (legal, editorial-policy, contact)"]
        S2["tr/newsSitemap.xml"] -->|Strict 48h SQL Filter (100% with news:news)| W2["Googlebot-News Instant Discovery"]
        N2["Fresh News Cross-Linking"] -->|Son 24 Saat & Related News| N3["Horizontal Link Equity Between Articles"]
        N2 -->|Immediate GSC / RSS Ping| G2["Fresh News Surfaces in SERP & Top Stories"]
    end
```

---

## 2. User Review Required

> [!IMPORTANT]
> **1. `noindex, follow` on Pure Utility Pages:**
> Utility pages that provide no organic search value to readers (`/cookie-policy`, `/accessibility`, `/security-policy`, `/terms-of-service`) will receive `<meta name="robots" content="noindex, follow">`. They will remain fully accessible to users, but Google will drop them from SERP results so they stop outranking news articles.
> *Core transparency pages (`/legal`, `/editorial-policy`, `/contact`, `/compliance`) will remain `index, follow` to satisfy Google E-E-A-T guidelines.*

> [!IMPORTANT]
> **2. Strict 48-Hour Filter for `tr/newsSitemap.xml`:**
> Google News specifications explicitly require that Google News Sitemaps contain *only* articles published within the last 48 hours. The current implementation fetches up to 1000 articles and omits `<news:news>` for older rows. We will enforce strict SQL filtering (`created_at >= NOW() - 48h`) so `newsSitemap.xml` contains 100% pure Google News items. Historical articles remain covered by `sitemap.xml`.

---

## 3. Open Questions

*There are no blocking ambiguities. The architecture and Google guidelines are clear and deterministic.*

---

## 4. Proposed Changes

### Component 1: Google News Sitemap Hardening (`mod/index/index.php`)

#### [MODIFY] `mod/index/index.php`
- Enforce strict 48-hour cutoff in SQL query for `newsSitemap()`:
  ```php
  $recentNewsCutoff = time() - (48 * 3600);
  $news->addWhere("created_at >= '" . date('Y-m-d H:i:s', $recentNewsCutoff) . "'");
  ```
- Ensure every single `<url>` in `newsSitemap.xml` includes the required `<news:news>` block.
- Adjust `priority` in `pageSitemap()`: reduce policy slugs from `0.60` to `0.30` and dynamic pages to `0.40`, ensuring `newsSitemap.xml` (`0.90` / `0.80`) and homepage (`1.0`) receive primary crawler focus.

---

### Component 2: Crawl Budget & Indexation Rebalancing (`templates/amed/`)

#### [MODIFY] `templates/amed/index/legal/cookie_policy.twig`
- Update meta robots:
  ```twig
  {% block meta_tags %}
      <meta name="robots" content="noindex, follow">
  ```

#### [MODIFY] `templates/amed/index/legal/accessibility.twig`
- Update meta robots:
  ```twig
  {% block meta_tags %}
      <meta name="robots" content="noindex, follow">
  ```

#### [MODIFY] `templates/amed/index/legal/security_policy.twig`
- Update meta robots:
  ```twig
  {% block meta_tags %}
      <meta name="robots" content="noindex, follow">
  ```

#### [MODIFY] `templates/amed/index/legal/terms_of_service.twig`
- Update meta robots:
  ```twig
  {% block meta_tags %}
      <meta name="robots" content="noindex, follow">
  ```

#### [MODIFY] `templates/amed/index/footer.twig`
- Add `rel="nofollow"` to utility-only links (`cookie-policy`, `accessibility`, `security-policy`, `terms-of-service`) to prevent internal PageRank drain away from news articles, while maintaining clean dofollow links to E-E-A-T anchors (`legal`, `editorial-policy`, `contact`, `compliance`).

---

### Component 3: Internal Link Equity for Fresh News (`templates/amed/index/mod_news/`)

#### [MODIFY] `templates/amed/index/mod_news/news.twig`
- Strengthen the "Son 24 Saat" (Last 24 Hours) sidebar and "İlgili Haberler" (Related News) grid.
- Ensure all recent article links have clear semantic anchors, descriptive titles, and optimized thumbnail dimensions to maximize crawler traversal.

---

### Component 4: RSS & Real-time Discovery Streamline

#### [MODIFY] `mod/index/index.php` (`rss()`)
- Ensure `lastBuildDate` matches the most recent article's timestamp instead of current wall-clock time if no new article was published, avoiding spurious cache invalidations.
- Ensure `<pubDate>` and `<atom:link>` accurately mirror the live canonical domain.

---

## 5. Verification Plan

### Automated Tests
1. **PHP Syntax & Template Validation:**
   ```bash
   composer lint:syntax
   python3 tools/code_separation_guard.py
   python3 tools/i18n_guard.py
   ```
2. **Sitemap Purity & Age Verification:**
   ```bash
   python3 -c "
   import urllib.request, xml.etree.ElementTree as ET
   from datetime import datetime, timezone, timedelta
   url = 'http://localhost:8080/tr/newsSitemap.xml'
   # verify all items have <news:news> and are <= 48h
   "
   ```
3. **HTTP Header & Meta Robots Verification:**
   Verify `cookie-policy`, `accessibility`, and `security-policy` output `noindex, follow`.
   Verify `news.twig` outputs `index, follow, max-image-preview:large, max-snippet:-1`.

### Manual Verification
1. Inspect live GSC URL Inspection for updated sitemaps.
2. Verify that `site:amedbulten.com` indexation rebalances away from utility pages over successive crawl passes.
