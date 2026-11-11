"""Feature 22: verify_facts_and_links.py Cross-Language Anchoring.

Validates bilingual claim verification where Turkish draft paragraphs cite foreign-language
sources (e.g. English) with declared source_anchors ({source_term, tr_term}),
failing closed if terms are missing on either side or if foreign sources lack both anchors and figures.
"""

from __future__ import annotations

import json
from pathlib import Path
import sys

from harness.base import E2ETestCase, feature, tier

KEVRAST_DIR = Path(__file__).resolve().parents[3] / ".agents" / "skills" / "kevrast" / "scripts"
if str(KEVRAST_DIR) not in sys.path:
    sys.path.insert(0, str(KEVRAST_DIR))

import verify_facts_and_links as kevrast


@tier(1)
@feature(22)
class TestF22FactsCrossLanguage(E2ETestCase):
    """Tier 1 tests for cross-language fact checking and anchor validation."""

    def setUp(self) -> None:
        super().setUp()
        self.auditor = kevrast.KevrastAuditor()
        self.script_path = str(KEVRAST_DIR / "verify_facts_and_links.py")
        self.fixtures_dir = Path(__file__).resolve().parents[1] / "fixtures" / "editorial"

    def test_f22_bilingual_anchoring_both_terms_present_passes(self) -> None:
        """When source_term appears on foreign page and tr_term in draft, anchoring passes."""
        source_info = {
            "source_lang": "en",
            "source_anchors": [{"source_term": "test framework", "tr_term": "test çerçevesi"}],
        }
        citing_text = "Yeni test çerçevesi duyuruldu."
        target_html = "<p>This is a new test framework released today.</p>"

        res = self.auditor.anchoring(citing_text, target_html, source_info)
        self.assertEqual(res.get("status"), "pass")
        self.assertIn("Cross-language anchor match: 1 pair(s) verified", res.get("note", ""))

    def test_f22_source_term_missing_on_foreign_page_blocks(self) -> None:
        """When source_term is missing on foreign target page, audit blocks with error."""
        source_info = {
            "source_lang": "en",
            "source_anchors": [{"source_term": "test framework", "tr_term": "test çerçevesi"}],
        }
        citing_text = "Yeni test çerçevesi duyuruldu."
        target_html = "<p>Arbitrary english text without the target terminology.</p>"

        res = self.auditor.anchoring(citing_text, target_html, source_info)
        self.assertEqual(res.get("status"), "block")
        self.assertIn("source term(s) ['test framework'] not found on target page", res.get("note", ""))

    def test_f22_tr_term_missing_in_draft_blocks(self) -> None:
        """When source_term exists on page but tr_term is missing from citing paragraph, audit blocks."""
        source_info = {
            "source_lang": "en",
            "source_anchors": [{"source_term": "test framework", "tr_term": "test çerçevesi"}],
        }
        citing_text = "Bu haberde detaylar yer alıyor fakat terim kullanılmadı."
        target_html = "<p>This is a new test framework.</p>"

        res = self.auditor.anchoring(citing_text, target_html, source_info)
        self.assertEqual(res.get("status"), "block")
        self.assertIn("none of the declared source_anchors", res.get("note", ""))

    def test_f22_foreign_source_without_anchors_and_numbers_blocks(self) -> None:
        """Foreign language source without figures or declared anchors cannot be evaluated."""
        citing_text = "Detaylı rapor sunuldu."
        target_html = "<p>Arbitrary english text.</p>"

        res = self.auditor.anchoring(citing_text, target_html, {"source_lang": "en", "source_anchors": []})
        self.assertIn(res.get("status"), ["block", "review"])

    def test_f22_dossier_schema_validation_bilingual_anchors(self) -> None:
        """Validates that research dossier schema accepts and confirms bilingual source_anchors."""
        dossier_path = self.fixtures_dir / "dossier_cross_lang_english_docs.json"
        res = self.run_python([
            self.script_path,
            "--validate-dossier", str(dossier_path),
            "--json",
        ])
        self.assertExitCode(res, 0)
        report = json.loads(res.stdout)
        self.assertTrue(report.get("valid"))
