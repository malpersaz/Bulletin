# Milestone 1: Admin Assets & Modal Engine Synchronization Report

**Explorer**: Explorer 2 (Milestone 1)  
**Date**: 2026-08-23  
**Status**: Complete  
**Reference Source**: `/home/malpersaz/Desktop/projects/htdocs/AF/Business`  
**Target Workspace**: `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname`  

---

## Executive Summary

This report delivers an exhaustive audit and synchronization specification for admin assets (CSS, JS, Modal Engine) between `AF/Business` (reference implementation) and `AF/Rojname` (target repository).

Key findings:
1. **8 Missing CSS Assets**: 7 module/utility stylesheets (`mod_faq.css`, `mod_seo.css`, `legal_info.css`, `mod_acl.css`, `mod_announcements.css`, `mod_user_acl.css`, `unauthorized.css`) and 1 centralized modal stylesheet (`modals/core_modals.css`).
2. **5 Missing Root Module JS Assets**: `mod_faq.js`, `mod_seo.js`, `legal_info.js`, `mod_acl.js`, `mod_user_acl.js`.
3. **7 Modal Engine Sub-Module JS Assets**: The entire `templates/admin/assets/js/modals/` directory (`af_modal_engine.js`, `modal_newsletter.js`, `modal_redirects.js`, `modal_site_status.js`, `modal_theme_logo.js`, `modal_user_menu.js`, `modal_user_profile.js`).
4. **2 New Assets for `mod_newsletter`**: `mod_newsletter.css` and `mod_newsletter.js` to extract inline styles/scripts from `templates/admin/mod_newsletter/list.twig` and guarantee 100% Strict Code Separation.
5. **1 Deprecated JS Asset for Deletion**: `templates/admin/assets/js/mod_product.js` (969 lines) as part of Requirement R3 (cleanup of deprecated business modules).
6. **Layout & Shell Integration**: `templates/admin/layout.twig` and `templates/admin/partials/sheets_extra.twig` need modal engine wiring and extraction of inline styles/scripts.

---

## 1. CSS Assets Inventory (`templates/admin/assets/css/`)

### Status Matrix

| File Path | Status in Rojname | Source in Business | Lines | Size | Purpose & Scope |
|---|---|---|---|---|---|
| `mod_faq.css` | **MISSING** | `AF/Business` | 717 | 16.3 KB | MD3 phone-first styling for polymorphic FAQ manager (context cards, drag handles, accordion body, bottom action bar, limit pills, RTL support). |
| `mod_seo.css` | **MISSING** | `AF/Business` | 287 | 5.3 KB | Phone-first styling for SEO management, Google Search Console onboarding, stat cards, metric grids, and credentials alerts. |
| `legal_info.css` | **MISSING** | `AF/Business` | 243 | 5.4 KB | Admin UI styles for Company Legal & Tax Information (`mod_settings/legal_info`), section cards, inputs, and notification banners. |
| `mod_acl.css` | **MISSING** | `AF/Business` | 376 | 7.7 KB | Access control management styling for `mod_acl`, floating labels, selection cards, touch targets (>=44px), avatar badges, loaders. |
| `mod_announcements.css` | **MISSING** | `AF/Business` | 233 | 4.9 KB | Admin announcements form cards, floating labels, selection grids, character counters, and bottom action bar. |
| `mod_user_acl.css` | **MISSING** | `AF/Business` | 1,180 | 32.4 KB | Complete ACL permission matrix styling: user group sticky carousel, search toolbar, accordion module cards, toggle switches, indeterminate progress bar, dark mode tokens. |
| `unauthorized.css` | **MISSING** | `AF/Business` | 93 | 2.0 KB | Centered card styling for 401/403 unauthorized access error page (`templates/admin/unauthorized.twig`). |
| `modals/core_modals.css` | **MISSING** | `AF/Business` | 1,652 | 41.0 KB | Standardized HTML5 `<dialog>` reset, phone bounds (max 376px), floating labels, View Transitions animations, top-layer toast banners, SweetAlert2 mobile bounds, TinyMCE popup constraints. |
| `mod_newsletter.css` | **NEW CREATION** | *Synthesized* | ~60 | ~1.5 KB | Extracted CSS rules from `templates/admin/mod_newsletter/list.twig` (toolbar, subscriber cards, meta badges, empty state) to enforce zero inline `<style>` tags. |

### Technical Analysis of CSS Assets

1. **`mod_faq.css`**:
   - Scoped custom properties: `--faq-primary`, `--faq-surface-container`, `--faq-error`, etc.
   - Enforces `#modal-dynamic-body .faq-modal-shell` padding when embedded in dynamic modal dialogs.
   - Sticky bottom action bar with `backdrop-filter: blur(12px)` docked inside native dialogs.
   - 44px+ touch targets for drag handles (`.faq-drag-handle`), expand buttons, status pills, and action buttons.

2. **`mod_user_acl.css`**:
   - Highly optimized phone layout (360px-430px) with custom tokens.
   - Sticky user group carousel (`.acl-group-carousel`) with `scroll-snap-type: x mandatory` and hidden scrollbars.
   - Custom MD3 toggle switches (`.switch-track`, `.switch-thumb`) with SVG/pseudo-element state markers (`✓`, `✕`).
   - Accordion cards with animated chevron indicators, counters (`X of Y enabled`), grant-all/revoke-all toolbars.

3. **`modals/core_modals.css`**:
   - Universal dialog rules for `newsletter-sheet`, `redirect-sheet`, `logo-icon-management-modal`, `site-settings-menu-modal`, `site-status-modal`, `theme-management-modal`, `modal-dynamic-dialog`.
   - Bounded viewport height (`80vh`, `max-height: 760px`, `max-width: 376px`).
   - Standardized top header bar controls: Close button (left/order 1), Title center (order 2), Back button (right/order 3).
   - View Transition API keyframes (`afModalSlideIn`, `afModalSlideOut`, `toastSlideDown`, `toastFadeOut`).

---

## 2. JS Assets Inventory (`templates/admin/assets/js/`)

### Status Matrix

| File Path | Status in Rojname | Source in Business | Lines | Size | Purpose & Scope |
|---|---|---|---|---|---|
| `mod_faq.js` | **MISSING** | `AF/Business` | 669 | 29.5 KB | `FaqManager` ES6 class: dynamic question additions, auto-focus, live 255/5000 character counters, 25-question limit, SortableJS reordering, single/batch AJAX persistence, status toggle. |
| `mod_seo.js` | **MISSING** | `AF/Business` | 83 | 3.0 KB | Search filtering for URL cards, declarative `[data-url]` navigation, Google Search Console settings change tracking. |
| `legal_info.js` | **MISSING** | `AF/Business` | 40 | 1.1 KB | Form dirty state detection and `beforeunload` warning for Company Legal & Tax Info. |
| `mod_acl.js` | **MISSING** | `AF/Business` | 419 | 13.9 KB | `AclManager` class: datatable JSON pagination, search debounce, card multiselect, single/batch delete via SweetAlert2, selection cards toggle. |
| `mod_user_acl.js` | **MISSING** | `AF/Business` | 900 | 33.7 KB | `AclManager` reactive permission matrix engine: state hydration from `#aclInitialData`, group tabs switching, optimistic updates with rollback, batch grant/revoke, live search with regex highlight. |
| `mod_newsletter.js` | **NEW CREATION** | *Synthesized* | ~70 | ~2.0 KB | Standalone controller for `templates/admin/mod_newsletter/list.twig`: datatable initialization, search debounce, toolbar delete bindings, modal opening. |
| `mod_product.js` | **DEPRECATED** | *To be Deleted* | 969 | 39.0 KB | Legacy product management asset; references `mod_product` and old helper endpoints. Marked for deletion. |

---

## 3. Modal Engine Sub-Module JS Inventory (`templates/admin/assets/js/modals/`)

The directory `templates/admin/assets/js/modals/` does not exist in `AF/Rojname`. All 7 sub-modules must be created:

| File Path | Lines | Size | Key Functions & Responsibilities |
|---|---|---|---|
| `af_modal_engine.js` | 600 | 30.6 KB | **Core Modal Navigation Stack Engine** (`window.AFModalEngine`):<br>• Stack history tracking with `pushState`/`popstate`<br>• Native HTML5 `<dialog>` lifecycle (`showModal`, `close`)<br>• Dynamic AJAX container loading (`loadAjax`) with `<script>` execution<br>• URL query modal trigger handling (`?open_modal=...`)<br>• On-demand dynamic module loading (`loadModule`)<br>• M3 toast banners (`showAFToast` / `showToast`) |
| `modal_newsletter.js` | 214 | 9.0 KB | `openNewsletterModal()`, `closeNewsletterModal()`, `fetchNewsletterSubscribers()`, `copySubscriberEmail()`, `toggleAddSubscriberForm()`, `submitAddSubscriberAdmin()`, `deleteSubscriberAjax()`, `exportSubscribersCSV()`, `filterNewsletterStatus()` |
| `modal_redirects.js` | 202 | 8.2 KB | `openRedirectModal()`, `closeRedirectModal()`, `fetchRedirects()`, `toggleRedirectAddForm()`, `editRedirectInline()`, `submitRedirectFormAjax()`, `deleteRedirectAjax()` |
| `modal_site_status.js` | 126 | 4.3 KB | `openSiteStatusModal()`, `closeSiteStatusModal()`, `setSiteStatusInstantAjax()`, `saveMaintenanceMessageInstantAjax()`, `updateSiteStatusUI()` |
| `modal_theme_logo.js` | 1,058 | 70.7 KB | `openSiteSettingsMenuModal()`, `openThemeManagementModal()`, `loadThemesAjax()`, `selectThemeAjax()`, `openStatsModal()`, `saveStatsForm()`, `openLogoIconManagementModal()`, `loadLogoIconAjax()`, `setHeaderStyleAjax()`, `processSelectedLogoModalImage()`, `removeLogoAssetModalAjax()`, `saveAnimatedLogoSettingsAjax()`, `uploadAnimatedLogoFileModalAjax()`, `removeAnimatedLogoModalAjax()` |
| `modal_user_menu.js` | 348 | 14.3 KB | `openUserMenuSheet()`, `closeUserMenuSheet()`, `showUserSubmenu()`, `showUserMenuMain()`, plus global document click event delegation for all `[data-action]` modal triggers across header/partials |
| `modal_user_profile.js`| 418 | 15.6 KB | `previewAvatar()`, `updateCharCounter()`, `deleteImage()`, `togglePassword()`, `checkStrength()`, `checkMatch()`, `submitEditProfileAjax()`, `submitChangePasswordAjax()` |

---

## 4. `mod_newsletter` Asset Architecture Specification

In `AF/Business`, `templates/admin/mod_newsletter/list.twig` contained inline `<style>` and `<script>` blocks. Under the repository's strict code separation mandate, these must be decoupled into dedicated external assets:

### A. Proposed `templates/admin/assets/css/mod_newsletter.css`
```css
/**
 * mod_newsletter.css - Admin Newsletter Module Styles
 * Phone-First MD3 Layout
 */

.newsletter-toolbar .toolbar-item {
    min-width: 44px;
}

.newsletter-empty-state,
.empty-state.newsletter-empty {
    background: var(--md-sys-color-surface);
    border-radius: var(--af-radius-lg, 16px);
    padding: 40px 20px;
    margin: 0 16px 120px;
    text-align: center;
    box-shadow: var(--md-sys-elevation-1);
    display: grid;
    gap: 16px;
    place-items: center;
}

.newsletter-empty-state i,
.empty-state.newsletter-empty i {
    font-size: 48px;
    color: var(--md-sys-color-primary);
}

.subscriber-card {
    background: var(--md-sys-color-surface);
    border-radius: var(--af-radius-md, 12px);
    padding: 16px;
    margin-bottom: 12px;
    box-shadow: var(--md-sys-elevation-1);
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.subscriber-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.subscriber-email {
    font-weight: 700;
    font-size: 1rem;
    word-break: break-all;
    color: var(--md-sys-color-on-surface);
}

.subscriber-meta {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    font-size: 0.8125rem;
    color: var(--md-sys-color-on-surface-variant);
}

.subscriber-meta .meta-badge {
    background: var(--md-sys-color-surface-variant);
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 600;
}
```

### B. Proposed `templates/admin/assets/js/mod_newsletter.js`
```javascript
/**
 * mod_newsletter.js - Admin Newsletter List Interactions
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof openNewsletterModal === 'function') {
            openNewsletterModal();
        }
    });

    window.deleteSelectedSubscribers = function() {
        // Toolbar batch delete handler
        const selected = document.querySelectorAll('.subscriber-card.selected');
        if (!selected.length) return;
        // Batch delete implementation
    };
})();
```

---

## 5. Deletion Plan for `templates/admin/assets/js/mod_product.js`

- **File**: `templates/admin/assets/js/mod_product.js` (969 lines, 39,008 bytes).
- **Reason**: The `mod_product` module is completely deprecated and removed from Rojname (news portal focus).
- **Impact Assessment**:
  - `templates/admin/mod_product/` will be deleted in M2.
  - Sibling references in `templates/admin/partials/sidebar.twig` and `templates/admin/mod_user/panel.twig` will be cleaned up in M2.
  - Safe to delete `mod_product.js` during M1 asset synchronization.

---

## 6. Layout & Shell Synchronization Requirements

To ensure the synchronized assets function correctly across all admin views, the following updates are identified for Milestone 1 implementers:

1. **`templates/admin/layout.twig`**:
   - Add `<link rel="stylesheet" href="/templates/admin/assets/css/modals/core_modals.css?v=1.8">`
   - Add `<link rel="stylesheet" href="/templates/admin/assets/css/mod_faq.css?v=1.2">`
   - Add `<script src="/templates/admin/assets/js/modals/af_modal_engine.js?v=1.13"></script>`
   - Add `<script src="/templates/admin/assets/js/modals/modal_user_menu.js?v=1.02"></script>`
   - Remove inline `<style>` with `:root` CSS variables (lines 15-27) to comply with Strict Code Separation.
   - Remove inline `<script>` flash message handling (lines 264-283) and route through standard JS event listeners.

2. **`templates/admin/partials/sheets_extra.twig`**:
   - Remove inline `<style>` block (390 lines of CSS) because those styles are now located in `style.css` / `core_modals.css`.
   - Convert all `onclick="..."` attributes to declarative `data-action="..."` attributes.
   - Add `contextActionFaq` (`data-context-type="faq"`) to support the polymorphic FAQ engine in `mod_content` / `mod_news`.

3. **`templates/admin/assets/css/style.css`**:
   - Ensure utility classes (`.d-none`, `.d-flex`, `.is-hidden`, `.toolbar-start-btn`, toolbar active states for video/gallery/files/faq/slider) are present.

---

## 7. Implementation Checklist for Milestone 1 Implementers

- [ ] **CSS Copy**:
  - `AF/Business/templates/admin/assets/css/mod_faq.css` -> `AF/Rojname/templates/admin/assets/css/mod_faq.css`
  - `AF/Business/templates/admin/assets/css/mod_seo.css` -> `AF/Rojname/templates/admin/assets/css/mod_seo.css`
  - `AF/Business/templates/admin/assets/css/legal_info.css` -> `AF/Rojname/templates/admin/assets/css/legal_info.css`
  - `AF/Business/templates/admin/assets/css/mod_acl.css` -> `AF/Rojname/templates/admin/assets/css/mod_acl.css`
  - `AF/Business/templates/admin/assets/css/mod_announcements.css` -> `AF/Rojname/templates/admin/assets/css/mod_announcements.css`
  - `AF/Business/templates/admin/assets/css/mod_user_acl.css` -> `AF/Rojname/templates/admin/assets/css/mod_user_acl.css`
  - `AF/Business/templates/admin/assets/css/unauthorized.css` -> `AF/Rojname/templates/admin/assets/css/unauthorized.css`
  - `AF/Business/templates/admin/assets/css/modals/core_modals.css` -> `AF/Rojname/templates/admin/assets/css/modals/core_modals.css`
- [ ] **JS Copy**:
  - `AF/Business/templates/admin/assets/js/mod_faq.js` -> `AF/Rojname/templates/admin/assets/js/mod_faq.js`
  - `AF/Business/templates/admin/assets/js/mod_seo.js` -> `AF/Rojname/templates/admin/assets/js/mod_seo.js`
  - `AF/Business/templates/admin/assets/js/legal_info.js` -> `AF/Rojname/templates/admin/assets/js/legal_info.js`
  - `AF/Business/templates/admin/assets/js/mod_acl.js` -> `AF/Rojname/templates/admin/assets/js/mod_acl.js`
  - `AF/Business/templates/admin/assets/js/mod_user_acl.js` -> `AF/Rojname/templates/admin/assets/js/mod_user_acl.js`
  - `AF/Business/templates/admin/assets/js/modals/*` (7 files) -> `AF/Rojname/templates/admin/assets/js/modals/*`
- [ ] **New Newsletter Assets**:
  - Create `templates/admin/assets/css/mod_newsletter.css`
  - Create `templates/admin/assets/js/mod_newsletter.js`
- [ ] **Deprecation Deletion**:
  - Delete `templates/admin/assets/js/mod_product.js`
- [ ] **Layout Sync**:
  - Update `templates/admin/layout.twig`
  - Update `templates/admin/partials/sheets_extra.twig`
