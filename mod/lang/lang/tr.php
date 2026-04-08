<?php
/**
 * Language Module - Turkish Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['lang'] = [
    'installer' => [
        'table_exists' => "'lang' tablosu veritabanında zaten kurulu",
        'sql_added' => "'lang.sql' dosyası veritabanına eklendi",
        'lang_added' => '<b>%s</b> veritabanına eklendi',
        'lang_exists' => '<b>%s</b> veritabanında zaten mevcut',
    ],
    'system' => [
        'starting' => 'Kurulum başlıyor...',
        'installing' => 'kuruluyor: %s',
    ],
    'admin' => [
        'title' => 'Dil Yönetimi',
        'headline' => 'Diller',
        'body' => 'Dilleri etkinleştirin, pasifleştirin ve varsayılan site dilini tek bir telefon uyumlu panelden yönetin.',
        'status_active' => 'Aktif',
        'status_inactive' => 'Pasif',
        'default_badge' => 'Varsayılan',
        'current_badge' => 'Geçerli Arayüz',
        'code_label' => 'Kod',
        'activate' => 'Aktifleştir',
        'deactivate' => 'Pasifleştir',
        'set_default' => 'Varsayılan Yap',
        'empty_state' => 'Hiç dil bulunamadı.',
        'permission_denied' => 'Dilleri yönetme yetkiniz yok.',
        'back_panel' => 'Panel',
        'back_home' => 'Site',
        'back' => 'Geri',
    ],
    'messages' => [
        'activated' => '%s artık aktif.',
        'deactivated' => '%s artık pasif.',
        'default_changed' => '%s artık varsayılan dil.',
    ],
    'errors' => [
        'language_not_found' => 'Seçilen dil bulunamadı.',
        'unknown_action' => 'İstenen dil işlemi geçerli değil.',
        'cannot_deactivate_default' => 'Bu dili pasifleştirmeden önce başka bir varsayılan dil seçin.',
        'cannot_deactivate_last_active' => 'En az bir dil aktif kalmalıdır.',
        'cannot_deactivate_current_interface' => 'Bu dili pasifleştirmeden önce mevcut yönetim dilini değiştirin.',
        'update_failed' => 'Dil durumu güncellenemedi.',
    ],
];
