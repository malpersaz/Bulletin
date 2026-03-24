<?php

class mod_popup_helper extends indexBase
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getLast()
    {
        $time = date('Y-m-d H:i:s', time());
        $sql = "SELECT id,name,content,link,sure,image,video FROM mod_popup WHERE status = 'y' AND created_at < '" . $time . "' AND etarih > '" . $time . "' AND lang='" . $this->site['lang'] . "' LIMIT 1";
        $pop = Load::model('mod_popup');
        $pop->fetch_array($sql);
        if ($pop->getSize() != 0) {
            $r = $pop->toArray();
            return $r[0];
            //$this->tema->ctx_set('pop',$r[0]);
        }
        return false;
    }
}
