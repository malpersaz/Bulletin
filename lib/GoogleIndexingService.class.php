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
    }

    /**
     * Notify Google Search Console that a URL has been added or updated
     * 
     * @param string $url Absolute URL of the page
     * @return bool True if notification was successfully sent
     */
    public function notifyUpdate(string $url): bool
    {
        if (!$this->isEnabled) {
            return false;
        }
        return $this->publishNotification($url, 'URL_UPDATED');
    }

    /**
     * Notify Google Search Console that a URL has been deleted
     * 
     * @param string $url Absolute URL of the page
     * @return bool True if notification was successfully sent
     */
    public function notifyDelete(string $url): bool
    {
        if (!$this->isEnabled) {
            return false;
        }
        return $this->publishNotification($url, 'URL_DELETED');
    }

    /**
     * Generate OAuth2 Access Token using RS256 JWT assertion
     */
    private function getAccessToken(): ?string
    {
        if (empty($this->credentialsPath) || !file_exists($this->credentialsPath)) {
            return null;
        }

        $json = json_decode(@file_get_contents($this->credentialsPath), true);
        if (!$json || empty($json['private_key']) || empty($json['client_email'])) {
            return null;
        }

        $private_key = $json['private_key'];
        $client_email = $json['client_email'];

        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $now = time();
        $claim_set = json_encode([
            'iss' => $client_email,
            'scope' => 'https://www.googleapis.com/auth/indexing',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        $base64_header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64_claim = rtrim(strtr(base64_encode($claim_set), '+/', '-_'), '=');

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

        return $httpResult['json']['access_token'] ?? null;
    }

    /**
     * Publish notification to Google Indexing API endpoint
     */
    private function publishNotification(string $url, string $type): bool
    {
        $token = $this->getAccessToken();
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
            return false;
        }

        return true;
    }
}
