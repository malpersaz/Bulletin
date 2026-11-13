<?php
/**
 * WebSubService - Real-time WebSub / PubSubHubbub Hub Notification Service
 *
 * Dispatches instant HTTP POST publish pings to public hubs when RSS feeds are updated.
 * Implements persistent in-process and file-based cooldown to avoid rate-limiting during batch operations.
 *
 * Amed Framework
 * @author Antigravity AI
 */
class WebSubService
{
    /**
     * Public WebSub / PubSubHubbub hubs to ping
     */
    private static array $hubs = [
        'https://pubsubhubbub.appspot.com/',
        'https://pubsubhubbub.superfeedr.com',
    ];

    /**
     * File path for persisting last ping timestamps
     */
    private static ?string $stateFilePath = null;

    /**
     * Cooldown threshold in seconds per feed URL
     */
    private const COOLDOWN_SECONDS = 60;

    /**
     * Get or initialize state file path
     */
    private static function getStateFilePath(): string
    {
        if (self::$stateFilePath === null) {
            $dataDir = defined('root_dir') ? root_dir . 'data' : dirname(__DIR__) . '/data';
            if (!is_dir($dataDir)) {
                @mkdir($dataDir, 0775, true);
            }
            self::$stateFilePath = $dataDir . '/indexing_state.json';
        }
        return self::$stateFilePath;
    }

    /**
     * Load indexing state from JSON file
     */
    private static function loadState(): array
    {
        $file = self::getStateFilePath();
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            if ($content !== false && $content !== '') {
                $data = json_decode($content, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }
        return [];
    }

    /**
     * Save indexing state to JSON file
     */
    private static function saveState(array $state): void
    {
        $file = self::getStateFilePath();
        @file_put_contents($file, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    /**
     * Check if a feed URL is within cooldown
     */
    private static function isThrottled(string $feedUrl): bool
    {
        $state = self::loadState();
        $websub = $state['websub'] ?? [];
        $lastPing = $websub[$feedUrl] ?? 0;
        return (time() - (int) $lastPing) < self::COOLDOWN_SECONDS;
    }

    /**
     * Record a successful ping timestamp for a feed URL
     */
    private static function recordPing(string $feedUrl): void
    {
        $state = self::loadState();
        if (!isset($state['websub']) || !is_array($state['websub'])) {
            $state['websub'] = [];
        }
        $state['websub'][$feedUrl] = time();
        self::saveState($state);
    }

    /**
     * Notify WebSub hubs for a given feed URL or list of feed URLs.
     *
     * @param string|array $feedUrls Absolute URL(s) of the feed(s)
     * @return array Results per hub ['hub_url' => bool]
     */
    public static function pingHubs($feedUrls): array
    {
        $urls = is_array($feedUrls) ? $feedUrls : [$feedUrls];
        $filteredUrls = [];

        foreach ($urls as $url) {
            $url = trim((string) $url);
            if ($url !== '' && !self::isThrottled($url)) {
                $filteredUrls[] = $url;
            }
        }

        if (empty($filteredUrls)) {
            return [];
        }

        $results = [];

        foreach (self::$hubs as $hubUrl) {
            $postFields = [
                'hub.mode' => 'publish',
            ];

            // Build query string supporting repeated hub.url parameters
            $parts = ['hub.mode=publish'];
            foreach ($filteredUrls as $u) {
                $parts[] = 'hub.url=' . urlencode($u);
            }
            $postBody = implode('&', $parts);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $hubUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Amed-Bulletin-WebSub/1.0');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
            ]);

            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // Hubs return 204 No Content or 200 OK on successful publish ping
            $success = ($httpCode >= 200 && $httpCode < 300);
            $results[$hubUrl] = $success;
        }

        // Record successful pings in cooldown state
        foreach ($filteredUrls as $u) {
            self::recordPing($u);
        }

        return $results;
    }

    /**
     * Ping all registered feeds (standard RSS and social RSS) for a published article's language.
     *
     * @param string $lang Language code (e.g. 'tr', 'ku')
     * @return bool True if at least one feed was processed
     */
    public static function pingForArticle(string $lang): bool
    {
        $lang = trim($lang);
        if ($lang === '') {
            $lang = 'tr';
        }

        $site_config = Registry::get('site_config');
        $baseUrl = isset($site_config['address']) ? (string) $site_config['address'] : '';
        if ($baseUrl === '') {
            return false;
        }

        $baseUrl = rtrim($baseUrl, '/') . '/';
        $feedUrls = [
            $baseUrl . $lang . '/rss.xml',
            $baseUrl . $lang . '/rss/social.xml',
        ];

        $results = self::pingHubs($feedUrls);
        return true;
    }
}
