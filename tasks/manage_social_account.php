<?php
/**
 * tasks/manage_social_account.php - CLI Management Tool for Social Media Accounts
 *
 * Allows adding, listing, testing, and toggling social media publishing accounts
 * with automatic encryption via SocialCrypto.
 *
 * Usage:
 *   php tasks/manage_social_account.php --list
 *   php tasks/manage_social_account.php --add --platform=x --label="Amed Bülten" --handle="amedbulten" \
 *       --consumer-key=KEY --consumer-secret=SECRET --access-token=TOKEN --access-token-secret=TOKEN_SECRET
 *   php tasks/manage_social_account.php --test --id=<account_id>
 *   php tasks/manage_social_account.php --toggle --id=<account_id>
 *   php tasks/manage_social_account.php --delete --id=<account_id>
 *
 * Amed Framework — Bulletin CMS
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'amed-social-account-manager';

require dirname(__DIR__) . '/init.php';
require_once lib_dir . 'SocialCrypto.class.php';
require_once lib_dir . 'NewsSocialShareService.class.php';

$options = getopt('', [
    'list',
    'add',
    'test',
    'toggle',
    'delete',
    'id:',
    'platform:',
    'label:',
    'handle:',
    'lang:',
    'post-mode:',
    'daily-limit:',
    'auto-share:',
    'active:',
    // X (Twitter) credentials
    'consumer-key:',
    'consumer-secret:',
    'access-token:',
    'access-token-secret:',
    // Meta / Facebook Page credentials
    'page-id:',
    'page-access-token:',
    // Instagram Story credentials
    'ig-user-id:',
    'help',
]);

if (isset($options['help']) || empty($options)) {
    echo "====================================================\n";
    echo " Amed Social Media Account Manager (CLI)\n";
    echo "====================================================\n";
    echo "Usage:\n";
    echo "  --list                     List all registered social media accounts\n";
    echo "  --test --id=<id>           Test API connection for an account\n";
    echo "  --toggle --id=<id>         Toggle account active state (y/n)\n";
    echo "  --delete --id=<id>         Delete an account and its pending jobs\n";
    echo "\nAdd Account Example (X / Twitter):\n";
    echo "  php tasks/manage_social_account.php --add \\\n";
    echo "      --platform=x \\\n";
    echo "      --label=\"Amed Bülten (@amedbulten)\" \\\n";
    echo "      --handle=\"amedbulten\" \\\n";
    echo "      --consumer-key=\"YOUR_API_KEY\" \\\n";
    echo "      --consumer-secret=\"YOUR_API_SECRET\" \\\n";
    echo "      --access-token=\"YOUR_ACCESS_TOKEN\" \\\n";
    echo "      --access-token-secret=\"YOUR_ACCESS_TOKEN_SECRET\"\n";
    echo "\nAdd Account Example (Facebook Page):\n";
    echo "  php tasks/manage_social_account.php --add \\\n";
    echo "      --platform=facebook \\\n";
    echo "      --label=\"Amed Bülten Facebook\" \\\n";
    echo "      --page-id=\"YOUR_PAGE_ID\" \\\n";
    echo "      --page-access-token=\"YOUR_SYSTEM_USER_ACCESS_TOKEN\"\n";
    echo "\nAdd Account Example (Instagram Stories):\n";
    echo "  php tasks/manage_social_account.php --add \\\n";
    echo "      --platform=instagram_story \\\n";
    echo "      --label=\"Amed Bülten Instagram\" \\\n";
    echo "      --ig-user-id=\"YOUR_IG_USER_ID\" \\\n";
    echo "      --page-access-token=\"YOUR_SYSTEM_USER_ACCESS_TOKEN\"\n";
    echo "====================================================\n";
    exit(0);
}

$db = new Database();

// 1. LIST ACCOUNTS
if (isset($options['list'])) {
    $db->fetch_array("SELECT id, platform, lang_scope, account_label, external_account_id, post_mode, auto_share_default, daily_limit, is_active, created_at FROM mod_news_social_accounts ORDER BY id ASC");
    $accounts = ($db->getSize() > 0) ? $db->toArray() : [];

    echo "====================================================\n";
    echo " Registered Social Media Accounts (" . count($accounts) . " total)\n";
    echo "====================================================\n";

    if (empty($accounts)) {
        echo "No accounts registered yet.\n";
        echo "Use --add to register a new account.\n";
        exit(0);
    }

    foreach ($accounts as $acc) {
        $statusStr = ($acc['is_active'] === 'y') ? "\033[32mACTIVE\033[0m" : "\033[31mDISABLED\033[0m";
        $autoStr = ($acc['auto_share_default'] === 'y') ? 'YES' : 'NO';
        echo sprintf(
            "[%d] %s (%s) | Handle/ID: %s | Scope: %s | Status: %s | Auto-Share: %s | Limit: %d/day\n",
            $acc['id'],
            $acc['account_label'],
            strtoupper($acc['platform']),
            $acc['external_account_id'] ?: 'N/A',
            $acc['lang_scope'],
            $statusStr,
            $autoStr,
            $acc['daily_limit']
        );
    }
    echo "====================================================\n";
    exit(0);
}

// 2. TOGGLE ACCOUNT STATUS
if (isset($options['toggle'])) {
    $id = (int) ($options['id'] ?? 0);
    if ($id <= 0) {
        fwrite(STDERR, "Error: Missing or invalid --id parameter.\n");
        exit(1);
    }

    $db->fetch_array("SELECT id, is_active, account_label FROM mod_news_social_accounts WHERE id = {$id} LIMIT 1");
    if ($db->getSize() === 0) {
        fwrite(STDERR, "Error: Account #{$id} not found.\n");
        exit(1);
    }
    $acc = $db->toArray()[0];
    $newStatus = ($acc['is_active'] === 'y') ? 'n' : 'y';
    $now = date('Y-m-d H:i:s');
    $db->query("UPDATE mod_news_social_accounts SET is_active = '{$newStatus}', updated_at = '{$now}' WHERE id = {$id}");

    echo sprintf("Account #%d (%s) status changed to: %s\n", $id, $acc['account_label'], ($newStatus === 'y' ? 'ACTIVE' : 'DISABLED'));
    exit(0);
}

// 3. DELETE ACCOUNT
if (isset($options['delete'])) {
    $id = (int) ($options['id'] ?? 0);
    if ($id <= 0) {
        fwrite(STDERR, "Error: Missing or invalid --id parameter.\n");
        exit(1);
    }

    $db->fetch_array("SELECT id, account_label FROM mod_news_social_accounts WHERE id = {$id} LIMIT 1");
    if ($db->getSize() === 0) {
        fwrite(STDERR, "Error: Account #{$id} not found.\n");
        exit(1);
    }
    $acc = $db->toArray()[0];
    $db->query("DELETE FROM mod_news_social_accounts WHERE id = {$id}");
    $db->query("DELETE FROM mod_news_social_jobs WHERE account_id = {$id}");

    echo sprintf("Account #%d (%s) and its jobs deleted successfully.\n", $id, $acc['account_label']);
    exit(0);
}

// 4. TEST CONNECTION
if (isset($options['test'])) {
    $id = (int) ($options['id'] ?? 0);
    if ($id <= 0) {
        fwrite(STDERR, "Error: Missing or invalid --id parameter.\n");
        exit(1);
    }

    $account = NewsSocialShareService::getAccountById($id);
    if (!$account) {
        fwrite(STDERR, "Error: Account #{$id} not found.\n");
        exit(1);
    }

    $provider = NewsSocialShareService::getProvider($account['platform']);
    if (!$provider) {
        fwrite(STDERR, "Error: Unsupported provider platform '{$account['platform']}'.\n");
        exit(1);
    }

    $credentials = SocialCrypto::decryptPayload($account['credentials_cipher'] ?? '') ?? [];
    echo "Testing connection to {$account['platform']} for Account #{$id} ({$account['account_label']})...\n";

    $result = $provider->verifyConnection($credentials, $account['external_account_id']);
    if (!empty($result['success'])) {
        echo "\033[32m[SUCCESS]\033[0m Connection verified successfully!\n";
        echo "Response Data: " . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    } else {
        echo "\033[31m[FAILED]\033[0m Connection verification failed.\n";
        echo "Error: " . ($result['message'] ?? 'Unknown error') . "\n";
        echo "Details: " . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
    exit(0);
}

// 5. ADD OR UPDATE ACCOUNT
if (isset($options['add'])) {
    $platform = strtolower(trim((string) ($options['platform'] ?? '')));
    $validPlatforms = ['x', 'facebook', 'instagram_story'];
    if (!in_array($platform, $validPlatforms, true)) {
        fwrite(STDERR, "Error: Invalid or missing --platform. Allowed: " . implode(', ', $validPlatforms) . "\n");
        exit(1);
    }

    $label = trim((string) ($options['label'] ?? ''));
    if ($label === '') {
        $label = ($platform === 'x') ? 'X (@' . ($options['handle'] ?? 'account') . ')' : ucfirst($platform);
    }

    $langScope = trim((string) ($options['lang'] ?? '*'));
    $postMode = trim((string) ($options['post-mode'] ?? ($platform === 'x' ? 'link_card' : 'native_media')));
    $autoShare = (isset($options['auto-share']) && strtolower($options['auto-share']) === 'n') ? 'n' : 'y';
    $dailyLimit = isset($options['daily-limit']) ? max(1, (int) $options['daily-limit']) : 50;
    $isActive = (isset($options['active']) && strtolower($options['active']) === 'n') ? 'n' : 'y';

    $credentials = [];
    $externalId = null;

    if ($platform === 'x') {
        $externalId = trim((string) ($options['handle'] ?? ''));
        $consumerKey = trim((string) ($options['consumer-key'] ?? ''));
        $consumerSecret = trim((string) ($options['consumer-secret'] ?? ''));
        $accessToken = trim((string) ($options['access-token'] ?? ''));
        $accessTokenSecret = trim((string) ($options['access-token-secret'] ?? ''));

        if ($consumerKey === '' || $consumerSecret === '' || $accessToken === '' || $accessTokenSecret === '') {
            fwrite(STDERR, "Error: X requires --consumer-key, --consumer-secret, --access-token, and --access-token-secret.\n");
            exit(1);
        }

        $credentials = [
            'consumer_key' => $consumerKey,
            'consumer_secret' => $consumerSecret,
            'access_token' => $accessToken,
            'access_token_secret' => $accessTokenSecret,
        ];
    } elseif ($platform === 'facebook') {
        $pageId = trim((string) ($options['page-id'] ?? ''));
        $pageAccessToken = trim((string) ($options['page-access-token'] ?? ''));

        if ($pageId === '' || $pageAccessToken === '') {
            fwrite(STDERR, "Error: Facebook requires --page-id and --page-access-token.\n");
            exit(1);
        }

        $externalId = $pageId;
        $credentials = [
            'page_id' => $pageId,
            'page_access_token' => $pageAccessToken,
        ];
    } elseif ($platform === 'instagram_story') {
        $igUserId = trim((string) ($options['ig-user-id'] ?? ''));
        $pageAccessToken = trim((string) ($options['page-access-token'] ?? ''));

        if ($igUserId === '' || $pageAccessToken === '') {
            fwrite(STDERR, "Error: Instagram Stories requires --ig-user-id and --page-access-token.\n");
            exit(1);
        }

        $externalId = $igUserId;
        $credentials = [
            'ig_user_id' => $igUserId,
            'page_access_token' => $pageAccessToken,
        ];
    }

    $cipher = SocialCrypto::encryptPayload($credentials);
    if (!$cipher) {
        fwrite(STDERR, "Error: Failed to encrypt credentials. Check social_secret_key in config/site.config.php.\n");
        exit(1);
    }

    $now = date('Y-m-d H:i:s');
    $safePlatform = addslashes($platform);
    $safeLang = addslashes($langScope);
    $safeLabel = addslashes($label);
    $safeExtId = $externalId !== null ? "'" . addslashes($externalId) . "'" : "NULL";
    $safeCipher = addslashes($cipher);
    $safePostMode = addslashes($postMode);
    $safeAutoShare = addslashes($autoShare);
    $safeActive = addslashes($isActive);

    $sql = "INSERT INTO mod_news_social_accounts 
            (platform, lang_scope, account_label, external_account_id, credentials_cipher, post_mode, auto_share_default, daily_limit, is_active, created_at, updated_at)
            VALUES 
            ('{$safePlatform}', '{$safeLang}', '{$safeLabel}', {$safeExtId}, '{$safeCipher}', '{$safePostMode}', '{$safeAutoShare}', {$dailyLimit}, '{$safeActive}', '{$now}', '{$now}')";

    $db->query($sql);
    $newId = (int) $db->insert_id();

    echo "====================================================\n";
    echo "\033[32m[SUCCESS]\033[0m Social account #{$newId} registered successfully!\n";
    echo "Platform: {$platform}\n";
    echo "Label: {$label}\n";
    echo "Handle / External ID: " . ($externalId ?: 'N/A') . "\n";
    echo "Status: " . ($isActive === 'y' ? 'ACTIVE' : 'DISABLED') . "\n";
    echo "====================================================\n";

    // Immediate test connection
    echo "Running automatic connection verification test...\n";
    $provider = NewsSocialShareService::getProvider($platform);
    if ($provider) {
        $testResult = $provider->verifyConnection($credentials, $externalId);
        if (!empty($testResult['success'])) {
            echo "\033[32m[VERIFIED]\033[0m Account connection is active and valid!\n";
            if (!empty($testResult['account_name'])) {
                echo "Connected Account Name: " . $testResult['account_name'] . "\n";
            }
        } else {
            echo "\033[33m[WARNING]\033[0m Account was saved, but API test failed:\n";
            echo ($testResult['message'] ?? 'Could not verify credentials with ' . $platform) . "\n";
        }
    }
    echo "====================================================\n";
    exit(0);
}
