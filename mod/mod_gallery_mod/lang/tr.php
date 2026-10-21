<?php
/**
 * Gallery Module Integration - Turkish Language File
 *
 * @package AKM Bulletin CMS
 * @module  mod_gallery_mod
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Gallery Module Integration Language Translations
 */
$lang['mod_gallery_mod'] = [

    // === Basic Navigation ===
    'list' => 'Galeri Modülü Listesi',
    'add' => 'Galeri Modülü Ekle',
    'edit' => 'Galeri Modülünü Düzenle',
    'information' => 'Bilgi!',
    'success' => 'İşlem Başarılı!',
    'error' => 'Hata Oluştu!',
    'remove_record' => 'Bu Kaydı Silmek İstediğinize Emin misiniz?',

    // === Message Structure ===
    'message' => [
        'success' => [
            'add' => 'Galeri Modülü Başarıyla Eklendi!',
            'edit' => 'Galeri Modülü Başarıyla Güncellendi!',
        ],
        'error' => [
            'add' => 'Galeri Modülü Eklenemedi!',
            'edit' => 'Galeri Modülü Güncellenemedi!',
        ],
    ],

    'errors' => [
        'record' => 'Belirtilen Kayıt Bulunamadı!',
    ],

    'button' => [
        'add' => 'Ekle',
        'edit' => 'Düzenle',
        'refresh' => 'Yenile',
        'delete' => 'Sil',
        'cancel' => 'Vazgeç',
        'remove' => 'Kaldır',
        'yes' => 'Evet',
        'no' => 'Hayır',
        'gallery' => 'Galeri',
        'file' => 'Dosyalar',
    ],

    'grid' => [
        'showing_results' => 'Toplam {total} Sonuçtan {from} den {to}\'a Kadar Olanlar Görüntüleniyor.',
        'pagination' => '{total} sonuçtan {from} ile {to} arası gösteriliyor',
        'page' => 'Sayfa',
        'page_count' => 'Sayfa Sayısı',
        'loading' => 'Veriler Yükleniyor, Lütfen Bekleyin...',
        'no_data' => 'Hiçbir Veri Bulunamadı',
        'connection_error' => 'Bağlantı Hatası',
        'search' => 'Arama',
        'select_at_least_one' => 'Lütfen En Az Bir Satır Seçin',
        'select_only_one' => 'Lütfen Sadece Bir Satır Seçin',
    ],

    'validator' => [
        'required' => 'Lütfen %s Alanını Boş Bırakmayın',
        'length' => '%s En Az %s En Çok %s Karakterden Oluşabilir',
        'email' => '%s Alanına Lütfen Geçerli Bir E-Posta Adresi Yazın.',
        'unique' => 'Girdiğiniz %s Sistemde Kayıtlı. Lütfen Başka Bir %s Belirleyin!',
        'inarray' => '%s Alanı Sadece \' %s \' Değerlerini Kabul Eder',
    ],

    'role' => [
        'name' => 'Galeri Modülü Yönetim Rolleri',
        'add' => 'Galeri Modülü Ekleyebilir',
        'edit' => 'Galeri Modülünü Düzenleyebilir',
        'delete' => 'Galeri Modülünü Silebilir',
        'list' => 'Galeri Modülü Yönetimini Görüntüleyebilir',
        'moderate_edit' => 'Kendisine Ait Olmayan Galeri Modülü Kaydını Düzenleyebilir',
        'moderate_delete' => 'Kendisine Ait Olmayan Galeri Modülü Kaydını Silebilir',
    ],

    'role_errors' => [
        'moderate_edit' => 'Size Ait Olmayan Galeri Modülü Kaydını Düzenleyemezsiniz',
        'moderate_delete' => 'Size Ait Olmayan Galeri Modülü Kaydını Silemezsiniz',
        'add' => 'Galeri Modülü Eklemeye Yetkiniz Yok',
        'edit' => 'Galeri Modülünü Düzenlemeye Yetkiniz Yok',
        'list' => 'Galeri Modülü Yönetimini Görüntülemeye Yetkiniz Yok',
        'delete' => 'Galeri Modülünü Silmeye Yetkiniz Yok',
    ],

    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'Galeri Modülü Adı',
            'mod_name' => 'Modül Adı',
            'mod_key' => 'Modül Anahtarı (ID)',
            'mod_value' => 'Modül Değeri (Ad)',
            'gallery_type' => 'Galeri Türü',
            'display_mode' => 'Görüntü Modu',
            'status' => 'Durum',
            'created_date' => 'Oluşturulma Tarihi',
            'modified_date' => 'Değiştirilme Tarihi',
            'actions' => 'İşlemler',
        ],
    ],

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Galeri Modülü Ekle',
        'edit' => 'Galeri Modülünü Düzenle',
        'list' => 'Galeri Modülü Listesi',
        'delete' => 'Galeri Modülünü Sil',
        'cancel' => 'İptal',
        'save' => 'Kaydet',
        'update' => 'Güncelle',
        'remove' => 'Kaldır',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Galeri Modülü Başarıyla Eklendi!',
            'updated' => 'Galeri Modülü Başarıyla Güncellendi!',
            'deleted' => 'Galeri Modülü Başarıyla Silindi!',
            'configured' => 'Galeri Modülü Başarıyla Yapılandırıldı!',
        ],
        'error' => [
            'not_added' => 'Galeri Modülü Eklenemedi!',
            'not_updated' => 'Galeri Modülü Güncellenemedi!',
            'not_deleted' => 'Galeri Modülü Silinemedi!',
            'not_found' => 'Belirtilen Galeri Modülü Kaydı Bulunamadı!',
            'configuration_failed' => 'Galeri Modülü Yapılandırması Başarısız!',
        ],
        'info' => [
            'module_intro' => 'Galeri modülleri diğer sistem bileşenlerine resim galerisi yetenekleri sağlar',
            'configuration_info' => 'Resim işlemlerini belirli modüllerde etkinleştirmek için galeri modülü ayarlarını yapılandırın',
        ],
    ],

    // === Gallery Module Configuration ===
    'configuration' => [
        'basic_settings' => 'Temel Ayarlar',
        'display_settings' => 'Görüntü Ayarları',
        'advanced_settings' => 'Gelişmiş Ayarlar',
        'integration_settings' => 'Entegrasyon Ayarları',

        'module_name' => 'Modül Adı',
        'module_description' => 'Modül Açıklaması',
        'gallery_type' => 'Galeri Türü',
        'display_mode' => 'Görüntü Modu',
        'image_size' => 'Resim Boyutu',
        'thumbnail_size' => 'Küçük Resim Boyutu',
        'columns_per_row' => 'Satır Başına Sütun',
        'auto_slideshow' => 'Otomatik Slayt Gösterisi',
        'slideshow_interval' => 'Slayt Gösterisi Aralığı (saniye)',
        'show_captions' => 'Başlıkları Göster',
        'enable_lightbox' => 'Lightbox\'ı Etkinleştir',
        'enable_zoom' => 'Yakınlaştırmayı Etkinleştir',
        'enable_download' => 'İndirmeyi Etkinleştir',
    ],

    // === Gallery Types ===
    'gallery_types' => [
        'image_gallery' => 'Resim Galerisi',
        'photo_album' => 'Fotoğraf Albümü',
        'portfolio' => 'Portfolyo',
        'showcase' => 'Vitrin',
        'slider_gallery' => 'Slayt Galerisi',
        'masonry' => 'Mozaik Düzen',
        'grid' => 'Izgara Düzen',
    ],

    // === Display Modes ===
    'display_modes' => [
        'grid' => 'Izgara',
        'list' => 'Liste',
        'masonry' => 'Mozaik',
        'carousel' => 'Karusel',
        'slider' => 'Slayt',
        'lightbox' => 'Lightbox',
        'modal' => 'Modal',
        'inline' => 'Satır İçi',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'remove_record' => 'Bu Kaydı Silmek İstediğinize Emin misiniz?',
        'delete_single' => 'Bu Galeri Modülünü Silmek İstediğinize Emin misiniz?',
        'delete_multiple' => 'Seçilen :count Galeri Modülünü Silmek İstediğinize Emin misiniz?',
        'reset_configuration' => 'Modül Yapılandırmasını Sıfırlamak İstediğinize Emin misiniz?',
        'clear_gallery' => 'Bu Galeriden Tüm Resimleri Temizlemek İstediğinize Emin misiniz?',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Ekle',
        'edit' => 'Düzenle',
        'refresh' => 'Yenile',
        'delete' => 'Sil',
        'cancel' => 'İptal',
        'remove' => 'Kaldır',
        'yes' => 'Evet',
        'no' => 'Hayır',
        'configure' => 'Yapılandır',
        'preview' => 'Önizleme',
        'test' => 'Test',
        'reset' => 'Sıfırla',
    ],

    // === Data Grid Messages ===
    'grid_messages' => [
        'pagination' => '{total} sonuçtan {from} ile {to} arası gösteriliyor',
        'page' => 'Sayfa',
        'page_count' => 'Sayfa Sayısı',
        'loading' => 'Veriler yükleniyor, lütfen bekleyin...',
        'no_data' => 'Veri bulunamadı',
        'connection_error' => 'Bağlantı Hatası',
        'search' => 'Ara',
        'select_at_least_one' => 'Lütfen en az bir satır seçin',
        'select_only_one' => 'Lütfen sadece bir satır seçin',
    ],

    // === Validation Messages ===
    'validation' => [
        'required' => 'Lütfen %s alanını boş bırakmayın',
        'length' => '%s %s ile %s karakter arasında olmalıdır',
        'email' => 'Lütfen %s alanına geçerli bir e-posta adresi girin',
        'unique' => 'Girdiğiniz %s sistemde kayıtlı. Lütfen farklı bir %s belirtin!',
        'inarray' => '%s alanı sadece \' %s \' değerlerini kabul eder',
        'module_name_required' => 'Modül adı gereklidir',
        'gallery_type_required' => 'Galeri türü seçimi gereklidir',
        'invalid_image_size' => 'Geçersiz resim boyutu belirtildi',
        'configuration_invalid' => 'Modül yapılandırması geçersiz',
    ],

    // === Access Control (ACL) ===
    'permissions' => [
        'title' => 'Galeri Modülü Yönetim İzinleri',
        'add' => 'Galeri Modülü Ekleyebilir',
        'edit' => 'Galeri Modülünü Düzenleyebilir',
        'delete' => 'Galeri Modülünü Silebilir',
        'list' => 'Galeri Modülü Yönetimini Görüntüleyebilir',
        'configure' => 'Galeri Modüllerini Yapılandırabilir',
        'preview' => 'Galeri Modüllerini Önizleyebilir',
        'test' => 'Galeri Modüllerini Test Edebilir',
        'edit_others' => 'Başkasına ait Galeri Modülü kayıtlarını düzenleyebilir',
        'delete_others' => 'Başkasına ait Galeri Modülü kayıtlarını silebilir',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'edit_others' => 'Başkasına ait Galeri Modülü kayıtlarını düzenleyemezsiniz',
        'delete_others' => 'Başkasına ait Galeri Modülü kayıtlarını silemezsiniz',
        'add' => 'Galeri Modülleri ekleme izniniz yok',
        'edit' => 'Galeri Modülleri düzenleme izniniz yok',
        'list' => 'Galeri Modülü Yönetimini görüntüleme izniniz yok',
        'delete' => 'Galeri Modülleri silme izniniz yok',
        'configure' => 'Galeri Modüllerini yapılandırma izniniz yok',
        'preview' => 'Galeri Modüllerini önizleme izniniz yok',
        'test' => 'Galeri Modüllerini test etme izniniz yok',
    ],

    // === Admin Panel Configuration ===
    'admin_columns' => [
        'id' => 'ID',
        'name' => 'Galeri Modülü Adı',
        'mod_name' => 'Modül Adı',
        'mod_key' => 'Modül Anahtarı (ID)',
        'mod_value' => 'Modül Değeri (Ad)',
        'gallery_type' => 'Galeri Türü',
        'display_mode' => 'Görüntü Modu',
        'status' => 'Durum',
        'created_date' => 'Oluşturulma Tarihi',
        'modified_date' => 'Değiştirilme Tarihi',
        'actions' => 'İşlemler',
    ],

    // === Image Management ===
    'image_management' => [
        'upload_images' => 'Resim Yükle',
        'manage_images' => 'Resimleri Yönet',
        'bulk_upload' => 'Toplu Yükleme',
        'drag_drop' => 'Resimleri Sürükle & Bırak',
        'reorder_images' => 'Resimleri Yeniden Sırala',
        'crop_images' => 'Resimleri Kırp',
        'resize_images' => 'Resimleri Yeniden Boyutlandır',
        'optimize_images' => 'Resimleri Optimize Et',
        'image_quality' => 'Resim Kalitesi',
        'compression_level' => 'Sıkıştırma Seviyesi',
        'auto_optimize' => 'Otomatik Optimize Et',
    ],

    // === Gallery Features ===
    'features' => [
        'lightbox_enabled' => 'Lightbox Etkin',
        'zoom_enabled' => 'Yakınlaştırma Etkin',
        'fullscreen_enabled' => 'Tam Ekran Etkin',
        'thumbnails_enabled' => 'Küçük Resimler Etkin',
        'captions_enabled' => 'Başlıklar Etkin',
        'navigation_enabled' => 'Navigasyon Etkin',
        'keyboard_navigation' => 'Klavye Navigasyonu',
        'touch_gestures' => 'Dokunma Hareketleri',
        'lazy_loading' => 'Tembel Yükleme',
        'infinite_scroll' => 'Sonsuz Kaydırma',
    ],

    // === Display Settings ===
    'display_settings' => [
        'gallery_width' => 'Galeri Genişliği',
        'gallery_height' => 'Galeri Yüksekliği',
        'image_spacing' => 'Resim Aralığı',
        'border_radius' => 'Kenar Yarıçapı',
        'shadow_effect' => 'Gölge Efekti',
        'hover_effect' => 'Üzerine Gelme Efekti',
        'transition_effect' => 'Geçiş Efekti',
        'animation_speed' => 'Animasyon Hızı',
        'background_color' => 'Arka Plan Rengi',
        'border_color' => 'Kenar Rengi',
    ],

    // === Success Messages ===
    'success_messages' => [
        'operation_completed' => 'İşlem başarıyla tamamlandı!',
        'changes_saved' => 'Değişiklikler başarıyla kaydedildi!',
        'images_uploaded' => ':count resim başarıyla yüklendi!',
        'gallery_configured' => 'Galeri başarıyla yapılandırıldı!',
        'test_passed' => 'Galeri testi başarıyla geçildi!',
        'preview_ready' => 'Galeri önizlemesi hazır!',
    ],

    // === Error Messages ===
    'error_messages' => [
        'general' => 'Bir hata oluştu. Lütfen tekrar deneyin.',
        'network' => 'Ağ hatası. Lütfen internet bağlantınızı kontrol edin.',
        'server' => 'Sunucu hatası. Lütfen daha sonra tekrar deneyin.',
        'upload_failed' => 'Resim yüklenemedi',
        'configuration_error' => 'Yapılandırma hatası oluştu',
        'test_failed' => 'Galeri testi başarısız',
        'module_not_found' => 'Galeri Modülü bulunamadı',
        'invalid_configuration' => 'Geçersiz yapılandırma',
    ],

    // === Helper Text ===
    'help' => [
        'module_intro' => 'Galeri modülleri diğer sistem bileşenlerine resim galerisi yetenekleri sağlar',
        'configuration_help' => 'Uygun resim galerisi entegrasyonu için modül ayarlarını yapılandırın',
        'upload_help' => 'Galerinizi oluşturmak için resimler yükleyin',
        'display_help' => 'Galerinizin kullanıcılara nasıl görüneceğini özelleştirin',
        'feature_help' => 'Kullanıcı deneyimini geliştirmek için gelişmiş özellikleri etkinleştirin',
    ],

    // === Empty States ===
    'empty_states' => [
        'no_modules' => 'Henüz yapılandırılmış Galeri Modülü yok',
        'no_results' => 'Arama kriterlerinize uyan modül bulunamadı',
        'no_images' => 'Bu galeride henüz resim yok',
        'add_first_module' => 'Başlamak için ilk Galeri Modülünüzü ekleyin',
        'upload_first_image' => 'Galeri oluşturmak için ilk resminizi yükleyin',
    ],

    // === Loading States ===
    'loading_states' => [
        'loading_modules' => 'Modüller yükleniyor...',
        'uploading_images' => 'Resimler yükleniyor...',
        'processing_images' => 'Resimler işleniyor...',
        'configuring' => 'Yapılandırılıyor...',
        'previewing' => 'Önizleme oluşturuluyor...',
        'testing' => 'Galeri test ediliyor...',
    ],

];
