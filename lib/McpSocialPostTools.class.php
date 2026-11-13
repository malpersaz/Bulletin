<?php
/**
 * Amed Framework
 * MCP Social Post Tools - CRUD and host linking for mod_socialmedia_post
 * lib/McpSocialPostTools.class.php
 *
 * Manages native social media post records (X, Facebook, Instagram) and
 * inline bridge bindings ([socialpost:N]) against registered host modules.
 * Intentionally preserves 4-byte UTF-8 emojis inside social captions and
 * author names across utf8mb4 storage and responses.
 */

class McpSocialPostTools extends McpCrudToolsBase
{
    /**
     * Return array of tool definitions provided by this domain tool class.
     *
     * @return array<string, array>
     */
    public static function definitions(): array
    {
        return [
            'list_socialmedia_posts' => [
                'module' => 'mod_socialmedia_post',
                'role' => 'list',
                'domain' => 'socialmedia_post',
                'handler' => 'listSocialmediaPosts',
                'description' => 'List social media post records with optional filters (lang, provider, media_type, status, search, mod_name, vid). Returns paginated items with their [socialpost:ID] inline token.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Filter by language code (e.g. ku, en, tr).',
                        ],
                        'provider' => [
                            'type' => 'string',
                            'enum' => ['x', 'facebook', 'instagram'],
                            'description' => 'Filter by social provider.',
                        ],
                        'media_type' => [
                            'type' => 'string',
                            'enum' => ['post', 'video', 'reel'],
                            'description' => 'Filter by social media type.',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                            'description' => 'Filter by publish status: y (published) or n (draft).',
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'Search term matched against name, handle, content, or url.',
                        ],
                        'mod_name' => [
                            'type' => 'string',
                            'description' => 'Optional host module name (e.g. mod_news) to filter linked posts.',
                        ],
                        'vid' => [
                            'type' => 'integer',
                            'description' => 'Optional host record ID to filter linked posts (requires mod_name).',
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
                'title' => 'List Social Media Posts',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_socialmedia_post' => [
                'module' => 'mod_socialmedia_post',
                'role' => 'list',
                'domain' => 'socialmedia_post',
                'handler' => 'getSocialmediaPost',
                'description' => 'Get a single social media post record with all fields, embed HTML, inline token [socialpost:ID], and host module link bindings.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Social media post record ID.',
                        ],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Get Social Media Post',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_socialmedia_post' => [
                'module' => 'mod_socialmedia_post',
                'role' => 'add',
                'domain' => 'socialmedia_post',
                'handler' => 'createSocialmediaPost',
                'description' => 'Create or idempotently reuse a social media post record for a given URL and active language. Defaults status to n (draft) for editorial review, preserves 4-byte UTF-8 emojis, fetches metadata when available, and optionally binds to a host record.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Target active language code (e.g. ku, tr, en).',
                        ],
                        'url' => [
                            'type' => 'string',
                            'description' => 'Canonical or pasted post URL or embed HTML from X, Facebook, or Instagram.',
                        ],
                        'embed_html' => [
                            'type' => 'string',
                            'description' => 'Optional raw embed iframe/snippet used if url is omitted.',
                        ],
                        'media_type' => [
                            'type' => 'string',
                            'enum' => ['post', 'video', 'reel'],
                            'description' => 'Optional explicit media type override.',
                        ],
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Display title for admin/accessibility. Auto-resolved from provider metadata when omitted.',
                        ],
                        'handle' => [
                            'type' => 'string',
                            'maxLength' => 64,
                            'description' => 'Author handle without leading @.',
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'Post caption or snapshot text (supports 4-byte UTF-8 emojis).',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                            'default' => 'n',
                            'description' => 'Publish status: n (draft, default) or y (published).',
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Optional ACL role ID (0 = unrestricted).',
                        ],
                        'created_at' => [
                            'type' => 'string',
                            'description' => 'Creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.',
                        ],
                        'mod_name' => [
                            'type' => 'string',
                            'description' => 'Optional host module name (e.g. mod_news) to bind immediately.',
                        ],
                        'vid' => [
                            'type' => 'integer',
                            'description' => 'Optional host record ID to bind immediately (requires mod_name).',
                        ],
                        'sort_order' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Sort order for optional immediate host link.',
                        ],
                    ],
                    'required' => ['lang'],
                ],
                'title' => 'Create Social Media Post',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'update_socialmedia_post' => [
                'module' => 'mod_socialmedia_post',
                'role' => 'edit',
                'domain' => 'socialmedia_post',
                'handler' => 'updateSocialmediaPost',
                'description' => 'Partially update an existing social media post record and rebuild canonical embed HTML when source attributes change.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Social media post record ID.',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Active language code.',
                        ],
                        'url' => [
                            'type' => 'string',
                            'description' => 'New post URL from X, Facebook, or Instagram.',
                        ],
                        'media_type' => [
                            'type' => 'string',
                            'enum' => ['post', 'video', 'reel'],
                            'description' => 'Updated media type.',
                        ],
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Updated title.',
                        ],
                        'handle' => [
                            'type' => 'string',
                            'maxLength' => 64,
                            'description' => 'Updated author handle.',
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'Updated post text or snapshot (supports emojis).',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                            'description' => 'Publish status: y or n.',
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'description' => 'Updated ACL ID (0 = unrestricted).',
                        ],
                        'created_at' => [
                            'type' => 'string',
                            'description' => 'Updated creation datetime (YYYY-MM-DD HH:MM:SS).',
                        ],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Update Social Media Post',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_socialmedia_post' => [
                'module' => 'mod_socialmedia_post',
                'role' => 'delete',
                'domain' => 'socialmedia_post',
                'handler' => 'deleteSocialmediaPost',
                'description' => 'Delete a social media post record, its cached local thumbnail, and all mod_socialmedia_post_link bridge bindings.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Social media post record ID.',
                        ],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Delete Social Media Post',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
            'link_socialmedia_post' => [
                'module' => 'mod_socialmedia_post',
                'role' => 'edit',
                'domain' => 'socialmedia_post',
                'handler' => 'linkSocialmediaPost',
                'description' => 'Bind an existing social media post to a host record (requires host module registered in mod_socialmedia_post_mod and edit permission on host module).',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'socialmedia_post_id' => [
                            'type' => 'integer',
                            'description' => 'Social media post record ID.',
                        ],
                        'mod_name' => [
                            'type' => 'string',
                            'description' => 'Host module name (e.g. mod_news, mod_page, mod_publication, mod_event, mod_announcements).',
                        ],
                        'vid' => [
                            'type' => 'integer',
                            'description' => 'Host record ID.',
                        ],
                        'sort_order' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Sort order weight for the binding.',
                        ],
                    ],
                    'required' => ['socialmedia_post_id', 'mod_name', 'vid'],
                ],
                'title' => 'Link Social Media Post',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'unlink_socialmedia_post' => [
                'module' => 'mod_socialmedia_post',
                'role' => 'edit',
                'domain' => 'socialmedia_post',
                'handler' => 'unlinkSocialmediaPost',
                'description' => 'Remove the bridge binding between a social media post and a host record.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'socialmedia_post_id' => [
                            'type' => 'integer',
                            'description' => 'Social media post record ID.',
                        ],
                        'mod_name' => [
                            'type' => 'string',
                            'description' => 'Host module name (e.g. mod_news).',
                        ],
                        'vid' => [
                            'type' => 'integer',
                            'description' => 'Host record ID.',
                        ],
                    ],
                    'required' => ['socialmedia_post_id', 'mod_name', 'vid'],
                ],
                'title' => 'Unlink Social Media Post',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
        ];
    }

    /**
     * Lists social media post records with optional filters.
     */
    public function listSocialmediaPosts(array $args): array
    {
        $this->ensureServiceLoaded();
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $linkedIds = null;
        if (isset($args['mod_name']) && trim((string) $args['mod_name']) !== '') {
            $modName = $this->normalizeHostModuleName((string) $args['mod_name']);
            $vid = isset($args['vid']) ? (int) $args['vid'] : 0;
            $linkedIds = $this->queryLinkedPostIds($modName, $vid);
            if ($linkedIds === []) {
                return [
                    'items' => [],
                    'total' => 0,
                    'limit' => $limit,
                    'offset' => $offset,
                ];
            }
        }

        $model = $this->freshModel('mod_socialmedia_post');
        $model->select('id, lang, provider, media_type, post_id, name, handle, content, url, image, status, acl_id, user_id, created_at');
        $this->applyListFilters($model, $args, $linkedIds);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_socialmedia_post');
        $countModel->select('id');
        $this->applyListFilters($countModel, $args, $linkedIds);
        $countModel->getResult(true, true);
        $total = $countModel->getSize();

        $enriched = [];
        if (is_array($items)) {
            foreach ($items as $row) {
                if (is_array($row)) {
                    $postId = (int) ($row['id'] ?? 0);
                    $row['token'] = '[socialpost:' . $postId . ']';
                    $enriched[] = $row;
                }
            }
        }

        return [
            'items' => $enriched,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Returns a single social media post record with token and host link bindings.
     */
    public function getSocialmediaPost(array $args): array
    {
        $this->ensureServiceLoaded();
        $id = $this->requireId($args);
        $model = $this->loadRecordById('mod_socialmedia_post', $id, 'Social media post');

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Social media post record '{$id}' not found.");
        }

        $row['token'] = '[socialpost:' . $id . ']';
        $links = $this->queryPostLinks($id);

        return [
            'item' => $row,
            'token' => '[socialpost:' . $id . ']',
            'links' => $links,
        ];
    }

    /**
     * Creates or idempotently reuses a social media post record.
     */
    public function createSocialmediaPost(array $args): array
    {
        $this->ensureServiceLoaded();
        $lang = $this->requireActiveLang($args);

        $rawUrl = trim((string) ($args['url'] ?? ''));
        $rawEmbed = trim((string) ($args['embed_html'] ?? ''));
        if ($rawUrl === '' && $rawEmbed === '') {
            throw new McpToolException("Field 'url' or 'embed_html' is required.");
        }

        $detected = $rawUrl !== ''
            ? SocialPostProviderService::detectProvider($rawUrl)
            : null;
        if ($detected === null && $rawEmbed !== '') {
            $detected = SocialPostProviderService::detectProvider($rawEmbed);
        }
        if ($detected === null) {
            throw new McpToolException("Unsupported or invalid social post URL. Supported providers: X (Twitter), Facebook, Instagram.");
        }

        $provider = (string) $detected['provider'];
        $providerPostId = (string) $detected['post_id'];

        $existingId = $this->findExistingPostId($provider, $providerPostId, $lang);
        if ($existingId > 0) {
            $linked = $this->applyOptionalHostLink($existingId, $args);
            $result = $this->getSocialmediaPost(['id' => $existingId]);
            return [
                'created' => false,
                'reused' => true,
                'id' => $existingId,
                'token' => '[socialpost:' . $existingId . ']',
                'linked' => $linked,
                'item' => $result['item'],
                'links' => $result['links'],
            ];
        }

        $meta = [];
        try {
            $meta = SocialPostProviderService::fetchMetadata(
                $provider,
                $providerPostId,
                (string) $detected['canonical_url'],
                $lang,
                (string) $detected['media_type']
            );
        } catch (Throwable $e) {
            $meta = [];
        }

        $mediaTypeDefault = (string) ($meta['media_type'] ?? ($detected['media_type'] ?? 'post'));
        $mediaType = $this->optionalEnum($args, 'media_type', $mediaTypeDefault, ['post', 'video', 'reel']);
        $canonicalUrl = trim((string) ($meta['canonical_url'] ?? $detected['canonical_url']));
        if (mb_strlen($canonicalUrl) > 500) {
            $canonicalUrl = mb_substr($canonicalUrl, 0, 500);
        }

        $rawHandle = (string) ($args['handle'] ?? ($meta['author_handle'] ?? ($detected['handle'] ?? '')));
        $handle = ltrim(trim($rawHandle), '@');
        $handle = SocialPostProviderService::sanitizeUtf8Text($handle);
        if (mb_strlen($handle) > 64) {
            $handle = mb_substr($handle, 0, 64);
        }

        $rawContent = (string) ($args['content'] ?? ($meta['text'] ?? ($meta['description'] ?? '')));
        $content = SocialPostProviderService::sanitizeUtf8Text($rawContent);

        $rawName = (string) ($args['name'] ?? ($meta['title'] ?? ($meta['author_name'] ?? '')));
        $name = trim(SocialPostProviderService::sanitizeUtf8Text($rawName));
        if ($name === '') {
            $name = strtoupper($provider) . ' ' . ($handle !== '' ? '@' . $handle : '#' . $providerPostId);
        }
        if (mb_strlen($name) > 255) {
            $name = mb_substr($name, 0, 255);
        }

        $embedHtml = SocialPostProviderService::buildEmbedHtml($provider, $providerPostId, [
            'url' => $canonicalUrl,
            'media_type' => $mediaType,
            'lang' => $lang,
            'title' => $name,
        ]);
        if ($embedHtml === '') {
            throw new McpToolException('Failed to construct canonical embed HTML for this social post.');
        }

        $status = $this->optionalEnum($args, 'status', 'n', ['y', 'n']);
        $aclId = max(0, (int) ($args['acl_id'] ?? 0));
        if ($aclId > 0) {
            $this->loadRecordById('mod_acl', $aclId, 'ACL role');
        }
        $createdAt = $this->optionalDateTime($args, 'created_at', date('Y-m-d H:i:s'));

        $localImage = '';
        if (!empty($meta['thumbnail_url'])) {
            $localImage = SocialPostProviderService::importRemoteThumbnail((string) $meta['thumbnail_url']);
        }

        $model = $this->freshModel('mod_socialmedia_post');
        $model->lang = $lang;
        $model->provider = $provider;
        $model->media_type = $mediaType;
        $model->post_id = $providerPostId;
        $model->url = $canonicalUrl;
        $model->embed_html = $embedHtml;
        $model->name = $name;
        $model->handle = $handle;
        $model->content = $content;
        $model->image = $localImage !== '' ? $localImage : null;
        $model->status = $status;
        $model->created_at = $createdAt;
        $model->user_id = $this->actingUserId;
        $model->acl_id = $aclId;

        if (!$model->save(true)) {
            if ($localImage !== '') {
                SocialPostProviderService::deleteLocalThumbnail($localImage);
            }
            throw new McpToolException('Failed to create social media post record.');
        }

        $newId = (int) $model->id;
        $linked = $this->applyOptionalHostLink($newId, $args);
        $result = $this->getSocialmediaPost(['id' => $newId]);

        return [
            'created' => true,
            'reused' => false,
            'id' => $newId,
            'token' => '[socialpost:' . $newId . ']',
            'linked' => $linked,
            'item' => $result['item'],
            'links' => $result['links'],
        ];
    }

    /**
     * Partially updates a social media post record and rebuilds canonical embed HTML.
     */
    public function updateSocialmediaPost(array $args): array
    {
        $this->ensureServiceLoaded();
        $id = $this->requireId($args);
        $model = $this->loadRecordById('mod_socialmedia_post', $id, 'Social media post');

        $this->assertModerateEdit('mod_socialmedia_post', (int) $model->user_id);

        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;
        $provider = (string) $model->provider;
        $providerPostId = (string) $model->post_id;
        $mediaType = (string) $model->media_type;
        $canonicalUrl = (string) $model->url;
        $handle = (string) $model->handle;

        $shouldRebuildEmbed = ($lang !== (string) $model->lang);

        if (isset($args['url'])) {
            $rawUrl = trim((string) $args['url']);
            if ($rawUrl === '') {
                throw new McpToolException("Field 'url' cannot be empty.");
            }
            $detected = SocialPostProviderService::detectProvider($rawUrl);
            if ($detected === null) {
                throw new McpToolException("Unsupported or invalid social post URL.");
            }
            $provider = (string) $detected['provider'];
            $providerPostId = (string) $detected['post_id'];
            $mediaType = (string) $detected['media_type'];
            $canonicalUrl = (string) $detected['canonical_url'];
            if (!isset($args['handle']) && $detected['handle'] !== '') {
                $handle = (string) $detected['handle'];
            }
            $shouldRebuildEmbed = true;
        }

        if (isset($args['media_type'])) {
            $mediaType = $this->optionalEnum($args, 'media_type', $mediaType, ['post', 'video', 'reel']);
            $shouldRebuildEmbed = true;
        }

        if ($lang !== (string) $model->lang || $provider !== (string) $model->provider || $providerPostId !== (string) $model->post_id) {
            $collisionId = $this->findExistingPostId($provider, $providerPostId, $lang, $id);
            if ($collisionId > 0) {
                throw new McpToolException("Another social media post (#{$collisionId}) already exists for ({$provider}, {$providerPostId}, {$lang}).");
            }
        }

        if (isset($args['name'])) {
            $name = trim(SocialPostProviderService::sanitizeUtf8Text((string) $args['name']));
            if ($name === '' || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 1-255 characters.");
            }
            $model->name = $name;
            $shouldRebuildEmbed = true;
        }

        if (isset($args['handle'])) {
            $cleanHandle = ltrim(trim(SocialPostProviderService::sanitizeUtf8Text((string) $args['handle'])), '@');
            if (mb_strlen($cleanHandle) > 64) {
                $cleanHandle = mb_substr($cleanHandle, 0, 64);
            }
            $handle = $cleanHandle;
        }

        if (isset($args['content'])) {
            $model->content = SocialPostProviderService::sanitizeUtf8Text((string) $args['content']);
        }

        if (isset($args['status'])) {
            $model->status = $this->optionalEnum($args, 'status', (string) $model->status, ['y', 'n']);
        }

        if (isset($args['acl_id'])) {
            $aclId = max(0, (int) $args['acl_id']);
            if ($aclId > 0) {
                $this->loadRecordById('mod_acl', $aclId, 'ACL role');
            }
            $model->acl_id = $aclId;
        }

        if (isset($args['created_at'])) {
            $model->created_at = $this->optionalDateTime($args, 'created_at', (string) $model->created_at);
        }

        if (mb_strlen($canonicalUrl) > 500) {
            $canonicalUrl = mb_substr($canonicalUrl, 0, 500);
        }

        $model->lang = $lang;
        $model->provider = $provider;
        $model->media_type = $mediaType;
        $model->post_id = $providerPostId;
        $model->url = $canonicalUrl;
        $model->handle = $handle;

        if ($shouldRebuildEmbed) {
            $model->embed_html = SocialPostProviderService::buildEmbedHtml($provider, $providerPostId, [
                'url' => $canonicalUrl,
                'media_type' => $mediaType,
                'lang' => $lang,
                'title' => (string) $model->name,
            ]);
        }

        $model->id = $id;
        if (!$model->update('id')) {
            throw new McpToolException("Failed to update social media post '{$id}'.");
        }

        return ['updated' => true] + $this->getSocialmediaPost(['id' => $id]);
    }

    /**
     * Deletes a social media post, its thumbnail, and all bridge links.
     */
    public function deleteSocialmediaPost(array $args): array
    {
        $this->ensureServiceLoaded();
        $id = $this->requireId($args);
        $model = $this->loadRecordById('mod_socialmedia_post', $id, 'Social media post');

        $this->assertModerateDelete('mod_socialmedia_post', (int) $model->user_id);

        if (!empty($model->image)) {
            SocialPostProviderService::deleteLocalThumbnail((string) $model->image);
        }

        $db = Load::lib('Database');
        if ($db) {
            $db->query("DELETE FROM `mod_socialmedia_post_link` WHERE `socialmedia_post_id` = {$id}");
        }

        $model->delete();

        return [
            'deleted' => true,
            'id' => $id,
        ];
    }

    /**
     * Binds an existing social media post to a registered host record.
     */
    public function linkSocialmediaPost(array $args): array
    {
        $this->ensureServiceLoaded();
        $postId = (int) ($args['socialmedia_post_id'] ?? ($args['id'] ?? 0));
        if ($postId <= 0) {
            throw new McpToolException("Field 'socialmedia_post_id' is required and must be a positive integer.");
        }

        $this->loadRecordById('mod_socialmedia_post', $postId, 'Social media post');

        $modName = $this->requireRegisteredHostModule($args);
        $vid = (int) ($args['vid'] ?? 0);
        if ($vid <= 0) {
            throw new McpToolException("Field 'vid' is required and must be a positive integer.");
        }
        $this->assertTargetRecordExists($modName, $vid);
        $this->assertPermission($modName, 'edit');

        $sortOrder = max(0, (int) ($args['sort_order'] ?? 0));
        $db = Load::lib('Database');
        $safeMod = Obuilder::cc($modName);
        $now = date('Y-m-d H:i:s');

        $db->fetch_array("SELECT id FROM `mod_socialmedia_post_link` WHERE `socialmedia_post_id` = {$postId} AND `mod_name` = '{$safeMod}' AND `vid` = {$vid} LIMIT 1");
        $rows = $db->toArray();
        $linkId = 0;

        if (is_array($rows) && !empty($rows[0]['id'])) {
            $linkId = (int) $rows[0]['id'];
            $db->query("UPDATE `mod_socialmedia_post_link` SET `sort_order` = {$sortOrder} WHERE `id` = {$linkId}");
        } else {
            $db->query("INSERT INTO `mod_socialmedia_post_link` (`socialmedia_post_id`, `mod_name`, `vid`, `sort_order`, `created_at`) VALUES ({$postId}, '{$safeMod}', {$vid}, {$sortOrder}, '{$now}')");
            $linkId = (int) $db->insert_id();
        }

        return [
            'linked' => true,
            'link_id' => $linkId,
            'socialmedia_post_id' => $postId,
            'mod_name' => $modName,
            'vid' => $vid,
            'sort_order' => $sortOrder,
            'token' => '[socialpost:' . $postId . ']',
        ];
    }

    /**
     * Removes the binding between a social media post and a host record.
     */
    public function unlinkSocialmediaPost(array $args): array
    {
        $this->ensureServiceLoaded();
        $postId = (int) ($args['socialmedia_post_id'] ?? ($args['id'] ?? 0));
        if ($postId <= 0) {
            throw new McpToolException("Field 'socialmedia_post_id' is required and must be a positive integer.");
        }

        $modName = $this->normalizeHostModuleName((string) ($args['mod_name'] ?? ''));
        $vid = (int) ($args['vid'] ?? 0);
        if ($vid <= 0) {
            throw new McpToolException("Field 'vid' is required and must be a positive integer.");
        }

        $this->assertPermission($modName, 'edit');

        $db = Load::lib('Database');
        $safeMod = Obuilder::cc($modName);
        $db->query("DELETE FROM `mod_socialmedia_post_link` WHERE `socialmedia_post_id` = {$postId} AND `mod_name` = '{$safeMod}' AND `vid` = {$vid}");

        return [
            'unlinked' => true,
            'socialmedia_post_id' => $postId,
            'mod_name' => $modName,
            'vid' => $vid,
        ];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function ensureServiceLoaded(): void
    {
        if (!class_exists('SocialPostProviderService', false)) {
            $servicePath = (defined('lib_dir') ? lib_dir : dirname(__DIR__) . '/lib/') . 'SocialPostProviderService.class.php';
            if (is_file($servicePath)) {
                require_once $servicePath;
            }
        }
        SocialPostProviderService::ensureUtf8mb4Connection();
    }

    private function applyListFilters($model, array $args, ?array $linkedIds): void
    {
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
        }
        if (isset($args['provider']) && in_array((string) $args['provider'], ['x', 'facebook', 'instagram'], true)) {
            $model->where_provider((string) $args['provider']);
        }
        if (isset($args['media_type']) && in_array((string) $args['media_type'], ['post', 'video', 'reel'], true)) {
            $model->where_media_type((string) $args['media_type']);
        }
        if (isset($args['status']) && in_array((string) $args['status'], ['y', 'n'], true)) {
            $model->where_status((string) $args['status']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $safeSearch = Obuilder::cc(trim((string) $args['search']));
            $model->where("(name LIKE '%{$safeSearch}%' OR handle LIKE '%{$safeSearch}%' OR content LIKE '%{$safeSearch}%' OR url LIKE '%{$safeSearch}%' OR post_id LIKE '%{$safeSearch}%')");
        }
        if ($linkedIds !== null && $linkedIds !== []) {
            $cleanIds = implode(',', array_map('intval', $linkedIds));
            $model->where("id IN ({$cleanIds})");
        }
    }

    private function queryLinkedPostIds(string $modName, int $vid = 0): array
    {
        $db = Load::lib('Database');
        $safeMod = Obuilder::cc($modName);
        $sql = "SELECT socialmedia_post_id FROM `mod_socialmedia_post_link` WHERE `mod_name` = '{$safeMod}'";
        if ($vid > 0) {
            $sql .= " AND `vid` = {$vid}";
        }
        $sql .= " ORDER BY sort_order ASC, id ASC";
        $db->fetch_array($sql);
        $rows = $db->toArray();

        $ids = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $postId = (int) ($row['socialmedia_post_id'] ?? 0);
                if ($postId > 0 && !in_array($postId, $ids, true)) {
                    $ids[] = $postId;
                }
            }
        }
        return $ids;
    }

    private function queryPostLinks(int $postId): array
    {
        $db = Load::lib('Database');
        $db->fetch_array("SELECT id, mod_name, vid, sort_order, created_at FROM `mod_socialmedia_post_link` WHERE `socialmedia_post_id` = {$postId} ORDER BY sort_order ASC, id ASC");
        $rows = $db->toArray();
        return is_array($rows) ? $rows : [];
    }

    private function findExistingPostId(string $provider, string $providerPostId, string $lang, int $excludeId = 0): int
    {
        $db = Load::lib('Database');
        $safeProvider = Obuilder::cc($provider);
        $safePostId = Obuilder::cc($providerPostId);
        $safeLang = Obuilder::cc($lang);
        $sql = "SELECT id FROM `mod_socialmedia_post` WHERE `provider` = '{$safeProvider}' AND `post_id` = '{$safePostId}' AND `lang` = '{$safeLang}'";
        if ($excludeId > 0) {
            $sql .= " AND `id` != {$excludeId}";
        }
        $sql .= " LIMIT 1";
        $db->fetch_array($sql);
        $rows = $db->toArray();
        return (is_array($rows) && !empty($rows[0]['id'])) ? (int) $rows[0]['id'] : 0;
    }

    private function applyOptionalHostLink(int $postId, array $args): bool
    {
        if (empty($args['mod_name']) || empty($args['vid'])) {
            return false;
        }
        $this->linkSocialmediaPost([
            'socialmedia_post_id' => $postId,
            'mod_name' => $args['mod_name'],
            'vid' => (int) $args['vid'],
            'sort_order' => (int) ($args['sort_order'] ?? 0),
        ]);
        return true;
    }

    private function normalizeHostModuleName(string $rawModName): string
    {
        $modName = basename(trim($rawModName));
        if ($modName === '' || preg_match('/^[a-z][a-z0-9_]*$/', $modName) !== 1) {
            throw new McpToolException("Field 'mod_name' must be a valid module name (e.g. mod_news).");
        }
        return $modName;
    }

    private function requireRegisteredHostModule(array $args): string
    {
        $modName = $this->normalizeHostModuleName((string) ($args['mod_name'] ?? ''));
        $bridgeModel = $this->freshModel('mod_socialmedia_post_mod');
        if ($bridgeModel->exists_mod_name($modName) === false) {
            throw new McpToolException("Module '{$modName}' is not registered in mod_socialmedia_post_mod.");
        }
        return $modName;
    }

    private function assertTargetRecordExists(string $modName, int $vid): void
    {
        $modelFile = mod_dir . $modName . DS . 'model' . DS . $modName . '.model.php';
        if (!is_file($modelFile)) {
            throw new McpToolException("Module '{$modName}' has no model file; its records cannot host social media posts.");
        }

        $targetModel = $this->freshModel($modName);
        if ($targetModel->exists_id($vid) === false) {
            throw new McpToolException("Host record '{$vid}' of module '{$modName}' does not exist.");
        }
    }
}
