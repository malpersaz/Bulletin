<?php

/**
 * Amed Framework
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * User: AKM
 * Time: 06.08.2025 - PostgreSQL PDO Adapter
 * Class Name: PDO PostgreSQL Adapter
 */
class Adapter_PDO_POSTGRESQL
{
    public $_con;
    public $_con_db;
    private $_con_setting;
    public $symbol = '"'; // PostgreSQL uses double quotes for identifiers
    private $_last_result;
    private $_last_insert_id;
    private $_affected_rows;

    /**
     * __construct
     *
     * @access public
     * @return boolean
     */
    public function __construct($database)
    {
        $db_config = Registry::get('db_config');
        $this->_con_setting = $db_config[$database]['conf'];

        if ($this->_connect() === false) {
            return false;
        }
        return true;
    }

    /**
     * Connect Database
     *
     * @access private
     * @return boolean
     */
    private function _connect()
    {
        $host = $this->_con_setting['host'];
        $port = isset($this->_con_setting['port']) ? $this->_con_setting['port'] : 5432;
        $database = $this->_con_setting['database'];
        
        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
        
        // Add SSL mode if specified
        if (isset($this->_con_setting['sslmode'])) {
            $dsn .= ";sslmode=" . $this->_con_setting['sslmode'];
        }
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->_con = new PDO($dsn, $this->_con_setting['user'], $this->_con_setting['pass'], $options);
            
            // Set character encoding if specified
            if (isset($this->_con_setting['encoding'])) {
                $this->_con->exec("SET NAMES '" . $this->_con_setting['encoding'] . "'");
            }
        } catch (PDOException $e) {
            throw new DatabaseException("PostgreSQL Connection Problem!\n" . $e->getMessage());
        }
        return true;
    }

    /**
     * Query SQL
     *
     * @access public
     * @return PDOStatement
     */
    public function query($sql = null)
    {
        if ($sql == null) {
            throw new DatabaseException("SQL String NOT NULL", null, null);
        }

        try {
            $this->_last_result = $this->_con->query($sql);
            
            // Store affected rows for INSERT/UPDATE/DELETE operations
            if (preg_match('/^(INSERT|UPDATE|DELETE)/i', trim($sql))) {
                $this->_affected_rows = $this->_last_result->rowCount();
            }
            
            // PostgreSQL uses sequences for auto-increment, need to handle differently
            if (preg_match('/^INSERT/i', trim($sql))) {
                // Try to get the last insert ID - may need table/sequence name
                try {
                    $this->_last_insert_id = $this->_con->lastInsertId();
                } catch (PDOException $e) {
                    // If lastInsertId fails, it might need a sequence name
                    $this->_last_insert_id = null;
                }
            }
            
            return $this->_last_result;
        } catch (PDOException $e) {
            throw new DatabaseException("SQL Query Problem: " . $e->getMessage(), $sql, $e->getMessage());
        }
    }

    /**
     * FETCH SQL - Compatible with mysqli_result->fetch_assoc()
     *
     * @access public
     * @return array|false
     */
    public function fetch_assoc($resource)
    {
        if (!($resource instanceof PDOStatement)) {
            throw new DatabaseException("fetch_assoc Method Accept Only PDOStatement Resource");
        }
        return $resource->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Affected Rows
     *
     * @access public
     * @return int
     */
    public function affected_rows()
    {
        return $this->_affected_rows ?? 0;
    }

    /**
     * Insert ID
     *
     * @access public
     * @return string|false
     */
    public function insert_id()
    {
        return $this->_last_insert_id ?? $this->_con->lastInsertId();
    }

    /**
     * Get insert ID with sequence name (PostgreSQL specific)
     *
     * @access public
     * @param string $sequence_name
     * @return string|false
     */
    public function insert_id_sequence($sequence_name)
    {
        return $this->_con->lastInsertId($sequence_name);
    }

    /**
     * Number of rows in result set
     *
     * @access public
     * @param PDOStatement $resource
     * @return int
     */
    public function num_rows($resource)
    {
        if (!($resource instanceof PDOStatement)) {
            throw new DatabaseException("num_rows Method Accept Only PDOStatement Resource");
        }
        return $resource->rowCount();
    }

    /**
     * Free result set
     *
     * @access public
     * @param PDOStatement $resource
     * @return void
     */
    public function free_result($resource)
    {
        if (!($resource instanceof PDOStatement)) {
            throw new DatabaseException("free_result Method Accept Only PDOStatement Resource");
        }
        $resource->closeCursor();
    }

    /**
     * Get last error
     *
     * @access public
     * @return void
     */
    public function error()
    {
        $errorInfo = $this->_con->errorInfo();
        if ($errorInfo[0] !== '00000') {
            printf("Database error: %s\n", $errorInfo[2]);
            exit();
        }
    }

    /**
     * Escape string for SQL queries
     *
     * @access public
     * @param string $string
     * @return string
     */
    public function escape_string($string)
    {
        return $this->_con->quote($string);
    }

    /**
     * Begin transaction
     *
     * @access public
     * @return boolean
     */
    public function begin_transaction()
    {
        return $this->_con->beginTransaction();
    }

    /**
     * Commit transaction
     *
     * @access public
     * @return boolean
     */
    public function commit()
    {
        return $this->_con->commit();
    }

    /**
     * Rollback transaction
     *
     * @access public
     * @return boolean
     */
    public function rollback()
    {
        return $this->_con->rollBack();
    }

    /**
     * Destructor - Close connection
     */
    public function __destruct()
    {
        $this->_con = null;
    }
}