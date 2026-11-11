# Dispatch for Worker 2 (Milestone 5 Iteration 2: Fix Release Tag to Annotated Tag & Re-Verify)

## Working Directory
`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`

## State Folder
`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m5_2/`

## Mandatory Reading
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md`
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md`
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m5/SCOPE.md`
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/auditor_m5_1/handoff.md` (FULL AUDITOR EVIDENCE REPORT)
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m5_1/handoff.md`
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/reviewer_m5_2/handoff.md`

## Problem & Full Auditor Finding
The Forensic Auditor reported INTEGRITY VIOLATION because tag `v1.0.63` was created as a lightweight ref (`git tag v1.0.63`), returning `type commit` instead of `type tag` via `git cat-file -t v1.0.63`. This broke 3 tests in `tests/e2e/tier1/test_f36_git_release_tag_push.py` and caused `python3 tests/e2e/run_e2e_tests.py` to fail with exit code 1 (184 passed, 3 failed).

## Required Concrete Actions
1. **Recreate Tag as Annotated Git Tag Object**:
   ```bash
   git tag -a -f v1.0.63 -m "Release v1.0.63: Kevanger editorial pipeline remediation and enhancement"
   ```
2. **Verify Tag Object Type**:
   ```bash
   git cat-file -t v1.0.63
   # MUST return 'tag' (NOT 'commit')
   ```
3. **Verify Tag Contents**:
   ```bash
   git cat-file -p v1.0.63
   # MUST show 'tagger' details and the release message
   ```
4. **Force Push Annotated Tag to GitHub Remote**:
   ```bash
   git push origin v1.0.63 --force
   ```
5. **Verify Full Test Suites**:
   - `PYTHONPATH=tests/e2e python3 -m unittest tests/e2e/tier1/test_f36_git_release_tag_push.py` (MUST pass 5/5 tests)
   - `python3 tests/e2e/run_e2e_tests.py` (MUST pass 187/187 tests with exit code 0)
   - `docker exec bulletin_app php tasks/mcp_smoke.php` (83 passed, 0 failed)
   - `python3 tools/mcp_health_check.py --endpoint http://localhost:8080/mcp.php` (24 passed, 0 failed)
   - `python3 tools/i18n_guard.py`
   - `python3 tools/code_separation_guard.py`
   - `python3 tools/module_boundary_guard.py --all`
   - `python3 tools/min_css_guard.py`

## Mandatory Integrity Warning
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

## Reporting
Write complete handoff report to:
`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m5_2/handoff.md`

## 2026-11-11T14:11:13Z
You are Worker 2 for Milestone 5 Iteration 2 (Fix Release Tag to Annotated Tag & Re-Verify).
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m5_2/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

MANDATORY READING:
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m5/SCOPE.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m5_2/DISPATCH.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/auditor_m5_1/handoff.md (FULL FORENSIC AUDITOR EVIDENCE REPORT)
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m5_1/handoff.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/reviewer_m5_2/handoff.md

Your tasks:
1. Re-create tag `v1.0.63` as an annotated Git tag object:
   `git tag -a -f v1.0.63 -m "Release v1.0.63: Kevanger editorial pipeline remediation and enhancement"`
2. Verify with `git cat-file -t v1.0.63` that it returns `tag` (not `commit`).
3. Verify with `git cat-file -p v1.0.63` that it contains tagger metadata and the release message.
4. Force push the annotated tag to remote GitHub:
   `git push origin v1.0.63 --force`
5. Run verification tests:
   - `PYTHONPATH=tests/e2e python3 -m unittest tests/e2e/tier1/test_f36_git_release_tag_push.py` (MUST pass 5/5 tests)
   - `python3 tests/e2e/run_e2e_tests.py` (MUST pass 187/187 tests with exit code 0)
   - `docker exec bulletin_app php tasks/mcp_smoke.php` (83 passed, 0 failed)
   - `python3 tools/mcp_health_check.py --endpoint http://localhost:8080/mcp.php` (24 passed, 0 failed)
   - `python3 tools/i18n_guard.py`
   - `python3 tools/code_separation_guard.py`
   - `python3 tools/module_boundary_guard.py --all`
   - `python3 tools/min_css_guard.py`
6. Write your complete handoff report to `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m5_2/handoff.md`.
Send a completion message when finished.
