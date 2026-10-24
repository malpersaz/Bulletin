<?php
/*
 * Amed Framework
 * @2025
 * mod_settings Module - Admin Controller
 * mod/mod_settings/index.php
 */
class mod_settings_mod extends indexMain
{


    public $language_management;
    public function __construct()
    {
        parent::__construct();
        // Load required libraries and models
        $this->theme = Load::lib('AdminTemplate');
        $this->db = Load::lib('Database');

        // Load language based on the current interface language.
        // Use the explicit runtime default language when the request does not provide one.
        $default_lang = Registry::get('default_lang');
        if (!is_string($default_lang) || $default_lang === '') {
            $default_lang = 'en';
        }

        $selected_lang = $this->site['lang'] ?? $default_lang; // Will be updated later
        $this->lang = Load::lang('mod_settings');

        $this->session = Load::lib('Session');
        $this->user = Load::model('mod_user.user');

        $this->ensureSiteNameColumn();
        $this->ensureStatsColumns();
        $this->ensureAppTemplateColumn();
        $this->ensureAnimatedLogoColumns();
        $this->ensureSiteStatusColumns();
        $this->ensureLegalInfoColumns();
        $this->ensureCookiesTable();
        $this->ensureWorkingHoursTable();
        $this->theme->lang($this->lang);
    }


    private function ensureStatsColumns()
    {
        if (!$this->db) {
            return;
        }

        $columns = [
            'stat_workforce' => 'INT DEFAULT 1200',
            'stat_offices' => 'INT DEFAULT 18',
        ];

        $table = 'mod_settings';
        $safeTable = addslashes($table);
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
    }

    private function ensureSiteNameColumn()
    {
        if (!$this->db) {
            return;
        }

        $table = 'mod_settings';
        $column = 'site_name';
        $safeTable = addslashes($table);
        $safeColumn = addslashes($column);
        $adapter = (string) $this->db->getAdapterType();
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
                if (strpos($adapter, 'PDO_SQLITE') !== false) {
                    $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN site_name VARCHAR(255) DEFAULT NULL";
                } elseif (strpos($adapter, 'POSTGRESQL') !== false) {
                    $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN site_name VARCHAR(255) DEFAULT NULL";
                } else {
                    $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN `site_name` VARCHAR(255) DEFAULT NULL";
                }
                $this->db->query($sql);
            }
        } catch (Exception $e) {
        }
    }

    private function ensureAppTemplateColumn()
    {
        if (!$this->db) {
            return;
        }

        $table = 'mod_settings';
        $column = 'app_template';
        $safeTable = addslashes($table);
        $safeColumn = addslashes($column);
        $adapter = (string) $this->db->getAdapterType();
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
                if (strpos($adapter, 'PDO_SQLITE') !== false || strpos($adapter, 'POSTGRESQL') !== false) {
                    $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN app_template VARCHAR(64) DEFAULT 'amida'";
                } else {
                    $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN `app_template` VARCHAR(64) DEFAULT 'amida'";
                }
                $this->db->query($sql);
            }
        } catch (Exception $e) {
        }
    }

    private function ensureAnimatedLogoColumns()
    {
        if (!$this->db) {
            return;
        }

        $columns = [
            'use_animated_logo' => "VARCHAR(1) DEFAULT 'n'",
            'animated_logo_path' => "VARCHAR(500) DEFAULT NULL",
            'animated_logo_type' => "VARCHAR(50) DEFAULT 'webm'",
            'animated_logo_height' => "INT DEFAULT 48",
            'animated_logo_aspect' => "VARCHAR(20) DEFAULT '16/9'",
            'animated_logo_mode' => "VARCHAR(20) DEFAULT 'loop'",
            'animated_logo_blend' => "VARCHAR(20) DEFAULT 'multiply'",
        ];

        $table = 'mod_settings';
        $safeTable = addslashes($table);
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
    }

    /**
     * Ensures site_status and maintenance_message columns exist in mod_settings table.
     */
    private function ensureSiteStatusColumns()
    {
        static $checked = false;
        if ($checked || !$this->db) {
            return;
        }
        $checked = true;

        $columns = [
            'site_status' => "VARCHAR(20) DEFAULT 'open'",
            'maintenance_message' => "TEXT NULL",
            'maintenance_retry_after' => "INT DEFAULT 3600",
        ];

        $table = 'mod_settings';
        $safeTable = addslashes($table);
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
    }

    /**
     * Ensures legal information columns exist in mod_settings table.
     */
    private function ensureLegalInfoColumns()
    {
        static $checked = false;
        if ($checked || !$this->db) {
            return;
        }
        $checked = true;

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

        $table = 'mod_settings';
        $safeTable = addslashes($table);
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

        // Auto-seed default cookie policy sample text for empty rows
        try {
            $this->db->fetch_array("SELECT id, lang, cookie_policy_content FROM `" . $safeTable . "`");
            $existingRows = $this->db->toArray();
            if (is_array($existingRows)) {
                foreach ($existingRows as $r) {
                    if (!isset($r['cookie_policy_content']) || trim((string) $r['cookie_policy_content']) === '') {
                        $rowLang = isset($r['lang']) ? trim((string) $r['lang']) : 'en';
                        $sampleText = $this->_get_default_cookie_policy_sample($rowLang);
                        $safeSample = $this->db->quote($sampleText);
                        $rowId = (int) $r['id'];
                        $this->db->query("UPDATE `" . $safeTable . "` SET `cookie_policy_content` = {$safeSample} WHERE `id` = {$rowId}");
                    }
                }
            }
        } catch (Exception $e) {
        }
    }

    /**
     * Ensures mod_settings_cookies table exists and is seeded.
     */
    private function ensureCookiesTable()
    {
        static $checked = false;
        if ($checked || !$this->db) {
            return;
        }
        $checked = true;

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
            }

            // Auto-seed if empty
            $this->db->fetch_array("SELECT id FROM `mod_settings_cookies` LIMIT 1");
            if ($this->db->getSize() === 0) {
                $this->db->fetch_array("SELECT seo FROM `lang`");
                $langs = $this->db->toArray();
                if (empty($langs)) {
                    $langs = [['seo' => 'en'], ['seo' => 'ku'], ['seo' => 'tr']];
                }
                $siteName = !empty($this->site['title']) ? $this->site['title'] : 'Amed Framework';
                foreach ($langs as $lang) {
                    $l = $lang['seo'];
                    $sampleCookies = $this->_get_sample_cookies_for_lang($l, $siteName);
                    foreach ($sampleCookies as $item) {
                        $safeLang = $this->db->quote($l);
                        $safeName = $this->db->quote($item['cookie_name']);
                        $safeCat = $this->db->quote($item['category']);
                        $safeProvider = $this->db->quote($item['provider']);
                        $safePurpose = $this->db->quote($item['purpose']);
                        $safeDuration = $this->db->quote($item['duration']);
                        $order = (int) $item['display_order'];
                        $this->db->query("INSERT INTO `mod_settings_cookies` (`lang`, `cookie_name`, `category`, `provider`, `purpose`, `duration`, `display_order`, `is_active`, `user_id`) VALUES ({$safeLang}, {$safeName}, {$safeCat}, {$safeProvider}, {$safePurpose}, {$safeDuration}, {$order}, 'y', 1)");
                    }
                }
            }
        } catch (Exception $e) {
        }
    }

    /**
     * Helper to return sample cookies for a given language code.
     */
    private function _get_sample_cookies_for_lang($langCode, $projectName)
    {
        $purposes = [
            'ku' => [
                'session' => 'Nasnameya rûniştinê ya bikarhêner û parastina ewlehiyê tomar dike.',
                'lang_theme' => 'Zimanê bijartî û tercîhên xuyabûna sêwiranê (şev/roj) diparêze.',
                'consent' => 'Hilbijartin û pejirandina komên kulîçeyan di bîrê de dihêle.',
                'sidebar' => 'Rewşa vebûn/girtinê ya pêkhateyên rûpelê tomar dike.',
                'analytics' => 'Amarên anonîm ên serdanvanan û leza rûpelan dipîve.',
                'marketing' => 'Bandor û têkiliyên kampanyayên danasînê dipîve.'
            ],
            'en' => [
                'session' => 'Preserves authenticated user state and request security token across requests.',
                'lang_theme' => 'Remembers visitor language selection and theme display mode preference.',
                'consent' => 'Stores cookie category consent approvals and visitor privacy preferences.',
                'sidebar' => 'Preserves navigational state and interface element visibility preferences.',
                'analytics' => 'Measures anonymized visitor statistics, page render speeds, and traffic trends.',
                'marketing' => 'Measures outreach campaign effectiveness and audience engagement metrics.'
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
                'provider' => 'Institutional Portal',
                'purpose' => $p['marketing'],
                'duration' => $d['6_months'],
                'display_order' => 6
            ]
        ];
    }

    /**
     * Ensures mod_settings_working_hour and mod_settings_closure tables exist and are seeded.
     */
    private function ensureWorkingHoursTable()
    {
        static $checked = false;
        if ($checked || !$this->db) {
            return;
        }
        $checked = true;

        try {
            $adapter = (string) $this->db->getAdapterType();
            $isSqlite = (strpos($adapter, 'PDO_SQLITE') !== false);

            $workingHoursTableExists = false;
            if ($isSqlite) {
                $this->db->fetch_array("SELECT name FROM sqlite_master WHERE type='table' AND name='mod_settings_working_hour'");
                $workingHoursTableExists = ($this->db->getSize() > 0);
            } else {
                $this->db->fetch_array("SHOW TABLES LIKE 'mod_settings_working_hour'");
                $workingHoursTableExists = ($this->db->getSize() > 0);
            }

            if (!$workingHoursTableExists) {
                if ($isSqlite) {
                    $this->db->query("CREATE TABLE IF NOT EXISTS `mod_settings_working_hour` (
                      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                      `day_of_week` INTEGER NOT NULL,
                      `is_closed` varchar(1) NOT NULL DEFAULT 'n',
                      `open_time` varchar(10) NOT NULL DEFAULT '09:00:00',
                      `close_time` varchar(10) NOT NULL DEFAULT '18:00:00',
                      `break_start_time` varchar(10) DEFAULT NULL,
                      `break_end_time` varchar(10) DEFAULT NULL,
                      `note` varchar(255) DEFAULT NULL,
                      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                    )");
                    $this->db->query("CREATE UNIQUE INDEX IF NOT EXISTS `uk_working_day` ON `mod_settings_working_hour` (`day_of_week`)");
                } else {
                    $this->db->query("CREATE TABLE IF NOT EXISTS `mod_settings_working_hour` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `day_of_week` tinyint(1) NOT NULL,
                      `is_closed` enum('y','n') NOT NULL DEFAULT 'n',
                      `open_time` time NOT NULL DEFAULT '09:00:00',
                      `close_time` time NOT NULL DEFAULT '18:00:00',
                      `break_start_time` time DEFAULT NULL,
                      `break_end_time` time DEFAULT NULL,
                      `note` varchar(255) DEFAULT NULL,
                      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `uk_working_day` (`day_of_week`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
                }
            }

            $closureTableExists = false;
            if ($isSqlite) {
                $this->db->fetch_array("SELECT name FROM sqlite_master WHERE type='table' AND name='mod_settings_closure'");
                $closureTableExists = ($this->db->getSize() > 0);
            } else {
                $this->db->fetch_array("SHOW TABLES LIKE 'mod_settings_closure'");
                $closureTableExists = ($this->db->getSize() > 0);
            }

            if (!$closureTableExists) {
                if ($isSqlite) {
                    $this->db->query("CREATE TABLE IF NOT EXISTS `mod_settings_closure` (
                      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                      `start_date` varchar(20) NOT NULL,
                      `end_date` varchar(20) NOT NULL,
                      `is_full_day` varchar(1) NOT NULL DEFAULT 'y',
                      `open_time` varchar(10) DEFAULT NULL,
                      `close_time` varchar(10) DEFAULT NULL,
                      `reason` varchar(255) NOT NULL,
                      `closure_type` varchar(50) NOT NULL DEFAULT 'holiday',
                      `is_recurring_yearly` varchar(1) NOT NULL DEFAULT 'n',
                      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                    )");
                    $this->db->query("CREATE INDEX IF NOT EXISTS `idx_closure_range` ON `mod_settings_closure` (`start_date`, `end_date`)");
                } else {
                    $this->db->query("CREATE TABLE IF NOT EXISTS `mod_settings_closure` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `start_date` date NOT NULL,
                      `end_date` date NOT NULL,
                      `is_full_day` enum('y','n') NOT NULL DEFAULT 'y',
                      `open_time` time DEFAULT NULL,
                      `close_time` time DEFAULT NULL,
                      `reason` varchar(255) NOT NULL,
                      `closure_type` enum('holiday','vacation','maintenance','emergency') NOT NULL DEFAULT 'holiday',
                      `is_recurring_yearly` enum('y','n') NOT NULL DEFAULT 'n',
                      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      INDEX `idx_closure_range` (`start_date`, `end_date`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
                }
            }

            // Seed default 7-day schedule if empty
            $this->db->fetch_array("SELECT id FROM `mod_settings_working_hour` LIMIT 1");
            if ($this->db->getSize() === 0) {
                $defaultSchedule = [
                    ['day_of_week' => 1, 'is_closed' => 'n', 'open_time' => '09:00:00', 'close_time' => '18:00:00', 'break_start_time' => '12:30:00', 'break_end_time' => '13:30:00', 'note' => 'Standard Clinic Hours'],
                    ['day_of_week' => 2, 'is_closed' => 'n', 'open_time' => '09:00:00', 'close_time' => '18:00:00', 'break_start_time' => '12:30:00', 'break_end_time' => '13:30:00', 'note' => 'Standard Clinic Hours'],
                    ['day_of_week' => 3, 'is_closed' => 'n', 'open_time' => '09:00:00', 'close_time' => '18:00:00', 'break_start_time' => '12:30:00', 'break_end_time' => '13:30:00', 'note' => 'Standard Clinic Hours'],
                    ['day_of_week' => 4, 'is_closed' => 'n', 'open_time' => '09:00:00', 'close_time' => '18:00:00', 'break_start_time' => '12:30:00', 'break_end_time' => '13:30:00', 'note' => 'Standard Clinic Hours'],
                    ['day_of_week' => 5, 'is_closed' => 'n', 'open_time' => '09:00:00', 'close_time' => '18:00:00', 'break_start_time' => '12:30:00', 'break_end_time' => '13:30:00', 'note' => 'Standard Clinic Hours'],
                    ['day_of_week' => 6, 'is_closed' => 'n', 'open_time' => '10:00:00', 'close_time' => '16:00:00', 'break_start_time' => null, 'break_end_time' => null, 'note' => 'Weekend Schedule'],
                    ['day_of_week' => 7, 'is_closed' => 'y', 'open_time' => '00:00:00', 'close_time' => '00:00:00', 'break_start_time' => null, 'break_end_time' => null, 'note' => 'Closed (Sunday)'],
                ];
                foreach ($defaultSchedule as $day) {
                    $this->db->db_insert('mod_settings_working_hour', $day);
                }
            }
        } catch (Exception $e) {
        }
    }

    /**
     * Ensures mod_settings_affiliation table exists and is seeded with initial affiliations if empty.
     */
    public function ensureAffiliationTable()
    {
        static $checked = false;
        if ($checked || !$this->db) {
            return;
        }
        $checked = true;

        try {
            $adapter = (string) $this->db->getAdapterType();
            $isSqlite = (strpos($adapter, 'PDO_SQLITE') !== false);

            $tableExists = false;
            if ($isSqlite) {
                $this->db->fetch_array("SELECT name FROM sqlite_master WHERE type='table' AND name='mod_settings_affiliation'");
                $tableExists = ($this->db->getSize() > 0);
            } else {
                $this->db->fetch_array("SHOW TABLES LIKE 'mod_settings_affiliation'");
                $tableExists = ($this->db->getSize() > 0);
            }

            if (!$tableExists) {
                if ($isSqlite) {
                    $this->db->query("CREATE TABLE IF NOT EXISTS `mod_settings_affiliation` (
                      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                      `name` varchar(255) NOT NULL,
                      `url` varchar(500) NOT NULL,
                      `logo` varchar(255) DEFAULT '',
                      `display_order` int(11) NOT NULL DEFAULT 1,
                      `is_active` varchar(1) NOT NULL DEFAULT 'y',
                      `user_id` int(11) NOT NULL DEFAULT 1,
                      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
                    )");
                    $this->db->query("CREATE INDEX IF NOT EXISTS `idx_affil_active_order` ON `mod_settings_affiliation` (`is_active`, `display_order`)");
                } else {
                    $this->db->query("CREATE TABLE IF NOT EXISTS `mod_settings_affiliation` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `name` varchar(255) NOT NULL,
                      `url` varchar(500) NOT NULL,
                      `logo` varchar(255) DEFAULT '',
                      `display_order` int(11) NOT NULL DEFAULT 1,
                      `is_active` enum('y','n') NOT NULL DEFAULT 'y',
                      `user_id` int(11) NOT NULL DEFAULT 1,
                      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      KEY `idx_active_order` (`is_active`, `display_order`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
                }
            }

            // Seed initial institutions if table is empty
            $this->db->fetch_array("SELECT id FROM `mod_settings_affiliation` LIMIT 1");
            if ($this->db->getSize() === 0) {
                $initialSeed = [
                    ['name' => 'Chamber of Commerce & Industry', 'url' => '#', 'logo' => '', 'display_order' => 1, 'is_active' => 'y', 'user_id' => 1],
                    ['name' => 'National Business Federation', 'url' => '#', 'logo' => '', 'display_order' => 2, 'is_active' => 'y', 'user_id' => 1],
                    ['name' => 'Global Enterprise Network', 'url' => '#', 'logo' => '', 'display_order' => 3, 'is_active' => 'y', 'user_id' => 1],
                    ['name' => 'International Trade Association', 'url' => '#', 'logo' => '', 'display_order' => 4, 'is_active' => 'y', 'user_id' => 1],
                    ['name' => 'Standards & Accreditation Council', 'url' => '#', 'logo' => '', 'display_order' => 5, 'is_active' => 'y', 'user_id' => 1],
                ];
                foreach ($initialSeed as $seedItem) {
                    $this->db->db_insert('mod_settings_affiliation', $seedItem);
                }
            }
        } catch (Exception $e) {
        }
    }

    /**
     * Fetches the current site status and maintenance message via AJAX.
     */
    public function ajax_get_site_status()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied', 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied',
            ]);
            return;
        }

        $this->ensureSiteStatusColumns();
        $this->db->fetch_array("SELECT site_status, maintenance_message, maintenance_retry_after FROM mod_settings LIMIT 1");
        $data = $this->db->toArray()[0] ?? [];

        $site_status = (!empty($data['site_status']) && $data['site_status'] === 'closed') ? 'closed' : 'open';
        $defaultMsg = $this->lang['mod_settings']['default_maintenance_message'] ?? '';
        $maintenance_message = (isset($data['maintenance_message']) && trim($data['maintenance_message']) !== '') ? $data['maintenance_message'] : $defaultMsg;

        $this->_json_response([
            'status' => 'success',
            'site_status' => $site_status,
            'maintenance_message' => $maintenance_message,
            'maintenance_retry_after' => intval($data['maintenance_retry_after'] ?? 3600),
        ]);
    }

    /**
     * Saves the updated site status and maintenance message via AJAX.
     */
    public function ajax_save_site_status()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied', 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied',
            ]);
            return;
        }

        $this->ensureSiteStatusColumns();
        $new_status = (Req::post('site_status') === 'closed') ? 'closed' : 'open';
        $message = Req::post('maintenance_message');
        if (!is_string($message) || trim($message) === '') {
            $message = $this->lang['mod_settings']['default_maintenance_message'] ?? '';
        }
        $safeMessage = $this->db->quote($message);

        $sql = "UPDATE mod_settings SET site_status = '{$new_status}', maintenance_message = {$safeMessage}";
        $result = $this->db->query($sql);

        if ($result) {
            $this->_json_response([
                'status' => 'success',
                'message' => $this->lang['mod_settings']['maintenance_status_updated'] ?? 'Site status updated successfully.',
                'site_status' => $new_status,
                'maintenance_message' => $message,
            ]);
        } else {
            $this->_json_response([
                'status' => 'error',
                'message' => 'Failed to save site status.'
            ]);
        }
    }

    /**
     * Handles the editing of site settings.
     * Displays a form with settings for all languages and processes updates.
     */
    public function edit($lang = NULL)
    {

        // Check for 'edit' permission for this module
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            return false;
        }

        // Process form submission if POST request
        if (Req::is_Post('edit')) {
            $this->_edit();
        }

        // Prepare the view
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_settings']['title'];
        $this->theme->set('site', $this->site);


        // Fetch all languages for dropdown
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY name ASC");
        $langs = $this->db->getResult()->toArray();
        $this->theme->set('langs', $langs);

        // Clean the language parameter
        $lang = Req::Url_Clear($lang);

        // Get selected language from URL parameter
        $selected_lang = null;
        if ($lang && !empty($lang)) {
            $selected_lang = $lang;
        } else {
            // For admin settings, prefer the explicit runtime default language.
            $selected_lang = Registry::get('default_lang');
            if (!is_string($selected_lang) || $selected_lang === '') {
                $selected_lang = 'en';
            }
            $lang_exists = false;
            foreach ($langs as $lang_item) {
                if ($lang_item['seo'] === $selected_lang) {
                    $lang_exists = true;
                    break;
                }
            }
            // If selected language does not exist, use first available language
            if (!$lang_exists && !empty($langs)) {
                $selected_lang = $langs[0]['seo'];
            }
        }

        // REMOVED: Do not force interface language to match edited language
        // $this->lang = Load::lang('mod_settings', $selected_lang);
        // $this->theme->lang($this->lang);

        // Verify the selected language exists in available languages
        $lang_exists = false;
        foreach ($langs as $lang) {
            if ($lang['seo'] === $selected_lang) {
                $lang_exists = true;
                break;
            }
        }

        // If language does not exist, use first available language
        if (!$lang_exists && !empty($langs)) {
            $selected_lang = $langs[0]['seo'];
        }

        $this->theme->set('selected_lang', $selected_lang);
        $this->_load_flash_messages();

        // Fetch settings for selected language
        $setting = null;
        if ($selected_lang) {
            $settings_model = Load::model('mod_settings');
            $settings_model->where_lang($selected_lang)->gRo();
            if ($settings_model->getSize() > 0) {
                $settings_model->sync();
                $setting = $settings_model->getResultOne()->toArray();
            }
        }
        $this->theme->set('setting', $setting);

        // Render the template
        $this->theme->tpl('mod_settings/edit');
    }

    /**
     * Handles the social media standalone page
     */
    public function social_media()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            return false;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_settings']['sections']['social_title'] ?? 'Social Media';
        $this->theme->set('site', $this->site);
        $this->theme->set('is_ajax', $this->_is_ajax_request());
        $this->theme->set('back_url', $this->site['saddress'] . 'mod_settings/edit');

        $this->db->fetch_array("SELECT * FROM mod_settings_social ORDER BY display_order ASC");
        $social_items = $this->db->getResult()->toArray();
        $this->theme->set('social_items', $social_items);

        $this->theme->tpl('mod_settings/social_media');
    }

    /**
     * Determines whether the current request is an AJAX request.
     */
    private function _is_ajax_request()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (!empty($_POST['ajax_request']) && $_POST['ajax_request'] === 'true') ||
            (!empty($_GET['ajax']) && ($_GET['ajax'] === '1' || $_GET['ajax'] === 'true')) ||
            (!empty($_GET['is_ajax']) && ($_GET['is_ajax'] === '1' || $_GET['is_ajax'] === 'true'));
    }

    /**
     * Handles the Legal & Tax Information admin page (Global Single Form).
     */
    public function legal_info()
    {
        // Check for 'edit' permission for this module
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied', 1) === false) {
            return false;
        }

        // Redirect non-AJAX direct visits to open modal over admin panel
        if (!$this->_is_ajax_request() && Req::get('direct') !== '1') {
            Helper::redirect($this->site['saddress'] . 'mod_user/panel?open_modal=legal_info');
            return false;
        }

        // Process form submission if POST request
        if (Req::is_Post('legal_info')) {
            $this->_legal_info();
        }

        // Prepare the view
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_settings']['legal_info_title'] ?? 'Legal Information';
        $this->theme->set('site', $this->site);
        $this->theme->set('is_ajax', $this->_is_ajax_request());

        $this->_load_flash_messages();

        // Fetch global settings
        $settings_model = Load::model('mod_settings');
        $default_lang = Registry::get('default_lang');
        if (!is_string($default_lang) || $default_lang === '') {
            $default_lang = 'en';
        }

        $setting = null;
        $settings_model->where_lang($default_lang)->gRo();
        if ($settings_model->getSize() > 0) {
            $settings_model->sync();
            $setting = $settings_model->getResultOne()->toArray();
        } else {
            $settings_model->gRo();
            if ($settings_model->getSize() > 0) {
                $settings_model->sync();
                $setting = $settings_model->getResultOne()->toArray();
            }
        }
        $this->theme->set('setting', $setting);

        $this->theme->set('organization_types', []);
        $this->theme->set('organization_types_grouped', []);

        // Render template
        $this->theme->tpl('mod_settings/legal_info');
    }

    /**
     * Handles the Cookie Policy & Inventory admin page.
     */
    public function cookie_policy()
    {
        // Check for 'edit' permission for this module
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied', 1) === false) {
            return false;
        }

        // Redirect non-AJAX direct visits to open modal over admin panel
        if (!$this->_is_ajax_request() && Req::get('direct') !== '1') {
            Helper::redirect($this->site['saddress'] . 'mod_user/panel?open_modal=cookie_policy');
            return false;
        }

        // Ensure cookies table exists and is seeded
        $this->ensureCookiesTable();

        $default_lang = Registry::get('default_lang');
        if (!is_string($default_lang) || $default_lang === '') {
            $default_lang = 'en';
        }
        $current_lang = !empty($this->site['lang']) ? $this->site['lang'] : (Registry::get('lang') ?: ($default_lang ?: 'en'));

        // Process non-AJAX POST for cookie policy general text if submitted
        if (Req::is_Post('cookie_policy_text_submit')) {
            $content = trim((string) Req::post('cookie_policy_content'));
            $safeContent = $this->db->quote($content);
            $safeLang = addslashes($current_lang);
            $this->db->query("UPDATE mod_settings SET `cookie_policy_content` = {$safeContent} WHERE `lang` = '{$safeLang}'");
            $this->_flash_success_and_redirect_cookie_policy($this->lang['mod_settings']['cookie_policy_text_save_success'] ?? 'Cookie policy text updated successfully.');
        }

        // Prepare the view
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_settings']['cookie_policy_title'] ?? 'Cookie Policy';
        $this->theme->set('site', $this->site);
        $this->theme->set('is_ajax', $this->_is_ajax_request());

        $this->_load_flash_messages();

        // Fetch settings for active language with fallback
        $settings_model = Load::model('mod_settings');
        $safeCurrentLang = addslashes($current_lang);
        $setting = null;
        $settings_model->where("lang = '{$safeCurrentLang}'")->gRo();
        if ($settings_model->getSize() > 0) {
            $settings_model->sync();
            $setting = $settings_model->getResultOne()->toArray();
        } else {
            $settings_model->where_lang($default_lang)->gRo();
            if ($settings_model->getSize() > 0) {
                $settings_model->sync();
                $setting = $settings_model->getResultOne()->toArray();
            } else {
                $settings_model->gRo();
                if ($settings_model->getSize() > 0) {
                    $settings_model->sync();
                    $setting = $settings_model->getResultOne()->toArray();
                }
            }
        }

        if (empty($setting['cookie_policy_content'])) {
            $setting['cookie_policy_content'] = $this->_get_default_cookie_policy_sample($current_lang);
        }
        $this->theme->set('setting', $setting);

        // Fetch dynamic cookies for current language (with fallback)
        $safeLang = addslashes($current_lang);
        $cookie_model = Load::model('mod_settings.mod_settings_cookies');
        $cookie_model->where("lang = '{$safeLang}'")->order('display_order', 'ASC')->gR();

        $cookies = [];
        if ($cookie_model->getSize() > 0) {
            $cookies = $cookie_model->toArray();
        } else {
            $cookie_model->where("lang = 'en'")->order('display_order', 'ASC')->gR();
            if ($cookie_model->getSize() > 0) {
                $cookies = $cookie_model->toArray();
            }
        }
        $this->theme->set('cookies', $cookies);
        $this->theme->set('current_lang', $current_lang);

        // Render template
        $this->theme->tpl('mod_settings/cookie_policy');
    }

    /**
     * AJAX endpoint to save cookie policy general disclosure text.
     */
    public function ajax_save_cookie_policy_content()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied', 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied',
            ]);
            return;
        }

        $content = trim((string) Req::post('cookie_policy_content'));
        $safeContent = $this->db->quote($content);
        $current_lang = !empty($this->site['lang']) ? $this->site['lang'] : (Registry::get('lang') ?: 'en');
        $safeLang = addslashes($current_lang);
        $this->db->query("UPDATE mod_settings SET `cookie_policy_content` = {$safeContent} WHERE `lang` = '{$safeLang}'");

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['cookie_policy_text_save_success'] ?? 'Cookie policy text updated successfully.',
        ]);
    }

    /**
     * AJAX endpoint to save (create/update) a single cookie item in mod_settings_cookies.
     */
    public function ajax_save_cookie_item()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied', 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied',
            ]);
            return;
        }

        $id = (int) Req::post('id');
        $cookie_name = trim((string) Req::post('cookie_name'));
        $category = trim((string) Req::post('category'));
        $provider = trim((string) Req::post('provider'));
        $purpose = trim((string) Req::post('purpose'));
        $duration = trim((string) Req::post('duration'));
        $display_order = (int) Req::post('display_order');
        $is_active = (Req::post('is_active') === 'n') ? 'n' : 'y';
        $langCode = trim((string) Req::post('lang'));
        if (empty($langCode)) {
            $langCode = !empty($this->site['lang']) ? $this->site['lang'] : (Registry::get('lang') ?: 'en');
        }

        $validCats = ['necessary', 'functional', 'analytics', 'marketing'];
        if (!in_array($category, $validCats, true)) {
            $category = 'necessary';
        }

        if (empty($cookie_name)) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['settings_required_fields'] ?? 'Cookie name is required.',
            ]);
            return;
        }

        $cookie_model = Load::model('mod_settings.mod_settings_cookies');
        if ($id > 0) {
            $cookie_model->where_id($id)->gRo();
            if ($cookie_model->getSize() > 0) {
                $cookie_model->sync();
                $cookie_item = $cookie_model->getResultOne();
            } else {
                $cookie_item = Load::model('mod_settings.mod_settings_cookies');
                $cookie_item->user_id = (int) $this->session->get('id');
            }
        } else {
            $cookie_item = Load::model('mod_settings.mod_settings_cookies');
            $cookie_item->user_id = (int) $this->session->get('id');
        }

        $cookie_item->lang = $langCode;
        $cookie_item->cookie_name = $cookie_name;
        $cookie_item->category = $category;
        $cookie_item->provider = $provider;
        $cookie_item->purpose = $purpose;
        $cookie_item->duration = $duration;
        $cookie_item->display_order = $display_order;
        $cookie_item->is_active = $is_active;

        if ($cookie_item->save()) {
            $this->_json_response([
                'status' => 'success',
                'message' => $this->lang['mod_settings']['cookie_save_success'] ?? 'Cookie item saved successfully.',
                'item' => [
                    'id' => (int) $cookie_item->id,
                    'lang' => (string) $cookie_item->lang,
                    'cookie_name' => (string) $cookie_item->cookie_name,
                    'category' => (string) $cookie_item->category,
                    'provider' => (string) $cookie_item->provider,
                    'purpose' => (string) $cookie_item->purpose,
                    'duration' => (string) $cookie_item->duration,
                    'display_order' => (int) $cookie_item->display_order,
                    'is_active' => (string) $cookie_item->is_active,
                ]
            ]);
        } else {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['cookie_save_error'] ?? 'An error occurred while saving the cookie.',
            ]);
        }
    }

    /**
     * AJAX endpoint to delete a cookie item.
     */
    public function ajax_delete_cookie_item()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied', 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied',
            ]);
            return;
        }

        $id = (int) Req::post('id');
        if ($id <= 0) {
            $this->_json_response([
                'status' => 'error',
                'message' => 'Invalid cookie ID.',
            ]);
            return;
        }

        $cookie_model = Load::model('mod_settings.mod_settings_cookies');
        $cookie_model->where_id($id)->gRo();
        if ($cookie_model->getSize() > 0) {
            $cookie_model->sync();
            $cookie_item = $cookie_model->getResultOne();
            $cookie_item->delete();
            $this->_json_response([
                'status' => 'success',
                'message' => $this->lang['mod_settings']['cookie_delete_success'] ?? 'Cookie deleted successfully.',
            ]);
        } else {
            $this->_json_response([
                'status' => 'error',
                'message' => 'Cookie record not found.',
            ]);
        }
    }

    /**
     * AJAX endpoint to toggle active status of a cookie item.
     */
    public function ajax_toggle_cookie_item()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied', 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied',
            ]);
            return;
        }

        $id = (int) Req::post('id');
        if ($id <= 0) {
            $this->_json_response(['status' => 'error', 'message' => 'Invalid ID']);
            return;
        }

        $cookie_model = Load::model('mod_settings.mod_settings_cookies');
        $cookie_model->where_id($id)->gRo();
        if ($cookie_model->getSize() > 0) {
            $cookie_model->sync();
            $cookie_item = $cookie_model->getResultOne();
            $newStatus = ($cookie_item->is_active === 'y') ? 'n' : 'y';
            $cookie_item->is_active = $newStatus;
            $cookie_item->save();

            $this->_json_response([
                'status' => 'success',
                'is_active' => $newStatus,
                'message' => $this->lang['mod_settings']['cookie_save_success'] ?? 'Cookie status updated successfully.',
            ]);
        } else {
            $this->_json_response(['status' => 'error', 'message' => 'Record not found']);
        }
    }

    /**
     * AJAX endpoint to save legal and tax settings.
     */
    public function ajax_save_legal_info()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied', 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied',
            ]);
            return;
        }

        $values = $this->_extract_legal_fields_from_post();
        $saved = $this->_save_legal_fields($values);

        if ($saved) {
            $this->_json_response([
                'status' => 'success',
                'message' => $this->lang['mod_settings']['legal_save_success'] ?? 'Legal information updated successfully.',
            ]);
        } else {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['legal_save_error'] ?? 'An error occurred while saving legal information.',
            ]);
        }
    }

    /**
     * Handles POST request for legal information globally across all system records.
     */
    private function _legal_info()
    {
        $values = $this->_extract_legal_fields_from_post();
        $saved = $this->_save_legal_fields($values);

        if ($saved) {
            $success_message = $this->lang['mod_settings']['legal_save_success'] ?? 'Legal information updated successfully.';
            if ($this->_is_ajax_request()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'success',
                    'message' => $success_message
                ]);
                exit;
            }
            $this->_flash_success_and_redirect_legal($success_message);
        } else {
            $error_message = $this->lang['mod_settings']['legal_save_error'] ?? 'An error occurred while saving legal information.';
            if ($this->_is_ajax_request()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'message' => $error_message
                ]);
                exit;
            }
            $this->_form_error($error_message);
        }
    }

    /**
     * Renders and manages the Google Business style working hours and holiday closures.
     */
    public function working_hours()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'] ?? 'Permission denied', 1) === false) {
            return false;
        }

        $this->ensureWorkingHoursTable();

        // Handle POST save working hours
        if (Req::is_Post('save_working_hours')) {
            $this->_save_working_hours();
            return true;
        }

        // Handle POST add closure
        if (Req::is_Post('add_closure')) {
            $this->_add_closure();
            return true;
        }

        // Handle POST/GET delete closure
        if (Req::get('action') === 'delete_closure' || Req::post('delete_closure_id')) {
            $this->_delete_closure();
            return true;
        }

        // Load 7 working days
        $this->db->fetch_array("SELECT * FROM `mod_settings_working_hour` ORDER BY day_of_week ASC");
        $workingHours = $this->db->toArray();

        // Load upcoming closures
        $this->db->fetch_array("SELECT * FROM `mod_settings_closure` ORDER BY start_date ASC");
        $closures = $this->db->toArray();

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_settings']['working_hours_title'] ?? 'Working Hours & Closures';
        $this->theme->set('site', $this->site);
        $this->theme->set('is_ajax', $this->_is_ajax_request());
        $this->theme->set('working_hours', $workingHours);
        $this->theme->set('closures', $closures);

        $this->_load_flash_messages();

        $this->theme->tpl('mod_settings/working_hours');
        return true;
    }

    /**
     * Handles saving working hours from POST.
     */
    private function _save_working_hours()
    {
        $daysPost = Req::post('days');
        if (is_array($daysPost)) {
            foreach ($daysPost as $dayNum => $dayData) {
                $dayNum = (int)$dayNum;
                if ($dayNum < 1 || $dayNum > 7) {
                    continue;
                }

                $isClosed = (isset($dayData['is_closed']) && ($dayData['is_closed'] === 'y' || $dayData['is_closed'] === '1')) ? 'y' : 'n';
                $openTime = !empty($dayData['open_time']) ? trim($dayData['open_time']) : '09:00:00';
                $closeTime = !empty($dayData['close_time']) ? trim($dayData['close_time']) : '18:00:00';

                if (strlen($openTime) === 5) $openTime .= ':00';
                if (strlen($closeTime) === 5) $closeTime .= ':00';

                $breakStart = !empty($dayData['break_start_time']) ? trim($dayData['break_start_time']) : null;
                if ($breakStart && strlen($breakStart) === 5) $breakStart .= ':00';

                $breakEnd = !empty($dayData['break_end_time']) ? trim($dayData['break_end_time']) : null;
                if ($breakEnd && strlen($breakEnd) === 5) $breakEnd .= ':00';

                $note = !empty($dayData['note']) ? trim($dayData['note']) : null;

                $updateData = [
                    'is_closed' => $isClosed,
                    'open_time' => $openTime,
                    'close_time' => $closeTime,
                    'break_start_time' => $breakStart,
                    'break_end_time' => $breakEnd,
                    'note' => $note
                ];

                $this->db->update('mod_settings_working_hour', $updateData, ['day_of_week' => $dayNum]);
            }

            $successMsg = $this->lang['mod_settings']['working_hours_saved_success'] ?? 'Working hours updated successfully.';
            if ($this->_is_ajax_request()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'success', 'message' => $successMsg]);
                exit;
            }
            $this->_flash_success_and_redirect_working_hours($successMsg);
        }
    }

    /**
     * Handles adding a special closure or holiday.
     */
    private function _add_closure()
    {
        $reason = trim((string)Req::post('closure_reason'));
        $startDate = trim((string)Req::post('start_date'));
        $endDate = trim((string)Req::post('end_date'));
        if ($endDate === '') $endDate = $startDate;
        $isFullDay = Req::post('is_full_day') === 'n' ? 'n' : 'y';
        $openTime = ($isFullDay === 'n' && Req::post('open_time')) ? trim(Req::post('open_time')) . ':00' : null;
        $closeTime = ($isFullDay === 'n' && Req::post('close_time')) ? trim(Req::post('close_time')) . ':00' : null;
        $closureType = trim((string)Req::post('closure_type')) ?: 'holiday';
        $isRecurring = Req::post('is_recurring_yearly') === 'y' ? 'y' : 'n';

        if ($reason !== '' && $startDate !== '') {
            $insertData = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_full_day' => $isFullDay,
                'open_time' => $openTime,
                'close_time' => $closeTime,
                'reason' => $reason,
                'closure_type' => $closureType,
                'is_recurring_yearly' => $isRecurring
            ];
            $this->db->db_insert('mod_settings_closure', $insertData);
            $newId = (int)$this->db->insert_id();
            $insertData['id'] = $newId;

            $successMsg = $this->lang['mod_settings']['closure_saved_success'] ?? 'Closure record saved successfully.';
            if ($this->_is_ajax_request()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'success',
                    'message' => $successMsg,
                    'closure' => $insertData
                ]);
                exit;
            }
            $this->_flash_success_and_redirect_working_hours($successMsg);
        } else {
            $errorMsg = $this->lang['mod_settings']['closure_saved_error'] ?? 'Please fill all required fields.';
            if ($this->_is_ajax_request()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => $errorMsg]);
                exit;
            }
            $this->_form_error($errorMsg);
        }
    }

    /**
     * Handles deleting a closure record.
     */
    private function _delete_closure()
    {
        $closureId = (int)(Req::post('delete_closure_id') ?: Req::get('id'));
        if ($closureId > 0) {
            $this->db->delete('mod_settings_closure', ['id' => $closureId]);
            $successMsg = $this->lang['mod_settings']['closure_deleted_success'] ?? 'Closure record deleted successfully.';
            if ($this->_is_ajax_request()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'success', 'message' => $successMsg, 'id' => $closureId]);
                exit;
            }
            $this->_flash_success_and_redirect_working_hours($successMsg);
        }
    }

    /**
     * Sets flash message and redirects back to working hours.
     */
    private function _flash_success_and_redirect_working_hours($message)
    {
        $this->_set_flash('success', $message);
        Helper::redirect($this->site['saddress'] . 'mod_settings/working_hours');
    }

    /**
     * Private method to handle the POST logic for updating settings.
     */
    private function _edit()
    {
        $selected_lang = Req::post('lang');
        if (empty($selected_lang)) {
            $this->_error_message($this->lang['mod_settings']['missing_language']);
            return;
        }

        $draft_payload = isset($_POST['draft_payload']) ? json_decode($_POST['draft_payload'], true) : [];
        if (!is_array($draft_payload)) {
            $draft_payload = [];
        }

        $draft_payload[$selected_lang] = $this->_extract_setting_fields_from_post();

        foreach ($draft_payload as $lang => $values) {
            $lang = Req::Url_Clear($lang);
            if (empty($lang) || !is_array($values)) {
                continue;
            }

            if ($this->_save_setting_fields($lang, $values) === false) {
                return;
            }
        }

        $settings_model = Load::model('mod_settings');
        $settings_model->where_lang($selected_lang)->gRo();
        if ($settings_model->getSize() > 0) {
            $settings_model->sync();
            $setting = $settings_model->getResultOne();
        } else {
            $setting = Load::model('mod_settings');
            $setting->lang = $selected_lang;
        }

        $run_generation = false;
        $source_path = null;

        // Handle cropped symbol base64 upload
        $cropped_symbol = Req::post('cropped_symbol');
        if (!empty($cropped_symbol) && strpos($cropped_symbol, 'data:image/png;base64,') === 0) {
            $data = base64_decode(substr($cropped_symbol, strlen('data:image/png;base64,')));
            $dest_dir = root_dir . 'static' . DS . 'mod_settings' . DS;
            if (!is_dir($dest_dir)) {
                mkdir($dest_dir, 0755, true);
            }
            $symbol_path = $dest_dir . 'logo_symbol.png';
            @unlink($symbol_path);
            file_put_contents($symbol_path, $data);

            $setting->logo_symbol = 'logo_symbol.png';
            $this->db->query("UPDATE mod_settings SET logo_symbol = 'logo_symbol.png'");
            $source_path = $symbol_path;
            $run_generation = true;

            try {
                $generator = Load::lib('BrandAssetGenerator');
                $generator->generateMultiSizeThumbnails($symbol_path);
            } catch (Throwable $e) {
            }
        }

        // Handle cropped square base64 upload
        $cropped_square = Req::post('cropped_square');
        if (!empty($cropped_square) && strpos($cropped_square, 'data:image/png;base64,') === 0) {
            $data = base64_decode(substr($cropped_square, strlen('data:image/png;base64,')));
            $dest_dir = root_dir . 'static' . DS . 'mod_settings' . DS;
            if (!is_dir($dest_dir)) {
                mkdir($dest_dir, 0755, true);
            }
            $square_path = $dest_dir . 'logo_square.png';
            @unlink($square_path);
            file_put_contents($square_path, $data);

            $setting->site_icon = 'logo_square.png';
            $this->db->query("UPDATE mod_settings SET site_icon = 'logo_square.png'");

            try {
                $generator = Load::lib('BrandAssetGenerator');
                $generator->generateMultiSizeThumbnails($square_path);
            } catch (Throwable $e) {
            }

        }

        // If logo_symbol already exists and run_generation is not set, regenerate manifest/assets on save if title/meta changes
        if (!$run_generation) {
            $dest_dir = root_dir . 'static' . DS . 'mod_settings' . DS;
            if (!empty($setting->logo_symbol) && file_exists($dest_dir . $setting->logo_symbol)) {
                $source_path = $dest_dir . $setting->logo_symbol;
                $run_generation = true;
            }
        }

        if ($run_generation && !empty($source_path)) {
            try {
                $generator = Load::lib('BrandAssetGenerator');
                $generated_fields = $generator->generateFromLocalFile(
                    $source_path,
                    [
                        'title' => isset($setting->title) ? $setting->title : '',
                        'description' => isset($setting->description) ? $setting->description : '',
                    ],
                    $this->_current_asset_files($setting)
                );
                foreach ($generated_fields as $field => $file_name) {
                    $setting->$field = $file_name;
                }
            } catch (Exception $e) {
                $this->_form_error($this->_resolve_asset_generator_error($e->getMessage()));
                return;
            }
        }

        // Handle cropped rect base64 upload
        $cropped_rect = Req::post('cropped_rect');
        if (!empty($cropped_rect) && strpos($cropped_rect, 'data:image/png;base64,') === 0) {
            $data = base64_decode(substr($cropped_rect, strlen('data:image/png;base64,')));
            $dest_dir = root_dir . 'static' . DS . 'mod_settings' . DS;
            if (!is_dir($dest_dir)) {
                mkdir($dest_dir, 0755, true);
            }
            $rect_path = $dest_dir . 'logo.png';
            @unlink($rect_path);
            file_put_contents($rect_path, $data);

            $setting->logo_path = 'logo.png';
            $this->db->query("UPDATE mod_settings SET logo_path = 'logo.png'");

            try {
                $generator = Load::lib('BrandAssetGenerator');
                $generator->generateRectThumbnail($rect_path);
            } catch (Throwable $e) {
            }
        }

        if ($this->_has_uploaded_file('logo_' . $selected_lang)) {
            try {
                $this->_generate_assets_for_setting($selected_lang, $setting);
            } catch (Exception $e) {
                $this->_form_error($this->_resolve_asset_generator_error($e->getMessage()));
                return;
            }
        }

        $save_result = $settings_model->getSize() > 0 ? $setting->update('lang') : $setting->save();

        if ($save_result) {
            $this->_flash_success_and_redirect($selected_lang, $this->lang['mod_settings']['messages']['success']['updated']);
        } else {
            $this->_form_error($this->lang['mod_settings']['save_error'] . ': ' . $settings_model->db->sql_error());
        }
    }

    public function ajax_save_stats()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $workforce = intval(Req::post('stat_workforce'));
        $offices = intval(Req::post('stat_offices'));

        $sql = "UPDATE mod_settings SET
            stat_workforce = {$workforce},
            stat_offices = {$offices}";

        $result = $this->db->query($sql);

        if ($result) {
            $this->_json_response([
                'status' => 'success',
                'message' => $this->lang['mod_settings']['messages']['success']['updated'] ?? 'Settings updated successfully.',
                'stats' => [
                    'stat_workforce' => $workforce,
                    'stat_offices' => $offices,
                ]
            ]);
        } else {
            $this->_json_response([
                'status' => 'error',
                'message' => 'Failed to save statistics.'
            ]);
        }
    }

    public function ajax_get_themes()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $activeTheme = Registry::get('app_template');
        if (empty($activeTheme)) {
            $activeTheme = $this->site['app_template'] ?? 'amida';
        }

        $themesDir = template_dir;
        $availableThemes = [];

        if (is_dir($themesDir)) {
            $items = scandir($themesDir);
            if (is_array($items)) {
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..' || $item === 'admin' || $item === 'partials' || $item === 'base_static') {
                        continue;
                    }
                    $fullPath = $themesDir . $item;
                    if (is_dir($fullPath)) {
                        $name = ucfirst(str_replace('_', ' ', $item));
                        $availableThemes[] = [
                            'id' => $item,
                            'name' => $name,
                            'is_active' => ($item === $activeTheme),
                        ];
                    }
                }
            }
        }

        $this->_json_response([
            'status' => 'success',
            'active_theme' => $activeTheme,
            'themes' => $availableThemes,
        ]);
    }

    public function ajax_save_theme()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $theme = Req::post('app_template');
        if (empty($theme)) {
            $this->_json_response([
                'status' => 'error',
                'message' => 'Template name cannot be empty.',
            ]);
            return;
        }

        $safeTheme = basename(Req::Clear($theme));
        $fullPath = template_dir . $safeTheme;

        if (!file_exists($fullPath) || !is_dir($fullPath) || $safeTheme === 'admin' || $safeTheme === 'base_static') {
            $this->_json_response([
                'status' => 'error',
                'message' => 'Invalid template selected.',
            ]);
            return;
        }

        $this->ensureAppTemplateColumn();

        $safeThemeEscaped = addslashes($safeTheme);
        $result = $this->db->query("UPDATE mod_settings SET app_template = '{$safeThemeEscaped}'");

        if ($result) {
            Registry::set('app_template', $safeTheme);
            $this->_json_response([
                'status' => 'success',
                'message' => $this->lang['mod_settings']['theme_save_success'] ?? 'Theme updated successfully.',
                'app_template' => $safeTheme,
            ]);
        } else {
            $this->_json_response([
                'status' => 'error',
                'message' => 'Failed to save theme in database.',
            ]);
        }
    }

    private function _extract_setting_fields_from_post()
    {
        return [
            'title' => trim(Req::Post('title')),
            'site_name' => trim(Req::Post('site_name')),
            'description' => trim(Req::Post('description')),
            'owner_name' => trim(Req::Post('owner_name')),
            'established_year' => trim(Req::Post('established_year')),
            'phone' => trim(Req::Post('phone')),
            'whatsapp' => trim(Req::Post('whatsapp')),
            'email' => trim(Req::Post('email')),
            'office_address' => trim(Req::Post('office_address')),
            'work_address' => trim(Req::Post('work_address')),
            'map_embed_code' => trim(Req::Post('map_embed_code')),
            'google_search_console' => trim(Req::Post('google_search_console')),
            'google_analytics' => trim(Req::Post('google_analytics')),
            'meta_tags' => trim(Req::Post('meta_tags')),
            'show_logo' => Req::Post('show_logo'),
            'logo_type' => Req::Post('logo_type'),
            'stat_workforce' => intval(Req::Post('stat_workforce')),
            'stat_offices' => intval(Req::Post('stat_offices')),
            'cookie_policy_content' => Req::Post('cookie_policy_content'),
        ];
    }

    private function _normalize_setting_fields(array $values)
    {
        $normalized = [
            'title' => trim((string) ($values['title'] ?? '')),
            'site_name' => trim((string) ($values['site_name'] ?? '')),
            'description' => trim((string) ($values['description'] ?? '')),
            'owner_name' => trim((string) ($values['owner_name'] ?? '')),
            'established_year' => trim((string) ($values['established_year'] ?? '')),
            'phone' => trim((string) ($values['phone'] ?? '')),
            'whatsapp' => trim((string) ($values['whatsapp'] ?? '')),
            'email' => trim((string) ($values['email'] ?? '')),
            'office_address' => trim((string) ($values['office_address'] ?? '')),
            'work_address' => trim((string) ($values['work_address'] ?? '')),
            'map_embed_code' => trim((string) ($values['map_embed_code'] ?? '')),
            'google_search_console' => trim((string) ($values['google_search_console'] ?? '')),
            'google_analytics' => trim((string) ($values['google_analytics'] ?? '')),
            'meta_tags' => trim((string) ($values['meta_tags'] ?? '')),
            'stat_workforce' => intval($values['stat_workforce'] ?? 1200),
            'stat_offices' => intval($values['stat_offices'] ?? 18),
            'cookie_policy_content' => trim((string) ($values['cookie_policy_content'] ?? '')),
        ];

        if (array_key_exists('show_logo', $values) && $values['show_logo'] !== null) {
            $normalized['show_logo'] = ($values['show_logo'] == 'y') ? 'y' : 'n';
        }

        if (array_key_exists('logo_type', $values) && $values['logo_type'] !== null && $values['logo_type'] !== '') {
            $valid_types = ['square', 'rectangle', 'square_name', 'rectangle_name', 'name_only'];
            $logo_type = trim((string) $values['logo_type']);
            if (in_array($logo_type, $valid_types, true)) {
                $normalized['logo_type'] = $logo_type;
            }
        }

        return $normalized;
    }

    private function _save_setting_fields($lang, array $values)
    {
        $settings_model = Load::model('mod_settings');
        $settings_model->where_lang($lang)->gRo();
        $normalized_values = $this->_normalize_setting_fields($values);

        if ($settings_model->getSize() > 0) {
            $settings_model->sync();
            $setting = $settings_model->getResultOne();

            $has_changes = false;
            foreach ($normalized_values as $field => $value) {
                if (trim((string) $setting->$field) !== $value) {
                    $has_changes = true;
                    break;
                }
            }

            if (!$has_changes) {
                return true;
            }
        } else {
            $has_values = false;
            foreach ($normalized_values as $value) {
                if ($value !== '') {
                    $has_values = true;
                    break;
                }
            }

            if (!$has_values) {
                return true;
            }

            $setting = Load::model('mod_settings');
            $setting->lang = $lang;
        }

        foreach ($normalized_values as $field => $value) {
            $setting->$field = $value;
        }

        $save_result = $settings_model->getSize() > 0 ? $setting->update('lang') : $setting->save();
        if ($save_result) {
            // Global synchronization: show_logo and logo_type apply to all languages
            if (isset($normalized_values['show_logo']) || isset($normalized_values['logo_type'])) {
                $global_fields = [];
                if (isset($normalized_values['show_logo'])) {
                    $global_fields['show_logo'] = $normalized_values['show_logo'];
                }
                if (isset($normalized_values['logo_type'])) {
                    $global_fields['logo_type'] = $normalized_values['logo_type'];
                }
                $this->_sync_asset_fields($global_fields);
            }

            return true;
        }

        $this->_form_error($this->lang['mod_settings']['save_error'] . ': ' . $settings_model->db->sql_error());
        return false;
    }

    /**
     * Extracts legal fields from POST payload.
     */
    private function _extract_legal_fields_from_post()
    {
        return [
            'country' => Req::Post('country'),
            'is_online_retail' => Req::Post('is_online_retail') === 'y' ? 'y' : 'n',
            'is_local_store' => Req::Post('is_local_store') === 'y' ? 'y' : 'n',
            'is_service_business' => Req::Post('is_service_business') === 'y' ? 'y' : 'n',
            'legal_company_name' => Req::Post('legal_company_name'),
            'legal_trading_name' => Req::Post('legal_trading_name'),
            'company_type' => Req::Post('company_type'),
            'company_registration_number' => Req::Post('company_registration_number'),
            'trade_registry_number' => Req::Post('trade_registry_number'),
            'central_registration_number' => Req::Post('central_registration_number'),
            'chamber_of_commerce' => Req::Post('chamber_of_commerce'),
            'corporate_address' => Req::Post('corporate_address'),
            'contact_address' => Req::Post('contact_address'),
            'tax_office' => Req::Post('tax_office'),
            'tax_number' => Req::Post('tax_number'),
            'vat_number' => Req::Post('vat_number'),
            'authorized_person' => Req::Post('authorized_person'),
            'registered_email' => Req::Post('registered_email'),
            'legal_contact_phone' => Req::Post('legal_contact_phone'),
            'managing_director' => Req::Post('managing_director'),
            'editor_in_chief' => Req::Post('editor_in_chief'),
            'press_code_license' => Req::Post('press_code_license'),
            'dpo_email' => Req::Post('dpo_email'),
            'hosting_provider' => Req::Post('hosting_provider'),
            'compliance_jurisdiction' => Req::Post('compliance_jurisdiction'),
            'default_warranty_duration' => Req::Post('default_warranty_duration'),
            'warranty_type' => Req::Post('warranty_type'),
            'return_policy_days' => Req::Post('return_policy_days'),
            'warranty_description' => Req::Post('warranty_description'),
            'cookie_policy_content' => Req::Post('cookie_policy_content'),
        ];
    }

    /**
     * Normalizes and sanitizes legal fields.
     */
    private function _normalize_legal_fields(array $values)
    {
        return [
            'country' => trim((string) ($values['country'] ?? '')),
            'is_online_retail' => (($values['is_online_retail'] ?? 'n') === 'y') ? 'y' : 'n',
            'is_local_store' => (($values['is_local_store'] ?? 'y') === 'y') ? 'y' : 'n',
            'is_service_business' => (($values['is_service_business'] ?? 'n') === 'y') ? 'y' : 'n',
            'legal_company_name' => trim((string) ($values['legal_company_name'] ?? '')),
            'legal_trading_name' => trim((string) ($values['legal_trading_name'] ?? '')),
            'company_type' => trim((string) ($values['company_type'] ?? '')),
            'company_registration_number' => trim((string) ($values['company_registration_number'] ?? '')),
            'trade_registry_number' => trim((string) ($values['trade_registry_number'] ?? '')),
            'central_registration_number' => trim((string) ($values['central_registration_number'] ?? '')),
            'chamber_of_commerce' => trim((string) ($values['chamber_of_commerce'] ?? '')),
            'corporate_address' => trim((string) ($values['corporate_address'] ?? '')),
            'contact_address' => trim((string) ($values['contact_address'] ?? '')),
            'tax_office' => trim((string) ($values['tax_office'] ?? '')),
            'tax_number' => trim((string) ($values['tax_number'] ?? '')),
            'vat_number' => trim((string) ($values['vat_number'] ?? '')),
            'authorized_person' => trim((string) ($values['authorized_person'] ?? '')),
            'registered_email' => trim((string) ($values['registered_email'] ?? '')),
            'legal_contact_phone' => trim((string) ($values['legal_contact_phone'] ?? '')),
            'managing_director' => trim((string) ($values['managing_director'] ?? '')),
            'editor_in_chief' => trim((string) ($values['editor_in_chief'] ?? '')),
            'press_code_license' => trim((string) ($values['press_code_license'] ?? '')),
            'dpo_email' => trim((string) ($values['dpo_email'] ?? '')),
            'hosting_provider' => trim((string) ($values['hosting_provider'] ?? '')),
            'compliance_jurisdiction' => trim((string) ($values['compliance_jurisdiction'] ?? '')),
            'default_warranty_duration' => trim((string) ($values['default_warranty_duration'] ?? '')),
            'warranty_type' => trim((string) ($values['warranty_type'] ?? '')),
            'return_policy_days' => trim((string) ($values['return_policy_days'] ?? '')),
            'warranty_description' => trim((string) ($values['warranty_description'] ?? '')),
        ];
    }

    /**
     * Returns default initial sample cookie policy content per language.
     *
     * @param string $langCode
     * @return string
     */
    private function _get_default_cookie_policy_sample($langCode)
    {
        $samples = [
            'ku' => "Ev malper kulîçeyan (cookies) û teknolojiyên wekhev bi kar tîne da ku ezmûna bikaranînê ya ewle û hêsan pêşkêş bike, performansa rûpelan bipîve û xizmetên guncaw pêşkêş bike. Bi rêya vê rûpelê hûn dikarin hûrguliyên kulîçeyan bibînin û her dem tercîhên xwe li gorî daxwaza xwe birêve bibin.",
            'en' => "This website uses cookies and related technologies to provide an optimal browsing experience, measure performance, and ensure secure digital services. You can review cookie disclosures and manage your consent preferences at any time.",
            'tr' => "Bu web sitesi; güvenli ve kesintisiz bir kullanıcı deneyimi sunmak, site performansını analiz etmek ve hizmetlerimizi geliştirmek amacıyla çerezleri (cookies) kullanmaktadır. Çerez politikamızı inceleyebilir ve tercihlerinizi dilediğiniz zaman güncelleyebilirsiniz.",
            'de' => "Diese Website verwendet Cookies und ähnliche Technologien, um ein optimales Nutzererlebnis zu gewährleisten, die Leistung zu analysieren und sichere Dienste bereitzustellen. Sie können Ihre Einstellungen jederzeit anpassen.",
            'fr' => "Ce site Web utilise des cookies et des technologies similaires pour garantir une navigation optimale, analyser l'audience et sécuriser nos services. Vous pouvez modifier vos préférences à tout moment.",
            'ar' => "يستخدم هذا الموقع ملفات تعريف الارتباط (الكوكيز) لتقديم أفضل تجربة تصفح وضمان أمان الخدمات وتحليل أداء المنصة. يمكنك مراجعة وتعديل تفضيلاتك في أي وقت.",
            'ckb' => "ئەم ماڵپەڕە کووکی و تەکنەلۆژیا هاوشێوەکان بەکاردەهێنێت بۆ پێشکەشکردنی ئەزموونێکی بەکارهێنانی پارێزراو و پێوانەکردنی کارایی. دەتوانیت لە هەر کاتێکدا بێت هەڵبژاردنەکانت بگۆڕیت.",
            'sv' => "Denna webbplats använder cookies och liknande tekniker för att säkerställa en optimal användarupplevelse, analysera prestanda och tillhandahålla säkra tjänster. Du kan när som helst hantera dina preferenser.",
            'zza' => "Na keyepela semedê averberdışê tecribeyê şuxulnayoxan, analîzê performansi u xizmetanê ewleyan çerezan (cookies) şuxulnena. Şıma şenê tercihanê xo her wext bivurnê.",
            'ru' => "Этот веб-сайт использует файлы cookie и схожие технологии для обеспечения удобного и безопасного взаимодействия с пользователем, анализа посещаемости и предоставления актуальных сервисов. Вы можете ознакомиться с политикой использования файлов cookie и в любое время настроить свои предпочтения.",
        ];

        return $samples[$langCode] ?? $samples['en'];
    }

    /**
     * Saves legal information fields globally across all system records.
     */
    private function _save_legal_fields(array $values)
    {
        $normalized_values = $this->_normalize_legal_fields($values);
        $settings_model = Load::model('mod_settings');
        $settings_model->gR();

        if ($settings_model->getSize() > 0) {
            $set_clauses = [];
            foreach ($normalized_values as $field => $value) {
                $safe_value = $this->db->quote($value);
                $set_clauses[] = "`" . addslashes($field) . "` = {$safe_value}";
            }
            $sql = "UPDATE mod_settings SET " . implode(', ', $set_clauses);
            $this->db->query($sql);
            return true;
        } else {
            $default_lang = Registry::get('default_lang');
            if (!is_string($default_lang) || $default_lang === '') {
                $default_lang = 'en';
            }
            $settings_model->lang = $default_lang;
            $settings_model->user_id = (int) $this->session->get('id');
            foreach ($normalized_values as $field => $value) {
                $settings_model->$field = $value;
            }
            return (bool) $settings_model->save();
        }
    }

    /**
     * Sets flash message and redirects to legal_info page.
     */
    private function _flash_success_and_redirect_legal($message)
    {
        if ($this->session->has('mod_settings_flash_success')) {
            $this->session->remove('mod_settings_flash_success');
        }

        if (!empty($message)) {
            $this->session->set('mod_settings_flash_success', $message);
        }

        header('Location: ' . $this->site['saddress'] . 'mod_settings/legal_info');
        exit;
    }

    /**
     * Sets flash message and redirects to cookie_policy page.
     */
    private function _flash_success_and_redirect_cookie_policy($message)
    {
        if ($this->session->has('mod_settings_flash_success')) {
            $this->session->remove('mod_settings_flash_success');
        }

        if (!empty($message)) {
            $this->session->set('mod_settings_flash_success', $message);
        }

        header('Location: ' . $this->site['saddress'] . 'mod_settings/cookie_policy');
        exit;
    }

    public function ajax_toggle_logo()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $show_logo = Req::post('show_logo');

        if (!in_array($show_logo, ['y', 'n'])) {
            $show_logo = 'n';
        }

        $this->db->query("UPDATE mod_settings SET show_logo = '" . $show_logo . "'");

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['messages']['success']['updated'] ?? 'Setting has been updated',
        ]);
    }

    public function ajax_get_logo_icon_data()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $default_lang = Registry::get('default_lang');
        if (!is_string($default_lang) || $default_lang === '') {
            $default_lang = 'en';
        }
        $selected_lang = Req::post('lang') ?: ($this->site['lang'] ?? $default_lang);

        $settings_model = Load::model('mod_settings');
        $settings_model->where_lang($selected_lang)->gRo();
        if ($settings_model->getSize() > 0) {
            $settings_model->sync();
            $setting = $settings_model->getResultOne();
        } else {
            $fallback_model = Load::model('mod_settings');
            $fallback_model->gRo();
            if ($fallback_model->getSize() > 0) {
                $fallback_model->sync();
                $setting = $fallback_model->getResultOne();
            } else {
                $setting = (object)[];
            }
        }

        try {
            $generator = Load::lib('BrandAssetGenerator');
            $generator->ensureThumbnailsExist();
        } catch (Throwable $e) {
        }

        $verify_asset_file = function ($filename) {
            if (empty($filename)) {
                return '';
            }
            $file_path = root_dir . 'static' . DS . 'mod_settings' . DS . $filename;
            return (file_exists($file_path) && filesize($file_path) > 5) ? $filename : '';
        };

        $this->_json_response([
            'status' => 'success',
            'data' => [
                'logo_type' => $setting->logo_type ?? 'rectangle',
                'show_logo' => $setting->show_logo ?? 'y',
                'logo_path' => $verify_asset_file($setting->logo_path ?? ''),
                'site_icon' => $verify_asset_file($setting->site_icon ?? ''),
                'logo_symbol' => $verify_asset_file($setting->logo_symbol ?? ''),
                'favicon_path' => $verify_asset_file($setting->favicon_path ?? ''),
                'favicon_96' => $verify_asset_file($setting->favicon_96 ?? ''),
                'apple_touch_icon' => $verify_asset_file($setting->apple_touch_icon ?? ''),
                'manifest_192' => $verify_asset_file($setting->manifest_192 ?? ''),
                'manifest_512' => $verify_asset_file($setting->manifest_512 ?? ''),
                'site_webmanifest' => $verify_asset_file($setting->site_webmanifest ?? ''),
                'use_animated_logo' => $setting->use_animated_logo ?? 'n',
                'animated_logo_path' => $verify_asset_file($setting->animated_logo_path ?? ''),
                'animated_logo_type' => $setting->animated_logo_type ?? 'webm',
                'animated_logo_height' => intval($setting->animated_logo_height ?? 48),
                'animated_logo_aspect' => $setting->animated_logo_aspect ?? '16/9',
                'animated_logo_mode' => $setting->animated_logo_mode ?? 'loop',
                'animated_logo_blend' => (!empty($setting->animated_logo_blend) && $setting->animated_logo_blend !== 'none') ? $setting->animated_logo_blend : ($setting->animated_logo_blend ?? 'multiply'),
            ]
        ]);
    }

    public function ajax_save_header_style()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $logo_type = Req::post('logo_type');
        $valid_types = ['square', 'rectangle', 'square_name', 'rectangle_name', 'name_only'];
        if (!in_array($logo_type, $valid_types, true)) {
            $logo_type = 'rectangle';
        }

        $this->db->query("UPDATE mod_settings SET logo_type = '" . addslashes($logo_type) . "', show_logo = 'y'");

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['messages']['success']['updated'] ?? 'Setting has been updated',
            'logo_type' => $logo_type,
        ]);
    }

    public function upload_animated_logo_ajax()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        if (empty($_FILES['animated_logo_file']['name']) || !isset($_FILES['animated_logo_file']['error']) || $_FILES['animated_logo_file']['error'] !== UPLOAD_ERR_OK) {
            $err_code = $_FILES['animated_logo_file']['error'] ?? -1;
            $msg = ($err_code === UPLOAD_ERR_INI_SIZE || $err_code === UPLOAD_ERR_FORM_SIZE)
                ? ($this->lang['mod_settings']['file_too_large'] ?? 'File size is too large.')
                : ($this->lang['mod_settings']['invalid_params'] ?? 'Invalid file upload.');
            $this->_json_response([
                'status' => 'error',
                'message' => $msg,
            ]);
            return;
        }

        $file_name = $_FILES['animated_logo_file']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['webm', 'mp4', 'json', 'gif', 'apng', 'svg', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            $this->_json_response([
                'status' => 'error',
                'message' => sprintf($this->lang['mod_settings']['invalid_extension'] ?? 'Invalid extension. Allowed: %s', implode(', ', $allowed)),
            ]);
            return;
        }

        $dest_dir = root_dir . 'static' . DS . 'mod_settings' . DS;
        if (!is_dir($dest_dir)) {
            @mkdir($dest_dir, 0755, true);
        }

        // Clean up previous animated logo files
        $old_files = glob($dest_dir . 'animated_logo.*');
        if (is_array($old_files)) {
            foreach ($old_files as $of) {
                @unlink($of);
            }
        }

        $target_file = 'animated_logo.' . $ext;
        $target_path = $dest_dir . $target_file;
        $tmp_path = $_FILES['animated_logo_file']['tmp_name'];

        $moved = false;
        if (is_uploaded_file($tmp_path)) {
            $moved = @move_uploaded_file($tmp_path, $target_path);
        }
        if (!$moved) {
            $moved = @copy($tmp_path, $target_path);
        }
        if (!$moved && file_exists($tmp_path)) {
            $content = @file_get_contents($tmp_path);
            if ($content !== false && $content !== '') {
                $moved = (@file_put_contents($target_path, $content) !== false);
            }
        }

        if (!$moved) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['delete_fail'] ?? 'File could not be saved.',
            ]);
            return;
        }

        // Process image files to auto-convert white background pixels into alpha transparency
        $this->_process_uploaded_image_transparency($target_path, $ext);

        $safe_target = addslashes($target_file);
        $safe_ext = addslashes($ext);
        $this->db->query("UPDATE mod_settings SET animated_logo_path = '{$safe_target}', animated_logo_type = '{$safe_ext}', use_animated_logo = 'y', animated_logo_blend = IF(animated_logo_blend IS NULL OR animated_logo_blend = '' OR animated_logo_blend = 'none', 'multiply', animated_logo_blend)");

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['messages']['success']['updated'] ?? 'Animated logo uploaded successfully.',
            'animated_logo_path' => $target_file,
            'animated_logo_type' => $ext,
            'use_animated_logo' => 'y',
        ]);
    }

    private function _process_uploaded_image_transparency($target_path, $ext)
    {
        $ext = strtolower($ext);
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            return;
        }
        if (!function_exists('imagecreatefromstring')) {
            return;
        }
        $data = @file_get_contents($target_path);
        if (!$data) return;

        $img = @imagecreatefromstring($data);
        if (!$img) return;

        $width = imagesx($img);
        $height = imagesy($img);

        $newImg = imagecreatetruecolor($width, $height);
        imagealphablending($newImg, false);
        imagesavealpha($newImg, true);

        $transparent = imagecolorallocatealpha($newImg, 0, 0, 0, 127);
        imagefill($newImg, 0, 0, $transparent);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgba = imagecolorat($img, $x, $y);
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                $a = ($rgba >> 24) & 0x7F;

                if ($r > 235 && $g > 235 && $b > 235) {
                    imagesetpixel($newImg, $x, $y, $transparent);
                } else {
                    $color = imagecolorallocatealpha($newImg, $r, $g, $b, $a);
                    imagesetpixel($newImg, $x, $y, $color);
                }
            }
        }

        if ($ext === 'webp' && function_exists('imagewebp')) {
            @imagewebp($newImg, $target_path, 95);
        } else if (function_exists('imagepng')) {
            @imagepng($newImg, $target_path, 9);
        }
        unset($img, $newImg);
    }

    public function ajax_save_animated_logo_settings()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $use_animated = Req::post('use_animated_logo') === 'y' ? 'y' : 'n';
        $height = max(24, min(120, intval(Req::post('animated_logo_height') ?: 48)));
        $aspect = Req::post('animated_logo_aspect') ?: '16/9';
        $valid_aspects = ['16/9', '3/1', '1/1', 'auto'];
        if (!in_array($aspect, $valid_aspects, true)) {
            $aspect = '16/9';
        }
        $mode = Req::post('animated_logo_mode') ?: 'loop';
        $valid_modes = ['loop', 'once', 'hover'];
        if (!in_array($mode, $valid_modes, true)) {
            $mode = 'loop';
        }
        $blend = Req::post('animated_logo_blend') ?: 'multiply';
        $valid_blends = ['none', 'multiply', 'screen'];
        if (!in_array($blend, $valid_blends, true)) {
            $blend = 'multiply';
        }

        $sql = "UPDATE mod_settings SET
            use_animated_logo = '" . addslashes($use_animated) . "',
            animated_logo_height = " . intval($height) . ",
            animated_logo_aspect = '" . addslashes($aspect) . "',
            animated_logo_mode = '" . addslashes($mode) . "',
            animated_logo_blend = '" . addslashes($blend) . "'";
        $this->db->query($sql);

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['messages']['success']['updated'] ?? 'Settings updated.',
            'use_animated_logo' => $use_animated,
            'animated_logo_height' => $height,
            'animated_logo_aspect' => $aspect,
            'animated_logo_mode' => $mode,
            'animated_logo_blend' => $blend,
        ]);
    }

    public function remove_animated_logo_ajax()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $dest_dir = root_dir . 'static' . DS . 'mod_settings' . DS;
        $files = glob($dest_dir . 'animated_logo.*');
        if (is_array($files)) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }

        $this->db->query("UPDATE mod_settings SET animated_logo_path = '', use_animated_logo = 'n'");

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['delete_success'] ?? 'Animated logo removed.',
        ]);
    }

    public function upload_cropped_logo_ajax()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $selected_lang = Req::post('lang');
        $step = intval(Req::post('step'));
        $image_data = Req::post('image');

        if (empty($selected_lang) || empty($image_data) || strpos($image_data, 'data:image/png;base64,') !== 0) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['invalid_params'] ?? 'Invalid parameters.',
            ]);
            return;
        }

        $settings_model = Load::model('mod_settings');
        $settings_model->where_lang($selected_lang)->gRo();
        if ($settings_model->getSize() > 0) {
            $settings_model->sync();
            $setting = $settings_model->getResultOne();
        } else {
            $setting = Load::model('mod_settings');
            $setting->lang = $selected_lang;
        }

        $data = base64_decode(substr($image_data, strlen('data:image/png;base64,')));
        $dest_dir = root_dir . 'static' . DS . 'mod_settings' . DS;
        if (!is_dir($dest_dir)) {
            mkdir($dest_dir, 0755, true);
        }

        $run_generation = false;
        $source_path = null;

        if ($step === 3) {
            // Favicon / Symbol Logo
            $symbol_path = $dest_dir . 'logo_symbol.png';
            if (file_exists($symbol_path)) {
                @unlink($symbol_path);
            }
            file_put_contents($symbol_path, $data);
            $setting->logo_symbol = 'logo_symbol.png';
            $this->db->query("UPDATE mod_settings SET logo_symbol = 'logo_symbol.png'");
            $source_path = $symbol_path;
            $run_generation = true;
        } elseif ($step === 1) {
            // Square Logo (site_icon)
            $square_path = $dest_dir . 'logo_square.png';
            if (file_exists($square_path)) {
                @unlink($square_path);
            }
            file_put_contents($square_path, $data);
            $setting->site_icon = 'logo_square.png';
            $this->db->query("UPDATE mod_settings SET site_icon = 'logo_square.png'");
            $run_generation = false;

            try {
                $generator = Load::lib('BrandAssetGenerator');
                $generator->generateMultiSizeThumbnails($square_path);
            } catch (Throwable $e) {
            }
        } elseif ($step === 2) {
            // Rectangle Logo (logo_path)
            $rect_path = $dest_dir . 'logo.png';
            if (file_exists($rect_path)) {
                @unlink($rect_path);
            }
            file_put_contents($rect_path, $data);
            $setting->logo_path = 'logo.png';
            $this->db->query("UPDATE mod_settings SET logo_path = 'logo.png'");
            $run_generation = false;

            try {
                $generator = Load::lib('BrandAssetGenerator');
                $generator->generateRectThumbnail($rect_path);
                $generator->ensureManifestExists([
                    'title' => isset($setting->title) ? $setting->title : '',
                    'name' => isset($setting->site_name) ? $setting->site_name : '',
                    'description' => isset($setting->description) ? $setting->description : '',
                ]);
            } catch (Throwable $e) {
            }
        }

        $generated_fields = [];
        if ($run_generation && !empty($source_path)) {
            try {
                $generator = Load::lib('BrandAssetGenerator');
                $generated_fields = $generator->generateFromLocalFile(
                    $source_path,
                    [
                        'title' => isset($setting->title) ? $setting->title : '',
                        'description' => isset($setting->description) ? $setting->description : '',
                    ],
                    $this->_current_asset_files($setting)
                );
                foreach ($generated_fields as $field => $file_name) {
                    $setting->$field = $file_name;
                    $this->db->query("UPDATE mod_settings SET {$field} = '" . addslashes($file_name) . "'");
                }
            } catch (Exception $e) {
                $this->_json_response([
                    'status' => 'error',
                    'message' => $this->_resolve_asset_generator_error($e->getMessage()),
                ]);
                return;
            }
        }

        if ($settings_model->getSize() > 0) {
            @$setting->update('lang');
        } else {
            @$setting->save();
        }

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['messages']['success']['updated'] ?? 'Setting has been updated.',
            'fields' => $generated_fields,
            'logo_path' => $setting->logo_path,
            'logo_symbol' => $setting->logo_symbol,
            'site_icon' => $setting->site_icon,
        ]);
    }

    public function generate_assets_ajax()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $selected_lang = Req::post('lang');
        if (empty($selected_lang)) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['missing_language'],
            ]);
            return;
        }

        $settings_model = Load::model('mod_settings');
        $settings_model->where_lang($selected_lang)->gRo();
        if ($settings_model->getSize() > 0) {
            $settings_model->sync();
            $setting = $settings_model->getResultOne();
        } else {
            $setting = Load::model('mod_settings');
            $setting->lang = $selected_lang;
        }

        $setting->title = trim(Req::Post('title'));
        $setting->description = trim(Req::Post('description'));

        try {
            $generated_fields = $this->_generate_assets_for_setting($selected_lang, $setting);
        } catch (Exception $e) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->_resolve_asset_generator_error($e->getMessage()),
            ]);
            return;
        }

        $save_result = $settings_model->getSize() > 0 ? $setting->update('lang') : $setting->save();

        if (!$save_result) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['save_error'],
            ]);
            return;
        }

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['messages']['success']['assets_generated'],
            'fields' => $generated_fields,
        ]);
    }

    /**
     * Helper to handle image uploads to avoid code duplication
     */
    private function _handle_image_upload($lang, $field, &$setting, $options)
    {
        $input_name = $field . '_' . $lang;

        if (isset($_FILES[$input_name]['name']) && !empty($_FILES[$input_name]['name'])) {
            // Delete old file
            if (!empty($setting->$field)) {
                $image_del = Load::mod('image');
                $image_del->mod = 'mod_settings';
                $image_del->url = $setting->$field;
                $image_del->_delete_files(['/' => []]);
            }

            $image = Load::mod('image');
            $image->name = $input_name;
            $image->mod = 'mod_settings';
            $image->custom_name = $field; // e.g. favicon_96 -> favicon_96.png

            try {
                if ($image->_resize($options)) {
                    $setting->$field = $image->url;
                    // Sync to all languages
                    $this->db->query("UPDATE mod_settings SET $field = '" . $image->url . "'");
                } else {
                     $this->_error_message($this->language_management['management']['logo_upload_error'] . ' (' . $field . ') ' . $image->error);
                }
            } catch (Exception $e) {
                $msg = $e->getMessage();

                 if (strpos($msg, 'invalid_extension|') === 0) {
                    $ext = explode('|', $msg)[1];
                    $this->_error_message($this->language_management['management']['logo_upload_error'] . $this->language_management['management']['invalid_image_extension'] . $ext);
                } else {
                    $this->_error_message($this->language_management['management']['logo_upload_error'] . $msg);
                }
            }
        }
    }

    private function _current_asset_files($setting)
    {
        $get_value = function ($key) use ($setting) {
            if (is_array($setting)) {
                return isset($setting[$key]) ? $setting[$key] : '';
            }
            return isset($setting->$key) ? $setting->$key : '';
        };

        return array_filter([
            $get_value('favicon_path'),
            $get_value('favicon_96'),
            $get_value('apple_touch_icon'),
            $get_value('site_webmanifest'),
            $get_value('manifest_192'),
            $get_value('manifest_512'),
        ]);
    }

    private function _has_uploaded_file($input_name)
    {
        return isset($_FILES[$input_name]['name']) && !empty($_FILES[$input_name]['name']);
    }

    private function _sync_asset_fields(array $fields)
    {
        foreach ($fields as $field => $value) {
            $this->db->query("UPDATE mod_settings SET $field = '" . $value . "'");
        }
    }

    private function _generate_assets_for_setting($selected_lang, &$setting)
    {
        $logo_file_input_name = 'logo_' . $selected_lang;
        if (!isset($_FILES[$logo_file_input_name]['name']) || empty($_FILES[$logo_file_input_name]['name'])) {
            throw new Exception('missing_upload');
        }

        $generator = Load::lib('BrandAssetGenerator');
        $generated_fields = $generator->generateFromUpload(
            $logo_file_input_name,
            [
                'title' => isset($setting->title) ? $setting->title : '',
                'description' => isset($setting->description) ? $setting->description : '',
            ],
            $this->_current_asset_files($setting)
        );

        foreach ($generated_fields as $field => $file_name) {
            $setting->$field = $file_name;
        }

        $this->_sync_asset_fields($generated_fields);
        return $generated_fields;
    }

    private function _resolve_asset_generator_error($code)
    {
        switch ($code) {
            case 'invalid_master_extension':
                return sprintf($this->lang['mod_settings']['invalid_extension'], '.png');
            case 'master_logo_too_small':
                return $this->lang['mod_settings']['asset_generation']['minimum_size'];
            case 'requires_imagick':
                return $this->lang['mod_settings']['asset_generation']['requires_imagick'];
            case 'output_dir_not_writable':
                return $this->lang['mod_settings']['asset_generation']['output_not_writable'];
            case 'missing_upload':
                return $this->lang['mod_settings']['asset_generation']['missing_upload'];
            case 'invalid_uploaded_file':
            default:
                return $this->lang['mod_settings']['generic_error'];
        }
    }

    private function _json_response(array $payload)
    {
        if (ob_get_length()) {
            @ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }

    private function _flash_success_and_redirect($selected_lang, $message)
    {
        if ($this->session->has('mod_settings_flash_success')) {
            $this->session->remove('mod_settings_flash_success');
        }

        if (!empty($message)) {
            $this->session->set('mod_settings_flash_success', $message);
        }

        header('Location: ' . $this->site['saddress'] . 'mod_settings/edit/' . Req::Url_Clear($selected_lang));
        exit;
    }

    private function _load_flash_messages()
    {
        if ($this->session->has('mod_settings_flash_success')) {
            $this->_success_message($this->session->get('mod_settings_flash_success'));
            $this->session->remove('mod_settings_flash_success');
        }
    }

    // Helper methods for success/error messages
    private function _success_message($message)
    {
        $this->theme->set('success', true);
        $this->theme->set('message', $message);
    }

    private function _error_message($message)
    {
        $this->theme->set('error', true);
        $this->theme->set('message', $message); // Set message variable for the alert
    }

    // New helper for form validation errors (does not hide form)
    private function _form_error($message)
    {
        $this->theme->set('errors', [$message]);
    }

    public function image_remove_image()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            echo $this->lang['mod_settings']['error'] . ': ' . $this->lang['mod_settings']['permission_errors']['settings_edit'];
            return;
        }

        $lang = Req::post('lang');
        $field = Req::post('field');

        if (empty($lang) || !in_array($field, ['logo_path', 'logo_symbol', 'site_icon', 'favicon_path', 'favicon_96', 'apple_touch_icon', 'site_webmanifest', 'manifest_192', 'manifest_512'])) {
            echo $this->lang['mod_settings']['error'] . ': ' . $this->lang['mod_settings']['invalid_params'];
            return;
        }

        $settings_model = Load::model('mod_settings');
        $settings_model->where_lang($lang)->gRo();

        if ($settings_model->getSize() === 0) {
            $fallback_model = Load::model('mod_settings');
            $fallback_model->gRo();
            if ($fallback_model->getSize() > 0) {
                $fallback_model->sync();
                $setting = $fallback_model->getResultOne();
            } else {
                echo 'Success';
                return;
            }
        } else {
            $settings_model->sync();
            $setting = $settings_model->getResultOne();
        }

        $image_path = $setting->$field;

        if (empty($image_path)) {
            echo 'Success'; // Already gone
            return;
        }

        $delete_options = ['/' => []];

        // Handle custom deletion for logo_path (Rectangle Logo)
        if ($field === 'logo_path') {
             $file_path = root_dir . 'static' . DS . 'mod_settings' . DS . 'logo.png';
             $thumb_path = root_dir . 'static' . DS . 'mod_settings' . DS . 'thumb' . DS . 'logo.png';
             if (file_exists($file_path)) {
                 @unlink($file_path);
             }
             if (file_exists($thumb_path)) {
                 @unlink($thumb_path);
             }
             if (!empty($setting->logo_path) && file_exists(root_dir . 'static' . DS . 'mod_settings' . DS . $setting->logo_path)) {
                 @unlink(root_dir . 'static' . DS . 'mod_settings' . DS . $setting->logo_path);
             }
             $setting->logo_path = '';
             $this->db->query("UPDATE mod_settings SET logo_path = ''");
             $setting->update('lang');
             echo 'Success';
             return;
        }

        // Handle custom deletion for site_icon (Square Logo)
        if ($field === 'site_icon') {
             $square_path = root_dir . 'static' . DS . 'mod_settings' . DS . 'logo_square.png';
             $thumb_square = root_dir . 'static' . DS . 'mod_settings' . DS . 'thumb' . DS . 'logo_square.png';
             if (file_exists($square_path)) {
                 @unlink($square_path);
             }
             if (file_exists($thumb_square)) {
                 @unlink($thumb_square);
             }
             foreach (['logo_32x32.png', 'logo_42x42.png', 'logo_64x64.png'] as $multi_thumb) {
                 $tp = root_dir . 'static' . DS . 'mod_settings' . DS . 'thumb' . DS . $multi_thumb;
                 if (file_exists($tp)) {
                     @unlink($tp);
                 }
             }
             if (!empty($setting->site_icon) && file_exists(root_dir . 'static' . DS . 'mod_settings' . DS . $setting->site_icon)) {
                 @unlink(root_dir . 'static' . DS . 'mod_settings' . DS . $setting->site_icon);
             }
             $setting->site_icon = '';
             $this->db->query("UPDATE mod_settings SET site_icon = ''");
             $setting->update('lang');
             echo 'Success';
             return;
        }

        // Handle custom deletion for logo_symbol (Favicon / Symbol Logo)
        if ($field === 'logo_symbol') {
             $generator = Load::lib('BrandAssetGenerator');
             $generator->deleteManagedFiles($this->_current_asset_files($setting->toArray()));
             $symbol_path = root_dir . 'static' . DS . 'mod_settings' . DS . 'logo_symbol.png';
             $thumb_symbol = root_dir . 'static' . DS . 'mod_settings' . DS . 'thumb' . DS . 'logo_symbol.png';
             if (file_exists($symbol_path)) {
                 @unlink($symbol_path);
             }
             if (file_exists($thumb_symbol)) {
                 @unlink($thumb_symbol);
             }
             if (!empty($setting->logo_symbol) && file_exists(root_dir . 'static' . DS . 'mod_settings' . DS . $setting->logo_symbol)) {
                 @unlink(root_dir . 'static' . DS . 'mod_settings' . DS . $setting->logo_symbol);
             }

             $favicon_fields = [
                 'logo_symbol',
                 'favicon_path',
                 'favicon_96',
                 'apple_touch_icon',
                 'site_webmanifest',
                 'manifest_192',
                 'manifest_512',
             ];
             foreach ($favicon_fields as $favicon_field) {
                 $setting->$favicon_field = '';
                 $this->db->query("UPDATE mod_settings SET $favicon_field = ''");
             }
             $setting->update('lang');
             echo 'Success';
             return;
        }

        if (in_array($field, ['favicon_path', 'favicon_96', 'apple_touch_icon', 'manifest_192', 'manifest_512', 'site_webmanifest'])) {
             $file_path = root_dir . 'static' . DS . 'mod_settings' . DS . $image_path;
             if (file_exists($file_path)) {
                 @unlink($file_path);
             }
             $setting->$field = '';
             $this->db->query("UPDATE mod_settings SET $field = ''");
             $setting->update('lang');
             echo 'Success';
             return;
        }

        $image = Load::mod('image');
        $image->mod = 'mod_settings';
        $image->url = $image_path;

        if ($image->_delete_files($delete_options)) {
            $setting->$field = '';
            if ($setting->update('lang')) {
                // Sync deletion to all languages
                $this->db->query("UPDATE mod_settings SET $field = ''");
                echo 'Success';
            } else {
                echo $this->lang['mod_settings']['error'] . ': ' . $this->lang['mod_settings']['update_error'] . ' ORM: ' . $settings_model->db->sql_error();
            }
        } else {
            $error_msg = !empty($image->error) ? $image->error : $this->lang['mod_settings']['delete_unknown_error'];
            echo $this->lang['mod_settings']['error'] . ': ' . $this->lang['mod_settings']['delete_fail'] . ' ' . $error_msg;
        }
    }
    // --- Social Media Management Endpoints ---

    public function ajax_social_list()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $social_model = Load::model('mod_settings.mod_settings_social');
        $this->db->fetch_array("SELECT * FROM mod_settings_social ORDER BY display_order ASC");

        $this->_json_response([
            'status' => 'success',
            'data' => $this->db->getResult()->toArray()
        ]);
    }

    public function ajax_social_save()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $id = (int) Req::post('id');
        $url = trim((string) Req::post('url'));
        $platform = trim((string) Req::post('platform'));
        $icon_class = trim((string) Req::post('icon_class'));
        $is_active = Req::post('is_active') === 'n' ? 'n' : 'y';

        $social_model = Load::model('mod_settings.mod_settings_social');

        if ($id <= 0) {
            if (empty($platform)) {
                $this->_json_response([
                    'status' => 'error',
                    'message' => $this->lang['mod_settings']['settings_required_fields'] ?? 'Platform and URL are required.'
                ]);
                return;
            }

            // Determine next display order
            $this->db->fetch_array("SELECT display_order FROM mod_settings_social ORDER BY display_order DESC LIMIT 1");
            $maxRows = $this->db->getResult()->toArray();
            $nextOrder = isset($maxRows[0]['display_order']) ? ((int) $maxRows[0]['display_order'] + 1) : 1;

            $social_model->platform = strtolower($platform);
            $social_model->url = $url;
            $social_model->icon_class = !empty($icon_class) ? $icon_class : ('bi bi-' . strtolower($platform));
            $social_model->display_order = $nextOrder;
            $social_model->is_active = $is_active;
            $social_model->user_id = (int) ($this->user->id ?? 1);

            $result = $social_model->insert();

            if ($result) {
                $this->_json_response([
                    'status' => 'success',
                    'message' => $this->lang['mod_settings']['messages']['success']['updated'] ?? 'Saved successfully.',
                    'id' => $social_model->id
                ]);
            } else {
                $this->_json_response([
                    'status' => 'error',
                    'message' => $this->lang['mod_settings']['error'] . ' ' . $this->db->sql_error()
                ]);
            }
            return;
        }

        $social_model->where_id($id)->gRo();
        if ($social_model->getSize() === 0) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['error']
            ]);
            return;
        }
        $social_model->sync();
        $social_model = $social_model->getResultOne();

        $social_model->url = $url;
        if (!empty($platform)) {
            $social_model->platform = strtolower($platform);
        }
        if (!empty($icon_class)) {
            $social_model->icon_class = $icon_class;
        }
        if (Req::post('is_active') !== null) {
            $social_model->is_active = $is_active;
        }

        $result = $social_model->update('id');

        if ($result) {
            $this->_json_response([
                'status' => 'success',
                'message' => $this->lang['mod_settings']['messages']['success']['updated'] ?? 'Updated successfully.'
            ]);
        } else {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['error'] . ' ' . $social_model->db->sql_error()
            ]);
        }
    }

    public function ajax_social_delete()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $id = (int) Req::post('id');
        if ($id <= 0) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['invalid_params']
            ]);
            return;
        }

        $social_model = Load::model('mod_settings.mod_settings_social');
        $social_model->where_id($id)->gRo();
        if ($social_model->getSize() === 0) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['error']
            ]);
            return;
        }
        $social_model->sync();
        $social_model = $social_model->getResultOne();
        $result = $social_model->delete('id');

        if ($result) {
            $this->_json_response([
                'status' => 'success',
                'message' => $this->lang['mod_settings']['delete_success'] ?? 'Deleted successfully.'
            ]);
        } else {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['error']
            ]);
        }
    }



    public function ajax_social_toggle_status()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $id = (int) Req::post('id');
        $is_active = Req::post('is_active') === 'y' ? 'y' : 'n';

        if ($id <= 0) {
             $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['invalid_params']
            ]);
            return;
        }

        $this->db->query("UPDATE mod_settings_social SET is_active = '{$is_active}' WHERE id = {$id}");

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['messages']['success']['updated']
        ]);
    }

    public function ajax_social_reorder()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        // We expect orders to be an array of associative arrays with id and order.
        // E.g., json string or directly array from post depends on client side submission.
        // Assuming client sends it as an array of objects `[{id: 1, order: 1}, ...]` via JSON payload or regular array.
        $orders = Req::post('orders');

        // If it's a JSON string, decode it.
        if (is_string($orders)) {
            $orders = json_decode($orders, true);
        }

        if (!empty($orders) && is_array($orders)) {
            foreach ($orders as $item) {
                $id = (int) ($item['id'] ?? 0);
                $order = (int) ($item['order'] ?? 0);
                if ($id > 0) {
                     $this->db->query("UPDATE mod_settings_social SET display_order = {$order} WHERE id = {$id}");
                }
            }
        }

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['messages']['success']['updated']
        ]);
    }

    /**
     * Handles the Affiliations & Institutions admin page
     */
    public function affiliations()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            return false;
        }

        $this->ensureAffiliationTable();

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_settings']['affiliations_title'] ?? 'Affiliations & Partnerships';
        $this->theme->set('site', $this->site);
        $this->theme->set('is_ajax', $this->_is_ajax_request());
        $this->theme->set('back_url', $this->site['saddress'] . 'mod_settings/edit');

        $this->db->fetch_array("SELECT * FROM mod_settings_affiliation ORDER BY display_order ASC");
        $affiliation_items = $this->db->getResult()->toArray();
        $this->theme->set('affiliation_items', $affiliation_items);

        $this->theme->tpl('mod_settings/affiliations');
    }

    public function ajax_affiliation_list()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $this->ensureAffiliationTable();

        $this->db->fetch_array("SELECT * FROM mod_settings_affiliation ORDER BY display_order ASC");
        $this->_json_response([
            'status' => 'success',
            'data' => $this->db->getResult()->toArray()
        ]);
    }

    public function ajax_affiliation_save()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $id = (int) Req::post('id');
        $name = trim((string) Req::post('name'));
        $url = trim((string) Req::post('url'));
        $is_active = Req::post('is_active') === 'n' ? 'n' : 'y';

        if (empty($name) || empty($url)) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['settings_required_fields'] ?? 'Name and URL are required.'
            ]);
            return;
        }

        // Handle logo file upload if provided
        $logo_filename = '';
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = root_dir . 'static' . DS . 'mod_settings' . DS;
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
            if (in_array($ext, $allowed_exts)) {
                $safe_name = 'affil_' . time() . '_' . substr(md5(uniqid(rand(), true)), 0, 8) . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $safe_name)) {
                    $logo_filename = $safe_name;
                }
            }
        }

        Load::modelfile('mod_settings.mod_settings_affiliation');
        $model = new mod_settings_affiliation_model();

        if ($id <= 0) {
            $this->db->fetch_array("SELECT display_order FROM mod_settings_affiliation ORDER BY display_order DESC LIMIT 1");
            $maxRows = $this->db->getResult()->toArray();
            $nextOrder = isset($maxRows[0]['display_order']) ? ((int) $maxRows[0]['display_order'] + 1) : 1;

            $model->name = $name;
            $model->url = $url;
            $model->logo = $logo_filename;
            $model->display_order = $nextOrder;
            $model->is_active = $is_active;
            $model->user_id = (int) ($this->user->id ?? 1);

            $result = $model->insert();
            if ($result) {
                $this->_json_response([
                    'status' => 'success',
                    'message' => $this->lang['mod_settings']['messages']['success']['updated'] ?? 'Saved successfully.',
                    'id' => $model->id
                ]);
            } else {
                $this->_json_response([
                    'status' => 'error',
                    'message' => $this->lang['mod_settings']['error'] . ' ' . $this->db->sql_error()
                ]);
            }
            return;
        }

        $model->where_id($id)->gRo();
        if ($model->getSize() === 0) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['error']
            ]);
            return;
        }
        $model->sync();
        $model = $model->getResultOne();

        $model->name = $name;
        $model->url = $url;
        if (!empty($logo_filename)) {
            if (!empty($model->logo) && file_exists(root_dir . 'static' . DS . 'mod_settings' . DS . $model->logo)) {
                @unlink(root_dir . 'static' . DS . 'mod_settings' . DS . $model->logo);
            }
            $model->logo = $logo_filename;
        }
        if (Req::post('is_active') !== null) {
            $model->is_active = $is_active;
        }

        $result = $model->update('id');
        if ($result) {
            $this->_json_response([
                'status' => 'success',
                'message' => $this->lang['mod_settings']['messages']['success']['updated'] ?? 'Updated successfully.'
            ]);
        } else {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['error'] . ' ' . $this->db->sql_error()
            ]);
        }
    }

    public function ajax_affiliation_delete()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $id = (int) Req::post('id');
        if ($id <= 0) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['invalid_params']
            ]);
            return;
        }

        Load::modelfile('mod_settings.mod_settings_affiliation');
        $model = new mod_settings_affiliation_model();
        $model->where_id($id)->gRo();
        if ($model->getSize() === 0) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['error']
            ]);
            return;
        }
        $model->sync();
        $model = $model->getResultOne();
        if (!empty($model->logo) && file_exists(root_dir . 'static' . DS . 'mod_settings' . DS . $model->logo)) {
            @unlink(root_dir . 'static' . DS . 'mod_settings' . DS . $model->logo);
        }
        $result = $model->delete('id');

        if ($result) {
            $this->_json_response([
                'status' => 'success',
                'message' => $this->lang['mod_settings']['delete_success'] ?? 'Deleted successfully.'
            ]);
        } else {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['error']
            ]);
        }
    }

    public function ajax_affiliation_toggle_status()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $id = (int) Req::post('id');
        $is_active = Req::post('is_active') === 'y' ? 'y' : 'n';

        if ($id <= 0) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['invalid_params']
            ]);
            return;
        }

        $this->db->query("UPDATE mod_settings_affiliation SET is_active = '{$is_active}' WHERE id = {$id}");

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['messages']['success']['updated']
        ]);
    }

    public function ajax_affiliation_reorder()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $orders = Req::post('orders');
        if (is_string($orders)) {
            $orders = json_decode($orders, true);
        }

        if (!empty($orders) && is_array($orders)) {
            foreach ($orders as $item) {
                $id = (int) ($item['id'] ?? 0);
                $order = (int) ($item['order'] ?? 0);
                if ($id > 0) {
                    $this->db->query("UPDATE mod_settings_affiliation SET display_order = {$order} WHERE id = {$id}");
                }
            }
        }

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['messages']['success']['updated']
        ]);
    }

    public function google_settings()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            return false;
        }

        Run::import('management', '_index');

        $dataDir = root_dir . 'data' . DS;
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0755, true);
        }
        $settingsPath = $dataDir . 'google_settings.json';
        $credentialsPath = $dataDir . 'credentials.json';

        $csrfToken = (string) $this->session->get('google_settings_csrf');
        if (strlen($csrfToken) !== 32) {
            $csrfToken = bin2hex(random_bytes(16));
            $this->session->set('google_settings_csrf', $csrfToken);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $submittedCsrf = (string) ($_POST['csrf'] ?? '');
            if ($submittedCsrf === '' || !hash_equals($csrfToken, $submittedCsrf)) {
                $this->session->setFlash('error', $this->lang['mod_settings']['generic_error']);
                Helper::redirect($this->site['saddress'] . 'mod_settings/google_settings', 0);
                return;
            }

            $bucketName = trim((string) Req::Post_Safe('bucket_name'));
            $geminiApiKey = trim((string) Req::Post_Safe('gemini_api_key'));

            $settings = [
                'bucket_name' => $bucketName,
                'gemini_api_key' => $geminiApiKey,
            ];
            file_put_contents($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));

            if (isset($_FILES['credentials_file']) && $_FILES['credentials_file']['error'] === UPLOAD_ERR_OK) {
                $tmpPath = $_FILES['credentials_file']['tmp_name'];
                $content = file_get_contents($tmpPath);
                $decoded = json_decode((string) $content, true);

                if ($decoded && isset($decoded['private_key']) && isset($decoded['client_email'])) {
                    file_put_contents($credentialsPath, $content);
                } else {
                    $this->session->setFlash('error', $this->lang['mod_settings']['json_invalid']);
                    Helper::redirect($this->site['saddress'] . 'mod_settings/google_settings', 0);
                    return;
                }
            }

            $cacheDir = root_dir . 'cache' . DS;
            $templatesCDir = root_dir . 'templates_c' . DS;
            foreach ([$cacheDir, $templatesCDir] as $dir) {
                if (is_dir($dir)) {
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($files as $fileinfo) {
                        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                        @$todo($fileinfo->getRealPath());
                    }
                }
            }

            $this->session->setFlash('success', $this->lang['mod_settings']['google']['settings_ok']);
            Helper::redirect($this->site['saddress'] . 'mod_settings/google_settings', 0);
            return;
        }

        $bucketName = '';
        $geminiApiKey = '';

        if (file_exists($settingsPath)) {
            $settings = json_decode((string) file_get_contents($settingsPath), true);
            $bucketName = $settings['bucket_name'] ?? '';
            $geminiApiKey = $settings['gemini_api_key'] ?? '';
        }

        $hasCredentials = file_exists($credentialsPath);
        $flashSuccess = $this->session->getFlash('success');
        $flashError = $this->session->getFlash('error');

        $this->site['title'] = $this->lang['mod_settings']['google_settings'];
        $this->theme->set('site', $this->site);
        $this->theme->set('google_settings_csrf', $csrfToken);
        $this->theme->set('flash_success', $flashSuccess);
        $this->theme->set('flash_error', $flashError);
        $this->theme->set('bucket_name', $bucketName);
        $this->theme->set('gemini_api_key', $geminiApiKey);
        $this->theme->set('has_credentials', $hasCredentials);
        $this->theme->set('cancel_url', $this->site['saddress'] . 'mod_user/panel');
        $this->theme->set('cancel_label', $this->lang['mod_settings']['button']['back']);
        $this->theme->set('submit_label', $this->lang['mod_settings']['save']);
        $this->theme->set('form_id', 'googleSettingsForm');
        $this->theme->tpl('mod_settings/google_settings');
    }
}
