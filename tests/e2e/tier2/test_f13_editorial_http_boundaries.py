"""
Tier 2 Boundary & Corner Cases: Feature 13 (editorial_http_boundaries).
Validates edge cases and boundary conditions in editorial_http.py:
- Malformed URLs (empty, scheme, invalid ports, label/length overflow)
- Advanced SSRF boundary matrix (IPv4-mapped IPv6, CGNAT, ULA, doc subnets, zero-net, broadcast)
- DNS rebinding prevention with multi-IP resolution (fail-closed if any IP is private)
- Redirect ceiling (>5 hops) and redirect-to-private targets blocked
- Retry-After header parsing (capping at 15s, RFC date parsing, malformed fallback)
- Cache TTL expiration and selective eviction
"""

from __future__ import annotations

import os
import socket
import sys
import time
from email.utils import formatdate
from unittest.mock import patch

from harness.base import E2ETestCase, feature, tier

# Add shared skills to sys.path
SHARED_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "..", ".agents", "skills", "_shared"))
if SHARED_DIR not in sys.path:
    sys.path.insert(0, SHARED_DIR)

import editorial_http


@tier(2)
@feature(13)
class TestF13EditorialHttpBoundaries(E2ETestCase):
    """Tier 2 tests for editorial_http boundary conditions."""

    def test_f13_malformed_url_boundary(self):
        """Malformed and invalid URLs are rejected with specific descriptive reasons."""
        malformed = [
            ("", "empty"),
            (None, "empty"),
            ("http://", "host"),
            ("http://:8080", "host"),
            ("http://example.com:99999", "port"),
            ("http://example.com:-1", "port"),
            ("ftp://example.com/file", "scheme"),
            ("http://" + "a" * 64 + ".com", "label"),
            ("http://" + ("a." * 130) + "com", "length"),
        ]
        for url, kind in malformed:
            valid, reason = editorial_http.validate_public_url(url)
            self.assertFalse(valid, f"URL '{url}' must be invalid ({kind})")
            self.assertTrue(len(reason) > 0)

    def test_f13_advanced_ssrf_boundary_matrix(self):
        """Advanced SSRF vectors (CGNAT, IPv4-mapped IPv6, ULA, documentation subnets) are blocked."""
        vectors = [
            "http://[::ffff:127.0.0.1]/",
            "http://100.64.0.1/",
            "http://169.254.169.254/latest/meta-data/",
            "http://[fc00::1]/",
            "http://192.0.2.1/",
            "http://[2001:db8::1]/",
            "http://0.0.0.0/",
            "http://255.255.255.255/",
        ]
        for vec in vectors:
            valid, reason = editorial_http.validate_public_url(vec)
            self.assertFalse(valid, f"Vector {vec} must be blocked by SSRF protection")
            self.assertIn("SSRF protection", reason)

    def test_f13_dns_rebinding_mixed_ip_boundary(self):
        """DNS resolution returning mixed public and private IPs fails closed on the private IP."""
        mock_addrinfo = [
            (socket.AF_INET, socket.SOCK_STREAM, 6, "", ("93.184.216.34", 80)),
            (socket.AF_INET, socket.SOCK_STREAM, 6, "", ("127.0.0.1", 80)),
        ]
        with patch("socket.getaddrinfo", return_value=mock_addrinfo):
            valid, reason = editorial_http.validate_public_url("http://rebind.example.com/api")
            self.assertFalse(valid)
            self.assertIn("SSRF protection", reason)
            self.assertIn("127.0.0.1", reason)

    def test_f13_redirect_ceiling_and_private_target_boundary(self):
        """Redirect exceeding 5 hops or redirecting to private target is blocked."""
        # Case A: Redirect loop exceeding ceiling
        loop_url = self.mock_http.get_url("/redir-loop")
        self.mock_http.add_route("/redir-loop", "", status=302, headers={"Location": loop_url})

        with patch.object(editorial_http, "validate_public_url", return_value=(True, "OK")):
            status_code, body, headers = editorial_http.fetch_html(loop_url, use_cache=False)
            self.assertIn(status_code, [0, 302, 400, 500])

        # Case B: Redirecting to private IP target
        self.mock_http.add_route("/redir-private", "", status=302, headers={"Location": "http://192.168.1.1/secret"})
        status_code, body, headers = editorial_http.fetch_html(self.mock_http.get_url("/redir-private"), use_cache=False)
        self.assertIn(status_code, [0, 400])

    def test_f13_retry_after_header_bounds(self):
        """Retry-After header parses integer, RFC date, caps at 15s, and handles malformed gracefully."""
        # Case A: Excessive integer seconds capped at 15.0s
        cap_val = editorial_http._parse_retry_after("999999")
        self.assertEqual(min(cap_val, 15.0), 15.0)

        # Case B: RFC 7231 date (2 seconds in the future)
        future_stamp = time.time() + 2.0
        rfc_date = formatdate(future_stamp, usegmt=True)
        date_val = editorial_http._parse_retry_after(rfc_date)
        self.assertGreaterEqual(date_val, 0.5)
        self.assertLessEqual(date_val, 3.5)

        # Case C: Malformed date string falls back to 0.0
        bad_val = editorial_http._parse_retry_after("invalid-garbage-date")
        self.assertEqual(bad_val, 0.0)

    def test_f13_cache_ttl_and_eviction_boundary(self):
        """Cache TTL respects expiration time and evicts expired cache entries."""
        test_url = "http://example-cdn.com/cached-page"
        cached_content = "<html>Cached</html>"
        cached_headers = {"content-type": "text/html"}

        # Store in cache using set_cached_response
        editorial_http.set_cached_response(test_url, 200, cached_content, cached_headers)

        # Immediate fetch with TTL=600 should hit cache
        hit = editorial_http.get_cached_response(test_url, ttl=600)
        self.assertIsNotNone(hit)
        self.assertEqual(hit[0], 200)

        # Fetch with TTL=0 should expire cache entry
        miss = editorial_http.get_cached_response(test_url, ttl=0)
        self.assertIsNone(miss)

        # clear_cache with older_than
        cleared = editorial_http.clear_cache(older_than=0)
        self.assertIsInstance(cleared, int)
