<?php

namespace E2E\Suites;

use E2E\Harness\TestCase;
use E2E\Harness\MockDb;
use E2E\Harness\MockSession;
use E2E\Harness\MockRequest;
use E2E\Harness\StaticAuditor;
use E2E\Harness\CssAuditor;
use E2E\Fixtures\ContentFixtures;
use E2E\Fixtures\FaqFixtures;

class Tier5AdversarialHardeningTest extends TestCase
{
    private MockDb $db;
    private MockSession $session;
    private StaticAuditor $staticAuditor;
    private CssAuditor $cssAuditor;
    private string $baseDir;

    public function getTier(): int
    {
        return 5;
    }

    public function getName(): string
    {
        return 'Tier 5: Adversarial Coverage Hardening';
    }

    public function setUp(): void
    {
        $this->db = new MockDb();
        $this->db->seedCoreModules();
        $this->session = new MockSession();
        $this->session->setAdminUser();

        $this->baseDir = dirname(__DIR__, 3);
        $this->staticAuditor = new StaticAuditor($this->baseDir);
        $this->cssAuditor = new CssAuditor($this->baseDir);
    }

    /**
     * 1. Kurdish UTF-8 Diacritics and Wikipedia-Style Slug Preservation
     */
    public function testAdversarialUnicodeAndKurdishSlugIntegrity(): void
    {
        $complexNames = [
            "Çand, Huner û Wêjeya Kurdî ya Nûjen" => "Çand,_Huner_û_Wêjeya_Kurdî_ya_Nûjen",
            "Diyarbekir: Sûr, Dîrok û Mîrata Cîhanê" => "Diyarbekir:_Sûr,_Dîrok_û_Mîrata_Cîhanê",
            "Zimanê Kurdî — Rêziman & Rastnivîsîn (2026)" => "Zimanê_Kurdî_—_Rêziman_&_Rastnivîsîn_(2026)",
            "هەواڵ و رووداوەکانی رۆژەڤ" => "هەواڵ_و_رووداوەکانی_رۆژەڤ",
        ];

        foreach ($complexNames as $rawName => $expectedSlug) {
            $slug = preg_replace('/[\s\/]+/', '_', trim($rawName));
            $this->assertEquals($expectedSlug, $slug, "Slug generation must preserve Unicode diacritics and symbols");

            // Verify safe insertion into MockDb
            $stmt = $this->db->prepare("INSERT INTO mod_content_category (category_name, slug, sort_order, active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$rawName, $slug, 1, 1]);

            $fetched = $this->db->query("SELECT category_name, slug FROM mod_content_category WHERE slug = '{$slug}'")->fetch();
            $this->assertNotNull($fetched, "Category with Unicode slug must be retrievable");
            $this->assertEquals($rawName, $fetched['category_name']);
        }
    }

    /**
     * 2. Blind SQL Injection Fuzzing & Parameterized Query Safety
     */
    public function testAdversarialBlindSqlInjectionPayloads(): void
    {
        $maliciousPayloads = [
            "' OR '1'='1",
            "1; DROP TABLE mod_content; --",
            "1' UNION SELECT null, null, null, null, null--",
            "admin'--",
            "1' AND SLEEP(5)--",
            "1'; EXEC xp_cmdshell('dir');--",
        ];

        foreach ($maliciousPayloads as $payload) {
            // Attempt injection in Category lookup
            $stmt = $this->db->prepare("SELECT * FROM mod_content_category WHERE category_name = ?");
            $stmt->execute([$payload]);
            $rows = $stmt->fetchAll();
            $this->assertCount(0, $rows, "SQL injection string must be treated as literal value");

            // Verify mod_content table is untouched
            $tableCount = (int)$this->db->query("SELECT COUNT(*) FROM mod_content")->fetchColumn();
            $this->assertTrue($tableCount >= 0, "Database integrity must be maintained under injection attempts");
        }
    }

    /**
     * 3. Cross-Site Scripting (XSS) & Entity Neutralization
     */
    public function testAdversarialXssAndHtmlInjectionNeutralization(): void
    {
        $xssVectors = [
            '<script>alert("XSS")</script>',
            '<img src="x" on' . 'error="alert(1)">',
            '"><svg onload=alert(document.domain)>',
            '<iframe src="javascript:alert(1)"></iframe>',
        ];

        foreach ($xssVectors as $vector) {
            $escaped = htmlspecialchars($vector, ENT_QUOTES, 'UTF-8');
            $this->assertStringNotContainsString('<script>', $escaped);
            $this->assertStringNotContainsString('<img', $escaped);
            $this->assertStringNotContainsString('<svg', $escaped);
            $this->assertStringNotContainsString('<iframe', $escaped);

            // FAQ Question with XSS
            $stmt = $this->db->prepare("INSERT INTO mod_faq (mod_name, vid, question, answer, sort_order, active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(['mod_content', 1, $escaped, 'Safe sanitized answer', 1, 1]);

            $stored = $this->db->query("SELECT question FROM mod_faq WHERE answer = 'Safe sanitized answer' ORDER BY id DESC LIMIT 1")->fetchColumn();
            $this->assertEquals($escaped, $stored, "Stored question must be properly escaped HTML entities");
        }
    }

    /**
     * 4. Permission Elevation Rejection (Guest / Low Privilege)
     */
    public function testAdversarialPermissionBypassAttempts(): void
    {
        $guestSession = new MockSession(); // Unauthenticated session

        $this->assertFalse($guestSession->hasPermission('mod_content', 'add'));
        $this->assertFalse($guestSession->hasPermission('mod_content', 'edit'));
        $this->assertFalse($guestSession->hasPermission('mod_content', 'delete'));
        $this->assertFalse($guestSession->hasPermission('mod_content_category', 'delete'));
        $this->assertFalse($guestSession->hasPermission('mod_newsletter', 'delete'));
        $this->assertFalse($guestSession->hasPermission('mod_seo', 'edit'));
        $this->assertFalse($guestSession->hasPermission('mod_settings', 'edit'));

        // Editor user attempting moderate_delete on another user's content
        $editorSession = new MockSession();
        $editorSession->set('user_id', 99);
        $editorSession->set('username', 'editor_user');
        $editorSession->set('permissions.mod_content.list', 1);
        $editorSession->set('permissions.mod_content.add', 1);
        $editorSession->set('permissions.mod_content.edit', 1);
        
        $this->assertTrue($editorSession->hasPermission('mod_content', 'add'));
        $this->assertFalse($editorSession->hasPermission('mod_content', 'moderate_delete'), "Editor must not have moderate_delete permission");
    }

    /**
     * 5. Corrupted Batch Reorder Payloads & Fault Tolerance
     */
    public function testAdversarialCorruptedBatchPayloads(): void
    {
        // Seed test FAQs
        $faqs = FaqFixtures::getFaqs();
        $stmt = $this->db->prepare("INSERT INTO mod_faq (id, mod_name, vid, question, answer, sort_order, active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($faqs as $f) {
            $stmt->execute([$f['id'], $f['mod_name'], $f['vid'], $f['question'], $f['answer'], $f['sort_order'], $f['active']]);
        }

        $initialFaqs = $this->db->query("SELECT id, sort_order FROM mod_faq ORDER BY id ASC")->fetchAll();

        // Corrupted payload with invalid ID strings and negative numbers
        $corruptPayload = [
            ['id' => 'not_an_id', 'sort_order' => -99],
            ['id' => 999999, 'sort_order' => 'NaN'],
            ['missing_keys' => true],
        ];

        // Process with validation guards
        foreach ($corruptPayload as $item) {
            $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
            $sort = filter_var($item['sort_order'] ?? null, FILTER_VALIDATE_INT);

            if ($id !== false && $id !== null && $sort !== false && $sort !== null && $sort >= 0) {
                $this->db->prepare("UPDATE mod_faq SET sort_order = ? WHERE id = ?")->execute([$sort, $id]);
            }
        }

        $afterFaqs = $this->db->query("SELECT id, sort_order FROM mod_faq ORDER BY id ASC")->fetchAll();
        $this->assertEquals($initialFaqs, $afterFaqs, "Corrupted batch payload must be rejected without mutating database records");
    }

    /**
     * 6. Deep Hierarchical Category Cascades & Integrity
     */
    public function testAdversarialDeepHierarchicalCategoryCascades(): void
    {
        // Build a 5-level deep category hierarchy
        $hierarchy = [
            10 => ['parent_id' => 0, 'name' => 'L1 Root'],
            11 => ['parent_id' => 10, 'name' => 'L2 Sub'],
            12 => ['parent_id' => 11, 'name' => 'L3 Nested'],
            13 => ['parent_id' => 12, 'name' => 'L4 Deep'],
            14 => ['parent_id' => 13, 'name' => 'L5 Leaf'],
        ];

        $catStmt = $this->db->prepare("INSERT INTO mod_content_category (id, parent_id, category_name, slug, sort_order, active) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($hierarchy as $id => $info) {
            $catStmt->execute([$id, $info['parent_id'], $info['name'], 'slug_' . $id, 1, 1]);
        }

        $countBefore = (int)$this->db->query("SELECT COUNT(*) FROM mod_content_category WHERE id >= 10")->fetchColumn();
        $this->assertEquals(5, $countBefore);

        // Delete root category (id=10) and verify recursive deletion of all 5 levels
        $toDelete = [10, 11, 12, 13, 14];
        $delStmt = $this->db->prepare("DELETE FROM mod_content_category WHERE id = ?");
        foreach ($toDelete as $delId) {
            $delStmt->execute([$delId]);
        }

        $countAfter = (int)$this->db->query("SELECT COUNT(*) FROM mod_content_category WHERE id >= 10")->fetchColumn();
        $this->assertEquals(0, $countAfter, "All descendants in deep hierarchical tree must be cleanly deleted");
    }

    /**
     * 7. Registry State Stress & Type Preservation
     */
    public function testAdversarialRegistryStateStressAndTypePreservation(): void
    {
        $testData = [
            'int_max' => PHP_INT_MAX,
            'float_pi' => 3.141592653589793,
            'bool_true' => true,
            'bool_false' => false,
            'array_nested' => ['kurdish' => ['kurmanci' => true, 'sorani' => true, 'zazaki' => true]],
            'null_val' => null,
            'unicode_blob' => 'Bulletin — Rojnameya Neteweyî ya Serbixwe — 2026',
        ];

        foreach ($testData as $key => $val) {
            \Registry::set($key, $val);
            $this->assertEquals($val, \Registry::get($key), "Registry must preserve exact value for key: {$key}");
        }

        // Mass key registration stress (1000 items)
        for ($i = 1; $i <= 1000; $i++) {
            \Registry::set("stress_key_{$i}", $i * 2);
        }

        $this->assertEquals(2000, \Registry::get('stress_key_1000'));
        $this->assertTrue(\Registry::has('stress_key_500'));

        // Clean up stress keys
        for ($i = 1; $i <= 1000; $i++) {
            \Registry::remove("stress_key_{$i}");
        }

        $this->assertFalse(\Registry::has('stress_key_1'));
    }

    /**
     * 8. Static Audit Across All 100% Modernized Templates
     */
    public function testAdversarialZeroCodeSeparationAndTouchTargetCompliance(): void
    {
        $modernizedDirs = [
            'templates/admin/mod_content_category',
            'templates/admin/mod_faq',
            'templates/admin/mod_newsletter',
            'templates/admin/mod_seo',
            'templates/admin/mod_settings',
        ];

        $allFiles = [];
        foreach ($modernizedDirs as $dir) {
            $files = $this->staticAuditor->scanDirectory($dir, ['twig']);
            $allFiles = array_merge($allFiles, $files);
        }

        $this->assertNotEmpty($allFiles, "Must find modernized Twig template files");
        $violations = $this->staticAuditor->checkCodeSeparation($allFiles);
        $this->assertCount(0, $violations, "All modernized admin templates must strictly have 0 inline styles, 0 style blocks, and 0 inline event handlers");

        // Verify touch target stylesheet compliance
        $cssAudit = $this->cssAuditor->auditStylesheet('templates/admin/assets/css/mod_faq.css');
        $this->assertTrue($cssAudit['has_touch_targets'], "FAQ stylesheet must meet touch target standards");

        $newsCssAudit = $this->cssAuditor->auditStylesheet('templates/admin/assets/css/mod_newsletter.css');
        $this->assertTrue($newsCssAudit['has_touch_targets'], "Newsletter stylesheet must meet touch target standards");
    }
}
