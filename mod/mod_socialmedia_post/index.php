<?php

/**
 * Controller for `mod_socialmedia_post`.
 *
 * Manages native social post records (X, Facebook, Instagram), provider metadata autofill,
 * mobile card grid JSON, and the host-editor bottom-sheet inline token bridge.
 */
class mod_socialmedia_post_mod extends BaseModuleController
{
    private const THUMB_IMAGE_WIDTH = 1280;
    private const THUMB_PREVIEW_WIDTH = 640;

    public $mod_socialmedia_post_model;

    protected $need_simple_data = [
        'lang' => 'lang_lang',
        'mod_acl' => 'mod_acl_acl_id',
    ];

    protected $special_fields = [
        'url' => ['type' => 'skip'],
        'embed' => ['type' => 'skip'],
        'embed_html' => ['type' => 'skip'],
        'name' => ['type' => 'skip'],
        'handle' => ['type' => 'skip'],
        'content' => ['type' => 'skip'],
        'provider' => ['type' => 'skip'],
        'media_type' => ['type' => 'skip'],
        'post_id' => ['type' => 'skip'],
        'image' => ['type' => 'skip'],
        'created_at' => ['type' => 'skip'],
        'autofill_image' => ['type' => 'skip'],
        'remove_image' => ['type' => 'skip'],
    ];

    public function __construct()
    {
        parent::__construct('mod_socialmedia_post');
        SocialPostProviderService::ensureUtf8mb4Connection($this->db);
    }

    protected function getDatatableColumns()
    {
        return [
            'id',
            'name|bold',
            'provider|badge',
            'media_type|badge',
            'image|image',
            'lang|badge',
            'created_at|datetime',
            'status|badge',
        ];
    }

    protected function prepareFormData($data)
    {
        $selectedLang = $this->site['lang'] ?? 'en';
        if (isset($data['d']) && is_array($data['d'])) {
            if (!empty($data['d']['lang'])) {
                $selectedLang = (string) $data['d']['lang'];
            }
            if (!empty($data['d']['image'])) {
                $data['thumb_url'] = $this->resolveThumbUrl((string) $data['d']['image']);
            }
        }

        if ($this->tableExists('lang')) {
            $safeLang = Req::Clear($selectedLang);
            $this->db->fetch_array("SELECT seo, name, status, is_default FROM lang WHERE status='y' OR seo='" . $safeLang . "' ORDER BY is_default DESC, seo ASC");
            $data['lang_lang'] = $this->db->toArray();
        }

        if ($this->tableExists('mod_acl')) {
            $this->db->fetch_array('SELECT id, name FROM mod_acl ORDER BY id DESC');
            $data['mod_acl_acl_id'] = $this->db->toArray();
        }

        return $data;
    }

    protected function beforeSave($model)
    {
        $langCode = strtolower(trim((string) Req::Post_Safe('lang')));
        if ($langCode === '') {
            $langCode = strtolower((string) ($this->site['lang'] ?? 'en'));
        }
        $model->lang = $langCode;

        $this->populatePostFields($model, $langCode);
        $this->handleThumbnailSave($model, '');

        $statusVal = trim((string) Req::Post_Safe('status'));
        $model->status = in_array($statusVal, ['y', 'n'], true) ? $statusVal : 'n';

        $model->acl_id = max(0, intval(Req::Post_Safe('acl_id') ?: 0));

        $model->user_id = intval($this->session->get('id'));
        $createdAtRaw = trim((string) Req::Post_Safe('created_at'));
        $model->created_at = $createdAtRaw !== '' ? $this->normalizeDateTime($createdAtRaw) : date('Y-m-d H:i:s');

        return $model;
    }

    protected function beforeUpdate($model, $id)
    {
        $existing = Load::model('mod_socialmedia_post');
        $existing->where_id(intval($id))->gRo();
        $oldImage = '';
        $oldCreatedAt = date('Y-m-d H:i:s');
        if ($existing->getSize() > 0) {
            $existing->sync();
            $oldImage = (string) ($existing->image ?? '');
            if (!empty($existing->created_at)) {
                $oldCreatedAt = (string) $existing->created_at;
            }
        }

        $langCode = strtolower(trim((string) Req::Post_Safe('lang')));
        if ($langCode === '') {
            $langCode = strtolower((string) ($existing->lang ?? ($this->site['lang'] ?? 'en')));
        }
        $model->lang = $langCode;

        $this->populatePostFields($model, $langCode);
        $this->handleThumbnailUpdate($model, $oldImage);

        $statusVal = trim((string) Req::Post_Safe('status'));
        if (in_array($statusVal, ['y', 'n'], true)) {
            $model->status = $statusVal;
        }

        if (isset($_POST['acl_id'])) {
            $model->acl_id = max(0, intval(Req::Post_Safe('acl_id') ?: 0));
        }

        $createdAtRaw = trim((string) Req::Post_Safe('created_at'));
        $model->created_at = $createdAtRaw !== '' ? $this->normalizeDateTime($createdAtRaw) : $oldCreatedAt;

        if (isset($model->user_id)) {
            unset($model->user_id);
        }

        return $model;
    }

    protected function beforeDelete($model, $id)
    {
        if (!empty($model->image)) {
            SocialPostProviderService::deleteLocalThumbnail((string) $model->image);
        }

        $postRecordId = intval($id);
        if ($postRecordId > 0 && $this->tableExists('mod_socialmedia_post_link')) {
            $this->db->query('DELETE FROM mod_socialmedia_post_link WHERE socialmedia_post_id=' . $postRecordId);
        }

        return $model;
    }

    public function list()
    {
        if ($this->user->hasPermission('mod_socialmedia_post', 'list', $this->lang['mod_socialmedia_post']['role_errors']['list'], 1) === false) {
            return false;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_socialmedia_post']['list'];
        $this->theme->tpl('mod_socialmedia_post/list');
        $this->theme->set('site', $this->site);

        if ($this->tableExists('lang')) {
            $this->db->fetch_array("SELECT seo, name, status, is_default FROM lang WHERE status='y' ORDER BY is_default DESC, seo ASC");
            $this->theme->set('lang_lang', $this->db->toArray());
        } else {
            $this->theme->set('lang_lang', []);
        }

        $this->theme->set('is_ajax', $this->isAjaxRequest() || (isset($_GET['ajax']) && (string) $_GET['ajax'] === '1'));
    }

    public function grid_json()
    {
        if (!$this->requireAuthenticatedAjax()) {
            return;
        }
        if (!$this->hasAjaxPermission(['list', 'add', 'edit'])) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['role_errors']['list'],
            ], 403);
            return;
        }

        if (!$this->tableExists('mod_socialmedia_post')) {
            $this->jsonResponse([
                'status' => 'success',
                'items' => [],
                'total' => 0,
                'has_more' => false,
                'offset' => 0,
                'limit' => 20,
            ]);
            return;
        }

        $langFilter = strtolower(trim((string) $this->readParam('lang')));
        $providerFilter = strtolower(trim((string) $this->readParam('provider')));
        $statusFilter = strtolower(trim((string) $this->readParam('status')));
        $queryFilter = trim((string) $this->readParam('q'));
        $offset = max(0, intval($this->readParam('offset', 0)));
        $limit = max(1, min(50, intval($this->readParam('limit', 20))));

        $wheres = $this->buildGridWheres($langFilter, $providerFilter, $statusFilter, $queryFilter);
        $whereSql = count($wheres) > 0 ? ('WHERE ' . implode(' AND ', $wheres)) : '';

        $this->db->fetch_array('SELECT COUNT(*) FROM mod_socialmedia_post ' . $whereSql);
        $countRows = $this->db->toArray();
        $total = (!empty($countRows) && is_array($countRows[0])) ? intval(reset($countRows[0])) : 0;

        $rows = [];
        if ($total > 0) {
            $sql = 'SELECT id, lang, provider, media_type, post_id, name, handle, content, url, embed_html, image, status, acl_id, user_id, created_at'
                . ' FROM mod_socialmedia_post '
                . $whereSql
                . ' ORDER BY created_at DESC, id DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
            $this->db->fetch_array($sql);
            $rawRows = $this->db->toArray();
            if (is_array($rawRows)) {
                foreach ($rawRows as $rawRow) {
                    $rows[] = $this->formatItemPayload($rawRow);
                }
            }
        }

        $this->jsonResponse([
            'status' => 'success',
            'items' => $rows,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'has_more' => ($offset + count($rows)) < $total,
        ]);
    }

    public function approve()
    {
        $query = Req::Post_Safe('query');
        if ($this->updateSingleColumn('status', 'y', $query, 'edit')) {
            $this->echoSuccessResponse();
            return;
        }
        echo 'ERROR';
    }

    public function unapprove()
    {
        $query = Req::Post_Safe('query');
        if ($this->updateSingleColumn('status', 'n', $query, 'edit')) {
            $this->echoSuccessResponse();
            return;
        }
        echo 'ERROR';
    }

    public function ajax_toggle_status()
    {
        if (!$this->requireAuthenticatedAjax()) {
            return;
        }
        if (!$this->hasAjaxPermission(['edit'])) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['role_errors']['edit'],
            ], 403);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        if ($id <= 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['ajax']['id_missing'],
            ]);
            return;
        }

        $model = Load::model('mod_socialmedia_post');
        $model->where_id($id)->gRo();
        if ($model->getSize() === 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['errors']['record'],
            ]);
            return;
        }
        $model->sync();

        if ($this->user->checkPermission('mod_socialmedia_post', 'moderate_edit', $model->user_id, $this->lang['mod_socialmedia_post']['role_errors']['moderate_edit'], 0) === false) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['role_errors']['moderate_edit'],
            ], 403);
            return;
        }

        $newStatus = ($model->status === 'y') ? 'n' : 'y';
        $update = Load::model('mod_socialmedia_post');
        $update->id = $id;
        $update->status = $newStatus;
        if ($update->update('id')) {
            $this->jsonResponse([
                'status' => 'success',
                'id' => $id,
                'new_status' => $newStatus,
                'message' => $this->lang['mod_socialmedia_post']['ajax']['save_success'],
            ]);
            return;
        }

        $this->jsonResponse([
            'status' => 'error',
            'message' => $this->lang['mod_socialmedia_post']['ajax']['error'],
        ]);
    }

    public function autofill_metadata()
    {
        if (!$this->requireAuthenticatedAjax()) {
            return;
        }
        if (!$this->hasAjaxPermission(['add', 'edit'], '', true)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['autofill']['permission_denied'],
            ], 403);
            return;
        }

        $urlInput = trim((string) Req::Post('url'));
        $source = $urlInput !== '' ? $urlInput : trim((string) Req::Post('source'));
        if ($source === '') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['autofill']['missing_source'],
            ]);
            return;
        }

        $detected = SocialPostProviderService::detectProvider($source);
        if ($detected === null) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['autofill']['invalid_source'],
            ]);
            return;
        }

        $langCode = strtolower(trim((string) Req::Post_Safe('lang')));
        if ($langCode === '') {
            $langCode = strtolower((string) ($this->site['lang'] ?? 'en'));
        }

        try {
            $metadata = SocialPostProviderService::fetchMetadata(
                $detected['provider'],
                $detected['post_id'],
                $detected['canonical_url'],
                $langCode,
                $detected['media_type']
            );

            $imageFile = '';
            $imageUrl = '';
            if (!empty($metadata['thumbnail_url'])) {
                $imageFile = SocialPostProviderService::importRemoteThumbnail((string) $metadata['thumbnail_url']);
                if ($imageFile !== '') {
                    $resolvedThumb = $this->resolveThumbUrl($imageFile);
                    $imageUrl = $resolvedThumb !== '' ? ($resolvedThumb . '?v=' . time()) : '';
                }
            }

            $safeName = SocialPostProviderService::sanitizeUtf8Text((string) ($metadata['name'] ?? ''));
            $safeContent = SocialPostProviderService::sanitizeUtf8Text((string) ($metadata['content'] ?? ''));
            $handle = ltrim(SocialPostProviderService::sanitizeUtf8Text((string) ($metadata['handle'] ?? ($detected['handle'] ?? ''))), '@');
            $canonicalUrl = (string) ($metadata['canonical_url'] ?? $detected['canonical_url']);
            $mediaType = (string) ($metadata['media_type'] ?? $detected['media_type']);

            $embedCode = SocialPostProviderService::buildEmbedHtml($detected['provider'], $detected['post_id'], [
                'url' => $canonicalUrl,
                'media_type' => $mediaType,
                'lang' => $langCode,
                'title' => $safeName,
            ]);

            $isPartial = !empty($metadata['is_partial']);
            $message = $isPartial
                ? $this->lang['mod_socialmedia_post']['autofill']['partial']
                : $this->lang['mod_socialmedia_post']['autofill']['success'];

            $this->jsonResponse([
                'status' => 'success',
                'is_partial' => $isPartial,
                'message' => $message,
                'data' => [
                    'provider' => $detected['provider'],
                    'provider_label' => $this->providerLabel($detected['provider']),
                    'media_type' => $mediaType,
                    'post_id' => $detected['post_id'],
                    'url' => $canonicalUrl,
                    'name' => $safeName,
                    'handle' => mb_substr($handle, 0, 64, 'UTF-8'),
                    'content' => $safeContent,
                    'image' => $imageFile,
                    'image_url' => $imageUrl,
                    'embed_html' => $embedCode,
                    'embed' => $embedCode,
                    'source' => $metadata['source'] ?? $detected['provider'],
                ],
            ]);
        } catch (Throwable $e) {
            $errKey = $e->getMessage();
            if ($errKey === 'autofill.tombstone_error') {
                $message = $this->lang['mod_socialmedia_post']['autofill']['tombstone_error'];
            } else {
                $message = $this->lang['mod_socialmedia_post']['autofill']['fetch_failed'];
            }

            $this->jsonResponse([
                'status' => 'error',
                'message' => $message,
            ]);
        }
    }

    public function quick_save()
    {
        register_shutdown_function(function () {
            $fatal = error_get_last();
            if ($fatal !== null && in_array($fatal['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $this->logQuickSaveFailure('fatal', $fatal['message'] . ' @ ' . $fatal['file'] . ':' . $fatal['line']);
            }
        });

        try {
            $this->runQuickSave();
        } catch (Throwable $e) {
            $this->logQuickSaveFailure('exception', get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['sheet']['quick_save_error'],
            ]);
        }
    }

    private function runQuickSave(): void
    {
        if (!$this->requireAuthenticatedAjax()) {
            return;
        }

        $targetModule = trim((string) Req::Post_Safe('target_module'));
        if (!$this->hasAjaxPermission(['add', 'edit'], $targetModule, true)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['role_errors']['add'],
            ], 403);
            return;
        }

        if (!$this->tableExists('mod_socialmedia_post')) {
            $this->logQuickSaveFailure('table', 'mod_socialmedia_post table not found');
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['sheet']['quick_save_error'],
            ], 500);
            return;
        }

        $rawUrl = trim((string) Req::Post('url'));
        $detected = SocialPostProviderService::detectProvider($rawUrl);
        if ($detected === null) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['errors']['invalid_url'],
            ]);
            return;
        }

        $langCode = strtolower(trim((string) Req::Post_Safe('lang')));
        if ($langCode === '') {
            $langCode = strtolower((string) ($this->site['lang'] ?? 'en'));
        }

        $targetVid = intval(Req::Post_Safe('target_id'));
        $statusVal = trim((string) Req::Post_Safe('status'));
        $finalStatus = in_array($statusVal, ['y', 'n'], true) ? $statusVal : 'y';

        $postIdValue = $detected['post_id'];
        $existing = Load::model('mod_socialmedia_post');
        $existing->flush();
        $existing->where_provider($detected['provider'])
            ->addWhere("post_id='" . Req::Clear($postIdValue) . "'")
            ->addWhere("lang='" . Req::Clear($langCode) . "'")
            ->limit(1)
            ->gRo();

        if ($existing->getSize() > 0) {
            $row = $existing->getResultOne()->toArray();
            $existingId = intval($row['id'] ?? 0);
            if ($finalStatus === 'y' && ($row['status'] ?? 'n') !== 'y' && $existingId > 0) {
                $update = Load::model('mod_socialmedia_post');
                $update->id = $existingId;
                $update->status = 'y';
                $update->update('id');
                $row['status'] = 'y';
            }
            if ($targetModule !== '' && $targetVid > 0 && $this->isRegisteredHostModule($targetModule)) {
                $this->insertLinkRow($targetModule, $targetVid, $existingId);
            }

            $this->jsonResponse([
                'status' => 'success',
                'id' => $existingId,
                'token' => '[socialpost:' . $existingId . ']',
                'already_existed' => true,
                'message' => $this->lang['mod_socialmedia_post']['sheet']['saved_and_inserted'],
                'item' => $this->formatItemPayload($row),
            ]);
            return;
        }

        $nameInput = SocialPostProviderService::sanitizeUtf8Text((string) Req::Post('name'));
        $contentInput = SocialPostProviderService::sanitizeUtf8Text((string) Req::Post('content'));
        $handleInput = ltrim(SocialPostProviderService::sanitizeUtf8Text((string) Req::Post('handle')), '@');
        $autofillImage = basename(trim((string) Req::Post_Safe('autofill_image')));
        $requestedMediaType = trim((string) Req::Post_Safe('media_type'));
        $mediaType = in_array($requestedMediaType, ['post', 'video', 'reel', 'photo'], true)
            ? $requestedMediaType
            : $detected['media_type'];

        $canonicalUrl = $detected['canonical_url'];
        $imageFile = $autofillImage;

        if ($nameInput === '' || $imageFile === '' || $mediaType === 'post') {
            try {
                $metadata = SocialPostProviderService::fetchMetadata(
                    $detected['provider'],
                    $postIdValue,
                    $canonicalUrl,
                    $langCode,
                    $mediaType
                );
                if (!empty($metadata['canonical_url'])) {
                    $canonicalUrl = (string) $metadata['canonical_url'];
                }
                if ($mediaType === 'post' && !empty($metadata['media_type']) && in_array($metadata['media_type'], ['post', 'video', 'reel', 'photo'], true)) {
                    $mediaType = (string) $metadata['media_type'];
                }
                if ($nameInput === '') {
                    $nameInput = SocialPostProviderService::sanitizeUtf8Text((string) ($metadata['name'] ?? ''));
                }
                if ($contentInput === '') {
                    $contentInput = SocialPostProviderService::sanitizeUtf8Text((string) ($metadata['content'] ?? ''));
                }
                if ($handleInput === '') {
                    $handleInput = ltrim(SocialPostProviderService::sanitizeUtf8Text((string) ($metadata['handle'] ?? ($detected['handle'] ?? ''))), '@');
                }
                if ($imageFile === '' && !empty($metadata['thumbnail_url'])) {
                    $imageFile = SocialPostProviderService::importRemoteThumbnail((string) $metadata['thumbnail_url']);
                }
            } catch (Throwable $e) {
                // Keep manual fallback fields if metadata lookup fails.
            }
        }

        if ($nameInput === '') {
            $nameInput = $this->providerLabel($detected['provider']) . ' #' . $postIdValue;
        }
        if ($handleInput === '' && !empty($detected['handle'])) {
            $handleInput = (string) $detected['handle'];
        }

        $embedResult = SocialPostProviderService::sanitizeOrRebuildEmbed($canonicalUrl, null, [
            'media_type' => $mediaType,
            'lang' => $langCode,
            'title' => $nameInput,
        ]);

        $model = Load::model('mod_socialmedia_post');
        $model->lang = $langCode;
        $model->provider = $detected['provider'];
        $model->media_type = $embedResult['media_type'] ?? $mediaType;
        $model->post_id = $postIdValue;
        $model->name = mb_substr($nameInput, 0, 255, 'UTF-8');
        $model->handle = mb_substr($handleInput, 0, 64, 'UTF-8');
        $model->content = $contentInput;
        $model->url = $embedResult['canonical_url'] ?? $canonicalUrl;
        $model->embed_html = $embedResult['embed_html'] ?? '';
        $model->image = $imageFile !== '' ? $imageFile : null;
        $model->status = $finalStatus;
        $model->acl_id = max(0, intval(Req::Post_Safe('acl_id') ?: 0));
        $model->user_id = intval($this->session->get('id'));
        $model->created_at = date('Y-m-d H:i:s');

        if (!$model->save(true)) {
            $this->logQuickSaveFailure('insert', 'ORM save() returned false for provider=' . $detected['provider'] . ' post_id=' . $postIdValue);
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['sheet']['quick_save_error'],
            ]);
            return;
        }

        $newId = intval($model->id ?: $this->db->insert_id());
        if ($newId > 0 && $targetModule !== '' && $targetVid > 0 && $this->isRegisteredHostModule($targetModule)) {
            $this->insertLinkRow($targetModule, $targetVid, $newId);
        }

        $savedRow = [
            'id' => $newId,
            'lang' => $model->lang,
            'provider' => $model->provider,
            'media_type' => $model->media_type,
            'post_id' => $model->post_id,
            'name' => $model->name,
            'handle' => $model->handle,
            'content' => $model->content,
            'url' => $model->url,
            'embed_html' => $model->embed_html,
            'image' => $model->image,
            'status' => $model->status,
            'acl_id' => $model->acl_id,
            'user_id' => $model->user_id,
            'created_at' => $model->created_at,
        ];

        $this->jsonResponse([
            'status' => 'success',
            'id' => $newId,
            'token' => '[socialpost:' . $newId . ']',
            'already_existed' => false,
            'message' => $this->lang['mod_socialmedia_post']['sheet']['saved_and_inserted'],
            'item' => $this->formatItemPayload($savedRow),
        ]);
    }

    public function ajax_list_library()
    {
        if (!$this->requireAuthenticatedAjax()) {
            return;
        }
        if (!$this->hasAjaxPermission(['list', 'add', 'edit'], '', true)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['role_errors']['list'],
            ], 403);
            return;
        }

        if (!$this->tableExists('mod_socialmedia_post')) {
            $this->jsonResponse([
                'status' => 'success',
                'items' => [],
                'total' => 0,
                'has_more' => false,
            ]);
            return;
        }

        $langFilter = strtolower(trim((string) $this->readParam('lang')));
        $providerFilter = strtolower(trim((string) $this->readParam('provider')));
        $queryFilter = trim((string) $this->readParam('q'));
        $offset = max(0, intval($this->readParam('offset', 0)));
        $limit = max(1, min(50, intval($this->readParam('limit', 20))));

        $wheres = $this->buildGridWheres($langFilter, $providerFilter, '', $queryFilter);
        $whereSql = count($wheres) > 0 ? ('WHERE ' . implode(' AND ', $wheres)) : '';

        $this->db->fetch_array('SELECT COUNT(*) FROM mod_socialmedia_post ' . $whereSql);
        $countRows = $this->db->toArray();
        $total = (!empty($countRows) && is_array($countRows[0])) ? intval(reset($countRows[0])) : 0;

        $items = [];
        if ($total > 0) {
            $sql = 'SELECT id, lang, provider, media_type, post_id, name, handle, content, url, image, status, created_at'
                . ' FROM mod_socialmedia_post '
                . $whereSql
                . ' ORDER BY created_at DESC, id DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
            $this->db->fetch_array($sql);
            foreach ($this->db->toArray() as $row) {
                $items[] = $this->formatItemPayload($row);
            }
        }

        $this->jsonResponse([
            'status' => 'success',
            'items' => $items,
            'total' => $total,
            'has_more' => ($offset + count($items)) < $total,
        ]);
    }

    public function ajax_get_bound()
    {
        if (!$this->requireAuthenticatedAjax()) {
            return;
        }

        $modName = trim((string) $this->readParam('mod_name'));
        if (!$this->hasAjaxPermission(['list', 'add', 'edit'], $modName, true)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['role_errors']['list'],
            ], 403);
            return;
        }

        if (!$this->tableExists('mod_socialmedia_post') || !$this->tableExists('mod_socialmedia_post_link')) {
            $this->jsonResponse([
                'status' => 'success',
                'items' => [],
            ]);
            return;
        }

        $vidRaw = $this->readParam('vid', '');
        $vid = ($vidRaw !== '') ? intval($vidRaw) : intval($this->readParam('record_id', 0));
        $rawPostIds = $this->readParam('post_ids', '');

        $rows = [];
        if ($modName !== '' && $vid > 0) {
            $rows = $this->fetchBoundRows($modName, $vid);
        } elseif ($rawPostIds !== '' && $rawPostIds !== []) {
            $idList = is_array($rawPostIds) ? $rawPostIds : explode(',', (string) $rawPostIds);
            $cleanIds = [];
            foreach ($idList as $item) {
                $num = intval($item);
                if ($num > 0 && !in_array($num, $cleanIds, true)) {
                    $cleanIds[] = $num;
                }
            }
            if (!empty($cleanIds)) {
                $sql = 'SELECT id, lang, provider, media_type, post_id, name, handle, content, url, image, status, created_at'
                    . ' FROM mod_socialmedia_post WHERE id IN (' . implode(',', $cleanIds) . ')';
                $this->db->fetch_array($sql);
                $fetched = $this->db->toArray();
                $indexed = [];
                foreach ($fetched as $rowItem) {
                    $indexed[intval($rowItem['id'])] = $rowItem;
                }
                foreach ($cleanIds as $idVal) {
                    if (isset($indexed[$idVal])) {
                        $rows[] = $this->formatItemPayload($indexed[$idVal]);
                    }
                }
            }
        }

        $this->jsonResponse([
            'status' => 'success',
            'items' => $rows,
        ]);
    }

    public function ajax_link()
    {
        if (!$this->requireAuthenticatedAjax()) {
            return;
        }

        $modName = trim((string) Req::Post_Safe('mod_name'));
        if (!$this->hasAjaxPermission(['edit', 'add'], $modName, false)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['role_errors']['edit'],
            ], 403);
            return;
        }

        $vid = Req::is_Post('vid') ? intval(Req::Post_Safe('vid')) : intval(Req::Post_Safe('record_id'));
        $socialPostId = Req::is_Post('socialmedia_post_id')
            ? intval(Req::Post_Safe('socialmedia_post_id'))
            : intval(Req::Post_Safe('post_record_id'));

        if ($modName === '' || $vid <= 0 || $socialPostId <= 0 || !$this->isRegisteredHostModule($modName)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['ajax']['id_missing'],
            ]);
            return;
        }

        if (!$this->tableExists('mod_socialmedia_post') || !$this->tableExists('mod_socialmedia_post_link')) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['errors']['record'],
            ]);
            return;
        }

        $this->db->fetch_array('SELECT id FROM mod_socialmedia_post WHERE id=' . $socialPostId . ' LIMIT 1');
        if ($this->db->getSize() === 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['errors']['record'],
            ]);
            return;
        }

        $this->insertLinkRow($modName, $vid, $socialPostId);

        $this->jsonResponse([
            'status' => 'success',
            'token' => '[socialpost:' . $socialPostId . ']',
            'items' => $this->fetchBoundRows($modName, $vid),
            'message' => $this->lang['mod_socialmedia_post']['ajax']['relation_updated'],
        ]);
    }

    public function ajax_unlink()
    {
        if (!$this->requireAuthenticatedAjax()) {
            return;
        }

        $modName = trim((string) Req::Post_Safe('mod_name'));
        if (!$this->hasAjaxPermission(['edit', 'add'], $modName, false)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['role_errors']['edit'],
            ], 403);
            return;
        }

        $vid = Req::is_Post('vid') ? intval(Req::Post_Safe('vid')) : intval(Req::Post_Safe('record_id'));
        $socialPostId = Req::is_Post('socialmedia_post_id')
            ? intval(Req::Post_Safe('socialmedia_post_id'))
            : intval(Req::Post_Safe('post_record_id'));

        if ($modName === '' || $vid <= 0 || $socialPostId <= 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $this->lang['mod_socialmedia_post']['ajax']['id_missing'],
            ]);
            return;
        }

        if ($this->tableExists('mod_socialmedia_post_link')) {
            $safeMod = Req::Clear($modName);
            $this->db->query(
                "DELETE FROM mod_socialmedia_post_link WHERE mod_name='" . $safeMod . "' AND vid=" . $vid . ' AND socialmedia_post_id=' . $socialPostId
            );
        }

        $this->jsonResponse([
            'status' => 'success',
            'items' => $this->fetchBoundRows($modName, $vid),
            'message' => $this->lang['mod_socialmedia_post']['ajax']['relation_updated'],
        ]);
    }

    /**
     * Bridge cleanup hook invoked when a host module or host record is deleted.
     */
    public function _remove($modName, $recordId = null)
    {
        $cleanModName = trim((string) $modName);
        if ($cleanModName === '' || !$this->tableExists('mod_socialmedia_post_link')) {
            return;
        }

        $safeMod = Req::Clear($cleanModName);
        $sql = "DELETE FROM mod_socialmedia_post_link WHERE mod_name='" . $safeMod . "'";
        if ($recordId !== null && intval($recordId) > 0) {
            $sql .= ' AND vid=' . intval($recordId);
        }
        $this->db->query($sql);
    }

    private function populatePostFields($model, string $langCode): void
    {
        $rawUrl = trim((string) Req::Post('url'));
        $rawEmbed = trim((string) (Req::is_Post('embed_html') ? Req::Post('embed_html') : Req::Post('embed')));
        $requestedMediaType = trim((string) Req::Post_Safe('media_type'));
        $nameText = SocialPostProviderService::sanitizeUtf8Text((string) Req::Post('name'));
        $handleText = ltrim(SocialPostProviderService::sanitizeUtf8Text((string) Req::Post('handle')), '@');
        $contentText = SocialPostProviderService::sanitizeUtf8Text((string) Req::Post('content'));

        $options = [
            'title' => $nameText,
            'lang' => $langCode,
        ];
        if (in_array($requestedMediaType, ['post', 'video', 'reel', 'photo'], true)) {
            $options['media_type'] = $requestedMediaType;
        }

        $sanitized = SocialPostProviderService::sanitizeOrRebuildEmbed($rawUrl, $rawEmbed, $options);

        $model->provider = $sanitized['provider'] ?? '';
        $model->media_type = $sanitized['media_type'] ?? 'post';
        $model->post_id = $sanitized['post_id'] ?? '';
        $model->url = $sanitized['canonical_url'] ?? $rawUrl;
        $model->embed_html = $sanitized['embed_html'] ?? '';
        $model->name = mb_substr($nameText, 0, 255, 'UTF-8');
        $model->handle = mb_substr($handleText !== '' ? $handleText : (string) ($sanitized['handle'] ?? ''), 0, 64, 'UTF-8');
        $model->content = $contentText;
    }

    private function handleThumbnailSave($model, string $oldImage): void
    {
        $autofillImage = basename(trim((string) Req::Post_Safe('autofill_image')));
        if (isset($_FILES['image']['name']) && $_FILES['image']['name'] !== '') {
            $image = Load::mod('image');
            $image->mod = 'mod_socialmedia_post';
            $image->name = 'image';
            if ($image->_resize($this->thumbnailImageOptions())) {
                $model->image = (string) $image->url;
                return;
            }
        }

        if ($autofillImage !== '' && $autofillImage !== '.' && $autofillImage !== '..') {
            $model->image = $autofillImage;
            return;
        }

        if ($oldImage === '') {
            $model->image = null;
        }
    }

    private function handleThumbnailUpdate($model, string $oldImage): void
    {
        $removeImage = Req::Post_Safe('remove_image') === '1';
        $autofillImage = basename(trim((string) Req::Post_Safe('autofill_image')));

        if (isset($_FILES['image']['name']) && $_FILES['image']['name'] !== '') {
            $image = Load::mod('image');
            $image->mod = 'mod_socialmedia_post';
            $image->name = 'image';
            if ($image->_resize($this->thumbnailImageOptions())) {
                $newFile = (string) $image->url;
                if ($oldImage !== '' && $oldImage !== $newFile) {
                    SocialPostProviderService::deleteLocalThumbnail($oldImage);
                }
                $model->image = $newFile;
                return;
            }
        }

        if ($autofillImage !== '' && $autofillImage !== '.' && $autofillImage !== '..') {
            if ($oldImage !== '' && $oldImage !== $autofillImage) {
                SocialPostProviderService::deleteLocalThumbnail($oldImage);
            }
            $model->image = $autofillImage;
            return;
        }

        if ($removeImage) {
            if ($oldImage !== '') {
                SocialPostProviderService::deleteLocalThumbnail($oldImage);
            }
            $model->image = null;
            return;
        }

        if (isset($model->image)) {
            unset($model->image);
        }
    }

    private function thumbnailImageOptions(): array
    {
        return [
            '/' => [
                'width' => self::THUMB_IMAGE_WIDTH,
                'height' => null,
                'option' => 'width',
            ],
            '/thumb' => [
                'width' => self::THUMB_PREVIEW_WIDTH,
                'height' => null,
                'option' => 'width',
            ],
        ];
    }

    private function buildGridWheres(string $langFilter, string $providerFilter, string $statusFilter, string $queryFilter): array
    {
        $wheres = [];
        if (
            Role::hasPermission('mod_socialmedia_post', 'moderate_edit') !== true
            && Role::hasPermission('mod_socialmedia_post', 'moderate_delete') !== true
        ) {
            $wheres[] = 'user_id=' . intval($this->session->get('id'));
        }

        if ($langFilter !== '' && $langFilter !== 'all') {
            $wheres[] = "lang='" . Req::Clear($langFilter) . "'";
        }
        if (in_array($providerFilter, ['x', 'facebook', 'instagram'], true)) {
            $wheres[] = "provider='" . Req::Clear($providerFilter) . "'";
        }
        if (in_array($statusFilter, ['y', 'n'], true)) {
            $wheres[] = "status='" . Req::Clear($statusFilter) . "'";
        }
        if ($queryFilter !== '') {
            $safeQ = Req::Clear($queryFilter);
            $wheres[] = "(name LIKE '%" . $safeQ . "%' OR handle LIKE '%" . $safeQ . "%' OR post_id LIKE '%" . $safeQ . "%' OR url LIKE '%" . $safeQ . "%' OR content LIKE '%" . $safeQ . "%')";
        }

        return $wheres;
    }

    private function fetchBoundRows(string $modName, int $vid): array
    {
        if (
            $modName === ''
            || $vid <= 0
            || !$this->tableExists('mod_socialmedia_post')
            || !$this->tableExists('mod_socialmedia_post_link')
        ) {
            return [];
        }

        $safeMod = Req::Clear($modName);
        $sql = 'SELECT p.id, p.lang, p.provider, p.media_type, p.post_id, p.name, p.handle, p.content, p.url, p.image, p.status, p.created_at'
            . ' FROM mod_socialmedia_post_link l'
            . ' INNER JOIN mod_socialmedia_post p ON p.id = l.socialmedia_post_id'
            . " WHERE l.mod_name='" . $safeMod . "' AND l.vid=" . $vid
            . ' ORDER BY l.sort_order ASC, l.id ASC';
        $this->db->fetch_array($sql);
        $raw = $this->db->toArray();
        if (!is_array($raw)) {
            return [];
        }

        $items = [];
        foreach ($raw as $row) {
            $items[] = $this->formatItemPayload($row);
        }
        return $items;
    }

    private function insertLinkRow(string $modName, int $vid, int $socialPostId): void
    {
        if (!$this->tableExists('mod_socialmedia_post_link')) {
            return;
        }

        $safeMod = Req::Clear($modName);
        $this->db->fetch_array(
            "SELECT id FROM mod_socialmedia_post_link WHERE mod_name='" . $safeMod . "' AND vid=" . $vid . ' AND socialmedia_post_id=' . $socialPostId . ' LIMIT 1'
        );
        if ($this->db->getSize() > 0) {
            return;
        }

        $this->db->fetch_array(
            "SELECT MAX(sort_order) FROM mod_socialmedia_post_link WHERE mod_name='" . $safeMod . "' AND vid=" . $vid
        );
        $maxRows = $this->db->toArray();
        $nextOrder = (!empty($maxRows) && is_array($maxRows[0])) ? (intval(reset($maxRows[0])) + 1) : 1;

        // The link model lives inside mod_socialmedia_post/model/, so it must be loaded via the dotted module path.
        $linkModel = Load::model('mod_socialmedia_post.mod_socialmedia_post_link');
        $linkModel->mod_name = $modName;
        $linkModel->vid = $vid;
        $linkModel->socialmedia_post_id = $socialPostId;
        $linkModel->sort_order = $nextOrder;
        $linkModel->created_at = date('Y-m-d H:i:s');
        $linkModel->save(true);
    }

    private function isRegisteredHostModule(string $modName): bool
    {
        if ($modName === '' || !$this->tableExists('mod_socialmedia_post_mod')) {
            return false;
        }
        $safeMod = Req::Clear($modName);
        $this->db->fetch_array("SELECT id FROM mod_socialmedia_post_mod WHERE mod_name='" . $safeMod . "' LIMIT 1");
        return $this->db->getSize() > 0;
    }

    private function formatItemPayload(array $row): array
    {
        $id = intval($row['id'] ?? 0);
        $provider = strtolower((string) ($row['provider'] ?? 'x'));
        $image = trim((string) ($row['image'] ?? ''));
        $thumbUrl = $this->resolveThumbUrl($image);

        return [
            'id' => $id,
            'token' => '[socialpost:' . $id . ']',
            'lang' => (string) ($row['lang'] ?? ''),
            'provider' => $provider,
            'provider_label' => $this->providerLabel($provider),
            'media_type' => (string) ($row['media_type'] ?? 'post'),
            'post_id' => (string) ($row['post_id'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'handle' => ltrim((string) ($row['handle'] ?? ''), '@'),
            'content' => (string) ($row['content'] ?? ''),
            'url' => (string) ($row['url'] ?? ''),
            'image' => $image,
            'thumb_url' => $thumbUrl,
            'status' => (string) ($row['status'] ?? 'n'),
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }

    /**
     * Appends a quick_save() failure reason to logs/ajax_errors.log for diagnostics.
     */
    private function logQuickSaveFailure(string $stage, string $detail): void
    {
        $baseRoot = defined('root_dir') ? root_dir : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;
        $logFile = rtrim($baseRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'ajax_errors.log';
        $line = '[' . date('Y-m-d H:i:s') . '] SocialPost quick_save failure (' . $stage . '): ' . $detail . "\n"
            . '  URL: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n---\n";
        @file_put_contents($logFile, $line, FILE_APPEND);
    }

    private function providerLabel(string $provider): string
    {
        $map = [
            'x' => 'X',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
        ];
        return $map[strtolower($provider)] ?? strtoupper($provider);
    }

    private function readParam(string $key, $default = '')
    {
        if (isset($_POST[$key])) {
            return is_array($_POST[$key]) ? $_POST[$key] : Req::Post_Safe($key);
        }
        if (isset($_GET[$key])) {
            return is_array($_GET[$key]) ? $_GET[$key] : Req::Get_Safe($key);
        }
        return $default;
    }

    private function requireAuthenticatedAjax(): bool
    {
        if ($this->user->checklogin() !== false) {
            return true;
        }

        $targetUrl = (!empty($this->site['saddress']) ? $this->site['saddress'] : $this->site['address']) . '?login=modal&session_expired=1';
        $this->jsonResponse([
            'status' => 'error',
            'code' => 'session_expired',
            'message' => $this->lang['mod_socialmedia_post']['autofill']['permission_denied'],
            'redirect' => $targetUrl,
        ], 401);
        return false;
    }

    private function hasAjaxPermission(array $roles, string $hostModule = '', bool $allowAnyRegisteredHost = false): bool
    {
        foreach ($roles as $roleName) {
            if (Role::hasPermission('mod_socialmedia_post', $roleName) === true) {
                return true;
            }
            if ($this->user->hasPermission('mod_socialmedia_post', $roleName, '', 0) === true) {
                return true;
            }
        }

        if ($hostModule !== '' && $this->isRegisteredHostModule($hostModule)) {
            if (
                Role::hasPermission($hostModule, 'edit') === true
                || Role::hasPermission($hostModule, 'add') === true
                || $this->user->hasPermission($hostModule, 'edit', '', 0) === true
                || $this->user->hasPermission($hostModule, 'add', '', 0) === true
            ) {
                return true;
            }
        }

        if ($allowAnyRegisteredHost) {
            foreach (['mod_news', 'mod_page', 'mod_publication', 'mod_event', 'mod_announcements'] as $candidateHost) {
                if (!$this->isRegisteredHostModule($candidateHost)) {
                    continue;
                }
                if (
                    Role::hasPermission($candidateHost, 'edit') === true
                    || Role::hasPermission($candidateHost, 'add') === true
                    || $this->user->hasPermission($candidateHost, 'edit', '', 0) === true
                    || $this->user->hasPermission($candidateHost, 'add', '', 0) === true
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    private function resolveThumbUrl(string $image): string
    {
        $image = trim($image);
        if ($image === '') {
            return '';
        }
        if (preg_match('/^https?:\/\//i', $image)) {
            return $image;
        }
        $safeFile = ltrim($image, '/');
        $baseRoot = defined('root_dir') ? root_dir : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;
        $thumbDiskPath = rtrim($baseRoot, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'static'
            . DIRECTORY_SEPARATOR . 'mod_socialmedia_post'
            . DIRECTORY_SEPARATOR . 'thumb'
            . DIRECTORY_SEPARATOR . $safeFile;
        if (is_file($thumbDiskPath)) {
            return $this->site['iaddress'] . 'mod_socialmedia_post/thumb/' . $safeFile;
        }
        return $this->site['iaddress'] . 'mod_socialmedia_post/' . $safeFile;
    }
}
