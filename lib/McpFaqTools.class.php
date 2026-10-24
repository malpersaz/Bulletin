<?php
/**
 * Amed Framework
 * MCP FAQ Tools - CRUD implementations for mod_faq
 * lib/McpFaqTools.class.php
 */

class McpFaqTools extends McpCrudToolsBase
{
    /**
     * Return array of tool definitions provided by this domain tool class.
     *
     * @return array<string, array>
     */
    public static function definitions(): array
    {
        return [
            'list_faqs' => [
                'module' => 'mod_faq',
                'role' => 'list',
                'domain' => 'faq',
                'handler' => 'listFaqs',
                'description' => 'List FAQ records with optional filters, ordered by sort_order then id. Returns paginated items (id, lang, mod_name, vid, question, sort_order, status, created_at, updated_at) plus the total count. The answer body is only returned by get_faq.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'mod_name' => [
                            'type' => 'string',
                            'description' => 'Filter by host module name (e.g. mod_page). FAQ records are attached to records of host modules registered in mod_faq_mod.',
                        ],
                        'vid' => [
                            'type' => 'integer',
                            'description' => 'Filter by host record ID the FAQ items are attached to.',
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
                        'search' => [
                            'type' => 'string',
                            'description' => 'Search term matched against the question text.',
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
                'title' => 'List FAQs',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_faq' => [
                'module' => 'mod_faq',
                'role' => 'list',
                'domain' => 'faq',
                'handler' => 'getFaq',
                'description' => 'Get a single FAQ record with all fields, including the full answer.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'FAQ record ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Get FAQ',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_faq' => [
                'module' => 'mod_faq',
                'role' => 'add',
                'domain' => 'faq',
                'handler' => 'createFaq',
                'description' => 'Create a FAQ record attached to a host module record. The host module must be registered in mod_faq_mod and the host record must exist. Questions are unique per host entity and language (case-insensitive); each entity and language accepts at most 25 questions. Editing a record owned by another admin requires the moderate_edit role.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'mod_name' => [
                            'type' => 'string',
                            'description' => 'Host module name that owns the entity (must be registered in mod_faq_mod, e.g. mod_page).',
                        ],
                        'vid' => [
                            'type' => 'integer',
                            'description' => 'ID of the existing host module record the FAQ is attached to.',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Language code of an active site language (e.g. ku, en, tr).',
                        ],
                        'question' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Question text (1-255 characters, unique per entity and language).',
                        ],
                        'answer' => [
                            'type' => 'string',
                            'maxLength' => 5000,
                            'description' => 'Answer text (1-5000 characters).',
                        ],
                        'sort_order' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'description' => 'Display order among the entity\'s FAQ items. Defaults to the last position.',
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
                        'created_at' => [
                            'type' => 'string',
                            'description' => 'Creation datetime (YYYY-MM-DD HH:MM:SS). Defaults to now.',
                        ],
                    ],
                    'required' => [
                        'mod_name',
                        'vid',
                        'lang',
                        'question',
                        'answer',
                    ],
                ],
                'title' => 'Create FAQ',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_faq' => [
                'module' => 'mod_faq',
                'role' => 'edit',
                'domain' => 'faq',
                'handler' => 'updateFaq',
                'description' => 'Partially update a FAQ record. Only provided fields change. The host entity (mod_name, vid) is immutable. Questions must remain unique per host entity and language (case-insensitive). Editing a record owned by another admin requires the moderate_edit role.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'FAQ record ID.',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'New language code of an active site language (e.g. ku, en, tr).',
                        ],
                        'question' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New question text (1-255 characters, unique per entity and language).',
                        ],
                        'answer' => [
                            'type' => 'string',
                            'maxLength' => 5000,
                            'description' => 'New answer text (1-5000 characters).',
                        ],
                        'sort_order' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'description' => 'New display order among the entity\'s FAQ items.',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'New publish status: y (published) or n (draft).',
                        ],
                        'created_at' => [
                            'type' => 'string',
                            'description' => 'New creation datetime (YYYY-MM-DD HH:MM:SS).',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Update FAQ',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_faq' => [
                'module' => 'mod_faq',
                'role' => 'delete',
                'domain' => 'faq',
                'handler' => 'deleteFaq',
                'description' => 'Delete a FAQ record. Deleting a record owned by another admin requires the moderate_delete role. No bridge relations exist between FAQ records and the host modules, so no cleanup is required.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'FAQ record ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Delete FAQ',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
        ];
    }

    private const FAQ_MODULE = 'mod_faq';
    private const FAQ_MAX_PER_ENTITY = 25;
    private const FAQ_QUESTION_MAX = 255;
    private const FAQ_ANSWER_MAX = 5000;

    /**
     * Lists FAQ records with optional filters
     */
    public function listFaqs(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel(self::FAQ_MODULE);
        $model->select('id, lang, mod_name, vid, question, sort_order, status, created_at, updated_at');
        $this->applyFaqFilters($model, $args);
        $model->order('sort_order', 'ASC')->order('id', 'ASC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel(self::FAQ_MODULE);
        $countModel->select('id');
        $this->applyFaqFilters($countModel, $args);
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
     * Returns a single FAQ record with all fields
     */
    public function getFaq(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadFaqById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("FAQ record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a FAQ record attached to a registered entity (mirrors the admin ajax_save flow)
     */
    public function createFaq(array $args): array
    {
        $modName = trim((string) ($args['mod_name'] ?? ''));
        $vid = (int) ($args['vid'] ?? 0);
        $this->assertEntity($modName, $vid);

        $lang = $this->requireActiveLang($args);

        $question = trim((string) ($args['question'] ?? ''));
        if ($question === '' || mb_strlen($question, 'UTF-8') > self::FAQ_QUESTION_MAX) {
            throw new McpToolException("Field 'question' is required (1-" . self::FAQ_QUESTION_MAX . " characters).");
        }

        $answer = trim((string) ($args['answer'] ?? ''));
        if ($answer === '' || mb_strlen($answer, 'UTF-8') > self::FAQ_ANSWER_MAX) {
            throw new McpToolException("Field 'answer' is required (1-" . self::FAQ_ANSWER_MAX . " characters).");
        }
        $this->assertContentMatchesLang($lang, $question, '', $answer);

        $this->assertQuestionUnique($modName, (int) $vid, $lang, $question, 0);
        $this->assertEntityCeiling($modName, (int) $vid, $lang);

        $model = $this->freshModel(self::FAQ_MODULE);
        $model->lang = $lang;
        $model->mod_name = $modName;
        $model->vid = $vid;
        $model->question = $question;
        $model->answer = $answer;
        $model->sort_order = isset($args['sort_order']) ? (int) $args['sort_order'] : $this->nextEntitySortOrder($modName, $vid, $lang);
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        $model->user_id = $this->actingUserId;
        $model->created_at = $this->optionalDateTime($args, 'created_at', date('Y-m-d H:i:s'));

        if (!$model->save(true)) {
            throw new McpToolException('Failed to create the FAQ record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getFaq(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a FAQ record (mirrors the admin ajax_save edit flow)
     */
    public function updateFaq(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadFaqById($id);

        $this->assertModerateEdit(self::FAQ_MODULE, (int) $model->user_id);

        if (isset($args['mod_name']) && trim((string) $args['mod_name']) !== (string) $model->mod_name) {
            throw new McpToolException('The mod_name of an existing FAQ record cannot be changed.');
        }

        if (isset($args['vid']) && (int) $args['vid'] !== (int) $model->vid) {
            throw new McpToolException('The vid of an existing FAQ record cannot be changed.');
        }

        $modName = (string) $model->mod_name;
        $vid = (int) $model->vid;

        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;

        if (isset($args['question'])) {
            $question = trim((string) $args['question']);
            if ($question === '' || mb_strlen($question, 'UTF-8') > self::FAQ_QUESTION_MAX) {
                throw new McpToolException("Field 'question' must be 1-" . self::FAQ_QUESTION_MAX . " characters.");
            }
            $this->assertQuestionUnique($modName, $vid, $lang, $question, $id);
            $model->question = $question;
        }

        if (isset($args['answer'])) {
            $answer = trim((string) $args['answer']);
            if ($answer === '' || mb_strlen($answer, 'UTF-8') > self::FAQ_ANSWER_MAX) {
                throw new McpToolException("Field 'answer' must be 1-" . self::FAQ_ANSWER_MAX . " characters.");
            }
            $model->answer = $answer;
        }

        if (isset($args['sort_order'])) {
            $model->sort_order = (int) $args['sort_order'];
        }

        if (isset($args['status'])) {
            $model->status = $this->optionalEnum($args, 'status', (string) $model->status, ['y', 'n']);
        }

        if (isset($args['created_at'])) {
            $model->created_at = $this->optionalDateTime($args, 'created_at', (string) $model->created_at);
        }

        $model->lang = $lang;
        $this->assertContentMatchesLang($lang, (string) $model->question, '', (string) $model->answer);

        $model->updated_at = date('Y-m-d H:i:s');

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the FAQ record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getFaq(['id' => $id])['item']
        ];
    }

    /**
     * Deletes a FAQ record with admin parity cleanup
     */
    public function deleteFaq(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadFaqById($id);

        $this->assertModerateDelete(self::FAQ_MODULE, (int) $model->user_id);

        if (!$model->delete()) {
            throw new McpToolException('Failed to delete the FAQ record.');
        }

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // FAQ helpers
    // ------------------------------------------------------------------

    private function loadFaqById(int $id): mod_faq_model
    {
        $model = $this->freshModel(self::FAQ_MODULE);
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("FAQ record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyFaqFilters($model, array $args): void
    {
        if (isset($args['mod_name']) && trim((string) $args['mod_name']) !== '') {
            $model->where_mod_name(trim((string) $args['mod_name']));
        }
        if (isset($args['vid'])) {
            $model->where_vid((int) $args['vid']);
        }
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
        }
        if (isset($args['status']) && in_array($args['status'], ['y', 'n'], true)) {
            $model->where_status($args['status']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $model->where('question LIKE \'%' . Obuilder::cc(trim((string) $args['search'])) . '%\'');
        }
    }

    /**
     * Validates the FAQ entity target exactly like the admin _check_mod flow
     */
    private function assertEntity(string $modName, int $vid): void
    {
        if ($modName === '') {
            throw new McpToolException("Field 'mod_name' is required.");
        }
        if ($vid <= 0) {
            throw new McpToolException("Field 'vid' is required and must be a positive integer.");
        }

        $bridgeModel = $this->freshModel('mod_faq_mod');
        if ($bridgeModel->exists_mod_name($modName) !== true) {
            throw new McpToolException("Module '{$modName}' is not registered for FAQ.");
        }

        $entityModel = $this->freshModel($modName);
        if ($entityModel->exists_id($vid) !== true) {
            throw new McpToolException("Entity record '{$vid}' of module '{$modName}' not found.");
        }
    }

    /**
     * Returns the next sort order for a FAQ entity (appends at the end)
     */
    private function nextEntitySortOrder(string $modName, int $vid, string $lang): int
    {
        $model = $this->freshModel(self::FAQ_MODULE);
        $model->select('sort_order');
        $model->where_mod_name_and_vid_and_lang($modName, $vid, $lang);
        $model->order('sort_order', 'DESC')->limit(1)->getResult(true, true);
        if ($model->getSize() === 0) {
            return 1;
        }
        $model->sync();
        return (int) $model->sort_order + 1;
    }

    private function assertQuestionUnique(string $modName, int $vid, string $lang, string $question, int $excludeId): void
    {
        $normalized = mb_strtolower($question, 'UTF-8');

        $model = $this->freshModel(self::FAQ_MODULE);
        $model->where_mod_name_and_vid_and_lang($modName, $vid, $lang)->getResult(true, true);
        if ($model->getSize() === 0) {
            return;
        }

        foreach ($model->toArray() as $existing) {
            if ($excludeId > 0 && (int) $existing['id'] === $excludeId) {
                continue;
            }
            if (mb_strtolower(trim((string) $existing['question']), 'UTF-8') === $normalized) {
                throw new McpToolException('This question has already been added for this entity and language.');
            }
        }
    }

    private function assertEntityCeiling(string $modName, int $vid, string $lang): void
    {
        $model = $this->freshModel(self::FAQ_MODULE);
        $model->select('id');
        $model->where_mod_name_and_vid_and_lang($modName, $vid, $lang)->getResult(true, true);
        if ($model->getSize() >= self::FAQ_MAX_PER_ENTITY) {
            throw new McpToolException('Maximum limit of ' . self::FAQ_MAX_PER_ENTITY . ' questions reached for this entity and language.');
        }
    }
}
