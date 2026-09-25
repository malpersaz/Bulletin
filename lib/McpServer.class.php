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
require_once(lib_dir . 'McpContentTools.class.php');
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

class McpServer
{
    private const SUPPORTED_PROTOCOL_VERSIONS = ['2024-11-05', '2025-03-26', '2025-06-18'];
    private const DEFAULT_PROTOCOL_VERSION = '2025-03-26';
    private const SERVER_NAME = 'amed-rojname-mcp';
    private const SERVER_VERSION = '1.0.0';
    private const API_KEY_PREFIX = 'amcp_';

    private const JSON_RPC_PARSE_ERROR = -32700;
    private const JSON_RPC_INVALID_REQUEST = -32600;
    private const JSON_RPC_METHOD_NOT_FOUND = -32601;
    private const JSON_RPC_INVALID_PARAMS = -32602;
    private const JSON_RPC_INTERNAL_ERROR = -32603;

    private const SERVER_INSTRUCTIONS = 'This server manages the Rojname CMS content domain. Use tools to create, read, update, and delete news articles, publications, contents, pages, galleries, videos, and settings. Content and news summaries must be between 90 and 120 characters. Categories must match the language of their parent categories. All operations are permission-checked against the API key\'s group.';

    /**
     * @var array<string, array{module: string, role: string, handler: string}> Tool registry
     */
    private array $toolRegistry = [];

    /**
     * @var array|null The authenticated API key row
     */
    private ?array $authKey = null;

    /**
     * @var array<string, array<string, bool>> Group permissions: mod_name => role => true
     */
    private array $groupPermissions = [];

    private McpNewsTools $newsTools;

    private McpContentTools $tools;

    private McpPublicationTools $publicationTools;

    private McpAnnouncementTools $announcementTools;

    private McpSettingsTools $settingsTools;

    private McpMenuTools $menuTools;

    private McpAclTools $aclTools;
    private McpAttachmentTools $attachmentTools;
    private McpDocumentTools $documentTools;
    private McpFaqTools $faqTools;
    private McpGalleryTools $galleryTools;
    private McpHeadlineTools $headlineTools;
    private McpImageCategoryTools $imagecategoryTools;
    private McpPageTools $pageTools;
    private McpPopupTools $popupTools;
    private McpRedirectTools $redirectTools;
    private McpSeoTools $seoTools;
    private McpSliderTools $sliderTools;
    private McpSurveyTools $surveyTools;
    private McpVideoTools $videoTools;
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
        $this->groupPermissions = $this->loadGroupPermissions($groupId);
        $this->initTools($userId, $this->groupPermissions);
    }

    /**
     * Instantiates the domain tool objects with the effective user and group permissions
     */
    private function initTools(int $userId, array $groupPermissions): void
    {
        $this->newsTools = new McpNewsTools($userId, $groupPermissions);
        $this->tools = new McpContentTools($userId, $groupPermissions);
        $this->publicationTools = new McpPublicationTools($userId, $groupPermissions);
        $this->announcementTools = new McpAnnouncementTools($userId, $groupPermissions);
        $this->settingsTools = new McpSettingsTools($userId, $groupPermissions);
        $this->menuTools = new McpMenuTools($userId, $groupPermissions);
        $this->aclTools = new McpAclTools($userId, $groupPermissions);
        $this->attachmentTools = new McpAttachmentTools($userId, $groupPermissions);
        $this->documentTools = new McpDocumentTools($userId, $groupPermissions);
        $this->faqTools = new McpFaqTools($userId, $groupPermissions);
        $this->galleryTools = new McpGalleryTools($userId, $groupPermissions);
        $this->headlineTools = new McpHeadlineTools($userId, $groupPermissions);
        $this->imagecategoryTools = new McpImageCategoryTools($userId, $groupPermissions);
        $this->pageTools = new McpPageTools($userId, $groupPermissions);
        $this->popupTools = new McpPopupTools($userId, $groupPermissions);
        $this->redirectTools = new McpRedirectTools($userId, $groupPermissions);
        $this->seoTools = new McpSeoTools($userId, $groupPermissions);
        $this->sliderTools = new McpSliderTools($userId, $groupPermissions);
        $this->surveyTools = new McpSurveyTools($userId, $groupPermissions);
        $this->videoTools = new McpVideoTools($userId, $groupPermissions);
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
     * @param array|null $domainFilter Optional array of domains to include (e.g. ['menu', 'page', 'content'])
     */
    public function getToolsForGemini(?array $domainFilter = null): array
    {
        $geminiDeclarations = [];
        foreach ($this->toolRegistry as $name => $tool) {
            $domain = $tool['domain'] ?? 'content';
            if ($domainFilter !== null && !in_array($domain, $domainFilter, true)) {
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
            return [
                'content' => [['type' => 'text', 'text' => 'Tool execution error: ' . $e->getMessage()]],
                'isError' => true,
            ];
        }
    }

    /**
     * Handles the full HTTP request lifecycle for the MCP endpoint
     */
    public function handle(): void
    {
        $this->sendCorsHeaders();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST, OPTIONS');
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
            $this->handleBatch($payload);
            return;
        }

        $response = $this->dispatch($payload);
        if ($response === null) {
            http_response_code(202);
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
            http_response_code(202);
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

    private function sendCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Api-Key, Mcp-Session-Id, MCP-Protocol-Version, Last-Event-ID');
        header('Access-Control-Expose-Headers: Mcp-Session-Id');
    }

    private function sendJson($payload, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // ------------------------------------------------------------------
    // Authentication
    // ------------------------------------------------------------------

    private function authenticate(): bool
    {
        $providedKey = $this->extractProvidedKey();

        if ($providedKey === '') {
            McpOAuth::logDiag('authenticate: no Authorization/X-Api-Key header presented');
            header('WWW-Authenticate: ' . $this->bearerChallenge());
            $this->sendJson(
                $this->errorResponse(null, self::JSON_RPC_INVALID_REQUEST, 'Missing API key. Provide it via the "Authorization: Bearer <key>" or "X-Api-Key" header.'),
                401
            );
            return false;
        }

        if (str_starts_with($providedKey, self::API_KEY_PREFIX)) {
            return $this->authenticateApiKey($providedKey);
        }

        return $this->authenticateAccessToken($providedKey);
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

        $this->authKey = $row;
        $this->groupPermissions = $this->loadGroupPermissions((int) $row['group_id']);
        $this->initTools((int) $row['user_id'], $this->groupPermissions);
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


        $this->authKey = $row;
        $this->groupPermissions = $this->loadGroupPermissions((int) $row['group_id']);
        $this->initTools((int) $row['user_id'], $this->groupPermissions);
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
        $authorization = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches)) {
            return trim($matches[1]);
        }
        return trim((string) ($_SERVER['HTTP_X_API_KEY'] ?? ''));
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
                    $result = $this->handleToolsList();
                    break;
                case 'tools/call':
                    $result = $this->handleToolsCall($params);
                    break;
                default:
                    if ($isNotification) {
                        return null;
                    }
                    return $this->errorResponse($id, self::JSON_RPC_METHOD_NOT_FOUND, "Method not found: {$method}");
            }
        } catch (McpToolException $e) {
            // Tool-level errors travel in-band as isError results
            $result = [
                'content' => [['type' => 'text', 'text' => $e->getMessage()]],
                'isError' => true,
            ];
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

    private function handleInitialize(array $params): array
    {
        $requested = (string) ($params['protocolVersion'] ?? '');
        $protocolVersion = in_array($requested, self::SUPPORTED_PROTOCOL_VERSIONS, true) ? $requested : self::DEFAULT_PROTOCOL_VERSION;

        return [
            'protocolVersion' => $protocolVersion,
            'capabilities' => [
                'tools' => ['listChanged' => false],
            ],
            'serverInfo' => [
                'name' => self::SERVER_NAME,
                'version' => self::SERVER_VERSION,
            ],
            'instructions' => self::SERVER_INSTRUCTIONS,
        ];
    }

    private function handleToolsList(): array
    {
        $tools = [];
        foreach ($this->toolRegistry as $name => $tool) {
            if (!$this->hasPermission($tool['module'], $tool['role'])) {
                continue;
            }
            $schema = $tool['schema'] ?? ['type' => 'object', 'properties' => (object) []];
            if (is_array($schema) && ($schema['properties'] ?? null) === []) {
                // JSON Schema requires properties to be an object; an empty
                // PHP array would serialize as [] and strict clients reject it
                $schema['properties'] = (object) [];
            }
            $tools[] = [
                'name' => $name,
                'description' => $tool['description'],
                'inputSchema' => $schema,
            ];
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

        $domain = $tool['domain'] ?? 'content';
        $domainTools = match ($domain) {
            'news' => $this->newsTools,
            'publication' => $this->publicationTools,
            'announcement' => $this->announcementTools,
            'settings' => $this->settingsTools,
            'menu' => $this->menuTools,
            'acl' => $this->aclTools,
            'attachment' => $this->attachmentTools,
            'document' => $this->documentTools,
            'faq' => $this->faqTools,
            'gallery' => $this->galleryTools,
            'headline' => $this->headlineTools,
            'imagecategories' => $this->imagecategoryTools,
            'page' => $this->pageTools,
            'popup' => $this->popupTools,
            'redirect' => $this->redirectTools,
            'seo' => $this->seoTools,
            'slider' => $this->sliderTools,
            'survey' => $this->surveyTools,
            'video' => $this->videoTools,
            default => $this->tools,
        };

        $payload = $domainTools->{$tool['handler']}($arguments);

        return [
            'content' => [[
                'type' => 'text',
                'text' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]],
            'isError' => false,
        ];
    }

    private function hasPermission(string $module, string $role): bool
    {
        if (isset($this->authKey['group_id']) && (int) $this->authKey['group_id'] === 1) {
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
        $this->toolRegistry = [
            'list_news' => [
                'module' => 'mod_news',
                'role' => 'list',
                'handler' => 'listNews',
                'domain' => 'news',
                'description' => 'List news articles with optional filters. Returns paginated items (id, lang, name, category_id, status, deck, slider, seo, created_at, updated_at) plus the total count.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'category_id' => ['type' => 'integer', 'description' => 'Filter by category ID.'],
                        'lang' => ['type' => 'string', 'description' => 'Filter by language code (e.g. ku, en, tr).'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by publish status: y (published) or n (draft).'],
                        'search' => ['type' => 'string', 'description' => 'Search term matched against news article names.'],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10, 'description' => 'Page size (1-50, default 10).'],
                        'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Number of items to skip for pagination.'],
                    ],
                ],
            ],
            'get_news' => [
                'module' => 'mod_news',
                'role' => 'list',
                'handler' => 'getNews',
                'domain' => 'news',
                'description' => 'Get a single news record with all fields, including body content, summary, and SEO metadata.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'News record ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],
            'create_news' => [
                'module' => 'mod_news',
                'role' => 'add',
                'handler' => 'createNews',
                'domain' => 'news',
                'description' => 'Create a news article. The summary must be between 90 and 120 characters (deck summary SEO standard). The category must exist and match the news language. The seo slug must be unique per language.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'News title/headline (1-255 characters).'],
                        'lang' => ['type' => 'string', 'description' => 'Language code of an active site language (e.g. ku, en, tr).'],
                        'category_id' => ['type' => 'integer', 'description' => 'ID of an existing news category with the same language.'],
                        'content' => ['type' => 'string', 'description' => 'Full HTML body of the news article.'],
                        'summary' => ['type' => 'string', 'minLength' => 90, 'maxLength' => 120, 'description' => 'Deck summary (exactly 90-120 characters).'],
                        'seo' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Unique SEO slug. Automatically generated if omitted.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Meta title.'],
                        'keywords' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Meta keywords.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Meta description.'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'y', 'description' => 'Publish status.'],
                        'deck' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Deck feature flag.'],
                        'slider' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Slider feature flag.'],
                        'created_at' => ['type' => 'string', 'description' => 'Publication datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.'],
                        'gallery_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Attached gallery ID.'],
                        'file_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Attached file/attachment ID.'],
                        'acl_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Access control level ID.'],
                        'video' => ['type' => 'string', 'description' => 'Comma-separated video keys.'],
                        'image' => [
                            'type' => 'object',
                            'nullable' => true,
                            'description' => 'Image upload object or null to clear.',
                            'properties' => [
                                'data_base64' => ['type' => 'string', 'description' => 'Base64 encoded image binary data.'],
                                'mime' => ['type' => 'string', 'description' => 'Image MIME type (e.g. image/jpeg, image/png, image/webp).'],
                            ],
                            'required' => ['data_base64', 'mime'],
                        ],
                    ],
                    'required' => ['name', 'lang', 'category_id', 'content', 'summary'],
                ],
            ],
            'update_news' => [
                'module' => 'mod_news',
                'role' => 'edit',
                'handler' => 'updateNews',
                'domain' => 'news',
                'description' => 'Partially update an existing news article by ID.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'News record ID.'],
                        'name' => ['type' => 'string', 'maxLength' => 255],
                        'lang' => ['type' => 'string'],
                        'category_id' => ['type' => 'integer'],
                        'content' => ['type' => 'string'],
                        'summary' => ['type' => 'string', 'minLength' => 90, 'maxLength' => 120],
                        'seo' => ['type' => 'string', 'maxLength' => 255],
                        'title' => ['type' => 'string', 'maxLength' => 255],
                        'keywords' => ['type' => 'string', 'maxLength' => 255],
                        'description' => ['type' => 'string', 'maxLength' => 255],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n']],
                        'deck' => ['type' => 'string', 'enum' => ['y', 'n']],
                        'slider' => ['type' => 'string', 'enum' => ['y', 'n']],
                        'created_at' => ['type' => 'string'],
                        'gallery_id' => ['type' => 'integer'],
                        'file_id' => ['type' => 'integer'],
                        'acl_id' => ['type' => 'integer'],
                        'video' => ['type' => 'string'],
                        'image' => [
                            'type' => 'object',
                            'nullable' => true,
                            'properties' => [
                                'data_base64' => ['type' => 'string'],
                                'mime' => ['type' => 'string'],
                            ],
                            'required' => ['data_base64', 'mime'],
                        ],
                    ],
                    'required' => ['id'],
                ],
            ],
            'delete_news' => [
                'module' => 'mod_news',
                'role' => 'delete',
                'handler' => 'deleteNews',
                'domain' => 'news',
                'description' => 'Delete a news article by ID, removing associated images and bridge relations.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'News record ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],
            'list_news_categories' => [
                'module' => 'mod_news',
                'role' => 'list',
                'handler' => 'listNewsCategories',
                'domain' => 'news',
                'description' => 'List news categories with optional filters.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => ['type' => 'string', 'description' => 'Filter by language code.'],
                        'root' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter root categories.'],
                        'search' => ['type' => 'string', 'description' => 'Search category name.'],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20],
                        'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0],
                    ],
                ],
            ],
            'get_news_category' => [
                'module' => 'mod_news',
                'role' => 'list',
                'handler' => 'getNewsCategory',
                'domain' => 'news',
                'description' => 'Get a single news category record with all fields.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Category ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],
            'create_news_category' => [
                'module' => 'mod_news',
                'role' => 'add',
                'handler' => 'createNewsCategory',
                'domain' => 'news',
                'description' => 'Create a news category. Name (2-255 chars) and lang are required.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 255],
                        'lang' => ['type' => 'string'],
                        'parent_id' => ['type' => 'integer', 'default' => 0],
                        'root' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n'],
                        'seo' => ['type' => 'string', 'maxLength' => 255],
                        'title' => ['type' => 'string', 'maxLength' => 255],
                        'keywords' => ['type' => 'string', 'maxLength' => 255],
                        'description' => ['type' => 'string', 'maxLength' => 255],
                        'acl_id' => ['type' => 'integer', 'default' => 0],
                        'image' => [
                            'type' => 'object',
                            'nullable' => true,
                            'properties' => [
                                'data_base64' => ['type' => 'string'],
                                'mime' => ['type' => 'string'],
                            ],
                            'required' => ['data_base64', 'mime'],
                        ],
                    ],
                    'required' => ['name', 'lang'],
                ],
            ],
            'update_news_category' => [
                'module' => 'mod_news',
                'role' => 'edit',
                'handler' => 'updateNewsCategory',
                'domain' => 'news',
                'description' => 'Partially update an existing news category by ID.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 255],
                        'lang' => ['type' => 'string'],
                        'parent_id' => ['type' => 'integer'],
                        'root' => ['type' => 'string', 'enum' => ['y', 'n']],
                        'seo' => ['type' => 'string', 'maxLength' => 255],
                        'title' => ['type' => 'string', 'maxLength' => 255],
                        'keywords' => ['type' => 'string', 'maxLength' => 255],
                        'description' => ['type' => 'string', 'maxLength' => 255],
                        'acl_id' => ['type' => 'integer'],
                        'order' => ['type' => 'integer'],
                        'image' => [
                            'type' => 'object',
                            'nullable' => true,
                            'properties' => [
                                'data_base64' => ['type' => 'string'],
                                'mime' => ['type' => 'string'],
                            ],
                            'required' => ['data_base64', 'mime'],
                        ],
                    ],
                    'required' => ['id'],
                ],
            ],
            'delete_news_category' => [
                'module' => 'mod_news',
                'role' => 'delete',
                'handler' => 'deleteNewsCategory',
                'domain' => 'news',
                'description' => 'Recursively delete a news category tree by ID.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'list_contents' => [
                'module' => 'mod_content',
                'role' => 'list',
                'handler' => 'listContents',
                'description' => 'List contents with optional filters. Returns paginated items (id, lang, name, category_id, status, deck, slider, seo, created_at, updated_at) plus the total count.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'category_id' => ['type' => 'integer', 'description' => 'Filter by category ID.'],
                        'lang' => ['type' => 'string', 'description' => 'Filter by language code (e.g. ku, en, tr).'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by publish status: y (published) or n (draft).'],
                        'search' => ['type' => 'string', 'description' => 'Search term matched against content names.'],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10, 'description' => 'Page size (1-50, default 10).'],
                        'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Number of items to skip for pagination.'],
                    ],
                ],
            ],

            'get_content' => [
                'module' => 'mod_content',
                'role' => 'list',
                'handler' => 'getContent',
                'description' => 'Get a single content record with all fields, including the full body content, summary, and SEO metadata.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Content record ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'create_content' => [
                'module' => 'mod_content',
                'role' => 'add',
                'handler' => 'createContent',
                'description' => 'Create a content record. The summary must be between 90 and 120 characters (deck summary SEO standard). The category must exist and match the content language. The seo slug must be unique per language; it defaults to a slug generated from the name.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Content name (1-255 characters).'],
                        'lang' => ['type' => 'string', 'description' => 'Language code of an active site language (e.g. ku, en, tr).'],
                        'category_id' => ['type' => 'integer', 'description' => 'ID of an existing content category with the same language.'],
                        'content' => ['type' => 'string', 'description' => 'Full HTML body of the content.'],
                        'summary' => ['type' => 'string', 'minLength' => 90, 'maxLength' => 120, 'description' => 'Deck summary (exactly 90-120 characters).'],
                        'seo' => ['type' => 'string', 'maxLength' => 255, 'description' => 'URL slug. Defaults to a slug generated from the name. Must be unique per language.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO title. Defaults to the name.'],
                        'keywords' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO keywords. Defaults to empty.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO description. Defaults to the summary.'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'y', 'description' => 'Publish status: y (published) or n (draft).'],
                        'deck' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Show on deck: y or n.'],
                        'slider' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Show in hero slider: y or n.'],
                        'created_at' => ['type' => 'string', 'description' => 'Creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.'],
                        'video' => ['type' => 'string', 'description' => 'Comma-separated video references (video:ID for local videos, vid:ID for embedded).'],
                        'gallery_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Related gallery ID.'],
                        'file_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Related attachment file ID.'],
                        'acl_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Access control group ID (0 = public).'],
                        'image' => ['type' => 'object', 'nullable' => true, 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['name', 'lang', 'category_id', 'content', 'summary'],
                ],
            ],

            'update_content' => [
                'module' => 'mod_content',
                'role' => 'edit',
                'handler' => 'updateContent',
                'description' => 'Partially update a content record. Only provided fields change; the summary (if provided) must be between 90 and 120 characters, and the seo slug (if provided) must remain unique per language.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Content record ID.'],
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New content name.'],
                        'lang' => ['type' => 'string', 'description' => 'New language code of an active site language. The category must match it.'],
                        'category_id' => ['type' => 'integer', 'description' => 'New category ID with a matching language.'],
                        'content' => ['type' => 'string', 'description' => 'New full HTML body.'],
                        'summary' => ['type' => 'string', 'minLength' => 90, 'maxLength' => 120, 'description' => 'New deck summary (90-120 characters).'],
                        'seo' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New URL slug, unique per language.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO title.'],
                        'keywords' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO keywords.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO description.'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New publish status.'],
                        'deck' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New deck flag.'],
                        'slider' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New hero slider flag.'],
                        'created_at' => ['type' => 'string', 'description' => 'New creation datetime (YYYY-MM-DD HH:MM:SS).'],
                        'video' => ['type' => 'string', 'description' => 'New comma-separated video references.'],
                        'gallery_id' => ['type' => 'integer', 'description' => 'New related gallery ID.'],
                        'file_id' => ['type' => 'integer', 'description' => 'New related attachment file ID.'],
                        'acl_id' => ['type' => 'integer', 'description' => 'New access control group ID.'],
                        'image' => ['type' => 'object', 'nullable' => true, 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['id'],
                ],
            ],

            'delete_content' => [
                'module' => 'mod_content',
                'role' => 'delete',
                'handler' => 'deleteContent',
                'description' => 'Delete a content record. Related gallery, attachment, and FAQ relations and uploaded image files are cleaned up automatically.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Content record ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'list_content_categories' => [
                'module' => 'mod_content_category',
                'role' => 'list',
                'handler' => 'listContentCategories',
                'description' => 'List content categories ordered by their sort order. Root categories are structural per-language roots and carry root="y".',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => ['type' => 'string', 'description' => 'Filter by language code (e.g. ku, en, tr).'],
                        'root' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by root flag: y (structural root) or n (regular category).'],
                        'search' => ['type' => 'string', 'description' => 'Search term matched against category names.'],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 50, 'description' => 'Page size (1-50, default 50).'],
                        'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Number of items to skip for pagination.'],
                    ],
                ],
            ],

            'get_content_category' => [
                'module' => 'mod_content_category',
                'role' => 'list',
                'handler' => 'getContentCategory',
                'description' => 'Get a single content category record with all fields.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Category record ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'create_content_category' => [
                'module' => 'mod_content_category',
                'role' => 'add',
                'handler' => 'createContentCategory',
                'description' => 'Create a content category. When a parent_id is given it must reference a category with the same language. The sort order defaults to the end of its sibling list. A menu entry is not created automatically.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Category name (1-255 characters).'],
                        'lang' => ['type' => 'string', 'description' => 'Language code of an active site language (e.g. ku, en, tr).'],
                        'parent_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Parent category ID with the same language, or 0 for a top-level category.'],
                        'root' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Structural root flag. Keep "n" for regular categories.'],
                        'seo' => ['type' => 'string', 'maxLength' => 255, 'description' => 'URL slug. Defaults to a slug generated from the name.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO title. Defaults to the name.'],
                        'keywords' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO keywords. Defaults to empty.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO description. Defaults to empty.'],
                        'acl_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Access control group ID (0 = public).'],
                        'image' => ['type' => 'object', 'nullable' => true, 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['name', 'lang'],
                ],
            ],

            'update_content_category' => [
                'module' => 'mod_content_category',
                'role' => 'edit',
                'handler' => 'updateContentCategory',
                'description' => 'Partially update a content category. When the name, language, seo, or acl changes, an existing menu entry pointing at the old category URL is updated automatically.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Category record ID.'],
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New category name.'],
                        'lang' => ['type' => 'string', 'description' => 'New language code of an active site language.'],
                        'parent_id' => ['type' => 'integer', 'description' => 'New parent category ID with the same language, or 0 for top level.'],
                        'root' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New structural root flag.'],
                        'seo' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New URL slug.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO title.'],
                        'keywords' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO keywords.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO description.'],
                        'acl_id' => ['type' => 'integer', 'description' => 'New access control group ID.'],
                        'order' => ['type' => 'integer', 'minimum' => 0, 'description' => 'New sort order.'],
                        'image' => ['type' => 'object', 'nullable' => true, 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['id'],
                ],
            ],

            'delete_content_category' => [
                'module' => 'mod_content_category',
                'role' => 'delete',
                'handler' => 'deleteContentCategory',
                'description' => 'Delete a content category and all of its child categories recursively. Related images, gallery/attachment relations, and menu entries are cleaned up automatically.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Category record ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'list_publications' => [
                'module' => 'mod_publication',
                'role' => 'list',
                'domain' => 'publication',
                'handler' => 'listPublications',
                'description' => 'List publications with optional filters. Returns paginated items (id, lang, name, category_id, status, highlighted, show_on_homepage, seo, created_at, updated_at) plus the total count.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'category_id' => ['type' => 'integer', 'description' => 'Filter by publication category ID.'],
                        'lang' => ['type' => 'string', 'description' => 'Filter by language code (e.g. ku, en, tr).'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by publish status: y (published) or n (draft).'],
                        'highlighted' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by highlighted flag.'],
                        'show_on_homepage' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by homepage visibility flag.'],
                        'search' => ['type' => 'string', 'description' => 'Search term matched against publication names.'],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10, 'description' => 'Page size (1-50, default 10).'],
                        'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Number of items to skip for pagination.'],
                    ],
                ],
            ],

            'get_publication' => [
                'module' => 'mod_publication',
                'role' => 'list',
                'domain' => 'publication',
                'handler' => 'getPublication',
                'description' => 'Get a single publication record with all fields, including the full body content, summary, specifications, and SEO metadata.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Publication record ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'create_publication' => [
                'module' => 'mod_publication',
                'role' => 'add',
                'domain' => 'publication',
                'handler' => 'createPublication',
                'description' => 'Create a publication record. The summary must be between 90 and 120 characters (deck summary SEO standard). The publication category must exist and match the publication language. The seo slug must be unique per language; it defaults to a slug generated from the name.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Service name (2-255 characters).'],
                        'lang' => ['type' => 'string', 'description' => 'Language code of an active site language (e.g. ku, en, tr).'],
                        'category_id' => ['type' => 'integer', 'description' => 'ID of an existing publication category with the same language.'],
                        'content' => ['type' => 'string', 'description' => 'Full HTML body of the service description.'],
                        'summary' => ['type' => 'string', 'minLength' => 90, 'maxLength' => 120, 'description' => 'Deck summary (exactly 90-120 characters).'],
                        'seo' => ['type' => 'string', 'maxLength' => 255, 'description' => 'URL slug. Defaults to a slug generated from the name. Must be unique per language.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO title. Defaults to the name.'],
                        'keywords' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO keywords. Defaults to empty.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO description. Defaults to the summary.'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'y', 'description' => 'Publish status: y (published) or n (draft).'],
                        'deck' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Show on deck: y or n.'],
                        'slider' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Show in hero slider flag.'],
                        'created_at' => ['type' => 'string', 'description' => 'Creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.'],
                        'video' => ['type' => 'string', 'description' => 'Comma-separated video references (video:ID for local videos, vid:ID for embedded).'],
                        'gallery_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Related gallery ID.'],
                        'file_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Related attachment file ID.'],
                        'acl_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Access control group ID (0 = public).'],
                        'image' => ['type' => 'object', 'nullable' => true, 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['name', 'lang', 'category_id', 'content', 'summary'],
                ],
            ],

            'update_publication' => [
                'module' => 'mod_publication',
                'role' => 'edit',
                'domain' => 'publication',
                'handler' => 'updatePublication',
                'description' => 'Partially update a publication record. Only provided fields change; the summary (if provided) must be between 90 and 120 characters, and the seo slug (if provided) must remain unique per language.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Publication record ID.'],
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New publication name.'],
                        'lang' => ['type' => 'string', 'description' => 'New language code of an active site language. The category must match it.'],
                        'category_id' => ['type' => 'integer', 'description' => 'New publication category ID with a matching language.'],
                        'content' => ['type' => 'string', 'description' => 'New full HTML body.'],
                        'summary' => ['type' => 'string', 'minLength' => 90, 'maxLength' => 120, 'description' => 'New deck summary (90-120 characters).'],
                        'specifications' => ['type' => 'string', 'description' => 'New technical specifications content.'],
                        'seo' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New URL slug, unique per language.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO title.'],
                        'keywords' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO keywords.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO description.'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New publish status.'],
                        'show_on_homepage' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New homepage visibility flag.'],
                        'slider' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New hero slider flag.'],
                        'created_at' => ['type' => 'string', 'description' => 'New creation datetime (YYYY-MM-DD HH:MM:SS).'],
                        'video' => ['type' => 'string', 'description' => 'New comma-separated video references.'],
                        'gallery_id' => ['type' => 'integer', 'description' => 'New related gallery ID.'],
                        'file_id' => ['type' => 'integer', 'description' => 'New related attachment file ID.'],
                        'acl_id' => ['type' => 'integer', 'description' => 'New access control group ID.'],
                        'image' => ['type' => 'object', 'nullable' => true, 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['id'],
                ],
            ],

            'delete_publication' => [
                'module' => 'mod_publication',
                'role' => 'delete',
                'domain' => 'publication',
                'handler' => 'deletePublication',
                'description' => 'Delete a publication record. Related gallery, attachment, and FAQ relations and uploaded image files are cleaned up automatically.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Publication record ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'list_publication_categories' => [
                'module' => 'mod_publication_category',
                'role' => 'list',
                'domain' => 'publication',
                'handler' => 'listPublicationCategories',
                'description' => 'List publication categories ordered by their sort order. Root categories are structural per-language roots and carry root="y".',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => ['type' => 'string', 'description' => 'Filter by language code (e.g. ku, en, tr).'],
                        'root' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by root flag: y (structural root) or n (regular category).'],
                        'search' => ['type' => 'string', 'description' => 'Search term matched against category names.'],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 50, 'description' => 'Page size (1-50, default 50).'],
                        'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Number of items to skip for pagination.'],
                    ],
                ],
            ],

            'get_publication_category' => [
                'module' => 'mod_publication_category',
                'role' => 'list',
                'domain' => 'publication',
                'handler' => 'getPublicationCategory',
                'description' => 'Get a single publication category record with all fields.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Publication category record ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'create_publication_category' => [
                'module' => 'mod_publication_category',
                'role' => 'add',
                'domain' => 'publication',
                'handler' => 'createPublicationCategory',
                'description' => 'Create a publication category. When a parent_id is given it must reference a category with the same language. The sort order defaults to the end of its sibling list. A menu entry is not created automatically.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Category name (2-255 characters).'],
                        'lang' => ['type' => 'string', 'description' => 'Language code of an active site language (e.g. ku, en, tr).'],
                        'parent_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Parent category ID with the same language, or 0 for a top-level category.'],
                        'root' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Structural root flag. Keep "n" for regular categories.'],
                        'seo' => ['type' => 'string', 'maxLength' => 255, 'description' => 'URL slug. Defaults to a slug generated from the name.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO title. Defaults to the name.'],
                        'keywords' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO keywords. Defaults to empty.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO description. Defaults to empty.'],
                        'show_on_homepage' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Homepage visibility flag.'],
                        'acl_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Access control group ID (0 = public).'],
                        'image' => ['type' => 'object', 'nullable' => true, 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['name', 'lang'],
                ],
            ],

            'update_publication_category' => [
                'module' => 'mod_publication_category',
                'role' => 'edit',
                'domain' => 'publication',
                'handler' => 'updatePublicationCategory',
                'description' => 'Partially update a publication category. When the name, language, seo, or acl changes, an existing menu entry pointing at the old category URL ({lang}/publications/{seo}) is updated automatically.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Publication category record ID.'],
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New category name.'],
                        'lang' => ['type' => 'string', 'description' => 'New language code of an active site language.'],
                        'parent_id' => ['type' => 'integer', 'description' => 'New parent category ID with the same language, or 0 for top level.'],
                        'root' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New structural root flag.'],
                        'seo' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New URL slug.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO title.'],
                        'keywords' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO keywords.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO description.'],
                        'show_on_homepage' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New homepage visibility flag.'],
                        'acl_id' => ['type' => 'integer', 'description' => 'New access control group ID.'],
                        'order' => ['type' => 'integer', 'minimum' => 0, 'description' => 'New sort order.'],
                        'image' => ['type' => 'object', 'nullable' => true, 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['id'],
                ],
            ],

            'delete_publication_category' => [
                'module' => 'mod_publication_category',
                'role' => 'delete',
                'domain' => 'publication',
                'handler' => 'deletePublicationCategory',
                'description' => 'Delete a publication category and all of its child categories recursively. Related image files and menu entries are cleaned up automatically.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Publication category record ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'list_announcements' => [
                'module' => 'mod_announcements',
                'role' => 'list',
                'domain' => 'announcement',
                'handler' => 'listAnnouncements',
                'description' => 'List announcements with optional filters. Returns paginated items (id, lang, name, category_id, status, highlighted, show_on_homepage, seo, created_at, updated_at) plus the total count.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'category_id' => ['type' => 'integer', 'description' => 'Filter by announcement ID.'],
                        'lang' => ['type' => 'string', 'description' => 'Filter by language code (e.g. ku, en, tr).'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by publish status: y (published) or n (draft).'],
                        'highlighted' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by highlighted flag.'],
                        'show_on_homepage' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by homepage visibility flag.'],
                        'search' => ['type' => 'string', 'description' => 'Search term matched against announcement names.'],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10, 'description' => 'Page size (1-50, default 10).'],
                        'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Number of items to skip for pagination.'],
                    ],
                ],
            ],

            'get_announcement' => [
                'module' => 'mod_announcements',
                'role' => 'list',
                'domain' => 'announcement',
                'handler' => 'getAnnouncement',
                'description' => 'Get a single announcement record with all fields, including the full body content, summary, specifications, and SEO metadata.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Announcement record ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'create_announcement' => [
                'module' => 'mod_announcements',
                'role' => 'add',
                'domain' => 'announcement',
                'handler' => 'createAnnouncement',
                'description' => 'Create an announcement record. The summary must be between 90 and 120 characters (deck summary SEO standard). Announcement names are 1-70 characters.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Service name (2-255 characters).'],
                        'lang' => ['type' => 'string', 'description' => 'Language code of an active site language (e.g. ku, en, tr).'],
                        'content' => ['type' => 'string', 'description' => 'Full HTML body of the service description.'],
                        'summary' => ['type' => 'string', 'minLength' => 90, 'maxLength' => 120, 'description' => 'Deck summary (exactly 90-120 characters).'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO title. Defaults to the name.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO description. Defaults to the summary.'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'y', 'description' => 'Publish status: y (published) or n (draft).'],
                        'deck' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Show on deck: y or n.'],
                        'slider' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Show in hero slider flag.'],
                        'created_at' => ['type' => 'string', 'description' => 'Creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.'],
                        'video' => ['type' => 'string', 'description' => 'Comma-separated video references (video:ID for local videos, vid:ID for embedded).'],
                        'gallery_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Related gallery ID.'],
                        'file_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Related attachment file ID.'],
                        'acl_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Access control group ID (0 = public).'],
                        'image' => ['type' => 'object', 'nullable' => true, 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['name', 'lang', 'content', 'summary'],
                ],
            ],

            'update_announcement' => [
                'module' => 'mod_announcements',
                'role' => 'edit',
                'domain' => 'announcement',
                'handler' => 'updateAnnouncement',
                'description' => 'Partially update an announcement record. Only provided fields change; the summary (if provided) must be between 90 and 120 characters,',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Announcement record ID.'],
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New publication name.'],
                        'lang' => ['type' => 'string', 'description' => 'New language code of an active site language.'],
                        'content' => ['type' => 'string', 'description' => 'New full HTML body.'],
                        'summary' => ['type' => 'string', 'minLength' => 90, 'maxLength' => 120, 'description' => 'New deck summary (90-120 characters).'],
                        'specifications' => ['type' => 'string', 'description' => 'New technical specifications content.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO title.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO description.'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New publish status.'],
                        'show_on_homepage' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New homepage visibility flag.'],
                        'slider' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New hero slider flag.'],
                        'created_at' => ['type' => 'string', 'description' => 'New creation datetime (YYYY-MM-DD HH:MM:SS).'],
                        'video' => ['type' => 'string', 'description' => 'New comma-separated video references.'],
                        'gallery_id' => ['type' => 'integer', 'description' => 'New related gallery ID.'],
                        'file_id' => ['type' => 'integer', 'description' => 'New related attachment file ID.'],
                        'acl_id' => ['type' => 'integer', 'description' => 'New access control group ID.'],
                        'image' => ['type' => 'object', 'nullable' => true, 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['id'],
                ],
            ],

            'delete_announcement' => [
                'module' => 'mod_announcements',
                'role' => 'delete',
                'domain' => 'announcement',
                'handler' => 'deleteAnnouncement',
                'description' => 'Delete a announcement record. Related gallery, attachment, and FAQ relations and uploaded image files are cleaned up automatically.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Announcement record ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'list_site_settings' => [
                'module' => 'mod_settings',
                'role' => 'list',
                'domain' => 'settings',
                'handler' => 'listSiteSettings',
                'description' => 'List site settings rows (one per language). Each row carries title, site name, description, owner/contact fields, SEO verification fields, statistics counters, and the cookie policy content.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => ['type' => 'string', 'description' => 'Filter by language code (e.g. ku, en, tr).'],
                    ],
                ],
            ],

            'get_site_settings' => [
                'module' => 'mod_settings',
                'role' => 'list',
                'domain' => 'settings',
                'handler' => 'getSiteSettings',
                'description' => 'Get the full site settings row for one language.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => ['type' => 'string', 'description' => 'Language code of the settings row (e.g. ku, en, tr).'],
                    ],
                    'required' => ['lang'],
                ],
            ],

            'update_site_settings' => [
                'module' => 'mod_settings',
                'role' => 'edit',
                'domain' => 'settings',
                'handler' => 'updateSiteSettings',
                'description' => 'Partially update the site settings row for one language. Editable fields: title, site_name, description, owner_name, phone, whatsapp, email, office_address, work_address, map_embed_code, google_search_console, google_analytics, meta_tags, cookie_policy_content, established_year, show_logo, logo_type, the four statistics counters, plus the global site_status (open/closed), maintenance_message and maintenance_retry_after (synchronized across all languages). Logo/favicon asset paths are managed through the admin upload UI and are intentionally not editable here.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => ['type' => 'string', 'description' => 'Language code of the settings row to update.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Browser tab title.'],
                        'site_name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Public site name.'],
                        'description' => ['type' => 'string', 'description' => 'Site meta description.'],
                        'owner_name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Site owner name.'],
                        'phone' => ['type' => 'string', 'maxLength' => 50, 'description' => 'Contact phone number.'],
                        'whatsapp' => ['type' => 'string', 'maxLength' => 50, 'description' => 'WhatsApp number.'],
                        'email' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Contact e-mail address.'],
                        'office_address' => ['type' => 'string', 'description' => 'Office address text.'],
                        'work_address' => ['type' => 'string', 'description' => 'Workshop address text.'],
                        'map_embed_code' => ['type' => 'string', 'description' => 'Map embed HTML snippet.'],
                        'google_search_console' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Google Search Console verification value.'],
                        'google_analytics' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Google Analytics measurement ID.'],
                        'meta_tags' => ['type' => 'string', 'description' => 'Additional meta tags HTML.'],
                        'cookie_policy_content' => ['type' => 'string', 'description' => 'Cookie policy page content (HTML).'],
                        'established_year' => ['type' => 'string', 'description' => '4-digit founding year, or empty.'],
                        'show_logo' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Show the site logo. Synchronized across all languages.'],
                        'logo_type' => ['type' => 'string', 'enum' => ['square', 'rectangle', 'square_name', 'rectangle_name', 'name_only'], 'description' => 'Logo layout type. Synchronized across all languages.'],
                        'stat_completed_projects' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Completed projects counter.'],
                        'stat_ongoing_projects' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Ongoing projects counter.'],
                        'stat_workforce' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Workforce counter.'],
                        'stat_offices' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Offices counter.'],
                        'site_status' => ['type' => 'string', 'enum' => ['open', 'closed'], 'description' => 'Site availability. Synchronized across all languages.'],
                        'maintenance_message' => ['type' => 'string', 'description' => 'Maintenance page message. Synchronized across all languages.'],
                        'maintenance_retry_after' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Retry-after seconds for the maintenance page. Synchronized across all languages.'],
                    ],
                    'required' => ['lang'],
                ],
            ],

            'list_social_links' => [
                'module' => 'mod_settings',
                'role' => 'list',
                'domain' => 'settings',
                'handler' => 'listSocialLinks',
                'description' => 'List the social media links ordered by display order (platform, url, icon, active state).',
                'schema' => [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
            ],

            'update_social_link' => [
                'module' => 'mod_settings',
                'role' => 'edit',
                'domain' => 'settings',
                'handler' => 'updateSocialLink',
                'description' => 'Update an existing social link (url, is_active, display_order). Social links are seeded at install; creating new ones is an admin UI concern and not available here.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Social link record ID.'],
                        'url' => ['type' => 'string', 'description' => 'New profile URL.'],
                        'is_active' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Active state.'],
                        'display_order' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Display order.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'list_cookie_declarations' => [
                'module' => 'mod_settings',
                'role' => 'list',
                'domain' => 'settings',
                'handler' => 'listCookieDeclarations',
                'description' => 'List cookie declarations with optional language and category filters.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => ['type' => 'string', 'description' => 'Filter by language code.'],
                        'category' => ['type' => 'string', 'enum' => ['necessary', 'functional', 'analytics', 'marketing'], 'description' => 'Filter by cookie category.'],
                    ],
                ],
            ],

            'create_cookie_declaration' => [
                'module' => 'mod_settings',
                'role' => 'edit',
                'domain' => 'settings',
                'handler' => 'createCookieDeclaration',
                'description' => 'Create a cookie declaration. Required: lang and cookie_name. Category defaults to necessary.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => ['type' => 'string', 'description' => 'Language code of an active site language.'],
                        'cookie_name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Cookie name (1-255 characters).'],
                        'category' => ['type' => 'string', 'enum' => ['necessary', 'functional', 'analytics', 'marketing'], 'default' => 'necessary', 'description' => 'Cookie category.'],
                        'provider' => ['type' => 'string', 'description' => 'Cookie provider.'],
                        'purpose' => ['type' => 'string', 'description' => 'Cookie purpose description.'],
                        'duration' => ['type' => 'string', 'description' => 'Cookie lifetime description.'],
                        'display_order' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Display order.'],
                        'is_active' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'y', 'description' => 'Active state.'],
                    ],
                    'required' => ['lang', 'cookie_name'],
                ],
            ],

            'update_cookie_declaration' => [
                'module' => 'mod_settings',
                'role' => 'edit',
                'domain' => 'settings',
                'handler' => 'updateCookieDeclaration',
                'description' => 'Partially update a cookie declaration. Only provided fields change.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Cookie declaration record ID.'],
                        'lang' => ['type' => 'string', 'description' => 'New language code of an active site language.'],
                        'cookie_name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New cookie name.'],
                        'category' => ['type' => 'string', 'enum' => ['necessary', 'functional', 'analytics', 'marketing'], 'description' => 'New cookie category.'],
                        'provider' => ['type' => 'string', 'description' => 'New provider.'],
                        'purpose' => ['type' => 'string', 'description' => 'New purpose description.'],
                        'duration' => ['type' => 'string', 'description' => 'New duration description.'],
                        'display_order' => ['type' => 'integer', 'minimum' => 0, 'description' => 'New display order.'],
                        'is_active' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New active state.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'delete_cookie_declaration' => [
                'module' => 'mod_settings',
                'role' => 'edit',
                'domain' => 'settings',
                'handler' => 'deleteCookieDeclaration',
                'description' => 'Delete a cookie declaration permanently.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Cookie declaration record ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'list_menu_items' => [
                'module' => 'mod_menu',
                'role' => 'list',
                'domain' => 'menu',
                'handler' => 'listMenuItems',
                'description' => 'List menu items with optional filters, ordered by display order. Structural container rows (language roots named ##<lang> and menu slots named ##Header_Menu, ##Footer_Menu, ##Action_Menu) carry the ## prefix and cannot be deleted or renamed.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => ['type' => 'string', 'description' => 'Filter by language code (e.g. ku, en, tr).'],
                        'parent_id' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Filter by parent item ID (0 = top level structural rows).'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by active status: y or n.'],
                        'search' => ['type' => 'string', 'description' => 'Search term matched against menu names.'],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 100, 'description' => 'Page size (1-100, default 100).'],
                        'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Number of items to skip for pagination.'],
                    ],
                ],
            ],

            'get_menu_item' => [
                'module' => 'mod_menu',
                'role' => 'list',
                'domain' => 'menu',
                'handler' => 'getMenuItem',
                'description' => 'Get a single menu item with all fields.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Menu item record ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'create_menu_item' => [
                'module' => 'mod_menu',
                'role' => 'add',
                'domain' => 'menu',
                'handler' => 'createMenuItem',
                'description' => 'Create a menu item under an existing parent (a menu slot root or another item). The parent ancestry language must match the item language. The sort order defaults to the end of the sibling list. Structural container names starting with ## are reserved.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => ['type' => 'string', 'description' => 'Language code of an active site language (e.g. ku, en, tr).'],
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Menu label (2-255 characters; cannot start with ##).'],
                        'parent_id' => ['type' => 'integer', 'description' => 'Parent slot root or parent item ID with a matching ancestry language.'],
                        'url' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Link target URL or route (e.g. tr/services/my_service). Optional when html is used.'],
                        'file' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Custom controller file reference.'],
                        'target' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Open in new window: y or n.'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'y', 'description' => 'Active status: y or n.'],
                        'html' => ['type' => 'string', 'description' => 'Raw HTML content instead of a link.'],
                        'icon' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Icon class (e.g. bi bi-house-door).'],
                        'acl_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Access control group ID (0 = public).'],
                    ],
                    'required' => ['lang', 'name', 'parent_id'],
                ],
            ],

            'update_menu_item' => [
                'module' => 'mod_menu',
                'role' => 'edit',
                'domain' => 'menu',
                'handler' => 'updateMenuItem',
                'description' => 'Partially update a menu item. Only provided fields change. Moving between slots re-validates the parent ancestry language. Editing a record owned by another admin requires the moderate_edit role.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Menu item record ID.'],
                        'lang' => ['type' => 'string', 'description' => 'New language code of an active site language.'],
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New menu label.'],
                        'parent_id' => ['type' => 'integer', 'description' => 'New parent slot root or parent item ID.'],
                        'url' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New link target URL or route.'],
                        'file' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New custom controller file reference.'],
                        'target' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New window target.'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New active status.'],
                        'html' => ['type' => 'string', 'description' => 'New raw HTML content.'],
                        'icon' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New icon class.'],
                        'acl_id' => ['type' => 'integer', 'description' => 'New access control group ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'delete_menu_item' => [
                'module' => 'mod_menu',
                'role' => 'delete',
                'domain' => 'menu',
                'handler' => 'deleteMenuItem',
                'description' => 'Delete a menu item and all of its child items recursively. Structural container rows (language roots and menu slots) are protected and cannot be deleted. Gallery/attachment relations are cleaned up.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Menu item record ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],

                   'list_acls' => [
                    'module' => 'mod_acl',
                    'role' => 'list',
                    'domain' => 'acl',
                    'handler' => 'listAclGroups',
                    'description' => 'List ACL groups (id and name) with optional name search. Results are ordered by ID descending and paginated (limit capped at 50).',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'search' => [
                    'type' => 'string',
                    'description' => 'Optional case-insensitive substring filter on the group name.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'default' => 10,
                    'description' => 'Maximum number of records to return (capped at 50).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'default' => 0,
                    'description' => 'Number of records to skip for pagination.'
                ]
                ],
                    'required' => []
                ]
                ],

            'get_acl' => [
                    'module' => 'mod_acl',
                    'role' => 'list',
                    'domain' => 'acl',
                    'handler' => 'getAclGroup',
                    'description' => 'Get a single ACL group by ID, including the raw serialized ACL column and the decoded list of allowed user group IDs (0 = guest).',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'ACL group record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'create_acl' => [
                    'module' => 'mod_acl',
                    'role' => 'add',
                    'domain' => 'acl',
                    'handler' => 'createAclGroup',
                    'description' => 'Create an ACL group. The name is required (2-255 characters, mirrors the admin validator). \'groups\' lists the user group IDs granted access (0 = guest); every non-zero ID must exist in user group management.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'name' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'ACL group name (2-255 characters).'
                ],
                    'groups' => [
                    'type' => 'array',
                    'items' => [
                    'type' => 'integer',
                    'minimum' => 0
                ],
                    'default' => [],
                    'description' => 'User group IDs allowed by this ACL (0 = guest). Each non-zero ID must reference an existing user group. Omit for an empty list.'
                ]
                ],
                    'required' => [
                    'name'
                ]
                ]
                ],

            'update_acl' => [
                    'module' => 'mod_acl',
                    'role' => 'edit',
                    'domain' => 'acl',
                    'handler' => 'updateAclGroup',
                    'description' => 'Partially update an ACL group. Only supplied fields change; editing a record owned by another user requires the moderate_edit role. The permission cache for the record is invalidated after a successful update.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'ACL group record ID.'
                ],
                    'name' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'New ACL group name (2-255 characters).'
                ],
                    'groups' => [
                    'type' => 'array',
                    'items' => [
                    'type' => 'integer',
                    'minimum' => 0
                ],
                    'description' => 'Replacement list of user group IDs allowed by this ACL (0 = guest). Each non-zero ID must reference an existing user group.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'delete_acl' => [
                    'module' => 'mod_acl',
                    'role' => 'delete',
                    'domain' => 'acl',
                    'handler' => 'deleteAclGroup',
                    'description' => 'Delete an ACL group by ID. Deleting a record owned by another user requires the moderate_delete role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'ACL group record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'list_attachments' => [
                    'module' => 'mod_attachment',
                    'role' => 'list',
                    'domain' => 'attachment',
                    'handler' => 'listAttachments',
                    'description' => 'List downloadable attachment file records with optional filters. Returns paginated items (id, filename, url, ext, original_name, type, created_at, mod_name, vid, user_id) plus the total count. Each attachment belongs to a host record of another module through mod_name and vid.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'mod_name' => [
                    'type' => 'string',
                    'description' => 'Filter by host module name (e.g. mod_content, mod_page).'
                ],
                    'vid' => [
                    'type' => 'integer',
                    'description' => 'Filter by host record ID.'
                ],
                    'ext' => [
                    'type' => 'string',
                    'description' => 'Filter by file extension (e.g. pdf, docx, zip).'
                ],
                    'search' => [
                    'type' => 'string',
                    'description' => 'Search term matched against the original file name.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'default' => 10,
                    'description' => 'Page size (1-50, default 10).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'default' => 0,
                    'description' => 'Number of items to skip for pagination.'
                ]
                ],
                    'required' => []
                ]
                ],

            'get_attachment' => [
                    'module' => 'mod_attachment',
                    'role' => 'list',
                    'domain' => 'attachment',
                    'handler' => 'getAttachment',
                    'description' => 'Get a single attachment record with all fields, including the stored URL, extension, original file name, MIME type, host module binding (mod_name, vid) and view count.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Attachment record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'create_attachment' => [
                    'module' => 'mod_attachment',
                    'role' => 'add',
                    'domain' => 'attachment',
                    'handler' => 'createAttachment',
                    'description' => 'Upload a downloadable file and attach it to a host record. The file is stored under static/mod_attachment/<host_module>/ with a random key; the extension must be one of the admin upload whitelist (txt, xls, jpg, jpeg, png, gif, bmp, rar, zip, pdf, doc, docx, ods, ppt, odt, epub) and the decoded payload must not exceed 10 MB. The host module must be registered in the attachment bridge (mod_attachment_mod) and the host record must exist.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'mod_name' => [
                    'type' => 'string',
                    'description' => 'Host module name the file is attached to (e.g. mod_content). Must be registered in the attachment bridge.'
                ],
                    'vid' => [
                    'type' => 'integer',
                    'description' => 'Host record ID the file is attached to. Must exist in the host module.'
                ],
                    'file' => [
                    'type' => 'object',
                    'description' => 'The file to upload.',
                    'properties' => [
                    'filename' => [
                    'type' => 'string',
                    'description' => 'Original file name including the extension (e.g. report.pdf). Determines the stored extension and the default display name.'
                ],
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded file content (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'maxLength' => 250,
                    'description' => 'MIME type stored with the record. Defaults to application/octet-stream.'
                ]
                ],
                    'required' => [
                    'filename',
                    'data_base64'
                ]
                ],
                    'filename' => [
                    'type' => 'string',
                    'maxLength' => 250,
                    'description' => 'Optional display name override. Defaults to the sanitized original file name.'
                ],
                    'created_at' => [
                    'type' => 'string',
                    'description' => 'Creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.'
                ]
                ],
                    'required' => [
                    'mod_name',
                    'vid',
                    'file'
                ]
                ]
                ],

            'update_attachment' => [
                    'module' => 'mod_attachment',
                    'role' => 'edit',
                    'domain' => 'attachment',
                    'handler' => 'updateAttachment',
                    'description' => 'Update an attachment record. The display filename can be renamed and the stored binary can optionally be replaced with a new upload (same whitelist and size rules as create; the previous file is removed after a successful save). The host binding (mod_name, vid) cannot be moved, mirroring the admin edit flow.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Attachment record ID.'
                ],
                    'filename' => [
                    'type' => 'string',
                    'maxLength' => 250,
                    'description' => 'New display name (1-250 characters).'
                ],
                    'file' => [
                    'type' => 'object',
                    'description' => 'Replacement file upload.',
                    'properties' => [
                    'filename' => [
                    'type' => 'string',
                    'description' => 'Original file name including the extension (e.g. report.pdf). Determines the stored extension and the default display name.'
                ],
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded file content (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'maxLength' => 250,
                    'description' => 'MIME type stored with the record. Defaults to application/octet-stream.'
                ]
                ],
                    'required' => [
                    'filename',
                    'data_base64'
                ]
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'delete_attachment' => [
                    'module' => 'mod_attachment',
                    'role' => 'delete',
                    'domain' => 'attachment',
                    'handler' => 'deleteAttachment',
                    'description' => 'Delete an attachment record and its stored file from static/mod_attachment/<host_module>/. Deleting a host record through its own module tools removes all attached files automatically; deleting another user\'s attachment requires the moderate_delete role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Attachment record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'list_documents' => [
                    'module' => 'mod_document',
                    'role' => 'list',
                    'domain' => 'document',
                    'handler' => 'listDocuments',
                    'description' => 'List documents with optional filters. Returns paginated items (id, lang, name, seo, status, image) plus the total count.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Filter by active site language code (e.g. en, tr, ku).'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by publication status: y = published, n = draft.'
                ],
                    'search' => [
                    'type' => 'string',
                    'description' => 'Case-insensitive substring filter applied to the document name.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'default' => 10,
                    'description' => 'Number of documents to return (max 50).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'default' => 0,
                    'description' => 'Number of documents to skip for pagination.'
                ]
                ],
                    'required' => []
                ]
                ],

            'get_document' => [
                    'module' => 'mod_document',
                    'role' => 'list',
                    'domain' => 'document',
                    'handler' => 'getDocument',
                    'description' => 'Get a single document record with all fields, including SEO metadata and the image reference.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'The document record id.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'create_document' => [
                    'module' => 'mod_document',
                    'role' => 'add',
                    'domain' => 'document',
                    'handler' => 'createDocument',
                    'description' => 'Create a document record. Requires lang, name, keywords and description. The seo slug must be unique per language; it defaults to a slug generated from the name. The title defaults to the name. Status defaults to published. An optional image can be uploaded as {data_base64, mime} (jpeg, png, gif, webp; max 10 MB decoded), or set to null to leave the image empty.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Active site language code for the document (e.g. en, tr, ku).'
                ],
                    'name' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'Document name (2-255 characters).'
                ],
                    'seo' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 255,
                    'description' => 'URL slug, unique per language. Defaults to a slug generated from the name.'
                ],
                    'title' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 255,
                    'description' => 'HTML title tag. Defaults to the document name.'
                ],
                    'keywords' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 255,
                    'description' => 'Meta keywords (1-255 characters).'
                ],
                    'description' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 500,
                    'description' => 'Meta description (1-500 characters).'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'default' => 'y',
                    'description' => 'Publication status: y = published, n = draft. Defaults to published.'
                ],
                    'image' => [
                    'oneOf' => [
                    [
                    'type' => 'object',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image data (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ],
                    'description' => 'Image MIME type.'
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ],
                    [
                    'type' => 'null'
                ]
                ],
                    'description' => 'Image upload object {data_base64, mime}, or null to explicitly leave/remove the image. Omit to keep untouched.'
                ]
                ],
                    'required' => [
                    'lang',
                    'name',
                    'keywords',
                    'description'
                ]
                ]
                ],

            'update_document' => [
                    'module' => 'mod_document',
                    'role' => 'edit',
                    'domain' => 'document',
                    'handler' => 'updateDocument',
                    'description' => 'Partially update a document record. Only provided fields change. The seo slug (if provided) must remain unique per language. Editing a document owned by another admin requires the moderate_edit role. The image argument accepts {data_base64, mime} to replace, null to remove, or can be omitted to keep the current image.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'The document record id.'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Active site language code to move the document to (e.g. en, tr, ku).'
                ],
                    'name' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'Document name (2-255 characters).'
                ],
                    'seo' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 255,
                    'description' => 'URL slug, unique per language.'
                ],
                    'title' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 255,
                    'description' => 'HTML title tag.'
                ],
                    'keywords' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 255,
                    'description' => 'Meta keywords (1-255 characters).'
                ],
                    'description' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 500,
                    'description' => 'Meta description (1-500 characters).'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Publication status: y = published, n = draft.'
                ],
                    'image' => [
                    'oneOf' => [
                    [
                    'type' => 'object',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image data (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ],
                    'description' => 'Image MIME type.'
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ],
                    [
                    'type' => 'null'
                ]
                ],
                    'description' => 'Image upload object {data_base64, mime} to replace the image, or null to remove it. Omit to keep the current image.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'delete_document' => [
                    'module' => 'mod_document',
                    'role' => 'delete',
                    'domain' => 'document',
                    'handler' => 'deleteDocument',
                    'description' => 'Delete a document record. Related gallery and attachment (file) relations and the uploaded image files are cleaned up automatically. Deleting a document owned by another admin requires the moderate_delete role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'The document record id.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'list_faqs' => [
                    'module' => 'mod_faq',
                    'role' => 'list',
                    'domain' => 'faq',
                    'handler' => 'listFaqs',
                    'description' => 'List FAQ records with optional filters, ordered by sort_order then id. Returns paginated items (id, lang, mod_name, vid, question, sort_order, status, created_at, updated_at) plus the total count. The answer body is only returned by get_faq.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'mod_name' => [
                    'type' => 'string',
                    'description' => 'Filter by host module name (e.g. mod_page). FAQ records are attached to records of host modules registered in mod_faq_mod.'
                ],
                    'vid' => [
                    'type' => 'integer',
                    'description' => 'Filter by host record ID the FAQ items are attached to.'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Filter by language code (e.g. ku, en, tr).'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by publish status: y (published) or n (draft).'
                ],
                    'search' => [
                    'type' => 'string',
                    'description' => 'Search term matched against the question text.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'default' => 10,
                    'description' => 'Page size (1-50, default 10).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'default' => 0,
                    'description' => 'Number of items to skip for pagination.'
                ]
                ]
                ]
                ],

            'get_faq' => [
                    'module' => 'mod_faq',
                    'role' => 'list',
                    'domain' => 'faq',
                    'handler' => 'getFaq',
                    'description' => 'Get a single FAQ record with all fields, including the full answer.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'FAQ record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'create_faq' => [
                    'module' => 'mod_faq',
                    'role' => 'add',
                    'domain' => 'faq',
                    'handler' => 'createFaq',
                    'description' => 'Create a FAQ record attached to a host module record. The host module must be registered in mod_faq_mod and the host record must exist. Questions are unique per host entity and language (case-insensitive); each entity and language accepts at most 25 questions. Editing a record owned by another admin requires the moderate_edit role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'mod_name' => [
                    'type' => 'string',
                    'description' => 'Host module name that owns the entity (must be registered in mod_faq_mod, e.g. mod_page).'
                ],
                    'vid' => [
                    'type' => 'integer',
                    'description' => 'ID of the existing host module record the FAQ is attached to.'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Language code of an active site language (e.g. ku, en, tr).'
                ],
                    'question' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Question text (1-255 characters, unique per entity and language).'
                ],
                    'answer' => [
                    'type' => 'string',
                    'maxLength' => 5000,
                    'description' => 'Answer text (1-5000 characters).'
                ],
                    'sort_order' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'description' => 'Display order among the entity\'s FAQ items. Defaults to the last position.'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'default' => 'y',
                    'description' => 'Publish status: y (published) or n (draft).'
                ],
                    'created_at' => [
                    'type' => 'string',
                    'description' => 'Creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.'
                ]
                ],
                    'required' => [
                    'mod_name',
                    'vid',
                    'lang',
                    'question',
                    'answer'
                ]
                ]
                ],

            'update_faq' => [
                    'module' => 'mod_faq',
                    'role' => 'edit',
                    'domain' => 'faq',
                    'handler' => 'updateFaq',
                    'description' => 'Partially update a FAQ record. Only provided fields change. The host entity (mod_name, vid) is immutable. Questions must remain unique per host entity and language (case-insensitive). Editing a record owned by another admin requires the moderate_edit role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'FAQ record ID.'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'New language code of an active site language (e.g. ku, en, tr).'
                ],
                    'question' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New question text (1-255 characters, unique per entity and language).'
                ],
                    'answer' => [
                    'type' => 'string',
                    'maxLength' => 5000,
                    'description' => 'New answer text (1-5000 characters).'
                ],
                    'sort_order' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'description' => 'New display order among the entity\'s FAQ items.'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'New publish status: y (published) or n (draft).'
                ],
                    'created_at' => [
                    'type' => 'string',
                    'description' => 'New creation datetime (YYYY-MM-DD HH:MM:SS).'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'delete_faq' => [
                    'module' => 'mod_faq',
                    'role' => 'delete',
                    'domain' => 'faq',
                    'handler' => 'deleteFaq',
                    'description' => 'Delete a FAQ record. Deleting a record owned by another admin requires the moderate_delete role. No bridge relations exist between FAQ records and the host modules, so no cleanup is required.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'FAQ record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'list_galleries' => [
                    'module' => 'mod_gallery',
                    'role' => 'list',
                    'domain' => 'gallery',
                    'handler' => 'listGalleries',
                    'description' => 'List galleries as (mod_name, vid) groups with image counts and the last three preview image urls. Only modules registered in the gallery bridge are listed. Optional filters: mod_name, vid, and a search term matched against the module name and record id.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'mod_name' => [
                    'type' => 'string',
                    'description' => 'Filter galleries by target module name (for example mod_content).'
                ],
                    'vid' => [
                    'type' => 'integer',
                    'description' => 'Filter galleries by target record id.'
                ],
                    'search' => [
                    'type' => 'string',
                    'description' => 'Search term matched against the module name and record id.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'default' => 10,
                    'maximum' => 50,
                    'description' => 'Maximum number of galleries to return (1-50).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Number of galleries to skip.'
                ]
                ],
                    'required' => []
                ]
                ],

            'get_gallery' => [
                    'module' => 'mod_gallery',
                    'role' => 'list',
                    'domain' => 'gallery',
                    'handler' => 'getGallery',
                    'description' => 'Get one gallery with all of its image rows (id, name, url, created_at, mod_name, vid, user_id), the resolved record label, and the image count. The target module must be registered for galleries and the target record must exist.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'mod_name' => [
                    'type' => 'string',
                    'description' => 'Target module name that owns the gallery (for example mod_content).'
                ],
                    'vid' => [
                    'type' => 'integer',
                    'description' => 'Target record id that owns the gallery.'
                ]
                ],
                    'required' => [
                    'mod_name',
                    'vid'
                ]
                ]
                ],

            'upload_gallery_image' => [
                    'module' => 'mod_gallery',
                    'role' => 'edit',
                    'domain' => 'gallery',
                    'handler' => 'uploadGalleryImage',
                    'description' => 'Upload a new image into a gallery. Stores an original copy (max width 1920) and a thumb (max width 500) under static/mod_gallery/{mod_name}/ and creates the image row. The display name defaults to the target record label; the target module must be registered for galleries and the target record must exist.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'mod_name' => [
                    'type' => 'string',
                    'description' => 'Target module name that owns the gallery.'
                ],
                    'vid' => [
                    'type' => 'integer',
                    'description' => 'Target record id that owns the gallery.'
                ],
                    'image' => [
                    'type' => 'object',
                    'description' => 'The image file to upload.',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ],
                    'description' => 'Image MIME type.'
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ],
                    'name' => [
                    'type' => 'string',
                    'maxLength' => 250,
                    'description' => 'Optional display name for the image. Defaults to the target record label.'
                ],
                    'created_at' => [
                    'type' => 'string',
                    'description' => 'Optional creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.'
                ]
                ],
                    'required' => [
                    'mod_name',
                    'vid',
                    'image'
                ]
                ]
                ],

            'rename_gallery_image' => [
                    'module' => 'mod_gallery',
                    'role' => 'edit',
                    'domain' => 'gallery',
                    'handler' => 'renameGalleryImage',
                    'description' => 'Rename a gallery image row. Requires the gallery role on the target module that owns the image.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Gallery image record id.'
                ],
                    'name' => [
                    'type' => 'string',
                    'maxLength' => 250,
                    'description' => 'New display name for the image.'
                ]
                ],
                    'required' => [
                    'id',
                    'name'
                ]
                ]
                ],

            'delete_gallery_image' => [
                    'module' => 'mod_gallery',
                    'role' => 'delete',
                    'domain' => 'gallery',
                    'handler' => 'deleteGalleryImage',
                    'description' => 'Delete one gallery image row together with its generated original and thumb files.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Gallery image record id.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'clear_gallery' => [
                    'module' => 'mod_gallery',
                    'role' => 'delete',
                    'domain' => 'gallery',
                    'handler' => 'clearGallery',
                    'description' => 'Remove every image of one gallery (all images attached to a target record) together with their generated files. Mirrors the admin bulk delete.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'mod_name' => [
                    'type' => 'string',
                    'description' => 'Target module name that owns the gallery.'
                ],
                    'vid' => [
                    'type' => 'integer',
                    'description' => 'Target record id that owns the gallery.'
                ]
                ],
                    'required' => [
                    'mod_name',
                    'vid'
                ]
                ]
                ],

            'list_gallery_modules' => [
                    'module' => 'mod_gallery_mod',
                    'role' => 'list',
                    'domain' => 'gallery',
                    'handler' => 'listGalleryModules',
                    'description' => 'List the modules registered as gallery-capable in the gallery bridge (id, name, mod_name, mod_key, mod_value), ordered by id descending.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'search' => [
                    'type' => 'string',
                    'description' => 'Search term matched against name, mod_name, mod_key, and mod_value.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'default' => 10,
                    'maximum' => 50,
                    'description' => 'Maximum number of registrations to return (1-50).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Number of registrations to skip.'
                ]
                ],
                    'required' => []
                ]
                ],

            'get_gallery_module' => [
                    'module' => 'mod_gallery_mod',
                    'role' => 'list',
                    'domain' => 'gallery',
                    'handler' => 'getGalleryModule',
                    'description' => 'Get a single gallery module registration with all fields.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Gallery module registration id.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'create_gallery_module' => [
                    'module' => 'mod_gallery_mod',
                    'role' => 'add',
                    'domain' => 'gallery',
                    'handler' => 'createGalleryModule',
                    'description' => 'Register a module as gallery-capable. Required: name, mod_name, mod_key, mod_value (2-255 characters each). mod_name must be a valid module identifier and must not already be registered. mod_key is the target table key column (usually id) and mod_value the record column used to resolve gallery display labels (usually name).',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'name' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Display name of the registration (2-255 characters).'
                ],
                    'mod_name' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Module name to register (2-255 characters, lowercase identifier).'
                ],
                    'mod_key' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Key column of the target module (2-255 characters, usually id).'
                ],
                    'mod_value' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Label column of the target module (2-255 characters, usually name).'
                ]
                ],
                    'required' => [
                    'name',
                    'mod_name',
                    'mod_key',
                    'mod_value'
                ]
                ]
                ],

            'update_gallery_module' => [
                    'module' => 'mod_gallery_mod',
                    'role' => 'edit',
                    'domain' => 'gallery',
                    'handler' => 'updateGalleryModule',
                    'description' => 'Partially update a gallery module registration. Only provided fields change; each must remain 2-255 characters. Editing a registration owned by another admin requires the moderate_edit role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Gallery module registration id.'
                ],
                    'name' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New display name (2-255 characters).'
                ],
                    'mod_name' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New module name (2-255 characters, must not collide with another registration).'
                ],
                    'mod_key' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New key column (2-255 characters).'
                ],
                    'mod_value' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New label column (2-255 characters).'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'delete_gallery_module' => [
                    'module' => 'mod_gallery_mod',
                    'role' => 'delete',
                    'domain' => 'gallery',
                    'handler' => 'deleteGalleryModule',
                    'description' => 'Delete a gallery module registration and remove every image attached to that module, including all generated image files. Deleting a registration owned by another admin requires the moderate_delete role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Gallery module registration id.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'list_headlines' => [
                    'module' => 'mod_headline',
                    'role' => 'list',
                    'domain' => 'headline',
                    'handler' => 'listHeadlines',
                    'description' => 'List headline items with optional filters. Returns paginated items (id, lang, name, status, created_at) plus the total count.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Filter by language code (e.g. ku, en, tr).'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by publish status: y (published) or n (draft).'
                ],
                    'search' => [
                    'type' => 'string',
                    'description' => 'Search term matched against headline item names.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'default' => 10,
                    'description' => 'Page size (1-50, default 10).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'default' => 0,
                    'description' => 'Number of items to skip for pagination.'
                ]
                ]
                ]
                ],

            'get_headline' => [
                    'module' => 'mod_headline',
                    'role' => 'list',
                    'domain' => 'headline',
                    'handler' => 'getHeadline',
                    'description' => 'Get a single headline item with all fields, including the image reference.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Headline item ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'create_headline' => [
                    'module' => 'mod_headline',
                    'role' => 'add',
                    'domain' => 'headline',
                    'handler' => 'createHeadline',
                    'description' => 'Create a headline item. Name is required (2-255 characters) and the creation datetime is stamped automatically. Supports an optional image (resized to 2000x750 plus a 100px thumbnail).',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'name' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'Headline item name (2-255 characters).'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Language code of an active site language (e.g. ku, en, tr).'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'default' => 'y',
                    'description' => 'Publish status: y (published) or n (draft).'
                ],
                    'image' => [
                    'type' => [
                    'object',
                    'null'
                ],
                    'description' => 'Image upload (base64-encoded) or null to explicitly set no image. On update: absent = unchanged.',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ]
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ]
                ],
                    'required' => [
                    'name',
                    'lang'
                ]
                ]
                ],

            'update_headline' => [
                    'module' => 'mod_headline',
                    'role' => 'edit',
                    'domain' => 'headline',
                    'handler' => 'updateHeadline',
                    'description' => 'Partially update a headline item. Only provided fields change; editing another user\'s item requires the moderate_edit role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Headline item ID.'
                ],
                    'name' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'New headline item name.'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'New language code of an active site language.'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'New publish status.'
                ],
                    'created_at' => [
                    'type' => 'string',
                    'description' => 'New creation datetime (YYYY-MM-DD HH:MM:SS).'
                ],
                    'image' => [
                    'type' => [
                    'object',
                    'null'
                ],
                    'description' => 'Image upload (base64-encoded) or null to remove the existing one. Absent = unchanged.',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ]
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'delete_headline' => [
                    'module' => 'mod_headline',
                    'role' => 'delete',
                    'domain' => 'headline',
                    'handler' => 'deleteHeadline',
                    'description' => 'Delete a headline item. Related gallery and attachment relations and the uploaded image file are cleaned up automatically. Deleting another user\'s item requires the moderate_delete role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Headline item ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'list_image_categories' => [
                    'module' => 'mod_imagecategories',
                    'role' => 'list',
                    'domain' => 'imagecategories',
                    'handler' => 'listImageCategories',
                    'description' => 'Lists image gallery categories (mod_imagecategories) with optional filters. Returns display columns, total count and pagination info.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Filter by language code (e.g. \'en\', \'ku\').'
                ],
                    'root' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by root flag: \'y\' for root categories, \'n\' for nested categories.'
                ],
                    'search' => [
                    'type' => 'string',
                    'description' => 'Case-insensitive substring filter on the category name.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'default' => 50,
                    'description' => 'Maximum number of categories to return (capped at 50).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'default' => 0,
                    'description' => 'Number of categories to skip for pagination.'
                ]
                ]
                ]
                ],

            'get_image_category' => [
                    'module' => 'mod_imagecategories',
                    'role' => 'list',
                    'domain' => 'imagecategories',
                    'handler' => 'getImageCategory',
                    'description' => 'Returns a single image gallery category with all fields.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Image category record id.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'create_image_category' => [
                    'module' => 'mod_imagecategories',
                    'role' => 'add',
                    'domain' => 'imagecategories',
                    'handler' => 'createImageCategory',
                    'description' => 'Creates an image gallery category. Mirrors the admin add validator: name 2-255, seo unique per language (auto-generated from name when omitted), title/keywords/description 1-255, parent must exist and match language, one language root (\'##\' name with root \'y\') per language. Optionally uploads an image and attaches the category to a menu.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'name' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'Category name (2-255 characters).'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Content language code; must be an active site language.'
                ],
                    'seo' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 255,
                    'description' => 'SEO slug, unique per language. Auto-generated from the name when omitted.'
                ],
                    'title' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Meta title (1-255 characters). Defaults to the name.'
                ],
                    'keywords' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Meta keywords (1-255 characters). Defaults to the name.'
                ],
                    'description' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Meta description (1-255 characters). Defaults to the name.'
                ],
                    'content' => [
                    'type' => 'string',
                    'description' => 'Optional rich-text category content.'
                ],
                    'parent_id' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Parent category id (0 for top-level). Must belong to the same language; a language root category cannot have a parent.'
                ],
                    'root' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'default' => 'n',
                    'description' => 'Root flag.'
                ],
                    'acl_id' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Access control list id (0 = public).'
                ],
                    'image' => [
                    'oneOf' => [
                    [
                    'type' => 'object',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image data (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ],
                    'description' => 'Image MIME type.'
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ],
                    [
                    'type' => 'null'
                ]
                ],
                    'description' => 'Image upload ({data_base64, mime}). Resized to 400px wide with a 200px wide thumbnail.'
                ],
                    'menu_parent_id' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Menu parent id; when greater than 0 a menu entry pointing to lang/gallery/<seo> is created under it.'
                ]
                ],
                    'required' => [
                    'name',
                    'lang'
                ]
                ]
                ],

            'update_image_category' => [
                    'module' => 'mod_imagecategories',
                    'role' => 'edit',
                    'domain' => 'imagecategories',
                    'handler' => 'updateImageCategory',
                    'description' => 'Partially updates an image gallery category. Only provided fields change. Enforces admin edit validation: name 2-255, seo unique per language, no self-parenting or parent cycles, parent language match, one language root per language. Syncs the attached menu entry (lang/gallery/<seo> URL) and requires moderate_edit for records owned by other users.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Image category record id.'
                ],
                    'name' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'Category name (2-255 characters).'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Content language code; must be an active site language.'
                ],
                    'seo' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 255,
                    'description' => 'SEO slug, unique per language.'
                ],
                    'title' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Meta title (1-255 characters).'
                ],
                    'keywords' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Meta keywords (1-255 characters).'
                ],
                    'description' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Meta description (1-255 characters).'
                ],
                    'content' => [
                    'type' => 'string',
                    'description' => 'Rich-text category content.'
                ],
                    'parent_id' => [
                    'type' => 'integer',
                    'description' => 'Parent category id (0 for top-level). Cannot be the category itself or one of its descendants; must belong to the same language.'
                ],
                    'root' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Root flag.'
                ],
                    'acl_id' => [
                    'type' => 'integer',
                    'description' => 'Access control list id (0 = public).'
                ],
                    'order' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'description' => 'Explicit sibling order. Recomputed automatically when the parent changes.'
                ],
                    'image' => [
                    'oneOf' => [
                    [
                    'type' => 'object',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image data (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ],
                    'description' => 'Image MIME type.'
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ],
                    [
                    'type' => 'null'
                ]
                ],
                    'description' => 'Image upload ({data_base64, mime}) replaces the current image; null removes it; omit to leave untouched. Resized to 400px wide with a 200px wide thumbnail.'
                ],
                    'menu_parent_id' => [
                    'type' => 'integer',
                    'description' => 'Menu parent id. 0 detaches the category menu entry; greater than 0 moves or creates the menu entry under that menu parent.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'delete_image_category' => [
                    'module' => 'mod_imagecategories',
                    'role' => 'delete',
                    'domain' => 'imagecategories',
                    'handler' => 'deleteImageCategory',
                    'description' => 'Deletes an image gallery category and its whole subtree. Cleans up image files, gallery/attachment bridge relations and the attached menu entry. Requires moderate_delete for records owned by other users.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Image category record id; the category and all descendant categories are deleted.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'list_pages' => [
                    'module' => 'mod_page',
                    'role' => 'list',
                    'domain' => 'page',
                    'handler' => 'listPages',
                    'description' => 'List static pages with optional filters, ordered like the admin page tree. Structural per-language root container rows carry root=\'y\' and cannot be deleted.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Filter by language code (e.g. ku, en, tr).'
                ],
                    'parent_id' => [
                    'type' => 'integer',
                    'description' => 'Filter by parent page ID.'
                ],
                    'root' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by root flag: y (structural language root container) or n (regular page).'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by publish status: y (published) or n (draft).'
                ],
                    'deck' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by deck flag: y or n.'
                ],
                    'search' => [
                    'type' => 'string',
                    'description' => 'Search term matched against page names.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'default' => 10,
                    'description' => 'Page size (1-50, default 10).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'default' => 0,
                    'description' => 'Number of items to skip for pagination.'
                ]
                ]
                ]
                ],

            'get_page' => [
                    'module' => 'mod_page',
                    'role' => 'list',
                    'domain' => 'page',
                    'handler' => 'getPage',
                    'description' => 'Get a single page record with all fields, including the full body content, summary, and SEO metadata.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Page record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'create_page' => [
                    'module' => 'mod_page',
                    'role' => 'add',
                    'domain' => 'page',
                    'handler' => 'createPage',
                    'description' => 'Create a static page. The summary must be between 90 and 120 characters (deck summary SEO standard). The parent page (if any), content category, gallery page, and file page references must exist and match the page language; a parent cannot be a structural language root container. The seo slug must be unique per language; it defaults to a slug generated from the name. No menu entry is created automatically.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'name' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Page name (1-255 characters).'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Language code of an active site language (e.g. ku, en, tr).'
                ],
                    'parent_id' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Parent page ID (0 = top level). Must reference a regular page with the same language, never a language root container.'
                ],
                    'content' => [
                    'type' => 'string',
                    'description' => 'Full HTML body of the page.'
                ],
                    'summary' => [
                    'type' => 'string',
                    'minLength' => 90,
                    'maxLength' => 120,
                    'description' => 'Deck summary (exactly 90-120 characters).'
                ],
                    'seo' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'URL slug. Defaults to a slug generated from the name. Must be unique per language.'
                ],
                    'title' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'SEO title. Defaults to the name.'
                ],
                    'keywords' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'SEO keywords. Defaults to empty.'
                ],
                    'description' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'SEO description. Defaults to the summary.'
                ],
                    'root' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'default' => 'n',
                    'description' => 'Structural language root flag: keep n for regular pages.'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'default' => 'y',
                    'description' => 'Publish status: y (published) or n (draft).'
                ],
                    'deck' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'default' => 'n',
                    'description' => 'Show on deck: y or n.'
                ],
                    'gallery_id' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Related gallery ID (gallery_id column references a page ID in this module).'
                ],
                    'file_id' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Related attachment file ID (file_id column references a page ID in this module).'
                ],
                    'acl_id' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Access control group ID (0 = public).'
                ],
                    'category_id' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'ID of an existing content category with the same language.'
                ],
                    'image' => [
                    'type' => [
                    'object',
                    'null'
                ],
                    'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ]
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ]
                ],
                    'required' => [
                    'name',
                    'lang',
                    'content',
                    'summary'
                ]
                ]
                ],

            'update_page' => [
                    'module' => 'mod_page',
                    'role' => 'edit',
                    'domain' => 'page',
                    'handler' => 'updatePage',
                    'description' => 'Partially update a page record. Only provided fields change; the summary (if provided) must be between 90 and 120 characters, and the seo slug (if provided) must remain unique per language. When the name, language, seo, or acl changes, an existing menu entry pointing at the old page URL ({lang}/page/{seo}) is updated automatically.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Page record ID.'
                ],
                    'name' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New page name.'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'New language code of an active site language. References must match it.'
                ],
                    'parent_id' => [
                    'type' => 'integer',
                    'description' => 'New parent page ID (0 = top level). Must reference a regular page with the same language, never a language root container.'
                ],
                    'content' => [
                    'type' => 'string',
                    'description' => 'New full HTML body.'
                ],
                    'summary' => [
                    'type' => 'string',
                    'minLength' => 90,
                    'maxLength' => 120,
                    'description' => 'New deck summary (90-120 characters).'
                ],
                    'seo' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New URL slug, unique per language.'
                ],
                    'title' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New SEO title.'
                ],
                    'keywords' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New SEO keywords.'
                ],
                    'description' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New SEO description.'
                ],
                    'root' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'New structural language root flag.'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'New publish status.'
                ],
                    'deck' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'New deck flag.'
                ],
                    'gallery_id' => [
                    'type' => 'integer',
                    'description' => 'New related gallery ID.'
                ],
                    'file_id' => [
                    'type' => 'integer',
                    'description' => 'New related attachment file ID.'
                ],
                    'acl_id' => [
                    'type' => 'integer',
                    'description' => 'New access control group ID.'
                ],
                    'category_id' => [
                    'type' => 'integer',
                    'description' => 'New content category ID.'
                ],
                    'image' => [
                    'type' => [
                    'object',
                    'null'
                ],
                    'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ]
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'delete_page' => [
                    'module' => 'mod_page',
                    'role' => 'delete',
                    'domain' => 'page',
                    'handler' => 'deletePage',
                    'description' => 'Delete a page and all of its child pages recursively. Structural language root container rows (root=\'y\') cannot be deleted. Related gallery, attachment, and FAQ relations, uploaded image files, and the menu entry pointing at the page URL are cleaned up automatically.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Page record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'list_popups' => [
                    'module' => 'mod_popup',
                    'role' => 'list',
                    'domain' => 'popup',
                    'handler' => 'listPopups',
                    'description' => 'List popups with optional filters. Returns paginated items (id, lang, name, image, publish_at, expired_at, status) plus the total count.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Filter by site language code (e.g. en, tr, ku).'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by publish status. Only one popup per language can be published at a time.'
                ],
                    'search' => [
                    'type' => 'string',
                    'description' => 'Case-insensitive substring search on the popup name.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'description' => 'Maximum number of items to return (default 10, capped at 50).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'description' => 'Number of items to skip for pagination.'
                ]
                ]
                ]
                ],

            'get_popup' => [
                    'module' => 'mod_popup',
                    'role' => 'list',
                    'domain' => 'popup',
                    'handler' => 'getPopup',
                    'description' => 'Get a single popup record with all fields, including the body content, display duration, video source, link, and image.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Popup record id.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'create_popup' => [
                    'module' => 'mod_popup',
                    'role' => 'add',
                    'domain' => 'popup',
                    'handler' => 'createPopup',
                    'description' => 'Create a popup record. Required: lang, name (2-255 characters), publish_at and expired_at datetimes (YYYY-MM-DD HH:MM:SS). New popups are always created unpublished (status n); publishing is exclusive per language and handled through the admin publish flow. Optional: content, duration (max 255), video source (url, youtube, vimeo, instagram; default url), link (max 255), and an image upload.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Site language code for the popup; must be an active language.'
                ],
                    'name' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'Popup title (2-255 characters).'
                ],
                    'publish_at' => [
                    'type' => 'string',
                    'description' => 'Publication start datetime (YYYY-MM-DD HH:MM:SS).'
                ],
                    'expired_at' => [
                    'type' => 'string',
                    'description' => 'Expiration datetime (YYYY-MM-DD HH:MM:SS).'
                ],
                    'content' => [
                    'type' => 'string',
                    'description' => 'Popup body content (HTML).'
                ],
                    'duration' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Popup display duration (max 255 characters).'
                ],
                    'video' => [
                    'type' => 'string',
                    'enum' => [
                    'url',
                    'youtube',
                    'vimeo',
                    'instagram'
                ],
                    'description' => 'Video source type (default url).'
                ],
                    'link' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Optional link target (max 255 characters).'
                ],
                    'image' => [
                    'oneOf' => [
                    [
                    'type' => 'object',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image data (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ],
                    'description' => 'Image MIME type.'
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ],
                    [
                    'type' => 'null',
                    'description' => 'Null is not valid on create; omit the field to create without an image.'
                ]
                ],
                    'description' => 'Image upload for the popup. Resized to 900px wide with a 100px wide thumbnail.'
                ]
                ],
                    'required' => [
                    'lang',
                    'name',
                    'publish_at',
                    'expired_at'
                ]
                ]
                ],

            'update_popup' => [
                    'module' => 'mod_popup',
                    'role' => 'edit',
                    'domain' => 'popup',
                    'handler' => 'updatePopup',
                    'description' => 'Partially update a popup record. Only provided fields change; name (if provided) must stay 2-255 characters and video (if provided) must be one of url, youtube, vimeo, instagram. The status field is not editable here: publishing is exclusive per language and handled through the admin publish flow. Editing a record owned by another admin requires the moderate_edit role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Popup record id.'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Site language code; must be an active language.'
                ],
                    'name' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'Popup title (2-255 characters).'
                ],
                    'publish_at' => [
                    'type' => 'string',
                    'description' => 'Publication start datetime (YYYY-MM-DD HH:MM:SS).'
                ],
                    'expired_at' => [
                    'type' => 'string',
                    'description' => 'Expiration datetime (YYYY-MM-DD HH:MM:SS).'
                ],
                    'content' => [
                    'type' => 'string',
                    'description' => 'Popup body content (HTML).'
                ],
                    'duration' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Popup display duration (max 255 characters).'
                ],
                    'video' => [
                    'type' => 'string',
                    'enum' => [
                    'url',
                    'youtube',
                    'vimeo',
                    'instagram'
                ],
                    'description' => 'Video source type.'
                ],
                    'link' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Optional link target (max 255 characters).'
                ],
                    'image' => [
                    'oneOf' => [
                    [
                    'type' => 'object',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image data (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ],
                    'description' => 'Image MIME type.'
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ],
                    [
                    'type' => 'null',
                    'description' => 'Pass null to remove the current image and delete its files.'
                ]
                ],
                    'description' => 'Image upload ({data_base64, mime}), null to remove, omit to leave untouched. Resized to 900px wide with a 100px wide thumbnail.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'delete_popup' => [
                    'module' => 'mod_popup',
                    'role' => 'delete',
                    'domain' => 'popup',
                    'handler' => 'deletePopup',
                    'description' => 'Delete a popup record. Related gallery and attachment relations and the uploaded image files are cleaned up automatically. Deleting a record owned by another admin requires the moderate_delete role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Popup record id.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'list_redirects' => [
                    'module' => 'mod_redirect',
                    'role' => 'list',
                    'domain' => 'redirect',
                    'handler' => 'listRedirects',
                    'description' => 'List URL redirects with optional search and status code filters. Returns paginated items (id, old_url, new_url, status_code, created_at), the filtered total, and unfiltered stats (total, permanent 301 count, temporary 302 count).',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'status_code' => [
                    'type' => 'integer',
                    'enum' => [
                    301,
                    302
                ],
                    'description' => 'Filter by redirect type: 301 (permanent) or 302 (temporary).'
                ],
                    'search' => [
                    'type' => 'string',
                    'description' => 'Search term matched against old_url and new_url.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'default' => 10,
                    'description' => 'Page size (1-50, default 10).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'default' => 0,
                    'description' => 'Number of items to skip for pagination.'
                ]
                ]
                ]
                ],

            'get_redirect' => [
                    'module' => 'mod_redirect',
                    'role' => 'list',
                    'domain' => 'redirect',
                    'handler' => 'getRedirect',
                    'description' => 'Get a single redirect record with all fields.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Redirect record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'create_redirect' => [
                    'module' => 'mod_redirect',
                    'role' => 'add',
                    'domain' => 'redirect',
                    'handler' => 'createRedirect',
                    'description' => 'Create a URL redirect. The old_url must be 2-250 characters and unique across all redirects; the new_url must be 1-250 characters; the status code must be 301 (permanent, default) or 302 (temporary).',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'old_url' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 250,
                    'description' => 'The old path or URL that should redirect. Must be unique across all redirects.'
                ],
                    'new_url' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 250,
                    'description' => 'The destination path or URL.'
                ],
                    'status_code' => [
                    'type' => 'integer',
                    'enum' => [
                    301,
                    302
                ],
                    'default' => 301,
                    'description' => 'HTTP redirect status: 301 (permanent) or 302 (temporary). Defaults to 301.'
                ]
                ],
                    'required' => [
                    'old_url',
                    'new_url'
                ]
                ]
                ],

            'update_redirect' => [
                    'module' => 'mod_redirect',
                    'role' => 'edit',
                    'domain' => 'redirect',
                    'handler' => 'updateRedirect',
                    'description' => 'Partially update a redirect record. Only provided fields change; the old_url (if provided) must remain unique across all other redirects.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Redirect record ID.'
                ],
                    'old_url' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 250,
                    'description' => 'New old path or URL. Must be unique across all other redirects.'
                ],
                    'new_url' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 250,
                    'description' => 'New destination path or URL.'
                ],
                    'status_code' => [
                    'type' => 'integer',
                    'enum' => [
                    301,
                    302
                ],
                    'description' => 'New HTTP redirect status: 301 (permanent) or 302 (temporary).'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'delete_redirect' => [
                    'module' => 'mod_redirect',
                    'role' => 'delete',
                    'domain' => 'redirect',
                    'handler' => 'deleteRedirect',
                    'description' => 'Delete a redirect record.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Redirect record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'get_seo_settings' => [
                    'module' => 'mod_seo',
                    'role' => 'list',
                    'domain' => 'seo',
                    'handler' => 'getSeoSettings',
                    'description' => 'Read the Google Search Console configuration status: the normalized property domain, whether it is configured, and whether the service account key file exists. Credentials content is never returned.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => (object) []
                ]
                ],

            'update_seo_settings' => [
                    'module' => 'mod_settings',
                    'role' => 'edit',
                    'domain' => 'seo',
                    'handler' => 'updateSeoSettings',
                    'description' => 'Save the Google Search Console property URL (normalized like the admin settings page: scheme and sc-domain: prefix stripped, slashes trimmed; empty string resets the configuration) and optionally replace the service account key JSON file. Mirrors the admin settings() save flow.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'property_url' => [
                    'type' => 'string',
                    'description' => 'Search Console property domain (e.g. example.com or https://example.com). Empty string resets the configuration.'
                ],
                    'credentials_json' => [
                    'type' => 'string',
                    'description' => 'Optional: full Google service account JSON (must contain private_key and client_email) stored as the Search Console API key file.'
                ]
                ],
                    'required' => [
                    'property_url'
                ]
                ]
                ],

            'get_google_settings' => [
                    'module' => 'mod_seo',
                    'role' => 'list',
                    'domain' => 'seo',
                    'handler' => 'getGoogleSettings',
                    'description' => 'Read the Google integrations configuration: bucket name, Gemini API key, and whether the service account credentials file exists.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => (object) []
                ]
                ],

            'update_google_settings' => [
                    'module' => 'mod_settings',
                    'role' => 'edit',
                    'domain' => 'seo',
                    'handler' => 'updateGoogleSettings',
                    'description' => 'Save the Google integrations configuration (bucket name and Gemini API key are always saved as a pair, like the admin form) and optionally replace the service account credentials JSON file. Clears the template caches after saving, mirroring the admin google_settings() flow.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'bucket_name' => [
                    'type' => 'string',
                    'description' => 'Google Cloud Storage bucket name. Saved together with gemini_api_key; use an empty string to clear.'
                ],
                    'gemini_api_key' => [
                    'type' => 'string',
                    'description' => 'Gemini API key used for meta generation. Saved together with bucket_name; use an empty string to clear.'
                ],
                    'credentials_json' => [
                    'type' => 'string',
                    'description' => 'Optional: full Google service account JSON (must contain private_key and client_email) stored as the credentials file.'
                ]
                ],
                    'required' => [
                    'bucket_name',
                    'gemini_api_key'
                ]
                ]
                ],

            'list_sliders' => [
                    'module' => 'mod_slider',
                    'role' => 'list',
                    'domain' => 'slider',
                    'handler' => 'listSliders',
                    'description' => 'List hero slider items with optional filters. Returns paginated items (id, lang, name, url, status, target, created_at) plus the total count.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Filter by language code (e.g. ku, en, tr).'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by publish status: y (published) or n (draft).'
                ],
                    'target' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by open-in-new-tab flag: y or n.'
                ],
                    'search' => [
                    'type' => 'string',
                    'description' => 'Search term matched against slider item names.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'default' => 10,
                    'description' => 'Page size (1-50, default 10).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'default' => 0,
                    'description' => 'Number of items to skip for pagination.'
                ]
                ]
                ]
                ],

            'get_slider' => [
                    'module' => 'mod_slider',
                    'role' => 'list',
                    'domain' => 'slider',
                    'handler' => 'getSlider',
                    'description' => 'Get a single hero slider item with all fields, including description and image references.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Slider item ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'create_slider' => [
                    'module' => 'mod_slider',
                    'role' => 'add',
                    'domain' => 'slider',
                    'handler' => 'createSlider',
                    'description' => 'Create a hero slider item. Name and link url are required (2-255 characters each). Supports a desktop image (resized to 1920x1080 plus a 100px thumbnail) and an optional mobile image (resized to 1080px width).',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'name' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'Slider item name (2-255 characters).'
                ],
                    'url' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'Link target URL (2-255 characters).'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Language code of an active site language (e.g. ku, en, tr).'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'default' => 'y',
                    'description' => 'Publish status: y (published) or n (draft).'
                ],
                    'target' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'default' => 'y',
                    'description' => 'Open the link in a new tab: y or n.'
                ],
                    'created_at' => [
                    'type' => 'string',
                    'description' => 'Creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.'
                ],
                    'description' => [
                    'type' => 'string',
                    'description' => 'Optional long description of the slider item.'
                ],
                    'acl_id' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Access control group ID (0 = public).'
                ],
                    'image' => [
                    'type' => [
                    'object',
                    'null'
                ],
                    'description' => 'Desktop image upload (base64-encoded) or null to explicitly set no image. On update: absent = unchanged.',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ]
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ],
                    'image_mobile' => [
                    'type' => [
                    'object',
                    'null'
                ],
                    'description' => 'Mobile image upload (base64-encoded) or null to explicitly set no mobile image. On update: absent = unchanged.',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ]
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ]
                ],
                    'required' => [
                    'name',
                    'url',
                    'lang'
                ]
                ]
                ],

            'update_slider' => [
                    'module' => 'mod_slider',
                    'role' => 'edit',
                    'domain' => 'slider',
                    'handler' => 'updateSlider',
                    'description' => 'Partially update a hero slider item. Only provided fields change; editing another user\'s item requires the moderate_edit role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Slider item ID.'
                ],
                    'name' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'New slider item name.'
                ],
                    'url' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'New link target URL.'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'New language code of an active site language.'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'New publish status.'
                ],
                    'target' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'New open-in-new-tab flag.'
                ],
                    'created_at' => [
                    'type' => 'string',
                    'description' => 'New creation datetime (YYYY-MM-DD HH:MM:SS).'
                ],
                    'description' => [
                    'type' => 'string',
                    'description' => 'New long description.'
                ],
                    'acl_id' => [
                    'type' => 'integer',
                    'description' => 'New access control group ID.'
                ],
                    'image' => [
                    'type' => [
                    'object',
                    'null'
                ],
                    'description' => 'Desktop image upload (base64-encoded) or null to remove the existing one. Absent = unchanged.',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ]
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ],
                    'image_mobile' => [
                    'type' => [
                    'object',
                    'null'
                ],
                    'description' => 'Mobile image upload (base64-encoded) or null to remove the existing one. Absent = unchanged.',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ]
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'delete_slider' => [
                    'module' => 'mod_slider',
                    'role' => 'delete',
                    'domain' => 'slider',
                    'handler' => 'deleteSlider',
                    'description' => 'Delete a hero slider item. Related gallery, attachment, and FAQ relations and both uploaded image files (desktop and mobile) are cleaned up automatically. Deleting another user\'s item requires the moderate_delete role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Slider item ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'list_surveys' => [
                    'module' => 'mod_survey',
                    'role' => 'list',
                    'domain' => 'survey',
                    'handler' => 'listSurveys',
                    'description' => 'List surveys (polls) with optional filters. Returns paginated items (id, lang, question, status, acl_id, user_id) plus the total count. Status \'y\' marks the active default poll of its language; only one survey per language can be active at a time.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Filter by language code (for example \'en\').'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by status: \'y\' active default poll, \'n\' inactive.'
                ],
                    'search' => [
                    'type' => 'string',
                    'description' => 'Case-insensitive substring search in the question text.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'description' => 'Number of records to return (default 10, maximum 50).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'description' => 'Number of records to skip for pagination (default 0).'
                ]
                ]
                ]
                ],

            'get_survey' => [
                    'module' => 'mod_survey',
                    'role' => 'list',
                    'domain' => 'survey',
                    'handler' => 'getSurvey',
                    'description' => 'Get a single survey (poll) record with all fields plus its voting options (id, option_text, hit) ordered by option id.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'The survey record id.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'create_survey' => [
                    'module' => 'mod_survey',
                    'role' => 'add',
                    'domain' => 'survey',
                    'handler' => 'createSurvey',
                    'description' => 'Create a survey (poll) record. Required: question (2-255 characters), an active language code, and at least two voting options. Status defaults to \'y\' (active default poll of the language) and acl_id defaults to 0 (no access restriction).',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'question' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'The poll question shown to visitors.'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Language code of an active site language (for example \'en\').'
                ],
                    'options' => [
                    'type' => 'array',
                    'minItems' => 2,
                    'items' => [
                    'type' => 'object',
                    'properties' => [
                    'option_text' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 255,
                    'description' => 'The voting option label.'
                ]
                ],
                    'required' => [
                    'option_text'
                ]
                ],
                    'description' => 'At least two voting options for the poll. Vote counters start at zero.'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => '\'y\' makes this the active default poll of the language, \'n\' keeps it inactive. Default \'y\'.'
                ],
                    'acl_id' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'description' => 'Access control list id restricting visibility; 0 means no restriction. Default 0.'
                ]
                ],
                    'required' => [
                    'question',
                    'lang',
                    'options'
                ]
                ]
                ],

            'update_survey' => [
                    'module' => 'mod_survey',
                    'role' => 'edit',
                    'domain' => 'survey',
                    'handler' => 'updateSurvey',
                    'description' => 'Partially update a survey (poll) record. Only provided fields change. When \'options\' is provided it replaces the full option set like the admin edit form: entries with an existing option id update the label and keep their vote counters, entries without an id are appended as new options, and omitted options are deleted. Editing a record owned by another admin requires the moderate_edit role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'The survey record id.'
                ],
                    'question' => [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 255,
                    'description' => 'The poll question shown to visitors.'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Language code of an active site language (for example \'en\').'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => '\'y\' marks the survey as an active default poll candidate, \'n\' inactive. Use set_default_survey to activate one poll per language exclusively.'
                ],
                    'acl_id' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'description' => 'Access control list id restricting visibility; 0 means no restriction.'
                ],
                    'options' => [
                    'type' => 'array',
                    'minItems' => 2,
                    'items' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'Existing option id to update (vote counters preserved). Omit to append a new option.'
                ],
                    'option_text' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 255,
                    'description' => 'The voting option label.'
                ]
                ],
                    'required' => [
                    'option_text'
                ]
                ],
                    'description' => 'Replacement option set (at least two options). Omit the field entirely to leave options untouched.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'delete_survey' => [
                    'module' => 'mod_survey',
                    'role' => 'delete',
                    'domain' => 'survey',
                    'handler' => 'deleteSurvey',
                    'description' => 'Delete a survey (poll) record. Its voting options and recorded voter IP entries are cleaned up automatically. Deleting a record owned by another admin requires the moderate_delete role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'The survey record id.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'set_default_survey' => [
                    'module' => 'mod_survey',
                    'role' => 'moderate_edit',
                    'domain' => 'survey',
                    'handler' => 'setDefaultSurvey',
                    'description' => 'Set one survey as the active default poll of its language (mirrors the admin set_default action): all other surveys of the same language are deactivated (status \'n\') and the selected survey is activated (status \'y\'). Requires the moderate_edit role.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'The survey record id to activate.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'list_videos' => [
                    'module' => 'mod_video',
                    'role' => 'list',
                    'domain' => 'video',
                    'handler' => 'listVideos',
                    'description' => 'List local video records with optional filters. Returns paginated items (id, lang, name, duration, status, slider, image, created_at) plus the total count.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Filter by language code (e.g. ku, en, tr).'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by publish status: y (published) or n (draft).'
                ],
                    'slider' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by hero slider flag.'
                ],
                    'search' => [
                    'type' => 'string',
                    'description' => 'Search term matched against video names.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'default' => 10,
                    'description' => 'Page size (1-50, default 10).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'default' => 0,
                    'description' => 'Number of items to skip for pagination.'
                ]
                ]
                ]
                ],

            'get_video' => [
                    'module' => 'mod_video',
                    'role' => 'list',
                    'domain' => 'video',
                    'handler' => 'getVideo',
                    'description' => 'Get a single local video record with all fields, including the converted file name, original file name, duration, description, and ACL group.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Video record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'create_video' => [
                    'module' => 'mod_video',
                    'role' => 'add',
                    'domain' => 'video',
                    'handler' => 'createVideo',
                    'description' => 'Create a local video record. The duration must be 5-8 characters (e.g. HH:MM:SS). Optionally uploads a thumbnail image (base64-encoded).',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'name' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Video name (2-255 characters).'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Language code of an active site language (e.g. ku, en, tr).'
                ],
                    'duration' => [
                    'type' => 'string',
                    'minLength' => 5,
                    'maxLength' => 8,
                    'description' => 'Video duration (5-8 characters, e.g. HH:MM:SS).'
                ],
                    'description' => [
                    'type' => 'string',
                    'description' => 'Optional video description.'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'default' => 'y',
                    'description' => 'Publish status: y (published) or n (draft).'
                ],
                    'slider' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'default' => 'n',
                    'description' => 'Show in hero slider flag.'
                ],
                    'flv' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'File name of the converted video under static/mod_video/flv. Optional.'
                ],
                    'original' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'File name of the original upload under static/mod_video/original. Optional.'
                ],
                    'acl_id' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Access control group ID.'
                ],
                    'created_at' => [
                    'type' => 'string',
                    'description' => 'Creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.'
                ],
                    'image' => [
                    'type' => [
                    'object',
                    'null'
                ],
                    'description' => 'Upload a thumbnail image (base64-encoded) or null to skip. On create: absent = no image.',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ]
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ]
                ],
                    'required' => [
                    'name',
                    'lang',
                    'duration'
                ]
                ]
                ],

            'update_video' => [
                    'module' => 'mod_video',
                    'role' => 'edit',
                    'domain' => 'video',
                    'handler' => 'updateVideo',
                    'description' => 'Partially update a local video record. Only supplied fields change; the image argument uploads a new thumbnail (base64) or null removes the existing one. Absent = unchanged.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Video record ID.'
                ],
                    'name' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New video name (2-255 characters).'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'New language code of an active site language.'
                ],
                    'duration' => [
                    'type' => 'string',
                    'minLength' => 5,
                    'maxLength' => 8,
                    'description' => 'New video duration (5-8 characters, e.g. HH:MM:SS).'
                ],
                    'description' => [
                    'type' => 'string',
                    'description' => 'New video description.'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'New publish status: y (published) or n (draft).'
                ],
                    'slider' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'New hero slider flag.'
                ],
                    'flv' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New converted video file name.'
                ],
                    'original' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New original video file name.'
                ],
                    'acl_id' => [
                    'type' => 'integer',
                    'description' => 'New access control group ID.'
                ],
                    'created_at' => [
                    'type' => 'string',
                    'description' => 'New creation datetime (YYYY-MM-DD HH:MM:SS).'
                ],
                    'image' => [
                    'type' => [
                    'object',
                    'null'
                ],
                    'description' => 'Upload a new thumbnail (base64-encoded) or null to remove the existing one. On update: absent = unchanged.',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ]
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'delete_video' => [
                    'module' => 'mod_video',
                    'role' => 'delete',
                    'domain' => 'video',
                    'handler' => 'deleteVideo',
                    'description' => 'Delete a local video record. Related gallery/attachment relations, the converted and original video files, and the thumbnail image are cleaned up automatically.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Video record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'list_video_embeds' => [
                    'module' => 'mod_video_embed',
                    'role' => 'list',
                    'domain' => 'video',
                    'handler' => 'listVideoEmbeds',
                    'description' => 'List embedded video records with optional filters. Returns paginated items (id, lang, category_id, name, duration, status, slider, image, created_at) plus the total count.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'category_id' => [
                    'type' => 'integer',
                    'description' => 'Filter by video embed category ID.'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Filter by language code (e.g. ku, en, tr).'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by publish status: y (published) or n (draft).'
                ],
                    'slider' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by hero slider flag.'
                ],
                    'search' => [
                    'type' => 'string',
                    'description' => 'Search term matched against video names.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'default' => 10,
                    'description' => 'Page size (1-50, default 10).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'default' => 0,
                    'description' => 'Number of items to skip for pagination.'
                ]
                ]
                ]
                ],

            'get_video_embed' => [
                    'module' => 'mod_video_embed',
                    'role' => 'list',
                    'domain' => 'video',
                    'handler' => 'getVideoEmbed',
                    'description' => 'Get a single embedded video record with all fields, including the embed code, body content, SEO metadata, and thumbnail.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Video embed record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'create_video_embed' => [
                    'module' => 'mod_video_embed',
                    'role' => 'add',
                    'domain' => 'video',
                    'handler' => 'createVideoEmbed',
                    'description' => 'Create an embedded video record (e.g. YouTube). The category must exist and match the video language. The content field is sanitized through the same HTML purifier as the admin form. Emoji in name, content, title, keywords, or description are rejected, mirroring the admin validator.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'name' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Video name (2-255 characters).'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Language code of an active site language (e.g. ku, en, tr).'
                ],
                    'category_id' => [
                    'type' => 'integer',
                    'description' => 'ID of an existing video embed category with the same language.'
                ],
                    'duration' => [
                    'type' => 'string',
                    'minLength' => 5,
                    'maxLength' => 8,
                    'description' => 'Video duration (5-8 characters, e.g. HH:MM:SS).'
                ],
                    'embed' => [
                    'type' => 'string',
                    'description' => 'Embed code or URL (e.g. a YouTube iframe embed). Required.'
                ],
                    'content' => [
                    'type' => 'string',
                    'description' => 'Full HTML body of the video description. Required; sanitized like the admin form.'
                ],
                    'title' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'SEO title (1-255 characters). Required.'
                ],
                    'keywords' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'SEO keywords (1-255 characters). Required.'
                ],
                    'description' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'SEO description (1-255 characters). Required.'
                ],
                    'original' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Original video URL (e.g. the YouTube watch URL). Optional.'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'default' => 'y',
                    'description' => 'Publish status: y (published) or n (draft).'
                ],
                    'slider' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'default' => 'n',
                    'description' => 'Show in hero slider flag.'
                ],
                    'acl_id' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Access control group ID.'
                ],
                    'created_at' => [
                    'type' => 'string',
                    'description' => 'Creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.'
                ],
                    'image' => [
                    'type' => [
                    'object',
                    'null'
                ],
                    'description' => 'Upload a thumbnail image (base64-encoded) or null to skip. On create: absent = no image.',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ]
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ]
                ],
                    'required' => [
                    'name',
                    'lang',
                    'category_id',
                    'duration',
                    'embed',
                    'content',
                    'title',
                    'keywords',
                    'description'
                ]
                ]
                ],

            'update_video_embed' => [
                    'module' => 'mod_video_embed',
                    'role' => 'edit',
                    'domain' => 'video',
                    'handler' => 'updateVideoEmbed',
                    'description' => 'Partially update an embedded video record. The category must match the (possibly new) language. Emoji are rejected in name, content, title, keywords, and description. The image argument uploads a new thumbnail (base64) or null removes the existing one. Absent = unchanged.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Video embed record ID.'
                ],
                    'name' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New video name (2-255 characters).'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'New language code of an active site language.'
                ],
                    'category_id' => [
                    'type' => 'integer',
                    'description' => 'New video embed category ID; must match the video language.'
                ],
                    'duration' => [
                    'type' => 'string',
                    'minLength' => 5,
                    'maxLength' => 8,
                    'description' => 'New video duration (5-8 characters, e.g. HH:MM:SS).'
                ],
                    'embed' => [
                    'type' => 'string',
                    'description' => 'New embed code or URL. Cannot be empty.'
                ],
                    'content' => [
                    'type' => 'string',
                    'description' => 'New full HTML body; sanitized like the admin form. Cannot be empty.'
                ],
                    'title' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New SEO title (1-255 characters).'
                ],
                    'keywords' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New SEO keywords (1-255 characters).'
                ],
                    'description' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New SEO description (1-255 characters).'
                ],
                    'original' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New original video URL.'
                ],
                    'status' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'New publish status: y (published) or n (draft).'
                ],
                    'slider' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'New hero slider flag.'
                ],
                    'acl_id' => [
                    'type' => 'integer',
                    'description' => 'New access control group ID.'
                ],
                    'created_at' => [
                    'type' => 'string',
                    'description' => 'New creation datetime (YYYY-MM-DD HH:MM:SS).'
                ],
                    'image' => [
                    'type' => [
                    'object',
                    'null'
                ],
                    'description' => 'Upload a new thumbnail (base64-encoded) or null to remove the existing one. On update: absent = unchanged.',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ]
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'delete_video_embed' => [
                    'module' => 'mod_video_embed',
                    'role' => 'delete',
                    'domain' => 'video',
                    'handler' => 'deleteVideoEmbed',
                    'description' => 'Delete an embedded video record. Related gallery/attachment relations and the thumbnail image files are cleaned up automatically.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Video embed record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'list_video_embed_categories' => [
                    'module' => 'mod_video_embed_category',
                    'role' => 'list',
                    'domain' => 'video',
                    'handler' => 'listVideoEmbedCategories',
                    'description' => 'List video embed categories with optional filters. Returns paginated items ordered by sort order plus the total count.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Filter by language code (e.g. ku, en, tr).'
                ],
                    'root' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'Filter by language-root flag.'
                ],
                    'search' => [
                    'type' => 'string',
                    'description' => 'Search term matched against category names.'
                ],
                    'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'default' => 50,
                    'description' => 'Page size (1-50, default 50).'
                ],
                    'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'default' => 0,
                    'description' => 'Number of items to skip for pagination.'
                ]
                ]
                ]
                ],

            'get_video_embed_category' => [
                    'module' => 'mod_video_embed_category',
                    'role' => 'list',
                    'domain' => 'video',
                    'handler' => 'getVideoEmbedCategory',
                    'description' => 'Get a single video embed category with all fields, including parent, sort order, root flag, SEO metadata, and image.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Video embed category record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'create_video_embed_category' => [
                    'module' => 'mod_video_embed_category',
                    'role' => 'add',
                    'domain' => 'video',
                    'handler' => 'createVideoEmbedCategory',
                    'description' => 'Create a video embed category. The seo slug must be unique per language and defaults to a slug generated from the name. A parent_id of 0 is only allowed while the language has no language-root category yet. Optionally creates a menu entry pointing at lang/videos/{seo}.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'name' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Category name (2-255 characters).'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'Language code of an active site language (e.g. ku, en, tr).'
                ],
                    'parent_id' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Parent category ID. 0 is only allowed while the language has no language-root category.'
                ],
                    'root' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'default' => 'n',
                    'description' => 'Language-root flag.'
                ],
                    'seo' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'URL slug. Defaults to a slug generated from the name. Must be unique per language.'
                ],
                    'title' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'SEO title (1-255 characters). Defaults to the name.'
                ],
                    'keywords' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'SEO keywords (1-255 characters). Required.'
                ],
                    'description' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'SEO description (1-255 characters). Required.'
                ],
                    'menu' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Menu parent ID; when non-zero a menu entry linking to this category is created.'
                ],
                    'acl_id' => [
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Access control group ID applied to the created menu entry.'
                ],
                    'image' => [
                    'type' => [
                    'object',
                    'null'
                ],
                    'description' => 'Upload a category image (base64-encoded) or null to skip. On create: absent = no image.',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ]
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ]
                ],
                    'required' => [
                    'name',
                    'lang',
                    'keywords',
                    'description'
                ]
                ]
                ],

            'update_video_embed_category' => [
                    'module' => 'mod_video_embed_category',
                    'role' => 'edit',
                    'domain' => 'video',
                    'handler' => 'updateVideoEmbedCategory',
                    'description' => 'Partially update a video embed category. Moving a category re-appends it after its new siblings and keeps the linked menu entry in sync (rename, re-parent, or delete it when menu=0). The image argument uploads a new image (base64) or null removes the existing one. Absent = unchanged.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Video embed category record ID.'
                ],
                    'name' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New category name (2-255 characters).'
                ],
                    'lang' => [
                    'type' => 'string',
                    'description' => 'New language code of an active site language.'
                ],
                    'parent_id' => [
                    'type' => 'integer',
                    'description' => 'New parent category ID. Cannot be the category itself or one of its descendants.'
                ],
                    'root' => [
                    'type' => 'string',
                    'enum' => [
                    'y',
                    'n'
                ],
                    'description' => 'New language-root flag.'
                ],
                    'seo' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New URL slug; must be unique per language. Linked menu URLs are updated.'
                ],
                    'title' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New SEO title (1-255 characters).'
                ],
                    'keywords' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New SEO keywords (1-255 characters).'
                ],
                    'description' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'New SEO description (1-255 characters).'
                ],
                    'order' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'description' => 'New sort order. Defaults to appending after the siblings when the parent changes.'
                ],
                    'menu' => [
                    'type' => 'integer',
                    'description' => 'New menu parent ID: 0 deletes the linked menu entry, non-zero re-parents or creates it. Absent = keep the existing entry aligned.'
                ],
                    'acl_id' => [
                    'type' => 'integer',
                    'description' => 'New access control group ID for the linked menu entry.'
                ],
                    'image' => [
                    'type' => [
                    'object',
                    'null'
                ],
                    'description' => 'Upload a new image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.',
                    'properties' => [
                    'data_base64' => [
                    'type' => 'string',
                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                ],
                    'mime' => [
                    'type' => 'string',
                    'enum' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ]
                ]
                ],
                    'required' => [
                    'data_base64',
                    'mime'
                ]
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'delete_video_embed_category' => [
                    'module' => 'mod_video_embed_category',
                    'role' => 'delete',
                    'domain' => 'video',
                    'handler' => 'deleteVideoEmbedCategory',
                    'description' => 'Delete a video embed category and all of its child categories recursively. Related images, gallery/attachment relations, and linked menu entries are cleaned up automatically.',
                    'schema' => [
                    'type' => 'object',
                    'properties' => [
                    'id' => [
                    'type' => 'integer',
                    'description' => 'Video embed category record ID.'
                ]
                ],
                    'required' => [
                    'id'
                ]
                ]
                ],

            'list_affiliations' => [
                'module' => 'mod_settings',
                'role' => 'list',
                'domain' => 'settings',
                'handler' => 'listAffiliations',
                'description' => 'List institutional affiliations and partnerships with optional status filtering and pagination.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'is_active' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by active status.'],
                        'search' => ['type' => 'string', 'description' => 'Substring search in institution name or URL.'],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20, 'description' => 'Number of items to return.'],
                        'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Number of items to skip.'],
                    ],
                ],
            ],

            'get_affiliation' => [
                'module' => 'mod_settings',
                'role' => 'list',
                'domain' => 'settings',
                'handler' => 'getAffiliation',
                'description' => 'Get a single institutional affiliation record by ID.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Affiliation ID.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            'create_affiliation' => [
                'module' => 'mod_settings',
                'role' => 'edit',
                'domain' => 'settings',
                'handler' => 'createAffiliation',
                'description' => 'Create a new institutional affiliation or partnership entry.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 150, 'description' => 'Institution or partner name.'],
                        'url' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 500, 'description' => 'Destination URL link.'],
                        'is_active' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'y', 'description' => 'Display status in marquee band.'],
                        'display_order' => ['type' => 'integer', 'description' => 'Sort order (defaults to next order).'],
                        'logo' => [
                            'type' => 'object',
                            'description' => 'Logo file upload (base64 encoded).',
                            'properties' => [
                                'data_base64' => ['type' => 'string', 'description' => 'Base64 encoded image data.'],
                                'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'], 'description' => 'MIME type.']
                            ],
                            'required' => ['data_base64', 'mime']
                        ]
                    ],
                    'required' => ['name', 'url'],
                ],
            ],

            'update_affiliation' => [
                'module' => 'mod_settings',
                'role' => 'edit',
                'domain' => 'settings',
                'handler' => 'updateAffiliation',
                'description' => 'Partially update an institutional affiliation record.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Affiliation ID to update.'],
                        'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 150, 'description' => 'Institution name.'],
                        'url' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 500, 'description' => 'URL link.'],
                        'is_active' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Active status.'],
                        'display_order' => ['type' => 'integer', 'description' => 'Sort order.'],
                        'logo' => [
                            'type' => ['object', 'null'],
                            'description' => 'Upload a new logo or null to remove existing.',
                            'properties' => [
                                'data_base64' => ['type' => 'string', 'description' => 'Base64 encoded image data.'],
                                'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'], 'description' => 'MIME type.']
                            ],
                            'required' => ['data_base64', 'mime']
                        ]
                    ],
                    'required' => ['id'],
                ],
            ],

            'delete_affiliation' => [
                'module' => 'mod_settings',
                'role' => 'edit',
                'domain' => 'settings',
                'handler' => 'deleteAffiliation',
                'description' => 'Delete an institutional affiliation record and cleans up logo files.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Affiliation ID to delete.'],
                    ],
                    'required' => ['id'],
                ],
            ],

            // ------------------------------------------------------------------
            // Appointment tools (mod_appointment)
            // ------------------------------------------------------------------
        ];
    }
}
