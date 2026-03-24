<?php
/**
 * Resim Model Dosyası
 */

class image_model extends ORM

{

    public $id;
    public $vid;
    public $url;
    public $mod;
    public $created_at;
    protected $_table = "image";
    protected $_pk = "id";

}
