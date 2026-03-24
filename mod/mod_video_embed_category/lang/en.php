<?php
/**
 * Video Category Management - English Language File
 *
 * @package Rojname
 * @module  mod_video_embed_category
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Video Category Management Language Translations
 */
$lang['mod_video_embed_category'] = [

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Add Video Category',
        'edit' => 'Edit Video Category',
        'list' => 'Video Category List',
        'delete' => 'Delete Video Category',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'update' => 'Update',
        'remove' => 'Remove',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Video Category successfully added!',
            'updated' => 'Video Category successfully updated!',
            'deleted' => 'Video Category successfully deleted!',
            'reordered' => 'Category order updated successfully!',
            'image_removed' => 'Category image removed successfully!',
        ],
        'error' => [
            'not_added' => 'Failed to add Video Category!',
            'not_updated' => 'Failed to update Video Category!',
            'not_deleted' => 'Failed to delete Video Category!',
            'not_found' => 'The specified Video Category record could not be found!',
            'has_videos' => 'Cannot delete category that contains videos!',
            'has_subcategories' => 'Cannot delete category that contains subcategories!',
        ],
        'info' => [
            'edit_double_click' => 'Double-click the Video Category you want to edit',
            'drag_reorder' => 'Drag and drop the Video Category to change its order',
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
        'delete_single' => 'Are you sure you want to delete this Video Category?',
        'delete_multiple' => 'Are you sure you want to delete :count selected Video Categories?',
        'delete_with_videos' => 'This category contains :count videos. Are you sure you want to delete it?',
        'move_videos' => 'Move videos to another category before deleting',
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
    ],

    // === Validation Messages ===
    'validation' => [
        'required' => 'Please do not leave the %s field empty',
        'length' => '%s must be between %s and %s characters',
        'email' => 'Please enter a valid email address in the %s field',
        'unique' => 'The %s you entered is already registered in the system. Please specify a different %s!',
        'inarray' => '%s field can only accept \' %s \' values',
        'category_name_required' => 'Category name is required',
        'slug_required' => 'URL slug is required',
        'parent_category_invalid' => 'Invalid parent category selected',
    ],

    // === Access Control (ACL) ===
    // === Access Control (ACL) ===
    'roll' => [
        'ad' => 'Video Category Management Permissions',
        'add' => 'Can Add Video Category',
        'edit' => 'Can Edit Video Category',
        'delete' => 'Can Delete Video Category',
        'list' => 'Can View Video Category Management',
        'moderate_edit' => 'Can edit Video Category records belonging to others',
        'moderate_delete' => 'Can delete Video Category records belonging to others',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'edit_others' => 'You cannot edit Video Category records belonging to others',
        'delete_others' => 'You cannot delete Video Category records belonging to others',
        'add' => 'You do not have permission to add Video Categories',
        'edit' => 'You do not have permission to edit Video Categories',
        'list' => 'You do not have permission to view Video Category Management',
        'delete' => 'You do not have permission to delete Video Categories',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Display Language for Video Category',
            'name' => 'Video Category Name',
            'parent_category' => 'Parent Video Category',
            'order' => 'Order',
            'is_root' => 'Root Category (Hidden from Display)?',
            'seo_url' => 'Video Category URL',
            'meta_title' => 'Title (for Search Engines)',
            'meta_keywords' => 'Keywords (for Search Engines)',
            'meta_description' => 'Description (for Search Engines)',
            'menu' => 'Send Category to Menu',
            'image' => 'Image',
        ],
        'select_values' => [
            'root_yes' => 'Yes',
            'root_no' => 'No',
        ],
    ],

    // === Category Hierarchy ===
    'hierarchy' => [
        'parent_child' => 'Parent-Child Relationships',
        'category_tree' => 'Category Tree',
        'nested_categories' => 'Nested Categories',
        'category_level' => 'Category Level',
        'root_categories' => 'Root Categories',
        'sub_categories' => 'Sub Categories',
        'category_depth' => 'Category Depth',
        'max_depth' => 'Maximum Category Depth',
        'breadcrumb' => 'Breadcrumb Navigation',
    ],

    // === Video Count and Statistics ===
    'statistics' => [
        'video_count' => 'Video Count',
        'total_videos' => 'Total Videos',
        'published_videos' => 'Published Videos',
        'draft_videos' => 'Draft Videos',
        'total_views' => 'Total Views',
        'total_duration' => 'Total Duration',
        'recent_videos' => 'Recent Videos',
        'popular_videos' => 'Popular Videos',
        'featured_videos' => 'Featured Videos',
    ],

    // === Category Display Options ===
    'display_options' => [
        'list_view' => 'List View',
        'grid_view' => 'Grid View',
        'tree_view' => 'Tree View',
        'show_video_count' => 'Show Video Count',
        'show_empty_categories' => 'Show Empty Categories',
        'show_thumbnail' => 'Show Category Thumbnail',
        'show_description' => 'Show Category Description',
        'sort_alphabetically' => 'Sort Alphabetically',
        'sort_by_order' => 'Sort by Order',
        'sort_by_date' => 'Sort by Date',
        'sort_by_video_count' => 'Sort by Video Count',
    ],

    // === Category Features ===
    'features' => [
        'enable_comments' => 'Enable Comments',
        'enable_ratings' => 'Enable Ratings',
        'enable_sharing' => 'Enable Sharing',
        'show_related_videos' => 'Show Related Videos',
        'auto_play' => 'Auto Play Videos',
        'show_video_thumbnails' => 'Show Video Thumbnails',
        'enable_video_upload' => 'Enable Video Upload',
        'require_approval' => 'Require Video Approval',
        'enable_subcategories' => 'Enable Subcategories',
    ],

    // === Video Management ===
    'video_management' => [
        'add_videos' => 'Add Videos',
        'manage_videos' => 'Manage Videos',
        'bulk_operations' => 'Bulk Operations',
        'move_videos' => 'Move Videos',
        'copy_videos' => 'Copy Videos',
        'publish_videos' => 'Publish Videos',
        'unpublish_videos' => 'Unpublish Videos',
        'feature_videos' => 'Feature Videos',
        'archive_videos' => 'Archive Videos',
    ],

    // === SEO and Meta Data ===
    'seo' => [
        'friendly_urls' => 'Friendly URLs',
        'auto_generate_slug' => 'Auto-generate URL Slug',
        'meta_description' => 'Meta Description',
        'meta_keywords' => 'Meta Keywords',
        'og_tags' => 'Open Graph Tags',
        'twitter_cards' => 'Twitter Cards',
        'structured_data' => 'Structured Data',
        'schema_markup' => 'Schema Markup',
        'canonical_url' => 'Canonical URL',
    ],

    // === Category Operations ===
    'operations' => [
        'create_category' => 'Create Category',
        'edit_category' => 'Edit Category',
        'delete_category' => 'Delete Category',
        'move_category' => 'Move Category',
        'merge_categories' => 'Merge Categories',
        'duplicate_category' => 'Duplicate Category',
        'export_category' => 'Export Category',
        'import_categories' => 'Import Categories',
        'reorder_categories' => 'Reorder Categories',
        'rebuild_tree' => 'Rebuild Category Tree',
    ],

    // === Bulk Operations ===
    'bulk_operations' => [
        'select_all' => 'Select All',
        'deselect_all' => 'Deselect All',
        'delete' => 'Delete Selected',
        'publish_selected' => 'Publish Selected',
        'unpublish_selected' => 'Unpublish Selected',
        'move_selected' => 'Move Selected',
        'export_selected' => 'Export Selected',
        'bulk_edit' => 'Bulk Edit',
        'merge_selected' => 'Merge Selected',
    ],

    // === Search and Filter ===
    'search_filter' => [
        'search_categories' => 'Search categories...',
        'filter_by_status' => 'Filter by Status',
        'filter_by_language' => 'Filter by Language',
        'filter_by_parent' => 'Filter by Parent',
        'filter_by_video_count' => 'Filter by Video Count',
        'filter_by_date' => 'Filter by Date',
        'show_all' => 'Show All',
        'active_only' => 'Active Only',
        'root_only' => 'Root Only',
        'with_videos' => 'With Videos Only',
        'empty_categories' => 'Empty Categories Only',
    ],

    // === Import/Export ===
    'import_export' => [
        'export_categories' => 'Export Categories',
        'import_categories' => 'Import Categories',
        'csv_format' => 'CSV Format',
        'json_format' => 'JSON Format',
        'xml_format' => 'XML Format',
        'export_selected' => 'Export Selected',
        'export_all' => 'Export All',
        'import_file' => 'Import File',
        'preview_import' => 'Preview Import',
        'validate_data' => 'Validate Data',
        'import_progress' => 'Import Progress',
    ],

    // === Success Messages ===
    'success_messages' => [
        'operation_completed' => 'Operation completed successfully!',
        'changes_saved' => 'Changes saved successfully!',
        'category_published' => 'Category published successfully!',
        'category_unpublished' => 'Category unpublished successfully!',
        'videos_moved' => ':count videos moved successfully!',
        'categories_merged' => 'Categories merged successfully!',
        'tree_rebuilt' => 'Category tree rebuilt successfully!',
    ],

    // === Error Messages ===
    'error_messages' => [
        'general' => 'An error occurred. Please try again.',
        'network' => 'Network error. Please check your internet connection.',
        'server' => 'Server error. Please try again later.',
        'category_not_found' => 'Category not found',
        'invalid_parent' => 'Invalid parent category selected',
        'depth_exceeded' => 'Maximum category depth exceeded',
        'circular_reference' => 'Circular reference detected in category hierarchy',
        'import_failed' => 'Import failed',
        'export_failed' => 'Export failed',
        'validation_failed' => 'Data validation failed',
    ],

    // === Helper Text ===
    'help' => [
        'category_name' => 'Enter a descriptive name for this video category',
        'parent_category' => 'Select parent category (leave empty for root level)',
        'category_order' => 'Determines display order (lower values appear first)',
        'root_category' => 'Root categories are organizational and not displayed publicly',
        'seo_url' => 'URL-friendly version of category name (used in links)',
        'meta_description' => 'Description shown in search engine results',
        'category_image' => 'Upload an image to represent this category',
        'video_management' => 'Manage videos within this category',
        'hierarchy_help' => 'Create nested categories to organize videos better',
    ],

    // === Placeholders ===
    'placeholders' => [
        'category_name' => 'Enter category name...',
        'category_description' => 'Enter category description...',
        'seo_url' => 'video-category-url-slug',
        'meta_title' => 'Enter meta title...',
        'meta_description' => 'Enter meta description...',
        'meta_keywords' => 'keyword1, keyword2, keyword3',
        'search_categories' => 'Search video categories...',
    ],

    // === Empty States ===
    'empty_states' => [
        'no_categories' => 'No video categories have been created yet',
        'no_results' => 'No categories found matching your search criteria',
        'no_videos' => 'No videos in this category yet',
        'add_first_category' => 'Click the button to create the first video category',
        'upload_first_video' => 'Upload your first video to get started',
        'create_subcategory' => 'Create subcategories to organize your videos',
    ],

    // === Loading States ===
    'loading_states' => [
        'loading_categories' => 'Loading categories...',
        'saving_changes' => 'Saving changes...',
        'deleting_category' => 'Deleting category...',
        'uploading_image' => 'Uploading image...',
        'processing_request' => 'Processing request...',
        'importing_data' => 'Importing data...',
        'exporting_data' => 'Exporting data...',
    ],

    // === Category Tree View ===
    'tree_view' => [
        'expand_all' => 'Expand All',
        'collapse_all' => 'Collapse All',
        'show_hierarchy' => 'Show Hierarchy',
        'show_flat' => 'Show Flat View',
        'drag_drop_reorder' => 'Drag and drop to reorder',
        'indent_children' => 'Indent Children',
        'show_counts' => 'Show Video Counts',
    ],

];