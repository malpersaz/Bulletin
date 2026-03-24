<?php

class mod_content extends installerbase implements installerbaseinterface
{
    public $tablolar;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_content';
        $this->tablolar = array(
            'mod_content',
            'contents'
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
        // Örnek İçerikleri Ekleyelim
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
            // Turkish Content
            array(
                'name' => 'Örnek İçerik',
                'lang' => 'tr',
                'category_id' => '10',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS, modern web siteleri oluşturmak için geliştirilmiş kapsamlı bir içerik yönetim sistemidir. Kullanıcı dostu arayüzü ve güçlü özellikleri ile web dünyasında hak ettiğiniz yere taşımanızı sağlar.',
                'content' => '<p>AKM Business CMS, modern web siteleri oluşturmak için geliştirilmiş kapsamlı bir içerik yönetim sistemidir. Kullanıcı dostu arayüzü ve güçlü özellikleri ile web dünyasında hak ettiğiniz yere taşımanızı sağlar.</p>
                <p><strong>Öne Çıkan Özellikler:</strong></p>
                    <ul>
                        <li>Çoklu dil desteği</li>
                        <li>Esnek içerik yönetimi</li>
                        <li>SEO optimizasyonu</li>
                        <li>Güvenli altyapı</li>
                        <li>Mobil uyumlu tasarım</li>
                    </ul>
                <p>AKM Business CMS sizi web dünyasında hakettiniz yere taşımak için var. Profesyonel çözümlerimizle işletmenizi dijital dünyada öne çıkarın.</p>',
                'seo' => 'ornek-icerik',
                'title' => 'Örnek İçerik - AKM Business CMS',
                'keywords' => 'örnek içerik, akm business cms, web sitesi, içerik yönetimi',
                'description' => 'AKM Business CMS sizi web dünyasında hakettiniz yere taşımak için var.',
                'user_id' => '1'
            ),
            // English Content
            array(
                'name' => 'Sample Content',
                'lang' => 'en',
                'category_id' => '11',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS is a comprehensive content management system developed for creating modern websites. With its user-friendly interface and powerful features, it helps you reach your deserved place in the web world.',
                'content' => '<p>AKM Business CMS is a comprehensive content management system developed for creating modern websites. With its user-friendly interface and powerful features, it helps you reach your deserved place in the web world.</p>
<p><strong>Featured Features:</strong></p>
<ul>
<li>Multi-language support</li>
<li>Flexible content management</li>
<li>SEO optimization</li>
<li>Secure infrastructure</li>
<li>Mobile-responsive design</li>
</ul>
<p>AKM Business CMS exists to take you to your deserved place in the web world. Stand out in the digital world with our professional solutions.</p>',
                'seo' => 'sample-content',
                'title' => 'Sample Content - AKM Business CMS',
                'keywords' => 'sample content, akm business cms, website, content management',
                'description' => 'AKM Business CMS exists to take you to your deserved place in the web world.',
                'user_id' => '1'
            ),
            // Arabic content
            array(
                'name' => 'محتوى نموذجي',
                'lang' => 'ar',
                'category_id' => '12',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS هو نظام إدارة محتوى شامل تم تطويره لإنشاء مواقع الويب الحديثة. بواجهته سهلة الاستخدام وميزاته القوية، يساعدك على الوصول إلى مكانك المستحق في عالم الويب.',
                'content' => '<p>AKM Business CMS هو نظام إدارة محتوى شامل تم تطويره لإنشاء مواقع الويب الحديثة. بواجهته سهلة الاستخدام وميزاته القوية، يساعدك على الوصول إلى مكانك المستحق في عالم الويب.</p>
<p><strong>الميزات البارزة:</strong></p>
<ul>
<li>دعم متعدد اللغات</li>
<li>إدارة محتوى مرنة</li>
<li>تحسين محركات البحث</li>
<li>بنية تحتية آمنة</li>
<li>تصميم متجاوب مع الجوال</li>
</ul>
<p>AKM Business CMS موجود لنقلك إلى مكانك المستحق في عالم الويب. تميز في العالم الرقمي بحلولنا الاحترافية.</p>',
                'seo' => 'محتوى-نموذجي',
                'title' => 'محتوى نموذجي - AKM Business CMS',
                'keywords' => 'محتوى نموذجي، akm business cms، موقع ويب، إدارة المحتوى',
                'description' => 'AKM Business CMS موجود لنقلك إلى مكانك المستحق في عالم الويب.',
                'user_id' => '1'
            ),
            // German Content
            array(
                'name' => 'Beispielinhalt',
                'lang' => 'de',
                'category_id' => '13',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS ist ein umfassendes Content-Management-System, das für die Erstellung moderner Websites entwickelt wurde. Mit seiner benutzerfreundlichen Oberfläche und leistungsstarken Funktionen hilft es Ihnen, Ihren verdienten Platz in der Webwelt zu erreichen.',
                'content' => '<p>AKM Business CMS ist ein umfassendes Content-Management-System, das für die Erstellung moderner Websites entwickelt wurde. Mit seiner benutzerfreundlichen Oberfläche und leistungsstarken Funktionen hilft es Ihnen, Ihren verdienten Platz in der Webwelt zu erreichen.</p>
<p><strong>Hervorgehobene Funktionen:</strong></p>
<ul>
<li>Mehrsprachige Unterstützung</li>
<li>Flexible Inhaltsverwaltung</li>
<li>SEO-Optimierung</li>
<li>Sichere Infrastruktur</li>
<li>Mobiles responsives Design</li>
</ul>
<p>AKM Business CMS existiert, um Sie an Ihren verdienten Platz in der Webwelt zu bringen. Heben Sie sich in der digitalen Welt mit unseren professionellen Lösungen hervor.</p>',
                'seo' => 'beispielinhalt',
                'title' => 'Beispielinhalt - AKM Business CMS',
                'keywords' => 'Beispielinhalt, akm business cms, Website, Content-Management',
                'description' => 'AKM Business CMS existiert, um Sie an Ihren verdienten Platz in der Webwelt zu bringen.',
                'user_id' => '1'
            ),
            // French Content
            array(
                'name' => 'Contenu d\'exemple',
                'lang' => 'fr',
                'category_id' => '14',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS est un système de gestion de contenu complet développé pour créer des sites web modernes. Avec son interface conviviale et ses fonctionnalités puissantes, il vous aide à atteindre votre place méritée dans le monde du web.',
                'content' => '<p>AKM Business CMS est un système de gestion de contenu complet développé pour créer des sites web modernes. Avec son interface conviviale et ses fonctionnalités puissantes, il vous aide à atteindre votre place méritée dans le monde du web.</p>
                <p><strong>Fonctionnalités en vedette :</strong></p>
                    <ul>
                        <li>Support multilingue</li>
                        <li>Gestion de contenu flexible</li>
                        <li>Optimisation SEO</li>
                        <li>Infrastructure sécurisée</li>
                        <li>Design responsive mobile</li>
                    </ul>
                <p>AKM Business CMS existe pour vous amener à votre place méritée dans le monde du web. Mettez-vous en avant dans le monde numérique avec nos solutions professionnelles.</p>',
                'seo' => 'contenu-exemple',
                'title' => 'Contenu d\'exemple - AKM Business CMS',
                'keywords' => 'contenu d\'exemple, akm business cms, site web, gestion de contenu',
                'description' => 'AKM Business CMS existe pour vous amener à votre place méritée dans le monde du web.',
                'user_id' => '1'
            ),
            // Kurdish Kurmancî Content
            array(
                'name' => 'Naveroka Mînak',
                'lang' => 'ku',
                'category_id' => '15',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS pergaleke rêveberiya naverokê ya berfireh e ku ji bo afirandina malperên nûjen hatî pêşve xistin. Bi navrûa bikarhêner-hezkir û xwediyên xwe yên hêzdar, dibe alîkar ku tu bigihîjî cihê xwe yê li cîhana webê.',
                'content' => '<p>AKM Business CMS pergaleke rêveberiya naverokê ya berfireh e ku ji bo afirandina malperên nûjen hatî pêşve xistin. Bi navrûa bikarhêner-hezkir û xwediyên xwe yên hêzdar, dibe alîkar ku tu bigihîjî cihê xwe yê li cîhana webê.</p>
                <p><strong>Xwediyên balkêş:</strong></p>
                    <ul>
                        <li>Piştgiriya zimanên pirjimar</li>
                        <li>Rêveberiya naverokê ya fleksîbel</li>
                        <li>Optîmîzasyona SEO</li>
                        <li>Altyapî ya ewle</li>
                        <li>Dîzayna mobîl-a bersivdar</li>
                    </ul>
                <p>AKM Business CMS heye ku tu bigihîjî cihê xwe yê li cîhana webê. Bi çareseriya profesyonelên me xwe di cîhana dîjîtal de deynin pêş.</p>',
                'seo' => 'naveroka-mînak',
                'title' => 'Naveroka Mînak - AKM Business CMS',
                'keywords' => 'naveroka mînak, akm business cms, malper, rêveberiya naverokê',
                'description' => 'AKM Business CMS heye ku tu bigihîjî cihê xwe yê li cîhana webê.',
                'user_id' => '1'
            ),
            // Kurdish Soranî Content
            array(
                'name' => 'ناوەڕۆکی نموونە',
                'lang' => 'ckb',
                'category_id' => '16',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS سیستەمێکی بەڕێوەبردنی ناوەڕۆکی گشتگیرە کە بۆ دروستکردنی ماڵپەڕە مۆدێرنەکان پەرەپێدراوە. بە ڕووکاری ئاسانی بەکارهێنان و تایبەتمەندیە بەهێزەکانی، یارمەتیت دەدات کە بگەیتە شوێنی شایستەت لە جیهانی وێبدا.',
                'content' => '<p>AKM Business CMS سیستەمێکی بەڕێوەبردنی ناوەڕۆکی گشتگیرە کە بۆ دروستکردنی ماڵپەڕە مۆدێرنەکان پەرەپێدراوە. بە ڕووکاری ئاسانی بەکارهێنان و تایبەتمەندیە بەهێزەکانی، یارمەتیت دەدات کە بگەیتە شوێنی شایستەت لە جیهانی وێبدا.</p>
<p><strong>تایبەتمەندیە سەرنجڕاکێشەکان:</strong></p>
<ul>
<li>پشتگیری فرەزمانی</li>
<li>بەڕێوەبردنی ناوەڕۆکی نەرم</li>
<li>باشکردنی SEO</li>
<li>ژێرخانی پارێزراو</li>
<li>دیزاینی وەڵامدەرەوەی مۆبایل</li>
</ul>
<p>AKM Business CMS بوونی هەیە بۆ ئەوەی تۆ بگەیەنێتە شوێنی شایستەت لە جیهانی وێبدا. لە جیهانی دیجیتاڵدا بە چارەسەرە پیشەیییەکانمان جیاوازبە.</p>',
                'seo' => 'ناوەڕۆکی-نموونە',
                'title' => 'ناوەڕۆکی نموونە - AKM Business CMS',
                'keywords' => 'ناوەڕۆکی نموونە، akm business cms، ماڵپەڕ، بەڕێوەبردنی ناوەڕۆک',
                'description' => 'AKM Business CMS بوونی هەیە بۆ ئەوەی تۆ بگەیەنێتە شوێنی شایستەت لە جیهانی وێبدا.',
                'user_id' => '1'
            ),
            // Swedish Content
            array(
                'name' => 'Exempelinnehåll',
                'lang' => 'sv',
                'category_id' => '17',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS är ett omfattande innehållshanteringssystem utvecklat för att skapa moderna webbplatser. Med sitt användarvänliga gränssnitt och kraftfulla funktioner hjälper det dig att nå din förtjänta plats i webbvärlden.',
                'content' => '<p>AKM Business CMS är ett omfattande innehållshanteringssystem utvecklat för att skapa moderna webbplatser. Med sitt användarvänliga gränssnitt och kraftfulla funktioner hjälper det dig att nå din förtjänta plats i webbvärlden.</p>
<p><strong>Utmärkta funktioner:</strong></p>
<ul>
<li>Flerspråkigt stöd</li>
<li>Flexibel innehållshantering</li>
<li>SEO-optimering</li>
<li>Säker infrastruktur</li>
<li>Mobilanpassad design</li>
</ul>
<p>AKM Business CMS finns för att ta dig till din förtjänta plats i webbvärlden. Stå ut i den digitala världen med våra professionella lösningar.</p>',
                'seo' => 'exempelinnehall',
                'title' => 'Exempelinnehåll - AKM Business CMS',
                'keywords' => 'exempelinnehåll, akm business cms, webbplats, innehållshantering',
                'description' => 'AKM Business CMS finns för att ta dig till din förtjänta plats i webbvärlden.',
                'user_id' => '1'
            ),
            // Zazaki Content
            array(
                'name' => 'Zerreyo Nimûne',
                'lang' => 'zza',
                'category_id' => '18',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS, yew sistemo averşiyaye yê idarekarda zerreyo ke seba viraştena malperanê modernan ameyo raverberdene. Bi interfaceê xo yo ke xebatkarî rê kîbar o û bi taybetmendiyanê xo yê quwetinan, şima resneno cayê şima yo heqkerde yê dınyaya webî.',
                'content' => '<p>AKM Business CMS, yew sistemo averşiyaye yê idarekarda zerreyo ke seba viraştena malperanê modernan ameyo raverberdene. Bi interfaceê xo yo ke xebatkarî rê kîbar o û bi taybetmendiyanê xo yê quwetinan, şima resneno cayê şima yo heqkerde yê dınyaya webî.</p>
                <p><strong>Taybetmendiyê ke Vejênê Pêş:</strong></p>
                    <ul>
                        <li>Paştdayışê zaf-ziwanî</li>
                        <li>İdarekerdena zerreyo elastîk</li>
                        <li>Optimîzasyonê SEO</li>
                        <li>Altyapiyo ewle</li>
                        <li>Dîzayno ke mobîlî rê munasib o</li>
                    </ul>
                <p>AKM Business CMS esta ke şima dınyaya webî de berê cayê şima yo heqkerde. Bi çareseriyanê ma yê profesyonelan dınyaya dîjîtale de karsaziya xo vejên pêş.</p>',
                'seo' => 'zerreyo-nimune',
                'title' => 'Zerreyo Nimûne - AKM Business CMS',
                'keywords' => 'zerreyo nimûne, akm business cms, malper, idarekarda zerreyî',
                'description' => 'AKM Business CMS esta ke şima dınyaya webî de berê cayê şima yo heqkerde.',
                'user_id' => '1'
            )
        );
        
        $this->model = Load::model('mod_content');
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