# Theme Agents

This folder contains repository-local agent guidance for public frontend theme creation and review.

## Files

- `manifest.md`
  - operating rules for theme agents
- `prompts/frontend-theme-agent.md`
  - primary specialized prompt for creating, auditing, or registering public frontend themes

## Scope

Theme agents work on public frontend theme files under `templates/<theme>`.

They must not treat `templates/admin` as part of public theme work. If admin templates are touched by an explicit task, the repository phone-only admin/screen surface rule applies.

## Required Coordination

Theme agents must follow:

- `.agents/INDEX.md`
- `docs/NEW_THEME_CREATION_GUIDE.md` when it exists
- `CODE_QUALITY_AGENT_STRATEGY.md`
- `.agents/quality/manifest.md`

Theme agents must keep reviews scoped to the active theme work and must not expand into unrelated legacy cleanup.
