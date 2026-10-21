# Milestone 1 Handoff Report: Admin Layout Separation, Modal Engine & Boot Strategy

**Agent**: Explorer 3 (`m1_explorer_3`)  
**Parent**: `a9a3fe81-914d-4f2b-a17e-0a0963c2c6ca` (parent)  
**Date**: 2026-08-23  

---

## 1. Observation

1. **`templates/admin/layout.twig`**:
   - Lines 15–27 contain an inline `<style>` block declaring CSS custom properties (`--af-radius-*`, `--md-sys-color-error*`).
   - Lines 63–65 contain a legacy mobile bottom navbar include (`{% include 'admin/partials/navbar.twig' %}`).
   - Lines 262–286 contain script references without `af_modal_engine.js` or `modal_user_menu.js`, and lines 264–284 have an inline `<script>` listener for `DOMContentLoaded` flash snackbars.
2. **`templates/admin/assets/css/style.css`**:
   - Lines 362–382 contain `:root` Material 3 definitions but omit the radius and error color custom properties present in `layout.twig`.
3. **`templates/admin/assets/` Directory**:
   - Subdirectories `templates/admin/assets/css/modals/` and `templates/admin/assets/js/modals/` do not exist in `AF/Bulletin`.
   - In `AF/Business`, these directories contain the centralized SPA modal navigation stack (`af_modal_engine.js`, `modal_user_menu.js`, `modal_newsletter.js`, `modal_redirects.js`, `modal_site_status.js`, `modal_theme_logo.js`, `modal_user_profile.js`, `core_modals.css`).
4. **Boot Sequence & `lib/Registry.class.php`**:
   - `init.php:65` attempts to require `lib/Registry.class.php`, but the file is missing in `AF/Bulletin` (only legacy `Kayit.class.php` and `Register.class.php` exist).
   - `config/*.php` files call `Registry::ekle(...)`.
   - Running `php -d display_errors=1 -r "require_once 'init.php';"` fails with a fatal error due to the missing `lib/Registry.class.php`.
   - Running `php tests/module_management_runtime_test.php` passes with 6/6 test cases.

---

## 2. Logic Chain

1. **Clean Separation**: Strict Code Separation Mandate (Rule 2) prohibits inline `<style>` and `<script>` in Twig/PHP templates. Moving `--af-radius-*` and `--md-sys-color-error*` into `:root` in `templates/admin/assets/css/style.css` allows deleting the `<style>` block from `layout.twig`.
2. **Modal Engine Integration**: `AF/Business` provides a complete modal stack (`af_modal_engine.js` + `modal_user_menu.js` + `core_modals.css`) that manages bottom sheets, dialogs, and popstate navigation cleanly via `[data-action]` delegation without inline event handlers or inline scripts. Adding these to `layout.twig` and syncing asset files enables modern admin functionality for all modules.
3. **Boot Strategy**: Framework bootstrap in `init.php` relies on `Registry::get('autoload_config')` loaded from `config/autoload.config.php`. Porting `lib/Registry.class.php` from `AF/Business` and updating `Registry::ekle` to `Registry::add` in `config/*.php` satisfies the boot contract and unblocks `init.php`.
4. **Prerequisites for Milestone 2**: Milestone 2 removes deprecated modules (`mod_client`, `mod_service`, `mod_product`, `mod_reference`). It requires a cleanly booting core, stable admin layout, and synced modal engine to prevent regressions during file removals and route cleanups.

---

## 3. Caveats

- In `templates/admin/partials/sheets_extra.twig`, lines 4–395 contain a 391-line `<style>` block that also requires separation into CSS in a subsequent step (or during full asset synchronization).
- `Load.class.php` and `InstallationState.php` contain legacy `Registry::getir` and `Registry::varmi` calls that should be updated to `Registry::get` and `Registry::has` during M1 execution.

---

## 4. Conclusion

The plan for Milestone 1 is fully defined:
1. Port canonical `lib/Registry.class.php` with methods `get`, `add`, `has`, `set`, `remove`, `all`.
2. Update all `config/*.php` files to use `Registry::add`.
3. Update `lib/Load.class.php` and `lib/InstallationState.php` to use standard `Registry` methods and remove `lib/Kayit.class.php` and `lib/Register.class.php`.
4. Move inline CSS variables from `templates/admin/layout.twig` into `templates/admin/assets/css/style.css`.
5. Sync modal engine CSS and JS files from `AF/Business/templates/admin/assets/` into `AF/Bulletin/templates/admin/assets/`.
6. Update `templates/admin/layout.twig` with modal scripts, clean CSS links, safe JSON escaping, and 0 inline scripts.

---

## 5. Verification Method

1. **Boot Verification**:
   ```bash
   php -d display_errors=1 -r "require_once 'init.php'; echo 'BOOT_OK\n';"
   ```
   *Expected output*: `BOOT_OK` (exit code 0).
2. **Runtime Test**:
   ```bash
   php tests/module_management_runtime_test.php
   ```
   *Expected output*: `All module management runtime tests passed.` (exit code 0).
3. **Syntax Linting**:
   ```bash
   find config lib mod templates installer -name "*.php" -exec php -l {} +
   ```
   *Expected output*: `No syntax errors detected` across all files.
4. **Layout Separation Check**:
   ```bash
   grep -rn "<style" templates/admin/layout.twig
   ```
   *Expected output*: No matches (0 inline `<style>` tags).
