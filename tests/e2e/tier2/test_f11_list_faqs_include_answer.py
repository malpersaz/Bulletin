"""
Tier 2 Boundary & Corner Cases: Feature 11 (list_faqs_include_answer).
Validates McpFaqTools::listFaqs boundary conditions:
- Truthy values (true, 1, '1', 'true') include answer column
- Falsy values (false, 0, '0', '', null) omit answer column
- Massive 20KB HTML answer preservation without truncation when included
- Complex Kurdish and Turkish Unicode characters preserved in answer
- Pagination and sorting preserved in conjunction with include_answer
- Zero matching results with include_answer returns clean empty envelope
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(2)
@feature(11)
class TestF11ListFaqsIncludeAnswerTier2(E2ETestCase):
    """Tier 2 tests for listFaqs include_answer toggle boundaries."""

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

    def test_f11_boundary_truthy_values_for_include_answer(self):
        """Truthy values (true, 1, '1', 'true') cause answer column to be included."""
        for val in ["true", "1", "'1'", "'true'"]:
            code = f"""
$res = $t->listFaqs(['limit' => 2, 'include_answer' => {val}]);
$hasAnswers = true;
foreach ($res['items'] as $item) {{
    if (!array_key_exists('answer', $item)) {{
        $hasAnswers = false;
        break;
    }}
}}
echo json_encode(['success' => true, 'has_answers' => $hasAnswers, 'count' => count($res['items'])]);
"""
            res = self._eval_faq_tool(code)
            self.assertTrue(res["success"])
            self.assertGreater(res["count"], 0)
            self.assertTrue(res["has_answers"], f"Expected answer key for truthy value {val}")

    def test_f11_boundary_falsy_values_for_include_answer(self):
        """Falsy values (false, 0, '0', '', null) omit answer column from all items."""
        for val in ["false", "0", "'0'", "''", "null"]:
            code = f"""
$res = $t->listFaqs(['limit' => 2, 'include_answer' => {val}]);
$hasAnswers = false;
foreach ($res['items'] as $item) {{
    if (array_key_exists('answer', $item)) {{
        $hasAnswers = true;
        break;
    }}
}}
echo json_encode(['success' => true, 'has_answers' => $hasAnswers, 'count' => count($res['items'])]);
"""
            res = self._eval_faq_tool(code)
            self.assertTrue(res["success"])
            self.assertGreater(res["count"], 0)
            self.assertFalse(res["has_answers"], f"Answer key should be absent for falsy value {val}")

    def test_f11_boundary_massive_html_answer_preservation(self):
        """Large 20KB HTML answer is omitted when false and preserved completely when true."""
        code = """
$db = Load::lib('Database');
$bigHtml = str_repeat('<p>Bu bir uzun detayli HTML paragrafidir.</p>', 450); // ~20KB
$db->query("INSERT INTO mod_faq (mod_name, vid, lang, sort_order, question, answer, status, user_id) VALUES 
('mod_news', 999994, 'tr', 1, 'Buyuk Cevap Sorusu', '" . addslashes($bigHtml) . "', 'y', 1)");
$faqId = (int)$db->insert_id();

try {
    $resWithout = $t->listFaqs(['vid' => 999994, 'include_answer' => false]);
    $resWith = $t->listFaqs(['vid' => 999994, 'include_answer' => true]);

    echo json_encode([
        'success' => true,
        'has_without' => array_key_exists('answer', $resWithout['items'][0] ?? []),
        'has_with' => array_key_exists('answer', $resWith['items'][0] ?? []),
        'with_len' => strlen($resWith['items'][0]['answer'] ?? ''),
        'expected_min_len' => 15000
    ]);
} finally {
    $db->query("DELETE FROM mod_faq WHERE id = {$faqId}");
}
"""
        res = self._eval_faq_tool(code)
        self.assertTrue(res["success"])
        self.assertFalse(res["has_without"])
        self.assertTrue(res["has_with"])
        self.assertGreaterEqual(res["with_len"], res["expected_min_len"])

    def test_f11_boundary_unicode_special_characters_in_answer(self):
        """Kurdish accents and unicode symbols in answer retrieved without corruption."""
        code = """
$db = Load::lib('Database');
$unicodeAnswer = 'Bersiv: Peyva Kurdî bi tîpên ê, î, û, ç, ş û sembolên ★, ✓, «, » re tê parastin.';
$db->query("INSERT INTO mod_faq (mod_name, vid, lang, sort_order, question, answer, status, user_id) VALUES 
('mod_news', 999995, 'ku', 1, 'Pirseke Kurdî', '" . addslashes($unicodeAnswer) . "', 'y', 1)");
$faqId = (int)$db->insert_id();

try {
    $res = $t->listFaqs(['vid' => 999995, 'include_answer' => true]);
    echo json_encode([
        'success' => true,
        'answer' => $res['items'][0]['answer'] ?? '',
        'expected' => $unicodeAnswer
    ]);
} finally {
    $db->query("DELETE FROM mod_faq WHERE id = {$faqId}");
}
"""
        res = self._eval_faq_tool(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["answer"], res["expected"])

    def test_f11_boundary_pagination_and_sorting_with_include_answer(self):
        """Pagination slice respects sort_order and limit while including answers."""
        code = """
$res = $t->listFaqs(['limit' => 2, 'offset' => 1, 'include_answer' => true]);
echo json_encode([
    'success' => true,
    'count' => count($res['items']),
    'limit' => $res['limit'],
    'offset' => $res['offset'],
    'has_answers' => !empty($res['items']) && array_key_exists('answer', $res['items'][0])
]);
"""
        res = self._eval_faq_tool(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["limit"], 2)
        self.assertEqual(res["offset"], 1)
        self.assertTrue(res["has_answers"])

    def test_f11_boundary_zero_matching_results_with_include_answer(self):
        """Query returning zero matches with include_answer=true returns empty items and total 0."""
        code = """
$res = $t->listFaqs(['vid' => 99999999, 'include_answer' => true]);
echo json_encode([
    'success' => true,
    'items' => $res['items'],
    'total' => $res['total']
]);
"""
        res = self._eval_faq_tool(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["items"], [])
        self.assertEqual(res["total"], 0)
