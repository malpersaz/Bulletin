Implement the Kevanger editorial pipeline remediation and enhancement plan in docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md, ensuring no regressions occur across the system, update docs/VERSION.md under v1.0.63 with full change notes, and tag and push the new version directly to GitHub.

Integrity mode: demo
The team must NOT: copy code from existing open-source projects for core logic

## Requirements

### R1. Kevanger Editorial Pipeline Remediation Plan Implementation
Implement all remediations and enhancements specified in docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md across Phases 1 through 4:
- Phase 1 (Critical MCP, ORM Filter & Nginx Fixes): Resolve B1 (conjunctive filtering with addEqFilter and parenthesized addLikeSearchFilter in MCP classes), B2 (Adapter_Mysqli database fetching in McpServer and McpCrudToolsBase, draft FAQ visibility, and McpHeadlineTools string argument), B3 (McpNewsTools root category check, mandatory makeSeo normalization, enriched get_news attribution, McpFaqTools batch_create_faqs, list_faq_modules, list_faqs include_answer), and A6 (Nginx location ^~ /.well-known/ configuration and mcp_health_check Stage 7).
- Phase 2 (Shared HTTP Client & Editorial Gate Hardening): Implement .agents/skills/_shared/editorial_http.py (verified TLS, SSRF guard, standard headers, Retry-After backoff, and URL response cache) and update check_freshness.py and verify_facts_and_links.py per A1, A4, and B4.
- Phase 3 (Image Pipeline, mod_news Media Credits & Swarm CLI Wrapper): Update process_news_image.py per A3 and B5, add image_alt and image_credit to mod_news, newses view, and templates/amed/index/mod_news/news.twig, and create tools/mcp_cli.py per A2.
- Phase 4 (kevkolin Radar, kevseo Alignment, Skill Docs & Regression Suite): Implement --scan-today in source_catalog.py (A1, B6), align audit_seo.py with audit_content_seo.py (B6), update skill documentation, and correct AGENTS.md rule number citations.
Maintain strict vigilance during implementation to prevent side effects or regressions elsewhere in the codebase.

### R2. Version Documentation Update
Update docs/VERSION.md under version v1.0.63 comprehensively, documenting all fixes, additions, and enhancements implemented without omission. Maintain version parity across config/site.config.php, config/site.config.example.php, docs/SERVER.md, and docs/VERSION.md.

### R3. Version Tagging and GitHub Push
Create the Git commit for v1.0.63, tag the release with v1.0.63, and push the commit and tag directly to GitHub.

## Acceptance Criteria

### Editorial Pipeline & MCP Remediation
- [ ] Multi-parameter filters in MCP tool classes combine conjunctively via $model->addWhere(...) using addEqFilter() and parenthesized addLikeSearchFilter(), resolving the B1 filter clobbering bug.
- [ ] McpServer::resolveEnvironment() and McpCrudToolsBase::getRecordAuditMeta() retrieve results using canonical database methods without invoking undefined methods on mysqli_result, exposing correct site_status, lang_status, and draft FAQ counts for staged news items.
- [ ] McpHeadlineTools passes strings to assertContentMatchesLang().
- [ ] McpNewsTools blocks staging news into root categories (root = 'y'), always normalizes SEO slugs via self::makeSeo(), and enriches get_news with username, category_name, and category_seo.
- [ ] McpFaqTools provides batch_create_faqs, list_faq_modules, and supports include_answer: bool on list_faqs.
- [ ] docker/nginx/prod.conf routes /.well-known/ requests without 403 Forbidden denial.
- [ ] .agents/skills/_shared/editorial_http.py is implemented with verified TLS, SSRF protection, standard headers, Retry-After retry logic, and request caching.
- [ ] check_freshness.py strips non-content tags before date scanning, parses ISO fractional seconds, selects earliest main-entity publication date, deduplicates draft audit links, and provides near-midnight age diagnostics.
- [ ] verify_facts_and_links.py evaluates all citing blocks across the draft, strips non-content tags before entity/numeric extraction, enforces exact normalized numeric equality, supports single-segment vendor download/release pages under defined criteria, enables cross-language anchoring via dossier source_anchors, supports Turkish agglutinative suffixes, and expands R4 attribution detection.
- [ ] process_news_image.py unwraps CDN/Next.js image URLs before deduplication, scopes hero image bonus to primary article elements, caps inflated srcset widths, and supports --mode master.
- [ ] mod_news and news.twig support image_alt and image_credit with <figcaption> and JSON-LD ImageObject.creditText.
- [ ] tools/mcp_cli.py provides safe, secret-opaque stdio access for workspace scripts and sub-agents.
- [ ] source_catalog.py supports --scan-today for RSS/sitemap feeds.
- [ ] audit_seo.py enforces headline length (40–70 chars), summary length (90–120 chars), meta description sync, and heading hierarchy.
- [ ] Stale AGENTS.md rule references are corrected.

### Verification & Quality Guards
- [ ] All modified PHP files pass php -l.
- [ ] All modified Python files pass python3 -m py_compile.
- [ ] tools/verify_news_pipeline.sh passes bash -n.
- [ ] tasks/mcp_smoke.php passes with assertions covering multi-parameter filtering, environment status, batch_create_faqs, list_faq_modules, and include_answer.
- [ ] tools/i18n_guard.py passes for all touched files.
- [ ] tools/code_separation_guard.py passes (zero inline CSS or JS).
- [ ] tools/module_boundary_guard.py --all passes.
- [ ] tools/min_css_guard.py passes.
- [ ] All code, comments, documentation, and skill files are in English per repository rules.

### Release & Deployment
- [ ] docs/VERSION.md is updated with full changelog under v1.0.63.
- [ ] Version 1.0.63 is synchronized across config/site.config.php, config/site.config.example.php, docs/SERVER.md, and docs/VERSION.md.
- [ ] Git commit and tag v1.0.63 are created and pushed to GitHub.
