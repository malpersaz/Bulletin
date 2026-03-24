<?php 
class Valid_Core{
	public function valid_required($arg)
	{
		if(Req::is_Request($arg['name'])===False)
		{
			return False;
		}
		return True;	
	}
	public function valid_not_empty($arg)
	{
		if(Req::is_Request($arg['name'])===False){
			return False;
		}
		if(Req::Request($arg['name'])=='')
		{
			return False;
		}
		return True;
		
	}
	
	
	public function valid_int($arg){
		if(Req::is_Request($arg['name'])===False){
			return False;
		}
		if(is_int(Req::Request($arg['name']))===False)
		{
			return False;
		}
		return True;
		
	}
	
	public function valid_num($arg){
		if(Req::is_Request($arg['name'])===False){
			return False;
		}
		if(filter_var(Req::Request($arg['name']),FILTER_VALIDATE_INT)===False)
		{
			return False;
		}
		return True;
		
	}
	
	public function valid_string($arg){
		if(Req::is_Request($arg['name'])===False){
			return False;
		}
		if(is_string(Req::Request($arg['name']))===False)
		{
			return False;
		}
		return True;
		
	}
	
	public function valid_alphanum($arg){
		if(Req::is_Request($arg['name'])===False){
			return False;
		}
		if(preg_match("/^[a-zA-Z0-9]+$/",Req::Request($arg['name']))===0)
		{
			return False;
		}
		return True;
		
	}
	
	public function valid_char($arg){
		if(Req::is_Request($arg['name'])===False){
			return False;
		}
		if(preg_match("/^[a-zA-Z]+$/",Req::Request($arg['name']))===0)
		{
			return False;
		}
		return True;
		
	}
	
	public function valid_double($arg){

		if(Req::is_Request($arg['name'])===False){
			return False;
		}		
		if(preg_match('/^\d+(\.\d{2}+)?$/',Req::Request($arg['name']))==0)
		{
			return False;
		}
		return True;
	}
	
	// ^[a-zA-ZığüşöçİĞÜŞÖÇîêûÎÊÛ\s]+$
	public function valid_trchar($arg){

		if(Req::is_Request($arg['name'])===False){
			return False;
		}		
		if(preg_match('/[^a-zA-ZığüşöçİĞÜŞÖÇîêûÎÊÛ ]+$/',Req::Request($arg['name']))===1)
		{
			return False;
		}
		return True;
	}
	public function valid_date($arg){

		if(Req::is_Request($arg['name'])===False){
			return False;
		}		
		if(preg_match('/[\d]{4}-[\d]{2}-[\d]{2}/',Req::Request($arg['name']))===0)
		{
			return False;
		}
		return True;
	}
	// ^[0 (]+[4|3|2][\d]{2}\)\s[1-9][\d]{2}-[\d]{4}
	//0 (455) 555-5555
	public function valid_telii($arg){

		if(Req::is_Request($arg['name'])===False){
			return False;
		}		
		if(preg_match('/^(0) \([4](\d){2}\) [1-9](\d){2}[-](\d){4}$/',Req::Request($arg['name']))===0)
		{
			return False;
		}
		return True;
	}
	//^http(s)?:\/\/([a-zA-Z0-9]+\.)?([a-z]+\.)([a-zA-Z]){2,4}(\.[a-zA-Z]{2,4})?
	//http://www.mynet.com.tr
	public function valid_web($arg){

		if(Req::is_Request($arg['name'])===False){
			return False;
		}		
		if(preg_match('/^http(s)?:\/\/([a-zA-Z0-9]+\.)?([a-z]+\.)([a-zA-Z]){2,4}(\.[a-zA-Z]{2,4})?/',Req::Request($arg['name']))===0)
		{
			return False;
		}
		return True;
	}
	
	
	//0 (555) 555-5555
	public function valid_cepii($arg){

		if(Req::is_Request($arg['name'])===False){
			return False;
		}		
		if(preg_match('/^(0) \([5](\d){2}\) [1-9](\d){2}[-](\d){4}$/',Req::Request($arg['name']))===0)
		{
			return False;
		}
		return True;
	}
	// 0 555 555 5555
	public function valid_cepi($arg){

		if(Req::is_Request($arg['name'])===False){
			return False;
		}		
		if(preg_match('/^(0) [5](\d){2} [1-9](\d){2} (\d){4}$/',Req::Request($arg['name']))===0)
		{
			return False;
		}
		return True;
	}
	// 05555555555
	public function valid_cep($arg){

		if(Req::is_Request($arg['name'])===False){
			return False;
		}		
		if(preg_match('^(05)([0-9]){2}[1-9]([0-9]){2}([0-9]){4}$',Req::Request($arg['name']))===0)
		{
			return False;
		}
		return True;
	}
	
	
	public function valid_email($arg){
		if(Req::is_Request($arg['name'])===False){
			return False;
		}
		if(preg_match("/.+@.+\..+./",Req::Request($arg['name']))===0)
		{
			return False;
		}
		/*if(checkdnsrr(array_pop(explode("@",Req::Request($arg['name']))),"MX")===False){
			return False;
		}*/
		return True;
		
	}
	
	public function valid_length($arg){
		if(Req::is_Request($arg['name'])===False){
			return False;
		}
		$say=$this->_say(Req::Request($arg['name']));
		if($say < $arg['arg']['min']){
			return False;
		}
		if($say > $arg['arg']['max']){
			return False;
		}
		return True;
	}
	
	public function valid_tcno($arg){
		if(Req::is_Request($arg['name'])===False){
			return False;
		}
		if($this->_check_tcno(Req::Request($arg['name']))===False){
			return False;
		}
		return True;
	}
	
	public function valid_valid($arg){
		if(Req::is_Request($arg['name'])===False){
			return False;
		}
		//$f=$arg['arg']['function'];
		if($arg['arg']['function'](Req::Request($arg['name']))===False){
			return False;
		}
		return False;
	}
	
	public function valid_array($arg){
		if(Req::is_Request($arg['name'])===False){
			return False;
		}	
		if(in_array(Req::Request($arg['name']),$arg['arg'])===False){
			return False;
		}
		return True;
	}
	public function valid_equal($arg){
		if(Req::is_Request($arg['name'])===False){
			return False;
		}	
		if(Req::Request($arg['name'])!=Req::Request($arg['arg'])){
			return False;
		}
		return True;
		
	}
	public function valid_not_equal($arg){
		if(Req::is_Request($arg['name'])===False){
			return False;
		}	
		if(Req::Request($arg['name'])==Req::Request($arg['arg'])){
			return False;
		}
		return True;
		
	}
	
	private function _check_tcno($tcno) {
		$sonIki='';
		preg_replace('/([1-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1}).*$/e', "eval('\$on=((((\\1+\\3+\\5+\\7+\\9)*7)-(\\2+\\4+\\6+\\8))%10); \$onbir=(\\1+\\2+\\3+\\4+\\5+\\6+\\7+\\8+\\9+\$on)%10; \$sonIki = \$on.\$onbir;')", $tcno);
		return(substr($tcno, -2) === $sonIki);
	}
	private function _say($str){
		return mb_strlen($str,"utf-8");
	}


}