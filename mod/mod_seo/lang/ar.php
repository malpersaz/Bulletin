<?php
$lang['mod_seo'] = array(
  'title' => 'تحكم SEO',
  'list' => 'ترتيبات جوجل (SEO)',
  'settings' => 'إعدادات SEO',
  'button' => array(
    'save' => 'حفظ الإعدادات',
    'back' => 'رجوع',
    'upload' => 'اختر ملف JSON',
  ),
  'admin' => array(
    'columns' => array(
      'url' => 'مسار URL',
      'name' => 'عنوان الصفحة',
      'type' => 'النوع',
      'clicks' => 'النقرات',
      'impressions' => 'الظهور',
      'ctr' => 'CTR (%)',
      'position' => 'متوسط الترتيب',
    ),
    'labels' => array(
      'property_url' => 'رابط خاصية Google Search Console (مثال: sc-domain:naturalworld.tech)',
      'json_file' => 'ملف مفتاح Google API (JSON)',
      'credentials_status' => 'حالة API',
      'credentials_ok' => 'تم تحميل مفتاح API الخاص بـ JSON',
      'credentials_missing' => 'مفتاح API الخاص بـ JSON مفقود! يرجى تحميله من صفحة الإعدادات.',
    )
  ),
  'messages' => array(
    'success' => 'تم تحديث الإعدادات بنجاح.',
    'error' => 'حدث خطأ.',
    'json_invalid' => 'ملف JSON غير صالح!',
  ),
  'role_errors' => array(
    'list' => 'ليس لديك صلاحية لعرض تحكم SEO',
    'settings' => 'ليس لديك صلاحية لإدارة إعدادات SEO',
  )
);
