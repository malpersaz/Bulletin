<?php
class surveyselect_model extends ORM
{
    public $_table = "mod_surveyselect";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $survey_id;
    public $option_text;
    public $hit;
}
