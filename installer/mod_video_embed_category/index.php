<?php

class mod_video_embed_category extends installerbase implements installerbaseinterface
{
    public $tablolar;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_video_embed_category';
        $this->tablolar = array(
            'mod_video_embed_category'
        );
        $this->rolls = array(
            'add', 'edit',  'delete', 'list', 'moderate_edit', 'moderate_delete'
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
        // Bir Kaç KAtegori Ekleyelim
        if ($this->addkategori() === FALSE) {
            return FALSE;
        }
        return TRUE;
    }

    public function addkategori()
    {
        $veri = array(
            array(
                'name' => 'Türkçe Video Kategorileri',
                'lang' => 'tr',
                'parent_id' => '0',
                'seo' => '#',
                'title' => '#',
                'keywords' => '#',
                'description' => '#',
                'root' => 'y',
                'user_id' => 1
            ),
            array(
                'name' => 'Kategoriyên Vîdyoyên Kurdî',
                'lang' => 'ku',
                'parent_id' => '0',
                'seo' => '#',
                'title' => '#',
                'keywords' => '#',
                'description' => '#',
                'root' => 'y',
                'user_id' => 1
            ),
            array(
                'name' => 'English Video Categories',
                'lang' => 'en',
                'parent_id' => '0',
                'seo' => '#',
                'title' => '#',
                'keywords' => '#',
                'description' => '#',
                'root' => 'y',
                'user_id' => 1
            ),

            // 4
            array(
                'name' => 'Genel',
                'lang' => 'tr',
                'parent_id' => '1',
                'seo' => 'Genel',
                'title' => '#',
                'keywords' => '#',
                'description' => '#',
                'root' => 'n',
                'user_id' => 1
            ),

            // 5
            array(
                'name' => 'Giştî',
                'lang' => 'ku',
                'parent_id' => '2',
                'seo' => 'Gîştî',
                'title' => '#',
                'keywords' => 'Giştî',
                'description' => 'Giştî',
                'root' => 'n',
                'user_id' => 1
            ),

            // 6
            array(
                'name' => 'General',
                'lang' => 'en',
                'parent_id' => '3',
                'seo' => 'General',
                'title' => '#',
                'keywords' => '#',
                'description' => '#',
                'root' => 'n',
                'user_id' => 1
            ),

            // New 'İcerik Videoları' category for Turkish
            array(
                'name' => 'İcerik Videoları',
                'lang' => 'tr',
                'parent_id' => '1', // Sub-category of 'Türkçe Video Kategorileri'
                'seo' => 'icerik-videolari',
                'title' => 'İcerik Videoları',
                'keywords' => 'icerik, video',
                'description' => 'İcerik Videoları',
                'root' => 'n',
                'user_id' => 1
            ),

            // New 'Content Videos' category for English
            array(
                'name' => 'Content Videos',
                'lang' => 'en',
                'parent_id' => '3', // Sub-category of 'İngilizce Video Kategorileri'
                'seo' => 'content-videos',
                'title' => 'Content Videos',
                'keywords' => 'content, video',
                'description' => 'Content Videos',
                'root' => 'n',
                'user_id' => 1
            ),

            // New 'Vîdyoyên Naverokê' category for Kurdish
            array(
                'name' => 'Vîdyoyên Naverokê',
                'lang' => 'ku',
                'parent_id' => '2', // Sub-category of 'Kürtçe Video Kategorileri'
                'seo' => 'vidyoyen-naveroke',
                'title' => 'Vîdyoyên Naverokê',
                'keywords' => 'vîdyo, naverok',
                'description' => 'Vîdyoyên Naverokê',
                'root' => 'n',
                'user_id' => 1
            ),
        );
        $this->model = Load::model('mod_video_embed_category');
        foreach ($veri AS $key => $value) {
            if ($this->model->exists_name_and_lang($value['name'], $value['lang']) === FALSE) {
                $this->oke($value['name'] . " Eklendi");
                $keys = array_keys($value);
                foreach ($keys AS $k) {
                    $this->model->$k = $value[$k];
                }
                // Set default sira if not provided
                if (!isset($value['order'])) {
                    $this->model->order = '1';
                }
                if ($this->model->save() === FALSE) {
                    $this->error($value['name'] . " Eklenirken Hata Oluştu" . print_r($this->model, TRUE));
                    return FALSE;
                }
                continue;

            } else {
                $this->warning($value['name'] . " Zaten Eklenmiş Durumda");
            }
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
}