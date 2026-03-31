# Code Quality Agent Strategy

## Purpose

Define a safe but strict agent-based quality control model for this repository.

The goal is to let specialized review agents enforce code-quality and localization rules during development without turning every task into a repository-wide cleanup effort.

## Core Principle

The repository uses a mixed enforcement model.

- untouched files are protected from legacy cleanup noise
- touched files are subject to stronger normalization rules

## Radical Rule For Touched Files

If a file is actively being worked on, any non-English comments and any non-English hard-coded user-facing strings remaining in that file must be normalized to English immediately.

This rule is intentionally stricter than pure diff-only review.

It applies file-wide inside touched files for these categories only:

- comments
- hard-coded user-facing strings

It does not automatically apply file-wide to:

- old variable names
- old method names
- old translation key names
- old filenames

Those categories remain diff-scoped unless a separate cleanup decision is made.

## Enforcement Boundary

The repository uses this scope model:

- untouched files: diff-free, no cleanup pressure
- touched files: file-wide enforcement for non-English comments and non-English hard-coded user-facing strings
- all other policy categories: diff-scoped by default

## Why This Rule Exists

Pure repository-wide enforcement creates too much noise in a legacy-heavy codebase.

Pure diff-only enforcement leaves visible language debt behind in files that are already being edited.

This mixed model solves both problems:

- untouched legacy files stay quiet
- touched files are progressively normalized

## Enforcement Model

## Scope Rules

Agents may inspect:

- staged diff
- working tree diff
- pull request diff
- newly added files
- renamed files
- full contents of touched files, but only for:
  - non-English comments
  - non-English hard-coded user-facing strings

Agents must ignore:

- untouched legacy code
- old translation keys not touched by the current task
- old non-English variables, methods, filenames, or class names in untouched files
- repository-wide debt outside the active task

## Exception Rule

Legacy violations may only be reported when one of the following is true:

1. The user explicitly asks for a legacy cleanup audit.
2. The legacy issue is directly broken by the current change.
3. The legacy issue creates an immediate runtime or security risk in the edited flow.
4. The issue is a non-English comment or non-English hard-coded user-facing string inside a touched file.

If none of those conditions is true, the agent must stay silent about legacy problems.

## Recommended Agent Roles

## 1. `i18n-guard`

Purpose:

- prevent localization regressions in new code

Checks:

- hard-coded user-facing strings introduced in the diff
- non-English hard-coded user-facing strings anywhere inside touched files
- newly added translation keys that are unused
- newly referenced translation keys that are missing
- non-English translation key names introduced in the diff
- translation-key fallbacks such as `lang.*|default(...)` introduced in the diff

Must ignore:

- old unused translation keys outside the diff
- old missing keys outside the diff
- legacy mixed naming in existing translation arrays outside the diff
- non-English hard-coded strings in untouched files

## 2. `comment-guard`

Purpose:

- keep comments and developer-facing text standardized

Checks:

- Turkish comments added in the diff
- Kurdish comments added in the diff
- non-English inline explanations added in the diff
- any Turkish/Kurdish/non-English comments remaining inside touched files
- unnecessary comments added in the diff

Must ignore:

- non-English comments in untouched files

## 3. `naming-guard`

Purpose:

- preserve English-only naming for newly written code

Checks:

- non-English method names introduced in the diff
- non-English variable names introduced in the diff
- non-English function names introduced in the diff
- non-English class/property names introduced in the diff
- non-English filenames introduced in the diff

Must ignore:

- legacy method names outside the diff
- legacy variable names outside the diff
- old filenames outside the diff
- old public APIs kept for compatibility

## 4. `template-guard`

Purpose:

- catch direct output and presentation-layer violations in new UI work

Checks:

- hard-coded visible strings in Twig, JS, and PHP introduced in the diff
- direct UI text bypassing language files
- newly added uploader/modal labels that do not use i18n sources
- non-English visible hard-coded strings anywhere inside touched template/UI files
- Twig/UI language fallbacks that replace missing keys with inline default text

Must ignore:

- old templates not touched by the change
- non-English visible hard-coded strings in untouched files

## 5. `cleanup-guard`

Purpose:

- detect leftovers created by the current task

Checks:

- newly added dead code
- newly added unused imports
- newly added unused translation keys
- temporary compatibility code added by the current task but not wired correctly

Must ignore:

- old dead code outside the diff
- old unused keys outside the diff
- legacy structural debt outside the diff

## Severity Model

## Error

Must block delivery:

- newly introduced hard-coded user-facing string
- newly introduced Turkish/Kurdish/non-English comment
- newly introduced non-English method/variable/function/class/file name
- newly introduced missing translation key in active flow
- any non-English comment remaining inside a touched file
- any non-English hard-coded user-facing string remaining inside a touched file

## Warning

Should be reported but does not always block:

- newly added unused translation key
- newly added compatibility alias without clear deprecation note
- naming inconsistency that is English but violates local style
- cleanup leftovers created by the current task

## Allowed Exception

May remain if documented in the change:

- compatibility route alias
- database-driven legacy field name
- externally required API name
- framework or vendor naming that cannot be changed safely

## Suggested Review Workflow

1. Primary development agent implements the change.
2. Guard agents review the result using the mixed scope rule.
3. A final policy gate summarizes only real violations.
4. Developer fixes the violations before final delivery.

## Suggested Policy Gate Prompt

Use a final review instruction similar to:

`Review touched files with file-wide enforcement for non-English comments and non-English hard-coded user-facing strings. Keep all other findings diff-scoped. Do not report unrelated untouched legacy debt.`

## Rules by Category

## Hard-Coded Strings

Agents should flag:

- visible button labels
- modal titles
- helper text
- validation messages
- toast/snackbar messages
- confirmation copy
- any non-English visible hard-coded string remaining inside a touched file

Agents should not flag:

- protocol values
- enum-like machine values
- CSS classes
- route fragments
- structured backend status values such as `success` or `error`
- hard-coded strings in untouched files unless explicitly requested

## Comments

Agents should flag:

- Turkish comments added in PHP
- Turkish comments added in JS
- Turkish comments added in Twig
- Kurdish comments added in PHP
- Kurdish comments added in JS
- Kurdish comments added in Twig
- any Turkish/Kurdish/non-English comment remaining inside a touched file

Agents should allow:

- concise English comments when genuinely useful
- no comment at all when code is self-explanatory

## Naming

Agents should flag newly introduced names such as:

- `resimYukle`
- `icerikListesi`
- `dosyaAdi`
- `duzenleModal`

Agents should allow:

- existing legacy identifiers if untouched
- compatibility wrappers around legacy endpoints when documented

## Translation Keys

Agents should flag:

- new key names written in Turkish or Kurdish
- new keys added but never referenced by the current change
- new references to keys that do not exist

Agents should not flag:

- old unused keys outside the diff
- old translation naming debt outside the diff

## Filenames

Agents should flag newly introduced files with non-English names.

Examples to reject:

- `resim_yukleyici.twig`
- `duzenle_helper.js`
- `yayin_kontrol.php`

Examples to allow:

- `image_uploader.twig`
- `edit_helper.js`
- `publication_guard.php`

## Legacy Protection Rules

Agents must not:

- open cleanup findings for untouched files
- suggest broad renames across old modules during a focused task
- turn a feature task into a repository-wide cleanup task
- require old language files to be normalized unless they were changed

Agents must:

- require English normalization for comments inside touched files
- require English normalization for hard-coded user-facing strings inside touched files

Agents may:

- note a legacy issue only when the current change directly interacts with it
- recommend a separate cleanup task instead of blocking the current work for unrelated debt

## Good Examples

Acceptable finding:

- "New string introduced in the current diff in `templates/admin/mod_content/list.twig` should use language keys."

Acceptable finding:

- "This file is touched and still contains a Turkish hard-coded visible string that must be converted to English."

Acceptable finding:

- "This file is touched and still contains a Kurdish comment that must be rewritten in English or removed."

Not acceptable:

- "There are many Turkish comments in older JS files across the repository."

Acceptable finding:

- "New method `resimSil()` added in this diff should be renamed to an English identifier."

Not acceptable:

- "Existing legacy method `resimSil()` in an untouched file should be renamed now."

## Recommended Rollout

## Phase 1

Run agents in report-only mode.

Purpose:

- measure false positives
- refine prompts and filters
- build developer trust

## Phase 2

Block on `Error` violations only.

Purpose:

- enforce core rules without slowing work too much

## Phase 3

Optionally add auto-fix behavior for safe categories:

- hard-coded visible strings converted to English or routed into language keys
- non-English comments rewritten to English or removed

Auto-fix must never become repository-wide by default.

## Definition of Success

The agent strategy is successful when:

1. New code quality stays high.
2. Untouched legacy code does not create noise during normal feature work.
3. Developers trust the guard agents.
4. Localization and naming regressions are caught early.
5. Touched files are progressively normalized for comments and visible hard-coded strings.
6. The system improves consistency without turning every task into a repository-wide cleanup project.
