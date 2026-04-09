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

    /**
     * IP Bazlı Görüntüleme Sayısını Günceller (Anti-F5 Spam)
     * @param string $table Tablo adı (Örn: mod_news)
     * @param int $id İçerik ID'si
     * @return bool
     */
    public function _update_view_count($table, $id)
    {
        if (empty($table) || empty($id)) {
            return false;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $today = date('Y-m-d');
        
        $db = Load::lib('Database');
        
        // Bu IP bugün bu içeriği görmüş mü kontrol et
        $db->fetch_array("SELECT id FROM `sys_view_logs` WHERE `table_name` = '$table' AND `item_id` = " . intval($id) . " AND `ip_address` = '$ip' AND `created_at` = '$today' LIMIT 1");
        
        if ($db->getSize() === 0) {
            // Log kaydı ekle (Duplicate entry hatasını önlemek için IGNORE kullanıyoruz)
            $db->query("INSERT IGNORE INTO `sys_view_logs` (`table_name`, `item_id`, `ip_address`, `created_at`) 
                        VALUES ('$table', " . intval($id) . ", '$ip', '$today')");
            
            // Eğer bir satır eklendiyse view count artır
            if ($db->affected_rows() > 0) {
                $db->query("UPDATE `$table` SET `view_count` = `view_count` + 1 WHERE `id` = " . intval($id));
                return true;
            }
        }
        
        return false;
    }
}
