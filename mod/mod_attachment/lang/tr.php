<?php
$lang['mod_attachment']=[];


$lang['mod_attachment']['admin']=[
	'upload_area'=>'Dosya Yükleme Alanı',
	'select_file_instruction'=>'Aşağıdaki Gözat Butonuna Tıklayarak Dosya Seçin!',
	'ctrl_key_instruction'=>'Klavyenizdeki <b>CTRL</b> tuşuna basılı tutarak,birden fazla Dosya seçip yükleyebilirsiniz.',
	'loading'=>'Yükleniyor...',
	'view'=>'Görüntüle',
	'waiting_queue'=>'Yüklenme Sırasını Bekliyor..',
	'file'=>'Dosya',
	'selected_files_count'=>'Seçilen Dosya Sayısı',
	'waiting_files_count'=>'Sıra Bekleyen Dosya Sayısı',
	'file_size_error'=>'Seçmiş olduğunuz \\\'+file.name+\\\' resminin boyutu çok büyük.',
	'browse'=>'Gözat',
	'back'=>'Geri',
	'file_management'=>'Dosya Yönetimi',
	'confirm_delete_file'=>'Bu Dosyai Silmek İstediğinize Eminmisiniz?',
	'delete_file'=>'Dosya Sil',
	'yes'=>'Evet',
	'cancel'=>'Vazgeç',
	'save'=>'Kaydet',
	'content_upload_area'=>'%s İçeriği İçin Dosya Yükleme Alanı',
	'file_name'=>'Dosya İsmi',
	'no_file_found'=>'Hiç Bir Dosya Bulunamadı!',
	'all_files'=>'---Bütün Dosyaler---',
	'no_saved_file_found'=>'Kayıtlı Hiç Bir Dosya Bulunamadı!',
	'select_category_add_file'=>'Kategori Seçip Dosya Ekleyin',
	'download_file'=>'Dosyayı İndir',
	'edit_file_name'=>'Dosya İsmini Düzenle',
	'remove_image'=>'Resmi Kaldır',
	'file_uploaded_success'=>'\'   %s Dosyası Sunucuya Yüklendi!   \'',
	'file_deleted'=>'Dosya Silindi',
	'files_belonging_to'=>'"%s"  Ait Dosyalar',
	'add_files'=>'Dosya Ekleyin',
	'file_edited'=>'Dosya Düzenlendi',
	'file_renamed'=>'"+original+" Adlı Dosyanın İsmi "+text+" Olarak Değiştirildi"',
	'file_upload_failed'=>'Dosya yüklenemedi.',
	'statistics'=>'İstatistik',
	'new'=>'Yeni',
	'panel'=>'Panel',
	'warning'=>'Uyarı!',
	'info'=>'Bilgi!',
	'message'=>'Mesaj!',
	'help'=>'Yardım!'
];

$lang['mod_attachment']['hata']=[
'source_not_retrieved'=>'Dosya Kaynağı Alınamadı',
'specified_file_invalid'=>'Belirttiğiniz Dosya Dosya Değil',
'specify_destination_file'=>'Lütfen Bir Dosyain yazılacağı Dosyayı Belirtin',
'supported_formats_only'=>'Sadece png,jpg,jpeg,gif,png,bmp Dosyaları çıktı olarak üretilebilinir',
'file_creation_failed'=>'Dosya Oluşturulamadı',
'file_output_failed'=>'Dosya Çıktı Oluşturulamadı'
];

$lang['mod_attachment']['errors'] = [
    'missing_module_name' => 'Dosya işlemi için modül bilgisi eksik.',
    'missing_record_id' => 'Dosya işlemi için kayıt bilgisi eksik.',
    'invalid_module' => 'Bu kayıt için dosya modülü tanımlı değil.',
    'module_file_permission_missing' => 'Bu modül için dosya yetkisi tanımlanmamış.',
    'invalid_record' => 'İlgili kayıt bulunamadı.',
    'invalid_file' => 'İlgili dosya kaydı bulunamadı.',
    'invalid_record_title_field' => 'Bu modül için mod_value alanı geçersiz. Gösterim alanını manuel olarak düzeltin.',
    'upload_field_missing' => 'Yukleme alani bilgisi eksik.',
    'uploaded_file_missing' => 'Yuklenecek dosya gonderilmedi.',
];

$lang['mod_attachment']['role']=[
	'name'=>'Galeri Rolleri',
	'add' =>'Dosya Ekleyebilir.',
	'edit' =>'Eklediği Resmi Düzenleyebilir',
	'delete' =>'Eklediği Resmi Silebilir',
	'list' =>'Dosya Yönetimini Görüntüleyebilir',
	'moderate_edit'=>'Başkasınına Ait Dosyaları Düzenleyebilir',
	'moderate_delete'=>'Başkasınına Ait Dosyaları Silebilir'
];
$lang['mod_attachment']['role_errors']=[
	'no_edit_permission'=>'Bir Başkasınına Ait Dosyai Düzenlemeye Permissionniz Bulunmuyor',
	'no_delete_permission'=>'Bir Başkasınına Ait Dosyai Silmeye Permissionniz Yok.',
	'add' =>'Dosya Eklemeye Permissionniz Yok',
	'delete' =>'Dosya Silmeye Permissionniz Yok',
	'edit' =>'Dosya Düzenleme Permissionniz Yok',
	'list' =>'Dosya Yönetimini Görüntülemeye Permissionniz Yok',
	'moderate_edit'=>'Bir Başkasınına Ait Dosyayı Düzenlemeye Permissionniz Bulunmuyor',
	'moderate_delete'=>'Bir Başkasınına Ait Dosyayı Silmeye Permissionniz Yok.'
];
