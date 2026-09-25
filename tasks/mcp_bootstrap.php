<?php

/**
 * MCP module bootstrap for EXISTING installations.
 * Registers mod_mcp in the ACL infrastructure (mods table, roles, group-1
 * permissions) and creates the MCP tables, mirroring the installer flow.
 *
 * Run once on a deployed site:  php tasks/mcp_bootstrap.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'mcp-bootstrap';

require dirname(__DIR__) . '/init.php';
ob_start();

echo "=== MCP module bootstrap ===\n";

// 1. Ensure mod_mcp infrastructure (creates mod_mcp_api_key table, mods entry, roles, perms)
Load::mod('mod_mcp');

// 2. MCP OAuth tables
Load::libfile('McpOAuth');
McpOAuth::ensureTables();

$db = Load::lib('Database');
foreach (['mod_mcp_api_key', 'mod_mcp_oauth_client', 'mod_mcp_oauth_code', 'mod_mcp_oauth_token'] as $table) {
    try {
        $db->query("SELECT 1 FROM `{$table}` LIMIT 1");
        echo "[OK] table {$table}\n";
    } catch (\Throwable $e) {
        echo "[FAIL] table {$table}: " . $e->getMessage() . "\n";
    }
}

// 3. mods entry verification
$db->fetch_array("SELECT mod_id FROM mods WHERE mod_name = 'mod_mcp' LIMIT 1");
$registered = $db->getSize() !== 0;
echo $registered ? "[OK] mod_mcp registered in mods table\n" : "[FAIL] mod_mcp NOT registered\n";

$db->fetch_array(
    "SELECT r.role_name FROM mod_user_role r
     JOIN mods m ON m.mod_id = r.mod_id
     WHERE m.mod_name = 'mod_mcp'"
);
$roles = array_column($db->toArray() ?: [], 'role_name');
echo "[OK] roles installed: " . (implode(', ', $roles) ?: 'none') . "\n";

$db->fetch_array(
    "SELECT p.group_id FROM mod_user_perm p
     JOIN mods m ON m.mod_id = p.mod_id
     WHERE m.mod_name = 'mod_mcp'"
);
$groups = array_unique(array_column($db->toArray() ?: [], 'group_id'));
echo "[OK] permissions granted to group(s): " . (implode(', ', $groups) ?: 'none') . "\n";

echo "\nDone. Log out and back in, or reload the admin panel: the sidebar\n";
echo "entry 'MCP API Keys' now appears for group-1 users.\n";
echo "If your admin group is not group 1, grant the mod_mcp roles via\n";
echo "User Groups > Permissions.\n";
echo ob_get_clean();
exit(0);
