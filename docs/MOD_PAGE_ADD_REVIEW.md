# Mod Page Remaining Review

This document tracks only the `mod_page` work that is still open after the recent add wizard, validation, menu sync, and list/delete fixes.

Reviewed area:

- [`templates/admin/mod_page/add.twig`](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/admin/mod_page/add.twig)
- [`templates/admin/partials/wizard_page.twig`](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/admin/partials/wizard_page.twig)
- [`templates/admin/assets/js/mod_page_wizard.js`](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/admin/assets/js/mod_page_wizard.js)
- [`templates/admin/mod_page/edit.twig`](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/admin/mod_page/edit.twig)
- [`templates/admin/assets/js/mod_page.js`](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/admin/assets/js/mod_page.js)
- [`templates/admin/assets/css/mod_page.css`](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/admin/assets/css/mod_page.css)
- [`templates/admin/mod_page/script.twig`](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/admin/mod_page/script.twig)
- [`mod/mod_page/index.php`](/home/malpersaz/Desktop/projects/htdocs/Rojname/mod/mod_page/index.php)
- [`mod/mod_page/validator/add.validator.php`](/home/malpersaz/Desktop/projects/htdocs/Rojname/mod/mod_page/validator/add.validator.php)
- [`mod/mod_page/validator/edit.validator.php`](/home/malpersaz/Desktop/projects/htdocs/Rojname/mod/mod_page/validator/edit.validator.php)

## Remaining Findings

### 1. Add and edit now use different UI systems and still need an intentional long-term contract

`add` is now a wizard-driven flow, while `edit` is still a card-based form.

References:

- [`templates/admin/mod_page/add.twig`](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/admin/mod_page/add.twig)
- [`templates/admin/partials/wizard_page.twig`](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/admin/partials/wizard_page.twig)
- [`templates/admin/mod_page/edit.twig`](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/admin/mod_page/edit.twig)

Risk:

- Admin users still learn two different interaction models for the same entity.
- Future field additions can drift between wizard and edit screens.

### 2. Add/edit field hydration is still duplicated in the controller

The add and edit flows still map request values into `mod_page_model` separately.

References:

- [`mod/mod_page/index.php`](/home/malpersaz/Desktop/projects/htdocs/Rojname/mod/mod_page/index.php)

Risk:

- A future field change can still be fixed in one flow and missed in the other.
- Normalization and persistence rules can drift again.

### 3. Lookup and tree-building logic still lives inside the controller

`loadFormLookups()` still handles:

- language loading
- nested parent tree building
- nested category tree building
- nested menu tree building
- page reference list building

Reference:

- [`mod/mod_page/index.php`](/home/malpersaz/Desktop/projects/htdocs/Rojname/mod/mod_page/index.php)

Risk:

- Controller responsibility remains too broad.
- Reuse in other modules stays harder than necessary.

### 4. Global admin asset dependency is still not fully localized

`mod_page` no longer ships its own CDN tags, but the admin layout still depends on remote assets.

References:

- [`templates/admin/layout.twig`](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/admin/layout.twig)
- [`templates/admin/assets/js/mod_page.js`](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/admin/assets/js/mod_page.js)

Risk:

- Admin behavior still depends on external availability.
- Offline or restricted-network admin usage is still fragile.

### 5. The new add wizard still needs hardening and cleanup

The wizard now works end-to-end, but it is still a first-generation implementation.

References:

- [`templates/admin/partials/wizard_page.twig`](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/admin/partials/wizard_page.twig)
- [`templates/admin/assets/js/mod_page_wizard.js`](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/admin/assets/js/mod_page_wizard.js)

Open points:

- extract repeated text/step labels from inline English strings into language files
- add stronger fail-safe handling if TinyMCE or remote assets fail to load
- decide whether success flow should stay classic redirect or move to AJAX submit with inline success state
- reduce cache-busting by moving to a clearer asset versioning strategy

## Remaining Recommended Fixes

### Phase 1: Stabilization

1. Move wizard step titles, helper text, and button text fully into `mod_page` language files.
2. Add front-end fail-safe behavior when TinyMCE or shared admin assets are unavailable.
3. Review the wizard success/error flow once more after real browser testing and remove any remaining reload-state edge cases.

### Phase 2: Refactor

4. Extract a shared `hydratePageModelFromRequest()` method.
5. Extract lookup/tree builders out of `mod/mod_page/index.php`.

### Phase 3: UX Direction

6. Decide whether `edit` should also move to wizard mode, or whether `add` and `edit` should intentionally remain different.
7. If one UX direction is chosen, extract shared field/section partials so future field changes land in one place.

### Phase 4: Admin Asset Strategy

8. Remove the remaining global admin CDN dependency where practical.
9. Decide whether TinyMCE and TomSelect are required dependencies or optional enhancements.

## Suggested Next Implementation Order

### Step 1

Stabilize the new add wizard with language-file cleanup and browser-level smoke testing.

### Step 2

Extract shared request-to-model hydration in [`mod/mod_page/index.php`](/home/malpersaz/Desktop/projects/htdocs/Rojname/mod/mod_page/index.php).

### Step 3

Move language-aware lookup generation into helper/service-level code.

### Step 4

Choose the long-term UX direction for add/edit and refactor toward one deliberate pattern.

### Step 5

Localize the remaining admin asset dependencies at layout level.
