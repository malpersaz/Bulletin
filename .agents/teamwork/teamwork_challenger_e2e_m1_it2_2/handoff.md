# Adversarial Challenge Report: E2E Harness Hardening Verification (Milestone E2E-M1, Iteration 2)

**Agent**: `teamwork_challenger_e2e_m1_it2_2`  
**Verdict**: `APPROVE`  
**Overall Risk Assessment**: `LOW`  
**Target Components**:
- `tests/e2e/harness/sandbox.py` (`Sandbox`)
- `tests/e2e/harness/mock_http.py` (`MockHttpServer`, `MockRouteHandler`)
- `tests/e2e/harness/assertions.py` (`assert_no_inline_styles_or_scripts`, `assert_html_tag_present`, `assert_date_is_today`)
- `tests/e2e/harness/runner.py` (`TestRunnerEngine`, `_parse_feature_filter`, `discover_tests`)
- `tests/e2e/run_e2e_tests.py` (CLI validation, zero-match reporting)

---

## 1. Observation

Direct empirical observations from executing adversarial test suites against the remediated harness, sandbox, mock HTTP server, assertion utilities, and test runner:

### Observation 1: `Sandbox` Path Traversal Defense & Zero Filesystem Leaks
- **File & Lines**: `tests/e2e/harness/sandbox.py:88-99`
  ```python
  root = os.path.realpath(self.root)
  target = os.path.realpath(os.path.join(root, *parts))
  try:
      if os.path.commonpath([target, root]) != root:
          raise ValueError(f"Path traversal detected: {parts!r} escapes sandbox root '{root}'")
  except ValueError as err:
      raise ValueError(f"Path traversal detected: {parts!r} escapes sandbox root '{root}'") from err
  ```
- **Empirical Execution & Results**:
  1. `sb.path("../escaped.txt")` -> Raised `ValueError: Path traversal detected: ('../escaped.txt',) escapes sandbox root`
  2. `sb.path("sub", "dir", "..", "..", "..", "escaped.txt")` -> Raised `ValueError`
  3. `sb.path("/tmp/outside_artifact.txt")` -> Raised `ValueError`
  4. `sb.path("/etc/passwd")` -> Raised `ValueError`
  5. `sb.path("//tmp/outside_double.txt")` -> Raised `ValueError`
  6. Symlink pointing to `/tmp` created inside sandbox, followed by `sb.path("symlink_tmp", "escaped.txt")` -> Caught by `os.path.realpath` and raised `ValueError`
  7. `sb.write_file("../leak_write.txt", "payload")` -> Raised `ValueError`, verified zero files created on disk outside sandbox
  8. `sb.mkdir("../leak_dir")` -> Raised `ValueError`
  9. `sb.exists("../some_file")` -> Safely caught `ValueError` and returned `False`
  10. Legitimate nested path `sb.path("sub1", "sub2", "file.txt")` -> Succeeded and verified path starts with `sb.root`.

### Observation 2: `Sandbox` Teardown of Read-Only Files and Directories
- **File & Lines**: `tests/e2e/harness/sandbox.py:44-78`
- **Empirical Execution & Results**:
  1. Created nested structure `ro_parent (chmod 0500) -> ro_child (chmod 0500) -> ro_file.txt (chmod 0400)` inside sandbox.
  2. Called `sb.cleanup()`.
  3. Verified `os.path.exists(root)` evaluated to `False`. The entire read-only hierarchy was deleted with zero leftover files in `/tmp`.
  4. Created extreme `chmod 0000` directory and file inside a fresh sandbox. Called `sb.cleanup()`. Verified directory was completely removed with zero leaks.
  5. Tested context manager exception handling with `preserve_on_error=False` -> Cleaned up read-only directories on exception.
  6. Tested context manager exception handling with `preserve_on_error=True` -> Intentionally preserved sandbox for debugging.

### Observation 3: `MockHttpServer` Re-entrant Concurrency & Multi-threading
- **File & Lines**: `tests/e2e/harness/mock_http.py:11-16, 156-162`
  ```python
  self.server = ThreadingHTTPServer((self.host, self.port), MockRouteHandler)
  self.server.daemon_threads = True
  ```
- **Empirical Execution & Results**:
  1. **Level 2 Re-entrancy**: Registered route `/internal-b` returning static text. Registered callback route `/re-entrant-a` that performs `urllib.request.urlopen` against `/internal-b` and combines responses. Request to `/re-entrant-a` completed successfully in `0.003s` with zero deadlock or timeout.
  2. **Level 3 Re-entrancy**: Route 1 calls Route 2, Route 2 calls Route 3, Route 3 returns leaf payload. Request completed successfully, returning `"Step 1 (Step 2 (Step 3 Leaf Data))"`.
  3. **Concurrency Isolation**: Started slow background request sleeping `0.3s` at `/slow`. Simultaneously initiated fast request at `/fast`. Fast request finished in `0.002s` without waiting for the slow request.
  4. **Request History Under Concurrency**: Thread-safe lock `_lock` recorded all 13 concurrent and re-entrant requests accurately in `server.get_requests()`.

### Observation 4: `MockHttpServer` In-Place Header Immutability & REST Support
- **File & Lines**: `tests/e2e/harness/mock_http.py:91-125, 197-202`
- **Empirical Execution & Results**:
  1. Passed `caller_headers = {"X-Custom-Auth": "Bearer test-token-123"}` to `server.add_route("/header-test", ..., headers=caller_headers)`.
  2. Executed GET request. Received HTTP response with `Content-Type: text/html; charset=utf-8` and `X-Custom-Auth`.
  3. Inspected `caller_headers`: dictionary remained strictly unchanged (`{"X-Custom-Auth": "Bearer test-token-123"}`).
  4. Registered callback returning `None` as headers: `(200, None, "body")`. Successfully handled without `TypeError`, responding with default `Content-Type`.
  5. Verified REST methods `PUT`, `DELETE`, `PATCH`, `OPTIONS`, `HEAD`, `POST`, and `GET` all return HTTP 200 without raising HTTP 501 Unsupported Method.

### Observation 5: `assert_no_inline_styles_or_scripts` (Code Separation vs JSON-LD)
- **File & Lines**: `tests/e2e/harness/assertions.py:215-270`
- **Empirical Execution & Results**:
  1. `<script type="application/ld+json">{"@context": "https://schema.org"}</script>` -> PASSED (no violation).
  2. `<script type = "application/ld+json; charset=utf-8">...</script>` -> PASSED.
  3. `<script async defer src="/static/bundle.min.js"></script>` -> PASSED.
  4. `<script src="/static/bundle.min.js"><!-- license comment --></script>` -> PASSED.
  5. `<script src="/static/bundle.min.js">console.log("bad");</script>` -> FAILED with `E2EAssertionError: Inline JavaScript body found in external <script src="..."> tag` (correctly blocked!).
  6. `<script>alert(1)</script>` -> FAILED with `E2EAssertionError` (correctly blocked).
  7. `<script type="text/javascript">var a = 1;</script>` -> FAILED with `E2EAssertionError` (correctly blocked).
  8. `<div style="display:none">` -> FAILED with `E2EAssertionError` (correctly blocked).
  9. `<style>.hero{}</style>` -> FAILED with `E2EAssertionError` (correctly blocked).
  10. `<button onclick="doIt()">` -> FAILED with `E2EAssertionError` (correctly blocked).

### Observation 6: `assert_html_tag_present` Quoted `>` & Strict Matching
- **File & Lines**: `tests/e2e/harness/assertions.py:144-193`
- **Empirical Execution & Results**:
  1. Tested HTML snippet with `alt="Comparison: Option A > Option B"` and `attrs={"class": "detail-hero-image"}`. Quote-aware regex `rf"<{re.escape(tag)}\b((?:[^>\"']|\"[^\"]*\"|'[^']*')*?)\s*/?>"` correctly captured entire tag without premature truncation at `>`.
  2. Tested `title="Navigation: Home > News > Local"` with `attrs={"href": "/news/local"}` -> PASSED.
  3. Asserting `attrs={"id": "hero"}` against `<span id="superhero">` -> Raised `E2EAssertionError` (loose substring matching bug eliminated).
  4. Asserting `attrs={"class": "primary-hero"}` against `<img class="detail-hero-image primary-hero">` -> PASSED (class token subset matching works).
  5. Asserting `attrs={"class": "hero"}` against `class="primary-hero"` -> Raised `E2EAssertionError` (token boundary enforced).

### Observation 7: `assert_date_is_today` Timezone-Aware Evaluation
- **File & Lines**: `tests/e2e/harness/assertions.py:312-392`
- **Empirical Execution & Results**:
  1. Plain `YYYY-MM-DD` string matching today in `Europe/Istanbul` -> PASSED.
  2. ISO string with `+03:00` offset (`2026-11-11T14:53:43+03:00`) -> PASSED.
  3. ISO string with fractional seconds comma (`2026-11-11T12:30:45,123+03:00`) -> PASSED.
  4. Near-midnight rollover: `"2026-11-10T22:30:00Z"` (which is `01:30:00` on `2026-11-11` in Istanbul) -> PASSED (correctly recognized as today in Istanbul).
  5. `"2026-11-10T19:30:00Z"` (which is `22:30:00` on `2026-11-10` in Istanbul) -> Raised `E2EAssertionError: Date 2026-11-10 is not today (2026-11-11) in Europe/Istanbul`.
  6. Datetime object in UTC (`datetime(..., tzinfo=timezone.utc)`) -> Correctly converted to `Europe/Istanbul`.
  7. Future date -> Raised `E2EAssertionError`.

### Observation 8: Runner CLI Argument Validation & Zero-Match Formatting
- **File & Lines**: `tests/e2e/run_e2e_tests.py:40-84, 167-179`, `tests/e2e/harness/runner.py:64-92`
- **Empirical Execution & Results**:
  1. `python3 tests/e2e/run_e2e_tests.py --feature abc` -> Exits `2` with `Configuration/CLI Error: argument -f/--feature: Invalid feature ID 'abc'`.
  2. `python3 tests/e2e/run_e2e_tests.py --feature 1,,2` -> Exits `2` with `Empty feature token in filter expression`.
  3. `python3 tests/e2e/run_e2e_tests.py --feature 10-2` -> Exits `2` with `Inverted feature range '10-2': start cannot be greater than end`.
  4. `python3 tests/e2e/run_e2e_tests.py --feature ""` -> Exits `2`.
  5. `python3 tests/e2e/run_e2e_tests.py --tier 99 --format json` -> Exits `0` and outputs valid JSON envelope with `summary.total = 0, summary.is_success = true`.
  6. `python3 tests/e2e/run_e2e_tests.py --tier 99 --format tap` -> Exits `0` with `TAP version 13\n1..0`.
  7. `python3 tests/e2e/run_e2e_tests.py --tier 99 --format junit` -> Exits `0` with valid XML `<testsuite tests="0">`.

### Observation 9: Quality Guards & Regression Suite
- **Tool Commands & Output**:
  1. `python3 -m unittest tests/e2e/test_harness_self_verification.py`: Ran 38 tests in 3.969s. `OK`.
  2. `python3 tests/e2e/run_e2e_tests.py --tier 1 --no-network`: All Tier 1 tests passed. Exit code 0.
  3. `python3 tools/code_separation_guard.py`: `[PASS] Code Separation Guard: 0 violations found across 3 files.`
  4. `python3 tools/module_boundary_guard.py`: `✅ PASSED: No forbidden module files or bindings detected.`
  5. `python3 tools/i18n_guard.py`: `✅ 100% CLEAN! Zero violations found in audited files.`

---

## 2. Logic Chain

1. **Path Traversal & Sandboxing (Observation 1)**:
   - `Sandbox.path()` canonicalizes the sandbox root and target path using `os.path.realpath()`, and asserts `os.path.commonpath([target, root]) == root`.
   - Any path escaping via leading slashes, directory climbing (`../`), or symbolic links is intercepted before filesystem access, raising `ValueError`.
   - Empirical tests proved that `sb.write_file("../leak.txt", "data")` cannot create files outside the sandbox.
   - Therefore, the sandbox provides complete filesystem isolation.

2. **Clean Teardown Without Leaks (Observation 2)**:
   - `Sandbox.cleanup()` utilizes recursive permission elevation (`_ensure_writable_tree`) and an `onerror` handler ensuring parent directories are writable (`stat.S_IRWXU`) before deleting files.
   - Empirical tests on nested `0500` and `0000` trees confirmed that all directories and files are completely removed.
   - Therefore, sandbox execution leaves zero residual artifacts in `/tmp`.

3. **Concurrency & Re-entrancy (Observations 3 & 4)**:
   - Upgrading `MockHttpServer` to `ThreadingHTTPServer` allows concurrent request handling without socket blocking.
   - Empirical tests proved that nested requests (Level 2 and Level 3) execute in `< 0.005s` with zero deadlocks.
   - In `MockRouteHandler._dispatch`, headers are cloned with `dict(raw_headers) if raw_headers else {}` before setting defaults (`Content-Type`, `Content-Length`), completely eliminating in-place mutation of caller dictionaries.
   - All REST methods (`PUT`, `DELETE`, `PATCH`, `OPTIONS`, `HEAD`, `POST`, `GET`) are supported.
   - Therefore, `MockHttpServer` safely supports offline, concurrent, and re-entrant E2E tests.

4. **Assertion Precision & Code Separation (Observations 5, 6, 7)**:
   - `assert_no_inline_styles_or_scripts` now distinguishes JSON-LD schemas (`application/ld+json`) and empty external scripts from inline executable JavaScript. It allows Google NewsArticle metadata mandated by Feature 28 while strictly enforcing `AGENTS.md` Rule 1.
   - `assert_html_tag_present` uses a quote-aware regex that ignores `>` characters enclosed in single or double quotes, and enforces exact equality matching for non-class attributes.
   - `assert_date_is_today` parses ISO strings with timezone offsets and converts tz-aware datetimes to `Europe/Istanbul`, correctly handling near-midnight UTC rollovers.
   - Therefore, the domain assertions produce zero false positives and reliably catch invalid HTML and code separation violations.

5. **CLI Robustness (Observation 8 & 9)**:
   - Malformed `--feature` arguments fail fast with exit code `2`, preventing test filtering bypasses.
   - Filter queries matching 0 tests return exit code `0` and well-formed report envelopes.
   - All 38 self-verification tests pass, and all repository quality guards pass with 0 violations.

---

## 3. Caveats

- Operating system tested is Linux (`x86_64`, Python 3.14). All sandbox cleanup handlers and threading mechanisms utilize standard POSIX and Python standard library primitives.
- Tier 1 test coverage currently contains infrastructure smoke tests (`test_f00_infra_smoke.py`). The full suite of 36 Tier 1 tests will be constructed and challenged under Milestone E2E-M2.
- No production files outside test infrastructure were modified.

---

## 4. Conclusion

**Verdict: APPROVE**

All 11 defects previously identified across `tests/e2e/harness/sandbox.py`, `tests/e2e/harness/mock_http.py`, `tests/e2e/harness/assertions.py`, `tests/e2e/harness/runner.py`, and `tests/e2e/run_e2e_tests.py` have been independently, empirically verified and confirmed fixed.

1. **Path Traversal**: Completely blocked with `ValueError`; zero leaks on disk.
2. **Read-Only Cleanup**: Clean recursive deletion of mode `0500` and `0000` trees; zero leaks in `/tmp`.
3. **Re-entrant Concurrency**: Multi-level recursive requests execute cleanly in milliseconds without deadlock.
4. **Header Immutability**: Caller headers are strictly protected from in-place mutation; `None` headers handled safely.
5. **REST Methods**: `PUT`, `DELETE`, `PATCH`, `OPTIONS` supported.
6. **Code Separation Assertions**: Feature 28 JSON-LD metadata and external scripts permitted; inline JS and CSS strictly blocked.
7. **HTML Tag Matching**: Quote-aware attribute parsing handles `>` in quotes; strict equality matching on non-class attributes.
8. **Timezone-Aware Date Matching**: Accurate conversion of ISO strings and datetime objects to `Europe/Istanbul`, including midnight edge cases.
9. **CLI & Discovery Engine**: Strict fail-closed validation on malformed inputs; well-formed empty reports on zero matches.
10. **Repository Health**: 38/38 unit tests pass; 0 violations across all repository quality guards.

The E2E test harness and runner infrastructure are robust, leak-free, thread-safe, and approved for Milestone E2E-M1.

---

## 5. Verification Method

To independently reproduce all verification checks and findings, execute the following commands:

```bash
# 1. Run the expanded self-verification test suite (38 unit tests)
python3 -m unittest tests/e2e/test_harness_self_verification.py

# 2. Run Tier 1 offline smoke tests via the unified runner
python3 tests/e2e/run_e2e_tests.py --tier 1 --no-network

# 3. Verify fail-closed CLI validation on malformed feature argument (Exit Code: 2)
python3 tests/e2e/run_e2e_tests.py --feature abc
python3 tests/e2e/run_e2e_tests.py --feature 10-2

# 4. Verify structured zero-match reporting (Exit Code: 0, valid JSON)
python3 tests/e2e/run_e2e_tests.py --tier 99 --format json

# 5. Run repository quality guards
python3 tools/code_separation_guard.py
python3 tools/module_boundary_guard.py
python3 tools/i18n_guard.py
```

**Invalidation Conditions**:
The verdict `APPROVE` shall be invalidated if:
- `Sandbox.path("../...")` fails to raise `ValueError` or allows file creation outside `sb.root`.
- `Sandbox.cleanup()` leaves residual directories or files in `/tmp` after handling read-only directories.
- `MockHttpServer` deadlocks on a re-entrant callback request or mutates caller headers in place.
- `assert_no_inline_styles_or_scripts` rejects `<script type="application/ld+json">`.
- `test_harness_self_verification.py` has any test failures or errors.
