# Task Assignment: Explorer 4 (Iteration 2 Fix Blueprint for tools/mcp_cli.py)

You are Explorer 4 for Milestone 3 (Iteration 2).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_4/

Context:
In Milestone 3 Gate Iteration 1, Challenger 2 reported a defect in `tools/mcp_cli.py`:
Positional syntax `python3 tools/mcp_cli.py amed-bulletin-mcp ping` (and `list-tools`) fails with exit code 1 (`Unknown tool: ping`) because `parse_and_dispatch` blindly maps positional arguments to `call <tool>`.

Read the failure report:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m3_2/handoff.md
and test harness:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/tests/test_m3_2_empirical_stress.py
and target file:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/tools/mcp_cli.py

Your objective:
Formulate the exact fix blueprint for `tools/mcp_cli.py`:
1. In `parse_and_dispatch`: when `<server> <subcommand>` positional syntax is used, detect if `tool_name` matches known subcommands (`ping`, `list-tools`, `get-record`) and route to their respective handlers (`handle_ping`, `handle_list_tools`, `handle_get_record`) instead of converting to `call <tool>`.
2. In `enforce_editorial_policy`: ensure `update_news` is also covered by image file path validation if `image.file_path` is provided.
3. Verify test command `python3 -m unittest tests/test_m3_2_empirical_stress.py -k test_positional_ping`.

Write report to .agents/teamwork/explorer_m3_4/handoff.md and report back via send_message.


## 2026-11-11T12:52:13Z
You are Explorer 4 for Milestone 3 (Iteration 2 Fix Blueprint for tools/mcp_cli.py).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_4/

MANDATORY: You MUST read the original user request at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
and your dispatch instructions at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_4/DISPATCH.md
and Challenger 2's failure report at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m3_2/handoff.md
and the test harness:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/tests/test_m3_2_empirical_stress.py
and target file:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/tools/mcp_cli.py

Formulate the exact fix blueprint for tools/mcp_cli.py so positional syntax `tools/mcp_cli.py <server> ping` and `<server> list-tools` route cleanly to their handlers instead of failing as unknown tools.
Write report to .agents/teamwork/explorer_m3_4/handoff.md and notify me via send_message when complete.
