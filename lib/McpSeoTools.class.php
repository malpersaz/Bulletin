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

        return [
            'bucket_name' => $bucketName,
            'gemini_api_key' => $geminiApiKey,
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
