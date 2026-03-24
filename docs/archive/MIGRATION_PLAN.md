# Refactoring Plan: Renaming File Modules

This document outlines the step-by-step plan to rename two core modules in the codebase to eliminate confusion and improve semantic clarity.

**Current State:**
1. `mod/mod_file` (Used for attaching files to other content)
2. `mod/mod_document` (Used for standalone document/download management)

**Target State:**
1. `mod/mod_file` -> **`mod/mod_attachment`**
2. `mod/mod_document` -> **`mod/mod_document`**

---

## Phase 1: Database Migration
*Objective: Rename tables and update internal string references in the database.*

1.  **Backup:** Create a full backup of the `data.sqlite` or MySQL database.
2.  **Rename Tables:**
    *   Rename table `mod_file` to `mod_attachment`.
    *   Rename table `mod_document` to `mod_document`.
3.  **Update References (Data Fixes):**
    *   Update `mod_name` columns in permission tables (e.g., `mod_acl`, `mod_user_group`) where value is `'mod_attachment'` to `'mod_attachment'`.
    *   Update `mod_name` columns where value is `'mod_document'` to `'mod_document'`.
    *   Update any `url` or `seo` fields in `mod_menu` pointing to old module names.

## Phase 2: File System Restructuring
*Objective: Rename directories to match the new module names.*

1.  **Module Directories:**
    *   `mv mod/mod_file mod/mod_attachment`
    *   `mv mod/mod_document mod/mod_document`
2.  **Static Assets:**
    *   `mv static/mod_file static/mod_attachment`
    *   `mv static/mod_document static/mod_document`
3.  **Templates (Admin & Front):**
    *   `mv templates/admin/mod_file templates/admin/mod_attachment` (if exists)
    *   `mv templates/admin/mod_document templates/admin/mod_document` (if exists)
    *   `mv templates/default/mod_file templates/default/mod_attachment` (if exists)
    *   `mv templates/default/mod_document templates/default/mod_document` (if exists)
4.  **Installer (If applicable):**
    *   `mv installer/mod_file installer/mod_attachment`
    *   `mv installer/mod_document installer/mod_document`

## Phase 3: Code Refactoring
*Objective: Update class names, references, and string literals in the source code.*

### A. Renaming `mod_file` to `mod_attachment`
1.  **Search & Replace Strings:**
    *   `'mod_attachment'` -> `'mod_attachment'`
    *   `"mod_attachment"` -> `"mod_attachment"`
2.  **Class Renaming:**
    *   `class mod_attachment_mod` -> `class mod_attachment_mod` (in `mod/mod_attachment/index.php`)
    *   `class mod_attachment_model` -> `class mod_attachment_model` (in model files)
3.  **Loader Calls:**
    *   `Load::model('mod_attachment')` -> `Load::model('mod_attachment')`
    *   `Load::lang('mod_attachment')` -> `Load::lang('mod_attachment')`

### B. Renaming `mod_document` to `mod_document`
1.  **Search & Replace Strings:**
    *   `'mod_document'` -> `'mod_document'`
    *   `"mod_document"` -> `"mod_document"`
2.  **Class Renaming:**
    *   `class mod_document_mod` -> `class mod_document_mod` (in `mod/mod_document/index.php`)
    *   `class mod_document_model` -> `class mod_document_model` (in model files)
3.  **Loader Calls:**
    *   `Load::model('mod_document')` -> `Load::model('mod_document')`
    *   `Load::lang('mod_document')` -> `Load::lang('mod_document')`

## Phase 4: Configuration & Cleanup
1.  **Config Files:**
    *   Check `config/` directory for any hardcoded permission lists or module configs involving the old names.
2.  **Cache Clearing:**
    *   Empty `templates_c/` folder.
    *   Empty `cache/` folder.
3.  **Dependency Check:**
    *   Ensure modules that depend on attachments (like `mod_content` or `mod_news`) now correctly call `mod_attachment`.

## Phase 5: Verification
1.  **Test Admin Panel:**
    *   Verify "Attachments" (formerly mod_file) work when adding a new content.
    *   Verify "Documents" (formerly mod_document) list, add, and delete functions work.
2.  **Test Frontend:**
    *   Verify download links and document pages are accessible.
