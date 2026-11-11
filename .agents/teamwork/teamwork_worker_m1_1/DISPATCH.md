## 2026-11-11T10:59:52Z
[Message] timestamp=2026-11-11T10:59:52Z sender=901f7801-31c7-4c08-be51-94ed6f17c21d priority=MESSAGE_PRIORITY_HIGH content=DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

You are Worker M1-1 for Milestone 1.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_worker_m1_1/

MANDATORY: Read ORIGINAL_REQUEST.md first at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
Also read:
- Milestone Scope: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m1/SCOPE.md
- Project Spec: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
- Reference plan: docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md
- Explorer Hand-offs:
  - /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_1/handoff.md
  - /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_2/handoff.md
  - /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_3/handoff.md

Your Owned Files:
- lib/McpCrudToolsBase.class.php
- lib/McpServer.class.php
- lib/McpNewsTools.class.php
- lib/McpHeadlineTools.class.php
- lib/McpFaqTools.class.php
- lib/McpSurveyTools.class.php (string argument fix)
- docker/nginx/prod.conf
- tools/mcp_health_check.py
- tasks/mcp_smoke.php

Specific Tasks:
1. Feature 1 (B1): In `lib/McpCrudToolsBase.class.php`, implement `protected function addEqFilter($model, string $column, $value): void` (with column regex `/^[a-zA-Z_][a-zA-Z0-9_]*$/` and `$model->addWhere(...)`) and `protected function addLikeSearchFilter($model, array $columns, string $rawSearch): void` (escaping with `\Obuilder::cc()` and `$model->addWhere('(' . implode(' OR ', $clauses) . ')')`). Update `applyCategoryFilters()` in `McpCrudToolsBase.class.php` and `applyFaqFilters()` in `lib/McpFaqTools.class.php` and `applyNewsFilters()` in `lib/McpNewsTools.class.php` to use these helpers.
2. Feature 2 (B2): In `lib/McpServer.class.php`, fix `resolveEnvironment()` to use `$db->fetch_array("SELECT site_status FROM mod_settings LIMIT 1")` and `$db->toArray()` so `site_status` is correctly resolved as `'open'` or `'closed'` instead of crashing with undefined method `fetch()`.
3. Feature 3 (B2): In `lib/McpCrudToolsBase.class.php`, fix `getRecordAuditMeta()` to use `$db->fetch_array(...)` and `$db->toArray()`. Update signature/implementation to accept `$recordStatus = 'y'` (or detect it) and query `mod_faq` with `status IN ('y', 'n')` when record status is `'n'` so draft FAQs on draft news are visible.
4. Feature 4 (B2): In `lib/McpHeadlineTools.class.php` (lines 284, 341) and `lib/McpSurveyTools.class.php` (lines 317, 365), fix `assertContentMatchesLang` calls by passing string `$name` or `$question` instead of `['name' => $name]`.
5. Feature 5 (B3): In `lib/McpNewsTools.class.php`, enforce leaf category check (`root !== 'y'`) in `createNews()` and `updateNews()` matching `lib/Traits/ModuleValidatorTrait.php:77`.
6. Feature 6 (B3): In `lib/McpNewsTools.class.php`, enforce mandatory `self::makeSeo($rawSeo !== '' ? $rawSeo : $name, $lang)` normalization so explicit `seo` parameters are cleaned, normalized with Wikipedia-style casing, spaces converted to underscores, and `U+0307` stripped.
7. Feature 7 (B3): In `lib/McpNewsTools.class.php`, enrich `getNews()` return payload so `$item` includes `username` and `author_fullname` (from `mod_user`), `category_name` and `category_seo` (from `mod_news_category`), and passes `$row['status']` into `getRecordAuditMeta()`.
8. Feature 8 (B3): In `lib/McpNewsTools.class.php`, implement `check_duplicate_news` tool with schema and handler (48h window lookback, headline Jaccard similarity >= 0.55 and exact SEO collision check).
9. Feature 9 (B3): In `lib/McpFaqTools.class.php`, implement `batch_create_faqs` tool with schema and handler (up to 25 items for `mod_name`, `vid`, `lang` with atomic transaction and pre-validation).
10. Feature 10 (B3): In `lib/McpFaqTools.class.php`, implement `list_faq_modules` tool querying `mod_faq_mod` table.
11. Feature 11 (B3): In `lib/McpFaqTools.class.php`, add `include_answer: bool` (default false) to `list_faqs` tool schema and query projection.
12. Feature 12 (A6): In `docker/nginx/prod.conf`, add `location ^~ /.well-known/ { try_files $uri /index.php?$query_string; }` before `location ~ /\.`.
13. Feature 13 (A6): In `tools/mcp_health_check.py`, remove the remote HTTP 403 bypass in Stage 7 so HTTP 403 on `.well-known` endpoints is logged as an explicit failure.
14. Feature 14 (Test Suite): In `tasks/mcp_smoke.php`, update line 676 to strictly reject `'unknown'` for `site_status` (`in_array(..., ['open', 'closed'], true)`), and add new test blocks verifying:
    - conjunctive filtering (multi-filter on list_faqs / list_news)
    - root category rejection on create_news
    - makeSeo normalization of dirty SEO strings
    - enriched get_news envelope
    - draft FAQ auditing on draft news
    - headline creation without TypeError
    - batch_create_faqs
    - list_faq_modules
    - list_faqs include_answer toggle

Verification:
- Run `php -l` on all touched PHP files.
- Run `docker exec bulletin_app php tasks/mcp_smoke.php` (or `php tasks/mcp_smoke.php`).
- Run `python3 tools/code_separation_guard.py`.
- Write your full report to `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_worker_m1_1/handoff.md`.
- Send message to parent when done.
