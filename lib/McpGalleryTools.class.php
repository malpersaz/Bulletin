<?php
/**
 * Amed Framework
 * MCP Gallery Tools - Image gallery management for mod_gallery and the mod_gallery_mod bridge
 * lib/McpGalleryTools.class.php
 *
 * In this framework a "gallery" is not a standalone record: it is the set of
 * mod_gallery image rows sharing the same (mod_name, vid) target pair. The
 * mod_gallery_mod table registers which modules support galleries. Tool
 * semantics mirror mod/mod_gallery/index.php (upload, rename, delete,
 * delete_all, list) and mod/mod_gallery_mod/index.php (bridge CRUD).
 */

class McpGalleryTools extends McpCrudToolsBase
{
    /**
     * Return array of tool definitions provided by this domain tool class.
     *
     * @return array<string, array>
     */
    public static function definitions(): array
    {
        return [
            'list_galleries' => [
                'module' => 'mod_gallery',
                'role' => 'list',
                'domain' => 'gallery',
                'handler' => 'listGalleries',
                'description' => 'List galleries as (mod_name, vid) groups with image counts and the last three preview image urls. Only modules registered in the gallery bridge are listed. Optional filters: mod_name, vid, and a search term matched against the module name and record id.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'mod_name' => [
                            'type' => 'string',
                            'description' => 'Filter galleries by target module name (for example mod_news).',
                        ],
                        'vid' => [
                            'type' => 'integer',
                            'description' => 'Filter galleries by target record id.',
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'Search term matched against the module name and record id.',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'default' => 10,
                            'maximum' => 50,
                            'description' => 'Maximum number of galleries to return (1-50).',
                        ],
                        'offset' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Number of galleries to skip.',
                        ],
                    ],
                    'required' => [
                    ],
                ],
                'title' => 'List Galleries',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_gallery' => [
                'module' => 'mod_gallery',
                'role' => 'list',
                'domain' => 'gallery',
                'handler' => 'getGallery',
                'description' => 'Get one gallery with all of its image rows (id, name, url, created_at, mod_name, vid, user_id), the resolved record label, and the image count. The target module must be registered for galleries and the target record must exist.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'mod_name' => [
                            'type' => 'string',
                            'description' => 'Target module name that owns the gallery (for example mod_news).',
                        ],
                        'vid' => [
                            'type' => 'integer',
                            'description' => 'Target record id that owns the gallery.',
                        ],
                    ],
                    'required' => [
                        'mod_name',
                        'vid',
                    ],
                ],
                'title' => 'Get Gallery',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'upload_gallery_image' => [
                'module' => 'mod_gallery',
                'role' => 'edit',
                'domain' => 'gallery',
                'handler' => 'uploadGalleryImage',
                'description' => 'Upload a new image into a gallery. Stores an original copy (max width 1920) and a thumb (max width 500) under static/mod_gallery/{mod_name}/ and creates the image row. The display name defaults to the target record label; the target module must be registered for galleries and the target record must exist.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'mod_name' => [
                            'type' => 'string',
                            'description' => 'Target module name that owns the gallery.',
                        ],
                        'vid' => [
                            'type' => 'integer',
                            'description' => 'Target record id that owns the gallery.',
                        ],
                        'image' => [
                            'type' => 'object',
                            'description' => 'The image file to upload.',
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
                                    'description' => 'Image MIME type.',
                                ],
                            ],
                            'required' => [
                                'data_base64',
                                'mime',
                            ],
                        ],
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 250,
                            'description' => 'Optional display name for the image. Defaults to the target record label.',
                        ],
                        'created_at' => [
                            'type' => 'string',
                            'description' => 'Optional creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.',
                        ],
                    ],
                    'required' => [
                        'mod_name',
                        'vid',
                        'image',
                    ],
                ],
                'title' => 'Upload Gallery Image',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'rename_gallery_image' => [
                'module' => 'mod_gallery',
                'role' => 'edit',
                'domain' => 'gallery',
                'handler' => 'renameGalleryImage',
                'description' => 'Rename a gallery image row. Requires the gallery role on the target module that owns the image.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Gallery image record id.',
                        ],
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 250,
                            'description' => 'New display name for the image.',
                        ],
                    ],
                    'required' => [
                        'id',
                        'name',
                    ],
                ],
                'title' => 'Rename Gallery Image',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_gallery_image' => [
                'module' => 'mod_gallery',
                'role' => 'delete',
                'domain' => 'gallery',
                'handler' => 'deleteGalleryImage',
                'description' => 'Delete one gallery image row together with its generated original and thumb files.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Gallery image record id.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Delete Gallery Image',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
            'clear_gallery' => [
                'module' => 'mod_gallery',
                'role' => 'delete',
                'domain' => 'gallery',
                'handler' => 'clearGallery',
                'description' => 'Remove every image of one gallery (all images attached to a target record) together with their generated files. Mirrors the admin bulk delete.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'mod_name' => [
                            'type' => 'string',
                            'description' => 'Target module name that owns the gallery.',
                        ],
                        'vid' => [
                            'type' => 'integer',
                            'description' => 'Target record id that owns the gallery.',
                        ],
                    ],
                    'required' => [
                        'mod_name',
                        'vid',
                    ],
                ],
                'title' => 'Clear Gallery',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
            'list_gallery_modules' => [
                'module' => 'mod_gallery_mod',
                'role' => 'list',
                'domain' => 'gallery',
                'handler' => 'listGalleryModules',
                'description' => 'List the modules registered as gallery-capable in the gallery bridge (id, name, mod_name, mod_key, mod_value), ordered by id descending.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => [
                            'type' => 'string',
                            'description' => 'Search term matched against name, mod_name, mod_key, and mod_value.',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'default' => 10,
                            'maximum' => 50,
                            'description' => 'Maximum number of registrations to return (1-50).',
                        ],
                        'offset' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Number of registrations to skip.',
                        ],
                    ],
                    'required' => [
                    ],
                ],
                'title' => 'List Gallery Modules',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_gallery_module' => [
                'module' => 'mod_gallery_mod',
                'role' => 'list',
                'domain' => 'gallery',
                'handler' => 'getGalleryModule',
                'description' => 'Get a single gallery module registration with all fields.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Gallery module registration id.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Get Gallery Module',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_gallery_module' => [
                'module' => 'mod_gallery_mod',
                'role' => 'add',
                'domain' => 'gallery',
                'handler' => 'createGalleryModule',
                'description' => 'Register a module as gallery-capable. Required: name, mod_name, mod_key, mod_value (2-255 characters each). mod_name must be a valid module identifier and must not already be registered. mod_key is the target table key column (usually id) and mod_value the record column used to resolve gallery display labels (usually name).',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Display name of the registration (2-255 characters).',
                        ],
                        'mod_name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Module name to register (2-255 characters, lowercase identifier).',
                        ],
                        'mod_key' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Key column of the target module (2-255 characters, usually id).',
                        ],
                        'mod_value' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Label column of the target module (2-255 characters, usually name).',
                        ],
                    ],
                    'required' => [
                        'name',
                        'mod_name',
                        'mod_key',
                        'mod_value',
                    ],
                ],
                'title' => 'Create Gallery Module',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_gallery_module' => [
                'module' => 'mod_gallery_mod',
                'role' => 'edit',
                'domain' => 'gallery',
                'handler' => 'updateGalleryModule',
                'description' => 'Partially update a gallery module registration. Only provided fields change; each must remain 2-255 characters. Editing a registration owned by another admin requires the moderate_edit role.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Gallery module registration id.',
                        ],
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New display name (2-255 characters).',
                        ],
                        'mod_name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New module name (2-255 characters, must not collide with another registration).',
                        ],
                        'mod_key' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New key column (2-255 characters).',
                        ],
                        'mod_value' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New label column (2-255 characters).',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Update Gallery Module',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_gallery_module' => [
                'module' => 'mod_gallery_mod',
                'role' => 'delete',
                'domain' => 'gallery',
                'handler' => 'deleteGalleryModule',
                'description' => 'Delete a gallery module registration and remove every image attached to that module, including all generated image files. Deleting a registration owned by another admin requires the moderate_delete role.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Gallery module registration id.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Delete Gallery Module',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
        ];
    }

    /**
     * Image size map used by the admin gallery uploader, keyed by the target
     * module: an original copy (max width 1920) and a thumb (max width 500).
     */
    private function imageSizes(string $mod): array
    {
        return [
            '/' . $mod . '/original/' => ['width' => 1920, 'height' => null, 'option' => 'width'],
            '/' . $mod . '/thumb' => ['width' => 500, 'height' => null, 'option' => 'width'],
        ];
    }

    // ------------------------------------------------------------------
    // Gallery (image collection) tools - mod_gallery
    // ------------------------------------------------------------------

    /**
     * Lists galleries as (mod_name, vid) groups with image counts and preview urls
     */
    public function listGalleries(array $args): array
    {
        $this->assertPermission('mod_gallery', 'list');

        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $pairs = $this->fetchGalleryPairs($args);
        $total = count($pairs);
        $page = array_slice($pairs, $offset, $limit);

        $registered = $this->registeredGalleryModules();
        $items = [];
        foreach ($page as $pair) {
            $mod = (string) $pair['mod_name'];
            $vid = (int) $pair['vid'];
            if (!$this->targetModelExists($mod)) {
                continue;
            }
            $bridge = $registered[$mod] ?? null;
            $configuredField = $bridge !== null ? (string) ($bridge['mod_value'] ?? '') : '';

            $items[] = [
                'mod_name' => $mod,
                'vid' => $vid,
                'name' => $this->resolveRecordLabel($mod, $vid, $configuredField, $mod . ' / ' . $vid),
                'image_count' => $this->countGalleryImages($mod, $vid),
                'preview_images' => $this->previewGalleryImages($mod, $vid),
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Returns every image row of one gallery (all images attached to a target record)
     */
    public function getGallery(array $args): array
    {
        $mod = $this->requireModuleName($args);
        $vid = $this->requireVid($args);

        $this->assertGalleryAccess($mod, $vid);

        $model = $this->freshModel('mod_gallery');
        $model->where_vid_and_mod_name($vid, $mod)->order('id', 'DESC')->getResult(true, true);
        $items = $model->toArray();

        $bridge = $this->loadBridgeModuleByName($mod);
        $configuredField = $bridge !== null ? (string) $bridge->mod_value : '';

        return [
            'gallery' => [
                'mod_name' => $mod,
                'vid' => $vid,
                'name' => $this->resolveRecordLabel($mod, $vid, $configuredField, $mod . ' / ' . $vid),
                'image_count' => is_array($items) ? count($items) : 0,
            ],
            'items' => is_array($items) ? $items : []
        ];
    }

    /**
     * Uploads a new image into a gallery (mirrors the admin upload flow)
     */
    public function uploadGalleryImage(array $args): array
    {
        $mod = $this->requireModuleName($args);
        $vid = $this->requireVid($args);

        if (!isset($args['image']) || !is_array($args['image'])) {
            throw new McpToolException("Field 'image' is required for uploads: an object with 'data_base64' and 'mime'.");
        }

        $this->assertGalleryAccess($mod, $vid);

        $bridge = $this->loadBridgeModuleByName($mod);
        $configuredField = $bridge !== null ? (string) $bridge->mod_value : '';
        $defaultName = $this->resolveRecordLabel($mod, $vid, $configuredField, 'image');
        $name = $this->optionalString($args, 'name', $defaultName, 250);

        $sizes = $this->imageSizes($mod);
        $newUrl = $this->storeUploadedImage('mod_gallery', $args['image'], $sizes);

        $model = $this->freshModel('mod_gallery');
        $model->name = $name;
        $model->url = $newUrl;
        $model->mod_name = $mod;
        $model->vid = $vid;
        $model->user_id = $this->actingUserId;
        $model->created_at = $this->optionalDateTime($args, 'created_at', date('Y-m-d H:i:s'));

        if (!$model->save(true)) {
            $this->deleteImageFiles('mod_gallery', $newUrl, $sizes);
            throw new McpToolException('Failed to create the gallery image record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'url' => 'static/mod_gallery/' . $mod . '/thumb/' . $newUrl,
            'original' => $newUrl,
            'item' => $this->loadImageRow((int) $model->id)
        ];
    }

    /**
     * Renames a gallery image (mirrors the admin rename flow)
     */
    public function renameGalleryImage(array $args): array
    {
        $id = $this->requireId($args);
        $row = $this->loadImageModel($id);

        $name = trim((string) ($args['name'] ?? ''));
        if ($name === '') {
            throw new McpToolException("Field 'name' is required.");
        }
        if (mb_strlen($name) > 250) {
            throw new McpToolException("Field 'name' must be at most 250 characters.");
        }

        $this->assertPermission((string) $row->mod_name, 'gallery');

        $row->name = $name;
        if (!$row->update('id')) {
            throw new McpToolException('Failed to rename the gallery image.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->loadImageRow($id)
        ];
    }

    /**
     * Deletes one gallery image together with its generated files
     */
    public function deleteGalleryImage(array $args): array
    {
        $id = $this->requireId($args);
        $row = $this->loadImageModel($id);

        $mod = (string) $row->mod_name;
        $vid = (int) $row->vid;

        $this->assertGalleryAccess($mod, $vid);

        $this->deleteImageFiles('mod_gallery', (string) $row->url, $this->imageSizes($mod));
        $row->delete();

        return ['deleted' => true, 'id' => $id];
    }

    /**
     * Removes every image of a gallery (mirrors the admin delete_all flow)
     */
    public function clearGallery(array $args): array
    {
        $mod = $this->requireModuleName($args);
        $vid = $this->requireVid($args);

        $this->assertGalleryAccess($mod, $vid);

        $model = $this->freshModel('mod_gallery');
        $model->where_vid_and_mod_name($vid, $mod)->getResult(true, true);
        $items = $model->toArray();
        $count = is_array($items) ? count($items) : 0;

        if ($count > 0) {
            $sizes = $this->imageSizes($mod);
            foreach ($items as $item) {
                $this->deleteImageFiles('mod_gallery', (string) $item['url'], $sizes);
            }
            $model->delete();
        }

        return ['deleted' => true, 'removed_images' => $count];
    }

    // ------------------------------------------------------------------
    // Bridge tools - mod_gallery_mod
    // ------------------------------------------------------------------

    /**
     * Lists the registered gallery-capable modules
     */
    public function listGalleryModules(array $args): array
    {
        $this->assertPermission('mod_gallery_mod', 'list');

        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_gallery_mod');
        $model->select('id, name, mod_name, mod_key, mod_value');
        $this->applyBridgeFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_gallery_mod');
        $countModel->select('id');
        $this->applyBridgeFilters($countModel, $args);
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
     * Returns a single gallery module registration with all fields
     */
    public function getGalleryModule(array $args): array
    {
        $this->assertPermission('mod_gallery_mod', 'list');

        $id = $this->requireId($args);
        $row = $this->loadBridgeRow($id);

        return ['item' => $row->toArray()];
    }

    /**
     * Registers a module as gallery-capable (mirrors the admin add validator)
     */
    public function createGalleryModule(array $args): array
    {
        $this->assertPermission('mod_gallery_mod', 'add');

        $name = $this->requireBridgeField($args, 'name');
        $modName = $this->requireBridgeField($args, 'mod_name');
        $modKey = $this->requireBridgeField($args, 'mod_key');
        $modValue = $this->requireBridgeField($args, 'mod_value');

        if ($this->loadBridgeModuleByName($modName) !== null) {
            throw new McpToolException("Module '{$modName}' is already registered for galleries.");
        }

        $model = $this->freshModel('mod_gallery_mod');
        $model->name = $name;
        $model->mod_name = $modName;
        $model->mod_key = $modKey;
        $model->mod_value = $modValue;
        $model->user_id = $this->actingUserId;

        if (!$model->save(true)) {
            throw new McpToolException('Failed to create the gallery module registration.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->loadBridgeRow((int) $model->id)->toArray()
        ];
    }

    /**
     * Partially updates a gallery module registration
     */
    public function updateGalleryModule(array $args): array
    {
        $this->assertPermission('mod_gallery_mod', 'edit');

        $id = $this->requireId($args);
        $row = $this->loadBridgeRow($id);

        $this->assertModerateEdit('mod_gallery_mod', (int) $row->user_id);

        foreach (['name', 'mod_name', 'mod_key', 'mod_value'] as $field) {
            if (isset($args[$field])) {
                $row->{$field} = $this->requireBridgeField($args, $field);
            }
        }

        if (isset($args['mod_name']) && (string) $row->mod_name !== (string) $args['mod_name']) {
            $existing = $this->loadBridgeModuleByName((string) $args['mod_name']);
            if ($existing !== null && (int) $existing->id !== $id) {
                throw new McpToolException("Module '{$args['mod_name']}' is already registered for galleries.");
            }
        }

        if (!$row->update('id')) {
            throw new McpToolException('Failed to update the gallery module registration.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->loadBridgeRow($id)->toArray()
        ];
    }

    /**
     * Deletes a gallery module registration and removes every image of that
     * module, mirroring the admin delete flow (mod_gallery _remove cleanup)
     */
    public function deleteGalleryModule(array $args): array
    {
        $this->assertPermission('mod_gallery_mod', 'delete');

        $id = $this->requireId($args);
        $row = $this->loadBridgeRow($id);

        $this->assertModerateDelete('mod_gallery_mod', (int) $row->user_id);

        $mod = (string) $row->mod_name;
        $removedImages = $this->countModuleImages($mod);

        Run::import('mod_gallery', '_remove', [$mod]);
        $row->delete();

        return ['deleted' => true, 'id' => $id, 'removed_images' => $removedImages];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Validates and returns the target module name argument
     */
    private function requireModuleName(array $args): string
    {
        $mod = trim((string) ($args['mod_name'] ?? ''));
        if ($mod === '') {
            throw new McpToolException("Field 'mod_name' is required.");
        }
        if (preg_match('/^[a-z][a-z0-9_]*$/', $mod) !== 1) {
            throw new McpToolException("Field 'mod_name' must be a valid module name (lowercase letters, digits, underscores).");
        }
        return $mod;
    }

    /**
     * Validates and returns the target record id argument
     */
    private function requireVid(array $args): int
    {
        $vid = (int) ($args['vid'] ?? 0);
        if ($vid <= 0) {
            throw new McpToolException("Field 'vid' is required and must be a positive integer.");
        }
        return $vid;
    }

    /**
     * Checks the target module has a loadable model file without triggering
     * the framework loader (which exits on missing files)
     */
    private function targetModelExists(string $mod): bool
    {
        return is_file(mod_dir . $mod . DS . model_dir . $mod . '.model.php');
    }

    /**
     * Full access gate mirroring the admin _check_mod flow: the module must be
     * registered for galleries (auto-registered on first use, admin parity),
     * the caller must hold one of the accepted roles, and the target record
     * must exist.
     */
    private function assertGalleryAccess(string $mod, int $vid): void
    {
        if (!$this->targetModelExists($mod)) {
            throw new McpToolException("Module '{$mod}' does not exist or has no gallery support.");
        }

        $this->ensureModuleRegistered($mod);

        $hasAccess = (
            isset($this->groupPermissions[$mod]['gallery']) ||
            isset($this->groupPermissions[$mod]['edit']) ||
            isset($this->groupPermissions['mod_gallery']['list']) ||
            isset($this->groupPermissions['mod_gallery']['edit'])
        );
        if (!$hasAccess) {
            throw new McpToolException("The API key's permission group does not grant gallery access to '{$mod}'.");
        }

        $targetModel = $this->freshModel($mod);
        if ($targetModel->exists_id($vid) === false) {
            throw new McpToolException("Target record '{$vid}' of module '{$mod}' not found.");
        }
    }

    /**
     * Auto-registers the module in mod_gallery_mod on first use (admin parity)
     * and returns the bridge row, or null when it cannot be ensured
     */
    private function ensureModuleRegistered(string $mod): ?ORM
    {
        $existing = $this->loadBridgeModuleByName($mod);
        if ($existing !== null) {
            return $existing;
        }

        $model = $this->freshModel('mod_gallery_mod');
        $model->name = ucfirst(str_replace('mod_', '', $mod)) . ' Galleries';
        $model->mod_name = $mod;
        $model->mod_key = 'id';
        $model->mod_value = 'name';
        $model->user_id = 1;
        if (!$model->save(true)) {
            return null;
        }

        return $this->loadBridgeModuleByName($mod);
    }

    /**
     * Loads a mod_gallery image model by id
     */
    private function loadImageModel(int $id): ORM
    {
        $model = $this->freshModel('mod_gallery');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Gallery image record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    /**
     * Loads the mod_gallery_mod row for a module name, or null when absent
     */
    private function loadBridgeModuleByName(string $mod): ?ORM
    {
        $model = $this->freshModel('mod_gallery_mod');
        if ($model->exists_mod_name($mod) === false) {
            return null;
        }
        $model->where_mod_name($mod)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            return null;
        }
        $model->sync();
        return $model;
    }

    /**
     * Loads a mod_gallery_mod row by id
     */
    private function loadBridgeRow(int $id): ORM
    {
        $model = $this->freshModel('mod_gallery_mod');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Gallery module registration '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    /**
     * Loads a mod_gallery image row by id
     */
    private function loadImageRow(int $id): array
    {
        return $this->loadImageModel($id)->toArray();
    }

    /**
     * Validates a bridge field against the admin validator rules
     * (required, 2-255 characters)
     */
    private function requireBridgeField(array $args, string $key): string
    {
        $value = trim((string) ($args[$key] ?? ''));
        if (mb_strlen($value) < 2 || mb_strlen($value) > 255) {
            throw new McpToolException("Field '{$key}' is required (2-255 characters).");
        }
        if ($key === 'mod_name' && preg_match('/^[a-z][a-z0-9_]*$/', $value) !== 1) {
            throw new McpToolException("Field 'mod_name' must be a valid module name (lowercase letters, digits, underscores).");
        }
        return $value;
    }

    /**
     * Applies list filters for the bridge module listing
     */
    private function applyBridgeFilters($model, array $args): void
    {
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $search = Obuilder::cc(trim((string) $args['search']));
            $model->addWhere("(name LIKE '%{$search}%' OR mod_name LIKE '%{$search}%' OR mod_key LIKE '%{$search}%' OR mod_value LIKE '%{$search}%')");
        }
    }

    /**
     * Fetches the distinct (mod_name, vid) gallery pairs, newest first,
     * restricted to registered gallery modules, with optional filters
     */
    private function fetchGalleryPairs(array $args): array
    {
        $registered = $this->registeredGalleryModules();

        $where = '';
        if (isset($args['mod_name']) && trim((string) $args['mod_name']) !== '') {
            $where .= " AND mod_name = '" . Obuilder::cc(trim((string) $args['mod_name'])) . "'";
        }
        if (isset($args['vid']) && (int) $args['vid'] > 0) {
            $where .= ' AND vid = ' . (int) $args['vid'];
        }

        $db = Load::lib('Database');
        $db->fetch_array('SELECT mod_name, vid FROM mod_gallery WHERE 1' . $where . ' GROUP BY mod_name, vid ORDER BY MAX(id) DESC');
        $rows = $db->toArray();
        if (!is_array($rows)) {
            return [];
        }

        $search = isset($args['search']) ? trim((string) $args['search']) : '';
        $pairs = [];
        foreach ($rows as $row) {
            $mod = (string) ($row['mod_name'] ?? '');
            $vid = (int) ($row['vid'] ?? 0);
            if ($mod === '' || $vid <= 0) {
                continue;
            }
            if (!isset($registered[$mod])) {
                continue;
            }
            if ($search !== '' && stripos($mod, $search) === false && stripos((string) $vid, $search) === false) {
                continue;
            }
            $pairs[] = ['mod_name' => $mod, 'vid' => $vid];
        }

        return $pairs;
    }

    /**
     * Returns the registered gallery modules keyed by mod_name
     */
    private function registeredGalleryModules(): array
    {
        $model = $this->freshModel('mod_gallery_mod');
        $model->where("mod_name != ''")->getResult(true, true);
        $rows = $model->toArray();
        $map = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (isset($row['mod_name']) && (string) $row['mod_name'] !== '') {
                    $map[(string) $row['mod_name']] = $row;
                }
            }
        }
        return $map;
    }

    /**
     * Counts the images of one gallery
     */
    private function countGalleryImages(string $mod, int $vid): int
    {
        $model = $this->freshModel('mod_gallery');
        $model->select('id');
        $model->where_vid_and_mod_name($vid, $mod)->getResult(true, true);
        return $model->getSize();
    }

    /**
     * Counts every image attached to a module
     */
    private function countModuleImages(string $mod): int
    {
        $model = $this->freshModel('mod_gallery');
        $model->select('id');
        $model->where_mod_name($mod)->getResult(true, true);
        return $model->getSize();
    }

    /**
     * Returns the last three image urls of a gallery for preview stacks
     */
    private function previewGalleryImages(string $mod, int $vid): array
    {
        $model = $this->freshModel('mod_gallery');
        $model->select('url');
        $model->where_vid_and_mod_name($vid, $mod)->order('id', 'DESC')->limit(3)->getResult(true, true);
        $rows = $model->toArray();
        $urls = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $urls[] = (string) ($row['url'] ?? '');
            }
        }
        return $urls;
    }

    /**
     * Resolves a human-readable label for the target record, mirroring the
     * admin _resolve_record_label candidate order
     */
    private function resolveRecordLabel(string $mod, int $vid, string $configuredField, string $fallback): string
    {
        $candidates = [];
        if ($configuredField !== '') {
            $candidates[] = $configuredField;
        }
        foreach (['name', 'title', 'headline', 'subject'] as $field) {
            if (!in_array($field, $candidates, true)) {
                $candidates[] = $field;
            }
        }

        $targetModel = $this->freshModel($mod);
        $targetModel->where_id($vid)->limit(1)->getResultOne(true, true);
        if ($targetModel->getSize() === 0) {
            return $fallback;
        }
        $targetModel->sync();
        $record = $targetModel->toArray();
        if (!is_array($record)) {
            return $fallback;
        }

        foreach ($candidates as $field) {
            if (!array_key_exists($field, $record)) {
                continue;
            }
            $value = trim((string) $record[$field]);
            if ($value === '') {
                continue;
            }
            if ($field === 'id' || substr($field, -3) === '_id') {
                continue;
            }
            return $value;
        }

        return $fallback;
    }
}
