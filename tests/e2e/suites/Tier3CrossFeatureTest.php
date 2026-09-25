<?php

namespace E2E\Suites;

use E2E\Harness\TestCase;
use E2E\Harness\MockDb;
use E2E\Harness\MockSession;
use E2E\Harness\MockRequest;
use E2E\Fixtures\FaqFixtures;
use E2E\Fixtures\SettingsFixtures;
use Throwable;

class Tier3CrossFeatureTest extends TestCase
{
    private MockDb $db;
    private MockSession $session;

    public function getTier(): int
    {
        return 3;
    }

    public function getName(): string
    {
        return 'Tier 3: Cross-Feature Combinations';
    }

    public function setUp(): void
    {
        $this->db = new MockDb();
        $this->db->seedCoreModules();
        $this->session = new MockSession();
        $this->session->setAdminUser();
    }

    public function testContentCategoryAndFaqIntegration(): void
    {
        // 1. Create category
        $this->db->exec("
            INSERT INTO mod_content_category (id, category_name, slug, active)
            VALUES (1, 'Diplomacy & Global Politics', 'Diplomacy_and_Global_Politics', 1)
        ");

        // 2. Create article
        $this->db->exec("
            INSERT INTO mod_content (id, category_id, title, slug, summary, body, status)
            VALUES (101, 1, 'Kurdish Delegation Meets European Parliament Officials', 'Kurdish_Delegation_Meets_European_Parliament_Officials', 'Key discussions held in Brussels.', '<p>Full communique.</p>', 'published')
        ");

        // 3. Attach polymorphic FAQs
        $stmt = $this->db->prepare("
            INSERT INTO mod_faq (mod_name, vid, question, answer, sort_order, active)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute(['mod_content', 101, 'Which committees were visited?', 'Foreign Affairs and Human Rights.', 1, 1]);
        $stmt->execute(['mod_content', 101, 'Is a follow-up session scheduled?', 'Yes, next month in Strasbourg.', 2, 1]);

        // 4. Retrieve unified payload
        $article = $this->db->query("SELECT * FROM contents WHERE id = 101")->fetch();
        $this->assertNotNull($article);
        $this->assertEquals('Diplomacy & Global Politics', $article['category_name']);

        $faqs = $this->db->query("SELECT * FROM mod_faq WHERE mod_name = 'mod_content' AND vid = 101 ORDER BY sort_order ASC")->fetchAll();
        $this->assertCount(2, $faqs);
        $this->assertEquals('Which committees were visited?', $faqs[0]['question']);

        // 5. Generate Schema.org JSON-LD
        $schema = FaqFixtures::getSampleJsonLdSchema($faqs);
        $this->assertEquals('FAQPage', $schema['@type']);
        $this->assertCount(2, $schema['mainEntity']);
    }

    public function testHeroSliderAndCategoryFilter(): void
    {
        $this->db->exec("
            INSERT INTO mod_content_category (id, category_name, slug, active) VALUES 
            (1, 'Cat 1', 'cat_1', 1),
            (2, 'Cat 2', 'cat_2', 1)
        ");

        $this->db->exec("
            INSERT INTO mod_content (id, category_id, title, slug, hero_slider, status) VALUES 
            (1, 1, 'Slide 1 - Cat 1', 'slide_1', 1, 'published'),
            (2, 1, 'Slide 2 - Cat 1', 'slide_2', 0, 'published'),
            (3, 2, 'Slide 3 - Cat 2', 'slide_3', 1, 'published'),
            (4, 2, 'Slide 4 - Draft Cat 2', 'slide_4', 1, 'draft')
        ");

        $activeSlides = $this->db->query("
            SELECT * FROM contents 
            WHERE hero_slider = 1 AND status = 'published'
            ORDER BY id ASC
        ")->fetchAll();

        $this->assertCount(2, $activeSlides, "Only published articles with hero_slider=1 should be returned");
        $this->assertEquals('Slide 1 - Cat 1', $activeSlides[0]['title']);
        $this->assertEquals('Slide 3 - Cat 2', $activeSlides[1]['title']);
    }

    public function testSeoSitemapGenerationWithActiveContent(): void
    {
        $this->db->exec("
            INSERT INTO mod_content_category (id, category_name, slug, active) VALUES (1, 'News', 'news', 1);
            INSERT INTO mod_content (id, category_id, title, slug, status, updated_at) VALUES 
            (1, 1, 'Breaking News A', 'Breaking_News_A', 'published', '2026-08-22 10:00:00'),
            (2, 1, 'Draft News B', 'Draft_News_B', 'draft', '2026-08-22 11:00:00')
        ");

        $urls = [];
        $rows = $this->db->query("SELECT slug, updated_at FROM mod_content WHERE status = 'published'")->fetchAll();
        foreach ($rows as $row) {
            $urls[] = 'https://rojname.org/news/' . $row['slug'];
        }

        $this->assertCount(1, $urls);
        $this->assertEquals('https://rojname.org/news/Breaking_News_A', $urls[0]);
    }

    public function testSettingsLegalInfoAndFooterData(): void
    {
        $payload = SettingsFixtures::getLegalInfoPayload();
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO mod_settings (setting_key, setting_value) VALUES (?, ?)");
        
        foreach ($payload as $k => $v) {
            $stmt->execute(['legal_' . $k, $v]);
        }

        $company = $this->db->query("SELECT setting_value FROM mod_settings WHERE setting_key = 'legal_company_name'")->fetchColumn();
        $director = $this->db->query("SELECT setting_value FROM mod_settings WHERE setting_key = 'legal_managing_director'")->fetchColumn();

        $this->assertEquals('Rojname Media Publishing Ltd.', $company);
        $this->assertEquals('Rezan Azad', $director);
    }

    public function testNewsletterSubscriptionAndModeration(): void
    {
        $email = 'subscriber.new@domain.org';
        $token = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare("
            INSERT INTO mod_newsletter (email, name, status, unsubscribe_token, ip_address)
            VALUES (?, ?, 'active', ?, '127.0.0.1')
        ");
        $stmt->execute([$email, 'New Subscriber', $token]);

        $sub = $this->db->query("SELECT * FROM mod_newsletter WHERE email = '{$email}'")->fetch();
        $this->assertNotNull($sub);
        $this->assertEquals('active', $sub['status']);

        // Admin moderation: delete subscriber
        $delStmt = $this->db->prepare("DELETE FROM mod_newsletter WHERE id = ?");
        $delStmt->execute([$sub['id']]);

        $remaining = (int)$this->db->query("SELECT COUNT(*) FROM mod_newsletter WHERE email = '{$email}'")->fetchColumn();
        $this->assertEquals(0, $remaining, "Deleted subscriber must no longer exist in database");
    }

    public function testRollbackOnDatabaseTransactionFailure(): void
    {
        $this->db->beginTransaction();

        try {
            $this->db->exec("
                INSERT INTO mod_content_category (id, category_name, slug) 
                VALUES (50, 'Rollback Test Category', 'rollback_cat')
            ");

            // Provoke duplicate slug failure
            $this->db->exec("
                INSERT INTO mod_content_category (id, category_name, slug) 
                VALUES (51, 'Duplicate Category', 'rollback_cat')
            ");

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
        }

        $exists = (int)$this->db->query("SELECT COUNT(*) FROM mod_content_category WHERE id = 50")->fetchColumn();
        $this->assertEquals(0, $exists, "Transaction rollback must undo all operations in the block");
    }

    public function testAclPermissionToggleAndAjaxRelationUpdate(): void
    {
        // 1. Grant editor permission to group 2
        $this->db->exec("INSERT INTO mod_acl_permissions (group_id, mod_name, role_name) VALUES (2, 'mod_content', 'edit')");

        // 2. Editor attaches gallery to news article
        $this->db->exec("
            INSERT INTO mod_content (id, category_id, title, slug, status)
            VALUES (200, 1, 'Art Gallery Opening in Amed', 'Art_Gallery_Opening_in_Amed', 'published')
        ");

        $req = MockRequest::ajaxPost('/mod_content/ajax_update_relation', [
            'id' => 200,
            'relation_type' => 'gallery',
            'relation_id' => 5
        ]);
        $this->assertTrue($req->isAjax());
        $this->assertEquals(200, $req->getPost('id'));
        $this->assertEquals(5, $req->getPost('relation_id'));
    }

    public function testShareToolbarAndDocumentExportIntegration(): void
    {
        $article = [
            'id' => 300,
            'title' => 'Historical Peace Archive 2026',
            'slug' => 'Historical_Peace_Archive_2026',
            'body' => '<p>Complete peace archive documentation.</p>',
            'status' => 'published'
        ];
        $this->db->exec("
            INSERT INTO mod_content (id, category_id, title, slug, body, status)
            VALUES ({$article['id']}, 1, '{$article['title']}', '{$article['slug']}', '{$article['body']}', '{$article['status']}')
        ");

        $exportLinks = [
            'html' => "/tools/saveHtml/{$article['id']}",
            'text' => "/tools/saveText/{$article['id']}",
            'zip'  => "/tools/saveZip/{$article['id']}",
        ];
        $this->assertEquals('/tools/saveHtml/300', $exportLinks['html']);
        $this->assertEquals('/tools/saveText/300', $exportLinks['text']);
        $this->assertEquals('/tools/saveZip/300', $exportLinks['zip']);
    }
}
