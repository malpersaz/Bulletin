<?php
/**
 * Category Module - Turkish Language File
 *
 * @package AKM Business
 * @module  mod_news_category
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Category Module Language Translations
 */
$lang['mod_news_category'] = [

    // === Basic Navigation ===
    'list' => 'Kategori Listesi',
    'add' => 'Kategori Ekle',
    'edit' => 'Kategoriyi Düzenle',
    'information' => 'Bilgilendirme',
    'oke' => 'İşlem Başarılı',
    'hata' => 'Hata Oluştu',
    'error' => 'Kritik Hata Oluştu',
    'remove_record' => 'Bu kaydı silmek istediğinizden emin misiniz?',
    'choose_wish' => 'Seçiniz',

    // === Message Structure ===
    'message' => [
        'oke' => [
            'add' => 'Kategori başarıyla eklendi!',
            'edit' => 'Kategori başarıyla güncellendi!',
        ],
        'error' => [
            'add' => 'Kategori eklenemedi!',
            'edit' => 'Kategori güncellenemedi!',
        ],
        'information' => [
            'moderate' => 'Düzenlemek istediğiniz kategoriye çift tıklayın',
            'move' => 'Sırasını değiştirmek istediğiniz kategoriyi sürükleyip bırakın',
        ],
    ],

    'errors' => [
        'record' => 'Belirtilen kayıt bulunamadı!',
        'parent_selection_required' => 'Lütfen bir üst kategori seçin.',
        'parent_language_mismatch' => 'Üst kategori dili, kategori diliyle aynı olmalıdır.',
        'parent_cycle' => 'Bir kategori kendisinin veya alt kategorilerinden birinin altına taşınamaz.',
        'menu_parent_not_found' => 'Seçilen menü üst kaydı bulunamadı.',
        'menu_language_mismatch' => 'Menü üst kaydının dili, kategori diliyle aynı olmalıdır.',
        'order_update_failed' => 'Kategori sırası güncellenemedi.',
    ],

    'parent_labels' => [
        'system_root' => 'Sistem kökü',
        'language_root' => ':language kökü',
    ],

    'button' => [
        'add' => 'Ekle',
        'edit' => 'Düzenle',
        'yenile' => 'Yenile',
        'delete' => 'Sil',
        'vazgec' => 'Vazgeç',
        'kaldir' => 'Kaldır',
        'evet' => 'Evet',
        'hayir' => 'Hayır',
        'save' => 'Kaydet',
        'remove' => 'Kaldır',
    ],

    'grid' => [
        0 => '{total} sonuçtan {from} - {to} arası gösteriliyor',
        1 => 'Sayfa',
        2 => 'Sayfa Sayısı',
        3 => 'Veriler yükleniyor, lütfen bekleyin...',
        4 => 'Veri bulunamadı',
        5 => 'Bağlantı Hatası',
        6 => 'Ara',
        7 => 'Lütfen en az bir satır seçin',
        8 => 'Lütfen sadece bir satır seçin',
    ],

    'validator' => [
        'required' => 'Lütfen %s alanını boş bırakmayın',
        'length' => '%s alanı %s ve %s karakter arasında olmalıdır',
        'email' => '%s alanına geçerli bir e-posta adresi girin',
        'unique' => 'Girdiğiniz %s sistemde kayıtlı. Lütfen farklı bir %s belirtin!',
        'inarray' => '%s alanı sadece \' %s \' değerlerini kabul edebilir',
    ],

    'roll' => [
        'ad' => 'Kategori Yönetimi Rolleri',
        'add' => 'Kategori Ekleyebilir',
        'edit' => 'Kategori Düzenleyebilir',
        'delete' => 'Kategori Silebilir',
        'list' => 'Kategori Yönetimini Görüntüleyebilir',
        'moderate_edit' => 'Başkasına ait kategori kayıtlarını düzenleyebilir',
        'moderate_delete' => 'Başkasına ait kategori kayıtlarını silebilir',
    ],

    'role_errors' => [
        'moderate_edit' => 'Başkasına ait kategori kayıtlarını düzenleyemezsiniz',
        'moderate_delete' => 'Başkasına ait kategori kayıtlarını silemezsiniz',
        'add' => 'Kategori ekleme permissionniz bulunmuyor',
        'edit' => 'Kategori düzenleme permissionniz bulunmuyor',
        'list' => 'Kategori Yönetimini görüntüleme permissionniz bulunmuyor',
        'delete' => 'Kategori silme permissionniz bulunmuyor',
    ],

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Kategori Ekle',
        'edit' => 'Kategoriyi Düzenle',
        'list' => 'Kategori Listesi',
        'delete' => 'Kategoriyi Sil',
        'cancel' => 'İptal',
        'save' => 'Kaydet',
        'update' => 'Güncelle',
        'remove' => 'Kaldır',
        'unlock_reorder' => 'Sıralama Modunu Aç',
        'lock_reorder' => 'Sıralama Modunu Kapat',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Kategori başarıyla eklendi!',
            'updated' => 'Kategori başarıyla güncellendi!',
            'deleted' => 'Kategori başarıyla silindi!',
            'reordered' => 'Kategori sırası güncellendi!',
            'image_removed' => 'Görsel başarıyla kaldırıldı!',
        ],
        'error' => [
            'not_added' => 'Kategori eklenemedi!',
            'not_updated' => 'Kategori güncellenemedi!',
            'not_deleted' => 'Kategori silinemedi!',
            'not_found' => 'Belirtilen kategori kaydı bulunamadı!',
            'image_not_removed' => 'Görsel kaldırılamadı!',
            'validation' => 'Lütfen gerekli alanları doğru şekilde doldurun!',
        ],
        'info' => [
            'edit_double_click' => 'Düzenlemek istediğiniz kategoriye çift tıklayın',
            'drag_reorder' => 'Sıralamayı değiştirmek için kategoriyi sürükleyip bırakın',
            'mode_unlocked' => 'Mod: Sıralama (Açık)',
        ],
        'warning' => [
            'reorder' => 'Kategori sıralamasını değiştirmek istediğinize emin misiniz?',
        ],
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Evet',
        'no' => 'Hayır',
    ],

    // === Form Labels ===
    'form' => [
        'basic_info' => 'Temel Bilgiler',
        'seo_settings' => 'SEO Ayarları',
        'display_settings' => 'Görüntüleme Ayarları',
        'advanced_settings' => 'Gelişmiş Ayarlar',

        'id' => 'ID',
        'name' => 'Kategori Adı',
        'parent_category' => 'Üst Kategori',
        'order' => 'Sıra',
        'language' => 'Görüntüleme Dili',
        'is_root' => 'Ana Kategori (Görüntülenmez)',
        'seo_url' => 'SEO URL',
        'meta_title' => 'Meta Başlık (Arama Motorları İçin)',
        'meta_keywords' => 'Meta Anahtar Kelimeler (Arama Motorları İçin)',
        'meta_description' => 'Meta Açıklama (Arama Motorları İçin)',
        'add_to_menu' => 'Kategoriyi Menüye Ekle',
        'image' => 'Kategori Görseli',
        'description' => 'Kategori Açıklaması',
        'access_control' => 'Erişim Kontrolü',
    ],

    // === Table Columns ===
    'columns' => [
        'id' => 'ID',
        'language' => 'Görüntüleme Dili',
        'name' => 'Kategori Adı',
        'parent_category' => 'Üst Kategori',
        'order' => 'Sıra',
        'root_category' => 'Ana Kategori',
        'seo_url' => 'SEO URL',
        'meta_title' => 'Meta Başlık',
        'meta_keywords' => 'Meta Anahtar Kelimeler',
        'meta_description' => 'Meta Açıklama',
        'add_to_menu' => 'Menüye Ekle',
        'image' => 'Görsel',
        'status' => 'Durum',
        'actions' => 'İşlemler',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Yeni Kategori Ekle',
        'edit' => 'Seçileni Düzenle',
        'delete' => 'Seçilenleri Sil',
        'refresh' => 'Yenile',
        'add' => 'Ekle',
        'edit' => 'Düzenle',
        'delete' => 'Sil',
        'cancel' => 'İptal',
        'remove' => 'Kaldır',
        'yes' => 'Evet',
        'no' => 'Hayır',
    ],

    // === Validation Messages ===
    'validation' => [
        'required' => 'Lütfen %s alanını boş bırakmayın',
        'length' => '%s alanı %s ve %s karakter arasında olmalıdır',
        'email' => '%s alanına geçerli bir e-posta adresi girin',
        'unique' => 'Girdiğiniz %s sistemde kayıtlı. Lütfen farklı bir %s belirtin!',
        'inarray' => '%s alanı sadece \' %s \' değerlerini kabul edebilir',
        'url' => 'Lütfen geçerli bir URL girin',
        'slug' => 'Lütfen geçerli bir URL slug girin',
    ],

    // === Access Control (ACL) ===
    'permissions' => [
        'title' => 'Kategori Yönetimi İzinleri',
        'add' => 'Kategori Ekleyebilir',
        'edit' => 'Kategori Düzenleyebilir',
        'delete' => 'Kategori Silebilir',
        'list' => 'Kategori Yönetimini Görüntüleyebilir',
        'edit_others' => 'Başkasına ait kategori kayıtlarını düzenleyebilir',
        'delete_others' => 'Başkasına ait kategori kayıtlarını silebilir',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'edit_others' => 'Başkasına ait kategori kayıtlarını düzenleyemezsiniz',
        'delete_others' => 'Başkasına ait kategori kayıtlarını silemezsiniz',
        'add' => 'Kategori ekleme permissionniz bulunmuyor',
        'edit' => 'Kategori düzenleme permissionniz bulunmuyor',
        'list' => 'Kategori yönetimini görüntüleme permissionniz bulunmuyor',
        'delete' => 'Kategori silme permissionniz bulunmuyor',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Görüntüleme Dili',
            'name' => 'Kategori Adı',
            'parent_id' => 'Üst Kategori',
            'order' => 'Sıra',
            'root' => 'Ana Kategori (Görüntülenmez)',
            'seo' => 'Kategori URL',
            'title' => 'Başlık (Arama Motorları İçin)',
            'keywords' => 'Anahtar Kelimeler (Arama Motorları İçin)',
            'description' => 'SEO ayarları için açıklama (Description)',
            'menu' => 'Kategoriyi Menüye Gönder',
            'image' => 'Görsel',
            'image_select' => 'Görsel seç',
            'acl_id' => 'Erişim Permissionsi',
        ],
        'select_values' => [
            'root_yes' => 'Evet',
            'root_no' => 'Hayır',
        ],
        'tooltips' => [
            'acl_settings' => 'ACL ayarları',
        ],
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'remove_image' => 'Bu görseli kaldırmak istediğinizden emin misiniz?',
        'remove_record' => 'Bu kaydı silmek istediğinizden emin misiniz?',
        'delete_single' => 'Bu kategoriyi silmek istediğinizden emin misiniz?',
        'delete_multiple' => 'Seçilen :count kategoriyi silmek istediğinizden emin misiniz?',
        'make_root' => 'Bunu ana kategori yapmak istediğinizden emin misiniz?',
        'remove_root' => 'Ana kategori durumunu kaldırmak istediğinizden emin misiniz?',
        'reorder_single' => 'Bu kategoriyi yeni yerine taşımak istiyor musunuz?',
    ],

    // === Success Messages ===
    'success_messages' => [
        'operation_completed' => 'İşlem başarıyla tamamlandı!',
        'changes_saved' => 'Değişiklikler kaydedildi!',
        'category_published' => 'Kategori başarıyla yayınlandı!',
        'category_unpublished' => 'Kategorinin yayını kaldırıldı!',
    ],

    // === Error Messages ===
    'error_messages' => [
        'general' => 'Bir hata oluştu. Lütfen tekrar deneyin.',
        'network' => 'Ağ hatası. İnternet bağlantınızı kontrol edin.',
        'server' => 'Sunucu hatası. Lütfen daha sonra tekrar deneyin.',
        'fatal' => 'Kritik bir hata oluştu!',
        'has_subcategories' => 'Alt kategorileri olan kategori silinemez',
        'has_content' => 'İçerik içeren kategori silinemez',
    ],

    // === Helper Text ===
    'help' => [
        'category_name' => 'Bu kategori için açıklayıcı bir ad girin',
        'parent_category' => 'Üst kategoriyi seçin (üst seviye için boş bırakın)',
        'order' => 'Görüntüleme sırasını belirler (düşük değerler önce gelir)',
        'root_category' => 'Ana kategoriler organizasyonel amaçlıdır ve herkese açık görüntülenmez',
        'seo_url' => 'Kategori adının URL uyumlu versiyonu (linklerde kullanılır)',
        'meta_title' => 'Arama motoru sonuçlarında gösterilecek başlık',
        'meta_description' => 'Arama motoru sonuçlarında gösterilecek açıklama',
        'add_to_menu' => 'Bu kategoriyi otomatik olarak navigasyon menüsüne ekle',
        'access_control' => 'Hangi kullanıcı rollerinin bu kategoriye erişebileceğini belirtin',
    ],

    // === Placeholders ===
    'placeholders' => [
        'category_name' => 'Kategori adını girin...',
        'seo_url' => 'kategori-url-slug',
        'meta_title' => 'Meta başlığı girin...',
        'meta_description' => 'Meta açıklamasını girin...',
        'meta_keywords' => 'anahtar1, anahtar2, anahtar3',
        'category_description' => 'Kategori açıklamasını girin (isteğe bağlı)...',
    ],

    // === Empty States ===
    'empty' => [
        'no_categories' => 'Henüz hiç kategori oluşturulmadı',
        'no_results' => 'Arama kriterlerinize uygun kategori bulunamadı',
        'add_first' => 'İlk kategoriyi oluşturmak için düğmeye tıklayın',
        'no_subcategories' => 'Alt kategori bulunamadı',
        'no_content' => 'Bu kategoride henüz içerik yok',
    ],

    // === Loading States ===
    'loading' => [
        'saving' => 'Kaydediliyor...',
        'loading' => 'Yükleniyor...',
        'processing' => 'İşleniyor...',
        'deleting' => 'Siliniyor...',
    ],

    // === Category Tree View ===
    'tree_view' => [
        'expand_all' => 'Tümünü Genişlet',
        'collapse_all' => 'Tümünü Daralt',
        'show_hierarchy' => 'Hiyerarşiyi Göster',
        'show_flat' => 'Düz Görünüm',
        'root_only' => 'Sadece Ana Kategoriler',
        'leaf_only' => 'Sadece Alt Kategoriler',
    ],

    // === SEO Features ===
    'seo' => [
        'auto_generate_slug' => 'URL slug otomatik oluştur',
        'preview_snippet' => 'Arama Sonucunu Önizle',
        'character_count' => 'Karakter sayısı: :count',
        'recommended_length' => 'Önerilen: :min-:max karakter',
        'good' => 'İyi',
        'too_short' => 'Çok kısa',
        'too_long' => 'Çok uzun',
    ],

    // === Bulk Operations ===
    'bulk_operations' => [
        'bulk_edit' => 'Toplu Düzenle',
        'bulk_delete' => 'Toplu Sil',
        'bulk_move' => 'Kategoriye Taşı',
        'bulk_publish' => 'Seçilenleri Yayınla',
        'bulk_unpublish' => 'Seçilenlerin Yayınını Kaldır',
        'select_all' => 'Tümünü Seç',
        'deselect_all' => 'Tümünün Seçimini Kaldır',
    ],

    // === Response Codes (for AJAX/PHP responses) ===
    'responses' => [
        'success' => 'Oke',  // Keep 'Oke' as it's used in JavaScript comparisons
        'error' => 'Error',  // Keep 'Error' as it's used in JavaScript comparisons
        'fatal_error' => 'ERROR',  // Keep 'ERROR' as it's used in JavaScript comparisons
    ],

];
