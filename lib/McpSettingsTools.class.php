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
    /**
     * Return array of tool definitions provided by this domain tool class.
     *
     * @return array<string, array>
     */
    public static function definitions(): array
    {
        return [
            'list_site_settings' => [
                'module' => 'mod_settings',
                'role' => 'list',
                'domain' => 'settings',
                'handler' => 'listSiteSettings',
                'description' => 'List site settings rows (one per language) enriched with lang_status (y/n) and is_default_lang (y/n) from the lang table, plus top-level active_languages and default_language.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Filter by language code (e.g. ku, en, tr).',
                        ],
                        'active_only' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                            'description' => 'When y, only return settings rows for languages that are active in the lang table (lang.status = y).',
                        ],
                    ],
                ],
                'title' => 'List Site Settings',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_site_settings' => [
                'module' => 'mod_settings',
                'role' => 'list',
                'domain' => 'settings',
                'handler' => 'getSiteSettings',
                'description' => 'Get the full site settings row for one language, enriched with lang_status (y/n), is_default_lang (y/n), active_languages, and default_language.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Language code of the settings row (e.g. ku, en, tr).',
                        ],
                    ],
                    'required' => [
                        'lang',
                    ],
                ],
                'title' => 'Get Site Settings',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'update_site_settings' => [
                'module' => 'mod_settings',
                'role' => 'edit',
                'domain' => 'settings',
                'handler' => 'updateSiteSettings',
                'description' => 'Partially update the site settings row for one language. Editable fields: title, site_name, description, owner_name, phone, whatsapp, email, office_address, work_address, map_embed_code, google_search_console, google_analytics, meta_tags, cookie_policy_content, established_year, show_logo, logo_type, workforce and offices counters, plus the global site_status (open/closed), maintenance_message and maintenance_retry_after (synchronized across all languages). Logo/favicon asset paths are managed through the admin upload UI and are intentionally not editable here.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Language code of the settings row to update.',
                        ],
                        'title' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Browser tab title.',
                        ],
                        'site_name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Public site name.',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Site meta description.',
                        ],
                        'owner_name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Site owner name.',
                        ],
                        'phone' => [
                            'type' => 'string',
                            'maxLength' => 50,
                            'description' => 'Contact phone number.',
                        ],
                        'whatsapp' => [
                            'type' => 'string',
                            'maxLength' => 50,
                            'description' => 'WhatsApp number.',
                        ],
                        'email' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Contact e-mail address.',
                        ],
                        'office_address' => [
                            'type' => 'string',
                            'description' => 'Office address text.',
                        ],
                        'work_address' => [
                            'type' => 'string',
                            'description' => 'Workshop address text.',
                        ],
                        'map_embed_code' => [
                            'type' => 'string',
                            'description' => 'Map embed HTML snippet.',
                        ],
                        'google_search_console' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Google Search Console verification value.',
                        ],
                        'google_analytics' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Google Analytics measurement ID.',
                        ],
                        'meta_tags' => [
                            'type' => 'string',
                            'description' => 'Additional meta tags HTML.',
                        ],
                        'cookie_policy_content' => [
                            'type' => 'string',
                            'description' => 'Cookie policy page content (HTML).',
                        ],
                        'established_year' => [
                            'type' => 'string',
                            'description' => '4-digit founding year, or empty.',
                        ],
                        'show_logo' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'Show the site logo. Synchronized across all languages.',
                        ],
                        'logo_type' => [
                            'type' => 'string',
                            'enum' => [
                                'square',
                                'rectangle',
                                'square_name',
                                'rectangle_name',
                                'name_only',
                            ],
                            'description' => 'Logo layout type. Synchronized across all languages.',
                        ],
                        'stat_workforce' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'description' => 'Workforce counter.',
                        ],
                        'stat_offices' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'description' => 'Offices counter.',
                        ],
                        'site_status' => [
                            'type' => 'string',
                            'enum' => [
                                'open',
                                'closed',
                            ],
                            'description' => 'Site availability. Synchronized across all languages.',
                        ],
                        'maintenance_message' => [
                            'type' => 'string',
                            'description' => 'Maintenance page message. Synchronized across all languages.',
                        ],
                        'maintenance_retry_after' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'description' => 'Retry-after seconds for the maintenance page. Synchronized across all languages.',
                        ],
                    ],
                    'required' => [
                        'lang',
                    ],
                ],
                'title' => 'Update Site Settings',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
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
                'title' => 'List Social Links',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
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
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Social link record ID.',
                        ],
                        'url' => [
                            'type' => 'string',
                            'description' => 'New profile URL.',
                        ],
                        'is_active' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'Active state.',
                        ],
                        'display_order' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'description' => 'Display order.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Update Social Link',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
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
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Filter by language code.',
                        ],
                        'category' => [
                            'type' => 'string',
                            'enum' => [
                                'necessary',
                                'functional',
                                'analytics',
                                'marketing',
                            ],
                            'description' => 'Filter by cookie category.',
                        ],
                    ],
                ],
                'title' => 'List Cookie Declarations',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
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
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Language code of an active site language.',
                        ],
                        'cookie_name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Cookie name (1-255 characters).',
                        ],
                        'category' => [
                            'type' => 'string',
                            'enum' => [
                                'necessary',
                                'functional',
                                'analytics',
                                'marketing',
                            ],
                            'default' => 'necessary',
                            'description' => 'Cookie category.',
                        ],
                        'provider' => [
                            'type' => 'string',
                            'description' => 'Cookie provider.',
                        ],
                        'purpose' => [
                            'type' => 'string',
                            'description' => 'Cookie purpose description.',
                        ],
                        'duration' => [
                            'type' => 'string',
                            'description' => 'Cookie lifetime description.',
                        ],
                        'display_order' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'description' => 'Display order.',
                        ],
                        'is_active' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'default' => 'y',
                            'description' => 'Active state.',
                        ],
                    ],
                    'required' => [
                        'lang',
                        'cookie_name',
                    ],
                ],
                'title' => 'Create Cookie Declaration',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
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
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Cookie declaration record ID.',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'New language code of an active site language.',
                        ],
                        'cookie_name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New cookie name.',
                        ],
                        'category' => [
                            'type' => 'string',
                            'enum' => [
                                'necessary',
                                'functional',
                                'analytics',
                                'marketing',
                            ],
                            'description' => 'New cookie category.',
                        ],
                        'provider' => [
                            'type' => 'string',
                            'description' => 'New provider.',
                        ],
                        'purpose' => [
                            'type' => 'string',
                            'description' => 'New purpose description.',
                        ],
                        'duration' => [
                            'type' => 'string',
                            'description' => 'New duration description.',
                        ],
                        'display_order' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'description' => 'New display order.',
                        ],
                        'is_active' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'New active state.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Update Cookie Declaration',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
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
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Cookie declaration record ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Delete Cookie Declaration',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
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
                        'is_active' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'Filter by active status.',
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'Substring search in institution name or URL.',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 100,
                            'default' => 20,
                            'description' => 'Number of items to return.',
                        ],
                        'offset' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'default' => 0,
                            'description' => 'Number of items to skip.',
                        ],
                    ],
                ],
                'title' => 'List Affiliations',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
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
                        'id' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'description' => 'Affiliation ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Get Affiliation',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
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
                        'name' => [
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 150,
                            'description' => 'Institution or partner name.',
                        ],
                        'url' => [
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 500,
                            'description' => 'Destination URL link.',
                        ],
                        'is_active' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'default' => 'y',
                            'description' => 'Display status in marquee band.',
                        ],
                        'display_order' => [
                            'type' => 'integer',
                            'description' => 'Sort order (defaults to next order).',
                        ],
                        'logo' => [
                            'type' => 'object',
                            'description' => 'Logo file upload (base64 encoded).',
                            'properties' => [
                                'data_base64' => [
                                    'type' => 'string',
                                    'description' => 'Base64 encoded image data.',
                                ],
                                'mime' => [
                                    'type' => 'string',
                                    'enum' => [
                                        'image/jpeg',
                                        'image/png',
                                        'image/webp',
                                        'image/svg+xml',
                                    ],
                                    'description' => 'MIME type.',
                                ],
                            ],
                            'required' => [
                                'data_base64',
                                'mime',
                            ],
                        ],
                    ],
                    'required' => [
                        'name',
                        'url',
                    ],
                ],
                'title' => 'Create Affiliation',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
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
                        'id' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'description' => 'Affiliation ID to update.',
                        ],
                        'name' => [
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 150,
                            'description' => 'Institution name.',
                        ],
                        'url' => [
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 500,
                            'description' => 'URL link.',
                        ],
                        'is_active' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'Active status.',
                        ],
                        'display_order' => [
                            'type' => 'integer',
                            'description' => 'Sort order.',
                        ],
                        'logo' => [
                            'type' => [
                                'object',
                                'null',
                            ],
                            'description' => 'Upload a new logo or null to remove existing.',
                            'properties' => [
                                'data_base64' => [
                                    'type' => 'string',
                                    'description' => 'Base64 encoded image data.',
                                ],
                                'mime' => [
                                    'type' => 'string',
                                    'enum' => [
                                        'image/jpeg',
                                        'image/png',
                                        'image/webp',
                                        'image/svg+xml',
                                    ],
                                    'description' => 'MIME type.',
                                ],
                            ],
                            'required' => [
                                'data_base64',
                                'mime',
                            ],
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Update Affiliation',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
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
                        'id' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'description' => 'Affiliation ID to delete.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Delete Affiliation',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
        ];
    }

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
     * Lists site settings rows (one per language) enriched with active language status
     */
    public function listSiteSettings(array $args): array
    {
        $langMeta = $this->getSiteLanguageMap();
        $activeOnly = isset($args['active_only']) && (string) $args['active_only'] === 'y';

        $model = $this->freshModel('mod_settings');
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
        }
        $model->order('id', 'ASC')->getResult(true, true);
        $items = $model->toArray();
        $rows = is_array($items) ? $items : [];

        $enriched = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowLang = (string) ($row['lang'] ?? '');
            $info = $langMeta['map'][$rowLang] ?? null;
            $row['lang_name'] = $info['name'] ?? $rowLang;
            $row['lang_status'] = $info['status'] ?? 'n';
            $row['is_default_lang'] = $info['is_default'] ?? 'n';

            if ($activeOnly && $row['lang_status'] !== 'y') {
                continue;
            }
            $enriched[] = $row;
        }

        return [
            'items' => $enriched,
            'active_languages' => $langMeta['active_languages'],
            'default_language' => $langMeta['default_language'],
        ];
    }

    /**
     * Returns a single site settings row enriched with active language status
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

        $langMeta = $this->getSiteLanguageMap();
        $info = $langMeta['map'][$lang] ?? null;
        $row['lang_name'] = $info['name'] ?? $lang;
        $row['lang_status'] = $info['status'] ?? 'n';
        $row['is_default_lang'] = $info['is_default'] ?? 'n';

        return [
            'item' => $row,
            'active_languages' => $langMeta['active_languages'],
            'default_language' => $langMeta['default_language'],
        ];
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
