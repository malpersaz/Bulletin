# User Panel Synchronization and Modernization Plan (v1.0.55)

This document outlines the research findings and implementation plan for synchronizing the admin user panel (`mod_user/panel`) of **AKM Rojname CMS** with the modernized phone-first architecture of **AKM Business CMS**.

## Research Findings

1.  **Framework Twin Differences**:
    *   **AKM Business CMS** has modules tailored for corporate sites (e.g., `mod_product`, `mod_service`, `mod_reference`, `mod_client`).
    *   **AKM Rojname CMS** is tailored for news portals, TVs, and publications, using modules like `mod_content`, `mod_news`, `mod_publication`, `mod_video_embed`, and `mod_slider`.
    *   The user panel action cards in both projects are customized to display only their respective modules based on user permissions.

2.  **Modern User Panel Structure (Business CMS)**:
    *   **Primary Navigation Bar (`panel-primary-nav`)**: A sticky/blur-based top nav bar containing a "Media" workspace link and a "More" button.
    *   **Quick Actions Grid**: Card elements grouped under a section. Each card has a `card-shell` wrapper with a `pin-toggle` button allowing users to pin/unpin individual modules.
    *   **"More Tools" Bottom Sheet (`panel-more-sheet`)**: Triggers an overlay sheet on phone screens showing less frequent administration routes (like ACL and User Management).
    *   **Pinning & History JS**: `script.js` contains a robust client-side pinning engine using `window.localStorage`. It stores pinned items, recent items, and tracks click interactions to dynamically render chips.

3.  **Gaps in AKM Rojname CMS**:
    *   Rojname's `panel.twig` still uses the old layout without the primary top navigation, pinning toggles, or the "More Tools" bottom sheet.
    *   Rojname's `script.js` does not contain the pinning, recent workspace, or navigation label registry helper functions.
    *   Missing language keys under `mod_user` and `management` for the nav and sheet labels.

---

## Proposed Synchronization Approach

We will synchronize the user panel layout and JS features while maintaining the Rojname-specific quick action cards.

### 1. Language Keys Integration
We will add the following keys to `mod/mod_user/lang/` and `mod/management/lang/` across all active language files (`en`, `ku`, `tr`, `de`, `fr`, `zza`, `ckb`, `ar`, `sv`):

**mod_user/panel**:
*   `recent_workspace`
*   `workspace_nav_media`
*   `workspace_nav_more`
*   `more_sheet_title`
*   `more_sheet_copy`
*   `more_sheet_open_drawer`

**management**:
*   `system_tools`
*   `recent_modules`
*   `pinned_modules`
*   `module_search_placeholder`
*   `pin_module`
*   `unpin_module`
*   `recent_workspace_empty`

### 2. Layout & Style Updates (`panel.twig`)
Modify `templates/admin/mod_user/panel.twig`:
*   Add MD3 styles for `.panel-primary-nav`, `.panel-chip-row`, `.panel-chip`, `.pin-toggle`, and the bottom sheet.
*   Implement `panel-primary-nav` containing the media link and the "More" button.
*   Add Pinned Modules section (`panelPinnedModules`) and Recent Workspace section (`panelRecentWorkspaceSection`).
*   Wrap action cards inside `card-shell` with a `pin-toggle` button.
*   Implement `panel-more-sheet` with System Tools (ACL) and Membership links.

### 3. Pinning & History Engine (`script.js`)
Append the following JS logic to `templates/admin/assets/js/script.js`:
*   `isForegroundModuleItemAllowed(item)`
*   `normalizeNavigationHref(href)`
*   `normalizeStoredNavigationItems(items)`
*   `getPinnedModules()`, `setPinnedModules(items)`, `ensurePinnedModulesSeeded()`
*   `buildPinnedAnchor(item, className)`, `togglePinnedModule(button)`, `renderPinnedModules()`
*   `rememberRecentDrawerLink(link)`, `renderPanelRecentModules()`, `personalizePanelQuickAccess()`
*   `initializeDrawerNavigation()` execution.

### 4. Controller Assertion (`index.php`)
Implement the language key assertion in `mod/mod_user/index.php`:
*   Add `_assert_panel_language_keys()` to verify that all required panel/nav keys are present.
*   Call it inside the `panel()` method to prevent rendering blank UI elements.

---

## Verification Plan

### Automated/Manual Checks
1.  **Visual Parity Check**: Navigate to `/ku/mod_user/panel` and verify the phone-first layout matches the Business CMS design (nav bar, cards, bottom sheet, bottom-scrim).
2.  **Pinning Mechanism**: Tap the pin icon on "News Management" card, verify it appears in the "Pinned" section and persists after page refresh.
3.  **Recent Activities**: Tap an action card (e.g. Content), navigate back, and verify it is listed in the "Recent Workspace" section.
4.  **More Tools Sheet**: Tap the "More" button on the top nav, verify the bottom sheet opens showing ACL/membership links and that they navigate correctly.
