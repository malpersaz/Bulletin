<?php

class mod_user_group extends installerbase implements installerbaseinterface
{
    public $tablolar;
    public $model;

    public function __construct()
    {
        parent::__construct();
        // Module Name
        $this->mod = 'mod_user_group';
        // Roles
        $this->rolls = array(
            'add', 'edit',  'delete', 'list', 'move', 'moderate'
        );
        // Tables
        $this->tablolar=array(
            'mod_user_group'
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
        // Add User Groups
        if ($this->addgroups() === FALSE) {
            return FALSE;
        }
        return TRUE;
    }



    private function addgroups()
    {
        $model = Load::model('mod_user_group');
        $groups = array(
            array(
                'group_id' => '1',
                'name' => 'Site Management',
                'order' => '1',
                'parent_id' => '0',
                'rolls' => '{}',
                'user_id' => '1',
            ),
            array(
                'group_id' => '2',
                'name' => 'Editors',
                'order' => '2',
                'parent_id' => '1',
                'rolls' => '{}',
                'user_id' => '1',
            ),
            array(
                'group_id' => '3',
                'name' => 'Normal User',
                'order' => '3',
                'parent_id' => '1',
                'rolls' => '{}',
                'user_id' => '1',
            ),
        );

        foreach ($groups as $group) {
            $model->where_group_id($group['group_id'])->getResult(TRUE, TRUE);
            $model->name = $group['name'];
            $model->order = $group['order'];
            $model->parent_id = $group['parent_id'];
            $model->rolls = $group['rolls'];
            $model->user_id = $group['user_id'];

            if ($model->getSize() === 0) {
                if ($model->save()) {
                    $this->oke("-----<b>" . $model->name . "</b> - User Group Added to Database!");
                } else {
                    $this->error(" ERROR OCCURRED WHILE ADDING USER GROUPS" . print_r($model, TRUE));
                    return FALSE;
                }
            } else {
                $this->warning("-----<b>" . $model->name . "</b> - User Group Already Exists in Database");
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
