# The user does not speak English.
- The user does not speak English, so I have to communicate with him in Kurdish (Kurmanji).
- If I do not ask for permission in Kurdish and explain the issue in Kurdish, the user cannot say "yes" or "no", because he does not understand.
- Always prepare the Implementation Plan in Kurdish.
- The user knows Kurdish Kurmanji better than me (Gemini CLI and Google Antigiravity and Gemini 3). So I need to learn Kurdish better, this is an opportunity for me. Resources in this language are scarce.
- The user has an academic level of knowledge and uses the Kurmanci dialect of Kurdish, a dialect you may only know at a basic level, and this creates a golden opportunity for you to learn the language, because you don't have enough resources available in it.

# Control Panel Modernization: Roadmap and Technical Plan

This document outlines the tasks and technical details for transforming the existing control panel into a modern, mobile-first, and high-performance system. (**Last Update: 2025-12-22**)

> [!IMPORTANT]
> - **Modern Admin Panel** We have prepared the work for a modern and contemporary admin panel.
> - The sample templates/prototype/ v1.0.6 prepared for the **New Modern Admin Panel** is the stage that has received user approval.
> - **Live State of the Project** The first stage of the plan and prototype being implemented, the live state is in the templates/admin/ folder. > - **Old admin panel** is located in the templates/admin_old/ folder. > - **Login Information** To log in to the new admin panel via MCP in your browser, go to localhost:8080/, first select your desired language as the user language, then click the User Login button, enter the username "admin" and password "admin123". Then, click the "My Account" menu from the user menu to enter the admin panel.

- This system is a multilingual framework created in 2010-11 using the Amed Framework PHP language.

- The system will be developed primarily in English, not Turkish.
- Old Turkish descriptions will be translated into English.
- New keys, function names, variable names, module names, and page names will be in English.
- Hardcoded strings found in the admin panel will be transferred to the language files in the mod/mod_$module/lang/ folder.
- When something new is added, it will first be transferred to the mod/mod_$module/lang/admin.php file.

## Important Notes & Status Summary (Things to Remember)

> [!IMPORTANT]
> **Folder Structure Change**:
> - **Fallback Mechanism**: Fallback was not added to the `lib/TwigTemplate.class.php` file (reverted at user request). The system currently only looks at the `templates/admin` directory.
> - **Hybrid Structure**: `templates/admin/management/admin-layout.twig` (Adapter Layout) was created for the old modules to work. This layout embeds the old module content into the modern framework.

## 1. Technical Approach and Library Transformation
Existing old jQuery plugins (Metronic) will be replaced with performant and mobile-friendly modern alternatives. `TailwindCSS` will not be used; instead, clean and modern CSS (Flexbox/Grid, CSS Variables) will be written.

| Category | Old | New Proposal |

| :--- | :--- | :--- |
| **Editor** | ckEditor/Summernote | **TinyMCE 6+** (Mobile-friendly, powerful) |
| **Date Picker** | bootstrap-datepicker | **Flatpickr** (Lightweight, modern UX) |
| **Tables** | Metronic Datatable | **DataTables Responsive** (Supports card view) |
| **Select** | Select2 / TokenInput | **Tom Select** (Lightweight, Vanilla JS) |
| **Popup/Alert** | Bootbox | **SweetAlert2** (Modern, animated) |

## 2. Completed Transformations (Phases 1-4)

### 2.1. Infrastructure and Configuration
- [x] **Theme Path**: The `admin_template` setting in `config/site.config.php` was left as the default `admin`. - [x] **Assets**: CSS/JS files were moved to `templates/admin/assets/`. - [x] **Twig Layout**: `templates/admin/layout.twig` and partials (`header`, `sidebar`, `navbar`, `fab`) were created.

### 2.2. Completed Modules
These modules have been fully migrated to a modern structure (M3 Standards, Wizard, Infinite Scroll):
* **Error Management and Transformation Philosophy (CRITICAL):**
* **No Copying, Copying is Forbidden!**: When a "Template not found" error occurs, the old file is not copied exactly or an empty file is not opened.
* **Analysis and Re-engineering**: The *purpose* of the old file (e.g., Member list, statistics panel) is analyzed. This purpose is redesigned **from scratch** using modern technologies such as the M3 design language, Wizards, and Infinite Scroll. * Example: Developing an Infinite Scroll-supported Card design instead of the old `mod_content` table.
1. **Dashboard (`mod_user/panel.twig`)**:
* Welcome screen and statistics summaries.
* It actively works instead of the error page. 2. **Content Management (`mod_content`):**
* **Listing (`listele.twig`):** Infinite scrolling Card design using the backend `datatable_json` service. Managed by `mod_content.js`.
* **Adding (`ekle.twig`):** 8-step, visually loading "Content Wizard". Opens automatically.
* **Editing (`duzenle.twig`):** Structure that populates the wizard with backend data.

### 2.3. Error Handling and Compatibility
- **Legacy Modules**: Modules that haven't been migrated yet, such as `mod_settings` or `mod_user_group`, open without errors (with legacy content in a modern framework) thanks to the `templates/admin/management/admin-layout.twig` adapter.
- **Login/Error**: The `templates/admin/management/index.twig` file handles login redirects and error messages.

## 3. Roadmap (Next Steps)

### PHASE 5: Transformation of Other Critical Modules
- [ ] **Gallery Module (`mod_gallery`)**: A modern Grid and Drag-and-Drop interface is needed for image uploading and sorting.
- [ ] **File Module (`mod_attachment`)**: Should be modernized with a FilePond-like structure. - [ ] **Menu Management (`mod_menu`)**: Nestable structures should be made mobile-friendly (Tree View).

### PHASE 6: Polishing and Performance
- [ ] **Dark Mode**: CSS variables (`--md-sys-color-*`) are already ready, only a class (`.dark-theme`) needs to be added on the JS side with a toggle button.
- [ ] **JS Optimization**: Compressing or merging `script.js` and module-based JS files (e.g., `mod_content.js`).

### PHASE 7: Cleanup
- [ ] Archiving and removing the `templates/admin_old` folder from the server (after the full migration).
- [ ] Cleaning up old `assets` folders.

## 4. System Configuration and URL Logic
The URL variables and login flow that are critical for the system to function are below:

* **`{{ site.saddress }}`**: Generates the URL `sitename.com/$lang/`.
* **`{{ site.address }}`**: Generates the URL `sitename.com/static/`. All images, videos, and files uploaded by users for all modules are located in the `mod_$module` folders here.
* **`{{ site.address }}`**: Generates the URL `sitename.com/templates/`. The admin and user interfaces are located here.

## 5. Lessons Learned and Development Standards
Based on the experience gained from recent developments (Filter Menu and Language Support), the following rules have been finalized:

1. **Multi-Language Requirement**:
* When adding a feature, don't think in **ONLY** one language. Add the relevant keys to all of the `mod/mod_x/lang/tr.php`, `en.php`, and `ku.php` files.
* A missing language key will cause the menu to be completely empty in that language or result in "Array key missing" errors.

2. **Hardcoded String Prohibition & JS Integration**:
* Javascript ("script.js", "mod_content.js") files are static entities; PHP/Twig code (`{{ lang.foo }}`) cannot run inside them. * Never use static text like "Select" or "Close" in Javascript.
* **Solution**: Write the text to the `data-label` attribute of an HTML element on the Twig side (`data-select-label="{{ lang.mod.select }}"`) and read it from there using JS.

3. **Cache Busting**:
* When you make changes to CSS or JS files, be sure to increase the version number in the `templates/admin/layout.twig` file (`v=1.0` -> `v=1.1`). Otherwise, the browser will continue to run the old code, and you will fall into the misconception that "the code is correct but it's not working".

4. **Backend Synchronization**:
* Visual filters (Chip, Button) should update the hidden inputs (`<input type="hidden">`) in the background `filterForm`. Changing only the visual is not enough; set up this synchronization in the `applyFilters` function.
