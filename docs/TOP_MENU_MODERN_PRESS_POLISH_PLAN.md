# Top Menu "Modern Press" — Taste Skill & Super Polish Implementation Plan

**Target Prototype Directory:** `docs/top-menu-modern-press/` (`index.html`, `style.css`, `script.js`)  
**Production Parity Reference:** `templates/amida/index/mod_menu/stacked_top_menu.twig`  
**Design Read:** *"Reading this as: an authoritative 2-row newspaper masthead and category navigation bar for news readers, with a crisp, high-trust editorial language leaning toward the Modern Editorial Press aesthetic family."*

---

## 1. Comprehensive Audit Findings (Zero-Defect Inspection)

A deep line-by-line inspection of `docs/top-menu-modern-press/index.html`, `style.css`, `script.js`, `static/mod_settings/logo.svg`, and `templates/amida/index/mod_menu/stacked_top_menu.twig` uncovered **8 specific structural, visual, and behavioral defects**:

### 1.1 Optical Alignment Drift (Row 1 Logo vs. Row 2 First Category)
- **Root Cause:** Both `.stacked-header-top` and `.stacked-nav-bar` use `max-width: 1280px; padding: 0 1.5rem;`. However, in Row 2, `.stacked-nav-list .nav-link` applies `padding: 0 1.05rem;` (`16.8px`). Furthermore, `static/mod_settings/logo.svg` (`viewBox="0 0 486 128"`, rendered at `width="175" height="42"`, aspect ratio `3.796:1` inside a `4.166:1` box) sits centered inside its `175px` image box (`~7.8px` horizontal letterbox inset) plus `~2px` internal glyph coordinate offset.
- **Visual Defect:** While the translucent active pill box around `"Haberler"` aligns with the outer container edge, the actual `"H"` of `"Haberler"` sits `16.8px` inward, drifting out of vertical alignment with the `"B"` of **Amed BÜLTEN** above it.

### 1.2 Generic SaaS Pill Tabs vs. Editorial Press Navigation (Row 2)
- **Root Cause:** `.stacked-nav-list .nav-item.active > .nav-link` uses `background-color: rgba(255, 255, 255, 0.2)` on a `32px` floating pill inside a `44px` bar.
- **Visual Defect:** Floating translucent rounded pills read like a generic Bootstrap SaaS dashboard tab bar rather than a newspaper masthead. Additionally, `index.html` and `style.css` comments explicitly promise a `3px` bottom active indicator (`--mp-green-600`) that is completely missing from the CSS rules.

### 1.3 Row 2 Right-Side Visual Vacuum
- **Root Cause:** Row 2 contains 5 top-level editorial categories (`Haberler`, `Makaleler`, `Yazarlar`, `Videolar`, `Duyurular`) clustered on the left, leaving `~65%` of the blue bar completely empty on desktop viewports.
- **Visual Defect:** Without subtle editorial column dividers (`1px` vertical hairlines) or intentional typographic tracking/spacing, the right two-thirds of Row 2 feels unfinished compared to the balanced 3-zone layout of Row 1.

### 1.4 Surface & Border Token Mismatch in Row 1 (Visual Weight Competition)
- **Root Cause:** The search bar (`.stacked-search-wrapper`) uses a quiet neutral resting state (`background: var(--mp-surface-subtle)`, `border: 1px solid var(--mp-border-strong)`), whereas the adjacent Dark Mode button (`.btn-header-action`) uses a saturated blue tint (`background: var(--mp-blue-50)`, `border: 1.5px solid var(--mp-blue-200)`, `color: var(--mp-blue-600)`).
- **Visual Defect:** Two different surface and border token systems compete on the same horizontal row, and the bright blue Dark Mode button steals visual attention from the primary **Giriş** CTA (`.btn-header-login`).

### 1.5 Incomplete Tactile Search Bar & Native WebKit Cancel Button Conflict
- **Root Cause:** `script.js` (lines 15–54) wires up `#stackedSearchClear`, `.has-value` state toggling, and the global `/` keyboard shortcut, and `style.css` header comments document them. However:
  1. `<kbd class="stacked-search-kbd">/</kbd>` and `<button id="stackedSearchClear">` are missing from `index.html`.
  2. `<input type="search">` renders the browser's native blue/gray `::-webkit-search-cancel-button` in Chrome/Edge/Safari, which will collide with `#stackedSearchClear` unless explicitly reset in CSS.

### 1.6 Production Twig Parity Gaps (`stacked_top_menu.twig` vs. Prototype)
- **Root Cause:** Comparing `templates/amida/index/mod_menu/stacked_top_menu.twig` with `docs/top-menu-modern-press/index.html` reveals three parity omissions:
  1. **Language Selector Dropdown (`#stackedLanguageDropdown` & `#stackedLanguageDropdownMobile`):** Present in `stacked_top_menu.twig` (lines 46–70 and 225–254) when `site.available_languages|length > 1`, but omitted from the prototype.
  2. **Mobile Member Profile Card (`.mobile-user-card`):** In `stacked_top_menu.twig` (lines 278–295), logged-in mobile users see their avatar, full name, `@username`, and quick profile button above the Logout button. In `index.html` (lines 209–214), `#userProfileMobile` only renders the Logout button.
  3. **Dropdown Avatar Sizing Class (`.user-avatar-sm`):** `stacked_top_menu.twig` (line 101) uses `.user-avatar-sm.user-avatar-sm-placeholder`, which is not styled in `style.css`.

### 1.7 Sticky Scroll Compaction Missing on Row 1 Controls
- **Root Cause:** When `.stacked-header.is-scrolled` activates on scroll, `.stacked-header-top` shrinks from `70px` to `58px` and `.stacked-search-wrapper` shrinks from `40px` to `36px` (`style.css` lines 132–135, 197–199). However, `.btn-header-action`, `.btn-header-login`, and `.user-profile-btn` remain fixed at `height: 40px`.
- **Visual Defect:** Upon scrolling, the search bar becomes `36px` tall while the right-side buttons stay `40px` tall, breaking the concentric `40px` horizontal height alignment across Row 1.

### 1.8 Accessibility & Motion Polish Gaps (`:focus-visible` & `prefers-reduced-motion`)
- **Root Cause:** `.stacked-search-input` sets `outline: none;`, which is properly handled by `.stacked-search-wrapper:focus-within`, but `.btn-header-action`, `.btn-header-login`, `.user-profile-btn`, and `.stacked-nav-list .nav-link` lack explicit `:focus-visible` rings, and `style.css` lacks a `@media (prefers-reduced-motion: reduce)` guard.

---

## 2. Taste Skill Calibration (Three Dials)

- **`DESIGN_VARIANCE`: `6 / 10`** — Structured 2-row editorial grid with crisp optical alignment, tactile search controls, and press-column rhythm.
- **`MOTION_INTENSITY`: `5 / 10`** — Smooth `cubic-bezier(0.22, 1, 0.36, 1)` transitions on search focus, `/` keycap fade, active underline reveal, and synchronized scroll compaction, guarded by `prefers-reduced-motion`.
- **`VISUAL_DENSITY`: `4 / 10`** — Disciplined `70px` top masthead row + `44px` category bar (`114px` resting; `96px` when `.is-scrolled`).

---

## 3. Step-by-Step Implementation Plan

### Phase 1: Row 1 Surface Harmony, Logo Geometry & Scroll Synchronization (`style.css`)
**Objective:** Unify Row 1 surface tokens, eliminate SVG bounding-box slack, and synchronize control heights in both resting (`40px`) and scrolled (`36px`) states.

1. **Fix SVG Masthead Intrinsic Aspect Ratio (`.stacked-logo-svg`):**
   - Set `width: auto; height: 42px;` on `.stacked-logo-svg` (and update `width="160" height="42"` in `index.html` to match the exact `486:128` aspect ratio of `logo.svg`) so there is zero horizontal letterboxing inside `a.stacked-brand`.
2. **Unify Utility Button Resting Surface (`.btn-header-action`):**
   - Style `.btn-header-action` (Language Dropdown & Dark Mode Toggle) with the same quiet, tactile resting tokens as `.stacked-search-wrapper`:
     - `background-color: var(--mp-surface-subtle);`
     - `border: 1px solid var(--mp-border-strong);`
     - `color: var(--mp-ink-muted);`
     - `font-weight: 600;`
   - On `:hover` and `:focus-visible`, transition to `background-color: var(--mp-blue-50); border-color: var(--mp-blue-300); color: var(--mp-blue-600);` so utility buttons feel responsive without overpowering the solid blue **Giriş** CTA.
3. **Synchronize `.is-scrolled` Control Heights:**
   - Add rules for `header.stacked-header.is-scrolled .btn-header-action`, `.btn-header-login`, and `.user-profile-btn` to compact cleanly to `height: 36px;` (and `.btn-header-action.btn-icon` to `width: 36px; height: 36px;`), preserving 100% height parity with the `36px` scrolled search bar.
4. **Refine Primary Login CTA (`.btn-header-login`):**
   - Add a subtle tactile top highlight: `box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 3px rgba(2, 132, 199, 0.22);`.

---

### Phase 2: Complete Tactile Search Bar (`index.html` & `style.css`)
**Objective:** Connect the existing `script.js` search controller (`/` shortcut, `.has-value`, `#stackedSearchClear`) with clean DOM elements and suppress native browser search cancel icons.

1. **Reset Native Search Cancel Input Chrome (`style.css`):**
   - Add:
     ```css
     header.stacked-header .stacked-search-input::-webkit-search-decoration,
     header.stacked-header .stacked-search-input::-webkit-search-cancel-button,
     header.stacked-header .stacked-search-input::-webkit-search-results-button,
     header.stacked-header .stacked-search-input::-webkit-search-results-decoration {
       -webkit-appearance: none;
       appearance: none;
       display: none;
     }
     ```
2. **Add `<kbd>` Hint & `#stackedSearchClear` Button (`index.html`):**
   - Inside `#stackedSearchWrapper`, right after `#stackedSearchInput` and before `.stacked-search-btn`, insert:
     - `<kbd class="stacked-search-kbd" aria-hidden="true" title="Aramak için / tuşuna basın">/</kbd>`
     - `<button class="stacked-search-clear" id="stackedSearchClear" type="button" aria-label="Aramayı temizle" title="Temizle"><i class="bi bi-x-lg" aria-hidden="true"></i></button>`
3. **Style `.stacked-search-kbd` & `.stacked-search-clear` (`style.css`):**
   - `.stacked-search-kbd`: `display: inline-flex; align-items: center; justify-content: center; height: 22px; min-width: 22px; padding: 0 6px; margin-right: 6px; font-family: inherit; font-size: 0.72rem; font-weight: 600; color: var(--mp-ink-placeholder); background-color: var(--mp-white); border: 1px solid var(--mp-border-strong); border-radius: 4px; box-shadow: 0 1px 0 rgba(15, 23, 42, 0.06); pointer-events: none; transition: opacity 0.15s ease, transform 0.15s ease;`
   - Hide `.stacked-search-kbd` (`opacity: 0; width: 0; margin-right: 0; overflow: hidden;`) when `.stacked-search-wrapper:focus-within` or `.stacked-search-wrapper.has-value` is active.
   - `.stacked-search-clear`: Hidden by default (`display: none;`), revealed as a `26x26px` subtle icon button (`display: inline-flex;`) when `.stacked-search-wrapper.has-value` is active.

---

### Phase 3: Row 2 Optical Alignment, Press Hairlines & 3px Editorial Underline (`style.css`)
**Objective:** Transform Row 2 from floating Bootstrap pills into an authentic newspaper category bar with exact left-edge optical alignment and the promised `3px` active indicator.

1. **Stretch Nav Items to Full Bar Height (`44px` resting / `38px` scrolled):**
   - Set `.stacked-nav-list` and `.stacked-nav-list .nav-item` to `height: 44px;` (`38px` when `.is-scrolled`), with `gap: 0;` instead of `gap: 0.35rem;`.
2. **Optical Left Alignment with Masthead Logo:**
   - Style `.stacked-nav-list .nav-link` as full-height editorial tabs (`height: 100%; padding: 0 1.15rem; border-radius: 0;`).
   - Add subtle vertical press dividers between categories (`border-right: 1px solid rgba(255, 255, 255, 0.12);` on `.stacked-nav-list .nav-item`, and `border-left: 1px solid rgba(255, 255, 255, 0.12);` on `.stacked-nav-list .nav-item:first-child`), OR offset the first link (`margin-left: -1.15rem;` when unbordered) so the `"H"` of `"Haberler"` aligns optically with the `"B"` of **Amed BÜLTEN** above it.
   - *Selected Precision Approach:* Keep full-height links with `padding: 0 1.1rem;`, add subtle `1px solid rgba(255, 255, 255, 0.1)` right dividers between items, and give `.nav-item.active > .nav-link` a refined translucent press surface (`background-color: rgba(255, 255, 255, 0.14)`) plus a flush `3px` bottom editorial bar (`::after` with `height: 3px; background-color: var(--mp-green-600);` or high-visibility `#4ade80` / `--mp-green-600` accent).
   - Ensure the first category's active bar and typography align cleanly with the left container axis.

---

### Phase 4: Production Twig Parity & Accessibility Hardening (`index.html` & `style.css`)
**Objective:** Ensure 100% parity with `templates/amida/index/mod_menu/stacked_top_menu.twig` and complete keyboard/motion accessibility.

1. **Add Language Selector Dropdown (Desktop & Mobile Drawer in `index.html`):**
   - Include the `#stackedLanguageDropdown` (`TR` / `KU` / `EN`) in `.stacked-utility-zone` and `#stackedLanguageDropdownMobile` in `.mobile-controls-row`, matching `stacked_top_menu.twig` lines 46–70 and 225–254.
2. **Complete Mobile Logged-in User Card (`#userProfileMobile` in `index.html` & `style.css`):**
   - Add `.mobile-user-card` inside `#userProfileMobile` (avatar, `Azad Amed`, `@azadamed`, and profile link button) above the Logout button, matching `stacked_top_menu.twig` lines 278–299, and style `.user-avatar-sm` / `.mobile-user-card` in `style.css`.
3. **Explicit `:focus-visible` & `@media (prefers-reduced-motion: reduce)` (`style.css`):**
   - Add clear `:focus-visible` outlines (`outline: 2px solid var(--mp-blue-500); outline-offset: 2px;`) for Row 1 buttons and (`outline: 2px solid #ffffff; outline-offset: -4px;`) for Row 2 nav links.
   - Add a `@media (prefers-reduced-motion: reduce)` block disabling transform/scroll animations for motion-sensitive users.
4. **Synchronize `.mp-spec-card` Documentation Copy (`index.html`):**
   - Update `.mp-spec-card` text and badges so they accurately describe the implemented design without outdated references.

---

## 4. Deterministic Verification Checklist

Before marking implementation complete, run and verify:
- [ ] **Code Separation Guard (`AGENTS.md` Rule #2):** Run `python3 tools/code_separation_guard.py` — confirm `0` inline `style` attributes, `0` `<style>` blocks, and `0` inline `on*` handlers in `docs/top-menu-modern-press/index.html`.
- [ ] **Module Boundary Guard:** Run `python3 tools/module_boundary_guard.py` to confirm zero boundary violations.
- [ ] **DOM & Selector Parity Check:** Verify all IDs and classes (`#amidaStackedHeader`, `#stackedSearchWrapper`, `#stackedSearchInput`, `#stackedSearchClear`, `# stackedLanguageDropdown`, `#darkModeToggle`, `#guestAuthDesktop`, `#userProfileDesktop`, `#amidaMobileMenu`, `#loginModal`) match `script.js` and `stacked_top_menu.twig`.
- [ ] **Visual & Responsive Inspection:** Verify Light/Dark themes, Guest/Member states, `/` search shortcut + clear button, `.is-scrolled` 36px height compaction, and `< 992px` Mobile Offcanvas Drawer.
