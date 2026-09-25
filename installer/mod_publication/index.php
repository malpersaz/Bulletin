<?php

class mod_publication extends installerbase implements installerbaseinterface
{
    public $tables;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_publication';
        // Slider is part of the content schema, not a separate module table.
        $this->tables = array(
            'mod_publication',
            'publications'
        );
        // Hero slider management reuses edit permission on content records.
        $this->roles = array(
            'add', 'edit', 'delete', 'list', 'gallery', 'file', 'edit_status', 'change_status', 'approval', 'moderate_edit', 'moderate_delete', 'video_download'
        );
    }

    public function install()
    {
        // Create the base content table together with the updated view definition.
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        if ($this->insert_roles() === FALSE) {
            return FALSE;
        }
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
            array(
                'name' => 'AKM Business CMS Yayın ve Akademik Raporu',
                'lang' => 'tr',
                'category_id' => '23',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AKM Business CMS platformunun modüler yayın yönetimi, akademik belge arşivleme ve dijital kütüphane altyapısı üzerine hazırlanan teknik rapor.',
                'content' => '<p><strong>AKM Business CMS Yayın ve Akademik Raporu</strong>, kurumsal yayınların, araştırma makalelerinin ve dijital dokümanların merkezi bir altyapıda güvenle arşivlenmesi ve sunulması amacıyla hazırlanmıştır.</p><p><strong>Öne Çıkan Özellikler:</strong></p><ul><li><strong>Çoklu Dil Desteği:</strong> Tüm yayınlar için bağımsız dil yönetimi.</li><li><strong>Doküman ve PDF Entegrasyonu:</strong> Yayınlara indirilebilir PDF ve teknik rapor ekleme.</li><li><strong>Gelişmiş SEO Yapısı:</strong> Arama motorları için optimize edilmiş içerik ve meta yapısı.</li></ul>',
                'seo' => 'akm_business_cms_yayin_ve_akademik_raporu',
                'title' => 'AKM Business CMS Yayın ve Akademik Raporu  - {{build_name}}',
                'keywords' => 'akm business cms, yayın, akademik rapor, pdf, dijital kütüphane',
                'description' => 'AKM Business CMS yayın ve akademik rapor detayları',
                'image' => 'akm_academic_report_cover.jpg',
                'user_id' => '1'
            ),
            array(
                'name' => 'AKM Business CMS Publication & Academic Report',
                'lang' => 'en',
                'category_id' => '22',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Technical publication report covering modular publishing, academic archiving, and digital library capabilities of AKM Business CMS.',
                'content' => '<p><strong>AKM Business CMS Publication & Academic Report</strong> is designed to manage enterprise publications, research papers, and technical reports within a centralized secure framework.</p><p><strong>Key Highlights:</strong></p><ul><li><strong>Native Multilingual Support:</strong> Seamless localization across all enabled system languages.</li><li><strong>Document & PDF Attachments:</strong> Attach downloadable PDF research papers and technical documents.</li><li><strong>SEO Optimized:</strong> Search engine friendly URLs and metadata management.</li></ul>',
                'seo' => 'akm_business_cms_publication_academic_report',
                'title' => 'AKM Business CMS Publication & Academic Report  - {{build_name}}',
                'keywords' => 'akm business cms, publication, academic report, pdf, digital library',
                'description' => 'Details and specifications of AKM Business CMS publication report',
                'image' => 'akm_academic_report_cover.jpg',
                'user_id' => '1'
            ),
            array(
                'name' => 'Rapora Weşan û Akademîk a AKM Business CMS',
                'lang' => 'ku',
                'category_id' => '21',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Rapora teknîkî ya ku li ser alîgirî û rêveberiya weşanan, arşîvkirina belgeyên akademîk û pirtûkxaneya dîjîtal di AKM Business CMS de hatî amadekirin.',
                'content' => '<p><strong>Rapora Weşan û Akademîk a AKM Business CMS</strong> bi armanca rêveberî û belavkirina ewledar a weşanên saziyê, meqaleyên lêkolînê û raporên teknîkî di bin pergaleke navendî de hatî sêwirandin.</p><p><strong>Taybetmendiyên Sereke:</strong></p><ul><li><strong>Piştgiriya Gelek Zimanan:</strong> Rêveberiya nerm di hemû zimanên çalak de.</li><li><strong>Entegrasyona PDF û Dosyeyan:</strong> Pêvekirina belgeyên PDF ên daxistinê bi weşanê re.</li><li><strong>Optimîzasyona SEO:</strong> Girêdanên paqij û meta-daneyên SEO ji bo motorên lêgerînê.</li></ul>',
                'seo' => 'rapora_wesan_u_akademik_a_akm_business_cms',
                'title' => 'Rapora Weşan û Akademîk a AKM Business CMS  - {{build_name}}',
                'keywords' => 'akm business cms, weşan, rapora akademik, pdf, pirtukxaneya dijital',
                'description' => 'Detay û agahiyên rapora weşanên AKM Business CMS',
                'image' => 'akm_academic_report_cover.jpg',
                'user_id' => '1'
            ),
            array(
                'name' => 'تقرير المنشورات والأبحاث لـ AKM Business CMS',
                'lang' => 'ar',
                'category_id' => '24',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'تقرير تقني حول إدارة المنشورات والأرشفة الأكاديمية والمكتبة الرقمية في نظام AKM Business CMS.',
                'content' => '<p><strong>تقرير المنشورات والأبحاث لـ AKM Business CMS</strong> تم إعداده لإدارة وأرشفة الأوراق البحثية والمنشورات المؤسسية بأمان عالٍ.</p>',
                'seo' => 'تقرير_المنشورات_والأبحاث_لـ_akm_business_cms',
                'title' => 'تقرير المنشورات والأبحاث لـ AKM Business CMS  - {{build_name}}',
                'keywords' => 'akm business cms, منشورات, تقرير أكاديمي, pdf',
                'description' => 'تفاصيل تقرير منشورات AKM Business CMS',
                'image' => 'akm_academic_report_cover.jpg',
                'user_id' => '1'
            ),
            array(
                'name' => 'AKM Business CMS Publikations- und Forschungsbericht',
                'lang' => 'de',
                'category_id' => '25',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Technischer Bericht über die modulare Publikationsverwaltung und akademische Archivierung im AKM Business CMS.',
                'content' => '<p>Der <strong>AKM Business CMS Publikations- und Forschungsbericht</strong> befasst sich mit der sicheren Verwaltung digitaler Publikationen und Berichte.</p>',
                'seo' => 'akm_business_cms_publikations_und_forschungsbericht',
                'title' => 'AKM Business CMS Publikations- und Forschungsbericht  - {{build_name}}',
                'keywords' => 'akm business cms, publikationen, forschungsbericht, pdf',
                'description' => 'Details zum Publikationsbericht von AKM Business CMS',
                'image' => 'akm_academic_report_cover.jpg',
                'user_id' => '1'
            ),
            array(
                'name' => "Rapport de Publications et d'Études AKM Business CMS",
                'lang' => 'fr',
                'category_id' => '26',
                'status' => 'y',
                'deck' => 'y',
                'summary' => "Rapport technique sur la gestion modulaire des publications et l'archivage académique dans AKM Business CMS.",
                'content' => "<p>Le <strong>Rapport de Publications et d'Études AKM Business CMS</strong> présente les capacités d'archivage numérique et d'édition d'articles.</p>",
                'seo' => 'rapport_de_publications_et_detudes_akm_business_cms',
                'title' => "Rapport de Publications et d'Études AKM Business CMS  - {{build_name}}",
                'keywords' => 'akm business cms, publications, rapport academique, pdf',
                'description' => 'Détails du rapport de publication AKM Business CMS',
                'image' => 'akm_academic_report_cover.jpg',
                'user_id' => '1'
            ),
            array(
                'name' => 'ڕاپۆرتی بڵاوکراوەکان و ئەکادیمی AKM Business CMS',
                'lang' => 'ckb',
                'category_id' => '27',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'ڕاپۆرتی تەکنیکی سەبارەت بە بەڕێوەبردنی بڵاوکراوەکان و ئەرشیفی بەڵگەنامەی ئەکادیمی لە AKM Business CMS.',
                'content' => '<p><strong>ڕاپۆرتی بڵاوکراوەکان و ئەکادیمی AKM Business CMS</strong> بۆ بەڕێوەبردن و بڵاوکردنەوەی ئارامی توێژینەوەکان پەرەپێدراوە.</p>',
                'seo' => 'ڕاپۆرتی_بڵاوکراوەکان_و_ئەکادیمی_akm_business_cms',
                'title' => 'ڕاپۆرتی بڵاوکراوەکان و ئەکادیمی AKM Business CMS  - {{build_name}}',
                'keywords' => 'akm business cms, بڵاوکراوەکان, ڕاپۆرت, pdf',
                'description' => 'وردەکاری ڕاپۆرتی بڵاوکراوەکانی AKM Business CMS',
                'image' => 'akm_academic_report_cover.jpg',
                'user_id' => '1'
            ),
            array(
                'name' => 'AKM Business CMS Publikations- och Forskningsrapport',
                'lang' => 'sv',
                'category_id' => '28',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Teknisk rapport om modulär publikationshantering och akademisk arkivering i AKM Business CMS.',
                'content' => '<p><strong>AKM Business CMS Publikations- och Forskningsrapport</strong> täcker säker hantering av digitala böcker och forskningsrapporter.</p>',
                'seo' => 'akm_business_cms_publikations_och_forskningsrapport',
                'title' => 'AKM Business CMS Publikations- och Forskningsrapport  - {{build_name}}',
                'keywords' => 'akm business cms, publikationer, rapport, pdf',
                'description' => 'Detaljer för AKM Business CMS publikationsrapport',
                'image' => 'akm_academic_report_cover.jpg',
                'user_id' => '1'
            ),
            array(
                'name' => 'Raporê Weşanan û Akademîk yê AKM Business CMS',
                'lang' => 'zza',
                'category_id' => '29',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Raporê teknîkî ser idarekerdena weşanan û arşîvkerdena meqaleyan di AKM Business CMS de.',
                'content' => '<p><strong>Raporê Weşanan û Akademîk yê AKM Business CMS</strong> seba idarekerdena ewle ya weşanan û dosyeyan ameyo hazirkerdene.</p>',
                'seo' => 'rapore_wesanan_u_akademik_ye_akm_business_cms',
                'title' => 'Raporê Weşanan û Akademîk yê AKM Business CMS  - {{build_name}}',
                'keywords' => 'akm business cms, weşan, rapor, pdf',
                'description' => 'Detayê raporê weşanî yê AKM Business CMS',
                'image' => 'akm_academic_report_cover.jpg',
                'user_id' => '1'
            ),
            // Russian Academic Report
            array(
                'name' => 'Отчет о публикациях и научных исследованиях AKM Business CMS',
                'lang' => 'ru',
                'category_id' => '29',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Технический отчет об управлении публикациями, научными статьями и PDF-документами в AKM Business CMS.',
                'content' => '<p><strong>Отчет о публикациях и научных исследованиях AKM Business CMS</strong> подготовлен для эффективного и безопасного распространения публикаций и документов.</p>',
                'seo' => 'otchet_o_publikatsiyakh_i_nauchnykh_issledovaniyakh_akm_business_cms',
                'title' => 'Отчет о публикациях и научных исследованиях AKM Business CMS - {{build_name}}',
                'keywords' => 'akm business cms, публикации, отчет, pdf',
                'description' => 'Подробности об отчете о публикациях AKM Business CMS',
                'image' => 'akm_academic_report_cover.jpg',
                'user_id' => '1'
            ),
            array(
                'name' => 'Rêberiya Taybetmendiyên Modula Weşanan (mod_publication)',
                'lang' => 'ku',
                'category_id' => '21',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Gotara berfireh a derbarê hemû taybetmendî, avahî, meqale, pirtûk û entegrasyonên belgeyên PDF di Modula Weşanan (mod_publication) de di Amed Framework de.',
                'content' => '<p><img src="/static/mod_publication/mod_publication_cover.jpg" alt="Rêberiya Taybetmendiyên Modula Weşanan" class="img-fluid rounded mb-3"></p><p><strong>Modula Weşanan (mod_publication)</strong> di Amed Framework (AKM Business CMS) de moduleke moduler û navendî ye ku ji bo belavkirin, arşîvkirin û nîşandana pirtûk, meqaleyên akademîk, raporên teknîkî, govar û weşanên saziyê hatî pêşxistin.</p><h3>1. Rêveberiya Naverok û Agahiyên Weşanê</h3><p>Modul destûrê dide ku agahiyên berfireh ji bo her weşanekê li danegehê (database) werin tomarkirin û nûjenkirin:</p><ul><li><strong>Navê Weşanê (name):</strong> Nasnav û sernavê sereke yê weşan an pirtûkê.</li><li><strong>Kurte / Resumê (summary):</strong> Şiroveya kurt ji bo nîşandana di lîste, pirtûkxane û kartên malperê de.</li><li><strong>Gotar û Naverok (content):</strong> Nivîsa dirêj û naveroka berfireh bi piştgiriya edîtorê dewlemend ê HTML.</li></ul><h3>2. Kategoriya Dînamîk û Piştgiriya Pirzimanî</h3><ul><li><strong>Kategorîzekirin (mod_publication_category):</strong> Dabeşkirina weşanan li gorî rûpel û beşên taybet (wekî meqale, pirtûk, rapor, daxuyantî).</li><li><strong>Piştgiriya Gelek Zimanan (Multilingual Engine):</strong> Rêveberiya weşanan di hemû zimanên çalak de bi yek tora danegehê û lokalîzasyona navendî.</li></ul><h3>3. Entegrasyona Medya, Galerî û Belgeyan (PDF)</h3><ul><li><strong>Wêneyê Cover (image):</strong> Mezinbûn û nûjenkirina otomatîk a wêneyên berbiçav bi navgîniya ImageService (wêneyê mezin 1600px û thumbnail 600px).</li><li><strong>Galerî (mod_gallery):</strong> Pêvekirina albûmên wêneyan bi weşanê re ji bo nîşandana pirtûk û belgeyên dîtbarî.</li><li><strong>Dosye û Belge / PDF (mod_attachment):</strong> Pêvekirina belgeyên PDF, pirtûkên dîjîtal û dosyayên daxistinê ji bo serdanerên malperê.</li><li><strong>Entegrasyona Vîdyoyê (mod_video):</strong> Pêvekirin û lîstina vîdyoyan rasterast di rûpela weşanê de.</li></ul><h3>4. Kontrolên Nîşandanê û Slider</h3><ul><li><strong>Rewşa Çalak/Neçalak (status):</strong> Kontrolkirina weşandina gotarê di malperê de.</li><li><strong>Weşana Bijarte / Manset (deck):</strong> Nîşankirin û derxistina weşanên girîng li ser manseta malperê.</li><li><strong>Entegrasyona Hero Slider (slider):</strong> Zêdekirina bilez a weşanê li ser slidera sereke ya malperê.</li></ul><h3>5. Optimîzasyona SEO, Ewlehî (ACL) û Statîstîk</h3><ul><li><strong>Dînamîk SEO URL (seo slug):</strong> Girêdanên paqij û hevalbendên motorên lêgerînê.</li><li><strong>Meta-Daneyên SEO (title, keywords, description):</strong> Rêveberiya sernav, peyvên mîlî û danasînên motorên lêgerînê.</li><li><strong>Kontrola Gihîştinê (mod_acl):</strong> Sînordarkirina dîtina weşanê ji bo grûpên bikarhêneran ên taybet.</li><li><strong>Jimartina Dîtinan (view_count):</strong> Şopandina jimara serdaner û xwendevanên rûpela weşanê.</li></ul>',
                'seo' => 'reberiya_taybetmendiyen_mod_publication',
                'title' => 'Rêberiya Taybetmendiyên Modula Weşanan  - {{build_name}}',
                'keywords' => 'mod_publication, taybetmediyen wesanan, amed framework, cms, publication',
                'description' => 'Zanyarî û gotara berfireh li ser hemû taybetmendiyên modula mod_publication di Amed Framework de.',
                'image' => 'mod_publication_cover.jpg',
                'user_id' => '8'
            ),
            array(
                'name' => 'Publication Module Features Guide (mod_publication)',
                'lang' => 'en',
                'category_id' => '22',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Comprehensive technical guide detailing all features, architecture, PDF document attachments, and media integrations of the Publication Module (mod_publication) in Amed Framework.',
                'content' => '<p><img src="/static/mod_publication/mod_publication_cover.jpg" alt="Publication Module Features Guide" class="img-fluid rounded mb-3"></p><p><strong>The Publication Module (mod_publication)</strong> in Amed Framework (AKM Business CMS) is a robust, modular system engineered for managing, archiving, and presenting enterprise publications, academic research papers, periodicals, and PDF documents.</p><h3>1. Publication Content & Data Management</h3><ul><li><strong>Publication Title (name):</strong> Primary title of the article or publication.</li><li><strong>Summary (summary):</strong> Excerpt for listing grids, cards, and library views.</li><li><strong>Full Article (content):</strong> Rich HTML content editor for detailed articles.</li></ul><h3>2. Dynamic Categorization & Multilingual Engine</h3><ul><li><strong>Taxonomy (mod_publication_category):</strong> Structured category hierarchy for organizing publications (articles, papers, reports).</li><li><strong>Multilingual Engine:</strong> Native localization support across all active languages.</li></ul><h3>3. Rich Media, Gallery & PDF Attachments</h3><ul><li><strong>Cover Image (image):</strong> Automatic resizing via ImageService (1600px main, 600px thumbnail).</li><li><strong>Photo Galleries (mod_gallery):</strong> Attach multi-image photo galleries to publications.</li><li><strong>Document Attachments (mod_attachment):</strong> PDF report uploads and downloadable documents.</li><li><strong>Video Integration (mod_video):</strong> Embedded video player support.</li></ul><h3>4. Display & Hero Slider Controls</h3><ul><li><strong>Publication Status (status):</strong> Publish and draft status controls.</li><li><strong>Featured Publication (deck):</strong> Highlight key publications on main index showcases.</li><li><strong>Hero Slider Integration (slider):</strong> Push publication directly to the main hero slider.</li></ul><h3>5. SEO, Security (ACL) & Analytics</h3><ul><li><strong>SEO Slugs & Metadata:</strong> Dynamic clean URLs, meta titles, keywords, and descriptions.</li><li><strong>Access Control (mod_acl):</strong> Role-based access control and member restrictions.</li><li><strong>View Analytics (view_count):</strong> Engagement tracking with view counts.</li></ul>',
                'seo' => 'publication_module_features_guide',
                'title' => 'Publication Module Features Guide  - {{build_name}}',
                'keywords' => 'mod_publication, publication features, amed framework, cms, reports',
                'description' => 'Comprehensive technical guide covering all features of mod_publication in Amed Framework.',
                'image' => 'mod_publication_cover.jpg',
                'user_id' => '8'
            ),
            array(
                'name' => 'Yayınlar Modülü Özellikler Rehberi (mod_publication)',
                'lang' => 'tr',
                'category_id' => '23',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Amed Framework içerisindeki Yayınlar Modülü (mod_publication) mimarisi, PDF doküman ekleri, medya entegrasyonları ve tüm özelliklerini anlatan kapsamlı rehber.',
                'content' => '<p><img src="/static/mod_publication/mod_publication_cover.jpg" alt="Yayınlar Modülü Özellikler Rehberi" class="img-fluid rounded mb-3"></p><p><strong>Yayınlar Modülü (mod_publication)</strong>, Amed Framework (AKM Business CMS) altyapısında kurumsal yayınları, akademik makaleleri, dijital raporları ve dergileri yönetmek için tasarlanmış gelişmiş bir modüldür.</p><h3>1. İçerik ve Veri Yönetimi</h3><ul><li><strong>Yayın Adı (name):</strong> Yayının ana başlığı.</li><li><strong>Özet (summary):</strong> Liste ve kütüphane kartları için kısa özet.</li><li><strong>Detaylı İçerik (content):</strong> Zengin HTML editör desteği ile yayın içeriği.</li></ul><h3>2. Dinamik Kategori ve Çoklu Dil Desteği</h3><ul><li><strong>Kategorizasyon (mod_publication_category):</strong> Yayınların tür ve kategorilere göre ayrılması.</li><li><strong>Çoklu Dil Yapısı:</strong> Tüm aktif dillerde kesintisiz içerik ve lokalizasyon yönetimi.</li></ul><h3>3. Medya, Galeri ve PDF Doküman Entegrasyonları</h3><ul><li><strong>Kapak Görseli (image):</strong> ImageService ile otomatik boyutlandırma (1600px ana görsel, 600px thumbnail).</li><li><strong>Fotoğraf Galerisi (mod_gallery):</strong> Yayına çoklu fotoğraf albümü bağlama.</li><li><strong>Dosya ve Ekler (mod_attachment):</strong> İndirilebilir PDF ve dijital rapor ekleri.</li><li><strong>Video Entegrasyonu (mod_video):</strong> Yayına video ekleme desteği.</li></ul><h3>4. Görüntüleme ve Slider Kontrolleri</h3><ul><li><strong>Durum Kontrolü (status):</strong> Yayında / taslak durumu.</li><li><strong>Öne Çıkarılan Yayın (deck):</strong> Seçkin yayınları anasayfada öne çıkarma.</li><li><strong>Hero Slider Entegrasyonu (slider):</strong> Ana slider alanında görüntüleme seçeneği.</li></ul><h3>5. SEO, Erişim Kontrolü (ACL) ve İstatistikler</h3><ul><li><strong>SEO Bağlantıları ve Meta Veriler:</strong> Özel SEO bağlantısı, başlık, anahtar kelimeler ve açıklama.</li><li><strong>Yetkilendirme (mod_acl):</strong> Yayın bazlı kullanıcı ve grup erişim yetkileri.</li><li><strong>Görüntülenme Sayacı (view_count):</strong> Yayın detay sayfası ziyaret istatistiği.</li></ul>',
                'seo' => 'yayinlar_modulu_ozellikler_rehberi',
                'title' => 'Yayınlar Modülü Özellikler Rehberi  - {{build_name}}',
                'keywords' => 'mod_publication, yayın özellikleri, amed framework, cms, dijital kütüphane',
                'description' => 'Amed Framework Yayınlar Modülü mod_publication tüm özellikleri ve entegrasyon rehberi.',
                'image' => 'mod_publication_cover.jpg',
                'user_id' => '8'
            ),
            array(
                'name' => 'دليل ميزات وحدة المنشورات (mod_publication)',
                'lang' => 'ar',
                'category_id' => '24',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'دليل شامل يغطي جميع ميزات وبنية وتكاملات الملفات وإرفاق PDF لـ وحدة المنشورات (mod_publication) في نظام Amed Framework.',
                'content' => '<p><strong>وحدة المنشورات (mod_publication)</strong> هي نظام نمطي متقدم في Amed Framework لإدارة وعرض المنشورات المؤسسية، المقالات الأكاديمية، والتقارير الرقمية بصيغة PDF.</p><ul><li><strong>إدارة المحتوى:</strong> العنوان، الملخص، والمحتوى التفصيلي HTML.</li><li><strong>التصنيف والتعدد اللغوي:</strong> ربط الفئات وتعدد اللغات الكامل.</li><li><strong>تكامل الوسائط والملفات:</strong> الصور الرئيسية، ألبومات الصور، المرفقات بصيغة PDF، والفيديوهات.</li><li><strong>عناصر التحكم بالعرض:</strong> الحالة، التميز، والربط مع Hero Slider.</li><li><strong>محركات البحث والأمان:</strong> روابط SEO، الميتا، وإذونات الوصول ACL.</li></ul>',
                'seo' => 'دليل_ميزات_وحدة_المنشورات',
                'title' => 'دليل ميزات وحدة المنشورات  - {{build_name}}',
                'keywords' => 'mod_publication, ميزات المنشورات, amed framework, cms',
                'description' => 'تفاصيل ودليل ميزات وحدة المنشورات mod_publication',
                'image' => 'mod_publication_cover.jpg',
                'user_id' => '8'
            ),
            array(
                'name' => 'Publikationsmodul Funktionshandbuch (mod_publication)',
                'lang' => 'de',
                'category_id' => '25',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Umfassendes Handbuch zu allen Funktionen, Architekturen, PDF-Dateianhängen und Medienintegrationen des Publikationsmoduls (mod_publication) im Amed Framework.',
                'content' => '<p>Das <strong>Publikationsmodul (mod_publication)</strong> im Amed Framework ist ein leistungsstarkes System zur Verwaltung digitaler Publikationen, Forschungsberichte und PDF-Dokumente.</p><ul><li><strong>Inhaltsverwaltung:</strong> Titel, Zusammenfassung und HTML-Hauptinhalt.</li><li><strong>Kategorisierung & Mehrsprachigkeit:</strong> Untergliederung in Kategorien und Unterstützung aller Systemsprachen.</li><li><strong>Medien- & PDF-Integration:</strong> Automatische Bildskalierung (ImageService), Fotogalerien, PDF-Anhänge und Videos.</li><li><strong>Anzeigestuerung:</strong> Status, Hervorhebungen und Hero-Slider-Verknüpfung.</li><li><strong>SEO & Rechteverwaltung:</strong> Optimierte URLs, Meta-Daten und ACL-Zugriffsbeschränkungen.</li></ul>',
                'seo' => 'publikationsmodul_funktionshandbuch',
                'title' => 'Publikationsmodul Funktionshandbuch  - {{build_name}}',
                'keywords' => 'mod_publication, publikationsfunktionen, amed framework, cms',
                'description' => 'Detailliertes Funktionshandbuch für das Modul mod_publication im Amed Framework.',
                'image' => 'mod_publication_cover.jpg',
                'user_id' => '8'
            ),
            array(
                'name' => 'Guide des Fonctionnalités du Module Publications (mod_publication)',
                'lang' => 'fr',
                'category_id' => '26',
                'status' => 'y',
                'deck' => 'y',
                'summary' => "Guide complet couvrant toutes les fonctionnalités, l'architecture, les pièces jointes PDF et les intégrations médias du module Publications (mod_publication) dans Amed Framework.",
                'content' => "<p>Le <strong>Module Publications (mod_publication)</strong> dans Amed Framework est un système modulaire conçu pour gérer, archiver et publier des rapports d'entreprise, des articles académiques et des fichiers PDF.</p><ul><li><strong>Gestion du Contenu:</strong> Titre, résumé et contenu détaillé HTML.</li><li><strong>Catégorisation & Multilingue:</strong> Classification dynamique et prise en charge native du multilinguisme.</li><li><strong>Intégration Médias & PDF:</strong> Redimensionnement d'images (ImageService), galeries photos, téléchargements PDF et vidéos.</li><li><strong>Contrôles d'Affichage:</strong> Statut, mise en avant et intégration Hero Slider.</li><li><strong>SEO & Contrôle d'Accès:</strong> URLs SEO, métadonnées et restrictions d'accès ACL.</li></ul>",
                'seo' => 'guide_fonctionnalites_module_publications',
                'title' => 'Guide des Fonctionnalités du Module Publications  - {{build_name}}',
                'keywords' => 'mod_publication, publications, amed framework, cms, pdf',
                'description' => 'Guide détaillé des fonctionnalités et capacités du module mod_publication.',
                'image' => 'mod_publication_cover.jpg',
                'user_id' => '8'
            ),
            array(
                'name' => 'ڕێنمایی تایبەتمەندییەکانی مۆدۆلی بڵاوکراوەکان (mod_publication)',
                'lang' => 'ckb',
                'category_id' => '27',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'ڕێنمایی گشتگیر سەبارەت بە هەموو تایبەتمەندییەکان، تەلارسازی و بەستنەوەی PDF لە مۆدۆلی بڵاوکراوەکان (mod_publication) لە Amed Framework.',
                'content' => '<p><strong>مۆدۆلی بڵاوکراوەکان (mod_publication)</strong> لە Amed Framework سیستەمێکی مۆدۆلاری پێشکەوتووە بۆ بەڕێوەبردن و ئەرشیفکردنی بڵاوکراوەکان و بەڵگەنامەکانی PDF.</p><ul><li><strong>بەڕێوەبردنی ناوەڕۆک:</strong> ناوی بڵاوکراوە، پوختە و ناوەڕۆک.</li><li><strong>پۆڵێنکردن و فرەزمانی:</strong> بەستنەوە بە هاوپۆلەکان و پشتیوانی تەواوی زمانەکان.</li><li><strong>بەستنەوەی میدیا و PDF:</strong> وێنەی سەرەکی، ئەلبوومی وێنەکان، بەڵگەنامەکانی PDF و ڤیدیۆ.</li><li><strong>کۆنتڕۆڵەکانی نیشاندان:</strong> بارودۆخ، دیاریکراو، و بەستنەوە بە Hero Slider.</li><li><strong>سئو و ئاسایش:</strong> بەستەری SEO، میتا داتا، و دەسەڵاتەکانی ACL.</li></ul>',
                'seo' => 'ڕێنمایی_تایبەتمەندییەکانی_مۆدۆلی_بڵاوکراوەکان',
                'title' => 'ڕێنمایی تایبەتمەندییەکانی مۆدۆلی بڵاوکراوەکان  - {{build_name}}',
                'keywords' => 'mod_publication, بڵاوکراوەکان, amed framework, cms',
                'description' => 'زانیاری و ڕێنمایی گشتگیر سەبارەت بە مۆدۆلی mod_publication',
                'image' => 'mod_publication_cover.jpg',
                'user_id' => '8'
            ),
            array(
                'name' => 'Guide för Publikationsmodulens Funktioner (mod_publication)',
                'lang' => 'sv',
                'category_id' => '28',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Omfattande guide som täcker alla funktioner, arkitektur, PDF-bilagor och medieintegrationer i publikationsmodulen (mod_publication) i Amed Framework.',
                'content' => '<p><strong>Publikationsmodulen (mod_publication)</strong> i Amed Framework är ett kraftfullt och modulärt system för att hantera, arkivera och publicera digitala böcker och PDF-rapporter.</p><ul><li><strong>Innehållshantering:</strong> Titel, sammanfattning och utförlig HTML-text.</li><li><strong>Kategorisering & Flerspråkighet:</strong> Dynamisk kategoristruktur och stöd för alla aktiva språk.</li><li><strong>Medie- & PDF-integration:</strong> Automatisk bildskalning (ImageService), fotogallerier, PDF-bilagor och videor.</li><li><strong>Visningskontroller:</strong> Status, utvalda publikationer och Hero Slider-koppling.</li><li><strong>SEO & Åtkomstkontroll:</strong> SEO-vänliga länkar, metadata, ACL-behörigheter och visningsstatistik.</li></ul>',
                'seo' => 'guide_publikationsmodulens_funktioner',
                'title' => 'Guide för Publikationsmodulens Funktioner  - {{build_name}}',
                'keywords' => 'mod_publication, publikationsfunktioner, amed framework, cms',
                'description' => 'Detaljerad guide för funktioner och integrationer i mod_publication.',
                'image' => 'mod_publication_cover.jpg',
                'user_id' => '8'
            ),
            array(
                'name' => 'Rêberiya Taybetmendiyên Modula Weşanan (mod_publication)',
                'lang' => 'zza',
                'category_id' => '29',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Gotara berfireh a derbarê hemû taybetmendî, avahî û entegrasyonên belgeyên PDF di Modula Weşanan (mod_publication) de di Amed Framework de.',
                'content' => '<p><strong>Modula Weşanan (mod_publication)</strong> di Amed Framework de seba idarekerden û belavkerdena weşanan û dosyanê PDF yew modula berfireh a.</p><ul><li><strong>İdarekerdena Zerreyî:</strong> Sername, kurte û zerreyê hîra.</li><li><strong>Kategorî û Zaf-Ziwanî:</strong> Avahiya kategoriyan û piştgiriya zaf-ziwanî.</li><li><strong>Medya û PDF:</strong> Resmo serekî (ImageService), galerî, dosyeyê PDF û vîdyoyî.</li><li><strong>Kontrolê Asayişî:</strong> Rewş, weşano bijarte û Hero Slider.</li><li><strong>SEO û Ewlehî:</strong> Linkê SEO, meta-daneyî û destûrê ACL.</li></ul>',
                'seo' => 'reberiya_taybetmendiyen_mod_publication_zza',
                'title' => 'Rêberiya Taybetmendiyên Modula Weşanan  - {{build_name}}',
                'keywords' => 'mod_publication, weşan, amed framework, cms',
                'description' => 'Detayê modula mod_publication di Amed Framework de',
                'image' => 'mod_publication_cover.jpg',
                'user_id' => '8'
            ),
            // Russian Guide Article
            array(
                'name' => 'Руководство по функциям модуля публикаций (mod_publication)',
                'lang' => 'ru',
                'category_id' => '29',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Подробная статья обо всех возможностях, архитектуре и PDF-интеграциях модуля публикаций (mod_publication) в Amed Framework.',
                'content' => '<p><strong>Модуль публикаций (mod_publication)</strong> в Amed Framework предназначен для организации, архивирования и распространения книг, академических статей и PDF-документов.</p><ul><li><strong>Управление контентом:</strong> Заголовок, краткое описание и подробный текст.</li><li><strong>Категории и мультиязычность:</strong> Иерархическая структура и поддержка нескольких языков.</li><li><strong>Медиа и PDF:</strong> Обложка (ImageService), фотогалереи, вложения PDF и видео.</li><li><strong>Управление отображением:</strong> Статус, главная публикация и слайдер.</li><li><strong>SEO и безопасность:</strong> ЧПУ, метаданные и контроль доступа ACL.</li></ul>',
                'seo' => 'rukovodstvo_po_funktsiyam_modulya_publikatsiy',
                'title' => 'Руководство по функциям модуля публикаций - {{build_name}}',
                'keywords' => 'mod_publication, публикации, amed framework, cms',
                'description' => 'Подробности о модуле mod_publication в Amed Framework',
                'image' => 'mod_publication_cover.jpg',
                'user_id' => '8'
            ),
            array(
                'name' => 'Şoreşa Zekaya Destçêkirî û Modelên Ziman ên Mezin (LLM)',
                'lang' => 'ku',
                'category_id' => '30',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Gotara nûjen li ser taybetmendî, mîmarî û pêşveçûna bilez a modelên ziman ên mezin (LLM) di qada zekaya destçêkirî de.',
                'content' => '<p><strong>Şoreşa Zekaya Destçêkirî û Modelên Ziman ên Mezin (LLM)</strong> di cîhana teknolojiyê de serdemeke nû ya dîjîtal vekiriye. Pergalên wekî Gemini 3.6 û modelên pêşketî yên fêrbûna kûr (Deep Learning) kapasîteya fêmkirin, analîzkirin û hilberîna zimanê mirovî gihandine asteke nedîtî.</p><h3>1. Mîmariya Transformer û Fêrbûna Kûr</h3><p>Modelên ziman ên mezin li ser binesaziya torên rehikan (neural networks) û mîmariya Transformer dixebitin. Ev mîmarî destûrê dide ku pergal têkiliya di navbera peyvan de di çarçoveya tevahiya metnê de fêm bike û bi awayekî herikbar bersivên guncav hilberîne.</p><h3>2. Pirzimanî û Şiyanên Multi-Modal</h3><p>Pergalên AI ên nûjen ne tenê metnan, lê di heman demê de wêne, deng, vîdyo û koda nermalavê jî bi hevre pêvajo dikin. Ev nêzîkatiya multi-modal rê li ber avakirina çareseriyên berfirehtir ên dîjîtal vedike.</p><h3>3. Bandora li ser Paşeroja Dîjîtal</h3><p>Ji afirandina naverokan heta analîzkirina daneyên mezin, modelên ziman ên mezin di hemû saziyan de karîgerî û leza xebatê bi awayekî berçav zêde dikin.</p>',
                'seo' => 'soresha_zekaya_destcekiri_u_modelen_ziman_en_mezin',
                'title' => 'Şoreşa Zekaya Destçêkirî û Modelên Ziman ên Mezin (LLM)  - {{build_name}}',
                'keywords' => 'zekaya destcekiri, ai, llm, gemini, deep learning, transformer',
                'description' => 'Gotara nûjen û berfireh li ser pêşveçûna modelên ziman ên mezin û zekaya destçêkirî.',
                'image' => 'ai_llm_cover.jpg',
                'user_id' => '2'
            ),
            array(
                'name' => 'The Artificial Intelligence Revolution and Large Language Models (LLM)',
                'lang' => 'en',
                'category_id' => '31',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Modern technical article exploring the rapid evolution, transformer architecture, and capabilities of Large Language Models in AI.',
                'content' => '<p><strong>The Artificial Intelligence Revolution and Large Language Models (LLM)</strong> has introduced a transformative paradigm in modern computing. Next-generation systems powered by deep neural networks process human language and multimodal data with unprecedented intelligence.</p><h3>1. Transformer Architecture</h3><p>At the core of LLMs lies the Transformer neural network architecture, enabling contextual self-attention and dynamic natural language understanding.</p><h3>2. Multimodal Intelligence</h3><p>Modern AI reasoning spans text, images, video, code, and structured data streams seamlessly.</p>',
                'seo' => 'ai_revolution_and_large_language_models',
                'title' => 'The Artificial Intelligence Revolution and Large Language Models (LLM)  - {{build_name}}',
                'keywords' => 'artificial intelligence, ai, llm, deep learning, transformer',
                'description' => 'In-depth article on the evolution of LLMs and artificial intelligence.',
                'image' => 'ai_llm_cover.jpg',
                'user_id' => '2'
            ),
            array(
                'name' => 'Yapay Zeka Devrimi ve Büyük Dil Modelleri (LLM)',
                'lang' => 'tr',
                'category_id' => '32',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Yapay zeka alanındaki son gelişmeler, Büyük Dil Modelleri (LLM) ve Transformer mimarisini ele alan kapsamlı teknik makale.',
                'content' => '<p><strong>Yapay Zeka Devrimi ve Büyük Dil Modelleri (LLM)</strong>, dijital dünyada yeni bir çağ başlatmıştır. Gemini ve gelişmiş derin öğrenme modelleri insan dilini anlama ve üretme kapasitesini zirveye taşımıştır.</p>',
                'seo' => 'yapay_zeka_devrimi_ve_buyuk_dil_modelleri',
                'title' => 'Yapay Zeka Devrimi ve Büyük Dil Modelleri (LLM)  - {{build_name}}',
                'keywords' => 'yapay zeka, ai, llm, derin ogrenme, transformer',
                'description' => 'Büyük dil modelleri ve yapay zeka devrimi üzerine inceleme.',
                'image' => 'ai_llm_cover.jpg',
                'user_id' => '2'
            ),
            array(
                'name' => 'Sîstemên Otonom ên Agentic AI û Dema Nû ya Pêşxistina Nermalavê',
                'lang' => 'ku',
                'category_id' => '39',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Lêkolîna nûjen li ser ajansên zeralî (Agentic AI Systems), otomatîzekirina kodkirinê û hevpar-programkirina mirov û AI.',
                'content' => '<p><strong>Sîstemên Otonom ên Agentic AI û Dema Nû ya Pêşxistina Nermalavê</strong> guherîneke kûr di awayê afirandina berhemên dîjîtal de çêkirine. Ajansên AI yên otonom dikarin amûran bi kar bînin, pêngavên tevlihev plan bikin, û çareseriyên nermalavê bixwe pêş bixin.</p><h3>1. Orşestrasyona Ajansan û Xebata Otonom</h3><p>Berevajî mîmarîyên kevn, ajansên zeralî (Agentic Agents) xwedî kapasîteya sedemderxistinê (reasoning), şopandina koda xelet, û rastkirina otomatîk a çewtiyan in.</p><h3>2. Hevpar-Programkirina Mirov û AI (Pair Programming)</h3><p>Ajansên zeralî yên wekî Google Antigravity bi pêşxisteneran re rasterast hevkariyê dikin da ku projeyên mîmarî yên mezin di demek kurt de bi serkeftî bi dawî bînin.</p>',
                'seo' => 'sistemen_otonom_en_agentic_ai_u_dema_nu_ya_peshxistina_nermalave',
                'title' => 'Sîstemên Otonom ên Agentic AI û Dema Nû ya Pêşxistina Nermalavê  - {{build_name}}',
                'keywords' => 'agentic ai, ai agents, antigravity, nermalav, pair programming',
                'description' => 'Gotara li ser sîstemên otonom ên Agentic AI û rola wan di pêşxistina nermalavê de.',
                'image' => 'agentic_ai_cover.jpg',
                'user_id' => '4'
            ),
            array(
                'name' => 'Autonomous Agentic AI Systems and the New Era of Software Engineering',
                'lang' => 'en',
                'category_id' => '40',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Technical analysis of autonomous agentic AI architectures, multi-agent workflows, tool execution, and AI-pair programming.',
                'content' => '<p><strong>Autonomous Agentic AI Systems</strong> represent the next evolutionary leap in software engineering. Equipped with reasoning loops, tool invocation, and multi-agent coordination, agentic AI automates complex development workflows.</p>',
                'seo' => 'autonomous_agentic_ai_systems_and_software_engineering',
                'title' => 'Autonomous Agentic AI Systems and the New Era of Software Engineering  - {{build_name}}',
                'keywords' => 'agentic ai, autonomous agents, antigravity, software engineering',
                'description' => 'In-depth review of agentic AI systems in modern software engineering.',
                'image' => 'agentic_ai_cover.jpg',
                'user_id' => '4'
            ),
            array(
                'name' => 'Otonom Agentic Yapıdaki AI Sistemleri ve Yazılım Geliştirmenin Yeni Çağı',
                'lang' => 'tr',
                'category_id' => '41',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Agentic AI mimarisi, otonom ajanslar, araç kullanımı ve yazılım geliştirmede yapay zeka ortaklığı üzerine teknik inceleme.',
                'content' => '<p><strong>Otonom Agentic Yapıdaki AI Sistemleri</strong> yazılım mühendisliğinde yeni bir dönüşüm başlatmıştır. Otonom karar alma, araç çalıştırma ve kod refaktörü kabiliyetleri karmaşık sistemlerin hızla kurulmasını sağlar.</p>',
                'seo' => 'otonom_agentic_yapadaki_ai_sistemleri',
                'title' => 'Otonom Agentic Yapıdaki AI Sistemleri ve Yazılım Geliştirmenin Yeni Çağı  - {{build_name}}',
                'keywords' => 'agentic ai, otonom ajanslar, yazilim gelistirme, antigravity',
                'description' => 'Otonom agentic yapay zeka sistemleri üzerine rehber.',
                'image' => 'agentic_ai_cover.jpg',
                'user_id' => '4'
            ),
            array(
                'name' => 'Zekaya Destçêkirî di Bijîşkî û Lêkolînên Zanistî de',
                'lang' => 'ku',
                'category_id' => '48',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Rola zekaya destçêkirî di tespîtkirina nexweşiyan, pêşbînkirina avahiya proteînan (AlphaFold), û bilezkirina vedîtina dermanan de.',
                'content' => '<p><strong>Zekaya Destçêkirî di Bijîşkî û Lêkolînên Zanistî de</strong> şoreşeke mezin di qada tenduristî û biyomedyayê de afirandiye. Bikaranîna algorîtmayan di pêşbînkirina avahiya 3D ya proteînan (AlphaFold) de astengiyên dehan salan di çend deqeyan de çareser kirin.</p><h3>1. Vedîtina Dermanan û Analîza Proteînan</h3><p>Modelên AI yên zanistî dihêlin ku lêkolîner pêkhateyên kîmyewî û tevgera proteînan bi lezeke pir bilind simulasyon bikin, ku ev yek dema ceribandina dermanan ji salan kêm dike bo mehan.</p><h3>2. Tespîtkirina Zû û Bijîşkiya Kesane</h3><p>Bi analîzkirina wêneyên rensgen û MR bi navgîniya AI, nexweşiyên wekî penceşêrê di astên herî zû de tên tespîtkirin û tedawiyên kesane tên amadekirin.</p>',
                'seo' => 'zekaya_destcekiri_di_bijiski_u_lekolinen_zanisti_de',
                'title' => 'Zekaya Destçêkirî di Bijîşkî û Lêkolînên Zanistî de  - {{build_name}}',
                'keywords' => 'zekaya destcekiri, bijiski, alphafold, derman, tenduristi',
                'description' => 'Gotara li ser rola zekaya destçêkirî di bijîşkî û lêkolînên zanistî de.',
                'image' => 'ai_science_cover.jpg',
                'user_id' => '5'
            ),
            array(
                'name' => 'Artificial Intelligence in Medicine and Scientific Breakthroughs',
                'lang' => 'en',
                'category_id' => '49',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Exploring the impact of AI on protein folding predictions (AlphaFold), accelerated drug discovery, and precision healthcare.',
                'content' => '<p><strong>Artificial Intelligence in Medicine and Scientific Breakthroughs</strong> is transforming healthcare. AlphaFold protein structure predictions and ML-driven molecular screening accelerate life science research exponentially.</p>',
                'seo' => 'artificial_intelligence_in_medicine_and_science',
                'title' => 'Artificial Intelligence in Medicine and Scientific Breakthroughs  - {{build_name}}',
                'keywords' => 'ai, medicine, alphafold, drug discovery, healthcare',
                'description' => 'Overview of AI applications in scientific research and clinical medicine.',
                'image' => 'ai_science_cover.jpg',
                'user_id' => '5'
            ),
            array(
                'name' => 'Tıp ve Bilimsel Araştırmalarda Yapay Zeka Devrimi',
                'lang' => 'tr',
                'category_id' => '50',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'AlphaFold ile protein katlanması tahmini, hızlı ilaç keşfi ve kişiselleştirilmiş tıp alanında yapay zekanın rolü.',
                'content' => '<p><strong>Tıp ve Bilimsel Araştırmalarda Yapay Zeka Devrimi</strong>, biyomedikal ve sağlık alanında çığır açmıştır. Yapay zeka destekli protein modelleme ve veri analizi ilaç geliştirme süreçlerini hızlandırmaktadır.</p>',
                'seo' => 'tip_ve_bilimsel_arastirmalarda_yapay_zeka',
                'title' => 'Tıp ve Bilimsel Araştırmalarda Yapay Zeka Devrimi  - {{build_name}}',
                'keywords' => 'yapay zeka, tip, alphafold, ilac kesfi, saglik',
                'description' => 'Tıp ve bilimsel araştırmalarda yapay zeka uygulamaları rehberi.',
                'image' => 'ai_science_cover.jpg',
                'user_id' => '5'
            ),
            array(
                'name' => 'Etîk, Ewlehî û Rêveberiya Zekaya Destçêkirî di Paşerojê de',
                'lang' => 'ku',
                'category_id' => '48',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Nîqaş û gotara berfireh li ser ewlehiya daneyan, etîka algorîtmayan, birêvebirina ewledar a AI û yasayên cîhanî.',
                'content' => '<p><strong>Etîk, Ewlehî û Rêveberiya Zekaya Destçêkirî di Paşerojê de</strong> yek ji mijarên herî girîng ên cîhana nûjen e. Bi pêşveçûna bilez a AI re, parastina ewlehiya daneyan, rêgirtin di ber alîgiriya algorîtmayan (algorithmic bias) de û çêkirina binesaziyên ewledar bûne mecbûrî.</p><h3>1. Ewlehiya Daneyan û Nepênî (Privacy)</h3><p>Pergalên AI divê li gorî standardên herî bilind ên ewlehiyê bên sêwirandin da ku daneyên kesane yên bikarhêneran bi temamî werin parastin.</p><h3>2. Yasayên Cîhanî û Rêziknameyên Etîkî</h3><p>Yekîtiya Ewropayê û saziyên navneteweyî rêziknameyên wekî AI Act xistine dewrê da ku sînorên karanîna zekaya destçêkirî di qadên hesas de diyar bikin.</p>',
                'seo' => 'etik_ewlehi_u_reveberiya_zekaya_destcekiri_di_paseroje_de',
                'title' => 'Etîk, Ewlehî û Rêveberiya Zekaya Destçêkirî di Paşerojê de  - {{build_name}}',
                'keywords' => 'zekaya destcekiri, etik, ewlehi, ai governance, ai act',
                'description' => 'Gotara berfireh li ser etîk, ewlehî û yasa û rêveberiya zekaya destçêkirî.',
                'image' => 'ai_ethics_cover.jpg',
                'user_id' => '7'
            ),
            array(
                'name' => 'Ethics, Security, and Governance of Artificial Intelligence in the Future',
                'lang' => 'en',
                'category_id' => '49',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Comprehensive discussion on data privacy, AI alignment, algorithmic bias mitigation, and global governance frameworks.',
                'content' => '<p><strong>Ethics, Security, and Governance of Artificial Intelligence</strong> is paramount as AI systems integrate into critical infrastructure. Global guardrails, privacy-preserving AI, and regulatory frameworks ensure safe technological deployment.</p>',
                'seo' => 'ethics_security_and_governance_of_ai',
                'title' => 'Ethics, Security, and Governance of Artificial Intelligence in the Future  - {{build_name}}',
                'keywords' => 'ai, ethics, security, governance, ai act',
                'description' => 'Analysis of ethical frameworks and security regulations for artificial intelligence.',
                'image' => 'ai_ethics_cover.jpg',
                'user_id' => '7'
            ),
            array(
                'name' => 'Geleceğin Yapay Zeka Etiği, Güvenliği ve Yönetişimi',
                'lang' => 'tr',
                'category_id' => '50',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Veri gizliliği, algoritmik tarafsızlık, yapay zeka güvenliği ve küresel düzenlemeler üzerine kapsamlı değerlendirme.',
                'content' => '<p><strong>Geleceğin Yapay Zeka Etiği, Güvenliği ve Yönetişimi</strong>, teknolojinin toplum yararına güvenle kullanılması için en kritik alandır. Küresel düzenlemeler ve veri koruma standartları güvenli yapay zeka sistemlerini desteklemektedir.</p>',
                'seo' => 'gelecegin_yapay_zeka_etigi_ve_guvenligi',
                'title' => 'Geleceğin Yapay Zeka Etiği, Güvenliği ve Yönetişimi  - {{build_name}}',
                'keywords' => 'yapay zeka, etik, guvenlik, yonetisim, ai act',
                'description' => 'Yapay zeka etiği, veri güvenliği ve yönetişimi hakkında rehber.',
                'image' => 'ai_ethics_cover.jpg',
                'user_id' => '7'
            ),
            array(
                'name' => 'Antigravity 2.0: Navenda Nû ya Rêveberiya Ajansên Otonom ên Zekaya Destçêkirî',
                'lang' => 'ku',
                'category_id' => '39',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Gotara danasînê ya berfireh li ser platforma nûjen a Google Antigravity 2.0, orşestrasyona ajansên otonom, subagentên dînamîk û ekosistema AI.',
                'content' => '<p><img src="/static/mod_publication/antigravity2_cover.jpg" alt="Antigravity 2.0 Platform" class="img-fluid rounded mb-3"></p><p><strong>Antigravity 2.0</strong> platformeke otonom û nûjen a Google-ê ye ku ji bo birêvebirin, kontrolkirin û orşestrasyona ajansên zekaya destçêkirî (Agentic AI Command Center) hatî sêwirandin.</p><h3>1. Navenda Rêveberiya Otonom (Command Center)</h3><p>Berevajî hawîrdorên kevn ên pêşxistina nermalavê (IDE), Antigravity 2.0 wekî nermalaveke serbixwe ya sermaseyê (desktop app) dixebite. Ev platform dihêle ku pêşxistener bi dehhan ajansên otonom re di heman demê de bixebitin û karên tevlihev birêve bibin.</p><h3>2. Biçekirina Subagentan a Dînamîk (Dynamic Subagents)</h3><p>Pergal xwedî kapasîteyeke bilind e ku di dema xebatê de ajansên alîkar (subagent) bixwe bera nava kar bide. Ev yek rê li ber dabeşkirina barê xebatê û bilezkirina projeyan ve dike.</p><h3>3. Karên di Paşxaneyê de û Otomatîzekirin</h3><p>Bi navgîniya daxwazên cron û demjimêrên otomatîk (Scheduled Tasks), Antigravity 2.0 karên analîz, ceribandin û kontrolkirinê di paşxaneyê de bê navber berdewam dike.</p><h3>4. Ekosistema Antigravity</h3><p>Ekosistema Antigravity ji çar beşên sereke pêk tê: Antigravity 2.0 (App), Antigravity IDE (Ji bo edîtorê koda çalak), Antigravity CLI (Amûra agy di terminalê de) û Antigravity SDK (Ji bo entegrasyona bi zimanê Python).</p>',
                'seo' => 'antigravity_20_navenda_nu_ya_reveberiya_ajansen_otonom',
                'title' => 'Antigravity 2.0: Navenda Nû ya Rêveberiya Ajansên Otonom ên Zekaya Destçêkirî  - {{build_name}}',
                'keywords' => 'antigravity 2.0, google antigravity, agentic ai, subagent, agy, ai command center',
                'description' => 'Gotara danasînê ya berfireh li ser platforma Antigravity 2.0 û orşestrasyona ajansên AI.',
                'image' => 'antigravity2_cover.jpg',
                'user_id' => '6'
            ),
            array(
                'name' => 'Antigravity 2.0: The Next-Gen Autonomous AI Agent Orchestration Platform',
                'lang' => 'en',
                'category_id' => '40',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Comprehensive introductory guide on Google Antigravity 2.0, autonomous agent orchestration, dynamic subagents, and the AI ecosystem.',
                'content' => '<p><img src="/static/mod_publication/antigravity2_cover.jpg" alt="Antigravity 2.0 Platform" class="img-fluid rounded mb-3"></p><p><strong>Antigravity 2.0</strong> is Google\'s next-generation desktop application engineered as a central command center for orchestrating autonomous AI agents.</p><h3>1. Standalone Agent Command Center</h3><p>Decoupled from traditional IDE environments, Antigravity 2.0 acts as a command center enabling multi-agent orchestration across complex workflows.</p><h3>2. Dynamic Subagent Spawning</h3><p>Agents autonomously define and launch subagents to process tasks concurrently without clogging context windows.</p><h3>3. Asynchronous Background Automation</h3><p>Integrated background cron scheduling and timer tasks execute automated routines continuously.</p><h3>4. The Antigravity Ecosystem</h3><p>The suite comprises Antigravity 2.0 Desktop App, Antigravity IDE, Antigravity CLI (agy), and the Antigravity Python SDK.</p>',
                'seo' => 'antigravity_20_next_gen_autonomous_ai_agent_orchestration_platform',
                'title' => 'Antigravity 2.0: The Next-Gen Autonomous AI Agent Orchestration Platform  - {{build_name}}',
                'keywords' => 'antigravity 2.0, google antigravity, agentic ai, subagent, agy, ai command center',
                'description' => 'Comprehensive guide on Google Antigravity 2.0 desktop platform and agent orchestration.',
                'image' => 'antigravity2_cover.jpg',
                'user_id' => '6'
            ),
            array(
                'name' => 'Antigravity 2.0: Otonom Yapay Zeka Ajanları Komuta ve Yönetim Platformu',
                'lang' => 'tr',
                'category_id' => '41',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'Google Antigravity 2.0 otonom yapay zeka ajan yönetimi, dinamik alt ajanlar ve AI ekosistemi üzerine kapsamlı tanıtım rehberi.',
                'content' => '<p><img src="/static/mod_publication/antigravity2_cover.jpg" alt="Antigravity 2.0 Platform" class="img-fluid rounded mb-3"></p><p><strong>Antigravity 2.0</strong>, Google tarafından geliştirilen ve otonom yapay zeka ajanlarını (Agentic AI) yönetmek için tasarlanmış bağımsız bir masaüstü komuta merkezidir.</p><h3>1. Bağımsız Ajan Komuta Merkezi</h3><p>Geleneksel IDE yapılarından bağımsız olarak çalışan Antigravity 2.0, çoklu ajan orkestrasyonunu merkezi bir arayüzden yönetir.</p><h3>2. Dinamik Alt Ajan Çalıştırma</h3><p>Ajanlar karmaşık işleri eşzamanlı yürütmek için kendi alt ajanlarını (subagents) otonom olarak tanımlayıp çalıştırabilir.</p><h3>3. Arka Plan Otomasyonu</h3><p>Zamanlanmış görevler ve arka plan süreçleri sayesinde sürekli otomasyon imkanı sunar.</p>',
                'seo' => 'antigravity_20_otonom_yapay_zeka_ajanlari_komuta_platformu',
                'title' => 'Antigravity 2.0: Otonom Yapay Zeka Ajanları Komuta ve Yönetim Platformu  - {{build_name}}',
                'keywords' => 'antigravity 2.0, google antigravity, agentic ai, alt ajan, agy, komuta merkezi',
                'description' => 'Google Antigravity 2.0 masaüstü platformu ve ajan yönetimi rehberi.',
                'image' => 'antigravity2_cover.jpg',
                'user_id' => '6'
            ),
            array(
                'name' => 'شۆڕشی ژیریی دەستکرد و مۆدێلە گەورەکانی زمان (LLM)',
                'lang' => 'ckb',
                'category_id' => '36',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'وتارێکی هاوچەرخ سەبارەت بە تایبەتمەندی، تەلارسازی و گەشەسەندنی خێرای مۆدێلە گەورەکانی زمان (LLM) لە بواری ژیریی دەستکرددا.',
                'content' => '<p><strong>شۆڕشی ژیریی دەستکرد و مۆدێلە گەورەکانی زمان (LLM)</strong> لە جیهانی تەکنەلۆجیادا سەرەدەمێکی نوێی دیجیتاڵیی کردۆتەوە. سیستەمەکانی وەکو Gemini 3.6 و مۆدێلە پێشکەوتووەکانی فێربوونی قووڵ (Deep Learning) توانای تێگەیشتن، شیکردنەوە و بەرهەمهێنانی زمانی مرۆییان گەیاندۆتە ئاستێکی بێپێشینە.</p><h3>١. تەلارسازیی Transformer و فێربوونی قووڵ</h3><p>مۆدێلە گەورەکانی زمان لەسەر ژێرخانی تۆڕە دەمارەیییەکان (neural networks) و تەلارسازیی Transformer کار دەکەن. ئەم تەلارسازییە ڕێگە دەدات سیستەم لە پەیوەندی نێوان وشەکان لە چوارچێوەی تەواوی دەقەکەدا بگەات و بە شێوەیەکی ڕەوان وەڵامی گونجاو بەرهەم بهێنێت.</p><h3>٢. فرەزمانی و تواناکانی Multi-Modal</h3><p>سیستەمەکانی نوێی AI تەنیا دەق نا، بەڵکو لە هەمان کاتدا وێنە، دەنگ، ڤیدیۆ و کۆدی نەرمەکاڵاش پێکەوە پڕۆسێس دەکەن. ئەم ڕوانگە فرەڕەهەندە (multi-modal) ڕێگە بۆ دروستکردنی چارەسەری بەربڵاوتری دیجیتاڵی خۆش دەکات.</p><h3>٣. کاریگەری لەسەر داهاتووی دیجیتاڵی</h3><p>لە دروستکردنی ناوەڕۆکەوە تا شیکردنەوەی داتای گەورە، مۆدێلە گەورەکانی زمان لە هەموو دامەزراوەکاندا کارایی و خێرایی کارکردن بە شێوەیەکی بەربەرچاو زیاد دەکەن.</p>',
                'seo' => 'shorshi_zhirii_destkird_u_modele_gewrekani_ziman',
                'title' => 'شۆڕشی ژیریی دەستکرد و مۆدێلە گەورەکانی زمان (LLM)  - {{build_name}}',
                'keywords' => 'ژیریی دەستکرد, ai, llm, gemini, deep learning, transformer',
                'description' => 'وتاری بەربڵاو سەبارەت بە گەشەسەندنی مۆدێلە گەورەکانی زمان و ژیریی دەستکرد.',
                'image' => 'ai_llm_cover.jpg',
                'user_id' => '2'
            ),
            array(
                'name' => 'سیستەمە سەربەخۆکانی Agentic AI و سەردەمی نوێی گەشەپێدانی نەرمەکاڵا',
                'lang' => 'ckb',
                'category_id' => '45',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'لێکۆڵینەوەیەکی نوێ سەبارەت بە بریکارە ژیرەکان (Agentic AI Systems)، ئۆتۆماتیککردنی کۆدنووسین و هاوبەش-پڕۆگرامسازی مرۆڤ و ژیریی دەستکرد.',
                'content' => '<p><strong>سیستەمە سەربەخۆکانی Agentic AI و سەردەمی نوێی گەشەپێدانی نەرمەکاڵا</strong> گۆڕانکارییەکی قووڵیان لە شێوازی دروستکردنی بەرهەمە دیجیتاڵییەکاندا دروست کردووە. بریکارەکانی ژیریی دەستکردی سەربەخۆ دەتوانن ئامرازەکان بەکار بهێنن، هەنگاوی ئاڵۆز پلانڕێژی بکەن، و چارەسەری نەرمەکاڵا خۆیان گەشە پێ بدەن.</p><h3>١. ئۆڕکێستراسیۆنی بریکارەکان و کاری سەربەخۆ</h3><p>بە پێچەوانەی تەلارسازییە کۆنەکان، بریکارە ژیرەکان (Agentic Agents) خاوەنی توانای بەڵگەهێنانەوە (reasoning)، بەدواداچوونی کۆدی هەڵە، و چاککردنەوەی ئۆتۆماتیکی هەڵەکانن.</p><h3>٢. هاوبەش-پڕۆگرامسازی مرۆڤ و ژیریی دەستکرد (Pair Programming)</h3><p>بریکارە ژیرەکانی وەکو Google Antigravity ڕاستەوخۆ هاوکاری لەگەڵ گەشەپێدەراندا دەکەن تا پڕۆژە تەلارسازییە گەورەکان لە ماوەیەکی کورتدا بە سەرکەوتوویی کۆتایی پێ بهێنن.</p>',
                'seo' => 'sisteme_serbexokani_agentic_ai_u_serdemi_nwi_geshepedani_nermekala',
                'title' => 'سیستەمە سەربەخۆکانی Agentic AI و سەردەمی نوێی گەشەپێدانی نەرمەکاڵا  - {{build_name}}',
                'keywords' => 'agentic ai, ai agents, antigravity, نەرمەکاڵا, pair programming',
                'description' => 'وتار سەبارەت بە سیستەمە سەربەخۆکانی Agentic AI و ڕۆڵیان لە گەشەپێدانی نەرمەکاڵادا.',
                'image' => 'agentic_ai_cover.jpg',
                'user_id' => '4'
            ),
            array(
                'name' => 'ژیریی دەستکرد لە پزیشکی و لێکۆڵینەوە زانستییەکاندا',
                'lang' => 'ckb',
                'category_id' => '54',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'ڕۆڵی ژیریی دەستکرد لە دەستنیشانکردنی نەخۆشییەکان، پێشبینیکردنی پێکهاتەی پڕۆتینەکان (AlphaFold)، و خێراکردنی دۆزینەوەی دەرماندا.',
                'content' => '<p><strong>ژیریی دەستکرد لە پزیشکی و لێکۆڵینەوە زانستییەکاندا</strong> شۆڕشێکی گەورەی لە بواری تەندروستی و بایۆمیدیا دروست کردووە. بەکارهێنانی ئەلگۆریتمەکان لە پێشبینیکردنی پێکهاتەی ٣Dی پڕۆتینەکان (AlphaFold) ئاستەنگەکانی دەیان ساڵی لە چەند خولەکێکدا چارەسەر کرد.</p><h3>١. دۆزینەوەی دەرمان و شیکردنەوەی پڕۆتینەکان</h3><p>مۆدێلە زانستییەکانی AI ڕێگە بە توێژەران دەدەن پێکهاتە کیمیاییەکان و ڕەفتاری پڕۆتینەکان بە خێراییەکی زۆر بەرز سیمولە بکەن، کە ئەمەش کاتی تاقیکردنەوەی دەرمانەکان لە ساڵانەوە کەم دەکاتەوە بۆ مانگان.</p><h3>٢. دەستنیشانکردنی پێشوەختە و پزیشکیی کەسی</h3><p>بۆ شیکردنەوەی وێنەکانی تیشک و MRI لە ڕێگەی ژیریی دەستکردەوە، نەخۆشییەکانی وەکو شێرپەنجە لە قۆناغە زۆر سەرەتاییەکاندا دەستنیشان دەکرێن و چارەسەری تایبەت ئامادە دەکرێت.</p>',
                'seo' => 'zhirii_destkird_le_pizishki_u_lekolinewe_zanistiyekanda',
                'title' => 'ژیریی دەستکرد لە پزیشکی و لێکۆڵینەوە زانستییەکاندا  - {{build_name}}',
                'keywords' => 'ژیریی دەستکرد, پزیشکی, alphafold, دەرمان, تەندروستی',
                'description' => 'وتار سەبارەت بە ڕۆڵی ژیریی دەستکرد لە پزیشکی و لێکۆڵینەوە زانستییەکاندا.',
                'image' => 'ai_science_cover.jpg',
                'user_id' => '5'
            ),
            array(
                'name' => 'ئەخلاق، ئاسایش و بەڕێوەبردنی ژیریی دەستکرد لە داهاتوودا',
                'lang' => 'ckb',
                'category_id' => '54',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'گۆشەنیگا و وتاری بەربڵاو سەبارەت بە ئاسایشی داتا، ئەخلاقی ئەلگۆریتمەکان، بەڕێوەبردنی ئارامی AI و یاسا جیهانییەکان.',
                'content' => '<p><strong>ئەخلاق، ئاسایش و بەڕێوەبردنی ژیریی دەستکرد لە داهاتوودا</strong> یەکێک لە بابەتە هەرە گرنگەکانی جیهانی هاوچەرخە. بە گەشەسەندنی خێرای AI، پاراستنی ئاسایشی داتا، ڕێگریکردن لە لایەنگریی ئەلگۆریتمەکان (algorithmic bias) و دروستکردنی ژێرخانی ئارام بوونەتە پێویستییەکی حەتمی.</p><h3>١. ئاسایشی داتا و تایبەتمەندی (Privacy)</h3><p>سیستەمەکانی AI دەبێت بەپێی بەرزترین استانداردەکانی ئاسایش دابڕێژرێن تا داتای کەسیی بەکارهێنەران بە تەواوی بپارێزرێت.</p><h3>٢. یاسا جیهانییەکان و ڕێنماییە ئەخلاقییەکان</h3><p>یەکێتیی ئەورووپا و دامەزراوە نێودەوڵەتییەکان ڕێنماییەکانی وەکو AI Actیان خستۆتە گەڕ تا سنوورەکانی بەکارهێنانی ژیریی دەستکرد لە بوارە هەستیارەکاندا دیاری بکەن.</p>',
                'seo' => 'exlaq_asasish_u_bereweberdni_zhirii_destkird_le_dahatuda',
                'title' => 'ئەخلاق، ئاسایش و بەڕێوەبردنی ژیریی دەستکرد لە داهاتوودا  - {{build_name}}',
                'keywords' => 'ژیریی دەستکرد, ئەخلاق, ئاسایش, ai governance, ai act',
                'description' => 'وتاری بەربڵاو سەبارەت بە ئەخلاق، ئاسایش و یاسا و بەڕێوەبردنی ژیریی دەستکرد.',
                'image' => 'ai_ethics_cover.jpg',
                'user_id' => '7'
            ),
            array(
                'name' => 'Antigravity 2.0: مەڵبەندی نوێی بەڕێوەبردنی بریکارە سەربەخۆکانی ژیریی دەستکرد',
                'lang' => 'ckb',
                'category_id' => '45',
                'status' => 'y',
                'deck' => 'y',
                'summary' => 'وتاری ناساندنی بەربڵاو سەبارەت بە پلاتفۆرمی نوێی Google Antigravity 2.0، ئۆڕکێستراسیۆنی بریکارە سەربەخۆکان، ژێربریکارە داینامیکییەکان و ئیکۆسیستەمی AI.',
                'content' => '<p><img src="/static/mod_publication/antigravity2_cover.jpg" alt="Antigravity 2.0 Platform" class="img-fluid rounded mb-3"></p><p><strong>Antigravity 2.0</strong> پلاتفۆرمێکی سەربەخۆ و نوێی Google-ە کە بۆ بەڕێوەبردن، کۆنترۆڵکردن و ئۆڕکێستراسیۆنی بریکارەکانی ژیریی دەستکرد (Agentic AI Command Center) داڕێژراوە.</p><h3>١. مەڵبەندی بەڕێوەبردنی سەربەخۆ (Command Center)</h3><p>بە پێچەوانەی ژینگەکانی کۆنی گەشەپێدانی نەرمەکاڵا (IDE)، Antigravity 2.0 وەکو نەرمەکاڵایەکی سەربەخۆی سەرشاشە (desktop app) کار دەکات. ئەم پلاتفۆرمە ڕێگە دەدات گەشەپێدەران لەگەڵ دەیان بریکاری سەربەخۆدا لە هەمان کاتدا کار بکەن و کارە ئاڵۆزەکان بەڕێوە ببەن.</p><h3>٢. بەچەکردنی داینامیکیی ژێربریکارەکان (Dynamic Subagents)</h3><p>سیستەم خاوەنی توانایەکی بەرزە کە لە کاتی کارکردندا بریکارەکانی یاریدەدەر (subagent) بە شێوەیەکی داینامیکی بخاتە کار. ئەمەش ڕێگە بۆ دابەشکردنی بارگرانی کار و خێراکردنی پڕۆژەکان خۆش دەکات.</p><h3>٣. کارەکانی باکگراوند و ئۆتۆماتیککردن</h3><p>لە ڕێگەی داواکارییەکانی cron و کاتژمێرە ئۆتۆماتیکییەکان (Scheduled Tasks)، Antigravity 2.0 کارەکانی شیکردنەوە، تاقیکردنەوە و کۆنترۆڵکردن لە باکگراونددا بەبێ وەستان بەردەوام دەکات.</p><h3>٤. ئیکۆسیستەمی Antigravity</h3><p>ئیکۆسیستەمی Antigravity لە چوار بەشی سەرەکی پێکهاتووە: Antigravity 2.0 (App)، Antigravity IDE (بۆ دەستکاریکەری کۆدی چالاک)، Antigravity CLI (ئامرازی agy لە ترمیناڵدا) و Antigravity SDK (بۆ ئینتەگراسیۆن بە زمانی پایتۆن).</p>',
                'seo' => 'antigravity_20_melbendi_nwi_bereweberdni_brikarani_serbexo',
                'title' => 'Antigravity 2.0: مەڵبەندی نوێی بەڕێوەبردنی بریکارە سەربەخۆکانی ژیریی دەستکرد  - {{build_name}}',
                'keywords' => 'antigravity 2.0, google antigravity, agentic ai, subagent, agy, ai command center',
                'description' => 'وتاری ناساندنی بەربڵاو سەبارەت بە پلاتفۆرمی Antigravity 2.0 و ئۆڕکێستراسیۆنی بریکارەکانی AI.',
                'image' => 'antigravity2_cover.jpg',
                'user_id' => '6'
            )
        );

        $data = installer_replace_build_name($data);

        $this->model = Load::model('mod_publication');
        foreach ($data as $key => $value) {
            if ($this->model->exists_name_and_lang($value['name'], $value['lang']) === FALSE) {
                $lang = $value['lang'];
                $starterName = $this->installer_resolve_category_starter_label('publications', $lang);
                if ($starterName === FALSE) {
                    $this->error("Starter category label not found for " . $lang);
                    return FALSE;
                }
                $query = "SELECT id FROM `mod_publication_category` WHERE lang='" . addslashes($lang) . "' AND name='" . addslashes($starterName) . "' ORDER BY id ASC LIMIT 1";
                $this->db->fetch_array($query);
                if ($this->db->getSize() === 0) {
                    $this->error("Category ID not found in mod_publication_category for language " . $lang . " and name " . $starterName);
                    return FALSE;
                }
                $rows = $this->db->getResult()->toArray();
                $categoryId = (int) $rows[0]['id'];
                $value['category_id'] = $categoryId;

                $this->success("Added: " . $value['name']);
                $keys = array_keys($value);
                foreach ($keys as $k) {
                    $this->model->$k = $value[$k];
                }
                if ($this->model->save() === FALSE) {
                    $this->error("Failed to add: " . $value['name']);
                    return FALSE;
                }
                continue;
            } else {
                $this->warning("Already exists: " . $value['name']);
            }
        }
        return TRUE;
    }
}
