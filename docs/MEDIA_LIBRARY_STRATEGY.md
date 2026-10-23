# Centralized Media Library & Path Resolution Strategy

**Document Status**: Draft Proposal / Strategy Architecture  
**Target Repository**: Amed Framework (AF Bulletin — Bulletin CMS)  
**Author**: Antigravity Assistant  
**Language**: English (Mandatory repository policy for `/docs/`)  

---

## 1. Overview & Problem Diagnosis

### 1.1 Context & Legacy Limitations
In the current implementation of Amed Framework (AF Bulletin), image uploads and rendering exhibit several structural limitations:

1. **Hardcoded Module Paths in Views**: Twig templates explicitly construct static asset URLs with hardcoded module folder prefixes:
   ```twig
   {# Legacy template binding #}
   <img src="{{ site.iaddress ~ 'mod_news/thumb/' ~ d.image }}">
   ```
2. **Module Coupling**: Uploaded images are physically saved into module-specific subdirectories (e.g. `static/mod_news/`, `static/mod_publication/`).
3. **Cross-Module 404 Failures**: If an image originally uploaded under `mod_news` is assigned to an item in `mod_publication`, rendering fails with a `404 Not Found` error because `mod_publication/thumb/filename.jpg` does not exist on disk.
4. **Storage Duplication**: Users uploading the same image multiple times cause redundant physical files on disk and duplicate database entries.
5. **Cascade Deletion Risk**: Deleting an item or its attached image in one module runs the risk of removing the physical file from `static/`, resulting in broken images across other entities referencing that file.

---

## 2. Target Architecture

The proposed solution aligns with modern enterprise CMS standards (e.g., WordPress Media Library, Drupal Media Entity, Strapi CMS, Laravel Spatie Media Library):

```
+-----------------------------------------------------------------------+
|                         Central Media Registry                        |
|                     (mod_media & Physical Storage)                    |
+-----------------------------------------------------------------------+
                                    |
                                    | Referenced via Media ID / UUID
                                    v
+-----------------------------------------------------------------------+
|                    Polymorphic Relationship Table                     |
|                      (mod_media_relationships)                        |
|  - model_type ('mod_news', 'mod_publication', 'mod_page', etc.)       |
|  - model_id                                                           |
|  - field_role ('thumb', 'gallery', 'body')                            |
+-----------------------------------------------------------------------+
                                    |
                                    | Resolved at Runtime via Twig Filter
                                    v
+-----------------------------------------------------------------------+
|                     Dynamic Path Resolver & Views                     |
|                {{ media_url(item.image, 'thumb') }}                  |
+-----------------------------------------------------------------------+
```

### Key Architectural Pillars

1. **Central Media Registry (`mod_media`)**: Decouples image storage from individual modules. Files are stored globally under structured date/hash paths (e.g., `uploads/media/2026/08/{file_hash}.webp`).
2. **Content-Addressable Storage (CAS)**: Computes a cryptographic checksum (SHA-256) upon upload. If a file with an identical hash already exists in storage, the system reuses the existing physical file without creating duplicates.
3. **Polymorphic Reference Tracking (`mod_media_relationships`)**: Maps media items to parent domain entities. Physical deletion is strictly prevented as long as active references exist (Reference Count > 0).
4. **Dynamic Path Resolver (`media_url` Twig Helper)**: Replaces hardcoded path strings in Twig templates with a dynamic resolver function that handles legacy filenames, relative paths, and media IDs seamlessly.
5. **Phone-First Responsive UI (Admin Media Picker)**: Extends `image_uploader.twig` modal to support selecting pre-existing images from the central media library.

---

## 3. Database Schema Definitions

### 3.1 Central Media Registry Table (`mod_media`)
```sql
CREATE TABLE `mod_media` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(36) NOT NULL UNIQUE,
  `file_hash` VARCHAR(64) NOT NULL, -- SHA-256
  `original_name` VARCHAR(255) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL, -- e.g., 'uploads/media/2026/08/uuid.webp'
  `mime_type` VARCHAR(100) NOT NULL,
  `file_size` INT(11) UNSIGNED NOT NULL,
  `width` INT(11) UNSIGNED DEFAULT NULL,
  `height` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_file_hash` (`file_hash`),
  KEY `idx_uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 Polymorphic Relationship Table (`mod_media_relationships`)
```sql
CREATE TABLE `mod_media_relationships` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `media_id` INT(11) NOT NULL,
  `model_type` VARCHAR(64) NOT NULL, -- e.g., 'mod_news', 'mod_publication', 'mod_page'
  `model_id` INT(11) NOT NULL,
  `field_role` VARCHAR(32) NOT NULL DEFAULT 'thumb', -- e.g., 'thumb', 'gallery', 'banner'
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_id` (`media_id`),
  KEY `idx_model` (`model_type`, `model_id`),
  CONSTRAINT `fk_media_rel_media` FOREIGN KEY (`media_id`) REFERENCES `mod_media` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 4. Path Resolution & Twig Integration Strategy

### 4.1 The `media_url` Twig Filter / Helper
To eliminate hardcoded Twig template paths without breaking existing content, a unified helper function `media_url()` is introduced:

```twig
{# Target standard rendering across all templates #}
<img src="{{ media_url(item.image, 'mod_news', 'thumb') }}" alt="{{ item.title }}">
```

### 4.2 Fallback Logic & Backward Compatibility
The `media_url` resolver handles inputs in three formats:

1. **Numeric Media ID / UUID**: Queries `mod_media` and returns the resolved URL.
   - Input: `1042` -> Output: `http://localhost:8080/static/uploads/media/2026/08/uuid.webp`
2. **Relative Path String**: Returns relative storage path directly.
   - Input: `'mod_gallery/picture.webp'` -> Output: `http://localhost:8080/static/mod_gallery/picture.webp`
3. **Legacy Filename String (Fallback)**: Appends the default fallback module prefix.
   - Input: `'filename.webp'`, Default: `'mod_news'` -> Output: `http://localhost:8080/static/mod_news/thumb/filename.webp`

---

## 5. Step-by-Step Implementation Roadmap

### Phase 1: Database Migration & Installer Update
- Create installer migration SQL scripts for `mod_media` and `mod_media_relationships`.
- Update `InstallerModuleCatalog.php` to register `mod_media` as a core dependency.

### Phase 2: Core Media Service Layer
- Implement `MediaService.class.php` under `lib/` or `mod/image/`:
  - `upload(array $file, string $modelType, int $modelId)`: Calculates SHA-256 hash, reuses existing file if present, registers relationship.
  - `attach(int $mediaId, string $modelType, int $modelId, string $role)`: Connects existing media item to a entity.
  - `detach(int $mediaId, string $modelType, int $modelId)`: Removes relationship and triggers physical cleanup only when reference count = 0.
  - `getUrl($mediaReference, string $fallbackModule, string $variant)`: Dynamic URL resolution.

### Phase 3: Twig Extension & Template Standardization
- Register `media_url` as a custom Twig filter and function in `lib/Theme.class.php`.
- Audit and refactor admin and frontend Twig files to replace hardcoded paths (e.g. `site.iaddress ~ 'mod_news/thumb/'`).

### Phase 4: Admin UI Media Picker Integration
- Upgrade `templates/admin/partials/image_uploader.twig` modal shell.
- Add "Select from Media Library" action button & drawer/modal section.
- Build AJAX endpoint `/admin/media/picker` with search, module filtering, and phone-responsive grid (2-column layout for mobile viewports).

### Phase 5: Migration & Verification
- Execute legacy content migration script to populate `mod_media` and `mod_media_relationships` from existing `mod_attachment` records.
- Validate across mobile viewports, verify zero 404 broken images, and confirm deduplication on upload.

---

## 6. Compliance & Policy Guidelines

- **Admin Mobile Compliance**: All Media Picker dialogs and UI elements must conform strictly to the `phone-only` mobile viewport standard (`admin_fixed_phone`).
- **Language Policy**: All code, database identifiers, and documentation must remain strictly in **English**.
- **Parity Standards**: Core media architecture changes must be documented for sibling repository parity synchronization when scheduled by explicit user request.
