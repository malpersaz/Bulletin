<?php

/**
 * Amed Framework
 * User: a.k.mutlu
 * Date: 4/23/11 - 7:09 PM
 * Copyright: AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * Class: AmedError
 * File: AmedError.class.php
 * Description: Localized error output helpers
 */
class AmedError
{
    public static $lang;
    public static $template;

    public static function error($page, $argument = NULL)
    {
        header("HTTP/1.1 404 Not Found");
        self::$lang = Load::lang('index');
        self::$template = Load::lib('TwigTemplate');
        
        if (isset($_SESSION)) {
            self::$template->setGlobal('session', $_SESSION);
        }
        if (isset($_COOKIE)) {
            self::$template->setGlobal('cookie', $_COOKIE);
        }
        $currentFullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
            "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
        self::$template->setGlobal('link', $currentFullUrl);

        $errorData = self::$lang['index']['error'][$page];
        self::$template->setGlobal('title', self::$lang['index']['error']['1'] . " : " . $errorData['ad']);
        self::$template->setGlobal('mesaj', sprintf($errorData['mesaj'], $argument));
        self::$template->setGlobal('tpl', 'index/error');
        self::$template->tpl('index/error');
        Run::import('index', '_index');
        
        self::$template->output();
        exit();
    }

    public static function dump()
    {
        list($callee) = debug_backtrace();
        $arguments = func_get_args();
        $total_arguments = count($arguments);

        echo '<fieldset style="background: #fefefe !important; border:2px red solid; padding:5px">';
        echo '<legend style="background:lightgrey; padding:5px;">' . $callee['file'] . ' @ line: ' . $callee['line'] . '</legend><pre>';
        $i = 0;
        foreach ($arguments as $argument) {
            echo '<br/><strong>Debug #' . (++$i) . ' of ' . $total_arguments . '</strong>: ';
            var_dump($argument);
        }

        echo "</pre>";
        echo "</fieldset>";
    }

    public static function method($a)
    {
        return self::error('method', $a);
    }

    public static function mod($a)
    {
        return self::error('mod', $a);
    }

    public static function not($a)
    {
        return self::error('not', $a);
    }

    public static function lang($path)
    {
        $indexLang = self::loadIndexLanguage();

        if (!isset($indexLang['index']['language'])) {
            throw new RuntimeException('Missing index language key: index.language');
        }

        if (!isset($indexLang['index']['file_not_found'])) {
            throw new RuntimeException('Missing index language key: index.file_not_found');
        }

        echo Helper::red_text($path);
        echo "</br>";
        echo Helper::black_text(sprintf("%s %s: %s", $indexLang['index']['language'], $indexLang['index']['file_not_found'], $path));
    }

    private static function loadIndexLanguage()
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
