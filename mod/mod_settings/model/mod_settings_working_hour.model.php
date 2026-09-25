<?php
class mod_settings_working_hour_model extends ORM
{
    public $_table = "mod_settings_working_hour";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $day_of_week;
    public $is_closed;
    public $open_time;
    public $close_time;
    public $break_start_time;
    public $break_end_time;
    public $note;
    public $updated_at;
}
