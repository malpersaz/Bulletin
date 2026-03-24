<?php
header('Content-Type: text/html; charset=utf-8');
require_once('../koberz.init.php');
define('installer_dir', root_dir . 'installer' . DS);

require_once(installer_dir . 'installerbase.php');
class installer extends installerbase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function install($mods)
    {
       //$this->db->start_transaction();
        foreach ($mods as $mod) {
            try {
                $m = $this->load_mod($mod);
            } catch (Exception $e) {
                //$this->db->rollback();
                echo $e->getMessage();
                exit();
            }
            if ($m->install() !== true) {
                //$this->db->rollback();
                $this->error("Error occurred while installing '" . $mod . "'");
                exit();
            }
            //$m->uninstall();
        }
        //$this->db->commit();
    }

    public function load_mod($modul)
    {
        if (file_exists($modul . DS . 'index.php') === false) {
            throw new Exception($modul . " Modülü Bulunamadı!");
        }
        require_once(installer_dir . $modul . DS . "index.php");
        return new $modul();
    }

    public function uninstall($mods)
    {
        //$this->db->start_transaction();
        foreach ($mods as $mod) {
            try {
                $m = $this->load_mod($mod);
            } catch (Exception $e) {
                //$this->db->rollback();
                echo $e->getMessage();
                exit();
            }
            if ($m->uninstall() !== true) {
                //$this->db->rollback();
                $this->error("Error occurred while uninstalling '" . $mod . "'");
                exit();
            }
            //$m->uninstall();
        }
        //$this->db->commit();
        //print_r(Database::$_sql_querys);
    }
}

$mods = array(
    # --- Level 0: No dependencies ---
    'lang',
    'mods',
    'mod_settings',
    'mod_user', // Depends on mod_user_group
    'mod_user_group',
    'mod_gallery_mod',
    'mod_gallery',
    'mod_menu',
    'mod_slider',
    'mod_headline',
    'mod_news_category',
    'mod_acl',
    'mod_survey',
    'mod_announcements',

    'mod_attachment',
    'mod_attachment_mod',
    'mod_video_embed_category',
    'mod_document',
    'mod_popup',
    'mod_video',
    'mod_page',

    # --- Level 1: Dependencies on Level 0 ---
    'mod_imagecategories', // Depends on mod_gallery

    # --- Level 2: Dependencies on Level 0, 1 ---
    'mod_user_pm', // Depends on mod_user
    'mod_video_embed', // Depends on mod_user, mod_video_embed_category
    'mod_news', // Depends on mod_news_category, mod_gallery, mod_user
);

$installer = new installer();
$lang = Load::lang('lang');

$installer->system_mesaj($lang['lang']['system']['starting']);

// --- Start Manual Install ---
//$installer->db->start_transaction();

// Install prerequisites first
$installer->system_mesaj(sprintf($lang['lang']['system']['installing'], 'lang'));
if ($installer->load_mod('lang')->install() !== TRUE) {
    //$installer->db->rollback();
    $installer->error("'lang' Yüklenirken Hata Oluştu");
    exit();
}

$installer->system_mesaj(sprintf($lang['lang']['system']['installing'], 'mods'));
if ($installer->load_mod('mods')->install() !== TRUE) {
    //$installer->db->rollback();
    $installer->error("'mods' Yüklenirken Hata Oluştu");
    exit();
}

$installer->system_mesaj(sprintf($lang['lang']['system']['installing'], 'mod_user (part 1)'));
$mod_user = $installer->load_mod('mod_user');
if ($mod_user->install() !== TRUE) {
    //$installer->db->rollback();
    $installer->error("'mod_user' Yüklenirken Hata Oluştu");
    exit();
}

$installer->system_mesaj(sprintf($lang['lang']['system']['installing'], 'mod_user_group'));
if ($installer->load_mod('mod_user_group')->install() !== TRUE) {
   // $installer->db->rollback();
    $installer->error("'mod_user_group' Yüklenirken Hata Oluştu");
    exit();
}

$installer->system_mesaj(sprintf($lang['lang']['system']['installing'], 'mod_user (part 2)'));
if ($mod_user->install_finish() !== TRUE) {
   // $installer->db->rollback();
    $installer->error("'mod_user' (finish) Yüklenirken Hata Oluştu");
    exit();
}
//$installer->db->commit();

// --- End Manual Install ---

// Remove manually installed mods from the list
$mods = array_diff($mods, array('lang', 'mods', 'mod_user', 'mod_user_group'));

$installer->install($mods); // Install the rest
