# mod_news List Image Upload Synchronization Plan

## Objective

Bring the image upload experience on `http://localhost:8080/en/mod_news/list` into alignment with the newer image upload technology already used by `templates/admin/mod_news/add.twig`.

The target is not just visual consistency. The goal is to unify behavior, reduce duplicated logic, and make the list, add, and edit flows rely on the same image-upload interaction model.

## Current State

### Add flow

The add page uses the modern wizard-based image uploader:

- `templates/admin/mod_news/add.twig`
- `templates/admin/partials/wizard_content.twig`
- `templates/admin/assets/js/script.js`
- `templates/admin/assets/css/style.css`

This uploader already supports a richer experience:

- drag and drop
- paste from clipboard
- import from image URL
- inline preview
- file metadata display
- structured error feedback
- local `File` generation from remote URLs before form submission

### Edit flow

The edit page uses a visually similar image uploader and offers nearly the same feature set:

- `templates/admin/mod_news/edit.twig`
- `templates/admin/assets/js/script.js`

The important nuance is that add and edit are not yet backed by a single shared uploader engine.

Today, `script.js` contains two parallel implementations:

- one implementation for the wizard/add context
- one implementation for the edit context

So while add and edit already look aligned in the UI, the underlying logic is still duplicated.

### List flow

The list page does not use the same uploader stack.

Relevant files:

- `templates/admin/mod_news/list.twig`
- `templates/admin/assets/js/mod_news.js`

The current list-page image upload path is handled in `processImageUpload(id)` inside `mod_news.js`. That flow uses:

- a hidden file input created dynamically
- direct file selection only
- upload to `mod_news/ajax_image_upload`
- no preview shell
- no drag-and-drop
- no URL import
- no clipboard paste
- no shared UI state with add/edit

This means the list page is functionally behind the add page and maintained as a separate upload technology path.

## Problem Statement

There are currently effectively three upload paths that need to converge:

1. A modern uploader implementation for the add wizard.
2. A similar but separate uploader implementation for edit.
3. A legacy-like lightweight upload path in the list flow.

This creates several issues:

- inconsistent user experience
- duplicated upload logic
- inconsistent validation and feedback
- higher maintenance cost
- increased regression risk when image upload behavior changes

## Target Outcome

After the work is complete, `mod_news/list`, `mod_news/add`, and `mod_news/edit` should share the same image upload interaction model.

The list page should support:

- file picker upload
- drag-and-drop
- paste image from clipboard
- import image from URL
- image preview before upload
- clear remove/reset behavior before submission
- consistent error and loading feedback

The list page should also remain practical for mobile admin usage and should not damage the card layout or scrolling experience.

## UX Recommendation

### Recommended approach

Do not embed the full advanced uploader directly inside each list card.

Instead, open a focused uploader surface from the card thumbnail click. This surface should be one of:

- a bottom sheet
- a modal
- a lightweight overlay anchored to the current admin UI language

### Why this is the better option

Embedding the full uploader directly inside every list card would create problems:

- cards become visually heavy
- list density drops
- mobile scrolling becomes harder
- drag/drop and URL input become awkward in-card
- layout regressions become more likely

Using an overlay or sheet keeps the list compact while still exposing the same modern upload capability used in add/edit flows.

## Technical Strategy

### Phase 1: Consolidate add and edit into one uploader engine

The current modern uploader logic in `templates/admin/assets/js/script.js` is still strongly tied to specific hard-coded IDs such as:

- `wizardImageShell`
- `wizardImgInput`
- `wizardImageUrlInput`
- `wizardImageDropzone`
- `wizardImagePreviewCard`

Before the list page can reuse this stack cleanly, the existing add and edit implementations should first be collapsed into a single reusable initializer or factory.

### Desired refactor direction

Extract the image uploader into a reusable API such as:

```js
initializeImageUploader({
  root,
  input,
  preview,
  dropzone,
  urlInput,
  importButton,
  removeButton,
  mode,
  callbacks
});
```

This reusable layer should support:

- add mode
- edit mode
- list quick-upload mode

### Reusable behaviors to centralize

- drag/drop handling
- clipboard paste handling
- URL import handling
- file validation
- preview rendering
- feedback state rendering
- reset/removal behavior
- optional persistence callbacks
- existing-image hydration for edit
- edit-specific delete flows and hidden-flag handling

## Proposed Implementation Plan

### Step 1: Extract a reusable image uploader core

Create a shared uploader abstraction in `templates/admin/assets/js/script.js` or a dedicated new shared asset if separation becomes necessary.

Deliverables:

- remove hard dependency on `wizardImage*` and `editImage*` IDs
- use container-scoped selectors or passed DOM references
- unify the duplicated add/edit image logic under one internal engine
- keep existing wizard add flow working without behavioral changes
- keep current edit flow working with the same shared helpers

Edit-specific requirements to preserve:

- existing image hydration on first render
- remove existing image via backend endpoint
- `delete_image_flag` behavior
- fallback from pending image state back to the original existing image when appropriate

Expected outcome:

- add and edit truly use the same internal uploader engine
- list becomes able to consume the same engine without copy-paste logic

### Step 2: Add list-page uploader UI container

Extend `templates/admin/mod_news/list.twig` with a dedicated uploader surface.

Recommended UI:

- hidden modal or bottom sheet in DOM
- opened when a card thumbnail is clicked
- reuses the same shell structure already used by the modern uploader:
  - dropzone
  - preview card
  - image metadata
  - URL input
  - import action
  - remove/reset action
  - save/upload action

The markup can remain list-specific in placement, but its internal structure should stay as close as possible to the existing `wizard-image-shell` pattern so styling and behavior stay aligned.

### Step 3: Replace `processImageUpload(id)` in `mod_news.js`

The current `processImageUpload(id)` method should stop creating and using a hidden file input as the main UX.

Instead it should:

- store the selected content ID
- open the new uploader modal/sheet
- initialize or hydrate the uploader for that content item
- submit the selected/generated image file to `mod_news/ajax_image_upload`

The upload endpoint can remain unchanged if the front end continues sending a `File` via `FormData`.

### Step 4: Keep URL import frontend-only at first

To minimize backend risk, URL import should initially stay frontend-driven:

- fetch remote image in the browser
- convert response to `Blob`
- create a `File`
- submit the `File` to the existing `ajax_image_upload` endpoint

Benefits:

- no backend contract change required
- lower implementation risk
- faster rollout

If backend-side remote fetch support is later needed, that can be added as a second-stage enhancement.

### Step 5: Add upload completion behavior

After a successful upload from the list page:

- close the uploader modal/sheet
- refresh the affected card thumbnail

Implementation options:

1. Simple path: call `loadData(true)` and refresh the list.
2. Better path: patch only the affected card thumbnail in the DOM.

Recommendation for the first implementation:

- start with `loadData(true)` for stability
- optimize to targeted DOM updates only if needed

### Step 6: Normalize feedback and error handling

Replace `alert(...)` usage in list upload flow with the same style used elsewhere in admin:

- inline uploader feedback for validation and URL errors
- `Swal` or snackbar for upload success/failure

This is important because uploader technology synchronization is not complete if the visual feedback model still differs.

### Step 7: Update asset versioning

Because this work changes shared JS and potentially shared CSS, asset version strings should be bumped where needed to avoid stale browser cache masking the refactor.

## File-Level Change Scope

### Likely files to modify

- `templates/admin/mod_news/list.twig`
- `templates/admin/assets/js/mod_news.js`
- `templates/admin/assets/js/script.js`
- `templates/admin/mod_news/edit.twig`
- potentially `templates/admin/assets/css/style.css`

### Possible optional additions

- a dedicated partial for reusable uploader markup
- a dedicated shared JS module if `script.js` becomes too large

## Architectural Notes

### Preferred architecture

Keep one image uploader engine with multiple view contexts:

- wizard add context
- compact edit context
- list quick-upload context

This is preferable to maintaining three separate implementations.

The immediate architectural priority is not list first. It is to eliminate the current duplication between add and edit so list can be added on top of a stable shared base.

### Avoid

- copying the entire wizard uploader logic into `mod_news.js`
- embedding full uploader HTML in every list card
- introducing another one-off uploader variant only for list

## Risks

### 1. Hard-coded DOM assumptions in current shared JS

The modern uploader logic was originally written around specific IDs, and add/edit currently duplicate much of that behavior. Refactoring it without breaking either context requires care.

Mitigation:

- extract incrementally
- preserve current ID-based compatibility layer during transition
- verify add and edit after each refactor

### 2. Edit-specific behavior regressions

The edit page does more than preview a new file. It also handles:

- existing image rendering
- existing image deletion through a backend endpoint
- hidden deletion flags
- pending-image versus existing-image state transitions

Mitigation:

- model these as explicit callbacks or mode-specific hooks in the shared uploader
- validate edit-only behaviors separately from add/list

### 3. Mobile layout regressions

The list page is already density-sensitive. A poorly designed overlay or overly tall sheet can create a new layout problem.

Mitigation:

- prefer modal/sheet over inline card expansion
- test on mobile viewport first
- keep preview shell compact in list context

### 4. Browser-side remote fetch restrictions

URL import may fail in some cases due to CORS or remote host protections.

Mitigation:

- handle remote fetch failure cleanly
- keep local file upload as the primary reliable path
- show a clear inline error when remote fetch fails

### 5. Inconsistent cache state

Shared JS/CSS changes may not appear if asset versioning is not updated.

Mitigation:

- bump asset version strings when shared uploader behavior changes

## Validation Plan

### Functional validation

Verify the following after implementation:

#### Add page

- `http://localhost:8080/en/mod_news/add`
- drag/drop still works
- URL import still works
- paste still works
- submission still includes the uploaded image

#### Edit page

- `http://localhost:8080/en/mod_news/edit/{id}`
- compact image uploader still fits the template
- local file preview works
- URL import works
- remove/reset behavior still works
- existing image is shown correctly on load
- deleting an existing image still works
- selecting a new image and then removing it restores the original image when expected

#### List page

- `http://localhost:8080/en/mod_news/list`
- clicking a thumbnail opens uploader UI
- selecting a local file uploads successfully
- drag/drop works
- paste image works
- URL import works when remote fetch is allowed
- upload success updates the card image
- upload failure shows proper feedback

### Regression validation

Also check:

- card selection still works
- toolbar actions still work
- delete action still works
- gallery/files navigation still works
- list scrolling and refresh behavior still work

## Suggested Delivery Order

### Milestone 1

Refactor add and edit image logic into one reusable uploader engine without changing list behavior yet.

### Milestone 2

Verify add and edit behavior on top of the new shared engine.

### Milestone 3

Introduce list uploader modal/sheet and wire it to the existing upload endpoint.

### Milestone 4

Replace old `processImageUpload()` hidden-input UX with the new uploader flow.

### Milestone 5

Run smoke tests on add, edit, and list pages.

### Milestone 6

Bump relevant asset version strings and verify cache-safe rollout.

### Milestone 7

If stable, reuse the same pattern for other modules that still use old list-page image upload technology.

## Success Criteria

This work should be considered complete when:

- `mod_news/list` uses the same modern image upload interaction model as `mod_news/add`
- `mod_news/add` and `mod_news/edit` are backed by one shared uploader engine instead of duplicated implementations
- list no longer depends on a separate primitive hidden-input upload path
- uploader logic is shared instead of duplicated
- add, edit, and list remain stable after the refactor
