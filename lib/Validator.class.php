<?php

include(__DIR__ . '/Valid_Core.php');

class Validator{
	public $valid;
    private $errors=[];
	public function __construct(){
		$this->valid=new Valid_Core();
	}
	public function ekle($veri){
		$arg = isset($veri['arg']) ? $veri['arg'] : '';
		$fonk = isset($veri['func']) ? $veri['func'] : (isset($veri['fonk']) ? $veri['fonk'] : '');
		$name = isset($veri['name']) ? $veri['name'] : '';
		$mesaj = isset($veri['message']) ? $veri['message'] : (isset($veri['mesaj']) ? $veri['mesaj'] : '');
		
		if($this->calistir($this->valid,$fonk,$veri)===False){
		 	$this->errors[]=$mesaj;
		 }
	}
	public function add($veri){
		$this->ekle($veri);
	}
	private function calistir($class,$method,$veri){
		$method="valid_".$method;
		if(method_exists($class,$method)){
			return (bool) $class->$method($veri);
		}
        exit(Helper::red_text(Req::Clear($method).' Validator Function Not Found'));
	}
	
	public function check(){
		if(count($this->errors)>0){
			return True;
		}
        return False;
	}
	public function list_errors(){
		return $this->errors;
	}
	
}