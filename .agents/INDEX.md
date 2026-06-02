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

- `/CODE_QUALITY_AGENT_STRATEGY.md`
  - primary quality-agent policy

- `/PILOT_AGENT_REVIEW_FLOW.md`
  - practical first-pass review workflow

## Quality Agents

- `/.agents/quality/README.md`
  - quality agent folder overview

- `/.agents/quality/manifest.md`
  - operational rules for quality agents

- `/.agents/quality/examples.md`
  - concrete examples of what to report and what to ignore

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

- `/.agents/quality/prompts/policy-gate.md`
  - final review consolidation prompt

## Recommended Reading Order

1. `/CODE_QUALITY_AGENT_STRATEGY.md`
2. `/PILOT_AGENT_REVIEW_FLOW.md`
3. `/.agents/quality/manifest.md`
4. `/.agents/quality/examples.md`
5. prompt files under `/.agents/quality/prompts/`

## Current Policy Summary

The repository currently uses mixed enforcement:

- untouched files stay protected from cleanup noise
- touched files are file-wide enforced for:
  - non-English comments
  - non-English hard-coded user-facing strings
- other categories remain diff-scoped by default

## Maintenance Rule

When quality-agent policy changes, update these files together:

- `/CODE_QUALITY_AGENT_STRATEGY.md`
- `/.agents/quality/manifest.md`
- `/.agents/quality/examples.md`
- any affected prompt files

## Version Logging Rule

- `/docs/VERSION.md` is the repository source of truth for version history.
- Before appending any completed development note, inspect the latest Git-uploaded version code from the current repository history.
- Every time a version-note update is needed, re-run the version-log checks from the start.
- Before writing any version note, verify the latest Git-uploaded version from actual Git history first; do not infer it only from `/docs/VERSION.md` or from the current worktree state.
- After identifying that Git-uploaded version, inspect the developments that exist after it and compare them against `/docs/VERSION.md`.
- If still-unuploaded developments are missing from `/docs/VERSION.md`, backfill those notes into the correct active version section before appending the current task note.
- When the current task represents a new upload after that Git version, create the next version section in `/docs/VERSION.md` first and append the completed notes under that new version code.
- If the current task does not introduce a new upload version, use the newest Git-uploaded version section in `/docs/VERSION.md` as the active target; if that section is missing, create it first and then append the note there.
- A user-provided version code overrides the auto-detected Git version for that task.

## Repository Boundary Rule

- The following modules are `Rojname`-only:
  - `mod_news`
  - `mod_news_category`
  - `mod_publication`
  - `mod_publication_category`
- Agents must preserve them as project-specific modules and must not treat them as shared-core candidates for `AKMbusiness`.
- The following installer decisions are also `Rojname`-specific:
  - `Rojname` branding/default project name
  - removing default sample menu/category/content seeds
- Other refactoring work should be treated as shared-core candidate work and compared for sibling-repo synchronization.

## Admin Mobile Rule

- The admin panel is a phone-only product surface.
- Agents must design, implement, and validate admin interfaces for phone screens only.
- Tablet and desktop admin layouts are out of scope unless the user explicitly overrides the rule.
- Validation and browser checks for admin work must use phone-sized viewports or the repository's phone simulator shell.
