# Handoff Report: Tier 1 Feature Coverage Suite Architecture & Test Blueprints (Features 13–23)

**Author:** `explorer_e2e_m2_2`  
**Milestone:** `E2E-M2: Tier 1 Feature Coverage Suite`  
**Working Directory:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**State Folder:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_e2e_m2_2/`  
**Target Specification:** `PROJECT.md`, `SCOPE.md`, `TEST_INFRA.md`, `SPEC_CATALOG` (`teamwork_spec_miner_e2e_m1_1/handoff.md`)  
**Scope:** Features 13 through 23 (Shared HTTP Client, Editorial Freshness Sentinel, and Fact-Checking Engine)

---

## 1. Observation

Direct observations and evidence collected across test harness infrastructure, test fixtures, and target script implementations:

### 1.1 Test Harness & Isolation Infrastructure
1. **`tests/e2e/harness/mock_http.py:129-230`**:
   - `MockHttpServer` runs as an embedded `ThreadingHTTPServer` on `127.0.0.1` with dynamic port assignment (`port=0`).
   - Supports static route registration via `add_route(path, content, status=200, headers=None, prefix=False)`.
   - Supports dynamic callbacks via `add_callback(path, callback, prefix=False)`, enabling stateful behaviors such as simulating transient HTTP `429 Too Many Requests` followed by `200 OK` on retry.
   - Request history is thread-safely recorded in `request_history` and queryable via `get_requests(path_contains=...)`, permitting precise assertion of network request counts (e.g. verifying URL deduplication and cache hits).
2. **`tests/e2e/harness/sandbox.py:15-139`**:
   - `Sandbox` creates isolated temporary directories with prefix `af_e2e_sb_` under `/tmp/`.
   - Traversal protection in `Sandbox.path()` strictly enforces containment (`os.path.commonpath([target, root]) == root`), preventing path traversal attacks.
   - Provides utilities `write_file(filename, content)`, `read_file(filename)`, `copy_file(src, dst)`, and recursive cleanup via `shutil.rmtree` with automatic permission elevation (`stat.S_IRWXU`).
3. **`tests/e2e/harness/assertions.py:24-392`**:
   - Custom assertions are domain-specific:
     - `assert_exit_code(result, expected_code=0)` evaluates `ProcessResult` or integer exit codes with stdout/stderr traces on failure.
     - `assert_json_envelope(data, required_keys=[...])` validates JSON formatting and required keys.
     - `assert_date_is_today(date_val, tz_name="Europe/Istanbul")` parses timezone-aware ISO strings, fractional seconds (`.000Z`, `,500+03:00`), and compares against current calendar date in `Europe/Istanbul`.
     - `assert_normalized_number(text, number)` enforces word boundary regex `(?<!\d)number(?!\d)` preventing substring false-positives (e.g. matching `45` inside `1450`).
     - `assert_wikipedia_slug(slug)` validates Wikipedia-style slug parity (no `\u0307`, underscores instead of spaces, no trailing slashes).
     - `assert_no_inline_styles_or_scripts(content)` validates strict code separation (AGENTS.md Rule 1).
4. **`tests/e2e/harness/base.py:52-199`**:
   - `E2ETestCase` integrates `Sandbox` (auto-created in `setUp` and wiped in `tearDown`), lazily instantiated `MockHttpServer`, process execution wrappers (`self.run_python()`, `self.run_cli()`, `self.run_php()`), fixture paths (`self.fixture_path()`), and assertion methods.
   - Test methods and classes are categorized using `@tier(1)` and `@feature(*feature_ids)`.

### 1.2 Fixture Catalog Availability (`tests/e2e/fixtures/editorial/`)
The existing fixture catalog contains:
- `dossier_today_rudaw_park.json`: Complete research dossier for today's news event (`date: "2026-11-11"`, primary source URL `https://www.rudaw.net/turkish/kurdistan/1287295`, metrics `45` and `120`).
- `dossier_yesterday_rudaw_park.json`: Stale dossier dated yesterday for negative testing.
- `dossier_cross_lang_english_docs.json`: Cross-language dossier declaring `source_lang: "en"` and `source_anchors: {"tr": "test çerçevesi", "en": "test framework"}`.
- `draft_today_diyarbakir_park.html`: Full article draft with semantic `<h2>`, `<p>`, and `<a href="...">` links.
- `draft_with_all_paragraphs_valid.html`: Multi-paragraph draft citing the same source URL where all paragraphs contain verified numbers (`45 dönüm` in Para 1, `120 ağaç` in Para 2).
- `draft_with_p4_hallucinated_num.html`: Multi-paragraph draft where Paragraphs 1-3 are valid, but Paragraph 4 introduces a fabricated metric (`550 bin`) citing the same URL.
- `draft_with_turkish_morphology.html`: Draft with Turkish inflected institutional entity (*"Diyarbakır Büyükşehir Belediyesine"*).
- `draft_with_unlinked_attribution.html`: Draft containing attribution phrase (*"paylaşılan bilgilere göre"*) lacking a hyperlink in the paragraph.
- `mock_rudaw_article_1287295.html`: Source HTML fixture containing publication date `2026-11-11T09:15:00.000Z`, `<article>` container, and metrics `45` and `120`.
- `mock_vendor_single_segment_download.html`: Single-segment download page with semantic version `v1.0.63`.
- `mock_antigravity_docs_swarm.html`: English source page containing `"test framework"` for cross-language anchor testing.
- `mock_nextjs_sidebar_article.html`: Article containing `<aside>` sidebar with misleading dates and thumbnails.
- `mock_news_section_front.html`: Generic rubric section front (`/news/`) for precision rejection tests.

### 1.3 Implementation Analysis for Features 13–23
1. **Feature 13: Shared HTTP Client (`.agents/skills/_shared/editorial_http.py`)**:
   - `validate_public_url(url: str) -> Tuple[bool, str]`: Validates URL scheme (`http`/`https`), parses host and port, resolves DNS via `socket.getaddrinfo()`, and checks all resolved IPs against comprehensive blacklists: RFC 1918 private IPv4 (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`), loopback (`127.0.0.0/8`), link-local / cloud metadata (`169.254.0.0/16`), CGNAT (`100.64.0.0/10`), documentation blocks, IPv6 loopback (`::1`), ULA (`fc00::/7`), and IPv4-mapped IPv6.
   - `SSRFSafeRedirectHandler`: Subclasses `urllib.request.HTTPRedirectHandler` and re-validates each redirect target against `validate_public_url` with a ceiling of 5 hops.
   - `_execute_transport()`: Enforces `max_bytes` check (returns `413` if exceeded). Parses `Retry-After` header (integer seconds or RFC 7231 HTTP-date) on transient status codes `(429, 502, 503, 504)` with bounded sleep up to `MAX_RETRY_SLEEP_SECONDS = 15.0`.
   - `get_cached_response()` & `set_cached_response()`: Two-tier cache with in-memory dict (`_MEMORY_CACHE`) and atomic disk JSON files (`_get_cache_path()`), defaulting to `DEFAULT_CACHE_TTL = 600` (10 minutes).
2. **Feature 14: `check_freshness.py` TLS & SSRF integration**:
   - Lines 29-37 & 361-375: Imports `editorial_http` and invokes `editorial_http.fetch_html()`.
   - On SSRF violation or network error, `fetch_html` returns an error status/raises `HTTPError`/`URLError`, recorded as `state="unreachable"`, `same_day=False`, and script exits with code `1` (fail-closed).
3. **Feature 15: `check_freshness.py` ISO fractional seconds**:
   - Lines 75-78 & 142-148: `RE_ISO` supports fractional seconds `(?:[.,]\d{1,9})?` while capturing timezone offset `(Z|[+-]\d{2}:?\d{2})?`.
   - `_apply_tz()` accurately converts UTC or offset-aware timestamps to `Europe/Istanbul` (+3 hours).
4. **Feature 16: `check_freshness.py` non-content tag stripping**:
   - Lines 258-266: Before date scanning, strips `<aside>`, `<nav>`, `<script>`, `<style>`, `<noscript>`, `<header>`, `<footer>` tags and their bodies. If an `<article>` tag is present, only contents within `<article>` are evaluated.
5. **Feature 17: `check_freshness.py` earliest main date & deduplication**:
   - Lines 215-256: Evaluates meta tags and JSON-LD `datePublished` (restricted to `ALLOWED_JSONLD_TYPES = {"NewsArticle", "Article", "ReportageNewsArticle", "BlogPosting", "WebPage"}`). If multiple candidates exist, `min(candidates, key=lambda c: c[0].timestamp())` selects the EARLIEST publication timestamp, explicitly rejecting modified dates or later timestamps.
   - Lines 558-578 & 604-645: `url_items = OrderedDict()` deduplicates draft and CLI URLs by normalized URL (`href.strip().rstrip("/")`). Tracks `citation_count` and fetches each unique URL exactly once.
6. **Feature 18: `check_freshness.py` near-midnight diagnostics**:
   - Lines 408-423: Computes `age_hours = round((now - pub_dt).total_seconds() / 3600.0, 2)` and `is_yesterday_late_evening = (pub_date == yesterday) and (18 <= pub_dt.hour <= 23)`.
   - Lines 669-674: Emits diagnostic string `[age: ...h, yesterday late-evening near-midnight]` in console output and structured attributes in JSON report.
7. **Feature 19: `verify_facts_and_links.py` all-paragraph verification**:
   - Lines 527-540 & 648-660: `_find_all_citing_blocks(url, text)` extracts every `<p>`, `<blockquote>`, or `<li>` citing the target URL. `audit_draft()` iterates through all citing blocks and executes `audit_url()` for each block with `entry["paragraph_index"] = b_idx`.
   - If any paragraph fails anchoring, `passed = False` and script exits with code `1`.
8. **Feature 20: `verify_facts_and_links.py` clean number & entity extraction**:
   - Lines 185-195: `strip_non_content_tags(html)` strips `<script>`, `<style>`, `<noscript>`, `<svg>`, `<nav>`, `<footer>`, `<aside>` bodies before numeric extraction, while preserving article `<meta>` descriptions.
   - Lines 361-401 & 424-434: `extract_distinctive_numbers()` extracts normalized percentages (`%80`, `yüzde 80`, `80 percent` -> `80%`), scaled numbers (`80 bin` -> `80000`, `1.5 milyon` -> `1500000`), and multi-digit integers. Exact normalized set comparison (`target_nums_set = set(...)`; `missing = [n for n in nums if n not in target_nums_set]`) replaces substring matching.
9. **Feature 21: `verify_facts_and_links.py` vendor single-segment allowlist**:
   - Lines 76-80 & 344-358: `SINGLE_SEGMENT_PRODUCT_SLUGS = {"download", "downloads", "releases", "changelog", "security", "pricing", "status", "docs", "manifesto", "about"}`.
   - A single-segment path is permitted only if `slug in SINGLE_SEGMENT_PRODUCT_SLUGS` and `role in ("supplementary", "product")`.
   - Lines 602-617: Post-fetch validation enforces that `<article>` tag count <= 2 and page contains a semantic version string (`SEMANTIC_VERSION_RE = r"\bv?\d+\.\d+(?:\.\d+)?\b"`) or declared source anchor.
10. **Feature 22: `verify_facts_and_links.py` cross-language anchoring**:
    - Lines 436-474: Evaluates declared `source_anchors` list containing `{"source_term": "...", "tr_term": "..."}` pairs. Enforces two-sided matching: `source_term` must appear on the foreign source page, and `tr_term` must appear in the citing Turkish draft paragraph.
    - If `source_lang != "tr"` and no declared anchors match, flags `R3 cross-language anchoring error` and marks status `block`.
11. **Feature 23: `verify_facts_and_links.py` Turkish morphology & attribution**:
    - Lines 105-131: `tr_fold()` handles dotted/dotless I (`İ` -> `i`, `I` -> `ı`, strip `\u0307`). `stem_institutional()` normalizes Turkish institutional suffixes (`Belediyesine` -> `belediyesi`, `Bakanlığına` -> `bakanlığı`, `Valiliğinden` -> `valiliği`, `Üniversitesinin` -> `üniversitesi`).
    - Lines 83-102 & 668-694: `OUTLET_ATTRIBUTION_RE` includes expanded phrases (*"paylaşılan bilgilere göre"*, *"analize göre"*, *"aktardığına göre"*). An attribution phrase in a paragraph without a hyperlink triggers `R4_UNLINKED_ATTRIBUTION` error. Speech attribution (`SPEECH_ATTRIBUTION_RE`) permits a link in the immediate preceding paragraph.

---

## 2. Logic Chain

1. From **Observation 1.1**, the test harness (`MockHttpServer`, `Sandbox`, `E2ETestCase`, `assertions.py`) provides full deterministic control over network responses, file creation, subprocess isolation, and domain-specific verification without external internet dependencies.
2. From **Observation 1.2**, high-fidelity HTML and JSON fixtures already exist in `tests/e2e/fixtures/editorial/` and can be supplemented with dynamic fixtures generated in temporary test sandboxes.
3. From **Observation 1.3 (F13)**, `editorial_http.py` exposes well-defined functional interfaces (`validate_public_url`, `fetch_html`, `fetch_binary`, cache controls) and network behaviors (TLS, SSRF IP blocking, `Retry-After`, byte limits). Testing requires 6 happy-path and boundary test cases targeting URL validation, caching, backoff, and size caps.
4. From **Observation 1.3 (F14–F18)**, `check_freshness.py` is invoked via CLI (`run_python()`) and directly imports `editorial_http`. Its verification requires 25 distinct tests across 5 features:
   - F14: verified transport, SSRF private IP blocking, fail-closed exit codes.
   - F15: ISO-8601 parsing with milliseconds, microseconds, comma separators, and timezone preservation.
   - F16: stripping `<aside>`, `<footer>`, `<script>`, and `<nav>` to avoid false-positive date matches.
   - F17: earliest publication date selection over modification dates, and URL deduplication across multi-citation drafts.
   - F18: structured `age_hours` computation, `is_yesterday_late_evening` flag, and diagnostic stdout messages.
5. From **Observation 1.3 (F19–F23)**, `verify_facts_and_links.py` provides both Python API methods (`KevrastAuditor`) and CLI commands (`--file`, `--dossier`, `--explain`, `--json`). Its verification requires 25 distinct tests across 5 features:
   - F19: auditing every citing paragraph, catching hallucinations in later paragraphs (e.g. Paragraph 4), and reporting exact paragraph indexes.
   - F20: non-content stripping of `<script>` chunk IDs, exact normalized numeric matching (preventing `45` in `1450`), and word-based percentage conversions.
   - F21: allowing defined single-segment vendor download/release pages under role `product`/`supplementary` with semantic versioning while rejecting generic section fronts.
   - F22: two-sided bilingual cross-language anchoring (`source_term` <-> `tr_term`) and schema validation.
   - F23: Turkish `tr_fold()` Unicode normalization, institutional case suffix stemming, and mandatory hyperlinking for expanded attribution phrases.
6. Therefore, designing 11 distinct test modules under `tests/e2e/tier1/` (one per feature) with >=5 test cases each (total 56 test cases) fully satisfies the E2E-M2 requirement.

---

## 3. Concrete Test Blueprints (Features 13 to 23)

A total of **56 test cases** are designed below (at least 5 per feature, with 6 for Feature 13).

### Feature 13: Shared HTTP Client `editorial_http.py`
**Target Module:** `tests/e2e/tier1/test_f13_editorial_http.py`  
**Test Class:** `TestF13EditorialHttp(E2ETestCase)`  
**Decorators:** `@tier(1)`, `@feature(13)`

1. `test_f13_fetch_html_happy_path`:
   - **Inputs:** URL served by `MockHttpServer` with route `/news/article-1`, content `<html><body><h1>Test Article</h1></body></html>`, status `200`, `Content-Type: text/html`.
   - **Mechanism:** Direct Python call `editorial_http.fetch_html(url, use_cache=False)`.
   - **Assertions:** `status_code == 200`, `html_body == "<html><body><h1>Test Article</h1></body></html>"`, `headers["x-final-url"] == url`, `headers["content-type"]` contains `text/html`.
2. `test_f13_fetch_binary_happy_path`:
   - **Inputs:** URL served by `MockHttpServer` with route `/images/hero.webp`, binary payload `b"\x52\x49\x46\x46\x00\x00\x00\x00\x57\x45\x42\x50"`, status `200`, `Content-Type: image/webp`.
   - **Mechanism:** Direct Python call `editorial_http.fetch_binary(url, use_cache=False)`.
   - **Assertions:** `status_code == 200`, `isinstance(binary_data, bytes)`, `binary_data == b"\x52\x49\x46\x46\x00\x00\x00\x00\x57\x45\x42\x50"`, `headers["content-type"] == "image/webp"`.
3. `test_f13_ssrf_ip_blocking`:
   - **Inputs:** URLs targeting private and non-global IP addresses: `http://127.0.0.1:8080/secret`, `http://169.254.169.254/latest/meta-data/`, `http://10.0.0.1/admin`, `http://192.168.1.1/config`, `http://[::1]/status`.
   - **Mechanism:** `editorial_http.validate_public_url(url)` and `editorial_http.fetch_html(url)`.
   - **Assertions:** `validate_public_url(url)[0] is False`, reason contains `"SSRF protection"`, `fetch_html(url)[0] == 400`, `headers["x-error"]` contains `"SSRF protection blocked URL"`.
4. `test_f13_two_tier_caching_and_ttl`:
   - **Inputs:** Route `/cached-page` served by `MockHttpServer`. First request with `use_cache=True`; second request with `use_cache=True`.
   - **Mechanism:** `editorial_http.clear_cache()`; invoke `fetch_html(url, use_cache=True)` twice; inspect `MockHttpServer.get_requests(path_contains="/cached-page")`.
   - **Assertions:** Server receives exactly 1 request; second response identical to first; Tier-1 memory cache hit; calling `editorial_http.clear_cache()` clears cache files on disk.
5. `test_f13_retry_after_backoff_handling`:
   - **Inputs:** Route `/rate-limited` on `MockHttpServer` with dynamic callback returning `429 Too Many Requests` with `Retry-After: 1` on call 1, and `200 OK` with `"Recovered"` on call 2.
   - **Mechanism:** `editorial_http.fetch_html(url, max_retries=2, use_cache=False)`.
   - **Assertions:** `status_code == 200`, `html_body == "Recovered"`, server request history records 2 attempts.
6. `test_f13_max_bytes_enforcement`:
   - **Inputs:** Route `/large-file` returning 5,000 bytes. Client invokes `fetch_html(url, max_bytes=2000, use_cache=False)`.
   - **Mechanism:** `editorial_http.fetch_html(url, max_bytes=2000, use_cache=False)`.
   - **Assertions:** `status_code == 413`, `html_body == ""`, `headers["x-error"]` contains `"exceeded maximum limit of 2000 bytes"`.

---

### Feature 14: `check_freshness.py` TLS & SSRF Integration
**Target Module:** `tests/e2e/tier1/test_f14_freshness_tls_ssrf.py`  
**Test Class:** `TestF14FreshnessTlsSsrf(E2ETestCase)`  
**Decorators:** `@tier(1)`, `@feature(14)`

1. `test_f14_editorial_http_transport_active`:
   - **Inputs:** Verify `check_freshness.py` contains `editorial_http` integration. Target route `/news/today` served on localhost `MockHttpServer` with valid today publication date.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", url, "--json"])`.
   - **Assertions:** `assertExitCode(res, 0)`, JSON report `status == "PASSED (Same-Day)"`, `targets[0]["state"] == "dated"`, `same_day == True`.
2. `test_f14_ssrf_private_ip_blocks_fail_closed`:
   - **Inputs:** Target URL `http://169.254.169.254/latest/meta-data/` passed as CLI argument.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", "http://169.254.169.254/latest/meta-data/", "--json"])`.
   - **Assertions:** `assertExitCode(res, 1)` (fail-closed), JSON report `passed == False`, `targets[0]["state"] == "unreachable"`, `targets[0]["note"]` contains `"SSRF protection"`.
3. `test_f14_rfc1918_private_network_blocks`:
   - **Inputs:** Target URL `http://192.168.1.100/article.html`.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", "http://192.168.1.100/article.html", "--json"])`.
   - **Assertions:** `assertExitCode(res, 1)`, `targets[0]["state"] == "unreachable"`.
4. `test_f14_network_timeout_handling`:
   - **Inputs:** Target URL pointing to a non-responsive mock port or unreachable IP.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", "http://192.0.2.1/timeout", "--timeout", "1", "--json"])`.
   - **Assertions:** `assertExitCode(res, 1)`, `targets[0]["state"] == "unreachable"`, report `passed == False`.
5. `test_f14_invalid_url_scheme_rejection`:
   - **Inputs:** Target `file:///etc/passwd` or `ftp://example.com/pub/news.html`.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", "ftp://example.com/pub/news.html", "--json"])`.
   - **Assertions:** `assertExitCode(res, 1)`, `targets[0]["state"] == "unreachable"`, report `passed == False`.

---

### Feature 15: `check_freshness.py` ISO Fractional Seconds
**Target Module:** `tests/e2e/tier1/test_f15_freshness_iso_fractional.py`  
**Test Class:** `TestF15FreshnessIsoFractional(E2ETestCase)`  
**Decorators:** `@tier(1)`, `@feature(15)`

1. `test_f15_parse_iso_fractional_milliseconds_utc_z`:
   - **Inputs:** ISO timestamp `2026-11-11T10:30:00.123Z`.
   - **Mechanism:** Python unit test importing `parse_iso_or_free` from `check_freshness.py`.
   - **Assertions:** Parsed `dt` is not `None`, `dt.year == 2026`, `dt.month == 11`, `dt.day == 11`, timezone is `Europe/Istanbul` (13:30:00).
2. `test_f15_parse_iso_microseconds_with_positive_offset`:
   - **Inputs:** ISO timestamp `2026-11-11T08:15:30.987654+02:00`.
   - **Mechanism:** `parse_iso_or_free("2026-11-11T08:15:30.987654+02:00")`.
   - **Assertions:** Parsed `dt` matches `2026-11-11 09:15:30` in `Europe/Istanbul`.
3. `test_f15_parse_iso_comma_decimal_separator`:
   - **Inputs:** ISO timestamp with comma decimal fraction: `2026-11-11T12:00:00,500Z`.
   - **Mechanism:** `parse_iso_or_free("2026-11-11T12:00:00,500Z")`.
   - **Assertions:** Parsed `dt` matches `2026-11-11 15:00:00` in `Europe/Istanbul`.
4. `test_f15_cli_date_fractional_today_passes`:
   - **Inputs:** `check_freshness.py --date "2026-11-11T10:00:00.000Z" --json` (using current today date).
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", "--date", f"{datetime.now(timezone.utc).strftime('%Y-%m-%d')}T10:00:00.000Z", "--json"])`.
   - **Assertions:** `assertExitCode(res, 0)`, `report["passed"] == True`, `report["status"] == "PASSED (Same-Day)"`.
5. `test_f15_meta_tag_fractional_seconds_html`:
   - **Inputs:** Mock HTML containing `<meta property="article:published_time" content="2026-11-11T09:20:15.123456Z">` served via `MockHttpServer`.
   - **Mechanism:** Execute `check_freshness.py` against mock URL with `--json`.
   - **Assertions:** `assertExitCode(res, 0)`, target state `dated`, `pub_datetime` contains `09:20:15` or Istanbul converted time, `same_day == True`.

---

### Feature 16: `check_freshness.py` Non-Content Tag Stripping
**Target Module:** `tests/e2e/tier1/test_f16_freshness_tag_strip.py`  
**Test Class:** `TestF16FreshnessTagStrip(E2ETestCase)`  
**Decorators:** `@tier(1)`, `@feature(16)`

1. `test_f16_footer_stale_copyright_stripped`:
   - **Inputs:** HTML fixture where `<article>` contains today's date, but `<footer>` contains `<p>© 2021-2023 Company Name. Tarih: 15.03.2023</p>`.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", html_file_path, "--json"])`.
   - **Assertions:** `assertExitCode(res, 0)`, `targets[0]["pub_date"]` reflects today's date, footer timestamp stripped.
2. `test_f16_aside_sidebar_recommended_date_stripped`:
   - **Inputs:** HTML fixture where article body is stale (2025-01-10), but `<aside class="sidebar">` contains `<time datetime="today">` for recommended stories.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", html_file_path, "--json"])`.
   - **Assertions:** `assertExitCode(res, 1)` (blocked), stale article date is detected, sidebar today timestamp ignored.
3. `test_f16_script_build_date_stripped`:
   - **Inputs:** HTML containing `<script type="text/javascript">window.__BUILD_TIME__ = "2026-11-11T12:00:00Z";</script>` and article body dated `2024-06-15`.
   - **Mechanism:** Execute `check_freshness.py` against file.
   - **Assertions:** `assertExitCode(res, 1)`, script tag is stripped, build time not picked up, status `BLOCKED`.
4. `test_f16_nav_header_ticker_stripped`:
   - **Inputs:** Header `<nav>` has a live news ticker showing today's date, while main `<article>` body date is `2025-08-20`.
   - **Mechanism:** Execute `check_freshness.py` against file.
   - **Assertions:** `assertExitCode(res, 1)`, `<nav>` is stripped, article identified as stale.
5. `test_f16_article_container_isolation`:
   - **Inputs:** Page with `<article>...</article>` and exterior non-content containers (`<style>`, `<noscript>`).
   - **Mechanism:** Direct call `check_freshness.extract_pub_date(html, url)`.
   - **Assertions:** `extract_pub_date` returns publication date strictly isolated from `<article>`, ignoring exterior timestamps.

---

### Feature 17: `check_freshness.py` Earliest Main Date & Deduplication
**Target Module:** `tests/e2e/tier1/test_f17_freshness_earliest_dedup.py`  
**Test Class:** `TestF17FreshnessEarliestDedup(E2ETestCase)`  
**Decorators:** `@tier(1)`, `@feature(17)`

1. `test_f17_date_published_vs_date_modified_earliest_wins`:
   - **Inputs:** HTML with JSON-LD having `datePublished: "2026-11-10T08:00:00Z"` (yesterday) and `dateModified: "2026-11-11T10:00:00Z"` (today).
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", file_path, "--json"])`.
   - **Assertions:** `assertExitCode(res, 1)` (stale), `targets[0]["evidence"]` indicates `json-ld datePublished`, modified date is rejected.
2. `test_f17_multiple_meta_tags_selects_earliest`:
   - **Inputs:** HTML with `<meta property="article:published_time" content="2026-11-11T05:00:00Z">` and `<meta name="publishdate" content="2026-11-11T11:00:00Z">`.
   - **Mechanism:** `check_freshness.extract_pub_date(html, "")`.
   - **Assertions:** Result `dt` matches `05:00:00Z` (the earliest candidate timestamp).
3. `test_f17_url_deduplication_in_draft_citations`:
   - **Inputs:** Draft HTML citing `https://www.rudaw.net/turkish/kurdistan/1287295` in 4 separate paragraphs.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", "--file", draft_path, "--dossier", dossier_path, "--no-network", "--json"])`.
   - **Assertions:** `len(report["targets"]) == 1`, `targets[0]["citation_count"] == 4`.
4. `test_f17_deduplication_network_fetch_count`:
   - **Inputs:** Draft citing `http://127.0.0.1:port/article` 3 times. Route registered in `MockHttpServer`.
   - **Mechanism:** Execute `check_freshness.py --file <draft>` against mock server.
   - **Assertions:** `len(mock_http.get_requests("/article")) == 1` (URL fetched exactly once).
5. `test_f17_normalized_url_trailing_slash_deduplication`:
   - **Inputs:** Positional arguments: `https://example.com/story/1` and `https://example.com/story/1/`.
   - **Mechanism:** Execute `check_freshness.py` with both URLs in `--no-network` mode.
   - **Assertions:** Output `targets` has exactly 1 entry with `citation_count == 2`.

---

### Feature 18: `check_freshness.py` Near-Midnight Diagnostics
**Target Module:** `tests/e2e/tier1/test_f18_freshness_near_midnight.py`  
**Test Class:** `TestF18FreshnessNearMidnight(E2ETestCase)`  
**Decorators:** `@tier(1)`, `@feature(18)`

1. `test_f18_yesterday_late_evening_flag_true`:
   - **Inputs:** Publication date set to yesterday at `22:30` Istanbul time (`(today - 1 day) 22:30:00`).
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", "--date", yesterday_2230, "--json"])`.
   - **Assertions:** `assertExitCode(res, 1)` (blocked because not today), `targets[0]["is_yesterday_late_evening"] is True`, `targets[0]["same_day"] is False`.
2. `test_f18_yesterday_morning_flag_false`:
   - **Inputs:** Publication date set to yesterday at `09:00` Istanbul time.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", "--date", yesterday_0900, "--json"])`.
   - **Assertions:** `targets[0]["is_yesterday_late_evening"] is False` (outside 18-23 hour window), `same_day is False`.
3. `test_f18_today_early_morning_flag_false`:
   - **Inputs:** Publication date set to today at `01:15` Istanbul time.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", "--date", today_0115, "--json"])`.
   - **Assertions:** `assertExitCode(res, 0)` (passed), `targets[0]["same_day"] is True`, `targets[0]["is_yesterday_late_evening"] is False`.
4. `test_f18_structured_age_hours_precision`:
   - **Inputs:** Article published 5.5 hours ago.
   - **Mechanism:** `check_freshness.check_target("test", "url", None, "", pub_dt)`.
   - **Assertions:** `5.4 <= result["age_hours"] <= 5.6`, `isinstance(result["age_hours"], float)`.
5. `test_f18_console_output_diagnostic_text`:
   - **Inputs:** `check_freshness.py --date <yesterday_21:00>`.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", "--date", yesterday_2100])`.
   - **Assertions:** `res.stdout` contains `yesterday late-evening near-midnight`, `assertExitCode(res, 1)`.

---

### Feature 19: `verify_facts_and_links.py` All-Paragraph Verification
**Target Module:** `tests/e2e/tier1/test_f19_facts_all_paragraphs.py`  
**Test Class:** `TestF19FactsAllParagraphs(E2ETestCase)`  
**Decorators:** `@tier(1)`, `@feature(19)`

1. `test_f19_all_paragraphs_evaluated_distinctly`:
   - **Inputs:** Draft `draft_with_all_paragraphs_valid.html` where Paragraph 1 and Paragraph 2 both cite the Rudaw URL.
   - **Mechanism:** `auditor = KevrastAuditor(); report = auditor.audit_draft(draft, dossier, skip_network=True)`.
   - **Assertions:** `len(report["url_audit"]) == 2`, `report["url_audit"][0]["paragraph_index"] == 1`, `report["url_audit"][1]["paragraph_index"] == 2`.
2. `test_f19_hallucination_in_paragraph_4_fails_audit`:
   - **Inputs:** Draft `draft_with_p4_hallucinated_num.html` where Paragraph 1 cites valid `45 dönüm` and Paragraph 4 cites fabricated `550 bin`. Target HTML contains `45` and `120`, but lacks `550 bin`.
   - **Mechanism:** `self.run_python([".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--file", draft_p4_path, "--dossier", dossier_path, "--json"])`.
   - **Assertions:** `assertExitCode(res, 1)` (failed), `report["status"] in ("FAILED", "BLOCK")`, Paragraph 4 audit has `anchoring.status == "block"`, `missing_numbers` contains `550000` or `550 bin`.
3. `test_f19_all_paragraphs_valid_passes`:
   - **Inputs:** Draft `draft_with_all_paragraphs_valid.html` tested against `mock_rudaw_article_1287295.html`.
   - **Mechanism:** `self.run_python([".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--file", draft_path, "--dossier", dossier_path, "--json"])`.
   - **Assertions:** `assertExitCode(res, 0)`, `report["status"] == "PASSED"`, all entries in `url_audit` have `anchoring.status == "pass"`.
4. `test_f19_explain_mode_locates_paragraph_index`:
   - **Inputs:** Draft `draft_with_p4_hallucinated_num.html` executed with `--explain`.
   - **Mechanism:** `self.run_python([".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--file", draft_p4_path, "--dossier", dossier_path, "--explain", "--json"])`.
   - **Assertions:** `report["diagnostics"]` contains an item with `paragraph_index == 4` and snippet from Paragraph 4.
5. `test_f19_single_paragraph_draft_compatibility`:
   - **Inputs:** Draft with a single `<p>` containing the citation link.
   - **Mechanism:** `auditor.audit_draft(single_p_draft, dossier, skip_network=True)`.
   - **Assertions:** `len(report["url_audit"]) == 1`, `url_audit[0]["paragraph_index"] == 1`.

---

### Feature 20: `verify_facts_and_links.py` Clean Number & Entity Extraction
**Target Module:** `tests/e2e/tier1/test_f20_facts_clean_numbers.py`  
**Test Class:** `TestF20FactsCleanNumbers(E2ETestCase)`  
**Decorators:** `@tier(1)`, `@feature(20)`

1. `test_f20_strip_non_content_removes_script_and_style_numbers`:
   - **Inputs:** Target HTML contains `<script>const chunkId = 88; const port = 3000;</script><style>.col-42 { width: 42%; }</style><p>Park alanında 120 adet fidan dikildi.</p>`.
   - **Mechanism:** `clean = strip_non_content_tags(html); nums = auditor.extract_distinctive_numbers(clean)`.
   - **Assertions:** `88` not in `nums`, `3000` not in `nums`, `42%` not in `nums`, `120` in `nums`.
2. `test_f20_exact_normalized_numeric_equality`:
   - **Inputs:** Citing paragraph claims `45`. Target text contains `1450` and `545`. Target does not contain standalone `45`.
   - **Mechanism:** `auditor.anchoring("45 adet fidan", target_html)`.
   - **Assertions:** `result["status"] == "block"`, `result["missing_numbers"] == ["45"]` (no false-positive substring match in `1450`).
3. `test_f20_percentage_normalization_equivalence`:
   - **Inputs:** Citing text writes `yüzde 80` and `80%`. Target text writes `80 percent`.
   - **Mechanism:** `auditor.anchoring("yüzde 80 katılım sağlandı", "<p>80 percent of residents participated</p>")`.
   - **Assertions:** `result["status"] == "pass"`, `result["note"]` indicates figure match `1/1`.
4. `test_f20_scaled_numeric_normalization`:
   - **Inputs:** Citing text writes `1.5 milyon`. Target text writes `1500000`.
   - **Mechanism:** `auditor.anchoring("Proje için 1.5 milyon lira ayrıldı", "<p>Ayrılan bütçe 1500000 liradır.</p>")`.
   - **Assertions:** Both normalize to `1500000`, `result["status"] == "pass"`.
5. `test_f20_thousands_separator_normalization`:
   - **Inputs:** Citing text writes `12,500` and target text writes `12.500`.
   - **Mechanism:** `normalize_number("12,500")` vs `normalize_number("12.500")`.
   - **Assertions:** Both return `"12500"`.

---

### Feature 21: `verify_facts_and_links.py` Vendor Single-Segment Allowlist
**Target Module:** `tests/e2e/tier1/test_f21_facts_vendor_allowlist.py`  
**Test Class:** `TestF21FactsVendorAllowlist(E2ETestCase)`  
**Decorators:** `@tier(1)`, `@feature(21)`

1. `test_f21_allowlisted_download_slug_with_version_passes`:
   - **Inputs:** URL `https://antigravity.google/download` with role `product`. Target page contains `v1.0.63`.
   - **Mechanism:** `auditor.audit_url(url, citing_text="Sürüm v1.0.63 yayınlandı", role="product")` against mock server.
   - **Assertions:** `entry["precision"]["precise"] is True`, `entry["precision"]["kind"] == "vendor_product_page"`.
2. `test_f21_non_allowlisted_single_segment_rejected`:
   - **Inputs:** URL `https://example.com/news` or `https://example.com/politics`.
   - **Mechanism:** `auditor.classify_precision("https://example.com/news", role="primary")`.
   - **Assertions:** `res["precise"] is False`, `res["kind"] == "section_front"`, `res["note"]` cites article page required.
3. `test_f21_allowlisted_slug_missing_version_fails_conditional_check`:
   - **Inputs:** URL `https://vendor.org/releases` with role `product`, but fetched HTML has generic marketing text without semantic version or source anchor.
   - **Mechanism:** `auditor.audit_url("https://vendor.org/releases", citing_text="İndirme bağlantısı", role="product")`.
   - **Assertions:** Post-fetch validation demotes precision: `entry["precision"]["precise"] is False`, `entry["precision"]["kind"] == "section_front"`.
4. `test_f21_allowlisted_slug_excessive_articles_rejected`:
   - **Inputs:** URL `https://vendor.org/releases` where fetched HTML has 5 `<article>` elements.
   - **Mechanism:** Post-fetch check in `audit_url()`.
   - **Assertions:** `entry["precision"]["precise"] is False` (article count > 2 indicates portal/blog).
5. `test_f21_primary_role_requires_deep_page`:
   - **Inputs:** URL `https://vendor.org/download` passed with role `primary` (not `supplementary`/`product`).
   - **Mechanism:** `auditor.classify_precision("https://vendor.org/download", role="primary")`.
   - **Assertions:** `res["precise"] is False`, `res["kind"] == "section_front"`.

---

### Feature 22: `verify_facts_and_links.py` Cross-Language Anchoring
**Target Module:** `tests/e2e/tier1/test_f22_facts_cross_language.py`  
**Test Class:** `TestF22FactsCrossLanguage(E2ETestCase)`  
**Decorators:** `@tier(1)`, `@feature(22)`

1. `test_f22_bilingual_anchoring_both_terms_present_passes`:
   - **Inputs:** English source HTML has `"test framework"`. Turkish draft paragraph has `"test çerçevesi"`. Source info declares `source_lang: "en"`, `source_anchors: [{"source_term": "test framework", "tr_term": "test çerçevesi"}]`.
   - **Mechanism:** `auditor.anchoring(citing_text, target_english_html, source_info)`.
   - **Assertions:** `res["status"] == "pass"`, `res["note"]` contains `"Cross-language anchor match: 1 pair(s) verified"`.
2. `test_f22_source_term_missing_on_foreign_page_blocks`:
   - **Inputs:** Turkish draft has `"test çerçevesi"`, but English source page lacks `"test framework"`.
   - **Mechanism:** `auditor.anchoring(citing_text, "<p>Arbitrary english text</p>", source_info)`.
   - **Assertions:** `res["status"] == "block"`, `res["note"]` contains `"source term(s) ['test framework'] not found on target page"`.
3. `test_f22_tr_term_missing_in_draft_blocks`:
   - **Inputs:** English source page has `"test framework"`, but Turkish draft paragraph mentions only unrelated words without `"test çerçevesi"`.
   - **Mechanism:** `auditor.anchoring("Bu haberde detaylar yer alıyor.", target_english_html, source_info)`.
   - **Assertions:** `res["status"] == "block"`, `res["note"]` contains `"none of the declared source_anchors"`.
4. `test_f22_foreign_source_without_anchors_and_numbers_blocks`:
   - **Inputs:** Source with `source_lang: "en"`, no figures in citing paragraph, and empty `source_anchors: []`.
   - **Mechanism:** `auditor.anchoring("Detaylı rapor sunuldu.", target_english_html, {"source_lang": "en", "source_anchors": []})`.
   - **Assertions:** `res["status"] in ("block", "review")`.
5. `test_f22_dossier_schema_validation_bilingual_anchors`:
   - **Inputs:** Dossier fixture `dossier_cross_lang_english_docs.json` declaring `source_lang` and `source_anchors`.
   - **Mechanism:** `self.run_python([".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--validate-dossier", dossier_cross_lang_path, "--json"])`.
   - **Assertions:** `assertExitCode(res, 0)`, `res.stdout` JSON contains `"valid": True`.

---

### Feature 23: `verify_facts_and_links.py` Turkish Morphology & Attribution
**Target Module:** `tests/e2e/tier1/test_f23_facts_turkish_morphology.py`  
**Test Class:** `TestF23FactsTurkishMorphology(E2ETestCase)`  
**Decorators:** `@tier(1)`, `@feature(23)`

1. `test_f23_tr_fold_unicode_dotted_dotless_i`:
   - **Inputs:** Strings `"DİYARBAKIR"`, `"İSTANBUL"`, `"ÇATIŞMA"`, `"ŞIRNAK"`.
   - **Mechanism:** Direct call `tr_fold(s)`.
   - **Assertions:** `tr_fold("DİYARBAKIR") == "diyarbakır"`, `tr_fold("İSTANBUL") == "istanbul"`, `\u0307` not present.
2. `test_f23_stem_institutional_case_suffixes`:
   - **Inputs:** Inflected proper nouns: `"Belediyesine"`, `"Bakanlığından"`, `"Valiliğinin"`, `"Müdürlüğüne"`, `"Odasına"`.
   - **Mechanism:** `stem_institutional(word)`.
   - **Assertions:** Returns `"belediyesi"`, `"bakanlığı"`, `"valiliği"`, `"müdürlüğü"`, `"odası"`.
3. `test_f23_institutional_entity_anchoring_match`:
   - **Inputs:** Citing text writes *"Diyarbakır Büyükşehir Belediyesine yönelik proje"*. Target HTML contains *"Diyarbakır Büyükşehir Belediyesi"*.
   - **Mechanism:** `auditor.anchoring(citing_text, target_html)`.
   - **Assertions:** `res["status"] == "pass"`, `res["note"]` contains `"Entity match"`.
4. `test_f23_expanded_outlet_attribution_requires_link`:
   - **Inputs:** Draft paragraph contains *"paylaşılan bilgilere göre"* or *"analize göre"* without an `<a href="...">` link in the paragraph.
   - **Mechanism:** `self.run_python([".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--file", draft_unlinked_path, "--dossier", dossier_path, "--json"])`.
   - **Assertions:** `assertExitCode(res, 1)`, report `linkage` list contains issue `"Attribution phrase lacks hyperlink (R4)"`.
5. `test_f23_speech_attribution_preceding_link_allowed`:
   - **Inputs:** Paragraph 2 has direct speech *"Yetkili, projenin tamamlandığını söyledi."* (no link), while Paragraph 1 contains the hyperlink to the source interview.
   - **Mechanism:** `auditor.audit_draft(draft_speech, dossier, skip_network=True)`.
   - **Assertions:** Linkage check passes for speech attribution (no error raised because preceding paragraph has link).

---

## 4. Proposed File Layout under `tests/e2e/tier1/`

To maintain adherence to `TEST_INFRA.md` (36 test modules, ≥5 tests / feature) and enable clean execution with `python3 tests/e2e/run_e2e_tests.py -f <id>`, the test suite for Features 13 to 23 should be implemented as follows:

```
tests/e2e/tier1/
├── __init__.py
├── test_f00_infra_smoke.py                  # Infrastructure smoke test (Features: 34)
├── test_f13_editorial_http.py               # Feature 13: Shared HTTP client (6 tests)
├── test_f14_freshness_tls_ssrf.py           # Feature 14: Freshness TLS & SSRF guard (5 tests)
├── test_f15_freshness_iso_fractional.py     # Feature 15: Freshness ISO fractional seconds (5 tests)
├── test_f16_freshness_tag_strip.py          # Feature 16: Freshness non-content tag strip (5 tests)
├── test_f17_freshness_earliest_dedup.py     # Feature 17: Freshness earliest date & dedup (5 tests)
├── test_f18_freshness_near_midnight.py      # Feature 18: Freshness near-midnight diagnostics (5 tests)
├── test_f19_facts_all_paragraphs.py         # Feature 19: Fact-checking all-paragraph audit (5 tests)
├── test_f20_facts_clean_numbers.py          # Feature 20: Clean numeric extraction & equality (5 tests)
├── test_f21_facts_vendor_allowlist.py       # Feature 21: Vendor single-segment allowlist (5 tests)
├── test_f22_facts_cross_language.py         # Feature 22: Bilingual cross-language anchoring (5 tests)
└── test_f23_facts_turkish_morphology.py     # Feature 23: Turkish morphology & attribution (5 tests)
```

**Total Modules:** 11 modules  
**Total Test Cases:** 56 tests  

---

## 5. Caveats & Recommendations for Test Authoring

1. **Localhost Mocking vs. SSRF Protection Interaction**:
   - `MockHttpServer` runs on `127.0.0.1`. However, `editorial_http.py`'s SSRF guard intentionally blocks `127.0.0.1` and loopback addresses.
   - For tests validating `editorial_http` or scripts fetching over HTTP, test authors should either:
     a) Use `MockHttpServer` to test non-network validation / SSRF rejection (asserting that `127.0.0.1` is correctly blocked).
     b) For happy-path network fetching of mock server responses, temporarily mock or parameterize the SSRF check, or use local file fixtures via `check_freshness.py <file>` / `verify_facts_and_links.py --file <file>` with `--no-network`.
2. **Current Calendar Date Sensitivity in Freshness Checks**:
   - `check_freshness.py` enforces same-day publication against `now_istanbul()`. Hardcoding dates (e.g. `2026-11-11`) in tests will fail when tests run on future dates.
   - **Recommendation:** Tests must dynamically compute `today = datetime.now(ZoneInfo("Europe/Istanbul")).date()` when generating test dates or mock timestamps, rather than relying on static calendar dates.
3. **Draft Containment (`R4`) in Kevrast Tests**:
   - In `verify_facts_and_links.py`, every link in the draft HTML must be present in the `--dossier` sources list (chain of custody). If a test draft contains a link not in the dossier, it will fail R4 containment before reaching R3 anchoring.
   - **Recommendation:** Always ensure mock dossiers declare all URLs present in the mock draft.
4. **Timezone Offset Parsing**:
   - When constructing ISO timestamps for Feature 15 tests, always specify the offset (e.g. `+00:00` or `Z`) to test UTC conversion deterministically.

---

## 6. Verification Method

To verify the findings and test designs outlined in this report:

1. **Verify Existing E2E Runner and Test Discovery:**
   ```bash
   python3 tests/e2e/run_e2e_tests.py --list
   python3 tests/e2e/run_e2e_tests.py
   ```
   *Expected result:* Discovers and executes existing smoke tests with exit code `0`.

2. **Verify Target Script Interfaces and Schema:**
   ```bash
   python3 .agents/skills/kevtemen/scripts/check_freshness.py --help
   python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --help
   python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --validate-dossier tests/e2e/fixtures/editorial/dossier_cross_lang_english_docs.json
   ```
   *Expected result:* Exits with code `0`, validating that `--validate-dossier` recognizes the cross-language schema.

3. **Verify Implementation Files Exist:**
   - `.agents/skills/_shared/editorial_http.py`
   - `.agents/skills/kevtemen/scripts/check_freshness.py`
   - `.agents/skills/kevrast/scripts/verify_facts_and_links.py`
   - `.agents/skills/kevkolin/scripts/dossier.schema.json`

4. **Invalidation Conditions:**
   - Any modification to `editorial_http.py` function signatures (`fetch_html`, `fetch_binary`, `validate_public_url`).
   - Alteration of CLI exit code conventions (`0` = pass, `1` = block/fail, `2` = usage error).
   - Reverting the multi-paragraph extraction logic `_find_all_citing_blocks` in `verify_facts_and_links.py`.
