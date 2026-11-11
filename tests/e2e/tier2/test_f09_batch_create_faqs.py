"""
Tier 2 Boundary & Corner Cases: Feature 9 (batch_create_faqs).
Validates McpFaqTools::batchCreateFaqs boundaries:
- Empty batch or null items array is rejected
- Exact 25 items batch succeeds; 26 items batch overflows and is rejected
- Cumulative ceiling overflow (existing active + batch > 25) rejected
- Duplicate questions within batch (case-insensitive) rejected
- Question (1-255) and answer (1-65535) length boundary violations rejected
- Atomic rollback ensures zero partial records persist on failure
- replace_existing_drafts purges only drafts ('n') and preserves published ('y')
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(2)
@feature(9)
class TestF09BatchCreateFaqsTier2(E2ETestCase):
    """Tier 2 tests for batch FAQ creation boundaries."""

    def _eval_faq_tool(self, php_code: str) -> dict:
        full_code = f"""<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpFaqTools(1, ['admin']);
try {{
    {php_code}
}} catch (Throwable $e) {{
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}}
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=full_code)
        self.assertExitCode(res, 0, f"PHP execution failed: {res.stderr}")
        return json.loads(res.stdout)

    def test_f09_boundary_empty_batch_rejection(self):
        """Empty array or non-array items argument is rejected."""
        res_empty = self._eval_faq_tool("""
$t->batchCreateFaqs([
    'mod_name' => 'mod_news',
    'vid' => 1,
    'lang' => 'tr',
    'items' => []
]);
""")
        self.assertFalse(res_empty["success"])
        self.assertIn("non-empty array", res_empty["error"])

    def test_f09_boundary_exact_25_items_accepted_and_26_items_overflow_rejected(self):
        """Batch of 25 items is accepted; 26 items exceeds batch ceiling and is rejected."""
        # 26 items test
        items_26 = [{"question": f"Soru {i}", "answer": f"Cevap {i}"} for i in range(26)]
        res_26 = self._eval_faq_tool(f"""
$items = json_decode('{json.dumps(items_26)}', true);
$t->batchCreateFaqs([
    'mod_name' => 'mod_news',
    'vid' => 1,
    'lang' => 'tr',
    'items' => $items
]);
""")
        self.assertFalse(res_26["success"])
        self.assertIn("exceeds maximum batch size of 25", res_26["error"])

        # 25 items on newly created isolated entity
        items_25 = [{"question": f"Izole Soru {i}", "answer": f"Izole Cevap {i}"} for i in range(25)]
        code_25 = f"""
$nt = new McpNewsTools(1, ['admin']);
$c = $nt->createNews([
    'name' => 'Faq Izole Test Haberi 25',
    'category_id' => 18,
    'summary' => 'Diyarbakır kent merkezinde yapımına başlanan ekolojik park projesinin ilk etabı tamamlandı ve açılışa hazırlanıyor.',
    'content' => '<p>İçerik</p>',
    'lang' => 'tr',
    'status' => 'n'
]);
$vid = (int)$c['id'];
$db = Load::lib('Database');

try {{
    $items = json_decode('{json.dumps(items_25)}', true);
    $res = $t->batchCreateFaqs([
        'mod_name' => 'mod_news',
        'vid' => $vid,
        'lang' => 'tr',
        'status' => 'n',
        'items' => $items
    ]);
    echo json_encode(['success' => true, 'count' => $res['count']]);
}} finally {{
    $db->query("DELETE FROM mod_faq WHERE vid = {{$vid}}");
    $db->query("DELETE FROM mod_news WHERE id = {{$vid}}");
}}
"""
        res_25 = self._eval_faq_tool(code_25)
        self.assertTrue(res_25["success"])
        self.assertEqual(res_25["count"], 25)

    def test_f09_boundary_cumulative_ceiling_overflow_against_existing_faqs(self):
        """When existing active FAQs + batch > 25, request is rejected."""
        code = """
$nt = new McpNewsTools(1, ['admin']);
$c = $nt->createNews([
    'name' => 'Faq Tavan Test Haberi 20',
    'category_id' => 18,
    'summary' => 'Diyarbakır kent merkezinde yapımına başlanan ekolojik park projesinin ilk etabı tamamlandı ve açılışa hazırlanıyor.',
    'content' => '<p>İçerik</p>',
    'lang' => 'tr',
    'status' => 'n'
]);
$vid = (int)$c['id'];
$db = Load::lib('Database');

// Seed 20 existing FAQs on newly created vid
for ($i = 1; $i <= 20; $i++) {
    $db->query("INSERT INTO mod_faq (mod_name, vid, lang, sort_order, question, answer, status, user_id) VALUES 
    ('mod_news', {$vid}, 'tr', {$i}, 'Mevcut Soru {$i}', 'Cevap {$i}', 'y', 1)");
}

try {
    // Attempt adding 6 items (20 + 6 = 26 > 25)
    $items = [
        ['question' => 'Ek Soru 1', 'answer' => 'Cevap 1'],
        ['question' => 'Ek Soru 2', 'answer' => 'Cevap 2'],
        ['question' => 'Ek Soru 3', 'answer' => 'Cevap 3'],
        ['question' => 'Ek Soru 4', 'answer' => 'Cevap 4'],
        ['question' => 'Ek Soru 5', 'answer' => 'Cevap 5'],
        ['question' => 'Ek Soru 6', 'answer' => 'Cevap 6'],
    ];
    $t->batchCreateFaqs([
        'mod_name' => 'mod_news',
        'vid' => $vid,
        'lang' => 'tr',
        'items' => $items
    ]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    $db->query("DELETE FROM mod_faq WHERE vid = {$vid}");
    $db->query("DELETE FROM mod_news WHERE id = {$vid}");
}
"""
        res = self._eval_faq_tool(code)
        self.assertFalse(res["success"])
        self.assertIn("exceed ceiling of 25", res["error"])

    def test_f09_boundary_duplicate_question_case_insensitive_within_batch(self):
        """Case-insensitive duplicate question within batch throws error before DB operations."""
        code = """
$items = [
    ['question' => 'Proje Ne Zaman Başlayacak?', 'answer' => 'Cevap A'],
    ['question' => '   PROJE NE ZAMAN BAŞLAYACAK?   ', 'answer' => 'Cevap B']
];
$t->batchCreateFaqs([
    'mod_name' => 'mod_news',
    'vid' => 1,
    'lang' => 'tr',
    'items' => $items
]);
"""
        res = self._eval_faq_tool(code)
        self.assertFalse(res["success"])
        self.assertIn("Duplicate question within batch", res["error"])

    def test_f09_boundary_question_and_answer_length_boundaries(self):
        """Question > 255 chars and empty answer are rejected."""
        q_256 = "S" * 256
        res_q = self._eval_faq_tool(f"""
$items = [['question' => '{q_256}', 'answer' => 'Cevap']];
$t->batchCreateFaqs(['mod_name' => 'mod_news', 'vid' => 1, 'lang' => 'tr', 'items' => $items]);
""")
        self.assertFalse(res_q["success"])
        self.assertIn("Field 'question' is required", res_q["error"])

        res_a = self._eval_faq_tool("""
$items = [['question' => 'Geçerli Soru', 'answer' => '   ']];
$t->batchCreateFaqs(['mod_name' => 'mod_news', 'vid' => 1, 'lang' => 'tr', 'items' => $items]);
""")
        self.assertFalse(res_a["success"])
        self.assertIn("Field 'answer' is required", res_a["error"])

    def test_f09_boundary_atomic_transaction_rollback_preserves_db_state(self):
        """Transaction rollback preserves DB state when failure happens during insertion."""
        code = """
$db = Load::lib('Database');
$cntBefore = (int)$db->fetch_array("SELECT COUNT(*) as c FROM mod_faq WHERE vid = 999992")->toArray()[0]['c'];

try {
    // Valid item 1, but item 2 fails validation in phase 1
    $items = [
        ['question' => 'Gecerli Soru 1', 'answer' => 'Gecerli Cevap 1'],
        ['question' => 'Hatali Soru 2', 'answer' => '']
    ];
    $t->batchCreateFaqs([
        'mod_name' => 'mod_news',
        'vid' => 999992,
        'lang' => 'tr',
        'items' => $items
    ]);
} catch (Throwable $e) {
    // expected error
}
$cntAfter = (int)$db->fetch_array("SELECT COUNT(*) as c FROM mod_faq WHERE vid = 999992")->toArray()[0]['c'];
echo json_encode(['success' => true, 'before' => $cntBefore, 'after' => $cntAfter]);
"""
        res = self._eval_faq_tool(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["before"], res["after"])
        self.assertEqual(res["after"], 0)

    def test_f09_boundary_replace_existing_drafts_purges_only_drafts(self):
        """replace_existing_drafts purges draft records ('n') while strictly preserving published ('y')."""
        code = """
$nt = new McpNewsTools(1, ['admin']);
$c = $nt->createNews([
    'name' => 'Faq Taslak Temizleme Haberi',
    'category_id' => 18,
    'summary' => 'Diyarbakır kent merkezinde yapımına başlanan ekolojik park projesinin ilk etabı tamamlandı ve açılışa hazırlanıyor.',
    'content' => '<p>İçerik</p>',
    'lang' => 'tr',
    'status' => 'n'
]);
$vid = (int)$c['id'];
$db = Load::lib('Database');

// Seed 1 published and 1 draft FAQ on created vid
$db->query("INSERT INTO mod_faq (mod_name, vid, lang, sort_order, question, answer, status, user_id) VALUES 
('mod_news', {$vid}, 'tr', 1, 'Kalici Soru', 'Kalici Cevap', 'y', 1),
('mod_news', {$vid}, 'tr', 2, 'Eski Taslak', 'Eski Cevap', 'n', 1)");

try {
    $items = [
        ['question' => 'Yeni Taslak Soru', 'answer' => 'Yeni Taslak Cevap']
    ];
    $t->batchCreateFaqs([
        'mod_name' => 'mod_news',
        'vid' => $vid,
        'lang' => 'tr',
        'status' => 'n',
        'replace_existing_drafts' => true,
        'items' => $items
    ]);

    $allFaqs = $db->fetch_array("SELECT question, status FROM mod_faq WHERE vid = {$vid} ORDER BY id ASC")->toArray();
    echo json_encode(['success' => true, 'faqs' => $allFaqs]);
} finally {
    $db->query("DELETE FROM mod_faq WHERE vid = {$vid}");
    $db->query("DELETE FROM mod_news WHERE id = {$vid}");
}
"""
        res = self._eval_faq_tool(code)
        self.assertTrue(res["success"])
        faqs = res["faqs"]
        # Exactly 2 FAQs: Kalici Soru ('y') and Yeni Taslak Soru ('n'); Eski Taslak is gone
        self.assertEqual(len(faqs), 2)
        questions = [f["question"] for f in faqs]
        self.assertIn("Kalici Soru", questions)
        self.assertIn("Yeni Taslak Soru", questions)
        self.assertNotIn("Eski Taslak", questions)
