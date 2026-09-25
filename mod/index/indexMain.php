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
    public $options;
    public $option;
    function __construct()
    {
        // Load Template Library
        // $this->tema = Load::lib('AdminTemplate');
        // Load Database Library
        // $this->db = Load::lib('Database');
        // Fetch site settings
        $this->site = Registry::get('site_config');
        // Session Library
        // $this->session = Load::lib('Session');
        // User Model
        // $this->user = Load::model('mod_user.user');
    }

    protected function resolveClientIp()
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if ($this->isPublicIp($remoteAddr)) {
            return $remoteAddr;
        }

        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_TRUE_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $header) {
            if (empty($_SERVER[$header])) {
                continue;
            }

            foreach (explode(',', $_SERVER[$header]) as $candidate) {
                $candidate = trim($candidate);

                if ($this->isPublicIp($candidate)) {
                    return $candidate;
                }
            }
        }

        return $this->normalizeIp($remoteAddr);
    }

    protected function isPublicIp($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    protected function normalizeIp($ip)
    {
        return filter_var(trim((string) $ip), FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }

    /**
     * Updates IP-based view count (Anti-F5 Spam)
     * @param string $table Table name (e.g. mod_news)
     * @param int $id Content ID
     * @return bool
     */
    public function _update_view_count($table, $id)
    {
        if (empty($table) || empty($id)) {
            return false;
        }

        $ip = $this->resolveClientIp();
        $today = date('Y-m-d');
        
        $db = Load::lib('Database');
        
        // Check if this IP viewed this item today
        $db->fetch_array("SELECT id FROM `sys_view_logs` WHERE `table_name` = '$table' AND `item_id` = " . intval($id) . " AND `ip_address` = '$ip' AND `created_at` = '$today' LIMIT 1");
        
        if ($db->getSize() === 0) {
            // Add log record (use INSERT IGNORE to prevent duplicate entry error)
            $db->query("INSERT IGNORE INTO `sys_view_logs` (`table_name`, `item_id`, `ip_address`, `created_at`) 
                        VALUES ('$table', " . intval($id) . ", '$ip', '$today')");
            
            // If inserted, increment view count
            if ($db->affected_rows() > 0) {
                $db->query("UPDATE `$table` SET `view_count` = `view_count` + 1 WHERE `id` = " . intval($id));
                return true;
            }
        }
        
        return false;
    }
}
