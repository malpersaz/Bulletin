<?php

class mod_news extends installerbase implements installerbaseinterface
{
    public $tablolar;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_news';
        $this->tablolar = array(
            'mod_news',
            'mod_news_contents'
        );
        $this->rolls = array(
            'add', 'edit',  'delete', 'list','gallery','file','edit_status','change_status','approval', 'moderate_edit','moderate_delete','video_download'
        );
    }

    public function install()
    {
        // Tabloları Oluşturalım
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        // Modülü Ekleyelim
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        // Roller ve Permleri Ekleyelim
        if ($this->insert_roll() === FALSE) {
            return FALSE;
        }
        // Örnek Haberleri Ekleyelim
        if ($this->addcontent() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    public function uninstall()
    {

        $this->remove_tables();
        $this->remove_mods();
        $this->remove_rool();
        $this->remove_perm();
        return TRUE;
    }

    public function addcontent()
    {
        $veri = array(
            // Turkish News
            array(
                'name' => 'Örnek Haber',
                'lang' => 'tr',
                'category_id' => '10',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Rojname altyapısında yayınlanan bu örnek haber, haber modülünün listeleme, detay ve vitrin alanlarını hızlıca doğrulamak için hazırlanmıştır.',
                'content' => '<p>Bu örnek haber, Rojname kurulumundan hemen sonra haber akışının doğru çalıştığını göstermek için otomatik olarak oluşturulur.</p>
                <p><strong>Bu örnek haber ile test edebilecekleriniz:</strong></p>
                    <ul>
                        <li>Anasayfa vitrin gösterimi</li>
                        <li>Haber listeleme sayfası</li>
                        <li>Haber detay bağlantıları</li>
                        <li>Kategori bazlı filtreleme</li>
                        <li>SEO dostu haber URL yapısı</li>
                    </ul>
                <p>Kurulum sonrasında bu haberi düzenleyebilir, silebilir ya da kendi gerçek haber içeriklerinizle değiştirebilirsiniz.</p>',
                'seo' => 'ornek-haber',
                'title' => 'Örnek Haber - Rojname',
                'keywords' => 'örnek haber, haber modülü, akm business cms, haber yönetimi',
                'description' => 'Haber modülünün kurulumla gelen örnek haberi.',
                'user_id' => '1'
            ),
            // English News
            array(
                'name' => 'Sample News',
                'lang' => 'en',
                'category_id' => '11',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'This sample news entry is generated during installation to verify the listing, detail, and featured sections of the news module.',
                'content' => '<p>This sample news article is created automatically after installation so you can confirm that the news flow is working end to end.</p>
<p><strong>You can use it to test:</strong></p>
<ul>
<li>Homepage featured news output</li>
<li>News listing pages</li>
<li>News detail links</li>
<li>Category-based filtering</li>
<li>SEO-friendly news URLs</li>
</ul>
<p>After setup, you can edit or remove this record and replace it with your own newsroom content.</p>',
                'seo' => 'sample-news',
                'title' => 'Sample News - Rojname',
                'keywords' => 'sample news, news module, akm business cms, news management',
                'description' => 'The sample news entry installed with the news module.',
                'user_id' => '1'
            ),
            // Arabic News
            array(
                'name' => 'خبر نموذجي',
                'lang' => 'ar',
                'category_id' => '12',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'تم إنشاء هذا الخبر النموذجي أثناء التثبيت للتحقق بسرعة من صفحات القائمة والتفاصيل والعرض المميز في وحدة الأخبار.',
                'content' => '<p>يتم إنشاء هذا الخبر النموذجي تلقائيًا بعد التثبيت حتى تتمكن من التأكد من أن تدفق الأخبار يعمل بشكل صحيح.</p>
<p><strong>يمكنك استخدامه لاختبار:</strong></p>
<ul>
<li>إظهار الخبر المميز في الصفحة الرئيسية</li>
<li>صفحات قائمة الأخبار</li>
<li>روابط تفاصيل الأخبار</li>
<li>التصفية حسب الفئة</li>
<li>روابط أخبار صديقة لمحركات البحث</li>
</ul>
<p>بعد الإعداد، يمكنك تعديل هذا السجل أو حذفه واستبداله بأخبارك الفعلية.</p>',
                'seo' => 'خبر-نموذجي',
                'title' => 'خبر نموذجي - Rojname',
                'keywords' => 'خبر نموذجي، وحدة الأخبار، akm business cms، إدارة الأخبار',
                'description' => 'الخبر النموذجي المثبت مع وحدة الأخبار.',
                'user_id' => '1'
            ),
            // German News
            array(
                'name' => 'Beispielnachricht',
                'lang' => 'de',
                'category_id' => '13',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Diese Beispielnachricht wird bei der Installation angelegt, um Listen-, Detail- und Startseitenbereiche des News-Moduls schnell zu pruefen.',
                'content' => '<p>Diese Beispielnachricht wird nach der Installation automatisch erstellt, damit Sie den kompletten Nachrichtenfluss direkt testen koennen.</p>
<p><strong>Damit koennen Sie pruefen:</strong></p>
<ul>
<li>Ausgabe hervorgehobener Nachrichten auf der Startseite</li>
<li>Nachrichtenlisten</li>
<li>Detailseiten und Verlinkungen</li>
<li>Kategoriebasierte Filterung</li>
<li>SEO-freundliche Nachrichten-URLs</li>
</ul>
<p>Nach dem Setup koennen Sie diesen Datensatz bearbeiten, loeschen oder durch Ihre eigenen Nachrichten ersetzen.</p>',
                'seo' => 'beispielnachricht',
                'title' => 'Beispielnachricht - Rojname',
                'keywords' => 'Beispielnachricht, News-Modul, akm business cms, Nachrichtenverwaltung',
                'description' => 'Die Beispielnachricht des News-Moduls.',
                'user_id' => '1'
            ),
            // French News
            array(
                'name' => 'Actualite d\'exemple',
                'lang' => 'fr',
                'category_id' => '14',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Cette actualite d\'exemple est creee pendant l\'installation pour verifier rapidement les vues liste, detail et mise en avant du module news.',
                'content' => '<p>Cette actualite d\'exemple est creee automatiquement apres l\'installation afin de valider le bon fonctionnement du flux de news.</p>
                <p><strong>Vous pouvez l\'utiliser pour tester :</strong></p>
                    <ul>
                        <li>L\'affichage en une sur la page d\'accueil</li>
                        <li>Les pages de liste des actualites</li>
                        <li>Les liens vers les pages detail</li>
                        <li>Le filtrage par categorie</li>
                        <li>Les URL d\'actualite optimisees pour le SEO</li>
                    </ul>
                <p>Apres l\'installation, vous pouvez modifier cette entree, la supprimer ou la remplacer par vos propres actualites.</p>',
                'seo' => 'actualite-exemple',
                'title' => 'Actualite d\'exemple - Rojname',
                'keywords' => 'actualite d\'exemple, module news, akm business cms, gestion des actualites',
                'description' => 'L\'actualite d\'exemple installee avec le module news.',
                'user_id' => '1'
            ),
            // Kurdish Kurmanci News
            array(
                'name' => 'Nuceya Mînak',
                'lang' => 'ku',
                'category_id' => '15',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Ev nuceya mînak di dema sazkirinê de tê afirandin da ku rûpelên lîste, detay û pêşnişandanê yên modula nûçeyan were ceribandin.',
                'content' => '<p>Ev nuceya mînak piştî sazkirinê bi awayekî otomatîk tê çêkirin da ku tu bikaribî tevahiya rêça nûçeyan ceribandî.</p>
                <p><strong>Bi vê re tu dikarî van tiştan biceribînî:</strong></p>
                    <ul>
                        <li>Nîşandana nuceya pêşerojî li rûpela sereke</li>
                        <li>Rûpelên lîsteya nûçeyan</li>
                        <li>Girêdanên detayê yên nûçeyan</li>
                        <li>Parzûnkirina li gorî kategoriyê</li>
                        <li>URL-yên nûçeyan yên hevalbend bi SEO re</li>
                    </ul>
                <p>Piştî sazkirinê tu dikarî vê qeydkirinê biguherînî, jê bibî an jî bi nûçeyên xwe yên rastîn bide guhertin.</p>',
                'seo' => 'nuceya-minak',
                'title' => 'Nuceya Mînak - Rojname',
                'keywords' => 'nuceya mînak, modula nûçeyan, akm business cms, rêveberiya nûçeyan',
                'description' => 'Nuceya mînak a ku bi modula nûçeyan re tê sazkirin.',
                'user_id' => '1'
            ),
            // Kurdish Sorani News
            array(
                'name' => 'هەواڵی نموونە',
                'lang' => 'ckb',
                'category_id' => '16',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'ئەم هەواڵەی نموونەیە لە کاتی دامەزراندندا دروست دەبێت بۆ ئەوەی پەڕەکانی لیست و وردەکاری و پیشاندانی سەرەکیی مۆدیولی هەواڵ تاقی بکرێنەوە.',
                'content' => '<p>ئەم هەواڵەی نموونەیە دوای دامەزراندن بە شێوەی ئۆتۆماتیکی دروست دەبێت بۆ ئەوەی دڵنیابیت لەوەی ڕێڕەوی هەواڵ بە تەواوی کار دەکات.</p>
<p><strong>دەتوانیت بەمە ئەمانە تاقی بکەیتەوە:</strong></p>
<ul>
<li>پیشاندانی هەواڵی تایبەت لە لاپەڕەی سەرەکی</li>
<li>لاپەڕەکانی لیستی هەواڵ</li>
<li>بەستەری وردەکاری هەواڵ</li>
<li>فلتەرکردن بە پێی هاوپۆل</li>
<li>بەستەری SEO بۆ هەواڵ</li>
</ul>
<p>دوای دامەزراندن دەتوانیت ئەم تۆمارە بگۆڕیت، بیسڕیتەوە یان بە هەواڵە ڕاستەقینەکانی خۆت بیگۆڕیت.</p>',
                'seo' => 'هەواڵی-نموونە',
                'title' => 'هەواڵی نموونە - Rojname',
                'keywords' => 'هەواڵی نموونە، مۆدیولی هەواڵ، akm business cms، بەڕێوەبردنی هەواڵ',
                'description' => 'هەواڵی نموونەی دامەزراو لەگەڵ مۆدیولی هەواڵ.',
                'user_id' => '1'
            ),
            // Swedish News
            array(
                'name' => 'Exempelnyhet',
                'lang' => 'sv',
                'category_id' => '17',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Den här exempelnyheten skapas vid installationen för att snabbt verifiera listor, detaljsidor och utvald visning i nyhetsmodulen.',
                'content' => '<p>Den här exempelnyheten skapas automatiskt efter installationen sa att du direkt kan kontrollera att hela nyhetsflodet fungerar.</p>
<p><strong>Du kan testa:</strong></p>
<ul>
<li>Utvald nyhet pa startsidan</li>
<li>Nyhetslistor</li>
<li>Lankar till detaljsidor</li>
<li>Kategoribaserad filtrering</li>
<li>SEO-vanliga nyhetsadresser</li>
</ul>
<p>Efter installationen kan du redigera posten, ta bort den eller ersatta den med dina egna nyheter.</p>',
                'seo' => 'exempelnyhet',
                'title' => 'Exempelnyhet - Rojname',
                'keywords' => 'exempelnyhet, nyhetsmodul, akm business cms, nyhetshantering',
                'description' => 'Exempelnyheten som installeras med nyhetsmodulen.',
                'user_id' => '1'
            ),
            // Zazaki News
            array(
                'name' => 'Heberê Nimûne',
                'lang' => 'zza',
                'category_id' => '18',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Ena heberê nimûne dema sazkirışi de tê goşterişnayış bo ke liste, detay û cayi vitrînê modulê heberan bête testkerdene.',
                'content' => '<p>Ena heberê nimûne piştî sazkirışi otomatik vîrazena bo ke şıma bıvîni ke rêça heberan temam xebitena.</p>
                <p><strong>Şıma bi nê test kerê:</strong></p>
                    <ul>
                        <li>Nişandışê heberê sero de rûpelê sereke</li>
                        <li>Rûpelê listeyê heberan</li>
                        <li>Gıreyên detayê heberan</li>
                        <li>Parzûnkerdış gorey kategoriyan</li>
                        <li>URL-yê heberanê gorey SEO</li>
                    </ul>
                <p>Piştî sazkirışi şıma şert o ke nê qeyd biguherênê, bıwazênê ya zi heberanê xo ra bıvurnê.</p>',
                'seo' => 'hebere-nimune',
                'title' => 'Heberê Nimûne - Rojname',
                'keywords' => 'heberê nimûne, modulê heberan, akm business cms, idarekerdışê heberan',
                'description' => 'Heberê nimûne ke bi modulê heberan ra tê sazkirış.',
                'user_id' => '1'
            )
        );
        
        $this->model = Load::model('mod_news');
        foreach ($veri AS $key => $value){
            if ($this->model->exists_name($value['name']) === FALSE){
                $this->oke($value['name']." Eklendi");
                $keys = array_keys($value);
                foreach ($keys AS $k){
                    $this->model->$k = $value[$k];
                }
                if ($this->model->save()===FALSE){
                    $this->error($value['name']." Eklenirken hata oluştu");
                    return FALSE;
                }
                continue;
            }
            else
            {
                $this->warning($value['name']." daha önce eklenmiş");
            }
        }
        return TRUE;
    }

}
