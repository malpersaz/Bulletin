"""Feature 30: source_catalog.py --scan-today.

Validates the editorial radar feed scanning:
- Discovers today's RSS items matching target date (Europe/Istanbul)
- Discards stale feed items
- Correctly parses Google News sitemap XML format (<news:publication_date>)
- Filters queried feeds by category (e.g. amed_diyarbakir)
- Resilient to individual feed errors (HTTP 500 / network failures) without aborting
"""

from __future__ import annotations

from datetime import date
import json
from pathlib import Path
import sys
from unittest.mock import patch

from harness.base import E2ETestCase, feature, tier

BASE_DIR = Path(__file__).resolve().parents[3]
KEVKOLIN_DIR = BASE_DIR / ".agents" / "skills" / "kevkolin" / "scripts"
SHARED_DIR = BASE_DIR / ".agents" / "skills" / "_shared"

for p in (str(KEVKOLIN_DIR), str(SHARED_DIR)):
    if p not in sys.path:
        sys.path.insert(0, p)

import editorial_http
import source_catalog


@tier(1)
@feature(30)
class TestF30SourceCatalogScanToday(E2ETestCase):
    """Tier 1 tests for source_catalog.py --scan-today radar functionality."""

    def setUp(self) -> None:
        super().setUp()
        self.fixtures_dir = Path(__file__).resolve().parents[1] / "fixtures" / "feeds"
        self.script_path = str(KEVKOLIN_DIR / "source_catalog.py")

    def test_f30_scan_today_discovers_today_rss_items(self) -> None:
        """RSS feed with today items extracts title, url, and ISO pub_date."""
        rss_xml = (self.fixtures_dir / "rss_diyarbakir_today.xml").read_text(encoding="utf-8")

        with patch.object(editorial_http, "fetch_html", return_value=(200, rss_xml, {})):
            items = source_catalog.scan_single_feed("Amed Haber", "https://example.com/rss.xml", date(2026, 11, 11))

        self.assertEqual(len(items), 2)
        self.assertEqual(items[0]["title"], "Diyarbakır'da Ekolojik Kent Parkının İlk Etabı Açıldı")
        self.assertIn("2026-11-11", items[0]["pub_date_iso"])

    def test_f30_scan_today_filters_out_stale_feed_items(self) -> None:
        """RSS feed containing only older articles returns an empty list for today."""
        stale_xml = (self.fixtures_dir / "rss_diyarbakir_stale.xml").read_text(encoding="utf-8")

        with patch.object(editorial_http, "fetch_html", return_value=(200, stale_xml, {})):
            items = source_catalog.scan_single_feed("Stale Outlet", "https://example.com/stale.xml", date(2026, 11, 11))

        self.assertEqual(len(items), 0, "Stale feed items must be filtered out")

    def test_f30_scan_today_sitemap_xml_parsing(self) -> None:
        """News sitemap XML with <news:publication_date> is correctly parsed."""
        sitemap_xml = (self.fixtures_dir / "sitemap_news_today.xml").read_text(encoding="utf-8")

        with patch.object(editorial_http, "fetch_html", return_value=(200, sitemap_xml, {})):
            items = source_catalog.scan_single_feed("Sitemap Source", "https://example.com/sitemap.xml", date(2026, 11, 11))

        self.assertEqual(len(items), 2)
        self.assertTrue(all("2026-11-11" in it["pub_date_iso"] for it in items))

    def test_f30_scan_today_category_filter(self) -> None:
        """Category filter restricts source catalog to sources in specified category."""
        res = self.run_python([
            self.script_path,
            "--list-sources",
            "--category", "amed_diyarbakir",
            "--json",
        ])
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertIn("amed_diyarbakir", data)
        cat_data = data["amed_diyarbakir"]
        sources = cat_data.get("sources", [])
        self.assertTrue(len(sources) > 0)
        # Verify specific amed_diyarbakir sources are present
        names = [s.get("name") for s in sources]
        self.assertIn("Amida Haber", names)

    def test_f30_scan_today_resilience_to_feed_error(self) -> None:
        """Individual feed failure (e.g. HTTP 500) returns empty list and does not raise an exception."""
        with patch.object(editorial_http, "fetch_html", return_value=(500, "", {"error": "Server error"})):
            items = source_catalog.scan_single_feed("Broken Feed", "https://example.com/broken.xml", date(2026, 11, 11))

        self.assertEqual(items, [])
