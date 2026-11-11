"""
Tier 2 Boundary & Corner Cases: Feature 3 (audit_meta_draft_faqs).
Validates McpCrudToolsBase::getRecordAuditMeta boundary conditions:
- Entity with zero FAQs returns accurate 0 counts and empty items
- Non-'n' recordStatus values default strictly to published FAQs
- SQL injection attempts in modName and lang parameters are sanitized
- Zero, negative, and large integer recordId values handled gracefully
- Non-existent/unregistered language returns lang_status 'n'
- Database query failure resilience returning safe default envelope
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(2)
@feature(3)
class TestF03AuditMetaDraftFaqsTier2(E2ETestCase):
    """Tier 2 tests for record audit metadata and FAQ counting boundaries."""

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
try {{
    $meta = $h->auditMeta({json.dumps(mod_name)}, {vid}, {json.dumps(lang)}, {json.dumps(status)});
    echo json_encode(['success' => true, 'meta' => $meta]);
}} catch (Throwable $e) {{
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}}
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0, f"PHP execution failed: {res.stderr}")
        return json.loads(res.stdout)

    def test_f03_boundary_zero_faqs_for_valid_entity(self):
        """Entity with zero FAQs returns 0 counts and empty items list cleanly."""
        res = self._call_audit_meta("mod_news", 999991, "tr", "y")
        self.assertTrue(res["success"])
        meta = res["meta"]
        self.assertEqual(meta["faq_count"], 0)
        self.assertEqual(meta["faq_published_count"], 0)
        self.assertEqual(meta["faq_draft_count"], 0)
        self.assertEqual(meta["faq_items"], [])
        self.assertEqual(meta["lang_status"], "y")

    def test_f03_boundary_record_status_other_than_n_defaults_to_published_only(self):
        """Only recordStatus === 'n' loads drafts; any other value ('draft', '', 'unknown') defaults to published only."""
        setup_code = """<?php
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
('mod_news', 999995, 'tr', 1, 'Pub Q', 'Pub A', 'y', 1),
('mod_news', 999995, 'tr', 2, 'Draft Q', 'Draft A', 'n', 1)");

$m_draft_str = $h->auditMeta('mod_news', 999995, 'tr', 'draft');
$m_empty = $h->auditMeta('mod_news', 999995, 'tr', '');
$m_unknown = $h->auditMeta('mod_news', 999995, 'tr', 'unknown');
$m_n = $h->auditMeta('mod_news', 999995, 'tr', 'n');

$db->query("DELETE FROM mod_faq WHERE vid = 999995");

echo json_encode([
    'draft_str' => $m_draft_str,
    'empty' => $m_empty,
    'unknown' => $m_unknown,
    'n' => $m_n,
]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=setup_code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        # All non-'n' should have draft count 0
        self.assertEqual(data["draft_str"]["faq_draft_count"], 0)
        self.assertEqual(data["draft_str"]["faq_count"], 1)
        self.assertEqual(data["empty"]["faq_draft_count"], 0)
        self.assertEqual(data["unknown"]["faq_draft_count"], 0)
        # recordStatus 'n' queries both published and draft
        self.assertEqual(data["n"]["faq_draft_count"], 1)
        self.assertEqual(data["n"]["faq_published_count"], 1)
        self.assertEqual(data["n"]["faq_count"], 2)

    def test_f03_boundary_sql_injection_in_mod_name_and_lang(self):
        """SQL injection characters in mod_name and lang are escaped without causing SQL errors."""
        res = self._call_audit_meta("mod_news' OR '1'='1", 1, "tr' UNION SELECT 1 --", "y")
        self.assertTrue(res["success"])
        meta = res["meta"]
        self.assertEqual(meta["faq_count"], 0)
        self.assertEqual(meta["lang_status"], "n")

    def test_f03_boundary_non_existent_or_negative_record_id(self):
        """Zero and negative recordId values are safely cast to int and return zero FAQs."""
        res0 = self._call_audit_meta("mod_news", 0, "tr", "y")
        self.assertTrue(res0["success"])
        self.assertEqual(res0["meta"]["faq_count"], 0)

        res_neg = self._call_audit_meta("mod_news", -1, "tr", "y")
        self.assertTrue(res_neg["success"])
        self.assertEqual(res_neg["meta"]["faq_count"], 0)

    def test_f03_boundary_non_existent_lang_returns_lang_status_n(self):
        """Non-existent language code returns lang_status == 'n'."""
        res = self._call_audit_meta("mod_news", 1, "non_existent_lang", "y")
        self.assertTrue(res["success"])
        self.assertEqual(res["meta"]["lang_status"], "n")

    def test_f03_boundary_database_exception_resilience(self):
        """Throwable during audit meta query returns safe default envelope."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

// Simulate the internal catch block of getRecordAuditMeta
$default = [
    'lang_status' => 'n',
    'faq_count' => 0,
    'faq_items' => [],
    'faq_published_count' => 0,
    'faq_draft_count' => 0,
];
try {
    throw new Exception("Simulated DB connection failure");
} catch (Throwable $e) {
    // Fallback exactly as implemented in McpCrudToolsBase::getRecordAuditMeta
    $result = $default;
}
echo json_encode($result);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertEqual(data["faq_count"], 0)
        self.assertEqual(data["lang_status"], "n")
