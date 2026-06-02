# Video Module Modernization (v1.0.56)

This document details the modernization of the Admin Video Management module (`mod_video`) in Rojname CMS, synchronized with the twin project Business CMS.

## Overview

The video management module has been refactored from a legacy desktop-first layout utilizing Flash-based uploading and outdated Datatables, into a modern, phone-first Material Design 3 (M3) interface. The backend has also been synchronized to support native HTML5 video uploads, progress bar feedback, local server-side FFMPEG video processing with safe fallbacks, and complete multilingual localization.

## Key Changes

### 1. Presentation Layer (Twig & Assets)
- **Video List (`templates/admin/mod_video/list.twig`)**:
  - Replaced the desktop-first portlet table with a phone-first M3 card layout.
  - Implemented infinite scrolling and pull-to-refresh.
  - Integrated a contextual bottom action toolbar (`fixed_bottom`) for editing, publishing, deleting, and uploading images.
- **Add Video Wizard (`templates/admin/mod_video/add.twig`)**:
  - Replaced the deprecated Flash `uploadify` plugin with a native HTML5 `<input type="file">` file-uploader.
  - Added real-time upload progress tracking using an AJAX XMLHttpRequest progress bar.
  - Implemented dynamic step-by-step navigation (Upload file -> Convert video -> Fill details).
  - Integrated local HTML5 `<video>` previewing.
- **Edit Video (`templates/admin/mod_video/edit.twig`)**:
  - Upgraded forms to use M3 fields and a clean card structure.
  - Replaced legacy prompt boxes with SweetAlert2 confirmation dialogs.
  - Added native inline image uploading and removal.
- **JavaScript Manager (`templates/admin/assets/js/mod_video.js`)**:
  - A new standalone script that handles asynchronous data loading, search filtering, status toggling, deletion confirmation, and AJAX image upload.

### 2. Backend Controller & Validation
- **Controller Refactoring (`mod/mod_video/index.php`)**:
  - Fully standardizes DB fields to use `duration` instead of the legacy mismatched `sure`.
  - Added `ajax_image_upload()` to allow operators to change video thumbnails directly from the card list view.
  - Hardened the `cevir()` (video conversion) action to detect local `ffmpeg`/`ffprobe` binaries dynamically, falling back gracefully to a non-conversion path if FFMPEG is missing or fails.
  - Added native `get_mp4_duration()` to parse MP4 metadata atoms in sub-milliseconds to populate duration.
- **Validators (`mod/mod_video/validator/`)**:
  - Renamed the legacy `ekle.validator.php` to `add.validator.php` to align with the core naming guidelines.
  - Updated both `add.validator.php` and `edit.validator.php` to validate `duration` instead of `sure`.

### 3. Language & Localization Layers
- **Standardized English Reference (`mod/mod_video/lang/en.php`)**:
  - Mapped all UI labels, form placeholders, status indicators, and validation rules.
- **Localization Parity (`ku.php`, `tr.php`, `zza.php`)**:
  - Fully localized all files to Kurdish, Turkish, and Zazaki.
  - Enforced Kurdish grammar conventions (e.g. prioritizing "nûjenkirin" over "nûve" for updates).

## Verification Plan

### Manual Verification
1. Access the updated admin video list on a phone-sized viewport.
2. Select a card and verify that the bottom toolbar lights up and is responsive.
3. Test adding a video file; confirm the progress bar functions and details are saved.
4. Verify thumbnails can be updated via AJAX on the card view.
