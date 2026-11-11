"""
Tier 2 Boundary & Corner Cases: Feature 6 (mandatory_make_seo).
Validates McpCrudToolsBase::makeSeo() boundaries:
- Empty string and whitespace-only return empty string
- String of pure punctuation/symbols returns empty string
- Decomposed Unicode U+0307 combining dots stripped into clean NFC
- Consecutive mixed slashes, backslashes, dashes, and underscores collapsed into single underscore
- Max length 255 multibyte truncation preserving valid UTF-8
- Kurdish typo normalization (ı -> i, İ -> I) while preserving Kurdish characters (ê, î, û)
- Path traversal and directory injection sequences neutralized
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(2)
@feature(6)
class TestF06MandatoryMakeSeoTier2(E2ETestCase):
    """Tier 2 tests for makeSeo() boundary conditions."""

    def _call_make_seo(self, title: str, lang: str = "tr") -> str:
        code = f"""<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$res = McpCrudToolsBase::makeSeo({json.dumps(title, ensure_ascii=False)}, {json.dumps(lang, ensure_ascii=False)});
echo json_encode(['slug' => $res]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        return data["slug"]

    def test_f06_boundary_empty_string_and_whitespace_returns_empty(self):
        """Empty string, whitespace, tabs, and newlines return empty string."""
        self.assertEqual(self._call_make_seo(""), "")
        self.assertEqual(self._call_make_seo("   "), "")
        self.assertEqual(self._call_make_seo("\t\n\r "), "")

    def test_f06_boundary_all_punctuation_and_symbols_returns_empty(self):
        """String of pure punctuation, quotes, slashes, brackets returns empty string."""
        dirty = "::: /// ??? !!! '' \"\" [[ ]] {{{ }}} << >> *** @@@ ### $$$ %%% ^^^ &&&"
        self.assertEqual(self._call_make_seo(dirty), "")

    def test_f06_boundary_extreme_unicode_decomposed_u0307_combining_dots(self):
        """Decomposed characters with U+0307 combining dot above are stripped into clean NFC."""
        decomposed = "I\u0307stanbul_Dı\u0307yarbakı\u0307r"
        slug = self._call_make_seo(decomposed, "tr")
        self.assertEqual(slug, "İstanbul_Dıyarbakır")
        self.assertNotIn("\u0307", slug)

    def test_f06_boundary_consecutive_mixed_slashes_and_dashes_collapsed(self):
        """Consecutive slashes, backslashes, dashes, and underscores are collapsed to single underscore."""
        mixed = "A///---___\\\\---B"
        slug = self._call_make_seo(mixed, "tr")
        self.assertEqual(slug, "A_B")

    def test_f06_boundary_max_length_255_multibyte_truncation(self):
        """Excessively long title with multibyte characters is cleanly truncated at 255 characters."""
        long_title = "Diyarbakır_Güneydoğu_Anadolu_Kültür_Mirası_" * 10  # > 400 chars
        slug = self._call_make_seo(long_title, "tr")
        self.assertLessEqual(len(slug), 255)
        # Ensure valid UTF-8 without broken byte sequences
        slug.encode("utf-8").decode("utf-8")
        self.assertFalse(slug.endswith("_"), "Truncated slug should not have trailing underscore")

    def test_f06_boundary_kurdish_typo_normalizations_in_ku_locale(self):
        """Kurdish locale replaces Turkish dotless ı with i, but preserves Kurdish accented letters."""
        title = "Pırsên Înternetê"
        slug = self._call_make_seo(title, "ku")
        self.assertEqual(slug, "Pirsên_Înternetê")
        self.assertNotIn("ı", slug)
        self.assertIn("ê", slug)

    def test_f06_boundary_path_traversal_and_directory_injection(self):
        """Deep path traversal sequences like ../../../../etc/shadow are stripped to base words."""
        slug = self._call_make_seo("../../../../etc/shadow", "tr")
        self.assertEqual(slug, "etc_shadow")
        self.assertNotIn("/", slug)
        self.assertNotIn(".", slug)
