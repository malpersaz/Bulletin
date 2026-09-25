<?php
/**
 * Publication Category Module - Arabic Language File
 *
 * @package AKM ARTIST CMS
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
    'list' => 'قائمة الفئات',
    'add' => 'إضافة فئة',
    'edit' => 'تعديل الفئة',
    'information' => 'معلومات',
    'success' => 'تم بنجاح',
    'error' => 'حدث خطأ',
    'remove_record' => 'هل أنت تأكد من رغبتك في حذف هذا السجل؟',
    'choose_wish' => 'اختر حسب رغبتك',
    'remove_image' => 'هل أنت تأكد من إزالة هذه الصورة؟',

    // === Message Structure ===
    'message' => [
        'success' => [
            'add' => 'تمت إضافة الفئة بنجاح!',
            'edit' => 'تم تحديث الفئة بنجاح!',
        ],
        'error' => [
            'add' => 'فشلت إضافة الفئة!',
            'edit' => 'فشل تحديث الفئة!',
        ],
        'information' => [
            'moderate' => 'انقر انقراً مزدوجاً على الفئة التي تريد تعديلها',
            'move' => 'اسحب وافلت الفئة لتغيير ترتيبها',
        ],
    ],

    'errors' => [
        'record' => 'لم يتم العثور على السجل المحدد!',
    ],

    'button' => [
        'add' => 'إضافة',
        'edit' => 'تعديل',
        'refresh' => 'تحديث',
        'delete' => 'حذف',
        'cancel' => 'إلغاء',
        'remove' => 'إزالة',
        'yes' => 'نعم',
        'no' => 'لا',
        'save' => 'حفظ',
        'gallery' => 'المعرض',
        'file' => 'ملف',
    ],

    'grid' => [
        'showing_results' => 'عرض {from} إلى {to} من أصل {total} نتيجة',
        'page' => 'الصفحة',
        'page_count' => 'عدد الصفحات',
        'loading' => 'جاري تحميل البيانات، يرجى الانتظار...',
        'no_data' => 'لم يتم العثور على بيانات',
        'connection_error' => 'خطأ في الاتصال',
        'search' => 'بحث',
        'select_at_least_one' => 'يرجى تحديد صف واحد على الأقل',
        'select_only_one' => 'يرجى تحديد صف واحد فقط',
    ],

    'validator' => [
        'required' => 'يرجى عدم ترك الحقل %s فارغاً',
        'length' => 'يجب أن يكون الحقل %s بين %s و %s حرفاً',
        'email' => 'يرجى إدخال عنوان بريد إلكتروني صالح في الحقل %s',
        'unique' => 'القيمة %s التي أدخلتها مسجلة بالفعل في النظام!',
        'inarray' => 'الحقل %s يقبل فقط القيم \' %s \'',
    ],

    'role' => [
        'name' => 'أدوار إدارة فئات المنشورات',
        'add' => 'يمكنه إضافة فئة',
        'edit' => 'يمكنه تعديل فئة',
        'delete' => 'يمكنه حذف فئة',
        'list' => 'يمكنه عرض إدارة الفئات',
        'moderate_edit' => 'يمكنه تعديل فئات الآخرين',
        'moderate_delete' => 'يمكنه حذف فئات الآخرين',
    ],

    'role_errors' => [
        'moderate_edit' => 'لا يمكنك تعديل فئات الآخرين',
        'moderate_delete' => 'لا يمكنك حذف فئات الآخرين',
        'add' => 'ليس لديك صلاحية لإضافة فئات',
        'edit' => 'ليس لديك صلاحية لتعديل الفئات',
        'list' => 'ليس لديك صلاحية لعرض إدارة الفئات',
        'delete' => 'ليس لديك صلاحية لحذف الفئات',
    ],

    'actions' => [
        'add' => 'إضافة فئة',
        'edit' => 'تعديل فئة',
        'list' => 'قائمة الفئات',
        'delete' => 'حذف فئة',
        'cancel' => 'إلغاء',
        'save' => 'حفظ',
        'update' => 'تحديث',
        'remove' => 'إزالة',
    ],

    'messages' => [
        'success' => [
            'added' => 'تمت إضافة الفئة بنجاح!',
            'updated' => 'تم تحديث الفئة بنجاح!',
            'deleted' => 'تم حذف الفئة بنجاح!',
            'reordered' => 'تم تحديث ترتيب الفئات!',
            'image_removed' => 'تمت إزالة الصورة بنجاح!',
        ],
        'error' => [
            'not_added' => 'فشلت إضافة الفئة!',
            'not_updated' => 'فشل تحديث الفئة!',
            'not_deleted' => 'فشل حذف الفئة!',
            'not_found' => 'لم يتم العثور على سجل الفئة المحدد!',
            'image_not_removed' => 'فشلت إزالة الصورة!',
            'validation' => 'يرجى ملء الحقول المطلوبة بشكل صحيح!',
        ],
        'info' => [
            'edit_double_click' => 'انقر نقراً مزدوجاً على الفئة للتعديل',
            'drag_reorder' => 'اسحب وافلت الفئة لإعادة الترتيب',
        ],
    ],

    'options' => [
        'yes' => 'نعم',
        'no' => 'لا',
    ],

    'form' => [
        'basic_info' => 'المعلومات الأساسية',
        'seo_settings' => 'إعدادات SEO',
        'display_settings' => 'إعدادات العرض',
        'advanced_settings' => 'إعدادات متقدمة',
        'id' => 'المعرف',
        'name' => 'اسم الفئة',
        'parent_category' => 'الفئة الرئيسية',
        'order' => 'الترتيب',
        'language' => 'لغة العرض',
        'is_root' => 'فئة جذرية (مخفية من العرض)',
        'seo_url' => 'رابط SEO',
        'meta_title' => 'عنوان ميتا (لمحركات البحث)',
        'meta_keywords' => 'كلمات ميتا المفتاحية (لمحركات البحث)',
        'meta_description' => 'وصف ميتا (لمحركات البحث)',
        'add_to_menu' => 'إضافة الفئة إلى القائمة',
        'image' => 'صورة الفئة',
        'description' => 'وصف الفئة',
        'access_control' => 'التحكم في الوصول',
    ],

    'columns' => [
        'id' => 'المعرف',
        'language' => 'لغة العرض',
        'name' => 'اسم الفئة',
        'parent_category' => 'الفئة الرئيسية',
        'order' => 'الترتيب',
        'root_category' => 'فئة جذرية',
        'seo_url' => 'رابط SEO',
        'meta_title' => 'عنوان ميتا',
        'meta_keywords' => 'كلمات ميتا المفتاحية',
        'meta_description' => 'وصف ميتا',
        'add_to_menu' => 'إضافة إلى القائمة',
        'image' => 'الصورة',
        'status' => 'الحالة',
        'actions' => 'الإجراءات',
    ],

    'buttons' => [
        'add' => 'إضافة فئة جديدة',
        'edit' => 'تعديل المحدد',
        'delete' => 'حذف المحدد',
        'refresh' => 'تحديث',
        'cancel' => 'إلغاء',
        'remove' => 'إزالة',
        'yes' => 'نعم',
        'no' => 'لا',
    ],

    'validation' => [
        'required' => 'يرجى عدم ترك الحقل %s فارغاً',
        'length' => 'يجب أن يكون الحقل %s بين %s و %s حرفاً',
        'email' => 'يرجى إدخال عنوان بريد إلكتروني صالح',
        'unique' => '%s مسجل بالفعل في النظام',
        'inarray' => 'الحقل %s يقبل فقط القيم \' %s \'',
        'url' => 'يرجى إدخال رابط صالح',
        'slug' => 'يرجى إدخال slug صالح',
    ],

    'permissions' => [
        'title' => 'صلاحيات إدارة فئات المنشورات',
        'add' => 'يمكنه إضافة فئة',
        'edit' => 'يمكنه تعديل فئة',
        'delete' => 'يمكنه حذف فئة',
        'list' => 'يمكنه عرض إدارة الفئات',
        'edit_others' => 'يمكنه تعديل فئات الآخرين',
        'delete_others' => 'يمكنه حذف فئات الآخرين',
    ],

    'permission_errors' => [
        'edit_others' => 'لا يمكنك تعديل فئات الآخرين',
        'delete_others' => 'لا يمكنك حذف فئات الآخرين',
        'add' => 'ليس لديك صلاحية لإضافة فئات',
        'edit' => 'ليس لديك صلاحية لتعديل الفئات',
        'list' => 'ليس لديك صلاحية لعرض إدارة الفئات',
        'delete' => 'ليس لديك صلاحية لحذف الفئات',
    ],

    'admin' => [
        'columns' => [
            'id' => 'المعرف',
            'language' => 'لغة العرض',
            'name' => 'اسم الفئة',
            'parent_id' => 'الفئة الرئيسية',
            'order' => 'الترتيب',
            'root' => 'فئة جذرية (مخفية من العرض)',
            'seo' => 'رابط الفئة',
            'title' => 'العنوان (لمحركات البحث)',
            'keywords' => 'الكلمات المفتاحية (لمحركات البحث)',
            'description' => 'الوصف (لمحركات البحث)',
            'menu' => 'إرسال الفئة إلى القائمة',
            'image' => 'الصورة',
            'acl_id' => 'التحكم في الوصول',
        ],
        'select_value' => [
            'root_e' => 'نعم',
            'root_h' => 'لا',
        ],
    ],

    'confirmations' => [
        'remove_image' => 'هل أنت تأكد من إزالة هذه الصورة؟',
        'remove_record' => 'هل أنت تأكد من حذف هذا السجل؟',
        'delete_single' => 'هل أنت تأكد من حذف هذه الفئة؟',
        'delete_multiple' => 'هل أنت تأكد من حذف :count فئة محددة؟',
        'make_root' => 'هل أنت تأكد من جعل هذه الفئة جذرية؟',
        'remove_root' => 'هل أنت تأكد من إزالة حالة الفئة الجذرية؟',
    ],

    'success_messages' => [
        'operation_completed' => 'تمت العملية بنجاح!',
        'changes_saved' => 'تم حفظ التغييرات!',
        'category_published' => 'تم نشر الفئة بنجاح!',
        'category_unpublished' => 'تم إلغاء نشر الفئة بنجاح!',
    ],

    'error_messages' => [
        'general' => 'حدث خطأ. يرجى المحاولة مرة أخرى.',
        'network' => 'خطأ في الشبكة. يرجى التحقق من اتصال الإنترنت.',
        'server' => 'خطأ في الخادم. يرجى المحاولة لاحقاً.',
        'fatal' => 'حدث خطأ جسيم!',
        'has_subcategories' => 'لا يمكن حذف فئة تحتوي على فئات فرعية',
        'has_content' => 'لا يمكن حذف فئة تحتوي على محتوى',
    ],

    'help' => [
        'category_name' => 'أدخل اسماً واضحاً وممتعاً لهذه الفئة',
        'parent_category' => 'اختر الفئة الرئيسية (اتركه فارغاً للمستوى الرئيسي)',
        'order' => 'يحدد ترتيب العرض (القيم الأقل تظهر أولاً)',
        'root_category' => 'الفئات الجذرية للتنظيم ولا تظهر للعامة',
        'seo_url' => 'نسخة صديقة لمحركات البحث من اسم الفئة',
        'meta_title' => 'العنوان المعروض في نتائج محركات البحث',
        'meta_description' => 'الوصف المعروض في نتائج محركات البحث',
        'add_to_menu' => 'إضافة الفئة تلقائياً إلى قائمة التنقل',
        'access_control' => 'تحديد أدوار المستخدمين المسوح لهم بالوصول',
    ],

    'placeholders' => [
        'category_name' => 'أدخل اسم الفئة...',
        'seo_url' => 'category-url-slug',
        'meta_title' => 'أدخل عنوان ميتا...',
        'meta_description' => 'أدخل وصف ميتا...',
        'meta_keywords' => 'كلمة1, كلمة2, كلمة3',
        'category_description' => 'أدخل وصف الفئة (اختياري)...',
    ],

    'empty' => [
        'no_categories' => 'لم يتم إنشاء أي فئات بعد',
        'no_results' => 'لم يتم العثور على فئات تطابق معايير البحث',
        'add_first' => 'انقر على الزر لإنشاء أول فئة',
        'no_subcategories' => 'لم يتم العثور على فئات فرعية',
        'no_content' => 'لا يوجد محتوى في هذه الفئة بعد',
    ],

    'loading' => [
        'saving' => 'جاري الحفظ...',
        'loading' => 'جاري التحميل...',
        'processing' => 'جاري المعالجة...',
        'deleting' => 'جاري الحذف...',
    ],

    'tree_view' => [
        'expand_all' => 'توسيع الكل',
        'collapse_all' => 'طوي الكل',
        'show_hierarchy' => 'عرض التسلسل الهرمي',
        'show_flat' => 'عرض مسطح',
        'root_only' => 'الفئات الجذرية فقط',
        'leaf_only' => 'الفئات الفرعية فقط',
    ],

    'seo' => [
        'auto_generate_slug' => 'إنشاء slug تلقائي للرابط',
        'preview_snippet' => 'معاينة نتيجة البحث',
        'character_count' => 'عدد الأحرف: :count',
        'recommended_length' => 'الموصى به: :min-:max حرفاً',
        'good' => 'جيد',
        'too_short' => 'قصير جداً',
        'too_long' => 'طويل جداً',
    ],

    'bulk_operations' => [
        'bulk_edit' => 'تعديل جماعي',
        'bulk_delete' => 'حذف جماعي',
        'bulk_move' => 'نقل إلى فئة',
        'bulk_publish' => 'نشر المحدد',
        'bulk_unpublish' => 'إلغاء نشر المحدد',
        'select_all' => 'تحديد الكل',
        'deselect_all' => 'إلغاء تحديد الكل',
    ],

    'ajax' => [
        'success' => 'تم بنجاح',
        'error' => 'خطأ',
    ],

    'responses' => [
        'success' => 'Success',
        'error' => 'Error',
        'fatal_error' => 'ERROR',
    ],

];
