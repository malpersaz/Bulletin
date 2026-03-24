<?php
class activation_model extends ORM
{
    public $_table = "mod_user_activation";
    public $_pk = "email";
    public $email;
    public $code;
    public $created_at;
}
