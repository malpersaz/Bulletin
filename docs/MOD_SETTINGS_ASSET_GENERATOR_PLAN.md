# mod_settings Asset Generator Plan

Last updated: 2026-03-13
Status: Implementation In Progress
Owner: Codex + user

## Objective

Replace the manual multi-file upload flow in `mod_settings` asset management with a user-friendly single-source generation workflow.

Target behavior:

- User uploads one master brand asset.
- System automatically generates the required favicon / icon / manifest asset set.
- Manual per-file uploads become fallback-only or are removed in a later cleanup step.

## Problem Statement

The current `templates/admin/mod_settings/edit.twig` asset section expects the user to upload multiple separate files:

- logo
- favicon
- favicon 96x96
- favicon svg
- apple touch icon
- manifest 192
- manifest 512
- webmanifest

This is technically tedious and not user-friendly.

## Current Code State

Relevant backend file:

- `mod/mod_settings/index.php`

Relevant template:

- `templates/admin/mod_settings/edit.twig`

Current backend behavior:

- `logo_path` upload is handled separately.
- Other files such as `favicon_path`, `favicon_96`, `apple_touch_icon`, `manifest_192`, `manifest_512`, and `site_webmanifest` are handled one by one.
- `site_webmanifest` is manually uploaded and stored as `site.webmanifest`.

Current framework image behavior:

- `mod/image/index.php` accepts `jpg`, `jpeg`, `png`, `gif`, `svg`, `webp`, `ico`
- Raster formats are generally converted/saved through Intervention Image + GD driver
- `svg` and `ico` are currently copied as-is, not transformed

## Production Environment Findings

Live target environment is Linux + PHP.

Confirmed on target environment:

- `imagick` PHP extension: yes
- `gd` PHP extension: yes
- ImageMagick library available through PHP imagick: yes
- `open_basedir`: none
- PHP `memory_limit`: `512M`
- PHP `upload_max_filesize`: `512M`
- PHP `post_max_size`: `512M`
- PHP `max_execution_time`: `0`
- Live project path confirmed: `/www/htdocs/w01a5d7f/koksor.com`
- `static/mod_settings` exists on production: yes
- `static/mod_settings` writable on production: yes
- Supported formats reported by imagick include:
  - `ICO`
  - `PNG`
  - `WEBP`
- `SVG` did **not** appear in the reported supported formats list

Interpretation:

- `PNG -> ICO / PNG / WEBP` generation is safe to implement
- `SVG -> raster generation` should **not** be assumed safe on production
- No remaining production environment blocker is currently known for PNG-based automatic asset generation

## Technical Decision

Primary source format for automatic generation:

- High-resolution `PNG`

Do not make `SVG` the required master source for the first implementation.

## Planned Generated Files

From one uploaded master PNG, generate:

- `logo_path`
- `favicon_path`
- `favicon_96`
- `apple_touch_icon`
- `manifest_192`
- `manifest_512`
- `site_webmanifest`

Planned filenames should align with existing frontend expectations where possible:

- `logo.*`
- `favicon.ico`
- `favicon-96x96.png`
- `apple-touch-icon.png`
- `web-app-manifest-192x192.png`
- `web-app-manifest-512x512.png`
- `site.webmanifest`

Output location rule:

- Generated files must be written directly into `root/static/mod_settings/`
- Do not generate `thumb/` variants for favicon, icon, manifest, or webmanifest outputs
- `thumb/` generation is not part of this asset pipeline except for a future logo-specific need that is explicitly justified

## Open Question

Need to decide whether `logo_path` should remain:

- a raster output such as `logo.webp` / `logo.png`
- or a separately managed display logo

Because current frontend and installer history show mixed expectations around `logo.svg`, `logo.png`, and generated asset naming.

## Implementation Plan

### Phase 1: Backend Generator

Implement a dedicated asset generation service for `mod_settings`.

Candidate name:

- `BrandAssetGenerator`

Responsibilities:

- read uploaded master PNG
- generate required icon sizes
- generate `favicon.ico`
- write generated files into `static/mod_settings/`
- generate `site.webmanifest`
- update `mod_settings` record fields consistently
- sync shared asset fields across languages if required by current module behavior

### Phase 2: mod_settings Integration

Update `mod/mod_settings/index.php` so that:

- when a master logo file is uploaded, the generator runs
- manual favicon/icon uploads are bypassed or treated as fallback
- old generated assets are safely replaced

### Phase 3: Admin UI Simplification

Update `templates/admin/mod_settings/edit.twig` so that:

- the asset section becomes single-source first
- manual asset fields are reduced, hidden, or moved behind an advanced/fallback section
- helper text clearly explains that one PNG is enough

### Phase 4: Validation and Safety

Add validation rules:

- accept PNG as the guaranteed supported source format
- enforce minimum size expectations for quality output
- return clear admin errors when source image is too small

Recommended minimum:

- at least `512x512`

Preferred:

- `1024x1024` or larger

## Anti-Regression Rules For This Work

- Do not reintroduce multi-file-first UX as the primary path.
- Do not depend on SVG conversion unless production support is explicitly verified.
- Do not route generated favicon or manifest files through a generic thumbnail workflow.
- Do not invent a custom bottom action bar for this module; reuse the shared modern pattern.
- Do not add decorative hero or stats UI to explain asset generation.
- Do not leave visible hardcoded strings in Twig; use language files with English key names.

## Current Progress Snapshot

Completed:

- Verified production PHP extensions: `imagick` and `gd`
- Verified ImageMagick format support via `php --ri imagick`
- Verified direct Imagick format query support for `ICO`, `PNG`, and `WEBP`
- Verified production PHP limits are sufficient for image processing
- Verified production `static/mod_settings` directory exists and is writable
- Determined that `SVG` support is not currently safe to assume
- Chosen `PNG` as the safe master upload format
- Added a dedicated backend generator service for `mod_settings`
- Wired master logo upload into `mod/mod_settings/index.php`
- Switched the admin asset UI to a single PNG-first workflow with generated file listing
- Updated installer default `logo_path` to `logo.png`

Next step:

- Run end-to-end runtime verification in an environment where the web PHP process has Imagick available

## Notes

This is a task-level working file created to preserve continuity during a longer implementation. Once the feature is shipped, its lasting rules and outcomes should be folded back into:

- `ADMIN_MODERNIZATION_MASTER_PLAN.md`
- `VERSION.md`
