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
- `policy-gate`

## Source Prompts

- `/.agents/quality/prompts/shared-diff-scope.md`
- `/.agents/quality/prompts/i18n-guard.md`
- `/.agents/quality/prompts/comment-guard.md`
- `/.agents/quality/prompts/naming-guard.md`
- `/.agents/quality/prompts/template-guard.md`
- `/.agents/quality/prompts/cleanup-guard.md`
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
7. `policy-gate` consolidates final findings.

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

