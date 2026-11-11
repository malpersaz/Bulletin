# Handoff Report: Explorer M2-IT2-2 (Domain Resolution & Unicode/IDNA Error Handling)

**Milestone:** M2 Iteration 2 (M2-IT2-2)  
**Agent:** Explorer M2-IT2-2 (Domain Resolution & Unicode/IDNA Error Handling)  
**State Folder:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_it2_2/`  
**Working Directory:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**Parent Agent:** `5c7538a8-5eae-4af0-8169-9ca427353718` (`sub_orch_m2`)  
**Date:** 2026-11-11  

---

## 1. Observation

### 1.1 Verbatim Code at Fault
In `.agents/skills/_shared/editorial_http.py`, lines 195–198:
```python
195:    try:
196:        addr_info = socket.getaddrinfo(hostname.rstrip("."), None, proto=socket.IPPROTO_TCP)
197:    except socket.gaierror as e:
198:        return False, f"Could not resolve host '{hostname}': {e}"
```

### 1.2 Crash Reproduction and Error Trace
When `validate_public_url()` is invoked with a domain label exceeding 63 characters (RFC 1035 label limit) or containing invalid IDNA characters, Python's `socket.getaddrinfo` delegates hostname encoding to `encodings.idna`. This raises `UnicodeEncodeError` (or `UnicodeError`), which is **not** caught by `except socket.gaierror:`.

Empirical execution of `tests/reproduce_m2_1_bugs.py` (Bug 4) reproduces this failure:
```
$ python3 tests/reproduce_m2_1_bugs.py
--- Bug 4: Oversized domain label (IDNA UnicodeEncodeError) ---
CONFIRMED BUG: validate_public_url crashed with UnicodeEncodeError: 'idna' codec can't encode characters in position 0-9999: label too long
```

Traceback observed when passing `"http://" + "a" * 10000 + ".com/"`:
```
Traceback (most recent call last):
  File "/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/skills/_shared/editorial_http.py", line 196, in validate_public_url
    addr_info = socket.getaddrinfo(hostname.rstrip("."), None, proto=socket.IPPROTO_TCP)
  File "/usr/lib/python3.14/socket.py", line 983, in getaddrinfo
    for res in _socket.getaddrinfo(host, port, family, type, proto, flags):
  File "/usr/lib/python3.14/encodings/idna.py", line 222, in encode
    raise UnicodeEncodeError(
UnicodeEncodeError: 'idna' codec can't encode characters in position 0-9999: label too long
```

### 1.3 Exception Hierarchy Analysis
In Python:
- `UnicodeEncodeError` → `UnicodeError` → `ValueError` → `Exception`
- `socket.gaierror` → `OSError` → `Exception`
- `socket.herror` → `OSError` → `Exception`

Because `UnicodeError` inherits from `ValueError` rather than `OSError`, catching only `socket.gaierror` leaves all IDNA encoding errors, host lookup errors (`socket.herror`), generic OS socket errors (`OSError`), and argument errors (`ValueError`) completely unhandled.

### 1.4 Downstream Pipeline Impact
In `.agents/skills/_shared/editorial_http.py`, line 435:
```python
435:    is_valid, reason = validate_public_url(url)
436:    if not is_valid:
437:        return 400, f"SSRF protection blocked URL: {reason}".encode("utf-8"), {"x-error": reason}
```
`_execute_transport()` assumes `validate_public_url(url)` adheres to its docstring contract of returning `Tuple[bool, str]`. When an unhandled `UnicodeEncodeError` escapes, it crashes `fetch_html()` and `fetch_binary()`.

In `.agents/skills/kevrast/scripts/verify_facts_and_links.py`, `audit_draft()` calls `fetch_page()` -> `editorial_http.fetch_html()`. An unhandled exception terminates the fact-checking process with exit code 1 and no JSON output, violating the editorial gate contract.

---

## 2. Logic Chain

1. **Contract Invariant**: `validate_public_url(url: str) -> Tuple[bool, str]` must never raise unhandled exceptions on untrusted user-supplied input strings; it must return `(True, "")` on success and `(False, "<reason>")` on failure.
2. **Observation (Section 1.1 & 1.2)**: `socket.getaddrinfo` performs implicit IDNA encoding on `hostname`. If `hostname` contains labels > 63 characters (RFC 1035 limit), consecutive dots (`example..com`), or invalid codepoints, `encodings.idna` raises `UnicodeEncodeError` or `UnicodeError`.
3. **Observation (Section 1.3)**: `UnicodeError` is derived from `ValueError`, completely outside the `socket.gaierror` / `OSError` tree.
4. **Resolution Failures Beyond `gaierror`**: Host lookups can also raise `socket.herror` (e.g. `HOST_NOT_FOUND`), low-level system calls can raise `OSError` (e.g. system socket descriptor exhaustion), and C-level getaddrinfo can raise `ValueError` (e.g. embedded NUL bytes).
5. **Remediation**: Broadening the exception handler in `validate_public_url()` from `except socket.gaierror as e:` to:
   ```python
   except (socket.gaierror, socket.herror, UnicodeError, OSError, ValueError) as e:
       return False, f"Could not resolve host '{hostname}': {e}"
   ```
   guarantees that all DNS resolution, IDNA codec, and OS-level socket lookup errors are trapped and cleanly returned as `(False, "Could not resolve host ...")`.
6. **Downstream Safety**: `_execute_transport()` receives `(False, ...)`, immediately returns HTTP 400 with `x-error`, and downstream consumers (e.g., `verify_facts_and_links.py`) complete execution producing structured JSON.

---

## 3. Caveats

1. **Port Parsing Independence**: `validate_public_url()` lines 191–193 contain a separate unhandled `ValueError` when accessing `parsed.port` on malformed ports (`:70000`, `:abc`, `:-1`), assigned to Explorer M2-IT2-1. Both fixes operate independently within `validate_public_url()` and must both be applied to achieve full stability.
2. **Milestone 3 Script Notice**: `.agents/skills/kevanger/scripts/process_news_image.py` (lines 79–83) also implements a local `validate_public_url()` catching only `socket.gaierror`. While `process_news_image.py` belongs to Milestone 3 scope, it should adopt the same robust tuple during M3 implementation.
3. **Public Network Resolution**: Valid public hostnames with valid IDNA characters will continue to resolve normally without alteration to the happy path.

---

## 4. Conclusion

The unhandled `UnicodeEncodeError` in `editorial_http.validate_public_url()` is confirmed and fully reproducible. It is resolved by expanding line 197's exception handler to catch `(socket.gaierror, socket.herror, UnicodeError, OSError, ValueError)`.

### Exact Code Blueprint for Worker M2:

**File**: `.agents/skills/_shared/editorial_http.py`  
**Lines**: 195–198  

```python
<<<<
    try:
        addr_info = socket.getaddrinfo(hostname.rstrip("."), None, proto=socket.IPPROTO_TCP)
    except socket.gaierror as e:
        return False, f"Could not resolve host '{hostname}': {e}"
====
    try:
        addr_info = socket.getaddrinfo(hostname.rstrip("."), None, proto=socket.IPPROTO_TCP)
    except (socket.gaierror, socket.herror, UnicodeError, OSError, ValueError) as e:
        return False, f"Could not resolve host '{hostname}': {e}"
>>>>
```

### Complementary Test Addition for `tests/test_editorial_http_stress.py`:
In `TestEditorialHttpAdversarial.test_ssrf_malformed_urls_and_port_boundaries`:
```python
        # IDNA and oversized label handling (RFC 1035 label limit)
        oversized_url = "http://" + "a" * 10000 + ".com/"
        valid, reason = eh.validate_public_url(oversized_url)
        self.assertFalse(valid)
        self.assertIn("Could not resolve host", reason)

        # Empty domain label
        empty_label_url = "http://example..com/"
        valid, reason = eh.validate_public_url(empty_label_url)
        self.assertFalse(valid)
        self.assertIn("Could not resolve host", reason)
```

---

## 5. Verification Method

### 5.1 Verification Commands
1. Run syntax compilation check:
   ```bash
   python3 -m py_compile .agents/skills/_shared/editorial_http.py
   ```
2. Run defect reproduction script:
   ```bash
   python3 tests/reproduce_m2_1_bugs.py
   ```
   *Expected after fix:* Bug 4 prints `UNEXPECTED SUCCESS: (False, "Could not resolve host ...")` instead of `CONFIRMED BUG: validate_public_url crashed with UnicodeEncodeError`.
3. Run adversarial stress suite:
   ```bash
   python3 tests/test_editorial_http_stress.py
   ```
   *Expected:* All tests pass.
4. Run full news pipeline verification:
   ```bash
   bash tools/verify_news_pipeline.sh
   ```

### 5.2 Invalidation Conditions
- If calling `validate_public_url("http://" + "a" * 10000 + ".com/")` raises `UnicodeEncodeError`, `UnicodeError`, or any unhandled exception.
- If calling `validate_public_url("http://example..com/")` raises `UnicodeEncodeError`.
- If resolving an invalid IDNA host returns an uncaught exception rather than `(False, "Could not resolve host ...")`.
