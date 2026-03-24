<?php
/**
 * Image Category Management - English Language File
 *
 * @package AKM Business CMS
 * @module  mod_imagecategories
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Image Category Management Language Translations
 */
$lang['mod_imagecategories'] = [

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Add Gallery Category',
        'edit' => 'Edit Gallery Category',
        'list' => 'Gallery Category List',
        'delete' => 'Delete Gallery Category',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'update' => 'Update',
        'remove' => 'Remove',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Gallery Category successfully added!',
            'updated' => 'Gallery Category successfully updated!',
            'deleted' => 'Gallery Category successfully deleted!',
            'image_removed' => 'Image successfully removed!',
        ],
        'error' => [
            'not_added' => 'Failed to add Gallery Category!',
            'not_updated' => 'Failed to update Gallery Category!',
            'not_deleted' => 'Failed to delete Gallery Category!',
            'not_found' => 'The specified Gallery Category record could not be found!',
        ],
        'info' => [
            'edit_double_click' => 'Double-click the Gallery Category you want to edit',
            'drag_reorder' => 'Drag and drop the Gallery Category to change its order',
        ],
    ],

    // === Status Types ===
    'status' => [
        'published' => 'Published',
        'draft' => 'Draft',
        'archived' => 'Archived',
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
        'delete_single' => 'Are you sure you want to delete this Gallery Category?',
        'delete_multiple' => 'Are you sure you want to delete :count selected Gallery Categories?',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'cancel' => 'Cancel',
        'remove' => 'Remove',
        'yes' => 'Yes',
        'no' => 'No',
        'gallery' => 'Image Gallery',
    ],

    // === Validation Messages ===
    'validation' => [
        'required' => 'Please do not leave the %s field empty',
        'length' => '%s must be between %s and %s characters',
        'email' => 'Please enter a valid email address in the %s field',
        'unique' => 'The %s you entered is already registered in the system. Please specify a different %s!',
        'inarray' => '%s field can only accept \' %s \' values',
        'image' => 'Please upload a valid image file',
        'category_name_required' => 'Category name is required',
        'slug_required' => 'URL slug is required',
    ],

    // === Access Control (ACL) ===
    // === Access Control (ACL) ===
    'roll' => [
        'ad' => 'Gallery Category Management Permissions',
        'add' => 'Can Add Gallery Category',
        'edit' => 'Can Edit Gallery Category',
        'delete' => 'Can Delete Gallery Category',
        'gallery' => 'Can Add Images?',
        'list' => 'Can View Gallery Category Management',
        'moderate_edit' => 'Can edit Gallery Category records belonging to others',
        'moderate_delete' => 'Can delete Gallery Category records belonging to others',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'edit_others' => 'You cannot edit Gallery Category records belonging to others',
        'delete_others' => 'You cannot delete Gallery Category records belonging to others',
        'add' => 'You do not have permission to add Gallery Categories',
        'edit' => 'You do not have permission to edit Gallery Categories',
        'list' => 'You do not have permission to view Gallery Category Management',
        'delete' => 'You do not have permission to delete Gallery Categories',
        'gallery' => 'You do not have permission to add images to gallery',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Language',
            'name' => 'Gallery Name',
            'description' => 'Short Description',
            'parent_category' => 'Parent Gallery',
            'order' => 'Order',
            'is_root' => 'Root Category (Hidden from Display)?',
            'seo_url' => 'Gallery URL',
            'meta_title' => 'Title (for Search Engines)',
            'meta_keywords' => 'Keywords (for Search Engines)',
            'meta_description' => 'Description (for Search Engines)',
            'acl_id' => 'Who can view this Gallery? ( <a href=\'mod_acl/list/\' target=\'_blank\'>ACL - Access Control List</a> )',
            'menu' => 'Send Gallery Category to Menu',
            'image' => 'Image',
        ],
        'select_values' => [
            'root_yes' => 'Yes',
            'root_no' => 'No',
        ],
    ],

    // === Category Management ===
    'category_management' => [
        'hierarchy' => 'Category Hierarchy',
        'parent_child' => 'Parent-Child Relationships',
        'category_tree' => 'Category Tree',
        'nested_categories' => 'Nested Categories',
        'category_level' => 'Category Level',
        'root_categories' => 'Root Categories',
        'sub_categories' => 'Sub Categories',
        'category_depth' => 'Category Depth',
        'max_depth' => 'Maximum Depth Allowed',
    ],

    // === Image Settings ===
    'image_settings' => [
        'category_image' => 'Category Image',
        'thumbnail_size' => 'Thumbnail Size',
        'image_quality' => 'Image Quality',
        'auto_resize' => 'Auto Resize',
        'crop_images' => 'Crop Images',
        'maintain_aspect_ratio' => 'Maintain Aspect Ratio',
        'image_alt_text' => 'Image Alt Text',
        'image_title' => 'Image Title',
        'image_caption' => 'Image Caption',
    ],

    // === SEO Features ===
    'seo_features' => [
        'friendly_urls' => 'Friendly URLs',
        'auto_generate_slug' => 'Auto-generate URL Slug',
        'meta_tags' => 'Meta Tags',
        'structured_data' => 'Structured Data',
        'sitemap_inclusion' => 'Sitemap Inclusion',
        'canonical_url' => 'Canonical URL',
        'og_tags' => 'Open Graph Tags',
        'twitter_cards' => 'Twitter Cards',
    ],

    // === Display Options ===
    'display_options' => [
        'list_view' => 'List View',
        'grid_view' => 'Grid View',
        'tree_view' => 'Tree View',
        'show_image_count' => 'Show Image Count',
        'show_empty_categories' => 'Show Empty Categories',
        'hide_protected' => 'Hide Protected Categories',
        'sort_alphabetically' => 'Sort Alphabetically',
        'sort_by_order' => 'Sort by Order',
        'sort_by_date' => 'Sort by Date',
    ],

    // === Gallery Features ===
    'gallery_features' => [
        'image_gallery' => 'Image Gallery',
        'photo_album' => 'Photo Album',
        'portfolio' => 'Portfolio',
        'showcase' => 'Showcase',
        'lightbox_enabled' => 'Lightbox Enabled',
        'slideshow_enabled' => 'Slideshow Enabled',
        'comments_enabled' => 'Comments Enabled',
        'rating_enabled' => 'Rating Enabled',
        'social_sharing' => 'Social Sharing',
    ],

    // === Category Operations ===
    'category_operations' => [
        'create_category' => 'Create Category',
        'edit_category' => 'Edit Category',
        'delete_category' => 'Delete Category',
        'move_category' => 'Move Category',
        'merge_categories' => 'Merge Categories',
        'duplicate_category' => 'Duplicate Category',
        'export_category' => 'Export Category',
        'import_categories' => 'Import Categories',
        'reorder_categories' => 'Reorder Categories',
    ],

    // === Bulk Operations ===
    'bulk_operations' => [
        'select_all' => 'Select All',
        'deselect_all' => 'Deselect All',
        'delete' => 'Delete Selected',
        'move_selected' => 'Move Selected',
        'publish_selected' => 'Publish Selected',
        'unpublish_selected' => 'Unpublish Selected',
        'export_selected' => 'Export Selected',
        'bulk_edit' => 'Bulk Edit',
    ],

    // === Search and Filter ===
    'search_filter' => [
        'search_categories' => 'Search categories...',
        'filter_by_status' => 'Filter by Status',
        'filter_by_language' => 'Filter by Language',
        'filter_by_parent' => 'Filter by Parent',
        'filter_by_date' => 'Filter by Date',
        'show_all' => 'Show All',
        'active_only' => 'Active Only',
        'root_only' => 'Root Only',
        'with_images' => 'With Images Only',
    ],

    // === Success Messages ===
    'success_messages' => [
        'operation_completed' => 'Operation completed successfully!',
        'changes_saved' => 'Changes saved successfully!',
        'category_published' => 'Category published successfully!',
        'category_unpublished' => 'Category unpublished successfully!',
        'images_added' => ':count images added successfully!',
        'category_moved' => 'Category moved successfully!',
    ],

    // === Error Messages ===
    'error_messages' => [
        'general' => 'An error occurred. Please try again.',
        'network' => 'Network error. Please check your internet connection.',
        'server' => 'Server error. Please try again later.',
        'category_not_found' => 'Category not found',
        'has_subcategories' => 'Cannot delete category that contains subcategories',
        'has_images' => 'Cannot delete category that contains images',
        'invalid_parent' => 'Invalid parent category selected',
        'depth_exceeded' => 'Maximum category depth exceeded',
    ],

    // === Helper Text ===
    'help' => [
        'category_name' => 'Enter a descriptive name for this category',
        'parent_category' => 'Select parent category (leave empty for root level)',
        'category_order' => 'Determines display order (lower values appear first)',
        'root_category' => 'Root categories are organizational and not displayed publicly',
        'seo_url' => 'URL-friendly version of category name (used in links)',
        'meta_description' => 'Description shown in search engine results',
        'category_image' => 'Upload an image to represent this category',
        'access_control' => 'Specify which user roles can access this category',
    ],

    // === Placeholders ===
    'placeholders' => [
        'category_name' => 'Enter category name...',
        'category_description' => 'Enter category description...',
        'seo_url' => 'category-url-slug',
        'meta_title' => 'Enter meta title...',
        'meta_description' => 'Enter meta description...',
        'meta_keywords' => 'keyword1, keyword2, keyword3',
        'image_alt_text' => 'Describe the image for accessibility...',
    ],

    // === Empty States ===
    'empty_states' => [
        'no_categories' => 'No categories have been created yet',
        'no_results' => 'No categories found matching your search criteria',
        'no_images' => 'No images in this category yet',
        'add_first_category' => 'Click the button to create the first category',
        'upload_first_image' => 'Upload your first image to get started',
    ],

    // === Loading States ===
    'loading_states' => [
        'loading_categories' => 'Loading categories...',
        'saving_changes' => 'Saving changes...',
        'deleting_category' => 'Deleting category...',
        'uploading_image' => 'Uploading image...',
        'processing_request' => 'Processing request...',
    ],

];