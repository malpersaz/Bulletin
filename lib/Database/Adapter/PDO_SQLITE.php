<?php

/**
 * Amed Framework
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * User: AKM
 * Date: 06.08.2025 - SQLite PDO Adapter
 * Class Name: PDO SQLite Adapter
 */
class Adapter_PDO_SQLITE
{
    public $_con;
    public $_con_db;
    private $_con_setting;
    public $symbol = '"'; // SQLite uses double quotes for identifiers
    private $_last_result;
    private $_last_insert_id;
    private $_affected_rows;
    private $_buffered_rows = [];
    private $_buffered_offsets = [];

    private function normalize_mysql_compat_sql($sql)
    {
        $normalized = ltrim((string) $sql);

        if (preg_match('/^INSERT\s+IGNORE\s+INTO\b/i', $normalized)) {
            return preg_replace('/^INSERT\s+IGNORE\s+INTO\b/i', 'INSERT OR IGNORE INTO', $normalized, 1);
        }

        return $sql;
    }

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
        // SQLite connection path: sqlite:/path/to/database.db
        $database_path = $this->_con_setting['database_path'] ?? 'data/database.sqlite';
        $connection_string = "sqlite:" . $database_path;
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->_con = new PDO($connection_string, null, null, $options);
            // Enable foreign key constraints and set busy timeout
            $this->_con->exec('PRAGMA foreign_keys = ON');
            $this->_con->exec('PRAGMA busy_timeout = 5000');
        } catch (PDOException $e) {
            throw new DatabaseException("SQLite Connection Problem!\n" . $e->getMessage());
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
            // Free the last result statement to clear buffered rows and offsets,
            // preventing spl_object_id collisions under PHP's garbage collection.
            if ($this->_last_result instanceof PDOStatement) {
                try {
                    $this->free_result($this->_last_result);
                } catch (Exception $e) {
                    // Ignore error if already closed
                }
            }

            $sql = $this->normalize_mysql_compat_sql($sql);
            $normalizedSql = strtoupper(trim((string) $sql));

            if ($normalizedSql === 'SET AUTOCOMMIT=0' || $normalizedSql === 'SET AUTOCOMMIT = 0') {
                $this->_affected_rows = 0;
                return true;
            }

            if ($normalizedSql === 'START TRANSACTION') {
                $this->_affected_rows = 0;

                if (!$this->_con->inTransaction()) {
                    $this->_con->beginTransaction();
                }

                return true;
            }

            if ($normalizedSql === 'COMMIT') {
                $this->_affected_rows = 0;

                if ($this->_con->inTransaction()) {
                    $this->_con->commit();
                }

                return true;
            }

            if ($normalizedSql === 'ROLLBACK') {
                $this->_affected_rows = 0;

                if ($this->_con->inTransaction()) {
                    $this->_con->rollBack();
                }

                return true;
            }

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

        $statementId = spl_object_id($resource);

        if (isset($this->_buffered_rows[$statementId])) {
            $offset = $this->_buffered_offsets[$statementId] ?? 0;

            if (!isset($this->_buffered_rows[$statementId][$offset])) {
                return false;
            }

            $this->_buffered_offsets[$statementId] = $offset + 1;
            return $this->_buffered_rows[$statementId][$offset];
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

        $rowCount = $resource->rowCount();
        if ($rowCount > 0) {
            return $rowCount;
        }

        $statementId = spl_object_id($resource);

        if (!isset($this->_buffered_rows[$statementId])) {
            $this->_buffered_rows[$statementId] = $resource->fetchAll(PDO::FETCH_ASSOC);
            $this->_buffered_offsets[$statementId] = 0;
        }

        return count($this->_buffered_rows[$statementId]);
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

        $statementId = spl_object_id($resource);
        unset($this->_buffered_rows[$statementId], $this->_buffered_offsets[$statementId]);
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
