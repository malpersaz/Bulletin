# Project: Kevanger Editorial Pipeline Remediation & Enhancement (v1.0.63)

## Architecture
- **MCP Backend & ORM**: `lib/mcp/`, `lib/Orm/`, `lib/Database/`, `docker/nginx/prod.conf`.
- **Shared Transport**: `.agents/skills/_shared/editorial_http.py` (TLS verification, SSRF protection, HTTP caching, Retry-After backoff).
- **Editorial Sentinel & Verification Gates**: `check_freshness.py` (Kevtemen), `verify_facts_and_links.py` (Kevrast).
- **Media Intake & Schema Modernization**: `process_news_image.py` (Kevanger), `mod/mod_news/`, `templates/amed/index/mod_news/news.twig`.
- **CLI Bridge & Radar**: `tools/mcp_cli.py`, `source_catalog.py` (Kevkolin), `audit_seo.py` (Kevseo).
- **Verification & Release**: `tasks/mcp_smoke.php`, `tools/verify_news_pipeline.sh`, quality guards, `docs/VERSION.md`, git tag & push v1.0.63.

## Feature Inventory
| # | Feature | Description | Milestone | Source |
|---|---------|-------------|-----------|--------|
| 1 | `McpCrudToolsBase::addEqFilter` & `addLikeSearchFilter` | Conjunctive filtering via `$model->addWhere()` avoiding B1 filter clobbering | M1 | survey |
| 2 | `McpServer::resolveEnvironment` DB fetch | Use `Database::fetch_array()` instead of undefined PDO `fetch()` on `mysqli_result` | M1 | survey |
| 3 | `McpCrudToolsBase::getRecordAuditMeta` DB fetch & draft FAQs | Use `Database::fetch_array()` and support draft FAQ counting (`recordStatus = 'n'`) | M1 | survey |
| 4 | `McpHeadlineTools` string argument fix | Pass `$name` as string to `assertContentMatchesLang` to prevent TypeError | M1 | survey |
| 5 | `McpNewsTools` leaf category check | Reject root categories (`root === 'y'`) in news staging | M1 | survey |
| 6 | `McpNewsTools` mandatory `makeSeo()` | Enforce Wikipedia-style slug normalization and `U+0307` strip on all slugs | M1 | survey |
| 7 | `McpNewsTools::getNews` enrichment | Return `username`, `author_fullname`, `category_name`, and `category_seo` | M1 | survey |
| 8 | `McpNewsTools::checkDuplicateNews` | Add pre-staging duplicate news check within last 48 hours | M1 | survey |
| 9 | `McpFaqTools::batchCreateFaqs` | Atomic creation of up to 25 FAQs for host record | M1 | survey |
| 10 | `McpFaqTools::listFaqModules` | List valid FAQ host modules from `mod_faq_mod` | M1 | survey |
| 11 | `McpFaqTools::listFaqs` `include_answer` | Add `include_answer: bool` parameter to return full FAQ answers | M1 | survey |
| 12 | Nginx `.well-known` passthrough | Add `location ^~ /.well-known/` to `docker/nginx/prod.conf` for RFC discovery | M1 | survey |
| 13 | Shared HTTP Client `editorial_http.py` | Verified TLS, SSRF guard, standard browser headers, Retry-After backoff, 10m cache | M2 | survey |
| 14 | `check_freshness.py` TLS & SSRF integration | Use `editorial_http` with verified TLS and SSRF protection | M2 | survey |
| 15 | `check_freshness.py` ISO fractional seconds | Parse fractional seconds `(?:[.,]\d+)?` preserving UTC timezone | M2 | survey |
| 16 | `check_freshness.py` non-content tag stripping | Strip `<script>`, `<style>`, `<aside>`, `<nav>`, `<footer>` before date scanning | M2 | survey |
| 17 | `check_freshness.py` earliest main date & deduplication | Select earliest main entity publication date; deduplicate audit URLs | M2 | survey |
| 18 | `check_freshness.py` near-midnight diagnostics | Provide structured `age_hours` and near-midnight diagnostic attributes | M2 | survey |
| 19 | `verify_facts_and_links.py` all-paragraph verification | Evaluate all citing paragraphs across the draft in `audit_draft()` | M2 | survey |
| 20 | `verify_facts_and_links.py` clean number & entity extraction | Strip non-content tags before R3; enforce exact normalized numeric equality | M2 | survey |
| 21 | `verify_facts_and_links.py` vendor single-segment allowlist | Allow defined single-segment vendor download/release pages under criteria | M2 | survey |
| 22 | `verify_facts_and_links.py` cross-language anchoring | Support `source_lang` and `source_anchors` in `dossier.schema.json` | M2 | survey |
| 23 | `verify_facts_and_links.py` Turkish morphology & attribution | Add `tr_fold()` and institutional suffix stemmer; expand attribution phrases | M2 | survey |
| 24 | `process_news_image.py` Next.js CDN proxy unwrap | Unwrap Next.js `/_next/image?url=...` before deduplication | M3 | survey |
| 25 | `process_news_image.py` hero scoring & srcset cap | Restrict hero bonus to primary article elements; cap inflated srcset at 3840w | M3 | survey |
| 26 | `process_news_image.py` `--mode master` | Support clean `>= 1200px` master image output | M3 | survey |
| 27 | `mod_news` `image_alt` & `image_credit` columns | Add schema columns to `mod_news`, model, controller, views | M3 | survey |
| 28 | `news.twig` hero figure & JSON-LD | Render `<figure class="detail-hero">` and `<figcaption>` with schema credits | M3 | survey |
| 29 | `tools/mcp_cli.py` Swarm CLI wrapper | Provide secret-opaque stdio bridge with `--editorial-safe` mutation guards | M3 | survey |
| 30 | `source_catalog.py` `--scan-today` | Support live RSS/Atom/sitemap scanning for same-day local primary recovery | M4 | survey |
| 31 | `audit_seo.py` alignment | Enforce headline 40-70, summary 90-120, description sync, heading hierarchy | M4 | survey |
| 32 | Skill documentation synchronization | Update Kevanger, Kevtemen, Kevkolin, Kevrast, Kevseo SKILL.md docs | M4 | survey |
| 33 | Correct AGENTS.md rule number citations | Fix citations in `kevseo/SKILL.md`, `audit_seo.py`, `code_separation_guard.py`, validators, and `CODE_QUALITY_AGENT_STRATEGY.md` | M4 | survey |
| 34 | Full Regression Suite & MCP Smoke Tests | Update `tasks/mcp_smoke.php` and `tools/verify_news_pipeline.sh` Stage 7 | M5 | survey |
| 35 | Version Documentation Update | Update `docs/VERSION.md` under v1.0.63 and synchronize version parity | M5 | survey |
| 36 | Git Release Tagging & Push | Commit changes, tag release v1.0.63, and push directly to GitHub | M5 | survey |

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| M1 | Critical MCP, ORM Filter & Nginx Fixes | Features 1-12 (MCP filtering, DB fixes, Leaf category, makeSeo, getNews, FAQs, Nginx) | none | DONE (gate passed, 83 smoke passed) |
| M2 | Shared HTTP Client & Editorial Gate Hardening | Features 13-23 (editorial_http.py, check_freshness.py, verify_facts_and_links.py) | none | DONE (gate passed, all tests verified) |
| M3 | Image Pipeline, mod_news Media Credits & Swarm CLI | Features 24-29 (process_news_image.py, mod_news schema/templates, mcp_cli.py) | M1, M2 | DONE (gate passed, all tests verified) |
| M4 | Radar, SEO Alignment, Skill Docs & Rule Fixes | Features 30-33 (source_catalog.py, audit_seo.py, SKILL.md docs, rule citations) | M2, M3 | DONE (gate passed, clean audit) |
| M5 | Verification, Quality Guards, Version Parity & Release | Features 34-36 (mcp_smoke.php, quality guards, docs/VERSION.md, git commit, tag, push) | M1, M2, M3, M4 | DONE (gate passed, commit b4060d39, tag v1.0.63 pushed) |

## Code Layout
- `lib/mcp/McpCrudToolsBase.php`: Core MCP CRUD methods, `addEqFilter()`, `addLikeSearchFilter()`, `getRecordAuditMeta()`.
- `lib/mcp/McpServer.php`: `resolveEnvironment()`.
- `lib/mcp/tools/McpNewsTools.php`: News MCP methods, root check, SEO normalization, getNews envelope, duplicate check.
- `lib/mcp/tools/McpHeadlineTools.php`: Headline MCP methods, type assertion fix.
- `lib/mcp/tools/McpFaqTools.php`: FAQ MCP methods, batch creation, list modules, include_answer.
- `docker/nginx/prod.conf`: Nginx production configuration, `.well-known/` location directive.
- `.agents/skills/_shared/editorial_http.py`: Shared HTTP client with verified TLS and SSRF protection.
- `.agents/skills/kevtemen/scripts/check_freshness.py`: Freshness sentinel script.
- `.agents/skills/kevrast/scripts/verify_facts_and_links.py`: Fact-checking and link provenance script.
- `.agents/skills/kevanger/scripts/process_news_image.py`: News image ingestion script.
- `mod/mod_news/`: Module news database schema (`mod_news.sql`), models, validators, controllers.
- `templates/amed/index/mod_news/news.twig`: Public news article template.
- `templates/admin/mod_news/`: Admin news templates.
- `tools/mcp_cli.py`: Secret-opaque stdio bridge CLI wrapper.
- `.agents/skills/kevkolin/scripts/source_catalog.py`: Source catalog script with `--scan-today`.
- `.agents/skills/kevseo/scripts/audit_seo.py`: SEO audit script aligned with `tools/audit_content_seo.py`.
- `.agents/skills/*/SKILL.md`: Skill instruction files.
- `tasks/mcp_smoke.php`: MCP smoke test suite.
- `tools/mcp_health_check.py`: MCP health check tool.
- `docs/VERSION.md`: Release notes.
- `config/site.config.php`, `config/site.config.example.php`, `docs/SERVER.md`: Version synchronization files.

## Interface Contracts
### McpCrudToolsBase ↔ MCP Tool Classes
- `protected function addEqFilter($model, string $column, $value): void`
- `protected function addLikeSearchFilter($model, array $columns, string $rawSearch): void`

### editorial_http.py ↔ Pipeline Scripts
- `fetch_html(url: str, timeout: int = 15, max_bytes: int = 2_000_000) -> Tuple[int, str, Dict[str, str]]`
- `fetch_binary(url: str, timeout: int = 20, max_bytes: int = 10_000_000) -> Tuple[int, bytes, Dict[str, str]]`
- `validate_public_url(url: str) -> Tuple[bool, str]`

### tools/mcp_cli.py ↔ Workspace Sub-agents
- Command: `python3 tools/mcp_cli.py <server> <tool> [args_json_or_flags] [--editorial-safe]`
- Standard JSON envelope on stdout; exits 0 on success, non-zero on error.
