"""
Tier 1 Feature Coverage: Feature 1 (orm_conjunctive_filters).
Validates McpCrudToolsBase::addEqFilter and addLikeSearchFilter conjunctive filtering.
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(1)
class TestF01OrmConjunctiveFilters(E2ETestCase):
    """Tier 1 tests for ORM conjunctive filtering."""

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
{php_code}
echo json_encode(['where' => $model->_where]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=full_code)
        self.assertExitCode(res, 0, f"PHP execution failed: {res.stderr}")
        return json.loads(res.stdout)

    def test_f01_add_eq_filter_single_column_numeric_and_string(self):
        """Verify addEqFilter handles numeric (unquoted) and string (quoted) correctly."""
        code = """
$h->testEq($model, 'id', 42);
$h->testEq($model, 'lang', 'tr');
"""
        res = self._eval_filter_script(code)
        self.assertIn("id = 42", res["where"])
        self.assertIn("lang = 'tr'", res["where"])
        self.assertIn("AND", res["where"])

    def test_f01_add_eq_filter_multiple_conjunctive_chaining(self):
        """Verify sequential calls to addEqFilter chain with AND without clobbering."""
        code = """
$h->testEq($model, 'lang', 'tr');
$h->testEq($model, 'status', 'y');
$h->testEq($model, 'category_id', 10);
"""
        res = self._eval_filter_script(code)
        expected = "lang = 'tr' AND status = 'y' AND category_id = 10"
        self.assertEqual(res["where"], expected)

    def test_f01_add_like_search_filter_multi_column_or(self):
        """Verify addLikeSearchFilter generates parenthesized OR expression across columns."""
        code = """
$h->testLike($model, ['name', 'summary'], 'Diyarbakır');
"""
        res = self._eval_filter_script(code)
        self.assertIn("(name LIKE '%Diyarbakır%' OR summary LIKE '%Diyarbakır%')", res["where"])

    def test_f01_add_like_search_filter_empty_or_whitespace_noop(self):
        """Verify empty or whitespace search queries are clean no-ops."""
        code = """
$h->testLike($model, ['name'], '');
$h->testLike($model, ['name'], '   ');
"""
        res = self._eval_filter_script(code)
        self.assertEqual(res["where"], "")

    def test_f01_add_eq_and_like_combination_conjunctive(self):
        """Verify combinations of eq and like filters chain together with AND."""
        code = """
$h->testEq($model, 'lang', 'ku');
$h->testEq($model, 'status', 'n');
$h->testLike($model, ['name', 'seo'], 'Amed');
"""
        res = self._eval_filter_script(code)
        expected = "lang = 'ku' AND status = 'n' AND (name LIKE '%Amed%' OR seo LIKE '%Amed%')"
        self.assertEqual(res["where"], expected)

    def test_f01_invalid_column_name_injection_rejection(self):
        """Verify invalid column names trigger McpToolException to prevent SQL injection."""
        full_code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

class DummyModel {
    public $_where = '';
    public function addWhere($clause, $op = 'AND') { return $this; }
}
class TestCrudHarness extends McpCrudToolsBase {
    public static function definitions(): array { return []; }
    public function testEq($model, $col, $val) { $this->addEqFilter($model, $col, $val); }
    public function testLike($model, $cols, $q) { $this->addLikeSearchFilter($model, $cols, $q); }
}

$h = new TestCrudHarness(1, []);
$model = new DummyModel();
$caught = 0;
try {
    $h->testEq($model, 'status; DROP TABLE mod_news;--', 'y');
} catch (McpToolException $e) {
    if (strpos($e->getMessage(), 'Invalid filter column name') !== false) {
        $caught++;
    }
}
try {
    $h->testLike($model, ['col` = 1 OR `a'], 'x');
} catch (McpToolException $e) {
    if (strpos($e->getMessage(), 'Invalid search column name') !== false) {
        $caught++;
    }
}
echo json_encode(['caught' => $caught]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=full_code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertEqual(data["caught"], 2)
