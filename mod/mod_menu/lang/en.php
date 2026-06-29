<?php
/**
 * Menu Module - English Language File
 *
 * @package AKM Business
 * @module  mod_menu
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Menu Module Language Translations
 */
$lang['mod_menu'] = [

    // === Page Titles ===
    'list' => 'Menu Management',
    'add' => 'Add Menu',
    'edit' => 'Edit Menu',
    'information' => 'Information',

    // === Legacy Message Keys (used by index.php) ===
    'message' => [
        'oke' => [
            'add' => 'Menu successfully added!',
            'edit' => 'Menu successfully updated!',
        ],
        'error' => [
            'add' => 'Failed to add menu!',
            'edit' => 'Failed to update menu!',
        ],
    ],

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'list' => 'List',
        'delete' => 'Delete',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'update' => 'Update',
        'remove' => 'Remove',
        'refresh' => 'Refresh',
        'export' => 'Export',
        'import' => 'Import',
        'bulk_actions' => 'Bulk Actions',
        'gallery' => 'Gallery',
        'file' => 'Files',
        'unlock_reorder' => 'Unlock Reorder Mode',
        'lock_reorder' => 'Lock Reorder Mode',
        'publish_toggle' => 'Publish Status',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Menu successfully added!',
            'updated' => 'Menu successfully updated!',
            'deleted' => 'Menu successfully deleted!',
            'reordered' => 'Menu order updated!',
            'published' => 'Menu successfully published!',
            'unpublished' => 'Menu successfully unpublished!',
        ],
        'error' => [
            'not_added' => 'Failed to add menu!',
            'not_updated' => 'Failed to update menu!',
            'not_deleted' => 'Failed to delete menu!',
            'not_found' => 'The specified menu record could not be found!',
        ],
        'warning' => [
            'delete' => 'Are you sure you want to delete this menu?',
            'reorder' => 'Are you sure you want to change the menu order?',
        ],
        'info' => [
            'edit_double_click' => 'Double-click the menu you want to edit',
            'drag_reorder' => 'Drag and drop the menu to reorder',
            'block_system_removed' => 'Block system removed - this feature is no longer available',
            'move' => 'You can drag and drop to reorder menus.',
            'reorder_mode' => 'Mode: Reorder (Unlocked)',
        ],
    ],

    // === Status Types ===
    'status' => [
        'published' => 'Published',
        'draft' => 'Draft',
        'archived' => 'Archived',
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Yes',
        'no' => 'No',
        'ok' => 'OK',
    ],

    // === Target Options ===
    'targets' => [
        'same' => 'Same Page',
        'new' => 'New Page',
        'blank' => 'New Tab',
    ],

    // === Form Labels ===
    'form' => [
        'basic_info' => 'Basic Information',
        'display_settings' => 'Display Settings',
        'advanced_settings' => 'Advanced Settings',
        'acl_settings' => 'Permission Settings (ACL)',

        'id' => 'ID',
        'name' => 'Menu Name',
        'url' => 'Menu URL',
        'parent' => 'Parent Menu',
        'order' => 'Order',
        'language' => 'Display Language',
        'target' => 'Target',
        'published' => 'Status',
        'html_class' => 'HTML Class',
        'icon' => 'Icon',
        'access_control' => 'Access Control',
        'description' => 'Description',
        'file' => 'File',
    ],

    // === Table Columns ===
    'columns' => [
        'id' => 'ID',
        'language' => 'Language',
        'name' => 'Menu Name',
        'url' => 'URL',
        'parent_menu' => 'Submenu',
        'order' => 'Order',
        'target' => 'Target',
        'status' => 'Status',
        'icon' => 'Icon',
        'access_level' => 'Access Level',
        'actions' => 'Actions',
        'parent_id' => 'Submenu',
        'html' => 'HTML',
        'acl_id' => 'Access Permission',
        'file' => 'File',
    ],

    'admin' => [
        'columns' => [
            'language' => 'Language',
            'name' => 'Menu Name',
            'url' => 'URL',
            'file' => 'File',
            'parent_id' => 'Submenu',
            'target' => 'Open Mode',
            'status' => 'Publish Status',
            'html' => 'HTML',
            'icon' => 'Icon',
            'acl_id' => 'Access Permission',
        ],
        'select_value' => [
            'target_e' => 'New Window (_blank)',
            'target_h' => 'Same Window (_self)',
        ],
        'radio' => [
            'publish_yes' => 'Published',
            'publish_no' => 'Draft',
        ],
    ],



    // === Validation Messages ===
    'validation' => [
        'required' => ':field field is required',
        'min_length' => ':field field must be at least :min characters',
        'max_length' => ':field field may not be greater than :max characters',
        'unique' => ':field field must be unique',
        'url' => 'Please enter a valid URL address',
        'numeric' => ':field field must be numeric',
        'email' => 'Please enter a valid email address',
    ],

    // === Access Control (ACL) ===
    'choose_wish' => 'Please Choose',
    'permissions' => [
        'title' => 'Menu Management Permissions',
        'view' => 'View Menus',
        'add' => 'Add Menu',
        'edit_own' => 'Edit Own Menu',
        'edit_all' => 'Edit All Menus',
        'delete_own' => 'Delete Own Menu',
        'delete_all' => 'Delete All Menus',
        'manage_access' => 'Manage Access',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'view_denied' => 'You do not have permission to view menu management',
        'add_denied' => 'You do not have permission to add menu',
        'edit_denied' => 'You do not have permission to edit menu',
        'delete_denied' => 'You do not have permission to delete menu',
        'access_denied' => 'You do not have permission to edit/delete this menu (does not belong to you)',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'delete_single' => 'Are you sure you want to delete this menu?',
        'delete_multiple' => 'Are you sure you want to delete :count selected menus?',
        'publish' => 'Are you sure you want to publish this menu?',
        'unpublish' => 'Are you sure you want to unpublish this menu?',
        'reorder_single' => 'Do you want to move this menu to its new location?',
    ],

    // === Helper Text ===
    'help' => [
        'parent_menu' => 'Select under which menu this menu should be displayed',
        'access_control' => 'Specify which user roles can see this menu',
        'url' => 'Use /starting format for internal links, start with https:// for external links',
        'order' => 'Determines the order of the menu (lower values appear at the top)',
        'icon' => 'Enter Bootstrap icon class name (e.g: bi-house)',
    ],

    // === Placeholders ===
    'placeholders' => [
        'menu_name' => 'Type the menu name',
        'menu_url' => '/sample-link or https://example.com',
        'menu_file' => 'menu-partial.twig',
        'menu_html' => 'Custom HTML content',
        'icon_class' => 'bi-house',
    ],

    // === Empty States ===
    'empty' => [
        'no_menus' => 'No menus have been added yet',
        'no_results' => 'No menus found matching your search criteria',
        'add_first' => 'Click the button to add the first menu',
    ],

    // === Loading States ===
    'loading' => [
        'saving' => 'Saving...',
        'loading' => 'Loading...',
        'processing' => 'Processing...',
    ],

    // === Success Messages ===
    'success' => [
        'operation_completed' => 'Operation completed successfully!',
        'changes_saved' => 'Changes saved!',
    ],

    // === Error Messages ===
    'errors' => [
        'general' => 'An error occurred. Please try again.',
        'network' => 'Network error. Please check your internet connection.',
        'server' => 'Server error. Please try again later.',
        'record' => 'The specified menu record could not be found.',
        'parent_selection_required' => 'You must choose which menu this item will be attached to.',
        'parent_language_mismatch' => 'Parent menu language must match the menu language.',
    ],

    // === AJAX Response Messages ===
    'ajax' => [
        'success' => 'Success',
        'error' => 'Error',
    ],

    // === Permission Roles ===
    'roll' => [
        'ad' => 'Menu Management Roles',
        'add' => 'Can Add Menus',
        'edit' => 'Can Edit Menus',
        'delete' => 'Can Delete Menus',
        'list' => 'Can View Menu Management',
        'acl' => 'Can Edit Menu Permissions',
        'setting' => 'Can Change Menu Settings',
        'link' => 'Can View Menu Links'
    ],

    // === Permission Errors ===
    'role_errors' => [
        'add' => 'You do not have permission to add menus',
        'edit' => 'You do not have permission to edit menus',
        'delete' => 'You do not have permission to delete menus',
        'list' => 'You do not have permission to view menu management',
        'moderate_edit' => 'You do not have permission to edit other users\' menus',
        'moderate_delete' => 'You do not have permission to delete other users\' menus',
    ],
];
$lang['mod_menu']['slots'] = [
    'header' => 'Header Menu',
    'footer' => 'Footer Menu',
];
$lang['mod_menu']['parent_labels'] = [
    'select_parent' => 'Select parent menu',
    'language_root' => 'Top level under :language',
    'system_root' => 'System root',
];
