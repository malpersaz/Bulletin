# `mod_news` Modernization & UI/UX Synchronization Plan

**Target Repository:** `AF/Bulletin` (Amed Framework — Bulletin CMS)  
**Target Version:** `v1.0.2` (In Progress)  
**Compliance Baseline:** `AGENTS.md` Rules 1–10 (`BaseModuleController`, `ORM`, Phone-Only Admin Surface, Strict Code Separation, 100% English-Only Code & Full Multilingual i18n Parity), `docs/CODE_QUALITY_AGENT_STRATEGY.md`, and `docs/PILOT_AGENT_REVIEW_FLOW.md`

---

## 1. Executive Summary

An audit of `mod_news` in `AF/Bulletin` confirms that the module's list view, toolbar, card renderer, and localization files require modernization to achieve full phone-first Material 3 UX and strict code-separation compliance:

1. **Bottom Contextual Toolbar (`templates/admin/mod_news/list.twig`, `mod_news.css`, `mod_news.js`):**
   - `AF/Bulletin` currently renders an always-visible 7-slot toolbar (`#contentToolbar.standard-toolbar.news-toolbar`) docked at the bottom of the viewport, where buttons are dimmed (`opacity: 0.3; pointer-events: none;`) when no card is selected. It also lacks the **Status Toggle** (`#toolbarStatus`) and **Deck / Featured Toggle** (`#toolbarDeck`) actions.
   - In the modernized target state, `#contentToolbar.standard-toolbar.news-toolbar` is a **floating liquid-glass contextual pill** (`border-radius: 22px`, `backdrop-filter: blur(18px) saturate(180%)`) that remains completely hidden (`aria-hidden="true"`, `opacity: 0`, `visibility: hidden`, `pointer-events: none`) until a news card is selected (`body.has-selection`, `.is-visible`).
   - It supports up to **9 permission-gated action buttons** (`44x44px` icon-only pills with `flex-wrap: wrap`): **Status Toggle**, **Deck (Featured) Toggle**, **Hero Slider Toggle**, **Video**, **Gallery**, **Files**, **FAQ**, **Edit**, and **Delete**.

2. **Secondary Menu (3-Dots Card Action Button) & Card Selection (`mod_news.js`):**
   - Currently in `mod_news.js`, clicking the 3-dots button (`.m3-icon-btn`) on a card calls `openActionSheet(id, titleText, statusRaw)`, which opens `#context-menu-sheet` (duplicating toolbar actions), while `renderCards()` injects multiple inline `style="..."` attributes violating **Rule 2 (Strict Code Separation Mandate)**.
   - In the modernized state, tapping either the card body or the 3-dots button (`.news-action-btn.m3-icon-btn`) toggles card selection (`selectContent(id, card)`), revealing the floating `#contentToolbar` with a spring animation (`newsToolbarPop`) and morphing `#mainFab` into a cancel/deselect button (`.is-cancel`).

3. **Main FAB (`#mainFab`) & Global Deselection Lifecycle (`script.js`, `mod_news.css`):**
   - When idle, `#mainFab` sits at `bottom: calc(20px + env(safe-area-inset-bottom, 0px))` displaying `bi-plus-lg` (gated by `session.permissions.mod_news.add`).
   - When a card is selected (`body.has-selection`), `#mainFab` smoothly translates upward (`transform: translate3d(0, -118px, 0)`) above the floating toolbar and morphs into a cancel button (`bi-x-lg`, `.is-cancel`).
   - Tapping `#mainFab.is-cancel`, tapping outside `.content-card` / `.news-toolbar` / `.news-image-modal`, or pressing `Escape` invokes `window.clearActiveSelection()`.
   - On desktop viewports (`@media (min-width: 1024px)`), both `#contentToolbar` and `#mainFab` remain scoped inside the `.mobile-wrapper` (`412x915px`) phone simulator via `position: absolute`.

4. **Backend AJAX Toggles, Strict Code Separation & Full Multilingual i18n Parity (`mod/mod_news/index.php`, `mod_news.model.php`, `lang/*.php`, `add.twig`, `edit.twig`, `partials/image_uploader.twig`, `mod/index/helper/mod_news.helper.php`, `templates/amida/index/mod_news/*.twig`):**
   - `mod/mod_news/index.php` currently implements `toggle_hero_slider()`, `approve()`, and `unapprove()`, but lacks JSON-based `toggle_deck()` and `toggle_status()` endpoints, uses the misspelled identifier `$colomns`, and does not return the `deck` column (`item[11]`) in `datatable_json()`.
   - `mod/mod_news/model/datatable.model.php` and `mod/mod_news/model/flexigrid.model.php` are dead legacy model files containing non-English comments and non-English identifiers (`$colom`) that trigger 19 `ERROR` violations in `python3 tools/i18n_guard.py --path mod/mod_news`.
   - `mod/mod_news/index.php` still contains a hardcoded `'Invalid AI action.'` string in `ajax_ai_assist()` and lacks a `jsonResponse()` header in `ajax_check_title()`.
   - `templates/admin/mod_news/list.twig` (lines 18–20) contains 3 `|default(...)` localization fallbacks (`FORBIDDEN_FALLBACK` in `i18n_guard.py` and `CODE_QUALITY_AGENT_STRATEGY.md`).
   - `templates/admin/mod_news/add.twig` contains an inline `<script>` block and inline `style="..."` attributes violating **Rule 2**.
   - `templates/admin/partials/image_uploader.twig` contains inline `onclick`/`onchange` attributes and a fallback reference to forbidden `lang.mod_content`, violating **Rule 2** and **Domain Boundaries**.
   - `templates/admin/assets/js/mod_news.js` contains hardcoded non-English strings inside `NewsEditManager.initAiAssistant()` and inline style assignments across `createLoadMoreButton()`, `loadData()`, `renderCards()`, `updateToolbar()`, and `initAlerts()`, violating **Rule 2** and **Rule 7 (100% English-Only Code & i18n System)**.
   - Public frontend helper and templates (`mod/index/helper/mod_news.helper.php`, `mod/index/index.php`, `mod/index/helper/index.php`, `templates/amida/index/mod_news/*.twig`) still contain legacy non-English identifiers (`$this->tema`, `$kat`, `_permission_kontrol`, `arsiv()`, `arsiv_yil`, `min_yil`, `max_yil`, `yil`, `ay`, `bulunamadi`), non-English template filename `arsiv.twig` with hardcoded non-English label text, an unused mockup file `category_sections.twig` containing non-English comments and inline `<style>`, and inline `style="..."` attributes in `newses.twig`.

---

## 2. Architectural Comparison Matrix

| Feature / Component | Current `AF/Bulletin` (`mod_news`) | Target Modernized State (`v1.0.2`) |
| :--- | :--- | :--- |
| **Bottom Toolbar (`#contentToolbar`)** | Always-visible 7-column bar; dimmed (`opacity: 0.3`) when idle; missing Status and Deck toggle buttons. | Hidden by default (`aria-hidden="true"`). Floating liquid-glass card (`max-width: 400px`, `border-radius: 22px`) that pops up (`.is-visible`) only on selection. 9 permission-gated `data-action` buttons. |
| **Toolbar Actions (9 Buttons)** | 1. Slider, 2. Video, 3. Gallery, 4. Files, 5. FAQ, 6. Edit, 7. Delete. | 1. **Status** (`toggle-status`), 2. **Deck** (`toggle-deck`), 3. **Slider** (`toggle-slider`), 4. **Video** (`open-video`), 5. **Gallery** (`open-gallery`), 6. **Files** (`open-files`), 7. **FAQ** (`open-faq`), 8. **Edit** (`edit-content`), 9. **Delete** (`delete-content`). |
| **Main FAB (`#mainFab`)** | Fixed at `bottom: 80px` above always-visible bar; ungated in `list.twig`. | Docked at `bottom: 20px` when idle; gated by `session.permissions.mod_news.add`. Elevates (`-118px`) and morphs into Cancel/Deselect (`.is-cancel`, `bi-x-lg`) when a card is selected. |
| **Card 3-Dots Button (`.news-action-btn`)** | Calls `openActionSheet(id, titleText, statusRaw)` to open `#context-menu-sheet`. | Toggles card selection (`selectContent(id, card)`) and reveals `#contentToolbar`. |
| **Card DOM & Styling (`renderCards()`)** | Uses inline `style="..."` on wrappers, thumbnails, badges, titles, and meta rows. | 100% class-driven (`.card-content-layout`, `.news-thumb-wrapper`, `.card-thumbnail`, `.news-card-title`, `.meta-bottom-row`, `.hero-slider-badge`, `.deck-inline-badge`). Zero inline styles. |
| **Search Hit Highlighting** | None. | Highlights active search query matches inside `.content-card h3` and `.meta-category` using `<mark class="news-search-hit">`. |
| **`add.twig` Wizard Trigger** | Uses inline `style="..."` and an inline `<script>` block inside `add.twig`. | Zero inline CSS/JS; uses `<div class="wizard-page-loader" id="newsAddShell" data-list-url="{{ site.saddress }}mod_news/list" data-active-lang="{{ site.lang|lower }}">` initialized by `mod_news.js`. |
| **Image Uploader Partial (`image_uploader.twig`)** | Uses inline `onclick` / `onchange` attributes and references `lang.mod_content`. | Uses clean `data-action` / external JS bindings in `script.js` and `mod_news.js`; removes forbidden `lang.mod_content` reference. |
| **Controller (`mod/mod_news/index.php`)** | Only `toggle_hero_slider()`; `datatable_json()` returns indices `0..10` (missing `deck`). | Unified `toggleFlag(string $column, ?string $forceValue = null, string $permission = 'edit')` using `ORM` (`Load::model('mod_news')`) and `$this->jsonResponse()`, powering `toggle_hero_slider()`, `toggle_deck()`, and `toggle_status()`. `datatable_json()` returns `item[11]` (`deck`). |

---

## 3. Detailed File-by-File Implementation Plan

### Phase 1: Backend Controller & Model Modernization

#### 1.1. [mod/mod_news/index.php](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_news/index.php)
- **Schema Verification in `__construct()`**:
  - Ensure both `slider` and `deck` columns exist on `mod_news` (preserving existing adapter-aware check).
- **`datatable_json()`**:
  - Normalize `$colomns` to `$columns` (per `CODE_QUALITY_AGENT_STRATEGY.md` naming rules) and update the `mod_news` query projection and `$columns` array to include `deck`:
    ```php
    $i->select('id, name, image, lang, status, created_at, category_id, user_id, slider, deck');
    $columns = ['id', 'name', 'image', 'lang', 'status', 'created_at', 'category_id', 'user_id', 'slider', 'deck'];
    ```
  - Append index `11` (`is_deck`) to each row in `$data`:
    ```php
    isset($content['deck']) && $content['deck'] === 'y' ? 1 : 0
    ```
- **Unified Flag Toggle Helper (`toggleFlag`) Using Native `AF/Bulletin` ORM**:
  - Refactor `toggle_hero_slider()` into a private helper `toggleFlag(string $column, ?string $forceValue = null, string $permission = 'edit'): void` that:
    - Validates permissions via `Role::hasPermission('mod_news', $permission)` (and for `status`, accepts `'approval'` or `'edit'`), returning `$this->jsonResponse(['status' => 'error', 'message' => ...])` on failure.
    - Reads `$id = intval(Req::Post_Safe('id'))` and loads the record via `Load::model('mod_news')` (`$model->where_id($id)->gRo()`, `$model->sync()`).
    - Checks moderation permission (`$this->user->checkPermission('mod_news', 'moderate_edit', $model->user_id, ..., 1)`) when editing another user's news item.
    - Computes `$newState = $forceValue !== null ? $forceValue : ((($model->$column ?? 'n') === 'y') ? 'n' : 'y')`.
    - Updates `mod_news` via `$updateModel = Load::model('mod_news')` (`$updateModel->id = $id`, `$updateModel->$column = $newState`, `$updateModel->updated_at = date('Y-m-d H:i:s')`, `$updateModel->update('id')`).
    - Returns localized JSON via `$this->jsonResponse([...])` containing `status => 'success'`, the updated state flags (`slider` / `in_hero_slider` / `is_slider`, `deck` / `is_deck`, `published` / `is_published`), and localized `message`.
  - Expose three public controller endpoints:
    1. `public function toggle_hero_slider(): void` -> `$this->toggleFlag('slider', null, 'edit');`
    2. `public function toggle_deck(): void` -> `$this->toggleFlag('deck', null, 'edit');`
    3. `public function toggle_status(): void` -> `$this->toggleFlag('status', null, 'approval');`
- **Standardize JSON Responses & Eliminate Hardcoded Strings (Rule 7)**:
  - In `ajax_check_title()`: Use `$this->jsonResponse([...])` so proper `Content-Type: application/json; charset=utf-8` headers are sent.
  - In `ajax_ai_assist()`: Replace hardcoded `'Invalid AI action.'` and English fallback strings with `$this->lang['mod_news']['ai']['invalid_action']` and localized dictionary keys.

#### 1.2. [mod/mod_news/model/mod_news.model.php](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_news/model/mod_news.model.php) & Legacy Model Cleanup
- Format indentation cleanly on `class mod_news_model extends ORM`.
- Add `public $view_count;` property.
- Add `public function getDefaults(): array` returning Amed Framework i18n-compliant default field values (`'lang' => Registry::is_set('default_lang') ? Registry::get('default_lang') : 'en'`, `category_id => 0`, `status => 'n'`, `slider => 'n'`, `deck => 'n'`).
- Remove unused legacy model files `mod/mod_news/model/datatable.model.php` and `mod/mod_news/model/flexigrid.model.php` (which are unreferenced dead code containing non-English comments and `$colom` variables that fail `tools/i18n_guard.py`, per `docs/CODE_QUALITY_AGENT_STRATEGY.md` and `docs/PILOT_AGENT_REVIEW_FLOW.md`).

---

### Phase 2: Full Multilingual i18n Synchronization (`mod/mod_news/lang/*.php`)

In strict compliance with Amed Framework's multilingual i18n architecture (`AGENTS.md` Rule 7 & `docs/CODE_QUALITY_AGENT_STRATEGY.md`), synchronize **all 8** language dictionaries in `mod/mod_news/lang/` ([en.php](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_news/lang/en.php), [tr.php](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_news/lang/tr.php), [ku.php](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_news/lang/ku.php), [de.php](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_news/lang/de.php), [ckb.php](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_news/lang/ckb.php), [ru.php](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_news/lang/ru.php), [sv.php](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_news/lang/sv.php), [zza.php](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_news/lang/zza.php)):
- **Root-level keys to add across all locale files**:
  - `remove_image_title`, `confirm_remove_image`
  - `deck_add`, `deck_remove`, `deck_added`, `deck_removed`, `deck_error`, `deck_badge`
  - `status_update_error`
- **`validator` keys to add across all locale files**:
  - `invalid_lang`, `category_not_found`, `category_root_forbidden`, `category_lang_mismatch`
- **`wizard.messages` keys to add across all locale files**:
  - `no_category_for_lang`
- **`ai` keys to add across all locale files**:
  - `headline_error`, `warning_title`, `error_title`, `invalid_action`
- **Cleanup & Full Translation Completeness**:
  - Remove duplicate `loading` and `no_data` keys inside the `grid` array across all affected language files (`en.php`, `tr.php`, `ku.php`, `de.php`, `ckb.php`, `sv.php`, `zza.php`).
  - Remove misplaced duplicate keys (`delete_error`, `video_success_sim`, `video_embed_error`, `video_title_error`) from the `filter` array in `ku.php`.
  - Translate remaining untranslated English strings in `de.php` into proper German so all module language packs maintain 100% native i18n completeness.

---

### Phase 3: Admin Templates (`templates/admin/mod_news/*.twig`, `partials/image_uploader.twig` & `layout.twig`)

#### 3.1. [templates/admin/mod_news/list.twig](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/admin/mod_news/list.twig)
- **Eliminate Forbidden `|default(...)` Fallbacks (`CODE_QUALITY_AGENT_STRATEGY.md` & `i18n_guard.py`)**:
  - Replace lines 18–20 (`lang.mod_news.datatable.zeroRecords|default(...)`, `emptyTable|default(...)`, `loadingRecords|default(...)`) with direct key bindings (`lang.mod_news.datatable.zeroRecords`, `lang.mod_news.datatable.emptyTable`, `lang.mod_news.datatable.loadingRecords`).
- **Data Attributes on `#content-list-container`**:
  - Add `data-perm-approval="{{ (session.permissions.mod_news.approval is defined or session.permissions.mod_news.edit is defined) ? '1' : '0' }}"` and localized deck/status attributes if needed alongside `window.adminLang.mod_news`.
- **Permission-Gated Main FAB (`#mainFab`)**:
  - Wrap `#mainFab` in `{% if session.permissions.mod_news.add is defined and session.permissions.mod_news.add %}`.
- **Modernized Floating Contextual Toolbar (`#contentToolbar`)**:
  - Set `aria-hidden="true"` on `#contentToolbar.standard-toolbar.news-toolbar`.
  - Render all 9 `data-action` buttons with proper permission guards:
    1. `#toolbarStatus` (`data-action="toggle-status"`, `bi-eye-fill`, gated by `approval` or `edit`)
    2. `#toolbarDeck` (`data-action="toggle-deck"`, `bi-bookmark-star`, gated by `edit`)
    3. `#toolbarSlider` (`data-action="toggle-slider"`, `bi-stars`, gated by `edit`)
    4. `#toolbarVideo` (`data-action="open-video"`, `bi-camera-video`, gated by `video` or `edit`)
    5. `#toolbarGallery` (`data-action="open-gallery"`, `bi-images`, gated by `gallery` or `edit`)
    6. `#toolbarFiles` (`data-action="open-files"`, `bi-paperclip`, gated by `file` or `edit`)
    7. `#toolbarFaq` (`data-action="open-faq"`, `bi-patch-question-fill`, gated by `faq` or `edit`)
    8. `#toolbarEdit` (`data-action="edit-content"`, `bi-pencil-fill`, gated by `edit`)
    9. `#toolbarDelete` (`data-action="delete-content"`, `bi-trash3-fill`, gated by `delete`)
- **Clean `image_uploader.twig` Include**:
  - Remove inline JS strings (`trigger_picker_js`, `remove_action_js`) in favor of `data-action` attributes.

#### 3.2. [templates/admin/mod_news/add.twig](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/admin/mod_news/add.twig)
- Remove all inline `style="..."` attributes and the entire inline `<script>...</script>` block.
- Render:
  ```twig
  <div class="wizard-page-loader" id="newsAddShell" data-list-url="{{ site.saddress }}mod_news/list" data-active-lang="{{ site.lang|lower }}">
      <div class="spinner-track">
          <div class="spinner-fill"></div>
      </div>
      <p>{{ lang.mod_news.wizard_loading }}</p>
  </div>
  ```
- Load `/templates/admin/assets/js/mod_news.js` in `{% block scripts %}` so `#newsAddShell` auto-opens `#contentWizard` cleanly without inline scripts.

#### 3.3. [templates/admin/mod_news/edit.twig](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/admin/mod_news/edit.twig)
- Add localized AI data attributes to `#editForm`:
  - `data-ai-warning-title="{{ lang.mod_news.ai.warning_title|escape('html_attr') }}"`
  - `data-ai-error-title="{{ lang.mod_news.ai.error_title|escape('html_attr') }}"`
  - `data-ai-content-required="{{ lang.mod_news.ai.content_required|escape('html_attr') }}"`
  - `data-ai-deck-error="{{ lang.mod_news.ai.deck_error|escape('html_attr') }}"`
  - `data-ai-headline-error="{{ lang.mod_news.ai.headline_error|escape('html_attr') }}"`
- Update `#editImageShell` attributes to use `lang.mod_news.remove_image_title` and `lang.mod_news.confirm_remove_image`, and remove inline JS callback parameters (`trigger_picker_js`, `remove_action_js`, `file_input_onchange`).

#### 3.4. [templates/admin/partials/image_uploader.twig](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/admin/partials/image_uploader.twig)
- Remove inline `onclick="{{ trigger_picker_js }}"`, `onclick="event.stopPropagation(); {{ remove_action_js }}"`, and `onchange="{{ file_input_onchange }}"` attributes; replace with `data-action="trigger-image-picker"` and `data-action="remove-uploader-image"` bound in `script.js` (`createImageUploader`).
- Remove the forbidden `lang.mod_content.wizard.image.upload_link` fallback on line 16 (use `lang.mod_news.wizard.image.upload_link` fallback instead).

#### 3.5. [templates/admin/layout.twig](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/admin/layout.twig)
- Expose `mod_news: {{ lang.mod_news is defined and lang.mod_news is not null ? lang.mod_news|json_encode|raw : 'null' }},` inside `window.adminLang` (around line 251) so `getNewsLang()` in `mod_news.js` has complete access to all localized `mod_news` strings.

---

### Phase 4: Frontend JavaScript & CSS Modernization

#### 4.1. [templates/admin/assets/js/mod_news.js](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/admin/assets/js/mod_news.js)
- **State & Selection Management**:
  - Track `selectedContentId`, `selectedInHeroSlider`, `selectedIsDeck`, and `selectedIsPublished`.
  - Update `updateToolbar(active)` to toggle `.is-visible` and `aria-hidden` on `#contentToolbar`, toggle `body.has-selection`, and update button states without setting inline `.style.pointerEvents` or `.style.opacity`.
  - Expose both `window.clearSelection` and `window.clearModuleSelection` so global deselection in `script.js` resets card selection, `#contentToolbar`, and `#mainFab`.
- **Toolbar Action Dispatcher**:
  - Bind `[data-action]` handlers for `toggle-status`, `toggle-deck`, `toggle-slider`, `open-video`, `open-gallery`, `open-files`, `open-faq`, `edit-content`, and `delete-content`.
  - Implement `toggleSelectedStatus()`, `toggleSelectedDeck()`, and `toggleHeroSlider()` with busy-state protection (`dataset.busy`) and live DOM badge/status updates (including `#statDraftNews` counter adjustments when status changes).
- **Zero-Inline-Style `renderCards()` & Search Highlighting**:
  - Eliminate all `style="..."` attributes and `.style.*` assignments in `renderCards()`, `createLoadMoreButton()`, `loadData()`, `applyHeroSliderState()`, and `initAlerts()`.
  - Render thumbnail `<img>` (`class="news-card-thumb"`) or fallback icon inside `.card-thumbnail` without inline `background-image`.
  - Render both `.hero-slider-badge` (`bi-stars`) and `.deck-inline-badge` (`bi-bookmark-star-fill`) using `.is-hidden` class toggling.
  - Bind `.news-action-btn.m3-icon-btn` (3-dots button) click to `this.selectContent(id, card)` instead of `openActionSheet()`.
  - Implement `highlightSearchHits(card)` using `<mark class="news-search-hit">` on `.news-card-title` and `.meta-category`.
- **`#newsAddShell` Auto-Opener & Localized AI Alerts**:
  - In `DOMContentLoaded`, detect `#newsAddShell` and auto-open `#contentWizard` (using `classList.add('active')` without inline `.style.visibility`).
  - In `NewsEditManager.initAiAssistant()`, replace all hardcoded non-English strings with localized `this.form.dataset.*` / `getNewsLang()` values.

#### 4.2. [templates/admin/assets/css/mod_news.css](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/admin/assets/css/mod_news.css) & [templates/admin/assets/css/style.css](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/admin/assets/css/style.css)
- Replace the legacy toolbar rules in `mod_news.css` with the **Floating Liquid-Glass Contextual Toolbar** styling:
  - `.standard-toolbar.news-toolbar`: `position: fixed` (and `position: absolute` inside `.mobile-wrapper` at `@media (min-width: 1024px)`), `bottom: calc(18px + env(safe-area-inset-bottom, 0px))`, `left: 50%`, `width: calc(100% - 24px)`, `max-width: 400px`, `display: flex`, `flex-wrap: wrap`, `justify-content: center`, `gap: 6px`, `padding: 10px 12px`, `height: auto`, `border-radius: 22px`, `opacity: 0`, `visibility: hidden`, `pointer-events: none`, `transform: translate3d(-50%, 40px, 0) scale(0.92)`.
  - `.standard-toolbar.news-toolbar.is-visible`: `opacity: 1`, `visibility: visible`, `pointer-events: auto`, `animation: newsToolbarPop 0.38s cubic-bezier(0.22, 1, 0.36, 1) forwards`.
  - `.standard-toolbar.news-toolbar .toolbar-item`: `width: 44px`, `height: 44px`, `min-width: 44px`, `border-radius: 14px`.
  - Semantic button state classes:
    - `#toolbarStatus.is-published` (emerald green `#1e8e3e`) & `#toolbarStatus.is-draft` (slate `#5f6368`)
    - `#toolbarDeck.is-selected` (amber gold `#e37400`)
    - `#toolbarSlider.is-selected` (emerald green `#2e7d32`)
    - `#toolbarDelete` (danger red `#b3261e`)
  - Main FAB positioning and elevation:
    - `#mainFab`: `bottom: calc(20px + env(safe-area-inset-bottom, 0px))`
    - `body.has-selection #mainFab`: `transform: translate3d(0, -118px, 0)`
    - `#mainFab.is-cancel`: danger red surface + `90deg` icon rotation.
  - Card layout & utility classes: `.news-thumb-wrapper`, `.news-card-thumb`, `.card-header-row`, `.news-card-title`, `.meta-bottom-row`, `.hero-slider-badge`, `.deck-inline-badge`, `.status-pill-published`, `.status-pill-draft`, `mark.news-search-hit`, `.load-more-btn`, `.wizard-page-loader`, and `.swal-error-list`.

#### 4.3. [templates/admin/assets/js/script.js](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/admin/assets/js/script.js)
- In `createImageUploader()` (`bind()`), attach click listeners to `[data-action="trigger-image-picker"]` and `[data-action="remove-uploader-image"]` within the shell so `partials/image_uploader.twig` operates with zero inline `onclick`/`onchange` handlers.
- In `window.updateGlobalFabState(active)`, toggle `document.body.classList.toggle('has-selection', Boolean(active))`.
- In `initGlobalDeselectionListeners()`, update the outside-click and `Escape` key selectors to include `.news-toolbar`, `.news-image-modal`, `.bottom-sheet`, and `.overlay`.

#### 4.4. Public Frontend `mod_news` Helper & Templates (100% English Code & Zero Inline Styles — Rules 2 & 7)
- **[mod/index/helper/mod_news.helper.php](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/mod_news.helper.php), [mod/index/index.php](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/index.php), & [mod/index/helper/index.php](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/index.php)**:
  - Replace all legacy non-English identifiers (`$this->tema` -> `$this->theme`, `$kat` -> `$categoryItem`, `_permission_kontrol` -> `_permission_check`, `arsiv()` -> `archive()`, `max_yil` -> `max_year`, `min_yil` -> `min_year`, `arsiv_yil` -> `archive_years`, `$yil` -> `$selectedYear`, `$ay` -> `$selectedMonth`, `bulunamadi` -> `not_found`).
  - Render `index/mod_news/archive` instead of `index/mod_news/arsiv`.
- **[templates/amida/index/mod_news/](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amida/index/mod_news/)**:
  - Replace `templates/amida/index/mod_news/arsiv.twig` with `templates/amida/index/mod_news/archive.twig` using pure English variables (`archive_years`, `min_year`, `max_year`, `selected_year`, `selected_month`, `not_found`) and 100% localized `lang.index.mod_news.*` labels (matching `templates/amida/index/mod_publication/archive.twig`).
  - Remove unused static mockup `templates/amida/index/mod_news/category_sections.twig` (which contains non-English comments, hardcoded non-English text, and an inline `<style>` block).
  - Remove the 2 inline `style="height: 200px; object-fit: ..."` attributes in `templates/amida/index/mod_news/newses.twig` (lines 101 & 107) and move them into CSS classes.

---

### Phase 5: Version Parity (`v1.0.2`)

- Update [config/site.config.php](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/site.config.php) and [config/site.config.example.php](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/site.config.example.php) version metadata if required by release policy.
- Document the `v1.0.2` changes in [docs/VERSION.md](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/docs/VERSION.md) strictly in English (**Rule 5**).

---

## 4. Verification & Quality Gate Checklist

Before concluding the implementation, the following deterministic checks (`docs/CODE_QUALITY_AGENT_STRATEGY.md` & `docs/PILOT_AGENT_REVIEW_FLOW.md`) must be executed and passed with **0 Errors**:

1. **PHP Syntax Validation**:
   - `find mod/mod_news mod/index -name "*.php" -exec php -l {} +`
2. **Strict Code Separation Guard (Rule 2)**:
   - `python3 tools/code_separation_guard.py --path templates/admin/mod_news`
   - `python3 tools/code_separation_guard.py --path templates/amida/index/mod_news`
   - `python3 tools/code_separation_guard.py --path templates/admin/partials/image_uploader.twig`
   - `python3 tools/code_separation_guard.py --path templates/admin/assets/js/mod_news.js`
   - `python3 tools/code_separation_guard.py --path mod/mod_news`
3. **Automated i18n, Naming, Comment & Fallback Guard (Rule 7 & `CODE_QUALITY_AGENT_STRATEGY.md`)**:
   - `python3 tools/i18n_guard.py --path mod/mod_news`
   - `python3 tools/i18n_guard.py --path mod/index/helper/mod_news.helper.php`
   - `python3 tools/i18n_guard.py --path templates/admin/mod_news`
   - `python3 tools/i18n_guard.py --path templates/amida/index/mod_news`
   - `python3 tools/i18n_guard.py --path templates/admin/partials/image_uploader.twig`
   - `python3 tools/i18n_guard.py --path templates/admin/assets/js/mod_news.js`
   - Pilot Agent Review Flow verification (`i18n-guard`, `comment-guard`, `naming-guard`, `template-guard`, `cleanup-guard`, `policy-gate`).
4. **Module Boundary Guard**:
   - `python3 tools/module_boundary_guard.py --all`
