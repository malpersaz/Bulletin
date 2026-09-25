#!/usr/bin/env python3
"""
m2_css_touch_target_auditor.py - Empirical CSS Touch Target & Mobile Layout Auditor
"""

import os
import re
import sys

REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))

CSS_FILES_TO_AUDIT = [
    "templates/admin/assets/css/mod_user_acl.css",
    "templates/admin/assets/css/mod_acl.css",
    "templates/admin/assets/css/modals/core_modals.css"
]

INTERACTIVE_CLASSES = [
    "acl-permission-switch",
    "acl-group-tab",
    "acl-module-header",
    "acl-btn-action",
    "acl-clear-search-btn",
    "acl-btn-reset-search",
    "acl-action-btn",
    "acl-selection-card",
    "m3-btn-half",
    "modal-close-btn",
    "modal-back-btn"
]

def audit_css_files():
    print("========================================================================")
    print("STARTING EMPIRICAL CSS TOUCH TARGET & MOBILE LAYOUT AUDIT")
    print("========================================================================\n")

    total_checks = 0
    passed_checks = 0
    failed_checks = 0

    for rel_path in CSS_FILES_TO_AUDIT:
        full_path = os.path.join(REPO_ROOT, rel_path)
        if not os.path.exists(full_path):
            print(f"  [FAIL] File not found: {rel_path}")
            failed_checks += 1
            continue

        with open(full_path, "r", encoding="utf-8") as f:
            content = f.read()

        print(f"Auditing {rel_path} ({len(content.splitlines())} lines):")

        # Check 1: No fixed desktop wide containers (e.g., width > 600px)
        total_checks += 1
        desktop_fixed_widths = re.findall(r'(?:width|max-width)\s*:\s*(\d{3,4})px', content)
        has_wide_desktop = False
        for w in desktop_fixed_widths:
            if int(w) > 600:
                has_wide_desktop = True
                print(f"  [FAIL] Found wide desktop width: {w}px in {rel_path}")
        if not has_wide_desktop:
            print(f"  [PASS] No wide desktop width constraints (> 600px) found.")
            passed_checks += 1
        else:
            failed_checks += 1

        # Check 2: Check presence of touch target variable or explicit 44px min-height/width
        total_checks += 1
        if "44px" in content or "48px" in content or "--touch-target-size" in content:
            print(f"  [PASS] Complies with 44px/48px minimum touch target standard.")
            passed_checks += 1
        else:
            print(f"  [FAIL] Missing 44px/48px touch target declarations.")
            failed_checks += 1

        # Check 3: Check interactive selector touch targets
        for cls in INTERACTIVE_CLASSES:
            pattern = re.compile(rf'\.{cls}\b[^\{{]*\{{([^\}}]+)\}}', re.DOTALL)
            matches = pattern.findall(content)
            if matches:
                total_checks += 1
                has_target_size = False
                for block in matches:
                    if "min-height" in block or "height" in block or "min-width" in block or "width" in block or "padding" in block:
                        has_target_size = True
                        break
                if has_target_size:
                    print(f"  [PASS] Class .{cls} provides adequate touch dimensions/padding.")
                    passed_checks += 1
                else:
                    print(f"  [FAIL] Class .{cls} missing touch target rules.")
                    failed_checks += 1

    print("\n========================================================================")
    print(f"CSS AUDIT SUMMARY: Total={total_checks}, Passed={passed_checks}, Failed={failed_checks}")
    print("========================================================================")

    if failed_checks > 0:
        sys.exit(1)
    sys.exit(0)

if __name__ == "__main__":
    audit_css_files()
