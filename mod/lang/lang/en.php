<?php
/**
 * Language Module - English Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['lang'] = [
    'installer' => [
        'table_exists' => "The 'lang' table is already installed in the database",
        'sql_added' => "The 'lang.sql' file was added to the database",
        'lang_added' => '<b>%s</b> was added to the database',
        'lang_exists' => '<b>%s</b> already exists in the database',
    ],
    'system' => [
        'starting' => 'Installation is starting...',
        'installing' => 'installing: %s',
    ],
    'admin' => [
        'title' => 'Language Management',
        'headline' => 'Languages',
        'body' => 'Activate, deactivate, and choose the default site language from one phone-friendly control panel.',
        'status_active' => 'Active',
        'status_inactive' => 'Inactive',
        'default_badge' => 'Default',
        'current_badge' => 'Current UI',
        'code_label' => 'Code',
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'set_default' => 'Set Default',
        'empty_state' => 'No languages were found.',
        'permission_denied' => 'You do not have permission to manage languages.',
        'back_panel' => 'Panel',
        'back_home' => 'Website',
        'back' => 'Back',
    ],
    'messages' => [
        'activated' => '%s is now active.',
        'deactivated' => '%s is now inactive.',
        'default_changed' => '%s is now the default language.',
    ],
    'errors' => [
        'language_not_found' => 'The selected language could not be found.',
        'unknown_action' => 'The requested language action is not valid.',
        'cannot_deactivate_default' => 'Set another default language before deactivating this one.',
        'cannot_deactivate_last_active' => 'At least one language must remain active.',
        'cannot_deactivate_current_interface' => 'Switch the current admin language before deactivating it.',
        'update_failed' => 'The language state could not be updated.',
    ],
];
