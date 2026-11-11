# BRIEFING — 2026-11-11T10:45:00Z

## Mission
Investigate PHP backend, MCP tools, database queries, ORM filter clobbering, mod_news schema/templates, and Nginx configs for plan v1.0.63.

## 🔒 My Identity
- Archetype: teamwork_explorer
- Roles: explorer, analyst, investigator
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
- State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_survey_backend_1/
- Original parent: 20708bfb-3a2e-4377-b059-f0289f40dff6
- Milestone: v1.0.63 investigation

## 🔒 Key Constraints
- Read-only investigation — do NOT implement
- Strictly isolated workspace: do NOT touch or sync sibling repos autonomously
- Communication with parent via send_message
- Output handoff.md in state folder

## Current Parent
- Conversation ID: 20708bfb-3a2e-4377-b059-f0289f40dff6
- Updated: 2026-11-11T10:45:00Z

## Investigation State
- **Explored paths**:
  - `lib/Orm/Orm.php`, `lib/Database/Database.php`, `lib/Database/Dbuilder.php`, `lib/Database/Adapter/Mysqli.php`
  - 15 MCP domain tool classes: `lib/McpNewsTools.class.php`, `lib/McpCrudToolsBase.class.php`, `lib/McpFaqTools.class.php`, `lib/McpPublicationTools.class.php`, `lib/McpPageTools.class.php`, `lib/McpAnnouncementTools.class.php`, `lib/McpAttachmentTools.class.php`, `lib/McpDocumentTools.class.php`, `lib/McpHeadlineTools.class.php`, `lib/McpMenuTools.class.php`, `lib/McpPopupTools.class.php`, `lib/McpRedirectTools.class.php`, `lib/McpSettingsTools.class.php`, `lib/McpSliderTools.class.php`, `lib/McpSurveyTools.class.php`, `lib/McpEventTools.class.php`, `lib/McpVideoTools.class.php`
  - `lib/McpServer.class.php` (resolveEnvironment)
  - `mod/mod_news/mod_news.sql`, `mod/mod_news/model/mod_news.model.php`, `mod/mod_news/model/newses.model.php`, `mod/mod_news/index.php`, `templates/amed/index/mod_news/news.twig`, `templates/admin/mod_news/edit.twig`, `templates/admin/mod_news/add.twig`
  - `docker/nginx/prod.conf`
  - `tasks/mcp_smoke.php` and `tools/mcp_health_check.py`
- **Key findings**:
  1. ORM filter clobbering confirmed across 15 MCP classes: `Database::where()` overwrites `$this->_where`. Conjunctive `addWhere()` exists on `Database`. `addEqFilter()` and `addLikeSearchFilter()` in `McpCrudToolsBase` will safely bridge without regressing legacy CMS modules.
  2. Raw PDO `->fetch()` on `Adapter_Mysqli` throws `Error: Call to undefined method mysqli_result::fetch()` in `McpServer::resolveEnvironment()` and `McpCrudToolsBase::getRecordAuditMeta()`. Canonical pattern is `Load::lib('Database')->fetch_array(...)->toArray()`.
  3. `McpHeadlineTools` passes array `['name' => $name]` to `assertContentMatchesLang`, causing fatal `TypeError`.
  4. `McpNewsTools` root category check `root !== 'y'`, mandatory `self::makeSeo()`, and enrichment of `username`/`category_name`/`category_seo` in `get_news` identified.
  5. `McpFaqTools` gaps (`batch_create_faqs`, `list_faq_modules`, `include_answer`) verified.
  6. `mod_news` requires `image_alt` and `image_credit` columns in schema, models (`mod_news_model`, `newses_model`), controller, admin form, and public `news.twig` (`<figure>` + `<figcaption>` + JSON-LD `creditText`).
  7. Nginx `docker/nginx/prod.conf` blocks `/.well-known/` via regex `location ~ /\.`. Fix is prefix location `location ^~ /.well-known/`.
  8. `tools/mcp_health_check.py` Stage 7 masks HTTP 403 on remote production. `tasks/mcp_smoke.php` requires regression tests for multi-filter, site_status, audit metadata, and new FAQ/headline tools.
- **Unexplored areas**: None. All 8 items fully explored with line-level evidence.

## Key Decisions Made
- Confirmed implementation designs for all 8 items.
- Structure handoff.md following 5-component protocol: Observation, Logic Chain, Caveats, Conclusion, Verification Method.

## Artifact Index
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_survey_backend_1/handoff.md — Complete investigation & handoff report
