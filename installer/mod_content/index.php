<?php

class mod_content extends installerbase implements installerbaseinterface
{
    public $tables;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_content';
        $this->tables = array(
            'mod_content',
            'contents'
        );
        $this->roles = array(
            'add', 'edit',  'delete', 'list','gallery','file','edit_status','change_status','approval', 'moderate_edit','moderate_delete','video_download'
        );
    }

    public function install()
    {
        // Create tables
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        // Register module
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        // Register roles and permissions
        if ($this->insert_roles() === FALSE) {
            return FALSE;
        }
        // Seed sample contents
        if ($this->add_content() === FALSE) {
            return FALSE;
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

    public function add_content()
    {
        if (!$this->installer_sample_content_enabled()) {
            return TRUE;
        }

        $data = array(
            // Turkish Content
            array(
                'name' => 'AKM Business CMS\'in Modern Yönlerini Tanıyın',
                'lang' => 'tr',
                'category_id' => '10',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS; modern web siteleri, kurumsal yazılımlar ve dinamik platformlar oluşturmak için tasarlanmış yüksek performanslı ve modüler bir içerik yönetim sistemidir.',
                'content' => '<p><strong>AKM Business CMS</strong>; modern web siteleri, kurumsal portallar ve ölçeklenebilir dijital çözümler sunmak üzere tasarlanmış kapsamlı ve yüksek performanslı bir içerik yönetim sistemidir. Kullanıcı dostu yönetim paneli, esnek veri mimarisi ve gelişmiş güvenlik standartları ile dijital varlığınızı bir üst seviyeye taşır.</p>
                <p><strong>Öne Çıkan Güçlü Özellikler:</strong></p>
                <ul>
                    <li><strong>Gelişmiş Çoklu Dil Desteği:</strong> Tüm içerik, kategori ve sistem yapılandırmalarında kesintisiz dil yönetimi.</li>
                    <li><strong>Esnek Modüler Mimari:</strong> İhtiyaçlarınıza göre genişletilebilir ve özelleştirilebilir modül altyapısı.</li>
                    <li><strong>SEO & Performans Optimizasyonu:</strong> Arama motorları için optimize edilmiş dinamik URL, meta ve başlık yapısı.</li>
                    <li><strong>Güvenli ve Modern Binaltı:</strong> Merkezi yetkilendirme (ACL) ve yüksek veri güvenliği.</li>
                    <li><strong>Mobil Uyumlu Tasarım:</strong> Tüm cihaz ve ekran çözünürlüklerinde mükemmel kullanıcı deneyimi.</li>
                </ul>
                <p>AKM Business CMS ile kurumsal projelerinizi dijital dünyada öne çıkarın ve geleceğin web standartlarına kolayca uyum sağlayın.</p>',
                'seo' => 'akm-business-cmsin-modern-yonlerini-taniyin',
                'title' => 'AKM Business CMS\'in Modern Yönlerini Tanıyın - {{build_name}}',
                'keywords' => 'akm business cms, içerik yönetim sistemi, kurumsal web yazılımı, çoklu dil cms, seo uyumlu cms',
                'description' => 'AKM Business CMS; modern web siteleri ve kurumsal yazılımlar için geliştirilmiş yüksek performanslı ve modüler içerik yönetim sistemidir.',
                'image' => 'akm_business_cms_cover.jpg',
                'user_id' => '1'
            ),
            // English Content
            array(
                'name' => 'Explore the Modern Aspects of AKM Business CMS',
                'lang' => 'en',
                'category_id' => '11',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS is a high-performance, modular content management system engineered for building modern websites and enterprise-grade applications.',
                'content' => '<p><strong>AKM Business CMS</strong> is a comprehensive, enterprise-grade content management system developed for creating modern web applications, corporate portals, and dynamic platforms. Powered by an intuitive admin interface, flexible architecture, and robust security standards, it enables seamless digital transformation.</p>
<p><strong>Key Highlights & Capabilities:</strong></p>
<ul>
<li><strong>Native Multilingual Engine:</strong> Effortless translation and localization management across all modules and content types.</li>
<li><strong>Modular Architecture:</strong> Scalable and flexible system design tailored for enterprise needs.</li>
<li><strong>SEO & Performance Focused:</strong> Optimized routing, dynamic metadata, clean URLs, and high-speed execution.</li>
<li><strong>Enterprise Security:</strong> Centralized access control (ACL), protected endpoints, and safe data handling.</li>
<li><strong>Fully Mobile Responsive:</strong> Exceptional user experience across mobile, tablet, and desktop viewports.</li>
</ul>
<p>Elevate your digital presence with AKM Business CMS and deliver state-of-the-art web solutions effortlessly.</p>',
                'seo' => 'explore-modern-aspects-of-akm-business-cms',
                'title' => 'Explore the Modern Aspects of AKM Business CMS - {{build_name}}',
                'keywords' => 'akm business cms, content management system, enterprise cms, multilingual cms, seo optimized cms',
                'description' => 'Discover AKM Business CMS, a high-performance modular content management system designed for modern websites and enterprise applications.',
                'image' => 'akm_business_cms_cover.jpg',
                'user_id' => '1'
            ),
            // Arabic content
            array(
                'name' => 'استكشف الجوانب الحديثة لـ AKM Business CMS',
                'lang' => 'ar',
                'category_id' => '12',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS هو نظام إدارة محتوى عالي الأداء وموحد تم تطويره لإنشاء مواقع الويب الحديثة والمنصات المؤسسية.',
                'content' => '<p><strong>AKM Business CMS</strong> هو نظام إدارة محتوى متكامل ومؤسسي تم تطويره لبناء تطبيقات الويب الحديثة، البوابات الإلكترونية، والمنصات الديناميكية. بفضل واجهة الإدارة السلسة والبنية التحتية المرنة، يساعدك على الارتقاء بحضورك الرقمي إلى أعلى المستويات.</p>
<p><strong>أبرز المميزات والقدرات:</strong></p>
<ul>
<li><strong>دعم متعدد اللغات:</strong> إدارة سلسة للترجمة والتوطين عبر جميع الوحدات وأنواع المحتوى.</li>
<li><strong>بنية نمطية مرنة:</strong> تصميم قابل للتوسع والتخصيص بما يناسب متطلبات المؤسسات.</li>
<li><strong>تحسين محركات البحث والأداء:</strong> مسارات محسنة، عناوين URL ديناميكية، وأداء عالي السرعة.</li>
<li><strong>أمان مؤسسي:</strong> نظام تحكم مركزي بالصلاحيات (ACL) وحماية متقدمة للبيانات.</li>
<li><strong>تصميم متجاوب بالكامل:</strong> تجربة مستخدم متميزة على الهواتف والأجهزة المختلفة.</li>
</ul>
<p>تميز في العالم الرقمي مع AKM Business CMS وابنِ حلول الويب المستقبلي بكل سهولة.</p>',
                'seo' => 'استكشف-الجوانب-الحديثة-لـ-akm-business-cms',
                'title' => 'استكشف الجوانب الحديثة لـ AKM Business CMS - {{build_name}}',
                'keywords' => 'akm business cms, نظام إدارة المحتوى, cms متعدد اللغات, cms مؤسسي, تحسين SEO',
                'description' => 'تعرف على نظام AKM Business CMS، نظام إدارة المحتوى عالي الأداء المصمم للمواقع الحديثة والتطبيقات المؤسسية.',
                'image' => 'akm_business_cms_cover.jpg',
                'user_id' => '1'
            ),
            // German Content
            array(
                'name' => 'Entdecken Sie die Modernen Aspekte von AKM Business CMS',
                'lang' => 'de',
                'category_id' => '13',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS ist ein leistungsstarkes und modulares Content-Management-System für die Erstellung moderner Unternehmens-Websites und dynamischer Plattformen.',
                'content' => '<p><strong>AKM Business CMS</strong> ist ein umfassendes Enterprise-Content-Management-System, das für die Entwicklung moderner Webanwendungen, Unternehmensportale und dynamischer Plattformen konzipiert wurde. Mit einer intuitiven Verwaltungsoberfläche, flexibler Architektur und höchsten Sicherheitsstandards bringt es Ihre digitale Präsenz auf ein neues Niveau.</p>
<p><strong>Wichtige Funktionen im Überblick:</strong></p>
<ul>
<li><strong>Mehrsprachige Engine:</strong> Nahtlose Verwaltung von Übersetzungen und Lokalisierungen über alle Module hinweg.</li>
<li><strong>Modulare Architektur:</strong> Skalierbares Systemdesign, perfekt angepasst an Unternehmensanforderungen.</li>
<li><strong>SEO & Performance-Optimiert:</strong> Saubere URLs, dynamische Meta-Daten und blitzschnelle Ladezeiten.</li>
<li><strong>Höchste Sicherheit:</strong> Zentrale Zugriffskontrolle (ACL) und geschützte Datenstrukturen.</li>
<li><strong>Vollständig Responsive:</strong> Erstklassige Benutzererfahrung auf allen Geräten und Bildschirmgrößen.</li>
</ul>
<p>Heben Sie Ihre digitalen Projekte mit AKM Business CMS von der Masse ab und setzen Sie auf moderne Webstandards.</p>',
                'seo' => 'entdecken-sie-die-modernen-aspekte-von-akm-business-cms',
                'title' => 'Entdecken Sie die Modernen Aspekte von AKM Business CMS - {{build_name}}',
                'keywords' => 'akm business cms, content management system, enterprise cms, mehrsprachiges cms, seo cms',
                'description' => 'Lernen Sie AKM Business CMS kennen – das modulare Content-Management-System für moderne Unternehmens-Websites und Plattformen.',
                'image' => 'akm_business_cms_cover.jpg',
                'user_id' => '1'
            ),
            // French Content
            array(
                'name' => 'Découvrez les Aspects Modernes d\'AKM Business CMS',
                'lang' => 'fr',
                'category_id' => '14',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS est un système de gestion de contenu modulaire et performant conçu pour créer des sites web modernes et des applications d\'entreprise.',
                'content' => '<p><strong>AKM Business CMS</strong> est un système de gestion de contenu complet pour les entreprises, développé pour créer des applications web modernes, des portails d\'entreprise et des plateformes dynamiques. Doté d\'une interface d\'administration intuitive et d\'une architecture flexible, il propulse votre présence numérique.</p>
                <p><strong>Fonctionnalités Principales :</strong></p>
                    <ul>
                        <li><strong>Moteur Multilingue Intégré :</strong> Gestion fluide de la traduction et de la localisation dans tous les modules.</li>
                        <li><strong>Architecture Modulaire :</strong> Conception évolutive adaptée aux besoins professionnels.</li>
                        <li><strong>Optimisé pour le SEO :</strong> URLs propres, métadonnées dynamiques et performances ultra-rapides.</li>
                        <li><strong>Sécurité Renforcée :</strong> Contrôle d\'accès centralisé (ACL) et protection des données.</li>
                        <li><strong>Design Adaptatif :</strong> Expérience utilisateur optimale sur mobiles, tablettes et ordinateurs.</li>
                    </ul>
                <p>Faites briller vos projets numériques avec AKM Business CMS et adoptez dès aujourd\'hui les standards du web de demain.</p>',
                'seo' => 'decouvrez-les-aspects-modernes-dakm-business-cms',
                'title' => 'Découvrez les Aspects Modernes d\'AKM Business CMS - {{build_name}}',
                'keywords' => 'akm business cms, systeme de gestion de contenu, cms entreprise, cms multilingue, cms optimise seo',
                'description' => 'Découvrez AKM Business CMS, un système de gestion de contenu modulaire haut de gamme pour sites web et applications d\'entreprise.',
                'image' => 'akm_business_cms_cover.jpg',
                'user_id' => '1'
            ),
            // Kurdish Kurmancî Content
            array(
                'name' => 'Aliyên Nûjen ên AKM Business CMS Nas Bike',
                'lang' => 'ku',
                'category_id' => '15',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS; pergaleke rêveberiya naverokê ya moduler û bi performansa bilind e ku ji bo avakirina malperên modern û nermalavên saziyê hatî pêşxistin.',
                'content' => '<p><strong>AKM Business CMS</strong>; pergaleke rêveberiya naverokê ya berfireh û bi performansa bilind e ku ji bo avakirina serlêdanên webê yên modern, portalên kurmancî/saziyê û platformên dînamîk hatî amadekirin. Bi panela rêveberiyê ya bikarhêner-dost, mîmariya nerm û standardên bilind ên ewlehiyê hebûna we ya dîjîtal dikişîne asteke bilindtir.</p>
<p><strong>Taybetmendiyên Sereke û Hêzdar:</strong></p>
<ul>
<li><strong>Piştgiriya Gelek Zimanan a Xweser:</strong> Rêveberiya bêkêmasî ya werger û ziman di hemû modulan û cûreyên naverokê de.</li>
<li><strong>Mîmariya Moduler a Nerm:</strong> Sêwirana pergalê ya guherbar û nerm ku li gorî hewcedariyên saziyê tê adaptekirin.</li>
<li><strong>Optimîzasyona SEO û Performansê:</strong> Avahiya URL ya paqij, meta-daneyên dînamîk û leza bilind a xebatê.</li>
<li><strong>Ewlehiya Saziyê:</strong> Pergala navendî ya kontrola destûran (ACL) û parastina ewledar a daneyan.</li>
<li><strong>Sêwirana Bi Temamî Mobîl-Uygum (Responsive):</strong> Ezmûneke mezin a bikarhêneriyê di hemû cîhaz û ekranan de.</li>
</ul>
<p>Bi AKM Business CMS re projeyên xwe yên dîjîtal derxînin pêş û standardên webê yên paşerojê bi hêsanî bi kar bînin.</p>',
                'seo' => 'aliyên_nûjen_ên_akm_business_cms_nas_bike',
                'title' => 'Aliyên Nûjen ên AKM Business CMS Nas Bike - {{build_name}}',
                'keywords' => 'akm business cms, pergala rêveberiya naverokê, nermalava webê ya saziyê, cms bi gelek zimanan, cms a seo-uygum',
                'description' => 'AKM Business CMS nas bikin; pergala rêveberiya naverokê ya moduler û bi performansa bilind ji bo malperên modern û nermalavên saziyê.',
                'image' => 'akm_business_cms_cover.jpg',
                'user_id' => '1'
            ),
            // Kurdish Soranî Content
            array(
                'name' => 'لایەنە مۆدێرنەکانی AKM Business CMS بناسە',
                'lang' => 'ckb',
                'category_id' => '16',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS سیستەمێکی بەڕێوەبردنی ناوەڕۆکی مۆدێرن و ئاسایشبەرزە کە بۆ بەڕێوەبردنی ماڵپەڕە بازرگانی و دامەزراوەییەکان پەرەپێدراوە.',
                'content' => '<p><strong>AKM Business CMS</strong> سیستەمێکی بەڕێوەبردنی ناوەڕۆکی مۆدێرن و گشتگیرە کە بۆ دروستکردنی ماڵپەڕە پێشکەوتووەکان، پۆرتالە بازرگانییەکان و پلاتفۆرمە دیجیتاڵییەکان پەرەپێدراوە. بە ڕووکاری بەڕێوەبردنی ئاسان، تەلارسازی نەرم و ستانداردە بەرزەکانی ئاسایش، بەرهەم و خزمەتگوزارییەکانت دەباتە ئاستێکی باڵاتر.</p>
<p><strong>تایبەتمەندییە سەرەکییەکان:</strong></p>
<ul>
<li><strong>مۆتۆری فرەزمانی سروشتی:</strong> بەڕێوەبردنی ئاسانی وەرگێڕان و ناوەڕۆک لە هەموو مۆدۆلەکاندا.</li>
<li><strong>تەلارسازی مۆدۆلاری نەرم:</strong> دیزاینی سیستەم کە بەپێی پێداویستییەکانی دامەزراوەکەت فراوان دەکرێت.</li>
<li><strong>باشکراو بۆ SEO و خێرایی:</strong> لینکی دیجیتاڵی خاوێن، مێتا-داتای داینامیکی و خێرایی بەرزی کارکردن.</li>
<li><strong>ئاسایشی ئاستی بەرز:</strong> کۆنترۆڵی ناوەندی دەسەڵاتەکان (ACL) و پاراستنی بەهێزی زانیارییەکان.</li>
<li><strong>دیزاینی وەڵامدەرەوەی مۆبایل:</strong> ئەزموونی بەکارهێنانی بێ کەموکوڕی لە مۆبایل، تابلێت و کۆمپیوتەردا.</li>
</ul>
<p>لە جیهانی دیجیتاڵدا بە AKM Business CMS جیاوازبە و ڕێگەخۆشکەربە بۆ بەرەوپێشچوونی کارەکانت.</p>',
                'seo' => 'لایەنە-مۆدێرنەکانی-akm-business-cms-بناسە',
                'title' => 'لایەنە مۆدێرنەکانی AKM Business CMS بناسە - {{build_name}}',
                'keywords' => 'akm business cms، سیستەمی بەڕێوەبردنی ناوەڕۆک، cms فرەزمان، cms بازرگانی، SEO cms',
                'description' => 'لایەنە مۆدێرنەکانی AKM Business CMS بناسە؛ سیستەمێکی بەڕێوەبردنی ناوەڕۆکی مۆدێرن کە بۆ ماڵپەڕ و پلاتفۆرمە بازرگانییەکان گەشەی پێدراوە.',
                'image' => 'akm_business_cms_cover.jpg',
                'user_id' => '1'
            ),
            // Swedish Content
            array(
                'name' => 'Utforska de Moderna Aspekterna av AKM Business CMS',
                'lang' => 'sv',
                'category_id' => '17',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS är ett modernt och säkert innehållshanteringssystem utvecklat för företagswebbplatser och dynamiska plattformar.',
                'content' => '<p><strong>AKM Business CMS</strong> är ett omfattande publiceringssystem för företag som utvecklats för att skapa moderna webbapplikationer, företagsportaler och dynamiska plattformar. Med ett intuitivt administrationsgränssnitt, flexibel arkitektur och höga säkerhetsstandarder tar det din digitala närvaro till nästa nivå.</p>
<p><strong>Viktiga Funktioner:</strong></p>
<ul>
<li><strong>Inbyggd Flerspråkig Motor:</strong> Smidig hantering av översättningar och lokalisering i alla moduler.</li>
<li><strong>Modulär Arkitektur:</strong> Skalbar systemdesign anpassad för företagets behov.</li>
<li><strong>SEO & Prestandaoptimering:</strong> Rena URL:er, dynamisk metadata och blixtsnabb laddning.</li>
<li><strong>Företagssäkerhet:</strong> Centraliserad behörighetskontroll (ACL) och säkert dataskydd.</li>
<li><strong>Helt Mobilanpassad:</strong> Enastående användarupplevelse på mobiler, surfplattor och datorer.</li>
</ul>
<p>Stärk din digitala närvaro med AKM Business CMS och leverera moderna webblösningar enkelt.</p>',
                'seo' => 'utforska-de-moderna-aspekterna-av-akm-business-cms',
                'title' => 'Utforska de Moderna Aspekterna av AKM Business CMS - {{build_name}}',
                'keywords' => 'akm business cms, innehållshanteringssystem, företags-cms, flerspråkigt cms, seo cms',
                'description' => 'Utforska de moderna aspekterna av AKM Business CMS, ett modernt och modult innehållshanteringssystem för företagswebbplatser och dynamiska plattformar.',
                'image' => 'akm_business_cms_cover.jpg',
                'user_id' => '1'
            ),
            // Zazakî Content
            array(
                'name' => 'Dormaliyên Newejenê AKM Business CMS Bişasna',
                'lang' => 'zza',
                'category_id' => '18',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS, yew sistemo averşiyaye û ewle yê idarekarda zerreyî yo ke seba platformanê karsazî û malperanê modernan ameyo raverberdene.',
                'content' => '<p><strong>AKM Business CMS</strong>, yew sistemo berfireh û ewle yê idarekarda zerreyî yo ke seba viraştena malperanê modernan, portalanê karsazî û platformanê dîjîtalan ameyo raverberdene. Bi panela idarekerdişi ya kîbare, binesaziya elastîk û pîvananê ewlehî yê berzan, karsaziya şima dinyaya dîjîtale de keno serek.</p>
                <p><strong>Taybetmendiyê ke Vejênê Pêş:</strong></p>
                    <ul>
                        <li><strong>Motora Zaf-Ziwanî:</strong> İdarekerdena hêsan û bêkûsûr ya ziwan û zerreyî di pêro modulan de.</li>
                        <li><strong>Avahiya Modulere:</strong> Dîzayno ke li gorî hewcedariyanê karsaziyê beno hîra û xwerû.</li>
                        <li><strong>SEO û Optimîzasyonê Lezî:</strong> Linkê dînamîkî, metadata paqije û binesaziya pîroz seba motoranê lêgerînî.</li>
                        <li><strong>Ewlehiya Berze:</strong> Sistemo navendî yê kontrola destûrî (ACL) û pawitişê daneyan.</li>
                        <li><strong>Dîzayno Mobîlî rê Munasib:</strong> Ezmûno bêkûsûr di mobîl, tablet û kompîturan de.</li>
                    </ul>
                <p>Bi AKM Business CMS dinyaya dîjîtale de karsaziya xo biroşe pêş û malpera xo biresnê asteke berze.</p>',
                'seo' => 'dormaliyen-newejene-akm-business-cms-bisasna',
                'title' => 'Dormaliyên Newejenê AKM Business CMS Bişasna - {{build_name}}',
                'keywords' => 'akm business cms, sistemo idarekarda zerreyî, cms karsazî, zaf ziwanî cms, seo cms',
                'description' => 'Dormaliyên newejenê AKM Business CMS bişasna; yew sistemo averşiyaye û ewle yê idarekarda zerreyî yo ke seba platformanê karsazî ameyo raverberdene.',
                'image' => 'akm_business_cms_cover.jpg',
                'user_id' => '1'
            ),
            // Russian Content
            array(
                'name' => 'Познакомьтесь с современными возможностями AKM Business CMS',
                'lang' => 'ru',
                'category_id' => '19',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS — это высокопроизводительная модульная система управления контентом, созданная для современных веб-сайтов и корпоративных платформ.',
                'content' => '<p><strong>AKM Business CMS</strong> — это комплексная масштабируемая система управления контентом корпоративного уровня, разработанная для создания современных веб-приложений, корпоративных порталов и динамических платформ. Интуитивно понятная панель управления, гибкая архитектура и передовые стандарты безопасности выводят ваше цифровое присутствие на новый уровень.</p>
                <p><strong>Ключевые преимущества и возможности:</strong></p>
                <ul>
                    <li><strong>Встроенная мультиязычность:</strong> Безупречное управление локализацией и переводами во всех модулях и типах контента.</li>
                    <li><strong>Модульная архитектура:</strong> Гибкая и масштабируемая структура, адаптированная под требования современного бизнеса.</li>
                    <li><strong>SEO и производительность:</strong> Оптимизированные ЧПУ (SEF URLs), динамические метаданные и высокая скорость загрузки.</li>
                    <li><strong>Корпоративная безопасность:</strong> Централизованный контроль доступа (ACL), защищенные эндпоинты и надежное хранение данных.</li>
                    <li><strong>Адаптивный дизайн:</strong> Безупречный пользовательский опыт на смартфонах, планшетах и компьютерах.</li>
                </ul>
                <p>Развивайте свой бизнес в цифровой среде с помощью AKM Business CMS и внедряйте передовые веб-стандарты с легкостью.</p>',
                'seo' => 'poznakomtes-s-sovremennymi-vozmozhnostyami-akm-business-cms',
                'title' => 'Познакомьтесь с современными возможностями AKM Business CMS - {{build_name}}',
                'keywords' => 'akm business cms, система управления контентом, корпоративная cms, мультиязычная cms, seo оптимизация',
                'description' => 'Откройте для себя AKM Business CMS — модульную высокопроизводительную платформу для современных корпоративных сайтов и веб-сервисов.',
                'image' => 'akm_business_cms_cover.jpg',
                'user_id' => '1'
            )
        );
        
        $data = installer_replace_build_name($data);

        $this->model = Load::model('mod_content');
        foreach ($data AS $key => $value){
            if ($this->model->exists_name_and_lang($value['name'], $value['lang']) === FALSE){
                $lang = $value['lang'];
                $starterName = $this->installer_resolve_category_starter_label('bulletin', $lang);
                if ($starterName === FALSE) {
                    $this->error("Starter category label not found for " . $lang);
                    return FALSE;
                }
                $query = "SELECT id FROM `mod_content_category` WHERE lang='" . addslashes($lang) . "' AND name='" . addslashes($starterName) . "' AND parent_id != 0 ORDER BY id ASC LIMIT 1";
                $this->db->fetch_array($query);
                if ($this->db->getSize() === 0) {
                    $this->error("Category ID not found in mod_content_category for language " . $lang . " and name " . $starterName);
                    return FALSE;
                }
                $rows = $this->db->getResult()->toArray();
                $categoryId = (int) $rows[0]['id'];
                $value['category_id'] = $categoryId;

                $this->success("Added: " . $value['name']);
                $keys = array_keys($value);
                foreach ($keys as $k){
                    $this->model->$k = $value[$k];
                }
                if ($this->model->save() === FALSE){
                    $this->error("Failed to add: " . $value['name']);
                    return FALSE;
                }
                continue;
            }
            else
            {
                $this->warning("Already exists: " . $value['name']);
            }
        }
        return TRUE;
    }

}
