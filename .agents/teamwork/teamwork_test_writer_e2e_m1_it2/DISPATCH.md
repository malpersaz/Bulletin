# Task Assignment: Test Infrastructure & Runner Hardening (Milestone E2E-M1, Iteration 2)

You are a test engineer agent (`teamwork_test_writer`).
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_test_writer_e2e_m1_it2/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin

MANDATORY READING:
You MUST read the following files before starting:
1. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
2. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
3. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_orch/SCOPE.md
4. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/TEST_INFRA.md
5. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_1/handoff.md (Runner CLI & Discovery Fixes)
6. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_2/handoff.md (Mock HTTP & Sandbox Fixes)
7. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_3/handoff.md (Assertion Utilities Fixes)

Objective:
Implement the hardening fixes across the E2E test runner and harness as detailed in the three Explorer handoff reports:
1. `tests/e2e/run_e2e_tests.py`:
   - Malformed `--feature` arguments (e.g. `--feature abc`) must raise `ArgumentTypeError` / error with exit code 2.
   - When 0 tests match a filter, format an empty `TestRunSummary` for non-console formats (JSON, TAP, JUnit) and write to `--output <path>` if specified.
2. `tests/e2e/harness/runner.py`:
   - Fix `ImportErrorCase` lambda closure bug: use `lambda s, err=e: (_ for _ in ()).throw(err)`.
   - Add module ownership check: `if getattr(attr, "__module__", None) != module.__name__: continue`.
   - Prefix module name in `sys.modules` with `f"tier{default_tier}_"` to prevent collisions.
3. `tests/e2e/harness/mock_http.py`:
   - Implement `ThreadingHTTPServer` to eliminate re-entrant deadlocks.
   - Copy route headers dictionary before mutating in `_dispatch()`.
4. `tests/e2e/harness/sandbox.py`:
   - Path traversal guard in `path()` and `write_file()`: ensure resolved path starts with `self.root`.
   - Read-only directory cleanup: handle `chmod 0700` in onerror handler for `shutil.rmtree`.
5. `tests/e2e/harness/assertions.py`:
   - `assert_no_inline_styles_or_scripts`: allow valid `<script type="application/ld+json">` and external scripts `<script src="...">`.
   - `assert_html_tag_present`: quote-aware tag regex `(?:[^>"']|"[^"]*"|'[^']*')*` and exact tokenized attribute matching.
   - `assert_date_is_today`: parse timezone offsets (`+03:00`, `Z`) and convert tz-aware `datetime` to `Europe/Istanbul`.
6. Update `tests/e2e/test_harness_self_verification.py`:
   - Add unit tests verifying all remediations.

Write Ownership (Exclusive):
You have exclusive write ownership of:
- `tests/e2e/run_e2e_tests.py`
- `tests/e2e/harness/*`
- `tests/e2e/test_harness_self_verification.py`
Do NOT modify production application code (`lib/`, `mod/`, `templates/`, `docker/`, `config/`).

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

Verification Commands:
- `python3 -m unittest tests/e2e/test_harness_self_verification.py`
- `python3 tests/e2e/run_e2e_tests.py --tier 1 --no-network`
- `python3 tests/e2e/run_e2e_tests.py --feature abc` (must exit code 2)
- `python3 tests/e2e/run_e2e_tests.py --tier 99 --format json` (must output valid empty JSON)
- `python3 tools/code_separation_guard.py`

Output:
Write your full report to:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_test_writer_e2e_m1_it2/handoff.md
Update progress.md and send message when complete.


## 2026-11-11T11:36:06Z
Read your assignment at /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_test_writer_e2e_m1_it2/DISPATCH.md.
MANDATORY: Read /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md before starting.
Apply all hardening fixes across run_e2e_tests.py, runner.py, mock_http.py, sandbox.py, and assertions.py, update tests/e2e/test_harness_self_verification.py, run verification commands, and write your handoff report to /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_test_writer_e2e_m1_it2/handoff.md.
