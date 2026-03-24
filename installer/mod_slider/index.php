<?php

class mod_slider extends installerbase implements installerbaseinterface
{
    public $tablolar;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_slider';
        $this->tablolar = array(
            'mod_slider'
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