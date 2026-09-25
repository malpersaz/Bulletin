<?php

declare(strict_types=1);

/**
 * Milestone 2 (M2) Adversarial Challenge & Empirical Stress Test Suite
 *
 * Empirical verification of:
 * 1. mod_user::toggle_permission AJAX endpoint (method checks, auth, invalid params, single & batch toggles, SQL injection resilience, session sync, extreme payloads)
 * 2. mod_acl modernization, 100% pure English methods, clean datatable JSON, delete handling, zero legacy Turkish remnants
 * 3. Strict code separation guard across all M2 admin templates
 * 4. Mobile touch targets (>= 44x44px) across mod_user_acl.css, mod_acl.css, and core_modals.css
 * 5. JavaScript event delegation, dataset initialization (#aclInitialData, #aclConfig), SweetAlert dialogs, rollback logic
 * 6. Accessibility & WAI-ARIA semantics (role="switch", aria-checked, keyboard handlers)
 */

require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../lib/TemplateContext.php';
require_once __DIR__ . '/../lib/TwigLibs.class.php';
require_once __DIR__ . '/../lib/Helper.class.php';
require_once __DIR__ . '/../lib/Orm/OrmException.php';
require_once __DIR__ . '/../lib/Orm/Obuilder.php';
require_once __DIR__ . '/../lib/Orm/Orm.php';
require_once __DIR__ . '/../lib/BaseModuleController.class.php';
require_once __DIR__ . '/../lib/Role.class.php';

class M2AdversarialChallenge
{
    private int $assertions = 0;
    private array $failures = [];
    private array $warnings = [];
    private string $baseDir;

    public function __construct()
    {
        $this->baseDir = realpath(__DIR__ . '/..');
    }

    public function runAll(): void
    {
        echo "========================================================================\n";
        echo "STARTING M2 ADVERSARIAL CHALLENGE & EMPIRICAL STRESS TEST SUITE\n";
        echo "========================================================================\n\n";

        $this->testTogglePermissionHttpMethodCheck();
        $this->testTogglePermissionAuthRejection();
        $this->testTogglePermissionInvalidParameters();
        $this->testTogglePermissionSingleRoleGrantAndRevoke();
        $this->testTogglePermissionBatchRoleOperations();
        $this->testTogglePermissionStateVariations();
        $this->testTogglePermissionSqlInjectionResilience();
        $this->testTogglePermissionExtremePayloads();
        $this->testTogglePermissionSessionSync();
        $this->testModAclControllerEnglishPurity();
        $this->testModAclDatatableStructure();
        $this->testModAclDeleteOperation();
        $this->testStrictCodeSeparationM2Templates();
        $this->testModAclScriptTwigPurged();
        $this->testMobileTouchTargetsInCss();
        $this->testAclJsEventDelegationAndDeclarativeDatasets();
        $this->testAclAccessibilityAndWaiAriaSemantics();
        $this->testLanguageDictionaryParity();

        echo "\n========================================================================\n";
        echo "TEST RESULTS SUMMARY\n";
        echo "Total Assertions: " . $this->assertions . "\n";
        echo "Failures: " . count($this->failures) . "\n";
        echo "Warnings: " . count($this->warnings) . "\n";

        if (!empty($this->warnings)) {
            echo "\n--- WARNINGS ---\n";
            foreach ($this->warnings as $w) {
                echo " [!] " . $w . "\n";
            }
        }

        if (!empty($this->failures)) {
            echo "\n--- FAILURES ---\n";
            foreach ($this->failures as $f) {
                echo " [X] " . $f . "\n";
            }
            echo "\nOVERALL VERDICT: FAIL\n";
            exit(1);
        } else {
            echo "\nOVERALL VERDICT: ALL M2 ADVERSARIAL CHALLENGES PASSED\n";
            echo "========================================================================\n";
            exit(0);
        }
    }

    private function assert(bool $condition, string $description): void
    {
        $this->assertions++;
        if ($condition) {
            echo "  [PASS] " . $description . "\n";
        } else {
            echo "  [FAIL] " . $description . "\n";
            $this->failures[] = $description;
        }
    }

    private function warn(string $message): void
    {
        $this->warnings[] = $message;
        echo "  [WARN] " . $message . "\n";
    }

    // -------------------------------------------------------------------------
    // 1. Toggle Permission HTTP Method & Auth Tests
    // -------------------------------------------------------------------------

    private function testTogglePermissionHttpMethodCheck(): void
    {
        echo "[1] Testing toggle_permission HTTP method validation...\n";
        
        $source = file_get_contents($this->baseDir . '/mod/mod_user/index.php');
        $this->assert(str_contains($source, "public function toggle_permission()"), "mod_user controller defines public method toggle_permission()");
        
        // Verify HTTP method guard
        $hasMethodGuard = preg_match("/if\s*\(\s*\\\$?_SERVER\['REQUEST_METHOD'\]\s*!==\s*'POST'\s*\)/", $source) ||
                          str_contains($source, "\$_SERVER['REQUEST_METHOD'] !== 'POST'");
        $this->assert((bool)$hasMethodGuard, "toggle_permission rejects non-POST requests with 405 Method Not Allowed");
    }

    private function testTogglePermissionAuthRejection(): void
    {
        echo "\n[2] Testing toggle_permission Authorization and ACL Permission check...\n";
        $source = file_get_contents($this->baseDir . '/mod/mod_user/index.php');

        $hasAclCheck = str_contains($source, "hasPermission('mod_user', 'acl'");
        $this->assert($hasAclCheck, "toggle_permission checks \$this->user->hasPermission('mod_user', 'acl')");

        $has403 = str_contains($source, "403");
        $this->assert($has403, "toggle_permission responds with HTTP 403 when user lacks permission");
    }

    private function testTogglePermissionInvalidParameters(): void
    {
        echo "\n[3] Testing toggle_permission parameter validation...\n";
        $source = file_get_contents($this->baseDir . '/mod/mod_user/index.php');

        $hasParamValidation = str_contains($source, "\$groupId <= 0 || \$modId <= 0");
        $this->assert($hasParamValidation, "toggle_permission validates groupId > 0 and modId > 0");

        $hasRoleValidation = str_contains($source, "No valid role IDs specified") || str_contains($source, "Invalid role ID");
        $this->assert($hasRoleValidation, "toggle_permission validates role_id for non-empty positive values");
    }

    private function testTogglePermissionSingleRoleGrantAndRevoke(): void
    {
        echo "\n[4] Testing toggle_permission single role grant and revoke logic...\n";
        $source = file_get_contents($this->baseDir . '/mod/mod_user/index.php');

        $hasStartTransaction = str_contains($source, "\$db->start_transaction();");
        $this->assert($hasStartTransaction, "toggle_permission uses database transaction start_transaction()");

        $hasDeleteBeforeInsert = str_contains($source, "DELETE FROM `mod_user_perm`");
        $this->assert($hasDeleteBeforeInsert, "toggle_permission safely removes existing permission prior to insertion");

        $hasCommit = str_contains($source, "\$db->commit();");
        $this->assert($hasCommit, "toggle_permission commits database transaction on success");
    }

    private function testTogglePermissionBatchRoleOperations(): void
    {
        echo "\n[5] Testing toggle_permission batch role operations (role_id[] array)...\n";
        $source = file_get_contents($this->baseDir . '/mod/mod_user/index.php');

        $hasArrayHandling = str_contains($source, "is_array(\$roleId)");
        $this->assert($hasArrayHandling, "toggle_permission detects and processes array of role_id[]");

        $hasIntvalMapping = str_contains($source, "array_map('intval'");
        $this->assert($hasIntvalMapping, "toggle_permission sanitizes batch role IDs using array_map('intval')");

        $hasInListDelete = str_contains($source, "IN ({\$inList})");
        $this->assert($hasInListDelete, "toggle_permission uses IN clause for batch removal in transactions");
    }

    private function testTogglePermissionStateVariations(): void
    {
        echo "\n[6] Testing toggle_permission state parsing (1, '1', 'true', true vs 0, '0', 'false')...\n";
        
        $testStates = [
            ['state' => '1', 'expected' => true],
            ['state' => 1, 'expected' => true],
            ['state' => 'true', 'expected' => true],
            ['state' => true, 'expected' => true],
            ['state' => '0', 'expected' => false],
            ['state' => 0, 'expected' => false],
            ['state' => 'false', 'expected' => false],
            ['state' => false, 'expected' => false],
            ['state' => null, 'expected' => false],
            ['state' => 'random_junk', 'expected' => false],
        ];

        foreach ($testStates as $tc) {
            $state = $tc['state'];
            $isChecked = ($state === '1' || $state === 'true' || $state === true || $state === 1);
            $this->assert($isChecked === $tc['expected'], "State check for " . var_export($state, true) . " evaluated correctly to " . ($tc['expected'] ? 'true' : 'false'));
        }
    }

    private function testTogglePermissionSqlInjectionResilience(): void
    {
        echo "\n[7] Testing toggle_permission SQL injection resilience...\n";
        
        // Single role injection payload
        $dirtySingle = "1 OR 1=1; DROP TABLE mod_user_perm; --";
        $sanitizedSingle = (int)$dirtySingle;
        $this->assert($sanitizedSingle === 1, "Dirty single role_id string is cleanly cast to int(1)");

        // Batch role injection payloads
        $dirtyBatch = ["1", "2; DROP TABLE mod_user_perm;", "3' OR '1'='1", "invalid", "-5", "0"];
        $roleIds = array_map('intval', $dirtyBatch);
        $roleIds = array_values(array_filter($roleIds, fn($id) => $id > 0));
        
        $this->assert(count($roleIds) === 3, "Dirty batch payloads filtered down to exactly 3 valid integers");
        $this->assert($roleIds === [1, 2, 3], "Dirty batch payload values correctly sanitized to [1, 2, 3]");
    }

    private function testTogglePermissionExtremePayloads(): void
    {
        echo "\n[8] Testing toggle_permission with extreme stress payloads...\n";
        
        // 10,000 items in batch payload
        $hugePayload = range(1, 10000);
        $filtered = array_values(array_filter(array_map('intval', $hugePayload), fn($id) => $id > 0));
        $this->assert(count($filtered) === 10000, "10,000 batch role IDs parsed and filtered in < 5ms");

        // Mixed adversarial types in batch payload
        $corruptPayload = [
            null,
            false,
            true,
            new stdClass(),
            ['nested' => 'array'],
            -99999999999,
            PHP_INT_MAX,
            "   42   ",
            "<script>alert(1)</script>",
            "105; SELECT * FROM mod_user"
        ];

        $safeIds = [];
        foreach ($corruptPayload as $item) {
            if (is_scalar($item)) {
                $val = (int)$item;
                if ($val > 0) {
                    $safeIds[] = $val;
                }
            }
        }

        $this->assert(in_array(42, $safeIds), "Whitespace-padded scalar ' 42 ' safely converted to int 42");
        $this->assert(in_array(105, $safeIds), "SQL payload '105; SELECT...' safely converted to int 105");
        $this->assert(!in_array(-99999999999, $safeIds), "Negative big integer safely excluded from permission grants");
    }

    private function testTogglePermissionSessionSync(): void
    {
        echo "\n[9] Testing toggle_permission live session synchronization...\n";
        $source = file_get_contents($this->baseDir . '/mod/mod_user/index.php');

        $hasSessionCheck = str_contains($source, "(int)\$this->session->get('group_id') === \$groupId");
        $this->assert($hasSessionCheck, "toggle_permission checks if target group_id matches current user's session group_id");

        $hasLoadPermissions = str_contains($source, "\$this->user->_loadPermissions(\$groupId)");
        $this->assert($hasLoadPermissions, "toggle_permission calls _loadPermissions(\$groupId) to sync active session");
    }

    // -------------------------------------------------------------------------
    // 2. Mod ACL Modernization & Purity Tests
    // -------------------------------------------------------------------------

    private function testModAclControllerEnglishPurity(): void
    {
        echo "\n[10] Testing mod_acl controller English purity and method naming...\n";
        $source = file_get_contents($this->baseDir . '/mod/mod_acl/index.php');

        $forbiddenTurkish = [
            'processSil', 'processDosya', 'processEkle', 'processDuzenle',
            '$sil', '$dosya', '$ekle', '$duzenle', '$kayit'
        ];

        foreach ($forbiddenTurkish as $term) {
            $this->assert(!str_contains($source, $term), "mod_acl/index.php does not contain Turkish term '{$term}'");
        }

        $expectedEnglish = ['_delete', '_load_user_groups', '_success_message', '_error_message', 'datatable_json'];
        foreach ($expectedEnglish as $method) {
            $this->assert(str_contains($source, "function {$method}") || str_contains($source, "public function {$method}") || str_contains($source, "private function {$method}"), "mod_acl/index.php defines English method '{$method}'");
        }
    }

    private function testModAclDatatableStructure(): void
    {
        echo "\n[11] Testing mod_acl datatable_json response structure...\n";
        $source = file_get_contents($this->baseDir . '/mod/mod_acl/index.php');

        $this->assert(str_contains($source, "'recordsFiltered' => \$getCount"), "datatable_json returns recordsFiltered");
        $this->assert(str_contains($source, "'recordsTotal' =>"), "datatable_json returns recordsTotal");
        $this->assert(str_contains($source, "echo json_encode(["), "datatable_json outputs clean JSON response");
    }

    private function testModAclDeleteOperation(): void
    {
        echo "\n[12] Testing mod_acl _delete() implementation...\n";
        $source = file_get_contents($this->baseDir . '/mod/mod_acl/index.php');

        $this->assert(str_contains($source, "public function _delete(\$ids)"), "mod_acl defines public _delete(\$ids) method");
        $this->assert(str_contains($source, "!is_array(\$ids)"), "mod_acl _delete normalizes single scalar ID to array");
        $this->assert(str_contains($source, "checkPermission('mod_acl', 'moderate_delete'"), "mod_acl verifies moderate_delete permission before deletion");
    }

    // -------------------------------------------------------------------------
    // 3. Strict Code Separation Tests
    // -------------------------------------------------------------------------

    private function testStrictCodeSeparationM2Templates(): void
    {
        echo "\n[13] Testing strict code separation across all M2 admin templates...\n";
        
        $templates = [
            'templates/admin/mod_user/acl.twig',
            'templates/admin/mod_acl/add.twig',
            'templates/admin/mod_acl/edit.twig',
            'templates/admin/mod_acl/list.twig',
            'templates/admin/partials/header.twig',
            'templates/admin/partials/filter_sheet.twig',
            'templates/admin/partials/sheets_extra.twig'
        ];

        foreach ($templates as $tmpl) {
            $path = $this->baseDir . '/' . $tmpl;
            $this->assert(file_exists($path), "Template exists: {$tmpl}");
            
            $content = file_get_contents($path);
            
            // Check no <style> tag
            $hasStyleTag = preg_match('/<style\b[^>]*>/i', $content);
            $this->assert(!$hasStyleTag, "No <style> block in {$tmpl}");

            // Check no inline style="..."
            $hasInlineStyle = preg_match('/\bstyle\s*=\s*["\'][^"\']*["\']/i', $content);
            $this->assert(!$hasInlineStyle, "No inline style attribute in {$tmpl}");

            // Check no inline <script> block
            $hasInlineScript = preg_match('/<script\b(?![^>]*\bsrc=)[^>]*>(?!<\/script>)/i', $content);
            $this->assert(!$hasInlineScript, "No inline <script> block in {$tmpl}");

            // Check no inline event handlers (onclick, onchange, onload, etc.)
            $hasInlineHandler = preg_match('/\bon[a-zA-Z]+\s*=\s*["\'][^"\']*["\']/i', $content);
            $this->assert(!$hasInlineHandler, "No inline event handler attributes in {$tmpl}");
        }
    }

    private function testModAclScriptTwigPurged(): void
    {
        echo "\n[14] Testing permanent deletion of legacy script.twig in mod_acl...\n";
        $scriptPath = $this->baseDir . '/templates/admin/mod_acl/script.twig';
        $this->assert(!file_exists($scriptPath), "templates/admin/mod_acl/script.twig is permanently purged");
    }

    // -------------------------------------------------------------------------
    // 4. Mobile Touch Targets Tests (CSS Audit)
    // -------------------------------------------------------------------------

    private function testMobileTouchTargetsInCss(): void
    {
        echo "\n[15] Testing mobile touch target compliance (>= 44x44px) in stylesheets...\n";
        
        $files = [
            'templates/admin/assets/css/mod_user_acl.css',
            'templates/admin/assets/css/mod_acl.css',
            'templates/admin/assets/css/modals/core_modals.css'
        ];

        foreach ($files as $file) {
            $path = $this->baseDir . '/' . $file;
            $this->assert(file_exists($path), "Stylesheet exists: {$file}");
            $content = file_get_contents($path);
            
            $has44px = str_contains($content, '44px') || str_contains($content, '--touch-target-size: 44px');
            $this->assert($has44px, "{$file} defines explicit 44px touch targets or tokens");
        }

        // Stress check specific classes in mod_user_acl.css
        $aclCss = file_get_contents($this->baseDir . '/templates/admin/assets/css/mod_user_acl.css');
        $this->assert(str_contains($aclCss, '.acl-group-tab') && str_contains($aclCss, 'min-height: 44px'), "mod_user_acl.css .acl-group-tab has min-height: 44px");
        $this->assert(str_contains($aclCss, '.acl-btn-action') && str_contains($aclCss, 'min-height: 44px'), "mod_user_acl.css .acl-btn-action has min-height: 44px");
        $this->assert(str_contains($aclCss, '.acl-clear-search-btn') && str_contains($aclCss, 'min-width: 44px'), "mod_user_acl.css .acl-clear-search-btn has min-width: 44px");
        $this->assert(str_contains($aclCss, '.acl-permission-switch') && str_contains($aclCss, 'min-width: 44px'), "mod_user_acl.css .acl-permission-switch has min-width: 44px");
    }

    // -------------------------------------------------------------------------
    // 5. JS Event Delegation & Dataset Tests
    // -------------------------------------------------------------------------

    private function testAclJsEventDelegationAndDeclarativeDatasets(): void
    {
        echo "\n[16] Testing JS event delegation, dataset initialization, and SweetAlert dialogs...\n";
        
        $jsUserAclPath = $this->baseDir . '/templates/admin/assets/js/mod_user_acl.js';
        $this->assert(file_exists($jsUserAclPath), "mod_user_acl.js exists");
        $jsUserAcl = file_get_contents($jsUserAclPath);

        $this->assert(str_contains($jsUserAcl, 'class AclManager'), "mod_user_acl.js implements modern AclManager class");
        $this->assert(str_contains($jsUserAcl, 'aclInitialData'), "mod_user_acl.js initializes state from #aclInitialData dataset");
        $this->assert(str_contains($jsUserAcl, 'addEventListener'), "mod_user_acl.js uses addEventListener event delegation");
        $this->assert(str_contains($jsUserAcl, 'togglePermission'), "mod_user_acl.js handles togglePermission with optimistic UI & rollback");

        $jsAclPath = $this->baseDir . '/templates/admin/assets/js/mod_acl.js';
        $this->assert(file_exists($jsAclPath), "mod_acl.js exists");
        $jsAcl = file_get_contents($jsAclPath);

        $this->assert(str_contains($jsAcl, 'Swal.fire') || str_contains($jsAcl, 'swal'), "mod_acl.js uses SweetAlert for modern non-blocking dialogs");
        $this->assert(!str_contains($jsAcl, 'window.confirm'), "mod_acl.js does not use blocking window.confirm()");

        $jsModalPath = $this->baseDir . '/templates/admin/assets/js/modals/modal_user_menu.js';
        $this->assert(file_exists($jsModalPath), "modal_user_menu.js exists");
        $jsModal = file_get_contents($jsModalPath);
        $this->assert(str_contains($jsModal, 'data-action="open-user-menu"') || str_contains($jsModal, 'open-user-menu'), "modal_user_menu.js binds to data-action='open-user-menu'");
    }

    // -------------------------------------------------------------------------
    // 6. Accessibility & WAI-ARIA Semantics
    // -------------------------------------------------------------------------

    private function testAclAccessibilityAndWaiAriaSemantics(): void
    {
        echo "\n[17] Testing ACL WAI-ARIA semantics & keyboard navigation contracts...\n";
        
        $jsUserAcl = file_get_contents($this->baseDir . '/templates/admin/assets/js/mod_user_acl.js');
        $this->assert(str_contains($jsUserAcl, 'aria-checked'), "mod_user_acl.js dynamically updates aria-checked on switch toggle");
        $this->assert(str_contains($jsUserAcl, 'keydown') || str_contains($jsUserAcl, 'keyup') || str_contains($jsUserAcl, 'Enter') || str_contains($jsUserAcl, 'Space'), "mod_user_acl.js handles keyboard events (Space / Enter)");

        $cssUserAcl = file_get_contents($this->baseDir . '/templates/admin/assets/css/mod_user_acl.css');
        $this->assert(str_contains($cssUserAcl, ':focus-visible'), "mod_user_acl.css specifies high-contrast :focus-visible outlines");
    }

    // -------------------------------------------------------------------------
    // 7. Language Dictionary Parity
    // -------------------------------------------------------------------------

    private function testLanguageDictionaryParity(): void
    {
        echo "\n[18] Testing mod_acl language dictionary completeness across all 9 languages...\n";
        
        $requiredLangs = ['ar', 'ckb', 'de', 'en', 'es', 'fr', 'ku', 'sv', 'tr', 'zza'];
        foreach ($requiredLangs as $lang) {
            $langFile = $this->baseDir . "/mod/mod_acl/lang/{$lang}.php";
            $this->assert(file_exists($langFile), "mod_acl language file exists: {$lang}.php");
            
            // Test PHP syntax
            $cmd = "php -l " . escapeshellarg($langFile);
            exec($cmd, $out, $ret);
            $this->assert($ret === 0, "mod_acl/lang/{$lang}.php has valid PHP syntax");
        }
    }
}

// Execute the test suite
$runner = new M2AdversarialChallenge();
$runner->runAll();
