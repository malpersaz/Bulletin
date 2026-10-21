# Survey Report: Requirement R4 & Quality / Test Infrastructure
**Target Workspace:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**Reference Source:** `/home/malpersaz/Desktop/projects/htdocs/AF/Business`  
**Explorer Agent:** `explorer_survey_3`  
**Date:** 2026-08-23  

---

## 1. Executive Summary & Scope Overview

This survey report provides a comprehensive investigation of **Requirement R4 (Architectural & Repository Policy Compliance)** and the **Quality & Test Infrastructure** for the synchronization and modernization of `AF/Bulletin` against the reference standard in `AF/Business`.

### Core Scope Investigated:
1. **Strict Code Separation:** Elimination of inline `<style>`, `style="..."` attributes, `<script>` blocks, and inline event handlers (`onclick`, `onchange`) in favor of centralized CSS and JS asset files.
2. **Language, Comments, Forbidden Terms & Registry Pattern:** Audit of non-English/Turkish comments, elimination of legacy `Kayit` classes/aliases, resolution of the missing `Registry.class.php` fatal error, and full adoption of the canonical `Registry` pattern.
3. **Phone-Only Mobile First Admin:** Viewport configuration, CSS structure, component touch targets, card lists, and compliance with the `admin-mobile-check` contract.
4. **Testing & Verification Infrastructure:** PHP syntax linting (`php -l`), automated quality guards (`code_separation_guard.py`, `i18n_guard.py`, `audit_modernization.py`), and runtime unit tests (`tests/module_management_runtime_test.php`).

---

## 2. Investigation Area 1: Strict Code Separation Analysis

### 2.1 Current State in `AF/Bulletin`
`AF/Bulletin` contains several legacy templates with inline CSS styles, inline `<style>` tags, and inline event handlers that directly violate **AGENTS.md Rule 2 (Strict Code Separation Mandate)**:

| File Path | Violations Identified | Details |
|---|---|---|
| `templates/admin/layout.twig` | `<style>` tag (lines 15–27) | Inline CSS root variables (`--af-radius-*`, `--md-sys-color-error*`) declared directly in `<head>`. |
| `templates/admin/layout.twig` | Inline `<script>` (lines 94–261, 264–284) | Direct JS initialization of `adminLang` and flash message listeners in layout footer. |
| `templates/admin/mod_seo/list.twig` | `<style>` (lines 12–70), `<script>` (lines 180+) | Inline `.seo-dashboard-grid`, `.seo-stat-box`, and click handlers. |
| `templates/admin/mod_settings/edit.twig` | `style="..."` (lines 486, 487, 488, 490, 492) | Inline flexbox and border styles on webmanifest / asset rows. |
| `templates/admin/mod_video_embed/add.twig` | `style="..."` (lines 84, 86, 104, 106, 107, 111, 115, 116, 143, 144, 156, 191, 192, 201, 210, 213, 219, 231, 241, 352), `onclick`/`onchange` (lines 219, 253, 258, 262, 389, 624) | Pervasive inline styles and inline event handlers. |
| `templates/admin/mod_video_embed/list.twig` | `onclick="..."` (lines 82, 89) | `onclick="deleteSelected()"`, `onclick="location.href='...'"` inline handlers. |

### 2.2 Reference Standard in `AF/Business`
In `AF/Business`, templates maintain clean separation:
- **Stylesheets:** Centralized under `templates/admin/assets/css/`:
  - `style.css` (v4.34, 4963 lines) contains all global layout, variables, and simulator styles.
  - Module styles are separated into dedicated files: `mod_seo.css`, `mod_faq.css`, `mod_settings.css`, `legal_info.css`, `mod_acl.css`, `mod_announcements.css`, `mod_page.css`, `mod_user_acl.css`, `mod_video_embed.css`.
  - Child templates enqueue styles via:
    ```twig
    {% block styles %}
        <link rel="stylesheet" href="/templates/admin/assets/css/mod_xxx.css?v=1.0">
    {% endblock %}
    ```
- **JavaScript Assets:** Centralized under `templates/admin/assets/js/`:
  - `script.js` (v1.71+) handles global drawer toggling, search filtering, pull-to-refresh, snackbar notifications, and FAB interactions.
  - Module logic is separated into dedicated files: `mod_seo.js`, `mod_faq.js`, `mod_settings.js`, `legal_info.js`, `mod_content.js`, `mod_gallery.js`, `mod_news.js`, `mod_page.js`, `mod_publication.js`, `mod_video_embed.js`.
  - Child templates enqueue scripts and pass strictly typed JSON config objects:
    ```twig
    {% block scripts %}
        <script>
            window.MOD_CONFIG = {
                title: {{ lang.mod_xxx.title|json_encode|raw }}
            };
        </script>
        <script src="/templates/admin/assets/js/mod_xxx.js?v=1.0"></script>
    {% endblock %}
    ```

### 2.3 Asset Porting Requirements for `AF/Bulletin`
1. Copy missing CSS files from `AF/Business/templates/admin/assets/css/` to `AF/Bulletin`:
   - `mod_faq.css`, `mod_seo.css`, `legal_info.css`, `mod_acl.css`, `mod_announcements.css`, `mod_user_acl.css`, `unauthorized.css`.
2. Copy missing/updated JS files from `AF/Business/templates/admin/assets/js/` to `AF/Bulletin`:
   - `mod_faq.js`, `mod_seo.js`, `legal_info.js`, `mod_acl.js`, `mod_user_acl.js`.
3. Create `mod_newsletter.css` and `mod_newsletter.js` when porting `mod_newsletter` to ensure 0 inline styles/scripts.
4. Remove legacy `templates/admin/assets/js/mod_product.js` (part of R3 cleanup).
5. Move inline `<style>` root variables in `templates/admin/layout.twig` into `templates/admin/assets/css/style.css`.

---

## 3. Investigation Area 2: Language, Comments, Forbidden Terms & Registry Pattern

### 3.1 Critical Finding: Missing `Registry.class.php` & Fatal Error
- **Observation:** `init.php` line 65 executes:
  ```php
  require_once(lib_dir . 'Registry.class.php');
  ```
  However, `lib/Registry.class.php` **does not exist** in `AF/Bulletin`. Executing `php -r "require_once 'init.php';"` immediately fails with:
  ```
  Fatal error: Uncaught Error: Failed opening required '/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Registry.class.php' in /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/init.php:65
  ```
- **Root Cause:** `AF/Bulletin/lib/` still contains the deprecated legacy class `lib/Kayit.class.php` (with Turkish methods `getir`, `ekle`, `varmi`, `sil`, `yenile`, `hepsi`) and a legacy `lib/Register.class.php`.
- **Solution:** 
  1. Port the canonical `lib/Registry.class.php` from `AF/Business` into `AF/Bulletin/lib/Registry.class.php`.
  2. The canonical `Registry` class provides standard English methods:
     - `Registry::get($key)`
     - `Registry::add($key, $value)`
     - `Registry::has($key)`
     - `Registry::remove($key)`
     - `Registry::set($key, $value)`
     - `Registry::all()`
  3. Delete `lib/Kayit.class.php` and `lib/Register.class.php`.

### 3.2 Configuration Files Audit (`Registry::ekle` vs `Registry::add`)
In `AF/Bulletin`, several configuration files still call the deprecated Turkish method `Registry::ekle(...)`:
- `config/autoload.config.php` (line 42: `Registry::ekle('autoload_config', $autoload)`)
- `config/db.config.php` (lines 12–13: `Registry::ekle('database', ...); Registry::ekle('db_config', ...);`)
- `config/db.config.example.php` (lines 111–117)
- `config/db.config.sqlite.php` (lines 88–94)
- `config/email.config.php` (line 21: `Registry::ekle('email_config', $email);`)
- `config/email.config.example.php` (line 21)
- `config/i18n.config.php` (lines 13–15: `Registry::ekle('i18n', TRUE); Registry::ekle('i18n_bootstrap', ...);`)
- `config/session.config.php` (lines 14–22: `Registry::ekle('session_name', ...); ...`)
- `config/site.config.example.php` (lines 56–67)

**Remediation:** Replace all occurrences of `Registry::ekle(...)` with `Registry::add(...)` across all config files, matching `AF/Business`.

### 3.3 Non-English Comments & Identifiers
- Turkish comments found in legacy modules:
  - `mod/tools/index.php` (e.g. `// Resim +Text Hepsini Kaydet`, `// Dosya Yokmuş`, `// Video Yokmuş`)
  - `mod/mod_category/` and installer SQL schemas
  - `mod/mod_attachment_mod/`
- Turkish translation keys:
  - Key `'kayit_yonetimi'` in `mod/mod_content/lang/{en,de,ku,tr,zza}.php` must be refactored to `'record_management'`.
- Policy enforcement: All code comments and variable/function names in newly ported or modified modules (`mod_content`, `mod_content_category`, `mod_seo`, `mod_faq`, `mod_newsletter`, `mod_settings`) must strictly be in English.

---

## 4. Investigation Area 3: Phone-Only Mobile First Admin Interface

### 4.1 Layout Contract & Viewport Configuration
- `templates/admin/layout.twig` and `templates/admin/layout_blank.twig` enforce the mobile viewport:
  ```html
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  ```
- **Mobile Container:**
  - On viewports `< 1024px`, `.mobile-wrapper` spans `100vw` by `100%` height (zero border/shadow).
  - On viewports `>= 1024px`, `.mobile-wrapper` renders a centered phone device shell (`width: 412px; height: 90vh; border-radius: 32px; border: 8px solid #1C1B1F;`).
  - `.app-scroll-frame` provides dedicated vertical scrolling with bottom padding (`200px`) to prevent floating bars (FAB, bottom action bars, bottom navbar) from obscuring form controls.

### 4.2 Fixed Mobile Chrome Architecture
1. **Header (`templates/admin/partials/header.twig`):**
   - Compact 56px top app bar with hamburger/drawer toggle, module title, and quick search icon.
2. **Floating Action Button (`templates/admin/partials/fab.twig`):**
   - 56x56px circular primary action button with elevated shadow.
3. **Bottom Navigation Bar (`templates/admin/partials/navbar.twig`):**
   - Fixed bottom tab bar for quick module switching with touch targets >= 44x44px.
4. **Bottom Action Bar (`fixed_bottom` block):**
   - Full-width dual button action bar (`.m3-final-bar` / `.m3-btn-half`) for forms: "Close" (neutral) and "Save" (primary).
5. **Filter Sheet (`templates/admin/partials/filter_sheet.twig`):**
   - Off-canvas bottom sheet with draggable pill handles for filter chips and date ranges.
6. **Wizards (`templates/admin/partials/wizard_*.twig`):**
   - Multi-step phone wizards for adding news, content, and video embeds with step progression indicators.

### 4.3 Component Level Mobile Usability Rules (`admin-mobile-check`)
- **Single Vertical Column Forms:** All form layouts must collapse into a single column (`flex-direction: column` / `grid-template-columns: 1fr`).
- **Card-based Lists:** Data grids should render touch-friendly card lists (`.content-list`, `.subscriber-card`, `.faq-item-card`) or wrap tabular data in horizontally scrolling containers (`.table-responsive`).
- **Touch Safety:** Buttons, inputs, chips, and drag handles must have minimum 44x44 CSS pixel tap dimensions with adequate hit-testing margins.
- **Vertical Economy:** Primary fields and submit actions must be visible without excessive decorative headers or redundant banners.

---

## 5. Investigation Area 4: Testing & Verification Infrastructure

### 5.1 Verification Matrix & Commands

| Tool / Check | Command | Scope / Target | Purpose |
|---|---|---|---|
| **PHP Syntax Linting** | `find config/ lib/ mod/ installer/ templates/ tests/ -name "*.php" -exec php -l {} +` | All custom PHP source files | Verifies zero PHP syntax errors across all touched files. |
| **Code Separation Guard** | `python3 tools/code_separation_guard.py --path templates/admin` | Admin templates & views | Checks for forbidden `<style>`, `style="..."`, and inline event handlers (`onclick`, `onchange`). |
| **i18n & Language Guard** | `python3 tools/i18n_guard.py --path <modified_path>` | Modified modules & templates | Validates English comments, English identifiers, no SQL aliases, and missing translation keys. |
| **Module Modernization Audit** | `python3 tools/audit_modernization.py` | Target modules | Audits OOP controller inheritance (`BaseModuleController`), datatables, image services, ACL, SQLite support. |
| **Module Runtime Test** | `php tests/module_management_runtime_test.php` | Runtime & ModuleRegistry | Validates feature maps, readiness checks, dependency failure detection, and degraded state handling. |

### 5.2 Test Suite Analysis: `tests/module_management_runtime_test.php`
- Current status: **Passing (6/6 test suites pass)**.
- **Required Update for R1:**
  - In `tests/module_management_runtime_test.php` line 150:
    ```php
    assert_same(['mod_content', 'mod_category'], $content['modules'], ...);
    ```
  - When `mod_category` is renamed to `mod_content_category` in R1, `tests/module_management_runtime_test.php` and `lib/ModuleRegistry.class.php` must both be updated to assert `['mod_content', 'mod_content_category']`.

---

## 6. Actionable Implementation Plan for Orchestrator & Implementers

### Phase 1: Core Foundation & Registry Fix (Prerequisite)
1. Port `lib/Registry.class.php` from `AF/Business/lib/Registry.class.php` to `AF/Bulletin/lib/Registry.class.php`.
2. Update all calls to `Registry::ekle(...)` -> `Registry::add(...)` in `config/*.php`.
3. Remove `lib/Kayit.class.php` and `lib/Register.class.php`.
4. Verify `php -r "require_once 'init.php';"` boots cleanly with zero errors.

### Phase 2: Assets & CSS/JS Synchronization
1. Copy shared admin assets from `AF/Business/templates/admin/assets/`:
   - CSS: `mod_faq.css`, `mod_seo.css`, `legal_info.css`, `mod_acl.css`, `mod_announcements.css`, `mod_user_acl.css`, `unauthorized.css`.
   - JS: `mod_faq.js`, `mod_seo.js`, `legal_info.js`, `mod_acl.js`, `mod_user_acl.js`.
2. Delete `templates/admin/assets/js/mod_product.js`.
3. Create `templates/admin/assets/css/mod_newsletter.css` and `templates/admin/assets/js/mod_newsletter.js`.
4. Clean up `templates/admin/layout.twig`: remove inline `<style>` tag (move to `style.css`).

### Phase 3: Module Porting & Code Separation Compliance
1. Port/sync `mod_content` & `mod_content_category` (R1).
2. Port/sync `mod_seo`, `mod_faq` / `mod_faq_mod`, `mod_newsletter`, and `mod_settings` (R2).
3. Extract any inline styles/scripts in ported templates into their respective CSS/JS files.
4. Clean up deprecated modules (`mod_client`, `mod_service`, `mod_product`, `mod_reference`) (R3).

### Phase 4: Quality Gate & Validation
1. Run `php -l` lint check across all touched files.
2. Run `python3 tools/code_separation_guard.py --path templates/admin`.
3. Run `python3 tools/i18n_guard.py --path mod/<module>`.
4. Update and run `php tests/module_management_runtime_test.php`.
5. Run `python3 tools/audit_modernization.py`.

---
*Report prepared by explorer_survey_3. All findings verified against current repository states.*
