# Task Assignment: Worker 2 (Milestone 3 Iteration 2 Remediation)

You are Worker 2 for Milestone 3.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m3_2/

Read the following documents:
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/SCOPE.md
- Blueprint: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_4/handoff.md
- Patch: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_4/tools_mcp_cli.patch

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

Owned files:
- tools/mcp_cli.py

Your implementation task:
Apply the fix blueprint from `.agents/teamwork/explorer_m3_4/handoff.md` (or patch `.agents/teamwork/explorer_m3_4/tools_mcp_cli.patch`) to `tools/mcp_cli.py`:
1. In `parse_and_dispatch`: route positional subcommands (`ping`, `list-tools`, `get-record`) with extracted global flags (`--bridge`, `--timeout`) to their dedicated handlers (`handle_ping`, `handle_list_tools`, `handle_get_record`) instead of `handle_call`.
2. In `enforce_editorial_policy`: ensure `image.file_path` is validated on `update_news` as well as `create_news`.

Verification requirements:
- python3 -m py_compile tools/mcp_cli.py
- python3 -m unittest tests/test_mcp_cli.py
- python3 -m unittest tests/test_m3_2_empirical_stress.py
- Test positional CLI command: python3 tools/mcp_cli.py amed-bulletin-mcp ping
- Test positional list-tools command: python3 tools/mcp_cli.py amed-bulletin-mcp list-tools
- python3 tools/code_separation_guard.py
- python3 tools/module_boundary_guard.py --all

Write your handoff report to `.agents/teamwork/worker_m3_2/handoff.md`.
Notify me via send_message when finished.


## 2026-11-11T13:00:52Z
[Message] timestamp=2026-11-11T13:00:52Z sender=1cb4034b-124b-423c-b681-70f423565312 priority=MESSAGE_PRIORITY_HIGH content=You are Worker 2 for Milestone 3 (Iteration 2 Remediation).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m3_2/

MANDATORY: You MUST read the original user request at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
and your dispatch instructions at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m3_2/DISPATCH.md
and the fix blueprint at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_4/handoff.md
and patch:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_4/tools_mcp_cli.patch

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

Owned files:
- tools/mcp_cli.py

Apply the patch to tools/mcp_cli.py so positional syntax <server> ping and <server> list-tools route to their dedicated handlers, and update_news validates image.file_path.
Run verification commands:
- python3 -m py_compile tools/mcp_cli.py
- python3 -m unittest tests/test_mcp_cli.py
- python3 -m unittest tests/test_m3_2_empirical_stress.py
- python3 tools/mcp_cli.py amed-bulletin-mcp ping
- python3 tools/mcp_cli.py amed-bulletin-mcp list-tools
- python3 tools/code_separation_guard.py
- python3 tools/module_boundary_guard.py --all

Write report to .agents/teamwork/worker_m3_2/handoff.md and report back via send_message.
