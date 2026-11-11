"""
Tier 1 Feature Coverage: Feature 10 (list_faq_modules).
Validates McpFaqTools::listFaqModules returns registered FAQ host modules,
enforces schema structure, search filtering, and pagination.
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(10)
class TestF10ListFaqModules(E2ETestCase):
    """Tier 1 tests for listing FAQ host modules."""

    def test_f10_list_faq_modules_returns_registered_modules(self):
        """Verify listFaqModules returns registered modules (mod_news, mod_page, mod_publication, mod_event)."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpFaqTools(1, ['admin']);
$res = $t->listFaqModules([]);
echo json_encode($res);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertGreaterEqual(data.get("total", 0), 4)
        mod_names = [item["mod_name"] for item in data.get("items", [])]
        self.assertIn("mod_news", mod_names)
        self.assertIn("mod_page", mod_names)
        self.assertIn("mod_publication", mod_names)
        self.assertIn("mod_event", mod_names)

    def test_f10_list_faq_modules_payload_schema(self):
        """Verify each module item conforms to required schema keys."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpFaqTools(1, ['admin']);
$res = $t->listFaqModules([]);
echo json_encode($res['items'] ?? []);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        items = json.loads(res.stdout)
        self.assertGreater(len(items), 0)
        for item in items:
            self.assertIn("id", item)
            self.assertIn("name", item)
            self.assertIn("mod_name", item)
            self.assertIn("mod_key", item)
            self.assertIn("mod_value", item)

    def test_f10_list_faq_modules_search_filter_matches_name_or_mod_name(self):
        """Verify search query matches target module and filters out unrelated modules."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpFaqTools(1, ['admin']);
$res = $t->listFaqModules(['search' => 'news']);
echo json_encode($res);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        items = data.get("items", [])
        self.assertGreaterEqual(len(items), 1)
        for item in items:
            self.assertIn("news", (item["name"] + " " + item["mod_name"]).lower())

    def test_f10_list_faq_modules_search_filter_no_results(self):
        """Verify search query with non-matching string returns empty items list and total 0."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpFaqTools(1, ['admin']);
$res = $t->listFaqModules(['search' => 'non_existent_module_xyz_123']);
echo json_encode($res);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertEqual(data.get("items"), [])
        self.assertEqual(data.get("total"), 0)

    def test_f10_list_faq_modules_pagination_limit_and_offset(self):
        """Verify pagination parameters limit and offset return appropriate slices."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpFaqTools(1, ['admin']);
$page1 = $t->listFaqModules(['limit' => 1, 'offset' => 0]);
$page2 = $t->listFaqModules(['limit' => 1, 'offset' => 1]);

echo json_encode(['page1' => $page1, 'page2' => $page2]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        p1_items = data["page1"].get("items", [])
        p2_items = data["page2"].get("items", [])
        self.assertEqual(len(p1_items), 1)
        self.assertEqual(len(p2_items), 1)
        self.assertNotEqual(p1_items[0]["id"], p2_items[0]["id"])
        self.assertEqual(data["page1"]["total"], data["page2"]["total"])
