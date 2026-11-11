<?php

require_once __DIR__ . '/SocialProviderInterface.php';

/**
 * Facebook Pages Meta Graph API v27.0 Provider
 * Publishes link feed posts with automatic OpenGraph previews
 * using Meta Business Portfolio System User Access Tokens (SUAT).
 */
class FacebookPageProvider implements SocialProviderInterface
{
    private const GRAPH_BASE = 'https://graph.facebook.com/v27.0';

    /**
     * Executes Meta Graph API HTTP request.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @return array [code, body, error]
     */
    private function executeRequest(string $method, string $endpoint, array $params = []): array
    {
        $url = self::GRAPH_BASE . '/' . ltrim($endpoint, '/');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AmedBulletin/1.0 (+https://amedbulten.com)');

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } else {
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false) {
            return [
                'code' => 0,
                'body' => null,
                'curl_error' => $curlError,
            ];
        }

        $data = json_decode($rawResponse, true);

        return [
            'code' => $httpCode,
            'body' => is_array($data) ? $data : $rawResponse,
            'curl_error' => null,
        ];
    }

    /**
     * Publishes a news item to Facebook Page.
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

        $accessToken = (string) ($credentials['access_token'] ?? '');
        $pageId = (string) ($account['external_account_id'] ?? ($credentials['page_id'] ?? ''));

        if (empty($accessToken) || empty($pageId)) {
            return [
                'success' => false,
                'status' => 'failed',
                'post_id' => null,
                'post_url' => null,
                'container_id' => null,
                'error_code' => 'INVALID_CREDENTIALS',
                'error_message' => 'Missing Facebook Page ID or System User Access Token',
                'is_transient' => false,
            ];
        }

        $shareUrl = $options['share_url'] ?? '';
        $headline = trim((string) ($news['name'] ?? ''));
        $customCaption = trim((string) ($options['custom_caption'] ?? ''));
        $message = !empty($customCaption) ? $customCaption : $headline;

        $postParams = [
            'message' => $message,
            'published' => 'true',
            'access_token' => $accessToken,
        ];

        if (!empty($shareUrl)) {
            $postParams['link'] = $shareUrl;
        }

        $response = $this->executeRequest('POST', $pageId . '/feed', $postParams);

        $httpCode = $response['code'];
        $body = $response['body'];

        if ($httpCode === 200 && is_array($body) && isset($body['id'])) {
            $postId = (string) $body['id'];
            // Construct live Facebook post URL
            // Post ID format: {pageId}_{targetId}
            $postUrl = 'https://www.facebook.com/' . $postId;
            if (strpos($postId, '_') !== false) {
                [$pId, $storyId] = explode('_', $postId, 2);
                $postUrl = 'https://www.facebook.com/' . $pId . '/posts/' . $storyId;
            }

            return [
                'success' => true,
                'status' => 'published',
                'post_id' => $postId,
                'post_url' => $postUrl,
                'container_id' => null,
                'error_code' => null,
                'error_message' => null,
                'is_transient' => false,
            ];
        }

        // Error Analysis
        $metaErrorCode = $body['error']['code'] ?? 0;
        $metaErrorSubcode = $body['error']['error_subcode'] ?? 0;
        $metaMsg = $body['error']['message'] ?? ($response['curl_error'] ?? 'Facebook Graph API Error');

        // Meta Error 190: Access token has expired or been invalidated
        $isTokenExpired = ($metaErrorCode === 190);
        $isTransient = (!$isTokenExpired && ($httpCode >= 500 || $httpCode === 0 || in_array($metaErrorCode, [1, 2, 4, 17, 341, 368], true)));

        return [
            'success' => false,
            'status' => $isTransient ? 'pending' : 'failed',
            'post_id' => null,
            'post_url' => null,
            'container_id' => null,
            'error_code' => 'META_' . $metaErrorCode . ($metaErrorSubcode ? '_' . $metaErrorSubcode : ''),
            'error_message' => $metaMsg,
            'is_transient' => $isTransient,
        ];
    }

    /**
     * Verifies Facebook Page credentials and access token.
     *
     * @param array $credentials
     * @param string|null $externalAccountId
     * @return array
     */
    public function verifyConnection(array $credentials, ?string $externalAccountId = null): array
    {
        $accessToken = (string) ($credentials['access_token'] ?? '');
        $pageId = (string) ($externalAccountId ?? ($credentials['page_id'] ?? ''));

        if (empty($accessToken) || empty($pageId)) {
            return [
                'success' => false,
                'message' => 'Missing Page ID or Access Token',
                'details' => [],
            ];
        }

        $response = $this->executeRequest('GET', $pageId, [
            'fields' => 'id,name,link,category',
            'access_token' => $accessToken,
        ]);

        if ($response['code'] === 200 && is_array($response['body']) && isset($response['body']['id'])) {
            $data = $response['body'];
            return [
                'success' => true,
                'message' => 'Connected to Page: ' . ($data['name'] ?? $data['id']) . ' (' . ($data['category'] ?? '') . ')',
                'details' => $data,
            ];
        }

        $errorMsg = $response['body']['error']['message'] ?? 'Unknown Facebook error';
        return [
            'success' => false,
            'message' => $errorMsg . ' (HTTP ' . $response['code'] . ')',
            'details' => is_array($response['body']) ? $response['body'] : [],
        ];
    }
}
