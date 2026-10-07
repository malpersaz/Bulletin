#!/usr/bin/env python3
"""
PostToolUse Hook for AF CMS Synchronizer (AF/Rojname)
Runs post-execution deterministic validation on modified files:
1. Validates PHP syntax via `php -l` on modified .php files.
2. Validates code separation via `tools/code_separation_guard.py`.
3. Validates module boundary compliance via `tools/module_boundary_guard.py`.
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

def get_modified_files(repo_root):
    try:
        res = subprocess.run(
            ['git', 'status', '--porcelain'],
            cwd=repo_root,
            capture_output=True,
            text=True,
            check=False
        )
        files = []
        if res.returncode == 0 and res.stdout:
            for line in res.stdout.splitlines():
                if len(line) > 3:
                    files.append(line[3:].strip())
        return files
    except Exception:
        return []

def main():
    repo_root = find_repo_root()
    modified_files = get_modified_files(repo_root)

    # 1. Check PHP syntax for modified PHP files
    for rel_path in modified_files:
        if rel_path.endswith('.php'):
            full_path = os.path.join(repo_root, rel_path)
            if os.path.exists(full_path):
                lint_res = subprocess.run(
                    ['php', '-l', full_path],
                    capture_output=True,
                    text=True,
                    check=False
                )
                if lint_res.returncode != 0:
                    sys.stderr.write(f"PHP SYNTAX ERROR in {rel_path}:\n{lint_res.stderr or lint_res.stdout}\n")
                    sys.exit(1)

    # 2. Check module boundary on modified files
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
            sys.stderr.write(f"MODULE BOUNDARY VIOLATION:\n{b_res.stdout}\n")
            sys.exit(1)

    # 3. Check code separation on modified templates
    sep_tool = os.path.join(repo_root, 'tools', 'code_separation_guard.py')
    if os.path.exists(sep_tool):
        target_templates = [
            f for f in modified_files
            if os.path.splitext(f)[1].lower() in ('.twig', '.html', '.php')
            and not f.startswith(('docs/', 'tools/', '.agents/'))
        ]
        for t_file in target_templates:
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
                    sys.stderr.write(f"CODE SEPARATION VIOLATION in {t_file}:\n{sep_res.stdout}\n")
                    sys.exit(1)

    print(json.dumps({}))

if __name__ == "__main__":
    main()
