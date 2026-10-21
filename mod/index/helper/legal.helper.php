<?php

class legal_helper extends indexBase
{
    public function __construct()
    {
        parent::__construct();
        $this->db = Load::lib('Database');
    }

    public function render($type = null)
    {
        $type = $type !== null ? strtolower(trim((string) $type)) : 'info';
        $type = Req::Url_Clear($type);

        $validTypes = ['info', 'cookies', 'privacy', 'terms', 'warranty'];
        if (!in_array($type, $validTypes, true)) {
            $type = 'info';
        }

        $template = 'index/legal/index';
        if ($type === 'cookies') {
            $title = isset($this->lang['index']['cookie_policy']) && !empty($this->lang['index']['cookie_policy'])
                ? $this->lang['index']['cookie_policy']
                : 'Cookie Policy';
            $description = isset($this->lang['index']['cookie_policy_subtitle']) && !empty($this->lang['index']['cookie_policy_subtitle'])
                ? $this->lang['index']['cookie_policy_subtitle']
                : '';
            $canonicalUrl = $this->site['saddress'] . 'cookie-policy';
            $template = 'index/legal/cookie_policy';

            // Fetch dynamic cookies grouped by category from mod_settings_cookies
            $cookiesByCategory = [
                'necessary' => [],
                'functional' => [],
                'analytics' => [],
                'marketing' => [],
            ];

            $langCode = Registry::get('lang');
            if (!is_string($langCode) || $langCode === '') {
                $langCode = 'en';
            }

            $tableReady = $this->ensureCookiesTable();
            $cookieRows = [];

            if ($tableReady && $this->db) {
                try {
                    $safeLang = addslashes($langCode);
                    $this->db->fetch_array("SELECT * FROM `mod_settings_cookies` WHERE `lang` = '{$safeLang}' AND `is_active` = 'y' ORDER BY `display_order` ASC, `id` ASC");
                    $cookieRows = $this->db->toArray();
                    if (empty($cookieRows) && $safeLang !== 'en') {
                        $this->db->fetch_array("SELECT * FROM `mod_settings_cookies` WHERE `lang` = 'en' AND `is_active` = 'y' ORDER BY `display_order` ASC, `id` ASC");
                        $cookieRows = $this->db->toArray();
                    }
                } catch (Exception $e) {
                    $cookieRows = [];
                }
            }

            if (empty($cookieRows)) {
                $siteName = !empty($this->site['title']) ? $this->site['title'] : 'AKM Bulletin CMS';
                $cookieRows = $this->getDefaultCookieSamples($langCode, $siteName);
            }

            if (is_array($cookieRows)) {
                foreach ($cookieRows as $row) {
                    $cat = isset($row['category']) ? strtolower(trim($row['category'])) : 'necessary';
                    if (isset($cookiesByCategory[$cat])) {
                        $cookiesByCategory[$cat][] = $row;
                    } else {
                        $cookiesByCategory['necessary'][] = $row;
                    }
                }
            }
            $this->theme->set('cookies_by_category', $cookiesByCategory);
        } else {
            $title = isset($this->lang['index']['legal_info']) && !empty($this->lang['index']['legal_info'])
                ? $this->lang['index']['legal_info']
                : 'Legal Information';

            $description = isset($this->lang['index']['legal_notice_desc']) && !empty($this->lang['index']['legal_notice_desc'])
                ? $this->lang['index']['legal_notice_desc']
                : (isset($this->lang['index']['legal_info_pending']) && !empty($this->lang['index']['legal_info_pending'])
                    ? $this->lang['index']['legal_info_pending']
                    : '');

            $canonicalUrl = $this->site['saddress'] . 'legal' . ($type !== 'info' ? '/' . $type : '');
        }

        $this->theme->ctx_set('legal_type', $type);
        $this->theme->ctx_set('canonical_url', $canonicalUrl);

        $this->site['title'] = $title;
        if (!empty($description)) {
            $this->site['description'] = $description;
        }

        $this->theme->set('site', $this->site);
        $this->theme->tpl($template);
        $this->_index();
    }

    /**
     * Ensures mod_settings_cookies table exists and is populated.
     * Compatible with MySQL and SQLite.
     *
     * @return bool
     */
    private function ensureCookiesTable()
    {
        if (!$this->db) {
            return false;
        }

        try {
            $adapter = (string) $this->db->getAdapterType();
            $isSqlite = (strpos($adapter, 'PDO_SQLITE') !== false);
            $tableExists = false;

            if ($isSqlite) {
                $this->db->fetch_array("SELECT name FROM sqlite_master WHERE type='table' AND name='mod_settings_cookies'");
                $tableExists = ($this->db->getSize() > 0);
            } else {
                $this->db->fetch_array("SHOW TABLES LIKE 'mod_settings_cookies'");
                $tableExists = ($this->db->getSize() > 0);
            }

            if (!$tableExists) {
                if ($isSqlite) {
                    $this->db->query("CREATE TABLE IF NOT EXISTS `mod_settings_cookies` (
                      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                      `lang` varchar(10) NOT NULL,
                      `cookie_name` varchar(100) NOT NULL,
                      `category` varchar(50) NOT NULL DEFAULT 'necessary',
                      `provider` varchar(150) NOT NULL,
                      `purpose` text NOT NULL,
                      `duration` varchar(100) NOT NULL,
                      `display_order` INTEGER NOT NULL DEFAULT 0,
                      `is_active` varchar(1) NOT NULL DEFAULT 'y',
                      `user_id` INTEGER NOT NULL DEFAULT 1
                    )");
                    $this->db->query("CREATE INDEX IF NOT EXISTS `idx_cookie_lang` ON `mod_settings_cookies` (`lang`)");
                    $this->db->query("CREATE INDEX IF NOT EXISTS `idx_cookie_cat` ON `mod_settings_cookies` (`category`)");
                } else {
                    $sql = "CREATE TABLE IF NOT EXISTS `mod_settings_cookies` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `lang` varchar(10) NOT NULL,
                      `cookie_name` varchar(100) NOT NULL,
                      `category` varchar(50) NOT NULL DEFAULT 'necessary',
                      `provider` varchar(150) NOT NULL,
                      `purpose` text NOT NULL,
                      `duration` varchar(100) NOT NULL,
                      `display_order` int(11) NOT NULL DEFAULT 0,
                      `is_active` enum('y','n') NOT NULL DEFAULT 'y',
                      `user_id` int(11) NOT NULL DEFAULT 1,
                      PRIMARY KEY (`id`),
                      INDEX `idx_cookie_lang` (`lang`),
                      INDEX `idx_cookie_cat` (`category`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
                    $this->db->query($sql);
                }
                $tableExists = true;
            }

            // Check if seeding is needed
            $this->db->fetch_array("SELECT id FROM `mod_settings_cookies` LIMIT 1");
            if ($this->db->getSize() === 0) {
                $siteName = !empty($this->site['title']) ? $this->site['title'] : 'AKM Bulletin CMS';
                $langs = ['en', 'ku', 'tr', 'de', 'fr', 'ar', 'ckb', 'sv', 'zza', 'ru'];
                foreach ($langs as $langCode) {
                    $samples = $this->getDefaultCookieSamples($langCode, $siteName);
                    foreach ($samples as $item) {
                        $safeLang = $this->db->quote($langCode);
                        $safeName = $this->db->quote($item['cookie_name']);
                        $safeCategory = $this->db->quote($item['category']);
                        $safeProvider = $this->db->quote($item['provider']);
                        $safePurpose = $this->db->quote($item['purpose']);
                        $safeDuration = $this->db->quote($item['duration']);
                        $display_order = (int) $item['display_order'];
                        $this->db->query("INSERT INTO `mod_settings_cookies` (`lang`, `cookie_name`, `category`, `provider`, `purpose`, `duration`, `display_order`, `is_active`, `user_id`) VALUES ({$safeLang}, {$safeName}, {$safeCategory}, {$safeProvider}, {$safePurpose}, {$safeDuration}, {$display_order}, 'y', 1)");
                    }
                }
            }

            return $tableExists;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Localized fallback cookie definitions.
     *
     * @param string $langCode
     * @param string $projectName
     * @return array
     */
    private function getDefaultCookieSamples($langCode, $projectName)
    {
        $purposes = [
            'ku' => [
                'session' => 'Nasnameya rûniştinê ya bikarhêner û parastina ewlehiyê tomar dike.',
                'lang_theme' => 'Zimanê bijartî û tercîhên xuyabûna sêwiranê (şev/roj) diparêze.',
                'consent' => 'Hilbijartin û pejirandina komên kulîçeyan di bîrê de dihêle.',
                'sidebar' => 'Rewşa vebûn/girtinê ya pêkhateyên rûpelê tomar dike.',
                'analytics' => 'Amarên anonîm ên serdanvanan û leza rûpelan dipîve.',
                'marketing' => 'Bandor û têkiliyên reklam û agahdarkirinê dipîve.'
            ],
            'en' => [
                'session' => 'Preserves authenticated user state and request security token across requests.',
                'lang_theme' => 'Remembers visitor language selection and theme display mode preference.',
                'consent' => 'Stores cookie category consent approvals and visitor privacy preferences.',
                'sidebar' => 'Preserves navigational state and interface element visibility preferences.',
                'analytics' => 'Measures anonymized visitor statistics, page render speeds, and traffic trends.',
                'marketing' => 'Measures outreach and advertising campaign effectiveness and audience engagement.'
            ],
            'tr' => [
                'session' => 'Kullanıcı oturum kimliğini ve istek güvenliğini oturum süresince korur.',
                'lang_theme' => 'Ziyaretçinin seçtiği arayüz dilini ve tema görünüm modunu hatırlar.',
                'consent' => 'Çerez onay tercihlerini ve gizlilik izinlerini tarayıcıda saklar.',
                'sidebar' => 'Arayüz gezinme durumunu ve panel görünüm tercihlerini korur.',
                'analytics' => 'Anonim ziyaretçi istatistiklerini, sayfa hızını ve trafik verilerini analiz eder.',
                'marketing' => 'Tanıtım ve duyuru kampanyalarının etkileşim oranlarını ölçer.'
            ],
            'de' => [
                'session' => 'Speichert die Benutzersitzung und Authentifizierungstoken während des Besuchs.',
                'lang_theme' => 'Speichert die ausgewählte Sprache und den bevorzugten Design-Modus.',
                'consent' => 'Speichert die erteilten Cookie-Einwilligungen und Datenschutzeinstellungen.',
                'sidebar' => 'Speichert den Navigationsstatus und Benutzeroberflächen-Einstellungen.',
                'analytics' => 'Erfasst anonymisierte Besucherstatistiken und Seitenladezeiten.',
                'marketing' => 'Misst die Wirksamkeit von Informations- und Kommunikationskampagnen.'
            ],
            'fr' => [
                'session' => 'Conserve la session utilisateur et le jeton de sécurité durant la navigation.',
                'lang_theme' => 'Mémorise la langue sélectionnée et le mode d’affichage du thème.',
                'consent' => 'Enregistre les consentements de cookies et les préférences de confidentialité.',
                'sidebar' => 'Conserve l’état des menus et des éléments de l’interface.',
                'analytics' => 'Mesure les statistiques de fréquentation anonymisées et la rapidité des pages.',
                'marketing' => 'Mesure l’efficacité des campagnes et l’engagement des visiteurs.'
            ],
            'ar' => [
                'session' => 'يحفظ جلسة المستخدم النشطة ورموز الأمان طوال فترة التصفح.',
                'lang_theme' => 'يتذكر تفضيلات اللغة المختارة ووضع مظهر الموقع.',
                'consent' => 'يحفظ موافقات المستخدم على فئات ملفات تعريف الارتباط.',
                'sidebar' => 'يحفظ حالة التنقل وتفضيلات واجهة المستخدم.',
                'analytics' => 'يقيس إحصاءات الزوار المجهولة وسرعة استجابة الصفحات.',
                'marketing' => 'يقيس مدى فاعلية الحملات التوعوية والتفاعلات الرقمية.'
            ],
            'ckb' => [
                'session' => 'ناسنامەی دانیشتنی بەکارهێنەر و پاراستنی ئاسایش لە کاتی سەرداندا دەپارێزێت.',
                'lang_theme' => 'زمانی هەڵبژێردراو و دۆخی دەرکەوتنی ڕووکار لە بیر دەهێڵێتەوە.',
                'consent' => 'هەڵبژاردن و ڕەزامەندییەکانی تایبەت بە کووکی پاشەکەوت دەکات.',
                'sidebar' => 'دۆخی کردنەوە و داخستنی بەشەکانی ڕووکار دەپارێزێت.',
                'analytics' => 'ئاماری نەناسراوی سەردانکەران و خێرایی پەڕەکان دەپێوێت.',
                'marketing' => 'کاریگەری کەمپینەکانی ناساندن و کارلێکەکان دەپێوێت.'
            ],
            'sv' => [
                'session' => 'Behåller användarsessionen och säkerhetstoken under webbplatsbesöket.',
                'lang_theme' => 'Kommer ihåg valt språk och visningsläge för webbplatsen.',
                'consent' => 'Sparar godkända samtycken för cookie-kategorier och integritetsval.',
                'sidebar' => 'Behåller navigeringsläge och gränssnittsinställningar.',
                'analytics' => 'Mäter anonymiserad besökarstatistik och sidladdningstider.',
                'marketing' => 'Mäter räckvidd och interaktioner för informationskampanjer.'
            ],
            'zza' => [
                'session' => 'Nasnameyê roniştişî û ewleyîya şuxulnayoxî wextê gêrayîşî de paweno.',
                'lang_theme' => 'Zonê tercîhkerdeyî û modê asayîşê sîstemî ano vîr.',
                'consent' => 'Tercihanê sîyasetê çerezan û destûranê xisûsîyan qeyd keno.',
                'sidebar' => 'Wextê gêrayîşî de rewşa menuyan û tercihanê rîyê peli paweno.',
                'analytics' => 'Amaranê anonîman ê zîyaretkeran û lezîya pelan pîyeno.',
                'marketing' => 'Karîgerîya kampanyayanê şınasnayîşî û têkilîyan pîyeno.'
            ],
            'ru' => [
                'session' => 'Сохраняет идентификатор сеанса пользователя и обеспечивает безопасность запросов.',
                'lang_theme' => 'Запоминает выбранный язык интерфейса и предпочтения оформления темы.',
                'consent' => 'Сохраняет согласия пользователя на категории файлов cookie и параметры конфиденциальности.',
                'sidebar' => 'Сохраняет состояние навигации и видимость элементов интерфейса.',
                'analytics' => 'Анализирует анонимную статистику посещаемости и скорость загрузки страниц.',
                'marketing' => 'Оценивает эффективность информационных кампаний и охват аудитории.'
            ]
        ];

        $durations = [
            'ku' => ['session' => 'Danşîn', '1_year' => '1 Sal', '6_months' => '6 Meh'],
            'en' => ['session' => 'Session', '1_year' => '1 Year', '6_months' => '6 Months'],
            'tr' => ['session' => 'Oturum', '1_year' => '1 Yıl', '6_months' => '6 Ay'],
            'de' => ['session' => 'Sitzung', '1_year' => '1 Jahr', '6_months' => '6 Monate'],
            'fr' => ['session' => 'Session', '1_year' => '1 An', '6_months' => '6 Mois'],
            'ar' => ['session' => 'جلسة', '1_year' => 'سنة واحدة', '6_months' => '6 أشهر'],
            'ckb' => ['session' => 'دانیشتن', '1_year' => '١ ساڵ', '6_months' => '٦ مانگ'],
            'sv' => ['session' => 'Session', '1_year' => '1 år', '6_months' => '6 månader'],
            'zza' => ['session' => 'Roniştiş', '1_year' => '1 Serre', '6_months' => '6 Aşmî'],
            'ru' => ['session' => 'Сессия', '1_year' => '1 год', '6_months' => '6 месяцев']
        ];

        $p = $purposes[$langCode] ?? $purposes['en'];
        $d = $durations[$langCode] ?? $durations['en'];

        return [
            [
                'cookie_name' => 'PHPSESSID',
                'category' => 'necessary',
                'provider' => $projectName,
                'purpose' => $p['session'],
                'duration' => $d['session'],
                'display_order' => 1
            ],
            [
                'cookie_name' => 'language, theme',
                'category' => 'necessary',
                'provider' => $projectName,
                'purpose' => $p['lang_theme'],
                'duration' => $d['1_year'],
                'display_order' => 2
            ],
            [
                'cookie_name' => 'cookie_consent',
                'category' => 'functional',
                'provider' => $projectName,
                'purpose' => $p['consent'],
                'duration' => $d['1_year'],
                'display_order' => 3
            ],
            [
                'cookie_name' => 'sidebar_state',
                'category' => 'functional',
                'provider' => $projectName,
                'purpose' => $p['sidebar'],
                'duration' => $d['6_months'],
                'display_order' => 4
            ],
            [
                'cookie_name' => '_ga, _gid',
                'category' => 'analytics',
                'provider' => 'Analytics Provider',
                'purpose' => $p['analytics'],
                'duration' => $d['1_year'],
                'display_order' => 5
            ],
            [
                'cookie_name' => '_camp_id',
                'category' => 'marketing',
                'provider' => 'Media & News Portal',
                'purpose' => $p['marketing'],
                'duration' => $d['6_months'],
                'display_order' => 6
            ]
        ];
    }
}
