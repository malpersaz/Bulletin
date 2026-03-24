<?php
/**
 * Koberz Bilişim İçerik Yönetim Sistemi
 * Web: www.koberz.com
 * E-Mail: info@koberz.com
 * Kullanıcı: Kobim
 * Zaman: 11.04.2011 - 17:29
 * Sınıf İsmi: Adapter
 */
class Adapter
{
    private $adapterclass;
    private $adapterfile;
    private static $instance = false;
    private static $_instances = [];
    private static $database;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Adapter();
        }
        return self::$instance;
    }

    public function getAdapter($database = null)
    {
        self::$database = $database;
        
        // Daha Önce Çağrılmışmı?
        if (array_key_exists(self::$database, self::$_instances)) {
            return self::$_instances[self::$database];
        }
        
        if ($database === null) {
            throw new AdapterException(
                "\$database varitable not allowed NULL\n" .
                "\$database NULL olamaz\n"
            );
            exit();
        }



        // Check database is defined in db_config?
        // İstenilen Database Bilgileri Kayıtlımı?
        try {
            $this->check_database_config_exists(self::$database);
        } catch (AdapterException $e) {
            $e->show();
            exit();
        }

        // Check Adapter is Exists
        // Adapter varmı?
        try {
            $this->adapter_exists(self::$database);
        } catch (AdapterException $e) {
            $e->show();
            exit();
        }

        // Adapter Class
        $adapterclass = $this->adapterclass;

        // Create New Adapter
        try {
            self::$_instances[self::$database] = new $adapterclass(self::$database);
        } catch (DatabaseException $e) {
            $e->show();
            exit();
        }
        
        return self::$_instances[self::$database];
    }

    /*
    * Check Database Exists İn Db_Config
    * @access public
    * @return boolean
    */
    public function check_database_config_exists($database)
    {
        // Get Db_Config
        $db_config = Kayit::getir('db_config');
        if (isset($db_config[$database]) === false) {
            throw new AdapterException(
                "Undefined Database Error!\n'$database'' Database Name is not defined in db.config.php" .
                "Tanımlanmamış Veritabanı Hatası!\n'$database' adlı Veritabanı db.config.php Dosyasında Tanımlanmamış"
            );
            return false;
        }
        return true;
    }
    /*
     * Check Adapter File and Class is Exists
     * Adapter Dosyası ve Sınıfı varmı yokmu kontrol eder
     * @param string $database
     * @return bool
     */
    public function adapter_exists($database)
    {
        $db_config = Kayit::getir('db_config');
        //$adapterclass = 'Adapter_' . ucfirst(strtolower($db_config['adapter']));
        $this->adapterclass = 'Adapter_' . $db_config[$database]['adapter'];
        $this->adapterfile = lib_dir . 'Database' . DS . 'Adapter' .DS . $db_config[$database]['adapter'] . '.php';
        if (!file_exists($this->adapterfile)) {
            throw new AdapterException(
                "Adapter File Not Found!\n
                <b>File:</b>" . $this->adapterfile
            );
            return false;
        }
        
        // Require Adapter File
        require_once($this->adapterfile);

        if (!class_exists($this->adapterclass)) {
            throw new AdapterException(
                "Adapter Class Not Found!\n
                <b>Class:</b>" . $this->adapterclass
            );
            return false;
        }
        return true;
    }
}
