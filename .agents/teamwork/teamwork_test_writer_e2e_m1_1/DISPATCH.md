# Task Assignment: E2E Test Infrastructure & Framework Setup (Milestone E2E-M1)

You are a test engineer agent (`teamwork_test_writer`).
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_test_writer_e2e_m1_1/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin

MANDATORY READING:
You MUST read the following files before starting:
1. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
2. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
3. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_orch/SCOPE.md
4. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_spec_miner_e2e_m1_1/handoff.md (Full 36-feature assertion catalog)
5. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_2/handoff.md (Existing test infra analysis)
6. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_3/handoff.md (Test runner architecture, layout & draft TEST_INFRA.md)

Objective:
Implement Milestone E2E-M1 (Test Infrastructure & Framework Setup):
1. Create `TEST_INFRA.md` at project root using the comprehensive specification and draft in `teamwork_explorer_e2e_m1_3/handoff.md §4.4`, covering the test architecture, runner invocation, and all 36 inventoried features per Project Pattern Dual Track requirements.
2. Implement the unified test runner `tests/e2e/run_e2e_tests.py`:
   - Supports CLI flags: `--tier <1|2|3|4|all>`, `--feature <id>`, `--format <console|json|tap|junit>`, `--output <path>`, `--verbose`, `--fail-fast`, `--no-network`, `--timeout <seconds>`, `--list`, `--help`.
   - Returns exit code 0 on all tests passing, exit code 1 on test failure, exit code 2 on configuration error.
   - Discovers and runs tests across `tests/e2e/tier1/` through `tests/e2e/tier4/`.
3. Implement harness modules under `tests/e2e/harness/`:
   - `__init__.py`
   - `base.py`: `E2ETestCase` base class with metadata decorators (`@tier`, `@feature`), lifecycle (`setUp`, `tearDown`), and assertion helpers.
   - `runner.py`: discovery engine, test filter, executor, and timeout manager.
   - `reporters.py`: Console formatter (rich colored summary), TAP version 13 reporter, JUnit XML reporter, and JSON reporter.
   - `process.py`: Subprocess execution wrapper (`run_cli`, `run_python`, `run_php`) capturing stdout, stderr, exit code, and execution time.
   - `php_bridge.py`: PHP execution helper capable of running against host MockDb or Docker `bulletin_app` container.
   - `mock_http.py`: Zero-dependency embedded Python HTTP mock server for deterministic offline execution (`--no-network`).
   - `sandbox.py`: Temporary isolated directory manager for test artifacts.
   - `assertions.py`: Assertion utilities for exit codes, JSON envelopes, HTML tags, and error contracts.
4. Populate initial fixture files under `tests/e2e/fixtures/` (`editorial/`, `images/`, `feeds/`, `mcp/`).
5. Verify test runner:
   - Run `python3 tests/e2e/run_e2e_tests.py --help`
   - Run `python3 tests/e2e/run_e2e_tests.py --list`
   - Run `python3 tests/e2e/run_e2e_tests.py --tier 1 --no-network` (or initial smoke test)
   - Verify `python3 tools/code_separation_guard.py` passes if any templates are inspected.

Write Ownership (Exclusive):
You have exclusive write ownership of:
- `TEST_INFRA.md` (at project root)
- `tests/e2e/run_e2e_tests.py`
- `tests/e2e/harness/*`
- `tests/e2e/fixtures/*`
Do NOT modify production application code (`lib/`, `mod/`, `templates/`, `config/`).

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

Output:
Write your full completion report to:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_test_writer_e2e_m1_1/handoff.md
Update your progress.md periodically.
Send a message when complete with summary and report path.


## 2026-11-11T11:02:51Z
Read your assignment at /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_test_writer_e2e_m1_1/DISPATCH.md.
MANDATORY: Read /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md before starting.
Implement Milestone E2E-M1: create TEST_INFRA.md at project root per specifications, implement tests/e2e/run_e2e_tests.py, harness modules under tests/e2e/harness/, and fixtures under tests/e2e/fixtures/.
Run verification commands and write your handoff report to /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_test_writer_e2e_m1_1/handoff.md.
