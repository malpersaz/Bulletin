"""Feature 24: process_news_image.py Next.js CDN Proxy Unwrap.

Validates that Next.js and Vercel image proxy URLs (/_next/image?url=...)
are unwrapped to their canonical original source URLs, preventing deduplication collisions,
joining relative paths with base URLs, unquoting UTF-8 sequences, and enforcing SSRF guards.
"""

from __future__ import annotations

from pathlib import Path
import sys

from harness.base import E2ETestCase, feature, tier

BASE_DIR = Path(__file__).resolve().parents[3]
KEVANGER_DIR = BASE_DIR / ".agents" / "skills" / "kevanger" / "scripts"
SHARED_DIR = BASE_DIR / ".agents" / "skills" / "_shared"

for p in (str(KEVANGER_DIR), str(SHARED_DIR)):
    if p not in sys.path:
        sys.path.insert(0, p)

import editorial_http
import process_news_image as img_proc


@tier(1)
@feature(24)
class TestF24ImageNextJsUnwrap(E2ETestCase):
    """Tier 1 tests for Next.js image proxy unwrapping in the image pipeline."""

    def test_f24_nextjs_single_candidate_unwrap(self) -> None:
        """Single Next.js proxy URL unwraps query string to canonical source target."""
        proxy_url = "/_next/image?url=https%3A%2F%2Fcdn.rudaw.net%2Fimages%2Fpark_lead.jpg&w=1920&q=75"
        unwrapped = img_proc.unwrap_cdn_image_url(proxy_url, base_url="https://www.rudaw.net/article")
        self.assertEqual(unwrapped, "https://cdn.rudaw.net/images/park_lead.jpg")

    def test_f24_nextjs_multiple_images_no_collision(self) -> None:
        """Multiple distinct Next.js images in an article are not collapsed to the same endpoint."""
        html = """
        <article>
            <img src="/_next/image?url=https%3A%2F%2Fcdn.example.org%2Fimg1.jpg&w=1200">
            <img src="/_next/image?url=https%3A%2F%2Fcdn.example.org%2Fimg2.jpg&w=1200">
            <img src="/_next/image?url=https%3A%2F%2Fcdn.example.org%2Fimg3.jpg&w=1200">
            <img src="/_next/image?url=https%3A%2F%2Fcdn.example.org%2Fimg4.jpg&w=1200">
        </article>
        """
        extractor = img_proc.ArticleImageCandidateExtractor(base_url="https://example.org/article")
        extractor.feed(html)
        unwrapped_urls = [c["unwrapped_url"] for c in extractor.candidates]

        self.assertEqual(len(unwrapped_urls), 4)
        self.assertEqual(len(set(unwrapped_urls)), 4, "All 4 unwrapped URLs must be unique")
        for i in range(1, 5):
            self.assertIn(f"https://cdn.example.org/img{i}.jpg", unwrapped_urls)

    def test_f24_nextjs_relative_url_unwrap(self) -> None:
        """Relative path in Next.js url query parameter is resolved against article base URL."""
        proxy_url = "/_next/image?url=%2Fstatic%2Fphotos%2Fhero.jpg&w=1200&q=75"
        unwrapped = img_proc.unwrap_cdn_image_url(proxy_url, base_url="https://example.org/news/story")
        self.assertEqual(unwrapped, "https://example.org/static/photos/hero.jpg")
        self.assertTrue(unwrapped.startswith("http"))

    def test_f24_nextjs_ssrf_blocked_unwrapped_target(self) -> None:
        """Unwrapped target pointing to metadata/private IP is blocked by SSRF validation."""
        proxy_url = "/_next/image?url=http%3A%2F%2F169.254.169.254%2Flatest%2Fmeta-data%2Fimage.jpg&w=1200&q=75"
        unwrapped = img_proc.unwrap_cdn_image_url(proxy_url, base_url="https://example.org/news")
        self.assertEqual(unwrapped, "http://169.254.169.254/latest/meta-data/image.jpg")

        valid, reason = editorial_http.validate_public_url(unwrapped)
        self.assertFalse(valid, "Unwrapped link-local IP must be rejected by SSRF guard")
        self.assertIn("SSRF protection", reason)

    def test_f24_nextjs_query_decoding_with_special_chars(self) -> None:
        """URL-encoded UTF-8 characters and spaces in image path are cleanly decoded."""
        proxy_url = "/_next/image?url=https%3A%2F%2Fcdn.org%2Fmedya%2Fdiyarbak%C4%B1r%20park%C4%B1.jpg&w=1200&q=75"
        unwrapped = img_proc.unwrap_cdn_image_url(proxy_url, base_url="https://cdn.org/article")
        self.assertEqual(unwrapped, "https://cdn.org/medya/diyarbakır parkı.jpg")
        self.assertIn("diyarbakır parkı", unwrapped)
