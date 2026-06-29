<?php
/**
 * Featured Content/Headline Management - English Language File
 *
 * @package AKM Business
 * @module  mod_headline
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Featured Content/Headline Management Language Translations
 */
$lang['mod_headline'] = [

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Add Featured Content',
        'edit' => 'Edit Featured Content',
        'list' => 'Featured Content List',
        'delete' => 'Delete Featured Content',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'update' => 'Update',
        'remove' => 'Remove',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Featured Content successfully added!',
            'updated' => 'Featured Content successfully updated!',
            'deleted' => 'Featured Content successfully deleted!',
            'published' => 'Featured Content published successfully!',
            'unpublished' => 'Featured Content unpublished successfully!',
            'image_removed' => 'Image removed successfully!',
            'reordered' => 'Content order updated successfully!',
        ],
        'error' => [
            'not_added' => 'Failed to add Featured Content!',
            'not_updated' => 'Failed to update Featured Content!',
            'not_deleted' => 'Failed to delete Featured Content!',
            'not_found' => 'The specified Featured Content record could not be found!',
            'image_upload_failed' => 'Image upload failed!',
            'invalid_image_format' => 'Invalid image format!',
            'file_too_large' => 'File size exceeds maximum limit!',
        ],
        'info' => [
            'edit_double_click' => 'Double-click the content you want to edit',
            'drag_reorder' => 'Drag and drop the content to change its order',
            'image_requirements' => 'Featured image should be high quality and properly sized',
        ],
    ],

    // === Content Types ===
    'content_types' => [
        'headline' => 'Headline',
        'featured_article' => 'Featured Article',
        'hero_banner' => 'Hero Banner',
        'spotlight' => 'Spotlight',
        'highlight' => 'Highlight',
        'showcase' => 'Showcase',
        'breaking_news' => 'Breaking News',
        'featured_story' => 'Featured Story',
    ],

    // === Status Types ===
    'status' => [
        'published' => 'Published',
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'archived' => 'Archived',
        'featured' => 'Featured',
        'pinned' => 'Pinned',
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Yes',
        'no' => 'No',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'remove_image' => 'Are you sure you want to remove this image?',
        'remove_record' => 'Are you sure you want to delete this record?',
        'delete_single' => 'Are you sure you want to delete this Featured Content?',
        'delete_multiple' => 'Are you sure you want to delete :count selected Featured Content items?',
        'publish' => 'Are you sure you want to publish this Featured Content?',
        'unpublish' => 'Are you sure you want to unpublish this Featured Content?',
        'feature' => 'Are you sure you want to feature this content?',
        'unfeature' => 'Are you sure you want to unfeature this content?',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'refresh' => 'Refresh',
        'delete' => 'Delete',
        'gallery' => 'Image Gallery',
        'cancel' => 'Cancel',
        'remove' => 'Remove',
        'yes' => 'Yes',
        'no' => 'No',
        'preview' => 'Preview',
        'feature' => 'Feature',
        'pin' => 'Pin',
        'unpin' => 'Unpin',
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
        'publish' =>
     [
      'key' => 'Publish',
      'value' => 'Are you sure you want to publish this headline?',
    ],
    'unpublish' =>
     [
      'key' => 'Unpublish',
      'value' => 'Are you sure you want to unpublish this headline?',
    ],
  ],

    // === Validation Messages ===
    'validation' => [
        'required' => 'Please do not leave the %s field empty',
        'length' => '%s must be between %s and %s characters',
        'email' => 'Please enter a valid email address in the %s field',
        'unique' => 'The %s you entered is already registered in the system. Please specify a different %s!',
        'inarray' => '%s field can only accept \' %s \' values',
        'title_required' => 'Content title is required',
        'image_required' => 'Featured image is required',
        'invalid_image_size' => 'Image size must be 2000x750 pixels',
        'invalid_file_type' => 'Invalid file type. Only JPG, PNG, GIF allowed',
    ],

    // === Access Control (ACL) ===
    // === Access Control (ACL) ===
    'roll' => [
        'ad' => 'Featured Content Management Permissions',
        'add' => 'Can Add Featured Content',
        'edit' => 'Can Edit Featured Content',
        'delete' => 'Can Delete Featured Content',
        'list' => 'Can View Featured Content Management',
        'publish' => 'Can Publish Featured Content',
        'feature' => 'Can Feature Content',
        'moderate_edit' => 'Can edit Featured Content records belonging to others',
        'moderate_delete' => 'Can delete Featured Content records belonging to others',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'edit_others' => 'You cannot edit Featured Content records belonging to others',
        'delete_others' => 'You cannot delete Featured Content records belonging to others',
        'add' => 'You do not have permission to add Featured Content',
        'edit' => 'You do not have permission to edit Featured Content',
        'list' => 'You do not have permission to view Featured Content Management',
        'delete' => 'You do not have permission to delete Featured Content',
        'publish' => 'You do not have permission to publish Featured Content',
        'feature' => 'You do not have permission to feature content',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Language',
            'name' => 'Featured Content Name',
            'published' => 'Published?',
            'date' => 'Date',
            'image' => 'Image (should be 2000x750 format)',
        ],
        'radio' => [
            'published_yes' => 'Yes',
            'published_no' => 'No',
        ],
    ],

    // === Content Settings ===
    'content_settings' => [
        'basic_info' => 'Basic Information',
        'content_details' => 'Content Details',
        'media_settings' => 'Media Settings',
        'publishing_settings' => 'Publishing Settings',
        'seo_settings' => 'SEO Settings',
        'advanced_settings' => 'Advanced Settings',

        'title' => 'Title',
        'subtitle' => 'Subtitle',
        'slug' => 'URL Slug',
        'content' => 'Content',
        'excerpt' => 'Excerpt',
        'summary' => 'Summary',
        'featured_image' => 'Featured Image',
        'thumbnail' => 'Thumbnail',
        'author' => 'Author',
        'publish_date' => 'Publish Date',
        'expiry_date' => 'Expiry Date',
    ],

    // === Image Management ===
    'image_management' => [
        'upload_image' => 'Upload Image',
        'select_image' => 'Select Image',
        'crop_image' => 'Crop Image',
        'resize_image' => 'Resize Image',
        'optimize_image' => 'Optimize Image',
        'image_requirements' => 'Image Requirements',
        'recommended_size' => 'Recommended Size: 2000x750 pixels',
        'max_file_size' => 'Maximum File Size: 5MB',
        'supported_formats' => 'Supported Formats: JPG, PNG, GIF',
        'alt_text' => 'Alt Text',
        'image_caption' => 'Image Caption',
        'image_credit' => 'Image Credit',
    ],

    // === Display Options ===
    'display_options' => [
        'layout_options' => 'Layout Options',
        'position_options' => 'Position Options',
        'size_options' => 'Size Options',
        'animation_options' => 'Animation Options',
        'color_scheme' => 'Color Scheme',
        'typography' => 'Typography',
        'spacing' => 'Spacing',
        'borders' => 'Borders',
        'shadows' => 'Shadows',
        'background' => 'Background',
    ],

    // === Layout Types ===
    'layout_types' => [
        'full_width' => 'Full Width',
        'half_width' => 'Half Width',
        'grid_layout' => 'Grid Layout',
        'masonry_layout' => 'Masonry Layout',
        'carousel_layout' => 'Carousel Layout',
        'stacked_layout' => 'Stacked Layout',
        'overlay_layout' => 'Overlay Layout',
        'sidebar_layout' => 'Sidebar Layout',
    ],

    // === Position Options ===
    'position_options' => [
        'top' => 'Top',
        'middle' => 'Middle',
        'bottom' => 'Bottom',
        'left' => 'Left',
        'right' => 'Right',
        'center' => 'Center',
        'full_screen' => 'Full Screen',
        'above_content' => 'Above Content',
        'below_content' => 'Below Content',
    ],

    // === SEO Features ===
    'seo_features' => [
        'meta_title' => 'Meta Title',
        'meta_description' => 'Meta Description',
        'meta_keywords' => 'Meta Keywords',
        'og_title' => 'Open Graph Title',
        'og_description' => 'Open Graph Description',
        'og_image' => 'Open Graph Image',
        'twitter_card' => 'Twitter Card',
        'canonical_url' => 'Canonical URL',
        'schema_markup' => 'Schema Markup',
        'structured_data' => 'Structured Data',
    ],

    // === Publishing Workflow ===
    'publishing' => [
        'save_draft' => 'Save as Draft',
        'schedule_publish' => 'Schedule Publishing',
        'publish_noow' => 'Publish Now',
        'publish_later' => 'Publish Later',
        'expiry_options' => 'Expiry Options',
        'never_expires' => 'Never Expires',
        'expires_on' => 'Expires On',
        'revision_history' => 'Revision History',
        'preview_mode' => 'Preview Mode',
    ],

    // === Content Categories ===
    'categories' => [
        'select_category' => 'Select Category',
        'all_categories' => 'All Categories',
        'uncategorized' => 'Uncategorized',
        'news' => 'News',
        'blog' => 'Blog',
        'portfolio' => 'Portfolio',
        'products' => 'Products',
        'services' => 'Services',
        'events' => 'Events',
        'announcements' => 'Announcements',
    ],

    // === Success Messages ===
    'success_messages' => [
        'operation_completed' => 'Operation completed successfully!',
        'changes_saved' => 'Changes saved successfully!',
        'content_published' => 'Content published successfully!',
        'content_unpublished' => 'Content unpublished successfully!',
        'content_featured' => 'Content featured successfully!',
        'image_uploaded' => 'Image uploaded successfully!',
        'content_reordered' => 'Content reordered successfully!',
    ],

    // === Error Messages ===
    'error_messages' => [
        'general' => 'An error occurred. Please try again.',
        'network' => 'Network error. Please check your internet connection.',
        'server' => 'Server error. Please try again later.',
        'upload_failed' => 'File upload failed',
        'processing_failed' => 'Image processing failed',
        'validation_failed' => 'Validation failed',
        'permission_denied' => 'Permission denied',
        'file_corrupted' => 'File appears to be corrupted',
        'size_limit_exceeded' => 'File size exceeds maximum limit',
    ],

    // === Helper Text ===
    'help' => [
        'content_title' => 'Enter a compelling title for your featured content',
        'content_subtitle' => 'Add a subtitle to provide more context',
        'featured_image' => 'Upload a high-quality image that represents your content',
        'image_requirements' => 'Use high-quality images with 2000x750 pixels for best results',
        'seo_optimization' => 'Optimize your content for better search engine visibility',
        'publishing_options' => 'Choose when and how your content should be published',
        'display_settings' => 'Customize how your content appears on the website',
    ],

    // === Placeholders ===
    'placeholders' => [
        'content_title' => 'Enter content title...',
        'content_subtitle' => 'Enter subtitle...',
        'content_excerpt' => 'Enter brief description...',
        'meta_title' => 'Enter meta title...',
        'meta_description' => 'Enter meta description...',
        'meta_keywords' => 'keyword1, keyword2, keyword3',
        'alt_text' => 'Describe image for accessibility...',
        'image_caption' => 'Enter image caption...',
        'search_content' => 'Search featured content...',
    ],

    // === Empty States ===
    'empty_states' => [
        'no_content' => 'No featured content has been created yet',
        'no_results' => 'No content found matching your search criteria',
        'no_images' => 'No images in the gallery',
        'no_categories' => 'No categories available',
        'add_first_content' => 'Create your first featured content to get started',
        'upload_first_image' => 'Upload your first featured image',
    ],

    // === Loading States ===
    'loading_states' => [
        'loading_content' => 'Loading content...',
        'saving_changes' => 'Saving changes...',
        'uploading_image' => 'Uploading image...',
        'processing_image' => 'Processing image...',
        'publishing_content' => 'Publishing content...',
        'deleting_content' => 'Deleting content...',
    ],

    // === Preview Features ===
    'preview' => [
        'preview_desktop' => 'Preview Desktop',
        'preview_mobile' => 'Preview Mobile',
        'preview_tablet' => 'Preview Tablet',
        'live_preview' => 'Live Preview',
        'preview_mode' => 'Preview Mode',
        'exit_preview' => 'Exit Preview',
        'responsive_test' => 'Responsive Test',
    ],

];