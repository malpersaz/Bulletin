<?php

/**
 * Bootstrap task for mod_socialmedia_post and mod_socialmedia_post_mod
 * on existing installations.
 *
 * Idempotently creates the three database tables, registers both modules
 * in the ACL infrastructure (mods, mod_user_role, group 1 mod_user_perm),
 * and seeds mod_socialmedia_post_mod for existing host modules.
 *
 * Usage: php tasks/bootstrap_socialmedia_post.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'socialmedia-post-bootstrap';

require dirname(__DIR__) . '/init.php';

echo "=== mod_socialmedia_post bootstrap ===\n";

$db = Load::lib('Database');
$pdo = $db->get_connection();
if (!$pdo) {
    fwrite(STDERR, "[FAIL] Database connection unavailable.\n");
    exit(1);
}

$sqlFiles = [
    dirname(__DIR__) . '/installer/mod_socialmedia_post_mod/mod_socialmedia_post_mod.sql',
    dirname(__DIR__) . '/installer/mod_socialmedia_post/mod_socialmedia_post.sql',
    dirname(__DIR__) . '/installer/mod_socialmedia_post/mod_socialmedia_post_link.sql',
];

foreach ($sqlFiles as $sqlFile) {
    if (!is_file($sqlFile)) {
        fwrite(STDERR, "[FAIL] Missing SQL file: {$sqlFile}\n");
        exit(1);
    }
    $sqlContent = trim((string) file_get_contents($sqlFile));
    if ($sqlContent !== '') {
        $pdo->exec($sqlContent);
    }
}

foreach (['mod_socialmedia_post_mod', 'mod_socialmedia_post', 'mod_socialmedia_post_link'] as $tableName) {
    try {
        $db->query("SELECT 1 FROM `{$tableName}` LIMIT 1");
        echo "[OK] table {$tableName}\n";
    } catch (\Throwable $e) {
        fwrite(STDERR, "[FAIL] table {$tableName}: " . $e->getMessage() . "\n");
        exit(1);
    }
}

$roles = ['add', 'edit', 'delete', 'list', 'moderate_edit', 'moderate_delete'];
$nowString = date('Y-m-d H:i:s');

foreach (['mod_socialmedia_post_mod', 'mod_socialmedia_post'] as $moduleName) {
    $modStatement = $pdo->prepare('SELECT `mod_id` FROM `mods` WHERE `mod_name` = ? LIMIT 1');
    $modStatement->execute([$moduleName]);
    $modRow = $modStatement->fetch(\PDO::FETCH_ASSOC);

    if (!$modRow) {
        try {
            $insertMod = $pdo->prepare(
                "INSERT INTO `mods` (`mod_name`, `status`, `schema_ready`, `installed_at`, `updated_at`) VALUES (?, 'ready', 'y', ?, ?)"
            );
            $insertMod->execute([$moduleName, $nowString, $nowString]);
            $modId = (int) $pdo->lastInsertId();
        } catch (\Throwable $e) {
            $insertFallback = $pdo->prepare(
                "INSERT INTO `mods` (`mod_name`, `status`, `schema_ready`) VALUES (?, 'ready', 'y')"
            );
            $insertFallback->execute([$moduleName]);
            $modId = (int) $pdo->lastInsertId();
        }
    } else {
        $modId = (int) $modRow['mod_id'];
    }

    if ($modId <= 0) {
        fwrite(STDERR, "[FAIL] Could not register {$moduleName} in mods.\n");
        exit(1);
    }

    echo "[OK] mods registered: {$moduleName} (mod_id={$modId})\n";

    foreach ($roles as $roleName) {
        $roleStatement = $pdo->prepare('SELECT `role_id` FROM `mod_user_role` WHERE `mod_id` = ? AND `role_name` = ? LIMIT 1');
        $roleStatement->execute([$modId, $roleName]);
        $roleRow = $roleStatement->fetch(\PDO::FETCH_ASSOC);

        if (!$roleRow) {
            $insertRole = $pdo->prepare('INSERT INTO `mod_user_role` (`mod_id`, `role_name`) VALUES (?, ?)');
            $insertRole->execute([$modId, $roleName]);
            $roleId = (int) $pdo->lastInsertId();
        } else {
            $roleId = (int) $roleRow['role_id'];
        }

        if ($roleId > 0) {
            $permStatement = $pdo->prepare('SELECT `id` FROM `mod_user_perm` WHERE `mod_id` = ? AND `role_id` = ? AND `group_id` = 1 LIMIT 1');
            $permStatement->execute([$modId, $roleId]);
            if (!$permStatement->fetch()) {
                $insertPerm = $pdo->prepare('INSERT INTO `mod_user_perm` (`group_id`, `role_id`, `mod_id`) VALUES (1, ?, ?)');
                $insertPerm->execute([$roleId, $modId]);
            }
        }
    }

    echo "[OK] roles and group-1 permissions ensured for {$moduleName}\n";
}

$hostSeeds = [
    ['name' => 'News Social Posts', 'mod_name' => 'mod_news', 'mod_key' => 'id', 'mod_value' => 'name', 'user_id' => 1],
    ['name' => 'Page Social Posts', 'mod_name' => 'mod_page', 'mod_key' => 'id', 'mod_value' => 'name', 'user_id' => 1],
    ['name' => 'Publication Social Posts', 'mod_name' => 'mod_publication', 'mod_key' => 'id', 'mod_value' => 'name', 'user_id' => 1],
    ['name' => 'Event Social Posts', 'mod_name' => 'mod_event', 'mod_key' => 'id', 'mod_value' => 'name', 'user_id' => 1],
    ['name' => 'Announcement Social Posts', 'mod_name' => 'mod_announcements', 'mod_key' => 'id', 'mod_value' => 'name', 'user_id' => 1],
];

$runtime = Load::lib('ModuleRuntime');
foreach ($hostSeeds as $seed) {
    $hostModule = $seed['mod_name'];
    if (!$runtime->tableExists($hostModule)) {
        continue;
    }

    $checkBridge = $pdo->prepare('SELECT `id` FROM `mod_socialmedia_post_mod` WHERE `mod_name` = ? LIMIT 1');
    $checkBridge->execute([$hostModule]);
    if (!$checkBridge->fetch()) {
        $insertBridge = $pdo->prepare(
            'INSERT INTO `mod_socialmedia_post_mod` (`name`, `mod_name`, `mod_key`, `mod_value`, `user_id`) VALUES (?, ?, ?, ?, ?)'
        );
        $insertBridge->execute([$seed['name'], $hostModule, $seed['mod_key'], $seed['mod_value'], $seed['user_id']]);
        echo "[OK] seeded mod_socialmedia_post_mod: {$hostModule}\n";
    } else {
        echo "[OK] existing mod_socialmedia_post_mod: {$hostModule}\n";
    }
}

echo "Done.\n";
exit(0);
