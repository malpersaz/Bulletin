"""
Tier 2 Boundary & Corner Cases: Feature 5 (leaf_category_check).
Validates McpNewsTools leaf category enforcement:
- Category ID zero and negative numbers rejected
- Non-existent category ID throws not found exception
- Cross-language category assignment rejected
- Root category with no children returns leaf hint
- Root category with children lists selectable child IDs
- updateNews rejects switching an existing article to a root category
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(2)
@feature(5)
class TestF05LeafCategoryCheckTier2(E2ETestCase):
    """Tier 2 tests for leaf category validation boundaries."""

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

    def test_f05_boundary_category_id_zero_and_negative_rejection(self):
        """category_id <= 0 is rejected with category_id required error."""
        res_zero = self._eval_news_tool("""
$t->createNews([
    'name' => 'Geçerli Başlık',
    'category_id' => 0,
    'summary' => 'Haber özeti en az birkaç kelime içermelidir.',
    'lang' => 'tr',
    'content' => '<p>Haber metni</p>',
]);
""")
        self.assertFalse(res_zero["success"])
        self.assertIn("category_id", res_zero["error"].lower())

        res_neg = self._eval_news_tool("""
$t->createNews([
    'name' => 'Geçerli Başlık',
    'category_id' => -5,
    'summary' => 'Haber özeti en az birkaç kelime içermelidir.',
    'lang' => 'tr',
    'content' => '<p>Haber metni</p>',
]);
""")
        self.assertFalse(res_neg["success"])
        self.assertIn("category_id", res_neg["error"].lower())

    def test_f05_boundary_non_existent_category_id(self):
        """Non-existent category_id throws not found error."""
        res = self._eval_news_tool("""
$t->createNews([
    'name' => 'Geçerli Başlık',
    'category_id' => 9999999,
    'summary' => 'Haber özeti en az birkaç kelime içermelidir.',
    'lang' => 'tr',
    'content' => '<p>Haber metni</p>',
]);
""")
        self.assertFalse(res["success"])
        self.assertIn("not found", res["error"].lower())

    def test_f05_boundary_category_language_mismatch(self):
        """Selecting a category from a different language triggers language mismatch error."""
        code = """
$db = Load::lib('Database');
$rows = $db->fetch_array("SELECT id FROM mod_news_category WHERE lang = 'ku' LIMIT 1")->toArray();
if (empty($rows)) {
    $db->query("INSERT INTO mod_news_category (name, seo, lang, root, parent_id, user_id, `order`) VALUES ('Kategori Ku', 'kategori_ku', 'ku', 'n', 0, 1, 1)");
    $kuCatId = (int)$db->insert_id();
    $cleanup = true;
} else {
    $kuCatId = (int)$rows[0]['id'];
    $cleanup = false;
}
try {
    $t->createNews([
        'name' => 'Türkçe Haber Başlığı',
        'category_id' => $kuCatId,
        'summary' => 'Haber özeti burada yer almaktadır.',
        'lang' => 'tr',
        'content' => '<p>Türkçe metin</p>',
    ]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    if ($cleanup) {
        $db->query("DELETE FROM mod_news_category WHERE id = {$kuCatId}");
    }
}
"""
        res = self._eval_news_tool(code)
        self.assertFalse(res["success"])
        self.assertIn("must match", res["error"].lower())

    def test_f05_boundary_root_category_with_no_children_hint(self):
        """Root category without selectable children returns hint to select a leaf category."""
        code = """
$db = Load::lib('Database');
$db->query("INSERT INTO mod_news_category (name, seo, lang, root, parent_id, user_id, `order`, title, keywords, description) VALUES ('Empty Root Cat', 'empty_root_cat', 'tr', 'y', 0, 1, 99, '', '', '')");
$catId = (int)$db->insert_id();
try {
    $t->createNews([
        'name' => 'Örnek Haber Başlığı',
        'category_id' => $catId,
        'summary' => 'Diyarbakır kent merkezinde yapımına başlanan ekolojik park projesinin ilk etabı tamamlandı ve açılışa hazırlanıyor.',
        'lang' => 'tr',
        'content' => '<p>İçerik</p>',
    ]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    $db->query("DELETE FROM mod_news_category WHERE id = {$catId}");
}
"""
        res = self._eval_news_tool(code)
        self.assertFalse(res["success"])
        self.assertIn("structural root container", res["error"])
        self.assertIn("Please select a leaf category", res["error"])

    def test_f05_boundary_root_category_with_multiple_children_hints(self):
        """Root category with children provides child category hints in exception message."""
        code = """
$t->createNews([
    'name' => 'Yeni Park Projesi Başlatıldı',
    'category_id' => 17, // Root category for tr with children
    'summary' => 'Diyarbakır kent merkezinde yapımına başlanan ekolojik park projesinin ilk etabı tamamlandı ve açılışa hazırlanıyor.',
    'lang' => 'tr',
    'content' => '<p>İçerik detayları</p>',
]);
"""
        res = self._eval_news_tool(code)
        self.assertFalse(res["success"])
        self.assertIn("structural root container", res["error"])
        self.assertIn("Selectable child categories", res["error"])

    def test_f05_boundary_update_news_rejects_switching_to_root_category(self):
        """Updating existing article to a root category is blocked, leaving record untouched."""
        code = """
$db = Load::lib('Database');
$c = $t->createNews([
    'name' => 'Test Güncelleme Haberi',
    'category_id' => 18, // Leaf category
    'summary' => 'Diyarbakır kent merkezinde yapımına başlanan ekolojik park projesinin ilk etabı tamamlandı ve açılışa hazırlanıyor.',
    'lang' => 'tr',
    'content' => '<p>İlk içerik</p>',
    'status' => 'n'
]);
$newsId = (int)$c['id'];
try {
    $u = $t->updateNews([
        'id' => $newsId,
        'category_id' => 17 // Root category
    ]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    $db->query("DELETE FROM mod_news WHERE id = {$newsId}");
}
"""
        res = self._eval_news_tool(code)
        self.assertFalse(res["success"])
        self.assertIn("structural root container", res["error"])
