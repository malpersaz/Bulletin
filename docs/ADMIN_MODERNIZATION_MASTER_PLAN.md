# Admin Modernization Master Plan

This document is the single source of truth for the admin panel modernization and the global Turkish-to-English refactoring effort in Rojname.

Last updated: 2026-03-13
Current target version: v1.0.45

## 1. Objective

The admin panel is being transformed from a legacy Metronic/jQuery-based interface into a modern, mobile-first, maintainable system built on a shared Twig layout, reusable partials, and module-specific JavaScript.

This document replaces the older fragmented planning files and combines:

- Admin modernization roadmap
- Global refactoring standards
- Module-level modernization status
- Legacy compatibility decisions
- Remaining work and priorities

## 2. Source of Truth Policy

Only two active project-wide Markdown files should remain at the repository root:

1. `ADMIN_MODERNIZATION_MASTER_PLAN.md`
2. `VERSION.md`

Rules:

- Future plans, current state, standards, and remaining work belong here.
- Completed work belongs in `VERSION.md`.
- Temporary task notes or module-specific planning drafts must not remain as active root documentation.
- Historical planning files are archived under `docs/archive/`.

## 3. Core Direction

### 3.1 Product Direction

- The new admin panel must be modern, mobile-first, and high-performance.
- The live implementation target is `templates/admin/`.
- The old admin panel remains in `templates/admin_old/` until migration is complete.
- Legacy modules may continue to run through an adapter layout during transition.

### 3.2 Language Direction

- The system is developed primarily in English, not Turkish.
- Old Turkish descriptions should be translated into English.
- New keys, functions, variables, module names, and page names must use English.
- Hardcoded strings in templates and JavaScript should be moved into language files.

### 3.3 Documentation Direction

- `ADMIN_MODERNIZATION_MASTER_PLAN.md` is the only active planning document.
- `VERSION.md` is the only active changelog.
- Any new work must update one or both of these files as part of completion.

### 3.4 Admin UI Non-Negotiables

These rules apply across Amed Framework admin work, not only to one module.

- Admin forms are work surfaces, not marketing surfaces.
- Prefer clarity over decoration.
- Prefer field labels over repeated card titles and explanatory copy.
- Use the existing shared admin patterns before inventing new ones.
- Secondary controls such as language selectors must stay secondary in both placement and visual weight.
- Visible user-facing text must come from language files, using English key names for newly introduced keys.
- If a module already has an accepted modern pattern for an interaction such as a bottom action bar, new modules should match it unless there is a real functional reason not to.
- Misleading section titles are worse than no section title.

## 4. Architecture Decisions

### 4.1 Admin Layout Structure

- Main modern layout: `templates/admin/layout.twig`
- Legacy adapter layout: `templates/admin/management/admin-layout.twig`
- Shared UI primitives: header, sidebar, navbar, FAB, sheets, wizards, toolbars

### 4.2 Legacy Compatibility

- Fallback was not added to `lib/TwigTemplate.class.php`.
- The system currently resolves admin templates only from `templates/admin`.
- Legacy modules can still render inside the modern shell via `management/admin-layout.twig`.

This hybrid model is intentional and remains active until all critical modules are fully modernized.

## 5. UI and Library Standards

Legacy Metronic-era plugins should be replaced by modern alternatives where practical.

| Category | Legacy | Standard |
| :--- | :--- | :--- |
| Editor | ckEditor / Summernote | TinyMCE 6+ |
| Date Picker | bootstrap-datepicker | Flatpickr |
| Tables | Metronic Datatable | Responsive card/list patterns or DataTables Responsive |
| Select | Select2 / TokenInput | Tom Select |
| Popup / Alert | Bootbox | SweetAlert2 |
| Layout | Portlet/table-heavy desktop UI | Mobile-first M3-style cards, sheets, toolbars |

TailwindCSS is not part of this migration. Styling should use clean CSS with variables, Flexbox, and Grid.

## 6. Global Refactoring Standards

### 6.1 Database and Field Naming

| Old | New |
| :--- | :--- |
| `ad` | `name` |
| `soyad` | `last_name` |
| `tarih` | `created_at` |
| `etarih` | `expired_at` |
| `yayin` / `durum` | `status` |
| `aciklama` | `description` |
| `resim` | `image` |
| `giris` | `summary` |
| `sira` | `order` |
| `alt` | `parent_id` |
| `dosya_id` | `file_id` |
| `kod` | `code` |
| `zaman` | `created_at` |

### 6.2 Status Values

- Old: `'e'`, `'h'`
- New: `'y'`, `'n'`
- Exception: admin-specific `'a'` may remain where required

### 6.3 Controller and Route Naming

| Old | New |
| :--- | :--- |
| `ekle()` | `add()` |
| `_ekle()` | `_add()` |
| `duzenle()` | `edit()` |
| `_duzenle()` | `_edit()` |
| `listele()` | `list()` |
| `sil()` | `delete()` |
| `bilgi()` | `info()` |
| `yayinla()` | `publish()` |
| `yayinkaldir()` | `unpublish()` |
| `aktifle()` | `activate()` |
| `pasifle()` | `deactivate()` |
| `tasi` | `move` |

### 6.4 Permissions and Session Naming

| Old | New |
| :--- | :--- |
| `ekle` | `add` |
| `duzenle` | `edit` |
| `sil` | `delete` |
| `listele` | `list` |
| `ddurum` | `edit_status` |
| `yetki` | `permissions` |

### 6.5 Twig and Frontend Naming

- `ekle.twig` -> `add.twig`
- `duzenle.twig` -> `edit.twig`
- `listele.twig` -> `list.twig`
- `hatalar` -> `errors`
- `hata` -> `error`
- `d.yayin` -> `d.status`
- `d.ad` -> `d.name`
- `d.resim` -> `d.image`

## 7. Working Standards

### 7.1 Multi-Language Requirement

When adding or changing features, relevant keys must be synchronized at least across:

- `tr.php`
- `en.php`
- `ku.php`

If a language key is missing, modules may render incomplete menus or throw missing-array-key errors.

### 7.2 Hardcoded String Rule

Hardcoded UI strings should not remain in active templates or JavaScript if they are part of the product interface.

For JavaScript:

- Twig language expressions cannot run inside static JS files.
- Text must be passed into JS via Twig-generated config objects, data attributes, or localized inline bootstrap objects.

### 7.3 Cache Busting

Whenever admin CSS or JS changes, version parameters in `templates/admin/layout.twig` must be increased.

### 7.4 Backend Synchronization

Visual controls are not enough by themselves. Filters, sort controls, and chips must synchronize with hidden form fields used by AJAX/DataTable endpoints.

### 7.5 Form Minimalism Rule

Admin edit forms must stay task-oriented and visually quiet.

Rules:

- Do not turn form screens into editorial or dashboard-like compositions.
- Do not add hero sections, summary cards, metric cards, or decorative overview blocks above a working form unless the module genuinely requires operational status visibility.
- A field label is usually enough. Do not repeat the same concept again as a card title, subtitle, helper paragraph, chip, or badge.
- Language selection is a secondary control. It must not be presented as the primary card or visual focal point of the page.
- Section headers must describe the actual content of the fields below them. Do not use generic or misleading labels such as "Language Settings" for a section that actually contains site title or company fields.
- If a section is already self-explanatory through field labels, prefer no extra descriptive copy.
- Reuse established action bars and shared interaction patterns from existing modern modules instead of inventing page-specific bottom menus.
- Avoid fallback UI copy in Twig such as `|default('...')` for visible product text in modernized modules. Visible strings must come from language files.

Important note:

- These rules were reinforced during the `mod_settings` modernization after repeated regressions caused by over-designed form treatment. They are framework-wide anti-regression rules for future admin work.

## 8. Current Status by Module

Status values:

- `Complete`: modern UI and refactoring target substantially achieved
- `In Progress`: modern UI exists but refactoring or cleanup is incomplete
- `Legacy via Adapter`: still primarily old structure inside modern shell
- `Legacy Only`: exists only in `templates/admin_old/` and is not yet represented in the active modern admin structure
- `Not Started`: no meaningful modernization in active admin UI

| Module / Area | Status | Notes |
| :--- | :--- | :--- |
| Admin shell (`layout.twig`, partials, assets) | Complete | Shared layout, header, navbar, sidebar, overlays, global JS bootstrap are active |
| Legacy adapter (`management/admin-layout.twig`) | Complete | Required for hybrid migration period |
| Dashboard (`mod_user/panel`) | Complete | Live modern dashboard instead of error page |
| `mod_content` | Complete | Modern list, wizard-based add/edit, infinite scroll, filter synchronization |
| `mod_user` | Complete | Modern list and major refactoring reference module |
| `mod_menu` | In Progress | Modern tree/list, SortableJS reorder, add/edit forms are active; backend naming cleanup still incomplete |
| `mod_category` | In Progress | Modern tree/list and smart form direction active; naming cleanup and some polish remain |
| `mod_page` | In Progress | Modern layout is used; refactoring consistency still incomplete |
| `mod_video_embed` | In Progress | Modern list and wizard patterns are active; route and naming cleanup remain |
| `mod_user_pm` | In Progress | Modern inbox and JS-driven interaction exist; planned stats/header polish is still incomplete |
| `mod_acl` | In Progress | Modern layout is used, but route and naming cleanup remain incomplete |
| `mod_video` | Legacy via Adapter | Still primarily portlet/table/datepicker-era structure |
| `mod_video_embed_category` | Legacy via Adapter | Still based on legacy structure |
| `mod_announcements` | Legacy Only | Present in `templates/admin_old/`, not yet represented in active modern admin templates |
| `mod_attachment` | In Progress | Modern direct list/upload templates have been introduced; relation flow and naming cleanup still remain |
| `mod_attachment_mod` | In Progress | Modern direct list/add/edit templates have been introduced; backend cleanup and terminology normalization still remain |
| `mod_document` | Legacy Only | Old-only admin UI; not yet queued into active modern template structure |
| `mod_gallery` | Legacy Only | Planned modernization target |
| `mod_gallery_mod` | Legacy Only | Gallery relation/management UI still old-only |
| `mod_headline` | Legacy Only | Old-only admin templates remain |
| `mod_imagecategories` | Legacy Only | Old-only admin templates remain |
| `mod_popup` | Legacy Only | Backend naming work exists, but active admin UI is still old-only |
| `mod_settings` | In Progress | Modern direct template migration is active in `templates/admin/mod_settings/`; the primary admin screen now uses the English `edit` route/template, while broader backend naming cleanup remains incomplete |
| `mod_slider` | Legacy Only | Backend naming work exists, active admin UI not modernized |
| `mod_survey` | Legacy Only | Old-only admin UI remains |
| `mod_user_group` | In Progress | Modern direct list/add/edit templates have been introduced; backend endpoint and naming cleanup still remain |

## 8.1 Legacy Inventory Not Yet Moved into `templates/admin/`

The following modules still exist in `templates/admin_old/` and should be tracked in the modernization plan even if they are not yet being actively migrated:

- `mod_announcements`
- `mod_attachment`
- `mod_attachment_mod`
- `mod_document`
- `mod_gallery`
- `mod_gallery_mod`
- `mod_headline`
- `mod_imagecategories`
- `mod_popup`
- `mod_settings`
- `mod_slider`
- `mod_survey`
- `mod_user_group`

Decision:

- These modules should be included in the plan immediately.
- They should not be copied or bulk-moved into `templates/admin/` before active modernization starts.
- A module should enter `templates/admin/` only when one of the following is true:
  1. its modern UI implementation is actively being built, or
  2. it is intentionally introduced through a controlled adapter strategy for a specific migration step.

## 9. Module Notes

### 9.1 `mod_content`

Reference modern module.

Implemented:

- Infinite-scroll card list
- Hidden filter form synchronized with UI
- Add wizard
- Edit wizard population
- Shared M3-style toolbar and overlays

### 9.2 `mod_user`

Reference refactoring module.

Implemented:

- English controller/action naming
- Standardized fields and statuses
- Modern admin list and actions

### 9.3 `mod_menu`

Implemented:

- Modern tree rendering
- Mobile-friendly reorder mode with SortableJS
- Modern add/edit forms
- Tom Select integration
- SweetAlert2 confirmation flow

Still needed:

- Full backend/controller/route rename consistency
- Removal of remaining legacy method endpoints and strings

### 9.4 `mod_category`

Implemented:

- Modern tree list
- Reorder mode
- SweetAlert2 delete flow
- Smart-form direction with SEO helpers and enhanced fields

Still needed:

- Full English naming consistency
- Final form cleanup
- Removal of remaining hardcoded strings

### 9.5 `mod_user_pm`

Implemented:

- Modern inbox shell
- Dynamic message list container
- Empty state
- Infinite loader
- Fixed bottom toolbar
- Dedicated JS manager

Still needed:

- Planned quick stats cards
- Final consistency cleanup for labels and action semantics

### 9.6 `mod_settings`

Implemented / clarified during active modernization:

- The screen is a form-first admin workspace, not a showcase page.
- The language selector must remain a compact secondary control.
- The top form area should rely on field labels, not duplicated card headings or explanatory marketing copy.
- The bottom action bar should follow the shared `mod_category/edit.twig` pattern instead of a module-specific design.
- Newly introduced language keys for this module should use English key names such as `button.*` and `sections.*`.

Anti-regression notes:

- Do not reintroduce hero blocks, stats cards, badges, or narrative descriptions above the form.
- Do not duplicate field meaning in multiple layers such as section title plus identical field label.
- Do not leave visible hardcoded fallback strings in Twig for this module.

### 9.7 Legacy-Only Module Group

The following modules are now officially part of the modernization scope, but remain outside active `templates/admin/` implementation:

- `mod_announcements`
- `mod_attachment`
- `mod_attachment_mod`
- `mod_document`
- `mod_gallery`
- `mod_gallery_mod`
- `mod_headline`
- `mod_imagecategories`
- `mod_popup`
- `mod_settings`
- `mod_slider`
- `mod_survey`
- `mod_user_group`

For this group, the current strategy is:

- Track them in the master plan now
- Prioritize them intentionally
- Avoid bulk copying into `templates/admin/`
- Migrate one module at a time based on product value, dependency impact, and risk

### 9.8 First-Wave Legacy Migration Matrix

The first migration wave should focus on the modules below.

| Module | Legacy Shape | Target Modern UI | Dependency Value | Risk Level | Recommended Approach |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `mod_settings` | Single large edit form with multilingual switching, file inputs for logo/favicon, and textarea-heavy configuration | Multi-section settings workspace with grouped cards, sticky save action, per-language switcher, image upload cards, and safe preview/removal flow | High | Medium | Migrate first as a dedicated modern form module; keep backend contract stable initially |
| `mod_user_group` | Tree-based group listing with add/edit/delete and permission-oriented hierarchy management | Mobile-first tree list with contextual actions, add/edit forms, and optional permission summary chips | High | High | Split into two steps: modern group tree/list first, then deeper permission UX refinements |
| `mod_attachment` | Legacy attachment picker/uploader tied to parent records, inline rename/delete/download actions | Drawer or sheet-based attachment manager with upload zone, preview cards, reorder/delete actions, and record-scoped filtering | High | High | Treat as cross-module infrastructure, not only as one isolated screen |
| `mod_attachment_mod` | Generic relation/metadata CRUD table for attachment mappings | Compact list + form workflow for mapping records, with English naming cleanup and better action clarity | Medium | Medium | Migrate after base attachment manager so relation flows can be validated against it |
| `mod_gallery` | Legacy image gallery manager with upload, reorder, inline rename/delete, and media previews | Grid-based gallery manager with drag-and-drop ordering, image preview cards, upload queue, and batch actions | High | High | Build as the visual reference for media modules and align patterns with attachment manager |
| `mod_gallery_mod` | Generic relation/metadata CRUD table for gallery mappings | Compact list + form workflow for gallery relation records, aligned with attachment_mod standards | Medium | Medium | Migrate after base gallery manager, not before |

Supporting observations from legacy templates:

- `mod_settings` is primarily an edit-centric admin module rather than a list module.
- Its active modern admin entry should use `edit` naming directly while legacy `duzenle` can remain only as a compatibility alias during transition.
- `mod_user_group` already has a tree mental model in the old UI, so the new UI should preserve hierarchy rather than flatten it.
- `mod_attachment` and `mod_gallery` are not normal CRUD modules; they act like media workspaces attached to other records.
- `mod_attachment_mod` and `mod_gallery_mod` behave more like mapping/configuration CRUD modules than end-user-facing media browsers.

## 10. Priority Roadmap

### Phase A: Refactoring Consistency

1. Remove remaining active Turkish route/action naming in migrated modules
2. Standardize `error/errors`, `status`, and English route/action usage
3. Align templates, controllers, validators, and installers

### Phase B: Complete Critical Module Migration

1. `mod_video`
2. `mod_video_embed_category`
3. `mod_attachment`
4. `mod_gallery`

### Phase B.1: Legacy Module Queue Definition

The remaining legacy-only modules should be queued in this order unless product priorities change:

1. `mod_settings`
2. `mod_user_group`
3. `mod_attachment`
4. `mod_attachment_mod`
5. `mod_gallery`
6. `mod_gallery_mod`
7. `mod_announcements`
8. `mod_popup`
9. `mod_slider`
10. `mod_headline`
11. `mod_document`
12. `mod_imagecategories`
13. `mod_survey`

Queue rationale:

- `mod_settings` and `mod_user_group` affect system administration directly
- `mod_attachment` and `mod_gallery` have dependency value for other modules
- content-adjacent modules such as announcements, popup, slider, and headline are good mid-tier migration targets
- lower-frequency or specialized modules can remain in later phases

### Phase B.1.a: First-Wave Execution Order

Inside the first wave, the recommended execution order is:

1. `mod_settings`
2. `mod_user_group`
3. `mod_attachment`
4. `mod_attachment_mod`
5. `mod_gallery`
6. `mod_gallery_mod`

Execution rationale:

- `mod_settings` gives a relatively self-contained modern form win with direct admin value
- `mod_user_group` is operationally critical for administration and permissions
- `mod_attachment` should be established before its relation-management module
- `mod_gallery` should be established before `mod_gallery_mod`

### Phase B.1.b: First-Wave Deliverables

For each of the six first-wave modules, the minimum acceptable migration output is:

1. direct use of `templates/admin/layout.twig`
2. English route and action naming plan documented
3. localized labels for TR/EN/KU
4. modern mobile-first list or form UI
5. no blind copy of old Twig structure
6. changelog entry in `VERSION.md` when shipped

### Phase B.2: Legacy Migration Rule

For every module still in `templates/admin_old/`, the order of work must be:

1. Add it to the master plan with explicit status
2. Define its migration target and UI approach
3. Start implementation in `templates/admin/`
4. Verify behavior against backend/controller contracts
5. Only then treat it as an active migrated module

### Phase C: Polish and Cleanup

1. Dark mode toggle completion
2. JS organization and optimization
3. Removal of obsolete admin assets
4. Final decommissioning of `templates/admin_old`

## 11. Verification Checklist

### Automated Checks

- Search for remaining legacy action names in active modules
- Validate PHP syntax
- Validate Twig rendering
- Confirm localized UI bootstrap objects are present for module JS

### Manual Checks

- Login and navigation flow
- Mobile list usability
- Toolbar actions
- Sort / filter synchronization
- Publish / unpublish behavior
- Upload fields using `image`
- Permission flow using `permissions`

## 12. Known Gaps

The following are still intentionally acknowledged as incomplete:

- Some active modules still use old endpoints like `ekle`, `duzenle`, or `tasi`
- Some templates still use `hata` / `hatalar`
- Some hardcoded strings remain in active Twig or JS code
- `VERSION.md` had fallen behind the actual codebase and is being re-synchronized from this point forward

## 13. Completion Definition

The admin modernization can be considered substantially complete when all of the following are true:

- All critical admin modules use the modern layout directly
- No actively used admin module depends on the legacy adapter for primary UI
- English naming standards are consistently applied across routes, controllers, templates, validators, and permissions
- Hardcoded interface text is minimized and localized
- `templates/admin_old/` is no longer required in production
