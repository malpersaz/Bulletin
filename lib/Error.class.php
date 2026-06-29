<?php

/**
 * Amed Framework
 * Kullanıcı: a.k.mutlu
 * Tarih: 4/23/11 - 7:09 PM
 * Telif Hakkı:AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * Sınıfı: Error
 * Dosya: Error.class.php
 * Açıklama:
 */
class Error
{
    public static $lang;
    public static $tema;

    public static function error($sayfa, $a = NULL)
    {
        header("HTTP/1.1 404 Not Found");
        self::$lang = Load::lang('index');
        self::$tema = Load::lib('TwigTemplate');
        $hata = self::$lang['index']['hata'][$sayfa];
        self::$tema->setGlobal('title', self::$lang['index']['hata']['1'] . " : " . $hata['ad']);
        self::$tema->setGlobal('mesaj', sprintf($hata['mesaj'], $a));
        self::$tema->setGlobal('tpl', 'index/hata.tpl');
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
          echo Helper::black_text(" Methodu Bulunamadı!");*/
        //exit();
    }

    public static function mod($a)
    {
        return self::error('mod', $a);
        /*echo Helper::red_text(Req::Clear($a));
          echo "</br>";
          echo Helper::black_text(" Modülü Bulunamadı!");*/
        //exit();
    }

    public static function not($a)
    {
        return self::error('not', $a);
        /*echo Helper::red_text(Req::Clear($a));
          echo "</br>";
          echo Helper::black_text(" Method Çalıştırılamaz!");
          exit();*/
    }

    public static function lang($a)
    {
        echo Helper::red_text($a);
        echo "</br>";
        echo Helper::black_text(" Modülünün " . $a . " Dil Dosyası Bulunamadı");
    }
}
