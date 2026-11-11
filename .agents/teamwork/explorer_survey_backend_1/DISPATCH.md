# Explorer Survey Backend Task

Investigate MCP classes, ORM filters, Nginx config, mod_news schema/templates, and tasks/mcp_smoke.php.


## 2026-11-11T10:37:26Z
[Message] timestamp=2026-11-11T10:37:26Z sender=20708bfb-3a2e-4377-b059-f0289f40dff6 priority=MESSAGE_PRIORITY_HIGH content=You are a teamwork_explorer.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_survey_backend_1/
MANDATORY: You MUST read the original user request at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md

Task:
Investigate the PHP backend, MCP tools, database queries, and Nginx configurations referenced in docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md:
Specifically examine:
1. MCP tool classes (lib/mcp/McpServer.php, lib/mcp/McpCrudToolsBase.php, lib/mcp/tools/McpNewsTools.php, lib/mcp/tools/McpHeadlineTools.php, lib/mcp/tools/McpFaqTools.php, and any other MCP tools where filter methods addWhere / addEqFilter / addLikeSearchFilter are used).
2. Existing filter implementations: how addWhere is called, how clobbering occurs when multiple filters are applied, how $model->addEqFilter and parenthesized addLikeSearchFilter should be implemented in model / base classes or MCP classes.
3. Database handling in McpServer::resolveEnvironment() and McpCrudToolsBase::getRecordAuditMeta() (look at Adapter_Mysqli vs mysqli_result methods, num_rows vs fetchRow vs query).
4. McpNewsTools: root category check (root = 'y'), SEO normalization via makeSeo(), enrichment in get_news (username, category_name, category_seo).
5. McpFaqTools: batch_create_faqs, list_faq_modules, list_faqs include_answer parameter.
6. mod_news changes: adding image_alt and image_credit to schema / model / controller / views (templates/amed/index/mod_news/news.twig, templates/admin/mod_news/newses.twig or form).
7. Nginx config: docker/nginx/prod.conf (location ^~ /.well-known/).
8. tasks/mcp_smoke.php and mcp_health_check Stage 7.

Note: You are READ-ONLY. Do NOT modify any code.
Write your analysis to:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_survey_backend_1/handoff.md
Update your progress.md periodically.
When finished, notify me via send_message with a summary and reference to handoff.md.
