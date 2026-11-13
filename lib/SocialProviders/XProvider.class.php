<?php

require_once __DIR__ . '/SocialProviderInterface.php';

/**
 * X (Twitter) API v2 Publishing Provider
 * Uses OAuth 1.0a User Context (Consumer Key, Consumer Secret, Access Token, Access Token Secret)
 * with HMAC-SHA1 signatures and pay-per-use rate protection.
 */
class XProvider implements SocialProviderInterface
{
    private const API_BASE = 'https://api.x.com/2';

    /**
     * Builds OAuth 1.0a Authorization header.
     *
     * @param string $method HTTP method (POST, GET)
     * @param string $url Target endpoint URL without query parameters
     * @param array $params Query or body parameters involved in signature
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param string $token
     * @param string $tokenSecret
     * @return string
     */
    public static function buildOAuth1Header(
        string $method,
        string $url,
        array $params,
        string $consumerKey,
        string $consumerSecret,
        string $token,
        string $tokenSecret
    ): string {
        $oauthParams = [
            'oauth_consumer_key' => $consumerKey,
            'oauth_nonce' => bin2hex(random_bytes(16)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_token' => $token,
            'oauth_version' => '1.0',
        ];

        // Combine all parameters, sort by key
        $allParams = array_merge($oauthParams, $params);
        ksort($allParams);

        // Normalize parameters
        $paramPairs = [];
        foreach ($allParams as $k => $v) {
            $paramPairs[] = rawurlencode((string) $k) . '=' . rawurlencode((string) $v);
        }
        $normalizedParams = implode('&', $paramPairs);

        // Build signature base string
        $baseString = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode($normalizedParams);

        // Build signing key
        $signingKey = rawurlencode($consumerSecret) . '&' . rawurlencode($tokenSecret);

        // Compute HMAC-SHA1 signature
        $oauthParams['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        // Format header
        $headerParts = [];
        foreach ($oauthParams as $k => $v) {
            $headerParts[] = rawurlencode($k) . '="' . rawurlencode($v) . '"';
        }

        return 'OAuth ' . implode(', ', $headerParts);
    }

    /**
     * Executes HTTP request with OAuth 1.0a authorization.
     *
     * @param string $method
     * @param string $url
     * @param array $credentials
     * @param array|null $jsonBody
     * @param array $queryParams
     * @return array [code, body, headers]
     */
    private function executeRequest(
        string $method,
        string $url,
        array $credentials,
        ?array $jsonBody = null,
        array $queryParams = []
    ): array {
        $consumerKey = (string) ($credentials['consumer_key'] ?? '');
        $consumerSecret = (string) ($credentials['consumer_secret'] ?? '');
        $token = (string) ($credentials['access_token'] ?? '');
        $tokenSecret = (string) ($credentials['access_token_secret'] ?? '');

        $fullUrl = $url;
        if (!empty($queryParams)) {
            $fullUrl .= '?' . http_build_query($queryParams);
        }

        // For JSON body, body params are not part of OAuth 1.0a signature base string (only query params)
        $authHeader = self::buildOAuth1Header(
            $method,
            $url,
            $queryParams,
            $consumerKey,
            $consumerSecret,
            $token,
            $tokenSecret
        );

        $headers = [
            'Authorization: ' . $authHeader,
            'User-Agent: AmedBulletin/1.0 (+https://amedbulten.com)',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($jsonBody !== null) {
                $payload = json_encode($jsonBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false) {
            return [
                'code' => 0,
                'body' => null,
                'headers' => [],
                'curl_error' => $curlError,
            ];
        }

        $headerStr = substr($rawResponse, 0, $headerSize);
        $bodyStr = substr($rawResponse, $headerSize);

        $parsedHeaders = [];
        foreach (explode("\r\n", $headerStr) as $line) {
            if (strpos($line, ':') !== false) {
                [$k, $v] = explode(':', $line, 2);
                $parsedHeaders[strtolower(trim($k))] = trim($v);
            }
        }

        $data = json_decode($bodyStr, true);

        return [
            'code' => $httpCode,
            'body' => is_array($data) ? $data : $bodyStr,
            'headers' => $parsedHeaders,
        ];
    }

    /**
     * Publishes a news item to X (Twitter).
     *
     * @param array $account
     * @param array $news
     * @param array $job
     * @param array $options
     * @return array
     */
    public function publish(array $account, array $news, array $job, array $options = []): array
    {
        $credentials = $options['credentials'] ?? [];
        if (empty($credentials) && !empty($account['credentials_cipher'])) {
            $credentials = SocialCrypto::decryptPayload($account['credentials_cipher']) ?? [];
        }

        if (empty($credentials['consumer_key']) || empty($credentials['consumer_secret']) ||
            empty($credentials['access_token']) || empty($credentials['access_token_secret'])) {
            return [
                'success' => false,
                'status' => 'failed',
                'post_id' => null,
                'post_url' => null,
                'container_id' => null,
                'error_code' => 'INVALID_CREDENTIALS',
                'error_message' => 'Missing OAuth 1.0a credentials in account configuration',
                'is_transient' => false,
            ];
        }

        $shareUrl = $options['share_url'] ?? '';
        $headline = trim((string) ($news['name'] ?? ''));
        $customCaption = trim((string) ($options['custom_caption'] ?? ''));

        $postText = !empty($customCaption) ? $customCaption : $headline;
        if (!empty($shareUrl)) {
            // Guarantee 1-tap card preview by appending canonical article URL
            $postText .= "\n\n" . $shareUrl;
        }

        $payload = [
            'text' => $postText,
        ];

        $response = $this->executeRequest('POST', self::API_BASE . '/tweets', $credentials, $payload);

        $httpCode = $response['code'];
        $body = $response['body'];

        if ($httpCode === 201 && is_array($body) && isset($body['data']['id'])) {
            $tweetId = (string) $body['data']['id'];
            $postUrl = 'https://x.com/i/status/' . $tweetId;

            return [
                'success' => true,
                'status' => 'published',
                'post_id' => $tweetId,
                'post_url' => $postUrl,
                'container_id' => null,
                'error_code' => null,
                'error_message' => null,
                'is_transient' => false,
            ];
        }

        // Handle Payment Required / Credit Depleted (HTTP 402)
        if ($httpCode === 402) {
            return [
                'success' => false,
                'status' => 'failed',
                'post_id' => null,
                'post_url' => null,
                'container_id' => null,
                'error_code' => 'CREDITS_DEPLETED_USE_RSS_BRIDGE',
                'error_message' => 'X API credits depleted (HTTP 402). Switch to RSS Bridge mode (Buffer/IFTTT/Make) to publish at zero cost.',
                'is_transient' => false,
            ];
        }

        // Handle Rate Limits (HTTP 429)
        if ($httpCode === 429) {
            $retryAfter = 900;
            if (isset($response['headers']['x-rate-limit-reset'])) {
                $resetTime = (int) $response['headers']['x-rate-limit-reset'];
                $retryAfter = max(60, $resetTime - time());
            }

            return [
                'success' => false,
                'status' => 'pending',
                'post_id' => null,
                'post_url' => null,
                'container_id' => null,
                'error_code' => 'RATE_LIMIT_EXCEEDED',
                'error_message' => 'X API rate limit reached. Reset in ' . $retryAfter . ' seconds.',
                'is_transient' => true,
                'retry_after' => $retryAfter,
            ];
        }

        // Handle Permanent vs Transient Errors
        $isTransient = ($httpCode >= 500 || $httpCode === 0);
        $errorCode = 'HTTP_' . $httpCode;
        $errorMsg = 'Unknown error from X API';

        if (is_array($body)) {
            if (isset($body['errors'][0]['message'])) {
                $errorMsg = (string) $body['errors'][0]['message'];
            } elseif (isset($body['detail'])) {
                $errorMsg = (string) $body['detail'];
            } elseif (isset($body['title'])) {
                $errorMsg = (string) $body['title'];
            }
        } elseif (!empty($response['curl_error'])) {
            $errorMsg = 'cURL error: ' . $response['curl_error'];
            $errorCode = 'CURL_ERROR';
        }

        return [
            'success' => false,
            'status' => $isTransient ? 'pending' : 'failed',
            'post_id' => null,
            'post_url' => null,
            'container_id' => null,
            'error_code' => $errorCode,
            'error_message' => $errorMsg,
            'is_transient' => $isTransient,
        ];
    }

    /**
     * Verifies OAuth 1.0a connection by calling X /2/users/me.
     *
     * @param array $credentials
     * @param string|null $externalAccountId
     * @return array
     */
    public function verifyConnection(array $credentials, ?string $externalAccountId = null): array
    {
        $response = $this->executeRequest('GET', self::API_BASE . '/users/me', $credentials);

        if ($response['code'] === 200 && is_array($response['body']) && isset($response['body']['data'])) {
            $data = $response['body']['data'];
            return [
                'success' => true,
                'message' => 'Authenticated as @' . ($data['username'] ?? 'unknown') . ' (' . ($data['name'] ?? '') . ')',
                'details' => $data,
            ];
        }

        $msg = 'Failed to verify X credentials';
        if (is_array($response['body']) && isset($response['body']['detail'])) {
            $msg = (string) $response['body']['detail'];
        }

        return [
            'success' => false,
            'message' => $msg . ' (HTTP ' . $response['code'] . ')',
            'details' => is_array($response['body']) ? $response['body'] : [],
        ];
    }
}
