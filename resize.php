<?php

require 'vendor/autoload.php';
require 'lib/Req.class.php';
require 'lib/Helper.class.php';

// import the Intervention Image Manager Class
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;


function showImage($file, $uzanti)
{
    $types = array(
        'gif' => 'image/gif',
        'png' => 'image/png',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
    );
    if (!isset($types[$uzanti])) {
        exit;
    }
    header('Content-type: ' . $types[$uzanti]);
    readfile($file);
    exit;
}

$dir = dirname(__FILE__) . "/";
$cache = $dir . "cache/img/";
$options = Req::is_Get('options') ? Req::Get_Safe('options') : exit("1");
$file = Req::is_Get('file') ? Req::Get_Safe('file') : exit("2");

$uzanti = Helper::extension($file);

$key = md5($file . $options);
$cache_file = $cache . $key;
if (file_exists($cache_file)) {
    showImage($cache_file, $uzanti);
}

// Parse Options
$options = explode("/", $options);
// Remove File ..
$file = str_replace('@..@', '', $file);
// This mod?
if (preg_match("@^mod_@", $file)) {
    $dir = $dir . 'static/';
}
// Full File Dir
$fullfile = $dir . $file;

// File is Exists?
if (!file_exists($fullfile)) {
    exit("3");
}

$w = NULL;
$h = NULL;
$a = 1;
$u = 1;

// Parse Options
foreach ($options as $o) {
    if (preg_match("@^w-(\d+)@", $o, $c)) {
        $w = $c[1];
        continue;
    }
    if (preg_match("@^h-(\d+)@", $o, $c)) {
        $h = $c[1];
        continue;
    }
    if (preg_match("@^a-([0|1])@", $o, $c)) {
        $a = $c[1];
        continue;
    }
    if (preg_match("@^u-([0|1])@", $o, $c)) {
        $u = $c[1];
        continue;
    }
}


$manager = new ImageManager(new Driver());


// open and resize an image file
$image = $manager->read($fullfile);

// Handle resize based on options
if ($a == 1 && $u == 1) {
    // Maintain aspect ratio and upsize (scaleDown behavior)
    if ($w && $h) {
        $image = $image->scaleDown($w, $h);
    } elseif ($w) {
        $image = $image->scaleDown($w);
    } elseif ($h) {
        $image = $image->scaleDown(height: $h);
    }
} else {
    // Exact resize without constraints
    if ($w && $h) {
        $image = $image->resize($w, $h);
    } elseif ($w) {
        $image = $image->resize($w, null);
    } elseif ($h) {
        $image = $image->resize(null, $h);
    }
}
$image->save($cache_file);
showImage($cache_file, $uzanti);
