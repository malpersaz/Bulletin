<?php
$lang['mod_page'] = [
  'add' => 'Sayfa Ekle',
  'edit' => 'Sayfayı Düzenle',
  'list' => 'Sayfa Listesi',
  'information' => 'Bilgi',
  'oke' => 'İşlem Başarılı',
  'hata' => 'Hata Oluştu',
  'error' => 'Kritik Hata',
  'remove_image' => 'Bu resmi kaldırmak istediğinize emin misiniz?',
  'remove_image_oke' => 'Resim Başarıyla Kaldırıldı',
  'choose_wish' => 'Dilerseniz Seçin',
  'menu_options' => [
    'none' => 'Menüye ekleme',
    'top_level' => 'Üst seviye menü öğesi olarak ekle',
    'slot_root' => 'Seçili menü yüzeyi altına ekle',
  ],
  'menu_slot_options' => [
    'none' => 'Menü yüzeyi yok',
    'header' => 'Üst Menü',
    'footer' => 'Alt Menü',
  ],
  'remove_record' => 'Bu kaydı silmek istediğinize emin misiniz?',
  'seo_meta' => 'SEO Meta Verileri',
  'messages' => [
    'oke' => [
      'add' => 'Sayfa Başarıyla Eklendi',
      'edit' => 'Sayfa Başarıyla Düzenlendi',
    ],
    'error' => [
      'add' => 'Sayfa Eklenemedi',
      'edit' => 'Sayfa Düzenlenemedi',
    ],
    'information' => [
      'moderate' => 'Düzenlemek istediğiniz sayfanın üzerine çift tıklayın',
      'move' => 'Sıralamayı değiştirmek için sayfaları sürükleyip bırakın',
    ],
    'warning' => [
      'reorder' => 'Sayfa sırası değiştirilsin mi?',
      'delete' => 'Bu sayfayı silmek istediğinize emin misiniz?',
    ],
    'success' => [
      'delete' => 'Sayfa başarıyla silindi',
    ],
  ],
  'confirmations' => [
    'reorder_single' => 'Bu sayfanın sırasını değiştirmek istiyor musunuz?',
    'delete_single' => 'Bu sayfayı silmek istediğinize emin misiniz?',
  ],
  'options' => [
    'yes' => 'Evet',
    'no' => 'Hayır',
  ],
  'errors' => [
    'record' => 'Hata: Belirtilen kayıt bulunamadı',
    'slot_branch_missing' => 'Seçilen menü yüzeyi bu dil için çözümlenemedi',
  ],
  'button' => [
    'add' => 'Ekle',
    'edit' => 'Düzenle',
    'delete' => 'Sil',
    'vazgec' => 'Vazgeç',
    'kaldir' => 'Kaldır',
    'evet' => 'Evet',
    'hayir' => 'Hayır',
    'gallery' => 'Resim Galerisi',
    'file' => 'Dosya Yöneticisi',
    'tamam' => 'Tamam',
    'upload' => 'Yükle',
  ],
  'actions' => [
    'cancel' => 'Vazgeç',
    'edit' => 'Düzenle',
    'delete' => 'Sil',
    'unlock_reorder' => 'Sürükle-Bırak Modunu Aç',
    'lock_reorder' => 'Sürükle-Bırak Modunu Kapat',
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
    'ad' => 'Sayfa Yönetim Rolleri',
    'add' => 'Sayfa Ekleyebilir',
    'edit' => 'Sayfa Düzenleyebilir',
    'delete' => 'Sayfa Silebilir',
    'list' => 'Sayfa Yönetimini Görüntüleyebilir',
    'gallery' => 'Galleri Ekleyebilirmi?',
    'file' => 'Dosya Ekleyebilirmi?',
    'moderate_edit' => 'Kendisine Ait Olmayan Sayfa Kaydını Düzenleyebilir',
    'moderate_delete' => 'Kendisine Ait Olmayan Sayfa Kaydını Silebilir',
    'approval' => 'Sayfaları Yayınlayabilir/Yayından Kaldırabilir mi?',
  ],
  'role_errors' =>
  [
    'moderate_edit' => 'Size Ait Olmayan Sayfa Kaydını Düzenleyemezsiniz',
    'moderate_delete' => 'Size Ait Olmayan Sayfa Kaydını Silemezsiniz',
    'add' => 'Sayfa Eklemeye Permissionniz Yok',
    'edit' => 'Sayfa Düzenlemeye Permissionniz Yok',
    'list' => 'Sayfa Yönetimini Görüntülemeye Permissionniz Yok',
    'delete' => 'Sayfa Silmeye Permissionniz Yok',
    'gallery' => 'Galeri Eklemeye Permissionniz Yok',
    'file' => 'Dosya Eklemeye Yetniniz Yok',
    'approval' => 'Sayfaları Yayınlamak/Yayından Kaldırmak İçin Yetkiniz Yok',
  ],
  'admin' =>
  [
    'columns' =>
    [
      'id' => 'ID',
      'language' => 'Görüntüleneceği Dil',
      'name' => 'Sayfa Adı',
      'parent_id' => 'Alt Sayfa',
      'order' => 'Sıra',
      'root' => 'Görüntülenmemesi Gereken Root Sayfamı?',
      'status' => 'Yayınlansın mı?',
      'publish_desc' => 'İçeriği yayınlamak mı yoksa taslak olarak kaydetmek mi istersiniz?',
      'deck' => 'Giriş bölümü içerik sayfasında götüntülenmeli mi?',
      'summary' => 'Giriş (Deck)',
      'content' => 'İçerik',
      'gallery_id' => 'Galeri ID (Başka bir sayfanın galeri resimlerini göstermek için o sayfanın ID\'sini yazın)',
      'file_id' => 'Dosya ID (Başka bir sayfanın dosyalarını göstermek için o sayfanın ID\'sini yazın)',
      'seo' => 'Sayfa Bağlantısı',
      'title' => 'Title ( Arama Motorları İçin)',
      'keywords' => 'Keywords ( Arama Motorları İçin)',
      'description' => 'Açıklama ( Arama Motorları İçin)',
      'image' => 'Resim',
      'acl_id' => 'Bu Sayfayı Kimler Görüntüleyebilir?',
      'category_id' => 'ilişkili İçerik Kategorisi',
      'menu' => 'Sayfayı Menüye Gönder',
      'menu_slot' => 'Menü Yüzeyi',
    ],
    'select_value' =>
    [
      'root_e' => 'Evet',
      'root_h' => 'Hayır',
    ],
    'radio' =>
    [
      'publish_yes' => 'Yayınla',
      'publish_no' => 'Taslak Olarak Kaydet',
      'deck_yes' => 'Evet',
      'deck_no' => 'Hayır',
    ],
  ],
  'wizard' =>
  [
    'steps' =>
    [
      'language' => 'Dil',
      'structure' => 'Yapı',
      'title' => 'Başlık',
      'summary' => 'Özet',
      'content' => 'İçerik',
      'display' => 'Görünüm',
      'relations' => 'İlişkiler',
      'seo_finish' => 'SEO ve Bitiş',
    ],
    'descriptions' =>
    [
      'language' => 'Sayfa ağacını ve ilgili seçenekleri oluşturmadan önce dili seçin.',
      'structure' => 'Bu sayfanın nereye ait olduğunu ve kategori ile menü ağaçlarına bağlanıp bağlanmayacağını seçin.',
      'title' => 'Sayfanın ana kimliğini belirleyecek adı tanımlayın.',
      'summary' => 'Sayfa gövdesinden önce gösterilecek kısa giriş metnini yazın.',
      'content' => 'Sayfanın asıl içeriğini oluşturun.',
      'display' => 'Sayfanın yapısal görünüm davranışını belirleyin.',
      'relations' => 'İsteğe bağlı ilişkili kayıtları ve medya referanslarını bağlayın.',
      'seo_finish' => 'SEO değerlerini gözden geçirin, yayın durumunu seçin ve sayfa kurulumunu tamamlayın.',
    ],
    'page_summary' => 'Sayfa Özeti',
    'previous' => 'Önceki',
    'next' => 'Sonraki',
  ],
  'quick_unpublish_confirm' => 'Bu sayfayı yayından kaldırmak istediğinizden emin misiniz?',
  'quick_publish_confirm' => 'Bu sayfayı yayınlamak istediğinizden emin misiniz?',
  'published_msg' => 'Sayfa Başarıyla Yayınlandı',
  'unpublished_msg' => 'Sayfa Başarıyla Yayından Kaldırıldı',
  'status_badge' => [
    'active' => 'Yayında',
    'draft' => 'Taslak',
  ],
];
