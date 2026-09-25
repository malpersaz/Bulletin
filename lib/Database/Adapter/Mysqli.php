<?php

/**
 * AKM Business Content Management System
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * User: AKM
 * Time: 08.04.2011 - 18:56
 * Class Name: Mysql
 */
class Adapter_Mysqli
{
    public $_con;
    public $_con_db;
    private $_con_setting;
    public $symbol = '`';

    /**
     * __construct
     *
     * @access public
     * @return boolean
     */
    public function __construct($database)
    {
        //echo "$database - CALLED";
        $db_config = Registry::get('db_config');
        $this->_con_setting = $db_config[$database]['conf'];

        if ($this->_connect() === false) {
            return false;
        }
        $this->_con->set_charset($this->_con_setting['encoding']);
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
        $this->_con = new mysqli($this->_con_setting['host'], $this->_con_setting['user'], $this->_con_setting['pass'], $this->_con_setting['database'], isset($this->_con_setting['port']) ? $this->_con_setting['port'] : 3306);
        if ($this->_con->connect_errno !== 0) {
            throw new DatabaseException("Connection Problem!\nPls Check Database Username,Pass And Host Config!\n", null, $this->error());
        }
        return true;
    }


    /**
     * Query SQL
     *
     * @access public
     * @return resource
     */
    public function query($sql = null)
    {
        if ($sql == null) {
            throw new DatabaseException("SQL String NOT NULL", null, null);
        }
        $result = $this->_con->query($sql);

        if ($result === false) {
            throw new DatabaseException("SQL Query Problem", $sql, $this->_con->error);
        }
        return $result;
    }

    /**
     * FETCH SQL
     *
     * @access public
     * @return resource
     */
    public function fetch_assoc($resource)
    {
        if (!($resource instanceof mysqli_result)) {
            throw new DatabaseException("fetch_assoc Method Accept Only Resource");
        }
        return $resource->fetch_assoc();
    }


    /**
     * Affected Rows
     *
     * @access public
     * @return resource
     */
    public function affected_rows()
    {
        return $this->_con->affected_rows;
    }

    public function insert_id()
    {
        return $this->_con->insert_id;
    }

    public function num_rows($resource)
    {
        if (!($resource instanceof mysqli_result)) {
            throw new DatabaseException("num_rows Method Accept Only Resource");
        }
        return $resource->num_rows;
    }


    public function free_result($resource)
    {
        if (!($resource instanceof mysqli_result)) {
            throw new DatabaseException("free_result Method Accept Only Resource");
        }
        $resource->free_result();
    }


    public function error()
    {
        if (isset($this->_con) && $this->_con->error) {
            return $this->_con->error;
        }

        if (isset($this->_con) && $this->_con->connect_error) {
            return "Connect failed: " . $this->_con->connect_error;
        }

        return "Unknown MySQLi Error (Check DatabaseException traces)";
    }

    public function __destruct()
    {
        $this->_con->close();
    }
}
