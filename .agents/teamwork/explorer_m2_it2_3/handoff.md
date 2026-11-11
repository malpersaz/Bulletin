# Handoff Report: Downstream Robustness & Error Shielding Blueprint (M2-IT2-3)

**Milestone:** Milestone 2 Iteration 2 (Features 13–23 Remediation)  
**Agent:** Explorer M2-IT2-3 (Downstream Robustness & Error Shielding)  
**State Folder:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_it2_3/`  
**Working Directory:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**Parent Agent:** `5c7538a8-5eae-4af0-8169-9ca427353718` (`sub_orch_m2`)  
**Verdict:** **BLUEPRINT READY FOR IMPLEMENTATION**  
**Date:** 2026-11-11  

---

## 1. Observation

Direct investigation of `.agents/skills/_shared/editorial_http.py`, `.agents/skills/kevrast/scripts/verify_facts_and_links.py`, and `.agents/skills/kevtemen/scripts/check_freshness.py` empirically confirmed the crash mechanisms identified by Challenger M2-1 and revealed additional failure modes in downstream consumers.

### 1.1 Unhandled Exceptions in `editorial_http.py`

#### Obs 1.1.1: `validate_public_url()` Port Evaluation Crash
- **File & Lines**: `.agents/skills/_shared/editorial_http.py:191-193`
- **Verbatim Code**:
  ```python
  191:    if parsed.port is not None:
  192:        if not (1 <= parsed.port <= 65535):
  193:            return False, f"Invalid port: {parsed.port}"
  ```
- **Observed Behavior**: `urllib.parse.SplitResult.port` is a property that calls `int()` and asserts `0 <= port <= 65535`. Accessing `parsed.port` on line 191 raises unhandled `ValueError` before line 192 can execute:
  1. `http://example.com:70000/` -> `ValueError: Port out of range 0-65535`
  2. `http://example.com:abc/` -> `ValueError: Port could not be cast to integer value as 'abc'`
  3. `http://example.com:-1/` -> `ValueError: Port could not be cast to integer value as '-1'`

#### Obs 1.1.2: `validate_public_url()` IDNA Label Length Crash
- **File & Lines**: `.agents/skills/_shared/editorial_http.py:196-198`
- **Verbatim Code**:
  ```python
  196:    try:
  197:        addr_info = socket.getaddrinfo(hostname.rstrip("."), None, proto=socket.IPPROTO_TCP)
  198:    except socket.gaierror as e:
  199:        return False, f"Could not resolve host '{hostname}': {e}"
  ```
- **Observed Behavior**: For domain labels exceeding 63 characters (RFC 1035 limit) or invalid IDNA encodings (e.g., `http://` + `"a"*10000` + `.com/`), `socket.getaddrinfo()` invokes Python's IDNA codec which raises `UnicodeEncodeError`. Because line 198 only catches `socket.gaierror`, `validate_public_url()` crashes with an unhandled `UnicodeEncodeError`.

#### Obs 1.1.3: `validate_public_url()` IPv6 Bracket Syntax Crash
- **File & Lines**: `.agents/skills/_shared/editorial_http.py:186`
- **Verbatim Code**:
  ```python
  186:    hostname = parsed.hostname
  ```
- **Observed Behavior**: In Python 3.12+, evaluating `parsed.hostname` on URLs with malformed IPv6 brackets (e.g. `http://[invalid_ipv6]/`) raises `ValueError: 'invalid_ipv6' does not appear to be an IPv4 or IPv6 address`.

#### Obs 1.1.4: Lack of Outer Exception Shielding in `fetch_html()` and `fetch_binary()`
- **File & Lines**: `.agents/skills/_shared/editorial_http.py:500-590`
- **Observed Behavior**: Neither `fetch_html()` nor `fetch_binary()` wraps transport execution in a top-level exception handler. Any exception escaping `validate_public_url()` or `_execute_transport()` crashes the caller immediately instead of returning an error tuple `(0, "", {"error": reason})` or `(400, "", {"error": reason})`.

---

### 1.2 Downstream Fragility in `verify_facts_and_links.py`

#### Obs 1.2.1: `fetch_page()` Does Not Shield `shared_fetch_html()`
- **File & Lines**: `.agents/skills/kevrast/scripts/verify_facts_and_links.py:227-236`
- **Verbatim Code**:
  ```python
  227:        if shared_fetch_html is not None:
  228:            status, body, headers = shared_fetch_html(url, timeout=self.timeout)
  229:            result["status_code"] = status
  230:            result["final_url"] = headers.get("x-final-url") or url
  ```
- **Observed Behavior**: `shared_fetch_html` is called without `try ... except`. Any unhandled exception from the transport layer halts execution with a fatal stack trace and exit code 1, destroying the JSON audit contract:
  ```bash
  $ python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --text '<p>Haber <a href="http://example.com:70000/">kaynak</a> 10 kisi.</p>' --json
  Traceback (most recent call last):
    ...
  ValueError: Port out of range 0-65535
  ```

#### Obs 1.2.2: `classify_precision()` Crashes on Malformed IPv6 URLs
- **File & Lines**: `.agents/skills/kevrast/scripts/verify_facts_and_links.py:288-292`
- **Verbatim Code**:
  ```python
  288:    def classify_precision(self, url: str, role: Optional[str] = None) -> Dict[str, Any]:
  289:        p = urllib.parse.urlparse(url)
  ```
- **Observed Behavior**: When `url` contains malformed brackets like `http://[invalid_ipv6]/`, `urllib.parse.urlparse(url)` directly raises `ValueError: 'invalid_ipv6' does not appear to be an IPv4 or IPv6 address`, crashing `audit_draft()` before any network check can run.

#### Obs 1.2.3: Missing Canonical Reachability Schema Fields
- **File & Lines**: `.agents/skills/kevrast/scripts/verify_facts_and_links.py:222-225, 524-530`
- **Observed Behavior**: `fetch_page()` and `audit_url()` currently only populate `"live": False` and `"note": "<reason>"`. They lack explicit `"reachable": False` and `"unreachable_reason": "<reason>"` keys, creating an inconsistent diagnostic contract across tools.

---

### 1.3 Downstream Diagnostic Failure in `check_freshness.py`

#### Obs 1.3.1: `fetch_html()` Silently Swallows `status == 0` Transport Errors
- **File & Lines**: `.agents/skills/kevtemen/scripts/check_freshness.py:361-368`
- **Verbatim Code**:
  ```python
  361: def fetch_html(url: str, timeout: int) -> str:
  362:     """Fetch HTML using verified shared transport layer or secure fallback."""
  363:     if editorial_http is not None:
  364:         status, html, headers = editorial_http.fetch_html(url, timeout=timeout)
  365:         if status >= 400:
  366:             err_msg = headers.get("x-error") or f"HTTP error {status}"
  367:             raise urllib.error.HTTPError(url, status, err_msg, headers, None)
  368:         return html
  ```
- **Observed Behavior**: When `editorial_http` encounters a network error (connection refused, host unreachable, timeout), it returns `status == 0, html == "", headers == {"x-error": "..."}`.
  Because line 365 only checks `status >= 400`, the condition `0 >= 400` evaluates to `False`.
  Line 368 returns `html = ""` without raising any error!
- **Downstream Diagnostic Corruption**:
  In lines 624-625:
  ```python
  html = fetch_html(raw, args.timeout)
  checks.append(check_target(raw, kind, html, raw, None, citation_count=count))
  ```
  `check_target()` receives `html = ""` (which is not `None`). It attempts to extract dates from empty string, finds none (`pub is None`), and falsely emits:
  ```json
  {
    "target": "http://public-ip-conn-refused.org/",
    "state": "undated",
    "same_day": false,
    "note": "Publication date could not be determined"
  }
  ```
  The sentinel reports `state: "undated"` instead of `state: "unreachable"`, concealing network reachability failures.

---

## 2. Logic Chain

1. **Root Cause in Transport Interface Contract (Obs 1.1.1–1.1.4)**:
   The contract for `editorial_http.validate_public_url(url: str)` is specified to return `(bool, str)`. Uncaught `ValueError` on malformed ports/hostnames and `UnicodeEncodeError` on oversized labels break this contract by escaping into `_execute_transport()` and `fetch_html()`.
2. **Defensive Boundary at Transport Layer (Requirement 1)**:
   By wrapping `parsed.port` in `try ... except (ValueError, Exception)` and expanding `getaddrinfo` exception handling to `(socket.gaierror, socket.herror, UnicodeError, OSError, Exception)`, `validate_public_url()` is guaranteed to never raise.
   Furthermore, wrapping `fetch_html()` and `fetch_binary()` in top-level `try ... except Exception as exc:` blocks ensures callers always receive a structured return tuple `(0, "", {"error": reason, "x-error": reason})` or `(400, "", {"error": reason, "x-error": reason})`.
3. **Robustness in Fact-Checking & Link Verification (Requirement 2 & Obs 1.2.1–1.2.3)**:
   In `verify_facts_and_links.py`, `classify_precision()` calls `urllib.parse.urlparse(url)` and `fetch_page()` calls `shared_fetch_html(url)`. If either call fails due to invalid syntax, the script crashes.
   Remediating `classify_precision()` to catch `(ValueError, Exception)` and `fetch_page()` to catch `Exception` ensures all malformed URLs produce clean audit entries with `reachable: False` and `unreachable_reason: "<reason>"`.
   `audit_draft()` completes gracefully, exiting with code 1 and emitting structured JSON with `passed: false, status: "BLOCKED"`.
4. **Diagnostic Integrity in Freshness Sentinel (Requirement 3 & Obs 1.3.1)**:
   `check_freshness.py`'s `fetch_html()` must treat `status < 200` (e.g. `status == 0`) as a fatal transport error, raising `urllib.error.URLError`.
   This routes execution into `except (urllib.error.URLError, OSError, ValueError, Exception) as exc:` in `main()`, which records `state: "unreachable"` with the verbatim error note, preventing false `undated` classifications.

---

## 3. Caveats

1. **Fail-Closed Gate Design**: Under this remediation blueprint, any malformed URL, unreachable host, or non-global IP results in `passed: false` and `status: "BLOCKED"`. This strictly complies with Kevtemen and Kevrast zero-hallucination, fail-closed policy.
2. **Header Dual-Key Parity**: To maintain backward compatibility with callers checking `headers.get("x-error")` and callers checking `headers.get("error")`, the remediation blueprint sets both keys in the return headers dictionary.
3. **No Production Files Modified**: Explorer M2-IT2-3 conducted a read-only investigation. All blueprints, patch definitions, and verification steps in this report are ready for implementation by the designated worker agent.

---

## 4. Conclusion & Concrete Remediation Blueprint

### 4.1 Remediation Blueprint: `.agents/skills/_shared/editorial_http.py`

#### Change 4.1.1: Harden `validate_public_url()`
**Target**: Lines 175–218
```python
def validate_public_url(url: str) -> Tuple[bool, str]:
    """
    Validate that the URL uses http/https and resolves exclusively
    to public IP addresses (SSRF guard).

    Returns:
        (True, "") on success.
        (False, "<reason>") on validation failure.
    """
    if not isinstance(url, str) or not url.strip():
        return False, "URL is empty or not a string"

    try:
        parsed = urllib.parse.urlsplit(url.strip())
        scheme = (parsed.scheme or "").lower()
        if scheme not in ("http", "https"):
            return False, f"Invalid URL scheme '{parsed.scheme}': only http and https are permitted"

        try:
            hostname = parsed.hostname
        except (ValueError, Exception) as host_err:
            return False, f"Invalid hostname in URL: {host_err}"

        if not hostname:
            return False, "URL is missing hostname"

        try:
            port = parsed.port
        except (ValueError, Exception) as port_err:
            return False, f"Invalid port: {port_err}"

        if port is not None and not (1 <= port <= 65535):
            return False, f"Invalid port: {port}"

    except Exception as e:
        return False, f"URL parsing error: {e}"

    try:
        addr_info = socket.getaddrinfo(hostname.rstrip("."), None, proto=socket.IPPROTO_TCP)
    except (socket.gaierror, socket.herror, UnicodeError, OSError, Exception) as e:
        return False, f"Could not resolve host '{hostname}': {e}"

    if not addr_info:
        return False, f"No IP addresses resolved for '{hostname}'"

    for item in addr_info:
        ip_str = item[4][0]
        try:
            ip = ipaddress.ip_address(ip_str)
        except ValueError as err:
            return False, f"Invalid IP address format '{ip_str}': {err}"

        blocked, network_str = is_blocked_ip(ip)
        if blocked:
            return (
                False,
                f"SSRF protection: host '{hostname}' resolves to non-public IP '{ip_str}' ({network_str})",
            )

    return True, ""
```

#### Change 4.1.2: Harden `_execute_transport()`
**Target**: Lines 434–438 & 486–495
```python
def _execute_transport(
    url: str,
    timeout: int,
    max_bytes: int,
    headers: Dict[str, str],
    max_retries: int,
) -> Tuple[int, bytes, Dict[str, str]]:
    """Execute raw HTTP request with verified TLS, SSRF protection, and bounded retry."""
    try:
        is_valid, reason = validate_public_url(url)
    except Exception as exc:
        is_valid, reason = False, f"Validation exception: {exc}"

    if not is_valid:
        err_dict = {"x-error": reason, "error": reason}
        return 400, f"SSRF protection blocked URL: {reason}".encode("utf-8"), err_dict

    opener = build_secure_opener()
    attempt = 0

    while True:
        try:
            req = urllib.request.Request(url, headers=headers, method="GET")
            with opener.open(req, timeout=timeout) as resp:
                raw_data = resp.read(max_bytes + 1)
                resp_headers = dict(resp.headers.items())
                resp_headers["x-final-url"] = resp.geturl()
                status_code = resp.status if hasattr(resp, "status") else resp.getcode()

                if len(raw_data) > max_bytes:
                    err_dict = {"x-error": f"Content exceeded maximum limit of {max_bytes} bytes",
                                "error": f"Content exceeded maximum limit of {max_bytes} bytes"}
                    return 413, b"", err_dict

                return status_code, raw_data, resp_headers

        except urllib.error.HTTPError as e:
            status_code = e.code
            resp_headers = dict(e.headers.items()) if e.headers else {}

            if status_code in (429, 502, 503, 504) and attempt < max_retries:
                retry_after_raw = resp_headers.get("Retry-After") or resp_headers.get("retry-after")
                sleep_sec: Optional[float] = None
                if retry_after_raw:
                    parsed_sec = _parse_retry_after(retry_after_raw)
                    if parsed_sec > 0:
                        sleep_sec = min(parsed_sec, MAX_RETRY_SLEEP_SECONDS)
                if sleep_sec is None:
                    sleep_sec = FALLBACK_RETRY_DELAYS[min(attempt, len(FALLBACK_RETRY_DELAYS) - 1)]

                time.sleep(sleep_sec)
                attempt += 1
                continue

            try:
                err_body = e.read(max_bytes + 1)
            except Exception:
                err_body = b""

            err_detail = str(e.msg) if getattr(e, "msg", None) else f"HTTP {status_code}"
            resp_headers["x-error"] = err_detail
            resp_headers["error"] = err_detail
            return status_code, err_body, resp_headers

        except (urllib.error.URLError, socket.timeout, TimeoutError, OSError, ValueError, Exception) as e:
            if attempt < max_retries and isinstance(e, (socket.timeout, TimeoutError)):
                sleep_sec = FALLBACK_RETRY_DELAYS[min(attempt, len(FALLBACK_RETRY_DELAYS) - 1)]
                time.sleep(sleep_sec)
                attempt += 1
                continue

            err_msg = str(e)
            err_dict = {
                "x-error": f"Transport error ({e.__class__.__name__}): {err_msg}",
                "error": f"Transport error ({e.__class__.__name__}): {err_msg}",
            }
            return 0, b"", err_dict
```

#### Change 4.1.3: Top-Level Shielding in `fetch_html()` and `fetch_binary()`
**Target**: Lines 500–590
```python
def fetch_html(
    url: str,
    timeout: int = DEFAULT_TIMEOUT_HTML,
    max_bytes: int = DEFAULT_MAX_BYTES_HTML,
    headers: Optional[Dict[str, str]] = None,
    use_cache: bool = True,
    max_retries: int = DEFAULT_MAX_RETRIES,
) -> Tuple[int, str, Dict[str, str]]:
    """Fetch HTML with complete exception shielding returning (status, html, headers)."""
    try:
        if use_cache:
            cached = get_cached_response(url, ttl=DEFAULT_CACHE_TTL, is_binary=False)
            if cached is not None:
                status, content, resp_headers = cached
                return status, str(content), resp_headers

        req_headers = dict(DEFAULT_HTML_HEADERS)
        if headers:
            req_headers.update(headers)

        status_code, raw_bytes, resp_headers = _execute_transport(
            url=url,
            timeout=timeout,
            max_bytes=max_bytes,
            headers=req_headers,
            max_retries=max_retries,
        )

        decoded_html = raw_bytes.decode("utf-8", errors="replace")

        if use_cache and (200 <= status_code < 300):
            set_cached_response(
                url=url,
                status_code=status_code,
                body=decoded_html,
                headers=resp_headers,
                is_binary=False,
            )

        return status_code, decoded_html, resp_headers
    except Exception as exc:
        reason = f"Unhandled fetch_html exception ({type(exc).__name__}): {exc}"
        return 0, "", {"error": reason, "x-error": reason}


def fetch_binary(
    url: str,
    timeout: int = DEFAULT_TIMEOUT_BINARY,
    max_bytes: int = DEFAULT_MAX_BYTES_BINARY,
    headers: Optional[Dict[str, str]] = None,
    use_cache: bool = True,
    max_retries: int = DEFAULT_MAX_RETRIES,
) -> Tuple[int, bytes, Dict[str, str]]:
    """Fetch binary with complete exception shielding returning (status, bytes, headers)."""
    try:
        if use_cache:
            cached = get_cached_response(url, ttl=DEFAULT_CACHE_TTL, is_binary=True)
            if cached is not None:
                status, content, resp_headers = cached
                raw = content if isinstance(content, (bytes, bytearray)) else content.encode("utf-8")
                return status, bytes(raw), resp_headers

        req_headers = dict(DEFAULT_BINARY_HEADERS)
        if headers:
            req_headers.update(headers)

        status_code, raw_bytes, resp_headers = _execute_transport(
            url=url,
            timeout=timeout,
            max_bytes=max_bytes,
            headers=req_headers,
            max_retries=max_retries,
        )

        if use_cache and (200 <= status_code < 300):
            set_cached_response(
                url=url,
                status_code=status_code,
                body=raw_bytes,
                headers=resp_headers,
                is_binary=True,
            )

        return status_code, raw_bytes, resp_headers
    except Exception as exc:
        reason = f"Unhandled fetch_binary exception ({type(exc).__name__}): {exc}"
        return 0, b"", {"error": reason, "x-error": reason}
```

---

### 4.2 Remediation Blueprint: `.agents/skills/kevrast/scripts/verify_facts_and_links.py`

#### Change 4.2.1: Shield `classify_precision()` from Parse Exceptions
**Target**: Lines 287–292
```python
    def classify_precision(self, url: str, role: Optional[str] = None) -> Dict[str, Any]:
        try:
            p = urllib.parse.urlparse(url)
        except (ValueError, Exception) as e:
            return {
                "precise": False,
                "kind": "malformed_url",
                "note": f"Malformed URL cannot be parsed ({e})",
            }
        segments = [s for s in (p.path or "").split("/") if s]
        ...
```

#### Change 4.2.2: Harden `fetch_page()` and Add Canonical `reachable` Fields
**Target**: Lines 217–285
```python
    def fetch_page(self, url: str) -> Dict[str, Any]:
        norm = self.norm_url(url)
        if norm in self._page_cache:
            return copy.deepcopy(self._page_cache[norm])

        result: Dict[str, Any] = {
            "url": url,
            "status_code": None,
            "final_url": None,
            "html": None,
            "live": False,
            "reachable": False,
            "unreachable_reason": None,
            "note": None,
        }

        if shared_fetch_html is not None:
            try:
                status, body, headers = shared_fetch_html(url, timeout=self.timeout)
            except Exception as e:
                status = 0
                body = ""
                headers = {"error": f"Transport exception: {e}", "x-error": f"Transport exception: {e}"}

            result["status_code"] = status
            result["final_url"] = headers.get("x-final-url") or url
            if 200 <= status < 300:
                result["html"] = body
            else:
                err_msg = headers.get("error") or headers.get("x-error") or f"HTTP {status}"
                result["note"] = err_msg
                result["reachable"] = False
                result["unreachable_reason"] = err_msg
                self._page_cache[norm] = copy.deepcopy(result)
                return result
        else:
            headers = {
                "User-Agent": self.user_agent,
                "Accept": "text/html,application/xhtml+xml,*/*;q=0.8",
                "Accept-Language": "tr,en;q=0.8",
            }
            try:
                req = urllib.request.Request(url, headers=headers, method="GET")
                with urllib.request.urlopen(req, timeout=self.timeout, context=self.ctx) as resp:
                    result["status_code"] = resp.status
                    result["final_url"] = resp.geturl()
                    result["html"] = resp.read(MAX_BYTES).decode("utf-8", errors="replace")
            except urllib.error.HTTPError as e:
                result["status_code"] = e.code
                result["note"] = f"HTTP {e.code}"
                result["reachable"] = False
                result["unreachable_reason"] = result["note"]
                self._page_cache[norm] = copy.deepcopy(result)
                return result
            except Exception as e:
                result["note"] = f"Access/TLS error ({e.__class__.__name__}: {e}) - manual check required"
                result["reachable"] = False
                result["unreachable_reason"] = result["note"]
                self._page_cache[norm] = copy.deepcopy(result)
                return result

            if not (200 <= result["status_code"] < 300):
                result["reachable"] = False
                result["unreachable_reason"] = result["note"]
                self._page_cache[norm] = copy.deepcopy(result)
                return result

        html = result["html"] or ""
        # Soft-404 heuristics
        if SOFT_404_TITLE_RE.search(html):
            result["note"] = "soft-404: title indicates error or not-found page"
            result["reachable"] = False
            result["unreachable_reason"] = result["note"]
            self._page_cache[norm] = copy.deepcopy(result)
            return result
        if len(html.strip()) < 500:
            result["note"] = "soft-404: content too short (<500 bytes)"
            result["reachable"] = False
            result["unreachable_reason"] = result["note"]
            self._page_cache[norm] = copy.deepcopy(result)
            return result

        try:
            orig = urllib.parse.urlparse(url)
            final = urllib.parse.urlparse(result["final_url"] or url)
            orig_root = (orig.path or "").strip("/") != ""
            final_root = (final.path or "/").strip("/") == ""
            if orig_root and final_root:
                result["note"] = "soft-404: redirected to domain root/homepage"
                result["reachable"] = False
                result["unreachable_reason"] = result["note"]
                self._page_cache[norm] = copy.deepcopy(result)
                return result
        except (ValueError, Exception) as e:
            result["note"] = f"URL parse error: {e}"
            result["reachable"] = False
            result["unreachable_reason"] = result["note"]
            self._page_cache[norm] = copy.deepcopy(result)
            return result

        result["live"] = True
        result["reachable"] = True
        result["unreachable_reason"] = None
        self._page_cache[norm] = copy.deepcopy(result)
        return result
```

#### Change 4.2.3: Populate Canonical Reachability in `audit_url()`
**Target**: Lines 524–565
```python
    def audit_url(self, url: str, citing_text: Optional[str], role: Optional[str],
                  skip_network: bool = False,
                  source_info: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        entry: Dict[str, Any] = {
            "url": url,
            "role": role or "-",
            "liveness": None,
            "precision": None,
            "anchoring": None,
        }
        precision = self.classify_precision(url, role)
        entry["precision"] = precision
        if skip_network:
            entry["liveness"] = {"live": None, "reachable": None, "note": "Network check skipped (--no-network)"}
            entry["reachable"] = None
            entry["anchoring"] = {"status": "unknown", "note": "Network check skipped"}
            return entry

        try:
            page = self.fetch_page(url)
        except Exception as e:
            page = {
                "url": url,
                "status_code": 0,
                "final_url": url,
                "html": None,
                "live": False,
                "reachable": False,
                "unreachable_reason": f"Fetch error: {e}",
                "note": f"Fetch error: {e}",
            }

        entry["liveness"] = page
        entry["reachable"] = page.get("reachable", page.get("live", False))
        if not entry["reachable"]:
            entry["unreachable_reason"] = page.get("unreachable_reason") or page.get("note") or "Target unreachable"

        # Post-fetch validation for single-segment vendor pages (Feature 21)
        if page["live"] and precision.get("conditional_check_required"):
            ...
```

---

### 4.3 Remediation Blueprint: `.agents/skills/kevtemen/scripts/check_freshness.py`

#### Change 4.3.1: Enforce URLError on `status < 200` in `fetch_html()`
**Target**: Lines 361–378
```python
def fetch_html(url: str, timeout: int) -> str:
    """Fetch HTML using verified shared transport layer or secure fallback."""
    if editorial_http is not None:
        try:
            status, html, headers = editorial_http.fetch_html(url, timeout=timeout)
        except Exception as exc:
            raise urllib.error.URLError(f"Transport exception: {exc}")

        if status >= 400:
            err_msg = headers.get("error") or headers.get("x-error") or f"HTTP error {status}"
            raise urllib.error.HTTPError(url, status, err_msg, headers, None)
        if status < 200:
            err_msg = headers.get("error") or headers.get("x-error") or f"Transport error (HTTP {status})"
            raise urllib.error.URLError(err_msg)
        return html

    ctx = ssl.create_default_context()
    req = urllib.request.Request(url, headers={
        "User-Agent": USER_AGENT,
        "Accept": "text/html,application/xhtml+xml,*/*;q=0.8",
        "Accept-Language": "tr,en;q=0.8",
    })
    with urllib.request.urlopen(req, timeout=timeout, context=ctx) as resp:
        return resp.read().decode("utf-8", errors="replace")
```

#### Change 4.3.2: Universal Exception Guard in Target Verification Loop
**Target**: Lines 622–630
```python
        if kind == "url":
            try:
                html = fetch_html(raw, args.timeout)
                checks.append(check_target(raw, kind, html, raw, None, citation_count=count))
            except (urllib.error.URLError, OSError, ValueError, Exception) as exc:
                checks.append({
                    "target": raw,
                    "kind": kind,
                    "state": "unreachable",
                    "same_day": False,
                    "citation_count": count,
                    "note": f"Target unreachable ({exc})",
                })
```

---

## 5. Verification Method

### 5.1 Verification Commands
The implementing agent can independently verify the remediated code using these commands:

```bash
# Step 1: Python compilation check
python3 -m py_compile .agents/skills/_shared/editorial_http.py \
                      .agents/skills/kevrast/scripts/verify_facts_and_links.py \
                      .agents/skills/kevtemen/scripts/check_freshness.py

# Step 2: Adversarial Stress Test Suite (all 19 tests must pass)
python3 tests/test_editorial_http_stress.py

# Step 3: Challenger Bug Reproduction Script (all 5 bug tests must succeed without crash)
python3 -c "
import sys, os, subprocess, json
sys.path.insert(0, '.agents/skills/_shared')
import editorial_http as eh

# Bug 1-4: validation returns False without exception
for u in ['http://example.com:70000/', 'http://example.com:abc/', 'http://example.com:-1/', 'http://' + 'a'*10000 + '.com/', 'http://[invalid_ipv6]/']:
    val, reason = eh.validate_public_url(u)
    assert val is False, f'Expected False for {u[:30]}'
    assert len(reason) > 0, f'Expected reason for {u[:30]}'

# Bug 5: verify_facts_and_links exits cleanly with code 1 and valid JSON
draft = '<p>Son dakika: <a href=\"http://example.com:70000/\">resmi aciklama</a> yapildi 10 kisi.</p>'
res = subprocess.run(
    ['python3', '.agents/skills/kevrast/scripts/verify_facts_and_links.py', '--text', draft, '--json'],
    capture_output=True, text=True
)
assert res.returncode == 1, f'Expected exit code 1, got {res.returncode}'
data = json.loads(res.stdout)
assert data['passed'] is False
assert data['status'] == 'BLOCKED'
assert data['url_audit'][0]['reachable'] is False
assert 'Invalid port' in data['url_audit'][0]['unreachable_reason']
print('VERIFICATION: Bug 1-5 completely resolved!')
"

# Step 4: check_freshness unreachable diagnostics verification
python3 -c "
import subprocess, json
res = subprocess.run(
    ['python3', '.agents/skills/kevtemen/scripts/check_freshness.py', 'http://example.com:70000/', '--json'],
    capture_output=True, text=True
)
assert res.returncode == 1
data = json.loads(res.stdout)
assert data['passed'] is False
assert data['status'] == 'BLOCKED'
target = data['targets'][0]
assert target['state'] == 'unreachable'
assert 'Invalid port' in target['note'] or 'out of range' in target['note']
print('VERIFICATION: check_freshness unreachable diagnostics verified!')
"

# Step 5: Full verification pipeline
tools/verify_news_pipeline.sh --help
python3 tools/code_separation_guard.py
python3 tools/module_boundary_guard.py --all
```

### 5.2 Invalidation Conditions
- If `editorial_http.validate_public_url("http://example.com:70000/")` raises `ValueError`.
- If `editorial_http.validate_public_url("http://" + "a"*10000 + ".com/")` raises `UnicodeEncodeError`.
- If `verify_facts_and_links.py` produces an unhandled traceback or stderr crash when given malformed ports or domain labels.
- If `check_freshness.py` reports `state: "undated"` instead of `state: "unreachable"` on unreachable endpoints.
- If `url_audit` entries in `verify_facts_and_links.py` lack `reachable: false` and `unreachable_reason: "..."`.
