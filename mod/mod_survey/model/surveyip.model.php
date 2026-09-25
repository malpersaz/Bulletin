<?php
class surveyip_model extends ORM
{
    public $_table = "mod_surveyip";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $survey_id;
    public $ip;
}