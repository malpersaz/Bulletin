<?php
/**
 * Category Module - English Language File
 *
 * @package Rojname
 * @module  mod_publication_category
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Category Module Language Translations
 */
$lang['mod_publication_category'] = [

    // === Basic Navigation ===
    'list' => 'Category List',
    'add' => 'Add Category',
    'edit' => 'Edit Category',
    'information' => 'Information',
    'oke' => 'Success',
    'hata' => 'Error Occurred',
    'error' => 'Fatal Error Occurred',
    'remove_record' => 'Are you sure you want to delete this record?',
    'choose_wish' => 'Choose as you wish',
    'remove_image' => 'Are you sure you want to remove this image?',

    // === Message Structure ===
    'message' => [
        'oke' => [
            'add' => 'Category successfully added!',
            'edit' => 'Category successfully updated!',
        ],
        'error' => [
            'add' => 'Failed to add category!',
            'edit' => 'Failed to update category!',
        ],
        'information' => [
            'moderate' => 'Double-click the category you want to edit',
            'move' => 'Drag and drop the category to change its order',
        ],
    ],

    'errors' => [
        'record' => 'The specified record could not be found!',
        'parent_selection_required' => 'Please select a parent category.',
        'parent_language_mismatch' => 'Parent category language must match the category language.',
        'parent_cycle' => 'A category cannot be moved under itself or one of its subcategories.',
        'menu_parent_not_found' => 'Selected menu parent could not be found.',
        'menu_language_mismatch' => 'Menu parent language must match the category language.',
        'order_update_failed' => 'Category order could not be updated.',
    ],

    'parent_labels' => [
        'system_root' => 'System root',
        'language_root' => ':language root',
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
        'save' => 'Save',
        'remove' => 'Remove',
        'gallery' => 'Gallery',
        'file' => 'File',
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
        'ad' => 'Category Management Roles',
        'add' => 'Can Add Category',
        'edit' => 'Can Edit Category',
        'delete' => 'Can Delete Category',
        'list' => 'Can View Category Management',
        'moderate_edit' => 'Can edit category records belonging to others',
        'moderate_delete' => 'Can delete category records belonging to others',
    ],

    'role_errors' => [
        'moderate_edit' => 'You cannot edit category records belonging to others',
        'moderate_delete' => 'You cannot delete category records belonging to others',
        'add' => 'You do not have permission to add categories',
        'edit' => 'You do not have permission to edit categories',
        'list' => 'You do not have permission to view Category Management',
        'delete' => 'You do not have permission to delete categories',
    ],

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Add Category',
        'edit' => 'Edit Category',
        'list' => 'Category List',
        'delete' => 'Delete Category',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'update' => 'Update',
        'remove' => 'Remove',
        'unlock_reorder' => 'Unlock Reorder',
        'lock_reorder' => 'Lock Reorder',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Category successfully added!',
            'updated' => 'Category successfully updated!',
            'deleted' => 'Category successfully deleted!',
            'reordered' => 'Category order updated!',
            'image_removed' => 'Image removed successfully!',
        ],
        'error' => [
            'not_added' => 'Failed to add category!',
            'not_updated' => 'Failed to update category!',
            'not_deleted' => 'Failed to delete category!',
            'not_found' => 'The specified category record could not be found!',
            'image_not_removed' => 'Failed to remove image!',
            'validation' => 'Please fill in the required fields correctly!',
        ],
        'info' => [
            'edit_double_click' => 'Double-click the category you want to edit',
            'drag_reorder' => 'Drag and drop the category to reorder',
            'mode_unlocked' => 'Mode: Reorder (Unlocked)',
        ],
        'warning' => [
            'reorder' => 'Warning! You are changing the tree structure.',
        ],
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Yes',
        'no' => 'No',
    ],

    // === Form Labels ===
    'form' => [
        'basic_info' => 'Basic Information',
        'seo_settings' => 'SEO Settings',
        'display_settings' => 'Display Settings',
        'advanced_settings' => 'Advanced Settings',

        'id' => 'ID',
        'name' => 'Category Name',
        'parent_category' => 'Parent Category',
        'order' => 'Order',
        'language' => 'Display Language',
        'is_root' => 'Root Category (Hidden from display)',
        'seo_url' => 'SEO URL',
        'meta_title' => 'Meta Title (for Search Engines)',
        'meta_keywords' => 'Meta Keywords (for Search Engines)',
        'meta_description' => 'Meta Description (for Search Engines)',
        'add_to_menu' => 'Add Category to Menu',
        'image' => 'Category Image',
        'description' => 'Category Description',
        'access_control' => 'Access Control',
    ],

    // === Table Columns ===
    'columns' => [
        'id' => 'ID',
        'language' => 'Display Language',
        'name' => 'Category Name',
        'parent_category' => 'Parent Category',
        'order' => 'Order',
        'root_category' => 'Root Category',
        'seo_url' => 'SEO URL',
        'meta_title' => 'Meta Title',
        'meta_keywords' => 'Meta Keywords',
        'meta_description' => 'Meta Description',
        'add_to_menu' => 'Add to Menu',
        'image' => 'Image',
        'status' => 'Status',
        'actions' => 'Actions',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Add New Category',
        'edit' => 'Edit Selected',
        'delete' => 'Delete Selected',
        'refresh' => 'Refresh',
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
        'url' => 'Please enter a valid URL',
        'slug' => 'Please enter a valid URL slug',
    ],

    // === Access Control (ACL) ===
    'permissions' => [
        'title' => 'Category Management Permissions',
        'add' => 'Can Add Category',
        'edit' => 'Can Edit Category',
        'delete' => 'Can Delete Category',
        'list' => 'Can View Category Management',
        'edit_others' => 'Can edit category records belonging to others',
        'delete_others' => 'Can delete category records belonging to others',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'edit_others' => 'You cannot edit category records belonging to others',
        'delete_others' => 'You cannot delete category records belonging to others',
        'add' => 'You do not have permission to add categories',
        'edit' => 'You do not have permission to edit categories',
        'list' => 'You do not have permission to view category management',
        'delete' => 'You do not have permission to delete categories',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Display Language',
            'name' => 'Category Name',
            'parent_id' => 'Parent Category',
            'order' => 'Order',
            'root' => 'Root Category (Hidden from Display)',
            'seo' => 'Category URL',
            'title' => 'Title (for Search Engines)',
            'keywords' => 'Keywords (for Search Engines)',
            'description' => 'Description (for Search Engines)',
            'menu' => 'Send Category to Menu',
            'image' => 'Image',
            'image_select' => 'Select Image',
            'acl_id' => 'Access Control',
        ],
        'select_values' => [
            'root_yes' => 'Yes',
            'root_no' => 'No',
        ],
        'tooltips' => [
            'acl_settings' => 'ACL Settings',
        ],
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'remove_image' => 'Are you sure you want to remove this image?',
        'remove_record' => 'Are you sure you want to delete this record?',
        'delete_single' => 'Are you sure you want to delete this category?',
        'delete_multiple' => 'Are you sure you want to delete :count selected categories?',
        'make_root' => 'Are you sure you want to make this a root category?',
        'remove_root' => 'Are you sure you want to remove root category status?',
        'reorder_single' => 'Do you want to move this category to its new location?',
    ],

    // === Success Messages ===
    'success_messages' => [
        'operation_completed' => 'Operation completed successfully!',
        'changes_saved' => 'Changes saved!',
        'category_published' => 'Category published successfully!',
        'category_unpublished' => 'Category unpublished successfully!',
    ],

    // === Error Messages ===
    'error_messages' => [
        'general' => 'An error occurred. Please try again.',
        'network' => 'Network error. Please check your internet connection.',
        'server' => 'Server error. Please try again later.',
        'fatal' => 'A fatal error occurred!',
        'has_subcategories' => 'Cannot delete category that contains subcategories',
        'has_content' => 'Cannot delete category that contains content',
    ],

    // === Helper Text ===
    'help' => [
        'category_name' => 'Enter a clear and descriptive name for this category',
        'parent_category' => 'Select the parent category (leave empty for root level)',
        'order' => 'Determines the display order (lower values appear first)',
        'root_category' => 'Root categories are organizational and not displayed publicly',
        'seo_url' => 'URL-friendly version of the category name (used in links)',
        'meta_title' => 'Title shown in search engine results',
        'meta_description' => 'Description shown in search engine results',
        'add_to_menu' => 'Automatically add this category to the navigation menu',
        'access_control' => 'Specify which user roles can access this category',
    ],

    // === Placeholders ===
    'placeholders' => [
        'category_name' => 'Enter category name...',
        'seo_url' => 'category-url-slug',
        'meta_title' => 'Enter meta title...',
        'meta_description' => 'Enter meta description...',
        'meta_keywords' => 'keyword1, keyword2, keyword3',
        'category_description' => 'Enter category description (optional)...',
    ],

    // === Empty States ===
    'empty' => [
        'no_categories' => 'No categories have been created yet',
        'no_results' => 'No categories found matching your search criteria',
        'add_first' => 'Click the button to create the first category',
        'no_subcategories' => 'No subcategories found',
        'no_content' => 'No content in this category yet',
    ],

    // === Loading States ===
    'loading' => [
        'saving' => 'Saving...',
        'loading' => 'Loading...',
        'processing' => 'Processing...',
        'deleting' => 'Deleting...',
    ],

    // === Category Tree View ===
    'tree_view' => [
        'expand_all' => 'Expand All',
        'collapse_all' => 'Collapse All',
        'show_hierarchy' => 'Show Hierarchy',
        'show_flat' => 'Flat View',
        'root_only' => 'Root Categories Only',
        'leaf_only' => 'Leaf Categories Only',
    ],

    // === SEO Features ===
    'seo' => [
        'auto_generate_slug' => 'Auto-generate URL slug',
        'preview_snippet' => 'Preview Search Result',
        'character_count' => 'Character count: :count',
        'recommended_length' => 'Recommended: :min-:max characters',
        'good' => 'Good',
        'too_short' => 'Too short',
        'too_long' => 'Too long',
    ],

    // === Bulk Operations ===
    'bulk_operations' => [
        'bulk_edit' => 'Bulk Edit',
        'bulk_delete' => 'Bulk Delete',
        'bulk_move' => 'Move to Category',
        'bulk_publish' => 'Publish Selected',
        'bulk_unpublish' => 'Unpublish Selected',
        'select_all' => 'Select All',
        'deselect_all' => 'Deselect All',
    ],

    // === AJAX Response Messages ===
    'ajax' => [
        'success' => 'Success',
        'error' => 'Error',
    ],

    // === Response Codes (for AJAX/PHP responses) ===
    'responses' => [
        'success' => 'Oke',  // Keep 'Oke' as it's used in JavaScript comparisons
        'error' => 'Error',  // Keep 'Error' as it's used in JavaScript comparisons
        'fatal_error' => 'ERROR',  // Keep 'ERROR' as it's used in JavaScript comparisons
    ],

];
