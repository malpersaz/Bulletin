<?php

/**
 * Interface for Social Media Channel Providers
 */
interface SocialProviderInterface
{
    /**
     * Publishes a news item to the social channel.
     *
     * @param array $account Account record containing credentials, settings
     * @param array $news News article record
     * @param array $job Outbox job record (contains attempts, container_id, etc.)
     * @param array $options Additional options (caption, shareUrl, storyImagePath, etc.)
     * @return array [
     *     'success' => bool,
     *     'status' => string ('published'|'in_progress'|'failed'),
     *     'post_id' => string|null,
     *     'post_url' => string|null,
     *     'container_id' => string|null,
     *     'error_code' => string|null,
     *     'error_message' => string|null,
     *     'is_transient' => bool,
     *     'retry_after' => int|null
     * ]
     */
    public function publish(array $account, array $news, array $job, array $options = []): array;

    /**
     * Verifies that the account credentials and tokens are valid.
     *
     * @param array $credentials Decrypted credentials array
     * @param string|null $externalAccountId
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    public function verifyConnection(array $credentials, ?string $externalAccountId = null): array;
}
