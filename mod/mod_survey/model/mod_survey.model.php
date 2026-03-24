<?php

class mod_survey_model extends ORM
{
    public $_table = "mod_survey";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $lang;
    public $question;
    public $status;
    public $acl_id;
    public $user_id;
}