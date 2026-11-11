"""
Tier 1 Feature Coverage: Feature 13 (editorial_http_client).
Validates .agents/skills/_shared/editorial_http.py verified transport, SSRF protection,
two-tier caching, Retry-After backoff, and max_bytes enforcement.
"""

from __future__ import annotations

import os
import sys
from unittest.mock import patch

from harness.base import E2ETestCase, feature, tier

# Add shared skills to sys.path
SHARED_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "..", ".agents", "skills", "_shared"))
if SHARED_DIR not in sys.path:
    sys.path.insert(0, SHARED_DIR)

import editorial_http


@tier(1)
@feature(13)
class TestF13EditorialHttp(E2ETestCase):
    """Tier 1 tests for the shared editorial HTTP client."""

    def test_f13_fetch_html_happy_path(self):
        """Verify fetch_html successfully fetches HTML from server, returns 200, body, and headers."""
        self.mock_http.add_route("/news/article-1", "<html><body><h1>Test Article</h1></body></html>", status=200, headers={"Content-Type": "text/html; charset=utf-8"})
        url = self.mock_http.get_url("/news/article-1")

        with patch.object(editorial_http, "validate_public_url", return_value=(True, "OK")):
            status_code, html_body, headers = editorial_http.fetch_html(url, use_cache=False)

        self.assertEqual(status_code, 200)
        self.assertEqual(html_body, "<html><body><h1>Test Article</h1></body></html>")
        lower_headers = {k.lower(): v for k, v in headers.items()}
        self.assertIn("text/html", lower_headers.get("content-type", ""))
        self.assertEqual(headers.get("x-final-url"), url)

    def test_f13_fetch_binary_happy_path(self):
        """Verify fetch_binary downloads binary payload (e.g. WebP/JPEG) returning bytes."""
        payload = b"\x52\x49\x46\x46\x00\x00\x00\x00\x57\x45\x42\x50"
        self.mock_http.add_route("/images/hero.webp", payload, status=200, headers={"Content-Type": "image/webp"})
        url = self.mock_http.get_url("/images/hero.webp")

        with patch.object(editorial_http, "validate_public_url", return_value=(True, "OK")):
            status_code, binary_data, headers = editorial_http.fetch_binary(url, use_cache=False)

        self.assertEqual(status_code, 200)
        self.assertIsInstance(binary_data, bytes)
        self.assertEqual(binary_data, payload)
        lower_headers = {k.lower(): v for k, v in headers.items()}
        self.assertIn("image/webp", lower_headers.get("content-type", ""))

    def test_f13_ssrf_ip_blocking(self):
        """Verify SSRF guard blocks loopback, private RFC 1918, link-local, and IPv6 loopback addresses."""
        blocked_targets = [
            "http://127.0.0.1:8080/secret",
            "http://169.254.169.254/latest/meta-data/",
            "http://10.0.0.1/admin",
            "http://192.168.1.1/router",
            "http://[::1]/status",
        ]
        for target in blocked_targets:
            valid, reason = editorial_http.validate_public_url(target)
            self.assertFalse(valid, f"Target {target} must be rejected by SSRF guard")
            self.assertIn("SSRF protection", reason)

            # fetch_html must also fail-closed with HTTP 400
            status_code, body, headers = editorial_http.fetch_html(target, use_cache=False)
            self.assertEqual(status_code, 400)
            self.assertIn("SSRF protection", headers.get("x-error", ""))

    def test_f13_two_tier_caching_and_ttl(self):
        """Verify two consecutive fetch_html requests hit cache, causing only 1 server request."""
        editorial_http.clear_cache()
        self.mock_http.add_route("/cached-page", "<html>Cached</html>", status=200, headers={"Content-Type": "text/html"})
        url = self.mock_http.get_url("/cached-page")

        with patch.object(editorial_http, "validate_public_url", return_value=(True, "OK")):
            s1, b1, _ = editorial_http.fetch_html(url, use_cache=True)
            s2, b2, _ = editorial_http.fetch_html(url, use_cache=True)

        self.assertEqual(s1, 200)
        self.assertEqual(s2, 200)
        self.assertEqual(b1, b2)

        requests = self.mock_http.get_requests("/cached-page")
        self.assertEqual(len(requests), 1, "Second request must hit cache and not contact server")

        editorial_http.clear_cache()

    def test_f13_retry_after_backoff_handling(self):
        """Verify 429 Too Many Requests response with Retry-After header retries and recovers."""
        attempt = 0

        def rate_limited_cb(req_record):
            nonlocal attempt
            attempt += 1
            if attempt == 1:
                return (429, {"Retry-After": "1", "Content-Type": "text/plain"}, "Rate limited")
            else:
                return (200, {"Content-Type": "text/plain"}, "Recovered OK")

        self.mock_http.add_callback("/rate-limit", rate_limited_cb)
        url = self.mock_http.get_url("/rate-limit")

        with patch.object(editorial_http, "validate_public_url", return_value=(True, "OK")):
            status_code, body, _ = editorial_http.fetch_html(url, max_retries=2, use_cache=False)

        self.assertEqual(status_code, 200)
        self.assertEqual(body, "Recovered OK")
        self.assertEqual(attempt, 2)

    def test_f13_max_bytes_enforcement(self):
        """Verify response exceeding max_bytes limit returns status 413 Payload Too Large."""
        large_body = "A" * 5000
        self.mock_http.add_route("/large-file", large_body, status=200, headers={"Content-Type": "text/plain"})
        url = self.mock_http.get_url("/large-file")

        with patch.object(editorial_http, "validate_public_url", return_value=(True, "OK")):
            status_code, body, headers = editorial_http.fetch_html(url, max_bytes=2000, use_cache=False)

        self.assertEqual(status_code, 413)
        self.assertEqual(body, "")
        self.assertIn("exceeded maximum limit of 2000 bytes", headers.get("x-error", ""))
