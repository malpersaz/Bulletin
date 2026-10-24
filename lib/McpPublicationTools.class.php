<?php
/**
 * Amed Framework
 * MCP Publication Tools - CRUD implementations for mod_publication and mod_publication_category
 * lib/McpPublicationTools.class.php
 */

class McpPublicationTools extends McpCrudToolsBase
{
    /**
     * Return array of tool definitions provided by this domain tool class.
     *
     * @return array<string, array>
     */
    public static function definitions(): array
    {
        return [
            'list_publications' => [
                'module' => 'mod_publication',
                'role' => 'list',
                'domain' => 'publication',
                'handler' => 'listPublications',
                'description' => 'List publications with optional filters. Returns paginated items (id, lang, name, category_id, status, deck, slider, seo, created_at, updated_at) plus the total count.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'category_id' => ['type' => 'integer', 'description' => 'Filter by publication category ID.'],
                        'lang' => ['type' => 'string', 'description' => 'Filter by language code (e.g. ku, en, tr).'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by publish status: y (published) or n (draft).'],
                        'deck' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by deck flag.'],
                        'slider' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by slider flag.'],
                        'search' => ['type' => 'string', 'description' => 'Search term matched against publication names.'],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10, 'description' => 'Page size (1-50, default 10).'],
                        'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Number of items to skip for pagination.'],
                    ],
                ],
                'title' => 'List Publications',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_publication' => [
                'module' => 'mod_publication',
                'role' => 'list',
                'domain' => 'publication',
                'handler' => 'getPublication',
                'description' => 'Get a single publication record with all fields by numeric id or URL slug (seo + optional lang), including the full body content, summary, and SEO metadata.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Publication record ID (optional if seo is provided).'],
                        'seo' => ['type' => 'string', 'description' => 'Publication URL slug (optional if id is provided).'],
                        'lang' => ['type' => 'string', 'description' => 'Language code (e.g. tr, ku, en) when resolving by seo slug.'],
                    ],
                ],
                'title' => 'Get Publication',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_publication' => [
                'module' => 'mod_publication',
                'role' => 'add',
                'domain' => 'publication',
                'handler' => 'createPublication',
                'description' => 'Create a publication record. The summary must be between 90 and 160 characters (90-120 deck / 120-160 SERP snippet standard). The publication category must exist and match the publication language. The seo slug must be unique per language; it defaults to a slug generated from the name.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Publication name (1-255 characters).'],
                        'lang' => ['type' => 'string', 'description' => 'Language code of an active site language (e.g. ku, en, tr).'],
                        'category_id' => ['type' => 'integer', 'description' => 'ID of an existing publication category with the same language.'],
                        'content' => ['type' => 'string', 'description' => 'Full HTML body of the publication.'],
                        'summary' => ['type' => 'string', 'minLength' => 90, 'maxLength' => 160, 'description' => 'Deck summary and meta description (90-160 characters; 120-155 recommended).'],
                        'seo' => ['type' => 'string', 'maxLength' => 255, 'description' => 'URL slug. Defaults to a slug generated from the name. Must be unique per language.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO title. Defaults to the name.'],
                        'keywords' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO keywords. Defaults to empty.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO description. Defaults to the summary.'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'y', 'description' => 'Publish status: y (published) or n (draft).'],
                        'deck' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Show on deck: y or n.'],
                        'slider' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Show in slider flag.'],
                        'created_at' => ['type' => 'string', 'description' => 'Creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.'],
                        'video' => ['type' => 'string', 'description' => 'Comma-separated video references (video:ID for local videos, vid:ID for embedded).'],
                        'gallery_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Related gallery ID.'],
                        'file_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Related attachment file ID.'],
                        'acl_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Access control group ID (0 = public).'],
                        'user_id' => ['type' => 'integer', 'description' => 'Author user ID.'],
                        'username' => ['type' => 'string', 'description' => 'Author username (e.g. deryawelat).'],
                        'image' => ['type' => ['object', 'null'], 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['name', 'lang', 'category_id', 'content', 'summary'],
                ],
                'title' => 'Create Publication',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_publication' => [
                'module' => 'mod_publication',
                'role' => 'edit',
                'domain' => 'publication',
                'handler' => 'updatePublication',
                'description' => 'Partially update a publication record. Only provided fields change; the summary (if provided) must be between 90 and 160 characters (auto-synced with description when omitted), and the seo slug (if provided) must remain unique per language. When the seo or lang changes, a 301 redirect is registered in mod_redirect.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Publication record ID.'],
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New publication name.'],
                        'lang' => ['type' => 'string', 'description' => 'New language code of an active site language. The category must match it.'],
                        'category_id' => ['type' => 'integer', 'description' => 'New publication category ID with a matching language.'],
                        'content' => ['type' => 'string', 'description' => 'New full HTML body.'],
                        'summary' => ['type' => 'string', 'minLength' => 90, 'maxLength' => 160, 'description' => 'New deck summary and meta description (90-160 characters; 120-155 recommended).'],
                        'seo' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New URL slug, unique per language.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO title.'],
                        'keywords' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO keywords.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO description.'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New publish status.'],
                        'deck' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New deck flag.'],
                        'slider' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New slider flag.'],
                        'created_at' => ['type' => 'string', 'description' => 'New creation datetime (YYYY-MM-DD HH:MM:SS).'],
                        'video' => ['type' => 'string', 'description' => 'New comma-separated video references.'],
                        'gallery_id' => ['type' => 'integer', 'description' => 'New related gallery ID.'],
                        'file_id' => ['type' => 'integer', 'description' => 'New related attachment file ID.'],
                        'acl_id' => ['type' => 'integer', 'description' => 'New access control group ID.'],
                        'user_id' => ['type' => 'integer', 'description' => 'Author user ID.'],
                        'username' => ['type' => 'string', 'description' => 'Author username (e.g. deryawelat).'],
                        'image' => ['type' => ['object', 'null'], 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Update Publication',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
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
                'title' => 'Delete Publication',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
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
                'title' => 'List Publication Categories',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_publication_category' => [
                'module' => 'mod_publication_category',
                'role' => 'list',
                'domain' => 'publication',
                'handler' => 'getPublicationCategory',
                'description' => 'Get a single publication category record with all fields by numeric id or URL slug (seo + optional lang).',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Publication category record ID (optional if seo is provided).'],
                        'seo' => ['type' => 'string', 'description' => 'Publication category URL slug (optional if id is provided).'],
                        'lang' => ['type' => 'string', 'description' => 'Language code (e.g. tr, ku, en) when resolving by seo slug.'],
                    ],
                ],
                'title' => 'Get Publication Category',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
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
                        'image' => ['type' => ['object', 'null'], 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['name', 'lang'],
                ],
                'title' => 'Create Publication Category',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_publication_category' => [
                'module' => 'mod_publication_category',
                'role' => 'edit',
                'domain' => 'publication',
                'handler' => 'updatePublicationCategory',
                'description' => 'Partially update a publication category. When the name, language, seo, or acl changes, an existing menu entry pointing at the old category URL ({lang}/publications/{seo}) is updated and a 301 redirect is registered in mod_redirect.',
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
                        'image' => ['type' => ['object', 'null'], 'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.', 'properties' => ['data_base64' => ['type' => 'string', 'description' => 'Base64-encoded image file (max 10 MB decoded).'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Update Publication Category',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
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
                'title' => 'Delete Publication Category',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
        ];
    }

    private const PUBLICATION_IMAGE_SIZES = [
        '/' => ['width' => 1600, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 600, 'height' => null, 'option' => 'width'],
    ];

    private const PUBLICATION_CATEGORY_IMAGE_SIZES = [
        '/' => ['width' => 200, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 150, 'height' => 90, 'option' => 'extract'],
    ];

    // ------------------------------------------------------------------
    // mod_publication tools
    // ------------------------------------------------------------------

    /**
     * Lists publications with optional filters
     */
    public function listPublications(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_publication');
        $model->select('id, lang, name, category_id, status, deck, slider, seo, created_at, updated_at');
        $this->applyPublicationFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_publication');
        $countModel->select('id');
        $this->applyPublicationFilters($countModel, $args);
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
     * Returns a single publication record with all fields
     */
    public function getPublication(array $args): array
    {
        $model = $this->loadRecordByIdOrSeo('mod_publication', $args, 'Publication');

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Publication record not found.");
        }

        $row['item'] = $row;
        return $row;
    }

    /**
     * Creates a publication record (mirrors the admin add validator)
     */
    public function createPublication(array $args): array
    {
        $name = trim(strip_tags((string) ($args['name'] ?? '')));
        if ($name === '' || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (1-255 characters).");
        }

        $lang = $this->requireActiveLang($args);
        $categoryId = (int) ($args['category_id'] ?? 0);
        if ($categoryId <= 0) {
            throw new McpToolException("Field 'category_id' is required.");
        }
        $this->assertRecordMatchesLang('mod_publication_category', $categoryId, $lang, 'Publication category');

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

        $seo = trim((string) ($args['seo'] ?? ''));
        if ($seo === '') {
            $seo = self::makeSeo($name, $lang);
        } else {
            $seo = Helper::legacy_seo($seo, $lang);
        }
        if ($seo === '' || mb_strlen($seo) > 255) {
            throw new McpToolException("Field 'seo' must be 1-255 characters.");
        }
        if ($this->recordSeoExists('mod_publication', $seo, $lang)) {
            throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
        }

        $model = $this->freshModel('mod_publication');
        $model->name = $name;
        $model->lang = $lang;
        $model->category_id = $categoryId;
        $model->content = $content;
        $model->summary = $summary;
        $model->seo = $seo;
        $model->title = $this->optionalString($args, 'title', $name, 255);
        $model->keywords = $this->optionalString($args, 'keywords', '', 255);
        $model->description = $this->optionalString($args, 'description', $summary, 255);
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        $model->deck = $this->optionalEnum($args, 'deck', 'n', ['y', 'n']);
        $model->slider = $this->optionalEnum($args, 'slider', 'n', ['y', 'n']);
        $model->created_at = $this->optionalDateTime($args, 'created_at', date('Y-m-d H:i:s'));
        $model->updated_at = date('Y-m-d H:i:s');
        $model->video = $this->serializeVideoRefs($args['video'] ?? '');
        $model->gallery_id = (int) ($args['gallery_id'] ?? 0);
        $model->file_id = (int) ($args['file_id'] ?? 0);
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $authorUserId = $this->actingUserId;
        if (!empty($args['user_id'])) {
            $authorUserId = (int) $args['user_id'];
        } elseif (!empty($args['username'])) {
            $targetUsername = trim((string) $args['username']);
            $db = Load::lib('Database');
            $db->fetch_array("SELECT id FROM mod_user WHERE username = '" . Obuilder::cc($targetUsername) . "' AND status = 'y' LIMIT 1");
            if ($db->getSize() > 0) {
                $userRow = $db->get_row();
                $authorUserId = (int) ($userRow['id'] ?? $this->actingUserId);
            }
        }
        $model->user_id = $authorUserId;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_publication', $args, self::PUBLICATION_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_publication', $imageUrl, self::PUBLICATION_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the publication record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getPublication(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a publication record
     */
    public function updatePublication(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadPublicationById($id);

        $oldLang = (string) $model->lang;
        $oldSeo = (string) $model->seo;
        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : $oldLang;
        $categoryId = isset($args['category_id']) ? (int) $args['category_id'] : (int) $model->category_id;

        if ($categoryId !== (int) $model->category_id || $lang !== $oldLang) {
            $this->assertRecordMatchesLang('mod_publication_category', $categoryId, $lang, 'Publication category');
        }

        if (isset($args['name'])) {
            $name = trim(strip_tags((string) $args['name']));
            if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 2-255 characters.");
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

        if (isset($args['seo'])) {
            $seo = Helper::legacy_seo(trim((string) $args['seo']), $lang);
            if ($seo === '' || mb_strlen($seo) > 255) {
                throw new McpToolException("Field 'seo' must be 1-255 characters.");
            }
            if ($this->recordSeoExists('mod_publication', $seo, $lang, $id)) {
                throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
            }
            $model->seo = $seo;
        }

        foreach (['title', 'keywords', 'description'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalString($args, $field, (string) $model->{$field}, 255);
            }
        }

        $this->synchronizeSummaryAndDescription($model, $args);
        $this->assertContentMatchesLang($lang, (string) $model->name, (string) $model->summary, (string) $model->content);

        foreach (['status', 'deck', 'slider'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalEnum($args, $field, (string) $model->{$field}, ['y', 'n']);
            }
        }

        if (isset($args['created_at'])) {
            $model->created_at = $this->optionalDateTime($args, 'created_at', (string) $model->created_at);
        }

        if (isset($args['video'])) {
            $model->video = $this->serializeVideoRefs($args['video']);
        }

        foreach (['gallery_id', 'file_id', 'acl_id'] as $intField) {
            if (isset($args[$intField])) {
                $model->{$intField} = (int) $args[$intField];
            }
        }

        if (isset($args['user_id'])) {
            $model->user_id = (int) $args['user_id'];
        } elseif (isset($args['username'])) {
            $targetUsername = trim((string) $args['username']);
            $db = Load::lib('Database');
            $db->fetch_array("SELECT id FROM mod_user WHERE username = '" . Obuilder::cc($targetUsername) . "' AND status = 'y' LIMIT 1");
            if ($db->getSize() > 0) {
                $userRow = $db->get_row();
                $model->user_id = (int) $userRow['id'];
            }
        }

        $model->lang = $lang;
        $model->category_id = $categoryId;
        $model->updated_at = date('Y-m-d H:i:s');

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_publication', $args, self::PUBLICATION_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the publication record.');
        }

        $this->registerSlugRedirectIfChanged($oldLang, $oldSeo, (string) $model->lang, (string) $model->seo, 'publication');

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getPublication(['id' => $id])['item']
        ];
    }

    /**
     * Deletes a publication record with full admin parity cleanup
     */
    public function deletePublication(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadPublicationById($id);

        $this->assertModerateDelete('mod_publication', (int) $model->user_id);

        $this->removeBridgeRelations('mod_publication', $id);

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_publication', (string) $model->image, self::PUBLICATION_IMAGE_SIZES);
        }

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // mod_publication_category tools
    // ------------------------------------------------------------------

    /**
     * Lists publication categories
     */
    public function listPublicationCategories(array $args): array
    {
        return $this->listCategoriesGeneric('mod_publication_category', $args, 50);
    }

    /**
     * Returns a single Publication category with all fields
     */
    public function getPublicationCategory(array $args): array
    {
        $model = $this->loadRecordByIdOrSeo('mod_publication_category', $args, 'Publication category');

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Publication category record not found.");
        }

        $row['item'] = $row;
        return $row;
    }

    /**
     * Creates a Publication category
     */
    public function createPublicationCategory(array $args): array
    {
        $name = trim(strip_tags((string) ($args['name'] ?? '')));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }

        $lang = $this->requireActiveLang($args);
        $this->assertContentMatchesLang($lang, $name, '', '');

        $parentId = (int) ($args['parent_id'] ?? 0);
        if ($parentId > 0) {
            $parent = $this->loadPublicationCategoryById($parentId);
            if ((string) $parent->lang !== $lang) {
                throw new McpToolException("Parent category '{$parentId}' belongs to language '" . $parent->lang . "', cannot nest a '{$lang}' category under it.");
            }
        }

        $model = $this->freshModel('mod_publication_category');
        $model->name = $name;
        $model->lang = $lang;
        $model->parent_id = $parentId;
        $model->root = $this->optionalEnum($args, 'root', 'n', ['y', 'n']);
        $model->seo = $this->optionalString($args, 'seo', self::makeSeo($name, $lang), 255);
        $model->title = $this->optionalString($args, 'title', $name, 255);
        $model->keywords = $this->optionalString($args, 'keywords', '', 255);
        $model->description = $this->optionalString($args, 'description', '', 255);
        $model->show_on_homepage = $this->optionalEnum($args, 'show_on_homepage', 'n', ['y', 'n']);
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $model->menu = 0;
        $model->user_id = $this->actingUserId;
        $model->order = $this->nextSiblingOrder('mod_publication_category', $parentId);

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_publication_category', $args, self::PUBLICATION_CATEGORY_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_publication_category', $imageUrl, self::PUBLICATION_CATEGORY_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the publication category record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getPublicationCategory(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a Publication category and syncs related menu entries
     */
    public function updatePublicationCategory(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadPublicationCategoryById($id);

        $oldLang = (string) $model->lang;
        $oldSeo = (string) $model->seo;
        $previousMenuUrl = $oldLang . '/publications/' . $oldSeo;

        if (isset($args['name'])) {
            $name = trim(strip_tags((string) $args['name']));
            if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 2-255 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['lang'])) {
            $model->lang = $this->requireActiveLang($args);
        }

        $this->assertContentMatchesLang((string) $model->lang, (string) $model->name, '', '');

        if (isset($args['parent_id'])) {
            $parentId = (int) $args['parent_id'];
            if ($parentId > 0) {
                if ($parentId === $id) {
                    throw new McpToolException("A category cannot be its own parent.");
                }
                $parent = $this->loadPublicationCategoryById($parentId);
                if ((string) $parent->lang !== (string) $model->lang) {
                    throw new McpToolException("Parent category '{$parentId}' belongs to language '" . $parent->lang . "', cannot nest a '" . $model->lang . "' category under it.");
                }
            }
            $model->parent_id = $parentId;
        }

        if (isset($args['root'])) {
            $model->root = $this->optionalEnum($args, 'root', (string) $model->root, ['y', 'n']);
        }

        if (isset($args['show_on_homepage'])) {
            $model->show_on_homepage = $this->optionalEnum($args, 'show_on_homepage', (string) $model->show_on_homepage, ['y', 'n']);
        }

        if (isset($args['seo'])) {
            $model->seo = $this->optionalString($args, 'seo', (string) $model->seo, 255);
        }

        foreach (['title', 'keywords', 'description'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalString($args, $field, (string) $model->{$field}, 255);
            }
        }

        if (isset($args['acl_id'])) {
            $model->acl_id = (int) $args['acl_id'];
        }

        if (isset($args['order'])) {
            $order = (int) $args['order'];
            if ($order < 0) {
                throw new McpToolException("Field 'order' must be a non-negative integer.");
            }
            $model->order = $order;
        }

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_publication_category', $args, self::PUBLICATION_CATEGORY_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the publication category record.');
        }

        $this->syncPublicationMenuOnUpdate($previousMenuUrl, $model);
        $this->registerSlugRedirectIfChanged($oldLang, $oldSeo, (string) $model->lang, (string) $model->seo, 'publications');

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getPublicationCategory(['id' => $id])['item']
        ];
    }

    /**
     * Recursively deletes a Publication category tree with admin parity cleanup
     */
    public function deletePublicationCategory(array $args): array
    {
        $id = $this->requireId($args);
        $deletedIds = [];
        $this->deletePublicationCategoryTree($id, $deletedIds);

        return ['deleted' => true, 'ids' => $deletedIds];
    }

    // ------------------------------------------------------------------
    // Publication helpers
    // ------------------------------------------------------------------

    private function deletePublicationCategoryTree(int $id, array &$deletedIds): void
    {
        $model = $this->loadPublicationCategoryById($id);

        $this->assertModerateDelete('mod_publication_category', (int) $model->user_id);

        $childrenModel = $this->freshModel('mod_publication_category');
        $childrenModel->where_parent_id($id)->getResult(true, true);
        foreach ($childrenModel->toArray() as $child) {
            $this->deletePublicationCategoryTree((int) $child['id'], $deletedIds);
        }

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_publication_category', (string) $model->image, self::PUBLICATION_CATEGORY_IMAGE_SIZES);
        }

        // Publication category parity: gallery/attachment bridge relations are cleaned up too
        $this->removeBridgeRelations('mod_publication_category', $id);

        $menuUrl = $model->lang . '/publications/' . $model->seo;
        $menuModel = $this->freshModel('mod_menu');
        if ($menuModel->exists_url($menuUrl)) {
            $menuModel->getResultOne(true, true)->delete();
        }

        $model->delete();
        $deletedIds[] = $id;
    }

    private function syncPublicationMenuOnUpdate(string $previousMenuUrl, $categoryModel): void
    {
        $menuModel = $this->freshModel('mod_menu');
        if ($menuModel->exists_url($previousMenuUrl)) {
            $menuModel->getResultOne(true, true)->sync();
            $menuModel->name = $categoryModel->name;
            $menuModel->lang = $categoryModel->lang;
            $menuModel->url = $categoryModel->lang . '/publications/' . $categoryModel->seo;
            $menuModel->acl_id = $categoryModel->acl_id;
            $menuModel->update();
        }
    }

    private function loadPublicationById(int $id): mod_publication_model
    {
        $model = $this->freshModel('mod_publication');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Publication record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function loadPublicationCategoryById(int $id): mod_publication_category_model
    {
        $model = $this->freshModel('mod_publication_category');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Publication category record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyPublicationFilters($model, array $args): void
    {
        if (isset($args['category_id']) && (int) $args['category_id'] > 0) {
            $model->where_category_id((int) $args['category_id']);
        }
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
        }
        if (isset($args['status']) && in_array($args['status'], ['y', 'n'], true)) {
            $model->where_status($args['status']);
        }
        if (isset($args['deck']) && in_array($args['deck'], ['y', 'n'], true)) {
            $model->where_deck($args['deck']);
        }
        if (isset($args['slider']) && in_array($args['slider'], ['y', 'n'], true)) {
            $model->where_slider($args['slider']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $model->where('name LIKE \'%' . Obuilder::cc(trim((string) $args['search'])) . '%\'');
        }
    }
}
