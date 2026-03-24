# Social Media Management Modernization Plan

This document outlines the plan for modernizing the social media management interface within the `mod_settings` module. The goal is to provide a professional, AJAX-powered, and user-friendly experience using Material Design 3 (M3) principles.

## 1. Objectives
- Implement a dedicated section for social media links in the Site Settings.
- Enable full CRUD (Create, Read, Update, Delete) operations via AJAX.
- Provide real-time feedback (success/error banners) using the existing system patterns.
- Ensure cross-language consistency (social links are global but accessible from any language setting page).

## 2. Architectural Changes

### 2.1 Backend (PHP/Controller)
We need to add the following endpoints to `mod/mod_settings/index.php`:
- `ajax_social_list()`: Returns the list of social media links as JSON or renders a partial template.
- `ajax_social_save()`: Handles adding or updating a social media platform.
- `ajax_social_delete()`: Handles deleting a social media platform.
- `ajax_social_toggle_status()`: Quickly enables/disables a platform.
- `ajax_social_reorder()`: Handles the display order of platforms.

### 2.2 Frontend (Twig/HTML)
- Add a new `<details>` section in `templates/admin/mod_settings/edit.twig` for "Social Media Links".
- Use M3-style cards or list items for each platform.
- Implement an "Add New Link" modal or inline form.

### 2.3 Frontend (JavaScript)
- Update `templates/admin/assets/js/mod_settings.js` to handle the new AJAX operations.
- Integrate with `Swal.fire` (SweetAlert2) for confirmations and alerts.
- (Optional) Use `SortableJS` if available for drag-and-drop reordering, or implement simple up/down logic.

## 3. Implementation Steps

### Phase 1: Controller Enhancement
1. Modify `mod/mod_settings/index.php` to include methods for social media management.
2. Ensure proper permission checks for each AJAX endpoint.

### Phase 2: Template Update
1. Update `templates/admin/mod_settings/edit.twig` to include the UI for social media.
2. Define new macros or partials for social media items to keep the code clean.

### Phase 3: JavaScript Integration
1. Add event listeners for social media actions (Edit, Delete, Toggle, Add).
2. Implement the `fetch` calls to the new controller endpoints.
3. Handle UI updates dynamically without page reload.

### Phase 4: Styling
1. Update `templates/admin/assets/css/mod_settings.css` to accommodate the new UI components.
2. Ensure mobile responsiveness and M3 aesthetics.

## 4. Technical Details

### Database Table
- Table: `mod_settings_social`
- Fields: `id`, `platform`, `url`, `icon_class`, `display_order`, `is_active`, `user_id`.

### AJAX Response Format
Success: `{ "status": "success", "message": "...", "data": [...] }`
Error: `{ "status": "error", "message": "..." }`

---
*Plan created on: March 22, 2026*
