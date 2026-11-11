"""
Zero-dependency embedded Python HTTP mock server for deterministic offline E2E tests.
Provides localhost HTTP server for serving mocked news HTML, images, and API responses.
"""

from __future__ import annotations

import json
import threading
from http.server import BaseHTTPRequestHandler, HTTPServer
try:
    from http.server import ThreadingHTTPServer
except ImportError:
    import socketserver
    class ThreadingHTTPServer(socketserver.ThreadingMixIn, HTTPServer):  # type: ignore
        daemon_threads = True
from typing import Any, Callable, Dict, List, Optional, Tuple, Union


class MockRouteHandler(BaseHTTPRequestHandler):
    """Internal HTTP handler delegating requests to MockHttpServer route table."""

    def log_message(self, format: str, *args: Any) -> None:
        """Suppress standard HTTP server logging to keep test console clean."""
        pass

    def do_GET(self) -> None:
        self._dispatch("GET")

    def do_POST(self) -> None:
        self._dispatch("POST")

    def do_HEAD(self) -> None:
        self._dispatch("HEAD")

    def do_PUT(self) -> None:
        self._dispatch("PUT")

    def do_DELETE(self) -> None:
        self._dispatch("DELETE")

    def do_PATCH(self) -> None:
        self._dispatch("PATCH")

    def do_OPTIONS(self) -> None:
        self._dispatch("OPTIONS")

    def _dispatch(self, method: str) -> None:
        mock_server: MockHttpServer = getattr(self.server, "mock_wrapper", None)  # type: ignore
        if mock_server is None:
            self.send_error(500, "Mock server wrapper not bound")
            return

        # Record incoming request
        content_length = int(self.headers.get("Content-Length", 0))
        body = self.rfile.read(content_length) if content_length > 0 else b""
        req_record = {
            "method": method,
            "path": self.path,
            "headers": dict(self.headers),
            "body": body,
        }
        with mock_server._lock:
            mock_server.request_history.append(req_record)

        # Match path against registered routes
        path_only = self.path.split("?")[0]
        route = None

        with mock_server._lock:
            # Check exact match with query, then path only, then prefix
            if self.path in mock_server.routes:
                route = mock_server.routes[self.path]
            elif path_only in mock_server.routes:
                route = mock_server.routes[path_only]
            else:
                for registered_path, r_data in mock_server.routes.items():
                    if r_data.get("prefix") and path_only.startswith(registered_path):
                        route = r_data
                        break

        if route is None:
            # Route not found
            self.send_response(404)
            self.send_header("Content-Type", "text/plain; charset=utf-8")
            self.end_headers()
            self.wfile.write(b"404 Not Found in MockHttpServer")
            return

        # Check if callback
        if "callback" in route and callable(route["callback"]):
            status, raw_headers, content = route["callback"](req_record)
            headers = dict(raw_headers) if raw_headers else {}
        else:
            status = route.get("status", 200)
            raw_headers = route.get("headers", {})
            headers = dict(raw_headers) if raw_headers else {}
            content = route.get("content", b"")

        if isinstance(content, str):
            content_bytes = content.encode("utf-8")
            default_ct = "text/html; charset=utf-8"
        elif isinstance(content, bytes):
            content_bytes = content
            default_ct = "application/octet-stream"
        elif isinstance(content, (dict, list)):
            content_bytes = json.dumps(content).encode("utf-8")
            default_ct = "application/json; charset=utf-8"
        else:
            content_bytes = str(content).encode("utf-8")
            default_ct = "text/plain; charset=utf-8"

        # Case-insensitive header check to avoid duplicate or conflicting headers
        headers_lower = {k.lower(): k for k in headers}
        if "content-type" not in headers_lower:
            headers["Content-Type"] = default_ct
        if "content-length" not in headers_lower:
            headers["Content-Length"] = str(len(content_bytes))

        self.send_response(status)
        for h_key, h_val in headers.items():
            self.send_header(h_key, str(h_val))
        self.end_headers()

        if method != "HEAD":
            self.wfile.write(content_bytes)


class MockHttpServer:
    """
    Embedded threaded HTTP server for mocking external network calls.
    """

    def __init__(self, host: str = "127.0.0.1", port: int = 0):
        self.host = host
        self.port = port
        self.server: Optional[Union[HTTPServer, ThreadingHTTPServer]] = None
        self.thread: Optional[threading.Thread] = None
        self.routes: Dict[str, Dict[str, Any]] = {}
        self.request_history: List[Dict[str, Any]] = []
        self._lock = threading.Lock()
        self.is_running = False

    def __enter__(self) -> "MockHttpServer":
        self.start()
        return self

    def __exit__(self, exc_type, exc_val, exc_tb) -> None:
        self.stop()

    def start(self) -> str:
        """Start the mock server in a background daemon thread."""
        if self.is_running:
            return self.base_url

        self.server = ThreadingHTTPServer((self.host, self.port), MockRouteHandler)
        self.server.daemon_threads = True
        setattr(self.server, "mock_wrapper", self)
        self.port = self.server.server_port
        self.thread = threading.Thread(target=self.server.serve_forever, daemon=True)
        self.thread.start()
        self.is_running = True
        return self.base_url

    def stop(self) -> None:
        """Stop and shutdown the HTTP server."""
        if self.server and self.is_running:
            self.server.shutdown()
            self.server.server_close()
            self.is_running = False
            self.server = None
            self.thread = None

    @property
    def base_url(self) -> str:
        """Get the base URL (e.g. http://127.0.0.1:8923)."""
        return f"http://{self.host}:{self.port}"

    def get_url(self, path: str = "/") -> str:
        """Get full URL for a specific path."""
        if not path.startswith("/"):
            path = "/" + path
        return f"{self.base_url}{path}"

    def add_route(
        self,
        path: str,
        content: Union[str, bytes, Dict[str, Any], List[Any]],
        status: int = 200,
        headers: Optional[Dict[str, str]] = None,
        prefix: bool = False
    ) -> None:
        """Register a mock route returning static content."""
        if not path.startswith("/"):
            path = "/" + path
        with self._lock:
            self.routes[path] = {
                "content": content,
                "status": status,
                "headers": dict(headers) if headers else {},
                "prefix": prefix,
            }

    def add_callback(
        self,
        path: str,
        callback: Callable[[Dict[str, Any]], Tuple[int, Dict[str, str], Union[str, bytes]]],
        prefix: bool = False
    ) -> None:
        """Register a dynamic callback route."""
        if not path.startswith("/"):
            path = "/" + path
        with self._lock:
            self.routes[path] = {
                "callback": callback,
                "prefix": prefix,
            }

    def get_requests(self, path_contains: Optional[str] = None) -> List[Dict[str, Any]]:
        """Retrieve request history, optionally filtered by path substring."""
        with self._lock:
            if path_contains is None:
                return list(self.request_history)
            return [r for r in self.request_history if path_contains in r["path"]]

    def clear(self) -> None:
        """Clear routes and request history."""
        with self._lock:
            self.routes.clear()
            self.request_history.clear()
