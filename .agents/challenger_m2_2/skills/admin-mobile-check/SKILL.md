---
name: admin-mobile-check
description: Audit and validate admin templates and assets to ensure they conform to the phone-only mobile layout contract.
---

# Admin Mobile Layout Check Skill

## Operating Rule
Keep this skill as a short ordered operation checklist. Do not load large design guides unless verifying a specific component layout profile.

## Sequence
1. Identify all created or modified files under `templates/admin/`, `templates/admin/assets/`, or touched admin module views/styles.
2. Audit layout assumptions: reject wide fixed-width columns; ensure mobile single vertical column collapse.
3. Verify touch interaction safety: tap targets min 44x44px, proper padding.
4. Audit vertical space usage: primary actions visible, no huge banners.
5. Validate viewport responsiveness on narrow mobile viewports.
6. Report policy violations.
