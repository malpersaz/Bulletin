<?php

class mod_category extends installerbase implements installerbaseinterface
{
    public $tablolar;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_category';
        $this->tablolar = array(
            'mod_category'
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
            // Ana Dil Kategorileri (Eski versiyondaki yapıya benzer)
            array(
                'name' => '#TÜRKÇE',
                'lang' => 'tr',
                'parent_id' => '0',
                'seo' => '#',
                'title' => '#',
                'keywords' => '#',
                'description' => '#',
                'root' => 'y',
                'user_id' => '1'
            ),
            array(
                'name' => '#ENGLISH',
                'lang' => 'en',
                'parent_id' => '0',
                'seo' => '#',
                'title' => '#',
                'keywords' => '#',
                'description' => '#',
                'root' => 'y',
                'user_id' => '1'
            ),
            array(
                'name' => '#العربية',
                'lang' => 'ar',
                'parent_id' => '0',
                'seo' => '#',
                'title' => '#',
                'keywords' => '#',
                'description' => '#',
                'root' => 'y',
                'user_id' => '1'
            ),
            array(
                'name' => '#DEUTSCH',
                'lang' => 'de',
                'parent_id' => '0',
                'seo' => '#',
                'title' => '#',
                'keywords' => '#',
                'description' => '#',
                'root' => 'y',
                'user_id' => '1'
            ),
            array(
                'name' => '#FRANÇAIS',
                'lang' => 'fr',
                'parent_id' => '0',
                'seo' => '#',
                'title' => '#',
                'keywords' => '#',
                'description' => '#',
                'root' => 'y',
                'user_id' => '1'
            ),
            array(
                'name' => '#KURDÎ',
                'lang' => 'ku',
                'parent_id' => '0',
                'seo' => '#',
                'title' => '#',
                'keywords' => '#',
                'description' => '#',
                'root' => 'y',
                'user_id' => '1'
            ),
            array(
                'name' => '#سۆرانی',
                'lang' => 'ckb',
                'parent_id' => '0',
                'seo' => '#',
                'title' => '#',
                'keywords' => '#',
                'description' => '#',
                'root' => 'y',
                'user_id' => '1'
            ),
            array(
                'name' => '#SVENSKA',
                'lang' => 'sv',
                'parent_id' => '0',
                'seo' => '#',
                'title' => '#',
                'keywords' => '#',
                'description' => '#',
                'root' => 'y',
                'user_id' => '1'
            ),
            array(
                'name' => '#ZAZAKÎ',
                'lang' => 'zza',
                'parent_id' => '0',
                'seo' => '#',
                'title' => '#',
                'keywords' => '#',
                'description' => '#',
                'root' => 'y',
                'user_id' => '1'
            ),
            
            // Contents
            array(
                'name' => 'İçerikler',
                'lang' => 'tr',
                'parent_id' => '1',
                'seo' => 'İçerikler',
                'title' => 'İçerikler',
                'keywords' => 'içerikler',
                'description' => 'İçerikler',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'Contents',
                'lang' => 'en',
                'parent_id' => '2',
                'seo' => 'contents',
                'title' => 'Contents',
                'keywords' => 'contents',
                'description' => 'Contents',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'محتويات',
                'lang' => 'ar',
                'parent_id' => '3',
                'seo' => 'محتويات',
                'title' => 'محتويات',
                'keywords' => 'محتويات',
                'description' => 'محتويات',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'Inhalte',
                'lang' => 'de',
                'parent_id' => '4',
                'seo' => 'inhalte',
                'title' => 'Inhalte',
                'keywords' => 'inhalte',
                'description' => 'Inhalte',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'Contenus',
                'lang' => 'fr',
                'parent_id' => '5',
                'seo' => 'contenus',
                'title' => 'Contenus',
                'keywords' => 'contenus',
                'description' => 'Contenus',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'Naverok',
                'lang' => 'ku',
                'parent_id' => '6',
                'seo' => 'naverok',
                'title' => 'Naverok',
                'keywords' => 'naverok',
                'description' => 'Naverok',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'ناوەڕۆک',
                'lang' => 'ckb',
                'parent_id' => '7',
                'seo' => 'ناوەڕۆک',
                'title' => 'ناوەڕۆک',
                'keywords' => 'ناوەڕۆک',
                'description' => 'ناوەڕۆک',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'Innehåll',
                'lang' => 'sv',
                'parent_id' => '8',
                'seo' => 'Innehåll',
                'title' => 'Innehåll',
                'keywords' => 'Innehåll',
                'description' => 'Innehåll',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'Zerre',
                'lang' => 'zza',
                'parent_id' => '9',
                'seo' => 'zerre',
                'title' => 'Zerre',
                'keywords' => 'zerre',
                'description' => 'Zerre',
                'root' => 'n',
                'user_id' => '1'
            ),
        );
        $this->model = Load::model('mod_category');
        foreach ($veri AS $key => $value){
            if ($this->model->exists_name($value['name']) === FALSE){
                $this->oke($value['name']." Eklendi");
                $keys = array_keys($value);
                foreach ($keys AS $k){
                    $this->model->$k = $value[$k];
                }
                // Set default order if not provided
                if (!isset($value['order'])) {
                    $this->model->order = '1';
                }
                if ($this->model->save()===FALSE){
                    $this->error($value['name']." Eklenirken hata oluştu");
                    return FALSE;
                }
                continue;
            }
            else
            {
                $this->warning($value['name']." daha önce eklenmiş");
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