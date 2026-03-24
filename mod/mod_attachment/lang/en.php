<?php
/**
 * File Upload Module - English Language File
 *
 * @package AKM Business CMS
 * @module  mod_file
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * File Upload Module Language Translations
 */
$lang['mod_attachment'] = [];

// === Admin Interface Messages ===
$lang['mod_attachment']['admin'] = [
    1 => 'File Upload Area',
    2 => 'Select a file by clicking the Browse button below!',
    3 => 'You can select and upload multiple files by holding down the <b>CTRL</b> key on your keyboard.',
    4 => 'Uploading...',
    5 => 'View',
    6 => 'Waiting in upload queue...',
    7 => 'File',
    8 => 'Number of Selected Files',
    9 => 'Number of Files Waiting in Queue',
    10 => 'The file \'+file.name+\' you selected is too large.',
    11 => 'Browse',
    12 => 'Back',
    14 => 'File Management',
    17 => 'Are you sure you want to delete this file?',
    18 => 'Delete File',
    19 => 'Yes',
    20 => 'Cancel',
    21 => 'Save',
    22 => 'File Upload Area for %s Content',
    23 => 'File Name',
    24 => 'Double-click the file you want to rename.',
    25 => 'No files found!',
    26 => '--- All Files ---',
    27 => 'No registered files found!',
    28 => 'Select a category and add files',
    29 => 'Download File',
    30 => 'Edit File Name',
    31 => 'Remove File',
    35 => '\'File %s has been uploaded to the server!\'',
    36 => 'File deleted',
    37 => 'Files belonging to "%s"',
    38 => 'Add File',
    39 => 'File Edited',
    40 => 'File name "+original+" has been changed to "+text+"',
    'warning' => 'Warning!',
    'info' => 'Info!',
    'message' => 'Message!',
    'help' => 'Help!'
];

// === Error Messages ===
$lang['mod_attachment']['hata'] = [
    1 => 'Could not get file source',
    2 => 'The specified file is not a valid file',
    3 => 'Please specify the file to write to',
    4 => 'Only png, jpg, jpeg, gif, png, bmp files can be generated as output',
    5 => 'File could not be created',
    6 => 'File output could not be generated'
];

// === Role Permissions ===
$lang['mod_attachment']['roll'] = [
    'ad' => 'Gallery Roles',
    'add' => 'Can add files.',
    'edit' => 'Can edit uploaded files',
    'delete' => 'Can delete uploaded files',
    'list' => 'Can view file management',
    'moderate' => 'Can manage files belonging to others'
];

// === Module Description ===
$lang['mod_attachment']['module'] = [
    'description' => 'File Module',
];

// === AJAX Response Messages ===
$lang['mod_attachment']['ajax'] = [
    'success' => 'Success',
    'error' => 'Error',
];

// === Role-based Error Messages ===
$lang['mod_attachment']['role_errors'] = [
    1 => 'You do not have permission to edit a file belonging to someone else',
    2 => 'You do not have permission to delete a file belonging to someone else.',
    'add' => 'You do not have permission to add files',
    'delete' => 'You do not have permission to delete files',
    'edit' => 'You do not have permission to edit files',
    'list' => 'You do not have permission to view file management'
];