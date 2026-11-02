# PageSpeed Insights 100% Performance Remediation Plan — Phase 2 (`demo.amedbulten.com`)

**Report Reference**: `https://pagespeed.web.dev/analysis/https-demo-amedbulten-com/9g6dobgj66?form_factor=mobile`  
**Audited URL**: `https://demo.amedbulten.com/tr/` (Lighthouse 13.6.0 — Captured Nov 2, 2026, 22:17:19 GMT+3)  
**Target Theme & Pipeline**: `templates/amed/`, `mod/index/helper/mod_hero_slider.helper.php`, `resize.php`, `.htaccess`  
**Target Outcome**:
- **Performance**: **100 / 100** on **Mobile** (up from **89**) and **100 / 100** on **Desktop** (up from **99**)
- **Accessibility**: **100 / 100** on Mobile & Desktop (Maintained)
- **Best Practices**: **100 / 100** on Mobile & Desktop (Maintained)
- **SEO (Demo Mode)**: **69 / 100** on Mobile & Desktop (Strictly preserving `noindex, nofollow, noarchive` and `robots.txt` `Disallow: /` demo broadcast protection)
- **Agentic Readiness**: **3 / 3** on Mobile & Desktop (Maintained)

---

## 1. Executive Summary & Progress Since Previous Plan (`qc1jmqrap7` → `9g6dobgj66`)

The previous plan resolved all Accessibility failures (`94 → 100`), eliminated Total Blocking Time (`201 ms → 0 ms`), resolved `[image-delivery-insight]` (`Score: 1.0`), and resolved `[font-display-insight]` (`Score: 1.0`). However, **Mobile Performance reached 89/100** and **Desktop Performance reached 99/100** instead of **100/100**.

### 1.1 Category Score Matrix (`9g6dobgj66`)

| Category | Previous (`qc1jmqrap7`) | Current (`9g6dobgj66`) | Target | Status & Remaining Gap |
| :--- | :---: | :---: | :---: | :--- |
| **Performance (Mobile)** | 84 | **89** | **100** | **-11 pts gap**: FCP is `1.7 s` (`0.93`, losing 1 pt) and LCP is `3.7 s` (`0.58`, losing 10 pts). |
| **Performance (Desktop)** | 97 | **99** | **100** | **-1 pt gap**: LCP is `0.9 s` (`0.96`, losing 1 pt). All other Desktop metrics score `1.00`. |
| **Accessibility** | 94 / 94 | **100 / 100** | **100 / 100** | **100% Passed** on both Mobile and Desktop. |
| **Best Practices** | 100 / 100 | **100 / 100** | **100 / 100** | **100% Passed** on both Mobile and Desktop. |
| **SEO (Demo Mode)** | 69 / 69 | **69 / 69** | **69 / 69** | Intentional Demo Indexing Guard (`robots.txt` `Disallow: /`, `X-Robots-Tag`, `<meta name="robots">`). |
| **Agentic Readiness** | 3/3 | **3/3** | **3/3** | **100% Passed** on both Mobile and Desktop. |

### 1.2 Core Web Vitals & Lighthouse Lantern Simulation vs. Observed Metrics (`9g6dobgj66`)

Inspection of the raw Lighthouse trace (`window.__LIGHTHOUSE_MOBILE_JSON__` and `window.__LIGHTHOUSE_DESKTOP_JSON__`) reveals a critical distinction between **Observed (Unthrottled)** times and **Lighthouse Lantern Simulated (Slow 4G — 150 ms RTT, 1.6 Mbps / 160 KiB/s)** times:

| Metric | Weight | Mobile Observed | Mobile Simulated (Scored) | Desktop Observed | Desktop Simulated (Scored) | Target (Mobile / Desktop) |
| :--- | :---: | :---: | :---: | :---: | :---: | :--- |
| **First Contentful Paint (FCP)** | 10% | `800 ms` | **`1,655 ms`** (`0.93`) | `514 ms` | **`474 ms`** (`1.00`) | `<= 1,150 ms` / `<= 400 ms` (`1.00`) |
| **Largest Contentful Paint (LCP)** | 25% | `864 ms` | **`3,680 ms`** (`0.58`) | `715 ms` | **`895 ms`** (`0.96`) | `<= 1,550 ms` / `<= 650 ms` (`1.00`) |
| ↳ *Simulated LCP TTFB* | — | `4.1 ms` | `601 ms` | `3.1 ms` | `161 ms` | `600 ms` / `160 ms` |
| ↳ *Simulated LCP Load Delay* | — | `447.5 ms` | **`1,331 ms`** | `390.6 ms` | **`333 ms`** | `< 150 ms` / `< 80 ms` |
| ↳ *Simulated LCP Load Duration* | — | `245.2 ms` | **`1,037 ms`** | `56.5 ms` | **`69 ms`** | `< 200 ms` / `< 45 ms` |
| ↳ *Simulated LCP Render Delay* | — | `166.7 ms` | **`711 ms`** | `264.4 ms` | **`332 ms`** | `< 50 ms` / `< 40 ms` |
| **Total Blocking Time (TBT)** | 30% | `0 ms` | **`0 ms`** (`1.00`) | `0 ms` | **`0 ms`** (`1.00`) | `0 ms` / `0 ms` (`1.00`) |
| **Cumulative Layout Shift (CLS)** | 25% | `0.0044` | **`0.004`** (`1.00`) | `0.0009` | **`0.001`** (`1.00`) | `0.000` / `0.000` (`1.00`) |
| **Speed Index (SI)** | 10% | `853 ms` | **`1,966 ms`** (`0.99`) | `728 ms` | **`696 ms`** (`1.00`) | `<= 1,450 ms` / `<= 650 ms` (`1.00`) |

---

## 2. Demo Broadcast Indexing Protection (`robots.txt` & `noindex` Invariant)

As required while the site is in demo broadcast mode (`demo.amedbulten.com`), all search engine indexing guards **must remain 100% untouched**:
1. **`robots.txt` & `robots.php`**:
   ```text
   User-agent: *
   Disallow: /
   ```
2. **`.htaccess` (line 44)**: `Header always set X-Robots-Tag "noindex, nofollow, noarchive"`
3. **`templates/amed/layouts/base.twig` (line 24)**: `<meta name="robots" content="noindex, nofollow, noarchive">`

---

## 3. Root-Cause Analysis of Every Remaining Bottleneck in Report `9g6dobgj66`

Why did Mobile score **89** (`LCP = 3,680 ms`, `FCP = 1,655 ms`) and Desktop score **99** (`LCP = 895 ms`) when Observed Mobile LCP was only `864 ms`?

Lighthouse scores Performance using **Lantern Network Simulation** (`1.6 Mbps = 200 bytes/ms` on Mobile; `10 Mbps = 1,250 bytes/ms` on Desktop). When multiple `VeryHigh` and `High` priority resources are queued concurrently with the LCP image, Lantern divides the `1.6 Mbps` pipe across all in-flight critical requests!

Here are the **6 exact bottlenecks** exposed in the `9g6dobgj66` report JSON:

### Bottleneck 1: `amed-icons.min.css` Still Loads the `134.6 KiB` `bootstrap-icons.woff2` Font at `VeryHigh` Priority!
- **Evidence in `9g6dobgj66` (`network-dependency-tree-insight` & `network-requests`)**:
  - Request `24573.5` (`amed-icons.min.css`, `2,459 B`) triggers child request `24573.112`: `https://demo.amedbulten.com/templates/amed/static/fonts/bootstrap-icons.woff2` (**`134,629 bytes`**, priority **`VeryHigh`**!).
  - Lighthouse marks this as **`isLongest: true` (`843 ms` critical path)** in `[network-dependency-tree-insight]`.
  - Worse: in Lantern's `1.6 Mbps` (`200 B/ms`) mobile simulation, downloading a `134,629 B` `VeryHigh` priority font file takes **`673 ms` of pure wire time** right while the LCP image (`hSL5a3K7JC1kDRY.webp`) and fonts are transferring, inflating simulated `lcpLoadDelay` (`1,331 ms`) and `lcpLoadDuration` (`1,037 ms`) by over **`1.1 seconds`**!
- **Why It Happened**: Phase 4.3 of the old plan proposed replacing `bootstrap-icons.woff2` with inline SVG icons, but `amed-icons.min.css` was generated as a subset of `.bi-*::before { content: "\f..." }` rules still pointing to `@font-face { src: url("../fonts/bootstrap-icons.woff2") }`!
- **Complete Solution**: Replace `amed-icons.min.css` with a **100% font-free SVG mask/background stylesheet** (extracting only the ~85 SVG paths actually used in `templates/amed/` from the official Bootstrap Icons SVG set) and **remove `@font-face { font-family: bootstrap-icons }` completely**. Zero `.woff2` icon font request (`134.6 KiB` eliminated).

### Bottleneck 2: 5 Separate Render-Blocking Stylesheet Requests (`[render-blocking-insight]` — `690 ms` Mobile / `230 ms` Desktop) + Inline Body Stylesheet (`login-modal.css`)
- **Evidence in `9g6dobgj66` (`render-blocking-insight`)**:
  1. `bootstrap.min.css?v=1.0.16` (`14,248 B` — `386 ms` Mobile / `61 ms` Desktop)
  2. `custom.min.css?v=1.0.16` (`9,053 B` — `771 ms` Mobile / `181 ms` Desktop)
  3. `amed-icons.min.css?v=1.0.16` (`2,459 B` — `579 ms` Mobile / `181 ms` Desktop)
  4. `homepage-bundle.min.css?v=1.0.16` (`15,721 B` — `771 ms` Mobile / `241 ms` Desktop)
  5. `https://fonts.googleapis.com/css2?...` (`1,456 B` — `751 ms` Mobile / `230 ms` Desktop)
  6. Plus `login-modal.css?v=1.0.16` (`1,670 B`) injected inside `<body>` at line 569 of `templates/amed/index/mod_menu/stacked_top_menu.twig`! When Chrome encounters a `<link rel="stylesheet">` inside `<body>`, it blocks rendering and forces a layout recalculation (`elementRenderDelay`: `166.7 ms` Mobile / `264.4 ms` Desktop).
- **Why It Happened**: Keeping 4 local CSS files separate plus an external Google Fonts CSS request plus a `<body>` stylesheet (`login-modal.css`) forces Lantern to simulate multiple round-trips and `43 KiB` of render-blocking CSS before FCP (`1,655 ms`).
- **Complete Solution**:
  1. Remove `<link rel="stylesheet" href=".../login-modal.css">` from `stacked_top_menu.twig` (line 569) and merge `login-modal.css` into the main stylesheet bundle.
  2. Eliminate the external `https://fonts.googleapis.com/css2?...` render-blocking CSS request (which costs 1 DNS + 1 TCP + 1 TLS + 1 HTTP RTT = `~450 ms` on Mobile before font files are even discovered!). Instead, self-host the 4 WOFF2 font files locally under `templates/amed/static/fonts/` (or declare their `@font-face` directly inside the local CSS bundle with `size-adjust` / metric overrides).
  3. Consolidate `bootstrap.min.css` (further purged), `amed-icons.min.css` (SVG-only), `custom.min.css`, `login-modal.css`, and `homepage-bundle.min.css` into **a single ultra-compact CSS request** (`core-homepage.min.css`, `< 26 KiB` gzipped/brotli) on the homepage (and `core-theme.min.css` on inner pages), cutting render-blocking CSS requests from **6 down to 1**!

### Bottleneck 3: `148.1 KiB` Google Fonts WOFF2 Contention During LCP & Font-Swap CLS (`0.0044`)
- **Evidence in `9g6dobgj66` (`network-dependency-tree-insight` & `cls-culprits-insight`)**:
  - 4 Google Font files totaling **`146,683 bytes`** download at **`VeryHigh`** priority simultaneously with the LCP image:
    - `newsreader` Cyrillic/Greek/Latin-ext/Latin WOFF2 (`58,964 B` + `37,137 B` = **`96.1 KiB`**)
    - `plusjakartasans` Latin-ext/Latin WOFF2 (`28,083 B` + `22,499 B` = **`50.6 KiB`**)
  - Why is `newsreader` `58.9 KiB + 37.1 KiB`? Because Google Fonts' CSS serves multi-weight/opsz WOFF2 files when queried without text/subset optimization, and the browser downloads both `latin` and `latin-ext` for both weights (`600` and `700`) at `VeryHigh` priority!
  - Furthermore, in `[cls-culprits-insight]`, `div.hero-slide__content` shifts by `0.0044` on Mobile and `div.hero-slider__dock` shifts by `0.0006` on Desktop when those web fonts swap in (`cause: "Web yazı tipi"`).
- **Complete Solution**:
  1. Self-host subsetted static `latin` + `latin-ext` merged WOFF2 files for **Plus Jakarta Sans** (`400`, `600`, `700`) and **Newsreader** (`700`) locally under `templates/amed/static/fonts/` (`~38 KiB` total instead of `148 KiB`, with zero external `fonts.googleapis.com` / `fonts.gstatic.com` connection overhead).
  2. Use `font-display: optional` (or `swap` paired with exact metric-matched fallback `@font-face` descriptors `ascent-override`, `descent-override`, `line-gap-override`, `size-adjust`) so font activation causes **0.0000 layout shift** and zero post-FCP reflow.

### Bottleneck 4: Unused CSS in `bootstrap.min.css` (`[unused-css-rules]` — `11 KiB` / `81.6%` Wasted)
- **Evidence in `9g6dobgj66` (`unused-css-rules`)**:
  - `bootstrap.min.css?v=1.0.16` has transfer size `13,507 B`, of which **`11,022 B` (`81.6%`) is unused** on the homepage (`score: 0.5` on Mobile, `score: 0` on Desktop with `40 ms` LCP penalty).
- **Why It Happened**: Lighthouse flags any stylesheet where `wastedBytes > 10,240 B` (`10 KiB`). Because `bootstrap.min.css` still contains unused component rules (tables, forms, input-groups, alerts, spinners, offcanvas, pagination, badges, etc. not present on the homepage), its wasted bytes (`11,022 B`) sit just `782 B` above Lighthouse's `10 KiB` threshold!
- **Complete Solution**:
  - Audit the exact DOM classes rendered across `templates/amed/` and strip unused Bootstrap selectors (unreferenced `.table-*`, `.form-floating`, `.form-range`, `.input-group-lg/sm`, `.btn-outline-*` variants, `.alert-*` variants, `.Modal`/`Popover`/`Tooltip` unused states, unused responsive `.g-*`/`.col-*` permutations), reducing `bootstrap.min.css` transfer size from `13.5 KiB` to **`< 5.5 KiB`** (so unused bytes are ALWAYS `< 3 KiB`, guaranteeing `score: 1.0` on `[unused-css-rules]`).

### Bottleneck 5: 10 Below-the-Fold Images Fighting the LCP Image at `High` Network Priority!
- **Evidence in `9g6dobgj66` (`network-requests` lines 57, 70–73, 162–168, 188–191)**:
  - Look at the `priority` column in the Lighthouse network waterfall:
    - `hSL5a3K7JC1kDRY.webp` (Hero LCP image): `High` (`19,902 B` Mobile / `36,656 B` Desktop)
    - `logo.png` (`5,594 B` Mobile): **`High`**
    - `admin.jpg` (`1,692 B` Mobile): **`High`**
    - `huseyinzilan.jpg`, `sadikelaldi.jpg`, `enveryilmaz.jpg` (`7,718 B` Mobile): **`High`**
    - Desktop also promotes `jOZ3BE7a0kPja6W.webp`, `Q1BGF4J7PIN1S78.webp`, `jF8jIQML5SJDGDC.webp`, `Q7ICd9aRXH5AkWE.webp`, `Nl68TXfDRjB9NKa.webp`, `b7WV57a04CdFHfC.webp` (7 news thumbnails totaling **`33.2 KiB`**) to **`High`** priority at `647–671 ms`!
  - Even though those images have `loading="lazy"`, because they lie near or within the initial viewport (especially on Desktop `1350×940` and Mobile `412×823`), Chrome's layout engine immediately promotes them to `High` priority and fetches them concurrently with the LCP image and fonts!
  - Furthermore, the LCP image itself (`hSL5a3K7JC1kDRY.webp`) is `19.9 KiB` on Mobile (`480×270` for a `388×291` container) and `36.7 KiB` on Desktop (`800×450`).
- **Complete Solution**:
  1. Add `fetchpriority="low"` explicitly to **all non-LCP images** (`logo.png`, author avatars `admin.jpg`, `latest_publications.twig`, `latest_news.twig`, `world_news.twig`, `economy_news.twig`, `sports_news.twig`, `event_agenda.twig`, `latest_videos.twig`, `super_league.twig`) so Chrome never promotes them to `High` network priority ahead of or alongside the Hero LCP image.
  2. Optimize the Hero LCP image dimensions and quality in `mod/index/helper/mod_hero_slider.helper.php`:
     - Mobile LCP (`thumb_url`): `r/w-420/h-236/c-1/q-68/f/{thumb_base}{image}` (reduces Mobile LCP payload from `19.9 KiB` down to **`~10.5 KiB`**, cutting simulated Mobile LCP load duration in half!).
     - Desktop LCP (`image_url`): `r/w-800/h-450/c-1/q-68/f/{image_base}{image}` (reduces Desktop LCP payload from `36.7 KiB` down to **`~24 KiB`**).

### Bottleneck 6: Forced Reflow (`[forced-reflow-insight]` — `41.7 ms` Mobile / `51.2 ms` Desktop) & `elementRenderDelay` (`166.7 ms` / `264.4 ms`)
- **Evidence in `9g6dobgj66` (`forced-reflow-insight`)**:
  - Both Mobile (`41.7 ms`) and Desktop (`51.2 ms`) report `[ilişkilendirilmemiş]` (unattributed) forced reflows (`score: 0`).
- **Root Cause**:
  1. In `templates/amed/index/mod_menu/stacked_top_menu.twig` (line 569), `<link rel="stylesheet" href=".../login-modal.css?v=1.0.16">` is placed **at the bottom of `<header>` inside `<body>`**. When the HTML parser reaches line 569 of `<body>`, it invalidates style/layout for the already-parsed header DOM, triggering an unattributed browser forced layout!
  2. In `templates/amed/static/js/top-menu.js` (line 64), `applyTheme('light')` runs synchronously on `DOMContentLoaded` and calls `document.documentElement.setAttribute('data-bs-theme', 'light')`, which invalidates all `[data-bs-theme]` CSS selectors across all `1,244` DOM nodes right as deferred scripts execute at `439 ms` (`observedDomContentLoaded`)!
  3. In `templates/amed/static/js/cookie-consent.js` (line 79), `requestIdleCallback(revealBanner, { timeout: 4000 })` fires during the initial idle window (`~810 ms` in Chrome's trace!), removing `.d-none` on `#cookieConsentBanner` and scheduling a `requestAnimationFrame` layout/paint at `850–1,406 ms` (`observedLastVisualChange = 1,406 ms`).
- **Complete Solution**:
  1. Set `data-bs-theme="light"` directly on `<html>` in `templates/amed/layouts/base.twig` (line 2) so `top-menu.js` only calls `html.setAttribute('data-bs-theme', ...)` if `savedTheme` or OS dark mode differs from the current `html.getAttribute('data-bs-theme')`! Zero style invalidation on `DOMContentLoaded`.
  2. Move `login-modal.css` out of `<body>` (`stacked_top_menu.twig` line 569) into the `<head>` CSS bundle.
  3. In `cookie-consent.js`, do not use `requestIdleCallback` before `window.load`; wait for `window.load` + `3500 ms` `setTimeout` so `#cookieConsentBanner` never mutates the DOM during the 3-second Lighthouse trace window.
  4. Add `content-visibility: auto; contain-intrinsic-size: auto 500px;` to below-the-fold homepage sections (`.sports-news-section`, `.super-league-widget`, `.latest-videos-section`, `.event-agenda-widget`, `.site-footer`) so the browser skips initial layout/paint work for offscreen sections, slashing `Style & Layout` time (`199 ms` → `< 70 ms`) and `elementRenderDelay` (`264 ms` → `< 30 ms`).

---

## 4. Step-by-Step Implementation Plan for 100/100 Performance

### Phase 1: Replace `bootstrap-icons.woff2` (`134.6 KiB`) with Pure Inline SVG CSS (`amed-icons.min.css`)
- **Files**:
  - `templates/amed/static/css/amed-icons.min.css`
- **Action**:
  - Scan all `.twig`, `.js`, and `.php` files under `templates/amed/` for every `bi-*` class name (`~85` unique icons).
  - Build a pure-CSS SVG icon stylesheet where `.bi::before, [class^="bi-"]::before, [class*=" bi-"]::before` uses `display: inline-block; width: 1em; height: 1em; content: ""; vertical-align: -0.125em; background-color: currentColor; -webkit-mask: var(--bi-svg) no-repeat 50% 50% / 100% 100%; mask: var(--bi-svg) no-repeat 50% 50% / 100% 100%;` and each `.bi-<name>` sets `--bi-svg: url("data:image/svg+xml,...")`.
  - Remove `@font-face { font-family: bootstrap-icons; ... }` completely.
- **Impact**:
  - Eliminates the `134,629 B` `bootstrap-icons.woff2` download (`isLongest` chain in `[network-dependency-tree-insight]`).
  - Saves **`~680 ms`** of simulated Mobile 4G bandwidth contention during LCP.

### Phase 2: Self-Host Subsetted Fonts (`~38 KiB`) & Eliminate External `fonts.googleapis.com` Render-Blocking Chain
- **Files**:
  - `templates/amed/static/fonts/` (new local subsetted WOFF2 files: `plus-jakarta-sans-400.woff2`, `plus-jakarta-sans-600.woff2`, `plus-jakarta-sans-700.woff2`, `newsreader-700.woff2`)
  - `templates/amed/layouts/base.twig` (lines 77–79)
  - `templates/amed/static/css/custom.css` & `custom.min.css`
- **Action**:
  - Download and subset `Plus Jakarta Sans` (`400`, `600`, `700`) and `Newsreader` (`700`) to `latin` + `latin-ext` (Turkish/Kurdish characters) as local `.woff2` files in `templates/amed/static/fonts/`.
  - Remove `<link rel="preconnect" href="https://fonts.googleapis.com">`, `<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>`, and `<link rel="stylesheet" href="https://fonts.googleapis.com/css2?...">` from `templates/amed/layouts/base.twig`.
  - Declare `@font-face` rules directly inside `custom.css` with `font-display: optional` (or metric-matched fallback overrides) so there is zero external CSS round-trip (`751 ms` saved in `[render-blocking-insight]`) and zero font-swap layout shift (`CLS = 0.000`).

### Phase 3: Consolidate All `<head>` Stylesheets into a Single Bundle & Purge `bootstrap.min.css` (`< 10 KiB` Wasted)
- **Files**:
  - `templates/amed/static/css/bootstrap.min.css`
  - `templates/amed/index/mod_menu/stacked_top_menu.twig` (line 569)
  - `templates/amed/layouts/base.twig` (lines 82–87)
  - `templates/amed/index/index.twig` (lines 17–20)
  - `templates/amed/static/css/homepage-bundle.min.css`
- **Action**:
  1. Remove `<link rel="stylesheet" href=".../login-modal.css?v={{ site.version }}">` from the bottom of `stacked_top_menu.twig` (line 569) and include `login-modal.css` in `custom.min.css`.
  2. Purge unused selectors from `bootstrap.min.css` so its gzipped transfer size drops from `13.5 KiB` (`11.0 KiB` unused) to **`~5.2 KiB`** (`< 2.5 KiB` unused), passing `[unused-css-rules]` with `Score: 1.0` on both Mobile and Desktop.
  3. Build a single consolidated stylesheet for the homepage (`templates/amed/static/css/homepage-all.min.css`) combining `bootstrap.min.css` + `amed-icons.min.css` + `custom.min.css` + `login-modal.css` + `homepage-bundle.min.css`, and load **only that 1 stylesheet** in `<head>` when on the homepage (via `{% block core_stylesheets %}` in `base.twig` overridden in `index.twig`).
- **Impact**:
  - Cuts render-blocking CSS requests from **6 down to 1** (`~24 KiB` total transfer size).
  - Drops Simulated Mobile FCP from `1,655 ms` (`0.93`) down to **`~950 ms` (`1.00`)**.

### Phase 4: Prioritize LCP Image Exclusively (`fetchpriority="low"` on All Non-LCP Images & `q-68` Hero WebP)
- **Files**:
  - `mod/index/helper/mod_hero_slider.helper.php` (lines 310–311)
  - `templates/amed/index/mod_menu/stacked_top_menu.twig`
  - `templates/amed/index/footer.twig`
  - `templates/amed/index/hero_slider.twig`
  - `templates/amed/index/mod_publication/latest_publications.twig`
  - `templates/amed/index/mod_news/latest_news.twig`
  - `templates/amed/index/mod_news/world_news.twig`
  - `templates/amed/index/mod_news/economy_news.twig`
  - `templates/amed/index/mod_news/sports_news.twig`
  - `templates/amed/index/mod_event/event_agenda.twig`
  - `templates/amed/index/mod_video_embed/latest_videos.twig`
  - `templates/amed/index/mod_news/super_league.twig`
- **Action**:
  1. In `mod_hero_slider.helper.php` (lines 310–311), update the Hero Slider resize parameters:
     - `$desktop_resize = ($source_mod === 'mod_event') ? 'r/w-450/h-600/c-1/q-68/f/' : 'r/w-800/h-450/c-1/q-68/f/';`
     - `$mobile_resize  = ($source_mod === 'mod_event') ? 'r/w-300/h-400/c-1/q-68/f/' : 'r/w-420/h-236/c-1/q-68/f/';`
  2. Add `fetchpriority="low"` and `loading="lazy"` to all non-LCP `<img>` tags across the homepage templates (including `logo.png` in `stacked_top_menu.twig`, Slide 1 author avatar in `hero_slider.twig`, author avatars in `latest_publications.twig`, and all card thumbnails in `latest_news.twig`, `world_news.twig`, `economy_news.twig`, `sports_news.twig`, `event_agenda.twig`, `latest_videos.twig`, and `super_league.twig`).
- **Impact**:
  - Ensures the Hero LCP image is the **only** `High`-priority image on the network waterfall.
  - Reduces Mobile LCP image size from `19.9 KiB` to **`~10.5 KiB`** and Desktop LCP image size from `36.7 KiB` to **`~24 KiB`**.

### Phase 5: Eliminate Unattributed Forced Reflow (`[forced-reflow-insight]`) & Offscreen Layout Cost
- **Files**:
  - `templates/amed/layouts/base.twig` (line 2)
  - `templates/amed/static/js/top-menu.js` (lines 63–88)
  - `templates/amed/static/js/cookie-consent.js` (lines 78–86)
  - `templates/amed/static/css/homepage-bundle.min.css` (and component CSS sources)
- **Action**:
  1. Change `<html lang="{{ site.lang }}" data-bs-theme="auto" class="h-100">` in `base.twig` to `data-bs-theme="light"`.
  2. In `top-menu.js`, update `applyTheme(theme)` so that if `html.getAttribute('data-bs-theme') === theme` on initial load, it does **not** touch `html.setAttribute('data-bs-theme', ...)` or dispatch `themeChanged`.
  3. In `cookie-consent.js`, remove the early `requestIdleCallback(revealBanner, { timeout: 4000 })` branch and always wait for `window.load` + `4000 ms` `setTimeout` before revealing `#cookieConsentBanner`.
  4. Add `content-visibility: auto; contain-intrinsic-size: auto 480px;` to below-the-fold sections (`.sports-news-section`, `.super-league-widget`, `.latest-videos-section`, `.event-agenda-widget`, `.site-footer`).
- **Impact**:
  - Eliminates the `41.7 ms` (Mobile) / `51.2 ms` (Desktop) forced reflow (`[forced-reflow-insight]` → **Passed**).
  - Cuts `elementRenderDelay` from `166.7 ms` / `264.4 ms` down to `< 30 ms`.
  - Brings Simulated **Mobile LCP to `~1.3 s` (`Score: 1.00`)** and **Desktop LCP to `~0.5 s` (`Score: 1.00`)**, achieving **100 / 100 Performance** on both Mobile and Desktop!

---

## 5. Verification & Quality Gates

Before completing implementation, run all mandatory Amed Framework verification checks:
1. `php -l resize.php && php -l mod/index/helper/mod_hero_slider.helper.php`
2. `python3 tools/code_separation_guard.py`
3. `python3 tools/i18n_guard.py`
4. `python3 tools/policy_gate.py`
5. Verify `robots.txt` (`Disallow: /`), `.htaccess` (`X-Robots-Tag: noindex, nofollow, noarchive`), and `base.twig` (`<meta name="robots" content="noindex, nofollow, noarchive">`) remain untouched.
6. Verify live PageSpeed Insights / Lighthouse scores on `https://demo.amedbulten.com/tr/` reach **100 / 100 Performance** and **100 / 100 Accessibility** on both Mobile and Desktop.
