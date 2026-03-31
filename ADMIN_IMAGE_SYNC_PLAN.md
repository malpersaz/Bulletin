# Admin Image Uploader Synchronization Plan

## Goal

Apply the image upload and editing technology currently implemented for `mod_news` to:

- `mod_content`
- `mod_publication`

The scope must cover admin `list`, `add`, and `edit` flows and bring all three modules onto the same frontend and backend image-handling pattern.

This includes CSS parity as a hard requirement:

- the uploader-related CSS used by `mod_news` must also be transferred to `mod_content` and `mod_publication`
- list modal styling and edit-shell styling must match `mod_news` one-to-one unless a deliberate, documented exception is approved
- behavior parity alone is not sufficient; visual and responsive parity is also required

## Research Summary

### 1. `mod_news` is already using the new uploader architecture

The `mod_news` module has already moved to a reusable uploader-based implementation in key places:

- Shared uploader partial:
  - `templates/admin/partials/mod_news_image_uploader.twig`
- Shared uploader engine:
  - `templates/admin/assets/js/script.js`
  - `createImageUploader(...)`
  - `initializeWizardImageInteractions()`
  - `initializeEditImageInteractions()`
- Modern list image modal:
  - `templates/admin/mod_news/list.twig`
  - `templates/admin/assets/js/mod_news.js`
- Modern edit image shell:
  - `templates/admin/mod_news/edit.twig`

This stack supports:

- drag and drop
- paste from clipboard
- remote image URL import
- reusable preview state management
- existing image rendering
- file metadata rendering
- image reset/remove behavior
- list-level image editing through a modal instead of direct file input

### 2. `mod_content` and `mod_publication` are still on mixed / older image flows

#### Add flow

The add pages for both modules already open the common wizard:

- `templates/admin/mod_content/add.twig`
- `templates/admin/mod_publication/add.twig`

However, inside the shared wizard partial:

- `templates/admin/partials/wizard_content.twig`

only `mod_news` uses the new uploader partial on Step 7.

For `mod_content` and `mod_publication`, Step 7 still falls back to the older simple pattern:

- click-to-upload block
- basic hidden file input
- plain image preview
- no drag/drop
- no URL import
- no uploader state abstraction

#### Edit flow

`mod_content/edit.twig` and `mod_publication/edit.twig` still use:

- inline `<label>` upload area
- `FileReader` preview logic embedded in page script
- module-specific remove-image JS
- no shared uploader shell
- no URL import
- no drag/drop
- no clipboard paste

In contrast, `mod_news/edit.twig` already uses the reusable image shell and shared edit uploader logic.

#### List flow

`mod_content.js` and `mod_publication.js` still use a direct file-input upload shortcut:

- create hidden `<input type="file">`
- upload immediately after selection
- refresh list

There is no modal sheet, no reusable uploader state, no remove-existing-image action, and no parity with `mod_news/list`.

### 3. Backend upload endpoints are already largely aligned

All three modules already expose `ajax_image_upload()` endpoints with the same essential behavior:

- permission check
- record lookup by `id`
- moderation check
- image resize via `Load::mod('image')`
- write original + thumb variants
- delete previous image before saving the new one
- persist new image name to module table
- return JSON response

Files:

- `mod/mod_news/index.php`
- `mod/mod_content/index.php`
- `mod/mod_publication/index.php`

This means the main synchronization burden is frontend and endpoint naming consistency, not image-processing capability.

### 4. Remove-image endpoints are not fully normalized

Current situation:

- `mod_news` uses `image_remove_image()`
- `mod_content` uses `image_remove_image()`
- `mod_publication` uses `remove_image()`

Return values are also inconsistent:

- `mod_news` remove returns `Success`
- `mod_content` remove returns `Oke`
- `mod_publication` remove returns `Oke`

This matters because the shared edit/list removal behavior in the new stack expects a predictable endpoint contract.

## Current-State Gap Matrix

| Area | mod_news | mod_content | mod_publication | Gap |
|---|---|---|---|---|
| Add wizard image step | New shared uploader | Old simple upload block | Old simple upload block | High |
| Edit image UI | Shared uploader shell | Legacy inline preview/remove | Legacy inline preview/remove | High |
| List image UI | Modal uploader + remove | Direct instant upload only | Direct instant upload only | High |
| Drag/drop | Yes | No | No | High |
| Clipboard paste | Yes | No | No | High |
| Remote URL import | Yes | No | No | High |
| Existing image state handling | Yes | Partial/legacy | Partial/legacy | Medium |
| Remove endpoint consistency | Yes | Partial | No | High |
| Shared JS abstraction usage | Yes | Very limited | Very limited | High |

## Target Architecture

All three modules should converge on one reusable admin image system with module-specific configuration only.

### Shared building blocks

Reuse these existing assets as the base:

- `templates/admin/partials/mod_news_image_uploader.twig`
- `templates/admin/assets/js/script.js`
  - `createImageUploader(...)`
  - wizard image helpers
  - edit image helpers

Reuse the `mod_news` uploader CSS as the visual source of truth:

- list modal CSS currently embedded in `templates/admin/mod_news/list.twig`
- edit shell CSS currently embedded in `templates/admin/mod_news/edit.twig`

### Module-specific integrations

Each module should only supply:

- language strings
- route URLs
- existing image URL
- remove endpoint URL
- success/error messages
- module thumb path

### Resulting behavior

After synchronization, `mod_news`, `mod_content`, and `mod_publication` should all support:

- identical add wizard image UX
- identical edit page image UX
- identical list image modal UX
- identical upload/remove response handling
- consistent empty / existing / pending image states

## Implementation Plan

## Phase 1: Normalize the shared contract

### 1. Normalize remove-image endpoint naming

Recommended target:

- all modules should expose `image_remove_image`

Actions:

1. Keep existing endpoints temporarily for backward compatibility.
2. Add `image_remove_image()` to `mod_publication/index.php` as the canonical method.
3. Either:
   - make `remove_image()` call `image_remove_image()`, or
   - keep `remove_image()` as a compatibility alias.

### 2. Normalize remove-image success response

Recommended target response:

- text response: `Success`

Reason:

- `mod_news` already uses it in the new list modal flow.
- shared JS becomes simpler if one success token is expected everywhere.

Actions:

1. Update `mod_content/index.php` remove action to return `Success`.
2. Update `mod_publication/index.php` remove action to return `Success`.
3. Ensure legacy callers are updated at the same time so no page still expects `Oke`.

### 3. Verify upload JSON contract consistency

Target response on successful upload:

```json
{
  "status": "success",
  "url": "<thumb-url>"
}
```

This is already effectively true. The task here is only to confirm there are no module-specific deviations before refactor.

## Phase 2: Synchronize Add Wizard Step 7

### 1. Replace fallback Step 7 blocks in `wizard_content.twig`

Current issue:

- `wizard_content.twig` uses the advanced uploader partial only when `wizard_module == 'mod_news'`.
- `mod_content` and `mod_publication` still render the older upload UI.

Plan:

1. Remove the `mod_news`-only conditional around the advanced uploader UI.
2. Render the shared uploader partial for all supported modules.
3. Feed module-specific language data using the already available `wizard_lang`.

### 2. Ensure shared JS is truly module-agnostic

Audit and adjust if needed:

- `getWizardImageI18n()`
- `initializeWizardImageInteractions()`
- `previewWizardImage()`
- `prepareAndSubmitWizard()`

Expected result:

- add flow in `mod_content` and `mod_publication` gets drag/drop, paste, URL import, and shared preview behavior without module-specific duplicate code.

### 3. Ensure wizard CSS parity

Even though the add flow is rendered through the shared wizard, CSS parity still needs explicit verification:

1. Confirm the shared uploader partial renders with the same structural classes for all modules.
2. Confirm any uploader-specific CSS used in the wizard is not conditionally tied to `mod_news`.
3. If uploader CSS is currently scattered, extract it into a shared admin stylesheet or shared Twig style block so all modules inherit the exact same presentation.

### 4. Preserve backend field compatibility

The wizard must continue submitting:

- `image`
- `lang`
- `category_id`
- `name`
- `created_at`
- `summary`
- `content`
- `seo`
- `title`
- `keywords`
- `description`
- `status`
- `deck`
- `gallery_id`
- `file_id`
- `acl_id`
- `menu`
- `video`

No backend payload contract should be broken during the UI change.

## Phase 3: Synchronize Edit Pages

### 1. Port the `mod_news/edit.twig` image shell pattern to `mod_content/edit.twig`

Replace legacy block:

- current inline upload label
- inline preview image
- inline remove button
- inline `FileReader` preview logic

With:

- shared `mod_news_image_uploader.twig` partial
- `editImageShell`
- module-specific `data-*` configuration
- shared `previewEditImage`, `removeEditImage`, `initializeEditImageInteractions`

Key module-specific values:

- thumb path: `mod_content/thumb/`
- remove URL: `mod_content/image_remove_image`
- success/error translations from `lang.mod_content`

### 2. Port the same pattern to `mod_publication/edit.twig`

Replace legacy edit image implementation with the same shared shell.

Key module-specific values:

- thumb path: `mod_publication/thumb/`
- remove URL: canonical target should become `mod_publication/image_remove_image`
- success/error translations from `lang.mod_publication`

### 3. Transfer edit CSS one-to-one from `mod_news`

This is now a strict requirement.

Transfer the uploader-related edit CSS from `templates/admin/mod_news/edit.twig` to:

- `templates/admin/mod_content/edit.twig`
- `templates/admin/mod_publication/edit.twig`

This specifically includes:

- `.image-upload-area`
- `.edit-image-shell`
- `.edit-image-shell .wizard-image-dropzone`
- `.edit-image-shell .wizard-image-preview-card`
- `.edit-image-shell .wizard-image-preview-frame`
- `.edit-image-shell .wizard-image-url-row`
- `.edit-image-shell .wizard-image-url-input`
- `.edit-image-shell .wizard-image-url-row .m3-btn-filled`
- `.edit-image-shell .wizard-image-empty`
- `.edit-image-shell .wizard-image-meta`
- associated mobile breakpoint rules

Recommendation:

1. Do not manually re-interpret these styles separately per module.
2. Copy the same rules verbatim first.
3. After parity is verified, optionally extract them into a shared stylesheet or shared Twig include.

### 4. Remove obsolete inline edit JS

After migration, remove duplicated page-level logic from both edit templates:

- local `previewEditImage()` implementations
- page-specific `removeEditImage()` implementations
- any legacy DOM rebuilding logic tied to the old `.image-upload-area`

The edit pages should rely on shared global uploader code instead of template-local implementations.

### 5. Keep delete-state semantics intact

The shared edit flow already uses:

- `delete_image_flag`
- existing image rendering
- pending image state

Before rollout, verify the corresponding backend edit handlers correctly interpret:

- replacing an image
- removing an image
- leaving existing image unchanged

If backend edit methods do not fully honor `delete_image`, align them during the same refactor.

## Phase 4: Synchronize List Pages

### 1. Port the `mod_news/list.twig` modal uploader structure to `mod_content/list.twig`

Add:

- modal markup
- modal footer
- modal title/subtitle placeholders
- uploader partial instance for list flow
- module-specific `CONTENT_LANG` additions
- list-level styles for modal sheet and uploader shell

### 2. Port the same structure to `mod_publication/list.twig`

Apply the same markup and behavior with module-specific translations and routes.

### 3. Transfer list modal CSS one-to-one from `mod_news`

This is also a strict requirement.

Transfer the modal/uploader CSS block from `templates/admin/mod_news/list.twig` to:

- `templates/admin/mod_content/list.twig`
- `templates/admin/mod_publication/list.twig`

This specifically includes:

- `.news-image-modal`
- `.news-image-modal.active`
- `.news-image-modal__scrim`
- `.news-image-modal__sheet`
- `.news-image-modal__body`
- `.news-image-shell`
- `.news-image-shell .wizard-image-url-row`
- `.news-image-shell .wizard-image-url-input`
- `.news-image-shell .wizard-image-dropzone`
- `.news-image-shell .wizard-image-meta`
- `.news-image-save`
- `.news-image-modal__footer`
- `body.news-image-modal-open`
- associated mobile breakpoint rules

Important note:

- class names may remain `news-image-*` during first transfer if the priority is exact parity and minimum risk
- if renamed to generic class names, the rename must be applied consistently in markup, JS, and CSS with no visual drift

### 4. Upgrade `mod_content.js`

Replace the direct hidden-file-input upload path with the `mod_news.js` pattern:

- modal open/close state
- `imageModalState`
- `initListImageUploader()`
- `resetListImageUploader()`
- `handleListImageRemoveAction()`
- `removeExistingListImage()`
- `submitListImageUpload()`

Keep module-specific parts configurable:

- `MODULE_URL`
- language strings
- remove endpoint

### 5. Upgrade `mod_publication.js`

Apply the same list uploader refactor as `mod_content.js`.

### 6. Normalize card dataset usage

The new list modal flow depends on card metadata:

- `data-image-src`
- `data-image-name`

Ensure content/publication card rendering matches the `mod_news` card contract so the modal can render existing images correctly.

## Phase 5: Language and Copy Synchronization

### 1. Confirm required translation keys exist

The new uploader depends on wizard image copy such as:

- `wizard.image.drop_title`
- `wizard.image.drop_description`
- `wizard.image.upload_link`
- `wizard.image.link_separator`
- `wizard.image.link_placeholder`
- `wizard.image.link_action`
- `wizard.image.hint_loading`
- `wizard.image.hint_invalid_type`
- `wizard.image.hint_no_drop_data`
- `wizard.image.hint_remote_failed`
- `wizard.summary.selected_none`

Verify these keys exist for:

- `mod_content`
- `mod_publication`

If any are missing in active languages, add them before UI rollout.

### 2. Confirm generic button/message keys

Needed especially for list/edit remove flows:

- remove-image title
- remove confirmation
- remove success
- generic error
- save/cancel buttons
- loading text

## Phase 6: CSS and UX Harmonization

### 1. Treat `mod_news` CSS as the canonical baseline

For this synchronization effort, the CSS source of truth is the current `mod_news` implementation.

That means:

- `mod_content` must visually match `mod_news`
- `mod_publication` must visually match `mod_news`
- uploader modal spacing, sizing, border radius, states, and responsive behavior must be aligned exactly

### 2. Reuse the same visual shell in all modules

Recommended:

- keep one visual standard derived from `mod_news`
- avoid separate module-specific visual variants unless required by content type

Best implementation option:

1. First transfer the same CSS rules exactly.
2. Then consolidate them into a shared admin CSS source to avoid future drift.

Possible consolidation targets:

- a shared admin uploader partial style block
- a dedicated shared admin uploader stylesheet under `templates/admin/assets/`

### 3. Verify responsive behavior

Must work in:

- mobile admin shell
- narrow simulator width
- desktop-width preview
- same breakpoints and sheet behavior as `mod_news`

### 4. Verify visual parity explicitly

Test visually against `mod_news` for both `mod_content` and `mod_publication`:

1. Empty uploader state
2. Existing image state
3. Pending-image state
4. Remove button placement
5. URL import input/button layout
6. Mobile bottom-sheet modal behavior
7. Desktop centered modal behavior

### 5. Verify interaction edge cases

Test:

- drag image onto uploader
- paste screenshot from clipboard
- import image by HTTPS URL
- remove pending image before save
- remove persisted image
- open modal with existing image
- open modal with no image
- cancel modal after selecting new file

## Phase 7: Regression and Verification

## Functional test matrix

### Add

For `mod_content` and `mod_publication`:

1. Add record with uploaded local image.
2. Add record with pasted clipboard image.
3. Add record with remote URL image.
4. Add record with no image.

### Edit

For `mod_content`, `mod_publication`, and re-check `mod_news`:

1. Replace existing image.
2. Remove existing image.
3. Add image to record that previously had none.
4. Cancel after selecting a new image and confirm existing image state is restored.

### List

For all three modules:

1. Open image modal from a record with image.
2. Open image modal from a record without image.
3. Upload new image from modal.
4. Remove existing image from modal.
5. Verify card thumbnail refresh after success.
6. Verify modal CSS is visually identical to `mod_news`.

### Backend / files

For all three modules:

1. Confirm original file written to `static/<module>/`.
2. Confirm thumb written to `static/<module>/thumb/`.
3. Confirm previous image files are deleted after replacement.
4. Confirm DB `image` column updates correctly.

## Recommended Delivery Order

1. Normalize backend remove endpoint and response contract.
2. Generalize wizard Step 7 for all modules.
3. Migrate `mod_content/edit`.
4. Migrate `mod_publication/edit`.
5. Migrate `mod_content/list`.
6. Migrate `mod_publication/list`.
7. Run cross-module regression pass.
8. Remove dead legacy JS and template code.

## Risks

### 1. Shared JS may still contain hidden `mod_news` assumptions

Even though the uploader engine is mostly reusable, some naming and message assumptions were introduced while building `mod_news` first. The migration should explicitly audit:

- hardcoded labels
- hardcoded success tokens
- assumptions about endpoint names
- assumptions about modal IDs

### 2. Edit removal flow currently has mixed success-token expectations

The shared edit logic checks for `Success`, while older content/publication code paths were built around `Oke`. This must be reconciled before switching those templates to shared code.

### 3. Partial naming is misleading

`mod_news_image_uploader.twig` is already functioning as a generic uploader component even though its filename is news-specific. This can create maintenance confusion.

Recommended follow-up:

- either rename it to a generic path such as `admin/partials/image_uploader.twig`
- or keep current filename for now and note clearly in code comments that it is shared across modules

### 4. CSS drift risk is high if copied manually in pieces

If CSS is partially retyped or selectively adapted:

- spacing drift will appear
- mobile behavior may diverge
- future fixes will need to be repeated three times

To avoid this:

1. Copy the `mod_news` CSS blocks exactly during first migration.
2. Consolidate them into a shared source immediately after parity is confirmed.

## Refactor Recommendation

If time allows, perform a light cleanup while synchronizing:

### Rename toward generic ownership

Potential cleanup targets:

- `templates/admin/partials/mod_news_image_uploader.twig` -> generic shared partial
- list modal class names such as `news-image-modal` -> generic class naming

This is not mandatory for the first rollout, but it would reduce future confusion when more modules adopt the same stack.

## Definition of Done

The synchronization is complete when:

1. `mod_content` and `mod_publication` use the same uploader technology as `mod_news` in admin `add`, `edit`, and `list`.
2. All three modules support drag/drop, paste, URL import, preview, replace, and remove.
3. All three modules use the same uploader-related CSS presentation as `mod_news` for list modal and edit shell.
4. All three modules use a consistent backend image remove contract.
5. Legacy inline image code in content/publication templates is removed.
6. Cross-module regression tests confirm matching behavior and no image-processing regressions.
