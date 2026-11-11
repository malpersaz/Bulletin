"""Feature 19: verify_facts_and_links.py All-Paragraph Verification.

Verifies that every citing paragraph in a news draft is independently evaluated,
ensuring hallucinations in later paragraphs (e.g. Paragraph 4) fail the audit,
and single-paragraph drafts remain fully compatible.
"""

from __future__ import annotations

import json
from pathlib import Path
import sys
from unittest.mock import patch

from harness.base import E2ETestCase, feature, tier

# Add kevrast and shared scripts to sys.path
BASE_DIR = Path(__file__).resolve().parents[3]
KEVRAST_DIR = BASE_DIR / ".agents" / "skills" / "kevrast" / "scripts"
SHARED_DIR = BASE_DIR / ".agents" / "skills" / "_shared"

for p in (str(KEVRAST_DIR), str(SHARED_DIR)):
    if p not in sys.path:
        sys.path.insert(0, p)

import editorial_http
import verify_facts_and_links as kevrast


@tier(1)
@feature(19)
class TestF19FactsAllParagraphs(E2ETestCase):
    """Tier 1 tests for all-paragraph fact checking in kevrast."""

    def setUp(self) -> None:
        super().setUp()
        self.script_path = str(KEVRAST_DIR / "verify_facts_and_links.py")
        self.fixtures_dir = Path(__file__).resolve().parents[1] / "fixtures" / "editorial"

    def test_f19_all_paragraphs_evaluated_distinctly(self) -> None:
        """Paragraph 2 and Paragraph 3 both citing the same URL are audited as distinct entries."""
        draft_path = self.fixtures_dir / "draft_with_all_paragraphs_valid.html"
        dossier_path = self.fixtures_dir / "dossier_today_rudaw_park.json"

        auditor = kevrast.KevrastAuditor()
        draft_content = draft_path.read_text(encoding="utf-8")
        dossier_data = json.loads(dossier_path.read_text(encoding="utf-8"))

        report = auditor.audit_draft(draft_content, dossier_data, skip_network=True)
        url_audit = report.get("url_audit", [])
        self.assertGreaterEqual(len(url_audit), 2, "Expected at least 2 citing paragraph audits")
        indices = [item.get("paragraph_index") for item in url_audit]
        self.assertIn(2, indices, "Paragraph 2 must be present in url_audit")
        self.assertIn(3, indices, "Paragraph 3 must be present in url_audit")

    def test_f19_hallucination_in_paragraph_4_fails_audit(self) -> None:
        """A hallucinated figure in paragraph 4 fails the audit even if P2 and P3 are valid."""
        draft_p4_path = self.fixtures_dir / "draft_with_p4_hallucinated_num.html"
        dossier_path = self.fixtures_dir / "dossier_today_rudaw_park.json"
        mock_rudaw_path = self.fixtures_dir / "mock_rudaw_article_1287295.html"

        auditor = kevrast.KevrastAuditor()
        draft_content = draft_p4_path.read_text(encoding="utf-8")
        dossier_data = json.loads(dossier_path.read_text(encoding="utf-8"))
        target_html = mock_rudaw_path.read_text(encoding="utf-8")

        url = dossier_data["sources"][0]["url"]
        norm = auditor.norm_url(url)
        auditor._page_cache[norm] = {
            "url": url,
            "status_code": 200,
            "final_url": url,
            "html": target_html,
            "live": True,
            "reachable": True,
            "unreachable_reason": None,
            "note": None,
        }

        report = auditor.audit_draft(draft_content, dossier_data, skip_network=False)
        self.assertFalse(report.get("passed"), "Draft with hallucinated figure must not pass")
        self.assertIn(report.get("status"), ["FAILED", "BLOCKED"])

        # Locate paragraph 4 in url_audit
        p4_items = [u for u in report.get("url_audit", []) if u.get("paragraph_index") == 4]
        self.assertTrue(len(p4_items) > 0, "Paragraph 4 must be captured in url_audit")
        anchoring = p4_items[0].get("anchoring", {})
        self.assertEqual(anchoring.get("status"), "block")
        missing_nums = anchoring.get("missing_numbers", [])
        self.assertTrue(
            any("999" in str(num) or "550" in str(num) for num in missing_nums),
            f"Expected missing number in Paragraph 4, got: {missing_nums}",
        )

    def test_f19_all_paragraphs_valid_passes(self) -> None:
        """When all citing paragraphs are backed by target page figures, anchoring passes."""
        # Create a 2-paragraph draft where both paragraphs cite numbers present in target page
        target_html = """<html><body>
        <h1>Park Projesi</h1>
        <p>Proje kapsamında 45 dönümlük arazi üzerine 120 adet yetişkin ağaç dikilmiştir.</p>
        </body></html>"""
        draft_content = """
        <p><a href="https://example.com/valid/article">Rudaw haberine göre</a> park alanı 45 dönümlük arazi üzerine kuruldu.</p>
        <p><a href="https://example.com/valid/article">Aynı kaynakta</a> toplamda 120 adet yetişkin ağaç dikildiği belirtildi.</p>
        """
        dossier_data = {
            "slug": "valid-all-p",
            "sources": [{"url": "https://example.com/valid/article", "role": "primary", "source_lang": "tr"}],
        }

        auditor = kevrast.KevrastAuditor()
        url = "https://example.com/valid/article"
        norm = auditor.norm_url(url)
        auditor._page_cache[norm] = {
            "url": url,
            "status_code": 200,
            "final_url": url,
            "html": target_html,
            "live": True,
            "reachable": True,
            "unreachable_reason": None,
            "note": None,
        }

        report = auditor.audit_draft(draft_content, dossier_data, skip_network=False)
        for u in report.get("url_audit", []):
            self.assertEqual(u.get("anchoring", {}).get("status"), "pass")
        self.assertEqual(report.get("status"), "PASSED")
        self.assertTrue(report.get("passed"))

    def test_f19_explain_mode_locates_paragraph_index(self) -> None:
        """Running audit in explain mode produces diagnostic output identifying the paragraph index."""
        draft_p4_path = self.fixtures_dir / "draft_with_p4_hallucinated_num.html"
        dossier_path = self.fixtures_dir / "dossier_today_rudaw_park.json"

        # Direct test with explain flag via CLI using --no-network to check structural diagnostics
        res = self.run_python([
            self.script_path,
            "--file", str(draft_p4_path),
            "--dossier", str(dossier_path),
            "--no-network",
            "--explain",
            "--json",
        ])
        report = json.loads(res.stdout)
        url_audit = report.get("url_audit", [])
        indices = [item.get("paragraph_index") for item in url_audit]
        self.assertIn(4, indices, "Diagnostics must locate paragraph 4 in url_audit")

    def test_f19_single_paragraph_draft_compatibility(self) -> None:
        """Draft with a single paragraph containing a link is fully supported."""
        single_p_draft = '<p>Diyarbakır Bağlar ilçesinde 45 dönümlük park açıldı: <a href="https://example.com/story">Kaynak</a></p>'
        dossier_data = {
            "slug": "single-p-test",
            "sources": [
                {
                    "url": "https://example.com/story",
                    "role": "primary",
                    "source_lang": "tr",
                }
            ],
        }

        auditor = kevrast.KevrastAuditor()
        report = auditor.audit_draft(single_p_draft, dossier_data, skip_network=True)
        url_audit = report.get("url_audit", [])
        self.assertEqual(len(url_audit), 1)
        self.assertEqual(url_audit[0].get("paragraph_index"), 1)
