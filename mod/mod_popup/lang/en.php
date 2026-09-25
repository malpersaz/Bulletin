<?php
/**
 * Popup/Modal Management Module - English Language File
 *
 * @package AKM Business
 * @module  mod_popup
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Popup/Modal Management Module Language Translations
 */
$lang['mod_popup'] = [

    // === Basic Actions ===
    'add' => 'Add Popup',
    'edit' => 'Edit Popup',
    'list' => 'Popup List',

    // === System Messages ===
    'success' => 'Operation Successful!',
    'error' => 'Error Occurred!',
    'information' => 'Information!',

    // === Image Management ===
    'remove_image' => 'Are you sure you want to remove this image?',
    'remove_image_success' => 'Image removed',
    'choose_wish' => 'Select if you wish',
    'remove_record' => 'Are you sure you want to delete this record?',

    // === Messages ===
    'message' => [
        'success' => [
            'add' => 'Popup successfully added!',
            'edit' => 'Popup successfully updated!',
        ],
        'error' => [
            'add' => 'Popup could not be added!',
            'edit' => 'Popup could not be updated!',
        ],
        'information' => [
            'moderate' => 'Double-click to edit content',
            'move' => 'Drag and drop to change content order',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'The specified record could not be found!',
    ],

    // === Buttons ===
    'button' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'refresh' => 'Refresh',
        'delete' => 'Delete',
        'gallery' => 'Image Gallery',
        'file' => 'File Manager',
        'cancel' => 'Cancel',
        'remove' => 'Remove',
        'yes' => 'Yes',
        'no' => 'No',
    ],

    // === Data Grid Messages ===
    'grid' => [
        'showing_results' => 'Showing {from} to {to} of {total} results.',
        'page' => 'Page',
        'page_count' => 'Page Count',
        'loading' => 'Loading data, please wait...',
        'no_data' => 'No data found',
        'connection_error' => 'Connection Error',
        'search' => 'Search',
        'select_at_least_one' => 'Please select at least one row',
        'select_only_one' => 'Please select only one row',
        'publish' => [
            'key' => 'Publish',
            'value' => 'Are you sure you want to publish this popup?',
        ],
        'unpublish' => [
            'key' => 'Unpublish',
            'value' => 'Are you sure you want to unpublish this popup?',
        ],
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Please do not leave the %s field blank',
        'length' => 'The %s field must be between %s and %s characters',
        'email' => 'Please enter a valid email address in the %s field',
        'unique' => 'The %s you entered is registered in the system. Please specify a different %s!',
        'inarray' => 'The %s field can only accept \"%s\" values',
    ],

    // === Permission Roles ===
    'role' => [
        'name' => 'Popup Management Permissions',
        'add' => 'Can add popup',
        'edit' => 'Can edit popup',
        'delete' => 'Can delete popup',
        'list' => 'Can view popup management',
        'moderate_edit' => 'Can edit popup records of others',
        'moderate_delete' => 'Can delete popup records of others',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'moderate_edit' => 'You cannot edit popup records of others',
        'moderate_delete' => 'You cannot delete popup records of others',
        'add' => 'You do not have permission to add popup',
        'edit' => 'You do not have permission to edit popup',
        'list' => 'You do not have permission to view popup management',
        'delete' => 'You do not have permission to delete popup',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Language',
            'name' => 'Popup Name',
            'start_date' => 'Publish Start',
            'end_date' => 'Publish End',
            'status' => 'Active?',
            'content' => 'Description',
            'sure' => 'Auto Close (seconds)',
            'video' => 'Video Type',
            'link' => 'Image Link or Video URL',
            'image' => 'Image',
        ],
        'radio' => [
            'publish_yes' => 'Yes',
            'publish_no' => 'No',
        ],
        'select_value' => [
            'video_url' => 'Other',
            'video_youtube' => 'YouTube',
            'video_vimeo' => 'Vimeo',
            'video_instagram' => 'Instagram',
        ],
    ],

];