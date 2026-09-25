<?php

class mod_page extends installerbase implements installerbaseinterface
{
    public $tables;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_page';
        $this->tables = array(
            'mod_page'
        );
        $this->roles = array(
            'add', 'edit',  'delete', 'list', 'gallery','file', 'moderate_edit', 'moderate_delete'
        );
    }

    public function install()
    {
        // Create Tables
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        // Add Module
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        // Add Roles and Permissions
        if ($this->insert_roles() === FALSE) {
            return FALSE;
        }
        // Add Sample Pages
        if ($this->addpage() === FALSE) {
            return FALSE;
        }
        return TRUE;
    }

    public function addpage()
    {
        $data = array(
            // =============================================
            // Root Category — Per language
            // =============================================

            // 1 - English Root
            array(
                'name' => 'English Pages',
                'lang' => 'en',
                'parent_id' => '0',
                'seo' => 'English_Pages',
                'title' => 'English Pages',
                'keywords' => 'English Pages',
                'description' => 'English Pages',
                'root' => 'y',
                'user_id' => 1
            ),
            // 2 - Kurdish (Kurmancî) Root
            array(
                'name' => 'Rûpelên Kurdî',
                'lang' => 'ku',
                'parent_id' => '0',
                'seo' => 'Rûpelên_Kurdî',
                'title' => 'Rûpelên Kurdî',
                'keywords' => 'Rûpelên Kurdî',
                'description' => 'Rûpelên Kurdî',
                'root' => 'y',
                'user_id' => 1
            ),
            // 3 - Kurdish (Soranî) Root
            array(
                'name' => 'لاپەڕەکانی کوردی',
                'lang' => 'ckb',
                'parent_id' => '0',
                'seo' => 'لاپەڕەکانی_کوردی',
                'title' => 'لاپەڕەکانی کوردی',
                'keywords' => 'لاپەڕەکانی کوردی',
                'description' => 'لاپەڕەکانی کوردی',
                'root' => 'y',
                'user_id' => 1
            ),
            // 4 - Arabic Root
            array(
                'name' => 'الصفحات العربية',
                'lang' => 'ar',
                'parent_id' => '0',
                'seo' => 'الصفحات_العربية',
                'title' => 'الصفحات العربية',
                'keywords' => 'الصفحات العربية',
                'description' => 'الصفحات العربية',
                'root' => 'y',
                'user_id' => 1
            ),
            // 5 - German Root (de)
            array(
                'name' => 'Deutsche Seiten',
                'lang' => 'de',
                'parent_id' => '0',
                'seo' => 'Deutsche_Seiten',
                'title' => 'Deutsche Seiten',
                'keywords' => 'Deutsche Seiten',
                'description' => 'Deutsche Seiten',
                'root' => 'y',
                'user_id' => 1
            ),
            // 6 - French Root
            array(
                'name' => 'Pages Françaises',
                'lang' => 'fr',
                'parent_id' => '0',
                'seo' => 'Pages_Françaises',
                'title' => 'Pages Françaises',
                'keywords' => 'Pages Françaises',
                'description' => 'Pages Françaises',
                'root' => 'y',
                'user_id' => 1
            ),
            // 7 - Swedish Root
            array(
                'name' => 'Svenska Sidor',
                'lang' => 'sv',
                'parent_id' => '0',
                'seo' => 'Svenska_Sidor',
                'title' => 'Svenska Sidor',
                'keywords' => 'Svenska Sidor',
                'description' => 'Svenska Sidor',
                'root' => 'y',
                'user_id' => 1
            ),
            // 8 - Turkish Root
            array(
                'name' => 'Türkçe Sayfalar',
                'lang' => 'tr',
                'parent_id' => '0',
                'seo' => 'Türkçe_Sayfalar',
                'title' => 'Türkçe Sayfalar',
                'keywords' => 'Türkçe Sayfalar',
                'description' => 'Türkçe Sayfalar',
                'root' => 'y',
                'user_id' => 1
            ),
            // 9 - Zazakî Root
            array(
                'name' => 'Pelê Zazakî',
                'lang' => 'zza',
                'parent_id' => '0',
                'seo' => 'Pelê_Zazakî',
                'title' => 'Pelê Zazakî',
                'image' => 'SiHdJS38a62RN7G.webp',
                'root' => 'y',
                'user_id' => 1
            ),
            // 10 - Russian Root
            array(
                'name' => 'Русские Страницы',
                'lang' => 'ru',
                'parent_id' => '0',
                'seo' => 'Русские_Страницы',
                'title' => 'Русские Страницы',
                'keywords' => 'Русские Страницы',
                'description' => 'Русские Страницы',
                'root' => 'y',
                'user_id' => 1
            ),

            // =============================================
            // About Page
            // =============================================

            // English - About Us
            array(
                'name' => 'About Us',
                'lang' => 'en',
                'parent_id' => '1',
                'seo' => 'About_Us',
                'title' => 'About Us | AKM Business CMS',
                'keywords' => 'About Us, AKM Business CMS, enterprise content management system, modular cms, corporate web solutions',
                'description' => 'AKM Business CMS is a high-performance, secure, and multilingual content management system designed for enterprise businesses and digital platforms.',
                'summary' => 'AKM Business CMS is a next-generation corporate content management system engineered to empower businesses, agencies, and enterprise portals with a modern, modular, and high-performance digital architecture.',
                'content' => "<p>About Us | AKM Business CMS<br><br></p>\n<h1>About Us | AKM Business CMS</h1>\n<h3><strong>Who We Are</strong></h3>\n<p><strong>AKM Business CMS</strong> is a high-performance, modular enterprise content management system developed to empower businesses, digital agencies, and organizations to manage their online presence effortlessly. Built on robust architecture with native multilingual support and an intuitive admin surface, it provides the foundation for state-of-the-art web projects.</p>\n<h3><strong>Our Vision &amp; Mission</strong></h3>\n<p>In a rapidly evolving digital landscape, our goal is to simplify, secure, and accelerate corporate content and data workflows:</p>\n<ul>\n<li>\n<p><strong>Modular &amp; Flexible Architecture:</strong> Deliver a scalable ecosystem that adapts seamlessly to diverse business needs.</p>\n</li>\n<li>\n<p><strong>Native Multilingual Capabilities:</strong> Reach global and local audiences with effortless locale and translation management.</p>\n</li>\n<li>\n<p><strong>High Performance &amp; SEO Standards:</strong> Boost search visibility and user satisfaction with lightning-fast load times and clean routing.</p>\n</li>\n<li>\n<p><strong>Enterprise Security:</strong> Protect sensitive data with centralized access control (ACL) and modern web protection standards.</p>\n</li>\n</ul>\n<h3><strong>What We Deliver</strong></h3>\n<p>More than just a CMS, AKM Business CMS provides an end-to-end digital management platform:</p>\n<ol>\n<li>\n<p><strong>Comprehensive Business Modules:</strong> Optimized modules for page management, news, services, products, portfolios, documents, and multimedia.</p>\n</li>\n<li>\n<p><strong>Intuitive Admin Surface:</strong> A phone-first, streamlined admin interface allowing full control without requiring technical expertise.</p>\n</li>\n<li>\n<p><strong>Scalable Data &amp; Template System:</strong> Reliable and efficient performance for both growing businesses and large-scale corporate portals.</p>\n</li>\n</ol>\n<p>Elevate your digital operations with AKM Business CMS and build tomorrow's web experience today.</p>",
                'image' => 'SiHdJS38a62RN7G.webp',
                'root' => 'n',
                'user_id' => 1
            ),
            // Kurdish (ku) - About Us
            array(
                'name' => 'Derbarê me',
                'lang' => 'ku',
                'parent_id' => '2',
                'seo' => 'Derbarê_me',
                'title' => 'Derbarê me | AKM Business CMS',
                'keywords' => 'Derbarê me, AKM Business CMS, pergala rêveberiya naverokê, nermalava kurmancî ya webê, pergalên saziyê',
                'description' => 'AKM Business CMS; pergaleke rêveberiya naverokê ya moduler, ewle û bi performansa bilind e ku ji bo sazî, karsazî û projeyên dîjîtal hatiye pêşxistin.',
                'summary' => 'AKM Business CMS; pergaleke nûjen û moduler a rêveberiya naverokê ye ku pêdiviyên web û dîjîtal ên kompaniyan, saziyan û ajansan bi lez, ewlehî û piştgiriya pirzimaniyê pêk tîne.',
                'content' => "<p>Derbarê me | AKM Business CMS<br><br></p>\n<h1>Derbarê me | AKM Business CMS</h1>\n<h3><strong>Em Kî Ne?</strong></h3>\n<p><strong>AKM Business CMS</strong> pergaleke rêveberiya naverokê ya saziyî, moduler û bi performansa bilind e ku ji bo rêvebirina hebûna dîjîtal a karsazî, şîrket û rêxistinan hatiye sêwirandin. Bi binesaziya xwe ya zexm, piştgiriya pirzimaniyê ya xwemalî û panela rêveberiyê ya nûjen, ji bo projeyên serkeftî yên webê bingehek bihêz ava dike.</p>\n<h3><strong>Vîzyon û Mîsyona Me</strong></h3>\n<p>Di cîhana teknolojiyê ya ku bi lez diguhere de, armanca me ew e ku pêvajoyên rêveberiya naverok û daneyan ji bo karsaziyan hêsantir, ewletir û bileztir bikin:</p>\n<ul>\n<li>\n<p><strong>Mîmariya Moduler û Nerm:</strong> Ekosîstemek ku li gorî hemû pêdiviyên karsaziyê bi hêsanî dikare were firehkirin û taybetkirin.</p>\n</li>\n<li>\n<p><strong>Piştgiriya Pirzimaniyê ya Xwemalî:</strong> Gihîştina bikarhênerên herêmî û navneteweyî bi rêya rêveberiya bêkêmasî ya zimanan.</p>\n</li>\n<li>\n<p><strong>Performansa Bilind û Standardên SEO:</strong> Zêdekirina dîtina di motorên lêgerînê de û ezmûneke bilez a bikarhêneran.</p>\n</li>\n<li>\n<p><strong>Ewlehiya Asta Saziyê:</strong> Parastina daneyan bi pergala pêşketî ya kontrolkirina destûran (ACL) û standardên ewlehiyê.</p>\n</li>\n</ul>\n<h3><strong>Em Çi Pêşkêş Dikin?</strong></h3>\n<p>AKM Business CMS ne tenê malperek e, ew platformeke rêveberiya dîjîtal a berfireh e:</p>\n<ol>\n<li>\n<p><strong>Modulên Berfireh ên Karsaziyê:</strong> Modulên taybet ên optimîzekirî ji bo rûpelan, nûçeyan, xizmetguzariyan, hilberan, projeyan, belgeyan û medyayê.</p>\n</li>\n<li>\n<p><strong>Panela Rêveberiyê ya Pratîk:</strong> Navrûyek nûjen ku bêyî hewcedariya zanîna teknîkî kontrola tevahî dide we.</p>\n</li>\n<li>\n<p><strong>Binesaziya Şablon û Daneyan a Pêşketî:</strong> Performansa bênavber hem ji bo karsaziyên nûjen û hem jî ji bo portalên mezin ên saziyî.</p>\n</li>\n</ol>\n<p>Bi AKM Business CMS re karsaziya xwe derbasî asta dîjîtal a nûjen bikin.</p>",
                'image' => 'SiHdJS38a62RN7G.webp',
                'root' => 'n',
                'user_id' => 1
            ),
            // Kurdish (ckb) - About Us
            array(
                'name' => 'دەربارەی ئێمە',
                'lang' => 'ckb',
                'parent_id' => '3',
                'seo' => 'دەربارەی_ئێمە',
                'title' => 'دەربارەی ئێمە | AKM Business CMS',
                'keywords' => 'دەربارەی ئێمە, AKM Business CMS, سیستەمی بەڕێوەبردنی ناوەڕۆک, سیستەمی بازرگانی, ماڵپەڕی کوردی',
                'description' => 'AKM Business CMS سیستەمێکی بەڕێوەبردنی ناوەڕۆکی مۆدێرن، فرەزمان و پارێزراوە کە بۆ کۆمپانیا و دامەزراوە بازرگانییەکان پەرەی پێدراوە.',
                'summary' => 'AKM Business CMS سیستەمێکی بەڕێوەبردنی ناوەڕۆکی پێشکەوتووە کە پێداویستییە دیجیتاڵییەکانی کۆمپانیاکان و پۆرتالە بازرگانییەکان بە شێوازێکی مۆدێرن و فرەزمان دابین دەکات.',
                'content' => "<p>دەربارەی ئێمە | AKM Business CMS<br><br></p>\n<h1>دەربارەی ئێمە | AKM Business CMS</h1>\n<h3><strong>ئێمە کێین؟</strong></h3>\n<p><strong>AKM Business CMS</strong> سیستەمێکی بەڕێوەبردنی ناوەڕۆکی دامەزراوەیی و مۆدۆلاری ئاستبەرزە کە بۆ بەڕێوەبردنی چالاکییە دیجیتاڵییەکانی کۆمپانیا، ئاژانس و ڕێکخراوەکان گەشەی پێدراوە. بە تەلارسازییەکی تۆکمە، پشتیوانی فرەزمانی سروشتی و پانێڵی بەڕێوەبردنی مۆدێرن، بناغەیەکی بەهێز بۆ پڕۆژە سەرکەوتووەکانی وێب دابین دەکات.</p>\n<h3><strong>دیدگا و ئەرکی ئێمە</strong></h3>\n<p>لە جیهانی خێرای تەکنۆلۆژیادا، ئامانجمان ئاسانکاری، خێراکردن و پاراستنی پرۆسەکانی بەڕێوەبردنی ناوەڕۆک و داتای دامەزراوەکانە:</p>\n<ul>\n<li>\n<p><strong>تەلارسازی مۆدۆلار و نەرم:</strong> ژینگەیەکی فراوانکراو کە بە ئاسانی لەگەڵ هەموو پێداویستییەکی کاردا دەگونجێت.</p>\n</li>\n<li>\n<p><strong>پشتیوانی فرەزمانی ڕەسەن:</strong> گەیشتن بە بەکارهێنەرانی جیهانی و ناوخۆیی بە بەڕێوەبردنی وەرگێڕان بە شێوازێکی پاراو.</p>\n</li>\n<li>\n<p><strong>ئاستی بەرزی کارکردن و ستانداردەکانی SEO:</strong> دەرکەوتنی بەهێز لە بزوێنەرەکانی گەڕان و خێرایی لە کاتی بارکردنی لاپەڕەکاندا.</p>\n</li>\n<li>\n<p><strong>ئاسایشی ئاستی دامەزراوەیی:</strong> پاراستنی داتا بە سیستەمی پێشکەوتووی دەسەڵاتەکان (ACL) و ستانداردە نوێیەکانی پاراستن.</p>\n</li>\n</ul>\n<h3><strong>چی پێشکەش دەکەین؟</strong></h3>\n<p>AKM Business CMS زیاتر لە تەنها ماڵپەڕێکە، پلاتفۆرمێکی بەڕێوەبردنی دیجیتاڵیی گشتگیرە:</p>\n<ol>\n<li>\n<p><strong>مۆدۆلە فراوانەکانی کار:</strong> مۆدۆلی تایبەت و باشکراو بۆ لاپەڕەکان، هەواڵ، خزمەتگوزاری، بەرهەم، پڕۆژە و میدیا.</p>\n</li>\n<li>\n<p><strong>پانێڵی بەڕێوەبردنی ئاسان:</strong> ڕووکارێکی مۆدێرن کە بەبێ پێویستی بە شارەزایی تەکنیکی کۆنترۆڵی تەواوت پێدەبەخشێت.</p>\n</li>\n<li>\n<p><strong>بناغەی پێشکەوتووی داتابەیس و داڕشتەکان:</strong> توانای بەرز بۆ کارکردن چ بۆ بزنسە تازەکان و چ بۆ پۆرتالە دامەزراوەییە گەورەکان.</p>\n</li>\n</ol>\n<p>لەگەڵ AKM Business CMS کارەکانت بگوازەرەوە بۆ ئاستی ستانداردە دیجیتاڵییەکانی داهاتوو.</p>",
                'image' => 'SiHdJS38a62RN7G.webp',
                'root' => 'n',
                'user_id' => 1
            ),
            // Arabic - About Us
            array(
                'name' => 'من نحن',
                'lang' => 'ar',
                'parent_id' => '4',
                'seo' => 'من_نحن',
                'title' => 'من نحن | AKM Business CMS',
                'keywords' => 'من نحن, AKM Business CMS, نظام إدارة المحتوى المؤسسي, إدارة المحتوى, حلول الويب المؤسسية',
                'description' => 'AKM Business CMS هو نظام إدارة محتوى مؤسسي متقدم، آمن ومتعدد اللغات صُمم خصيصاً لتلبية احتياجات الشركات والمنصات الرقمية الحديثة.',
                'summary' => 'AKM Business CMS هو نظام إدارة محتوى حديث مصمم للمؤسسات والشركات والوكالات الرقمية لتقديم حلول ويب متكاملة، مرنة وآمنة تلبي متطلبات الأعمال المعاصرة.',
                'content' => "<p>من نحن | AKM Business CMS<br><br></p>\n<h1>من نحن | AKM Business CMS</h1>\n<h3><strong>من نحن؟</strong></h3>\n<p><strong>AKM Business CMS</strong> هو نظام إدارة محتوى مؤسسي متقدم ومرن تم تصميمه لتمكين الشركات، الوكالات الرقمية والمؤسسات من إدارة حضورها الرقمي بأعلى مستويات الكفاءة. بفضل بنيته التحتية القوية ودعمه الأصيل لتعدد اللغات وواجهة إدارته المتطورة، يوفر الأساس المتين لمشاريع الويب الاحترافية.</p>\n<h3><strong>رؤيتنا ورسالتنا</strong></h3>\n<p>في ظل التطور الرقمي المتسارع، نهدف إلى تبسيط وتأمين وتسريع إدارة المحتوى والبيانات في المؤسسات:</p>\n<ul>\n<li>\n<p><strong>بنية نمطية ومرنة:</strong> بيئة برمجية قابلة للتوسع والتخصيص لتلائم مختلف متطلبات الأعمال.</p>\n</li>\n<li>\n<p><strong>دعم أصيل للغات المتعددة:</strong> الوصول إلى الجمهور المحلي والدولي عبر إدارة ترجمة سلسة ومتكاملة.</p>\n</li>\n<li>\n<p><strong>أداء استثنائي ومعايير SEO:</strong> تحسين ظهور الموقع في محركات البحث وتوفير سرعة فائقة لتجربة مستخدم مثالية.</p>\n</li>\n<li>\n<p><strong>أمان على مستوى المؤسسات:</strong> حماية شاملة للبيانات من خلال إدارة مركزية للصلاحيات (ACL) وأحدث معايير الأمان.</p>\n</li>\n</ul>\n<h3><strong>ما الذي نقدمه؟</strong></h3>\n<p>يقدم AKM Business CMS منصة إدارة رقمية متكاملة تتجاوز مفهوم الموقع التقليدي:</p>\n<ol>\n<li>\n<p><strong>وحدات أعمال شاملة:</strong> وحدات محسنة ومخصصة لإدارة الصفحات، الأخبار، الخدمات، المنتجات، المشاريع، المستندات والوسائط.</p>\n</li>\n<li>\n<p><strong>لوحة تحكم سهلة الاستخدام:</strong> واجهة حديثة تمنحك التحكم الكامل دون الحاجة إلى خبرة برمجية معقدة.</p>\n</li>\n<li>\n<p><strong>بنية قواعد بيانات وقوالب متطورة:</strong> أداء موثوق ومستقر يناسب المشاريع الناشئة والبوابات المؤسسية الضخمة.</p>\n</li>\n</ol>\n<p>ارتقِ بأعمالك إلى معايير المستقبل الرقمي مع AKM Business CMS.</p>",
                'image' => 'SiHdJS38a62RN7G.webp',
                'root' => 'n',
                'user_id' => 1
            ),
            // German - About Us
            array(
                'name' => 'Über uns',
                'lang' => 'de',
                'parent_id' => '5',
                'seo' => 'Über_uns',
                'title' => 'Über uns | AKM Business CMS',
                'keywords' => 'Über uns, AKM Business CMS, Enterprise CMS, mehrsprachiges CMS, Content Management System, Unternehmenswebseite',
                'description' => 'AKM Business CMS ist ein leistungsstarkes, sicheres und mehrsprachiges Enterprise-Content-Management-System für moderne Unternehmen und digitale Plattformen.',
                'summary' => 'AKM Business CMS ist ein fortschrittliches Content-Management-System für Unternehmen, Agenturen und digitale Portale, das flexible, sichere und mehrsprachige Webarchitekturen bietet.',
                'content' => "<p>Über uns | AKM Business CMS<br><br></p>\n<h1>Über uns | AKM Business CMS</h1>\n<h3><strong>Wer wir sind</strong></h3>\n<p><strong>AKM Business CMS</strong> ist ein hochmodernes, modulares Enterprise-Content-Management-System, das entwickelt wurde, um Unternehmen, Digitalagenturen und Organisationen die professionelle Verwaltung ihrer digitalen Präsenz zu ermöglichen. Mit einer robusten Kernarchitektur, nativer Mehrsprachigkeit und einer intuitiven Verwaltungsoberfläche bildet es das Fundament für zukunftssichere Webprojekte.</p>\n<h3><strong>Unsere Vision &amp; Mission</strong></h3>\n<p>In einer sich ständig wandelnden digitalen Welt unterstützen wir Unternehmen dabei, ihre Inhalts- und Datenprozesse zu vereinfachen, abzusichern und zu beschleunigen:</p>\n<ul>\n<li>\n<p><strong>Modulare &amp; flexible Architektur:</strong> Ein skalierbares Ökosystem, das sich nahtlos an individuelle Geschäftsanforderungen anpasst.</p>\n</li>\n<li>\n<p><strong>Native Mehrsprachigkeit:</strong> Müheloses Lokalisierungs- und Übersetzungsmanagement für globale und lokale Zielgruppen.</p>\n</li>\n<li>\n<p><strong>Höchste Performance &amp; SEO-Standards:</strong> Schnelle Ladezeiten und saubere Routing-Strukturen für beste Platzierungen in Suchmaschinen.</p>\n</li>\n<li>\n<p><strong>Enterprise-Sicherheit:</strong> Umfassender Schutz von Daten durch zentrale Rechteverwaltung (ACL) und modernste Sicherheitsstandards.</p>\n</li>\n</ul>\n<h3><strong>Was wir bieten</strong></h3>\n<p>AKM Business CMS bietet eine ganzheitliche Plattform für digitales Management:</p>\n<ol>\n<li>\n<p><strong>Umfassende Business-Module:</strong> Optimierte Module für Seiten, News, Dienstleistungen, Produkte, Referenzen, Dokumente und Medien.</p>\n</li>\n<li>\n<p><strong>Intuitive Verwaltung:</strong> Benutzerfreundliche Steuerung der gesamten Website ohne erforderliche Programmierkenntnisse.</p>\n</li>\n<li>\n<p><strong>Skalierbare Datenbank- &amp; Template-Struktur:</strong> Stabile Performance sowohl für wachsende Unternehmen als auch für große Unternehmensportale.</p>\n</li>\n</ol>\n<p>Bringen Sie Ihr Unternehmen mit AKM Business CMS auf den neuesten digitalen Stand.</p>",
                'image' => 'SiHdJS38a62RN7G.webp',
                'root' => 'n',
                'user_id' => 1
            ),
            // French - About Us
            array(
                'name' => 'À propos',
                'lang' => 'fr',
                'parent_id' => '6',
                'seo' => 'À_propos',
                'title' => 'À propos | AKM Business CMS',
                'keywords' => 'À propos, AKM Business CMS, CMS entreprise, gestion de contenu, solution web professionnelle, CMS multilingue',
                'description' => 'AKM Business CMS est un système de gestion de contenu d\'entreprise performant, sécurisé et multilingue, conçu pour les entreprises et plateformes digitales.',
                'summary' => 'AKM Business CMS est un système de gestion de contenu de nouvelle génération conçu pour répondre aux exigences des entreprises, agences et portails corporatifs avec une architecture moderne et modulaire.',
                'content' => "<p>À propos | AKM Business CMS<br><br></p>\n<h2>À propos | AKM Business CMS</h2>\n<h3><strong>Qui sommes-nous ?</strong></h3>\n<p><strong>AKM Business CMS</strong> est un système de gestion de contenu d'entreprise (CMS) performant et modulaire conçu pour permettre aux entreprises, agences digitales et organisations de piloter leur présence en ligne en toute simplicité. Doté d'une architecture robuste, d'un support multilingue natif et d'une interface d'administration intuitive, il offre un socle idéal pour des projets web ambitieux.</p>\n<h3><strong>Notre Vision &amp; Mission</strong></h3>\n<p>Dans un paysage numérique en perpétuelle évolution, notre objectif est de simplifier, sécuriser et accélérer la gestion des contenus et des données d'entreprise :</p>\n<ul>\n<li>\n<p><strong>Architecture Modulaire &amp; Flexible :</strong> Un écosystème évolutif qui s'adapte précisément aux besoins spécifiques de chaque organisation.</p>\n</li>\n<li>\n<p><strong>Support Multilingue Natif :</strong> Une gestion fluide de la localisation pour toucher des audiences internationales et locales.</p>\n</li>\n<li>\n<p><strong>Haute Performance &amp; Standards SEO :</strong> Des temps de chargement ultra-rapides et des URLs optimisées pour les moteurs de recherche.</p>\n</li>\n<li>\n<p><strong>Sécurité Entreprise :</strong> Protection maximale des données grâce au contrôle d'accès centralisé (ACL) et aux meilleures pratiques de sécurité.</p>\n</li>\n</ul>\n<h3><strong>Ce que nous offrons</strong></h3>\n<p>Bien plus qu'un simple CMS, AKM Business CMS est une plateforme complète de gestion digitale :</p>\n<ol>\n<li>\n<p><strong>Modules Métiers Complets :</strong> Des modules dédiés pour les pages, actualités, services, produits, réalisations, documents et médias.</p>\n</li>\n<li>\n<p><strong>Panneau d'Administration Intuitif :</strong> Un contrôle total et ergonomique sans nécessiter de compétences techniques avancées.</p>\n</li>\n<li>\n<p><strong>Infrastructure Évolutive :</strong> Des performances remarquables pour les PME en pleine croissance comme pour les grands portails d'entreprise.</p>\n</li>\n</ol>\n<p>Faites passer votre présence en ligne au niveau supérieur avec AKM Business CMS.</p>",
                'image' => 'SiHdJS38a62RN7G.webp',
                'root' => 'n',
                'user_id' => 1
            ),
            // Swedish - About Us
            array(
                'name' => 'Om oss',
                'lang' => 'sv',
                'parent_id' => '7',
                'seo' => 'Om_oss',
                'title' => 'Om oss | AKM Business CMS',
                'keywords' => 'Om oss, AKM Business CMS, innehållshanteringssystem, företagswebb, flerspråkigt cms, modernt cms',
                'description' => 'AKM Business CMS är ett högpresterande, säkert och flerspråkigt publiceringssystem utvecklat för företag och digitala plattformar.',
                'summary' => 'AKM Business CMS är ett modernt publiceringssystem för företag och organisationer som erbjuder en flexibel, säker och högpresterande digital plattform.',
                'content' => "<p>Om oss | AKM Business CMS<br><br></p>\n<h2>Om oss | AKM Business CMS</h2>\n<h3><strong>Vilka vi är</strong></h3>\n<p><strong>AKM Business CMS</strong> är ett modernt och modulärt innehållshanteringssystem (CMS) för företag och organisationer som vill hantera sin digitala närvaro på ett effektivt och professionellt sätt. Med en robust kärnarkitektur, inbyggt stöd för flera språk och en intuitiv administrationspanel utgör det grunden för framgångsrika webbprojekt.</p>\n<h3><strong>Vår vision &amp; mission</strong></h3>\n<p>I en snabbt föränderlig digital värld strävar vi efter att förenkla, säkra och optimera företagens innehålls- och datahantering:</p>\n<ul>\n<li>\n<p><strong>Modulär och flexibel arkitektur:</strong> Ett skalbart system som enkelt anpassas efter verksamhetens unika behov.</p>\n</li>\n<li>\n<p><strong>Inbyggt flerspråkigt stöd:</strong> Nå både globala och lokala målgrupper med smidig språk- och översättningshantering.</p>\n</li>\n<li>\n<p><strong>Hög prestanda och SEO-standard:</strong> Blixtsnabb laddningstid och optimerade URL:er för maximal synlighet i sökmotorer.</p>\n</li>\n<li>\n<p><strong>Företagssäkerhet:</strong> Centraliserad behörighetskontroll (ACL) och moderna säkerhetsstandarder som skyddar din data.</p>\n</li>\n</ul>\n<h3><strong>Vad vi erbjuder</strong></h3>\n<p>AKM Business CMS erbjuder en heltäckande plattform för digital verksamhetshantering:</p>\n<ol>\n<li>\n<p><strong>Omfattande företagsmoduler:</strong> Optimerade moduler för sidor, nyheter, tjänster, produkter, projekt, dokument och multimedia.</p>\n</li>\n<li>\n<p><strong>Intuitiv kontrollpanel:</strong> Full kontroll över webbplatsen via ett användarvänligt gränssnitt utan krav på teknisk förkunskap.</p>\n</li>\n<li>\n<p><strong>Skalbar struktur:</strong> Pålitlig drift och hög kapacitet för både mindre företag och stora företagsportaler.</p>\n</li>\n</ol>\n<p>Ta er digitala närvaro till nästa nivå med AKM Business CMS.</p>",
                'image' => 'SiHdJS38a62RN7G.webp',
                'root' => 'n',
                'user_id' => 1
            ),
            // Turkish - About Us
            array(
                'name' => 'Hakkımızda',
                'lang' => 'tr',
                'parent_id' => '8',
                'seo' => 'Hakkımızda',
                'title' => 'Hakkımızda | AKM Business CMS',
                'keywords' => 'Hakkımızda, AKM Business CMS, kurumsal içerik yönetim sistemi, modüler cms, kurumsal web çözümleri',
                'description' => 'AKM Business CMS, kurumsal işletmeler ve dijital projeler için tasarlanmış yüksek performanslı, güvenli ve çok dilli bir içerik yönetim sistemidir.',
                'summary' => 'AKM Business CMS; kurumsal şirketlerin, ajansların ve dijital platformların tüm web ve içerik yönetim ihtiyaçlarını modern, modüler ve güvenli bir mimariyle karşılayan yeni nesil iş odaklı bir içerik yönetim sistemidir.',
                'content' => "<p>Hakkımızda | AKM Business CMS<br><br></p>\n<h2>Hakkımızda | AKM Business CMS</h2>\n<h3><strong>Biz Kimiz?</strong></h3>\n<p><strong>AKM Business CMS</strong>, kurumsal işletmelerin, dijital ajansların ve büyüyen organizasyonların dijital varlıklarını en üst düzeyde yönetmelerini sağlamak amacıyla geliştirilmiş, yüksek performanslı ve modüler bir kurumsal içerik yönetim sistemidir. Güçlü çekirdek mimarisi, çok dilli altyapısı ve modern yönetim paneli ile dijital dünyada fark yaratan projelere zemin hazırlar.</p>\n<h3><strong>Vizyonumuz &amp; Misyonumuz</strong></h3>\n<p>Teknolojinin hızla dönüştüğü günümüz dünyasında, kurumların içerik ve veri yönetim süreçlerini sadeleştirmeyi, güvenli hale getirmeyi ve hızlandırmayı hedefliyoruz:</p>\n<ul>\n<li>\n<p><strong>Modüler ve Esnek Mimari:</strong> İhtiyaca göre kolayca özelleştirilebilen ve genişletilebilen modül ekosistemi sunmak.</p>\n</li>\n<li>\n<p><strong>Yerel Çoklu Dil Desteği:</strong> Küresel ve yerel pazarlarda hedef kitlelere kendi dillerinde kesintisiz erişim sağlamak.</p>\n</li>\n<li>\n<p><strong>Üstün Performans ve SEO Standartları:</strong> Arama motorlarında yüksek görünürlük ve hızlı sayfa yükleme süreleriyle ziyaretçi deneyimini zirveye taşımak.</p>\n</li>\n<li>\n<p><strong>Kurumsal Güvenlik:</strong> Gelişmiş yetkilendirme (ACL) ve modern güvenlik protokolleriyle verileri güvende tutmak.</p>\n</li>\n</ul>\n<h3><strong>Neler Sunuyoruz?</strong></h3>\n<p>AKM Business CMS, standart bir web sitesinin ötesinde, uçtan uca kurumsal bir yönetim deneyimi sağlar:</p>\n<ol>\n<li>\n<p><strong>Kapsamlı Modül Desteği:</strong> Sayfa yönetimi, haberler, hizmetler, ürünler, projeler, belgeler ve multimedya içerikleri için özel olarak optimize edilmiş modüller.</p>\n</li>\n<li>\n<p><strong>Gelişmiş Yönetim Paneli:</strong> Mobil uyumlu ve sezgisel arayüzü sayesinde teknik bilgi gerektirmeden tam kontrol imkânı.</p>\n</li>\n<li>\n<p><strong>Ölçeklenebilir Veritabanı ve Şablon Altyapısı:</strong> Küçük işletmelerden büyük kurumsal portallara kadar her ölçekte sorunsuz çalışma kabiliyeti.</p>\n</li>\n</ol>\n<p>AKM Business CMS ile işinizi geleceğin dijital standartlarına taşıyın.</p>",
                'image' => 'SiHdJS38a62RN7G.webp',
                'root' => 'n',
                'user_id' => 1
            ),
            // Kurdish (zza) - About Us
            array(
                'name' => 'Ma Heqa',
                'lang' => 'zza',
                'parent_id' => '9',
                'seo' => 'Ma_Heqa',
                'title' => 'Ma Heqa | AKM Business CMS',
                'keywords' => 'Ma Heqa, AKM Business CMS, perSystema idareyê naverokî, nermalawa sazîye, webê zazakî',
                'description' => 'AKM Business CMS; semedê karsazî û sazîyan pergalêka idareyê naverokî ya bêmıanet, ewle û bi performansa berze ya.',
                'summary' => 'AKM Business CMS; semedê şîrketan û dezgehan yew pergalêka modern û moduler a idareyê naverokî ya ke hewcedarîyên dîjîtal bi zafzimanan temîn kena.',
                'content' => "<p>Ma Heqa | AKM Business CMS<br><br></p>\n<h2>Ma Heqa | AKM Business CMS</h2>\n<h3><strong>Ma Kam ê?</strong></h3>\n<p><strong>AKM Business CMS</strong>, semedê şîrketan, ajansanê dîjîtalan û dezgehan yew pergalêka idareyê naverokî ya sazîyî û moduler a ke bi performansa berze ameya averberdene. Bi binyatê xo yo xurt, piştgirîya zafzimanan û panela idareyî ya asane, semedê projeyanê webî yê serkewteyan bingehêko qewîn viraşto.</p>\n<h3><strong>Vîzyon û Mîsyonê Ma</strong></h3>\n<p>Dinyaya teknolojîye ya leze de, amancê ma no yo ke prosesê idareyê naverok û daeyan semedê sazîyan hênî hêsan, ewle û lezkar bikê:</p>\n<ul>\n<li>\n<p><strong>Mîmarîya Modulere û Nerme:</strong> Sîstemêko hîrabîyaye ke goreyê hewcedarîyên karî bi asanî yeno averberdene.</p>\n</li>\n<li>\n<p><strong>Piştgirîya Zafzimanan:</strong> Bi idareyê çerxê wergerî resayîşê hemwelatîyan û şarê gerdûnî.</p>\n</li>\n<li>\n<p><strong>Performanso Berz û Standardê SEO:</strong> Zêdekerdişê asayîşê motoranê cıgêrayîşî û lezbîyayîşê barkerdişê pelan.</p>\n</li>\n<li>\n<p><strong>Ewlehîya Sazîye:</strong> Pawitişê daeyan bi pergalêka averşîyaye ya destûran (ACL) û standardanê ewlehîye.</p>\n</li>\n</ul>\n<h3><strong>Ma Çi Danê?</strong></h3>\n<p>AKM Business CMS teyna yew keyepel nîyo, yew platformê idareyê dîjîtalî yo pêroyî yo:</p>\n<ol>\n<li>\n<p><strong>Modulê Karî yê Hîrayî:</strong> Modulê xusûsî semedê pelan, xeberan, xizmetan, berheman, projeyan, dokumanan û medyayê.</p>\n</li>\n<li>\n<p><strong>Panela Îdareyî ya Pratîke:</strong> Navrûyêko modern ke bê hewcedarîya zanayîşê teknîkî kontrola tam dano şima.</p>\n</li>\n<li>\n<p><strong>Binyatê Daeyan û Şablonan ê Berz:</strong> Xebatêka bêkêmasî semedê karê werdîyan û portalanê gırdan ê sazîyan.</p>\n</li>\n</ol>\n<p>Bi AKM Business CMS karê xo bierrzê asta dîjîtale ya paşerojê.</p>",
                'image' => 'SiHdJS38a62RN7G.webp',
                'root' => 'n',
                'user_id' => 1
            ),
            // Russian - About Us
            array(
                'name' => 'О нас',
                'lang' => 'ru',
                'parent_id' => '10',
                'seo' => 'О_нас',
                'title' => 'О нас | AKM Business CMS',
                'keywords' => 'О нас, AKM Business CMS, корпоративная система управления контентом, модульная cms, веб-решения',
                'description' => 'AKM Business CMS — это высокопроизводительная, безопасная и многоязычная система управления контентом для бизнеса и цифровых платформ.',
                'summary' => 'AKM Business CMS — это корпоративная система управления контентом нового поколения, созданная для обеспечения бизнеса современной, модульной и производительной цифровой архитектурой.',
                'content' => "<p>О нас | AKM Business CMS<br><br></p>\n<h2>О нас | AKM Business CMS</h2>\n<h3><strong>Кто мы?</strong></h3>\n<p><strong>AKM Business CMS</strong> — это высокопроизводительная и модульная корпоративная система управления контентом, разработанная для эффективного управления цифровыми активами предприятий, агентств и растущих организаций. Надежная архитектура ядра, мультиязычная инфраструктура и современная панель управления создают основу для успешных цифровых проектов.</p>\n<h3><strong>Наше видение и миссия</strong></h3>\n<p>В быстро меняющемся технологическом мире наша цель — сделать процессы управления контентом и данными простыми, безопасными и быстрыми:</p>\n<ul>\n<li>\n<p><strong>Модульная и гибкая архитектура:</strong> Предоставление экосистемы модулей, легко настраиваемых под любые задачи бизнеса.</p>\n</li>\n<li>\n<p><strong>Встроенная мультиязычность:</strong> Беспрепятственный выход на глобальные и локальные рынки на родных языках пользователей.</p>\n</li>\n<li>\n<p><strong>Высокая производительность и стандарты SEO:</strong> Максимальная видимость в поисковых системах и быстрая загрузка страниц.</p>\n</li>\n<li>\n<p><strong>Корпоративная безопасность:</strong> Защита данных с помощью гибкой системы разграничения прав (ACL) и современных протоколов безопасности.</p>\n</li>\n</ul>\n<h3><strong>Что мы предлагаем?</strong></h3>\n<p>AKM Business CMS — это не просто веб-сайт, а полноценная цифровая платформа управления:</p>\n<ol>\n<li>\n<p><strong>Комплексный набор модулей:</strong> Специализированные модули для страниц, новостей, услуг, продукции, проектов, документов и медиа-контента.</p>\n</li>\n<li>\n<p><strong>Удобная панель управления:</strong> Адаптивный и интуитивный интерфейс, позволяющий управлять платформой без глубоких технических знаний.</p>\n</li>\n<li>\n<p><strong>Масштабируемость:</strong> Безупречная работа как для малого бизнеса, так и для крупных корпоративных порталов.</p>\n</li>\n</ol>\n<p>Выведите свой бизнес на уровень передовых цифровых стандартов вместе с AKM Business CMS.</p>",
                'image' => 'SiHdJS38a62RN7G.webp',
                'root' => 'n',
                'user_id' => 1
            )
        );

        $this->model = Load::model('mod_page');
        foreach ($data AS $key => $value) {
            if ($this->model->exists_name_and_lang($value['name'],$value['lang']) === FALSE) {
                $this->success("Added: " . $value['name']);
                $keys = array_keys($value);
                foreach ($keys AS $k) {
                    $this->model->$k = $value[$k];
                }
                // Default value for fields missing in data
                if (!isset($value['order'])) {
                    $this->model->order = 0;
                }
                if ($this->model->save() === FALSE) {
                    $this->error("Failed to add: " . $value['name'] . print_r($this->model, TRUE));
                    return FALSE;
                }
                continue;

            } else {
                $this->warning("Already exists: " . $value['name']);
            }
        }
        return TRUE;
    }

    public function uninstall()
    {

        $this->remove_tables();
        $this->remove_mods();
        $this->delete_roles();
        $this->remove_perm();
        return TRUE;
    }
}