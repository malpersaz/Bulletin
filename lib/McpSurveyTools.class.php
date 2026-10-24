<?php
/**
 * Amed Framework
 * MCP Survey Tools - CRUD implementations for mod_survey (polls) with voting options
 * lib/McpSurveyTools.class.php
 */

class McpSurveyTools extends McpCrudToolsBase
{
    // ------------------------------------------------------------------
    // Tool definitions
    // ------------------------------------------------------------------

    public static function definitions(): array
    {
        return [
            'list_surveys' => [
                'module' => 'mod_survey',
                'role' => 'list',
                'domain' => 'survey',
                'handler' => 'listSurveys',
                'description' => 'List surveys (polls) with optional filters. Returns paginated items (id, lang, question, status, acl_id, user_id) plus the total count. Status \'y\' marks the active default poll of its language; only one survey per language can be active at a time.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Filter by language code (for example \'en\').'
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                            'description' => 'Filter by status: \'y\' active default poll, \'n\' inactive.'
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'Case-insensitive substring search in the question text.'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 50,
                            'description' => 'Number of records to return (default 10, maximum 50).'
                        ],
                        'offset' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'description' => 'Number of records to skip for pagination (default 0).'
                        ]
                    ]
                ],
                'title' => 'List Surveys',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_survey' => [
                'module' => 'mod_survey',
                'role' => 'list',
                'domain' => 'survey',
                'handler' => 'getSurvey',
                'description' => 'Get a single survey (poll) record with all fields plus its voting options (id, option_text, hit) ordered by option id.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'description' => 'The survey record id.'
                        ]
                    ],
                    'required' => ['id']
                ],
                'title' => 'Get Survey',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_survey' => [
                'module' => 'mod_survey',
                'role' => 'add',
                'domain' => 'survey',
                'handler' => 'createSurvey',
                'description' => 'Create a survey (poll) record. Required: question (2-255 characters), an active language code, and at least two voting options. Status defaults to \'y\' (active default poll of the language) and acl_id defaults to 0 (no access restriction).',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'question' => [
                            'type' => 'string',
                            'minLength' => 2,
                            'maxLength' => 255,
                            'description' => 'The poll question shown to visitors.'
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Language code of an active site language (for example \'en\').'
                        ],
                        'options' => [
                            'type' => 'array',
                            'minItems' => 2,
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'option_text' => [
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 255,
                                        'description' => 'The voting option label.'
                                    ]
                                ],
                                'required' => ['option_text']
                            ],
                            'description' => 'At least two voting options for the poll. Vote counters start at zero.'
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                            'description' => '\'y\' makes this the active default poll of the language, \'n\' keeps it inactive. Default \'y\'.'
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'description' => 'Access control list id restricting visibility; 0 means no restriction. Default 0.'
                        ]
                    ],
                    'required' => ['question', 'lang', 'options']
                ],
                'title' => 'Create Survey',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_survey' => [
                'module' => 'mod_survey',
                'role' => 'edit',
                'domain' => 'survey',
                'handler' => 'updateSurvey',
                'description' => 'Partially update a survey (poll) record. Only provided fields change. When \'options\' is provided it replaces the full option set like the admin edit form: entries with an existing option id update the label and keep their vote counters, entries without an id are appended as new options, and omitted options are deleted. Editing a record owned by another admin requires the moderate_edit role.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'description' => 'The survey record id.'
                        ],
                        'question' => [
                            'type' => 'string',
                            'minLength' => 2,
                            'maxLength' => 255,
                            'description' => 'The poll question shown to visitors.'
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Language code of an active site language (for example \'en\').'
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['y', 'n'],
                            'description' => '\'y\' marks the survey as an active default poll candidate, \'n\' inactive. Use set_default_survey to activate one poll per language exclusively.'
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'description' => 'Access control list id restricting visibility; 0 means no restriction.'
                        ],
                        'options' => [
                            'type' => 'array',
                            'minItems' => 2,
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => [
                                        'type' => 'integer',
                                        'minimum' => 1,
                                        'description' => 'Existing option id to update (vote counters preserved). Omit to append a new option.'
                                    ],
                                    'option_text' => [
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 255,
                                        'description' => 'The voting option label.'
                                    ]
                                ],
                                'required' => ['option_text']
                            ],
                            'description' => 'Replacement option set (at least two options). Omit the field entirely to leave options untouched.'
                        ]
                    ],
                    'required' => ['id']
                ],
                'title' => 'Update Survey',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_survey' => [
                'module' => 'mod_survey',
                'role' => 'delete',
                'domain' => 'survey',
                'handler' => 'deleteSurvey',
                'description' => 'Delete a survey (poll) record. Its voting options and recorded voter IP entries are cleaned up automatically. Deleting a record owned by another admin requires the moderate_delete role.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'description' => 'The survey record id.'
                        ]
                    ],
                    'required' => ['id']
                ],
                'title' => 'Delete Survey',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
            'set_default_survey' => [
                'module' => 'mod_survey',
                'role' => 'moderate_edit',
                'domain' => 'survey',
                'handler' => 'setDefaultSurvey',
                'description' => 'Set one survey as the active default poll of its language (mirrors the admin set_default action): all other surveys of the same language are deactivated (status \'n\') and the selected survey is activated (status \'y\'). Requires the moderate_edit role.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'description' => 'The survey record id to activate.'
                        ]
                    ],
                    'required' => ['id']
                ],
                'title' => 'Set Default Survey',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
        ];
    }

    // ------------------------------------------------------------------
    // mod_survey tools
    // ------------------------------------------------------------------

    /**
     * Lists surveys (polls) with optional filters
     */
    public function listSurveys(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_survey');
        $model->select('id, lang, question, status, acl_id, user_id');
        $this->applySurveyFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_survey');
        $countModel->select('id');
        $this->applySurveyFilters($countModel, $args);
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
     * Returns a single survey record with all fields and its voting options
     */
    public function getSurvey(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadRecordById('mod_survey', $id, 'Survey');

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Survey record '{$id}' not found.");
        }
        $row['options'] = $this->fetchSurveyOptions($id);

        return ['item' => $row];
    }

    /**
     * Creates a survey (poll) record with its voting options (mirrors the admin add form)
     */
    public function createSurvey(array $args): array
    {
        $question = trim((string) ($args['question'] ?? ''));
        if (mb_strlen($question) < 2 || mb_strlen($question) > 255) {
            throw new McpToolException("Field 'question' is required (2-255 characters).");
        }

        $lang = $this->requireActiveLang($args);
        $options = $this->parseSurveyOptions($args['options'] ?? null);
        $this->assertContentMatchesLang($lang, ['question' => $question]);

        $model = $this->freshModel('mod_survey');
        $model->lang = $lang;
        $model->question = $question;
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $model->user_id = $this->actingUserId;

        if (!$model->save(true)) {
            throw new McpToolException('Failed to create the survey record.');
        }

        $surveyId = (int) $model->id;
        $this->insertSurveyOptions($surveyId, $options);

        return [
            'created' => true,
            'id' => $surveyId,
            'item' => $this->getSurvey(['id' => $surveyId])['item']
        ];
    }

    /**
     * Partially updates a survey record. When 'options' is provided it replaces
     * the full option set with the same semantics as the admin edit form:
     * entries with an existing id are updated (votes preserved), entries without
     * an id are appended, and omitted options are deleted.
     */
    public function updateSurvey(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadRecordById('mod_survey', $id, 'Survey');

        $this->assertModerateEdit('mod_survey', (int) $model->user_id);

        if (isset($args['lang'])) {
            $model->lang = $this->requireActiveLang($args);
        }

        if (isset($args['question'])) {
            $question = trim((string) $args['question']);
            if (mb_strlen($question) < 2 || mb_strlen($question) > 255) {
                throw new McpToolException("Field 'question' must be 2-255 characters.");
            }
            $model->question = $question;
        }

        $this->assertContentMatchesLang((string) $model->lang, ['question' => (string) $model->question]);

        if (isset($args['status'])) {
            $model->status = $this->optionalEnum($args, 'status', (string) $model->status, ['y', 'n']);
        }

        if (isset($args['acl_id'])) {
            $aclId = (int) $args['acl_id'];
            if ($aclId < 0) {
                throw new McpToolException("Field 'acl_id' must be a non-negative integer.");
            }
            $model->acl_id = $aclId;
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the survey record.');
        }

        if (array_key_exists('options', $args)) {
            $this->replaceSurveyOptions($id, $args['options']);
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getSurvey(['id' => $id])['item']
        ];
    }

    /**
     * Deletes a survey record with admin parity cleanup of options and voter IPs
     */
    public function deleteSurvey(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadRecordById('mod_survey', $id, 'Survey');

        $this->assertModerateDelete('mod_survey', (int) $model->user_id);

        $optionsModel = $this->freshModel('mod_survey.surveyselect');
        $optionsModel->where_survey_id($id)->delete();

        $ipModel = $this->freshModel('mod_survey.surveyip');
        $ipModel->where_survey_id($id)->delete();

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    /**
     * Sets one survey as the active poll of its language (mirrors the admin
     * set_default action): all other surveys of the same language are set to
     * status 'n' and the selected survey is set to status 'y'.
     */
    public function setDefaultSurvey(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadRecordById('mod_survey', $id, 'Survey');

        $this->assertPermission('mod_survey', 'moderate_edit');

        $db = Load::lib('Database');
        $db->query("UPDATE `mod_survey` SET `status` = 'n' WHERE `lang` = '" . Obuilder::cc((string) $model->lang) . "'");

        $model->status = 'y';
        $model->id = $id;
        if (!$model->update('id')) {
            throw new McpToolException('Failed to set the survey as the default poll.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getSurvey(['id' => $id])['item']
        ];
    }

    // ------------------------------------------------------------------
    // Survey helpers
    // ------------------------------------------------------------------

    /**
     * Validates and normalizes the options payload of a survey.
     * Each entry is an object with an 'option_text' (1-255 characters) and an
     * optional 'id' referencing an existing option row. The admin form requires
     * a minimum of two options.
     */
    private function parseSurveyOptions($raw): array
    {
        if (!is_array($raw)) {
            throw new McpToolException("Field 'options' is required and must be an array of option objects.");
        }

        $options = [];
        foreach ($raw as $entry) {
            if (!is_array($entry) || !isset($entry['option_text'])) {
                throw new McpToolException("Each entry in 'options' must be an object with an 'option_text' field.");
            }
            $text = trim((string) $entry['option_text']);
            if ($text === '' || mb_strlen($text) > 255) {
                throw new McpToolException("Each 'options' entry must have an 'option_text' of 1-255 characters.");
            }
            $options[] = [
                'id' => isset($entry['id']) ? (int) $entry['id'] : 0,
                'option_text' => $text
            ];
        }

        if (count($options) < 2) {
            throw new McpToolException("Field 'options' must contain at least 2 options (the admin form requires a minimum of two).");
        }

        return $options;
    }

    /**
     * Inserts fresh voting options for a new survey (mirrors the admin afterSave callback)
     */
    private function insertSurveyOptions(int $surveyId, array $options): void
    {
        $optionsModel = $this->freshModel('mod_survey.surveyselect');
        foreach ($options as $option) {
            $optionsModel->flush();
            $optionsModel->survey_id = $surveyId;
            $optionsModel->option_text = $option['option_text'];
            $optionsModel->hit = 0;
            $optionsModel->save(true);
        }
    }

    /**
     * Replaces the option set of a survey (mirrors the admin afterUpdate callback):
     * existing options keep their vote counters, unknown ids are appended as new
     * options, and options omitted from the payload are deleted.
     */
    private function replaceSurveyOptions(int $surveyId, $raw): void
    {
        $options = $this->parseSurveyOptions($raw);

        $optionsModel = $this->freshModel('mod_survey.surveyselect');
        $optionsModel->where_survey_id($surveyId)->getResult(true, true);
        $existing = [];
        if ($optionsModel->getSize() > 0) {
            foreach ($optionsModel->toArray() as $existingOption) {
                $existing[(int) $existingOption['id']] = $existingOption;
            }
        }

        $validIds = [];
        foreach ($options as $option) {
            $optionsModel->flush();
            if ($option['id'] > 0 && isset($existing[$option['id']])) {
                $optionsModel->option_text = $option['option_text'];
                $optionsModel->id = $option['id'];
                $optionsModel->update('id');
                $validIds[] = $option['id'];
            } else {
                $optionsModel->survey_id = $surveyId;
                $optionsModel->option_text = $option['option_text'];
                $optionsModel->hit = 0;
                $optionsModel->save(true);
                $validIds[] = (int) $optionsModel->id;
            }
        }

        $optionsModel->flush();
        if ($validIds !== []) {
            $optionsModel->where_survey_id_and_not_id($surveyId, $validIds)->delete();
        } else {
            $optionsModel->where_survey_id($surveyId)->delete();
        }
    }

    /**
     * Fetches the voting options of a survey ordered by id
     */
    private function fetchSurveyOptions(int $surveyId): array
    {
        $optionsModel = $this->freshModel('mod_survey.surveyselect');
        $optionsModel->select('id, option_text, hit');
        $optionsModel->where_survey_id($surveyId)->order('id', 'ASC')->getResult(true, true);
        $options = $optionsModel->toArray();

        return is_array($options) ? $options : [];
    }

    /**
     * Applies the shared list filters of the survey tools
     */
    private function applySurveyFilters($model, array $args): void
    {
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
}
