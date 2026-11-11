<?php

require_once __DIR__ . '/SocialProviderInterface.php';

/**
 * Instagram Stories Meta Graph API v27.0 Provider
 * Generates and publishes 9:16 vertical 1080x1920 sRGB JPEGs to Instagram Stories
 * with non-blocking container status polling and bio landing bridge integration.
 */
class InstagramStoryProvider implements SocialProviderInterface
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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
     * Publishes a story card to Instagram Stories.
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
        $igUserId = (string) ($account['external_account_id'] ?? ($credentials['ig_user_id'] ?? ''));

        if (empty($accessToken) || empty($igUserId)) {
            return [
                'success' => false,
                'status' => 'failed',
                'post_id' => null,
                'post_url' => null,
                'container_id' => null,
                'error_code' => 'INVALID_CREDENTIALS',
                'error_message' => 'Missing Instagram Business User ID or Access Token',
                'is_transient' => false,
            ];
        }

        $containerId = $job['external_container_id'] ?? null;

        // Step 1: Create Container if not yet created
        if (empty($containerId)) {
            $publicStoryImageUrl = $options['story_image_url'] ?? null;
            if (empty($publicStoryImageUrl)) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'post_id' => null,
                    'post_url' => null,
                    'container_id' => null,
                    'error_code' => 'MISSING_STORY_IMAGE',
                    'error_message' => 'Instagram Story requires a public HTTPS story image URL',
                    'is_transient' => false,
                ];
            }

            $createRes = $this->executeRequest('POST', $igUserId . '/media', [
                'image_url' => $publicStoryImageUrl,
                'media_type' => 'STORIES',
                'access_token' => $accessToken,
            ]);

            if ($createRes['code'] === 200 && is_array($createRes['body']) && isset($createRes['body']['id'])) {
                $newContainerId = (string) $createRes['body']['id'];

                // Container successfully queued at Meta; yield and schedule status poll in 5s
                return [
                    'success' => false,
                    'status' => 'in_progress',
                    'post_id' => null,
                    'post_url' => null,
                    'container_id' => $newContainerId,
                    'error_code' => null,
                    'error_message' => null,
                    'is_transient' => true,
                    'retry_after' => 5,
                ];
            }

            $metaErr = $createRes['body']['error']['message'] ?? ($createRes['curl_error'] ?? 'Failed to create Instagram container');
            $metaCode = $createRes['body']['error']['code'] ?? 0;
            $isTransient = ($metaCode === 1 || $metaCode === 2 || $createRes['code'] >= 500 || $createRes['code'] === 0);

            return [
                'success' => false,
                'status' => $isTransient ? 'pending' : 'failed',
                'post_id' => null,
                'post_url' => null,
                'container_id' => null,
                'error_code' => 'IG_CONTAINER_CREATE_' . $metaCode,
                'error_message' => $metaErr,
                'is_transient' => $isTransient,
            ];
        }

        // Step 2: Poll Container Status
        $statusRes = $this->executeRequest('GET', $containerId, [
            'fields' => 'status_code,status',
            'access_token' => $accessToken,
        ]);

        $statusCode = is_array($statusRes['body']) ? ($statusRes['body']['status_code'] ?? null) : null;

        if ($statusCode === 'IN_PROGRESS') {
            // Still processing at Instagram; yield for another 5s
            return [
                'success' => false,
                'status' => 'in_progress',
                'post_id' => null,
                'post_url' => null,
                'container_id' => $containerId,
                'error_code' => null,
                'error_message' => null,
                'is_transient' => true,
                'retry_after' => 5,
            ];
        }

        if ($statusCode === 'ERROR' || $statusCode === 'EXPIRED') {
            $msg = is_array($statusRes['body']) ? ($statusRes['body']['status'] ?? 'Container failed processing') : 'Processing error';
            return [
                'success' => false,
                'status' => 'failed',
                'post_id' => null,
                'post_url' => null,
                'container_id' => $containerId,
                'error_code' => 'IG_CONTAINER_' . $statusCode,
                'error_message' => $msg,
                'is_transient' => false,
            ];
        }

        // Step 3: Publish Container when FINISHED (or statusCode empty if ready)
        $publishRes = $this->executeRequest('POST', $igUserId . '/media_publish', [
            'creation_id' => $containerId,
            'access_token' => $accessToken,
        ]);

        if ($publishRes['code'] === 200 && is_array($publishRes['body']) && isset($publishRes['body']['id'])) {
            $publishedId = (string) $publishRes['body']['id'];
            $postUrl = 'https://www.instagram.com/';

            return [
                'success' => true,
                'status' => 'published',
                'post_id' => $publishedId,
                'post_url' => $postUrl,
                'container_id' => $containerId,
                'error_code' => null,
                'error_message' => null,
                'is_transient' => false,
            ];
        }

        $metaErr = $publishRes['body']['error']['message'] ?? ($publishRes['curl_error'] ?? 'Failed to publish Instagram story');
        $metaCode = $publishRes['body']['error']['code'] ?? 0;
        $isTransient = ($metaCode === 1 || $metaCode === 2 || $publishRes['code'] >= 500);

        return [
            'success' => false,
            'status' => $isTransient ? 'pending' : 'failed',
            'post_id' => null,
            'post_url' => null,
            'container_id' => $containerId,
            'error_code' => 'IG_PUBLISH_' . $metaCode,
            'error_message' => $metaErr,
            'is_transient' => $isTransient,
        ];
    }

    /**
     * Verifies Instagram Business credentials.
     *
     * @param array $credentials
     * @param string|null $externalAccountId
     * @return array
     */
    public function verifyConnection(array $credentials, ?string $externalAccountId = null): array
    {
        $accessToken = (string) ($credentials['access_token'] ?? '');
        $igUserId = (string) ($externalAccountId ?? ($credentials['ig_user_id'] ?? ''));

        if (empty($accessToken) || empty($igUserId)) {
            return [
                'success' => false,
                'message' => 'Missing Instagram User ID or Access Token',
                'details' => [],
            ];
        }

        $response = $this->executeRequest('GET', $igUserId, [
            'fields' => 'id,username,name,profile_picture_url',
            'access_token' => $accessToken,
        ]);

        if ($response['code'] === 200 && is_array($response['body']) && isset($response['body']['id'])) {
            $data = $response['body'];
            return [
                'success' => true,
                'message' => 'Connected to @' . ($data['username'] ?? $data['id']),
                'details' => $data,
            ];
        }

        $errorMsg = $response['body']['error']['message'] ?? 'Unknown Instagram error';
        return [
            'success' => false,
            'message' => $errorMsg . ' (HTTP ' . $response['code'] . ')',
            'details' => is_array($response['body']) ? $response['body'] : [],
        ];
    }
}
