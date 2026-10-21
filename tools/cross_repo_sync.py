#!/usr/bin/env python3
"""Cross-repo shared-core drift report for the AF CMS repository family.

Model (decided 2026-09-16): one-way synchronization. `Business` is the canonical
shared-core reference; sibling repositories (Association, ...) adopt Business
changes while preserving their documented boundary adaptations. This tool is
READ-ONLY: it reports drift, it never modifies files.

Policies:
  mirror   - File must be byte-identical to the canonical copy after brand-token
             normalization. Any difference is DRIFT and fails the run (exit 1).
  adapted  - File is expected to differ between repositories. Every entry carries
             a reason; differences are reported for visibility only.
  local    - File is repository-private; never compared.

One-sided files that reference a module owned by the other repository are
classified DOMAIN_LOCAL (repository domain modules are never portable).

Usage:
  python3 tools/cross_repo_sync.py [--json] [--sibling PATH]

Maintenance rule: when a shared-core change lands in Business, port it to the
sibling repositories in the same pass (or run this tool and clear the report).
When a file legitimately becomes per-repository, move it to the `adapted` list
with a reason comment. Keep the policy table identical across repositories.
"""

import argparse
import difflib
import json
import os
import re
import sys

CANONICAL_REPO = "Business"
SIBLING_CANDIDATES = ["Association", "Artist", "Property", "Bulletin"]

# Longest token first: normalized away before comparison so header branding
# does not count as drift.
BRAND_TOKENS = [
    ("AKM Association Content Management System", "<CMS_NAME>"),
    ("AKM Business Content Management System", "<CMS_NAME>"),
    ("AKM Association CMS", "<CMS_NAME>"),
    ("AKM Business CMS", "<CMS_NAME>"),
    ("AKMAssociation", "<BRAND_NOSPACE>"),
    ("AKMBusiness", "<BRAND_NOSPACE>"),
    ("akmassociation", "<brand_nospace>"),
    ("akmbusiness", "<brand_nospace>"),
    ("amed-association-mcp", "<mcp_server_name>"),
    ("amed-business-mcp", "<mcp_server_name>"),
    ("AKM Association", "<BRAND>"),
    ("AKM Business", "<BRAND>"),
    ("Amed Association", "<BRAND>"),
    ("Amed Framework", "<BRAND>"),
    ("Learnly", "<BRAND_NOSPACE>"),
]

# path prefix or exact file -> policy. Longest matching prefix wins.
# Reasons document WHY a file is allowed to differ; keep them current.
POLICY = {
    # ---- core libraries that hold deliberate per-repository adaptations
    "lib/McpServer.class.php": "adapted",      # domain tool registrations differ per repo
    "lib/LlmsService.class.php": "adapted",    # domain content sections differ per repo
    "lib/ModuleRegistry.class.php": "adapted", # module catalogs differ per repo
    "lib/Make_Model.class.php": "adapted",     # Business renamed generator to columns; DB-layer rename pending in BOTH repos (coordinated backlog)
    "lib/AdminTemplate.class.php": "adapted",  # per-repo module sidebar data
    # lib/McpCrudToolsBase: faq_mod bridge mapping now shared (mod_faq owned by both repos) -> mirror
    # Association holds newer local improvements (reverse backlog: adopt in Business when scheduled):
    "lib/Amed.class.php": "adapted",           # cookie-theme negotiation, strict language-segment routing fix, getRow() rename
    "lib/AmedError.class.php": "adapted",      # pairs with clearTpl(); Business calls clearTpl() without defining it (Business bug, reverse backlog)
    "lib/TwigTemplate.class.php": "adapted",   # clearTpl() support (see AmedError note)
    "lib/AmedResize.class.php": "adapted",     # clear method/variable naming
    "lib/Paginate.class.php": "adapted",       # $sideLimit naming
    "lib/FFmpeg.class.php": "adapted",         # $audioCodec/$parts naming
    "lib/Captcha.class.php": "adapted",        # $digits/$fontId naming
    "lib/BrandAssetGenerator.class.php": "adapted",  # descriptive naming
    "lib/Helper.class.php": "adapted",         # trivial naming divergence both directions
    "lib/session/Session.db.php": "adapted",   # $timestamp naming
    "lib/Validator.class.php": "adapted",      # $callback naming
    "lib/Req.class.php": "adapted",            # clearer comment
    "lib/CssOptimizer.class.php": "adapted",   # clearer comment
    "lib/BaseModuleValidator.class.php": "adapted",  # clearer comment
    "lib/Database/Adapter/PDO_MYSQL.php": "adapted", # clearer comment
    "lib/Database/Database.php": "adapted",    # getRow() rename + private static state (Association ahead)
    "lib/BaseModuleController.class.php": "adapted", # getRow() rename + English docs (Business doc still has Turkish 'fiyat')
    "lib/Orm/Orm.php": "adapted",              # comment divergence only
    "lib/Orm/Obuilder.php": "adapted",         # naming divergence both directions (groupOpen vs og_key)
    "lib/TwigLibs.class.php": "adapted",       # CDATA emission style differs (Association splits the literal; Business uses the //<![CDATA[ idiom)
    "lib/Database/Adapter.php": "adapted",     # canonical carries a commented-out legacy adapter line
    "lib/": "mirror",
    # ---- quality guard tooling: shared logic, per-repo configuration
    "tools/i18n_guard.py": "adapted",          # per-repo allowlists and exclude sets
    "tools/module_boundary_guard.py": "adapted",# per-repo forbidden module lists
    "tools/audit_modernization.py": "adapted", # per-repo target module set
    "tools/cross_repo_sync.py": "mirror",      # this file must stay identical
    "tools/mobile_simulator.css": "local",     # Association-private QA helper
    "tools/mobile_simulator.html": "local",    # Association-private QA helper
    "tools/": "mirror",
    # ---- installer: shared engine mirrors, per-repo catalogs/seeds adapt
    "installer/app/InstallerModuleCatalog.php": "adapted",  # module sets differ
    "installer/app/InstallerStateStore.php": "adapted",     # Association keeps branding remap + db-config prefill
    "installer/app/InstallerConfigWriter.php": "adapted",   # naming divergence ($staticAddress vs $iaddress); Business @-suppression candidate
    "installer/app/": "mirror",
    "installer/installerbase.php": "adapted",  # Association ahead: getRow() rename (Business still gRo)
    "installer/index.php": "mirror",
    "installer/lang/": "adapted",              # domain module labels differ per repo
    "installer/mod_": "adapted",               # sample seed content and schemas differ per repo
    "installer/assets/css/installer.css": "adapted",  # Association keeps installer-msg-* classes; BOTH installerbases reference them, Business lacks the definitions (reverse backlog)
    "installer/assets/": "mirror",
    # ---- root bootstrap scripts: per-repo adaptations (several Association-ahead)
    "clear_cache.php": "adapted",
    "css.php": "adapted",
    "js.php": "adapted",
    "resize.php": "adapted",
    "robots.php": "adapted",
    "template.php": "adapted",
    # ---- configuration and runtime content: per-repo by nature
    "config/": "adapted",
    "mod/": "adapted",                         # module-level sync tracked in docs/AF_CMS_SYNCHRONIZATION_STRATEGY.md roadmap
    "templates/": "adapted",                   # public themes are per-repo; admin shell tracked separately
    "data/": "local",
    "cache/": "local",
    "logs/": "local",
    "log/": "local",
    "vendor/": "local",
    "node_modules/": "local",
    ".git/": "local",
    ".agents/": "local",
    ".codegraph/": "local",
    "docs/": "local",
    "depo/": "local",
    "backup/": "local",
    "templates_c/": "local",
    "tasks/": "adapted",
    "static/": "adapted",
    "walkthrough.md": "local",
}

ROOT_MIRROR_FILES = ["init.php"]

SCAN_ROOTS = ["lib", "tools", "installer", "config", "mod", "templates", "static"]
SKIP_DIRS = {
    ".git", "node_modules", "vendor", "cache", "logs", "log", "templates_c",
    ".agents", ".codegraph", "data", "depo", "backup", "docs",
    "__pycache__",
}

MODULE_TOKEN = re.compile(r"mod_[a-z_]+")
MCP_TOOLS_CLASS = re.compile(r"Mcp([A-Za-z]+)Tools")


def policy_for(rel):
    if rel in ROOT_MIRROR_FILES:
        return "mirror"
    if rel in POLICY:
        return POLICY[rel]
    best = None
    for prefix, policy in POLICY.items():
        if prefix.endswith("/") and rel.startswith(prefix):
            if best is None or len(prefix) > len(best[0]):
                best = (prefix, policy)
    return best[1] if best else "adapted"


def normalize_brand(text):
    for token, placeholder in BRAND_TOKENS:
        text = text.replace(token, placeholder)
    return text


def modules_of(root):
    mods = set()
    mdir = os.path.join(root, "mod")
    if os.path.isdir(mdir):
        for d in os.listdir(mdir):
            mods.add(d[4:] if d.startswith("mod_") else d)
    return mods


def file_module_tokens(rel):
    tokens = set()
    for token in MODULE_TOKEN.findall(rel):
        tokens.add(token[4:])
    for name in MCP_TOOLS_CLASS.findall(os.path.basename(rel)):
        tokens.add(name.lower())
    return tokens


def walk_files(root):
    found = []
    for scan_root in SCAN_ROOTS:
        base = os.path.join(root, scan_root)
        if not os.path.isdir(base):
            continue
        for dirpath, dirs, files in os.walk(base):
            dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
            for f in files:
                found.append(os.path.relpath(os.path.join(dirpath, f), root))
    for f in ROOT_MIRROR_FILES:
        if os.path.isfile(os.path.join(root, f)):
            found.append(f)
    return sorted(set(found))


def diff_stats(a_text, b_text):
    a_lines = a_text.splitlines(keepends=True)
    b_lines = b_text.splitlines(keepends=True)
    delta = list(difflib.unified_diff(a_lines, b_lines, n=0, lineterm=""))
    plus = sum(1 for l in delta if l.startswith("+") and not l.startswith("+++"))
    minus = sum(1 for l in delta if l.startswith("-") and not l.startswith("---"))
    return plus, minus


def build_report(local_root, canonical_root):
    local_modules = modules_of(local_root)
    canon_modules = modules_of(canonical_root)
    local_files = set(walk_files(local_root))
    canon_files = set(walk_files(canonical_root))
    rows = []
    for rel in sorted(local_files | canon_files):
        policy = policy_for(rel)
        if policy == "local":
            continue
        lp = os.path.join(local_root, rel)
        cp = os.path.join(canonical_root, rel)
        in_local, in_canon = os.path.isfile(lp), os.path.isfile(cp)
        row = {"file": rel, "policy": policy}
        if in_local != in_canon:
            if policy != "mirror":
                row["status"] = "PER_REPO"  # one-sided file under a per-repo policy
            elif file_module_tokens(rel) - local_modules:
                row["status"] = "DOMAIN_LOCAL"  # canonical repo's domain module
            else:
                row["status"] = "MISSING_LOCALLY"
            rows.append(row)
            continue
        a = normalize_brand(open(lp, encoding="utf-8", errors="ignore").read())
        b = normalize_brand(open(cp, encoding="utf-8", errors="ignore").read())
        if a == b:
            row["status"] = "IDENTICAL"
            rows.append(row)
            continue
        plus, minus = diff_stats(a, b)
        row["delta_lines"] = plus + minus
        row["added_local"] = minus   # lines present locally, absent in canonical
        row["missing_local"] = plus  # lines present in canonical, absent locally
        row["status"] = "DRIFT" if policy == "mirror" else "ADAPTED_DIFF"
        rows.append(row)
    return rows


def main():
    repo_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    repo_name = os.path.basename(repo_root)
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--sibling", default=None, help="Path to the canonical repository")
    parser.add_argument("--json", action="store_true", help="Machine-readable output")
    args = parser.parse_args()

    canonical_root = args.sibling
    if not canonical_root:
        parent = os.path.dirname(repo_root)
        candidate = os.path.join(parent, CANONICAL_REPO)
        if os.path.isdir(candidate):
            canonical_root = candidate
        else:
            for name in SIBLING_CANDIDATES:
                p = os.path.join(parent, name)
                if os.path.isdir(p):
                    canonical_root = p
                    break
    if not canonical_root or not os.path.isdir(canonical_root):
        print(f"ERROR: canonical repository '{CANONICAL_REPO}' not found next to {repo_root}. "
              f"Pass --sibling PATH explicitly.")
        return 2
    if os.path.basename(os.path.normpath(canonical_root)) == repo_name:
        print("This repository IS the canonical shared-core reference; run this tool "
              "from an adopting repository instead.")
        return 0

    rows = build_report(repo_root, canonical_root)
    drift = [r for r in rows if r["status"] == "DRIFT"]
    missing = [r for r in rows if r["status"] == "MISSING_LOCALLY"]
    per_repo = [r for r in rows if r["status"] == "PER_REPO"]
    domain_local = [r for r in rows if r["status"] == "DOMAIN_LOCAL"]
    adapted_diff = [r for r in rows if r["status"] == "ADAPTED_DIFF"]
    identical = [r for r in rows if r["status"] == "IDENTICAL"]

    if args.json:
        print(json.dumps({
            "local_repo": repo_name,
            "canonical_repo": os.path.basename(canonical_root),
            "drift": drift,
            "missing_locally": missing,
            "per_repo_count": len(per_repo),
            "domain_local_count": len(domain_local),
            "adapted_diff": adapted_diff,
            "identical_count": len(identical),
        }, indent=2))
    else:
        print("=" * 64)
        print(" AF CMS Cross-Repo Sync Report")
        print(f" local: {repo_name}  |  canonical: {os.path.basename(canonical_root)} (one-way)")
        print("=" * 64)
        if drift:
            print(f"\nDRIFT - mirror files differing from canonical ({len(drift)}):")
            for r in drift:
                print(f"  [{r['delta_lines']:>5} lines] {r['file']}")
        if missing:
            print(f"\nMISSING LOCALLY - shared-core files present in canonical only ({len(missing)}):")
            for r in missing:
                print(f"  {r['file']}")
        if adapted_diff:
            print(f"\nADAPTED - expected per-repo differences ({len(adapted_diff)}):")
            for r in adapted_diff:
                print(f"  [{r.get('delta_lines', 0):>5} lines] {r['file']}")
        print(f"\nidentical: {len(identical)} | drift: {len(drift)} | missing: {len(missing)} | "
              f"per-repo: {len(per_repo)} | domain-local: {len(domain_local)} | adapted-diff: {len(adapted_diff)}")
        if drift or missing:
            print("FAIL: port the listed shared-core changes from the canonical repository.")
        else:
            print("PASS: shared core is in parity with the canonical repository.")

    return 1 if (drift or missing) else 0


if __name__ == "__main__":
    sys.exit(main())
