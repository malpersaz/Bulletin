<?php
/**
 * Poll/Survey Module - Turkish Language File
 *
 * @package AKM Bulletin CMS
 * @module  mod_survey
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Poll/Survey Module Language Translations
 */
$lang['mod_survey'] = [
    'add' => 'Anket Ekle',
    'choose_wish' => 'Dilerseniz Seçin',

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Anket Ekle',
        'edit' => 'Anket Düzenle',
        'list' => 'Anket Listesi',
        'delete' => 'Anket Sil',
        'cancel' => 'Vazgeç',
        'save' => 'Kaydet',
        'update' => 'Güncelle',
        'remove' => 'Kaldır',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Anket başarıyla eklendi!',
            'updated' => 'Anket başarıyla güncellendi!',
            'deleted' => 'Anket başarıyla silindi!',
            'set_default' => 'Anket varsayılan olarak ayarlandı!',
        ],
        'error' => [
            'not_added' => 'Anket eklenemedi!',
            'not_updated' => 'Anket güncellenemedi!',
            'not_deleted' => 'Anket silinemedi!',
            'not_found' => 'Belirtilen anket kaydı bulunamadı!',
        ],
        'info' => [
            'edit_double_click' => 'Düzenlemek istediğiniz ankete çift tıklayın',
            'drag_reorder' => 'Sıralamak istediğiniz anketi sürükleyip bırakın',
        ],
    ],

    // === Status Types ===
    'status' => [
        'published' => 'Yayında',
        'draft' => 'Taslak',
        'archived' => 'Arşivlendi',
        'default' => 'Varsayılan Anket',
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Evet',
        'no' => 'Hayır',
    ],

    // === Form Labels ===
    'form' => [
        'basic_info' => 'Temel Bilgiler',
        'display_settings' => 'Görüntüleme Ayarları',
        'advanced_settings' => 'Gelişmiş Ayarlar',

        'id' => 'ID',
        'question' => 'Anket Sorusu',
        'language' => 'Görüntüleme Dili',
        'is_default' => 'Varsayılan Anket Yap',
        'access_control' => 'Erişim Kontrolü',
        'options' => 'Anket Seçenekleri',
        'start_date' => 'Başlangıç Tarihi',
        'end_date' => 'Bitiş Tarihi',
        'multiple_choice' => 'Çoklu Seçime İzin Ver',
    ],

    // === Table Columns ===
    'columns' => [
        'id' => 'ID',
        'language' => 'Görüntüleme Dili',
        'question' => 'Anket Sorusu',
        'is_default' => 'Varsayılan Anket',
        'status' => 'Durum',
        'created_at' => 'Oluşturma Tarihi',
        'access_level' => 'Erişim Seviyesi',
        'actions' => 'İşlemler',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Yeni Anket Ekle',
        'edit' => 'Seçileni Düzenle',
        'delete' => 'Seçileni Sil',
        'refresh' => 'Yenile',
        'set_default' => 'Varsayılan Yap',
        'view_results' => 'Sonuçları Görüntüle',
        'export' => 'Sonuçları Dışa Aktar',
    ],

    // === Validation Messages ===
    'validation' => [
        'required' => '%s alanı zorunludur',
        'min_length' => '%s alanı en az %s karakter olmalıdır',
        'max_length' => '%s alanı en fazla %s karakter olmalıdır',
        'unique' => '%s alanı benzersiz olmalıdır',
        'email' => 'Lütfen geçerli bir e-posta adresi girin',
        'min_options' => 'Anket en az 2 seçenek içermelidir',
        'max_options' => 'Anket en fazla %s seçenek içerebilir',
    ],

    // === Access Control (ACL) ===
    'role' => [
        'name' => 'Anket Yönetim Yetkileri',
        'list' => 'Anketleri Görüntüleyebilir',
        'add' => 'Anket Eklenebilir',
        'edit' => 'Kendi Anketini Düzenleyebilir',
        'moderate_edit' => 'Tüm Anketleri Düzenleyebilir',
        'delete' => 'Kendi Anketini Silebilir',
        'moderate_delete' => 'Tüm Anketleri Silebilir',
        'manage_access' => 'Erişimi Yönetebilir',
        'view_results' => 'Anket Sonuçlarını Görebilir',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'view_denied' => 'Anket yönetimini görüntüleme yetkiniz yok',
        'add_denied' => 'Anket ekleme yetkiniz yok',
        'edit_denied' => 'Anket düzenleme yetkiniz yok',
        'delete_denied' => 'Anket silme yetkiniz yok',
        'results_denied' => 'Anket sonuçlarını görüntüleme yetkiniz yok',
        'access_denied' => 'Bu anketi düzenleme/silme yetkiniz yok (size ait değil)',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'delete_single' => 'Bu anketi silmek istediğinize emin misiniz?',
        'delete_multiple' => 'Seçilen :count anketi silmek istediğinize emin misiniz?',
        'set_default' => 'Bu anketi varsayılan yapmak istediğinize emin misiniz?',
        'publish' => 'Bu anketi yayınlamak istediğinize emin misiniz?',
        'unpublish' => 'Bu anketin yayınını kaldırmak istediğinize emin misiniz?',
    ],

    // === Helper Text ===
    'help' => [
        'poll_question' => 'Anketiniz için açık ve net bir soru girin',
        'access_control' => 'Bu anketi hangi kullanıcı rollerinin görüp oy verebileceğini belirtin',
        'default_poll' => 'Aynı anda yalnızca bir anket varsayılan olarak ayarlanabilir',
        'multiple_choice' => 'Kullanıcıların birden fazla seçenek seçmesine izin ver',
        'poll_options' => 'Kullanıcıların seçebileceği oylama seçeneklerini ekleyin',
    ],

    // === Placeholders ===
    'placeholders' => [
        'poll_question' => 'Anket sorusunu buraya girin...',
        'poll_option' => 'Anket seçeneğini girin...',
        'start_date' => 'Başlangıç tarihi seçin...',
        'end_date' => 'Bitiş tarihi seçin (isteğe bağlı)...',
    ],

    // === Empty States ===
    'empty' => [
        'no_polls' => 'Henüz hiç anket oluşturulmadı',
        'no_results' => 'Arama kriterlerinize uygun anket bulunamadı',
        'add_first' => 'İlk anketi oluşturmak için butona tıklayın',
        'no_votes' => 'Henüz hiç oy kullanılmadı',
    ],

    // === Loading States ===
    'loading' => [
        'saving' => 'Kaydediliyor...',
        'loading' => 'Yükleniyor...',
        'processing' => 'Oylar işleniyor...',
        'counting' => 'Sonuçlar hesaplanıyor...',
    ],

    // === Success Messages ===
    'success' => [
        'operation_completed' => 'İşlem başarıyla tamamlandı!',
        'changes_saved' => 'Değişiklikler kaydedildi!',
        'poll_published' => 'Anket başarıyla yayınlandı!',
        'poll_unpublished' => 'Anket yayını başarıyla kaldırıldı!',
    ],

    // === Error Messages ===
    'errors' => [
        'general' => 'Bir hata oluştu. Lütfen tekrar deneyin.',
        'network' => 'Ağ hatası. Lütfen internet bağlantınızı kontrol edin.',
        'server' => 'Sunucu hatası. Lütfen daha sonra tekrar deneyin.',
        'no_default_poll' => 'Varsayılan anket ayarlanmadı',
        'multiple_defaults' => 'Birden fazla varsayılan anket tespit edildi',
    ],

    // === Results Display ===
    'results' => [
        'total_votes' => 'Toplam Oy',
        'vote_count' => ':count oy',
        'vote_saved' => 'Oyunuz kaydedildi!',
        'percentage' => '%%:percentage%',
        'no_votes_yet' => 'Henüz oy yok',
        'view_detailed_results' => 'Detaylı Sonuçları Görüntüle',
        'export_to_csv' => 'CSV Olarak Dışa Aktar',
        'reset_results' => 'Sonuçları Sıfırla',
    ],

    // === Poll Options Management ===
    'options_management' => [
        'add_option' => 'Seçenek Ekle',
        'remove_option' => 'Seçeneği Kaldır',
        'option_text' => 'Seçenek Metni',
        'vote_count' => 'Oy Sayısı',
        'reorder_options' => 'Seçenekleri Sırala',
    ],

];