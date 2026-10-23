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
    'add' => 'افزودن خبر',
    'edit' => 'ویرایش خبر',
    'list' => 'فهرست اخبار',
    'information' => 'اطلاعات!',
    'success' => 'عملیات با موفقیت انجام شد!',
    'error_occurred' => 'خطایی رخ داد!',
    'error' => 'خطای بحرانی رخ داد!',
    'remove_image_title' => 'حذف تصویر',
    'remove_image' => 'آیا مطمئن هستید که می‌خواهید این تصویر را حذف کنید؟',
    'remove_image_success' => 'تصویر حذف شد',
    'choose_wish' => 'لطفاً در صورت تمایل انتخاب کنید',
    'remove_record' => 'آیا مطمئن هستید که می‌خواهید این رکورد را حذف کنید؟',
    'delete_confirm' => 'آیا مطمئن هستید که می‌خواهید این خبر را حذف کنید؟',
    'quick_unpublish_confirm' => 'آیا مطمئن هستید که می‌خواهید انتشار این خبر را لغو کنید؟',
    'quick_publish_confirm' => 'آیا مطمئن هستید که می‌خواهید این خبر را منتشر کنید؟',
    'wizard_loading' => 'جادوگر در حال بارگذاری است...',
    'add_image' => 'افزودن تصویر',
    'upload_image' => 'بارگذاری/تغییر تصویر',
    'local' => 'محلی',
    'embed' => 'جاسازی',

    // === Filter Menu ===
    'filter' => [
        'title' => 'فیلتر و مرتب‌سازی',
        'close' => 'Close',
        'sort_label' => 'Sort',
        'newest' => 'جدیدترین',
        'oldest' => 'قدیمی‌ترین',
        'title_az' => 'عنوان (الف-ی)',
        'title_za' => 'عنوان (ی-الف)',
        'view_count' => 'تعداد بازدید',
        'limit_label' => 'محدودیت نمایش',
        'lang_label' => 'انتخاب زبان',
        'selected_lang' => 'زبان انتخاب‌شده',
        'date_label' => 'بازه زمانی',
        'start_date' => 'Start',
        'end_date' => 'End',
        'clear' => 'Clear',
        'apply' => 'مشاهده نتایج',
        'select_lang' => 'انتخاب زبان',
        'all_langs' => 'همه زبان‌ها',
        'select_label' => 'Select',
    ],

    // === Messages ===
    'message' => [
        'success' => [
            'add' => 'خبر اضافه شد!',
            'edit' => 'خبر به‌روزرسانی شد!',
        ],
        'error' => [
            'add' => 'افزودن خبر انجام نشد!',
            'edit' => 'به‌روزرسانی خبر انجام نشد!',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'رکورد مشخص‌شده یافت نشد!',
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
        'gallery' => 'گالری تصاویر',
        'file' => 'مدیریت فایل',
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
        'no_data' => 'خبری یافت نشد',
        'approve' => [
            'key' => 'Publish',
            'value' => 'آیا مطمئن هستید که می‌خواهید این خبر را منتشر کنید؟',
        ],
        'unapprove' => [
            'key' => 'Unpublish',
            'value' => 'آیا مطمئن هستید که می‌خواهید انتشار این خبر را لغو کنید؟',
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
        'title_length' => 'باید عنوانی بین ۱ تا ۷۰ کاراکتر وارد کنید.',
        'title_exists' => 'این عنوان از قبل وجود دارد، لطفاً آن را تغییر دهید',
        'title_unique' => 'عنوان شما یکتا است',
        'deck_length' => 'لطفاً متن خلاصه را بین ۹۰ تا ۱۲۰ کاراکتر وارد کنید.',
        'invalid_lang' => 'لطفاً یک زبان فعال معتبر انتخاب کنید.',
        'category_not_found' => 'دسته‌بندی انتخاب‌شده یافت نشد.',
        'category_root_forbidden' => 'دسته‌بندی‌های ریشه را نمی‌توان مستقیماً اختصاص داد. لطفاً یک زیردسته انتخاب کنید.',
        'category_lang_mismatch' => 'دسته‌بندی انتخاب‌شده با زبان انتخاب‌شده مطابقت ندارد.',
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
        'default_sort' => 'جدیدترین',
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
        'lang_title' => 'انتخاب زبان',
        'ok' => 'OK',
        'search_placeholder' => 'Search language (e.g. English, JP)...',
        'category_title' => 'Select Category',
        'all' => 'All (Unfiltered)',
    ],

    // === Wizard ===
    'wizard' => [
        'step' => [
            'language' => 'انتخاب زبان',
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
    'hero_slider_add' => 'ارسال به اسلایدر',
    'hero_slider_remove' => 'حذف از اسلایدر',
    'hero_slider_added' => 'به اسلایدر ارسال شد.',
    'hero_slider_removed' => 'از اسلایدر حذف شد.',
    'hero_slider_error' => 'به‌روزرسانی اسلایدر ناموفق بود.',
    'hero_slider_badge' => 'Slider',
    'deck_add' => 'علامت‌گذاری به عنوان ویژه',
    'deck_remove' => 'حذف از ویژه',
    'deck_added' => 'به عنوان ویژه علامت‌گذاری شد.',
    'deck_removed' => 'از حالت ویژه خارج شد.',
    'deck_error' => 'به‌روزرسانی وضعیت ویژه ناموفق بود.',
    'deck_badge' => 'Featured',

    'picker' => [
        'gallery' => 'انتخاب گالری موجود',
        'file' => 'انتخاب فایل موجود',
        'video' => 'انتخاب ویدیوی موجود',
    ],

    // === Latest News ===
    'latest_news' => [
        'title' => 'آخرین اخبار',
        'subtitle' => 'مشاهده همه اخبار',
        'description' => 'هنوز خبری منتشر نشده است.',
    ],

    // === AJAX & Relation Messages ===
    'ajax' => [
        'success' => 'Success',
        'error' => 'خطایی رخ داد',
        'database_error' => 'خطای پایگاه داده',
        'record_not_found' => 'خبر یافت نشد!',
        'unknown_type' => 'نوع رابطه ناشناخته',
        'relation_updated' => 'رابطه با موفقیت به‌روزرسانی شد',
    ],

    // === AI Assistant ===
    'ai' => [
        'generate_deck' => 'تولید لید با هوش مصنوعی',
        'suggest_headlines' => 'پیشنهاد تیتر با هوش مصنوعی',
        'generate_seo' => 'تولید سئو با هوش مصنوعی',
        'generating' => 'در حال تولید...',
        'missing_key' => 'کلید Gemini API در Google Services Hub تنظیم نشده است.',
        'content_required' => 'عنوان یا محتوای خبر برای تولید با هوش مصنوعی الزامی است.',
        'select_headline' => 'انتخاب تیتر',
        'deck_success' => 'لید با موفقیت تولید شد (%d کاراکتر)',
        'deck_error' => 'تولید لید ناموفق بود.',
        'headline_success' => 'تیترها با موفقیت پیشنهاد شدند.',
        'headline_error' => 'پیشنهاد تیتر ناموفق بود.',
        'warning_title' => 'Warning',
        'error_title' => 'Error',
        'invalid_action' => 'عملیات هوش مصنوعی نامعتبر است.',
        'copy' => 'استفاده از این',
    ],
];
