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

$activeLangs = Registry::get('active_langs');
if (!is_array($activeLangs) || empty($activeLangs)) {
    $activeLangs = [];
    $dbKey = Registry::get('database');
    $dbConfig = Registry::get('db_config');
    $hasDbConfig = is_string($dbKey) && $dbKey !== ''
        && is_array($dbConfig)
        && isset($dbConfig[$dbKey]['adapter'], $dbConfig[$dbKey]['conf'])
        && is_array($dbConfig[$dbKey]['conf'])
        && class_exists('PDO')
        && in_array('mysql', PDO::getAvailableDrivers(), true);

    if ($hasDbConfig) {
        try {
            $langModel = Load::model('lang');
            $langModel->all();
            if ($langModel->getSize() > 0) {
                foreach ($langModel->toArray() as $row) {
                    $isActive = !isset($row['status']) || $row['status'] === 'y';
                    if ($isActive && !empty($row['seo'])) {
                        $activeLangs[] = (string) $row['seo'];
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fallback to configured default language
        }
    }
    if (empty($activeLangs)) {
        $defaultLang = is_array($site) && !empty($site['default_lang']) ? (string) $site['default_lang'] : 'tr';
        $activeLangs = [$defaultLang];
    }
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
    foreach (array_values(array_unique($activeLangs)) as $lang) {
        echo "Sitemap: " . $baseUrl . $lang . "/newsSitemap.xml\n";
        echo "Sitemap: " . $baseUrl . $lang . "/newsArchiveSitemap.xml\n";
    }
    echo "\n# AI / LLM Index\n";
    echo "# LLMS-Txt: " . $baseUrl . "llms.txt\n";
}
