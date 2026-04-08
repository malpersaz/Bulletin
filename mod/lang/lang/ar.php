<?php
/**
 * Language Module - Arabic Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['lang'] = [
    'installer' => [
        'table_exists' => "جدول 'lang' مثبت مسبقاً في قاعدة البيانات",
        'sql_added' => "تمت إضافة الملف 'lang.sql' إلى قاعدة البيانات",
        'lang_added' => 'تمت إضافة <b>%s</b> إلى قاعدة البيانات',
        'lang_exists' => '<b>%s</b> موجود مسبقاً في قاعدة البيانات',
    ],
    'system' => [
        'starting' => 'يبدأ التثبيت...',
        'installing' => 'جارٍ التثبيت: %s',
    ],
    'admin' => [
        'title' => 'إدارة اللغات',
        'headline' => 'اللغات',
        'body' => 'فعّل اللغات أو عطّلها وحدد اللغة الافتراضية للموقع من لوحة واحدة ملائمة للهاتف.',
        'status_active' => 'نشط',
        'status_inactive' => 'غير نشط',
        'default_badge' => 'افتراضي',
        'current_badge' => 'الواجهة الحالية',
        'code_label' => 'الرمز',
        'activate' => 'تفعيل',
        'deactivate' => 'تعطيل',
        'set_default' => 'تعيين كافتراضي',
        'empty_state' => 'لم يتم العثور على أي لغة.',
        'permission_denied' => 'ليست لديك صلاحية لإدارة اللغات.',
        'back_panel' => 'اللوحة',
        'back_home' => 'الموقع',
        'back' => 'رجوع',
    ],
    'messages' => [
        'activated' => 'أصبحت %s نشطة الآن.',
        'deactivated' => 'أصبحت %s غير نشطة الآن.',
        'default_changed' => 'أصبحت %s اللغة الافتراضية الآن.',
    ],
    'errors' => [
        'language_not_found' => 'تعذر العثور على اللغة المحددة.',
        'unknown_action' => 'إجراء اللغة المطلوب غير صالح.',
        'cannot_deactivate_default' => 'اختر لغة افتراضية أخرى قبل تعطيل هذه اللغة.',
        'cannot_deactivate_last_active' => 'يجب أن تبقى لغة واحدة نشطة على الأقل.',
        'cannot_deactivate_current_interface' => 'غيّر لغة واجهة الإدارة الحالية قبل تعطيلها.',
        'update_failed' => 'تعذر تحديث حالة اللغة.',
    ],
];
