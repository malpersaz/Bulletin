# Handoff Report: Explorer M2-IT2-1 (Port Parsing & Validation Robustness)

**Agent:** Explorer M2-IT2-1  
**Milestone:** Milestone 2 Iteration 2 (Port Parsing & Validation Robustness)  
**State Folder:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_it2_1/`  
**Working Directory:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**Parent Agent:** `5c7538a8-5eae-4af0-8169-9ca427353718` (`sub_orch_m2`)  
**Date:** 2026-11-11  

---

## 1. Observation

Direct empirical and source code inspection of `.agents/skills/_shared/editorial_http.py`, `tests/reproduce_m2_1_bugs.py`, and Python 3.14 standard library (`urllib.parse`) yielded the following observations:

### 1.1 Vulnerable Code in `editorial_http.py`
- **File**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/skills/_shared/editorial_http.py`
- **Lines 186–194**:
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

### 1.2 Python `urllib.parse.SplitResult.port` Implementation
- In Python's standard library (`urllib.parse._NetlocResultMixinBase.port`):
  ```python
  @property
  def port(self):
      port = self._hostinfo[1]
      if port is not None:
          if port.isdigit() and port.isascii():
              port = int(port)
          else:
              raise ValueError(f"Port could not be cast to integer value as {port!r}")
          if not (0 <= port <= 65535):
              raise ValueError("Port out of range 0-65535")
      return port
  ```
- **Evaluation**: The property getter performs eager integer conversion and range checking (`0 <= port <= 65535`). If the port string in netloc is non-numeric, negative, or > 65535, accessing the `.port` property raises an immediate `ValueError`.

### 1.3 Verbatim Execution Results & Reproduction
Running `tests/reproduce_m2_1_bugs.py` confirmed 3 direct `ValueError` crashes in `validate_public_url()`:
1. **Out of range (> 65535)**:
   - URL: `http://example.com:70000/`
   - Command: `python3 -c "import sys; sys.path.insert(0, '.agents/skills/_shared'); import editorial_http as eh; eh.validate_public_url('http://example.com:70000/')"`
   - Result:
     ```
     Traceback (most recent call last):
       File "<string>", line 1, in <module>
       File "/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/skills/_shared/editorial_http.py", line 191, in validate_public_url
         if parsed.port is not None:
            ^^^^^^^^^^^
       File "/usr/lib/python3.14/urllib/parse.py", line 184, in port
         raise ValueError("Port out of range 0-65535")
     ValueError: Port out of range 0-65535
     ```
2. **Non-numeric port**:
   - URL: `http://example.com:abc/`
   - Result: `ValueError: Port could not be cast to integer value as 'abc'`
3. **Negative port**:
   - URL: `http://example.com:-1/`
   - Result: `ValueError: Port could not be cast to integer value as '-1'`

### 1.4 Downstream Impact on Editorial Pipeline
- **File**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/skills/kevrast/scripts/verify_facts_and_links.py`, line 228
  ```python
  227:        if shared_fetch_html is not None:
  228:            status, body, headers = shared_fetch_html(url, timeout=self.timeout)
  ```
- When `verify_facts_and_links.py` is invoked with a news draft containing `<a href="http://example.com:70000/">link</a>`:
  ```bash
  python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --text '<p>Son dakika: <a href="http://example.com:70000/">resmi aciklama</a> yapildi.</p>' --json
  ```
  - Return code: `1`
  - Output: Crashes with uncaught traceback (`ValueError: Port out of range 0-65535`).
  - No JSON audit report is produced, halting automated editorial gate pipelines.

---

## 2. Logic Chain

1. **Contract Requirement**: The docstring of `validate_public_url(url: str) -> Tuple[bool, str]` establishes the contract:
   - Returns `(True, "")` on success.
   - Returns `(False, "<reason>")` on failure.
   - It is an input validation guard designed to isolate external network input, meaning it must never raise unhandled exceptions on invalid input strings.
2. **Root Cause (Referencing Observation 1.1 & 1.2)**:
   - On line 191 of `editorial_http.py`, `parsed.port` is accessed without exception handling: `if parsed.port is not None:`.
   - Accessing `parsed.port` executes the property getter `_NetlocResultMixinBase.port`.
   - Because ports > 65535, non-numeric strings, or negative values trigger `raise ValueError(...)` inside `urllib.parse`, line 191 crashes immediately. Line 192 (`if not (1 <= parsed.port <= 65535):`) never executes.
3. **Propagation to Transport (Referencing Observation 1.1 & 1.4)**:
   - `_execute_transport()` calls `validate_public_url(url)` on line 435.
   - `fetch_html()` and `fetch_binary()` delegate directly to `_execute_transport()`.
   - None of these functions catch `ValueError`, so the unhandled exception propagates to the caller.
4. **Denial of Service in Editorial Fact-Checking (Referencing Observation 1.4)**:
   - `KevrastAuditor.fetch_page()` calls `shared_fetch_html(url)` expecting a status code and error header (`headers.get("x-error")`).
   - The uncaught `ValueError` terminates `verify_facts_and_links.py` with exit code 1 and no JSON output, breaking CI and editorial automation.
5. **Remediation Invariant**:
   - Accessing `parsed.port` must be wrapped in `try ... except ValueError as e:`.
   - If `ValueError` is raised, immediately return `(False, f"Invalid port: {e}")`.
   - If no exception, store the value in local variable `port` and check `if port is not None:` followed by `if not (1 <= port <= 65535): return False, f"Invalid port: {port}"`.
   - This cleanly handles port 0 (`Invalid port: 0`), out-of-range ports (`Invalid port: Port out of range 0-65535`), malformed ports (`Invalid port: Port could not be cast to integer value as 'abc'`), omitted ports (`port is None` -> valid), and standard ports (`80`, `443` -> valid).

---

## 3. Caveats

1. **Port 0 Handling**: Python's `urllib.parse` considers port 0 within `0 <= port <= 65535` and returns integer `0` without raising `ValueError`. The subsequent check `if not (1 <= port <= 65535):` correctly identifies 0 as an invalid HTTP/TCP port and returns `(False, "Invalid port: 0")`.
2. **IDNA UnicodeEncodeError Separation**: Challenger M2-1 also identified Defect 2 (`UnicodeEncodeError` in `socket.getaddrinfo` on oversized domain labels > 63 chars). This defect is located on lines 195–198 and is addressed by Explorer M2-IT2-2. The remediation for `parsed.port` formulated here is completely decoupled and will cleanly integrate with the IDNA exception handler.
3. **Read-Only Scope Compliance**: As an Explorer, no source files were altered in this turn. Empirical validation was conducted using non-destructive Python one-liners and existing test scripts.

---

## 4. Conclusion & Actionable Blueprint

### 4.1 Exact Code Remediation for Worker M2

Target file: `.agents/skills/_shared/editorial_http.py`  
Location: Lines 190–194

#### Before:
```python
    # Validate port if specified
    if parsed.port is not None:
        if not (1 <= parsed.port <= 65535):
            return False, f"Invalid port: {parsed.port}"
```

#### After:
```python
    # Validate port if specified
    try:
        port = parsed.port
    except ValueError as e:
        return False, f"Invalid port: {e}"

    if port is not None:
        if not (1 <= port <= 65535):
            return False, f"Invalid port: {port}"
```

### 4.2 Behavior Matrix Post-Fix

| Input URL | `parsed.port` Evaluation | Return Value | Status Code in `fetch_html` |
|---|---|---|---|
| `http://example.com:80/` | Evaluates to `80` | `(True, "")` | Continues to DNS check |
| `http://example.com:443/` | Evaluates to `443` | `(True, "")` | Continues to DNS check |
| `http://example.com/` | Evaluates to `None` | `(True, "")` | Continues to DNS check |
| `http://example.com:/` | Evaluates to `None` | `(True, "")` | Continues to DNS check |
| `http://example.com:0/` | Evaluates to `0` | `(False, "Invalid port: 0")` | 400 (`x-error`: "Invalid port: 0") |
| `http://example.com:70000/` | Raises `ValueError` | `(False, "Invalid port: Port out of range 0-65535")` | 400 (`x-error`: "Invalid port: Port out of range 0-65535") |
| `http://example.com:65536/` | Raises `ValueError` | `(False, "Invalid port: Port out of range 0-65535")` | 400 (`x-error`: "Invalid port: Port out of range 0-65535") |
| `http://example.com:abc/` | Raises `ValueError` | `(False, "Invalid port: Port could not be cast to integer value as 'abc'")` | 400 (`x-error`: "Invalid port: Port could not be cast to integer value as 'abc'") |
| `http://example.com:-1/` | Raises `ValueError` | `(False, "Invalid port: Port could not be cast to integer value as '-1'")` | 400 (`x-error`: "Invalid port: Port could not be cast to integer value as '-1'") |
| `http://example.com: 80/` | Raises `ValueError` | `(False, "Invalid port: Port could not be cast to integer value as ' 80'")` | 400 (`x-error`: "Invalid port: Port could not be cast to integer value as ' 80'") |

### 4.3 Unit Test Additions for `tests/test_editorial_http_stress.py`

In `TestEditorialHttpAdversarial.test_ssrf_malformed_urls_and_port_boundaries` (around line 225):
```python
        # Out-of-range, non-numeric, and negative ports must return (False, "Invalid port: ...") without raising ValueError
        invalid_port_test_cases = [
            "http://8.8.8.8:70000/",
            "http://8.8.8.8:65536/",
            "http://8.8.8.8:abc/",
            "http://8.8.8.8:-1/",
            "http://8.8.8.8: 80/",
            "http://example.com:70000/",
            "http://example.com:abc/",
            "http://example.com:-1/",
        ]
        for url in invalid_port_test_cases:
            valid, reason = eh.validate_public_url(url)
            self.assertFalse(valid, f"Expected validation failure for '{url}'")
            self.assertIn("Invalid port", reason, f"Expected 'Invalid port' in reason for '{url}', got '{reason}'")
```

---

## 5. Verification Method

### 5.1 Verification Commands
1. **Direct Port Validation Check**:
   ```bash
   python3 -c "
   import sys; sys.path.insert(0, '.agents/skills/_shared')
   import editorial_http as eh
   for u in ['http://example.com:70000/', 'http://example.com:abc/', 'http://example.com:-1/', 'http://example.com:0/']:
       valid, reason = eh.validate_public_url(u)
       assert not valid, f'Expected False for {u}'
       assert 'Invalid port' in reason, f'Expected Invalid port for {u}, got {reason}'
   print('PORT VALIDATION VERIFIED: All invalid ports returned False without exceptions.')
   "
   ```
2. **Stress Test Suite**:
   ```bash
   python3 tests/test_editorial_http_stress.py
   ```
   *Expected Result*: All tests pass with exit code 0.
3. **Bug Reproduction Script Verification**:
   ```bash
   python3 tests/reproduce_m2_1_bugs.py
   ```
   *Expected Result*: Bugs 1, 2, 3, and 5 resolve from "CONFIRMED BUG" / crash to clean validation rejections and successful JSON generation.
4. **Downstream Pipeline Execution**:
   ```bash
   python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --text '<p>Haber <a href="http://example.com:70000/">kaynak</a> 10 kisi.</p>' --json
   ```
   *Expected Result*: Returns exit code 0, emitting valid JSON with `links[0].live == false` and `links[0].note == "Invalid port: Port out of range 0-65535"`.

### 5.2 Invalidation Conditions
- Any call to `validate_public_url("http://example.com:70000/")` raises `ValueError`.
- Any call to `validate_public_url("http://example.com:abc/")` raises `ValueError`.
- `verify_facts_and_links.py` exits with code 1 and an unhandled traceback when parsing drafts with out-of-range ports.
