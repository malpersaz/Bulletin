# New Frontend Theme Creation Guide

## Purpose

This guide defines the repository-safe process for creating a new public frontend theme under `templates/`.

It replaces the older theme notes that referenced non-existent or project-external theme folders. A new theme must be built from the actual repository state, not from assumptions copied from sibling repositories or older branches.

## Scope

This guide applies to public frontend themes such as:

- `templates/amida`
- `templates/<new_theme>`

It does not apply to:

- `templates/admin`, which is the active phone-only admin panel.
- `templates/base_static`, which contains shared legacy/vendor JavaScript assets.
- runtime uploaded assets under `static/`.

If a theme task touches admin templates, the admin mobile-only rule applies: design and validate only for phone-sized admin surfaces.

## Current Architecture Facts

Before creating a theme, verify the current repository state. At the time this guide is written, the active public theme folder in this worktree is `templates/amida`.

The public theme is selected as follows:

- `config/site.config.php` sets `$site['app_template'] = 'amida'`.
- If the `theme` cookie is present, the value is sanitized with `basename(Req::Clear(...))`.
- The cookie value is accepted only when `template_dir . <cookie_value>` exists and is a directory.
- The selected value is registered with `Registry::add('app_template', $site['app_template'])`.
- `lib/TwigTemplate.class.php` loads Twig paths in this order:
  - `templates/admin`
  - `templates/<active_app_template>`
  - `templates`

Important consequences:

- Includes such as `{% extends "layouts/base.twig" %}` resolve through the active public theme.
- Includes such as `{% include "index/footer.twig" %}` resolve through the active public theme.
- Hardcoded includes such as `{% include "amida/..." %}` pin the old theme and must not be introduced.
- Asset URLs should normally use `{{ site.taddress }}{{ site.app_template }}/...` so they follow the active theme folder.
- `{{ site.iaddress }}` is for runtime static storage, not theme assets.

## Mandatory Rules

Every new theme must satisfy these rules before it is considered complete:

1. No visible UI text may be hardcoded in Twig, JavaScript, or PHP output.
2. No visible localization expression may use `default(...)`, inline fallback text, or equivalent fallback behavior.
3. Missing visible text must be solved by adding or wiring real language keys.
4. All new technical names must be English: folder names, file names, CSS classes, JavaScript identifiers, translation keys, and template names.
5. Do not leave old and new template names, routes, validators, aliases, or keys alive together unless compatibility retention is explicitly requested.
6. Do not leave Turkish, Kurdish, or other non-English comments in touched files.
7. Do not create a theme by visual reskin only; preserve the runtime behavior of the active frontend.
8. Do not copy references to non-existent theme folders.
9. Do not introduce commercial or foreign module templates (`mod_content`, `mod_product`, `mod_service`, `mod_project`, `mod_client`, `mod_reference`, `mod_hero_slider`) that violate `AF/Bulletin` domain boundaries.
10. Treat shared-core refactoring work as a shared-core candidate unless a local-only boundary is explicitly documented.

## Required Theme Contract

A complete public theme should provide this structure:

```text
templates/<theme>/
  index/
  layouts/
  macros/
  mod_user/
  partials/
  static/
    css/
    img/
    js/
    scss/
```

The exact asset subfolders may vary, but the theme must cover the same public rendering surfaces as the active frontend.

At minimum, audit and preserve coverage for:

- `layouts/base.twig`
- `index/index.twig`
- `index/footer.twig`
- `index/search.twig`
- `index/mod_menu/stacked_top_menu.twig`
- `index/mod_slider/slider.twig`
- `index/mod_headline/*`
- `index/mod_news/*`
- `index/mod_page/*`
- `index/mod_publication/*`
- `index/mod_video_embed/*`
- `index/mod_imagecategories/*`
- `mod_user/*`
- `partials/_cookie_consent.twig`
- `partials/_popup.twig`
- `partials/mod_gallery.twig`
- `partials/share.twig`

If the active repository gains or loses modules, regenerate this inventory before implementation.

## Agent And Skill Usage

Use repository guidance first:

1. Read `.agents/INDEX.md`.
2. Read `docs/CODE_QUALITY_AGENT_STRATEGY.md`.
3. Read `.agents/quality/manifest.md`.
4. Use `.agents/skills/frontend-theme/SKILL.md` for the compact operational workflow.
5. Use this guide for the detailed theme-specific contract.
6. For visual theme creation or substantial redesign, use `.agents/design/INDEX.md` after this guide to load only the public theme design contract and one matching profile.

Design documents provide visual direction only. They do not replace the runtime contract in this guide, localization policy, quality-agent policy, or version-log rules.

Use subagents only when the user explicitly asks for agent delegation or parallel agent work.

Recommended delegated tasks:

- Explorer 1: inventory active public theme files, includes, extends, asset paths, and context keys.
- Explorer 2: locate theme activation and theme-switching behavior in config, Twig, JavaScript, and cookie usage.
- Worker 1: create the new theme folder and update only `templates/<theme>`.
- Worker 2: update registration or selector files if the user wants the new theme exposed through UI controls.
- Final review: apply `i18n-guard`, `comment-guard`, `naming-guard`, `template-guard`, `cleanup-guard`, then `policy-gate`.

When assigning a dedicated theme agent, use:

- `.agents/theme/prompts/frontend-theme-agent.md`

Available skills:

- Use `frontend-theme` for public frontend theme creation, audit, registration, and validation work.
- Use `lang-refactor` when new theme work requires replacing numeric or non-English language keys with descriptive English keys and propagating those key changes across the codebase.
- Use `imagegen` only when the theme needs generated raster assets such as images, textures, sprites, or mockups. Do not use it for code-native SVG, icons, CSS, or existing vector systems.

## Workflow

### 1. Verify The Starting State

Run a fresh inventory before every theme task.

```bash
find templates -maxdepth 2 -type d | sort
```

```bash
find templates/amida -maxdepth 3 -type f | sort
```

```bash
rg -n "app_template|\$_COOKIE\['theme'\]|template_dir|site\.app_template|site\.taddress" config lib templates/amida
```

Confirm:

- Which public theme is the real baseline.
- Whether `templates/<new_theme>` already exists.
- Whether any referenced old theme folder is missing.
- Which files will be touched.

### 2. Choose A Theme Name

Use a unique lowercase folder name:

```text
templates/<theme>
```

Rules:

- Use lowercase ASCII.
- Use English naming.
- Avoid spaces, uppercase letters, Turkish characters, and marketing labels that are likely to change.
- Use the same value in folder names, cookie values, selectors, tests, and docs.

Examples:

- `studio`
- `editorial`
- `portfolio`
- `soul`

### 3. Select Design Direction

For a durable visual theme or substantial redesign:

1. Read `.agents/design/INDEX.md`.
2. Read `.agents/design/public-theme/DESIGN.md`.
3. Select at most one public theme profile unless the user explicitly requests a hybrid direction.
4. Draft `templates/<theme>/DESIGN.md` from `.agents/design/public-theme/theme-design-template.md` before visual implementation.

Skip shared design profiles for small parity fixes, registration-only tasks, or non-visual audits.

The theme-local `DESIGN.md` should document durable identity decisions, not implementation history or copied code.

### 4. Decide The Build Strategy

Use one of these strategies.

Preferred for production work:

1. Copy `templates/amida` as a temporary scaffold.
2. Immediately clean inherited references.
3. Rebuild visual design and assets inside `templates/<theme>`.
4. Preserve module coverage from the baseline.

Preferred for experimental work:

1. Create the folder manually.
2. Bring over only the required layout, partial, and module files.
3. Keep a checklist proving that every active route still has a template.

Do not start from a folder that does not exist in this repository.

### 5. Scaffold The Folder

If using `amida` as the scaffold:

```bash
cp -a templates/amida templates/<theme>
```

After copying, do not begin styling first. First remove technical inheritance mistakes.

Search for old theme references:

```bash
rg -n "templates/amida|amida/static|amida/|artist|new|new_theme|template.php" templates/<theme>
```

Fix every unintended reference before continuing.

Allowed references:

- Shared `templates/base_static` assets when the baseline already depends on them and the dependency is intentional.
- Runtime assets under `static/` or `site.iaddress` when they come from CMS settings or uploaded content.

### 6. Stabilize The Base Layout

Start with:

```text
templates/<theme>/layouts/base.twig
```

Preserve:

- charset, viewport, color-scheme, canonical, title, description, keywords
- Open Graph and Twitter metadata
- favicon, Apple touch icon, and web manifest from `mod_settings`
- `site.available_languages` hreflang output
- Google Analytics support when configured
- Bootstrap and required plugin dependencies unless intentionally replaced in the same pass
- `run({"mod": "index", "method": "_menu"})`
- `ctx_get('javascript')`
- `ctx_get('jquery_document')`
- cookie consent include
- footer include

Use active-theme asset paths:

```twig
{{ site.taddress }}{{ site.app_template }}/static/css/custom.css
```

Do not use hardcoded paths like:

```twig
templates/amida/static/css/custom.css
```

### 7. Preserve Navigation Behavior

The public header is not static markup. It depends on the framework menu pipeline:

```twig
{{ run({"mod": "index", "method": "_menu"}) }}
```

The menu helper provides:

- `ctx_get('menu_header')`
- `ctx_get('menu_header_root_id')`
- `ctx_get('menu_footer')`
- `ctx_get('menu_footer_root_id')`

The new theme must preserve:

- localized header menu rendering
- footer menu rendering
- nested menu behavior
- mobile navigation behavior
- search entry point
- language switcher
- dark mode toggle if the baseline exposes it
- login/register entry points
- logged-in user account actions
- message count display
- add-content shortcuts when available

Visible labels must use language keys from `mod/index/lang/*.php` or the relevant module language pack.

### 8. Preserve Homepage Composition Deliberately

`templates/amida/index/index.twig` currently composes the homepage from active module feeds and helper outputs.

Before changing the homepage, list every helper/template include in the baseline:

- headline / hero slider
- video follow-up
- latest authors / columnists
- latest news & category feeds
- latest publications
- bottom menu

If the new theme intentionally removes or reorders a homepage block, document the product decision in the task summary or plan. Do not remove a module accidentally because a copied template did not include it.

Homepage design may change, but the data must still come from CMS-managed modules or language files. Do not add unmanaged marketing copy directly into Twig.

### 9. Preserve Module Templates

For each module folder under `templates/amida/index/`, decide one of:

- copy and restyle
- rewrite with the same context contract
- intentionally remove only with explicit user approval

When rewriting, preserve the context variables used by the module helper/controller. Examples include:

- `ctx_get('d')`
- `ctx_get('categories')`
- `ctx_get('res')`
- `ctx_get('file')`
- `ctx_get('video')`
- `ctx_get('pagination')`
- `ctx_get('page')`

Do not rename context keys unless the producing PHP helper/controller is updated in the same pass.

### 10. Preserve Authentication Screens

The theme must keep public user flows working:

- login
- register
- activation
- resend activation
- forgot password
- password reset
- account-related email templates

The login modal in the header and full-page `mod_user` templates must share the same language coverage and AJAX behavior expected by the current baseline.

Any new validation or snackbar copy must be wired through language keys.

### 11. Preserve Shared Frontend Behaviors

The new theme must keep these behaviors functional:

- cookie consent storage and hide/show behavior
- popup rendering through `run({"mod": "index", "method": "_popup"})` where enabled
- gallery/lightbox rendering
- share UI
- dark/light mode persistence when used
- language switching
- theme cookie activation
- responsive public layout
- module-specific CSS and JavaScript initialization

If replacing a library, replace the full behavior in the same pass and remove dead dependencies.

### 12. Register Or Expose The Theme

The loader already accepts a `theme` cookie when a matching folder exists. Creating `templates/<theme>` is enough for low-level activation by cookie.

Only change configuration or UI when the task requires it.

Review:

- `config/site.config.php`
- `templates/amida/index/mod_menu/stacked_top_menu.twig`
- any JavaScript that sets or reads the `theme` cookie

Use `template.php` for theme gallery switching.

If exposing the theme in a selector, add all visible labels through language files and update every active language pack for the touched module.

### 13. Localize New Copy

Public shell copy usually belongs in:

```text
mod/index/lang/*.php
```

User-flow copy usually belongs in:

```text
mod/mod_user/lang/*.php
```

Module-owned copy belongs in that module's language files:

```text
mod/<module>/lang/*.php
```

Rules:

- Add descriptive English keys.
- Update all existing language files for the touched module.
- Do not add a key in only one language file.
- Do not use `|default('...')` to hide missing keys.
- Do not leave numeric or non-English key names in new work.
- Use `lang-refactor` if a key migration is large or risky.

### 14. Build Assets Inside The Theme

Theme-specific assets belong under:

```text
templates/<theme>/static/
```

Use:

- `static/css` for compiled CSS
- `static/scss` for SCSS sources if used
- `static/js` for theme-owned JavaScript
- `static/img` for theme-owned images

Keep assets scoped to the theme. Do not modify `templates/base_static` unless the task is explicitly about shared vendor/runtime behavior.

JavaScript rules:

- Do not render visible strings from inline literals.
- Export server-provided language strings into JS only through localized Twig variables or structured language payloads.
- Keep selectors stable and theme-scoped.
- Remove copied dead code.

CSS rules:

- Do not rely on CSS `content` for user-visible text.
- Keep layout responsive for the public frontend.
- Avoid one-off class names inherited from an old visual direction when they no longer describe the new component.

### 15. Clear Caches When Needed

If rendered Twig or assets do not update, clear only the relevant caches:

```text
templates_c
cache/css
cache/js
```

Use the project's cache-clearing workflow when available.

### 16. Validate The Theme

Run file and reference checks:

```bash
find templates/<theme> -type f | sort
```

```bash
rg -n "amida/|templates/amida|artist|new|new_theme|template.php" templates/<theme>
```

```bash
rg -n "\\|default\\(|TODO|FIXME|lorem|placeholder" templates/<theme>
```

```bash
rg -n ">[A-Za-z][^<{]*<|placeholder=\"[^\"]+\"|title=\"[^\"]+\"|aria-label=\"[^\"]+\"" templates/<theme> --glob "*.twig"
```

The last search is a review aid, not an automatic failure. Verify whether each match is localized, machine-only, or genuinely visible hardcoded text.

If design documents were used or changed, also check:

```bash
find .agents/design -maxdepth 3 -type f | sort
```

```bash
rg -n "default\\(|lorem|placeholder|TODO|FIXME" .agents/design
```

For durable visual themes, confirm `templates/<theme>/DESIGN.md` exists and matches the selected public design profile.

Manually test the main public routes:

- homepage
- search results
- header navigation with nested menus
- footer navigation
- language switcher
- dark/light mode
- login modal
- register page
- forgot-password and reset-password pages
- page detail
- news list and detail
- publication list and detail
- gallery
- video embed list and detail
- cookie consent
- popup-enabled route if available

Browser validation should include public desktop and mobile sizes. If any admin screen is touched, validate that admin work only in phone-sized viewports.

### 17. Run Quality Review

Before finishing, perform a short touched-file review.

Apply these checks in order:

1. `i18n-guard`: no hardcoded visible copy, no missing keys, no fallback localization.
2. `comment-guard`: no non-English comments in touched files.
3. `naming-guard`: no newly introduced non-English identifiers.
4. `template-guard`: no visible template/JS copy bypassing language files.
5. `cleanup-guard`: no leftovers, dead copied files, stale references, or half-refactors.
6. `policy-gate`: consolidate only actionable findings.

Do not expand the review into unrelated legacy cleanup.

## Version Logging

When the theme task changes repository documentation or implementation, update `docs/VERSION.md` according to the repository version rule.

Before writing any version note, re-run the checks from the start:

```bash
git log --oneline --decorate -n 20
```

```bash
git status --short
```

```bash
sed -n '1,80p' docs/VERSION.md
```

Then:

- Treat the latest Git-uploaded version as shipped.
- Do not append unpublished work under a shipped section.
- Create or reuse the next unpublished version section.
- Keep shipped sections frozen unless the user explicitly asks to repair historical notes.

## Completion Checklist

The new theme is complete only when all of these are true:

- `templates/<theme>` exists and contains the required frontend structure.
- Durable visual themes include `templates/<theme>/DESIGN.md` with the selected design direction.
- The theme can render through the `theme` cookie or configured `app_template`.
- No unintended `default`, stale theme, or missing-folder references remain.
- Layout metadata, SEO tags, favicons, manifest, analytics, and context script injection still work.
- Header and footer menus still come from the framework menu pipeline.
- Search, language switching, dark mode, theme activation, auth, cookie consent, popup, gallery, share, and module pages still work.
- Every visible string introduced by the task is localized across all active language files for that module.
- No `default(...)` or inline fallback copy masks missing localization.
- No non-English comments remain in touched files.
- No copied dead files or half-replaced templates remain.
- Public desktop and mobile routes have been checked.
- Any touched admin surface was validated only as a phone-only interface.
- `docs/VERSION.md` has the correct unpublished note when the task requires a version entry.
