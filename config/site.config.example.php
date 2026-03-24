<?php

/**
 * Amed Framework
 * Kullanıcı: a.k.mutlu
 * Tarih: 4/26/11 - 1:58 PM
 * Telif Hakkı:AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * Dosya:site.config.php
 * Açıklama:Site Config Bilgileri
 */
//$site['title'] = 'Site Name';
//$site['keywords'] = 'Your, Site, Keywords';
//$site['description'] = 'Yoru Site Description';


$host=$_SERVER['HTTP_HOST'];
if ($host == 'localhost' 
|| strpos($host, 'localhost') !== false
|| strpos($host, 'pinggy.link') !== false
) {
    $site['adres'] = 'http://'.$host.'/';
    $site['sadres'] = 'http://'.$host.'/';
    $site['radres'] = 'http://'.$host.'/static/';
    $site['tadres'] = 'http://'.$host.'/templates/';
} else {
    $site['adres'] = 'https://sitename.com/';
    $site['sadres'] = 'https://sitename.com/';
    $site['radres'] = 'https://image.sitename.com/';
    $site['tadres'] = 'https://template.sitename.com/';
}
$site['css'] = 'static/css/';
$site['js'] = 'static/js/';
$site['lang'] = 'en';
$site['author'] = 'AKMSoftware';
$site['generator'] = 'Amed Framework';
$site['developer'] = 'AKMSoftware';
$site['app_template'] = 'default';


//$site['position']="37.938274,40.28630350000003";
//$site['position'] = "37.938274,40.28630350000003";

if (isset($_COOKIE['tema'])) {
    $full = template_dir . basename(Req::Clear($_COOKIE['tema']));
    if (file_exists($full) && is_dir($full)) {
        $site['app_template'] = basename(Req::Clear($_COOKIE['tema']));
    }
}

// Site teması
Kayit::ekle('app_template', $site['app_template']);
// Admin Teması
Kayit::ekle('admin_template', 'admin');
// Default Mod
Kayit::ekle('app_default_mod', 'index');
// Default Function
Kayit::ekle('app_default_function', 'index');
// Site Lang
Kayit::ekle('lang', $site['lang']);

Kayit::ekle('site_config', $site);
unset($site);
