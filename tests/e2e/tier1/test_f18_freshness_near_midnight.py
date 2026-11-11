"""
Tier 1 Feature Coverage: Feature 18 (freshness_near_midnight).
Validates check_freshness.py near-midnight diagnostic attributes (age_hours, is_yesterday_late_evening)
and console diagnostic emissions when handling yesterday late-evening articles.
"""

from __future__ import annotations

from datetime import datetime, timedelta
import json
import os
import sys
from zoneinfo import ZoneInfo

from harness.base import E2ETestCase, feature, tier

KEVTEMEN_SCRIPTS = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "..", ".agents", "skills", "kevtemen", "scripts"))


@tier(1)
@feature(18)
class TestF18FreshnessNearMidnight(E2ETestCase):
    """Tier 1 tests for near-midnight diagnostics and age_hours in check_freshness."""

    @property
    def script_path(self) -> str:
        return os.path.join(KEVTEMEN_SCRIPTS, "check_freshness.py")

    def test_f18_yesterday_late_evening_flag_true(self):
        """Verify publication date yesterday at 22:30 Istanbul time sets is_yesterday_late_evening=True and fails."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        yesterday_2230 = (now - timedelta(days=1)).replace(hour=22, minute=30, second=0).strftime("%Y-%m-%dT22:30:00+03:00")

        res = self.run_python([self.script_path, "--date", yesterday_2230, "--json"])
        self.assertExitCode(res, 1)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["passed", "targets"])
        self.assertFalse(data["passed"])
        target = data["targets"][0]
        self.assertTrue(target["is_yesterday_late_evening"])
        self.assertFalse(target["same_day"])

    def test_f18_yesterday_morning_flag_false(self):
        """Verify publication date yesterday morning (09:00) sets is_yesterday_late_evening=False."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        yesterday_0900 = (now - timedelta(days=1)).replace(hour=9, minute=0, second=0).strftime("%Y-%m-%dT09:00:00+03:00")

        res = self.run_python([self.script_path, "--date", yesterday_0900, "--json"])
        self.assertExitCode(res, 1)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["targets"])
        target = data["targets"][0]
        self.assertFalse(target["is_yesterday_late_evening"])
        self.assertFalse(target["same_day"])

    def test_f18_today_early_morning_flag_false(self):
        """Verify publication date today early morning (01:15) passes with is_yesterday_late_evening=False."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        today_0115 = now.replace(hour=1, minute=15, second=0).strftime("%Y-%m-%dT01:15:00+03:00")

        res = self.run_python([self.script_path, "--date", today_0115, "--json"])
        self.assertExitCode(res, 0)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["passed", "targets"])
        self.assertTrue(data["passed"])
        target = data["targets"][0]
        self.assertTrue(target["same_day"])
        self.assertFalse(target["is_yesterday_late_evening"])

    def test_f18_structured_age_hours_precision(self):
        """Verify age_hours is computed as a floating point number reflecting duration since publication."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        pub_dt = (now - timedelta(hours=5, minutes=30)).strftime("%Y-%m-%dT%H:%M:%S+03:00")

        res = self.run_python([self.script_path, "--date", pub_dt, "--json"])
        data = self.assertJsonEnvelope(res.stdout, required_keys=["targets"])
        target = data["targets"][0]
        self.assertIsInstance(target["age_hours"], float)
        self.assertAlmostEqual(target["age_hours"], 5.5, delta=0.5)

    def test_f18_console_output_diagnostic_text(self):
        """Verify console text output contains yesterday late-evening diagnostic message when blocked."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        yesterday_2100 = (now - timedelta(days=1)).replace(hour=21, minute=0, second=0).strftime("%Y-%m-%dT21:00:00+03:00")

        res = self.run_python([self.script_path, "--date", yesterday_2100])
        self.assertExitCode(res, 1)
        self.assertIn("yesterday late-evening near-midnight", res.stdout)
