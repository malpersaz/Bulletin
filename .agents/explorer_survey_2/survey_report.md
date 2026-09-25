# Deprecated Modules Cleanup Inventory (Requirement R3)

**Date**: 2026-08-23  
**Target Repository**: `AF/Rojname` (`/home/malpersaz/Desktop/projects/htdocs/AF/Rojname`)  
**Scope**: Complete inventory of files, directories, SQL schemas, route definitions, catalog entries, controllers, helpers, admin templates, assets, and language definitions for:
1. `mod_client`
2. `mod_service` & `mod_service_category`
3. `mod_product` & `mod_product_category`
4. `mod_reference` & `mod_reference_category` (and orphaned `mod_project` / `mod_project_category` references)

---

## Executive Summary

The investigation confirmed that the core controller directories for `mod_client`, `mod_service`, `mod_product`, and `mod_reference` were already partially removed from `mod/` and `templates/admin/`. However, significant remnants remain across:
- **Installer directories and SQL files**: 6 module directories with 15 SQL/PHP files.
- **Frontend helpers**: 4 helper classes in `mod/index/helper/`.
- **Admin assets**: 1 legacy JavaScript file in `templates/admin/assets/js/`.
- **System registration and catalogs**: `installer/app/InstallerModuleCatalog.php`, `lib/ModuleRegistry.class.php`, `mod/management/index.php`.
- **Routing & Controllers**: `config/url.config.php`, `mod/index/index.php`, `mod/index/helper/search.helper.php`, `mod/index/helper/mod_hero_slider.helper.php`, `mod/mod_slider/index.php`, `mod/mod_seo/index.php`, `mod/mod_user/index.php`.
- **Admin UI templates**: `templates/admin/partials/sidebar.twig`, `templates/admin/mod_user/panel.twig`.
- **Language files**: 10 language files in `installer/lang/`, 10 language files in `mod/management/lang/`, and 10 language files in `mod/mod_user/lang/`.

---

## 1. Files & Directories Scheduled for Deletion

The following 11 files and 6 directories must be completely deleted:

| Category | File / Directory Path | Description |
|---|---|---|
| **Installer** | `installer/mod_client/` | Directory containing installer `index.php` and `mod_client.sql` table schema |
| **Installer** | `installer/mod_service/` | Directory containing installer `index.php`, `mod_service.sql` table schema, and `services.sql` view schema |
| **Installer** | `installer/mod_service_category/` | Directory containing installer `index.php` and `mod_service_category.sql` table schema |
| **Installer** | `installer/mod_product/` | Directory containing installer `index.php`, `mod_product.sql` table schema, and `products.sql` view schema |
| **Installer** | `installer/mod_product_category/` | Directory containing installer `index.php` and `mod_product_category.sql` table schema |
| **Installer** | `installer/mod_reference/` | Directory containing installer `index.php`, `mod_reference.sql` table schema, and `references.sql` view schema |
| **Helpers** | `mod/index/helper/mod_client.helper.php` | Frontend helper for client pages, list, and latest clients |
| **Helpers** | `mod/index/helper/mod_service.helper.php` | Frontend helper for service pages, categories, and latest services |
| **Helpers** | `mod/index/helper/mod_product.helper.php` | Frontend helper for product pages, categories, and latest products |
| **Helpers** | `mod/index/helper/mod_reference.helper.php` | Frontend helper for reference pages, list, and latest references |
| **Admin Asset** | `templates/admin/assets/js/mod_product.js` | Legacy admin JavaScript asset for product module |

---

## 2. Codebase References & Required Modifications

### 2.1 Installer Configuration & Catalog

#### `installer/app/InstallerModuleCatalog.php`
- **Lines 18–25**: Remove entries in `$modules` array:
  - Line 18: `'mod_client'`
  - Line 19: `'mod_product_category'`
  - Line 20: `'mod_project_category'` (orphaned reference)
  - Line 21: `'mod_service_category'`
  - Line 22: `'mod_service'`
  - Line 23: `'mod_product'`
  - Line 24: `'mod_project'` (orphaned reference)
  - Line 25: `'mod_reference'`
- **Lines 59–62, 77–80**: Remove entries in `$installOrder` array:
  - Lines 59–62: `'mod_client'`, `'mod_product_category'`, `'mod_project_category'`, `'mod_service_category'`
  - Lines 77–80: `'mod_service'`, `'mod_product'`, `'mod_project'`, `'mod_reference'`

#### `installer/lang/*.php` (All 10 language files: `ar`, `ckb`, `de`, `en`, `fa`, `fr`, `ku`, `sv`, `tr`, `zza`)
- Remove module title translations under `'modules'` array:
  - `'mod_client'`
  - `'mod_product_category'`
  - `'mod_service_category'`
  - `'mod_product'`
  - `'mod_service'`
  - `'mod_reference'`

---

### 2.2 System Registries & Management Core

#### `lib/ModuleRegistry.class.php`
- **Lines 77–88**: Remove from `$installDependencies`:
  - Lines 77–79: `'mod_product'` entry
  - Lines 81–84: `'mod_service'` entry
  - Lines 85–88: `'mod_reference'` entry
- **Lines 99–103**: Remove from `$uninstallDependents`:
  - Line 99: `'mod_client' => ['mod_reference']`
  - Line 100: `'mod_product_category' => ['mod_product']`
  - Line 101: `'mod_service_category' => ['mod_service']`
  - Line 102: `'mod_product' => ['mod_reference']`
  - Line 103: `'mod_service' => ['mod_reference']`

#### `mod/management/index.php`
- **Lines 157–158**: Remove deprecated module entries in `$sharedModules`:
  - `'mod_product' => $this->runtime()->isModuleReady('mod_product'),`
  - `'mod_service' => $this->runtime()->isModuleReady('mod_service'),`

#### `mod/management/lang/*.php` (All 10 language files: `ar`, `ckb`, `de`, `en`, `fa`, `fr`, `ku`, `sv`, `tr`, `zza`)
- Remove unused navigation language keys:
  - `'products_workspace'`
  - `'products'`
  - `'services'`
  - `'product_categories'`
  - `'service_categories'`
  - `'clients'`
  - `'references'`

---

### 2.3 Frontend Routing & Index Controller

#### `config/url.config.php`
- **Lines 44–48**: Remove deprecated sitemap routes:
  - `$url['productSitemap.xml'] = 'index/productSitemap';`
  - `$url['projectSitemap.xml'] = 'index/projectSitemap';`
  - `$url['serviceSitemap.xml'] = 'index/serviceSitemap';`
  - `$url['referenceSitemap.xml'] = 'index/referenceSitemap';`
  - `$url['clientSitemap.xml'] = 'index/clientSitemap';`
- **Lines 101–124**: Remove deprecated URL routing rules:
  - Product family (lines 101–106): `product/(:any)`, `products`, `products/`, `products/(:any)`, `products/(:any)/(:num)`
  - Service family (lines 108–113): `service/(:any)`, `services`, `services/`, `services/(:any)`, `services/(:any)/(:num)`
  - Client family (lines 115–118): `client/(:any)`, `clients`, `clients/`
  - Reference family (lines 120–124): `reference/(:any)`, `references`, `references/`, `references/(:num)`

#### `mod/index/index.php`
- **Lines 164–218**: Remove public route controller methods:
  - `product($seo = null)`
  - `products($category = null, $page = 1)`
  - `service($seo = null)`
  - `services($category = null, $page = 1)`
  - `client($seo = null)`
  - `clients()`
  - `reference($seo = null)`
  - `references($page = 1)`
- **Lines 441–471**: Remove helper wrapper methods:
  - `_latest_products($t, $limit, $select)`
  - `_latest_services($t, $limit, $select)`
  - `_latest_clients($t, $limit, $select)`
  - `_latest_references($t, $limit, $select)`
- **Lines 585–596**: Remove sitemap links inside `sitemapIndex()`:
  - `productSitemap.xml`
  - `serviceSitemap.xml`
  - `referenceSitemap.xml`
  - `clientSitemap.xml`
- **Lines 682–780**: Remove sitemap action methods:
  - `productSitemap($page = 1)`
  - `serviceSitemap($page = 1)`
  - `referenceSitemap($page = 1)`
  - `clientSitemap($page = 1)`
- **Lines 946–972**: Remove category sitemap blocks for `mod_product_category` and `mod_service_category` in `categorySitemap()`.

---

### 2.4 Helpers & Search Integration

#### `mod/index/helper/search.helper.php`
- **Lines 87–110**: Remove search query and cache block for `mod_product`
- **Lines 112–135**: Remove search query and cache block for `mod_client`
- **Lines 137–160**: Remove search query and cache block for `mod_service`
- **Lines 162–185**: Remove search query and cache block for `mod_reference`
- **Lines 219–222**: Remove theme template assignments for `product_lang`, `client_lang`, `service_lang`, `reference_lang`.

#### `mod/index/helper/mod_hero_slider.helper.php`
- **Line 38**: Remove `$this->loadProductItems()` from `hero_slider()` merge array.
- **Lines 262–267**: Remove `elseif ($source_mod === 'mod_product')` branch from `normalizeItem()`.
- **Lines 395–458**: Remove `loadProductItems()` method.

---

### 2.5 Admin Surface & User Panel

#### `templates/admin/partials/sidebar.twig`
- **Line 5**: Remove `solutions_active` definition:
  - `{% set solutions_active = mod == 'mod_product' or mod == 'mod_product_category' or mod == 'mod_service' or mod == 'mod_service_category' or mod == 'mod_client' or mod == 'mod_reference' %}`
- **Lines 159–203**: Remove the entire "products" navigation group (`<div class="nav-group{% if solutions_active %} expanded{% endif %}" data-nav-group="products">...</div>`).

#### `templates/admin/mod_user/panel.twig`
- **Lines 474–486**: Remove module card class & icon mappings for `mod_product`, `mod_service`, `mod_reference`, `mod_client`.
- **Lines 514–522**: Remove FAB quick-action item for `mod_product` (`shared_admin_modules.mod_product`).
- **Lines 525–533**: Remove FAB quick-action item for `mod_service` (`shared_admin_modules.mod_service`).

#### `mod/mod_user/index.php`
- **Lines 928–954**: In `_load_panel_actions()`, remove definitions for `mod_product`, `mod_service`, `mod_reference`, `mod_client`.

#### `mod/mod_user/lang/*.php` (All 10 language files: `ar`, `ckb`, `de`, `en`, `fa`, `fr`, `ku`, `sv`, `tr`, `zza`)
- Remove FAB keys `'add_product'` and `'add_service'`.

#### `mod/mod_slider/index.php`
- **Lines 462–469**: Remove `mod_product` table check and query union in `sliderContent()`.
- **Line 521**: Remove `'mod_product'` label from `$module_labels`.
- **Lines 583–584**: Remove `mod_product` handler in `_publish()`.
- **Lines 618–619**: Remove `mod_product` handler in `_unpublish()`.

#### `mod/mod_seo/index.php`
- **Lines 300–334**: Query loops for `mod_product`, `mod_service`, `mod_reference` in sitemap builder.
- *(Note: Will be completely superseded when `mod_seo` is ported from `AF/Business` in Requirement R2)*.

---

## 3. Database Impact & Schema Notes

When cleanups are applied:
1. Installer will no longer create `mod_client`, `mod_service`, `mod_service_category`, `mod_product`, `mod_product_category`, `mod_reference` tables or `products`, `services`, `references` views.
2. `ModuleRegistry` will no longer declare install dependencies or uninstall dependents for these tables and modules.
3. No foreign key constraints in active modules (`mod_content`, `mod_news`, `mod_publication`, `mod_video`, etc.) depend on any of these deleted modules.

---

## 4. Verification Strategy for Cleanup Phase

To verify complete cleanup:
1. **Grep Search Validation**:
   ```bash
   rg -i "mod_client|mod_service|mod_product|mod_reference" --glob '!tests/**' --glob '!docs/**' --glob '!.agents/**'
   ```
   Must return 0 occurrences across `mod/`, `installer/`, `lib/`, `config/`, and `templates/admin/`.
2. **File Existence Validation**:
   Confirm that all listed installer directories and helper files have been deleted.
3. **Syntax & Smoke Checks**:
   - `php -l` on all modified PHP files (`config/url.config.php`, `lib/ModuleRegistry.class.php`, `mod/management/index.php`, `mod/index/index.php`, `mod/mod_slider/index.php`, etc.).
   - Run `php tasks/module_management_smoke.php` to ensure no runtime errors occur.
