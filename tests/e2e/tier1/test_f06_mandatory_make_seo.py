"""
Tier 1 Feature Coverage: Feature 6 (mandatory_make_seo).
Validates McpNewsTools & McpCrudToolsBase::makeSeo() enforces Unicode NFC normalization,
U+0307 dot-above stripping, Kurdish Latin preservation and typo normalization,
and Wikipedia-style underscore slug formatting.
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(6)
class TestF06MandatoryMakeSeo(E2ETestCase):
    """Tier 1 tests for mandatory makeSeo() slug generation and sanitization."""

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

    def test_f06_make_seo_omitted_slug_auto_generated_from_title(self):
        """Verify standard Turkish title generates NFC precomposed Wikipedia slug preserving Turkish characters."""
        title = "Diyarbakır Sur Kültür Festivali"
        slug = self._call_make_seo(title, "tr")
        self.assertEqual(slug, "Diyarbakır_Sur_Kültür_Festivali")
        self.assertWikipediaSlug(slug)

    def test_f06_make_seo_explicit_dirty_slug_sanitized(self):
        """Verify dirty slug with slashes, colons, and quotes is sanitized into clean underscore slug."""
        dirty = "Diyarbakır Haber: Test / '123'"
        slug = self._call_make_seo(dirty, "tr")
        self.assertEqual(slug, "Diyarbakır_Haber_Test_123")
        self.assertNotIn(":", slug)
        self.assertNotIn("/", slug)
        self.assertNotIn("'", slug)

    def test_f06_make_seo_strips_combining_dot_above_u0307(self):
        """Verify decomposed Unicode I + U+0307 combining dot above is stripped into NFC precomposed İ."""
        decomposed = "I\u0307stanbul Haber"
        slug = self._call_make_seo(decomposed, "tr")
        self.assertEqual(slug, "İstanbul_Haber")
        self.assertNotIn("\u0307", slug)

    def test_f06_make_seo_kurdish_locale_typo_normalization(self):
        """Verify Kurdish locale normalizes Turkish dotless ı -> i and dotted İ -> I."""
        title = "Pırs İnternet"
        slug = self._call_make_seo(title, "ku")
        self.assertEqual(slug, "Pirs_Internet")
        self.assertNotIn("ı", slug)
        self.assertNotIn("İ", slug)

    def test_f06_make_seo_path_traversal_and_xss_sanitization(self):
        """Verify path traversal sequences and XSS tags are stripped and double underscores collapsed."""
        self.assertEqual(self._call_make_seo("../../etc/passwd", "tr"), "etc_passwd")
        self.assertEqual(self._call_make_seo("<script>alert(1)</script>", "tr"), "script_alert1_script")
        self.assertEqual(self._call_make_seo("Leading--and--trailing__", "tr"), "Leading_and_trailing")
