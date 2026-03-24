<?php

class indexMain
{
    public $tema;
    public $db;
    public $site;
    public $lang;
    public $session;
    public $user;
    public $val;
    public $mod;
    public $model;
    public $secenek;
    public $sec;
    function __construct()
    {
        // Tema Kütüphanesini Çağıralım
        // $this->tema = Load::lib('AdminTemplate');
        // // Database Kütüphanesini Yükleyelim
        // $this->db = Load::lib('Database');
        // // Site ayarlarını çekelim
        $this->site = Kayit::getir('site_config');
        // // Session Kütüphanesi
        // $this->session = Load::lib('Session');
        // // User Model
        // $this->user = Load::model('mod_user.user');
    }
}
