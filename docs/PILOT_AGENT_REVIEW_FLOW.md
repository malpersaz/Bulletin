# Pilot Agent Review Flow

## Purpose

Define a practical first-pass workflow for using repository quality agents during normal development.

## When To Use

Use this flow for:

- feature work
- refactors
- UI changes
- localization-sensitive changes
- admin template and JS/PHP work

## Flow

### 1. Primary Implementation

One primary development agent performs the actual code change.

Responsibilities:

- implement the requested task
- keep changes scoped
- avoid introducing new policy violations
- apply agent rules during the implementation pass
- do not use `default(...)`, inline fallback text, or hard-coded fallback strings for visible localized UI
- if a visible localization key is missing, add or wire it in the same task
- do not treat partial multilingual wiring as acceptable in the touched flow
- do not keep old and new keys, routes, methods, validators, templates, or aliases alive together unless the user explicitly requests compatibility retention
- do not leave half-refactored modules behind

### 2. Localization Review

Run:

- `i18n-guard`
- `template-guard`

Purpose:

- detect hard-coded user-facing strings
- detect missing localization usage
- enforce touched-file language cleanup for visible strings

### 3. Comment Review

Run:

- `comment-guard`

Purpose:

- detect non-English comments
- enforce touched-file language cleanup for comments

### 4. Naming Review

Run:

- `naming-guard`

Purpose:

- detect new non-English identifiers introduced in the diff

## 5. Cleanup Review

Run:

- `cleanup-guard`

Purpose:

- detect leftovers created by the current task
- catch newly added unused keys or dead logic

## 6. Final Gate

Run:

- `policy-gate`

Purpose:

- merge duplicate findings
- discard invalid legacy-only findings
- keep only actionable errors and warnings

The final gate must also confirm:

- zero non-English comments remain anywhere in the module or touched files
- zero non-English identifiers (variables, methods, functions, classes, translation keys, filenames, or routes) remain in the module or touched files
- touched visible UI does not rely on `default(...)` or inline fallback text for localization
- touched visible flows are fully wired to localization sources across all module language packs
- the module is not left in a half-refactored state
- old and new names are not being kept alive together without explicit user approval

## Scope Rule

The flow uses repository mixed-scope policy.

- untouched files must stay quiet
- touched files are file-wide enforced for:
  - non-English comments
  - non-English hard-coded user-facing strings
- all other categories remain diff-scoped by default

Repository boundary:

- `AF/Bulletin` defines domain-specific journalism and publishing modules (`mod_news`, `mod_news_category`, `mod_headline`, `mod_publication`, `mod_publication_category`) and strictly forbids commercial/foreign modules via `tools/module_boundary_guard.py`.
- Unless the user explicitly creates a local-only boundary, shared-core refactoring changes should be reviewed as shared-core synchronization candidates.

## Blocking Conditions

The pilot flow should block delivery if:

- a touched file still contains a non-English comment
- a touched file still contains a non-English hard-coded user-facing string
- a new non-English identifier is introduced
- a new required localization key is missing
- a new hard-coded visible string bypasses localization
- a touched visible UI string uses `default(...)` or inline fallback text
- a touched visible flow is not fully localized end-to-end

## Non-Blocking Conditions

The pilot flow should usually warn, not block, for:

- new unused translation keys
- temporary compatibility code
- cleanup leftovers
- non-critical consistency issues

## Recommended Operating Mode

### Phase 1

Run all guards in report-only mode.

Goal:

- validate prompts
- reduce false positives
- measure review usefulness

### Phase 2

Block only on `Error` findings.

Goal:

- enforce core rules without slowing development too much

## Example Sequence

1. Developer agent implements image uploader changes in `mod_news`.
2. `i18n-guard` checks new UI strings and touched-file visible strings.
3. `comment-guard` checks the touched files for non-English comments.
4. `naming-guard` checks only new identifiers in the diff.
5. `cleanup-guard` checks for temporary leftovers.
6. `policy-gate` confirms the module is not half-refactored and that old/new aliases are not being kept together without approval.
7. Developer agent fixes remaining errors before moving to the next module.

## Success Criteria

The pilot is successful when:

1. untouched legacy files do not generate noise
2. touched files become progressively cleaner
3. visible language debt is reduced during normal work
4. developers can actually use the process without excessive friction
