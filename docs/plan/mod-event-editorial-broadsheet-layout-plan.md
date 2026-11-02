# `mod_event` Editorial Broadsheet Layout & Multi-Section Spatial Partitioning Plan

- **Target Repository:** `AF/Bulletin` (Amed Framework & Bulletin CMS)
- **Target Modules:** `mod_event`, `mod_event_category`, and `mod/index/helper/mod_event.helper.php`
- **Target Templates:** `templates/amed/index/mod_event/events.twig`, `templates/amed/index/mod_event/event.twig`, `templates/amed/index/mod_event/calendar.twig`, and `templates/amed/index/mod_event/sections/section_*.twig`
- **Target Assets:** `templates/amed/static/css/events.css`, `templates/amed/static/js/event-calendar.js`
- **Design Framework:** `taste-skill` (Anti-Slop Broadsheet Newspaper Layout & Spatial Discipline)
- **Status:** Active Implementation

---

## 1. Taste Skill Calibration & Root-Cause Diagnosis

### 1.1 Taste Skill Design Read
> *"Reading this as: A regional civic and cultural broadsheet event hub for news readership, featuring an authoritative editorial hierarchy, partitioned multi-column section grids, and distinct visual grammars for poster-driven cultural events versus non-poster civic calendar schedules."*

- **`DESIGN_VARIANCE`**: `8` (Multi-zone broadsheet grid: 2-column paired cultural stage + 3-column civic schedule board).
- **`MOTION_INTENSITY`**: `4` (Subtle elevation transitions, live today pulse badge, zero visual clutter).
- **`VISUAL_DENSITY`**: `7` (Zero wasted horizontal whitespace; every section shares row geometry with its peers).

### 1.2 Root-Cause Flaws Fixed by This Architecture
1. **Eliminating Full-Width Vertical Twig Stacking:**
   - Previously, `events.twig` rendered every `section_*.twig` inside a single vertical container where each `<section>` occupied 100% width, while a single card inside it occupied only 50% (`col-lg-6`), leaving the entire right half of the page blank.
   - **Solution:** The sections themselves now partition the page grid (`row g-4`):
     - **Cultural Poster Sections** (`culture_art`, `literature_talk`, `cinema_documentary`, `workshop_academy`, `default`) are placed in `col-12 col-lg-6` section columns on the Hub view so two categories sit side-by-side in each row.
     - **Civic Schedule Sections** (`exams`, `official_holidays`, `holidays`) are placed in `col-12 col-lg-4` section columns on the Hub view so all three civic calendar categories sit side-by-side across a unified 3-column row.
2. **Distinct Visual Grammar per Category Type (Poster vs. Non-Poster):**
   - **Cultural Categories** (`Kültür & Sanat`, `Edebiyat & Söyleşi`, `Sinema & Belgesel`, `Atölye & Akademi`): Feature 3:4 vertical posters on the **left** (`event-card-media`) and structured metadata (topic badge, title, summary, full date, time, and venue) on the **right** (`event-card-body`).
   - **Civic & Calendar Categories** (`Sınavlar`, `Resmi Tatil`, `Bayramlar`): Never use posters. Instead, each has a bespoke schedule card design:
     - **`Sınavlar` (`section_exams.twig`):** Academic calendar sheet with a left date seal, institution badge (`ÖSYM`, `MEB`), exam session time, and countdown pill (`X gün kaldı`).
     - **`Resmi Tatil` (`section_official_holidays.twig`):** Statutory holiday strip with crimson accent border, start–end date span, total holiday duration (`X Gün`), and countdown badge.
     - **`Bayramlar` (`section_holidays.twig`):** Celebration & observance card with emerald/amber accent, multi-day duration badge, date range, and countdown pill.
3. **Detail Page (`event.twig`) Poster & Info Alignment:**
   - On `/event/{seo}`, when an event has a poster, the poster is positioned on the **left** and the event header/summary/quick-info sits on the **right**, eliminating the awkward centered poster block.
4. **Localization & Code Quality Fixes:**
   - Fix header count formatting in `events.twig` (`4 Etkinlik` instead of `4 Etkinlikler`).
   - Replace hardcoded `"Today"` in `event-calendar.js` with localized `data-today-label` from `calendar.twig`.
   - Remove forbidden `|default(...)` fallbacks on localized properties in `event.twig` and `event_agenda.twig`.
   - Add missing `lang.index.search.mod_event` key across all 11 language packs (`mod/index/lang/*.php`).

---

## 2. Spatial Partitioning Architecture on `/events`

```
+---------------------------------------------------------------------------------------------------+
| Breadcrumb & Broadsheet Header ("Tüm Etkinlikler" | Count Badge | Category Filter Pills)          |
+-------------------------------------------------+-------------------------------------------------+
| [col-12 col-lg-6]                               | [col-12 col-lg-6]                               |
| SECTION: Kültür & Sanat                         | SECTION: Edebiyat & Söyleşi                     |
| +---------------+-----------------------------+ | +---------------+-----------------------------+ |
| | POSTER (3:4)  | Topic Badge                 | | | POSTER (3:4)  | Topic Badge                 | |
| | + Date Seal   | Headline & Summary          | | | + Date Seal   | Headline & Summary          | |
| | (Left)        | Date • Time • Venue (Right) | | | (Left)        | Date • Time • Venue (Right) | |
| +---------------+-----------------------------+ | +---------------+-----------------------------+ |
+-------------------------------------------------+-------------------------------------------------+
| [col-12 col-lg-6]                               | [col-12 col-lg-6]                               |
| SECTION: Sinema & Belgesel                      | SECTION: Atölye & Akademi                       |
| +---------------+-----------------------------+ | +---------------+-----------------------------+ |
| | POSTER (3:4)  | Topic Badge                 | | | POSTER (3:4)  | Topic Badge                 | |
| | + Date Seal   | Headline & Summary          | | | + Date Seal   | Headline & Summary          | |
| | (Left)        | Date • Time • Venue (Right) | | | (Left)        | Date • Time • Venue (Right) | |
| +---------------+-----------------------------+ | +---------------+-----------------------------+ |
+---------------------------------+---------------------------------+-------------------------------+
| [col-12 col-lg-4]               | [col-12 col-lg-4]               | [col-12 col-lg-4]             |
| SECTION: Sınavlar               | SECTION: Resmi Tatil            | SECTION: Bayramlar            |
| (No Poster - Exam Schedule)     | (No Poster - Statutory Strip)   | (No Poster - Holiday Card)    |
| [Date Seal] Institution + Title | [Date Seal] Duration + Title    | [Date Seal] Duration + Title  |
|             Countdown + Time    |             Date Span + Status  |             Date Span + Status|
+---------------------------------+---------------------------------+-------------------------------+
```

---

## 3. Verification & Quality Gates

1. `php -l mod/index/helper/mod_event.helper.php`
2. `python3 tools/code_separation_guard.py` (0 violations)
3. `python3 tools/i18n_guard.py` (0 errors)
4. Chrome DevTools full-page screenshot verification on `http://localhost:8080/tr/events`.
