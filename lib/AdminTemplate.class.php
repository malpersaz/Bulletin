<?php
/*
 * Koberz İçerik Yönetim Sistemi
 * @ 2010
 * Loader Sınıfı
 * Template.class.php
 */
if (defined('root_dir') === false) {
    exit(':)');
}

require_once __DIR__ . '/TwigTemplate.class.php';
class AdminTemplate extends TwigTemplate
{
    public function __construct()
    {
        parent::__construct();
        
    }
}
