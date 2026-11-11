# Task Assignment: Explorer 2 (Iteration 2) — Mock HTTP & Sandbox Hardening

You are an exploration agent (`teamwork_explorer`).
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_2/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin

MANDATORY READING:
You MUST read the following files before starting:
1. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
2. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
3. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_orch/SCOPE.md
4. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/TEST_INFRA.md
5. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_challenger_e2e_m1_2/handoff.md

Objective:
Investigate and formulate the fix strategy for the mocking and sandbox defects found by Challenger 2:
1. In `tests/e2e/harness/mock_http.py`:
   - Threading deadlock: `MockHttpServer` currently uses single-threaded `HTTPServer`. Re-entrant requests (e.g. callback making HTTP call) cause deadlocks. Recommend how to implement `ThreadingHTTPServer` (or `socketserver.ThreadingMixIn`).
   - Header mutation in place: `_dispatch` mutates the route's headers dictionary. Recommend making a `.copy()` before mutating.
2. In `tests/e2e/harness/sandbox.py`:
   - Path traversal vulnerability: `Sandbox.path()` and `write_file()` permit escaping via `/...` or `../`. Recommend canonical `os.path.realpath` / `os.path.commonpath` boundary check.
   - Read-only file cleanup: `shutil.rmtree` silently fails on read-only subdirectories. Recommend an `onerror` handler that calls `os.chmod(path, stat.S_IWRITE | stat.S_IREAD | stat.S_IEXEC)` and retries deletion.

Scope Boundaries:
- READ-ONLY investigation. Do NOT modify source code or tests. Recommend exact fix strategy.

Output:
Write your report to:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_2/handoff.md
Update progress.md and send message when complete.


## 2026-11-11T11:28:22Z
Read your assignment at /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_2/DISPATCH.md.
MANDATORY: Read /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md before starting.
Investigate and formulate the fix strategy for the Mock HTTP and Sandbox defects found by Challenger 2.
Write your report to /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_2/handoff.md.
