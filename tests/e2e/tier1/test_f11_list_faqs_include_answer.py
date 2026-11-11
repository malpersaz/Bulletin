"""
Tier 1 Feature Coverage: Feature 11 (list_faqs_include_answer).
Validates McpFaqTools::listFaqs include_answer parameter (omitting answers by default
for bandwidth optimization, and including full answers when requested).
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(11)
class TestF11ListFaqsIncludeAnswer(E2ETestCase):
    """Tier 1 tests for FAQ listing include_answer toggle."""

    def test_f11_list_faqs_omitted_include_answer_omits_answer_field(self):
        """Verify omitting include_answer excludes answer column from items by default."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpFaqTools(1, ['admin']);
$res = $t->listFaqs(['limit' => 5]);
echo json_encode($res['items'] ?? []);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        items = json.loads(res.stdout)
        self.assertGreater(len(items), 0)
        for item in items:
            self.assertNotIn("answer", item)

    def test_f11_list_faqs_include_answer_false_omits_answer_field(self):
        """Verify explicitly passing include_answer=false omits answer column."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpFaqTools(1, ['admin']);
$res = $t->listFaqs(['limit' => 5, 'include_answer' => false]);
echo json_encode($res['items'] ?? []);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        items = json.loads(res.stdout)
        self.assertGreater(len(items), 0)
        for item in items:
            self.assertNotIn("answer", item)

    def test_f11_list_faqs_include_answer_true_includes_full_answer(self):
        """Verify passing include_answer=true includes the full answer text in each item."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpFaqTools(1, ['admin']);
$res = $t->listFaqs(['limit' => 5, 'include_answer' => true]);
echo json_encode($res['items'] ?? []);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        items = json.loads(res.stdout)
        self.assertGreater(len(items), 0)
        for item in items:
            self.assertIn("answer", item)
            self.assertIsInstance(item["answer"], str)
            self.assertGreater(len(item["answer"]), 0)

    def test_f11_list_faqs_payload_size_comparison(self):
        """Verify JSON payload size with include_answer=false is strictly smaller than include_answer=true."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpFaqTools(1, ['admin']);
$noAns = $t->listFaqs(['limit' => 5, 'include_answer' => false]);
$yesAns = $t->listFaqs(['limit' => 5, 'include_answer' => true]);

echo json_encode([
    'len_no' => strlen(json_encode($noAns)),
    'len_yes' => strlen(json_encode($yesAns))
]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertLess(data["len_no"], data["len_yes"])

    def test_f11_list_faqs_include_answer_with_filters_status_and_lang(self):
        """Verify include_answer=true works seamlessly in conjunction with lang and status filters."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpFaqTools(1, ['admin']);
$res = $t->listFaqs(['lang' => 'tr', 'status' => 'y', 'include_answer' => true, 'limit' => 5]);
echo json_encode($res['items'] ?? []);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        items = json.loads(res.stdout)
        self.assertGreater(len(items), 0)
        for item in items:
            self.assertEqual(item["lang"], "tr")
            self.assertEqual(item["status"], "y")
            self.assertIn("answer", item)
