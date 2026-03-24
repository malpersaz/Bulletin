<?php

class mod_user extends installerbase implements installerbaseinterface
{
    public $tablolar;
    public $model;
    public $lang;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_user';
        $this->tablolar = array(
            'mod_user',
            'mod_user_activation',
            'mod_user_settings',
            'mod_user_log',
            'mod_user_perm',
            'mod_user_roll',
            'mod_user_password_reset',
            'sessions'
        );
        $this->rolls = array(
            'add', 'edit', 'delete', 'list', 'acl', 'settings', 'link'
        );
        $this->lang = Load::lang('mod_user');
    }

    public function install()
    {
        // Create Tables
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        // Add Module
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        // Add Roles and Permissions
        if ($this->insert_roll() === FALSE) {
            return FALSE;
        }


        return TRUE;
    }

    public function install_finish()
    {
        if ($this->install_sql_file('users.sql') === FALSE) {
            $this->error($this->lang['mod_user']['installer']['sql_error']);
            return FALSE;
        }
        // Add User
        if ($this->adduser() === FALSE) {
            return FALSE;
        }
        // Add User Settings
        if ($this->loaduserconfig() === FALSE) {
            return FALSE;
        }
        return TRUE;
    }

    private function loaduserconfig()
    {
        $model = Load::model('mod_user.mod_user_settings');
        $conf = array(
            array('k' => 'activation', 'v' => '2'),
            array('k' => 'status', 'v' => '1'),
            array('k' => 'group_id', 'v' => '3'),
            array('k' => 'check_user_login', 'v' => '1')
        );

        foreach ($conf AS $c) {

            $model->where_k($c['k'])->getResult(TRUE, TRUE);

            if ($model->getSize() === 0) {
                $model->k = $c['k'];
                $model->v = $c['v'];
                if ($model->save()) {
                    $this->oke(sprintf($this->lang['mod_user']['installer']['settings_added'], $c['k']));
                    continue;
                } else {
                    $this->error($this->lang['mod_user']['installer']['settings_error'] . print_r($model, TRUE));
                    return FALSE;
                }

            } else {
                $this->warning(sprintf($this->lang['mod_user']['installer']['settings_exists'], $c['k']));
            }
        }
        return TRUE;
    }

    private function adduser()
    {
        $model = Load::model('mod_user');
        $model->id = 1;
        $model->name = "Amed";
        $model->last_name = "Framework";
        $model->group_id = 1;
        $model->username = "admin";
        $model->password = md5("admin123");
        $model->email = "info@akmsoftware.co.uk";
        $model->phone = "";
        $model->updated_at = "1990-01-01 00:00:00";
        $model->ip = "127.0.0.1";
        $model->image = "";
        $model->city = "";
        $model->blog_name = "";
        $model->about = "";
        $model->status = "y";
        $model->created_at = NOW;

        $model->where_id($model->id)->getResult(TRUE, TRUE);
        if ($model->getSize() === 0) {
            if ($model->save()) {
                $this->oke(sprintf($this->lang['mod_user']['installer']['user_added'], $model->username));
            } else {
                $this->error(sprintf($this->lang['mod_user']['installer']['user_error'], $model->username));
                return FALSE;
            }
        } else {
            $this->warning(sprintf($this->lang['mod_user']['installer']['user_exists'], $model->username));
        }


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
