<?php

class RunException extends Exception{
    public $mod;
    public $method;
    
    public function __construct($mod,$method){
        $this->mod=str_replace('_mod','',get_class($mod));
        $this->method=$method;
    }
    public function show(){
        require_once(lib_dir . 'AmedError.class.php');
        AmedError::method($this->mod . '/' . $this->method);
    }
}
class Run
{
    public static function execute($a, $b, $ext = NULL)
    {
        if (method_exists($a, $b)) {
            if (isset($ext['0']) === FALSE) {
                return call_user_func([$a, $b]);
            }
            return call_user_func_array([$a, $b], $ext);
        }
        throw new RunException($a, $b, $ext);
        return FALSE;
    }

    public static function import($mod, $method, $array = NULL)
    {
        $mod = Load::mod($mod);
        return self::execute($mod, $method, $array);
    }
}