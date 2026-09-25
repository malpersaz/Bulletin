<?php

class mod_faq_mod extends installerbase implements installerbaseinterface
{
    public $tables;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_faq_mod';
        $this->tables = array(
            'mod_faq_mod'
        );
        $this->roles = array(
            'add', 'edit', 'delete', 'list', 'moderate_edit', 'moderate_delete'
        );
    }

    public function install()
    {
        // Create tables.
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        // Register the module.
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        // Register roles and permissions.
        if ($this->insert_roles() === FALSE) {
            return FALSE;
        }

        if ($this->add_faq_mods() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    public function add_faq_mods()
    {
        $records = array(
            array(
                'name' => 'Content FAQs',
                'mod_name' => 'mod_content',
                'mod_key' => 'id',
                'mod_value' => 'name',
                'user_id' => 1
            ),
            array(
                'name' => 'News FAQs',
                'mod_name' => 'mod_news',
                'mod_key' => 'id',
                'mod_value' => 'name',
                'user_id' => 1
            ),
            array(
                'name' => 'Page FAQs',
                'mod_name' => 'mod_page',
                'mod_key' => 'id',
                'mod_value' => 'name',
                'user_id' => 1
            ),
            array(
                'name' => 'Publication FAQs',
                'mod_name' => 'mod_publication',
                'mod_key' => 'id',
                'mod_value' => 'name',
                'user_id' => 1
            )
        );

        $this->model = Load::model('mod_faq_mod');
        foreach ($records as $record) {
            if ($this->model->exists_mod_name($record['mod_name']) === FALSE) {
                $this->success(" -----<b>" . $record['name'] . "</b> FAQ module registered");
                $keys = array_keys($record);
                foreach ($keys as $key) {
                    $this->model->$key = $record[$key];
                }
                if ($this->model->save() === FALSE) {
                    $this->error(" -----<b>" . $record['name'] . "</b> failed to register FAQ module" . print_r($this->model, TRUE));
                    return FALSE;
                }
                continue;
            } else {
                $this->warning(" -----<b>" . $record['name'] . "</b> FAQ module already exists");
            }
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
