<?php
class mod_redirect_model extends ORM
{
    public $_table = "mod_redirect";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $old_url;
    public $new_url;
    public $status_code;
    public $created_at;
}
