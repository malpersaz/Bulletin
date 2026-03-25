<?php
/**
 * News Management Module - Turkish Language File
 *
 * @package Rojname
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
    'oke' => 'İşlem Başarılı!',
    'hata' => 'Hata Oluştu!',
    'error' => 'Ölümcül Hata Oluştu!',
    'remove_image' => 'Bu Resmi Kaldırmak İstediğinize emin misiniz?',
    'remove_image_oke' => 'Resim Kaldırıldı',
    'choose_wish' => 'Dilerseniz Seçin',
    'remove_record' => 'Bu Kaydı Silmek İstediğinize emin misiniz?',
    'delete_confirm' => 'Bu haberi silmek istediğinize emin misiniz?',
    'quick_unpublish_confirm' => 'Bu haberi yayından kaldırmak istediğinizden emin misiniz?',
    'quick_publish_confirm' => 'Bu haberi yayınlamak istediğinizden emin misiniz?',
    'resim_ekle' => 'Resim Ekle',
    'resim_yukle_degistir' => 'Resim Yükle/Değiştir',
    'lokal' => 'Lokal',
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
        'oke' => [
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
        'record' => 'Belirttiğiniz Kayıt Bulunamadı!',
    ],

    // === Buttons ===
    'button' => [
        'next' => 'İleri',
        'prev' => 'Geri',
        'send' => 'Gönder',
        'add' => 'Ekle',
        'edit' => 'Düzenle',
        'yenile' => 'Yenile',
        'delete' => 'Sil',
        'gallery' => 'Resim Galerisi',
        'file' => 'Dosya Yöneticisi',
        'vazgec' => 'Vazgeç',
        'kaldir' => 'Kaldır',
        'evet' => 'Evet',
        'hayir' => 'Hayır',
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
        'deck_length' => 'Lütfen en az 110, en fazla 280 karakterden oluşan bir giriş metni giriniz.',
    ],

    // === Permission Roles ===
    'roll' => [
        'ad' => 'Haber Yönetim Rolleri',
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
        'kayit_yonetimi' => 'Kayıt Yönetimi',
        'islem' => 'İşlem',
        'uygula' => 'Uygula',
        'islem_column' => 'Aksiyon',
        'select' => 'Seçin',
        'burdan' => 'Buradan',
        'buraya' => 'Buraya',
        'ara' => 'Ara',
        'sifirla' => 'Sıfırla',
        'approval' => 'Onayla',
        'isleniyor' => 'İşleniyor',
        'record_selected' => 'Kayıt seçildi',
        'islem_gerceklestirilemedi' => 'İşlem gerçekleştirilemedi',
        'islem_secin' => 'Lütfen bir işlem seçin',
        'all' => 'Tümü',
        'pdf' => 'PDF',
        'csv' => 'CSV',
        'excel' => 'Excel',
        'yazdir' => 'Yazdır',
        'yazdir_bilgi' => 'Yazdır bilgisi',
        'olusturan' => 'Oluşturan',
        'kopyala' => 'Kopyala',
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
            'intro' => 'Kısa ve öz bir özet yazın. (110 - 280 Karakter)',
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
        'files' => 'Dosya İşlemleri',
        'status' => 'Durum Değiştir',
        'delete' => 'Sil',
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
        'delete_confirm' => 'Evet, Sil!',
        'delete_success' => 'Haber silindi!',
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
    ],
];
