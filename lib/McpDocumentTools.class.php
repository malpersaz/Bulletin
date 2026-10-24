<?php
/**
 * Amed Framework
 * MCP Document Tools - CRUD implementations for mod_document
 * lib/McpDocumentTools.class.php
 */

class McpDocumentTools extends McpCrudToolsBase
{
    /**
     * Return array of tool definitions provided by this domain tool class.
     *
     * @return array<string, array>
     */
    public static function definitions(): array
    {
        return [
            'list_documents' => [
                'module' => 'mod_document',
                'role' => 'list',
                'domain' => 'document',
                'handler' => 'listDocuments',
                'description' => 'List documents with optional filters. Returns paginated items (id, lang, name, seo, status, image) plus the total count.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Filter by active site language code (e.g. en, tr, ku).',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'Filter by publication status: y = published, n = draft.',
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'Case-insensitive substring filter applied to the document name.',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 50,
                            'default' => 10,
                            'description' => 'Number of documents to return (max 50).',
                        ],
                        'offset' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'default' => 0,
                            'description' => 'Number of documents to skip for pagination.',
                        ],
                    ],
                    'required' => [
                    ],
                ],
                'title' => 'List Documents',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_document' => [
                'module' => 'mod_document',
                'role' => 'list',
                'domain' => 'document',
                'handler' => 'getDocument',
                'description' => 'Get a single document record with all fields, including SEO metadata and the image reference.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'The document record id.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Get Document',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_document' => [
                'module' => 'mod_document',
                'role' => 'add',
                'domain' => 'document',
                'handler' => 'createDocument',
                'description' => 'Create a document record. Requires lang, name, keywords and description. The seo slug must be unique per language; it defaults to a slug generated from the name. The title defaults to the name. Status defaults to published. An optional image can be uploaded as {data_base64, mime} (jpeg, png, gif, webp; max 10 MB decoded), or set to null to leave the image empty.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Active site language code for the document (e.g. en, tr, ku).',
                        ],
                        'name' => [
                            'type' => 'string',
                            'minLength' => 2,
                            'maxLength' => 255,
                            'description' => 'Document name (2-255 characters).',
                        ],
                        'seo' => [
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 255,
                            'description' => 'URL slug, unique per language. Defaults to a slug generated from the name.',
                        ],
                        'title' => [
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 255,
                            'description' => 'HTML title tag. Defaults to the document name.',
                        ],
                        'keywords' => [
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 255,
                            'description' => 'Meta keywords (1-255 characters).',
                        ],
                        'description' => [
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 500,
                            'description' => 'Meta description (1-500 characters).',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'default' => 'y',
                            'description' => 'Publication status: y = published, n = draft. Defaults to published.',
                        ],
                        'image' => [
                            'oneOf' => [
                                [
                                    'type' => 'object',
                                    'properties' => [
                                        'data_base64' => [
                                            'type' => 'string',
                                            'description' => 'Base64-encoded image data (max 10 MB decoded).',
                                        ],
                                        'mime' => [
                                            'type' => 'string',
                                            'enum' => [
                                                'image/jpeg',
                                                'image/png',
                                                'image/gif',
                                                'image/webp',
                                            ],
                                            'description' => 'Image MIME type.',
                                        ],
                                    ],
                                    'required' => [
                                        'data_base64',
                                        'mime',
                                    ],
                                ],
                                [
                                    'type' => 'null',
                                ],
                            ],
                            'description' => 'Image upload object {data_base64, mime}, or null to explicitly leave/remove the image. Omit to keep untouched.',
                        ],
                    ],
                    'required' => [
                        'lang',
                        'name',
                        'keywords',
                        'description',
                    ],
                ],
                'title' => 'Create Document',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_document' => [
                'module' => 'mod_document',
                'role' => 'edit',
                'domain' => 'document',
                'handler' => 'updateDocument',
                'description' => 'Partially update a document record. Only provided fields change. The seo slug (if provided) must remain unique per language. Editing a document owned by another admin requires the moderate_edit role. The image argument accepts {data_base64, mime} to replace, null to remove, or can be omitted to keep the current image.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'The document record id.',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Active site language code to move the document to (e.g. en, tr, ku).',
                        ],
                        'name' => [
                            'type' => 'string',
                            'minLength' => 2,
                            'maxLength' => 255,
                            'description' => 'Document name (2-255 characters).',
                        ],
                        'seo' => [
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 255,
                            'description' => 'URL slug, unique per language.',
                        ],
                        'title' => [
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 255,
                            'description' => 'HTML title tag.',
                        ],
                        'keywords' => [
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 255,
                            'description' => 'Meta keywords (1-255 characters).',
                        ],
                        'description' => [
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 500,
                            'description' => 'Meta description (1-500 characters).',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'Publication status: y = published, n = draft.',
                        ],
                        'image' => [
                            'oneOf' => [
                                [
                                    'type' => 'object',
                                    'properties' => [
                                        'data_base64' => [
                                            'type' => 'string',
                                            'description' => 'Base64-encoded image data (max 10 MB decoded).',
                                        ],
                                        'mime' => [
                                            'type' => 'string',
                                            'enum' => [
                                                'image/jpeg',
                                                'image/png',
                                                'image/gif',
                                                'image/webp',
                                            ],
                                            'description' => 'Image MIME type.',
                                        ],
                                    ],
                                    'required' => [
                                        'data_base64',
                                        'mime',
                                    ],
                                ],
                                [
                                    'type' => 'null',
                                ],
                            ],
                            'description' => 'Image upload object {data_base64, mime} to replace the image, or null to remove it. Omit to keep the current image.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Update Document',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_document' => [
                'module' => 'mod_document',
                'role' => 'delete',
                'domain' => 'document',
                'handler' => 'deleteDocument',
                'description' => 'Delete a document record. Related gallery and attachment (file) relations and the uploaded image files are cleaned up automatically. Deleting a document owned by another admin requires the moderate_delete role.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'The document record id.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Delete Document',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
        ];
    }

    /**
     * Image size pipeline mirroring the mod_document admin controller
     */
    private const DOCUMENT_IMAGE_SIZES = [
        '/' => ['width' => 1200, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 300, 'height' => null, 'option' => 'width'],
    ];

    // ------------------------------------------------------------------
    // mod_document tools
    // ------------------------------------------------------------------

    /**
     * Lists documents with optional filters
     */
    public function listDocuments(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_document');
        $model->select('id, lang, name, seo, status, image');
        $this->applyDocumentFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_document');
        $countModel->select('id');
        $this->applyDocumentFilters($countModel, $args);
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
     * Returns a single document record with all fields
     */
    public function getDocument(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadDocumentById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Document record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a document record (mirrors the admin add validator and flow)
     */
    public function createDocument(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }

        $lang = $this->requireActiveLang($args);

        $seo = trim((string) ($args['seo'] ?? ''));
        if ($seo === '') {
            $seo = self::makeSeo($name, $lang);
        }
        if ($seo === '' || mb_strlen($seo) > 255) {
            throw new McpToolException("Field 'seo' must be 1-255 characters.");
        }
        if ($this->recordSeoExists('mod_document', $seo, $lang)) {
            throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
        }

        $title = trim((string) ($args['title'] ?? ''));
        if ($title === '') {
            $title = $name;
        }
        if (mb_strlen($title) > 255) {
            throw new McpToolException("Field 'title' must be at most 255 characters.");
        }

        $keywords = trim((string) ($args['keywords'] ?? ''));
        if ($keywords === '' || mb_strlen($keywords) > 255) {
            throw new McpToolException("Field 'keywords' is required (1-255 characters).");
        }

        $description = trim((string) ($args['description'] ?? ''));
        if ($description === '' || mb_strlen($description) > 500) {
            throw new McpToolException("Field 'description' is required (1-500 characters).");
        }

        $model = $this->freshModel('mod_document');
        $model->lang = $lang;
        $model->name = $name;
        $model->seo = $seo;
        $model->title = $title;
        $model->keywords = $keywords;
        $model->description = $description;
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        $model->user_id = $this->actingUserId;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_document', $args, self::DOCUMENT_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_document', $imageUrl, self::DOCUMENT_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the document record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getDocument(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a document record (mirrors the admin edit validator and flow)
     */
    public function updateDocument(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadDocumentById($id);

        $this->assertModerateEdit('mod_document', (int) $model->user_id);

        if (isset($args['lang'])) {
            $model->lang = $this->requireActiveLang($args);
        }

        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 2-255 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['seo'])) {
            $seo = trim((string) $args['seo']);
            if ($seo === '' || mb_strlen($seo) > 255) {
                throw new McpToolException("Field 'seo' must be 1-255 characters.");
            }
            if ($this->recordSeoExists('mod_document', $seo, (string) $model->lang, $id)) {
                throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
            }
            $model->seo = $seo;
        }

        if (isset($args['title'])) {
            $model->title = $this->optionalString($args, 'title', (string) $model->title, 255);
        }

        if (isset($args['keywords'])) {
            $keywords = trim((string) $args['keywords']);
            if ($keywords === '' || mb_strlen($keywords) > 255) {
                throw new McpToolException("Field 'keywords' must be 1-255 characters.");
            }
            $model->keywords = $keywords;
        }

        if (isset($args['description'])) {
            $description = trim((string) $args['description']);
            if ($description === '' || mb_strlen($description) > 500) {
                throw new McpToolException("Field 'description' must be 1-500 characters.");
            }
            $model->description = $description;
        }

        if (isset($args['status'])) {
            $model->status = $this->optionalEnum($args, 'status', (string) $model->status, ['y', 'n']);
        }

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_document', $args, self::DOCUMENT_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the document record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getDocument(['id' => $id])['item']
        ];
    }

    /**
     * Deletes a document record with full admin parity cleanup
     */
    public function deleteDocument(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadDocumentById($id);

        $this->assertModerateDelete('mod_document', (int) $model->user_id);

        $this->removeBridgeRelations('mod_document', $id);

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_document', (string) $model->image, self::DOCUMENT_IMAGE_SIZES);
        }

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // Document helpers
    // ------------------------------------------------------------------

    private function loadDocumentById(int $id): mod_document_model
    {
        $model = $this->freshModel('mod_document');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Document record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyDocumentFilters($model, array $args): void
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
