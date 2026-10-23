<?php
/**
 * News Management Module - English Language File
 *
 * @package AKM Business
 * @module  mod_news
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * News Management Module Language Translations
 */
$lang['mod_news'] = [

    // === Basic Actions ===
    'add' => 'Add News',
    'edit' => 'Edit News',
    'list' => 'News List',
    'information' => 'Information!',
    'success' => 'Operation Successful!',
    'error_occurred' => 'Error Occurred!',
    'error' => 'Fatal Error Occurred!',
    'remove_image_title' => 'Remove Image',
    'remove_image' => 'Are you sure you want to remove this image?',
    'remove_image_success' => 'Image Removed',
    'choose_wish' => 'Please choose if you want',
    'remove_record' => 'Are you sure you want to delete this record?',
    'delete_confirm' => 'Are you sure you want to delete this news item?',
    'quick_unpublish_confirm' => 'Are you sure you want to unpublish this news item?',
    'quick_publish_confirm' => 'Are you sure you want to publish this news item?',
    'wizard_loading' => 'Wizard is loading...',
    'add_image' => 'Add Image',
    'upload_image' => 'Upload/Change Image',
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
    ],

    // === Messages ===
    'message' => [
        'success' => [
            'add' => 'News Added!',
            'edit' => 'News Updated!',
        ],
        'error' => [
            'add' => 'News Could Not Be Added!',
            'edit' => 'News Could Not Be Updated!',
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
        'ok' => 'OK',
        'yes' => 'Yes',
        'no' => 'No',
        'seo_meta' => '(SEO, Meta)',
    ],

    // === Data Grid Messages ===
    'grid' => [
        'showing_results' => 'Showing {from} to {to} of {total} results.',
        'page' => 'Page',
        'page_count' => 'Page Count',
        'connection_error' => 'Connection Error',
        'search' => 'Search',
        'select_at_least_one' => 'Please select at least one row',
        'select_only_one' => 'Please select only one row',
        'loading' => 'Loading...',
        'no_data' => 'No News Found',
        'approve' => [
            'key' => 'Publish',
            'value' => 'Are you sure you want to publish this news item?',
        ],
        'unapprove' => [
            'key' => 'Unpublish',
            'value' => 'Are you sure you want to unpublish this news item?',
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
        'deck_length' => 'Please enter an introductory text between 90 and 120 characters.',
        'invalid_lang' => 'Please select a valid active language.',
        'category_not_found' => 'The selected category could not be found.',
        'category_root_forbidden' => 'Root categories cannot be assigned directly. Please select a sub-category.',
        'category_lang_mismatch' => 'The selected category does not match the selected language.',
    ],

    // === Permission Roles ===
    'role' => [
        'name' => 'News Management Roles',
        'add' => 'Can Add News',
        'edit' => 'Can Edit News',
        'delete' => 'Can Delete News',
        'list' => 'Can View News Management',
        'publish' => 'Can Publish News',
        'unpublish' => 'Can Unpublish News',
        'change_status' => 'Can Change Publish Status',
        'edit_status' => 'Can Change Detailed Publish Status',
        'moderate_edit' => 'Can Edit Others News',
        'moderate_delete' => 'Can Delete Others News',
        'gallery' => 'Can Manage Gallery',
        'file' => 'Can Manage Files',
        'approval' => 'Can Approve News',
        'video_download' => 'Can Download Video',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'add' => 'You do not have permission to add news',
        'edit' => 'You do not have permission to edit news',
        'list' => 'You do not have permission to view news management',
        'delete' => 'You do not have permission to delete news',
        'approval' => 'You do not have permission to approve news',
        'moderate_edit' => 'You cannot edit news records of others',
        'moderate_delete' => 'You cannot delete news records of others',
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
            'news' => 'News',
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
            'video_help' => 'Search for the name of the video you want to appear in the news',
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
            'language' => 'Language Selection',
            'category' => 'Category',
            'title' => 'Title',
            'date' => 'Publish Date',
            'intro' => 'Intro Text (Deck)',
            'news' => 'News Details',
            'image' => 'Image Selection',
            'finish' => 'Complete',
        ],
        'subtitle' => [
            'language' => 'Select the main language for the news item.',
            'category' => 'Determine the category to add the news item to.',
            'title' => 'Enter an engaging title. (Min: 1, Max: 70)',
            'date' => 'Date and time for publication.',
            'intro' => 'Write a short and concise summary. (90 - 120 Characters)',
            'news' => 'Rich text editor.',
            'image' => 'Upload a cover image.',
            'finish' => 'Final checks and publishing.',
        ],
        'label' => [
            'news_title' => 'News Title',
            'intro_text' => 'Intro Text',
            'upload_text' => 'Tap to Upload Image',
            'published' => 'Published',
            'published_desc' => 'News will be visible to everyone once added.',
            'category_select' => 'Select Category...',
            'change_image' => 'Change Image',
            'editor_placeholder' => 'Start writing your news here...',
        ],
        'image' => [
            'drop_title' => 'Drag an image here',
            'drop_description' => 'Upload, paste, or drop an image.',
            'upload_link' => 'upload a file',
            'link_separator' => 'OR',
            'link_placeholder' => 'Paste image link',
            'link_action' => 'Import',
            'hint_loading' => 'Importing image...',
            'hint_invalid_type' => 'Only image files are supported here.',
            'hint_no_drop_data' => 'No usable image was found in the dropped content.',
            'hint_remote_failed' => 'The image could not be imported from that source.',
        ],
        'messages' => [
            'success_sim' => 'News successfully created! (Simulation)',
            'video_success_sim' => 'Video successfully added! (Simulation)',
            'video_embed_error' => 'Please enter a valid embed code (iframe).',
            'video_title_error' => 'Video title must be at least 3 characters.',
            'card_click' => 'Card clicked: %s',
            'no_category_for_lang' => 'No categories found for this language. Please add a category first.',
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
        'title' => 'News Actions',
        'edit' => 'Edit',
        'gallery' => 'Add Gallery to the News',
        'files' => 'Add File to the News',
        'video' => 'Add Video to the News',
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
        'gallery_alert' => "GALLERY MANAGEMENT:\nID: %s\nTitle: %s\nOpening image gallery for news.",
        'files_alert' => "FILE MANAGEMENT:\nID: %s\nTitle: %s\nOpening file upload screen for news.",
        'status_alert' => "STATUS UPDATED:\n%s",
        'delete_success' => 'News deleted!',
        'add_video' => 'Add Video',
        'add_file' => 'Add File',
    ],

    // === Hero Slider & Featured Deck ===
    'hero_slider_add' => 'Send to Slider',
    'hero_slider_remove' => 'Remove from Slider',
    'hero_slider_added' => 'Sent to slider.',
    'hero_slider_removed' => 'Removed from slider.',
    'hero_slider_error' => 'Slider update failed.',
    'hero_slider_badge' => 'Slider',
    'deck_add' => 'Mark as Featured',
    'deck_remove' => 'Remove from Featured',
    'deck_added' => 'Marked as featured.',
    'deck_removed' => 'Removed from featured.',
    'deck_error' => 'Failed to update featured state.',
    'deck_badge' => 'Featured',

    'picker' => [
        'gallery' => 'Select Existing Gallery',
        'file' => 'Select Existing File',
        'video' => 'Select Existing Video',
    ],

    // === Latest News ===
    'latest_news' => [
        'title' => 'Latest News',
        'subtitle' => 'View All News',
        'description' => 'No news published yet.',
    ],

    // === AJAX & Relation Messages ===
    'ajax' => [
        'success' => 'Success',
        'error' => 'Error Occurred',
        'database_error' => 'Database Error',
        'record_not_found' => 'News item not found!',
        'unknown_type' => 'Unknown relation type',
        'relation_updated' => 'Relation successfully updated',
    ],

    // === AI Assistant ===
    'ai' => [
        'generate_deck' => 'Generate Deck with AI',
        'suggest_headlines' => 'Suggest Headlines with AI',
        'generate_seo' => 'Generate SEO with AI',
        'generating' => 'Generating...',
        'missing_key' => 'Gemini API key is not configured in Google Services Hub.',
        'content_required' => 'Article title or content is required for AI generation.',
        'select_headline' => 'Select a Headline',
        'deck_success' => 'Deck generated successfully (%d chars)',
        'deck_error' => 'Failed to generate deck.',
        'headline_success' => 'Headlines suggested successfully.',
        'headline_error' => 'Failed to suggest headlines.',
        'warning_title' => 'Warning',
        'error_title' => 'Error',
        'invalid_action' => 'Invalid AI action.',
        'copy' => 'Use This',
    ],
];
