# BRIEFING — 2026-08-23T20:20:00Z

## Mission
Investigate and survey AF/Rojname for R1 (Strict Code Separation & Admin User ACL Modernization) and R2 (Core Framework, ORM & Twig SEO Unicode Preservation) compared with AF/Business standards.

## 🔒 My Identity
- Archetype: explorer
- Roles: Codebase & Architecture Explorer (R1 & R2 Focus)
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Rojname/.agents/explorer_survey_1
- Original parent: 252c7225-77db-4c3e-bc1d-f37695c6498d
- Milestone: Survey and Gap Analysis for R1 and R2

## 🔒 Key Constraints
- Read-only investigation — do NOT implement changes in codebase
- Strict Code Separation (No inline CSS or JS in PHP/Twig)
- English identifiers and comments
- Phone-Only Mobile First admin surface
- Wikipedia-style URL generation parity

## Current Parent
- Conversation ID: 252c7225-77db-4c3e-bc1d-f37695c6498d
- Updated: 2026-08-23T20:20:00Z

## Investigation State
- **Explored paths**:
  - `templates/admin/mod_user/acl.twig` & `mod/mod_user/index.php`
  - `templates/admin/mod_acl/` (`list.twig`, `add.twig`, `edit.twig`, `script.twig`) & `mod/mod_acl/index.php`
  - `templates/admin/partials/header.twig`, `filter_sheet.twig`, `sheets_extra.twig`
  - `templates/admin/assets/js/modals/modal_user_menu.js`, `core_modals.css`
  - `lib/TwigLibs.class.php`, `lib/Helper.class.php`
  - `lib/Orm/Orm.php`, `lib/Orm/Obuilder.php`
  - `lib/BaseModuleController.class.php`
- **Key findings**:
  - `mod_user/acl.twig` contains heavy inline styles (lines 10-132), inline `<script>` blocks (lines 135-158, 245-279), inline `onclick` handlers, and legacy synchronous form submit.
  - `mod/mod_user/index.php` lacks the asynchronous `toggle_permission()` AJAX endpoint and sends Turkish variable names to the template.
  - `mod_acl` templates (`list.twig`, `add.twig`, `edit.twig`) contain inline styles and `<script>` blocks; `script.twig` contains extensive Turkish strings and obsolete Datatable code. `mod/mod_acl/index.php` contains legacy Turkish callbacks (`processSil`, `processDosya`).
  - `header.twig` and `filter_sheet.twig` contain inline styles and inline `onclick` attributes instead of routing through `data-action` delegated listeners in `modal_user_menu.js`.
  - `lib/TwigLibs.class.php` (`legacySeoFilter`) uses an ASCII-only regex that corrupts Kurdish Unicode characters instead of delegating to `Helper::legacy_seo($s)`.
  - `lib/Orm/Orm.php` and `Obuilder.php` lack case-insensitive column comparison and have Turkish exception messages.
  - `lib/BaseModuleController.class.php` lacks centralized AJAX relation helper methods (`ajax_get_files()`, `ajax_get_images()`, `ajax_get_current_media()`, `ajax_update_relation()`).
- **Unexplored areas**: None for R1 & R2 scope.

## Key Decisions Made
- Fully documented all file paths, exact line numbers, code diffs, and modernization requirements in handoff.md.

## Artifact Index
- `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname/.agents/explorer_survey_1/handoff.md` — Survey & Gap Analysis Report
- `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname/.agents/explorer_survey_1/progress.md` — Heartbeat Tracking
