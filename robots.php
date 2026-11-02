<?php
require_once('vendor/autoload.php');
require_once('init.php');

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow, noarchive');

echo "User-agent: *\n";
echo "Disallow: /\n";
