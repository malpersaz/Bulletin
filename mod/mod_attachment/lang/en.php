<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_attachment'] = [];

$lang['mod_attachment']['admin'] = [
    'upload_area' => 'File Upload Area',
    'select_file_instruction' => 'Select a file by clicking the Browse button below!',
    'ctrl_key_instruction' => 'You can select and upload multiple files by holding down the <b>CTRL</b> key on your keyboard.',
    'loading' => 'Uploading...',
    'view' => 'View',
    'waiting_queue' => 'Waiting in upload queue...',
    'file' => 'File',
    'selected_files_count' => 'Number of Selected Files',
    'waiting_files_count' => 'Number of Files Waiting in Queue',
    'file_size_error' => 'The file \'+file.name+\' you selected is too large.',
    'browse' => 'Browse',
    'back' => 'Back',
    'file_management' => 'File Management',
    'confirm_delete_file' => 'Are you sure you want to delete this file?',
    'delete_file' => 'Delete File',
    'yes' => 'Yes',
    'cancel' => 'Cancel',
    'save' => 'Save',
    'content_upload_area' => 'File Upload Area for %s Content',
    'file_name' => 'File Name',
    'no_file_found' => 'No files found!',
    'all_files' => '--- All Files ---',
    'no_saved_file_found' => 'No registered files found!',
    'select_category_add_file' => 'Select a category and add files',
    'download_file' => 'Download File',
    'edit_file_name' => 'Edit File Name',
    'remove_image' => 'Remove File',
    'file_uploaded_success' => '\'File %s has been uploaded to the server!\'',
    'file_deleted' => 'File deleted',
    'files_belonging_to' => 'Files belonging to "%s"',
    'add_files' => 'Add File',
    'file_edited' => 'File Edited',
    'file_renamed' => 'File name "+original+" has been changed to "+text+"',
    'file_upload_failed' => 'File upload failed.',
    'statistics' => 'Statistics',
    'new' => 'New',
    'panel' => 'Panel',
    'warning' => 'Warning!',
    'info' => 'Info!',
    'message' => 'Message!',
    'help' => 'Help!'
];

$lang['mod_attachment']['hata'] = [
    'source_not_retrieved' => 'Could not get file source',
    'specified_file_invalid' => 'The specified file is not a valid file',
    'specify_destination_file' => 'Please specify the file to write to',
    'supported_formats_only' => 'Only png, jpg, jpeg, gif, png, bmp files can be generated as output',
    'file_creation_failed' => 'File could not be created',
    'file_output_failed' => 'File output could not be generated'
];

$lang['mod_attachment']['errors'] = [
    'missing_module_name' => 'Module information is missing for the file operation.',
    'missing_record_id' => 'Record information is missing for the file operation.',
    'invalid_module' => 'No file module is defined for this record.',
    'module_file_permission_missing' => 'No file permission is defined for this module.',
    'invalid_record' => 'The related record could not be found.',
    'invalid_file' => 'The related file record could not be found.',
    'invalid_record_title_field' => 'The mod_value field for this module is invalid. Correct the display field manually.',
    'upload_field_missing' => 'Upload field information is missing.',
    'uploaded_file_missing' => 'No file was provided for upload.',
];

$lang['mod_attachment']['role'] = [
    'name' => 'Gallery Roles',
    'add' => 'Can add files.',
    'edit' => 'Can edit uploaded files',
    'delete' => 'Can delete uploaded files',
    'list' => 'Can view file management',
    'moderate' => 'Can manage files belonging to others'
];

$lang['mod_attachment']['module'] = [
    'description' => 'File Module',
];

$lang['mod_attachment']['ajax'] = [
    'success' => 'Success',
    'error' => 'Error',
];

$lang['mod_attachment']['role_errors'] = [
    'no_edit_permission' => 'You do not have permission to edit a file belonging to someone else',
    'no_delete_permission' => 'You do not have permission to delete a file belonging to someone else.',
    'add' => 'You do not have permission to add files',
    'delete' => 'You do not have permission to delete files',
    'edit' => 'You do not have permission to edit files',
    'list' => 'You do not have permission to view file management'
];
