<?php
include('../koberz.init.php');
include lib_dir.'Make_Model.class.php';
$maker=new Make_Model();
$maker->pk='id';
echo $maker->get_source($_GET['t']);
//print_r($maker);