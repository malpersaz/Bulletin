<?php
/**
 * Amed Framework
 * MCP CRUD Tools Base - Shared helpers for MCP domain tool classes
 * lib/McpCrudToolsBase.class.php
 */

require_once(lib_dir . 'McpToolException.php');

abstract class McpCrudToolsBase
{
    protected const KURDISH_LOCALES = ['ku', 'ckb', 'zza'];

    /**
     * User ID attributed to records created or updated through MCP
     */
    protected int $actingUserId;

    /**
     * Group permissions: mod_name => role => true
     *
     * @var array<string, array<string, bool>>
     */
    protected array $groupPermissions;

    protected const MAX_IMAGE_BYTES = 10485760; // 10 MB decoded

    protected const ALLOWED_IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public function __construct(int $actingUserId, array $groupPermissions)
    {
        $this->actingUserId = $actingUserId;
        $this->groupPermissions = $groupPermissions;
    }

    // ------------------------------------------------------------------
    // Model and lookup helpers
    // ------------------------------------------------------------------

    protected function freshModel(string $name): ORM
    {
        $class = Load::modelfile($name);
        return new $class();
    }

    protected function requireId(array $args): int
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            throw new McpToolException("Field 'id' is required and must be a positive integer.");
        }
        return $id;
    }

    protected function loadRecordById(string $modelName, int $id, string $label): ORM
    {
        $model = $this->freshModel($modelName);
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("{$label} record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    protected function recordSeoExists(string $modelName, string $seo, string $lang, int $excludeId = 0): bool
    {
        $model = $this->freshModel($modelName);
        $model->where_seo_and_lang($seo, $lang);
        if ($excludeId > 0) {
            $model->addWhere('id <> ' . (int) $excludeId);
        }
        $model->limit(1)->getResultOne(true, true);
        return $model->getSize() > 0;
    }

    protected function nextSiblingOrder(string $modelName, int $parentId): int
    {
        $model = $this->freshModel($modelName);
        $model->where_parent_id($parentId)->order('`order`', 'DESC')->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            return 1;
        }
        $model->sync();
        return (int) $model->order + 1;
    }

    // ------------------------------------------------------------------
    // Validation helpers
    // ------------------------------------------------------------------

    protected function requireActiveLang(array $args): string
    {
        $lang = trim((string) ($args['lang'] ?? ''));
        if ($lang === '') {
            throw new McpToolException("Field 'lang' is required.");
        }

        $db = Load::lib('Database');
        $db->fetch_array('SELECT seo FROM lang WHERE seo = \'' . Obuilder::cc($lang) . '\' AND status = \'y\' LIMIT 1');
        if ($db->getSize() === 0) {
            throw new McpToolException("Language '{$lang}' is not an active site language.");
        }

        return $lang;
    }

    protected function assertRecordMatchesLang(string $modelName, int $recordId, string $lang, string $label): void
    {
        $record = $this->loadRecordById($modelName, $recordId, $label);
        if ((string) $record->lang !== $lang) {
            throw new McpToolException("{$label} '{$recordId}' belongs to language '" . $record->lang . "', content language is '{$lang}'. They must match.");
        }
    }

    protected function assertModerateDelete(string $module, int $recordUserId): void
    {
        if ($recordUserId !== $this->actingUserId) {
            $this->assertPermission($module, 'moderate_delete');
        }
    }

    protected function assertModerateEdit(string $module, int $recordUserId): void
    {
        if ($recordUserId !== $this->actingUserId) {
            $this->assertPermission($module, 'moderate_edit');
        }
    }

    protected function assertPermission(string $module, string $role): void
    {
        if (!isset($this->groupPermissions[$module][$role])) {
            throw new McpToolException("The API key's permission group does not have the '{$module}:{$role}' role required for this operation.");
        }
    }

    protected function optionalString(array $args, string $key, string $default, int $maxLen): string
    {
        $value = trim((string) ($args[$key] ?? $default));
        if (mb_strlen($value) > $maxLen) {
            throw new McpToolException("Field '{$key}' must be at most {$maxLen} characters.");
        }
        return $value;
    }

    protected function optionalEnum(array $args, string $key, string $default, array $allowed): string
    {
        $value = (string) ($args[$key] ?? $default);
        if (!in_array($value, $allowed, true)) {
            throw new McpToolException("Field '{$key}' must be one of: " . implode(', ', $allowed) . '.');
        }
        return $value;
    }

    /**
     * Generic paginated category listing shared by domain tool classes
     */
    protected function listCategoriesGeneric(string $modelName, array $args, int $defaultLimit): array
    {
        $limit = $this->normalizeLimit($args, $defaultLimit);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel($modelName);
        $this->applyCategoryFilters($model, $args);
        $model->order('`order`', 'ASC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel($modelName);
        $countModel->select('id');
        $this->applyCategoryFilters($countModel, $args);
        $countModel->getResult(true, true);
        $total = $countModel->getSize();

        return [
            'items' => is_array($items) ? $items : [],
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    protected function applyCategoryFilters($model, array $args): void
    {
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
        }
        if (isset($args['root']) && in_array($args['root'], ['y', 'n'], true)) {
            $model->where_root($args['root']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $model->where('name LIKE \'%' . Obuilder::cc(trim((string) $args['search'])) . '%\'');
        }
    }

    protected function optionalDateTime(array $args, string $key, string $default): string
    {
        $value = trim((string) ($args[$key] ?? ''));
        if ($value === '') {
            return $default;
        }
        $parsed = date_parse($value);
        if ($parsed === false || $parsed['error_count'] > 0 || $parsed['warning_count'] > 0) {
            throw new McpToolException("Field '{$key}' must be a valid datetime (YYYY-MM-DD HH:MM:SS).");
        }
        if (checkdate((int) $parsed['month'], (int) $parsed['day'], (int) $parsed['year']) === false) {
            throw new McpToolException("Field '{$key}' must be a valid datetime (YYYY-MM-DD HH:MM:SS).");
        }
        return date('Y-m-d H:i:s', mktime(
            (int) $parsed['hour'],
            (int) $parsed['minute'],
            (int) $parsed['second'],
            (int) $parsed['month'],
            (int) $parsed['day'],
            (int) $parsed['year']
        ));
    }

    protected function assertSummaryLength(string $summary): void
    {
        $summaryLength = mb_strlen($summary);
        if ($summaryLength < 90 || $summaryLength > 120) {
            throw new McpToolException("Field 'summary' must be between 90 and 120 characters (deck summary standard), got {$summaryLength}.");
        }
    }

    // ------------------------------------------------------------------
    // Mutation helpers
    // ------------------------------------------------------------------

    protected function removeBridgeRelations(string $module, int $recordId): void
    {
        $bridges = [
            'mod_gallery_mod' => 'mod_gallery',
            'mod_attachment_mod' => 'mod_attachment',
            'mod_faq_mod' => 'mod_faq',
        ];

        foreach ($bridges as $bridgeTable => $bridgeModule) {
            $bridgeModel = $this->freshModel($bridgeTable);
            if ($bridgeModel->exists_mod_name($module) === true) {
                Run::import($bridgeModule, '_remove', [$module, $recordId]);
            }
        }
    }

    protected function deleteImageFiles(string $module, string $imageUrl, array $sizes): void
    {
        $image = Load::mod('image');
        $image->mod = $module;
        $image->name = 'image';
        $image->url = $imageUrl;
        $image->_delete_files($sizes);
    }

    /**
     * Handles the MCP 'image' argument: stores a new upload, removes the
     * existing image (null argument), or leaves it untouched (absent).
     * Returns the new image URL, or null when untouched/removed.
     * Callers must persist the returned value and delete orphaned files on failure.
     *
     * @return string|null New image URL, empty string for removal, null when absent
     */
    protected function resolveImageArgument(string $module, array $args, array $sizes, ?string $currentUrl): ?string
    {
        if (!array_key_exists('image', $args)) {
            return null;
        }

        if ($args['image'] === null) {
            if ($currentUrl !== null && $currentUrl !== '') {
                $this->deleteImageFiles($module, $currentUrl, $sizes);
            }
            return '';
        }

        $newUrl = $this->storeUploadedImage($module, $args['image'], $sizes);

        if ($currentUrl !== null && $currentUrl !== '') {
            $this->deleteImageFiles($module, $currentUrl, $sizes);
        }

        return $newUrl;
    }

    /**
     * Decodes a base64 image upload and stores it through the image module
     * pipeline (resize + thumbnails) by simulating the multipart upload array.
     */
    protected function storeUploadedImage(string $module, $image, array $sizes): string
    {
        if (!is_array($image) || !isset($image['data_base64'], $image['mime'])) {
            throw new McpToolException("Field 'image' must be an object with 'data_base64' and 'mime', or null to remove the image.");
        }

        $mime = strtolower(trim((string) $image['mime']));
        if (!isset(self::ALLOWED_IMAGE_MIMES[$mime])) {
            throw new McpToolException("Field 'image.mime' must be one of: " . implode(', ', array_keys(self::ALLOWED_IMAGE_MIMES)) . '.');
        }

        $cleanBase64 = preg_replace('/\s+/', '', (string) $image['data_base64']);
        $data = base64_decode($cleanBase64, true);
        if ($data === false || $data === '') {
            throw new McpToolException("Field 'image.data_base64' is not valid base64 data.");
        }
        if (strlen($data) > self::MAX_IMAGE_BYTES) {
            throw new McpToolException("Field 'image.data_base64' exceeds the 10 MB decoded size limit.");
        }

        $extension = self::ALLOWED_IMAGE_MIMES[$mime];
        $tempPath = tempnam(sys_get_temp_dir(), 'mcpimg');
        if ($tempPath === false || file_put_contents($tempPath, $data) === false) {
            throw new McpToolException('Could not buffer the uploaded image.');
        }

        $previousFileEntry = $_FILES['image'] ?? null;
        $_FILES['image'] = [
            'name' => 'mcp_upload.' . $extension,
            'type' => $mime,
            'tmp_name' => $tempPath,
            'error' => 0,
            'size' => strlen($data),
        ];

        try {
            $imageModule = Load::mod('image');
            $imageModule->mod = $module;
            $imageModule->name = 'image';
            if ($imageModule->_resize($sizes) === false) {
                throw new McpToolException('Image upload failed: ' . (string) $imageModule->error);
            }
            return (string) $imageModule->url;
        } finally {
            if ($previousFileEntry === null) {
                unset($_FILES['image']);
            } else {
                $_FILES['image'] = $previousFileEntry;
            }
            @unlink($tempPath);
        }
    }

    protected function serializeVideoRefs($videoRefs): string
    {
        $videoStr = (string) $videoRefs;
        if ($videoStr === '') {
            return '';
        }

        $videos = explode(',', $videoStr);
        $data = [];
        foreach ($videos as $videoKey) {
            if (trim($videoKey) !== '') {
                $data[] = trim($videoKey);
            }
        }
        if ($data !== []) {
            return serialize(array_values(array_unique($data)));
        }
        return '';
    }

    // ------------------------------------------------------------------
    // Pagination helpers
    // ------------------------------------------------------------------

    protected function normalizeLimit(array $args, int $default = 10): int
    {
        $limit = (int) ($args['limit'] ?? $default);
        if ($limit < 1) {
            $limit = 1;
        }
        return min($limit, 50);
    }

    protected function normalizeOffset(array $args): int
    {
        $offset = (int) ($args['offset'] ?? 0);
        return max($offset, 0);
    }

    /**
     * Builds an SEO slug from a name, mirroring the admin makeSeoValue JS helper
     */
    public static function makeSeo(string $text, string $locale = 'en'): string
    {
        if ($text === '') {
            return '';
        }

        $locale = strtolower(trim($locale));
        $isKurdish = in_array($locale, self::KURDISH_LOCALES, true);

        // Remove apostrophes and quotation marks
        $value = preg_replace('/[\x{0027}\x{0022}\x{2019}\x{201B}\x{201C}\x{201D}\x{201E}\x{201F}]/u', '', $text) ?? '';
        // Whitespace and dashes become underscores
        $value = preg_replace('/[\s-]+/u', '_', $value) ?? '';
        // Locale-aware lowercase
        $value = mb_strtolower($value, 'UTF-8');
        // Normalize to NFC and strip stray combining dot above (U+0307)
        if (class_exists('Normalizer')) {
            $normalized = \Normalizer::normalize($value, \Normalizer::FORM_C);
            if (is_string($normalized)) {
                $value = $normalized;
            }
        }
        $value = str_replace("\u{0307}", '', $value);
        // Kurdish keyboard typo normalizations
        if ($isKurdish) {
            $value = str_replace(['ı', 'İ'], 'i', $value);
        }
        // Collapse repeated underscores and trim
        $value = preg_replace('/_+/', '_', $value) ?? '';
        $value = trim($value, '_');

        if (mb_strlen($value) > 255) {
            $value = mb_substr($value, 0, 255);
        }

        return $value;
    }
}
