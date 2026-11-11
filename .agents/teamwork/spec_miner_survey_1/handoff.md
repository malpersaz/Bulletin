# Specification Mining Report: Kevanger Editorial Pipeline Remediation & Enhancement Plan (v1.0.63)

**Author:** `teamwork_spec_miner`  
**State Directory:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/spec_miner_survey_1/`  
**Target Plan:** `docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md`  
**Original Request:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md`  
**Repository Working Directory:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**Target Release Version:** `v1.0.63` (Current Baseline: `v1.0.62`)  

---

## 1. Observation

Direct code and test observations executed against the codebase:

1. **Plan & User Request Source:**
   - `ORIGINAL_REQUEST.md:5`: *"Implement the Kevanger editorial pipeline remediation and enhancement plan in docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md, ensuring no regressions occur across the system, update docs/VERSION.md under v1.0.63 with full change notes, and tag and push the new version directly to GitHub."*
   - `docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md:1-544`: 81,158 bytes specifying a 4-phase architecture remediation across 6 live execution bottlenecks (A1–A6) and 6 systemic framework/MCP defects (B1–B6).

2. **ORM Sequential Filter Clobbering (B1):**
   - `lib/Orm/Orm.php:111-125`: `_call_where($result, $value)` calls `$this->where($where);`.
   - `lib/Database/Database.php:603-611`: `Database::where($arg)` executes `$this->_where = Dbuilder::where($arg);`, which overwrites any previous `_where` clause.
   - `lib/Database/Database.php:613-625`: `Database::addWhere($arg, $condition = 'AND')` is the conjunctive builder method (`$this->_where = $this->_where . " " . $condition . " " . Dbuilder::where($arg);`).
   - `lib/McpNewsTools.class.php:1252-1272`: `applyNewsFilters()` chains `where_category_id()`, `where_lang()`, `where_status()`, `where_show_on_homepage()`, `where_seo()`, and `where('name LIKE ...')`. Because each call invokes `where()`, only the last active filter survives.
   - Observed across 15 MCP tool classes (`McpNewsTools`, `McpCrudToolsBase`, `McpFaqTools`, `McpPublicationTools`, `McpPageTools`, `McpAnnouncementTools`, `McpAttachmentTools`, `McpDocumentTools`, `McpHeadlineTools`, `McpMenuTools`, `McpPopupTools`, `McpRedirectTools`, `McpSettingsTools`, `McpSliderTools`, `McpSurveyTools`).

3. **`Adapter_Mysqli` PDO Syntax Fatal Error & Headline TypeError (B2):**
   - `lib/McpServer.class.php:138`: `$row = $db->query("SELECT site_status FROM mod_settings LIMIT 1")->fetch();` calls `fetch()` on `mysqli_result` (which only implements `fetch_assoc()`, `fetch_row()`, `fetch_all()`), throwing `Error: Call to undefined method mysqli_result::fetch()`, caught by line 151 `catch (\Throwable)`, causing `site_status` to always return `'unknown'`.
   - `lib/McpCrudToolsBase.class.php:792, 799, 805`: `$langRow = $db->query(...)->fetch()` and `$faqRes->fetchAll()` / `$faqRes->fetch()` throw or return `false` on `mysqli_result`, causing `getRecordAuditMeta()` to silently fall back to `['lang_status' => 'n', 'faq_count' => 0, 'faq_items' => []]`.
   - `lib/McpHeadlineTools.class.php:284, 341`: `$this->assertContentMatchesLang($lang, ['name' => $name])` passes an `array` to `assertContentMatchesLang(string $lang, string $name, ...)`, causing a fatal `TypeError`.

4. **Category Leaf Guard, makeSeo & Enriched get_news (B3):**
   - `lib/McpNewsTools.class.php:710, 871`: `createNews` and `updateNews` check `assertRecordMatchesLang('mod_news_category', $categoryId, $lang, 'Category')` but do not check `($record->root ?? 'n') === 'y'`.
   - `lib/McpNewsTools.class.php:725-728, 894-898`: `self::makeSeo($name, $lang)` is only called when `$seo === ''`. Explicit unnormalized slugs pass straight through without Wikipedia-style `legacy_seo` normalization or `U+0307` strip.
   - `lib/McpNewsTools.class.php:672-686`: `getNews` loads from base `mod_news` and lacks `username`, `category_name`, and `category_seo`.
   - `lib/McpFaqTools.class.php:261`: `listFaqs` omits `answer` from `$model->select(...)`, requiring `N` additional queries to audit answers.

5. **Nginx Dotfile Blocking RFC Discovery Endpoints (A6):**
   - `docker/nginx/prod.conf:84-88`:
     ```nginx
     location ~ /\. {
         deny all;
         access_log off;
         log_not_found off;
     }
     ```
   - Running `python3 tools/mcp_health_check.py` observed Stage 7 output:
     `✓ RFC 8414 discovery: HTTP 403 on remote production server prior to v1.0.61 .htaccess deployment (https://amedbulten.com/.well-known/oauth-authorization-server)`
     `✓ RFC 9728 discovery: HTTP 403 on remote production server prior to v1.0.61 .htaccess deployment (https://amedbulten.com/.well-known/oauth-protected-resource)`
     HTTP 403 is returned and currently masked as non-blocking.

6. **Kevtemen & Kevrast Vulnerabilities and Gaps (A1, A4, B4):**
   - `.agents/skills/kevtemen/scripts/check_freshness.py:34-36`: `_SSL_CTX.check_hostname = False; _SSL_CTX.verify_mode = ssl.CERT_NONE` disables TLS verification.
   - `.agents/skills/kevtemen/scripts/check_freshness.py:50-52`: `RE_ISO` regex does not match fractional seconds `(?:[.,]\d+)?`, causing `.000Z` timestamps to lose UTC timezone and be treated as naive Istanbul time (`+03:00`), false-blocking late UTC articles.
   - `.agents/skills/kevtemen/scripts/check_freshness.py:64, 160`: `TAG_RE.sub(" ", html)` strips tags without removing `<script>` or `<style>` blocks; `<time>` in sidebar ticker and nav `"Bugün"` cause false passes.
   - `.agents/skills/kevtemen/scripts/check_freshness.py:406-433`: `draft_hrefs = HREF_RE.findall(draft)` fetches and checks URLs repeatedly without deduplication.
   - `.agents/skills/kevrast/scripts/verify_facts_and_links.py:270-277, 348`: `_find_citing_block(u, text)` only validates the first paragraph containing a URL. Hallucinated numbers in subsequent paragraphs pass silently with exit code 0.
   - `.agents/skills/kevrast/scripts/verify_facts_and_links.py:178-183`: `len(segments) <= 1` unconditionally classifies single-segment URLs (e.g. `/download`, `/releases`) as `section_front` (R2 FAIL).
   - `.agents/skills/kevrast/scripts/verify_facts_and_links.py:224`: `n == t or n in t` allows substring digits inside unrelated numbers.
   - `.agents/skills/kevrast/scripts/verify_facts_and_links.py:47-53`: `CITATION_PHRASE_RE` misses common journalistic attribution expressions such as `"paylaşılan bilgilere göre"`, `"analize göre"`, `"sürüm notlarına göre"`, `"kaynağında"`.

7. **Image Ingestion Collision & Schema Modernization (A3, B5):**
   - `.agents/skills/kevanger/scripts/process_news_image.py:303`: `u = c["url"].split("?")[0].rstrip("/")` turns all Next.js `/_next/image?url=...` URLs into `https://rudaw.net/_next/image`, collapsing 54 candidates into 1 and selecting an unrelated sidebar photo.
   - `mod/mod_news/mod_news.sql:1-30`: `mod_news` lacks dedicated `image_alt` and `image_credit` columns.
   - `templates/amed/index/mod_news/news.twig:412-416`: Hero image is inside `<div class="detail-hero">` without `<figure>`, without `image_alt`, and without `<figcaption>` or Schema.org `caption`/`creditText`.

8. **Radar & SEO Rubric Drift (A1, B6):**
   - `.agents/skills/kevkolin/scripts/source_catalog.py`: Lacks `--scan-today` live RSS/Atom/sitemap scanning for same-day local primary recovery.
   - `.agents/skills/kevseo/scripts/audit_seo.py`: Treats 0 `<h2>` as warning instead of error, skips headline/summary checks if omitted, does not enforce `description == summary` (which `tools/audit_content_seo.py --profile editorial` strictly requires).
   - Rule reference drift in `kevseo/SKILL.md:39` and `audit_seo.py:63` citing Rule 2 (should be Rule 1), `add.validator.php:141` citing Rule 10 (should be Rule 9), and `CODE_QUALITY_AGENT_STRATEGY.md:16, 20` citing Rule 7 (should be Rule 6).

---

## 2. Logic Chain

1. **From B1 Observations to Filter Integrity:** Sequential `$model->where_*()` calls replace `$this->_where` because `Database::where()` overwrites the SQL string. Thus, multi-parameter queries (such as `mod_name` + `vid` + `lang`) drop all earlier conditions, leading to data leaks and cross-record pollution (as verified on News `#128` where `list_faqs` returned 18 items across 6 records instead of 6). Adding `addEqFilter()` and `addLikeSearchFilter()` to `McpCrudToolsBase` using `$model->addWhere(...)` restores conjunctive filtering across all 15 MCP domain tool classes without modifying legacy ORM behavior.
2. **From B2 Observations to Environment & Audit Reliability:** The framework's `Database` class delegates to `Adapter_Mysqli`, whose `query()` method returns `mysqli_result`. Calling PDO's `->fetch()` or `->fetchAll()` throws fatal PHP errors caught silently by `catch (\Throwable)`. Replacing these calls with `$db->fetch_array()` / `$db->toArray()` allows `resolveEnvironment()` to accurately return `'open'` or `'closed'` and `getRecordAuditMeta()` to return real language statuses and FAQ items. Exposing draft FAQs when `recordStatus === 'n'` ensures `audit_content_seo.py` can properly evaluate staged drafts.
3. **From B3 & B5 Observations to Editorial Staging Quality:** Without checking `root !== 'y'`, articles can be assigned to structural parent categories where they remain hidden. Without normalizing explicit `seo`, non-compliant URLs leak into the database. Without `username` and `category_name` in `get_news`, automated verifiers cannot assert author or category placement. Adding `image_alt` and `image_credit` columns to `mod_news` and upgrading `news.twig` to `<figure>` + `<figcaption>` removes the need to inject photo credits into `articleBody`.
4. **From A3 Observations to Image Ingestion Reliability:** Next.js uses `/_next/image?url=<encoded>` for all images. Stripping the query parameter destroys uniqueness, collapsing all images into a single URL. By unwrapping CDN proxy URLs before deduplication and restricting candidate scoring to `<article>` while penalizing sidebars, the genuine article hero image is preserved.
5. **From A4 & B4 Observations to Zero-Hallucination Gate Enforcement:** Disabling TLS and skipping SSRF validation creates security vulnerabilities. Dropping fractional seconds from ISO-8601 timestamps causes false date rejections. Stripping HTML without removing `<script>` tags permits numbers in JavaScript bundles to satisfy fact checks. Only evaluating the first citing paragraph allows fabricated facts in subsequent paragraphs to pass undetected. Fixing regexes, stripping `<script>` blocks, evaluating all citing paragraphs, and introducing two-sided cross-language anchors (`source_lang` + `source_anchors`) eliminates both false passes and false blocks.
6. **From A2 & A6 Observations to Infrastructure Robustness:** Swarm sub-agents cannot call MCP tools when permissions are confined to the parent session. Introducing `tools/mcp_cli.py` with an `--editorial-safe` policy provides a sanctioned, secret-opaque bridge. In Nginx, adding `location ^~ /.well-known/` before `location ~ /\. ` ensures RFC discovery endpoints return HTTP 200 rather than HTTP 403.

---

## 3. Specification Mining Core Data

### Features Discovered

| # | Category | Feature | Description | Inputs | Outputs | Error Behavior | Discovered Via |
|---|----------|---------|-------------|--------|---------|----------------|----------------|
| 1 | Backend/MCP | `McpCrudToolsBase::addEqFilter` & `addLikeSearchFilter` | Conjunctive SQL filtering helper using `addWhere()` | `$model`, `$column`, `$value` / `$columns`, `$rawSearch` | Appends `AND` condition to model | Validates column identifier `/^[a-zA-Z_][a-zA-Z0-9_]*$/`; escapes search text via `Obuilder::cc()` | B1 spec & `lib/Orm/Orm.php` |
| 2 | Backend/MCP | `McpServer::resolveEnvironment` Fix | Reliable site status resolution using `Database::fetch_array()` | None | Environment array with `site_status` (`'open'`, `'closed'`, or `'unknown'`) | Falls back to `'unknown'` on DB failure without crashing | B2 spec & `lib/McpServer.class.php` |
| 3 | Backend/MCP | `McpCrudToolsBase::getRecordAuditMeta` Fix & Draft Visibility | Audit metadata loader using `Database::fetch_array()`, supporting draft FAQs | `$modName`, `$recordId`, `$lang`, optional `$recordStatus = 'y'` | `['lang_status', 'faq_count', 'faq_items', 'faq_published_count', 'faq_draft_count']` | Safe defaults `lang_status: 'n'`, `faq_count: 0` on error | B2 spec & `lib/McpCrudToolsBase.class.php` |
| 4 | Backend/MCP | `McpHeadlineTools` Type Fix | Pass string name to `assertContentMatchesLang` | `$lang`, `$name` | Void | Throws `McpToolException` on mismatch (prevents `TypeError`) | B2 spec & `lib/McpHeadlineTools.class.php` |
| 5 | Backend/MCP | `McpNewsTools` Leaf Category Enforcement | Enforce `root !== 'y'` on category selection | `category_id`, `lang` | Void | Throws `McpToolException` listing valid child categories if root is selected | B3 spec & `lib/McpNewsTools.class.php` |
| 6 | Backend/MCP | `McpNewsTools` Mandatory `makeSeo()` | Wikipedia-style normalization and `U+0307` strip on all slugs | `seo` (explicit or generated from `name`), `lang` | Clean slug with underscores | Throws `McpToolException` if slug length invalid or already exists | B3 spec & `lib/McpNewsTools.class.php` |
| 7 | Backend/MCP | `get_news` Enriched Envelope | Return resolved `username`, `author_fullname`, `category_name`, `category_seo` | `id` or `seo`, `lang` | Enriched article record + canonical `item` envelope | Throws `McpToolException` if news record not found | B3 spec & `lib/McpNewsTools.class.php` |
| 8 | Backend/MCP | `check_duplicate_news` Tool | Pre-staging similarity check querying news within last 48 hours | `name`, `lang`, optional `threshold = 0.55` | List of duplicate matches with ID, name, date, similarity score | Returns empty list if no matches | B3 spec & `lib/McpNewsTools.class.php` |
| 9 | Backend/MCP | `batch_create_faqs` Tool | Atomic creation of up to 25 FAQs for a host record | `mod_name`, `vid`, `lang`, `items: [{question, answer, sort_order?}]`, `replace_existing_drafts` | Created FAQ count, IDs, and items | Validates all items before inserting; fails entire batch if any check fails | B3 spec & `lib/McpFaqTools.class.php` |
| 10 | Backend/MCP | `list_faq_modules` Tool | Dynamic discovery of valid FAQ host modules from `mod_faq_mod` | `search`, `limit`, `offset` | List of host modules (`id`, `name`, `mod_name`, `mod_key`, `mod_value`) | Throws `McpToolException` on permission failure | B3 spec & `lib/McpFaqTools.class.php` |
| 11 | Backend/MCP | `list_faqs(include_answer: bool)` | Optional inclusion of FAQ `answer` body in list results | `include_answer: bool` (default `false`) + filters | Items containing `answer` string when requested | Returns standard fields when false | B3 spec & `lib/McpFaqTools.class.php` |
| 12 | Infra/Nginx | `prod.conf` `/.well-known/` Passthrough | Preempt dotfile deny rule for RFC discovery endpoints | HTTP request matching `^~ /.well-known/` | Routed to `index.php?$query_string` | Dotfiles outside `/.well-known/` still denied 403 | A6 spec & `docker/nginx/prod.conf` |
| 13 | Shared Lib | `editorial_http.py` | Shared hardened HTTP client with verified TLS, SSRF guard, `Retry-After` backoff, and cache | URL, headers, timeout, max_retries, cache_ttl | HTTP response body, status, headers | Raises specific network/SSRF exceptions; retries on 429/503 | A4/B4 spec & `.agents/skills/_shared/` |
| 14 | Pipeline/Gate | `check_freshness.py` Hardening | Verified SSL, fractional-second ISO handling, element stripping, priority tiering, deduplication | URLs, `--file`, `--dossier` | JSON/CLI status: `PASSED`, `BLOCKED`, `citation_count` | Exits 1 on stale/blocked; 2 on bad invocation; 0 on pass | A1/A4 spec & `kevtemen` |
| 15 | Pipeline/Gate | `verify_facts_and_links.py` All-Paragraph Parity | Multi-paragraph factual claim verification across all citing blocks | Draft HTML, research dossier, `--explain` | Diagnostic report, claim matrices, pass/block | Exits 1 on unverified numbers, invalid URLs, missing attributions | B4 spec & `kevrast` |
| 16 | Pipeline/Gate | `verify_facts_and_links.py` R2 Vendor Allowlist | Permits single-segment vendor pages (`/download`, `/releases`) with semver/anchor | URL, dossier source role | Precision classification: `deep_page` vs `section_front` | Non-allowlisted single segments remain blocked `section_front` | B4 spec & `kevrast` |
| 17 | Pipeline/Gate | Two-Sided Cross-Language R3 Anchoring | Verifies terms across foreign sources and Turkish draft using `source_anchors` | `source_lang`, `source_anchors` pairs, foreign HTML, draft text | Anchoring match result | Fails claim if English term missing on source or Turkish term missing in draft | B4 spec & `kevrast`/`dossier.schema.json` |
| 18 | Pipeline/Gate | Turkish Morphology & Case-Folding | `tr_fold()` and institutional suffix normalizer (`Belediyesine` -> `Belediyesi`) | Proper noun / entity tokens | Normalized root entity string | Matches case-insensitive Turkish entities accurately | B4 spec & `kevrast` |
| 19 | Pipeline/Gate | Expanded R4 Attribution Lexicon | Catches journalistic attribution phrases (`paylaşılan bilgilere göre`, `analize göre`, etc.) | Paragraph text | Detected attributions requiring `<a href>` | Flags unlinked attributions with `R4_UNLINKED_ATTRIBUTION` | B4 spec & `kevrast` |
| 20 | Pipeline/Image | `process_news_image.py` Next.js CDN Unwrap & Scope | Unwraps Next.js `/_next/image` URLs and restricts scoring to primary `<article>` | Article URL or local image | High-res master image, JSON report | Drops sidebar thumbnails; exits with error if no valid image | A3/B5 spec & `kevanger` |
| 21 | Pipeline/Image | `process_news_image.py` Master Mode | Clean master export (`>= 1200px`) for single-pass framework resizing | Source image binary, `--mode master` | High-res WebP/PNG master image | Avoids double downscale/upscale degradation | B5 spec & `process_news_image.py` |
| 22 | Database/Theme | `image_alt` & `image_credit` Fields | Dedicated news image metadata columns, Twig `<figure>` / `<figcaption>`, Schema.org tags | Database columns in `mod_news`, Twig variables | Rendered HTML with `<figcaption>` and JSON-LD credits | Empty credits omit `<figcaption>` gracefully | B5 spec & `mod_news` / `news.twig` |
| 23 | Tooling/CLI | `tools/mcp_cli.py` Editorial Safe Bridge | Sanctioned stdio JSON-RPC client for sub-agents and verification scripts | Subcommand, tool name, arguments | JSON response from MCP bridge | Rejects `delete_*` and unapproved mutations under `--editorial-safe` | A2 spec & `tools/mcp_cli.py` |
| 24 | Pipeline/Radar | `source_catalog.py --scan-today` | RSS/Atom/sitemap scanner for same-day local news discovery | Category, optional query filter | List of verified today URLs and timestamps | Reports empty list if no today articles found | A1/B6 spec & `kevkolin` |
| 25 | Pipeline/SEO | `audit_seo.py` Strict Editorial Alignment | Rubric alignment with `audit_content_seo.py` (`summary` 90-120 chars, `description == summary`, `>= 3 <h2>`) | Draft file, headline, summary, description, SEO slug | Rubric score and violation list | Exits 1 if headings < 3, heading levels jump, or summary length invalid | B6 spec & `kevseo` |
| 26 | Documentation | Rule Reference & Version Parity | Synchronize version `v1.0.63` and fix stale `AGENTS.md` rule citations | Config files, documentation files | Consistent version and policy citations | Fails quality audit if versions drift | B6/Release spec & repository docs |

---

### Edge Cases

| # | Feature | Input | Observed / Expected Behavior |
|---|---------|-------|------------------------------|
| 1 | `check_freshness.py` (A4) | ISO timestamp `2026-11-10T23:13:00.000Z` | Preserves fractional seconds and `Z` (UTC). Shifting `23:13Z` to Istanbul (`+03:00`) yields `2026-11-11 02:13:00+03:00` (`today` -> `PASSED`). Previously treated as naive Istanbul time (`yesterday` -> false `BLOCKED`). |
| 2 | `check_freshness.py` (A4) | Stale article with Next.js Flight script containing `"date":"today"` and sidebar `<time datetime="today">` | Strips `<script>`, `<style>`, and `<aside>` prior to scanning. Evaluates main article metadata only -> correctly returns `BLOCKED`. |
| 3 | `check_freshness.py` (A4) | Page with navigation link text `"Bugün Hava Durumu"` | Ignores bare `"Bugün"` in navigation links. Resolves to undated / original published date -> prevents false pass. |
| 4 | `check_freshness.py` (A4) | Draft citing the same primary URL 3 times in different paragraphs | Groups and deduplicates URLs prior to network requests. Emits exactly 1 `[OK]` line with `citation_count: 3`. |
| 5 | `check_freshness.py` (A1) | Seed URL published at `23:13` yesterday evening (`today - 1 day`, hour >= 20) | Emits structured diagnostic `is_yesterday_late_evening: true`, allowing one targeted `kevkolin --scan-today` discovery check for official/local same-day follow-ups. If found, demotes seed to `supplementary`; if not, halts `BLOCKED`. |
| 6 | `verify_facts_and_links.py` (B4) | Draft citing valid URL in Paragraph 1, but linking it again in Paragraph 4 with fabricated figure `"%95"` | `_find_all_citing_blocks()` audits Paragraph 4 and blocks with `R3_MISSING_NUMBER: 95%`. Previously passed because only Paragraph 1 was inspected. |
| 7 | `verify_facts_and_links.py` (B4) | Source HTML containing Webpack chunk `1287295` and draft stating `"12 bin kişi katıldı"` | Exact token matching prevents `"12"` from matching the substring of `"1287295"`. Stripping `<script>` blocks prevents chunk numbers from polluting source figures. |
| 8 | `verify_facts_and_links.py` (B4) | Single-segment vendor page `https://antigravity.google/download` (HTTP 200, contains version `2.31.0`) | Matches allowlisted slug `download` with supplementary role and semver anchor -> passes R2 as `deep_page`. Media front `https://rudaw.net/turkish` remains blocked as `section_front`. |
| 9 | `verify_facts_and_links.py` (B4) | English source doc `https://antigravity.google/docs/swarm` with dossier anchor `Coordinator` <-> `Koordinatör` | Verifies `Coordinator` exists on English target page AND `Koordinatör` exists in Turkish citing paragraph -> passes R3 without routing through third-party blogs. |
| 10 | `verify_facts_and_links.py` (B4) | Turkish entity written as *"Diyarbakır Büyükşehir Belediyesine"* without apostrophe | Institutional suffix stemmer normalizes `Belediyesine` to `Belediyesi`, matching *"Diyarbakır Büyükşehir Belediyesi"* on source page without error. |
| 11 | `process_news_image.py` (A3) | Next.js page with 54 responsive images sharing the path prefix `/_next/image` | `unwrap_cdn_image_url()` extracts the inner encoded URL for each candidate. Deduplication preserves all 54 unique images, and `<article>` scoping selects the true hero photograph. |
| 12 | `process_news_image.py` (A3) | Next.js sidebar thumbnail advertising responsive width `3840w` | Unverified `srcset` descriptors on CDN proxies are capped at 1200; `og:image` stem match boost (`+2000`) guarantees the authentic hero photo wins over sidebar thumbnails. |
| 13 | `McpNewsTools::createNews` (B3) | Caller attempts to stage news with `category_id` corresponding to a structural root category (`root = 'y'`) | Rejects operation with `McpToolException: Category is a root container and cannot contain articles directly. Choose a selectable child category.` |
| 14 | `McpNewsTools::createNews` (B3) | Caller supplies explicit slug `Diyarbakır_Park_Projesi` with decomposed Unicode `i\u0307` | Strips `U+0307` combining mark and normalizes via `self::makeSeo()`, storing clean Wikipedia-style slug `Diyarbakır_Park_Projesi`. |
| 15 | `McpFaqTools::batch_create_faqs` (B3) | Caller submits batch of 6 FAQs where the 5th item has an active duplicate question in the database | Pre-validates all 6 items prior to insertion; detects duplicate on item 5; aborts entire batch without inserting items 1–4 (zero partial state). |
| 16 | `docker/nginx/prod.conf` (A6) | Client requests `GET /.well-known/oauth-authorization-server` | Preempts `location ~ /\. ` via `location ^~ /.well-known/`, routing request to `index.php?$query_string` and returning HTTP 200 with OAuth metadata. |

---

### Architectural Issues Detail Matrix

| Issue Code | Layer & Components | Root Cause | Expected Behavior | Exact Files to Change |
|---|---|---|---|---|
| **A1** | `kevtemen` + `kevkolin` + `kevanger` | Fail-closed calendar rule halts late-evening (`>= 20:00`) overnight stories even when verified same-day local follow-ups exist; `kevtemen/SKILL.md` strictly forbids follow-up discovery. | Enable bounded "Same-Day Primary Discovery": run 1 targeted `kevkolin --scan-today` check on local Amed sources; if a verified today follow-up exists, promote it to `primary` and demote seed to `supplementary`; if none, halt. | `.agents/skills/kevtemen/SKILL.md`<br>`.agents/skills/kevanger/SKILL.md`<br>`.agents/skills/kevtemen/scripts/check_freshness.py`<br>`.agents/skills/kevkolin/scripts/source_catalog.py` |
| **A2** | Swarm Multi-Agent + MCP Bridge | Sub-agents and worker processes cannot call MCP tools directly (`server amed-bulletin is not allowed in this context`) and are forbidden from reading credentials or crafting raw `curl` commands. | Provide `tools/mcp_cli.py`, a sanctioned, secret-opaque stdio client spawning `amed_bulletin_mcp.py` with an `--editorial-safe` policy gating unsafe mutations and verifying environment identity. | `tools/mcp_cli.py` (New)<br>`.agents/skills/kevanger/SKILL.md` |
| **A3** | `kevanger` Image Intake (`process_news_image.py`) | 1. Query stripping (`url.split("?")[0]`) collapsed 54 Next.js `/_next/image` photos into 1 key.<br>2. Combined `<main>`/`<article>` bonus awarded `+500` to sidebar items.<br>3. Next.js `3840w` `srcset` descriptor inflated sidebar thumbnails to score 4340 over `og:image` (1024). | Unwrap CDN proxy URLs before deduplication; separate `<article>` vs `<main>` DOM scoping; exclude `<aside>`/sidebars; cap `srcset` hints at 1200; boost `og:image` matches by `+2000`; add browser-grade request headers. | `.agents/skills/kevanger/scripts/process_news_image.py` |
| **A4** | `kevtemen` Freshness Sentinel (`check_freshness.py`) | 1. Disabled TLS verification (`CERT_NONE`) and missing SSRF guards.<br>2. `RE_ISO` drops fractional seconds `.000Z`, shifting UTC to naive Istanbul.<br>3. `TAG_RE` leaves `<script>`/`<style>` bodies intact.<br>4. Sorting by `-timestamp` picks newest sidebar `<time>` or nav `"Bugün"`.<br>5. Non-deduplicated `draft_hrefs` causes redundant GET requests. | Use shared `editorial_http.py` with verified TLS and SSRF protection; capture fractional seconds in `RE_ISO`; strip `<script>`/`<style>`/`<aside>` tags; enforce strict priority tiering (main entity JSON-LD/meta first, earliest timestamp); deduplicate check URLs. | `.agents/skills/kevtemen/scripts/check_freshness.py`<br>`.agents/skills/_shared/editorial_http.py` (New) |
| **A5** | `kevanger` + `kevtemen` Documentation | Documentation ambiguity regarding Stage 0 seed pre-gate vs Stage 2 `--dossier` gate; agents risk wasting tokens researching stale URLs or skipping anti-laundering checks. | Codify explicit two-pass freshness protocol: Stage 0 pre-gate runs `check_freshness.py "$SEED_URL"` before research; Stage 2 dossier gate runs `check_freshness.py --dossier "$DOSSIER_JSON"` before drafting. | `.agents/skills/kevanger/SKILL.md`<br>`.agents/skills/kevtemen/SKILL.md` |
| **A6** | Nginx Config + Health Check (`docker/nginx/prod.conf`) | Regex `location ~ /\. ` in Nginx blocks `/.well-known/*` with HTTP 403 Forbidden; `mcp_health_check.py` Stage 7 masks the 403 response as a pass. | Insert `location ^~ /.well-known/ { try_files $uri /index.php?$query_string; }` before `location ~ /\. `; update `tools/mcp_health_check.py` Stage 7 to strictly require HTTP 200 and valid JSON issuer metadata. | `docker/nginx/prod.conf`<br>`tools/mcp_health_check.py` |

---

### Bug Codes Detail Matrix

| Bug Code | Exact Specification | Root Cause | Expected Behavior | Exact Files to Change |
|---|---|---|---|---|
| **B1** | Sequential `$model->where_*()` calls overwrite `$this->_where` across 15 MCP domain tool classes | `ORM::_call_where()` invokes `$this->where($where)`, and `Database::where()` overwrites `$this->_where = Dbuilder::where($arg);` instead of appending with `addWhere()`. Multi-parameter filters drop all earlier clauses. | Introduce `addEqFilter()` and parenthesized `addLikeSearchFilter()` in `McpCrudToolsBase` using `$model->addWhere(...)`. Refactor all 15 MCP classes to use these helpers for conjunctive `AND` filtering. | `lib/McpCrudToolsBase.class.php`<br>`lib/McpNewsTools.class.php`<br>`lib/McpFaqTools.class.php`<br>`lib/McpPublicationTools.class.php`<br>`lib/McpPageTools.class.php`<br>`lib/McpAnnouncementTools.class.php`<br>`lib/McpAttachmentTools.class.php`<br>`lib/McpDocumentTools.class.php`<br>`lib/McpHeadlineTools.class.php`<br>`lib/McpMenuTools.class.php`<br>`lib/McpPopupTools.class.php`<br>`lib/McpRedirectTools.class.php`<br>`lib/McpSettingsTools.class.php`<br>`lib/McpSliderTools.class.php`<br>`lib/McpSurveyTools.class.php`<br>`tasks/mcp_smoke.php` |
| **B2** | Swallowed fatal errors on `Adapter_Mysqli` in `McpServer::resolveEnvironment()` and `McpCrudToolsBase::getRecordAuditMeta()`, plus `TypeError` in `McpHeadlineTools` | 1. Calling PDO methods `fetch()` / `fetchAll()` on `mysqli_result` throws fatal `Error`, caught by `catch (\Throwable)`. Causes `site_status` to remain `'unknown'` and `get_news` audit metadata to always return `lang_status: 'n'`, `faq_count: 0`.<br>2. `getRecordAuditMeta()` filters `status = 'y'`, hiding draft FAQs for draft articles.<br>3. `McpHeadlineTools` passes an array to `assertContentMatchesLang`. | Replace `$db->query()->fetch()` with canonical `$db->fetch_array(); $db->toArray()`. Accept `$recordStatus = 'y'` in `getRecordAuditMeta` to include draft FAQs when inspecting drafts. Pass string `$name` in `McpHeadlineTools`. | `lib/McpServer.class.php`<br>`lib/McpCrudToolsBase.class.php`<br>`lib/McpHeadlineTools.class.php`<br>`tasks/mcp_smoke.php` |
| **B3** | Root category bypass, unnormalized `seo` bypass, missing author/category in `get_news`, and `mod_faq` batching/discovery gaps | 1. `createNews` and `updateNews` do not verify `root !== 'y'`.<br>2. Explicit `seo` values bypass `self::makeSeo()`.<br>3. `getNews` queries base `mod_news` without author/category joins.<br>4. `create_faq` requires single-item round-trips; `mod_faq_mod` modules cannot be discovered; `list_faqs` omits `answer`. | Enforce `root !== 'y'`; always run `self::makeSeo()` and strip `U+0307`; enrich `getNews` with `username`, `category_name`, and `category_seo`; add `batch_create_faqs` (atomic, max 25); add `list_faq_modules`; add optional `include_answer: bool` to `list_faqs`; add `check_duplicate_news`. | `lib/McpNewsTools.class.php`<br>`lib/McpFaqTools.class.php`<br>`tasks/mcp_smoke.php` |
| **B4** | Kevrast hallucination leaks (1st-paragraph-only audit, script number pollution, substring numbers, missing attributions) and false blocks (R2 vendor rejection, cross-lang failures, Turkish morphology) | 1. `_find_citing_block` only checks the first paragraph containing a URL.<br>2. Unstripped `<script>` blocks pollute target figures.<br>3. Substring matching `n in t` allows short numbers to match chunk IDs or timestamps.<br>4. `CITATION_PHRASE_RE` misses common journalistic patterns.<br>5. `classify_precision` rejects single-segment `/download` pages.<br>6. R3 lacks cross-language term mapping.<br>7. Case-folding fails on Turkish `İ`/`I`; suffix stripping misses un-apostrophed institutional titles. | Use `_find_all_citing_blocks()` for all-paragraph auditing; strip `<script>`/`<style>` tags; enforce exact normalized numeric tokens; allow single-segment vendor endpoints with semver/anchors; implement two-sided `source_lang` + `source_anchors` in `dossier.schema.json`; add `tr_fold()` and institutional stemmer; expand `OUTLET_ATTRIBUTION_RE` and separate speech verbs; create shared `editorial_http.py`. | `.agents/skills/kevrast/scripts/verify_facts_and_links.py`<br>`.agents/skills/kevkolin/scripts/dossier.schema.json`<br>`.agents/skills/_shared/editorial_http.py` (New)<br>`tests/fixtures/editorial/` |
| **B5** | Double lossy image re-compression/upscaling, and missing native `image_alt` / `image_credit` columns | 1. `process_news_image.py` pre-crops to `1200×675` WebP (<100 KB); framework then upscales to `1280×720`, `1200×900`, and `1200×1200` with a second WebP re-encoding.<br>2. `mod_news` lacks `image_alt` and `image_credit`, forcing photo credits into `articleBody`. | Add `--mode master` in `process_news_image.py` (exports `>= 1200px` clean master); add `image_alt` and `image_credit` columns to `mod_news`, `newses` model/view, and MCP tools; upgrade `news.twig` to `<figure class="detail-hero">` + `<figcaption class="news-hero-caption">` with JSON-LD credit properties; style via external CSS. | `.agents/skills/kevanger/scripts/process_news_image.py`<br>`mod/mod_news/mod_news.sql`<br>`mod/mod_news/model/newses.model.php`<br>`lib/McpNewsTools.class.php`<br>`templates/amed/index/mod_news/news.twig`<br>`templates/amed/static/css/detail-foundation.css` |
| **B6** | Static `source_catalog.py`, `audit_seo.py` vs `audit_content_seo.py` rubric drift, and stale `AGENTS.md` rule citations | 1. `source_catalog.py` lacks RSS/sitemap scanning.<br>2. `audit_seo.py` treats missing `<h2>` as warning, skips headline/summary checks if omitted, and does not enforce `description == summary`.<br>3. Stale rule numbers in docs and validators. | Add `--scan-today` with RSS/Atom/sitemap scanning to `source_catalog.py`; align `audit_seo.py --strict-editorial` with `audit_content_seo.py` (enforce 90-120 char summary, `description == summary`, `>= 3 <h2>`, no heading level jumps); fix rule citations across skills and validators. | `.agents/skills/kevkolin/scripts/source_catalog.py`<br>`.agents/skills/kevseo/scripts/audit_seo.py`<br>`.agents/skills/kevseo/SKILL.md`<br>`mod/mod_news/validator/add.validator.php`<br>`docs/CODE_QUALITY_AGENT_STRATEGY.md`<br>`tools/verify_news_pipeline.sh` |

---

### Acceptance Criteria & Test Requirements

1. **Syntax & Unit Checks:**
   - PHP lint check:
     `php -l lib/McpServer.class.php lib/McpCrudToolsBase.class.php lib/McpNewsTools.class.php lib/McpFaqTools.class.php lib/McpHeadlineTools.class.php lib/McpMenuTools.class.php lib/McpRedirectTools.class.php tasks/mcp_smoke.php`
   - Python compilation check:
     `python3 -m py_compile tools/mcp_cli.py tools/mcp_health_check.py tools/audit_content_seo.py .agents/skills/_shared/editorial_http.py .agents/skills/kevtemen/scripts/check_freshness.py .agents/skills/kevrast/scripts/verify_facts_and_links.py .agents/skills/kevanger/scripts/process_news_image.py .agents/skills/kevkolin/scripts/source_catalog.py .agents/skills/kevseo/scripts/audit_seo.py`
   - Shell syntax check:
     `bash -n tools/verify_news_pipeline.sh`

2. **MCP Smoke Test Suite (`tasks/mcp_smoke.php`):**
   - Must run in Docker test container (`docker exec bulletin_app php tasks/mcp_smoke.php`).
   - Must verify:
     - `list_faqs(mod_name: 'mod_news', vid: <id>, lang: 'tr')` returns exact match with `list_faqs(vid: <id>)` (News `#128` conjunctive filter regression guard).
     - `list_faqs(mod_name: 'mod_news', vid: 999999, lang: 'tr')` returns `total === 0`.
     - Multi-parameter filtering on `list_news(category_id, lang, status)` and `list_news_categories(lang, root, search)`.
     - `environment.site_status !== 'unknown'` (`in_array(..., ['open', 'closed'], true)`).
     - `get_news` returns `lang_status === 'y'` for active Turkish articles.
     - Headline lifecycle smoke test (`create_headline` -> `update_headline` -> `delete_headline`).
     - FAQ enhancements: `batch_create_faqs`, `list_faq_modules`, and `list_faqs(include_answer: true)`.

3. **End-to-End Editorial Pipeline Verification (`tools/verify_news_pipeline.sh`):**
   - Stage 1: `check_freshness.py --file <draft> --dossier <dossier>` passes with deduplicated audit lines.
   - Stage 2: `audit_seo.py --file <draft> --headline <h> --summary <s> --seo <slug>` enforces `>= 3 <h2>`, 40-70 char headline, 90-120 char summary, `description == summary`.
   - Stage 3: `verify_facts_and_links.py --file <draft> --dossier <dossier> --explain` audits all citing blocks, catches unlinked attributions, validates cross-language anchors, and passes valid single-segment product endpoints.
   - Stage 4: `audit_content_seo.py --module mod_news --profile editorial --record-json <json> --lang-status y` scores `>= 90/100`.

4. **Quality Guard Verifications:**
   - `python3 tools/i18n_guard.py --path <modified_files>` (must report 0 violations).
   - `python3 tools/code_separation_guard.py` (all new/modified template code must have 0 inline styles or inline scripts).
   - `python3 tools/module_boundary_guard.py --all` (must report 0 forbidden module files or bindings).
   - `python3 tools/min_css_guard.py` (must pass CSS minification consistency checks).

5. **Release & Version Parity Requirements:**
   - Synchronize release version string `1.0.63` across:
     - `config/site.config.php` (`$site['version'] = '1.0.63';`)
     - `config/site.config.example.php` (`$site['version'] = '1.0.63';`)
     - `docs/SERVER.md` (`- **Version:** v1.0.63 (YYYY-MM-DD)`)
     - `docs/VERSION.md` (new `## v1.0.63` section documenting all Phase 1–4 changes)
   - Git commit, tag `v1.0.63`, and push per release protocol.

---

### Architectural Constraints & Invariants

1. **Rule 1 — Strict Code Separation Mandate:** Zero inline CSS (`style="..."`, `<style>` tags) and zero inline JavaScript (`onclick="..."`, `<script>` tags) in HTML, Twig templates, or PHP files. All hero caption styling must be placed in `templates/amed/static/css/detail-foundation.css`.
2. **Rule 2 & Rule 6 — English Code & Documentation Mandate:** All identifiers, comments, commit messages, agent rules, skills, and documentation under `docs/` must be written strictly in English. Turkish text patterns in regular expressions (such as `MONTHS_TR` and `OUTLET_ATTRIBUTION_RE`) operate on Turkish domain data and must be accompanied by English comments.
3. **Rule 3 — Workspace Isolation & Zero Autonomous Cross-Repo Sync:** All operations, tool executions, and file edits must remain strictly within `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`. Sibling repositories must not be accessed or modified autonomously.
4. **Rule 5 — Admin Mobile-First Surface Rule:** All templates under `templates/admin/` represent a phone-only product surface. Any administrative form adjustments (such as adding `image_alt` and `image_credit` inputs) must adhere strictly to mobile viewport layout contracts.
5. **Rule 9 — Wikipedia-Style URL Generation Parity:** All URLs and slugs must preserve original casing and characters (`ç, ğ, ı, İ, ö, ş, ü, ê, î, û`) with spaces converted to underscores (`_`) via `McpCrudToolsBase::makeSeo()`. Forced lowercase hyphenation is prohibited.
6. **Domain Boundary Isolation:** Bulletin domain ownership is restricted to news journalism (`mod_news`), headlines (`mod_headline`), publications (`mod_publication`), FAQs, pages, surveys, attachments, galleries, and video embeds. All commercial, assessment, and property modules remain strictly forbidden.

---

## 4. Caveats

1. **Production Deployment vs Local Workspace:** Remote production deployment via `tools/deploy_production.sh` requires active SSH/KAS credentials and remote server connectivity. Testing against the live production container or running mutation commands on `https://amedbulten.com` must not occur during local development; `mcp_smoke.php` must run against the local Docker container.
2. **Backward Compatibility of Legacy ORM:** `ORM::_call_where()` retains its historical behavior to ensure existing admin controllers and frontend modules do not experience unintended side effects. Conjunctive `AND` filtering is introduced via `McpCrudToolsBase::addEqFilter()` and `addLikeSearchFilter()`.
3. **`secondary_category_ids` Junction Table:** The plan outlines a roadmap item for secondary categories via `mod_news_category_rel`. In `v1.0.63`, the priority is leaf-category enforcement on the existing `category_id` column, leaving the junction table schema extension for a planned subsequent migration.

---

## 5. Conclusion

The specification mining of `docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md` and `ORIGINAL_REQUEST.md` is complete. The plan defines an end-to-end remediation addressing:
- 6 live execution bottlenecks (A1–A6), including near-midnight local news discovery, sub-agent MCP access via `tools/mcp_cli.py`, Next.js image collision fixes in `process_news_image.py`, TLS/ISO/tag-stripping fixes in `check_freshness.py`, two-pass gate codification, and Nginx dotfile passthrough for RFC discovery endpoints.
- 6 systemic framework defects (B1–B6), including conjunctive multi-parameter ORM filtering across 15 MCP classes, `mysqli_result` compatibility in `McpServer` and `McpCrudToolsBase`, leaf-category and `makeSeo()` enforcement, atomic batch FAQ staging, comprehensive Kevrast anti-hallucination and cross-language anchoring, native `image_alt`/`image_credit` columns with `<figcaption>` support, and RSS local radar scanning with aligned SEO rubrics.
- 4 execution phases followed by strict quality guard verification and release tagging under `v1.0.63`.

All specifications, error conditions, root causes, file paths, and test requirements have been identified, verified against the active codebase, and documented in this report.

---

## 6. Verification Method

To independently verify this specification mining report against the codebase:

1. **Verify Root Cause B1 in Database & ORM:**
   - Inspect `lib/Orm/Orm.php:111-125` to confirm `_call_where` calls `$this->where($where)`.
   - Inspect `lib/Database/Database.php:603-611` to confirm `$this->_where` is overwritten by `Database::where()`.
   - Inspect `lib/McpNewsTools.class.php:1252-1272` to confirm multiple sequential `$model->where_*()` calls.

2. **Verify Root Cause B2 in MCP Server & Crud Base:**
   - Inspect `lib/McpServer.class.php:138` to confirm `$db->query(...)->fetch()` call on `Adapter_Mysqli`.
   - Inspect `lib/McpCrudToolsBase.class.php:792, 799, 805` to confirm `fetch()` and `fetchAll()` calls on `mysqli_result`.
   - Inspect `lib/McpHeadlineTools.class.php:284, 341` to confirm array argument passed to `assertContentMatchesLang`.

3. **Verify Root Cause A6 in Nginx Configuration:**
   - Inspect `docker/nginx/prod.conf:84-88` to confirm regex `location ~ /\. ` blocks all dotfiles including `/.well-known/`.
   - Run `python3 tools/mcp_health_check.py` to confirm Stage 7 observes HTTP 403 on `/.well-known/oauth-*`.

4. **Verify Root Causes A3, A4 & B4 in Editorial Scripts:**
   - Inspect `.agents/skills/kevanger/scripts/process_news_image.py:303` to confirm `url.split("?")[0]` query stripping.
   - Inspect `.agents/skills/kevtemen/scripts/check_freshness.py:34-36, 50-52` to confirm `CERT_NONE` and missing fractional second support in `RE_ISO`.
   - Inspect `.agents/skills/kevrast/scripts/verify_facts_and_links.py:270-277, 348` to confirm `_find_citing_block` only evaluates the first paragraph.
