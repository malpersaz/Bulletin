<?php
/**
 * Task: Seed Missing Roles and Permissions for mod_event and mod_event_category
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../init.php';

$db = new Database();

echo "=== Seeding Roles & Permissions for mod_event & mod_event_category ===\n";

$modules = [
    'mod_event' => [
        'add', 'edit', 'delete', 'list', 'gallery', 'file', 'faq', 'video', 
        'edit_status', 'change_status', 'approval', 'moderate_edit', 'moderate_delete', 'video_download'
    ],
    'mod_event_category' => [
        'add', 'edit', 'delete', 'list', 'moderate_edit', 'moderate_delete'
    ]
];

foreach ($modules as $modName => $roles) {
    $db->fetch_array("SELECT mod_id FROM mods WHERE mod_name = '" . addslashes($modName) . "'");
    if ($db->getSize() === 0) {
        echo "[WARN] Module $modName not found in mods table. Inserting...\n";
        $db->query("INSERT INTO mods (mod_name) VALUES ('" . addslashes($modName) . "')");
        $modId = (int)$db->insertId();
    } else {
        $row = $db->toArray()[0];
        $modId = (int)$row['mod_id'];
    }

    echo "Module: $modName (mod_id: $modId)\n";

    foreach ($roles as $role) {
        $db->fetch_array("SELECT role_id FROM mod_user_role WHERE mod_id = $modId AND role_name = '" . addslashes($role) . "'");
        if ($db->getSize() === 0) {
            $db->query("INSERT INTO mod_user_role (mod_id, role_name) VALUES ($modId, '" . addslashes($role) . "')");
            $roleId = (int)$db->insertId();
            echo "  + Added role: $role (role_id: $roleId)\n";
        } else {
            $roleId = (int)$db->toArray()[0]['role_id'];
            echo "  = Existing role: $role (role_id: $roleId)\n";
        }

        // Grant to Admin group (group_id = 1)
        $db->fetch_array("SELECT id FROM mod_user_perm WHERE group_id = 1 AND mod_id = $modId AND role_id = $roleId");
        if ($db->getSize() === 0) {
            $db->query("INSERT INTO mod_user_perm (group_id, mod_id, role_id) VALUES (1, $modId, $roleId)");
            echo "    -> Granted permission to Group 1 (Admin)\n";
        } else {
            echo "    -> Permission already granted to Group 1\n";
        }
    }
}

echo "=== Seeding Completed Successfully ===\n";
