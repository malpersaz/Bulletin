<?php
/**
 * Gallery Management Module Turkish Language File
 * Amed Framework CMS - Multilingual Content Management System
 */

$lang['mod_gallery'] = [
    // === Admin Interface ===
    'admin' => [
        'title' => 'Galeri Yönetimi',
        'list_title' => 'Galeriler',
        'create_gallery' => 'Yeni Galeri Oluştur',
        'search_placeholder' => 'Galerilerde ara...',
        'no_results' => 'Aramanızla eşleşen galeri bulunamadı.',
        'no_galleries' => 'Henüz hiç galeri oluşturulmamış.',
        'add_images' => 'Resim Ekle',
        'rename_image' => 'Resmi Yeniden Adlandır',
        'back' => 'Galerilere Dön',
        'edit_hint' => 'Resimleri görüntülemek, yeniden adlandırmak veya silmek için işlem düğmelerini kullanın.',
        'view_original' => 'Orijinali Görüntüle',
        'remove_image' => 'Resmi Kaldır',
        'edit_image_name' => 'Resmi Yeniden Adlandır',
        'image_name' => 'Resim İsmi',
        'save' => 'Değişiklikleri Kaydet',
        'cancel' => 'Vazgeç',
        'loading_galleries' => 'Galeriler yükleniyor...',
        'uploading' => 'Resimler Yükleniyor...',
        'upload_button' => 'Seçili Resimleri Yükle',
        'upload_success' => '"%s" başarıyla yüklendi.',
        'upload_complete' => 'Seçilen tüm resimler başarıyla yüklendi.',
        'image_deleted' => 'Resim silindi.',
        'image_edited' => 'Resim ismi başarıyla güncellendi.',
        'gallery_images_title' => '"%s" İçindeki Resimler',
        'delete_title' => 'Resmi Sil',
        'delete_confirm' => 'Bu resmi silmek istediğinize emin misiniz? Bu işlem geri alınamaz.',
        'yes' => 'Evet, Sil',
        'browse_description' => 'Bir veya daha fazla resim seçmek için tıklayın',
        'ctrl_select_hint' => 'Birden fazla dosya seçmek için <b>CTRL</b> tuşuna basılı tutun.',
        'area_title' => 'Medya Yükle',
        'upload_area_for' => '"%s" için resim yükleyin',
        'no_registered_images' => 'Bu galeride hiç resim bulunmuyor.',
        'select_category_hint' => 'Galeriyi doldurmak için resim yükleyin.',
        'selected_files' => 'Seçilen dosyalar',
        'preview_alt' => 'Galeri resmi önizlemesi',
        'search_images' => 'Resimlerde ara...',
        'module_label' => 'Modül',
        'record_label' => 'Kayıt',
        'images_summary' => '%s resim',
        'no_filtered_images' => 'Aramanızla eşleşen resim bulunamadı.',
        'clear_search' => 'Aramayı temizle',
        'sort_label' => 'Sırala',
        'sort_newest' => 'En yeni önce',
        'sort_oldest' => 'En eski önce',
        'sort_name_asc' => 'İsim A-Z',
        'sort_name_desc' => 'İsim Z-A',
        'select_mode' => 'Seç',
        'done_selecting' => 'Bitti',
        'select_all' => 'Görünürleri seç',
        'clear_selection' => 'Temizle',
        'bulk_delete' => 'Seçilileri sil',
        'selected_summary' => '%s seçili',
        'bulk_delete_title' => 'Seçili resimleri sil',
        'bulk_delete_confirm' => 'Seçili %s resmi silmek istediğinize emin misiniz? Bu işlem geri alınamaz.',
        'detail_title' => 'Resim Detayı',
        'detail_empty' => 'Detaylarını ve işlemlerini görmek için bir resim seçin.',
        'detail_filename' => 'Dosya adı',
        'detail_image_id' => 'Resim ID',
        'detail_position' => 'Sıra',
        'open_detail' => 'Detayı aç',
        'previous_image' => 'Önceki resim',
        'next_image' => 'Sonraki resim',
        'upload_queue_title' => 'Yükleme Kuyruğu',
        'queue_waiting' => 'Bekliyor',
        'queue_uploading' => 'Yükleniyor',
        'queue_success' => 'Yüklendi',
        'queue_failed' => 'Başarısız',
        'queue_ready' => '%s dosya yüklenmeye hazır',
        'upload_status_ready' => 'Kuyruk hazır',
        'upload_status_active' => 'Yükleme sürüyor',
        'upload_status_complete' => 'Yükleme tamamlandı',
        'upload_status_attention' => 'Yükleme sonuçlarını gözden geçir',
        'upload_progress' => '%s / %s dosya tamamlandı',
        'upload_progress_with_errors' => '%s / %s dosya tamamlandı, %s başarısız',
        'upload_remaining' => '%s dosya kaldı',
        'upload_processing_current' => '%s / %s dosya yükleniyor',
        'upload_sheet_lock' => 'Tüm yüklemeler bitene kadar bu sayfayı açık tutun. Sayfayı şimdi kapatmak kuyruğu kesebilir.',
        'upload_leave_warning' => 'Yüklemeler hâlâ sürüyor. Şimdi ayrılırsanız kuyruk kesilebilir.',
    ],

    // === Gallery List / Grid ===
    'grid' => [
        'name' => 'Galeri Adı',
        'image_count' => 'Resim Sayısı',
        'created_at' => 'Oluşturulma Tarihi',
        'actions' => 'İşlemler',
        'load_more' => 'Daha Fazla Yükle',
        'empty_state' => 'Görüntülenecek galeri bulunmuyor.',
    ],

    // === Errors ===
    'errors' => [
        'record' => 'Kayıt bulunamadı!',
        'upload_failed' => 'Yükleme başarısız oldu!',
        'delete_failed' => 'Kayıt silinemedi.',
        'load_failed' => 'Galeriler yüklenemedi.',
        'rename_failed' => 'Resim ismi güncellenemedi.',
    ],

    // === Role Management ===
    'roll' => [
        'ad' => 'Galeri Yönetim Rolleri',
        'add' => 'Galeri ve resim ekleyebilir',
        'edit' => 'Galeri öğelerini düzenleyebilir',
        'delete' => 'Galeri öğelerini silebilir',
        'list' => 'Galeri yönetimini görüntüleyebilir',
    ],

    // === Role Errors ===
    'role_errors' => [
        'add' => 'Galeri ekleme izniniz bulunmuyor.',
        'edit' => 'Galeri öğelerini düzenleme izniniz bulunmuyor.',
        'delete' => 'Öğeleri silme izniniz bulunmuyor.',
        'list' => 'Galeri yönetimini görüntüleme izniniz bulunmuyor.',
        'gallery' => 'Bu galeriyi yönetme izniniz bulunmuyor.',
        'file' => 'Bu galeriye dosya yükleme izniniz bulunmuyor.',
    ],

    'hata' => [
        'get_source_failed' => 'Resim kaynağı okunamadı.',
    ],

    // === AJAX Messages ===
    'ajax' => [
        'success' => 'İşlem başarılı',
        'error' => 'İstek sırasında bir hata oluştu.',
        'relation_updated' => 'Galeri ilişkisi başarıyla güncellendi.',
    ],

    'info' => 'Bilgi',
    'warning' => 'Uyarı',
    'message' => 'Mesaj',
    'help' => 'Yardım',
];
