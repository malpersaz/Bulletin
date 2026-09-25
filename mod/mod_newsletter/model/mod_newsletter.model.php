<?php
/**
 * AKM Software Business CMS Framework
 * Newsletter Module Model
 * mod/mod_newsletter/model/mod_newsletter.model.php
 */

class mod_newsletter_model extends ORM
{
    public $_table = "mod_newsletter";
    public $_pk = "id";
    public $_ai = "id";

    public $id;
    public $email;
    public $lang;
    public $source;
    public $status;
    public $ip_address;
    public $created_at;
    public $updated_at;
}
