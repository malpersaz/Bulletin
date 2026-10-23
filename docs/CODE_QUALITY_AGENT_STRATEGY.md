# Code Quality Agent Strategy

## Purpose

Define a safe but strict agent-based quality control model for this repository.

The goal is to let specialized review agents enforce code-quality and localization rules during development without turning every task into a repository-wide cleanup effort.

## Core Principle

The repository uses a mixed enforcement model.

- untouched files are protected from legacy cleanup noise
- touched files are subject to stronger normalization rules

## Zero Non-English Tolerance Rule (`AGENTS.md` Rule 7)

No non-English comments, identifiers, variables, methods, functions, classes, translation keys, filenames, routes, or hardcoded user-facing strings are accepted in the codebase.

If non-English code or comments are encountered in legacy or touched files during development or refactoring, they must be rewritten in clean English immediately (`AGENTS.md` Rule 7):

- all code comments must be strictly in English
- all variable, method, function, class, and property names must be in English
- all translation key names (`en.php` baseline) must be descriptive English
- all filenames, template names, and route endpoints must be in English
- all user-facing strings must be wired through the i18n localization dictionaries (`lang/*.php`)

## Enforcement Boundary

The repository uses this scope model:

- untouched files: diff-free, no cleanup pressure
- touched files: file-wide enforcement for non-English comments and non-English hard-coded user-facing strings
- all other policy categories: diff-scoped by default

## Repository Boundary

The following modules are `AF/Bulletin` domain-specific modules:

- `mod_news` and `mod_news_category`
- `mod_headline`
- `mod_publication` and `mod_publication_category`

Commercial, generic-content, and assessment modules (`mod_content`, `mod_product`, `mod_service`, `mod_project`, `mod_client`, `mod_reference`, `mod_appointment`, `mod_reservation`, `mod_hr`, `mod_organization_type`, `mod_test`, `mod_assessment`, `mod_hero_slider`) are strictly forbidden in `AF/Bulletin` and guarded by `tools/module_boundary_guard.py`.

Unless the user explicitly defines a local-only rule, shared-core refactoring work (`lib/`, `installer/`, `tools/`) should be treated as shared-core candidate work that needs cross-repository evaluation.

When a new `AF/Bulletin`-specific boundary is introduced, update the linked repository policy documents in the same pass.

## Synchronization Discipline

When a module is being synchronized or refactored, the agent must apply policy during implementation, not after the fact.

Required behavior:

- update the module and apply agent rules in the same pass
- remove Turkish comments from touched files
- do not use `default(...)`, inline fallback text, or hard-coded fallback strings to cover missing localization in visible UI output
- if a visible localization key is missing, add or wire the proper key in the same task instead of shipping a fallback
- treat touched flows as required to reach fully wired multilingual behavior before the task can be considered complete
- do not keep old and new keys, routes, methods, validators, templates, or aliases alive together unless the user explicitly requests compatibility retention
- do not leave half-completed refactors behind
- run a short review before moving on to the next module

## Admin Mobile-Only Rule

The repository admin panel must be treated as a phone-only interface.

Required behavior for admin UI work:

- design and implement for phone screens only
- treat the repository's device-frame simulator as a phone target, not as a desktop canvas
- reject tablet-first or desktop-first layout reasoning unless the user explicitly overrides the rule
- choose UI patterns, densities, controls, and interaction models that fit narrow phone widths and touch input
- prefer compact vertical composition over decorative top matter when space competes with the primary task
- validate admin UI behavior only in phone-sized viewports or the phone simulator shell

Agents must report as policy violations:

- admin UI changes optimized primarily for tablet or desktop widths
- admin validation performed only on tablet or desktop assumptions
- admin layouts that consume critical phone viewport space with non-essential chrome and make the main task hard to use

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
- any inline fallback text that masks a missing visible translation key in the touched flow
- any attempt to ship a touched visible flow without complete localization coverage

Must ignore:

- old unused translation keys outside the diff
- old missing keys outside the diff
- legacy mixed naming in existing translation arrays outside the diff
- non-English hard-coded strings in untouched files

## 2. `comment-guard`

Purpose:

- prevent non-English comments in code

Checks:

- any non-English comments added in the change
- any non-English comments remaining inside touched files
- unnecessary comments added where code is clear

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
- `default(...)` usage on visible localization expressions in touched templates or JS-rendered UI
- any visible UI state that depends on fallback copy instead of a real translation key
- any admin UI layout introduced in the diff that is optimized for tablet/desktop instead of phone-first use
- any admin UI chrome introduced in the diff that materially harms phone usability or hides the primary task in the initial viewport

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
- newly introduced non-English comment
- newly introduced non-English method/variable/function/class/file name
- newly introduced missing translation key in active flow
- any `lang.*|default(...)` or equivalent inline fallback used for visible UI text in the touched flow
- any touched visible UI flow that is not fully wired to localization sources
- any non-English comment remaining inside a touched file
- any non-English hard-coded user-facing string remaining inside a touched file
- keeping old and new keys, routes, methods, validators, templates, or aliases alive together after a refactor without explicit user approval
- leaving a touched module in a half-refactored state
- shipping an admin UI change that is clearly built or validated for tablet/desktop instead of the required phone-only target
- shipping an admin phone flow where non-essential header, hero, toolbar, or overlay chrome materially blocks the primary task in the initial viewport

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

Compatibility retention is allowed only when the user explicitly requests it or when an external dependency is concretely identified in the change.

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

Agents must flag:

- any non-English comments introduced in the change
- any non-English comments remaining anywhere inside touched files
- unnecessary comments added where code is self-explanatory

Agents should allow:

- concise English comments when genuinely useful
- no comment at all when code is self-explanatory

## Naming

Agents must flag any newly introduced or modified identifiers with non-English names across all scopes (methods, functions, variables, properties, classes, parameters, and array keys) without restricting evaluation to predefined example lists.

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

Agents must flag any newly introduced or modified files, directories, routes, or templates with non-English names. All file system entities and routing endpoints must strictly use standard English naming.

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

## Reproducible Verification & Pre-Release Runbook

### 1. SQL View Parity & Safety
Always check view definitions in `installer/views/*.sql` prior to modifying module queries or schemas. Ensure no query performs `SELECT *` from views with hidden columns.
Verify view definitions:
```bash
docker exec bulletin_db mysql -u akm_user -pakm_password akm_cms -e "SHOW CREATE VIEW news\G"
```

### 2. Functional Module Smoke Harness
Run the module smoke test harness inside the application container. The harness executes within transactional sandboxes and asserts complete state isolation:
```bash
docker exec bulletin_app php tasks/module_smoke.php
```

### 3. MCP Smoke & Integration Harness
Run the MCP test suite covering all tools, authentication, authorization, rate limiting, and parity checks:
```bash
docker exec bulletin_app php tasks/mcp_smoke.php
```

### 4. Database Migrations Execution
Execute structured database migrations using the safe migration runner:
```bash
# Preview changes (Dry Run)
docker exec bulletin_app php tasks/migrate.php --dry-run

# Execute a specific migration step
docker exec bulletin_app php tasks/migrate.php --step M-06 --confirm

# Roll back a specific migration step
docker exec bulletin_app php tasks/migrate.php --rollback M-06 --confirm
```

### 5. Automated Pre-Release Quality Guards
Before finalizing any release or tagging, all automated guards must exit 0:
```bash
# 1. PHP Syntax validation
find mod/mod_news* lib/McpNewsTools* -name "*.php" -exec php -l {} +

# 2. Module Boundary Guard (zero forbidden commercial/foreign modules)
python3 tools/module_boundary_guard.py --all

# 3. i18n Guard (strict localization & comment English check)
python3 tools/i18n_guard.py

# 4. Code Separation Guard (no inline styles or scripts)
python3 tools/code_separation_guard.py

# 5. Language Parity Check
python3 tools/lang_parity_check.py --module mod_news --module mod_news_category

# 6. Public Surface Audit (zero broken routes, valid assets)
python3 tools/public_surface_audit.py
```
