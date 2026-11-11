"""Feature 21: verify_facts_and_links.py Vendor Single-Segment Allowlist.

Validates that vendor/product download and release single-segment URLs are allowed
only when role is 'product' and post-fetch verification passes (version string present,
<= 2 <article> elements), while generic section fronts and unallowlisted single segments fail.
"""

from __future__ import annotations

from pathlib import Path
import sys

from harness.base import E2ETestCase, feature, tier

KEVRAST_DIR = Path(__file__).resolve().parents[3] / ".agents" / "skills" / "kevrast" / "scripts"
if str(KEVRAST_DIR) not in sys.path:
    sys.path.insert(0, str(KEVRAST_DIR))

import verify_facts_and_links as kevrast


@tier(1)
@feature(21)
class TestF21FactsVendorAllowlist(E2ETestCase):
    """Tier 1 tests for vendor single-segment allowlist."""

    def setUp(self) -> None:
        super().setUp()
        self.auditor = kevrast.KevrastAuditor()

    def test_f21_allowlisted_download_slug_with_version_passes(self) -> None:
        """Vendor download slug with role='product' and version string in body passes."""
        url = "https://antigravity.google/download"
        norm = self.auditor.norm_url(url)
        self.auditor._page_cache[norm] = {
            "url": url,
            "status_code": 200,
            "final_url": url,
            "html": "<html><body><h1>Download Antigravity</h1><p>Current version is v1.0.63 for Linux</p></body></html>",
            "live": True,
            "reachable": True,
            "unreachable_reason": None,
            "note": None,
        }

        entry = self.auditor.audit_url(url, citing_text="Sürüm v1.0.63 yayınlandı", role="product")
        precision = entry.get("precision", {})
        self.assertTrue(precision.get("precise"))
        self.assertEqual(precision.get("kind"), "vendor_product_page")

    def test_f21_non_allowlisted_single_segment_rejected(self) -> None:
        """Generic single-segment section fronts (e.g. /news, /politics) are rejected."""
        res_news = self.auditor.classify_precision("https://example.com/news", role="primary")
        self.assertFalse(res_news.get("precise"))
        self.assertEqual(res_news.get("kind"), "section_front")

        res_politics = self.auditor.classify_precision("https://example.com/politics", role="primary")
        self.assertFalse(res_politics.get("precise"))
        self.assertEqual(res_politics.get("kind"), "section_front")

    def test_f21_allowlisted_slug_missing_version_fails_conditional_check(self) -> None:
        """Allowlisted vendor slug lacking a version or anchor string demotes to section_front."""
        url = "https://vendor.org/releases"
        norm = self.auditor.norm_url(url)
        self.auditor._page_cache[norm] = {
            "url": url,
            "status_code": 200,
            "final_url": url,
            "html": "<html><body><h1>Releases</h1><p>Generic download text without version</p></body></html>",
            "live": True,
            "reachable": True,
            "unreachable_reason": None,
            "note": None,
        }

        entry = self.auditor.audit_url(url, citing_text="İndirme bağlantısı", role="product")
        precision = entry.get("precision", {})
        self.assertFalse(precision.get("precise"))
        self.assertEqual(precision.get("kind"), "section_front")

    def test_f21_allowlisted_slug_excessive_articles_rejected(self) -> None:
        """Vendor slug with > 2 <article> elements indicates blog/portal and is demoted."""
        url = "https://vendor.org/releases"
        norm = self.auditor.norm_url(url)
        self.auditor._page_cache[norm] = {
            "url": url,
            "status_code": 200,
            "final_url": url,
            "html": "<html><body><article>A1</article><article>A2</article><article>A3</article></body></html>",
            "live": True,
            "reachable": True,
            "unreachable_reason": None,
            "note": None,
        }

        entry = self.auditor.audit_url(url, citing_text="İndirme bağlantısı", role="product")
        precision = entry.get("precision", {})
        self.assertFalse(precision.get("precise"))
        self.assertEqual(precision.get("kind"), "section_front")

    def test_f21_primary_role_requires_deep_page(self) -> None:
        """Allowlisted vendor slugs must NOT pass when passed with role='primary'."""
        res = self.auditor.classify_precision("https://vendor.org/download", role="primary")
        self.assertFalse(res.get("precise"))
        self.assertEqual(res.get("kind"), "section_front")
