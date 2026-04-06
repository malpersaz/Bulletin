<?php
/**
 * Publication Management Module - Turkish Language File
 *
 * @package Rojname
 * @module  mod_publication
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Publication Management Module Language Translations
 */
$lang['mod_publication'] = [

    // === Basic Actions ===
    'add' => 'Yayın Ekle',
    'edit' => 'Yayını Düzenle',
    'list' => 'Yayın Listesi',
    'information' => 'Bilgi!',
    'success' => 'İşlem Başarılı!',
    'error' => 'Hata Oluştu!',
    'fatal_error' => 'Ölümcül Hata Oluştu!',
    'remove_image' => 'Bu Resmi Kaldırmak İstediğinize emin misiniz?',
    'remove_image_success' => 'Resim Kaldırıldı',
    'choose_wish' => 'Dilerseniz Seçin',
    'remove_record' => 'Bu Kaydı Silmek İstediğinize emin misiniz?',
    'delete_confirm' => 'Bu yayını silmek istediğinize emin misiniz?',
    'quick_unpublish_confirm' => 'Bu yayını yayından kaldırmak istediğinizden emin misiniz?',
    'quick_publish_confirm' => 'Bu yayını yayınlamak istediğinizden emin misiniz?',
    'add_image' => 'Resim Ekle',
    'upload_change_image' => 'Resim Yükle/Değiştir',
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
        'delete_error' => 'Silme işlemi başarısız oldu!',
        'video_success_sim' => 'Video başarıyla eklendi! (Simülasyon)',
        'video_embed_error' => 'Geçersiz embed kodu',
        'video_title_error' => 'Geçersiz başlık',
    ],

    // === Messages ===
    'message' => [
        'success' => [
            'add' => 'Yayın Eklendi!',
            'edit' => 'Yayın Düzenlendi!',
        ],
        'error' => [
            'add' => 'Yayın Eklenemedi!',
            'edit' => 'Yayın Düzenlenemedi!',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'Belirttiğiniz Kayıt Bulunamadı!',
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
        'yes' => 'Evet',
        'no' => 'Hayır',
    ],

    // === Data Grid Messages ===
    'grid' => [
        0 => 'Toplam {total} sonuçtan {from} ile {to} arasındaki kayıtlar gösteriliyor.',
        1 => 'Sayfa',
        2 => 'Sayfa Sayısı',
        3 => 'Veriler yükleniyor, lütfen bekleyin...',
        4 => 'Veri bulunamadı',
        5 => 'Bağlantı Hatası',
        6 => 'Arama',
        7 => 'Lütfen en az bir satır seçin',
        8 => 'Lütfen sadece bir satır seçin',
        'approve' => [
            'key' => 'Yayınla',
            'value' => 'Bu yayını yayınlamak istediğinize emin misiniz?',
        ],
        'unapprove' => [
            'key' => 'Yayından Kaldır',
            'value' => 'Bu yayını yayından kaldırmak istediğinize emin misiniz?',
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
        'deck_length' => 'Lütfen en az 110, en fazla 280 karakterden oluşan bir giriş metni giriniz.',
    ],

    // === Permission Roles ===
    'roll' => [
        'name' => 'Yayın Yönetim Rolleri',
        'add' => 'Yayın Ekleyebilir',
        'edit' => 'Yayın Düzenleyebilir',
        'delete' => 'Yayın Silebilir',
        'list' => 'Yayın Yönetimini Görüntüleyebilir',
        'publish' => 'Yayın Yayınlayabilir',
        'unpublish' => 'Yayını Yayından Kaldırabilir',
        'change_status' => 'Yayın Durumu Değiştirebilir',
        'edit_status' => 'Detaylı Yayın Durumu Değiştirebilir',
        'moderate_edit' => 'Başkalarının Yayınlarını Düzenleyebilir',
        'moderate_delete' => 'Başkalarının Yayınlarını Silebilir',
        'gallery' => 'Galeri Yönetebilir',
        'file' => 'Dosya Yönetebilir',
        'approval' => 'Yayın Onaylayabilir',
        'video_download' => 'Video İndirebilir',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'add' => 'Yayın eklemeye permissionniz yok',
        'edit' => 'Yayın düzenlemeye permissionniz yok',
        'list' => 'Yayın yönetimini görüntülemeye permissionniz yok',
        'delete' => 'Yayın silmeye permissionniz yok',
        'approval' => 'Yayın approvema permissionniz yok',
        'moderate_edit' => 'Başkalarına ait yayın kayıtlarını düzenleyemezsiniz',
        'moderate_delete' => 'Başkalarına ait yayın kayıtlarını silemezsiniz',
        'gallery' => 'Galeri yönetme permissionniz yok',
        'file' => 'Dosya yönetme permissionniz yok',
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
            'publication' => 'Yayın',
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
            'video_help' => 'Yayınte görünmesini istediğiniz videonun adını arayın',
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
        'all' => 'Tümü',
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
        'labelSecond' => 'Saniye',
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
            'lang' => 'Dil Seçimi',
            'language' => 'Dil Seçimi',
            'category' => 'Kategori',
            'title' => 'Başlık',
            'date' => 'Yayın Tarihi',
            'intro' => 'Giriş Metni (Deck)',
            'publication' => 'Yayın Detayı',
            'image' => 'Görsel Seçimi',
            'finish' => 'Tamamla',
        ],
        'subtitle' => [
            'language' => 'İçeriğin yayınlanacağı ana dili seçiniz.',
            'category' => 'İçeriğin ekleneceği kategoriyi belirleyin.',
            'title' => 'İlgi çekici bir başlık girin. (Min: 1, Max: 70)',
            'date' => 'İçeriğin yayınlanacağı tarih ve saat.',
            'intro' => 'Kısa ve öz bir özet yazın. (110 - 280 Karakter)',
            'publication' => 'Zengin metin editörü.',
            'image' => 'Kapak görseli yükleyin.',
            'finish' => 'Son kontroller ve yayınlama.',
        ],
        'label' => [
            'publication_title' => 'Yayın Başlığı',
            'intro_text' => 'Giriş Metni',
            'upload_text' => 'Görsel Yüklemek İçin Dokunun',
            'published' => 'Yayında',
            'published_desc' => 'Yayın siteye eklendiği an herkes tarafından görülebilecek.',
            'editor_placeholder' => 'İçeriğinizi buraya yazmaya başlayın...',
            'category_select' => 'Kategori Seçiniz...',
            'change_image' => 'Görsel Değiştir',
        ],
        'messages' => [
            'success_sim' => 'Yayın başarıyla oluşturuldu! (Simülasyon)',
            'video_success_sim' => 'Video başarıyla eklendi! (Simülasyon)',
            'video_title_error' => 'Video başlığı en az 3 karakter olmalıdır.',
            'card_click' => 'Kart tıklandı: %s',
            'no_category_for_lang' => 'Bu dilde kategori bulunamadı. Lütfen önce kategorileri ekleyin.',
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
        'title' => 'Yayın İşlemleri',
        'edit' => 'Düzenle',
        'gallery' => 'Yayına Galeri Ekle',
        'files' => 'Yayına Dosya Ekle',
        'video' => 'Yayına Gömülü Video Ekle',
        'status' => 'Durum Değiştir',
        'delete' => 'Sil',
        'delete_error' => 'Silme işlemi başarısız oldu!',
        'video_success_sim' => 'Video başarıyla eklendi! (Simülasyon)',
        'video_embed_error' => 'Geçersiz embed kodu',
        'video_title_error' => 'Geçersiz başlık',
        'publish' => 'Yayınla',
        'unpublish' => 'Yayından Kaldır',
        'published_msg' => 'Yayınlandı',
        'unpublished_msg' => 'Yayından Kaldırıldı',
        'confirm' => 'Evet',
        'cancel' => 'Vazgeç',
        'edit_alert' => "DÜZENLEME MODU:\nID: %s\nBaşlık: %s",
        'gallery_alert' => "GALERİ YÖNETİMİ:\nID: %s\nBaşlık: %s\nYayın için resim galerisi açılıyor.",
        'files_alert' => "DOSYA YÖNETİMİ:\nID: %s\nBaşlık: %s\nYayın için dosya yükleme ekranı açılıyor.",
        'status_alert' => "DURUM GÜNCELLENDİ:\n%s",
        'delete_confirm' => 'Evet, Sil!',
        'delete_success' => 'Yayın silindi!',
        'video_unavailable' => 'Yayınlar için video yönetimi henüz kullanıma açık değil.',
    ],

    'mock' => [
        'titles' => [
            "Kürt Basınında Yeni Dönem Başlıyor",
            "Diyarbakır Kültür Sanat Festivali Takvimi Açıklandı",
            "Yerel Gazetecilik Çalıştayı Sona Erdi",
            "Yapay Zeka ve Medya İlişkisi Üzerine Notlar",
            "Geleneksel Gazetecilikten Dijital Medyaya Geçiş",
            "Sosyal Medya Kullanım İstatistikleri 2024",
            "Haftalık Teknoloji Özeti: Neler Oldu?",
            "Yazarlar Birliği Yeni Yönetimini Seçti",
            "Fotoğrafçılık Atölyesi Kayıtları Başladı",
            "Rojname Mobil Uygulaması Güncellendi"
        ],
        'categories' => ["Gündem", "Kültür & Sanat", "Teknoloji", "Medya", "Yaşam"],
        'authors' => [],
    ],

    // === AJAX & Relation Messages ===
    'ajax' => [
        'success' => 'Başarılı',
        'error' => 'Hata Oluştu',
        'database_error' => 'Veritabanı Hatası',
        'record_not_found' => 'Kayıt Bulunamadı!',
        'unknown_type' => 'Bilinmeyen ilişki türü',
        'file_added' => 'Yayına bir dosya eklendi',
        'file_removed' => 'Dosya yayından kaldırıldı',
        'gallery_added' => 'Yayına bir galeri eklendi',
        'gallery_removed' => 'Galeri yayından kaldırıldı',
        'relation_updated' => 'İlişki başarıyla güncellendi',
    ],

    // === Hero Slider ===
    'hero_slider_add' => 'Slider\'a Gönder',
    'hero_slider_remove' => 'Slider\'dan Kaldır',
    'hero_slider_added' => 'Slider\'a eklendi.',
    'hero_slider_removed' => 'Slider\'dan kaldırıldı.',
    'hero_slider_error' => 'Slider güncellemesi başarısız.',
    'hero_slider_badge' => 'Slider',

    // === Media Picker UI ===
    'picker' => [
        'gallery' => 'Mevcut Galeriyi Seç',
        'file' => 'Mevcut Dosyayı Seç',
        'video' => 'Mevcut Videoyu Seç',
    ],
];
