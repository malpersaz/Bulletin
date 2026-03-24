<?php
class index_mod_slider_helper
{
    public function enson_slider($limit)
    {
        $slider = Load::model('mod_slider');
        $slider->where_status('y');
        $slider->order('created_at', 'DESC');
        $slider->limit($limit);
        $slider->gR();
        return $slider->toArray();
    }
}
