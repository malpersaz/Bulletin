# Module Management Validation Checklist

## Purpose

This checklist defines the minimum manual regression flow for the module readiness work until automated coverage is added.

The goal is to verify that optional front-end features fail safely, admin visibility follows runtime readiness, and module status toggles behave as expected.

## Preconditions

- a local environment is running and reachable
- the `mods` table exists
- the latest module management changes are present
- an admin user with `mod_user` ACL permission is available for management validation

## CLI Smoke Check

Run the baseline smoke check first:

```bash
php tasks/module_management_smoke.php
```

Optional JSON output:

```bash
php tasks/module_management_smoke.php --json
```

## Runtime Logic Test

Run the isolated runtime decision test suite:

```bash
composer test:module-management
```

Or directly:

```bash
php tests/module_management_runtime_test.php
```

## Public Route Validation

### Content flow

1. Confirm `mod_content` and `mod_category` exist in `mods` and both tables exist.
2. Open `/contents`.
3. Verify the route loads without a database exception.
4. Remove or rename the `mod_category` table in a disposable environment.
5. Open `/contents` again.
6. Verify the result is a controlled `404` flow, not a raw SQL table-not-found exception.
7. Restore the table.
8. Remove the `mod_content` row from `mods` in a disposable environment.
9. Open `/contents`.
10. Verify the result is a controlled `404` flow.

### News flow

1. Confirm `mod_news` and `mod_news_category` are installed and ready.
2. Open `/newses`.
3. Verify the route loads without a database exception.
4. Remove or rename `mod_news_category` in a disposable environment.
5. Open `/newses`.
6. Verify the result is a controlled `404` flow.

### Publication flow

1. Confirm `mod_publication` and `mod_publication_category` are installed and ready.
2. Open `/publications`.
3. Verify the route loads without a database exception.
4. Remove or rename `mod_publication_category` in a disposable environment.
5. Open `/publications`.
6. Verify the result is a controlled `404` flow.

### Video embed flow

1. Confirm `mod_video_embed` and `mod_video_embed_category` are installed and ready.
2. Open the public video listing route used by the project.
3. Verify the route loads without a database exception.
4. Remove or rename `mod_video_embed_category` in a disposable environment.
5. Open the same route again.
6. Verify the result is a controlled `404` flow.

## Widget And Helper Validation

1. Load a page that renders latest content, latest news, latest publication, latest authors, or latest videos widgets.
2. Disable the backing module or remove a required dependency table in a disposable environment.
3. Reload the page.
4. Verify the page still renders.
5. Verify the affected widget returns empty output or is suppressed cleanly.
6. Verify no raw SQL exception is shown.

## Management Validation

### Readiness visibility

1. Sign in to the admin panel on a phone-sized viewport.
2. Open the navigation drawer.
3. Inspect the module health section.
4. Verify `mod_content`, `mod_news`, `mod_publication`, and `mod_video_embed` display a status badge.
5. Break one dependency in a disposable environment.
6. Reload the admin panel.
7. Verify the affected module shows a non-ready state and lists missing modules or missing tables.

### Enable and disable flow

1. In the admin drawer, disable `mod_content`.
2. Verify the module health card switches to `disabled`.
3. Open `/contents`.
4. Verify the route now fails closed with a controlled `404` flow.
5. Re-enable `mod_content`.
6. Verify `/contents` works again when dependencies and tables are present.

## Installer Validation

1. In a disposable environment, try installing `mod_content` without `mod_category`.
2. Verify the installer blocks or degrades the install instead of leaving a silent broken state.
3. Verify the `mods` row reflects the expected status.
4. Try uninstalling `mod_category` while `mod_content` is still installed.
5. Verify the uninstall is blocked.

## Logging Validation

1. Trigger a blocked public feature such as `/contents` with a missing dependency.
2. Inspect PHP error logs.
3. Verify a `module_runtime_blocked` entry exists.
4. Confirm the payload includes:
   - `feature`
   - `reason`
   - `missing_modules`
   - `missing_tables`
   - request context such as URL

## Exit Criteria

The validation pass is complete only when:

- blocked public routes fail with controlled `404` behavior
- widgets degrade safely
- admin health indicators match runtime reality
- enable and disable toggles change actual front-end exposure
- installer dependency protections are observable
- blocked-feature logs include actionable failure reasons
