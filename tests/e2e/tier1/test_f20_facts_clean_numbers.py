"""Feature 20: verify_facts_and_links.py Clean Number & Entity Extraction.

Validates that non-content tags (<script>, <style>) do not leak numbers into verification,
exact normalized numeric equality is enforced (no false-positive substring matches),
and percentage/scaled/thousands separators are correctly normalized.
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
@feature(20)
class TestF20FactsCleanNumbers(E2ETestCase):
    """Tier 1 tests for clean number extraction and normalization."""

    def setUp(self) -> None:
        super().setUp()
        self.auditor = kevrast.KevrastAuditor()

    def test_f20_strip_non_content_removes_script_and_style_numbers(self) -> None:
        """Numbers inside <script> and <style> tags are completely stripped and ignored."""
        html = (
            "<script>const chunkId = 88; const port = 3000;</script>"
            "<style>.col-42 { width: 42%; }</style>"
            "<p>Park alanında 120 adet fidan dikildi.</p>"
        )
        cleaned = kevrast.strip_non_content_tags(html)
        nums = self.auditor.extract_distinctive_numbers(cleaned)
        self.assertNotIn("88", nums)
        self.assertNotIn("3000", nums)
        self.assertNotIn("42", nums)
        self.assertIn("120", nums)

    def test_f20_exact_normalized_numeric_equality(self) -> None:
        """Citing paragraph claiming 45 is not satisfied by substring 1450 or 545."""
        citing_text = "Proje alanında 45 adet fidan dikildi."
        target_html = "<p>Bölgede 1450 fidan ve 545 ağaç yer alıyor.</p>"

        result = self.auditor.anchoring(citing_text, target_html)
        self.assertEqual(result.get("status"), "block")
        self.assertIn("45", result.get("missing_numbers", []))

    def test_f20_percentage_normalization_equivalence(self) -> None:
        """Percentage notations (yüzde 80, 80%, 80 percent) match symmetrically."""
        citing_text = "Projeye halkın yüzde 80 katılım sağladığı açıklandı."
        target_html = "<p>According to survey data, 80 percent of residents participated.</p>"

        result = self.auditor.anchoring(citing_text, target_html)
        self.assertEqual(result.get("status"), "pass")
        self.assertIn("1/1", result.get("note", ""))

    def test_f20_scaled_numeric_normalization(self) -> None:
        """Scaled Turkish numeric phrases like 1.5 milyon normalize to 1500000."""
        citing_text = "Proje için 1.5 milyon lira ayrıldı."
        target_html = "<p>Ayrılan toplam bütçe 1500000 liradır.</p>"

        result = self.auditor.anchoring(citing_text, target_html)
        self.assertEqual(result.get("status"), "pass")
        self.assertIn("1/1", result.get("note", ""))

    def test_f20_thousands_separator_normalization(self) -> None:
        """Both dot and comma thousands separators normalize to uniform integer string."""
        self.assertEqual(kevrast.normalize_number("12,500"), "12500")
        self.assertEqual(kevrast.normalize_number("12.500"), "12500")
        self.assertEqual(kevrast.normalize_number("1,000,000"), "1000000")
        self.assertEqual(kevrast.normalize_number("1.000.000"), "1000000")
