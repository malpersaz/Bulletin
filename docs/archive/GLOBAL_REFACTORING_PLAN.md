# Global Refactoring & Standardization Master Plan
## Amed Framework: Turkish to English Transition

This document provides a comprehensive guide and record of the global refactoring effort to standardize the Amed Framework. It merges the previous synchronization plans, global status reports, and the `mod_user` case study into a single master reference.

---

## 1. Global Field Name & Terminology Standards

The following core database fields and framework terms are standardized across all modules, models, and views.

### Database Fields
| Old (Turkish) | New (English) | Scope/Context |
| :--- | :--- | :--- |
| `ad` | `name` | Global |
| `soyad` | `last_name` | User/Staff |
| `tarih` | `created_at` | Global (Creation timestamp) |
| `etarih` | `expired_at` | Expiry/End date |
| `yayin` / `durum` | `status` | Global (Visibility/State) |
| `aciklama` | `description` | Global |
| `resim` | `image` | Global |
| `giris` | `summary` | Content/Articles |
| `sira` | `order` | Sorting/Ordering |
| `alt` | `parent_id` | Hierarchical structures |
| `dosya_id` | `file_id` | Attachments |
| `kod` | `code` | Activation/Verification codes |
| `zaman` | `created_at` | Log/Activation timestamps |

### Status Enum Values
- **Old**: `'e'` (evet), `'h'` (hayır)
- **New**: `'y'` (yes), `'n'` (no)
- **Note**: Admin-specific status `'a'` remains where applicable.

---

## 2. Core Logic & Method Standardizations

All core CRUD and bulk action methods in `BaseModuleController` and child modules are renamed to professional English standards.

### CRUD Operations
- `ekle()` / `_ekle()` → `add()` / `_add()`
- `duzenle()` / `_duzenle()` → `edit()` / `_edit()`
- `listele()` → `list()`
- `sil()` / `_sil()` → `delete()` / `_delete()` (Physical files: `_delete_files()`, DB records: `_delete_records()`)
- `bilgi()` → `info()`

### Bulk & Grid Actions
- `yayinla()` → `publish()`
- `yayinkaldir()` → `unpublish()`
- `aktifle()` → `activate()`
- `pasifle()` → `deactivate()`
- `tasi` → `move`
- `_sira_degis()` → `_order_change()`

### Permissions & Authorization
- `ekle` → `add`
- `duzenle` → `edit`
- `sil` → `delete`
- `listele` → `list`
- `ddurum` → `edit_status`
- `yetki` → `permissions` (Global rename: `Roll::hasPermission`, `|permission` filter, `$_SESSION['permissions']`)

---

## 3. Core Library & Framework Synchronization

To ensure consistent behavior, core libraries must support the new English standards.

### `ImageService.class.php`
- Default `$field_name` updated from `'resim'` to `'image'`.
- Internal variables and comments updated to English.

### `Kayit.class.php` & `Validator.class.php`
- Introduced `add()` method as an alias/replacement for `ekle()`.
- Renamed `'fonk'` → `'func'` and `'mesaj'` → `'message'` in validator parameters.

### Exception Handling
- Custom properties renamed (e.g., `Exception::$file` → `$filePath`) to avoid built-in property conflicts.

---

## 4. UI & Frontend Synchronization (Twig & JS)

### Twig Templates
- **Attribute Access**: `d.yayin` → `d.status`, `d.ad` → `d.name`, `d.resim` → `d.image`.
- **Filters**: `|yetki` → `|permission`.
- **Variables**: `hatalar` → `errors`, `hata` → `error`.
- **File Names (CRUD)**: `ekle.twig` → `add.twig`, `listele.twig` → `list.twig`, etc.
- **File Names (User/Email)**:
  - `sposta.twig` → `email_password_reset.twig`
  - `posta.twig` → `email_activation.twig`
  - `pposta.twig` → `email_new_password.twig`
  - `pyonetici.twig` → `email_admin_activation.twig`
- **File Names (Pages)**: 
  - `uyeol.twig` → `register.twig`
  - `ara.twig` → `search.twig` (Requires `search.helper.php` backend update & URL action changes to `index/search/`)

### JavaScript & AJAX
- **Action Handlers**: `processDuzenle()` → `processEdit()`, `yayinla` → `publish`.
- **Form IDs**: `ekleForm` → `addForm`, `duzenleForm` → `editForm`.
- **AJAX Keys**: Updated `data` keys and `FormData` assignments to match English field names.

---

## 5. Case Studies

### Module User (`mod_user`)

The `mod_user` module serves as the completed reference for this refactoring.

#### Key Changes Implemented in `mod_user`:
1.  **Languages**: All 8 language files (`tr`, `ku`, `en`, etc.) synchronized with English keys.
2.  **Controller**: All methods (`add`, `edit`, `list`, `delete`, `change_password`, `password_reset`) refactored.
3.  **Models**: Updated to use `public $errors` and standardized field names (`last_name`, `image`, `status`).
4.  **Validators**: Renamed files (e.g., `edit.validator.php`) and updated methods to `$this->val->add()`.
5.  **Installer**: Updated `$this->rolls`, translated SQL comments to English, and renamed tables (e.g., `mod_user_password_reset`).
6.  **Database**: Columns `kod` → `code`, `zaman` → `created_at`, `durum` → `status`.
7.  **Status**: Full transition to `'y'`/`'n'` enum values.

### Module Announcements (`mod_announcements`)

The `mod_announcements` module terminology has been standardized globally.
1. **Terminology**: Replaced "duyuru" and "duyurular" with "announcement" and "announcements" respectively across the entire system.
2. **Controllers/Helpers**: Renamed methods `duyuru()` to `announcement()`, `duyurular()` to `announcements()`, and `_en_son_duyurular()` to `_latest_announcements()`.
3. **Routing Configuration**: Standardized URLs to use `index/announcement` and `index/announcements`.
4. **Templates**: Renamed `duyuru.twig` to `announcement.twig` and `duyurular.twig` to `announcements.twig`, and updated their references.
5. **Template Variables**: Context variables updated from `duyurular` to `announcements` across all active themes (Soul, Artist, Default).

---

## 6. Modernization Roadmap for Remaining Modules

Follow these steps in order for each module (e.g., `mod_content`, `mod_category`):

1.  **Step 1: Language Files**: Rename Turkish keys to English (`add`, `edit`, `list`, `delete`, `error`).
2.  **Step 2: Controller**: Rename methods, update `Req::is_Post`, and permission checks.
3.  **Step 3: Models**: Synchronize properties and JSON response keys.
4.  **Step 4: Validators**: Rename files and methods (`add()`).
5.  **Step 5: Installer**: Update roles, translate comments, and use `$this->error()`/`$this->warning()`.
6.  **Step 6: Templates**: Rename `.twig` files and update variable references (`errors`, `status`).
7.  **Step 7: URL Config**: Update mappings in `config/url.config.php`.
8.  **Step 8: Database**: Perform `ALTER TABLE` operations for column renames and enum updates.

### Suggested Priority
1. `mod_content` (Core)
2. `mod_category` (Dependency)
3. `mod_page` (Simple)
4. `mod_menu` (Navigation)

---

## 7. Verification Plan

### Automated Checks
- Use `grep` to ensure no legacy terms (`ekle`, `yayin`, `durum`, `yetki`) remain in the refactored module.
- Validate PHP syntax and Twig template rendering.

### Manual Verification
- Test image uploads (ensure `image` field is recognized).
- Test status toggles (publish/unpublish) in DataTables.
- Confirm user permissions function correctly with the new `permissions` session key.
