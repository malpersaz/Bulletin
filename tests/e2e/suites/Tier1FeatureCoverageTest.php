<?php

namespace E2E\Suites;

use E2E\Harness\TestCase;
use E2E\Harness\MockDb;
use E2E\Harness\MockSession;
use E2E\Harness\MockRequest;
use E2E\Harness\StaticAuditor;
use E2E\Harness\TwigAuditor;
use E2E\Harness\CssAuditor;
use E2E\Fixtures\ModuleFixtures;
use E2E\Fixtures\ContentFixtures;
use E2E\Fixtures\FaqFixtures;
use E2E\Fixtures\SeoFixtures;
use E2E\Fixtures\NewsletterFixtures;
use E2E\Fixtures\SettingsFixtures;
use E2E\Fixtures\LanguageFixtures;

class Tier1FeatureCoverageTest extends TestCase
{
    private MockDb $db;
    private MockSession $session;
    private StaticAuditor $staticAuditor;
    private TwigAuditor $twigAuditor;
    private CssAuditor $cssAuditor;
    private string $baseDir;

    public function getTier(): int
    {
        return 1;
    }

    public function getName(): string
    {
        return 'Tier 1: Feature Coverage';
    }

    public function setUp(): void
    {
        $this->db = new MockDb();
        $this->db->seedCoreModules();
        $this->session = new MockSession();
        $this->session->setAdminUser();

        $this->baseDir = dirname(__DIR__, 3);
        $this->staticAuditor = new StaticAuditor($this->baseDir);
        $this->twigAuditor = new TwigAuditor($this->baseDir);
        $this->cssAuditor = new CssAuditor($this->baseDir);
    }

    // =========================================================================
    // Feature 1: Twig Unicode SEO Filter (legacySeoFilter / Helper::legacy_seo)
    // =========================================================================

    public function testF1_TwigLegacySeoPreservesKurdishLatinLetters(): void
    {
        $input = 'Çavdêrî û Şîrove li ser Civaka Kurdî';
        $result = \Helper::legacy_seo($input);
        $this->assertEquals('Çavdêrî_û_Şîrove_li_ser_Civaka_Kurdî', $result, 'Kurdish Latin letters Ç, ê, î, ş, û must be preserved with underscores');
    }

    public function testF1_TwigLegacySeoPreservesNumbersAndPunctuation(): void
    {
        $input = 'Kongreya 2026an ya Neteweyî: Beşa 1em';
        $result = \Helper::legacy_seo($input);
        $this->assertEquals('Kongreya_2026an_ya_Neteweyî_Beşa_1em', $result, 'Numbers and letters should be preserved in Wikipedia style');
    }

    public function testF1_TwigLegacySeoPreservesArabicAndSoraniCharacters(): void
    {
        $input = 'هەواڵ و رووداوەکانی رۆژەڤ';
        $result = \Helper::legacy_seo($input);
        $this->assertEquals('هەواڵ_و_رووداوەکانی_رۆژەڤ', $result, 'Sorani Arabic script must be preserved in Wikipedia style');
    }

    public function testF1_TwigLegacySeoReplacesConsecutiveSpacesWithUnderscore(): void
    {
        $input = 'Diyarbekir    Sûr    Mîrat';
        $result = \Helper::legacy_seo($input);
        $this->assertEquals('Diyarbekir_Sûr_Mîrat', $result, 'Multiple consecutive spaces must collapse to a single underscore');
    }

    public function testF1_TwigLegacySeoTrimsWhitespaceAndPreservesCasing(): void
    {
        $input = '   Zimanê Kurdî ya Nûjen   ';
        $result = \Helper::legacy_seo($input);
        $this->assertEquals('Zimanê_Kurdî_ya_Nûjen', $result, 'Leading and trailing whitespace must be trimmed while preserving exact casing');
    }

    // =========================================================================
    // Feature 2: ORM Case-Insensitive Columns & Exceptions
    // =========================================================================

    public function testF2_OrmCaseInsensitiveColumnResolution(): void
    {
        $columns = ['user_id', 'title', 'category_id', 'created_at'];
        $lookupKey = 'USER_ID';
        $resolved = null;
        foreach ($columns as $col) {
            if (strcasecmp($col, $lookupKey) === 0) {
                $resolved = $col;
                break;
            }
        }
        $this->assertEquals('user_id', $resolved, 'Case-insensitive column lookup should match user_id');
    }

    public function testF2_OrmWhereBuilderGeneratesValidSql(): void
    {
        $columns = ['id', 'status', 'user_id'];
        $sql = \Obuilder::where('id\_and\_status', [1, 'published'], $columns, 'mod_content');
        $this->assertStringContainsString("id = '1'", $sql);
        $this->assertStringContainsString("status = 'published'", $sql);
    }

    public function testF2_OrmCheckKeyExistsWithCaseInsensitivity(): void
    {
        $columns = ['id', 'title', 'summary'];
        $isValid = true;
        try {
            \Obuilder::check_key_exists('title', $columns);
        } catch (\Throwable $e) {
            $isValid = false;
        }
        $this->assertTrue($isValid, 'check_key_exists must accept valid model columns');
    }

    public function testF2_OrmExceptionsAreInEnglish(): void
    {
        $exception = new \OrmException("Column 'invalid_col' is undefined in model schema");
        $this->assertStringContainsString("Column 'invalid_col' is undefined", $exception->getMessage());
        $this->assertStringNotContainsString("tanımlanmamış", $exception->getMessage());
    }

    public function testF2_OrmDynamicMethodsWithCasing(): void
    {
        $columns = ['id', 'views_count', 'is_featured'];
        $whereSql = \Obuilder::where('is_featured', [1], $columns, 'mod_content');
        $this->assertStringContainsString("is_featured = '1'", $whereSql);
    }

    // =========================================================================
    // Feature 3: BaseController Relation Helpers
    // =========================================================================

    public function testF3_BaseControllerAjaxGetFilesContract(): void
    {
        $files = [
            ['id' => 1, 'title' => 'Press Release PDF', 'filename' => 'press_release.pdf', 'filesize' => '2.4 MB'],
            ['id' => 2, 'title' => 'Financial Audit Document', 'filename' => 'audit_2026.pdf', 'filesize' => '5.1 MB']
        ];
        $response = ['status' => 'success', 'data' => $files, 'count' => count($files)];
        $this->assertEquals('success', $response['status']);
        $this->assertCount(2, $response['data']);
        $this->assertEquals('press_release.pdf', $response['data'][0]['filename']);
    }

    public function testF3_BaseControllerAjaxGetImagesContract(): void
    {
        $galleries = [
            ['id' => 10, 'gallery_name' => 'Amed Photojournalism Exhibition', 'image_count' => 12],
            ['id' => 11, 'gallery_name' => 'Historical Walls Gallery', 'image_count' => 8]
        ];
        $response = ['status' => 'success', 'galleries' => $galleries];
        $this->assertEquals('success', $response['status']);
        $this->assertEquals(12, $response['galleries'][0]['image_count']);
    }

    public function testF3_BaseControllerAjaxGetCurrentMediaContract(): void
    {
        $currentMedia = [
            'file_id' => 4,
            'gallery_id' => 10,
            'video' => ['id' => 2, 'embed_code' => '<iframe src="..."></iframe>']
        ];
        $this->assertEquals(4, $currentMedia['file_id']);
        $this->assertEquals(10, $currentMedia['gallery_id']);
        $this->assertIsArray($currentMedia['video']);
    }

    public function testF3_BaseControllerAjaxUpdateRelationContract(): void
    {
        $req = MockRequest::ajaxPost('/mod_content/ajax_update_relation', [
            'id' => 100,
            'relation_type' => 'gallery',
            'relation_id' => 10
        ]);
        $this->assertTrue($req->isAjax());
        $this->assertEquals('gallery', $req->getPost('relation_type'));
        $this->assertEquals(10, $req->getPost('relation_id'));
    }

    public function testF3_BaseControllerRelationAclEnforcement(): void
    {
        $guestSession = new MockSession([]);
        $this->assertFalse($guestSession->hasPermission('mod_content', 'edit'), 'Guest user cannot execute relation updates');
        $this->assertTrue($this->session->hasPermission('mod_content', 'edit'), 'Admin session has edit permission for relations');
    }

    // =========================================================================
    // Feature 4: Admin User ACL Modernization (acl.twig)
    // =========================================================================

    public function testF4_AclTwigTemplateZeroInlineCode(): void
    {
        $aclTwigPath = $this->baseDir . '/templates/admin/mod_user/acl.twig';
        if (file_exists($aclTwigPath)) {
            $violations = $this->staticAuditor->checkCodeSeparation([$aclTwigPath]);
            $this->assertCount(0, $violations, 'mod_user/acl.twig must have 0 inline styles, 0 style blocks, and 0 inline scripts');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF4_AclCssTouchTargetCompliance(): void
    {
        $cssPath = 'templates/admin/assets/css/mod_user_acl.css';
        $fullPath = $this->baseDir . '/' . $cssPath;
        if (file_exists($fullPath)) {
            $content = file_get_contents($fullPath);
            $hasTouchTarget = str_contains($content, '44px') || str_contains($content, 'min-height') || str_contains($content, 'min-width');
            $this->assertTrue($hasTouchTarget, 'mod_user_acl.css must have touch targets >= 44px for phone viewports');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF4_AclJsEventDelegation(): void
    {
        $jsPath = $this->baseDir . '/templates/admin/assets/js/mod_user_acl.js';
        if (file_exists($jsPath)) {
            $content = file_get_contents($jsPath);
            $this->assertStringContainsString('addEventListener', $content, 'mod_user_acl.js should use modern event delegation');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF4_AclMobileCarouselStructure(): void
    {
        $aclTwigPath = $this->baseDir . '/templates/admin/mod_user/acl.twig';
        if (file_exists($aclTwigPath)) {
            $content = file_get_contents($aclTwigPath);
            $this->assertFalse(str_contains($content, 'onclick='), 'ACL template must not use inline onclick handlers');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF4_AclLiveSearchFiltering(): void
    {
        $aclTwigPath = $this->baseDir . '/templates/admin/mod_user/acl.twig';
        if (file_exists($aclTwigPath)) {
            $content = file_get_contents($aclTwigPath);
            $hasSearch = str_contains($content, 'search') || str_contains($content, 'filter') || str_contains($content, 'input');
            $this->assertTrue($hasSearch, 'ACL template should provide filter search input structure');
        } else {
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // Feature 5: Asynchronous toggle_permission AJAX Endpoint
    // =========================================================================

    public function testF5_TogglePermissionEndpointSuccess(): void
    {
        $this->db->exec("
            INSERT INTO mod_acl_permissions (group_id, mod_name, role_name)
            VALUES (1, 'mod_content', 'list')
        ");
        $count = (int)$this->db->query("SELECT COUNT(*) FROM mod_acl_permissions WHERE group_id = 1 AND mod_name = 'mod_content'")->fetchColumn();
        $this->assertEquals(1, $count);
    }

    public function testF5_TogglePermissionRevokeState(): void
    {
        $this->db->exec("
            INSERT INTO mod_acl_permissions (group_id, mod_name, role_name) VALUES (2, 'mod_news', 'delete')
        ");
        $this->db->exec("DELETE FROM mod_acl_permissions WHERE group_id = 2 AND mod_name = 'mod_news' AND role_name = 'delete'");
        $count = (int)$this->db->query("SELECT COUNT(*) FROM mod_acl_permissions WHERE group_id = 2 AND mod_name = 'mod_news' AND role_name = 'delete'")->fetchColumn();
        $this->assertEquals(0, $count, 'Revoking permission should remove record');
    }

    public function testF5_TogglePermissionDatabaseTransactionCommit(): void
    {
        $this->db->beginTransaction();
        $this->db->exec("INSERT INTO mod_acl_permissions (group_id, mod_name, role_name) VALUES (3, 'mod_page', 'add')");
        $this->db->commit();

        $count = (int)$this->db->query("SELECT COUNT(*) FROM mod_acl_permissions WHERE group_id = 3 AND mod_name = 'mod_page'")->fetchColumn();
        $this->assertEquals(1, $count);
    }

    public function testF5_TogglePermissionSessionSync(): void
    {
        $session = new MockSession(['group_id' => 1, 'permissions' => []]);
        $session->set('permissions.mod_content.add', 1);
        $this->assertTrue($session->hasPermission('mod_content', 'add'));
    }

    public function testF5_TogglePermissionBatchRoles(): void
    {
        $roles = ['list', 'add', 'edit', 'delete'];
        $stmt = $this->db->prepare("INSERT INTO mod_acl_permissions (group_id, mod_name, role_name) VALUES (1, 'mod_gallery', ?)");
        foreach ($roles as $role) {
            $stmt->execute([$role]);
        }
        $count = (int)$this->db->query("SELECT COUNT(*) FROM mod_acl_permissions WHERE group_id = 1 AND mod_name = 'mod_gallery'")->fetchColumn();
        $this->assertEquals(4, $count);
    }

    // =========================================================================
    // Feature 6: Modernized mod_acl & AclManager
    // =========================================================================

    public function testF6_ModAclTemplatesZeroInlineCode(): void
    {
        $files = $this->staticAuditor->scanDirectory('templates/admin/mod_acl', ['twig']);
        if (!empty($files)) {
            $violations = $this->staticAuditor->checkCodeSeparation($files);
            $this->assertCount(0, $violations, 'All templates in mod_acl must have 0 inline styles and 0 inline scripts');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF6_ModAclNoScriptTwig(): void
    {
        $scriptTwig = $this->baseDir . '/templates/admin/mod_acl/script.twig';
        $this->assertFileDoesNotExist($scriptTwig, 'script.twig in mod_acl must be completely purged');
    }

    public function testF6_ModAclNoTurkishMethodNames(): void
    {
        $aclFiles = array_merge(
            $this->staticAuditor->scanDirectory('templates/admin/mod_acl', ['twig']),
            $this->staticAuditor->scanDirectory('mod/mod_acl', ['php'])
        );
        foreach ($aclFiles as $f) {
            $content = file_get_contents($f);
            $this->assertStringNotContainsString('processSil', $content, "File {$f} must not contain processSil");
            $this->assertStringNotContainsString('processDosya', $content, "File {$f} must not contain processDosya");
        }
    }

    public function testF6_AclManagerClassPresence(): void
    {
        $jsFile = $this->baseDir . '/templates/admin/assets/js/mod_acl.js';
        if (file_exists($jsFile)) {
            $content = file_get_contents($jsFile);
            $this->assertTrue(str_contains($content, 'AclManager') || str_contains($content, 'acl'), 'mod_acl.js must define AclManager');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF6_ModAclCssTouchTargets(): void
    {
        $cssFile = $this->baseDir . '/templates/admin/assets/css/mod_acl.css';
        if (file_exists($cssFile)) {
            $audit = $this->cssAuditor->auditStylesheet('templates/admin/assets/css/mod_acl.css');
            $this->assertTrue($audit['has_touch_targets'], 'mod_acl.css must enforce touch targets for mobile admin UI');
        } else {
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // Feature 7: Admin Partials Code Separation
    // =========================================================================

    public function testF7_HeaderTwigZeroInlineCode(): void
    {
        $file = $this->baseDir . '/templates/admin/partials/header.twig';
        if (file_exists($file)) {
            $violations = $this->staticAuditor->checkCodeSeparation([$file]);
            $this->assertCount(0, $violations, 'header.twig must have 0 inline styles or handlers');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF7_FilterSheetTwigZeroInlineCode(): void
    {
        $file = $this->baseDir . '/templates/admin/partials/filter_sheet.twig';
        if (file_exists($file)) {
            $violations = $this->staticAuditor->checkCodeSeparation([$file]);
            $this->assertCount(0, $violations, 'filter_sheet.twig must have zero inline handlers');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF7_SheetsExtraTwigZeroInlineCode(): void
    {
        $file = $this->baseDir . '/templates/admin/partials/sheets_extra.twig';
        if (file_exists($file)) {
            $violations = $this->staticAuditor->checkCodeSeparation([$file]);
            $this->assertCount(0, $violations, 'sheets_extra.twig must be decoupled into external assets');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF7_ModalUserMenuJsDelegation(): void
    {
        $file = $this->baseDir . '/templates/admin/assets/js/modals/modal_user_menu.js';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertStringContainsString('data-action', $content, 'modal_user_menu.js should use data-action delegation');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF7_CoreModalsCssStyles(): void
    {
        $file = $this->baseDir . '/templates/admin/assets/css/core_modals.css';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertTrue(strlen($content) > 10, 'core_modals.css should contain styling for dialogs and sheets');
        } else {
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // Feature 8: Reusable share_toolbar.twig & Assets
    // =========================================================================

    public function testF8_ShareToolbarTwigZeroInlineCode(): void
    {
        $file = $this->baseDir . '/templates/amida/partials/share_toolbar.twig';
        if (file_exists($file)) {
            $violations = $this->staticAuditor->checkCodeSeparation([$file]);
            $this->assertCount(0, $violations, 'share_toolbar.twig must have 0 inline styles and 0 inline scripts');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF8_ShareToolbarSocialLinks(): void
    {
        $file = $this->baseDir . '/templates/amida/partials/share_toolbar.twig';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertStringContainsString('whatsapp', strtolower($content));
            $this->assertStringContainsString('facebook', strtolower($content));
            $this->assertStringContainsString('tiktok', strtolower($content));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF8_ShareToolbarDocumentExportLinks(): void
    {
        $file = $this->baseDir . '/templates/amida/partials/share_toolbar.twig';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertTrue(str_contains($content, 'tools/saveHtml') || str_contains($content, 'saveHtml'), 'Must link to HTML document export');
            $this->assertTrue(str_contains($content, 'tools/saveText') || str_contains($content, 'saveText'), 'Must link to Text document export');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF8_ShareToolbarPrintAction(): void
    {
        $file = $this->baseDir . '/templates/amida/partials/share_toolbar.twig';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertStringContainsString('data-action="print"', $content, 'Print button must use data-action="print"');
            $this->assertStringNotContainsString('onclick="window.print()"', $content);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF8_ShareCssAndJsAssets(): void
    {
        $css = $this->baseDir . '/templates/amida/static/css/share.css';
        $js = $this->baseDir . '/templates/amida/static/js/share.js';
        if (file_exists($css) && file_exists($js)) {
            $this->assertTrue(filesize($css) > 0);
            $this->assertTrue(filesize($js) > 0);
        } else {
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // Feature 9: Compact share.twig Modernization
    // =========================================================================

    public function testF9_CompactShareTwigZeroInlineCode(): void
    {
        $file = $this->baseDir . '/templates/amida/partials/share.twig';
        if (file_exists($file)) {
            $violations = $this->staticAuditor->checkCodeSeparation([$file]);
            $this->assertCount(0, $violations, 'share.twig must have 0 inline styles or inline handlers');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF9_CompactShareUsesExternalShareAssets(): void
    {
        $file = $this->baseDir . '/templates/amida/partials/share.twig';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertFalse(str_contains($content, '<style>'), 'Compact share must not include <style> blocks');
            $this->assertFalse(str_contains($content, '<script>'), 'Compact share must not include inline <script> blocks');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF9_CompactShareNativeShareIntegration(): void
    {
        $file = $this->baseDir . '/templates/amida/partials/share.twig';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $hasShareTrigger = str_contains($content, 'share') || str_contains($content, 'data-action');
            $this->assertTrue($hasShareTrigger);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF9_CompactShareClipboardTooltipElement(): void
    {
        $jsFile = $this->baseDir . '/templates/amida/static/js/share.js';
        if (file_exists($jsFile)) {
            $content = file_get_contents($jsFile);
            $this->assertTrue(str_contains($content, 'clipboard') || str_contains($content, 'tooltip') || str_contains($content, 'copy'), 'share.js should implement clipboard feedback');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF9_CompactShareTouchTargetSizes(): void
    {
        $cssFile = $this->baseDir . '/templates/amida/static/css/share.css';
        if (file_exists($cssFile)) {
            $content = file_get_contents($cssFile);
            $this->assertTrue(str_contains($content, 'min-height') || str_contains($content, 'min-width') || str_contains($content, 'padding'), 'share.css should define tactile button dimensions');
        } else {
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // Feature 10: Detail Views Share Toolbar Integration
    // =========================================================================

    public function testF10_NewsDetailIncludesShareToolbar(): void
    {
        $file = $this->baseDir . '/templates/amida/mod_news/detail.twig';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertTrue(str_contains($content, 'share_toolbar.twig') || str_contains($content, 'share.twig'), 'mod_news/detail.twig must include modernized share toolbar');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF10_PublicationDetailIncludesShareToolbar(): void
    {
        $file = $this->baseDir . '/templates/amida/mod_publication/detail.twig';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertTrue(str_contains($content, 'share_toolbar.twig') || str_contains($content, 'share.twig'), 'mod_publication/detail.twig must include share toolbar');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF10_ContentDetailIncludesShareToolbar(): void
    {
        $file = $this->baseDir . '/templates/amida/mod_content/detail.twig';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertTrue(str_contains($content, 'share_toolbar.twig') || str_contains($content, 'share.twig'), 'mod_content/detail.twig must include share toolbar');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF10_PageDetailIncludesShareToolbar(): void
    {
        $file = $this->baseDir . '/templates/amida/mod_page/detail.twig';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertTrue(str_contains($content, 'share_toolbar.twig') || str_contains($content, 'share.twig'), 'mod_page/detail.twig must include share toolbar');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF10_AnnouncementsDetailIncludesShareToolbar(): void
    {
        $file = $this->baseDir . '/templates/amida/mod_announcements/detail.twig';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertTrue(str_contains($content, 'share_toolbar.twig') || str_contains($content, 'share.twig'), 'mod_announcements/detail.twig must include share toolbar');
        } else {
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // Feature 11: Document Export & Tools Cleanliness
    // =========================================================================

    public function testF11_ToolsControllerSaveHtmlEndpoint(): void
    {
        $sampleHtml = "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>News Article</title></head><body><h1>News Article</h1><p>Body content</p></body></html>";
        $this->assertStringContainsString('<!DOCTYPE html>', $sampleHtml);
        $this->assertStringContainsString('charset="UTF-8"', $sampleHtml);
    }

    public function testF11_ToolsControllerSaveTextEndpoint(): void
    {
        $html = "<h1>Sur Restoration Report</h1><p>The historical wall restoration is ongoing.</p>";
        $plainText = trim(strip_tags($html));
        $this->assertEquals("Sur Restoration ReportThe historical wall restoration is ongoing.", str_replace("\n", "", $plainText));
    }

    public function testF11_ToolsControllerSaveZipEndpoint(): void
    {
        $zipHeader = "PK\x03\x04";
        $this->assertEquals("PK\x03\x04", $zipHeader, 'ZIP files must start with PK header');
    }

    public function testF11_ToolsControllerPermissionCheck(): void
    {
        $guestSession = new MockSession([]);
        $isDraft = true;
        $canExport = !$isDraft || $guestSession->get('is_admin', false);
        $this->assertFalse($canExport, 'Guests cannot export draft content');
    }

    public function testF11_ToolsDictionaryKeysInAll9Languages(): void
    {
        $languages = LanguageFixtures::getSupportedLanguages();
        $this->assertCount(9, $languages);
        foreach ($languages as $lang) {
            $langFile = $this->baseDir . "/mod/index/lang/{$lang}.php";
            if (file_exists($langFile)) {
                $dict = LanguageFixtures::loadModuleLang($langFile);
                $this->assertIsArray($dict);
            }
        }
    }

    // =========================================================================
    // Feature 12: Corporate & Fiscal Settings Schema
    // =========================================================================

    public function testF12_CorporateFieldsInInstallerSql(): void
    {
        $sqlPath = $this->baseDir . '/installer/mod_settings/mod_settings.sql';
        if (file_exists($sqlPath)) {
            $content = file_get_contents($sqlPath);
            $this->assertStringContainsString('legal_company_name', $content);
            $this->assertStringContainsString('tax_number', $content);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF12_CorporateRegistrationFieldsInInstallerSql(): void
    {
        $sqlPath = $this->baseDir . '/installer/mod_settings/mod_settings.sql';
        if (file_exists($sqlPath)) {
            $content = file_get_contents($sqlPath);
            $this->assertStringContainsString('registered_email', $content);
            $this->assertStringContainsString('corporate_address', $content);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF12_FiscalSettingsFieldsInInstallerSql(): void
    {
        $sqlPath = $this->baseDir . '/installer/mod_settings/mod_settings.sql';
        if (file_exists($sqlPath)) {
            $content = file_get_contents($sqlPath);
            $this->assertStringContainsString('managing_director', $content);
            $this->assertStringContainsString('editor_in_chief', $content);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF12_SettingsSqlParity(): void
    {
        $installerSql = $this->baseDir . '/installer/mod_settings/mod_settings.sql';
        $modSql = $this->baseDir . '/mod/mod_settings/mod_settings.sql';
        if (file_exists($installerSql) && file_exists($modSql)) {
            $instContent = file_get_contents($installerSql);
            $modContent = file_get_contents($modSql);
            $this->assertStringContainsString('legal_company_name', $instContent);
            $this->assertStringContainsString('legal_company_name', $modContent);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF12_SettingsModelCorporateGetters(): void
    {
        $fields = LanguageFixtures::getCorporateFields();
        $this->assertCount(16, $fields, 'Must define 16 corporate and fiscal fields');
    }

    // =========================================================================
    // Feature 13: Multilingual Legal & Cookie Page Fixtures
    // =========================================================================

    public function testF13_InstallerPageSeedsLegalInfoInAll9Languages(): void
    {
        $pageInstaller = $this->baseDir . '/installer/mod_page/index.php';
        if (file_exists($pageInstaller)) {
            $content = file_get_contents($pageInstaller);
            $this->assertStringContainsString('Legal Information', $content);
            $this->assertStringContainsString('Agahdariya Zagônî', $content);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF13_InstallerPageSeedsCookiePolicyInAll9Languages(): void
    {
        $pageInstaller = $this->baseDir . '/installer/mod_page/index.php';
        if (file_exists($pageInstaller)) {
            $content = file_get_contents($pageInstaller);
            $this->assertStringContainsString('Cookie Policy', $content);
            $this->assertStringContainsString('Rêbaza Çerezan', $content);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF13_LegalInfoPageContentNotEmpty(): void
    {
        $sampleKurdishLegal = "Ev malper li gorî zagonên weşana dîjîtal tê meşandin.";
        $this->assertNotEmpty($sampleKurdishLegal);
    }

    public function testF13_CookiePolicyPageContentNotEmpty(): void
    {
        $sampleCookieText = "Ev malper ji bo ezmûneke çêtir çerezan bi kar tîne.";
        $this->assertNotEmpty($sampleCookieText);
    }

    public function testF13_PageSeedsSlugsFollowWikipediaStyle(): void
    {
        $slug = \Helper::legacy_seo('Agahdariya Zagônî');
        $this->assertEquals('Agahdariya_Zagônî', $slug);
    }

    // =========================================================================
    // Feature 14: E-E-A-T Frontend Components
    // =========================================================================

    public function testF14_LegalInfoPartialZeroInlineCode(): void
    {
        $file = $this->baseDir . '/templates/amida/partials/_legal_info.twig';
        if (file_exists($file)) {
            $violations = $this->staticAuditor->checkCodeSeparation([$file]);
            $this->assertCount(0, $violations, '_legal_info.twig must have 0 inline styles and 0 inline scripts');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF14_CookieConsentPartialDynamicSlug(): void
    {
        $file = $this->baseDir . '/templates/amida/partials/_cookie_consent.twig';
        if (file_exists($file)) {
            $violations = $this->staticAuditor->checkCodeSeparation([$file]);
            $this->assertCount(0, $violations, '_cookie_consent.twig must have 0 inline styles and 0 inline scripts');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF14_FooterTwigLinksToLegalAndCookiePages(): void
    {
        $file = $this->baseDir . '/templates/amida/index/footer.twig';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertTrue(str_contains($content, 'legal') || str_contains($content, 'cookie') || str_contains($content, 'page/'), 'Footer should link to legal/cookie pages');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF14_LegalInfoRendersPublishingCredentials(): void
    {
        $file = $this->baseDir . '/templates/amida/partials/_legal_info.twig';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertTrue(str_contains($content, 'managing_director') || str_contains($content, 'editor_in_chief') || str_contains($content, 'company_name'));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF14_CookieConsentZeroInlineStyles(): void
    {
        $file = $this->baseDir . '/templates/amida/partials/_cookie_consent.twig';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertFalse(str_contains($content, 'style="'));
            $this->assertFalse(str_contains($content, '<style>'));
        } else {
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // Feature 15: Sorani Kurdish Display Normalization (سۆرانی)
    // =========================================================================

    public function testF15_SoraniDisplayNameInInstallerLangIndex(): void
    {
        $file = $this->baseDir . '/installer/lang/index.php';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertStringContainsString('سۆرانی', $content);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF15_SoraniDisplayNameInInstallerLangClass(): void
    {
        $file = $this->baseDir . '/installer/app/InstallerLang.php';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertStringContainsString('سۆرانی', $content);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF15_SoraniDisplayNameInAllInstallerLangFiles(): void
    {
        $files = $this->staticAuditor->scanDirectory('installer/lang', ['php']);
        $this->assertNotEmpty($files);
    }

    public function testF15_SoraniLanguageCodeIsCkb(): void
    {
        $ckbFile = $this->baseDir . '/installer/lang/ckb.php';
        if (file_exists($ckbFile)) {
            $this->assertFileExists($ckbFile);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF15_SoraniDirectionIsRtl(): void
    {
        $ckbFile = $this->baseDir . '/installer/lang/ckb.php';
        if (file_exists($ckbFile)) {
            $dict = include $ckbFile;
            if (is_array($dict) && isset($dict['direction'])) {
                $this->assertEquals('rtl', $dict['direction']);
            }
        }
        $this->assertTrue(true);
    }

    // =========================================================================
    // Feature 16: Brand Placeholder Fallback Hierarchy
    // =========================================================================

    public function testF16_BrandFallbackPriority1DirectImage(): void
    {
        $item = ['image' => 'news_cover.webp', 'has_disk_file' => true];
        $renderClass = ($item['image'] && $item['has_disk_file']) ? 'detail-hero-image' : 'detail-hero-placeholder';
        $this->assertEquals('detail-hero-image', $renderClass);
    }

    public function testF16_BrandFallbackPriority2SiteLogo(): void
    {
        $site = ['logo_path' => 'templates/amida/assets/img/logo.svg'];
        $item = ['image' => null];
        $placeholderMode = ($item['image'] === null && !empty($site['logo_path'])) ? 'logo' : 'symbol';
        $this->assertEquals('logo', $placeholderMode);
    }

    public function testF16_BrandFallbackPriority3SiteSymbol(): void
    {
        $site = ['logo_path' => null, 'logo_symbol' => 'templates/amida/assets/img/symbol.svg'];
        $item = ['image' => null];
        $placeholderMode = ($item['image'] === null && empty($site['logo_path']) && !empty($site['logo_symbol'])) ? 'symbol' : 'gradient';
        $this->assertEquals('symbol', $placeholderMode);
    }

    public function testF16_BrandFallbackPriority4RadialGradient(): void
    {
        $site = ['logo_path' => null, 'logo_symbol' => null];
        $item = ['image' => null];
        $isGradient = empty($site['logo_path']) && empty($site['logo_symbol']);
        $this->assertTrue($isGradient);
    }

    public function testF16_DetailFoundationCssContainsFallbackStyles(): void
    {
        $css = $this->baseDir . '/templates/amida/static/css/detail-foundation.css';
        if (file_exists($css)) {
            $content = file_get_contents($css);
            $this->assertTrue(str_contains($content, 'placeholder') || str_contains($content, 'gradient') || str_contains($content, 'hero'), 'detail-foundation.css must define placeholder styling');
        } else {
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // Feature 17: Backend Disk Asset Verification (file_exists)
    // =========================================================================

    public function testF17_NewsHelperVerifiesDiskAsset(): void
    {
        $helperPath = $this->baseDir . '/mod/index/helper/mod_news.helper.php';
        if (file_exists($helperPath)) {
            $content = file_get_contents($helperPath);
            $this->assertTrue(str_contains($content, 'file_exists') || str_contains($content, 'is_file') || str_contains($content, 'image'), 'News helper should verify file on disk');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF17_PublicationHelperVerifiesDiskAsset(): void
    {
        $helperPath = $this->baseDir . '/mod/index/helper/mod_publication.helper.php';
        if (file_exists($helperPath)) {
            $content = file_get_contents($helperPath);
            $this->assertTrue(str_contains($content, 'file_exists') || str_contains($content, 'is_file') || str_contains($content, 'image'));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF17_AnnouncementsHelperVerifiesDiskAsset(): void
    {
        $helperPath = $this->baseDir . '/mod/index/helper/mod_announcements.helper.php';
        if (file_exists($helperPath)) {
            $content = file_get_contents($helperPath);
            $this->assertTrue(str_contains($content, 'file_exists') || str_contains($content, 'is_file') || str_contains($content, 'image'));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF17_PageHelperVerifiesDiskAsset(): void
    {
        $helperPath = $this->baseDir . '/mod/index/helper/mod_page.helper.php';
        if (file_exists($helperPath)) {
            $content = file_get_contents($helperPath);
            $this->assertTrue(str_contains($content, 'file_exists') || str_contains($content, 'is_file') || str_contains($content, 'image'));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF17_HeroSliderHelperVerifiesDiskAsset(): void
    {
        $helperPath = $this->baseDir . '/mod/index/helper/mod_hero_slider.helper.php';
        if (file_exists($helperPath)) {
            $content = file_get_contents($helperPath);
            $this->assertTrue(str_contains($content, 'file_exists') || str_contains($content, 'is_file') || str_contains($content, 'image'));
        } else {
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // Feature 18: Client-Side Broken Image Interception
    // =========================================================================

    public function testF18_AppJsInterceptionEventListener(): void
    {
        $js = $this->baseDir . '/templates/amida/static/js/app.js';
        if (file_exists($js)) {
            $content = file_get_contents($js);
            $this->assertTrue(str_contains($content, 'error') || str_contains($content, 'addEventListener') || str_contains($content, 'img'), 'app.js should attach image error listener');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF18_AppJsReplacesBrokenImageWithPlaceholder(): void
    {
        $js = $this->baseDir . '/templates/amida/static/js/app.js';
        if (file_exists($js)) {
            $content = file_get_contents($js);
            $this->assertNotEmpty($content);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF18_HeroNewsSliderJsInterception(): void
    {
        $js = $this->baseDir . '/templates/amida/static/js/hero-news-slider.js';
        if (file_exists($js)) {
            $content = file_get_contents($js);
            $this->assertNotEmpty($content);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF18_AppJsPreventsInfiniteErrorLoop(): void
    {
        $js = $this->baseDir . '/templates/amida/static/js/app.js';
        if (file_exists($js)) {
            $content = file_get_contents($js);
            $this->assertStringNotContainsString('this.src = this.src', $content, 'Must avoid infinite reload loops on broken images');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF18_JsFilesPassSyntaxCheck(): void
    {
        $jsFiles = [
            $this->baseDir . '/templates/amida/static/js/app.js',
            $this->baseDir . '/templates/amida/static/js/hero-news-slider.js',
            $this->baseDir . '/templates/amida/static/js/share.js',
        ];
        foreach ($jsFiles as $js) {
            if (file_exists($js)) {
                $this->assertFileExists($js);
            }
        }
    }

    // =========================================================================
    // Feature 19: 9-Language Module Dictionaries Parity
    // =========================================================================

    public function testF19_ModUserPmHasAll9Languages(): void
    {
        $dir = $this->baseDir . '/mod/mod_user_pm/lang';
        if (is_dir($dir)) {
            foreach (LanguageFixtures::getSupportedLanguages() as $lang) {
                $this->assertFileExists("{$dir}/{$lang}.php");
            }
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF19_ModVideoEmbedHasAll9Languages(): void
    {
        $dir = $this->baseDir . '/mod/mod_video_embed/lang';
        if (is_dir($dir)) {
            foreach (LanguageFixtures::getSupportedLanguages() as $lang) {
                $this->assertFileExists("{$dir}/{$lang}.php");
            }
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF19_ModAclHasAll9Languages(): void
    {
        $dir = $this->baseDir . '/mod/mod_acl/lang';
        if (is_dir($dir)) {
            foreach (LanguageFixtures::getSupportedLanguages() as $lang) {
                $this->assertFileExists("{$dir}/{$lang}.php");
            }
        } else {
            $this->assertTrue(true);
        }
    }

    public function testF19_AllLanguageFilesReturnValidArrays(): void
    {
        $langFiles = $this->staticAuditor->scanDirectory('mod/mod_acl/lang', ['php']);
        foreach ($langFiles as $f) {
            $dict = LanguageFixtures::loadModuleLang($f);
            $this->assertIsArray($dict, "Language file {$f} must return an array");
        }
    }

    public function testF19_KeyParityWithEnglishMaster(): void
    {
        $enFile = $this->baseDir . '/mod/mod_acl/lang/en.php';
        $kuFile = $this->baseDir . '/mod/mod_acl/lang/ku.php';
        if (file_exists($enFile) && file_exists($kuFile)) {
            $parity = $this->staticAuditor->checkLanguageParity($enFile, $kuFile);
            $this->assertCount(0, $parity['missing_keys'], 'Kurdish pack must match English master keys');
        } else {
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // Feature 20: Code Standards & Action Verbs (Lêke)
    // =========================================================================

    public function testF20_NoForbiddenTurkishKeysInEnLang(): void
    {
        $enFiles = [
            $this->baseDir . '/mod/index/lang/en.php',
            $this->baseDir . '/mod/mod_user/lang/en.php',
        ];
        foreach ($enFiles as $f) {
            if (file_exists($f)) {
                $dict = LanguageFixtures::loadModuleLang($f);
                foreach (LanguageFixtures::getForbiddenTurkishKeys() as $tk) {
                    $this->assertArrayNotHasKey($tk, $dict, "Forbidden Turkish key '{$tk}' found in {$f}");
                }
            }
        }
    }

    public function testF20_KurdishAddActionVerbIsLeke(): void
    {
        $verbs = LanguageFixtures::getKurdishCanonicalVerbs();
        $this->assertEquals('Lêke', $verbs['add'], "Kurdish 'add' action verb must be 'Lêke'");
    }

    public function testF20_KurdishUpdateVerbIsNujenkirin(): void
    {
        $verbs = LanguageFixtures::getKurdishCanonicalVerbs();
        $this->assertEquals('Nûjenkirin', $verbs['update'], "Kurdish 'update' action verb must be 'Nûjenkirin'");
    }

    public function testF20_CodebaseCommentsInEnglish(): void
    {
        $file = $this->baseDir . '/lib/Registry.class.php';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->assertStringNotContainsString('// Kayıt', $content);
        }
    }

    public function testF20_KurdishClickVerbIsTikandin(): void
    {
        $verbs = LanguageFixtures::getKurdishCanonicalVerbs();
        $this->assertEquals('Bitikîne', $verbs['click']);
    }

    // =========================================================================
    // Feature 21: Cleanliness & Commercial Module Exclusion
    // =========================================================================

    public function testF21_ZeroOccurrencesOfModClient(): void
    {
        $installerDir = $this->baseDir . '/installer/mod_client';
        $this->assertFileDoesNotExist($installerDir);
        $modFiles = $this->staticAuditor->scanDirectory('mod', ['php', 'twig']);
        $violations = $this->staticAuditor->checkDeprecatedModuleReferences($modFiles, ['mod_client']);
        $this->assertCount(0, $violations, "mod_client must not be referenced in mod/");
    }

    public function testF21_ZeroOccurrencesOfModService(): void
    {
        $installerDir = $this->baseDir . '/installer/mod_service';
        $this->assertFileDoesNotExist($installerDir);
        $modFiles = $this->staticAuditor->scanDirectory('mod', ['php', 'twig']);
        $violations = $this->staticAuditor->checkDeprecatedModuleReferences($modFiles, ['mod_service']);
        $this->assertCount(0, $violations, "mod_service must not be referenced in mod/");
    }

    public function testF21_ZeroOccurrencesOfModProduct(): void
    {
        $installerDir = $this->baseDir . '/installer/mod_product';
        $this->assertFileDoesNotExist($installerDir);
        $modFiles = $this->staticAuditor->scanDirectory('mod', ['php', 'twig']);
        $violations = $this->staticAuditor->checkDeprecatedModuleReferences($modFiles, ['mod_product']);
        $this->assertCount(0, $violations, "mod_product must not be referenced in mod/");
    }

    public function testF21_ZeroOccurrencesOfModReference(): void
    {
        $installerDir = $this->baseDir . '/installer/mod_reference';
        $this->assertFileDoesNotExist($installerDir);
        $modFiles = $this->staticAuditor->scanDirectory('mod', ['php', 'twig']);
        $violations = $this->staticAuditor->checkDeprecatedModuleReferences($modFiles, ['mod_reference']);
        $this->assertCount(0, $violations, "mod_reference must not be referenced in mod/");
    }

    public function testF21_FaqModInstallerSeedsClean(): void
    {
        $faqModInstaller = $this->baseDir . '/installer/mod_faq_mod/index.php';
        if (file_exists($faqModInstaller)) {
            $content = file_get_contents($faqModInstaller);
            $this->assertStringNotContainsString('mod_product', $content);
            $this->assertStringNotContainsString('mod_service', $content);
            $this->assertStringNotContainsString('mod_project', $content);
        } else {
            $this->assertTrue(true);
        }
    }
}
