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
        $this->tema = Load::lib('AdminTemplate');
        $this->db = Load::lib('Database');
        $this->lang = Load::lang('mod_seo');
        $this->tema->lang($this->lang);
        $this->session = Load::lib('Session');
        $this->user = Load::model('mod_user.user');
    }

    public function index()
    {
        // Check list permission
        if ($this->user->hasPermission('mod_seo', 'list', $this->lang['mod_seo']['role_errors']['list'], 1) === false) {
            return false;
        }

        Run::import('management', '_index');

        // Resolve property URL
        $settingsPath = root_dir . 'data' . DS . 'seo_settings.json';
        $propertyUrl = '';
        if (file_exists($settingsPath)) {
            $settings = json_decode(file_get_contents($settingsPath), true);
            $propertyUrl = $settings['property_url'] ?? '';
        }

        if ($propertyUrl === '') {
            $parsedUrl = parse_url($this->site['address']);
            $domain = $parsedUrl['host'] ?? '';
            $propertyUrl = 'sc-domain:' . $domain;
        }

        $keyPath = root_dir . 'data' . DS . 'google_search_console_api.json';
        $hasCredentials = file_exists($keyPath);
        $gscData = [];
        $errorMessage = '';

        if ($hasCredentials) {
            try {
                $gscData = $this->getSearchConsoleData($propertyUrl);
                if ($gscData === null) {
                    $errorMessage = $this->lang['mod_seo']['admin']['labels']['credentials_missing'];
                }
            } catch (Throwable $e) {
                $errorMessage = $e->getMessage();
            }
        } else {
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
                'clicks' => $metrics ? $metrics['clicks'] : 0,
                'impressions' => $metrics ? $metrics['impressions'] : 0,
                'ctr' => $metrics ? $metrics['ctr'] : 0,
                'position' => $metrics ? $metrics['position'] : '-'
            ];
        }

        // Sort by impressions DESC, then clicks DESC
        usort($matchedUrls, function ($a, $b) {
            if ($a['impressions'] === $b['impressions']) {
                return $b['clicks'] <=> $a['clicks'];
            }
            return $b['impressions'] <=> $a['impressions'];
        });

        $this->site['title'] = $this->lang['mod_seo']['list'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('site_urls', $matchedUrls);
        $this->tema->veri('property_url', $propertyUrl);
        $this->tema->veri('has_credentials', $hasCredentials);
        $this->tema->veri('error_message', $errorMessage);
        $this->tema->tpl('mod_seo/list');
    }

    public function settings()
    {
        // Check settings permission
        if ($this->user->hasPermission('mod_seo', 'settings', $this->lang['mod_seo']['role_errors']['settings'], 1) === false) {
            return false;
        }

        Run::import('management', '_index');

        $settingsPath = root_dir . 'data' . DS . 'seo_settings.json';
        $keyPath = root_dir . 'data' . DS . 'google_search_console_api.json';

        if (Req::is_Post('save')) {
            $propertyUrl = trim((string) Req::Post_Safe('property_url'));
            
            // Save settings file
            $settings = ['property_url' => $propertyUrl];
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
                    Helper::yonlendir($this->site['saddress'] . 'seo/settings', 0);
                    return;
                }
            }

            $this->session->setFlash('success', $this->lang['mod_seo']['messages']['success']);
            Helper::yonlendir($this->site['saddress'] . 'seo/settings', 0);
            return;
        }

        $propertyUrl = '';
        if (file_exists($settingsPath)) {
            $settings = json_decode(file_get_contents($settingsPath), true);
            $propertyUrl = $settings['property_url'] ?? '';
        }

        if ($propertyUrl === '') {
            $parsedUrl = parse_url($this->site['address']);
            $domain = $parsedUrl['host'] ?? '';
            $propertyUrl = 'sc-domain:' . $domain;
        }

        $hasCredentials = file_exists($keyPath);

        $this->site['title'] = $this->lang['mod_seo']['settings'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('property_url', $propertyUrl);
        $this->tema->veri('has_credentials', $hasCredentials);
        $this->tema->tpl('mod_seo/settings');
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
            return null;
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
        curl_close($ch);

        if ($httpCode !== 200) {
            return [];
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
        return $results;
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
        $db->fetch_array("SELECT title AS name, seo, lang FROM `mod_news` WHERE status='y' AND lang='" . addslashes($langCode) . "'");
        if ($db->getSize() > 0) {
            foreach ($db->toArray() as $r) {
                $urls[] = [
                    'type' => 'News',
                    'name' => html_entity_decode($r['name'], ENT_QUOTES, 'UTF-8'),
                    'path' => $r['lang'] . '/news/' . $r['seo']
                ];
            }
        }

        // 3. Products (mod_product)
        $db->fetch_array("SELECT name, seo, lang FROM `mod_product` WHERE status='y' AND lang='" . addslashes($langCode) . "'");
        if ($db->getSize() > 0) {
            foreach ($db->toArray() as $r) {
                $urls[] = [
                    'type' => 'Product',
                    'name' => html_entity_decode($r['name'], ENT_QUOTES, 'UTF-8'),
                    'path' => $r['lang'] . '/product/' . $r['seo']
                ];
            }
        }

        // 4. Services (mod_service)
        $db->fetch_array("SELECT name, seo, lang FROM `mod_service` WHERE status='y' AND lang='" . addslashes($langCode) . "'");
        if ($db->getSize() > 0) {
            foreach ($db->toArray() as $r) {
                $urls[] = [
                    'type' => 'Service',
                    'name' => html_entity_decode($r['name'], ENT_QUOTES, 'UTF-8'),
                    'path' => $r['lang'] . '/service/' . $r['seo']
                ];
            }
        }

        // 5. References (mod_reference)
        $db->fetch_array("SELECT name, seo, lang FROM `mod_reference` WHERE status='y' AND lang='" . addslashes($langCode) . "'");
        if ($db->getSize() > 0) {
            foreach ($db->toArray() as $r) {
                $urls[] = [
                    'type' => 'Reference',
                    'name' => html_entity_decode($r['name'], ENT_QUOTES, 'UTF-8'),
                    'path' => $r['lang'] . '/reference/' . $r['seo']
                ];
            }
        }

        return $urls;
    }
}
