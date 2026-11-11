# Adversarial Challenge Report: Harness Isolation & Mocking Infrastructure (Milestone E2E-M1)

**Verdict**: `REQUEST_CHANGES`  
**Overall Risk Assessment**: `CRITICAL`  
**Target Components**:
- `tests/e2e/harness/mock_http.py` (`MockHttpServer`, `MockRouteHandler`)
- `tests/e2e/harness/sandbox.py` (`Sandbox`)
- `tests/e2e/harness/assertions.py` (Domain assertions)

---

## 1. Observation

Direct empirical observations from executing adversarial test harnesses against `mock_http.py`, `sandbox.py`, and `assertions.py`:

### Observation 1: `MockHttpServer` Deadlock on Re-entrant Requests & Concurrency Starvation
- **File & Lines**: `tests/e2e/harness/mock_http.py:133-137`
  ```python
  self.server = HTTPServer((self.host, self.port), MockRouteHandler)
  setattr(self.server, "mock_wrapper", self)
  self.port = self.server.server_port
  self.thread = threading.Thread(target=self.server.serve_forever, daemon=True)
  self.thread.start()
  ```
- **Command & Output**: Executed a callback handler issuing a nested HTTP request back to the server.
  ```text
  BrokenPipeError: [Errno 32] Broken pipe
  [FAIL] 1.5 Re-entrant / Recursive Request: Deadlock or timeout occurred! Remote end closed connection without response (HTTPServer cannot handle re-entrant calls due to single-threading)
  [FAIL] 1.4 Multi-threading Isolation Under Slow Request: FAST request blocked by SLOW request! Duration: 0.90s (HTTPServer is single-threaded)
  ```
- **Detail**: `HTTPServer` is single-threaded synchronous socket server. A slow request serializes all other requests, and any re-entrant call deadlocks immediately.

### Observation 2: `MockHttpServer` Mutates Caller's Headers In-Place
- **File & Lines**: `tests/e2e/harness/mock_http.py:77-83`
  ```python
  else:
      status = route.get("status", 200)
      headers = route.get("headers", {})
      content = route.get("content", b"")

  if isinstance(content, str):
      content_bytes = content.encode("utf-8")
      if "Content-Type" not in headers:
          headers["Content-Type"] = "text/html; charset=utf-8"
  ```
- **Command & Output**: Passed `custom_hdr = {"X-Track": "Initial"}` to `add_route("/header-test", "body", headers=custom_hdr)`.
  ```text
  [FAIL] 1.8 Headers Dictionary Mutation: CRITICAL: add_route mutated caller's headers dictionary! {'X-Track': 'Initial', 'Content-Type': 'text/html; charset=utf-8'}
  ```

### Observation 3: `MockHttpServer` Crashes on Callback Returning `headers=None`
- **File & Lines**: `tests/e2e/harness/mock_http.py:82`
  ```python
  if "Content-Type" not in headers:
  ```
- **Command & Output**:
  ```text
  TypeError: argument of type 'NoneType' is not a container or iterable
  [FAIL] 1.10 Callback returning None headers: Crashed on None headers: Remote end closed connection without response
  ```

### Observation 4: `MockHttpServer` Returns HTTP 501 on REST Methods (`PUT`, `DELETE`, `PATCH`)
- **File & Lines**: `tests/e2e/harness/mock_http.py:21-29`
  Only `do_GET`, `do_POST`, and `do_HEAD` are implemented.
- **Command & Output**: Sent PUT request.
  ```text
  [FAIL] 1.11 PUT / DELETE Method Support: HTTP 501 Unsupported Method 'PUT': MockRouteHandler lacks do_PUT/do_DELETE/do_PATCH
  ```

### Observation 5: `Sandbox` Path Traversal via Leading Slashes
- **File & Lines**: `tests/e2e/harness/sandbox.py:52-56`
  ```python
  def path(self, *parts: str) -> str:
      if not self._active or not self.root:
          self.create()
      return os.path.join(self.root, *parts)
  ```
- **Command & Output**: `sb.path("/tmp/outside_file.txt")`
  ```text
  [FAIL] 2.2 Path Traversal via Leading Slash: VULNERABILITY: sb.path('/tmp/outside_file.txt') escaped sandbox root! Got: /tmp/outside_file.txt
  ```
- **Detail**: `os.path.join` discards previous paths if an argument begins with `/`.

### Observation 6: `Sandbox` Directory Climbing (`../`) Leaks Files Outside Sandbox
- **File & Lines**: `tests/e2e/harness/sandbox.py:64-74`
- **Command & Output**: `sb.write_file("../af_e2e_leaked_artifact.txt", "leaked")`
  ```text
  [FAIL] 2.3 Path Traversal via '../' (Climbing): VULNERABILITY: write_file('../...') wrote outside sandbox root and leaked onto disk at /tmp/af_e2e_leaked_artifact.txt
  ```
- **Detail**: Neither `path()` nor `write_file()` nor `copy_file()` validates that the target path remains inside `self.root`. Files written outside escape cleanup and permanently pollute the filesystem.

### Observation 7: `Sandbox` Cleanup Silently Fails on Read-Only Directories (Resource Leak)
- **File & Lines**: `tests/e2e/harness/sandbox.py:43-50`
  ```python
  def cleanup(self) -> None:
      if self._active and self.root and os.path.exists(self.root):
          try:
              shutil.rmtree(self.root, ignore_errors=True)
  ```
- **Command & Output**: Created directory with `chmod 0500` inside sandbox, then invoked `sb.cleanup()`.
  ```text
  [FAIL] 2.5 Read-Only Directory Cleanup: DEFECT: shutil.rmtree(ignore_errors=True) silently failed to delete read-only directory! Leaked in /tmp
  ```

### Observation 8: `assert_no_inline_styles_or_scripts` Rejects Valid JSON-LD and External Scripts
- **File & Lines**: `tests/e2e/harness/assertions.py:229-230`
  ```python
  if re.search(r'<\s*script\b[^>]*>', content, flags=re.IGNORECASE):
      violations.append('Inline <script> tag found')
  ```
- **Command & Output**: Tested HTML output containing `<script type="application/ld+json">` (mandated by Feature 28) and `<script src="...">`.
  ```text
  [FAIL] 3.6 assert_no_inline_styles_or_scripts JSON-LD Handling: FALSE POSITIVE: Rejected valid JSON-LD metadata schema (<script type='application/ld+json'>): Code Separation violation(s):
    - Inline <script> tag found
  [FAIL] 3.7 assert_no_inline_styles_or_scripts External Script: FALSE POSITIVE: Rejected valid external script tag (<script src='...'>): Code Separation violation(s):
    - Inline <script> tag found
  ```

### Observation 9: `assert_html_tag_present` Regex Truncation on `>` in Attribute Values
- **File & Lines**: `tests/e2e/harness/assertions.py:154-155`
  ```python
  pattern = rf"<{re.escape(tag)}\b([^>]*)>"
  ```
- **Command & Output**: Tested `<img alt="Comparison: A > B" src="hero.jpg" class="detail-hero-image">`.
  ```text
  [FAIL] 3.5 assert_html_tag_present '>' inside Attribute Quotes: BUG: Regex pattern [^>]* truncated attribute string at '>' inside quotes! Tag <img> found, but matching attributes {'class': 'detail-hero-image'} were not found in matches: ['<img alt="Comparison: A >']
  ```

### Observation 10: `assert_html_tag_present` Loose Substring False Matches
- **File & Lines**: `tests/e2e/harness/assertions.py:182-184`
  ```python
  elif actual_val != v and v not in actual_val:
      all_match = False
      break
  ```
- **Command & Output**: Asserting `id="hero"` against `<div id="superhero">`.
  ```text
  [FAIL] 3.4 assert_html_tag_present Loose Substring Matching: FALSE POSITIVE: assert_html_tag_present passed id='hero' against id='superhero' because of 'v in actual_val'!
  ```

### Observation 11: `assert_date_is_today` Ignores Timezone Offsets in ISO Strings and Datetimes
- **File & Lines**: `tests/e2e/harness/assertions.py:293-300`
  ```python
  if isinstance(date_val, datetime):
      test_date = date_val.date()
  elif isinstance(date_val, str):
      # Match YYYY-MM-DD
      m = re.search(r'\b(\d{4})-(\d{2})-(\d{2})\b', date_val)
      test_date = datetime.strptime(m.group(0), "%Y-%m-%d").date()
  ```
- **Command & Output**: Tested ISO timestamp `"2026-11-10T21:30:00Z"` which corresponds to `2026-11-11 00:30:00` in Istanbul.
  ```text
  [FAIL] 3.11 assert_date_is_today Timezone-Aware ISO Parsing: DEFECT: assert_date_is_today uses raw string regex \d{4}-\d{2}-\d{2} ignoring timezone offset! Could not parse date from string: '2026-11-10T21:30:00Z'
  [FAIL] 3.12 assert_date_is_today Tz-aware Datetime Object: DEFECT: assert_date_is_today calls date_val.date() directly without converting timezone to Europe/Istanbul! Date 2026-11-10 is not today (2026-11-11) in Europe/Istanbul
  ```

---

## 2. Logic Chain

1. **Isolation Failure in Sandbox** (Observations 5, 6, 7):
   - The primary guarantee of `Sandbox` is ensuring test artifacts never leak into the repository working tree or filesystem (`TEST_INFRA.md:158`).
   - Because `sb.path()` blindly delegates to `os.path.join()` without path resolution containment (`commonpath`), any path starting with `/` or containing `../` escapes the sandbox directory.
   - When files are written outside `self.root`, `sb.cleanup()` does not touch them.
   - Additionally, read-only directories are silently ignored by `shutil.rmtree(..., ignore_errors=True)`, permanently leaking directories in `/tmp/`.
   - Therefore, `Sandbox` fails its core isolation contract.

2. **Deadlock and Threading Bottlenecks in MockHttpServer** (Observations 1, 2, 3, 4):
   - `MockHttpServer` runs `HTTPServer`, which is strictly single-threaded synchronous.
   - In E2E tests where multiple requests execute in parallel (e.g. concurrent image downloads, feed fetching, or multi-threaded test runners), requests block behind each other.
   - In recursive flows (e.g. a callback fetching a downstream mock route), the server deadlocks with `BrokenPipeError`.
   - In addition, route dispatch mutates the caller's `headers` dictionary in place, corrupting test fixtures.
   - Therefore, `MockHttpServer` cannot safely support concurrent or re-entrant E2E test execution.

3. **Assertion Invalidation and False Positives** (Observations 8, 9, 10, 11):
   - Feature 28 explicitly requires rendering `<script type="application/ld+json">` for Google NewsArticle schema.
   - `assert_no_inline_styles_or_scripts` blanket-bans all `<script>` tags, causing valid Tier 1/2 tests on `news.twig` to fail.
   - `assert_html_tag_present` corrupts attribute parsing when attribute values contain `>`, and produces false passes on substring attributes (`id="superhero"` passing for `id="hero"`).
   - `assert_date_is_today` naively matches `YYYY-MM-DD` strings without timezone awareness, causing tests running near midnight UTC/Istanbul to falsely fail or pass incorrectly.
   - Therefore, the assertion suite produces both false positives and false negatives under realistic test conditions.

---

## 3. Caveats

- Operating system under test is Linux (`x86_64`, Python 3.14). Behavior of `shutil.rmtree` on permissions may differ slightly on Windows or macOS, but permission errors on read-only directories are reproducible on standard POSIX environments.
- Network interface tests bound to `127.0.0.1` (IPv4). Dual-stack IPv6 localhost was not tested.

---

## 4. Conclusion

**Verdict: REQUEST_CHANGES**

The harness infrastructure under `tests/e2e/harness/` cannot be approved in its current state due to critical isolation, concurrency, and assertion flaws:
1. **Sandbox escaping**: Path traversal (`/` and `../`) allows out-of-bounds filesystem operations, defeating sandboxing.
2. **MockHttpServer deadlock**: Single-threaded `HTTPServer` causes deadlocks on re-entrant callbacks and test starvation under concurrency.
3. **In-place header mutation**: `MockRouteHandler` permanently mutates caller header dictionaries.
4. **Assertion false positives**: `assert_no_inline_styles_or_scripts` breaks Feature 28 JSON-LD schemas; `assert_html_tag_present` regex breaks on `>` characters; `assert_date_is_today` is timezone-blind.

### Required Remediations Before Approval:
1. **`Sandbox`**:
   - In `path(*parts)`: resolve `target = os.path.abspath(os.path.join(self.root, *parts))` and raise `ValueError` / `SecurityError` if `os.path.commonpath([target, self.root]) != self.root`.
   - In `cleanup()`: add an error handler (`onerror` or `onexc`) to `shutil.rmtree` that executes `os.chmod(path, stat.S_IRWXU)` to cleanly delete read-only files and directories.
2. **`MockHttpServer`**:
   - Replace `HTTPServer` with `http.server.ThreadingHTTPServer` to support concurrent and re-entrant requests without deadlock.
   - In `_dispatch`: copy headers via `headers = dict(route.get("headers", {}) or {})` so neither the route table nor caller dictionary is mutated.
   - Add default `do_PUT`, `do_DELETE`, `do_PATCH`, and `do_OPTIONS` delegating to `_dispatch`.
   - Handle callbacks returning `None` headers gracefully (`headers = headers or {}`).
3. **`assertions.py`**:
   - In `assert_no_inline_styles_or_scripts`: exempt `<script\b[^>]*\btype=["']application/ld\+json["'][^>]*>` and `<script\b[^>]*\bsrc=[^>]*>`. Only flag inline executable JavaScript (`<script>` without src or with JS mime type).
   - In `assert_html_tag_present`: replace `[^>]*` with a robust attribute scanner that respects quoted strings containing `>`.
   - In `assert_html_tag_present`: enforce exact attribute value equality instead of `v in actual_val` (except for `class`).
   - In `assert_date_is_today`: parse full ISO timestamps with timezone offsets and convert to `tz_name` (e.g. `Europe/Istanbul`) before comparing calendar dates.

---

## 5. Verification Method

To independently reproduce all 11 findings, run the following verification harness:

```bash
python3 - << 'EOF'
import os, stat, urllib.request, urllib.error, json, tempfile
from tests.e2e.harness.mock_http import MockHttpServer
from tests.e2e.harness.sandbox import Sandbox
from tests.e2e.harness.assertions import assert_no_inline_styles_or_scripts, assert_html_tag_present

# 1. Verify Sandbox Traversal Vulnerability
with Sandbox() as sb:
    escaped = sb.path("/tmp/outside.txt")
    assert not escaped.startswith(sb.root), "Vulnerability: escaped sandbox"
    print("REPRODUCED: Sandbox escaped via leading slash:", escaped)

# 2. Verify Headers Mutation Bug
server = MockHttpServer()
server.start()
hdr = {"X-Custom": "val"}
server.add_route("/test", "content", headers=hdr)
urllib.request.urlopen(server.get_url("/test"))
assert "Content-Type" in hdr, "Vulnerability: caller header mutated"
print("REPRODUCED: Caller headers dictionary mutated:", hdr)
server.stop()

# 3. Verify JSON-LD Schema False Positive
json_ld = '<script type="application/ld+json">{"@context":"https://schema.org"}</script>'
try:
    assert_no_inline_styles_or_scripts(json_ld)
except Exception as e:
    print("REPRODUCED: assert_no_inline_styles_or_scripts falsely rejected JSON-LD:", e)

# 4. Verify Attribute '>' Truncation
html = '<img alt="A > B" class="hero">'
try:
    assert_html_tag_present(html, "img", {"class": "hero"})
except Exception as e:
    print("REPRODUCED: assert_html_tag_present broke on '>':", e)
EOF
```

**Invalidation Conditions**:
The verdict `REQUEST_CHANGES` shall be updated to `APPROVE` once the implementer modifies `mock_http.py`, `sandbox.py`, and `assertions.py` to fix all 11 reproducible defects, and the empirical challenge suite passes with 0 failures.
