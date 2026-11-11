"""
Tier 1 Feature Coverage: Feature 9 (batch_create_faqs).
Validates McpFaqTools::batchCreateFaqs atomic creation, rollback on failure,
25-item batch ceiling, duplicate question rejection, and replace_existing_drafts isolation.
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(9)
class TestF09BatchCreateFaqs(E2ETestCase):
    """Tier 1 tests for atomic batch FAQ creation."""

    def test_f09_batch_create_faqs_happy_path_three_items(self):
        """Verify atomic creation of 3 valid FAQs returns created=True and list of IDs."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpFaqTools(1, ['admin']);
$res = $t->batchCreateFaqs([
    'mod_name' => 'mod_news',
    'vid' => 1,
    'lang' => 'tr',
    'status' => 'n',
    'items' => [
        ['question' => 'Batch Soru 1 ' . uniqid(), 'answer' => 'Cevap 1'],
        ['question' => 'Batch Soru 2 ' . uniqid(), 'answer' => 'Cevap 2'],
        ['question' => 'Batch Soru 3 ' . uniqid(), 'answer' => 'Cevap 3']
    ]
]);

if (!empty($res['ids'])) {
    $db = Load::lib('Database');
    $db->query("DELETE FROM mod_faq WHERE id IN (" . implode(',', $res['ids']) . ")");
}
echo json_encode($res);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("created"))
        self.assertEqual(data.get("count"), 3)
        self.assertEqual(data.get("created_count"), 3)
        self.assertEqual(len(data.get("ids", [])), 3)
        for item in data.get("items", []):
            self.assertEqual(item.get("status"), "n")

    def test_f09_batch_create_faqs_atomic_rollback_on_invalid_item(self):
        """Verify transaction rollback when one item in batch fails validation; zero rows persist."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpFaqTools(1, ['admin']);
$uniq = uniqid();
$q1 = 'Rollback Test Q1 ' . $uniq;
$caught = false;
$msg = '';

try {
    $t->batchCreateFaqs([
        'mod_name' => 'mod_news',
        'vid' => 1,
        'lang' => 'tr',
        'status' => 'n',
        'items' => [
            ['question' => $q1, 'answer' => 'Valid answer'],
            ['question' => '', 'answer' => 'Invalid empty question'] // Fails validation
        ]
    ]);
} catch (McpToolException $e) {
    $caught = true;
    $msg = $e->getMessage();
}

$db = Load::lib('Database');
$db->fetch_array("SELECT COUNT(*) as cnt FROM mod_faq WHERE question = '{$q1}'");
$row = $db->toArray()[0];
$persistedCount = (int)$row['cnt'];

echo json_encode([
    'caught' => $caught,
    'msg' => $msg,
    'persisted_count' => $persistedCount
]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertTrue(data.get("caught"), "Should raise McpToolException on invalid item")
        self.assertEqual(data.get("persisted_count"), 0, "No items from failed batch must persist")

    def test_f09_batch_create_faqs_rejects_batch_exceeding_25_items(self):
        """Verify batch containing >25 items is rejected before database insertion."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpFaqTools(1, ['admin']);
$items = [];
for ($i = 1; $i <= 26; $i++) {
    $items[] = ['question' => "Question {$i}", 'answer' => "Answer {$i}"];
}

$caught = false;
$msg = '';
try {
    $t->batchCreateFaqs([
        'mod_name' => 'mod_news',
        'vid' => 1,
        'lang' => 'tr',
        'items' => $items
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
        self.assertTrue(data.get("caught"))
        self.assertIn("maximum batch size of 25", data.get("msg", ""))

    def test_f09_batch_create_faqs_duplicate_question_in_batch_rejected(self):
        """Verify duplicate question within same batch is rejected to prevent duplicate FAQs."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$t = new McpFaqTools(1, ['admin']);
$caught = false;
$msg = '';
try {
    $t->batchCreateFaqs([
        'mod_name' => 'mod_news',
        'vid' => 1,
        'lang' => 'tr',
        'items' => [
            ['question' => 'Aynı Soru Metni', 'answer' => 'Cevap A'],
            ['question' => 'aynı soru metni', 'answer' => 'Cevap B'] // Case-insensitive duplicate
        ]
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
        self.assertTrue(data.get("caught"))
        self.assertIn("Duplicate question within batch", data.get("msg", ""))

    def test_f09_batch_create_faqs_replace_existing_drafts_isolation(self):
        """Verify replace_existing_drafts purges target entity drafts while preserving published FAQs and other drafts."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$db = Load::lib('Database');
// Use valid news IDs 1 and 2
$db->query("INSERT INTO mod_faq (mod_name, vid, lang, sort_order, question, answer, status, user_id) VALUES 
('mod_news', 1, 'tr', 1, 'Target Published FAQ', 'Ans', 'y', 1),
('mod_news', 1, 'tr', 2, 'Target Old Draft FAQ', 'Ans', 'n', 1)");

$db->query("INSERT INTO mod_faq (mod_name, vid, lang, sort_order, question, answer, status, user_id) VALUES 
('mod_news', 2, 'tr', 1, 'Unrelated Draft FAQ', 'Ans', 'n', 1)");

$t = new McpFaqTools(1, ['admin']);
$res = $t->batchCreateFaqs([
    'mod_name' => 'mod_news',
    'vid' => 1,
    'lang' => 'tr',
    'status' => 'n',
    'replace_existing_drafts' => true,
    'items' => [
        ['question' => 'Target New Draft FAQ', 'answer' => 'New Ans']
    ]
]);

// Check what exists for vid=1
$db->fetch_array("SELECT question, status FROM mod_faq WHERE mod_name = 'mod_news' AND vid = 1");
$targetFaqs = $db->toArray();

// Check what exists for vid=2
$db->fetch_array("SELECT question, status FROM mod_faq WHERE mod_name = 'mod_news' AND vid = 2");
$unrelatedFaqs = $db->toArray();

// Cleanup inserted test FAQs
$db->query("DELETE FROM mod_faq WHERE question IN ('Target Published FAQ', 'Target Old Draft FAQ', 'Target New Draft FAQ', 'Unrelated Draft FAQ')");

echo json_encode([
    'target_faqs' => $targetFaqs,
    'unrelated_faqs' => $unrelatedFaqs
]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        target_questions = [f["question"] for f in data["target_faqs"]]
        # Published FAQ preserved
        self.assertIn("Target Published FAQ", target_questions)
        # Old draft removed
        self.assertNotIn("Target Old Draft FAQ", target_questions)
        # New draft created
        self.assertIn("Target New Draft FAQ", target_questions)
        # Unrelated entity draft untouched
        unrelated_questions = [f["question"] for f in data["unrelated_faqs"]]
        self.assertIn("Unrelated Draft FAQ", unrelated_questions)
