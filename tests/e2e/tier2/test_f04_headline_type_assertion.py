"""
Tier 2 Boundary & Corner Cases: Feature 4 (headline_type_assertion).
Validates McpHeadlineTools boundaries:
- Empty or whitespace-only name throws validation error
- Name length lower boundary: 1 char rejected, 2 chars accepted
- Name length upper boundary: 255 chars accepted, 256 chars rejected
- Numeric name parameter coercion to string without TypeError
- Missing or inactive language rejection
- Cross-language mismatch detection on updateHeadline
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(2)
@feature(4)
class TestF04HeadlineTypeAssertionTier2(E2ETestCase):
    """Tier 2 tests for headline type assertions and validation boundaries."""

    def _eval_headline_tool(self, php_code: str) -> dict:
        full_code = f"""<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpHeadlineTools(1, ['editor']);
try {{
    {php_code}
}} catch (Throwable $e) {{
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}}
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=full_code)
        self.assertExitCode(res, 0, f"PHP execution failed: {res.stderr}")
        return json.loads(res.stdout)

    def test_f04_boundary_name_empty_or_whitespace_only(self):
        """Empty or whitespace-only name is rejected with length requirement message."""
        code = """
$t->createHeadline(['name' => '   ', 'lang' => 'tr']);
"""
        res = self._eval_headline_tool(code)
        self.assertFalse(res["success"])
        self.assertIn("Field 'name' is required (2-255 characters)", res["error"])

    def test_f04_boundary_name_length_lower_boundary_1_and_2_chars(self):
        """Single character name is rejected; 2-character name is accepted."""
        res1 = self._eval_headline_tool("$t->createHeadline(['name' => 'A', 'lang' => 'tr']);")
        self.assertFalse(res1["success"])
        self.assertIn("Field 'name' is required (2-255 characters)", res1["error"])

        code2 = """
$r = $t->createHeadline(['name' => 'AB', 'lang' => 'tr']);
if (!empty($r['id'])) { $t->deleteHeadline(['id' => $r['id']]); }
echo json_encode(['success' => true, 'id' => $r['id']]);
"""
        res2 = self._eval_headline_tool(code2)
        self.assertTrue(res2["success"])

    def test_f04_boundary_name_length_upper_boundary_255_and_256_chars(self):
        """255-character name is accepted; 256-character name is rejected."""
        valid_255 = "A" * 255
        invalid_256 = "A" * 256

        res_256 = self._eval_headline_tool(
            "$t->createHeadline(['name' => '" + invalid_256 + "', 'lang' => 'tr']);"
        )
        self.assertFalse(res_256["success"])
        self.assertIn("Field 'name' is required (2-255 characters)", res_256["error"])

        code_255 = (
            "$r = $t->createHeadline(['name' => '" + valid_255 + "', 'lang' => 'tr']);\n"
            "if (!empty($r['id'])) { $t->deleteHeadline(['id' => $r['id']]); }\n"
            "echo json_encode(['success' => true, 'id' => $r['id']]);\n"
        )
        res_255 = self._eval_headline_tool(code_255)
        self.assertTrue(res_255["success"])

    def test_f04_boundary_numeric_and_null_name_type_coercion(self):
        """Numeric name value is coerced to string without throwing PHP 8 TypeError."""
        code = """
$r = $t->createHeadline(['name' => 12345, 'lang' => 'tr']);
if (!empty($r['id'])) { $t->deleteHeadline(['id' => $r['id']]); }
echo json_encode(['success' => true, 'id' => $r['id']]);
"""
        res = self._eval_headline_tool(code)
        self.assertTrue(res["success"])

    def test_f04_boundary_missing_or_inactive_language(self):
        """Missing or inactive language is rejected with informative error."""
        res_missing = self._eval_headline_tool("""
$t->createHeadline(['name' => 'Geçerli Başlık Metni']);
""")
        self.assertFalse(res_missing["success"])
        self.assertIn("Field 'lang' is required", res_missing["error"])

        res_inactive = self._eval_headline_tool("""
$t->createHeadline(['name' => 'Geçerli Başlık Metni', 'lang' => 'de']);
""")
        self.assertFalse(res_inactive["success"])
        self.assertIn("not an active site language", res_inactive["error"])

    def test_f04_boundary_update_headline_language_mismatch_cross_detection(self):
        """Updating a Kurdish headline with distinctly Turkish text triggers language mismatch error."""
        code = """
$c = $t->createHeadline(['name' => 'Serkeftina Tîma Diyarbekir', 'lang' => 'ku']);
$id = $c['id'];
try {
    // Attempt updating with distinct Turkish stopwords and chars under ku language
    $u = $t->updateHeadline([
        'id' => $id,
        'name' => 'Bu başlık kapsamında yapılan yeni çalışmalar olarak açıklandı'
    ]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    $t->deleteHeadline(['id' => $id]);
}
"""
        res = self._eval_headline_tool(code)
        self.assertFalse(res["success"])
        self.assertIn("language mismatch", res["error"].lower())
