<?php
require_once('vendor/autoload.php');
require_once('init.php');

$site = Registry::get('site_config');
$baseUrl = '';

if (is_array($site) && !empty($site['address'])) {
    $baseUrl = rtrim((string) $site['address'], '/') . '/';
} elseif (is_array($site) && !empty($site['saddress'])) {
    $baseUrl = rtrim((string) $site['saddress'], '/') . '/';
}

header('Content-Type: text/plain; charset=utf-8');

echo "User-agent: *\n";
echo "Allow: /\n\n";
echo "# Disallow internal utility and tool endpoints\n";
echo "Disallow: /tools/\n";
echo "Disallow: /*/tools/\n";
echo "Disallow: /cache/\n";
echo "Disallow: /installer/\n\n";

if ($baseUrl !== '') {
    echo "# Master Sitemap Index\n";
    echo "Sitemap: " . $baseUrl . "sitemap.xml\n";
    echo "Sitemap: " . $baseUrl . "newsSitemap.xml\n\n";
    echo "# AI / LLM Index\n";
    echo "# LLMS-Txt: " . $baseUrl . "llms.txt\n";
}
