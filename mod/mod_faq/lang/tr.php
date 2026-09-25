<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_faq'] = [
    'title' => 'Sıkça Sorulan Sorular (SSS)',
    'module_title' => 'Sıkça Sorulan Sorular (SSS)',
    'add_question' => 'Soru Ekle',
    'edit_question' => 'Soruyu Düzenle',
    'delete_question' => 'Soruyu Sil',
    'update' => 'Güncelle',
    'save_all' => 'Değişiklikleri Kaydet',
    'question' => 'Soru',
    'answer' => 'Cevap',
    'sort_order' => 'Sıra',
    'status' => 'Durum',
    'published' => 'Yayında',
    'draft' => 'Taslak',
    'empty_state' => 'Henüz soru eklenmedi. İlk soruyu eklemek için + butonuna dokunun.',
    'character_limit' => 'Karakter Sınırı',
    'limit_reached' => 'Maksimum 25 soru sınırına ulaşıldı.',
    'question_placeholder' => 'Soruyu buraya yazın...',
    'answer_placeholder' => 'Cevabı buraya yazın...',
    'drag_to_reorder' => 'Sıralamak için sürükleyin',
    'confirm_delete' => 'Bu soruyu silmek istediğinizden emin misiniz?',
    'admin' => [
        'faq_management' => 'SSS Yönetimi',
        'manage_faq' => 'Sıkça Sorulan Sorular',
    ],
    'errors' => [
        'missing_module_name' => 'SSS işlemi için modül bilgisi eksik.',
        'missing_record_id' => 'SSS işlemi için kayıt bilgisi eksik.',
        'invalid_module' => 'Bu kayıt için tanımlı bir SSS modülü bulunamadı.',
        'module_faq_permission_missing' => 'Bu modül için SSS yetkisi tanımlanmamış.',
        'invalid_record' => 'İlgili kayıt bulunamadı.',
        'invalid_faq' => 'İlgili SSS kaydı bulunamadı.',
        'invalid_record_title_field' => 'Bu modül için mod_value alanı geçersiz.',
    ],
    'role' => [
        'name' => 'SSS Rolleri',
        'add' => 'Soru ekleyebilir.',
        'edit' => 'Soruları düzenleyebilir.',
        'delete' => 'Soruları silebilir.',
        'list' => 'SSS yönetimini görüntüleyebilir.',
        'moderate_edit' => 'Başkalarına ait soruları düzenleyebilir.',
        'moderate_delete' => 'Başkalarına ait soruları silebilir.',
    ],
    'role_errors' => [
        'faq' => 'SSS yönetme yetkiniz bulunmuyor.',
        'add' => 'Soru ekleme yetkiniz bulunmuyor.',
        'edit' => 'Soru düzenleme yetkiniz bulunmuyor.',
        'delete' => 'Soru silme yetkiniz bulunmuyor.',
        'list' => 'SSS yönetimini görüntüleme yetkiniz bulunmuyor.',
        'moderate_edit' => 'Başkalarına ait soruları düzenleme yetkiniz bulunmuyor.',
        'moderate_delete' => 'Başkalarına ait soruları silme yetkiniz bulunmuyor.',
    ],
    'message' => [
        'success' => [
            'add' => 'Soru başarıyla eklendi.',
            'edit' => 'Soru başarıyla güncellendi.',
            'delete' => 'Soru başarıyla silindi.',
            'reorder' => 'Sıralama başarıyla güncellendi.',
            'batch' => 'Tüm sorular başarıyla kaydedildi.',
            'status' => 'Durum başarıyla güncellendi.',
        ],
        'error' => [
            'add' => 'Soru eklenemedi.',
            'edit' => 'Soru güncellenemedi.',
            'delete' => 'Soru silinemedi.',
            'reorder' => 'Sıralama güncellenemedi.',
            'batch' => 'Toplu kayıt başarısız oldu.',
            'status' => 'Durum güncellenemedi.',
            'max_limit' => '25 soru sınırını aşamazsınız.',
            'invalid_record' => 'Geçersiz kayıt veya modül.',
            'permission_denied' => 'SSS yönetme yetkiniz bulunmuyor.',
            'duplicate_question' => 'Aynı metne sahip bir soru zaten mevcut.',
        ],
    ],
    'validation' => [
        'question_required' => 'Soru alanı zorunludur.',
        'question_max' => 'Soru 255 karakterden uzun olamaz.',
        'question_duplicate' => 'Bu soru daha önce eklenmiş.',
        'answer_required' => 'Cevap alanı zorunludur.',
        'answer_max' => 'Cevap 5000 karakterden uzun olamaz.',
    ]
];
