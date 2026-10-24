# Homepage Right Sidebar Architecture Plan

## 1. Executive Summary & Architectural Objective
This document defines the concrete implementation plan for the **Homepage Right Sidebar Column** (`#homepageHeroSidebar`, 4-column / `384 px` desktop width) in `AF/Bulletin`.

All specifications reference existing modules, database tables, category slugs, image dimensions, and helper classes in `AF/Bulletin` and match the finalized wireframe in `docs/homepage-layout-wireframe.html`. No foreign modules are introduced (strictly adhering to `tools/module_boundary_guard.py`).

---

## 2. Sidebar Spatial Geometry & Block Hierarchy

On desktop viewports (`>= 992px`), the homepage container (`1200 px` content width) is split into:
- **Left Main Column (`#homepageHeroMain`):** `800 px` (`col-lg-8`)
- **Column Gap:** `16 px`
- **Right Sidebar Column (`#homepageHeroSidebar`):** `384 px` (`col-lg-4`)

The Right Sidebar contains three vertical blocks stacked with a `16 px` gap:

1. **Block R1 — World & Middle East News (`Dünya & Ortadoğu`):**
   - Exact desktop height: **`450 px`** (horizontally aligned 1:1 with the `800 × 450 px` Hero Slider on the left).
   - Hierarchy: **1 Lead Card** (`160 × 90 px` `16:9` thumbnail + headline + 120-char summary) + **2 Compact Sub-Cards** (`104 × 58 px` `16:9` thumbnails + headline + 120-char summary).
2. **Block R2 — Economy News & Market Ticker (`Ekonomi & Piyasalar`):**
   - Top Sub-Block: **Sister News Layout** for the `Ekonomi` category (**1 Lead Card** `160 × 90 px` + **2 Compact Sub-Cards** `104 × 58 px`, each with headline + 120-char summary).
   - Bottom Sub-Block: **Professional Market Ticker (`Piyasalar`)** displaying 8 financial instruments in a `2 × 4` compact grid (`GRAM ALTIN`, `DOLAR`, `EURO`, `STERLİN`, `BIST 100`, `BITCOIN`, `GRAM GÜMÜŞ`, `BRENT`).
3. **Block R3 — Event Agenda (`Etkinlik Ajandası`):**
   - Positioned directly below the Economy block.
   - Powered by `mod_announcements` (`mod/mod_announcements/index.php` and `mod/index/helper/mod_announcements.helper.php`).
   - Features a header bar with `Tümü` link + `<` / `>` navigation buttons, a split body (left `300 × 390` portrait poster preview + right 5-item interactive event list), and bottom pagination dots.

---

## 3. Database & Category Grounding

Live `mod_news_category` records verified in the database for `lang = 'tr'` (and corresponding localized categories in `ku` and `en`):

| Block | Category ID (`tr`) | Category Name (`tr`) | Category SEO (`tr`) | Image Source (`16:9`) |
| :--- | :--- | :--- | :--- | :--- |
| **Block R1** | `27`, `28` | `Dünya`, `Ortadoğu` | `Dünya`, `Ortadoğu` | `/static/mod_news/thumb/{image}` (`480 × 270 px`) |
| **Block R2** | `26` | `Ekonomi` | `Ekonomi` | `/static/mod_news/thumb/{image}` (`480 × 270 px`) |
| **Block R3** | `mod_announcements` | `Duyuru ve İlanlar` / `Etkinlik` | `/tr/announcements` | `/static/mod_announcements/thumb/{image}` (`300 × 390 px`) |

> [!NOTE]
> Because `/static/mod_news/thumb/{image}` is generated at `480 × 270 px` (`16:9`) by `mod/mod_news/index.php`, both the Lead Card thumbnail (`160 × 90 px`, `16:9`) and the Sub-Card thumbnails (`104 × 58 px`, `16:9`) render native thumbnails without aspect-ratio cropping.
> Similarly, `mod/mod_announcements/index.php` (lines 28–36) generates `/static/mod_announcements/thumb/{image}` at `300 × 390 px` (`10:13` portrait poster ratio), which fits the Event Agenda's left poster preview (`120 × 156 px` -> `130 × 169 px`).

---

## 4. Detailed Component Specifications

### 4.1. Block R1: World & Middle East (`Dünya & Ortadoğu`)
- **Template:** `templates/amida/index/mod_news/sidebar_world_news.twig`
- **Backend Helper Call (`mod/index/index.php`):**
  ```php
  self::_world_news($this->template, 3);
  ```
- **Query Logic (`mod/index/helper/mod_news.helper.php`):**
  - Calls `mod_news_helper::_category_news(['Dünya', 'Ortadoğu'], 3, 'id,name,seo,summary,image,category_id,category_name,category_seo,created_at')` (with language-aware fallback for `ku` and `en` category slugs).
  - Filters: `status = 'y'`, `lang = $lang`, `slider = 'n'`, ACL & allowed category validation.
  - Truncates `summary` to **120 characters** (`summary_short`):
    ```php
    $summaryPlain = trim(strip_tags((string) ($val['summary'] ?? '')));
    $val['summary_short'] = mb_strlen($summaryPlain) > 120
        ? mb_substr($summaryPlain, 0, 117) . '...'
        : $summaryPlain;
    ```
- **Visual Hierarchy (`384 × 450 px`):**
  - **Header (`38 px`):** Blue accent indicator (`#0284c7`), title `Dünya & Ortadoğu`, and `Tümü` link (`/tr/news/category/Dünya`).
  - **Item 1 — Lead Story (`~170 px`):**
    - Highlighted container (`background: #f8fafc`, `border: 1px solid #cbd5e1`).
    - Left image: `160 × 90 px` (`16:9`), `object-fit: cover`.
    - Right title: `0.86rem`, `font-weight: 800`, 3-line clamp.
    - Full-width bottom summary: `0.74rem`, 2-line clamp, 120 characters.
  - **Items 2 & 3 — Sub Stories (`~96 px` each):**
    - Compact container (`background: #ffffff`, `border: 1px solid #e2e8f0`).
    - Left image: `104 × 58 px` (`16:9`), `object-fit: cover`.
    - Right column: `0.78rem` bold title (2-line clamp) + `0.70rem` 120-char summary (2-line clamp).

---

### 4.2. Block R2: Economy & Markets (`Ekonomi & Piyasalar`)
- **Template:** `templates/amida/index/mod_news/sidebar_economy_news.twig`
- **Backend News Call (`mod/index/index.php`):**
  ```php
  self::_economy_news($this->template, 3);
  self::_market_ticker($this->template);
  ```
- **Part A — Sister News Layout (`Ekonomi` Category, 3 Items):**
  - Reuses the exact CSS component classes of Block R1 (`.sidebar-sister-block`, `.lead-card`, `.sub-card`) with an emerald accent (`#059669`) and header link to `/tr/news/category/Ekonomi`.
  - Item 1 renders as the `160 × 90 px` Lead Card; Items 2 & 3 render as `104 × 58 px` Sub-Cards, each with 120-character summaries.
- **Part B — Professional Market Ticker (`Piyasalar` `2 × 4` Grid):**
  - **Helper:** `mod/index/helper/market.helper.php` (`market_helper::_get_ rates()`)
  - **Caching Contract:** Uses the framework's `Cache` / file cache under `cache/sql/` (or `cache/market_rates.json`) with a **900-second (15-minute) TTL** so homepage requests never block on external HTTP calls. If the upstream feed is unreachable or disabled, it returns the last cached snapshot or safe baseline structure.
  - **8 Instruments Displayed in a `2 × 4` Grid (`.econ-With-ticker` / `.econ-ticker-grid`):**
    1. `GRAM ALTIN` (`code: 'GAU/TRY'`)
    2. `DOLAR` (`code: 'USD/TRY'`)
    3. `EURO` (`code: 'EUR/TRY'`)
    4. `STERLİN` (`code: 'GBP/TRY'`)
    5. `BIST 100` (`code: 'XU100'`)
    6. `BITCOIN` (`code: 'BTC/USD'`)
    7. `GRAM GÜMÜŞ` (`code: 'XAG/TRY'`)
    8. `BRENT` (`code: 'BRENT/USD'`)
  - Each ticker cell displays:
    - Left: Instrument label (`.econ-sym`) and formatted price (`.econ-val`, tabular numerals `font-variant-numeric: tabular-nums`).
    - Right: Direction badge (`.econ-chg.up` in green `#dcfce7 / #15803d` with `▲`, `.econ-chg.down` in red `#fee2e2 / #b91c1c` with `▼`, or `.econ-chg.flat` in slate).

---

### 4.3. Block R3: Event Agenda (`Etkinlik Ajandası`)
- **Template:** `templates/amida/index/mod_announcements/sidebar_event_agenda.twig`
- **External JS Controller:** `templates/amida/static/js/event-agenda.js`
- **Backend Data Call (`mod/index/index.php` & `mod/index/helper/mod_announcements.helper.php`):**
  - Calls `self::_latest_announcements($this->template, 10, 'id,name,summary,image,created_at');` in `mod/index/index.php`.
  - Enhance `mod_announcements_helper::_latest_announcements()` so each item includes:
    - `image_url`: `/static/mod_announcements/thumb/{image}` (or fallback placeholder `/templates/amida/images/no-image-poster.svg` if `image` is empty).
    - `url`: `/{{ lang.lang }}/announcements/view/{{ id }}`
    - `formatted_date`: Localized date/time string from `created_at`.
- **Visual & Interactive Architecture:**
  - **Header Bar:** Calendar icon + `Etkinlik Ajandası` title on the left; `Tümü` pill (`/tr/announcements`), `<` (`.event-agenda-prev`), and `>` (`.event-agenda-next`) buttons on the right.
  - **Left Poster Preview (`126 × 164 px`, `10:13` portrait ratio):**
    - Displays the active event's poster (`300 × 390 px` source from `/static/mod_announcements/thumb/`).
    - Bottom gradient overlay showing the active event's date badge and title.
  - **Right 5-Item Event List:**
    - Displays 5 items per page.
    - Clicking or hovering an item (handled cleanly in `templates/amida/static/js/event-agenda.js` via `data-*` attributes, zero inline JS) updates the active `.is-active` state and swaps the left poster preview image, title, link, and date.
  - **Bottom Pagination Dots:**
    - Allows switching between pages of 5 events (e.g., items 1–5 on page 1, items 6–10 on page 2).

---

## 5. Stylesheet & Asset Organization (Strict Rule 2 Compliance)

To comply with **Rule 2 (Strict Code Separation Mandate)**, all styles and scripts are externalized:
1. **CSS File:** `templates/amida/static/css/homepage-sidebar.css`
   - Loaded in `templates/amida/index/index.twig` (or `header.twig`).
   - Houses all layout rules for `.sidebar-sister-block`, `.lead-card`, `.sub-card`, `.econ-ticker-wrap`, `.econ-ticker-grid`, and `.event-agenda-block`.
   - Includes full Dark Mode support (`html[data-bs-theme="dark"]` / `body.dark-mode`) matching existing Amida theme variables.
2. **JS File:** `templates/amida/static/js/event-agenda.js`
   - Loaded via `defer` in `templates/amida/index/index.twig`.
   - Manages item selection (`.event-item[data-event-poster]`), `<`/`>` button paging, and pagination dot switching without any `onclick` attributes or inline `<script>` tags.

---

## 6. Localization Keys (`mod/index/lang/*.php`)

Add the following descriptive English keys to `mod/index/lang/en.php` and synchronize `tr.php`, `ku.php`, and `de.php`:

```php
$lang['index']['sidebar'] = [
    'world_middle_east_title' => 'World & Middle East',
    'economy_title' => 'Economy',
    'markets_title' => 'Markets',
    'markets_live_note' => '15m delayed',
    'event_agenda_title' => 'Event Agenda',
    'view_all' => 'All',
    'previous_events' => 'Previous events',
    'next_events' => 'Next events',
    'market_gold_gram' => 'GRAM GOLD',
    'market_usd' => 'DOLLAR',
    'market_eur' => 'EURO',
    'market_gbp' => 'STERLING',
    'market_bist100' => 'BIST 100',
    'market_bitcoin' => 'BITCOIN',
    'market_silver_gram' => 'GRAM SILVER',
    'market_brent' => 'BRENT',
];
```

- **Turkish (`mod/index/lang/tr.php`):**
  - `'world_middle_east_title' => 'Dünya & Ortadoğu'`
  - `'economy_title' => 'Ekonomi'`
  - `'markets_title' => 'Piyasalar'`
  - `'markets_live_note' => 'Canlı / 15dk'`
  - `'event_agenda_title' => 'Etkinlik Ajandası'`
  - `'view_all' => 'Tümü'`
  - `'market_gold_gram' => 'GRAM ALTIN'`, `'market_usd' => 'DOLAR'`, `'market_eur' => 'EURO'`, `'market_gbp' => 'STERLİN'`, `'market_bist100' => 'BIST 100'`, `'market_bitcoin' => 'BITCOIN'`, `'market_silver_gram' => 'GRAM GÜMÜŞ'`, `'market_brent' => 'BRENT'`
- **Kurmanji Kurdish (`mod/index/lang/ku.php`):**
  - `'world_middle_east_title' => 'Cîhan & Rojhilata Navîn'`
  - `'economy_title' => 'Aborî'`
  - `'markets_title' => 'Piyase'`
  - `'markets_live_note' => 'Zindî / 15dk'`
  - `'event_agenda_title' => 'Ajandaya Çalakiyan'`
  - `'view_all' => 'Hemû'`

---

## 7. Verification & Quality Gates
1. **PHP Syntax Validation:**
   ```bash
   php -l mod/index/index.php
   php -l mod/index/helper/mod_news.helper.php
   php -l mod/index/helper/mod_announcements.helper.php
   php -l mod/index/helper/market.helper.php
   php -l mod/index/lang/en.php mod/index/lang/tr.php mod/index/lang/ku.php mod/index/lang/de.php
   ```
2. **Code Separation Guard:**
   ```bash
   python3 tools/code_separation_guard.py
   ```
3. **Localization & Boundary Guards:**
   ```bash
   python3 tools/i18n_guard.py
   python3 tools/module_boundary_guard.py
   ```
