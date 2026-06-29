<?php
$lang['mod_seo'] = array(
  'title' => 'SEO Kontrolü',
  'list' => 'Google Sıralaması (SEO)',
  'settings' => 'SEO Ayarları',
  'button' => array(
    'save' => 'Ayarları Kaydet',
    'back' => 'Geri',
    'upload' => 'JSON Dosyası Seç',
  ),
  'admin' => array(
    'columns' => array(
      'url' => 'URL Adresi',
      'name' => 'Sayfa Başlığı',
      'type' => 'Tür',
      'clicks' => 'Tıklama',
      'impressions' => 'Gösterim',
      'ctr' => 'CTR (%)',
      'position' => 'Ort. Konum',
    ),
    'labels' => array(
      'property_url' => 'Google Search Console Mülk URL\'si (örneğin: sc-domain:naturalworld.tech)',
      'json_file' => 'Google API Anahtar Dosyası (JSON)',
      'credentials_status' => 'API Durumu',
      'credentials_ok' => 'JSON API anahtarı yüklendi',
      'credentials_missing' => 'JSON API anahtarı eksik! Lütfen ayarlar sayfasından yükleyin.',
    )
  ),
  'messages' => array(
    'success' => 'Ayarlar başarıyla güncellendi.',
    'error' => 'Bir hata oluştu.',
    'json_invalid' => 'Geçersiz JSON dosyası!',
  ),
  'role_errors' => array(
    'list' => 'SEO Kontrolünü görüntüleme yetkiniz yok',
    'settings' => 'SEO Ayarlarını yönetme yetkiniz yok',
  )
);
