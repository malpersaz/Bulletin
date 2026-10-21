#!/usr/bin/env python3
"""
code_separation_guard.py - Automated Code Separation Guard for Amed Framework (AF/Bulletin)

Purpose:
  Enforces AGENTS.md Rule 2 (Strict Code Separation Mandate):
  - Catches inline CSS (`style="..."` attributes and `<style>` blocks)
  - Catches inline JavaScript event handlers (`onclick=`, `onchange=`, `onsubmit=`, etc.)
  - Verifies that styling and behavioral logic remain cleanly separated into .css and .js files.

Usage:
  python3 tools/code_separation_guard.py               # Audits git modified / staged files
  python3 tools/code_separation_guard.py --all         # Audits all templates and views
  python3 tools/code_separation_guard.py --path path/  # Audits specific file or directory
  python3 tools/code_separation_guard.py --json        # Returns machine-readable JSON output
"""

import os
import sys
import re
import argparse
import subprocess
from typing import List, Dict, Tuple

REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))

# Patterns for violations
RE_INLINE_STYLE_ATTR = re.compile(r'\bstyle\s*=\s*["\']([^"\']*)["\']', re.IGNORECASE)
RE_STYLE_TAG = re.compile(r'<style\b[^>]*>', re.IGNORECASE)
RE_INLINE_EVENT_HANDLER = re.compile(r'\bon[a-zA-Z]+\s*=\s*["\'][^"\']*["\']', re.IGNORECASE)

# File extensions to scan
AUDIT_EXTENSIONS = {".twig", ".html", ".php"}

# Directories to exclude from scan
EXCLUDE_DIRS = {
    ".git", ".gemini", ".agents", "vendor", "node_modules", "docs", "cache", "data", "static/assets/vendor", "lib"
}


class CodeSeparationGuard:
    def __init__(self, root_dir: str):
        self.root_dir = root_dir

    def audit_file(self, file_path: str) -> List[Dict[str, any]]:
        """Audits a single file for code separation violations."""
        violations = []
        rel_path = os.path.relpath(file_path, self.root_dir)

        try:
            with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
                lines = f.readlines()
        except Exception as e:
            return [{"file": rel_path, "line": 0, "type": "READ_ERROR", "message": str(e), "snippet": ""}]

        for idx, line in enumerate(lines, start=1):
            stripped = line.strip()

            # Ignore comments
            if stripped.startswith("{#") or stripped.startswith("<!--") or stripped.startswith("//") or stripped.startswith("*"):
                continue

            # Check for <style> tags
            if RE_STYLE_TAG.search(line):
                violations.append({
                    "file": rel_path,
                    "line": idx,
                    "type": "INLINE_STYLE_TAG",
                    "message": "Forbidden <style> tag in template/code file. Move styles to an external CSS stylesheet.",
                    "snippet": stripped
                })

            # Check for style="..." attributes
            style_match = RE_INLINE_STYLE_ATTR.search(line)
            if style_match:
                violations.append({
                    "file": rel_path,
                    "line": idx,
                    "type": "INLINE_STYLE_ATTR",
                    "message": f"Forbidden inline style attribute: '{style_match.group(0)}'. Move styling to CSS classes.",
                    "snippet": stripped
                })

            # Check for inline event handlers (e.g. onclick, onchange)
            # Exempt standard Google/Lighthouse async CSS print-swap pattern on <link> tags
            if "<link " in line and ("this.media=" in line or "this.onload=null" in line):
                event_match = None
            else:
                event_match = RE_INLINE_EVENT_HANDLER.search(line)

            if event_match:
                violations.append({
                    "file": rel_path,
                    "line": idx,
                    "type": "INLINE_EVENT_HANDLER",
                    "message": f"Forbidden inline event handler: '{event_match.group(0)}'. Bind events in external JS files.",
                    "snippet": stripped
                })

        return violations

    def get_git_modified_files(self) -> List[str]:
        """Gets modified and staged files from git."""
        try:
            out = subprocess.check_output(
                ["git", "status", "--porcelain"],
                cwd=self.root_dir,
                universal_newlines=True
            )
            files = []
            for line in out.splitlines():
                if len(line) > 3:
                    file_path = line[3:].strip()
                    # Handle renames
                    if " -> " in file_path:
                        file_path = file_path.split(" -> ")[1].strip()
                    full_path = os.path.join(self.root_dir, file_path)
                    parts = set(os.path.normpath(file_path).split(os.sep))
                    if parts.intersection(EXCLUDE_DIRS):
                        continue
                    ext = os.path.splitext(full_path)[1].lower()
                    if ext in AUDIT_EXTENSIONS and os.path.isfile(full_path):
                        files.append(full_path)
            return files
        except Exception:
            return []

    def get_all_target_files(self, base_path: str = None) -> List[str]:
        """Scans for all relevant files under base_path or templates directory."""
        search_root = base_path if base_path else self.root_dir
        target_files = []

        for root, dirs, files in os.walk(search_root):
            # Exclude directories
            dirs[:] = [d for d in dirs if d not in EXCLUDE_DIRS and not d.startswith(".")]

            for f in files:
                ext = os.path.splitext(f)[1].lower()
                if ext in AUDIT_EXTENSIONS:
                    target_files.append(os.path.join(root, f))

        return target_files


def main():
    parser = argparse.ArgumentParser(description="Code Separation Guard for Amed Framework")
    parser.add_argument("--path", type=str, help="Specific file or directory path to audit")
    parser.add_argument("--all", action="store_true", help="Audit all templates across repository")
    parser.add_argument("--json", action="store_true", help="Output results in JSON format")

    args = parser.parse_args()
    guard = CodeSeparationGuard(REPO_ROOT)

    if args.path:
        target_path = os.path.abspath(os.path.join(REPO_ROOT, args.path))
        if os.path.isfile(target_path):
            files_to_audit = [target_path]
        else:
            files_to_audit = guard.get_all_target_files(target_path)
    elif args.all:
        files_to_audit = guard.get_all_target_files()
    else:
        files_to_audit = guard.get_git_modified_files()
        if not files_to_audit:
            # Fallback to scanning templates/admin if git has no changes
            files_to_audit = guard.get_all_target_files(os.path.join(REPO_ROOT, "templates/admin"))

    all_violations = []
    for f in files_to_audit:
        all_violations.extend(guard.audit_file(f))

    if args.json:
        import json
        print(json.dumps({"total_violations": len(all_violations), "violations": all_violations}, indent=2))
        sys.exit(1 if all_violations else 0)

    if not all_violations:
        print(f"\033[92m[PASS] Code Separation Guard: 0 violations found across {len(files_to_audit)} files.\033[0m")
        sys.exit(0)
    else:
        print(f"\033[91m[FAIL] Code Separation Guard: Found {len(all_violations)} violation(s) across {len(files_to_audit)} files:\033[0m\n")
        for v in all_violations:
            print(f"  \033[93m{v['file']}:{v['line']}\033[0m - [{v['type']}] {v['message']}")
            print(f"    Snippet: \033[90m{v['snippet']}\033[0m\n")
        sys.exit(1)


if __name__ == "__main__":
    main()
