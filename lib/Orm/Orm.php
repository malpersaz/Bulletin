<?php

/**
 * Amed Framework Content Management System
 * File: Orm.php
 * Description: Object-Relational Mapping (ORM) Base Class
 */

interface Orm_interface
{
    public function size();

    public function getResult($build = false, $fetch = false);

    public function getResultOne($build = false, $fetch = false);

    public function allResult();

    public function renew($database);

    public function table($arg);

    public function coloms($arg);

    public function limit($limit, $offset = null);

    public function order($colom, $type);

    public function where($arg);

    public function build();

    public function insert_id();

    public function toObject();

    public function toArray();

    public function toJson();

    public function toSerialize();
}

#[AllowDynamicProperties]
abstract class ORM extends Database implements Orm_interface
{
    public $_ai;
    public $_pk = 'id';
    public $_table;
    public $_orm_table;
    public $_orm_coloms = [];
    public $_orm_pk;
    public $_orm_ai;


    public function __call($name, $value)
    {
        try {
            $column_list = implode("|", $this->_orm_coloms);
            $column_pattern = "!^(" . $column_list . ")$!i";
            $get_pattern = "!^(g|G)et(.+)$!";
            $delete_pattern = "!^(d|D)elete_(.+)$!";
            $where_pattern = "!^(w|W)here_(.+)!";
            $exists_pattern = "!^(e|E)xists_(.+)!";

            if (preg_match($exists_pattern, $name, $result)) {
                return $this->_call_exists($result, $value);
            }
            if (preg_match($where_pattern, $name, $result)) {
                $this->_call_where($result, $value);
                return $this;
            }
            if (preg_match($delete_pattern, $name, $result)) {
                $this->_call_delete($result, $value);
                return $this;
            }
            if (preg_match($column_pattern, $name, $result)) {
                $this->_call_colom($result, $value);
                return $this;
            }
            if (preg_match($get_pattern, $name, $result)) {
                $this->_call_get($result, $value);
                return $this;
            }
            throw new OrmException("Function '{$name}' not found!");
        } catch (OrmException $e) {
            $e->show();
        }
    }

    private function _call_delete($result, $value)
    {
        $this->make_delete($this->_orm_table, Obuilder::where($result['2'], $value, $this->_orm_coloms, $this->_orm_table));
        return $this;
    }

    private function _call_exists($result, $value)
    {
        $this->_call_where($result, $value);
        $this->getResult(true, true);
        return $this->_result !== null;
    }

    private function escape($e)
    {
        return str_replace('_', '^', $e[1]);
    }

    private function _call_where($result, $value)
    {
        try {
            // Column names with underscores need escaping.
            // Escape names such as user_id before building the condition.
            $pattern_terms = implode("|", array_merge(array_keys(Obuilder::$equations), Obuilder::$valid_after_key, $this->_orm_coloms));
            $d = '#_?((' . $pattern_terms . ')+)#';
            $parsed_clause = preg_replace($d, '\_$1', $result[2]);
            // Remove a leading escaped underscore so the condition remains valid.
            $dd = '#^\\\_(.)#';
            $parsed_clause = preg_replace($dd, '$1', $parsed_clause);
            $where = Obuilder::where($parsed_clause, $value, $this->_orm_coloms, $this->_orm_table);
            $this->where($where);
        } catch (OrmException $e) {
            $e->show();
            exit();
        }
    }

    private function _call_colom($colom, $value)
    {
        $targetCol = $this->resolve_column($colom['1']) ?? $colom['1'];
        try {
            Obuilder::check_key_exists($targetCol, $this->_orm_coloms);
        } catch (OrmException $e) {
            $e->show();
        }

        $this->where($targetCol . "='" . $value['0'] . "'");
    }

    private function _call_get($colom, $value)
    {
        $targetCol = $this->resolve_column($colom['2']) ?? $colom['2'];
        try {
            Obuilder::check_key_exists($targetCol, $this->_orm_coloms);
        } catch (OrmException $e) {
            $e->show();
            exit();
        }
        $this->where($targetCol . "='" . $value['0'] . "'");
        if ($this->_result !== null) {
            // Sync with case-insensitive column resolution
            $lowerResult = [];
            foreach ($this->_result as $k => $v) {
                $lowerResult[strtolower($k)] = $v;
            }
            foreach ($this->_orm_coloms as $c) {
                $lowerCol = strtolower($c);
                if (array_key_exists($c, $this->_result)) {
                    $this->$c = $this->_result[$c];
                } elseif (array_key_exists($lowerCol, $lowerResult)) {
                    $this->$c = $lowerResult[$lowerCol];
                } else {
                    $this->$c = null;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Resolve column name case-insensitively against registered schema columns.
     *
     * @param string $column_name
     * @return string|null
     */
    public function resolve_column($column_name)
    {
        if (is_array($this->_orm_coloms)) {
            foreach ($this->_orm_coloms as $col) {
                if (strcasecmp($col, $column_name) === 0) {
                    return $col;
                }
            }
        }
        return null;
    }

    /**
     * Case-insensitive setter for ORM properties.
     *
     * @param string|array $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
            return $this;
        }

        $resolved = $this->resolve_column($key);
        $target = $resolved !== null ? $resolved : $key;
        $this->$target = $value;
        return $this;
    }

    // Sync the current object when getResultOne has loaded a record.
    public function sync($results = false)
    {
        $targetResult = null;
        if ($results === false) {
            if (!is_array($this->_result)) {
                throw new OrmException("Result to be synced not found!");
            }
            $targetResult = $this->_result;
        } else {
            if (!isset($this->_result[0])) {
                throw new OrmException("Result to be synced not found!");
            }
            $targetResult = $this->_result[0];
        }

        if (is_array($this->_orm_coloms)) {
            $lowerResult = [];
            foreach ($targetResult as $k => $v) {
                $lowerResult[strtolower($k)] = $v;
            }

            foreach ($this->_orm_coloms as $colom) {
                $lowerCol = strtolower($colom);
                if (array_key_exists($colom, $targetResult)) {
                    $this->$colom = $targetResult[$colom];
                } elseif (array_key_exists($lowerCol, $lowerResult)) {
                    $this->$colom = $lowerResult[$lowerCol];
                } else {
                    $this->$colom = null;
                }
            }
        }
        return $this;
    }

    public function size()
    {
        return count($this->_result ?? []);
    }

    public function __construct()
    {
        $this->_orm_coloms = [];
        foreach ($this as $key => $value) {
            if (substr($key, 0, 1) === '_' && $key != '_table') {
                continue;
            }

            if ($key != '_table') {
                $this->_orm_coloms[] = $key;
            } else {
                $this->_orm_table = $value;
            }
        }

        $this->connect();
        if ($this->_orm_table !== null) {
            $this->table($this->_orm_table);
        }
        $this->_orm_pk = isset($this->_pk) ? $this->_pk : 'id';
        $this->_orm_ai = isset($this->_ai) ? $this->_ai : null;
    }

    public function __destruct()
    {
    }

    // Save the current ORM record.
    public function save($sync = false)
    {
        //OrmEvent::run('_before_insert');
        $data = [];
        if (is_array($this->_orm_coloms)) {
            foreach ($this->_orm_coloms as $colom) {
                if (isset($this->$colom)) {
                    $data[$colom] = $this->$colom;
                }
            }
        }
        // Exclude auto-increment primary key from insert payload so the database engine generates IDs cleanly
        if ($this->_orm_ai !== null && array_key_exists($this->_orm_ai, $data)) {
            unset($data[$this->_orm_ai]);
        }

        if ($this->db_insert($this->_orm_table, $data)) {
            $insertId = $this->insert_id();
            if ($this->_orm_ai !== null && $insertId) {
                $aiKey = $this->_orm_ai;
                $this->$aiKey = is_numeric($insertId) ? (int)$insertId : $insertId;
            }
            if ($sync === true) {
                $pkCol = !empty($this->_orm_pk) ? $this->_orm_pk : 'id';
                $pkValue = ($this->_orm_ai !== null && $insertId) ? $insertId : (isset($this->$pkCol) ? $this->$pkCol : $insertId);
                $this->where(" " . $pkCol . "='" . $pkValue . "'");
                $this->getResultOne(true, true);
                $this->sync();
                $this->_where = null;
            }
            return $this;
        }
        return false;
    }

    // Update the current ORM record.
    public function update($key = null)
    {
        if ($key !== null) {
            $resolvedKey = $this->resolve_column($key) ?? $key;
            try {
                Obuilder::check_key_exists($resolvedKey, $this->_orm_coloms);
            } catch (OrmException $e) {
                $e->show();
                exit();
            }

            try {
                $this->check_object_exists($resolvedKey);
            } catch (OrmException $e) {
                $e->show();
                exit();
            }
            $where = [$resolvedKey => $this->$resolvedKey];
        } elseif (isset($this->_where)) {
            $where = $this->_where;
        } else {
            $pkCol = !empty($this->_orm_pk) ? $this->_orm_pk : 'id';
            $resolvedKey = $this->resolve_column($pkCol) ?? $pkCol;
            try {
                Obuilder::check_key_exists($resolvedKey, $this->_orm_coloms);
            } catch (OrmException $e) {
                $e->show();
                exit();
            }

            try {
                $this->check_object_exists($resolvedKey);
            } catch (OrmException $e) {
                $e->show();
                exit();
            }
            $where = [$resolvedKey => $this->$resolvedKey];
        }

        $data = [];
        if (is_array($this->_orm_coloms)) {
            foreach ($this->_orm_coloms as $colom) {
                if (isset($this->$colom)) {
                    $data[$colom] = $this->$colom;
                }
            }
        }

        // Exclude auto-increment primary key from update payload
        if ($this->_orm_ai !== null && array_key_exists($this->_orm_ai, $data)) {
            unset($data[$this->_orm_ai]);
        }

        if (empty($data)) {
            return true;
        }

        // DB update
        if ($this->db_update($this->_orm_table, $data, $where)) {
            return true;
        }
        return false;
    }

    public function findByPk($id = null)
    {
        $this->where($this->_orm_pk . "='" . $id . "'");
        return $this;
    }

    public function all()
    {
        $this->getResult(true, true);
        return $this;
    }

    public function flush()
    {
        parent::flush();
        if ($this->_orm_table !== null) {
            $this->table($this->_orm_table);
        }
        $this->_result = null;
        $this->_db_result = null;
        $this->_query_result = null;
        $this->_cache_result = null;
        if (is_array($this->_orm_coloms)) {
            foreach ($this->_orm_coloms as $key) {
                $this->$key = null;
            }
        }
        return $this;
    }

    public function check_object_exists($key)
    {
        $resolvedKey = $this->resolve_column($key) ?? $key;
        if (isset($this->$resolvedKey) === false) {
            throw new OrmException("Object '{$key}' is undefined - update key not found!");
            return false;
        }
        return true;
    }
}
