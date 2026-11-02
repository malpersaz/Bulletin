<?php

class mod_event extends installerbase implements installerbaseinterface
{
    public $tables;
    public $views;
    public $roles;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_event';
        $this->tables = array(
            'mod_event',
            'events'
        );
        $this->roles = array(
            'add', 'edit', 'delete', 'list', 'gallery', 'file', 'faq', 'video', 'edit_status', 'change_status', 'approval', 'moderate_edit', 'moderate_delete', 'video_download'
        );
    }

    public function install()
    {
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        if ($this->insert_roles() === FALSE) {
            return FALSE;
        }

        $this->create_directories();

        if ($this->add_content() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    public function uninstall()
    {
        $this->remove_tables();
        if ($this->uninstall_blocked === TRUE) {
            return FALSE;
        }
        if (class_exists('BridgeCleanupService')) {
            BridgeCleanupService::cleanModule('mod_event');
        }
        $this->remove_mods();
        $this->delete_roles();
        $this->remove_perm();
        return TRUE;
    }

    private function create_directories()
    {
        $dirs = array(
            root_dir . 'static/mod_event',
            root_dir . 'static/mod_event/thumb',
            root_dir . 'static/mod_event/original',
            root_dir . 'static/mod_gallery/mod_event',
            root_dir . 'static/mod_gallery/mod_event/thumb',
            root_dir . 'static/mod_gallery/mod_event/original',
        );

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
        }
    }

    public function add_content()
    {
        if (!$this->installer_sample_content_enabled()) {
            return TRUE;
        }

        $now = time();
        $startDate1 = date('Y-m-d H:i:s', $now + (86400 * 3));
        $endDate1 = date('Y-m-d H:i:s', $now + (86400 * 3) + 7200);

        $startDate2 = date('Y-m-d H:i:s', $now + (86400 * 7));
        $endDate2 = date('Y-m-d H:i:s', $now + (86400 * 7) + 14400);

        $data = array(
            array(
                'name' => 'Amed Uluslararası Kültür ve Sanat Konferansı',
                'lang' => 'tr',
                'subject' => 'Konferans',
                'start_date' => $startDate1,
                'end_date' => $endDate1,
                'status' => 'y',
                'slider' => 'y',
                'deck' => 'y',
                'summary' => 'Amed Uluslararası Kültür ve Sanat Konferansı, bölgenin kadim tarihini ve güncel sanat üretimlerini uluslararası panellerle buluşturuyor.',
                'content' => '<p><strong>Amed Uluslararası Kültür ve Sanat Konferansı</strong>, çok dilli ve çok kültürlü yapısıyla sanatçıları, araştırmacıları ve edebiyatçıları bir araya getiriyor.</p><p>Program dahilinde çağdaş sanat panelleri, fotoğraf sergileri ve atölyeler düzenlenecektir.</p>',
                'location' => 'Sezai Karakoç Kongre ve Sanat Merkezi, Amed',
                'participants' => 'Akademisyenler, Sanatçılar ve Yazarlar',
                'guests' => 'Onur Konukları ve Delegasyon Temsilcileri',
                'seo' => 'amed_uluslararasi_kultur_ve_sanat_konferansi',
                'title' => 'Amed Uluslararası Kültür ve Sanat Konferansı - {{build_name}}',
                'keywords' => 'amed, kültür, sanat, konferans, panel',
                'description' => 'Amed Uluslararası Kültür ve Sanat Konferansı programı ve katılım bilgileri.',
                'user_id' => 1
            ),
            array(
                'name' => 'Amed International Culture and Arts Conference',
                'lang' => 'en',
                'subject' => 'Conference',
                'start_date' => $startDate1,
                'end_date' => $endDate1,
                'status' => 'y',
                'slider' => 'y',
                'deck' => 'y',
                'summary' => 'The Amed International Culture and Arts Conference brings together scholars, artists, and researchers to explore cultural heritage and contemporary expressions.',
                'content' => '<p><strong>The Amed International Culture and Arts Conference</strong> features multilingual symposiums, contemporary visual art exhibits, and academic roundtables.</p><p>All sessions will provide simultaneous translation across major languages.</p>',
                'location' => 'Sezai Karakoç Convention and Exhibition Center, Amed',
                'participants' => 'Scholars, Artists, and Authors',
                'guests' => 'Distinguished Delegates and Keynote Speakers',
                'seo' => 'amed_international_culture_and_arts_conference',
                'title' => 'Amed International Culture and Arts Conference - {{build_name}}',
                'keywords' => 'amed, culture, arts, conference, symposium',
                'description' => 'Program and details for the Amed International Culture and Arts Conference.',
                'user_id' => 1
            ),
            array(
                'name' => 'Konferansa Navneteweyî ya Çand û Hunerê ya Amedê',
                'lang' => 'ku',
                'subject' => 'Konferans',
                'start_date' => $startDate1,
                'end_date' => $endDate1,
                'status' => 'y',
                'slider' => 'y',
                'deck' => 'y',
                'summary' => 'Konferansa Navneteweyî ya Çand û Hunerê ya Amedê dîroka kevnar û berhemên hunerî yên hevdem bi panelên navneteweyî pêşkêş dike.',
                'content' => '<p><strong>Konferansa Navneteweyî ya Çand û Hunerê ya Amedê</strong> çandhezan, lêkolîneran û nivîskaran li hev dicivîne.</p><p>Di çarçoveya bernameyê de panelên hunera hemdem, pêşangehên wêneyan û atolye dê werin lidarxistin.</p>',
                'location' => 'Navenda Kongre û Hunerê ya Sezaî Karakoç, Amed',
                'participants' => 'Akademîsyen, Hunermend û Nivîskar',
                'guests' => 'Mêvanên Rûmetê û Şandeyên Taybet',
                'seo' => 'konferansa_navneteweyi_ya_cand_u_hunere_ya_amede',
                'title' => 'Konferansa Navneteweyî ya Çand û Hunerê ya Amedê - {{build_name}}',
                'keywords' => 'amed, çand, huner, konferans, panel',
                'description' => 'Agahî û bernameya Konferansa Navneteweyî ya Çand û Hunerê ya Amedê.',
                'user_id' => 1
            ),
            array(
                'name' => 'Mezopotamya Klasik Müzik Konseri',
                'lang' => 'tr',
                'subject' => 'Konser',
                'start_date' => $startDate2,
                'end_date' => $endDate2,
                'status' => 'y',
                'slider' => 'n',
                'deck' => 'y',
                'summary' => 'Mezopotamya senfoni orkestrası ve yerel dengbêjlerin eşliğinde düzenlenen özel klasik müzik dinletisi.',
                'content' => '<p>Kadim melodilerin senfonik aranjmanlarla harmanlandığı özel bir konser akşamı müzikseverleri bekliyor.</p>',
                'location' => 'Tarihi Sur İçi Amfi Tiyatrosu, Amed',
                'participants' => 'Mezopotamya Orkestrası & Solistler',
                'guests' => 'Müzik Eleştirmenleri ve Sanatseverler',
                'seo' => 'mezopotamya_klasik_muzik_konseri',
                'title' => 'Mezopotamya Klasik Müzik Konseri - {{build_name}}',
                'keywords' => 'mezopotamya, klasik müzik, konser, amed',
                'description' => 'Mezopotamya Klasik Müzik Konseri bilet ve etkinlik ayrıntıları.',
                'user_id' => 1
            )
        );

        $data = installer_replace_build_name($data);
        $this->model = Load::model('mod_event');

        foreach ($data as $value) {
            $lang = $value['lang'];
            if ($this->model->exists_name_and_lang($value['name'], $lang) === FALSE) {
                $starterName = $this->installer_resolve_category_starter_label('events', $lang);
                if ($starterName === FALSE) {
                    $starterName = 'Events';
                }
                $query = "SELECT id FROM `mod_event_category` WHERE lang='" . addslashes($lang) . "' AND name='" . addslashes($starterName) . "' ORDER BY id ASC LIMIT 1";
                $this->db->fetch_array($query);
                if ($this->db->getSize() > 0) {
                    $rows = $this->db->getResult()->toArray();
                    $value['category_id'] = (int) $rows[0]['id'];
                } else {
                    $value['category_id'] = 0;
                }

                $keys = array_keys($value);
                foreach ($keys as $k) {
                    $this->model->$k = $value[$k];
                }
                if ($this->model->save() === FALSE) {
                    $this->error("Failed to add event: " . $value['name']);
                    return FALSE;
                }
                $this->success("Added event: " . $value['name']);
            }
        }

        return TRUE;
    }
}
