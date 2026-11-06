<?php
/**
 * GoogleIndexingService - Helper to notify Google Search Console of URL updates and deletions
 * Native JWT OpenSSL implementation with zero Google\Client composer dependencies.
 * 
 * Amed Framework
 * @author Antigravity AI
 */

class GoogleIndexingService
{
    private $credentialsPath = '';
    private $isEnabled = false;
    private $tokenCache = [];
    private static $notifiedUrls = [];
    private static $pingedSitemapLangs = [];
    private static $stateFilePath = null;

    public function __construct()
    {
        $googleConfig = Registry::get('google_config');
        $credentialsPath = isset($googleConfig['credentials_path']) ? $googleConfig['credentials_path'] : '';

        // Fallback candidates if not set in registry
        if (empty($credentialsPath) || !file_exists($credentialsPath)) {
            $root = defined('root_dir') ? root_dir : dirname(__DIR__) . DIRECTORY_SEPARATOR;
            $candidates = [
                $root . 'data' . DIRECTORY_SEPARATOR . 'credentials.json',
                $root . 'data' . DIRECTORY_SEPARATOR . 'google_search_console_api.json',
                getenv('GOOGLE_APPLICATION_CREDENTIALS'),
            ];
            foreach ($candidates as $candidatePath) {
                if (!empty($candidatePath) && file_exists($candidatePath)) {
                    $credentialsPath = $candidatePath;
                    break;
                }
            }
        }

        if (!empty($credentialsPath) && file_exists($credentialsPath)) {
            $this->credentialsPath = $credentialsPath;
            $this->isEnabled = true;
        }

        if (self::$stateFilePath === null) {
            $root = defined('root_dir') ? root_dir : dirname(__DIR__) . DIRECTORY_SEPARATOR;
            self::$stateFilePath = $root . 'data' . DIRECTORY_SEPARATOR . 'indexing_state.json';
        }
    }

    /**
     * Read persistent indexing state from data/indexing_state.json
     */
    private function loadState(): array
    {
        if (self::$stateFilePath && file_exists(self::$stateFilePath)) {
            $data = json_decode((string)@file_get_contents(self::$stateFilePath), true);
            if (is_array($data)) {
                return $data;
            }
        }
        return ['urls' => [], 'sitemaps' => [], 'quota_exhausted_until' => 0];
    }

    /**
     * Save persistent indexing state to data/indexing_state.json
     */
    private function saveState(array $state): void
    {
        if (self::$stateFilePath) {
            @file_put_contents(self::$stateFilePath, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * Notify Google Search Console that a URL has been added or updated,
     * refresh the language homepage crawl, and resubmit sitemaps.
     * 
     * @param string $url Absolute URL of the page
     * @return bool True if notification was successfully sent
     */
    public function notifyUpdate(string $url): bool
    {
        if (!$this->isEnabled) {
            return false;
        }
        $result = $this->publishNotification($url, 'URL_UPDATED');
        $this->dispatchCompanionNotifications($url);
        return $result;
    }

    /**
     * Notify Google Search Console that a URL has been deleted,
     * refresh the language homepage crawl, and resubmit sitemaps.
     * 
     * @param string $url Absolute URL of the page
     * @return bool True if notification was successfully sent
     */
    public function notifyDelete(string $url): bool
    {
        if (!$this->isEnabled) {
            return false;
        }
        $result = $this->publishNotification($url, 'URL_DELETED');
        $this->dispatchCompanionNotifications($url);
        return $result;
    }

    /**
     * Resubmit master sitemap.xml and language newsSitemap.xml to Google Search Console
     */
    public function pingSitemaps(?string $baseOrigin = null, ?string $lang = null): bool
    {
        if (!$this->isEnabled) {
            return false;
        }

        $sitemapKey = ($baseOrigin ?? '') . '|' . ($lang ?? '');
        $state = $this->loadState();
        $now = time();
        $lastPing = (int)($state['sitemaps'][$sitemapKey] ?? 0);
        // Cooldown: do not re-ping the same sitemaps within 10 minutes (600s) across requests
        if (($now - $lastPing) < 600) {
            return true;
        }

        $root = defined('root_dir') ? root_dir : dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $propertyDomain = '';
        $seoSettingsPath = $root . 'data' . DIRECTORY_SEPARATOR . 'seo_settings.json';
        if (file_exists($seoSettingsPath)) {
            $settings = json_decode((string) @file_get_contents($seoSettingsPath), true);
            $cleanDomain = trim((string) ($settings['property_url'] ?? ''));
            $cleanDomain = preg_replace('#^https?://#i', '', $cleanDomain);
            $cleanDomain = preg_replace('#^sc-domain:#i', '', $cleanDomain);
            $cleanDomain = trim($cleanDomain, " /\t\n\r\0\x0B");
            if ($cleanDomain !== '' && !str_contains($cleanDomain, 'localhost')) {
                $propertyDomain = $cleanDomain;
            }
        }

        if ($baseOrigin === null || $baseOrigin === '') {
            $siteConfig = Registry::get('site_config');
            $baseOrigin = is_array($siteConfig) && !empty($siteConfig['address'])
                ? rtrim((string) $siteConfig['address'], '/')
                : ($propertyDomain !== '' ? 'https://' . $propertyDomain : '');
        } else {
            $baseOrigin = rtrim($baseOrigin, '/');
        }

        if ($propertyDomain === '' && $baseOrigin !== '') {
            $host = (string) parse_url($baseOrigin, PHP_URL_HOST);
            if ($host !== '' && !str_contains($host, 'localhost') && $host !== '127.0.0.1') {
                $propertyDomain = preg_replace('/^www\./i', '', $host);
            }
        }

        if ($propertyDomain === '' || $baseOrigin === '') {
            return false;
        }

        $token = $this->getAccessToken('https://www.googleapis.com/auth/webmasters');
        if (!$token) {
            return false;
        }

        $sitemaps = [$baseOrigin . '/sitemap.xml'];
        if (!empty($lang)) {
            $langPrefix = trim($lang, '/');
            $sitemaps[] = $baseOrigin . '/' . $langPrefix . '/newsSitemap.xml';
            $sitemaps[] = $baseOrigin . '/' . $langPrefix . '/pageSitemap.xml';
            $sitemaps[] = $baseOrigin . '/' . $langPrefix . '/categorySitemap.xml';

            $sitemaps[] = $baseOrigin . '/' . $langPrefix . '/gallerySitemap.xml';
        }

        $propertyId = 'sc-domain:' . $propertyDomain;
        $allOk = true;
        foreach (array_values(array_unique($sitemaps)) as $sitemapUrl) {
            $endpoint = 'https://www.googleapis.com/webmasters/v3/sites/'
                . rawurlencode($propertyId)
                . '/sitemaps/'
                . rawurlencode($sitemapUrl);

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Length: 0',
                ],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $responseBody = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode < 200 || $httpCode >= 300) {
                $allOk = false;
                error_log("Google Search Console sitemap ping failed ({$httpCode}) for {$sitemapUrl}: {$responseBody}");
            }
            curl_close($ch);
        }

        if ($allOk) {
            $state['sitemaps'][$sitemapKey] = $now;
            $this->saveState($state);
        }

        return $allOk;
    }

    /**
     * When a content item URL is updated/deleted, resubmit the sitemaps
     * for the affected language to Google Search Console.
     */
    private function dispatchCompanionNotifications(string $url): void
    {
        $parsed = parse_url($url);
        if (!is_array($parsed) || empty($parsed['scheme']) || empty($parsed['host'])) {
            return;
        }

        $host = strtolower((string) $parsed['host']);
        if (str_contains($host, 'localhost') || $host === '127.0.0.1') {
            return;
        }

        $baseOrigin = $parsed['scheme'] . '://' . $parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
        $lang = null;

        if (!empty($parsed['path']) && preg_match('#^/([a-z]{2,3})(/|$)#i', (string) $parsed['path'], $m)) {
            $lang = strtolower($m[1]);
        } else {
            $siteConfig = Registry::get('site_config');
            if (is_array($siteConfig) && !empty($siteConfig['default_lang'])) {
                $lang = (string) $siteConfig['default_lang'];
            }
        }

        $sitemapKey = $baseOrigin . '|' . ($lang ?? '');
        if (empty(self::$pingedSitemapLangs[$sitemapKey])) {
            self::$pingedSitemapLangs[$sitemapKey] = true;
            $this->pingSitemaps($baseOrigin, $lang);
        }
    }

    /**
     * Generate OAuth2 Access Token using RS256 JWT assertion
     */
    private function getAccessToken(string $scope = 'https://www.googleapis.com/auth/indexing'): ?string
    {
        if (isset($this->tokenCache[$scope])) {
            return $this->tokenCache[$scope];
        }

        if (empty($this->credentialsPath) || !file_exists($this->credentialsPath)) {
            return null;
        }

        $json = json_decode((string) @file_get_contents($this->credentialsPath), true);
        if (!$json || empty($json['private_key']) || empty($json['client_email'])) {
            return null;
        }

        $private_key = $json['private_key'];
        $client_email = $json['client_email'];

        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $now = time();
        $claim_set = json_encode([
            'iss' => $client_email,
            'scope' => $scope,
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        $base64_header = rtrim(strtr(base64_encode((string) $header), '+/', '-_'), '=');
        $base64_claim = rtrim(strtr(base64_encode((string) $claim_set), '+/', '-_'), '=');

        $signature_input = $base64_header . '.' . $base64_claim;
        if (!openssl_sign($signature_input, $signature, $private_key, 'SHA256')) {
            return null;
        }
        $base64_signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $jwt = $signature_input . '.' . $base64_signature;

        if (!class_exists('HttpClient')) {
            $httpPath = (defined('lib_dir') ? lib_dir : dirname(__DIR__) . '/lib/') . 'HttpClient.class.php';
            if (file_exists($httpPath)) {
                require_once $httpPath;
            }
        }

        if (!class_exists('HttpClient')) {
            return null;
        }

        $httpResult = HttpClient::postForm('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ], [], 10);

        if ($httpResult['status'] !== 200 || !is_array($httpResult['json'])) {
            return null;
        }

        $token = $httpResult['json']['access_token'] ?? null;
        if (is_string($token) && $token !== '') {
            $this->tokenCache[$scope] = $token;
            return $token;
        }

        return null;
    }

    /**
     * Publish notification to Google Indexing API endpoint
     */
    private function publishNotification(string $url, string $type): bool
    {
        $url = Helper::sitemap_url($url);
        $state = $this->loadState();
        $now = time();

        // Circuit breaker if quota was exhausted
        if (($state['quota_exhausted_until'] ?? 0) > $now) {
            return false;
        }

        $urlKey = $url . '|' . $type;
        $lastPublished = (int)($state['urls'][$urlKey] ?? 0);
        // Per-URL cooldown: 15 minutes (900s)
        if (($now - $lastPublished) < 900) {
            return true;
        }

        $token = $this->getAccessToken('https://www.googleapis.com/auth/indexing');
        if (!$token) {
            return false;
        }

        if (!class_exists('HttpClient')) {
            $httpPath = (defined('lib_dir') ? lib_dir : dirname(__DIR__) . '/lib/') . 'HttpClient.class.php';
            if (file_exists($httpPath)) {
                require_once $httpPath;
            }
        }

        $endpoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
        $payload = [
            'url' => $url,
            'type' => $type
        ];

        $res = HttpClient::postJson($endpoint, $payload, [
            'Authorization: Bearer ' . $token
        ], 10);

        if ($res['status'] !== 200) {
            $errMsg = $res['json']['error']['message'] ?? ('HTTP ' . $res['status']);
            error_log("Google Indexing API publish ({$type}) failed for {$url}: {$errMsg}");

            // Handle HTTP 429 quota exhaustion
            if ($res['status'] === 429) {
                // Back off until end of UTC day or 1 hour
                $state['quota_exhausted_until'] = $now + 3600;
                $this->saveState($state);
            }
            return false;
        }

        $state['urls'][$urlKey] = $now;
        $this->saveState($state);
        return true;
    }
}
