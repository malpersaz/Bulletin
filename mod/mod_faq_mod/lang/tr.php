<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_faq_mod'] = [
    'list' => 'SSS Modül Listesi',
    'add' => 'SSS Modülü Ekle',
    'edit' => 'SSS Modülü Düzenle',
    'information' => 'Bilgi',
    'success' => 'Başarılı',
    'error' => 'Hata Oluştu',
    'remove_record' => 'Bu kaydı silmek istediğinizden emin misiniz?',
    'message' => [
        'success' => [
            'add' => 'SSS modülü başarıyla eklendi!',
            'edit' => 'SSS modülü başarıyla güncellendi!',
            'delete' => 'SSS modülü başarıyla silindi!',
        ],
        'error' => [
            'add' => 'SSS modülü eklenemedi!',
            'edit' => 'SSS modülü güncellenemedi!',
        ],
    ],
    'errors' => [
        'record' => 'Belirtilen kayıt bulunamadı!',
    ],
    'button' => [
        'add' => 'Ekle',
        'edit' => 'Düzenle',
        'delete' => 'Sil',
        'refresh' => 'Yenile',
        'cancel' => 'İptal',
        'remove' => 'Kaldır',
        'yes' => 'Evet',
        'no' => 'Hayır',
    ],
    'validator' => [
        'required' => 'Lütfen %s alanını boş bırakmayınız',
        'length' => '%s, %s ile %s karakter arasında olmalıdır',
        'unique' => 'Girdiğiniz %s sistemde zaten kayıtlıdır. Lütfen farklı bir %s belirtiniz!',
    ],
    'role' => [
        'name' => 'SSS Modül Yönetim Rolleri',
        'add' => 'SSS modülü ekleyebilir',
        'edit' => 'SSS modülü düzenleyebilir',
        'delete' => 'SSS modülü silebilir',
        'list' => 'SSS modül yönetimini görüntüleyebilir',
        'moderate_edit' => 'Başkalarına ait kayıtları düzenleyebilir',
        'moderate_delete' => 'Başkalarına ait kayıtları silebilir',
    ],
    'role_errors' => [
        'moderate_edit' => 'Başkalarına ait kayıtları düzenleme yetkiniz bulunmuyor',
        'moderate_delete' => 'Başkalarına ait kayıtları silme yetkiniz bulunmuyor',
        'add' => 'SSS modülü ekleme yetkiniz bulunmuyor',
        'edit' => 'SSS modülü düzenleme yetkiniz bulunmuyor',
        'list' => 'SSS modül yönetimini görüntüleme yetkiniz bulunmuyor',
        'delete' => 'SSS modülü silme yetkiniz bulunmuyor',
    ],
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'SSS Modül Adı',
            'mod_name' => 'Modül Adı',
            'mod_key' => 'Modül Anahtarı (ID)',
            'mod_value' => 'Modül Değeri (Başlık)',
        ],
    ],
];
