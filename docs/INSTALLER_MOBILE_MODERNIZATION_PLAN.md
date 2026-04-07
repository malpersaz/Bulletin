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

### Step 2: Environment Check

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

## Suggested Phases

### Phase 1: Foundation

- define installer product scope
- add installer language selection and persistence
- introduce module manifest format
- split domain logic from screen rendering
- create phone-only layout shell
- add environment check step

### Phase 2: Selection Flow

- implement required versus optional module cards
- add dependency-aware selection engine
- add sample-content yes or no step
- block invalid selections

### Phase 3: Execution Flow

- replace print-based install output with structured progress UI
- add per-module progress states
- add resumable install state
- add safer failure handling

### Phase 4: Recovery and Polish

- add retry and repair paths
- improve post-install summary
- show installed modules, skipped modules, warnings, and sample-content decision
- refine motion, microcopy, and touch ergonomics

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
