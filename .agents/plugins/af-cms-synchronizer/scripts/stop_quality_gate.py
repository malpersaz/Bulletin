#!/usr/bin/env python3
"""
Stop Hook for AF CMS Synchronizer (AF/Bulletin)
Runs before the agent terminates its loop to ensure zero unresolved quality or boundary violations.
If violations exist, it forces the agent to continue and resolve them.
"""

import sys
import json
import os
import subprocess

def find_repo_root():
    curr = os.path.abspath(os.path.dirname(__file__))
    while curr != os.path.dirname(curr):
        if os.path.exists(os.path.join(curr, '.git')):
            return curr
        curr = os.path.dirname(curr)
    return os.path.abspath(os.path.join(os.path.dirname(__file__), '../../../..'))

def main():
    repo_root = find_repo_root()
    violations = []

    # 1. Check module boundary guard
    boundary_tool = os.path.join(repo_root, 'tools', 'module_boundary_guard.py')
    if os.path.exists(boundary_tool):
        b_res = subprocess.run(
            [sys.executable, boundary_tool],
            cwd=repo_root,
            capture_output=True,
            text=True,
            check=False
        )
        if b_res.returncode != 0:
            violations.append(f"Module Boundary Violations:\n{b_res.stdout.strip()}")

    # 2. Check code separation guard
    sep_tool = os.path.join(repo_root, 'tools', 'code_separation_guard.py')
    if os.path.exists(sep_tool):
        status_res = subprocess.run(
            ["git", "status", "--porcelain"],
            cwd=repo_root,
            capture_output=True,
            text=True,
            check=False
        )
        modified_templates = []
        if status_res.returncode == 0 and status_res.stdout:
            for s_line in status_res.stdout.splitlines():
                if len(s_line) > 3:
                    fp = s_line[3:].strip()
                    if fp.endswith((".twig", ".html")) and not fp.startswith(("docs/", "tools/", ".agents/")):
                        modified_templates.append(fp)
        for t_file in modified_templates:
            full_t = os.path.join(repo_root, t_file)
            if os.path.exists(full_t):
                sep_res = subprocess.run(
                    [sys.executable, sep_tool, "--path", t_file],
                    cwd=repo_root,
                    capture_output=True,
                    text=True,
                    check=False
                )
                if sep_res.returncode != 0:
                    violations.append(f"Code Separation Violations in {t_file}:\n{sep_res.stdout.strip()}")

    if violations:
        print(json.dumps({
            "decision": "continue",
            "reason": "STOP REJECTED: You cannot stop while quality violations exist:\n" + "\n---\n".join(violations)
        }))
    else:
        print(json.dumps({
            "decision": "allow"
        }))

if __name__ == "__main__":
    main()
