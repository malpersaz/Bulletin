<?php

/**
 * Koberz Content Management System
 * Web: www.koberz.com
 * E-Mail: info@koberz.com
 * User: Kobim
 * Date: 13.04.2011 - 20:33
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

abstract class ORM extends Database implements Orm_interface
{
    private $_ai;
    private $_orm_table;
    private $_orm_coloms;
    private $_orm_pk;
    public $_orm_ai;


    public function __call($name, $value)
    {
        try {
            // id|ad|seo
            $desen = implode("|", $this->_orm_coloms);
            // $model->id(1);
            $colom_desen = "!^(" . $desen . ")$!";
            // $model->getid(1);
            // $model->Getid(1);
            $get_desen = "!^(g|G)et(.+)$!";
            // $model->delete_id(1);
            // $model->Delete_id(1);
            $delete_desen = "!^(d|D)elete_(.+)$!";
            // $model->where_id(1); $model->Where_id(1);
            // $model->where_id_and_seo(1,'seo-key');
            //$where_desen = "!^(w|W)here_((".$this->wdesen."|_)+)!";
            $where_desen = "!^(w|W)here_(.+)!";

            // $model->exists_id('1');
            // $model->Exists_id_and_seo('1','example');
            $exists_desen = "!^(e|E)xists_(.+)!";

            // Oke
            if (preg_match($exists_desen, $name, $result)) {
                return $this->_call_exists($result, $value);
            }
            // Oke
            if (preg_match($where_desen, $name, $result)) {
                $this->_call_where($result, $value);
                return $this;
            }
            // Oke
            if (preg_match($delete_desen, $name, $result)) {
                $this->_call_delete($result, $value);
                return $this;
            }
            // Oke
            if (preg_match($colom_desen, $name, $result)) {
                $this->_call_colom($result, $value);
                return $this;
            }
            // Oke
            if (preg_match($get_desen, $name, $result)) {
                $this->_call_get($result, $value);
                return $this;
            }
            throw new OrmException(
                "'" . $name . "' Function Not Found\n" .
                    "'" . $name . "' Function Not Found!"
            );
        } catch (OrmException $e) {
            $e->show();
        }
    }

    private function _call_delete($result, $value)
    {
        //$this->where(Obuilder::where($result['2'], $value));
        $this->make_delete($this->_orm_table, Obuilder::where($result['2'], $value, $this->_orm_coloms, $this->_orm_table));
        return $this;
    }

    private function _call_exists($result, $value)
    {
        /*try {
            // Column names with underscores need escaping.
            // Escape names such as user_id before building the condition.
            $wdesen = implode("|", array_merge(array_keys(Obuilder::$equations), Obuilder::$valid_after_key, $this->_orm_coloms));
            $d = '#_((' . $wdesen . ')+)#';
            $cikti = preg_replace($d, '\_$1', $result[2]);
            $this->where(Obuilder::where($cikti, $value, $this->_orm_coloms));
            $this->getResult(TRUE, TRUE);
            return $this->_result === NULL ? FALSE : TRUE;
        } catch (OrmException $e) {
            $e->show();
            exit();
        }*/
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
            $wdesen = implode("|", array_merge(array_keys(Obuilder::$equations), Obuilder::$valid_after_key, $this->_orm_coloms));
            $d = '#_?((' . $wdesen . ')+)#';
            $cikti = preg_replace($d, '\_$1', $result[2]);
            // Remove a leading escaped underscore so the condition remains valid.
            $dd = '#^\\\_(.)#';
            $cikti = preg_replace($dd, '$1', $cikti);
            $where = Obuilder::where($cikti, $value, $this->_orm_coloms, $this->_orm_table);
            $this->where($where);
        } catch (OrmException $e) {
            $e->show();
            exit();
        }
        /*
                try {


                    // Column names with underscores need escaping.
                    $wdesen = implode("|", array_merge(array_keys(Obuilder::$equations), $this->_orm_coloms));

                    $cikti=preg_replace("@(".$wdesen.")@", '$1\\', $result['2']);

                    $cikti=rtrim($cikti,"\\");


                    $this->where(Obuilder::where($cikti, $value, $this->_orm_coloms, $this->_orm_table));
                    //$this->getResult(TRUE, TRUE);
                } catch (OrmException $e) {
                    $e->show();
                    exit();
                }
        */
    }

    private function _call_colom($colom, $value)
    {
        try {
            Obuilder::check_key_exists($colom['1'], $this->_orm_coloms);
        } catch (OrmException $e) {
            $e->show();
        }

        $this->where($colom['1'] . "='" . $value['0'] . "'");
        //$this->getResult(TRUE, TRUE);
    }

    private function _call_get($colom, $value)
    {
        try {
            Obuilder::check_key_exists($colom['2'], $this->_orm_coloms);
        } catch (OrmException $e) {
            $e->show();
            exit();
        }
        $this->where($colom['2'] . "='" . $value['0'] . "'");
        //$this->getResultOne(TRUE, TRUE);
        if ($this->_result !== null) {
            // Sync
            foreach ($this->_orm_coloms as $value) {
                $this->$value = isset($this->_result[$value]) ? $this->_result[$value] : null;
            }
            return true;
        }
        return false;
    }

    // Sync the current object when getResultOne has loaded a record.
    public function sync($results = false)
    {
        if ($results === false) {
            if (!is_array($this->_result)) {
                logla(print_r($this, true), "sync");
                throw new OrmException(
                    "Result Not Found in Sync\n" .
                        "Result to be synced not found!"
                );
            }
            foreach ($this->_orm_coloms as $value) {
                $this->$value = isset($this->_result[$value]) ? $this->_result[$value] : null;
            }
            return $this;
        }

        if (!isset($this->_result[0])) {
            logla(print_r($this, true), "sync1");
            throw new OrmException(
                "Result Not Found in Sync\n" .
                    "Result to be synced not found!"
            );
        }
        foreach ($this->_orm_coloms as $value) {
            $this->$value = isset($this->_result['0'][$value]) ? $this->_result['0'][$value] : null;
        }
        return $this;
    }

    public function size()
    {
        return count($this->_result);
    }

    /*
        public function delete()
        {
            $this->db->delete();
        }
    */
    public function __construct()
    {

        /* $disable = array(
            '_after_update', '_before_update',
            '_after_insert', '_before_insert',
            '_after_delete', '_before_delete',
            '_model_table', '_model_coloms',
            '_db_result','_result','_db','_query_count','_adapter','_coloms','_limit','_order','_where','_cache'
        );*/
        /*if (in_array($key, $disable)) {
            continue;
        }*/

        foreach ($this as $key => $value) {
            if (substr($key, 0, 1) === '_' && $key != '_table') {
                continue;
            }

            if ($key != '_table') {
                $this->_orm_coloms[] = $key;
            } else {
                $this->_orm_table = $value;
            }
            //unset($this->$key);
        }


        //$this->_model_coloms = $_coloms;
        //$this->_model_table = $_table;
        // Get DB
        //$this->db= new Database();
        $this->connect();
        $this->table($this->_orm_table);
        //Obuilder::$_orm_coloms = $this->_orm_coloms;
        $this->_orm_pk = $this->_pk;
        $this->_orm_ai = isset($this->_ai) ? $this->_ai : null;
    }


    public function __destruct()
    {
        /*$this->db->build();
        echo $this->db->sql_text;
*/
        //$this->result = $this->db->result;
    }


    // Save the current ORM record.
    public function save($sync = false)
    {
        //OrmEvent::run('_before_insert');
        $data = [];
        foreach ($this->_orm_coloms as $colom) {
            isset($this->$colom) ? $data[$colom] = $this->$colom : null;
        }
        // Set orm_pk to NULL
        if ($this->_orm_ai !== null && isset($data[$this->_orm_ai])) {
            $data[$this->_orm_ai] = null;
        }

        if ($this->db_insert($this->_orm_table, $data)) {
            if ($sync === true) {
                $this->where(" " . $this->_orm_pk . "='" . $this->insert_id() . "'");
                $this->getResultOne(true, true);
                $this->sync();
            }
            return $this;
        }
        return false;
    }

    // Update the current ORM record.
    public function update($key = null)
    {
        /*if ($key === NULL) {
            $key = $this->_orm_pk;
        }*/

        if ($key !== null) {
            // if $key check key is exists?
            // Is $key defined?
            try {
                Obuilder::check_key_exists($key, $this->_orm_coloms);
            } catch (OrmException $e) {
                $e->show();
                exit();
            }

            // Check Update Key is Exists?
            try {
                $this->check_object_exists($key);
            } catch (OrmException $e) {
                $e->show();
                exit();
            }
            $where = [$key => $this->$key];
        } elseif (isset($this->_where)) {
            $where = $this->_where;
        } else {
            $key = $this->_orm_pk;
            try {
                Obuilder::check_key_exists($key, $this->_orm_coloms);
            } catch (OrmException $e) {
                $e->show();
                exit();
            }

            // Check Update Key is Exists?
            try {
                $this->check_object_exists($key);
            } catch (OrmException $e) {
                $e->show();
                exit();
            }
            $where = [$key => $this->$key];
        }


        $data = [];
        foreach ($this->_orm_coloms as $colom) {
            isset($this->$colom) ? $data[$colom] = $this->$colom : null;
        }

        // unset
        if ($this->_orm_ai !== null && isset($data[$this->_orm_ai])) {
            unset($data[$this->_orm_ai]);
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
        //$this->getResult(TRUE, TRUE);
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
        foreach ($this->_orm_coloms as $key) {
            $this->$key = null;
        }
        return $this;
    }

    public function check_object_exists($key)
    {
        if (isset($this->$key) === false) {
            throw new OrmException(
                " $key object is Undefined! - Update Key Not Found!\n" .
                    " $key object is undefined - Update Key not found!"
            );
            return false;
        }
        return true;
    }
}
