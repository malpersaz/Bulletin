# Dispatch: Test Writer (Milestone E2E-M3 - Tier 2 Boundary & Corner Case Suite)

**Working Directory**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_test_writer_m3/`  
**Target Milestone**: `E2E-M3` (Tier 2 Boundary & Corner Cases)  
**Assigned Scope**: Author all Tier 2 test modules under `tests/e2e/tier2/` covering all 36 features based on the Explorer blueprints.  

## Mandatory Inputs to Read First
1. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md` (MUST read first)
2. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md`
3. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_orch_gen2/SCOPE.md`
4. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/TEST_INFRA.md`
5. Explorer 1 Blueprints: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_1/handoff.md` (Features 1-12, 76 test cases)
6. Explorer 2 Blueprints: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_2/handoff.md` (Features 13-23, 56 test cases)
7. Explorer 3 Blueprints: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_3/handoff.md` (Features 24-36, 78 test cases)
8. Reference Tier 1 tests in `tests/e2e/tier1/` and harness package under `tests/e2e/harness/`

## Write Ownership
You exclusively own:
- Directory: `tests/e2e/tier2/`
- All files created inside `tests/e2e/tier2/` (`__init__.py`, `test_f01_*.py` through `test_f36_*.py`)
- Do NOT touch files outside `tests/e2e/tier2/` except for your state folder metadata.

## Mandatory Integrity Warning
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

## Requirements
1. Author comprehensive, genuine, opaque-box, boundary and corner-case test cases in `tests/e2e/tier2/` following the blueprint specifications from Explorers 1, 2, and 3.
2. Target: ≥5 distinct boundary/corner test cases per feature for all 36 features (minimum 180 test cases total, matching or adapting the ~210 explorer blueprints).
3. Every test must be completely genuine, using the harness (`tests/e2e/harness/`), making real subprocess / CLI / HTTP mock / Docker calls as appropriate, asserting on real exit codes, standard streams, database state, headers, or JSON payloads.
4. All tests must be offline-deterministic and pass cleanly when executed via:
   `python3 tests/e2e/run_e2e_tests.py --tier 2 --no-network`
5. Test runner integration:
   - Ensure `tests/e2e/tier2/__init__.py` exists.
   - Modules must follow standard naming `test_fXX_*.py` matching the runner's discovery patterns.
   - If any test requires special readiness or skipping (like F36 release tag readiness before M5 release), use proper skip decorators `unittest.skip` or skip indicators.
6. Verify your implementation by running:
   `python3 tests/e2e/run_e2e_tests.py --tier 2 --no-network`
   `python3 tests/e2e/test_harness_self_verification.py`
   `python3 tools/code_separation_guard.py`
   `python3 tools/module_boundary_guard.py --all`
7. Produce your handoff report at `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_test_writer_m3/handoff.md` with full execution results and test counts.


## 2026-11-11T14:16:05Z
You are the Test Writer for Milestone E2E-M3: Tier 2 Boundary & Corner Case Suite.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_test_writer_m3/

Read your dispatch file at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_test_writer_m3/DISPATCH.md
and ORIGINAL_REQUEST.md at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md

Read the 3 Explorer handoff reports containing the test blueprints:
1. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_1/handoff.md (Features 1-12, 76 test cases)
2. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_2/handoff.md (Features 13-23, 56 test cases)
3. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_3/handoff.md (Features 24-36, 78 test cases)

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

You exclusively own the directory tests/e2e/tier2/.
Implement the Tier 2 boundary and corner case test suites covering all 36 features (>=5 per feature, >=180 test cases).
Run build and test verification:
- python3 tests/e2e/run_e2e_tests.py --tier 2 --no-network
- python3 tests/e2e/test_harness_self_verification.py
- python3 tools/code_separation_guard.py
- python3 tools/module_boundary_guard.py --all

Write your handoff report to:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_test_writer_m3/handoff.md
When complete, notify me with send_message.
