"""
Tier 1 Feature Coverage: Feature 5 (leaf_category_check).
Validates McpNewsTools rejects structural root category containers (root='y')
during news creation and update, while allowing leaf categories (root='n').
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(5)
class TestF05LeafCategoryCheck(E2ETestCase):
    """Tier 1 tests for leaf category validation in news staging."""

    def test_f05_create_news_with_leaf_category_succeeds(self):
        """Verify news creation succeeds when attached to a leaf category (root='n')."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpNewsTools(1, ['admin', 'editor']);
$uniq = uniqid();
$res = $t->createNews([
    'name' => 'Diyarbakır Ekolojik Parkı İhalesi ' . $uniq,
    'category_id' => 18, // Leaf category "Haberler"
    'summary' => 'Diyarbakır kent merkezinde inşa edilecek ekolojik park projesinin ihale süreci bugün resmi olarak başlatıldı.',
    'content' => '<p>Ekolojik park projesi detayları paylaşıldı.</p>',
    'lang' => 'tr',
    'status' => 'n'
]);
if (!empty($res['id'])) {
    $db = Load::lib('Database');
    $db->query("DELETE FROM mod_news WHERE id = " . (int)$res['id']);
}
echo json_encode(['created' => true, 'id' => $res['id'] ?? null]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("created"))
        self.assertIsNotNone(data.get("id"))

    def test_f05_create_news_with_root_category_rejected(self):
        """Verify news creation throws McpToolException when category is a root container (root='y')."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpNewsTools(1, ['admin', 'editor']);
$caught = false;
$msg = '';
try {
    $t->createNews([
        'name' => 'Kök Kategori Altında Deneme Haberi ' . uniqid(),
        'category_id' => 17, // Root category "##Türkçe_Kategoriler" (root='y')
        'summary' => 'Diyarbakır kent merkezinde inşa edilecek ekolojik park projesinin ihale süreci bugün resmi olarak başlatıldı.',
        'content' => '<p>İçerik buraya gelecek.</p>',
        'lang' => 'tr',
        'status' => 'n'
    ]);
} catch (McpToolException $e) {
    $caught = true;
    $msg = $e->getMessage();
}
echo json_encode(['caught' => $caught, 'msg' => $msg]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("caught"), "Must reject root category")
        self.assertIn("is a structural root container (root='y') and cannot contain news directly", data.get("msg", ""))

    def test_f05_root_category_exception_includes_available_leaf_children_hint(self):
        """Verify root category exception message lists child categories when children exist."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpNewsTools(1, ['admin', 'editor']);
$msg = '';
try {
    $t->createNews([
        'name' => 'Kök Kategori Çocuk İpuçları Testi ' . uniqid(),
        'category_id' => 17,
        'summary' => 'Diyarbakır kent merkezinde inşa edilecek ekolojik park projesinin ihale süreci bugün resmi olarak başlatıldı.',
        'content' => '<p>İçerik buraya gelecek.</p>',
        'lang' => 'tr',
        'status' => 'n'
    ]);
} catch (McpToolException $e) {
    $msg = $e->getMessage();
}
echo json_encode(['msg' => $msg]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        msg = data.get("msg", "")
        self.assertIn("Selectable child categories:", msg)
        self.assertIn("ID 18", msg)

    def test_f05_update_news_changing_to_root_category_rejected(self):
        """Verify updateNews throws McpToolException when attempting to reassign news to root category."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpNewsTools(1, ['admin', 'editor']);
$created = $t->createNews([
    'name' => 'Geçerli Haber Kök Kategori Değişim Testi ' . uniqid(),
    'category_id' => 18,
    'summary' => 'Diyarbakır kent merkezinde inşa edilecek ekolojik park projesinin ihale süreci bugün resmi olarak başlatıldı.',
    'content' => '<p>İçerik buraya gelecek.</p>',
    'lang' => 'tr',
    'status' => 'n'
]);
$id = (int)$created['id'];

$caught = false;
$msg = '';
try {
    $t->updateNews([
        'id' => $id,
        'category_id' => 17
    ]);
} catch (McpToolException $e) {
    $caught = true;
    $msg = $e->getMessage();
}

$db = Load::lib('Database');
$db->query("DELETE FROM mod_news WHERE id = " . $id);
echo json_encode(['caught' => $caught, 'msg' => $msg]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("caught"), "Must reject update to root category")
        self.assertIn("structural root container", data.get("msg", ""))

    def test_f05_root_category_without_children_clean_rejection_message(self):
        """Verify root category with no children returns fallback rejection hint."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpNewsTools(1, ['admin', 'editor']);
$cat = $t->createNewsCategory([
    'name' => 'Boş Kök Kategori ' . uniqid(),
    'lang' => 'tr',
    'root' => 'y',
    'parent_id' => 0
]);
$emptyRootId = (int)$cat['id'];

$caught = false;
$msg = '';
try {
    $t->createNews([
        'name' => 'Boş Kök Kategori Test Haberi ' . uniqid(),
        'category_id' => $emptyRootId,
        'summary' => 'Diyarbakır kent merkezinde inşa edilecek ekolojik park projesinin ihale süreci bugün resmi olarak başlatıldı.',
        'content' => '<p>İçerik buraya gelecek.</p>',
        'lang' => 'tr',
        'status' => 'n'
    ]);
} catch (McpToolException $e) {
    $caught = true;
    $msg = $e->getMessage();
}

$db = Load::lib('Database');
$db->query("DELETE FROM mod_news_category WHERE id = " . $emptyRootId);
echo json_encode(['caught' => $caught, 'msg' => $msg]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("caught"))
        self.assertIn("Please select a leaf category (root != 'y')", data.get("msg", ""))
