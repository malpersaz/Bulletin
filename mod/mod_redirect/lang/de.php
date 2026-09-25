<?php

$lang['mod_redirect'] = [
    'list' => 'Redirect Management',
    'add' => 'Add New Redirect',
    'edit' => 'Edit Redirect',
    'information' => 'Information',
    'success' => 'Success',
    'error' => 'Error occurred',
    'remove_record' => 'Are you sure you want to delete this redirect?',
    'message' => [
        'success' => [
            'add' => 'Redirect added successfully!',
            'edit' => 'Redirect updated successfully!',
            'delete' => 'Redirect deleted successfully!',
        ],
        'error' => [
            'add' => 'Failed to add redirect!',
            'edit' => 'Failed to update redirect!',
        ],
    ],
    'errors' => [
        'record' => 'Specified redirect could not be found!',
    ],
    'button' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'cancel' => 'Cancel',
        'yes' => 'Yes',
        'no' => 'No',
    ],
    'validator' => [
        'required' => 'Please do not leave the %s field empty',
        'length' => '%s length must be between %s and %s characters',
        'unique_old_url' => 'This source URL is already registered in the system!',
        'inarray' => 'The %s field can only accept \'%s\' values',
    ],
    'role' => [
        'name' => 'Redirect Management Roles',
        'add' => 'Can add new redirects',
        'edit' => 'Can edit redirects',
        'delete' => 'Can delete redirects',
        'list' => 'Can view redirect management dashboard',
    ],
    'role_errors' => [
        'add' => 'You do not have permission to add redirects',
        'edit' => 'You do not have permission to edit redirects',
        'list' => 'You do not have permission to view redirect management',
        'delete' => 'You do not have permission to delete redirects',
    ],
    'admin' => [
        'search_placeholder' => 'Search redirects...',
        'intro_add' => 'Enter the source URL, target URL, and choose the HTTP status code to create a redirect.',
        'intro_edit' => 'Modify the source URL, target URL, or HTTP status code for this redirect record.',
        'empty_description' => 'No registered redirects found.',
        'dashboard' => [
            'active_redirects' => 'Active Redirects',
            'standard_type' => 'Standard Type',
        ],
        'columns' => [
            'id' => 'ID',
            'old_url' => 'Source URL (Old)',
            'new_url' => 'Target URL (New)',
            'status_code' => 'Status Code',
            'created_at' => 'Creation Date',
        ],
    ],
];
