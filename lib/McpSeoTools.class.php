<?php
/**
 * Amed Framework
 * MCP SEO Tools - file-based SEO configuration for mod_seo
 * lib/McpSeoTools.class.php
 *
 * mod_seo stores its configuration in JSON files under data/ (no database
 * tables). The tools below mirror the admin settings()/google_settings()
 * save and read semantics exactly, including domain normalization,
 * service-account credential validation, and template cache clearing.
 */

class McpSeoTools extends McpCrudToolsBase
{
    /**
     * Return array of tool definitions provided by this domain tool class.
     *
     * @return array<string, array>
     */
    public static function definitions(): array
    {
        return [
            'get_seo_settings' => [
                'module' => 'mod_seo',
                'role' => 'list',
                'domain' => 'seo',
                'handler' => 'getSeoSettings',
                'description' => 'Read the Google Search Console configuration status: the normalized property domain, whether it is configured, and whether the service account key file exists. Credentials content is never returned.',
                'schema' => [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
                'title' => 'Get SEO Settings',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
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
                            'description' => 'Search Console property domain (e.g. example.com or https://example.com). Empty string resets the configuration.',
                        ],
                        'credentials_json' => [
                            'type' => 'string',
                            'description' => 'Optional: full Google service account JSON (must contain private_key and client_email) stored as the Search Console API key file.',
                        ],
                    ],
                    'required' => [
                        'property_url',
                    ],
                ],
                'title' => 'Update SEO Settings',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_google_settings' => [
                'module' => 'mod_settings',
                'role' => 'list',
                'domain' => 'seo',
                'handler' => 'getGoogleSettings',
                'description' => 'Read the Google integrations configuration: bucket name, Gemini API key, and whether the service account credentials file exists.',
                'schema' => [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
                'title' => 'Get Google Settings',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
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
                            'description' => 'Google Cloud Storage bucket name. Saved together with gemini_api_key; use an empty string to clear.',
                        ],
                        'gemini_api_key' => [
                            'type' => 'string',
                            'description' => 'Gemini API key used for meta generation. Saved together with bucket_name; use an empty string to clear.',
                        ],
                        'credentials_json' => [
                            'type' => 'string',
                            'description' => 'Optional: full Google service account JSON (must contain private_key and client_email) stored as the credentials file.',
                        ],
                    ],
                    'required' => [
                        'bucket_name',
                        'gemini_api_key',
                    ],
                ],
                'title' => 'Update Google Settings',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
        ];
    }

    private const SETTINGS_FILE = 'seo_settings.json';
    private const SEARCH_CONSOLE_KEY_FILE = 'google_search_console_api.json';
    private const GOOGLE_SETTINGS_FILE = 'google_settings.json';
    private const GOOGLE_CREDENTIALS_FILE = 'credentials.json';

    // ------------------------------------------------------------------
    // Search Console settings (data/seo_settings.json)
    // ------------------------------------------------------------------

    /**
     * Returns the Search Console configuration status
     */
    public function getSeoSettings(array $args): array
    {
        $settingsPath = $this->dataPath(self::SETTINGS_FILE);
        $propertyUrl = '';
        $isConfigured = false;

        if (file_exists($settingsPath)) {
            $decoded = json_decode((string) file_get_contents($settingsPath), true);
            $propertyUrl = $this->cleanDomain((string) ($decoded['property_url'] ?? ''));
            if ($propertyUrl !== '' && !str_contains($propertyUrl, 'localhost')) {
                $isConfigured = true;
            } else {
                $propertyUrl = '';
            }
        }

        return [
            'property_url' => $propertyUrl,
            'is_configured' => $isConfigured,
            'has_key_file' => file_exists($this->dataPath(self::SEARCH_CONSOLE_KEY_FILE)),
            'has_credentials' => $isConfigured && file_exists($this->dataPath(self::SEARCH_CONSOLE_KEY_FILE)),
        ];
    }

    /**
     * Saves the Search Console property URL and optionally the service
     * account key JSON (mirrors the admin settings() POST flow)
     */
    public function updateSeoSettings(array $args): array
    {
        if (!array_key_exists('property_url', $args)) {
            throw new McpToolException("Field 'property_url' is required (use an empty string to reset the configuration).");
        }

        $propertyUrl = $this->cleanDomain(trim((string) $args['property_url']));
        $this->writeJsonFile($this->dataPath(self::SETTINGS_FILE), ['property_url' => $propertyUrl]);

        if (array_key_exists('credentials_json', $args)) {
            $this->storeCredentialsJson($this->dataPath(self::SEARCH_CONSOLE_KEY_FILE), (string) $args['credentials_json']);
        }

        return [
            'updated' => true,
            'item' => $this->getSeoSettings($args),
        ];
    }

    // ------------------------------------------------------------------
    // Google integrations settings (data/google_settings.json)
    // ------------------------------------------------------------------

    /**
     * Returns the Google integrations configuration
     */
    public function getGoogleSettings(array $args): array
    {
        $bucketName = '';
        $geminiApiKey = '';

        $settingsPath = $this->dataPath(self::GOOGLE_SETTINGS_FILE);
        if (file_exists($settingsPath)) {
            $decoded = json_decode((string) file_get_contents($settingsPath), true);
            if (is_array($decoded)) {
                $bucketName = (string) ($decoded['bucket_name'] ?? '');
                $geminiApiKey = (string) ($decoded['gemini_api_key'] ?? '');
            }
        }

        $hasGeminiKey = $geminiApiKey !== '';
        $maskedKey = '';
        if ($hasGeminiKey) {
            $maskedKey = mb_strlen($geminiApiKey) > 8
                ? mb_substr($geminiApiKey, 0, 4) . str_repeat('*', 8) . mb_substr($geminiApiKey, -4)
                : '********';
        }

        return [
            'bucket_name' => $bucketName,
            'gemini_api_key' => $maskedKey,
            'has_gemini_api_key' => $hasGeminiKey,
            'has_credentials' => file_exists($this->dataPath(self::GOOGLE_CREDENTIALS_FILE)),
        ];
    }

    /**
     * Saves the Google integrations configuration and clears the template
     * caches so end-users see updates instantly (mirrors the admin
     * google_settings() POST flow)
     */
    public function updateGoogleSettings(array $args): array
    {
        if (!array_key_exists('bucket_name', $args) || !array_key_exists('gemini_api_key', $args)) {
            throw new McpToolException("Fields 'bucket_name' and 'gemini_api_key' are both required (the admin form always saves the pair; use empty strings to clear).");
        }

        $bucketName = trim((string) $args['bucket_name']);
        $geminiApiKey = trim((string) $args['gemini_api_key']);

        $this->writeJsonFile($this->dataPath(self::GOOGLE_SETTINGS_FILE), [
            'bucket_name' => $bucketName,
            'gemini_api_key' => $geminiApiKey,
        ]);

        if (array_key_exists('credentials_json', $args)) {
            $this->storeCredentialsJson($this->dataPath(self::GOOGLE_CREDENTIALS_FILE), (string) $args['credentials_json']);
        }

        $this->clearTemplateCaches();

        return [
            'updated' => true,
            'item' => $this->getGoogleSettings($args),
        ];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function dataPath(string $fileName): string
    {
        return root_dir . 'data' . DS . $fileName;
    }

    /**
     * Normalizes a Search Console property domain exactly like the admin
     * settings page (strips scheme and sc-domain: prefix, trims slashes)
     */
    protected function cleanDomain(string $inputDomain): string
    {
        $cleanDomain = preg_replace('#^https?://#i', '', $inputDomain) ?? '';
        $cleanDomain = preg_replace('#^sc-domain:#i', '', $cleanDomain) ?? '';
        return trim($cleanDomain, " /\t\n\r\0\x0B");
    }

    /**
     * Validates a service account JSON payload (must contain private_key
     * and client_email) and stores it verbatim, mirroring the admin upload
     */
    private function storeCredentialsJson(string $targetPath, string $credentialsJson): void
    {
        $decoded = json_decode($credentialsJson, true);
        if (!is_array($decoded) || !isset($decoded['private_key']) || !isset($decoded['client_email'])) {
            throw new McpToolException("Field 'credentials_json' must be a Google service account JSON containing 'private_key' and 'client_email'.");
        }

        if (file_put_contents($targetPath, $credentialsJson) === false) {
            throw new McpToolException('Failed to write the credentials file.');
        }
    }

    private function writeJsonFile(string $path, array $settings): void
    {
        if (file_put_contents($path, json_encode($settings, JSON_PRETTY_PRINT)) === false) {
            throw new McpToolException('Failed to write the settings file: ' . basename($path) . '.');
        }
    }

    /**
     * Clears the Twig template caches, mirroring the admin save flow
     */
    private function clearTemplateCaches(): void
    {
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
    }
}
