<?php

/**
 * Public entrypoint for standard llms.txt and llms-full.txt
 * 
 * Standard: https://llmstxt.org
 * Amed Framework - Newspaper and Editorial Edition
 * @author Antigravity AI
 */

require_once('vendor/autoload.php');
require_once('init.php');

$lang = isset($_GET['lang']) ? trim((string) $_GET['lang']) : '';
$isFull = (isset($_GET['mode']) && $_GET['mode'] === 'full');

try {
    /** @var LlmsService $llmsService */
    $llmsService = Load::lib('LlmsService');
    $output = $llmsService->render($lang, $isFull);

    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: public, max-age=3600, stale-while-revalidate=86400');
    header('X-Robots-Tag: all');

    echo $output;
} catch (\Throwable $e) {
    header('Content-Type: text/plain; charset=utf-8', true, 500);
    echo "# Error Generating llms.txt\n\n> An unexpected error occurred while generating the AI index.\n";
}
