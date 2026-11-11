"""
Tier 1 Feature Coverage: Feature 16 (freshness_tag_strip).
Validates check_freshness.py strips non-content tags (<script>, <style>, <aside>, <nav>, <footer>)
before date scanning and isolates publication dates to the primary <article> element.
"""

from __future__ import annotations

from datetime import datetime
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
@feature(16)
class TestF16FreshnessTagStrip(E2ETestCase):
    """Tier 1 tests for non-content tag stripping in check_freshness."""

    def test_f16_footer_stale_copyright_stripped(self):
        """Verify stale date inside <footer> is stripped and today's article date is recognized."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        today_iso = now.strftime("%Y-%m-%dT10:00:00Z")
        html = f"""<!DOCTYPE html>
<html>
<body>
    <article>
        <time datetime="{today_iso}">Today Article</time>
    </article>
    <footer>
        <p>© 2021-2023 Company Name. Tarih: 15.03.2023</p>
    </footer>
</body>
</html>"""
        path = os.path.join(self.sandbox.root, "article_footer.html")
        with open(path, "w", encoding="utf-8") as f:
            f.write(html)

        script = os.path.join(KEVTEMEN_SCRIPTS, "check_freshness.py")
        res = self.run_python([script, path, "--json"])
        self.assertExitCode(res, 0)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["passed", "targets"])
        self.assertTrue(data["passed"])
        self.assertTrue(data["targets"][0]["same_day"])

    def test_f16_aside_sidebar_recommended_date_stripped(self):
        """Verify today's date in <aside> sidebar is stripped when main <article> is stale, resulting in BLOCKED."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        today_iso = now.strftime("%Y-%m-%dT10:00:00Z")
        html = f"""<!DOCTYPE html>
<html>
<body>
    <article>
        <time datetime="2025-01-10T09:00:00Z">Stale News</time>
    </article>
    <aside class="sidebar">
        <h4>Recommended Stories</h4>
        <time datetime="{today_iso}">Today Sidebar Story</time>
    </aside>
</body>
</html>"""
        path = os.path.join(self.sandbox.root, "article_aside.html")
        with open(path, "w", encoding="utf-8") as f:
            f.write(html)

        script = os.path.join(KEVTEMEN_SCRIPTS, "check_freshness.py")
        res = self.run_python([script, path, "--json"])
        self.assertExitCode(res, 1)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["passed", "status"])
        self.assertFalse(data["passed"])
        self.assertIn("BLOCKED", data["status"])

    def test_f16_script_build_date_stripped(self):
        """Verify window.__BUILD_TIME__ script tag is stripped and not evaluated as publication date."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        today_iso = now.strftime("%Y-%m-%dT12:00:00Z")
        html = f"""<!DOCTYPE html>
<html>
<head>
    <script type="text/javascript">
        window.__BUILD_TIME__ = "{today_iso}";
    </script>
</head>
<body>
    <article>
        <time datetime="2024-06-15T10:00:00Z">Old Story</time>
    </article>
</body>
</html>"""
        path = os.path.join(self.sandbox.root, "article_script.html")
        with open(path, "w", encoding="utf-8") as f:
            f.write(html)

        script = os.path.join(KEVTEMEN_SCRIPTS, "check_freshness.py")
        res = self.run_python([script, path, "--json"])
        self.assertExitCode(res, 1)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["passed", "status"])
        self.assertFalse(data["passed"])

    def test_f16_nav_header_ticker_stripped(self):
        """Verify <nav> ticker showing today's date is ignored when article body date is stale."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        today_str = now.strftime("%d.%m.%Y")
        html = f"""<!DOCTYPE html>
<html>
<body>
    <header>
        <nav><p>Live News Ticker: {today_str}</p></nav>
    </header>
    <article>
        <time datetime="2025-08-20T10:00:00Z">Stale Article Body</time>
    </article>
</body>
</html>"""
        path = os.path.join(self.sandbox.root, "article_nav.html")
        with open(path, "w", encoding="utf-8") as f:
            f.write(html)

        script = os.path.join(KEVTEMEN_SCRIPTS, "check_freshness.py")
        res = self.run_python([script, path, "--json"])
        self.assertExitCode(res, 1)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["passed", "status"])
        self.assertFalse(data["passed"])

    def test_f16_article_container_isolation(self):
        """Verify extract_pub_date isolates dates within <article>, ignoring exterior text."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        today_str = now.strftime("%d.%m.%Y")
        html = f"""<!DOCTYPE html>
<html>
<body>
    <div>Outside date: {today_str}</div>
    <article>
        <p>Yayınlanma tarihi: 05.01.2025</p>
    </article>
</body>
</html>"""
        result = check_freshness.extract_pub_date(html, "http://example.com/isolated")
        self.assertIsNotNone(result)
        dt = result["dt"]
        self.assertEqual(dt.year, 2025)
        self.assertEqual(dt.month, 1)
        self.assertEqual(dt.day, 5)
