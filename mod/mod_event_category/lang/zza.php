<?php
/**
 * Event Category Module - Language File (zza)
 *
 * @package AKM Bulletin CMS
 * @module  mod_event_category
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_event_category'] = [
    'list' => 'Lîsteya Kategoriyan',
    'add' => 'Kategorî Lêke',
    'edit' => 'Kategorî Bivurne',
    'information' => 'Information',
    'success' => 'Success',
    'error' => 'Error Occurred',
    'remove_record' => 'Are you sure you want to delete this category?',
    'choose_wish' => 'Choose as you wish',
    'remove_image' => 'Are you sure you want to remove this image?',

    'message' => [
        'success' => [
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
        'record' => 'The specified category record could not be found!',
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
        'refresh' => 'Refresh',
        'delete' => 'Delete',
        'cancel' => 'Cancel',
        'remove' => 'Remove',
        'yes' => 'Yes',
        'no' => 'No',
        'save' => 'Save',
        'gallery' => 'Gallery',
        'file' => 'File',
    ],

    'grid' => [
        'showing_results' => 'Showing {from} to {to} of {total} results',
        'page' => 'Page',
        'page_count' => 'Page Count',
        'loading' => 'Loading data, please wait...',
        'no_data' => 'No data found',
        'connection_error' => 'Connection Error',
        'search' => 'Search',
        'select_at_least_one' => 'Please select at least one row',
        'select_only_one' => 'Please select only one row',
    ],

    'validator' => [
        'required' => 'Please do not leave the %s field empty',
        'length' => '%s must be between %s and %s characters',
        'email' => 'Please enter a valid email address in the %s field',
        'unique' => 'The %s you entered is already registered in the system. Please specify a different %s!',
        'inarray' => '%s field can only accept \' %s \' values',
        'invalid_lang' => 'Please select a valid active language.',
    ],

    'role' => [
        'name' => 'Event Category Roles',
        'add' => 'Can Add Event Category',
        'edit' => 'Can Edit Event Category',
        'delete' => 'Can Delete Event Category',
        'list' => 'Can View Event Categories',
        'moderate_edit' => 'Can Edit Others Categories',
        'moderate_delete' => 'Can Delete Others Categories',
    ],

    'role_errors' => [
        'add' => 'You do not have permission to add event categories',
        'edit' => 'You do not have permission to edit event categories',
        'delete' => 'You do not have permission to delete event categories',
        'list' => 'You do not have permission to view event categories',
        'moderate_edit' => 'You cannot edit categories of others',
        'moderate_delete' => 'You cannot delete categories of others',
    ],

    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Language',
            'name' => 'Category Name',
            'parent_id' => 'Parent Category',
            'seo' => 'SEO URL',
            'title' => 'Page Title',
            'keywords' => 'Keywords',
            'description' => 'Description',
            'image' => 'Category Image',
            'menu' => 'Add to Menu',
            'show_on_homepage' => 'Show on Homepage',
            'order' => 'Order',
            'acl_id' => 'Access Control',
            'status' => 'Status',
        ],
        'radio' => [
            'show_on_homepage_yes' => 'Show on Homepage',
            'show_on_homepage_no' => 'Do Not Show',
        ],
    ],

    'ui' => [
        'category_management' => 'Kategoriyê Çalakiyan',
        'expand_all' => 'Expand All',
        'collapse_all' => 'Collapse All',
        'filter_by_language' => 'Filter by Language',
        'all_languages' => 'All Languages',
        'empty_state_title' => 'No Categories Found',
        'empty_state_desc' => 'Create a category to organize events.',
        'create_first_category' => 'Create First Category',
        'save_order' => 'Save Order',
        'reset_order' => 'Reset Order',
    ],
];
