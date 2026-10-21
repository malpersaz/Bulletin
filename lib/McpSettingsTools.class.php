<?php
/**
 * Amed Framework
 * MCP Settings Tools - site settings, social links, and cookie declarations
 * lib/McpSettingsTools.class.php
 *
 * Scope mirrors the admin panel: per-language site settings whitelist,
 * social links (edit/toggle only - creation is not an admin flow),
 * and full cookie declaration CRUD.
 */

class McpSettingsTools extends McpCrudToolsBase
{
    private const SETTINGS_STRING_FIELDS = [
        'title' => 255,
        'site_name' => 255,
        'description' => null,
        'owner_name' => 255,
        'phone' => 50,
        'whatsapp' => 50,
        'email' => 255,
        'office_address' => null,
        'work_address' => null,
        'map_embed_code' => null,
        'google_search_console' => 255,
        'google_analytics' => 255,
        'meta_tags' => null,
        'cookie_policy_content' => null,
    ];

    private const SETTINGS_INT_FIELDS = [
        'stat_workforce',
        'stat_offices',
        'maintenance_retry_after',
    ];

    private const LOGO_TYPES = ['square', 'rectangle', 'square_name', 'rectangle_name', 'name_only'];

    private const COOKIE_CATEGORIES = ['necessary', 'functional', 'analytics', 'marketing'];

    // ------------------------------------------------------------------
    // Site settings tools
    // ------------------------------------------------------------------

    /**
     * Lists site settings rows (one per language)
     */
    public function listSiteSettings(array $args): array
    {
        $model = $this->freshModel('mod_settings');
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
        }
        $model->order('id', 'ASC')->getResult(true, true);
        $items = $model->toArray();

        return ['items' => is_array($items) ? $items : []];
    }

    /**
     * Returns a single site settings row
     */
    public function getSiteSettings(array $args): array
    {
        $lang = trim((string) ($args['lang'] ?? ''));
        if ($lang === '') {
            throw new McpToolException("Field 'lang' is required.");
        }

        $model = $this->freshModel('mod_settings');
        $model->where_lang($lang)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("No site settings row exists for language '{$lang}'.");
        }
        $model->sync();

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Site settings row for language '{$lang}' could not be read.");
        }

        return ['item' => $row];
    }

    /**
     * Partially updates the site settings row for a language
     */
    public function updateSiteSettings(array $args): array
    {
        $lang = trim((string) ($args['lang'] ?? ''));
        if ($lang === '') {
            throw new McpToolException("Field 'lang' is required.");
        }

        $model = $this->freshModel('mod_settings');
        $model->where_lang($lang)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("No site settings row exists for language '{$lang}'.");
        }
        $model->sync();

        $applied = [];

        // Strings
        foreach (self::SETTINGS_STRING_FIELDS as $field => $maxLen) {
            if (!array_key_exists($field, $args)) {
                continue;
            }
            $value = trim((string) $args[$field]);
            if ($maxLen !== null && mb_strlen($value) > $maxLen) {
                throw new McpToolException("Field '{$field}' must be at most {$maxLen} characters.");
            }
            if ($field === 'email' && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                throw new McpToolException("Field 'email' must be a valid e-mail address.");
            }
            $model->{$field} = $value;
            $applied[] = $field;
        }

        // Ints (non-negative)
        foreach (self::SETTINGS_INT_FIELDS as $field) {
            if (!array_key_exists($field, $args)) {
                continue;
            }
            $value = (int) $args[$field];
            if ($value < 0) {
                throw new McpToolException("Field '{$field}' must be a non-negative integer.");
            }
            $model->{$field} = $value;
            $applied[] = $field;
        }

        // established_year
        if (array_key_exists('established_year', $args)) {
            $year = trim((string) $args['established_year']);
            if ($year !== '' && (strlen($year) !== 4 || (int) $year < 1000 || (int) $year > 9999)) {
                throw new McpToolException("Field 'established_year' must be a 4-digit year or empty.");
            }
            $model->established_year = $year === '' ? null : $year;
            $applied[] = 'established_year';
        }

        // Enums
        if (array_key_exists('show_logo', $args)) {
            $model->show_logo = $this->optionalEnum($args, 'show_logo', (string) $model->show_logo, ['y', 'n']);
            $applied[] = 'show_logo';
        }
        if (array_key_exists('logo_type', $args)) {
            $logoType = trim((string) $args['logo_type']);
            if ($logoType !== '' && !in_array($logoType, self::LOGO_TYPES, true)) {
                throw new McpToolException("Field 'logo_type' must be one of: " . implode(', ', self::LOGO_TYPES) . '.');
            }
            if ($logoType !== '') {
                $model->logo_type = $logoType;
                $applied[] = 'logo_type';
            }
        }

        // Global fields (site status / maintenance)
        $globalFields = [];
        if (array_key_exists('site_status', $args)) {
            $siteStatus = strtolower(trim((string) $args['site_status']));
            if (!in_array($siteStatus, ['open', 'closed'], true)) {
                throw new McpToolException("Field 'site_status' must be 'open' or 'closed'.");
            }
            $model->site_status = $siteStatus;
            $globalFields['site_status'] = $siteStatus;
            $applied[] = 'site_status';
        }
        if (array_key_exists('maintenance_message', $args)) {
            $model->maintenance_message = trim((string) $args['maintenance_message']);
            $globalFields['maintenance_message'] = $model->maintenance_message;
            $applied[] = 'maintenance_message';
        }
        if (array_key_exists('maintenance_retry_after', $args)) {
            $retry = (int) $args['maintenance_retry_after'];
            if ($retry < 0) {
                throw new McpToolException("Field 'maintenance_retry_after' must be a non-negative integer.");
            }
            $model->maintenance_retry_after = $retry;
            $globalFields['maintenance_retry_after'] = $retry;
            $applied[] = 'maintenance_retry_after';
        }

        if ($applied === []) {
            throw new McpToolException('No editable settings field was provided.');
        }

        if (!$model->update('lang')) {
            throw new McpToolException('Failed to update the site settings record.');
        }

        // Global synchronization across all language rows (admin parity)
        if ($globalFields !== []) {
            $db = Load::lib('Database');
            foreach ($globalFields as $field => $value) {
                $db->query('UPDATE mod_settings SET `' . $field . '` = \'' . Obuilder::cc((string) $value) . '\'');
            }
        }

        return [
            'updated' => true,
            'lang' => $lang,
            'item' => $this->getSiteSettings(['lang' => $lang])['item'],
        ];
    }

    // ------------------------------------------------------------------
    // Social link tools (admin parity: edit and toggle only)
    // ------------------------------------------------------------------

    /**
     * Lists social links ordered by display order
     */
    public function listSocialLinks(array $args): array
    {
        $model = $this->freshModel('mod_settings.mod_settings_social');
        $model->order('display_order', 'ASC')->getResult(true, true);
        $items = $model->toArray();

        return ['items' => is_array($items) ? $items : []];
    }

    /**
     * Updates a social link (url, active state, display order)
     */
    public function updateSocialLink(array $args): array
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            throw new McpToolException("Field 'id' is required and must be a positive integer.");
        }

        $model = $this->freshModel('mod_settings.mod_settings_social');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Social link '{$id}' not found.");
        }
        $model->sync();

        $updated = [];

        if (array_key_exists('url', $args)) {
            $url = trim((string) $args['url']);
            if ($url === '') {
                throw new McpToolException("Field 'url' cannot be empty.");
            }
            $model->url = $url;
            $updated[] = 'url';
        }

        if (array_key_exists('is_active', $args)) {
            $model->is_active = $this->optionalEnum($args, 'is_active', (string) $model->is_active, ['y', 'n']);
            $updated[] = 'is_active';
        }

        if (array_key_exists('display_order', $args)) {
            $order = (int) $args['display_order'];
            if ($order < 0) {
                throw new McpToolException("Field 'display_order' must be a non-negative integer.");
            }
            $model->display_order = $order;
            $updated[] = 'display_order';
        }

        if ($updated === []) {
            throw new McpToolException('No editable social link field was provided (url, is_active, display_order).');
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the social link record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getSocialLink($id),
        ];
    }

    private function getSocialLink(int $id): array
    {
        $model = $this->freshModel('mod_settings.mod_settings_social');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Social link '{$id}' not found.");
        }

        $row = $model->toArray();
        if (!is_array($row)) {
            throw new McpToolException("Social link '{$id}' could not be read.");
        }
        return $row;
    }

    // ------------------------------------------------------------------
    // Cookie declaration tools (full admin parity)
    // ------------------------------------------------------------------

    /**
     * Lists cookie declarations with an optional language filter
     */
    public function listCookieDeclarations(array $args): array
    {
        $model = $this->freshModel('mod_settings.mod_settings_cookies');
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
        }
        if (isset($args['category']) && in_array($args['category'], self::COOKIE_CATEGORIES, true)) {
            $model->where_category($args['category']);
        }
        $model->order('display_order', 'ASC')->getResult(true, true);
        $items = $model->toArray();

        return ['items' => is_array($items) ? $items : []];
    }

    /**
     * Creates a cookie declaration
     */
    public function createCookieDeclaration(array $args): array
    {
        $fields = $this->buildCookieFields($args, true);

        $model = $this->freshModel('mod_settings.mod_settings_cookies');
        foreach ($fields as $field => $value) {
            $model->{$field} = $value;
        }
        $model->user_id = $this->actingUserId;

        if (!$model->save(true)) {
            throw new McpToolException('Failed to create the cookie declaration record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getCookieDeclaration((int) $model->id),
        ];
    }

    /**
     * Partially updates a cookie declaration
     */
    public function updateCookieDeclaration(array $args): array
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            throw new McpToolException("Field 'id' is required and must be a positive integer.");
        }

        $model = $this->freshModel('mod_settings.mod_settings_cookies');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Cookie declaration '{$id}' not found.");
        }
        $model->sync();

        $fields = $this->buildCookieFields($args, false);
        foreach ($fields as $field => $value) {
            $model->{$field} = $value;
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the cookie declaration record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getCookieDeclaration($id),
        ];
    }

    /**
     * Deletes a cookie declaration
     */
    public function deleteCookieDeclaration(array $args): array
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            throw new McpToolException("Field 'id' is required and must be a positive integer.");
        }

        $model = $this->freshModel('mod_settings.mod_settings_cookies');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Cookie declaration '{$id}' not found.");
        }
        $model->sync();
        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    private function getCookieDeclaration(int $id): array
    {
        $model = $this->freshModel('mod_settings.mod_settings_cookies');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Cookie declaration '{$id}' not found.");
        }

        $row = $model->toArray();
        if (!is_array($row)) {
            throw new McpToolException("Cookie declaration '{$id}' could not be read.");
        }
        return $row;
    }

    // ------------------------------------------------------------------
    // Cookie helpers
    // ------------------------------------------------------------------

    /**
     * Validates cookie arguments; returns only the provided fields
     *
     * @return array<string, mixed>
     */
    private function buildCookieFields(array $args, bool $requireName): array
    {
        $fields = [];

        if (array_key_exists('lang', $args)) {
            $fields['lang'] = $this->requireActiveLang($args);
        } elseif ($requireName) {
            throw new McpToolException("Field 'lang' is required.");
        }

        if (array_key_exists('cookie_name', $args)) {
            $cookieName = trim((string) $args['cookie_name']);
            if ($cookieName === '' || mb_strlen($cookieName) > 255) {
                throw new McpToolException("Field 'cookie_name' is required (1-255 characters).");
            }
            $fields['cookie_name'] = $cookieName;
        } elseif ($requireName) {
            throw new McpToolException("Field 'cookie_name' is required.");
        }

        if (array_key_exists('category', $args)) {
            $category = trim((string) $args['category']);
            if (!in_array($category, self::COOKIE_CATEGORIES, true)) {
                throw new McpToolException("Field 'category' must be one of: " . implode(', ', self::COOKIE_CATEGORIES) . '.');
            }
            $fields['category'] = $category;
        } elseif ($requireName) {
            $fields['category'] = 'necessary';
        }

        // provider, purpose, duration are NOT NULL columns: default to empty strings
        foreach (['provider', 'purpose', 'duration'] as $field) {
            $fields[$field] = array_key_exists($field, $args) ? trim((string) $args[$field]) : '';
        }

        if (array_key_exists('display_order', $args)) {
            $order = (int) $args['display_order'];
            if ($order < 0) {
                throw new McpToolException("Field 'display_order' must be a non-negative integer.");
            }
            $fields['display_order'] = $order;
        }

        if (array_key_exists('is_active', $args)) {
            $fields['is_active'] = $this->optionalEnum($args, 'is_active', 'y', ['y', 'n']);
        }

        return $fields;
    }

    // ------------------------------------------------------------------
    // Institutional Affiliations tools (mod_settings_affiliation)
    // ------------------------------------------------------------------

    private const AFFILIATION_MODEL = 'mod_settings.mod_settings_affiliation';

    /**
     * Lists institutional affiliations ordered by display order
     */
    public function listAffiliations(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel(self::AFFILIATION_MODEL);
        if (isset($args['is_active']) && in_array($args['is_active'], ['y', 'n'], true)) {
            $model->where_is_active($args['is_active']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $searchTerm = Obuilder::cc(trim((string) $args['search']));
            $model->where('(name LIKE \'%' . $searchTerm . '%\' OR url LIKE \'%' . $searchTerm . '%\')');
        }

        $model->order('display_order', 'ASC')->order('id', 'ASC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel(self::AFFILIATION_MODEL);
        if (isset($args['is_active']) && in_array($args['is_active'], ['y', 'n'], true)) {
            $countModel->where_is_active($args['is_active']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $searchTerm = Obuilder::cc(trim((string) $args['search']));
            $countModel->where('(name LIKE \'%' . $searchTerm . '%\' OR url LIKE \'%' . $searchTerm . '%\')');
        }
        $countModel->getResult(true, true);
        $total = $countModel->getSize();

        return [
            'items' => is_array($items) ? $items : [],
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Returns a single affiliation record by ID
     */
    public function getAffiliation(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadRecordById(self::AFFILIATION_MODEL, $id, 'Affiliation');
        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Affiliation record '{$id}' not found.");
        }
        return ['item' => $row];
    }

    /**
     * Creates a new institutional affiliation
     */
    public function createAffiliation(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 150) {
            throw new McpToolException("Field 'name' is required (1-150 characters).");
        }

        $url = trim((string) ($args['url'] ?? ''));
        if ($url === '' || mb_strlen($url) > 500) {
            throw new McpToolException("Field 'url' is required (1-500 characters).");
        }

        $isActive = $this->optionalEnum($args, 'is_active', 'y', ['y', 'n']);
        $displayOrder = isset($args['display_order']) ? (int) $args['display_order'] : $this->nextAffiliationOrder();

        $model = $this->freshModel(self::AFFILIATION_MODEL);
        $model->name = $name;
        $model->url = $url;
        $model->display_order = $displayOrder;
        $model->is_active = $isActive;
        $model->user_id = $this->actingUserId;
        $model->logo = '';

        if (array_key_exists('logo', $args) && is_array($args['logo'])) {
            $model->logo = $this->storeAffiliationLogo($args['logo']);
        }

        if (!$model->save(true)) {
            throw new McpToolException('Failed to create the affiliation record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getAffiliation(['id' => (int) $model->id])['item'],
        ];
    }

    /**
     * Partially updates an affiliation record
     */
    public function updateAffiliation(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadRecordById(self::AFFILIATION_MODEL, $id, 'Affiliation');

        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if ($name === '' || mb_strlen($name) > 150) {
                throw new McpToolException("Field 'name' must be 1-150 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['url'])) {
            $url = trim((string) $args['url']);
            if ($url === '' || mb_strlen($url) > 500) {
                throw new McpToolException("Field 'url' must be 1-500 characters.");
            }
            $model->url = $url;
        }

        if (isset($args['is_active'])) {
            $model->is_active = $this->optionalEnum($args, 'is_active', (string) $model->is_active, ['y', 'n']);
        }

        if (isset($args['display_order'])) {
            $order = (int) $args['display_order'];
            if ($order < 0) {
                throw new McpToolException("Field 'display_order' must be non-negative.");
            }
            $model->display_order = $order;
        }

        if (array_key_exists('logo', $args)) {
            if ($args['logo'] === null) {
                if (!empty($model->logo)) {
                    $this->deleteAffiliationLogo((string) $model->logo);
                }
                $model->logo = '';
            } elseif (is_array($args['logo'])) {
                if (!empty($model->logo)) {
                    $this->deleteAffiliationLogo((string) $model->logo);
                }
                $model->logo = $this->storeAffiliationLogo($args['logo']);
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the affiliation record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getAffiliation(['id' => $id])['item'],
        ];
    }

    /**
     * Deletes an affiliation record and its logo file
     */
    public function deleteAffiliation(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadRecordById(self::AFFILIATION_MODEL, $id, 'Affiliation');

        if (!empty($model->logo)) {
            $this->deleteAffiliationLogo((string) $model->logo);
        }

        if (!$model->delete('id')) {
            throw new McpToolException("Failed to delete affiliation record '{$id}'.");
        }

        return [
            'deleted' => true,
            'id' => $id,
        ];
    }

    private function nextAffiliationOrder(): int
    {
        $model = $this->freshModel(self::AFFILIATION_MODEL);
        $model->select('display_order')->order('display_order', 'DESC')->limit(1)->getResult(true, true);
        $rows = $model->toArray();
        return !empty($rows) ? ((int) $rows[0]['display_order'] + 1) : 1;
    }

    private function storeAffiliationLogo(array $logo): string
    {
        if (!isset($logo['data_base64'], $logo['mime'])) {
            throw new McpToolException("Field 'logo' must be an object with 'data_base64' and 'mime'.");
        }
        $mime = strtolower(trim((string) $logo['mime']));
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];
        if (!isset($allowed[$mime])) {
            throw new McpToolException("Field 'logo.mime' must be one of: " . implode(', ', array_keys($allowed)));
        }
        $data = base64_decode((string) $logo['data_base64'], true);
        if ($data === false || $data === '') {
            throw new McpToolException("Field 'logo.data_base64' is not valid base64 data.");
        }
        $ext = $allowed[$mime];
        $uploadDir = root_dir . 'static' . DS . 'mod_settings' . DS;
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $safeName = 'affil_' . time() . '_' . substr(md5(uniqid((string) rand(), true)), 0, 8) . '.' . $ext;
        if (file_put_contents($uploadDir . $safeName, $data) === false) {
            throw new McpToolException('Could not save the uploaded logo file.');
        }
        return $safeName;
    }

    private function deleteAffiliationLogo(string $filename): void
    {
        $filePath = root_dir . 'static' . DS . 'mod_settings' . DS . basename($filename);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
}
