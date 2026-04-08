# Language Management Modernization Plan

## Status Snapshot

Implementation status for the current repository as of `v1.0.53.52`:

- `Current`: installer seeds language rows into the `lang` table with only `name` and `seo`
- `Current`: runtime language validation still depends on the static `valid_lang` list in [`config/i18n.config.php`](/home/malpersaz/Desktop/projects/htdocs/Rojname/config/i18n.config.php)
- `Current`: installer collects a project default language, but that choice is not yet promoted into a canonical language-management state
- `Current`: many modules read available languages from the `lang` table, but there is no admin control for active versus inactive languages or a single default language
- `Gap`: the system has no canonical database-backed language lifecycle model
- `Gap`: the system has no phone-first admin surface for language activation, deactivation, or default-language switching
- `Gap`: runtime and installer behavior are not yet aligned to the same language source of truth

## Goal

Establish a professional, database-backed language management system for `Rojname`.

This system must allow administrators to:

- activate a language
- deactivate a language
- choose exactly one default language
- manage those decisions from the admin panel
- have installer, runtime, and admin behavior all obey the same source of truth

The target result is not just a new admin screen. It is a full language-governance model shared by:

- `installer/lang`
- `mod/lang`
- runtime routing and i18n bootstrapping
- admin language-aware module flows

## Product Principles

The language management system should follow these principles:

- one canonical source of truth for active and default languages
- no split-brain behavior between config files and database state
- no fake toggles that change admin UI but not runtime behavior
- one and only one default language at any time
- safe deactivation rules that prevent the system from becoming unusable
- phone-first admin UX with compact, touch-friendly controls
- clear operational semantics for active, inactive, and default states
- backward-tolerant rollout for existing installations

## Current Problems

The repository currently has a partial language foundation but not a complete management model.

Primary problems:

- the `lang` table is not yet designed as a lifecycle-managed registry
- installer seeding is too limited for post-install language administration
- `config/i18n.config.php` still acts as a static gatekeeper for valid languages
- runtime route parsing in [`lib/Amed.class.php`](/home/malpersaz/Desktop/projects/htdocs/Rojname/lib/Amed.class.php) is constrained by that static list
- admin code cannot reliably distinguish:
  - all known languages
  - active languages
  - inactive languages
  - the default language
- there is no protected workflow for:
  - preventing the last active language from being disabled
  - ensuring a new default is set before disabling the current default
  - keeping installer-selected project language and runtime default aligned

## Target Architecture

The professional target architecture should center on the `lang` table as the canonical language registry.

### Canonical Language Registry

The `lang` table should become the source of truth for:

- language code
- display name
- active status
- default-language status
- optional ordering metadata for admin presentation

Recommended minimum fields:

- `seo`
- `name`
- `status`
- `is_default`

Recommended optional future field:

- `sort_order`

Behavior rules:

- `status='y'` means the language is selectable and routable
- `status='n'` means the language remains stored but is no longer selectable or routable
- `is_default='y'` must exist on exactly one row
- a default language must also be active

### Runtime Language Resolution

Runtime language resolution should no longer treat `config/i18n.config.php` as the canonical valid-language list.

Target runtime behavior:

- read active languages from the database-backed registry
- cache them into `Kayit` for request-time efficiency
- use the database-backed active list in route resolution
- use the database-backed default language when no language prefix is present
- keep `config/i18n.config.php` only as a fallback bootstrapping safety net if database state is unavailable

### Installer Responsibility

The installer should set the initial language state, not own long-term language management.

Installer responsibilities:

- create or upgrade the `lang` table schema
- seed supported language rows
- mark the installer-selected project default language as the single default language
- mark initial active languages according to product rules
- leave post-install lifecycle decisions to admin management

### Admin Responsibility

The admin language module should manage ongoing language lifecycle safely.

Admin responsibilities:

- list all languages
- show active versus inactive state clearly
- show which language is default
- toggle active status with guardrails
- change default language with a dedicated action
- explain blocked actions in plain language

## Scope

This plan covers:

- `installer/lang`
- `mod/lang`
- runtime i18n bootstrapping in `config/` and `lib/`
- phone-first admin UX for language management
- migration rules for existing installations

This plan does not yet cover:

- per-language content completeness auditing
- automatic translation provisioning
- machine translation workflows
- module-by-module content backfill when a new language is activated

## Content and Routing Policy

Language activation state must have explicit product behavior across both frontend and admin surfaces.

### Active Language

An active language:

- is accepted as a valid frontend route prefix
- is available in language selectors and language-aware create flows
- can be used as the default language
- participates in normal runtime navigation

### Inactive Language

An inactive language:

- remains stored in the database
- is not accepted as a normal frontend route prefix
- is not offered in standard create flows for new content
- may remain visible in admin maintenance views where legacy content still exists

Inactive does not mean deleted.

It means operationally disabled for normal public use while preserving existing records and administrative recovery options.

### Frontend Route Contract

When a request targets an inactive language prefix, the runtime must follow one explicit policy.

Recommended policy:

- treat inactive language prefixes as invalid public routes
- resolve them through the normal not-found flow rather than silently serving content under an inactive locale

This policy should remain consistent unless the product explicitly introduces a redirect strategy later.

### Admin Content Contract

The admin panel must distinguish between:

- language management
- content maintenance

Recommended behavior:

- operational creation flows should show active languages only
- maintenance and reporting flows may show inactive languages when existing records depend on them
- inactive-language content should remain editable where operationally necessary, but clearly marked as tied to an inactive language

### Default-Language Change Contract

Changing the default language must not rewrite content languages.

It should only change:

- runtime fallback behavior
- default selection behavior in relevant admin flows
- installer-aligned project default language state where that state is persisted canonically

### Deactivation Contract

Deactivating a language must not:

- delete language rows
- delete language-bound content
- silently reassign existing content to another language

It should only:

- remove the language from active runtime routing
- remove it from standard operational selection lists
- preserve recoverability in admin maintenance flows

## Data Model Direction

### Required State Model

The minimum professional language state model should include:

- language exists
- language is active or inactive
- language is default or not default

### Integrity Rules

The implementation must enforce these rules:

- there must always be at least one active language
- there must always be exactly one default language
- an inactive language cannot remain marked as default
- if the default language is changed, the previous default must be cleared in the same operation
- if the current default is targeted for deactivation, the action must be blocked unless another default is selected first

### Migration Rules

For legacy installations:

- if `status` does not exist, add it with active default values
- if `is_default` does not exist, add it and derive one default row
- if multiple rows become default due to legacy inconsistency, normalize to one deterministic default
- if no default can be inferred, fall back to the current project language

## Operational Guarantees

The implementation needs explicit operational guarantees so language state changes behave predictably in production.

### Atomicity

Language-state transitions must be atomic where they affect multiple rows.

Required examples:

- switching the default language must clear the old default and set the new default in one protected operation
- deactivating a language that is currently default must be blocked or converted into a controlled multi-step transition

### Cache Invalidation

Whenever language state changes:

- cached active-language lists must be refreshed
- cached default-language state must be refreshed
- any runtime language metadata derived from the registry must be refreshed in the same change flow

The admin UI must not report success before cache refresh is completed or safely queued.

### Concurrency Safety

The design should assume multiple administrators may act at the same time.

The implementation should therefore:

- avoid read-then-write race conditions for default switching
- avoid leaving two defaults due to concurrent requests
- avoid leaving zero active languages due to concurrent deactivation attempts

### Auditability

Language-management actions should be traceable.

Recommended actions to log:

- language activated
- language deactivated
- default language changed

Minimum audit payload:

- acting user
- target language code
- action type
- timestamp

### Failure Semantics

If a language-state change fails mid-operation:

- partial state must not be left behind
- the previous valid state should remain authoritative
- the admin UI should receive a plain-language failure message

### Rollback Expectation

The first implementation does not require a generic rollback console.

However, every language-state action must be designed so that:

- the previous valid state can be restored through a normal admin action
- no manual database repair is needed for ordinary recoverable failures

## Admin UX Direction

The admin language management page must follow the repository's phone-only admin rule.

### Layout Rules

- fixed phone-sized shell assumptions only
- vertically stacked language cards
- one primary action per card cluster
- large tap targets
- compact status chips
- no desktop table-first management UI

### Card Content

Each language row or card should show:

- language native name
- language code
- active or inactive state
- default badge when applicable
- available actions

### Action Model

Recommended actions:

- `Activate`
- `Deactivate`
- `Set Default`

Action constraints:

- do not show `Deactivate` as enabled on the last active language
- do not show `Set Default` on the current default language
- if an inactive language is made default, activate it in the same atomic operation or block the action explicitly

## Phase-Based Task List

## Phase 1: Canonical Data Foundation

Purpose:

- turn `lang` into a real language registry instead of a simple seed table

Work items:

- update `installer/lang/lang.sql` to include lifecycle fields
- update `installer/lang/index.php` to seed those fields
- align installer seeding with the selected project default language
- add schema-upgrade logic for legacy databases
- extend [`mod/lang/model/lang.model.php`](/home/malpersaz/Desktop/projects/htdocs/Rojname/mod/lang/model/lang.model.php) with the new columns
- align installer behavior with the `mods` module standard where appropriate so registry and lifecycle semantics follow the same professional pattern

Deliverables:

- upgraded `lang` table schema
- deterministic default-language initialization
- safe legacy upgrade path

Acceptance criteria:

- new installations create a fully managed `lang` table
- old installations are upgraded without manual SQL
- exactly one language is marked default after installer completion
- all seeded languages have an explicit active or inactive state

## Phase 2: Runtime Source-of-Truth Alignment

Purpose:

- make runtime behavior obey the database-backed language registry

Work items:

- refactor `config/i18n.config.php` from hard-coded authority to fallback-only bootstrap config
- update [`lib/Amed.class.php`](/home/malpersaz/Desktop/projects/htdocs/Rojname/lib/Amed.class.php) to resolve active languages from the database-backed registry
- cache active languages and the default language in `Kayit`
- ensure language-prefixed routes only accept active languages
- ensure routes without a prefix fall back to the database default language
- preserve safe behavior when database bootstrapping is temporarily unavailable

Deliverables:

- runtime active-language list sourced from the database
- runtime default-language fallback sourced from the database
- cache refresh strategy for language changes

Acceptance criteria:

- deactivated languages are no longer treated as valid frontend route prefixes
- newly activated languages become valid without code edits
- changing the default language affects no-prefix runtime behavior

## Phase 3: Admin Language Module

Purpose:

- provide a real post-install language management surface

Work items:

- implement `mod/lang` as an actual admin module instead of only a model and language files
- add list and update controller actions
- add permission wiring through module registration
- create phone-first templates for list and action confirmation flows
- add language-file keys for all admin UI text using English key names
- ensure there are no inline visible fallback strings in templates

Deliverables:

- `mod/lang` admin controller
- `mod/lang` admin template(s)
- localized admin language files
- permission-aware management flow

Acceptance criteria:

- admins can view all languages from the control panel
- admins can activate and deactivate languages
- admins can change the default language
- the page is usable in a phone-sized admin shell

## Phase 4: Guardrails and Business Rules

Purpose:

- prevent invalid language-state transitions

Work items:

- block deactivation of the last active language
- block invalid default removal
- normalize default switching into one atomic update path
- surface plain-language error messages for blocked actions
- review module flows that load all languages and update them to use active languages where appropriate
- define whether some admin creation forms should show:
  - active languages only
  - all languages with inactive-state indicators

Deliverables:

- centralized service or helper for language-state transitions
- protected admin action rules
- consistent behavior across modules

Acceptance criteria:

- no invalid language state can be saved through admin actions
- the system never ends up with zero active languages
- the system never ends up with multiple defaults

## Phase 5: Cross-Module Alignment

Purpose:

- bring dependent modules into line with the new lifecycle model

Work items:

- audit modules that call `SELECT * FROM lang`
- split usage into:
  - all languages for reporting or migration
  - active languages for operational selection
- review settings flows that assume the first static language is the default
- remove remaining static assumptions around valid languages
- document where inactive languages should still remain visible for existing content maintenance
- formalize the inactive-language frontend route policy
- formalize which admin flows must remain capable of editing inactive-language content

Deliverables:

- module usage map
- adjusted language queries in affected modules
- reduced dependence on static config language ordering

Acceptance criteria:

- operational forms do not offer inactive languages where that would be misleading
- existing content tied to inactive languages remains safely readable or editable where needed
- default-language assumptions no longer depend on array order from static config

## Phase 6: Validation and Rollout

Purpose:

- verify the new language management model under real operational conditions

Work items:

- test fresh installation flow
- test legacy installation upgrade flow
- test activation, deactivation, and default switching
- test frontend route acceptance for active languages only
- test no-prefix fallback behavior
- test admin rendering in phone-sized viewports only
- test cache refresh behavior after language changes

Deliverables:

- rollout checklist
- regression checklist
- admin and installer validation notes

Acceptance criteria:

- fresh installs and upgraded installs behave the same way
- frontend, admin, and installer share the same language truth model
- phone-only admin management remains usable after repeated state changes

## Risks

Primary risks:

- runtime still partially trusting static `valid_lang` values after partial refactor
- modules unintentionally depending on inactive languages still appearing in selection lists
- legacy records producing ambiguous default-language normalization
- admin toggles changing database state without invalidating cached language state

Mitigations:

- refactor runtime resolution before shipping admin toggles broadly
- isolate language-state transitions in one service path
- add deterministic normalization for legacy data
- explicitly refresh cached language metadata after every admin change

## Validation Plan

Installer validation:

- install a fresh system and confirm the selected project default language is stored as the only default
- verify seeded languages include lifecycle state columns

Runtime validation:

- confirm only active languages resolve as route prefixes
- confirm default-language fallback matches the database default

Admin validation:

- confirm the language page works in a phone-sized viewport
- confirm last-active-language deactivation is blocked
- confirm default switching is atomic and visible immediately

Cross-module validation:

- confirm language pickers in touched modules respect active-language rules
- confirm settings and content flows still load correctly after a language is deactivated

## Recommended Implementation Order

Recommended order:

1. Phase 1: Canonical Data Foundation
2. Phase 2: Runtime Source-of-Truth Alignment
3. Phase 3: Admin Language Module
4. Phase 4: Guardrails and Business Rules
5. Phase 5: Cross-Module Alignment
6. Phase 6: Validation and Rollout

This order is important.

The admin panel should not expose activation and deactivation controls before runtime stops relying on the static `valid_lang` list as the real authority.

## Backlog

The following backlog translates the plan into implementation-ready work items.

### Epic 1: Canonical Language Registry

#### Story 1.1: Extend the `lang` table schema

Description:

- Add lifecycle fields to the installer-managed `lang` schema.

Tasks:

- update `installer/lang/lang.sql`
- add `status` field
- add `is_default` field
- optionally reserve `sort_order` for later without wiring it yet

Acceptance criteria:

- fresh installs create the extended schema
- schema is compatible with existing `seo` primary-key behavior

#### Story 1.2: Add legacy schema upgrade logic

Description:

- Upgrade existing `lang` tables safely during installer execution.

Tasks:

- add column-existence checks in `installer/lang/index.php`
- add non-destructive `ALTER TABLE` upgrades for missing lifecycle columns
- normalize invalid legacy default states

Acceptance criteria:

- old installations gain the new columns without manual SQL
- no duplicate-default state remains after normalization

#### Story 1.3: Persist installer-selected default language

Description:

- Promote the installer-selected project default language into canonical language state.

Tasks:

- read the selected project default language from installer state
- mark exactly one language row as `is_default='y'`
- ensure the default language is active

Acceptance criteria:

- installer-selected project language becomes the only default language after installation
- no other language remains default

#### Story 1.4: Register `lang` consistently with installer module standards

Description:

- Align `installer/lang` behavior with the professional `mods` module registration pattern.

Tasks:

- review `insert_mods()` usage for `installer/lang`
- ensure schema-ready state is reflected consistently
- keep installer messages and lifecycle behavior aligned with module standards

Acceptance criteria:

- `lang` installation follows the same module-registration conventions as other installer-managed modules

### Epic 2: Runtime Language Authority

#### Story 2.1: Replace static runtime authority with database-backed active languages

Description:

- Runtime should resolve valid languages from the canonical registry instead of a hard-coded config list.

Tasks:

- refactor `config/i18n.config.php` into fallback-only behavior
- update `lib/Amed.class.php` to load active languages from the database
- keep a safe bootstrap fallback when database resolution is unavailable

Acceptance criteria:

- active languages are sourced from the database in normal runtime flow
- static config no longer silently overrides admin-managed state

#### Story 2.2: Resolve runtime default language from canonical state

Description:

- The default frontend language should come from the registry.

Tasks:

- load canonical default language into runtime state
- use that language when no route prefix exists
- preserve stable behavior during early bootstrap failures

Acceptance criteria:

- no-prefix requests use the database-backed default language

#### Story 2.3: Add language-state cache refresh

Description:

- Runtime language metadata must refresh after changes.

Tasks:

- define cached keys for active languages and default language
- refresh cache after installer normalization
- refresh cache after admin language updates

Acceptance criteria:

- changing language state is reflected without manual cache repair

### Epic 3: `mod/lang` Admin Module

#### Story 3.1: Create the `mod/lang` admin controller

Description:

- Turn `mod/lang` into a working admin module.

Tasks:

- add controller actions for listing and updating language state
- add permission checks
- align naming and language keys with repository standards

Acceptance criteria:

- admins can load the language-management page
- unauthorized users are blocked cleanly

#### Story 3.2: Build the phone-first language list UI

Description:

- Provide a phone-only management surface for language lifecycle actions.

Tasks:

- create phone-sized language cards or stacked rows
- show language name, code, active state, and default badge
- add action controls for activate, deactivate, and set default

Acceptance criteria:

- the page is usable in a phone viewport
- action state is visually clear without desktop assumptions

#### Story 3.3: Localize the admin language-management UI

Description:

- Ensure the `mod/lang` admin UI is fully localized.

Tasks:

- create or update `mod/lang/lang/*.php`
- use English key names
- avoid visible fallback strings in templates and controllers

Acceptance criteria:

- touched `mod/lang` admin flows have complete language coverage

### Epic 4: Guardrails and State Transitions

#### Story 4.1: Enforce single-default semantics

Description:

- Default-language switching must be safe and atomic.

Tasks:

- implement one update path for default switching
- clear previous default in the same operation
- ensure the chosen default is active

Acceptance criteria:

- the system never persists multiple defaults

#### Story 4.2: Prevent invalid deactivation

Description:

- Admin actions must not leave the system without a valid language state.

Tasks:

- block deactivation of the last active language
- block deactivation of the current default unless a new default is selected first
- return plain-language validation messages

Acceptance criteria:

- invalid deactivation attempts are rejected consistently

#### Story 4.3: Add audit and failure semantics

Description:

- Language-state changes should be traceable and safe under failure.

Tasks:

- log activate, deactivate, and set-default actions
- include user, language code, action type, and timestamp
- ensure failed multi-step actions do not leave partial state behind

Acceptance criteria:

- language-state actions are auditable
- partial failed updates do not corrupt canonical state

### Epic 5: Content and Module Alignment

#### Story 5.1: Classify `lang` table consumers

Description:

- Existing modules use `lang` differently and need explicit alignment.

Tasks:

- audit modules that query `lang`
- classify each usage as:
  - active-only operational usage
  - all-language maintenance usage
  - legacy usage requiring redesign

Acceptance criteria:

- every touched `lang` consumer has an explicit usage category

#### Story 5.2: Align operational selectors to active languages

Description:

- Create flows should not offer inactive languages where that would be misleading.

Tasks:

- update touched admin create/edit flows to use the correct language source
- keep inactive-language content maintainable where needed
- document exceptions explicitly

Acceptance criteria:

- operational selectors do not expose inactive languages by accident

#### Story 5.3: Formalize inactive-language route behavior

Description:

- Frontend behavior for inactive language prefixes must be consistent.

Tasks:

- implement the chosen route policy
- ensure inactive language prefixes do not serve normal public content
- confirm behavior matches the plan contract

Acceptance criteria:

- inactive language routes behave consistently across frontend entry points

### Epic 6: Validation and Rollout

#### Story 6.1: Validate fresh installation behavior

Description:

- Confirm new installs produce correct canonical language state.

Tasks:

- run installer on a fresh database
- verify seeded languages, statuses, and default flag
- verify runtime uses the installed default language

Acceptance criteria:

- fresh install behavior matches the canonical model

#### Story 6.2: Validate legacy upgrade behavior

Description:

- Confirm old databases upgrade safely.

Tasks:

- test upgrade from a legacy `lang` table
- verify missing columns are added
- verify default normalization and runtime alignment

Acceptance criteria:

- legacy upgrade completes without manual repair

#### Story 6.3: Validate admin management behavior

Description:

- Confirm admin language actions are safe and usable.

Tasks:

- test activate, deactivate, and set-default actions
- test guardrail cases
- test cache refresh behavior
- test phone-only admin usability

Acceptance criteria:

- admin language actions are stable, understandable, and immediately effective

## Definition of Done

This initiative is complete when:

- `lang` is the canonical language registry
- installer, runtime, and admin all use the same language truth model
- one default language is always enforced
- active and inactive language states are operationally meaningful
- the admin panel provides a phone-first language management experience
- no static config list silently overrides admin-managed language state
