"""
Tier 2 Boundary & Corner Cases: Feature 7 (get_news_enrichment).
Validates McpNewsTools::getNews boundary cases:
- Missing/deleted user record safely falls back to empty username and author_fullname
- User with empty name/last_name falls back to username for author_fullname
- Missing/deleted category record falls back to empty category_name and category_seo
- Non-existent news ID (0, negative, out of bounds) throws appropriate exceptions
- SEO lookup with non-matching language throws not found exception
- Strict parity between flat envelope and nested $res['item']
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(2)
@feature(7)
class TestF07GetNewsEnrichmentTier2(E2ETestCase):
    """Tier 2 tests for getNews enrichment boundaries."""

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

    def test_f07_boundary_missing_or_deleted_user_record(self):
        """When user_id does not exist, getNews returns empty username and author_fullname without failing."""
        code = """
$c = $t->createNews([
    'name' => 'Örnek Kullanıcısız Haber',
    'category_id' => 18,
    'summary' => 'Diyarbakır kent merkezinde yapımına başlanan ekolojik park projesinin ilk etabı tamamlandı ve açılışa hazırlanıyor.',
    'content' => '<p>Metin</p>',
    'lang' => 'tr',
    'status' => 'n'
]);
$newsId = (int)$c['id'];
$db = Load::lib('Database');
$db->query("UPDATE mod_news SET user_id = 9999999 WHERE id = {$newsId}");

try {
    $res = $t->getNews(['id' => $newsId]);
    echo json_encode([
        'success' => true,
        'username' => $res['username'],
        'author_fullname' => $res['author_fullname']
    ]);
} finally {
    $db->query("DELETE FROM mod_news WHERE id = {$newsId}");
}
"""
        res = self._eval_news_tool(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["username"], "")
        self.assertEqual(res["author_fullname"], "")

    def test_f07_boundary_user_with_empty_name_falls_back_to_username(self):
        """When user's name and last_name are empty strings, author_fullname falls back to username."""
        code = """
$db = Load::lib('Database');
$newsRow = $db->fetch_array("SELECT id, user_id FROM mod_news WHERE id = 1 LIMIT 1")->toArray()[0];
$uid = (int)$newsRow['user_id'];
$uRow = $db->fetch_array("SELECT username, name, last_name FROM mod_user WHERE id = {$uid} LIMIT 1")->toArray()[0];
$origName = $uRow['name'] ?? '';
$origLast = $uRow['last_name'] ?? '';
$username = $uRow['username'];

try {
    $db->query("UPDATE mod_user SET name = '', last_name = '' WHERE id = {$uid}");
    $res = $t->getNews(['id' => 1]);
    echo json_encode([
        'success' => true,
        'username' => $res['username'],
        'author_fullname' => $res['author_fullname'],
        'expected' => $username,
    ]);
} finally {
    $db->query("UPDATE mod_user SET name = '" . addslashes($origName) . "', last_name = '" . addslashes($origLast) . "' WHERE id = {$uid}");
}
"""
        res = self._eval_news_tool(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["author_fullname"], res["expected"])
        self.assertEqual(res["author_fullname"], res["username"])

    def test_f07_boundary_missing_or_deleted_category_record(self):
        """When category_id does not exist, getNews returns empty category_name and category_seo."""
        code = """
$c = $t->createNews([
    'name' => 'Örnek Kategorisiz Haber',
    'category_id' => 18,
    'summary' => 'Diyarbakır kent merkezinde yapımına başlanan ekolojik park projesinin ilk etabı tamamlandı ve açılışa hazırlanıyor.',
    'content' => '<p>Metin</p>',
    'lang' => 'tr',
    'status' => 'n'
]);
$newsId = (int)$c['id'];
$db = Load::lib('Database');
$db->query("UPDATE mod_news SET category_id = 9999999 WHERE id = {$newsId}");

try {
    $res = $t->getNews(['id' => $newsId]);
    echo json_encode([
        'success' => true,
        'category_name' => $res['category_name'],
        'category_seo' => $res['category_seo']
    ]);
} finally {
    $db->query("DELETE FROM mod_news WHERE id = {$newsId}");
}
"""
        res = self._eval_news_tool(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["category_name"], "")
        self.assertEqual(res["category_seo"], "")

    def test_f07_boundary_non_existent_news_id_0_and_negative_rejection(self):
        """Querying getNews with id <= 0 or missing id throws appropriate exception."""
        res_zero = self._eval_news_tool("$t->getNews(['id' => 0]);")
        self.assertFalse(res_zero["success"])

        res_neg = self._eval_news_tool("$t->getNews(['id' => -1]);")
        self.assertFalse(res_neg["success"])

        res_missing = self._eval_news_tool("$t->getNews(['id' => 99999999]);")
        self.assertFalse(res_missing["success"])
        self.assertIn("not found", res_missing["error"].lower())

    def test_f07_boundary_seo_lookup_with_non_matching_lang(self):
        """Querying news by seo slug under mismatched lang throws not found exception."""
        # Record 1 exists in Turkish with its SEO slug
        code = """
$db = Load::lib('Database');
$row = $db->fetch_array("SELECT seo FROM mod_news WHERE id = 1 LIMIT 1")->toArray();
$seo = $row[0]['seo'] ?? 'first_news';

$t->getNews(['seo' => $seo, 'lang' => 'ku']);
"""
        res = self._eval_news_tool(code)
        self.assertFalse(res["success"])
        self.assertIn("not found", res["error"].lower())

    def test_f07_boundary_dual_envelope_structure_integrity(self):
        """All enriched fields are identical between flat root properties and nested $res['item']."""
        code = """
$res = $t->getNews(['id' => 1]);
echo json_encode([
    'success' => true,
    'root_username' => $res['username'],
    'item_username' => $res['item']['username'],
    'root_author' => $res['author_fullname'],
    'item_author' => $res['item']['author_fullname'],
    'root_cat_name' => $res['category_name'],
    'item_cat_name' => $res['item']['category_name'],
    'root_cat_seo' => $res['category_seo'],
    'item_cat_seo' => $res['item']['category_seo'],
]);
"""
        res = self._eval_news_tool(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["root_username"], res["item_username"])
        self.assertEqual(res["root_author"], res["item_author"])
        self.assertEqual(res["root_cat_name"], res["item_cat_name"])
        self.assertEqual(res["root_cat_seo"], res["item_cat_seo"])
