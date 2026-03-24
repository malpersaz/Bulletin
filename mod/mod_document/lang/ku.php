<?php
/**
 * Advanced File Manager Module - Kurdish Language File
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
    'list' => 'Rêveberîya Pelan',

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Dosya Zêde Bike',
        'edit' => 'İçeriği Biguhere',
        'list' => 'Lîsteya Pelan',
        'delete' => 'Dosyayê Jê Bibe',
        'cancel' => 'Betal',
        'save' => 'Tomar Bike',
        'update' => 'Rojane Bike',
        'remove' => 'Rake',
    ],

    // === System Messages ===
    'message' => [
        'success' => [
            'added' => 'Dosya bi serkeftî hat zêdekirin!',
            'updated' => 'Dosya bi serkeftî hat rojanekirin!',
            'deleted' => 'Dosya bi serkeftî hat jêbirin!',
            'image_removed' => 'Wêne bi serkeftî hat rakirin!',
            'add' => 'Dosya Zêde Bikendi!',
            'edit' => 'Dosya Biguherendi!',
        ],
        'error' => [
            'not_added' => 'Dosya nehat zêdekirin!',
            'not_updated' => 'Dosya nehat rojanekirin!',
            'not_deleted' => 'Dosya nehat jêbirin!',
            'not_found' => 'Pelê ku texîs kirî nehat dîtin!',
            'add' => 'Dosya Zêde Bikenemedi!',
            'edit' => 'Dosya Biguherenemedi!',
        ],
        'information' => [
            'moderate' => 'Ji bo sererastkirina naverokê ku dixwazin du caran bitikînin',
            'move' => 'Naverokê ku dixwazin rêzê wê biguherînin bikşînin û berdin',
        ],
    ],

    // === Status Types ===
    'status' => [
        'published' => 'Hate Weşandin',
        'draft' => 'Mışorî',
        'archived' => 'Arşîvkirî',
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Erê',
        'no' => 'Na',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'remove_image' => 'Ma tu bawer î, ku dixwazî vî wêneyî jêbibî?',
        'remove_record' => 'Ma tu bawer î, ku dixwazî vê tomariyê jêbibî?',
        'delete_single' => 'Ma tu bawer î, ku dixwazî vê pelê jêbibî?',
        'delete_multiple' => 'Ma tu bawer î, ku dixwazî pela :count a hilbijartî jêbibî?',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Zêde Bike',
        'edit' => 'Biguhere',
        'refresh' => 'Nû Bike',
        'delete' => 'Jê Bibe',
        'gallery' => 'Wêne Galerisi',
        'file_manager' => 'Pel Yöneticisi',
        'cancel' => 'Betal',
        'remove' => 'Rake',
        'yes' => 'Erê',
        'no' => 'Na',
        'add' => 'Zêde Bike',
        'edit' => 'Biguhere',
        'yenile' => 'Nû Bike',
        'delete' => 'Jê Bibe',
        'gallery' => 'Wêne Galerisi',
        'file' => 'Pel Yöneticisi',
        'vazgec' => 'Betal',
        'kaldir' => 'Kaldır',
        'evet' => 'Erê',
        'hayir' => 'Na',
    ],

    // === Data Grid Messages ===
    'grid' => [
        'pagination' => 'Ji {total} encamên {from} heta {to} tên nîşandan',
        'page' => 'Rûpel',
        'page_count' => 'Hejmara Rûpelan',
        'loading' => 'Daney tên barkirin, ji kerema xwe re benda xwe bin...',
        'no_data' => 'Daneyê nehat dîtin',
        'connection_error' => 'Çewtiya Girêdanê',
        'search' => 'Lêbigerin',
        'select_at_least_one' => 'Ji kerema xwe re herî kêm yek rêzik hilbijêrin',
        'select_only_one' => 'Ji kerema xwe re tenê yek rêzik hilbijêrin',
        0 => 'Toplam {total} Sonuçtan {from} den {to}\'a Kadar Olanlar Görüntüleniyor.',
        1 => 'Rûpel',
        2 => 'Rûpel Sayısı',
        3 => 'Veriler Yükleniyor, Lütfen BZêde Bikeyin ...',
        4 => 'Hiçbir Veri Ne Hate Dîtin',
        5 => 'Bağlantı Çewtîsı',
        6 => 'Lêbigerema',
        7 => 'Lütfen En Az Bir Satır Hilbijêre',
        8 => 'Lütfen Sadece Bir Satır Hilbijêre',
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Ji kerema xwe re qada %s vala nekin',
        'length' => '%s divê di navbera %s û %s karakteran de be',
        'email' => 'Ji kerema xwe re di qada %s de navnîşana e-postaya derbasdar binivîsin',
        'unique' => '%s ku nivîsandî di pergalê de hate tomar. Ji kerema xwe re %yeke din hilbijêrin!',
        'inarray' => 'Qada %s tenê nirxên \'%s\' dipejirîne',
        'required' => 'Ji kerema xwe re %s Alanını Boş Bırakmayın',
        'length' => '%s En Kısa %s En Uzun %s Karekterden Oluşabilir',
        'email' => '%s Alanına Lütfen Geçerli Bir E-Posta Navresi Yazın.',
        'unique' => 'Yazmış Olduğunuz %s Sistemde Kayıtlı.Ji kerema xwe re Başka Bir %s Belirleyin!',
        'inarray' => '%s Qadaı Sadece \' %s \' değerleri alabilir',
    ],

    // === Access Control (ACL) ===
    'roll' => [
        'title' => 'Roleyên Rêveberîya Pelan',
        'add' => 'Dikare Pelan Zêde Bike',
        'edit' => 'Dikare Pelan Biguhere',
        'delete' => 'Dikare Pelan Jê Bibe',
        'list' => 'Dikare Rêveberîya Pelan Bibîne',
        'attach_files' => 'Dikare pelên naverokê bike?',
        'edit_others' => 'Dikare tomarên pelên yên yên din Biguhere',
        'delete_others' => 'Dikare tomarên pelên yên yên din jê bibe',
        'ad' => 'Dosya Rêveberî Rolleri',
        'add' => 'Dosya Zêde Bikeyebilir',
        'edit' => 'Dosya Biguhereyebilir',
        'delete' => 'Dosya Jê Bibeebilir',
        'list' => 'Dosya Rêveberîni Görüntüleyebilir',
        'file' => 'İçeğe Dosya Zêde Bikeyebilirmi?',
        'moderate_edit' => 'Kendisine Ait Olmayan Dosya Kaydını Biguhereyebilir',
        'moderate_delete' => 'Kendisine Ait Olmayan Dosya Kaydını Biguhereyabilir',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'edit_others' => 'Nikaribin tomarên pelên yên yên din sererast bikin',
        'delete_others' => 'Nikaribin tomarên pelên yên yên din jê bibin',
        'add' => 'Destûra zêdekirina pelan tune',
        'edit' => 'Destûra sererastkirina pelan tune',
        'list' => 'Destûra dîtina rêveberîya pelan tune',
        'delete' => 'Destûra jêbirina pelan tune',
        'attach' => 'Destûra zêdekirina pelan naverokê tune',
        'moderate_edit' => 'Size Ait Olmayan Dosya Kaydını Biguhereyemezsiniz',
        'moderate_delete' => 'Size Ait Olmayan Dosya Kaydını Jê Bibeemezsiniz',
        'add' => 'Dosya Zêde Bikemeye Permissionniz Yok',
        'edit' => 'Dosya Biguheremeye Permissionniz Yok',
        'list' => 'Dosya Rêveberîni Görüntülemeye Permissionniz Yok',
        'delete' => 'Dosya Jê Bibemeye Permissionniz Yok',
        'file' => 'İçeriğe Dosya Zêde Bikemeye Permissionniz Yok',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Ziman',
            'name' => 'Navê Kategorîya Pelan',
            'seo' => 'URLya Kategorîya Pelan',
            'published' => 'Were Weşandin?',
            'image' => 'Wêne',
            'title' => 'Sernav (Ji Bo Motora Lêgerînê)',
            'keywords' => 'Nimûneyên Calak (Ji Bo Motora Lêgerînê)',
            'description' => 'Rave (Ji Bo Motora Lêgerînê)',
            'menu' => 'Dosyayê Bişîne Menûyê',
            'id' => 'ID',
            'language' => 'Dil',
            'ad' => 'Dosya Kategori Nav',
            'seo' => 'Pel Kategori Bağlantısı',
            'status' => 'Yayınlansın mı?',
            'image' => 'Wêne',
            'title' => 'Title ( Lêbigerema Motorları İçin)',
            'keywords' => 'Keywords ( Lêbigerema Motorları İçin)',
            'aciklama' => 'Rave ( Arama Motorları İçin)',
            'menu' => 'Dosyayı Menüye Bişîne',
        ],
        'radio' => [
            'published_yes' => 'Erê',
            'published_no' => 'Na',
            'publish_yes' => 'Erê',
            'publish_no' => 'Na',
        ],
    ],

    // === Additional Legacy Keys ===
    'information' => 'Agahî!',
    'oke' => 'İşlem Serkeftî!',
    'hata' => 'Çewtî Oluştu!',
    'error' => 'Ölümcül Çewtî Oluştu!',
    'remove_image_oke' => 'Wêne Kaldırıldı',
    'choose_wish' => 'Beşekê Hilbijêre',
    'remove_record' => 'Bu Kaydı Jê Bibemek İstediğinize Eminmisiniz?',
    'errors' => [
        'record' => 'Belirttiğiniz Kayıt Ne Hate Dîtin!',
    ],

];