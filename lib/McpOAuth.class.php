<?php
/**
 * Amed Framework
 * MCP OAuth 2.1 Authorization Server
 * lib/McpOAuth.class.php
 *
 * Serves the OAuth 2.1 + PKCE authorization server required by the MCP
 * authorization specification so that clients such as ChatGPT can connect:
 *
 * - GET  /.well-known/oauth-protected-resource  (RFC 9728 protected resource metadata)
 * - GET  /.well-known/oauth-authorization-server (RFC 8414 authorization server metadata)
 * - POST /oauth/register   Dynamic client registration (RFC 7591)
 * - GET  /oauth/authorize  Authorization code flow (PKCE S256, RFC 7636)
 * - POST /oauth/authorize  Login + consent submission
 * - POST /oauth/token      Token exchange (authorization_code, refresh_token)
 *
 * Users authenticate with their regular CMS account (mod_user); tool
 * permissions follow the user's group, exactly like API-key MCP requests.
 * Access tokens are opaque random values stored as SHA-256 hashes.
 *
 * Amed Framework
 */

class McpOAuth
{
    private const CODE_TTL = 600;            // seconds
    private const ACCESS_TTL = 3600;         // seconds
    private const REFRESH_TTL = 2592000;     // seconds (30 days)
    private const SCOPE = 'mcp';
    private const CLIENT_ID_PREFIX = 'mcpo_';
    private const CODE_VERIFIER_MIN = 43;
    private const CODE_VERIFIER_MAX = 128;

    /** @var Session */
    private Session $session;

    /** @var string Canonical resource identifier, e.g. https://example.com (no trailing slash) */
    private string $resource;

    public function __construct()
    {
        $this->session = Load::lib('Session');
        $this->resource = rtrim((string) (Registry::get('site_config')['address'] ?? ''), '/');
    }

    /**
     * Self-healing table creation (SQLite + MySQL), mirroring mod_mcp
     */
    public static function ensureTables(): void
    {
        try {
            $db = Load::lib('Database');
            $pdo = method_exists($db, 'get_connection') ? $db->get_connection() : null;
            if (!$pdo instanceof \PDO) {
                return;
            }
            $driver = strtolower($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));

            $clientSql = $driver === 'sqlite'
                ? "CREATE TABLE IF NOT EXISTS `mod_mcp_oauth_client` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `client_id` VARCHAR(64) NOT NULL UNIQUE,
                    `client_name` VARCHAR(255) NOT NULL,
                    `redirect_uris` TEXT NOT NULL,
                    `status` TEXT NOT NULL DEFAULT 'y' CHECK(status IN ('y', 'n')),
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NULL
                );"
                : "CREATE TABLE IF NOT EXISTS `mod_mcp_oauth_client` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `client_id` VARCHAR(64) NOT NULL UNIQUE,
                    `client_name` VARCHAR(255) NOT NULL,
                    `redirect_uris` TEXT NOT NULL,
                    `status` ENUM('y', 'n') NOT NULL DEFAULT 'y',
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NULL,
                    INDEX `idx_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            $codeSql = $driver === 'sqlite'
                ? "CREATE TABLE IF NOT EXISTS `mod_mcp_oauth_code` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `code_hash` VARCHAR(64) NOT NULL UNIQUE,
                    `client_id` VARCHAR(64) NOT NULL,
                    `redirect_uri` TEXT NOT NULL,
                    `code_challenge` VARCHAR(64) NOT NULL,
                    `resource` TEXT NOT NULL,
                    `user_id` INTEGER NOT NULL,
                    `group_id` INTEGER NOT NULL,
                    `used_at` DATETIME NULL,
                    `expires_at` DATETIME NOT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                );"
                : "CREATE TABLE IF NOT EXISTS `mod_mcp_oauth_code` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `code_hash` VARCHAR(64) NOT NULL UNIQUE,
                    `client_id` VARCHAR(64) NOT NULL,
                    `redirect_uri` TEXT NOT NULL,
                    `code_challenge` VARCHAR(64) NOT NULL,
                    `resource` TEXT NOT NULL,
                    `user_id` INT UNSIGNED NOT NULL,
                    `group_id` INT UNSIGNED NOT NULL,
                    `used_at` DATETIME NULL,
                    `expires_at` DATETIME NOT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_client` (`client_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            $tokenSql = $driver === 'sqlite'
                ? "CREATE TABLE IF NOT EXISTS `mod_mcp_oauth_token` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `access_token_hash` VARCHAR(64) NOT NULL UNIQUE,
                    `refresh_token_hash` VARCHAR(64) NOT NULL UNIQUE,
                    `client_id` VARCHAR(64) NOT NULL,
                    `resource` TEXT NOT NULL,
                    `user_id` INTEGER NOT NULL,
                    `group_id` INTEGER NOT NULL,
                    `status` TEXT NOT NULL DEFAULT 'y' CHECK(status IN ('y', 'n')),
                    `expires_at` DATETIME NOT NULL,
                    `refresh_expires_at` DATETIME NOT NULL,
                    `last_used_at` DATETIME NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                );"
                : "CREATE TABLE IF NOT EXISTS `mod_mcp_oauth_token` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `access_token_hash` VARCHAR(64) NOT NULL UNIQUE,
                    `refresh_token_hash` VARCHAR(64) NOT NULL UNIQUE,
                    `client_id` VARCHAR(64) NOT NULL,
                    `resource` TEXT NOT NULL,
                    `user_id` INT UNSIGNED NOT NULL,
                    `group_id` INT UNSIGNED NOT NULL,
                    `status` ENUM('y', 'n') NOT NULL DEFAULT 'y',
                    `expires_at` DATETIME NOT NULL,
                    `refresh_expires_at` DATETIME NOT NULL,
                    `last_used_at` DATETIME NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_client` (`client_id`),
                    INDEX `idx_user` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            $pdo->exec($clientSql);
            $pdo->exec($codeSql);
            $pdo->exec($tokenSql);
        } catch (\Throwable $e) {
            // Self-healing must never break request handling
        }
    }

    /**
     * Diagnostic sink for the OAuth flow; error_log is unreliable on shared
     * hosts (php.ini overrides the site error_log ini), so diagnostics are
     * written directly to the site log folder with a temp-dir fallback.
     */
    public static function logDiag(string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
        foreach ([root_dir . 'log' . DS . 'oauth-debug.log', sys_get_temp_dir() . DS . 'oauth-debug.log'] as $path) {
            if (@file_put_contents($path, $line, FILE_APPEND | LOCK_EX) !== false) {
                return;
            }
        }
    }


    /**
     * Resolves the request route from the rewrite target or the request URI
     */
    private function resolveRoute(): string
    {
        $route = (string) ($_GET['route'] ?? '');
        if ($route !== '') {
            return $route;
        }

        $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        if ($path === '/.well-known/oauth-protected-resource') {
            return 'protected_resource';
        }
        if ($path === '/.well-known/oauth-authorization-server') {
            return 'authorization_server';
        }
        foreach (['authorize', 'token', 'register'] as $action) {
            if ($path === '/oauth/' . $action || preg_match('#/oauth/' . $action . '\.php$#', $path)) {
                return $action;
            }
        }

        return '';
    }

    /**
     * Handles the full HTTP request lifecycle for the OAuth endpoint
     */
    public function handle(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');

        self::ensureTables();

        switch ($this->resolveRoute()) {
            case 'protected_resource':
                $this->sendProtectedResourceMetadata();
                break;
            case 'authorization_server':
                $this->sendAuthorizationServerMetadata();
                break;
            case 'register':
                $this->handleRegister();
                break;
            case 'authorize':
                $this->handleAuthorize();
                break;
            case 'token':
                $this->handleToken();
                break;
            default:
                http_response_code(404);
                $this->sendJson(['error' => 'not_found', 'error_description' => 'Unknown OAuth endpoint.'], 404);
        }
    }

    // ------------------------------------------------------------------
    // Discovery metadata
    // ------------------------------------------------------------------

    private function sendProtectedResourceMetadata(): void
    {
        $this->sendJson([
            'resource' => $this->resource,
            'authorization_servers' => [$this->resource],
            'scopes_supported' => [self::SCOPE],
            'resource_documentation' => $this->resource . '/llms.php',
        ]);
    }

    private function sendAuthorizationServerMetadata(): void
    {
        $this->sendJson([
            'issuer' => $this->resource,
            'authorization_response_iss_parameter_supported' => true,
            'authorization_endpoint' => $this->resource . '/oauth/authorize',
            'token_endpoint' => $this->resource . '/oauth/token',
            'registration_endpoint' => $this->resource . '/oauth/register',
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => ['none'],
            'client_id_metadata_document_supported' => true,
            'scopes_supported' => [self::SCOPE],
        ]);
    }

    // ------------------------------------------------------------------
    // Dynamic client registration (RFC 7591)
    // ------------------------------------------------------------------

    private function handleRegister(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Allow: POST');
            $this->sendJson(['error' => 'invalid_request', 'error_description' => 'Registration requires HTTP POST.'], 405);
            return;
        }

        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $clientName = trim((string) ($payload['client_name'] ?? ''));
        $redirectUris = $payload['redirect_uris'] ?? null;
        if (!is_array($redirectUris)) {
            $redirectUris = $redirectUris === null || $redirectUris === '' ? [] : [trim((string) $redirectUris)];
        }

        $validatedUris = [];
        foreach ($redirectUris as $uri) {
            $uri = trim((string) $uri);
            if ($uri === '') {
                continue;
            }
            $parsed = parse_url($uri);
            if (!isset($parsed['scheme'], $parsed['host']) || isset($parsed['fragment'])) {
                $this->sendJson(['error' => 'invalid_redirect_uri', 'error_description' => 'redirect_uris entries must be absolute URLs without a fragment.'], 400);
                return;
            }
            $isLoopback = in_array($parsed['host'], ['localhost', '127.0.0.1', '::1'], true);
            if ($parsed['scheme'] === 'https' || ($parsed['scheme'] === 'http' && $isLoopback)) {
                $validatedUris[] = $uri;
                continue;
            }
            $this->sendJson(['error' => 'invalid_redirect_uri', 'error_description' => 'redirect_uris entries must use HTTPS (HTTP only for loopback hosts).'], 400);
            return;
        }

        if ($validatedUris === []) {
            $this->sendJson(['error' => 'invalid_redirect_uri', 'error_description' => 'At least one redirect_uri is required.'], 400);
            return;
        }
        if ($clientName === '' || mb_strlen($clientName) > 255) {
            $clientName = 'MCP client';
        }

        $model = $this->freshModel('mod_mcp.mod_mcp_oauth_client');
        $model->client_id = self::CLIENT_ID_PREFIX . bin2hex(random_bytes(20));
        $model->client_name = $clientName;
        $model->redirect_uris = implode("\n", $validatedUris);
        $model->status = 'y';
        $model->created_at = NOW;
        if (!$model->save(true)) {
            $this->sendJson(['error' => 'server_error', 'error_description' => 'Client could not be persisted.'], 500);
            return;
        }

        $this->sendJson([
            'client_id' => $model->client_id,
            'client_id_issued_at' => time(),
            'client_name' => $clientName,
            'redirect_uris' => $validatedUris,
            'token_endpoint_auth_method' => 'none',
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'scope' => self::SCOPE,
        ], 201);
    }

    // ------------------------------------------------------------------
    // Authorization endpoint (login + consent)
    // ------------------------------------------------------------------

    private function handleAuthorize(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['GET', 'POST'], true)) {
            header('Allow: GET, POST');
            http_response_code(405);
            $this->sendJson(['error' => 'invalid_request', 'error_description' => 'This endpoint only accepts GET and POST requests.'], 405);
            return;
        }

        if ($method === 'POST') {
            $this->handleAuthorizePost();
            return;
        }

        $params = $_GET;
        $error = $this->validateAuthorizeParams($params, $client, $redirectUri, $resource);
        if ($error !== null) {
            $this->renderErrorPage($error['error'], $error['error_description']);
            return;
        }

        $this->renderAuthorizePage($params, $client, $redirectUri, $resource);
    }

    private function handleAuthorizePost(): void
    {
        // Rebuild the validated context from the signed, self-contained hidden fields
        $params = $_POST;
        $error = $this->validateAuthorizeParams($params, $client, $redirectUri, $resource);
        if ($error !== null) {
            $this->renderErrorPage($error['error'], $error['error_description']);
            return;
        }

        $action = (string) ($_POST['oauth_action'] ?? '');

        if ($action === 'login') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $user = Load::model('mod_user.user');
            if (!$user->login($username, $password)) {
                $this->renderAuthorizePage($params, $client, $redirectUri, $resource, 'Invalid username or password.');
                return;
            }
            // Authenticated: re-render, now showing the consent form
            $this->renderAuthorizePage($params, $client, $redirectUri, $resource);
            return;
        }

        // Consent submission
        $csrf = (string) ($_POST['csrf'] ?? '');
        if (!$this->session->has('id') || $csrf === '' || $csrf !== (string) $this->session->get('oauth_csrf')) {
            $this->renderAuthorizePage($params, $client, $redirectUri, $resource, 'Your session has expired. Please try again.');
            return;
        }
        if ((string) ($_POST['decision'] ?? '') !== 'approve') {
            $this->redirectBack($redirectUri, ['error' => 'access_denied', 'error_description' => 'The resource owner denied the request.', 'state' => (string) ($params['state'] ?? '')]);
            return;
        }

        $userId = (int) $this->session->get('id');
        $groupId = (int) $this->session->get('group_id');

        // Fresh CSRF token for every rendered page
        $this->session->remove('oauth_csrf');

        $plaintextCode = bin2hex(random_bytes(32));
        $codeModel = $this->freshModel('mod_mcp.mod_mcp_oauth_code');
        $codeModel->code_hash = hash('sha256', $plaintextCode);
        $codeModel->client_id = (string) $client['client_id'];
        $codeModel->redirect_uri = $redirectUri;
        $codeModel->code_challenge = (string) $params['code_challenge'];
        $codeModel->resource = $resource;
        $codeModel->user_id = $userId;
        $codeModel->group_id = $groupId;
        $codeModel->used_at = null;
        $codeModel->expires_at = date('Y-m-d H:i:s', time() + self::CODE_TTL);
        $codeModel->created_at = NOW;
        if (!$codeModel->save(true)) {
            $this->renderErrorPage('server_error', 'Authorization code could not be issued.');
            return;
        }
        self::logDiag('authorize: code issued client=' . (string) $client['client_id'] . ' user=' . $userId . ' group=' . $groupId . ' redirect_host=' . (string) (parse_url($redirectUri, PHP_URL_HOST) ?? ''));

        self::logDiag('authorize: redirecting back to ' . (string) (parse_url($redirectUri, PHP_URL_HOST) ?? '') . ' with code, iss, state');
        $this->redirectBack($redirectUri, [
            'code' => $plaintextCode,
            'iss' => $this->resource,
            'state' => (string) ($params['state'] ?? ''),
        ]);
    }

    /**
     * Validates the shared authorize parameters; returns null on success
     *
     * @param array $params
     * @param array|null $client Set to the client row on success
     * @param string|null $redirectUri Set to the validated redirect URI on success
     * @param string|null $resource Set to the validated resource identifier on success
     * @return array|null Error payload with error/error_description keys
     */
    private function validateAuthorizeParams(array $params, ?array &$client, ?string &$redirectUri, ?string &$resource): ?array
    {
        if ((string) ($params['response_type'] ?? '') !== 'code') {
            return ['error' => 'unsupported_response_type', 'error_description' => 'Only response_type=code is supported.'];
        }

        $clientId = trim((string) ($params['client_id'] ?? ''));
        $client = $this->loadClient($clientId);
        if ($client === null) {
            self::logDiag('authorize: invalid_client client_id=' . $clientId . ' redirect_uri=' . (string) ($params['redirect_uri'] ?? ''));
            return ['error' => 'invalid_client', 'error_description' => 'Unknown or inactive client_id.'];
        }

        $redirectUri = (string) ($params['redirect_uri'] ?? '');
        if (!in_array($redirectUri, $this->clientRedirectUris($client), true)) {
            return ['error' => 'invalid_request', 'error_description' => 'redirect_uri does not match the registered redirect URIs.'];
        }

        $method = (string) ($params['code_challenge_method'] ?? '');
        $challenge = (string) ($params['code_challenge'] ?? '');
        if ($method !== 'S256') {
            return ['error' => 'invalid_request', 'error_description' => 'code_challenge_method must be S256.'];
        }
        if (!preg_match('/^[A-Za-z0-9\-_]{43}$/', $challenge)) {
            return ['error' => 'invalid_request', 'error_description' => 'code_challenge must be a 43-character base64url SHA-256 digest.'];
        }

        $scope = trim((string) ($params['scope'] ?? self::SCOPE));
        if ($scope !== '' && $scope !== self::SCOPE) {
            return ['error' => 'invalid_scope', 'error_description' => 'The requested scope is not supported.'];
        }

        $resource = trim((string) ($params['resource'] ?? ''));
        if ($resource === '') {
            $resource = $this->resource;
        }
        if (rtrim($resource, '/') !== $this->resource) {
            return ['error' => 'invalid_target', 'error_description' => 'The requested resource does not match this MCP server.'];
        }

        return null;
    }

    private function loadClient(string $clientId): ?array
    {
        if ($clientId === '') {
            return null;
        }

        // Client ID Metadata Documents (CIMD, MCP SEP-3149): the OpenAI host
        // presents its HTTPS metadata document URL as the client_id
        if (preg_match('#^https://[A-Za-z0-9.\-]+/#', $clientId)) {
            return $this->loadCimdClient($clientId);
        }

        $model = $this->freshModel('mod_mcp.mod_mcp_oauth_client');
        $model->where_client_id($clientId)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            return null;
        }
        $row = $model->toArray();
        if (!is_array($row) || ($row['status'] ?? 'n') !== 'y') {
            return null;
        }
        return $row;
    }

    /**
     * Resolves a CIMD client_id URL by fetching and validating the hosted
     * client metadata document. Hosts are restricted to chatgpt.com to avoid
     * an SSRF vector; results are cached briefly in the system temp directory.
     */
    private function loadCimdClient(string $clientId): ?array
    {
        $host = (string) (parse_url($clientId, PHP_URL_HOST) ?? '');
        if ($host !== 'chatgpt.com') {
            self::logDiag('cimd: rejected foreign client_id host=' . $host);
            return null;
        }

        $cachePath = sys_get_temp_dir() . DS . 'oauth-cimd-' . md5($clientId) . '.json';
        $document = null;
        if (is_file($cachePath) && (time() - (int) filemtime($cachePath)) < 300) {
            $document = json_decode((string) file_get_contents($cachePath), true);
        }

        if (!is_array($document)) {
            $context = stream_context_create(['http' => ['timeout' => 5, 'follow_location' => 0, 'ignore_errors' => false]]);
            $raw = @file_get_contents($clientId, false, $context);
            if ($raw === false) {
                self::logDiag('cimd: fetch failed for client_id=' . $clientId);
                return null;
            }
            $document = json_decode($raw, true);
            if (is_array($document)) {
                @file_put_contents($cachePath, $raw, LOCK_EX);
            }
        }

        if (!is_array($document) || !is_array($document['redirect_uris'] ?? null) || $document['redirect_uris'] === []) {
            self::logDiag('cimd: invalid metadata document for client_id=' . $clientId);
            return null;
        }
        if (isset($document['client_id']) && $document['client_id'] !== $clientId) {
            self::logDiag('cimd: document client_id mismatch for client_id=' . $clientId);
            return null;
        }

        return [
            'client_id' => $clientId,
            'client_name' => (string) ($document['client_name'] ?? 'MCP client'),
            'redirect_uris' => implode("\n", array_map('strval', $document['redirect_uris'])),
            'status' => 'y',
        ];
    }

    /**
     * @return string[] Registered redirect URIs
     */
    private function clientRedirectUris(array $client): array
    {
        $uris = [];
        foreach (preg_split('/\r?\n/', (string) ($client['redirect_uris'] ?? '')) as $uri) {
            $uri = trim((string) $uri);
            if ($uri !== '') {
                $uris[] = $uri;
            }
        }
        return $uris;
    }

    // ------------------------------------------------------------------
    // Token endpoint
    // ------------------------------------------------------------------

    private function handleToken(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Allow: POST');
            $this->sendJson(['error' => 'invalid_request', 'error_description' => 'The token endpoint only accepts HTTP POST.'], 405);
            return;
        }

        $grantType = (string) ($_POST['grant_type'] ?? '');
        self::logDiag('token request: grant=' . $grantType . ' fields=' . implode(',', array_keys($_POST)) . ' client_id=' . (string) ($_POST['client_id'] ?? '') . ' resource=' . (string) ($_POST['resource'] ?? ''));
        try {
            if ($grantType === 'authorization_code') {
                $response = $this->grantAuthorizationCode();
            } elseif ($grantType === 'refresh_token') {
                $response = $this->grantRefreshToken();
            } else {
                $this->sendJson(['error' => 'unsupported_grant_type', 'error_description' => 'Supported grants: authorization_code, refresh_token.'], 400);
                return;
            }
        } catch (OAuthTokenException $e) {
            self::logDiag('token rejection: ' . $e->getOAuthError() . ' :: ' . $e->getMessage());
            $this->sendJson(['error' => $e->getOAuthError(), 'error_description' => $e->getMessage()], 400);
            return;
        } catch (\Throwable $e) {
            self::logDiag('token error: ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->sendJson(['error' => 'server_error', 'error_description' => 'The token request could not be processed.'], 500);
            return;
        }

        self::logDiag('token issued: grant=' . $grantType);

        header('Cache-Control: no-store');
        header('Pragma: no-cache');
        $this->sendJson($response);
    }

    private function grantAuthorizationCode(): array
    {
        $clientId = trim((string) ($_POST['client_id'] ?? ''));
        $code = (string) ($_POST['code'] ?? '');
        $redirectUri = (string) ($_POST['redirect_uri'] ?? '');
        $codeVerifier = (string) ($_POST['code_verifier'] ?? '');

        $client = $this->loadClient($clientId);
        if ($client === null) {
            throw new OAuthTokenException('invalid_client', 'Unknown or inactive client_id.');
        }
        if ($code === '') {
            throw new OAuthTokenException('invalid_request', 'The code parameter is required.');
        }

        $model = $this->freshModel('mod_mcp.mod_mcp_oauth_code');
        $model->where_code_hash(hash('sha256', $code))->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new OAuthTokenException('invalid_grant', 'Authorization code is invalid.');
        }
        $codeRow = $model->toArray();

        if (($codeRow['used_at'] ?? '') !== null && (string) ($codeRow['used_at'] ?? '') !== '') {
            $this->revokeTokensForClient((string) $codeRow['client_id'], (string) $codeRow['resource']);
            throw new OAuthTokenException('invalid_grant', 'Authorization code has already been used.');
        }
        if (strtotime((string) $codeRow['expires_at']) < time()) {
            throw new OAuthTokenException('invalid_grant', 'Authorization code has expired.');
        }
        if ((string) $codeRow['client_id'] !== $clientId) {
            throw new OAuthTokenException('invalid_grant', 'Authorization code was issued to another client.');
        }
        if ($redirectUri !== '' && $redirectUri !== (string) $codeRow['redirect_uri']) {
            throw new OAuthTokenException('invalid_grant', 'redirect_uri does not match the authorization request.');
        }
        if (!$this->verifyPkce($codeVerifier, (string) $codeRow['code_challenge'])) {
            throw new OAuthTokenException('invalid_grant', 'PKCE code_verifier does not match the code_challenge.');
        }

        // Single-use: consume the code; a replay revokes the tokens minted from this client
        $db = Load::lib('Database');
        $db->query("UPDATE mod_mcp_oauth_code SET used_at = '" . date('Y-m-d H:i:s') . "' WHERE id = " . (int) $codeRow['id'] . " AND used_at IS NULL");

        return $this->issueTokenRow(
            (string) $codeRow['client_id'],
            (string) $codeRow['resource'],
            (int) $codeRow['user_id'],
            (int) $codeRow['group_id']
        );
    }

    private function grantRefreshToken(): array
    {
        $refreshToken = (string) ($_POST['refresh_token'] ?? '');
        $clientId = trim((string) ($_POST['client_id'] ?? ''));
        if ($refreshToken === '') {
            throw new OAuthTokenException('invalid_request', 'The refresh_token parameter is required.');
        }

        $model = $this->freshModel('mod_mcp.mod_mcp_oauth_token');
        $model->where_refresh_token_hash(hash('sha256', $refreshToken))->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new OAuthTokenException('invalid_grant', 'Refresh token is invalid.');
        }
        $row = $model->toArray();

        if (($row['status'] ?? 'n') !== 'y') {
            throw new OAuthTokenException('invalid_grant', 'Refresh token has been revoked.');
        }
        if (strtotime((string) $row['refresh_expires_at']) < time()) {
            throw new OAuthTokenException('invalid_grant', 'Refresh token has expired.');
        }
        if ($clientId !== '' && $clientId !== (string) $row['client_id']) {
            throw new OAuthTokenException('invalid_grant', 'Refresh token was issued to another client.');
        }

        // Rotation: deactivate the presented refresh token and mint a fresh pair
        $db = Load::lib('Database');
        $db->query("UPDATE mod_mcp_oauth_token SET status = 'n' WHERE id = " . (int) $row['id']);

        return $this->issueTokenRow(
            (string) $row['client_id'],
            (string) $row['resource'],
            (int) $row['user_id'],
            (int) $row['group_id']
        );
    }

    /**
     * Mints an access/refresh token pair and persists the hashed rows
     */
    private function issueTokenRow(string $clientId, string $resource, int $userId, int $groupId): array
    {
        $accessToken = bin2hex(random_bytes(32));
        $refreshToken = bin2hex(random_bytes(32));

        $model = $this->freshModel('mod_mcp.mod_mcp_oauth_token');
        $model->access_token_hash = hash('sha256', $accessToken);
        $model->refresh_token_hash = hash('sha256', $refreshToken);
        $model->client_id = $clientId;
        $model->resource = $resource;
        $model->user_id = $userId;
        $model->group_id = $groupId;
        $model->status = 'y';
        $model->expires_at = date('Y-m-d H:i:s', time() + self::ACCESS_TTL);
        $model->refresh_expires_at = date('Y-m-d H:i:s', time() + self::REFRESH_TTL);
        $model->last_used_at = null;
        $model->created_at = NOW;
        if (!$model->save(true)) {
            throw new OAuthTokenException('server_error', 'Tokens could not be persisted.');
        }

        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TTL,
            'refresh_token' => $refreshToken,
            'scope' => self::SCOPE,
        ];
    }

    /**
     * Revokes every active token of a client for one resource; used when a
     * single-use authorization code is replayed
     */
    private function revokeTokensForClient(string $clientId, string $resource): void
    {
        try {
            $db = Load::lib('Database');
            $pdo = method_exists($db, 'get_connection') ? $db->get_connection() : null;
            if ($pdo instanceof \PDO) {
                $stmt = $pdo->prepare("UPDATE mod_mcp_oauth_token SET status = 'n' WHERE client_id = ? AND resource = ? AND status = 'y'");
                $stmt->execute([$clientId, $resource]);
            }
        } catch (\Throwable $e) {
            // Revocation is best-effort
        }
    }

    // ------------------------------------------------------------------
    // PKCE (RFC 7636)
    // ------------------------------------------------------------------

    private function verifyPkce(string $verifier, string $challenge): bool
    {
        $len = strlen($verifier);
        if ($len < self::CODE_VERIFIER_MIN || $len > self::CODE_VERIFIER_MAX) {
            return false;
        }
        if (!preg_match('/^[A-Za-z0-9\-._~]+$/', $verifier)) {
            return false;
        }
        return hash_equals($challenge, $this->pkceChallenge($verifier));
    }

    /**
     * S256 challenge: BASE64URL-ENCODE(SHA256(ASCII(code_verifier)))
     */
    public static function pkceChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    // ------------------------------------------------------------------
    // Rendering helpers
    // ------------------------------------------------------------------

    protected function redirectBack(string $redirectUri, array $query): void
    {
        header('Location: ' . $this->buildRedirect($redirectUri, $query));
        http_response_code(302);
    }

    /**
     * Builds the authorization response redirect URL; overridable for CLI harnesses
     */
    protected function buildRedirect(string $redirectUri, array $query): string
    {
        $separator = str_contains($redirectUri, '?') ? '&' : '?';
        $params = [];
        foreach ($query as $key => $value) {
            if ($value !== '') {
                $params[$key] = $value;
            }
        }
        return $redirectUri . $separator . http_build_query($params);
    }

    private function renderAuthorizePage(array $params, array $client, string $redirectUri, string $resource, string $error = ''): void
    {
        $loggedIn = $this->session->has('id');
        $csrf = bin2hex(random_bytes(16));
        $this->session->set('oauth_csrf', $csrf);

        $hidden = '';
        foreach (['response_type', 'client_id', 'redirect_uri', 'scope', 'state', 'code_challenge', 'code_challenge_method', 'resource'] as $field) {
            $value = (string) ($params[$field] ?? '');
            $hidden .= '<input type="hidden" name="' . htmlspecialchars($field, ENT_QUOTES) . '" value="' . htmlspecialchars($value, ENT_QUOTES) . '">' . "\n";
        }

        $clientName = htmlspecialchars((string) ($client['client_name'] ?? 'MCP client'), ENT_QUOTES);
        $serverName = htmlspecialchars((string) (Registry::get('site_config')['site_name'] ?? $this->resource), ENT_QUOTES);
        $errorBlock = $error === '' ? '' : '<p class="oauth-error">' . htmlspecialchars($error, ENT_QUOTES) . '</p>';
        $loginOrConsent = $loggedIn ? $this->consentHtml($csrf) : $this->loginHtml();

        echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Authorize MCP access</title>
<style>
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f5f5f7;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#1d1d1f}
.oauth-card{width:100%;max-width:380px;background:#fff;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.08);padding:32px;box-sizing:border-box}
.oauth-card h1{font-size:18px;margin:0 0 8px}
.oauth-card p{font-size:14px;line-height:1.5;color:#555;margin:0 0 20px}
.oauth-error{color:#c0262f;font-size:13px;margin:-8px 0 16px}
label{display:block;font-size:13px;font-weight:600;margin:0 0 6px}
input[type=text],input[type=password]{width:100%;box-sizing:border-box;padding:10px 12px;font-size:15px;border:1px solid #d2d2d7;border-radius:8px;margin:0 0 16px}
button{width:100%;padding:11px 16px;font-size:15px;font-weight:600;border:0;border-radius:8px;cursor:pointer}
button.primary{background:#1d1d1f;color:#fff;margin-bottom:10px}
button.secondary{background:transparent;color:#1d1d1f;border:1px solid #d2d2d7}
</style>
</head>
<body>
<div class="oauth-card">
<h1>MCP authorization</h1>
' . $errorBlock . '
' . ($loggedIn
            ? '<p><strong>' . $clientName . '</strong> requests access to the MCP tools of <strong>' . $serverName . '</strong> using your CMS account. Approved requests use your group permissions until the token expires.</p>'
            : '') . '
<form method="post" action="' . htmlspecialchars($this->resource, ENT_QUOTES) . '/oauth/authorize" autocomplete="off">
' . $hidden . '
' . $loginOrConsent . '
</form>
</div>
</body>
</html>';
        $GLOBALS['oauth_html_mode'] = true;
    }

    private function loginHtml(): string
    {
        return '<input type="hidden" name="oauth_action" value="login">
<label for="oauth-username">Username</label>
<input type="text" id="oauth-username" name="username" required autofocus>
<label for="oauth-password">Password</label>
<input type="password" id="oauth-password" name="password" required>
<button type="submit" class="primary">Sign in to continue</button>';
    }

    private function consentHtml(string $csrf): string
    {
        return '<input type="hidden" name="oauth_action" value="consent">
<input type="hidden" name="csrf" value="' . htmlspecialchars($csrf, ENT_QUOTES) . '">
<button type="submit" name="decision" value="approve" class="primary">Authorize access</button>
<button type="submit" name="decision" value="deny" class="secondary">Deny</button>';
    }

    private function renderErrorPage(string $error, string $description): void
    {
        http_response_code(400);
        $GLOBALS['oauth_html_mode'] = true;
        echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>MCP authorization error</title>
<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f5f5f7;font-family:system-ui,sans-serif;color:#1d1d1f}.oauth-card{max-width:380px;background:#fff;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.08);padding:32px}h1{font-size:18px}code{background:#f0f0f2;border-radius:6px;padding:2px 6px;font-size:13px}</style>
</head>
<body>
<div class="oauth-card">
<h1>Authorization failed</h1>
<p>The MCP authorization request was rejected: <code>' . htmlspecialchars($error, ENT_QUOTES) . '</code></p>
<p>' . htmlspecialchars($description, ENT_QUOTES) . '</p>
</div>
</body>
</html>';
    }

    private function sendJson(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function freshModel(string $name): ORM
    {
        $class = Load::modelfile($name);
        return new $class();
    }
}

/**
 * Token endpoint error carrying the RFC 6749 error code
 */
class OAuthTokenException extends \Exception
{
    private string $oauthError;

    public function __construct(string $oauthError, string $description)
    {
        parent::__construct($description);
        $this->oauthError = $oauthError;
    }

    public function getOAuthError(): string
    {
        return $this->oauthError;
    }
}
