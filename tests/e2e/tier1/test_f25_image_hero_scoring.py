"""Feature 25: process_news_image.py Hero Scoring & Srcset Cap.

Validates candidate extraction and ranking in the image processing pipeline:
- In-article authentic lead photos beat inflated sidebar thumbnails
- CDN proxy srcset width hints are capped at 1200px
- Aside, nav, and footer containers are excluded from lead hero bonuses
- Branded social banners receive a -600 ranking penalty
- og:image stem matching awards a +2000 cross-anchor boost
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

import process_news_image as img_proc


@tier(1)
@feature(25)
class TestF25ImageHeroScoring(E2ETestCase):
    """Tier 1 tests for image candidate extraction and scoring heuristics."""

    def setUp(self) -> None:
        super().setUp()
        self.fixtures_dir = Path(__file__).resolve().parents[1] / "fixtures" / "editorial"

    def test_f25_lead_hero_in_article_beats_sidebar_3840w(self) -> None:
        """Authentic article lead hero ranks higher than inflated 3840w sidebar thumbnail."""
        fixture_path = self.fixtures_dir / "mock_nextjs_sidebar_article.html"
        html = fixture_path.read_text(encoding="utf-8")

        extractor = img_proc.ArticleImageCandidateExtractor(base_url="https://www.rudaw.net/turkish/kurdistan/1287295")
        extractor.feed(html)
        ranked = img_proc.rank_candidates(extractor.candidates, base_url="https://www.rudaw.net/turkish/kurdistan/1287295")

        self.assertTrue(len(ranked) > 0, "Expected ranked candidates")
        top_cand = ranked[0]
        # Top candidate must be the lead hero photo in the article, not the sidebar thumbnail
        self.assertIn("hero_main", top_cand["unwrapped_url"])
        self.assertTrue(top_cand.get("in_article"))
        self.assertFalse(top_cand.get("is_excluded"))

    def test_f25_srcset_width_hint_cap(self) -> None:
        """CDN proxy srcset descriptor with 3840w is capped at 1200px width_hint."""
        html = """
        <article>
            <img src="/_next/image?url=https%3A%2F%2Fcdn.example.org%2Flead.jpg&w=1200"
                 srcset="/_next/image?url=https%3A%2F%2Fcdn.example.org%2Flead.jpg&w=3840 3840w">
        </article>
        """
        extractor = img_proc.ArticleImageCandidateExtractor(base_url="https://example.org/article")
        extractor.feed(html)

        # Candidates from srcset proxy must be capped at 1200
        for cand in extractor.candidates:
            if "srcset" in cand.get("origin", ""):
                self.assertLessEqual(cand["width_hint"], 1200)

    def test_f25_aside_container_exclusion(self) -> None:
        """Images inside <aside> receive is_excluded=True and in_article=False."""
        html = """
        <main>
            <aside class="sidebar">
                <img src="https://cdn.example.org/ad_banner.jpg" width="1200">
            </aside>
            <article>
                <img src="https://cdn.example.org/real_article_photo.jpg" width="1200">
            </article>
        </main>
        """
        extractor = img_proc.ArticleImageCandidateExtractor(base_url="https://example.org/article")
        extractor.feed(html)

        aside_cand = next((c for c in extractor.candidates if "ad_banner" in c["url"]), None)
        article_cand = next((c for c in extractor.candidates if "real_article_photo" in c["url"]), None)

        self.assertIsNotNone(aside_cand)
        self.assertIsNotNone(article_cand)
        self.assertTrue(aside_cand["is_excluded"])
        self.assertFalse(aside_cand["in_article"])
        self.assertTrue(article_cand["in_article"])
        self.assertFalse(article_cand["is_excluded"])

        ranked = img_proc.rank_candidates(extractor.candidates, base_url="https://example.org/article")
        self.assertEqual(ranked[0]["unwrapped_url"], article_cand["unwrapped_url"])

    def test_f25_social_banner_penalty(self) -> None:
        """Images with branded_news/social/banner patterns in filename receive penalty."""
        candidates = [
            {
                "url": "https://cdn.org/social_branded_news.jpg",
                "unwrapped_url": "https://cdn.org/social_branded_news.jpg",
                "width_hint": 1200,
                "origin": "img_src",
                "in_article": True,
                "in_main": True,
                "is_lead_hero": True,
                "is_excluded": False,
                "matches_meta_stem": False,
            },
            {
                "url": "https://cdn.org/actual_lead_photo.jpg",
                "unwrapped_url": "https://cdn.org/actual_lead_photo.jpg",
                "width_hint": 1000,
                "origin": "img_src",
                "in_article": True,
                "in_main": True,
                "is_lead_hero": True,
                "is_excluded": False,
                "matches_meta_stem": False,
            },
        ]
        ranked = img_proc.rank_candidates(candidates, base_url="https://cdn.org/article")
        # Even with smaller width_hint (1000 vs 1200), actual_lead_photo wins because social_branded_news gets -600 penalty
        self.assertEqual(ranked[0]["unwrapped_url"], "https://cdn.org/actual_lead_photo.jpg")

    def test_f25_og_image_stem_match_boost(self) -> None:
        """In-article image sharing filename stem with og:image receives large ranking boost."""
        html = """
        <html>
        <head>
            <meta property="og:image" content="https://cdn.org/1287295/lead_cover.jpg">
        </head>
        <body>
            <article>
                <img src="https://cdn.org/photos/secondary.jpg" width="1200">
                <img src="https://cdn.org/1287295/lead_cover_1600.webp" width="1000">
            </article>
        </body>
        </html>
        """
        extractor = img_proc.ArticleImageCandidateExtractor(base_url="https://cdn.org/article")
        extractor.feed(html)
        ranked = img_proc.rank_candidates(extractor.candidates, base_url="https://cdn.org/article")

        # lead_cover matches og:image stem and must rank first
        self.assertIn("lead_cover", ranked[0]["unwrapped_url"])
        self.assertTrue(ranked[0]["matches_meta_stem"])
