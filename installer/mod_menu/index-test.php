<?php

class mod_menu extends installerbase implements installerbaseinterface
{

    public $model;
    public $tablolar;
    public $mod;

    public $defaultvalues = [
        'target' => 'n',
        'status' => 'y',
        'acl_id' => '0',
        'user_id' => '1',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_menu';
        $this->tablolar = array(
            'mod_menu'
        );
        $this->rolls = array(
            'ekle',
            'duzenle',
             'delete',
            'listele',
            'moderate_edit',
            'moderate_delete'
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

        // Örnek menüleri ekleyelim
        if ($this->addmenu() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    public function addmenu()
    {
        // Root menüleri oluşturalım
        $rootMenus = [];

        for ($i = 1; $i < 10; $i++) {
            $rootMenus[] = array(
                'name' => 'menu' . $i,
                'url' => '##',
                'parent_id' => '0',
                'target' => 'n',
                'status' => 'n',
                'lang' => 'en',
                'acl_id' => '0',
                'user_id' => '1'
            );
        }

        $veri = array(
            // English Main Menu
            array(
                'name' => '#MainMenu',
                'url' => '##',
                'parent_id' => '1',
                'target' => 'n',
                'status' => 'n',
                'lang' => 'en',
                'acl_id' => '0',
                'user_id' => '1',
                'submenus' => [
                    [
                        'name' => 'Home',
                        'url' => 'en/'
                    ],
                    [
                        'name' => 'Contact',
                        'url' => 'en/contact'
                    ],

                ]

            ),
            // Kurdish Main Menu
            array(
                'name' => '#SerPêşek',
                'url' => '##',
                'parent_id' => '1',
                'target' => 'n',
                'status' => 'n',
                'lang' => 'ku',
                'acl_id' => '0',
                'user_id' => '1'
            ),
            // Deutsh Main Menu
            array(
                'name' => '#Hauptmenü',
                'url' => '##',
                'parent_id' => '1',
                'target' => 'n',
                'status' => 'n',
                'lang' => 'de',
                'acl_id' => '0',
                'user_id' => '1'
            ),
            // Arabîc Main Menu
            array(
                'name' => '#القائمةالرئيسية',
                'url' => '##',
                'parent_id' => '1',
                'target' => 'n',
                'status' => 'n',
                'lang' => 'ar',
                'acl_id' => '0',
                'user_id' => '1'
            ),
            // French Main Menu
            array(
                'name' => '#MenuPrincipal',
                'url' => '##',
                'parent_id' => '1',
                'target' => 'n',
                'status' => 'n',
                'lang' => 'fr',
                'acl_id' => '0',
                'user_id' => '1'
            ),
            // Turkish Main Menu
            array(
                'name' => '#AnaMenü',
                'url' => '##',
                'parent_id' => '1',
                'target' => 'n',
                'status' => 'n',
                'lang' => 'tr',
                'acl_id' => '0',
                'user_id' => '1'
            ),
        );


        $veri = array_merge($rootMenus, $veri);


        foreach ($veri as $key => $value) {
            $menuModel = Load::model('mod_menu'); // Her seferinde yeni model
            if ($menuModel->exists_name_and_lang($value['name'], $value['lang']) === FALSE) {
                $this->oke(" -----<b>" . $value['name'] . "</b> menüsü ekleniyor...");
                $keys = array_keys($value);
                foreach ($keys as $k) {
                    if ($k !== 'submenus') { // submenus dizisi değilse ata
                        $menuModel->$k = $value[$k];
                    }
                }

                // Model içeriğini kontrol et
                // $this->oke(" -----Model verileri: " . print_r($menuModel, TRUE));

                $saveResult = $menuModel->save();
                if ($saveResult === FALSE) {
                    $this->hata(" -----<b>" . $value['name'] . "</b>  menüsü eklenirken hata oluştu. Save result: " . var_export($saveResult, TRUE));
                    $this->hata(" -----Model durumu: " . print_r($menuModel, TRUE));
                    return FALSE;
                } else {
                    $this->oke(" -----<b>" . $value['name'] . "</b> menüsü başarıyla kaydedildi. ID: " . $menuModel->id);
                }

                if (isset($value['submenus']) && count($value['submenus']) > 0) {

                    foreach ($value['submenus'] as $submenu) {
                        $me = Load::model('mod_menu');
                        $me->name = $submenu['name'];
                        $me->url = $submenu['url'];
                        $me->parent_id = $menuModel->id;
                        $me->lang = $menuModel->lang;

                        foreach ($this->defaultvalues as $k => $v) {
                            $me->$k = $v;
                        }

                        $this->oke(" -----Alt menü ekleniyor: " . $submenu['name']);
                        if ($me->save() === FALSE) {
                            $this->hata(" -----Alt menü eklenirken hata: " . $submenu['name']);
                        } else {
                            $this->oke(" -----Alt menü başarıyla eklendi: " . $submenu['name'] . " (ID: " . $me->id . ")");
                        }
                    }
                }



                continue;
            } else {
                $this->warning(" -----<b>" . $value['name'] . "</b> menüsü daha önce eklenmiş");
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
