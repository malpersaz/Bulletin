"""
Tier 1 Feature Coverage: Feature 3 (audit_meta_draft_faqs).
Validates McpCrudToolsBase::getRecordAuditMeta DB fetch, active lang resolution,
and draft FAQ counting when recordStatus is 'n' vs 'y'.
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(3)
class TestF03AuditMetaDraftFaqs(E2ETestCase):
    """Tier 1 tests for getRecordAuditMeta and draft FAQ counting."""

    def _call_audit_meta(self, mod_name: str, vid: int, lang: str, status: str = "y") -> dict:
        code = f"""<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

class TestCrudHarness extends McpCrudToolsBase {{
    public static function definitions(): array {{ return []; }}
    public function auditMeta(string $modName, int $recordId, string $lang, string $recordStatus = 'y'): array {{
        return $this->getRecordAuditMeta($modName, $recordId, $lang, $recordStatus);
    }}
}}

$h = new TestCrudHarness(1, []);
$meta = $h->auditMeta('{mod_name}', {vid}, '{lang}', '{status}');
echo json_encode($meta);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0, f"PHP execution failed: {res.stderr}")
        return json.loads(res.stdout)

    def test_f03_audit_meta_resolves_active_lang_status_y(self):
        """Verify active language (e.g. tr) returns lang_status == 'y'."""
        meta = self._call_audit_meta("mod_news", 1, "tr", "y")
        self.assertEqual(meta.get("lang_status"), "y")

    def test_f03_audit_meta_counts_published_faqs_only_when_record_status_y(self):
        """Verify record_status='y' returns only published FAQs and draft count 0."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

class TestCrudHarness extends McpCrudToolsBase {
    public static function definitions(): array { return []; }
    public function auditMeta(string $modName, int $recordId, string $lang, string $recordStatus = 'y'): array {
        return $this->getRecordAuditMeta($modName, $recordId, $lang, $recordStatus);
    }
}

$h = new TestCrudHarness(1, []);
$db = Load::lib('Database');

$db->query("INSERT INTO mod_faq (mod_name, vid, lang, sort_order, question, answer, status, user_id) VALUES 
('mod_news', 999992, 'tr', 1, 'Pub Q', 'Pub A', 'y', 1),
('mod_news', 999992, 'tr', 2, 'Draft Q', 'Draft A', 'n', 1)");

$meta = $h->auditMeta('mod_news', 999992, 'tr', 'y');
$db->query("DELETE FROM mod_faq WHERE vid = 999992");

echo json_encode($meta);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertEqual(data.get("faq_count"), 1)
        self.assertEqual(data.get("faq_published_count"), 1)
        self.assertEqual(data.get("faq_draft_count"), 0)
        self.assertEqual(len(data.get("faq_items", [])), 1)
        self.assertEqual(data["faq_items"][0]["status"], "y")

    def test_f03_audit_meta_counts_draft_and_published_faqs_when_record_status_n(self):
        """Verify record_status='n' returns both draft and published FAQs."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

class TestCrudHarness extends McpCrudToolsBase {
    public static function definitions(): array { return []; }
    public function auditMeta(string $modName, int $recordId, string $lang, string $recordStatus = 'y'): array {
        return $this->getRecordAuditMeta($modName, $recordId, $lang, $recordStatus);
    }
}

$h = new TestCrudHarness(1, []);
$db = Load::lib('Database');

$db->query("INSERT INTO mod_faq (mod_name, vid, lang, sort_order, question, answer, status, user_id) VALUES 
('mod_news', 999993, 'tr', 1, 'Pub Q', 'Pub A', 'y', 1),
('mod_news', 999993, 'tr', 2, 'Draft Q', 'Draft A', 'n', 1)");

$meta = $h->auditMeta('mod_news', 999993, 'tr', 'n');
$db->query("DELETE FROM mod_faq WHERE vid = 999993");

echo json_encode($meta);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertEqual(data.get("faq_count"), 2)
        self.assertEqual(data.get("faq_published_count"), 1)
        self.assertEqual(data.get("faq_draft_count"), 1)
        self.assertEqual(len(data.get("faq_items", [])), 2)

    def test_f03_audit_meta_zero_faqs_and_inactive_lang(self):
        """Verify non-existent record and inactive language return lang_status='n' and zero FAQs."""
        meta = self._call_audit_meta("mod_news", 999999, "xx", "y")
        self.assertEqual(meta.get("lang_status"), "n")
        self.assertEqual(meta.get("faq_count"), 0)
        self.assertEqual(meta.get("faq_published_count"), 0)
        self.assertEqual(meta.get("faq_draft_count"), 0)
        self.assertEqual(meta.get("faq_items"), [])

    def test_f03_audit_meta_sort_order_preservation(self):
        """Verify FAQ items are returned strictly sorted by sort_order ASC, id ASC."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

class TestCrudHarness extends McpCrudToolsBase {
    public static function definitions(): array { return []; }
    public function auditMeta(string $modName, int $recordId, string $lang, string $recordStatus = 'y'): array {
        return $this->getRecordAuditMeta($modName, $recordId, $lang, $recordStatus);
    }
}

$h = new TestCrudHarness(1, []);
$db = Load::lib('Database');

$db->query("INSERT INTO mod_faq (mod_name, vid, lang, sort_order, question, answer, status, user_id) VALUES 
('mod_news', 999994, 'tr', 30, 'Third Q', 'A', 'y', 1),
('mod_news', 999994, 'tr', 10, 'First Q', 'A', 'y', 1),
('mod_news', 999994, 'tr', 20, 'Second Q', 'A', 'y', 1)");

$meta = $h->auditMeta('mod_news', 999994, 'tr', 'y');
$db->query("DELETE FROM mod_faq WHERE vid = 999994");

echo json_encode($meta);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        items = data.get("faq_items", [])
        self.assertEqual(len(items), 3)
        self.assertEqual(items[0]["question"], "First Q")
        self.assertEqual(items[1]["question"], "Second Q")
        self.assertEqual(items[2]["question"], "Third Q")
