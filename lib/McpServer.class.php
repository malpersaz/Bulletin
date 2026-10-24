<?php
/**
 * Amed Framework
 * MCP Server - Model Context Protocol (Streamable HTTP transport)
 * lib/McpServer.class.php
 *
 * Implements the MCP Streamable HTTP transport over JSON-RPC 2.0:
 * - POST  /mcp.php : JSON-RPC request processing (stateless)
 * - GET   /mcp.php : 405 (SSE streaming not supported)
 * - Auth          : Bearer API key (mod_mcp_api_key) or X-Api-Key header
 *
 * Amed Framework
 */

require_once(lib_dir . 'McpToolException.php');
require_once(lib_dir . 'McpCrudToolsBase.class.php');
require_once(lib_dir . 'McpOAuth.class.php');
require_once(lib_dir . 'McpNewsTools.class.php');
require_once(lib_dir . 'McpPublicationTools.class.php');
require_once(lib_dir . 'McpAnnouncementTools.class.php');
require_once(lib_dir . 'McpSettingsTools.class.php');
require_once(lib_dir . 'McpMenuTools.class.php');
require_once(lib_dir . 'McpAclTools.class.php');
require_once(lib_dir . 'McpAttachmentTools.class.php');
require_once(lib_dir . 'McpDocumentTools.class.php');
require_once(lib_dir . 'McpFaqTools.class.php');
require_once(lib_dir . 'McpGalleryTools.class.php');
require_once(lib_dir . 'McpHeadlineTools.class.php');
require_once(lib_dir . 'McpImageCategoryTools.class.php');
require_once(lib_dir . 'McpPageTools.class.php');
require_once(lib_dir . 'McpPopupTools.class.php');
require_once(lib_dir . 'McpRedirectTools.class.php');
require_once(lib_dir . 'McpSeoTools.class.php');
require_once(lib_dir . 'McpSliderTools.class.php');
require_once(lib_dir . 'McpSurveyTools.class.php');
require_once(lib_dir . 'McpVideoTools.class.php');
require_once(lib_dir . 'McpResourceManager.class.php');
require_once(lib_dir . 'McpPromptManager.class.php');

class McpServer
{
    private const SUPPORTED_PROTOCOL_VERSIONS = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25'];
    private const DEFAULT_PROTOCOL_VERSION = '2025-03-26';
    private const SERVER_NAME = 'amed-bulletin-mcp';
    private const SERVER_VERSION = '1.0.0';
    private const API_KEY_PREFIX = 'amcp_';

    public const TOOLSET_GROUPS = [
        'content' => ['news', 'page', 'publication', 'announcement', 'headline'],
        'media' => ['gallery', 'video', 'attachment', 'document', 'imagecategories'],
        'taxonomy' => ['menu', 'faq', 'survey'],
        'system' => ['settings', 'acl', 'popup', 'redirect', 'seo', 'slider'],
    ];

    private string $negotiatedProtocolVersion = self::DEFAULT_PROTOCOL_VERSION;

    private const JSON_RPC_PARSE_ERROR = -32700;
    private const JSON_RPC_INVALID_REQUEST = -32600;
    private const JSON_RPC_METHOD_NOT_FOUND = -32601;
    private const JSON_RPC_INVALID_PARAMS = -32602;
    private const JSON_RPC_INTERNAL_ERROR = -32603;

    public const DOMAIN_CLASSES = [
        'news' => McpNewsTools::class,
        'publication' => McpPublicationTools::class,
        'announcement' => McpAnnouncementTools::class,
        'settings' => McpSettingsTools::class,
        'menu' => McpMenuTools::class,
        'acl' => McpAclTools::class,
        'attachment' => McpAttachmentTools::class,
        'document' => McpDocumentTools::class,
        'faq' => McpFaqTools::class,
        'gallery' => McpGalleryTools::class,
        'headline' => McpHeadlineTools::class,
        'imagecategories' => McpImageCategoryTools::class,
        'page' => McpPageTools::class,
        'popup' => McpPopupTools::class,
        'redirect' => McpRedirectTools::class,
        'seo' => McpSeoTools::class,
        'slider' => McpSliderTools::class,
        'survey' => McpSurveyTools::class,
        'video' => McpVideoTools::class,
    ];

    /**
     * @var array<string, array> Tool registry
     */
    private array $toolRegistry = [];

    /**
     * @var array|null The authenticated API key row
     */
    private ?array $authKey = null;

    /**
     * @var string Authentication kind: 'api_key', 'oauth', 'agent_chat', or 'internal'
     */
    private string $authKind = 'internal';

    /**
     * @var int|null Identifier for the principal (API key id, OAuth token id, or user id)
     */
    private ?int $principalId = null;

    /**
     * @var int|null The active user ID
     */
    private ?int $actingUserId = null;

    /**
     * @var array<string, array<string, bool>> Group permissions: mod_name => role => true
     */
    private array $groupPermissions = [];

    /**
     * @var array<string, McpCrudToolsBase> Lazy-instantiated domain tool objects
     */
    private array $toolInstances = [];

    public function __construct()
    {
        $this->registerTools();
    }

    /**
     * Initializes tools for a logged in CMS user session
     */
    public function initForUser(int $userId, int $groupId): void
    {
        $this->authKey = [
            'id' => 0,
            'user_id' => $userId,
            'group_id' => $groupId,
            'status' => 'y',
        ];
        $this->authKind = 'agent_chat';
        $this->principalId = $userId;
        $this->actingUserId = $userId;
        $this->groupPermissions = $this->loadGroupPermissions($groupId);
        $this->initTools($userId, $this->groupPermissions);
    }

    /**
     * Resets loaded tool objects when user session is loaded
     */
    private function initTools(int $userId, array $groupPermissions): void
    {
        $this->toolInstances = [];
    }

    /**
     * Loads and returns a domain tool class on demand
     */
    private function getToolInstance(string $domain): McpCrudToolsBase
    {
        if (!isset($this->toolInstances[$domain])) {
            if (!isset(self::DOMAIN_CLASSES[$domain])) {
                throw new McpToolException("Unknown tool domain: {$domain}");
            }
            $class = self::DOMAIN_CLASSES[$domain];
            $userId = $this->actingUserId ?? (int) ($this->authKey['user_id'] ?? 0);
            $this->toolInstances[$domain] = new $class($userId, $this->groupPermissions);
        }
        return $this->toolInstances[$domain];
    }

    /**
     * Recursively sanitizes OpenAPI / JSON Schema into a format strictly valid for Gemini API
     */
    public static function sanitizeSchemaForGemini(mixed $schema): array
    {
        if (!is_array($schema)) {
            if (is_object($schema)) {
                $schema = (array) $schema;
            } else {
                return ['type' => 'object', 'properties' => new \stdClass()];
            }
        }

        $result = [];

        // 1. Type and Nullable
        if (isset($schema['type'])) {
            if (is_array($schema['type'])) {
                if (in_array('null', $schema['type'], true)) {
                    $result['nullable'] = true;
                }
                $nonNull = array_values(array_filter($schema['type'], fn($t) => $t !== 'null'));
                $result['type'] = !empty($nonNull) ? (string) $nonNull[0] : 'string';
            } else {
                $result['type'] = (string) $schema['type'];
            }
        } else {
            $result['type'] = isset($schema['properties']) ? 'object' : 'string';
        }

        // 2. Nullable flag
        if (isset($schema['nullable'])) {
            $result['nullable'] = (bool) $schema['nullable'];
        }

        // 3. Description
        if (isset($schema['description']) && is_string($schema['description'])) {
            $result['description'] = $schema['description'];
        }

        // 4. Enum (all items must be strings)
        if (isset($schema['enum']) && is_array($schema['enum'])) {
            $result['enum'] = array_values(array_map('strval', $schema['enum']));
        }

        // 5. Format
        if (isset($schema['format']) && is_string($schema['format'])) {
            $result['format'] = $schema['format'];
        }

        // 6. String & Numeric constraints
        if (isset($schema['minimum'])) {
            $result['minimum'] = (float) $schema['minimum'];
        }
        if (isset($schema['maximum'])) {
            $result['maximum'] = (float) $schema['maximum'];
        }
        if (isset($schema['minLength'])) {
            $result['minLength'] = (int) $schema['minLength'];
        }
        if (isset($schema['maxLength'])) {
            $result['maxLength'] = (int) $schema['maxLength'];
        }
        if (isset($schema['minItems'])) {
            $result['minItems'] = (int) $schema['minItems'];
        }
        if (isset($schema['maxItems'])) {
            $result['maxItems'] = (int) $schema['maxItems'];
        }
        if (isset($schema['pattern']) && is_string($schema['pattern'])) {
            $result['pattern'] = $schema['pattern'];
        }

        // 7. Check for erroneously placed 'required' inside 'properties'
        $extractedRequired = [];
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            if (isset($schema['properties']['required']) && is_array($schema['properties']['required'])) {
                $extractedRequired = $schema['properties']['required'];
                unset($schema['properties']['required']);
            }
        }

        // 8. Properties (must always serialize to a JSON object, never an empty JSON array)
        if (isset($schema['properties'])) {
            $props = is_array($schema['properties']) ? $schema['properties'] : (array) $schema['properties'];
            $cleanProps = [];
            foreach ($props as $key => $val) {
                if ($key === 'required' && is_array($val)) {
                    continue;
                }
                $cleanProps[(string) $key] = self::sanitizeSchemaForGemini($val);
            }
            $result['properties'] = empty($cleanProps) ? new \stdClass() : (object) $cleanProps;
        } elseif ($result['type'] === 'object') {
            $result['properties'] = new \stdClass();
        }

        // 9. Required list
        $requiredList = [];
        if (isset($schema['required']) && is_array($schema['required'])) {
            $requiredList = $schema['required'];
        }
        if (!empty($extractedRequired)) {
            $requiredList = array_merge($requiredList, $extractedRequired);
        }
        if (!empty($requiredList)) {
            $result['required'] = array_values(array_unique(array_map('strval', $requiredList)));
        }

        // 10. Items for Array
        if ($result['type'] === 'array') {
            if (isset($schema['items'])) {
                $result['items'] = self::sanitizeSchemaForGemini($schema['items']);
            } else {
                $result['items'] = ['type' => 'string'];
            }
        } elseif (isset($schema['items'])) {
            $result['items'] = self::sanitizeSchemaForGemini($schema['items']);
        }

        return $result;
    }

    /**
     * Converts registered tools into Gemini Function Declarations format
     * @param array|null $domainFilter Optional array of domains to include (e.g. ['menu', 'page', 'news'])
     * @param bool $readOnly When true, exposes only tools annotated with readOnlyHint === true
     */
    public function getToolsForGemini(?array $domainFilter = null, bool $readOnly = false): array
    {
        $geminiDeclarations = [];
        foreach ($this->toolRegistry as $name => $tool) {
            $domain = $tool['domain'] ?? 'news';
            if ($domainFilter !== null && !in_array($domain, $domainFilter, true)) {
                continue;
            }
            if ($readOnly && empty($tool['annotations']['readOnlyHint'])) {
                continue;
            }
            if (!$this->hasPermission($tool['module'], $tool['role'])) {
                continue;
            }

            $schema = $tool['schema'] ?? ['type' => 'object', 'properties' => (object)[]];

            $geminiDeclarations[] = [
                'name' => $name,
                'description' => $tool['description'] ?? '',
                'parameters' => self::sanitizeSchemaForGemini($schema),
            ];
        }

        return $geminiDeclarations;
    }

    public function getToolRegistry(): array
    {
        return $this->toolRegistry;
    }

    public function getToolNames(): array
    {
        return array_keys($this->toolRegistry);
    }

    public function getToolDefinition(string $name): ?array
    {
        return $this->toolRegistry[$name] ?? null;
    }

    /**
     * Directly executes an MCP tool by name and returns result array
     */
    public function executeToolDirect(string $name, array $args): array
    {
        try {
            return $this->handleToolsCall(['name' => $name, 'arguments' => $args]);
        } catch (McpToolException $e) {
            return [
                'content' => [['type' => 'text', 'text' => $e->getMessage()]],
                'isError' => true,
            ];
        } catch (\Throwable $e) {
            error_log('MCP tool execution error (' . $name . '): ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'content' => [['type' => 'text', 'text' => 'An internal error occurred while executing tool \'' . htmlspecialchars($name, ENT_QUOTES) . '\'.']],
                'isError' => true,
            ];
        }
    }

    /**
     * Handles the full HTTP request lifecycle for the MCP endpoint
     */
    public function handle(): void
    {
        if (!$this->sendCorsHeaders()) {
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, 'Origin not allowed.'),
                403
            );
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            if (!headers_sent()) {
                http_response_code(204);
            }
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            if (!headers_sent()) {
                header('Allow: POST, OPTIONS');
            }
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, 'This MCP endpoint only accepts HTTP POST requests.'),
                405
            );
            return;
        }

        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');
        if (stripos($contentType, 'application/json') === false) {
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, 'Content-Type must be application/json.'),
                415
            );
            return;
        }

        $requestedVersion = $_SERVER['HTTP_MCP_PROTOCOL_VERSION'] ?? null;
        if ($requestedVersion !== null && trim((string) $requestedVersion) !== '') {
            $requestedVersion = trim((string) $requestedVersion);
            if (!in_array($requestedVersion, self::SUPPORTED_PROTOCOL_VERSIONS, true)) {
                $this->sendJson(
                    $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, "Unsupported MCP-Protocol-Version '{$requestedVersion}'. Supported versions: " . implode(', ', self::SUPPORTED_PROTOCOL_VERSIONS)),
                    400
                );
                return;
            }
            $this->negotiatedProtocolVersion = $requestedVersion;
        }

        if (!$this->authenticate()) {
            return;
        }

        $raw = $this->readRequestBody();
        $payload = json_decode($raw, true);

        if ($payload === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_PARSE_ERROR, 'Parse error: ' . json_last_error_msg()),
                400
            );
            return;
        }

        if (is_array($payload) && array_is_list($payload)) {
            if (version_compare($this->negotiatedProtocolVersion, '2025-06-18', '>=')) {
                $this->sendJson(
                    $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, "JSON-RPC batching is not supported in MCP protocol version '{$this->negotiatedProtocolVersion}'."),
                    400
                );
                return;
            }
            $this->handleBatch($payload);
            return;
        }

        $response = $this->dispatch($payload);
        if ($response === null) {
            if (!headers_sent()) {
                http_response_code(202);
            }
            return;
        }
        $this->sendJson($response, 200);
    }

    // ------------------------------------------------------------------
    // Transport
    // ------------------------------------------------------------------

    private function handleBatch(array $requests): void
    {
        $responses = [];
        foreach ($requests as $request) {
            $response = $this->dispatch($request);
            if ($response !== null) {
                $responses[] = $response;
            }
        }

        if ($responses === []) {
            if (!headers_sent()) {
                http_response_code(202);
            }
            return;
        }
        $this->sendJson($responses, 200);
    }

    /**
     * Reads the raw request body; overridable for CLI test harnesses
     */
    protected function readRequestBody(): string
    {
        return (string) file_get_contents('php://input');
    }

    private function isOriginAllowed(?string $origin): bool
    {
        if ($origin === null || $origin === '') {
            return true; // Non-browser clients (CLI, cURL, server-to-server) do not send Origin
        }
        $parsed = parse_url($origin);
        $host = strtolower($parsed['host'] ?? '');
        if ($host === '') {
            return false;
        }
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }
        $site = Registry::get('site_config');
        $siteHost = strtolower((string) parse_url($site['address'] ?? '', PHP_URL_HOST));
        if ($siteHost !== '' && ($host === $siteHost || str_ends_with($host, '.' . $siteHost))) {
            return true;
        }
        return false;
    }

    private function sendCorsHeaders(): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        if ($origin !== null && $origin !== '') {
            if (!$this->isOriginAllowed($origin)) {
                return false;
            }
            if (!headers_sent()) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Vary: Origin');
            }
        } elseif (!headers_sent()) {
            header('Access-Control-Allow-Origin: *');
        }
        if (!headers_sent()) {
            header('Access-Control-Allow-Methods: POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Api-Key, Mcp-Session-Id, MCP-Protocol-Version, Last-Event-ID');
            header('Access-Control-Expose-Headers: Mcp-Session-Id, MCP-Protocol-Version');
        }
        return true;
    }

    private function sendJson($payload, int $statusCode): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            header('MCP-Protocol-Version: ' . $this->negotiatedProtocolVersion);
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // ------------------------------------------------------------------
    // Authentication
    // ------------------------------------------------------------------

    private function authenticate(): bool
    {
        require_once __DIR__ . '/McpAuditLogger.class.php';
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $maxAuthFailures = (int) (getenv('MCP_AUTH_FAILURE_LIMIT_PER_MINUTE') ?: 60);
        if (!McpAuditLogger::checkRateLimit('api_key', 'ip_' . $ip, $maxAuthFailures, 'auth_failed')) {
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, 'Too many failed authentication attempts. Please try again later.'),
                429
            );
            return false;
        }

        $providedKey = $this->extractProvidedKey();

        if ($providedKey === '') {
            McpOAuth::logDiag('authenticate: no Authorization/X-Api-Key header presented');
            if (!headers_sent()) {
                header('WWW-Authenticate: ' . $this->bearerChallenge());
            }
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, 'Missing API key. Provide it via the "Authorization: Bearer <key>" or "X-Api-Key" header.'),
                401
            );
            return false;
        }

        if (str_starts_with($providedKey, self::API_KEY_PREFIX)) {
            $ok = $this->authenticateApiKey($providedKey);
        } else {
            $ok = $this->authenticateAccessToken($providedKey);
        }

        if (!$ok) {
            McpAuditLogger::log([
                'caller_type' => 'api_key',
                'caller_id' => 'ip_' . $ip,
                'ip_address' => $ip,
                'tool_name' => 'auth_failed',
                'status_code' => 401,
                'is_error' => 1,
            ]);
        }

        return $ok;
    }

    /**
     * Verifies that the user exists and is active, returning the live record
     */
    private function loadLiveUser(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }
        $model = $this->freshModel('mod_user.user');
        $model->where_id($userId)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            return null;
        }
        $user = $model->toArray();
        if (!is_array($user) || ($user['status'] ?? 'n') !== 'y') {
            return null;
        }
        return $user;
    }

    /**
     * Authenticates a static admin-panel API key (amcp_...)
     */
    private function authenticateApiKey(string $providedKey): bool
    {
        $model = $this->freshModel('mod_mcp.mod_mcp_api_key');
        $model->where_api_key_hash(hash('sha256', $providedKey))->limit(1)->getResultOne(true, true);

        if ($model->getSize() === 0) {
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, 'Invalid API key.'),
                401
            );
            return false;
        }

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INTERNAL_ERROR, 'API key record could not be read.'),
                500
            );
            return false;
        }

        if (($row['status'] ?? 'n') !== 'y') {
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, 'This API key has been revoked.'),
                403
            );
            return false;
        }

        $liveUser = $this->loadLiveUser((int) $row['user_id']);
        if ($liveUser === null) {
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, 'The user account associated with this API key is inactive or no longer exists.'),
                401
            );
            return false;
        }

        $liveGroupId = (int) $liveUser['group_id'];
        $this->authKey = $row;
        $this->authKey['group_id'] = $liveGroupId;
        $this->authKind = 'api_key';
        $this->principalId = (int) $row['id'];
        $this->actingUserId = (int) $row['user_id'];
        $this->groupPermissions = $this->loadGroupPermissions($liveGroupId);
        $this->initTools($this->actingUserId, $this->groupPermissions);
        $this->touchLastUsedAt((int) $row['id']);

        return true;
    }

    /**
     * Authenticates an OAuth 2.1 access token issued by McpOAuth
     */
    private function authenticateAccessToken(string $accessToken): bool
    {
        $model = $this->freshModel('mod_mcp.mod_mcp_oauth_token');
        $model->where_access_token_hash(hash('sha256', $accessToken))->limit(1)->getResultOne(true, true);

        if ($model->getSize() === 0) {
            McpOAuth::logDiag('authenticate: token not found in mod_mcp_oauth_token (presented_hash=' . substr(hash('sha256', $accessToken), 0, 8) . ')');
            header('WWW-Authenticate: ' . $this->bearerChallenge());
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, 'Invalid access token.'),
                401
            );
            return false;
        }

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INTERNAL_ERROR, 'Access token record could not be read.'),
                500
            );
            return false;
        }

        if (($row['status'] ?? 'n') !== 'y') {
            McpOAuth::logDiag('authenticate: token revoked (status=' . (string) ($row['status'] ?? 'n') . ')');
            header('WWW-Authenticate: ' . $this->bearerChallenge());
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, 'This access token has been revoked.'),
                401
            );
            return false;
        }

        if (strtotime((string) ($row['expires_at'] ?? '')) <= time()) {
            McpOAuth::logDiag('authenticate: token expired (expires_at=' . (string) ($row['expires_at'] ?? '') . ' now=' . date('Y-m-d H:i:s') . ')');
            header('WWW-Authenticate: ' . $this->bearerChallenge());
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, 'This access token has expired.'),
                401
            );
            return false;
        }

        if (!$this->tokenMatchesResource((string) ($row['resource'] ?? ''))) {
            McpOAuth::logDiag('authenticate: token resource mismatch (resource=' . (string) ($row['resource'] ?? '') . ' canonical=' . $this->canonicalResource() . ')');
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, 'This access token was not issued for this MCP server.'),
                403
            );
            return false;
        }

        $liveUser = $this->loadLiveUser((int) $row['user_id']);
        if ($liveUser === null) {
            McpOAuth::logDiag('authenticate: user account inactive or missing (user_id=' . (int) $row['user_id'] . ')');
            header('WWW-Authenticate: ' . $this->bearerChallenge());
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, 'The user account associated with this access token is inactive or no longer exists.'),
                401
            );
            return false;
        }

        $liveGroupId = (int) $liveUser['group_id'];
        $this->authKey = $row;
        $this->authKey['group_id'] = $liveGroupId;
        $this->authKind = 'oauth';
        $this->principalId = (int) $row['id'];
        $this->actingUserId = (int) $row['user_id'];
        $this->groupPermissions = $this->loadGroupPermissions($liveGroupId);
        $this->initTools($this->actingUserId, $this->groupPermissions);
        $this->touchTokenLastUsedAt((int) $row['id']);

        return true;
    }

    /**
     * RFC 9728 bearer challenge pointing MCP clients at the protected resource metadata
     */
    private function bearerChallenge(): string
    {
        $challenge = 'Bearer realm="mcp"';
        $canonical = $this->canonicalResource();
        if ($canonical !== '') {
            $challenge .= ', resource_metadata="' . $canonical . '/.well-known/oauth-protected-resource"';
        }
        return $challenge;
    }

    private function canonicalResource(): string
    {
        return rtrim((string) (Registry::get('site_config')['address'] ?? ''), '/');
    }

    private function tokenMatchesResource(string $resource): bool
    {
        $canonical = $this->canonicalResource();
        if ($canonical === '') {
            return true;
        }
        return rtrim($resource, '/') === $canonical;
    }

    private function extractProvidedKey(): string
    {
        $authorization = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));
        if ($authorization === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            $authorization = (string) ($headers['Authorization'] ?? ($headers['authorization'] ?? ''));
        }
        if (preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches)) {
            return trim($matches[1]);
        }
        $xApiKey = (string) ($_SERVER['HTTP_X_API_KEY'] ?? ($_SERVER['REDIRECT_HTTP_X_API_KEY'] ?? ''));
        if ($xApiKey === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            $xApiKey = (string) ($headers['X-Api-Key'] ?? ($headers['x-api-key'] ?? ''));
        }
        return trim($xApiKey);
    }

    private function loadGroupPermissions(int $groupId): array
    {
        $db = Load::lib('Database');
        $db->fetch_array(
            "SELECT `mod_user_role`.role_name, `mods`.mod_name
             FROM `mod_user_perm`
             JOIN `mod_user_role` ON `mod_user_role`.role_id = `mod_user_perm`.role_id AND `mod_user_role`.mod_id = `mod_user_perm`.mod_id
             JOIN `mods` ON `mods`.mod_id = `mod_user_perm`.mod_id
             WHERE `mod_user_perm`.group_id = " . (int) $groupId
        );

        $permissions = [];
        if ($groupId === 1) {
            $permissions['*']['*'] = true;
        }

        $rows = $db->getResult()->toArray();
        if (is_array($rows)) {
            foreach ($rows as $role) {
                if (!empty($role['role_name']) && !empty($role['mod_name'])) {
                    $permissions[$role['mod_name']][$role['role_name']] = true;
                }
            }
        }

        return $permissions;
    }

    private function touchLastUsedAt(int $keyId): void
    {
        try {
            $db = Load::lib('Database');
            $db->query('UPDATE mod_mcp_api_key SET last_used_at = \'' . date('Y-m-d H:i:s') . '\' WHERE id = ' . (int) $keyId);
        } catch (\Throwable $e) {
            // last_used_at tracking must never break request handling
        }
    }

    private function touchTokenLastUsedAt(int $tokenId): void
    {
        try {
            $db = Load::lib('Database');
            $db->query('UPDATE mod_mcp_oauth_token SET last_used_at = \'' . date('Y-m-d H:i:s') . '\' WHERE id = ' . (int) $tokenId);
        } catch (\Throwable $e) {
            // last_used_at tracking must never break request handling
        }
    }

    // ------------------------------------------------------------------
    // JSON-RPC dispatch
    // ------------------------------------------------------------------

    /**
     * Executes a JSON-RPC request programmatically (for internal CLI and Agent usage)
     */
    public function executeRequest(array $request): ?array
    {
        return $this->dispatch($request);
    }

    /**
     * Dispatches a single JSON-RPC request; returns null for notifications
     */
    private function dispatch($request): ?array
    {
        if (!is_array($request) || !isset($request['jsonrpc']) || $request['jsonrpc'] !== '2.0' || !isset($request['method']) || !is_string($request['method'])) {
            return $this->errorResponse($request['id'] ?? null, self::JSON_RPC_INVALID_REQUEST, 'Invalid Request: jsonrpc, method and id fields are required.');
        }

        $id = array_key_exists('id', $request) ? $request['id'] : null;
        $isNotification = !array_key_exists('id', $request);
        $method = $request['method'];
        $params = $request['params'] ?? [];

        if (!is_array($params)) {
            if ($isNotification) {
                return null;
            }
            return $this->errorResponse($id, self::JSON_RPC_INVALID_PARAMS, 'Invalid params: params must be an object.');
        }

        try {
            switch ($method) {
                case 'initialize':
                    $result = $this->handleInitialize($params);
                    break;
                case 'ping':
                    $result = [];
                    break;
                case 'tools/list':
                    $result = $this->handleToolsList($params);
                    break;
                case 'tools/call':
                    $result = $this->handleToolsCall($params);
                    break;
                case 'resources/list':
                    $result = $this->handleResourcesList();
                    break;
                case 'resources/templates/list':
                    $result = $this->handleResourceTemplatesList();
                    break;
                case 'resources/read':
                    $result = $this->handleResourcesRead($params);
                    break;
                case 'prompts/list':
                    $result = $this->handlePromptsList();
                    break;
                case 'prompts/get':
                    $result = $this->handlePromptsGet($params);
                    break;
                default:
                    if ($isNotification) {
                        return null;
                    }
                    return $this->errorResponse($id, self::JSON_RPC_METHOD_NOT_FOUND, "Method not found: {$method}");
            }
        } catch (McpToolException $e) {
            if ($method === 'tools/call') {
                $result = [
                    'content' => [['type' => 'text', 'text' => $e->getMessage()]],
                    'isError' => true,
                ];
            } else {
                return $this->errorResponse($id, self::JSON_RPC_INVALID_PARAMS, $e->getMessage());
            }
        } catch (\Throwable $e) {
            error_log('MCP Server error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            if ($isNotification) {
                return null;
            }
            return $this->errorResponse($id, self::JSON_RPC_INTERNAL_ERROR, 'Internal error.');
        }

        if ($isNotification) {
            return null;
        }

        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function handleResourcesList(): array
    {
        $manager = new McpResourceManager($this->authKey, $this->groupPermissions);
        return $manager->listResources();
    }

    private function handleResourceTemplatesList(): array
    {
        $manager = new McpResourceManager($this->authKey, $this->groupPermissions);
        return $manager->listResourceTemplates();
    }

    private function handleResourcesRead(array $params): array
    {
        $manager = new McpResourceManager($this->authKey, $this->groupPermissions);
        return $manager->readResource($params);
    }

    private function handlePromptsList(): array
    {
        $manager = new McpPromptManager();
        return $manager->listPrompts();
    }

    private function handlePromptsGet(array $params): array
    {
        $manager = new McpPromptManager();
        return $manager->getPrompt($params);
    }

    private function buildServerInstructions(): string
    {
        $domainCount = count(self::DOMAIN_CLASSES);
        $domainNames = implode(', ', array_keys(self::DOMAIN_CLASSES));
        return "This server manages {$domainCount} CMS domains ({$domainNames}). "
            . "Use the corresponding domain tools for full CRUD and operational lifecycle actions. "
            . "All operations are strictly validated and checked against role-based access control (RBAC). "
            . "Always adhere to domain constraints and obtain user confirmation before executing destructive operations.";
    }

    private function handleInitialize(array $params): array
    {
        $requested = (string) ($params['protocolVersion'] ?? '');
        $protocolVersion = in_array($requested, self::SUPPORTED_PROTOCOL_VERSIONS, true) ? $requested : self::DEFAULT_PROTOCOL_VERSION;
        $this->negotiatedProtocolVersion = $protocolVersion;

        return [
            'protocolVersion' => $protocolVersion,
            'capabilities' => [
                'tools' => ['listChanged' => false],
                'resources' => ['subscribe' => false, 'listChanged' => false],
                'prompts' => ['listChanged' => false],
            ],
            'serverInfo' => [
                'name' => self::SERVER_NAME,
                'version' => self::SERVER_VERSION,
            ],
            'instructions' => $this->buildServerInstructions(),
        ];
    }

    private function handleToolsList(array $params = []): array
    {
        $toolsetParam = $params['toolsets'] ?? ($_GET['toolsets'] ?? null);
        $allowedDomains = null;
        if (!empty($toolsetParam)) {
            $requestedSets = is_array($toolsetParam) ? $toolsetParam : explode(',', (string) $toolsetParam);
            $allowedDomains = [];
            foreach ($requestedSets as $set) {
                $set = trim(strtolower((string) $set));
                if (isset(self::TOOLSET_GROUPS[$set])) {
                    foreach (self::TOOLSET_GROUPS[$set] as $dom) {
                        $allowedDomains[$dom] = true;
                    }
                } elseif (isset(self::DOMAIN_CLASSES[$set])) {
                    $allowedDomains[$set] = true;
                }
            }
        }

        $tools = [];
        foreach ($this->toolRegistry as $name => $tool) {
            $domain = $tool['domain'] ?? 'news';
            if ($allowedDomains !== null && !isset($allowedDomains[$domain])) {
                continue;
            }
            if (!$this->hasPermission($tool['module'], $tool['role'])) {
                continue;
            }
            $schema = $tool['schema'] ?? ['type' => 'object', 'properties' => (object) []];
            if (is_array($schema) && ($schema['properties'] ?? null) === []) {
                // JSON Schema requires properties to be an object; an empty
                // PHP array would serialize as [] and strict clients reject it
                $schema['properties'] = (object) [];
            }
            $item = [
                'name' => $name,
                'description' => $tool['description'],
                'inputSchema' => $schema,
            ];
            if (!empty($tool['title'])) {
                $item['title'] = $tool['title'];
            }
            if (!empty($tool['annotations'])) {
                $item['annotations'] = $tool['annotations'];
            }
            $tools[] = $item;
        }

        $totalCount = count($tools);
        $hasCursor = array_key_exists('cursor', $params);
        $hasLimit = array_key_exists('limit', $params);

        if ($hasCursor || $hasLimit) {
            $limit = isset($params['limit']) ? max(1, min(200, (int) $params['limit'])) : 50;
            $offset = 0;
            if (!empty($params['cursor'])) {
                $decoded = base64_decode((string) $params['cursor'], true);
                if ($decoded !== false && is_numeric($decoded)) {
                    $offset = (int) $decoded;
                } elseif (is_numeric($params['cursor'])) {
                    $offset = (int) $params['cursor'];
                }
            }
            $pagedTools = array_slice($tools, $offset, $limit);
            $result = ['tools' => $pagedTools];
            if ($offset + $limit < $totalCount) {
                $result['nextCursor'] = base64_encode((string) ($offset + $limit));
            }
            return $result;
        }

        return ['tools' => $tools];
    }

    private function handleToolsCall(array $params): array
    {
        $name = (string) ($params['name'] ?? '');
        if ($name === '') {
            throw new McpToolException('Missing tool name.');
        }

        if (!isset($this->toolRegistry[$name])) {
            throw new McpToolException("Unknown tool: {$name}");
        }

        $tool = $this->toolRegistry[$name];
        if (!$this->hasPermission($tool['module'], $tool['role'])) {
            throw new McpToolException("The API key's permission group does not have the '{$tool['module']}:{$tool['role']}' role required for this operation.");
        }

        $arguments = $params['arguments'] ?? [];
        if (!is_array($arguments)) {
            throw new McpToolException('Tool arguments must be an object.');
        }

        $callerType = $this->authKind;
        $callerId = match ($this->authKind) {
            'api_key' => 'key_' . ($this->principalId ?? 0),
            'oauth' => 'token_' . ($this->principalId ?? 0),
            'agent_chat' => 'user_' . ($this->actingUserId ?? 0),
            default => 'system',
        };

        require_once __DIR__ . '/McpAuditLogger.class.php';
        $maxPerMinute = (int) (getenv('MCP_RATE_LIMIT_PER_MINUTE') ?: 300);
        if ($callerType !== 'internal' && !McpAuditLogger::checkRateLimit($callerType, $callerId, $maxPerMinute)) {
            throw new McpToolException("Rate limit exceeded for {$callerType}. Maximum {$maxPerMinute} requests per minute allowed.");
        }

        $startTime = microtime(true);
        $isError = false;
        try {
            $domain = $tool['domain'] ?? 'news';
            $domainTools = $this->getToolInstance($domain);
            $payload = $domainTools->{$tool['handler']}($arguments);

            return [
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]],
                'structuredContent' => $payload,
                'isError' => false,
            ];
        } catch (\Throwable $e) {
            $isError = true;
            throw $e;
        } finally {
            $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);
            McpAuditLogger::log([
                'caller_type' => $callerType,
                'caller_id' => $callerId,
                'user_id' => $this->actingUserId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'tool_name' => $name,
                'parameters' => $arguments,
                'execution_time_ms' => $elapsedMs,
                'tokens_used' => 0,
                'status_code' => $isError ? 400 : 200,
                'is_error' => $isError ? 1 : 0,
            ]);
        }
    }

    private function hasPermission(string $module, string $role): bool
    {
        if (isset($this->authKey['group_id']) && (int) $this->authKey['group_id'] === 1) {
            return true;
        }
        if (isset($this->groupPermissions['*']['*']) && $this->groupPermissions['*']['*']) {
            return true;
        }
        return isset($this->groupPermissions[$module][$role]);
    }

    // ------------------------------------------------------------------
    // Response helpers
    // ------------------------------------------------------------------

    private function errorResponse($id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }

    private function freshModel(string $name): ORM
    {
        $class = Load::modelfile($name);
        return new $class();
    }

    // ------------------------------------------------------------------
    // Tool registry
    // ------------------------------------------------------------------

    private function registerTools(): void
    {
        $this->toolRegistry = [];
        foreach (self::DOMAIN_CLASSES as $domain => $class) {
            if (method_exists($class, 'definitions')) {
                $tools = $class::definitions();
                foreach ($tools as $name => $def) {
                    if (!isset($def['domain'])) {
                        $def['domain'] = $domain;
                    }
                    $this->toolRegistry[$name] = $def;
                }
            }
        }
    }
}
