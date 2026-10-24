<?php
/**
 * Amed Framework
 * MCP Headline Tools - CRUD implementations for mod_headline
 * lib/McpHeadlineTools.class.php
 */

class McpHeadlineTools extends McpCrudToolsBase
{
    /**
     * Headline image sizes, mirrors the admin controller option array
     */
    private const HEADLINE_IMAGE_SIZES = [
        '/' => ['width' => 2000, 'height' => 750, 'option' => 'width'],
        '/thumb' => ['width' => 100, 'height' => null, 'option' => 'width'],
    ];

    // ------------------------------------------------------------------
    // Tool definitions
    // ------------------------------------------------------------------

    public static function definitions(): array
    {
        return [
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
                            'enum' => ['y', 'n'],
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
                ],
                'title' => 'List Headlines',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
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
                    'required' => ['id']
                ],
                'title' => 'Get Headline',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
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
                            'enum' => ['y', 'n'],
                            'default' => 'y',
                            'description' => 'Publish status: y (published) or n (draft).'
                        ],
                        'image' => [
                            'type' => ['object', 'null'],
                            'description' => 'Image upload (base64-encoded) or null to explicitly set no image. On update: absent = unchanged.',
                            'properties' => [
                                'data_base64' => [
                                    'type' => 'string',
                                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                                ],
                                'mime' => [
                                    'type' => 'string',
                                    'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
                                ]
                            ],
                            'required' => ['data_base64', 'mime']
                        ]
                    ],
                    'required' => ['name', 'lang']
                ],
                'title' => 'Create Headline',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
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
                            'enum' => ['y', 'n'],
                            'description' => 'New publish status.'
                        ],
                        'created_at' => [
                            'type' => 'string',
                            'description' => 'New creation datetime (YYYY-MM-DD HH:MM:SS).'
                        ],
                        'image' => [
                            'type' => ['object', 'null'],
                            'description' => 'Image upload (base64-encoded) or null to remove the existing one. Absent = unchanged.',
                            'properties' => [
                                'data_base64' => [
                                    'type' => 'string',
                                    'description' => 'Base64-encoded image file (max 10 MB decoded).'
                                ],
                                'mime' => [
                                    'type' => 'string',
                                    'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
                                ]
                            ],
                            'required' => ['data_base64', 'mime']
                        ]
                    ],
                    'required' => ['id']
                ],
                'title' => 'Update Headline',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
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
                    'required' => ['id']
                ],
                'title' => 'Delete Headline',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
        ];
    }

    // ------------------------------------------------------------------
    // mod_headline tools
    // ------------------------------------------------------------------

    /**
     * Lists headline items with optional filters
     */
    public function listHeadlines(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_headline');
        $model->select('id, lang, name, status, created_at');
        $this->applyHeadlineFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_headline');
        $countModel->select('id');
        $this->applyHeadlineFilters($countModel, $args);
        $countModel->getResult(true, true);
        $total = $countModel->getSize();

        return [
            'items' => is_array($items) ? $items : [],
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Returns a single headline item with all fields
     */
    public function getHeadline(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadHeadlineById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Headline record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a headline item (mirrors the admin add validator)
     */
    public function createHeadline(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }

        $lang = $this->requireActiveLang($args);
        $this->assertContentMatchesLang($lang, ['name' => $name]);

        $model = $this->freshModel('mod_headline');
        $model->name = $name;
        $model->lang = $lang;
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        // The admin controller always stamps the creation time on add
        $model->created_at = date('Y-m-d H:i:s');
        $model->user_id = $this->actingUserId;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_headline', $args, self::HEADLINE_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_headline', $imageUrl, self::HEADLINE_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the headline record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getHeadline(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a headline item
     */
    public function updateHeadline(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadHeadlineById($id);

        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;

        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 2-255 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['status'])) {
            $model->status = $this->optionalEnum($args, 'status', (string) $model->status, ['y', 'n']);
        }

        if (isset($args['created_at'])) {
            $model->created_at = $this->optionalDateTime($args, 'created_at', (string) $model->created_at);
        }

        $model->lang = $lang;
        $this->assertContentMatchesLang($lang, ['name' => (string) $model->name]);

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_headline', $args, self::HEADLINE_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the headline record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getHeadline(['id' => $id])['item']
        ];
    }

    /**
     * Deletes a headline item with admin parity cleanup
     */
    public function deleteHeadline(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadHeadlineById($id);

        $this->assertModerateDelete('mod_headline', (int) $model->user_id);

        $this->removeBridgeRelations('mod_headline', $id);

        if ((string) ($model->image ?? '') !== '') {
            $this->deleteImageFiles('mod_headline', (string) $model->image, self::HEADLINE_IMAGE_SIZES);
        }

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // Headline helpers
    // ------------------------------------------------------------------

    private function loadHeadlineById(int $id): mod_headline_model
    {
        $model = $this->freshModel('mod_headline');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Headline record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyHeadlineFilters($model, array $args): void
    {
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
        }
        if (isset($args['status']) && in_array($args['status'], ['y', 'n'], true)) {
            $model->where_status($args['status']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $model->where('name LIKE \'%' . Obuilder::cc(trim((string) $args['search'])) . '%\'');
        }
    }
}
