<?php

class CssOptimizer
{
    public $css_files;
    public $css_source;
    public $cachefile;
    public $css_cache_file;
    public $data_uri_css_source;
    public $data_uri_css_cache_file;
    public $site;

    public function run($css)
    {
        if (strpos($css, '|')) {
            $this->css_files = explode('|', $css);
        } else {
            $this->css_files[] = $css;
        }
        //$this->site = Kayit::getir('site_config');

        $this->make_cache();
        $this->make_cache_file();
    }

    private function make_cache()
    {
        if (is_array($this->css_files)) {
            foreach ($this->css_files as $css) {
                $file = root_dir . $css;
                if (file_exists($file) and $this->getExt($css) === 'css') {
                    // Bulunduğü Dizini bulalım $e['0']
                    $e = explode(basename($css), $css);
                    // Dosyayı Alalım
                    $source = file_get_contents($file);
                    // Tek dosya olduğunda hata vermemesi için url'yi fixleyip
                    // CSSmin ile sıkıştırıp temizleyelim
                    $this->css_source .= cssmin::minify($this->fix_url($source, $e['0']));
                    // Yukardaki ile aynı şey fakat resimleri datauri şekline getirelim
                    $this->data_uri_css_source .= cssmin::minify($this->data_uri_fix_url($source, $e['0']));
                }
            }
        }
    }


    private function make_cache_file()
    {
        file_put_contents($this->cachefile, $this->css_source);
        file_put_contents($this->cachefile . '-data', $this->data_uri_css_source);
        //$this->make_gzip_file();
    }


    public function getSource()
    {
        return file_get_contents($this->cachefile);
    }

    public function getGzipSource()
    {
        return file_get_contents($this->cachefile . '.gz');
    }

    public function getCacheFile()
    {
        return $this->css_cache_file;
    }

    public function getGzipCacheFile()
    {
        return $this->css_cache_file . '.gz';
    }

    // Uzantı Bulmak için
    private function getExt($file)
    {
        $array = explode('.', $file);
        $size = count($array) - 1;
        return $array[$size];
    }

    // 	75 Yola Sıfır
    private function data_uri_fix_url($data, $dizin)
    {
        $k1 = ['url("', 'url(\''];
        $k2 = ['")', '\')'];
        $data = str_replace($k1, 'url(', $data);
        $data = str_replace($k2, ')', $data);
        $desen = '#url\((.*?)\)#';
        $replace = 'url("' . $dizin . '$1")';
        $desen1 = '#\w+\/\.\.\/#';
        $replace1 = '';
        $data = preg_replace($desen, $replace, $data);
        $data = preg_replace($desen1, $replace1, $data);
        preg_match_all('#url\("(.*)"\)#', $data, $cikti);
        $s = count($cikti[1]) - 1; // Sayıp bir eksiltelim
        for ($i = 0; $i <= $s; $i++) {
            if (isset($cikti['1'][$i])) {
                $file = root_dir . $cikti['1'][$i];
                if (file_exists($file)) {
                    $kaynak = base64_encode(file_get_contents($file));
                    $mime = mime_content_type($file);
                    $data = preg_replace('#url\("(' . $cikti['1'][$i] . ')"\)#', 'url("data:' . $mime . ';base64,' . $kaynak . '")', $data);
                } elseif (strpos($cikti['1'][$i], 'data:image') === FALSE) {
                    echo $file . " Not Found";
                }
            }
        }
        return $data;
    }


    // 	75 Yola Sıfır
    private function fix_url($data, $dizin)
    {
        $k1 = ['url("', 'url(\''];
        $k2 = ['")', '\')'];
        $data = str_replace($k1, 'url(', $data);
        $data = str_replace($k2, ')', $data);
        $desen = '#url\((.*?)\)#';
        $replace = 'url("' . $this->site['taddress'] . $dizin . '$1")';
        $desen1 = '#\w+\/\.\.\/#';
        $replace1 = '';
        $data = preg_replace($desen, $replace, $data);
        return preg_replace($desen1, $replace1, $data);
    }
}


function cssmin_array_clean(array $array)
{
    $r = [];
    if (cssmin_array_is_assoc($array)) {
        foreach ($array as $key => $value) {
            $r[$key] = trim($value);
        }
    } else {
        foreach ($array as $value) {
            if (trim($value) !== "") {
                $r[] = trim($value);
            }
        }
    }
    return $r;
}

/**
 * Return if a value is a associative array.
 *
 * @param    array        $array
 * @return    bool
 */
function cssmin_array_is_assoc($array)
{
    if (!is_array($array)) {
        return false;
    }
    krsort($array, SORT_STRING);
    return !is_numeric(key($array));
}

/**
 * Encodes a url() expression.
 *
 * @param    array    $match
 * @return    string
 */
function cssmin_encode_url($match)
{
    return "url(" . base64_encode(trim($match[1])) . ")";
}

/**
 * Decodes a url() expression.
 *
 * @param    array    $match
 * @return    string
 */
function cssmin_decode_url($match)
{
    return "url(" . base64_decode($match[1]) . ")";
}

class cssmin
{
    /**
     * Minifies stylesheet definitions
     *
     * <code>
     * $css_minified = cssmin::minify(file_get_contents("path/to/target/file.css"));
     * </code>
     *
     * @param    string            $css        Stylesheet definitions as string
     * @param    array|string    $options    Array or comma speperated list of options:
     *
     * - remove-last-semicolon: Removes the last semicolon in
     * the style definition of an element (activated by default).
     *
     * - preserve-urls: Preserves every url defined in an url()-
     * expression. This option is only required if you have
     * defined really uncommon urls with multiple spaces or
     * combination of colon, semi-colon, braces with leading or
     * following spaces.
     * @return    string            Minified stylesheet definitions
     */
    public static function minify($css, $options = "remove-last-semicolon")
    {
        $options = ($options == "") ? [] : (is_array($options) ? $options : explode(",", $options));
        if (in_array("preserve-urls", $options)) {
            // Encode url() to base64
            $css = preg_replace_callback("/url\s*\((.*)\)/siU", "cssmin_encode_url", $css);
        }
        // Remove comments
        $css = preg_replace("/\/\*[\d\D]*?\*\/|\t+/", " ", $css);
        // Replace CR, LF and TAB to spaces
        $css = str_replace(["\n", "\r", "\t"], " ", $css);
        // Replace multiple to single space
        $css = preg_replace("/\s\s+/", " ", $css);
        // Remove unneeded spaces
        $css = preg_replace("/\s*({|}|\[|\]|=|~|\+|>|\||;|:|,)\s*/", "$1", $css);
        if (in_array("remove-last-semicolon", $options)) {
            // Removes the last semicolon of every style definition
            $css = str_replace(";}", "}", $css);
        }
        $css = trim($css);
        if (in_array("preserve-urls", $options)) {
            // Decode url()
            $css = preg_replace_callback("/url\s*\((.*)\)/siU", "cssmin_encode_url", $css);
        }
        return $css;
    }

    /**
     * Return a array structure of a stylesheet definitions.
     *
     * <code>
     * $css_structure = cssmin::toArray(file_get_contents("path/to/target/file.css"));
     * </code>
     *
     * @param    string        $css            Stylesheet definitions as string
     * @param    string        $options        Options for {@link cssmin::minify()}
     * @return    array                        Structure of the stylesheet definitions as array
     */
    public static function toArray($css, $options = "")
    {
        $r = [];
        $css = cssmin::minify($css, $options);
        preg_match_all("/(.+){(.+:.+);}/U", $css, $items);
        if (count($items[0]) > 0) {
            for ($i = 0; $i < $c = count($items[0]); $i++) {
                $keys = explode(",", $items[1][$i]);
                $styles_tmp = explode(";", $items[2][$i]);
                $styles = [];
                foreach ($styles_tmp as $style) {
                    $style_tmp = explode(":", $style);
                    $styles[$style_tmp[0]] = $style_tmp[1];
                }
                $r[] = ["keys" => cssmin_array_clean($keys), "styles" => cssmin_array_clean($styles)];
            }
        }
        return $r;
    }

    /**
     * Return a array structure created by {@link cssmin::toArray()} to a string.
     *
     * <code>
     * $css_string = cssmin::toString($css_structure);
     * </code>
     *
     * @param    array        $css
     * @return    array
     */
    public static function toString(array $array)
    {
        $r = "";
        foreach ($array as $item) {
            $r .= implode(",", $item["keys"]) . "{";
            foreach ($item["styles"] as $key => $value) {
                $r .= $key . ":" . $value . ";";
            }
            $r .= "}";
        }
        return $r;
    }
}
