<?php
/**
 * Amed Framework
 * MCP Announcement Tools - CRUD implementations for mod_announcements
 * lib/McpAnnouncementTools.class.php
 */

class McpAnnouncementTools extends McpCrudToolsBase
{
    /**
     * Return array of tool definitions provided by this domain tool class.
     *
     * @return array<string, array>
     */
    public static function definitions(): array
    {
        return [
            'list_announcements' => [
                'module' => 'mod_announcements',
                'role' => 'list',
                'domain' => 'announcement',
                'handler' => 'listAnnouncements',
                'description' => 'List announcements with optional filters. Returns paginated items (id, lang, name, status, deck, created_at) plus the total count.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => ['type' => 'string', 'description' => 'Filter by language code (e.g. ku, en, tr).'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by publish status: y (published) or n (draft).'],
                        'deck' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by deck flag.'],
                        'search' => ['type' => 'string', 'description' => 'Search term matched against announcement names.'],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10, 'description' => 'Page size (1-50, default 10).'],
                        'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Number of items to skip for pagination.'],
                    ],
                ],
                'title' => 'List Announcements',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_announcement' => [
                'module' => 'mod_announcements',
                'role' => 'list',
                'domain' => 'announcement',
                'handler' => 'getAnnouncement',
                'description' => 'Get a single announcement record with all fields, including the full body content and summary.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Announcement record ID.'],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Get Announcement',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_announcement' => [
                'module' => 'mod_announcements',
                'role' => 'add',
                'domain' => 'announcement',
                'handler' => 'createAnnouncement',
                'description' => 'Create an announcement record. The summary must be between 90 and 160 characters (90-120 deck / 120-160 SERP snippet standard). Announcement names are 1-70 characters.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'maxLength' => 70, 'description' => 'Announcement name (1-70 characters).'],
                        'lang' => ['type' => 'string', 'description' => 'Language code of an active site language (e.g. ku, en, tr).'],
                        'content' => ['type' => 'string', 'description' => 'Full HTML body of the announcement.'],
                        'summary' => ['type' => 'string', 'minLength' => 90, 'maxLength' => 160, 'description' => 'Deck summary and meta description (90-160 characters; 120-155 recommended).'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'y', 'description' => 'Publish status: y (published) or n (draft).'],
                        'deck' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Show on deck: y or n.'],
                        'created_at' => ['type' => 'string', 'description' => 'Creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.'],
                        'gallery_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Related gallery ID.'],
                        'file_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Related attachment file ID.'],
                        'acl_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Access control group ID (0 = public).'],
                        'image' => ['type' => ['object', 'null'], 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['name', 'lang', 'content', 'summary'],
                ],
                'title' => 'Create Announcement',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_announcement' => [
                'module' => 'mod_announcements',
                'role' => 'edit',
                'domain' => 'announcement',
                'handler' => 'updateAnnouncement',
                'description' => 'Partially update an announcement record. Only provided fields change; the summary (if provided) must be between 90 and 160 characters.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Announcement record ID.'],
                        'name' => ['type' => 'string', 'maxLength' => 70, 'description' => 'New announcement name (1-70 characters).'],
                        'lang' => ['type' => 'string', 'description' => 'New language code of an active site language.'],
                        'content' => ['type' => 'string', 'description' => 'New full HTML body.'],
                        'summary' => ['type' => 'string', 'minLength' => 90, 'maxLength' => 160, 'description' => 'New deck summary (90-160 characters; 120-155 recommended).'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New publish status.'],
                        'deck' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New deck flag.'],
                        'created_at' => ['type' => 'string', 'description' => 'New creation datetime (YYYY-MM-DD HH:MM:SS).'],
                        'gallery_id' => ['type' => 'integer', 'description' => 'New related gallery ID.'],
                        'file_id' => ['type' => 'integer', 'description' => 'New related attachment file ID.'],
                        'acl_id' => ['type' => 'integer', 'description' => 'New access control group ID.'],
                        'image' => ['type' => ['object', 'null'], 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Update Announcement',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_announcement' => [
                'module' => 'mod_announcements',
                'role' => 'delete',
                'domain' => 'announcement',
                'handler' => 'deleteAnnouncement',
                'description' => 'Delete an announcement record. Related gallery, attachment, and FAQ relations and uploaded image files are cleaned up automatically.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Announcement record ID.'],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Delete Announcement',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
        ];
    }

    private const ANNOUNCEMENT_IMAGE_SIZES = [
        '/' => ['width' => 600, 'height' => 780, 'option' => 'width'],
        '/thumb' => ['width' => 300, 'height' => 390, 'option' => 'width'],
        '/original' => ['width' => null, 'height' => null, 'option' => 'original'],
    ];

    /**
     * Lists announcements with optional filters
     */
    public function listAnnouncements(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_announcements');
        $model->select('id, lang, name, status, deck, created_at');
        $this->applyAnnouncementFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_announcements');
        $countModel->select('id');
        $this->applyAnnouncementFilters($countModel, $args);
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
     * Returns a single announcement with all fields
     */
    public function getAnnouncement(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadAnnouncementById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Announcement record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates an announcement (mirrors the admin add validator)
     */
    public function createAnnouncement(array $args): array
    {
        $name = trim(strip_tags((string) ($args['name'] ?? '')));
        if ($name === '' || mb_strlen($name) > 70) {
            throw new McpToolException("Field 'name' is required (1-70 characters).");
        }

        $lang = $this->requireActiveLang($args);

        $content = $this->sanitizeBodyHtml((string) ($args['content'] ?? ''));
        if (trim(strip_tags($content)) === '') {
            throw new McpToolException("Field 'content' is required.");
        }

        $summary = trim(strip_tags((string) ($args['summary'] ?? '')));
        if ($summary === '') {
            throw new McpToolException("Field 'summary' is required.");
        }
        $this->assertSummaryLength($summary);
        $this->assertContentMatchesLang($lang, $name, $summary, $content);

        $model = $this->freshModel('mod_announcements');
        $model->name = $name;
        $model->lang = $lang;
        $model->content = $content;
        $model->summary = $summary;
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        $model->deck = $this->optionalEnum($args, 'deck', 'n', ['y', 'n']);
        $model->created_at = $this->optionalDateTime($args, 'created_at', date('Y-m-d H:i:s'));
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $model->gallery_id = (int) ($args['gallery_id'] ?? 0);
        $model->file_id = (int) ($args['file_id'] ?? 0);
        $model->user_id = $this->actingUserId;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_announcements', $args, self::ANNOUNCEMENT_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_announcements', $imageUrl, self::ANNOUNCEMENT_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the announcement record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getAnnouncement(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates an announcement
     */
    public function updateAnnouncement(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadAnnouncementById($id);

        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;

        if (isset($args['name'])) {
            $name = trim(strip_tags((string) $args['name']));
            if ($name === '' || mb_strlen($name) > 70) {
                throw new McpToolException("Field 'name' must be 1-70 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['content'])) {
            $content = $this->sanitizeBodyHtml((string) $args['content']);
            if (trim(strip_tags($content)) === '') {
                throw new McpToolException("Field 'content' cannot be empty.");
            }
            $model->content = $content;
        }

        if (isset($args['summary'])) {
            $summary = trim(strip_tags((string) $args['summary']));
            $this->assertSummaryLength($summary);
            $model->summary = $summary;
        }

        $this->assertContentMatchesLang($lang, (string) $model->name, (string) $model->summary, (string) $model->content);

        foreach (['status', 'deck'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalEnum($args, $field, (string) $model->{$field}, ['y', 'n']);
            }
        }

        if (isset($args['created_at'])) {
            $model->created_at = $this->optionalDateTime($args, 'created_at', (string) $model->created_at);
        }

        if (isset($args['acl_id'])) {
            $model->acl_id = (int) $args['acl_id'];
        }
        if (isset($args['gallery_id'])) {
            $model->gallery_id = (int) $args['gallery_id'];
        }
        if (isset($args['file_id'])) {
            $model->file_id = (int) $args['file_id'];
        }

        $model->lang = $lang;

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_announcements', $args, self::ANNOUNCEMENT_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the announcement record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getAnnouncement(['id' => $id])['item']
        ];
    }

    /**
     * Deletes an announcement with admin parity cleanup
     */
    public function deleteAnnouncement(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadAnnouncementById($id);

        $this->assertModerateDelete('mod_announcements', (int) $model->user_id);

        $this->removeBridgeRelations('mod_announcements', $id);

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_announcements', (string) $model->image, self::ANNOUNCEMENT_IMAGE_SIZES);
        }

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // Announcement helpers
    // ------------------------------------------------------------------

    private function loadAnnouncementById(int $id): mod_announcements_model
    {
        $model = $this->freshModel('mod_announcements');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Announcement record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyAnnouncementFilters($model, array $args): void
    {
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
        }
        if (isset($args['status']) && in_array($args['status'], ['y', 'n'], true)) {
            $model->where_status($args['status']);
        }
        if (isset($args['deck']) && in_array($args['deck'], ['y', 'n'], true)) {
            $model->where_deck($args['deck']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $model->where('name LIKE \'%' . Obuilder::cc(trim((string) $args['search'])) . '%\'');
        }
    }
}
