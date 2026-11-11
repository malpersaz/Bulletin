"""
Tier 1 Feature Coverage: Feature 7 (get_news_enrichment).
Validates McpNewsTools::getNews enriches responses with username, author_fullname,
category_name, and category_seo, and provides dual flat + envelope structure.
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(7)
class TestF07GetNewsEnrichment(E2ETestCase):
    """Tier 1 tests for getNews response enrichment."""

    def test_f07_get_news_by_id_returns_enriched_author_fields(self):
        """Verify getNews by ID returns username and author_fullname from mod_user."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpNewsTools(1, ['admin']);
$res = $t->getNews(['id' => 1]);
echo json_encode([
    'username' => $res['username'] ?? null,
    'author_fullname' => $res['author_fullname'] ?? null
]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertIsNotNone(data.get("username"))
        self.assertIsNotNone(data.get("author_fullname"))
        self.assertTrue(len(data["username"]) > 0)
        self.assertTrue(len(data["author_fullname"]) > 0)

    def test_f07_get_news_by_id_returns_enriched_category_fields(self):
        """Verify getNews by ID returns category_name and category_seo from mod_news_category."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpNewsTools(1, ['admin']);
$res = $t->getNews(['id' => 1]);
echo json_encode([
    'category_name' => $res['category_name'] ?? null,
    'category_seo' => $res['category_seo'] ?? null
]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertIsNotNone(data.get("category_name"))
        self.assertIsNotNone(data.get("category_seo"))
        self.assertTrue(len(data["category_name"]) > 0)
        self.assertTrue(len(data["category_seo"]) > 0)

    def test_f07_get_news_by_seo_and_lang_returns_identical_enrichment(self):
        """Verify querying by seo + lang returns identical enrichment to querying by id."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpNewsTools(1, ['admin']);
$byId = $t->getNews(['id' => 1]);
$bySeo = $t->getNews(['seo' => $byId['seo'], 'lang' => $byId['lang']]);

echo json_encode([
    'same_user' => ($byId['username'] === $bySeo['username']),
    'same_author' => ($byId['author_fullname'] === $bySeo['author_fullname']),
    'same_cat_name' => ($byId['category_name'] === $bySeo['category_name']),
    'same_cat_seo' => ($byId['category_seo'] === $bySeo['category_seo']),
]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("same_user"))
        self.assertTrue(data.get("same_author"))
        self.assertTrue(data.get("same_cat_name"))
        self.assertTrue(data.get("same_cat_seo"))

    def test_f07_get_news_dual_envelope_structure(self):
        """Verify getNews response provides enriched fields at root level and within nested item."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpNewsTools(1, ['admin']);
$res = $t->getNews(['id' => 1]);
$item = $res['item'] ?? [];

echo json_encode([
    'has_nested_item' => is_array($item) && !empty($item),
    'root_user' => $res['username'] ?? '',
    'item_user' => $item['username'] ?? '',
    'root_cat' => $res['category_name'] ?? '',
    'item_cat' => $item['category_name'] ?? ''
]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("has_nested_item"))
        self.assertEqual(data["root_user"], data["item_user"])
        self.assertEqual(data["root_cat"], data["item_cat"])

    def test_f07_get_news_author_fallback_when_user_or_category_missing(self):
        """Verify getNews returns empty strings instead of crashing when user_id or category_id is 0."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$db = Load::lib('Database');
$uniq = uniqid();
$db->query("INSERT INTO mod_news (lang, name, category_id, status, summary, content, seo, title, keywords, description, user_id) VALUES 
('tr', 'No Author No Cat News " . $uniq . "', 0, 'n', 'Test summary with enough characters for news validation in amed framework cms.', '<p>Body</p>', 'no_author_test_" . $uniq . "', 'No Author Title', 'kw', 'desc', 0)");
$db->fetch_array("SELECT id FROM mod_news WHERE name = 'No Author No Cat News " . $uniq . "' LIMIT 1");
$row = $db->toArray()[0];
$id = (int)$row['id'];

$t = new McpNewsTools(1, ['admin']);
$res = $t->getNews(['id' => $id]);

$db->query("DELETE FROM mod_news WHERE id = " . $id);

echo json_encode([
    'username' => $res['username'],
    'author_fullname' => $res['author_fullname'],
    'category_name' => $res['category_name'],
    'category_seo' => $res['category_seo']
]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertEqual(data.get("username"), "")
        self.assertEqual(data.get("author_fullname"), "")
        self.assertEqual(data.get("category_name"), "")
        self.assertEqual(data.get("category_seo"), "")
