# Installer Mobile Modernization Plan

## Goal

Modernize the `installer/` package into a phone-only installation product.

This installer must feel like a focused mobile app flow, not a legacy setup page adapted from desktop assumptions.

The target experience is:

- 100% designed for phones
- touch-first
- fast to understand
- visually clear
- operationally safe
- dependency-aware
- modern in architecture and UX

Tablet, laptop, and desktop layouts are explicitly out of scope for the design target.

## Product Principles

The new installer should follow these principles:

- phone-first from the first screen to the final completion state
- one-handed usability with large tap targets and short vertical decisions
- guided flow instead of long technical forms dumped on one page
- strong state visibility for required versus optional modules
- dependency-aware selection logic
- safe defaults with explicit override points
- modern async installation feedback
- resumable progress and clear recovery states
- no raw SQL, stack trace, or low-level installer noise in user-facing views

## Current Installer Problems

The current installer is functional but not productized.

Current limitations:

- linear PHP execution with primitive text output
- no dedicated mobile UX system
- no modern step flow
- no explicit required versus optional module visualization
- no structured module grouping for user decisions
- no sample-content decision surface
- no recovery-oriented UI for degraded or failed modules
- no installer state machine
- weak separation between installer domain logic and presentation

## Target Experience

The installer should behave like a native-style mobile setup wizard.

Recommended flow:

1. Welcome
2. Installer Language
3. Environment Check
4. Project Setup
5. Module Selection
6. Sample Content Choice
7. Install Progress
8. Post-Install Review
9. Finish

Each step should occupy a compact phone screen with:

- a single primary action
- at most one secondary action
- concise explanatory text
- strong progress indication

## Phone-Only UX Direction

### Layout Rules

- fixed phone-sized shell
- stacked content only
- no multi-column module matrix
- no desktop sidebar assumptions
- bottom action bar for next, back, continue, retry
- large touch targets with minimum 44px height
- strong contrast and state color coding

### Motion

- subtle step transitions
- progressive reveal for dependency details
- animated install progress states
- staggered completion marks for finished modules

### Navigation

- stepper at top or as compact progress pills
- sticky bottom action area
- no hover-dependent interactions
- no desktop modal complexity

## Information Architecture

### Step 1: Welcome

Purpose:

- explain what the installer will do
- confirm this is a new installation or reset flow

Content:

- product name
- short installation summary
- environment prerequisites
- primary action: `Start Setup`

### Step 2: Installer Language

Purpose:

- let the user choose the installer UI language before the rest of the flow
- make all following steps readable from the first interaction

Requirements:

- language choice must appear near the beginning of the installer
- the choice must update installer text immediately
- the selected installer language must persist through refresh and resume

Recommended languages:

- use the project's supported language list as the starting set
- preserve the `Rojname` installer rule that excludes `tr` and `ar` from the default installer language setup unless the product decision is explicitly changed
- highlight the current default choice clearly

UX model:

- large tappable language cards or rows
- native-language labels when possible
- no tiny dropdown as the primary interaction

Behavior rules:

- installer language selection affects installer UI text only
- project default language selection remains a separate project setup decision
- the user must be able to change installer language before installation begins

### Step 3: Environment Check

Purpose:

- verify PHP version
- required extensions
- writable directories
- database connection ability

Output style:

- green pass chips
- red fail chips
- compact remediation text

Blocked install behavior:

- if a required prerequisite fails, the user cannot continue
- show one clear correction message per failed item

### Step 4: Project Setup

Purpose:

- collect install-time project settings

Fields:

- site title or project name
- default language
- database credentials if not already injected
- admin account information if needed

Rules:

- minimal fields only
- validate inline
- no multi-screen technical sprawl unless necessary

Language rule:

- keep installer language and project default language as separate controls
- keep the `Rojname` installer default-language exclusions intact unless a deliberate project-specific override is approved

### Step 5: Module Selection

This is the core decision screen.

Requirements:

- required modules and optional modules must be visually distinct
- dependencies must be visible before the user commits
- invalid combinations must be prevented

#### Required Modules

Required modules must be:

- preselected
- locked
- shown in a dedicated required section
- colored differently from optional modules

Recommended treatment:

- required modules: green or blue solid cards
- optional modules: neutral cards
- disabled-by-dependency modules: muted cards with dependency explanation

#### Optional Modules

Optional modules should:

- be grouped by capability
- show a short description
- show dependency chips
- show whether sample content is available

Suggested groups:

- Core System
- Content
- Media
- User and Access
- Communication
- Project-Specific Modules

#### Dependency Behavior

The installer must automatically:

- enable dependency prerequisites when an optional module requires them
- explain why a dependency is needed
- prevent deselecting a dependency that is still required by another selection

Example:

- selecting `mod_content` auto-requires `mod_category`
- selecting `mod_news` auto-requires `mod_news_category`
- selecting `mod_publication` auto-requires `mod_publication_category`

## Installer Language Model

The installer needs two separate language concepts:

- installer language
- project default language

These must not be merged into one control.

### Installer Language

This controls:

- step labels
- helper text
- warnings
- button labels
- progress copy
- failure and recovery copy

This does not automatically define the installed site's default language.

### Project Default Language

This controls:

- the default runtime language for the installed project
- language-dependent seed decisions where applicable

The installer should explain this distinction clearly so the user does not confuse the two choices.

## Sample Content Choice

The installer must provide an explicit yes or no decision for sample content.

This choice should not be hidden inside advanced settings.

### Required Behavior

The user must be able to choose:

- `Yes, install sample content`
- `No, start with a clean system`

### UX Model

Use a dedicated step or a prominent section immediately after module selection.

The screen should explain:

- sample content helps users explore the system quickly
- clean install keeps the project empty and production-ready

### Recommended Scope

Sample content should be modular.

The installer should support:

- global sample content toggle
- future per-module sample content toggles if needed
- preserve the existing `Rojname` behavior that removes default sample menu, category, and content seeds unless the installer product scope is explicitly changed

### Data Rules

Sample content installation should:

- never run for modules the user did not select
- be idempotent
- be clearly logged in installer results

## Installation Engine Modernization

The backend installer should move from a print-driven procedural flow to a structured installation pipeline.

### Recommended Architecture

Introduce an installer application layer with:

- step controller
- installation state store
- module manifest reader
- dependency resolver
- task runner
- progress event publisher

Suggested building blocks:

- `InstallerManifest`
- `InstallerModuleCatalog`
- `InstallerDependencyResolver`
- `InstallerTaskRunner`
- `InstallerStateStore`
- `InstallerProgressService`

## Module Manifest Model

Instead of hardcoding module behavior only in `installer/index.php`, define structured metadata for each module.

Suggested manifest fields:

```php
[
    'name' => 'mod_content',
    'label' => 'Content',
    'group' => 'content',
    'required' => false,
    'default_selected' => true,
    'dependencies' => ['mod_category', 'mod_gallery', 'mod_user'],
    'supports_sample_content' => true,
    'project_specific' => false,
]
```

This enables:

- clean module-selection UI
- dependency-aware auto-selection
- future filtering
- easier cross-project installer maintenance

## State Machine

The installer should use explicit states.

Suggested states:

- `not_started`
- `environment_blocked`
- `configuring`
- `ready_to_install`
- `installing`
- `completed`
- `completed_with_warnings`
- `failed`
- `recoverable_failure`

Per-module runtime states during install:

- `pending`
- `running`
- `completed`
- `skipped`
- `failed`
- `degraded`

## Progress Screen

The install progress screen should feel like a mobile task runner.

Requirements:

- live module-by-module progress
- current step label
- completed count
- failure badge if something breaks
- retry action when safe

Per-module row content:

- module label
- state icon
- optional dependency note
- optional sample-content note

## Error Handling and Recovery

The installer must not strand the user on raw error pages.

### Failure UX

On failure:

- show which module failed
- show whether the failure is recoverable
- show a plain-language reason
- provide `Retry`, `Back`, or `Exit Setup` actions where valid

### Recovery Behavior

If installation fails mid-run:

- persist installation state
- allow resume after correction when safe
- clearly mark modules already completed
- do not rerun destructive steps blindly

## Information Architecture Additions

### Step 8: Post-Install Review

Purpose:

- summarize what was installed before the user exits the installer
- surface warnings, skipped items, and any recovery-relevant notes

Content:

- installed module list
- skipped optional modules
- sample-content decision summary
- warnings or degraded states
- primary action: `Continue`

Rules:

- no raw logs in the default view
- expandable technical details only when needed
- completed versus degraded results must be visually distinct

### Step 9: Finish

Purpose:

- provide the final handoff into the installed product
- make the next safe action obvious

Content:

- success confirmation
- installed project name
- admin entry action if available
- reminder to remove or lock installer access if required by the deployment model

Primary actions:

- `Open Admin`
- `Exit Installer`

Rules:

- the finish state must not look identical to the progress screen
- if warnings remain, show them before the exit action without blocking a valid completion

## Required vs Optional Visual Language

This is a strict product requirement.

The user must immediately understand which modules are mandatory and which are optional.

Recommended visual system:

- required modules:
  - stronger surface color
  - lock icon
  - label such as `Required`
- optional modules:
  - neutral surface color
  - toggle affordance
  - label such as `Optional`
- auto-selected dependency:
  - different accent from both required and optional
  - label such as `Needed by selection`

## Technical Modernization Scope

### Frontend

Recommended:

- Twig-driven phone-first installer views
- structured component partials
- modern CSS variables
- progressive enhancement JS
- fetch-based step submission
- optimistic UI where safe

Do not build this as:

- a desktop admin page with responsive patches
- a Bootstrap-era multi-column setup page
- a server-printing status log

### Backend

Recommended:

- central manifest registry
- dependency resolution before execution
- installer session or state persistence
- structured JSON step responses for async screens
- installation logs separated from UI rendering

## Phase-Based Task List

### Phase 1: Foundation

Goal:

- establish the installer's mobile product shell and architectural baseline

Tasks:

- audit the current `installer/` entry flow and identify which parts are rendering, orchestration, state, and execution concerns
- define the target step contract for `Welcome`, `Installer Language`, `Environment Check`, and `Project Setup`
- introduce a phone-only installer layout shell with sticky bottom actions and compact progress treatment
- create a central installer state object or persistence layer for current step, installer language, project configuration, and resume state
- add installer language selection near the start of the flow and persist it across refresh and resume
- keep installer language separate from project default language in both backend state and UI controls
- preserve the `Rojname`-specific installer defaults, including language exclusions and project branding
- implement the environment check step with structured pass or fail items instead of print-based output
- define the initial installer service boundaries for manifest loading, state storage, and step navigation

Deliverables:

- phone-only installer shell
- working step navigation for the first setup steps
- persistent installer language selection
- structured environment check screen

Exit criteria:

- the first four setup steps work in sequence on a phone-sized viewport
- installer language changes are reflected immediately and survive refresh
- environment failures block forward navigation with clear remediation text

### Phase 2: Selection Flow

Goal:

- turn module choice into a dependency-aware mobile decision flow

Tasks:

- define the installer module manifest format and register all installer-managed modules through it
- mark required, optional, dependency-driven, sample-content-capable, and project-specific modules in manifest data
- group optional modules into mobile-friendly capability sections
- build the `Module Selection` screen with visually distinct required and optional cards
- lock required modules and prevent invalid deselection paths
- implement dependency resolution that auto-selects prerequisites and explains why they were selected
- expose dependency chips, requirement labels, and sample-content availability in module cards
- add an explicit `Sample Content Choice` step or equivalent dedicated surface immediately after module selection
- preserve the `Rojname` rule that default sample seeds remain excluded unless a deliberate product decision changes it
- ensure invalid module combinations are blocked before installation starts

Deliverables:

- manifest-driven module catalog
- dependency-aware module selection UI
- explicit sample-content decision step

Exit criteria:

- required and optional modules are visually distinct on phone screens
- dependency rules are enforced before install execution
- sample-content choice is explicit and stored in installer state

### Phase 3: Execution Flow

Goal:

- replace procedural installer output with a structured installation pipeline and progress UI

Tasks:

- introduce the installer application services needed for dependency resolution, task execution, progress publishing, and state transitions
- define explicit installer lifecycle states and per-module runtime states
- replace print-driven install progress with structured responses consumable by a phone-first progress screen
- build the `Install Progress` screen with current step, module rows, live statuses, and safe retry affordances
- separate installation logs from visible UI output
- persist execution progress so the installer can resume safely after interruption
- ensure completed modules are tracked and not rerun blindly on refresh or resume
- include sample-content execution state in progress and result reporting when applicable

Deliverables:

- structured installer task runner
- progress event model
- phone-first install progress screen
- resumable execution state

Exit criteria:

- installation progress is visible module by module
- refresh or interruption does not destroy safe progress state
- raw logs and low-level errors are not exposed as the default user-facing screen

### Phase 4: Recovery and Completion

Goal:

- make failure, review, and completion states safe, clear, and operationally usable

Tasks:

- implement recoverable versus non-recoverable failure classification
- add `Retry`, `Back`, and `Exit Setup` actions where they are valid and safe
- build the `Post-Install Review` screen with installed modules, skipped modules, warnings, degraded states, and sample-content summary
- build the `Finish` screen with final handoff actions such as `Open Admin`
- ensure failure and completion screens are visually distinct from the active progress screen
- expose expandable technical details only when needed, while keeping the default view plain-language
- verify touch targets, scroll behavior, action placement, and content density on phone-sized viewports only
- refine microcopy, state labels, and progress language so the installer remains understandable in every supported installer language

Deliverables:

- recovery-capable failure UX
- post-install review screen
- final finish screen
- mobile polish pass for touch ergonomics and state clarity

Exit criteria:

- recoverable failures can resume safely after correction
- the user can review what happened before leaving the installer
- the finish state presents the next safe action clearly without exposing raw internals

## Validation Requirements

The modernization is not complete unless these are true:

- the installer is comfortable to use on a phone-sized viewport only
- required and optional modules are visibly distinct
- dependency relationships are visible and enforced
- the user can explicitly choose the installer language
- the user can explicitly choose whether to install sample content
- installer failures surface clear and recoverable states
- module selection and install progress do not rely on desktop layout assumptions
- the final experience feels like a modern mobile setup flow, not a legacy script output

## Deliverables

Expected deliverables for implementation:

- new installer architecture plan translated into code structure
- installer language selection step and persistence model
- module manifest system
- phone-only installer layout and components
- module selection screen with required and optional visual split
- sample content choice step
- async installation progress screen
- failure and recovery screens
- validation checklist for phone-sized installer flows

## Backlog

### Epic 1: Installer Foundation

#### Story 1.1: Audit the current installer flow

Description:

- inspect the existing `installer/` entry flow and map rendering, orchestration, state, and execution responsibilities

Acceptance criteria:

- the current installer flow is documented as discrete responsibilities
- legacy print-driven behavior and state gaps are identified

#### Story 1.2: Create the mobile installer shell

Description:

- build a phone-only installer shell with compact progress treatment and sticky bottom actions

Acceptance criteria:

- the shell is designed for phone-sized viewports only
- primary and secondary actions remain usable in a fixed mobile action area

#### Story 1.3: Add installer state persistence

Description:

- introduce a central installer state model for current step, installer language, project configuration, and resume state

Acceptance criteria:

- installer step state survives refresh
- installer language and project setup state can be restored after interruption

#### Story 1.4: Add installer language selection

Description:

- add an early installer language step that updates installer UI text immediately

Acceptance criteria:

- installer language can be selected before installation begins
- language changes are reflected immediately in installer UI
- installer language remains separate from project default language
- `Rojname` installer-specific default language exclusions remain preserved

#### Story 1.5: Build the environment check step

Description:

- replace primitive prerequisite output with a structured `Environment Check` step

Acceptance criteria:

- PHP version, required extensions, writable directories, and database checks are shown as structured items
- blocking failures prevent forward navigation
- each failed item has concise remediation text

### Epic 2: Module Selection Flow

#### Story 2.1: Introduce installer module manifests

Description:

- define and register manifest metadata for installer-managed modules

Acceptance criteria:

- each installer-managed module declares required or optional status, dependencies, grouping, and sample-content capability
- project-specific modules can be flagged explicitly

#### Story 2.2: Build the module selection screen

Description:

- implement a phone-first `Module Selection` screen with clear required and optional module treatment

Acceptance criteria:

- required modules are visually distinct, preselected, and locked
- optional modules are shown as selectable mobile cards
- no desktop-style matrix or multi-column layout is used

#### Story 2.3: Add dependency-aware selection logic

Description:

- implement dependency resolution for module selection before installation starts

Acceptance criteria:

- selecting a module auto-selects required prerequisites
- dependencies are explained in visible UI
- a required dependency cannot be deselected while still needed by another selected module

#### Story 2.4: Add sample content choice

Description:

- add an explicit installer decision for sample content after module selection

Acceptance criteria:

- the user can explicitly choose sample content yes or no
- sample-content choice is stored in installer state
- default `Rojname` sample seed exclusions remain preserved unless explicitly changed

#### Story 2.5: Block invalid module combinations

Description:

- prevent invalid combinations from reaching the installation phase

Acceptance criteria:

- invalid module states are caught before install execution
- the UI explains why the user cannot continue

### Epic 3: Installation Execution

#### Story 3.1: Introduce installer application services

Description:

- add structured services for manifest loading, dependency resolution, task execution, progress publishing, and state transitions

Acceptance criteria:

- installer responsibilities are separated into explicit services
- execution logic is no longer coupled directly to legacy screen output

#### Story 3.2: Define installer runtime states

Description:

- define lifecycle states for the installer and per-module execution

Acceptance criteria:

- installer lifecycle states are explicit and consistently used
- per-module states cover pending, running, completed, skipped, failed, and degraded outcomes

#### Story 3.3: Build the install progress screen

Description:

- replace print-based progress output with a structured phone-first progress screen

Acceptance criteria:

- progress is visible module by module
- the current installer state is visible during execution
- low-level logs are not shown as the default user-facing view

#### Story 3.4: Persist resumable execution state

Description:

- keep enough execution state to support safe refresh and resume behavior

Acceptance criteria:

- refresh does not erase known completed work
- completed modules are not rerun blindly after interruption
- resumable progress state is available when recovery is safe

#### Story 3.5: Report sample-content execution state

Description:

- include sample-content work in installation progress and final results when applicable

Acceptance criteria:

- sample-content execution is visible in progress or result summaries
- sample-content tasks never run for unselected modules

### Epic 4: Recovery and Completion

#### Story 4.1: Add recovery-aware failure handling

Description:

- classify failures as recoverable or non-recoverable and expose safe recovery actions

Acceptance criteria:

- recoverable failures expose valid retry or resume actions
- non-recoverable failures do not suggest unsafe continuation
- failure reasons are shown in plain language

#### Story 4.2: Build the post-install review screen

Description:

- add a `Post-Install Review` step that summarizes install results before exit

Acceptance criteria:

- installed modules, skipped modules, warnings, degraded states, and sample-content decisions are visible
- raw logs are hidden behind optional technical detail disclosure

#### Story 4.3: Build the finish screen

Description:

- add a final completion screen with the next safe action for the installed product

Acceptance criteria:

- the finish state is visually distinct from the progress state
- the user can reach the next valid action such as `Open Admin`
- any remaining warnings are visible before exit without blocking valid completion

#### Story 4.4: Run the mobile polish pass

Description:

- refine touch ergonomics, action placement, scrolling, and localized microcopy for phone-only use

Acceptance criteria:

- touch targets remain usable on phone-sized viewports
- action placement supports one-hand use
- installer copy stays understandable across supported installer languages

## MVP Backlog

### P0

- Story 1.1: audit the current installer flow
- Story 1.2: create the mobile installer shell
- Story 1.3: add installer state persistence
- Story 1.4: add installer language selection
- Story 1.5: build the environment check step
- Story 2.1: introduce installer module manifests
- Story 2.2: build the module selection screen
- Story 2.3: add dependency-aware selection logic
- Story 2.4: add sample content choice
- Story 2.5: block invalid module combinations
- Story 3.1: introduce installer application services
- Story 3.2: define installer runtime states
- Story 3.3: build the install progress screen
- Story 3.4: persist resumable execution state
- Story 4.1: add recovery-aware failure handling
- Story 4.2: build the post-install review screen
- Story 4.3: build the finish screen

P0 outcome:

- the installer can run end to end as a phone-first guided flow with dependency-aware selection, resumable execution, and safe completion states

### P1

- Story 3.5: report sample-content execution state
- Story 4.4: run the mobile polish pass

P1 outcome:

- the installer becomes clearer, more polished, and easier to operate under real-world interruption and localization conditions

### Deferred

- future per-module sample content toggles
- expanded technical detail surfaces beyond the minimum recovery view
- non-essential motion polish beyond meaningful state transitions

Deferred rule:

- deferred items must not block the first shippable mobile installer flow

## Initial Implementation Order

Use this order for the first execution pass so infrastructure is built before dependent screens and runtime behaviors.

### Order 1: Baseline analysis and shell

- Story 1.1: audit the current installer flow
- Story 1.2: create the mobile installer shell

Reason:

- the existing installer responsibilities must be mapped before they can be safely separated
- the shell establishes the phone-only frame that all later steps will use

### Order 2: Shared installer state and step model

- Story 1.3: add installer state persistence
- Story 3.2: define installer runtime states
- Story 1.4: add installer language selection

Reason:

- step navigation, resume behavior, and localized UI all depend on a stable installer state model
- explicit runtime states should exist before execution and recovery UI are built on top of them

### Order 3: Early guided setup steps

- Story 1.5: build the environment check step

Reason:

- environment gating belongs early in the flow and should block invalid progression before deeper setup work begins

### Order 4: Module metadata and selection logic

- Story 2.1: introduce installer module manifests
- Story 2.3: add dependency-aware selection logic
- Story 2.5: block invalid module combinations

Reason:

- the selection UI should not be built against implicit or hardcoded module rules
- dependency enforcement and invalid-state blocking must exist before the selection screen is treated as complete

### Order 5: Selection-facing screens

- Story 2.2: build the module selection screen
- Story 2.4: add sample content choice

Reason:

- once manifest and dependency logic exist, the mobile selection flow can be built against stable rules
- sample-content choice depends on selected modules and should come immediately after them

### Order 6: Execution architecture

- Story 3.1: introduce installer application services
- Story 3.4: persist resumable execution state

Reason:

- the progress screen should not be implemented before the execution pipeline and resume model exist
- resumable execution is foundational for recovery-safe install behavior

### Order 7: Execution-facing screen

- Story 3.3: build the install progress screen

Reason:

- the progress screen depends on structured task execution, runtime states, and persisted install progress

### Order 8: Failure, review, and completion

- Story 4.1: add recovery-aware failure handling
- Story 4.2: build the post-install review screen
- Story 4.3: build the finish screen

Reason:

- recovery behavior depends on real execution state and failure classification
- review and finish screens should be built after the system can produce reliable results and warnings

### Order 9: Post-MVP refinements

- Story 3.5: report sample-content execution state
- Story 4.4: run the mobile polish pass

Reason:

- these improve clarity and product quality, but they should not block the first end-to-end mobile installer release

## Recommendation

Do not incrementally patch the current installer UI into a pseudo-modern desktop page.

The correct direction is:

1. treat the installer as a dedicated phone product
2. model module metadata explicitly
3. make dependency rules visible in the selection flow
4. separate required and optional modules with strong visual language
5. expose sample content as an explicit yes or no choice
6. execute installation through a structured, recoverable pipeline

That approach will produce an installer that is easier to understand, safer to operate, and aligned with the repository's phone-only admin product direction.
