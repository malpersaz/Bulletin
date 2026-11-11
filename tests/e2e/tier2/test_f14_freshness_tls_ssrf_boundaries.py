"""
Tier 2 Boundary & Corner Cases: Feature 14 (freshness_tls_ssrf_boundaries).
Validates check_freshness.py TLS & SSRF boundary handling:
- Untrusted SSL certificates result in unreachable state and exit code 1
- Non-HTTP schemes (ftp://, gopher://, file://) fail closed
- Network timeouts result in unreachable state and exit code 1
- Empty response body results in undated state and exit code 1
- HTTP error statuses (403, 404, 500) result in unreachable state and exit code 1
"""

from __future__ import annotations

import json
import os
import sys
import urllib.error
from unittest.mock import patch

from harness.base import E2ETestCase, feature, tier

# Add paths to sys.path
SHARED_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "..", ".agents", "skills", "_shared"))
KEVTEMEN_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "..", ".agents", "skills", "kevtemen", "scripts"))
for d in (SHARED_DIR, KEVTEMEN_DIR):
    if d not in sys.path:
        sys.path.insert(0, d)

import check_freshness
import editorial_http


@tier(2)
@feature(14)
class TestF14FreshnessTlsSsrfBoundaries(E2ETestCase):
    """Tier 2 tests for check_freshness TLS and SSRF boundary handling."""

    @property
    def script_path(self) -> str:
        return os.path.join(self.project_root, ".agents", "skills", "kevtemen", "scripts", "check_freshness.py")

    def test_f14_untrusted_ssl_cert_boundary(self):
        """Untrusted SSL certificate verification failure raises URLError and marks target unreachable."""
        with patch.object(
            editorial_http,
            "fetch_html",
            return_value=(0, "", {"error": "SSLCertVerificationError: certificate verify failed"})
        ):
            with self.assertRaises(urllib.error.URLError) as ctx:
                check_freshness.fetch_html("https://untrusted-bank.example/news", timeout=5)
            self.assertIn("certificate verify failed", str(ctx.exception))

            res = check_freshness.check_target(
                "t1", "url", None, "https://untrusted-bank.example/news", explicit_dt=None
            )
            self.assertFalse(res["same_day"])
            self.assertEqual(res["state"], "unreachable")

    def test_f14_non_http_scheme_boundary(self):
        """Non-HTTP schemes fail closed with exit code 1."""
        res = self.run_python([self.script_path, "gopher://127.0.0.1/1", "--json"])
        self.assertExitCode(res, 1)
        data = json.loads(res.stdout)
        self.assertFalse(data["passed"])
        self.assertEqual(data["targets"][0]["state"], "unreachable")

    def test_f14_timeout_boundary_enforcement(self):
        """Timeout enforcement raises URLError and marks target unreachable."""
        with patch.object(
            editorial_http,
            "fetch_html",
            return_value=(0, "", {"error": "timed out"})
        ):
            with self.assertRaises(urllib.error.URLError) as ctx:
                check_freshness.fetch_html("https://slow-api.example/article", timeout=1)
            self.assertIn("timed out", str(ctx.exception).lower())

            res = check_freshness.check_target(
                "t2", "url", None, "https://slow-api.example/article", explicit_dt=None
            )
            self.assertFalse(res["same_day"])
            self.assertEqual(res["state"], "unreachable")

    def test_f14_empty_response_body_boundary(self):
        """Empty response body (0 bytes) returns undated state and fails closed."""
        empty_file = self.sandbox.path("empty_response.html")
        with open(empty_file, "w", encoding="utf-8") as f:
            f.write("")

        res = self.run_python([self.script_path, empty_file, "--json"])
        self.assertExitCode(res, 1)
        data = json.loads(res.stdout)
        self.assertFalse(data["passed"])
        target = data["targets"][0]
        self.assertEqual(target["state"], "undated")
        self.assertIn("could not be determined", target["note"].lower())

    def test_f14_http_error_status_boundaries(self):
        """HTTP error statuses (403, 404, 500) raise HTTPError and mark target unreachable."""
        for status in [403, 404, 500]:
            with patch.object(
                editorial_http,
                "fetch_html",
                return_value=(status, f"Error {status}", {"error": f"HTTP {status}"})
            ):
                with self.assertRaises(urllib.error.HTTPError) as ctx:
                    check_freshness.fetch_html(f"https://example.com/status-{status}", timeout=5)
                self.assertEqual(ctx.exception.code, status)

                res = check_freshness.check_target(
                    "t3", "url", None, f"https://example.com/status-{status}", explicit_dt=None
                )
                self.assertFalse(res["same_day"])
                self.assertEqual(res["state"], "unreachable")
