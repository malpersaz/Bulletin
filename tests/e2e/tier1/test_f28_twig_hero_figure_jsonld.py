"""Feature 28: news.twig Hero Figure & JSON-LD.

Validates semantic HTML and JSON-LD schema in public news template:
- Hero image uses semantic <figure class="detail-hero"> instead of <div>
- <figcaption class="news-hero-caption"> is rendered conditionally when image_credit is present
- alt attribute falls back to d.name when d.image_alt is empty
- JSON-LD ImageObject renders caption and creditText schema properties
- Full compliance with AGENTS.md Rule 1 Code Separation (zero inline styles or scripts)
"""

from __future__ import annotations

import json
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

    def test_f28_jsonld_image_object_google_license_metadata(self) -> None:
        """All three NewsArticle ImageObjects emit contentUrl, creator, copyrightNotice, license, and acquireLicensePage."""
        for prop in ('"contentUrl":', '"creator":', '"copyrightNotice":', '"license":', '"acquireLicensePage":'):
            self.assertGreaterEqual(
                self.twig_content.count(prop),
                3,
                f"news.twig must emit {prop} across all 3 ImageObject aspect variants",
            )

    def test_f28_helper_enriches_image_schema_metadata(self) -> None:
        """mod_news_helper::enrichImageSchemaMetadata derives truthful creator, creditText, copyrightNotice, and license URLs."""
        use_docker = self.php.is_docker_available()
        seed_cache_block = ""
        if not use_docker:
            seed_cache_block = """
            $cacheProp = $ref->getProperty('licenseUrlCache');
            if (PHP_VERSION_ID < 80100) {
                $cacheProp->setAccessible(true);
            }
            $cacheProp->setValue(null, [
                'http://localhost:8080/tr/|tr' => [
                    'license' => 'http://localhost:8080/tr/page/Kullanım_Koşulları',
                    'acquire' => 'http://localhost:8080/tr/legal',
                ],
            ]);
            """

        php_code = """<?php
        require_once 'init.php';
        require_once 'lib/Traits/PublicHelperTrait.php';
        require_once 'mod/index/indexBase.php';
        require_once 'mod/index/helper/mod_news.helper.php';

        $ref = new ReflectionClass('mod_news_helper');
        $helper = $ref->newInstanceWithoutConstructor();
        $helper->site = [
            'lang' => 'tr',
            'saddress' => 'http://localhost:8080/tr/',
            'title' => 'Amed Bülten',
            'owner_name' => 'Amed Bülten',
        ];
        __SEED_CACHE__
        $method = $ref->getMethod('enrichImageSchemaMetadata');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $cases = [
            'prefixed' => $method->invoke($helper, ['created_at' => '2026-11-13 11:39:13', 'image_credit' => 'Fotoğraf: The Jerusalem Post']),
            'bare' => $method->invoke($helper, ['created_at' => '2026-11-14 12:19:11', 'image_credit' => 'Reuters']),
            'temsili' => $method->invoke($helper, ['created_at' => '2026-11-14 10:00:00', 'image_credit' => 'Fotoğraf: Temsili']),
            'temsili_ku' => $method->invoke($helper, ['created_at' => '2026-11-14 10:00:00', 'image_credit' => 'Wêne: Temsîlî']),
            'empty' => $method->invoke($helper, ['created_at' => '2026-11-14 10:00:00', 'image_credit' => '']),
        ];
        echo json_encode($cases, JSON_UNESCAPED_UNICODE);
        """.replace("__SEED_CACHE__", seed_cache_block)

        if use_docker:
            res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=php_code)
        else:
            res = self.run_php([], input_data=php_code)

        self.assertExitCode(res, 0)
        data = json.loads(res.stdout.strip().splitlines()[-1])

        prefixed = data["prefixed"]
        self.assertEqual(prefixed["image_schema_creator_name"], "The Jerusalem Post")
        self.assertEqual(prefixed["image_schema_credit_text"], "Fotoğraf: The Jerusalem Post")
        self.assertEqual(prefixed["image_schema_copyright_notice"], "© 2026 The Jerusalem Post")
        self.assertTrue(prefixed["image_schema_license_url"].startswith("http://localhost:8080/tr/"))
        self.assertNotIn("%C4%B1", prefixed["image_schema_license_url"])
        self.assertNotIn("%C5%9F", prefixed["image_schema_license_url"])
        self.assertEqual(prefixed["image_schema_acquire_license_url"], "http://localhost:8080/tr/legal")

        bare = data["bare"]
        self.assertEqual(bare["image_schema_creator_name"], "Reuters")
        self.assertEqual(bare["image_schema_credit_text"], "Reuters")
        self.assertEqual(bare["image_schema_copyright_notice"], "© 2026 Reuters")

        for fallback_key in ("temsili", "temsili_ku", "empty"):
            row = data[fallback_key]
            self.assertEqual(row["image_schema_creator_name"], "Amed Bülten")
            self.assertEqual(row["image_schema_copyright_notice"], "© 2026 Amed Bülten")
            self.assertNotIn("Temsili", row["image_schema_creator_name"])
            self.assertNotIn("Temsîlî", row["image_schema_creator_name"])

        self.assertEqual(data["empty"]["image_schema_credit_text"], "Amed Bülten")

    def test_f28_human_readable_utf8_url_contract(self) -> None:
        """Public templates avoid |sitemap_url while share.js restores decodeURI and keeps popover UI."""
        repo_root = Path(__file__).resolve().parents[3]
        templates_dir = repo_root / "templates" / "amed"
        for twig_file in templates_dir.rglob("*.twig"):
            content = twig_file.read_text(encoding="utf-8")
            self.assertNotIn(
                "|sitemap_url",
                content,
                f"{twig_file.relative_to(repo_root)} must preserve native UTF-8 URLs without |sitemap_url",
            )

        share_js = (templates_dir / "static" / "js" / "share.js").read_text(encoding="utf-8")
        self.assertIn("function toCleanReadableUrl(", share_js)
        self.assertIn("decodeURI(rawUrl)", share_js)
        self.assertNotIn("toShareableUrl", share_js)
        self.assertIn(".share-popover-wrapper", share_js)
        self.assertIn(".share-trigger-btn", share_js)
        self.assertIn(".share-popover-menu", share_js)
        self.assertIn("showCopySuccessFeedback", share_js)

    def test_f28_share_partial_renders_clean_utf8_without_double_encoding(self) -> None:
        """share.twig preserves the popover UI, emits UTF-8 data-copy-url, and avoids %25 double encoding."""
        php_code = """<?php
        require_once 'init.php';
        require_once 'lib/TwigLibs.class.php';

        $loader = new \\Twig\\Loader\\FilesystemLoader('templates/amed');
        $twig = new \\Twig\\Environment($loader);
        $twigRef = new ReflectionClass('TwigLibs');
        $twig->addExtension($twigRef->newInstanceWithoutConstructor());

        echo $twig->render('partials/share.twig', [
            'site' => [
                'taddress' => 'https://assets.amedbulten.com/',
                'app_template' => 'amed',
                'version' => '1.0.71',
                'saddress' => 'https://amedbulten.com/tr/',
                'name' => 'Amed Bülten',
            ],
            'lang' => [
                'index' => [
                    'share' => 'Paylaş',
                    'copy_link' => 'Bağlantıyı Kopyala',
                    'link_copied' => 'Bağlantı Kopyalandı!',
                ],
            ],
            'share_url_path' => 'news',
            'd' => [
                'seo' => 'türkiyenin_spor_yazarları_tam_kadro_diyarbakırdalar',
                'name' => 'Türkiye’nin spor yazarları tam kadro Diyarbakır’dalar!',
            ],
        ]);
        """
        if self.php.is_docker_available():
            res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=php_code)
        else:
            res = self.run_php([], input_data=php_code)

        self.assertExitCode(res, 0)
        html = res.stdout
        self.assertIn('class="share-container share-popover-wrapper', html)
        self.assertIn('class="share-popover-menu"', html)
        self.assertIn(
            'data-copy-url="https://amedbulten.com/tr/news/türkiyenin_spor_yazarları_tam_kadro_diyarbakırdalar"',
            html,
        )
        self.assertIn(
            'data-share-url="https://amedbulten.com/tr/news/türkiyenin_spor_yazarları_tam_kadro_diyarbakırdalar"',
            html,
        )
        self.assertIn(
            'https://api.whatsapp.com/send?text=T%C3%BCrkiye%E2%80%99nin%20spor%20yazarlar%C4%B1%20tam%20kadro%20Diyarbak%C4%B1r%E2%80%99dalar%21%0Ahttps://amedbulten.com/tr/news/türkiyenin_spor_yazarları_tam_kadro_diyarbakırdalar',
            html,
        )
        self.assertIn(
            'https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Famedbulten.com%2Ftr%2Fnews%2Ft%C3%BCrkiyenin',
            html,
        )
        self.assertNotIn("%25", html)

    def test_f28_code_separation_compliance(self) -> None:
        """news.twig has zero inline styles and zero inline event handlers per AGENTS.md Rule 1."""
        res = self.run_python([
            "tools/code_separation_guard.py",
            "--path", str(self.twig_path),
        ])
        self.assertExitCode(res, 0)
        self.assertIn("0 violations found", res.stdout)
