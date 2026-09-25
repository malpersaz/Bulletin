<?php

/**
 * Amed Framework
 * User: AKM
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
    public $message;
    public $type;
    public $filePath;
    public function __construct($message, $type, $file)
    {
        $this->message = $message;
        $this->type = $type;
        $this->filePath = $file;
    }

    public function show()
    {
        $msg = 'File not found';
        try {
            $indexLang = $this->loadIndexLanguage();
            if (isset($indexLang['index']['file_not_found'])) {
                $msg = $indexLang['index']['file_not_found'];
            }
        } catch (\Exception $e) {
            // Fallback to English if language file fails to load
        }

        echo sprintf($this->message, $this->type);
        echo "<br>";
        echo $msg . ":\n";
        echo "<b>" . $this->filePath . "</b>";
    }

    private function loadIndexLanguage()
    {
        if (Registry::has('index-lang') !== False) {
            return Registry::get('index-lang');
        }

        $currentLang = Registry::get('lang');
        $file = mod_dir . 'index' . DS . lang_dir . $currentLang . '.php';

        if (file_exists($file) === FALSE) {
            throw new RuntimeException('Missing index language file: ' . $file);
        }

        require($file);
        Registry::add('index-lang', $lang);

        return $lang;
    }
}

class ClassException extends Exception
{
    public $message;
    public $type;
    public $filePath;
    public function __construct($message, $type, $file)
    {
        $this->message = $message;
        $this->type = $type;
        $this->filePath = $file;
    }

    public function show()
    {
        echo "<h2>Class Exception</h2>";
        echo "<br>";
        echo sprintf("%s File <b>%s</b> Class Not Defined", $this->type, $this->message);
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

        $tmp = Registry::has('helperfile') === FALSE ? [] : Registry::get('helperfile');
        if (in_array($file, $tmp) === FALSE) {
            try {
                self::loadfile($file, 'Helper');
            } catch (LoadFileException $e) {
                $e->show();
                exit();
            }
            $tmp[] = $file;
            Registry::set('helperfile', $tmp);
        }


        $className = $class . '_helper';
        if (!class_exists($className)) {
            throw new ClassException($className, 'Helper', $file);
        }
        unset($tmp);
        unset($file);
        unset($class);
        unset($val);
        return $className;
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

        $tmp = Registry::has('modelfile') === FALSE ? [] : Registry::get('modelfile');
        if (in_array($file, $tmp) === FALSE) {
            try {
                self::loadfile($file, 'Model');
            } catch (LoadFileException $e) {
                $e->show();
                exit();
            }
            $tmp[] = $file;
            Registry::set('modelfile', $tmp);
        }


        $className = $class . '_model';
        if (!class_exists($className)) {
            throw new ClassException($className, 'Model', $file);
        }
        unset($tmp);
        unset($file);
        unset($class);
        unset($val);
        return $className;
    }

    // Loads Library File
    public static function libfile($val)
    {
        $tmp = Registry::has('libfile') === FALSE ? [] : Registry::get('libfile');
        if (in_array($val, $tmp) === FALSE) {
            $file = lib_dir . $val . '.class.php';
            try {
                self::loadfile($file, 'Library');
            } catch (LoadFileException $e) {
                $e->show();
                exit();
            }
            $tmp[] = $val;
            Registry::set('libfile', $tmp);
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
        if (Registry::has($val . '-lib') === False) {
            try {
                self::libfile($val);
            } catch (ClassException   $e) {
                $e->show();
                exit();
            }

            //self::libfile($val);
            $instance = new $val();
            Registry::add($val . '-lib', $instance);
            return $instance;
        }
        return Registry::get($val . '-lib');
    }

    // Model Loader
    public static function model($val)
    {
        if (Registry::has($val . '-model') === False) {

            try {
                $class = self::modelfile($val);
            } catch (ClassException $e) {
                $e->show();
                exit();
            }


            $instance = new $class();

            Registry::add($val . '-model', $instance);
            return $instance;
        }
        return Registry::get($val . '-model');
    }


    // Helper Loader
    public static function helper($val)
    {
        if (Registry::has($val . '-helper') === False) {

            try {
                $class = self::helperfile($val);
            } catch (ClassException $e) {
                $e->show();
                exit();
            }


            $instance = new $class();

            Registry::add($val . '-helper', $instance);
            return $instance;
        }
        return Registry::get($val . '-helper');
    }

    // Mod Loader
    public static function mod($val)
    {
        if (Registry::has($val . '-mod') === False) {
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
            $instance = new $class();
            Registry::add($val . '-mod', $instance);
            return $instance;
        }
        return Registry::get($val . '-mod');
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
        if (Registry::has($val . '-lang') === False) {
            $tmp = Registry::has('langfile') === FALSE ? [] : Registry::get('langfile');
            if (in_array($val, $tmp) === FALSE) {
                $file = mod_dir . $val . DS . lang_dir . Registry::get('lang') . '.php';
                
                // Fallback to default lang or English if requested language file is missing
                if (file_exists($file) === FALSE) {
                    $defaultLang = Registry::has('default_lang') ? Registry::get('default_lang') : 'en';
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
                Registry::set('langfile', $tmp);
                Registry::add($val . '-lang', $lang);
            }
            unset($tmp);
            unset($file);
        }
        return Registry::get($val . '-lang');
    }

    // Validator Loader
    public static function validator($val)
    {
        if (Registry::has($val . '-validator') === False) {

            $class = self::validatorfile($val);

            $instance = new $class();
            Registry::add($val . '-validator', $instance);
            return $instance;
        }
        return Registry::get($val . '-validator');
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

        $tmp = Registry::has('validatorfile') === FALSE ? [] : Registry::get('validatorfile');
        if (in_array($file, $tmp) === FALSE) {
            try {
                self::loadfile($file, 'Validator');
            } catch (LoadFileException $e) {
                $e->show();
                exit();
            }
            $tmp[] = $file;
            Registry::set('validatorfile', $tmp);
        }


        $className = $class . '_validator';
        if (!class_exists($className)) {
            throw new ClassException($className, 'Validator', $file);
        }
        unset($tmp);
        unset($file);
        unset($class);
        unset($val);
        return $className;
    }
}
