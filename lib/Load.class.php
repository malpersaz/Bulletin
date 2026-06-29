<?php

/**
 * Amed Framework
 * User: a.k.mutlu
 * Date: 4/23/11 - 7:12 PM
 * Copyright: AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * Class: Load
 * File: Load.class.php
 * Description: Model, helper, and module loader
 */

class LoadFileException extends Exception
{
    public $mesaj;
    public $type;
    public $filePath;
    public function __construct($mesaj, $type, $file)
    {
        $this->mesaj = $mesaj;
        $this->type = $type;
        $this->filePath = $file;
    }

    public function show()
    {
        $indexLang = $this->loadIndexLanguage();
        if (!isset($indexLang['index']['file_not_found'])) {
            throw new RuntimeException('Missing index language key: index.file_not_found');
        }

        echo sprintf($this->mesaj, $this->type);
        echo "<br>";
        echo $indexLang['index']['file_not_found'] . ":\n";
        echo "<b>" . $this->filePath . "</b>";
    }

    private function loadIndexLanguage()
    {
        if (Kayit::varmi('index-lang') !== False) {
            return Kayit::getir('index-lang');
        }

        $currentLang = Kayit::getir('lang');
        $file = mod_dir . 'index' . DS . lang_dir . $currentLang . '.php';

        if (file_exists($file) === FALSE) {
            throw new RuntimeException('Missing index language file: ' . $file);
        }

        require($file);
        Kayit::ekle('index-lang', $lang);

        return $lang;
    }
}

class ClassException extends Exception
{
    public $mesaj;
    public $type;
    public $filePath;
    public function __construct($mesaj, $type, $file)
    {
        $this->mesaj = $mesaj;
        $this->type = $type;
        $this->filePath = $file;
    }

    public function show()
    {
        echo "<h2>Class Exception</h2>";
        echo "<br>";
        echo sprintf("%s File <b>%s</b> Class Not Defined", $this->type, $this->mesaj);
        echo "<br>";
        echo "\nFile:\n";
        echo "<b>" . $this->filePath . "</b>";
    }
}

class ModException extends Exception
{

    public function show()
    {
        require_once(lib_dir . 'AmedError.class.php');
        $modName = str_replace(['_mod', ' Class Not Found!'], '', $this->getMessage());
        AmedError::mod($modName);
    }
}


class Load
{


    // Loads a helper file.
    public static function helperfile($val)
    {

        if (stristr($val, '.') !== False) {
            $a = explode('.', $val);
            $mod = $a['0'];
            $class = end($a);
            unset($a['0']);
            $s = helper_dir . implode(DS, $a);
            $file = mod_dir . $mod . DS . $s . '.helper.php';
        } else {
            $class = $val;
            $file = mod_dir . $val . DS . helper_dir . $val . '.helper.php';
        }

        $tmp = Kayit::varmi('helperfile') === FALSE ? [] : Kayit::getir('helperfile');
        if (in_array($file, $tmp) === FALSE) {
            try {
                self::loadfile($file, 'Helper');
            } catch (LoadFileException $e) {
                $e->show();
                exit();
            }
            $tmp[] = $file;
            Kayit::yenile('helperfile', $tmp);
        }


        $classadi = $class . '_helper';
        if (!class_exists($classadi)) {
            throw new ClassException($classadi, 'Helper', $file);
        }
        unset($tmp);
        unset($file);
        unset($class);
        unset($val);
        return $classadi;
    }

    // Loads Model File
    public static function modelfile($val)
    {

        if (stristr($val, '.') !== False) {
            $a = explode('.', $val);
            $mod = $a['0'];
            $class = end($a);
            unset($a['0']);
            $s = model_dir . implode(DS, $a);
            $file = mod_dir . $mod . DS . $s . '.model.php';
        } else {
            $class = $val;
            $file = mod_dir . $val . DS . model_dir . $val . '.model.php';
        }

        $tmp = Kayit::varmi('modelfile') === FALSE ? [] : Kayit::getir('modelfile');
        if (in_array($file, $tmp) === FALSE) {
            try {
                self::loadfile($file, 'Model');
            } catch (LoadFileException $e) {
                $e->show();
                exit();
            }
            $tmp[] = $file;
            Kayit::yenile('modelfile', $tmp);
        }


        $classadi = $class . '_model';
        if (!class_exists($classadi)) {
            throw new ClassException($classadi, 'Model', $file);
        }
        unset($tmp);
        unset($file);
        unset($class);
        unset($val);
        return $classadi;
    }

    // Loads Library File
    public static function libfile($val)
    {
        $tmp = Kayit::varmi('libfile') === FALSE ? [] : Kayit::getir('libfile');
        if (in_array($val, $tmp) === FALSE) {
            $file = lib_dir . $val . '.class.php';
            try {
                self::loadfile($file, 'Library');
            } catch (LoadFileException $e) {
                $e->show();
                exit();
            }
            $tmp[] = $val;
            Kayit::yenile('libfile', $tmp);
            if (!class_exists($val)) {
                throw new ClassException($val, 'Library', $file);
                exit("<h2>Class Exception</h2>Library File <b>" . $val . "</b> Class Not Defined<br>
                File:<b>" . $file . "</b>");
            }
        }


        unset($tmp);
        unset($val);
        unset($file);
    }

    // Library Loader
    public static function lib($val)
    {
        if (Kayit::varmi($val . '-lib') === False) {
            try {
                self::libfile($val);
            } catch (ClassException   $e) {
                $e->show();
                exit();
            }

            //self::libfile($val);
            $sonuc = new $val();
            Kayit::ekle($val . '-lib', $sonuc);
            return $sonuc;
        }
        return Kayit::getir($val . '-lib');
    }

    // Model Loader
    public static function model($val)
    {
        if (Kayit::varmi($val . '-model') === False) {

            try {
                $class = self::modelfile($val);
            } catch (ClassException $e) {
                $e->show();
                exit();
            }


            $sonuc = new $class();

            Kayit::ekle($val . '-model', $sonuc);
            return $sonuc;
        }
        return Kayit::getir($val . '-model');
    }


    // Helper Loader
    public static function helper($val)
    {
        if (Kayit::varmi($val . '-helper') === False) {

            try {
                $class = self::helperfile($val);
            } catch (ClassException $e) {
                $e->show();
                exit();
            }


            $sonuc = new $class();

            Kayit::ekle($val . '-helper', $sonuc);
            return $sonuc;
        }
        return Kayit::getir($val . '-helper');
    }

    // Mod Loader
    public static function mod($val)
    {
        if (Kayit::varmi($val . '-mod') === False) {
            $file = mod_dir . $val . DS . 'index.php';
            try {
                self::loadfile($file, 'Module');
            } catch (LoadFileException $e) {
                require_once(lib_dir . 'AmedError.class.php');
                AmedError::mod($val);
                exit();
            }
            $class = $val . '_mod';
            if (!class_exists($class)) {
                throw new ModException($class . " Class Not Found!");
                return FALSE;
            }
            $sonuc = new $class();
            Kayit::ekle($val . '-mod', $sonuc);
            return $sonuc;
        }
        return Kayit::getir($val . '-mod');
    }

    // Config Loader
    public static function config($conf)
    {
        $file = root_dir . 'config' . DS . $conf . '.config.php';

        try {
            self::loadfile($file, 'Config');
        } catch (LoadFileException $e) {
            $e->show();
            exit();
        }
    }

    // Loads Language File
    public static function lang($val)
    {
        global $lang;
        if (Kayit::varmi($val . '-lang') === False) {
            $tmp = Kayit::varmi('langfile') === FALSE ? [] : Kayit::getir('langfile');
            if (in_array($val, $tmp) === FALSE) {
                $file = mod_dir . $val . DS . lang_dir . Kayit::getir('lang') . '.php';
                
                // Fallback to default lang or English if requested language file is missing
                if (file_exists($file) === FALSE) {
                    $defaultLang = Kayit::varmi('default_lang') ? Kayit::getir('default_lang') : 'en';
                    $file = mod_dir . $val . DS . lang_dir . $defaultLang . '.php';
                    
                    if (file_exists($file) === FALSE) {
                        $file = mod_dir . $val . DS . lang_dir . 'en.php';
                        
                        if (file_exists($file) === FALSE) {
                            $langFiles = glob(mod_dir . $val . DS . lang_dir . '*.php');
                            if (!empty($langFiles)) {
                                $file = $langFiles[0];
                            } else {
                                require_once(lib_dir . 'AmedError.class.php');
                                AmedError::lang($file);
                                exit();
                            }
                        }
                    }
                }
                
                require_once($file);
                $tmp[] = $val;
                Kayit::yenile('langfile', $tmp);
                Kayit::ekle($val . '-lang', $lang);
            }
            unset($tmp);
            unset($file);
        }
        return Kayit::getir($val . '-lang');
    }

    // Validator Loader
    public static function validator($val)
    {
        if (Kayit::varmi($val . '-validator') === False) {

            $class = self::validatorfile($val);

            $sonuc = new $class();
            Kayit::ekle($val . '-validator', $sonuc);
            return $sonuc;
        }
        return Kayit::getir($val . '-validator');
    }


    public static function loadfile($file, $type)
    {
        if (file_exists($file)) {
            require_once $file;
            return TRUE;
        }
        throw new LoadFileException("%s", $type, $file);
        return FALSE;
    }


    // Loads Validator File
    public static function validatorfile($val)
    {

        if (stristr($val, '.') !== False) {
            $a = explode('.', $val);
            $mod = $a['0'];
            $class = end($a);
            unset($a['0']);
            $s = validator_dir . implode(DS, $a);
            $file = mod_dir . $mod . DS . $s . '.validator.php';
        } else {
            $class = $val;
            $file = mod_dir . $val . DS . validator_dir . $val . '.validator.php';
        }

        $tmp = Kayit::varmi('validatorfile') === FALSE ? [] : Kayit::getir('validatorfile');
        if (in_array($file, $tmp) === FALSE) {
            try {
                self::loadfile($file, 'Validator');
            } catch (LoadFileException $e) {
                $e->show();
                exit();
            }
            $tmp[] = $file;
            Kayit::yenile('validatorfile', $tmp);
        }


        $classadi = $class . '_validator';
        if (!class_exists($classadi)) {
            throw new ClassException($classadi, 'Validator', $file);
        }
        unset($tmp);
        unset($file);
        unset($class);
        unset($val);
        return $classadi;
    }
}
