<?php

class Session_Db implements SessionHandlerInterface
{
    public $db;

    public function __construct()
    {
        $this->db = new Database();
        session_set_save_handler($this, true);
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $quotedId = $this->db->quote($id);
        $sql = "SELECT data FROM sessions WHERE id = {$quotedId}";
        $this->db->fetch_array($sql)->getResultOne();
        if ($this->db->getSize() != 0) {
            return (string) $this->db->toObject()->data;
        }

        return '';
    }

    public function write(string $id, string $data): bool
    {
        $stime = time();
        return (bool) $this->db->db_insert('sessions', [
            'id' => $id,
            'data' => $data,
            'stime' => $stime,
        ], true);
    }

    public function destroy(string $id): bool
    {
        return (bool) $this->db->make_delete('sessions', ['id' => $id])->run();
    }

    public function gc(int $max_lifetime): int|false
    {
        $old = time() - $max_lifetime;
        $sql = "DELETE FROM sessions WHERE stime < '" . $old . "'";
        $this->db->query($sql);
        return (int) $this->db->affected_rows();
    }

    public function _open($save_path, $session_name)
    {
        return $this->open((string) $save_path, (string) $session_name);
    }

    public function _close()
    {
        return $this->close();
    }

    public function _read($id)
    {
        return $this->read((string) $id);
    }

    public function _write($id, $data)
    {
        return $this->write((string) $id, (string) $data);
    }

    public function _destroy($id)
    {
        return $this->destroy((string) $id);
    }

    public function _clean($max)
    {
        return (bool) $this->gc((int) $max);
    }

    public function __destruct()
    {

    }
}