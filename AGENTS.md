# AGENTS

Repository-local agent documentation lives under:

- `/.agents/INDEX.md`

Primary quality-agent policy:

- `/CODE_QUALITY_AGENT_STRATEGY.md`

Use the index first to navigate prompts, manifests, and examples.

Version logging rule:

- `/docs/VERSION.md` is the source of truth for shipped and in-progress version notes.
- Before appending any new development note, inspect the latest Git-uploaded version code from the current repository history.
- When the current task represents a new upload after that Git version, create the next version section in `/docs/VERSION.md` first and append the completed notes under that new version code.
- If the current task does not introduce a new upload version, use the newest Git-uploaded version section in `/docs/VERSION.md` as the active target; if that section does not exist yet, create it first and then append the note there.
- A user-provided version code still overrides the auto-detected Git version for that task.

Repository boundary rule:

- The following modules are `Rojname`-only and must remain project-specific unless the user explicitly changes the rule:
  - `mod_news`
  - `mod_news_category`
  - `mod_publication`
  - `mod_publication_category`
- The following installer decisions are also `Rojname`-specific and must stay local unless the user explicitly changes the rule:
  - using `Rojname` as installer branding/default project name
  - removing default sample menu/category/content seeds from installer setup
- Refactoring work outside those project-specific decisions should be treated as shared-core candidate work for sibling-repository synchronization.

Module synchronization rule:

- When updating a module, the agent must apply agent rules during the same pass.
- The agent must not leave Turkish comments in touched files.
- The agent must not use `default(...)`, inline fallback text, or any other fallback mechanism to hide missing localization keys in visible UI output.
- If a visible string needs localization and the required key does not exist, the agent must add or wire the correct key instead of leaving a fallback behind.
- The agent must treat full multilingual coverage in the touched flow as mandatory, not optional.
- The agent must not keep old and new keys, routes, methods, validators, templates, or aliases alive together unless the user explicitly requests compatibility retention.
- The agent must not leave a half-completed refactor behind.
- Before moving to the next module, the agent must perform a short review of the touched files and fix any obvious rule violations.

Admin mobile rule:

- The entire admin panel must be treated as a phone-only product surface.
- Agents must develop admin UI for phone screens only; tablet and desktop layouts are not target platforms unless the user explicitly overrides this rule.
- Agents must not use tablet-first, desktop-first, or responsive desktop assumptions when designing, implementing, or reviewing admin interfaces.
- Admin UI decisions must optimize for fixed phone-sized app shells, touch interaction, compact vertical space usage, and one-hand usability.
- During implementation and validation, agents must test admin UI flows only against phone-sized viewports or the repository's phone simulator shell.
- Tablet and desktop visual polish, breakpoint tuning, and layout validation must not delay or redirect admin work unless the user explicitly requests them.
