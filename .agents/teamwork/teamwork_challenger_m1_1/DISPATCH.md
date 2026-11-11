# Challenger M1-1 Dispatch

Empirically test Milestone 1 implementation, edge cases, and smoke assertions.


## 2026-11-11T11:20:02Z
You are Challenger M1-1 for Milestone 1.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_challenger_m1_1/

MANDATORY: Read ORIGINAL_REQUEST.md first at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
Also read:
- Milestone Scope: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m1/SCOPE.md
- Project Spec: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
- Worker Handoff: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_worker_m1_1/handoff.md

Challenger Objectives:
Empirically stress-test the new functionality:
1. Conjunctive filtering stress test: Write and run a standalone PHP script or CLI invocation that runs `list_faqs` with combinations of `mod_name`, `vid`, `lang`, `status`, and `search`. Assert that adding each filter narrows or maintains results rather than replacing preceding WHERE clauses.
2. Draft FAQ auditing on draft news: Create a draft news article and a draft FAQ attached to it via MCP. Call `get_news` and verify `audit_meta` reflects `faq_count = 1` and `faq_draft_count = 1` while `status = 'n'`.
3. Category root guard: Test `create_news` with root category ID (e.g. ID 17 in Turkish) and verify it throws an actionable McpToolException listing child categories.
4. Dirty SEO slug stress test: Call `create_news` or `makeSeo()` with diverse characters (`"Diyarbakır Haber: Test / '123'"` with spaces, combining `\u{0307}`, Turkish I/ı) and verify the resulting SEO slug is Wikipedia-compliant without raw spaces or invalid characters.
5. `batch_create_faqs` transaction integrity: Test batch creation with 1 valid and 1 invalid item (e.g. invalid language). Verify that zero items are committed and database rolls back completely.
6. Verify existing smoke suite: `docker exec bulletin_app php tasks/mcp_smoke.php`.

Deliverables:
- Write handoff report at `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_challenger_m1_1/handoff.md`.
- Explicit verdict: APPROVE or REQUEST_CHANGES.
- Send message back to parent when done.
