<?php
/**
 * Gallery Module Integration - English Language File
 *
 * @package Rojname
 * @module  mod_gallery_mod
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Gallery Module Integration Language Translations
 */
$lang['mod_gallery_mod'] = [

    // === Basic Navigation ===
    'list' => 'Gallery Module List',
    'add' => 'Add Gallery Module',
    'edit' => 'Edit Gallery Module',
    'information' => 'Information',
    'oke' => 'Success',
    'hata' => 'Error Occurred',
    'error' => 'Fatal Error Occurred',

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Add Gallery Module',
        'edit' => 'Edit Gallery Module',
        'list' => 'Gallery Module List',
        'delete' => 'Delete Gallery Module',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'update' => 'Update',
        'remove' => 'Remove',
    ],

    // === Message Structure ===
    'message' => [
        'oke' => [
            'add' => 'Gallery Module successfully added!',
            'edit' => 'Gallery Module successfully updated!',
        ],
        'error' => [
            'add' => 'Failed to add Gallery Module!',
            'edit' => 'Failed to update Gallery Module!',
        ],
    ],

    'errors' => [
        'record' => 'The specified record could not be found!',
    ],

    'button' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'yenile' => 'Refresh',
        'delete' => 'Delete',
        'vazgec' => 'Cancel',
        'kaldir' => 'Remove',
        'evet' => 'Yes',
        'hayir' => 'No',
    ],

    'grid' => [
        0 => 'Showing {from} to {to} of {total} results',
        1 => 'Page',
        2 => 'Page Count',
        3 => 'Loading data, please wait...',
        4 => 'No data found',
        5 => 'Connection Error',
        6 => 'Search',
        7 => 'Please select at least one row',
        8 => 'Please select only one row',
    ],

    'validator' => [
        'required' => 'Please do not leave the %s field empty',
        'length' => '%s must be between %s and %s characters',
        'email' => 'Please enter a valid email address in the %s field',
        'unique' => 'The %s you entered is already registered in the system. Please specify a different %s!',
        'inarray' => '%s field can only accept \' %s \' values',
    ],

    'roll' => [
        'ad' => 'Gallery Module Management Roles',
        'add' => 'Can Add Gallery Module',
        'edit' => 'Can Edit Gallery Module',
        'delete' => 'Can Delete Gallery Module',
        'list' => 'Can View Gallery Module Management',
        'moderate_edit' => 'Can edit Gallery Module records belonging to others',
        'moderate_delete' => 'Can delete Gallery Module records belonging to others',
    ],

    'role_errors' => [
        'moderate_edit' => 'You cannot edit Gallery Module records belonging to others',
        'moderate_delete' => 'You cannot delete Gallery Module records belonging to others',
        'add' => 'You do not have permission to add Gallery Modules',
        'edit' => 'You do not have permission to edit Gallery Modules',
        'list' => 'You do not have permission to view Gallery Module Management',
        'delete' => 'You do not have permission to delete Gallery Modules',
    ],

    'admin' => [
        'columns' => [
            'id' => 'ID',
            'ad' => 'Gallery Module Name',
            'mod_name' => 'Module Name',
            'mod_key' => 'Module Key (ID)',
            'mod_value' => 'Module Value (Name)',
        ],
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Gallery Module successfully added!',
            'updated' => 'Gallery Module successfully updated!',
            'deleted' => 'Gallery Module successfully deleted!',
            'configured' => 'Gallery Module configured successfully!',
        ],
        'error' => [
            'not_added' => 'Failed to add Gallery Module!',
            'not_updated' => 'Failed to update Gallery Module!',
            'not_deleted' => 'Failed to delete Gallery Module!',
            'not_found' => 'The specified Gallery Module record could not be found!',
            'configuration_failed' => 'Gallery Module configuration failed!',
        ],
        'info' => [
            'module_intro' => 'Gallery modules provide image gallery capabilities to other system components',
            'configuration_info' => 'Configure gallery module settings to enable image operations in specific modules',
        ],
    ],

    // === Gallery Module Configuration ===
    'configuration' => [
        'basic_settings' => 'Basic Settings',
        'display_settings' => 'Display Settings',
        'advanced_settings' => 'Advanced Settings',
        'integration_settings' => 'Integration Settings',

        'module_name' => 'Module Name',
        'module_description' => 'Module Description',
        'gallery_type' => 'Gallery Type',
        'display_mode' => 'Display Mode',
        'image_size' => 'Image Size',
        'thumbnail_size' => 'Thumbnail Size',
        'columns_per_row' => 'Columns per Row',
        'auto_slideshow' => 'Auto Slideshow',
        'slideshow_interval' => 'Slideshow Interval (seconds)',
        'show_captions' => 'Show Captions',
        'enable_lightbox' => 'Enable Lightbox',
        'enable_zoom' => 'Enable Zoom',
        'enable_download' => 'Enable Download',
    ],

    // === Gallery Types ===
    'gallery_types' => [
        'image_gallery' => 'Image Gallery',
        'photo_album' => 'Photo Album',
        'portfolio' => 'Portfolio',
        'product_gallery' => 'Product Gallery',
        'showcase' => 'Showcase',
        'slider_gallery' => 'Slider Gallery',
        'masonry' => 'Masonry Layout',
        'grid' => 'Grid Layout',
    ],

    // === Display Modes ===
    'display_modes' => [
        'grid' => 'Grid',
        'list' => 'List',
        'masonry' => 'Masonry',
        'carousel' => 'Carousel',
        'slider' => 'Slider',
        'lightbox' => 'Lightbox',
        'modal' => 'Modal',
        'inline' => 'Inline',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'remove_record' => 'Are you sure you want to delete this record?',
        'delete_single' => 'Are you sure you want to delete this Gallery Module?',
        'delete_multiple' => 'Are you sure you want to delete :count selected Gallery Modules?',
        'reset_configuration' => 'Are you sure you want to reset the module configuration?',
        'clear_gallery' => 'Are you sure you want to clear all images from this gallery?',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'refresh' => 'Refresh',
        'delete' => 'Delete',
        'cancel' => 'Cancel',
        'remove' => 'Remove',
        'yes' => 'Yes',
        'no' => 'No',
        'configure' => 'Configure',
        'preview' => 'Preview',
        'test' => 'Test',
        'reset' => 'Reset',
    ],

    // === Data Grid Messages ===
    'grid' => [
        'pagination' => 'Showing {from} to {to} of {total} results',
        'page' => 'Page',
        'page_count' => 'Page Count',
        'loading' => 'Loading data, please wait...',
        'no_data' => 'No data found',
        'connection_error' => 'Connection Error',
        'search' => 'Search',
        'select_at_least_one' => 'Please select at least one row',
        'select_only_one' => 'Please select only one row',
    ],

    // === Validation Messages ===
    'validation' => [
        'required' => 'Please do not leave the %s field empty',
        'length' => '%s must be between %s and %s characters',
        'email' => 'Please enter a valid email address in the %s field',
        'unique' => 'The %s you entered is already registered in the system. Please specify a different %s!',
        'inarray' => '%s field can only accept \' %s \' values',
        'module_name_required' => 'Module name is required',
        'gallery_type_required' => 'Gallery type selection is required',
        'invalid_image_size' => 'Invalid image size specified',
        'configuration_invalid' => 'Module configuration is invalid',
    ],

    // === Access Control (ACL) ===
    'permissions' => [
        'title' => 'Gallery Module Management Permissions',
        'add' => 'Can Add Gallery Module',
        'edit' => 'Can Edit Gallery Module',
        'delete' => 'Can Delete Gallery Module',
        'list' => 'Can View Gallery Module Management',
        'configure' => 'Can Configure Gallery Modules',
        'preview' => 'Can Preview Gallery Modules',
        'test' => 'Can Test Gallery Modules',
        'edit_others' => 'Can edit Gallery Module records belonging to others',
        'delete_others' => 'Can delete Gallery Module records belonging to others',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'edit_others' => 'You cannot edit Gallery Module records belonging to others',
        'delete_others' => 'You cannot delete Gallery Module records belonging to others',
        'add' => 'You do not have permission to add Gallery Modules',
        'edit' => 'You do not have permission to edit Gallery Modules',
        'list' => 'You do not have permission to view Gallery Module Management',
        'delete' => 'You do not have permission to delete Gallery Modules',
        'configure' => 'You do not have permission to configure Gallery Modules',
        'preview' => 'You do not have permission to preview Gallery Modules',
        'test' => 'You do not have permission to test Gallery Modules',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'Gallery Module Name',
            'mod_name' => 'Module Name',
            'mod_key' => 'Module Key (ID)',
            'mod_value' => 'Module Value (Name)',
            'gallery_type' => 'Gallery Type',
            'display_mode' => 'Display Mode',
            'status' => 'Status',
            'created_date' => 'Created Date',
            'modified_date' => 'Modified Date',
            'actions' => 'Actions',
        ],
    ],

    // === Image Management ===
    'image_management' => [
        'upload_images' => 'Upload Images',
        'manage_images' => 'Manage Images',
        'bulk_upload' => 'Bulk Upload',
        'drag_drop' => 'Drag & Drop Images',
        'reorder_images' => 'Reorder Images',
        'crop_images' => 'Crop Images',
        'resize_images' => 'Resize Images',
        'optimize_images' => 'Optimize Images',
        'image_quality' => 'Image Quality',
        'compression_level' => 'Compression Level',
        'auto_optimize' => 'Auto Optimize',
    ],

    // === Gallery Features ===
    'features' => [
        'lightbox_enabled' => 'Lightbox Enabled',
        'zoom_enabled' => 'Zoom Enabled',
        'fullscreen_enabled' => 'Fullscreen Enabled',
        'thumbnails_enabled' => 'Thumbnails Enabled',
        'captions_enabled' => 'Captions Enabled',
        'navigation_enabled' => 'Navigation Enabled',
        'keyboard_navigation' => 'Keyboard Navigation',
        'touch_gestures' => 'Touch Gestures',
        'lazy_loading' => 'Lazy Loading',
        'infinite_scroll' => 'Infinite Scroll',
    ],

    // === Display Settings ===
    'display_settings' => [
        'gallery_width' => 'Gallery Width',
        'gallery_height' => 'Gallery Height',
        'image_spacing' => 'Image Spacing',
        'border_radius' => 'Border Radius',
        'shadow_effect' => 'Shadow Effect',
        'hover_effect' => 'Hover Effect',
        'transition_effect' => 'Transition Effect',
        'animation_speed' => 'Animation Speed',
        'background_color' => 'Background Color',
        'border_color' => 'Border Color',
    ],

    // === Success Messages ===
    'success_messages' => [
        'operation_completed' => 'Operation completed successfully!',
        'changes_saved' => 'Changes saved successfully!',
        'images_uploaded' => ':count images uploaded successfully!',
        'gallery_configured' => 'Gallery configured successfully!',
        'test_passed' => 'Gallery test passed successfully!',
        'preview_ready' => 'Gallery preview is ready!',
    ],

    // === Error Messages ===
    'error_messages' => [
        'general' => 'An error occurred. Please try again.',
        'network' => 'Network error. Please check your internet connection.',
        'server' => 'Server error. Please try again later.',
        'upload_failed' => 'Image upload failed',
        'configuration_error' => 'Configuration error occurred',
        'test_failed' => 'Gallery test failed',
        'module_not_found' => 'Gallery Module not found',
        'invalid_configuration' => 'Invalid configuration',
    ],

    // === Helper Text ===
    'help' => [
        'module_intro' => 'Gallery modules provide image gallery capabilities to other system components',
        'configuration_help' => 'Configure module settings to enable proper image gallery integration',
        'upload_help' => 'Upload images to populate your gallery',
        'display_help' => 'Customize how your gallery appears to users',
        'feature_help' => 'Enable advanced features to enhance user experience',
    ],

    // === Empty States ===
    'empty_states' => [
        'no_modules' => 'No Gallery Modules configured yet',
        'no_results' => 'No modules found matching your search criteria',
        'no_images' => 'No images in this gallery yet',
        'add_first_module' => 'Add your first Gallery Module to get started',
        'upload_first_image' => 'Upload your first image to create a gallery',
    ],

    // === Loading States ===
    'loading_states' => [
        'loading_modules' => 'Loading modules...',
        'uploading_images' => 'Uploading images...',
        'processing_images' => 'Processing images...',
        'configuring' => 'Configuring...',
        'previewing' => 'Generating preview...',
        'testing' => 'Testing gallery...',
    ],

];