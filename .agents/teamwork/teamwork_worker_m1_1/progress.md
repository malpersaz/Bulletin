# Progress Heartbeat — teamwork_worker_m1_1

Last visited: 2026-11-11T11:20:00Z
Status: All 14 specific tasks implemented, tested, and verified.
Completed Steps:
- Feature 1 (B1): Implemented `addEqFilter()` and `addLikeSearchFilter()` in `lib/McpCrudToolsBase.class.php`. Updated `applyCategoryFilters()`, `applyFaqFilters()`, and `applyNewsFilters()`.
- Feature 2 (B2): Fixed `resolveEnvironment()` in `lib/McpServer.class.php` to use canonical `Database::fetch_array()` and `Database::toArray()`, resolving `site_status` to `'open'`/`'closed'`.
- Feature 3 (B2): Fixed `getRecordAuditMeta()` in `lib/McpCrudToolsBase.class.php` to use `Database::fetch_array()` and support draft FAQ visibility for draft news (`recordStatus = 'n'`).
- Feature 4 (B2): Fixed `assertContentMatchesLang()` string argument calls in `lib/McpHeadlineTools.class.php` and `lib/McpSurveyTools.class.php`.
- Feature 5 (B3): Enforced selectable leaf category check (`root !== 'y'`) via `assertSelectableNewsCategory()` in `createNews()` and `updateNews()`.
- Feature 6 (B3): Enforced mandatory `self::makeSeo()` normalization on explicit and implicit `seo` arguments in `createNews()` and `updateNews()`.
- Feature 7 (B3): Enriched `getNews()` return payload with `username`, `author_fullname`, `category_name`, and `category_seo`, passing `$row['status']` to audit metadata.
- Feature 8 (B3): Implemented `check_duplicate_news` tool with 48h lookback, Jaccard similarity (>= 0.55), and SEO collision check.
- Feature 9 (B3): Implemented `batch_create_faqs` tool with atomic transaction, pre-validation, intra-batch deduplication, and draft replacement support.
- Feature 10 (B3): Implemented `list_faq_modules` tool querying `mod_faq_mod`.
- Feature 11 (B3): Added `include_answer: bool` parameter to `list_faqs` schema and select projection.
- Feature 12 (A6): Added `location ^~ /.well-known/` to `docker/nginx/prod.conf`.
- Feature 13 (A6): Removed remote HTTP 403 bypass in `tools/mcp_health_check.py` Stage 7.
- Feature 14 (Test Suite): Enhanced `tasks/mcp_smoke.php` with 10 comprehensive Milestone 1 regression tests and strict `site_status` check (82/82 tests pass).
- Verification: `php -l` passed for all touched PHP files, `python3 -m py_compile` passed, `tools/code_separation_guard.py` passed, `tools/module_boundary_guard.py` passed, and `tasks/mcp_smoke.php` passed 100%.
Current Step: Writing handoff report and notifying parent orchestrator.
