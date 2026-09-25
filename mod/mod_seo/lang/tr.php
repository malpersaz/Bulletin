<?php
$lang['mod_seo'] = [
  'title' => 'SEO Kontrolü',
  'list' => 'Google Sıralaması (SEO)',
  'settings' => 'SEO Ayarları',
  'button' => [
    'save' => 'Ayarları Kaydet',
    'back' => 'Geri',
    'upload' => 'JSON Dosyası Seç',
    'setup_api' => 'API Ayarlarını Yapılandır',
    'refresh' => 'Yenile',
  ],
  'admin' => [
    'columns' => [
      'url' => 'URL Adresi',
      'name' => 'Sayfa Başlığı',
      'type' => 'Tür',
      'clicks' => 'Tıklama',
      'impressions' => 'Gösterim',
      'ctr' => 'CTR (%)',
      'position' => 'Ort. Konum',
    ],
    'labels' => [
      'property_url' => 'Google Search Console Mülk Alan Adı',
      'property_url_placeholder' => 'alanadiniz.com',
      'json_file' => 'Google API Anahtar Dosyası (JSON)',
      'credentials_status' => 'API Durumu',
      'credentials_ok' => 'JSON API anahtarı yüklendi',
      'credentials_missing' => 'JSON API anahtarı eksik! Lütfen ayarlar sayfasından yükleyin.',
      'settings_credentials_missing' => 'JSON API anahtarı henüz yüklenmedi. Lütfen aşağıdaki alandan anahtar dosyasını seçip kaydedin.',
      'onboarding_title' => 'API Yapılandırması Gerekli',
      'onboarding_desc' => 'Canlı Google arama analizlerini (Tıklamalar, Gösterimler ve Sıralamalar) almak için lütfen JSON API kimlik bilgilerinizi yükleyin.',
    ]
  ],
  'messages' => [
    'success' => 'Ayarlar başarıyla güncellendi.',
    'error' => 'Bir hata oluştu.',
    'json_invalid' => 'Geçersiz JSON dosyası!',
  ],
  'role' => [
    'name' => 'SEO Yönetim Rolleri',
    'roles' => 'SEO Kontrolü',
    'list' => 'SEO kontrolünü ve sıralamaları görüntüleyebilir',
    'settings' => 'SEO ayarlarını yönetebilir',
    'google_settings' => 'Google Cloud ve Gemini API ayarlarını yönetebilir',
  ],
  'role_errors' => [
    'list' => 'SEO Kontrolünü görüntüleme yetkiniz yok',
    'settings' => 'SEO Ayarlarını yönetme yetkiniz yok',
    'google_settings' => 'Google Hizmetleri ayarlarını yönetme yetkiniz yok',
  ],
  'google_settings' => 'Google Services Hub',
  'google' => [
    'bucket_name' => 'GCS (Google Cloud Storage) Bucket Adı',
    'gemini_api_key' => 'Gemini API Anahtarı',
    'credentials_file' => 'Google Servis Hesabı Dosyası (credentials.json)',
    'credentials_status' => 'Kimlik Bilgisi Durumu',
    'credentials_ok' => 'credentials.json dosyası yüklendi',
    'credentials_missing' => 'credentials.json dosyası eksik! Lütfen yükleyin.',
    'settings_ok' => 'Google ayarları başarıyla kaydedildi.',
  ]
];
