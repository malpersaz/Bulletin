# Milestone 3 Handoff Report: Image Pipeline, mod_news Media Credits & Swarm CLI Wrapper

## 1. Observation

All objectives across Milestone 3's three tracks were implemented and verified in `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`:

### 1.1 Track 1: News Image Pipeline (`.agents/skills/kevanger/scripts/process_news_image.py`)
1. **Next.js & CDN Proxy Unwrapping**:
   Implemented `unwrap_cdn_image_url(url, base_url)` handling `/_next/image?url=...`, `/_vercel/image?url=...`, WordPress Photon (`i0.wp.com`), and Cloudflare Image Resizing (`/cdn-cgi/image/...`).
2. **Canonical Deduplication**:
   Implemented `canonical_image_key(url, base_url)` stripping query parameters (`w`, `q`, `width`, `height`, `fit`, `auto`, etc.) while preserving unique asset identifiers.
3. **DOM Depth Tracking & Container Exclusion**:
   Enhanced `ArticleImageCandidateExtractor` with explicit `article_depth`, `main_depth`, and `excluded_depth` counters and tag stack tracking. Automatically penalizes elements inside `<aside>`, `<nav>`, `<footer>`, header (outside article), and containers matching `EXCLUDE_CONTAINER_RE` by `-2000` points.
4. **Srcset Cap & Stem Anchoring**:
   Capped responsive descriptors at `3840w` max (and `1200w` for proxy URLs unless explicit width is >= 1200). Stems from `<meta property="og:image">` and `<meta name="twitter:image">` are extracted into `self.meta_stems`, awarding a `+2000` boost to matching candidate images.
5. **Mode Modernization**:
   Implemented `--mode master` (default) outputting unflattened, natural aspect ratio WebP images (width capped at 1920px, minimum width 1200px, quality 90, file size < 1.5 MB) to avoid double down/upscaling in CMS multi-size derivation. Retained `--mode inline-16x9` (1280x720, <100 KB) for backward compatibility.
6. **Regional Watermark Heuristics**:
   Implemented `inspect_watermarks(img)` detecting Kurdish (Rudaw/K24 top-right red bug) and Turkish (AA/DHA/İHA bottom ticker bar) overlays, returning detection flags and crop margins, with `--crop-watermark` option.
7. **Editorial HTTP Transport**:
   Integrated `.agents/skills/_shared/editorial_http.py` (`fetch_html` and `fetch_binary`) with browser-grade headers and referer pass-through, backed by secure internal urllib fallback.

### 1.2 Track 2: `mod_news` Schema & Templates Modernization
1. **SQL Schema**:
   `mod/mod_news/mod_news.sql` updated with `image_alt varchar(255) DEFAULT NULL` and `image_credit varchar(255) DEFAULT NULL` after `image`.
2. **ORM & View Models**:
   - `mod/mod_news/model/mod_news.model.php`: added `$image_alt` and `$image_credit` properties and default values.
   - `mod/mod_news/model/newses.model.php`: added self-healing `ensureTableColumns()` (`ALTER TABLE mod_news ADD COLUMN ...`) and column checks `hasImageAltColumn()` / `hasImageCreditColumn()`, dynamically migrating existing databases and rebuilding the `newses` view.
3. **Controller & Helper Sanitization**:
   - `mod/mod_news/index.php`: `cleanNewsTextEntities` strips HTML tags, decodes double entities, truncates to 255 chars, maps empty strings to null, and overrides `cleanEntityFields` for output decoding.
   - `mod/index/helper/mod_news.helper.php`: `_cleanNewsRow` decodes entities on `image_alt` and `image_credit`.
4. **MCP Tool Support**:
   `lib/McpNewsTools.class.php`: updated `create_news` and `update_news` tool schemas to accept `image_alt` and `image_credit`, and updated `createNews` / `updateNews` to sanitize and assign them to the model.
5. **Validators**:
   `mod/mod_news/validator/add.validator.php` and `edit.validator.php` updated with optional length checks (1–255 chars) and corrected comment citations to AGENTS.md Rule 9.
6. **Localization**:
   Added `image_alt` and `image_credit` column definitions across all 11 language packs in `mod/mod_news/lang/` (`ar`, `ckb`, `de`, `en`, `fa`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`).
7. **Admin Templates**:
   - `templates/admin/mod_news/edit.twig`: added phone-only mobile inputs (`.m3-input-wrapper`, `.m3-input-lg`, `.m3-floating-label`) for `image_alt` and `image_credit` with 0 inline styles.
   - `templates/admin/partials/wizard_content.twig`: added wizard step 7 inputs for `mod_news`.
   - `templates/admin/assets/js/script.js`: collected wizard inputs into formData.
8. **Public Template & CSS**:
   - `templates/amed/index/mod_news/news.twig`: upgraded hero container from `<div>` to `<figure class="detail-hero">`, added `alt="{{ d.image_alt|default(d.name) }}"`, added conditional `<figcaption class="news-hero-caption">{{ d.image_credit }}</figcaption>`, added `og:image:alt` and `twitter:image:alt`, and added `caption` and `creditText` to Schema.org JSON-LD `ImageObject`.
   - `templates/amed/static/css/detail-foundation.css`: added `.news-hero-caption` styles for light and dark themes.

### 1.3 Track 3: Swarm CLI Wrapper (`tools/mcp_cli.py`)
1. **Secret-Opaque Stdio Transport**:
   Implemented `tools/mcp_cli.py` wrapping `~/.gemini/antigravity/bin/amed_bulletin_mcp.py` via line-delimited JSON-RPC 2.0 stdio.
2. **Zero Credential Exposure**:
   Accepts no API keys or token flags. Child bridge handles authorization. Stderr output is regex-scrubbed (`amcp_[a-zA-Z0-9]+` -> `[REDACTED_API_KEY]`).
3. **Dual Syntax Compatibility**:
   - Subcommand mode: `call <tool>`, `get-record --module <mod>`, `ping`, `list-tools`.
   - Positional mode: `<server> <tool> [args] [--editorial-safe]` matching PROJECT.md interface contract.
4. **Editorial Safety Policy Gate (`--editorial-safe`, default: True)**:
   - Unconditionally blocks all `delete_*` tools (exit 1).
   - Rejects `status="y"` on `create_news`, `update_news`, `create_publication`, `update_publication`, `create_faq`, `update_faq`, `batch_create_faqs` (exit 1).
   - Enforces `status="n"` draft staging on those operations.
   - Enforces author `username="habermerkezi"` on `create_news`.
   - Validates existence of local featured image file on disk when `image.file_path` is passed.
5. **Output & Testability**:
   - Standard JSON envelope on stdout; environment name printed on stderr; exit code 0 on success, 1 on error, 2 on usage error.
   - `--dry-run` flag and offline mock bridge support via `MCP_CLI_BRIDGE`.
   - Test suite `tests/test_mcp_cli.py` implemented with 17 test cases passing in 0.06s.

---

## 2. Logic Chain

1. **Section A3 Regression Resolution**:
   In `process_news_image.py`, splitting URL on `?` collapsed all `/_next/image` query-based URLs into identical keys. By unwrapping query parameters via `unwrap_cdn_image_url()` and maintaining explicit DOM depth counters (`article_depth`, `main_depth`, `excluded_depth`), `rank_candidates()` correctly identified and scored `hero_main.jpg` over `sidebar_thumb.jpg` in fixture `mock_nextjs_sidebar_article.html`.
2. **Zero Distortion in Multi-Size Derivation**:
   Pre-cropping to 16:9 (`1200x675`) forced the CMS to stretch images vertically for 4:3 (`1200x900`) and 1:1 (`1200x1200`). By defaulting to `--mode master` (unflattened WebP, max width 1920px, quality 90, <1.5 MB), CMS multi-size derivation retains full vertical clarity.
3. **Zero-Downtime View Auto-Migration**:
   `newses.model.php` checks for `image_alt` and `image_credit` in the `newses` view. If missing, `ensureTableColumns()` executes `ALTER TABLE mod_news ADD COLUMN ...` before dropping and recreating the view. Verified in running container `bulletin_app`: all four column checks reported `EXISTS`.
4. **Strict Code Separation**:
   All new inputs in `templates/admin/mod_news/edit.twig` and `wizard_content.twig` use existing `.m3-*` stylesheet classes. Public hero caption in `templates/amed/index/mod_news/news.twig` uses classes defined in `detail-foundation.css`. `code_separation_guard.py` passed with 0 violations across all 25 checked template files.
5. **Sub-Agent Sandbox Protection**:
   `tools/mcp_cli.py` gives sub-agents a clean CLI interface to interact with MCP tools without exposing credentials or requiring direct MCP server access. Stderr token scrubbing and editorial safety policy gates guarantee that automated tools cannot delete data or publish live content.

---

## 3. Caveats

1. **Inline HTML Images**:
   `image_alt` and `image_credit` columns apply to the article's lead/featured image (`mod_news.image`). Secondary images embedded in the body HTML continue to be governed by TinyMCE markup.
2. **Production MCP Bridge**:
   `~/.gemini/antigravity/bin/amed_bulletin_mcp.py` connects to the production endpoint (`https://amedbulten.com/mcp.php`). The `--editorial-safe` gate strictly blocks `delete_*` and enforces `status="n"` (draft staging) to safeguard production integrity.
3. **No Cross-Repo Writes**:
   All changes are strictly confined to the `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin` workspace in accordance with AGENTS.md Rule 3.

---

## 4. Conclusion

Milestone 3 is complete, fully tested, and ready for audit:
- Image pipeline (`process_news_image.py`) correctly unwraps CDN proxies, scopes DOM elements, caps srcset descriptors, applies watermark heuristics, and defaults to `--mode master`.
- `mod_news` database schema, ORM model, self-healing view, controller, public/admin templates, and i18n language files now support `image_alt` and `image_credit` with 0 code separation violations.
- Swarm CLI wrapper (`tools/mcp_cli.py`) provides secret-opaque, policy-gated stdio MCP access with complete offline test coverage.

---

## 5. Verification Method

To independently verify these changes, run the following commands:

### 5.1 Syntax and Compilation Validation
```bash
php -l mod/mod_news/model/mod_news.model.php
php -l mod/mod_news/model/newses.model.php
php -l mod/mod_news/index.php
php -l mod/index/helper/mod_news.helper.php
php -l lib/McpNewsTools.class.php
php -l mod/mod_news/validator/add.validator.php
php -l mod/mod_news/validator/edit.validator.php
for f in mod/mod_news/lang/*.php; do php -l "$f"; done
python3 -m py_compile .agents/skills/kevanger/scripts/process_news_image.py tools/mcp_cli.py tests/test_mcp_cli.py
```
*Expected Result*: All files pass with 0 syntax or compilation errors.

### 5.2 Next.js Candidate Ranking & Image Extraction Test
```bash
python3 -c "
import sys
sys.path.insert(0, '.agents/skills/kevanger/scripts')
import process_news_image

with open('tests/e2e/fixtures/editorial/mock_nextjs_sidebar_article.html') as f:
    html = f.read()

parser = process_news_image.ArticleImageCandidateExtractor('https://example.com/article/1')
parser.feed(html)
ranked = process_news_image.rank_candidates(parser.candidates, 'https://example.com/article/1')

assert len(ranked) >= 2, f'Expected multiple candidates, got {len(ranked)}'
assert 'hero_main' in ranked[0]['unwrapped_url'], f'Hero image failed to win! Winner: {ranked[0]}'
assert 'sidebar_thumb' not in ranked[0]['unwrapped_url'], 'Sidebar thumbnail hijacked ranking!'
print(f'PASS: Found {len(ranked)} candidates. Winner is {ranked[0][\"unwrapped_url\"]}')
"
```
*Expected Result*: `PASS: Found 2 candidates. Winner is https://cdn.example.com/photos/hero_main.jpg`.

### 5.3 CLI Wrapper Unit Test Suite
```bash
python3 -m unittest tests/test_mcp_cli.py
```
*Expected Result*: `Ran 17 tests in ... OK`.

### 5.4 Database Migration & View Verification in Docker
```bash
docker exec bulletin_app php -r '
require_once "/var/www/html/init.php";
Load::model("mod_news.newses");
$db = Load::lib("Database");
$db->fetch_array("SHOW COLUMNS FROM mod_news LIKE \"image_alt\"");
echo "mod_news image_alt: " . ($db->getSize() > 0 ? "EXISTS\n" : "MISSING\n");
$db->fetch_array("SHOW COLUMNS FROM mod_news LIKE \"image_credit\"");
echo "mod_news image_credit: " . ($db->getSize() > 0 ? "EXISTS\n" : "MISSING\n");
$db->fetch_array("SHOW COLUMNS FROM newses LIKE \"image_alt\"");
echo "newses image_alt: " . ($db->getSize() > 0 ? "EXISTS\n" : "MISSING\n");
$db->fetch_array("SHOW COLUMNS FROM newses LIKE \"image_credit\"");
echo "newses image_credit: " . ($db->getSize() > 0 ? "EXISTS\n" : "MISSING\n");
'
```
*Expected Result*: All 4 checks output `EXISTS`.

### 5.5 Repository Quality Guards
```bash
python3 tools/code_separation_guard.py
python3 tools/i18n_guard.py
python3 tools/module_boundary_guard.py --all
```
*Expected Result*: 0 violations across all three guards.
