"""Feature 28: news.twig Hero Figure & JSON-LD.

Validates semantic HTML and JSON-LD schema in public news template:
- Hero image uses semantic <figure class="detail-hero"> instead of <div>
- <figcaption class="news-hero-caption"> is rendered conditionally when image_credit is present
- alt attribute falls back to d.name when d.image_alt is empty
- JSON-LD ImageObject renders caption and creditText schema properties
- Full compliance with AGENTS.md Rule 1 Code Separation (zero inline styles or scripts)
"""

from __future__ import annotations

from pathlib import Path
import re

from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(28)
class TestF28TwigHeroFigureJsonld(E2ETestCase):
    """Tier 1 tests for semantic hero figure, figcaption, and JSON-LD schema in news.twig."""

    def setUp(self) -> None:
        super().setUp()
        self.twig_path = (
            Path(__file__).resolve().parents[3]
            / "templates"
            / "amed"
            / "index"
            / "mod_news"
            / "news.twig"
        )
        self.twig_content = self.twig_path.read_text(encoding="utf-8")

    def test_f28_hero_figure_semantic_element(self) -> None:
        """news.twig renders hero image in semantic <figure class="detail-hero"> and forbids div."""
        self.assertIn('<figure class="detail-hero">', self.twig_content)
        self.assertNotIn('<div class="detail-hero">', self.twig_content)

    def test_f28_figcaption_rendered_when_credit_present(self) -> None:
        """<figcaption class="news-hero-caption"> is rendered when image_credit is populated."""
        pattern = r'<figcaption\s+class="news-hero-caption">\s*\{\{\s*d\.image_credit\s*\}\}\s*</figcaption>'
        self.assertTrue(
            re.search(pattern, self.twig_content),
            "news.twig must contain <figcaption class=\"news-hero-caption\">{{ d.image_credit }}</figcaption>",
        )

    def test_f28_figcaption_conditional_guard(self) -> None:
        """<figcaption> is strictly enclosed within an {% if d.image_credit ... %} conditional block."""
        match = re.search(
            r'\{%\s*if\s+d\.image_credit\s+is\s+defined\s+and\s+d\.image_credit\s+is\s+not\s+empty\s*%\}\s*<figcaption',
            self.twig_content,
        )
        self.assertIsNotNone(match, "figcaption must be wrapped in condition guarding non-empty image_credit")

    def test_f28_img_alt_prefers_image_alt_over_name(self) -> None:
        """Hero <img> tag uses d.image_alt|default(d.name) for accessible alt description."""
        self.assertIn('alt="{{ d.image_alt|default(d.name) }}"', self.twig_content)
        # Verify OpenGraph and Twitter meta tags also use image_alt
        self.assertIn('property="og:image:alt"', self.twig_content)
        self.assertIn('name="twitter:image:alt"', self.twig_content)

    def test_f28_jsonld_image_object_credit_text(self) -> None:
        """JSON-LD schema includes ImageObject with caption and creditText properties."""
        self.assertIn('"caption": {{ (d.image_alt|default(d.name))|json_ld|raw }}', self.twig_content)
        self.assertIn('"creditText": {{ d.image_credit|json_ld|raw }}', self.twig_content)

    def test_f28_code_separation_compliance(self) -> None:
        """news.twig has zero inline styles and zero inline event handlers per AGENTS.md Rule 1."""
        res = self.run_python([
            "tools/code_separation_guard.py",
            "--path", str(self.twig_path),
        ])
        self.assertExitCode(res, 0)
        self.assertIn("0 violations found", res.stdout)
