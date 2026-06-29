<?php
 /**
 * AKM Business İçerik Yönetim Sistemi
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * Kullanıcı: a.k.mutlu
 * Zaman: 16.04.2011- 14:39
 * Sınıfı : Make_Model
 */ 
class Make_Model {


    public $db;
    public $pk;
    public $ai;
    public $coloms;
    public $source;
    public function __construct(){
        $this->db=new Database();
        //$this->db->connect('localhost');
    }

    public function generate($table){
        $this->get_coloms($table);
        $v="public \$_table = \"$table\";\n";
        $v.="public \$_pk = \"$this->pk\";\n";
        if(isset($this->ai)){
            $v.="public \$_ai = \"$this->ai\";\n";
        }
        
        foreach($this->coloms[$table] as $colom){
            $v.="public $$colom;\n";
        }
        $this->source[$table]='<?php class '.$table.'_model extends ORM{
            '.$v.'
            }
        ';
    }

      public function generatearray($table){
        $this->get_coloms($table);
        $v="public \$_table = \"$table\";\n";
        $v.="public \$_pk = \"$this->pk\";\n";
        $v.="public \$_ai = \"$this->ai\";\n";
        $v.="public \$_coloms=array(\n";
        $v.="'";
        $v.=implode("',\n'",$this->coloms[$table]);
        /*foreach($this->coloms[$table] as $colom){
            $v.="public \$_coloms=$colom;\n";
        }*/
        $v.="'\n);";
        $this->source[$table]='<?php class '.$table.' extends ORM{
            '.$v.'}
        ';
    }
    public function get_source($table){
        if(!isset($this->source[$table])){
            $this->generate($table);
        }
        if(!isset($this->source[$table])){
            throw new Exception($table.' Source Oluşturulamadı!');
        }
        echo '<pre>';
        echo htmlspecialchars($this->source[$table]);
        echo '</pre>';
    }

    public function get_coloms($table){
        $this->db->fetch_array ( "SHOW COLUMNS FROM `".$table."`");
		foreach ( $this->db->getResult()->toArray() as $v ) {
			$this->coloms[$table][] = $v ['Field'];
            if($v['Extra']==='auto_increment' AND $v['Key']==='PRI'){
                $this->pk=$v ['Field'];
                $this->ai=$v ['Field'];
            }elseif($v['Key']==='PRI'){
                $this->pk=$v ['Field'];
            }
            
		}


        /*if($this->coloms[$table]<1){
            throw new Exception('COlomlar alınamadı!');
        }*/
		return TRUE;
    }
}
