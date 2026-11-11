## 2026-11-11T10:49:54Z
You are Explorer M1-3 for Milestone 1.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_3/

MANDATORY: Read ORIGINAL_REQUEST.md first at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
Also read:
- Milestone Scope: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m1/SCOPE.md
- Project Spec: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
- Reference plan: docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md

Objective:
Investigate Features 12, Nginx, Health Check, and Smoke Tests:
12. Nginx `.well-known` passthrough (A6):
   - Inspect `docker/nginx/prod.conf` around lines 80-95.
   - Verify where `location ~ /\. { deny all; }` is located.
   - Determine the exact location block:
     `location ^~ /.well-known/ { try_files $uri /index.php?$query_string; }`
     and verify it is placed BEFORE `location ~ /\.`.
13. Health Check Stage 7 unmasking:
   - Inspect `tools/mcp_health_check.py` lines 525-560.
   - Verify how HTTP 403 on `.well-known` endpoints is currently masked as a pass with a warning.
   - Formulate the fix so HTTP 403 is treated as an explicit failure, requiring correct Nginx configuration.
14. Smoke Tests in `tasks/mcp_smoke.php`:
   - Inspect `tasks/mcp_smoke.php` line 675 and existing test blocks.
   - Formulate test cases covering:
     a) `site_status` must be `'open'` or `'closed'` (rejecting `'unknown'`).
     b) Conjunctive filtering: test `list_news` or `list_faqs` with multiple filters simultaneously (`category_id`, `lang`, `status`, `search`) and assert that ALL conditions are respected.
     c) Root category rejection on `create_news` (must throw error when category `root === 'y'`).
     d) SEO normalization: test passing dirty SEO slug with spaces/Turkish chars and verify it normalizes.
     e) Enriched `get_news` envelope: check `username`, `author_fullname`, `category_name`, `category_seo`.
     f) Draft FAQ auditing: verify draft FAQ on draft news is counted in `audit_meta`.
     g) Headline creation without `TypeError`: test `create_headline` / `update_headline`.
     h) `batch_create_faqs`: test creating multiple FAQs in one call.
     i) `list_faq_modules`: test listing modules.
     j) `list_faqs` `include_answer: true` vs `false`.

Deliverables:
- Write comprehensive handoff report at `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_3/handoff.md` with:
  - Observation: exact files, line numbers, and existing code.
  - Logic Chain: exact changes needed for Nginx, health check, and test harness.
  - Fix diff proposals: concrete diffs for `docker/nginx/prod.conf`, `tools/mcp_health_check.py`, and `tasks/mcp_smoke.php`.
  - Verification Method: syntax and execution checks.
- Send message back to parent when done.
