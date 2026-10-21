<?php
/**
 * Publication Management Module - Arabic Language File
 *
 * @package AKM Bulletin CMS
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
    'add' => 'إضافة منشور',
    'edit' => 'تعديل المنشور',
    'list' => 'قائمة المنشورات',
    'information' => 'معلومات!',
    'success' => 'تمت العملية بنجاح!',
    'error' => 'حدث خطأ!',
    'error_occurred' => 'حدث خطأ!',
    'wizard_loading' => 'جاري تحميل المعالج...',
    'fatal_error' => 'حدث خطأ جسيم!',
    'remove_image' => 'هل أنت تأكد من إزالة هذه الصورة؟',
    'remove_image_success' => 'تمت إزالة الصورة',
    'choose_wish' => 'يرجى الاختيار حسب رغبتك',
    'remove_record' => 'هل أنت تأكد من حذف هذا السجل؟',
    'delete_confirm' => 'هل أنت تأكد من حذف هذا المنشور؟',
    'quick_unpublish_confirm' => 'هل أنت تأكد من إلغاء نشر هذا المنشور؟',
    'quick_publish_confirm' => 'هل أنت تأكد من نشر هذا المنشور؟',
    'add_image' => 'إضافة صورة',
    'upload_change_image' => 'رفع / تغيير الصورة',
    'local' => 'محلي',
    'embed' => 'تضمين',

    // === Filter Menu ===
    'filter' => [
        'title' => 'تصفية وترتيب',
        'close' => 'إغلاق',
        'sort_label' => 'ترتيب',
        'newest' => 'الأحدث',
        'oldest' => 'الأقدم',
        'title_az' => 'العنوان (أ-ي)',
        'title_za' => 'العنوان (ي-أ)',
        'view_count' => 'عدد المشاهدات',
        'limit_label' => 'حد العرض',
        'lang_label' => 'اختيار اللغة',
        'selected_lang' => 'اللغة المحددة',
        'date_label' => 'النطاق الزمني',
        'start_date' => 'البداية',
        'end_date' => 'النهاية',
        'clear' => 'مسح',
        'apply' => 'عرض النتائج',
        'select_lang' => 'اختر اللغة',
        'all_langs' => 'جميع اللغات',
        'select_label' => 'تحديد',
        'delete_error' => 'فشل الحذف!',
        'video_success_sim' => 'تمت إضافة الفيديو بنجاح (محاكاة)',
        'video_embed_error' => 'رمز تضمين غير صالح',
        'video_title_error' => 'عنوان غير صالح',
    ],

    // === Messages ===
    'message' => [
        'success' => [
            'add' => 'تمت إضافة المنشور!',
            'edit' => 'تم تحديث المنشور!',
        ],
        'error' => [
            'add' => 'تعذر إضافة المنشور!',
            'edit' => 'تعذر تحديث المنشور!',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'لم يتم العثور على السجل المحدد!',
    ],

    // === Buttons ===
    'button' => [
        'next' => 'التالي',
        'prev' => 'السابق',
        'send' => 'إرسال',
        'add' => 'إضافة',
        'edit' => 'تعديل',
        'refresh' => 'تحديث',
        'delete' => 'حذف',
        'gallery' => 'معرض الصور',
        'file' => 'مدير الملفات',
        'cancel' => 'إلغاء',
        'remove' => 'إزالة',
        'yes' => 'نعم',
        'no' => 'لا',
        'save' => 'حفظ',
    ],

    // === Admin Grid Columns ===
    'admin' => [
        'columns' => [
            'id' => 'المعرف',
            'language' => 'اللغة',
            'title' => 'عنوان المنشور',
            'category_id' => 'الفئة',
            'status' => 'الحالة',
            'created_at' => 'تاريخ الإنشاء',
            'actions' => 'الإجراءات',
        ],
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'يرجى عدم ترك الحقل %s فارغًا.',
        'length' => 'يجب أن يكون الحقل %s بين %s و %s حرفًا.',
        'unique' => 'القيمة %s موجودة بالفعل. يرجى اختيار قيمة أخرى لـ %s.',
        'inarray' => 'الحقل %s يمكنه قبول هذه القيم فقط: %s.',
        'deck_length' => 'يرجى إدخال نص تمهيدي يتراوح بين 90 و 120 حرفًا.',
    ],

];
