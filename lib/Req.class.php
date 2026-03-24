<?php

/*
 * POST GET işlemleri İçin.
 * Req Sınıfı
 * Fonksiyonlar Static Olarak tanımlanmıştır.
 */

class Req
{
    // MQ MQ MQ - Fixed for PHP 8+ compatibility
    public static function deep(&$value)
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                $v = self::deep($v);
            }
        } else {
            $value = $value !== null ? stripslashes($value) : '';
        }
        return $value;
    }

    // Veri temizlemek İçin
    public static function Temizle($veri)
    {
        if ($veri === null) {
            return '';
        }
        $veri = trim(htmlspecialchars($veri, ENT_QUOTES, 'UTF-8'));
        //	echo $veri;
        //echo "<br>";
        $veri = str_replace('`', '&#96;', $veri);

        // hsc fix &amp;#039; => to => &#039;
        $veri = preg_replace("/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", $veri);
        // $veri = preg_replace("/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", $veri);


        return $veri;
    }

    // Url den gelen veriyi temizliyoruz.
    public static function Url_Clear($veri)
    {
        $veri = urldecode($veri ?? '');
        return self::Clear($veri);
    }

    // Veri temizlemek için
    public static function Clear($value = null)
    {
        if ($value === null) {
            return '';
        }
        if (is_array($value)) {
            return array_map(['Req', 'Clear'], $value);
        }
        return Req::Temizle($value);
    }

    public static function unhtmlentities($string)
    {
        $trans_tbl = get_html_translation_table(HTML_ENTITIES);
        $trans_tbl = array_flip($trans_tbl);
        return strtr($string, $trans_tbl);
    }

    // MQ Değerine Bakıyoruz.
    public static function Amed()
    {
        return true;
        // return get_magic_quotes_gpc() ? true : false;
    }

    // $_POST ile gelen değeri alıyoruz
    public static function Post($name)
    {
        return self::Amed() ? self::deep($_POST[$name]) : $_POST[$name];
    }

    // $_POST ile alınan veriyi temizliyoruz
    public static function Post_Safe($name)
    {
        return self::Clear(self::Post($name));
    }

    // $_POST ile gelen değer varmı bakıyoruz
    public static function is_Post($name)
    {
        return isset($_POST[$name]) and $_POST[$name] != '';
    }

    // $_POST ile gelen veri int mi
    public static function is_int_Post($name)
    {
        return is_int($_POST[$name]);
    }

    // $_POST ile gelen veri int mi
    public static function is_string_Post($name)
    {
        return is_string($_POST[$name]);
    }

    // $_FILES ile gelen değeri alıyoruz
    public static function Files($name)
    {
        return $_FILES[$name];
    }

    // $_FILES ile gelen değer varmı bakıyoruz
    public static function is_Files($name)
    {
        return isset($_FILES[$name]);
    }

    // $_FILES ile gelen değerin ismini alıyoruz
    public static function Files_Name($name)
    {
        return $_FILES[$name]['name'];
    }

    // $_FILES ile gelen değerin tipini alıyoruz
    public static function Files_Type($name)
    {
        return $_FILES[$name]['type'];
    }

    // $_FILES ile gelen değerin tmp name değerini alıyoruz
    public static function Files_Tmp($name)
    {
        return $_FILES[$name]['tmp_name'];
    }

    // $_FILES ile gelen değerin uzantısını alıyoruz.
    public static function Files_Ext($name)
    {
        $array = explode('.', $_FILES[$name]['name']);
        $size = count($array) - 1;
        return $array[$size];
    }

    // $_GET ile gelen değeri alıyoruz
    public static function Get($name)
    {
        return self::Amed() ? self::deep($_GET[$name]) : $_GET[$name];
    }

    // $_GET ile gelen değer varmı bakıyoruz
    public static function is_Get($name)
    {
        return isset($_GET[$name]) and $_GET[$name] != '';
    }

    // $_GET ile alınan veriyi temizliyoruz
    public static function Get_Safe($name)
    {
        return self::Url_Clear(self::Get($name));
    }

    // $_GET ile gelen veri int mi
    public static function is_int_Get($name)
    {
        return is_int($_GET[$name]);
    }

    // $_GET ile gelen veri int mi
    public static function is_string_Get($name)
    {
        return is_string($_GET[$name]);
    }

    // $_REQUEST ile gelen değeri alıyoruz
    public static function Request($name)
    {
        return self::Amed() ? self::deep($_REQUEST[$name]) : $_REQUEST[$name];
    }

    // $_REQUEST ile gelen değer varmı bakıyoruz
    public static function is_Request($name)
    {
        return isset($_REQUEST[$name]) and $_REQUEST[$name] != '';
    }

    // $_REQUEST ile alınan veriyi temizliyoruz
    public static function Request_Safe($name)
    {
        return self::Url_Clear(self::Request($name));
    }

    // $_REQUEST ile gelen veri int mi
    public static function is_int_Request($name)
    {
        return is_int($_REQUEST[$name]);
    }

    // $_REQUEST ile gelen veri int mi
    public static function is_string_Request($name)
    {
        return is_string($_REQUEST[$name]);
    }

    public static function isNumeric($numeric)
    {
        return preg_match("/^\\d+\$/", $numeric);
    }

    public static function isBiggerDouble($a, $b)
    {
        return ((float)$a) > ((float)$b);
    }
}
