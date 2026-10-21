<?php

class mod_settings extends installerbase implements installerbaseinterface
{
    public $tables;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_settings';
        $this->tables = array(
            'mod_settings',
            'settings',
            'mod_settings_social',
            'mod_settings_cookies',
            'mod_settings_working_hour',
            'mod_settings_closure',
            'mod_settings_affiliation'
        );
        $this->roles = array(
            'list', 'edit', 'working_hours'
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
        // Add Default Settings
        if ($this->add_default_settings() === FALSE) {
            return FALSE;
        }
        // Add Example Social Media Platforms
        if ($this->add_default_social() === FALSE) {
            return FALSE;
        }
        // Add Default Sample Cookie Inventory
        if ($this->add_default_cookies() === FALSE) {
            return FALSE;
        }
        // Add Default Working Hours
        if ($this->add_default_working_hours() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }
    
    private function add_default_settings()
    {
        $projectSetup = installer_get_project_setup_state();
        $projectName = trim((string) $projectSetup['project_name']);
        $projectDescription = trim((string) $projectSetup['project_description']);
        $projectOwnerName = trim((string) $projectSetup['project_owner_name']);
        $projectEstablishedYear = trim((string) $projectSetup['project_established_year']);
        $projectPhone = trim((string) $projectSetup['project_phone']);
        $projectWhatsapp = trim((string) $projectSetup['project_whatsapp']);
        $projectEmail = trim((string) $projectSetup['project_email']);
        $projectOfficeAddress = trim((string) $projectSetup['project_office_address']);
        $projectWorkAddress = trim((string) $projectSetup['project_work_address']);

        // Fetch languages dynamically from the database
        $lang_query = $this->db->fetch_array("SELECT name, seo FROM `lang` ");
        if ($this->db->getSize() === 0) {
            $this->warning("No languages found in the system. 'lang' table is empty. Settings cannot be added.");
            return true; // Stop if no languages are defined
        }
        $langs = $this->db->getResult()->toArray();

        $this->model = Load::model('mod_settings');

        foreach ($langs as $lang) {
            // Check if settings for this language already exist
            $this->model->where_lang($lang['seo'])->getResult(TRUE, TRUE);
            if ($this->model->getSize() === 0) {
                $this->model->lang = $lang['seo'];
                $this->model->title = $projectName !== '' ? $projectName : installer_site_build_name();
                $this->model->site_name = $projectName !== '' ? $projectName : installer_site_build_name();
                $this->model->description = $projectDescription;
                $this->model->owner_name = $projectOwnerName;
                $this->model->established_year = $projectEstablishedYear !== '' ? $projectEstablishedYear : NULL;
                $this->model->phone = $projectPhone;
                $this->model->whatsapp = $projectWhatsapp;
                $this->model->email = $projectEmail;
                $this->model->office_address = $projectOfficeAddress;
                $this->model->work_address = $projectWorkAddress;
                
                $this->model->map_embed_code = NULL; // Default to NULL
                $this->model->google_search_console = NULL; // Default to NULL
                $this->model->google_analytics = NULL; // Default to NULL
                $this->model->meta_tags = NULL; // Default to NULL
                $this->model->show_logo = 'y'; // Default to y
                $this->model->logo_path = 'logo.png';
                $this->model->site_icon = 'logo_square.png';
                $this->model->logo_symbol = 'logo_symbol.png';
                $this->model->logo_type = 'rectangle';
                $this->model->favicon_path = 'favicon.ico';
                $this->model->favicon_96 = 'favicon-96x96.png';
                $this->model->apple_touch_icon = 'apple-touch-icon.png';
                $this->model->site_webmanifest = 'site.webmanifest';
                $this->model->manifest_192 = 'web-app-manifest-192x192.png';
                $this->model->manifest_512 = 'web-app-manifest-512x512.png';
                $this->model->app_template = 'amida';
                $this->model->cookie_policy_content = $this->_get_default_cookie_policy($lang['seo']);
                $this->model->user_id = 1;

                if ($this->model->save()) {
                    $this->success("Default settings added for language: " . $lang['name']);
                } else {
                    $this->error("Error occurred while adding settings for language: " . $lang['name']);
                    return FALSE;
                }
            } else {
                $this->warning("Settings already exist for language: " . $lang['name']);
            }
        }

        try {
            $brandGenerator = Load::lib('BrandAssetGenerator');
            $brandGenerator->writeManifest([
                'title' => $projectName !== '' ? $projectName : installer_site_build_name(),
                'name' => $projectName !== '' ? $projectName : installer_site_build_name(),
            ]);
        } catch (Exception $e) {
            // Ignore manifest generation error during installer fallback
        }

        return TRUE;
    }

    private function add_default_social()
    {
        $platforms = array(
            array(
                'platform' => 'twitter',
                'url' => 'https://twitter.com/akmbulletin',
                'icon_class' => 'bi bi-twitter-x',
                'display_order' => 1
            ),
            array(
                'platform' => 'facebook',
                'url' => 'https://facebook.com/akmbulletin',
                'icon_class' => 'bi bi-facebook',
                'display_order' => 2
            ),
            array(
                'platform' => 'instagram',
                'url' => 'https://instagram.com/akmbulletin',
                'icon_class' => 'bi bi-instagram',
                'display_order' => 3
            ),
            array(
                'platform' => 'linkedin',
                'url' => 'https://linkedin.com/company/akmbulletin',
                'icon_class' => 'bi bi-linkedin',
                'display_order' => 4
            ),
            array(
                'platform' => 'youtube',
                'url' => 'https://youtube.com/@akmbulletin',
                'icon_class' => 'bi bi-youtube',
                'display_order' => 5
            ),
            array(
                'platform' => 'spotify',
                'url' => 'https://open.spotify.com/artist/akmbulletin',
                'icon_class' => 'bi bi-spotify',
                'display_order' => 6
            ),
            array(
                'platform' => 'tiktok',
                'url' => 'https://tiktok.com/@akmbulletin',
                'icon_class' => 'bi bi-tiktok',
                'display_order' => 7
            )
        );

        $this->model = Load::model('mod_settings.mod_settings_social'); // Load the social model

        foreach ($platforms as $platform) {
            $this->model->where_platform($platform['platform'])->getResult(TRUE, TRUE);
            if ($this->model->getSize() === 0) {
                foreach ($platform as $key => $value) {
                    $this->model->$key = $value;
                }
                $this->model->is_active = 'y';
                $this->model->user_id = 1; // Assuming default user ID

                if ($this->model->save()) {
                    $this->success("Platform added: " . $platform['platform']);
                } else {
                    $this->error("Error occurred while adding platform: " . $platform['platform']);
                    return FALSE;
                }
            } else {
                $this->warning("Platform already exists: " . $platform['platform']);
            }
        }
        return TRUE;
    }

    /**
     * Returns default initial sample cookie policy content per language.
     *
     * @param string $langCode
     * @return string
     */
    private function _get_default_cookie_policy($langCode)
    {
        $samples = array(
            'ku' => "Ev malper çerezan (cookies) û teknolojiyên wekhev bi kar tîne da ku ezmûna bikaranînê ya ewle û hêsan pêşkêş bike, performansa rûpelan bipîve û xizmetên guncaw pêşkêş bike. Bi rêya vê rûpelê hûn dikarin hûrguliyên çerezan bibînin û her dem tercîhên xwe li gorî daxwaza xwe birêve bibin.",
            'en' => "This website uses cookies and related technologies to provide an optimal browsing experience, measure performance, and ensure secure digital services. You can review cookie disclosures and manage your consent preferences at any time.",
            'tr' => "Bu web sitesi; güvenli ve kesintisiz bir kullanıcı deneyimi sunmak, site performansını analiz etmek ve hizmetlerimizi geliştirmek amacıyla çerezleri (cookies) kullanmaktadır. Çerez politikamızı inceleyebilir ve tercihlerinizi dilediğiniz zaman güncelleyebilirsiniz.",
            'de' => "Diese Website verwendet Cookies und ähnliche Technologien, um ein optimales Nutzererlebnis zu gewährleisten, die Leistung zu analysieren und sichere Dienste bereitzustellen. Sie können Ihre Einstellungen jederzeit anpassen.",
            'fr' => "Ce site Web utilise des cookies et des technologies similaires pour garantir une navigation optimale, analyser l'audience et sécuriser nos services. Vous pouvez modifier vos préférences à tout moment.",
            'ar' => "يستخدم هذا الموقع ملفات تعريف الارتباط (الكوكيز) لتقديم أفضل تجربة تصفح وضمان أمان الخدمات وتحليل أداء المنصة. يمكنك مراجعة وتعديل تفضيلاتك في أي وقت.",
            'ckb' => "ئەم ماڵپەڕە کووکی و تەکنەلۆژیا هاوشێوەکان بەکاردەهێنێت بۆ پێشکەشکردنی ئەزموونێکی بەکارهێنانی پارێزراو و پێوانەکردنی کارایی. دەتوانیت لە هەر کاتێکدا بێت هەڵبژاردنەکانت بگۆڕیت.",
            'sv' => "Denna webbplats använder cookies och liknande tekniker för att säkerställa en optimal användarupplevelse, analysera prestanda och tillhandahålla säkra tjänster. Du kan när som helst hantera dina preferenser.",
            'zza' => "Na keyepela semedê averberdışê tecribeyê şuxulnayoxan, analîzê performansi u xizmetanê ewleyan çerezan (cookies) şuxulnena. Şıma şenê tercihanê xo her wext bivurnê.",
            'ru' => "Этот веб-сайт использует файлы cookie и схожие технологии для обеспечения удобного и безопасного взаимодействия с пользователем, анализа посещаемости и предоставления актуальных сервисов. Вы можете ознакомиться с политикой использования файлов cookie и в любое время настроить свои предпочтения."
        );

        return isset($samples[$langCode]) ? $samples[$langCode] : $samples['en'];
    }

    /**
     * Seeds initial sample cookie inventory per language.
     *
     * @return bool
     */
    private function add_default_cookies()
    {
        $projectSetup = installer_get_project_setup_state();
        $projectName = trim((string) ($projectSetup['project_name'] ?? ''));
        if ($projectName === '') {
            $projectName = installer_site_build_name();
        }

        $lang_query = $this->db->fetch_array("SELECT name, seo FROM `lang` ");
        if ($this->db->getSize() === 0) {
            return true;
        }
        $langs = $this->db->getResult()->toArray();

        foreach ($langs as $lang) {
            $langCode = $lang['seo'];
            $sampleCookies = $this->_get_default_cookie_samples_for_lang($langCode, $projectName);

            foreach ($sampleCookies as $item) {
                $safeLang = addslashes($langCode);
                $safeName = addslashes($item['cookie_name']);
                $this->db->fetch_array("SELECT id FROM `mod_settings_cookies` WHERE `lang` = '{$safeLang}' AND `cookie_name` = '{$safeName}'");
                if ($this->db->getSize() === 0) {
                    $c = Load::model('mod_settings.mod_settings_cookies');
                    $c->lang = $langCode;
                    $c->cookie_name = $item['cookie_name'];
                    $c->category = $item['category'];
                    $c->provider = $item['provider'];
                    $c->purpose = $item['purpose'];
                    $c->duration = $item['duration'];
                    $c->display_order = $item['display_order'];
                    $c->is_active = 'y';
                    $c->user_id = 1;
                    $c->save();
                }
            }
        }

        return true;
    }

    /**
     * Generates standard sample cookie dictionary for the given language.
     *
     * @param string $langCode
     * @param string $projectName
     * @return array
     */
    private function _get_default_cookie_samples_for_lang($langCode, $projectName)
    {
        $purposes = array(
            'ku' => array(
                'session' => 'Nasnameya rûniştinê ya bikarhêner û parastina ewlehiyê tomar dike.',
                'lang_theme' => 'Zimanê bijartî û tercîhên xuyabûna sêwiranê (şev/roj) diparêze.',
                'consent' => 'Hilbijartin û pejirandina komên çerezan di bîrê de dihêle.',
                'sidebar' => 'Rewşa vebûn/girtinê ya pêkhateyên rûpelê tomar dike.',
                'analytics' => 'Amarên anonîm ên serdanvanan û leza rûpelan dipîve.',
                'marketing' => 'Bandor û têkiliyên kampanyayên danasînê dipîve.'
            ),
            'en' => array(
                'session' => 'Preserves authenticated user state and request security token across requests.',
                'lang_theme' => 'Remembers visitor language selection and theme display mode preference.',
                'consent' => 'Stores cookie category consent approvals and visitor privacy preferences.',
                'sidebar' => 'Preserves navigational state and interface element visibility preferences.',
                'analytics' => 'Measures anonymized visitor statistics, page render speeds, and traffic trends.',
                'marketing' => 'Measures outreach campaign effectiveness and audience engagement metrics.'
            ),
            'tr' => array(
                'session' => 'Kullanıcı oturum kimliğini ve istek güvenliğini oturum süresince korur.',
                'lang_theme' => 'Ziyaretçinin seçtiği arayüz dilini ve tema görünüm modunu hatırlar.',
                'consent' => 'Çerez onay tercihlerini ve gizlilik izinlerini tarayıcıda saklar.',
                'sidebar' => 'Arayüz gezinme durumunu ve panel görünüm tercihlerini korur.',
                'analytics' => 'Anonim ziyaretçi istatistiklerini, sayfa hızını ve trafik verilerini analiz eder.',
                'marketing' => 'Tanıtım ve duyuru kampanyalarının etkileşim oranlarını ölçer.'
            ),
            'de' => array(
                'session' => 'Speichert die Benutzersitzung und Authentifizierungstoken während des Besuchs.',
                'lang_theme' => 'Speichert die ausgewählte Sprache und den bevorzugten Design-Modus.',
                'consent' => 'Speichert die erteilten Cookie-Einwilligungen und Datenschutzeinstellungen.',
                'sidebar' => 'Speichert den Navigationsstatus und Benutzeroberflächen-Einstellungen.',
                'analytics' => 'Erfasst anonymisierte Besucherstatistiken und Seitenladezeiten.',
                'marketing' => 'Misst die Wirksamkeit von Informations- und Kommunikationskampagnen.'
            ),
            'fr' => array(
                'session' => 'Conserve la session utilisateur et le jeton de sécurité durant la navigation.',
                'lang_theme' => 'Mémorise la langue sélectionnée et le mode d’affichage du thème.',
                'consent' => 'Enregistre les consentements de cookies et les préférences de confidentialité.',
                'sidebar' => 'Conserve l’état des menus et des éléments de l’interface.',
                'analytics' => 'Mesure les statistiques de fréquentation anonymisées et la rapidité des pages.',
                'marketing' => 'Mesure l’efficacité des campagnes et l’engagement des visiteurs.'
            ),
            'ar' => array(
                'session' => 'يحفظ جلسة المستخدم النشطة ورموز الأمان طوال فترة التصفح.',
                'lang_theme' => 'يتذكر تفضيلات اللغة المختارة ووضع مظهر الموقع.',
                'consent' => 'يحفظ موافقات المستخدم على فئات ملفات تعريف الارتباط.',
                'sidebar' => 'يحفظ حالة التنقل وتفضيلات واجهة المستخدم.',
                'analytics' => 'يقيس إحصاءات الزوار المجهولة وسرعة استجابة الصفحات.',
                'marketing' => 'يقيس مدى فاعلية الحملات التوعوية والتفاعلات الرقمية.'
            ),
            'ckb' => array(
                'session' => 'ناسنامەی دانیشتنی بەکارهێنەر و پاراستنی ئاسایش لە کاتی سەرداندا دەپارێزێت.',
                'lang_theme' => 'زمانی هەڵبژێردراو و دۆخی دەرکەوتنی ڕووکار لە بیر دەهێڵێتەوە.',
                'consent' => 'هەڵبژاردن و ڕەزامەندییەکانی تایبەت بە کووکی پاشەکەوت دەکات.',
                'sidebar' => 'دۆخی کردنەوە و داخستنی بەشەکانی ڕووکار دەپارێزێت.',
                'analytics' => 'ئاماری نەناسراوی سەردانکەران و خێرایی پەڕەکان دەپێوێت.',
                'marketing' => 'کاریگەری کەمپینەکانی ناساندن و کارلێکەکان دەپێوێت.'
            ),
            'sv' => array(
                'session' => 'Behåller användarsessionen och säkerhetstoken under webbplatsbesöket.',
                'lang_theme' => 'Kommer ihåg valt språk och visningsläge för webbplatsen.',
                'consent' => 'Sparar godkända samtycken för cookie-kategorier och integritetsval.',
                'sidebar' => 'Behåller navigeringsläge och gränssnittsinställningar.',
                'analytics' => 'Mäter anonymiserad besökarstatistik och sidladdningstider.',
                'marketing' => 'Mäter räckvidd och interaktioner för informationskampanjer.'
            ),
            'zza' => array(
                'session' => 'Nasnameyê roniştişî û ewleyîya şuxulnayoxî wextê gêrayîşî de paweno.',
                'lang_theme' => 'Zonê tercîhkerdeyî û modê asayîşê sîstemî ano vîr.',
                'consent' => 'Tercihanê sîyasetê çerezan û destûranê xisûsîyan qeyd keno.',
                'sidebar' => 'Wextê gêrayîşî de rewşa menuyan û tercihanê rîyê peli paweno.',
                'analytics' => 'Amaranê anonîman ê zîyaretkeran û lezîya pelan pîyeno.',
                'marketing' => 'Karîgerîya kampanyayanê şınasnayîşî û têkilîyan pîyeno.'
            ),
            'ru' => array(
                'session' => 'Сохраняет идентификатор сеанса пользователя и обеспечивает безопасность запросов.',
                'lang_theme' => 'Запоминает выбранный язык интерфейса и предпочтения оформления темы.',
                'consent' => 'Сохраняет согласия пользователя на категории файлов cookie и параметры конфиденциальности.',
                'sidebar' => 'Сохраняет состояние навигации и видимость элементов интерфейса.',
                'analytics' => 'Анализирует анонимную статистику посещаемости и скорость загрузки страниц.',
                'marketing' => 'Оценивает эффективность информационных кампаний и охват аудитории.'
            )
        );

        $durations = array(
            'ku' => array('session' => 'Danşîn', '1_year' => '1 Sal', '6_months' => '6 Meh'),
            'en' => array('session' => 'Session', '1_year' => '1 Year', '6_months' => '6 Months'),
            'tr' => array('session' => 'Oturum', '1_year' => '1 Yıl', '6_months' => '6 Ay'),
            'de' => array('session' => 'Sitzung', '1_year' => '1 Jahr', '6_months' => '6 Monate'),
            'fr' => array('session' => 'Session', '1_year' => '1 An', '6_months' => '6 Mois'),
            'ar' => array('session' => 'جلسة', '1_year' => 'سنة واحدة', '6_months' => '6 أشهر'),
            'ckb' => array('session' => 'دانیشتن', '1_year' => '١ ساڵ', '6_months' => '٦ مانگ'),
            'sv' => array('session' => 'Session', '1_year' => '1 år', '6_months' => '6 månader'),
            'zza' => array('session' => 'Roniştiş', '1_year' => '1 Serre', '6_months' => '6 Aşmî'),
            'ru' => array('session' => 'Сессия', '1_year' => '1 год', '6_months' => '6 месяцев')
        );

        $p = isset($purposes[$langCode]) ? $purposes[$langCode] : $purposes['en'];
        $d = isset($durations[$langCode]) ? $durations[$langCode] : $durations['en'];

        return array(
            array(
                'cookie_name' => 'PHPSESSID',
                'category' => 'necessary',
                'provider' => $projectName,
                'purpose' => $p['session'],
                'duration' => $d['session'],
                'display_order' => 1
            ),
            array(
                'cookie_name' => 'language, theme',
                'category' => 'necessary',
                'provider' => $projectName,
                'purpose' => $p['lang_theme'],
                'duration' => $d['1_year'],
                'display_order' => 2
            ),
            array(
                'cookie_name' => 'cookie_consent',
                'category' => 'functional',
                'provider' => $projectName,
                'purpose' => $p['consent'],
                'duration' => $d['1_year'],
                'display_order' => 3
            ),
            array(
                'cookie_name' => 'sidebar_state',
                'category' => 'functional',
                'provider' => $projectName,
                'purpose' => $p['sidebar'],
                'duration' => $d['6_months'],
                'display_order' => 4
            ),
            array(
                'cookie_name' => '_ga, _gid',
                'category' => 'analytics',
                'provider' => 'Analytics Provider',
                'purpose' => $p['analytics'],
                'duration' => $d['1_year'],
                'display_order' => 5
            ),
            array(
                'cookie_name' => '_camp_id',
                'category' => 'marketing',
                'provider' => 'Institutional Portal',
                'purpose' => $p['marketing'],
                'duration' => $d['6_months'],
                'display_order' => 6
            )
        );
    }

    private function add_default_working_hours()
    {
        $defaultSchedule = array(
            array('day_of_week' => 1, 'is_closed' => 'n', 'open_time' => '09:00:00', 'close_time' => '18:00:00', 'break_start_time' => '12:30:00', 'break_end_time' => '13:30:00', 'note' => 'Standard Working Hours'),
            array('day_of_week' => 2, 'is_closed' => 'n', 'open_time' => '09:00:00', 'close_time' => '18:00:00', 'break_start_time' => '12:30:00', 'break_end_time' => '13:30:00', 'note' => 'Standard Working Hours'),
            array('day_of_week' => 3, 'is_closed' => 'n', 'open_time' => '09:00:00', 'close_time' => '18:00:00', 'break_start_time' => '12:30:00', 'break_end_time' => '13:30:00', 'note' => 'Standard Working Hours'),
            array('day_of_week' => 4, 'is_closed' => 'n', 'open_time' => '09:00:00', 'close_time' => '18:00:00', 'break_start_time' => '12:30:00', 'break_end_time' => '13:30:00', 'note' => 'Standard Working Hours'),
            array('day_of_week' => 5, 'is_closed' => 'n', 'open_time' => '09:00:00', 'close_time' => '18:00:00', 'break_start_time' => '12:30:00', 'break_end_time' => '13:30:00', 'note' => 'Standard Working Hours'),
            array('day_of_week' => 6, 'is_closed' => 'n', 'open_time' => '10:00:00', 'close_time' => '16:00:00', 'break_start_time' => NULL, 'break_end_time' => NULL, 'note' => 'Weekend Schedule'),
            array('day_of_week' => 7, 'is_closed' => 'y', 'open_time' => '00:00:00', 'close_time' => '00:00:00', 'break_start_time' => NULL, 'break_end_time' => NULL, 'note' => 'Closed (Sunday)'),
        );

        foreach ($defaultSchedule as $day) {
            $this->db->fetch_array("SELECT id FROM `mod_settings_working_hour` WHERE day_of_week = " . (int)$day['day_of_week']);
            if ($this->db->getSize() === 0) {
                $this->db->db_insert('mod_settings_working_hour', $day);
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
