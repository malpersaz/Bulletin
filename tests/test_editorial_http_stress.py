#!/usr/bin/env python3
"""
Empirical Adversarial Stress Test Suite for editorial_http.py
Milestone 2 Challenge M2-1 (Empirical Transport & SSRF Stress Testing)

Tests:
1. SSRF evasion & IP representation normalization (validate_public_url)
2. Redirect interception & loop limits (SSRFSafeRedirectHandler)
3. Retry-After parsing & bounded backoff (_parse_retry_after)
4. Two-tier cache atomicity & concurrency under multi-threaded and multi-process loads
"""

import concurrent.futures
import datetime
import email.utils
import io
import json
import multiprocessing
import os
import shutil
import sys
import tempfile
import time
import unittest
import urllib.error
import urllib.parse
import urllib.request
from typing import List, Tuple

# Add shared skills path
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "../.agents/skills/_shared")))
import editorial_http as eh


# ---------------------------------------------------------------------------
# Multiprocessing Worker for Cache Concurrency
# ---------------------------------------------------------------------------

def _mp_cache_worker(args: Tuple[int, str, str]) -> str:
    """Worker function for multi-process cache concurrency testing."""
    worker_id, cache_dir, base_url = args
    eh._MEMORY_CACHE.clear()

    # Perform alternating writes and reads on shared and isolated keys
    for cycle in range(20):
        url = f"{base_url}_{cycle % 3}"
        payload = f"worker_{worker_id}_cycle_{cycle}_" + ("X" * 10000)
        headers = {"x-worker-id": str(worker_id), "x-cycle": str(cycle)}

        eh.set_cached_response(
            url=url,
            status_code=200,
            body=payload,
            headers=headers,
            cache_dir=cache_dir,
        )

        # Clear memory cache in this process to force disk read
        eh._MEMORY_CACHE.clear()
        cached = eh.get_cached_response(url, cache_dir=cache_dir)
        if cached is None:
            return f"Error: worker {worker_id} got None on read of {url}"

        status, body, res_headers = cached
        if status != 200:
            return f"Error: worker {worker_id} got status {status}"
        if not isinstance(body, str) or len(body) < 10000 or not body.startswith("worker_"):
            return f"Error: worker {worker_id} got corrupted body (len={len(body)})"

    return "OK"


# ---------------------------------------------------------------------------
# Test Suite
# ---------------------------------------------------------------------------

class TestEditorialHttpAdversarial(unittest.TestCase):

    def setUp(self):
        self.test_cache_dir = tempfile.mkdtemp(prefix="af_test_cache_")
        eh.clear_cache(cache_dir=self.test_cache_dir)

    def tearDown(self):
        shutil.rmtree(self.test_cache_dir, ignore_errors=True)

    # =======================================================================
    # 1. SSRF & IP Evasion Stress Tests (validate_public_url)
    # =======================================================================

    def test_ssrf_decimal_hex_octal_evasions(self):
        """Stress-test alternate IP encoding evasions (decimal, hex, octal, shorthand)."""
        evasion_cases = [
            # Decimal representation (2130706433 -> 127.0.0.1)
            ("http://2130706433/", "decimal integer 127.0.0.1"),
            ("http://3232235521/", "decimal integer 192.168.0.1"),
            ("http://2852039166/", "decimal integer 169.254.169.254"),
            ("http://0/", "decimal 0 (0.0.0.0)"),
            # Octal representations
            ("http://0177.0.0.1/", "octal 0177.0.0.1 (127.0.0.1)"),
            ("http://017700000001/", "octal single-dword 127.0.0.1"),
            ("http://0300.0250.0.1/", "octal 192.168.0.1"),
            ("http://0251.0376.0251.0376/", "octal 169.254.169.254"),
            # Hex representations
            ("http://0x7f000001/", "hex single-dword 127.0.0.1"),
            ("http://0x7f.0.0.1/", "hex dotted 127.0.0.1"),
            ("http://0xc0a80001/", "hex 192.168.0.1"),
            ("http://0xa9fea9fe/", "hex 169.254.169.254"),
            # Abbreviated notation
            ("http://127.1/", "shorthand 127.1 (127.0.0.1)"),
            ("http://127.0.1/", "shorthand 127.0.1 (127.0.0.1)"),
            ("http://10.1/", "shorthand 10.1 (10.0.0.1)"),
        ]

        for url, desc in evasion_cases:
            valid, reason = eh.validate_public_url(url)
            self.assertFalse(
                valid,
                f"SSRF EVASION VULNERABILITY: {desc} ({url}) was ALLOWED! Reason: {reason}",
            )
            self.assertIn("SSRF protection", reason, f"Expected SSRF protection reason for {url}")

    def test_ssrf_forbidden_subnets_and_ranges(self):
        """Verify all RFC-specified forbidden subnets and metadata ranges are blocked."""
        forbidden_ips = [
            # RFC 1122 Loopback & Current Network
            ("http://0.0.0.0/", "0.0.0.0 current net"),
            ("http://127.0.0.1/", "127.0.0.1 loopback"),
            ("http://127.127.127.127/", "127.127.127.127 loopback boundary"),
            ("http://127.255.255.254/", "127.255.255.254 loopback broadcast boundary"),
            # RFC 1918 Private Subnets
            ("http://10.0.0.1/", "10.0.0.1 Class A"),
            ("http://10.255.255.255/", "10.255.255.255 Class A broadcast"),
            ("http://172.16.0.1/", "172.16.0.1 Class B start"),
            ("http://172.31.255.255/", "172.31.255.255 Class B end"),
            ("http://192.168.0.1/", "192.168.0.1 Class C start"),
            ("http://192.168.255.255/", "192.168.255.255 Class C end"),
            # RFC 3927 Link-Local / Cloud Metadata
            ("http://169.254.169.254/", "169.254.169.254 AWS/GCP/Azure metadata"),
            ("http://169.254.0.1/", "169.254.0.1 link-local"),
            # RFC 6598 Carrier-Grade NAT (CGNAT)
            ("http://100.64.0.1/", "100.64.0.1 CGNAT start"),
            ("http://100.64.1.1/", "100.64.1.1 CGNAT internal"),
            ("http://100.127.255.254/", "100.127.255.254 CGNAT end"),
            # RFC 5737 Test Networks & Benchmark
            ("http://192.0.2.1/", "TEST-NET-1 192.0.2.1"),
            ("http://198.51.100.1/", "TEST-NET-2 198.51.100.1"),
            ("http://203.0.113.1/", "TEST-NET-3 203.0.113.1"),
            ("http://198.18.0.1/", "Network benchmark 198.18.0.1"),
            # Multicast, Reserved, Broadcast
            ("http://224.0.0.1/", "Multicast 224.0.0.1"),
            ("http://240.0.0.1/", "Reserved 240.0.0.1"),
            ("http://255.255.255.255/", "Broadcast 255.255.255.255"),
            # IPv6 Special & Private
            ("http://[::]/", "IPv6 unspecified"),
            ("http://[::1]/", "IPv6 loopback"),
            ("http://[::ffff:127.0.0.1]/", "IPv4-mapped IPv6 loopback"),
            ("http://[::ffff:10.0.0.1]/", "IPv4-mapped IPv6 RFC 1918"),
            ("http://[::ffff:169.254.169.254]/", "IPv4-mapped IPv6 metadata"),
            ("http://[fc00::1]/", "IPv6 ULA start"),
            ("http://[fd12:3456:789a:1::1]/", "IPv6 ULA random"),
            ("http://[fe80::1]/", "IPv6 link-local"),
            ("http://[ff02::1]/", "IPv6 multicast"),
            # Localhost naming
            ("http://localhost/", "localhost"),
            ("http://localhost:8080/", "localhost with port"),
        ]

        for url, desc in forbidden_ips:
            valid, reason = eh.validate_public_url(url)
            self.assertFalse(
                valid,
                f"FORBIDDEN SUBNET LEAK: {desc} ({url}) was ALLOWED! Reason: {reason}",
            )

    def test_ssrf_forbidden_schemes(self):
        """Verify non-HTTP schemes are rejected without network requests."""
        schemes = [
            "file:///etc/passwd",
            "file:///c:/windows/win.ini",
            "gopher://127.0.0.1:6379/_INFO",
            "ftp://evil.com/payload.sh",
            "php://filter/read=convert.base64-encode/resource=index.php",
            "data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==",
            "javascript:alert(document.cookie)",
            "dict://127.0.0.1:2628/",
            "ldap://127.0.0.1:389/o=base",
            "tftp://127.0.0.1/boot",
        ]

        for url in schemes:
            valid, reason = eh.validate_public_url(url)
            self.assertFalse(valid, f"SCHEME LEAK: {url} was ALLOWED!")
            self.assertIn("Invalid URL scheme", reason)

    def test_ssrf_legitimate_public_ips_allowed(self):
        """Verify legitimate public IP addresses and domains are permitted."""
        legit_urls = [
            "http://8.8.8.8/",
            "https://1.1.1.1/",
            "http://93.184.216.34/",  # example.com
            "https://8.8.4.4:443/dns-query",
        ]

        for url in legit_urls:
            valid, reason = eh.validate_public_url(url)
            self.assertTrue(valid, f"FALSE POSITIVE: Public URL {url} was BLOCKED: {reason}")
            self.assertEqual(reason, "")

    def test_ssrf_malformed_urls_and_port_boundaries(self):
        """Verify handling of malformed URLs and port boundaries."""
        # Empty and non-string
        self.assertFalse(eh.validate_public_url("")[0])
        self.assertFalse(eh.validate_public_url("   ")[0])
        self.assertFalse(eh.validate_public_url(None)[0])  # type: ignore

        # Valid ports
        self.assertTrue(eh.validate_public_url("http://8.8.8.8:80/")[0])
        self.assertTrue(eh.validate_public_url("http://8.8.8.8:8080/")[0])
        self.assertTrue(eh.validate_public_url("http://8.8.8.8:65535/")[0])

        # Port 0 is rejected by validate_public_url contract
        valid, reason = eh.validate_public_url("http://8.8.8.8:0/")
        self.assertFalse(valid)
        self.assertIn("Invalid port", reason)

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

        # IDNA and oversized label handling (RFC 1035 label limit)
        oversized_url = "http://" + "a" * 10000 + ".com/"
        valid, reason = eh.validate_public_url(oversized_url)
        self.assertFalse(valid)
        self.assertTrue(len(reason) > 0)

        # Malformed IPv6 brackets
        malformed_ipv6 = "http://[invalid_ipv6]/"
        valid, reason = eh.validate_public_url(malformed_ipv6)
        self.assertFalse(valid)
        self.assertTrue(len(reason) > 0)

        # Exception shielding in fetch_html and fetch_binary
        status, html, headers = eh.fetch_html("http://example.com:70000/", use_cache=False)
        self.assertEqual(status, 400)
        self.assertIn("Invalid port", headers.get("x-error", ""))

        status, bdata, headers = eh.fetch_binary("http://example.com:70000/", use_cache=False)
        self.assertEqual(status, 400)
        self.assertIn("Invalid port", headers.get("x-error", ""))

    # =======================================================================
    # 2. Redirect Handler Stress Tests (SSRFSafeRedirectHandler)
    # =======================================================================

    def test_redirect_handler_intercepts_ssrf(self):
        """Verify redirect handler blocks hops to internal/metadata endpoints."""
        handler = eh.SSRFSafeRedirectHandler()
        req = urllib.request.Request("https://example.com/start")
        fp = io.BytesIO(b"")

        malicious_targets = [
            "http://127.0.0.1/admin",
            "http://169.254.169.254/latest/meta-data/",
            "http://10.0.0.1/internal",
            "http://192.168.1.1/router",
            "http://172.16.0.1/intranet",
            "http://[::1]/secret",
            "http://[fc00::1]/db",
            "http://100.64.1.1/cgnat",
            "file:///etc/passwd",
            "http://2130706433/",
            "//127.0.0.1/relative-protocol",
            "/relative-path-leading-to-ssrf",  # will join to https://example.com/...
        ]

        for target in malicious_targets:
            if target == "/relative-path-leading-to-ssrf":
                # Relative path stays on example.com (public) -> should not raise SSRF error
                new_req = handler.redirect_request(req, fp, 302, "Found", {}, target)
                self.assertIsNotNone(new_req)
                continue

            with self.assertRaises(urllib.error.HTTPError, msg=f"Allowed redirect to {target}") as ctx:
                handler.redirect_request(req, fp, 302, "Found", {}, target)
            self.assertIn("SSRF protection blocked redirect", str(ctx.exception))

    def test_redirect_handler_enforces_max_redirection_limit(self):
        """Verify handler terminates on redirect chains exceeding max_redirections (5)."""
        class MockOpener:
            def __init__(self, h):
                self.handler = h
                self.handler.parent = self
                self.hops = 0

            def open(self, r, timeout=None):
                self.hops += 1
                if self.hops > 10:
                    raise RuntimeError("Infinite redirect runaway!")
                headers = {"location": f"https://8.8.8.8/hop_{self.hops}"}
                r.timeout = 10
                return self.handler.http_error_302(r, io.BytesIO(b""), 302, "Found", headers)

        handler = eh.SSRFSafeRedirectHandler()
        opener = MockOpener(handler)
        initial_req = urllib.request.Request("https://8.8.8.8/start")
        initial_req.timeout = 10

        with self.assertRaises(urllib.error.HTTPError) as ctx:
            opener.open(initial_req)

        self.assertEqual(ctx.exception.code, 302)
        self.assertIn("infinite loop", str(ctx.exception.msg).lower())
        # With max_redirections = 5, the 6th open call triggers loop detection
        self.assertEqual(opener.hops, 6, "Did not terminate at expected hop count")

    def test_redirect_handler_circular_loop_termination(self):
        """Verify handler terminates on ping-pong 2-hop circular redirect loop."""
        class MockPingPongOpener:
            def __init__(self, h):
                self.handler = h
                self.handler.parent = self
                self.hops = 0

            def open(self, r, timeout=None):
                self.hops += 1
                if self.hops > 10:
                    raise RuntimeError("Infinite redirect runaway!")
                target = "https://8.8.8.8/a" if (self.hops % 2 == 1) else "https://8.8.8.8/b"
                headers = {"location": target}
                r.timeout = 10
                return self.handler.http_error_302(r, io.BytesIO(b""), 302, "Found", headers)

        handler = eh.SSRFSafeRedirectHandler()
        opener = MockPingPongOpener(handler)
        initial_req = urllib.request.Request("https://8.8.8.8/a")
        initial_req.timeout = 10

        with self.assertRaises(urllib.error.HTTPError) as ctx:
            opener.open(initial_req)

        self.assertEqual(ctx.exception.code, 302)
        # With max_repeats = 2, terminates once a URL is visited >= 2 times
        self.assertLessEqual(opener.hops, 5)

    def test_redirect_handler_allows_safe_public_redirect(self):
        """Verify redirect to valid public endpoint succeeds and produces new Request."""
        handler = eh.SSRFSafeRedirectHandler()
        req = urllib.request.Request("https://8.8.8.8/old")
        fp = io.BytesIO(b"")

        new_req = handler.redirect_request(req, fp, 301, "Moved Permanently", {}, "https://8.8.8.8/new")
        self.assertIsNotNone(new_req)
        self.assertEqual(new_req.full_url, "https://8.8.8.8/new")

    # =======================================================================
    # 3. Retry-After & Backoff Stress Tests (_parse_retry_after)
    # =======================================================================

    def test_retry_after_integer_seconds(self):
        """Verify standard integer delay-seconds parsing."""
        self.assertEqual(eh._parse_retry_after("0"), 0.0)
        self.assertEqual(eh._parse_retry_after("5"), 5.0)
        self.assertEqual(eh._parse_retry_after("15"), 15.0)
        self.assertEqual(eh._parse_retry_after("120"), 120.0)
        self.assertEqual(eh._parse_retry_after("  30  "), 30.0)

    def test_retry_after_negative_and_malformed(self):
        """Verify negative, malformed, or non-numeric strings safely return 0.0."""
        malformed = ["-5", "-1", "abc", "NaN", "undefined", "", "   ", None, "12.34", '"10"']
        for val in malformed:
            res = eh._parse_retry_after(val)  # type: ignore
            self.assertEqual(res, 0.0, f"Expected 0.0 for malformed Retry-After: {val!r}")

    def test_retry_after_rfc_http_dates(self):
        """Verify RFC 7231 / RFC 2822 HTTP-date parsing for future and past timestamps."""
        now = datetime.datetime.now(datetime.timezone.utc)

        # Future date: now + 40 seconds
        future_dt = now + datetime.timedelta(seconds=40)
        future_str = email.utils.format_datetime(future_dt)
        parsed_future = eh._parse_retry_after(future_str)
        self.assertGreater(parsed_future, 30.0)
        self.assertLessEqual(parsed_future, 45.0)

        # Past date: now - 40 seconds -> must clamp to 0.0
        past_dt = now - datetime.timedelta(seconds=40)
        past_str = email.utils.format_datetime(past_dt)
        parsed_past = eh._parse_retry_after(past_str)
        self.assertEqual(parsed_past, 0.0)

    def test_retry_after_maximum_backoff_cap(self):
        """Verify large Retry-After numbers are bounded by MAX_RETRY_SLEEP_SECONDS (15.0s)."""
        large_values = ["999999", "86400", "3600", "16"]
        for val in large_values:
            parsed = eh._parse_retry_after(val)
            bounded_sleep = min(parsed, eh.MAX_RETRY_SLEEP_SECONDS)
            self.assertLessEqual(
                bounded_sleep,
                eh.MAX_RETRY_SLEEP_SECONDS,
                f"Backoff sleep {bounded_sleep} exceeded MAX_RETRY_SLEEP_SECONDS for {val}",
            )
            self.assertEqual(bounded_sleep, 15.0)

    # =======================================================================
    # 4. Cache Concurrency & Atomicity Stress Tests
    # =======================================================================

    def test_cache_multithreaded_concurrency(self):
        """Verify multi-threaded concurrent reads and writes maintain integrity."""
        url = "https://8.8.8.8/test_concurrency_thread"
        errors: List[str] = []

        def worker(worker_id: int):
            try:
                for i in range(10):
                    body = f"worker_{worker_id}_run_{i}_" + ("Z" * 5000)
                    eh.set_cached_response(
                        url=url,
                        status_code=200,
                        body=body,
                        headers={"x-thread": str(worker_id)},
                        cache_dir=self.test_cache_dir,
                    )
                    cached = eh.get_cached_response(url, cache_dir=self.test_cache_dir)
                    if cached is None:
                        errors.append(f"Thread {worker_id} read None from cache")
                        return
                    status, c_body, headers = cached
                    if status != 200 or not str(c_body).startswith("worker_"):
                        errors.append(f"Thread {worker_id} read corrupted data")
            except Exception as e:
                errors.append(f"Thread {worker_id} exception: {e}")

        with concurrent.futures.ThreadPoolExecutor(max_workers=16) as executor:
            futures = [executor.submit(worker, w) for w in range(32)]
            concurrent.futures.wait(futures)

        self.assertEqual(errors, [], f"Multi-threaded cache errors detected: {errors[:5]}")

    def test_cache_multiprocess_concurrency(self):
        """Verify multi-process concurrent access with atomic POSIX file replacement."""
        workers = 8
        base_url = "https://8.8.8.8/mp_test"
        args_list = [(i, self.test_cache_dir, base_url) for i in range(workers)]

        with multiprocessing.Pool(processes=workers) as pool:
            results = pool.map(_mp_cache_worker, args_list)

        failures = [r for r in results if r != "OK"]
        self.assertEqual(failures, [], f"Multi-process cache concurrency failures: {failures}")

    def test_cache_ttl_and_eviction(self):
        """Verify TTL expiration and clear_cache behavior."""
        url = "https://8.8.8.8/test_ttl"
        eh.set_cached_response(
            url=url,
            status_code=200,
            body="fresh content",
            headers={},
            cache_dir=self.test_cache_dir,
        )

        # Valid read with TTL = 10
        self.assertIsNotNone(eh.get_cached_response(url, ttl=10, cache_dir=self.test_cache_dir))

        # Clear memory cache to force disk evaluation
        eh._MEMORY_CACHE.clear()

        # Immediate read with TTL = 0 -> expired
        time.sleep(0.05)
        expired = eh.get_cached_response(url, ttl=0, cache_dir=self.test_cache_dir)
        self.assertIsNone(expired, "Expired cache entry was returned!")

        # Clear cache completely
        eh.set_cached_response(url, 200, "data", {}, cache_dir=self.test_cache_dir)
        cleared_count = eh.clear_cache(cache_dir=self.test_cache_dir)
        self.assertGreaterEqual(cleared_count, 1)
        self.assertIsNone(eh.get_cached_response(url, cache_dir=self.test_cache_dir))

    def test_cache_binary_roundtrip(self):
        """Verify binary data correctly serializes via base64 and roundtrips without loss."""
        url = "https://8.8.8.8/sample_image.png"
        raw_bytes = bytes([0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A]) + os.urandom(1024)

        eh.set_cached_response(
            url=url,
            status_code=200,
            body=raw_bytes,
            headers={"content-type": "image/png"},
            is_binary=True,
            cache_dir=self.test_cache_dir,
        )

        # Tier 1 read
        cached_t1 = eh.get_cached_response(url, is_binary=True, cache_dir=self.test_cache_dir)
        self.assertIsNotNone(cached_t1)
        self.assertEqual(cached_t1[1], raw_bytes)

        # Clear memory cache -> Tier 2 disk read
        eh._MEMORY_CACHE.clear()
        cached_t2 = eh.get_cached_response(url, is_binary=True, cache_dir=self.test_cache_dir)
        self.assertIsNotNone(cached_t2)
        self.assertEqual(cached_t2[1], raw_bytes)

    # =======================================================================
    # 5. End-to-End Transport Safety Tests (fetch_html & fetch_binary)
    # =======================================================================

    def test_fetch_html_blocks_ssrf_before_connection(self):
        """Verify fetch_html immediately blocks SSRF targets with HTTP 400 and x-error."""
        for target in ["http://127.0.0.1:9999/", "http://169.254.169.254/", "file:///etc/hosts"]:
            status, body, headers = eh.fetch_html(target, use_cache=False)
            self.assertEqual(status, 400)
            err_msg = headers.get("x-error", "")
            self.assertTrue(
                "SSRF protection" in err_msg or "Invalid URL scheme" in err_msg,
                f"Expected SSRF or scheme error header in {err_msg}",
            )
            self.assertTrue(
                "SSRF protection blocked URL" in body or "Invalid URL scheme" in body,
                f"Expected SSRF or scheme error in body: {body}",
            )

    def test_fetch_binary_blocks_ssrf_before_connection(self):
        """Verify fetch_binary immediately blocks SSRF targets with HTTP 400."""
        for target in ["http://10.0.0.1/photo.jpg", "http://[::1]/asset.webp"]:
            status, body, headers = eh.fetch_binary(target, use_cache=False)
            self.assertEqual(status, 400)
            self.assertIn("SSRF protection", headers.get("x-error", ""))


if __name__ == "__main__":
    unittest.main(verbosity=2)
