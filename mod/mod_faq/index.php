<?php

/**
 * mod_faq controller
 * Modernized version extending BaseModuleController
 * 
 * Amed Framework
 */
class mod_faq_mod extends BaseModuleController
{
    public $mlang;
    public $mod_faq_model;
    public $r;

    public function __construct()
    {
        parent::__construct('mod_faq');

        if (Req::is_Post('sid')) {
            Registry::add('session_sid', Req::Post_Safe('sid'));
        }
    }

    public function index()
    {
        $this->list();
    }

    private function _resolve_record_title($record, $configured_value = null)
    {
        if (!is_array($record) || $record === [] || $configured_value === null || $configured_value === '') {
            return false;
        }

        if (!array_key_exists($configured_value, $record)) {
            return false;
        }

        $value = trim((string) $record[$configured_value]);
        if ($value === '') {
            return false;
        }

        if ($configured_value === 'id' || substr($configured_value, -3) === '_id') {
            return false;
        }

        return $value;
    }

    public function _check_mod($mod, $vid)
    {
        if ($mod === null || $mod === '') {
            $msg = isset($this->lang['mod_faq']['errors']['missing_module_name']) ? $this->lang['mod_faq']['errors']['missing_module_name'] : 'Missing module name.';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['status' => 'error', 'message' => $msg]);
            }
            exit($msg);
        }

        if ($vid === null || $vid === '') {
            $msg = isset($this->lang['mod_faq']['errors']['missing_record_id']) ? $this->lang['mod_faq']['errors']['missing_record_id'] : 'Missing record ID.';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['status' => 'error', 'message' => $msg]);
            }
            exit($msg);
        }

        $this->mod = Load::model('mod_faq_mod');
        if ($this->mod->exists_mod_name($mod) === false) {
            $msg = isset($this->lang['mod_faq']['errors']['invalid_module']) ? $this->lang['mod_faq']['errors']['invalid_module'] : 'Invalid module for FAQ.';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['status' => 'error', 'message' => $msg]);
            }
            exit($msg);
        }

        $this->mod->getResultOne();
        $this->mod->sync();

        $this->mlang = Load::lang($mod);

        $has_permission = (
            Role::hasPermission($mod, 'faq') ||
            Role::hasPermission($mod, 'edit') ||
            Role::hasPermission('mod_faq', 'list') ||
            Role::hasPermission('mod_faq', 'edit')
        );

        if (!$has_permission) {
            $msg = isset($this->lang['mod_faq']['role_errors']['faq']) ? $this->lang['mod_faq']['role_errors']['faq'] : 'Permission denied to manage FAQs.';
            if ($this->isAjaxRequest() || Req::is_Ajax() || (isset($_GET['ajax']) && $_GET['ajax'] == 1)) {
                $this->jsonResponse(['status' => 'error', 'message' => $msg]);
                return false;
            }
            exit($msg);
        }

        $this->model = Load::model($mod);
        if ($this->model->exists_id($vid) === false) {
            $msg = isset($this->lang['mod_faq']['errors']['invalid_record']) ? $this->lang['mod_faq']['errors']['invalid_record'] : 'Invalid record.';
            if ($this->isAjaxRequest() || Req::is_Ajax() || (isset($_GET['ajax']) && $_GET['ajax'] == 1)) {
                $this->jsonResponse(['status' => 'error', 'message' => $msg]);
                return false;
            }
            Helper::redirect($this->site['address'] . $mod . '/list', 1);
            print($msg);
            return false;
        }

        $this->model->getResultOne();
        $this->model->sync();

        $this->theme->set('key', $this->mod->mod_key);
        $this->theme->set('value', $this->mod->mod_value);
        $this->theme->set('mod_name', $mod);

        $record = $this->model->toArray();
        $record_title = $this->_resolve_record_title($record, $this->mod->mod_value);
        if ($record_title === false) {
            $record_title = isset($record['name']) ? $record['name'] : (isset($record['title']) ? $record['title'] : 'Item #' . $vid);
        }

        $this->theme->set('d', $record);
        $this->theme->set('record_title', $record_title);
        $this->theme->set('vid', (int)$vid);

        return true;
    }

    public function list($mod = null, $vid = null)
    {
        $mod = Req::Url_Clear($mod);
        $vid = Req::Url_Clear($vid);

        if ($mod === '') {
            $mod = null;
        }
        if ($vid === '') {
            $vid = null;
        }

        if ($mod === null && $vid === null) {
            if (!Role::hasPermission('mod_faq', 'list')) {
                $msg = isset($this->lang['mod_faq']['role_errors']['list']) ? $this->lang['mod_faq']['role_errors']['list'] : 'Permission denied.';
                if ($this->isAjaxRequest() || Req::is_Ajax() || (isset($_GET['ajax']) && $_GET['ajax'] == 1)) {
                    $this->jsonResponse(['status' => 'error', 'message' => $msg]);
                    return false;
                }
                return false;
            }

            Run::import('management', '_index');
            $this->site['title'] = isset($this->lang['mod_faq']['admin']['faq_management']) ? $this->lang['mod_faq']['admin']['faq_management'] : 'FAQ Management';
            $this->theme->set('site', $this->site);
            $this->theme->set('is_global_list', true);

            $this->mod_faq_model = Load::model('mod_faq');
            $this->mod_faq_model->flush();
            $this->mod_faq_model->select('id,lang,mod_name,vid,question,answer,sort_order,status,user_id,created_at,updated_at')->order('id', 'DESC')->gR();

            if ($this->mod_faq_model->getSize() !== 0) {
                $this->theme->set('faqs', $this->mod_faq_model->toArray());
            } else {
                $this->theme->set('faqs', []);
            }

            $this->theme->tpl('mod_faq/list');
            return true;
        }

        if ($this->_check_mod($mod, $vid) === false) {
            return false;
        }

        $isAjax = Req::is_Ajax() || $this->isAjaxRequest() || (isset($_GET['ajax']) && $_GET['ajax'] == 1);
        $this->theme->set('is_ajax', $isAjax);

        Run::import('management', '_index');
        $this->site['title'] = isset($this->lang['mod_faq']['admin']['faq_management']) ? $this->lang['mod_faq']['admin']['faq_management'] : 'FAQ Management';
        $this->theme->set('site', $this->site);
        $this->theme->set('is_global_list', false);

        $referer = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';
        $current_url = 'mod_faq/list/' . $mod . '/' . $vid;
        if ($referer !== '' && strpos($referer, $this->site['saddress']) === 0 && strpos($referer, $current_url) === false) {
            $back_url = $referer;
        } else {
            $back_url = $this->site['saddress'] . $mod . '/list';
        }
        $this->theme->set('back_url', $back_url);

        $this->mod_faq_model = Load::model('mod_faq');
        $this->mod_faq_model->flush();
        $this->mod_faq_model->where_mod_name_and_vid($mod, (int)$vid)->order('sort_order', 'ASC')->order('id', 'ASC')->gR();

        $faqs = ($this->mod_faq_model->getSize() !== 0) ? $this->mod_faq_model->toArray() : [];
        $this->theme->set('faqs', $faqs);
        $this->theme->set('faq_count', count($faqs));
        $this->theme->set('max_allowed', 25);

        $this->theme->tpl('mod_faq/modal_list');
        return true;
    }

    public function ajax_list()
    {
        $mod_name = Req::Post_Safe('mod_name');
        $vid = Req::Post_Safe('vid');
        $lang = Req::Post_Safe('lang');

        if ($this->_check_mod($mod_name, $vid) === false) {
            return;
        }

        $faq_model = Load::model('mod_faq');
        $faq_model->flush();
        if (!empty($lang)) {
            $faq_model->where_mod_name_and_vid_and_lang($mod_name, (int)$vid, $lang);
        } else {
            $faq_model->where_mod_name_and_vid($mod_name, (int)$vid);
        }

        $faq_model->order('sort_order', 'ASC')->order('id', 'ASC')->gR();
        $items = ($faq_model->getSize() > 0) ? $faq_model->toArray() : [];

        $this->jsonResponse([
            'status' => 'success',
            'data' => $items,
            'count' => count($items),
            'max_allowed' => 25
        ]);
    }

    public function ajax_save()
    {
        $id = (int) Req::Post_Safe('id');
        $mod_name = Req::Post_Safe('mod_name');
        $vid = (int) Req::Post_Safe('vid');
        $lang = Req::Post_Safe('lang');
        $question = trim(Req::Post_Safe('question'));
        $answer = trim(Req::Post_Safe('answer'));
        $sort_order = (int) Req::Post_Safe('sort_order');
        $status = Req::Post_Safe('status');

        if ($status !== 'n') {
            $status = 'y';
        }

        if (empty($lang)) {
            $lang = !empty($this->site['lang']) ? $this->site['lang'] : 'en';
        }

        if ($this->_check_mod($mod_name, $vid) === false) {
            return;
        }

        if ($question === '') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => isset($this->lang['mod_faq']['validation']['question_required']) ? $this->lang['mod_faq']['validation']['question_required'] : 'Question is required.'
            ]);
            return;
        }

        if (mb_strlen($question, 'UTF-8') > 255) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => isset($this->lang['mod_faq']['validation']['question_max']) ? $this->lang['mod_faq']['validation']['question_max'] : 'Question cannot exceed 255 characters.'
            ]);
            return;
        }

        if ($answer === '') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => isset($this->lang['mod_faq']['validation']['answer_required']) ? $this->lang['mod_faq']['validation']['answer_required'] : 'Answer is required.'
            ]);
            return;
        }

        if (mb_strlen($answer, 'UTF-8') > 5000) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => isset($this->lang['mod_faq']['validation']['answer_max']) ? $this->lang['mod_faq']['validation']['answer_max'] : 'Answer cannot exceed 5000 characters.'
            ]);
            return;
        }

        // Duplicate question check for the same entity and language
        $faq_dup_check = Load::model('mod_faq');
        $faq_dup_check->flush();
        $faq_dup_check->where_mod_name_and_vid_and_lang_and_question($mod_name, $vid, $lang, $question);
        if ($id > 0) {
            $faq_dup_check->addWhere("id != " . (int)$id);
        }
        $faq_dup_check->gRo();
        if ($faq_dup_check->getSize() > 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => isset($this->lang['mod_faq']['validation']['question_duplicate']) ? $this->lang['mod_faq']['validation']['question_duplicate'] : 'This question has already been added.'
            ]);
            return;
        }

        // Ceiling enforcement: maximum 25 questions per entity
        if ($id === 0) {
            $faq_check = Load::model('mod_faq');
            $faq_check->where_mod_name_and_vid_and_lang($mod_name, $vid, $lang)->gR();
            if ($faq_check->getSize() >= 25) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => isset($this->lang['mod_faq']['message']['error']['max_limit']) ? $this->lang['mod_faq']['message']['error']['max_limit'] : 'Maximum limit of 25 questions reached.'
                ]);
                return;
            }
        }

        if ($id > 0) {
            $faq_model = Load::model('mod_faq');
            $faq_model->flush();
            $faq_model->where_id($id)->gRo();
            if ($faq_model->getSize() === 0) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => isset($this->lang['mod_faq']['errors']['invalid_faq']) ? $this->lang['mod_faq']['errors']['invalid_faq'] : 'Invalid FAQ record.'
                ]);
                return;
            }

            $faq_model->sync();

            if ($faq_model->mod_name !== $mod_name || (int)$faq_model->vid !== (int)$vid) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => isset($this->lang['mod_faq']['errors']['invalid_faq']) ? $this->lang['mod_faq']['errors']['invalid_faq'] : 'Invalid FAQ record.'
                ]);
                return;
            }

            $currentUserId = (int)$this->session->get('id');
            $isOwner = ((int)$faq_model->user_id === $currentUserId);
            $canModerate = Role::hasPermission('mod_faq', 'moderate_edit') || Role::hasPermission($mod_name, 'moderate_edit');
            $canEdit = Role::hasPermission('mod_faq', 'edit') || Role::hasPermission($mod_name, 'edit');

            if (!$canModerate && !($isOwner && $canEdit)) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => isset($this->lang['mod_faq']['role_errors']['edit']) ? $this->lang['mod_faq']['role_errors']['edit'] : 'Permission denied.'
                ]);
                return;
            }

            $faq_model->id = $id;
            $faq_model->question = $question;
            $faq_model->answer = $answer;
            $faq_model->sort_order = $sort_order;
            $faq_model->status = $status;
            if (!empty($lang)) {
                $faq_model->lang = $lang;
            }

            if ($faq_model->update('id')) {
                $this->jsonResponse([
                    'status' => 'success',
                    'message' => isset($this->lang['mod_faq']['message']['success']['edit']) ? $this->lang['mod_faq']['message']['success']['edit'] : 'Question updated successfully.',
                    'item' => [
                        'id' => $id,
                        'lang' => $faq_model->lang,
                        'mod_name' => $mod_name,
                        'vid' => $vid,
                        'question' => $question,
                        'answer' => $answer,
                        'sort_order' => $sort_order,
                        'status' => $status
                    ]
                ]);
            } else {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => isset($this->lang['mod_faq']['message']['error']['edit']) ? $this->lang['mod_faq']['message']['error']['edit'] : 'Failed to update question.'
                ]);
            }
        } else {
            $faq_model = Load::model('mod_faq');
            $faq_model->flush();
            $faq_model->lang = $lang;
            $faq_model->mod_name = $mod_name;
            $faq_model->vid = $vid;
            $faq_model->question = $question;
            $faq_model->answer = $answer;
            $faq_model->sort_order = $sort_order;
            $faq_model->status = $status;
            $faq_model->user_id = (int)$this->session->get('id');

            if ($faq_model->save(true)) {
                $new_id = (int)$faq_model->id;
                $this->jsonResponse([
                    'status' => 'success',
                    'message' => isset($this->lang['mod_faq']['message']['success']['add']) ? $this->lang['mod_faq']['message']['success']['add'] : 'Question added successfully.',
                    'item' => [
                        'id' => $new_id,
                        'lang' => $lang,
                        'mod_name' => $mod_name,
                        'vid' => $vid,
                        'question' => $question,
                        'answer' => $answer,
                        'sort_order' => $sort_order,
                        'status' => $status
                    ]
                ]);
            } else {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => isset($this->lang['mod_faq']['message']['error']['add']) ? $this->lang['mod_faq']['message']['error']['add'] : 'Failed to add question.'
                ]);
            }
        }
    }

    public function ajax_batch_save()
    {
        $mod_name = Req::Post_Safe('mod_name');
        $vid = (int) Req::Post_Safe('vid');
        $lang = Req::Post_Safe('lang');

        if (empty($lang)) {
            $lang = !empty($this->site['lang']) ? $this->site['lang'] : 'en';
        }

        if ($this->_check_mod($mod_name, $vid) === false) {
            return;
        }

        $items_raw = isset($_POST['items']) ? $_POST['items'] : [];
        if (is_string($items_raw)) {
            $items = json_decode($items_raw, true);
            if (!is_array($items)) {
                $items = [];
            }
        } else {
            $items = (array) $items_raw;
        }

        if (count($items) > 25) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => isset($this->lang['mod_faq']['message']['error']['max_limit']) ? $this->lang['mod_faq']['message']['error']['max_limit'] : 'Maximum limit of 25 questions reached.'
            ]);
            return;
        }

        $seen_questions = [];
        foreach ($items as $item) {
            $q = isset($item['question']) ? trim((string)$item['question']) : '';
            $a = isset($item['answer']) ? trim((string)$item['answer']) : '';

            if ($q === '') {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => isset($this->lang['mod_faq']['validation']['question_required']) ? $this->lang['mod_faq']['validation']['question_required'] : 'Question is required.'
                ]);
                return;
            }
            if (mb_strlen($q, 'UTF-8') > 255) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => isset($this->lang['mod_faq']['validation']['question_max']) ? $this->lang['mod_faq']['validation']['question_max'] : 'Question cannot exceed 255 characters.'
                ]);
                return;
            }

            $q_lower = mb_strtolower($q, 'UTF-8');
            if (in_array($q_lower, $seen_questions, true)) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => isset($this->lang['mod_faq']['validation']['question_duplicate']) ? $this->lang['mod_faq']['validation']['question_duplicate'] : 'This question has already been added.'
                ]);
                return;
            }
            $seen_questions[] = $q_lower;

            if ($a === '') {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => isset($this->lang['mod_faq']['validation']['answer_required']) ? $this->lang['mod_faq']['validation']['answer_required'] : 'Answer is required.'
                ]);
                return;
            }
            if (mb_strlen($a, 'UTF-8') > 5000) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => isset($this->lang['mod_faq']['validation']['answer_max']) ? $this->lang['mod_faq']['validation']['answer_max'] : 'Answer cannot exceed 5000 characters.'
                ]);
                return;
            }
        }

        $saved_items = [];
        $currentUserId = (int)$this->session->get('id');
        $canModerate = Role::hasPermission('mod_faq', 'moderate_edit') || Role::hasPermission($mod_name, 'moderate_edit');
        $canEdit = Role::hasPermission('mod_faq', 'edit') || Role::hasPermission($mod_name, 'edit');

        foreach ($items as $index => $item) {
            $item_id = isset($item['id']) ? (int)$item['id'] : 0;
            $q = trim((string)$item['question']);
            $a = trim((string)$item['answer']);
            $sort_order = isset($item['sort_order']) ? (int)$item['sort_order'] : $index;
            $status = (isset($item['status']) && $item['status'] === 'n') ? 'n' : 'y';

            if ($item_id > 0) {
                $faq_model = Load::model('mod_faq');
                $faq_model->flush();
                $faq_model->where_id($item_id)->gRo();
                if ($faq_model->getSize() === 0) {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => isset($this->lang['mod_faq']['errors']['invalid_faq']) ? $this->lang['mod_faq']['errors']['invalid_faq'] : 'Invalid FAQ record.'
                    ]);
                    return;
                }

                $faq_model->sync();

                if ($faq_model->mod_name !== $mod_name || (int)$faq_model->vid !== (int)$vid) {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => isset($this->lang['mod_faq']['errors']['invalid_faq']) ? $this->lang['mod_faq']['errors']['invalid_faq'] : 'Invalid FAQ record.'
                    ]);
                    return;
                }

                $isOwner = ((int)$faq_model->user_id === $currentUserId);
                if (!$canModerate && !($isOwner && $canEdit)) {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => isset($this->lang['mod_faq']['role_errors']['edit']) ? $this->lang['mod_faq']['role_errors']['edit'] : 'Permission denied.'
                    ]);
                    return;
                }

                $faq_model->id = $item_id;
                $faq_model->question = $q;
                $faq_model->answer = $a;
                $faq_model->sort_order = $sort_order;
                $faq_model->status = $status;
                if (!empty($lang)) {
                    $faq_model->lang = $lang;
                }
                if ($faq_model->update('id')) {
                    $saved_items[] = [
                        'id' => $item_id,
                        'lang' => $lang,
                        'mod_name' => $mod_name,
                        'vid' => $vid,
                        'question' => $q,
                        'answer' => $a,
                        'sort_order' => $sort_order,
                        'status' => $status
                    ];
                } else {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => isset($this->lang['mod_faq']['message']['error']['edit']) ? $this->lang['mod_faq']['message']['error']['edit'] : 'Failed to update question.'
                    ]);
                    return;
                }
            } else {
                $faq_model = Load::model('mod_faq');
                $faq_model->flush();
                $faq_model->lang = $lang;
                $faq_model->mod_name = $mod_name;
                $faq_model->vid = $vid;
                $faq_model->question = $q;
                $faq_model->answer = $a;
                $faq_model->sort_order = $sort_order;
                $faq_model->status = $status;
                $faq_model->user_id = $currentUserId;
                if ($faq_model->save(true)) {
                    $assigned_id = (int)$faq_model->id;
                    $saved_items[] = [
                        'id' => $assigned_id,
                        'lang' => $lang,
                        'mod_name' => $mod_name,
                        'vid' => $vid,
                        'question' => $q,
                        'answer' => $a,
                        'sort_order' => $sort_order,
                        'status' => $status
                    ];
                } else {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => isset($this->lang['mod_faq']['message']['error']['add']) ? $this->lang['mod_faq']['message']['error']['add'] : 'Failed to add question.'
                    ]);
                    return;
                }
            }
        }

        $this->jsonResponse([
            'status' => 'success',
            'message' => isset($this->lang['mod_faq']['message']['success']['batch']) ? $this->lang['mod_faq']['message']['success']['batch'] : 'All questions saved successfully.',
            'count' => count($saved_items),
            'items' => $saved_items
        ]);
    }

    public function ajax_delete()
    {
        $id = (int) Req::Post_Safe('id');
        if ($id <= 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => isset($this->lang['mod_faq']['errors']['invalid_faq']) ? $this->lang['mod_faq']['errors']['invalid_faq'] : 'Invalid FAQ ID.'
            ]);
            return;
        }

        $faq_model = Load::model('mod_faq');
        $faq_model->flush();
        $faq_model->where_id($id)->gRo();

        if ($faq_model->getSize() === 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => isset($this->lang['mod_faq']['errors']['invalid_faq']) ? $this->lang['mod_faq']['errors']['invalid_faq'] : 'Invalid FAQ record.'
            ]);
            return;
        }

        $faq_model->sync();

        $currentUserId = (int)$this->session->get('id');
        $isOwner = ((int)$faq_model->user_id === $currentUserId);
        $canModerateDelete = Role::hasPermission('mod_faq', 'moderate_delete') || Role::hasPermission($faq_model->mod_name, 'moderate_delete');
        $canDelete = Role::hasPermission('mod_faq', 'delete') || Role::hasPermission($faq_model->mod_name, 'delete');

        if (!$canModerateDelete && !($isOwner && $canDelete)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => isset($this->lang['mod_faq']['role_errors']['delete']) ? $this->lang['mod_faq']['role_errors']['delete'] : 'Permission denied to delete FAQ.'
            ]);
            return;
        }

        if ($faq_model->delete()) {
            $this->jsonResponse([
                'status' => 'success',
                'message' => isset($this->lang['mod_faq']['message']['success']['delete']) ? $this->lang['mod_faq']['message']['success']['delete'] : 'Question deleted successfully.',
                'id' => $id
            ]);
        } else {
            $this->jsonResponse([
                'status' => 'error',
                'message' => isset($this->lang['mod_faq']['message']['error']['delete']) ? $this->lang['mod_faq']['message']['error']['delete'] : 'Failed to delete question.'
            ]);
        }
    }

    public function ajax_reorder()
    {
        $mod_name = Req::Post_Safe('mod_name');
        $vid = (int) Req::Post_Safe('vid');

        if ($this->_check_mod($mod_name, $vid) === false) {
            return;
        }

        $order_raw = isset($_POST['order']) ? $_POST['order'] : [];
        if (is_string($order_raw)) {
            $order = json_decode($order_raw, true);
            if (!is_array($order)) {
                $order = [];
            }
        } else {
            $order = (array) $order_raw;
        }

        foreach ($order as $index => $faq_id) {
            $faq_id = (int) $faq_id;
            if ($faq_id > 0) {
                $item_model = Load::model('mod_faq');
                $item_model->flush();
                $item_model->id = $faq_id;
                $item_model->sort_order = $index;
                $item_model->update('id');
            }
        }

        $this->jsonResponse([
            'status' => 'success',
            'message' => isset($this->lang['mod_faq']['message']['success']['reorder']) ? $this->lang['mod_faq']['message']['success']['reorder'] : 'Sort order updated successfully.'
        ]);
    }

    public function ajax_toggle_status()
    {
        $id = (int) Req::Post_Safe('id');
        $status = Req::Post_Safe('status');

        if ($id <= 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => isset($this->lang['mod_faq']['errors']['invalid_faq']) ? $this->lang['mod_faq']['errors']['invalid_faq'] : 'Invalid FAQ ID.'
            ]);
            return;
        }

        $faq_model = Load::model('mod_faq');
        $faq_model->flush();
        $faq_model->where_id($id)->gRo();

        if ($faq_model->getSize() === 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => isset($this->lang['mod_faq']['errors']['invalid_faq']) ? $this->lang['mod_faq']['errors']['invalid_faq'] : 'Invalid FAQ record.'
            ]);
            return;
        }

        $faq_model->sync();

        $currentUserId = (int)$this->session->get('id');
        $isOwner = ((int)$faq_model->user_id === $currentUserId);
        $canModerate = Role::hasPermission('mod_faq', 'moderate_edit') || Role::hasPermission($faq_model->mod_name, 'moderate_edit');
        $canEdit = Role::hasPermission('mod_faq', 'edit') || Role::hasPermission($faq_model->mod_name, 'edit');

        if (!$canModerate && !($isOwner && $canEdit)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => isset($this->lang['mod_faq']['role_errors']['edit']) ? $this->lang['mod_faq']['role_errors']['edit'] : 'Permission denied.'
            ]);
            return;
        }

        if ($status !== 'y' && $status !== 'n') {
            $status = ($faq_model->status === 'y') ? 'n' : 'y';
        }

        $faq_model->id = $id;
        $faq_model->status = $status;

        if ($faq_model->update('id')) {
            $this->jsonResponse([
                'status' => 'success',
                'message' => isset($this->lang['mod_faq']['message']['success']['status']) ? $this->lang['mod_faq']['message']['success']['status'] : 'Status updated successfully.',
                'new_status' => $status
            ]);
        } else {
            $this->jsonResponse([
                'status' => 'error',
                'message' => isset($this->lang['mod_faq']['message']['error']['status']) ? $this->lang['mod_faq']['message']['error']['status'] : 'Failed to update status.'
            ]);
        }
    }

    public function _remove($mod, $vid = null)
    {
        $this->r = Load::model('mod_faq');
        $this->r->flush();

        if ($vid === null) {
            $this->r->where_mod_name($mod)->gR();
        } else {
            $this->r->where_mod_name_and_vid($mod, (int)$vid)->gR();
        }

        if ($this->r->getSize() === 0) {
            return true;
        }

        return $this->r->delete();
    }
}
