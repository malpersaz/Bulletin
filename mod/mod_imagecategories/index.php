<?php
class mod_imagecategories_mod extends indexMain
{
    public $db;
    public $session;
    public $user;
    public $view;
    public $lang;
    public $mod_imagecategories_model;
    private $lastErrorMessage = '';
    private $menuSlotAliases = [
        'header' => ['##HEADER MENU', 'HEADER MENU', '##HEADER', 'HEADER', 'TOP MENU', 'MAIN MENU'],
        'footer' => ['##FOOTER MENU', 'FOOTER MENU', '##FOOTER', 'FOOTER', 'BOTTOM MENU'],
        'sidebar' => ['##SIDEBAR', 'SIDEBAR', 'SIDE MENU'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->db = Load::lib('Database');
        $this->session = Load::lib('Session');
        $this->user = Load::model('mod_user.user');
        $this->view = Load::lib('AdminTemplate');
        $this->lang = Load::lang('mod_imagecategories');
        $this->view->lang($this->lang);
    }

    private function isCategoryContainerRow(array $row)
    {
        return isset($row['name']) && strpos(trim((string) $row['name']), '##') === 0;
    }

    private function isLanguageRootRow(array $row)
    {
        return $this->isLanguageRootCandidateRow($row)
            && (int) ($row['parent_id'] ?? -1) === 0;
    }

    private function isLanguageRootCandidateRow(array $row)
    {
        return $this->isCategoryContainerRow($row)
            && (($row['root'] ?? '') === 'y');
    }

    public function resolveDisplayName($row, array $visibleLanguages = [])
    {
        $name = is_array($row) ? ($row['name'] ?? '') : (string) $row;
        if (strpos($name, '##') === 0) {
            if (is_array($row) && (int) ($row['parent_id'] ?? -1) === 0 && !empty($visibleLanguages)) {
                $langCode = isset($row['lang']) ? $row['lang'] : '';
                foreach ($visibleLanguages as $language) {
                    if (($language['seo'] ?? '') === $langCode) {
                        return $language['name'];
                    }
                }
            }

            $langKey = substr($name, 2);
            if (isset($this->lang['mod_imagecategories'][$langKey])) {
                return $this->lang['mod_imagecategories'][$langKey];
            }

            return $name;
        }

        return $name;
    }

    private function getVisibleLanguages($currentRecordLang = '')
    {
        $langWhere = "WHERE status='y'";
        if ($currentRecordLang !== '') {
            $langWhere .= " OR seo='" . Req::Clear($currentRecordLang) . "'";
        }

        $this->db->fetch_array("SELECT * FROM `lang` " . $langWhere . " ORDER BY is_default DESC, seo ASC");
        $rows = $this->db->getResult()->toArray();

        return is_array($rows) ? $rows : [];
    }

    private function getVisibleLanguageCodes($currentRecordLang = '')
    {
        $codes = [];
        foreach ($this->getVisibleLanguages($currentRecordLang) as $row) {
            if (!empty($row['seo'])) {
                $codes[] = (string) $row['seo'];
            }
        }

        return $codes;
    }

    private function buildNestedRows(array $rows)
    {
        if (empty($rows)) {
            return [];
        }

        $nested = Helper::nested($rows, 'parent_id', 'id');
        return is_array($nested) ? $nested : [];
    }

    private function getAllCategoryRows()
    {
        $this->db->fetch_array("SELECT * FROM `mod_imagecategories` ORDER BY `order` ASC, `id` ASC");
        $rows = $this->db->getResult()->toArray();

        return is_array($rows) ? $rows : [];
    }

    private function findLanguageRootRow(array $rows, $lang)
    {
        foreach ($rows as $row) {
            if (($row['lang'] ?? '') === $lang && $this->isLanguageRootRow($row)) {
                return $row;
            }
        }

        return null;
    }

    private function resolveCategoryLanguageFromAncestry(array $rowsById, array $startRow)
    {
        $current = $startRow;
        $visited = [];

        while (isset($current['id'])) {
            $currentId = (int) $current['id'];
            if (isset($visited[$currentId])) {
                break;
            }
            $visited[$currentId] = true;

            $currentLang = isset($current['lang']) ? trim((string) $current['lang']) : '';
            if ($currentLang !== '') {
                return $currentLang;
            }

            $parentId = isset($current['parent_id']) ? (int) $current['parent_id'] : 0;
            if ($parentId === 0 || !isset($rowsById[$parentId])) {
                break;
            }

            $current = $rowsById[$parentId];
        }

        return '';
    }

    private function categoryRowsById(array $rows)
    {
        $rowsById = [];
        foreach ($rows as $row) {
            $rowsById[(int) ($row['id'] ?? 0)] = $row;
        }

        return $rowsById;
    }

    private function isDescendantOf(array $rowsById, $candidateId, $ancestorId)
    {
        $candidateId = (int) $candidateId;
        $ancestorId = (int) $ancestorId;
        $visited = [];

        while ($candidateId > 0 && isset($rowsById[$candidateId])) {
            if ($candidateId === $ancestorId) {
                return true;
            }

            if (isset($visited[$candidateId])) {
                return true;
            }
            $visited[$candidateId] = true;

            $candidateId = (int) ($rowsById[$candidateId]['parent_id'] ?? 0);
        }

        return false;
    }

    private function resolveSelectedLanguage($currentRecordLang = '')
    {
        if (Req::is_Post('lang')) {
            return Req::Post_Safe('lang');
        }

        if (isset($_GET['lang'])) {
            return trim((string) $_GET['lang']);
        }

        if ($currentRecordLang !== '') {
            return $currentRecordLang;
        }

        if (!empty($this->site['lang'])) {
            return $this->site['lang'];
        }

        $defaultLang = Kayit::getir('default_lang');
        if (is_string($defaultLang) && $defaultLang !== '') {
            return $defaultLang;
        }

        return 'en';
    }

    private function normalizeMenuSlotName($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = str_replace(
            ['Ü', 'ü', 'Ş', 'ş', 'İ', 'ı', 'Ö', 'ö', 'Ç', 'ç', 'Ğ', 'ğ', '_', '-'],
            ['U', 'U', 'S', 'S', 'I', 'I', 'O', 'O', 'C', 'C', 'G', 'G', ' ', ' '],
            $value
        );
        $value = strtoupper($value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }

    private function isMenuContainerRow(array $row)
    {
        $url = isset($row['url']) ? trim((string) $row['url']) : '';
        $name = isset($row['name']) ? trim((string) $row['name']) : '';

        return $url === '##' || strpos($name, '##') === 0;
    }

    private function rowMatchesMenuSlotAlias(array $row, $slot)
    {
        if (!isset($this->menuSlotAliases[$slot])) {
            return false;
        }

        $normalizedName = $this->normalizeMenuSlotName(isset($row['name']) ? $row['name'] : '');
        foreach ($this->menuSlotAliases[$slot] as $alias) {
            if ($normalizedName === $this->normalizeMenuSlotName($alias)) {
                return true;
            }
        }

        return false;
    }

    private function isMenuLanguageRootRow(array $row)
    {
        if (!$this->isMenuContainerRow($row) || (int) ($row['parent_id'] ?? -1) !== 0) {
            return false;
        }

        foreach (array_keys($this->menuSlotAliases) as $slot) {
            if ($this->rowMatchesMenuSlotAlias($row, $slot)) {
                return false;
            }
        }

        return true;
    }

    private function findMenuLanguageRootRow(array $rows, $lang)
    {
        foreach ($rows as $row) {
            if (($row['lang'] ?? '') === $lang && $this->isMenuLanguageRootRow($row)) {
                return $row;
            }
        }

        return null;
    }

    private function resolveMenuDisplayName(array $row, array $visibleLanguages, array $menuLang)
    {
        $name = isset($row['name']) ? $row['name'] : '';

        if ($this->isMenuLanguageRootRow($row)) {
            $langCode = isset($row['lang']) ? $row['lang'] : '';
            foreach ($visibleLanguages as $language) {
                if (($language['seo'] ?? '') === $langCode) {
                    return $language['name'];
                }
            }
            return $name;
        }

        foreach (array_keys($this->menuSlotAliases) as $slot) {
            if ($this->rowMatchesMenuSlotAlias($row, $slot)) {
                if (isset($menuLang['mod_menu']['slots'][$slot])) {
                    return $menuLang['mod_menu']['slots'][$slot];
                }

                return $name;
            }
        }

        if ($this->isMenuContainerRow($row)) {
            return $name;
        }

        return $name;
    }

    private function getAllMenuRows()
    {
        $this->db->fetch_array("SELECT * FROM `mod_menu` ORDER BY `order` ASC, `id` ASC");
        $rows = $this->db->getResult()->toArray();

        return is_array($rows) ? $rows : [];
    }

    private function menuRowsById(array $rows)
    {
        $rowsById = [];
        foreach ($rows as $row) {
            $rowsById[(int) ($row['id'] ?? 0)] = $row;
        }

        return $rowsById;
    }

    private function resolveMenuLanguageFromAncestry(array $rowsById, array $startRow)
    {
        $current = $startRow;
        $visited = [];

        while (isset($current['id'])) {
            $currentId = (int) $current['id'];
            if (isset($visited[$currentId])) {
                break;
            }
            $visited[$currentId] = true;

            $currentLang = isset($current['lang']) ? trim((string) $current['lang']) : '';
            if ($currentLang !== '') {
                return $currentLang;
            }

            $parentId = isset($current['parent_id']) ? (int) $current['parent_id'] : 0;
            if ($parentId === 0 || !isset($rowsById[$parentId])) {
                break;
            }

            $current = $rowsById[$parentId];
        }

        return '';
    }

    private function setFormError($message)
    {
        $this->lastErrorMessage = $message;
        $this->_error_message($message);
    }

    private function validateCategoryParent($lang, $parentId, $currentId = null, array $candidateRow = [])
    {
        $parentId = intval($parentId);
        $rows = $this->getAllCategoryRows();
        $rowsById = $this->categoryRowsById($rows);
        $languageRootRow = $this->findLanguageRootRow($rows, $lang);
        $currentRow = $candidateRow;
        if ($currentId !== null && isset($rowsById[(int) $currentId])) {
            $currentRow = array_merge($rowsById[(int) $currentId], $candidateRow);
        }

        if ($parentId !== 0 && $this->isLanguageRootCandidateRow($currentRow)) {
            $this->setFormError($this->lang['mod_imagecategories']['errors']['language_root_parent_locked']);
            return false;
        }

        if ($parentId === 0) {
            if ($currentId !== null && isset($rowsById[(int) $currentId])) {
                $existingRow = $rowsById[(int) $currentId];
                $systemRootCandidate = $currentRow;
                $systemRootCandidate['parent_id'] = 0;
                if ($this->isLanguageRootRow($existingRow) && $this->isLanguageRootRow($systemRootCandidate) && (($existingRow['lang'] ?? '') === $lang)) {
                    return true;
                }
            }

            if ($languageRootRow === null) {
                return true;
            }

            $this->setFormError($this->lang['mod_imagecategories']['errors']['parent_selection_required']);
            return false;
        }

        if (!isset($rowsById[$parentId])) {
            $this->setFormError($this->lang['mod_imagecategories']['errors']['record']);
            return false;
        }

        if ($currentId !== null && $this->isDescendantOf($rowsById, $parentId, $currentId)) {
            $this->setFormError($this->lang['mod_imagecategories']['errors']['parent_cycle']);
            return false;
        }

        $parentLang = $this->resolveCategoryLanguageFromAncestry($rowsById, $rowsById[$parentId]);
        if ($parentLang !== '' && $parentLang !== $lang) {
            $this->setFormError($this->lang['mod_imagecategories']['errors']['parent_language_mismatch']);
            return false;
        }

        return true;
    }

    private function validateMenuParent($lang, $menuParentId)
    {
        $menuParentId = intval($menuParentId);
        if ($menuParentId === 0) {
            return true;
        }

        $rows = $this->getAllMenuRows();
        $rowsById = $this->menuRowsById($rows);
        if (!isset($rowsById[$menuParentId])) {
            $this->setFormError($this->lang['mod_imagecategories']['errors']['menu_parent_not_found']);
            return false;
        }

        $parentLang = $this->resolveMenuLanguageFromAncestry($rowsById, $rowsById[$menuParentId]);
        if ($parentLang !== '' && $parentLang !== $lang) {
            $this->setFormError($this->lang['mod_imagecategories']['errors']['menu_language_mismatch']);
            return false;
        }

        return true;
    }

    public function index()
    {
        $this->list();
    }

    public function list()
    {
        if ($this->user->hasPermission('mod_imagecategories', 'list', $this->lang['mod_imagecategories']['role_errors']['list'], 1) === false) {
            return false;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_imagecategories']['list'];
        $this->view->veri('site', $this->site);
        $this->view->veri('category_tree', $this->loadCategoryTree());
        $this->view->tpl('mod_imagecategories/list');
    }

    public function add()
    {
        if ($this->user->hasPermission('mod_imagecategories', 'add', $this->lang['mod_imagecategories']['role_errors']['add'], 1) === false) {
            return false;
        }

        if (Req::is_Post('add')) {
            $this->_add();
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_imagecategories']['add'];
        $this->view->veri('site', $this->site);
        $selectedLang = $this->resolveSelectedLanguage();
        $selectedParentId = Req::is_Post('parent_id') ? Req::Post_Safe('parent_id') : null;
        $formDefaults = $this->defaultFormData();
        $formDefaults['lang'] = $selectedLang;
        $this->loadFormMetadata($selectedLang, null, $selectedParentId);
        $this->view->veri('record', $formDefaults);
        $this->view->veri('form_id', 'imageCategoryAddForm');
        $this->view->veri('mode', 'add');
        $this->view->tpl('mod_imagecategories/add');
    }

    public function edit($id = null)
    {
        if ($this->user->hasPermission('mod_imagecategories', 'edit', $this->lang['mod_imagecategories']['role_errors']['edit'], 1) === false) {
            return false;
        }

        $id = Req::Url_Clear($id);
        $this->mod_imagecategories_model = Load::model('mod_imagecategories');
        $this->mod_imagecategories_model->where_id($id)->limit(1)->gRo();
        if ($this->mod_imagecategories_model->getSize() === 0) {
            $this->_fatal_error($this->lang['mod_imagecategories']['errors']['record']);
            Run::import('management', '_index');
            $this->view->veri('site', $this->site);
            $this->view->tpl('mod_imagecategories/edit');
            return false;
        }

        $this->mod_imagecategories_model->sync();
        $record = $this->mod_imagecategories_model->toArray();
        $record = $this->withMenuDefaults($record);

        if ($this->user->checkPermission('mod_imagecategories', 'moderate_edit', $record['user_id'], $this->lang['mod_imagecategories']['role_errors']['moderate_edit'], 1) === false) {
            return false;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id, $record);
            $this->mod_imagecategories_model->flush();
            $this->mod_imagecategories_model->where_id($id)->limit(1)->gRo()->sync();
            $record = $this->withMenuDefaults($this->mod_imagecategories_model->toArray());
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_imagecategories']['edit'];
        $this->view->veri('site', $this->site);
        $selectedLang = Req::is_Post('lang') ? Req::Post_Safe('lang') : ($record['lang'] ?? '');
        $selectedParentId = Req::is_Post('parent_id') ? Req::Post_Safe('parent_id') : ($record['parent_id'] ?? 0);
        $this->loadFormMetadata($selectedLang, $id, $selectedParentId, $record['lang'] ?? '');
        $this->view->veri('record', $record);
        $this->view->veri('form_id', 'imageCategoryEditForm');
        $this->view->veri('mode', 'edit');
        $this->view->tpl('mod_imagecategories/edit');
    }

    public function delete()
    {
        if ($this->user->hasPermission('mod_imagecategories', 'delete', $this->lang['mod_imagecategories']['role_errors']['delete'], 1) === false) {
            return false;
        }

        $query = Req::Post_Safe('query');
        if (!is_array($query) || empty($query)) {
            echo $this->lang['mod_imagecategories']['errors']['delete_failed'];
            return false;
        }

        foreach ($query as $id) {
            if ($this->deleteCategoryTree(intval($id)) !== true) {
                echo $this->lang['mod_imagecategories']['errors']['delete_failed'];
                return false;
            }
        }

        echo 'Oke';
        return true;
    }

    public function image_remove_image()
    {
        if ($this->user->hasPermission('mod_imagecategories', 'edit', $this->lang['mod_imagecategories']['role_errors']['edit'], 1) === false) {
            return false;
        }

        $id = intval(Req::Post_Safe('id'));
        $this->mod_imagecategories_model = Load::model('mod_imagecategories');
        $this->mod_imagecategories_model->where_id($id)->limit(1)->gRo();
        if ($this->mod_imagecategories_model->getSize() === 0) {
            echo $this->lang['mod_imagecategories']['errors']['record'];
            return false;
        }

        $this->mod_imagecategories_model->sync();
        if ($this->user->checkPermission('mod_imagecategories', 'moderate_edit', $this->mod_imagecategories_model->user_id, $this->lang['mod_imagecategories']['role_errors']['moderate_edit']) === false) {
            return false;
        }

        if (empty($this->mod_imagecategories_model->image)) {
            echo 'Oke';
            return true;
        }

        if ($this->deleteImageFiles($this->mod_imagecategories_model->image)) {
            $this->mod_imagecategories_model->image = '';
            $this->mod_imagecategories_model->update('id');
            echo 'Oke';
            return true;
        }

        echo $this->lang['mod_imagecategories']['errors']['image_remove_failed'];
        return false;
    }

    private function _add()
    {
        $this->val = Load::validator('mod_imagecategories.add');
        if ($this->val->check()) {
            $this->bindValidationInput();
            $this->view->veri('errors', $this->val->errors);
            return false;
        }

        $this->mod_imagecategories_model = Load::model('mod_imagecategories');
        $this->populateCategoryModel($this->mod_imagecategories_model);
        $this->mod_imagecategories_model->user_id = $this->session->get('id');

        $menuParentId = intval(Req::Post_Safe('menu'));
        $candidateRow = $this->categoryModelData($this->mod_imagecategories_model);
        if (!$this->validateCategoryParent($this->mod_imagecategories_model->lang, $this->mod_imagecategories_model->parent_id, null, $candidateRow) || !$this->validateMenuParent($this->mod_imagecategories_model->lang, $menuParentId)) {
            return false;
        }

        $this->mod_imagecategories_model->order = $this->nextOrder($this->mod_imagecategories_model->parent_id);

        $uploadedImage = $this->uploadImage('image');
        if ($uploadedImage === false) {
            return false;
        }
        if ($uploadedImage !== null) {
            $this->mod_imagecategories_model->image = $uploadedImage;
        }

        if ($this->mod_imagecategories_model->save(true)) {
            $category = $this->categoryModelData($this->mod_imagecategories_model);
            $category['menu'] = $menuParentId;
            $this->syncMenuOnCreate($category);
            $this->_success_message($this->lang['mod_imagecategories']['message']['success']['add']);
            return true;
        }

        if (!empty($uploadedImage)) {
            $this->deleteImageFiles($uploadedImage);
        }
        $this->_error_message($this->lang['mod_imagecategories']['message']['error']['add']);
        return false;
    }

    private function _edit($id, array $record)
    {
        $this->val = Load::validator('mod_imagecategories.edit');
        if ($this->val->check($id)) {
            $this->bindValidationInput();
            $this->view->veri('errors', $this->val->errors);
            return false;
        }

        $previousUrl = $this->categoryMenuUrl($record);
        $this->mod_imagecategories_model = Load::model('mod_imagecategories');
        $this->populateCategoryModel($this->mod_imagecategories_model);
        $this->mod_imagecategories_model->id = $id;
        $this->mod_imagecategories_model->user_id = $record['user_id'];

        $menuParentId = intval(Req::Post_Safe('menu'));
        $candidateRow = $this->categoryModelData($this->mod_imagecategories_model);
        if (!$this->validateCategoryParent($this->mod_imagecategories_model->lang, $this->mod_imagecategories_model->parent_id, $id, $candidateRow) || !$this->validateMenuParent($this->mod_imagecategories_model->lang, $menuParentId)) {
            return false;
        }

        $this->mod_imagecategories_model->order = (int) ($record['parent_id'] ?? 0) !== (int) $this->mod_imagecategories_model->parent_id
            ? $this->nextOrder($this->mod_imagecategories_model->parent_id)
            : $record['order'];

        $uploadedImage = $this->uploadImage('image');
        if ($uploadedImage === false) {
            return false;
        }
        if ($uploadedImage !== null) {
            $this->mod_imagecategories_model->image = $uploadedImage;
        }

        if ($this->mod_imagecategories_model->update('id')) {
            $updated = array_merge($record, $this->categoryModelData($this->mod_imagecategories_model));
            $updated['menu'] = $menuParentId;
            if ($uploadedImage !== null && !empty($record['image'])) {
                $this->deleteImageFiles($record['image']);
            }
            $this->syncMenuOnUpdate($previousUrl, $updated);
            $this->_success_message($this->lang['mod_imagecategories']['message']['success']['edit']);
            return true;
        }

        if (!empty($uploadedImage)) {
            $this->deleteImageFiles($uploadedImage);
        }
        $this->_error_message($this->lang['mod_imagecategories']['message']['error']['edit']);
        return false;
    }

    private function populateCategoryModel($model)
    {
        $model->lang = Req::Post_Safe('lang');
        $model->name = Req::Post_Safe('name');
        $model->content = Req::Post_Safe('content');
        $model->parent_id = intval(Req::Post_Safe('parent_id'));
        $model->root = Req::Post_Safe('root');
        $model->seo = html_entity_decode(trim(Req::Post('seo')), ENT_QUOTES, 'UTF-8');
        $model->title = Req::Post_Safe('title');
        $model->keywords = Req::Post_Safe('keywords');
        $model->description = Req::Post_Safe('description');
        $model->acl_id = intval(Req::Post_Safe('acl_id'));
    }

    private function categoryModelData($model)
    {
        $data = [];
        foreach (['id', 'lang', 'name', 'content', 'parent_id', 'order', 'root', 'seo', 'title', 'keywords', 'description', 'acl_id', 'image', 'user_id'] as $field) {
            if (isset($model->$field)) {
                $data[$field] = $model->$field;
            }
        }

        return $data;
    }

    private function bindValidationInput()
    {
        foreach ($_POST as $key => $value) {
            $targetKey = $key === 'lang' ? 'form_lang' : $key;
            $this->view->veri($targetKey, Req::Post_Safe($key));
        }
    }

    private function loadFormMetadata($selectedLang = '', $currentId = null, $selectedParentId = null, $currentRecordLang = '')
    {
        if ($selectedLang === '') {
            $selectedLang = $this->resolveSelectedLanguage($currentRecordLang);
        }

        $visibleLanguages = $this->getVisibleLanguages($currentRecordLang);
        $this->view->veri('selected_lang', $selectedLang);
        $this->view->veri('lang_lang', $visibleLanguages);

        $categoryRows = $this->getAllCategoryRows();
        $languageRootRow = $this->findLanguageRootRow($categoryRows, $selectedLang);
        $languageRootId = $languageRootRow !== null ? (int) $languageRootRow['id'] : 0;
        $currentRow = null;
        if ($currentId !== null) {
            foreach ($categoryRows as $row) {
                if ((int) ($row['id'] ?? 0) === (int) $currentId) {
                    $currentRow = $row;
                    break;
                }
            }
        }

        $filteredCategories = [];
        foreach ($categoryRows as $row) {
            if (($row['lang'] ?? '') !== $selectedLang) {
                continue;
            }

            if ($currentId !== null && (int) ($row['id'] ?? 0) === (int) $currentId) {
                continue;
            }

            if ($this->isLanguageRootRow($row)) {
                continue;
            }

            if ($languageRootId > 0 && (int) ($row['parent_id'] ?? 0) === 0) {
                $row['parent_id'] = $languageRootId;
            }
            $row['display_name'] = $this->resolveDisplayName($row, $visibleLanguages);
            $filteredCategories[] = $row;
        }

        $languageRootLabel = '';
        if ($languageRootRow !== null) {
            $resolvedLanguageName = $this->resolveDisplayName($languageRootRow, $visibleLanguages);
            $languageRootLabel = str_replace(':language', $resolvedLanguageName, $this->lang['mod_imagecategories']['parent_labels']['language_root']);
        }

        $selectedParentId = $selectedParentId !== null ? (int) $selectedParentId : null;
        $allowSystemParent = $currentRow !== null && $this->isLanguageRootRow($currentRow);
        if ($allowSystemParent) {
            $filteredCategories = [];
            $languageRootId = 0;
            $languageRootLabel = '';
            $selectedParentId = 0;
        }
        $defaultParentId = $languageRootId > 0 ? $languageRootId : 0;
        if ($selectedParentId === 0 && !$allowSystemParent && $languageRootId > 0) {
            $selectedParentId = $languageRootId;
        }

        $this->view->veri('select_nested_parent_id_result', $this->buildNestedRows($filteredCategories));
        $this->view->veri('category_parent_root_id', $languageRootId);
        $this->view->veri('category_parent_default_id', $defaultParentId);
        $this->view->veri('category_selected_parent_id', $selectedParentId !== null ? $selectedParentId : $defaultParentId);
        $this->view->veri('category_language_root_label', $languageRootLabel);
        $this->view->veri('category_allow_system_parent', $allowSystemParent);

        $menuLang = Load::lang('mod_menu');
        $menuRows = $this->getAllMenuRows();
        $menuLanguageRootRow = $this->findMenuLanguageRootRow($menuRows, $selectedLang);
        $menuLanguageRootId = $menuLanguageRootRow !== null ? (int) $menuLanguageRootRow['id'] : 0;
        $filteredMenus = [];
        foreach ($menuRows as $row) {
            if (($row['lang'] ?? '') !== $selectedLang) {
                continue;
            }

            if ($this->isMenuLanguageRootRow($row)) {
                continue;
            }

            if ($menuLanguageRootId > 0 && (int) ($row['parent_id'] ?? 0) === 0) {
                $row['parent_id'] = $menuLanguageRootId;
            }
            $row['display_name'] = $this->resolveMenuDisplayName($row, $visibleLanguages, $menuLang);
            $filteredMenus[] = $row;
        }
        $this->view->veri('select_nested_menu_result', $this->buildNestedRows($filteredMenus));
        $this->view->veri('category_menu_root_id', $menuLanguageRootId);

        $this->db->fetch_array("SELECT id, name FROM mod_acl ORDER BY name ASC");
        $this->view->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    private function loadCategoryTree()
    {
        $visibleLanguages = $this->getVisibleLanguages();
        $visibleLangs = $this->getVisibleLanguageCodes();
        $visibleRows = [];
        foreach ($this->getAllCategoryRows() as $entry) {
            if (!isset($entry['lang']) || in_array((string) $entry['lang'], $visibleLangs, true)) {
                $entry['display_name'] = $this->resolveDisplayName($entry, $visibleLanguages);
                $visibleRows[] = $entry;
            }
        }

        return $this->buildNestedRows($visibleRows);
    }

    private function defaultFormData()
    {
        return [
            'lang' => $this->site['lang'] ?? 'en',
            'name' => '',
            'content' => '',
            'parent_id' => 0,
            'root' => 'n',
            'seo' => '',
            'title' => '',
            'keywords' => '',
            'description' => '',
            'menu' => 0,
            'acl_id' => 0,
            'image' => '',
        ];
    }

    private function withMenuDefaults(array $record)
    {
        $menu = $this->getMenuRowByUrl($this->categoryMenuUrl($record));
        $record['menu'] = is_array($menu) ? (int) ($menu['parent_id'] ?? 0) : 0;
        $record['acl_id'] = isset($record['acl_id']) ? (int) $record['acl_id'] : 0;

        return $record;
    }

    private function getMenuRowByUrl($menuUrl)
    {
        $menuModel = Load::model('mod_menu');
        if ($menuModel->exists_url($menuUrl)) {
            return $menuModel->getResultOne()->toArray();
        }

        return null;
    }

    private function nextOrder($parentId)
    {
        $result = $this->db->fetch_array("SELECT MAX(`order`) AS max_order FROM mod_imagecategories WHERE parent_id = " . intval($parentId))->getResult()->toArray();
        return isset($result[0]['max_order']) ? intval($result[0]['max_order']) + 1 : 1;
    }

    private function nextMenuSiblingOrder($parentId, $lang)
    {
        $query = "SELECT MAX(`order`) AS max_order FROM `mod_menu` WHERE parent_id=" . intval($parentId);
        $lang = trim((string) $lang);
        if ($lang !== '') {
            $query .= " AND lang='" . Req::Clear($lang) . "'";
        }

        $this->db->fetch_array($query);
        $rows = $this->db->getResult()->toArray();
        $maxOrder = isset($rows[0]['max_order']) && Req::isNumeric((string) $rows[0]['max_order']) ? (int) $rows[0]['max_order'] : 0;

        return $maxOrder + 1;
    }

    private function imageOptions()
    {
        return [
            '/' => [
                'width' => 400,
                'height' => null,
                'option' => 'width',
            ],
            '/thumb' => [
                'width' => 200,
                'height' => null,
                'option' => 'width',
            ],
        ];
    }

    private function uploadImage($fieldName)
    {
        if (!isset($_FILES[$fieldName]['name']) || $_FILES[$fieldName]['name'] === '') {
            return null;
        }

        $image = Load::mod('image');
        $image->mod = 'mod_imagecategories';
        $image->name = $fieldName;

        if ($image->_resize($this->imageOptions())) {
            return $image->url;
        }

        $this->_error_message($image->error);
        return false;
    }

    private function deleteImageFiles($imageUrl)
    {
        $image = Load::mod('image');
        $image->mod = 'mod_imagecategories';
        $image->url = $imageUrl;
        return $image->_delete_files($this->imageOptions());
    }

    private function categoryMenuUrl(array $category)
    {
        return $category['lang'] . '/rkat/' . $category['seo'];
    }

    private function syncMenuOnCreate(array $category)
    {
        if (intval($category['menu']) === 0) {
            return;
        }

        $menuModel = Load::model('mod_menu');
        $menuModel->parent_id = intval($category['menu']);
        $menuModel->name = $category['name'];
        $menuModel->lang = $category['lang'];
        $menuModel->url = $this->categoryMenuUrl($category);
        $menuModel->acl_id = intval($category['acl_id']);
        $menuModel->target = 'n';
        $menuModel->status = 'y';
        $menuModel->order = $this->nextMenuSiblingOrder($menuModel->parent_id, $category['lang']);
        $menuModel->user_id = $this->session->get('id');
        $menuModel->save();
    }

    private function syncMenuOnUpdate($previousUrl, array $category)
    {
        $menuModel = Load::model('mod_menu');
        if (intval($category['menu']) === 0) {
            $this->deleteMenuByUrl($previousUrl);
            return;
        }

        if ($menuModel->exists_url($previousUrl)) {
            $menuModel->getResultOne()->sync();
            $previousParentId = (int) $menuModel->parent_id;
            $menuModel->name = $category['name'];
            $menuModel->lang = $category['lang'];
            $menuModel->url = $this->categoryMenuUrl($category);
            $menuModel->acl_id = intval($category['acl_id']);
            $menuModel->parent_id = intval($category['menu']);
            $menuModel->target = 'n';
            $menuModel->status = 'y';
            if ($previousParentId !== (int) $category['menu']) {
                $menuModel->order = $this->nextMenuSiblingOrder($category['menu'], $category['lang']);
            }
            $menuModel->update();
            return;
        }

        $menuModel->parent_id = intval($category['menu']);
        $menuModel->name = $category['name'];
        $menuModel->lang = $category['lang'];
        $menuModel->url = $this->categoryMenuUrl($category);
        $menuModel->acl_id = intval($category['acl_id']);
        $menuModel->target = 'n';
        $menuModel->status = 'y';
        $menuModel->order = $this->nextMenuSiblingOrder($menuModel->parent_id, $category['lang']);
        $menuModel->user_id = $this->session->get('id');
        $menuModel->save();
    }

    private function deleteMenuByUrl($menuUrl)
    {
        $menuModel = Load::model('mod_menu');
        if ($menuModel->exists_url($menuUrl)) {
            $menuModel->getResultOne()->delete();
        }
    }

    private function applySiblingOrder()
    {
        $orderArray = Req::Post('order');
        if (empty($orderArray) || !is_array($orderArray)) {
            return true;
        }

        $order = 1;
        foreach ($orderArray as $categoryId) {
            $id = intval($categoryId);
            if ($id > 0) {
                $this->db->query("UPDATE `mod_imagecategories` SET `order` = '$order' WHERE `id` = '$id'");
            }
            $order++;
        }

        return true;
    }

    public function move()
    {
        if ($this->user->hasPermission('mod_imagecategories', 'edit', $this->lang['mod_imagecategories']['role_errors']['edit']) === false) {
            return false;
        }

        $id = intval(Req::Post_Safe('id'));
        $parentId = intval(Req::Post_Safe('parent_id'));

        if ($id <= 0) {
            echo $this->lang['mod_imagecategories']['errors']['record'];
            return false;
        }

        $rows = $this->getAllCategoryRows();
        $rowsById = $this->categoryRowsById($rows);
        if (!isset($rowsById[$id])) {
            echo $this->lang['mod_imagecategories']['errors']['record'];
            return false;
        }

        $movingRow = $rowsById[$id];
        if (!$this->validateCategoryParent($movingRow['lang'] ?? '', $parentId, $id, $movingRow)) {
            echo $this->lastErrorMessage !== '' ? $this->lastErrorMessage : $this->lang['mod_imagecategories']['errors']['parent_language_mismatch'];
            return false;
        }

        if ($this->db->query("UPDATE `mod_imagecategories` SET `parent_id` = '$parentId' WHERE `id` = '$id'")) {
            if ($this->applySiblingOrder()) {
                echo 'Oke';
                return true;
            }

            echo $this->lang['mod_imagecategories']['errors']['order_update_failed'];
            return false;
        }

        echo $this->lang['mod_imagecategories']['errors']['order_update_failed'];
        return false;
    }

    private function deleteCategoryTree($id)
    {
        Load::model('mod_imagecategories');
        $model = new mod_imagecategories_model();
        $model->getid($id)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            return true;
        }

        $model->sync();
        if ($this->user->checkPermission('mod_imagecategories', 'moderate_delete', $model->user_id, $this->lang['mod_imagecategories']['role_errors']['moderate_delete']) === false) {
            return false;
        }

        $categoryRow = $model->toArray();
        if (!is_array($categoryRow)) {
            return false;
        }

        $children = Load::model('mod_imagecategories');
        $children->where_parent_id($id)->all();
        $childRows = $children->toArray();
        if (!is_array($childRows)) {
            $childRows = [];
        }

        foreach ($childRows as $child) {
            if ($this->deleteCategoryTree($child['id']) !== true) {
                return false;
            }
        }

        if (!empty($categoryRow['image'])) {
            $this->deleteImageFiles($categoryRow['image']);
        }

        $galleryModule = Load::model('mod_gallery_mod');
        if ($galleryModule->exists_mod_name('mod_imagecategories') === true) {
            Run::import('mod_gallery', '_remove', ['mod_imagecategories', $id]);
        }

        $attachmentModule = Load::model('mod_attachment_mod');
        if ($attachmentModule->exists_mod_name('mod_imagecategories') === true) {
            Run::import('mod_attachment', '_remove', ['mod_imagecategories', $id]);
        }

        $this->deleteMenuByUrl($this->categoryMenuUrl($categoryRow));
        $model->delete();
        return true;
    }

    private function _success_message($message)
    {
        $this->view->veri('success', true);
        $this->view->veri('message', $message);
    }

    private function _error_message($message)
    {
        $this->view->veri('error', true);
        $this->view->veri('errors', [$message]);
    }

    private function _fatal_error($message)
    {
        $this->view->veri('fatal_error', true);
        $this->view->veri('message', $message);
    }
}
