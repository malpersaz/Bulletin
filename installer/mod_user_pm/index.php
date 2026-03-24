<?php

class mod_user_pm extends installerbase implements installerbaseinterface
{
    public $tablolar;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_user_pm';
        $this->tablolar = array(
            'mod_user_pm_inbox',
            'mod_user_pm_sent'
        );
        $this->rolls = array(
            'send', 'bulk', 'group_bulk', 'all'
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
        // Özel MEsaj Ekleleyim
        if ($this->addmessage() === FALSE) {
            return FALSE;
        }
        return TRUE;
    }

    public function addmessage()
    {
        $message_data = array(
            'subject' => 'Test Message',
            'message' => 'Example Private Message',
            'reader_id' => 1,
            'user_id' => 1
        );


        $this->model = Load::model('mod_user_pm.sent');
        $this->model->all();
        if($this->model->getSize()!==0){
            return TRUE;
        }

        if ($this->model->send_user_message($message_data['subject'], $message_data['message'], $message_data['reader_id'], $message_data['user_id'])) {
            $this->oke("Example Private Message Added");
        } else {
            $this->error("Error Occurred While Adding Example Message" . print_r($this->model, TRUE));
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
