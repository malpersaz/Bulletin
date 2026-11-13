<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_socialmedia_post_mod'] = [
    'named' => 'Social Post Modules',
    'list' => 'Social Post Module List',
    'add' => 'Add Social Post Module',
    'edit' => 'Edit Social Post Module',
    'information' => 'Information',
    'success' => 'Success',
    'error' => 'Error Occurred',
    'remove_record' => 'Are you sure you want to delete this record?',
    'message' => [
        'success' => [
            'add' => 'Social Post Module added successfully!',
            'edit' => 'Social Post Module updated successfully!',
            'delete' => 'Social Post Module deleted successfully!',
        ],
        'error' => [
            'add' => 'Failed to add Social Post Module!',
            'edit' => 'Failed to update Social Post Module!',
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
        'name' => 'Social Post Module Management Roles',
        'add' => 'Can Add Social Post Module',
        'edit' => 'Can Edit Social Post Module',
        'delete' => 'Can Delete Social Post Module',
        'list' => 'Can View Social Post Module Management',
        'moderate_edit' => 'Can edit Social Post Module records belonging to others',
        'moderate_delete' => 'Can delete Social Post Module records belonging to others',
    ],
    'role_errors' => [
        'moderate_edit' => 'You cannot edit Social Post Module records belonging to others',
        'moderate_delete' => 'You cannot delete Social Post Module records belonging to others',
        'add' => 'You do not have permission to add Social Post Modules',
        'edit' => 'You do not have permission to edit Social Post Modules',
        'list' => 'You do not have permission to view Social Post Module Management',
        'delete' => 'You do not have permission to delete Social Post Modules',
    ],
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'Social Post Module Name',
            'mod_name' => 'Module Name',
            'mod_key' => 'Module Key (ID)',
            'mod_value' => 'Module Value (Title)',
        ],
    ],
];
