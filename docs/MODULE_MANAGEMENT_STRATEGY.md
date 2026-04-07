# Module Management Strategy and Robust Routing Plan

## Purpose

This document defines a practical architecture plan for optional front-end modules in Rojname.

The main goal is to prevent runtime database exceptions such as:

- visiting `/contents` when `mod_category` or `mod_content` tables do not exist
- visiting `/newses` when the news module is partially installed
- calling shared widgets or helper methods that assume dependent tables are available

The plan is intentionally based on the current repository structure, not on a greenfield rewrite.

## Current Implementation Status

The repository now has a partial implementation of this strategy.

Implemented:

- centralized runtime readiness checks
- dependency-aware feature maps
- route, helper, and widget guards for content, news, publication, and video embed flows
- management visibility based on runtime readiness instead of directory checks
- installer dependency enforcement and uninstall blocking for key dependency pairs
- module registry status fields in `mods`
- blocked-feature reason reporting and logging
- admin module health visibility and basic enable/disable controls for shared front-end modules

Still pending or partial:

- automated regression coverage
- a full repair or recovery workflow for degraded modules
- broader operational controls beyond the current shared module subset
- long-term router decoupling

## Current Repository Reality

The codebase already has a module registry concept:

- the installer writes installed modules into the `mods` table
- some module flows already use `exists_mod_name(...)`
- some admin management screens still rely on `is_dir(mod_dir . $moduleName)`
- front-end routing in `mod/index/index.php` directly calls feature helpers without a readiness guard

This means the repository does not currently suffer from a missing concept. It suffers from incomplete and inconsistent enforcement of that concept.

## Core Problem

The failure mode is not only "module inactive".

The real failure mode is:

1. a route is reachable
2. the route calls a helper or model
3. the helper assumes required tables exist
4. one or more dependency tables are missing
5. a `DatabaseException` is thrown before the flow can degrade gracefully

Example:

- `/contents` calls the `mod_content` helper
- the helper queries both `mod_content` and `mod_category`
- if either table is missing, the request crashes

So a simple `is module active` check is not enough. The framework needs a `module readiness` check that includes dependency and schema validation.

## Design Principles

The implementation should follow these principles:

- use the existing `mods` table as the starting source of truth instead of introducing a second registry immediately
- distinguish `installed`, `enabled`, and `ready`
- treat readiness as dependency-aware, not module-name-only
- fail closed with `404` or a controlled unavailable response, never with an uncaught SQL exception
- centralize the guard logic so controllers, helpers, and widgets use the same rules
- make installer and uninstaller operations keep registry state and schema state aligned
- support future router decoupling, but solve the current crash path first

## Definitions

### Installed

A module is installed when it has a row in the `mods` table.

This does not prove that:

- all required tables exist
- related dependency modules are installed
- the feature is safe to execute

### Enabled

A module is enabled when the system is configured to expose its routes and widgets.

The current repository does not have a dedicated enabled/disabled field in `mods`. That can be added later if operational toggling is required.

### Ready

A module is ready only when all of the following are true:

- the module is registered in `mods`
- all required tables exist
- all required dependency modules are installed
- any required auxiliary modules for the flow are present
- the feature can run without triggering missing-table or missing-module exceptions

Readiness, not installation alone, must be the gate used by front-end execution.

## Recommended Data Model

### Phase 1: Reuse `mods`

The first implementation phase should keep `mods` as the canonical module registry.

This avoids:

- dual sources of truth
- migration complexity without immediate benefit
- inconsistency between installer behavior and runtime checks

### Optional Phase 2: Extend `mods`

If runtime activation control is needed, extend `mods` instead of creating `sys_modules` immediately.

Suggested future columns:

```sql
ALTER TABLE `mods`
ADD COLUMN `status` ENUM('enabled', 'disabled') NOT NULL DEFAULT 'enabled',
ADD COLUMN `installed_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
```

If per-module version tracking becomes operationally useful, add a version column later. It is not required to solve the current routing failures.

## Central Capability Layer

Create a shared runtime service responsible for module and schema readiness decisions.

Suggested responsibilities:

- read installed modules from `mods`
- cache results in memory or `Kayit`
- check table existence
- evaluate module dependency maps
- expose a small set of guard methods

Suggested interface:

```php
class ModuleRuntime
{
    public static function isInstalled(string $module): bool;

    public static function tableExists(string $table): bool;

    public static function isReady(string $feature): bool;

    public static function requireReady(string $feature): bool;

    public static function getFailureReason(string $feature): ?array;
}
```

The exact class name can vary. The important part is to stop scattering module checks across controllers and templates.

## Feature Readiness Map

Readiness must be defined per feature flow, not only per module.

Example mapping:

```php
[
    'content.front' => [
        'modules' => ['mod_content', 'mod_category'],
        'tables' => ['mod_content', 'mod_category'],
    ],
    'news.front' => [
        'modules' => ['mod_news', 'mod_news_category'],
        'tables' => ['mod_news', 'mod_news_category'],
    ],
    'publication.front' => [
        'modules' => ['mod_publication', 'mod_publication_category'],
        'tables' => ['mod_publication', 'mod_publication_category'],
    ],
    'video_embed.front' => [
        'modules' => ['mod_video_embed', 'mod_video_embed_category'],
        'tables' => ['mod_video_embed', 'mod_video_embed_category'],
    ],
]
```

This is the key correction to the earlier plan.

`mod_content` is not independently ready for `/contents` if `mod_category` is missing.

## Guarding Strategy

### 1. Route-Level Guard

Every public route in `mod/index/index.php` that delegates to an optional feature must check readiness before loading the helper.

Example:

```php
public function contents($category = null, $page = 1)
{
    if (!ModuleRuntime::isReady('content.front')) {
        return $this->_404();
    }

    $content = Load::helper('index.mod_content');
    $content->contents($category, $page);
}
```

This protects the most visible crash entrypoint.

### 2. Helper-Level Guard

Helpers must also check readiness at their own entry points.

This is required because helpers may be called from:

- controller methods
- shared internal methods
- widget loaders
- future routes

Example:

```php
public function contents($category = null, $page = 1)
{
    if (!ModuleRuntime::isReady('content.front')) {
        return $this->_404();
    }

    // existing logic
}
```

Route guards without helper guards are incomplete.

### 3. Widget and Partial Guard

Methods such as:

- `_latest_content`
- `_latest_news`
- homepage category widgets
- archive builders

must be guarded as well.

For non-page widgets, the preferred behavior is usually:

- return an empty array or no-op when the feature is unavailable
- do not throw
- do not render broken UI shells

### 4. Shared Base Guard Utilities

To avoid repeated boilerplate, add reusable guard methods in a shared base class or runtime service.

Example:

```php
protected function guardFeatureOr404(string $feature): bool
{
    if (!ModuleRuntime::isReady($feature)) {
        $this->_404();
        return false;
    }

    return true;
}
```

This keeps controller and helper code consistent.

## Table Existence Policy

The system should not perform ad hoc SQL and then handle failures after the fact.

Instead, it should explicitly verify the existence of required tables before entering the feature flow.

Recommended behavior:

- `SHOW TABLES LIKE ...` or adapter-aware equivalent
- cache the result for the request lifecycle
- optionally cache for a short TTL if the bootstrap layer already supports it

This is cheaper and safer than allowing requests to reach failing queries.

## Installer and Uninstaller Responsibilities

The installer layer must keep runtime reality and registry reality synchronized.

### Install Path

When installing a module:

1. create the required tables
2. insert the module into `mods`
3. insert any required roles and permissions
4. validate that required dependency modules exist if the module depends on them
5. mark the install as failed if the schema is incomplete

### Uninstall Path

When uninstalling a module:

1. identify dependent modules or dependent front-end flows
2. remove or disable the module safely
3. remove the module row from `mods`
4. ensure related routes and widgets become unavailable through guards
5. never leave a route exposed to a helper that now points to missing tables

### Dependency Enforcement

Some modules are not operationally independent.

Examples:

- `mod_content` front-end flow depends on `mod_category`
- `mod_news` front-end flow depends on `mod_news_category`
- `mod_publication` front-end flow depends on `mod_publication_category`

The installer should enforce or at least warn on these dependency relationships.

## Admin Management Correction

Admin management currently uses directory existence in some places to infer module availability.

That is not sufficient for runtime safety.

A module directory can exist while:

- the module is not installed
- required tables were never created
- tables were partially removed
- the module should not be exposed on the front end

Admin UIs should be migrated from:

- file-system presence checks

to:

- installed checks from `mods`
- readiness checks for flows that require schema completeness

## Failure Behavior

The runtime must use explicit graceful degradation rules.

### Public Page Routes

For routes like:

- `/contents`
- `/newses`
- `/publications`

behavior should be:

- return `404` when the feature is not ready
- never expose raw SQL exceptions to the user

### Widgets and Partials

For optional blocks such as latest items or archive sidebars:

- return empty data
- suppress rendering when no data is available
- do not convert missing features into fatal errors

### Logging

The system should log why a feature was blocked.

Example log payload:

```php
[
    'feature' => 'content.front',
    'reason' => 'missing_table',
    'missing' => ['mod_category'],
]
```

This helps diagnose environment drift without breaking the page.

## Refactoring Scope

The first pass should cover all current crash-prone front-end entry points.

Minimum scope:

- `mod/index/index.php`
- `mod/index/helper/mod_content.helper.php`
- `mod/index/helper/mod_news.helper.php`
- `mod/index/helper/mod_publication.helper.php`
- `mod/index/helper/mod_video_embed.helper.php`
- any `_latest_*` helper accessors exposed through `mod/index/index.php`

This should be treated as a single integrity pass. The repository should not keep mixed guarded and unguarded variants of the same feature family.

## Rollout Plan

### Phase 1: Stabilization

Deliver immediate crash prevention.

Tasks:

1. implement a small shared module runtime service
2. read installed modules from `mods`
3. add adapter-safe table existence checks
4. define readiness maps for content, news, publication, and video embed flows
5. guard the front-end controller methods
6. guard helper entry points and latest-item widget methods
7. return `404` or empty results instead of allowing SQL exceptions

### Phase 2: Management Alignment

Make admin and installer behavior consistent with runtime checks.

Tasks:

1. replace `is_dir(...)` module checks in management screens where appropriate
2. add dependency warnings or enforcement in installers
3. ensure uninstall operations do not leave broken route exposure behind

### Phase 3: Optional Operational Controls

Only if needed:

1. add `status` support to `mods`
2. support enabled versus disabled modules
3. surface module state in admin management

### Phase 4: Router Decoupling

This is optional and should not block stabilization.

Possible future direction:

- modules register front-end capabilities or routes
- the router dispatches only to ready features
- `mod/index/index.php` no longer acts as a hardcoded facade for all optional features

This is a valid long-term design, but it should be treated as a later refactor, not as the prerequisite for fixing the current production risk.

## Testing Requirements

This strategy is not complete without regression coverage.

Required scenarios:

1. `/contents` returns `404` when `mod_content` is installed but `mod_category` table is missing
2. `/contents` returns `404` when `mod_content` is missing from `mods`
3. `/contents` works when both `mod_content` and `mod_category` are installed and tables exist
4. `_latest_content` returns safely when the content feature is unavailable
5. `/newses` and `/publications` behave the same way for their own dependency pairs
6. management screens do not present a module as available only because the directory exists

If automated tests are not available yet, these cases should at least be documented as mandatory manual validation flows.

The current manual regression source of truth is:

- `docs/MODULE_MANAGEMENT_VALIDATION_CHECKLIST.md`

The current lightweight smoke command is:

- `php tasks/module_management_smoke.php`

The current isolated runtime logic test command is:

- `composer test:module-management`

## Acceptance Criteria

This plan is complete only when all of the following are true:

- visiting an unavailable optional route never throws a database table-not-found exception
- optional widgets never hard-fail when their backing module is unavailable
- front-end exposure is based on readiness, not just file presence
- installer and runtime behavior share the same module registry model
- dependency modules are accounted for explicitly
- the system can explain why a feature was blocked

## Final Recommendation

Do not introduce a separate `sys_modules` table as the first move.

The correct near-term strategy is:

1. standardize on `mods` as the existing registry
2. add a centralized readiness service
3. define dependency-aware feature maps
4. guard routes, helpers, and widgets
5. align installer and management behavior with the same runtime truth

That plan is sufficient to prevent the current class of `DatabaseException` failures while keeping the implementation compatible with the existing repository architecture.
