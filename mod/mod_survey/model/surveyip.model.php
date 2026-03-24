<?php
class anketip_model extends ORM
{
    public $_table = "mod_surveyip";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $anket_id;
    public $ip;
}