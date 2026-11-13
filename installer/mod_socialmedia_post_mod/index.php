<?php

class mod_socialmedia_post_mod extends installerbase implements installerbaseinterface
{
    public $tables;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_socialmedia_post_mod';
        $this->tables = [
            'mod_socialmedia_post_mod',
        ];
        $this->roles = [
            'add', 'edit', 'delete', 'list', 'moderate_edit', 'moderate_delete',
        ];
    }

    public function install()
    {
        if ($this->create_tables() === false) {
            return false;
        }
        if ($this->insert_mods() === false) {
            return false;
        }
        if ($this->insert_roles() === false) {
            return false;
        }
        if ($this->add_social_post_mods() === false) {
            return false;
        }

        return true;
    }

    public function add_social_post_mods()
    {
        $records = [
            [
                'name' => 'News Social Posts',
                'mod_name' => 'mod_news',
                'mod_key' => 'id',
                'mod_value' => 'name',
                'user_id' => 1,
            ],
            [
                'name' => 'Page Social Posts',
                'mod_name' => 'mod_page',
                'mod_key' => 'id',
                'mod_value' => 'name',
                'user_id' => 1,
            ],
            [
                'name' => 'Publication Social Posts',
                'mod_name' => 'mod_publication',
                'mod_key' => 'id',
                'mod_value' => 'name',
                'user_id' => 1,
            ],
            [
                'name' => 'Event Social Posts',
                'mod_name' => 'mod_event',
                'mod_key' => 'id',
                'mod_value' => 'name',
                'user_id' => 1,
            ],
            [
                'name' => 'Announcement Social Posts',
                'mod_name' => 'mod_announcements',
                'mod_key' => 'id',
                'mod_value' => 'name',
                'user_id' => 1,
            ],
        ];

        $this->model = Load::model('mod_socialmedia_post_mod');
        $runtime = Load::lib('ModuleRuntime');
        foreach ($records as $record) {
            $hostModule = $record['mod_name'];
            if (!$runtime->tableExists($hostModule)) {
                continue;
            }
            $this->model->flush();
            if ($this->model->exists_mod_name($hostModule) === false) {
                $this->success(" -----<b>" . $record['name'] . "</b> Social post bridge module registered");
                foreach ($record as $key => $value) {
                    $this->model->$key = $value;
                }
                if ($this->model->save() === false) {
                    $this->error(" -----<b>" . $record['name'] . "</b> failed to register social post bridge module");
                    return false;
                }
            } else {
                $this->warning(" -----<b>" . $record['name'] . "</b> Social post bridge module already exists");
            }
        }
        return true;
    }

    public function uninstall()
    {
        $this->remove_tables();
        $this->remove_mods();
        $this->delete_roles();
        $this->remove_perm();
        return true;
    }
}
