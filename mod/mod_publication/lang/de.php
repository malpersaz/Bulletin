<?php
/**
 * Publication Management Module - English Language File
 *
 * @package AKM ARTIST CMS
 * @module  mod_publication
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Publication Management Module Language Translations
 */
$lang['mod_publication'] = [

    // === Basic Actions ===
    'add' => 'Add Publication',
    'edit' => 'Edit Publication',
    'list' => 'Publication List',
    'information' => 'Information!',
    'success' => 'Operation Successful!',
    'error' => 'Error Occurred!',
    'error_occurred' => 'Error Occurred!',
    'wizard_loading' => 'Wizard is loading...',
    'fatal_error' => 'Fatal Error Occurred!',
    'remove_image' => 'Are you sure you want to remove this image?',
    'remove_image_success' => 'Image Removed',
    'choose_wish' => 'Please choose if you want',
    'remove_record' => 'Are you sure you want to delete this record?',
    'delete_confirm' => 'Are you sure you want to delete this publication?',
    'quick_unpublish_confirm' => 'Are you sure you want to unpublish this publication?',
    'quick_publish_confirm' => 'Are you sure you want to publish this publication?',
    'add_image' => 'Add Image',
    'upload_change_image' => 'Upload/Change Image',
    'local' => 'Local',
    'embed' => 'Embed',

    // === Filter Menu ===
    'filter' => [
        'title' => 'Filter & Sort',
        'close' => 'Close',
        'sort_label' => 'Sort',
        'newest' => 'Newest',
        'oldest' => 'Oldest',
        'title_az' => 'Title (A-Z)',
        'title_za' => 'Title (Z-A)',
        'view_count' => 'View Count',
        'limit_label' => 'Display Limit',
        'lang_label' => 'Language Selection',
        'selected_lang' => 'Selected Language',
        'date_label' => 'Date Range',
        'start_date' => 'Start',
        'end_date' => 'End',
        'clear' => 'Clear',
        'apply' => 'View Results',
        'select_lang' => 'Select Language',
        'all_langs' => 'All Languages',
        'select_label' => 'Select',
        'delete_error' => 'Deletion failed!',
        'video_success_sim' => 'Video Successfully Added (Simulation)',
        'video_embed_error' => 'Invalid Embed Code',
        'video_title_error' => 'Invalid Title',
    ],

    // === Messages ===
    'message' => [
        'success' => [
            'add' => 'Publication Added!',
            'edit' => 'Publication Updated!',
        ],
        'error' => [
            'add' => 'Publication Could Not Be Added!',
            'edit' => 'Publication Could Not Be Updated!',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'The specified record could not be found!',
    ],

    // === Buttons ===
    'button' => [
        'next' => 'Next',
        'prev' => 'Previous',
        'send' => 'Send',
        'add' => 'Add',
        'edit' => 'Edit',
        'refresh' => 'Refresh',
        'delete' => 'Delete',
        'gallery' => 'Image Gallery',
        'file' => 'File Manager',
        'cancel' => 'Cancel',
        'remove' => 'Remove',
        'yes' => 'Yes',
        'no' => 'No',
    ],

    // === Data Grid Messages ===
    'grid' => [
        'showing_results' => 'Showing {from} to {to} of {total} results.',
        'page' => 'Page',
        'page_count' => 'Page Count',
        'loading' => 'Loading data, please wait...',
        'no_data' => 'No data found',
        'connection_error' => 'Connection Error',
        'search' => 'Search',
        'select_at_least_one' => 'Please select at least one row',
        'select_only_one' => 'Please select only one row',
        'approve' => [
            'key' => 'Publish',
            'value' => 'Are you sure you want to publish this publication?',
        ],
        'unapprove' => [
            'key' => 'Unpublish',
            'value' => 'Are you sure you want to unpublish this publication?',
        ],
        'load_more' => 'Load More',
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Please do not leave the %s field empty',
        'length' => '%s can be minimum %s and maximum %s characters',
        'email' => 'Please enter a valid email address for the %s field.',
        'unique' => 'The %s you entered is already registered in the system. Please specify another %s!',
        'inarray' => 'The %s field can only take the values \' %s \'',
        'title_length' => 'You must enter a title consisting of at least 1 and at most 70 characters.',
        'title_exists' => 'This title already exists, please change it',
        'title_unique' => 'Your title is unique',
        'deck_length' => 'Bitte geben Sie einen Einleitungstext zwischen 90 und 120 Zeichen ein.',
    ],

    // === Permission Roles ===
    'role' => [
        'name' => 'Publication Management Roles',
        'add' => 'Can Add Publications',
        'edit' => 'Can Edit Publications',
        'delete' => 'Can Delete Publications',
        'list' => 'Can View Publication Management',
        'publish' => 'Can Publish Publications',
        'unpublish' => 'Can Unpublish Publications',
        'change_status' => 'Can Change Publish Status',
        'edit_status' => 'Can Change Detailed Publish Status',
        'moderate_edit' => 'Can Edit Others Publications',
        'moderate_delete' => 'Can Delete Others Publications',
        'gallery' => 'Can Manage Gallery',
        'file' => 'Can Manage Files',
        'approval' => 'Can Approve Publications',
        'video_download' => 'Can Download Video',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'add' => 'You do not have permission to add publications',
        'edit' => 'You do not have permission to edit publications',
        'list' => 'You do not have permission to view publication management',
        'delete' => 'You do not have permission to delete publications',
        'approval' => 'You do not have permission to approve publications',
        'moderate_edit' => 'You cannot edit publication records of others',
        'moderate_delete' => 'You cannot delete publication records of others',
        'gallery' => 'You do not have permission to manage gallery',
        'file' => 'You do not have permission to manage files',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Language',
            'name' => 'Title',
            'category_id' => 'Category',
            'created_at' => 'Date',
            'updated_at' => 'Edit Date',
            'publication' => 'Publication',
            'summary' => 'Introduction',
            'seo' => 'SEO URL',
            'title' => 'Page Title',
            'keywords' => 'Keywords',
            'description' => 'Description',
            'status' => 'Publish Status',
            'deck' => 'Featured',
            'image' => 'Image',
            'gallery_id' => 'Gallery',
            'file_id' => 'File',
            'acl_id' => 'Access Control',
            'menu' => 'Add to Menu',
            'video' => 'Video Gallery',
            'video_help' => 'Search for the video you want to display in the publication',
        ],
        'radio' => [
            'publish_yes' => 'Published',
            'publish_no' => 'Just Save',
            'deck_yes' => 'Featured',
            'deck_no' => 'Not Featured',
        ],
    ],

    // === UI Elements ===
    'ui' => [
        'record_management' => 'Record Management',
        'operation' => 'Operation',
        'apply' => 'Apply',
        'action_column' => 'Action',
        'select' => 'Select',
        'from' => 'From',
        'to' => 'To',
        'search' => 'Search',
        'reset' => 'Reset',
        'approval' => 'Confirm',
        'processing' => 'Processing',
        'record_selected' => 'Record selected',
        'operation_failed' => 'Operation could not be performed',
        'select_operation' => 'Please select an operation',
        'all' => 'All',
        'pdf' => 'PDF',
        'csv' => 'CSV',
        'excel' => 'Excel',
        'print' => 'Print',
        'print_info' => 'Print info',
        'created_by' => 'Created by',
        'copy' => 'Copy',
        'default_sort' => 'Newest',
        'default_lang' => 'EN',
        'date_locale' => 'en-US',
        'filter_cleared' => 'Filters cleared and reset to defaults.',
        'filter_applied' => 'Filters Applied',
        'sort_label' => 'Sorting',
        'limit_label' => 'Count',
        'lang_label' => 'Language',
    ],

    // === DataTable Configuration ===
    'datatable' => [
        'aria' => [
            'sortAscending' => ': activate to sort column ascending',
            'sortDescending' => ': activate to sort column descending',
        ],
        'emptyTable' => 'No data available in table',
        'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
        'infoEmpty' => 'Showing 0 to 0 of 0 entries',
        'infoFiltered' => '(filtered from _MAX_ total entries)',
        'lengthMenu' => 'Show _MENU_ entries',
        'search' => 'Search:',
        'zeroRecords' => 'No matching records found',
        'loadingRecords' => 'Loading...',
        'processing' => 'Processing...',
        'paginate' => [
            'first' => 'First',
            'last' => 'Last',
            'next' => 'Next',
            'previous' => 'Previous',
        ],
    ],

    // === Tokenizing Configuration ===
    'tokenizing' => [
        'hintText' => 'Type in a search term',
        'noResultsText' => 'No results',
        'searchingText' => 'Searching...',
    ],

    // === AnyTime Picker ===
    'anytime' => [
        'labelDayOfMonth' => 'Day of Month',
        'labelHour' => 'Hour',
        'labelMinute' => 'Minute',
        'labelMonth' => 'Month',
        'labelSecond' => 'Second',
        'labelTitle' => 'Select Date and Time',
        'labelYear' => 'Year',
        'dayAbbreviations' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        'dayNames' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        'monthAbbreviations' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        'monthNames' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    ],

    // === Status Badges ===
    'status_badge' => [
        'active' => 'Published',
        'draft' => 'Draft',
        'general' => 'General',
    ],

    // === Sheet Labels ===
    'sheet' => [
        'lang_title' => 'Language Selection',
        'ok' => 'OK',
        'search_placeholder' => 'Search language (e.g. English, JP)...',
        'category_title' => 'Select Category',
        'all' => 'All (Unfiltered)',
    ],

    // === Wizard ===
    'wizard' => [
        'step' => [
            'lang' => 'Language Selection',
            'language' => 'Language Selection',
            'category' => 'Category',
            'title' => 'Title',
            'date' => 'Publish Date',
            'intro' => 'Intro Text (Deck)',
            'publication' => 'Publication Details',
            'image' => 'Image Selection',
            'finish' => 'Complete',
        ],
        'subtitle' => [
            'language' => 'Select the main language for the publication.',
            'category' => 'Select the category this publication belongs to.',
            'title' => 'Enter an engaging title. (Min: 1, Max: 70)',
            'date' => 'Date and time for publication.',
            'intro' => 'Schreiben Sie eine kurze Zusammenfassung. (90 - 120 Zeichen)',
            'publication' => 'Rich text editor.',
            'image' => 'Upload a cover image.',
            'finish' => 'Final checks and publishing.',
        ],
        'label' => [
            'publication_title' => 'Publication Title',
            'intro_text' => 'Intro Text',
            'upload_text' => 'Tap to Upload Image',
            'published' => 'Published',
            'published_desc' => 'The publication will be visible to everyone once it is added.',
            'category_select' => 'Select Category...',
            'change_image' => 'Change Image',
            'editor_placeholder' => 'Start writing your publication here...',
        ],
        'messages' => [
            'success_sim' => 'Publication successfully created! (Simulation)',
            'video_success_sim' => 'Video successfully added! (Simulation)',
            'video_embed_error' => 'Please enter a valid embed code (iframe).',
            'video_title_error' => 'Video title must be at least 3 characters.',
            'card_click' => 'Card clicked: %s',
            'no_category_for_lang' => 'No categories found in this language. Please add categories first.',
        ],
        'summary' => [
            'title' => 'Summary',
            'selected_none' => 'Not Selected',
        ],
        'button' => [
            'save' => 'Save',
            'next' => 'Continue',
            'prev' => 'Back',
            'finish' => 'Save and Finish',
        ]
    ],

    // === Context Menu ===
    'context_menu' => [
        'title' => 'Publication Actions',
        'edit' => 'Edit',
        'gallery' => 'Add Gallery to the Publication',
        'files' => 'Add File to the Publication',
        'video' => 'Add Video Embed to the Publication',
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
        'gallery_alert' => "GALLERY MANAGEMENT:\nID: %s\nTitle: %s\nOpening image gallery for publication.",
        'files_alert' => "FILE MANAGEMENT:\nID: %s\nTitle: %s\nOpening file upload screen for publication.",
        'status_alert' => "STATUS UPDATED:\n%s",
        'delete_success' => 'Publication deleted!',
        'video_unavailable' => 'Video management is not available for publications yet.',
    ],

    'mock' => [
        'titles' => [
            "New Era Begins in Kurdish Press",
            "Diyarbakir Culture and Arts Festival Schedule Announced",
            "Local Journalism Workshop Concluded",
            "Notes on AI and Media Relationship",
            "Transition from Traditional Journalism to Digital Media",
            "Social Media Usage Statistics 2024",
            "Weekly Tech Summary: What Happened?",
            "Writers Union Elects New Board",
            "Photography Workshop Registrations Started",
            "AKM ARTIST CMS Mobile App Updated"
        ],
        'categories' => ["Agenda", "Culture & Arts", "Technology", "Media", "Life"],
        'authors' => [],
    ],

    // === AJAX & Relation Messages ===
    'ajax' => [
        'success' => 'Success',
        'error' => 'Error Occurred',
        'database_error' => 'Database Error',
        'record_not_found' => 'Publication item not found!',
        'unknown_type' => 'Unknown relation type',
        'file_added' => 'A file has been added to the publication',
        'file_removed' => 'The file has been removed from publication',
        'gallery_added' => 'A gallery has been added to the publication',
        'gallery_removed' => 'The gallery has been removed from publication',
        'relation_updated' => 'Relation successfully updated',
    ],

    // === Hero Slider ===
    'hero_slider_add' => 'Send to Slider',
    'hero_slider_remove' => 'Remove from Slider',
    'hero_slider_added' => 'Sent to slider.',
    'hero_slider_removed' => 'Removed from slider.',
    'hero_slider_error' => 'Slider update failed.',
    'hero_slider_badge' => 'Slider',

    // === Media Picker UI ===
    'picker' => [
        'gallery' => 'Select Existing Gallery',
        'file' => 'Select Existing File',
        'video' => 'Select Existing Video',
    ],
];
