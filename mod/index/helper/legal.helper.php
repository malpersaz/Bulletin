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
        $rawType = $type !== null ? strtolower(trim((string) $type)) : 'info';
        $rawType = Req::Url_Clear($rawType);
        if ($rawType === '') {
            $rawType = 'info';
        }

        $aliasMap = [
            'info' => 'info',
            'legal' => 'info',
            'imprint' => 'info',
            'kunya' => 'info',
            'cookies' => 'cookies',
            'cookie-policy' => 'cookies',
            'privacy' => 'privacy',
            'privacy-policy' => 'privacy',
            'terms' => 'terms',
            'terms-of-service' => 'terms',
            'editorial' => 'editorial',
            'editorial-policy' => 'editorial',
            'editorial-guidelines' => 'editorial',
            'corrections' => 'editorial',
            'corrections-policy' => 'editorial',
            'accessibility' => 'accessibility',
            'a11y' => 'accessibility',
            'security' => 'security',
            'security-policy' => 'security',
            'compliance' => 'compliance',
            'ethics-policy' => 'compliance',
        ];

        if (!isset($aliasMap[$rawType])) {
            return $this->_404();
        }

        $this->ensureLegalInfoColumns();

        $resolvedKey = $aliasMap[$rawType];

        $registry = [
            'info' => [
                'canonical_slug' => 'legal',
                'template' => 'index/legal/index',
                'title_key' => 'legal_info',
                'desc_key' => 'legal_notice_desc',
                'schema_type' => 'AboutPage',
            ],
            'cookies' => [
                'canonical_slug' => 'cookie-policy',
                'template' => 'index/legal/cookie_policy',
                'title_key' => 'cookie_policy',
                'desc_key' => 'cookie_policy_subtitle',
                'schema_type' => 'WebPage',
            ],
            'privacy' => [
                'canonical_slug' => 'privacy-policy',
                'template' => 'index/legal/privacy_policy',
                'title_key' => 'privacy_policy',
                'desc_key' => 'privacy_policy_subtitle',
                'schema_type' => 'WebPage',
            ],
            'terms' => [
                'canonical_slug' => 'terms-of-service',
                'template' => 'index/legal/terms_of_service',
                'title_key' => 'terms_of_service',
                'desc_key' => 'terms_of_service_subtitle',
                'schema_type' => 'WebPage',
            ],
            'editorial' => [
                'canonical_slug' => 'editorial-policy',
                'template' => 'index/legal/editorial_policy',
                'title_key' => 'editorial_policy',
                'desc_key' => 'editorial_policy_subtitle',
                'schema_type' => 'WebPage',
            ],
            'accessibility' => [
                'canonical_slug' => 'accessibility',
                'template' => 'index/legal/accessibility',
                'title_key' => 'accessibility',
                'desc_key' => 'accessibility_subtitle',
                'schema_type' => 'WebPage',
            ],
            'security' => [
                'canonical_slug' => 'security-policy',
                'template' => 'index/legal/security_policy',
                'title_key' => 'security_policy',
                'desc_key' => 'security_policy_subtitle',
                'schema_type' => 'WebPage',
            ],
            'compliance' => [
                'canonical_slug' => 'compliance',
                'template' => 'index/legal/compliance',
                'title_key' => 'compliance',
                'desc_key' => 'compliance_subtitle',
                'schema_type' => 'WebPage',
            ],
        ];

        $policy = $registry[$resolvedKey];

        $title = isset($this->lang['index'][$policy['title_key']]) && !empty($this->lang['index'][$policy['title_key']])
            ? $this->lang['index'][$policy['title_key']]
            : ucwords(str_replace('-', ' ', $policy['canonical_slug']));

        $description = isset($this->lang['index'][$policy['desc_key']]) && !empty($this->lang['index'][$policy['desc_key']])
            ? $this->lang['index'][$policy['desc_key']]
            : (!empty($this->site['description']) ? $this->site['description'] : '');

        $canonicalUrl = $this->site['saddress'] . $policy['canonical_slug'];

        if ($resolvedKey === 'cookies') {
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
        }

        $this->theme->set('legal_type', $resolvedKey);
        $this->theme->set('canonical_url', $canonicalUrl);
        $this->theme->set('schema_type', $policy['schema_type']);
        $this->theme->set('policy_title', $title);
        $this->theme->set('policy_description', $description);

        $this->site['title'] = $title;
        if (!empty($description)) {
            $this->site['description'] = $description;
        }

        $this->theme->set('site', $this->site);
        $this->theme->tpl($policy['template']);
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

    /**
     * Ensures all legal and masthead columns exist in mod_settings table
     * and merges any non-empty legal fields into $this->site if missing.
     */
    private function ensureLegalInfoColumns()
    {
        if (!$this->db) {
            return;
        }

        $columns = [
            'legal_company_name' => 'VARCHAR(255) DEFAULT NULL',
            'legal_trading_name' => 'VARCHAR(255) DEFAULT NULL',
            'company_type' => 'VARCHAR(100) DEFAULT NULL',
            'company_registration_number' => 'VARCHAR(100) DEFAULT NULL',
            'trade_registry_number' => 'VARCHAR(100) DEFAULT NULL',
            'central_registration_number' => 'VARCHAR(100) DEFAULT NULL',
            'chamber_of_commerce' => 'VARCHAR(255) DEFAULT NULL',
            'corporate_address' => 'TEXT DEFAULT NULL',
            'contact_address' => 'TEXT DEFAULT NULL',
            'tax_office' => 'VARCHAR(255) DEFAULT NULL',
            'tax_number' => 'VARCHAR(100) DEFAULT NULL',
            'vat_number' => 'VARCHAR(100) DEFAULT NULL',
            'authorized_person' => 'VARCHAR(255) DEFAULT NULL',
            'registered_email' => 'VARCHAR(255) DEFAULT NULL',
            'legal_contact_phone' => 'VARCHAR(100) DEFAULT NULL',
            'managing_director' => 'VARCHAR(255) DEFAULT NULL',
            'editor_in_chief' => 'VARCHAR(255) DEFAULT NULL',
            'press_code_license' => 'VARCHAR(150) DEFAULT NULL',
            'dpo_email' => 'VARCHAR(255) DEFAULT NULL',
            'hosting_provider' => 'VARCHAR(255) DEFAULT NULL',
            'compliance_jurisdiction' => 'VARCHAR(255) DEFAULT NULL',
            'default_warranty_duration' => 'VARCHAR(50) DEFAULT NULL',
            'warranty_type' => 'VARCHAR(100) DEFAULT NULL',
            'return_policy_days' => 'VARCHAR(50) DEFAULT NULL',
            'country' => 'VARCHAR(100) DEFAULT NULL',
            'is_online_retail' => "VARCHAR(1) DEFAULT 'n'",
            'is_local_store' => "VARCHAR(1) DEFAULT 'y'",
            'is_service_business' => "VARCHAR(1) DEFAULT 'n'",
            'warranty_description' => 'TEXT DEFAULT NULL',
            'cookie_policy_content' => 'TEXT DEFAULT NULL',
        ];

        $safeTable = 'mod_settings';
        $adapter = (string) $this->db->getAdapterType();

        foreach ($columns as $column => $definition) {
            $safeColumn = addslashes($column);
            $exists = false;
            try {
                if (strpos($adapter, 'PDO_SQLITE') !== false) {
                    $this->db->fetch_array("PRAGMA table_info('" . $safeTable . "')");
                    $rows = $this->db->toArray();
                    if (is_array($rows)) {
                        foreach ($rows as $row) {
                            if (isset($row['name']) && $row['name'] === $column) {
                                $exists = true;
                                break;
                            }
                        }
                    }
                } elseif (strpos($adapter, 'POSTGRESQL') !== false) {
                    $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema NOT IN ('pg_catalog', 'information_schema') AND table_name='" . $safeTable . "' AND column_name='" . $safeColumn . "'";
                    $this->db->fetch_array($sql);
                    $exists = ($this->db->getSize() !== 0);
                } else {
                    $this->db->fetch_array("SHOW COLUMNS FROM `" . $safeTable . "` LIKE '" . $safeColumn . "'");
                    $exists = ($this->db->getSize() !== 0);
                }

                if (!$exists) {
                    $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN `" . $safeColumn . "` " . $definition;
                    $this->db->query($sql);
                }
            } catch (Exception $e) {
            }
        }

        try {
            $langCode = Registry::get('lang');
            $safeLang = is_string($langCode) && $langCode !== '' ? addslashes($langCode) : 'en';
            $this->db->fetch_array("SELECT * FROM `" . $safeTable . "` WHERE `lang` = '" . $safeLang . "' LIMIT 1");
            $rows = $this->db->toArray();
            if (empty($rows)) {
                $this->db->fetch_array("SELECT * FROM `" . $safeTable . "` ORDER BY `id` ASC LIMIT 1");
                $rows = $this->db->toArray();
            }
            if (!empty($rows) && is_array($rows[0])) {
                foreach (array_keys($columns) as $colKey) {
                    if ((!isset($this->site[$colKey]) || $this->site[$colKey] === '') && isset($rows[0][$colKey]) && $rows[0][$colKey] !== '') {
                        $this->site[$colKey] = $rows[0][$colKey];
                    }
                }
            }
        } catch (Exception $e) {
        }
    }
}
