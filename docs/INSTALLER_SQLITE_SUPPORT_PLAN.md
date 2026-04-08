# Installer SQLite Support Plan

## Objective

Add SQLite (`PDO_SQLITE`) as a real installer-supported database option without breaking the existing MySQL flow. "Supported" means more than showing a new step in the UI: the installer must be able to save configuration, create or open the selected database, and complete module installation successfully on SQLite.

## Current Status

Partial implementation work already exists in the working tree:

- installer state has database-related fields
- a `database` step was added to the installer flow
- `InstallerConfigWriter` and `SqliteSqlTransformer` were introduced
- environment checks were adjusted to make database setup non-blocking

The current implementation is not yet shippable. Review of the pending changes identified these blocking gaps:

1. `SqliteSqlTransformer` exists but is not wired into module SQL execution.
2. `InstallerConfigWriter` currently assumes `config/db.config.php` already exists and is writable, which fails the fresh-install target.
3. Environment checks currently require both `pdo_mysql` and `pdo_sqlite`, even though the installer should allow either one depending on the selected engine.
4. The new database-step UI still contains hard-coded visible text instead of using installer language keys.
5. `docs/VERSION.md` currently describes SQLite support as completed even though the runtime path is incomplete.

This document replaces the earlier "proposed changes" draft with an execution plan that closes those gaps.

## Scope

In scope:

- add a phone-first database configuration step to the installer
- support either MySQL or SQLite as the chosen adapter
- persist the selected database configuration safely
- make SQLite capable of completing module installation
- keep visible installer copy fully localized in touched languages

Out of scope:

- PostgreSQL installer support
- general database abstraction refactors outside the installer path
- desktop or tablet-specific installer layout work

## Implementation Plan

### 1. Installer State And Step Flow

Files:

- `installer/app/InstallerStateStore.php`
- `installer/index.php`
- `installer/app/InstallerEnvironmentCheck.php`

Required outcomes:

- keep `database_*` values in installer state
- preserve state when moving back and forward through the installer
- make the environment step informative, not falsely blocking, before database selection
- keep the step order as `language -> welcome -> environment -> database -> setup -> modules -> ...`

Rules:

- the database check may report "not configured yet", but that must not block navigation to the database step
- extension checks must reflect capability correctly:
  - `pdo` remains required
  - `pdo_mysql` is required only for MySQL installs
  - `pdo_sqlite` is required only for SQLite installs
- if the installer has not yet chosen an engine, the environment screen should present availability clearly instead of failing simply because one optional driver is missing

### 2. Configuration Persistence

Files:

- `installer/app/InstallerConfigWriter.php`
- optionally `config/db.config.php`
- optionally `config/db.config.example.php`

Required outcomes:

- support a fresh environment where `config/db.config.php` does not yet exist
- create or overwrite installer-managed configuration safely
- generate valid config for both `PDO_MYSQL` and `PDO_SQLITE`
- keep the runtime-compatible `Kayit::ekle('database', ...)` and `Kayit::ekle('db_config', ...)` structure

Design requirements:

- do not rely on `is_writable()` for a missing target file; check writability of the containing directory when the file does not yet exist
- sanitize and normalize relative SQLite paths under project root
- reject unsafe or invalid paths instead of writing ambiguous config
- keep the generated file deterministic and minimal

### 3. SQLite Runtime Support

Files:

- `installer/app/SqliteSqlTransformer.php`
- `installer/installerbase.php`
- any installer runtime helpers touched by SQL execution

Required outcomes:

- module SQL files must be transformed before execution when the active adapter is SQLite
- MySQL installs must continue to execute raw SQL without transformation
- SQLite module installation must succeed for bootstrap modules and selected optional modules

Implementation requirements:

- wire `SqliteSqlTransformer::transform()` into the SQL-file install path used by modules
- verify multi-statement SQL files still execute correctly after transformation
- handle common MySQL-only constructs used by module SQL files, including at least:
  - `AUTO_INCREMENT`
  - inline or trailing `PRIMARY KEY`
  - `ENGINE`, `CHARSET`, `COLLATE`
  - `ENUM`
  - MySQL `KEY` syntax inside `CREATE TABLE`
- do not claim SQLite support is complete until module installation has been validated end-to-end

### 4. Database Step UI And Localization

Files:

- `installer/index.php`
- `installer/lang/en.php`
- `installer/lang/ku.php`
- any other touched installer language files

Required outcomes:

- all visible strings in the new database step must come from language files
- no inline fallback text or hard-coded labels in touched UI
- MySQL and SQLite modes must show only the fields relevant to the selected adapter
- database-step error states must render through the real installer error mechanism

Phone-first constraints:

- keep controls compact and touch-friendly
- avoid desktop-style two-column assumptions
- keep the primary action visible in a narrow viewport

### 5. Documentation And Version Logging

Files:

- `docs/VERSION.md`
- `docs/INSTALLER_SQLITE_SUPPORT_PLAN.md`

Required outcomes:

- do not record SQLite support as shipped before the installer can complete a real SQLite install
- when implementation is actually complete, append an accurate development note under the active Git-derived version bucket
- keep this plan aligned with the real remaining work rather than aspirational status

## Acceptance Criteria

The task is complete only when all of the following are true:

1. On a fresh environment, the installer can proceed to the database step without a pre-existing `config/db.config.php`.
2. Selecting SQLite creates or opens the target `.sqlite` file and persists a valid config.
3. Selecting MySQL still persists a valid config and passes connection validation.
4. Running the installer with SQLite completes bootstrap module installation without SQL syntax errors caused by MySQL-only DDL.
5. The database step has no hard-coded visible strings in touched files.
6. Installer error handling for the database step displays the real failure message.
7. `docs/VERSION.md` does not overstate completion before the above items are verified.

## Verification Plan

### Manual Verification

- start from a fresh checkout or environment where `config/db.config.php` is absent or installer-generated content is removed
- verify the environment step does not falsely fail just because the non-selected driver is unavailable
- choose SQLite and confirm:
  - the selected database path is validated
  - the target directory is writable or a clear error is shown
  - the `.sqlite` file is created
  - config is written successfully
  - bootstrap modules install successfully
- choose MySQL and confirm:
  - connection validation works
  - config is written successfully
  - the installer continues through setup normally
- test back-navigation and state persistence on the database step
- test a localized installer language and confirm the database step contains no English hard-coded labels unless English is the active locale

### Code Verification

- run `php -l` against all touched installer PHP files
- inspect the SQL execution path to confirm SQLite transformation is actually invoked
- review touched files for visible hard-coded text and leftover compatibility shortcuts

## Execution Order

1. Fix `InstallerConfigWriter` so fresh installs are supported.
2. Fix environment capability checks so engine availability is reported correctly.
3. Wire SQLite SQL transformation into module SQL execution.
4. Clean up database-step UI localization and error rendering.
5. Run verification for both SQLite and MySQL paths.
6. Only after verification, update `docs/VERSION.md` with accurate completion notes.
