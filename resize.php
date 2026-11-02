<?php

require 'vendor/autoload.php';
require 'lib/Req.class.php';
require 'lib/Helper.class.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

function showImage(string $file, string $extension): void
{
    $types = [
        'gif'  => 'image/gif',
        'png'  => 'image/png',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'webp' => 'image/webp',
    ];

    if (!isset($types[$extension])) {
        exit;
    }

    header('Content-Type: ' . $types[$extension]);
    header('Cache-Control: max-age=31536000, public, immutable');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    readfile($file);
    exit;
}

$dir = dirname(__FILE__) . '/';
$cache = $dir . 'cache/img/';
if (!is_dir($cache)) {
    @mkdir($cache, 0777, true);
}

$options = Req::is_Get('options') ? Req::Get_Safe('options') : exit('1');
$file = Req::is_Get('file') ? Req::Get_Safe('file') : exit('2');

// Sanitization
$file = str_replace('@..@', '', $file);
$source_extension = strtolower((string) Helper::extension($file));

// For all raster photo formats, produce optimized WebP
$target_extension = ($source_extension === 'gif') ? 'gif' : 'webp';

$key = md5($file . $options);
$cache_file = $cache . $key . '.' . $target_extension;
if (file_exists($cache_file) && filesize($cache_file) > 0) {
    showImage($cache_file, $target_extension);
}

// Module path prefix
if (preg_match('@^mod_@', $file)) {
    $dir = $dir . 'static/';
}
$fullfile = $dir . $file;

if (!file_exists($fullfile)) {
    exit('3');
}

// Parse Options
$option_parts = explode('/', $options);
$w = null;
$h = null;
$a = 1;
$u = 1;
$c = 0;
$q = 74;

foreach ($option_parts as $o) {
    if (preg_match('@^w-(\d+)@', $o, $m)) {
        $w = (int) $m[1];
        continue;
    }
    if (preg_match('@^h-(\d+)@', $o, $m)) {
        $h = (int) $m[1];
        continue;
    }
    if (preg_match('@^a-([0|1])@', $o, $m)) {
        $a = (int) $m[1];
        continue;
    }
    if (preg_match('@^u-([0|1])@', $o, $m)) {
        $u = (int) $m[1];
        continue;
    }
    if (preg_match('@^c-([0|1])@', $o, $m)) {
        $c = (int) $m[1];
        continue;
    }
    if (preg_match('@^q-(\d+)@', $o, $m)) {
        $q = max(30, min(100, (int) $m[1]));
        continue;
    }
}

try {
    if (extension_loaded('imagick')) {
        $manager = new ImageManager(new ImagickDriver());
    } elseif (extension_loaded('gd')) {
        $manager = new ImageManager(new GdDriver());
    } else {
        showImage($fullfile, $source_extension);
    }

    $image = $manager->read($fullfile);

    // Apply transformation
    if ($c === 1 && $w && $h) {
        $image = $image->coverDown($w, $h);
    } elseif ($a === 1 && $u === 1) {
        if ($w && $h) {
            $image = $image->scaleDown($w, $h);
        } elseif ($w) {
            $image = $image->scaleDown($w);
        } elseif ($h) {
            $image = $image->scaleDown(height: $h);
        }
    } else {
        if ($w && $h) {
            $image = $image->resize($w, $h);
        } elseif ($w) {
            $image = $image->resize($w, null);
        } elseif ($h) {
            $image = $image->resize(null, $h);
        }
    }

    if ($target_extension === 'webp') {
        $encoded = $image->toWebp($q);
        $encoded->save($cache_file);
    } else {
        $image->save($cache_file);
    }

    if (file_exists($cache_file) && filesize($cache_file) > 0) {
        showImage($cache_file, $target_extension);
    }
} catch (\Throwable $exception) {
    // Fall back to original file if resizing fails
}

showImage($fullfile, $source_extension);
