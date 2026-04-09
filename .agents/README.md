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

## Rule

Agent assets in this directory must support `diff-scoped` review and must not target untouched legacy code unless explicitly requested.

Agent workflows must also respect the repository version log in `/docs/VERSION.md`.

- Before appending any development note, inspect the latest Git-uploaded version code from the current repository history.
- When the current task represents a new upload after that Git version, create the next version section in `/docs/VERSION.md` first and append the completed notes under that new version code.
- If the current task does not introduce a new upload version, use the newest Git-uploaded version section in `/docs/VERSION.md` as the active target.
- If that section does not exist yet, create it first and then append the note there.
- A user-provided version code still overrides the auto-detected Git version for that task.

Agent workflows must also respect repository boundaries.

- These modules are `Rojname`-specific and must not be treated as shared modules for `AKMbusiness`:
  - `mod_news`
  - `mod_news_category`
  - `mod_publication`
  - `mod_publication_category`
- `Rojname`-specific installer branding, language-selection, and seed-content decisions must also stay local.
- Other refactoring work should be assumed shareable unless the user marks it as project-specific.
