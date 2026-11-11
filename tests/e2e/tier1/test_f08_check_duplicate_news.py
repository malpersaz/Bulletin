"""
Tier 1 Feature Coverage: Feature 8 (check_duplicate_news).
Validates McpNewsTools::checkDuplicateNews detects identical headlines, Jaccard token similarity
above 0.55, exact SEO slug collisions, and respects exclude_id and hours window parameters.
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(8)
class TestF08CheckDuplicateNews(E2ETestCase):
    """Tier 1 tests for pre-staging duplicate news check."""

    def test_f08_check_duplicate_identical_headline_flagged(self):
        """Verify identical headline within 48h is detected with similarity 1.0."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpNewsTools(1, ['admin']);
$rand = rand(1000, 9999);
$title = "Diyarbakır Şehir Hastanesi Projesi İhalesi " . $rand;

$created = $t->createNews([
    'name' => $title,
    'category_id' => 18,
    'summary' => 'Diyarbakır kent merkezinde inşa edilecek yeni hastane projesinin ihale süreci bugün tamamlandı.',
    'content' => '<p>Hastane ihalesi tamamlandı.</p>',
    'lang' => 'tr',
    'status' => 'n'
]);
$id = (int)$created['id'];

$dup = $t->checkDuplicateNews([
    'name' => $title,
    'lang' => 'tr'
]);

$db = Load::lib('Database');
$db->query("DELETE FROM mod_news WHERE id = " . $id);

echo json_encode($dup);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("has_duplicate"))
        self.assertGreaterEqual(data.get("duplicate_count", 0), 1)
        match = data["duplicates"][0]
        self.assertEqual(match.get("similarity"), 1.0)
        self.assertIn("title_similarity", match.get("match_reasons", []))

    def test_f08_check_duplicate_jaccard_similarity_above_threshold(self):
        """Verify Jaccard token similarity above 0.55 triggers duplicate flag."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpNewsTools(1, ['admin']);
$rand = rand(1000, 9999);
$seedTitle = "Belediye Park ve Bahçeler Birimi Açılış Yaptı " . $rand;
$proposedTitle = "Belediye Park ve Bahçeler Birimi Açılışı Yaptı " . $rand;

$created = $t->createNews([
    'name' => $seedTitle,
    'category_id' => 18,
    'summary' => 'Diyarbakır kent merkezinde inşa edilecek ekolojik park projesinin ihale süreci bugün resmi olarak başlatıldı.',
    'content' => '<p>İçerik</p>',
    'lang' => 'tr',
    'status' => 'n'
]);
$id = (int)$created['id'];

$dup = $t->checkDuplicateNews([
    'name' => $proposedTitle,
    'lang' => 'tr'
]);

$db = Load::lib('Database');
$db->query("DELETE FROM mod_news WHERE id = " . $id);

echo json_encode($dup);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("has_duplicate"))
        self.assertGreaterEqual(data.get("duplicate_count", 0), 1)

    def test_f08_check_duplicate_distinct_news_returns_false(self):
        """Verify distinct news title returns has_duplicate == False and zero duplicates."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpNewsTools(1, ['admin']);
$dup = $t->checkDuplicateNews([
    'name' => 'Tamamen Benzersiz Astronomi Kulübü ' . rand(10000, 99999),
    'lang' => 'tr'
]);
echo json_encode($dup);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertFalse(data.get("has_duplicate"))
        self.assertEqual(data.get("duplicate_count"), 0)
        self.assertEqual(data.get("duplicates"), [])

    def test_f08_check_duplicate_exact_seo_collision_detected(self):
        """Verify proposed headline generating identical SEO slug flags exact_seo_match."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpNewsTools(1, ['admin']);
$rand = rand(1000, 9999);
$titleA = "Diyarbakır Kültür Yolu Festivali " . $rand;
$titleB = "Diyarbakır: Kültür Yolu Festivali! " . $rand;

$created = $t->createNews([
    'name' => $titleA,
    'category_id' => 18,
    'summary' => 'Diyarbakır kent merkezinde inşa edilecek kültür yolu projesinin ihale süreci bugün resmi olarak başlatıldı.',
    'content' => '<p>İçerik</p>',
    'lang' => 'tr',
    'status' => 'n'
]);
$id = (int)$created['id'];

$dup = $t->checkDuplicateNews([
    'name' => $titleB,
    'lang' => 'tr'
]);

$db = Load::lib('Database');
$db->query("DELETE FROM mod_news WHERE id = " . $id);

echo json_encode($dup);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("has_duplicate"))
        match = data["duplicates"][0]
        self.assertIn("exact_seo_match", match.get("match_reasons", []))

    def test_f08_check_duplicate_exclude_id_and_hours_window(self):
        """Verify exclude_id ignores target article and hours window limits search range."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpNewsTools(1, ['admin']);
$rand = rand(1000, 9999);
$title = "Düzenlenen Makale Başlığı " . $rand;

$created = $t->createNews([
    'name' => $title,
    'category_id' => 18,
    'summary' => 'Diyarbakır kent merkezinde inşa edilecek ekolojik park projesinin ihale süreci bugün resmi olarak başlatıldı.',
    'content' => '<p>İçerik</p>',
    'lang' => 'tr',
    'status' => 'n'
]);
$id = (int)$created['id'];

// Check duplicate while excluding self
$dupExcluded = $t->checkDuplicateNews([
    'name' => $title,
    'lang' => 'tr',
    'exclude_id' => $id
]);

// Set created_at to 3 hours ago and test with hours=1
$db = Load::lib('Database');
$db->query("UPDATE mod_news SET created_at = '" . date('Y-m-d H:i:s', time() - 10800) . "' WHERE id = " . $id);

$dupHours = $t->checkDuplicateNews([
    'name' => $title,
    'lang' => 'tr',
    'hours' => 1
]);

$db->query("DELETE FROM mod_news WHERE id = " . $id);

echo json_encode([
    'excluded_duplicate' => $dupExcluded['has_duplicate'],
    'hours_duplicate' => $dupHours['has_duplicate']
]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertFalse(data.get("excluded_duplicate"))
        self.assertFalse(data.get("hours_duplicate"))
