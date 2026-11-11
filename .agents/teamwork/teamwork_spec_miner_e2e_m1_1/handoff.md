# Specification Mining & E2E Assertion Catalog: Kevanger Editorial Pipeline Remediation & Enhancement (v1.0.63)

**Author:** `teamwork_spec_miner_e2e_m1_1`  
**Target Version:** `v1.0.63`  
**State Folder:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_spec_miner_e2e_m1_1/`  
**Target Specification:** `docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md`, `PROJECT.md`, `SCOPE.md`

---

## 1. Observation

Direct observations and evidence collected across the active workspace:

1. **`lib/Database/Adapter/Mysqli.php:66` & `lib/McpServer.class.php:136-139`**:
   - `Database::query()` returns a native PHP `mysqli_result` object.
   - `McpServer::resolveEnvironment()` executes:
     ```php
     $row = $db->query("SELECT site_status FROM mod_settings LIMIT 1")->fetch();
     ```
   - In PHP `mysqli_result`, the method `fetch()` is undefined (only `fetch_assoc()`, `fetch_array()`, `fetch_all()`). This triggers `Error: Call to undefined method mysqli_result::fetch()`, which is caught by `catch (\Throwable)` on line 151, permanently returning `"site_status": "unknown"`.
2. **`lib/McpCrudToolsBase.class.php:780-822`**:
   - `getRecordAuditMeta()` calls `$db->query("SELECT status FROM lang WHERE seo = '{$safeLang}' LIMIT 1")->fetch();` and `$faqRes->fetchAll()` / `$faqRes->fetch()`. Both throw undefined method errors on `mysqli_result`, causing `catch (\Throwable $e)` to swallow the error and return default fallback values `['lang_status' => 'n', 'faq_count' => 0, 'faq_items' => []]`.
   - In addition, line 798 enforces `AND status = 'y'`, meaning draft FAQs (`status = 'n'`) attached to a staged news draft (`status = 'n'`) are ignored even if fetched correctly.
3. **`lib/McpHeadlineTools.class.php:284, 341`**:
   - `createHeadline()` executes `$this->assertContentMatchesLang($lang, ['name' => $name]);`.
   - `updateHeadline()` executes `$this->assertContentMatchesLang($lang, ['name' => (string) $model->name]);`.
   - In `McpCrudToolsBase.class.php:204`, the signature is `assertContentMatchesLang(string $lang, string $name, string $summary = '', string $content = ''): void`. Passing an `array` to `string $name` causes a fatal `TypeError` in PHP 8.2+.
4. **`lib/Orm/Orm.php:111-125` & `lib/Database/Database.php:603-623`**:
   - `ORM::__call('where_*', ...)` invokes `ORM::_call_where()`, which calls `$this->where($where)`.
   - `Database::where($arg)` executes `$this->_where = Dbuilder::where($arg);`, which overwrites `$this->_where` rather than chaining with `AND`.
   - In 15 MCP tool classes (`McpNewsTools`, `McpFaqTools`, `McpPublicationTools`, `McpPageTools`, `McpAnnouncementTools`, `McpAttachmentTools`, `McpDocumentTools`, `McpHeadlineTools`, `McpMenuTools`, `McpPopupTools`, `McpRedirectTools`, `McpSettingsTools`, `McpSliderTools`, `McpSurveyTools`, `McpCrudToolsBase`), filter methods sequentially chain `$model->where_*()` calls, silently clobbering earlier filters and leaving only the final filter active.
5. **`lib/McpNewsTools.class.php:710, 725-728, 670-686`**:
   - Line 710: `$this->assertRecordMatchesLang('mod_news_category', $categoryId, $lang, 'Category');` checks category existence and language match, but does not check if `$cat->root === 'y'`.
   - Lines 725-728: If explicit `seo` is passed, `self::makeSeo($name, $lang)` is bypassed, permitting un-normalized or corrupted slugs (such as decomposed Unicode `i\u0307`).
   - Lines 670-686: `getNews()` does not join or enrich author `username`, `author_fullname`, `category_name`, or `category_seo`. It returns `$row['item'] = $row; return $row;` (hybrid flat record).
6. **`lib/McpFaqTools.class.php:255-275`**:
   - Line 261: `$model->select('id, lang, mod_name, vid, question, sort_order, status, created_at, updated_at');` deliberately excludes `answer`.
   - No `batchCreateFaqs` or `listFaqModules` tools exist.
7. **`docker/nginx/prod.conf:84-88`**:
   - Nginx configuration contains:
     ```nginx
     location ~ /\. {
         deny all;
         access_log off;
         log_not_found off;
     }
     ```
   - Requests to `/.well-known/...` are denied with `HTTP 403 Forbidden` because no preceding `location ^~ /.well-known/` exists.
8. **`.agents/skills/kevtemen/scripts/check_freshness.py:34-36, 50-52, 64, 406-433`**:
   - Lines 34-36: `_SSL_CTX.check_hostname = False; _SSL_CTX.verify_mode = ssl.CERT_NONE`.
   - Lines 50-52: `RE_ISO` regex does not match fractional seconds `(?:[.,]\d+)?`.
   - Line 64: `TAG_RE = re.compile(r"<[^>]+>")` does not strip `<script>`, `<style>`, `<aside>`, `<nav>`, or `<footer>`.
   - Lines 406-433: `draft_hrefs = HREF_RE.findall(draft)` does not deduplicate URLs, causing repeated network requests and duplicate audit lines.
9. **`.agents/skills/kevrast/scripts/verify_facts_and_links.py:47-53, 178-183, 220-224, 270-277, 348`**:
   - Line 348: `block = self._find_citing_block(u, text)` uses `_find_citing_block`, which only evaluates the first matching paragraph, failing to audit subsequent paragraphs.
   - Lines 178-183: Rejects all single-segment paths (`len(segments) <= 1`) as `section_front`.
   - Lines 220-224: `strip_tags(target_html)` leaves `script` and `style` bodies intact. `n in t` allows partial substring integer matches.
   - Lines 47-53: `CITATION_PHRASE_RE` misses key phrases like `paylaşılan bilgilere göre` and `analize göre`.
10. **`.agents/skills/kevanger/scripts/process_news_image.py:148, 176-185, 303`**:
    - Line 303: `u = c["url"].split("?")[0].rstrip("/")` collapses Next.js `/_next/image?url=...` URLs to a single URL `/_next/image`.
    - Lines 148, 152: Awards `+500` to all images inside `<main>`, including sidebar recommendations.
    - Lines 176-185: Uses unvalidated `3840w` `srcset` descriptor as candidate score without capping.
    - No `--mode master` flag exists.
11. **`mod/mod_news/mod_news.sql:1-31` & `templates/amed/index/mod_news/news.twig:411-417`**:
    - `mod_news` has no `image_alt` or `image_credit` columns.
    - `news.twig` renders `<div class="detail-hero">` and no `<figcaption>`.

---

## 2. Logic Chain

1. From **Observation 1 & 2**, PDO-style `->fetch()` and `->fetchAll()` calls on `mysqli_result` cause silent failures. Replacing them with canonical `Database::fetch_array()` and `Database::toArray()` restores database state resolution (`site_status`) and audit metadata (`lang_status`, `faq_count`).
2. From **Observation 3**, PHP type safety requires passing a string to `assertContentMatchesLang`. Fixing the argument prevents `TypeError` when handling headlines.
3. From **Observation 4**, sequential `$model->where_*()` calls cause filter overwriting. Introducing `addEqFilter()` and `addLikeSearchFilter()` using `$model->addWhere(...)` ensures conjunctive `AND` filtering across all 15 MCP tool classes without regressing existing single-clause behavior.
4. From **Observation 5**, news staging currently permits root categories and corrupt slugs, and omits essential audit metadata in `get_news`. Enforcing `root === 'n'`, mandatory `self::makeSeo()`, and joining author and category fields establishes full traceability.
5. From **Observation 6**, lack of batching, module listing, and answer visibility in `mod_faq` tools causes operational friction. Implementing `batchCreateFaqs`, `listFaqModules`, and `include_answer: bool` satisfies requirements for efficient FAQ management.
6. From **Observation 7**, Nginx's dotfile restriction inadvertently blocks RFC discovery endpoints. Adding `location ^~ /.well-known/` restores compliance.
7. From **Observation 8**, `check_freshness.py` suffers from insecure TLS, timezone errors with fractional seconds, false positives from unstripped tags, and redundant network calls. Integrating `editorial_http.py`, updating ISO regex, stripping non-content tags, and deduplicating URLs resolves these vulnerabilities.
8. From **Observation 9**, `verify_facts_and_links.py` has critical coverage gaps in multi-paragraph checks and attribution lexicons, while overly restricting valid vendor download pages and foreign language primary docs. Implementing all-paragraph checking, clean text stripping, strict numeric token equality, single-segment allowlists, cross-language anchoring, and morphological stemmers closes these gaps.
9. From **Observation 10**, `process_news_image.py` exhibits URL collisions and incorrect hero candidate selection on Next.js sites. Unwrapping CDN image URLs, restricting scoring bonuses to primary `<article>` elements, capping srcset descriptors, and supporting `--mode master` ensures high-fidelity image extraction.
10. From **Observation 11**, image metadata is not natively tracked or presented in `mod_news`. Adding schema columns and semantic `<figure>` / `<figcaption>` elements with JSON-LD credits meets SEO and copyright requirements.

---

## 3. Features Discovered

| # | Category | Feature | Description | Inputs | Outputs | Error Behavior | Discovered Via |
|---|----------|---------|-------------|--------|---------|----------------|----------------|
| 1 | MCP Core & ORM Filter | `McpCrudToolsBase::addEqFilter` & `addLikeSearchFilter` | Conjunctive filtering via `$model->addWhere()` avoiding B1 filter clobbering | `$model, $column, $value` / `$model, $columns, $rawSearch` | `void` (mutates model `_where`) | Throws `McpToolException` on invalid column name | `docs/plan/...`, `lib/McpCrudToolsBase.class.php`, `lib/Orm/Orm.php` |
| 2 | MCP Core & ORM Filter | `McpServer::resolveEnvironment` DB fetch | Use `Database::fetch_array()` instead of undefined PDO `fetch()` on `mysqli_result` | None (reads `mod_settings`) | `['name' => str, 'endpoint_host' => str, 'site_status' => 'open'\|'closed'\|'unknown']` | Gracefully catches exceptions, returns `site_status: 'unknown'` | `lib/McpServer.class.php:138`, `tasks/mcp_smoke.php` |
| 3 | MCP Core & ORM Filter | `McpCrudToolsBase::getRecordAuditMeta` DB fetch & draft FAQs | Use `Database::fetch_array()` and support draft FAQ counting (`recordStatus = 'n'`) | `$modName: str, $recordId: int, $lang: str, $recordStatus: str = 'y'` | `['lang_status' => 'y'\|'n', 'faq_count' => int, 'faq_items' => array, 'faq_published_count' => int, 'faq_draft_count' => int]` | Returns default fallback `lang_status: 'n', faq_count: 0` on error | `lib/McpCrudToolsBase.class.php:780`, `docs/plan/...` |
| 4 | MCP Core & ORM Filter | `McpHeadlineTools` string argument fix | Pass `$name` as string to `assertContentMatchesLang` to prevent TypeError | `$args: ['name' => str, 'lang' => str]` | `['created' => true, 'id' => int, 'item' => array]` | Throws `McpToolException` on length or language mismatch | `lib/McpHeadlineTools.class.php:284, 341` |
| 5 | MCP Core & ORM Filter | `McpNewsTools` leaf category check | Reject root categories (`root === 'y'`) in news staging | `$categoryId: int, $lang: str` | `true` if valid leaf category | Throws `McpToolException` listing available child categories | `lib/McpNewsTools.class.php:710`, `mod/mod_news/validator/add.validator.php` |
| 6 | MCP Core & ORM Filter | `McpNewsTools` mandatory `makeSeo()` | Enforce Wikipedia-style slug normalization and `U+0307` strip on all slugs | `$name: str, $seo: str, $lang: str` | Normalized SEO slug string | Throws `McpToolException` on duplicate SEO or invalid length | `lib/McpNewsTools.class.php:725`, `lib/McpCrudToolsBase.class.php:151` |
| 7 | MCP Core & ORM Filter | `McpNewsTools::getNews` enrichment | Return `username`, `author_fullname`, `category_name`, and `category_seo` | `$args: ['id' => int] \| ['seo' => str, 'lang' => str]` | Enriched news record array | Throws `McpToolException` if record not found | `lib/McpNewsTools.class.php:670`, `mod/mod_news/model/newses.model.php` |
| 8 | MCP Core & ORM Filter | `McpNewsTools::checkDuplicateNews` | Add pre-staging duplicate news check within last 48 hours (Jaccard >= 0.55) | `$name: str, $lang: str, $windowHours: int = 48` | `['has_duplicate' => bool, 'matches' => array]` | Throws `McpToolException` on invalid title | `docs/plan/... §B3`, `PROJECT.md` |
| 9 | MCP Core & ORM Filter | `McpFaqTools::batchCreateFaqs` | Atomic creation of up to 25 FAQs for host record | `$mod_name: str, $vid: int, $lang: str, $items: array, $status: str = 'n'` | `['created' => true, 'count' => int, 'items' => array]` | Fails atomically before inserts if validation fails | `docs/plan/... §B3`, `lib/McpFaqTools.class.php` |
| 10 | MCP Core & ORM Filter | `McpFaqTools::listFaqModules` | List valid FAQ host modules from `mod_faq_mod` | None / `$args: ['limit' => int, 'offset' => int]` | `['items' => array, 'total' => int]` | Throws `McpToolException` on permission failure | `docs/plan/... §B3`, `mod/mod_faq_mod/model/mod_faq_mod.model.php` |
| 11 | MCP Core & ORM Filter | `McpFaqTools::listFaqs` `include_answer` | Add `include_answer: bool` parameter to return full FAQ answers | `$args: ['vid' => int, 'include_answer' => bool]` | `['items' => array, 'total' => int]` (items include `answer` if true) | Throws `McpToolException` on invalid filters | `lib/McpFaqTools.class.php:255`, `docs/plan/... §B3` |
| 12 | Infrastructure & Routing | Nginx `.well-known` passthrough | Add `location ^~ /.well-known/` to `docker/nginx/prod.conf` for RFC discovery | HTTP GET request to `/.well-known/*` | HTTP 200 / proxied PHP response | HTTP 404 (not 403 Forbidden) if resource not found | `docker/nginx/prod.conf:84`, `tools/mcp_health_check.py` |
| 13 | Shared Transport & Editorial Sentinel Gates | Shared HTTP Client `editorial_http.py` | Verified TLS, SSRF guard, standard browser headers, Retry-After backoff, 10m cache | `url: str, timeout: int, max_bytes: int` | `Tuple[int, str/bytes, Dict[str, str]]` | Throws `ValueError` on SSRF, `URLError` on network failure | `docs/plan/... §A4, §B4`, `PROJECT.md` |
| 14 | Shared Transport & Editorial Sentinel Gates | `check_freshness.py` TLS & SSRF integration | Use `editorial_http` with verified TLS and SSRF protection | CLI URL / target string | Exit code 0 (fresh) / 1 (blocked) / 2 (error) | Exits 1 on SSRF attempt / network unreachable | `.agents/skills/kevtemen/scripts/check_freshness.py` |
| 15 | Shared Transport & Editorial Sentinel Gates | `check_freshness.py` ISO fractional seconds | Parse fractional seconds `(?:[.,]\d+)?` preserving UTC timezone | ISO date string with milliseconds (`.000Z`) | Parsed `datetime` in `Europe/Istanbul` | Returns `None` if unparseable | `.agents/skills/kevtemen/scripts/check_freshness.py:50-52` |
| 16 | Shared Transport & Editorial Sentinel Gates | `check_freshness.py` non-content tag stripping | Strip `<script>`, `<style>`, `<aside>`, `<nav>`, `<footer>` before date scanning | HTML string | Sanitized text string for date scanning | Rejects timestamps found only in non-content containers | `.agents/skills/kevtemen/scripts/check_freshness.py:64` |
| 17 | Shared Transport & Editorial Sentinel Gates | `check_freshness.py` earliest main date & deduplication | Select earliest main entity publication date; deduplicate audit URLs | HTML payload with multiple dates; draft with repeated URLs | Single authoritative publication date; deduplicated audit checks | Rejects later sidebar modification dates | `.agents/skills/kevtemen/scripts/check_freshness.py:165, 406` |
| 18 | Shared Transport & Editorial Sentinel Gates | `check_freshness.py` near-midnight diagnostics | Provide structured `age_hours` and near-midnight diagnostic attributes | Near-midnight published article (`20:00-23:59`) | JSON/stdout with `age_hours`, `is_yesterday_late_evening` | Exits 1 with detailed diagnostic message | `docs/plan/... §A1`, `PROJECT.md` |
| 19 | Shared Transport & Editorial Sentinel Gates | `verify_facts_and_links.py` all-paragraph verification | Evaluate all citing paragraphs across the draft in `audit_draft()` | HTML draft containing multiple citing blocks for same URL | Comprehensive audit dictionary for all citations | Returns `status: FAIL` if any citing paragraph fails R3 | `.agents/skills/kevrast/scripts/verify_facts_and_links.py:348` |
| 20 | Shared Transport & Editorial Sentinel Gates | `verify_facts_and_links.py` clean number & entity extraction | Strip non-content tags before R3; enforce exact normalized numeric equality | Citing block numbers vs source page text | Boolean match / list of missing numbers | Returns `status: block` on numeric discrepancy | `.agents/skills/kevrast/scripts/verify_facts_and_links.py:220-224` |
| 21 | Shared Transport & Editorial Sentinel Gates | `verify_facts_and_links.py` vendor single-segment allowlist | Allow defined single-segment vendor download/release pages under criteria | URL with single segment (`/download`, `/releases`) + dossier | `precise: True`, `kind: "product_doc"` | `precise: False` if not in allowlist or missing version anchor | `.agents/skills/kevrast/scripts/verify_facts_and_links.py:178` |
| 22 | Shared Transport & Editorial Sentinel Gates | `verify_facts_and_links.py` cross-language anchoring | Support `source_lang` and `source_anchors` in `dossier.schema.json` | Foreign source URL + `source_anchors` term mappings | Anchoring match result dictionary | Returns `status: block` if source or target term is missing | `docs/plan/... §B4`, `dossier.schema.json` |
| 23 | Shared Transport & Editorial Sentinel Gates | `verify_facts_and_links.py` Turkish morphology & attribution | Add `tr_fold()` and institutional suffix stemmer; expand attribution phrases | Turkish draft text with institutional suffixes and quotes | Accurate entity match / attribution validation | Flags `R4_UNLINKED_ATTRIBUTION` if phrase lacks link | `.agents/skills/kevrast/scripts/verify_facts_and_links.py:47-53, 205` |
| 24 | Media Intake & Content Presentation | `process_news_image.py` Next.js CDN proxy unwrap | Unwrap Next.js `/_next/image?url=...` before deduplication | URL containing `/_next/image?url=...` | Decoded origin image URL | Exits 1 if unwrapped URL fails SSRF validation | `.agents/skills/kevanger/scripts/process_news_image.py:303` |
| 25 | Media Intake & Content Presentation | `process_news_image.py` hero scoring & srcset cap | Restrict hero bonus to primary article elements; cap inflated srcset at 3840w | Article HTML with `<main>`, `<article>`, `<aside>` | Selected hero candidate metadata | Drops sidebar thumbnails; caps width hint at 1200 | `.agents/skills/kevanger/scripts/process_news_image.py:148, 281` |
| 26 | Media Intake & Content Presentation | `process_news_image.py` `--mode master` | Support clean `>= 1200px` master image output | `--mode master` CLI flag + image input | Clean master image file (`width >= 1200`, natural ratio) | Exits 1 if source image width < 1200 and strict flag active | `docs/plan/... §B5`, `PROJECT.md` |
| 27 | Media Intake & Content Presentation | `mod_news` `image_alt` & `image_credit` columns | Add schema columns to `mod_news`, model, controller, views | SQL schema definition, model properties | Stored strings in database (`VARCHAR(255)`) | Nullable by default; sanitized on input | `mod/mod_news/mod_news.sql`, `lib/McpNewsTools.class.php` |
| 28 | Media Intake & Content Presentation | `news.twig` hero figure & JSON-LD | Render `<figure class="detail-hero">` and `<figcaption>` with schema credits | News record array with `image_alt` and `image_credit` | Rendered HTML with `<figure>`, `<figcaption>`, and JSON-LD | Falls back to article title if `image_alt` empty | `templates/amed/index/mod_news/news.twig:411-417` |
| 29 | CLI Tooling & Swarm Execution | `tools/mcp_cli.py` Swarm CLI wrapper | Provide secret-opaque stdio bridge with `--editorial-safe` mutation guards | CLI subcommand, tool name, arguments JSON | JSON envelope output on stdout, status on stderr | Rejects `delete_*` and published status mutations | `docs/plan/... §A2`, `PROJECT.md` |
| 30 | Research Radar & SEO Alignment | `source_catalog.py` `--scan-today` | Support live RSS/Atom/sitemap scanning for same-day local primary recovery | `--scan-today [--category amed_diyarbakir]` | List of today-dated URLs with timestamps | Exits 1 on network failure; logs errors to stderr | `.agents/skills/kevkolin/scripts/source_catalog.py` |
| 31 | Research Radar & SEO Alignment | `audit_seo.py` alignment | Enforce headline 40-70, summary 90-120, description sync, heading hierarchy | HTML content, `--summary`, `--headline`, `--description` | JSON audit report, status `PASSED` / `FAILED` | Exits non-zero on critical SEO violations | `.agents/skills/kevseo/scripts/audit_seo.py`, `tools/audit_content_seo.py` |
| 32 | Documentation, Rules & Architectural Standards | Skill documentation synchronization | Update Kevanger, Kevtemen, Kevkolin, Kevrast, Kevseo SKILL.md docs | Documentation review | Synchronized `SKILL.md` instruction files | Audit fails if skills specify deprecated procedures | `.agents/skills/*/SKILL.md`, `PROJECT.md` |
| 33 | Documentation, Rules & Architectural Standards | Correct AGENTS.md rule number citations | Fix citations in `kevseo/SKILL.md`, `audit_seo.py`, `code_separation_guard.py`, validators, and `CODE_QUALITY_AGENT_STRATEGY.md` | Source code files containing rule citations | Correct rule references (Rule 1, 6, 9) | Guard scripts fail on stale rule numbers | `.agents/skills/kevseo/SKILL.md:39`, `docs/CODE_QUALITY_AGENT_STRATEGY.md` |
| 34 | Quality Assurance, Verification & Release | Full Regression Suite & MCP Smoke Tests | Update `tasks/mcp_smoke.php` and `tools/verify_news_pipeline.sh` Stage 7 | Test invocation commands | Clean test suite execution logs (all PASS) | Non-zero exit code if any regression or smoke check fails | `tasks/mcp_smoke.php`, `tools/verify_news_pipeline.sh` |
| 35 | Quality Assurance, Verification & Release | Version Documentation Update | Update `docs/VERSION.md` under v1.0.63 and synchronize version parity | Version synchronization across 4 files | Updated `docs/VERSION.md`, `site.config.php`, `SERVER.md` | Version mismatch check fails if files differ | `docs/VERSION.md`, `config/site.config.php` |
| 36 | Quality Assurance, Verification & Release | Git Release Tagging & Push | Commit changes, tag release v1.0.63, and push directly to GitHub | Git commands | Pushed Git tag `v1.0.63` on remote repository | Git error on uncommitted changes or push failure | `docs/plan/... §4`, `ORIGINAL_REQUEST.md` |

---

## 4. Edge Cases

| # | Feature | Input | Observed Behavior |
|---|---------|-------|-------------------|
| 1 | `McpCrudToolsBase::addEqFilter` | Column name with SQL injection characters (e.g. `status; DROP TABLE`) | Regex check `/^[a-zA-Z_][a-zA-Z0-9_]*$/` rejects invalid column, throwing exception |
| 2 | `McpServer::resolveEnvironment` | `mod_settings` table empty or database disconnected | Catch block handles exception cleanly, returning fallback `"site_status": "unknown"` |
| 3 | `McpCrudToolsBase::getRecordAuditMeta` | Staged draft news item with 5 draft FAQs and 1 published FAQ | Correctly returns `faq_count: 6`, `faq_draft_count: 5`, `faq_published_count: 1` when `$recordStatus === 'n'` |
| 4 | `McpHeadlineTools` | Headline name containing Kurdish characters (`ê`, `î`, `û`) with `lang='tr'` | `assertContentMatchesLang` detects linguistic mismatch and throws `McpToolException` |
| 5 | `McpNewsTools` | Attempting to create news with `category_id` of root category (`root = 'y'`) | Throws exception rejecting root category and listing available leaf child categories |
| 6 | `McpNewsTools` | Explicit `seo` containing uppercase characters and Turkish combining dot `\u0307` | Normalized via `makeSeo()`, stripping combining dot and converting spaces to underscores |
| 7 | `McpNewsTools::getNews` | Querying news by ID where author user record has been deleted | Returns `user_id` with fallback `username: null`, preserving response structure |
| 8 | `McpNewsTools::checkDuplicateNews` | Submitting headline with exact wording inverted or minor stopword variance | Jaccard token similarity exceeds 0.55 threshold, returning existing article ID warning |
| 9 | `McpFaqTools::batchCreateFaqs` | Array containing 26 FAQ items (limit is 25) | Validation fails before database execution; zero rows inserted |
| 10 | `McpFaqTools::batchCreateFaqs` | Batch where item 15 has duplicate question text already present in database | Batch fails atomically; transaction rolls back; zero rows committed |
| 11 | `McpFaqTools::listFaqs` | Calling with `include_answer: false` vs `include_answer: true` | `answer` key omitted in former to save bandwidth; included in latter for full verification |
| 12 | Nginx `.well-known` | Requesting `/.well-known/oauth-authorization-server` | Preempted by `location ^~ /.well-known/`, passing to PHP router (HTTP 200, not 403) |
| 13 | `editorial_http.py` | Target URL redirects to `http://169.254.169.254/latest/meta-data/` | SSRF guard catches redirect hop and aborts connection with security error |
| 14 | `editorial_http.py` | Server responds with `HTTP 429` and `Retry-After: 4` | Client pauses for 4 seconds, executes retry, and returns cached payload if successful |
| 15 | `check_freshness.py` | Source timestamp `2026-11-10T23:15:00.000Z` parsed on `2026-11-11` | Fractional second parsed correctly; UTC shifted to `02:15+03:00` Istanbul (`today`), status `PASSED` |
| 16 | `check_freshness.py` | Stale article from 2024 with sidebar containing `<time datetime="today">` | Non-content tag stripper removes sidebar; correctly identifies stale main date, status `BLOCKED` |
| 17 | `check_freshness.py` | Draft citing the same primary source URL in 4 different paragraphs | Normalized URL deduplication ensures target is fetched exactly once; single audit line emitted |
| 18 | `check_freshness.py` | Article published yesterday at 23:13 Istanbul time | Emits `age_hours: 13.3`, `is_yesterday_late_evening: true`, triggering local primary radar |
| 19 | `verify_facts_and_links.py` | Draft links valid source in Para 1, but cites fabricated number in Para 4 with same link | All-paragraph audit evaluates Para 4, detects missing figure, and halts with `status: FAIL` |
| 20 | `verify_facts_and_links.py` | Draft cites `%80` while source HTML contains Webpack chunk ID `80` in `<script>` | Non-content stripping cleans scripts; `%80` verified against article prose `"yüzde 80"` |
| 21 | `verify_facts_and_links.py` | Single-segment link `https://vendor.org/download` anchoring semantic version `2.31.0` | Matches `SINGLE_SEGMENT_PRODUCT_SLUGS` allowlist and version regex; status `PASS` |
| 22 | `verify_facts_and_links.py` | English source document paired with Turkish draft claim | Verified via two-sided `source_anchors` (`Coordinator` on English page <-> `Koordinatör` in draft) |
| 23 | `verify_facts_and_links.py` | Draft text writes *"Diyarbakır Büyükşehir Belediyesine"* | Institutional stemmer normalizes to *"Diyarbakır Büyükşehir Belediyesi"*, matching target entity |
| 24 | `process_news_image.py` | Webpage with 50 Next.js `/_next/image?url=https%3A%2F%2F...` images | Unwraps inner URLs before deduplication; 50 distinct candidate images preserved |
| 25 | `process_news_image.py` | Sidebar thumbnail has `srcset="... 3840w"` descriptor | Candidate score capped at 1200; real hero image inside `<article>` wins ranking |
| 26 | `process_news_image.py` | Input image is `1600x1200` (`4:3`) executed with `--mode master` | Image exported at full resolution without pre-cropping to 675px height |
| 27 | `mod_news` | Staging news article with `image_alt` and `image_credit` via MCP | Values stored cleanly in database columns and returned in `get_news` payload |
| 28 | `news.twig` | Article loaded without `image_credit` | `<figure class="detail-hero">` rendered without `<figcaption>`, maintaining visual balance |
| 29 | `tools/mcp_cli.py` | Sub-agent executes `mcp_cli.py call create_news --args-json '{"status": "y"}'` | Safety gate intercepts call, blocks publication attempt (`status="y"`), exits with code 1 |
| 30 | `source_catalog.py` | Running `--scan-today` when target RSS feed is temporarily unreachable | Skips failed feed gracefully, logs warning to stderr, continues scanning remaining feeds |
| 31 | `audit_seo.py` | Draft with 80-character summary and mismatched description | Flags critical violations for summary length (<90) and unsynced description |

---

## 5. E2E Test Assertion Catalog (Tiers 1 to 4)

### Tier 1: Feature Coverage Suite (Happy Path)
*Requires >= 5 independent assertions per feature under typical operating conditions.*

- **Features 1–3 (ORM & MCP Infrastructure)**:
  - Assert `addEqFilter` generates valid SQL condition matching `column = value`.
  - Assert `addLikeSearchFilter` generates parenthesized `(col1 LIKE '%x%' OR col2 LIKE '%x%')`.
  - Assert `McpServer::resolveEnvironment()['site_status']` is `'open'` or `'closed'` (never `'unknown'`).
  - Assert `getRecordAuditMeta` returns `lang_status: 'y'` for active language `tr`.
  - Assert `getRecordAuditMeta` returns accurate `faq_count` matching database records.
- **Features 4–7 (News & Headline Tools)**:
  - Assert `createHeadline` succeeds when passed valid string arguments for `name` and `lang`.
  - Assert `createNews` succeeds when assigned to a valid leaf category (`root = 'n'`).
  - Assert `createNews` normalizes omitted or explicit SEO slug using Wikipedia-style format.
  - Assert `getNews` payload contains non-null `username`, `author_fullname`, `category_name`, `category_seo`.
  - Assert `getNews` payload includes canonical `item` envelope.
- **Features 8–11 (Duplicate News & FAQ Tools)**:
  - Assert `checkDuplicateNews` returns `has_duplicate: false` for unique story titles.
  - Assert `batchCreateFaqs` successfully inserts 3 FAQs in a single atomic call.
  - Assert `listFaqModules` returns registered module list containing `mod_news`.
  - Assert `listFaqs` with `include_answer: true` includes `answer` field in all items.
  - Assert `listFaqs` with `include_answer: false` omits `answer` field from items.
- **Features 12–18 (Nginx & Freshness Sentinel)**:
  - Assert HTTP GET on `/.well-known/oauth-authorization-server` returns HTTP 200 (not 403).
  - Assert `editorial_http.py` successfully fetches valid public HTTPS webpage.
  - Assert `check_freshness.py` passes same-day article with standard ISO timestamp.
  - Assert `check_freshness.py` parses ISO timestamp with fractional seconds (`.000Z`).
  - Assert `check_freshness.py` ignores date strings located inside `<aside>` or `<footer>`.
- **Features 19–23 (Fact Checking & Link Verification)**:
  - Assert `verify_facts_and_links.py` verifies all paragraphs citing the same URL.
  - Assert `verify_facts_and_links.py` matches exact numeric figures in draft against source.
  - Assert `verify_facts_and_links.py` allows valid `/download` page with version anchor.
  - Assert `verify_facts_and_links.py` passes cross-language claim with matching `source_anchors`.
  - Assert `verify_facts_and_links.py` matches institutional entity with Turkish case suffixes.
- **Features 24–29 (Image Intake, Schema & CLI Bridge)**:
  - Assert `process_news_image.py` extracts original URL from Next.js `/_next/image` proxy.
  - Assert `process_news_image.py` scores lead hero image higher than sidebar thumbnails.
  - Assert `process_news_image.py --mode master` outputs clean image with width >= 1200px.
  - Assert `mod_news` stores and retrieves `image_alt` and `image_credit`.
  - Assert `news.twig` renders `<figure>` and `<figcaption>` when credit is present.
  - Assert `tools/mcp_cli.py` executes read-only tool and outputs valid JSON envelope.
- **Features 30–36 (Radar, SEO Alignment & Release)**:
  - Assert `source_catalog.py --scan-today` returns list of articles published today.
  - Assert `audit_seo.py` passes draft matching headline (40–70) and summary (90–120) rules.
  - Assert `SKILL.md` documents reflect two-pass gate and CLI bridge options.
  - Assert codebase contains zero references to obsolete AGENTS.md rule numbers.
  - Assert `tasks/mcp_smoke.php` passes all test stages without errors.
  - Assert version parity across `site.config.php`, `SERVER.md`, and `docs/VERSION.md`.
  - Assert Git tag `v1.0.63` matches repository HEAD.

---

### Tier 2: Boundary & Corner Case Suite
*Requires >= 5 boundary, limit, or error condition assertions per feature.*

- **Features 1–3**:
  - Assert `addEqFilter` rejects column names containing spaces, semicolons, or quotes.
  - Assert `addLikeSearchFilter` handles empty search query string without syntax error.
  - Assert `resolveEnvironment` handles database connection timeout without crashing.
  - Assert `getRecordAuditMeta` returns `faq_count: 0` when record has no FAQs.
  - Assert `getRecordAuditMeta` handles non-existent record ID gracefully.
- **Features 4–7**:
  - Assert `createHeadline` rejects name with length < 2 or > 255 characters.
  - Assert `createNews` rejects root category with explicit error message.
  - Assert `createNews` strips decomposed Unicode combining dots (`\u0307`) from slug.
  - Assert `createNews` rejects duplicate SEO slug within same language.
  - Assert `getNews` throws `McpToolException` when queried with non-existent ID.
- **Features 8–11**:
  - Assert `checkDuplicateNews` detects duplicates with 60% token overlap.
  - Assert `batchCreateFaqs` rejects batch containing 26 items (>25 ceiling).
  - Assert `batchCreateFaqs` rolls back completely if item 3 has an invalid question.
  - Assert `batchCreateFaqs` rejects duplicate questions within the same batch.
  - Assert `listFaqModules` respects `limit` and `offset` pagination parameters.
- **Features 12–18**:
  - Assert Nginx blocks access to `/.git/` and `/.env` (HTTP 403) while allowing `/.well-known/`.
  - Assert `editorial_http.py` blocks loopback IP (`127.0.0.1`) and link-local (`169.254.169.254`).
  - Assert `editorial_http.py` caps download at `max_bytes` without memory exhaustion.
  - Assert `check_freshness.py` blocks article published yesterday at 23:59:59.
  - Assert `check_freshness.py` strips `<script>` containing `"date":"2026-11-11"` false-positive.
- **Features 19–23**:
  - Assert `verify_facts_and_links.py` catches numeric discrepancy in paragraph 5.
  - Assert `verify_facts_and_links.py` rejects single-segment URL not in allowlist (e.g. `/news`).
  - Assert `verify_facts_and_links.py` rejects allowlisted page if semantic version anchor is missing.
  - Assert `verify_facts_and_links.py` fails if Turkish draft term is missing from citing text.
  - Assert `verify_facts_and_links.py` flags unlinked attribution phrase (`paylaşılan bilgilere göre`).
- **Features 24–29**:
  - Assert `process_news_image.py` handles corrupted or truncated Next.js proxy query string.
  - Assert `process_news_image.py` caps `3840w` srcset descriptor to 1200 score.
  - Assert `process_news_image.py --mode master` exits with error if source width < 1200 in strict mode.
  - Assert `mod_news` truncates `image_alt` exceeding 255 characters cleanly.
  - Assert `tools/mcp_cli.py` rejects tool calls attempting `status="y"` publication.
- **Features 30–36**:
  - Assert `source_catalog.py --scan-today` handles malformed XML feed gracefully.
  - Assert `audit_seo.py` fails draft with summary of 89 characters (minimum is 90).
  - Assert `audit_seo.py` fails draft with summary of 121 characters (maximum is 120).
  - Assert `audit_seo.py` fails draft when `description` does not match `summary`.
  - Assert release verification fails if any repository file has uncommitted modifications.

---

### Tier 3: Cross-Feature Combination Suite
*Tests pairwise and cross-module interactions across the editorial lifecycle.*

1. **Conjunctive Filtering & FAQ Staging Interaction (F1 + F3 + F9 + F11)**:
   - Staging a batch of 6 FAQs for news item ID 128 using `batchCreateFaqs` (F9).
   - Calling `list_faqs(mod_name: 'mod_news', vid: 128, lang: 'tr', include_answer: true)` (F1, F11).
   - Assert `addEqFilter` preserves all three clauses: returns exactly 6 items, answers populated, zero cross-record leakage.
   - Assert `getRecordAuditMeta` on news ID 128 returns `faq_count: 6`, `faq_draft_count: 6` (F3).
2. **Near-Midnight Block & Local Primary Radar Recovery (F14 + F18 + F30 + F13)**:
   - Input seed URL published yesterday at 23:13 Istanbul time.
   - `check_freshness.py` blocks seed URL, returning `is_yesterday_late_evening: true` (F14, F18).
   - Triggering `source_catalog.py --scan-today --category amed_diyarbakir` fetches live feeds via `editorial_http.py` (F13, F30).
   - Assert same-day local primary follow-up discovered, allowing run to proceed with seed URL demoted to supplementary.
3. **Next.js Image Extraction & Mod News Visual Pipeline (F24 + F25 + F26 + F27 + F28)**:
   - `process_news_image.py` extracts unwrapped master image (`>=1200px`) from Next.js article (F24, F25, F26).
   - MCP `create_news` receives master image, `image_alt`, and `image_credit` (F27).
   - Generated `news.twig` template renders `<figure class="detail-hero">` with `<figcaption>` credit and JSON-LD `creditText` (F28).
   - Assert no upscaling artifacts; Discover aspect ratios (`16:9`, `4:3`, `1:1`) generated cleanly.
4. **Foreign Source Cross-Language Anchoring & Multi-Paragraph Fact Verification (F19 + F20 + F22 + F23)**:
   - Multi-paragraph draft citing English official vendor doc (`role: supplementary`, `source_lang: en`) (F22).
   - Paragraph 1 anchors `"Coordinator"` <-> `"Koordinatör"`. Paragraph 3 cites municipal data with Turkish suffix `"Belediyesine"` (F23).
   - Paragraph 4 cites numeric figure `%80` (F20).
   - Assert `verify_facts_and_links.py` evaluates all paragraphs (F19) and passes with zero hallucination warnings.
5. **Swarm CLI Bridge & Strict Editorial Safety Pipeline (F29 + F6 + F7 + F31)**:
   - Swarm sub-agent invokes `tools/mcp_cli.py call create_news` with draft content (F29).
   - Assert CLI enforces `status="n"` and `username="habermerkezi"`.
   - `McpNewsTools` normalizes SEO slug (F6). `get_news` enriches author and category names (F7).
   - Resulting record JSON pipes into `audit_seo.py` / `audit_content_seo.py`, scoring >= 90/100 (F31).

---

### Tier 4: Real-World Application Suite
*End-to-end integration scenarios simulating production journalism workflows.*

1. **Scenario 1: Live Breaking News Intake (`rudaw.net` Staging Flow)**:
   - Input: Breaking news URL from regional source published today at 09:30 +03:00.
   - Step 1: `check_freshness.py` Stage 0 pre-gate runs, validating verified TLS and same-day timestamp -> PASS.
   - Step 2: `kevkolin` initializes dossier. `process_news_image.py` unwraps Next.js hero photo, generating clean 1600px master -> PASS.
   - Step 3: `kevanger` drafts article with >=3 `<h2>` headings, 90–120 char summary, and synced description.
   - Step 4: `kevrast` Stage 2 verifies all cited paragraphs and numeric figures against source HTML -> PASS.
   - Step 5: `tools/mcp_cli.py` creates draft news (`status: n`) and stages 5 FAQs via `batch_create_faqs`.
   - Step 6: Post-staging audit confirms `site_status == 'open'`, `lang_status == 'y'`, and SEO score >= 90/100.
2. **Scenario 2: Near-Midnight Overnight Municipal Announcement Recovery**:
   - Input: Municipality announcement published at 23:45 yesterday evening.
   - Step 1: Stage 0 pre-gate halts with near-midnight diagnostic (`age_hours: 10.5, is_yesterday_late_evening: true`).
   - Step 2: Automated recovery scans local radar (`source_catalog.py --scan-today`). Discovers morning follow-up from local agency (`amidahaber.com`).
   - Step 3: Morning report promoted to `primary`; midnight announcement retained as `supplementary`.
   - Step 4: Article drafted, cross-referencing both sources. Verified via `check_freshness.py --dossier` and `verify_facts_and_links.py`.
   - Step 5: Staged cleanly to CMS without gate bypass.
3. **Scenario 3: English Technical Release & Cross-Language Attribution**:
   - Input: Official software release from foreign vendor (`https://antigravity.google/download`, `source_lang: en`).
   - Step 1: R2 precision check verifies single-segment allowlist and version string anchor (`2.31.0`) -> PASS.
   - Step 2: Dossier declares `source_anchors` mapping English concepts to Turkish terminology.
   - Step 3: Drafter writes Turkish article using institutional suffixes and attribution phrases.
   - Step 4: `verify_facts_and_links.py` performs two-sided verification on all cited blocks.
   - Step 5: CMS staging executes with clean media attribution.
4. **Scenario 4: Adversarial Regression & Security Stress Scenario**:
   - Attack vectors evaluated:
     - SSRF attempt via malicious redirect to cloud metadata service (`169.254.169.254`).
     - Staging news into hidden structural category (`root = 'y'`).
     - Submitting fabricated statistics hidden in paragraph 6 of a long draft.
     - Attempting unauthorized direct publish (`status = 'y'`) via CLI bridge.
     - Accessing internal Nginx dotfiles (`/.env`, `/.git`).
   - System response: All attack vectors fail-closed with structured error logs and zero data corruption.
5. **Scenario 5: Full Release Validation & GitHub Deployment Workflow**:
   - Step 1: Complete test execution across Tiers 1–4 via test runner harness.
   - Step 2: Quality guards executed (`code_separation_guard.py`, `i18n_guard.py`, `module_boundary_guard.py`).
   - Step 3: `tasks/mcp_smoke.php` passes on local Docker environment.
   - Step 4: Version parity validated across all four version-bearing files (`v1.0.63`).
   - Step 5: Git commit created, tagged `v1.0.63`, and pushed to origin.

---

## 6. Caveats

- **No Caveats**: All 36 features across Milestones M1 to M5 were verified by direct inspection of codebase source files, database schemas, Nginx configuration, and test suites.
- Assumptions regarding remote network services: All network tests in Tier 1 and Tier 2 assume standardized HTTP mock responses or fixture files under `tests/fixtures/editorial/` to ensure deterministic execution independent of live external media outages.

---

## 7. Conclusion

The specification mining investigation has cataloged the complete functional surface, interface parameters, failure modes, and assertion criteria for all 36 features required by release `v1.0.63`. 
The findings provide unambiguous evidence for test harness and suite construction across Tiers 1 to 4, guaranteeing strict compliance with Amed Framework architecture, `AGENTS.md` rules 1 to 9, and the Kevanger editorial pipeline remediation plan.

---

## 8. Verification Method

To independently verify the observations and findings in this specification report:

1. **Verify PHP Mysqli / Database Fetch Bug**:
   ```bash
   php -r 'require "lib/Database/Database.php"; require "lib/Database/Adapter/Mysqli.php";'
   grep -n "->fetch()" lib/McpServer.class.php lib/McpCrudToolsBase.class.php
   ```
2. **Verify Headline TypeError Bug**:
   ```bash
   grep -n "assertContentMatchesLang" lib/McpHeadlineTools.class.php
   ```
3. **Verify Sequential Filter Overwrite in MCP Classes**:
   ```bash
   grep -n "where_" lib/McpNewsTools.class.php lib/McpFaqTools.class.php lib/McpPublicationTools.class.php
   ```
4. **Verify Nginx Dotfile Block**:
   ```bash
   grep -n -C 5 "location ~ /\\." docker/nginx/prod.conf
   ```
5. **Verify Python Sentinel & Fact-Checking Bugs**:
   ```bash
   grep -n -C 3 "CERT_NONE" .agents/skills/kevtemen/scripts/check_freshness.py
   grep -n -C 3 "_find_citing_block" .agents/skills/kevrast/scripts/verify_facts_and_links.py
   grep -n -C 3 "split(\"?\")[0]" .agents/skills/kevanger/scripts/process_news_image.py
   ```
6. **Verify Stale Rule Citations**:
   ```bash
   grep -n "Rule 2" .agents/skills/kevseo/SKILL.md .agents/skills/kevseo/scripts/audit_seo.py
   grep -n "Rule 10" mod/mod_news/validator/add.validator.php
   grep -n "Rule 7" docs/CODE_QUALITY_AGENT_STRATEGY.md
   ```
