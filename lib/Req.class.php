<?php

/**
 * Amed Framework Request Handler
 * Static helpers for $_POST, $_GET, $_REQUEST, and $_FILES processing
 */
class Req
{
    // Deep stripslashes for compatibility
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

    /**
     * Clear and sanitize string value
     *
     * @param string|null $data
     * @return string
     */
    public static function Clear_Value($data)
    {
        if ($data === null) {
            return '';
        }
        $data = trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
        $data = str_replace('`', '&#96;', $data);

        // hsc fix &amp;#039; => to => &#039;
        $data = preg_replace("/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", $data);

        return $data;
    }

    /**
     * Backward-compatibility alias for Clear_Value
     *
     * @param string|null $data
     * @return string
     */
    public static function Temizle($data)
    {
        return self::Clear_Value($data);
    }

    /**
     * Clean URL-encoded data
     *
     * @param string|null $data
     * @return string|array
     */
    public static function Url_Clear($data)
    {
        $data = urldecode($data ?? '');
        return self::Clear($data);
    }

    /**
     * Sanitize scalar or array values
     *
     * @param mixed $value
     * @return mixed
     */
    public static function Clear($value = null)
    {
        if ($value === null) {
            return '';
        }
        if (is_array($value)) {
            return array_map(['Req', 'Clear'], $value);
        }
        return self::Clear_Value($value);
    }

    public static function unhtmlentities($string)
    {
        $trans_tbl = get_html_translation_table(HTML_ENTITIES);
        $trans_tbl = array_flip($trans_tbl);
        return strtr($string, $trans_tbl);
    }

    public static function Amed()
    {
        return true;
    }

    // Retrieve $_POST parameter
    public static function Post($name)
    {
        return self::Amed() ? self::deep($_POST[$name]) : $_POST[$name];
    }

    // Retrieve sanitized $_POST parameter
    public static function Post_Safe($name)
    {
        return self::Clear(self::Post($name));
    }

    // Check if $_POST parameter exists and is not empty
    public static function is_Post($name)
    {
        return isset($_POST[$name]) and $_POST[$name] != '';
    }

    // Check if $_POST parameter is integer
    public static function is_int_Post($name)
    {
        return is_int($_POST[$name]);
    }

    // Check if $_POST parameter is string
    public static function is_string_Post($name)
    {
        return is_string($_POST[$name]);
    }

    // Retrieve $_FILES entry
    public static function Files($name)
    {
        return $_FILES[$name];
    }

    // Check if $_FILES entry exists
    public static function is_Files($name)
    {
        return isset($_FILES[$name]);
    }

    // Retrieve uploaded file name
    public static function Files_Name($name)
    {
        return $_FILES[$name]['name'];
    }

    // Retrieve uploaded file MIME type
    public static function Files_Type($name)
    {
        return $_FILES[$name]['type'];
    }

    // Retrieve uploaded file temp path
    public static function Files_Tmp($name)
    {
        return $_FILES[$name]['tmp_name'];
    }

    // Retrieve uploaded file extension
    public static function Files_Ext($name)
    {
        $array = explode('.', $_FILES[$name]['name']);
        $size = count($array) - 1;
        return $array[$size];
    }

    // Retrieve $_GET parameter
    public static function Get($name)
    {
        return self::Amed() ? self::deep($_GET[$name]) : $_GET[$name];
    }

    // Check if $_GET parameter exists and is not empty
    public static function is_Get($name)
    {
        return isset($_GET[$name]) and $_GET[$name] != '';
    }

    // Retrieve sanitized $_GET parameter
    public static function Get_Safe($name)
    {
        return self::Url_Clear(self::Get($name));
    }

    // Check if $_GET parameter is integer
    public static function is_int_Get($name)
    {
        return is_int($_GET[$name]);
    }

    // Check if $_GET parameter is string
    public static function is_string_Get($name)
    {
        return is_string($_GET[$name]);
    }

    // Retrieve $_REQUEST parameter
    public static function Request($name)
    {
        return self::Amed() ? self::deep($_REQUEST[$name]) : $_REQUEST[$name];
    }

    // Check if $_REQUEST parameter exists and is not empty
    public static function is_Request($name)
    {
        return isset($_REQUEST[$name]) and $_REQUEST[$name] != '';
    }

    // Retrieve sanitized $_REQUEST parameter
    public static function Request_Safe($name)
    {
        return self::Url_Clear(self::Request($name));
    }

    // Check if $_REQUEST parameter is integer
    public static function is_int_Request($name)
    {
        return is_int($_REQUEST[$name]);
    }

    // Check if $_REQUEST parameter is string
    public static function is_string_Request($name)
    {
        return is_string($_REQUEST[$name]);
    }

    public static function isNumeric($numeric)
    {
        return preg_match("/^\d+$/", $numeric);
    }

    public static function isBiggerDouble($a, $b)
    {
        return ((float)$a) > ((float)$b);
    }
}
