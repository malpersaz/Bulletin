<?php

class mod_socialmedia_post_link_model extends ORM
{
    public $_table = 'mod_socialmedia_post_link';
    public $_pk = 'id';
    public $_ai = 'id';
    public $id;
    public $socialmedia_post_id;
    public $mod_name;
    public $vid;
    public $sort_order;
    public $created_at;
}
