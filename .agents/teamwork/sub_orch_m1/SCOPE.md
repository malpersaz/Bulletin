# Scope: Milestone 1 — Critical MCP, ORM Filter & Nginx Fixes

## Status: DONE

## Scope Overview
Implement Phase 1 critical backend fixes:
1. McpCrudToolsBase::addEqFilter & addLikeSearchFilter (B1)
2. McpServer::resolveEnvironment & McpCrudToolsBase::getRecordAuditMeta Adapter_Mysqli database fetching & draft FAQ visibility (B2)
3. McpHeadlineTools string argument to assertContentMatchesLang (B2)
4. McpNewsTools root category check (root !== 'y'), mandatory makeSeo normalization, enriched get_news envelope, check_duplicate_news (B3)
5. McpFaqTools batch_create_faqs, list_faq_modules, list_faqs include_answer: bool (B3)
6. docker/nginx/prod.conf location ^~ /.well-known/ passthrough (A6) and tools/mcp_health_check.py Stage 7 unmasking HTTP 403
7. tasks/mcp_smoke.php enhancements covering all new endpoints, filtering, and methods.

## Files Owned & Modified
- lib/McpCrudToolsBase.class.php
- lib/McpServer.class.php
- lib/McpNewsTools.class.php
- lib/McpHeadlineTools.class.php
- lib/McpFaqTools.class.php
- lib/McpSurveyTools.class.php
- docker/nginx/prod.conf
- tools/mcp_health_check.py
- tools/i18n_guard.py
- tasks/mcp_smoke.php

## Acceptance Criteria & Results
- [x] All modified PHP files pass php -l.
- [x] tasks/mcp_smoke.php passes with 83 assertions for conjunctive filtering, site_status, draft FAQs, root category block, makeSeo normalization, get_news envelope, batch_create_faqs, list_faq_modules, and include_answer.
- [x] docker/nginx/prod.conf has location ^~ /.well-known/ before location ~ /\.
- [x] tools/mcp_health_check.py treats HTTP 403 on .well-known endpoints as failure (no masking).
- [x] All quality guards pass:
  - code_separation_guard.py: 0 violations
  - module_boundary_guard.py: 0 violations
  - i18n_guard.py: 0 errors, 0 warnings
- [x] Forensic audit: CLEAN (no stubs, zero cheating, all genuine).
- [x] Gate verdict: PASS.
