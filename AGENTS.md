# AGENTS

Repository-local agent documentation lives under:

- `/.agents/INDEX.md`

Primary quality-agent policy:

- `/CODE_QUALITY_AGENT_STRATEGY.md`

Use the index first to navigate prompts, manifests, and examples.

Version logging rule:

- `/docs/VERSION.md` is the source of truth for shipped and in-progress version notes.
- Until the user provides a new Git upload version code, append new development notes under the current active version section in `/docs/VERSION.md`.
- The current active version code is `v1.0.49.8`.

Repository boundary rule:

- The following modules are `Rojname`-only and must remain project-specific unless the user explicitly changes the rule:
  - `mod_news`
  - `mod_news_category`
  - `mod_publication`
  - `mod_publication_category`
- The following installer decisions are also `Rojname`-specific and must stay local unless the user explicitly changes the rule:
  - using `Rojname` as installer branding/default project name
  - excluding `tr` and `ar` from installer default language setup
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
