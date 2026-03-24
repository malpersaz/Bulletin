<?php
if (defined('DS') === FALSE) {
    define('DS', DIRECTORY_SEPARATOR);
}
// Versiyon
$version = isset($_GET['version']) ? intval($_GET['version']) : 1;
// Cache dizini
$cache_dir = 'js';
// Cache Root Dizini ve Js Dosyası
$root_cache_dir = DIRNAME(__FILE__) . DS . 'cache' . DS . $cache_dir . DS;
// js yi gösterelim
// Eğer Zlib varsa gzib'i gösterelim.yoksa normal js dosyasını gösterelim
function show($cachefile)
{
    $zlib = extension_loaded("zlib") ? TRUE : FALSE;
    if ($zlib) {
        ob_start("ob_gzhandler");
        header("Content-Encoding: gzip");
    }

    $zaman_asimi = 7 * 24 * 60 * 60;
    $yas = time() + $zaman_asimi;
    $etag = filemtime($cachefile) + $yas;
    header("Content-type: text/javascript; charset: UTF-8");
    header("Cache-Control: max-age=$zaman_asimi");
    header("ETag: " . dechex($etag));
    header("Expires: " . gmdate("D, d M Y H:i:s", $yas) . " GMT");
    echo file_get_contents($cachefile);
}

if (isset($_GET['js'])) {
    $js = $_GET['js'];
    $js_cache_file = md5($js) . '-v-' . $version . '.js';
    $cachefile = $root_cache_dir . $js_cache_file;
    if (file_exists($cachefile) === FALSE) {
        require_once(DIRNAME(__FILE__) . 'koberz.init.php');
        $jsoptimizer = Load::lib('JsOptimizer');
        $jsoptimizer->cachefile = $cachefile;
        $jsoptimizer->js_cache_file = $js_cache_file;
        $jsoptimizer->run($js);
    }
    // Gösterelim
    show($cachefile);
} elseif ($_GET['cachefile']) {
    $cachefile = $root_cache_dir . basename($_GET['cachefile']) . '.js';
    if (file_exists($cachefile)) {
        // Gösterelim
        show($cachefile);
    } else {
        exit('NON');
    }
}
/*if ($zlib) {
    header("Content-Encoding: gzip");
    header("Content-Length: " . filesize($cachefile . ".gz"));
    $etag = filemtime($cachefile . ".gz") . " - ";
} else {
    header("Content-Length: " . filesize($$cachefile . ".js"));
    $etag = filemtime($cachefile . ".js") . " - ";
}*/
/*
if ($zlib) {
    echo file_get_contents($cachefile . '.gz');
} else {
    echo file_get_contents($cachefile);
}*/