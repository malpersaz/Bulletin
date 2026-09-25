<?php
/**
 * Popup/Modal Management Module - Turkish Language File
 *
 * @package AKM Business
 * @module  mod_popup
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Popup/Modal Management Module Language Translations
 */
$lang['mod_popup'] = [

    // === Basic Actions ===
    'add' => 'Popup Ekle',
    'edit' => 'Popup Düzenle',
    'list' => 'Popup Listesi',

    // === System Messages ===
    'success' => 'Operasyon Başarılı!',
    'error' => 'Hata Oluştu!',
    'information' => 'Bilgi!',

    // === Image Management ===
    'remove_image' => 'Bu resmi kaldırmak istediğinizden emin misiniz?',
    'remove_image_success' => 'Resim kaldırıldı',
    'choose_wish' => 'İsterseniz seçin',
    'remove_record' => 'Bu kaydı silmek istediğinizden emin misiniz?',

    // === Messages ===
    'message' => [
        'success' => [
            'add' => 'Popup başarıyla eklendi!',
            'edit' => 'Popup başarıyla güncellendi!',
        ],
        'error' => [
            'add' => 'Popup eklenemedi!',
            'edit' => 'Popup güncellenemedi!',
        ],
        'information' => [
            'moderate' => 'İçeriği düzenlemek için iki kez tıklayın',
            'move' => 'İçeriğin sırasını değiştirmek için sürükleyip bırakın',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'Belirttiğiniz kayıt bulunamadı!',
    ],

    // === Buttons ===
    'button' => [
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
        'showing_results' => 'Toplam {total} sonuçtan {from}\'dan {to}\'a kadar olanlar görüntüleniyor.',
        'page' => 'Sayfa',
        'page_count' => 'Sayfa Sayısı',
        'loading' => 'Veriler yükleniyor, lütfen bekleyin...',
        'no_data' => 'Veri bulunamadı',
        'connection_error' => 'Bağlantı Hatası',
        'search' => 'Ara',
        'select_at_least_one' => 'Lütfen en az bir satır seçin',
        'select_only_one' => 'Lütfen sadece bir satır seçin',
    'publish' => [
            'key' => 'Yayınla',
            'value' => 'Bu popupu yayınlamak istediğinizden emin misiniz?',
        ],
        'unpublish' => [
            'key' => 'Yayından Al',
            'value' => 'Bu popupu yayından kaldırmak istediğinizden emin misiniz?',
        ],
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Lütfen %s alanını boş bırakmayın',
        'length' => '%s alanı %s ile %s karakter arasında olmalıdır',
        'email' => 'Lütfen %s alanına geçerli bir e-posta adresi girin',
        'unique' => 'Girdiğiniz %s sistemde kayıtlı. Lütfen farklı bir %s belirtin!',
        'inarray' => '%s alanı sadece \"%s\" değerlerini kabul edebilir',
    ],

    // === Permission Roles ===
    'role' => [
        'name' => 'Popup Yönetim İzinleri',
        'add' => 'Popup Ekleyebilir',
        'edit' => 'Popup Düzenleyebilir',
        'delete' => 'Popup Silebilir',
        'list' => 'Popup Yönetimini Görüntüleyebilir',
        'moderate_edit' => 'Başkalarına ait popup kayıtlarını düzenleyebilir',
        'moderate_delete' => 'Başkalarına ait popup kayıtlarını silebilir',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'moderate_edit' => 'Başkalarına ait popup kayıtlarını düzenleyemezsiniz',
        'moderate_delete' => 'Başkalarına ait popup kayıtlarını silemezsiniz',
        'add' => 'Popup eklemeye yetkiniz yok',
        'edit' => 'Popup düzenlemeye yetkiniz yok',
        'list' => 'Popup yönetimini görüntülemeye yetkiniz yok',
        'delete' => 'Popup silmeye yetkiniz yok',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Dil',
            'name' => 'Popup Adı',
            'start_date' => 'Yayın Başlangıç',
            'end_date' => 'Yayın Bitiş',
            'status' => 'Aktif mi?',
            'content' => 'Açıklama',
            'sure' => 'Otomatik Kapanma (saniye)',
            'video' => 'Video Tipi',
            'link' => 'Resim Linki veya Video URL',
            'image' => 'Resim',
        ],
        'radio' => [
            'publish_yes' => 'Evet',
            'publish_no' => 'Hayır',
        ],
        'select_value' => [
            'video_url' => 'Diğer',
            'video_youtube' => 'YouTube',
            'video_vimeo' => 'Vimeo',
            'video_instagram' => 'Instagram',
        ],
    ],

];