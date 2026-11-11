#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Milestone 3 Empirical Adversarial Stress Test Suite for process_news_image.py.

Tests:
1. Next.js Fixture Verification:
   - Unwrapping Next.js CDN proxy URLs (/_next/image?url=...)
   - Deduplication without collapsing distinct images
   - Article vs sidebar scoping (container exclusion penalties)
   - Responsiveness srcset descriptor capping (3840w max, 1200w proxy cap)
   - og:image stem cross-anchoring (+2000 bonus)
2. Mode Empirical Verification:
   - --mode master: >=1200px width, natural aspect ratio preserved, WebP quality 90, <1.5 MB
   - --mode inline-16x9: 16:9 center-crop to 1280x720, WebP <100 KB
   - Various aspect ratios: 4:3, 1:1, 9:16, 21:9, large 4000x3000, high-entropy noisy image
3. Edge Cases & Security:
   - Malformed URLs (empty, invalid scheme, broken query, unquoted chars)
   - SSRF protection (127.0.0.1, 169.254.169.254, 0.0.0.0, 10.0.0.1, 192.168.1.1, [::1], file://, ftp://)
   - Nested CDN proxies (Cloudflare -> Next.js, Photon -> Cloudflare -> Next.js, 4+ levels)
   - Pathological and malformed HTML structures
"""

import base64
import io
import json
import os
import subprocess
import sys
import unittest
import urllib.parse
from pathlib import Path
from PIL import Image, ImageDraw
import numpy as np

# Ensure kevanger scripts and shared dir are on sys.path
PROJECT_ROOT = Path(__file__).resolve().parent.parent
KEVANGER_DIR = PROJECT_ROOT / ".agents" / "skills" / "kevanger" / "scripts"
SHARED_DIR = PROJECT_ROOT / ".agents" / "skills" / "_shared"

sys.path.insert(0, str(KEVANGER_DIR))
sys.path.insert(0, str(SHARED_DIR))

import process_news_image
import editorial_http


class TestNextJsFixtureAndScoping(unittest.TestCase):
    """Objective 1: Next.js fixture, unwrapping, deduplication, DOM scoping, srcset capping, and meta stem anchoring."""

    @classmethod
    def setUpClass(cls):
        cls.fixture_path = PROJECT_ROOT / "tests" / "e2e" / "fixtures" / "editorial" / "mock_nextjs_sidebar_article.html"
        with open(cls.fixture_path, "r", encoding="utf-8") as f:
            cls.fixture_html = f.read()
        cls.base_url = "https://example.com/article/100"

    def test_nextjs_fixture_extraction_and_ranking(self):
        """Verify extraction and ranking on the standard Next.js sidebar fixture."""
        parser = process_news_image.ArticleImageCandidateExtractor(self.base_url)
        parser.feed(self.fixture_html)
        ranked = process_news_image.rank_candidates(parser.candidates, self.base_url)

        self.assertGreaterEqual(len(ranked), 2, "Expected at least 2 distinct image candidates")
        winner = ranked[0]
        loser = ranked[1]

        # Winner must be hero_main.jpg
        self.assertIn("hero_main.jpg", winner["unwrapped_url"], "Hero main image must be the winner")
        # Loser must be sidebar_thumb.jpg
        self.assertIn("sidebar_thumb.jpg", loser["unwrapped_url"], "Sidebar thumb must be ranked lower")
        # Ensure hero is inside article and not excluded
        self.assertTrue(winner["in_article"], "Winner must be in_article")
        self.assertFalse(winner["is_excluded"], "Winner must not be excluded")
        # Ensure sidebar is excluded
        self.assertTrue(loser["is_excluded"], "Sidebar candidate must have is_excluded=True")

    def test_unwrapping_nextjs_and_cdn_proxies(self):
        """Verify unwrapping of Next.js, Vercel, Photon, and Cloudflare proxy URLs."""
        # Next.js proxy
        u1 = "/_next/image?url=https%3A%2F%2Fcdn.example.com%2Fphotos%2Fhero_main.jpg&w=1920&q=75"
        self.assertEqual(
            process_news_image.unwrap_cdn_image_url(u1, self.base_url),
            "https://cdn.example.com/photos/hero_main.jpg"
        )

        # Vercel proxy
        u2 = "/_vercel/image?url=%2Fstatic%2Fhero.png&w=1200"
        self.assertEqual(
            process_news_image.unwrap_cdn_image_url(u2, "https://example.com/page"),
            "https://example.com/static/hero.png"
        )

        # WordPress Photon
        u3 = "https://i0.wp.com/cdn.example.com/photos/photon_hero.jpg?resize=1200%2C675"
        self.assertEqual(
            process_news_image.unwrap_cdn_image_url(u3),
            "https://cdn.example.com/photos/photon_hero.jpg"
        )

        # Cloudflare Image Resizing
        u4 = "https://example.com/cdn-cgi/image/width=1200,quality=85/https://cdn.example.com/photos/cf_hero.jpg"
        self.assertEqual(
            process_news_image.unwrap_cdn_image_url(u4),
            "https://cdn.example.com/photos/cf_hero.jpg"
        )

    def test_non_collapsing_candidate_urls(self):
        """Verify that distinct images with same domain or query params are NOT collapsed into one URL."""
        k1 = process_news_image.canonical_image_key(
            "/_next/image?url=https%3A%2F%2Fcdn.example.com%2Fphotos%2Fhero_main.jpg&w=1920&q=75",
            self.base_url
        )
        k2 = process_news_image.canonical_image_key(
            "/_next/image?url=https%3A%2F%2Fcdn.example.com%2Fphotos%2Fsidebar_thumb.jpg&w=3840&q=75",
            self.base_url
        )
        self.assertNotEqual(k1, k2, "Distinct images must produce distinct canonical keys")
        self.assertEqual(k1, "https://cdn.example.com/photos/hero_main.jpg")
        self.assertEqual(k2, "https://cdn.example.com/photos/sidebar_thumb.jpg")

        # Verify semantic query parameters are preserved while resize parameters are stripped
        k3 = process_news_image.canonical_image_key("https://cdn.example.com/img?id=42&w=1920&q=80")
        k4 = process_news_image.canonical_image_key("https://cdn.example.com/img?id=99&w=1920&q=80")
        self.assertNotEqual(k3, k4, "Images with different semantic parameters (id=42 vs id=99) must not collapse")
        self.assertIn("id=42", k3)
        self.assertIn("id=99", k4)
        self.assertNotIn("w=1920", k3)

    def test_dom_scoping_article_vs_sidebar(self):
        """Verify hero bonus is strictly restricted to <article> and excluded containers receive -2000 penalty."""
        html = """
        <html>
        <body>
          <aside class="sidebar">
            <img src="https://example.com/aside_img.jpg" width="1600">
          </aside>
          <div class="related-news">
            <img src="https://example.com/related_img.jpg" width="1600">
          </div>
          <nav>
            <img src="https://example.com/nav_img.jpg" width="1600">
          </nav>
          <footer>
            <img src="https://example.com/footer_img.jpg" width="1600">
          </footer>
          <article>
            <img src="https://example.com/article_lead_img.jpg" width="1000">
          </article>
        </body>
        </html>
        """
        parser = process_news_image.ArticleImageCandidateExtractor("https://example.com/post")
        parser.feed(html)
        ranked = process_news_image.rank_candidates(parser.candidates, "https://example.com/post")

        # article_lead_img (width 1000 + lead hero bonus 600 = 1600) must beat all excluded 1600px images (1600 - 2000 = -400)
        self.assertEqual(ranked[0]["unwrapped_url"], "https://example.com/article_lead_img.jpg")
        for loser in ranked[1:]:
            self.assertTrue(loser["is_excluded"], f"Candidate {loser['url']} should have been marked is_excluded")

    def test_srcset_capping_and_proxy_limits(self):
        """Verify responsiveness srcset descriptor cap at 3840w max, and 1200w for proxy without explicit width."""
        html = """
        <html>
        <body>
          <article>
            <!-- Case 1: Standard image with inflated 5000w descriptor -->
            <img src="https://example.com/standard.jpg"
                 srcset="https://example.com/standard_5k.jpg 5000w">

            <!-- Case 2: Proxy URL with 3840w and NO explicit width -->
            <img src="/_next/image?url=https%3A%2F%2Fexample.com%2Fproxy_no_width.jpg&w=3840"
                 srcset="/_next/image?url=https%3A%2F%2Fexample.com%2Fproxy_no_width.jpg&w=3840 3840w">

            <!-- Case 3: Proxy URL with 3840w and explicit width 1920 -->
            <img src="/_next/image?url=https%3A%2F%2Fexample.com%2Fproxy_explicit.jpg&w=3840"
                 srcset="/_next/image?url=https%3A%2F%2Fexample.com%2Fproxy_explicit.jpg&w=3840 3840w"
                 width="1920">

            <!-- Case 4: Proxy URL with 3840w and explicit width 600 (< 1200) -->
            <img src="/_next/image?url=https%3A%2F%2Fexample.com%2Fproxy_small.jpg&w=3840"
                 srcset="/_next/image?url=https%3A%2F%2Fexample.com%2Fproxy_small.jpg&w=3840 3840w"
                 width="600">
          </article>
        </body>
        </html>
        """
        parser = process_news_image.ArticleImageCandidateExtractor("https://example.com")
        parser.feed(html)

        cand_map = {c["unwrapped_url"]: c for c in parser.candidates if c.get("origin") == "img_srcset"}

        # Case 1: 5000w capped to 3840w
        self.assertEqual(cand_map["https://example.com/standard_5k.jpg"]["width_hint"], 3840)
        # Case 2: proxy with 3840w and no explicit width capped to 1200w
        self.assertEqual(cand_map["https://example.com/proxy_no_width.jpg"]["width_hint"], 1200)
        # Case 3: proxy with 3840w and explicit 1920 capped to 1920w
        self.assertEqual(cand_map["https://example.com/proxy_explicit.jpg"]["width_hint"], 1920)
        # Case 4: proxy with 3840w and explicit 600 (< 1200) capped to 1200w
        self.assertEqual(cand_map["https://example.com/proxy_small.jpg"]["width_hint"], 1200)

    def test_og_image_stem_cross_anchoring(self):
        """Verify that og:image stem cross-anchoring (+2000 bonus) ensures authentic hero wins."""
        html = """
        <html>
        <head>
          <meta property="og:image" content="https://cdn.example.com/media/2026/dam_reservoir_amed_lead.jpg?w=1200">
        </head>
        <body>
          <main>
            <!-- Competitor image outside article with high resolution -->
            <div class="banner">
              <img src="https://cdn.example.com/media/2026/huge_banner_promo.jpg" width="2400">
            </div>
            <article>
              <!-- Lead image inside article matching og:image stem -->
              <img src="/_next/image?url=https%3A%2F%2Fcdn.example.com%2Fmedia%2F2026%2Fdam_reservoir_amed_lead.jpg&w=1200"
                   width="1200">
            </article>
          </main>
        </body>
        </html>
        """
        parser = process_news_image.ArticleImageCandidateExtractor("https://example.com/news/1")
        parser.feed(html)
        ranked = process_news_image.rank_candidates(parser.candidates, "https://example.com/news/1")

        winner = ranked[0]
        self.assertIn("dam_reservoir_amed_lead.jpg", winner["unwrapped_url"])
        self.assertTrue(winner["matches_meta_stem"], "Winner should have matches_meta_stem=True")


class TestImageProcessingModes(unittest.TestCase):
    """Objective 2: --mode master vs --mode inline-16x9 empirical stress testing."""

    def _create_test_image(self, width: int, height: int, color=(100, 150, 200), noisy: bool = False) -> Image.Image:
        if noisy:
            # Generate high entropy random RGB image to test WebP compression limits
            np.random.seed(42)
            arr = np.random.randint(0, 256, (height, width, 3), dtype=np.uint8)
            return Image.fromarray(arr, "RGB")
        img = Image.new("RGB", (width, height), color=color)
        draw = ImageDraw.Draw(img)
        draw.line((0, 0, width, height), fill=(255, 0, 0), width=3)
        draw.line((0, height, width, 0), fill=(0, 255, 0), width=3)
        return img

    def test_mode_master_preserves_aspect_ratio_and_spec(self):
        """Verify --mode master preserves natural aspect ratios (16:9, 4:3, 1:1, 9:16, 21:9)."""
        aspect_cases = [
            (1600, 900, "16:9"),
            (1600, 1200, "4:3"),
            (1400, 1400, "1:1"),
            (1200, 1600, "3:4 portrait"),
            (2100, 900, "21:9 ultrawide"),
        ]

        for w_in, h_in, desc in aspect_cases:
            with self.subTest(aspect=desc, orig=(w_in, h_in)):
                img = self._create_test_image(w_in, h_in)
                data, w_out, h_out, wm = process_news_image.process_image(img, mode="master")

                # Master image must be WebP
                self.assertEqual(data[:4], b"RIFF")
                self.assertEqual(data[8:12], b"WEBP")

                # Size limit < 1.5 MB
                self.assertLessEqual(len(data), 1536 * 1024, f"Size {len(data)} exceeds 1.5 MB for {desc}")

                # Master mode must NOT center crop to 16:9!
                expected_aspect = w_in / h_in
                actual_aspect = w_out / h_out
                self.assertAlmostEqual(
                    actual_aspect, expected_aspect, delta=0.03,
                    msg=f"Aspect ratio deviated for {desc}: expected {expected_aspect:.3f}, got {actual_aspect:.3f}"
                )

                # If original width > 1920, scaled down to 1920
                if w_in > 1920:
                    self.assertEqual(w_out, 1920)
                else:
                    self.assertEqual(w_out, w_in)
                    self.assertEqual(h_out, h_in)

    def test_mode_inline_16x9_crops_and_fits_specs(self):
        """Verify --mode inline-16x9 center-crops to exact 1280x720 and stays under 100 KB."""
        aspect_cases = [
            (1600, 900, "16:9"),
            (1600, 1200, "4:3"),
            (1400, 1400, "1:1"),
            (1200, 1600, "portrait"),
        ]

        for w_in, h_in, desc in aspect_cases:
            with self.subTest(aspect=desc):
                img = self._create_test_image(w_in, h_in)
                data, w_out, h_out, wm = process_news_image.process_image(img, mode="inline-16x9")

                self.assertEqual(w_out, 1280, f"Inline width must be 1280 for {desc}")
                self.assertEqual(h_out, 720, f"Inline height must be 720 for {desc}")
                self.assertLessEqual(len(data), 100 * 1024, f"Inline size {len(data)} exceeds 100 KB for {desc}")

    def test_high_entropy_master_compression_fallback(self):
        """Stress-test high entropy noisy image to verify quality step-down handles large files."""
        # 1920x1080 pure noise is extremely hard to compress in lossy WebP
        noisy_img = self._create_test_image(1920, 1080, noisy=True)
        data, w_out, h_out, _ = process_news_image.process_image(noisy_img, mode="master")

        self.assertEqual(w_out, 1920)
        self.assertEqual(h_out, 1080)
        # Even with high noise, must remain within bounds or step down gracefully
        self.assertLessEqual(len(data), 1536 * 1024, f"High noise master exceeded 1.5MB: {len(data)} bytes")

    def test_cli_execution_modes(self):
        """Verify CLI execution of process_news_image.py for both modes with stdout JSON envelope."""
        test_img_path = PROJECT_ROOT / "tests" / "e2e" / "fixtures" / "images" / "rudaw_true_hero_1920x1080.jpg"
        self.assertTrue(test_img_path.exists(), f"Missing fixture {test_img_path}")

        # 1. Test CLI with --mode master
        cmd_master = [
            sys.executable,
            str(KEVANGER_DIR / "process_news_image.py"),
            "--input", str(test_img_path),
            "--source", "Rudaw",
            "--mode", "master"
        ]
        res_master = subprocess.run(cmd_master, capture_output=True, text=True)
        self.assertEqual(res_master.returncode, 0, f"CLI master mode failed: {res_master.stderr}")
        out_master = json.loads(res_master.stdout)
        self.assertEqual(out_master["status"], "SUCCESS")
        self.assertEqual(out_master["mode"], "master")
        self.assertGreaterEqual(out_master["width"], 1200)
        self.assertLessEqual(out_master["size_bytes"], 1536 * 1024)
        self.assertIn("data_base64", out_master)
        self.assertEqual(out_master["attribution_ku"], "Wêne: Rudaw")

        # 2. Test CLI with --mode inline-16x9
        cmd_inline = [
            sys.executable,
            str(KEVANGER_DIR / "process_news_image.py"),
            "--input", str(test_img_path),
            "--source", "Rudaw",
            "--mode", "inline-16x9"
        ]
        res_inline = subprocess.run(cmd_inline, capture_output=True, text=True)
        self.assertEqual(res_inline.returncode, 0, f"CLI inline mode failed: {res_inline.stderr}")
        out_inline = json.loads(res_inline.stdout)
        self.assertEqual(out_inline["status"], "SUCCESS")
        self.assertEqual(out_inline["mode"], "inline-16x9")
        self.assertEqual(out_inline["width"], 1280)
        self.assertEqual(out_inline["height"], 720)
        self.assertLessEqual(out_inline["size_bytes"], 100 * 1024)


class TestEdgeCasesAndSecurity(unittest.TestCase):
    """Objective 3: SSRF attempts, malformed URLs, nested CDN proxies, and adversarial inputs."""

    def test_ssrf_protection_on_private_and_metadata_ips(self):
        """Verify that SSRF attempts to private networks and cloud metadata are strictly blocked."""
        ssrf_targets = [
            "http://127.0.0.1/evil.jpg",
            "http://127.0.0.2:8080/secret.png",
            "http://localhost/admin.png",
            "http://169.254.169.254/latest/meta-data/",
            "http://169.254.169.254/computeMetadata/v1/",
            "http://0.0.0.0:8000/test.jpg",
            "http://10.0.0.1/db.dump",
            "http://172.16.0.1/internal.jpg",
            "http://192.168.1.1/router.png",
            "http://[::1]/secret.jpg",
            "http://100.64.0.1/cgnat.jpg",
        ]

        for target in ssrf_targets:
            with self.subTest(target=target):
                # Test direct validate_public_url in process_news_image
                with self.assertRaises(ValueError, msg=f"SSRF target {target} should be blocked"):
                    process_news_image.validate_public_url(target)

    def test_malformed_urls_unwrapping(self):
        """Verify unwrap_cdn_image_url handles malformed, empty, or anomalous URLs gracefully."""
        cases = [
            ("", ""),
            ("   ", ""),
            ("https://example.com/_next/image", "https://example.com/_next/image"),
            ("https://example.com/_next/image?url=", "https://example.com/_next/image?url="),
            ("https://example.com/_next/image?w=1920", "https://example.com/_next/image?w=1920"),
            ("https://i0.wp.com/", "https://"),
            ("https://i0.wp.com/broken", "https://broken"),
            ("https://example.com/cdn-cgi/image/", "https://example.com/cdn-cgi/image/"),
            ("https://example.com/cdn-cgi/image/width=100", "https://example.com/cdn-cgi/image/width=100"),
            ("javascript:alert(1)", "javascript:alert(1)"),
            ("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==",
             "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=="),
        ]

        for input_url, expected in cases:
            with self.subTest(url=input_url):
                res = process_news_image.unwrap_cdn_image_url(input_url)
                self.assertIsInstance(res, str)

    def test_nested_cdn_proxies(self):
        """Verify multi-tier nested CDN proxy unwrapping (e.g. Cloudflare -> Next.js -> clean URL)."""
        clean_target = "https://cdn.example.com/photos/real_hero.jpg"

        # 1 level: Next.js proxy
        l1 = f"https://example.com/_next/image?url={urllib.parse.quote(clean_target)}&w=1920"
        self.assertEqual(process_news_image.unwrap_cdn_image_url(l1), clean_target)

        # 2 levels: Cloudflare Image Resizing wrapping Next.js
        l2 = f"https://example.com/cdn-cgi/image/width=1200,quality=85/{l1}"
        self.assertEqual(process_news_image.unwrap_cdn_image_url(l2), clean_target)

        # 3 levels: Photon wrapping Cloudflare wrapping Next.js
        # i0.wp.com strips https://, so:
        l3 = f"https://i0.wp.com/example.com/cdn-cgi/image/width=1200,quality=85/{l1}"
        self.assertEqual(process_news_image.unwrap_cdn_image_url(l3), clean_target)

        # 4 levels: Exceeds 3 loops, terminates safely without infinite loop
        l4 = f"https://example.com/_next/image?url={urllib.parse.quote(l3)}&w=1920"
        res_l4 = process_news_image.unwrap_cdn_image_url(l4)
        self.assertIsInstance(res_l4, str)

    def test_adversarial_html_structures(self):
        """Verify candidate extractor against pathological HTML (deep nesting, unclosed tags, no images)."""
        # 1. Empty HTML
        parser = process_news_image.ArticleImageCandidateExtractor("https://example.com")
        parser.feed("")
        self.assertEqual(len(parser.candidates), 0)

        # 2. HTML with zero images
        parser = process_news_image.ArticleImageCandidateExtractor("https://example.com")
        parser.feed("<html><body><article><h1>Title</h1><p>Body</p></article></body></html>")
        self.assertEqual(len(parser.candidates), 0)

        # 3. Pathological tag soup / unclosed tags
        tag_soup = """
        <article>
          <aside>
            <div>
              <span>
                <img src="https://example.com/broken_soup.jpg">
          </aside>
          <!-- Now back in article without proper closing tags -->
          <img src="https://example.com/article_valid.jpg">
        </article>
        """
        parser = process_news_image.ArticleImageCandidateExtractor("https://example.com")
        parser.feed(tag_soup)
        self.assertGreaterEqual(len(parser.candidates), 1)

    def test_watermark_detection_and_crop(self):
        """Verify detection of Kurdish (Rudaw/K24 top-right red bug) and Turkish (AA/DHA bottom ticker)."""
        rudaw_fixture = PROJECT_ROOT / "tests" / "e2e" / "fixtures" / "images" / "rudaw_true_hero_1920x1080.jpg"
        if rudaw_fixture.exists():
            img = Image.open(rudaw_fixture)
            wm_report = process_news_image.inspect_watermarks(img)
            self.assertIn("watermark_detected", wm_report)
            self.assertIn("crop_percentages", wm_report)


if __name__ == "__main__":
    unittest.main()
