# Agents Directory

This directory contains repository-local agent definitions, prompts, policies, and supporting documents.

## Purpose

Keep agent-related assets separate from application code so they can evolve independently.

## Structure

- `quality/`
  - quality guard definitions
  - prompts
  - checklists
  - enforcement notes
- `theme/`
  - public theme agent prompts and manifest
- `design/`
  - theme profiles and admin design contracts
- `skills/`
  - workflow helper scripts and skill descriptors

## Rule

Agent assets in this directory must support `diff-scoped` review and must not target untouched legacy code unless explicitly requested.

Agent workflows must also respect the repository version log in `/docs/VERSION.md`.

- Before appending any development note, inspect the latest Git-uploaded version code from the current repository history.
- Every time a version-note update is needed, re-run the version-log checks from the start.
- Before writing any version note, verify the latest Git-uploaded version from actual Git history first; do not infer it only from `/docs/VERSION.md` or from the current worktree state.
- After identifying that Git-uploaded version, inspect the developments that exist after it and compare them against `/docs/VERSION.md`.
- Treat the latest Git-uploaded version as the latest shipped version, and keep its section frozen unless the user explicitly asks to repair shipped historical notes.
- If still-unuploaded developments are missing from `/docs/VERSION.md`, do not backfill them under the shipped version section; create or use the next unpublished version section and place those notes there first.
- When the current task represents unpublished work after that Git version, create the next version section in `/docs/VERSION.md` first and append the completed notes under that new version code.
- If the current task continues the same unpublished cycle, keep using the already-open next unpublished version section instead of the shipped version section.
- Before appending to any existing version section, explicitly verify whether the note belongs to a shipped Git version or to the next unpublished version.
- A user-provided version code overrides the auto-detected Git version for that task.

Agent workflows must also respect repository boundaries.

- These modules are `Bulletin`-specific and must not be treated as shared modules for sibling projects:
  - `mod_news`
  - `mod_news_category`
  - `mod_publication`
  - `mod_publication_category`
- `Bulletin`-specific installer branding, language-selection, and seed-content decisions must also stay local.
- Other refactoring work should be assumed shareable unless the user marks it as project-specific.

