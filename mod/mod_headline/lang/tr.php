<?php
/**
 * Featured Content/Headline Management - Turkish Language File
 *
 * @package AKM Bulletin CMS
 * @module  mod_headline
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Featured Content/Headline Management Language Translations
 */
$lang['mod_headline'] = [
    'add' => 'Tam Manşet Ekle',
    'edit' => 'Tam Manşeti Düzenle',
    'list' => 'Tam Manşet Listesi',
    'information' => 'Bilgi!',
    'success' => 'İşlem Başarılı!',
    'error' => 'Hata Oluştu!',
    'remove_image' => 'Bu Resmi Kaldırmak İstediğinize Emin misiniz?',
    'remove_image_success' => 'Resim Kaldırıldı',
    'choose_wish' => 'Dilerseniz Seçin',
    'remove_record' => 'Bu Kaydı Silmek İstediğinize Emin misiniz?',

    'message' => [
        'success' => [
            'add' => 'Tam Manşet Eklendi!',
            'edit' => 'Tam Manşet Düzenlendi!',
        ],
        'error' => [
            'add' => 'Tam Manşet Eklenemedi!',
            'edit' => 'Tam Manşet Düzenlenemedi!',
        ],
        'information' => [
            'moderate' => 'Düzenlemek İstediğiniz İçeriği İki Defa Tıklayın',
            'move' => 'Sırasını Değiştirmek İstediğiniz İçeriği Sürükleyip Bırakın',
        ],
    ],

    'errors' => [
        'record' => 'Belirttiğiniz Kayıt Bulunamadı!',
    ],

    'button' => [
        'add' => 'Ekle',
        'edit' => 'Düzenle',
        'refresh' => 'Yenile',
        'delete' => 'Sil',
        'gallery' => 'Resim Galerisi',
        'cancel' => 'Vazgeç',
        'remove' => 'Kaldır',
        'yes' => 'Evet',
        'no' => 'Hayır',
    ],

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Öne Çıkan İçerik Ekle',
        'edit' => 'Öne Çıkan İçeriği Düzenle',
        'list' => 'Öne Çıkan İçerik Listesi',
        'delete' => 'Öne Çıkan İçeriği Sil',
        'cancel' => 'İptal',
        'save' => 'Kaydet',
        'update' => 'Güncelle',
        'remove' => 'Kaldır',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Öne Çıkan İçerik başarıyla eklendi!',
            'updated' => 'Öne Çıkan İçerik başarıyla güncellendi!',
            'deleted' => 'Öne Çıkan İçerik başarıyla silindi!',
            'published' => 'Öne Çıkan İçerik başarıyla yayınlandı!',
            'unpublished' => 'Öne Çıkan İçerik yayından kaldırıldı!',
            'image_removed' => 'Görsel başarıyla kaldırıldı!',
            'reordered' => 'İçerik sıralaması başarıyla güncellendi!',
        ],
        'error' => [
            'not_added' => 'Öne Çıkan İçerik eklenemedi!',
            'not_updated' => 'Öne Çıkan İçerik güncellenemedi!',
            'not_deleted' => 'Öne Çıkan İçerik silinemedi!',
            'not_found' => 'Belirtilen Öne Çıkan İçerik kaydı bulunamadı!',
            'image_upload_failed' => 'Görsel yüklenemedi!',
            'invalid_image_format' => 'Geçersiz görsel formatı!',
            'file_too_large' => 'Dosya boyutu maksimum sınırı aşıyor!',
        ],
        'info' => [
            'edit_double_click' => 'Düzenlemek istediğiniz içeriğe çift tıklayın',
            'drag_reorder' => 'Sırasını değiştirmek için içeriği sürükleyip bırakın',
            'image_requirements' => 'Öne çıkan görsel yüksek kaliteli ve uygun boyutta olmalıdır',
        ],
    ],

    // === Content Types ===
    'content_types' => [
        'headline' => 'Manşet',
        'featured_article' => 'Öne Çıkan Makale',
        'hero_banner' => 'Hero Banner',
        'spotlight' => 'Gündem',
        'highlight' => 'Vurgu',
        'showcase' => 'Vitrin',
        'breaking_news' => 'Son Dakika',
        'featured_story' => 'Öne Çıkan Haber',
    ],

    // === Status Types ===
    'status' => [
        'published' => 'Yayınlandı',
        'draft' => 'Taslak',
        'scheduled' => 'Zamanlandı',
        'archived' => 'Arşivlendi',
        'featured' => 'Öne Çıkan',
        'pinned' => 'Sabitlendi',
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Evet',
        'no' => 'Hayır',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'remove_image' => 'Bu görseli kaldırmak istediğinizden emin misiniz?',
        'remove_record' => 'Bu kaydı silmek istediğinizden emin misiniz?',
        'delete_single' => 'Bu Öne Çıkan İçeriği silmek istediğinizden emin misiniz?',
        'delete_multiple' => 'Seçilen :count Öne Çıkan İçerik öğesini silmek istediğinizden emin misiniz?',
        'publish' => 'Bu Öne Çıkan İçeriği yayınlamak istediğinizden emin misiniz?',
        'unpublish' => 'Bu Öne Çıkan İçeriği yayından kaldırmak istediğinizden emin misiniz?',
        'feature' => 'Bu içeriği öne çıkarmak istediğinizden emin misiniz?',
        'unfeature' => 'Bu içeriği öne çıkanlardan kaldırmak istediğinizden emin misiniz?',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Ekle',
        'edit' => 'Düzenle',
        'refresh' => 'Yenile',
        'delete' => 'Sil',
        'gallery' => 'Resim Galerisi',
        'cancel' => 'İptal',
        'remove' => 'Kaldır',
        'yes' => 'Evet',
        'no' => 'Hayır',
        'preview' => 'Önizleme',
        'feature' => 'Öne Çıkar',
        'pin' => 'Sabitle',
        'unpin' => 'Sabitlemeyi Kaldır',
    ],

    // === Data Grid Messages ===
    'grid' => [
        'showing_results' => 'Toplam {total} Sonuçtan {from} den {to}\\\'a Kadar Olanlar Görüntüleniyor.',
        'pagination' => '{total} sonuçtan {from} ile {to} arası gösteriliyor',
        'page' => 'Sayfa',
        'page_count' => 'Sayfa Sayısı',
        'loading' => 'Veriler Yükleniyor, Lütfen Bekleyin ...',
        'no_data' => 'Hiçbir Veri Bulunamadı',
        'connection_error' => 'Bağlantı Hatası',
        'search' => 'Arama',
        'select_at_least_one' => 'Lütfen En Az Bir Satır Seçin',
        'select_only_one' => 'Lütfen Sadece Bir Satır Seçin',
        'publish' => [
            'key' => 'Yayınla',
            'value' => 'Bu Tam Manşeti Yayınlamak İstediğinize Emin misiniz?',
        ],
        'unpublish' => [
            'key' => 'Yayından Al',
            'value' => 'Bu Tam Manşeti Yayından Kaldırmak İstediğinize Emin misiniz?',
        ],
    ],

    'validator' => [
        'required' => 'Lütfen %s Alanını Boş Bırakmayın',
        'length' => '%s En Kısa %s En Uzun %s Karakterden Oluşabilir',
        'email' => '%s Alanına Lütfen Geçerli Bir E-Posta Adresi Yazın.',
        'unique' => 'Yazmış Olduğunuz %s Sistemde Kayıtlı. Lütfen Başka Bir %s Belirleyin!',
        'inarray' => '%s Alanı Sadece \' %s \' değerleri alabilir',
    ],

    // === Validation Messages ===
    'validation' => [
        'required' => 'Lütfen %s alanını boş bırakmayın',
        'length' => '%s %s ile %s karakter arasında olmalıdır',
        'email' => 'Lütfen %s alanına geçerli bir e-posta adresi girin',
        'unique' => 'Girdiğiniz %s sistemde kayıtlı. Lütfen farklı bir %s belirtin!',
        'inarray' => '%s alanı sadece \' %s \' değerlerini kabul eder',
        'title_required' => 'İçerik başlığı gereklidir',
        'image_required' => 'Öne çıkan görsel gereklidir',
        'invalid_image_size' => 'Görsel boyutu 2000x750 piksel olmalıdır',
        'invalid_file_type' => 'Geçersiz dosya türü. Sadece JPG, PNG, GIF kabul edilir',
    ],

    // === Access Control (ACL) ===
    'role' => [
        'name' => 'Tam Manşet Yönetim Rolleri',
        'add' => 'Tam Manşet Ekleyebilir',
        'edit' => 'Tam Manşet Düzenleyebilir',
        'delete' => 'Tam Manşet Silebilir',
        'list' => 'Tam Manşet Yönetimini Görüntüleyebilir',
        'publish' => 'Tam Manşet Yayınlayabilir',
        'feature' => 'İçeriği Öne Çıkarabilir',
        'moderate_edit' => 'Kendisine Ait Olmayan Tam Manşet Kaydını Düzenleyebilir',
        'moderate_delete' => 'Kendisine Ait Olmayan Tam Manşet Kaydını Silebilir',
    ],

    'role_errors' => [
        'moderate_edit' => 'Size Ait Olmayan Tam Manşet Kaydını Düzenleyemezsiniz',
        'moderate_delete' => 'Size Ait Olmayan Tam Manşet Kaydını Silemezsiniz',
        'add' => 'Tam Manşet Eklemeye Yetkiniz Yok',
        'edit' => 'Tam Manşet Düzenlemeye Yetkiniz Yok',
        'list' => 'Tam Manşet Yönetimini Görüntülemeye Yetkiniz Yok',
        'delete' => 'Tam Manşet Silmeye Yetkiniz Yok',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'edit_others' => 'Başkasına ait Öne Çıkan İçerik kayıtlarını düzenleyemezsiniz',
        'delete_others' => 'Başkasına ait Öne Çıkan İçerik kayıtlarını silemezsiniz',
        'add' => 'Öne Çıkan İçerik ekleme yetkiniz yok',
        'edit' => 'Öne Çıkan İçerik düzenleme yetkiniz yok',
        'list' => 'Öne Çıkan İçerik Yönetimini görüntüleme yetkiniz yok',
        'delete' => 'Öne Çıkan İçerik silme yetkiniz yok',
        'publish' => 'Öne Çıkan İçerik yayınlama yetkiniz yok',
        'feature' => 'İçeriği öne çıkarma yetkiniz yok',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Dil',
            'name' => 'Tam Manşet Adı',
            'status' => 'Yayınlansın mı?',
            'published' => 'Yayınlansın mı?',
            'date' => 'Tarih',
            'created_at' => 'Tarih',
            'image' => 'Resim 2000x750 formatında olmalı',
        ],
        'radio' => [
            'publish_yes' => 'Evet',
            'publish_no' => 'Hayır',
            'published_yes' => 'Evet',
            'published_no' => 'Hayır',
        ],
    ],

    // === Content Settings ===
    'content_settings' => [
        'basic_info' => 'Temel Bilgiler',
        'content_details' => 'İçerik Detayları',
        'media_settings' => 'Medya Ayarları',
        'publishing_settings' => 'Yayınlama Ayarları',
        'seo_settings' => 'SEO Ayarları',
        'advanced_settings' => 'Gelişmiş Ayarlar',

        'title' => 'Başlık',
        'subtitle' => 'Alt Başlık',
        'slug' => 'URL Kısa Adı (Slug)',
        'content' => 'İçerik',
        'excerpt' => 'Alıntı',
        'summary' => 'Özet',
        'featured_image' => 'Öne Çıkan Görsel',
        'thumbnail' => 'Küçük Görsel',
        'author' => 'Yazar',
        'publish_date' => 'Yayın Tarihi',
        'expiry_date' => 'Bitiş Tarihi',
    ],

    // === Image Management ===
    'image_management' => [
        'upload_image' => 'Görsel Yükle',
        'select_image' => 'Görsel Seç',
        'crop_image' => 'Görseli Kırp',
        'resize_image' => 'Görseli Yeniden Boyutlandır',
        'optimize_image' => 'Görseli Optimize Et',
        'image_requirements' => 'Görsel Gereksinimleri',
        'recommended_size' => 'Önerilen Boyut: 2000x750 piksel',
        'max_file_size' => 'Maksimum Dosya Boyutu: 5MB',
        'supported_formats' => 'Desteklenen Formatlar: JPG, PNG, GIF',
        'alt_text' => 'Alternatif Metin (Alt Text)',
        'image_caption' => 'Görsel Alt Yazısı',
        'image_credit' => 'Görsel Kaynağı',
    ],

    // === Display Options ===
    'display_options' => [
        'layout_options' => 'Düzen Seçenekleri',
        'position_options' => 'Konum Seçenekleri',
        'size_options' => 'Boyut Seçenekleri',
        'animation_options' => 'Animasyon Seçenekleri',
        'color_scheme' => 'Renk Şeması',
        'typography' => 'Tipografi',
        'spacing' => 'Boşluklandırma',
        'borders' => 'Kenarlıklar',
        'shadows' => 'Gölgeler',
        'background' => 'Arka Plan',
    ],

    // === Layout Types ===
    'layout_types' => [
        'full_width' => 'Tam Genişlik',
        'half_width' => 'Yarım Genişlik',
        'grid_layout' => 'Izgara Düzeni',
        'masonry_layout' => 'Mozaik Düzen',
        'carousel_layout' => 'Karusel Düzeni',
        'stacked_layout' => 'Yığın Düzeni',
        'overlay_layout' => 'Kaplama Düzeni',
        'sidebar_layout' => 'Kenar Çubuğu Düzeni',
    ],

    // === Position Options ===
    'position_options' => [
        'top' => 'Üst',
        'middle' => 'Orta',
        'bottom' => 'Alt',
        'left' => 'Sol',
        'right' => 'Sağ',
        'center' => 'Merkez',
        'full_screen' => 'Tam Ekran',
        'above_content' => 'İçeriğin Üstünde',
        'below_content' => 'İçeriğin Altında',
    ],

    // === SEO Features ===
    'seo_features' => [
        'meta_title' => 'Meta Başlığı',
        'meta_description' => 'Meta Açıklaması',
        'meta_keywords' => 'Meta Anahtar Kelimeler',
        'og_title' => 'Open Graph Başlığı',
        'og_description' => 'Open Graph Açıklaması',
        'og_image' => 'Open Graph Görseli',
        'twitter_card' => 'Twitter Kartı',
        'canonical_url' => 'Kanonik URL',
        'schema_markup' => 'Şema İşaretlemesi',
        'structured_data' => 'Yapılandırılmış Veri',
    ],

    // === Publishing Workflow ===
    'publishing' => [
        'save_draft' => 'Taslak Olarak Kaydet',
        'schedule_publish' => 'Yayını Zamanla',
        'publish_now' => 'Şimdi Yayınla',
        'publish_later' => 'Daha Sonra Yayınla',
        'expiry_options' => 'Süre Sonu Seçenekleri',
        'never_expires' => 'Hiçbir Zaman Sona Ermez',
        'expires_on' => 'Sona Erme Tarihi',
        'revision_history' => 'Revizyon Geçmişi',
        'preview_mode' => 'Önizleme Modu',
    ],

    // === Content Categories ===
    'categories' => [
        'select_category' => 'Kategori Seçin',
        'all_categories' => 'Tüm Kategoriler',
        'uncategorized' => 'Kategorisiz',
        'news' => 'Haberler',
        'blog' => 'Blog',
        'portfolio' => 'Portfolyo',
        'services' => 'Hizmetler',
        'events' => 'Etkinlikler',
        'announcements' => 'Duyurular',
    ],

    // === Success Messages ===
    'success_messages' => [
        'operation_completed' => 'İşlem başarıyla tamamlandı!',
        'changes_saved' => 'Değişiklikler başarıyla kaydedildi!',
        'content_published' => 'İçerik başarıyla yayınlandı!',
        'content_unpublished' => 'İçerik yayından kaldırıldı!',
        'content_featured' => 'İçerik başarıyla öne çıkarıldı!',
        'image_uploaded' => 'Görsel başarıyla yüklendi!',
        'content_reordered' => 'İçerik başarıyla yeniden sıralandı!',
    ],

    // === Error Messages ===
    'error_messages' => [
        'general' => 'Bir hata oluştu. Lütfen tekrar deneyin.',
        'network' => 'Ağ hatası. Lütfen internet bağlantınızı kontrol edin.',
        'server' => 'Sunucu hatası. Lütfen daha sonra tekrar deneyin.',
        'upload_failed' => 'Dosya yüklenemedi',
        'processing_failed' => 'Görsel işleme başarısız oldu',
        'validation_failed' => 'Doğrulama başarısız oldu',
        'permission_denied' => 'İzin reddedildi',
        'file_corrupted' => 'Dosya bozuk görünüyor',
        'size_limit_exceeded' => 'Dosya boyutu maksimum sınırı aşıyor',
    ],

    // === Helper Text ===
    'help' => [
        'content_title' => 'Öne çıkan içeriğiniz için etkileyici bir başlık girin',
        'content_subtitle' => 'Daha fazla bağlam sağlamak için bir alt başlık ekleyin',
        'featured_image' => 'İçeriğinizi temsil eden yüksek kaliteli bir görsel yükleyin',
        'image_requirements' => 'En iyi sonuçlar için 2000x750 piksel boyutunda yüksek kaliteli görseller kullanın',
        'seo_optimization' => 'Daha iyi arama motoru görünürlüğü için içeriğinizi optimize edin',
        'publishing_options' => 'İçeriğinizin ne zaman ve nasıl yayınlanacağını seçin',
        'display_settings' => 'İçeriğinizin web sitesinde nasıl görüneceğini özelleştirin',
    ],

    // === Placeholders ===
    'placeholders' => [
        'content_title' => 'İçerik başlığını girin...',
        'content_subtitle' => 'Alt başlığı girin...',
        'content_excerpt' => 'Kısa açıklama girin...',
        'meta_title' => 'Meta başlığını girin...',
        'meta_description' => 'Meta açıklamasını girin...',
        'meta_keywords' => 'anahtar1, anahtar2, anahtar3',
        'alt_text' => 'Erişilebilirlik için görseli açıklayın...',
        'image_caption' => 'Görsel alt yazısını girin...',
        'search_content' => 'Öne çıkan içerik ara...',
    ],

    // === Empty States ===
    'empty_states' => [
        'no_content' => 'Henüz öne çıkan içerik oluşturulmadı',
        'no_results' => 'Arama kriterlerinize uyan içerik bulunamadı',
        'no_images' => 'Galeride görsel yok',
        'no_categories' => 'Kullanılabilir kategori yok',
        'add_first_content' => 'Başlamak için ilk öne çıkan içeriğinizi oluşturun',
        'upload_first_image' => 'İlk öne çıkan görselinizi yükleyin',
    ],

    // === Loading States ===
    'loading_states' => [
        'loading_content' => 'İçerik yükleniyor...',
        'saving_changes' => 'Değişiklikler kaydediliyor...',
        'uploading_image' => 'Görsel yükleniyor...',
        'processing_image' => 'Görsel işleniyor...',
        'publishing_content' => 'İçerik yayınlanıyor...',
        'deleting_content' => 'İçerik siliniyor...',
    ],

    // === Preview Features ===
    'preview' => [
        'preview_desktop' => 'Masaüstü Önizleme',
        'preview_mobile' => 'Mobil Önizleme',
        'preview_tablet' => 'Tablet Önizleme',
        'live_preview' => 'Canlı Önizleme',
        'preview_mode' => 'Önizleme Modu',
        'exit_preview' => 'Önizlemeden Çık',
        'responsive_test' => 'Duyarlılık Testi',
    ],

];