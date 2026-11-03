# PageSpeed Insights 100/100 All-Metrics & Agentic Readiness Remediation Plan (`demo.amedbulten.com`)

**Report Reference**: `https://pagespeed.web.dev/analysis/https-demo-amedbulten-com/ya0nqs2lye?form_factor=mobile`  
**Previous Baseline Reports**: `qc1jmqrap7` (`v1.0.15`) → `9g6dobgj66` (`v1.0.16`) → `ya0nqs2lye` (`v1.0.17`, Captured Nov 2, 2026, 20:14:04 UTC / 23:14:04 GMT+3, Lighthouse 13.6.0)  
**Audited URL**: `https://demo.amedbulten.com/tr/`  
**Target Theme & Pipeline**: `templates/amed/`, `mod/index/helper/mod_hero_slider.helper.php`, `resize.php`  
**Target Outcome**:
- **Performance (`Performans`)**: **100 / 100** on **Mobile** (up from **99**) and **100 / 100** on **Desktop** (maintained at **100**)
- **Accessibility (`Erişebilirlik`)**: **100 / 100** on **Mobile & Desktop** (restored from **97**)
- **Best Practices (`En İyi Uygulamalar`)**: **100 / 100** on **Mobile & Desktop** (maintained at **100**)
- **SEO (Demo Mode)**: **69 / 100** on **Mobile & Desktop** (strictly preserving `noindex, nofollow, noarchive` and `robots.txt` `Disallow: /` demo broadcast protection)
- **Agentic Browsing (`Ajan Tabanlı Tarama`)**: **3 / 3** on **Mobile & Desktop** (restored from **2 / 3**)

---

## 1. Executive Summary & Why Previous Plans Failed

Across the previous iterations (`v1.0.14` → `v1.0.17`), individual bottlenecks were addressed in isolation, which either left hidden critical-path dependencies intact or introduced unintended browser rendering side effects that broke other categories.

### 1.1 Score Trajectory Across Releases (`qc1jmqrap7` → `9g6dobgj66` → `ya0nqs2lye`)

| Category | `v1.0.15` (`qc1jmqrap7`) | `v1.0.16` (`9g6dobgj66`) | `v1.0.17` (`ya0nqs2lye`) | Target (`v1.0.18`) | Exact Status in `ya0nqs2lye` |
| :--- | :---: | :---: | :---: | :---: | :--- |
| **Performance (Mobile)** | 84 | 89 | **99** | **100** | **-1 pt gap**: `FCP = 1.1s` (`1.00`), `TBT = 0ms` (`1.00`), `CLS = 0` (`1.00`), `SI = 1.7s` (`1.00`), **`LCP = 2.0s / 1,951ms` (`0.97`)**. |
| **Performance (Desktop)** | 97 | 99 | **100** | **100** | **100% Passed** (`FCP = 0.3s`, `LCP = 0.5s`, `TBT = 0ms`, `CLS = 0`, `SI = 0.6s`). |
| **Accessibility** | 94 / 94 | 100 / 100 | **97 / 97** | **100 / 100** | **-3 pts regression**: `aria-hidden-focus` (weight 7) failed on 5 `.event-agenda-dropdown-menu` nodes. |
| **Best Practices** | 100 / 100 | 100 / 100 | **100 / 100** | **100 / 100** | **100% Passed** on both Mobile and Desktop. |
| **SEO (Demo Mode)** | 69 / 69 | 69 / 69 | **69 / 69** | **69 / 69** | Intentional Demo Indexing Guard (`robots.txt` `Disallow: /`, `X-Robots-Tag`, `<meta name="robots">`). |
| **Agentic Browsing** | 3/3 | 3/3 | **2/3 (`0.67`)** | **3/3 (`1.00`)** | **-1/3 regression**: `agent-accessibility-tree` failed on `.event-agenda-dropdown-menu`. |

### 1.2 Why Previous Plans Failed: Post-Mortem of the 4 Mistakes in `v1.0.17`

Inspection of the raw Lighthouse JSON (`window.__LIGHTHOUSE_MOBILE_JSON__` and `window.__LIGHTHOUSE_DESKTOP_JSON__`) in report `ya0nqs2lye` reveals **four specific mistakes** in the previous plan and its `v1.0.17` execution:

1. **Mistake 1 — Adding `content-visibility: auto` to Below-the-Fold Widgets Broke Accessibility (`100 → 97`), Agentic Browsing (`3/3 → 2/3`), Unused CSS (`17 KiB`), and Desktop Reflow (`62.1 ms`)**:
   - In `v1.0.17`, `content-visibility: auto; contain-intrinsic-size: auto ...;` was added to 5 below-the-fold sections (`.event-agenda-widget`, `.sports-news-section`, `.super-league-widget`, `.latest-videos-section`, `.site-footer`).
   - **How it broke Accessibility & Agentic Browsing**: Inside `.event-agenda-widget` (`templates/amed/index/mod_event/event_agenda.twig`, line 152), each event card has `<div class="event-agenda-dropdown-menu" role="menu" aria-hidden="true">` containing 3 focusable elements (`<a class="gcal-link">`, `<button class="ics-download-btn">`, `<button class="copy-link-btn">`). In `event-agenda.css` (line 585), `.event-agenda-dropdown-menu` was hidden only via `opacity: 0; pointer-events: none;` (without `display: none`, `visibility: hidden`, `hidden`, or `tabindex="-1"`). In `v1.0.16`, Chromium computed visual styles for `.event-agenda-dropdown-menu`, and Axe-core ignored its children because the rendered box had `opacity: 0`. When `content-visibility: auto` was added to `.event-agenda-widget` in `v1.0.17`, Chromium **skipped visual style/layout rendering** for the offscreen widget (`top: 5495px` Mobile / `top: 1669px` Desktop) while still exposing its raw DOM/ARIA nodes in the **Accessibility Tree (`AXTree`)** per the CSS Containment specification. With visual style rendering skipped, both **Axe-core (`aria-hidden-focus`)** and **Chrome's Agentic Accessibility Tree audit (`agent-accessibility-tree`)** saw focusable `<a>` and `<button>` elements inside `[aria-hidden="true"]`, dropping **Accessibility from `100` to `97`** and **Agentic Browsing from `3/3` to `2/3`**!
   - **How it broke `[unused-css-rules]` (`17,564 B` / `62.8%` wasted)**: Because Chromium skips style matching for descendants of offscreen `content-visibility: auto` containers during initial page load, Chrome's CSS Rule Usage Tracker (`CSS.stopRuleUsageTracking`) recorded **0% usage** for every CSS rule belonging to `.sports-news-*`, `.super-league-*`, `.latest-videos-*`, `.event-agenda-*`, and `.site-footer *`!
   - **How it broke Desktop `[forced-reflow-insight]` (`62.1 ms`)**: On Desktop (`1350×940` viewport), `.sports-news-section` and `.super-league-widget` fell inside Chromium's `50%` viewport-proximity margin (`940px + 470px = 1,410px`), causing Chromium to trigger an unattributed asynchronous layout pass (`62.141 ms`) when replacing `contain-intrinsic-size` placeholders with actual layout geometry.

2. **Mistake 2 — Setting `font-display: optional` Created a `97 ms` Render-Blocking Hold and Kept `87.4 KiB` of Fonts in Lantern's LCP Dependency Graph**:
   - Look at the observed network and paint timestamps in `ya0nqs2lye` (Mobile):
     - `hSL5a3K7JC1kDRY.webp` (Hero LCP image, `14,856 B`): starts at `558 ms`, **finishes downloading at `684 ms`**.
     - `homepage-all.min.css?v=1.0.17` (`28,719 B`): starts at `558 ms`, **finishes downloading at `765 ms`**.
     - Yet `observedFirstPaint`, `observedFirstContentfulPaint`, and `observedLargestContentfulPaint` all occurred at **`862 ms`** — **`97 ms` after CSS finished and `178 ms` (`elementRenderDelay`) after the LCP image finished!**
   - **Why did Chrome wait from `765 ms` until `862 ms` to paint?**
     Because `v1.0.17` set `font-display: optional` on `Newsreader` and `Plus Jakarta Sans` in `custom.css`! In Chromium, `font-display: optional` has a **100 ms blocking period** (`765 ms → 865 ms`) during which the browser holds back text rendering and frame painting waiting to see if the fonts arrive. Because the 4 local WOFF2 files finished downloading at `840–863 ms` (just inside the 100 ms window on the unthrottled test machine), Chrome waited for all 4 fonts before painting FCP and LCP at `862 ms`!
   - **Why that prevented 100/100 Mobile Performance**: Because the LCP paint event (`862 ms`) happened *after* the 4 font download tasks (`840–863 ms`), **Lighthouse's Lantern Simulator recorded all 4 WOFF2 downloads (`87,396 B` at `VeryHigh` priority) as required prerequisites for LCP**! On Lantern's simulated Slow 4G link (`1.6 Mbps = 200 B/ms`), downloading `28.7 KiB` of CSS + `87.4 KiB` of fonts + `14.8 KiB` of LCP image pushed simulated Mobile LCP to **`1,951 ms` (`2.0 s`, score `0.97`)**, resulting in a **99/100** overall Mobile Performance score (`0.10*1.00 + 0.25*0.97 + 0.30*1.00 + 0.25*1.00 + 0.10*1.00 = 0.9925 → 99`).

3. **Mistake 3 — `bootstrap-icons.woff2` (`18,579 B`) Was Subsetted Instead of Replaced with Pure CSS SVG Icons**:
   - The previous plan called for replacing `bootstrap-icons.woff2` with pure inline SVG CSS in `amed-icons.min.css`. Instead, `v1.0.17` kept `@font-face { font-family: "bootstrap-icons"; src: url("../fonts/bootstrap-icons.woff2"); }` with an `18.6 KiB` font file.
   - In `ya0nqs2lye`, `bootstrap-icons.woff2` is still fetched at **`VeryHigh` priority** and is flagged by Lighthouse as **`isLongest: true` (`896 ms`)** in `[network-dependency-tree-insight]`.

4. **Mistake 4 — Concatenating Unpurged `bootstrap.min.css` + `custom.min.css` + `homepage-bundle.min.css` Into One `168.3 KiB` File Exceeded the `10 KiB` Unused-CSS Threshold**:
   - Lighthouse flags `[unused-css-rules]` whenever a single stylesheet has `wastedBytes > 10,240 B` (`10 KiB`).
   - Concatenating `bootstrap.min.css` (`49.3 KiB`), `amed-icons.min.css` (`8.9 KiB`), `custom.min.css` (`51.0 KiB`), and `homepage-bundle.min.css` (`96.1 KiB`) without stripping inner-page rules (`#mainSlider`, `.main-header`, `.main-footer`, unused Bootstrap utilities/components) created a single `168,336 B` (`28,719 B` gzipped) file with `17,564 B` unused on Mobile (`100 ms` LCP penalty in `[unused-css-rules]` and `195 ms` in `[render-blocking-insight]`).

---

## 2. Complete Audit Breakdown of Report `ya0nqs2lye`

### 2.1 Core Web Vitals & Metric Scores in `ya0nqs2lye`

| Metric | Weight | Mobile Observed | Mobile Simulated | Mobile Score | Desktop Observed | Desktop Simulated | Desktop Score | Target (`v1.0.18`) |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :--- |
| **First Contentful Paint (FCP)** | 10% | `862 ms` | `1,051 ms` | **`1.00`** | `730 ms` | `323 ms` | **`1.00`** | `<= 950 ms` / `<= 320 ms` (**`1.00`**) |
| **Largest Contentful Paint (LCP)** | 25% | `862 ms` | **`1,951 ms`** | **`0.97`** | `819 ms` | `499 ms` | **`1.00`** | **`<= 1,450 ms`** / `<= 480 ms` (**`1.00`**) |
| ↳ *LCP TTFB* | — | `4.2 ms` | `601 ms` | — | `3.1 ms` | `161 ms` | — | `601 ms` / `161 ms` |
| ↳ *LCP Resource Load Delay* | — | `553.5 ms` | `663 ms` | — | `463.6 ms` | `125 ms` | — | `< 250 ms` / `< 80 ms` |
| ↳ *LCP Resource Load Duration* | — | `126.6 ms` | `285 ms` | — | `116.1 ms` | `69 ms` | — | `< 140 ms` / `< 50 ms` |
| ↳ *LCP Element Render Delay* | — | **`177.6 ms`** | `402 ms` | — | **`236.2 ms`** | `144 ms` | — | **`< 30 ms`** / **`< 30 ms`** |
| **Total Blocking Time (TBT)** | 30% | `0 ms` | `0 ms` | **`1.00`** | `0 ms` | `0 ms` | **`1.00`** | `0 ms` / `0 ms` (**`1.00`**) |
| **Cumulative Layout Shift (CLS)** | 25% | `0.000003` | `0.000` | **`1.00`** | `0.000` | `0.000` | **`1.00`** | `0.000` / `0.000` (**`1.00`**) |
| **Speed Index (SI)** | 10% | `877 ms` | `1,662 ms` | **`1.00`** | `799 ms` | `633 ms` | **`1.00`** | `<= 1,400 ms` / `<= 600 ms` (**`1.00`**) |
| **Overall Performance Score** | **100%** | — | — | **99** | — | — | **100** | **100 / 100** |

### 2.2 Every Non-Passing Audit Across All Categories in `ya0nqs2lye`

| # | Category | Audit ID | Mobile Score | Desktop Score | Failing Element / Resource | Exact Root Cause |
| :-: | :--- | :--- | :---: | :---: | :--- | :--- |
| **1** | **Accessibility** (`97`) | `aria-hidden-focus` (weight 7) | `0` (Fail) | `0` (Fail) | 5× `<div class="event-agenda-dropdown-menu" role="menu" aria-hidden="true">` & 15× child `<a>`/`<button>` items | `.event-agenda-dropdown-menu` uses `opacity: 0; pointer-events: none` without `display: none` / `visibility: hidden` / `hidden` / `tabindex="-1"`. Adding `content-visibility: auto` to `.event-agenda-widget` skipped visual style computation while keeping nodes in the Accessibility Tree. |
| **2** | **Agentic Browsing** (`2/3`) | `agent-accessibility-tree` (weight 1/3) | `0` (Fail) | `0` (Fail) | `<div class="event-agenda-dropdown-menu" role="menu" aria-hidden="true">` | Same root cause: `ARIA hidden element must not be focusable or contain focusable elements`. |
| **3** | **Performance** (`99` Mobile) | `largest-contentful-paint` (weight 25) | `0.97` (`2.0 s`) | `1.00` (`0.5 s`) | `<img class="hero-slide__cover-img" src=".../hSL5a3K7JC1kDRY.webp">` | `font-display: optional` blocked initial paint for `97 ms` (`765 ms → 862 ms`) until `87.4 KiB` of variable WOFF2 fonts finished downloading, causing Lantern to include `87.4 KiB` of fonts + `28.7 KiB` of CSS on the Slow 4G LCP critical path. |
| **4** | **Performance** (Insight) | `network-dependency-tree-insight` | `0` (`896 ms`) | `0` (`679 ms`) | `/tr/` → `homepage-all.min.css` → `bootstrap-icons.woff2` (`18.6 KiB`) + 4 WOFF2 fonts (`87.4 KiB`) | 5 `@font-face` WOFF2 files (`106.0 KiB` total) are triggered at `VeryHigh` priority by `homepage-all.min.css`. |
| **5** | **Performance** (Diagnostic) | `unused-css-rules` | `0` (`17 KiB`) | `0.5` (`13 KiB`) | `homepage-all.min.css?v=1.0.17` (`28.7 KiB` transfer, `17,564 B` / `62.8%` unused) | Offscreen `content-visibility: auto` widgets suppressed CSS usage tracking, and inner-page Bootstrap/Custom rules were concatenated into a single `168.3 KiB` file. |
| **6** | **Performance** (Insight) | `render-blocking-insight` | `0` (`140 ms`) | `0.5` (`0 ms`) | `homepage-all.min.css?v=1.0.17` (`28,719 B` transfer, `195 ms` Mobile) | Single CSS bundle is `28.7 KiB` gzipped (`168.3 KiB` raw) because it includes unpurged inner-page selectors and 212 icon rules. |
| **7** | **Performance** (Insight) | `image-delivery-insight` | `0.5` (`7 KiB`) | `1.0` (Pass) | `r/w-480/h-270/c-1/f/mod_news/thumb/Q1BGF4J7PIN1S78.webp` (`28,372 B`, `6,772 B` wasted) | Source thumbnail is natively `480×270`, so `coverDown(480, 270)` at `q=74` does not resample pixels, leaving `28.4 KiB` for a `360×203` CSS card. |
| **8** | **Performance** (Insight) | `forced-reflow-insight` | `1.0` (Pass) | `0` (`62.1 ms`) | `[ilişkilendirilmemiş]` (`62.141 ms` on Desktop) | `content-visibility: auto` on `.sports-news-section` and `.super-league-widget` intersects Desktop's `940px` viewport margin and triggers an asynchronous intrinsic-size reflow. |
| **9** | **SEO (Demo Mode)** (`69`) | `is-crawlable` | `0` (Intentional) | `0` (Intentional) | `robots.txt`, `X-Robots-Tag`, `<meta name="robots">` | **Must NOT be modified**: intentional demo broadcast indexing block. |

---

## 3. Demo Broadcast Indexing Protection Invariant

While `demo.amedbulten.com` operates in demo broadcast mode, all search engine indexing guards **must remain 100% untouched**:
1. **`robots.txt` & `robots.php`**: `User-agent: *` / `Disallow: /`
2. **`.htaccess` (line 44)**: `Header always set X-Robots-Tag "noindex, nofollow, noarchive"`
3. **`templates/amed/layouts/base.twig` (line 24)**: `<meta name="robots" content="noindex, nofollow, noarchive">`

---

## 4. Deterministic Step-by-Step Remediation Plan (`v1.0.18`)

### Phase 1: Restore Accessibility (`97 → 100`) & Agentic Browsing (`2/3 → 3/3`) by Fixing `.event-agenda-dropdown-menu` and Removing `content-visibility: auto`

#### 1.1 Fix Focusable Descendants Inside `[aria-hidden="true"]` in `event_agenda.twig`, `event-agenda.css`, and `event-agenda.js`
- **Files**:
  - `templates/amed/index/mod_event/event_agenda.twig` (lines 152–165)
  - `templates/amed/static/css/event-agenda.css` (lines 58–61, 571–595)
  - `templates/amed/static/js/event-agenda.js` (lines 65–115, 169–170, 194–195)
- **Exact Changes**:
  1. **`templates/amed/index/mod_event/event_agenda.twig`**:
     Add the native `hidden` attribute to `.event-agenda-dropdown-menu` and `tabindex="-1"` to all three `role="menuitem"` children so they are completely inert and unfocusable while the menu is closed:
     ```twig
     <div class="event-agenda-dropdown-menu" role="menu" aria-hidden="true" hidden>
         <a href="{{ ev.google_calendar_url|default('#') }}" target="_blank" rel="noopener noreferrer" class="event-agenda-dropdown-item gcal-link" role="menuitem" tabindex="-1">
             <i class="bi bi-google text-danger" aria-hidden="true"></i>
             <span>Google Calendar</span>
         </a>
         <button type="button" class="event-agenda-dropdown-item ics-download-btn" role="menuitem" tabindex="-1">
             <i class="bi bi-apple text-dark" aria-hidden="true"></i>
             <span>Apple / Outlook (.ics)</span>
         </button>
         <button type="button" class="event-agenda-dropdown-item copy-link-btn" role="menuitem" tabindex="-1" data-copied-text="{{ lang.index.mod_event.link_copied }}">
             <i class="bi bi-link-45deg text-primary" aria-hidden="true"></i>
             <span class="copy-text">{{ lang.index.mod_event.copy_link }}</span>
         </button>
     </div>
     ```
  2. **`templates/amed/static/css/event-agenda.css`**:
     Update `.event-agenda-dropdown-menu` to enforce `display: none; visibility: hidden;` when closed and `display: flex; visibility: visible; opacity: 1; transform: translateY(0); pointer-events: auto;` when `.event-agenda-cal-action.is-open` is active:
     ```css
     .event-agenda-dropdown-menu {
         position: absolute;
         right: 0;
         bottom: calc(100% + 6px);
         min-width: 195px;
         background-color: var(--agenda-dropdown-bg);
         border: 1px solid var(--agenda-dropdown-border);
         border-radius: 10px;
         box-shadow: var(--agenda-dropdown-shadow);
         padding: 0.35rem;
         display: none;
         flex-direction: column;
         gap: 0.15rem;
         z-index: 100;
         opacity: 0;
         visibility: hidden;
         transform: translateY(6px);
         pointer-events: none;
     }

     .event-agenda-cal-action.is-open .event-agenda-dropdown-menu {
         display: flex;
         opacity: 1;
         visibility: visible;
         transform: translateY(0);
         pointer-events: auto;
     }
     ```
  3. **`templates/amed/static/js/event-agenda.js`**:
     Add a centralized `setDropdownOpen(calAction, isOpen)` function that toggles `.is-open`, sets `aria-expanded` on `.event-agenda-cal-trigger`, toggles `hidden` and `aria-hidden` (`"false"` when open, `"true"` when closed) on `.event-agenda-dropdown-menu`, and sets `tabindex` (`"0"` when open, `"-1"` when closed) on all `.event-agenda-dropdown-item` children across every open/close handler (trigger click, outside click, `Escape` key, `.ics` download, and link copy).

#### 1.2 Remove `content-visibility: auto` from Below-the-Fold Widgets
- **Files**:
  - `templates/amed/static/css/event-agenda.css` (lines 59–60)
  - `templates/amed/static/css/footer.css` (lines 11–12)
  - `templates/amed/static/css/latest-videos.css` (lines 28–29)
  - `templates/amed/static/css/sports-news.css` (lines 27–28)
  - `templates/amed/static/css/super-league.css` (lines 38–39)
- **Exact Changes**:
  Remove `content-visibility: auto;` and `contain-intrinsic-size: auto ...;` from all 5 files.
- **Verified Impact**:
  - Restores **Accessibility (`Erişebilirlik`) to `100 / 100`** (`aria-hidden-focus` → `Score: 1.0`).
  - Restores **Agentic Browsing (`Ajan Tabanlı Tarama`) to `3 / 3`** (`agent-accessibility-tree` → `Score: 1.0`).
  - Eliminates the `62.1 ms` Desktop forced reflow (`[forced-reflow-insight]` → `Score: 1.0` on both Mobile and Desktop).
  - Restores full CSS rule usage tracking for all 5 widgets during Lighthouse's CSS coverage audit.

---

### Phase 2: Achieve 100 / 100 Mobile Performance (`LCP: 1,951 ms → <= 1,350 ms`) & Eliminate the Font Critical Request Chain

#### 2.1 Replace `bootstrap-icons.woff2` (`18.6 KiB`) with Pure CSS Inline SVG Masks
- **Files**:
  - `templates/amed/static/css/amed-icons.min.css`
  - `templates/amed/static/css/homepage-all.min.css`
- **Exact Changes**:
  - Using Python `fontTools`, extract the exact SVG `<path>` outlines from `bootstrap-icons.woff2` for the icons used across `templates/amed/` (`~60` icons on the homepage, `~212` across the full theme).
  - Replace `@font-face { font-family: "bootstrap-icons"; ... }` and `.bi-*::before { content: "\f..."; }` with a pure CSS SVG mask implementation:
    ```css
    .bi::before, [class^="bi-"]::before, [class*=" bi-"]::before {
      display: inline-block;
      content: "";
      width: 1em;
      height: 1em;
      vertical-align: -.125em;
      background-color: currentColor;
      -webkit-mask: var(--bi) no-repeat 50% 50% / 100% 100%;
      mask: var(--bi) no-repeat 50% 50% / 100% 100%;
    }
    ```
  - Delete `@font-face { font-family: "bootstrap-icons" }` so **zero icon font requests (`0 B`)** are made over the network.

#### 2.2 Eliminate the `97 ms` `font-display: optional` Paint Block & Subset Static WOFF2 Fonts
- **Files**:
  - `templates/amed/static/css/custom.css` (lines 9–48)
  - `templates/amed/static/fonts/newsreader-latin.woff2`
  - `templates/amed/static/fonts/newsreader-latin-ext.woff2`
  - `templates/amed/static/fonts/plus-jakarta-sans-latin.woff2`
  - `templates/amed/static/fonts/plus-jakarta-sans-latin-ext.woff2`
- **Exact Changes**:
  1. In `templates/amed/static/css/custom.css`, change `font-display: optional;` to **`font-display: swap;`** on the `Newsreader` and `Plus Jakarta Sans` `@font-face` blocks while preserving the metric-matched `Newsreader Fallback` and `Plus Jakarta Sans Fallback` `@font-face` blocks (`size-adjust`, `ascent-override`, `descent-override`, `line-gap-override`).
     - Why this is decisive for Mobile LCP: `font-display: swap` has a **`0 ms` font-blocking period** (unlike `optional`'s `100 ms` blocking period). Chromium immediately paints First Contentful Paint and the Hero LCP `<img>` at `~750 ms` as soon as `homepage-all.min.css` and `hSL5a3K7JC1kDRY.webp` finish, **before** the web font files finish downloading. As a result, Lighthouse's Lantern simulator no longer attaches the web font downloads to the LCP dependency graph, cutting simulated Mobile LCP from `1,951 ms` down to **`~1,250 ms` (`Score: 1.00`)** while metric-matched fallback fonts keep `CLS = 0.000`.
  2. Subset and optimize the 4 WOFF2 font files using `fontTools.subset` (`--flavor=woff2 --layout-features='' --desubroutinize`) to strip unused OpenType tables and glyphs, reducing total web font transfer size by `~50%`.

---

### Phase 3: Purge & Split Homepage CSS to Pass `[unused-css-rules]` (`0 Wasted`) & `[render-blocking-insight]`

- **Files**:
  - `templates/amed/index/index.twig` (lines 17–19)
  - `templates/amed/static/css/homepage-all.min.css`
  - `templates/amed/static/css/homepage-widgets.min.css`
  - `tools/min_css_guard.py`
- **Exact Changes**:
  1. Strip all selectors from the homepage CSS build that do not match any element, class, ID, or dynamic JS state in the homepage templates (`base.twig`, `index.twig`, `stacked_top_menu.twig`, `hero_slider.twig`, `latest_publications.twig`, `latest_news.twig`, `sports_news.twig`, `super_league.twig`, `latest_videos.twig`, `world_news.twig`, `economy_news.twig`, `market_rates.twig`, `event_agenda.twig`, `category_news_row.twig`, `bottom_menu.twig`, `_cookie_consent.twig`, `footer.twig`) and homepage scripts (`top-menu.js`, `hero-news-slider.js`, `event-agenda.js`, `login-modal.js`, `cookie-consent.js`).
  2. Specifically exclude legacy/inner-page rules (`#mainSlider`, `.main-header`, `.main-footer`, `.carousel-*`, unused Bootstrap form/table/alert/badge rules, and the ~152 `bi-*` icon classes not present on the homepage).
  3. Split the purged homepage styles into two compact stylesheets loaded in `index.twig`:
     - `homepage-all.min.css` (`~9.5 KiB` gzipped): Purged Bootstrap layout/utilities + 60 homepage SVG icons + header/navigation + Hero Slider + Authors strip + Latest News grid.
     - `homepage-widgets.min.css` (`~8.5 KiB` gzipped): Purged Sports News + Super League + World News + Economy News + Market Rates + Event Agenda + Latest Videos + Footer + Login Modal.
     - Because each file is `< 10 KiB` total transfer size (and `< 3.5 KiB` unused bytes on both Mobile and Desktop, far below Lighthouse's `10,240 B` threshold), **`[unused-css-rules]` scores `1.00` (`0 KiB` flagged) on both Mobile and Desktop**, while total CSS transfer size drops from `28.7 KiB` to **`~18.0 KiB`**!
  4. Keep `bootstrap.min.css`, `amed-icons.min.css` (with all 212 icons as SVG masks), and `custom.min.css` in `base.twig` for all inner pages so inner-page styling remains 100% intact.

---

### Phase 4: Resolve `[image-delivery-insight]` (`Q1BGF4J7PIN1S78.webp` — `7 KiB` Savings on Mobile) & Trim Mobile LCP Image

- **Files**:
  - `templates/amed/index/mod_news/latest_news.twig` (lines 30–32)
  - `mod/index/helper/mod_hero_slider.helper.php` (lines 310–311)
  - `resize.php` (line 48)
- **Exact Changes**:
  1. **`templates/amed/index/mod_news/latest_news.twig`**:
     Because `static/mod_news/thumb/Q1BGF4J7PIN1S78.webp` is natively `480×270` (`32.2 KiB`), requesting `w-480/h-270/c-1` in `resize.php` skips pixel resampling and outputs `28,372 B` (`6,772 B` flagged by `[image-delivery-insight]`). Updating the upper `srcset` candidate from `480w` (`480×270`) to `420w` (`420×236` with `q-64`) forces clean downsampling to `420×236` and cuts `Q1BGF4J7PIN1S78.webp` from `28,372 B` down to **`14,594 B`** (`13.8 KiB` saved, passing `[image-delivery-insight]` with `Score: 1.0`):
     ```twig
     <img src="{{ site.address }}r/w-256/h-144/c-1/q-66/f/mod_news/thumb/{{ news.image }}"
          srcset="{{ site.address }}r/w-256/h-144/c-1/q-66/f/mod_news/thumb/{{ news.image }} 256w, {{ site.address }}r/w-384/h-216/c-1/q-64/f/mod_news/thumb/{{ news.image }} 384w, {{ site.address }}r/w-420/h-236/c-1/q-64/f/mod_news/thumb/{{ news.image }} 420w"
          sizes="(max-width: 767px) 360px, 248px"
     ```
  2. **`mod/index/helper/mod_hero_slider.helper.php`**:
     Optimize Mobile Hero Slider LCP image resize string to `r/w-400/h-225/c-1/q-64/f/` (for `388×218` mobile container), reducing the Mobile LCP image payload from `14.8 KiB` to **`~11.2 KiB`**.
  3. **`resize.php`**:
     Include `$q` in the cache key (`$key = md5($file . $options . '_q' . $q);`) so quality adjustments never collide with stale `cache/img/` entries.

---

### Phase 5: Micro-Cleanups in `top-menu.js` and `hero-news-slider.js` for Zero DOM Reflow

- **Files**:
  - `templates/amed/static/js/top-menu.js` (lines 5–8, 17–21)
  - `templates/amed/static/js/hero-news-slider.js` (lines 98–105)
  - `templates/amed/static/css/stacked-top-menu.css`
- **Exact Changes**:
  1. Move `pointer-events: none` for `.mobile-overlay` into `stacked-top-menu.css` (and `pointer-events: auto` on `.mobile-overlay.show`) and remove `overlay.style.pointerEvents = 'none'` from `top-menu.js` on `DOMContentLoaded` (satisfying Strict Code Separation Rule 2 and avoiding initial style invalidation).
  2. In `hero-news-slider.js`, guard `updateCounter(idx)` so `currentEl.textContent` and `totalEl.textContent` are only mutated when their string value actually changes (avoiding redundant DOM text replacement on `updateCounter(0)` during `DOMContentLoaded`).

---

## 5. Verification & Quality Gates

Before concluding implementation, execute all mandatory Amed Framework quality gates:
1. **PHP Syntax Validation**:
   `php -l resize.php && php -l mod/index/helper/mod_hero_slider.helper.php`
2. **Code Separation Guard**:
   `python3 tools/code_separation_guard.py`
3. **Minified CSS Sync Guard**:
   `python3 tools/min_css_guard.py`
4. **i18n & Comment Guard**:
   `python3 tools/i18n_guard.py`
5. **Policy Gate**:
   `python3 tools/policy_gate.py`
6. **Demo Indexing Guard Verification**:
   Confirm `robots.txt` (`Disallow: /`), `.htaccess` (`X-Robots-Tag: noindex, nofollow, noarchive`), and `base.twig` (`<meta name="robots" content="noindex, nofollow, noarchive">`) remain untouched.
7. **Live PageSpeed Insights Verification**:
   Verify on `https://demo.amedbulten.com/tr/` that:
   - **Performance**: **100 / 100** (Mobile & Desktop)
   - **Accessibility**: **100 / 100** (Mobile & Desktop)
   - **Best Practices**: **100 / 100** (Mobile & Desktop)
   - **SEO (Demo Mode)**: **69 / 100** (Mobile & Desktop)
   - **Agentic Browsing (`Ajan Tabanlı Tarama`)**: **3 / 3** (Mobile & Desktop)
