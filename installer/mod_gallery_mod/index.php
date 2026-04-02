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
        // Create tables.
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        // Register the module.
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        // Register roles and permissions.
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
        $records = array(
            array(
                'name' => 'Content Galleries',
                'mod_name' => 'mod_content',
                'mod_key' => 'id',
                'mod_value' => 'gallery_id',
                'user_id' => 1
            ),
            array(
                'name' => 'Page Galleries',
                'mod_name' => 'mod_page',
                'mod_key' => 'id',
                'mod_value' => 'gallery_id',
                'user_id' => 1
            ),
            array(
                'name' => 'Announcement Galleries',
                'mod_name' => 'mod_announcements',
                'mod_key' => 'id',
                'mod_value' => 'gallery_id',
                'user_id' => 1
            ),
            array(
                'name' => 'Project Galleries',
                'mod_name' => 'mod_proje',
                'mod_key' => 'id',
                'mod_value' => 'gallery_id',
                'user_id' => 1
            ),
                        array(
                'name' => 'Video Galleries',
                'mod_name' => 'mod_video_embed',
                'mod_key' => 'id',
                'mod_value' => 'gallery_id',
                'user_id' => 1
            )
        );

        $this->model = Load::model('mod_gallery_mod');
        foreach ($records as $key => $value) {
            if ($this->model->exists_mod_name($value['mod_name']) === FALSE) {
                $this->oke(" -----<b>" . $value['name'] . "</b> gallery module registered");
                $keys = array_keys($value);
                foreach ($keys as $k) {
                    $this->model->$k = $value[$k];
                }
                if ($this->model->save() === FALSE) {
                    $this->error(" -----Failed to register gallery module for <b>" . $value['name'] . "</b>" . print_r($this->model, TRUE));
                    return FALSE;
                }
                continue;

            } else {
                $this->warning(" -----Gallery module for <b>" . $value['name'] . "</b> already exists");
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
