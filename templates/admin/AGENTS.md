# Admin Templates Agent Rules

Inherit the repository root `AGENTS.md` and `templates/AGENTS.md`.

## Product Surface

The admin panel is a phone-only product surface.

- Design for phone-sized app shells only.
- Validate admin UI only in phone-sized viewports or the repository phone simulator shell.
- Do not spend effort on tablet or desktop layout polish unless the user explicitly requests it.
- Prefer compact vertical composition, touch-sized controls, and one-hand usability.
- Keep primary tasks visible early in the phone viewport; avoid decorative chrome that pushes the task below the fold.

For admin layout, form, or list design work, read `.agents/design/admin/DESIGN.md` and only the matching admin profile after this file. Do not load public theme design profiles for admin work.

## Scope

This folder contains the active admin Twig templates and admin assets.

Relevant surfaces include:

- `templates/admin/layout.twig`
- `templates/admin/partials/`
- `templates/admin/assets/css/`
- `templates/admin/assets/js/`
- `templates/admin/mod_*`
- `templates/admin/pages/`
- `templates/admin/management/`

Do not use public theme assumptions from `templates/default` or future public themes when working here.

## Localization

- Do not add visible hardcoded copy in Twig or JavaScript.
- Do not use `default(...)`, inline fallback copy, or hidden fallback mechanisms for visible admin text.
- Add or wire proper language keys in the owning module language files.
- Keep language coverage complete for the touched admin flow.
- Do not leave old and new keys, routes, validators, templates, or aliases alive together unless the user explicitly asks for compatibility retention.

## Code And Template Hygiene

- Do not leave Turkish, Kurdish, or other non-English comments in touched files.
- Keep new identifiers, CSS classes, JavaScript names, template names, and language keys in English.
- Do not copy legacy admin markup into the active admin panel without modernizing it in the same pass.
- Avoid broad cleanup outside the touched admin flow.
- Before moving to another module, review the touched files and fix obvious policy violations.

## Assets

- Keep admin-specific CSS and JavaScript under `templates/admin/assets/`.
- When admin CSS or JavaScript changes, check `templates/admin/layout.twig` for asset version query parameters and update them when the changed asset is loaded through the layout.
- Do not modify `templates/base_static` for admin-only behavior unless the task explicitly requires shared runtime asset changes.

## Validation

For admin UI changes, validate the relevant flow with phone-sized assumptions.

Minimum review:

- visible text is localized
- no visible fallback copy remains
- touched files contain no non-English comments
- controls are reachable by touch
- primary action and primary content fit the phone workflow
- no tablet/desktop-only layout dependency was introduced
