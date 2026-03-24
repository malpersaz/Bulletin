<?php

class mod_news_category extends installerbase implements installerbaseinterface
{
    public $tablolar;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_news_category';
        $this->tablolar = array(
            'mod_news_category'
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
            
            // News
            array(
                'name' => 'Haberler',
                'lang' => 'tr',
                'parent_id' => '1',
                'seo' => 'haberler',
                'title' => 'Haberler',
                'keywords' => 'haberler',
                'description' => 'Haberler',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'News',
                'lang' => 'en',
                'parent_id' => '2',
                'seo' => 'news',
                'title' => 'News',
                'keywords' => 'news',
                'description' => 'News',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'أخبار',
                'lang' => 'ar',
                'parent_id' => '3',
                'seo' => 'أخبار',
                'title' => 'أخبار',
                'keywords' => 'أخبار',
                'description' => 'أخبار',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'Nachrichten',
                'lang' => 'de',
                'parent_id' => '4',
                'seo' => 'nachrichten',
                'title' => 'Nachrichten',
                'keywords' => 'nachrichten',
                'description' => 'Nachrichten',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'Actualites',
                'lang' => 'fr',
                'parent_id' => '5',
                'seo' => 'actualites',
                'title' => 'Actualites',
                'keywords' => 'actualites',
                'description' => 'Actualites',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'Nuce',
                'lang' => 'ku',
                'parent_id' => '6',
                'seo' => 'nuce',
                'title' => 'Nuce',
                'keywords' => 'nuce',
                'description' => 'Nuce',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'هەواڵ',
                'lang' => 'ckb',
                'parent_id' => '7',
                'seo' => 'هەواڵ',
                'title' => 'هەواڵ',
                'keywords' => 'هەواڵ',
                'description' => 'هەواڵ',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'Nyheter',
                'lang' => 'sv',
                'parent_id' => '8',
                'seo' => 'nyheter',
                'title' => 'Nyheter',
                'keywords' => 'nyheter',
                'description' => 'Nyheter',
                'root' => 'n',
                'user_id' => '1'
            ),
            array(
                'name' => 'Heber',
                'lang' => 'zza',
                'parent_id' => '9',
                'seo' => 'heber',
                'title' => 'Heber',
                'keywords' => 'heber',
                'description' => 'Heber',
                'root' => 'n',
                'user_id' => '1'
            ),
        );
        $this->model = Load::model('mod_news_category');
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
