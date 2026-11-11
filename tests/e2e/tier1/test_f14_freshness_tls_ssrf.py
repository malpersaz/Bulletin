"""
Tier 1 Feature Coverage: Feature 14 (freshness_tls_ssrf).
Validates check_freshness.py integration with editorial_http for verified TLS
and SSRF private IP blocking, exiting fail-closed (code 1) on unreachable/blocked targets.
"""

from __future__ import annotations

from datetime import datetime
import json
import os
from zoneinfo import ZoneInfo

from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(14)
class TestF14FreshnessTlsSsrf(E2ETestCase):
    """Tier 1 tests for check_freshness.py TLS & SSRF integration."""

    @property
    def script_path(self) -> str:
        return os.path.join(self.project_root, ".agents", "skills", "kevtemen", "scripts", "check_freshness.py")

    def test_f14_editorial_http_transport_active(self):
        """Verify check_freshness passes when evaluating a valid same-day date."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        today_str = now.strftime("%d.%m.%Y")
        res = self.run_python([self.script_path, "--date", today_str, "--json"])
        self.assertExitCode(res, 0)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["passed", "status", "targets"])
        self.assertTrue(data["passed"])
        self.assertIn("PASSED", data["status"])
        self.assertTrue(data["targets"][0]["same_day"])

    def test_f14_ssrf_private_ip_blocks_fail_closed(self):
        """Verify cloud metadata endpoint (169.254.169.254) is blocked by SSRF and fails closed."""
        res = self.run_python([self.script_path, "http://169.254.169.254/latest/meta-data/", "--json"])
        self.assertExitCode(res, 1)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["passed", "status", "targets"])
        self.assertFalse(data["passed"])
        target = data["targets"][0]
        self.assertEqual(target["state"], "unreachable")
        self.assertIn("SSRF protection", target["note"])

    def test_f14_rfc1918_private_network_blocks(self):
        """Verify RFC 1918 private network IP (192.168.1.100) is blocked and script exits 1."""
        res = self.run_python([self.script_path, "http://192.168.1.100/article.html", "--json"])
        self.assertExitCode(res, 1)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["passed", "targets"])
        self.assertFalse(data["passed"])
        self.assertEqual(data["targets"][0]["state"], "unreachable")

    def test_f14_network_timeout_handling(self):
        """Verify unreachable test IP times out gracefully and marks target unreachable."""
        res = self.run_python([self.script_path, "http://192.0.2.1/timeout", "--timeout", "1", "--json"])
        self.assertExitCode(res, 1)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["passed", "targets"])
        self.assertFalse(data["passed"])
        self.assertEqual(data["targets"][0]["state"], "unreachable")

    def test_f14_invalid_url_scheme_rejection(self):
        """Verify non-HTTP/HTTPS URL schemes (e.g. ftp://) are rejected fail-closed."""
        res = self.run_python([self.script_path, "ftp://example.com/pub/news.html", "--json"])
        self.assertExitCode(res, 1)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["passed", "targets"])
        self.assertFalse(data["passed"])
        self.assertEqual(data["targets"][0]["state"], "unreachable")
