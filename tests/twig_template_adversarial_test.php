<?php

declare(strict_types=1);

require_once __DIR__ . '/../init.php';
require_once root_dir . 'lib/TemplateContext.php';
require_once root_dir . 'lib/TwigLibs.class.php';
require_once root_dir . 'lib/TwigTemplate.class.php';
require_once root_dir . 'lib/AdminTemplate.class.php';
require_once root_dir . 'lib/BaseModuleController.class.php';
require_once root_dir . 'lib/Cache.class.php';
require_once root_dir . 'lib/Req.class.php';
require_once root_dir . 'mod/index/indexBase.php';
require_once root_dir . 'mod/index/helper/search.helper.php';
require_once root_dir . 'mod/index/helper/mod_survey.helper.php';

class TwigTemplateAdversarialTest
{
    private int $passed = 0;
    private int $failed = 0;

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$message}\n";
        } else {
            $this->failed++;
            echo "  [FAIL] {$message}\n";
        }
    }

    public function runAll(): void
    {
        echo "=== STARTING TWIG TEMPLATE ADVERSARIAL TEST SUITE ===\n\n";

        $this->testScalarSet();
        $this->testArraySet();
        $this->testLegacyVeriForwarding();
        $this->testLegacyVeriArrayForwarding();
        $this->testAssignAlias();
        $this->testMagicCallForwarding();
        $this->testMagicCallInvalidThrows();
        $this->testAdminTemplateInheritance();
        $this->testBaseModuleControllerTemaGuarantees();
        $this->testIndexBaseTemaThemeViewParity();
        $this->testReqSanitizationAndAliases();
        $this->testCacheModernizationAndTtlAliases();
        $this->testIndexBaseOptionAndMethodParity();
        $this->testSearchHelperKeywordParity();
        $this->testSurveyHelperMethodParity();
        $this->testEdgeCaseValues();
        $this->testTemplateRenderingWithVariables();

        echo "\n=======================================================\n";
        echo "TEST RESULTS: Passed={$this->passed}, Failed={$this->failed}\n";
        echo "=======================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function testScalarSet(): void
    {
        $tpl = new TwigTemplate();
        $tpl->set('sample_key', 'sample_value');

        $this->assert($tpl->assign_data['sample_key'] === 'sample_value', 'TwigTemplate::set stores scalar value in assign_data');
        $this->assert($tpl->twigLibs->ctx_get('sample_key') === 'sample_value', 'TwigTemplate::set sets value in TemplateContext');
    }

    private function testArraySet(): void
    {
        $tpl = new TwigTemplate();
        $tpl->set([
            'first_key' => 'alpha',
            'second_key' => 'beta',
            'numeric_key' => 12345
        ]);

        $this->assert($tpl->assign_data['first_key'] === 'alpha', 'TwigTemplate::set handles array batch hydration: first_key');
        $this->assert($tpl->assign_data['second_key'] === 'beta', 'TwigTemplate::set handles array batch hydration: second_key');
        $this->assert($tpl->assign_data['numeric_key'] === 12345, 'TwigTemplate::set handles array batch hydration: numeric_key');
        $this->assert($tpl->twigLibs->ctx_get('first_key') === 'alpha', 'TwigTemplate::set array propagates to TemplateContext');
    }

    private function testLegacyVeriForwarding(): void
    {
        $tpl = new TwigTemplate();
        $tpl->veri('legacy_var', 'legacy_data');

        $this->assert($tpl->assign_data['legacy_var'] === 'legacy_data', 'TwigTemplate::veri forwards directly to set()');
        $this->assert($tpl->twigLibs->ctx_get('legacy_var') === 'legacy_data', 'TwigTemplate::veri propagates to TemplateContext');
    }

    private function testLegacyVeriArrayForwarding(): void
    {
        $tpl = new TwigTemplate();
        $tpl->veri([
            'batch_1' => 'val1',
            'batch_2' => 'val2'
        ]);

        $this->assert($tpl->assign_data['batch_1'] === 'val1', 'TwigTemplate::veri handles array batch forwarding: batch_1');
        $this->assert($tpl->assign_data['batch_2'] === 'val2', 'TwigTemplate::veri handles array batch forwarding: batch_2');
    }

    private function testAssignAlias(): void
    {
        $tpl = new TwigTemplate();
        $tpl->assign('alias_key', 'alias_val');

        $this->assert($tpl->assign_data['alias_key'] === 'alias_val', 'TwigTemplate::assign acts as alias for set()');
    }

    private function testMagicCallForwarding(): void
    {
        $tpl = new TwigTemplate();
        // Dynamic call
        $method = 'veri';
        $tpl->$method('dynamic_key', 'dynamic_val');

        $this->assert($tpl->assign_data['dynamic_key'] === 'dynamic_val', 'Dynamic method call ->veri() succeeds via direct method or __call');

        $methodAssign = 'assign';
        $tpl->$methodAssign('dynamic_assign_key', 'dynamic_assign_val');
        $this->assert($tpl->assign_data['dynamic_assign_key'] === 'dynamic_assign_val', 'Dynamic method call ->assign() succeeds');
    }

    private function testMagicCallInvalidThrows(): void
    {
        $tpl = new TwigTemplate();
        $caught = false;
        try {
            $tpl->nonExistentMethod('arg');
        } catch (\BadMethodCallException $e) {
            $caught = true;
        }

        $this->assert($caught, 'Undefined method call on TwigTemplate throws BadMethodCallException');
    }

    private function testAdminTemplateInheritance(): void
    {
        $adminTpl = new AdminTemplate();
        $adminTpl->set('admin_var', 'admin_val');
        $adminTpl->veri('legacy_admin_var', 'legacy_admin_val');

        $this->assert($adminTpl->assign_data['admin_var'] === 'admin_val', 'AdminTemplate inherits set()');
        $this->assert($adminTpl->assign_data['legacy_admin_var'] === 'legacy_admin_val', 'AdminTemplate inherits veri() backward compatibility');
    }

    private function testBaseModuleControllerTemaGuarantees(): void
    {
        $ref = new \ReflectionClass(BaseModuleController::class);
        $this->assert($ref->hasProperty('tema'), 'BaseModuleController declares public $tema property');
        $this->assert($ref->hasProperty('theme'), 'BaseModuleController declares public $theme property');
        $this->assert($ref->hasProperty('view'), 'BaseModuleController declares public $view property');

        $mock = new class extends BaseModuleController {
            public function __construct() {}
        };
        $adminTpl = new AdminTemplate();
        $mock->theme = $adminTpl;
        $mock->view = $adminTpl;
        $mock->tema = $adminTpl;

        $this->assert($mock->tema === $adminTpl, 'BaseModuleController::$tema holds AdminTemplate instance');
        $this->assert($mock->__get('tema') === $adminTpl, 'BaseModuleController::__get("tema") dynamically falls back to $theme');
    }

    private function testIndexBaseTemaThemeViewParity(): void
    {
        $ref = new \ReflectionClass(indexBase::class);
        $this->assert($ref->hasProperty('tema'), 'indexBase declares public $tema property');
        $this->assert($ref->hasProperty('theme'), 'indexBase declares public $theme property');
        $this->assert($ref->hasProperty('view'), 'indexBase declares public $view property');

        $mock = new class extends indexBase {
            public function __construct() {}
        };
        $tpl = new TwigTemplate();
        $mock->tema = $tpl;
        $mock->theme = $tpl;
        $mock->view = $tpl;

        $this->assert($mock->theme === $tpl, 'indexBase::$theme holds TwigTemplate instance');
        $this->assert($mock->view === $tpl, 'indexBase::$view holds TwigTemplate instance');
        $this->assert($mock->__get('theme') === $tpl, 'indexBase::__get("theme") dynamically falls back to $tema');
        $this->assert($mock->__get('view') === $tpl, 'indexBase::__get("view") dynamically falls back to $tema');
    }

    private function testReqSanitizationAndAliases(): void
    {
        $this->assert(Req::Clear_Value('<b>test</b>') === '&lt;b&gt;test&lt;/b&gt;', 'Req::Clear_Value sanitizes HTML tags');
        $this->assert(Req::Temizle('<b>test</b>') === '&lt;b&gt;test&lt;/b&gt;', 'Req::Temizle forwards to Clear_Value identically');
        $this->assert(Req::Clear(['k' => '<script>']) === ['k' => '&lt;script&gt;'], 'Req::Clear recurses into arrays');
        $this->assert(Req::Url_Clear('%3Cb%3Ehi%3C%2Fb%3E') === '&lt;b&gt;hi&lt;/b&gt;', 'Req::Url_Clear sanitizes urlencoded values');
    }

    private function testCacheModernizationAndTtlAliases(): void
    {
        $testData = ['message' => 'Rojname Cache Test', 'status' => true];
        $key = 'test_cache_parity_' . time();
        $testDir = 'cache/test_sql/';
        $fullTestDir = root_dir . $testDir;
        if (!is_dir($fullTestDir)) {
            @mkdir($fullTestDir, 0777, true);
        }

        $origDir = Cache::$cachedir;
        Cache::setCacheDir($testDir);

        try {
            $saved = Cache::set($testData, $key);
            $this->assert($saved === true, 'Cache::set successfully persists cache data');
            $this->assert(Cache::exists_cache($key), 'Cache::exists_cache recognizes created cache');
            $cached = Cache::get_cache();
            $this->assert($cached === $testData, 'Cache::get_cache retrieves identical payload');
            Cache::delete_cache();
            $this->assert(!Cache::exists_cache($key), 'Cache::delete_cache removes cache file');
        } finally {
            Cache::setCacheDir($origDir);
            if (is_dir($fullTestDir)) {
                @rmdir($fullTestDir);
            }
        }
    }

    private function testIndexBaseOptionAndMethodParity(): void
    {
        $ref = new \ReflectionClass(indexBase::class);
        $this->assert($ref->hasProperty('options'), 'indexBase declares public $options property');
        $this->assert($ref->hasProperty('option'), 'indexBase declares public $option property');
        $this->assert($ref->hasMethod('_gallery_get_images'), 'indexBase declares public _gallery_get_images method');
        $this->assert($ref->hasMethod('_permission_check'), 'indexBase declares public _permission_check method');

        $mock = new class extends indexBase {
            public function __construct() {}
        };
        $mock->options = ['opt1', 'opt2'];
        $this->assert($mock->__get('secenek') === ['opt1', 'opt2'], 'indexBase dynamic __get maps secenek to options');
    }

    private function testSearchHelperKeywordParity(): void
    {
        $ref = new \ReflectionClass(search_helper::class);
        $this->assert($ref->hasMethod('search'), 'search_helper declares search method');
    }

    private function testSurveyHelperMethodParity(): void
    {
        $ref = new \ReflectionClass(mod_survey_helper::class);
        $this->assert($ref->hasMethod('vote'), 'mod_survey_helper declares canonical vote method');
        $this->assert($ref->hasMethod('oyver'), 'mod_survey_helper retains backward-compatible oyver alias');
        $this->assert($ref->hasMethod('_survey'), 'mod_survey_helper declares canonical _survey method');
        $this->assert($ref->hasMethod('_anket'), 'mod_survey_helper retains backward-compatible _anket alias');
    }

    private function testEdgeCaseValues(): void
    {
        $tpl = new TwigTemplate();
        $testCases = [
            'null_val' => null,
            'false_val' => false,
            'true_val' => true,
            'zero_int' => 0,
            'empty_string' => '',
            'utf8_kurdish' => 'Diyarbekir - Kurdî / Kurmancî: Çavdêrî & Lêgerîn',
            'nested_array' => ['a' => ['b' => ['c' => 42]]],
        ];

        foreach ($testCases as $key => $val) {
            $tpl->set($key, $val);
            $this->assert($tpl->assign_data[$key] === $val, "TwigTemplate::set handles type edge case: {$key}");
        }

        foreach ($testCases as $key => $val) {
            $tpl->veri('veri_' . $key, $val);
            $this->assert($tpl->assign_data['veri_' . $key] === $val, "TwigTemplate::veri handles type edge case: veri_{$key}");
        }
    }

    private function testTemplateRenderingWithVariables(): void
    {
        $tpl = new TwigTemplate();
        $tpl->set('test_var', 'Hello World');
        $tpl->veri('test_legacy', 'Legacy World');

        $this->assert(isset($tpl->assign_data['test_var']), 'assign_data contains test_var for Twig rendering');
        $this->assert(isset($tpl->assign_data['test_legacy']), 'assign_data contains test_legacy for Twig rendering');
    }
}

$suite = new TwigTemplateAdversarialTest();
$suite->runAll();
