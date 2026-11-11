"""
Tier 2 Boundary & Corner Cases: Feature 8 (check_duplicate_news).
Validates McpNewsTools::checkDuplicateNews boundary conditions:
- Exact 48h boundary cutoff (47h 59m included, 48h 01m excluded)
- Whitespace and punctuation variations with case folding match with 1.0 similarity
- Exact SEO slug match with differing title tokens triggers exact_seo_match
- Parameter clamping for hours (clamped to [1, 168])
- Parameter clamping for threshold (clamped to [0.1, 1.0])
- Cross-language isolation prevents false positive matches across languages
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(2)
@feature(8)
class TestF08CheckDuplicateNewsTier2(E2ETestCase):
    """Tier 2 tests for duplicate news detection boundaries."""

    def _eval_news_tool(self, php_code: str) -> dict:
        full_code = f"""<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpNewsTools(1, ['admin', 'editor']);
try {{
    {php_code}
}} catch (Throwable $e) {{
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}}
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=full_code)
        self.assertExitCode(res, 0, f"PHP execution failed: {res.stderr}")
        return json.loads(res.stdout)

    def test_f08_boundary_exact_48h_boundary_47h59m_vs_48h01m(self):
        """Record at 47h59m is within 48h window; record at 48h01m is outside."""
        code = """
$db = Load::lib('Database');
$c = $t->createNews([
    'name' => 'Kırk Sekiz Saat Sınır Test Haberi',
    'category_id' => 18,
    'summary' => 'Diyarbakır kent merkezinde yapımına başlanan ekolojik park projesinin ilk etabı tamamlandı ve açılışa hazırlanıyor.',
    'content' => '<p>İçerik</p>',
    'lang' => 'tr',
    'status' => 'n'
]);
$newsId = (int)$c['id'];

try {
    // 1. Set created_at to 47h 59m ago
    $dtInside = date('Y-m-d H:i:s', time() - (47 * 3600 + 59 * 60));
    $db->query("UPDATE mod_news SET created_at = '{$dtInside}' WHERE id = {$newsId}");
    $resInside = $t->checkDuplicateNews(['name' => 'Kırk Sekiz Saat Sınır Test Haberi', 'lang' => 'tr', 'hours' => 48]);

    // 2. Set created_at to 48h 01m ago
    $dtOutside = date('Y-m-d H:i:s', time() - (48 * 3600 + 60));
    $db->query("UPDATE mod_news SET created_at = '{$dtOutside}' WHERE id = {$newsId}");
    $resOutside = $t->checkDuplicateNews(['name' => 'Kırk Sekiz Saat Sınır Test Haberi', 'lang' => 'tr', 'hours' => 48]);

    echo json_encode([
        'success' => true,
        'inside' => $resInside['has_duplicate'],
        'outside' => $resOutside['has_duplicate']
    ]);
} finally {
    $db->query("DELETE FROM mod_news WHERE id = {$newsId}");
}
"""
        res = self._eval_news_tool(code)
        self.assertTrue(res["success"])
        self.assertTrue(res["inside"], "Record within 47h59m should be detected as duplicate")
        self.assertFalse(res["outside"], "Record older than 48h01m should not be detected")

    def test_f08_boundary_whitespace_and_punctuation_token_jaccard_similarity(self):
        """Extreme whitespace and punctuation variations produce 1.0 token similarity."""
        code = """
$db = Load::lib('Database');
$c = $t->createNews([
    'name' => 'Diyarbakır Surları Onarımı Başladı',
    'category_id' => 18,
    'summary' => 'Diyarbakır kent merkezinde yapımına başlanan ekolojik park projesinin ilk etabı tamamlandı ve açılışa hazırlanıyor.',
    'content' => '<p>İçerik</p>',
    'lang' => 'tr',
    'status' => 'n'
]);
$newsId = (int)$c['id'];

try {
    $dup = $t->checkDuplicateNews([
        'name' => '   Diyarbakır   Surları   Onarımı   Başladı!!!   ',
        'lang' => 'tr'
    ]);
    echo json_encode([
        'success' => true,
        'has_duplicate' => $dup['has_duplicate'],
        'similarity' => $dup['duplicates'][0]['similarity'] ?? 0.0
    ]);
} finally {
    $db->query("DELETE FROM mod_news WHERE id = {$newsId}");
}
"""
        res = self._eval_news_tool(code)
        self.assertTrue(res["success"])
        self.assertTrue(res["has_duplicate"])
        self.assertEqual(res["similarity"], 1.0)

    def test_f08_boundary_exact_seo_slug_match_with_different_title_tokens(self):
        """Exact SEO slug match triggers duplicate detection even if title word tokens are below threshold."""
        code = """
$db = Load::lib('Database');
$c = $t->createNews([
    'name' => 'Sur İçi Yeni Düzenleme',
    'category_id' => 18,
    'summary' => 'Diyarbakır kent merkezinde yapımına başlanan ekolojik park projesinin ilk etabı tamamlandı ve açılışa hazırlanıyor.',
    'content' => '<p>İçerik</p>',
    'lang' => 'tr',
    'status' => 'n'
]);
$newsId = (int)$c['id'];
$seo = $db->fetch_array("SELECT seo FROM mod_news WHERE id = {$newsId}")->toArray()[0]['seo'];

try {
    // Propose completely different title words, but with explicit matching seo slug
    $dup = $t->checkDuplicateNews([
        'name' => 'Bambaşka Bir Başlık İfadesi',
        'seo' => $seo,
        'lang' => 'tr'
    ]);
    echo json_encode([
        'success' => true,
        'has_duplicate' => $dup['has_duplicate'],
        'reasons' => $dup['duplicates'][0]['match_reasons'] ?? []
    ]);
} finally {
    $db->query("DELETE FROM mod_news WHERE id = {$newsId}");
}
"""
        res = self._eval_news_tool(code)
        self.assertTrue(res["success"])
        self.assertTrue(res["has_duplicate"])
        self.assertIn("exact_seo_match", res["reasons"])

    def test_f08_boundary_hours_clamping_limits(self):
        """Hours parameter is clamped to [1, 168]."""
        code = """
$res_low = $t->checkDuplicateNews(['name' => 'Test Başlık', 'lang' => 'tr', 'hours' => 0]);
$res_high = $t->checkDuplicateNews(['name' => 'Test Başlık', 'lang' => 'tr', 'hours' => 500]);
echo json_encode([
    'success' => true,
    'hours_low' => $res_low['hours_window'],
    'hours_high' => $res_high['hours_window']
]);
"""
        res = self._eval_news_tool(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["hours_low"], 1)
        self.assertEqual(res["hours_high"], 168)

    def test_f08_boundary_threshold_clamping_limits(self):
        """Threshold parameter is clamped to [0.1, 1.0]."""
        code = """
$res_low = $t->checkDuplicateNews(['name' => 'Test Başlık', 'lang' => 'tr', 'threshold' => 0.0]);
$res_high = $t->checkDuplicateNews(['name' => 'Test Başlık', 'lang' => 'tr', 'threshold' => 2.5]);
echo json_encode([
    'success' => true,
    'threshold_low' => $res_low['threshold'],
    'threshold_high' => $res_high['threshold']
]);
"""
        res = self._eval_news_tool(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["threshold_low"], 0.1)
        self.assertEqual(res["threshold_high"], 1.0)

    def test_f08_boundary_cross_language_isolation(self):
        """Identical headline created in Turkish does not match duplicate check in Kurdish."""
        code = """
$db = Load::lib('Database');
$c = $t->createNews([
    'name' => 'Amedspor Galibiyeti Kutlandı',
    'category_id' => 18,
    'summary' => 'Diyarbakır kent merkezinde yapımına başlanan ekolojik park projesinin ilk etabı tamamlandı ve açılışa hazırlanıyor.',
    'content' => '<p>İçerik</p>',
    'lang' => 'tr',
    'status' => 'n'
]);
$newsId = (int)$c['id'];

try {
    $dup_ku = $t->checkDuplicateNews([
        'name' => 'Amedspor Galibiyeti Kutlandı',
        'lang' => 'ku'
    ]);
    echo json_encode([
        'success' => true,
        'has_duplicate' => $dup_ku['has_duplicate']
    ]);
} finally {
    $db->query("DELETE FROM mod_news WHERE id = {$newsId}");
}
"""
        res = self._eval_news_tool(code)
        self.assertTrue(res["success"])
        self.assertFalse(res["has_duplicate"], "Language isolation must prevent cross-language duplicates")
