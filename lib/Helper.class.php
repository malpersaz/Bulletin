<?php
class Helper
{

    // Find extension
    static function extension($filename)
    {
        $array = explode('.', $filename);
        $size = count($array) - 1;
        return $array[$size];
    }

    // Red text
    static function red_text($a)
    {
        return '<font color="red"><b>' . $a . '</b></font>';
    }

    // Green text
    static function green_text($a)
    {
        return '<font color="green"><b>' . $a . '</b></font>';
    }

    static function black_text($a)
    {
        return '<font color="black"><b>' . $a . '</b></font>';
    }

    // Site URL
    static function site_url()
    {
        return site_url;
    }

    // Redirect function: redirect('index.php', 3);
    static function redirect($url, $seconds = 3)
    {
        if (!headers_sent()) {
            @header("refresh: $seconds; url=$url");
        } else {
            echo '<script type="text/javascript">';
            echo 'window.setTimeout("location.href=\'' . $url . '\'", ' . $seconds . '000);';
            echo '</script>';
            echo '<noscript>';
            echo '<meta http-equiv="refresh" content="' . $seconds . ';url=' . $url . '" />';
            echo '</noscript>';
        }
        exit();
    }

    // JavaScript / Meta redirection without calling exit()
    static function jsRedirect($url, $seconds = 3)
    {
        echo '<script type="text/javascript">';
        echo 'window.setTimeout("location.href=\'' . $url . '\'", ' . $seconds . '000);';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="' . $seconds . ';url=' . $url . '" />';
        echo '</noscript>';
    }

    // CSRF protection - Referer validation.
    static function ref_control($script)
    {
        $pattern = '#^' . preg_quote(str_replace('www.', '', site_url) . $script, '#') . '#i';
        $ref = str_replace('www.', '', (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''));
        return !preg_match($pattern, $ref) ? die(self::red('CSRF ATTACK')) : '';
    }

    static function convertArrayToObject($arr)
    {
        foreach ($arr as &$v) {
            if (is_array($v)) {
                $v = self::convertArrayToObject($v);
            }
        }
        return (object)$arr;
    }

    /*static function convertObjectToArray($object) {
         $array = array ();
         $class = new ReflectionObject ( $object );
         $properties = $class->getProperties ();

         foreach ( $properties as $property ) {
             if ($object->{$property->name} instanceof StdClass) {
                 $array [$property->name] = self::convertObjectToArray ( $object->{$property->name} );
             } else {
                 $array [$property->name] = $object->{$property->name};
             }
         }

         return $array;
     }*/

    static function convertObjectToArray($object)
    {
        if (is_array($object) || is_object($object)) {
            $array = [];
            foreach ($object as $key => $value) {
                $array[$key] = self::convertObjectToArray($value);
            }
            return $array;
        }
        return $object;
    }

    static function isWritable($file)
    {
        $perms = self::getFilePerms($file);
        $owner = fileowner($file);
        $uid = posix_getuid();

        $req = is_file($file) ? 6 : 7; //Directory needs to be 7, file 6


        if ($uid == $owner) {
            if (substr($perms, 0, 1) >= $req) {
                return TRUE;
            }
        } else {
            if (substr($perms, -1) >= $req) {
                return TRUE;
            }
        }
        return FALSE;
    }

    static function getFilePerms($file)
    {
        if (!file_exists($file)) {
            die('File or directory ' . $file . ' doesn\'t exist. Please create it.');
        }

        return substr(sprintf('%o', fileperms($file)), -3);
    }

    static function show_404($a, $b)
    {
        echo self::red_text($a) . self::black_text($b);
    }

    static function randomkey($amount)
    {

        $keyset = "abcdefghijklmABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

        $randkey = "";

        for ($i = 0; $i < $amount; $i++)

            $randkey .= substr($keyset, rand(0, strlen($keyset) - 1), 1);

        return $randkey;
    }

    static function sefurl($s)
    {
        $turkishChars = ['ş', 'Ş', 'ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'Ç', 'ç'];
        $asciiChars   = ['s', 'S', 'i', 'I', 'g', 'G', 'u', 'U', 'o', 'O', 'C', 'c'];
        $s = str_replace($turkishChars, $asciiChars, $s);
        $s = preg_replace('[^0-9A-Za-z]', " ", $s);
        $s = trim($s);
        $s = str_replace(' ', "-", $s);
        for ($i = 0; $i <= 5; $i++) {
            $s = str_replace("--", "-", $s);
        }
        return strtolower($s);
    }

    static function legacy_seo($s)
    {
        $s = preg_replace('/[^\p{L}\p{N}"\' ]/u', " ", $s);
        $s = trim($s);
        $s = preg_replace('/\s+/', '_', $s);
        for ($i = 0; $i <= 5; $i++) {
            $s = str_replace("__", "_", $s);
        }
        return $s;
    }
    static function sef($s)
    {
        $s = preg_replace('/[^\p{L}\p{N}]/u', " ", $s);
        $s = trim($s);
        $s = str_replace(' ', "_", $s);
        for ($i = 0; $i <= 5; $i++) {
            $s = str_replace("__", "_", $s);
        }
        return $s;
    }
    static function keywords($s)
    {
        $s = str_replace(' ', ',', $s);
        return str_replace(',,', ',', $s);
    }
    static function getFilesize($file, $digits = 2)
    {
        if (is_file($file)) {
            $fileSize = filesize($file);
            $sizes = ["TB", "GB", "MB", "KB", "B"];
            $total = count($sizes);
            while ($total-- && $fileSize > 1024) {
                $fileSize /= 1024;
            }
            return round($fileSize, $digits) . " " . $sizes[$total];
        }
        return false;
    }

    static public function days_in_month($year, $month)
    {
        return $month == 2 ? ($year % 4 !== 0 ? 28 : ($year % 100 !== 0 ? 29 : ($year % 400 !== 0 ? 28 : 29))) : (($month - 1) % 7 % 2 !== 0 ? 30 : 31);
    }

    static function format_date($timestamp)
    {
        $days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
        $months = [NULL, "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        return date("d", $timestamp) . " " . $months[date("n", $timestamp)] . " " . date("Y", $timestamp) . " " . $days[date("w", $timestamp)] . " " . date("H:i", $timestamp);
    }

    // Build a nested array based on given parameters
    public static function nested($datas, $parentKey, $idKey)
    {
        if (!is_array($datas)) {
            return FALSE;
        }
        $keys = array_keys(current($datas));
        if (in_array($parentKey, $keys) === FALSE || in_array($idKey, $keys) === FALSE) {
            return FALSE;
        }
        $array = [];
        foreach ($datas as $data) {
            foreach ($keys as $k) {
                $array[$data[$parentKey]][$data[$idKey]][$k] = $data[$k];
            }
        }
        return $array;
    }

    /***
     *  Encode MailAddresses against Spam Bots
     ***/
    public static function jsEncode($address, $text)
    {
        preg_match('!^(.*)(\?.*)$!', $address, $match);
        if (!empty($match[2])) {
            print("mailto: hex encoding does not work with extra attributes. Try javascript.");
            return;
        }
        $address_encode = '';
        for ($x = 0; $x < strlen($address); $x++) {
            if (preg_match('!\w!', $address[$x])) {
                $address_encode .= '%' . bin2hex($address[$x]);
            } else {
                $address_encode .= $address[$x];
            }
        }
        $text_encode = '';
        for ($x = 0; $x < strlen($text); $x++) {
            $text_encode .= '&#x' . bin2hex($text[$x]) . ';';
        }

        $mailto = "&#109;&#97;&#105;&#108;&#116;&#111;&#58;";
        return '<a href="' . $mailto . $address_encode . '">' . $text_encode . '</a>';
    }

    public static function clear_html($html)
    {

        $element = 'a,abbr,acronym,b,blockquote,br,caption,cite,code,dd,del,dfn,div,dl,dt,em,font,h1,h2,h3,h4,h5,h6,i,img,ins,kbd,li,ol,p,pre,s,span,strike,strong,sub,sup,table,tbody,td,tfoot,th,thead,tr,tt,u,ul,var,object,param,embed,hr';
        $attr = 'a.name,a.href,a.rev,a.title,a.target,a.rel,abbr.title,acronym.title,blockquote.cite,div.align,div.style,div.class,div.id,font.size,font.color,h1.style,h2.style,h3.style,h4.style,h5.style,h6.style,img.src,img.alt,img.title,img.class,img.align,img.style,img.height,img.width,ol.style,p.class,p.style,span.style,span.class,span.id,table.align,table.class,table.id,table.border,table.cellpadding,table.cellspacing,table.style,table.width,td.abbr,td.align,td.class,td.id,td.colspan,td.rowspan,td.style,td.valign,tr.align,tr.class,tr.id,tr.style,tr.valign,th.abbr,th.align,th.class,th.id,th.colspan,th.rowspan,th.style,th.valign,ul.style,object.style,object.data,object.width,object.height,param.name,param.value,embed.src,embed.type,embed.wmode,embed.width,embed.height';

        $purifier = new HTMLPurifier();
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null); // disables cache
        $config->set('HTML.AllowedElements', $element); // sets allowed html elements that can be used.
        $config->set('HTML.AllowedAttributes', $attr); // sets allowed html attributes that can be used.
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.TidyLevel', 'heavy');
        // Allow the iframe tag
        $config->set('HTML.SafeIframe', true);

        // Set allowed iframe sources using regular expression (VERY IMPORTANT!)
        $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube\.com/embed/|player\.vimeo\.com/video/)%');
        $config->set('AutoFormat.Linkify', true);
        $config->set('AutoFormat.RemoveEmpty', false);

        //Flash
        $config->set('HTML.SafeObject', true);
        $config->set('HTML.SafeEmbed', true);
        $config->set('Output.FlashCompat', true);

        $purifier = new HTMLPurifier($config);
        return $purifier->purify($html);
    }
}
