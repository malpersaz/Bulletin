<?php
/**
 * Amed Framework
 * MCP Event Tools - CRUD implementations for mod_event and mod_event_category
 * lib/McpEventTools.class.php
 */

class McpEventTools extends McpCrudToolsBase
{
    /**
     * Return array of tool definitions provided by this domain tool class.
     *
     * @return array<string, array>
     */
    public static function definitions(): array
    {
        return [
            'list_events' => [
                'module' => 'mod_event',
                'role' => 'list',
                'domain' => 'event',
                'handler' => 'listEvents',
                'description' => 'List events with optional filters. Returns paginated items (id, lang, name, category_id, status, deck, slider, start_date, end_date, location, seo, created_at) plus the total count.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'category_id' => ['type' => 'integer', 'description' => 'Filter by event category ID.'],
                        'lang' => ['type' => 'string', 'description' => 'Filter by language code (e.g. ku, en, tr).'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by publish status: y (published) or n (draft).'],
                        'deck' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by deck flag.'],
                        'slider' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by slider flag.'],
                        'search' => ['type' => 'string', 'description' => 'Search term matched against event names, summaries, or topics.'],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10, 'description' => 'Page size (1-50, default 10).'],
                        'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Number of items to skip for pagination.'],
                    ],
                ],
                'title' => 'List Events',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_event' => [
                'module' => 'mod_event',
                'role' => 'list',
                'domain' => 'event',
                'handler' => 'getEvent',
                'description' => 'Get a single event record with all fields by numeric id or URL slug (seo + optional lang).',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Event record ID (optional if seo is provided).'],
                        'seo' => ['type' => 'string', 'description' => 'Event URL slug (optional if id is provided).'],
                        'lang' => ['type' => 'string', 'description' => 'Language code (e.g. tr, ku, en) when resolving by seo slug.'],
                    ],
                ],
                'title' => 'Get Event',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_event' => [
                'module' => 'mod_event',
                'role' => 'add',
                'domain' => 'event',
                'handler' => 'createEvent',
                'description' => 'Create an event record. Fields include name, language, category, start date, venue, topic, description, and optional 3:4 poster.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Event name / title (1-255 characters).'],
                        'lang' => ['type' => 'string', 'description' => 'Language code of an active site language (e.g. ku, en, tr).'],
                        'category_id' => ['type' => 'integer', 'description' => 'ID of an existing event category with the same language.'],
                        'start_date' => ['type' => 'string', 'description' => 'Event start datetime (YYYY-MM-DD HH:MM:SS or YYYY-MM-DD).'],
                        'end_date' => ['type' => 'string', 'description' => 'Event end datetime (optional).'],
                        'location' => ['type' => 'string', 'description' => 'Event location / venue.'],
                        'subject' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Topic / Subject / Subtitle.'],
                        'organizer' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Event organizer name.'],
                        'guests' => ['type' => 'string', 'description' => 'Invited guests and speakers.'],
                        'participants' => ['type' => 'string', 'description' => 'Expected participants or registration info.'],
                        'content' => ['type' => 'string', 'description' => 'Full HTML details of the event.'],
                        'summary' => ['type' => 'string', 'description' => 'Short summary.'],
                        'seo' => ['type' => 'string', 'maxLength' => 255, 'description' => 'URL slug. Defaults to slug generated from name.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO title. Defaults to name.'],
                        'keywords' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO keywords.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO meta description.'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'y', 'description' => 'Publish status: y (published) or n (draft).'],
                        'deck' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Show in deck: y or n.'],
                        'slider' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Show in hero slider: y or n.'],
                        'gallery_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Related gallery ID.'],
                        'file_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Related attachment file ID.'],
                        'video' => ['type' => 'string', 'description' => 'Video embed or serialized array.'],
                        'acl_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Access control group ID (0 = public).'],
                        'image' => ['type' => ['object', 'null'], 'description' => 'Upload a 3:4 poster (base64-encoded) or null.', 'properties' => ['data_base64' => ['type' => 'string'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['name', 'lang', 'category_id', 'start_date'],
                ],
                'title' => 'Create Event',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_event' => [
                'module' => 'mod_event',
                'role' => 'edit',
                'domain' => 'event',
                'handler' => 'updateEvent',
                'description' => 'Partially update an event record. Only provided fields are modified.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Event record ID.'],
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New event name.'],
                        'lang' => ['type' => 'string', 'description' => 'New language code.'],
                        'category_id' => ['type' => 'integer', 'description' => 'New category ID.'],
                        'start_date' => ['type' => 'string', 'description' => 'New start datetime.'],
                        'end_date' => ['type' => 'string', 'description' => 'New end datetime.'],
                        'location' => ['type' => 'string', 'description' => 'New venue / location.'],
                        'subject' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New topic / subject.'],
                        'organizer' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New organizer.'],
                        'guests' => ['type' => 'string', 'description' => 'New guests.'],
                        'participants' => ['type' => 'string', 'description' => 'New participants.'],
                        'content' => ['type' => 'string', 'description' => 'New full details.'],
                        'summary' => ['type' => 'string', 'description' => 'New summary.'],
                        'seo' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New URL slug.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO title.'],
                        'keywords' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO keywords.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO description.'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New publish status.'],
                        'deck' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New deck status.'],
                        'slider' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'New slider status.'],
                        'gallery_id' => ['type' => 'integer', 'description' => 'New gallery ID.'],
                        'file_id' => ['type' => 'integer', 'description' => 'New attachment file ID.'],
                        'video' => ['type' => 'string', 'description' => 'New video info.'],
                        'acl_id' => ['type' => 'integer', 'description' => 'New ACL ID.'],
                        'image' => ['type' => ['object', 'null'], 'description' => 'New image or null to delete.', 'properties' => ['data_base64' => ['type' => 'string'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Update Event',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_event' => [
                'module' => 'mod_event',
                'role' => 'delete',
                'domain' => 'event',
                'handler' => 'deleteEvent',
                'description' => 'Delete an event record permanently. Associated poster images and bridges are cleaned up.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Event record ID.'],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Delete Event',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
            'list_event_categories' => [
                'module' => 'mod_event_category',
                'role' => 'list',
                'domain' => 'event',
                'handler' => 'listEventCategories',
                'description' => 'List event categories with optional language filtering.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => ['type' => 'string', 'description' => 'Filter by language code.'],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50, 'description' => 'Page size.'],
                        'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Pagination offset.'],
                    ],
                ],
                'title' => 'List Event Categories',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_event_category' => [
                'module' => 'mod_event_category',
                'role' => 'list',
                'domain' => 'event',
                'handler' => 'getEventCategory',
                'description' => 'Get a single event category record by ID or SEO slug.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Category ID.'],
                        'seo' => ['type' => 'string', 'description' => 'Category SEO slug.'],
                        'lang' => ['type' => 'string', 'description' => 'Language code for SEO lookup.'],
                    ],
                ],
                'title' => 'Get Event Category',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_event_category' => [
                'module' => 'mod_event_category',
                'role' => 'add',
                'domain' => 'event',
                'handler' => 'createEventCategory',
                'description' => 'Create a new event category.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Category name.'],
                        'lang' => ['type' => 'string', 'description' => 'Language code.'],
                        'parent_id' => ['type' => 'integer', 'default' => 0, 'description' => 'Parent category ID.'],
                        'root' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Root flag.'],
                        'seo' => ['type' => 'string', 'maxLength' => 255, 'description' => 'URL slug.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO title.'],
                        'keywords' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO keywords.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO description.'],
                        'acl_id' => ['type' => 'integer', 'default' => 0, 'description' => 'ACL group ID.'],
                        'image' => ['type' => ['object', 'null'], 'description' => 'Optional category image.', 'properties' => ['data_base64' => ['type' => 'string'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['name', 'lang'],
                ],
                'title' => 'Create Event Category',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_event_category' => [
                'module' => 'mod_event_category',
                'role' => 'edit',
                'domain' => 'event',
                'handler' => 'updateEventCategory',
                'description' => 'Partially update an event category record.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Category ID.'],
                        'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New name.'],
                        'lang' => ['type' => 'string', 'description' => 'New language code.'],
                        'parent_id' => ['type' => 'integer', 'description' => 'New parent ID.'],
                        'root' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Root flag.'],
                        'seo' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New URL slug.'],
                        'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO title.'],
                        'keywords' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New keywords.'],
                        'description' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New SEO description.'],
                        'acl_id' => ['type' => 'integer', 'description' => 'New ACL ID.'],
                        'image' => ['type' => ['object', 'null'], 'description' => 'New image.', 'properties' => ['data_base64' => ['type' => 'string'], 'mime' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']]], 'required' => ['data_base64', 'mime']],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Update Event Category',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_event_category' => [
                'module' => 'mod_event_category',
                'role' => 'delete',
                'domain' => 'event',
                'handler' => 'deleteEventCategory',
                'description' => 'Delete an event category record and its child categories.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Category ID.'],
                    ],
                    'required' => ['id'],
                ],
                'title' => 'Delete Event Category',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
        ];
    }

    private const EVENT_IMAGE_SIZES = [
        '/' => ['width' => 800, 'height' => 1066, 'option' => 'extract'],
        '/thumb' => ['width' => 400, 'height' => 533, 'option' => 'extract'],
    ];

    private const EVENT_CATEGORY_IMAGE_SIZES = [
        '/' => ['width' => 200, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 150, 'height' => 90, 'option' => 'extract'],
    ];

    // ------------------------------------------------------------------
    // Event Handlers
    // ------------------------------------------------------------------

    public function listEvents(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_event');
        $model->select('id, lang, name, category_id, status, deck, slider, start_date, end_date, location, seo, created_at');
        $this->applyEventFilters($model, $args);
        $model->order('start_date', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_event');
        $countModel->select('id');
        $this->applyEventFilters($countModel, $args);
        $countModel->getResult(true, true);
        $total = $countModel->getSize();

        return [
            'items' => is_array($items) ? $items : [],
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    public function getEvent(array $args): array
    {
        $model = $this->loadRecordByIdOrSeo('mod_event', $args, 'Event');
        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Event record not found.");
        }
        $row['item'] = $row;
        return $row;
    }

    public function createEvent(array $args): array
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
        $this->assertRecordMatchesLang('mod_event_category', $categoryId, $lang, 'Event category');

        $startDate = trim((string) ($args['start_date'] ?? ''));
        if ($startDate === '') {
            throw new McpToolException("Field 'start_date' is required.");
        }

        $content = isset($args['content']) ? $this->sanitizeBodyHtml((string) $args['content']) : '';
        $summary = isset($args['summary']) ? trim(strip_tags((string) $args['summary'])) : '';

        $seo = trim((string) ($args['seo'] ?? ''));
        if ($seo === '') {
            $seo = self::makeSeo($name, $lang);
        } else {
            $seo = Helper::legacy_seo($seo, $lang);
        }
        if ($seo === '' || mb_strlen($seo) > 255) {
            throw new McpToolException("Field 'seo' must be 1-255 characters.");
        }
        if ($this->recordSeoExists('mod_event', $seo, $lang)) {
            throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
        }

        $model = $this->freshModel('mod_event');
        $model->name = $name;
        $model->lang = $lang;
        $model->category_id = $categoryId;
        $model->start_date = $startDate;
        $model->end_date = !empty($args['end_date']) ? (string) $args['end_date'] : null;
        $model->location = $this->optionalString($args, 'location', '', 255);
        $model->subject = $this->optionalString($args, 'subject', '', 255);
        $model->guests = $this->optionalString($args, 'guests', '', 1000);
        $model->participants = $this->optionalString($args, 'participants', '', 1000);
        $model->content = $content;
        $model->summary = $summary;
        $model->seo = $seo;
        $model->title = $this->optionalString($args, 'title', $name, 255);
        $model->keywords = $this->optionalString($args, 'keywords', '', 255);
        $model->description = $this->optionalString($args, 'description', $summary, 255);
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        $model->deck = $this->optionalEnum($args, 'deck', 'n', ['y', 'n']);
        $model->slider = $this->optionalEnum($args, 'slider', 'n', ['y', 'n']);
        $model->gallery_id = (int) ($args['gallery_id'] ?? 0);
        $model->file_id = (int) ($args['file_id'] ?? 0);
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $model->video = !empty($args['video']) ? (string) $args['video'] : null;
        $model->user_id = $this->actingUserId;
        $model->view_count = 0;
        $model->created_at = date('Y-m-d H:i:s');

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_event', $args, self::EVENT_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_event', $imageUrl, self::EVENT_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the event record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getEvent(['id' => (int) $model->id])['item']
        ];
    }

    public function updateEvent(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->freshModel('mod_event');
        $model->where_id($id)->gRo();
        if ($model->getSize() === 0) {
            throw new McpToolException("Event record not found.");
        }
        $model->sync();

        $oldLang = (string) $model->lang;
        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : $oldLang;
        $categoryId = isset($args['category_id']) ? (int) $args['category_id'] : (int) $model->category_id;

        if ($categoryId !== (int) $model->category_id || $lang !== $oldLang) {
            $this->assertRecordMatchesLang('mod_event_category', $categoryId, $lang, 'Event category');
            $model->category_id = $categoryId;
            $model->lang = $lang;
        }

        if (isset($args['name'])) {
            $name = trim(strip_tags((string) $args['name']));
            if ($name === '' || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 1-255 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['start_date'])) {
            $model->start_date = trim((string) $args['start_date']);
        }
        if (array_key_exists('end_date', $args)) {
            $model->end_date = !empty($args['end_date']) ? (string) $args['end_date'] : null;
        }
        if (isset($args['location'])) {
            $model->location = trim(strip_tags((string) $args['location']));
        }
        if (isset($args['subject'])) {
            $model->subject = trim(strip_tags((string) $args['subject']));
        }
        if (isset($args['guests'])) {
            $model->guests = trim((string) $args['guests']);
        }
        if (isset($args['participants'])) {
            $model->participants = trim((string) $args['participants']);
        }
        if (isset($args['content'])) {
            $model->content = $this->sanitizeBodyHtml((string) $args['content']);
        }
        if (isset($args['summary'])) {
            $model->summary = trim(strip_tags((string) $args['summary']));
        }

        if (isset($args['seo'])) {
            $seo = Helper::legacy_seo(trim((string) $args['seo']), $lang);
            if ($seo === '' || mb_strlen($seo) > 255) {
                throw new McpToolException("Field 'seo' must be 1-255 characters.");
            }
            if ($this->recordSeoExists('mod_event', $seo, $lang, $id)) {
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

        if (isset($args['gallery_id'])) $model->gallery_id = (int) $args['gallery_id'];
        if (isset($args['file_id'])) $model->file_id = (int) $args['file_id'];
        if (isset($args['acl_id'])) $model->acl_id = (int) $args['acl_id'];
        if (isset($args['video'])) $model->video = (string) $args['video'];

        if (array_key_exists('image', $args)) {
            $newImage = $this->resolveImageArgument('mod_event', $args, self::EVENT_IMAGE_SIZES, (string) $model->image);
            $model->image = (string) $newImage;
        }

        $model->updated_at = date('Y-m-d H:i:s');
        if (!$model->save(true)) {
            throw new McpToolException("Failed to update event record #{$id}.");
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getEvent(['id' => $id])['item']
        ];
    }

    public function deleteEvent(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->freshModel('mod_event');
        $model->where_id($id)->gRo();
        if ($model->getSize() === 0) {
            throw new McpToolException("Event record not found.");
        }
        $model->sync();

        $image = (string) $model->image;
        if (!$model->delete()) {
            throw new McpToolException("Failed to delete event record #{$id}.");
        }

        if ($image !== '') {
            $this->deleteImageFiles('mod_event', $image, self::EVENT_IMAGE_SIZES);
        }

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // Event Category Handlers
    // ------------------------------------------------------------------

    public function listEventCategories(array $args): array
    {
        return $this->listCategoriesGeneric('mod_event_category', $args, 50);
    }

    public function getEventCategory(array $args): array
    {
        $model = $this->loadRecordByIdOrSeo('mod_event_category', $args, 'Event category');
        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Event category record not found.");
        }
        $row['item'] = $row;
        return $row;
    }

    public function createEventCategory(array $args): array
    {
        $name = trim(strip_tags((string) ($args['name'] ?? '')));
        if ($name === '' || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (1-255 characters).");
        }
        $lang = $this->requireActiveLang($args);

        $seo = trim((string) ($args['seo'] ?? ''));
        if ($seo === '') {
            $seo = self::makeSeo($name, $lang);
        } else {
            $seo = Helper::legacy_seo($seo, $lang);
        }
        if ($this->recordSeoExists('mod_event_category', $seo, $lang)) {
            throw new McpToolException("Category slug '{$seo}' is already taken in language '{$lang}'.");
        }

        $model = $this->freshModel('mod_event_category');
        $model->name = $name;
        $model->lang = $lang;
        $model->parent_id = (int) ($args['parent_id'] ?? 0);
        $model->root = $this->optionalEnum($args, 'root', 'n', ['y', 'n']);
        $model->seo = $seo;
        $model->title = $this->optionalString($args, 'title', $name, 255);
        $model->keywords = $this->optionalString($args, 'keywords', '', 255);
        $model->description = $this->optionalString($args, 'description', '', 255);
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $model->user_id = $this->actingUserId;
        $model->order = $this->nextSiblingOrder('mod_event_category', $model->parent_id);

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_event_category', $args, self::EVENT_CATEGORY_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_event_category', $imageUrl, self::EVENT_CATEGORY_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the event category record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getEventCategory(['id' => (int) $model->id])['item']
        ];
    }

    public function updateEventCategory(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->freshModel('mod_event_category');
        $model->where_id($id)->gRo();
        if ($model->getSize() === 0) {
            throw new McpToolException("Event category record not found.");
        }
        $model->sync();

        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;
        $model->lang = $lang;

        if (isset($args['name'])) {
            $name = trim(strip_tags((string) $args['name']));
            if ($name === '' || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 1-255 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['parent_id'])) $model->parent_id = (int) $args['parent_id'];
        if (isset($args['root'])) $model->root = $this->optionalEnum($args, 'root', (string) $model->root, ['y', 'n']);

        if (isset($args['seo'])) {
            $seo = Helper::legacy_seo(trim((string) $args['seo']), $lang);
            if ($this->recordSeoExists('mod_event_category', $seo, $lang, $id)) {
                throw new McpToolException("Category slug '{$seo}' is already taken in language '{$lang}'.");
            }
            $model->seo = $seo;
        }

        foreach (['title', 'keywords', 'description'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalString($args, $field, (string) $model->{$field}, 255);
            }
        }

        if (isset($args['acl_id'])) $model->acl_id = (int) $args['acl_id'];

        if (array_key_exists('image', $args)) {
            $newImage = $this->resolveImageArgument('mod_event_category', $args, self::EVENT_CATEGORY_IMAGE_SIZES, (string) $model->image);
            $model->image = (string) $newImage;
        }

        if (!$model->save(true)) {
            throw new McpToolException("Failed to update event category record #{$id}.");
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getEventCategory(['id' => $id])['item']
        ];
    }

    public function deleteEventCategory(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->freshModel('mod_event_category');
        $model->where_id($id)->gRo();
        if ($model->getSize() === 0) {
            throw new McpToolException("Event category record not found.");
        }
        $model->sync();

        $image = (string) $model->image;
        if (!$model->delete()) {
            throw new McpToolException("Failed to delete event category record #{$id}.");
        }

        if ($image !== '') {
            $this->deleteImageFiles('mod_event_category', $image, self::EVENT_CATEGORY_IMAGE_SIZES);
        }

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // Internal Filter Helpers
    // ------------------------------------------------------------------

    private function applyEventFilters(ORM $model, array $args): void
    {
        if (!empty($args['category_id'])) {
            $model->addWhere('category_id = ' . (int) $args['category_id']);
        }
        if (!empty($args['lang'])) {
            $model->addWhere("lang = '" . addslashes((string) $args['lang']) . "'");
        }
        if (!empty($args['status'])) {
            $model->addWhere("status = '" . addslashes((string) $args['status']) . "'");
        }
        if (!empty($args['deck'])) {
            $model->addWhere("deck = '" . addslashes((string) $args['deck']) . "'");
        }
        if (!empty($args['slider'])) {
            $model->addWhere("slider = '" . addslashes((string) $args['slider']) . "'");
        }
        if (!empty($args['search'])) {
            $safeSearch = addslashes((string) $args['search']);
            $model->addWhere("(name LIKE '%{$safeSearch}%' OR summary LIKE '%{$safeSearch}%' OR subject LIKE '%{$safeSearch}%')");
        }
    }
}
