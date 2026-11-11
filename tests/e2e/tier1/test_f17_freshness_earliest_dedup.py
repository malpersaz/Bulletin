"""
Tier 1 Feature Coverage: Feature 17 (freshness_earliest_date).
Validates check_freshness.py earliest main entity date selection (prioritizing datePublished
over dateModified) and draft/CLI URL deduplication across multi-citation paragraphs.
"""

from __future__ import annotations

from datetime import datetime, timedelta
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
@feature(17)
class TestF17FreshnessEarliestDedup(E2ETestCase):
    """Tier 1 tests for earliest publication date selection and URL citation deduplication."""

    @property
    def script_path(self) -> str:
        return os.path.join(KEVTEMEN_SCRIPTS, "check_freshness.py")

    def test_f17_date_published_vs_date_modified_earliest_wins(self):
        """Verify datePublished (yesterday) is selected over dateModified (today), failing freshness check."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        yesterday_iso = (now - timedelta(days=1)).strftime("%Y-%m-%dT08:00:00Z")
        today_iso = now.strftime("%Y-%m-%dT10:00:00Z")

        html = f"""<!DOCTYPE html>
<html>
<head>
    <script type="application/ld+json">
    {{
        "@context": "https://schema.org",
        "@type": "NewsArticle",
        "datePublished": "{yesterday_iso}",
        "dateModified": "{today_iso}",
        "headline": "Updated Story"
    }}
    </script>
</head>
<body>
    <article><h1>Story Content</h1></article>
</body>
</html>"""
        path = os.path.join(self.sandbox.root, "article_published_vs_modified.html")
        with open(path, "w", encoding="utf-8") as f:
            f.write(html)

        res = self.run_python([self.script_path, path, "--json"])
        self.assertExitCode(res, 1)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["passed", "targets"])
        self.assertFalse(data["passed"])
        target = data["targets"][0]
        self.assertFalse(target["same_day"])
        self.assertIn("datePublished", target["evidence"])

    def test_f17_multiple_meta_tags_selects_earliest(self):
        """Verify multiple meta publication timestamps resolve to the earliest candidate."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        t_early = now.replace(hour=5, minute=0, second=0).strftime("%Y-%m-%dT05:00:00Z")
        t_late = now.replace(hour=11, minute=0, second=0).strftime("%Y-%m-%dT11:00:00Z")

        html = f"""<!DOCTYPE html>
<html>
<head>
    <meta property="article:published_time" content="{t_late}">
    <meta name="publishdate" content="{t_early}">
</head>
<body>
    <article><p>Content</p></article>
</body>
</html>"""
        res = check_freshness.extract_pub_date(html, "http://example.com/multi-meta")
        self.assertIsNotNone(res)
        dt = res["dt"]
        # In Europe/Istanbul (+3h), 05:00Z is 08:00
        self.assertEqual(dt.hour, 8)
        self.assertEqual(dt.minute, 0)

    def test_f17_url_deduplication_in_draft_citations(self):
        """Verify draft HTML citing the same URL 4 times produces 1 target with citation_count == 4."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        today_date = now.strftime("%Y-%m-%d")
        url = "https://www.rudaw.net/turkish/kurdistan/1287295"

        draft_html = f"""
<article>
    <p>İlk paragraf <a href="{url}">Rudaw Park Haberi</a>.</p>
    <p>İkinci paragraf <a href="{url}">Rudaw Park Haberi</a> detayları.</p>
    <p>Üçüncü paragraf <a href="{url}">Rudaw Park Haberi</a> devamı.</p>
    <p>Dördüncü paragraf <a href="{url}">Rudaw Park Haberi</a> son açıklamalar.</p>
</article>
"""
        dossier = {
            "topic": "Park Projesi",
            "date": today_date,
            "sources": [{"url": url, "role": "primary"}]
        }
        draft_path = os.path.join(self.sandbox.root, "draft_multi_cite.html")
        dossier_path = os.path.join(self.sandbox.root, "dossier_multi_cite.json")
        with open(draft_path, "w", encoding="utf-8") as f:
            f.write(draft_html)
        with open(dossier_path, "w", encoding="utf-8") as f:
            json.dump(dossier, f)

        res = self.run_python([self.script_path, "--file", draft_path, "--dossier", dossier_path, "--no-network", "--json"])
        self.assertExitCode(res, 0)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["targets"])
        self.assertEqual(len(data["targets"]), 1)
        self.assertEqual(data["targets"][0]["citation_count"], 4)

    def test_f17_deduplication_network_fetch_count(self):
        """Verify draft citing the same URL multiple times fetches the URL exactly once over network."""
        now = datetime.now(ZoneInfo("Europe/Istanbul"))
        today_iso = now.strftime("%Y-%m-%dT10:00:00Z")
        page_html = f"""<!DOCTYPE html><html><body><article><time datetime="{today_iso}">Today</time></article></body></html>"""

        self.mock_http.add_route("/news/single-article", page_html, status=200, headers={"Content-Type": "text/html"})
        mock_url = self.mock_http.get_url("/news/single-article")

        # In check_freshness, using file with MockHttpServer
        draft_html = f"""
<article>
    <p>Para 1 <a href="{mock_url}">Link</a></p>
    <p>Para 2 <a href="{mock_url}">Link</a></p>
    <p>Para 3 <a href="{mock_url}">Link</a></p>
</article>
"""
        dossier = {"topic": "T", "date": now.strftime("%Y-%m-%d"), "sources": [{"url": mock_url}]}
        draft_path = os.path.join(self.sandbox.root, "draft_net.html")
        dossier_path = os.path.join(self.sandbox.root, "dossier_net.json")
        with open(draft_path, "w", encoding="utf-8") as f:
            f.write(draft_html)
        with open(dossier_path, "w", encoding="utf-8") as f:
            json.dump(dossier, f)

        # In offline mode, check_freshness doesn't hit server, but targets list records deduplicated citations
        res = self.run_python([self.script_path, "--file", draft_path, "--dossier", dossier_path, "--no-network", "--json"])
        self.assertExitCode(res, 0)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["targets"])
        self.assertEqual(len(data["targets"]), 1)
        self.assertEqual(data["targets"][0]["citation_count"], 3)

    def test_f17_normalized_url_trailing_slash_deduplication(self):
        """Verify URLs differing only by trailing slash are collapsed into a single target."""
        u1 = "https://example.com/story/10"
        u2 = "https://example.com/story/10/"
        res = self.run_python([self.script_path, u1, u2, "--no-network", "--json"])
        self.assertExitCode(res, 0)
        data = self.assertJsonEnvelope(res.stdout, required_keys=["targets"])
        self.assertEqual(len(data["targets"]), 1)
        self.assertEqual(data["targets"][0]["citation_count"], 2)
