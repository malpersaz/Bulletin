---
name: admin-mobile-check
description: Audit and validate admin templates and assets to ensure they conform to the phone-only mobile layout contract.
---

# Admin Mobile Layout Check Skill

## Operating Rule

Keep this skill as a short ordered operation checklist. Do not load large design guides unless verifying a specific component layout profile.

## Sequence

1. Identify all created or modified files under:
   - `templates/admin/`
   - `templates/admin/assets/`
   - Touched admin module controller views or styles

2. Audit layout assumptions in the touched files:
   - Reject any layout using wide, fixed-width desktop columns or grids.
   - Verify all grids collapse into a single vertical column on narrow widths (max-width: 480px/576px).
   - Ensure tables are wrapped in a horizontally scrollable container or converted to card lists on mobile.

3. Verify touch interaction safety:
   - Tap targets (buttons, links, inputs) must have a minimum size of 44x44 CSS pixels.
   - Verify proper padding around interactive elements to prevent accidental taps.

4. Audit vertical space usage:
   - Check if critical actions (e.g. submit buttons, primary form fields) are visible in the initial phone viewport.
   - Reject massive hero sections, decorative header images, or redundant banners that push primary tasks below the fold.

5. Validate viewport responsiveness:
   - Test files against the fixed device breakpoints or simulate viewport rendering using a device shell emulator.

6. Report policy violations:
   - Flag any admin layout that defaults to desktop/tablet assumptions or ignores narrow-width phone usability.
