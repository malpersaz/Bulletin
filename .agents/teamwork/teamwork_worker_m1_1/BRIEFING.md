# BRIEFING — 2026-11-11T11:20:00Z

## Mission
Implement Milestone 1 features B1, B2, B3, A6, and test suite additions in Bulletin's MCP subsystem.

## 🔒 My Identity
- Archetype: worker
- Roles: implementer, qa, specialist
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
- State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_worker_m1_1
- Original parent: 901f7801-31c7-4c08-be51-94ed6f17c21d
- Milestone: Milestone 1 (MCP Bug Fixes, Tool Enhancements & Verification)

## 🔒 Key Constraints
- DO NOT CHEAT. All implementations genuine. No dummy implementations, no hardcoded test results.
- Strict Code Separation Mandate (no inline CSS/JS).
- Zero autonomous cross-repo sync.
- English only in code, docs, git commit messages.
- Admin mobile-first surface rule (phone-only).
- Wikipedia-style SEO URLs (preserve casing/accents, spaces to underscores, strip combining dot above U+0307).
- Owned files: lib/McpCrudToolsBase.class.php, lib/McpServer.class.php, lib/McpNewsTools.class.php, lib/McpHeadlineTools.class.php, lib/McpFaqTools.class.php, lib/McpSurveyTools.class.php, docker/nginx/prod.conf, tools/mcp_health_check.py, tasks/mcp_smoke.php.

## Current Parent
- Conversation ID: 901f7801-31c7-4c08-be51-94ed6f17c21d
- Updated: 2026-11-11T11:20:00Z

## Task Summary
- **What to build**: 14 specific tasks across MCP CRUD tools, server environment resolution, headline/survey lang checks, news leaf category validation, makeSeo normalization, getNews enrichment, duplicate news check, batch FAQ creation, FAQ modules listing, include_answer FAQ projection, nginx well-known route, health check remote 403 bypass removal, and mcp_smoke.php comprehensive tests.
- **Success criteria**: All 14 tasks implemented genuine, php -l passes, php tasks/mcp_smoke.php passes all checks (82 passed, 0 failed), tools/code_separation_guard.py passes, handoff.md written.
- **Interface contracts**: SCOPE.md, PROJECT.md
- **Code layout**: lib/ for PHP classes, docker/nginx for config, tools/ for python scripts, tasks/ for cli scripts.

## Key Decisions Made
- Used `addWhere()` via `addEqFilter()` and `addLikeSearchFilter()` in `McpCrudToolsBase` to guarantee conjunctive SQL queries across `applyCategoryFilters()`, `applyFaqFilters()`, and `applyNewsFilters()`.
- Standardized database queries on `Database::fetch_array()` and `Database::toArray()` across `McpServer::resolveEnvironment()` and `McpCrudToolsBase::getRecordAuditMeta()` for MySQLi adapter compatibility.
- Enforced leaf category check with dynamic helpful hints listing valid child categories when root containers are specified.
- Enforced strict `makeSeo()` normalization preserving Wikipedia casing and accents while converting spaces to underscores.
- Implemented atomic transactions with rollback on `batch_create_faqs`.

## Artifact Index
- DISPATCH.md — assignment details
- BRIEFING.md — working memory
- progress.md — liveness heartbeat
- handoff.md — final handoff report

## Change Tracker
- **Files modified**:
  - `lib/McpCrudToolsBase.class.php`: Added `addEqFilter()`, `addLikeSearchFilter()`, updated `applyCategoryFilters()`, fixed `getRecordAuditMeta()` with draft status support, updated `assertRecordMatchesLang()` return type.
  - `lib/McpServer.class.php`: Fixed `resolveEnvironment()` with `Database::fetch_array()`.
  - `lib/McpNewsTools.class.php`: Added `assertSelectableNewsCategory()`, mandatory `makeSeo()`, enriched `getNews()`, added `check_duplicate_news` tool, and conjunctive `applyNewsFilters()`.
  - `lib/McpHeadlineTools.class.php`: Passed string to `assertContentMatchesLang()`.
  - `lib/McpSurveyTools.class.php`: Passed string to `assertContentMatchesLang()`.
  - `lib/McpFaqTools.class.php`: Added `batch_create_faqs` tool, `list_faq_modules` tool, `include_answer: bool` to `list_faqs`, and conjunctive `applyFaqFilters()`.
  - `docker/nginx/prod.conf`: Added `location ^~ /.well-known/`.
  - `tools/mcp_health_check.py`: Removed remote 403 bypass in Stage 7.
  - `tasks/mcp_smoke.php`: Added Milestone 1 tests in Section 6.3 and tightened Section 7 `site_status` validation (82/82 pass).
- **Build status**: PASS (all 82 smoke tests pass; php -l passes on all files)
- **Pending issues**: None

## Quality Status
- **Build/test result**: PASS (82/82 passed in `tasks/mcp_smoke.php`)
- **Lint status**: 0 violations on modified files
- **Tests added/modified**: 15 new test assertions in `tasks/mcp_smoke.php`

## Loaded Skills
- None
