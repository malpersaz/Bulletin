# Handoff Report: Challenger M2-1 (Empirical Transport & SSRF Stress Testing)

**Milestone:** M2 (Features 13–23)  
**Agent:** Challenger M2-1 (Empirical Transport & SSRF Stress Testing)  
**State Folder:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m2_1/`  
**Working Directory:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**Parent Agent:** `5c7538a8-5eae-4af0-8169-9ca427353718` (`sub_orch_m2`)  
**Verdict:** **REJECT** (Requires remediation of unhandled exception crashes in `validate_public_url()`)  
**Date:** 2026-11-11  

---

## 1. Observation

Direct empirical stress-testing of `.agents/skills/_shared/editorial_http.py` and downstream integration with `.agents/skills/kevrast/scripts/verify_facts_and_links.py` yielded the following findings:

### 1.1 Robust Defenses Verified (Passing)
1. **SSRF IP Normalization & Evasion Resistance**:
   - Octal IP representations (`http://0177.0.0.1/`, `http://017700000001/`, `http://0300.0250.0.1/`) are resolved by `socket.getaddrinfo` to canonical IP (`127.0.0.1`, `192.168.0.1`) and correctly blocked by `validate_public_url()`.
   - Hex representations (`http://0x7f000001/`, `http://0x7f.0.0.1/`, `http://0xc0a80001/`) are resolved to canonical IP and blocked.
   - Decimal integer representations (`http://2130706433/` -> `127.0.0.1`, `http://3232235521/` -> `192.168.0.1`, `http://0/` -> `0.0.0.0`) are resolved to canonical IP and blocked.
   - Prohibited subnets and metadata IPs are blocked: `0.0.0.0`, `127.127.127.127`, `169.254.169.254`, `[::1]`, `[::ffff:127.0.0.1]`, `[fc00::1]`, `100.64.1.1` (CGNAT), `10.255.255.255`, `192.168.1.1`, `172.16.0.1`, `localhost`, and wildcard DNS rebinding domains (`127.0.0.1.nip.io`).
   - Non-HTTP schemes are rejected at entry: `file:///etc/passwd`, `gopher://127.0.0.1:6379/`, `ftp://evil.com/`, `php://filter`, `data:`, `javascript:`, `dict:`, `ldap:`.

2. **Redirect Interception & Hop Limit Enforcement**:
   - `SSRFSafeRedirectHandler.redirect_request()` intercepts redirects to `127.0.0.1`, `169.254.169.254`, `10.0.0.1`, `192.168.1.1`, `[::1]`, `[fc00::1]`, `100.64.1.1`, and `file://`, raising `urllib.error.HTTPError` with `SSRF protection blocked redirect`.
   - Bounded redirect ceiling (`max_redirections = 5` and `max_repeats = 2`) strictly terminates infinite chains at hop 6 and circular ping-pong loops at hop 5 with `HTTPError: 302 The HTTP server returned a redirect error that would lead to an infinite loop`.

3. **Retry-After Parsing & Backoff Bounding**:
   - `_parse_retry_after()` parses integer seconds (`"0"`, `"5"`, `"15"`, `"120"`) and RFC 7231 / RFC 2822 HTTP-dates (IMF-fixdate, RFC 850, asctime).
   - Negative strings (`"-5"`), non-numeric values (`"abc"`, `""`, `None`), and past dates correctly evaluate to `0.0s`.
   - Upper bound sleep in transport is clamped by `MAX_RETRY_SLEEP_SECONDS = 15.0s`.

4. **Cache Concurrency & POSIX Atomicity**:
   - Multi-threaded stress test (32 workers, concurrent shared-key updates) executed with 0 errors.
   - Multi-process stress test (8 processes, alternating shared and isolated keys) confirmed atomic POSIX replace (`os.replace`) prevents partial file reads, race conditions, or JSON corruption.
   - Cache TTL expiration, eviction, and binary serialization round-trips via base64 operate without data loss.

---

### 1.2 Vulnerabilities & Defects Identified (Failing)

#### Defect 1: Unhandled `ValueError` on Malformed or Out-of-Range Ports in `validate_public_url()`
- **File**: `.agents/skills/_shared/editorial_http.py`, lines 191–193
- **Verbatim Code**:
  ```python
  186:    hostname = parsed.hostname
  187:    if not hostname:
  188:        return False, "URL is missing hostname"
  189:
  190:    # Validate port if specified
  191:    if parsed.port is not None:
  192:        if not (1 <= parsed.port <= 65535):
  193:            return False, f"Invalid port: {parsed.port}"
  ```
- **Observed Behavior**:
  `urllib.parse.SplitResult.port` is a property that calls `int()` on the netloc port component and checks `0 <= port <= 65535`.
  Evaluating `parsed.port` on line 191 raises `ValueError` before line 192 can ever execute:
  1. For port > 65535 (`http://example.com:70000/`): raises `ValueError: Port out of range 0-65535`.
  2. For non-integer port (`http://example.com:abc/`): raises `ValueError: Port could not be cast to integer value as 'abc'`.
  3. For negative port (`http://example.com:-1/`): raises `ValueError: Port out of range 0-65535`.
- **Verbatim Error Trace**:
  ```
  Traceback (most recent call last):
    File "/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/skills/_shared/editorial_http.py", line 191, in validate_public_url
      if parsed.port is not None:
         ^^^^^^^^^^^
    File "/usr/lib/python3.14/urllib/parse.py", line 184, in port
      raise ValueError("Port out of range 0-65535")
  ValueError: Port out of range 0-65535
  ```
- **Downstream Pipeline Impact**:
  In `.agents/skills/kevrast/scripts/verify_facts_and_links.py`, `audit_draft()` calls `fetch_page()` which invokes `editorial_http.fetch_html()`.
  When an article or citing text contains a URL with an out-of-range or malformed port (e.g. `<a href="http://example.com:70000/">link</a>`), the script does NOT catch `ValueError` and terminates abruptly with an unhandled fatal traceback, failing to produce the audit report or exit cleanly:
  ```
  $ python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --text '<p>Haber <a href="http://example.com:70000/">kaynak</a> 10 kisi.</p>' --json
  Traceback (most recent call last):
    ...
    File "/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/skills/_shared/editorial_http.py", line 191, in validate_public_url
      if parsed.port is not None:
         ^^^^^^^^^^^
  ValueError: Port out of range 0-65535
  (Exit code: 1, no JSON output)
  ```

#### Defect 2: Unhandled `UnicodeEncodeError` on Oversized Domain Labels in `validate_public_url()`
- **File**: `.agents/skills/_shared/editorial_http.py`, lines 195–198
- **Verbatim Code**:
  ```python
  195:    try:
  196:        addr_info = socket.getaddrinfo(hostname.rstrip("."), None, proto=socket.IPPROTO_TCP)
  197:    except socket.gaierror as e:
  198:        return False, f"Could not resolve host '{hostname}': {e}"
  ```
- **Observed Behavior**:
  When resolving a domain label that exceeds 63 characters (RFC 1035 label limit) or contains invalid IDNA sequences (e.g. `http://` + `"a"*10000` + `.com/`), `socket.getaddrinfo()` invokes the Python IDNA codec which raises `UnicodeEncodeError`. Because line 197 only catches `socket.gaierror`, `validate_public_url()` crashes with an unhandled `UnicodeEncodeError`.
- **Verbatim Error Trace**:
  ```
  Traceback (most recent call last):
    File "/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/skills/_shared/editorial_http.py", line 196, in validate_public_url
      addr_info = socket.getaddrinfo(hostname.rstrip("."), None, proto=socket.IPPROTO_TCP)
    ...
  UnicodeEncodeError: 'idna' codec can't encode characters in position 0-9999: label too long
  ```

---

## 2. Logic Chain

1. **Contract Invariant**: The docstring and contract for `validate_public_url(url: str) -> Tuple[bool, str]` explicitly guarantees `(True, "")` on success and `(False, "<reason>")` on failure. It must never raise unhandled exceptions on invalid input strings.
2. **Crash Propagation (Observation 1.2, Defect 1)**: Because `parsed.port` property evaluation on line 191 is placed outside a `try ... except` block, any malformed port (`:70000`, `:abc`, `:-1`) raises `ValueError`.
3. **Transport Fragility**: `_execute_transport()` calls `validate_public_url(url)` without catching `ValueError`. As a result, both `fetch_html()` and `fetch_binary()` crash with an uncaught `ValueError`.
4. **Editorial Gate Denial of Service**: The fact-checking gate `verify_facts_and_links.py` does not wrap `shared_fetch_html()` in a generic exception handler. A news draft containing a malformed port URL immediately crashes `verify_facts_and_links.py` with an unhandled exception and exit code 1, destroying the JSON audit contract.
5. **IDNA Exception Leak (Observation 1.2, Defect 2)**: Python's `socket.getaddrinfo` raises `UnicodeError` / `UnicodeEncodeError` on long labels. Catching solely `socket.gaierror` leaves this exception uncaught.
6. **Remediation Requirement**: These crashes can be completely prevented with 6 lines of defensive exception handling in `validate_public_url()`. Until resolved, Milestone 2 transport cannot be certified safe for automated pipelines.

---

## 3. Caveats

1. **SSRF Filtering Core**: The core IP evaluation logic in `is_blocked_ip()` and `BLOCKED_IPV4_NETWORKS` / `BLOCKED_IPV6_NETWORKS` is robust and correctly handles all required decimal, octal, hex, IPv6-mapped, loopback, metadata, and CGNAT IPs.
2. **DNS Rebinding**: As noted by Worker M2, socket resolution precedes connection initiation in `urllib.request`. While socket pinning could be implemented, the current pre-validation plus redirect re-validation is adequate for the CMS environment once exception handling is hardened.
3. **No Code Modified**: Per the Review-Only constraint, Challenger M2-1 has not modified `.agents/skills/_shared/editorial_http.py`. All tests were written to `tests/test_editorial_http_stress.py` and `tests/reproduce_m2_1_bugs.py`.

---

## 4. Conclusion

**Verdict: REJECT.**

While the SSRF IP blocking, redirect hop interception, Retry-After backoff, and cache concurrency implementations are well-designed and empirically verified, `editorial_http.validate_public_url()` contains fatal unhandled exception bugs on malformed ports and domain labels that cause denial-of-service crashes in downstream pipeline scripts (`verify_facts_and_links.py`).

### Required Action Items for Worker M2:
1. In `validate_public_url()`, wrap `parsed.port` access in a `try ... except (ValueError, Exception)` block and return `(False, f"Invalid port: {err}")`.
2. In `validate_public_url()`, extend the `socket.getaddrinfo` exception handler to catch `(socket.gaierror, socket.herror, UnicodeError, OSError)`.

---

## 5. Verification Method

### 5.1 Run Full Adversarial Stress Suite (19 tests)
```bash
python3 tests/test_editorial_http_stress.py
```
*Expected Result:* All 19 tests pass (SSRF evasions, redirects, loops, retry-after, concurrency, caching).

### 5.2 Run Defect Reproduction Suite
```bash
python3 tests/reproduce_m2_1_bugs.py
```
*Expected Result before fix:* Outputs 5 confirmed bugs, including `ValueError: Port out of range 0-65535` and crash in `verify_facts_and_links.py`.  
*Expected Result after fix:* All calls should return `(False, "...")` and `verify_facts_and_links.py` should output clean JSON indicating the target is unreachable/invalid.

### 5.3 Invalidation Conditions
- If `validate_public_url("http://example.com:70000/")` returns `(False, ...)` without throwing `ValueError`.
- If `validate_public_url("http://example.com:abc/")` returns `(False, ...)` without throwing `ValueError`.
- If `verify_facts_and_links.py` exits with structured JSON instead of a traceback when auditing drafts citing `http://example.com:70000/`.
