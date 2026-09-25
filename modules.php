<?php

include('init.php');
header("Access-Control-Allow-Origin: *");
$mods = Load::model('mods'); // Mod Modeli
$mods->all();
$data = array();


foreach ($mods->toArray() AS $mod) {


    $l=Load::lang($mod ['mod_name']);
    $ad=$l[$mod ['mod_name']]['roll']['ad'];
    $ad=str_replace('Yönetim Rolleri','Modülü',$ad);
    $ad=str_replace('Rolleri','Modülü',$ad);
    //print_r($l);
    $data[$mod ['mod_name']]=array('ad'=>$ad,'sef'=>$mod ['mod_name'],'price'=>2);

}
echo json_encode($data);
//print_r($mods->toJson());