<?php

$lang['mod_redirect'] = [
    'list' => 'Yönlendirme Yönetimi',
    'add' => 'Yeni Yönlendirme Ekle',
    'edit' => 'Yönlendirmeyi Düzenle',
    'information' => 'Bilgi',
    'success' => 'Başarılı',
    'error' => 'Hata oluştu',
    'remove_record' => 'Bu yönlendirmeyi silmek istediğinizden emin misiniz?',
    'message' => [
        'success' => [
            'add' => 'Yönlendirme başarıyla eklendi!',
            'edit' => 'Yönlendirme başarıyla güncellendi!',
            'delete' => 'Yönlendirme başarıyla silindi!',
        ],
        'error' => [
            'add' => 'Yönlendirme eklenemedi!',
            'edit' => 'Yönlendirme güncellenemedi!',
        ],
    ],
    'errors' => [
        'record' => 'Belirtilen yönlendirme bulunamadı!',
    ],
    'button' => [
        'add' => 'Ekle',
        'edit' => 'Düzenle',
        'delete' => 'Sil',
        'cancel' => 'İptal',
        'yes' => 'Evet',
        'no' => 'Hayır',
    ],
    'validator' => [
        'required' => 'Lütfen %s alanını boş bırakmayınız',
        'length' => '%s uzunluğu %s ile %s karakter arasında olmalıdır',
        'unique_old_url' => 'Bu eski URL zaten sisteme kayıtlı!',
        'inarray' => '%s alanı sadece \'%s\' değerlerini alabilir',
    ],
    'role' => [
        'name' => 'Yönlendirme Yönetimi Rolleri',
        'add' => 'Yeni yönlendirme ekleyebilir',
        'edit' => 'Yönlendirmeleri düzenleyebilir',
        'delete' => 'Yönlendirmeleri silebilir',
        'list' => 'Yönlendirme yönetimini görüntüleyebilir',
    ],
    'role_errors' => [
        'add' => 'Yönlendirme ekleme yetkiniz yok',
        'edit' => 'Yönlendirme düzenleme yetkiniz yok',
        'list' => 'Yönlendirme yönetimini görüntüleme yetkiniz yok',
        'delete' => 'Yönlendirme silme yetkiniz yok',
    ],
    'admin' => [
        'search_placeholder' => 'Yönlendirmelerde ara...',
        'intro_add' => 'Yeni bir yönlendirme eklemek için eski URL ve yeni URL değerlerini girip durum kodunu seçiniz.',
        'intro_edit' => 'Buradan yönlendirme URL değerlerini ve durum kodunu düzenleyebilirsiniz.',
        'empty_description' => 'Burada kayıtlı herhangi bir yönlendirme bulunamadı.',
        'dashboard' => [
            'active_redirects' => 'Aktif Yönlendirmeler',
            'standard_type' => 'Standart Tür',
        ],
        'columns' => [
            'id' => 'Kimlik (ID)',
            'old_url' => 'Eski URL (Kaynak)',
            'new_url' => 'Yeni URL (Hedef)',
            'status_code' => 'Durum Kodu (Status Code)',
            'created_at' => 'Oluşturulma Tarihi',
        ],
    ],
];
