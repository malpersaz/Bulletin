# Design Document Index

## Purpose

Route agents to the smallest design document set needed for public theme or admin template work.

Use this index after reading the applicable `AGENTS.md` files and the relevant short workflow source.

For public theme work, that usually means `.agents/skills/frontend-theme/SKILL.md` and the needed sections of `docs/NEW_THEME_CREATION_GUIDE.md` when it exists.

For admin template work, that means `templates/admin/AGENTS.md` and only the admin design baseline/profile needed for the phone-sized task.

## Loading Order

1. Read repository and local `AGENTS.md` files.
2. Read the relevant short workflow source:
   - public theme: `.agents/skills/frontend-theme/SKILL.md`
   - admin template: `templates/admin/AGENTS.md`
3. Read this index.
4. Read one baseline design contract:
   - public theme: `.agents/design/public-theme/DESIGN.md`
   - admin: `.agents/design/admin/DESIGN.md`
5. Read at most one matching profile unless the user explicitly asks for a hybrid direction.
6. Read or create `templates/<theme>/DESIGN.md` only for a durable public theme identity.

Do not load the full collection by default.

## Public Theme Profiles

Use these only for public frontend themes under `templates/<theme>`.

- `public-theme/portfolio.md`
  - Personal, studio, agency, creative-work, or case-study-led sites.
- `public-theme/editorial.md`
  - Reading-heavy sites where articles, pages, and publications dominate.
- `public-theme/content-heavy.md`
  - News, archive, media, publication, and category-heavy sites.
- `public-theme/cms.md`
  - Company, service, product, institutional, and conversion-oriented sites.

If none fit, use only `public-theme/DESIGN.md` and ask before adding a new shared profile.

## Admin Profiles

Use these only for `templates/admin` work.

- `admin/phone-shell.md`
  - App shell, top bar, navigation drawer, bottom actions, and persistent controls.
- `admin/form-flow.md`
  - Add/edit forms, wizard steps, validation, and save flows.
- `admin/list-workspace.md`
  - Lists, filters, card rows, progressive loading, and bulk actions.

Admin work must remain phone-only. Do not use public theme profiles for admin screens.

## Theme-Local Design File

Create `templates/<theme>/DESIGN.md` when creating a new durable public theme or when an existing theme gains a durable visual identity decision.

Start from:

```text
.agents/design/public-theme/theme-design-template.md
```

Keep the local file shorter than the implementation history. It should explain the identity and decisions future edits must preserve.

The local file should also record any intentional module reordering or visual quieting so future edits do not confuse profile direction with permission to drop CMS coverage.

## Guardrails

- Design docs do not override localization, quality, version-log, or theme-contract rules.
- Shared profiles are suggestions for reusable design direction, not permission to remove CMS module coverage.
- External visual inspiration requires license and provenance review before assets are copied into the repo.
- Keep `SKILL.md` as the operation sequence; keep design decisions here or in `templates/<theme>/DESIGN.md`.
