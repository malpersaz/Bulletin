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
            @header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
            @header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
            @header('Pragma: no-cache');
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
        if (!headers_sent()) {
            @header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
            @header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
            @header('Pragma: no-cache');
        }
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

    static function convertArrayToObject($array)
    {
        foreach ($array as &$v) {
            if (is_array($v)) {
                $v = self::convertArrayToObject($v);
            }
        }
        return (object)$array;
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
        $userId = posix_getuid();

        $req = is_file($file) ? 6 : 7; //Directory needs to be 7, file 6


        if ($userId == $owner) {
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
        // Remove apostrophes, quotation marks, and punctuation (:;,!? etc.)
        $s = preg_replace('/[\x{0027}\x{0022}\x{2019}\x{201B}\x{201C}\x{201D}\x{201E}\x{201F}:;,!?()\[\]{}]/u', '', (string) $s);
        $s = preg_replace('/[^\p{L}\p{N} ]/u', " ", $s);
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

    /**
     * Formats a relative time string according to localized dictionary definitions (Amed Framework standard).
     */
    public static function time_ago($datetime, $lang = null): string
    {
        if (empty($datetime)) {
            return '';
        }

        try {
            if ($datetime instanceof DateTime) {
                $published_at = $datetime;
            } elseif (is_numeric($datetime)) {
                $published_at = new DateTime('@' . $datetime);
                $published_at->setTimezone(new DateTimeZone(date_default_timezone_get()));
            } else {
                $published_at = new DateTime($datetime);
            }
            $now = new DateTime('now');
            $diff = $published_at->diff($now);
        } catch (Exception $e) {
            return '';
        }

        $timeLang = null;
        if (is_array($lang)) {
            $timeLang = isset($lang['time_ago']) && is_array($lang['time_ago']) ? $lang['time_ago'] : (isset($lang['index']['time_ago']) && is_array($lang['index']['time_ago']) ? $lang['index']['time_ago'] : $lang);
        }

        if (!is_array($timeLang) || empty($timeLang)) {
            if (isset($GLOBALS['lang']['index']['time_ago']) && is_array($GLOBALS['lang']['index']['time_ago'])) {
                $timeLang = $GLOBALS['lang']['index']['time_ago'];
            } elseif (isset($GLOBALS['lang']['time_ago']) && is_array($GLOBALS['lang']['time_ago'])) {
                $timeLang = $GLOBALS['lang']['time_ago'];
            }
        }

        if (!is_array($timeLang) || empty($timeLang)) {
            if (class_exists('Load') && method_exists('Load', 'lang')) {
                $loaded = Load::lang('index');
                $timeLang = isset($loaded['index']['time_ago']) && is_array($loaded['index']['time_ago']) ? $loaded['index']['time_ago'] : [];
            }
        }

        if ($published_at > $now) {
            return isset($timeLang['just_now']) ? $timeLang['just_now'] : 'Just now';
        }

        if ($diff->y > 0) {
            $key = $diff->y === 1 ? 'year' : 'years';
            $fallbackKey = $diff->y === 1 ? 'year_ago' : 'years_ago';
            $template = $timeLang[$key] ?? $timeLang[$fallbackKey] ?? ($diff->y === 1 ? '%d year ago' : '%d years ago');
            return sprintf($template, $diff->y);
        }
        if ($diff->m > 0) {
            $key = $diff->m === 1 ? 'month' : 'months';
            $fallbackKey = $diff->m === 1 ? 'month_ago' : 'months_ago';
            $template = $timeLang[$key] ?? $timeLang[$fallbackKey] ?? ($diff->m === 1 ? '%d month ago' : '%d months ago');
            return sprintf($template, $diff->m);
        }
        if ($diff->d > 0) {
            $key = $diff->d === 1 ? 'day' : 'days';
            $fallbackKey = $diff->d === 1 ? 'day_ago' : 'days_ago';
            $template = $timeLang[$key] ?? $timeLang[$fallbackKey] ?? ($diff->d === 1 ? '%d day ago' : '%d days ago');
            return sprintf($template, $diff->d);
        }
        if ($diff->h > 0) {
            $key = $diff->h === 1 ? 'hour' : 'hours';
            $fallbackKey = $diff->h === 1 ? 'hour_ago' : 'hours_ago';
            $template = $timeLang[$key] ?? $timeLang[$fallbackKey] ?? ($diff->h === 1 ? '%d hour ago' : '%d hours ago');
            return sprintf($template, $diff->h);
        }
        if ($diff->i > 0) {
            $key = $diff->i === 1 ? 'minute' : 'minutes';
            $fallbackKey = $diff->i === 1 ? 'minute_ago' : 'minutes_ago';
            $template = $timeLang[$key] ?? $timeLang[$fallbackKey] ?? ($diff->i === 1 ? '%d minute ago' : '%d minutes ago');
            return sprintf($template, $diff->i);
        }

        return isset($timeLang['just_now']) ? $timeLang['just_now'] : 'Just now';
    }

    public static function format_relative_time($datetime, $trans = null): string
    {
        return self::time_ago($datetime, $trans);
    }

    // Build a nested array based on given parameters
    public static function nested($datas, $parentKey, $idKey)
    {
        if (!is_array($datas) || empty($datas)) {
            return false;
        }
        $firstRow = current($datas);
        if (!is_array($firstRow)) {
            return false;
        }
        $keys = array_keys($firstRow);
        if (in_array($parentKey, $keys, true) === false || in_array($idKey, $keys, true) === false) {
            return false;
        }
        $array = [];
        foreach ($datas as $data) {
            if (!is_array($data) || !array_key_exists($parentKey, $data) || !array_key_exists($idKey, $data)) {
                continue;
            }
            foreach ($keys as $k) {
                $array[$data[$parentKey]][$data[$idKey]][$k] = $data[$k] ?? null;
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

    /**
     * Truncates text at word boundaries up to maxChars for clean SEO meta descriptions.
     */
    public static function truncateWords(string $text, int $maxChars = 160): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
        if (mb_strlen($clean) <= $maxChars) {
            return $clean;
        }
        $sub = mb_substr($clean, 0, $maxChars);
        $lastSpace = mb_strrpos($sub, ' ');
        if ($lastSpace !== false && $lastSpace > ($maxChars / 2)) {
            $sub = mb_substr($sub, 0, $lastSpace);
        }
        return rtrim($sub, " \t\n\r\0\x0B.,:;!?") . '...';
    }

    /**
     * Normalizes a URL slug or path token for pure UTF-8 case-insensitive comparisons
     * preserving Wikipedia-style UTF-8 characters across all languages.
     * Decodes URL encoding, normalizes hyphens/spaces to underscores, and applies
     * Unicode-safe lowercase folding (handling Turkish dotted İ -> i safely).
     */
    public static function utf8_slug_key(string $slug): string
    {
        $decoded = rawurldecode($slug);
        $normalized = str_replace(['-', ' '], '_', trim($decoded, "/ \t\n\r\0\x0B"));
        // Safely fold Turkish capital dotted İ (\xC4\xB0) to 'i' before mb_strtolower
        // to avoid mb_strtolower producing combining dot characters on certain PHP libicu setups.
        $foldedI = str_replace("\xC4\xB0", 'i', $normalized);
        return mb_strtolower($foldedI, 'UTF-8');
    }

    /**
     * Resolves a statutory page slug or alias to its canonical root route slug
     * ('legal', 'cookie-policy', 'privacy-policy', 'terms-of-service',
     *  'editorial-policy', 'accessibility', 'security-policy', 'compliance', 'contact').
     * Returns null if the slug is not a statutory page.
     */
    public static function resolve_statutory_slug(string $slug): ?string
    {
        $key = self::utf8_slug_key($slug);
        if ($key === '') {
            return null;
        }

        static $map = null;
        if ($map === null) {
            $map = [
                // Canonical root route keys & English short aliases
                'legal' => 'legal',
                'imprint' => 'legal',
                'masthead' => 'legal',
                'legal_info' => 'legal',
                'legal_information' => 'legal',

                'cookie_policy' => 'cookie-policy',
                'cookie-policy' => 'cookie-policy',
                'cookies' => 'cookie-policy',

                'privacy_policy' => 'privacy-policy',
                'privacy-policy' => 'privacy-policy',
                'privacy' => 'privacy-policy',

                'terms_of_service' => 'terms-of-service',
                'terms-of-service' => 'terms-of-service',
                'terms' => 'terms-of-service',
                'tos' => 'terms-of-service',

                'editorial_policy' => 'editorial-policy',
                'editorial-policy' => 'editorial-policy',
                'editorial' => 'editorial-policy',
                'editorial_guidelines' => 'editorial-policy',
                'editorial-guidelines' => 'editorial-policy',
                'corrections' => 'editorial-policy',
                'corrections_policy' => 'editorial-policy',
                'corrections-policy' => 'editorial-policy',

                'accessibility' => 'accessibility',
                'a11y' => 'accessibility',

                'security_policy' => 'security-policy',
                'security-policy' => 'security-policy',
                'security' => 'security-policy',

                'compliance' => 'compliance',
                'ethics' => 'compliance',
                'ethics_policy' => 'compliance',
                'ethics-policy' => 'compliance',

                'contact' => 'contact',
                'contact_us' => 'contact',

                // Turkish (tr) - Native UTF-8
                'yasal_bilgiler' => 'legal',
                'künye' => 'legal',
                'kunye' => 'legal',
                'kunya' => 'legal',
                'çerez_politikası' => 'cookie-policy',
                'gizlilik_politikası' => 'privacy-policy',
                'kullanım_koşulları' => 'terms-of-service',
                'yayın_ilkeleri' => 'editorial-policy',
                'yayın_ılkeleri' => 'editorial-policy',
                'erişilebilirlik' => 'accessibility',
                'güvenlik_politikası' => 'security-policy',
                'basın_etiği_ve_uyum' => 'compliance',
                'iletişim' => 'contact',

                // Kurdish (ku) - Native UTF-8
                'agahiyên_qanûnî' => 'legal',
                'agahdariya_zagonî' => 'legal',
                'agahdariya_zagônî' => 'legal',
                'polîtîkaya_çerezan' => 'cookie-policy',
                'polîtîkaya_kulîçeyan' => 'cookie-policy',
                'rêbaza_çerezan' => 'cookie-policy',
                'polîtîkaya_nepeniyê' => 'privacy-policy',
                'polîtîkaya_nepaniyê' => 'privacy-policy',
                'mercên_bikaranînê' => 'terms-of-service',
                'mercên_bikarhanînê' => 'terms-of-service',
                'prensîbên_weşanê' => 'editorial-policy',
                'yasayên_weşanê' => 'editorial-policy',
                'zasayên_weşanê' => 'editorial-policy',
                'gihîştbarî' => 'accessibility',
                'gihîştinbarî' => 'accessibility',
                'polîtîkaya_ewlehiyê' => 'security-policy',
                'etîka_çapemeniyê' => 'compliance',
                'etîka_çapemeniyê_û_lihevhatin' => 'compliance',
                'kûnye' => 'legal',
                'têkilî' => 'contact',

                // Kurdish dialect (zza) - Native UTF-8
                'melumatê_huqûqî' => 'legal',
                'melûmatê_hiqûqî' => 'legal',
                'siyasetê_çerezan' => 'cookie-policy',
                'politîkaya_çerezan' => 'cookie-policy',
                'polîtîkaya_nimiteyîye' => 'privacy-policy',
                'şertê_xebitnayîşî' => 'terms-of-service',
                'prensîbê_weşanî' => 'editorial-policy',
                'resayîşbarîye' => 'accessibility',
                'polîtîkaya_asayîşî' => 'security-policy',
                'liheveyîye' => 'compliance',

                // Central Kurdish (ckb) - Native script
                'زانیارییە_یاساییەکان' => 'legal',
                'سیاسەتی_کووکییەکان' => 'cookie-policy',
                'سیاسەتی_تایبەتمەندی' => 'privacy-policy',
                'مەرجەکانی_بەکارهێنان' => 'terms-of-service',
                'بنەماکانی_دەستەی_نووسەران' => 'editorial-policy',
                'دەستپێڕاگەیشتن' => 'accessibility',
                'سیاسەتی_ئاسایش' => 'security-policy',
                'ئیتیکی_ڕۆژنامەوانی' => 'compliance',

                // Arabic (ar) - Native script
                'المعلومات_القانونية' => 'legal',
                'سياسة_ملفات_تعريف_الارتباط' => 'cookie-policy',
                'سياسة_الخصوصية' => 'privacy-policy',
                'شروط_الاستخدام' => 'terms-of-service',
                'المعايير_التحريرية' => 'editorial-policy',
                'إمكانية_الوصول' => 'accessibility',
                'امكانية_الوصول' => 'accessibility',
                'سياسة_الأمان' => 'security-policy',
                'سياسة_الامان' => 'security-policy',
                'ميثاق_الشرف_الصحفي' => 'compliance',

                // Russian (ru) - Native script
                'правовая_информация' => 'legal',
                'политика_cookie' => 'cookie-policy',
                'политика_конфиденциальности' => 'privacy-policy',
                'условия_использования' => 'terms-of-service',
                'редакционная_политика' => 'editorial-policy',
                'доступность' => 'accessibility',
                'политика_безопасности' => 'security-policy',
                'журналистская_этика' => 'compliance',

                // German (de) - Native UTF-8
                'rechtliche_hinweise' => 'legal',
                'cookie_richtlinie' => 'cookie-policy',
                'datenschutzerklärung' => 'privacy-policy',
                'nutzungsbedingungen' => 'terms-of-service',
                'redaktionelle_richtlinien' => 'editorial-policy',
                'barrierefreiheit' => 'accessibility',
                'sicherheitsrichtlinie' => 'security-policy',
                'presseethik_und_compliance' => 'compliance',

                // French (fr) - Native UTF-8
                'informations_légales' => 'legal',
                'politique_de_cookies' => 'cookie-policy',
                'politique_de_confidentialité' => 'privacy-policy',
                'conditions_d_utilisation' => 'terms-of-service',
                'principes_éditoriaux' => 'editorial-policy',
                'accessibilité' => 'accessibility',
                'politique_de_sécurité' => 'security-policy',
                'conformité' => 'compliance',

                // Swedish (sv) - Native UTF-8
                'juridisk_information' => 'legal',
                'cookiepolicy' => 'cookie-policy',
                'integritetspolicy' => 'privacy-policy',
                'användarvillkor' => 'terms-of-service',
                'publicistiska_riktlinjer' => 'editorial-policy',
                'tillgänglighet' => 'accessibility',
                'säkerhetspolicy' => 'security-policy',
                'regelefterlevnad' => 'compliance',

                // Historical legacy ASCII slugs (preserved for 301 backward compatibility)
                'cerez_politikasi' => 'cookie-policy',
                'gizlilik_politikasi' => 'privacy-policy',
                'kullanim_kosullari' => 'terms-of-service',
                'yayin_ilkeleri' => 'editorial-policy',
                'erisilebilirlik' => 'accessibility',
                'guvenlik_politikasi' => 'security-policy',
                'basin_etigi_ve_uyum' => 'compliance',
                'iletisim' => 'contact',
                'agahiyen_qanuni' => 'legal',
                'agahdariya_zagoni' => 'legal',
                'melumate_huquqi' => 'legal',
                'politikaya_cerezan' => 'cookie-policy',
                'politikaya_kuliceyan' => 'cookie-policy',
                'siyasete_cerezan' => 'cookie-policy',
                'politikaya_nepaniye' => 'privacy-policy',
                'politika_nepeniye' => 'privacy-policy',
                'mercen_bikaranine' => 'terms-of-service',
                'mercen_bikarhanine' => 'terms-of-service',
                'prensiben_wesane' => 'editorial-policy',
                'zasayen_wesane' => 'editorial-policy',
                'yasayen_wesane' => 'editorial-policy',
                'gihistbari' => 'accessibility',
                'gihistinbari' => 'accessibility',
                'politikaya_ewlehiye' => 'security-policy',
                'etika_capemeniye' => 'compliance',
                'etiqa_capemeniye_u_lihevhatin' => 'compliance',
                'politikaya_nimiteyiye' => 'privacy-policy',
                'serte_xebitnayisi' => 'terms-of-service',
                'prensibe_wesani' => 'editorial-policy',
                'resayisbariye' => 'accessibility',
                'politikaya_asayisi' => 'security-policy',
                'liheveyiye' => 'compliance',
                'datenschutzerklaerung' => 'privacy-policy',
                'politique_de_confidentialite' => 'privacy-policy',
                'principes_editoriaux' => 'editorial-policy',
                'accessibilite' => 'accessibility',
                'politique_de_securite' => 'security-policy',
                'conformite' => 'compliance',
                'anvandarvillkor' => 'terms-of-service',
                'tillganglighet' => 'accessibility',
                'sakerhetspolicy' => 'security-policy',
            ];
        }

        return $map[$key] ?? null;
    }

    /**
     * Compute normalized ASCII matching key for inbound legacy URL reconciliation.
     * Strictly used for inbound 301 redirection lookups; never for outbound slug generation.
     */
    public static function ascii_slug_key(string $slug): string
    {
        $raw = rawurldecode($slug);
        $raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $raw = preg_replace('/[\'\":,.\/!?\(\)\[\]\{\}«»“”]/u', '', $raw);
        $raw = self::sefurl($raw);
        $raw = mb_strtolower((string)$raw, 'UTF-8');
        $raw = preg_replace('/[-_\s]+/u', '_', $raw);
        return trim((string)$raw, '_');
    }

    /**
     * RFC 3986 percent-encode XML sitemap and RSS URL paths without double-encoding.
     */
    public static function sitemap_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme']) || !isset($parts['host'])) {
            return $url;
        }

        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '/';

        $segments = explode('/', $path);
        $encodedSegments = [];
        foreach ($segments as $segment) {
            if ($segment === '') {
                $encodedSegments[] = '';
                continue;
            }
            $decoded = rawurldecode($segment);
            $encodedSegments[] = rawurlencode($decoded);
        }
        $encodedPath = implode('/', $encodedSegments);

        $result = $scheme . '://' . $host . $port . $encodedPath;
        if (isset($parts['query']) && $parts['query'] !== '') {
            $result .= '?' . $parts['query'];
        }
        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $result .= '#' . $parts['fragment'];
        }

        return $result;
    }
}
