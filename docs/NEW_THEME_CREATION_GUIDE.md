# New Theme Creation Guide

This document explains how to create a new frontend theme in this project without modifying the existing `templates/amida` theme directly.

## Mandatory Theme Rules

The new theme must follow these rules without exception:

1. No hardcoded strings are allowed in the theme. All visible text must be connected to language files.
2. English must be used as the standard language for core technical elements such as keys, methods, functions, variables, and structural naming.
3. The implementation must follow the Amed Framework & CMS architecture correctly so the theme integrates in the most accurate and maintainable way.
4. The theme must use modern technologies and modern frontend practices, with mobile usage treated as the highest priority.
5. User convenience must always come first. Navigation, interactions, and content access should be simple and clear.
6. When ordering interface elements, importance and user priority must be considered deliberately.
7. The visual direction must reflect an artistic and creative spirit rather than a generic or purely utilitarian style.

## Soul Theme Specific Direction

The following instructions are specific to the new `soul` theme and must be treated as the active design brief for implementation.

### Theme Identity

- Theme name: `soul`
- Target folder: `templates/soul`
- Theme type: single-person portfolio
- Intended persona: one individual with poet, writer, and director qualities

### Homepage Structure For `soul`

The homepage must use the slider as the visual background foundation.

Required structure:

1. Top menu over the slider/background
2. Slider as the main visual layer
3. Footer over the slider/background

Rules:

- `mod_slider` must remain the primary homepage background and visual storytelling layer.
- `mod_menu` must sit on top of the slider visually.
- The footer must also sit on top of the slider visually.
- The homepage must not allocate sections for other modules.
- The homepage must not render `latest_content`.
- Empty placeholder columns for future homepage modules must not be added.

### Visual Direction For `soul`

- The top menu must be minimalist.
- The footer must be minimalist.
- The top menu and footer must still feel original and custom, not generic and not derivative of the existing `artist` theme.
- Typography must be minimal.
- The color system must be high contrast.
- The overall emotional direction must support a personal artistic identity rather than a corporate or magazine-like style.

### Content And Brand Character

The `soul` theme should feel appropriate for a single creator who works across multiple expressive disciplines.

The interface should communicate:

- literary depth
- visual restraint
- cinematic presence
- a personal author signature

The design should not feel:

- generic
- agency-like
- multi-author newsroom-like
- overly decorative without purpose

## Legacy Theme Analysis And No-Regression Requirements

The new theme must not be treated as a simple visual reskin. Based on the current `default` and `artist` themes, the new implementation must preserve the combined behavior set of both themes.

### A. Shared Core Capabilities That Must Survive

Both existing themes already implement a substantial frontend system. The new theme must preserve all of the following:

- Full Twig theme structure with `layouts`, `index`, `partials`, `macros`, `mod_user`, and `static`.
- Global base layout with SEO metadata, Open Graph tags, Twitter tags, favicon links, web manifest, and viewport configuration.
- Frontend navigation generated through the framework menu pipeline (`run({"mod": "index", "method": "_menu"})` and macro-based rendering).
- Homepage support with slider integration and homepage content sections.
- Search entry points and search result templates.
- Content, page, announcement, gallery, and video presentation templates.
- Footer rendering and shared partial-based composition.
- Cookie consent UI and persistence behavior.
- Authentication-related screens such as login, register, activation, re-activation, forgot password, and password reset.
- Responsive behavior across desktop and mobile.
- Frontend JavaScript hooks that rely on `ctx_get`, `ctx_add`, `javascript`, and `jquery_document` context injection.

### B. Existing Feature Inventory From `default`

The `default` theme currently provides these important behaviors that must not be lost:

- A full Bootstrap-based top navigation with desktop and mobile states.
- Search UI integrated directly into the main navigation.
- Language switcher for multiple languages including `ku`, `ckb`, `zza`, `tr`, `en`, `ar`, `de`, `fr`, and `sv`.
- User account dropdown with unread messages, account shortcut, and add-content shortcut.
- Frontend theme selector UI using the `tema` cookie and active-state indicators.
- Dark mode toggle behavior in shared top menu JavaScript.
- Login modal with a two-step user-check flow before password entry.
- Rich register page behavior, including AJAX username availability checks, email validation, phone formatting logic, and client-side feedback.
- Forgot-password screen with enhanced validation and user feedback.
- Gallery helper partial that initializes Swiper.
- Popup system driven by `run({"mod": "index", "method": "_popup"})` and cookie-based one-time display behavior.
- Share partial with social sharing and copy-link support.
- Homepage latest content block enabled by default.
- Dedicated CSS files such as `gallery.css` and `login-modal.css`.

### C. Existing Feature Inventory From `artist`

The `artist` theme currently provides these important behaviors that must not be lost:

- A visually distinct floating glass-style header (`artist_top_menu.twig`) rather than a standard navbar only.
- Artistic brand presentation, including special typography behavior for supported languages.
- A design direction that prioritizes atmosphere, presentation, and author identity.
- A dedicated artistic top-menu stylesheet (`artist-top-menu.css`).
- A compact action-oriented header with navigation, search, language selection, dark mode, auth access, and mobile toggler.
- Alternative login modal handling with retry-aware AJAX behavior and graceful fallback handling.
- Cookie consent presentation with a more refined card-like UI and delayed reveal behavior.
- Theme-aware early color-mode bootstrapping in `layouts/base.twig` to reduce flicker.
- `color_mode` persistence via cookie and `localStorage`.
- Theme-specific imagery and branding assets such as background and artist identity visuals.
- A separate video template namespace under `index/mod_video_embed`.
- Additional `base_static` assets related to gallery and AKM tools.
- A homepage variant where latest content is intentionally hidden, which means homepage block composition is theme-driven and must remain configurable.

### D. Functional Differences The New Theme Must Reconcile

The two themes are not identical. The new theme must intentionally unify these differences instead of silently dropping one side:

1. Theme switching:
The project uses `tema` cookie-based template switching, but the `artist` theme also has custom `color_mode` persistence logic. The new theme must preserve template switching and color-mode switching without allowing one to break the other.

2. Navigation architecture:
`default` uses a classic Bootstrap navbar pattern, while `artist` uses a floating artistic header. The new theme may choose a new visual direction, but it must retain the full capability set of both approaches.

3. Video template namespace:
Frontend video templates use `index/mod_video_embed`. The new theme must support the active runtime namespace instead of assuming outdated module names.

4. Homepage composition:
`default` renders latest content on the homepage, while `artist` suppresses it. The new theme must make this a deliberate, configurable content-priority decision rather than an accidental omission.
For this project, the new theme must follow the `artist` homepage composition model: the homepage should contain only the top menu, slider, and footer. No additional homepage area should be reserved for other frontend modules unless a future requirement explicitly changes this rule.

5. Login flow depth:
`default` has a more guided two-step login flow, while `artist` focuses more on request resilience and fallback handling. The new theme should preserve both usability and robustness.
For the login modal UI and interaction model, the `default` theme must be used as the primary reference because it is the newer and more modern implementation.

6. Cookie consent behavior:
Both themes implement consent, but with different interaction models. The new theme must keep consent storage, visible messaging, and clear acceptance flow.

### E. Mandatory No-Loss Migration Checklist

Before the new theme can be considered complete, confirm that none of these capabilities were removed:

- SEO/meta/head setup is still complete in the base layout.
- Framework-driven menu loading still works.
- Desktop and mobile navigation both work.
- Search remains available from primary navigation.
- Multi-language switching remains available.
- Dark mode and color-mode persistence still work.
- Frontend theme switching still works.
- Login modal still supports AJAX and user-friendly recovery states.
- The login modal baseline is taken from the newer `default` theme implementation, not the older `artist` modal structure.
- Register page still supports validation and asynchronous availability checks.
- Forgot-password flow still works.
- Content pages still support share UI where currently available.
- Announcement and page detail templates still support media gallery behavior.
- Slider rendering still works on the homepage.
- The homepage structure follows the `artist` model and does not allocate homepage sections for extra modules.
- Cookie consent still appears and persists correctly.
- Popup behavior still works where enabled.
- User shortcuts such as inbox, account panel, and content creation remain reachable.
- Existing context-script injection (`ctx_add`, `ctx_get`) still executes correctly.
- No module template namespace in active use has been dropped.

### F. Implementation Guidance From The Analysis

Based on the current themes, the correct strategy is not "copy one theme and restyle it." The correct strategy is:

1. Audit both themes file by file before deciding the new baseline.
2. Build a merged feature checklist from both themes.
3. Choose one theme only as a temporary scaffold, not as the full source of truth.
4. Reintroduce any missing features from the other theme immediately after scaffolding.
5. Treat auth, navigation, language switching, theme switching, cookie consent, media galleries, and homepage composition as protected features.
6. Only after feature parity is reached should visual redesign continue.

## Homepage Composition Rule

The new theme homepage must be intentionally minimal and must follow the current `templates/artist/` behavior.

Required homepage structure:

1. Top menu
2. Slider
3. Footer

Homepage restrictions:

- Do not render `latest_content` on the homepage.
- Do not reserve empty left/right content columns for future modules.
- Do not place announcements, galleries, videos, content lists, or other module blocks on the homepage by default.
- Keep the homepage focused on brand presence, first impression, and the slider-driven visual narrative.

## 1. Choose a Theme Name

Pick a unique, lowercase folder name for the new theme.

For this project, the selected theme name is:

- `soul`

Example:

- `templates/minimal`
- `templates/editorial`
- `templates/studio`

Use the same name consistently in folder names, cookies, selectors, and any theme registration logic.

## 2. Create the Theme Folder

Create a new folder under `templates/` using the selected theme name.

For this project, the target folder is:

- `templates/soul`

The safest starting point is to copy one existing theme as a base, then refactor it.

Recommended approach:

1. Copy `templates/amida` into a new folder.
2. Rename the copied folder to the new theme name.
3. Remove or replace design-specific files that should not be inherited.

## 3. Preserve the Required Structure

Keep the same general folder structure used by the active frontend themes.

Typical structure:

- `templates/<theme>/index`
- `templates/<theme>/layouts`
- `templates/<theme>/partials`
- `templates/<theme>/macros`
- `templates/<theme>/mod_user`
- `templates/<theme>/static`

Depending on the design, you may also need:

- `templates/<theme>/static/css`
- `templates/<theme>/static/js`
- `templates/<theme>/static/img`
- `templates/<theme>/static/scss`
- `templates/<theme>/static/less`

If the new theme requires extra shared assets or module-specific assets, create equivalent subfolders inside the new theme.

## 4. Update Layout Files First

Start from the main layout layer before editing page-level templates.

Review and adapt:

- `layouts/base.twig`
- Any other layout files inside `layouts/`
- Global includes from `partials/`

Make sure the layout points to the new theme's own CSS, JS, and image paths instead of referencing the old theme by name.

## 5. Update Partial Templates

Check shared UI pieces such as:

- header
- footer
- menu
- alerts
- cookie consent
- search UI
- user/account dropdowns

These often contain theme-specific classes, asset paths, or includes. Replace inherited visual rules with the new theme's own structure.

## 6. Review Page Templates Under `index/`

Inspect the page templates used by frontend modules, especially:

- `index/mod_menu`
- `index/mod_page`
- `index/mod_content`
- `index/mod_slider`
- `index/mod_announcements`
- `index/mod_imagecategories`
- `index/mod_video_embed`

Confirm that all includes and extends statements point to the new theme namespace.

Examples to verify:

- `{% extends "<theme>/layouts/base.twig" %}`
- `{% include "<theme>/partials/..." %}`

## 7. Move or Rebuild Theme Assets

Create the asset files needed by the new design inside the new theme folder.

Typical files:

- CSS bundles
- SCSS or LESS sources
- JavaScript for menus, sliders, theme interactions, and page effects
- images, icons, backgrounds, and logos

Do not leave the new theme dependent on `default` or `artist` asset files unless that dependency is intentional and documented.

## 8. Register the Theme in Theme Selection Logic

This project uses a `tema` cookie to switch frontend themes, so the new theme must be added anywhere the valid theme list is defined or rendered.

Review and update these areas:

1. `tema.php`
2. `config/site.config.example.php`
3. Theme selector buttons in frontend menus
4. Any JavaScript that sets or reads the `tema` cookie

At minimum, verify the new theme name is accepted by the theme-switching flow.

## 9. Update Frontend Theme Selector UI

The existing frontend menu templates contain hardcoded theme buttons.

Review files such as:

- `templates/amida/index/mod_menu/top_menu.twig`
- related `top-menu.js` files inside each theme

Add a new selector entry for the new theme if you want users to switch to it from the frontend.

Also update any visual label, badge, or indicator class used by the selector.

## 10. Check Cookie-Based Activation

The current configuration reads the active theme from the `tema` cookie when the selected template directory exists.

Make sure:

1. The cookie value exactly matches the new folder name.
2. The new theme folder exists under `templates/`.
3. No old logic clears or overrides the cookie unexpectedly.

If theme switching behaves inconsistently, inspect both Twig templates and JavaScript for cookie reset logic.

## 11. Fix Hardcoded Old Theme References

After copying a base theme, search for leftover references to:

- `default`
- `artist`
- `new`

Common places:

- Twig `include` and `extends` statements
- asset URLs
- CSS class names
- JS selectors
- inline labels such as "Default" or "Modern"

This step is critical. A copied theme often appears to work while still loading files from the original theme.

## 12. Validate Module Coverage

Open and test the major frontend routes to confirm the new theme covers all important screens.

Suggested checks:

1. Homepage
2. Menu/navigation pages
3. Content detail pages
4. Static pages
5. Announcement/news pages
6. Search results
7. Login/register/user panel pages
8. Any page with sliders, galleries, or embeds
9. Popup-enabled pages
10. Theme-switching and dark-mode interaction paths
11. Language switcher interactions

## 13. Test Responsive Behavior

Check the new theme on:

- desktop
- tablet
- mobile

Pay special attention to:

- navigation collapse
- dropdown menus
- header spacing
- image scaling
- slider behavior
- footer layout
- dark mode or theme-specific toggles if present

## 14. Clear Caches If Needed

If Twig or asset changes do not appear immediately, clear the relevant caches.

Relevant cache areas in this project include:

- `templates_c`
- `cache/css`
- `cache/js`

Use the project's existing cache-clearing workflow if required.

## 15. Final Verification Checklist

Before considering the new theme complete, confirm:

- A dedicated folder exists under `templates/<theme>`.
- The new theme has its own layouts, partials, index templates, and assets.
- No unintended dependency on `default` or `artist` remains.
- No hardcoded UI text remains in Twig, CSS-generated content, or JavaScript-rendered content.
- Language keys and technical naming are consistent and English-based.
- The integration respects Amed Framework & CMS conventions instead of bypassing them.
- The new theme preserves the combined capability set currently split across `default` and `artist`.
- Navigation, authentication, theme switching, language switching, gallery behavior, popup behavior, and cookie consent all remain functional.
- Any feature present in only one old theme has been reviewed and either preserved or intentionally replaced with an equal-or-better implementation.
- The theme can be activated through the project's theme selection mechanism.
- Main frontend pages render without Twig errors.
- Static assets load without 404 errors.
- Mobile-first layouts and interactions work correctly across key breakpoints.
- The interface hierarchy reflects content importance clearly.
- The design direction feels modern, creative, and artistically intentional.
- Mobile navigation and responsive sections work correctly.

## Recommended Workflow Summary

1. Copy one existing theme into a new folder.
2. Rename and clean all inherited references.
3. Rebuild layout and shared partials.
4. Replace assets with the new design system.
5. Register the new theme in cookie-based theme switching.
6. Test all major frontend routes.
7. Clear caches and re-test.
