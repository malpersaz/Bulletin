"""
Tier 1 Feature Coverage: Feature 4 (headline_type_assertion).
Validates McpHeadlineTools passes $name as string to assertContentMatchesLang,
preventing PHP 8.2+ TypeError, and verifies language matching and length boundaries.
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(4)
class TestF04HeadlineTypeAssertion(E2ETestCase):
    """Tier 1 tests for McpHeadlineTools type assertion fix and language validation."""

    def test_f04_create_headline_valid_string_no_type_error(self):
        """Verify createHeadline accepts string name without TypeError and returns created id."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpHeadlineTools(1, ['editor']);
$res = $t->createHeadline(['name' => 'Amedspor Şampiyonluk Yolunda Yeni Galibiyet Aldı', 'lang' => 'tr']);
if (!empty($res['id'])) {
    $t->deleteHeadline(['id' => $res['id']]);
}
echo json_encode($res);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        self.assertNotIn("TypeError", res.stderr)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("created"))
        self.assertIsInstance(data.get("id"), int)
        self.assertIn("item", data)

    def test_f04_update_headline_valid_string_no_type_error(self):
        """Verify updateHeadline passes string name to assertContentMatchesLang without TypeError."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpHeadlineTools(1, ['editor']);
$c = $t->createHeadline(['name' => 'İlk Başlık Test Metni', 'lang' => 'tr']);
$id = $c['id'];

$u = $t->updateHeadline(['id' => $id, 'name' => 'Amed Şehir Tiyatrosu Yeni Sezon Prömiyeri']);
$t->deleteHeadline(['id' => $id]);

echo json_encode(['updated' => $u['updated'] ?? false, 'id' => $id]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        self.assertNotIn("TypeError", res.stderr)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("updated"))

    def test_f04_headline_language_matching_kurdish_latin(self):
        """Verify Kurdish Latin headline passes language gate when lang='ku'."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$db = Load::lib('Database');
$db->query("UPDATE lang SET status = 'y' WHERE seo = 'ku'");

$t = new McpHeadlineTools(1, ['editor']);
$c = $t->createHeadline(['name' => 'Diyarbekir di dîroka Kurdistanê de ciyekî girîng digire', 'lang' => 'ku']);
if (!empty($c['id'])) {
    $t->deleteHeadline(['id' => $c['id']]);
}
echo json_encode(['created' => $c['created'] ?? false, 'lang' => $c['item']['lang'] ?? '']);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("created"))
        self.assertEqual(data.get("lang"), "ku")

    def test_f04_headline_language_mismatch_detection(self):
        """Verify Turkish text with ğ/ı/ş and stopwords submitted under lang='ku' raises language mismatch exception."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$db = Load::lib('Database');
$db->query("UPDATE lang SET status = 'y' WHERE seo = 'ku'");

$t = new McpHeadlineTools(1, ['editor']);
$caught = false;
$msg = '';
try {
    $t->createHeadline(['name' => 'Sağlık Bakanlığı tarafından yapılan yeni hastane açılışı hakkında', 'lang' => 'ku']);
} catch (McpToolException $e) {
    $caught = true;
    $msg = $e->getMessage();
}
echo json_encode(['caught' => $caught, 'msg' => $msg]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("caught"), "Should raise McpToolException on language mismatch")
        self.assertIn("language mismatch", data.get("msg", "").lower())

    def test_f04_headline_name_boundary_validation(self):
        """Verify headline with name shorter than 2 chars is rejected."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpHeadlineTools(1, ['editor']);
$caught = false;
$msg = '';
try {
    $t->createHeadline(['name' => 'A', 'lang' => 'tr']);
} catch (McpToolException $e) {
    $caught = true;
    $msg = $e->getMessage();
}
echo json_encode(['caught' => $caught, 'msg' => $msg]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("caught"), "Should reject headline shorter than 2 characters")
        self.assertIn("2-255 characters", data.get("msg", ""))
