# Agents Index

## Purpose

This file is the entry point for repository-local agent guidance.

Use it to quickly locate:

- policy documents
- operational manifests
- prompts
- examples

## Top-Level Documents

- `/.agents/README.md`
  - repository-local agent directory overview

- `/docs/VERSION.md`
  - active version log and development note target

- `/docs/CODE_QUALITY_AGENT_STRATEGY.md`
  - primary quality-agent policy

- `/docs/PILOT_AGENT_REVIEW_FLOW.md`
  - practical first-pass review workflow

## Quality Agents

- `/.agents/quality/README.md`
  - quality agent folder overview

- `/.agents/quality/manifest.md`
  - operational rules for quality agents

- `/.agents/quality/examples.md`
  - concrete examples of what to report and what to ignore

## Theme Agents

- `/.agents/theme/README.md`
  - public frontend theme agent overview

- `/.agents/theme/manifest.md`
  - operational rules for theme creation and review agents

- `/.agents/theme/prompts/frontend-theme-agent.md`
  - specialized agent prompt for creating, auditing, and registering public frontend themes

## Design Documents

- `/.agents/design/INDEX.md`
  - router for public theme and phone-only admin design documents

- `/.agents/design/public-theme/DESIGN.md`
  - baseline public frontend theme design contract

- `/.agents/design/admin/DESIGN.md`
  - baseline phone-only admin design contract

## Skills

- `/.agents/skills/admin-mobile-check/SKILL.md`
  - Admin Mobile Layout Check: audit admin templates/assets for mobile layout compliance

- `/.agents/skills/frontend-theme/SKILL.md`
  - public frontend theme creation, registration, audit, and validation workflow

- `/.agents/skills/github-release/SKILL.md`
  - GitHub Upload & Release Process: validate version parity across Git history, config/site.config.php, and docs/VERSION.md

- `/.agents/skills/i18n-guard/SKILL.md`
  - automated quality guard to audit localization completeness, code language standards, forbidden aliases, code separation, and missing keys

- `/.agents/skills/impeccable/SKILL.md`
  - Impeccable Design: design vocabulary and refinement protocol (/polish, /distill, /clarify, /audit, /critique)

- `/.agents/skills/kevanger/SKILL.md`
  - Kevanger: curate, cross-reference, and stage drafted news in mod_news via MCP with orthography validation

- `/.agents/skills/lang-refactor/SKILL.md`
  - language-key refactoring helper for descriptive English key migrations

- `/.agents/skills/module-modernization-check/SKILL.md`
  - Internal Modernization Check: module modernization standards, SQLite compatibility, and validation rules

- `/.agents/skills/task-validation/SKILL.md`
  - Task Completion & Release Validation: version logging in VERSION.md and running project quality guards before release

- `/.agents/skills/taste-skill/SKILL.md`
  - Taste Skill: anti-slop frontend design framework, brief inference, and dial controls (VARIANCE, MOTION, DENSITY)


## Prompt Set

- `/.agents/quality/prompts/shared-diff-scope.md`
  - shared scope policy

- `/.agents/quality/prompts/i18n-guard.md`
  - localization guard prompt

- `/.agents/quality/prompts/comment-guard.md`
  - comment policy guard prompt

- `/.agents/quality/prompts/naming-guard.md`
  - naming policy guard prompt

- `/.agents/quality/prompts/template-guard.md`
  - UI/template text guard prompt

- `/.agents/quality/prompts/cleanup-guard.md`
  - cleanup and leftover detection prompt

- `/.agents/quality/prompts/modernization-guard.md`
  - modernization and refactoring standard validation guard prompt

- `/.agents/quality/prompts/parity-guard.md`
  - sibling repository parity and synchronization guard prompt

- `/.agents/quality/prompts/policy-gate.md`
  - final review consolidation prompt

## Recommended Reading Order

1. `/docs/CODE_QUALITY_AGENT_STRATEGY.md`
2. `/docs/PILOT_AGENT_REVIEW_FLOW.md`
3. `/.agents/quality/manifest.md`
4. `/.agents/quality/examples.md`
5. prompt files under `/.agents/quality/prompts/`
6. for public theme work, `/.agents/skills/frontend-theme/SKILL.md`
7. for public theme agent work, `/.agents/theme/manifest.md`
8. for visual public theme or admin design work, `/.agents/design/INDEX.md`

## Current Policy Summary

The repository currently uses mixed enforcement:

- untouched files stay protected from cleanup noise
- touched files are file-wide enforced for:
  - non-English comments
  - non-English hard-coded user-facing strings
- other categories remain diff-scoped by default

## Maintenance Rule

When quality-agent policy changes, update these files together:

- `/docs/CODE_QUALITY_AGENT_STRATEGY.md`
- `/.agents/quality/manifest.md`
- `/.agents/quality/examples.md`
- any affected prompt files

## Version Logging Rule

- `/docs/VERSION.md` is the repository source of truth for version history.
- Before appending any completed development note, inspect the latest Git-uploaded version code from the current repository history.
- Every time a version-note update is needed, re-run the version-log checks from the start.
- Before writing any version note, verify the latest Git-uploaded version from actual Git history first; do not infer it only from `/docs/VERSION.md` or from the current worktree state.
- After identifying that Git-uploaded version, inspect the developments that exist after it and compare them against `/docs/VERSION.md`.
- Treat the latest Git-uploaded version as the latest shipped version, and keep its section frozen unless the user explicitly asks to repair shipped historical notes.
- Immediately upon marking a version header with a release date (pushed/shipped to GitHub), automatically open the next `## vX.X.X.XX (In Progress)` version section at the top of `/docs/VERSION.md`.
- Always synchronize `$site['version']` in `config/site.config.php` to match the active version code in `/docs/VERSION.md` in the same pass.
- If still-unuploaded developments are missing from `/docs/VERSION.md`, do not backfill them under the shipped version section; create or use the next unpublished version section and place those notes there first.
- When the current task represents unpublished work after that Git version, create the next version section in `/docs/VERSION.md` first and append the completed notes under that new version code.
- If the current task continues the same unpublished cycle, keep using the already-open next unpublished version section instead of the shipped version section.
- Before appending to any existing version section, explicitly verify whether the note belongs to a shipped Git version or to the next unpublished version.
- A user-provided version code overrides the auto-detected Git version for that task.

## Repository Boundary Rule

- The following modules are `Rojname`-only:
  - `mod_news`
  - `mod_news_category`
  - `mod_publication`
  - `mod_publication_category`
- Agents must preserve them as project-specific modules and must not treat them as shared-core candidates for sibling projects.
- The following installer decisions are also `Rojname`-specific:
  - `Rojname` branding/default project name
  - removing default sample menu/category/content seeds
- Other refactoring work should be treated as shared-core candidate work and compared for sibling-repository synchronization.

## Admin Mobile Rule

- The admin panel is a phone-only product surface.
- Agents must design, implement, and validate admin interfaces for phone screens only.
- Tablet and desktop admin layouts are out of scope unless the user explicitly overrides the rule.
- Validation and browser checks for admin work must use phone-sized viewports or the repository's phone simulator shell.
