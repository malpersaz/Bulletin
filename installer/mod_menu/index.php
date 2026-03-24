<?php

class mod_menu extends installerbase implements installerbaseinterface
{
    public $tablolar;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_menu';
        $this->tablolar = array(
            'mod_menu'
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

	        // Örnek menüleri ekleyelim
	        if ($this->addmenu() === FALSE) {
	            return FALSE;
	        }

	        return TRUE;
    }

    public function addmenu()
    {
        $veri = array(
            // English Main Menu
            array(
                'id' => '1',
                'name' => '#MainMenu',
                'url' => '##',
                'parent_id' => '0',
                'order' => '1',
                'target' => 'n',
                'status' => 'n',
                'lang' => 'en',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // Kurdish Main Menu
            array(
                'id' => '2',
                'name' => '#SerPêşek',
                'url' => '##',
                'parent_id' => '0',
                'order' => '1',
                'target' => 'n',
                'status' => 'n',
                'lang' => 'ku',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // Deutsh Main Menu
            array(
                'id' => '3',
                'name' => '#Hauptmenü',
                'url' => '##',
                'parent_id' => '0',
                'target' => 'n',
                'status' => 'n',
                'lang' => 'de',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // Arabîc Main Menu
            array(
                'id' => '4',
                'name' => '#القائمةالرئيسية',
                'url' => '##',
                'parent_id' => '0',
                'target' => 'n',
                'status' => 'n',
                'lang' => 'ar',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // French Main Menu
            array(
                'id' => '5',
                'name' => '#MenuPrincipal',
                'url' => '##',
                'parent_id' => '0',
                'target' => 'n',
                'status' => 'n',
                'lang' => 'fr',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            array(
                'id' => '6',
                'name' => '#AnaMenü',
                'url' => '##',
                'parent_id' => '0',
                'target' => 'n',
                'status' => 'n',
                'lang' => 'tr',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // Zazaki Main Menu
            array(
                'id' => '7',
                'name' => '#MenuyoSerên',
                'url' => '##',
                'parent_id' => '0',
                'order' => '1',
                'target' => 'n',
                'status' => 'n',
                'lang' => 'zza',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // English Menu
            array(
                'id' => '8',
                'name' => 'Homepage',
                'url' => 'en/index',
                'parent_id' => '1',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'en',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // Kürtçe Menü
            array(
                'id' => '9',
                'name' => 'Serûpel',
                'url' => 'ku/index',
                'parent_id' => '2',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'ku',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // Deutche Menu
            array(
                'id' => '10',
                'name' => 'Startseite',
                'url' => 'de/index',
                'parent_id' => '3',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'de',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // Arabic Menu
            array(
                'id' => '11',
                'name' => 'الصفحة الرئيسية',
                'url' => 'ar/index',
                'parent_id' => '4',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'ar',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // French Menu
            array(
                'id' => '12',
                'name' => 'Accueil',
                'url' => 'fr/index',
                'parent_id' => '5',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'fr',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // Turkish Menu
            array(
                'id' => '13',
                'name' => 'Anasayfa',
                'url' => 'tr/index',
                'parent_id' => '6',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'tr',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // English Menu
            array(
                'id' => '14',
                'name' => 'Contact',
                'url' => 'en/contact',
                'parent_id' => '1',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'en',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // Kurdish Menu
            array(
                'id' => '15',
                'name' => 'Têkilî',
                'url' => 'ku/contact',
                'parent_id' => '2',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'ku',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // Deutsh Menu
            array(
                'id' => '16',
                'name' => 'Kontakt',
                'url' => 'de/contact',
                'parent_id' => '3',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'de',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // Arabic Menu
            array(
                'id' => '17',
                'name' => 'اتصال',
                'url' => 'ar/contact',
                'parent_id' => '4',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'ar',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
                        // French Menu
            array(
                'id' => '18',
                'name' => 'Contact',
                'url' => 'fr/contact',
                'parent_id' => '5',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'fr',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            array(
                'id' => '19',
                'name' => 'İçerikler',
                'url' => 'tr/contents',
                'parent_id' => '6',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'tr',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            array(
                'id' => '20',
                'name' => 'Contents',
                'url' => 'en/contents',
                'parent_id' => '1',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'en',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            array(
                'id' => '21',
                'name' => 'İletişim',
                'url' => 'tr/contact',
                'parent_id' => '6',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'tr',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            // Zazaki Menu Items
            array(
                'id' => '22',
                'name' => 'Pela Serî',
                'url' => 'zza/index',
                'parent_id' => '7',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'zza',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            array(
                'id' => '23',
                'name' => 'Îrtibat',
                'url' => 'zza/contact',
                'parent_id' => '7',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'zza',
                'acl_id' => '0',
                'user_id'=> '1'
            ),
            array(
                'id' => '24',
                'name' => 'Zerre',
                'url' => 'zza/contents',
                'parent_id' => '7',
                'target' => 'n',
                'status' => 'y',
                'lang' => 'zza',
                'acl_id' => '0',
                'user_id'=> '1'
            )
        );

        $this->model = Load::model('mod_menu');
        foreach ($veri AS $key => $value) {
            if ($this->model->exists_name_and_lang($value['name'], $value['lang']) === FALSE) {
                $this->oke(" -----<b>" . $value['name']."</b> menüsü eklendi");
                $keys = array_keys($value);
                foreach ($keys AS $k) {
                    $this->model->$k = $value[$k];
                }
                // Set default order if not provided
                if (!isset($value['order'])) {
                    $this->model->order = '1';
                }
                if($this->model->save()===FALSE){
                    $this->error(" -----<b>" . $value['name']."</b>  menüsü eklenirken hata oluştu".print_r($this->model,TRUE));
                    return FALSE;
                }
                continue;

            }else{
                $this->warning(" -----<b>" . $value['name']."</b> menüsü daha önce eklenmiş");
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