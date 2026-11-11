# Dispatch for Worker (Milestone 5: Verification, Quality Guards, Version Parity & Tag/Push Release)

## Working Directory
`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`

## State Folder
`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m5_1/`

## Mandatory Reading
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md`
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md`
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m5/SCOPE.md`
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md`

## Owned Files
- `docs/VERSION.md`
- `config/site.config.php`
- `config/site.config.example.php`
- `docs/SERVER.md`

## Instructions & Steps
1. **Run Full Verification & Quality Suite**:
   - `php tasks/mcp_smoke.php` (must pass 100%)
   - `bash tools/verify_news_pipeline.sh` (must pass bash -n and pipeline checks)
   - `python3 tools/mcp_health_check.py`
   - `python3 tools/i18n_guard.py`
   - `python3 tools/code_separation_guard.py`
   - `python3 tools/module_boundary_guard.py --all`
   - `python3 tools/min_css_guard.py`
   - `php -l` on all PHP files
   - `python3 -m py_compile` on all Python files
   If any tool needs a fix or adjustment per M1-M4 blueprints (e.g. mcp_smoke.php or verify_news_pipeline.sh), diagnose and document.

2. **Version Parity Synchronization across 4 files (v1.0.63)**:
   - `docs/VERSION.md`: Add comprehensive changelog under `## [v1.0.63] - 2026-11-11` documenting all fixes, additions, and enhancements across Phases 1-4 without omission. Follow the exact formatting of prior entries in `docs/VERSION.md`.
   - `config/site.config.php`: Update version string to `1.0.63`.
   - `config/site.config.example.php`: Update version string to `1.0.63`.
   - `docs/SERVER.md`: Update version string references to `1.0.63`.

3. **Release Deployment**:
   - Verify git status: Ensure clean working directory with only desired changes.
   - Stage and commit with exact commit message:
     `Release v1.0.63: Kevanger editorial pipeline remediation and enhancement`
   - Create annotated or lightweight git tag:
     `git tag v1.0.63`
   - Push commit and tag directly to remote GitHub repository:
     `git push origin <branch> --tags`
     (Verify current branch using `git branch --show-current`, e.g., `git push origin main --tags` or whatever current branch is).

4. **Mandatory Integrity Warning**:
   DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

5. **Reporting**:
   Write complete handoff report to `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m5_1/handoff.md` with:
   - Full command outputs and verification evidence
   - Diff/changes made to the 4 version files
   - Git commit hash, tag verification, and git push output
   Send completion message when done.


## 2026-11-11T13:43:58Z
You are Worker 1 for Milestone 5 (Verification, Quality Guards, Version Parity & Tag/Push Release).
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m5_1/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

MANDATORY READING BEFORE DOING ANY WORK:
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m5/SCOPE.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m5_1/DISPATCH.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md

Owned files:
- docs/VERSION.md
- config/site.config.php
- config/site.config.example.php
- docs/SERVER.md

Your concrete tasks:
1. Run Full Regression & Verification:
   - `php tasks/mcp_smoke.php` (must pass 100%)
   - `bash tools/verify_news_pipeline.sh` (pass bash -n and pipeline checks)
   - `python3 tools/mcp_health_check.py`
   - `python3 tools/i18n_guard.py`
   - `python3 tools/code_separation_guard.py`
   - `python3 tools/module_boundary_guard.py --all`
   - `python3 tools/min_css_guard.py`
   - `php -l` on all PHP files
   - `python3 -m py_compile` on all Python files
2. Version Parity Documentation (v1.0.63):
   - Update `docs/VERSION.md` with comprehensive changelog under `## [v1.0.63] - 2026-11-11` detailing all fixes, additions, and enhancements across Phases 1-4 without omission.
   - Synchronize version string `1.0.63` across `config/site.config.php`, `config/site.config.example.php`, `docs/SERVER.md`, and `docs/VERSION.md`.
3. Release Tagging & GitHub Push:
   - Verify `git status`
   - Stage and commit with exact commit message: `Release v1.0.63: Kevanger editorial pipeline remediation and enhancement`
   - Git tag `v1.0.63`
   - Push commit and tag directly to remote GitHub: `git push origin <branch> --tags`
4. Document all outputs, diffs, and verification commands in:
   `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m5_1/handoff.md`
   Send a completion message when finished.
