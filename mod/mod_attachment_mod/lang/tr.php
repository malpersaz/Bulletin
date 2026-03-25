<?php
$lang['mod_attachment_mod'] = [
  'add' => 'Dosya Modu Ekle',
  'edit' => 'Dosya Modunu Düzenle',
  'list' => 'Dosya Mod Listesi',
  'information' => 'Bilgi!',
  'oke' => 'İşlem Başarılı!',
  'hata' => 'Hata Oluştu!',
  'error' => 'Ölümcül Hata Oluştu!',
  'remove_record' => 'Bu Kaydı Silmek İstediğinize Eminmisiniz?',
  'message' => 
   [
    'oke' => 
     [
      'add' => 'Dosya Modu Eklendi!',
      'edit' => 'Dosya Modu Düzenlendi!',
      'delete' => 'Dosya Modu Silindi!',
    ],
    'error' => 
     [
      'add' => 'Dosya Modu Eklenemedi!',
      'edit' => 'Dosya Modu Düzenlenemedi!',
    ],
  ],
  'errors' => 
   [
    'record' => 'Belirttiğiniz Kayıt Bulunamadı!',
  ],
  'button' => 
   [
    'add' => 'Ekle',
    'edit' => 'Düzenle',
    'yenile' => 'Yenile',
    'delete' => 'Sil',
    'file' => 'Dosyalar',
    'gallery' => 'Galeri',
    'vazgec' => 'Vazgeç',
    'kaldir' => 'Kaldır',
    'evet' => 'Evet',
    'hayir' => 'Hayır',
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
  ],
  'validator' => 
   [
    'required' => 'Lütfen %s Alanını Boş Bırakmayın',
    'length' => '%s En Kısa %s En Uzun %s Karekterden Oluşabilir',
    'display_field' => '%s alanı teknik veya ilişki kolonu olamaz. id, file_id yerine name veya title gibi gösterim alanı kullanın.',
    'email' => '%s Alanına Lütfen Geçerli Bir E-Posta Adresi Yazın.',
    'unique' => 'Yazmış Olduğunuz %s Sistemde Kayıtlı.Lütfen Başka Bir %s Belirleyin!',
    'inarray' => '%s Alanı Sadece \' %s \' değerleri alabilir',
  ],
  'roll' => 
   [
    'ad' => 'Dosya Mod Yönetim Rolleri',
    'add' => 'Dosya Modu Ekleyebilir',
    'edit' => 'Dosya Modu Düzenleyebilir',
    'delete' => 'Dosya Modu Silebilir',
    'list' => 'Dosya Mod Yönetimini Görüntüleyebilir',
    'moderate_edit' => 'Kendisine Ait Olmayan Dosya Mod Kaydını Düzenleyebilir',
    'moderate_delete' => 'Kendisine Ait Olmayan Dosya Mod Kaydını Düzenleyebilir',
  ],
  'role_errors' => 
   [
    'moderate_edit' => 'Size Ait Olmayan Dosya Mod Kaydını Düzenleyemezsiniz',
    'moderate_delete' => 'Size Ait Olmayan Dosya Mod Kaydını Silemezsiniz',
    'add' => 'Dosya Modu Eklemeye Permissionniz Yok',
    'edit' => 'Dosya Modu Düzenlemeye Permissionniz Yok',
    'list' => 'Dosya Modu Yönetimini Görüntülemeye Permissionniz Yok',
    'delete' => 'Dosya Modu Silmeye Permissionniz Yok',
  ],
  'admin' => 
   [
    'intro_add' => 'Bir içerik modülünü dosya yöneticisine bağlamak için görünen adı, hedef modül adını ve o modülden okunacak anahtar alanları tanımlayın.',
    'intro_edit' => 'Bu kaydın hangi modüle bağlandığını ve başlık bilgisinin hangi alanlardan okunacağını buradan güncelleyebilirsiniz.',
    'empty_description' => 'Dosya modları, farklı içerik modüllerine dosya yönetimi bağlamanızı sağlar.',
    'hints' =>
     [
      'name' => 'Yönetim ekranında görünecek başlık.',
      'mod_name' => 'Örnek: mod_news, mod_page, mod_attachment_mod',
      'mod_key' => 'Kayıt başlığını bulmak için okunacak alan adı. Genelde id veya ilişki alanı.',
      'mod_value' => 'Başlıkta gösterilecek veri alanı. Genelde name, title veya benzeri bir kolon.',
      'mod_name_edit' => 'Bağlı olduğu sistem modülünün teknik adı.',
      'mod_key_edit' => 'Başlık kaydını bulmak için okunacak alan.',
      'mod_value_edit' => 'Listede içerik başlığı olarak gösterilecek alan.',
    ],
    'columns' => 
     [
      'id' => 'ID',
      'name' => 'Dosya Mod İsmi',
      'ad' => 'Dosya Mod İsmi',
      'mod_name' => 'Dosya mod_name',
      'mod_key' => 'Dosya mod_key (id)',
      'mod_value' => 'Gösterim Alanı',
    ],
  ],
];
