<?php
/**
 * Amed Framework
 * MCP Page Tools - CRUD implementations for mod_page
 * lib/McpPageTools.class.php
 */

class McpPageTools extends McpCrudToolsBase
{
    /**
     * Return array of tool definitions provided by this domain tool class.
     *
     * @return array<string, array>
     */
    public static function definitions(): array
    {
        return [
            'list_pages' => [
                'module' => 'mod_page',
                'role' => 'list',
                'domain' => 'page',
                'handler' => 'listPages',
                'description' => 'List static pages with optional filters, ordered like the admin page tree. Structural per-language root container rows carry root=\'y\' and cannot be deleted.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Filter by language code (e.g. ku, en, tr).',
                        ],
                        'parent_id' => [
                            'type' => 'integer',
                            'description' => 'Filter by parent page ID.',
                        ],
                        'root' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'Filter by root flag: y (structural language root container) or n (regular page).',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'Filter by publish status: y (published) or n (draft).',
                        ],
                        'deck' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'Filter by deck flag: y or n.',
                        ],
                        'seo' => [
                            'type' => 'string',
                            'description' => 'Filter by exact URL slug (seo).',
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'Search term matched against page names, seo slugs, and SEO titles.',
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
                'title' => 'List Pages',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_page' => [
                'module' => 'mod_page',
                'role' => 'list',
                'domain' => 'page',
                'handler' => 'getPage',
                'description' => 'Get a single page record with all fields by numeric id or URL slug (seo + optional lang), including the full body content, summary, and SEO metadata.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Page record ID (optional if seo is provided).',
                        ],
                        'seo' => [
                            'type' => 'string',
                            'description' => 'Page URL slug (optional if id is provided).',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Language code (e.g. tr, ku, en) when resolving by seo slug.',
                        ],
                    ],
                ],
                'title' => 'Get Page',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_page' => [
                'module' => 'mod_page',
                'role' => 'add',
                'domain' => 'page',
                'handler' => 'createPage',
                'description' => 'Create a static page. The summary must be between 90 and 160 characters (90-120 deck / 120-160 SERP snippet standard). The parent page (if any), gallery page, and file page references must exist and match the page language; a parent cannot be a structural language root container. The seo slug must be unique per language; it defaults to a slug generated from the name. No menu entry is created automatically.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Page name (1-255 characters).',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Language code of an active site language (e.g. ku, en, tr).',
                        ],
                        'parent_id' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Parent page ID (0 = top level). Must reference a regular page with the same language, never a language root container.',
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'Full HTML body of the page.',
                        ],
                        'summary' => [
                            'type' => 'string',
                            'minLength' => 90,
                            'maxLength' => 160,
                            'description' => 'Deck summary and meta description (90-160 characters; 120-155 recommended).',
                        ],
                        'seo' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'URL slug. Defaults to a slug generated from the name. Must be unique per language.',
                        ],
                        'title' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'SEO title. Defaults to the name.',
                        ],
                        'keywords' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'SEO keywords. Defaults to empty.',
                        ],
                        'description' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'SEO description. Defaults to the summary.',
                        ],
                        'root' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'default' => 'n',
                            'description' => 'Structural language root flag: keep n for regular pages.',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'default' => 'y',
                            'description' => 'Publish status: y (published) or n (draft).',
                        ],
                        'deck' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'default' => 'n',
                            'description' => 'Show on deck: y or n.',
                        ],
                        'gallery_id' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Related gallery ID (gallery_id column references a page ID in this module).',
                        ],
                        'file_id' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Related attachment file ID (file_id column references a page ID in this module).',
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Access control group ID (0 = public).',
                        ],
                        'category_id' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Optional category ID.',
                        ],
                        'image' => [
                            'type' => [
                                'object',
                                'null',
                            ],
                            'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.',
                            'properties' => [
                                'data_base64' => [
                                    'type' => 'string',
                                    'description' => 'Base64-encoded image file (max 10 MB decoded).',
                                ],
                                'mime' => [
                                    'type' => 'string',
                                    'enum' => [
                                        'image/jpeg',
                                        'image/png',
                                        'image/gif',
                                        'image/webp',
                                    ],
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
                        'lang',
                        'content',
                        'summary',
                    ],
                ],
                'title' => 'Create Page',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_page' => [
                'module' => 'mod_page',
                'role' => 'edit',
                'domain' => 'page',
                'handler' => 'updatePage',
                'description' => 'Partially update a page record. Only provided fields change; the summary (if provided) must be between 90 and 160 characters (auto-synced with description when omitted), and the seo slug (if provided) must remain unique per language. When the name, language, seo, or acl changes, an existing menu entry pointing at the old page URL ({lang}/page/{seo}) is updated and a 301 redirect is registered in mod_redirect.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Page record ID.',
                        ],
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New page name.',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'New language code of an active site language. References must match it.',
                        ],
                        'parent_id' => [
                            'type' => 'integer',
                            'description' => 'New parent page ID (0 = top level). Must reference a regular page with the same language, never a language root container.',
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'New full HTML body.',
                        ],
                        'summary' => [
                            'type' => 'string',
                            'minLength' => 90,
                            'maxLength' => 160,
                            'description' => 'New deck summary and meta description (90-160 characters; 120-155 recommended).',
                        ],
                        'seo' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New URL slug, unique per language.',
                        ],
                        'title' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New SEO title.',
                        ],
                        'keywords' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New SEO keywords.',
                        ],
                        'description' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New SEO description.',
                        ],
                        'root' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'New structural language root flag.',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'New publish status.',
                        ],
                        'deck' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'New deck flag.',
                        ],
                        'gallery_id' => [
                            'type' => 'integer',
                            'description' => 'New related gallery ID.',
                        ],
                        'file_id' => [
                            'type' => 'integer',
                            'description' => 'New related attachment file ID.',
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'description' => 'New access control group ID.',
                        ],
                        'category_id' => [
                            'type' => 'integer',
                            'description' => 'New category ID.',
                        ],
                        'image' => [
                            'type' => [
                                'object',
                                'null',
                            ],
                            'description' => 'Upload an image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.',
                            'properties' => [
                                'data_base64' => [
                                    'type' => 'string',
                                    'description' => 'Base64-encoded image file (max 10 MB decoded).',
                                ],
                                'mime' => [
                                    'type' => 'string',
                                    'enum' => [
                                        'image/jpeg',
                                        'image/png',
                                        'image/gif',
                                        'image/webp',
                                    ],
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
                'title' => 'Update Page',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_page' => [
                'module' => 'mod_page',
                'role' => 'delete',
                'domain' => 'page',
                'handler' => 'deletePage',
                'description' => 'Delete a page and all of its child pages recursively. Structural language root container rows (root=\'y\') cannot be deleted. Related gallery, attachment, and FAQ relations, uploaded image files, and the menu entry pointing at the page URL are cleaned up automatically.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Page record ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Delete Page',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
        ];
    }

    private const PAGE_IMAGE_SIZES = [
        '/' => ['width' => 1600, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 600, 'height' => null, 'option' => 'width'],
    ];

    // ------------------------------------------------------------------
    // mod_page tools
    // ------------------------------------------------------------------

    /**
     * Lists pages with optional filters, ordered like the admin page tree
     */
    public function listPages(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_page');
        $model->select('id, lang, name, parent_id, `order`, root, status, deck, seo, category_id, acl_id, menu, image');
        $this->applyPageFilters($model, $args);
        $model->order('`order`', 'ASC')->order('id', 'ASC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_page');
        $countModel->select('id');
        $this->applyPageFilters($countModel, $args);
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
     * Returns a single page record with all fields
     */
    public function getPage(array $args): array
    {
        $model = $this->loadRecordByIdOrSeo('mod_page', $args, 'Page');

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Page record not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a page record (mirrors the admin add validator)
     */
    public function createPage(array $args): array
    {
        $name = trim(strip_tags((string) ($args['name'] ?? '')));
        if ($name === '' || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (1-255 characters).");
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

        $seoInput = trim((string) ($args['seo'] ?? ''));
        $seo = $seoInput === '' ? self::makeSeo($name, $lang) : Helper::legacy_seo($seoInput, $lang);
        if ($seo === '' || mb_strlen($seo) > 255) {
            throw new McpToolException("Field 'seo' must be 1-255 characters.");
        }
        if ($this->recordSeoExists('mod_page', $seo, $lang)) {
            throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
        }

        $parentId = (int) ($args['parent_id'] ?? 0);
        if ($parentId > 0) {
            $this->assertRecordMatchesLang('mod_page', $parentId, $lang, 'Parent page');
            $parentRow = $this->getPage(['id' => $parentId]);
            if ((string) $parentRow['item']['root'] === 'y') {
                throw new McpToolException("A page cannot be nested under a language root container row.");
            }
        }

        $model = $this->freshModel('mod_page');
        $model->lang = $lang;
        $model->name = $name;
        $model->parent_id = $parentId;
        $model->order = $this->nextSiblingOrder('mod_page', $parentId);
        $model->root = $this->optionalEnum($args, 'root', 'n', ['y', 'n']);
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        $model->deck = $this->optionalEnum($args, 'deck', 'n', ['y', 'n']);
        $model->summary = $summary;
        $model->content = $content;
        $model->gallery_id = (int) ($args['gallery_id'] ?? 0);
        $model->file_id = (int) ($args['file_id'] ?? 0);
        $model->seo = $seo;
        $model->title = $this->optionalString($args, 'title', $name, 255);
        $model->keywords = $this->optionalString($args, 'keywords', '', 255);
        $model->description = $this->optionalString($args, 'description', $summary, 255);
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $model->category_id = (int) ($args['category_id'] ?? 0);
        $model->menu = 0;
        $model->user_id = $this->actingUserId;

        if ($model->gallery_id > 0) {
            $this->assertRecordMatchesLang('mod_page', $model->gallery_id, $lang, 'Gallery page');
        }
        if ($model->file_id > 0) {
            $this->assertRecordMatchesLang('mod_page', $model->file_id, $lang, 'File page');
        }

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_page', $args, self::PAGE_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_page', $imageUrl, self::PAGE_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the page record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getPage(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a page record and syncs its menu entry URL
     */
    public function updatePage(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadPageById($id);

        $this->assertModerateEdit('mod_page', (int) $model->user_id);

        $oldLang = (string) $model->lang;
        $oldSeo = (string) $model->seo;
        $previousMenuUrl = $oldLang . '/page/' . $oldSeo;

        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : $oldLang;

        if (isset($args['parent_id'])) {
            $parentId = (int) $args['parent_id'];
            if ($parentId === $id) {
                throw new McpToolException("A page cannot be its own parent.");
            }
            if ($parentId > 0) {
                $parentRow = $this->getPage(['id' => $parentId]);
                if ((string) $parentRow['item']['lang'] !== $lang) {
                    throw new McpToolException("Parent page '{$parentId}' belongs to language '" . $parentRow['item']['lang'] . "', content language is '{$lang}'. They must match.");
                }
                if ((string) $parentRow['item']['root'] === 'y') {
                    throw new McpToolException("A page cannot be nested under a language root container row.");
                }
            }
            $model->parent_id = $parentId;
        }

        if (isset($args['name'])) {
            $name = trim(strip_tags((string) $args['name']));
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
            $summary = trim(strip_tags((string) $args['summary']));
            if ($summary === '') {
                throw new McpToolException("Field 'summary' cannot be empty.");
            }
            $this->assertSummaryLength($summary);
            $model->summary = $summary;
        }

        if (isset($args['seo'])) {
            $seo = Helper::legacy_seo(trim((string) $args['seo']), $lang);
            if ($seo === '' || mb_strlen($seo) > 255) {
                throw new McpToolException("Field 'seo' must be 1-255 characters.");
            }
            if ($this->recordSeoExists('mod_page', $seo, $lang, $id)) {
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

        foreach (['status', 'root', 'deck'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalEnum($args, $field, (string) $model->{$field}, ['y', 'n']);
            }
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
        if (isset($args['category_id'])) {
            $model->category_id = (int) $args['category_id'];
        }

        $model->lang = $lang;

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_page', $args, self::PAGE_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the page record.');
        }

        $updatedPage = $this->loadPageById($id);
        $this->syncPageMenuOnUpdate($previousMenuUrl, $updatedPage);
        $this->registerSlugRedirectIfChanged($oldLang, $oldSeo, (string) $updatedPage->lang, (string) $updatedPage->seo, 'page');

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getPage(['id' => $id])['item']
        ];
    }

    /**
     * Deletes a page record with admin parity cleanup
     */
    public function deletePage(array $args): array
    {
        $id = $this->requireId($args);
        $this->deletePageRow($id);

        return ['deleted' => true, 'ids' => [$id]];
    }

    // ------------------------------------------------------------------
    // Page helpers
    // ------------------------------------------------------------------

    /**
     * Deletes a page and all of its child pages recursively (admin parity),
     * refusing to delete structural language root container rows
     */
    private function deletePageRow(int $id): void
    {
        $model = $this->loadPageById($id);

        if ((string) $model->root === 'y') {
            throw new McpToolException("Page '{$id}' is a structural language root container row and cannot be deleted.");
        }

        $this->assertModerateDelete('mod_page', (int) $model->user_id);

        $childrenModel = $this->freshModel('mod_page');
        $childrenModel->where_parent_id($id)->getResult(true, true);
        foreach ($childrenModel->toArray() as $child) {
            $this->deletePageRow((int) $child['id']);
        }

        $this->removeBridgeRelations('mod_page', $id);

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_page', (string) $model->image, self::PAGE_IMAGE_SIZES);
        }

        $menuUrl = (string) $model->lang . '/page/' . (string) $model->seo;
        $menuModel = $this->freshModel('mod_menu');
        if ($menuModel->exists_url($menuUrl)) {
            $menuModel->getResultOne(true, true)->delete();
        }

        $model->delete();
    }

    /**
     * When the page name, language, seo, or acl changes, an existing menu
     * entry pointing at the old page URL is updated automatically
     */
    private function syncPageMenuOnUpdate(string $previousMenuUrl, $pageModel): void
    {
        $menuModel = $this->freshModel('mod_menu');
        if ($menuModel->exists_url($previousMenuUrl)) {
            $menuModel->getResultOne(true, true)->sync();
            $menuModel->name = $pageModel->name;
            $menuModel->lang = $pageModel->lang;
            $menuModel->status = $pageModel->status;
            $menuModel->acl_id = $pageModel->acl_id;
            $menuModel->url = $pageModel->lang . '/page/' . $pageModel->seo;
            $menuModel->update();
        }
    }

    private function loadPageById(int $id): mod_page_model
    {
        $model = $this->freshModel('mod_page');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Page record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyPageFilters($model, array $args): void
    {
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
        }
        if (isset($args['seo']) && trim((string) $args['seo']) !== '') {
            $model->where_seo(trim((string) $args['seo']));
        }
        if (isset($args['parent_id']) && (int) $args['parent_id'] > 0) {
            $model->where_parent_id((int) $args['parent_id']);
        }
        if (isset($args['root']) && in_array($args['root'], ['y', 'n'], true)) {
            $model->where_root($args['root']);
        }
        if (isset($args['status']) && in_array($args['status'], ['y', 'n'], true)) {
            $model->where_status($args['status']);
        }
        if (isset($args['deck']) && in_array($args['deck'], ['y', 'n'], true)) {
            $model->where_deck($args['deck']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $term = Obuilder::cc(trim((string) $args['search']));
            $model->where("(name LIKE '%{$term}%' OR seo LIKE '%{$term}%' OR title LIKE '%{$term}%')");
        }
    }
}
