"""
Tier 2 Boundary & Corner Cases: Feature 1 (orm_conjunctive_filters).
Validates edge cases, injection escaping, zero/negative numerics, empty strings,
invalid columns, unicode quotes, and complex multi-condition chains for
McpCrudToolsBase::addEqFilter and addLikeSearchFilter.
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(2)
@feature(1)
class TestF01OrmConjunctiveFiltersTier2(E2ETestCase):
    """Tier 2 tests for ORM conjunctive filtering boundaries."""

    def _eval_filter_script(self, php_code: str) -> dict:
        full_code = f"""<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

class DummyModel {{
    public $_where = '';
    public function addWhere($clause, $op = 'AND') {{
        if (!empty($this->_where)) {{
            $this->_where .= ' ' . $op . ' ' . $clause;
        }} else {{
            $this->_where = $clause;
        }}
        return $this;
    }}
}}

class TestCrudHarness extends McpCrudToolsBase {{
    public static function definitions(): array {{ return []; }}
    public function testEq($model, $col, $val) {{
        $this->addEqFilter($model, $col, $val);
    }}
    public function testLike($model, $cols, $q) {{
        $this->addLikeSearchFilter($model, $cols, $q);
    }}
}}

$h = new TestCrudHarness(1, []);
$model = new DummyModel();
try {{
    {php_code}
    echo json_encode(['success' => true, 'where' => $model->_where]);
}} catch (Throwable $e) {{
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}}
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=full_code)
        self.assertExitCode(res, 0, f"PHP execution failed: {res.stderr}")
        return json.loads(res.stdout)

    def test_f01_boundary_empty_string_value_in_add_eq_filter(self):
        """Empty string value in addEqFilter is preserved inside quotes, not dropped."""
        code = """
$h->testEq($model, 'status', '');
$h->testEq($model, 'lang', 'tr');
"""
        res = self._eval_filter_script(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["where"], "status = '' AND lang = 'tr'")

    def test_f01_boundary_sql_injection_in_value_properly_escaped(self):
        """SQL injection attempt in value is escaped by Obuilder::cc doubling single quotes."""
        code = """
$h->testEq($model, 'title', "test' OR 1=1 --");
"""
        res = self._eval_filter_script(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["where"], "title = 'test'' OR 1=1 --'")

    def test_f01_boundary_zero_and_negative_numeric_values(self):
        """Zero and negative numeric values are treated as unquoted numbers."""
        code = """
$h->testEq($model, 'cat_id', 0);
$h->testEq($model, 'parent_id', -1);
$h->testEq($model, 'score', 0);
"""
        res = self._eval_filter_script(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["where"], "cat_id = 0 AND parent_id = -1 AND score = 0")

    def test_f01_boundary_invalid_column_names_validation(self):
        """Invalid column names containing SQL characters or spaces throw McpToolException."""
        invalid_cols = ["status; DROP TABLE", "col name", "table.col", "1column", ""]
        for col in invalid_cols:
            code = f"""
$h->testEq($model, {json.dumps(col)}, 'val');
"""
            res = self._eval_filter_script(code)
            self.assertFalse(res["success"], f"Column '{col}' should have been rejected")
            self.assertIn("Invalid filter column name", res["error"])

    def test_f01_boundary_like_filter_empty_and_whitespace_no_op(self):
        """Empty or whitespace search query or empty columns list is strict no-op."""
        code = """
$h->testLike($model, ['name'], '');
$h->testLike($model, ['name'], '   ');
$h->testLike($model, [], 'test');
"""
        res = self._eval_filter_script(code)
        self.assertTrue(res["success"])
        self.assertEqual(res["where"], "")

    def test_f01_boundary_like_filter_extreme_unicode_and_quotes(self):
        """Like filter handles Turkish/Kurdish Unicode, single quotes, and percent signs safely."""
        code = """
$h->testLike($model, ['name', 'summary'], "Diyarbakır'da Kürtçe & Türkçe %100");
"""
        res = self._eval_filter_script(code)
        self.assertTrue(res["success"])
        expected = "(name LIKE '%Diyarbakır''da Kürtçe & Türkçe %100%' OR summary LIKE '%Diyarbakır''da Kürtçe & Türkçe %100%')"
        self.assertEqual(res["where"], expected)

    def test_f01_boundary_complex_multi_condition_conjunction_chain(self):
        """Complex interleaving of addEqFilter and addLikeSearchFilter chains properly with AND."""
        code = """
$h->testEq($model, 'status', 'y');
$h->testEq($model, 'cat_id', 0);
$h->testEq($model, 'lang', 'ku');
$h->testEq($model, 'deleted', 0);
$h->testLike($model, ['name', 'summary'], 'Amed');
"""
        res = self._eval_filter_script(code)
        self.assertTrue(res["success"])
        expected = "status = 'y' AND cat_id = 0 AND lang = 'ku' AND deleted = 0 AND (name LIKE '%Amed%' OR summary LIKE '%Amed%')"
        self.assertEqual(res["where"], expected)
