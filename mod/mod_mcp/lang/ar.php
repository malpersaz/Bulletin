<?php
/**
 * Amed Framework
 * MCP Module Arabic Language Pack
 * mod/mod_mcp/lang/ar.php
 */

$lang['mod_mcp'] = [
    'module_title' => 'مفاتيح MCP API',
    'page_intro' => 'أنشئ مفاتيح API لنقطة نهاية Model Context Protocol وأدرها. يقوم العملاء بالمصادقة بهذه المفاتيح لإدارة المحتويات وفئات المحتويات عن بُعد.',
    'endpoint_label' => 'نقطة نهاية MCP',
    'no_keys' => 'لم يتم إنشاء أي مفاتيح API بعد.',
    'key_name' => 'اسم المفتاح',
    'key_prefix_label' => 'بادئة المفتاح',
    'group_label' => 'مجموعة الصلاحيات',
    'status_label' => 'الحالة',
    'created_at_label' => 'تاريخ الإنشاء',
    'last_used_label' => 'آخر استخدام',
    'creator_label' => 'أنشئ بواسطة',
    'active' => 'نشط',
    'revoked' => 'ملغى',
    'never_used' => 'لم يستخدم بعد',
    'create_key_title' => 'إنشاء مفتاح API جديد',
    'name_placeholder' => 'مثال: عميل أتمتة المحتوى',
    'submit_create' => 'إنشاء المفتاح',
    'key_created_title' => 'تم إنشاء مفتاح API',
    'key_created_warning' => 'انسخ هذا المفتاح الآن. لن يظهر مرة أخرى أبدًا.',
    'copy_key' => 'نسخ المفتاح',
    'key_copied' => 'تم نسخ مفتاح API إلى الحافظة.',
    'reveal_close' => 'تم',
    'delete_confirm' => 'هل أنت متأكد من أنك تريد حذف مفتاح API هذا نهائيًا؟ سيفقد العملاء الذين يستخدمونه الوصول فورًا.',
    'toggle_activate' => 'تنشيط المفتاح',
    'toggle_revoke' => 'إلغاء المفتاح',
    'delete_key' => 'حذف المفتاح',
    'oauth_section_title' => 'عملاء OAuth 2.1',
    'oauth_hint' => 'العملاء المتصلون عبر نقاط /oauth (يستخدم ChatGPT هوية عميل موقعة خاصة به ولا يظهر هنا). تعطيل العميل يلغي رموزه فوراً؛ والحذف يزيل أيضاً أكواده ورموزه.',
    'oauth_no_clients' => 'لا يوجد عملاء OAuth مسجلون بعد. يقوم العملاء بتسجيل أنفسهم عبر /oauth/register عند الاتصال.',
    'oauth_tokens_active' => 'الرموز النشطة',
    'oauth_deactivate' => 'تعطيل العميل',
    'oauth_activate' => 'تنشيط العميل',
    'oauth_delete' => 'حذف العميل',
    'oauth_delete_confirm' => 'هل أنت متأكد من أنك تريد حذف عميل OAuth هذا نهائياً؟ ستتم إزالة أكواد التفويض والرموز الخاصة به فوراً.',
    'errors' => [
        'invalid_name' => 'يرجى إدخال اسم مفتاح بين 1 و100 حرف.',
        'invalid_group' => 'يرجى اختيار مجموعة صلاحيات صالحة.',
        'record' => 'لم يتم العثور على سجل مفتاح API.'
    ],
    'role_errors' => [
        'list' => 'ليست لديك صلاحية عرض مفاتيح MCP API.',
        'add' => 'ليست لديك صلاحية إنشاء مفاتيح MCP API.',
        'edit' => 'ليست لديك صلاحية تعديل مفاتيح MCP API.',
        'delete' => 'ليست لديك صلاحية حذف مفاتيح MCP API.'
    ],
    'message' => [
        'success' => [
            'add' => 'تم إنشاء مفتاح API بنجاح.',
            'edit' => 'تم تحديث مفتاح API بنجاح.',
            'delete' => 'تم حذف مفتاح API بنجاح.'
        ],
        'error' => [
            'add' => 'فشل إنشاء مفتاح API.',
            'edit' => 'فشل تحديث مفتاح API.',
            'delete' => 'فشل حذف مفتاح API.'
        ]
    ],
    'role' => [
        'name' => 'أدوار مفاتيح MCP',
        'list' => 'يمكن عرض مفاتيح MCP',
        'add' => 'يمكن إنشاء مفاتيح MCP',
        'edit' => 'يمكن تعديل مفاتيح MCP',
        'delete' => 'يمكن حذف مفاتيح MCP'
    ]
];
