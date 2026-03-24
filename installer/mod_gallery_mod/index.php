<?php

class mod_gallery_mod extends installerbase implements installerbaseinterface
{
    public $tablolar;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_gallery_mod';
        $this->tablolar = array(
            'mod_gallery_mod'
        );
        $this->rolls = array(
            'add', 'edit',  'delete', 'list', 'moderate_edit','moderate_delete'
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

        if ($this->add_gallery_mods() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    public function add_gallery_mods()
    {
        $veri = array(
            array(
                'name' => 'İçerik Galerileri',
                'mod_name' => 'mod_content',
                'mod_key' => 'id',
                'mod_value' => 'gallery_id',
                'user_id' => 1
            ),
            array(
                'name' => 'Sayfa Galerileri',
                'mod_name' => 'mod_page',
                'mod_key' => 'id',
                'mod_value' => 'gallery_id',
                'user_id' => 1
            ),
            array(
                'name' => 'Announcement Galerileri',
                'mod_name' => 'mod_announcements',
                'mod_key' => 'id',
                'mod_value' => 'gallery_id',
                'user_id' => 1
            ),
            array(
                'name' => 'Proje Galerileri',
                'mod_name' => 'mod_proje',
                'mod_key' => 'id',
                'mod_value' => 'gallery_id',
                'user_id' => 1
            ),
                        array(
                'name' => 'Video Galerileri',
                'mod_name' => 'mod_video_embed',
                'mod_key' => 'id',
                'mod_value' => 'gallery_id',
                'user_id' => 1
            )
        );

        $this->model = Load::model('mod_gallery_mod');
        foreach ($veri AS $key => $value) {
            if ($this->model->exists_mod_name($value['mod_name']) === FALSE) {
                $this->oke(" -----<b>" . $value['name']."</b> için galeri modu eklendi");
                $keys = array_keys($value);
                foreach ($keys AS $k) {
                    $this->model->$k = $value[$k];
                }
                if($this->model->save()===FALSE){
                    $this->error(" -----<b>" . $value['name']."</b> için galeri modu eklenirken hata oluştu".print_r($this->model,TRUE));
                    return FALSE;
                }
                continue;

            }else{
                $this->warning(" -----<b>" . $value['name']."</b> için galeri modu daha önce eklenmiş");
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