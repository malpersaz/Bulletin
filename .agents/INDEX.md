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
- The active version bucket remains `v1.0.49.8` until the user explicitly provides a new Git upload version code.
- New completed development notes must be appended under the active version bucket until that code changes.

## Repository Boundary Rule

- The following modules are `Rojname`-only:
  - `mod_news`
  - `mod_news_category`
  - `mod_publication`
  - `mod_publication_category`
- Agents must preserve them as project-specific modules and must not treat them as shared-core candidates for `AKMbusiness`.
- The following installer decisions are also `Rojname`-specific:
  - `Rojname` branding/default project name
  - excluding `tr` and `ar` from installer default languages
  - removing default sample menu/category/content seeds
- Other refactoring work should be treated as shared-core candidate work and compared for sibling-repo synchronization.
