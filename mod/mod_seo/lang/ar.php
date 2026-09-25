<?php
$lang['mod_seo'] = [
  'title' => 'تحكم SEO',
  'list' => 'ترتيبات جوجل (SEO)',
  'settings' => 'إعدادات SEO',
  'button' => [
    'save' => 'حفظ الإعدادات',
    'back' => 'رجوع',
    'upload' => 'اختر ملف JSON',
    'setup_api' => 'إعداد API',
    'refresh' => 'تحديث',
  ],
  'admin' => [
    'columns' => [
      'url' => 'مسار URL',
      'name' => 'عنوان الصفحة',
      'type' => 'النوع',
      'clicks' => 'النقرات',
      'impressions' => 'الظهور',
      'ctr' => 'CTR (%)',
      'position' => 'متوسط الترتيب',
    ],
    'labels' => [
      'property_url' => 'نطاق موقع Google Search Console',
      'property_url_placeholder' => 'yourdomain.com',
      'json_file' => 'ملف مفتاح Google API (JSON)',
      'credentials_status' => 'حالة API',
      'credentials_ok' => 'تم تحميل مفتاح API الخاص بـ JSON',
      'credentials_missing' => 'مفتاح API الخاص بـ JSON مفقود! يرجى تحميله من صفحة الإعدادات.',
      'settings_credentials_missing' => 'لم يتم تحميل مفتاح API الخاص بـ JSON بعد. يرجى اختيار الملف أدناه وحفظه.',
      'onboarding_title' => 'مطلوب إعداد API',
      'onboarding_desc' => 'للحصول على تحليلات بحث Google المباشرة (النقرات، الظهور، والترتيب)، يرجى تحميل بيانات اعتماد JSON الخاصة بك.',
    ]
  ],
  'messages' => [
    'success' => 'تم تحديث الإعدادات بنجاح.',
    'error' => 'حدث خطأ.',
    'json_invalid' => 'ملف JSON غير صالح!',
  ],
  'role' => [
    'name' => 'أدوار إدارة تحسين محركات البحث (SEO)',
    'roles' => 'تحكم SEO',
    'list' => 'يمكنه عرض لوحة تحكم وترتيبات SEO',
    'settings' => 'يمكنه إدارة إعدادات SEO',
    'google_settings' => 'يمكنه إدارة إعدادات Google Cloud و Gemini API',
  ],
  'role_errors' => [
    'list' => 'ليس لديك صلاحية لعرض تحكم SEO',
    'settings' => 'ليس لديك صلاحية لإدارة إعدادات SEO',
    'google_settings' => 'ليس لديك إذن لإدارة خدمات Google',
  ],
  'google_settings' => 'Google Services Hub',
  'google' => [
    'bucket_name' => 'اسم حاوية GCS (Google Cloud Storage)',
    'gemini_api_key' => 'مفتاح Gemini API',
    'credentials_file' => 'ملف حساب خدمة Google (credentials.json)',
    'credentials_status' => 'حالة بيانات الاعتماد',
    'credentials_ok' => 'تم تحميل ملف credentials.json',
    'credentials_missing' => 'ملف credentials.json مفقود! يرجى تحميله.',
    'settings_ok' => 'تم حفظ إعدادات Google بنجاح.',
  ]
];
