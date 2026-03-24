<?php

class sent_model extends ORM
{
    public $_table = "mod_user_pm_sent";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $subject;
    public $message;
    public $created_at;
    public $reader_id;
    public $user_id;

    /**
     * Send message to a single user
     */
    public function send_user_message($subject, $message, $reader_id, $user_id)
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->created_at = date('Y-m-d H:i:s');
        $this->reader_id = $reader_id;
        $this->user_id = $user_id;

        $inbox_model = Load::model('mod_user_pm.inbox');
        $inbox_model->subject = $subject;
        $inbox_model->message = $message;
        $inbox_model->status = 'n';
        $inbox_model->created_at = date('Y-m-d H:i:s');
        $inbox_model->reader_id = $reader_id;
        $inbox_model->user_id = $user_id;
        
        if ($this->save() && $inbox_model->save()) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Send message to a specific user group
     */
    public function send_user_group_message($subject, $message, $group_id, $user_id)
    {
        $user = Load::model('mod_user');
        $users = $user->where_group_id($group_id)->all()->toArray();
        if (is_array($users)) {
            foreach ($users AS $u) {
                if ($this->send_user_message($subject, $message, $u['id'], $user_id)) {
                    continue;
                }
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Send message to all members
     */
    public function send_all_user_message($subject, $message, $user_id)
    {
        $user = Load::model('mod_user');
        $users = $user->all()->toArray();
        if (is_array($users)) {
            foreach ($users AS $u) {
                if ($this->send_user_message($subject, $message, $u['id'], $user_id)) {
                    continue;
                }
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Send bulk message to specific users
     */
    public function send_bulk_message($subject, $message, $users, $user_id)
    {
        if (is_array($users)) {
            foreach ($users AS $u) {
                if ($this->send_user_message($subject, $message, $u, $user_id)) {
                    continue;
                }
                return FALSE;
            }
        }
        return TRUE;
    }
}
