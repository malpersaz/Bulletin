# Handoff Report: Tier 1 Feature Coverage Suite Specification (Features 24–36)

**Milestone:** E2E-M2 (Tier 1 Feature Coverage Suite)  
**Agent:** Explorer 3 (`explorer_e2e_m2_3`)  
**Scope:** Features 24 through 36 (Image Processing, Hero Figure & JSON-LD, Swarm CLI, Radar, SEO Auditing, Skill Docs, Code Quality Guards, Regression Smoke Tests, and Version/Release)  
**Working Directory:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**State Folder:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_e2e_m2_3/`  
**Date:** 2026-11-11  

---

## 1. Observation

Direct observations and evidence collected across active workspace files, test harnesses, fixtures, and target scripts:

1. **Test Runner & Harness Architecture (`tests/e2e/harness/`)**:
   - `tests/e2e/harness/runner.py:78-150`: The `TestRunnerEngine` discovers test modules dynamically by recursively walking directories `tier1`, `tier2`, `tier3`, and `tier4` for files named `test_*.py`. It inspects discovered `unittest.TestCase` classes for decorators `@tier(int)` and `@feature(*ids)` defined in `tests/e2e/harness/base.py`. Test methods starting with `test_` inherit class-level metadata and can override them via method-level decorators.
   - `tests/e2e/harness/process.py:37-132`: Provides `run_cli`, `run_python`, and `run_php` with timeout management, duration measurement, and structured `ProcessResult(cmd, exit_code, stdout, stderr, duration_sec, timed_out)`. Methods `.json()` and `.success()` allow direct parsing.
   - `tests/e2e/harness/base.py:52-160`: `E2ETestCase` initializes an isolated `Sandbox` on `setUp()` and cleans it on `tearDown()`. It provides a lazy embedded `MockHttpServer` (`self.mock_http`), a `PhpBridge` (`self.php`), and domain assertion wrappers (`assertExitCode`, `assertJsonEnvelope`, `assertMcpSuccess`, `assertMcpError`, `assertHtmlTagPresent`, `assertHtmlTagAbsent`, `assertNoInlineStylesOrScripts`, `assertWikipediaSlug`, `assertNormalizedNumber`, `assertDateIsToday`).
   - `tests/e2e/harness/assertions.py:215-270`: `assert_no_inline_styles_or_scripts` strictly enforces AGENTS.md Rule 1. It forbids `style="..."`, `<style>`, and inline `on[a-z]+="..."` handlers. It permits `<script type="application/ld+json">` and external script tags `<script src="..." ...></script>` with empty bodies.

2. **Image Pipeline Implementation (`.agents/skills/kevanger/scripts/process_news_image.py`)**:
   - Line 303: `u = c["url"].split("?")[0].rstrip("/")` currently collapses all Next.js CDN proxy URLs (`/_next/image?url=...`) to `https://domain.com/_next/image`, causing collisions when an article has multiple Next.js images and dropping candidate variety.
   - Lines 148, 286: `in_article_or_main` awards a `+500` score bonus to anything inside `<main>`, erroneously boosting sidebar recommendation cards.
   - Lines 176-185, 282: Descriptors like `srcset="thumb.jpg 3840w"` directly set `width_hint = 3840`, allowing inflated sidebar thumbnails to beat authentic lead article photos (1920w).
   - Lines 384-391: Image processing forces center-cropping to `16:9` (`1200x675`). There is no `--mode master` flag to preserve clean, unflattened, natural aspect ratio `>= 1200px` masters.

3. **Database Schema & Public Template (`mod/mod_news/` & `templates/amed/index/mod_news/news.twig`)**:
   - `mod/mod_news/mod_news.sql:1-30`: Table `mod_news` defines `image varchar(255) DEFAULT NULL`, but lacks `image_alt` and `image_credit` columns (`VARCHAR(255) DEFAULT NULL`).
   - `templates/amed/index/mod_news/news.twig:411-417`: The hero image is currently rendered inside a non-semantic `<div class="detail-hero">` and lacks a `<figcaption class="news-hero-caption">{{ d.image_credit }}</figcaption>`.
   - `templates/amed/index/mod_news/news.twig:158-186`: The JSON-LD schema renders `ImageObject` with `@type`, `url`, `width`, `height`, but omits `caption` and `creditText` (`image_credit`).

4. **Swarm CLI & Stdio Bridge (`tools/mcp_cli.py`)**:
   - Currently, `tools/mcp_cli.py` is not yet created (planned in Milestone M3).
   - Architectural contract in `PROJECT.md:93-95` and `docs/plan/v1.0.63-...:100-110`:
     - Must provide secret-opaque stdio bridge spawning `amed_bulletin_mcp.py`.
     - Subcommands: `call <tool_name> --args-json '<json>'` and `get-record --module <mod> --id <id> --out <file>`.
     - Safety gate: `--editorial-safe` (default enabled) hard-blocks `delete_*` tools, forbids `status="y"` on news/faq creation/updates (draft staging only), and requires `username="habermerkezi"`.
     - Emits standard JSON envelope on stdout and logs environment (`production` vs `development`) to stderr.

5. **Radar & SEO Alignment (`source_catalog.py` & `audit_seo.py`)**:
   - `.agents/skills/kevkolin/scripts/source_catalog.py:155-164`: Currently supports `--list-sources`, `--category`, `--new-dossier`, `--date`, but lacks `--scan-today` for live RSS/Atom/sitemap scanning.
   - `.agents/skills/kevseo/scripts/audit_seo.py:130-143`: Enforces headline (40–70) and deck summary (90–120), but requires alignment with `tools/audit_content_seo.py --profile editorial` for description sync, `>= 3 <h2>` headings, and strict heading hierarchy.

6. **Documentation & Rule Citations (`AGENTS.md`, `SKILL.md`, `tools/`)**:
   - `AGENTS.md` establishes the primary hierarchy: Rule 1 is Strict Code Separation, Rule 6 is English Code & Comments, Rule 9 is URL Generation Parity (Wikipedia Style).
   - Stale citations exist in `.agents/skills/kevseo/SKILL.md:39` (cites "AGENTS.md Rule 2"), `.agents/skills/kevseo/scripts/audit_seo.py:63` (cites "AGENTS.md Rule 2"), `tools/code_separation_guard.py:6` (cites "AGENTS.md Rule 2"), `mod/mod_news/validator/add.validator.php:141` (cites "AGENTS.md Rule 10"), and `docs/CODE_QUALITY_AGENT_STRATEGY.md:16, 20` (cites "AGENTS.md Rule 7").

7. **Verification & Release Files (`tasks/mcp_smoke.php`, `docs/VERSION.md`, Git)**:
   - `tasks/mcp_smoke.php:1-990`: Contains extensive CLI tests for MCP endpoints, exiting with code 0 on pass or code 1 on fail.
   - `tools/verify_news_pipeline.sh:1-168`: Multi-stage editorial pipeline script supporting `--draft`, `--dossier`, `--headline`, `--summary`, `--no-network`.
   - `docs/VERSION.md:1-25`: Currently records `## v1.0.62 (2026-11-11)`. Prepares for `## v1.0.63 (2026-11-11)`.
   - `config/site.config.php:61-75`: Automatically resolves version from `docs/VERSION.md` with fallback `'1.0.62'`.
   - `docs/SERVER.md:3`: References `v1.0.62`.

---

## 2. Logic Chain

1. **Opaque-Box Requirement (Dual Track Pattern)**:
   From **Observation 1**, tests must never rely on internal monkey-patching or bypass validation layers. Using `tests/e2e/harness/process.py` (`run_cli`, `run_python`, `run_php`) and `E2ETestCase` domain assertions guarantees genuine external evaluation of scripts, CLI wrappers, templates, and database state.
2. **Deterministic Offline Execution**:
   From **Observation 1 & 5**, network variability must be eliminated during testing. Using `self.mock_http` with local XML fixtures (`rss_diyarbakir_today.xml`, `sitemap_news_today.xml`) and HTML fixtures (`mock_nextjs_sidebar_article.html`) ensures 100% deterministic, offline execution under `--no-network`.
3. **Image Pipeline Rigor (Features 24, 25, 26)**:
   From **Observation 2**, test cases must verify:
   - Next.js URL query parameter extraction and decoding before deduplication (`F24`).
   - Elimination of sidebar candidate false-positives by DOM scoping to `<article>` and capping inflated 3840w `srcset` descriptors at a realistic 1200 ceiling (`F25`).
   - Clean aspect-ratio-preserving master generation (`>= 1200px`) via `--mode master` (`F26`).
4. **Media Credits & Presentation (Features 27, 28)**:
   From **Observation 3**, full pipeline coverage requires verifying database schema columns (`image_alt`, `image_credit`), MCP tool persistence and retrieval, semantic `<figure class="detail-hero">` and `<figcaption>` rendering, and JSON-LD schema enrichment while maintaining 0 Code Separation violations.
5. **Swarm CLI & Safety (Feature 29)**:
   From **Observation 4**, tests must verify secret opaqueness (zero credentials leaked on stdout/stderr), stdout JSON envelope formatting, and strict mutation blocking by `--editorial-safe` (`delete_*` rejected, `status="y"` publication blocked).
6. **Editorial Radar & SEO Alignment (Features 30, 31)**:
   From **Observation 5**, test cases must assert that `--scan-today` accurately parses ISO timestamps, isolates today's articles, handles feed errors gracefully, and that `audit_seo.py` enforces editorial thresholds (headline 40–70, summary 90–120, description sync, `>= 3 <h2>`).
7. **Quality Governance & Release Parity (Features 32, 33, 34, 35, 36)**:
   From **Observation 6 & 7**, tests must verify that all 5 skill instruction files are synchronized, all stale AGENTS.md rule numbers are corrected, regression smoke tests exit 0, documentation files reflect `v1.0.63` without drift, and Git release tags are properly structured.

---

## 3. Concrete Test Blueprints (Features 24 to 36)

Every feature contains at least 5 distinct test cases (minimum 65 test cases total across 13 features).

### Feature 24: `process_news_image.py` Next.js CDN Proxy Unwrap (`image_nextjs_unwrap`)
- **Module File**: `tests/e2e/tier1/test_f24_image_nextjs_unwrap.py`
- **Test Class**: `TestF24ImageNextJsUnwrap(E2ETestCase)` (`@tier(1)`, `@feature(24)`)

1. **`test_f24_nextjs_single_candidate_unwrap`**:
   - **Inputs**: Mock article HTML served via `self.mock_http` containing:
     `<img src="/_next/image?url=https%3A%2F%2Fcdn.rudaw.net%2Fimages%2Fpark_lead.jpg&w=1920&q=75" alt="Lead">`.
   - **Execution**: `self.run_python([".agents/skills/kevanger/scripts/process_news_image.py", "--from-article", self.mock_http.get_url("/article"), "--source", "Rûdaw", "--out", os.path.join(self.sandbox.root, "out.webp")])`.
   - **Expected Behavior**: Process exits 0; unwraps query string to `https://cdn.rudaw.net/images/park_lead.jpg`; outputs JSON.
   - **Assertions**: `self.assertExitCode(res, 0)`; `payload = self.assertJsonEnvelope(res.stdout)`; `self.assertEqual(payload["source_candidate_url"], "https://cdn.rudaw.net/images/park_lead.jpg")`.

2. **`test_f24_nextjs_multiple_images_no_collision`**:
   - **Inputs**: HTML fixture with 4 distinct Next.js images (`url=img1.jpg`, `url=img2.jpg`, `url=img3.jpg`, `url=img4.jpg`).
   - **Execution**: Run image extraction parser against HTML fixture.
   - **Expected Behavior**: All 4 URLs preserve distinct unwrapped targets and are not collapsed to `/_next/image`.
   - **Assertions**: Parse candidate list; assert `len(unique_unwrapped) == 4`.

3. **`test_f24_nextjs_relative_url_unwrap`**:
   - **Inputs**: HTML containing `/_next/image?url=%2Fstatic%2Fphotos%2Fhero.jpg&w=1200&q=75`.
   - **Execution**: `run_python` candidate extraction.
   - **Expected Behavior**: Unwrapped relative path is joined with base URL into `https://example.org/static/photos/hero.jpg`.
   - **Assertions**: `self.assertTrue(payload["source_candidate_url"].startswith("http"))`; `self.assertIn("/static/photos/hero.jpg", payload["source_candidate_url"])`.

4. **`test_f24_nextjs_ssrf_blocked_unwrapped_target`**:
   - **Inputs**: Next.js proxy URL embedding internal IP: `/_next/image?url=http%3A%2F%2F169.254.169.254%2Flatest%2Fmeta-data%2Fimage.jpg&w=1200&q=75`.
   - **Execution**: `run_python` against page containing SSRF candidate.
   - **Expected Behavior**: SSRF guard (`validate_public_url`) triggers on the unwrapped target; candidate is rejected/skipped; script does not execute HTTP request to internal IP.
   - **Assertions**: Assert no HTTP request to link-local IP; process either selects fallback candidate or exits 1 with SSRF error.

5. **`test_f24_nextjs_query_decoding_with_special_chars`**:
   - **Inputs**: Next.js proxy URL containing URL-encoded UTF-8 characters and spaces: `/_next/image?url=https%3A%2F%2Fcdn.org%2Fmedya%2Fdiyarbak%C4%B1r%20park%C4%B1.jpg&w=1200&q=75`.
   - **Execution**: `run_python` image candidate extraction.
   - **Expected Behavior**: Successfully decodes into `https://cdn.org/medya/diyarbakır parkı.jpg` without truncation or encoding corruption.
   - **Assertions**: Assert candidate URL contains properly unquoted UTF-8 string.

---

### Feature 25: `process_news_image.py` Hero Scoring & Srcset Cap (`image_hero_scoring`)
- **Module File**: `tests/e2e/tier1/test_f25_image_hero_scoring.py`
- **Test Class**: `TestF25ImageHeroScoring(E2ETestCase)` (`@tier(1)`, `@feature(25)`)

1. **`test_f25_lead_hero_in_article_beats_sidebar_3840w`**:
   - **Inputs**: HTML fixture (`tests/e2e/fixtures/editorial/mock_nextjs_sidebar_article.html`) containing 1920x1080 hero image inside `<article>` and thumbnail inside `<aside>` with `srcset="thumb.jpg 3840w"`.
   - **Execution**: `self.run_python([".agents/skills/kevanger/scripts/process_news_image.py", "--from-article", self.mock_http.get_url("/article"), "--source", "Rûdaw", "--out", os.path.join(self.sandbox.root, "out.webp")])`.
   - **Expected Behavior**: True article hero is selected; inflated sidebar thumbnail is rejected.
   - **Assertions**: `self.assertExitCode(res, 0)`; `payload = self.assertJsonEnvelope(res.stdout)`; `self.assertIn("rudaw_true_hero", payload["source_candidate_url"])`.

2. **`test_f25_srcset_width_hint_cap`**:
   - **Inputs**: HTML snippet with `srcset="huge.jpg 3840w, normal.jpg 1200w"`.
   - **Execution**: Execute scoring function or run script against snippet.
   - **Expected Behavior**: The `width_hint` for the 3840w descriptor is capped at 1200.
   - **Assertions**: Assert calculated score for the candidate does not exceed `1200 + article_bonus`.

3. **`test_f25_aside_container_exclusion`**:
   - **Inputs**: HTML where `<main>` contains `<aside class="sidebar"><img src="ad.jpg"></aside>` and `<article><img src="story.jpg"></article>`.
   - **Execution**: Run candidate extraction.
   - **Expected Behavior**: Candidate inside `<aside>` receives `in_article: False` and receives zero article bonus.
   - **Assertions**: Candidate `ad.jpg` has lower score than `story.jpg`.

4. **`test_f25_social_banner_penalty`**:
   - **Inputs**: HTML with `meta property="og:image" content="https://site.org/images/social_branded_news.jpg"` and in-article photo `https://site.org/images/photo.jpg`.
   - **Execution**: Run image candidate ranking.
   - **Expected Behavior**: Social-branded candidate receives `-600` penalty; clean photo wins.
   - **Assertions**: Selected candidate is `photo.jpg`.

5. **`test_f25_og_image_stem_match_boost`**:
   - **Inputs**: HTML where `og:image` is `https://cdn.org/1287295/lead_cover.jpg` and in-article `<picture>` includes `lead_cover_1600.webp`.
   - **Execution**: Run ranking algorithm.
   - **Expected Behavior**: Matching stem receives ranking boost (+400).
   - **Assertions**: Assert candidate matching the `og:image` stem is chosen as lead hero.

---

### Feature 26: `process_news_image.py` `--mode master` (`image_mode_master`)
- **Module File**: `tests/e2e/tier1/test_f26_image_mode_master.py`
- **Test Class**: `TestF26ImageModeMaster(E2ETestCase)` (`@tier(1)`, `@feature(26)`)

1. **`test_f26_mode_master_preserves_aspect_ratio_4x3`**:
   - **Inputs**: 1600x1200 (4:3) input image (`sample_valid_master_1600x900.webp` resized to 1600x1200).
   - **Execution**: `self.run_python([".agents/skills/kevanger/scripts/process_news_image.py", "-i", input_img, "--mode", "master", "--source", "AA", "-o", out_webp])`.
   - **Expected Behavior**: Image is exported with width 1600 and height 1200 without being cropped to 16:9.
   - **Assertions**: `self.assertExitCode(res, 0)`; `with Image.open(out_webp) as im: self.assertEqual(im.size, (1600, 1200))`.

2. **`test_f26_mode_master_json_payload_attributes`**:
   - **Inputs**: Input image >= 1200px processed with `--mode master`.
   - **Execution**: `run_python` image processing.
   - **Expected Behavior**: JSON output contains `"mode": "master"`, `"mime": "image/webp"`, `"width": >= 1200`, and credit keys.
   - **Assertions**: `payload = self.assertJsonEnvelope(res.stdout)`; `self.assertEqual(payload["mode"], "master")`; `self.assertGreaterEqual(payload["width"], 1200)`.

3. **`test_f26_mode_master_strict_min_width_pass`**:
   - **Inputs**: 1920x1080 image with `--mode master --strict-min-width`.
   - **Execution**: `run_python` image processing.
   - **Expected Behavior**: Exits 0, `"upscaled": false`.
   - **Assertions**: `self.assertExitCode(res, 0)`; `self.assertFalse(payload["upscaled"])`.

4. **`test_f26_mode_master_exif_orientation_correction`**:
   - **Inputs**: Test image with EXIF orientation tag 6 (rotated 90 degrees).
   - **Execution**: Process with `--mode master`.
   - **Expected Behavior**: EXIF transposed so dimensions match visually upright orientation.
   - **Assertions**: Verify saved image dimensions match transposed orientation.

5. **`test_f26_mode_master_file_output_written`**:
   - **Inputs**: Valid 1600x900 WebP fixture with `-o <sandbox>/master.webp`.
   - **Execution**: Process with `--mode master`.
   - **Expected Behavior**: Target WebP file created on disk, valid WebP header, non-zero file size.
   - **Assertions**: `self.assertTrue(os.path.exists(out_webp))`; `self.assertGreater(os.path.getsize(out_webp), 0)`.

---

### Feature 27: `mod_news` `image_alt` & `image_credit` Columns (`mod_news_image_columns`)
- **Module File**: `tests/e2e/tier1/test_f27_mod_news_image_columns.py`
- **Test Class**: `TestF27ModNewsImageColumns(E2ETestCase)` (`@tier(1)`, `@feature(27)`)

1. **`test_f27_schema_columns_exist_in_sql`**:
   - **Inputs**: `mod/mod_news/mod_news.sql`.
   - **Execution**: Read and parse SQL schema.
   - **Expected Behavior**: Contains column definitions for `image_alt` and `image_credit` (`varchar(255)`).
   - **Assertions**: `self.assertIn("image_alt", sql_content)`; `self.assertIn("image_credit", sql_content)`.

2. **`test_f27_mcp_create_news_with_media_credits`**:
   - **Inputs**: MCP payload for `create_news` with `image_alt: "Park Açılışı"`, `image_credit: "Fotoğraf: Amida Haber"`, `status: "n"`.
   - **Execution**: `self.php.call_mcp_tool("create_news", payload)` or MCP bridge.
   - **Expected Behavior**: News article created successfully; returns record ID.
   - **Assertions**: `res = self.assertMcpSuccess(raw_resp)`; `self.assertIn("id", res)`.

3. **`test_f27_mcp_get_news_returns_media_credits`**:
   - **Inputs**: Query ID of news record created in Case 2.
   - **Execution**: `self.php.call_mcp_tool("get_news", {"id": record_id})`.
   - **Expected Behavior**: Returned record array contains `image_alt == "Park Açılışı"` and `image_credit == "Fotoğraf: Amida Haber"`.
   - **Assertions**: `item = res["item"]`; `self.assertEqual(item["image_alt"], "Park Açılışı")`; `self.assertEqual(item["image_credit"], "Fotoğraf: Amida Haber")`.

4. **`test_f27_mcp_update_news_modifies_media_credits`**:
   - **Inputs**: `update_news` payload modifying `image_credit: "Wêne: Rûdaw"`.
   - **Execution**: `self.php.call_mcp_tool("update_news", update_payload)`.
   - **Expected Behavior**: Update succeeds; subsequent `get_news` reflects updated string.
   - **Assertions**: `self.assertMcpSuccess(res)`; `self.assertEqual(updated_item["image_credit"], "Wêne: Rûdaw")`.

5. **`test_f27_media_credits_xss_sanitization`**:
   - **Inputs**: `create_news` with `image_alt: "<script>alert(1)</script>Park"`.
   - **Execution**: Call `create_news`.
   - **Expected Behavior**: Input is sanitized, HTML tags stripped/escaped.
   - **Assertions**: `self.assertNotIn("<script>", stored_item["image_alt"])`.

---

### Feature 28: `news.twig` Hero Figure & JSON-LD (`twig_hero_figure_jsonld`)
- **Module File**: `tests/e2e/tier1/test_f28_twig_hero_figure_jsonld.py`
- **Test Class**: `TestF28TwigHeroFigureJsonld(E2ETestCase)` (`@tier(1)`, `@feature(28)`)

1. **`test_f28_hero_figure_semantic_element`**:
   - **Inputs**: Render `news.twig` with news data having `image: "lead.webp"`.
   - **Execution**: Twig render via `self.php.eval_script(...)`.
   - **Expected Behavior**: Renders `<figure class="detail-hero">` and does not use `<div class="detail-hero">`.
   - **Assertions**: `self.assertHtmlTagPresent(html, "figure", attrs={"class": "detail-hero"})`; `self.assertHtmlTagAbsent(html, "div", attrs={"class": "detail-hero"})`.

2. **`test_f28_figcaption_rendered_when_credit_present`**:
   - **Inputs**: Render with `image_credit: "Fotoğraf: AA"`.
   - **Execution**: Twig render.
   - **Expected Behavior**: HTML contains `<figcaption class="news-hero-caption">Fotoğraf: AA</figcaption>`.
   - **Assertions**: `self.assertHtmlTagPresent(html, "figcaption", attrs={"class": "news-hero-caption"})`; `self.assertIn("Fotoğraf: AA", html)`.

3. **`test_f28_figcaption_omitted_when_credit_empty`**:
   - **Inputs**: Render with `image_credit: ""` (empty).
   - **Execution**: Twig render.
   - **Expected Behavior**: `<figcaption>` is not rendered in output.
   - **Assertions**: `self.assertHtmlTagAbsent(html, "figcaption")`.

4. **`test_f28_img_alt_prefers_image_alt_over_name`**:
   - **Inputs**: Render with `name: "Story Headline"`, `image_alt: "Ekolojik Kent Parkı Kuşbakışı"`.
   - **Execution**: Twig render.
   - **Expected Behavior**: Hero `<img>` tag has `alt="Ekolojik Kent Parkı Kuşbakışı"`.
   - **Assertions**: `self.assertHtmlTagPresent(html, "img", attrs={"class": "detail-hero-image", "alt": "Ekolojik Kent Parkı Kuşbakışı"})`.

5. **`test_f28_jsonld_image_object_credit_text`**:
   - **Inputs**: Render with `image_credit: "Wêne: Rûdaw"`, `image_alt: "Diyarbakır Parkı"`.
   - **Execution**: Extract `<script type="application/ld+json">` from rendered HTML and parse JSON.
   - **Expected Behavior**: `ImageObject` in `image` array contains `"creditText": "Wêne: Rûdaw"` (or `"caption"`).
   - **Assertions**: `schema = json.loads(json_str)`; `img_obj = schema["image"][0]`; `self.assertEqual(img_obj.get("creditText"), "Wêne: Rûdaw")`.

6. **`test_f28_code_separation_compliance`**:
   - **Inputs**: Rendered `news.twig` output.
   - **Execution**: Check against Rule 1 code separation.
   - **Expected Behavior**: Zero inline CSS and zero inline JS.
   - **Assertions**: `self.assertNoInlineStylesOrScripts(html)`.

---

### Feature 29: `tools/mcp_cli.py` Swarm CLI Wrapper (`mcp_cli_bridge`)
- **Module File**: `tests/e2e/tier1/test_f29_mcp_cli_bridge.py`
- **Test Class**: `TestF29McpCliBridge(E2ETestCase)` (`@tier(1)`, `@feature(29)`)

1. **`test_f29_mcp_cli_read_tool_call_success`**:
   - **Inputs**: Command: `python3 tools/mcp_cli.py call list_news_categories --args-json '{"lang": "tr"}'`.
   - **Execution**: `self.run_python(["tools/mcp_cli.py", "call", "list_news_categories", "--args-json", '{"lang": "tr"}'])`.
   - **Expected Behavior**: Exits 0; stdout is valid JSON envelope containing categories; stderr logs environment name.
   - **Assertions**: `self.assertExitCode(res, 0)`; `payload = self.assertJsonEnvelope(res.stdout)`; `self.assertIn("items", payload["result"])`.

2. **`test_f29_editorial_safe_blocks_delete_mutation`**:
   - **Inputs**: Command: `python3 tools/mcp_cli.py call delete_news --args-json '{"id": 999}'`.
   - **Execution**: `run_python` with default `--editorial-safe`.
   - **Expected Behavior**: Exits non-zero (1); operation aborted; stderr reports blocked delete tool.
   - **Assertions**: `self.assertExitCode(res, 1)`; `self.assertIn("blocked", res.stderr.lower())`.

3. **`test_f29_editorial_safe_blocks_published_status`**:
   - **Inputs**: Command: `python3 tools/mcp_cli.py call create_news --args-json '{"name": "X", "status": "y"}'`.
   - **Execution**: `run_python` with default `--editorial-safe`.
   - **Expected Behavior**: Exits non-zero (1); publication blocked; requires `status="n"`.
   - **Assertions**: `self.assertExitCode(res, 1)`; `self.assertIn("status", res.stderr.lower())`.

4. **`test_f29_editorial_safe_allows_draft_staging`**:
   - **Inputs**: Valid draft staging JSON with `status="n"` and `username="habermerkezi"`.
   - **Execution**: `run_python` tool call.
   - **Expected Behavior**: Passes `--editorial-safe` validation gate and invokes bridge.
   - **Assertions**: Does not fail on safety validation.

5. **`test_f29_get_record_subcommand`**:
   - **Inputs**: `python3 tools/mcp_cli.py get-record --module mod_news --id 1 --out <sandbox>/rec.json`.
   - **Execution**: `run_python` get-record command.
   - **Expected Behavior**: Exits 0; file `<sandbox>/rec.json` is written with record details.
   - **Assertions**: `self.assertExitCode(res, 0)`; `self.assertTrue(os.path.exists(out_file))`; `rec = json.load(open(out_file))`.

---

### Feature 30: `source_catalog.py` `--scan-today` (`source_catalog_scan_today`)
- **Module File**: `tests/e2e/tier1/test_f30_source_catalog_scan_today.py`
- **Test Class**: `TestF30SourceCatalogScanToday(E2ETestCase)` (`@tier(1)`, `@feature(30)`)

1. **`test_f30_scan_today_discovers_today_rss_items`**:
   - **Inputs**: Mock HTTP server hosting `tests/e2e/fixtures/feeds/rss_diyarbakir_today.xml`.
   - **Execution**: `self.run_python([".agents/skills/kevkolin/scripts/source_catalog.py", "--scan-today", "--category", "amed_diyarbakir", "--feed-override", self.mock_http.get_url("/rss.xml")])`.
   - **Expected Behavior**: Exits 0; output JSON contains 2 items published today with `title`, `url`, `pub_date`.
   - **Assertions**: `self.assertExitCode(res, 0)`; `items = self.assertJsonEnvelope(res.stdout)`; `self.assertEqual(len(items), 2)`.

2. **`test_f30_scan_today_filters_out_stale_feed_items`**:
   - **Inputs**: Mock feed serving `tests/e2e/fixtures/feeds/rss_diyarbakir_stale.xml` (dated 2026-11-01).
   - **Execution**: `run_python` with `--scan-today`.
   - **Expected Behavior**: Exits 0; stale articles are discarded; output list is empty `[]`.
   - **Assertions**: `self.assertExitCode(res, 0)`; `items = self.assertJsonEnvelope(res.stdout)`; `self.assertEqual(len(items), 0)`.

3. **`test_f30_scan_today_sitemap_xml_parsing`**:
   - **Inputs**: Mock feed serving `tests/e2e/fixtures/feeds/sitemap_news_today.xml`.
   - **Execution**: `run_python` with `--scan-today`.
   - **Expected Behavior**: Parses `<news:publication_date>` ISO dates and extracts article URLs.
   - **Assertions**: `items = self.assertJsonEnvelope(res.stdout)`; `self.assertGreaterEqual(len(items), 1)`.

4. **`test_f30_scan_today_category_filter`**:
   - **Inputs**: Command: `source_catalog.py --scan-today --category amed_diyarbakir`.
   - **Execution**: Run with category argument.
   - **Expected Behavior**: Only sources registered under `amed_diyarbakir` in `VERIFIED_SOURCES` are queried.
   - **Assertions**: Verify queried sources match category list.

5. **`test_f30_scan_today_resilience_to_feed_error`**:
   - **Inputs**: One feed endpoint returns HTTP 500 while a second returns valid XML.
   - **Execution**: Run scan across multiple feeds.
   - **Expected Behavior**: Script logs warning on stderr for the failed feed, does not crash, and outputs results from the healthy feed.
   - **Assertions**: `self.assertExitCode(res, 0)`; `self.assertIn("warning", res.stderr.lower())`.

---

### Feature 31: `audit_seo.py` Alignment (`audit_seo_alignment`)
- **Module File**: `tests/e2e/tier1/test_f31_audit_seo_alignment.py`
- **Test Class**: `TestF31AuditSeoAlignment(E2ETestCase)` (`@tier(1)`, `@feature(31)`)

1. **`test_f31_seo_audit_clean_editorial_draft_passes`**:
   - **Inputs**: Valid draft (`tests/e2e/fixtures/editorial/draft_today_diyarbakir_park.html`), headline (50 chars), summary (106 chars).
   - **Execution**: `self.run_python([".agents/skills/kevseo/scripts/audit_seo.py", "--file", draft_path, "--headline", "Diyarbakır'da Ekolojik Kent Parkının İlk Etabı Açıldı", "--summary", "Diyarbakır merkezinde 45 dönümlük alanda inşa edilen ekolojik park bugün halkın hizmetine törenle açıldı.", "--json"])`.
   - **Expected Behavior**: Exits 0, status `"PASSED"`.
   - **Assertions**: `self.assertExitCode(res, 0)`; `payload = self.assertJsonEnvelope(res.stdout)`; `self.assertEqual(payload["status"], "PASSED")`.

2. **`test_f31_seo_audit_fails_short_summary`**:
   - **Inputs**: Same draft, but summary is 80 characters (< 90).
   - **Execution**: `run_python` audit script.
   - **Expected Behavior**: Exits 1; flags summary length below 90.
   - **Assertions**: `self.assertExitCode(res, 1)`; `payload = self.assertJsonEnvelope(res.stdout)`; `self.assertEqual(payload["meta_analysis"]["deck_status"], "FAILED")`.

3. **`test_f31_seo_audit_fails_long_headline`**:
   - **Inputs**: Same draft, but headline is 75 characters (> 70).
   - **Execution**: `run_python` audit script.
   - **Expected Behavior**: Exits 1; flags headline length > 70.
   - **Assertions**: `self.assertExitCode(res, 1)`; `self.assertEqual(payload["meta_analysis"]["headline_status"], "FAILED")`.

4. **`test_f31_seo_audit_fails_insufficient_h2_headings`**:
   - **Inputs**: HTML content with only 1 `<h2>` subheading.
   - **Execution**: `run_python` audit script.
   - **Expected Behavior**: Exits 1; reports missing `<h2>` thematic subheadings.
   - **Assertions**: `self.assertExitCode(res, 1)`; `issues = [i["type"] for i in payload["architecture_issues"]]`; `self.assertIn("missing_h2", issues)`.

5. **`test_f31_seo_audit_fails_inline_styles`**:
   - **Inputs**: HTML containing `<p style="font-weight: bold;">Text</p>`.
   - **Execution**: `run_python` audit script.
   - **Expected Behavior**: Exits 1; flags `inline_style_violation` as CRITICAL.
   - **Assertions**: `self.assertExitCode(res, 1)`; `self.assertIn("inline_style_violation", [i["type"] for i in payload["architecture_issues"]])`.

---

### Feature 32: Skill Documentation Synchronization (`skill_docs_sync`)
- **Module File**: `tests/e2e/tier1/test_f32_skill_docs_sync.py`
- **Test Class**: `TestF32SkillDocsSync(E2ETestCase)` (`@tier(1)`, `@feature(32)`)

1. **`test_f32_skills_yaml_frontmatter_validity`**:
   - **Inputs**: The 5 skill files: `.agents/skills/{kevanger,kevtemen,kevkolin,kevrast,kevseo}/SKILL.md`.
   - **Execution**: Read and parse YAML frontmatter between opening and closing `---`.
   - **Expected Behavior**: Each file contains valid YAML frontmatter with `name` (matching directory) and non-empty `description`.
   - **Assertions**: Assert `name` matches folder name; assert `len(desc) > 20`.

2. **`test_f32_kevanger_skill_documents_mcp_cli_and_master_image`**:
   - **Inputs**: `.agents/skills/kevanger/SKILL.md`.
   - **Execution**: Search for CLI bridge and master image documentation.
   - **Expected Behavior**: Mentions `tools/mcp_cli.py`, `--editorial-safe`, and `--mode master`.
   - **Assertions**: `self.assertIn("mcp_cli.py", content)`; `self.assertIn("--mode master", content)`.

3. **`test_f32_kevtemen_skill_documents_fail_closed_gate`**:
   - **Inputs**: `.agents/skills/kevtemen/SKILL.md`.
   - **Execution**: Search for gate protocol documentation.
   - **Expected Behavior**: Documents fail-closed behavior and Europe/Istanbul calendar boundary.
   - **Assertions**: `self.assertIn("fail-closed", content.lower())`; `self.assertIn("Europe/Istanbul", content)`.

4. **`test_f32_kevrast_skill_documents_two_pass_and_all_paragraphs`**:
   - **Inputs**: `.agents/skills/kevrast/SKILL.md`.
   - **Execution**: Search for Stage 1 / Stage 2 and paragraph audit documentation.
   - **Expected Behavior**: Documents two-stage fact checking and multi-paragraph checking.
   - **Assertions**: `self.assertIn("Stage 1", content)`; `self.assertIn("Stage 2", content)`.

5. **`test_f32_kevkolin_skill_documents_scan_today_radar`**:
   - **Inputs**: `.agents/skills/kevkolin/SKILL.md`.
   - **Execution**: Search for radar documentation.
   - **Expected Behavior**: Documents `--scan-today` local news discovery radar.
   - **Assertions**: `self.assertIn("--scan-today", content)`.

---

### Feature 33: Correct AGENTS.md Rule Number Citations (`agents_rule_citations`)
- **Module File**: `tests/e2e/tier1/test_f33_agents_rule_citations.py`
- **Test Class**: `TestF33AgentsRuleCitations(E2ETestCase)` (`@tier(1)`, `@feature(33)`)

1. **`test_f33_kevseo_skill_cites_rule_1_for_code_separation`**:
   - **Inputs**: `.agents/skills/kevseo/SKILL.md`.
   - **Execution**: Inspect lines around Code Separation Mandate.
   - **Expected Behavior**: Cites `AGENTS.md Rule 1` (never Rule 2).
   - **Assertions**: `self.assertIn("AGENTS.md Rule 1", content)`; `self.assertNotIn("AGENTS.md Rule 2", content)`.

2. **`test_f33_audit_seo_script_cites_rule_1_for_code_separation`**:
   - **Inputs**: `.agents/skills/kevseo/scripts/audit_seo.py`.
   - **Execution**: Inspect comments around inline style/script checks.
   - **Expected Behavior**: Cites `AGENTS.md Rule 1`.
   - **Assertions**: `self.assertIn("AGENTS.md Rule 1", content)`; `self.assertNotIn("AGENTS.md Rule 2", content)`.

3. **`test_f33_code_separation_guard_cites_rule_1`**:
   - **Inputs**: `tools/code_separation_guard.py`.
   - **Execution**: Inspect docstring.
   - **Expected Behavior**: Cites `AGENTS.md Rule 1`.
   - **Assertions**: `self.assertIn("AGENTS.md Rule 1", content)`.

4. **`test_f33_news_validator_cites_rule_9_for_wikipedia_slug`**:
   - **Inputs**: `mod/mod_news/validator/add.validator.php`.
   - **Execution**: Inspect comments around `legacy_seo`.
   - **Expected Behavior**: Cites `AGENTS.md Rule 9` (never Rule 10).
   - **Assertions**: `self.assertNotIn("AGENTS.md Rule 10", content)`.

5. **`test_f33_code_quality_strategy_cites_rule_6_for_english`**:
   - **Inputs**: `docs/CODE_QUALITY_AGENT_STRATEGY.md`.
   - **Execution**: Inspect rules table.
   - **Expected Behavior**: Cites `AGENTS.md Rule 6` for English identifiers and comments (never Rule 7).
   - **Assertions**: `self.assertNotIn("AGENTS.md Rule 7", content)`.

---

### Feature 34: Full Regression Suite & MCP Smoke Tests (`regression_suite_smoke`)
- **Module File**: `tests/e2e/tier1/test_f34_regression_suite_smoke.py`
- **Test Class**: `TestF34RegressionSuiteSmoke(E2ETestCase)` (`@tier(1)`, `@feature(34)`)

1. **`test_f34_php_syntax_lint_all_mcp_and_task_files`**:
   - **Inputs**: Core PHP files: `lib/McpServer.class.php`, `lib/McpCrudToolsBase.class.php`, `lib/McpNewsTools.class.php`, `lib/McpFaqTools.class.php`, `tasks/mcp_smoke.php`.
   - **Execution**: `self.php.lint_file(path)`.
   - **Expected Behavior**: All core PHP files pass `php -l` with exit code 0.
   - **Assertions**: `self.assertExitCode(res, 0)` for each file.

2. **`test_f34_verify_news_pipeline_happy_path`**:
   - **Inputs**: Valid draft, valid dossier, headline, summary.
   - **Execution**: `self.run_cli(["bash", "tools/verify_news_pipeline.sh", "--no-network", "--draft", draft_path, "--dossier", dossier_path, "--headline", "Diyarbakır'da Ekolojik Kent Parkının İlk Etabı Açıldı", "--summary", "Diyarbakır merkezinde 45 dönümlük alanda inşa edilen ekolojik park bugün halkın hizmetine törenle açıldı."])`.
   - **Expected Behavior**: All 3 stages (kevtemen, kevseo, kevrast) pass; exits 0.
   - **Assertions**: `self.assertExitCode(res, 0)`; `self.assertIn("ALL PIPELINE VERIFICATION STAGES PASSED", res.stdout)`.

3. **`test_f34_verify_news_pipeline_rejects_missing_args`**:
   - **Inputs**: Command without required arguments.
   - **Execution**: `self.run_cli(["bash", "tools/verify_news_pipeline.sh"])`.
   - **Expected Behavior**: Exits with code 2 and usage message.
   - **Assertions**: `self.assertExitCode(res, 2)`; `self.assertIn("Usage:", res.stdout)`.

4. **`test_f34_module_boundary_guard_zero_violations`**:
   - **Inputs**: Full repository scan.
   - **Execution**: `self.run_python(["tools/module_boundary_guard.py", "--all"])`.
   - **Expected Behavior**: Exits 0, 0 violations detected.
   - **Assertions**: `self.assertExitCode(res, 0)`; `self.assertIn("Violations Found: 0", res.stdout)`.

5. **`test_f34_code_separation_on_core_templates`**:
   - **Inputs**: `templates/amed/index/mod_news/news.twig`.
   - **Execution**: `self.run_python(["tools/code_separation_guard.py", "--path", "templates/amed/index/mod_news/news.twig"])`.
   - **Expected Behavior**: Exits 0, 0 violations found.
   - **Assertions**: `self.assertExitCode(res, 0)`; `self.assertIn("0 violations found", res.stdout)`.

---

### Feature 35: Version Documentation Update (`version_doc_update`)
- **Module File**: `tests/e2e/tier1/test_f35_version_doc_update.py`
- **Test Class**: `TestF35VersionDocUpdate(E2ETestCase)` (`@tier(1)`, `@feature(35)`)

1. **`test_f35_version_md_has_v1_0_63_header`**:
   - **Inputs**: `docs/VERSION.md`.
   - **Execution**: Read file content.
   - **Expected Behavior**: Contains release header `## v1.0.63 (YYYY-MM-DD)` with change notes.
   - **Assertions**: `self.assertTrue(re.search(r'^##\s*v1\.0\.63\b', content, re.MULTILINE))`.

2. **`test_f35_site_config_resolves_version_1_0_63`**:
   - **Inputs**: `config/site.config.php`.
   - **Execution**: `self.php.eval_script("require 'config/site.config.php'; echo json_encode(['version' => $site['version']]);")`.
   - **Expected Behavior**: Resolved `$site['version']` equals `'1.0.63'`.
   - **Assertions**: `data = json.loads(res.stdout)`; `self.assertEqual(data["version"], "1.0.63")`.

3. **`test_f35_site_config_example_version_parity`**:
   - **Inputs**: `config/site.config.example.php`.
   - **Execution**: Inspect version setting line.
   - **Expected Behavior**: Matches `'1.0.63'`.
   - **Assertions**: `self.assertIn("$site['version'] = '1.0.63'", content)`.

4. **`test_f35_server_md_version_parity`**:
   - **Inputs**: `docs/SERVER.md`.
   - **Execution**: Inspect header line 3.
   - **Expected Behavior**: Version is `v1.0.63`.
   - **Assertions**: `self.assertIn("v1.0.63", content)`.

5. **`test_f35_version_parity_zero_drift`**:
   - **Inputs**: All 4 version-bearing files.
   - **Execution**: Compare extracted versions across files.
   - **Expected Behavior**: All files agree on exact same semantic version `1.0.63`.
   - **Assertions**: `self.assertEqual(len(set(extracted_versions)), 1)`.

---

### Feature 36: Git Release Tagging & Push (`git_release_tag_push`)
- **Module File**: `tests/e2e/tier1/test_f36_git_release_tag_push.py`
- **Test Class**: `TestF36GitReleaseTagPush(E2ETestCase)` (`@tier(1)`, `@feature(36)`)

1. **`test_f36_git_clean_working_tree`**:
   - **Inputs**: Git repository status.
   - **Execution**: `self.run_cli(["git", "status", "--porcelain"])`.
   - **Expected Behavior**: Tracked source directories (`lib/`, `mod/`, `templates/`, `tools/`, `tasks/`) have zero uncommitted modifications.
   - **Assertions**: Assert no tracked file modifications in git status output.

2. **`test_f36_git_release_tag_format`**:
   - **Inputs**: `git tag -l "v1.0.63"`.
   - **Execution**: `self.run_cli(["git", "tag", "-l", "v1.0.63"])`.
   - **Expected Behavior**: Tag `v1.0.63` matches semver tag regex `^v\d+\.\d+\.\d+$`.
   - **Assertions**: `self.assertEqual(res.stdout.strip(), "v1.0.63")`.

3. **`test_f36_git_tag_annotated`**:
   - **Inputs**: `git cat-file -t v1.0.63`.
   - **Execution**: Inspect Git tag object type.
   - **Expected Behavior**: Tag is an annotated tag (`tag`), not a lightweight commit ref.
   - **Assertions**: `self.assertEqual(res.stdout.strip(), "tag")`.

4. **`test_f36_git_tag_points_to_head_or_valid_commit`**:
   - **Inputs**: `git rev-parse v1.0.63^{commit}` and `git rev-parse HEAD`.
   - **Execution**: Compare SHA-1 hashes or check commit reachability.
   - **Expected Behavior**: Tag points to a valid release commit.
   - **Assertions**: `self.assertEqual(len(res.stdout.strip()), 40)`.

5. **`test_f36_git_remote_origin_configured`**:
   - **Inputs**: `git remote -v`.
   - **Execution**: `self.run_cli(["git", "remote", "-v"])`.
   - **Expected Behavior**: Remote `origin` exists and points to repository URL.
   - **Assertions**: `self.assertIn("origin", res.stdout)`.

---

## 4. Proposed File Layout under `tests/e2e/tier1/`

To maintain 1:1 parity with the Feature Inventory and test runner discovery, the 13 test modules are organized as follows:

```
tests/e2e/tier1/
├── __init__.py
├── test_f00_infra_smoke.py                  # Infrastructure & Fixture smoke tests (M1)
├── ...                                      # Features 1 to 23 test modules
├── test_f24_image_nextjs_unwrap.py          # Feature 24: Next.js CDN proxy unwrap (≥5 tests)
├── test_f25_image_hero_scoring.py           # Feature 25: Hero scoring & srcset cap (≥5 tests)
├── test_f26_image_mode_master.py            # Feature 26: process_news_image.py --mode master (≥5 tests)
├── test_f27_mod_news_image_columns.py       # Feature 27: mod_news image_alt & image_credit (≥5 tests)
├── test_f28_twig_hero_figure_jsonld.py      # Feature 28: news.twig <figure> & JSON-LD credits (≥5 tests)
├── test_f29_mcp_cli_bridge.py               # Feature 29: tools/mcp_cli.py Swarm wrapper (≥5 tests)
├── test_f30_source_catalog_scan_today.py    # Feature 30: source_catalog.py --scan-today radar (≥5 tests)
├── test_f31_audit_seo_alignment.py          # Feature 31: audit_seo.py alignment (≥5 tests)
├── test_f32_skill_docs_sync.py              # Feature 32: Skill documentation sync (≥5 tests)
├── test_f33_agents_rule_citations.py        # Feature 33: Correct AGENTS.md rule citations (≥5 tests)
├── test_f34_regression_suite_smoke.py       # Feature 34: Regression smoke suite & guards (≥5 tests)
├── test_f35_version_doc_update.py           # Feature 35: Version documentation update (≥5 tests)
└── test_f36_git_release_tag_push.py         # Feature 36: Git release tagging & push (≥5 tests)
```

**Total New Test Files:** 13 files  
**Total Test Cases in Scope:** 66 test cases (minimum 5 per feature, 6 for F28).

---

## 5. Caveats and Recommendations for Test Authoring

1. **Read-Only Explorer Constraint**:
   Explorer 3 conducted a strictly read-only investigation. No source files, template files, or test files were modified or created.
2. **Offline Isolation & Mock HTTP**:
   For F24, F25, and F30, tests MUST use `self.mock_http` rather than making outbound network connections. Live network calls in automated suites cause flakiness, rate-limiting (HTTP 429), or SSRF false-positives.
3. **Temporary Filesystem Cleanliness**:
   All test-generated WebP images, temporary dossiers, and record JSON files MUST be directed into `self.sandbox.root` (e.g. `os.path.join(self.sandbox.root, "out.webp")`). `E2ETestCase.tearDown()` guarantees automatic directory purging, preventing repo contamination.
4. **Git Operations in F36**:
   Tests for Feature 36 that inspect Git tags should be tolerant if the release tag `v1.0.63` has not yet been pushed during early milestone development (e.g. asserting tag creation contract or validating pre-tag working tree cleanliness).
5. **AGENTS.md Citations Verification**:
   When testing F33, verify that the scan covers `.agents/skills/`, `docs/`, `mod/`, and `tools/`, but excludes historical release notes in `docs/VERSION.md` (e.g., historical references to earlier versions shouldn't fail the guard).

---

## 6. Conclusion

The test architecture (`tests/e2e/harness/`), execution engine (`runner.py`), and assertion library (`assertions.py`) provide complete, production-grade infrastructure for implementing Tier 1 coverage for Features 24 to 36. 
A comprehensive catalog of **66 concrete test cases** (at least 5 per feature) has been designed and mapped to 13 distinct test files under `tests/e2e/tier1/`. Every test case has fully specified inputs, execution mechanisms, expected outcomes, and assertion criteria adhering to the opaque-box testing standard.

---

## 7. Verification Method

To verify the test suite design and prerequisites:

1. **Verify Test Infrastructure Readiness**:
   ```bash
   python3 tests/e2e/run_e2e_tests.py --tier 1
   ```
   *Expected Result:* All existing smoke tests pass cleanly.
2. **Verify Code Quality Guards**:
   ```bash
   python3 tools/module_boundary_guard.py --all
   python3 tools/code_separation_guard.py --path templates/amed/index/mod_news/news.twig
   ```
   *Expected Result:* 0 violations detected.
3. **Verify Target Script Syntax**:
   ```bash
   python3 -m py_compile .agents/skills/kevanger/scripts/process_news_image.py \
       .agents/skills/kevkolin/scripts/source_catalog.py \
       .agents/skills/kevseo/scripts/audit_seo.py
   php -l tasks/mcp_smoke.php
   ```
   *Expected Result:* Clean compilation with exit code 0.
