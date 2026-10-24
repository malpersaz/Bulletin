<?php
/**
 * Amed Framework
 * MCP Attachment Tools - CRUD implementations for mod_attachment (downloadable files)
 * lib/McpAttachmentTools.class.php
 *
 * Attachment records belong to a host record of another module through the
 * (mod_name, vid) pair and mirror the admin upload/edit/delete flows of
 * mod/mod_attachment/index.php. Files are stored under
 * static/mod_attachment/<host_module>/<random-key> by the file module.
 */

class McpAttachmentTools extends McpCrudToolsBase
{
    /**
     * Return array of tool definitions provided by this domain tool class.
     *
     * @return array<string, array>
     */
    public static function definitions(): array
    {
        return [
            'list_attachments' => [
                'module' => 'mod_attachment',
                'role' => 'list',
                'domain' => 'attachment',
                'handler' => 'listAttachments',
                'description' => 'List downloadable attachment file records with optional filters. Returns paginated items (id, filename, url, ext, original_name, type, created_at, mod_name, vid, user_id) plus the total count. Each attachment belongs to a host record of another module through mod_name and vid.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'mod_name' => [
                            'type' => 'string',
                            'description' => 'Filter by host module name (e.g. mod_news, mod_page).',
                        ],
                        'vid' => [
                            'type' => 'integer',
                            'description' => 'Filter by host record ID.',
                        ],
                        'ext' => [
                            'type' => 'string',
                            'description' => 'Filter by file extension (e.g. pdf, docx, zip).',
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'Search term matched against the original file name.',
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
                    'required' => [
                    ],
                ],
                'title' => 'List Attachments',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_attachment' => [
                'module' => 'mod_attachment',
                'role' => 'list',
                'domain' => 'attachment',
                'handler' => 'getAttachment',
                'description' => 'Get a single attachment record with all fields, including the stored URL, extension, original file name, MIME type, host module binding (mod_name, vid) and view count.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Attachment record ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Get Attachment',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_attachment' => [
                'module' => 'mod_attachment',
                'role' => 'add',
                'domain' => 'attachment',
                'handler' => 'createAttachment',
                'description' => 'Upload a downloadable file and attach it to a host record. The file is stored under static/mod_attachment/<host_module>/ with a random key; the extension must be one of the admin upload whitelist (txt, xls, jpg, jpeg, png, gif, bmp, rar, zip, pdf, doc, docx, ods, ppt, odt, epub) and the decoded payload must not exceed 10 MB. The host module must be registered in the attachment bridge (mod_attachment_mod) and the host record must exist.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'mod_name' => [
                            'type' => 'string',
                            'description' => 'Host module name the file is attached to (e.g. mod_news). Must be registered in the attachment bridge.',
                        ],
                        'vid' => [
                            'type' => 'integer',
                            'description' => 'Host record ID the file is attached to. Must exist in the host module.',
                        ],
                        'file' => [
                            'type' => 'object',
                            'description' => 'The file to upload.',
                            'properties' => [
                                'filename' => [
                                    'type' => 'string',
                                    'description' => 'Original file name including the extension (e.g. report.pdf). Determines the stored extension and the default display name.',
                                ],
                                'data_base64' => [
                                    'type' => 'string',
                                    'description' => 'Base64-encoded file content (max 10 MB decoded).',
                                ],
                                'mime' => [
                                    'type' => 'string',
                                    'maxLength' => 250,
                                    'description' => 'MIME type stored with the record. Defaults to application/octet-stream.',
                                ],
                            ],
                            'required' => [
                                'filename',
                                'data_base64',
                            ],
                        ],
                        'filename' => [
                            'type' => 'string',
                            'maxLength' => 250,
                            'description' => 'Optional display name override. Defaults to the sanitized original file name.',
                        ],
                        'created_at' => [
                            'type' => 'string',
                            'description' => 'Creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.',
                        ],
                    ],
                    'required' => [
                        'mod_name',
                        'vid',
                        'file',
                    ],
                ],
                'title' => 'Create Attachment',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_attachment' => [
                'module' => 'mod_attachment',
                'role' => 'edit',
                'domain' => 'attachment',
                'handler' => 'updateAttachment',
                'description' => 'Update an attachment record. The display filename can be renamed and the stored binary can optionally be replaced with a new upload (same whitelist and size rules as create; the previous file is removed after a successful save). The host binding (mod_name, vid) cannot be moved, mirroring the admin edit flow.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Attachment record ID.',
                        ],
                        'filename' => [
                            'type' => 'string',
                            'maxLength' => 250,
                            'description' => 'New display name (1-250 characters).',
                        ],
                        'file' => [
                            'type' => 'object',
                            'description' => 'Replacement file upload.',
                            'properties' => [
                                'filename' => [
                                    'type' => 'string',
                                    'description' => 'Original file name including the extension (e.g. report.pdf). Determines the stored extension and the default display name.',
                                ],
                                'data_base64' => [
                                    'type' => 'string',
                                    'description' => 'Base64-encoded file content (max 10 MB decoded).',
                                ],
                                'mime' => [
                                    'type' => 'string',
                                    'maxLength' => 250,
                                    'description' => 'MIME type stored with the record. Defaults to application/octet-stream.',
                                ],
                            ],
                            'required' => [
                                'filename',
                                'data_base64',
                            ],
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Update Attachment',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_attachment' => [
                'module' => 'mod_attachment',
                'role' => 'delete',
                'domain' => 'attachment',
                'handler' => 'deleteAttachment',
                'description' => 'Delete an attachment record and its stored file from static/mod_attachment/<host_module>/. Deleting a host record through its own module tools removes all attached files automatically; deleting another user\'s attachment requires the moderate_delete role.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Attachment record ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Delete Attachment',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
        ];
    }

    /**
     * Extension whitelist of the admin upload flow (case-insensitive set)
     */
    private const ALLOWED_FILE_EXTENSIONS = [
        'txt', 'xls', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'rar', 'zip', 'pdf',
        'doc', 'docx', 'ods', 'ppt', 'odt', 'epub',
    ];

    private const MAX_FILE_BYTES = 10485760; // 10 MB decoded

    // ------------------------------------------------------------------
    // mod_attachment tools
    // ------------------------------------------------------------------

    /**
     * Lists attachment records with optional filters
     */
    public function listAttachments(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_attachment');
        $model->select('id, filename, url, ext, original_name, type, created_at, mod_name, vid, user_id');
        $this->applyAttachmentFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_attachment');
        $countModel->select('id');
        $this->applyAttachmentFilters($countModel, $args);
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
     * Returns a single attachment record with all fields
     */
    public function getAttachment(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadAttachmentById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Attachment record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates an attachment record by storing an uploaded file
     * (mirrors the admin upload flow)
     */
    public function createAttachment(array $args): array
    {
        $modName = $this->requireHostModule($args);
        $vid = (int) ($args['vid'] ?? 0);
        if ($vid <= 0) {
            throw new McpToolException("Field 'vid' is required and must be a positive integer (host record ID).");
        }
        $this->assertTargetRecordExists($modName, $vid);

        $stored = $this->resolveFileArgument($modName, $args, null);
        if ($stored === null) {
            throw new McpToolException("Field 'file' is required: an object with 'filename' and 'data_base64'.");
        }

        $model = $this->freshModel('mod_attachment');
        $model->filename = $this->resolveDisplayFilename($args, $stored['original_name']);
        $model->url = $stored['url'];
        $model->ext = $stored['ext'];
        $model->original_name = $stored['original_name'];
        $model->type = $stored['type'];
        $model->created_at = $this->optionalDateTime($args, 'created_at', date('Y-m-d H:i:s'));
        $model->mod_name = $modName;
        $model->vid = $vid;
        $model->user_id = $this->actingUserId;

        if (!$model->save(true)) {
            $this->deleteStoredFile($modName, $stored['url']);
            throw new McpToolException('Failed to create the attachment record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getAttachment(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates an attachment record (admin parity: the display
     * filename is the only metadata the admin edit flow changes; an optional
     * 'file' argument replaces the stored binary the same way image
     * replacement works for other modules)
     */
    public function updateAttachment(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadAttachmentById($id);

        $this->assertModerateEdit('mod_attachment', (int) $model->user_id);

        if (isset($args['filename'])) {
            $filename = trim((string) $args['filename']);
            if ($filename === '' || mb_strlen($filename) > 250) {
                throw new McpToolException("Field 'filename' must be 1-250 characters.");
            }
            $model->filename = Req::Clear($filename);
        }

        $previousUrl = null;
        $stored = null;
        if (array_key_exists('file', $args)) {
            if ($args['file'] === null) {
                throw new McpToolException("Field 'file' cannot be null on an attachment record; delete the record to remove its file.");
            }
            $previousUrl = (string) $model->url;
            $stored = $this->resolveFileArgument((string) $model->mod_name, $args, null);
            $model->url = $stored['url'];
            $model->ext = $stored['ext'];
            $model->original_name = $stored['original_name'];
            $model->type = $stored['type'];
            if (!isset($args['filename'])) {
                $model->filename = $this->resolveDisplayFilename([], $stored['original_name']);
            }
        }

        if (!$model->update('id')) {
            if ($stored !== null) {
                $this->deleteStoredFile((string) $model->mod_name, $stored['url']);
            }
            throw new McpToolException('Failed to update the attachment record.');
        }

        if ($previousUrl !== null && $stored !== null && $previousUrl !== $stored['url']) {
            $this->deleteStoredFile((string) $model->mod_name, $previousUrl);
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getAttachment(['id' => $id])['item']
        ];
    }

    /**
     * Deletes an attachment record with full admin parity cleanup
     * (removes the stored file, then the row)
     */
    public function deleteAttachment(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadAttachmentById($id);

        $this->assertModerateDelete('mod_attachment', (int) $model->user_id);

        $this->deleteStoredFile((string) $model->mod_name, (string) $model->url);

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // Attachment helpers
    // ------------------------------------------------------------------

    private function loadAttachmentById(int $id): mod_attachment_model
    {
        $model = $this->freshModel('mod_attachment');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Attachment record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyAttachmentFilters($model, array $args): void
    {
        if (isset($args['mod_name']) && trim((string) $args['mod_name']) !== '') {
            $model->where_mod_name(basename(trim((string) $args['mod_name'])));
        }
        if (isset($args['vid']) && (int) $args['vid'] > 0) {
            $model->where_vid((int) $args['vid']);
        }
        if (isset($args['ext']) && trim((string) $args['ext']) !== '') {
            $ext = Obuilder::cc(strtolower(trim((string) $args['ext'])));
            $model->where('ext LIKE \'' . $ext . '\'');
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $search = Obuilder::cc(trim((string) $args['search']));
            $model->where('original_name LIKE \'%' . $search . '%\'');
        }
    }

    /**
     * Validates and normalizes the host module name against the
     * mod_attachment_mod bridge registry (admin _check_mod parity)
     */
    private function requireHostModule(array $args): string
    {
        $modName = basename(trim((string) ($args['mod_name'] ?? '')));
        if ($modName === '') {
            throw new McpToolException("Field 'mod_name' is required (host module name, e.g. mod_news).");
        }
        if (preg_match('/^[a-z][a-z0-9_]*$/', $modName) !== 1) {
            throw new McpToolException("Field 'mod_name' must be a valid module name.");
        }

        $bridgeModel = $this->freshModel('mod_attachment_mod');
        if ($bridgeModel->exists_mod_name($modName) === false) {
            throw new McpToolException("Module '{$modName}' is not registered in the attachment bridge (mod_attachment_mod). Register it in the admin file management settings first.");
        }

        return $modName;
    }

    /**
     * Verifies the host record exists (admin _check_mod parity)
     */
    private function assertTargetRecordExists(string $modName, int $vid): void
    {
        $modelFile = mod_dir . $modName . DS . 'model' . DS . $modName . '.model.php';
        if (!is_file($modelFile)) {
            throw new McpToolException("Module '{$modName}' has no model file; its records cannot host attachments.");
        }

        $targetModel = $this->freshModel($modName);
        if ($targetModel->exists_id($vid) === false) {
            throw new McpToolException("Host record '{$vid}' of module '{$modName}' does not exist.");
        }
    }

    /**
     * Handles the MCP 'file' argument: stores a new upload. Unlike the image
     * pipeline, attachment storage accepts arbitrary whitelisted document
     * extensions, so the file module's storage mechanics are mirrored here
     * (static/mod_attachment/<module>/<random-key>, extension kept in the
     * database, not in the filename). Returns null when the argument is absent.
     *
     * @return array{url: string, ext: string, original_name: string, type: string}|null
     */
    private function resolveFileArgument(string $modName, array $args, ?string $currentUrl): ?array
    {
        if (!array_key_exists('file', $args)) {
            return null;
        }

        $file = $args['file'];
        if (!is_array($file) || !isset($file['data_base64'], $file['filename'])) {
            throw new McpToolException("Field 'file' must be an object with 'filename' and 'data_base64'.");
        }

        $originalName = basename(trim((string) $file['filename']));
        if ($originalName === '' || $originalName === '.' || $originalName === '..') {
            throw new McpToolException("Field 'file.filename' is required.");
        }

        $ext = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, self::ALLOWED_FILE_EXTENSIONS, true)) {
            throw new McpToolException("Field 'file.filename' must have an allowed extension: " . implode(', ', self::ALLOWED_FILE_EXTENSIONS) . '.');
        }

        $data = base64_decode((string) $file['data_base64'], true);
        if ($data === false || $data === '') {
            throw new McpToolException("Field 'file.data_base64' is not valid base64 data.");
        }
        if (strlen($data) > self::MAX_FILE_BYTES) {
            throw new McpToolException("Field 'file.data_base64' exceeds the 10 MB decoded size limit.");
        }

        $type = trim((string) ($file['mime'] ?? ''));
        if ($type === '') {
            $type = 'application/octet-stream';
        }
        if (mb_strlen($type) > 250) {
            throw new McpToolException("Field 'file.mime' must be at most 250 characters.");
        }

        $url = $this->storeUploadedFile($modName, $data);

        if ($currentUrl !== null && $currentUrl !== '') {
            $this->deleteStoredFile($modName, $currentUrl);
        }

        return [
            'url' => $url,
            'ext' => $ext,
            'original_name' => $originalName,
            'type' => $type,
        ];
    }

    /**
     * Mirrors file module storage: random 20-character key inside
     * static/mod_attachment/<module>/, unique within the directory
     */
    private function storeUploadedFile(string $modName, string $data): string
    {
        $dir = root_dir . 'static' . DS . 'mod_attachment' . DS . $modName . DS;
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new McpToolException('Attachment storage directory could not be created: ' . $dir);
        }

        $url = Helper::randomkey(20);
        while (file_exists($dir . $url)) {
            $url = Helper::randomkey(20);
        }

        if (@file_put_contents($dir . $url, $data) === false) {
            throw new McpToolException('An error occurred while storing the uploaded file.');
        }

        return $url;
    }

    /**
     * Mirrors file module deletion: unlinks static/mod_attachment/<module>/<url>
     * A missing file is treated as already cleaned up
     */
    private function deleteStoredFile(string $modName, string $url): void
    {
        if ($url === '') {
            return;
        }
        $path = root_dir . 'static' . DS . 'mod_attachment' . DS . $modName . DS . $url;
        if (is_file($path) && !@unlink($path)) {
            throw new McpToolException('File could not be deleted: ' . $path);
        }
    }

    /**
     * Admin parity: the display filename defaults to the sanitized original
     * upload name (Req::Clear), with an optional explicit override
     */
    private function resolveDisplayFilename(array $args, string $originalName): string
    {
        if (isset($args['filename'])) {
            $filename = trim((string) $args['filename']);
            if ($filename === '' || mb_strlen($filename) > 250) {
                throw new McpToolException("Field 'filename' must be 1-250 characters.");
            }
            return Req::Clear($filename);
        }
        return Req::Clear($originalName);
    }
}
