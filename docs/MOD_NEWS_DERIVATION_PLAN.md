# `mod_news` / `mod_news_category` Türetme Planı

Bu doküman, mevcut `mod_content` ve `mod_category` modülleri yerinde bırakılarak bunlardan sırasıyla `mod_news` ve `mod_news_category` modüllerinin türetilmesi için uygulanacak planı tanımlar.

Amaç yeni özellik eklemek değildir. Amaç, mevcut davranışı koruyarak haber alanı için ayrı modül ailesi oluşturmaktır.

## Hedef

- `mod_content` yerinde kalacak.
- `mod_category` yerinde kalacak.
- `mod_content` taban alınarak yeni bir `mod_news` modülü üretilecek.
- `mod_category` taban alınarak yeni bir `mod_news_category` modülü üretilecek.
- `mod_news`, kategori kaynağı olarak `mod_category` yerine `mod_news_category` kullanacak.
- İlk aşamada davranış kopyalanacak; fonksiyonel genişleme yapılmayacak.

## Neden Bu Yol

Bu framework içinde modül adı yalnızca klasör adı değildir. Aşağıdaki katmanlar modül adına bağlıdır:

- PHP sınıf adları
- `Load::model()` ve `Load::lang()` çağrıları
- izin anahtarları
- installer kayıtları
- SQL tablo adları
- admin ve frontend template yolları
- statik dosya klasörleri
- diğer modüllerin string tabanlı bağımlılıkları

Bu yüzden doğrudan rename yerine türetme yaklaşımı daha güvenlidir. Mevcut modüller bozulmadan kalır, yeni modüller parça parça doğrulanabilir.

## Kapsam

Plan aşağıdaki alanları kapsar:

- modül klasörleri
- model ve validator sınıfları
- dil dosyaları
- installer dosyaları
- SQL şemaları
- admin template'leri
- frontend template'leri
- menü, izin ve ilişki referansları
- veri taşıma ve doğrulama

Plan aşağıdakileri kapsamaz:

- yeni business rule eklemek
- yeni alan eklemek
- yeni UI akışı tasarlamak
- `mod_content` veya `mod_category` davranışını değiştirmek

## Uygulama Stratejisi

İş iki büyük fazda yapılmalıdır:

1. `mod_news_category` türetilecek ve çalışır hale getirilecek.
2. `mod_news`, `mod_news_category`'ye bağlanacak şekilde türetilecek.

Bu sıra önemlidir. Çünkü `mod_news` içinde kategori seçimleri, nested kategori listeleri, join sorguları ve frontend içerik filtreleri kategori modülüne bağlıdır.

## Etkilenen Ana Dosya Yüzeyi

### Kaynak modüller

`mod_category`:

- `mod/mod_category/index.php`
- `mod/mod_category/model/mod_category.model.php`
- `mod/mod_category/validator/add.validator.php`
- `mod/mod_category/validator/edit.validator.php`
- `mod/mod_category/lang/*.php`
- `mod/mod_category/mod_category.sql`
- `installer/mod_category/index.php`
- `installer/mod_category/mod_category.sql`
- `templates/admin/mod_category/*.twig`
- `templates/admin_old/mod_category/*.twig`

`mod_content`:

- `mod/mod_content/index.php`
- `mod/mod_content/model/mod_content.model.php`
- `mod/mod_content/model/contents.model.php`
- `mod/mod_content/model/datatable.model.php`
- `mod/mod_content/model/flexigrid.model.php`
- `mod/mod_content/validator/add.validator.php`
- `mod/mod_content/validator/edit.validator.php`
- `mod/mod_content/lang/*.php`
- `mod/mod_content/mod_content.sql`
- `installer/mod_content/index.php`
- `installer/mod_content/mod_content.sql`
- `installer/mod_content/contents.sql`
- `templates/admin/mod_content/*.twig`
- `templates/admin_old/mod_content/*.twig`
- `templates/admin_old/modern/mod_content/*.twig`
- `templates/default/index/mod_content/*.twig`
- `templates/artist/index/mod_content/*.twig`
- `templates/soul/index/mod_content/*.twig`

### Üretilecek hedef modüller

- `mod/mod_news/...`
- `mod/mod_news_category/...`
- `installer/mod_news/...`
- `installer/mod_news_category/...`
- `templates/admin/mod_news/...`
- `templates/admin/mod_news_category/...`
- gerekiyorsa `templates/admin_old/...`
- gerekiyorsa frontend tema eşdeğerleri

## Faz 1: `mod_news_category` Türetme

Bu faz bağımsız tamamlanmalıdır.

### 1. Dosya çoğaltma

`mod_category` dosya yapısı `mod_news_category` adıyla çoğaltılacak:

- `mod/mod_category` -> `mod/mod_news_category`
- `installer/mod_category` -> `installer/mod_news_category`
- `templates/admin/mod_category` -> `templates/admin/mod_news_category`
- gerekliyse `templates/admin_old/mod_category` -> `templates/admin_old/mod_news_category`

### 2. Sınıf ve loader referansları

Aşağıdaki anahtarlar yeni modüle çevrilecek:

- `mod_category` -> `mod_news_category`
- `class mod_category_mod` -> `class mod_news_category_mod`
- `class mod_category_model` -> `class mod_news_category_model`
- `Load::lang('mod_category')` -> `Load::lang('mod_news_category')`
- `Load::model('mod_category')` -> `Load::model('mod_news_category')`
- izin kontrollerinde `mod_category` -> `mod_news_category`

### 3. SQL ve tablo isimleri

Yeni kategori modülü kendi tablosunu kullanmalıdır:

- `mod_category` tablosu kopyalanıp `mod_news_category` olarak üretilecek
- installer SQL'i bu tabloyu oluşturacak şekilde güncellenecek
- model içindeki `$_table` değeri `mod_news_category` olacak

### 4. Dil anahtarları

Dil dosyalarında kök anahtar `mod_news_category` olacak.

Örnek:

- `$lang['mod_category']` -> `$lang['mod_news_category']`

Metinler ilk aşamada kategori mantığını koruyabilir; yalnız modül anahtarı ayrıştırılmalıdır.

### 5. Template yolları

Admin template referansları yeni yola çevrilecek:

- `admin/mod_category/add` -> `admin/mod_news_category/add`
- `admin/mod_category/edit` -> `admin/mod_news_category/edit`
- `admin/mod_category/list` -> `admin/mod_news_category/list`

### 6. Menü ve izin kayıtları

Installer içinde:

- modül kaydı `mod_news_category` adıyla oluşturulacak
- varsayılan izinler `mod_news_category` anahtarıyla yazılacak
- admin navigasyonda görünmesi gerekiyorsa yeni route bağlanacak

### 7. Doğrulama

Bu faz sonunda aşağıdakiler test edilmelidir:

- kategori listeleme
- kategori ekleme
- kategori düzenleme
- kategori silme
- kategori sıralama / parent-child yapısı
- kategori görseli varsa yükleme/silme
- admin template render zinciri

## Faz 2: `mod_news` Türetme

Bu faz yalnız `mod_news_category` stabil olduktan sonra başlatılmalıdır.

### 1. Dosya çoğaltma

`mod_content` dosya yapısı `mod_news` adıyla çoğaltılacak:

- `mod/mod_content` -> `mod/mod_news`
- `installer/mod_content` -> `installer/mod_news`
- `templates/admin/mod_content` -> `templates/admin/mod_news`
- gerekliyse `templates/admin_old/mod_content` -> `templates/admin_old/mod_news`
- frontend tema template'leri `mod_news` altına kopyalanacak

### 2. Sınıf ve loader referansları

Aşağıdaki anahtarlar yeni modüle çevrilecek:

- `mod_content` -> `mod_news`
- `class mod_content_mod` -> `class mod_news_mod`
- `class mod_content_model` -> `class mod_news_model`
- `Load::lang('mod_content')` -> `Load::lang('mod_news')`
- `Load::model('mod_content')` -> `Load::model('mod_news')`
- izin kontrollerinde `mod_content` -> `mod_news`

### 3. Veritabanı ayrıştırması

`mod_news` kendi tablo ailesini kullanmalıdır:

- `mod_content` tablosu kopyalanıp `mod_news` olarak oluşturulacak
- `installer/mod_content/mod_content.sql` türevi `installer/mod_news/mod_news.sql` olacak
- `installer/mod_content/contents.sql` türevi `installer/mod_news/contents.sql` olacak
- ilgili model dosyalarında `$_table = "mod_news"` kullanılacak

### 4. Kategori bağımlılığını ayırma

Bu fazın en kritik işi budur. `mod_news`, `mod_category` yerine `mod_news_category` kullanmalıdır.

Değiştirilecek ana bağımlılıklar:

- `Load::model('mod_category')` -> `Load::model('mod_news_category')`
- kategori seçim listeleri
- nested kategori helper kullanımı
- SQL join'lerde `mod_category` -> `mod_news_category`
- varsa kategori SEO / kategori isim eşlemeleri
- frontend helper'larda kategori veri kaynağı

Not:

- `category_id` alan adı ilk aşamada korunabilir.
- Ancak bu alan artık `mod_news_category.id` değerlerini işaret edecek şekilde çalışmalıdır.

### 5. Galeri, dosya ve diğer yardımcı modül ilişkileri

`mod_news`, `mod_content` gibi ek medya ilişkileri kullanıyorsa yeni modül adıyla çalışmalıdır.

Kontrol edilecek alanlar:

- `mod_gallery_mod.mod_name`
- `mod_attachment_mod.mod_name`
- araç modülleri içinde `exists_mod_name('mod_news')`
- izin kontrolleri
- indirme / medya erişim helper'ları

### 6. Template ve route ayrıştırması

Admin tarafında:

- `mod_news/add`
- `mod_news/edit`
- `mod_news/list`

Frontend tarafında:

- `mod_news/content`
- `mod_news/contents`
- `mod_news/arsiv`
- `mod_news/latest_content`

İlk aşamada görünüm birebir aynı kalabilir. Sadece veri kaynağı ve route adı ayrıştırılmalıdır.

### 7. Dil anahtarları

Dil dosyalarında kök anahtar `mod_news` olacak.

Örnek:

- `$lang['mod_content']` -> `$lang['mod_news']`

Metinlerde "content" yerine "news" karşılıkları gerekiyorsa sonradan ayrı temizlik yapılabilir. İlk geçişte kırılmayan, tutarlı anahtar yapısı daha önemlidir.

### 8. Menü ve izin kayıtları

Installer ve yönetim menüsünde yeni modül ayrı bir varlık olarak tanımlanmalıdır:

- modül kaydı `mod_news`
- permission anahtarı `mod_news`
- admin linkleri `mod_news/*`
- gerekiyorsa dashboard kartları ve hızlı erişim bağlantıları

## Faz 3: Çapraz Referans Temizliği

Her iki modül üretildikten sonra aşağıdaki alanlar taranmalıdır:

- `mod_user` içindeki admin menü linkleri
- `management` içindeki ortak admin context verileri
- `installer/index.php` modül yükleme sırası
- ortak helper veya service sınıfları
- frontend helper'lar
- SQL view / rapor dosyaları
- manuel string tabanlı route üretimleri

Özellikle aşağıdaki ilişki yönleri kontrol edilmelidir:

- `mod_news` yanlışlıkla `mod_content` template'ine gitmemeli
- `mod_news` yanlışlıkla `mod_category` kullanmamalı
- `mod_news_category` yanlışlıkla `mod_category` izinleriyle çalışmamalı
- eski modüller yeni modüllerin tablolarını kullanmamalı

## Faz 4: Veri Stratejisi

Burada iki seçenek vardır. Kod yazımından önce biri seçilmelidir.

### Seçenek A: Boş başlangıç

- `mod_news_category` boş tabloyla başlar
- `mod_news` boş tabloyla başlar
- içerik ve kategori verileri sonradan manuel girilir

Avantaj:

- en düşük veri riski

Dezavantaj:

- admin tarafında test için örnek veri gerekir

### Seçenek B: Başlangıç kopyası

- `mod_category` verileri `mod_news_category` tablosuna kopyalanır
- `mod_content` verileri `mod_news` tablosuna kopyalanır
- `mod_news.category_id` değerleri yeni kategori ID'lerine göre eşlenir

Avantaj:

- yeni modül ilk günden dolu gelir

Dezavantaj:

- veri eşleme script'i gerekir
- kategori ID eşleme hatası riski vardır

Bu proje için teknik olarak en güvenli yol:

- önce kodu ayağa kaldırmak
- sonra istenirse veri taşıma script'i yazmak

Yani varsayılan plan `Seçenek A` olmalıdır.

## Faz 5: Doğrulama Planı

### `mod_news_category`

- admin listesi açılıyor mu
- ekleme kaydı DB'ye gidiyor mu
- düzenleme çalışıyor mu
- silme ve sıralama çalışıyor mu
- nested kategori yapısı korunuyor mu

### `mod_news`

- admin listesi açılıyor mu
- ekleme formunda `mod_news_category` verileri geliyor mu
- kayıt sırasında `category_id` doğru tabloya bağlı mı
- edit ekranı doğru veriyi çekiyor mu
- galeri / dosya ilişki modülleri yeni modül adıyla çalışıyor mu
- permission kontrolleri doğru modül anahtarıyla çalışıyor mu

### Frontend

- haber listesi açılıyor mu
- tekil haber sayfası açılıyor mu
- kategori filtreleme doğru çalışıyor mu
- ilgili içerik / arşiv sorguları yanlış modüle düşmüyor mu

## Uygulama Sırası

1. `mod_news_category` klasörlerini ve installer dosyalarını üret.
2. `mod_news_category` sınıf, dil, SQL ve template referanslarını ayrıştır.
3. `mod_news_category` admin akışını doğrula.
4. `mod_news` klasörlerini ve installer dosyalarını üret.
5. `mod_news` sınıf, dil, SQL ve template referanslarını ayrıştır.
6. `mod_news` içindeki tüm kategori bağımlılıklarını `mod_news_category`'ye çevir.
7. medya, izin, menü ve araç modülü ilişkilerini düzelt.
8. admin akışlarını doğrula.
9. frontend akışlarını doğrula.
10. gerekiyorsa ayrı veri taşıma script'i yaz.

## Tahmini Riskler

- string tabanlı referansların bir kısmının gözden kaçması
- template yolunun eski modüle kalması
- SQL join dosyalarında `mod_category` veya `mod_content` adının unutulması
- installer yükleme sırasının eksik kalması
- `mod_user` veya yönetim paneli linklerinin yeni modülleri göstermemesi
- frontend helper'ların eski kategori kaynağını kullanmaya devam etmesi

## Karar Özeti

Bu iş doğrudan rename olarak yapılmayacak.

Uygulanacak yöntem:

- `mod_category` korunacak, `mod_news_category` türetilecek
- `mod_content` korunacak, `mod_news` türetilecek
- önce kategori modülü üretilecek
- sonra içerik modülü buna bağlanacak
- yeni özellik eklenmeyecek
- veri taşıma koddan ayrı, ikinci adım olarak ele alınacak

Bu plan; risk, doğrulanabilirlik, geri dönüş kolaylığı ve toplam efor açısından en dengeli yaklaşımdır.
