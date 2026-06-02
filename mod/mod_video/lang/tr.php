<?php
                $lang['mod_video'] = [
    'add' => 'Video Ekle',
    'edit' => 'Videoyu Düzenle',
    'list' => 'Video Listesi',
    'information' => 'Bilgi!',
    'oke' => 'İşlem Başarılı!',
    'hata' => 'Hata Oluştu!',
    'error' => 'Ölümcül Hata Oluştu!',
    'remove_image' => 'Bu Resmi Kaldırmak İstediğinize Eminmisiniz?',
    'remove_image_oke' => 'Resim Kaldırıldı',
    'choose_wish' => 'Dilerseniz Seçin',
    'remove_record' => 'Bu Kaydı Silmek İstediğinize Eminmisiniz?',
    'converting' => 'Video Dönüştürülüyor Lütfen Bekleyin...',
    'converted' => 'Video Yükleme Tamamlandı! Şimdi Videoyu Dönüştürebilirsiniz.',
    '19' => 'Video Dönüştürülürken Hatalar Oluştu %s',
    '20' => 'Video Dönüştürülme Tamamlandı.<br>Bu İşlem %s Saniye Sürdü.',
    'add_uzak' => 'Bu Videoyu Uzak Video Olarakta Sisteme Ekle',
    'unremove_original' => 'Bu Videonun Orjinal Halini Kaydet',
    'message' =>
    [
        'oke' =>
        [
            'add' => 'Video Eklendi!',
            'edit' => 'Video Düzenlendi!',
        ],
        'error' =>
        [
            'add' => 'Video Eklenemedi!',
            'edit' => 'Video Düzenlenemedi!',
        ],
        'information' =>
        [
            'moderate' => 'Düzenlemek İstediğiniz İçeriği İki Defa Tıklayın',
            'move' => 'Sırasını Değiştirmek İstediğiniz İçeriği Sürükleyip Bırakın',
        ],
    ],
    'errors' =>
    [
        '1' => 'TMP dizininden FLV dosyası Taşınırken Hata Oluştu',
        '2' => 'TMP dizininden Resim dosyası Taşınırken Hata Oluştu',
        '3' => 'TMP dizininden ORjinal Video Dosyası Taşınırken Hata Oluştu',
        'record' => 'Belirttiğiniz Kayıt Bulunamadı!',
        'in_valid_ext' => 'Geçersiz Bir Video Yükleme İstediniz.
	 Yanlızca <font color="black">%s</font> uzantılı Videolar Yükleyebilirsiniz.',
        'in_valid_ext_original' => 'Göndermiş Olduğunuz Orjinal Video Hatalı.
	 Yanlızca <font color="black">%s</font> uzantılı Videolar Yükleyebilirsiniz.'
    ],
    'button' =>
    [
        'add' => 'Ekle',
        'edit' => 'Düzenle',
        'yenile' => 'Yenile',
        'delete' => 'Sil',
        'gallery' => 'Resim Galerisi',
        'vazgec' => 'Vazgeç',
        'kaldir' => 'Kaldır',
        'evet' => 'Evet',
        'hayir' => 'Hayır',
        'yukle' => 'Yükle',
        'donustur' => 'Dönüştür'
    ],
    'grid' =>
    [
        0 => 'Toplam {total} Sonuçtan {from} den {to}\\\'a Kadar Olanlar Görüntüleniyor.',
        1 => 'Sayfa',
        2 => 'Sayfa Sayısı',
        3 => 'Veriler Yükleniyor, Lütfen Bekleyin ...',
        4 => 'Hiçbir Veri Bulunamadı',
        5 => 'Bağlantı Hatası',
        6 => 'Arama',
        7 => 'Lütfen En Az Bir Satır Seçin',
        8 => 'Lütfen Sadece Bir Satır Seçin',
        'publish' =>
             [
                'key' => 'Yayınla',
                'value' => 'Bu Videoyu Yayınlamak İstediğinize Eminmisiniz?',
            ],
        'unpublish' =>
             [
                'key' => 'Yayından Al',
                'value' => 'Bu Videoyu Yayından Kaldırmak İstediğinize Eminmisiniz?',
            ],
    ],
    'validator' =>
    [
        'required' => 'Lütfen %s Alanını Boş Bırakmayın',
        'length' => '%s En Kısa %s En Uzun %s Karekterden Oluşabilir',
        'email' => '%s Alanına Lütfen Geçerli Bir E-Posta Adresi Yazın.',
        'unique' => 'Yazmış Olduğunuz %s Sistemde Kayıtlı.Lütfen Başka Bir %s Belirleyin!',
        'inarray' => '%s Alanı Sadece \' %s \' değerleri alabilir',
    ],
    'roll' =>
    [
        'name' => 'Video Yönetim Rolleri',
        'add' => 'Video Ekleyebilir',
        'edit' => 'Video Düzenleyebilir',
        'delete' => 'Video Silebilir',
        'list' => 'Video Yönetimini Görüntüleyebilir',
        'moderate_edit' => 'Kendisine Ait Olmayan Video Kaydını Düzenleyebilir',
        'moderate_delete' => 'Kendisine Ait Olmayan Video Kaydını Silebilir',
    ],
    'role_errors' =>
    [
        'moderate_edit' => 'Size Ait Olmayan Video Kaydını Düzenleyemezsiniz',
        'moderate_delete' => 'Size Ait Olmayan Video Kaydını Silemezsiniz',
        'add' => 'Video Eklemeye Permissionniz Yok',
        'edit' => 'Video Düzenlemeye Permissionniz Yok',
        'list' => 'Video Yönetimini Görüntülemeye Permissionniz Yok',
        'delete' => 'Video Silmeye Permissionniz Yok',
    ],
    'admin' =>
    [
        'columns' =>
        [
            'id' => 'ID',
            'lang' => 'Videonun Görüntüleneceği Dil',
            'name' => 'Video Adı',
            'duration' => 'Süre',
            'created_at' => 'Tarih',
            'status' => 'Yayınlansın mı?',
            'description' => 'Açıklama',
            'image' => 'Resim',
            'preview' => 'Önizleme',
            'acl_id' => 'Bu Videoyu Kimler Görüntüleyebilir? <br><a href=\'mod_acl/list/\' target=\'_blank\' class=\'btn btn-xs blue\' style=\'margin-top:5px;\'><i class=\'fa fa-external-link\'></i> ACL - Erişim kontrol listesi</a>',
        ],
        'radio' =>
        [
            'publish_yes' => 'Evet',
            'publish_no' => 'Hayır',
        ],
    ],
     'placeholder' => [
        'name' => 'Video adı...',
        'description' => 'Videonun içeriği ve amacı hakkında kısa açıklama...',
    ],
    'empty_state' => 'Yüklenmiş bir video bulunamadı',
    // Extended Video Management Features
    'upload' => [
        'select_file' => 'Video Dosyası Seçin',
        'drag_drop' => 'Seç veya Taşı',
        'supported_formats' => 'Desteklenen Formatlar: MP4, AVI, MOV, WMV, FLV, WebM',
        'max_file_size' => 'Maksimum dosya boyutu: %s',
        'upload_progress' => 'Yükleme İlerlemesi',
        'processing' => 'Video işleniyor...',
        'conversion_queue' => 'Video dönüştürme sırasına eklendi',
    ],
    // Video Quality Options
    'quality' => [
        'auto' => 'Otomatik Kalite',
        'high' => 'Yüksek Kalite',
        'medium' => 'Orta Kalite',
        'low' => 'Düşük Kalite',
        'original' => 'Orijinal Kalite',
    ],
    // Video Categories
    'category' => [
        'select' => 'Kategori Seçin',
        'all' => 'Tüm Kategoriler',
        'uncategorized' => 'Kategorisiz',
        'add' => 'Yeni Kategori Ekle',
    ],
    // Publishing Options
    'publishing' => [
        'immediately' => 'Hemen Yayınla',
        'schedule' => 'Yayınlamayı Zamanla',
        'draft' => 'Taslak Olarak Kaydet',
        'private' => 'Özel',
        'password_protected' => 'Şifre Korumalı',
    ],
    // SEO Options
    'seo' => [
        'meta_title' => 'Meta Başlığı',
        'meta_description' => 'Meta Açıklaması',
        'meta_keywords' => 'Meta Anahtar Kelimeleri',
        'friendly_url' => 'Dostu URL',
        'video_sitemap' => 'Video Sitemap\'ine Dahil Et',
    ],
    // Advanced Features
    'advanced' => [
        'captions' => 'Altyazılar',
        'chapters' => 'Video Bölümleri',
        'playlist' => 'Oynatma Listesine Ekle',
        'related_videos' => 'İlişkili Videolar',
        'comments' => 'Yorumları Etkinleştir',
        'rating' => 'Derecelendirmeyi Etkinleştir',
        'embed' => 'Yerleştirme Kodu',
        'download' => 'İndirmeye İzin Ver',
    ],
    // Video Statistics
    'statistics' => [
        'views' => 'Toplam İzlenme',
        'unique_views' => 'Tekil İzlenme',
        'average_watch_time' => 'Ortalama İzleme Süresi',
        'completion_rate' => 'Tamamlama Oranı',
        'engagement' => 'Etkileşim Skoru',
        'shares' => 'Paylaşımlar',
        'likes' => 'Beğeniler',
        'dislikes' => 'Beğenmemeler',
    ],
    // Bulk Operations
    'bulk' => [
        'select_all' => 'Tümünü Seç',
        'deselect_all' => 'Seçimi Kaldır',
        'bulk_edit' => 'Toplu Düzenle',
        'bulk_delete' => 'Toplu Sil',
        'bulk_publish' => 'Toplu Yayınla',
        'bulk_unpublish' => 'Toplu Yayından Kaldır',
        'export' => 'Videoları Dışa Aktar',
        'import' => 'Videoları İçe Aktar',
    ],
    // Notification Messages
    'notifications' => [
        'upload_complete' => 'Video başarıyla yüklendi',
        'conversion_started' => 'Video dönüştürme işlemi başladı',
        'conversion_complete' => 'Video dönüştürme tamamlandı',
        'conversion_failed' => 'Video dönüştürme başarısız oldu',
        'publish_scheduled' => 'Video yayınlanması zamanlandı',
        'video_published' => 'Video başarıyla yayınlandı',
        'video_unpublished' => 'Video başarıyla yayından kaldırıldı',
    ],
    // Help Text
    'help' => [
        'upload_tips' => 'İpuçları: Yüksek kaliteli videolar kullanın.',
        'conversion_info' => 'Bu işlem birkaç dakika sürebilir.',
        'seo_tips' => 'Açıklayıcı başlıklar ve açıklamalar kullanın.',
        'embed_help' => 'Bu kodu diğer web sitelerine yerleştirmek için kopyalayıp yapıştırın.',
    ],
    // Error Categories
    'error_types' => [
        'upload_error' => 'Yükleme Hatası',
        'conversion_error' => 'Dönüştürme Hatası',
        'permission_error' => 'Yetki Hatası',
        'validation_error' => 'Doğrulama Hatası',
        'system_error' => 'Sistem Hatası',
        'network_error' => 'Ağ Hatası',
    ],
    // Status Indicators
    'status' => [
        'uploading' => 'Yükleniyor...',
        'processing' => 'İşleniyor...',
        'converting' => 'Dönüştürülüyor...',
        'completed' => 'Tamamlandı',
        'failed' => 'Başarısız',
        'pending' => 'Beklemede',
        'published' => 'Yayınlandı',
        'unpublished' => 'Yayınlanmadı',
        'scheduled' => 'Zamanlandı',
        'draft' => 'Taslak',
    ],
    // Time Formats
    'time_formats' => [
        'seconds' => 'saniye',
        'minutes' => 'dakika',
        'hours' => 'saat',
        'ago' => 'önce',
        'remaining' => 'kalan',
        'estimated_time' => 'Tahmini süre: %s',
    ],
    // File Information
    'file_info' => [
        'file_name' => 'Dosya Adı',
        'file_size' => 'Dosya Boyutu',
        'file_type' => 'Dosya Türü',
        'duration' => 'Süre',
        'resolution' => 'Çözünürlük',
        'bitrate' => 'Bit Hızı',
        'fps' => 'Kare Hızı',
        'codec' => 'Kodek',
    ],
    // API Integration
    'api' => [
        'embed_url' => 'Yerleştirme URL\'si',
        'api_endpoint' => 'API Uç Noktası',
        'api_key' => 'API Anahtarı',
        'webhook_url' => 'Webhook URL\'si',
        'third_party' => 'Üçüncü Taraf Entegrasyonu',
    ],
    // Moderation Tools
    'moderation' => [
        'pending_approval' => 'Onay Bekliyor',
        'approved' => 'Onaylandı',
        'rejected' => 'Reddedildi',
        'flagged' => 'Bildirilen İçerik',
        'review_queue' => 'İnceleme Sırası',
        'moderator_notes' => 'Moderatör Notları',
    ],
    // Export/Import Options
    'data_management' => [
        'export_csv' => 'CSV Olarak Dışa Aktar',
        'export_xml' => 'XML Olarak Dışa Aktar',
        'export_json' => 'JSON Olarak Dışa Aktar',
        'import_csv' => 'CSV\'den İçe Aktar',
        'backup_videos' => 'Videoları Yedekle',
        'restore_videos' => 'Yedekleri Geri Yükle',
    ],
    // Search and Filter
    'search_filters' => [
        'search_by_title' => 'Başlığa Göre Ara',
        'search_by_description' => 'Açıklamaya Göre Ara',
        'filter_by_date' => 'Tarihe Göre Filtrele',
        'filter_by_category' => 'Kategoriye Göre Filtrele',
        'filter_by_author' => 'Yazara Göre Filtrele',
        'filter_by_status' => 'Duruma Göre Filtrele',
        'advanced_search' => 'Gelişmiş Arama',
    ],
    // User Interface
    'ui' => [
        'dashboard' => 'Video Paneli',
        'overview' => 'Genel Bakış',
        'recent_videos' => 'Son Videolar',
        'popular_videos' => 'Popüler Videolar',
        'trending_videos' => 'Trend Videolar',
        'featured_videos' => 'Öne Çıkan Videolar',
        'archive' => 'Video Arşivi',
        'trash' => 'Çöp Kutusu',
    ],
    // Wizard Steps Instructions
    'wizard' => [
        'step1_title' => 'Video Seçin',
        'step1_instruction' => 'Lütfen yüklenecek video dosyasını seçin.',
        'step2_title' => 'Videoyu Dönüştür',
        'step2_instruction' => 'Videoyu web yayını için dönüştürün.',
        'step3_title' => 'Videoyu Kaydet',
        'step3_instruction' => 'Video bilgilerini doldurun ve kaydedin.',
    ],
];