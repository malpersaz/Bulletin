<?php
/**
 * File Management Module - English Language File
 *
 * @package Rojname
 * @module  dosya
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['file'] = [
    // === Core Operations ===
    'upload' => [
        'success' => 'File uploaded successfully!',
        'failed' => 'File upload failed!',
        'error_extension' => 'Invalid file extension. Only files with extensions: :extensions are allowed.',
        'error_directory' => 'Directory could not be created!',
        'error_upload' => 'Error occurred while uploading file',
        'error_move' => 'Could not move uploaded file to destination',
    ],
    'delete' => [
        'success' => 'File deleted successfully!',
        'failed' => 'File deletion failed!',
        'error_not_found' => 'File not found!',
        'error_permission' => 'Permission denied while deleting file',
    ],
    // === File Types ===
    'types' => [
        'image' => 'Image',
        'document' => 'Document',
        'video' => 'Video',
        'audio' => 'Audio',
        'archive' => 'Archive',
        'other' => 'Other',
    ],
    // === Status Messages ===
    'status' => [
        'uploading' => 'Uploading...',
        'processing' => 'Processing...',
        'complete' => 'Complete',
        'error' => 'Error',
        'queued' => 'Queued',
        'cancelled' => 'Cancelled',
    ],
    // === Validation Messages ===
    'validation' => [
        'required' => 'File selection is required',
        'invalid_type' => 'Invalid file type',
        'size_exceeded' => 'File size exceeds maximum allowed size',
        'corrupted' => 'File appears to be corrupted',
        'not_found' => 'The specified file could not be found',
    ],
    // === Error Messages ===
    'errors' => [
        'general' => 'An error occurred while processing the file',
        'permission_denied' => 'Permission denied. Check file and directory permissions',
        'disk_full' => 'Server disk space is full',
        'network_error' => 'Network error occurred during upload',
        'server_error' => 'Server error occurred. Please try again later',
        'timeout' => 'Upload timeout. Please try again',
    ],
    // === Success Messages ===
    'success' => [
        'file_uploaded' => 'File uploaded successfully!',
        'file_deleted' => 'File deleted successfully!',
        'file_moved' => 'File moved successfully!',
        'file_processed' => 'File processed successfully!',
    ],
    // === File Information ===
    'info' => [
        'name' => 'File Name',
        'size' => 'File Size',
        'type' => 'File Type',
        'extension' => 'Extension',
        'uploaded_at' => 'Uploaded At',
        'modified_at' => 'Last Modified',
        'path' => 'File Path',
        'url' => 'File URL',
    ],
    // === Actions ===
    'actions' => [
        'upload' => 'Upload File',
        'download' => 'Download',
        'delete' => 'Delete',
        'rename' => 'Rename',
        'move' => 'Move',
        'copy' => 'Copy',
        'view' => 'View',
        'edit' => 'Edit',
    ],
    // === File Size Units ===
    'size_units' => [
        'bytes' => 'Bytes',
        'kb' => 'KB',
        'mb' => 'MB',
        'gb' => 'GB',
        'tb' => 'TB',
    ],
    // === Supported Extensions ===
    'extensions' => [
        'images' => 'jpg, jpeg, png, gif, webp, svg',
        'documents' => 'pdf, doc, docx, xls, xlsx, ppt, pptx, txt',
        'videos' => 'mp4, avi, mov, wmv, flv, webm',
        'audio' => 'mp3, wav, ogg, flac, aac',
        'archives' => 'zip, rar, 7z, tar, gz',
    ],
    // === Configuration ===
    'config' => [
        'max_file_size' => 'Maximum file size: :size',
        'allowed_types' => 'Allowed file types: :types',
        'upload_path' => 'Upload directory: :path',
        'max_files' => 'Maximum files: :count',
    ],
    // === Placeholders ===
    'placeholders' => [
        'select_file' => 'Select a file...',
        'search_files' => 'Search files...',
        'file_name' => 'Enter file name...',
    ],
    // === Empty States ===
    'empty' => [
        'no_files' => 'No files found',
        'no_results' => 'No files match your search criteria',
        'upload_first' => 'Upload your first file to get started',
    ],
    // === Helper Text ===
    'help' => [
        'upload_multiple' => 'You can select multiple files by holding down the CTRL key',
        'drag_drop' => 'Drag and drop files here to upload',
        'file_format' => 'Supported formats: :formats',
        'max_size' => 'Maximum file size: :size',
    ],
    // === Confirmation Messages ===
    'confirmations' => [
        'delete_single' => 'Are you sure you want to delete this file?',
        'delete_multiple' => 'Are you sure you want to delete :count selected files?',
        'replace' => 'A file with this name already exists. Do you want to replace it?',
    ],
    // === Progress Messages ===
    'progress' => [
        'uploading_file' => 'Uploading :filename...',
        'processing_file' => 'Processing :filename...',
        'validating_file' => 'Validating :filename...',
        'moving_file' => 'Moving :filename...',
    ],
];