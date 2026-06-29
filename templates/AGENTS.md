# Templates Agent Rules

Inherit the repository root `AGENTS.md`.

## Scope Map

- `templates/default` and future `templates/<theme>` folders are public frontend themes.
- `templates/admin` is the active admin panel and is a phone-only admin/screen surface.
- `templates/base_static` contains shared legacy/vendor frontend assets.

## Public Theme Work

- Follow `docs/NEW_THEME_CREATION_GUIDE.md` when it exists or is referenced.
- Use `.agents/skills/frontend-theme/SKILL.md` for public theme creation, audit, registration, and validation.
- Use `.agents/theme/prompts/frontend-theme-agent.md` only when the user explicitly requests a dedicated theme agent.
- For new durable visual themes or substantial redesigns, use `.agents/design/INDEX.md`, load only the public theme baseline and one matching profile, and create or update `templates/<theme>/DESIGN.md`.
- Keep public theme includes active-theme-relative. Do not pin copied templates to `default/` unless that dependency is intentional and documented.
- Use `{{ site.taddress }}{{ site.app_template }}/...` for theme-owned assets.
- Put theme-owned assets under `templates/<theme>/static/`.

## Admin Work

- For anything under `templates/admin`, read `templates/admin/AGENTS.md` first.
- For admin layout, form, or list design work, use `.agents/design/INDEX.md` and load only the relevant admin design profile.
- Admin UI must be designed and validated only for phone-sized surfaces unless the user explicitly overrides this rule.

## Shared Asset Work

- Do not modify `templates/base_static` unless the task is explicitly about shared/vendor runtime assets.
- Prefer theme-owned assets inside the active theme folder when the change is visual or theme-specific.

## Localization And Quality

- Do not add visible hardcoded strings in Twig, JavaScript, or PHP output.
- Do not use `default(...)`, inline fallback copy, or equivalent visible localization fallback behavior.
- Add or wire real language keys when visible copy is needed.
- Do not leave Turkish, Kurdish, or other non-English comments in touched files.
- Keep new technical naming in English.
- Review touched files before leaving the `templates/` scope.
