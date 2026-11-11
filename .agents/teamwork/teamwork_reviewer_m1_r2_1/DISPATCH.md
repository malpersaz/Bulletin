# Reviewer M1-R2-1 Dispatch

Independently review Milestone 1 Iteration 2 remediations across all modified files.


## 2026-11-11T11:56:58Z
You are Reviewer M1-R2-1 for Milestone 1, Iteration 2.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_reviewer_m1_r2_1/

MANDATORY: Read ORIGINAL_REQUEST.md first at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
Also read:
- Milestone Scope: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m1/SCOPE.md
- Project Spec: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
- Gate Status (Iteration 1 Failure): /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m1/GATE_STATUS.md
- Worker M1-2 Handoff: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_worker_m1_2/handoff.md

Review Objectives:
Review all Iteration 2 remediations:
1. `lib/McpFaqTools.class.php:579–584`: Verify `batchCreateFaqs` draft deletion uses `addEqFilter()` for `mod_name`, `vid`, `lang`, and `status = 'n'` so the WHERE clause is strictly scoped and unrelated drafts are not deleted.
2. `lib/McpCrudToolsBase.class.php:742–777`: Verify `makeSeo()` normalizes Unicode NFC first and strips forward slashes `/`, backslashes, and symbols via `[^\p{L}\p{N}\s_-]/u` to prevent broken routing.
3. `lib/McpNewsTools.class.php:981–983`: Verify `updateNews()` checks leaf category whenever `isset($args['category_id'])`.
4. `tools/i18n_guard.py`: Run `python3 tools/i18n_guard.py` and verify 0 errors, 0 warnings.
5. `tasks/mcp_smoke.php`: Run `docker exec bulletin_app php tasks/mcp_smoke.php` (83 passed, 0 failed). Verify test coverage for draft deletion isolation.

Deliverables:
- Write handoff report at `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_reviewer_m1_r2_1/handoff.md`.
- Include explicit verdict: APPROVE or REQUEST_CHANGES.
- Send message back to parent when done.
