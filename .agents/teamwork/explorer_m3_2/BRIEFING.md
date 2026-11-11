# BRIEFING — 2026-11-11T12:20:00Z

## Mission
Investigate mod_news schema, model, controller, validator, and template files to produce a detailed implementation blueprint for image_alt and image_credit fields.

## 🔒 My Identity
- Archetype: explorer
- Roles: investigator, analyzer, synthesizer
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
- State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_2/
- Original parent: 1cb4034b-124b-423c-b681-70f423565312
- Milestone: Milestone 3 (mod_news Media Credits & Templates)

## 🔒 Key Constraints
- Read-only investigation — do NOT implement source code modifications
- Strict code separation: zero inline styles (style="...") or inline scripts in templates/PHP
- Admin mobile-first surface rule (phone-only mobile viewport contract)
- i18n English keys in en.php
- Output handoff report to .agents/teamwork/explorer_m3_2/handoff.md
- Output messages via send_message to caller (1cb4034b-124b-423c-b681-70f423565312)

## Current Parent
- Conversation ID: 1cb4034b-124b-423c-b681-70f423565312
- Updated: 2026-11-11T12:10:33Z

## Investigation State
- **Explored paths**:
  - `mod/mod_news/mod_news.sql` (schema definitions)
  - `mod/mod_news/model/mod_news.model.php` (ORM model)
  - `mod/mod_news/model/newses.model.php` (view model & automatic view recreation)
  - `mod/mod_news/index.php` (controller, text entity cleaning, datatables)
  - `lib/McpNewsTools.class.php` (MCP CRUD tools, JSON-RPC schemas, getNews enrichment)
  - `mod/mod_news/validator/add.validator.php` & `edit.validator.php` (form validation)
  - `mod/mod_news/lang/*.php` (translation catalogs)
  - `templates/admin/mod_news/add.twig` & `edit.twig` (mobile admin forms)
  - `templates/admin/partials/wizard_content.twig` & `templates/admin/assets/js/script.js` (mobile add wizard)
  - `mod/index/helper/mod_news.helper.php` (public news view controller)
  - `templates/amed/index/mod_news/news.twig` (public news template, hero figure, JSON-LD)
  - `templates/amed/static/css/detail-foundation.css` (CSS styling for hero & caption)
  - Quality guard scripts: `code_separation_guard.py`, `i18n_guard.py`, `module_boundary_guard.py`
- **Key findings**:
  - `mod_news` table and `newses` view currently lack `image_alt` and `image_credit` columns.
  - `newses.model.php` has an `ensureViewExists()` method that caches the view unless specific column checks fail. Auto-migration via `ensureTableColumns()` and new checks `hasImageAltColumn()` / `hasImageCreditColumn()` are required.
  - `cleanNewsTextEntities` in `mod_news/index.php` must sanitize both fields with `strip_tags()`, `html_entity_decode()`, truncate to 255 chars, and map empty strings to `null`.
  - `cleanEntityFields` in `mod_news/index.php` and `_cleanNewsRow` in `mod_news.helper.php` must decode entities for edit form display and public template rendering.
  - Admin form inputs in `edit.twig` must use `.m3-input-wrapper` and `.m3-input-lg` with zero inline CSS/JS.
  - Public template `news.twig` must upgrade `.detail-hero` to `<figure class="detail-hero">`, render `alt="{{ d.image_alt|default(d.name) }}"`, render `<figcaption class="news-hero-caption">{{ d.image_credit }}</figcaption>` conditionally, and update 3 responsive Schema.org JSON-LD `ImageObject` entries with `caption` and `creditText`.
- **Unexplored areas**: None. All components in the dispatch scope are fully audited.

## Key Decisions Made
- Fully specified self-healing schema alteration in `newses.model.php` so existing database containers automatically gain the columns without manual SQL execution.
- Verified all quality guards against the proposed changes to ensure 100% compliance.

## Artifact Index
- .agents/teamwork/explorer_m3_2/BRIEFING.md — Persistent working memory
- .agents/teamwork/explorer_m3_2/DISPATCH.md — Task assignment and message log
- .agents/teamwork/explorer_m3_2/progress.md — Heartbeat and progress log
- .agents/teamwork/explorer_m3_2/handoff.md — 5-component handoff report
