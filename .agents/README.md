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

- Current active version bucket: `v1.0.49.8`
- Until the user declares a new Git upload version code, new development notes belong under that same version heading.

Agent workflows must also respect repository boundaries.

- These modules are `Rojname`-specific and must not be treated as shared modules for `AKMbusiness`:
  - `mod_news`
  - `mod_news_category`
  - `mod_publication`
  - `mod_publication_category`
- `Rojname`-specific installer branding, language-selection, and seed-content decisions must also stay local.
- Other refactoring work should be assumed shareable unless the user marks it as project-specific.
