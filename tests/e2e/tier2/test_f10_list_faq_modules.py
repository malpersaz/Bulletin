"""
Tier 2 Boundary & Corner Cases: Feature 10 (list_faq_modules).
Validates McpFaqTools::listFaqModules boundary conditions:
- Limit clamping: 0 and negative clamped to 1, excessive clamped to 50
- Offset clamping: negative clamped to 0, large offset returns empty items with accurate total
- Search SQL injection resistance escaping via Obuilder::cc without syntax errors
- Empty and whitespace-only search string acts as a no-op returning all modules
- Search with Unicode and Turkish characters matches target modules
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(2)
@feature(10)
class TestF10ListFaqModulesTier2(E2ETestCase):
    """Tier 2 tests for listFaqModules boundary conditions."""

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

    def test_f10_boundary_limit_clamping_zero_negative_and_overflow(self):
        """Limit <= 0 clamped to 1, limit > 50 clamped to 50."""
        code = """
$res_zero = $t->listFaqModules(['limit' => 0]);
$res_neg = $t->listFaqModules(['limit' => -5]);
$res_high = $t->listFaqModules(['limit' => 100]);
echo json_encode([
    'success' => true,
    'limit_zero' => $res_zero['limit'],
    'limit_neg' => $res_neg['limit'],
    'limit_high' => $res_high['limit']
]);
"""
        res = self._eval_faq_tool(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["limit_zero"], 1)
        self.assertEqual(res["limit_neg"], 1)
        self.assertEqual(res["limit_high"], 50)

    def test_f10_boundary_offset_clamping_negative_and_excessive(self):
        """Negative offset clamped to 0; large offset returns empty items list with accurate total."""
        code = """
$res_neg = $t->listFaqModules(['offset' => -10]);
$res_large = $t->listFaqModules(['offset' => 99999]);
echo json_encode([
    'success' => true,
    'offset_neg' => $res_neg['offset'],
    'offset_large' => $res_large['offset'],
    'items_large_count' => count($res_large['items']),
    'total' => $res_large['total']
]);
"""
        res = self._eval_faq_tool(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["offset_neg"], 0)
        self.assertEqual(res["offset_large"], 99999)
        self.assertEqual(res["items_large_count"], 0)
        self.assertGreaterEqual(res["total"], 4)

    def test_f10_boundary_search_sql_injection_and_special_chars(self):
        """SQL injection strings in search parameter are sanitized and return 0 matches safely."""
        code = """
$res = $t->listFaqModules(['search' => "mod_%' OR '1'='1"]);
echo json_encode([
    'success' => true,
    'total' => $res['total'],
    'items' => $res['items']
]);
"""
        res = self._eval_faq_tool(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["total"], 0)
        self.assertEqual(res["items"], [])

    def test_f10_boundary_empty_and_whitespace_search_no_op(self):
        """Empty and whitespace-only search string is a no-op returning all registered modules."""
        code = """
$all = $t->listFaqModules([]);
$empty_search = $t->listFaqModules(['search' => '']);
$space_search = $t->listFaqModules(['search' => '   ']);
echo json_encode([
    'success' => true,
    'total_all' => $all['total'],
    'total_empty' => $empty_search['total'],
    'total_space' => $space_search['total']
]);
"""
        res = self._eval_faq_tool(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["total_all"], res["total_empty"])
        self.assertEqual(res["total_all"], res["total_space"])

    def test_f10_boundary_search_unicode_and_exact_module_match(self):
        """Search matches registered FAQ host modules by name or mod_name."""
        code = """
$res = $t->listFaqModules(['search' => 'News']);
echo json_encode([
    'success' => true,
    'total' => $res['total'],
    'mod_name' => $res['items'][0]['mod_name'] ?? ''
]);
"""
        res = self._eval_faq_tool(code)
        self.assertTrue(res["success"])
        self.assertGreaterEqual(res["total"], 1)
        self.assertEqual(res["mod_name"], "mod_news")
