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

    /**
     * Return array of tool definitions provided by this domain tool class.
     *
     * @return array<string, array>
     */
    abstract public static function definitions(): array;

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

    /**
     * Resolves a record by numeric 'id' or by 'seo' slug (with optional 'lang' filter).
     */
    protected function loadRecordByIdOrSeo(string $modelName, array $args, string $label): ORM
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id > 0) {
            return $this->loadRecordById($modelName, $id, $label);
        }

        $seo = trim((string) ($args['seo'] ?? ''));
        if ($seo !== '') {
            $lang = trim((string) ($args['lang'] ?? ''));
            $model = $this->freshModel($modelName);
            if ($lang !== '') {
                $model->where_seo_and_lang($seo, $lang);
            } else {
                $model->where_seo($seo);
            }
            $model->limit(1)->getResultOne(true, true);
            if ($model->getSize() === 0) {
                $suffix = $lang !== '' ? " (lang '{$lang}')" : '';
                throw new McpToolException("{$label} record with seo '{$seo}'{$suffix} not found.");
            }
            $model->sync();
            return $model;
        }

        throw new McpToolException("Field 'id' (positive integer) or 'seo' (slug string) is required.");
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

    /**
     * Queries the `lang` table and returns language metadata:
     * - 'map': array<string, array{seo: string, name: string, status: string, is_default: string}>
     * - 'active_languages': list<string> of language codes where status === 'y'
     * - 'default_language': string default active language code
     */
    protected function getSiteLanguageMap(): array
    {
        $db = Load::lib('Database');
        $db->fetch_array('SELECT seo, name, status, is_default FROM lang ORDER BY is_default DESC, seo ASC');
        $rows = $db->toArray();
        $map = [];
        $activeLanguages = [];
        $defaultLanguage = '';
        if (is_array($rows)) {
            foreach ($rows as $r) {
                if (!is_array($r)) {
                    continue;
                }
                $seo = (string) ($r['seo'] ?? '');
                if ($seo === '') {
                    continue;
                }
                $status = (string) ($r['status'] ?? 'n');
                $isDefault = (string) ($r['is_default'] ?? 'n');
                $map[$seo] = [
                    'seo' => $seo,
                    'name' => (string) ($r['name'] ?? $seo),
                    'status' => $status,
                    'is_default' => $isDefault,
                ];
                if ($status === 'y') {
                    $activeLanguages[] = $seo;
                    if ($defaultLanguage === '' || $isDefault === 'y') {
                        $defaultLanguage = $seo;
                    }
                }
            }
        }
        if ($defaultLanguage === '' && !empty($activeLanguages)) {
            $defaultLanguage = $activeLanguages[0];
        }
        return [
            'map' => $map,
            'active_languages' => $activeLanguages,
            'default_language' => $defaultLanguage,
        ];
    }

    protected function requireActiveLang(array $args): string
    {
        $lang = trim((string) ($args['lang'] ?? ''));
        if ($lang === '') {
            throw new McpToolException("Field 'lang' is required.");
        }

        $langMeta = $this->getSiteLanguageMap();
        if (!in_array($lang, $langMeta['active_languages'], true)) {
            $activeList = !empty($langMeta['active_languages']) ? implode(', ', $langMeta['active_languages']) : 'none';
            throw new McpToolException("Language '{$lang}' is not an active site language. Active languages: {$activeList}.");
        }

        return $lang;
    }

    /**
     * Enforces linguistic homogeneity between the target record language ($lang)
     * and the prose supplied in name, summary, and content.
     */
    protected function assertContentMatchesLang(string $lang, string $name, string $summary = '', string $content = ''): void
    {
        $lang = strtolower(trim($lang));
        if ($lang === '') {
            return;
        }

        $combined = strip_tags($name . ' ' . $summary . ' ' . $content);
        $combined = html_entity_decode($combined, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lower = mb_strtolower($combined, 'UTF-8');

        // 1. Detect Kurdish prose inside a non-Kurdish record (e.g. tr, en)
        if (!in_array($lang, self::KURDISH_LOCALES, true)) {
            $kuCharCount = preg_match_all('/[êîû]/u', $lower);
            if ($kuCharCount >= 2) {
                $kuStopwords = [
                    'ji', 'bo', 'yên', 'derbarê', 'hatiye', 'wekî', 'ligel',
                    'navbera', 'piştî', 'berî', 'xwe', 'herî', 'dê', 'bû',
                    'li', 'ku', 'ev', 'wê', 'wan',
                ];
                $matchedKuWords = 0;
                foreach ($kuStopwords as $word) {
                    if (preg_match('/(?<!\p{L})' . preg_quote($word, '/') . '(?!\p{L})/u', $lower)) {
                        $matchedKuWords++;
                    }
                }
                if ($matchedKuWords >= 2) {
                    throw new McpToolException(
                        "Content language mismatch: target record language is '{$lang}', but the supplied text appears to be Kurmanji Kurdish ('ku'). Translate all fields into '{$lang}' before saving."
                    );
                }
            }
        }

        // 2. Detect Turkish prose inside a non-Turkish record (e.g. ku, en)
        if ($lang !== 'tr') {
            $trCharCount = preg_match_all('/[ğı]/u', $lower) + preg_match_all('/İ/u', $combined);
            if ($trCharCount >= 2) {
                $trStopwords = [
                    'için', 'olarak', 'tarafından', 'olan', 'birlikte', 'göre',
                    'ancak', 'sonrası', 'öncesi', 'hakkında', 'ilgili', 'arasında',
                    'üzerine', 'kapsamında', 'nedeniyle', 'konusunda', 'yapılan',
                    'edildi', 'olduğu',
                ];
                $matchedTrWords = 0;
                foreach ($trStopwords as $word) {
                    if (preg_match('/(?<!\p{L})' . preg_quote($word, '/') . '(?!\p{L})/u', $lower)) {
                        $matchedTrWords++;
                    }
                }
                if ($matchedTrWords >= 2) {
                    throw new McpToolException(
                        "Content language mismatch: target record language is '{$lang}', but the supplied text appears to be Turkish ('tr'). Translate all fields into '{$lang}' before saving."
                    );
                }
            }
        }
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
        if (!empty($this->groupPermissions['*']['*']) || !empty($this->groupPermissions[$module]['*'])) {
            return;
        }
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
        $langMeta = $this->getSiteLanguageMap();

        $model = $this->freshModel($modelName);
        $this->applyCategoryFilters($model, $args);
        $model->order('`order`', 'ASC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();
        $rows = is_array($items) ? $items : [];

        $enriched = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowLang = (string) ($row['lang'] ?? '');
            $row['lang_status'] = $langMeta['map'][$rowLang]['status'] ?? 'n';
            $enriched[] = $row;
        }

        $countModel = $this->freshModel($modelName);
        $countModel->select('id');
        $this->applyCategoryFilters($countModel, $args);
        $countModel->getResult(true, true);
        $total = $countModel->getSize();

        return [
            'items' => $enriched,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'active_languages' => $langMeta['active_languages'],
            'default_language' => $langMeta['default_language'],
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
        if (isset($args['seo']) && trim((string) $args['seo']) !== '') {
            $model->where_seo(trim((string) $args['seo']));
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $escaped = Obuilder::cc(trim((string) $args['search']));
            $model->where("(name LIKE '%{$escaped}%' OR seo LIKE '%{$escaped}%')");
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
        if ($summaryLength < 90 || $summaryLength > 160) {
            throw new McpToolException("Field 'summary' must be between 90 and 160 characters (90 and 120 deck standard; 120-160 SERP snippet standard), got {$summaryLength}.");
        }
    }

    /**
     * Sanitizes rich HTML body content:
     * - Applies Helper::clear_html()
     * - Strips inline style="..." attributes and <style>/<script> tags (AGENTS.md Rule 2)
     * - Downgrades stray <h1> tags in body content to <h2> to preserve single-H1 page hierarchy
     */
    protected function sanitizeBodyHtml(string $html): string
    {
        if (class_exists('Helper') && method_exists('Helper', 'clear_html')) {
            $html = (string) Helper::clear_html($html);
        }
        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = preg_replace('/(<[a-zA-Z0-9]+\b[^>]*?)\s+style\s*=\s*(?:"[^"]*"|\'[^\']*\')/i', '$1', $html) ?? $html;
        $html = preg_replace(['/<h1(\b[^>]*)>/i', '/<\/h1>/i'], ['<h2$1>', '</h2>'], $html) ?? $html;
        return $html;
    }

    /**
     * Synchronizes summary and description fields on partial updates so that
     * Twig templates evaluating `d.summary ?: d.description` never render stale metadata.
     */
    protected function synchronizeSummaryAndDescription(ORM $model, array $args): void
    {
        $hasSummaryArg = isset($args['summary']);
        $hasDescArg = isset($args['description']);

        if ($hasSummaryArg && !$hasDescArg) {
            $model->description = (string) $model->summary;
        } elseif ($hasDescArg && !$hasSummaryArg) {
            $desc = trim(strip_tags((string) $model->description));
            $descLen = mb_strlen($desc);
            if ($descLen >= 90 && $descLen <= 160) {
                $model->summary = $desc;
            }
        }
    }

    /**
     * Automatically registers or updates an HTTP 301 redirect in mod_redirect
     * whenever a record's or category's SEO slug or language changes on update.
     */
    protected function registerSlugRedirectIfChanged(string $oldLang, string $oldSeo, string $newLang, string $newSeo, string $routePrefix): void
    {
        $oldSeo = trim($oldSeo, '/');
        $newSeo = trim($newSeo, '/');
        $oldLang = trim($oldLang, '/');
        $newLang = trim($newLang, '/');
        $routePrefix = trim($routePrefix, '/');

        if ($oldSeo === '' || $newSeo === '' || ($oldSeo === $newSeo && $oldLang === $newLang)) {
            return;
        }

        $oldPath = ($oldLang !== '' ? $oldLang . '/' : '') . ($routePrefix !== '' ? $routePrefix . '/' : '') . $oldSeo;
        $newPath = ($newLang !== '' ? $newLang . '/' : '') . ($routePrefix !== '' ? $routePrefix . '/' : '') . $newSeo;

        if ($oldPath === $newPath) {
            return;
        }

        try {
            $db = Load::lib('Database');
            $safeOld = Obuilder::cc($oldPath);
            $safeNew = Obuilder::cc($newPath);
            $now = date('Y-m-d H:i:s');

            // Collapse redirect chains: any redirect pointing to oldPath now points directly to newPath
            $db->query("UPDATE mod_redirect SET new_url = '{$safeNew}' WHERE new_url = '{$safeOld}' OR new_url = '/{$safeOld}'");

            // Prevent circular redirect if reverting to a previously redirected slug
            $db->query("DELETE FROM mod_redirect WHERE old_url = '{$safeNew}' OR old_url = '/{$safeNew}'");

            // Insert or update oldPath -> newPath 301 redirect
            $db->fetch_array("SELECT id FROM mod_redirect WHERE old_url = '{$safeOld}' LIMIT 1");
            if ($db->getSize() > 0) {
                $existing = $db->toArray()[0];
                $rid = (int) ($existing['id'] ?? 0);
                if ($rid > 0) {
                    $db->query("UPDATE mod_redirect SET new_url = '{$safeNew}', status_code = 301 WHERE id = {$rid}");
                }
            } else {
                $db->query("INSERT INTO mod_redirect (old_url, new_url, status_code, created_at) VALUES ('{$safeOld}', '{$safeNew}', 301, '{$now}')");
            }
        } catch (\Throwable $e) {
            // Do not fail primary CRUD update if redirect table is unavailable
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
        $data = base64_decode((string) $cleanBase64, true);
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

    protected function normalizeLimit(array $args, int $default = 10, int $max = 50): int
    {
        $limit = (int) ($args['limit'] ?? $default);
        if ($limit < 1) {
            $limit = 1;
        }
        return min($limit, $max);
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
        // Wikipedia-style parity: preserve original casing, do not force lowercase
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
            $value = str_replace(['ı', 'İ'], ['i', 'I'], $value);
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
