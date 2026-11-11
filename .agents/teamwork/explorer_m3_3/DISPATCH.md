# Task Assignment: Explorer 3 (M3 Swarm CLI Wrapper tools/mcp_cli.py)

You are Explorer 3 for Milestone 3 (Swarm CLI Wrapper).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_3/

Read the following documents:
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/SCOPE.md
- docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md (Sections A2, Phase 3)
- Reference pattern: tools/mcp_health_check.py (lines 182-270 `StdioBridgeSession`)

Your objective:
Investigate and design the exact implementation blueprint for `tools/mcp_cli.py`:
1. Architecture of `tools/mcp_cli.py` as a secret-opaque JSON-RPC stdio client wrapping `~/.gemini/antigravity/bin/amed_bulletin_mcp.py`.
2. Zero Credential Exposure: child process reads its own host configuration; `mcp_cli.py` never logs, accepts, or prints raw tokens or keys.
3. Supported CLI subcommands and syntax:
   - `python3 tools/mcp_cli.py call <tool_name> --args-json '<json>'` (or `--args-file /path/to/args.json`)
   - `python3 tools/mcp_cli.py get-record --module mod_news --id <id> --out /path/to/record.json`
   - Positional syntax support (e.g. `python3 tools/mcp_cli.py <server> <tool> [args] [--editorial-safe]` if needed per PROJECT.md interface contract).
4. Policy enforcement via `--editorial-safe` flag (default true):
   - Reject `delete_*` tools unconditionally.
   - On `create_news`, `update_news`, `create_faq`, `update_faq`, `batch_create_faqs`: enforce `status == "n"` (hard error if publishing `"y"`).
   - On `create_news`: enforce `username == "habermerkezi"`.
5. Response envelope handling: standard JSON envelope on stdout; print environment info (`environment.name`) on stderr; exit code 0 on success, non-zero on failure.
6. Verify testability without live external dependencies (e.g. mocking or dry-run validation, fallback behavior).

Write a complete, structured handoff report to `.agents/teamwork/explorer_m3_3/handoff.md`.
Report back when finished via send_message.


## 2026-11-11T12:10:41Z
You are Explorer 3 for Milestone 3 (Swarm CLI Wrapper tools/mcp_cli.py).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_3/

MANDATORY: You MUST read the original user request at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
and your dispatch instructions at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_3/DISPATCH.md
and the milestone scope at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/SCOPE.md
and the project spec at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
and docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md (Sections A2, Phase 3).
Reference pattern: tools/mcp_health_check.py (lines 182-270 StdioBridgeSession).

Investigate and design the exact implementation blueprint for tools/mcp_cli.py:
1. Architecture of tools/mcp_cli.py as a secret-opaque JSON-RPC stdio client wrapping ~/.gemini/antigravity/bin/amed_bulletin_mcp.py.
2. Zero Credential Exposure: child process reads its own host configuration; mcp_cli.py never logs, accepts, or prints raw tokens or keys.
3. Supported CLI subcommands and syntax:
   - python3 tools/mcp_cli.py call <tool_name> --args-json '<json>' (or --args-file /path/to/args.json)
   - python3 tools/mcp_cli.py get-record --module mod_news --id <id> --out /path/to/record.json
   - Positional syntax support (e.g. python3 tools/mcp_cli.py <server> <tool> [args] [--editorial-safe] per PROJECT.md interface contract).
4. Policy enforcement via --editorial-safe flag (default true):
   - Reject delete_* tools unconditionally.
   - On create_news, update_news, create_faq, update_faq, batch_create_faqs: enforce status == "n" (hard error if publishing "y").
   - On create_news: enforce username == "habermerkezi".
5. Response envelope handling: standard JSON envelope on stdout; print environment info (environment.name) on stderr; exit code 0 on success, non-zero on failure.
6. Verify testability without live external dependencies (e.g. mocking or dry-run validation, fallback behavior).

Write a complete, structured handoff report to .agents/teamwork/explorer_m3_3/handoff.md.
When finished, notify me via send_message.
