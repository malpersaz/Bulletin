<?php

require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../lib/Req.class.php';
require_once __DIR__ . '/../lib/Helper.class.php';
require_once __DIR__ . '/../lib/Run.class.php';
require_once __DIR__ . '/../lib/Validator.class.php';
require_once __DIR__ . '/../lib/Load.class.php';

echo "=== STARTING RUN & VALIDATOR ADVERSARIAL TEST ===\n";

$assertions = 0;

function check_assert($cond, $msg) {
    global $assertions;
    $assertions++;
    if (!$cond) {
        throw new RuntimeException("Assertion failed: " . $msg);
    }
    echo "  [PASS] $msg\n";
}

// 1. Run::execute Tests
class DummyController {
    public function noArg() { return 'no_args'; }
    public function singleArg($x) { return 'single:' . $x; }
    public function multipleArgs($a, $b, $c) { return "$a|$b|$c"; }
    public function arrayArg(array $arr) { return count($arr); }
}

$controller = new DummyController();

// 1.1 Null / Empty args
check_assert(Run::execute($controller, 'noArg') === 'no_args', "Run::execute with no extra args");
check_assert(Run::execute($controller, 'noArg', null) === 'no_args', "Run::execute with null");
check_assert(Run::execute($controller, 'noArg', []) === 'no_args', "Run::execute with empty array");
check_assert(Run::execute($controller, 'noArg', '') === 'no_args', "Run::execute with empty string");

// 1.2 Positional arguments
check_assert(Run::execute($controller, 'singleArg', 'test') === 'single:test', "Run::execute scalar string argument");
check_assert(Run::execute($controller, 'singleArg', 42) === 'single:42', "Run::execute scalar int argument");
check_assert(Run::execute($controller, 'singleArg', 0) === 'single:0', "Run::execute scalar zero argument");
check_assert(Run::execute($controller, 'singleArg', ['wrapped']) === 'single:wrapped', "Run::execute indexed array argument");
check_assert(Run::execute($controller, 'multipleArgs', [1, 2, 3]) === '1|2|3', "Run::execute 3 positional args");

// 1.3 Associative arguments (array_values unpack)
check_assert(Run::execute($controller, 'multipleArgs', ['p1' => 'alpha', 'p2' => 'beta', 'p3' => 'gamma']) === 'alpha|beta|gamma', "Run::execute assoc array positional unpack");

// 1.4 Array parameter method
check_assert(Run::execute($controller, 'arrayArg', [['a', 'b', 'c']]) === 3, "Run::execute nested array parameter");

// 1.5 Legacy Run::calistir fallback via __callStatic
check_assert(Run::calistir($controller, 'singleArg', 'legacy_call') === 'single:legacy_call', "Run::calistir __callStatic fallback");

// 1.6 RunException on missing method
try {
    Run::execute($controller, 'nonExistentMethod');
    check_assert(false, "Should throw RunException on missing method");
} catch (RunException $e) {
    check_assert($e->mod === 'DummyController' || $e->mod === 'Dummy', "RunException properly populates module: " . $e->mod);
    check_assert($e->method === 'nonExistentMethod', "RunException properly populates method");
}

// 1.7 RunException on missing class string
try {
    Run::execute('NonExistentClassString', 'nonExistentMethod');
    check_assert(false, "Should throw RunException on non existent class");
} catch (RunException $e) {
    check_assert($e->mod === 'NonExistentClassString', "RunException on string class properly populates mod without TypeError");
}

// 1.8 Run::execute with primitive edge cases (boolean, float, negative numbers, object argument)
class EdgeController {
    public function boolArg($b) { return is_bool($b) ? ($b ? 'TRUE' : 'FALSE') : 'NOT_BOOL'; }
    public function floatArg($f) { return is_float($f) ? round($f, 2) : 'NOT_FLOAT'; }
    public function objArg($obj) { return is_object($obj) ? get_class($obj) : 'NOT_OBJ'; }
    public function nullArg($n) { return $n === null ? 'IS_NULL' : 'NOT_NULL'; }
}

$edge = new EdgeController();
check_assert(Run::execute($edge, 'boolArg', false) === 'FALSE', "Run::execute with false boolean argument");
check_assert(Run::execute($edge, 'boolArg', true) === 'TRUE', "Run::execute with true boolean argument");
check_assert(Run::execute($edge, 'floatArg', 3.14159) === 3.14, "Run::execute with float argument");
check_assert(Run::execute($edge, 'objArg', new stdClass()) === 'stdClass', "Run::execute with object argument");
check_assert(Run::execute($edge, 'nullArg', [null]) === 'IS_NULL', "Run::execute with explicit [null] argument array");

// 2. Validator Tests
$validator = new Validator();

// 2.1 Standard add with English func and message
$_REQUEST['test_req'] = 'present';
$validator->add(['name' => 'test_req', 'func' => 'required', 'message' => 'Required test']);
check_assert($validator->check() === false, "Validator passed for present field");
check_assert(count($validator->list_errors()) === 0, "Zero errors for valid field");

// 2.2 Standard add with failing rule
$_REQUEST['test_failing'] = '';
$validator->add(['name' => 'test_failing', 'func' => 'not_empty', 'message' => 'Field cannot be empty']);
check_assert($validator->check() === true, "Validator failed for empty field");
check_assert(count($validator->list_errors()) === 1, "Exactly 1 error captured");
check_assert($validator->list_errors()[0] === 'Field cannot be empty', "Correct error message recorded");

// 2.3 Legacy __call('ekle') with Turkish 'fonk' and 'mesaj'
$v2 = new Validator();
$_REQUEST['test_num'] = 'abc_not_numeric';
$v2->ekle(['name' => 'test_num', 'fonk' => 'num', 'mesaj' => 'Must be numeric']);
check_assert($v2->check() === true, "Validator failed on non-numeric value via ekle");
check_assert(count($v2->list_errors()) === 1, "Exactly 1 error via ekle/fonk/mesaj");
check_assert($v2->list_errors()[0] === 'Must be numeric', "Correct error message recorded via ekle");

// 2.4 Multilingual Unicode Validator
$_REQUEST['kurdish_title'] = 'Rojbaş Kurdistan';
$v3 = new Validator();
$v3->add(['name' => 'kurdish_title', 'func' => 'alpha_unicode', 'message' => 'Invalid unicode text']);
check_assert($v3->check() === false, "Validator passed for Kurdish unicode text");

// 3. Load::validator Fallback Resolution Tests (run in sub-process to avoid un-namespaced class collisions)
$validatorsToTest = [
    'mod_acl.add' => 'add_validator',
    'mod_acl.edit' => 'edit_validator',
    'mod_acl.ekle' => 'add_validator',
    'mod_acl.duzenle' => 'edit_validator',
    'mod_gallery_mod.add' => 'add_validator',
    'mod_gallery_mod.edit' => 'edit_validator',
    'mod_gallery_mod.ekle' => 'add_validator',
    'mod_gallery_mod.duzenle' => 'edit_validator',
    'mod_popup.add' => 'add_validator',
    'mod_popup.edit' => 'edit_validator',
    'mod_headline.add' => 'add_validator',
    'mod_headline.edit' => 'edit_validator',
    'mod_survey.add' => 'add_validator',
    'mod_survey.edit' => 'edit_validator',
];

foreach ($validatorsToTest as $validatorKey => $expectedClass) {
    $code = sprintf(
        'require_once "%s/../init.php"; $v = Load::validator("%s"); exit(($v instanceof %s) ? 0 : 1);',
        __DIR__,
        $validatorKey,
        $expectedClass
    );
    $cmd = 'php -r ' . escapeshellarg($code);
    exec($cmd, $output, $returnCode);
    check_assert($returnCode === 0, "Load::validator('$validatorKey') cleanly instantiates $expectedClass");
}

// 4. Registry Canonical & Legacy Fallback Tests
Registry::add('adversarial_key_1', 'val_initial');
check_assert(Registry::get('adversarial_key_1') === 'val_initial', "Registry::get retrieves added key");
check_assert(Registry::has('adversarial_key_1') === true, "Registry::has returns true for existing key");
check_assert(Registry::has('non_existent_key_xyz') === false, "Registry::has returns false for missing key");
check_assert(Registry::get('non_existent_key_xyz', 'fallback_default') === 'fallback_default', "Registry::get respects default fallback");

Registry::set('adversarial_key_1', 'val_updated');
check_assert(Registry::get('adversarial_key_1') === 'val_updated', "Registry::set updates existing key");

check_assert(is_array(Registry::all()) && isset(Registry::all()['adversarial_key_1']), "Registry::all returns all registered data");

Registry::remove('adversarial_key_1');
check_assert(Registry::has('adversarial_key_1') === false, "Registry::remove deletes key");
check_assert(Registry::get('adversarial_key_1') === null, "Registry::get returns null for removed key");

// 4.1 Registry __callStatic Legacy Fallback Tests
Registry::ekle('legacy_adv_key', 'legacy_val_1');
check_assert(Registry::getir('legacy_adv_key') === 'legacy_val_1', "Registry::getir fallback retrieves key via __callStatic");
check_assert(Registry::varmi('legacy_adv_key') === true, "Registry::varmi fallback detects key via __callStatic");

Registry::yenile('legacy_adv_key', 'legacy_val_updated');
check_assert(Registry::getir('legacy_adv_key') === 'legacy_val_updated', "Registry::yenile fallback updates key via __callStatic");

check_assert(is_array(Registry::hepsi()) && isset(Registry::hepsi()['legacy_adv_key']), "Registry::hepsi fallback returns array via __callStatic");

Registry::sil('legacy_adv_key');
check_assert(Registry::varmi('legacy_adv_key') === false, "Registry::sil fallback removes key via __callStatic");

// 5. Index Module & indexBase Bootstrapping Test (run in isolated process)
$indexBootCode = sprintf(
    'require_once "%s/../init.php"; Registry::set("database", null); $mod = Load::mod("index"); exit(($mod instanceof index_mod && $mod->site["address"] !== "") ? 0 : 1);',
    __DIR__
);
exec('php -r ' . escapeshellarg($indexBootCode), $output, $returnCode);
check_assert($returnCode === 0, "Load::mod('index') and indexBase cleanly bootstrap with Registry without fatal errors");

echo "\n=== SUMMARY: ALL $assertions RUN, VALIDATOR & REGISTRY ADVERSARIAL ASSERTIONS PASSED! ===\n";
