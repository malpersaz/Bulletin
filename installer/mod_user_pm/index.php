<?php

class mod_user_pm extends installerbase implements installerbaseinterface
{
    public $tables;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_user_pm';
        $this->tables = array(
            'mod_user_pm_inbox',
            'mod_user_pm_sent'
        );
        $this->roles = array(
            'send', 'bulk', 'group_bulk', 'all'
        );
    }

    public function install()
    {
        // Create tables
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        // Add module
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        // Insert roles and permissions
        if ($this->insert_roles() === FALSE) {
            return FALSE;
        }
        // Add private message
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
            $this->success("Example Private Message Added");
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
        $this->delete_roles();
        $this->remove_perm();
        return TRUE;
    }
}
