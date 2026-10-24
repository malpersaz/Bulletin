<?php

/**
 * Amed Framework Content Management System
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * User: AKM
 * Date: 08.04.2011 - 18:55
 */
if (defined('lib_dir') === false) {
    exit(':)');
}

class Database
{
    public $_db;
    public $_query_count = 0;
    public $_adapter;
    public $_sql_text;

    public $_for_update = false;
    public $_transaction_depth = 0;
    public static $_sql_querys = [];

    public static $_instances = [];
    public static $database;

    public $_first_next = 0;

    // SQL Builder
    public $_table;
    public $_coloms;
    public $_limit;
    public $_order;
    public $_where;
    public $_from;

    public $_query_result;
    public $_cache_result;
    public $_db_result;
    // Caching
    public $_cache = false;
    public $_result = null;

    public function __construct($database = null)
    {
        return $this->connect($database);
    }


    public function connect($database = null)
    {
        if (is_array($database)) {
            $dbconfig = array_merge($database, Registry::get('db_config'));
            Registry::set('db_config', $dbconfig);
            $r = array_keys($database);
            $database = $r[0];
        }
        // If the $database parameter passed is not NULL
        self::$database = $database != null ? $database : Registry::get('database');

        // If this database has been called before.
        if (array_key_exists(self::$database, self::$_instances)) {
            $this->_db[self::$database] = self::$_instances[self::$database];
            return true;
        }

        // Instantiate the adapter and check if it exists in the config
        try {
            $_adapter = Adapter::getInstance();
        } catch (AdapterException $e) {
            $e->show();
            exit();
        }
        // Get the adapter
        try {
            $this->_db[self::$database] = $_adapter->getAdapter(self::$database);
        } catch (AdapterException $e) {
            $e->show();
            exit();
        }
        // Add to _instances object
        self::$_instances[self::$database] = $this->_db[self::$database];

        // symbol
        Dbuilder::$symbol = $this->_db[self::$database]->symbol;
        return true;
    }


    /*
    * Check Database Exists In Db_Config
    * @access public
    * @return mixed
    */
    public function query($sql = null)
    {
        $this->_sql_text = $sql;

        if ($this->_cache === true && isset($this->_query_result[self::$database][$this->_sql_text]) === true) {
            return $this->_query_result[self::$database][$this->_sql_text];
        }
        try {
            $this->_query();
        } catch (DatabaseException $e) {
            $e->show();
            return false;
        }
        return $this->_query_result[self::$database][$this->_sql_text];
    }

    /*
    * Check Database Exists In Db_Config
    * @access private
    * @return bool
    */
    private function _query()
    {
        $this->_query_count++;
        try {
            // Add to log
            self::$_sql_querys[] = [
                'time' => time(),
                'sql' => $this->_sql_text
            ];

            $this->_query_result[self::$database][$this->_sql_text] = $this->_db[self::$database]->query($this->_sql_text);
        } catch (DatabaseException $e) {
            $e->show();
            exit();
        }
        return true;
    }

    public function _fetch_array()
    {
        try {
            return $this->_db[self::$database]->fetch_assoc($this->_query_result[self::$database][$this->_sql_text]);
        } catch (DatabaseException $e) {
            $e->show();
        }
    }

    public function fetch_array($sql_text = null)
    {
        if ($sql_text !== null) {
            $this->_sql_text = $sql_text;
        }


        try {
            if ($this->_sql_text === null) {
                throw new DatabaseException('SQL String NOT NULL');
            }
        } catch (DatabaseException $e) {
            $e->show();
            exit();
        }
        // IF exists Cache Result In Object return cache
        if ($this->_cache && isset($this->_cache_result[self::$database]['array'][$this->_sql_text])) {
            return $this->_cache_result[self::$database]['array'][$this->_sql_text];
        }


        // Query
        $this->query($this->_sql_text);


        // UNSET OLD RESULT
        unset($this->_db_result[self::$database]);
        unset($this->_result);
        $this->_result = null;
        $this->_db_result[self::$database] = null;


        if ($this->_cache) {
            unset($this->_cache_result[self::$database]['array'][$this->_sql_text]);
        }

        while ($source = $this->_fetch_array()) {
            // Cache is On? add result cache
            if ($this->_cache) {
                $this->_cache_result[self::$database]['array'][$this->_sql_text][] = $source;
            }
            $this->_db_result[self::$database][] = $source;
        }

        if (is_array($this->_db_result[self::$database])) {
            $this->_result = $this->_db_result[self::$database];
        }
        $this->_query_count++;
        // Cache is Off unset and free_result
        if (!$this->_cache) {
            $this->_free_result($this->_query_result[self::$database][$this->_sql_text]);
            unset($this->_query_result[self::$database][$this->_sql_text]);
            unset($this->_cache_result[self::$database]['array'][$this->_sql_text]);
        }
        return $this;
    }

    public function _free_result($resource)
    {
        $this->_query_count++;
        try {
            $this->_db[self::$database]->free_result($resource);
        } catch (DatabaseException $e) {
            $e->show();
            return false;
        }
        return true;
    }

    public function start_transaction()
    {
        $db = $this->getDB();
        if ($this->_transaction_depth === 0) {
            if ($db && method_exists($db, 'begin_transaction')) {
                $db->begin_transaction();
            } else {
                $this->query("START TRANSACTION");
            }
        }
        $this->_transaction_depth++;
        return true;
    }

    public function setForUpdate($u = true)
    {
        $this->_for_update = $u;
    }

    public function commit()
    {
        $this->setForUpdate(false);
        if ($this->_transaction_depth > 0) {
            $this->_transaction_depth--;
        }
        if ($this->_transaction_depth === 0) {
            $db = $this->getDB();
            try {
                if ($db && method_exists($db, 'in_transaction') && method_exists($db, 'commit')) {
                    if ($db->in_transaction()) {
                        $db->commit();
                    }
                } elseif ($db && method_exists($db, 'commit')) {
                    $db->commit();
                } else {
                    $this->query("COMMIT");
                }
            } catch (DatabaseException $e) {
                $e->show();
                return false;
            } catch (\Throwable $e) {
                return false;
            }
        }
        return true;
    }

    public function rollback()
    {
        $this->setForUpdate(false);
        $this->_transaction_depth = 0;
        $db = $this->getDB();
        try {
            if ($db && method_exists($db, 'in_transaction') && method_exists($db, 'rollback')) {
                if ($db->in_transaction()) {
                    $db->rollback();
                }
            } elseif ($db && method_exists($db, 'rollback')) {
                $db->rollback();
            } else {
                $this->query("ROLLBACK");
            }
        } catch (DatabaseException $e) {
            $e->show();
            return false;
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    public function in_transaction()
    {
        $db = $this->getDB();
        if ($db && method_exists($db, 'in_transaction')) {
            return (bool)$db->in_transaction();
        }
        return $this->_transaction_depth > 0;
    }

    public function truncate($table)
    {
        $this->query("TRUNCATE TABLE `" . $table . "`");
    }

    /*
    * Make SQL insert
    * Sql insert Function
    * @param string $table
    * @param array $array
    * @param bool $bool
    * @return bool
    */
    public function db_insert($table, $array, $replace = false)
    {
        $this->_sql_text = Dbuilder::create_insert($table, $array, $replace);
        try {
            $this->query($this->_sql_text);
        } catch (DatabaseException $e) {
            $e->show();
            return false;
        }
        return true;
    }

    public function db_update($table, $array, $where)
    {
        $this->_sql_text = Dbuilder::create_update($table, $array, $where);
        try {
            $this->query($this->_sql_text);
        } catch (DatabaseException $e) {
            $e->show();
            return false;
        }
        return true;
    }

    public function getResult($build = false, $fetch = false)
    {
        if ($build) {
            $this->build();
        }
        if ($fetch) {
            $this->fetch_array();
        }
        $this->_result = isset($this->_db_result[self::$database]) ? $this->_db_result[self::$database] : null;
        return $this;
    }

    public function getLimit($limit, $offset = 0)
    {
        // Create a Temp Array
        $temp_result = [];
        // Check How Many Results
        // var_dump($this->_result);
        $size = $this->_result == NULL ? 0 : count($this->_result);
        // Create a temp value
        $count = 0;

        // Nullify result if Offset array value is missing
        if (!isset($this->_result[$offset])) {
            $this->_result = null;
            return $this;
        }
        // Loop and assign offset value to $i
        for ($i = $offset; $i <= $size; $i++) {
            // Break if desired limit of data is added to temp_result
            if ($count == $limit) {
                break;
            }
            // Add to temp_result if the specified index exists in _result
            if (isset($this->_result[$i])) {
                $temp_result[] = $this->_result[$i];
                $count++;
                continue;
            }
            // otherwise break
            break;
        }
        $this->_result = $temp_result;
        unset($temp_result);
        unset($count);
        unset($size);
        unset($i);
        return $this;
    }

    public function getResultOne($build = false, $fetch = false)
    {
        if ($build) {
            $this->build();
        }
        if ($fetch) {
            $this->fetch_array();
        }
        $this->_result = isset($this->_db_result[self::$database][0]) ? $this->_db_result[self::$database][0] : null;
        return $this;
    }

    public function gR()
    {
        $this->build();
        $this->fetch_array();
        $this->_result = isset($this->_db_result[self::$database]) ? $this->_db_result[self::$database] : null;
        return $this;
    }

    public function gRo()
    {
        $this->build();
        $this->fetch_array();
        $this->_result = isset($this->_db_result[self::$database][0]) ? $this->_db_result[self::$database][0] : null;
        return $this;
    }

    public function getSql()
    {
        return $this->_sql_text;
    }

    public function getSize()
    {
        return $this->_result !== null ? count($this->_result) : 0;
    }

    public function getCount()
    {
        $this->build();
        $this->query($this->_sql_text);
        $res = $this->_query_result[self::$database][$this->_sql_text];
        $count = $this->_db[self::$database]->num_rows($res);
        // Free the database query resource and clear the cached result to prevent SQLite PDO memory leaks.
        $this->_free_result($res);
        unset($this->_query_result[self::$database][$this->_sql_text]);
        return $count;
    }

    public function affected_rows()
    {
        return $this->_db[self::$database]->affected_rows();
    }

    public function error()
    {
        return $this->_db[self::$database]->error();
    }

    public function first()
    {
        $this->_result = current($this->_db_result[self::$database]) !== false
            ? current($this->_db_result[self::$database]) : null;
        return $this;
    }

    public function next()
    {
        $this->_first_next++;

        $this->_result = isset($this->_db_result[self::$database][$this->_first_next])
            ? $this->_db_result[self::$database][$this->_first_next] : false;
        return $this;
    }

    public function prev()
    {
        $this->_first_next--;
        $this->_result = isset($this->_db_result[self::$database][$this->_first_next])
            ? $this->_db_result[self::$database][$this->_first_next] : false;
        return $this;
    }

    public function last()
    {
        $this->_result = end($this->_db_result[self::$database]) !== false ? end($this->_db_result[self::$database])
            : null;
        return $this;
    }

    public function allResult()
    {
        $this->_result = $this->_db_result;
        return $this;
    }

    public function run($sql = null)
    {
        if ($sql != null) {
            $this->_sql_text = $sql;
        }
        try {
            $this->query($this->_sql_text);
            return true;
        } catch (DatabaseException $e) {
            $e->show();
        }
        return false;
    }

    /*
    * Change Database Connect
    * @access public
    * @return connect function
    */
    public function renew($database)
    {
        return $this->connect($database);
    }


    public function make_delete($table = null, $where = null)
    {
        try {
            $this->_sql_text = Dbuilder::create_delete($table, $where);
        } catch (DbuilderException $e) {
            $e->show();
            exit();
        }
        return $this;
    }

    public function delete()
    {
        try {
            $this->_sql_text = "DELETE FROM " . $this->_from;
            $this->_sql_text .= isset($this->_where) ? " WHERE " . $this->_where : '';
            $this->query($this->_sql_text);
            return $this;
        } catch (DbuilderException $e) {
            $e->show();
            exit();
        }
    }

    public function table($arg)
    {
        try {
            $this->_from = Dbuilder::table($arg);
        } catch (DbuilderException $e) {
            $e->show();
            exit();
        }
        return $this;
    }

    public function table_join($config)
    {
        try {
            $res = Dbuilder::table_join($config);
        } catch (DbuilderException $e) {
            $e->show();
            exit();
        }
        $this->_from = $res['table'];
        $this->_coloms = implode(',', $res['select']);
        return $this;
    }

    public function coloms($arg)
    {
        try {
            $this->_coloms = Dbuilder::coloms($arg);
        } catch (DbuilderException $e) {
            $e->show();
            exit();
        }
        return $this;
    }

    public function select($arg)
    {
        try {
            $this->_coloms = Dbuilder::coloms($arg);
        } catch (DbuilderException $e) {
            $e->show();
            exit();
        }
        return $this;
    }

    public function limit($limit, $offset = null)
    {
        try {
            $this->_limit = Dbuilder::limit($limit, $offset);
        } catch (DbuilderException $e) {
            $e->show();
        }
        return $this;
    }

    public function order($column, $type)
    {
        try {
            $this->_order = Dbuilder::order($column, $type);
        } catch (DbuilderException $e) {
            $e->show();
        }
        return $this;
    }

    public function where($arg)
    {
        try {
            $this->_where = Dbuilder::where($arg);
        } catch (DbuilderException $e) {
            $e->show();
        }
        return $this;
    }

    public function addWhere($arg, $condition = 'AND')
    {
        try {
            if ($this->_where == null || $this->_where == '') {
                $this->_where = Dbuilder::where($arg);
            } else {
                $this->_where = $this->_where . " " . $condition . " " . Dbuilder::where($arg);
            }
        } catch (DbuilderException $e) {
            $e->show();
            exit;
        }
        return $this;
    }


    public function build()
    {
        $this->_coloms = empty($this->_coloms) ? "*" : $this->_coloms;
        $this->_sql_text = "SELECT " . $this->_coloms . " FROM " . $this->_from;
        $this->_sql_text .= isset($this->_where) ? " WHERE " . $this->_where : '';
        $this->_sql_text .= $this->_order . " " . $this->_limit;
        if ($this->_for_update === true) {
            $this->_sql_text .= " FOR UPDATE";
        }
        return $this;
    }

    public function setWhere($where)
    {
        $this->_where = $where;
        return $this;
    }

    public function setColoms($coloms)
    {
        $this->_coloms = $coloms;
        return $this;
    }

    public function setSqlText($text)
    {
        $this->_sql_text = $text;
        return $this;
    }

    public function getDB()
    {
        return $this->_db[self::$database];
    }

    public function get_connection()
    {
        $db = $this->getDB();
        return isset($db->_con) ? $db->_con : null;
    }

    public function getAdapterType()
    {
        if (isset($this->_db[self::$database])) {
            return get_class($this->_db[self::$database]);
        }
        return null;
    }

    /*
    * mysql_insert_id or pg_insert_id
    * @return int
    */
    public function insert_id()
    {
        try {
            return $this->_db[self::$database]->insert_id($this->_query_result[self::$database][$this->_sql_text]);
        } catch (DatabaseException $e) {
            $e->show();
            return null;
        }
    }

    /**
     * Safely quotes and escapes a string value for query execution.
     *
     * @param string $string String value to escape
     * @return string Quoted/escaped string
     */
    public function quote($string)
    {
        $db = $this->getDB();
        if (method_exists($db, 'escape_string')) {
            return $db->escape_string($string);
        }
        if (isset($db->_con) && method_exists($db->_con, 'quote')) {
            return $db->_con->quote($string);
        }
        if (isset($db->_con) && method_exists($db->_con, 'real_escape_string')) {
            return "'" . $db->_con->real_escape_string($string) . "'";
        }
        return "'" . addslashes($string) . "'";
    }

    public function flush()
    {
        $this->_coloms = null;
        $this->_where = null;
        $this->_sql_text = null;
        $this->_limit = null;
        $this->_order = null;
        $this->_table = null;
        return $this;
    }

    /*
    *  Converter
    *  Converters
    */
    public function toObject()
    {
        if ($this->_result === null) {
            return null;
        }
        try {
            return Dconvert::toObject($this->_result);
        } catch (DconvertException $e) {
            $e->show();
        }
    }

    public function toArray()
    {
        if ($this->_result === null) {
            return null;
        }
        try {
            return Dconvert::toArray($this->_result);
        } catch (DconvertException $e) {
            $e->show();
            exit();
        }
    }

    public function toJson()
    {
        if ($this->_result === null) {
            return null;
        }
        return Dconvert::toJson($this->_result);
    }

    public function toSerialize()
    {
        if ($this->_result === null) {
            return null;
        }
        return Dconvert::toSerialize($this->_result);
    }
}
