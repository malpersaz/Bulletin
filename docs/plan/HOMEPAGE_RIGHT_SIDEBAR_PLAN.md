# Homepage Right Sidebar Architecture Plan

## 1. Executive Summary & Architectural Objective
This document defines the concrete, repository-grounded implementation plan for the **Homepage Right Sidebar Column** (`#homepageHeroSidebar`, 4-column / `384 px` desktop width) in `AF/Bulletin` (`v1.0.4`).

All specifications reference existing modules, database tables, category slugs, URL routing rules (`config/url.config.php`), image dimensions, and helper classes in `AF/Bulletin` (`mod/index/index.php`, `mod/index/helper/mod_news.helper.php`, `mod/index/helper/mod_announcements.helper.php`, `lib/Cache.class.php`, `lib/HttpClient.class.php`, and `templates/amida/index/index.twig`) and match the finalized wireframe in `docs/plan/homepage-layout-wireframe.html`. No foreign modules are introduced (strictly adhering to `tools/module_boundary_guard.py`).

---

## 2. Sidebar Spatial Geometry & Block Hierarchy

On desktop viewports (`>= 992px`), the homepage container (`1200 px` content width) is split into:
- **Left Main Column (`#homepageHeroMain`):** `800 px` (`col-lg-8`)
- **Column Gap:** `16 px`
- **Right Sidebar Column (`#homepageHeroSidebar`):** `384 px` (`col-lg-4`)

The Right Sidebar contains three vertical blocks stacked with a `16 px` gap:

1. **Block R1 — World & Middle East News (`Dünya & Ortadoğu`):**
   - Exact desktop height: **`450 px`** (horizontally aligned 1:1 with the `800 × 450 px` Hero Slider on the left).
   - Hierarchy: **1 Lead Card** (`160 × 90 px` `16:9` thumbnail + headline + 120-char summary) + **2 Compact Sub-Cards** (`104 × 58.5 px` `16:9` thumbnails + headline + 120-char summary).
2. **Block R2 — Economy News & Market Ticker (`Ekonomi & Piyasalar`):**
   - Top Sub-Block: **Sister News Layout** for the `Ekonomi` category (**1 Lead Card** `160 × 90 px` + **2 Compact Sub-Cards** `104 × 58.5 px`, each with headline + 120-char summary).
   - Bottom Sub-Block: **Professional Market Ticker (`Piyasalar`)** displaying 8 financial instruments in a `2 × 4` compact grid (`GRAM ALTIN`, `DOLAR`, `EURO`, `STERLİN`, `BIST 100`, `BITCOIN`, `GRAM GÜMÜŞ`, `BRENT`).
3. **Block R3 — Event Agenda (`Etkinlik Ajandası`):**
   - Positioned directly below the Economy block (`flex: 1` to align the bottom edge of the right column with the left column).
   - Powered by `mod_announcements` (`mod/mod_announcements/index.php` and `mod/index/helper/mod_announcements.helper.php`).
   - Features a header bar with `{{ lang.index.sidebar.view_all }}` link (`{{ site.saddress }}announcements`) + `<` / `>` navigation buttons, a split body (`grid-template-columns: 148px 1fr; gap: 10px;` — left `300 × 390` portrait poster preview + right 5-item interactive event list), and bottom pagination dots.

---

## 3. Database, Category & Routing Grounding

### 3.1. Category & Module Mapping
| Block | Source Module / View | Category Candidates (`tr` / `ku` / `en` / `de`) | Canonical Listing Route (`config/url.config.php`) | Canonical Detail Route (`config/url.config.php`) | Thumbnail Asset (`16:9` / `10:13`) |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Block R1** | `mod_news.newses` | `['Dünya', 'Ortadoğu', 'Cîhan', 'Rojhilata_Navîn', 'World', 'Middle_East', 'Welt', 'Naher_Osten']` | `{{ site.saddress }}newses/{{ category_seo }}` | `{{ site.saddress }}news/{{ item.seo }}` | `/static/mod_news/thumb/{image}` (`480 × 270 px`) |
| **Block R2** | `mod_news.newses` | `['Ekonomi', 'Aborî', 'Abori', 'Economy', 'Business', 'Wirtschaft']` | `{{ site.saddress }}newses/{{ category_seo }}` | `{{ site.saddress }}news/{{ item.seo }}` | `/static/mod_news/thumb/{image}` (`480 × 270 px`) |
| **Block R3** | `mod_announcements` | All active announcements (`status = 'y'`, `lang = $lang`) | `{{ site.saddress }}announcements` | `{{ site.saddress }}announcement/{{ item.id }}-{{ item.name\|legacy_seo }}` | `/static/mod_announcements/thumb/{image}` (`300 × 390 px`) |

> [!NOTE]
> - Because `/static/mod_news/thumb/{image}` is generated at `480 × 270 px` (`16:9`) by `mod/mod_news/index.php`, both the Lead Card thumbnail (`160 × 90 px`, `16:9`) and the Sub-Card thumbnails (`104 × 58.5 px`, `16:9`) render native thumbnails without aspect-ratio cropping.
> - Similarly, `mod/mod_announcements/index.php` generates `/static/mod_announcements/thumb/{image}` at `300 × 390 px` (`10:13` portrait poster ratio), matching the Event Agenda's left poster preview column (`148 px` width).

---

## 4. Detailed Component Specifications

### 4.1. Block R1: World & Middle East (`Dünya & Ortadoğu`)
- **Template:** `templates/amida/index/mod_news/sidebar_world_news.twig`
- **Twig Invocation:**
  ```twig
  {{ run({"mod": "index", "method": "_world_news", "extra": [3]}) }}
  {% set world_news = ctx_get('world_news') %}
  {% set world_category_seo = ctx_get('world_category_seo') %}
  ```
- **Controller Method (`mod/index/index.php`):**
  ```php
  public function _world_news($t, $limit = 3)
  {
      if (!$this->isFeatureReady('news.latest')) {
          $t->ctx_set('world_news', []);
          $t->ctx_set('world_category_seo', '');
          return;
      }

      $helper = Load::helper('index.mod_news');
      $result = $helper->_category_news(
          ['Dünya', 'Ortadoğu', 'Cîhan', 'Rojhilata_Navîn', 'World', 'Middle_East', 'Welt', 'Naher_Osten'],
          (int) $limit
      );
      $t->ctx_set('world_news', $result['items']);
      $t->ctx_set('world_category_seo', $result['category_seo']);
      unset($helper);
  }
  ```
- **Query Logic (`mod/index/helper/mod_news.helper.php`):**
  - Uses `mod_news_helper::_category_news(array $categoryCandidates, int $limit = 3)` (shared with `_sports_news` and `_economy_news`).
  - Resolves all matching category IDs (including descendants via `getCategoryDescendantIds()`) in `mod_news_category` for `$this->site['lang']` intersected with `$this->getAllowedCategoryIds('mod_news_category', $lang)`.
  - Filters: `status = 'y'`, `lang = $lang`, `slider = 'n'`, and `$this->getAllowedAclCondition('')`.
  - Cleans rows via `$this->_cleanNewsRows()` and computes `summary_short` (120 characters):
    ```php
    $summaryPlain = trim(strip_tags((string) ($row['summary'] ?? '')));
    $row['summary_short'] = mb_strlen($summaryPlain) > 120
        ? mb_substr($summaryPlain, 0, 117) . '...'
        : $summaryPlain;
    ```
- **Visual Hierarchy (`384 × 450 px`):**
  - **Header:** Blue accent indicator (`#0284c7`), title `{{ lang.index.sidebar.world_middle_east_title }}`, and `{{ lang.index.sidebar.view_all }}` link to `{{ site.saddress }}newses/{{ world_category_seo }}`.
  - **Item 1 — Lead Story (`.world-lead`):**
    - Top row (`.world-lead-top`): Left `160 × 90 px` (`16:9`) thumbnail (`width="480" height="270"`, `object-fit: cover`) + Right bold headline (`0.82rem`, 3-line clamp).
    - Bottom row (`.world-lead-summary`): Full-width 120-character summary separated by a subtle dotted top border (`0.70rem`, 2-line clamp).
  - **Items 2 & 3 — Sub Stories (`.world-sub`):**
    - Left `104 × 58.5 px` (`16:9`) thumbnail + Right column with bold headline (`0.74rem`, 2-line clamp) and 120-character summary (`0.68rem`, 2-line clamp).

---

### 4.2. Block R2: Economy & Markets (`Ekonomi & Piyasalar`)
- **Template:** `templates/amida/index/mod_news/sidebar_economy_news.twig`
- **Twig Invocation:**
  ```twig
  {{ run({"mod": "index", "method": "_economy_news", "extra": [3]}) }}
  {{ run({"mod": "index", "method": "_market_ticker", "extra": []}) }}
  {% set economy_news = ctx_get('economy_news') %}
  {% set economy_category_seo = ctx_get('economy_category_seo') %}
  {% set market_rates = ctx_get('market_rates') %}
  ```
- **Controller Methods (`mod/index/index.php`):**
  ```php
  public function _economy_news($t, $limit = 3)
  {
      if (!$this->isFeatureReady('news.latest')) {
          $t->ctx_set('economy_news', []);
          $t->ctx_set('economy_category_seo', '');
          return;
      }

      $helper = Load::helper('index.mod_news');
      $result = $helper->_category_news(
          ['Ekonomi', 'Aborî', 'Abori', 'Economy', 'Business', 'Wirtschaft'],
          (int) $limit
      );
      $t->ctx_set('economy_news', $result['items']);
      $t->ctx_set('economy_category_seo', $result['category_seo']);
      unset($helper);
  }

  public function _market_ticker($t)
  {
      $helper = Load::helper('index.market');
      $t->ctx_set('market_rates', $helper->getRates());
      unset($helper);
  }
  ```
- **Part A — Sister News Layout (`Ekonomi` Category, 3 Items):**
  - Reuses the exact card classes of Block R1 (`.world-list`, `.world-lead`, `.world-sub`) with an emerald accent (`#059669`) and header link to `{{ site.saddress }}newses/{{ economy_category_seo }}`.
- **Part B — Professional Market Ticker (`Piyasalar` `2 × 4` Grid):**
  - **Helper:** `mod/index/helper/market.helper.php` (`class market_helper extends indexBase`, method `public function getRates(): array`)
  - **Caching & Transport Contract:**
    - Uses `Cache` (`lib/Cache.class.php`) with a **900-second (15-minute) TTL** (`Cache::$ttl = 900;`, key `'homepage_market_rates'`) so homepage requests never block on external network calls.
    - Returns a deterministic 8-instrument array (`code`, `label_key`, `value`, `change_pct`, `diff`, `direction` in `['up', 'down', 'flat']`) with safe fallback baseline values when offline or in local development.
  - **8 Instruments Displayed in a `2 × 4` Grid (`.market-widget` / `.market-grid`):**
    1. `market_gold_gram` (`GAU/TRY`)
    2. `market_usd` (`USD/TRY`)
    3. `market_eur` (`EUR/TRY`)
    4. `market_gbp` (`GBP/TRY`)
    5. `market_bist100` (`XU100`)
    6. `market_bitcoin` (`BTC/USD`)
    7. `market_silver_gram` (`XAG/TRY`)
    8. `market_brent` (`BRENT/USD`)
  - Each `.market-item` cell displays:
    - Left (`.market-left`): Localized instrument label (`.market-name`) and formatted price (`.market-val`, `font-variant-numeric: tabular-nums`).
    - Right (`.market-right`): Direction badge (`.market-badge.up` with `▲`, `.market-badge.down` with `▼`, or `.market-badge.flat`) and net change (`.market-diff`).

---

### 4.3. Block R3: Event Agenda (`Etkinlik Ajandası`)
- **Template:** `templates/amida/index/mod_announcements/sidebar_event_agenda.twig`
- **External JS Controller:** `templates/amida/static/js/event-agenda.js`
- **Backend Data Call (`mod/index/index.php` & `mod/index/helper/mod_announcements.helper.php`):**
  - Twig calls:
    ```twig
    {{ run({"mod": "index", "method": "_latest_announcements", "extra": [10, "id,name,summary,image,created_at,acl_id"]}) }}
    {% set latest_announcements = ctx_get('latest_announcements') %}
    ```
  - Upgrade `mod_announcements_helper::_latest_announcements($limit, $select)` (`mod/index/helper/mod_announcements.helper.php` lines 19–29):
    1. Enforce anonymous/user ACL filtering via `$aclCondition = $this->getAllowedAclCondition('')`.
    2. Return `[]` instead of `false` when no records exist.
    3. Normalize each row:
       - Decode HTML entities on `name` and `summary`.
       - Verify image existence under `root_dir . 'static/mod_announcements/thumb/' . $row['image']`; set `thumb_url` to `$this->site['iaddress'] . 'mod_announcements/thumb/' . $row['image']` (or `''` if missing, triggering the CSS icon placeholder).
       - Build canonical detail URL matching `config/url.config.php` (`$url['announcement/(:num)-(:any)'] = 'index/announcement/$1'`):
         ```php
         $row['detail_url'] = $this->site['saddress'] . 'announcement/' . (int) $row['id'] . '-' . Helper::legacy_seo($row['name']);
         ```
       - Format `formatted_date` (`date('d.m.Y', strtotime($row['created_at']))`).
- **Visual & Interactive Architecture (`.sidebar-block-agenda`):**
  - **Header Bar:** Title `{{ lang.index.sidebar.event_agenda_title }}` on the left; `{{ lang.index.sidebar.view_all }}` link (`{{ site.saddress }}announcements`), `<` (`.agenda-btn-prev`), and `>` (`.agenda-btn-next`) buttons on the right.
  - **Split Body (`.agenda-body`, `grid-template-columns: 148px 1fr; gap: 10px;`):**
    - **Left Poster Preview (`.agenda-poster`, `148 px` wide, `10:13` portrait ratio):** Displays the active event's poster (`300 × 390 px` source from `/static/mod_announcements/thumb/`), or a branded calendar placeholder icon if no poster image is attached, plus active event title and date overlay.
    - **Right 5-Item Event List (`.agenda-list`):** Displays 5 items per page (`.agenda-item` with `data-event-index`, `data-event-title`, `data-event-date`, `data-event-url`, `data-event-poster`).
    - Hovering or focusing an `.agenda-item` (handled in `templates/amida/static/js/event-agenda.js` with zero inline JS) updates the `.is-active` state and swaps the left poster preview.
  - **Bottom Pagination Dots (`.agenda-dots`):**
    - Allows switching between pages of 5 events (items 1–5 on page 1, items 6–10 on page 2).

---

## 5. Stylesheet & Asset Organization (Strict Rule 2 Compliance)

To comply with **Rule 2 (Strict Code Separation Mandate)**, all styles and scripts are externalized:
1. **CSS File:** `templates/amida/static/css/homepage-sidebar.css`
   - Loaded in `templates/amida/index/index.twig` (`{% block stylesheets %}`).
   - Houses all layout rules for `.split-800-384` / `#homepageHeroRow`, `.sidebar-sister-block`, `.world-list`, `.world-lead`, `.world-sub`, `.market-widget`, `.market-grid`, `.sidebar-block-agenda`, `.agenda-body`, `.agenda-poster`, `.agenda-list`, and `.sports-news-grid`.
   - Includes full Dark Mode support (`html[data-bs-theme="dark"]` / `body.dark-mode`) and responsive tablet/mobile breakpoints (`< 992px`).
2. **JS File:** `templates/amida/static/js/event-agenda.js`
   - Loaded via `defer` in `templates/amida/index/index.twig` (`{% block js_custom %}`).
   - Manages item selection (`.agenda-item`), `<`/`>` button paging, and pagination dot switching without any `onclick` attributes or inline `<script>` tags.

---

## 6. Localization Keys (`mod/index/lang/*.php`)

To pass `python3 tools/i18n_guard.py` with zero missing-key errors, add `$lang['index']['sidebar']` across **all 9 dictionaries** in `mod/index/lang/{ar,ckb,de,en,fr,ku,sv,tr,zza}.php`:

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
    'no_events' => 'No upcoming announcements.',
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
  - `'world_middle_east_title' => 'Dünya & Ortadoğu'`, `'economy_title' => 'Ekonomi'`, `'markets_title' => 'Piyasalar'`, `'markets_live_note' => 'Canlı / 15dk'`, `'event_agenda_title' => 'Etkinlik Ajandası'`, `'view_all' => 'Tümü'`, `'previous_events' => 'Önceki etkinlikler'`, `'next_events' => 'Sonraki etkinlikler'`, `'no_events' => 'Henüz etkinlik duyurusu bulunmuyor.'`
  - `'market_gold_gram' => 'GRAM ALTIN'`, `'market_usd' => 'DOLAR'`, `'market_eur' => 'EURO'`, `'market_gbp' => 'STERLİN'`, `'market_bist100' => 'BIST 100'`, `'market_bitcoin' => 'BITCOIN'`, `'market_silver_gram' => 'GRAM GÜMÜŞ'`, `'market_brent' => 'BRENT'`
- **Kurmanji Kurdish (`mod/index/lang/ku.php`):**
  - `'world_middle_east_title' => 'Cîhan & Rojhilata Navîn'`, `'economy_title' => 'Aborî'`, `'markets_title' => 'Piyase'`, `'markets_live_note' => 'Zindî / 15dk'`, `'event_agenda_title' => 'Ajandaya Çalakiyan'`, `'view_all' => 'Hemû'`, `'previous_events' => 'Çalakiyên berê'`, `'next_events' => 'Çalakiyên din'`, `'no_events' => 'Hîn daxuyaniya çalakiyê tune ye.'`
  - `'market_gold_gram' => 'ZÊRÊ GRAM'`, `'market_usd' => 'DOLAR'`, `'market_eur' => 'EWRO'`, `'market_gbp' => 'STERLÎN'`, `'market_bist100' => 'BIST 100'`, `'market_bitcoin' => 'BITCOIN'`, `'market_silver_gram' => 'ZÎVÊ GRAM'`, `'market_brent' => 'BRENT'`
- Synchronize equivalent translations in `ckb.php`, `zza.php`, `de.php`, `fr.php`, `ar.php`, and `sv.php`.

---

## 7. Verification & Quality Gates
1. **PHP Syntax Validation:**
   ```bash
   php -l mod/index/index.php
   php -l mod/index/helper/mod_news.helper.php
   php -l mod/index/helper/mod_announcements.helper.php
   php -l mod/index/helper/market.helper.php
   for f in mod/index/lang/*.php; do php -l "$f"; done
   ```
2. **Strict Code Separation Guard (Rule 2):**
   ```bash
   python3 tools/code_separation_guard.py --path templates/amida/index/
   ```
   Must report `0 violations`.
3. **Localization & Boundary Guards:**
   ```bash
   python3 tools/i18n_guard.py --path mod/index/
   python3 tools/module_boundary_guard.py
   ```
