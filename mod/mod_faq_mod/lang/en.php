<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_faq_mod'] = [
    'list' => 'FAQ Module List',
    'add' => 'Add FAQ Module',
    'edit' => 'Edit FAQ Module',
    'information' => 'Information',
    'success' => 'Success',
    'error' => 'Error Occurred',
    'remove_record' => 'Are you sure you want to delete this record?',
    'message' => [
        'success' => [
            'add' => 'FAQ Module successfully added!',
            'edit' => 'FAQ Module successfully updated!',
            'delete' => 'FAQ Module deleted successfully!',
        ],
        'error' => [
            'add' => 'Failed to add FAQ Module!',
            'edit' => 'Failed to update FAQ Module!',
        ],
    ],
    'errors' => [
        'record' => 'The specified record could not be found!',
    ],
    'button' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'refresh' => 'Refresh',
        'cancel' => 'Cancel',
        'remove' => 'Remove',
        'yes' => 'Yes',
        'no' => 'No',
    ],
    'validator' => [
        'required' => 'Please do not leave the %s field empty',
        'length' => '%s must be between %s and %s characters',
        'unique' => 'The %s you entered is already registered in the system. Please specify a different %s!',
    ],
    'role' => [
        'name' => 'FAQ Module Management Roles',
        'add' => 'Can Add FAQ Module',
        'edit' => 'Can Edit FAQ Module',
        'delete' => 'Can Delete FAQ Module',
        'list' => 'Can View FAQ Module Management',
        'moderate_edit' => 'Can edit FAQ Module records belonging to others',
        'moderate_delete' => 'Can delete FAQ Module records belonging to others',
    ],
    'role_errors' => [
        'moderate_edit' => 'You cannot edit FAQ Module records belonging to others',
        'moderate_delete' => 'You cannot delete FAQ Module records belonging to others',
        'add' => 'You do not have permission to add FAQ Modules',
        'edit' => 'You do not have permission to edit FAQ Modules',
        'list' => 'You do not have permission to view FAQ Module Management',
        'delete' => 'You do not have permission to delete FAQ Modules',
    ],
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'FAQ Module Name',
            'mod_name' => 'Module Name',
            'mod_key' => 'Module Key (ID)',
            'mod_value' => 'Module Value (Title)',
        ],
    ],
];
