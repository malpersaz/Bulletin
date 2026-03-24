<?php
class sessions_model extends ORM
{
    public $_table = "sessions";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $data;
    public $stime;
}