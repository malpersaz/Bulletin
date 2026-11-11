<?php

require_once __DIR__ . '/SocialCrypto.class.php';
require_once __DIR__ . '/SocialStoryCardGenerator.class.php';
require_once __DIR__ . '/SocialProviders/SocialProviderInterface.php';
require_once __DIR__ . '/SocialProviders/XProvider.class.php';
require_once __DIR__ . '/SocialProviders/FacebookPageProvider.class.php';
require_once __DIR__ . '/SocialProviders/InstagramStoryProvider.class.php';

/**
 * Service for News Automated Social Sharing
 * Implements Transactional Outbox, queue management, idempotency checks,
 * and background dispatch to social media providers.
 */
class NewsSocialShareService
{
    /**
     * Resolves provider instance by platform name.
     *
     * @param string $platform
     * @return SocialProviderInterface|null
     */
    public static function getProvider(string $platform): ?SocialProviderInterface
    {
        switch (strtolower(trim($platform))) {
            case 'x':
            case 'twitter':
                return new XProvider();
            case 'facebook':
            case 'facebook_page':
                return new FacebookPageProvider();
            case 'instagram_story':
            case 'instagram':
                return new InstagramStoryProvider();
            default:
                return null;
        }
    }

    /**
     * Retrieves active social accounts matching platform and language scope.
     *
     * @param string|null $platform
     * @param string $lang
     * @return array
     */
    public static function getActiveAccounts(?string $platform = null, string $lang = '*'): array
    {
        $db = new Database();
        $sql = "SELECT id, platform, lang_scope, account_label, external_account_id, post_mode, auto_share_default, daily_limit, is_active, created_at, updated_at 
                FROM mod_news_social_accounts 
                WHERE is_active = 'y'";

        if (!empty($platform)) {
            $sql .= " AND platform = '" . addslashes($platform) . "'";
        }

        if ($lang !== '*') {
            $sql .= " AND (lang_scope = '*' OR lang_scope = '" . addslashes($lang) . "')";
        }

        $sql .= " ORDER BY id ASC";
        $db->fetch_array($sql);

        return ($db->getSize() > 0) ? $db->toArray() : [];
    }

    /**
     * Retrieves a single account by ID (including credentials_cipher).
     *
     * @param int $accountId
     * @return array|null
     */
    public static function getAccountById(int $accountId): ?array
    {
        $db = new Database();
        $db->fetch_array("SELECT * FROM mod_news_social_accounts WHERE id = " . intval($accountId) . " LIMIT 1");
        if ($db->getSize() > 0) {
            $res = $db->toArray();
            return $res[0];
        }
        return null;
    }

    /**
     * Saves per-article social sharing preferences to mod_news_social_prefs.
     *
     * @param int $newsId
     * @param array $channelAccountIds
     * @param string|null $customCaption
     * @return bool
     */
    public static function savePreferences(int $newsId, array $channelAccountIds, ?string $customCaption = null): bool
    {
        if ($newsId <= 0) {
            return false;
        }

        $db = new Database();
        $channelsJson = json_encode(array_values(array_unique(array_map('intval', $channelAccountIds))));
        $captionClean = !empty($customCaption) ? trim(strip_tags((string) $customCaption)) : null;

        $db->fetch_array("SELECT id FROM mod_news_social_prefs WHERE news_id = " . intval($newsId) . " LIMIT 1");
        $now = date('Y-m-d H:i:s');

        if ($db->getSize() > 0) {
            $updateSql = "UPDATE mod_news_social_prefs 
                          SET channels_json = '" . addslashes($channelsJson) . "', 
                              custom_caption = " . ($captionClean !== null ? "'" . addslashes($captionClean) . "'" : "NULL") . ", 
                              updated_at = '" . $now . "' 
                          WHERE news_id = " . intval($newsId);
            return (bool) $db->query($updateSql);
        } else {
            $insertSql = "INSERT INTO mod_news_social_prefs (news_id, channels_json, custom_caption, updated_at) 
                          VALUES (" . intval($newsId) . ", '" . addslashes($channelsJson) . "', " . 
                          ($captionClean !== null ? "'" . addslashes($captionClean) . "'" : "NULL") . ", '" . $now . "')";
            return (bool) $db->query($insertSql);
        }
    }

    /**
     * Retrieves saved sharing preferences for an article.
     *
     * @param int $newsId
     * @return array|null
     */
    public static function getPreferences(int $newsId): ?array
    {
        if ($newsId <= 0) {
            return null;
        }

        $db = new Database();
        $db->fetch_array("SELECT * FROM mod_news_social_prefs WHERE news_id = " . intval($newsId) . " LIMIT 1");
        if ($db->getSize() > 0) {
            $rows = $db->toArray();
            $pref = $rows[0];
            $pref['channels'] = json_decode($pref['channels_json'] ?? '[]', true) ?: [];
            return $pref;
        }

        return null;
    }

    /**
     * Retrieves all outbox jobs associated with a news article.
     *
     * @param int $newsId
     * @return array
     */
    public static function getJobsForNews(int $newsId): array
    {
        if ($newsId <= 0) {
            return [];
        }

        $db = new Database();
        $sql = "SELECT j.*, a.account_label, a.platform as account_platform 
                FROM mod_news_social_jobs j 
                LEFT JOIN mod_news_social_accounts a ON j.account_id = a.id 
                WHERE j.news_id = " . intval($newsId) . " 
                ORDER BY j.id ASC";
        $db->fetch_array($sql);

        return ($db->getSize() > 0) ? $db->toArray() : [];
    }

    /**
     * Enqueues pending outbox jobs for a news article.
     *
     * @param int $newsId
     * @param array $selectedAccountIds Explicit accounts selected in admin wizard/form
     * @param string|null $customCaption
     * @param bool $force
     * @return array Summary of enqueued jobs
     */
    public static function enqueueForNews(
        int $newsId,
        array $selectedAccountIds = [],
        ?string $customCaption = null,
        bool $force = false
    ): array {
        if ($newsId <= 0) {
            return ['enqueued' => 0, 'reason' => 'INVALID_ID'];
        }

        $db = new Database();
        $db->fetch_array("SELECT * FROM mod_news WHERE id = " . intval($newsId) . " LIMIT 1");
        if ($db->getSize() === 0) {
            return ['enqueued' => 0, 'reason' => 'NOT_FOUND'];
        }
        $news = $db->toArray()[0];

        // Publishing eligibility gates
        if (!$force) {
            if (($news['status'] ?? 'n') !== 'y') {
                return ['enqueued' => 0, 'reason' => 'NOT_PUBLISHED'];
            }
            if (isset($news['acl_id']) && (int) $news['acl_id'] !== 0) {
                return ['enqueued' => 0, 'reason' => 'ACL_RESTRICTED'];
            }
        }

        $newsLang = (string) ($news['lang'] ?? 'tr');

        // Resolve target accounts
        $targetAccounts = [];
        if (!empty($selectedAccountIds)) {
            $idList = implode(',', array_map('intval', $selectedAccountIds));
            $db->fetch_array("SELECT * FROM mod_news_social_accounts WHERE is_active = 'y' AND id IN ($idList)");
            if ($db->getSize() > 0) {
                $targetAccounts = $db->toArray();
            }
        } else {
            // Check saved preferences
            $savedPrefs = self::getPreferences($newsId);
            if (!empty($savedPrefs['channels'])) {
                $idList = implode(',', array_map('intval', $savedPrefs['channels']));
                $db->fetch_array("SELECT * FROM mod_news_social_accounts WHERE is_active = 'y' AND id IN ($idList)");
                if ($db->getSize() > 0) {
                    $targetAccounts = $db->toArray();
                }
            } else {
                // Default auto-share accounts matching news language
                $db->fetch_array("SELECT * FROM mod_news_social_accounts 
                                  WHERE is_active = 'y' 
                                    AND auto_share_default = 'y' 
                                    AND (lang_scope = '*' OR lang_scope = '" . addslashes($newsLang) . "')");
                if ($db->getSize() > 0) {
                    $targetAccounts = $db->toArray();
                }
            }
        }

        if (empty($targetAccounts)) {
            return ['enqueued' => 0, 'reason' => 'NO_ACCOUNTS_MATCHED'];
        }

        // Calculate scheduled_at: MAX(NOW(), created_at) + 15 seconds
        $createdAtTs = !empty($news['created_at']) ? strtotime($news['created_at']) : time();
        $scheduledTs = max(time(), $createdAtTs) + 15;
        $scheduledAt = date('Y-m-d H:i:s', $scheduledTs);
        $now = date('Y-m-d H:i:s');

        $enqueuedCount = 0;

        foreach ($targetAccounts as $account) {
            $accountId = (int) $account['id'];
            $platform = (string) $account['platform'];
            $idempotencyKey = "news:{$newsId}:acct:{$accountId}";

            // Idempotency check: see if job already exists
            $db->fetch_array("SELECT id, status FROM mod_news_social_jobs WHERE idempotency_key = '" . addslashes($idempotencyKey) . "' LIMIT 1");
            if ($db->getSize() > 0) {
                $existing = $db->toArray()[0];
                if ($existing['status'] === 'published') {
                    // Strictly idempotent: never re-post already published article
                    continue;
                }
                if ($existing['status'] === 'pending' || $existing['status'] === 'processing') {
                    // Already in flight
                    continue;
                }
                if ($existing['status'] === 'failed' || $existing['status'] === 'cancelled' || $existing['status'] === 'skipped') {
                    // Reset to pending if explicitly re-enqueued
                    $resetSql = "UPDATE mod_news_social_jobs 
                                 SET status = 'pending', 
                                     attempts = 0, 
                                     scheduled_at = '" . $scheduledAt . "', 
                                     next_retry_at = NULL, 
                                     last_error_code = NULL, 
                                     last_error_message = NULL, 
                                     updated_at = '" . $now . "' 
                                 WHERE id = " . intval($existing['id']);
                    $db->query($resetSql);
                    $enqueuedCount++;
                    continue;
                }
            }

            // Insert new pending job
            $insertSql = "INSERT INTO mod_news_social_jobs 
                          (news_id, account_id, platform, idempotency_key, status, scheduled_at, attempts, max_attempts, created_at, updated_at) 
                          VALUES 
                          (" . intval($newsId) . ", " . intval($accountId) . ", '" . addslashes($platform) . "', '" . 
                          addslashes($idempotencyKey) . "', 'pending', '" . $scheduledAt . "', 0, 4, '" . $now . "', '" . $now . "')";

            if ($db->query($insertSql)) {
                $enqueuedCount++;
            }
        }

        // Persist preferences if custom caption or accounts passed
        if (!empty($selectedAccountIds) || !empty($customCaption)) {
            self::savePreferences($newsId, $selectedAccountIds, $customCaption);
        }

        return ['enqueued' => $enqueuedCount, 'reason' => 'OK'];
    }

    /**
     * Resets a failed or cancelled job back to pending.
     *
     * @param int $jobId
     * @return bool
     */
    public static function retryJob(int $jobId): bool
    {
        if ($jobId <= 0) {
            return false;
        }

        $db = new Database();
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE mod_news_social_jobs 
                SET status = 'pending', 
                    attempts = 0, 
                    scheduled_at = '" . $now . "', 
                    next_retry_at = NULL, 
                    last_error_code = NULL, 
                    last_error_message = NULL, 
                    worker_id = NULL, 
                    updated_at = '" . $now . "' 
                WHERE id = " . intval($jobId);

        return (bool) $db->query($sql);
    }

    /**
     * Cancels pending or queued jobs for a deleted or unpublished article.
     *
     * @param int $newsId
     * @return int
     */
    public static function cancelJobsForNews(int $newsId): int
    {
        if ($newsId <= 0) {
            return 0;
        }

        $db = new Database();
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE mod_news_social_jobs 
                SET status = 'cancelled', 
                    worker_id = NULL, 
                    updated_at = '" . $now . "' 
                WHERE news_id = " . intval($newsId) . " 
                  AND status IN ('pending', 'processing')";

        $db->query($sql);
        return $db->affected_rows();
    }

    /**
     * Main background worker processing engine.
     * Fetches due jobs, claims locks atomically, dispatches to providers,
     * manages Instagram polling, and handles exponential retry backoff.
     *
     * @param int $limit
     * @param string|null $workerId
     * @return array
     */
    public static function processPendingJobs(int $limit = 5, ?string $workerId = null): array
    {
        $db = new Database();
        $wid = !empty($workerId) ? $workerId : (gethostname() . ':' . getmypid());
        $now = date('Y-m-d H:i:s');

        // 1. Reap stale locks (> 10 minutes inactive)
        $reapSql = "UPDATE mod_news_social_jobs 
                    SET status = 'pending', worker_id = NULL 
                    WHERE status = 'processing' 
                      AND (heartbeat_at IS NULL OR heartbeat_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE))";
        $db->query($reapSql);

        // 2. Fetch due pending jobs
        $fetchSql = "SELECT j.*, a.daily_limit, a.credentials_cipher, a.post_mode, a.account_label, a.external_account_id as acct_ext_id, a.is_active as acct_active 
                     FROM mod_news_social_jobs j 
                     INNER JOIN mod_news_social_accounts a ON j.account_id = a.id 
                     WHERE j.status = 'pending' 
                       AND j.scheduled_at <= '" . $now . "' 
                       AND (j.next_retry_at IS NULL OR j.next_retry_at <= '" . $now . "') 
                     ORDER BY j.scheduled_at ASC 
                     LIMIT " . intval($limit);

        $db->fetch_array($fetchSql);
        if ($db->getSize() === 0) {
            // Clean up old story cards periodically
            SocialStoryCardGenerator::cleanupOldStoryCards(72);
            return [
                'processed' => 0,
                'published' => 0,
                'in_progress' => 0,
                'failed' => 0,
                'skipped' => 0,
            ];
        }

        $jobs = $db->toArray();
        $stats = [
            'processed' => 0,
            'published' => 0,
            'in_progress' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        $siteConfig = (class_exists('Registry') && Registry::has('site_config')) ? Registry::get('site_config') : [];
        $siteAddress = rtrim($siteConfig['address'] ?? 'http://localhost/', '/');
        $siteIAddress = rtrim($siteConfig['iaddress'] ?? ($siteAddress . '/static/'), '/');

        foreach ($jobs as $job) {
            $jobId = (int) $job['id'];
            $newsId = (int) $job['news_id'];
            $accountId = (int) $job['account_id'];
            $platform = (string) $job['platform'];

            // 3. Atomically claim job
            $claimSql = "UPDATE mod_news_social_jobs 
                         SET status = 'processing', 
                             worker_id = '" . addslashes($wid) . "', 
                             heartbeat_at = '" . $now . "', 
                             updated_at = '" . $now . "' 
                         WHERE id = " . $jobId . " AND status = 'pending'";
            $db->query($claimSql);
            if ($db->affected_rows() === 0) {
                // Claimed by another worker
                continue;
            }

            $stats['processed']++;

            // 4. Validate Account Status
            if (($job['acct_active'] ?? 'n') !== 'y') {
                $db->query("UPDATE mod_news_social_jobs SET status = 'failed', last_error_code = 'ACCOUNT_INACTIVE', last_error_message = 'Target social account is disabled', worker_id = NULL, updated_at = '" . date('Y-m-d H:i:s') . "' WHERE id = " . $jobId);
                $stats['failed']++;
                continue;
            }

            // 5. Check Daily Limit
            $dailyLimit = (int) ($job['daily_limit'] ?? 50);
            $db->fetch_array("SELECT COUNT(*) as published_today FROM mod_news_social_jobs WHERE account_id = " . $accountId . " AND status = 'published' AND published_at >= CURDATE()");
            $publishedToday = ($db->getSize() > 0) ? (int) $db->toArray()[0]['published_today'] : 0;

            if ($publishedToday >= $dailyLimit) {
                // Delay job to tomorrow
                $nextDay = date('Y-m-d 06:00:00', strtotime('tomorrow'));
                $db->query("UPDATE mod_news_social_jobs SET status = 'pending', next_retry_at = '" . $nextDay . "', last_error_code = 'DAILY_LIMIT_REACHED', last_error_message = 'Account reached daily limit of $dailyLimit shares', worker_id = NULL, updated_at = '" . date('Y-m-d H:i:s') . "' WHERE id = " . $jobId);
                $stats['skipped']++;
                continue;
            }

            // 6. Fetch News Article Record
            $db->fetch_array("SELECT * FROM mod_news WHERE id = " . $newsId . " LIMIT 1");
            if ($db->getSize() === 0) {
                $db->query("UPDATE mod_news_social_jobs SET status = 'skipped', last_error_code = 'NEWS_NOT_FOUND', worker_id = NULL, updated_at = '" . date('Y-m-d H:i:s') . "' WHERE id = " . $jobId);
                $stats['skipped']++;
                continue;
            }
            $news = $db->toArray()[0];

            if (($news['status'] ?? 'n') !== 'y') {
                $db->query("UPDATE mod_news_social_jobs SET status = 'skipped', last_error_code = 'NEWS_UNPUBLISHED', worker_id = NULL, updated_at = '" . date('Y-m-d H:i:s') . "' WHERE id = " . $jobId);
                $stats['skipped']++;
                continue;
            }

            // Fetch Category Name if available
            $categoryName = '';
            if (!empty($news['category_id'])) {
                $db->fetch_array("SELECT name FROM mod_news_category WHERE id = " . intval($news['category_id']) . " LIMIT 1");
                if ($db->getSize() > 0) {
                    $categoryName = (string) $db->toArray()[0]['name'];
                }
            }

            // 7. Resolve Canonical Share URL (Wikipedia Style RFC 3986)
            $rawUrl = $siteAddress . '/' . ($news['lang'] ?? 'tr') . '/news/' . ($news['seo'] ?? '');
            $shareUrl = class_exists('Helper') && method_exists('Helper', 'sitemap_url') 
                ? Helper::sitemap_url($rawUrl) 
                : $rawUrl;

            // Fetch custom caption from preferences if present
            $pref = self::getPreferences($newsId);
            $customCaption = $pref['custom_caption'] ?? null;

            // 8. Prepare Options and Story Assets
            $options = [
                'share_url' => $shareUrl,
                'custom_caption' => $customCaption,
                'credentials' => SocialCrypto::decryptPayload($job['credentials_cipher'] ?? '') ?? [],
            ];

            // If platform is Instagram Story, ensure 1080x1920 card is rendered
            if ($platform === 'instagram_story') {
                $storyPath = SocialStoryCardGenerator::generateStoryCard($news, $categoryName);
                if (!empty($storyPath) && file_exists($storyPath)) {
                    $storyFileName = basename($storyPath);
                    $options['story_image_path'] = $storyPath;
                    $options['story_image_url'] = $siteIAddress . '/mod_news/story/' . $storyFileName;
                }
            }

            // Generate companion OpenGraph image for crawlers
            if (!empty($news['image'])) {
                SocialStoryCardGenerator::generateOpenGraphImage($news['image']);
            }

            // 9. Dispatch to Provider
            $provider = self::getProvider($platform);
            if (!$provider) {
                $db->query("UPDATE mod_news_social_jobs SET status = 'failed', last_error_code = 'UNSUPPORTED_PLATFORM', worker_id = NULL, updated_at = '" . date('Y-m-d H:i:s') . "' WHERE id = " . $jobId);
                $stats['failed']++;
                continue;
            }

            $accountData = [
                'id' => $accountId,
                'platform' => $platform,
                'external_account_id' => $job['acct_ext_id'] ?? null,
                'credentials_cipher' => $job['credentials_cipher'] ?? '',
                'post_mode' => $job['post_mode'] ?? 'link_card',
            ];

            try {
                $res = $provider->publish($accountData, $news, $job, $options);
            } catch (\Throwable $e) {
                $res = [
                    'success' => false,
                    'status' => 'failed',
                    'error_code' => 'EXCEPTION',
                    'error_message' => $e->getMessage(),
                    'is_transient' => true,
                ];
            }

            // 10. Handle Dispatch Result
            $nowTs = date('Y-m-d H:i:s');
            if (!empty($res['success']) && ($res['status'] ?? '') === 'published') {
                $postId = $res['post_id'] ?? '';
                $postUrl = $res['post_url'] ?? '';

                $updateSql = "UPDATE mod_news_social_jobs 
                              SET status = 'published', 
                                  external_post_id = '" . addslashes($postId) . "', 
                                  external_post_url = '" . addslashes($postUrl) . "', 
                                  published_at = '" . $nowTs . "', 
                                  worker_id = NULL, 
                                  last_error_code = NULL, 
                                  last_error_message = NULL, 
                                  updated_at = '" . $nowTs . "' 
                              WHERE id = " . $jobId;
                $db->query($updateSql);
                $stats['published']++;
            } elseif (($res['status'] ?? '') === 'in_progress') {
                // Instagram container polling state machine
                $containerId = $res['container_id'] ?? ($job['external_container_id'] ?? '');
                $delaySec = (int) ($res['retry_after'] ?? 5);
                $nextRetry = date('Y-m-d H:i:s', time() + $delaySec);

                $updateSql = "UPDATE mod_news_social_jobs 
                              SET status = 'pending', 
                                  external_container_id = '" . addslashes($containerId) . "', 
                                  next_retry_at = '" . $nextRetry . "', 
                                  worker_id = NULL, 
                                  updated_at = '" . $nowTs . "' 
                              WHERE id = " . $jobId;
                $db->query($updateSql);
                $stats['in_progress']++;
            } else {
                // Failure (transient or permanent)
                $attempts = (int) ($job['attempts'] ?? 0) + 1;
                $maxAttempts = (int) ($job['max_attempts'] ?? 4);
                $isTransient = (bool) ($res['is_transient'] ?? false);
                $errCode = (string) ($res['error_code'] ?? 'UNKNOWN');
                $errMsg = (string) ($res['error_message'] ?? 'Publishing failed');

                if ($isTransient && $attempts < $maxAttempts) {
                    $backoffSec = isset($res['retry_after']) ? (int) $res['retry_after'] : min(10800, pow(4, $attempts) * 60);
                    $nextRetry = date('Y-m-d H:i:s', time() + $backoffSec);

                    $updateSql = "UPDATE mod_news_social_jobs 
                                  SET status = 'pending', 
                                      attempts = " . $attempts . ", 
                                      next_retry_at = '" . $nextRetry . "', 
                                      last_error_code = '" . addslashes($errCode) . "', 
                                      last_error_message = '" . addslashes($errMsg) . "', 
                                      worker_id = NULL, 
                                      updated_at = '" . $nowTs . "' 
                                  WHERE id = " . $jobId;
                    $db->query($updateSql);
                } else {
                    $updateSql = "UPDATE mod_news_social_jobs 
                                  SET status = 'failed', 
                                      attempts = " . $attempts . ", 
                                      last_error_code = '" . addslashes($errCode) . "', 
                                      last_error_message = '" . addslashes($errMsg) . "', 
                                      worker_id = NULL, 
                                      updated_at = '" . $nowTs . "' 
                                  WHERE id = " . $jobId;
                    $db->query($updateSql);
                    $stats['failed']++;
                }
            }
        }

        // Clean up old story cards
        SocialStoryCardGenerator::cleanupOldStoryCards(72);

        return $stats;
    }
}
