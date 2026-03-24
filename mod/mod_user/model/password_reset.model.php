<?php
class password_reset_model extends ORM
{
    public $_table = "mod_user_password_reset";
    public $_pk = "email";
    public $email;
    public $code;
    public $created_at;
}
        