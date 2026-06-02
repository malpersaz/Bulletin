# Theme Agent Manifest

## Purpose

Define how repository theme agents create, audit, and validate public frontend themes safely.

## Active Agent

- `frontend-theme-agent`

## Source Prompt

- `.agents/theme/prompts/frontend-theme-agent.md`

## Primary Skill

- `.agents/skills/frontend-theme/SKILL.md`

## Source Of Truth

The implementation procedure is referenced in:

- `docs/NEW_THEME_CREATION_GUIDE.md` when it exists

Visual design direction is routed through:

- `.agents/design/INDEX.md`

Repository policy is defined in:

- `/CODE_QUALITY_AGENT_STRATEGY.md`
- `.agents/quality/manifest.md`

## Scope

Theme agents may inspect:

- current diff
- `templates/default`
- `templates/<new_theme>`
- `config/site.config.php`
- `config/site.config.example.php`
- `lib/TwigTemplate.class.php`
- public frontend controllers/helpers needed to understand context keys
- relevant language files under `mod/*/lang/`
- `.agents/design/INDEX.md`
- one relevant public theme design profile
- `templates/<theme>/DESIGN.md` when it exists or is being created

Theme agents must avoid:

- unrelated admin modernization work
- repository-wide cleanup outside the requested theme task
- sibling-repository assumptions
- references to missing theme folders as implementation facts

## Blocking Rules

The following block completion:

- public theme created without preserving the active frontend template contract
- unintended hardcoded dependency on `templates/default` or a missing theme folder
- visible hardcoded UI copy introduced in Twig, JavaScript, or PHP output
- visible localization fallback such as `default(...)` or inline fallback copy
- missing language keys in a touched visible flow
- newly introduced non-English technical naming
- non-English comments left in touched files
- copied placeholder, TODO, lorem ipsum, or dead theme files left in the result
- `docs/VERSION.md` updated under a shipped Git version for unpublished work

## Review Order

1. Confirm the real theme baseline from the worktree.
2. Confirm theme activation through `app_template` and the `tema` cookie.
3. For visual creation or redesign, confirm the selected design profile and local `templates/<theme>/DESIGN.md`.
4. Review layout, navigation, footer, homepage, module templates, auth, cookie consent, popup, gallery/share, and context script injection.
5. Review asset paths and old theme references.
6. Review localization coverage.
7. Run the quality-agent policy checks.
8. Verify version-log placement if the task changed docs or implementation.

## Success Condition

A theme-agent task is complete when the requested theme work is implemented or reviewed, active frontend behavior is preserved or intentionally documented as changed, quality policy passes under the mixed scope model, and the version log reflects unpublished work correctly when required.
