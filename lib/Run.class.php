<?php

class RunException extends Exception{
    public $mod;
    public $method;
    
    public function __construct($mod,$method){
        $this->mod=str_replace('_mod','',get_class($mod));
        $this->method=$method;
    }
    public function show(){
        $lang = Kayit::getir('lang');
        if ($lang == 'ku') {
            echo "Metoda <b>".$this->method."</b> ya Modûla " . $this->mod . " Nehat Dîtin";
        } elseif ($lang == 'tr') {
             echo $this->mod." Modülünün <b>".$this->method."</b> Methodu Bulunamadı";
        } else {
             echo "Method <b>".$this->method."</b> of Module " . $this->mod . " Not Found";
        }
    }
}
class Run
{
    public static function calistir($a, $b, $ext = NULL)
    {

        if (method_exists($a, $b)) {
            if (isset($ext['0'])===FALSE) {
                return call_user_func([$a, $b]);
            }
            return call_user_func_array([$a, $b], $ext);

        }
        throw new RunException($a,$b,$ext);
        return FALSE;
    }

    public static function import($mod, $method, $array = NULL)
    {
        // var_dump($mod, $method, $array);
        // var_dump(gettype($mod), gettype($method), gettype($array));
        // var_dump($array[0]);
        // $array[0]->getx('menu');
        $mod = Load::mod($mod);
        return self::calistir($mod, $method, $array);
    }
}