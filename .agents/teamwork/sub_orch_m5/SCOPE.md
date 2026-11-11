# Scope: Milestone 5 — Full Verification, Quality Guards, Version Parity & Release Tag/Push

## Scope Overview
Implement R2 and R3 final verification, version documentation, and release deployment:
1. Run full verification suite:
   - `php tasks/mcp_smoke.php` (must pass 100%)
   - `bash tools/verify_news_pipeline.sh` (must pass bash -n and pipeline stages)
   - `python3 tools/mcp_health_check.py` (Stage 7 RFC discovery unmasked)
   - `python3 tools/i18n_guard.py` (must pass 0 errors)
   - `python3 tools/code_separation_guard.py` (must pass 0 inline CSS/JS)
   - `python3 tools/module_boundary_guard.py --all` (must pass 0 violations)
   - `python3 tools/min_css_guard.py` (must pass)
   - `php -l` on all PHP files
   - `python3 -m py_compile` on all Python files
2. Version Parity Synchronization across 4 files (v1.0.63):
   - `docs/VERSION.md`: Full comprehensive changelog under `## [v1.0.63] - 2026-11-11` documenting all fixes and features across Phases 1–4.
   - `config/site.config.php`: Version set to `1.0.63`.
   - `config/site.config.example.php`: Version set to `1.0.63`.
   - `docs/SERVER.md`: Version set to `1.0.63`.
3. Release Deployment:
   - Git commit all changes with message: `Release v1.0.63: Kevanger editorial pipeline remediation and enhancement`.
   - Git tag `v1.0.63`.
   - Git push commit and tag directly to remote GitHub repository (`git push origin <branch> --tags`).

## Files Owned
- docs/VERSION.md
- config/site.config.php
- config/site.config.example.php
- docs/SERVER.md

## Acceptance Criteria
- Full verification suite passes with 0 failures across all quality guards and smoke tests.
- Version 1.0.63 is strictly synchronized across `config/site.config.php`, `config/site.config.example.php`, `docs/SERVER.md`, and `docs/VERSION.md`.
- `docs/VERSION.md` contains comprehensive notes for all Phases 1-4.
- Git commit and tag `v1.0.63` are created and pushed to GitHub.
