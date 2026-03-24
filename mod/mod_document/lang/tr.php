<?php
/**
 * Advanced File Manager Module - Turkish Language File
 *
 * @package AKM Business CMS
 * @module  mod_document
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Advanced File Manager Module Language Translations
 */
$lang['mod_document'] = [

    // === Page Titles ===
    'list' => 'Dosya Yönetimi',

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Dosya Ekle',
        'edit' => 'İçeriği Düzenle',
        'list' => 'Dosya Listesi',
        'delete' => 'Dosyayı Sil',
        'cancel' => 'İptal',
        'save' => 'Kaydet',
        'update' => 'Güncelle',
        'remove' => 'Kaldır',
    ],

    // === System Messages ===
    'message' => [
        'success' => [
            'added' => 'Dosya başarıyla eklendi!',
            'updated' => 'Dosya başarıyla güncellendi!',
            'deleted' => 'Dosya başarıyla silindi!',
            'image_removed' => 'Resim başarıyla kaldırıldı!',
            'add' => 'Dosya Eklendi!',
            'edit' => 'Dosya Düzenlendi!',
        ],
        'error' => [
            'not_added' => 'Dosya eklenemedi!',
            'not_updated' => 'Dosya güncellenemedi!',
            'not_deleted' => 'Dosya silinemedi!',
            'not_found' => 'Belirtilen dosya kaydı bulunamadı!',
            'add' => 'Dosya Eklenemedi!',
            'edit' => 'Dosya Düzenlenemedi!',
        ],
        'information' => [
            'moderate' => 'Düzenlemek istediğiniz içeriği iki defa tıklayın',
            'move' => 'Sırasını değiştirmek istediğiniz içeriği sürükleyip bırakın',
        ],
    ],

    // === Status Types ===
    'status' => [
        'published' => 'Yayınlandı',
        'draft' => 'Taslak',
        'archived' => 'Arşivlendi',
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Evet',
        'no' => 'Hayır',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'remove_image' => 'Bu resmi kaldırmak istediğinize emin misiniz?',
        'remove_record' => 'Bu kaydı silmek istediğinize emin misiniz?',
        'delete_single' => 'Bu dosyayı silmek istediğinize emin misiniz?',
        'delete_multiple' => 'Seçili :count dosyayı silmek istediğinize emin misiniz?',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Ekle',
        'edit' => 'Düzenle',
        'refresh' => 'Yenile',
        'delete' => 'Sil',
        'gallery' => 'Resim Galerisi',
        'file_manager' => 'Dosya Yöneticisi',
        'cancel' => 'Vazgeç',
        'remove' => 'Kaldır',
        'yes' => 'Evet',
        'no' => 'Hayır',
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
        'pagination' => '{total} sonuçtan {from} ile {to} arasındaki kayıtlar gösteriliyor',
        'page' => 'Sayfa',
        'page_count' => 'Sayfa Sayısı',
        'loading' => 'Veriler yükleniyor, lütfen bekleyin...',
        'no_data' => 'Veri bulunamadı',
        'connection_error' => 'Bağlantı Hatası',
        'search' => 'Arama',
        'select_at_least_one' => 'Lütfen en az bir satır seçin',
        'select_only_one' => 'Lütfen sadece bir satır seçin',
        0 => 'Toplam {total} Sonuçtan {from} den {to}\'a Kadar Olanlar Görüntüleniyor.',
        1 => 'Sayfa',
        2 => 'Sayfa Sayısı',
        3 => 'Veriler Yükleniyor, Lütfen Bekleyin ...',
        4 => 'Hiçbir Veri Bulunamadı',
        5 => 'Bağlantı Hatası',
        6 => 'Arama',
        7 => 'Lütfen En Az Bir Satır Seçin',
        8 => 'Lütfen Sadece Bir Satır Seçin',
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Lütfen %s alanını boş bırakmayın',
        'length' => '%s en az %s en fazla %s karakterden oluşabilir',
        'email' => '%s alanına lütfen geçerli bir e-posta adresi yazın',
        'unique' => 'Girdiğiniz %s sistemde kayıtlı. Lütfen farklı bir %s belirtin!',
        'inarray' => '%s alanı sadece \'%s\' değerleri alabilir',
        'required' => 'Lütfen %s Alanını Boş Bırakmayın',
        'length' => '%s En Kısa %s En Uzun %s Karekterden Oluşabilir',
        'email' => '%s Alanına Lütfen Geçerli Bir E-Posta Adresi Yazın.',
        'unique' => 'Yazmış Olduğunuz %s Sistemde Kayıtlı.Lütfen Başka Bir %s Belirleyin!',
        'inarray' => '%s Alanı Sadece \' %s \' değerleri alabilir',
    ],

    // === Access Control (ACL) ===
    'roll' => [
        'title' => 'Dosya Yönetim Rolleri',
        'add' => 'Dosya Ekleyebilir',
        'edit' => 'Dosya Düzenleyebilir',
        'delete' => 'Dosya Silebilir',
        'list' => 'Dosya Yönetimini Görüntüleyebilir',
        'attach_files' => 'İçeriğe dosya ekleyebilir mi?',
        'edit_others' => 'Başkasına ait dosya kayıtlarını düzenleyebilir',
        'delete_others' => 'Başkasına ait dosya kayıtlarını silebilir',
        'ad' => 'Dosya Yönetim Rolleri',
        'add' => 'Dosya Ekleyebilir',
        'edit' => 'Dosya Düzenleyebilir',
        'delete' => 'Dosya Silebilir',
        'list' => 'Dosya Yönetimini Görüntüleyebilir',
        'file' => 'İçeğe Dosya Ekleyebilirmi?',
        'moderate_edit' => 'Kendisine Ait Olmayan Dosya Kaydını Düzenleyebilir',
        'moderate_delete' => 'Kendisine Ait Olmayan Dosya Kaydını Düzenleyebilir',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'edit_others' => 'Başkasına ait dosya kayıtlarını düzenleyemezsiniz',
        'delete_others' => 'Başkasına ait dosya kayıtlarını silemezsiniz',
        'add' => 'Dosya ekleme permissionniz yok',
        'edit' => 'Dosya düzenleme permissionniz yok',
        'list' => 'Dosya yönetimini görüntüleme permissionniz yok',
        'delete' => 'Dosya silme permissionniz yok',
        'attach' => 'İçeriğe dosya ekleme permissionniz yok',
        'moderate_edit' => 'Size Ait Olmayan Dosya Kaydını Düzenleyemezsiniz',
        'moderate_delete' => 'Size Ait Olmayan Dosya Kaydını Silemezsiniz',
        'add' => 'Dosya Eklemeye Permissionniz Yok',
        'edit' => 'Dosya Düzenlemeye Permissionniz Yok',
        'list' => 'Dosya Yönetimini Görüntülemeye Permissionniz Yok',
        'delete' => 'Dosya Silmeye Permissionniz Yok',
        'file' => 'İçeriğe Dosya Eklemeye Permissionniz Yok',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Dil',
            'name' => 'Dosya Kategori Adı',
            'seo' => 'Dosya Kategori URL',
            'published' => 'Yayınlansın mı?',
            'image' => 'Resim',
            'title' => 'Başlık (Arama Motorları İçin)',
            'keywords' => 'Anahtar Kelimeler (Arama Motorları İçin)',
            'description' => 'Açıklama (Arama Motorları İçin)',
            'menu' => 'Dosyayı Menüye Gönder',
            'id' => 'ID',
            'language' => 'Dil',
            'ad' => 'Dosya Kategori Adı',
            'seo' => 'Dosya Kategori Bağlantısı',
            'status' => 'Yayınlansın mı?',
            'image' => 'Resim',
            'title' => 'Title ( Arama Motorları İçin)',
            'keywords' => 'Keywords ( Arama Motorları İçin)',
            'aciklama' => 'Açıklama ( Arama Motorları İçin)',
            'menu' => 'Dosyayı Menüye Gönder',
        ],
        'radio' => [
            'published_yes' => 'Evet',
            'published_no' => 'Hayır',
            'publish_yes' => 'Evet',
            'publish_no' => 'Hayır',
        ],
    ],

    // === Additional Legacy Keys ===
    'information' => 'Bilgi!',
    'oke' => 'İşlem Başarılı!',
    'hata' => 'Hata Oluştu!',
    'error' => 'Ölümcül Hata Oluştu!',
    'remove_image_oke' => 'Resim Kaldırıldı',
    'choose_wish' => 'Dilerseniz Seçin',
    'remove_record' => 'Bu Kaydı Silmek İstediğinize Eminmisiniz?',
    'errors' => [
        'record' => 'Belirttiğiniz Kayıt Bulunamadı!',
    ],

];