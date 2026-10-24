<?php

/**
 * Amed Framework
 * Class: Adapter_PDO_MYSQL
 * Description: PDO MySQL Database Adapter
 */
class Adapter_PDO_MYSQL
{
    public $_con;
    public $_con_db;
    private $_con_setting;
    public $symbol = '`';
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
        $port = isset($this->_con_setting['port']) ? $this->_con_setting['port'] : 3306;
        $database = $this->_con_setting['database'];
        $charset = isset($this->_con_setting['encoding']) ? $this->_con_setting['encoding'] : 'utf8';
        
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->_con = new PDO($dsn, $this->_con_setting['user'], $this->_con_setting['pass'], $options);
        } catch (PDOException $e) {
            throw new DatabaseException("Connection Problem!\nPls Check Database Username,Pass And Host Config!\n" . $e->getMessage());
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
            
            // Store last insert id for INSERT operations
            if (preg_match('/^INSERT/i', trim($sql))) {
                $this->_last_insert_id = $this->_con->lastInsertId();
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
        
        // For SELECT statements, PDO doesn't provide a direct way to count rows
        // without fetching them. This is a known limitation of PDO.
        // We'll use rowCount() which works for INSERT/UPDATE/DELETE
        // For SELECT queries, the calling code should use a separate COUNT() query
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
        if (isset($errorInfo[2]) && $errorInfo[2] !== null) {
            return $errorInfo[2];
        }

        return "Unknown MySQL Error (Check PDOException traces)";
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
        // PDO handles escaping through prepared statements
        // This method is for backward compatibility
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
     * Check if in transaction
     *
     * @access public
     * @return boolean
     */
    public function in_transaction()
    {
        return $this->_con ? $this->_con->inTransaction() : false;
    }

    /**
     * Destructor - Close connection
     */
    public function __destruct()
    {
        $this->_con = null;
    }
}
