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
    'video_management' => 'Video Management',

    // System Messages
    'information' => 'Information',
    'success' => 'Operation Successful',
    'error' => 'Error Occurred',

    // Image Management
    'remove_image' => 'Are you sure you want to remove this image?',
    'remove_image_success' => 'Image Removed',
    'choose_wish' => 'Select if you wish',

    // Record Management
    'remove_record' => 'Are you sure you want to delete this record?',

    // Video Processing
    'converting' => 'Video is being converted, please wait...',
    'converted' => 'Video upload complete! You can now convert the video.',
    'add_remote' => 'Add this video to the system as a remote video',
    'unremove_original' => 'Save the original version of this video',

    // Video Conversion Messages
    'video_gallery' => 'Errors occurred during video conversion: %s',
    'video_list' => 'Video conversion completed.<br>This operation took %s seconds.',

    // Success Messages
    'message' => [
        'success' => [
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
        'title' => 'Error occurred while moving FLV file from TMP directory',
        'subtitle' => 'Error occurred while moving image file from TMP directory',
        'description' => 'Error occurred while moving original video file from TMP directory',
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
        'refresh' => 'Refresh',
        'delete' => 'Delete',
        'gallery' => 'Image Gallery',
        'cancel' => 'Cancel',
        'remove' => 'Remove',
        'yes' => 'Yes',
        'no' => 'No',
        'upload' => 'Upload',
        'convert' => 'Convert',
    ],

    // Data Grid Messages
    'grid' => [
        'showing_results' => 'Displaying {from} to {to} of {total} results',
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
            'value' => 'Are you sure you want to publish this video?',
        ],
        'unpublish' => [
            'key' => 'Unpublish',
            'value' => 'Are you sure you want to unpublish this video?',
        ],
        'publishing' => 'Publishing...',
        'unpublishing' => 'Unpublishing...',
        'delete_confirm_title' => 'Are you sure?',
        'delete_confirm_text' => 'The selected video will be deleted.',
    ],
    'js_enable' => 'JavaScript is required to view this video.',

    // Form Validation Messages
    'validator' => [
        'required' => 'Please do not leave the %s field empty',
        'length' => '%s can be minimum %s and maximum %s characters',
        'email' => 'Please enter a valid email address in the %s field',
        'unique' => 'The %s you entered is already registered in the system. Please choose another %s!',
        'inarray' => 'The %s field can only accept \'%s\' values',
    ],

    // Role Management
    'role' => [
        'name' => 'Video Management Roles',
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
            'lang' => 'Display Language',
            'name' => 'Video Title',
            'duration' => 'Duration',
            'created_at' => 'Date',
            'status' => 'Publish Status',
            'description' => 'Description',
            'image' => 'Thumbnail',
            'preview' => 'Preview',
            'acl_id' => 'Who can view this video? <br><a href=\'mod_acl/List/\' target=\'_blank\' class=\'btn btn-xs blue\' style=\'margin-top:5px;\'><i class=\'fa fa-external-link\'></i> ACL - Access Control List</a>',
        ],
        'radio' => [
            'publish_yes' => 'Yes',
            'publish_no' => 'No',
        ],
    ],
     'placeholder' => [
        'name' => 'Video title...',
        'description' => 'A short description of the video content and purpose...',
    ],
    'empty_state' => 'No uploaded videos found',

    // Extended Video Management Features
    'upload' => [
        'select_file' => 'Select Video File',
        'drag_drop' => 'Select or Drag',
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
        'upload_tips' => 'Tips: Use high-quality videos.',
        'conversion_info' => 'This process may take a few minutes.',
        'seo_tips' => 'Use descriptive titles and descriptions.',
        'embed_help' => 'Copy and paste this code to embed the video.',
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
    'role' => [
        'name' => 'Video Management Roles',
        'add' => 'Can Add Videos',
        'edit' => 'Can Edit Videos',
        'delete' => 'Can Delete Videos',
        'list' => 'Can View Video Management',
        'acl' => 'Can Edit Video Permissions',
        'setting' => 'Can Change Video Settings',
        'link' => 'Can View Video Links'
    ],
    // Wizard Steps Instructions
    'wizard' => [
        'step1_title' => 'Select Video',
        'step1_instruction' => 'Select a video file to upload.',
        'step2_title' => 'Convert Video',
        'step2_instruction' => 'Convert video for web playback.',
        'step3_title' => 'Save Video',
        'step3_instruction' => 'Fill in details and save.',
    ],
    'context_menu' => [
        'title' => 'Video Actions',
        'edit' => 'Edit',
        'gallery' => 'Gallery Operations',
        'files' => 'File Operations',
        'video' => 'Add Video to Video',
        'status' => 'Change Status',
        'delete' => 'Delete',
        'delete_error' => 'Deletion failed!',
        'video_success_sim' => 'Video Successfully Added (Simulation)',
        'video_embed_error' => 'Invalid Embed Code',
        'video_title_error' => 'Invalid Title',
        'publish' => 'Publish',
        'unpublish' => 'Unpublish',
        'published_msg' => 'Published',
        'unpublished_msg' => 'Unpublished',
        'confirm' => 'Yes',
        'cancel' => 'Cancel',
        'edit_alert' => "EDIT MODE:\nID: %s\nTitle: %s",
        'gallery_alert' => "GALLERY MANAGEMENT:\nID: %s\nTitle: %s\nOpening image gallery for video.",
        'files_alert' => "FILE MANAGEMENT:\nID: %s\nTitle: %s\nOpening file upload screen for video.",
        'status_alert' => "STATUS UPDATED:\n%s",
        'delete_success' => 'Video deleted!',
    ],
    'status_badge' => [
        'active' => 'Active',
        'draft' => 'Draft',
    ],
    'sheet' => [
        'all' => 'All',
        'lang_title' => 'Filter Language',
        'ok' => 'OK',
        'category_title' => 'Select Category',
    ],
    // === Hero Slider ===
    'hero_slider_add' => 'Send to Slider',
    'hero_slider_remove' => 'Remove from Slider',
    'hero_slider_added' => 'Video sent to slider.',
    'hero_slider_removed' => 'Video removed from slider.',
    'hero_slider_error' => 'Slider update failed.',
    'hero_slider_badge' => 'Slider',
];
?>