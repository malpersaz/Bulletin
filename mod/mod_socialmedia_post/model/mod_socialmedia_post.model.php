<?php

class mod_socialmedia_post_model extends ORM
{
    public $_table = 'mod_socialmedia_post';
    public $_pk = 'id';
    public $_ai = 'id';
    public $id;
    public $lang;
    public $provider;
    public $media_type;
    public $post_id;
    public $name;
    public $handle;
    public $content;
    public $url;
    public $embed_html;
    public $image;
    public $status;
    public $acl_id;
    public $user_id;
    public $created_at;
}
