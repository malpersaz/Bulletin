<?php

class mod_headline_helper extends indexBase
{
    public function __construct()
    {
        parent::__construct();
    }
    public function all($limit)
    {
        $fullmanset = Load::model("mod_headline");
        $fullmanset->select('name,image')->limit($limit)->where_status_and_lang('y', $this->site['lang'])->order('created_at', 'DESC');
        $fullmanset->gR();
        return $fullmanset->toArray();
        // $this->tema->ctx_set('fullmanset', $fullmanset->toArray());
        // unset($fullmanset);
    }
}
