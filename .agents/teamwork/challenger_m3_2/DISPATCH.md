# Task Assignment: Challenger 2 (CLI Wrapper & Database View Empirical Stress Tester)

You are Challenger 2 for Milestone 3.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m3_2/

Read the following documents:
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/SCOPE.md
- Worker handoff: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m3_1/handoff.md
- Target: tools/mcp_cli.py and mod/mod_news/model/newses.model.php

Your adversarial challenge objectives:
1. Empirically test `tools/mcp_cli.py`:
   - Run unit test suite: `python3 tests/test_mcp_cli.py` (or `pytest tests/test_mcp_cli.py`).
   - Test `--editorial-safe` policy enforcement:
     a) Attempt calling `delete_news` under `--editorial-safe` -> MUST abort with non-zero exit code.
     b) Attempt calling `create_news` or `update_news` with `status: "y"` under `--editorial-safe` -> MUST reject or force `status: "n"`.
     c) Attempt calling `create_news` with `username: "admin"` -> MUST reject or force `username: "habermerkezi"`.
     d) Attempt calling with missing local image file -> MUST reject.
   - Test stdio envelope and stderr token redaction: verify API keys matching `amcp_*` are scrubbed from stderr.
   - Test positional invocation syntax: `python3 tools/mcp_cli.py amed-bulletin-mcp ping`.
2. Empirically test database view self-healing migration:
   - Verify `newses.model.php` `ensureTableColumns()` logic.
3. Render a clear empirical verdict: APPROVE or REQUEST_CHANGES.

Write your report to .agents/teamwork/challenger_m3_2/handoff.md.
Notify me via send_message when finished.


## 2026-11-11T12:42:40Z
You are Challenger 2 for Milestone 3 (CLI Wrapper & Database View Empirical Stress Tester).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m3_2/

MANDATORY: You MUST read the original user request at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
and your dispatch instructions at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m3_2/DISPATCH.md
and the milestone scope at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/SCOPE.md
and the project spec at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
and the worker handoff at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m3_1/handoff.md

Empirically test tools/mcp_cli.py and database view migration:
1. Run test suite: python3 -m unittest tests/test_mcp_cli.py (or pytest).
2. Empirically test --editorial-safe policy enforcement:
   - Call delete_news under --editorial-safe -> must fail/abort.
   - Call create_news or update_news with status="y" -> must reject or enforce "n".
   - Call create_news with username="admin" -> must reject or enforce "habermerkezi".
   - Call with non-existent local image file -> must reject.
   - Test stderr token scrubbing for amcp_*.
   - Test positional syntax: tools/mcp_cli.py amed-bulletin-mcp ping.
3. Test database view self-healing migration in newses.model.php.
Render a clear empirical verdict: APPROVE or REQUEST_CHANGES.
Write report to .agents/teamwork/challenger_m3_2/handoff.md and notify me via send_message when complete.
