"""
Tier 2 Boundary & Corner Cases: Feature 15 (freshness_iso_fractional_boundaries).
Validates check_freshness.py ISO fractional seconds edge cases:
- Full fractional digits spectrum (1, 2, 3, 6, 9 digits)
- European comma separator in fractional seconds
- Extreme timezone offsets (+14:00 and -12:00)
- Leap second 23:59:60 handled gracefully without crashing
- Malformed ISO date strings (month 13, day 32, hour 25, truncated)
"""

from __future__ import annotations

import datetime
import os
import sys

from harness.base import E2ETestCase, feature, tier

KEVTEMEN_SCRIPTS = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "..", ".agents", "skills", "kevtemen", "scripts"))
if KEVTEMEN_SCRIPTS not in sys.path:
    sys.path.insert(0, KEVTEMEN_SCRIPTS)

import check_freshness


@tier(2)
@feature(15)
class TestF15FreshnessIsoFractionalBoundaries(E2ETestCase):
    """Tier 2 tests for ISO timestamp precision and timezone boundaries."""

    def test_f15_fractional_digits_spectrum(self):
        """Fractional seconds with 1 to 9 digits parse and convert UTC to Istanbul accurately."""
        specs = [
            ("2026-11-11T14:30:00.1Z", 100000),
            ("2026-11-11T14:30:00.12Z", 120000),
            ("2026-11-11T14:30:00.123Z", 123000),
            ("2026-11-11T14:30:00.123456Z", 123456),
            ("2026-11-11T14:30:00.123456789Z", 123456),  # microsecond precision truncates at 6 digits
        ]
        for s, expected_usec in specs:
            dt = check_freshness.parse_iso_or_free(s)
            self.assertIsNotNone(dt, f"Failed to parse {s}")
            self.assertEqual(dt.year, 2026)
            self.assertEqual(dt.month, 11)
            self.assertEqual(dt.day, 11)
            self.assertEqual(dt.hour, 17)  # UTC 14:30 -> Istanbul (+3) 17:30
            self.assertEqual(dt.minute, 30)
            self.assertEqual(dt.microsecond, expected_usec)

    def test_f15_european_comma_fractional_separator(self):
        """Comma separator for decimals (common in French/German feeds) parses equivalently to period."""
        dt1 = check_freshness.parse_iso_or_free("2026-11-11T10:15:30,500+03:00")
        self.assertIsNotNone(dt1)
        self.assertEqual(dt1.hour, 10)
        self.assertEqual(dt1.microsecond, 500000)

        dt2 = check_freshness.parse_iso_or_free("2026-11-11T08:00:00,123456Z")
        self.assertIsNotNone(dt2)
        self.assertEqual(dt2.hour, 11)  # 08:00 UTC + 3 = 11:00
        self.assertEqual(dt2.microsecond, 123456)

    def test_f15_extreme_timezone_offsets(self):
        """Extreme timezone offsets (+14:00 Kiritimati and -12:00 Baker Island) convert to Istanbul."""
        # Offset +14:00: UTC 06:00 -> Istanbul 09:00 on 2026-11-11
        dt_plus14 = check_freshness.parse_iso_or_free("2026-11-11T20:00:00+14:00")
        self.assertIsNotNone(dt_plus14)
        self.assertEqual(dt_plus14.date(), datetime.date(2026, 11, 11))
        self.assertEqual(dt_plus14.hour, 9)

        # Offset -12:00: UTC 14:00 -> Istanbul 17:00 on 2026-11-11
        dt_minus12 = check_freshness.parse_iso_or_free("2026-11-11T02:00:00-12:00")
        self.assertIsNotNone(dt_minus12)
        self.assertEqual(dt_minus12.date(), datetime.date(2026, 11, 11))
        self.assertEqual(dt_minus12.hour, 17)

    def test_f15_leap_second_boundary(self):
        """Leap second 23:59:60 is handled gracefully without unhandled exception."""
        dt = check_freshness.parse_iso_or_free("2026-11-11T23:59:60Z")
        # Python datetime does not support second=60, so parser returns None
        self.assertIsNone(dt)

    def test_f15_malformed_iso_strings(self):
        """Invalid date components return None gracefully without throwing exceptions."""
        malformed = [
            "2026-13-11T10:00:00Z",  # month 13
            "2026-11-32T10:00:00Z",  # day 32
            "2026-11-11T25:00:00Z",  # hour 25
            "2026-11-",             # truncated
            "not-a-date-at-all",    # random text
        ]
        for bad in malformed:
            dt = check_freshness.parse_iso_or_free(bad)
            self.assertIsNone(dt, f"Malformed string '{bad}' should return None")
