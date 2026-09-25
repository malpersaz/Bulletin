<?php

/**
 * Amed Framework
 * User: AKM
 * Date: 4/23/11 - 7:09 PM
 * Copyright: AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * Class: Error
 * File: Error.class.php
 * Description: Error handling utility class
 */
class Error
{
    public static $lang;
    public static $theme;

    public static function error($page, $argument = NULL)
    {
        header("HTTP/1.1 404 Not Found");
        self::$lang = Load::lang('index');
        self::$theme = Load::lib('TwigTemplate');
        $error = self::$lang['index']['error'][$page] ?? [];
        $errorTitle = self::$lang['index']['error']['title'] ?? 'Error';
        self::$theme->setGlobal('title', $errorTitle . " : " . ($error['title'] ?? ($error['name'] ?? 'Error')));
        self::$theme->setGlobal('message', sprintf($error['message'] ?? '%s', $argument));
        self::$theme->setGlobal('tpl', 'index/error.twig');
        Run::import('index', '_index');
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
        /*echo Helper::red_text(Req::Clear($a));
          echo "</br>";
          echo Helper::black_text(" Method Not Found!");*/
        //exit();
    }

    public static function mod($a)
    {
        return self::error('mod', $a);
        /*echo Helper::red_text(Req::Clear($a));
          echo "</br>";
          echo Helper::black_text(" Module Not Found!");*/
        //exit();
    }

    public static function not($a)
    {
        return self::error('not', $a);
        /*echo Helper::red_text(Req::Clear($a));
          echo "</br>";
          echo Helper::black_text(" Method Cannot Be Executed!");
          exit();*/
    }

    public static function lang($a)
    {
        echo Helper::red_text($a);
        echo "</br>";
        echo Helper::black_text(" Language file not found for module " . $a);
    }
}
