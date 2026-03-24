<?php
/**
 * Content Management Module - English Language File
 *
 * @package AKM Business CMS
 * @module  mod_content
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Content Management Module Language Translations
 */
$lang['mod_content'] = [

    // === Basic Actions ===
    'add' => 'Add Content',
    'edit' => 'Edit Content',
    'list' => 'Content List',
    'information' => 'Information!',
    'oke' => 'Operation Successful!',
    'hata' => 'Error Occurred!',
    'error' => 'Fatal Error Occurred!',
    'remove_image' => 'Are you sure you want to remove this image?',
    'remove_image_oke' => 'Image Removed',
    'choose_wish' => 'Please choose if you want',
    'remove_record' => 'Are you sure you want to delete this record?',
    'quick_unpublish_confirm' => 'Are you sure you want to unpublish this content?',
    'quick_publish_confirm' => 'Are you sure you want to publish this content?',
    'resim_ekle' => 'Add Image',
    'resim_yukle_degistir' => 'Upload/Change Image',
    'lokal' => 'Local',
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
        'oke' => [
            'add' => 'Content Added!',
            'edit' => 'Content Updated!',
        ],
        'error' => [
            'add' => 'Content Could Not Be Added!',
            'edit' => 'Content Could Not Be Updated!',
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
        'yenile' => 'Refresh',
        'delete' => 'Delete',
        'gallery' => 'Image Gallery',
        'file' => 'File Manager',
        'vazgec' => 'Cancel',
        'kaldir' => 'Remove',
        'evet' => 'Yes',
        'hayir' => 'No',
    ],

    // === Data Grid Messages ===
    'grid' => [
        0 => 'Showing {from} to {to} of {total} results.',
        1 => 'Page',
        2 => 'Page Count',
        3 => 'Loading data, please wait...',
        4 => 'No data found',
        5 => 'Connection Error',
        6 => 'Search',
        7 => 'Please select at least one row',
        8 => 'Please select only one row',
        'approve' => [
            'key' => 'Publish',
            'value' => 'Are you sure you want to publish this content?',
        ],
        'unapprove' => [
            'key' => 'Unpublish',
            'value' => 'Are you sure you want to unpublish this content?',
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
        'deck_length' => 'Please enter an introductory text of at least 110 characters and a maximum of 280 characters.',
    ],

    // === Permission Roles ===
    'roll' => [
        'name' => 'Content Management Roles',
        'add' => 'Can Add Content',
        'edit' => 'Can Edit Content',
        'delete' => 'Can Delete Content',
        'list' => 'Can View Content Management',
        'publish' => 'Can Publish Content',
        'unpublish' => 'Can Unpublish Content',
        'change_status' => 'Can Change Publish Status',
        'edit_status' => 'Can Change Detailed Publish Status',
        'moderate_edit' => 'Can Edit Others Content',
        'moderate_delete' => 'Can Delete Others Content',
        'gallery' => 'Can Manage Gallery',
        'file' => 'Can Manage Files',
        'approval' => 'Can Approve Content',
        'video_download' => 'Can Download Video',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'add' => 'You do not have permission to add content',
        'edit' => 'You do not have permission to edit content',
        'list' => 'You do not have permission to view content management',
        'delete' => 'You do not have permission to delete content',
        'approval' => 'You do not have permission to approve content',
        'moderate_edit' => 'You cannot edit content records of others',
        'moderate_delete' => 'You cannot delete content records of others',
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
            'content' => 'Content',
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
            'video' => 'Video Gallery',
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
        'kayit_yonetimi' => 'Record Management',
        'islem' => 'Operation',
        'uygula' => 'Apply',
        'islem_column' => 'Action',
        'select' => 'Select',
        'burdan' => 'From',
        'buraya' => 'To',
        'ara' => 'Search',
        'sifirla' => 'Reset',
        'approval' => 'Confirm',
        'isleniyor' => 'Processing',
        'record_selected' => 'Record selected',
        'islem_gerceklestirilemedi' => 'Operation could not be performed',
        'islem_secin' => 'Please select an operation',
        'all' => 'All',
        'pdf' => 'PDF',
        'csv' => 'CSV',
        'excel' => 'Excel',
        'yazdir' => 'Print',
        'yazdir_bilgi' => 'Print info',
        'olusturan' => 'Created by',
        'kopyala' => 'Copy',
        'default_sort' => 'Newest',
        'default_lang' => 'EN',
        'date_locale' => 'en-US',
        'all' => 'All',
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
            'content' => 'Content Details',
            'image' => 'Image Selection',
            'finish' => 'Complete',
        ],
        'subtitle' => [
            'language' => 'Select the main language for the content.',
            'category' => 'Determine the category to add content to.',
            'title' => 'Enter an engaging title. (Min: 1, Max: 70)',
            'date' => 'Date and time for publication.',
            'intro' => 'Write a short and concise summary. (110 - 280 Characters)',
            'content' => 'Rich text editor.',
            'image' => 'Upload a cover image.',
            'finish' => 'Final checks and publishing.',
        ],
        'label' => [
            'content_title' => 'Content Title',
            'intro_text' => 'Intro Text',
            'upload_text' => 'Tap to Upload Image',
            'published' => 'Published',
            'published_desc' => 'Content will be visible to everyone once added.',
            'category_select' => 'Select Category...',
            'change_image' => 'Change Image',
            'editor_placeholder' => 'Start writing your content here...',
        ],
        'messages' => [
            'success_sim' => 'Content successfully created! (Simulation)',
            'video_success_sim' => 'Video successfully added! (Simulation)',
            'video_embed_error' => 'Please enter a valid embed code (iframe).',
            'video_title_error' => 'Video title must be at least 3 characters.',
            'card_click' => 'Card clicked: %s',
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
        'title' => 'Content Actions',
        'edit' => 'Edit',
        'gallery' => 'Gallery Actions',
        'files' => 'File Actions',
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
        'gallery_alert' => "GALLERY MANAGEMENT:\nID: %s\nTitle: %s\nOpening image gallery for content.",
        'files_alert' => "FILE MANAGEMENT:\nID: %s\nTitle: %s\nOpening file upload screen for content.",
        'status_alert' => "STATUS UPDATED:\n%s",
        'delete_confirm' => 'Yes, Delete!',
        'delete_success' => 'Content deleted!',
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
            "Rojname Mobile App Updated"
        ],
        'categories' => ["Agenda", "Culture & Arts", "Technology", "Media", "Life"],
    ],
];