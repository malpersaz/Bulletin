<?php
header('Content-Type: text/html; charset=utf-8');
// İnstaller interface zorunlu fonksiyonları belirttik
interface installerbaseinterface
{
    public function __construct();

    public function install();
}


class installerbase
{
    public $mod;
    public $mod_id;
    public $rolls;
    public $db;

    public function __construct()
    {
        $this->db = new Database();

    }


    public function check_table_exists($table)
    {

        if($this->db->getAdapterType() && strpos($this->db->getAdapterType(), 'PDO_SQLITE') !== false){
            $this->db->fetch_array("SELECT name FROM sqlite_master WHERE type='table' AND name='" . $table . "'");
        }else{
            $this->db->fetch_array("show tables like '" . $table . "'");
        }

        if ($this->db->getSize() === 0) {
            return FALSE;
        }
        return TRUE;
    }

    public function install_sql_file($file)
    {
        $mods_sql_text = $this->load_sql_file($this->mod . DS .$file);
        if ($this->db->query($mods_sql_text)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function load_sql_file($file)
    {
        if (file_exists($file) === FALSE) {
            throw new Exception($file . " Not Found");
        }
        return file_get_contents($file);
    }


    public function oke($message)
    {
        echo $this->mod . ": <font color='green'>" . $message . "</font>";
        echo "<br>";
    }

    public function error($message)
    {
        echo $this->mod . ": <font color='red'>" . $message . "</font>";
        echo "<br><font color='red'>Database Error: " . $this->db->sql_error() . "</font>";
        echo "<br>";
    }

    public function warning($message)
    {
        echo $this->mod . ": <font color='#ffd700'>" . $message . "</font>";
        echo "<br>";
    }

    public function system_mesaj($message)
    {
        echo "<font style='color:green;font-size:20px;'>" . $message . "</font>";
        echo "<br>";
    }

    /**
     * Inserts module into 'mods' table
     */
    protected function insert_mods()
    {
        // Create fresh model for each module insertion
        $checkModel = Load::model('mods');
        $checkModel->where_mod_name($this->mod)->gRo();

        if ($checkModel->getSize() === 0) {
            $model = Load::model('mods');
            $model->mod_id = NULL; // Force auto-increment
            $model->mod_name = $this->mod;
            if ($model->save(TRUE) === FALSE) {
                $this->error(" ERROR OCCURRED WHILE ADDING MODULE TO 'mods' TABLE:" . print_r($model, TRUE));
                return FALSE;
            }
            $this->mod_id = $model->mod_id;
        } else {
            $result = $checkModel->getResultOne()->toArray();
            $this->mod_id = $result['mod_id'];
        }
        return TRUE;
    }

    /**
     * Inserts roles into 'mod_user_roll' table
     */
    protected function insert_roll()
    {
        if (is_array($this->rolls)) {
            foreach ($this->rolls AS $roll) {
                $model = Load::model('mod_user.mod_user_roll');
                $model->where_mod_id_and_roll_name($this->mod_id, $roll)->limit(1)->getResultOne(TRUE, TRUE);
                if ($model->getSize() === 0) {
                    $model->roll_id = NULL; // Reset ID for auto-increment
                    $model->mod_id = $this->mod_id;
                    $model->roll_name = $roll;
                    if ($model->save(TRUE) === FALSE) {
                        $this->error(" ERROR OCCURRED WHILE ADDING ROLES:" . print_r($model));
                        return FALSE;
                    }
                    $this->oke("Role '" . $roll . "' added");
                } else {
                    // Sync loaded model
                    $model->sync();
                    $this->warning("Role '" . $roll . "' already exists in database");
                }
                // Add permissions if they exist
                if ($this->addperm($model->roll_id, $model->roll_name) === FALSE) {
                    return FALSE;
                }
            }
        } else {
            $this->warning("No roles found to add!");
        }
        return TRUE;
    }

    /**
     * Inserts permissions into 'mod_user_perm' table
     */
    protected function addperm($roll_id, $roll_name)
    {
        $pmodel = Load::model('mod_user.mod_user_perm');

        $pmodel->where_mod_id_and_roll_id_and_group_id($this->mod_id, $roll_id, '1')->limit(1)->getResultOne(TRUE, TRUE);
        if ($pmodel->getSize() === 0) {
            $pmodel->group_id = 1;
            $pmodel->roll_id = $roll_id;
            $pmodel->mod_id = $this->mod_id;
            if ($pmodel->save() === FALSE) {
                $this->error(" ERROR OCCURRED WHILE ADDING PERMISSIONS:" . print_r($pmodel));
                return FALSE;
            }
            $this->oke("Role '" . $roll_name . "' added as permission for Group ID 1");
        } else {
            $this->warning("Role '" . $roll_name . "' already exists as permission for Group ID 1");
        }
        return TRUE;
    }

    protected function create_tables()
    {
        foreach ($this->tablolar AS $tablo) {
            if ($this->check_table_exists($tablo) === TRUE) {
                $this->warning(" Table '" . $tablo . "' is already installed in database");
            } elseif ($this->install_sql_file($tablo . ".sql")) {
                $this->oke(" File '" . $tablo . ".sql' added to database");
            } else {
                $this->error(" Database table '" . $tablo . "' could not be created");
                return FALSE;
            }
        }
    }

    // Drop table
    public function drop_table($table)
    {
        if ($this->db->query("DROP TABLE " . $table)) {
            return TRUE;
        }
        return FALSE;
    }

    // Remove table
    protected function remove_tables()
    {
        foreach ($this->tablolar AS $tablo) {
            if ($this->check_table_exists($tablo) === FALSE) {
                $this->warning(" Table '" . $tablo . "' is not installed in database");
            } elseif ($this->drop_table($tablo)) {
                $this->oke(" Table '" . $tablo . "' dropped");
            }
        }
    }

    protected function remove_mods()
    {
        $model = Load::model('mods');
        $model->where_mod_name($this->mod)->gRo();
        if ($model->getSize() !== 0) {
            $model->sync();
            $this->mod_id = $model->mod_id;
            if ($model->delete()) {
                $this->oke(" Module removed from 'mods' table");
            }
            $this->mod_id = $model->mod_id;
        } else {
            $this->warning(" Module not found in 'mods' table");
        }
        return TRUE;
    }

    protected function remove_rool()
    {
        $model = Load::model('mod_user.mod_user_roll');
        if ($this->mod_id == '') {
            return TRUE;
        }
        $model->where_mod_id($this->mod_id);
        if ($model->delete()) {
            $this->oke(" Roles belonging to the module deleted from 'mod_user_roll' table");
        }
        return TRUE;
    }

    protected function remove_perm()
    {
        $model = Load::model('mod_user.mod_user_perm');
        if ($this->mod_id == '') {
            return TRUE;
        }
        $model->where_mod_id($this->mod_id);
        if ($model->delete()) {
            $this->oke(" Permissions belonging to the module deleted from 'mod_user_perm' table");
        }
        return TRUE;
    }
}