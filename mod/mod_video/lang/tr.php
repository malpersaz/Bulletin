<?php
                $lang['mod_video'] = [
    'add' => 'Video Ekle',
    'edit' => 'Videoyu Düzenle',
    'list' => 'Video Listesi',
    'information' => 'Bilgi!',
    'oke' => 'İşlem Başarılı!',
    'hata' => 'Hata Oluştu!',
    'error' => 'Ölümcül Hata Oluştu!',
    'remove_image' => 'Bu Resmi Kaldırmak İstediğinize Eminmisiniz?',
    'remove_image_oke' => 'Resim Kaldırıldı',
    'choose_wish' => 'Dilerseniz Seçin',
    'remove_record' => 'Bu Kaydı Silmek İstediğinize Eminmisiniz?',
    'converting' => 'Video Dönüştürülüyor Lütfen Bekleyin...',
    'converted' => 'Video Yükleme Tamamlandı! Şimdi Videoyu Dönüştürebilirsiniz.',
    '19' => 'Video Dönüştürülürken Hatalar Oluştu %s',
    '20' => 'Video Dönüştürülme Tamamlandı.<br>Bu İşlem %s Saniye Sürdü.',
    'add_uzak' => 'Bu Videoyu Uzak Video Olarakta Sisteme Ekle',
    'unremove_original' => 'Bu Videonun Orjinal Halini Kaydet',
    'message' =>
    [
        'oke' =>
        [
            'add' => 'Video Eklendi!',
            'edit' => 'Video Düzenlendi!',
        ],
        'error' =>
        [
            'add' => 'Video Eklenemedi!',
            'edit' => 'Video Düzenlenemedi!',
        ],
        'information' =>
        [
            'moderate' => 'Düzenlemek İstediğiniz İçeriği İki Defa Tıklayın',
            'move' => 'Sırasını Değiştirmek İstediğiniz İçeriği Sürükleyip Bırakın',
        ],
    ],
    'errors' =>
    [
        '1' => 'TMP dizininden FLV dosyası Taşınırken Hata Oluştu',
        '2' => 'TMP dizininden Resim dosyası Taşınırken Hata Oluştu',
        '3' => 'TMP dizininden ORjinal Video Dosyası Taşınırken Hata Oluştu',
        'record' => 'Belirttiğiniz Kayıt Bulunamadı!',
        'in_valid_ext' => 'Geçersiz Bir Video Yükleme İstediniz.
	 Yanlızca <font color="black">%s</font> uzantılı Videolar Yükleyebilirsiniz.',
        'in_valid_ext_original' => 'Göndermiş Olduğunuz Orjinal Video Hatalı.
	 Yanlızca <font color="black">%s</font> uzantılı Videolar Yükleyebilirsiniz.'
    ],
    'button' =>
    [
        'add' => 'Ekle',
        'edit' => 'Düzenle',
        'yenile' => 'Yenile',
        'delete' => 'Sil',
        'gallery' => 'Resim Galerisi',
        'vazgec' => 'Vazgeç',
        'kaldir' => 'Kaldır',
        'evet' => 'Evet',
        'hayir' => 'Hayır',
        'yukle' => 'Yükle',
        'donustur' => 'Dönüştür'
    ],
    'grid' =>
    [
        0 => 'Toplam {total} Sonuçtan {from} den {to}\\\'a Kadar Olanlar Görüntüleniyor.',
        1 => 'Sayfa',
        2 => 'Sayfa Sayısı',
        3 => 'Veriler Yükleniyor, Lütfen Bekleyin ...',
        4 => 'Hiçbir Veri Bulunamadı',
        5 => 'Bağlantı Hatası',
        6 => 'Arama',
        7 => 'Lütfen En Az Bir Satır Seçin',
        8 => 'Lütfen Sadece Bir Satır Seçin',
        'publish' =>
             [
                'key' => 'Yayınla',
                'value' => 'Bu Videoyu Yayınlamak İstediğinize Eminmisiniz?',
            ],
        'unpublish' =>
             [
                'key' => 'Yayından Al',
                'value' => 'Bu Videoyu Yayından Kaldırmak İstediğinize Eminmisiniz?',
            ],
    ],
    'validator' =>
    [
        'required' => 'Lütfen %s Alanını Boş Bırakmayın',
        'length' => '%s En Kısa %s En Uzun %s Karekterden Oluşabilir',
        'email' => '%s Alanına Lütfen Geçerli Bir E-Posta Adresi Yazın.',
        'unique' => 'Yazmış Olduğunuz %s Sistemde Kayıtlı.Lütfen Başka Bir %s Belirleyin!',
        'inarray' => '%s Alanı Sadece \' %s \' değerleri alabilir',
    ],
    'roll' =>
    [
        'ad' => 'Video Yönetim Rolleri',
        'add' => 'Video Ekleyebilir',
        'edit' => 'Video Düzenleyebilir',
        'delete' => 'Video Silebilir',
        'list' => 'Video Yönetimini Görüntüleyebilir',
        'moderate_edit' => 'Kendisine Ait Olmayan Video Kaydını Düzenleyebilir',
        'moderate_delete' => 'Kendisine Ait Olmayan Video Kaydını Silebilir',
    ],
    'role_errors' =>
    [
        'moderate_edit' => 'Size Ait Olmayan Video Kaydını Düzenleyemezsiniz',
        'moderate_delete' => 'Size Ait Olmayan Video Kaydını Silemezsiniz',
        'add' => 'Video Eklemeye Permissionniz Yok',
        'edit' => 'Video Düzenlemeye Permissionniz Yok',
        'list' => 'Video Yönetimini Görüntülemeye Permissionniz Yok',
        'delete' => 'Video Silmeye Permissionniz Yok',
    ],
    'admin' =>
    [
        'columns' =>
        [
            'id' => 'ID',
            'language' => 'Videonun Görüntüleneceği Dil',
            'ad' => 'Video Adı',
            'sure' => 'Süre',
            'tarih' => 'Tarih',
            'status' => 'Yayınlansın mı?',
            'aciklama' => 'Açıklama',
            'image' => 'Resim',
            'onizleme' => 'Önizleme',
            'acl_id' => 'Bu Videoyu Kimler Görüntüleyebilir? <br><a href=\'mod_acl/list/\' target=\'_blank\' class=\'btn btn-xs blue\' style=\'margin-top:5px;\'><i class=\'fa fa-external-link\'></i> ACL - Erişim kontrol listesi</a>',
        ],
        'radio' =>
        [
            'publish_yes' => 'Evet',
            'publish_no' => 'Hayır',
        ],
    ],
];