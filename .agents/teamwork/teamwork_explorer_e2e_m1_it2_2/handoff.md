# Investigation & Fix Strategy Report: Mock HTTP Server and Sandbox Hardening (Milestone E2E-M1, Iteration 2)

**Agent Role**: `teamwork_explorer` (Explorer 2, Iteration 2)  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_2/`  
**Working Directory**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**Investigation Scope**: Defects identified in Challenger 2 Handoff regarding `tests/e2e/harness/mock_http.py` and `tests/e2e/harness/sandbox.py`.  
**Execution Mode**: READ-ONLY analysis & precise remediation formulation.

---

## 1. Observation

Direct empirical investigation and code inspection of `tests/e2e/harness/mock_http.py` and `tests/e2e/harness/sandbox.py` confirmed four critical defects:

### Observation 1: `MockHttpServer` Deadlocks on Re-Entrant Requests and Starves Under Concurrency
- **File & Lines**: `tests/e2e/harness/mock_http.py:10`, `tests/e2e/harness/mock_http.py:133-137`
  ```python
  10: from http.server import BaseHTTPRequestHandler, HTTPServer
  ...
  133:         self.server = HTTPServer((self.host, self.port), MockRouteHandler)
  134:         setattr(self.server, "mock_wrapper", self)
  135:         self.port = self.server.server_port
  136:         self.thread = threading.Thread(target=self.server.serve_forever, daemon=True)
  137:         self.thread.start()
  ```
- **Reproduction Command**:
  ```python
  import urllib.request
  from tests.e2e.harness.mock_http import MockHttpServer

  server = MockHttpServer()
  server.start()

  def callback_a(req):
      # Callback making an HTTP call to /b on same mock server
      res = urllib.request.urlopen(server.get_url("/b"), timeout=2).read()
      return 200, {}, f"Got {res.decode('utf-8')}"

  server.add_callback("/a", callback_a)
  server.add_route("/b", "Hello from B")
  urllib.request.urlopen(server.get_url("/a"), timeout=2)
  ```
- **Empirical Output**:
  ```text
  REPRODUCED DEADLOCK: <class 'TimeoutError'> timed out
  ```
- **Root Cause**: `HTTPServer` operates synchronously on a single thread. When request `/a` executes `callback_a`, the server thread is occupied. The inner request to `/b` cannot be accepted or dispatched until `/a` finishes, resulting in an unrecoverable circular wait (deadlock). Additionally, slow requests (e.g. rate-limit simulations) block all concurrent test requests.

---

### Observation 2: `MockRouteHandler` Mutates Caller's Headers In-Place & Crashes on `None` Headers
- **File & Lines**: `tests/e2e/harness/mock_http.py:72-84`
  ```python
  72:         # Check if callback
  73:         if "callback" in route and callable(route["callback"]):
  74:             status, headers, content = route["callback"](req_record)
  75:         else:
  76:             status = route.get("status", 200)
  77:             headers = route.get("headers", {})
  78:             content = route.get("content", b"")
  79: 
  80:         if isinstance(content, str):
  81:             content_bytes = content.encode("utf-8")
  82:             if "Content-Type" not in headers:
  83:                 headers["Content-Type"] = "text/html; charset=utf-8"
  ```
- **Reproduction Command**:
  ```python
  server = MockHttpServer()
  server.start()
  hdr = {"X-Custom": "val"}
  server.add_route("/test", "content", headers=hdr)
  urllib.request.urlopen(server.get_url("/test"))
  print("Mutated header:", hdr)
  ```
- **Empirical Output**:
  ```text
  REPRODUCED: Caller headers dictionary mutated: {'X-Custom': 'val', 'Content-Type': 'text/html; charset=utf-8'}
  ```
- **Secondary Defect**: When a callback returns `(200, None, "body")`, `headers` is `None`. Line 82 (`if "Content-Type" not in headers:`) raises:
  ```text
  TypeError: argument of type 'NoneType' is not a container or iterable
  ```
  causing connection termination without an HTTP response.
- **Third Defect**: Only `do_GET`, `do_POST`, `do_HEAD` are implemented (`mock_http.py:21-29`). Standard REST methods (`PUT`, `DELETE`, `PATCH`, `OPTIONS`) return `HTTP 501 Unsupported Method`.

---

### Observation 3: `Sandbox.path()` Permits Arbitrary Path Traversal via Leading Slashes and `../` Climbing
- **File & Lines**: `tests/e2e/harness/sandbox.py:52-56`, `tests/e2e/harness/sandbox.py:64-74`
  ```python
  52:     def path(self, *parts: str) -> str:
  53:         if not self._active or not self.root:
  54:             self.create()
  55:         return os.path.join(self.root, *parts)  # type: ignore
  ...
  64:     def write_file(self, filename: str, content: Union[str, bytes], encoding: str = "utf-8") -> str:
  65:         target_path = self.path(filename)
  66:         os.makedirs(os.path.dirname(target_path), exist_ok=True)
  ```
- **Reproduction Command**:
  ```python
  with Sandbox() as sb:
      escaped_abs = sb.path("/tmp/outside.txt")
      print("Escaped via leading slash:", escaped_abs)
      escaped_climb = sb.write_file("../af_e2e_leaked_artifact.txt", "leaked")
      print("Escaped via climbing:", escaped_climb)
  ```
- **Empirical Output**:
  ```text
  Escaped via leading slash: /tmp/outside.txt
  Escaped via climbing: /tmp/af_e2e_leaked_artifact.txt
  ```
- **Root Cause**: Python's `os.path.join(a, b)` discards all previous path segments if `b` starts with `/`. Furthermore, relative directory climbing (`../`) is appended without resolving or bounding against `self.root`. As a result, operations escape the sandbox and write files directly into parent directories or root filesystems. Because `sb.cleanup()` only purges `self.root`, leaked artifacts are never cleaned up.

---

### Observation 4: `Sandbox.cleanup()` Silently Fails to Remove Read-Only Directories (POSIX Permission Cascade)
- **File & Lines**: `tests/e2e/harness/sandbox.py:43-50`
  ```python
  43:     def cleanup(self) -> None:
  44:         """Remove the sandbox directory and all its contents."""
  45:         if self._active and self.root and os.path.exists(self.root):
  46:             try:
  47:                 shutil.rmtree(self.root, ignore_errors=True)
  48:             finally:
  49:                 self._active = False
  50:                 self.root = None
  ```
- **Reproduction Command**:
  ```python
  sb = Sandbox()
  sb.create()
  ro_dir = os.path.join(sb.root, "ro_sub")
  os.makedirs(ro_dir)
  with open(os.path.join(ro_dir, "test.txt"), "w") as f:
      f.write("data")
  os.chmod(ro_dir, 0o500) # Read-only, no write permission on dir
  root_path = sb.root
  sb.cleanup()
  print("Root still exists after cleanup:", os.path.exists(root_path))
  ```
- **Empirical Output**:
  ```text
  Root still exists after cleanup: True
  REPRODUCED: Sandbox cleanup failed to remove read-only dir with files, leaked at: /tmp/af_e2e_sb_l3jmmd9f
  ```
- **Crucial POSIX Permission Discovery**:
  During testing, an essential filesystem principle was established:
  On POSIX systems, deleting a file requires write and execute permissions (`stat.S_IWUSR | stat.S_IXUSR`) on the **parent directory**, not the file itself.
  If an implementation naively attaches an `onerror` handler to `shutil.rmtree` that only does `os.chmod(path, stat.S_IRWXU)`:
  - When `path` is `/tmp/.../ro_sub/test.txt`, chmodding `test.txt` still fails with `[Errno 13] Permission denied` because `ro_sub` is non-writable!
  - When `test.txt` cannot be unlinked, `os.rmdir(ro_sub)` subsequently fails with `[Errno 39] Directory not empty`.
  - Furthermore, if a directory has mode `0000`, `shutil.rmtree`'s `os.scandir` fails before it can list child files.
  Therefore, a reliable cleanup requires a two-layer remediation: a top-down pre-chmod pass (`_ensure_writable_tree`) combined with a parent-aware error retry handler.

---

## 2. Logic Chain

1. **Mock HTTP Server Concurrency & Re-Entrancy**:
   - *Observation 1* demonstrates that `MockHttpServer`'s single-threaded event loop cannot process nested HTTP requests initiated within callbacks, causing test suites to deadlock.
   - Python's standard library provides `http.server.ThreadingHTTPServer` (derived from `socketserver.ThreadingMixIn` and `HTTPServer`), which spawns an independent thread per connection.
   - Because `MockHttpServer` already synchronizes access to `routes` and `request_history` via `self._lock = threading.Lock()`, swapping `HTTPServer` for `ThreadingHTTPServer` with `daemon_threads = True` seamlessly resolves deadlocks and concurrency bottlenecks without thread contention.

2. **Route Header State Integrity**:
   - *Observation 2* shows that `_dispatch()` assigns `headers = route.get("headers", {})` by reference and modifies it via `headers["Content-Type"] = ...`.
   - Any mutable object passed to `add_route(headers=...)` or stored in the route table is corrupted across successive requests.
   - Creating a shallow dictionary copy (`dict(raw_headers or {})`) at both route registration and request dispatch completely insulates callers and routes from mutation.
   - Guarding against `None` headers prevents `TypeError` when callbacks return `None` as the headers element.
   - Implementing `do_PUT`, `do_DELETE`, `do_PATCH`, `do_OPTIONS` ensures compliance with modern REST API testing.

3. **Filesystem Sandbox Boundary Containment**:
   - *Observation 3* reveals that `os.path.join(self.root, *parts)` drops `self.root` whenever any component starts with `/` or escapes via `../`.
   - By resolving `target = os.path.realpath(os.path.join(root, *parts))` and asserting `os.path.commonpath([target, root]) == root`, any attempt to escape `self.root` is intercepted immediately.
   - Raising `ValueError` within `path(*parts)` guarantees that `mkdir()`, `write_file()`, `read_file()`, and `copy_file()` fail-closed before any disk modification occurs.

4. **Resource Leak Prevention on Sandbox Teardown**:
   - *Observation 4* demonstrates that `shutil.rmtree(..., ignore_errors=True)` silently ignores `PermissionError`, leaking temporary directories in `/tmp`.
   - Because POSIX file deletion depends on parent directory write permissions, and directory listing depends on directory execute/read permissions, `cleanup()` must ensure that the entire directory subtree is writable (`stat.S_IRWXU`) prior to and during deletion.
   - Executing a recursive top-down permission restoration pass followed by an `onerror` handler that restores permissions on both `path` and `os.path.dirname(path)` guarantees 100% complete deletion of read-only files and directories.

---

## 3. Caveats

1. **Python Version Compatibility**:
   - `http.server.ThreadingHTTPServer` was introduced in Python 3.7. In environments running Python 3.7 through 3.14+ (the current environment is Python 3.14.4), it is a native built-in. For extreme legacy compatibility, a 3-line fallback class (`socketserver.ThreadingMixIn, HTTPServer`) provides identical semantics.
2. **`shutil.rmtree` Deprecation in Python 3.12+**:
   - In Python 3.12+, `shutil.rmtree` introduced keyword argument `onexc` while keeping `onerror`. `onerror` continues to function cleanly without raising warnings in standard test runs, but supporting both or using `onerror` ensures cross-version compatibility across Python 3.8-3.14.
3. **Cross-Drive Paths on Windows**:
   - In Windows environments, `os.path.commonpath([target, root])` raises `ValueError` if paths reside on different drive letters (e.g. `C:\` vs `D:\`). The proposed `try...except ValueError` block treats this as an out-of-sandbox traversal, which is the correct security posture.

---

## 4. Conclusion & Recommended Fix Strategy

### 4.1 Remediation Plan for `tests/e2e/harness/mock_http.py`

#### A. Architecture Changes:
1. Replace `from http.server import BaseHTTPRequestHandler, HTTPServer` with `from http.server import BaseHTTPRequestHandler, HTTPServer, ThreadingHTTPServer`.
2. Add graceful import fallback for `ThreadingHTTPServer`.
3. In `MockRouteHandler`, add `do_PUT`, `do_DELETE`, `do_PATCH`, and `do_OPTIONS`.
4. In `MockRouteHandler._dispatch`, copy headers using `dict(raw_headers or {})` and perform case-insensitive header checks.
5. In `MockHttpServer.start()`, instantiate `ThreadingHTTPServer` and configure `self.server.daemon_threads = True`.
6. In `MockHttpServer.add_route()`, store a copy of headers (`dict(headers) if headers else {}`).

#### B. Unified Diff Proposal for `tests/e2e/harness/mock_http.py`:
```diff
--- a/tests/e2e/harness/mock_http.py
+++ b/tests/e2e/harness/mock_http.py
@@ -9,7 +9,13 @@ from __future__ import annotations
 import json
 import threading
-from http.server import BaseHTTPRequestHandler, HTTPServer
+from http.server import BaseHTTPRequestHandler, HTTPServer
+try:
+    from http.server import ThreadingHTTPServer
+except ImportError:
+    import socketserver
+    class ThreadingHTTPServer(socketserver.ThreadingMixIn, HTTPServer):  # type: ignore
+        daemon_threads = True
 from typing import Any, Callable, Dict, List, Optional, Tuple, Union
 
 
@@ -28,6 +34,18 @@ class MockRouteHandler(BaseHTTPRequestHandler):
     def do_HEAD(self) -> None:
         self._dispatch("HEAD")
 
+    def do_PUT(self) -> None:
+        self._dispatch("PUT")
+
+    def do_DELETE(self) -> None:
+        self._dispatch("DELETE")
+
+    def do_PATCH(self) -> None:
+        self._dispatch("PATCH")
+
+    def do_OPTIONS(self) -> None:
+        self._dispatch("OPTIONS")
+
     def _dispatch(self, method: str) -> None:
         mock_server: MockHttpServer = getattr(self.server, "mock_wrapper", None)  # type: ignore
         if mock_server is None:
@@ -73,28 +91,37 @@ class MockRouteHandler(BaseHTTPRequestHandler):
         # Check if callback
         if "callback" in route and callable(route["callback"]):
-            status, headers, content = route["callback"](req_record)
+            status, raw_headers, content = route["callback"](req_record)
+            headers = dict(raw_headers) if raw_headers else {}
         else:
             status = route.get("status", 200)
-            headers = route.get("headers", {})
+            raw_headers = route.get("headers", {})
+            headers = dict(raw_headers) if raw_headers else {}
             content = route.get("content", b"")
 
         if isinstance(content, str):
             content_bytes = content.encode("utf-8")
-            if "Content-Type" not in headers:
-                headers["Content-Type"] = "text/html; charset=utf-8"
+            default_ct = "text/html; charset=utf-8"
         elif isinstance(content, bytes):
             content_bytes = content
-            if "Content-Type" not in headers:
-                headers["Content-Type"] = "application/octet-stream"
+            default_ct = "application/octet-stream"
         elif isinstance(content, (dict, list)):
             content_bytes = json.dumps(content).encode("utf-8")
-            if "Content-Type" not in headers:
-                headers["Content-Type"] = "application/json; charset=utf-8"
+            default_ct = "application/json; charset=utf-8"
         else:
             content_bytes = str(content).encode("utf-8")
+            default_ct = "text/plain; charset=utf-8"
+
+        # Case-insensitive header check to avoid duplicate or conflicting headers
+        headers_lower = {k.lower(): k for k in headers}
+        if "content-type" not in headers_lower:
+            headers["Content-Type"] = default_ct
+        if "content-length" not in headers_lower:
+            headers["Content-Length"] = str(len(content_bytes))
 
         self.send_response(status)
         for h_key, h_val in headers.items():
             self.send_header(h_key, str(h_val))
-        if "Content-Length" not in headers:
-            self.send_header("Content-Length", str(len(content_bytes)))
         self.end_headers()
 
         if method != "HEAD":
@@ -113,7 +140,7 @@ class MockHttpServer:
     def __init__(self, host: str = "127.0.0.1", port: int = 0):
         self.host = host
         self.port = port
-        self.server: Optional[HTTPServer] = None
+        self.server: Optional[Union[HTTPServer, ThreadingHTTPServer]] = None
         self.thread: Optional[threading.Thread] = None
         self.routes: Dict[str, Dict[str, Any]] = {}
         self.request_history: List[Dict[str, Any]] = []
@@ -132,7 +159,8 @@ class MockHttpServer:
         if self.is_running:
             return self.base_url
 
-        self.server = HTTPServer((self.host, self.port), MockRouteHandler)
+        self.server = ThreadingHTTPServer((self.host, self.port), MockRouteHandler)
+        self.server.daemon_threads = True
         setattr(self.server, "mock_wrapper", self)
         self.port = self.server.server_port
         self.thread = threading.Thread(target=self.server.serve_forever, daemon=True)
@@ -175,7 +203,7 @@ class MockHttpServer:
             self.routes[path] = {
                 "content": content,
                 "status": status,
-                "headers": headers or {},
+                "headers": dict(headers) if headers else {},
                 "prefix": prefix,
             }
```

---

### 4.2 Remediation Plan for `tests/e2e/harness/sandbox.py`

#### A. Architecture Changes:
1. Canonicalize `self.root = os.path.realpath(tempfile.mkdtemp(prefix=self.prefix))` upon creation.
2. In `path(*parts)`:
   - Compute `target = os.path.realpath(os.path.join(root, *parts))`.
   - Verify `os.path.commonpath([target, root]) == root`.
   - Raise `ValueError(f"Path traversal detected: {parts!r} escapes sandbox root '{root}'")` on violation.
3. In `exists(filename)`:
   - Catch `ValueError` and return `False`.
4. Implement `_ensure_writable_tree(top: str)` helper to recursively restore `stat.S_IRWXU` before deletion.
5. In `cleanup()`:
   - Execute `_ensure_writable_tree(self.root)`.
   - Provide `_handle_remove_readonly` error handler for `shutil.rmtree` that chmods `os.path.dirname(path)` and `path` before retrying `os.unlink` or `os.rmdir`.

#### B. Unified Diff Proposal for `tests/e2e/harness/sandbox.py`:
```diff
--- a/tests/e2e/harness/sandbox.py
+++ b/tests/e2e/harness/sandbox.py
@@ -8,6 +8,7 @@ from __future__ import annotations
 
 import os
 import shutil
+import stat
 import tempfile
 from typing import Optional, Union
 
@@ -38,15 +39,49 @@ class Sandbox:
     def create(self) -> str:
         """Create the temporary sandbox directory if not already created."""
         if not self._active:
-            self.root = tempfile.mkdtemp(prefix=self.prefix)
+            self.root = os.path.realpath(tempfile.mkdtemp(prefix=self.prefix))
             self._active = True
         return self.root  # type: ignore
 
+    @staticmethod
+    def _ensure_writable_tree(top: str) -> None:
+        """Recursively ensure top directory and all descendants are writable and searchable."""
+        try:
+            os.chmod(top, stat.S_IRWXU)
+        except Exception:
+            pass
+        if os.path.isdir(top):
+            try:
+                with os.scandir(top) as it:
+                    for entry in it:
+                        Sandbox._ensure_writable_tree(entry.path)
+            except Exception:
+                pass
+
     def cleanup(self) -> None:
-        """Remove the sandbox directory and all its contents."""
+        """Remove the sandbox directory and all its contents cleanly without permission leaks."""
         if self._active and self.root and os.path.exists(self.root):
             try:
-                shutil.rmtree(self.root, ignore_errors=True)
+                self._ensure_writable_tree(self.root)
+
+                def _handle_remove_readonly(func, path, exc_info):
+                    try:
+                        parent = os.path.dirname(path)
+                        if parent and os.path.exists(parent):
+                            os.chmod(parent, stat.S_IRWXU)
+                        os.chmod(path, stat.S_IRWXU)
+                        if os.path.isdir(path):
+                            os.rmdir(path)
+                        else:
+                            os.unlink(path)
+                    except Exception:
+                        pass
+
+                shutil.rmtree(self.root, onerror=_handle_remove_readonly)
+            except Exception:
+                try:
+                    shutil.rmtree(self.root, ignore_errors=True)
+                except Exception:
+                    pass
             finally:
                 self._active = False
                 self.root = None
@@ -53,6 +88,14 @@ class Sandbox:
     def path(self, *parts: str) -> str:
-        """Get an absolute path within the sandbox."""
+        """Get an absolute path within the sandbox, enforcing containment against traversal attacks."""
         if not self._active or not self.root:
             self.create()
-        return os.path.join(self.root, *parts)  # type: ignore
+        root = os.path.realpath(self.root)
+        target = os.path.realpath(os.path.join(root, *parts))
+        try:
+            if os.path.commonpath([target, root]) != root:
+                raise ValueError(f"Path traversal detected: {parts!r} escapes sandbox root '{root}'")
+        except ValueError as err:
+            raise ValueError(f"Path traversal detected: {parts!r} escapes sandbox root '{root}'") from err
+        return target
 
     def mkdir(self, *parts: str) -> str:
@@ -92,4 +135,7 @@ class Sandbox:
     def exists(self, filename: str) -> bool:
         """Check if a file or directory exists in the sandbox."""
-        return os.path.exists(self.path(filename))
+        try:
+            return os.path.exists(self.path(filename))
+        except ValueError:
+            return False
```

---

## 5. Verification Method

To independently verify this strategy once applied by the implementer, run the following commands:

### Command 1: Unit Test Suite
```bash
python3 -m unittest tests/e2e/test_harness_self_verification.py
```
*Expected*: All 21 self-verification unit tests pass with exit code `0`.

### Command 2: Adversarial Challenge Suite
Run the adversarial test harness targeting the Challenger 2 findings:
```bash
python3 - << 'EOF'
import os, stat, urllib.request, json
from tests.e2e.harness.mock_http import MockHttpServer
from tests.e2e.harness.sandbox import Sandbox

# 1. Verify Sandbox Traversal Defense (Leading Slash)
with Sandbox() as sb:
    try:
        sb.path("/tmp/outside.txt")
        assert False, "FAILED: Allowed path escaping via leading slash"
    except ValueError:
        print("[VERIFIED] Sandbox blocked leading slash path traversal")

# 2. Verify Sandbox Traversal Defense (Directory Climbing)
with Sandbox() as sb:
    try:
        sb.write_file("../leaked.txt", "content")
        assert False, "FAILED: Allowed write_file escaping via ../"
    except ValueError:
        print("[VERIFIED] Sandbox blocked climbing path traversal")

# 3. Verify Sandbox Read-Only Directory Cleanup (Zero Leaks)
sb_ro = Sandbox()
sb_ro.create()
saved_root = sb_ro.root
ro_dir = sb_ro.mkdir("ro_dir")
sb_ro.write_file("ro_dir/file.txt", "data")
os.chmod(sb_ro.path("ro_dir/file.txt"), stat.S_IREAD)
os.chmod(ro_dir, stat.S_IREAD | stat.S_IEXEC)
sb_ro.cleanup()
assert not os.path.exists(saved_root), f"FAILED: Leak detected at {saved_root}"
print("[VERIFIED] Sandbox cleaned up read-only subdirectories cleanly")

# 4. Verify MockHttpServer Re-entrant Concurrency (No Deadlock)
with MockHttpServer() as srv:
    def cb_reentrant(req):
        nested = urllib.request.urlopen(srv.get_url("/sub"), timeout=2).read()
        return 200, None, f"Parent got: {nested.decode()}"
    srv.add_callback("/reentrant", cb_reentrant)
    srv.add_route("/sub", "SUB_OK")
    res = urllib.request.urlopen(srv.get_url("/reentrant"), timeout=3).read()
    assert b"SUB_OK" in res
    print("[VERIFIED] MockHttpServer handled re-entrant request without deadlock")

# 5. Verify MockHttpServer Header Immutability
with MockHttpServer() as srv:
    hdr = {"X-Track": "Initial"}
    srv.add_route("/hdr", "body", headers=hdr)
    urllib.request.urlopen(srv.get_url("/hdr"))
    assert "Content-Type" not in hdr
    print("[VERIFIED] MockHttpServer preserved caller headers dictionary")

# 6. Verify MockHttpServer REST Methods
with MockHttpServer() as srv:
    srv.add_route("/rest", {"ok": True}, status=200)
    req = urllib.request.Request(srv.get_url("/rest"), method="PUT", data=b"{}")
    resp = urllib.request.urlopen(req)
    assert resp.status == 200
    print("[VERIFIED] MockHttpServer supported PUT method")

print("\nALL HARNESS HARDENING TESTS PASSED SUCCESSFULLY!")
EOF
```

### Invalidation Conditions:
This strategy is invalidated if:
1. `sb.path("/tmp/file.txt")` or `sb.path("../file.txt")` fails to raise `ValueError`.
2. A temporary directory containing read-only files (`0400`) or directories (`0500`, `0000`) remains in `/tmp` after `sb.cleanup()`.
3. A re-entrant callback on `MockHttpServer` hangs or exceeds a 2-second timeout.
4. `add_route` mutates the dictionary passed in the `headers` parameter.
