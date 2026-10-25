<?php
/**
 * Amed Framework
 * MCP News Tools - CRUD implementations for mod_news and mod_news_category
 * lib/McpNewsTools.class.php
 */

class McpNewsTools extends McpCrudToolsBase
{
    private const NEWS_IMAGE_SIZES = [
        '/' => ['width' => 1280, 'height' => 720, 'option' => 'cover'],
        '/thumb' => ['width' => 480, 'height' => 270, 'option' => 'cover'],
    ];

    private const CATEGORY_IMAGE_SIZES = [
        '/' => ['width' => 200, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 100, 'height' => null, 'option' => 'width'],
    ];

    /**
     * Return array of tool definitions provided by this domain tool class.
     *
     * @return array<string, array>
     */
    public static function definitions(): array
    {
        return [
            'list_news' => [
                'module' => 'mod_news',
                'role' => 'list',
                'domain' => 'news',
                'handler' => 'listNews',
                'description' => 'List news articles with optional filters. Returns paginated items plus the total count.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'category_id' => [
                            'type' => 'integer',
                            'description' => 'Filter by news category ID.',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Filter by language code (e.g. ku, en, tr).',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                            'description' => 'Filter by publish status: y (published) or n (draft).',
                        ],
                        'seo' => [
                            'type' => 'string',
                            'description' => 'Filter by exact SEO slug.',
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'Search term matched against news titles.',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 50,
                            'default' => 10,
                            'description' => 'Page size (1-50, default 10).',
                        ],
                        'offset' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'default' => 0,
                            'description' => 'Number of items to skip for pagination.',
                        ],
                    ],
                ],
                'title' => 'List News Articles',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_news' => [
                'module' => 'mod_news',
                'role' => 'list',
                'domain' => 'news',
                'handler' => 'getNews',
                'description' => 'Get a single news article by numeric ID or SEO slug with all fields including full body content, summary, and SEO metadata.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'News record ID (provide either id or seo).',
                        ],
                        'seo' => [
                            'type' => 'string',
                            'description' => 'News SEO slug (alternative to id).',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Optional language code when looking up by seo slug.',
                        ],
                    ],
                ],
                'title' => 'Get News Article',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_news' => [
                'module' => 'mod_news',
                'role' => 'add',
                'domain' => 'news',
                'handler' => 'createNews',
                'description' => 'Create a news article. The summary must be between 90 and 160 characters (90-120 deck standard; 120-160 SERP snippet standard). The category must exist and match the news language. The seo slug must be unique per language.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'News title/headline (1-255 characters).',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Language code of an active site language (e.g. ku, en, tr).',
                        ],
                        'category_id' => [
                            'type' => 'integer',
                            'description' => 'ID of an existing news category with the same language.',
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'Full HTML body of the news article.',
                        ],
                        'summary' => [
                            'type' => 'string',
                            'minLength' => 90,
                            'maxLength' => 160,
                            'description' => 'Deck and SERP summary (90-160 characters).',
                        ],
                        'seo' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Unique SEO slug. Automatically generated if omitted.',
                        ],
                        'title' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Meta title.',
                        ],
                        'keywords' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Meta keywords.',
                        ],
                        'description' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Meta description (defaults to summary).',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                            'default' => 'y',
                            'description' => 'Publish status: y (published) or n (draft).',
                        ],
                        'deck' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                            'default' => 'n',
                            'description' => 'Deck feature flag.',
                        ],
                        'slider' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                            'default' => 'n',
                            'description' => 'Slider feature flag.',
                        ],
                        'created_at' => [
                            'type' => 'string',
                            'description' => 'Publication datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.',
                        ],
                        'gallery_id' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Attached gallery ID.',
                        ],
                        'file_id' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Attached file/attachment ID.',
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Access control level ID.',
                        ],
                        'video' => [
                            'type' => 'string',
                            'description' => 'Comma-separated video keys.',
                        ],
                        'user_id' => [
                            'type' => 'integer',
                            'description' => 'Optional author user ID. Defaults to acting user.',
                        ],
                        'username' => [
                            'type' => 'string',
                            'description' => 'Optional author username (e.g. deryawelat). Defaults to acting user.',
                        ],
                        'image' => [
                            'type' => 'object',
                            'nullable' => true,
                            'description' => 'Image upload object or null to clear.',
                            'properties' => [
                                'data_base64' => [
                                    'type' => 'string',
                                    'description' => 'Base64 encoded image binary data.',
                                ],
                                'mime' => [
                                    'type' => 'string',
                                    'description' => 'Image MIME type (e.g. image/jpeg, image/png, image/webp).',
                                ],
                            ],
                            'required' => ['data_base64', 'mime'],
                        ],
                    ],
                    'required' => ['name', 'lang', 'category_id', 'content', 'summary'],
                ],
                'title' => 'Create News Article',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_news' => [
                'module' => 'mod_news',
                'role' => 'edit',
                'domain' => 'news',
                'handler' => 'updateNews',
                'description' => 'Partially update an existing news article by ID.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'News record ID.',
                        ],
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                        ],
                        'lang' => [
                            'type' => 'string',
                        ],
                        'category_id' => [
                            'type' => 'integer',
                        ],
                        'content' => [
                            'type' => 'string',
                        ],
                        'summary' => [
                            'type' => 'string',
                            'minLength' => 90,
                            'maxLength' => 160,
                        ],
                        'seo' => [
                            'type' => 'string',
                            'maxLength' => 255,
                        ],
                        'title' => [
                            'type' => 'string',
                            'maxLength' => 255,
                        ],
                        'keywords' => [
                            'type' => 'string',
                            'maxLength' => 255,
                        ],
                        'description' => [
                            'type' => 'string',
                            'maxLength' => 255,
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                        ],
                        'deck' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                        ],
                        'slider' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                        ],
                        'created_at' => [
                            'type' => 'string',
                        ],
                        'gallery_id' => [
                            'type' => 'integer',
                        ],
                        'file_id' => [
                            'type' => 'integer',
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                        ],
                        'video' => [
                            'type' => 'string',
                        ],
                        'user_id' => [
                            'type' => 'integer',
                            'description' => 'Author user ID.',
                        ],
                        'username' => [
                            'type' => 'string',
                            'description' => 'Author username (e.g. deryawelat).',
                        ],
                        'image' => [
                            'type' => 'object',
                            'nullable' => true,
                            'properties' => [
                                'data_base64' => ['type' => 'string'],
                                'mime' => ['type' => 'string'],
                            ],
                            'required' => ['data_base64', 'mime'],
                        ],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Update News Article',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_news' => [
                'module' => 'mod_news',
                'role' => 'delete',
                'domain' => 'news',
                'handler' => 'deleteNews',
                'description' => 'Delete a news article by ID, removing associated images and bridge relations.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'News record ID.',
                        ],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Delete News Article',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => false,
                ],
            ],
            'list_news_categories' => [
                'module' => 'mod_news_category',
                'role' => 'list',
                'domain' => 'news',
                'handler' => 'listNewsCategories',
                'description' => 'List news categories with optional filters and active language metadata.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Filter by language code.',
                        ],
                        'root' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                            'description' => 'Filter root categories.',
                        ],
                        'seo' => [
                            'type' => 'string',
                            'description' => 'Filter by exact SEO slug.',
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'Search category name.',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 50,
                            'default' => 20,
                        ],
                        'offset' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'default' => 0,
                        ],
                    ],
                ],
                'title' => 'List News Categories',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_news_category' => [
                'module' => 'mod_news_category',
                'role' => 'list',
                'domain' => 'news',
                'handler' => 'getNewsCategory',
                'description' => 'Get a single news category record with all fields by numeric ID or SEO slug.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Category ID.',
                        ],
                        'seo' => [
                            'type' => 'string',
                            'description' => 'Category SEO slug (alternative to id).',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Optional language code when looking up by seo slug.',
                        ],
                    ],
                ],
                'title' => 'Get News Category',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_news_category' => [
                'module' => 'mod_news_category',
                'role' => 'add',
                'domain' => 'news',
                'handler' => 'createNewsCategory',
                'description' => 'Create a news category. Name (2-255 chars) and lang are required.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'minLength' => 2,
                            'maxLength' => 255,
                        ],
                        'lang' => [
                            'type' => 'string',
                        ],
                        'parent_id' => [
                            'type' => 'integer',
                            'default' => 0,
                        ],
                        'root' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                            'default' => 'n',
                        ],
                        'seo' => [
                            'type' => 'string',
                            'maxLength' => 255,
                        ],
                        'title' => [
                            'type' => 'string',
                            'maxLength' => 255,
                        ],
                        'keywords' => [
                            'type' => 'string',
                            'maxLength' => 255,
                        ],
                        'description' => [
                            'type' => 'string',
                            'maxLength' => 255,
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'default' => 0,
                        ],
                        'image' => [
                            'type' => 'object',
                            'nullable' => true,
                            'properties' => [
                                'data_base64' => ['type' => 'string'],
                                'mime' => ['type' => 'string'],
                            ],
                            'required' => ['data_base64', 'mime'],
                        ],
                    ],
                    'required' => ['name', 'lang'],
                ],
                'title' => 'Create News Category',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_news_category' => [
                'module' => 'mod_news_category',
                'role' => 'edit',
                'domain' => 'news',
                'handler' => 'updateNewsCategory',
                'description' => 'Partially update an existing news category by ID.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                        ],
                        'name' => [
                            'type' => 'string',
                            'minLength' => 2,
                            'maxLength' => 255,
                        ],
                        'lang' => [
                            'type' => 'string',
                        ],
                        'parent_id' => [
                            'type' => 'integer',
                        ],
                        'root' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                        ],
                        'seo' => [
                            'type' => 'string',
                            'maxLength' => 255,
                        ],
                        'title' => [
                            'type' => 'string',
                            'maxLength' => 255,
                        ],
                        'keywords' => [
                            'type' => 'string',
                            'maxLength' => 255,
                        ],
                        'description' => [
                            'type' => 'string',
                            'maxLength' => 255,
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                        ],
                        'order' => [
                            'type' => 'integer',
                        ],
                        'image' => [
                            'type' => 'object',
                            'nullable' => true,
                            'properties' => [
                                'data_base64' => ['type' => 'string'],
                                'mime' => ['type' => 'string'],
                            ],
                            'required' => ['data_base64', 'mime'],
                        ],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Update News Category',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_news_category' => [
                'module' => 'mod_news_category',
                'role' => 'delete',
                'domain' => 'news',
                'handler' => 'deleteNewsCategory',
                'description' => 'Delete a news category and all of its child categories recursively.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Category ID.',
                        ],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Delete News Category',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => false,
                ],
            ],
        ];
    }

    // ------------------------------------------------------------------
    // mod_news tools
    // ------------------------------------------------------------------

    /**
     * Lists news articles with optional filters
     */
    public function listNews(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_news');
        $model->select('id, lang, name, category_id, status, deck, slider, seo, created_at, updated_at');
        $this->applyNewsFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();
        $rows = is_array($items) ? $items : [];

        $countModel = $this->freshModel('mod_news');
        $countModel->select('id');
        $this->applyNewsFilters($countModel, $args);
        $countModel->getResult(true, true);
        $total = $countModel->getSize();

        return [
            'items' => $rows,
            'total' => $total,
            'count' => count($rows),
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Returns a single news record with all fields by id or seo
     */
    public function getNews(array $args): array
    {
        $model = $this->loadRecordByIdOrSeo('mod_news', $args, 'News');

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException('News record not found.');
        }

        $row['item'] = $row;
        return $row;
    }

    /**
     * Creates a news record (mirrors the admin add validator)
     */
    public function createNews(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (1-255 characters).");
        }

        $lang = $this->requireActiveLang($args);
        $categoryId = (int) ($args['category_id'] ?? 0);
        if ($categoryId <= 0) {
            throw new McpToolException("Field 'category_id' is required.");
        }
        $this->assertRecordMatchesLang('mod_news_category', $categoryId, $lang, 'Category');

        $content = $this->sanitizeBodyHtml((string) ($args['content'] ?? ''));
        if (trim(strip_tags($content)) === '') {
            throw new McpToolException("Field 'content' is required.");
        }

        $summary = trim((string) ($args['summary'] ?? ''));
        if ($summary === '') {
            throw new McpToolException("Field 'summary' is required.");
        }
        $this->assertSummaryLength($summary);

        $this->assertContentMatchesLang($lang, $name, $summary, $content);

        $seo = trim((string) ($args['seo'] ?? ''));
        if ($seo === '') {
            $seo = self::makeSeo($name, $lang);
        }
        if ($seo === '' || mb_strlen($seo) > 255) {
            throw new McpToolException("Field 'seo' must be 1-255 characters.");
        }
        if ($this->recordSeoExists('mod_news', $seo, $lang)) {
            throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
        }

        $model = $this->freshModel('mod_news');
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
                $authorUserId = (int) $userRow['id'];
            }
        }
        $model->user_id = $authorUserId;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_news', $args, self::NEWS_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_news', $imageUrl, self::NEWS_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the news record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'name' => $model->name,
            'seo' => $model->seo,
            'lang' => $model->lang,
            'status' => $model->status,
            'item' => $this->getNews(['id' => (int) $model->id])['item'],
        ];
    }

    /**
     * Partially updates a news record
     */
    public function updateNews(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadNewsById($id);
        $oldLang = (string) $model->lang;
        $oldSeo = (string) $model->seo;

        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;
        $categoryId = isset($args['category_id']) ? (int) $args['category_id'] : (int) $model->category_id;

        if ($categoryId !== (int) $model->category_id || $lang !== (string) $model->lang) {
            $this->assertRecordMatchesLang('mod_news_category', $categoryId, $lang, 'Category');
        }

        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if ($name === '' || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 1-255 characters.");
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
            $summary = trim((string) $args['summary']);
            $this->assertSummaryLength($summary);
            $model->summary = $summary;
        }

        if (isset($args['seo'])) {
            $seo = trim((string) $args['seo']);
            if ($seo === '') {
                $seo = self::makeSeo((string) $model->name, $lang);
            }
            if ($seo === '' || mb_strlen($seo) > 255) {
                throw new McpToolException("Field 'seo' must be 1-255 characters.");
            }
            if ($this->recordSeoExists('mod_news', $seo, $lang, $id)) {
                throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
            }
            $model->seo = $seo;
        }

        foreach (['title', 'keywords', 'description'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalString($args, $field, (string) $model->{$field}, 255);
            }
        }

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

        if (isset($args['gallery_id'])) {
            $model->gallery_id = (int) $args['gallery_id'];
        }

        if (isset($args['file_id'])) {
            $model->file_id = (int) $args['file_id'];
        }

        if (isset($args['acl_id'])) {
            $model->acl_id = (int) $args['acl_id'];
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
        $this->synchronizeSummaryAndDescription($model, $args);
        $this->assertContentMatchesLang(
            (string) $model->lang,
            (string) $model->name,
            (string) $model->summary,
            (string) $model->content
        );
        $model->updated_at = date('Y-m-d H:i:s');

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_news', $args, self::NEWS_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the news record.');
        }

        $this->registerSlugRedirectIfChanged($oldLang, $oldSeo, (string) $model->lang, (string) $model->seo, 'news');

        return [
            'updated' => true,
            'id' => $id,
            'name' => $model->name,
            'seo' => $model->seo,
            'lang' => $model->lang,
            'status' => $model->status,
            'item' => $this->getNews(['id' => $id])['item'],
        ];
    }

    /**
     * Deletes a news record with admin parity cleanup
     */
    public function deleteNews(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadNewsById($id);

        $this->assertModerateDelete('mod_news', (int) $model->user_id);

        $this->removeBridgeRelations('mod_news', $id);

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_news', (string) $model->image, self::NEWS_IMAGE_SIZES);
        }

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // mod_news_category tools
    // ------------------------------------------------------------------

    /**
     * Lists news categories with optional filters and active language metadata
     */
    public function listNewsCategories(array $args): array
    {
        $result = $this->listCategoriesGeneric('mod_news_category', $args, 20);
        $result['count'] = count($result['items']);
        return $result;
    }

    /**
     * Returns a single news category record with all fields by id or seo
     */
    public function getNewsCategory(array $args): array
    {
        $model = $this->loadRecordByIdOrSeo('mod_news_category', $args, 'News category');

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException('News category record not found.');
        }

        $row['item'] = $row;
        return $row;
    }

    /**
     * Creates a news category record
     */
    public function createNewsCategory(array $args): array
    {
        $fields = $this->buildCategoryFields($args, 'mod_news_category');

        $seo = $fields['seo'];
        if ($this->recordSeoExists('mod_news_category', $seo, $fields['lang'])) {
            throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
        }

        $model = $this->freshModel('mod_news_category');
        foreach ($fields as $key => $val) {
            $model->{$key} = $val;
        }

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_news_category', $args, self::CATEGORY_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_news_category', $imageUrl, self::CATEGORY_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the news category record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'name' => $model->name,
            'seo' => $model->seo,
            'lang' => $model->lang,
            'item' => $this->getNewsCategory(['id' => (int) $model->id])['item'],
        ];
    }

    /**
     * Partially updates a news category and syncs related menu entries
     */
    public function updateNewsCategory(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadCategoryById($id);
        $oldLang = (string) $model->lang;
        $oldSeo = (string) $model->seo;

        $previousMenuUrl = $model->lang . '/newses/' . $model->seo;
        $this->applyCategoryUpdate($model, $args);

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_news_category', $args, self::CATEGORY_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the news category record.');
        }

        $this->syncCategoryMenuOnUpdate($previousMenuUrl, $model);
        $this->registerSlugRedirectIfChanged($oldLang, $oldSeo, (string) $model->lang, (string) $model->seo, 'newses');

        return [
            'updated' => true,
            'id' => $id,
            'name' => $model->name,
            'seo' => $model->seo,
            'item' => $this->getNewsCategory(['id' => $id])['item'],
        ];
    }

    /**
     * Recursively deletes a news category tree with admin parity cleanup
     */
    public function deleteNewsCategory(array $args): array
    {
        $id = $this->requireId($args);
        $deletedIds = [];
        $this->deleteCategoryTree($id, $deletedIds);

        return ['deleted' => true, 'id' => $id, 'ids' => $deletedIds];
    }

    // ------------------------------------------------------------------
    // Shared category logic ({lang}/newses/{seo} menu URL pattern)
    // ------------------------------------------------------------------

    private function deleteCategoryTree(int $id, array &$deletedIds): void
    {
        $model = $this->loadCategoryById($id);

        $this->assertModerateDelete('mod_news_category', (int) $model->user_id);

        $childrenModel = $this->freshModel('mod_news_category');
        $childrenModel->where_parent_id($id)->getResult(true, true);
        foreach ($childrenModel->toArray() as $child) {
            $this->deleteCategoryTree((int) $child['id'], $deletedIds);
        }

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_news_category', (string) $model->image, self::CATEGORY_IMAGE_SIZES);
        }

        $this->removeBridgeRelations('mod_news_category', $id);

        $menuUrl = $model->lang . '/newses/' . $model->seo;
        $menuModel = $this->freshModel('mod_menu');
        if ($menuModel->exists_url($menuUrl)) {
            $menuModel->getResultOne(true, true)->delete();
        }

        $model->delete();
        $deletedIds[] = $id;
    }

    private function syncCategoryMenuOnUpdate(string $previousMenuUrl, $categoryModel): void
    {
        $menuModel = $this->freshModel('mod_menu');
        if ($menuModel->exists_url($previousMenuUrl)) {
            $menuModel->getResultOne(true, true)->sync();
            $menuModel->name = $categoryModel->name;
            $menuModel->lang = $categoryModel->lang;
            $menuModel->url = $categoryModel->lang . '/newses/' . $categoryModel->seo;
            $menuModel->acl_id = $categoryModel->acl_id;
            $menuModel->update();
        }
    }

    private function loadCategoryById(int $id): mod_news_category_model
    {
        $model = $this->freshModel('mod_news_category');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("News category record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function loadNewsById(int $id): mod_news_model
    {
        $model = $this->freshModel('mod_news');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("News record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyNewsFilters($model, array $args): void
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
        if (isset($args['seo']) && trim((string) $args['seo']) !== '') {
            $model->where_seo(trim((string) $args['seo']));
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $model->where('name LIKE \'%' . Obuilder::cc(trim((string) $args['search'])) . '%\'');
        }
    }

    protected function buildCategoryFields(array $args, string $modelName): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }

        $lang = $this->requireActiveLang($args);
        $description = $this->optionalString($args, 'description', '', 255);
        $this->assertContentMatchesLang($lang, $name, $description, '');

        $parentId = (int) ($args['parent_id'] ?? 0);
        if ($parentId > 0) {
            $this->assertRecordMatchesLang($modelName, $parentId, $lang, 'Parent category');
        }

        return [
            'name' => $name,
            'lang' => $lang,
            'parent_id' => $parentId,
            'root' => $this->optionalEnum($args, 'root', 'n', ['y', 'n']),
            'seo' => $this->optionalString($args, 'seo', self::makeSeo($name, $lang), 255),
            'title' => $this->optionalString($args, 'title', $name, 255),
            'keywords' => $this->optionalString($args, 'keywords', '', 255),
            'description' => $this->optionalString($args, 'description', '', 255),
            'acl_id' => (int) ($args['acl_id'] ?? 0),
            'user_id' => $this->actingUserId,
            'order' => $this->nextSiblingOrder($modelName, $parentId),
        ];
    }

    protected function applyCategoryUpdate($model, array $args): void
    {
        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 2-255 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['lang'])) {
            $model->lang = $this->requireActiveLang($args);
        }

        if (isset($args['parent_id'])) {
            $parentId = (int) $args['parent_id'];
            if ($parentId > 0) {
                if ($parentId === (int) $model->id) {
                    throw new McpToolException("A category cannot be its own parent.");
                }
                $parent = $this->loadRecordById('mod_news_category', $parentId, 'Parent category');
                if ((string) $parent->lang !== (string) $model->lang) {
                    throw new McpToolException("Parent category '{$parentId}' belongs to language '" . $parent->lang . "', cannot nest a '" . $model->lang . "' category under it.");
                }
            }
            $model->parent_id = $parentId;
        }

        if (isset($args['root'])) {
            $model->root = $this->optionalEnum($args, 'root', (string) $model->root, ['y', 'n']);
        }

        if (isset($args['seo'])) {
            $seo = $this->optionalString($args, 'seo', (string) $model->seo, 255);
            if ($seo === '') {
                $seo = self::makeSeo((string) $model->name, (string) $model->lang);
            }
            if ($this->recordSeoExists('mod_news_category', $seo, (string) $model->lang, (int) $model->id)) {
                throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
            }
            $model->seo = $seo;
        }

        foreach (['title', 'keywords', 'description'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalString($args, $field, (string) $model->{$field}, 255);
            }
        }

        $this->assertContentMatchesLang((string) $model->lang, (string) $model->name, (string) $model->description, '');

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
    }
}
