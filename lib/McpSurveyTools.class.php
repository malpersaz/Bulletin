<?php
/**
 * Amed Framework
 * MCP Survey Tools - CRUD implementations for mod_survey (polls) with voting options
 * lib/McpSurveyTools.class.php
 */

class McpSurveyTools extends McpCrudToolsBase
{
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
