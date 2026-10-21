<?php
/**
 * News Management Module - Turkish Language File
 *
 * @package AKM Bulletin CMS
 * @module  mod_news
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * News Management Module Language Translations
 */
$lang['mod_news'] = [

    // === Basic Actions ===
    'add' => 'Haber Ekle',
    'edit' => 'Haberi Düzenle',
    'list' => 'Haber Listesi',
    'information' => 'Bilgi!',
    'success' => 'İşlem Başarılı!',
    'error_occurred' => 'Hata Oluştu!',
    'error' => 'Ölümcül Hata Oluştu!',
    'remove_image' => 'Bu Resmi Kaldırmak İstediğinize emin misiniz?',
    'remove_image_success' => 'Resim Kaldırıldı',
    'choose_wish' => 'Dilerseniz Seçin',
    'remove_record' => 'Bu İçeriği Silmek İstediğinize emin misiniz?',
    'delete_confirm' => 'Bu haberi silmek istediğinize emin misiniz?',
    'quick_unpublish_confirm' => 'Bu haberi yayından kaldırmak istediğinizden emin misiniz?',
    'quick_publish_confirm' => 'Bu haberi yayınlamak istediğinizden emin misiniz?',
    'wizard_loading' => 'Sihirbaz yükleniyor...',
    'add_image' => 'Resim Ekle',
    'upload_image' => 'Resim Yükle/Değiştir',
    'local' => 'Lokal',
    'embed' => 'Embed',

    // === Filter Menu ===
    'filter' => [
        'title' => 'Filtrele & Sırala',
        'close' => 'Kapat',
        'sort_label' => 'Sıralama',
        'newest' => 'En Yeni',
        'oldest' => 'En Eski',
        'title_az' => 'Başlık (A-Z)',
        'title_za' => 'Başlık (Z-A)',
        'view_count' => 'Okunma Sayısı',
        'limit_label' => 'Gösterim Adedi',
        'lang_label' => 'Dil Seçimi',
        'selected_lang' => 'Seçili Dil',
        'date_label' => 'Tarih Aralığı',
        'start_date' => 'Başlangıç',
        'end_date' => 'Bitiş',
        'clear' => 'Temizle',
        'apply' => 'Sonuçları Gör',
        'select_lang' => 'Dil Seç',
        'all_langs' => 'Tüm Diller',
        'select_label' => 'Seç',
    ],

    // === Messages ===
    'message' => [
        'success' => [
            'add' => 'Haber Eklendi!',
            'edit' => 'Haber Düzenlendi!',
        ],
        'error' => [
            'add' => 'Haber Eklenemedi!',
            'edit' => 'Haber Düzenlenemedi!',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'Belirttiğiniz Öğe Bulunamadı!',
    ],

    // === Buttons ===
    'button' => [
        'next' => 'İleri',
        'prev' => 'Geri',
        'send' => 'Gönder',
        'add' => 'Ekle',
        'edit' => 'Düzenle',
        'refresh' => 'Yenile',
        'delete' => 'Sil',
        'gallery' => 'Resim Galerisi',
        'file' => 'Dosya Yöneticisi',
        'cancel' => 'Vazgeç',
        'remove' => 'Kaldır',
        'ok' => 'Tamam',
        'yes' => 'Evet',
        'no' => 'Hayır',
        'seo_meta' => '(SEO, Meta)',
    ],

    // === Data Grid Messages ===
    'grid' => [
        'showing_results' => 'Toplam {total} sonuçtan {from} ile {to} arasındaki kayıtlar gösteriliyor.',
        'page' => 'Sayfa',
        'page_count' => 'Sayfa Sayısı',
        'loading' => 'Veriler yükleniyor, lütfen bekleyin...',
        'no_data' => 'Veri bulunamadı',
        'connection_error' => 'Bağlantı Hatası',
        'search' => 'Arama',
        'select_at_least_one' => 'Lütfen en az bir satır seçin',
        'select_only_one' => 'Lütfen sadece bir satır seçin',
        'loading' => 'Yükleniyor...',
        'no_data' => 'Haber bulunamadı',
        'approve' => [
            'key' => 'Yayınla',
            'value' => 'Bu haberi yayınlamak istediğinize emin misiniz?',
        ],
        'unapprove' => [
            'key' => 'Yayından Kaldır',
            'value' => 'Bu haberi yayından kaldırmak istediğinize emin misiniz?',
        ],
        'load_more' => 'Daha Fazla Yükle',
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Lütfen %s alanını boş bırakmayın',
        'length' => '%s en az %s ve en fazla %s karakter olabilir',
        'email' => 'Lütfen %s alanı için geçerli bir e-posta adresi girin.',
        'unique' => 'Girdiğiniz %s sistemde kayıtlı. Lütfen başka bir %s belirtin!',
        'inarray' => '%s alanı sadece \'%s\' değerlerini alabilir',
        'title_length' => 'En az 1, en fazla 70 karakterden oluşan bir başlık girmelisiniz.',
        'title_exists' => 'Bu başlık zaten mevcut, lütfen değiştirin',
        'title_unique' => 'Başlığınız benzersiz',
        'deck_length' => 'Lütfen 90 ile 120 karakter arasında bir giriş metni giriniz.',
    ],

    // === Permission Roles ===
    'role' => [
        'name' => 'Haber Yönetim Rolleri',
        'add' => 'Haber Ekleyebilir',
        'edit' => 'Haber Düzenleyebilir',
        'delete' => 'Haber Silebilir',
        'list' => 'Haber Yönetimini Görüntüleyebilir',
        'publish' => 'Haber Yayınlayabilir',
        'unpublish' => 'Haberi Yayından Kaldırabilir',
        'change_status' => 'Yayın Durumu Değiştirebilir',
        'edit_status' => 'Detaylı Yayın Durumu Değiştirebilir',
        'moderate_edit' => 'Başkalarının Haberlerini Düzenleyebilir',
        'moderate_delete' => 'Başkalarının Haberlerini Silebilir',
        'gallery' => 'Galeri Yönetebilir',
        'file' => 'Dosya Yönetebilir',
        'approval' => 'Haber Onaylayabilir',
        'video_download' => 'Video İndirebilir',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'add' => 'Haber ekleme yetkiniz yok',
        'edit' => 'Haber düzenleme yetkiniz yok',
        'list' => 'Haber yönetimini görüntüleme yetkiniz yok',
        'delete' => 'Haber silme yetkiniz yok',
        'approval' => 'Haber onaylama yetkiniz yok',
        'moderate_edit' => 'Başkalarına ait haber kayıtlarını düzenleyemezsiniz',
        'moderate_delete' => 'Başkalarına ait haber kayıtlarını silemezsiniz',
        'gallery' => 'Galeri yönetme yetkiniz yok',
        'file' => 'Dosya yönetme yetkiniz yok',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Dil',
            'name' => 'Başlık',
            'category_id' => 'Kategori',
            'created_at' => 'Tarih',
            'updated_at' => 'Düzenleme Tarihi',
            'news' => 'Haber',
            'summary' => 'Giriş',
            'seo' => 'SEO URL',
            'title' => 'Sayfa Başlığı',
            'keywords' => 'Anahtar Kelimeler',
            'description' => 'Açıklama',
            'status' => 'Yayın Durumu',
            'deck' => 'Öne Çıkan',
            'image' => 'Resim',
            'gallery_id' => 'Galeri',
            'file_id' => 'Dosya',
            'acl_id' => 'Erişim Kontrolü',
            'menu' => 'Menüye Ekle',
            'video' => 'Video Seç',
            'video_help' => 'Haberde görünmesini istediğiniz videonun adını arayın',
        ],
        'radio' => [
            'publish_yes' => 'Yayınla',
            'publish_no' => 'Sadece Kaydet',
            'deck_yes' => 'Giriş Olsun',
            'deck_no' => 'Giriş Olmasın',
        ],
    ],

    // === UI Elements ===
    'ui' => [
        'record_management' => 'Kayıt Yönetimi',
        'operation' => 'İşlem',
        'apply' => 'Uygula',
        'action_column' => 'Aksiyon',
        'select' => 'Seçin',
        'from' => 'Buradan',
        'to' => 'Buraya',
        'search' => 'Ara',
        'reset' => 'Sıfırla',
        'approval' => 'Onayla',
        'processing' => 'İşleniyor',
        'record_selected' => 'Kayıt seçildi',
        'operation_failed' => 'İşlem gerçekleştirilemedi',
        'select_operation' => 'Lütfen bir işlem seçin',
        'all' => 'Tümü',
        'pdf' => 'PDF',
        'csv' => 'CSV',
        'excel' => 'Excel',
        'print' => 'Yazdır',
        'print_info' => 'Yazdır bilgisi',
        'created_by' => 'Oluşturan',
        'copy' => 'Kopyala',
        'default_sort' => 'En Yeni',
        'default_lang' => 'TR',
        'date_locale' => 'tr-TR',
        'filter_cleared' => 'Filtreler temizlendi ve varsayılana döndürüldü.',
        'filter_applied' => 'Filtreler Uygulandı',
        'sort_label' => 'Sıralama',
        'limit_label' => 'Adet',
        'lang_label' => 'Dil',
    ],

    // === DataTable Configuration ===
    'datatable' => [
        'aria' => [
            'sortAscending' => ': artan sıralama için etkinleştir',
            'sortDescending' => ': azalan sıralama için etkinleştir',
        ],
        'emptyTable' => 'Tabloda veri yok',
        'info' => '_TOTAL_ kayıttan _START_ - _END_ arası gösteriliyor',
        'infoEmpty' => '0 kayıttan 0 - 0 arası gösteriliyor',
        'infoFiltered' => '_MAX_ toplam kayıttan filtrelendi',
        'lengthMenu' => '_MENU_ kayıt göster',
        'search' => 'Ara:',
        'zeroRecords' => 'Eşleşen kayıt bulunamadı',
        'loadingRecords' => 'Yükleniyor...',
        'processing' => 'İşleniyor...',
        'paginate' => [
            'first' => 'İlk',
            'last' => 'Son',
            'next' => 'Sonraki',
            'previous' => 'Önceki',
        ],
    ],

    // === Tokenizing Configuration ===
    'tokenizing' => [
        'hintText' => 'Arama terimi girin',
        'noResultsText' => 'Sonuç yok',
        'searchingText' => 'Aranıyor...',
    ],

    // === AnyTime Picker ===
    'anytime' => [
        'labelDayOfMonth' => 'Haftanın Günleri',
        'labelHour' => 'Saat',
        'labelMinute' => 'Dakika',
        'labelMonth' => 'Aylar',
        'labelTitle' => 'Lütfen Tarih ve Zamanı Seçin',
        'labelYear' => 'Yıl',
        'dayAbbreviations' => ['Pz', 'Pt', 'Sa', 'Ça', 'Pe', 'Cu', 'Ct'],
        'dayNames' => ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'],
        'monthAbbreviations' => ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'],
        'monthNames' => ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
    ],

    // === Status Badges ===
    'status_badge' => [
        'active' => 'Yayında',
        'draft' => 'Taslak',
        'general' => 'Genel',
    ],

    // === Sheet Labels ===
    'sheet' => [
        'lang_title' => 'Dil Seçimi',
        'ok' => 'Tamam',
        'search_placeholder' => 'Dil ara (örn: İngilizce, JP)...',
        'category_title' => 'Kategori Seç',
        'all' => 'Tümü (Filtresiz)',
    ],
    
    // === Wizard ===
    'wizard' => [
        'step' => [
            'language' => 'Dil Seçimi',
            'category' => 'Kategori',
            'title' => 'Başlık',
            'date' => 'Yayın Tarihi',
            'intro' => 'Giriş Metni (Deck)',
            'news' => 'Haber Detayı',
            'image' => 'Görsel Seçimi',
            'finish' => 'Tamamla',
        ],
        'subtitle' => [
            'language' => 'Haberin yayınlanacağı ana dili seçiniz.',
            'category' => 'Haberin ekleneceği kategoriyi belirleyin.',
            'title' => 'İlgi çekici bir başlık girin. (Min: 1, Max: 70)',
            'date' => 'Haberin yayınlanacağı tarih ve saat.',
            'intro' => 'Kısa ve öz bir özet yazın. (90 - 120 Karakter)',
            'news' => 'Zengin metin editörü.',
            'image' => 'Kapak görseli yükleyin.',
            'finish' => 'Son kontroller ve yayınlama.',
        ],
        'label' => [
            'news_title' => 'Haber Başlığı',
            'intro_text' => 'Giriş Metni',
            'upload_text' => 'Görsel Yüklemek İçin Dokunun',
            'published' => 'Yayında',
            'published_desc' => 'Haber siteye eklendiği an herkes tarafından görülebilecek.',
            'editor_placeholder' => 'Haberinizi buraya yazmaya başlayın...',
            'category_select' => 'Kategori Seçiniz...',
            'change_image' => 'Görsel Değiştir',
        ],
        'image' => [
            'drop_title' => 'Bir görseli buraya sürükleyin',
            'drop_description' => 'Yükleyin, yapıştırın veya bir görsel bırakın.',
            'upload_link' => 'dosya yükleyin',
            'link_separator' => 'VEYA',
            'link_placeholder' => 'Görsel bağlantısını yapıştırın',
            'link_action' => 'İçe Aktar',
            'hint_loading' => 'Görsel içe aktarılıyor...',
            'hint_invalid_type' => 'Burada yalnızca görsel dosyaları desteklenir.',
            'hint_no_drop_data' => 'Bırakılan içerikte kullanılabilir bir görsel bulunamadı.',
            'hint_remote_failed' => 'Bu kaynaktaki görsel içe aktarılamadı.',
        ],
        'messages' => [
            'success_sim' => 'Haber başarıyla oluşturuldu! (Simülasyon)',
            'video_success_sim' => 'Video başarıyla eklendi! (Simülasyon)',
            'video_embed_error' => 'Lütfen geçerli bir embed kodu giriniz (iframe).',
            'video_title_error' => 'Video başlığı en az 3 karakter olmalıdır.',
            'card_click' => 'Kart tıklandı: %s',
        ],
        'summary' => [
            'title' => 'Özet',
            'selected_none' => 'Seçilmedi',
        ],
        'button' => [
            'save' => 'Kaydet',
            'next' => 'Devam Et',
            'prev' => 'Geri',
            'finish' => 'Kaydet ve Bitir',
        ]
    ],

    // === Context Menu ===
    'context_menu' => [
        'title' => 'Haber İşlemleri',
        'edit' => 'Düzenle',
        'gallery' => 'Galeri İşlemleri',
        'files' => 'Dosya Ekle',
        'video' => 'Habere Video Ekle',
        'add_video' => 'Video Ekle',
        'add_file' => 'Dosya Ekle',
        'status' => 'Durum Değiştir',
        'delete' => 'Sil',
        'delete_error' => 'Silme işlemi başarısız!',
        'video_success_sim' => 'Video Başarıyla Eklendi (Simülasyon)',
        'video_embed_error' => 'Geçersiz Embed Kodu',
        'video_title_error' => 'Geçersiz Başlık',
        'publish' => 'Yayınla',
        'unpublish' => 'Yayından Kaldır',
        'published_msg' => 'Yayınlandı',
        'unpublished_msg' => 'Yayından Kaldırıldı',
        'confirm' => 'Evet',
        'cancel' => 'Vazgeç',
        'edit_alert' => "DÜZENLEME MODU:\nID: %s\nBaşlık: %s",
        'gallery_alert' => "GALERİ YÖNETİMİ:\nID: %s\nBaşlık: %s\nHaber için resim galerisi açılıyor.",
        'files_alert' => "DOSYA YÖNETİMİ:\nID: %s\nBaşlık: %s\nHaber için dosya yükleme ekranı açılıyor.",
        'status_alert' => "DURUM GÜNCELLENDİ:\n%s",
        'delete_success' => 'Haber silindi!',
    ],

    // === Hero Slider ===
    'hero_slider_add' => 'Slider\'a Gönder',
    'hero_slider_remove' => 'Slider\'dan Kaldır',
    'hero_slider_added' => 'Haber slider\'a eklendi.',
    'hero_slider_removed' => 'Haber slider\'dan kaldırıldı.',
    'hero_slider_error' => 'Slider güncellenemedi.',
    'hero_slider_badge' => 'Slider',

    // === Latest News ===
    'latest_news' => [
        'title' => 'Son Haberler',
        'subtitle' => 'Tüm Haberleri Gör',
        'description' => 'Henüz haber yayınlanmadı.',
    ],

    // === AJAX & Relation Messages ===
    'ajax' => [
        'success' => 'Başarılı',
        'error' => 'Hata oluştu',
        'database_error' => 'Veritabanı Hatası',
        'record_not_found' => 'Haber bulunamadı!',
        'unknown_type' => 'Bilinmeyen ilişki türü',
        'relation_updated' => 'İlişki başarıyla güncellendi',
    ],

    // === Media Picker UI ===
    'picker' => [
        'gallery' => 'Mevcut Galeriyi Seç',
        'file' => 'Mevcut Dosyayı Seç',
        'video' => 'Mevcut Videoyu Seç',
    ],

    // === AI Assistant ===
    'ai' => [
        'generate_deck' => 'AI ile Özet Çıkar',
        'suggest_headlines' => 'AI ile Başlık Öner',
        'generate_seo' => 'AI ile SEO Oluştur',
        'generating' => 'Oluşturuluyor...',
        'missing_key' => 'Gemini API anahtarı Google Services Hub üzerinde yapılandırılmamış.',
        'content_required' => 'AI üretimi için başlık veya içerik gereklidir.',
        'select_headline' => 'Bir Başlık Seçin',
        'deck_success' => 'Özet başarıyla oluşturuldu (%d karakter)',
        'deck_error' => 'Özet oluşturulamadı.',
        'headline_success' => 'Başlık önerileri hazırlandı.',
        'copy' => 'Bunu Kullan',
    ],
];
