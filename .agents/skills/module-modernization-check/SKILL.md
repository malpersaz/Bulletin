---
name: module-modernization-check
description: Internal Synchronization & Modernization Check - Audit modules, plugins, and components within the active workspace for compliance with the project modernization standard (BaseModuleController, ImageService, DataTableService, mod_acl, mod_attachment, mod_gallery, mod_video).
---

# Internal Synchronization & Modernization Check (Module Modernization)

## Purpose

This skill defines the checks and baseline modernization rules for all modules within the Rojname CMS project to track and verify their alignment with the modernization standard.

## Modernization Checklist & Criteria

When a module is created, modified, or audited, it must be evaluated across the following six key aspects based on the standard defined in [module_modernization_integrated.html](file:///home/malpersaz/Desktop/projects/htdocs/AF/Rojname/docs/module_modernization_integrated.html):

### 1. Installer Aspect
* **Check:** Verify that the installation directory `installer/mod_<module_name>/` exists.
* **Requirements:**
  * Must contain an `index.php` installation script.
  * Must contain `.sql` schema files (e.g. `mod_<module_name>.sql`) defines the required tables.
  * Schema files should be audited for database engine compatibility (MySQL vs SQLite-friendly schemas).

### 2. Mod (PHP Code) Aspect
* **OOP Controller Inheritance:** The controller in `mod/mod_<module_name>/index.php` must inherit `BaseModuleController` instead of legacy structures (like `indexMain` or raw logic).
* **Centralized Image Handling:** The controller must not call raw `Load::mod('image')`. Image configurations must be declared in the `$image_fields` array property of the controller, and processed automatically by `ImageService`.
* **Media & Relation Cleanups:**
  * Gallery: Set `protected $has_gallery = true;` to enable automatic related gallery deletion upon record purge.
  * Attachments: Set `protected $has_files = true;` to automatically clean up attachment relations.
* **Access Control (ACL):** Centralize authorization checks within controller CRUD lifecycles using the helper `$this->user->hasPermission` instead of raw queries or hardcoded role checks.
* **Video & Video Embed Integration:** Any module supporting videos must use the standard `video` database column to store serialized references (e.g., `video:ID` for local files via `mod_video`, and `vid:ID` for embedded videos via `mod_video_embed`). The controller must expose `all_video` and `all_video_get` actions to aggregate local/embed search results and integrate with the centralized UI picker/wizard. The integration is modernized (`Green`) only when the controller extends `BaseModuleController` and the UI employs modern cards/pickers rather than plain text input boxes.
* **Slider Integration:** Any module supporting hero slider feature must use the standard `slider` ENUM database column. The controller must expose `toggle_hero_slider` action to allow toggling of the slider status via AJAX. The integration is modernized (`Green`) only when the controller extends `BaseModuleController` and the UI DataTable utilizes modernized toolbar options instead of legacy inline links.
* **Homepage Integration:** Any module supporting homepage visibility must use the standard `show_on_homepage` or `homepage` database column. The controller must support homepage toggling. The integration is modernized (`Green`) only when the controller extends `BaseModuleController` and the UI employs modern cards/pickers rather than plain checkboxes.
* **DataTableService Integration:** Implement database grid views using `DataTableService` by defining column mapping in `getDatatableColumns()`.
* **Database compatibility:** All raw SQL queries must dynamically check the adapter type via `Load::lib('Database')->getAdapterType()`. If the adapter is `PDO_SQLITE`, avoid MySQL-specific functions (like `YEAR()`, `MONTH()`, `NOW()`, `RAND()`) and use SQLite-equivalent functions.

### 3. Templates Aspect
* **Check:** Verify the presence of public-facing view templates under `templates/default/mod_<module_name>/` (or the corresponding active public theme).
* **Requirements:**
  * Public views must use Twig templates and exclude legacy inline DB query logic.

### 4. Admin (Admin Templates) Aspect
* **Check:** Verify administrative view templates under `templates/admin/mod_<module_name>/` (usually including `add.twig`, `edit.twig`, `list.twig`, and optional `script.twig`).
* **Mobile Layout Contract:**
  * Admin templates must conform to the phone-only vertical mobile app layout.
  * Lists and tables must adapt to narrow phone viewports with horizontal scrolling spec-tables and touch-friendly row heights.

### 5. Twig (Twig Templating) Aspect
* **Check:** Audit all template files under `templates/admin/mod_<module_name>/` to ensure they are written entirely in Twig (`.twig` files).
* **Requirements:**
  * Legacy PHP templates (.php files) are completely banned.
  * No inline PHP logic tags (`<?php ... ?>`) are allowed in Twig files.
  * Localization tags must utilize `lang` filters without default text fallbacks.

### 6. UI (User Interface) Aspect
* **Character Counters:** Input forms for name/title (max 255/70) and summary/description (max 250/255) must have live JS-based character counters (e.g. `#titleCharCounter`, `#introCharCounter`).
* **Floating Label Overlaps:** Textarea/summary fields must have proper CSS padding (e.g. `padding-top: 20px` or floating label container offsets) to prevent input text from overlapping the floating label.
* **Selection Cards:** Status/publish options and deck toggles must use modernized card-based selection elements (radio buttons styled as `.selection-card` with checked states changing borders and rendering checking icons like `.check-icon`).
* **Premium Image Uploader:** Image fields must integrate the drag-and-drop `image_uploader.twig` partial and call `initializeEditImageInteractions()` instead of using raw HTML `<input type="file">`.
* **ACL Label Clarity:** The ACL access group field must have clean, concise labels (e.g. "Koma Gihîştinê") without long descriptions.

---

## Modernization Classifications

When preparing tables or reporting on module status, classify modules into one of the following states:
1. **Bi Temamî Nûjen (Fully Modern)**: Admin controller extends `BaseModuleController`, uses centralized image handling and `DataTableService`, AND the public helper/views are fully functional and SQLite-compatible.
2. **Bi Qismî Nûjen (Partially Modern)**: Admin controller is modernized, but public helpers, templates, or database queries contain compatibility bugs or legacy logic.
3. **Kevn (Legacy)**: Admin controller does not extend `BaseModuleController`.

---

## Automated Verification & Report Updating

A project audit script is available at `tools/audit_modernization.py` to automatically analyze the 10 target modules across these six aspects, determine their status (Spî, Sor, Kesk), and update their radio button checked states in `docs/module_modernization_integrated.html`.
If the audit finds any issues outside the table criteria, it appends those details in English at the bottom of the HTML page.

---

## Operating Rule for Agents

1. **Before starting a task:** Read the guidelines inside this skill to identify standard baseline expectations.
2. **Execution:** Heta bikarhêner fermana rasterast a qontrolkirina senkronîzasyona hindurîn (mînak a vê repoyê) an jî senkronîzasyona repoyên cuda (Business, Artist, Association, Property, Rojname) nede, tu bi serê xwe vê pêvajoyê mede destpêkirin û ji bikarhêner jî daxwaza destûra wê meke!


