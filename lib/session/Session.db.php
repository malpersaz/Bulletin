<?php

class Session_Db
{
    public $db;

    public function __construct()
    {
        $this->db = new Database();
        session_set_save_handler(
            [$this, '_open'],
            [$this, '_close'],
            [$this, '_read'],
            [$this, '_write'],
            [$this, '_destroy'],
            [$this, '_clean']
        );
    }

    public function _open($save_path, $session_name)
    {

        return TRUE;
    }

    public function _close()
    {
        return TRUE;
    }

    public function _read($id)
    {
        $sql = "SELECT data FROM sessions WHERE  id = '" . $id . "'";
        $this->db->fetch_array($sql)->getResultOne();
        if ($this->db->getSize() != 0) {
            return $this->db->toObject()->data;
        }

        return '';
    }

    public function _write($id, $data)
    {
        $stime = time();
        $sql = "REPLACE INTO sessions(id,data,stime) VALUES('$id', '$data', '$stime')";
        $this->db->query($sql);
        return TRUE;
    }

    public function _destroy($id)
    {
        $sql = "DELETE FROM sessions WHERE  id = '$id'";
        $this->db->query($sql);
        return TRUE;
    }

    public function _clean($max)
    {
        $old = time() - $max;
        $sql = "DELETE FROM   sessions WHERE  stime < '$old'";
        $this->db->query($sql);
        return TRUE;
    }

    public function __destruct()
    {

    }
}