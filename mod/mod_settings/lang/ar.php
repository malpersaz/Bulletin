<?php
$lang['mod_settings'] = [
    // Page & Section Titles
    'title' => 'إعدادات الموقع العامة',
    'description' => 'إدارة إعدادات الموقع لجميع اللغات.',
    'lang_settings' => 'إعدادات اللغة',
    'social' => 'روابط وسائل التواصل الاجتماعي',
    'add_social' => 'إضافة رابط جديد',

    // Placeholders
    'platform_ph' => 'المنصة (مثلاً: تويتر)',
    'url_ph' => 'الرابط (https://...)',
    'icon_ph' => 'فئة الأيقونة (مثلاً: bi bi-twitter-x)',

    // Form Labels
    'title' => 'عنوان الموقع',
    'description' => 'وصف الموقع',
    'owner_name' => 'اسم المالك',
    'established_year' => 'سنة التأسيس',
    'phone' => 'الهاتف',
    'whatsapp' => 'واتساب',
    'email' => 'البريد الإلكتروني',
    'office_address' => 'عنوان المكتب',
    'work_address' => 'عنوان العمل',

    // Buttons & Actions
    'save' => 'حفظ',
    'list' => 'قائمة',
    'button' => [
        'cancel' => 'إلغاء',
        'close' => 'إغلاق',
        'edit' => 'تعديل',
        'save_and_close' => 'حفظ وإغلاق',
        'generate_assets' => 'إنشاء الأيقونات',
        'confirm' => 'موافق',
        'leave_page' => 'مغادرة الصفحة',
        'stay' => 'البقاء',
    ],

    // Messages & Status
    'updated' => 'تم تحديث الإعدادات بنجاح.',

    // Errors
    'perm_edit' => 'ليس لديك صلاحية تعديل الإعدادات العامة.',
    'error' => 'خطأ',

    // === Access Control (ACL) ===
    'roll' => [
      'ad' => 'أدوار إدارة إعدادات الموقع',
      'list' => 'يمكن عرض إعدادات الموقع',
      'edit' => 'يمكن تعديل إعدادات الموقع',
      'add' => 'يمكن إضافة إعدادات موقع جديدة (للغات)',
      'delete' => 'يمكن حذف إعدادات الموقع'
    ],

    // === Permission Error Messages ===
    'permission_errors' => [
        'settings_edit' => 'ليس لديك صلاحية تعديل إعدادات الموقع.',
        'settings_view' => 'ليس لديك صلاحية عرض إعدادات الموقع.',
    ],

    // === Settings Page ===
    'settings' => [
        'title' => 'إدارة إعدادات الموقع',
    ],
    'sections' => [
        'seo_title' => 'الميتا والتحقق',
        'seo_description' => 'أكواد التحقق وحقول الميتا وحقول التضمين.',
        'assets_title' => 'الملفات',
        'assets_description' => 'ارفع شعار PNG واحدا وسيقوم النظام بإنشاء الايقونات المطلوبة تلقائيا.',
        'social_title' => 'وسائل التواصل الاجتماعي',
        'social_description' => 'إدارة روابط وسائل التواصل الاجتماعي.',
    ],

    // === Success Messages ===
    'messages' => [
        'success' => [
            'updated' => 'تم تحديث الإعدادات بنجاح.',
            'assets_generated' => 'تم إنشاء ملفات الأيقونات بنجاح.',
        ],
    ],

    // === Additional Form Labels ===
    'map_embed_code' => 'كود تضمين الخريطة',
    'google_search_console' => 'علامة Google Search',
    'google_analytics' => 'كود Google Analytics',
    'meta_tags' => 'علامات Meta إضافية',
    'show_logo_title' => 'عرض الشعار',
    'show_logo_desc' => 'عرض الشعار بدلاً من اسم الموقع',
    'logo_path' => 'الشعار الرئيسي (PNG)',
    'favicon_path' => 'مسار Favicon',

    // === UI Labels ===
    'select_language' => 'اختر اللغة',
    'site_name' => 'اسم الموقع',
    'site_description' => 'وصف الموقع',

    // === File Upload & Management ===
    'current_logo' => 'Current Logo:',
    'logo_not_uploaded' => 'Logo not uploaded.',
    'logo_help_text' => 'ارفع ملف PNG بخلفية شفافة. الحد الأدنى 512x512 بكسل، والمقاس الموصى به 1024x1024 بكسل أو أكبر. سيقوم النظام بإنشاء ملفات favicon والايقونات وmanifest تلقائيا.',
    'favicon_not_uploaded' => 'Favicon not uploaded.',
    'favicon_help_text' => 'تنسيق ICO أو PNG. سيتم تغيير الحجم إلى 32x32.',
    'not_uploaded' => 'Not uploaded.',
    'png_96_help' => 'PNG format. Will be resized to 96x96.',
    'png_180_help' => 'PNG format. Will be resized to 180x180.',
    'view_file' => 'View File',
    'webmanifest_help' => '.webmanifest or .json format.',
    'png_192_help' => 'PNG format. Will be resized to 192x192.',
    'png_512_help' => 'PNG format. Will be resized to 512x512.',
    'delete' => 'Delete',
    'confirm_delete' => 'Are you sure you want to delete this item?',
    'delete_error' => 'Could not delete:',
    'delete_success' => 'Deleted',
    'generic_error' => 'An error occurred.',
    'invalid_extension' => 'Invalid file extension. Allowed: %s',
    'manifest_icon_192' => 'Manifest Icon 192x192',
    'manifest_icon_512' => 'Manifest Icon 512x512',
    'favicon_96' => 'Favicon 96x96',
    'apple_touch_icon' => 'Apple Touch Icon',
    'web_manifest' => 'Web Manifest',
    'choose_file' => 'اختر ملف',
    'no_file_selected' => 'لم يتم اختيار ملف',
    'save_error' => 'An error occurred while saving settings',
    'loading_message' => 'جاري التحميل...',
    'invalid_params' => 'Missing or invalid parameters.',
    'settings_not_found' => 'Settings not found for the specified language.',
    'update_error' => 'Database record could not be updated.',
    'delete_fail' => 'File could not be deleted.',
    'delete_unknown_error' => 'خطأ غير معروف أثناء حذف الملف.',
    'unsaved_changes' => 'لديك تغييرات غير محفوظة. تغيير اللغة سيؤدي إلى فقدانها.',
    'asset_generation' => [
        'minimum_size' => 'يجب أن يكون الشعار الرئيسي بحجم لا يقل عن 512x512 بكسل.',
        'requires_imagick' => 'يتطلب إنشاء الأصول تلقائيا وجود Imagick.',
        'output_not_writable' => 'مجلد إخراج الأصول غير قابل للكتابة.',
        'missing_upload' => 'اختر شعار PNG أولا.',
    ],
    'missing_language' => 'معلومات اللغة مفقودة.',
    'settings_required_fields' => 'المنصة والرابط مطلوبان.',
    'no_records_found' => 'لا توجد سجلات.',
    'no_url_configured' => 'لم يتم تكوين رابط',
    'header_style_title' => 'نمط عرض الرأس',
    'header_style_desc' => 'اختر كيفية ظهور الشعار واسم الموقع في الرأس.',
    'header_style_square' => 'شعار مربع فقط',
    'header_style_rect' => 'شعار مستطيل فقط',
    'header_style_square_name' => 'شعار مربع واسم الموقع',
    'header_style_rect_name' => 'شعار مستطيل واسم الموقع',
    'logo_rect_title' => 'الشعار المستطيل (3:1)',
    'logo_square_title' => 'الشعار المربع (1:1)',
    'logo_rect_help_text' => 'الشعار الأفقي بنسبة 3:1 المستخدم في الموقع.',
    'logo_square_help_text' => 'الشعار المربع بنسبة 1:1. يستخدم لإنشاء الأيقونات.',
    'crop_square' => 'اقتصاص الشعار المربع (1:1)',
    'crop_rect' => 'اقتصاص الشعار المستطيل (3:1)',
    'crop_symbol' => 'اقتصاص الرمز (1:1)',
    'logo_symbol_title' => 'أيقونة الموقع (1:1)',
    'logo_symbol_help_text' => 'الرمز المربع الذي يحتوي على الشعار فقط. يُستخدم بالأولوية لإنشاء الأيقونات.',
];
