# BRIEFING — 2026-11-11T12:59:30Z

## Mission
Formulate exact fix blueprint for tools/mcp_cli.py addressing positional routing defects and update_news image policy enforcement.

## 🔒 My Identity
- Archetype: explorer
- Roles: investigation, synthesis
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
- State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_4/
- Original parent: 1cb4034b-124b-423c-b681-70f423565312
- Milestone: Milestone 3 (Iteration 2)

## 🔒 Key Constraints
- Read-only investigation — do NOT implement / modify source code directly
- Positional syntax routing for `<server> ping`, `<server> list-tools`, and `<server> get-record`
- Policy validation check for `update_news` image file path
- Self-contained 5-component handoff report

## Current Parent
- Conversation ID: 1cb4034b-124b-423c-b681-70f423565312
- Updated: 2026-11-11T12:59:30Z

## Investigation State
- **Explored paths**:
  - `tools/mcp_cli.py` (lines 150-230, lines 565-681)
  - `tests/test_m3_2_empirical_stress.py` (lines 1-388)
  - `.agents/teamwork/challenger_m3_2/handoff.md`
  - `.agents/teamwork/ORIGINAL_REQUEST.md`
  - `~/.gemini/antigravity/bin/amed_bulletin_mcp.py`
- **Key findings**:
  1. `test_positional_ping` in `tests/test_m3_2_empirical_stress.py` failed with code 1 (`Unknown tool: ping`) because `parse_and_dispatch` blindly routed any positional argument to `call <tool>`.
  2. Subcommands `ping` and `list-tools` require JSON-RPC methods `ping` and `tools/list`, not `tools/call`.
  3. `argparse` rejects global options (`--bridge`, `--timeout`) if placed after subcommands without being declared on the subcommand parser; global flags must be separated and prepended before the subcommand token.
  4. `enforce_editorial_policy` only checked `image.file_path` on `create_news`, leaving `update_news` unvalidated.
- **Unexplored areas**: None. Complete evidence chain established and verified.

## Key Decisions Made
- Prepared clean git patch at `.agents/teamwork/explorer_m3_4/tools_mcp_cli.patch` verified with `git apply --check`.
- Verified 24/24 empirical stress tests pass with proposed blueprint.

## Artifact Index
- `.agents/teamwork/explorer_m3_4/DISPATCH.md` — Dispatch instructions log
- `.agents/teamwork/explorer_m3_4/BRIEFING.md` — Persistent context & identity
- `.agents/teamwork/explorer_m3_4/progress.md` — Heartbeat and status
- `.agents/teamwork/explorer_m3_4/tools_mcp_cli.patch` — Unified diff patch for tools/mcp_cli.py
- `.agents/teamwork/explorer_m3_4/handoff.md` — 5-component handoff report
