# Task Assignment: Explorer 1 (Iteration 2) — Runner CLI & Discovery Fixes

You are an exploration agent (`teamwork_explorer`).
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_1/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin

MANDATORY READING:
You MUST read the following files before starting:
1. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
2. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
3. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_orch/SCOPE.md
4. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/TEST_INFRA.md
5. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_challenger_e2e_m1_1/handoff.md
6. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_reviewer_e2e_m1_1/handoff.md

Objective:
Investigate and formulate the fix strategy for the runner CLI, filtering, and discovery defects found by Challenger 1 and Reviewer 1:
1. In `tests/e2e/run_e2e_tests.py`:
   - Malformed `--feature <val>` filter (e.g. `--feature abc`): Currently falls back to empty set and runs all tests. Recommend how `_parse_feature_filter` should handle invalid integer IDs (reject with exit code 2 or return a sentinel empty set that matches 0 tests).
   - Zero test matches: Currently prints message and early-returns 0 bytes for non-console formats. Recommend how to properly write empty TAP, JUnit XML, and JSON reports to stdout and to `--output <path>`.
2. In `tests/e2e/harness/runner.py`:
   - `ImportErrorCase`: The lambda references `e` after the `except` block, causing `NameError`. Recommend the exact closure fix (`err=e`).
   - Test discovery module ownership: Ensure test classes/methods collected belong to the inspected module (`attr.__module__ == module.__name__`) to prevent duplicate collection when tests import base classes.

Scope Boundaries:
- READ-ONLY investigation. Do NOT modify source code or tests. Recommend exact fix strategy.

Output:
Write your report to:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_1/handoff.md
Update progress.md and send message when complete.


## 2026-11-11T11:28:19Z
Read your assignment at /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_1/DISPATCH.md.
MANDATORY: Read /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md before starting.
Investigate and formulate the fix strategy for the runner CLI, filtering, and discovery defects found by Challenger 1 and Reviewer 1.
Write your report to /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_1/handoff.md.
