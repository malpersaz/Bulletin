<?php
/**
 * Video Management Module English Language File
 * Amed Framework CMS - Multilingual Content Management System
 *
 * This file contains all English translations for the video management module.
 * Professional translations with modern CMS terminology and video processing features.
 */

$lang['mod_video'] = [
    // Basic Operations
    'add' => 'Add Video',
    'edit' => 'Edit Video',
    'list' => 'Video List',

    // System Messages
    'information' => 'Information',
    'oke' => 'Operation Successful',
    'hata' => 'Error Occurred',
    'error' => 'Fatal Error Occurred',

    // Image Management
    'remove_image' => 'Are you sure you want to remove this image?',
    'remove_image_oke' => 'Image Removed',
    'choose_wish' => 'Select if you wish',

    // Record Management
    'remove_record' => 'Are you sure you want to delete this record?',

    // Video Processing
    'converting' => 'Video is being converted, please wait...',
    'converted' => 'Video upload complete! You can now convert the video.',
    'add_uzak' => 'Add this video to the system as a remote video',
    'unremove_original' => 'Save the original version of this video',

    // Video Conversion Messages
    19 => 'Errors occurred during video conversion: %s',
    20 => 'Video conversion completed.<br>This operation took %s seconds.',

    // Success Messages
    'message' => [
        'oke' => [
            'add' => 'Video Added Successfully!',
            'edit' => 'Video Updated Successfully!',
        ],
        'error' => [
            'add' => 'Video Could Not Be Added!',
            'edit' => 'Video Could Not Be Updated!',
        ],
        'information' => [
            'moderate' => 'Double-click the content you want to edit',
            'move' => 'Drag and drop the content you want to reorder',
        ],
    ],

    // Error Messages
    'errors' => [
        1 => 'Error occurred while moving FLV file from TMP directory',
        2 => 'Error occurred while moving image file from TMP directory',
        3 => 'Error occurred while moving original video file from TMP directory',
        'record' => 'The specified record was not found!',
        'in_valid_ext' => 'You attempted to upload an invalid video file.
		Only <font color="black">%s</font> extension videos can be uploaded.',
        'in_valid_ext_original' => 'The original video you submitted is invalid.
		Only <font color="black">%s</font> extension videos can be uploaded.',
    ],

    // Button Labels
    'button' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'yenile' => 'Refresh',
        'delete' => 'Delete',
        'gallery' => 'Image Gallery',
        'vazgec' => 'Cancel',
        'kaldir' => 'Remove',
        'evet' => 'Yes',
        'hayir' => 'No',
        'yukle' => 'Upload',
        'donustur' => 'Convert',
    ],

    // Data Grid Messages
    'grid' => [
        0 => 'Displaying {from} to {to} of {total} results',
        1 => 'Page',
        2 => 'Page Count',
        3 => 'Loading data, please wait...',
        4 => 'No data found',
        5 => 'Connection Error',
        6 => 'Search',
        7 => 'Please select at least one row',
        8 => 'Please select only one row',
        'publish' => [
            'key' => 'Publish',
            'value' => 'Are you sure you want to publish this video?',
        ],
        'unpublish' => [
            'key' => 'Unpublish',
            'value' => 'Are you sure you want to unpublish this video?',
        ],
    ],

    // Form Validation Messages
    'validator' => [
        'required' => 'Please do not leave the %s field empty',
        'length' => '%s can be minimum %s and maximum %s characters',
        'email' => 'Please enter a valid email address in the %s field',
        'unique' => 'The %s you entered is already registered in the system. Please choose another %s!',
        'inarray' => 'The %s field can only accept \'%s\' values',
    ],

    // Role Management
    'roll' => [
        'ad' => 'Video Management Roles',
        'add' => 'Can add videos',
        'edit' => 'Can edit videos',
        'delete' => 'Can delete videos',
        'list' => 'Can view video management',
        'moderate_edit' => 'Can edit video records belonging to others',
        'moderate_delete' => 'Can delete video records belonging to others',
    ],

    // Role-based Error Messages
    'role_errors' => [
        'moderate_edit' => 'You cannot edit video records that do not belong to you',
        'moderate_delete' => 'You cannot delete video records that do not belong to you',
        'add' => 'You do not have permission to add videos',
        'edit' => 'You do not have permission to edit videos',
        'list' => 'You do not have permission to view video management',
        'delete' => 'You do not have permission to delete videos',
    ],

    // Admin Panel Labels
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Display Language',
            'ad' => 'Video Title',
            'sure' => 'Duration',
            'tarih' => 'Date',
            'status' => 'Publish Status',
            'aciklama' => 'Description',
            'image' => 'Thumbnail',
            'onizleme' => 'Preview',
            'acl_id' => 'Who can view this video? <br><a href=\'mod_acl/List/\' target=\'_blank\' class=\'btn btn-xs blue\' style=\'margin-top:5px;\'><i class=\'fa fa-external-link\'></i> ACL - Access Control List</a>',
        ],
        'radio' => [
            'publish_yes' => 'Yes',
            'publish_no' => 'No',
        ],
    ],

    // Extended Video Management Features
    'upload' => [
        'select_file' => 'Select Video File',
        'drag_drop' => 'Drag and drop video files here',
        'supported_formats' => 'Supported Formats: MP4, AVI, MOV, WMV, FLV, WebM',
        'max_file_size' => 'Maximum file size: %s',
        'upload_progress' => 'Upload Progress',
        'processing' => 'Processing video...',
        'conversion_queue' => 'Video added to conversion queue',
    ],

    // Video Quality Options
    'quality' => [
        'auto' => 'Auto Quality',
        'high' => 'High Quality',
        'medium' => 'Medium Quality',
        'low' => 'Low Quality',
        'original' => 'Original Quality',
    ],

    // Video Categories
    'category' => [
        'select' => 'Select Category',
        'all' => 'All Categories',
        'uncategorized' => 'Uncategorized',
        'add' => 'Add New Category',
    ],

    // Publishing Options
    'publishing' => [
        'immediately' => 'Publish Immediately',
        'schedule' => 'Schedule Publishing',
        'draft' => 'Save as Draft',
        'private' => 'Private',
        'password_protected' => 'Password Protected',
    ],

    // SEO Options
    'seo' => [
        'meta_title' => 'Meta Title',
        'meta_description' => 'Meta Description',
        'meta_keywords' => 'Meta Keywords',
        'friendly_url' => 'Friendly URL',
        'video_sitemap' => 'Include in Video Sitemap',
    ],

    // Advanced Features
    'advanced' => [
        'captions' => 'Captions/Subtitles',
        'chapters' => 'Video Chapters',
        'playlist' => 'Add to Playlist',
        'related_videos' => 'Related Videos',
        'comments' => 'Enable Comments',
        'rating' => 'Enable Rating',
        'embed' => 'Embed Code',
        'download' => 'Allow Download',
    ],

    // Video Statistics
    'statistics' => [
        'views' => 'Total Views',
        'unique_views' => 'Unique Views',
        'average_watch_time' => 'Average Watch Time',
        'completion_rate' => 'Completion Rate',
        'engagement' => 'Engagement Score',
        'shares' => 'Shares',
        'likes' => 'Likes',
        'dislikes' => 'Dislikes',
    ],

    // Bulk Operations
    'bulk' => [
        'select_all' => 'Select All',
        'deselect_all' => 'Deselect All',
        'bulk_edit' => 'Bulk Edit',
        'bulk_delete' => 'Bulk Delete',
        'bulk_publish' => 'Bulk Publish',
        'bulk_unpublish' => 'Bulk Unpublish',
        'export' => 'Export Videos',
        'import' => 'Import Videos',
    ],

    // Notification Messages
    'notifications' => [
        'upload_complete' => 'Video uploaded successfully',
        'conversion_started' => 'Video conversion has started',
        'conversion_complete' => 'Video conversion completed',
        'conversion_failed' => 'Video conversion failed',
        'publish_scheduled' => 'Video scheduled for publishing',
        'video_published' => 'Video published successfully',
        'video_unpublished' => 'Video unpublished successfully',
    ],

    // Help Text
    'help' => [
        'upload_tips' => 'Tips: Use high-quality videos for better user experience. Large files may take longer to upload and process.',
        'conversion_info' => 'Video conversion optimizes your videos for web playback. This may take several minutes depending on file size.',
        'seo_tips' => 'Use descriptive titles and descriptions to improve search engine visibility.',
        'embed_help' => 'Copy and paste this code to embed the video on other websites.',
    ],

    // Error Categories
    'error_types' => [
        'upload_error' => 'Upload Error',
        'conversion_error' => 'Conversion Error',
        'permission_error' => 'Permission Error',
        'validation_error' => 'Validation Error',
        'system_error' => 'System Error',
        'network_error' => 'Network Error',
    ],

    // Status Indicators
    'status' => [
        'uploading' => 'Uploading...',
        'processing' => 'Processing...',
        'converting' => 'Converting...',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'pending' => 'Pending',
        'published' => 'Published',
        'unpublished' => 'Unpublished',
        'scheduled' => 'Scheduled',
        'draft' => 'Draft',
    ],

    // Time Formats
    'time_formats' => [
        'seconds' => 'seconds',
        'minutes' => 'minutes',
        'hours' => 'hours',
        'ago' => 'ago',
        'remaining' => 'remaining',
        'estimated_time' => 'Estimated time: %s',
    ],

    // File Information
    'file_info' => [
        'file_name' => 'File Name',
        'file_size' => 'File Size',
        'file_type' => 'File Type',
        'duration' => 'Duration',
        'resolution' => 'Resolution',
        'bitrate' => 'Bitrate',
        'fps' => 'Frame Rate',
        'codec' => 'Codec',
    ],

    // API Integration
    'api' => [
        'embed_url' => 'Embed URL',
        'api_endpoint' => 'API Endpoint',
        'api_key' => 'API Key',
        'webhook_url' => 'Webhook URL',
        'third_party' => 'Third Party Integration',
    ],

    // Moderation Tools
    'moderation' => [
        'pending_approval' => 'Pending Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'flagged' => 'Flagged Content',
        'review_queue' => 'Review Queue',
        'moderator_notes' => 'Moderator Notes',
    ],

    // Export/Import Options
    'data_management' => [
        'export_csv' => 'Export as CSV',
        'export_xml' => 'Export as XML',
        'export_json' => 'Export as JSON',
        'import_csv' => 'Import from CSV',
        'backup_videos' => 'Backup Videos',
        'restore_videos' => 'Restore Videos',
    ],

    // Search and Filter
    'search_filters' => [
        'search_by_title' => 'Search by Title',
        'search_by_description' => 'Search by Description',
        'filter_by_date' => 'Filter by Date',
        'filter_by_category' => 'Filter by Category',
        'filter_by_author' => 'Filter by Author',
        'filter_by_status' => 'Filter by Status',
        'advanced_search' => 'Advanced Search',
    ],

    // User Interface
    'ui' => [
        'dashboard' => 'Video Dashboard',
        'overview' => 'Overview',
        'recent_videos' => 'Recent Videos',
        'popular_videos' => 'Popular Videos',
        'trending_videos' => 'Trending Videos',
        'featured_videos' => 'Featured Videos',
        'archive' => 'Video Archive',
        'trash' => 'Trash',
    ],

    // === Permission Roles ===
    'roll' => [
        'ad' => 'Video Management Roles',
        'add' => 'Can Add Videos',
        'edit' => 'Can Edit Videos',
        'delete' => 'Can Delete Videos',
        'list' => 'Can View Video Management',
        'acl' => 'Can Edit Video Permissions',
        'setting' => 'Can Change Video Settings',
        'link' => 'Can View Video Links'
    ],
];
?>