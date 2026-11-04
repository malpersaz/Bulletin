<?php

/**
 * Cron Manager Helper for Amed Framework
 * Provides secure, authenticated execution of background cron feeds and data updates.
 */
class cron_manager_helper extends indexBase
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Checks if current request is authorized to execute cron routines.
     * Supports HTTP Basic Auth, X-Cron-Token header, query token, active admin session, and CLI.
     *
     * @return bool
     */
    private function isAuthorized(): bool
    {
        if (PHP_SAPI === 'cli') {
            return true;
        }

        $expectedUser = (string) ($this->site['cron_user'] ?? 'amed_cron');
        $expectedToken = (string) ($this->site['cron_token'] ?? '');

        // 1. Authenticated super administrator session check (works even if cron_token is not yet configured)
        if ($this->session && $this->session->has('id')) {
            $userModel = Load::model('mod_user.user');
            if ($userModel && method_exists($userModel, 'checklogin') && $userModel->checklogin()) {
                $groupId = (int) $this->session->get('group_id');
                if ($groupId === 1 || Role::hasPermission('mod_settings', 'edit') || Role::hasPermission('mod_user', 'acl')) {
                    return true;
                }
            }
        }

        if ($expectedToken === '' || $expectedToken === 'CHANGE_ME_HIGH_ENTROPY_CRON_TOKEN') {
            return false;
        }

        // 2. HTTP Basic Authentication check (supports Apache FastCGI Authorization headers)
        $providedUser = $_SERVER['PHP_AUTH_USER'] ?? null;
        $providedPw = $_SERVER['PHP_AUTH_PW'] ?? null;

        if ($providedUser === null && $providedPw === null) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
            if (str_starts_with($authHeader, 'Basic ')) {
                $decoded = base64_decode(substr($authHeader, 6), true);
                if ($decoded && str_contains($decoded, ':')) {
                    [$providedUser, $providedPw] = explode(':', $decoded, 2);
                }
            }
        }

        if ($providedUser !== null && $providedPw !== null) {
            if (hash_equals($expectedUser, (string) $providedUser) && hash_equals($expectedToken, (string) $providedPw)) {
                return true;
            }
        }

        // 3. Custom header token check (X-Cron-Token)
        $headerToken = $_SERVER['HTTP_X_CRON_TOKEN'] ?? null;
        if ($headerToken !== null && hash_equals($expectedToken, (string) $headerToken)) {
            return true;
        }

        // 4. Query string token check (?token=...)
        $queryToken = $_GET['token'] ?? null;
        if ($queryToken !== null && hash_equals($expectedToken, (string) $queryToken)) {
            return true;
        }

        return false;
    }

    /**
     * Logs unauthorized cron access attempts to logs/cron_security.log without exposing internals.
     *
     * @return void
     */
    private function logUnauthorizedAttempt(): void
    {
        $logDir = defined('root_dir') ? root_dir . 'logs' : dirname(__DIR__, 3) . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $ip = (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown')));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/cron');
        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 120);
        $line = sprintf("[%s] 403 Unauthorized cron attempt | IP: %s | URI: %s | UA: %s\n", date('Y-m-d H:i:s'), $ip, $uri, $ua);

        @file_put_contents($logDir . '/cron_security.log', $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Resolves target from route parameter or query string.
     * Returns null when an unsupported target is requested.
     *
     * @param string|null $rawTarget
     * @return string|null
     */
    private function resolveTarget(?string $rawTarget): ?string
    {
        $target = $rawTarget !== null ? strtolower(trim((string) $rawTarget)) : '';
        if ($target === '') {
            $target = strtolower(trim((string) ($_GET['job'] ?? ($_GET['target'] ?? 'all'))));
        }

        if ($target === 'us_elections' || $target === 'us-elections') {
            $target = 'elections';
        }

        if (in_array($target, ['market', 'sports', 'elections', 'all'], true)) {
            return $target;
        }

        return null;
    }

    /**
     * Main action to render and execute cron requests.
     *
     * @param string|null $targetParam
     * @return void
     */
    public function render(?string $targetParam = null): void
    {
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');

        if (!$this->isAuthorized()) {
            $this->logUnauthorizedAttempt();
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized cron request. Valid credentials or token required.',
                'timestamp' => date('c'),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $target = $this->resolveTarget($targetParam);
        if ($target === null) {
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid cron target. Allowed targets: market, sports, elections, all.',
                'timestamp' => date('c'),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $forceSports = isset($_GET['force']) && ($_GET['force'] === '1' || $_GET['force'] === 'true');
        $forceElections = isset($_GET['force']) && ($_GET['force'] === '1' || $_GET['force'] === 'true');
        $startTime = microtime(true);
        $tasks = [];

        require_once lib_dir . 'MarketDataService.class.php';
        require_once lib_dir . 'SportsDataService.class.php';
        require_once lib_dir . 'UsElectionDataService.class.php';

        if ($target === 'market' || $target === 'all') {
            try {
                $marketRes = MarketDataService::refreshRates(true);
                $tasks['market'] = [
                    'status' => 'success',
                    'items_count' => count($marketRes['items'] ?? []),
                    'updated_iso' => $marketRes['updated_iso'] ?? date('c'),
                ];
            } catch (\Throwable $e) {
                $tasks['market'] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        if ($target === 'sports' || $target === 'all') {
            try {
                // Uses adaptive 5m (live match) / 15m (idle) TTL by default unless ?force=1 is passed
                $sportsRes = SportsDataService::refreshSuperLeagueData($forceSports);
                $tasks['sports'] = [
                    'status' => 'success',
                    'mode' => $forceSports ? 'forced' : 'adaptive',
                    'matchday' => $sportsRes['matchday'] ?? null,
                    'standings_count' => count($sportsRes['standings'] ?? []),
                    'matches_count' => count($sportsRes['matches'] ?? []),
                    'updated_iso' => $sportsRes['updated_iso'] ?? date('c'),
                ];
            } catch (\Throwable $e) {
                $tasks['sports'] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        if ($target === 'elections' || $target === 'all') {
            try {
                $electionsRes = UsElectionDataService::refreshData($forceElections);
                $tasks['elections'] = [
                    'status' => 'success',
                    'mode' => $forceElections ? 'forced' : 'cached_or_fresh',
                    'senate_dem' => $electionsRes['senate']['democrats'] ?? null,
                    'senate_rep' => $electionsRes['senate']['republicans'] ?? null,
                    'house_dem' => $electionsRes['house']['democrats'] ?? null,
                    'house_rep' => $electionsRes['house']['republicans'] ?? null,
                    'updated_at' => $electionsRes['updated_at'] ?? time(),
                ];
            } catch (\Throwable $e) {
                $tasks['elections'] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);

        echo json_encode([
            'status' => 'ok',
            'target' => $target,
            'duration_ms' => $durationMs,
            'tasks' => $tasks,
            'timestamp' => date('c'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
