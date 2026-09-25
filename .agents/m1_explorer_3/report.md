# Milestone 1 Investigation Report: Admin Layout Separation, Modal Engine Integration & Boot Strategy

**Explorer**: Explorer 3 (Milestone 1 — Layout Separation & Boot Strategy)  
**Date**: 2026-08-23  
**Target Workspace**: `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname`  
**Reference Workspace**: `/home/malpersaz/Desktop/projects/htdocs/AF/Business`

---

## Executive Summary

This investigation analyzed three critical components for Milestone 1 (Core Foundation, Layout Separation & Boot Strategy):
1. **Clean Layout Separation (`templates/admin/layout.twig`)**: Migration of inline `<style>` CSS custom properties into `templates/admin/assets/css/style.css` under `:root`, elimination of legacy inline style blocks, and removal of deprecated mobile bottom navbar references.
2. **Admin Layout Scripts & Modal Engine Integration**: Incorporation of the centralized SPA HTML5 Modal Engine (`af_modal_engine.js`, `modal_user_menu.js`, and on-demand modal sub-modules) into `layout.twig`, safe JSON encoding for `adminLang` variables, and elimination of inline DOMContentLoaded snackbar scripts.
3. **Framework Boot Strategy & Verification Criteria**: Identification of the exact sequence required to make `init.php` and `index.php` boot cleanly without errors, including `lib/Registry.class.php` creation, `config/*.php` migration from `Registry::ekle` to `Registry::add`, removal of legacy `Kayit`/`Register` classes, and establishing prerequisites for Milestone 2 (Deprecated Modules Cleanup).

---

## 1. Clean Layout Separation (`templates/admin/layout.twig` & `style.css`)

### 1.1 Observations & Baseline State

In `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname/templates/admin/layout.twig` (lines 15–27):
```twig
    <!-- CSS Assets (Absolute Path) -->
<link rel="stylesheet" href="/templates/admin/assets/css/style.css?v=4.11">
    <style>
        :root {
            --af-radius-xs: 4px;
            --af-radius-sm: 8px;
            --af-radius-md: 12px;
            --af-radius-lg: 20px;
            --af-radius-full: 999px;
            --md-sys-color-error: #B3261E;
            --md-sys-color-on-error: #FFFFFF;
            --md-sys-color-error-container: #F9DEDC;
            --md-sys-color-on-error-container: #410E0B;
        }
    </style>
    {% block styles %}{% endblock %}
```

In `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname/templates/admin/assets/css/style.css` (lines 362–382):
The `:root` block contains primary and secondary Material Design 3 variables, elevation, and navbar height, but is missing the `--af-radius-*` and `--md-sys-color-error*` custom properties:
```css
:root {
  --md-sys-color-primary: #6750A4;
  --md-sys-color-on-primary: #FFFFFF;
  --md-sys-color-primary-container: #EADDFF;
  --md-sys-color-on-primary-container: #21005D;
  --md-sys-color-secondary: #625B71;
  --md-sys-color-secondary-container: #E8DEF8;
  --md-sys-color-on-secondary-container: #1D192B;
  --md-sys-color-surface: #FFFBFE;
  --md-sys-color-surface-container: #F3EDF7;
  --md-sys-color-surface-container-high: #E8DEF8;
  --md-sys-color-on-surface: #1C1B1F;
  --md-sys-color-on-surface-variant: #49454F;
  --md-sys-color-outline: #79747E;
  --md-sys-color-blue: #0B57D0;
  --md-sys-color-on-blue: #FFFFFF;
  --md-sys-color-blue-container: #DCE8FF;
  --md-sys-color-on-blue-container: #001D3A;
  --md-sys-elevation-1: 0px 1px 2px rgba(0, 0, 0, 0.3), 0px 1px 3px 1px rgba(0, 0, 0, 0.15);
  --nav-bar-height: 80px;
}
```

In reference repository `/home/malpersaz/Desktop/projects/htdocs/AF/Business/templates/admin/assets/css/style.css` (lines 362–391):
All radius and error color variables are unified directly inside `:root` in `style.css`:
```css
:root {
  --md-sys-color-primary: #6750A4;
  --md-sys-color-on-primary: #FFFFFF;
  --md-sys-color-primary-container: #EADDFF;
  --md-sys-color-on-primary-container: #21005D;
  --md-sys-color-secondary: #625B71;
  --md-sys-color-secondary-container: #E8DEF8;
  --md-sys-color-on-secondary-container: #1D192B;
  --md-sys-color-surface: #FFFBFE;
  --md-sys-color-surface-container: #F3EDF7;
  --md-sys-color-surface-container-high: #E8DEF8;
  --md-sys-color-on-surface: #1C1B1F;
  --md-sys-color-on-surface-variant: #49454F;
  --md-sys-color-outline: #79747E;
  --md-sys-color-blue: #0B57D0;
  --md-sys-color-on-blue: #FFFFFF;
  --md-sys-color-blue-container: #DCE8FF;
  --md-sys-color-on-blue-container: #001D3A;
  --af-radius-xs: 4px;
  --af-radius-sm: 8px;
  --af-radius-md: 12px;
  --af-radius-lg: 20px;
  --af-radius-full: 999px;
  --md-sys-color-error: #B3261E;
  --md-sys-color-on-error: #FFFFFF;
  --md-sys-color-error-container: #F9DEDC;
  --md-sys-color-on-error-container: #410E0B;
  --md-sys-elevation-1: 0px 1px 2px rgba(0, 0, 0, 0.3), 0px 1px 3px 1px rgba(0, 0, 0, 0.15);
  --nav-bar-height: 80px;
}
```

### 1.2 Proposed Migration for Layout Separation

1. **`templates/admin/assets/css/style.css`**:
   - Insert lines 380–388 from `AF/Business` into `:root` in `AF/Rojname/templates/admin/assets/css/style.css`.
2. **`templates/admin/layout.twig`**:
   - Delete lines 15–27 (the entire inline `<style>` block).
   - Add references to modal engine and module CSS files in `<head>`:
     ```twig
     <link rel="stylesheet" href="/templates/admin/assets/css/style.css?v=4.34">
     <link rel="stylesheet" href="/templates/admin/assets/css/modals/core_modals.css?v=1.8">
     <link rel="stylesheet" href="/templates/admin/assets/css/mod_faq.css?v=1.2">
     ```
   - Remove legacy navbar include (lines 63–65 in Rojname `layout.twig`):
     ```twig
     {% if (show_navbar is not defined or show_navbar) and active_module_lang_key is not null %}
         {% include 'admin/partials/navbar.twig' %}
     {% endif %}
     ```
   - Update active module language fallback (lines 31–35 in Rojname `layout.twig`) from deliberate division-by-zero assertion to safe conditional attribute checking:
     ```twig
     {% set active_module_lang_key = module_lang_key is defined ? module_lang_key : (wizard_module is defined ? wizard_module : null) %}
     {% set active_module_lang = active_module_lang_key is not null and lang[active_module_lang_key] is defined ? attribute(lang, active_module_lang_key) : null %}
     ```

---

## 2. Admin Layout Scripts & Modal Engine Integration

### 2.1 Observations & Baseline State

In `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname/templates/admin/layout.twig` (lines 262–286):
```twig
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.5.1/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="/templates/admin/assets/js/script.js?v=1.71"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle Flash Messages from Session
            {% if session.flash is defined and session.flash %}
                showSnackbar('{{ session.flash.message|e("js") }}', '{{ session.flash.type|e("js") }}');
            {% endif %}

            // Handle Direct Template Messages (Validation errors, etc.)
            {% if success is defined and success %}
                showSnackbar('{{ message|e("js") }}', 'success');
            {% elseif error is defined and error %}
                showSnackbar('{{ message|e("js") }}', 'error');
            {% elseif fatal_error is defined and fatal_error %}
                showSnackbar('{{ message|e("js") }}', 'error');
            {% elseif errors is defined and errors|length > 0 %}
                {% for err in errors %}
                    showSnackbar('{{ err|e("js") }}', 'error');
                {% endfor %}
            {% endif %}
        });
    </script>
    {% block scripts %}{% endblock %}
```

In `/home/malpersaz/Desktop/projects/htdocs/AF/Business/templates/admin/layout.twig` (lines 262–267):
```twig
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.5.1/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="/templates/admin/assets/js/modals/af_modal_engine.js?v=1.13"></script>
    <script src="/templates/admin/assets/js/modals/modal_user_menu.js?v=1.02"></script>
    <script src="/templates/admin/assets/js/script.js?v=1.90"></script>
    {% block scripts %}{% endblock %}
```

### 2.2 Modal Engine Architecture & Assets Inventory

The modern admin panel uses an asynchronous, lightweight SPA modal navigation stack:
- **`af_modal_engine.js`**: Core stack manager (`AFModalEngine`) supporting `push`, `pushModalElement`, `pop`, `back`, `closeAll`, and browser history `popstate` navigation.
- **`modal_user_menu.js`**: Global delegated event listeners for `[data-action]` clicks (user menus, status, site settings, newsletter, stats, theme logo, filters, wizard categories, and pickers).
- **Dynamic On-Demand Modules** (loaded dynamically via `AFModalEngine.ensureModuleForModal()`):
  - `modal_newsletter.js`: Subscriber management & CSV export modal.
  - `modal_redirects.js`: SEO URL redirect manager modal.
  - `modal_site_status.js`: Instant AJAX site maintenance mode modal.
  - `modal_theme_logo.js`: Live theme logo and icon switcher modal.
  - `modal_user_profile.js`: Dynamic profile dialog.

### 2.3 Asset Files to Port from `AF/Business` to `AF/Rojname` in M1

| Asset Category | File Path in `templates/admin/assets/` | Description |
|---|---|---|
| **Modal CSS** | `css/modals/core_modals.css` | Core modal stack styling & animations |
| **Module CSS** | `css/mod_faq.css` | FAQ accordion & modal list styles |
| **Module CSS** | `css/mod_seo.css` | SEO manager styles |
| **Module CSS** | `css/legal_info.css` | Legal info & imprint styles |
| **Module CSS** | `css/mod_acl.css` | ACL permission grid styles |
| **Module CSS** | `css/mod_announcements.css` | Announcement styles |
| **Module CSS** | `css/mod_user_acl.css` | User ACL panel styles |
| **Module CSS** | `css/unauthorized.css` | 403 / unauthorized access styles |
| **Modal JS Engine** | `js/modals/af_modal_engine.js` | Core modal stack manager |
| **Modal JS Engine** | `js/modals/modal_user_menu.js` | Global delegated event listeners |
| **Modal Sub-modules** | `js/modals/modal_newsletter.js` | Newsletter modal handler |
| **Modal Sub-modules** | `js/modals/modal_redirects.js` | Redirects modal handler |
| **Modal Sub-modules** | `js/modals/modal_site_status.js` | Site status modal handler |
| **Modal Sub-modules** | `js/modals/modal_theme_logo.js` | Theme & logo modal handler |
| **Modal Sub-modules** | `js/modals/modal_user_profile.js` | User profile modal handler |
| **Module JS** | `js/mod_faq.js` | FAQ drag & drop reorder + AJAX |
| **Module JS** | `js/mod_seo.js` | SEO analysis & GSC sync |
| **Module JS** | `js/legal_info.js` | Legal info dynamic AJAX form |
| **Module JS** | `js/mod_acl.js` | ACL matrix handlers |
| **Module JS** | `js/mod_user_acl.js` | User ACL handlers |

### 2.4 Layout `adminLang` Script Sanitization

In `templates/admin/layout.twig`:
- Replace string concatenations (e.g. `'{{ wizard_lang.wizard.step.language|e("js") }}'`) with direct JSON encoding `{{ wizard_lang.wizard.step.language|default('')|json_encode|raw }}`.
- Add `mod_settings: {{ lang.mod_settings is defined and lang.mod_settings is not null ? lang.mod_settings|json_encode|raw : 'null' }}`.
- Remove inline `<script>` containing `DOMContentLoaded` snackbar listener to comply with the Strict Code Separation mandate (Rule 2).

---

## 3. Boot Verification Criteria & Pre-conditions for Milestone 2

### 3.1 Root Cause of Current Boot Failure

When running `php -d display_errors=1 -r "require_once 'init.php';"`:
```
Fatal error: Uncaught Error: Failed opening required '/home/malpersaz/Desktop/projects/htdocs/AF/Rojname/lib/Registry.class.php' in /home/malpersaz/Desktop/projects/htdocs/AF/Rojname/init.php:65
```
**Cause**:
- `init.php` line 65 requires `lib/Registry.class.php`.
- In `AF/Rojname`, the file was not created yet; only legacy files `lib/Kayit.class.php` and `lib/Register.class.php` existed.
- Configuration files (`config/autoload.config.php`, `config/db.config.php`, `config/site.config.php`, etc.) currently call `Registry::ekle(...)`.
- `lib/Load.class.php`, `lib/InstallationState.php`, and `lib/Database/Adapter/*.php` call `Registry::getir(...)` and `Registry::varmi(...)`.

### 3.2 Canonical `Registry.class.php` Specification

Port canonical `lib/Registry.class.php` from `AF/Business` with the following contract:
```php
<?php
/**
 * Amed Framework
 * User: AKM
 * File: Registry.class.php
 * Description: Core Registry Container Class (Registry Pattern)
 */
class Registry {
    static private $data = [];

    // Fetch a registered key
    static public function get(string $key, mixed $default = null): mixed {
        return (isset(self::$data[$key]) ? self::$data[$key] : $default);
    }

    // Add/Register a new key
    static public function add(string $key, mixed $value): void {
        self::$data[$key] = $value;
    }

    // Check if key exists
    static public function has(string $key): bool {
        return isset(self::$data[$key]);
    }

    // Delete/Remove a registered key
    static public function remove(string $key): void {
        unset(self::$data[$key]);
    }

    // Update/Reset a registered key
    static public function set(string $key, mixed $value): void {
        if (self::has($key)) {
            self::remove($key);
        }
        self::add($key, $value);
    }

    // Dump all registered data for debugging
    static public function all(): array {
        return self::$data;
    }
}
```

### 3.3 Core Registry Callers Refactoring in M1

1. **`config/*.php`**:
   - `config/autoload.config.php`: `Registry::add('autoload_config', $autoload);`
   - `config/db.config.php` & `config/db.config.example.php`: `Registry::add('database', ...); Registry::add('db_config', ...);`
   - `config/site.config.php` & `config/site.config.example.php`: `Registry::add('site_config', ...); Registry::add('app_template', ...);`
   - `config/i18n.config.php`: `Registry::add('i18n', ...); Registry::add('i18n_bootstrap', ...);`
   - `config/session.config.php`: `Registry::add('session_name', ...);`
   - `config/url.config.php`: `Registry::add('url_config', ...);`
   - `config/email.config.php`: `Registry::add('email_config', ...);`
2. **`lib/Load.class.php`**:
   - Replace all `Registry::varmi` with `Registry::has`.
   - Replace all `Registry::getir` with `Registry::get`.
   - Replace all `Registry::ekle` with `Registry::add`.
3. **`lib/InstallationState.php`**:
   - Replace `Registry::getir('database')` and `Registry::getir('db_config')` with `Registry::get('database')` and `Registry::get('db_config')`.
4. **Delete legacy classes**:
   - Delete `lib/Kayit.class.php`.
   - Delete `lib/Register.class.php`.

### 3.4 Verification Commands & Pass Criteria

| Verification Step | Command | Expected Result |
|---|---|---|
| **Boot Check** | `php -d display_errors=1 -r "require_once 'init.php'; echo 'BOOT_OK';"` | Output: `BOOT_OK`, exit code 0 |
| **Runtime Test** | `php tests/module_management_runtime_test.php` | Output: `All module management runtime tests passed.`, exit code 0 |
| **PHP Syntax Check** | `find config lib mod templates installer -name "*.php" -exec php -l {} +` | Zero syntax errors |
| **Code Separation Guard** | `grep -rn "<style" templates/admin/layout.twig` | Zero occurrences |
| **Legacy Identifier Guard** | `grep -rn "class Kayit" lib/` | Zero occurrences |

### 3.5 Milestone 2 Pre-conditions Checklist

Before proceeding to Milestone 2 (Deprecated Modules Cleanup: removing `mod_client`, `mod_service`, `mod_product`, `mod_reference`):
- [ ] `lib/Registry.class.php` is active and boots without error in `init.php`.
- [ ] `config/*.php` files call `Registry::add()`.
- [ ] `lib/Kayit.class.php` and `lib/Register.class.php` are removed.
- [ ] `templates/admin/layout.twig` has 0 inline `<style>` blocks and references `core_modals.css` and `af_modal_engine.js`.
- [ ] Modal assets directory (`templates/admin/assets/{css,js}/modals/`) is synced.
- [ ] `php tests/module_management_runtime_test.php` passes cleanly.

---

## 4. Conclusion & Handover Recommendation

The layout separation, modal engine integration, and boot strategy are fully investigated and specified with exact file paths, line numbers, and diff targets. The implementation agents can execute Milestone 1 without ambiguity.
