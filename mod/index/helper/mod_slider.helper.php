<?php

class mod_slider_helper extends indexBase
{
    public function __construct()
    {
        parent::__construct();
    }
    public function enson_slider($limit)
    {
        $slider = Load::model("mod_slider");
        $slider->select('id,name,image,image_mobile,target,url,description')->limit($limit)->where_status_and_lang('y', $this->site['lang'])->order('created_at', 'DESC');
        $slider->gR();
        return $slider->toArray();
    }
}
