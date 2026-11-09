<?php
/**
 * Task: Seed Google News Statutory & Transparency Pages (E-E-A-T)
 *
 * Amed Framework / Bulletin CMS
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../init.php';

$db = new Database();

$pagesToSeed = [
    [
        'lang' => 'tr',
        'name' => 'Doğrulama ve Teyit Politikası',
        'parent_id' => 18,
        'order' => 2,
        'root' => 'n',
        'status' => 'y',
        'deck' => 'y',
        'summary' => 'Amed Bülten haber doğrulama ve teyit protokolü; çok kaynaklı araştırma, birincil belge denetimi, manipülatif içerik tespiti ve bağımsız fact-checking ilkeleri.',
        'content' => '<h2>Doğrulama ve Teyit (Fact-Checking) Politikası</h2>
<p><strong>Amed Bülten</strong> (<code>amedbulten.com</code>), Diyarbakır, bölge, Türkiye ve dünya gündemine ilişkin yayımladığı tüm haber, araştırma ve dosyalarda gerçeğin saptanması ve kamuoyuna doğru aktarılmasını mutlak editoryal öncelik kabul eder.</p>
<h3>1. Çok Kaynaklı Doğrulama Standardı (Two-Source Rule)</h3>
<p>Yazı işlerimize ulaşan iddia, duyum, ihbar ve sosyal medya içerikleri, en az iki bağımsız ve güvenilir birincil kaynak tarafından doğrulanmadıkça kesinleşmiş haber formatında yayımlanmaz. Çelişkili bilgi veya tek taraflı iddiaların kamu yararı gereği haberleştirilmesi durumunda, iddia sahibi ile muhatapların görüşleri dengeli biçimde metinde belirtilir ve nihai teyit durumu şeffaflıkla okuyucuya aktarılır.</p>
<h3>2. Birincil Kaynak ve Resmî Belge Önceliği</h3>
<p>Adli soruşturmalar, kamu ihaleleri, kaza ve asayiş olayları, ekonomik göstergeler ve kurumsal kararlara ilişkin haberlerde yalnızca ikincil aktarımlara güvenilmez; resmî tutanaklar, mahkeme kararları, resmî gazete ilanları ve yetkili kurum açıklamaları asıl dayanak olarak incelenir.</p>
<h3>3. Görsel ve Dijital Medya Doğrulaması</h3>
<p>Haberlerde kullanılan fotoğraf, video ve ses kayıtları; çekim yeri, tarihi, hava durumu koşulları ve tersine görsel arama (reverse image search) araçlarıyla dijital manipülasyon ve bağlam koparma (out-of-context) riskine karşı taranır. Doğrulanamayan veya olaya ait olmayan görseller asla gerçekmiş gibi sunulmaz; kaynak görsel bulunmadığında kullanılan temsili görseller "Fotoğraf: Temsili" ibaresiyle net şekilde etiketlenir.</p>
<h3>4. Doğrulama Sürecinde Yapay Zeka Desteği</h3>
<p>Büyük veri kümelerinin taranması ve çapraz referans eşleştirmelerinde yardımcı analitik araçlardan yararlanılabilir; ancak bilginin doğrulanması, anlamlandırılması ve editoryal süzgeçten geçirilmesi doğrudan insan gazetecilerin ve tecrübeli editörlerimizin sorumluluğundadır.</p>',
        'gallery_id' => 0,
        'file_id' => 0,
        'seo' => 'Doğrulama_ve_Teyit_Politikası',
        'title' => 'Doğrulama ve Teyit Politikası',
        'keywords' => 'Doğrulama ve Teyit Politikası, fact checking, haber doğrulama, teyit ilkeleri, Amed Bülten doğrulama',
        'description' => 'Amed Bülten haber doğrulama ve teyit protokolü; çok kaynaklı araştırma, birincil belge denetimi, manipülatif içerik tespiti ve bağımsız fact-checking ilkeleri.',
        'image' => null,
        'acl_id' => 0,
        'category_id' => 0,
        'menu' => 'y',
        'user_id' => 1,
        'view_count' => 0,
    ],
    [
        'lang' => 'tr',
        'name' => 'Düzeltme ve Tekzip Politikası',
        'parent_id' => 18,
        'order' => 3,
        'root' => 'n',
        'status' => 'y',
        'deck' => 'y',
        'summary' => 'Amed Bülten maddi hata düzeltme süreçleri, editoryal güncelleme bildirimleri, tekzip başvuruları ve şeffaf düzeltme mekanizması prosedürleri.',
        'content' => '<h2>Düzeltme, Güncelleme ve Tekzip Politikası</h2>
<p><strong>Amed Bülten</strong>, gazetecilik faaliyetlerinde şeffaflık, dürüstlük ve okuyucuya karşı hesap verebilirlik ilkelerini esas alır. Yayınlanan içeriklerde hata yapıldığında bunu gizlemek yerine açıkça düzeltmek editoryal onurumuzun gereğidir.</p>
<h3>1. Maddi Hataların Düzeltilmesi ve Güncelleme Notu</h3>
<p>Yayımlanan bir haberde isim, unvan, tarih, sayısal veri, alıntı veya olgu hatası tespit edildiğinde; editörlerimiz içeriği derhal düzeltir. Haberin özünü veya akışını etkileyen düzeltmelerde, haber metninin en altında tarih ve saat içeren açık bir <strong>"Düzeltme ve Güncelleme Notu"</strong> yayımlanır. Yazım kuralları veya imla düzeltmeleri gibi anlamı değiştirmeyen teknik müdahaleler hariç olmak üzere hiçbir haber gizlice veya gerekçesiz değiştirilemez.</p>
<h3>2. Düzeltme Bildirim Mekanizması</h3>
<p>Okuyucularımız, haber kaynaklarımız veya ilgililer, haberlerde yer alan herhangi bir maddi hatayı <strong>info@amedbulten.com</strong> adresi üzerinden doğrudan Yayın Kurulu\'na iletebilir. Bildirilen hususlar en geç 24 saat içinde teyit edilir ve hata saptanırsa derhal gerekli düzeltme uygulanır.</p>
<h3>3. Yasal Cevap ve Düzeltme (Tekzip) Prosedürü</h3>
<p>5187 sayılı Basın Kanunu ve 5651 sayılı İnternet Ortamında Yapılan Yayınların Düzenlenmesi ve Bu Yayınlar Yoluyla İşlenen Suçlarla Mücadele Edilmesi Hakkında Kanun uyarınca, yayınlarımız nedeniyle kişilik hakları, şeref ve haysiyeti zedelenen ya da gerçeğe aykırı yayınlara maruz kalan gerçek ve tüzel kişilerin cevap ve düzeltme (tekzip) hakkı saklıdır.</p>
<p>Yetkili merci veya noter kanalıyla veya kurumsal iletişim adreslerimize ulaştırılan tekzip metinleri, kanuni süreler ve usul kuralları çerçevesinde, ilgili yayının yapıldığı sayfada veya ana sayfada aynı puntolarla ve eksiksiz olarak yayımlanır.</p>',
        'gallery_id' => 0,
        'file_id' => 0,
        'seo' => 'Düzeltme_ve_Tekzip_Politikası',
        'title' => 'Düzeltme ve Tekzip Politikası',
        'keywords' => 'Düzeltme ve Tekzip Politikası, tekzip hakkı, maddi hata düzeltme, düzeltme notu, Amed Bülten tekzip',
        'description' => 'Amed Bülten maddi hata düzeltme süreçleri, editoryal güncelleme bildirimleri, tekzip başvuruları ve şeffaf düzeltme mekanizması prosedürleri.',
        'image' => null,
        'acl_id' => 0,
        'category_id' => 0,
        'menu' => 'y',
        'user_id' => 1,
        'view_count' => 0,
    ],
    [
        'lang' => 'tr',
        'name' => 'Mülkiyet ve Finansman Yapısı',
        'parent_id' => 18,
        'order' => 4,
        'root' => 'n',
        'status' => 'y',
        'deck' => 'y',
        'summary' => 'Amed Bülten tüzel kişilik yapısı, imtiyaz sahibi, gelir kaynakları ve editoryal bağımsızlığı teminat altına alan mülkiyet ve finansman şeffaflığı beyanı.',
        'content' => '<h2>Mülkiyet, Finansman ve Kurumsal Şeffaflık</h2>
<p><strong>Amed Bülten</strong> (<code>amedbulten.com</code>), bağımsız dijital habercilik anlayışıyla yayın hayatını sürdüren tarafsız ve çoğulcu bir basın organıdır. Okuyucularımıza ve kamuoyuna karşı şeffaflık gereği mülkiyet yapımız ve gelir kaynaklarımız açıkça beyan edilir.</p>
<h3>1. Tüzel Kişilik ve Yayın Sahibi</h3>
<p>Amed Bülten\'in tescilli imtiyaz sahibi ve kurucusu <strong>AKM Medya / AKM Software</strong> bünyesinde faaliyet gösteren tüzel kişilik olup, yayın organının yasal temsilcileri, genel yayın yönetmeni, sorumlu yazı işleri müdürü ve kurumsal sicil bilgileri resmi <a href="/tr/legal">Künye / Yasal Bilgiler</a> sayfamızda güncel olarak ilan edilmektedir.</p>
<h3>2. Gelir Kaynakları ve Finansman Modeli</h3>
<p>Amed Bülten, yayın faaliyetlerinin sürdürülebilirliğini aşağıdaki şeffaf gelir kalemleriyle sağlar:
<ul>
  <li>Programatik dijital reklamlar ve görüntülü reklam ağları (Google AdSense ve yetkili reklam ortakları),</li>
  <li>Yerel ve ulusal kurumların Basın İlan Kurumu ve ticari standartlara uygun doğrudan sponsorluk ve banner duyuruları,</li>
  <li>Açıkça etiketlenmiş advertorial / tanıtım yazıları (bu tür içerikler okuyucuyu yanıltmamak adına "Sponsorlu İçerik" veya "Tanıtım" ibaresiyle işaretlenir).</li>
</ul>
</p>
<h3>3. Editoryal Bağımsızlık Güvencesi</h3>
<p>Amed Bülten; hiçbir siyasi parti, sivil toplum kuruluşu, cemaat, fon sağlayıcı veya devlet kurumundan doğrudan sübvansiyon, koşullu bağış veya güdümlü fon kabul etmez. Reklam verenler ve sponsorlar, Amed Bülten\'in haber içeriklerine, başlıklarına, editoryal çizgisine veya araştırma konularına hiçbir surette müdahale edemez. Reklam yönetimi ile haber merkezi arasında aşılmaz bir editoryal güvenlik duvarı (firewall) mevcuttur.</p>',
        'gallery_id' => 0,
        'file_id' => 0,
        'seo' => 'Mülkiyet_ve_Finansman_Yapısı',
        'title' => 'Mülkiyet ve Finansman Yapısı',
        'keywords' => 'Mülkiyet ve Finansman Yapısı, imtiyaz sahibi, editoryal bağımsızlık, gelir modeli, Amed Bülten şeffaflık',
        'description' => 'Amed Bülten tüzel kişilik yapısı, imtiyaz sahibi, gelir kaynakları ve editoryal bağımsızlığı teminat altına alan mülkiyet ve finansman şeffaflığı beyanı.',
        'image' => null,
        'acl_id' => 0,
        'category_id' => 0,
        'menu' => 'y',
        'user_id' => 1,
        'view_count' => 0,
    ],
];

foreach ($pagesToSeed as $pageData) {
    $existing = Load::model('mod_page');
    $existing->flush();
    $existing->where_seo_and_lang($pageData['seo'], $pageData['lang'])->gRo();

    if ($existing->getSize() > 0) {
        echo "[EXISTS] Page already exists: {$pageData['name']} ({$pageData['seo']})\n";
        continue;
    }

    $pageModel = Load::model('mod_page');
    $pageModel->flush();
    foreach ($pageData as $key => $val) {
        $pageModel->$key = $val;
    }

    if ($pageModel->save()) {
        echo "[CREATED] Successfully created: {$pageData['name']} ({$pageData['seo']})\n";
    } else {
        echo "[FAILED] Failed to create: {$pageData['name']} ({$pageData['seo']})\n";
    }
}

echo "Seed complete.\n";
