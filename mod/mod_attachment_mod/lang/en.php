<?php
/**
 * File Module Integration - English Language File
 *
 * @package Rojname
 * @module  mod_attachment_mod
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * File Module Integration Language Translations
 */
$lang['mod_attachment_mod'] = [

    // === Basic Navigation ===
    'list' => 'File Module List',
    'add' => 'Add File Module',
    'edit' => 'Edit File Module',
    'information' => 'Information',
    'oke' => 'Success',
    'hata' => 'Error Occurred',
    'error' => 'Fatal Error Occurred',
    'remove_record' => 'Are you sure you want to delete this record?',

    // === Message Structure ===
    'message' => [
        'oke' => [
            'add' => 'File Module successfully added!',
            'edit' => 'File Module successfully updated!',
        ],
        'error' => [
            'add' => 'Failed to add File Module!',
            'edit' => 'Failed to update File Module!',
        ],
    ],

    'errors' => [
        'record' => 'The specified record could not be found!',
    ],

    'button' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'remove' => 'Remove',
        'yes' => 'Yes',
        'no' => 'No',
        'refresh' => 'Refresh',
        'download' => 'Download',
        'upload' => 'Upload',
        'add' => 'Add',
        'edit' => 'Edit',
        'yenile' => 'Refresh',
        'delete' => 'Delete',
        'vazgec' => 'Cancel',
        'kaldir' => 'Remove',
        'evet' => 'Yes',
        'hayir' => 'No',
    ],

    'grid' => [
        0 => 'Showing {from} to {to} of {total} results',
        1 => 'Page',
        2 => 'Page Count',
        3 => 'Loading data, please wait...',
        4 => 'No data found',
        5 => 'Connection Error',
        6 => 'Search',
        7 => 'Please select at least one row',
        8 => 'Please select only one row',
    ],

    'validator' => [
        'required' => 'Please do not leave the %s field empty',
        'length' => '%s must be between %s and %s characters',
        'email' => 'Please enter a valid email address in the %s field',
        'unique' => 'The %s you entered is already registered in the system. Please specify a different %s!',
        'inarray' => '%s field can only accept \' %s \' values',
    ],

    'roll' => [
        'ad' => 'File Module Management Roles',
        'add' => 'Can Add File Module',
        'edit' => 'Can Edit File Module',
        'delete' => 'Can Delete File Module',
        'list' => 'Can View File Module Management',
        'moderate_edit' => 'Can edit File Module records belonging to others',
        'moderate_delete' => 'Can delete File Module records belonging to others',
    ],

    'role_errors' => [
        'moderate_edit' => 'You cannot edit File Module records belonging to others',
        'moderate_delete' => 'You cannot delete File Module records belonging to others',
        'add' => 'You do not have permission to add File Modules',
        'edit' => 'You do not have permission to edit File Modules',
        'list' => 'You do not have permission to view File Module Management',
        'delete' => 'You do not have permission to delete File Modules',
    ],

    'admin' => [
        'columns' => [
            'id' => 'ID',
            'ad' => 'File Module Name',
            'mod_name' => 'Module Name',
            'mod_key' => 'Module Key (ID)',
            'mod_value' => 'Module Value (Name)',
        ],
    ],

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Add File Module',
        'edit' => 'Edit File Module',
        'list' => 'File Module List',
        'delete' => 'Delete File Module',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'update' => 'Update',
        'remove' => 'Remove',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'File Module successfully added!',
            'updated' => 'File Module successfully updated!',
            'deleted' => 'File Module successfully deleted!',
            'configured' => 'File Module configured successfully!',
        ],
        'error' => [
            'not_added' => 'Failed to add File Module!',
            'not_updated' => 'Failed to update File Module!',
            'not_deleted' => 'Failed to delete File Module!',
            'not_found' => 'The specified File Module record could not be found!',
            'configuration_failed' => 'File Module configuration failed!',
        ],
        'info' => [
            'module_intro' => 'File modules allow integration of file management capabilities into other system components',
            'configuration_info' => 'Configure file module settings to enable file operations in specific modules',
        ],
    ],

    // === Module Configuration ===
    'configuration' => [
        'basic_settings' => 'Basic Settings',
        'advanced_settings' => 'Advanced Settings',
        'integration_settings' => 'Integration Settings',
        'security_settings' => 'Security Settings',

        'module_name' => 'Module Name',
        'module_description' => 'Module Description',
        'module_version' => 'Module Version',
        'module_status' => 'Module Status',
        'target_module' => 'Target Module',
        'integration_type' => 'Integration Type',
        'file_permissions' => 'File Permissions',
        'access_level' => 'Access Level',
        'auto_sync' => 'Auto Synchronization',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'remove_record' => 'Are you sure you want to delete this record?',
        'delete_single' => 'Are you sure you want to delete this File Module?',
        'delete_multiple' => 'Are you sure you want to delete :count selected File Modules?',
        'enable_module' => 'Are you sure you want to enable this File Module?',
        'disable_module' => 'Are you sure you want to disable this File Module?',
        'reset_configuration' => 'Are you sure you want to reset the module configuration?',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'refresh' => 'Refresh',
        'delete' => 'Delete',
        'cancel' => 'Cancel',
        'remove' => 'Remove',
        'yes' => 'Yes',
        'no' => 'No',
        'enable' => 'Enable',
        'disable' => 'Disable',
        'configure' => 'Configure',
        'test' => 'Test Connection',
        'sync' => 'Sync Now',
        'reset' => 'Reset',
    ],

    // === Data Grid Messages ===
    'grid' => [
        'pagination' => 'Showing {from} to {to} of {total} results',
        'page' => 'Page',
        'page_count' => 'Page Count',
        'loading' => 'Loading data, please wait...',
        'no_data' => 'No data found',
        'connection_error' => 'Connection Error',
        'search' => 'Search',
        'select_at_least_one' => 'Please select at least one row',
        'select_only_one' => 'Please select only one row',
    ],

    // === Validation Messages ===
    'validation' => [
        'required' => 'Please do not leave the %s field empty',
        'length' => '%s must be between %s and %s characters',
        'email' => 'Please enter a valid email address in the %s field',
        'unique' => 'The %s you entered is already registered in the system. Please specify a different %s!',
        'inarray' => '%s field can only accept \' %s \' values',
        'module_name_required' => 'Module name is required',
        'target_module_required' => 'Target module selection is required',
        'invalid_integration_type' => 'Invalid integration type selected',
        'configuration_invalid' => 'Module configuration is invalid',
    ],

    // === Access Control (ACL) ===
    'permissions' => [
        'title' => 'File Module Management Permissions',
        'add' => 'Can Add File Module',
        'edit' => 'Can Edit File Module',
        'delete' => 'Can Delete File Module',
        'list' => 'Can View File Module Management',
        'configure' => 'Can Configure File Modules',
        'test' => 'Can Test Module Connections',
        'sync' => 'Can Synchronize Modules',
        'edit_others' => 'Can edit File Module records belonging to others',
        'delete_others' => 'Can delete File Module records belonging to others',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'edit_others' => 'You cannot edit File Module records belonging to others',
        'delete_others' => 'You cannot delete File Module records belonging to others',
        'add' => 'You do not have permission to add File Modules',
        'edit' => 'You do not have permission to edit File Modules',
        'list' => 'You do not have permission to view File Module Management',
        'delete' => 'You do not have permission to delete File Modules',
        'configure' => 'You do not have permission to configure File Modules',
        'test' => 'You do not have permission to test module connections',
        'sync' => 'You do not have permission to synchronize modules',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'File Module Name',
            'mod_name' => 'Module Name',
            'mod_key' => 'Module Key (ID)',
            'mod_value' => 'Module Value (Name)',
            'status' => 'Status',
            'target_module' => 'Target Module',
            'integration_type' => 'Integration Type',
            'created_date' => 'Created Date',
            'modified_date' => 'Modified Date',
            'actions' => 'Actions',
        ],
    ],

    // === Module Types ===
    'module_types' => [
        'file_upload' => 'File Upload Module',
        'file_manager' => 'File Manager Module',
        'image_gallery' => 'Image Gallery Module',
        'document_library' => 'Document Library Module',
        'media_player' => 'Media Player Module',
        'file_browser' => 'File Browser Module',
        'custom_integration' => 'Custom Integration',
    ],

    // === Integration Types ===
    'integration_types' => [
        'standalone' => 'Standalone',
        'embedded' => 'Embedded',
        'popup' => 'Popup Modal',
        'overlay' => 'Overlay',
        'iframe' => 'iFrame',
        'api_based' => 'API Based',
        'webhook' => 'Webhook',
    ],

    // === Module Status ===
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'testing' => 'Testing',
        'error' => 'Error',
        'maintenance' => 'Maintenance',
        'deprecated' => 'Deprecated',
    ],

    // === Synchronization Features ===
    'synchronization' => [
        'auto_sync' => 'Auto Synchronization',
        'sync_now' => 'Sync Now',
        'sync_status' => 'Sync Status',
        'last_sync' => 'Last Sync',
        'sync_in_progress' => 'Synchronization in progress...',
        'sync_completed' => 'Synchronization completed successfully',
        'sync_failed' => 'Synchronization failed',
        'sync_settings' => 'Sync Settings',
        'sync_interval' => 'Sync Interval',
        'sync_method' => 'Sync Method',
    ],

    // === Testing Features ===
    'testing' => [
        'test_connection' => 'Test Connection',
        'test_upload' => 'Test Upload',
        'test_download' => 'Test Download',
        'test_permissions' => 'Test Permissions',
        'test_configuration' => 'Test Configuration',
        'test_results' => 'Test Results',
        'test_passed' => 'Test Passed',
        'test_failed' => 'Test Failed',
        'test_in_progress' => 'Testing in progress...',
        'detailed_report' => 'Detailed Test Report',
    ],

    // === Advanced Configuration ===
    'advanced_config' => [
        'file_size_limit' => 'File Size Limit',
        'allowed_extensions' => 'Allowed Extensions',
        'upload_path' => 'Upload Path',
        'temp_path' => 'Temporary Path',
        'backup_enabled' => 'Backup Enabled',
        'backup_interval' => 'Backup Interval',
        'compression_enabled' => 'Compression Enabled',
        'encryption_enabled' => 'Encryption Enabled',
        'virus_scanning' => 'Virus Scanning',
        'logging_enabled' => 'Logging Enabled',
    ],

    // === Security Settings ===
    'security' => [
        'access_control' => 'Access Control',
        'user_permissions' => 'User Permissions',
        'role_permissions' => 'Role Permissions',
        'ip_restrictions' => 'IP Restrictions',
        'rate_limiting' => 'Rate Limiting',
        'audit_trail' => 'Audit Trail',
        'secure_upload' => 'Secure Upload',
        'file_validation' => 'File Validation',
        'security_headers' => 'Security Headers',
    ],

    // === Success Messages ===
    'success_messages' => [
        'operation_completed' => 'Operation completed successfully!',
        'changes_saved' => 'Changes saved successfully!',
        'module_enabled' => 'File Module enabled successfully!',
        'module_disabled' => 'File Module disabled successfully!',
        'configuration_saved' => 'Configuration saved successfully!',
        'sync_completed' => 'Synchronization completed successfully!',
        'test_passed' => 'All tests passed successfully!',
    ],

    // === Error Messages ===
    'error_messages' => [
        'general' => 'An error occurred. Please try again.',
        'network' => 'Network error. Please check your internet connection.',
        'server' => 'Server error. Please try again later.',
        'configuration_error' => 'Configuration error occurred',
        'connection_failed' => 'Connection failed',
        'sync_failed' => 'Synchronization failed',
        'test_failed' => 'Test failed',
        'module_not_found' => 'File Module not found',
        'invalid_configuration' => 'Invalid configuration',
    ],

    // === Helper Text ===
    'help' => [
        'module_intro' => 'File modules provide file management capabilities to other system components',
        'configuration_help' => 'Configure module settings to enable proper integration',
        'security_help' => 'Configure security settings to protect your files and system',
        'testing_help' => 'Test your module configuration to ensure everything works correctly',
        'sync_help' => 'Synchronize modules to keep data consistent across the system',
    ],

    // === Empty States ===
    'empty_states' => [
        'no_modules' => 'No File Modules configured yet',
        'no_results' => 'No modules found matching your search criteria',
        'no_sync_data' => 'No synchronization data available',
        'add_first_module' => 'Add your first File Module to get started',
    ],

    // === Loading States ===
    'loading_states' => [
        'loading_modules' => 'Loading modules...',
        'testing_connection' => 'Testing connection...',
        'synchronizing' => 'Synchronizing...',
        'configuring' => 'Configuring...',
        'processing_request' => 'Processing request...',
    ],

    // === AJAX Response Messages ===
    'ajax' => [
        'success' => 'Success',
        'error' => 'Error',
    ],

];