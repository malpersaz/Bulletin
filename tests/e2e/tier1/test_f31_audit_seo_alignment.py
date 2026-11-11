"""Feature 31: audit_seo.py Alignment.

Validates kevseo Google SEO and Semantic HTML auditor:
- Clean editorial draft with compliant headline (40-70) and deck summary (90-120) passes
- Summary shorter than 90 characters triggers deck_status=FAILED
- Headline longer than 70 characters triggers headline_status=FAILED
- Fewer than 3 <h2> headings under --strict-editorial triggers insufficient_h2
- Inline style='...' attributes trigger critical code separation violation
"""

from __future__ import annotations

import json
from pathlib import Path

from harness.base import E2ETestCase, feature, tier

BASE_DIR = Path(__file__).resolve().parents[3]
KEVSEO_DIR = BASE_DIR / ".agents" / "skills" / "kevseo" / "scripts"


@tier(1)
@feature(31)
class TestF31AuditSeoAlignment(E2ETestCase):
    """Tier 1 tests for audit_seo.py editorial rules and HTML architecture audits."""

    def setUp(self) -> None:
        super().setUp()
        self.script_path = str(KEVSEO_DIR / "audit_seo.py")
        self.fixtures_dir = Path(__file__).resolve().parents[1] / "fixtures" / "editorial"

    def test_f31_seo_audit_clean_editorial_draft_passes(self) -> None:
        """Valid draft with >=1500 chars, 53-char headline, and 105-char summary passes."""
        base_html = (self.fixtures_dir / "draft_today_diyarbakir_park.html").read_text(encoding="utf-8")
        full_html = base_html + "<p>" + (
            "Diyarbakır kent merkezinde sürdürülen çevre düzenleme ve peyzaj çalışmaları kapsamında "
            "ekolojik park alanı vatandaşların dinlenme ve sosyal aktivite ihtiyaçlarına yanıt verecek "
            "modern donatılarla zenginleştirilmektedir. " * 3
        ) + "</p>"
        draft_path = self.sandbox.path("clean_draft.html")
        with open(draft_path, "w", encoding="utf-8") as f:
            f.write(full_html)

        res = self.run_python([
            self.script_path,
            "--file", draft_path,
            "--headline", "Diyarbakır'da Ekolojik Kent Parkının İlk Etabı Açıldı",
            "--summary", "Diyarbakır merkezinde 45 dönümlük alanda inşa edilen ekolojik park bugün halkın hizmetine törenle açıldı.",
            "--json",
        ])
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertEqual(data.get("status"), "PASSED")
        meta = data.get("meta_analysis", {})
        self.assertEqual(meta.get("headline_status"), "PASSED")
        self.assertEqual(meta.get("deck_status"), "PASSED")

    def test_f31_seo_audit_fails_short_summary(self) -> None:
        """Summary under 90 characters triggers deck_status=FAILED and non-zero exit code."""
        base_html = (self.fixtures_dir / "draft_today_diyarbakir_park.html").read_text(encoding="utf-8")
        draft_path = self.sandbox.path("short_summary_draft.html")
        with open(draft_path, "w", encoding="utf-8") as f:
            f.write(base_html)

        res = self.run_python([
            self.script_path,
            "--file", draft_path,
            "--headline", "Diyarbakır'da Ekolojik Kent Parkının İlk Etabı Açıldı",
            "--summary", "Kısa özet metni doksan karakterden daha azdır.",
            "--json",
        ])
        self.assertExitCode(res, 1)
        data = json.loads(res.stdout)
        self.assertEqual(data.get("meta_analysis", {}).get("deck_status"), "FAILED")

    def test_f31_seo_audit_fails_long_headline(self) -> None:
        """Headline over 70 characters triggers headline_status=FAILED and non-zero exit code."""
        base_html = (self.fixtures_dir / "draft_today_diyarbakir_park.html").read_text(encoding="utf-8")
        draft_path = self.sandbox.path("long_headline_draft.html")
        with open(draft_path, "w", encoding="utf-8") as f:
            f.write(base_html)

        long_headline = "Diyarbakır Büyükşehir Belediyesi Tarafından Yapılan Ekolojik Kent Parkı Bugün Törenle Halkın Hizmetine Açıldı"
        self.assertGreater(len(long_headline), 70)

        res = self.run_python([
            self.script_path,
            "--file", draft_path,
            "--headline", long_headline,
            "--summary", "Diyarbakır merkezinde 45 dönümlük alanda inşa edilen ekolojik park bugün halkın hizmetine törenle açıldı.",
            "--json",
        ])
        self.assertExitCode(res, 1)
        data = json.loads(res.stdout)
        self.assertEqual(data.get("meta_analysis", {}).get("headline_status"), "FAILED")

    def test_f31_seo_audit_fails_insufficient_h2_headings(self) -> None:
        """Draft with fewer than 3 <h2> headings under --strict-editorial reports insufficient_h2."""
        single_h2_html = (
            "<p>Giriş metni ve haber detayları.</p>"
            "<h2>Tek Başlık</h2>"
            "<p>" + ("Uzun metin gövdesi ve detaylar içeren açıklamalar. " * 80) + "</p>"
        )
        draft_path = self.sandbox.path("single_h2_draft.html")
        with open(draft_path, "w", encoding="utf-8") as f:
            f.write(single_h2_html)

        res = self.run_python([
            self.script_path,
            "--file", draft_path,
            "--headline", "Diyarbakır'da Ekolojik Kent Parkının İlk Etabı Açıldı",
            "--summary", "Diyarbakır merkezinde 45 dönümlük alanda inşa edilen ekolojik park bugün halkın hizmetine törenle açıldı.",
            "--strict-editorial",
            "--json",
        ])
        self.assertExitCode(res, 1)
        data = json.loads(res.stdout)
        issue_types = [issue.get("type") for issue in data.get("architecture_issues", [])]
        self.assertIn("insufficient_h2", issue_types)

    def test_f31_seo_audit_fails_inline_styles(self) -> None:
        """Draft containing inline style='...' attributes triggers inline_style_violation."""
        styled_html = (
            '<p style="color: red;">Biçimlendirilmiş metin</p>'
            "<h2>Birinci Başlık</h2><p>Metin 1</p>"
            "<h2>İkinci Başlık</h2><p>Metin 2</p>"
            "<h2>Üçüncü Başlık</h2><p>Metin 3</p>"
        )
        draft_path = self.sandbox.path("styled_draft.html")
        with open(draft_path, "w", encoding="utf-8") as f:
            f.write(styled_html)

        res = self.run_python([
            self.script_path,
            "--file", draft_path,
            "--headline", "Diyarbakır'da Ekolojik Kent Parkının İlk Etabı Açıldı",
            "--summary", "Diyarbakır merkezinde 45 dönümlük alanda inşa edilen ekolojik park bugün halkın hizmetine törenle açıldı.",
            "--json",
        ])
        self.assertExitCode(res, 1)
        data = json.loads(res.stdout)
        issue_types = [issue.get("type") for issue in data.get("architecture_issues", [])]
        self.assertIn("inline_style_violation", issue_types)
