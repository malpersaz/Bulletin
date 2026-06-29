# Quality Agent Manifest

## Purpose

This manifest defines how repository quality agents are expected to operate during normal development work.

It is the operational companion to:

- `/CODE_QUALITY_AGENT_STRATEGY.md`

## Active Agents

- `i18n-guard`
- `comment-guard`
- `naming-guard`
- `template-guard`
- `cleanup-guard`
- `modernization-guard`
- `parity-guard`
- `policy-gate`

## Source Prompts

- `/.agents/quality/prompts/shared-diff-scope.md`
- `/.agents/quality/prompts/i18n-guard.md`
- `/.agents/quality/prompts/comment-guard.md`
- `/.agents/quality/prompts/naming-guard.md`
- `/.agents/quality/prompts/template-guard.md`
- `/.agents/quality/prompts/cleanup-guard.md`
- `/.agents/quality/prompts/modernization-guard.md`
- `/.agents/quality/prompts/parity-guard.md`
- `/.agents/quality/prompts/policy-gate.md`

## Scope Model

The repository uses mixed scope enforcement.

### Untouched Files

Untouched files are protected from cleanup noise.

Agents must not report unrelated legacy debt in untouched files.

### Touched Files

Touched files are subject to file-wide enforcement only for:

- non-English comments
- non-English hard-coded user-facing strings

All other categories remain diff-scoped by default.

## Blocking Rules

The following are blocking `Error` conditions:

- a non-English comment remains inside a touched file
- a non-English hard-coded user-facing string remains inside a touched file
- a newly introduced hard-coded user-facing string bypasses localization
- a newly introduced non-English identifier appears in the diff
- a newly introduced required language key is missing
- a visible UI string in the touched flow uses `default(...)`, inline fallback text, or any equivalent fallback mechanism
- a touched visible flow is left without complete localization coverage
- old and new keys, routes, methods, validators, templates, or aliases are left alive together after a refactor without explicit user approval
- a touched module is left in a half-refactored state
- an admin UI change is built or validated primarily for tablet/desktop instead of the required phone-only target
- an admin phone flow is left with non-essential chrome that materially blocks the primary task in the initial viewport

## Non-Blocking Rules

The following are usually `Warning` conditions:

- a newly added translation key is unused
- cleanup leftovers introduced by the current task
- compatibility code added without clear rationale
- non-critical English naming inconsistency

## Standard Review Order

1. Primary implementation agent completes the change.
2. `i18n-guard` reviews localization issues.
3. `comment-guard` reviews comment policy.
4. `naming-guard` reviews naming in the diff.
5. `template-guard` reviews UI-layer strings.
6. `cleanup-guard` reviews leftovers.
7. `modernization-guard` reviews alignment with the modernization standard.
8. `parity-guard` reviews synchronization and parity with sibling repositories.
9. `policy-gate` consolidates final findings.

## Standard Decision Rule

If a finding targets:

- an untouched file
- an untouched hunk
- unrelated repository-wide legacy debt

it must be dropped unless the user explicitly requested a legacy audit.

## Touched-File Language Rule

If a file is part of the active task, then the file must not leave behind:

- Turkish comments
- Kurdish comments
- other non-English comments
- Turkish hard-coded user-facing strings
- Kurdish hard-coded user-facing strings
- other non-English hard-coded user-facing strings

Those must be:

- rewritten in English
- moved into localization structures where appropriate
- or removed

## What This Manifest Does Not Force

This manifest does not automatically require file-wide cleanup for:

- legacy variable names
- legacy method names
- legacy filenames
- legacy translation key names

Those stay diff-scoped unless a separate cleanup directive is given.

## Output Expectations

All agents should:

- be concise
- report only actionable findings
- avoid repository-wide commentary
- avoid repeating the same issue already covered by another agent

## Version Log Rule

When a task is completed and repository documentation is updated, agents must treat `/docs/VERSION.md` as the single version log.

- Before appending any completed development note, inspect the latest Git-uploaded version code from the current repository history.
- Every time a version-note update is needed, re-run the version-log checks from the start.
- Before writing any version note, verify the latest Git-uploaded version from actual Git history first; do not infer it only from `/docs/VERSION.md` or from the current worktree state.
- After identifying that Git-uploaded version, inspect the developments that exist after it and compare them against `/docs/VERSION.md`.
- Treat the latest Git-uploaded version as the latest shipped version, and keep its section frozen unless the user explicitly asks to repair shipped historical notes.
- If still-unuploaded developments are missing from `/docs/VERSION.md`, do not backfill them under the shipped version section; create or use the next unpublished version section and place those notes there first.
- When the current task represents unpublished work after that Git version, create the next version section in `/docs/VERSION.md` first and append the completed notes under that new version code.
- If the current task continues the same unpublished cycle, keep using the already-open next unpublished version section instead of the shipped version section.
- Before appending to any existing version section, explicitly verify whether the note belongs to a shipped Git version or to the next unpublished version.
- A user-provided version code overrides the auto-detected Git version for that task.
- All version notes written in `/docs/VERSION.md` must be written in English. Do not write version notes in Kurdish or Turkish, even if communicating with the user in Kurdish.
- Before proposing any walkthrough or finishing a task, the agent must run the `task-validation` skill checks.

## Repository Boundary Rule

The following modules are `Rojname`-only:

- `mod_news`
- `mod_news_category`
- `mod_publication`
- `mod_publication_category`

Agents must preserve them as project-specific modules and must not propose them as shared-core changes for sibling projects unless the user explicitly overrides the rule.

The following installer decisions are also `Rojname`-specific and must remain local unless the user explicitly overrides the rule:

- `Rojname` branding/default project name
- removal of default sample menu/category/content seeds

Outside those project-specific boundaries, refactoring work should be treated as shared-core candidate work and reviewed for synchronization across sibling repositories.

## Synchronization Rule

When updating a module, agents must apply policy during implementation.

- update the module and apply agent rules in the same pass
- remove Turkish comments from touched files
- do not use `default(...)`, inline fallback text, or hard-coded fallback strings for visible UI localization
- if a visible localization key is missing, add or wire the key instead of leaving fallback text in place
- require complete multilingual wiring in the touched flow before considering the task done
- do not keep old and new keys, routes, methods, validators, templates, or aliases alive together unless the user explicitly requests compatibility retention
- do not leave half-completed refactors behind
- perform a short review before moving to the next module
- treat all admin UI work as phone-only unless the user explicitly overrides the rule
- validate admin flows with phone-sized viewports or the repository phone simulator, not tablet/desktop assumptions
- prefer compact task-first mobile layouts over decorative top chrome when vertical space is limited

## Policy Gate Responsibility

`policy-gate` is the final arbiter.

It must:

- merge duplicate findings
- remove invalid legacy-only findings
- preserve touched-file language-rule violations
- present a short final list of errors and warnings

## Success Condition

The manifest is being followed correctly when:

1. untouched legacy files stay quiet
2. touched files get visibly cleaner
3. English-only comment and visible-string normalization happens incrementally
4. development is not blocked by unrelated historical debt
