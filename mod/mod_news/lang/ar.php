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
    'add' => 'إضافة خبر',
    'edit' => 'تعديل الخبر',
    'list' => 'قائمة الأخبار',
    'information' => 'معلومات!',
    'success' => 'تمت العملية بنجاح!',
    'error_occurred' => 'حدث خطأ!',
    'error' => 'حدث خطأ فادح!',
    'remove_image_title' => 'إزالة الصورة',
    'remove_image' => 'هل أنت متأكد أنك تريد إزالة هذه الصورة؟',
    'remove_image_success' => 'تمت إزالة الصورة',
    'choose_wish' => 'يرجى الاختيار إذا كنت ترغب',
    'remove_record' => 'هل أنت متأكد أنك تريد حذف هذا السجل؟',
    'delete_confirm' => 'هل أنت متأكد أنك تريد حذف هذا الخبر؟',
    'quick_unpublish_confirm' => 'هل أنت متأكد أنك تريد إلغاء نشر هذا الخبر؟',
    'quick_publish_confirm' => 'هل أنت متأكد أنك تريد نشر هذا الخبر؟',
    'wizard_loading' => 'جاري تحميل المعالج...',
    'add_image' => 'إضافة صورة',
    'upload_image' => 'رفع/تغيير الصورة',
    'local' => 'محلي',
    'embed' => 'تضمين',

    // === Filter Menu ===
    'filter' => [
        'title' => 'تصفية وترتيب',
        'close' => 'Close',
        'sort_label' => 'Sort',
        'newest' => 'الأحدث',
        'oldest' => 'الأقدم',
        'title_az' => 'العنوان (أ-ي)',
        'title_za' => 'العنوان (ي-أ)',
        'view_count' => 'عدد المشاهدات',
        'limit_label' => 'حد العرض',
        'lang_label' => 'اختيار اللغة',
        'selected_lang' => 'اللغة المحددة',
        'date_label' => 'النطاق الزمني',
        'start_date' => 'Start',
        'end_date' => 'End',
        'clear' => 'Clear',
        'apply' => 'عرض النتائج',
        'select_lang' => 'اختر اللغة',
        'all_langs' => 'جميع اللغات',
        'select_label' => 'Select',
    ],

    // === Messages ===
    'message' => [
        'success' => [
            'add' => 'تمت إضافة الخبر!',
            'edit' => 'تم تحديث الخبر!',
        ],
        'error' => [
            'add' => 'تعذر إضافة الخبر!',
            'edit' => 'تعذر تحديث الخبر!',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'تعذر العثور على السجل المحدد!',
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
        'gallery' => 'معرض الصور',
        'file' => 'مدير الملفات',
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
        'no_data' => 'لم يتم العثور على أخبار',
        'approve' => [
            'key' => 'Publish',
            'value' => 'هل أنت متأكد أنك تريد نشر هذا الخبر؟',
        ],
        'unapprove' => [
            'key' => 'Unpublish',
            'value' => 'هل أنت متأكد أنك تريد إلغاء نشر هذا الخبر؟',
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
        'title_length' => 'يجب إدخال عنوان يتكون من حرف واحد على الأقل و70 حرفًا على الأكثر.',
        'title_exists' => 'هذا العنوان موجود بالفعل، يرجى تغييره',
        'title_unique' => 'عنوانك فريد',
        'deck_length' => 'يرجى إدخال نص تمهيدي بين 90 و120 حرفًا.',
        'invalid_lang' => 'يرجى اختيار لغة نشطة صالحة.',
        'category_not_found' => 'تعذر العثور على الفئة المحددة.',
        'category_root_forbidden' => 'لا يمكن تعيين الفئات الجذرية مباشرة. يرجى اختيار فئة فرعية.',
        'category_lang_mismatch' => 'الفئة المحددة لا تتطابق مع اللغة المحددة.',
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
        'default_sort' => 'الأحدث',
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
        'lang_title' => 'اختيار اللغة',
        'ok' => 'OK',
        'search_placeholder' => 'Search language (e.g. English, JP)...',
        'category_title' => 'Select Category',
        'all' => 'All (Unfiltered)',
    ],

    // === Wizard ===
    'wizard' => [
        'step' => [
            'language' => 'اختيار اللغة',
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
    'hero_slider_add' => 'إرسال إلى شريط التمرير',
    'hero_slider_remove' => 'إزالة من شريط التمرير',
    'hero_slider_added' => 'تم الإرسال إلى شريط التمرير.',
    'hero_slider_removed' => 'تمت الإزالة من شريط التمرير.',
    'hero_slider_error' => 'فشل تحديث شريط التمرير.',
    'hero_slider_badge' => 'Slider',
    'deck_add' => 'تحديد كمميز',
    'deck_remove' => 'إزالة من المميزة',
    'deck_added' => 'تم التحديد كمميز.',
    'deck_removed' => 'تمت الإزالة من المميزة.',
    'deck_error' => 'فشل تحديث حالة التمييز.',
    'deck_badge' => 'Featured',

    'picker' => [
        'gallery' => 'اختر معرضًا موجودًا',
        'file' => 'اختر ملفًا موجودًا',
        'video' => 'اختر فيديو موجودًا',
    ],

    // === Latest News ===
    'latest_news' => [
        'title' => 'آخر الأخبار',
        'subtitle' => 'عرض جميع الأخبار',
        'description' => 'لم يتم نشر أي أخبار بعد.',
    ],

    // === AJAX & Relation Messages ===
    'ajax' => [
        'success' => 'Success',
        'error' => 'حدث خطأ',
        'database_error' => 'خطأ في قاعدة البيانات',
        'record_not_found' => 'الخبر غير موجود!',
        'unknown_type' => 'نوع علاقة غير معروف',
        'relation_updated' => 'تم تحديث العلاقة بنجاح',
    ],

    // === AI Assistant ===
    'ai' => [
        'generate_deck' => 'إنشاء مقدمة بالذكاء الاصطناعي',
        'suggest_headlines' => 'اقتراح عناوين بالذكاء الاصطناعي',
        'generate_seo' => 'إنشاء SEO بالذكاء الاصطناعي',
        'generating' => 'جاري الإنشاء...',
        'missing_key' => 'مفتاح Gemini API غير مهيأ في Google Services Hub.',
        'content_required' => 'عنوان الخبر أو محتواه مطلوب للإنشاء بالذكاء الاصطناعي.',
        'select_headline' => 'اختر عنوانًا',
        'deck_success' => 'تم إنشاء المقدمة بنجاح (%d حرفًا)',
        'deck_error' => 'فشل إنشاء المقدمة.',
        'headline_success' => 'تم اقتراح العناوين بنجاح.',
        'headline_error' => 'فشل اقتراح العناوين.',
        'warning_title' => 'Warning',
        'error_title' => 'Error',
        'invalid_action' => 'إجراء ذكاء اصطناعي غير صالح.',
        'copy' => 'استخدم هذا',
    ],
];
