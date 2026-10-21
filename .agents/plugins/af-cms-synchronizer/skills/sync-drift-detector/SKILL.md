---
name: sync-drift-detector
description: Detect, analyze, and categorize shared-core drift between AF/Bulletin and canonical AF/Business using deterministic policy analysis.
---

# Sync Drift Detector Skill

Use this skill to deterministically inspect, categorize, and resolve drift between this repository (`AF/Bulletin`) and the canonical upstream reference (`AF/Business`).

## 1. Execution Procedure

Run `cross_repo_sync.py` pointing to the canonical `Business` repository:

```bash
python3 tools/cross_repo_sync.py --sibling /home/malpersaz/Desktop/projects/htdocs/AF/Business --json
```

## 2. Classification Matrix

The sync tool classifies every file according to `docs/CROSS_REPO_SYNC_POLICY.md`:

| Category | Policy | Action Required |
| :--- | :--- | :--- |
| **DRIFT** | `mirror` | **Must Port**: Shared-core file diverged from canonical. Port canonical changes to Bulletin. |
| **MISSING_LOCALLY** | `mirror` | **Must Port or Classify**: File exists in canonical core but missing locally. Port unless it's a domain module. |
| **ADAPTED_DIFF** | `adapted` | **Preserve**: Expected repository-specific variance. Do NOT overwrite. |
| **DOMAIN_LOCAL** | `local` | **Ignore**: Belongs exclusively to one repository. |

## 3. Safe Porting Procedure

When porting a file marked as `DRIFT` or `MISSING_LOCALLY`:

1. **Read Canonical Source**: View the source file in `/home/malpersaz/Desktop/projects/htdocs/AF/Business/<path>`.
2. **Pre-Port Check**: Ensure the file does not reference forbidden commercial, appointment, or test modules.
3. **Apply Changes**: Update the file in `AF/Bulletin/<path>`.
4. **Syntax Verification**: Run `php -l <path>` on modified PHP files.
5. **Re-run Audit**: Execute `python3 tools/cross_repo_sync.py --sibling /home/malpersaz/Desktop/projects/htdocs/AF/Business` to verify that drift has been resolved.
