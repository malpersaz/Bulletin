<?php

class mods extends installerbase implements installerbaseinterface
{


    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mods';
    }

    public function install()
    {
        if ($this->check_table_exists('mods') === TRUE) {
            $this->warning(" 'mods' tablosu veritabanında Yüklü");
        } elseif ($this->install_sql_file('mods.sql')) {
            $this->oke(" 'mods.sql' dosyası veritabanına eklendi");
        }else{
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