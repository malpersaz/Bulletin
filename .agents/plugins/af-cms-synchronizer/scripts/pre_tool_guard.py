#!/usr/bin/env python3
"""
PreToolUse Hook for AF CMS Synchronizer (AF/Rojname)
Intercepts tool execution before it happens to deterministically prevent:
1. Writing to or creating files in forbidden modules (commercial, assessment, or foreign modules).
2. Violations of AGENTS.md Rule 2 (Strict Code Separation: inline CSS/JS).
3. Dangerous cross-repo side effects.
"""

import sys
import json
import os
import re

FORBIDDEN_MODULES = [
    'mod_content',
    'mod_content_category',
    'mod_product',
    'mod_product_category',
    'mod_service',
    'mod_service_category',
    'mod_client',
    'mod_reference',
    'mod_project',
    'mod_project_category',
    'mod_appointment',
    'mod_reservation',
    'mod_hr',
    'mod_organization_type',
    'mod_test',
    'mod_assessment',
    'mod_category',
    'mod_hero_slider',
]

# Patterns for inline code violations (AGENTS.md Rule 2)
RE_STYLE_TAG = re.compile(r'<style\b[^>]*>', re.IGNORECASE)
RE_INLINE_STYLE_ATTR = re.compile(r'\bstyle\s*=\s*["\'][^"\']*["\']', re.IGNORECASE)
RE_INLINE_EVENT = re.compile(r'\bon[a-zA-Z]+\s*=\s*["\'][^"\']*["\']', re.IGNORECASE)

def find_repo_root():
    curr = os.path.abspath(os.path.dirname(__file__))
    while curr != os.path.dirname(curr):
        if os.path.exists(os.path.join(curr, '.git')):
            return curr
        curr = os.path.dirname(curr)
    return os.path.abspath(os.path.join(os.path.dirname(__file__), '../../../..'))

ALLOWED_FOREIGN_NAMED_PATHS = {
    'mod/index/helper/mod_hero_slider.helper.php',
}

def check_file_path(file_path):
    norm_path = file_path.replace('\\', '/')
    for allowed in ALLOWED_FOREIGN_NAMED_PATHS:
        if norm_path.endswith(allowed):
            return None
    parts = norm_path.split('/')
    base = os.path.basename(norm_path)

    # Check against forbidden modules
    for mod in FORBIDDEN_MODULES:
        if mod in parts:
            return f"Target file '{file_path}' belongs to forbidden module '{mod}' in AF/Rojname."
        if mod in base and not base.endswith(('.md', '.py', '.json')):
            return f"Target file '{file_path}' carries forbidden module token '{mod}' in AF/Rojname."
    return None

def check_code_content(file_path, content):
    ext = os.path.splitext(file_path)[1].lower()
    if ext not in ('.twig', '.html', '.php'):
        return None

    # Check for <style> tags
    if RE_STYLE_TAG.search(content):
        return "Strict Code Separation Violation: Inline <style> tags are forbidden in templates and PHP files."

    # Check for inline style attributes
    if RE_INLINE_STYLE_ATTR.search(content):
        return "Strict Code Separation Violation: Inline style='...' attributes are forbidden in templates and PHP files."

    # Check for inline event handlers (onclick, onchange, etc.)
    if RE_INLINE_EVENT.search(content):
        return "Strict Code Separation Violation: Inline JavaScript event handlers (e.g. onclick=) are forbidden."

    return None

def main():
    try:
        payload = json.load(sys.stdin)
    except Exception as e:
        print(json.dumps({"decision": "allow"}))
        return

    tool_call = payload.get("toolCall", {})
    tool_name = tool_call.get("name", "")
    args = tool_call.get("args", {})

    # Intercept file write / replace tools
    if tool_name in ("write_to_file", "replace_file_content"):
        target_file = args.get("TargetFile", "")
        if target_file:
            # 1. Module boundary check
            path_err = check_file_path(target_file)
            if path_err:
                print(json.dumps({
                    "decision": "deny",
                    "reason": f"SECURITY GATE BLOCK: {path_err}"
                }))
                return

            # 2. Code separation check
            content = args.get("CodeContent") or args.get("ReplacementContent") or ""
            if content:
                content_err = check_code_content(target_file, content)
                if content_err:
                    print(json.dumps({
                        "decision": "deny",
                        "reason": f"SECURITY GATE BLOCK: {content_err}"
                    }))
                    return

    # Default: allow tool execution
    print(json.dumps({"decision": "allow"}))

if __name__ == "__main__":
    main()
