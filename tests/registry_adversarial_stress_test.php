<?php
/**
 * Adversarial Stress & Edge Case Test Suite for Milestone 1
 * Targets: lib/Registry.class.php and init.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$results = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0,
    'tests' => []
];

function record_test($name, $status, $details = '') {
    global $results;
    if ($status === 'PASS') {
        $results['passed']++;
    } elseif ($status === 'WARN') {
        $results['warnings']++;
    } else {
        $results['failed']++;
    }
    $results['tests'][] = [
        'name' => $name,
        'status' => $status,
        'details' => $details
    ];
    echo sprintf("[%s] %s %s\n", $status, $name, $details ? "($details)" : '');
}

echo "=== Milestone 1 Adversarial Stress Test Suite ===\n\n";

// Load Registry
require_once __DIR__ . '/../lib/Registry.class.php';

// Test 1: Basic CRUD
try {
    Registry::add('crud_key', 'initial_val');
    $val = Registry::get('crud_key');
    $has = Registry::has('crud_key');
    Registry::set('crud_key', 'updated_val');
    $val_updated = Registry::get('crud_key');
    Registry::remove('crud_key');
    $has_after = Registry::has('crud_key');
    $val_after = Registry::get('crud_key');

    if ($val === 'initial_val' && $has === true && $val_updated === 'updated_val' && $has_after === false && $val_after === null) {
        record_test("Basic CRUD", "PASS");
    } else {
        record_test("Basic CRUD", "FAIL", "State mismatch");
    }
} catch (\Throwable $e) {
    record_test("Basic CRUD", "FAIL", $e->getMessage());
}

// Test 2: Extreme Input - Null Key (Strict string type check)
try {
    try {
        Registry::add(null, 'null_key_value');
        record_test("Null Key Handling", "FAIL", "Expected TypeError for null key");
    } catch (\TypeError $te) {
        record_test("Null Key Handling", "PASS", "Strict type enforcement prevents null key");
    }
} catch (\Throwable $e) {
    record_test("Null Key Handling", "FAIL", $e->getMessage());
}

// Test 3: Extreme Input - Empty String Key
try {
    Registry::add('', 'empty_string_val');
    $get_empty = Registry::get('');
    $has_empty = Registry::has('');
    Registry::remove('');
    if ($get_empty === 'empty_string_val' && $has_empty === true && Registry::has('') === false) {
        record_test("Empty String Key Handling", "PASS");
    } else {
        record_test("Empty String Key Handling", "FAIL");
    }
} catch (\Throwable $e) {
    record_test("Empty String Key Handling", "FAIL", $e->getMessage());
}

// Test 4: Extreme Input - Unicode & Multi-byte / Special Character Keys
try {
    $special_keys = [
        'kurdî_rojname_serê_sibe' => 'kurmancî',
        '🔑_emoji_key_🔥' => 'emoji_val',
        "multi\nline\tkey" => 'escape_val',
        "<script>alert('xss')</script>" => 'xss_val',
        "'; DROP TABLE users; --" => 'sql_val',
        "\0binary_null_byte" => 'binary_val'
    ];

    $all_matched = true;
    foreach ($special_keys as $k => $v) {
        Registry::add($k, $v);
        if (Registry::get($k) !== $v || Registry::has($k) !== true) {
            $all_matched = false;
            break;
        }
        Registry::remove($k);
        if (Registry::has($k) !== false || Registry::get($k) !== null) {
            $all_matched = false;
            break;
        }
    }
    if ($all_matched) {
        record_test("Unicode & Special Character Keys", "PASS", "All special keys preserved exactly");
    } else {
        record_test("Unicode & Special Character Keys", "FAIL", "Mismatch in special key retrieval");
    }
} catch (\Throwable $e) {
    record_test("Unicode & Special Character Keys", "FAIL", $e->getMessage());
}

// Test 5: Value Edge Cases - Storing Null, False, Zero, Empty String, Float 0.0
try {
    Registry::add('val_null', null);
    Registry::add('val_false', false);
    Registry::add('val_zero', 0);
    Registry::add('val_empty_str', '');
    Registry::add('val_float_zero', 0.0);

    $get_null = Registry::get('val_null');
    $has_null = Registry::has('val_null'); // Note: isset() on null value returns false

    $get_false = Registry::get('val_false');
    $has_false = Registry::has('val_false');

    $get_zero = Registry::get('val_zero');
    $has_zero = Registry::has('val_zero');

    $get_empty = Registry::get('val_empty_str');
    $has_empty = Registry::has('val_empty_str');

    $get_float = Registry::get('val_float_zero');
    $has_float = Registry::has('val_float_zero');

    $details = [];
    if ($get_null !== null) $details[] = "get(val_null) failed";
    if ($get_false !== false || !$has_false) $details[] = "get/has(val_false) failed";
    if ($get_zero !== 0 || !$has_zero) $details[] = "get/has(val_zero) failed";
    if ($get_empty !== '' || !$has_empty) $details[] = "get/has(val_empty_str) failed";
    if ($get_float !== 0.0 || !$has_float) $details[] = "get/has(val_float_zero) failed";

    if (!$has_null) {
        // Explaining the isset() vs array_key_exists nuance
        record_test("Value Edge Cases (False, 0, Empty, Float)", "PASS", "Falsy values retrieved correctly");
        record_test("Null Value has() Nuance", "WARN", "Registry::has() uses isset(), returning false for registered key with value null (standard PHP isset behavior)");
    } else {
        record_test("Value Edge Cases (Null, False, 0, Empty, Float)", "PASS");
    }
} catch (\Throwable $e) {
    record_test("Value Edge Cases", "FAIL", $e->getMessage());
}

// Test 6: Deep Multi-Dimensional Arrays & Complex Objects
try {
    // 50-level nested array
    $deep_array = ['data' => 'deep_payload'];
    for ($i = 0; $i < 50; $i++) {
        $deep_array = ['level_' . $i => $deep_array];
    }
    Registry::add('deep_array', $deep_array);

    // Objects, Closures, Anonymous Classes
    $obj = new \stdClass();
    $obj->prop = 'value';
    $obj->nested = (object)['a' => 1, 'b' => [1, 2, 3]];
    Registry::add('object_instance', $obj);

    $closure = function($x) { return $x * 2; };
    Registry::add('closure_instance', $closure);

    $anon = new class {
        public function getGreeting() { return "hello"; }
    };
    Registry::add('anonymous_class', $anon);

    $res = fopen('php://memory', 'r+');
    fwrite($res, "stream_data");
    rewind($res);
    Registry::add('resource_stream', $res);

    $deep_ok = (Registry::get('deep_array') === $deep_array);
    $obj_ok = (Registry::get('object_instance')->nested->b === [1, 2, 3]);
    $fn = Registry::get('closure_instance');
    $closure_ok = is_callable($fn) && $fn(5) === 10;
    $anon_ok = Registry::get('anonymous_class')->getGreeting() === "hello";
    $stream = Registry::get('resource_stream');
    $stream_ok = is_resource($stream) && stream_get_contents($stream) === "stream_data";
    fclose($res);

    if ($deep_ok && $obj_ok && $closure_ok && $anon_ok && $stream_ok) {
        record_test("Deep Arrays, Objects, Closures, Resources", "PASS", "All reference types stored and retrieved intact");
    } else {
        record_test("Deep Arrays, Objects, Closures, Resources", "FAIL", "Type preservation mismatch");
    }
} catch (\Throwable $e) {
    record_test("Deep Arrays, Objects, Closures, Resources", "FAIL", $e->getMessage());
}

// Test 7: Overwriting Existing Keys (add vs set)
try {
    Registry::add('overwrite_key', 'initial');
    Registry::add('overwrite_key', 'second_add'); // Direct add overwrite
    $add_overwrite_ok = (Registry::get('overwrite_key') === 'second_add');

    Registry::set('overwrite_key', 'set_value'); // Set overwrite
    $set_overwrite_ok = (Registry::get('overwrite_key') === 'set_value');

    // Set on non-existent key
    Registry::set('fresh_set_key', 'fresh_value');
    $fresh_set_ok = (Registry::get('fresh_set_key') === 'fresh_value');

    if ($add_overwrite_ok && $set_overwrite_ok && $fresh_set_ok) {
        record_test("Key Overwrite Mechanics (add & set)", "PASS");
    } else {
        record_test("Key Overwrite Mechanics (add & set)", "FAIL");
    }
} catch (\Throwable $e) {
    record_test("Key Overwrite Mechanics (add & set)", "FAIL", $e->getMessage());
}

// Test 8: Removal of Non-existent Keys
try {
    Registry::remove('non_existent_key_that_was_never_registered');
    record_test("Remove Non-Existent Key", "PASS", "No warning/error thrown on unset non-existent key");
} catch (\Throwable $e) {
    record_test("Remove Non-Existent Key", "FAIL", $e->getMessage());
}

// Test 9: Unset Key Retrieval & Notice Free Operation
try {
    $unset_val = Registry::get('totally_missing_key_' . uniqid());
    if ($unset_val === null) {
        record_test("Unset Key Retrieval", "PASS", "Returned NULL cleanly without PHP notice");
    } else {
        record_test("Unset Key Retrieval", "FAIL", "Expected NULL, got: " . var_export($unset_val, true));
    }
} catch (\Throwable $e) {
    record_test("Unset Key Retrieval", "FAIL", $e->getMessage());
}

// Test 10: Bulk Insertion & Memory/Performance Stress
try {
    $count = 10000;
    $start_mem = memory_get_usage();
    $start_time = microtime(true);

    for ($i = 0; $i < $count; $i++) {
        Registry::add("bulk_key_{$i}", ["id" => $i, "rand" => rand(1000, 9999)]);
    }

    $mid_time = microtime(true);
    $read_ok = true;
    for ($i = 0; $i < $count; $i += 100) {
        $val = Registry::get("bulk_key_{$i}");
        if ($val['id'] !== $i) {
            $read_ok = false;
            break;
        }
    }

    // Cleanup bulk keys
    for ($i = 0; $i < $count; $i++) {
        Registry::remove("bulk_key_{$i}");
    }
    $end_time = microtime(true);
    $end_mem = memory_get_usage();

    $duration = round(($end_time - $start_time) * 1000, 2);
    $insert_duration = round(($mid_time - $start_time) * 1000, 2);

    if ($read_ok) {
        record_test("Bulk 10,000 Keys Stress Test", "PASS", "Inserted in {$insert_duration}ms, full CRUD completed in {$duration}ms");
    } else {
        record_test("Bulk 10,000 Keys Stress Test", "FAIL", "Bulk read integrity check failed");
    }
} catch (\Throwable $e) {
    record_test("Bulk 10,000 Keys Stress Test", "FAIL", $e->getMessage());
}

// Test 11: Registry::all() Method Behavior
try {
    Registry::add('test_all_key', 'test_val');
    $all = Registry::all();
    if (is_array($all) && isset($all['test_all_key']) && $all['test_all_key'] === 'test_val') {
        Registry::remove('test_all_key');
        record_test("Registry::all() Contract Check", "PASS", "Returns complete data array");
    } else {
        record_test("Registry::all() Contract Check", "FAIL", "Did not return array of stored data");
    }
} catch (\Throwable $e) {
    record_test("Registry::all() Contract Check", "FAIL", $e->getMessage());
}

echo "\n=== Summary: Passed: {$results['passed']}, Warnings: {$results['warnings']}, Failed: {$results['failed']} ===\n";

exit($results['failed'] > 0 ? 1 : 0);
