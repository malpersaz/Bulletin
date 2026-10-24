<?php
/**
 * Amed Framework
 * MCP Resource Manager - Handles MCP resources/list and resources/read
 * lib/McpResourceManager.class.php
 */

require_once(lib_dir . 'McpToolException.php');

class McpResourceManager
{
    private ?array $authKey;
    private array $groupPermissions;

    public function __construct(?array $authKey, array $groupPermissions)
    {
        $this->authKey = $authKey;
        $this->groupPermissions = $groupPermissions;
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

    public function listResources(): array
    {
        $resources = [
            [
                'uri' => 'cms://site/info',
                'name' => 'Site Information',
                'description' => 'Public site configuration, active languages, branding, and status.',
                'mimeType' => 'application/json',
            ],
            [
                'uri' => 'cms://modules/active',
                'name' => 'Active Modules',
                'description' => 'List of active installed system modules and schema status.',
                'mimeType' => 'application/json',
            ],
        ];

        return ['resources' => $resources];
    }

    public function listResourceTemplates(): array
    {
        return [
            'resourceTemplates' => [
                [
                    'uriTemplate' => 'cms://news/{id}',
                    'name' => 'News Article by ID',
                    'description' => 'Direct read access to any published news article by ID.',
                    'mimeType' => 'application/json',
                ],
                [
                    'uriTemplate' => 'cms://publication/{id}',
                    'name' => 'Publication by ID',
                    'description' => 'Direct read access to any publication record by ID.',
                    'mimeType' => 'application/json',
                ],
                [
                    'uriTemplate' => 'cms://page/{id}',
                    'name' => 'Page by ID',
                    'description' => 'Direct read access to any static page record by ID.',
                    'mimeType' => 'application/json',
                ],
                [
                    'uriTemplate' => 'cms://announcement/{id}',
                    'name' => 'Announcement by ID',
                    'description' => 'Direct read access to any announcement record by ID.',
                    'mimeType' => 'application/json',
                ],
            ],
        ];
    }

    public function readResource(array $params): array
    {
        $uri = trim((string) ($params['uri'] ?? ''));
        if ($uri === '') {
            throw new McpToolException("Parameter 'uri' is required.");
        }

        // Explicitly disallow exposure of raw database schemas or sensitive logs
        if (str_starts_with($uri, 'cms://database') || str_starts_with($uri, 'cms://logs') || str_starts_with($uri, 'cms://config')) {
            throw new McpToolException("Access denied: Inspection of internal system resource '{$uri}' is prohibited.");
        }

        if ($uri === 'cms://site/info') {
            return $this->readSiteInfo($uri);
        }

        if ($uri === 'cms://modules/active') {
            return $this->readActiveModules($uri);
        }

        if (preg_match('#^cms://news/(\d+)$#', $uri, $matches)) {
            return $this->readEntity($uri, 'mod_news', 'mod_news', (int) $matches[1], 'News');
        }

        if (preg_match('#^cms://publication/(\d+)$#', $uri, $matches)) {
            return $this->readEntity($uri, 'mod_publication', 'mod_publication', (int) $matches[1], 'Publication');
        }

        if (preg_match('#^cms://page/(\d+)$#', $uri, $matches)) {
            return $this->readEntity($uri, 'mod_page', 'mod_page', (int) $matches[1], 'Page');
        }

        if (preg_match('#^cms://announcement/(\d+)$#', $uri, $matches)) {
            return $this->readEntity($uri, 'mod_announcements', 'mod_announcements', (int) $matches[1], 'Announcement');
        }

        throw new McpToolException("Resource not found: {$uri}");
    }

    private function readSiteInfo(string $uri): array
    {
        $siteConfig = Registry::get('site_config') ?? [];
        $db = Load::lib('Database');
        $db->fetch_array("SELECT name, seo, is_default FROM lang WHERE status = 'y' ORDER BY is_default DESC");
        $languages = $db->toArray() ?: [];

        $payload = [
            'name' => $siteConfig['title'] ?? '',
            'address' => $siteConfig['address'] ?? '',
            'site_status' => $siteConfig['site_status'] ?? 'online',
            'languages' => $languages,
        ];

        return [
            'contents' => [
                [
                    'uri' => $uri,
                    'mimeType' => 'application/json',
                    'text' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ],
            ],
        ];
    }

    private function readActiveModules(string $uri): array
    {
        $db = Load::lib('Database');
        $db->fetch_array("SELECT mod_id, mod_name, status, schema_ready FROM mods WHERE status = 'ready' ORDER BY mod_name ASC");
        $modules = $db->toArray() ?: [];

        return [
            'contents' => [
                [
                    'uri' => $uri,
                    'mimeType' => 'application/json',
                    'text' => json_encode($modules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ],
            ],
        ];
    }

    private function readEntity(string $uri, string $modName, string $modelName, int $id, string $label): array
    {
        if (!$this->hasPermission($modName, 'list')) {
            throw new McpToolException("Access denied: '{$modName}:list' permission required to inspect resource '{$uri}'.");
        }

        $class = Load::modelfile($modelName);
        if (!$class || !class_exists($class)) {
            throw new McpToolException("{$label} module model is unavailable.");
        }

        $model = new $class();
        $model->load($id);

        if (!$model->id) {
            throw new McpToolException("{$label} record #{$id} not found.");
        }

        $data = $model->toArray();

        return [
            'contents' => [
                [
                    'uri' => $uri,
                    'mimeType' => 'application/json',
                    'text' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ],
            ],
        ];
    }
}
