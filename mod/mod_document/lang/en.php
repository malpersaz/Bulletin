<?php
/**
 * Advanced File Manager Module - English Language File
 */
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_document'] = [
    'list' => 'File Management',
    'add' => 'Add File',
    'edit' => 'Edit File',
    'delete' => 'Delete',
    'ad' => 'Name',
    'aciklama' => 'Description',
    'status' => 'Published',
    'publish_yes' => 'Yes',
    'publish_no' => 'No',
    'oke' => 'Success!',
    'hata' => 'Error!',
    'error' => 'Fatal Error!',
    'remove_record' => 'Are you sure you want to delete this record?',

    'role_errors' => [
        'list' => 'You do not have permission to view file management',
        'add' => 'You do not have permission to add files',
        'edit' => 'You do not have permission to edit files',
        'delete' => 'You do not have permission to delete files',
    ],

    'roll' => [
        'ad' => 'File Management Permissions',
        'add' => 'Can Add Files',
        'edit' => 'Can Edit Files',
        'delete' => 'Can Delete Files',
        'list' => 'Can View File Management',
        'moderate_edit' => 'Can edit file records belonging to others',
        'moderate_delete' => 'Can delete file records belonging to others',
        'file' => 'Can Upload Files',
    ],

    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Language',
            'ad' => 'File Name',
            'seo' => 'File URL',
            'status' => 'Published?',
            'image' => 'Image',
            'title' => 'Title',
            'keywords' => 'Keywords',
            'aciklama' => 'Description',
            'menu' => 'Send to Menu',
        ],
        'radio' => [
            'publish_yes' => 'Yes',
            'publish_no' => 'No',
        ],
    ],

    'buttons' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],
];
