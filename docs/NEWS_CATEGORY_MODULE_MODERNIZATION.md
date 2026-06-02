# News Category Module Modernization (v1.0.56)

This document details the modernization of the Admin News Category module (`mod_news_category`) in Rojname CMS, synchronized with the twin project Business CMS.

## Overview

The news category module has been refactored from a flat list view into a modern, phone-first nested tree structure utilizing SortableJS for drag-and-drop reordering. The backend controller and templating layers have been updated to support hierarchy loop validations, home page visibility toggles, recursive deletion of subcategories, and complete multilingual localization.

## Key Changes

### 1. Presentation Layer (Twig & Assets)
- **Category Tree List (`templates/admin/mod_news_category/list.twig`)**:
  - Replaced the simple list table with a recursive, nested tree card view using Twig macro recursion.
  - Added a SortableJS drag-and-drop controller to rearrange categories dynamically.
  - Implemented home page visibility indicator toggles and category select handlers.
- **Add Category Wizard (`templates/admin/mod_news_category/add.twig`)**:
  - Upgraded form layouts to standard phone-first M3 inputs.
  - Added support for selecting parent categories and syncing with menu slots.
- **Edit Category (`templates/admin/mod_news_category/edit.twig`)**:
  - Added SweetAlert2 confirmation dialogs for thumbnail image removals.
- **Category Script (`templates/admin/mod_news_category/script.twig`)**:
  - Refactored category drag-and-drop handler script, supporting lock/unlock drag reordering, and direct ajax deletion / status toggles.

### 2. Backend Controller & Validation
- **Controller Refactoring (`mod/mod_news_category/index.php`)**:
  - Implemented dynamic display name mapping for language roots starting with `##`.
  - Added cyclic parent-child loop checking to prevent infinite hierarchy loops when rearranging categories.
  - Added dynamic ancestry-based language resolution to validate that child categories belong to the same language tree as their parents.
  - Synchronized category creation and updates with menu table slots (`mod_menu`).
  - Standardized recursive deletion to clean up child categories, images, menu slots, and linked media attachments recursively.

### 3. Language & Localization Layers
- **Standardized English Reference (`mod/mod_news_category/lang/en.php`)**:
  - Standardized meta column headings and action prompt strings.
- **Localization Parity (`ku.php`, `tr.php`, `zza.php`)**:
  - Fully mapped all localizations without fallback text.

## Verification Plan

### Manual Verification
1. Access the updated admin news category list on a phone-sized viewport.
2. Toggle the "Unlock Reorder" button and verify that drag handles become active and SortableJS allows moving category nodes.
3. Test editing parent relations, selecting language dropdowns, and changing category visibility.
4. Try deleting a parent category and verify SweetAlert prompts recursive subcategory deletion successfully.
