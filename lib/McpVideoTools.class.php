<?php
/**
 * Amed Framework
 * MCP Video Tools - CRUD implementations for mod_video, mod_video_embed and mod_video_embed_category
 * lib/McpVideoTools.class.php
 */

class McpVideoTools extends McpCrudToolsBase
{
    /**
     * Return array of tool definitions provided by this domain tool class.
     *
     * @return array<string, array>
     */
    public static function definitions(): array
    {
        return [
            'list_videos' => [
                'module' => 'mod_video',
                'role' => 'list',
                'domain' => 'video',
                'handler' => 'listVideos',
                'description' => 'List local video records with optional filters. Returns paginated items (id, lang, name, duration, status, slider, image, created_at) plus the total count.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Filter by language code (e.g. ku, en, tr).',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'Filter by publish status: y (published) or n (draft).',
                        ],
                        'slider' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'Filter by hero slider flag.',
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'Search term matched against video names.',
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
                'title' => 'List Videos',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_video' => [
                'module' => 'mod_video',
                'role' => 'list',
                'domain' => 'video',
                'handler' => 'getVideo',
                'description' => 'Get a single local video record with all fields, including the converted file name, original file name, duration, description, and ACL group.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Video record ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Get Video',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_video' => [
                'module' => 'mod_video',
                'role' => 'add',
                'domain' => 'video',
                'handler' => 'createVideo',
                'description' => 'Create a local video record. The duration must be 5-8 characters (e.g. HH:MM:SS). Optionally uploads a thumbnail image (base64-encoded).',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Video name (2-255 characters).',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Language code of an active site language (e.g. ku, en, tr).',
                        ],
                        'duration' => [
                            'type' => 'string',
                            'minLength' => 5,
                            'maxLength' => 8,
                            'description' => 'Video duration (5-8 characters, e.g. HH:MM:SS).',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Optional video description.',
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
                        'slider' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'default' => 'n',
                            'description' => 'Show in hero slider flag.',
                        ],
                        'flv' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'File name of the converted video under static/mod_video/flv. Optional.',
                        ],
                        'original' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'File name of the original upload under static/mod_video/original. Optional.',
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Access control group ID.',
                        ],
                        'created_at' => [
                            'type' => 'string',
                            'description' => 'Creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.',
                        ],
                        'image' => [
                            'type' => [
                                'object',
                                'null',
                            ],
                            'description' => 'Upload a thumbnail image (base64-encoded) or null to skip. On create: absent = no image.',
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
                        'duration',
                    ],
                ],
                'title' => 'Create Video',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_video' => [
                'module' => 'mod_video',
                'role' => 'edit',
                'domain' => 'video',
                'handler' => 'updateVideo',
                'description' => 'Partially update a local video record. Only supplied fields change; the image argument uploads a new thumbnail (base64) or null removes the existing one. Absent = unchanged.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Video record ID.',
                        ],
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New video name (2-255 characters).',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'New language code of an active site language.',
                        ],
                        'duration' => [
                            'type' => 'string',
                            'minLength' => 5,
                            'maxLength' => 8,
                            'description' => 'New video duration (5-8 characters, e.g. HH:MM:SS).',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'New video description.',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'New publish status: y (published) or n (draft).',
                        ],
                        'slider' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'New hero slider flag.',
                        ],
                        'flv' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New converted video file name.',
                        ],
                        'original' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New original video file name.',
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'description' => 'New access control group ID.',
                        ],
                        'created_at' => [
                            'type' => 'string',
                            'description' => 'New creation datetime (YYYY-MM-DD HH:MM:SS).',
                        ],
                        'image' => [
                            'type' => [
                                'object',
                                'null',
                            ],
                            'description' => 'Upload a new thumbnail (base64-encoded) or null to remove the existing one. On update: absent = unchanged.',
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
                'title' => 'Update Video',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_video' => [
                'module' => 'mod_video',
                'role' => 'delete',
                'domain' => 'video',
                'handler' => 'deleteVideo',
                'description' => 'Delete a local video record. Related gallery/attachment relations, the converted and original video files, and the thumbnail image are cleaned up automatically.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Video record ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Delete Video',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
            'list_video_embeds' => [
                'module' => 'mod_video_embed',
                'role' => 'list',
                'domain' => 'video',
                'handler' => 'listVideoEmbeds',
                'description' => 'List embedded video records with optional filters. Returns paginated items (id, lang, category_id, name, duration, status, slider, image, created_at) plus the total count.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'category_id' => [
                            'type' => 'integer',
                            'description' => 'Filter by video embed category ID.',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Filter by language code (e.g. ku, en, tr).',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'Filter by publish status: y (published) or n (draft).',
                        ],
                        'slider' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'Filter by hero slider flag.',
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'Search term matched against video names.',
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
                'title' => 'List Video Embeds',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_video_embed' => [
                'module' => 'mod_video_embed',
                'role' => 'list',
                'domain' => 'video',
                'handler' => 'getVideoEmbed',
                'description' => 'Get a single embedded video record with all fields, including the embed code, body content, SEO metadata, and thumbnail.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Video embed record ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Get Video Embed',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_video_embed' => [
                'module' => 'mod_video_embed',
                'role' => 'add',
                'domain' => 'video',
                'handler' => 'createVideoEmbed',
                'description' => 'Create an embedded video record (e.g. YouTube). The category must exist and match the video language. The content field is sanitized through the same HTML purifier as the admin form. Emoji in name, content, title, keywords, or description are rejected, mirroring the admin validator.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Video name (2-255 characters).',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Language code of an active site language (e.g. ku, en, tr).',
                        ],
                        'category_id' => [
                            'type' => 'integer',
                            'description' => 'ID of an existing video embed category with the same language.',
                        ],
                        'duration' => [
                            'type' => 'string',
                            'minLength' => 5,
                            'maxLength' => 8,
                            'description' => 'Video duration (5-8 characters, e.g. HH:MM:SS).',
                        ],
                        'embed' => [
                            'type' => 'string',
                            'description' => 'Embed code or URL (e.g. a YouTube iframe embed). Required.',
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'Full HTML body of the video description. Required; sanitized like the admin form.',
                        ],
                        'title' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'SEO title (1-255 characters). Required.',
                        ],
                        'keywords' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'SEO keywords (1-255 characters). Required.',
                        ],
                        'description' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'SEO description (1-255 characters). Required.',
                        ],
                        'original' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Original video URL (e.g. the YouTube watch URL). Optional.',
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
                        'slider' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'default' => 'n',
                            'description' => 'Show in hero slider flag.',
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Access control group ID.',
                        ],
                        'created_at' => [
                            'type' => 'string',
                            'description' => 'Creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.',
                        ],
                        'image' => [
                            'type' => [
                                'object',
                                'null',
                            ],
                            'description' => 'Upload a thumbnail image (base64-encoded) or null to skip. On create: absent = no image.',
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
                        'category_id',
                        'duration',
                        'embed',
                        'content',
                        'title',
                        'keywords',
                        'description',
                    ],
                ],
                'title' => 'Create Video Embed',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_video_embed' => [
                'module' => 'mod_video_embed',
                'role' => 'edit',
                'domain' => 'video',
                'handler' => 'updateVideoEmbed',
                'description' => 'Partially update an embedded video record. The category must match the (possibly new) language. Emoji are rejected in name, content, title, keywords, and description. The image argument uploads a new thumbnail (base64) or null removes the existing one. Absent = unchanged.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Video embed record ID.',
                        ],
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New video name (2-255 characters).',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'New language code of an active site language.',
                        ],
                        'category_id' => [
                            'type' => 'integer',
                            'description' => 'New video embed category ID; must match the video language.',
                        ],
                        'duration' => [
                            'type' => 'string',
                            'minLength' => 5,
                            'maxLength' => 8,
                            'description' => 'New video duration (5-8 characters, e.g. HH:MM:SS).',
                        ],
                        'embed' => [
                            'type' => 'string',
                            'description' => 'New embed code or URL. Cannot be empty.',
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'New full HTML body; sanitized like the admin form. Cannot be empty.',
                        ],
                        'title' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New SEO title (1-255 characters).',
                        ],
                        'keywords' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New SEO keywords (1-255 characters).',
                        ],
                        'description' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New SEO description (1-255 characters).',
                        ],
                        'original' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New original video URL.',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'New publish status: y (published) or n (draft).',
                        ],
                        'slider' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'New hero slider flag.',
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'description' => 'New access control group ID.',
                        ],
                        'created_at' => [
                            'type' => 'string',
                            'description' => 'New creation datetime (YYYY-MM-DD HH:MM:SS).',
                        ],
                        'image' => [
                            'type' => [
                                'object',
                                'null',
                            ],
                            'description' => 'Upload a new thumbnail (base64-encoded) or null to remove the existing one. On update: absent = unchanged.',
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
                'title' => 'Update Video Embed',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_video_embed' => [
                'module' => 'mod_video_embed',
                'role' => 'delete',
                'domain' => 'video',
                'handler' => 'deleteVideoEmbed',
                'description' => 'Delete an embedded video record. Related gallery/attachment relations and the thumbnail image files are cleaned up automatically.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Video embed record ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Delete Video Embed',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
            'list_video_embed_categories' => [
                'module' => 'mod_video_embed_category',
                'role' => 'list',
                'domain' => 'video',
                'handler' => 'listVideoEmbedCategories',
                'description' => 'List video embed categories with optional filters. Returns paginated items ordered by sort order plus the total count.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Filter by language code (e.g. ku, en, tr).',
                        ],
                        'root' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'Filter by language-root flag.',
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'Search term matched against category names.',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 50,
                            'default' => 50,
                            'description' => 'Page size (1-50, default 50).',
                        ],
                        'offset' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'default' => 0,
                            'description' => 'Number of items to skip for pagination.',
                        ],
                    ],
                ],
                'title' => 'List Video Embed Categories',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_video_embed_category' => [
                'module' => 'mod_video_embed_category',
                'role' => 'list',
                'domain' => 'video',
                'handler' => 'getVideoEmbedCategory',
                'description' => 'Get a single video embed category with all fields, including parent, sort order, root flag, SEO metadata, and image.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Video embed category record ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Get Video Embed Category',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_video_embed_category' => [
                'module' => 'mod_video_embed_category',
                'role' => 'add',
                'domain' => 'video',
                'handler' => 'createVideoEmbedCategory',
                'description' => 'Create a video embed category. The seo slug must be unique per language and defaults to a slug generated from the name. A parent_id of 0 is only allowed while the language has no language-root category yet. Optionally creates a menu entry pointing at lang/videos/{seo}.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Category name (2-255 characters).',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Language code of an active site language (e.g. ku, en, tr).',
                        ],
                        'parent_id' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Parent category ID. 0 is only allowed while the language has no language-root category.',
                        ],
                        'root' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'default' => 'n',
                            'description' => 'Language-root flag.',
                        ],
                        'seo' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'URL slug. Defaults to a slug generated from the name. Must be unique per language.',
                        ],
                        'title' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'SEO title (1-255 characters). Defaults to the name.',
                        ],
                        'keywords' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'SEO keywords (1-255 characters). Required.',
                        ],
                        'description' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'SEO description (1-255 characters). Required.',
                        ],
                        'menu' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Menu parent ID; when non-zero a menu entry linking to this category is created.',
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Access control group ID applied to the created menu entry.',
                        ],
                        'image' => [
                            'type' => [
                                'object',
                                'null',
                            ],
                            'description' => 'Upload a category image (base64-encoded) or null to skip. On create: absent = no image.',
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
                        'keywords',
                        'description',
                    ],
                ],
                'title' => 'Create Video Embed Category',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_video_embed_category' => [
                'module' => 'mod_video_embed_category',
                'role' => 'edit',
                'domain' => 'video',
                'handler' => 'updateVideoEmbedCategory',
                'description' => 'Partially update a video embed category. Moving a category re-appends it after its new siblings and keeps the linked menu entry in sync (rename, re-parent, or delete it when menu=0). The image argument uploads a new image (base64) or null removes the existing one. Absent = unchanged.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Video embed category record ID.',
                        ],
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New category name (2-255 characters).',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'New language code of an active site language.',
                        ],
                        'parent_id' => [
                            'type' => 'integer',
                            'description' => 'New parent category ID. Cannot be the category itself or one of its descendants.',
                        ],
                        'root' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'New language-root flag.',
                        ],
                        'seo' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New URL slug; must be unique per language. Linked menu URLs are updated.',
                        ],
                        'title' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New SEO title (1-255 characters).',
                        ],
                        'keywords' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New SEO keywords (1-255 characters).',
                        ],
                        'description' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New SEO description (1-255 characters).',
                        ],
                        'order' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'description' => 'New sort order. Defaults to appending after the siblings when the parent changes.',
                        ],
                        'menu' => [
                            'type' => 'integer',
                            'description' => 'New menu parent ID: 0 deletes the linked menu entry, non-zero re-parents or creates it. Absent = keep the existing entry aligned.',
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'description' => 'New access control group ID for the linked menu entry.',
                        ],
                        'image' => [
                            'type' => [
                                'object',
                                'null',
                            ],
                            'description' => 'Upload a new image (base64-encoded) or null to remove the existing one. On update: absent = unchanged.',
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
                'title' => 'Update Video Embed Category',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_video_embed_category' => [
                'module' => 'mod_video_embed_category',
                'role' => 'delete',
                'domain' => 'video',
                'handler' => 'deleteVideoEmbedCategory',
                'description' => 'Delete a video embed category and all of its child categories recursively. Related images, gallery/attachment relations, and linked menu entries are cleaned up automatically.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Video embed category record ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Delete Video Embed Category',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
        ];
    }

    /**
     * mod_video thumbnail pipeline (admin ajax_image_upload / image_remove_image)
     */
    private const VIDEO_IMAGE_SIZES = [
        '/image' => ['width' => 250, 'height' => null, 'option' => 'width'],
    ];

    /**
     * mod_video_embed thumbnail pipeline (admin _videoImageOptions)
     */
    private const EMBED_IMAGE_SIZES = [
        '/' => ['width' => 1280, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 640, 'height' => null, 'option' => 'width'],
    ];

    /**
     * mod_video_embed_category image pipeline (admin imageOptions)
     */
    private const CATEGORY_IMAGE_SIZES = [
        '/' => ['width' => 200, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 150, 'height' => 90, 'option' => 'extract'],
    ];

    // ------------------------------------------------------------------
    // mod_video tools (local video records)
    // ------------------------------------------------------------------

    /**
     * Lists local videos with optional filters
     */
    public function listVideos(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_video');
        $model->select('id, lang, name, duration, status, slider, image, created_at');
        $this->applyVideoFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_video');
        $countModel->select('id');
        $this->applyVideoFilters($countModel, $args);
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
     * Returns a single local video record with all fields
     */
    public function getVideo(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadVideoById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Video record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a local video record (mirrors the admin add validator)
     */
    public function createVideo(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }

        $lang = $this->requireActiveLang($args);
        $duration = $this->requireDuration($args);

        $model = $this->freshVideoModel();
        $model->lang = $lang;
        $model->name = $name;
        $model->duration = $duration;
        $model->created_at = $this->optionalDateTime($args, 'created_at', date('Y-m-d H:i:s'));
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        $model->slider = $this->optionalEnum($args, 'slider', 'n', ['y', 'n']);
        $model->description = $this->optionalString($args, 'description', '', 65535);
        $model->flv = $this->optionalString($args, 'flv', '', 255);
        $model->original = $this->optionalString($args, 'original', '', 255);
        $model->seo = '';
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $model->user_id = $this->actingUserId;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_video', $args, self::VIDEO_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_video', $imageUrl, self::VIDEO_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the video record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getVideo(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a local video record
     */
    public function updateVideo(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadVideoById($id);

        $this->assertModerateEdit('mod_video', (int) $model->user_id);

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

        if (isset($args['duration'])) {
            $model->duration = $this->requireDuration($args);
        }

        if (isset($args['created_at'])) {
            $model->created_at = $this->optionalDateTime($args, 'created_at', (string) $model->created_at);
        }

        foreach (['status', 'slider'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalEnum($args, $field, (string) $model->{$field}, ['y', 'n']);
            }
        }

        if (isset($args['description'])) {
            $model->description = $this->optionalString($args, 'description', '', 65535);
        }

        foreach (['flv', 'original'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalString($args, $field, '', 255);
            }
        }

        if (isset($args['acl_id'])) {
            $model->acl_id = (int) $args['acl_id'];
        }

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_video', $args, self::VIDEO_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the video record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getVideo(['id' => $id])['item']
        ];
    }

    /**
     * Deletes a local video record with full admin parity cleanup
     */
    public function deleteVideo(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadVideoById($id);

        $this->assertModerateDelete('mod_video', (int) $model->user_id);

        $this->removeBridgeRelations('mod_video', $id);

        if ((string) ($model->original ?? '') !== '') {
            @unlink(root_dir . 'static' . DS . 'mod_video' . DS . 'original' . DS . $model->original);
        }
        if ((string) ($model->flv ?? '') !== '') {
            @unlink(root_dir . 'static' . DS . 'mod_video' . DS . 'flv' . DS . $model->flv);
        }
        if ((string) ($model->image ?? '') !== '') {
            $this->deleteImageFiles('mod_video', (string) $model->image, self::VIDEO_IMAGE_SIZES);
        }

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // mod_video_embed tools (embedded YouTube videos)
    // ------------------------------------------------------------------

    /**
     * Lists embedded videos with optional filters
     */
    public function listVideoEmbeds(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_video_embed');
        $model->select('id, lang, category_id, name, duration, status, slider, image, created_at');
        $this->applyVideoEmbedFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_video_embed');
        $countModel->select('id');
        $this->applyVideoEmbedFilters($countModel, $args);
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
     * Returns a single embedded video record with all fields
     */
    public function getVideoEmbed(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadVideoEmbedById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Video embed record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates an embedded video record (mirrors the admin add validator)
     */
    public function createVideoEmbed(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }

        $lang = $this->requireActiveLang($args);

        $categoryId = (int) ($args['category_id'] ?? 0);
        if ($categoryId <= 0) {
            throw new McpToolException("Field 'category_id' is required.");
        }
        $this->assertRecordMatchesLang('mod_video_embed_category', $categoryId, $lang, 'Video embed category');

        $duration = $this->requireDuration($args);

        $embed = trim((string) ($args['embed'] ?? ''));
        if ($embed === '') {
            throw new McpToolException("Field 'embed' is required.");
        }

        $content = (string) ($args['content'] ?? '');
        if (trim(strip_tags($content)) === '') {
            throw new McpToolException("Field 'content' is required.");
        }

        $title = trim((string) ($args['title'] ?? ''));
        if (mb_strlen($title) < 1 || mb_strlen($title) > 255) {
            throw new McpToolException("Field 'title' is required (1-255 characters).");
        }

        $keywords = trim((string) ($args['keywords'] ?? ''));
        if (mb_strlen($keywords) < 1 || mb_strlen($keywords) > 255) {
            throw new McpToolException("Field 'keywords' is required (1-255 characters).");
        }

        $description = trim((string) ($args['description'] ?? ''));
        if (mb_strlen($description) < 1 || mb_strlen($description) > 255) {
            throw new McpToolException("Field 'description' is required (1-255 characters).");
        }

        $this->assertNoUnsupportedCharacters([
            'name' => $name,
            'content' => $content,
            'title' => $title,
            'keywords' => $keywords,
            'description' => $description
        ]);

        $model = $this->freshModel('mod_video_embed');
        $model->lang = $lang;
        $model->category_id = $categoryId;
        $model->name = $name;
        $model->original = $this->optionalString($args, 'original', '', 255);
        $model->duration = $duration;
        $model->created_at = $this->optionalDateTime($args, 'created_at', date('Y-m-d H:i:s'));
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        $model->slider = $this->optionalEnum($args, 'slider', 'n', ['y', 'n']);
        $model->embed = $embed;
        $model->content = Helper::clear_html($content);
        $model->title = $title;
        $model->keywords = $keywords;
        $model->description = $description;
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $model->user_id = $this->actingUserId;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_video_embed', $args, self::EMBED_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_video_embed', $imageUrl, self::EMBED_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the video embed record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getVideoEmbed(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates an embedded video record
     */
    public function updateVideoEmbed(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadVideoEmbedById($id);

        $this->assertModerateEdit('mod_video_embed', (int) $model->user_id);

        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;
        $categoryId = isset($args['category_id']) ? (int) $args['category_id'] : (int) $model->category_id;

        if ($categoryId !== (int) $model->category_id || $lang !== (string) $model->lang) {
            $this->assertRecordMatchesLang('mod_video_embed_category', $categoryId, $lang, 'Video embed category');
        }

        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 2-255 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['original'])) {
            $model->original = $this->optionalString($args, 'original', '', 255);
        }

        if (isset($args['duration'])) {
            $model->duration = $this->requireDuration($args);
        }

        if (isset($args['created_at'])) {
            $model->created_at = $this->optionalDateTime($args, 'created_at', (string) $model->created_at);
        }

        foreach (['status', 'slider'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalEnum($args, $field, (string) $model->{$field}, ['y', 'n']);
            }
        }

        if (isset($args['embed'])) {
            $embed = trim((string) $args['embed']);
            if ($embed === '') {
                throw new McpToolException("Field 'embed' cannot be empty.");
            }
            $model->embed = $embed;
        }

        if (isset($args['content'])) {
            $content = (string) $args['content'];
            if (trim(strip_tags($content)) === '') {
                throw new McpToolException("Field 'content' cannot be empty.");
            }
            $model->content = Helper::clear_html($content);
        }

        foreach (['title', 'keywords', 'description'] as $field) {
            if (isset($args[$field])) {
                $value = trim((string) $args[$field]);
                if (mb_strlen($value) < 1 || mb_strlen($value) > 255) {
                    throw new McpToolException("Field '{$field}' must be 1-255 characters.");
                }
                $model->{$field} = $value;
            }
        }

        $this->assertNoUnsupportedCharacters([
            'name' => (string) $model->name,
            'content' => (string) $model->content,
            'title' => (string) $model->title,
            'keywords' => (string) $model->keywords,
            'description' => (string) $model->description
        ]);

        if (isset($args['acl_id'])) {
            $model->acl_id = (int) $args['acl_id'];
        }

        $model->lang = $lang;
        $model->category_id = $categoryId;

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_video_embed', $args, self::EMBED_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the video embed record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getVideoEmbed(['id' => $id])['item']
        ];
    }

    /**
     * Deletes an embedded video record with full admin parity cleanup
     */
    public function deleteVideoEmbed(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadVideoEmbedById($id);

        $this->assertModerateDelete('mod_video_embed', (int) $model->user_id);

        $this->removeBridgeRelations('mod_video_embed', $id);

        if ((string) ($model->image ?? '') !== '') {
            $this->deleteImageFiles('mod_video_embed', (string) $model->image, self::EMBED_IMAGE_SIZES);
            $this->deleteEmbedImageArtifacts((string) $model->image);
        }

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // mod_video_embed_category tools
    // ------------------------------------------------------------------

    /**
     * Lists video embed categories
     */
    public function listVideoEmbedCategories(array $args): array
    {
        return $this->listCategoriesGeneric('mod_video_embed_category', $args, 50);
    }

    /**
     * Returns a single video embed category with all fields
     */
    public function getVideoEmbedCategory(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadVideoEmbedCategoryById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Video embed category record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a video embed category (mirrors the admin add validator)
     */
    public function createVideoEmbedCategory(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }

        $lang = strtolower($this->requireActiveLang($args));

        $parentId = (int) ($args['parent_id'] ?? 0);
        $this->validateCategoryParent($lang, $parentId);

        $seo = trim((string) ($args['seo'] ?? ''));
        if ($seo === '') {
            $seo = self::makeSeo($name, $lang);
        }
        if ($seo === '' || mb_strlen($seo) > 255) {
            throw new McpToolException("Field 'seo' must be 1-255 characters.");
        }
        if ($this->recordSeoExists('mod_video_embed_category', $seo, $lang)) {
            throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
        }

        $title = trim((string) ($args['title'] ?? ''));
        if ($title === '') {
            $title = $name;
        }
        if (mb_strlen($title) > 255) {
            throw new McpToolException("Field 'title' must be 1-255 characters.");
        }

        $keywords = trim((string) ($args['keywords'] ?? ''));
        if ($keywords === '' || mb_strlen($keywords) > 255) {
            throw new McpToolException("Field 'keywords' is required (1-255 characters).");
        }

        $description = trim((string) ($args['description'] ?? ''));
        if ($description === '' || mb_strlen($description) > 255) {
            throw new McpToolException("Field 'description' is required (1-255 characters).");
        }

        $model = $this->freshModel('mod_video_embed_category');
        $model->lang = $lang;
        $model->name = $name;
        $model->parent_id = $parentId;
        $model->root = $this->optionalEnum($args, 'root', 'n', ['y', 'n']);
        $model->seo = $seo;
        $model->title = $title;
        $model->keywords = $keywords;
        $model->description = $description;
        $model->order = $this->nextSiblingOrder('mod_video_embed_category', $parentId);
        $model->user_id = $this->actingUserId;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_video_embed_category', $args, self::CATEGORY_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_video_embed_category', $imageUrl, self::CATEGORY_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the video embed category record.');
        }

        $menuParentId = (int) ($args['menu'] ?? 0);
        $menuAclId = (int) ($args['acl_id'] ?? 0);
        $this->syncVideoCategoryMenuOnCreate($model, $menuParentId, $menuAclId);

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getVideoEmbedCategory(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a video embed category and syncs its menu entry
     */
    public function updateVideoEmbedCategory(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadVideoEmbedCategoryById($id);

        $this->assertModerateEdit('mod_video_embed_category', (int) $model->user_id);

        $previousMenuUrl = $this->videoCategoryMenuUrl((string) $model->lang, (string) $model->seo);
        $previousParentId = (int) $model->parent_id;

        if (isset($args['lang'])) {
            $model->lang = strtolower($this->requireActiveLang($args));
        }

        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 2-255 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['parent_id'])) {
            $parentId = (int) $args['parent_id'];
            $this->validateCategoryParent((string) $model->lang, $parentId, $id);
            $model->parent_id = $parentId;
        }

        if (isset($args['root'])) {
            $model->root = $this->optionalEnum($args, 'root', (string) $model->root, ['y', 'n']);
        }

        if (isset($args['seo'])) {
            $seo = trim((string) $args['seo']);
            if ($seo === '' || mb_strlen($seo) > 255) {
                throw new McpToolException("Field 'seo' must be 1-255 characters.");
            }
            if ($this->recordSeoExists('mod_video_embed_category', $seo, (string) $model->lang, $id)) {
                throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
            }
            $model->seo = $seo;
        }

        foreach (['title', 'keywords', 'description'] as $field) {
            if (isset($args[$field])) {
                $value = trim((string) $args[$field]);
                if (mb_strlen($value) < 1 || mb_strlen($value) > 255) {
                    throw new McpToolException("Field '{$field}' must be 1-255 characters.");
                }
                $model->{$field} = $value;
            }
        }

        if (isset($args['order'])) {
            $order = (int) $args['order'];
            if ($order < 0) {
                throw new McpToolException("Field 'order' must be a non-negative integer.");
            }
            $model->order = $order;
        } elseif ((int) $model->parent_id !== $previousParentId) {
            $model->order = $this->nextSiblingOrder('mod_video_embed_category', (int) $model->parent_id);
        }

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_video_embed_category', $args, self::CATEGORY_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the video embed category record.');
        }

        $menuParentId = isset($args['menu']) ? (int) $args['menu'] : null;
        $menuAclId = isset($args['acl_id']) ? (int) $args['acl_id'] : null;
        $this->syncVideoCategoryMenuOnUpdate($previousMenuUrl, $model, $menuParentId, $menuAclId);

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getVideoEmbedCategory(['id' => $id])['item']
        ];
    }

    /**
     * Recursively deletes a video embed category tree with admin parity cleanup
     */
    public function deleteVideoEmbedCategory(array $args): array
    {
        $id = $this->requireId($args);
        $deletedIds = [];
        $this->deleteVideoEmbedCategoryTree($id, $deletedIds);

        return ['deleted' => true, 'ids' => $deletedIds];
    }

    // ------------------------------------------------------------------
    // mod_video helpers
    // ------------------------------------------------------------------

    private function loadVideoById(int $id): mod_video_model
    {
        $model = $this->freshVideoModel();
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Video record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }
    private function applyVideoFilters($model, array $args): void
    {
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->addWhere('lang = \'' . Obuilder::cc(trim((string) $args['lang'])) . '\'');
        }
        if (isset($args['status']) && in_array($args['status'], ['y', 'n'], true)) {
            $model->addWhere('status = \'' . $args['status'] . '\'');
        }
        if (isset($args['slider']) && in_array($args['slider'], ['y', 'n'], true)) {
            $model->addWhere('slider = \'' . $args['slider'] . '\'');
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $model->addWhere('name LIKE \'%' . Obuilder::cc(trim((string) $args['search'])) . '%\'');
        }
    }

    /**
     * Creates a fresh mod_video model. Database drift workaround: the model
     * file does not declare the seo column the table defines as NOT NULL, so
     * the runtime column list is extended to satisfy the insert constraint.
     */
    private function freshVideoModel(): mod_video_model
    {
        $model = $this->freshModel('mod_video');
        if (!in_array('seo', $model->_orm_coloms, true)) {
            $model->_orm_coloms[] = 'seo';
        }
        $model->seo = '';
        return $model;
    }


    /**
     * Duration field shared by mod_video and mod_video_embed (admin length 5-8, e.g. HH:MM:SS)
     */
    private function requireDuration(array $args): string
    {
        $duration = trim((string) ($args['duration'] ?? ''));
        $length = mb_strlen($duration);
        if ($length < 5 || $length > 8) {
            throw new McpToolException("Field 'duration' is required (5-8 characters, e.g. HH:MM:SS).");
        }
        return $duration;
    }

    /**
     * Mirrors the admin emoji guard: characters outside the BMP are rejected
     */
    private function assertNoUnsupportedCharacters(array $fields): void
    {
        foreach ($fields as $field => $value) {
            if ($value !== '' && preg_match('/[\x{10000}-\x{10FFFF}]/u', (string) $value) === 1) {
                throw new McpToolException("Field '{$field}' contains unsupported characters (emoji are not allowed).");
            }
        }
    }

    // ------------------------------------------------------------------
    // mod_video_embed helpers
    // ------------------------------------------------------------------

    private function loadVideoEmbedById(int $id): mod_video_embed_model
    {
        $model = $this->freshModel('mod_video_embed');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Video embed record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyVideoEmbedFilters($model, array $args): void
    {
        if (isset($args['category_id']) && (int) $args['category_id'] > 0) {
            $model->addWhere('category_id = ' . (int) $args['category_id']);
        }
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->addWhere('lang = \'' . Obuilder::cc(trim((string) $args['lang'])) . '\'');
        }
        if (isset($args['status']) && in_array($args['status'], ['y', 'n'], true)) {
            $model->addWhere('status = \'' . $args['status'] . '\'');
        }
        if (isset($args['slider']) && in_array($args['slider'], ['y', 'n'], true)) {
            $model->addWhere('slider = \'' . $args['slider'] . '\'');
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $model->addWhere('name LIKE \'%' . Obuilder::cc(trim((string) $args['search'])) . '%\'');
        }
    }

    /**
     * The admin pipeline also keeps raw copies under static/mod_video_embed and
     * static/mod_video_embed/thumb; the image module delete removes the sized
     * variants, this removes any leftover raw artifacts.
     */
    private function deleteEmbedImageArtifacts(string $filename): void
    {
        $filename = basename($filename);
        if ($filename === '') {
            return;
        }

        $paths = [
            root_dir . 'static' . DS . 'mod_video_embed' . DS . $filename,
            root_dir . 'static' . DS . 'mod_video_embed' . DS . 'thumb' . DS . $filename,
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    // ------------------------------------------------------------------
    // mod_video_embed_category helpers
    // ------------------------------------------------------------------

    private function loadVideoEmbedCategoryById(int $id): mod_video_embed_category_model
    {
        $model = $this->freshModel('mod_video_embed_category');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Video embed category record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function videoCategoryMenuUrl(string $lang, string $seo): string
    {
        return $lang . '/videos/' . $seo;
    }

    /**
     * Mirrors the admin validateCategoryParent: a zero parent is only allowed
     * while the language has no language-root row yet; non-zero parents must
     * exist and speak the same language.
     */
    private function validateCategoryParent(string $lang, int $parentId, int $currentId = 0): void
    {
        if ($parentId === 0) {
            $rootModel = $this->freshModel('mod_video_embed_category');
            $rootModel->addWhere('lang = \'' . Obuilder::cc($lang) . '\'');
            $rootModel->addWhere('parent_id = 0');
            $rootModel->addWhere('root = \'y\'');
            $rootModel->limit(1)->getResultOne(true, true);
            $rootModel->sync();
            if ($rootModel->getSize() > 0 && (int) $rootModel->id !== $currentId) {
                throw new McpToolException("Field 'parent_id' cannot be 0: language '{$lang}' already has a language root category. Nest under it instead.");
            }
            return;
        }

        if ($parentId === $currentId) {
            throw new McpToolException("A category cannot be its own parent.");
        }

        $parent = $this->loadVideoEmbedCategoryById($parentId);
        if ((string) $parent->lang !== $lang) {
            throw new McpToolException("Parent category '{$parentId}' belongs to language '" . $parent->lang . "', cannot nest a '{$lang}' category under it.");
        }

        if ($currentId > 0 && $this->isCategoryDescendantOf($parentId, $currentId)) {
            throw new McpToolException("Field 'parent_id' cannot be a descendant of the category being moved (cycle).");
        }
    }

    private function isCategoryDescendantOf(int $candidateId, int $ancestorId): bool
    {
        $visited = [];
        $candidateId = (int) $candidateId;

        while ($candidateId > 0 && !isset($visited[$candidateId])) {
            if ($candidateId === $ancestorId) {
                return true;
            }
            $visited[$candidateId] = true;

            $row = $this->freshModel('mod_video_embed_category');
            $row->select('parent_id')->where_id($candidateId)->limit(1)->getResultOne(true, true);
            if ($row->getSize() === 0) {
                return false;
            }
            $row->sync();
            $candidateId = (int) $row->parent_id;
        }

        return false;
    }

    private function syncVideoCategoryMenuOnCreate($category, int $menuParentId, int $menuAclId): void
    {
        if ($menuParentId === 0) {
            return;
        }

        $menuModel = $this->freshModel('mod_menu');
        $menuModel->parent_id = $menuParentId;
        $menuModel->name = (string) $category->name;
        $menuModel->lang = (string) $category->lang;
        $menuModel->url = $this->videoCategoryMenuUrl((string) $category->lang, (string) $category->seo);
        $menuModel->acl_id = $menuAclId;
        $menuModel->target = 'n';
        $menuModel->status = 'y';
        $menuModel->order = $this->nextMenuSiblingOrder($menuParentId, (string) $category->lang);
        $menuModel->user_id = $this->actingUserId;
        $menuModel->save();
    }

    private function syncVideoCategoryMenuOnUpdate(string $previousUrl, $category, ?int $menuParentId, ?int $menuAclId): void
    {
        if ($menuParentId === null) {
            // No menu intent supplied: keep an existing entry aligned with the new URL only.
            $menuModel = $this->freshModel('mod_menu');
            if ($menuModel->exists_url($previousUrl)) {
                $menuModel->getResultOne(true, true)->sync();
                $menuModel->name = (string) $category->name;
                $menuModel->lang = (string) $category->lang;
                $menuModel->url = $this->videoCategoryMenuUrl((string) $category->lang, (string) $category->seo);
                $menuModel->update();
            }
            return;
        }

        $menuModel = $this->freshModel('mod_menu');
        if ($menuParentId === 0) {
            if ($menuModel->exists_url($previousUrl)) {
                $menuModel->getResultOne(true, true)->delete();
            }
            return;
        }

        $menuUrl = $this->videoCategoryMenuUrl((string) $category->lang, (string) $category->seo);
        if ($menuModel->exists_url($previousUrl)) {
            $menuModel->getResultOne(true, true)->sync();
            $previousParentId = (int) $menuModel->parent_id;
            $menuModel->name = (string) $category->name;
            $menuModel->lang = (string) $category->lang;
            $menuModel->url = $menuUrl;
            if ($menuAclId !== null) {
                $menuModel->acl_id = $menuAclId;
            }
            $menuModel->parent_id = $menuParentId;
            $menuModel->target = 'n';
            $menuModel->status = 'y';
            if ($previousParentId !== $menuParentId) {
                $menuModel->order = $this->nextMenuSiblingOrder($menuParentId, (string) $category->lang);
            }
            $menuModel->update();
            return;
        }

        $menuModel->parent_id = $menuParentId;
        $menuModel->name = (string) $category->name;
        $menuModel->lang = (string) $category->lang;
        $menuModel->url = $menuUrl;
        $menuModel->acl_id = $menuAclId ?? 0;
        $menuModel->target = 'n';
        $menuModel->status = 'y';
        $menuModel->order = $this->nextMenuSiblingOrder($menuParentId, (string) $category->lang);
        $menuModel->user_id = $this->actingUserId;
        $menuModel->save();
    }

    private function nextMenuSiblingOrder(int $parentId, string $lang): int
    {
        $menuModel = $this->freshModel('mod_menu');
        $menuModel->select('`order`');
        $menuModel->addWhere('parent_id = ' . (int) $parentId);
        if (trim($lang) !== '') {
            $menuModel->addWhere('lang = \'' . Obuilder::cc($lang) . '\'');
        }
        $menuModel->order('`order`', 'DESC')->limit(1)->getResultOne(true, true);
        if ($menuModel->getSize() === 0) {
            return 1;
        }
        $menuModel->sync();
        return (int) $menuModel->order + 1;
    }

    private function deleteVideoEmbedCategoryTree(int $id, array &$deletedIds): void
    {
        $model = $this->loadVideoEmbedCategoryById($id);

        $this->assertModerateDelete('mod_video_embed_category', (int) $model->user_id);

        $childrenModel = $this->freshModel('mod_video_embed_category');
        $childrenModel->where_parent_id($id)->getResult(true, true);
        $children = $childrenModel->toArray();
        foreach ((is_array($children) ? $children : []) as $child) {
            $this->deleteVideoEmbedCategoryTree((int) $child['id'], $deletedIds);
        }

        if ((string) ($model->image ?? '') !== '') {
            $this->deleteImageFiles('mod_video_embed_category', (string) $model->image, self::CATEGORY_IMAGE_SIZES);
        }

        $this->removeBridgeRelations('mod_video_embed_category', $id);

        $menuUrl = $this->videoCategoryMenuUrl((string) $model->lang, (string) $model->seo);
        $menuModel = $this->freshModel('mod_menu');
        if ($menuModel->exists_url($menuUrl)) {
            $menuModel->getResultOne(true, true)->delete();
        }

        $model->delete();
        $deletedIds[] = $id;
    }
}
