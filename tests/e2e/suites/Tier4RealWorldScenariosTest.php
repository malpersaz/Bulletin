<?php

namespace E2E\Suites;

use E2E\Harness\TestCase;
use E2E\Harness\MockDb;
use E2E\Harness\MockSession;
use E2E\Harness\MockRequest;
use E2E\Fixtures\FaqFixtures;

class Tier4RealWorldScenariosTest extends TestCase
{
    private MockDb $db;
    private MockSession $session;

    public function getTier(): int
    {
        return 4;
    }

    public function getName(): string
    {
        return 'Tier 4: Real-World Scenarios';
    }

    public function setUp(): void
    {
        $this->db = new MockDb();
        $this->db->seedCoreModules();
        $this->session = new MockSession();
        $this->session->setAdminUser();
    }

    public function testFullEditorialPublishingWorkflow(): void
    {
        // Step 1: Admin creates category
        $this->db->exec("
            INSERT INTO mod_content_category (id, category_name, slug, description, active)
            VALUES (10, 'Investigative Journalism', 'Investigative_Journalism', 'In-depth investigative reports', 1)
        ");

        // Step 2: Admin drafts article
        $this->db->exec("
            INSERT INTO mod_content (id, category_id, title, slug, summary, body, image, slider, hero_slider, status, author_id)
            VALUES (500, 10, 'Sur Heritage Preservation and Restoration Audit', 'Sur_Heritage_Preservation_and_Restoration_Audit', 'Archaeological analysis of historical bastions.', '<p>Detailed report text.</p>', 'sur_restoration.webp', 1, 1, 'draft', 1)
        ");

        // Step 3: Admin attaches 2 FAQs
        $stmt = $this->db->prepare("
            INSERT INTO mod_faq (mod_name, vid, question, answer, sort_order, active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute(['mod_content', 500, 'When was the restoration completed?', 'Phase one concluded in July 2026.', 1]);
        $stmt->execute(['mod_content', 500, 'Are the bastions open to public visits?', 'Yes, guided cultural tours operate daily.', 2]);

        // Step 4: Admin configures SEO metadata
        $this->db->exec("
            INSERT INTO mod_seo (route_path, title, meta_description, canonical_url, og_title, robots)
            VALUES ('/article/Sur_Heritage_Preservation_and_Restoration_Audit', 'Sur Heritage Preservation Audit | Rojname', 'Comprehensive investigative report on Diyarbakir historical walls restoration.', 'https://rojname.org/article/Sur_Heritage_Preservation_and_Restoration_Audit', 'Sur Heritage Preservation Audit', 'index, follow')
        ");

        // Step 5: Admin publishes article
        $this->db->exec("UPDATE mod_content SET status = 'published', published_at = CURRENT_TIMESTAMP WHERE id = 500");

        // Step 6: Reader accesses article
        $published = $this->db->query("SELECT * FROM contents WHERE id = 500 AND status = 'published'")->fetch();
        $this->assertNotNull($published);
        $this->assertEquals('Investigative Journalism', $published['category_name']);
        $this->assertEquals('Sur_Heritage_Preservation_and_Restoration_Audit', $published['slug']);

        // Step 7: View count increment
        $this->db->exec("UPDATE mod_content SET views_count = views_count + 1 WHERE id = 500");
        $views = (int)$this->db->query("SELECT views_count FROM mod_content WHERE id = 500")->fetchColumn();
        $this->assertEquals(1, $views);

        // Step 8: Frontend FAQ schema check
        $faqs = $this->db->query("SELECT * FROM mod_faq WHERE mod_name = 'mod_content' AND vid = 500 ORDER BY sort_order ASC")->fetchAll();
        $this->assertCount(2, $faqs);
        $schema = FaqFixtures::getSampleJsonLdSchema($faqs);
        $this->assertEquals('FAQPage', $schema['@type']);
        $this->assertEquals('When was the restoration completed?', $schema['mainEntity'][0]['name']);
    }

    public function testSiteMaintenanceModeLockdown(): void
    {
        // 1. Set site to maintenance mode
        $kurdishMaintenanceMsg = "Pergala me tê nûjenkirin. Demek şûnda vegerin.";
        $this->db->exec("
            INSERT OR REPLACE INTO mod_settings (setting_key, setting_value) VALUES 
            ('site_status', 'offline'),
            ('maintenance_message', '{$kurdishMaintenanceMsg}')
        ");

        // 2. Simulate Anonymous Visitor
        $anonSession = new MockSession([]);
        $status = $this->db->query("SELECT setting_value FROM mod_settings WHERE setting_key = 'site_status'")->fetchColumn();
        $msg = $this->db->query("SELECT setting_value FROM mod_settings WHERE setting_key = 'maintenance_message'")->fetchColumn();

        $this->assertEquals('offline', $status);
        $this->assertEquals($kurdishMaintenanceMsg, $msg);
        $this->assertFalse($anonSession->get('is_admin', false), "Anonymous visitor is not admin");

        // 3. Admin remains authorized
        $this->assertTrue($this->session->get('is_admin', false), "Admin can still manage the system");

        // 4. Restore site status to online
        $this->db->exec("UPDATE mod_settings SET setting_value = 'online' WHERE setting_key = 'site_status'");
        $restored = $this->db->query("SELECT setting_value FROM mod_settings WHERE setting_key = 'site_status'")->fetchColumn();
        $this->assertEquals('online', $restored);
    }

    public function testNewsletterBulkCampaignLifecycle(): void
    {
        $subscribers = [
            ['reader1@kurdishmail.com', 'Azad', bin2hex(random_bytes(16)), '192.168.1.1'],
            ['reader2@kurdishmail.com', 'Diyar', bin2hex(random_bytes(16)), '192.168.1.2'],
            ['reader3@kurdishmail.com', 'Rojda', bin2hex(random_bytes(16)), '192.168.1.3'],
        ];

        $stmt = $this->db->prepare("
            INSERT INTO mod_newsletter (email, name, unsubscribe_token, ip_address, status)
            VALUES (?, ?, ?, ?, 'active')
        ");

        foreach ($subscribers as $s) {
            $stmt->execute($s);
        }

        $activeList = $this->db->query("SELECT * FROM mod_newsletter WHERE status = 'active' ORDER BY id ASC")->fetchAll();
        $this->assertCount(3, $activeList);

        // Reader #2 clicks unsubscribe
        $tokenToUnsub = $subscribers[1][2];
        $unsubStmt = $this->db->prepare("
            UPDATE mod_newsletter 
            SET status = 'unsubscribed', unsubscribed_at = CURRENT_TIMESTAMP 
            WHERE unsubscribe_token = ?
        ");
        $unsubStmt->execute([$tokenToUnsub]);

        $activeRemaining = (int)$this->db->query("SELECT COUNT(*) FROM mod_newsletter WHERE status = 'active'")->fetchColumn();
        $this->assertEquals(2, $activeRemaining);

        $unsubRecord = $this->db->query("SELECT * FROM mod_newsletter WHERE unsubscribe_token = '{$tokenToUnsub}'")->fetch();
        $this->assertEquals('unsubscribed', $unsubRecord['status']);
        $this->assertNotNull($unsubRecord['unsubscribed_at']);
    }

    public function testCategoryReorganizationAndArticleReassignment(): void
    {
        // Setup initial categories
        $this->db->exec("
            INSERT INTO mod_content_category (id, category_name, slug, active) VALUES 
            (1, 'General News', 'General_News', 1),
            (2, 'Culture & Heritage', 'Culture_and_Heritage', 1)
        ");

        // Create articles in General News
        $this->db->exec("
            INSERT INTO mod_content (id, category_id, title, slug, status) VALUES 
            (1, 1, 'Traditional Dengbêj Night in Amed', 'Traditional_Dengbej_Night_in_Amed', 'published'),
            (2, 1, 'Kurdish Grammar Symposium in Hewlêr', 'Kurdish_Grammar_Symposium_in_Hewler', 'published')
        ");

        // Verify initial join
        $initial = $this->db->query("SELECT category_name FROM contents WHERE id = 1")->fetchColumn();
        $this->assertEquals('General News', $initial);

        // Admin reassigns articles to Culture & Heritage
        $this->db->exec("UPDATE mod_content SET category_id = 2 WHERE category_id = 1");

        // Verify updated view
        $updated = $this->db->query("SELECT category_name, category_slug FROM contents WHERE id = 1")->fetch();
        $this->assertEquals('Culture & Heritage', $updated['category_name']);
        $this->assertEquals('Culture_and_Heritage', $updated['category_slug']);
    }

    public function testSeoRobotsAndDynamicSitemapAutoUpdate(): void
    {
        $this->db->exec("
            INSERT INTO mod_content_category (id, category_name, slug, active) VALUES (1, 'Art', 'Art', 1);
            INSERT INTO mod_content (id, category_id, title, slug, status, updated_at) VALUES 
            (10, 1, 'Contemporary Kurdish Painting Exhibition', 'Contemporary_Kurdish_Painting_Exhibition', 'published', '2026-08-23 12:00:00')
        ");

        // Dynamically build sitemap
        $rows = $this->db->query("
            SELECT slug, updated_at FROM mod_content WHERE status = 'published'
            UNION ALL
            SELECT slug, updated_at FROM mod_content_category WHERE active = 1
        ")->fetchAll();

        $this->assertCount(2, $rows, "Sitemap should index 1 published article + 1 active category");
    }
}
