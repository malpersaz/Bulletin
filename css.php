<?php
if (defined('DS') === FALSE) {
    define('DS',DIRECTORY_SEPARATOR);
}
// Versiyon
$version = isset($_GET['version']) ? intval($_GET['version']) : 1;
// Cache dizini
$cache_dir = 'css';
// Cache Root Dizini ve css Dosyası
$root_cache_dir = DIRNAME(__FILE__) . DS . 'cache' . DS . $cache_dir . DS;
function show($cachefile)
{
    $zlib = extension_loaded("zlib") ? TRUE : FALSE;
    if ($zlib) {
        ob_start("ob_gzhandler");
        header("Content-Encoding: gzip");
    }
    $zaman_asimi = 7 * 24 * 60 * 60;
    $yas = time() + $zaman_asimi;
    $etag = filemtime($cachefile) . " - ";
    header("Content-type: text/css; charset: UTF-8");
    header("Cache-Control: must-revalidate");
    header("Cache-Control: max-age=$zaman_asimi");
    header("ETag: " . dechex($etag . $yas));
    header("Expires: " . gmdate("D, d M Y H:i:s", $yas) . " GMT");
    if (is_data_url() === TRUE) {
        echo file_get_contents($cachefile . '-data');
    } else {
        echo file_get_contents($cachefile);
    }
}

function is_data_url()
{
    $desen = '/(?i)msie [1-8]/';
    $agent = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($agent, 'MSIE') && preg_match($desen, $agent)) {
        return FALSE;
    }
    return TRUE;
}

if (isset($_GET['css'])) {
    $css = $_GET['css'];
    $css_cache_file = md5($css) . '-v-' . $version . '.css';
    $cachefile = $root_cache_dir . $css_cache_file;
    if (file_exists($cachefile) === FALSE) {
        require_once(DIRNAME(__FILE__) . 'koberz.init.php');
        $cssoptimizer = Load::lib('CssOptimizer');
        $cssoptimizer->cachefile = $cachefile;
        $cssoptimizer->css_cache_file = $css_cache_file;
        $cssoptimizer->run($css);
    }
    // Gösterelim
    show($cachefile);
} elseif ($_GET['cachefile']) {
    $cachefile = $root_cache_dir . basename($_GET['cachefile']) . '.css';
    if (file_exists($cachefile)) {
        // Gösterelim
        show($cachefile);
    } else {
        exit('NON');
    }
}
