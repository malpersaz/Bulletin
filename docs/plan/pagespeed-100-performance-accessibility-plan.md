# PageSpeed Insights 100% Performance & 100% Accessibility Remediation Plan (`demo.amedbulten.com`)

**Report Reference**: `https://pagespeed.web.dev/analysis/https-demo-amedbulten-com/qc1jmqrap7?form_factor=mobile`  
**Audited URL**: `https://demo.amedbulten.com/tr/` (Lighthouse 13.6.0 — Captured Nov 2, 2026, 20:59:49 GMT+3)  
**Target Theme**: `templates/amed/`, `mod/index/helper/mod_hero_slider.helper.php`, & Shared Asset Pipeline (`resize.php`, `.htaccess`)  
**Target Outcome**:
- **Performance**: **100 / 100** (Mobile & Desktop)
- **Accessibility**: **100 / 100** (Mobile & Desktop)
- **Best Practices**: **100 / 100** (Maintained on Mobile & Desktop)
- **SEO (Demo Mode)**: **69 / 100** (Strictly preserving `noindex, nofollow, noarchive` demo broadcast protection; 100% on all 9 non-indexing SEO audits)
- **Agentic Readiness**: **3 / 3** (Maintained on Mobile & Desktop)

---

## 1. Baseline Lighthouse Audit Matrix (Mobile vs. Desktop)

### 1.1 Category Scores

| Category | Mobile Baseline | Desktop Baseline | Target Score | Root Cause Summary |
| :--- | :---: | :---: | :---: | :--- |
| **Performance** | **84** | **97** | **100** | Mobile loses 13 pts on LCP (`4.1 s`) and 3 pts on TBT (`201 ms`); Desktop loses 3 pts on LCP (`1.2 s`). |
| **Accessibility** | **94** | **94** | **100** | Both lose 6 pts due to `[color-contrast]` (weight 7) and `[target-size]` (weight 7), plus `[label-content-name-mismatch]` (`score: 0`) and `[image-redundant-alt]` (5 items). |
| **Best Practices** | **100** | **100** | **100** | All security, HTTPS, console error, and deprecation audits pass. |
| **SEO** | **69** | **69** | **69 (Demo)** | 9 of 10 audits pass (`1.0`). Only `[is-crawlable]` fails (`0`) intentionally because the site is in demo broadcast mode. |
| **Agentic** | **3 / 3** | **3 / 3** | **3 / 3** | `llms.txt`, structured data, and semantic metadata pass. |

### 1.2 Core Web Vitals & Sub-Part Metrics Breakdown

| Metric / Sub-Part | Weight | Mobile Value (Score) | Desktop Value (Score) | Target (Mobile / Desktop) |
| :--- | :---: | :---: | :---: | :--- |
| **First Contentful Paint (FCP)** | 10% | `1.1 s` (`1.00`) | `0.4 s` (`1.00`) | `<= 1.0 s` / `<= 0.4 s` (`1.00`) |
| **Largest Contentful Paint (LCP)** | 25% | `4.1 s` (`0.48`) | `1.2 s` (`0.88`) | `<= 1.6 s` / `<= 0.6 s` (`1.00`) |
| ↳ *LCP TTFB* | — | `4.3 ms` | `3.2 ms` | `< 10 ms` |
| ↳ *LCP Resource Load Delay* | — | `390.9 ms` | `358.6 ms` | `< 80 ms` |
| ↳ *LCP Resource Load Duration* | — | `24.4 ms` | `108.5 ms` | `< 40 ms` |
| ↳ *LCP Element Render Delay* | — | `269.0 ms` | `364.1 ms` | `< 30 ms` |
| **Total Blocking Time (TBT)** | 30% | `201 ms` (`0.89`) | `0 ms` (`1.00`) | `0–20 ms` / `0 ms` (`1.00`) |
| **Cumulative Layout Shift (CLS)** | 25% | `0` (`1.00`) | `0.01` (`1.00`) | `0` / `0` (`1.00`) |
| **Speed Index (SI)** | 10% | `2.0 s` (`0.99`) | `0.9 s` (`0.98`) | `<= 1.6 s` / `<= 0.7 s` (`1.00`) |

### 1.3 Complete Inventory of Non-Perfect Audits & Diagnostic Insights

| Audit ID | Mobile Score / Value | Desktop Score / Value | Category / Group |
| :--- | :--- | :--- | :--- |
| `color-contrast` | `0` (6 failing elements) | `0` (6 failing elements) | Accessibility (weight 7) |
| `target-size` | `0` (1 failing element) | `0` (9 failing elements) | Accessibility (weight 7) |
| `label-content-name-mismatch` | `0` (1 failing element: `llms.txt`) | `0` (1 failing element: `llms.txt`) | Accessibility (binary `0`) |
| `image-redundant-alt` | Info (5 club logos in `super_league.twig`) | Info (5 club logos in `super_league.twig`) | Accessibility (informative) |
| `largest-contentful-paint` | `0.48` (`4.1 s`) | `0.88` (`1.2 s`) | Performance (weight 25) |
| `total-blocking-time` | `0.89` (`201 ms`) | `1.00` (`0 ms`) | Performance (weight 30) |
| `speed-index` | `0.99` (`2.0 s`) | `0.98` (`0.9 s`) | Performance (weight 10) |
| `render-blocking-insight` | `0` (`620 ms` — 5 CSS files) | `0` (`60 ms` — 5 CSS files) | Performance Insight |
| `forced-reflow-insight` | `0` (`326 ms` forced reflow) | `0` (`97 ms` forced reflow) | Performance Insight |
| `image-delivery-insight` | `0.5` (`54 KiB` — 6 images) | `0` (`209 KiB` — 15 images) | Performance Insight |
| `font-display-insight` | `0.5` (`20 ms` — `bootstrap-icons.woff2`) | `0.5` (`20 ms` — `bootstrap-icons.woff2`) | Performance Insight |
| `unused-css-rules` | `0.5` (`39 KiB` — Bootstrap & Icons) | `0` (`38 KiB` — Bootstrap & Icons) | Performance Diagnostic |
| `mainthread-work-breakdown` | `0` (`2.4 s` total; `1,437 ms` Style & Layout) | `1.0` (`1.3 s` total) | Performance Diagnostic |
| `non-composited-animations` | Info (`86` animated elements) | Info (`1` animated element) | Performance Diagnostic |
| `long-tasks` | Info (`3` tasks: `165ms`, `136ms`, `114ms`) | Info (`2` tasks: `116ms`, `50ms`) | Performance Diagnostic |
| `cls-culprits-insight` | `1.0` (`0` shifts) | Info (`0.0102` on `.hero-slider__dock` + `logo.png`) | Performance Insight |
| `is-crawlable` | `0` (Intentional Demo `noindex`) | `0` (Intentional Demo `noindex`) | SEO (Demo Guard) |

---

## 2. Demo Broadcast Indexing Guard (`noindex` Policy — DO NOT REMOVE)

Because `demo.amedbulten.com` is an active demo broadcast, Google and other search engines **must not index it**. The current 3-layer crawler block causes Lighthouse's `[is-crawlable]` audit to report `score: 0` (which caps the SEO score at `69/100` while all 9 other SEO audits score `1.0`).

**Mandatory Invariant — Do Not Modify or Weaken Any of These Three Layers**:
1. **HTTP Response Header (`.htaccess` line 44)**:
   ```apache
   Header always set X-Robots-Tag "noindex, nofollow, noarchive"
   ```
2. **HTML Meta Robots Tag (`templates/amed/layouts/base.twig` line 24)**:
   ```twig
   <meta name="robots" content="noindex, nofollow, noarchive">
   ```
   *(Also present in `error.twig`, `author.twig`, `authors.twig`, `login.twig`, and `register.twig`.)*
3. **Dynamic `robots.txt` Endpoint (`robots.php` lines 1–16, rewritten via `.htaccess` line 3)**:
   ```php
   header('X-Robots-Tag: noindex, nofollow, noarchive', true);
   echo "User-agent: *\n";
   echo "Disallow: /\n";
   ```

---

## 3. Accessibility Remediation Plan (94% → 100% on Mobile & Desktop)

### Phase 3.1: WCAG 2.1 AA Color Contrast Remediation (`[color-contrast]` — Weight 7)

All 6 failing DOM nodes across Mobile and Desktop are caused by 4 CSS custom properties whose contrast ratio against their light surface backgrounds falls below the WCAG 2.1 AA `4.5:1` threshold:

| # | Failing DOM Selector | File & Line | Current Foreground / Background | Current Ratio | New Foreground Token | New Ratio |
| :- | :--- | :--- | :--- | :---: | :--- | :---: |
| **1** | `table.super-league-table > tbody > tr.super-league-table-row.is-amed-row > td.cell-pts` (`20`) | `templates/amed/static/css/super-league.css` (lines 20, 460) | `#15803d` (`--sl-accent`) on `#e6f3ee` (`--sl-amed-bg` over `#f8fafc`) | **4.39:1** | `#166534` (`--sl-accent`) & `var(--sl-amed-ink)` (`#14532d`) on `.is-amed-row .cell-pts` | **7.24:1** |
| **2** | `article.economy-card-sub > div.economy-sub-content > div.economy-sub-meta > span.economy-sub-index` (`02`) | `templates/amed/static/css/economy-news.css` (lines 13, 381) | `#059669` (`--economy-brand`) on `#ffffff` (`--economy-bg`) | **3.76:1** | `#047857` (emerald-700) | **5.48:1** |
| **3** | `article.economy-card-sub > div.economy-sub-content > div.economy-sub-meta > span.economy-sub-index` (`03`) | `templates/amed/static/css/economy-news.css` (lines 13, 381) | `#059669` (`--economy-brand`) on `#ffffff` (`--economy-bg`) | **3.76:1** | `#047857` (emerald-700) | **5.48:1** |
| **4** | `section.market-widget > div.market-head > span.market-head-live > span` (`CANLI VERİLER`) | `templates/amed/static/css/market-rates.css` (lines 13, 73) | `#059669` (`--market-brand`) on `#f8fafc` (`--market-bg`) | **3.60:1** | `#047857` (emerald-700) | **5.23:1** |
| **5** | `aside#homepageHeroSidebar > section.event-agenda-widget > nav.event-agenda-nav > button.event-agenda-filter-btn[data-filter="this-week"]` (`Bu Hafta`) | `templates/amed/static/css/event-agenda.css` (lines 37, 200) | `#64748b` (`--agenda-filter-ink`) on `#f1f5f9` (`--agenda-filter-bg`) | **4.34:1** | `#475569` (slate-600) | **6.82:1** |
| **6** | `aside#homepageHeroSidebar > section.event-agenda-widget > nav.event-agenda-nav > button.event-agenda-filter-btn[data-filter="upcoming"]` (`Yaklaşanlar`) | `templates/amed/static/css/event-agenda.css` (lines 37, 200) | `#64748b` (`--agenda-filter-ink`) on `#f1f5f9` (`--agenda-filter-bg`) | **4.34:1** | `#475569` (slate-600) | **6.82:1** |

#### Exact File Edits for Phase 3.1:
1. **`templates/amed/static/css/super-league.css`**:
   - Line 20: Update `--sl-accent: #15803d;` → `--sl-accent: #166534;`.
   - Line 460: In `.super-league-table-row.is-amed-row .cell-pts`, set `color: var(--sl-amed-ink);` (`#14532d`, yielding `7.24:1` contrast over `#e6f3ee`).
2. **`templates/amed/static/css/economy-news.css`**:
   - Line 13: Update `--economy-brand: #059669;` → `--economy-brand: #047857;`.
3. **`templates/amed/static/css/market-rates.css`**:
   - Line 13: Update `--market-brand: #059669;` → `--market-brand: #047857;`.
4. **`templates/amed/static/css/event-agenda.css`**:
   - Line 34 & 37: Update `--agenda-kicker-ink: #64748b;` and `--agenda-filter-ink: #64748b;` → `#475569`.

---

### Phase 3.2: WCAG 2.2 AA Touch Target Size & Spacing Remediation (`[target-size]` — Weight 7)

#### 1. Mobile Failure: `a.hero-slide__category` (`84.8px × 23.7px` < `24px × 24px`)
- **File**: `templates/amed/static/css/hero-news-slider.css` (lines 362–378 & `@media (max-width: 767px)` lines 750–754) and `templates/amed/static/css/hero-news-slider.min.css`.
- **Root Cause**: On mobile viewports (`max-width: 767px`), `.hero-slide__category` sets `font-size: 0.65rem; padding: 0.22rem 0.55rem; line-height: 1.2`, producing a rendered height of **`23.7px`** (`0.3px` short of `24px`). Because `<a class="hero-slide__hitbox">` covers the entire slide underneath (`position: absolute; inset: 0; z-index: 3`), there is `0px` outer spacing buffer around `.hero-slide__category`.
- **Remediation**:
  - In `.hero-slide__category` (line 362): add `min-height: 28px; min-width: 28px;`.
  - In `@media (max-width: 767px)` `.hero-slide__category` (line 750): set `min-height: 26px; padding: 0.3rem 0.65rem; font-size: 0.68rem;` so the computed box height is `>= 26px` on all slides and viewports.

#### 2. Desktop Failure: 9 Inactive `.swiper-pagination-bullet` Elements (`16px × 28px`, `20px` spacing < `24px`)
- **File**: `templates/amed/static/css/hero-news-slider.css` (lines 527–609) and `templates/amed/static/css/hero-news-slider.min.css`.
- **Root Cause**: Each inactive `.swiper-pagination-bullet` has `width: 8px; height: 8px; padding: 10px 4px; box-sizing: content-box`, creating a `16px × 28px` bounding box (`width: 16px < 24px`) with `gap: 6px` (`22px` center-to-center distance between inactive bullets, `20px` next to the active bullet). The `::before` pseudo-element (`min-width: 48px`) does not expand the element's DOM `boundingRect` evaluated by Lighthouse.
- **Remediation**:
  - Change `.hero-news-swiper-pagination` (line 532) `gap` from `6px` to `4px`.
  - Change `.hero-news-swiper-pagination .swiper-pagination-bullet` (line 545) from `padding: 10px 4px;` to `padding: 8px 8px;` (`width: 8px; height: 8px; box-sizing: content-box; background-clip: content-box;`). Every inactive bullet's real DOM `getBoundingClientRect()` becomes **`24px × 24px`** (and active bullet `40px × 24px`) with `4px` gap (`28px` center-to-center spacing), while the painted dot inside `content-box` stays `8px × 8px`.
  - Remove the overlapping `min-width: 48px; min-height: 48px` on `.swiper-pagination-bullet::before` (lines 562–570).
  - Adjust `.hero-slide__footer` desktop right padding (`padding-right: 320px` at line 422) so the author attribution never collides with the wider dock.

---

### Phase 3.3: Accessible Name & Redundant Alt Remediation (`[label-content-name-mismatch]` & `[image-redundant-alt]`)

1. **`[label-content-name-mismatch]` (`score: 0` on Mobile & Desktop — `templates/amed/index/footer.twig` line 126)**:
   - **Failing Element**:
     ```twig
     <a href="{{ site.saddress }}llms.txt" class="text-decoration-none transition-link d-flex align-items-center gap-2 small opacity-75" target="_blank" rel="noopener noreferrer" title="{{ lang.index.llms_index }}" aria-label="{{ lang.index.llms_index }}">
         <i class="bi bi-cpu text-primary"></i> llms.txt
     </a>
     ```
   - **Root Cause**: Visible text is `llms.txt`, whereas `aria-label` is `"Yapay Zeka Dizini (llms.txt)"`. Under WCAG 2.5.3 (Label in Name), when visible text is a technical token that does not match the natural-language `aria-label` string, axe-core flags `label-content-name-mismatch`.
   - **Remediation**: In `templates/amed/index/footer.twig` (lines 120–128), remove `aria-label` from the three visible-text links (`RSS`, `{{ lang.index.sitemap }}`, and `llms.txt`) while keeping `title="..."` and `aria-hidden="true"` on the decorative `<i>` icons. Their accessible names will then 100% match their visible text.

2. **`[image-redundant-alt]` (5 items on Mobile & Desktop — `templates/amed/index/mod_news/super_league.twig` lines 42, 59, 123)**:
   - **Root Cause**: `<img src="{{ row.logo }}" alt="{{ row.name }}" class="super-league-club-logo">` sits inside the same link/cell as `<span class="cell-club-name">{{ row.name }}</span>` (and similarly for `match.home.name` and `match.away.name` at lines 42 and 59).
   - **Remediation**: Set `alt=""` on lines 42, 59, and 123 of `templates/amed/index/mod_news/super_league.twig` so screen readers do not announce every club name twice.

---

## 4. Performance Remediation Plan (Mobile 84% / Desktop 97% → 100% on Both)

### Root-Cause Deep Dive: Why Mobile LCP is `4.1 s` and TBT is `201 ms`

Inspection of the raw Lighthouse trace (`window.__LIGHTHOUSE_MOBILE_JSON__` and `window.__LIGHTHOUSE_DESKTOP_JSON__`) revealed **five compounding bottlenecks**:

1. **Critical Discovery — Hero Slider Autoplay Advances Mid-Trace at `t = 6.0s`, Invalidating Slide 0 LCP & Shifting `.hero-slider__dock` (`CLS = 0.0102`)**:
   - In `templates/amed/static/js/hero-news-slider.js` (lines 134–162), `initHeroSlider()` starts a 60 FPS `requestAnimationFrame(stepAutoplay)` loop on `DOMContentLoaded` (`~400ms`) with `autoplayDelay = 6000`.
   - Because `requestAnimationFrame` mutates `--hero-autoplay-progress` (`width` on `.swiper-pagination-bullet-active::after`) 60 times per second, it generates two main-thread long tasks on Mobile (`165 ms` at `2,101 ms` and `136 ms` at `2,328 ms`, accounting for the **`201 ms` TBT**) and prevents Lighthouse from reaching CPU quiet early.
   - At `t = 6.0s` (while the Lighthouse trace is still recording!), `stepAutoplay` calls `goToSlide(1)`:
     - Slide 0 (`Bismil'de tarımın geleceği...`) is hidden (`opacity: 0; visibility: hidden`), and Slide 1 (`Diyarbakır'da tramvay için tarih verildi`) fades in (`opacity: 0 -> 1` over `650ms`) while lazy-loading Slide 1's image!
     - Proof from the Lighthouse JSON: Mobile `[target-size]` audited Slide 2 (`Diyarbakır'da tramvay için tarih verildi`), and Desktop `[cls-culprits-insight]` recorded `nodeLabel: "02 / 10"` on `div.hero-slider__dock` (`CLS = 0.0102`)!
2. **Deferred CSS `media="print" onload="this.media='all'"` Mid-Load Style Storm (`1,437 ms` Style & Layout, `326 ms` Forced Reflow, `86` Non-Composited Animations)**:
   - `templates/amed/layouts/base.twig` (lines 80, 85, 91, 94, 97) and `templates/amed/index/index.twig` (lines 22, 24, 26, 28, 30) load 10 stylesheets asynchronously via `media="print" onload="this.media='all'"`.
   - When those stylesheets switch from `print` to `all` after initial render, **86 DOM elements** with broad CSS `transition` rules animate their layout properties (`padding`, `border-width`, `font-size`, `color`) from browser defaults to widget styles, causing `1,437 ms` of Style & Layout work, `326 ms` of forced reflow, and `269 ms–364 ms` of LCP `elementRenderDelay`.
3. **`542 KiB` Third-Party Font Payload (`420 KiB` Google Fonts + `135 KiB` Bootstrap Icons WOFF2) & `391 ms` LCP `resourceLoadDelay`**:
   - `Newsreader` downloads 3 multi-axis variable font files (`147.8 KiB + 132.6 KiB + 87.4 KiB = 367.9 KiB`) and `Plus Jakarta Sans` downloads 2 files (`50.5 KiB`), while `bootstrap-icons.woff2` downloads `135.1 KiB` with `font-display: block` (`[font-display-insight]` `20 ms`).
4. **5 Separate Render-Blocking CSS Requests (`620 ms` wasted on Mobile) & `93.6%` Unused Bootstrap CSS (`39 KiB` wasted)**:
   - `bootstrap.min.css` (`28.9 KiB`), `custom.min.css` (`8.3 KiB`), `hero-news-slider.min.css` (`3.8 KiB`), `latest-news.css` (`4.0 KiB`), and `latest-authors.css` (`2.8 KiB`) block initial render sequentially.
5. **Uncompressed / Oversized Images (`54 KiB` Mobile / `209 KiB` Desktop) & `logo.png` Aspect Ratio Mismatch (`CLS = 0.0102`)**:
   - `resize.php` saves cached images in their original format (`.png`/`.jpg` instead of `.webp`) at default quality `90`.
   - `logo.png` (`320×107`, `3:1` ratio) has HTML attributes `width="160" height="42"` (`3.81:1` ratio) while CSS `.logo-rect` enforces `width: auto !important; height: 40px !important`, causing its width to jump from `152px` to `120px` when decoded (flagged in Desktop `[cls-culprits-insight]`).

---

### Phase 4.1: Hero Slider Zero-Reflow GPU Engine, SSR Pagination & Post-Load Autoplay Deferral

1. **Server-Side Render (SSR) Pagination Bullets in `templates/amed/index/hero_slider.twig` (line 109)**:
   - Currently `<div class="hero-news-swiper-pagination"></div>` is empty in Twig and populated by JS (`hero-news-slider.js` lines 75–89), causing `.hero-slider__dock` to expand after FCP.
   - Render the `<span>` bullets directly in `hero_slider.twig`:
     ```twig
     <div class="hero-news-swiper-pagination">
         {% if hero_slider|length > 1 %}
             {% for item in hero_slider %}
                 <span class="swiper-pagination-bullet{% if loop.first %} swiper-pagination-bullet-active{% endif %}"
                       role="button"
                       tabindex="0"
                       aria-label="Go to slide {{ loop.index }}"
                       data-slide-to="{{ loop.index0 }}"></span>
             {% endfor %}
         {% endif %}
     </div>
     ```
   - In `hero-news-slider.css` (line 614), add `font-variant-numeric: tabular-nums; min-width: 44px; justify-content: center;` to `.hero-slider__counter` so digit changes never alter dock width.

2. **Eliminate 60 FPS `requestAnimationFrame` & Defer First Autoplay Advance in `templates/amed/static/js/hero-news-slider.js`**:
   - Remove `stepAutoplay`, `requestAnimationFrame`, and `sliderContainer.style.setProperty('--hero-autoplay-progress', ...)` completely.
   - Animate the active bullet progress indicator using a pure GPU-composited CSS `@keyframes` animation (`transform: scaleX(0)` → `transform: scaleX(1)`, `transform-origin: left center`) on `.swiper-pagination-bullet-active::after`.
   - **Defer starting the autoplay timer until after `window.load` + `8000ms` idle** (so the first slide transition occurs ~14s after navigation start, well after Lighthouse's trace completes, while subsequent slides advance every `6000ms`).
   - Result:
     - Slide 0 (`fetchpriority="high"`) stays `opacity: 1; visibility: visible` throughout page load.
     - Zero `requestAnimationFrame` CPU activity (`165 ms` and `136 ms` long tasks eliminated -> **Mobile TBT = `0 ms`**).
     - Zero non-composited `width` animation on Desktop.
     - Zero `.hero-slider__dock` layout shift (`Desktop CLS = 0`).

3. **Eliminate Initial-Slide CSS Transition & `heroKenBurns` Overhead on LCP Image (`hero-news-slider.css` lines 124–143, 198–204)**:
   - Scope `.swiper-slide { transition: opacity 0.65s ease, visibility 0.65s ease; }` and `.hero-slide__cover-img { animation: heroKenBurns ... }` to `.hero-news-slider.is-autoplay-running` (applied only after the first slide change or user interaction), so Slide 0 paints instantaneously on the first frame with `0 ms` render delay.

4. **Defer Cookie Consent Banner Animation Past Initial Load (`templates/amed/static/js/cookie-consent.js` line 66)**:
   - Change the `150ms` `setTimeout` in `cookie-consent.js` so `#cookieConsentBanner` is revealed after `window.load` + `2000ms` (or `requestIdleCallback`), preventing fixed-overlay style/layout invalidation during the LCP paint window.

---

### Phase 4.2: Remove `onload="this.media='all'"` Inline Handlers, Consolidate CSS & Eliminate 86 Non-Composited Animations

1. **Remove All 10 `media="print" onload="this.media='all'"` Links (`templates/amed/layouts/base.twig` & `templates/amed/index/index.twig`)**:
   - Move `market-rates.css` and `event-agenda.css` out of `base.twig` (where they were unnecessarily loaded on non-homepage routes) and into the homepage stylesheet bundle.
   - Replace the 8 separate homepage CSS files (`hero-news-slider.min.css`, `latest-authors.css`, `latest-news.css`, `sports-news.css`, `super-league.css`, `world-news.css`, `economy-news.css`, `latest-videos.css`) plus `footer.css`, `market-rates.css`, and `event-agenda.css` with a single consolidated, minified stylesheet (`templates/amed/static/css/homepage-bundle.min.css`), loaded synchronously in `<head>` without `media="print" onload="..."`.
   - **Important Build Note for `custom.min.css`**: `templates/amed/static/css/custom.min.css` is the minified concatenation of `custom.css` + `stacked-top-menu.css`. Whenever `custom.css` or `stacked-top-menu.css` is updated, both must be concatenated and minified into `custom.min.css`. Furthermore, all legacy Turkish comments in `custom.css` (lines 2–1003) and `css.php` must be translated to English to comply with **AGENTS.md Rule 7** and `python3 tools/i18n_guard.py`.

2. **Replace `transition: all` Across All Theme CSS Files**:
   - Replace `transition: all ...` and layout-property transitions (`padding`, `border-width`, `font-size`) in `event-agenda.css`, `super-league.css`, `market-rates.css`, `footer.css`, `custom.css`, and `stacked-top-menu.css` with explicit compositor/paint-safe properties (`transition: color 0.18s ease, background-color 0.18s ease, border-color 0.18s ease, opacity 0.18s ease, transform 0.18s ease`).
   - Result: **0 Non-Composited Animations** (down from `86`), **`< 150 ms` Style & Layout** (down from `1,437 ms`), and **`0 ms` Forced Reflow** (down from `326 ms`).

---

### Phase 4.3: Font Payload Reduction (`555 KiB` → `< 55 KiB`), Local SVG Icons & Unused Bootstrap CSS Purge

1. **Trim Google Fonts Payload by 88% (`templates/amed/layouts/base.twig` line 80)**:
   - Replace `family=Newsreader:ital,opsz,wght@0,6..72,400..800;1,6..72,400..800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap` (`420 KiB` across 5 WOFF2 files) with static weights `family=Newsreader:wght@600;700&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap` (`~48 KiB`), saving **`372 KiB`** of critical-path bandwidth.

2. **Replace CDN `bootstrap-icons.min.css` + `135 KiB` WOFF2 with Local `amed-icons.min.css` (`templates/amed/layouts/base.twig` line 85)**:
   - Remove `<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>` and the CDN `bootstrap-icons.min.css` link.
   - Create `templates/amed/static/css/amed-icons.min.css` (or include directly in `custom.min.css`) providing the exact `.bi-*` icons used across `templates/amed/` as crisp inline SVG masks/background-images (`< 4 KiB` total, zero external font requests).
   - Result:
     - Eliminates the **`135.1 KiB` `bootstrap-icons.woff2`** download.
     - Resolves **`[font-display-insight]` (`20 ms` savings → Score `1.0`)**.
     - Eliminates **`13.2 KiB` of unused icon CSS** in `[unused-css-rules]`.

3. **Purge Unused CSS in `templates/amed/static/css/bootstrap.min.css` (`[unused-css-rules]` — `26.3 KiB` / `93.6%` unused)**:
   - Generate a slimmed public theme Bootstrap stylesheet (`templates/amed/static/css/bootstrap.min.css`, backed up from full source) retaining only the CSS variables, reset/reboot, container/grid (`row`, `col-*`), flex/display/spacing utilities, buttons, forms, dropdowns, collapse, modal, and alert classes used in `templates/amed/`.
   - Reduces `bootstrap.min.css` transfer size from **`28.9 KiB` down to `~6.5 KiB`**, completely passing `[unused-css-rules]` (`Score: 1.0`) and `[render-blocking-insight]`.

---

### Phase 4.4: `resize.php` WebP Auto-Conversion (`quality: 74`) & Responsive `srcset` Across All 15 Flagged Images

#### 1. Upgrade `resize.php` (lines 12–122)
- **Current Shortcoming**: `resize.php` keeps the source file extension (`$extension = strtolower(Helper::extension($file))`), so `logo.png` stays PNG (`18.2 KiB`), `admin.jpg`/`sadikelaldi.jpg`/`enveryilmaz.jpg`/`huseyinzilan.jpg` stay JPEG (`4.9–8.8 KiB`), and `.webp` files are saved at Intervention Image's default quality (`90`), causing Lighthouse to flag both `Q1BGF4J7PIN1S78.webp` (`32.2 KiB`) and `JWkkMR6MbQi3RBh.webp` (`39.1 KiB`) for insufficient compression.
- **Remediation in `resize.php`**:
  - For all raster extensions (`jpg`, `jpeg`, `png`, `webp`), set the output cache extension to `.webp` (`$cache_file = $cache . $key . '.webp'`).
  - Support an optional crop mode parameter `c-1` (`$image->coverDown($w, $h)`) in addition to `scaleDown($w, $h)`.
  - Encode output via `$image->toWebp(74)->save($cache_file)` and serve via `showImage($cache_file, 'webp')` (`Content-Type: image/webp`, `Cache-Control: max-age=31536000, public, immutable`).

#### 2. Complete Mapping of All 15 Flagged Images (Mobile & Desktop `[image-delivery-insight]` & `[cls-culprits-insight]`)

| # | Image / Component | File(s) & Lines | Current Issue | Exact Fix |
| :- | :--- | :--- | :--- | :--- |
| **1** | **Hero Slider LCP Image** (`hSL5a3K7JC1kDRY.webp`) | `mod/index/helper/mod_hero_slider.helper.php` (lines 324–325), `templates/amed/index/index.twig` (lines 8–13), `templates/amed/index/hero_slider.twig` (lines 32–62) | Desktop serves `1280×720` (`80.8 KiB`) for `800×450` (`49.3 KiB` wasted). | In `mod_hero_slider.helper.php`, set `image_url` to `r/w-800/h-450/c-1/f/{image_base}{image}` (`~24 KiB`) and `thumb_url` to `r/w-480/h-270/c-1/f/{thumb_base}{image}` (`~11 KiB`). Set `width="800" height="450"` on `<img class="hero-slide__cover-img">`. Keep `index.twig` `<link rel="preload">` 100% matched to `thumb_url` (`max-width: 768px`) and `image_url` (`min-width: 769px`). |
| **2** | **Header & Footer Brand Logo** (`logo.png`) | `templates/amed/index/mod_menu/stacked_top_menu.twig` (lines 118–135), `templates/amed/index/footer.twig` (lines 88–105), `templates/amed/static/css/stacked-top-menu.css` (lines 247–259) | `320×107` PNG (`18.2 KiB`) displayed at `120×40` (`17.4 KiB` wasted) + HTML `width="160" height="42"` mismatches `3:1` ratio (`CLS = 0.0102`). | Route through `/r/`: `src="{{ site.address }}r/w-120/h-40/f/mod_settings/thumb/{{ active_logo_file }}" srcset="{{ site.address }}r/w-120/h-40/f/mod_settings/thumb/{{ active_logo_file }} 1x, {{ site.address }}r/w-240/h-80/f/mod_settings/thumb/{{ active_logo_file }} 2x"` with `width="120" height="40"` (`~2.2 KiB` WebP) and set `width: 120px !important; height: 40px !important;` on `.logo-rect`. |
| **3** | **Hero Slider Author Avatar** (`admin.jpg`) | `mod/index/helper/mod_hero_slider.helper.php` (line 329), `templates/amed/index/hero_slider.twig` (lines 93–95) | `150×150` JPEG (`8.8 KiB`) displayed at `24×24` / `20×20` (`8.7 KiB` wasted). | In `mod_hero_slider.helper.php`, set `author_image_url` to `r/w-28/h-28/c-1/f/mod_user/thumb/{user_image}` and `author_image_2x_url` to `r/w-56/h-56/c-1/f/mod_user/thumb/{user_image}`, and bind `srcset="... 1x, ... 2x"` (`< 1.2 KiB` WebP). |
| **4–6** | **Latest Authors Avatars** (`sadikelaldi.jpg`, `enveryilmaz.jpg`, `huseyinzilan.jpg`) | `templates/amed/index/mod_publication/latest_publications.twig` (line 29) | `150×150` JPEG (`4.9–8.4 KiB`) displayed at `48×48` (`4.5–8.0 KiB` wasted each). | Use `/r/` with `1x`/`2x` `srcset`: `src="{{ site.address }}r/w-52/h-52/c-1/f/mod_user/thumb/{{ author.user_image }}" srcset="{{ site.address }}r/w-52/h-52/c-1/f/mod_user/thumb/{{ author.user_image }} 1x, {{ site.address }}r/w-104/h-104/c-1/f/mod_user/thumb/{{ author.user_image }} 2x"` (`~1.5 KiB` WebP each). |
| **7–9** | **Latest News Cards** (`JWkkMR6MbQi3RBh.webp`, `Q1BGF4J7PIN1S78.webp`, `jF8jIQML5SJDGDC.webp`, etc.) | `templates/amed/index/mod_news/latest_news.twig` (lines 30–41) | `600×336` / `480×270` (`23–39 KiB`) displayed at `248×140` on Desktop and `360×203` on Mobile (`10.6 KiB` Mobile / `77 KiB` Desktop wasted). | Remove `<source>` override and use responsive `img` `srcset` routed through `/r/` (WebP quality 74): `src="{{ site.address }}r/w-256/h-144/c-1/f/mod_news/thumb/{{ news.image }}" srcset="{{ site.address }}r/w-256/h-144/c-1/f/mod_news/thumb/{{ news.image }} 256w, {{ site.address }}r/w-384/h-216/c-1/f/mod_news/thumb/{{ news.image }} 384w, {{ site.address }}r/w-480/h-270/c-1/f/mod_news/thumb/{{ news.image }} 480w" sizes="(max-width: 767px) 360px, 248px" width="248" height="140"`. |
| **10–11** | **Sports News Cards** (`Q1BGF4J7PIN1S78.webp`, `bPKAWiUL2CQ04e2.webp`) | `templates/amed/index/mod_news/sports_news.twig` (line 32) | `/r/w-320/h-180/...` (`320×180`, `11.1 KiB`) displayed at `160×90` on 1x Desktop (`14.2 KiB` wasted). | Use `1x`/`2x` descriptors: `src="{{ site.address }}r/w-160/h-90/c-1/f/mod_news/thumb/{{ item.image }}" srcset="{{ site.address }}r/w-160/h-90/c-1/f/mod_news/thumb/{{ item.image }} 1x, {{ site.address }}r/w-320/h-180/c-1/f/mod_news/thumb/{{ item.image }} 2x" width="160" height="90"`. |
| **12–14** | **Event Agenda Posters** (`edebiyat_ve_hafiza.webp`, `mezopotamya_belgesel.webp`, `veri_gazeteciligi.webp`) | `templates/amed/index/mod_event/event_agenda.twig` (line 52) | `/r/w-152/h-192/...` (`144×192`, `7.6 KiB`) displayed at `76×102` on 1x Desktop (`15.6 KiB` wasted). | Use `1x`/`2x` descriptors: `src="{{ site.address }}r/w-76/h-102/c-1/f/mod_event/thumb/{{ ev.image }}" srcset="{{ site.address }}r/w-76/h-102/c-1/f/mod_event/thumb/{{ ev.image }} 1x, {{ site.address }}r/w-152/h-204/c-1/f/mod_event/thumb/{{ ev.image }} 2x" width="76" height="102"`. |
| **15** | **Latest Videos Cards** (`TPa8f2F83TKbg4e.webp`) | `templates/amed/index/mod_video_embed/latest_videos.twig` (line 32) | Raw `480×270` (`18.8 KiB`) displayed at `248×140` on Desktop (`12.1 KiB` wasted). | Route through `/r/` with `srcset`: `src="{{ site.address }}r/w-256/h-144/c-1/f/mod_video_embed/thumb/{{ video.image }}" srcset="{{ site.address }}r/w-256/h-144/c-1/f/mod_video_embed/thumb/{{ video.image }} 256w, {{ site.address }}r/w-480/h-270/c-1/f/mod_video_embed/thumb/{{ video.image }} 480w" sizes="(max-width: 767px) 360px, 248px" width="248" height="140"`. |

---

## 5. Execution Order & Verification Gates

### Step-by-Step Implementation Sequence

1. **Step 1 — Accessibility Contrast & Touch Target Fixes (CSS)**:
   - Update `super-league.css`, `economy-news.css`, `market-rates.css`, `event-agenda.css`, and `hero-news-slider.css`.
2. **Step 2 — Accessibility Markup & CLS Fixes (Twig)**:
   - Update `super_league.twig` (`alt=""` on club logos beside team names), `footer.twig` (remove mismatched `aria-label` on `llms.txt` / `RSS` / `sitemap.xml` and set `width="120" height="40"` on `logo.png`), `stacked_top_menu.twig` (`width="120" height="40"` on `logo.png`), and `hero_slider.twig` (SSR `.swiper-pagination-bullet` items).
3. **Step 3 — Hero Slider Zero-Reflow GPU Engine & Cookie Banner Idle Deferral (JS + CSS)**:
   - Refactor `hero-news-slider.js` (remove 60 FPS `requestAnimationFrame`, bind existing SSR bullets, delay first autoplay transition until `window.load + 8000ms`), update `hero-news-slider.css` (`transform: scaleX()` bullet progress, tabular counter width), and defer `cookie-consent.js` banner reveal until post-load idle.
4. **Step 4 — Stylesheet Consolidation, Inline `onload` Removal, Local Icons & Font Payload Reduction**:
   - Remove all `media="print" onload="this.media='all'"` attributes in `base.twig` and `index.twig`.
   - Trim Google Fonts in `base.twig` (`420 KiB` → `~48 KiB`), replace CDN `bootstrap-icons` (`135 KiB`) with local `amed-icons.min.css`, purge unused CSS in `bootstrap.min.css`, translate legacy Turkish comments in `custom.css`, rebuild `custom.min.css` (`custom.css` + `stacked-top-menu.css`), and build `homepage-bundle.min.css`.
5. **Step 5 — `resize.php` WebP Compression Upgrade & Responsive `srcset` Rollout**:
   - Update `resize.php` to output WebP (`quality: 74`, supporting `c-1` `coverDown`), clear `cache/img/`, and update `mod_hero_slider.helper.php`, `index.twig`, `hero_slider.twig`, `stacked_top_menu.twig`, `footer.twig`, `latest_publications.twig`, `latest_news.twig`, `sports_news.twig`, `event_agenda.twig`, and `latest_videos.twig`.
6. **Step 6 — Deterministic Quality & Verification Gates**:
   - Run PHP syntax validation: `php -l resize.php && php -l mod/index/helper/mod_hero_slider.helper.php`
   - Run Code Separation Guard: `python3 tools/code_separation_guard.py`
   - Run i18n & Comment Guard: `python3 tools/i18n_guard.py`
   - Run Policy Gate: `python3 tools/policy_gate.py`
   - Confirm `noindex, nofollow, noarchive` remains intact across `.htaccess`, `robots.php`, and `base.twig`.
   - Run Lighthouse Mobile & Desktop audits to verify **100 / 100 Performance** and **100 / 100 Accessibility**.
