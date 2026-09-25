<?php
class mod_settings_closure_model extends ORM
{
    public $_table = "mod_settings_closure";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $start_date;
    public $end_date;
    public $is_full_day;
    public $open_time;
    public $close_time;
    public $reason;
    public $closure_type;
    public $is_recurring_yearly;
    public $created_at;
}
