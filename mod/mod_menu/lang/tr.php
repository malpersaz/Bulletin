<?php
/**
 * Menu Module - Turkish Language File
 *
 * @package AKM Business
 * @module  mod_menu
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Menu Module Language Translations
 */
$lang['mod_menu'] = [

    // === Page Titles ===
    'list' => 'Menü Yönetimi',
    'add' => 'Menü Ekle',
    'edit' => 'Menü Düzenle',
    'information' => 'Bilgilendirme',

    // === Legacy Message Keys (used by index.php) ===
    'message' => [
        'oke' => [
            'add' => 'Menü başarıyla eklendi!',
            'edit' => 'Menü başarıyla güncellendi!',
        ],
        'error' => [
            'add' => 'Menü eklenirken hata oluştu!',
            'edit' => 'Menü güncellenirken hata oluştu!',
        ],
    ],

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Ekle',
        'edit' => 'Düzenle',
        'list' => 'Listele',
        'delete' => 'Sil',
        'cancel' => 'İptal',
        'save' => 'Kaydet',
        'update' => 'Güncelle',
        'remove' => 'Kaldır',
        'refresh' => 'Yenile',
        'export' => 'Dışa Aktar',
        'import' => 'İçe Aktar',
        'bulk_actions' => 'Toplu İşlemler',
        'gallery' => 'Galeri',
        'file' => 'Dosyalar',
        'unlock_reorder' => 'Sürükle-Bırak Modunu Aç',
        'lock_reorder' => 'Sürükle-Bırak Modunu Kapat',
        'publish_toggle' => 'Yayın Durumu',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Ekleme başarılı!',
            'updated' => 'Güncelleme başarılı!',
            'deleted' => 'Silme başarılı!!',
            'reordered' => 'Sıralama başarılı!',
            'published' => 'Menü başarıyla yayınlandı!',
            'unpublished' => 'Menü yayından kaldırıldı!',
        ],
        'error' => [
            'not_added' => 'Ekleme hatası!',
            'not_updated' => 'Güncelleme hatası!',
            'not_deleted' => 'Silinme hatası!',
            'not_found' => 'Kayıt bulunamadı!',
        ],
        'warning' => [
            'delete' => 'Bu menüyü silmek istediğinizden emin misiniz?',
            'reorder' => 'Menü sırası değiştirilsin mi?',
        ],
        'info' => [
            'edit_double_click' => 'Düzenlemek istediğiniz menüyü çift tıklayın',
            'drag_reorder' => 'Sırasını değiştirmek için menüyü sürükleyip bırakın',
            'block_system_removed' => 'Blok sistemi kaldırıldı - bu özellik artık kullanılamıyor',
            'move' => 'Menülerin sırasını değiştirmek için sürükleyip bırakabilirsiniz.',
            'reorder_mode' => 'Mod: Sıralama (Açık)',
        ],
    ],

    // === Status Types ===
    'status' => [
        'published' => 'Yayında',
        'draft' => 'Taslak',
        'archived' => 'Arşivlenmiş',
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Evet',
        'no' => 'Hayır',
        'ok' => 'Tamam',
    ],

    // === Target Options ===
    'targets' => [
        'same' => 'Aynı Sayfada',
        'new' => 'Yeni Sayfada',
        'blank' => 'Yeni Sekmede',
    ],

    // === Form Labels ===
    'form' => [
        'basic_info' => 'Temel Bilgiler',
        'display_settings' => 'Görüntüleme Ayarları',
        'advanced_settings' => 'Gelişmiş Ayarlar',
        'acl_settings' => 'İzin Ayarları (ACL)',

        'id' => 'ID',
        'name' => 'Menü Adı',
        'url' => 'Menü Bağlantısı',
        'parent' => 'Üst Menü',
        'order' => 'Sıra',
        'language' => 'Görüntüleneceği Dil',
        'target' => 'Açılma Şekli',
        'published' => 'Yayın Durumu',
        'html_class' => 'HTML Sınıfı',
        'icon' => 'İkon',
        'access_control' => 'Erişim Kontrolü',
        'description' => 'Açıklama',
        'file' => 'Dosya',
    ],

    // === Table Columns ===
    'columns' => [
        'id' => 'ID',
        'language' => 'Dil',
        'name' => 'Menü Adı',
        'url' => 'Bağlantı',
        'parent_menu' => 'Alt Menü',
        'order' => 'Sıra',
        'target' => 'Hedef',
        'status' => 'Durum',
        'icon' => 'İkon',
        'access_level' => 'Erişim Seviyesi',
        'actions' => 'İşlemler',
        'parent_id' => 'Alt Menü',
        'html' => 'HTML',
        'acl_id' => 'Erişim İzni',
        'file' => 'Dosya',
    ],

    'admin' => [
        'columns' => [
            'language' => 'Dil',
            'name' => 'Menü Adı',
            'url' => 'Bağlantı',
            'file' => 'Dosya',
            'parent_id' => 'Alt Menü',
            'target' => 'Açılma Şekli',
            'status' => 'Yayın Durumu',
            'html' => 'HTML',
            'icon' => 'İkon',
            'acl_id' => 'Erişim İzni',
        ],
        'select_value' => [
            'target_e' => 'Yeni Pencerede (_blank)',
            'target_h' => 'Aynı Pencerede (_self)',
        ],
        'radio' => [
            'publish_yes' => 'Yayında',
            'publish_no' => 'Taslak',
        ],
    ],



    // === Validation Messages ===
    'validation' => [
        'required' => ':field alanı zorunludur',
        'min_length' => ':field alanı en az :min karakter olmalıdır',
        'max_length' => ':field alanı en fazla :max karakter olabilir',
        'unique' => ':field alanı benzersiz olmalıdır',
        'url' => 'Geçerli bir URL adresi giriniz',
        'numeric' => ':field alanı sayısal olmalıdır',
        'email' => 'Geçerli bir e-posta adresi giriniz',
    ],

    // === Access Control (ACL) ===
    'choose_wish' => 'Seçiniz',
    'permissions' => [
        'title' => 'Menü Yönetim Permissionleri',
        'view' => 'Menüleri Görüntüle',
        'add' => 'Menü Ekle',
        'edit_own' => 'Kendi Menüsünü Düzenle',
        'edit_all' => 'Tüm Menüleri Düzenle',
        'delete_own' => 'Kendi Menüsünü Sil',
        'delete_all' => 'Tüm Menüleri Sil',
        'manage_access' => 'Erişim Yönetimi',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'view_denied' => 'Menü yönetimini görüntüleme permissionniz yok',
        'add_denied' => 'Menü ekleme permissionniz yok',
        'edit_denied' => 'Menü düzenleme permissionniz yok',
        'delete_denied' => 'Menü silme permissionniz yok',
        'access_denied' => 'Bu menüyü düzenleme/silme permissionniz yok (size ait değil)',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'delete_single' => 'Bu menüyü silmek istediğinizden emin misiniz?',
        'delete_multiple' => 'Seçili :count menüyü silmek istediğinizden emin misiniz?',
        'publish' => 'Bu menüyü yayınlamak istediğinizden emin misiniz?',
        'unpublish' => 'Bu menüyü yayından kaldırmak istediğinizden emin misiniz?',
        'reorder_single' => 'Bu menüyü yeni yerine taşımak istiyor musunuz?',
    ],

    // === Helper Text ===
    'help' => [
        'parent_menu' => 'Bu menünün hangi menünün altında gösterileceğini seçin',
        'access_control' => 'Bu menüyü hangi kullanıcı rollerinin görebileceğini belirtin',
        'url' => 'Dahili linkler için /başlangıç formatını kullanın, harici linkler için https:// ile başlayın',
        'order' => 'Menünün sıralamasını belirler (düşük değerler üstte görünür)',
        'icon' => 'Bootstrap ikon sınıf adını girin (örn: bi-house)',
    ],

    // === Placeholders ===
    'placeholders' => [
        'menu_name' => 'Menü adını yazın',
        'menu_url' => '/ornek-baglanti veya https://example.com',
        'menu_file' => 'menu-parcasi.twig',
        'menu_html' => 'Özel HTML içeriği',
        'icon_class' => 'bi-house',
    ],

    // === Empty States ===
    'empty' => [
        'no_menus' => 'Henüz menü eklenmemiş',
        'no_results' => 'Arama kriterlerinize uygun menü bulunamadı',
        'add_first' => 'İlk menüyü eklemek için butona tıklayın',
    ],

    // === Loading States ===
    'loading' => [
        'saving' => 'Kaydediliyor...',
        'loading' => 'Yükleniyor...',
        'processing' => 'İşleniyor...',
    ],

    // === Success Messages ===
    'success' => [
        'operation_completed' => 'İşlem başarıyla tamamlandı!',
        'changes_saved' => 'Değişiklikler kaydedildi!',
    ],

    // === Error Messages ===
    'errors' => [
        'general' => 'Bir hata oluştu. Lütfen tekrar deneyin.',
        'network' => 'Bağlantı hatası. Lütfen internet bağlantınızı kontrol edin.',
        'server' => 'Sunucu hatası. Lütfen daha sonra tekrar deneyin.',
        'record' => 'Kayıt bulunamadı!',
        'parent_selection_required' => 'Bağlanacağı menüyü seçmelisiniz.',
        'parent_language_mismatch' => 'Üst menünün dili, menü diliyle aynı olmalıdır.',
    ],

    // === ACL Roles ===
    'roll' => [
        'ad' => 'Menü Yönetim Rolleri',
        'add' => 'Menü Ekleyebilir',
        'edit' => 'Menü Düzenleyebilir',
        'delete' => 'Menü Silebilir',
        'list' => 'Menü Yönetimini Görüntüleyebilir',
        'moderate_edit' => 'Başkasına Ait Menüleri Düzenleyebilir',
        'moderate_delete' => 'Başkasına Ait Menüleri Silebilir',
    ],

    // === ACL Error Messages ===
    'role_errors' => [
        'add' => 'Menü Ekleme Permissionniz Yok',
        'edit' => 'Menü Düzenleme Permissionniz Yok',
        'delete' => 'Menü Silme Permissionniz Yok',
        'list' => 'Menü Yönetimini Görüntüleme Permissionniz Yok',
        'moderate_edit' => 'Başkasına Ait Menüleri Düzenleme Permissionniz Yok',
        'moderate_delete' => 'Başkasına Ait Menüleri Silme Permissionniz Yok',
    ],

];
$lang['mod_menu']['slots'] = [
    'header' => 'Üst Menü',
    'footer' => 'Alt Menü',
];
$lang['mod_menu']['parent_labels'] = [
    'select_parent' => 'Üst menüyü seçin',
    'language_root' => ':language altında üst seviye',
    'system_root' => 'Sistem kökü',
];
