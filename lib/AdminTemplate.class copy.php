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
// require_once(lib_dir . 'smarty/Smarty.class.php');

class AdminTemplate extends Smarty
{
    public $dir;
    public $out = [];
    private $dil;
    public $assing_data = [];

    public function __construct()
    {
        parent::__construct();
        $this->left_delimiter = "{{";
        $this->right_delimiter = "}}";
        $this->setTemplateDir(template_dir . Kayit::getir('admin_template') . DS);
        $this->setCompileDir($this->compile_dir = root_dir . 'templates_c' . DS);
        $this->debugging = DEBUG;
        $this->force_compile = true;
        $this->compile_check = true;
        $this->addPluginsDir(lib_dir . 'smartyplugins');

        $this->error_reporting = E_ALL & ~E_NOTICE;

        //$this->caching_type='memcache';
        //$this->cache_modified_check=false;
        //$this->loadResource('memcache');
        //$this->loadCacheResource('memcache');
        //$this->setCaching(true);
        //$this->setCachingType('apc');
        //$this->cache_modified_check=false;
        /*$this->force_compile = false;
        $this->compile_check=false;*/


        /*$this->force_compile = true;
        $this->compile_check=true;*/

        //var_dump($this->force_compile);
        //var_dump($this->compile_check);
        //print_r($this);
        //$this->assing_data=array()
    }

    public function veri($a, $b)
    {
        $this->assign($a, $b);
        //return $this->assing_data[$a] = $b;
    }

    public function tpl($a)
    {
        return $this->out[] = $a;
    }

    public function output()
    {
        if (is_array($this->out)) {
            foreach ($this->out as $a) {
                $this->display($a . '.tpl');
            }
        }
    }

    // Dil Dosyası Üzerine Yazmaması için
    public function lang($a)
    {
        if (is_array($a)) {
            $this->dil = is_array($this->dil) && count($this->dil) > 0 ? array_merge($this->dil, $a) : $a;
        }
        return $this->assign('lang', $this->dil);
    }

    public function fetch_tpl($tpl)
    {
        return $this->fetch($tpl . '.tpl');
    }
}
