"""Feature 23: verify_facts_and_links.py Turkish Morphology & Attribution.

Validates Turkish Unicode lowercasing (tr_fold handling dotted/dotless I),
institutional suffix stripping (stem_institutional), institutional entity anchoring,
and expanded attribution linkage checks (outlet attribution requiring link vs speech attribution).
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
@feature(23)
class TestF23FactsTurkishMorphology(E2ETestCase):
    """Tier 1 tests for Turkish morphology handling and attribution linkage rules."""

    def setUp(self) -> None:
        super().setUp()
        self.auditor = kevrast.KevrastAuditor()
        self.script_path = str(KEVRAST_DIR / "verify_facts_and_links.py")
        self.fixtures_dir = Path(__file__).resolve().parents[1] / "fixtures" / "editorial"

    def test_f23_tr_fold_unicode_dotted_dotless_i(self) -> None:
        """Turkish case folding tr_fold handles dotted and dotless I without combining accents."""
        self.assertEqual(kevrast.tr_fold("DİYARBAKIR"), "diyarbakır")
        self.assertEqual(kevrast.tr_fold("İSTANBUL"), "istanbul")
        self.assertEqual(kevrast.tr_fold("ÇATIŞMA"), "çatışma")
        self.assertEqual(kevrast.tr_fold("ŞIRNAK"), "şırnak")
        self.assertNotIn("\u0307", kevrast.tr_fold("İSTANBUL"))

    def test_f23_stem_institutional_case_suffixes(self) -> None:
        """Inflected institutional nouns strip dative, ablative, and genitive case suffixes."""
        self.assertEqual(kevrast.stem_institutional("Belediyesine"), "belediyesi")
        self.assertEqual(kevrast.stem_institutional("Bakanlığından"), "bakanlığı")
        self.assertEqual(kevrast.stem_institutional("Valiliğinin"), "valiliği")
        self.assertEqual(kevrast.stem_institutional("Müdürlüğüne"), "müdürlüğü")
        self.assertEqual(kevrast.stem_institutional("Odasına"), "odası")

    def test_f23_institutional_entity_anchoring_match(self) -> None:
        """Citing paragraph mentioning stemmed institution matches uninflected institution in target."""
        citing_text = "Diyarbakır Büyükşehir Belediyesine yönelik proje hazırlandı."
        target_html = "<html><body><p>Diyarbakır Büyükşehir Belediyesi tarafından onaylandı.</p></body></html>"

        res = self.auditor.anchoring(citing_text, target_html)
        self.assertEqual(res.get("status"), "pass")
        self.assertIn("Entity match", res.get("note", ""))

    def test_f23_expanded_outlet_attribution_requires_link(self) -> None:
        """Paragraph with outlet attribution (e.g. 'Paylaşılan bilgilere göre') lacks link and triggers R4."""
        draft_unlinked = (
            "<p>Diyarbakır merkezinde yeni proje başladı.</p>\n"
            "<p>Paylaşılan bilgilere göre çalışmalar hızla ilerliyor.</p>"
        )
        dossier = {"slug": "test", "sources": [{"url": "https://example.com/a/b", "role": "primary"}]}

        report = self.auditor.audit_draft(draft_unlinked, dossier, skip_network=True)
        linkage = report.get("citation_linkage", [])
        self.assertTrue(len(linkage) > 0, "Expected citation linkage violation")
        self.assertTrue(
            any("Attribution phrase lacks hyperlink (R4)" in item.get("issue", "") for item in linkage),
            f"Expected R4 issue in linkage, got: {linkage}",
        )
        self.assertEqual(report.get("status"), "BLOCKED")

    def test_f23_speech_attribution_preceding_link_allowed(self) -> None:
        """Direct speech attribution is allowed without link if preceding paragraph contains citation."""
        draft_speech = (
            '<p><a href="https://example.com/a/b">Rudaw\'ın aktardığına göre</a> yetkililer incelemede bulundu.</p>\n'
            "<p>Yetkili, projenin tamamlandığını söyledi.</p>"
        )
        dossier = {"slug": "test", "sources": [{"url": "https://example.com/a/b", "role": "primary"}]}

        report = self.auditor.audit_draft(draft_speech, dossier, skip_network=True)
        linkage = report.get("citation_linkage", [])
        # No unlinked speech attribution issue should be registered
        speech_issues = [item for item in linkage if "Direct-speech attribution" in item.get("issue", "")]
        self.assertEqual(len(speech_issues), 0, f"Speech attribution should be allowed: {speech_issues}")
