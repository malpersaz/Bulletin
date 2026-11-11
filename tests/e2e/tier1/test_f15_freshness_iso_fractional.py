"""
Tier 1 Feature Coverage: Feature 15 (freshness_iso_fractional).
Validates check_freshness.py ISO fractional seconds parsing (., comma decimals, microseconds)
while accurately converting to Europe/Istanbul calendar date and timezone.
"""

from __future__ import annotations

from datetime import datetime, timezone
import json
import os
import sys
from zoneinfo import ZoneInfo

from harness.base import E2ETestCase, feature, tier

KEVTEMEN_SCRIPTS = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "..", ".agents", "skills", "kevtemen", "scripts"))
if KEVTEMEN_SCRIPTS not in sys.path:
    sys.path.insert(0, KEVTEMEN_SCRIPTS)

import check_freshness


@tier(1)
@feature(15)
class TestF15FreshnessIsoFractional(E2ETestCase):
    """Tier 1 tests for ISO-8601 fractional seconds parsing in check_freshness."""

    def test_f15_parse_iso_fractional_milliseconds_utc_z(self):
        """Verify parse_iso_or_free parses milliseconds and converts UTC Z to Europe/Istanbul (+3h)."""
        dt = check_freshness.parse_iso_or_free("2026-11-11T10:30:00.123Z")
        self.assertIsNotNone(dt)
        self.assertEqual(dt.year, 2026)
        self.assertEqual(dt.month, 11)
        self.assertEqual(dt.day, 11)
        self.assertEqual(dt.hour, 13)
        self.assertEqual(dt.minute, 30)
        self.assertEqual(dt.microsecond, 123000)

    def test_f15_parse_iso_microseconds_with_positive_offset(self):
        """Verify parsing 6-digit microseconds with explicit timezone offset +02:00."""
        dt = check_freshness.parse_iso_or_free("2026-11-11T08:15:30.987654+02:00")
        self.assertIsNotNone(dt)
        self.assertEqual(dt.hour, 9)
        self.assertEqual(dt.minute, 15)
        self.assertEqual(dt.second, 30)
        self.assertEqual(dt.microsecond, 987654)

    def test_f15_parse_iso_comma_decimal_separator(self):
        """Verify ISO timestamp with comma decimal fraction (e.g. 12:00:00,500Z) is parsed correctly."""
        dt = check_freshness.parse_iso_or_free("2026-11-11T12:00:00,500Z")
        self.assertIsNotNone(dt)
        self.assertEqual(dt.hour, 15)
        self.assertEqual(dt.minute, 0)
        self.assertEqual(dt.microsecond, 500000)

    def test_f15_cli_date_fractional_today_passes(self):
        """Verify CLI execution with fractional seconds on today's calendar date passes."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        today_iso = now.strftime("%Y-%m-%dT10:00:00.000Z")
        script = os.path.join(KEVTEMEN_SCRIPTS, "check_freshness.py")
        res = self.run_python([script, "--date", today_iso, "--json"])
        self.assertExitCode(res, 0)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["passed", "status"])
        self.assertTrue(data["passed"])
        self.assertIn("PASSED", data["status"])

    def test_f15_meta_tag_fractional_seconds_html(self):
        """Verify HTML containing <meta property='article:published_time'> with fractional seconds is dated."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        iso_str = now.strftime("%Y-%m-%dT09:20:15.123456Z")
        html_content = f"""<!DOCTYPE html>
<html>
<head>
    <meta property="article:published_time" content="{iso_str}">
</head>
<body>
    <article><h1>Today Article</h1></article>
</body>
</html>"""
        temp_file = os.path.join(self.sandbox.root, "test_fractional.html")
        with open(temp_file, "w", encoding="utf-8") as f:
            f.write(html_content)

        script = os.path.join(KEVTEMEN_SCRIPTS, "check_freshness.py")
        res = self.run_python([script, temp_file, "--json"])
        self.assertExitCode(res, 0)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["passed", "targets"])
        self.assertTrue(data["passed"])
        target = data["targets"][0]
        self.assertEqual(target["state"], "dated")
        self.assertTrue(target["same_day"])
