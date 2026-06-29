<?php
$lang['mod_settings'] = [
    // Page & Section Titles
    'settings' => [
        'title' => 'Genel Site Ayarları',
        'description' => 'Tüm diller için site genelindeki ayarları yönetin.',
    ],
    'lang_settings' => 'Dil Ayarları',
    'social' => 'Sosyal Medya Bağlantıları',
    'add_social' => 'Yeni Link Ekle',

    // Messages
    'messages' => [
        'success' => [
            'updated' => 'Ayarlar başarıyla güncellendi.',
        ]
    ],

    // Placeholders
    'platform_ph' => 'Platform (örn: Twitter)',
    'url_ph' => 'URL (https://...)',
    'icon_ph' => 'İkon Sınıfı (örn: bi bi-twitter-x)',

    // Form Labels
    'title' => 'Site Başlığı',
    'description' => 'Site Açıklaması',
    'owner_name' => 'Sahip Adı',
    'established_year' => 'Kuruluş Yılı',
    'phone' => 'Telefon',
    'whatsapp' => 'WhatsApp',
    'email' => 'E-posta',
    'office_address' => 'Ofis Adresi',
    'work_address' => 'İş Adresi',

    // Buttons & Actions
    'save' => 'Kaydet',
    'list' => 'Listele',
    'button' => [
        'cancel' => 'Vazgeç',
        'close' => 'Kapat',
        'edit' => 'Düzenle',
        'save_and_close' => 'Kaydet ve Kapat',
        'generate_assets' => 'Ikonlari Uret',
        'confirm' => 'Tamam',
        'leave_page' => 'Sayfadan Ayril',
        'stay' => 'Kal',
    ],

    // Errors
    'perm_edit' => 'Genel ayarları düzenleme permissionniz bulunmuyor.',
    'error' => 'Hata',

    // === Access Control (ACL) ===
    'roll' => [
      'ad' => 'Site Ayarları Yönetim Rolleri',
      'list' => 'Site Ayarlarını Görüntüleyebilir',
      'edit' => 'Site Ayarlarını Düzenleyebilir',
      'add' => 'Yeni Site Ayarı Ekleyebilir (Diller için)',
      'delete' => 'Site Ayarını Silebilir'
    ],

    // === Permission Error Messages ===
    'permission_errors' => [
        'settings_edit' => 'Site ayarlarını düzenleme permissionniz bulunmuyor.',
        'settings_view' => 'Site ayarlarını görüntüleme permissionniz bulunmuyor.',
    ],

    // === Settings Page ===
    'settings' => [
        'title' => 'Site Ayarları Yönetimi',
    ],
    'sections' => [
        'seo_title' => 'Meta ve Doğrulama',
        'seo_description' => 'Doğrulama kodları, meta alanları ve gömme içerikleri.',
        'assets_title' => 'Varlıklar',
        'assets_description' => 'Tek bir PNG logo yukleyin; sistem gerekli ikonlari otomatik uretsin.',
        'social_title' => 'Sosyal Medya',
        'social_description' => 'Sosyal medya bağlantılarını yönetin.',
    ],

    // === Success Messages ===
    'messages' => [
        'success' => [
            'updated' => 'Ayarlar başarıyla güncellendi.',
            'assets_generated' => 'Ikon dosyalari basariyla uretildi.',
        ],
    ],

    // === Additional Form Labels ===
    'map_embed_code' => 'Harita Gömme Kodu',
    'google_search_console' => 'Google Search Etiketi',
    'google_analytics' => 'Google Analytics Kodu',
    'meta_tags' => 'Ek Meta Etiketleri',
    'show_logo_title' => 'Logo Göster',
    'show_logo_desc' => 'Site adı yerine logo gösterilir',
    'logo_path' => 'Ana Logo (PNG)',
    'favicon_path' => 'Favicon Yolu',

    // === UI Labels ===
    'select_language' => 'Dil Seçin',
    'site_name' => 'Site Adı',
    'site_description' => 'Site Açıklaması',
    
    // === File Upload & Management ===
    'current_logo' => 'Mevcut logo:',
    'logo_not_uploaded' => 'Logo yüklenmemiş.',
    'logo_help_text' => 'Seffaf arka planli bir PNG yukleyin. Minimum 512x512 px, onerilen 1024x1024 px veya ustu. Sistem favicon, ikon ve manifest dosyalarini otomatik olusturur.',
    'favicon_not_uploaded' => 'Favicon yüklenmemiş.',
    'favicon_help_text' => 'ICO veya PNG formatinda. 32x32 boyutuna yeniden boyutlandirilacaktir.',
    'not_uploaded' => 'Yüklenmemiş.',
    'png_96_help' => 'PNG formatında. 96x96 boyutuna yeniden boyutlandırılacaktır.',
    'png_180_help' => 'PNG formatında. 180x180 boyutuna yeniden boyutlandırılacaktır.',
    'view_file' => 'Dosyayı Görüntüle',
    'webmanifest_help' => '.webmanifest veya .json formatında.',
    'png_192_help' => 'PNG formatında. 192x192 boyutuna yeniden boyutlandırılacaktır.',
    'png_512_help' => 'PNG formatında. 512x512 boyutuna yeniden boyutlandırılacaktır.',
    'delete' => 'Sil',
    'confirm_delete' => 'Bu öğeyi silmek istediğinizden emin misiniz?',
    'delete_error' => 'Silinemedi:',
    'delete_success' => 'Silindi',
    'generic_error' => 'Bir hata oluştu.',
    'invalid_extension' => 'Geçersiz dosya uzantısı. İzin verilenler: %s',
    'manifest_icon_192' => 'Manifest İkonu 192x192',
    'manifest_icon_512' => 'Manifest İkonu 512x512',
    'favicon_96' => 'Favicon 96x96',
    'apple_touch_icon' => 'Apple Touch İkonu',
    'web_manifest' => 'Web Manifest',
    'choose_file' => 'Dosya Seç',
    'no_file_selected' => 'Dosya Seçilmedi',
    'save_error' => 'Ayarlar kaydedilirken bir hata oluştu',
    'loading_message' => 'Yükleniyor...',
    'missing_language' => 'Dil bilgisi eksik.',
    'invalid_params' => 'Eksik veya geçersiz parametreler.',
    'settings_not_found' => 'Belirtilen dil için ayar bulunamadı.',
    'update_error' => 'Veritabanı kaydı güncellenemedi.',
    'delete_fail' => 'Dosya silinemedi.',
    'delete_unknown_error' => 'Bilinmeyen dosya silme hatası.',
    'unsaved_changes' => 'Kaydedilmemis degisiklikler var. Dili degistirirseniz bu degisiklikler kaybolacak.',
    'asset_generation' => [
        'minimum_size' => 'Ana logo en az 512x512 piksel olmalidir.',
        'requires_imagick' => 'Otomatik varlik uretimi icin Imagick gereklidir.',
        'output_not_writable' => 'Varlik cikti klasoru yazilabilir degil.',
        'missing_upload' => 'Once bir PNG logo secin.',
    ],
    'settings_required_fields' => 'Platform ve URL gereklidir.',
    'no_records_found' => 'Kayıt bulunamadı.',
    'no_url_configured' => 'URL yapılandırılmadı',
    'header_style_title' => 'Header Görünüm Şekli',
    'header_style_desc' => 'Logonun ve site adının header alanında nasıl görüneceğini seçin.',
    'header_style_square' => 'Sadece Kare Logo',
    'header_style_rect' => 'Sadece Yatay Logo',
    'header_style_square_name' => 'Kare Logo ve Site Adı',
    'header_style_rect_name' => 'Yatay Logo ve Site Adı',
    'logo_rect_title' => 'Yatay Logo (3:1)',
    'logo_square_title' => 'Kare Logo (1:1)',
    'logo_rect_help_text' => 'Sitede kullanılacak 3:1 oranındaki yatay logo.',
    'logo_square_help_text' => '1:1 oranındaki kare logo. Favicon ve ikonların üretimi için kullanılır.',
    'crop_square' => 'Kare Logoyu Kırp (1:1)',
    'crop_rect' => 'Yatay Logoyu Kırp (3:1)',
    'crop_symbol' => 'Sembolü Kırp (1:1)',
    'logo_symbol_title' => 'Favicon (1:1)',
    'logo_symbol_help_text' => 'Sadece ikon veya amblem içeren 1:1 oranındaki sembol. Favicon ve ikonların üretimi için öncelikli kullanılır.',
];
