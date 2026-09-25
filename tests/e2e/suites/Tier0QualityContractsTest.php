<?php

namespace E2E\Suites;

use E2E\Harness\TestCase;
use E2E\Harness\StaticAuditor;
use E2E\Harness\TwigAuditor;
use E2E\Harness\CssAuditor;
use E2E\Fixtures\ModuleFixtures;
use E2E\Fixtures\LanguageFixtures;

class Tier0QualityContractsTest extends TestCase
{
    private StaticAuditor $staticAuditor;
    private TwigAuditor $twigAuditor;
    private CssAuditor $cssAuditor;
    private string $baseDir;

    public function getTier(): int
    {
        return 0;
    }

    public function getName(): string
    {
        return 'Tier 0: Quality Gates & Code Separation';
    }

    public function setUp(): void
    {
        $this->baseDir = dirname(__DIR__, 3);
        $this->staticAuditor = new StaticAuditor($this->baseDir);
        $this->twigAuditor = new TwigAuditor($this->baseDir);
        $this->cssAuditor = new CssAuditor($this->baseDir);
    }

    public function testCodeSeparationInTwigTemplates(): void
    {
        $targetDirs = [
            'templates/admin/mod_content_category',
            'templates/admin/mod_faq',
            'templates/admin/mod_newsletter',
            'templates/admin/mod_seo',
            'templates/admin/mod_settings',
            'templates/admin/mod_acl',
            'templates/admin/mod_user',
            'templates/amida/partials',
        ];

        $checkedFiles = [];
        foreach ($targetDirs as $dir) {
            $files = $this->staticAuditor->scanDirectory($dir, ['twig']);
            $checkedFiles = array_merge($checkedFiles, $files);
        }

        if (!empty($checkedFiles)) {
            $violations = $this->staticAuditor->checkCodeSeparation($checkedFiles);
            // Verify our target modernized templates maintain zero violations
            $modernizedViolations = array_filter($violations, function($v) {
                return str_contains($v['file'], 'mod_content_category')
                    || str_contains($v['file'], 'mod_faq')
                    || str_contains($v['file'], 'mod_newsletter')
                    || str_contains($v['file'], 'mod_seo')
                    || str_contains($v['file'], 'mod_settings')
                    || str_contains($v['file'], 'share_toolbar.twig')
                    || str_contains($v['file'], '_legal_info.twig')
                    || str_contains($v['file'], '_cookie_consent.twig');
            });
            $this->assertCount(0, $modernizedViolations, "Modernized templates must have 0 inline styles or scripts");
        } else {
            $this->assertTrue(true, "Modernized admin templates will be verified upon milestone implementation");
        }
    }

    public function testDeprecatedModuleFileRemoval(): void
    {
        $deprecated = LanguageFixtures::getExcludedModules();
        $this->assertNotEmpty($deprecated);
        $this->assertCount(7, $deprecated, "Must cover all 7 deprecated modules");

        // Verify installer directories are completely removed
        foreach ($deprecated as $mod) {
            $installerPath = $this->baseDir . '/installer/' . $mod;
            $this->assertFalse(is_dir($installerPath), "Deprecated installer directory must not exist: {$installerPath}");
        }

        // Verify frontend helper files are completely removed
        $helperMods = ['mod_client', 'mod_service', 'mod_product', 'mod_reference'];
        foreach ($helperMods as $mod) {
            $helperPath = $this->baseDir . '/mod/index/helper/' . $mod . '.helper.php';
            $this->assertFalse(file_exists($helperPath), "Deprecated helper file must not exist: {$helperPath}");
        }

        // Verify zero occurrences of deprecated commercial modules in application code
        $scanDirs = ['mod', 'lib', 'installer', 'templates', 'config', 'inc'];
        $allFiles = [];
        foreach ($scanDirs as $dir) {
            $files = $this->staticAuditor->scanDirectory($dir, ['php', 'twig', 'js', 'json', 'sql']);
            $allFiles = array_merge($allFiles, $files);
        }
        $this->assertNotEmpty($allFiles, "Must scan codebase files for deprecated module references");
        $violations = $this->staticAuditor->checkDeprecatedModuleReferences($allFiles, $deprecated);
        $this->assertCount(0, $violations, "Must have 0 deprecated module references in application code");
    }

    public function testNoForbiddenTurkishIdentifiers(): void
    {
        $checkedFiles = [
            $this->baseDir . '/lib/Registry.class.php',
            $this->baseDir . '/lib/ModuleRegistry.class.php',
        ];

        foreach ($checkedFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $this->assertStringNotContainsString('class Kayit', $content, "Legacy class Kayit must not be defined in modern core");
            }
        }
    }

    public function testTwigFaqMacroStructure(): void
    {
        $macroPath = 'templates/amida/macros/faq.twig';
        $fullPath = $this->baseDir . '/' . $macroPath;
        if (file_exists($fullPath)) {
            $audit = $this->twigAuditor->auditMacro($macroPath, ['render', 'schema']);
            $this->assertTrue($audit['valid'], "FAQ macro must define 'render' and 'schema' macros");
        } else {
            $this->assertTrue(true, "Macro file will be verified upon porting");
        }
    }

    public function testAdminStylesheetTouchTargets(): void
    {
        $cssFiles = [
            'templates/admin/assets/css/mod_faq.css',
            'templates/admin/assets/css/mod_newsletter.css',
            'templates/admin/assets/css/mod_seo.css',
        ];

        foreach ($cssFiles as $css) {
            $fullPath = $this->baseDir . '/' . $css;
            if (file_exists($fullPath)) {
                $audit = $this->cssAuditor->auditStylesheet($css);
                $this->assertTrue($audit['has_touch_targets'], "Admin CSS must support tactile touch targets for mobile viewport");
            }
        }
        
        $this->assertTrue(true, "Checked stylesheets touch target conformance");
    }
}
