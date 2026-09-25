<?php

class mod_menu extends installerbase implements installerbaseinterface
{
    public $tables;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_menu';
        $this->tables = array(
            'mod_menu'
        );
        $this->roles = array(
            'add', 'edit',  'delete', 'list', 'moderate_edit','moderate_delete'
        );
    }

    public function install()
    {
          // Create installer tables.
            if ($this->create_tables() === FALSE) {
                return FALSE;
            }
            // Register the module.
            if ($this->insert_mods() === FALSE) {
                return FALSE;
            }
            // Add roles and permissions.
            if ($this->insert_roles() === FALSE) {
                return FALSE;
            }
            if ($this->add_default_slot_roots() === FALSE) {
                return FALSE;
            }

            return TRUE;
    }

    private function insertMenuRecord(array $record, $successMessage, $existsMessage)
    {
        $langCode = isset($record['lang']) ? addslashes((string) $record['lang']) : '';
        $name = isset($record['name']) ? addslashes((string) $record['name']) : '';
        $parentId = isset($record['parent_id']) ? (int) $record['parent_id'] : 0;

        $this->db->fetch_array("SELECT id FROM `mod_menu` WHERE `lang`='" . $langCode . "' AND `parent_id`=" . $parentId . " AND `name`='" . $name . "' ORDER BY id ASC LIMIT 1");
        if ($this->db->getSize() !== 0) {
            $existing = $this->db->getResult()->toArray();
            $this->warning($existsMessage);
            return (int) $existing[0]['id'];
        }

        $insert = $this->installer_filter_existing_columns('mod_menu', $record);
        $columns = array();
        $values = array();

        foreach ($insert as $column => $value) {
            $columns[] = '`' . $column . '`';
            $values[] = $this->installer_sql_value($value);
        }

        $sql = "INSERT INTO `mod_menu` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
        if ($this->db->query($sql) === FALSE) {
            $this->error($successMessage . ' could not be added');
            return FALSE;
        }

        $insertId = (int) $this->db->insert_id();
        $this->success($successMessage . ' added');
        return $insertId;
    }

    private function add_default_slot_roots()
    {
        $langs = [];
        $this->db->fetch_array("SELECT name, seo FROM `lang` ORDER BY seo ASC");
        if ($this->db->getSize() > 0) {
            $langs = $this->db->getResult()->toArray();
        }

        if (empty($langs)) {
            $langs = [
                ['name' => 'Türkçe', 'seo' => 'tr'],
                ['name' => 'English', 'seo' => 'en'],
                ['name' => 'Kurmancî', 'seo' => 'ku'],
            ];
        }

        $primaryLang = isset($langs[0]['seo']) ? trim((string) $langs[0]['seo']) : 'tr';

        foreach ($langs as $lang) {
            $langCode = isset($lang['seo']) ? trim((string) $lang['seo']) : '';
            $langName = isset($lang['name']) ? trim((string) $lang['name']) : '';

            if ($langCode === '' || $langName === '') {
                continue;
            }

            $languageRootName = '##' . $langName;
            $languageRootId = $this->insertMenuRecord(
                [
                    'lang' => $langCode,
                    'name' => $languageRootName,
                    'url' => '##',
                    'file' => null,
                    'parent_id' => 0,
                    'order' => 0,
                    'target' => 'n',
                    'status' => 'y',
                    'html' => null,
                    'icon' => null,
                    'acl_id' => 0,
                    'user_id' => 1,
                ],
                $languageRootName,
                $languageRootName . ' already exists'
            );

            if ($languageRootId === FALSE) {
                return FALSE;
            }

            foreach (['##Header_Menu', '##Footer_Menu'] as $slotName) {
                $slotRootId = $this->insertMenuRecord(
                    [
                        'lang' => $langCode,
                        'name' => $slotName,
                        'url' => '##',
                        'file' => null,
                        'parent_id' => $languageRootId,
                        'order' => 0,
                        'target' => 'n',
                        'status' => 'y',
                        'html' => null,
                        'icon' => null,
                        'acl_id' => 0,
                        'user_id' => 1,
                    ],
                    $slotName . ' for ' . $langCode,
                    $slotName . ' for ' . $langCode . ' already exists'
                );

                if ($slotRootId === FALSE) {
                    return FALSE;
                }

                if (in_array($slotName, ['##Header_Menu', '##Footer_Menu'], true) && $this->installer_sample_content_enabled()) {
                    $sampleItems = [
                        [
                            'url_suffix' => 'about',
                            'names' => [
                                'ar' => 'من نحن',
                                'ckb' => 'دەربارەی ئێمە',
                                'de' => 'Über uns',
                                'en' => 'About Us',
                                'fa' => 'درباره ما',
                                'fr' => 'À propos de nous',
                                'ku' => 'Me Nas Bike',
                                'sv' => 'Om oss',
                                'tr' => 'Hakkımızda',
                                'zza' => 'Heqê ma de',
                                'ru' => 'О нас',
                            ],
                            'children' => [
                                [
                                    'url_suffix' => 'services',
                                    'names' => [
                                        'ar' => 'الخدمات',
                                        'ckb' => 'خزمەتگوزارییەکان',
                                        'de' => 'Dienstleistungen',
                                        'en' => 'Services',
                                        'fa' => 'خدمات',
                                        'fr' => 'Services',
                                        'ku' => 'Xizmet',
                                        'sv' => 'Tjänster',
                                        'tr' => 'Hizmetler',
                                        'zza' => 'Xizmeti',
                                        'ru' => 'Услуги',
                                    ]
                                ],
                                [
                                    'url_suffix' => 'newses',
                                    'names' => [
                                        'ar' => 'الأخبار',
                                        'ckb' => 'هەواڵەکان',
                                        'de' => 'Nachrichten',
                                        'en' => 'News',
                                        'fa' => 'اخبار',
                                        'fr' => 'Actualités',
                                        'ku' => 'Nûçe',
                                        'sv' => 'Nyheter',
                                        'tr' => 'Haberler',
                                        'zza' => 'Nûçey',
                                        'ru' => 'Новости',
                                    ]
                                ],
                                [
                                    'url_suffix' => 'contents',
                                    'names' => [
                                        'ar' => 'النشرة',
                                        'ckb' => 'بڵاوکراوە',
                                        'de' => 'Bulletin',
                                        'en' => 'Bulletin',
                                        'fa' => 'بولتن',
                                        'fr' => 'Bulletin',
                                        'ku' => 'Bûlten',
                                        'sv' => 'Bulletin',
                                        'tr' => 'Bülten',
                                        'zza' => 'Bulten',
                                        'ru' => 'Бюллетень',
                                    ]
                                ],
                            ]
                        ],
                        [
                            'url_suffix' => 'products',
                            'names' => [
                                'ar' => 'المنتجات',
                                'ckb' => 'بەرهەمەکان',
                                'de' => 'Produkte',
                                'en' => 'Products',
                                'fa' => 'محصولات',
                                'fr' => 'Produits',
                                'ku' => 'Berhem',
                                'sv' => 'Produkter',
                                'tr' => 'Ürünler',
                                'zza' => 'Berhemi',
                                'ru' => 'Продукция',
                            ]
                        ],
                        [
                            'url_suffix' => 'projects',
                            'names' => [
                                'ar' => 'المشاريع',
                                'ckb' => 'پرۆژە',
                                'de' => 'Projekte',
                                'en' => 'Projects',
                                'fa' => 'پروژه‌ها',
                                'fr' => 'Projets',
                                'ku' => 'Proje',
                                'sv' => 'Projekt',
                                'tr' => 'Projeler',
                                'zza' => 'Proje',
                                'ru' => 'Проекты',
                            ]
                        ],
                        [
                            'url_suffix' => 'videos',
                            'names' => [
                                'ar' => 'الفيديو',
                                'ckb' => 'ڤیدیۆکان',
                                'de' => 'Videos',
                                'en' => 'Video',
                                'fa' => 'ویدیوها',
                                'fr' => 'Vidéos',
                                'ku' => 'Vîdewo',
                                'sv' => 'Videor',
                                'tr' => 'Videolar',
                                'zza' => 'Videoy',
                                'ru' => 'Видео',
                            ]
                        ],
                        [
                            'url_suffix' => 'announcements',
                            'names' => [
                                'ar' => 'الإعلانات',
                                'ckb' => 'ڕاگەیەندراوەکان',
                                'de' => 'Ankündigungen',
                                'en' => 'Announcements',
                                'fa' => 'اطلاعیه‌ها',
                                'fr' => 'Annonces',
                                'ku' => 'Daxwiyanî',
                                'sv' => 'Meddelanden',
                                'tr' => 'Duyurular',
                                'zza' => 'Eşnawiyayışi',
                                'ru' => 'Объявления',
                            ]
                        ]
                    ];

                    $order = 1;
                    $aboutSeoMap = [
                        'ar' => 'من_نحن',
                        'ckb' => 'دەربارەی_ئێمە',
                        'de' => 'Über_uns',
                        'en' => 'About_Us',
                        'fa' => 'About_Us',
                        'fr' => 'À_propos',
                        'ku' => 'Derbarê_me',
                        'sv' => 'Om_oss',
                        'tr' => 'Hakkımızda',
                        'zza' => 'Ma_Heqa',
                        'ru' => 'О_нас'
                    ];

                    foreach ($sampleItems as $item) {
                        $displayName = isset($item['names'][$langCode]) ? $item['names'][$langCode] : $item['names']['en'];
                        if ($item['url_suffix'] === 'about') {
                            $seoName = isset($aboutSeoMap[$langCode]) ? $aboutSeoMap[$langCode] : 'About_Us';
                            $url = $langCode . '/page/' . $seoName;
                        } else {
                            $url = $langCode . '/' . $item['url_suffix'];
                        }

                        $itemId = $this->insertMenuRecord(
                            [
                                'lang' => $langCode,
                                'name' => $displayName,
                                'url' => $url,
                                'file' => null,
                                'parent_id' => $slotRootId,
                                'order' => $order,
                                'target' => 'n',
                                'status' => 'y',
                                'html' => null,
                                'icon' => null,
                                'acl_id' => 0,
                                'user_id' => 1,
                            ],
                            $displayName . ' menu item for ' . $langCode,
                            $displayName . ' menu item for ' . $langCode . ' already exists'
                        );

                        if ($itemId === FALSE) {
                            return FALSE;
                        }

                        if (!empty($item['children'])) {
                            $childOrder = 1;
                            foreach ($item['children'] as $child) {
                                $childDisplayName = isset($child['names'][$langCode]) ? $child['names'][$langCode] : $child['names']['en'];
                                $childUrl = $langCode . '/' . $child['url_suffix'];

                                $childId = $this->insertMenuRecord(
                                    [
                                        'lang' => $langCode,
                                        'name' => $childDisplayName,
                                        'url' => $childUrl,
                                        'file' => null,
                                        'parent_id' => $itemId,
                                        'order' => $childOrder,
                                        'target' => 'n',
                                        'status' => 'y',
                                        'html' => null,
                                        'icon' => null,
                                        'acl_id' => 0,
                                        'user_id' => 1,
                                    ],
                                    $childDisplayName . ' submenu item for ' . $langCode,
                                    $childDisplayName . ' submenu item for ' . $langCode . ' already exists'
                                );

                                if ($childId === FALSE) {
                                    return FALSE;
                                }
                                $childOrder++;
                            }
                        }

                        $order++;
                    }
                }
            }

            if ($languageRootId === FALSE) {
                return FALSE;
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
