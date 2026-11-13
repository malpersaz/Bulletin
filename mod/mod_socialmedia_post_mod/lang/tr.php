<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_socialmedia_post_mod'] = [
    'named' => 'Sosyal Gönderi Modülleri',
    'list' => 'Sosyal Gönderi Modül Listesi',
    'add' => 'Sosyal Gönderi Modülü Ekle',
    'edit' => 'Sosyal Gönderi Modülü Düzenle',
    'information' => 'Bilgi',
    'success' => 'Başarılı',
    'error' => 'Hata Oluştu',
    'remove_record' => 'Bu kaydı silmek istediğinize emin misiniz?',
    'message' => [
        'success' => [
            'add' => 'Sosyal Gönderi Modülü başarıyla eklendi!',
            'edit' => 'Sosyal Gönderi Modülü başarıyla güncellendi!',
            'delete' => 'Sosyal Gönderi Modülü başarıyla silindi!',
        ],
        'error' => [
            'add' => 'Sosyal Gönderi Modülü eklenemedi!',
            'edit' => 'Sosyal Gönderi Modülü güncellenemedi!',
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
        'required' => 'Lütfen %s alanını boş bırakmayın',
        'length' => '%s alanı %s ile %s karakter arasında olmalıdır',
        'unique' => 'Girdiğiniz %s sistemde zaten kayıtlı. Lütfen farklı bir %s belirtin!',
    ],
    'role' => [
        'name' => 'Sosyal Gönderi Modül Yönetimi Yetkileri',
        'add' => 'Sosyal Gönderi Modülü Ekleyebilir',
        'edit' => 'Sosyal Gönderi Modülü Düzenleyebilir',
        'delete' => 'Sosyal Gönderi Modülü Silebilir',
        'list' => 'Sosyal Gönderi Modül Yönetimini Görüntüleyebilir',
        'moderate_edit' => 'Başkalarına ait Sosyal Gönderi Modülü kayıtlarını düzenleyebilir',
        'moderate_delete' => 'Başkalarına ait Sosyal Gönderi Modülü kayıtlarını silebilir',
    ],
    'role_errors' => [
        'moderate_edit' => 'Başkalarına ait Sosyal Gönderi Modülü kayıtlarını düzenleyemezsiniz',
        'moderate_delete' => 'Başkalarına ait Sosyal Gönderi Modülü kayıtlarını silemezsiniz',
        'add' => 'Sosyal Gönderi Modülü ekleme yetkiniz yok',
        'edit' => 'Sosyal Gönderi Modülü düzenleme yetkiniz yok',
        'list' => 'Sosyal Gönderi Modül yönetimini görüntüleme yetkiniz yok',
        'delete' => 'Sosyal Gönderi Modülü silme yetkiniz yok',
    ],
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'Modül Başlığı',
            'mod_name' => 'Modül Adı',
            'mod_key' => 'Modül Anahtarı (ID)',
            'mod_value' => 'Modül Değeri (Başlık)',
        ],
    ],
];
