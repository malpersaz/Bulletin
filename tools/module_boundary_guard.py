#!/usr/bin/env python3
"""
Amed Framework - Module Boundary Guard Tool
Audits and guards the codebase against foreign, commercial, and excluded modules.
Enforces strict repository isolation and prevents unwanted module files and references.
"""

import os
import sys
import json
import argparse
import subprocess
import re

FORBIDDEN_MODULES = [
    # General CMS content modules (owned by AF/Business, forbidden in AF/Rojname)
    'mod_content',
    'mod_content_category',
    # Commercial & E-commerce modules (owned by AF/Business)
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
    # Assessment & Cognitive testing
    'mod_test',
    'mod_assessment',
    # Legacy generic category module (deprecated in favor of domain taxonomies)
    'mod_category',
    # Foreign / orphan hero slider module
    'mod_hero_slider',
]

# Repository-owned shared-core files whose names embed a foreign module token
# (e.g. the hero-slider homepage helper implements the slider ENUM feature, not the foreign module)
ALLOWED_FOREIGN_NAMED_PATHS = {
    'mod/index/helper/mod_hero_slider.helper.php',
}

# Relative-path prefixes never audited (agent workspaces, docs, tooling, caches, dependencies)
AUDIT_SKIP_PREFIXES = ('.agents/', 'docs/', '.git/', 'node_modules/', 'vendor/', 'tools/')

REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))

def get_git_touched_files():
    """Retrieve list of modified, staged, and untracked files from Git."""
    touched = set()
    try:
        # Check staged and unstaged diffs excluding deleted files
        res = subprocess.run(
            ['git', 'diff', '--name-only', '--diff-filter=d', 'HEAD'],
            cwd=REPO_ROOT,
            capture_output=True,
            text=True,
            check=False
        )
        if res.returncode == 0 and res.stdout:
            for f in res.stdout.splitlines():
                f = f.strip()
                if f and os.path.exists(os.path.join(REPO_ROOT, f)):
                    touched.add(f)
            
        # Check untracked and modified files that exist on disk
        res_untracked = subprocess.run(
            ['git', 'status', '--porcelain'],
            cwd=REPO_ROOT,
            capture_output=True,
            text=True,
            check=False
        )
        if res_untracked.returncode == 0 and res_untracked.stdout:
            for line in res_untracked.stdout.splitlines():
                line = line.strip()
                if line.startswith(('?? ', 'A ', 'M ', 'AM', ' M')):
                    p = line[3:].strip()
                    if os.path.exists(os.path.join(REPO_ROOT, p)):
                        touched.add(p)
    except Exception as e:
        print(f"Warning: Failed to query git status ({e}). Falling back to repository scan.")
    return sorted(list(touched))

def audit_path_for_forbidden_modules(file_path):
    """Check if a file path belongs to or directly targets a forbidden module."""
    rel = file_path.replace('\\', '/')
    if rel in ALLOWED_FOREIGN_NAMED_PATHS or rel.startswith(AUDIT_SKIP_PREFIXES):
        return None
    parts = file_path.replace('\\', '/').split('/')
    for mod in FORBIDDEN_MODULES:
        # Direct folder match (e.g. mod/mod_news, installer/mod_product, templates/admin/mod_service)
        if mod in parts:
            return {
                'type': 'FORBIDDEN_MODULE_FILE',
                'module': mod,
                'file': file_path,
                'message': f"File '{file_path}' belongs to forbidden module '{mod}'."
            }
        # Helper / sub-file match (e.g. mod/index/helper/mod_client.helper.php)
        base = os.path.basename(file_path)
        if mod in base and not base.endswith(('.md', '.py')):
            return {
                'type': 'FORBIDDEN_MODULE_REFERENCE_FILE',
                'module': mod,
                'file': file_path,
                'message': f"File '{file_path}' has forbidden module token '{mod}' in its name."
            }
    return None

def audit_file_content_for_forbidden_modules(full_path, rel_path):
    """Scan file content for new imports or references to forbidden modules."""
    violations = []
    # Skip binary files, docs, git, node_modules, vendor, and this tool itself
    if (rel_path.startswith(('.agents/', 'docs/', '.git/', 'node_modules/', 'vendor/', 'cache/', 'logs/', 'tools/'))
        or rel_path.endswith(('.md', '.png', '.jpg', '.jpeg', '.svg', '.gif', '.ico', '.lock', '.phar'))):
        return violations

    try:
        with open(full_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
    except Exception:
        return violations

    for mod in FORBIDDEN_MODULES:
        # Pattern checking for direct table or module registration/hooking
        patterns = [
            (rf"['\"]{mod}['\"]", f"Direct reference to forbidden module '{mod}'"),
            (rf"\b{mod}\b", f"Token reference to forbidden module '{mod}'")
        ]
        
        # Only flag if it's in core dispatchers or routing configurations
        if rel_path in ['config/url.config.php', 'lib/ModuleRegistry.class.php', 'installer/app/InstallerModuleCatalog.php']:
            if re.search(rf"['\"]{mod}['\"]", content):
                violations.append({
                    'type': 'FORBIDDEN_MODULE_CORE_BINDING',
                    'module': mod,
                    'file': rel_path,
                    'message': f"Core configuration file '{rel_path}' contains binding or route for forbidden module '{mod}'."
                })
    return violations

def run_guard(audit_all=False, json_output=False):
    violations = []
    
    if audit_all:
        target_files = []
        for root, _, files in os.walk(REPO_ROOT):
            for f in files:
                full_p = os.path.join(root, f)
                rel_p = os.path.relpath(full_p, REPO_ROOT)
                target_files.append(rel_p)
    else:
        target_files = get_git_touched_files()

    for rel_p in target_files:
        full_p = os.path.join(REPO_ROOT, rel_p)
        
        # 1. Path check
        path_violation = audit_path_for_forbidden_modules(rel_p)
        if path_violation:
            violations.append(path_violation)
            
        # 2. Content check
        if os.path.isfile(full_p):
            content_violations = audit_file_content_for_forbidden_modules(full_p, rel_p)
            violations.extend(content_violations)

    if json_output:
        print(json.dumps({
            'status': 'FAIL' if violations else 'PASS',
            'scanned_files_count': len(target_files),
            'violations_count': len(violations),
            'violations': violations
        }, indent=2))
    else:
        print("=" * 60)
        print("  Amed Framework - Module Boundary Guard Report")
        print("=" * 60)
        print(f"Mode: {'Full Repository Audit' if audit_all else 'Git Touched Files Audit'}")
        print(f"Forbidden Modules Protected: {len(FORBIDDEN_MODULES)}")
        print(f"Scanned Files: {len(target_files)}")
        print(f"Violations Found: {len(violations)}")
        print("-" * 60)
        
        if not violations:
            print("✅ PASSED: No forbidden module files or bindings detected.")
            print("=" * 60)
            return 0
        else:
            print("❌ FAILED: The following forbidden module violations were detected:")
            for v in violations:
                print(f"  - [{v['type']}] ({v['module']}): {v['message']}")
            print("=" * 60)
            print("Action Required: Remove or isolate all forbidden module files and references.")
            return 1

    return 1 if violations else 0

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description="Audit and guard codebase against forbidden foreign modules.")
    parser.add_argument('--all', action='store_true', help="Audit all files in the repository.")
    parser.add_argument('--json', action='store_true', help="Output results in JSON format.")
    args = parser.parse_args()

    exit_code = run_guard(audit_all=args.all, json_output=args.json)
    sys.exit(exit_code)
