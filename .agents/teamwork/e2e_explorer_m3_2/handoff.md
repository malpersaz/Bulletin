# Handoff Report: Tier 2 Boundary & Corner Case Suite Architecture & Test Blueprints (Features 13–23)

**Author:** `e2e_explorer_m3_2` (Explorer 2)  
**Milestone:** `E2E-M3: Tier 2 Boundary & Corner Case Suite`  
**Working Directory:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**State Folder:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_2/`  
**Target Specification:** `PROJECT.md`, `SCOPE.md`, `TEST_INFRA.md`, `DISPATCH.md`  
**Assigned Scope:** Features 13 through 23 in `PROJECT.md § Feature Inventory`  

---

## 1. Observation

Direct observations and evidence collected across target script implementations, network isolation mechanisms, and test harness infrastructure:

### 1.1 Implementation Codebase Analysis for Features 13–23

1. **Feature 13: Shared HTTP Client (`.agents/skills/_shared/editorial_http.py`)**:
   - Lines 76–105 (`BLOCKED_IPV4_NETWORKS`, `BLOCKED_IPV6_NETWORKS`): Blacklists all private IPv4 (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`), Carrier-Grade NAT (`100.64.0.0/10`, RFC 6598), loopback (`127.0.0.0/8`), link-local / cloud metadata (`169.254.0.0/16`), documentation (`192.0.2.0/24`, `198.51.100.0/24`, `203.0.113.0/24`), zero-net (`0.0.0.0/8`), broadcast (`255.255.255.255/32`), IPv6 loopback (`::1`), ULA (`fc00::/7`), IPv4-mapped IPv6 (`::ffff:0:0/96`), and documentation (`2001:db8::/32`).
   - Lines 175–208 (`validate_public_url`): Validates scheme (`http`/`https`), host existence, port range (`1 <= port <= 65535`), label length (`<= 63` chars), and overall hostname length (`<= 253` chars). Rejects non-string and empty inputs.
   - Lines 209–234 (`validate_public_url` DNS resolution): Calls `socket.getaddrinfo()`. Iterates over ALL resolved IP addresses. If ANY IP address belongs to a blocked network, returns `(False, f"SSRF protection: host '{hostname}' resolves to non-public IP ...")`, effectively mitigating DNS rebinding attacks.
   - Lines 239–260 (`SSRFSafeRedirectHandler`): Subclasses `urllib.request.HTTPRedirectHandler` with `max_redirections = 5`. Re-validates each redirect target against `validate_public_url()`. Raises `HTTPError` if redirect target is private or exceeds 5 hops.
   - Lines 272–292 (`_parse_retry_after`): Parses integer seconds or RFC 7231 / RFC 2822 HTTP-date. Lines 483–497 cap `sleep_sec` at `MAX_RETRY_SLEEP_SECONDS = 15.0`. Returns `0.0` and falls back to `FALLBACK_RETRY_DELAYS` on malformed date strings.
   - Lines 297–358 (`get_cached_response`): Checks Tier 1 memory cache and Tier 2 disk cache. Evaluates `(now - timestamp) <= ttl`. At `now - timestamp > ttl`, deletes memory cache entry and removes expired disk file. Lines 416–437 (`clear_cache`): Supports selective eviction with `older_than` parameter.

2. **Feature 14: `check_freshness.py` TLS & SSRF Integration (`.agents/skills/kevtemen/scripts/check_freshness.py`)**:
   - Lines 361–375 (`fetch_html`): Calls `editorial_http.fetch_html()`. Raises `urllib.error.HTTPError` on `status >= 400` and `urllib.error.URLError` on transport exceptions (such as untrusted TLS certificates or SSRF blocks).
   - Lines 629–644: Catches `(urllib.error.URLError, OSError, ValueError, Exception)`, records `state="unreachable"`, `same_day=False`, and exits with code `1` (fail-closed).
   - Lines 566–578: Positional targets not starting with `http://` or `https://` are treated as local files; invalid schemes (e.g. `gopher://`, `file://`) fail closed. Empty HTTP response bodies result in `extract_pub_date()` returning `None`, setting `state="undated"`, `same_day=False`, and exit code `1`.

3. **Feature 15: `check_freshness.py` ISO Fractional Seconds (`.agents/skills/kevtemen/scripts/check_freshness.py`)**:
   - Lines 75–78 (`RE_ISO`): Regex `r"(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?(?:[.,]\d{1,9})?(Z|[+-]\d{2}:?\d{2})?)?"` parses 1 to 9 fractional digits with either period `.` or comma `,`.
   - Lines 121–148 (`_apply_tz`): Converts UTC (`Z`) or offset (`+14:00`, `-12:00`, `+0300`) to `Europe/Istanbul`. Handles invalid date/time components (e.g. leap second `23:59:60` or month `13`) via `try: naive = datetime(...) except ValueError: return None`.

4. **Feature 16: `check_freshness.py` Non-Content Tag Stripping (`.agents/skills/kevtemen/scripts/check_freshness.py`)**:
   - Lines 258–266: Before date scanning, isolates `<article>` container if present. Strips `<aside>`, `<nav>`, `<script>`, `<style>`, `<noscript>`, `<header>`, `<footer>` using `re.I | re.DOTALL`.
   - Nested tags (`<nav><script>...</script></nav>`), uppercase tags (`<SCRIPT>`, `<NAV>`), CDATA blocks, and tag attributes containing date strings are cleanly removed, preventing byline or visible date false-positives.

5. **Feature 17: `check_freshness.py` Earliest Main Date & Deduplication (`.agents/skills/kevtemen/scripts/check_freshness.py`)**:
   - Lines 215–256: Evaluates meta tags and JSON-LD `datePublished` (restricted to `ALLOWED_JSONLD_TYPES = {"NewsArticle", "Article", "ReportageNewsArticle", "BlogPosting", "WebPage"}`). Filters out unrelated `@graph` types (`BreadcrumbList`, `Organization`). If multiple candidates exist, `min(p1_candidates, key=lambda c: c[0].timestamp())` selects the earliest timestamp.
   - Lines 550–605: `url_items = OrderedDict()` deduplicates URLs by normalized key `href.strip().rstrip("/")`. Increments `citation_count` for repeated URLs and executes network fetch exactly once. Empty input (0 URLs and no text) exits with code `2`.

6. **Feature 18: `check_freshness.py` Near-Midnight Diagnostics (`.agents/skills/kevtemen/scripts/check_freshness.py`)**:
   - Lines 408–423: Computes `age_hours = round((now - pub_dt).total_seconds() / 3600.0, 2)` and `is_yesterday_late_evening = (pub_date == yesterday) and (18 <= pub_dt.hour <= 23)`.
   - Near-midnight boundary at `23:59:59` yesterday evening produces `is_yesterday_late_evening=True`, `same_day=False`. Exact start of day `00:00:01` today produces `same_day=True`. Future dates produce negative `age_hours` and are BLOCKED (`same_day=False`). Turkey observes permanent UTC+3 without DST.

7. **Feature 19: `verify_facts_and_links.py` All-Paragraph Verification (`.agents/skills/kevrast/scripts/verify_facts_and_links.py`)**:
   - Lines 513–540 & 648–660: `_extract_blocks()` extracts all `<p>`, `<blockquote>`, and `<li>` blocks. `_find_all_citing_blocks()` maps each URL to all citing blocks. `audit_draft()` audits every citing block with `entry["paragraph_index"] = b_idx`.
   - In a 100+ paragraph draft, if Paragraph 1 is valid but Paragraph 105 introduces a fabricated number, `passed = False` and script exits with code `1`. Empty drafts (0 links) trigger `zero_link = True`, `passed = False`. Unregistered URLs fail R4 containment. Single paragraphs citing multiple URLs are both evaluated.

8. **Feature 20: `verify_facts_and_links.py` Clean Number & Entity Extraction (`.agents/skills/kevrast/scripts/verify_facts_and_links.py`)**:
   - Lines 197–207 (`normalize_number`): Normalizes US `1,234.56` and European `1.234,56` to standard decimal format.
   - Lines 361–401 (`extract_distinctive_numbers`): Extracts percentages (`%80`, `yüzde 80`, `80 percent` -> `80%`), written percentages (`yüzde yetmiş` -> `70%`), scaled numbers (`80 bin` -> `80000`, `1.5 trilyon` -> `1500000000000`), and generic numbers >= 2 digits. Strips currency symbols (`$`, `€`, `₺`).
   - Lines 423–434: Enforces exact set membership `missing = [n for n in nums if n not in target_nums_set]`, completely preventing substring false matches (such as `45` inside `1450` or `20` inside `2026`).

9. **Feature 21: `verify_facts_and_links.py` Vendor Single-Segment Allowlist (`.agents/skills/kevrast/scripts/verify_facts_and_links.py`)**:
   - Lines 76–80 & 343–358: `SINGLE_SEGMENT_PRODUCT_SLUGS = {"download", "downloads", "releases", "changelog", "security", "pricing", "status", "docs", "manifesto", "about"}`.
   - Only permitted if `slug in SINGLE_SEGMENT_PRODUCT_SLUGS` and `role in ("supplementary", "product")`. If `role == "primary"`, it is rejected as a section front. Domain roots (`https://vendor.com/`) are strictly rejected regardless of role.
   - Lines 602–618: Post-fetch validation enforces `<article>` tag count `<= 2` and presence of semantic version string (`SEMANTIC_VERSION_RE = r"\bv?\d+\.\d+(?:\.\d+)?\b"`) or declared source anchor. Disguised section fronts with `> 2` articles fail post-fetch check.

10. **Feature 22: `verify_facts_and_links.py` Cross-Language Anchoring (`.agents/skills/kevrast/scripts/verify_facts_and_links.py`)**:
    - Lines 436–474: Evaluates `source_anchors` list containing `{"source_term": "...", "tr_term": "..."}` pairs. Enforces two-sided matching: `source_term` must appear on source page, and `tr_term` must appear in citing paragraph.
    - If `source_term` is missing on source page, flags `missing_on_source` (status `block`). If `source_lang != "tr"` and no anchors match, flags `R3 cross-language anchoring error` (status `block`).
    - `dossier.schema.json` validates `source_lang` enum `["tr", "en", "ku", "ar", "de", "fr"]` and `source_anchors` items (`minLength: 3`).

11. **Feature 23: `verify_facts_and_links.py` Turkish Morphology & Attribution (`.agents/skills/kevrast/scripts/verify_facts_and_links.py`)**:
    - Lines 105–131: `tr_fold()` converts `İ` -> `i`, `I` -> `ı`, strips `\u0307`. `stem_institutional()` stems case suffixes across 9 institutional categories (`-ne`, `-nde`, `-nden`, `-nin`, `-ni`).
    - Lines 403–413: Handles both straight `'` and curly `’` apostrophes (`tok.split("'")[0].split("’")[0]`). Punctuation attached to entity tokens is separated by word boundary regex. Non-institutional proper nouns remain intact.
    - Lines 83–102 & 668–694: `OUTLET_ATTRIBUTION_RE` requires a link in the citing paragraph. Direct speech attribution allows a link in the current or preceding paragraph.

### 1.2 Test Runner & Harness State
- `tests/e2e/run_e2e_tests.py`: Operational E2E runner supporting `--tier 1|2|3|4|all`, `--feature <id>`, `--no-network`, `--format <console|json|tap|junit>`.
- `tests/e2e/harness/runner.py`: Discovers test files matching `test_*.py` under `tests/e2e/tier2/`.
- All 56 Tier 1 tests for Features 13–23 currently pass in 13.31 seconds (`python3 tests/e2e/run_e2e_tests.py --tier 1 -f 13-23`).

---

## 2. Logic Chain

1. From **Observation 1.1 (F13)**, `editorial_http.py` is the central transport layer. Its boundary vulnerabilities include malformed URLs, complex SSRF bypasses (IPv4-mapped IPv6, CGNAT, documentation subnets), DNS rebinding with split IP responses, excessive redirect loops (>5 hops), large `Retry-After` values, and two-tier cache TTL expiration boundaries. Blueprinting 6 tests targeting these specific boundaries guarantees transport robustness.
2. From **Observation 1.1 (F14)**, `check_freshness.py` relies on `editorial_http`. Its failure modes include untrusted TLS certificates, forbidden schemes (`file://`, `gopher://`), connection timeouts, 0-byte responses, and HTTP 4xx/5xx errors, which must all fail closed with exit code 1. Blueprinting 5 negative tests validates strict gate enforcement.
3. From **Observation 1.1 (F15)**, `RE_ISO` and `_apply_tz()` must handle edge cases in fractional seconds (1 to 9 digits), European comma notation, extreme timezone offsets (+14:00, -12:00), leap seconds (23:59:60), and malformed dates without uncaught exceptions. Blueprinting 5 tests covers the full ISO syntax spectrum.
4. From **Observation 1.1 (F16)**, non-content tag stripping must withstand nested tags, case-insensitivity (`<SCRIPT>`), date attributes within stripped elements, CDATA blocks, and footer copyright ranges. Blueprinting 5 tests ensures zero date contamination from peripheral DOM elements.
5. From **Observation 1.1 (F17)**, metadata conflicts between Meta tags and JSON-LD must resolve to the earliest publication date, `@graph` arrays must filter non-article entities, duplicate URLs with varying fragments or parameters must be deduplicated, and empty inputs must be rejected with code 2. Blueprinting 5 tests guarantees deduplication and date priority contracts.
6. From **Observation 1.1 (F18)**, near-midnight boundaries (23:59:59 yesterday vs 00:00:01 today), future date rejections (negative `age_hours`), permanent UTC+3 stability (zero DST jitter), and leap year dates (Feb 29) require precise boundary assertions. Blueprinting 5 tests covers temporal edge cases.
7. From **Observation 1.1 (F19)**, `verify_facts_and_links.py` must audit all citing blocks across high-volume drafts (100+ paragraphs), catch late-paragraph hallucinations, handle empty drafts, reject undeclared URLs, and audit multi-link paragraphs. Blueprinting 5 tests validates complete draft auditing.
8. From **Observation 1.1 (F20)**, numeric extraction must normalize European vs US formats, isolate currency symbols (`$`, `€`, `₺`), prevent substring false matches (`45` in `1450`), handle extreme scales (`>= 10^12`), and convert written percentage words. Blueprinting 5 tests ensures strict numeric equality.
9. From **Observation 1.1 (F21)**, vendor single-segment allowlists must reject root domains, disallow non-whitelisted rubric slugs, enforce role restrictions (`role != "primary"`), cap post-fetch `<article>` tags at 2, and normalize trailing slashes. Blueprinting 5 tests validates vendor boundary rules.
10. From **Observation 1.1 (F22)**, cross-language anchoring must reject unsupported schema language codes, block foreign sources missing anchors, catch one-sided term mismatches, and handle RTL/Kurdish Unicode text. Blueprinting 5 tests guarantees cross-lingual integrity.
11. From **Observation 1.1 (F23)**, Turkish morphology must correctly handle dotted/dotless I under `tr_fold()`, stem 9 institutional case declensions (`-ne/-nde/-nden/-nin/-ni`), parse curly apostrophes `’`, strip attached punctuation, and preserve non-institutional nouns. Blueprinting 5 tests ensures linguistic accuracy.
12. Therefore, designing 11 distinct test modules under `tests/e2e/tier2/` with a total of **56 concrete test cases** completely fulfills the Tier 2 blueprint requirements for Milestone E2E-M3.

---

## 3. Concrete Test Blueprints (Features 13 to 23)

A total of **56 test cases** are designed below across 11 test modules under `tests/e2e/tier2/`.

### Feature 13: Shared HTTP Client Boundaries (`editorial_http.py`)
**Target Module:** `tests/e2e/tier2/test_f13_editorial_http_boundaries.py`  
**Test Class:** `TestF13EditorialHttpBoundaries(E2ETestCase)`  
**Decorators:** `@tier(2)`, `@feature(13)`

1. `test_f13_malformed_url_boundary`:
   - **Inputs:** `""`, `None`, `"http://"`, `"http://:8080"`, `"http://example.com:99999"`, `"http://example.com:-1"`, `"ftp://example.com/file"`, `"http://" + "a" * 64 + ".com"`, `"http://" + "a." * 130 + "com"`.
   - **Mechanism:** `editorial_http.validate_public_url(url)` and `editorial_http.fetch_html(url)`.
   - **Assertions:** `valid is False`, reason identifies empty/scheme/port/label/host error, `fetch_html()` returns `status_code == 400`, `headers["x-error"]` populated.
2. `test_f13_advanced_ssrf_boundary_matrix`:
   - **Inputs:** IPv4-mapped IPv6 `http://[::ffff:127.0.0.1]/`, Carrier-Grade NAT `http://100.64.0.1/`, cloud metadata `http://169.254.169.254/latest/meta-data/`, ULA `http://[fc00::1]/`, documentation IPs `http://192.0.2.1/` and `http://[2001:db8::1]/`, zero-net `http://0.0.0.0/`, broadcast `http://255.255.255.255/`.
   - **Mechanism:** `editorial_http.validate_public_url(url)`.
   - **Assertions:** All return `(False, reason)` where reason contains `"SSRF protection"`, confirming full RFC block coverage.
3. `test_f13_dns_rebinding_mixed_ip_boundary`:
   - **Inputs:** Hostname `"rebind.example.com"` mocked via `patch("socket.getaddrinfo")` returning mixed IP list: `[(..., ('93.184.216.34', 80)), (..., ('127.0.0.1', 80))]`.
   - **Mechanism:** `editorial_http.validate_public_url("http://rebind.example.com/api")`.
   - **Assertions:** Returns `(False, reason)`; reason indicates host resolves to non-public IP `127.0.0.1` (fail-closed on any private IP).
4. `test_f13_redirect_ceiling_and_private_target_boundary`:
   - **Inputs:** 
     - Case A: Route `/redir-loop-1` redirecting across 6 hops (`/r1` -> `/r2` -> `/r3` -> `/r4` -> `/r5` -> `/r6`).
     - Case B: Route `/redir-to-private` on mock server returning HTTP 302 with `Location: http://192.168.1.1/secret`.
   - **Mechanism:** `editorial_http.fetch_html(url, use_cache=False)`.
   - **Assertions:** 
     - Case A: Returns status `0` or `302` with error indicating redirect ceiling exceeded (> 5 hops).
     - Case B: Returns status `400` with error `"SSRF protection blocked redirect"`.
5. `test_f13_retry_after_header_bounds`:
   - **Inputs:** 
     - Case A: Server returns HTTP 429 with `Retry-After: 999999`.
     - Case B: Server returns HTTP 429 with RFC 7231 HTTP-date `Retry-After: <now + 2 seconds in GMT>`.
     - Case C: Server returns HTTP 429 with malformed `Retry-After: invalid-garbage`.
   - **Mechanism:** `editorial_http._parse_retry_after(header_val)`.
   - **Assertions:** 
     - Case A: `min(parsed, 15.0) == 15.0` (capped at 15s).
     - Case B: `1.0 <= parsed <= 3.0` (accurate date parsing).
     - Case C: `parsed == 0.0` (graceful fallback without unhandled exception).
6. `test_f13_cache_ttl_and_eviction_boundary`:
   - **Inputs:** Mock route `/ttl-check`. Set cached response with artificial timestamp `time.time() - 601` (expired) vs `time.time() - 599` (fresh).
   - **Mechanism:** `editorial_http.get_cached_response(url, ttl=600)` and `editorial_http.clear_cache(older_than=300)`.
   - **Assertions:** Expired entry returns `None` and disk file is purged; fresh entry returns `(200, body, headers)`; `clear_cache` returns count of cleared files.

---

### Feature 14: `check_freshness.py` TLS & SSRF Boundaries
**Target Module:** `tests/e2e/tier2/test_f14_freshness_tls_ssrf_boundaries.py`  
**Test Class:** `TestF14FreshnessTlsSsrfBoundaries(E2ETestCase)`  
**Decorators:** `@tier(2)`, `@feature(14)`

1. `test_f14_untrusted_ssl_cert_boundary`:
   - **Inputs:** Target URL returning SSL verification error (mocked via `patch.object(editorial_http, "fetch_html", return_value=(0, "", {"error": "SSLCertVerificationError"}))`).
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", "https://untrusted-bank.com/article", "--json"])`.
   - **Assertions:** Exit code `1`; JSON report `passed is False`; target state is `"unreachable"`; note contains `"SSLCertVerificationError"`.
2. `test_f14_non_http_scheme_boundary`:
   - **Inputs:** Target strings `file:///etc/passwd`, `gopher://127.0.0.1/1`, `ftp://files.example.com`.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", "file:///etc/passwd", "--json"])`.
   - **Assertions:** Exit code `1`; JSON report `passed is False`; target state is `"unreachable"`.
3. `test_f14_timeout_boundary_enforcement`:
   - **Inputs:** Target URL on mock server where callback sleeps 2 seconds, while CLI specifies `--timeout 1`.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", slow_url, "--timeout", "1", "--json"])`.
   - **Assertions:** Exit code `1`; target state is `"unreachable"`; note indicates timeout.
4. `test_f14_empty_response_body_boundary`:
   - **Inputs:** Mock route `/empty-page` returning HTTP 200 with content `""` (0 bytes).
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", empty_url, "--json"])`.
   - **Assertions:** Exit code `1`; JSON report `passed is False`; target state is `"undated"`; note contains `"Publication date could not be determined"`.
5. `test_f14_http_error_status_boundaries`:
   - **Inputs:** Mock routes returning HTTP 403 Forbidden, 404 Not Found, and 500 Internal Server Error.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", err_url, "--json"])`.
   - **Assertions:** Exit code `1`; target state is `"unreachable"`; note includes HTTP status.

---

### Feature 15: `check_freshness.py` ISO Fractional Seconds Boundaries
**Target Module:** `tests/e2e/tier2/test_f15_freshness_iso_fractional_boundaries.py`  
**Test Class:** `TestF15FreshnessIsoFractionalBoundaries(E2ETestCase)`  
**Decorators:** `@tier(2)`, `@feature(15)`

1. `test_f15_fractional_digits_spectrum`:
   - **Inputs:** Strings with varying fractional precision:
     - 1 digit: `2026-11-11T14:30:00.1Z`
     - 2 digits: `2026-11-11T14:30:00.12Z`
     - 3 digits: `2026-11-11T14:30:00.123Z`
     - 6 digits: `2026-11-11T14:30:00.123456Z`
     - 9 digits: `2026-11-11T14:30:00.123456789Z`
   - **Mechanism:** Call `check_freshness.parse_iso_or_free(s)`.
   - **Assertions:** All return valid `datetime` objects; calendar date in Istanbul matches `2026-11-11`; hour converted from UTC `14:30` to Istanbul `17:30`.
2. `test_f15_european_comma_fractional_separator`:
   - **Inputs:** `2026-11-11T10:15:30,500+03:00` and `2026-11-11T08:00:00,123456Z`.
   - **Mechanism:** `check_freshness.parse_iso_or_free(s)`.
   - **Assertions:** Returns valid `datetime`; comma `,` parsed equivalently to period `.`; timezone converted accurately.
3. `test_f15_extreme_timezone_offsets`:
   - **Inputs:**
     - Offset +14:00 (Kiritimati): `2026-11-11T20:00:00+14:00` (UTC 06:00 -> Istanbul 09:00 on 2026-11-11).
     - Offset -12:00 (Baker Island): `2026-11-11T02:00:00-12:00` (UTC 14:00 -> Istanbul 17:00 on 2026-11-11).
   - **Mechanism:** `check_freshness.parse_iso_or_free(s)`.
   - **Assertions:** Converted Istanbul dates both match `datetime.date(2026, 11, 11)`; hour equals `9` and `17` respectively.
4. `test_f15_leap_second_boundary`:
   - **Inputs:** Leap second timestamp `2026-11-11T23:59:60Z`.
   - **Mechanism:** `check_freshness.parse_iso_or_free("2026-11-11T23:59:60Z")`.
   - **Assertions:** Returns `None` gracefully without throwing unhandled `ValueError` exception.
5. `test_f15_malformed_iso_strings`:
   - **Inputs:** Month 13 `2026-13-11T10:00:00Z`, Day 32 `2026-11-32T10:00:00Z`, Hour 25 `2026-11-11T25:00:00Z`, Truncated `2026-11-`, Non-numeric `2026-11-11Tabcd`.
   - **Mechanism:** `check_freshness.parse_iso_or_free(s)`.
   - **Assertions:** Returns `None` or extracts valid date prefix without crashing.

---

### Feature 16: `check_freshness.py` Non-Content Tag Stripping Boundaries
**Target Module:** `tests/e2e/tier2/test_f16_freshness_tag_strip_boundaries.py`  
**Test Class:** `TestF16FreshnessTagStripBoundaries(E2ETestCase)`  
**Decorators:** `@tier(2)`, `@feature(16)`

1. `test_f16_nested_non_content_tags`:
   - **Inputs:** HTML containing `<nav><script>var oldDate = "2020-01-01T00:00:00Z";</script><span>Menu</span></nav><article><p>Haber metni</p><time datetime="{TODAY}T10:00:00Z"></time></article>`.
   - **Mechanism:** `check_freshness.extract_pub_date(html, "https://example.com/art")`.
   - **Assertions:** Returns date matching today (`{TODAY}`); evidence contains `"article time tag"`; date `2020-01-01` in nested script is discarded.
2. `test_f16_case_insensitive_tag_stripping`:
   - **Inputs:** HTML with uppercase tags: `<SCRIPT>var d="2019-05-01";</SCRIPT><ASIDE>Eski haber: 01.01.2019</ASIDE><article><p>Giriş tarihi: {TODAY_DOT}</p></article>`.
   - **Mechanism:** `check_freshness.extract_pub_date(html, "https://example.com/art")`.
   - **Assertions:** Returns today's date; uppercase `<SCRIPT>` and `<ASIDE>` are stripped.
3. `test_f16_tag_attributes_containing_dates`:
   - **Inputs:** HTML with date strings in attributes: `<aside data-published="2026-11-11" class="date-2026">Eski haber 01.01.2020</aside><p>Yayınlanma: 05.05.2021</p>`.
   - **Mechanism:** `check_freshness.extract_pub_date(html, "https://example.com/art")`.
   - **Assertions:** Returns `05.05.2021`; `data-published="2026-11-11"` in `<aside>` is completely stripped and not parsed as today.
4. `test_f16_cdata_blocks_in_scripts`:
   - **Inputs:** HTML containing `<script type="text/javascript"><![CDATA[ var pub = "{TODAY}T00:00:00Z"; ]]></script><article><time datetime="2022-01-01T00:00:00Z"></time></article>`.
   - **Mechanism:** `check_freshness.extract_pub_date(html, "https://example.com/art")`.
   - **Assertions:** Returns `2022-01-01`; CDATA date inside script is stripped and not promoted.
5. `test_f16_footer_and_header_stale_dates`:
   - **Inputs:** HTML with `<footer>Copyright © 2018-2024 Gazette. Tüm hakları saklıdır.</footer><article><p>Giriş: {TODAY_DOT}</p></article>`.
   - **Mechanism:** `check_freshness.extract_pub_date(html, "https://example.com/art")`.
   - **Assertions:** Returns today's date; footer copyright years (`2018-2024`) are stripped and do not match body date regex.

---

### Feature 17: `check_freshness.py` Earliest Main Date & Deduplication Boundaries
**Target Module:** `tests/e2e/tier2/test_f17_freshness_earliest_dedup_boundaries.py`  
**Test Class:** `TestF17FreshnessEarliestDedupBoundaries(E2ETestCase)`  
**Decorators:** `@tier(2)`, `@feature(17)`

1. `test_f17_conflicting_meta_vs_jsonld_dates`:
   - **Inputs:** HTML where meta `article:published_time` specifies `{TODAY}T12:00:00Z`, but JSON-LD `datePublished` specifies `{YESTERDAY}T20:00:00Z`.
   - **Mechanism:** `check_freshness.extract_pub_date(html, "https://example.com/art")`.
   - **Assertions:** Selected datetime is `{YESTERDAY}T20:00:00Z` (`min()` timestamp selection); evidence cites `"json-ld datePublished"`.
2. `test_f17_jsonld_graph_multi_entity_filtering`:
   - **Inputs:** JSON-LD `@graph` array containing:
     - `@type: "BreadcrumbList"` with date `{TODAY}`
     - `@type: "Organization"` with copyright date `2015`
     - `@type: "NewsArticle"` with `datePublished: "{YESTERDAY}T15:00:00Z"`
   - **Mechanism:** `check_freshness.extract_pub_date(html, "https://example.com/art")`.
   - **Assertions:** Selects `NewsArticle` datePublished `{YESTERDAY}`; ignores `BreadcrumbList` and `Organization`.
3. `test_f17_url_deduplication_multi_citation`:
   - **Inputs:** HTML draft citing `https://example.com/art-1` 4 times in different paragraphs.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py", draft_path, "--json"])`.
   - **Assertions:** `report["targets"]` contains exactly 1 entry for `art-1` with `citation_count == 4`; server request log records exactly 1 fetch.
4. `test_f17_url_fragments_and_query_dedup`:
   - **Inputs:** CLI targets `https://example.com/art-1#comments`, `https://example.com/art-1#top`, and `https://example.com/art-1/`.
   - **Mechanism:** Deduplication loop in `check_freshness.py`.
   - **Assertions:** Trailing slashes and repeated base paths collapsed; unique audit items evaluated.
5. `test_f17_empty_inputs_boundary`:
   - **Inputs:** Run `check_freshness.py` with empty arguments, draft containing 0 hyperlinks, and dossier with 0 primary sources.
   - **Mechanism:** `self.run_python([".agents/skills/kevtemen/scripts/check_freshness.py"])`.
   - **Assertions:** Exit code `2` (usage error); stderr prints usage help or zero primary sources error.

---

### Feature 18: `check_freshness.py` Near-Midnight Diagnostics Boundaries
**Target Module:** `tests/e2e/tier2/test_f18_freshness_near_midnight_boundaries.py`  
**Test Class:** `TestF18FreshnessNearMidnightBoundaries(E2ETestCase)`  
**Decorators:** `@tier(2)`, `@feature(18)`

1. `test_f18_exact_yesterday_late_evening_boundary`:
   - **Inputs:** Target timestamp `yesterday.strftime("%Y-%m-%d") + "T23:59:59+03:00"`.
   - **Mechanism:** Call `check_freshness.check_target("t1", "url", None, "http://ex.com", explicit_dt=dt)`.
   - **Assertions:** `res["same_day"] is False`; `res["is_yesterday_late_evening"] is True`; `res["age_hours"] > 0`.
2. `test_f18_exact_start_of_day_boundary`:
   - **Inputs:** Target timestamp `today.strftime("%Y-%m-%d") + "T00:00:01+03:00"`.
   - **Mechanism:** Call `check_freshness.check_target("t2", "url", None, "http://ex.com", explicit_dt=dt)`.
   - **Assertions:** `res["same_day"] is True`; `res["is_yesterday_late_evening"] is False`; `0.0 <= res["age_hours"] <= 24.0`.
3. `test_f18_future_timestamp_negative_age_rejection`:
   - **Inputs:** Target timestamp tomorrow `(today + timedelta(days=1)).strftime("%Y-%m-%d") + "T10:00:00+03:00"`.
   - **Mechanism:** Call `check_freshness.check_target("t3", "url", None, "http://ex.com", explicit_dt=dt)`.
   - **Assertions:** `res["same_day"] is False`; `res["age_hours"] < 0`; gate fails closed.
4. `test_f18_permanent_utc3_stability`:
   - **Inputs:** Dates in July (summer, e.g. `2026-07-15T12:00:00Z`) and January (winter, e.g. `2026-01-15T12:00:00Z`).
   - **Mechanism:** `check_freshness.parse_iso_or_free(s)`.
   - **Assertions:** Both timestamps converted to Istanbul produce `hour == 15` (exact +3h offset with zero seasonal DST jitter).
5. `test_f18_leap_year_february_29`:
   - **Inputs:** Leap day `2028-02-29T12:00:00+03:00` vs non-leap day `2026-02-29T12:00:00+03:00`.
   - **Mechanism:** `check_freshness.parse_iso_or_free(s)`.
   - **Assertions:** `2028-02-29` returns valid `datetime`; `2026-02-29` returns `None`.

---

### Feature 19: `verify_facts_and_links.py` All-Paragraph Verification Boundaries
**Target Module:** `tests/e2e/tier2/test_f19_facts_all_paragraphs_boundaries.py`  
**Test Class:** `TestF19FactsAllParagraphsBoundaries(E2ETestCase)`  
**Decorators:** `@tier(2)`, `@feature(19)`

1. `test_f19_empty_draft_zero_paragraphs`:
   - **Inputs:** Draft file with empty string `""` or whitespace only `   \n\n  `.
   - **Mechanism:** `self.run_python([".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--file", empty_draft, "--dossier", dossier_path, "--json"])`.
   - **Assertions:** Exit code `1`; report `passed is False`; report notes zero outbound links.
2. `test_f19_high_volume_paragraphs_stress`:
   - **Inputs:** Draft containing 110 `<p>` tags. Paragraph 1 cites URL 1 with valid metric `45`. Paragraphs 2–104 have unlinked narrative. Paragraph 105 cites URL 1 with fabricated metric `99999`.
   - **Mechanism:** `auditor.audit_draft(draft_html, dossier, skip_network=True)`.
   - **Assertions:** `passed is False`; `url_audit` contains entry with `paragraph_index == 105`; anchoring status is `"block"`; missing number is `"99999"`.
3. `test_f19_narrative_paragraphs_without_citations`:
   - **Inputs:** Draft with 5 paragraphs. Paragraph 1 has link. Paragraphs 2–5 contain narrative sentences without attribution phrases.
   - **Mechanism:** `auditor.audit_draft(draft_html, dossier, skip_network=True)`.
   - **Assertions:** `passed is True`; `linkage` list is empty (no false R4 attribution errors).
4. `test_f19_missing_dossier_containment_failure`:
   - **Inputs:** Draft cites `https://external.com/unregistered-news` which is omitted from `dossier["sources"]`.
   - **Mechanism:** `self.run_python([".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--file", draft_path, "--dossier", dossier_path, "--json"])`.
   - **Assertions:** Exit code `1`; report `passed is False`; `containment` list flags unregistered URL under R4 chain of custody.
5. `test_f19_multiple_citations_in_single_paragraph`:
   - **Inputs:** Single paragraph containing two distinct links: `<p>Veri A <a href="URL_A">burada</a> ve veri B <a href="URL_B">burada</a> açıklandı.</p>`.
   - **Mechanism:** `auditor.audit_draft(draft_html, dossier, skip_network=True, explain=True)`.
   - **Assertions:** `url_audit` contains distinct evaluations for both URL_A and URL_B at `paragraph_index == 1`; diagnostics flag multi-link variance note.

---

### Feature 20: `verify_facts_and_links.py` Clean Number & Entity Extraction Boundaries
**Target Module:** `tests/e2e/tier2/test_f20_facts_clean_numbers_boundaries.py`  
**Test Class:** `TestF20FactsCleanNumbersBoundaries(E2ETestCase)`  
**Decorators:** `@tier(2)`, `@feature(20)`

1. `test_f20_european_vs_us_number_formatting`:
   - **Inputs:** Text A contains European `1.234,56`. Text B contains US `1,234.56`.
   - **Mechanism:** `KevrastAuditor.extract_distinctive_numbers(text)`.
   - **Assertions:** Both formats cleanly normalize without thousands commas/periods; normalized representation is comparable.
2. `test_f20_currency_symbol_isolation`:
   - **Inputs:** Paragraph with `$500`, `€1.200`, and `750₺`.
   - **Mechanism:** `auditor.extract_distinctive_numbers(text)`.
   - **Assertions:** Returns `['500', '750', '1200']`; currency symbols `$`, `€`, `₺` are stripped without corrupting numeric digits.
3. `test_f20_substring_false_positive_prevention`:
   - **Inputs:** Citing paragraph claims `"Toplam 45 bina inşa edildi"`. Target HTML page contains `"1450 bina ve 450 daire"`.
   - **Mechanism:** `auditor.anchoring(citing_text, target_html)`.
   - **Assertions:** `res["status"] == "block"`; `res["missing_numbers"] == ["45"]` (exact set membership prevents `45` matching inside `1450`).
4. `test_f20_extreme_scales_trillion`:
   - **Inputs:** Citing text `"Bütçe 1.5 trilyon lira olarak açıklandı"`. Target page has `"1500000000000 TL"`.
   - **Mechanism:** `auditor.anchoring(citing_text, target_html)`.
   - **Assertions:** Scale `1.5 trilyon` converts to `1500000000000`; `res["status"] == "pass"`.
5. `test_f20_written_percent_conversion`:
   - **Inputs:** Text `"Projeye destek yüzde yetmiş seviyesinde"`. Target HTML has `"%70"`.
   - **Mechanism:** `auditor.anchoring(citing_text, target_html)`.
   - **Assertions:** `"yüzde yetmiş"` converts to `"70%"`; `res["status"] == "pass"`.

---

### Feature 21: `verify_facts_and_links.py` Vendor Single-Segment Allowlist Boundaries
**Target Module:** `tests/e2e/tier2/test_f21_facts_vendor_allowlist_boundaries.py`  
**Test Class:** `TestF21FactsVendorAllowlistBoundaries(E2ETestCase)`  
**Decorators:** `@tier(2)`, `@feature(21)`

1. `test_f21_domain_root_strictly_rejected`:
   - **Inputs:** URL `https://github.com/` or `https://sqlite.org/` cited under `role="product"` or `role="supplementary"`.
   - **Mechanism:** `auditor.classify_precision(url, role="product")`.
   - **Assertions:** `res["precise"] is False`; `res["kind"] == "domain_root"`; domain roots always fail R2 regardless of role.
2. `test_f21_disallowed_single_segment_slugs`:
   - **Inputs:** URLs with generic single-segment paths: `https://news.com/politics`, `https://gazette.com/gundem`, `https://site.com/turkey`.
   - **Mechanism:** `auditor.classify_precision(url, role="supplementary")`.
   - **Assertions:** `res["precise"] is False`; `res["kind"] == "section_front"`; only whitelisted slugs (`download`, `releases`, etc.) pass.
3. `test_f21_role_restriction_product_supplementary`:
   - **Inputs:** URL `https://python.org/downloads` cited under `role="primary"`.
   - **Mechanism:** `auditor.classify_precision(url, role="primary")`.
   - **Assertions:** `res["precise"] is False`; `res["kind"] == "section_front"`; primary citations must be deep article pages.
4. `test_f21_post_fetch_article_ceiling_violation`:
   - **Inputs:** URL `https://vendor.com/releases` served by mock server returning HTML with 5 `<article>` cards and semantic version `v1.2.0`.
   - **Mechanism:** `auditor.audit_url(url, citing_text, role="product")`.
   - **Assertions:** `entry["precision"]["precise"] is False`; note cites `<article>` count = 5 exceeds maximum ceiling of 2.
5. `test_f21_trailing_slashes_and_query_normalization`:
   - **Inputs:** `https://sqlite.org/download/`, `https://sqlite.org/download/?v=latest`, `https://sqlite.org/download#top`.
   - **Mechanism:** `auditor.classify_precision(url, role="product")`.
   - **Assertions:** Path segments correctly parsed as single slug `"download"`; `res["precise"] is True`; `res["kind"] == "vendor_product_page"`.

---

### Feature 22: `verify_facts_and_links.py` Cross-Language Anchoring Boundaries
**Target Module:** `tests/e2e/tier2/test_f22_facts_cross_language_boundaries.py`  
**Test Class:** `TestF22FactsCrossLanguageBoundaries(E2ETestCase)`  
**Decorators:** `@tier(2)`, `@feature(22)`

1. `test_f22_unsupported_source_lang_schema`:
   - **Inputs:** Dossier JSON declaring `"source_lang": "es"` or `"source_lang": "ru"`.
   - **Mechanism:** `self.run_python([".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--validate-dossier", dossier_path])`.
   - **Assertions:** Exit code `2`; validation error flags unsupported language code not in `["tr", "en", "ku", "ar", "de", "fr"]`.
2. `test_f22_foreign_source_missing_anchors_blocked`:
   - **Inputs:** Dossier source specifies `"source_lang": "en"`, `"source_anchors": []`.
   - **Mechanism:** `auditor.anchoring("Bu bir test metnidir.", target_html, source_info={"source_lang": "en", "source_anchors": []})`.
   - **Assertions:** `res["status"] == "block"`; note highlights missing cross-language anchors.
3. `test_f22_target_missing_source_term`:
   - **Inputs:** Anchor pair: `{"source_term": "artificial intelligence", "tr_term": "yapay zeka"}`. Draft has *"yapay zeka"*, but target page HTML does NOT contain *"artificial intelligence"*.
   - **Mechanism:** `auditor.anchoring(citing_text, target_html, source_info)`.
   - **Assertions:** `res["status"] == "block"`; note specifies source term `['artificial intelligence']` missing on target page.
4. `test_f22_draft_missing_tr_term`:
   - **Inputs:** Anchor pair: `{"source_term": "quantum computing", "tr_term": "kuantum hesaplama"}`. Target page has *"quantum computing"*, but draft paragraph mentions only general terms without *"kuantum hesaplama"*.
   - **Mechanism:** `auditor.anchoring(citing_text, target_html, source_info)`.
   - **Assertions:** `res["status"] == "block"`; note specifies declared anchor term not found in citing paragraph.
5. `test_f22_multilingual_unicode_scripts`:
   - **Inputs:** Kurdish Latin and Arabic script anchor pairs: `{"source_term": "Peymangeha Kurdî", "tr_term": "Kürt Enstitüsü"}`.
   - **Mechanism:** `auditor.anchoring(citing_text, target_html, source_info)`.
   - **Assertions:** Unicode normalized word boundary search succeeds; `res["status"] == "pass"`.

---

### Feature 23: `verify_facts_and_links.py` Turkish Morphology & Attribution Boundaries
**Target Module:** `tests/e2e/tier2/test_f23_facts_turkish_morphology_boundaries.py`  
**Test Class:** `TestF23FactsTurkishMorphologyBoundaries(E2ETestCase)`  
**Decorators:** `@tier(2)`, `@feature(23)`

1. `test_f23_tr_fold_dotted_dotless_i_combining_dot`:
   - **Inputs:** `"IĞDIR"`, `"İZMİR"`, `"Di\u0307yarba\u0307kır"`, `"ÇATIŞMA"`.
   - **Mechanism:** `tr_fold(s)`.
   - **Assertions:** `tr_fold("IĞDIR") == "ığdır"`; `tr_fold("İZMİR") == "izmir"`; `\u0307` is completely stripped; no English lower() corruption.
2. `test_f23_institutional_declension_matrix`:
   - **Inputs:** Complete declension forms:
     - Dative `-ne/-na`: `"Valiliğine"`, `"Bakanlığına"`
     - Locative `-nde/-nda`: `"Belediyesinde"`, `"Kaymakamlığında"`
     - Ablative `-nden/-ndan`: `"Müdürlüğünden"`, `"Başkanlığından"`
     - Genitive `-nin/-nın/-nün`: `"Üniversitesinin"`, `"Müdürlüğünün"`
     - Accusative `-ni/-nı/-nü`: `"Odasını"`, `"Birliğini"`
   - **Mechanism:** `stem_institutional(word)`.
   - **Assertions:** Stems correctly to `"valiliği"`, `"bakanlığı"`, `"belediyesi"`, `"kaymakamlığı"`, `"müdürlüğü"`, `"başkanlığı"`, `"üniversitesi"`, `"odası"`, `"birliği"`.
3. `test_f23_curly_vs_straight_apostrophe_tokens`:
   - **Inputs:** Proper nouns with curly apostrophe: `"Valiliği’ne"`, `"Belediyesi’nden"` vs straight apostrophe: `"Valiliği'ne"`.
   - **Mechanism:** `auditor.extract_entities(text)`.
   - **Assertions:** Both apostrophe variants yield clean entity stems (`"valiliği"`, `"belediyesi"`) without trailing apostrophes or case endings.
4. `test_f23_punctuation_attached_to_entity_tokens`:
   - **Inputs:** Text `"Belediyesine, Bakanlığına; Valiliğinin: projesi onaylandı."`.
   - **Mechanism:** `auditor.extract_entities(text)`.
   - **Assertions:** Extracted entity list contains clean stems; attached punctuation (`,`, `;`, `:`) is discarded.
5. `test_f23_non_institutional_noun_preservation`:
   - **Inputs:** Words `"Diyarbakır"`, `"Kürdistan"`, `"Ahmet"`, `"Güneydoğu"`.
   - **Mechanism:** `stem_institutional(word)`.
   - **Assertions:** Words remain unmodified without spurious stem truncation.

---

## 4. Proposed File Layout under `tests/e2e/tier2/`

```
tests/e2e/tier2/
├── __init__.py
├── test_f13_editorial_http_boundaries.py          # Feature 13 (6 tests)
├── test_f14_freshness_tls_ssrf_boundaries.py      # Feature 14 (5 tests)
├── test_f15_freshness_iso_fractional_boundaries.py# Feature 15 (5 tests)
├── test_f16_freshness_tag_strip_boundaries.py     # Feature 16 (5 tests)
├── test_f17_freshness_earliest_dedup_boundaries.py# Feature 17 (5 tests)
├── test_f18_freshness_near_midnight_boundaries.py # Feature 18 (5 tests)
├── test_f19_facts_all_paragraphs_boundaries.py    # Feature 19 (5 tests)
├── test_f20_facts_clean_numbers_boundaries.py     # Feature 20 (5 tests)
├── test_f21_facts_vendor_allowlist_boundaries.py  # Feature 21 (5 tests)
├── test_f22_facts_cross_language_boundaries.py    # Feature 22 (5 tests)
└── test_f23_facts_turkish_morphology_boundaries.py# Feature 23 (5 tests)
```

**Total Modules:** 11 modules  
**Total Tier 2 Test Cases:** 56 test cases (exceeds target of 55)

---

## 5. Caveats

1. **Mock Server Loopback vs. SSRF Interaction in Tier 2**:
   - `MockHttpServer` binds to `127.0.0.1`. When tests invoke `editorial_http.fetch_html()` directly against `MockHttpServer` to test non-SSRF behaviors (e.g. redirect loops, 429 Retry-After, or HTML parsing), the test case must patch `editorial_http.validate_public_url` with `return_value=(True, "OK")`. Conversely, when testing SSRF protection explicitly, `validate_public_url` must NOT be patched.
2. **Dynamic Istanbul Date Anchoring**:
   - Tests generating mock timestamps or verifying same-day gates must compute `today = datetime.now(ZoneInfo("Europe/Istanbul")).date()` rather than hardcoding calendar dates to avoid future test bit-rot.
3. **No Network Isolation (`--no-network`)**:
   - All 56 tests are designed to execute offline using embedded `MockHttpServer` or direct function calls, ensuring 100% determinism in isolated CI/CD environments.

---

## 6. Conclusion

The boundary and corner case landscape for Features 13 through 23 has been comprehensively explored and mapped. A complete, robust blueprint of **56 test cases across 11 modules** is documented with exact inputs, mechanisms, and assertions. The test specifications strictly adhere to the project's opaque-box testing standard, dual-track quality guard, and test harness infrastructure. Implementing these blueprints in `tests/e2e/tier2/` will establish complete boundary validation for the editorial transport, same-day freshness sentinel, and fact-checking engines.

---

## 7. Verification Method

1. **Verify Existing E2E Runner and Tier 1 Baseline:**
   ```bash
   python3 tests/e2e/run_e2e_tests.py --tier 1 -f 13,14,15,16,17,18,19,20,21,22,23
   ```
   *Expected Result:* 56 tests executed, 56 passed, exit code `0`.

2. **Verify Target Script Interfaces & Help Flags:**
   ```bash
   python3 .agents/skills/kevtemen/scripts/check_freshness.py --help
   python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --help
   ```
   *Expected Result:* Clean usage output, exit code `0`.

3. **Verify Dossier Schema Validation:**
   ```bash
   python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --validate-dossier tests/e2e/fixtures/editorial/dossier_cross_lang_english_docs.json
   ```
   *Expected Result:* Validates successfully, exit code `0`.

4. **Verify Quality Guards:**
   ```bash
   python3 tools/code_separation_guard.py
   ```
   *Expected Result:* Zero violations.

5. **Invalidation Conditions:**
   - Any signature changes to `editorial_http.validate_public_url`, `fetch_html`, or `fetch_binary`.
   - Changing exit code conventions in `check_freshness.py` or `verify_facts_and_links.py`.
   - Modifying `dossier.schema.json` `source_lang` enum or `source_anchors` constraints.
