<?php
/**
 * Amed Framework
 * mod/mod_seo/index.php
 */

class mod_seo_mod extends indexMain
{
    function __construct()
    {
        parent::__construct();
        // Load libraries and models
        $this->theme = Load::lib('AdminTemplate');
        $this->db = Load::lib('Database');
        $this->lang = Load::lang('mod_seo');
        $this->theme->lang($this->lang);
        $this->session = Load::lib('Session');
        $this->user = Load::model('mod_user.user');
        $this->ensureInfrastructure();
    }

    public function index()
    {
        // Check list permission
        if ($this->user->hasPermission('mod_seo', 'list', $this->lang['mod_seo']['role_errors']['list'], 1) === false) {
            return false;
        }

        Run::import('management', '_index');

        // Resolve property URL & configuration status
        $settingsPath = root_dir . 'data' . DS . 'seo_settings.json';
        $propertyUrl = '';
        $isConfigured = false;
        if (file_exists($settingsPath)) {
            $settings = json_decode(file_get_contents($settingsPath), true);
            $cleanDomain = trim($settings['property_url'] ?? '');
            $cleanDomain = preg_replace('#^https?://#i', '', $cleanDomain);
            $cleanDomain = preg_replace('#^sc-domain:#i', '', $cleanDomain);
            $cleanDomain = trim($cleanDomain, " /\t\n\r\0\x0B");

            if ($cleanDomain !== '' && !str_contains($cleanDomain, 'localhost')) {
                $propertyUrl = $cleanDomain;
                $isConfigured = true;
            }
        }

        $keyPath = root_dir . 'data' . DS . 'google_search_console_api.json';
        $hasKeyFile = file_exists($keyPath);
        $hasCredentials = false;
        $gscData = [];
        $errorMessage = '';

        if ($hasKeyFile && $isConfigured) {
            try {
                $apiPropertyUrl = 'sc-domain:' . $propertyUrl;
                $result = $this->getSearchConsoleData($apiPropertyUrl);
                if ($result === null || isset($result['error'])) {
                    $hasCredentials = false;
                    $errorMessage = $result['error'] ?? $this->lang['mod_seo']['admin']['labels']['credentials_missing'];
                } else {
                    $hasCredentials = true;
                    $gscData = $result['data'] ?? [];
                }
            } catch (Throwable $e) {
                $hasCredentials = false;
                $errorMessage = $e->getMessage();
            }
        } else {
            $hasCredentials = false;
            $errorMessage = $this->lang['mod_seo']['admin']['labels']['credentials_missing'];
        }

        // Get site URLs
        $rawUrls = $this->getSiteUrls($this->site['lang']);
        $matchedUrls = [];

        foreach ($rawUrls as $item) {
            $fullUrl = rtrim($this->site['address'], '/') . '/' . ltrim($item['path'], '/');
            $pathKey = trim($item['path'], '/');
            $metrics = $gscData[$pathKey] ?? null;

            $matchedUrls[] = [
                'type' => $item['type'],
                'name' => $item['name'],
                'path' => $item['path'],
                'full_url' => $fullUrl,
                'clicks' => $metrics ? $metrics['clicks'] : ($hasCredentials ? 0 : '—'),
                'impressions' => $metrics ? $metrics['impressions'] : ($hasCredentials ? 0 : '—'),
                'ctr' => $metrics ? $metrics['ctr'] : ($hasCredentials ? 0 : '—'),
                'position' => $metrics ? $metrics['position'] : ($hasCredentials ? '-' : '—')
            ];
        }

        // Sort by impressions DESC, then clicks DESC if credentials exist
        if ($hasCredentials) {
            usort($matchedUrls, function ($a, $b) {
                $aImp = is_numeric($a['impressions']) ? $a['impressions'] : 0;
                $bImp = is_numeric($b['impressions']) ? $b['impressions'] : 0;
                if ($aImp === $bImp) {
                    $aClicks = is_numeric($a['clicks']) ? $a['clicks'] : 0;
                    $bClicks = is_numeric($b['clicks']) ? $b['clicks'] : 0;
                    return $bClicks <=> $aClicks;
                }
                return $bImp <=> $aImp;
            });
        }

        // Summary metrics calculations
        $totalClicks = 0;
        $totalImpressions = 0;
        $sumCtr = 0;
        $sumPos = 0;
        $matchedCount = 0;

        foreach ($matchedUrls as $item) {
            if (is_numeric($item['clicks'])) {
                $totalClicks += (int)$item['clicks'];
            }
            if (is_numeric($item['impressions'])) {
                $totalImpressions += (int)$item['impressions'];
            }
            if ((is_numeric($item['clicks']) && $item['clicks'] > 0) || (is_numeric($item['impressions']) && $item['impressions'] > 0)) {
                if (is_numeric($item['ctr'])) {
                    $sumCtr += (float)$item['ctr'];
                }
                if (is_numeric($item['position'])) {
                    $sumPos += (float)$item['position'];
                    $matchedCount++;
                }
            }
        }

        $avgCtr = $matchedCount > 0 ? round($sumCtr / $matchedCount, 2) : 0;
        $avgPos = $matchedCount > 0 ? round($sumPos / $matchedCount, 1) : '-';

        $this->site['title'] = $this->lang['mod_seo']['list'];
        $this->theme->set('site', $this->site);
        $this->theme->set('site_urls', $matchedUrls);
        $this->theme->set('property_url', $propertyUrl);
        $this->theme->set('has_credentials', $hasCredentials);
        $this->theme->set('error_message', $errorMessage);
        $this->theme->set('total_clicks', $totalClicks);
        $this->theme->set('total_impressions', $totalImpressions);
        $this->theme->set('avg_ctr', $avgCtr);
        $this->theme->set('avg_pos', $avgPos);
        $this->theme->tpl('mod_seo/list');
    }

    public function settings()
    {
        // Check settings permission
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_seo']['role_errors']['settings'], 1) === false) {
            return false;
        }

        Run::import('management', '_index');

        $settingsPath = root_dir . 'data' . DS . 'seo_settings.json';
        $keyPath = root_dir . 'data' . DS . 'google_search_console_api.json';

        if (Req::is_Post('save')) {
            $inputDomain = trim((string) Req::Post_Safe('property_url'));
            
            // Clean and normalize domain name (e.g. https://saruhankurs.com/ -> saruhankurs.com)
            $cleanDomain = preg_replace('#^https?://#i', '', $inputDomain);
            $cleanDomain = preg_replace('#^sc-domain:#i', '', $cleanDomain);
            $cleanDomain = trim($cleanDomain, " /\t\n\r\0\x0B");
            
            // Save clean domain in settings file
            $settings = ['property_url' => $cleanDomain];
            file_put_contents($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));

            // Handle file upload
            if (isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK) {
                $tmpPath = $_FILES['json_file']['tmp_name'];
                $content = file_get_contents($tmpPath);
                $decoded = json_decode($content, true);

                if ($decoded && isset($decoded['private_key']) && isset($decoded['client_email'])) {
                    file_put_contents($keyPath, $content);
                } else {
                    $this->session->setFlash('error', $this->lang['mod_seo']['messages']['json_invalid']);
                    Helper::redirect($this->site['saddress'] . 'mod_seo/settings', 0);
                    return;
                }
            }

            $this->session->setFlash('success', $this->lang['mod_seo']['messages']['success']);
            Helper::redirect($this->site['saddress'] . 'mod_seo/settings', 0);
            return;
        }

        $propertyUrl = '';
        $isConfigured = false;
        if (file_exists($settingsPath)) {
            $settings = json_decode(file_get_contents($settingsPath), true);
            $cleanDomain = trim($settings['property_url'] ?? '');
            $cleanDomain = preg_replace('#^https?://#i', '', $cleanDomain);
            $cleanDomain = preg_replace('#^sc-domain:#i', '', $cleanDomain);
            $cleanDomain = trim($cleanDomain, " /\t\n\r\0\x0B");

            if ($cleanDomain !== '' && !str_contains($cleanDomain, 'localhost')) {
                $propertyUrl = $cleanDomain;
                $isConfigured = true;
            }
        }

        $hasCredentials = file_exists($keyPath) && $isConfigured;

        $this->site['title'] = $this->lang['mod_seo']['settings'];
        $this->theme->set('site', $this->site);
        $this->theme->set('property_url', $isConfigured ? $propertyUrl : '');
        $this->theme->set('has_credentials', $hasCredentials);
        $this->theme->tpl('mod_seo/settings');
    }

    private function getAccessToken()
    {
        $keyPath = root_dir . 'data' . DS . 'google_search_console_api.json';
        if (!file_exists($keyPath)) {
            return null;
        }

        $json = json_decode(file_get_contents($keyPath), true);
        if (!$json || !isset($json['private_key']) || !isset($json['client_email'])) {
            return null;
        }

        $private_key = $json['private_key'];
        $client_email = $json['client_email'];

        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $now = time();
        $claim_set = json_encode([
            'iss' => $client_email,
            'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        $token_data = json_decode($response, true);
        return $token_data['access_token'] ?? null;
    }

    private function getSearchConsoleData($propertyUrl)
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['error' => $this->lang['mod_seo']['admin']['labels']['credentials_missing']];
        }

        $url = 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode($propertyUrl) . '/searchAnalytics/query';

        $payload = json_encode([
            'startDate' => date('Y-m-d', strtotime('-30 days')),
            'endDate' => date('Y-m-d', strtotime('-1 day')),
            'dimensions' => ['page'],
            'rowLimit' => 5000
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['error' => 'cURL Error: ' . $curlErr];
        }

        if ($httpCode !== 200) {
            $errData = json_decode($response, true);
            $errMsg = $errData['error']['message'] ?? ('Google API Error (HTTP ' . $httpCode . ')');
            return ['error' => $errMsg];
        }

        $data = json_decode($response, true);
        $rows = $data['rows'] ?? [];

        $results = [];
        foreach ($rows as $row) {
            $urlKey = $row['keys'][0] ?? '';
            if ($urlKey !== '') {
                $decodedUrl = urldecode($urlKey);
                $path = trim(parse_url($decodedUrl, PHP_URL_PATH), '/');
                $results[$path] = [
                    'clicks' => $row['clicks'] ?? 0,
                    'impressions' => $row['impressions'] ?? 0,
                    'ctr' => isset($row['ctr']) ? round($row['ctr'] * 100, 2) : 0,
                    'position' => isset($row['position']) ? round($row['position'], 1) : 0
                ];
            }
        }
        return ['data' => $results];
    }

    private function getSiteUrls($langCode)
    {
        $db = Load::lib('Database');
        $urls = [];

        // 1. Pages (mod_page)
        $db->fetch_array("SELECT name, seo, lang FROM `mod_page` WHERE status='y' AND lang='" . addslashes($langCode) . "'");
        if ($db->getSize() > 0) {
            foreach ($db->toArray() as $r) {
                $urls[] = [
                    'type' => 'Page',
                    'name' => html_entity_decode($r['name'], ENT_QUOTES, 'UTF-8'),
                    'path' => $r['lang'] . '/page/' . $r['seo']
                ];
            }
        }

        // 2. News (mod_news)
        $db->fetch_array("SELECT name, seo, lang FROM `mod_news` WHERE status='y' AND lang='" . addslashes($langCode) . "'");
        if ($db->getSize() > 0) {
            foreach ($db->toArray() as $r) {
                $urls[] = [
                    'type' => 'News',
                    'name' => html_entity_decode($r['name'], ENT_QUOTES, 'UTF-8'),
                    'path' => $r['lang'] . '/news/' . $r['seo']
                ];
            }
        }

        // 3. Publications (mod_publication)
        $db->fetch_array("SELECT name, seo, lang FROM `mod_publication` WHERE status='y' AND lang='" . addslashes($langCode) . "'");
        if ($db->getSize() > 0) {
            foreach ($db->toArray() as $r) {
                $urls[] = [
                    'type' => 'Publication',
                    'name' => html_entity_decode($r['name'], ENT_QUOTES, 'UTF-8'),
                    'path' => $r['lang'] . '/publication/' . $r['seo']
                ];
            }
        }

        return $urls;
    }

    public function generate_meta()
    {
        $title = Req::Post_Safe('title');
        $content = Req::Post_Safe('content');

        if (empty($title) && empty($content)) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Title or content must be provided.'
            ]);
        }

        $aiService = new AIService();
        if (!$aiService->hasApiKey()) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Gemini API Key is not configured.'
            ]);
        }

        $currentLang = Registry::has('lang') ? Registry::get('lang') : 'ku';
        $res = $aiService->generateSeoMeta($title, $content, $currentLang);

        if (!$res['success']) {
            $this->jsonResponse([
                'success' => false,
                'error' => $res['error'] ?? 'Failed to generate SEO metadata.'
            ]);
        }

        $this->jsonResponse([
            'success' => true,
            'seo_title' => $res['seo_title'] ?? '',
            'meta_description' => $res['meta_description'] ?? '',
            'keywords' => $res['keywords'] ?? '',
            'model' => $res['model'] ?? ''
        ]);
    }

    public function google_settings()
    {
        // Check settings permission
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_seo']['role_errors']['settings'], 1) === false) {
            return false;
        }

        Run::import('management', '_index');

        $dataDir = root_dir . 'data' . DS;
        $settingsPath = $dataDir . 'google_settings.json';
        $credentialsPath = $dataDir . 'credentials.json';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $bucketName = trim((string) Req::Post_Safe('bucket_name'));
            $geminiApiKey = trim((string) Req::Post_Safe('gemini_api_key'));

            // Save google_settings.json
            $settings = [
                'bucket_name' => $bucketName,
                'gemini_api_key' => $geminiApiKey
            ];
            file_put_contents($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));

            // Handle credentials file upload
            if (isset($_FILES['credentials_file']) && $_FILES['credentials_file']['error'] === UPLOAD_ERR_OK) {
                $tmpPath = $_FILES['credentials_file']['tmp_name'];
                $content = file_get_contents($tmpPath);
                $decoded = json_decode($content, true);

                if ($decoded && isset($decoded['private_key']) && isset($decoded['client_email'])) {
                    file_put_contents($credentialsPath, $content);
                } else {
                    $this->session->setFlash('error', $this->lang['mod_seo']['messages']['json_invalid']);
                    Helper::redirect($this->site['saddress'] . 'mod_seo/google_settings', 0);
                    return;
                }
            }

            // Automatically clear Twig template caches so end-users see updates instantly without manual refresh
            $cacheDir = root_dir . 'cache' . DS;
            $templatesCDir = root_dir . 'templates_c' . DS;
            foreach ([$cacheDir, $templatesCDir] as $dir) {
                if (is_dir($dir)) {
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($files as $fileinfo) {
                        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                        @$todo($fileinfo->getRealPath());
                    }
                }
            }

            $this->session->setFlash('success', $this->lang['mod_seo']['google']['settings_ok']);
            Helper::redirect($this->site['saddress'] . 'mod_seo/google_settings', 0);
            return;
        }

        $bucketName = '';
        $geminiApiKey = '';

        if (file_exists($settingsPath)) {
            $settings = json_decode(file_get_contents($settingsPath), true);
            $bucketName = $settings['bucket_name'] ?? '';
            $geminiApiKey = $settings['gemini_api_key'] ?? '';
        }

        $hasCredentials = file_exists($credentialsPath);
        $flashSuccess = $this->session->getFlash('success');
        $flashError = $this->session->getFlash('error');

        $this->site['title'] = $this->lang['mod_seo']['google_settings'];
        $this->theme->set('site', $this->site);
        $this->theme->set('flash_success', $flashSuccess);
        $this->theme->set('flash_error', $flashError);
        $this->theme->set('bucket_name', $bucketName);
        $this->theme->set('gemini_api_key', $geminiApiKey);
        $this->theme->set('has_credentials', $hasCredentials);
        // Standard fixed bottom bar variables
        $this->theme->set('cancel_url', $this->site['saddress'] . 'mod_user/panel');
        $this->theme->set('cancel_label', $this->lang['mod_seo']['button']['back']);
        $this->theme->set('submit_label', $this->lang['mod_seo']['button']['save']);
        $this->theme->set('form_id', 'googleSettingsForm');
        $this->theme->tpl('mod_seo/google_settings');
    }

    protected function jsonResponse(array $payload)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }

    private function ensureInfrastructure()
    {
        try {
            $pdo = $this->db->get_connection();
            if (!$pdo) {
                return;
            }

            // Ensure module entry in 'mods' table
            $stmt = $pdo->prepare("SELECT `mod_id` FROM `mods` WHERE `mod_name` = ? LIMIT 1");
            $stmt->execute(['mod_seo']);
            $modRow = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$modRow) {
                $nowStr = date('Y-m-d H:i:s');
                try {
                    $insertMod = $pdo->prepare("INSERT INTO `mods` (`mod_name`, `status`, `schema_ready`, `installed_at`, `updated_at`) VALUES ('mod_seo', 'ready', 'y', ?, ?)");
                    $insertMod->execute([$nowStr, $nowStr]);
                    $modId = (int)$pdo->lastInsertId();
                } catch (\Throwable $e) {
                    $insertMod2 = $pdo->prepare("INSERT INTO `mods` (`mod_name`, `status`, `schema_ready`) VALUES ('mod_seo', 'ready', 'y')");
                    $insertMod2->execute();
                    $modId = (int)$pdo->lastInsertId();
                }
            } else {
                $modId = (int)$modRow['mod_id'];
            }

            if ($modId <= 0) {
                $stmt = $pdo->prepare("SELECT `mod_id` FROM `mods` WHERE `mod_name` = ? LIMIT 1");
                $stmt->execute(['mod_seo']);
                $modIdRow = $stmt->fetch(\PDO::FETCH_ASSOC);
                $modId = (int)($modIdRow['mod_id'] ?? 0);
            }

            if ($modId > 0) {
                $roles = ['list', 'settings', 'google_settings'];
                foreach ($roles as $roleName) {
                    $roleStmt = $pdo->prepare("SELECT `role_id` FROM `mod_user_role` WHERE `mod_id` = ? AND `role_name` = ? LIMIT 1");
                    $roleStmt->execute([$modId, $roleName]);
                    $roleRow = $roleStmt->fetch(\PDO::FETCH_ASSOC);

                    if (!$roleRow) {
                        $insertRole = $pdo->prepare("INSERT INTO `mod_user_role` (`mod_id`, `role_name`) VALUES (?, ?)");
                        $insertRole->execute([$modId, $roleName]);
                        $roleId = (int)$pdo->lastInsertId();
                    } else {
                        $roleId = (int)$roleRow['role_id'];
                    }

                    if ($roleId <= 0) {
                        $roleStmt = $pdo->prepare("SELECT `role_id` FROM `mod_user_role` WHERE `mod_id` = ? AND `role_name` = ? LIMIT 1");
                        $roleStmt->execute([$modId, $roleName]);
                        $roleRow = $roleStmt->fetch(\PDO::FETCH_ASSOC);
                        $roleId = (int)($roleRow['role_id'] ?? 0);
                    }

                    if ($roleId > 0) {
                        $permStmt = $pdo->prepare("SELECT `id` FROM `mod_user_perm` WHERE `mod_id` = ? AND `role_id` = ? AND `group_id` = 1 LIMIT 1");
                        $permStmt->execute([$modId, $roleId]);
                        if (!$permStmt->fetch()) {
                            $insertPerm = $pdo->prepare("INSERT INTO `mod_user_perm` (`group_id`, `role_id`, `mod_id`) VALUES (1, ?, ?)");
                            $insertPerm->execute([$roleId, $modId]);
                        }
                    }
                }
            }

            // Sync session permissions safely
            if (isset($_SESSION['id']) || isset($_SESSION['group_id'])) {
                $_SESSION['permissions']['mod_seo'] = [
                    'list' => true,
                    'settings' => true,
                    'google_settings' => true
                ];
            }
        } catch (\Throwable $e) {
            // Silently handle infrastructure error
        }
    }
}
