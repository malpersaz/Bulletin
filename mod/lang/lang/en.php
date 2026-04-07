<?php
/**
 * Language Module - English Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['lang'] = [
    'installer' => [
        'table_exists' => 'Table \'lang\' is already installed in the database',
        'sql_added' => 'File \'lang.sql\' has been added to the database',
        'lang_added' => '<b>%s</b> - added to database!',
        'lang_exists' => '<b>%s</b> - already exists in database!',
    ],
    'system' => [
        'starting' => 'Starting Installation...',
        'installing' => 'installing: %s',
    ],
    'installer_mobile' => [
        'hero' => [
            'badge' => 'Rojname installer',
            'title' => 'Phone-first setup shell',
            'body' => 'This installer runs inside a mobile-focused flow with step persistence, early environment checks, and a controlled handoff into the legacy installer.',
        ],
        'steps' => ['welcome' => 'Welcome', 'language' => 'Language', 'checks' => 'Checks', 'setup' => 'Setup', 'review' => 'Review', 'result' => 'Result'],
        'welcome' => [
            'title' => 'Focused install flow',
            'body' => 'The shell keeps setup inside a phone-sized path, remembers progress between refreshes, and blocks installation when prerequisites are missing.',
            'project_label' => 'Project',
            'project_detail' => 'Rojname branding remains the default installer target for this repository.',
            'language_scope_label' => 'Language scope',
            'language_scope_detail' => 'Installer defaults keep the repository-specific language exclusions in place.',
        ],
        'language' => ['title' => 'Installer language', 'body' => 'Select the installer session language before moving deeper into setup. This state is persisted in the current session.'],
        'environment' => ['title' => 'Environment check', 'body' => 'Forward navigation remains blocked until every required check passes.', 'ready' => 'Ready', 'blocked' => 'Blocked'],
        'setup' => [
            'title' => 'Project setup',
            'body' => 'Capture the minimum install state now. The current execution phase still hands off module execution to the existing installer pipeline.',
            'project_name_label' => 'Project name',
            'project_default_language_label' => 'Project default language',
            'sample_content_label' => 'Sample content',
            'sample_content_no' => 'No, keep the install clean.',
            'sample_content_yes' => 'Yes, keep sample-content intent in installer state.',
            'sample_content_notice' => 'Sample-content choice is stored now so the mobile flow has an explicit decision surface. Execution wiring still needs a later phase.',
        ],
        'review' => [
            'title' => 'Review and install',
            'body' => 'The current mobile shell summarizes installer state and then executes the existing module pipeline inside this guided flow.',
            'project_name_label' => 'Project name',
            'installer_language_label' => 'Installer language',
            'project_default_language_label' => 'Project default language',
            'module_summary_label' => 'Module summary',
            'module_summary_detail' => '%d required, %d optional, %d project-specific, %d sample-content-capable.',
            'sample_content_label' => 'Sample content decision',
            'sample_content_enabled' => 'Stored as enabled for later execution wiring.',
            'sample_content_disabled' => 'Stored as disabled.',
        ],
        'result' => [
            'success_title' => 'Installation complete',
            'failure_title' => 'Installation stopped',
            'success_body' => 'The legacy module installer completed inside the mobile shell.',
            'failure_body' => 'The install did not complete successfully. Review the captured output before retrying.',
            'installed_at' => 'Installed at',
            'no_output' => 'No installer output was captured.',
        ],
        'actions' => [
            'start_setup' => 'Start setup',
            'continue' => 'Continue',
            'back' => 'Back',
            'continue_to_setup' => 'Continue to setup',
            'continue_to_review' => 'Continue to review',
            'run_installer' => 'Run installer',
            'reset_state' => 'Reset installer state',
        ],
        'errors' => [
            'resolve_environment' => 'Resolve the blocking environment checks before continuing.',
            'blocked_install' => 'Installation cannot start while environment checks are blocked.',
        ],
        'checks' => [
            'php_version' => ['label' => 'PHP 8.0+', 'detail' => 'Current runtime: %s'],
            'extensions' => ['label' => 'Required extensions', 'ok' => 'All required extensions are available.', 'missing' => 'Missing: %s'],
            'directories' => ['label' => 'Writable directories', 'ok' => 'Installer storage directories are writable.', 'blocked' => 'Blocked: %s'],
            'database' => ['label' => 'Database configuration', 'missing' => 'No active database configuration found.', 'detail' => 'Active adapter: %s (%s)'],
        ],
    ],
];
