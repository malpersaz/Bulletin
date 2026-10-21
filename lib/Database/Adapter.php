<?php
/**
 * Amed Framework
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * User: AKM
 * Time: 11.04.2011 - 17:29
 * Class Name: Adapter
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
        
        // Has it been called before?
        if (array_key_exists(self::$database, self::$_instances)) {
            return self::$_instances[self::$database];
        }
        
        if ($database === null) {
            throw new AdapterException(
                "\$database variable cannot be NULL\n"
            );
            exit();
        }



        // Check database is defined in db_config?
        // Is the requested Database Information Registered?
        try {
            $this->check_database_config_exists(self::$database);
        } catch (AdapterException $e) {
            $e->show();
            exit();
        }

        // Check Adapter is Exists
        // Does the Adapter exist?
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
    * Check Database Exists In Db_Config
    * @access public
    * @return boolean
    */
    public function check_database_config_exists($database)
    {
        // Get Db_Config
        $db_config = Registry::get('db_config');
        if (isset($db_config[$database]) === false) {
            throw new AdapterException(
                "Undefined Database Error!\nDatabase '$database' is not defined in db.config.php"
            );
            return false;
        }
        return true;
    }
    /*
     * Check Adapter File and Class is Exists
     * Checks if the Adapter File and Class exist
     * @param string $database
     * @return bool
     */
    public function adapter_exists($database)
    {
        $db_config = Registry::get('db_config');
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
