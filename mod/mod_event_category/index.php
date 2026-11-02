<?php
/**
 * Amed Framework
 * mod_event_category Index
 * mod/mod_event_category/index.php
 */

class mod_event_category_mod extends indexMain
{
    public $db;
    public $user;
    public $view;
    public $lang;
    public $session;
    private $lastErrorMessage = '';
    private $menuSlotAliases = [
        'header' => ['##HEADER MENU', 'HEADER MENU', '##HEADER', 'HEADER', 'TOP MENU', 'MAIN MENU'],
        'footer' => ['##FOOTER MENU', 'FOOTER MENU', '##FOOTER', 'FOOTER', 'BOTTOM MENU'],
        'sidebar' => ['##SIDEBAR', 'SIDEBAR', 'SIDE MENU'],
        'action' => ['##ACTION MENU', 'ACTION MENU', '##ACTION', 'ACTION', 'CTA MENU', '##CTA MENU', 'ACTIONMENU', 'ACTION_MENU', 'PESEKA CALAKIYE', 'ÇALAKÎ', 'AKTIONSMENU', 'AKTIONSMENÜ', 'EYLEM MENUSU'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->db = Load::lib('Database');
        $this->user = Load::model('mod_user.user');
        $this->view = Load::lib('AdminTemplate');
        $this->session = Load::lib('Session');
        $this->lang = Load::lang('mod_event_category');

        $this->view->lang($this->lang);
    }

    private function isCategoryContainerRow(array $row)
    {
        return isset($row['name']) && strpos(trim((string) $row['name']), '##') === 0;
    }

    private function isLanguageRootRow(array $row)
    {
        return $this->isCategoryContainerRow($row)
            && (int) ($row['parent_id'] ?? -1) === 0
            && (($row['root'] ?? '') === 'y');
    }

    public function resolveDisplayName($row, array $visibleLanguages = [])
    {
        $name = is_array($row) ? ($row['name'] ?? '') : (string) $row;
        if (strpos($name, '##') === 0) {
            if (is_array($row) && (int) ($row['parent_id'] ?? -1) === 0 && $visibleLanguages !== []) {
                $langCode = isset($row['lang']) ? $row['lang'] : '';
                foreach ($visibleLanguages as $language) {
                    if (($language['seo'] ?? '') === $langCode) {
                        return $language['name'];
                    }
                }
            }

            $langKey = substr($name, 2);
            return isset($this->lang['mod_event_category'][$langKey]) ? $this->lang['mod_event_category'][$langKey] : str_replace('_', ' ', $langKey);
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
        if ($rows === []) {
            return [];
        }

        $nested = Helper::nested($rows, 'parent_id', 'id');
        return is_array($nested) ? $nested : [];
    }

    private function getAllCategoryRows()
    {
        $this->db->fetch_array("SELECT * FROM `mod_event_category` ORDER BY `order` ASC, `id` ASC");
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

    private function ensureLanguageRootCategories()
    {
        $visibleLanguages = $this->getVisibleLanguages();
        $rows = $this->getAllCategoryRows();
        foreach ($visibleLanguages as $language) {
            $langCode = $language['seo'] ?? '';
            if ($langCode === '') {
                continue;
            }
            $found = false;
            foreach ($rows as $row) {
                if (($row['lang'] ?? '') === $langCode && $this->isLanguageRootRow($row)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $categoryModel = Load::model('mod_event_category');
                $categoryModel->name = '##' . strtoupper($langCode);
                $categoryModel->lang = $langCode;
                $categoryModel->parent_id = 0;
                $categoryModel->root = 'y';
                $categoryModel->show_on_homepage = 'n';
                $categoryModel->seo = $langCode;
                $categoryModel->order = 0;
                $categoryModel->user_id = $this->session->get('id') ? (int) $this->session->get('id') : 1;
                $categoryModel->save();
            }
        }
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

        $defaultLang = Registry::get('default_lang');
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
            return ltrim($name, '#');
        }

        foreach (array_keys($this->menuSlotAliases) as $slot) {
            if ($this->rowMatchesMenuSlotAlias($row, $slot)) {
                return isset($menuLang['mod_menu']['slots'][$slot]) ? $menuLang['mod_menu']['slots'][$slot] : ltrim($name, '#');
            }
        }

        if ($this->isMenuContainerRow($row)) {
            return ltrim($name, '#');
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
        $this->view->set('error', true);
        $this->view->set('message', $message);
    }

    private function validateCategoryParent($lang, $parentId, $currentId = null, array $candidateRow = [])
    {
        $parentId = intval($parentId);
        $rows = $this->getAllCategoryRows();
        $rowsById = $this->categoryRowsById($rows);
        $languageRootRow = $this->findLanguageRootRow($rows, $lang);

        if ($parentId === 0) {
            if ($currentId !== null && isset($rowsById[(int) $currentId])) {
                $existingRow = $rowsById[(int) $currentId];
                $currentRow = array_merge($existingRow, $candidateRow);
                $currentRow['parent_id'] = 0;
                if ($this->isLanguageRootRow($existingRow) && $this->isLanguageRootRow($currentRow) && (($existingRow['lang'] ?? '') === $lang)) {
                    return true;
                }
            }

            if ($languageRootRow === null) {
                return true;
            }

            $this->setFormError($this->lang['mod_event_category']['errors']['parent_selection_required']);
            return false;
        }

        if (!isset($rowsById[$parentId])) {
            $this->setFormError($this->lang['mod_event_category']['errors']['record']);
            return false;
        }

        if ($currentId !== null && $this->isDescendantOf($rowsById, $parentId, $currentId)) {
            $this->setFormError($this->lang['mod_event_category']['errors']['parent_cycle']);
            return false;
        }

        $parentLang = $this->resolveCategoryLanguageFromAncestry($rowsById, $rowsById[$parentId]);
        if ($parentLang !== '' && $parentLang !== $lang) {
            $this->setFormError($this->lang['mod_event_category']['errors']['parent_language_mismatch']);
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
            $this->setFormError($this->lang['mod_event_category']['errors']['menu_parent_not_found']);
            return false;
        }

        $parentLang = $this->resolveMenuLanguageFromAncestry($rowsById, $rowsById[$menuParentId]);
        if ($parentLang !== '' && $parentLang !== $lang) {
            $this->setFormError($this->lang['mod_event_category']['errors']['menu_language_mismatch']);
            return false;
        }

        return true;
    }

    private function nextCategorySiblingOrder($parentId)
    {
        $this->db->fetch_array("SELECT `order` FROM `mod_event_category` WHERE parent_id=" . intval($parentId) . " ORDER BY `order` DESC LIMIT 1");
        $rows = $this->db->getResult()->toArray();
        $maxOrder = isset($rows[0]['order']) && Req::isNumeric((string) $rows[0]['order']) ? (int) $rows[0]['order'] : 0;

        return $maxOrder + 1;
    }

    private function nextMenuSiblingOrder($parentId, $lang)
    {
        $query = "SELECT `order` FROM `mod_menu` WHERE parent_id=" . intval($parentId);
        $lang = trim((string) $lang);
        if ($lang !== '') {
            $query .= " AND lang='" . Req::Clear($lang) . "'";
        }
        $query .= " ORDER BY `order` DESC LIMIT 1";

        $this->db->fetch_array($query);
        $rows = $this->db->getResult()->toArray();
        $maxOrder = isset($rows[0]['order']) && Req::isNumeric((string) $rows[0]['order']) ? (int) $rows[0]['order'] : 0;

        return $maxOrder + 1;
    }

    public function index()
    {
        $this->list();
    }

    public function list()
    {
        if ($this->user->hasPermission('mod_event_category', 'list', $this->lang['mod_event_category']['role_errors']['list'], 1) === false) {
            return false;
        }

        $this->ensureLanguageRootCategories();

        $visibleLanguages = $this->getVisibleLanguages();
        $visibleLangs = $this->getVisibleLanguageCodes();
        $visibleRows = [];
        foreach ($this->getAllCategoryRows() as $entry) {
            if (!isset($entry['lang']) || in_array((string) $entry['lang'], $visibleLangs, true)) {
                $entry['display_name'] = $this->resolveDisplayName($entry, $visibleLanguages);
                $visibleRows[] = $entry;
            }
        }

        Run::import('management', '_index');
        $this->view->set('category_tree', $this->buildNestedRows($visibleRows));
        $this->view->tpl('admin/mod_event_category/list');
    }

    private function imageOptions()
    {
        return [
            '/' => ['width' => 200, 'height' => null, 'option' => 'width'],
            '/thumb' => ['width' => 100, 'height' => null, 'option' => 'width'],
        ];
    }

    private function uploadImageOrFail($fieldName = 'image')
    {
        if (!isset($_FILES[$fieldName]['name']) || $_FILES[$fieldName]['name'] === '') {
            return null;
        }

        $image = Load::mod('image');
        $image->mod = 'mod_event_category';
        $image->name = $fieldName;

        if ($image->_resize($this->imageOptions())) {
            return $image->url;
        }

        $this->view->set('error', true);
        $this->view->set('message', $image->error);

        return false;
    }

    private function rollbackUploadedImage($imageUrl)
    {
        if (empty($imageUrl)) {
            return;
        }

        $image = Load::mod('image');
        $image->mod = 'mod_event_category';
        $image->url = $imageUrl;
        $image->_delete_files($this->imageOptions());
    }

    private function categoryDataFromPost(array $base = [])
    {
        $base['name'] = Req::Post_Safe('name');
        $base['lang'] = Req::Post_Safe('lang');
        $base['parent_id'] = intval(Req::Post_Safe('parent_id'));
        $base['root'] = Req::Post_Safe('root');
        $base['show_on_homepage'] = Req::Post_Safe('show_on_homepage');
        $base['seo'] = Req::Post_Safe('seo');
        $base['title'] = Req::Post_Safe('title');
        $base['keywords'] = Req::Post_Safe('keywords');
        $base['description'] = Req::Post('description');
        $base['menu'] = intval(Req::Post_Safe('menu'));
        $base['acl_id'] = intval(Req::Post_Safe('acl_id'));

        return $base;
    }

    private function loadFormMetadata($selectedLang = '', $currentId = null, $selectedParentId = null, $currentRecordLang = '')
    {
        if ($selectedLang === '') {
            $selectedLang = $this->resolveSelectedLanguage($currentRecordLang);
        }

        $visibleLanguages = $this->getVisibleLanguages($currentRecordLang);
        $this->view->set('selected_lang', $selectedLang);
        $this->view->set('lang_lang', $visibleLanguages);

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
            $languageRootLabel = str_replace(':language', $resolvedLanguageName, $this->lang['mod_event_category']['parent_labels']['language_root']);
        }

        $selectedParentId = $selectedParentId !== null ? (int) $selectedParentId : null;
        $allowSystemParent = $currentRow !== null && $this->isLanguageRootRow($currentRow);
        $defaultParentId = $languageRootId > 0 ? $languageRootId : 0;
        if ($selectedParentId === 0 && !$allowSystemParent && $languageRootId > 0) {
            $selectedParentId = $languageRootId;
        }

        $this->view->set('select_nested_parent_id_result', $this->buildNestedRows($filteredCategories));
        $this->view->set('category_parent_root_id', $languageRootId);
        $this->view->set('category_parent_default_id', $defaultParentId);
        $this->view->set('category_selected_parent_id', $selectedParentId !== null ? $selectedParentId : $defaultParentId);
        $this->view->set('category_language_root_label', $languageRootLabel);
        $this->view->set('category_allow_system_parent', $allowSystemParent);

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
        $this->view->set('select_nested_menu_result', $this->buildNestedRows($filteredMenus));
        $this->view->set('category_menu_root_id', $menuLanguageRootId);

        $this->db->fetch_array("SELECT id, name FROM mod_acl ORDER BY name ASC");
        $this->view->set('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    private function syncMenuOnCreate($categoryModel)
    {
        if ((int) Req::Post_Safe('menu') === 0) {
            return;
        }

        $parentId = (int) Req::Post_Safe('menu');
        $menuModel = Load::model('mod_menu');
        $menuModel->parent_id = $parentId;
        $menuModel->name = $categoryModel->name;
        $menuModel->lang = $categoryModel->lang;
        $menuModel->url = $categoryModel->lang . '/events/' . $categoryModel->seo;
        $menuModel->target = 'n';
        $menuModel->status = 'y';
        $menuModel->order = $this->nextMenuSiblingOrder($parentId, $categoryModel->lang);
        $menuModel->acl_id = $categoryModel->acl_id;
        $menuModel->user_id = $this->session->get('id');
        $menuModel->save();
    }

    private function syncMenuOnUpdate($previousUrl, $categoryModel)
    {
        $menuModel = Load::model('mod_menu');
        $requestedParentId = (int) Req::Post_Safe('menu');
        if ($menuModel->exists_url($previousUrl)) {
            $menuModel->getResultOne()->sync();
            if ($requestedParentId === 0) {
                $menuModel->delete();
                return;
            }

            $previousParentId = (int) $menuModel->parent_id;
            $menuModel->name = $categoryModel->name;
            $menuModel->lang = $categoryModel->lang;
            $menuModel->url = $categoryModel->lang . '/events/' . $categoryModel->seo;
            $menuModel->acl_id = $categoryModel->acl_id;
            $menuModel->parent_id = $requestedParentId;
            if ($previousParentId !== $requestedParentId) {
                $menuModel->order = $this->nextMenuSiblingOrder($requestedParentId, $categoryModel->lang);
            }
            $menuModel->update();
            return;
        }

        if ($requestedParentId !== 0) {
            $this->syncMenuOnCreate($categoryModel);
        }
    }

    private function deleteCategoryTree($id)
    {
        Load::model('mod_event_category');
        $categoryModel = new mod_event_category_model();
        $categoryModel->getid($id)->getResultOne(true, true);
        if ($categoryModel->getSize() === 0) {
            print($this->lang['mod_event_category']['errors']['record']);
            return false;
        }
        $categoryModel->sync();

        if ($this->user->checkPermission('mod_event_category', 'moderate_delete', $categoryModel->user_id, $this->lang['mod_event_category']['role_errors']['moderate_delete']) === false) {
            return false;
        }

        $childrenModel = new mod_event_category_model();
        if ($childrenModel->exists_parent_id($id)) {
            foreach ($childrenModel->toArray() as $child) {
                if ($this->deleteCategoryTree($child['id']) !== true) {
                    return false;
                }
            }
        }

        if ($categoryModel->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_event_category';
            $image->url = $categoryModel->image;
            $image->_delete_files($this->imageOptions());
        }

        $galleryMod = Load::model('mod_gallery_mod');
        if ($galleryMod->exists_mod_name('mod_event_category') === true) {
            Run::import('mod_gallery', '_remove', ['mod_event_category', $categoryModel->id]);
        }

        $attachmentMod = Load::model('mod_attachment_mod');
        if ($attachmentMod->exists_mod_name('mod_event_category') === true) {
            Run::import('mod_attachment', '_remove', ['mod_event_category', $categoryModel->id]);
        }

        $menuUrl = $categoryModel->lang . '/events/' . $categoryModel->seo;
        $menuModel = Load::model('mod_menu');
        if ($menuModel->exists_url($menuUrl)) {
            $menuModel->getResultOne()->delete();
        }

        $categoryModel->delete();
        return true;
    }

    public function add()
    {
        if ($this->user->hasPermission('mod_event_category', 'add', $this->lang['mod_event_category']['role_errors']['add'], 1) === false) {
            return false;
        }

        if (Req::is_Post('add')) {
            $this->val = Load::validator('mod_event_category.add');
            if ($this->val->check()) {
                foreach ($_POST as $key => $val) {
                    $this->view->set($key, Req::Post_Safe($key));
                }
                $this->view->set('errors', $this->val->errors);
                $this->view->set('error', true);
                $this->view->set('message', $this->lang['mod_event_category']['messages']['error']['validation']);
            } else {
                Load::model('mod_event_category');
                $categoryModel = new mod_event_category_model();
                $formData = $this->categoryDataFromPost();

                $categoryModel->name = $formData['name'];
                $categoryModel->lang = $formData['lang'];
                $categoryModel->parent_id = $formData['parent_id'];
                $categoryModel->root = $formData['root'];
                $categoryModel->show_on_homepage = $formData['show_on_homepage'];
                $categoryModel->seo = $formData['seo'];
                $categoryModel->title = $formData['title'];
                $categoryModel->keywords = $formData['keywords'];
                $categoryModel->description = $formData['description'];
                $categoryModel->menu = $formData['menu'];
                $categoryModel->acl_id = $formData['acl_id'];
                $categoryModel->user_id = $this->session->get('id');

                if (!$this->validateCategoryParent($categoryModel->lang, $categoryModel->parent_id) || !$this->validateMenuParent($categoryModel->lang, $categoryModel->menu)) {
                    foreach ($formData as $key => $value) {
                        $this->view->set($key, $value);
                    }
                } else {
                    $categoryModel->order = $this->nextCategorySiblingOrder($categoryModel->parent_id);

                    $uploadedImage = $this->uploadImageOrFail('image');
                    if ($uploadedImage === false) {
                        foreach ($formData as $key => $value) {
                            $this->view->set($key, $value);
                        }
                    } else {
                        if ($uploadedImage !== null) {
                            $categoryModel->image = $uploadedImage;
                        }

                        if ($categoryModel->save()) {
                            $this->syncMenuOnCreate($categoryModel);
                            $this->view->set('Success', true);
                            $this->view->set('message', $this->lang['mod_event_category']['messages']['success']['added']);
                        } else {
                            $this->rollbackUploadedImage($uploadedImage);
                            foreach ($formData as $key => $value) {
                                $this->view->set($key, $value);
                            }
                            $this->setFormError($this->lang['mod_event_category']['messages']['error']['not_added']);
                        }
                    }
                }
            }
        }

        $selectedParentId = Req::is_Post('parent_id') ? Req::Post_Safe('parent_id') : null;
        $this->loadFormMetadata($this->resolveSelectedLanguage(), null, $selectedParentId);
        Run::import('management', '_index');
        $this->view->tpl('admin/mod_event_category/add');
    }

    public function edit($id)
    {
        if ($this->user->hasPermission('mod_event_category', 'edit', $this->lang['mod_event_category']['role_errors']['edit'], 1) === false) {
            return false;
        }

        Load::model('mod_event_category');
        $categoryModel = new mod_event_category_model();
        $id = Req::Url_Clear($id);
        if ($categoryModel->exists_id($id) === false) {
            $this->view->set('error', true);
            $this->view->set('message', $this->lang['mod_event_category']['errors']['record']);
            $this->loadFormMetadata();
            Run::import('management', '_index');
            $this->view->tpl('admin/mod_event_category/edit');
            return;
        }

        $existing = $categoryModel->getResultOne()->toArray();
        $viewData = $existing;
        $reloadFromDb = true;
        if ($this->user->checkPermission('mod_event_category', 'moderate_edit', $existing['user_id'], $this->lang['mod_event_category']['role_errors']['moderate_edit'], 1) === false) {
            return false;
        }

        if (Req::is_Post('edit')) {
            $this->val = Load::validator('mod_event_category.edit');
            if ($this->val->check($id)) {
                $this->view->set('errors', $this->val->errors);
                $this->view->set('error', true);
                $this->view->set('message', $this->lang['mod_event_category']['messages']['error']['validation']);
                $viewData = $this->categoryDataFromPost($existing);
                $reloadFromDb = false;
            } else {
                $categoryModel = new mod_event_category_model();
                $categoryModel->id = $id;
                $formData = $this->categoryDataFromPost(['id' => $id]);

                $categoryModel->name = $formData['name'];
                $categoryModel->lang = $formData['lang'];
                $categoryModel->parent_id = $formData['parent_id'];
                $categoryModel->root = $formData['root'];
                $categoryModel->show_on_homepage = $formData['show_on_homepage'];
                $categoryModel->seo = $formData['seo'];
                $categoryModel->title = $formData['title'];
                $categoryModel->keywords = $formData['keywords'];
                $categoryModel->description = $formData['description'];
                $categoryModel->menu = $formData['menu'];
                $categoryModel->acl_id = $formData['acl_id'];

                if (!$this->validateCategoryParent($categoryModel->lang, $categoryModel->parent_id, $id, $formData) || !$this->validateMenuParent($categoryModel->lang, $categoryModel->menu)) {
                    $viewData = array_merge($existing, $formData);
                    $reloadFromDb = false;
                } else {
                    if ((int) ($existing['parent_id'] ?? 0) !== (int) $categoryModel->parent_id) {
                        $categoryModel->order = $this->nextCategorySiblingOrder($categoryModel->parent_id);
                    }

                    $uploadedImage = $this->uploadImageOrFail('image');
                    if ($uploadedImage === false) {
                        $viewData = array_merge($existing, $formData);
                        $reloadFromDb = false;
                    } else {
                        if ($uploadedImage !== null) {
                            $categoryModel->image = $uploadedImage;
                        }

                        if ($categoryModel->update('id')) {
                            $this->syncMenuOnUpdate($existing['lang'] . '/events/' . $existing['seo'], $categoryModel);
                            $this->view->set('Success', true);
                            $this->view->set('message', $this->lang['mod_event_category']['messages']['success']['updated']);
                        } else {
                            $this->rollbackUploadedImage($uploadedImage);
                            $this->setFormError($this->lang['mod_event_category']['messages']['error']['not_updated']);
                        }
                    }
                }
            }
        }

        $res = $reloadFromDb ? $categoryModel->where_id($id)->gRo()->toArray() : $viewData;
        $selectedLang = Req::is_Post('lang') ? Req::Post_Safe('lang') : ($res['lang'] ?? $existing['lang']);
        $selectedParentId = Req::is_Post('parent_id') ? Req::Post_Safe('parent_id') : ($res['parent_id'] ?? $existing['parent_id']);
        $this->loadFormMetadata($selectedLang, $id, $selectedParentId, $existing['lang']);

        Run::import('management', '_index');
        $this->view->set('d', $res);
        $this->view->tpl('admin/mod_event_category/edit');
    }

    public function image_remove_image()
    {
        if ($this->user->hasPermission('mod_event_category', 'edit', $this->lang['mod_event_category']['role_errors']['edit'], 1) === false) {
            return false;
        }

        $id = intval(Req::Post_Safe('id'));
        Load::model('mod_event_category');
        $categoryModel = new mod_event_category_model();
        $categoryModel->where_id($id)->gRo();
        if ($categoryModel->getSize() === 0) {
            echo $this->lang['mod_event_category']['errors']['record'];
            return false;
        }

        $categoryModel->sync();
        if ($this->user->checkPermission('mod_event_category', 'moderate_edit', $categoryModel->user_id, $this->lang['mod_event_category']['role_errors']['moderate_edit']) === false) {
            echo $this->lang['mod_event_category']['role_errors']['moderate_edit'];
            return false;
        }

        if ($categoryModel->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_event_category';
            $image->url = $categoryModel->image;
            if ($image->_delete_files($this->imageOptions())) {
                $categoryModel->image = '';
                $categoryModel->update('id');
                echo 'Success';
            } else {
                echo $image->error;
                return false;
            }
        } else {
            echo 'Success';
        }
    }

    public function toggle_homepage()
    {
        if ($this->user->hasPermission('mod_event_category', 'edit', $this->lang['mod_event_category']['role_errors']['edit'], 1) === false) {
            return false;
        }

        $id = intval(Req::Post_Safe('id'));
        if ($id > 0) {
            Load::model('mod_event_category');
            $category = new mod_event_category_model();
            $category->where_id($id)->gRo();

            if ($category->getSize() > 0) {
                $category->sync();
                $newStatus = ($category->show_on_homepage === 'y') ? 'n' : 'y';

                $this->db->query("UPDATE `mod_event_category` SET `show_on_homepage` = '$newStatus' WHERE `id` = '$id'");
                echo 'Success';
            } else {
                echo $this->lang['mod_event_category']['errors']['record'];
            }
        } else {
            echo 'IDError';
        }
    }

    public function delete()
    {
        if ($this->user->hasPermission('mod_event_category', 'delete', $this->lang['mod_event_category']['role_errors']['delete']) === false) {
            return false;
        }

        $idParam = Req::Post_Safe('id');
        if ($idParam === null || $idParam === '') {
            $idParam = Req::Post_Safe('delete');
        }

        $ids = is_array($idParam) ? $idParam : [$idParam];

        foreach ($ids as $id) {
            $id = intval($id);
            if ($id > 0 && $this->deleteCategoryTree($id) !== true) {
                echo 'ERROR';
                return false;
            }
        }
        echo 'Success';
    }

    public function dragdrop_delete()
    {
        $this->delete();
    }

    public function _order_change()
    {
        $orderArray = Req::Post('order');
        if (empty($orderArray) || !is_array($orderArray)) {
            return true;
        }

        $x = 1;
        foreach ($orderArray as $categoryId) {
            $id = intval($categoryId);
            if ($id > 0) {
                $this->db->query("UPDATE `mod_event_category` SET `order` = '$x' WHERE `id` = '$id'");
            }
            $x++;
        }
        return true;
    }

    public function reorder()
    {
        if ($this->user->hasPermission('mod_event_category', 'edit', $this->lang['mod_event_category']['role_errors']['edit']) === false) {
            return false;
        }

        $id = intval(Req::Post_Safe('id'));
        $parentId = intval(Req::Post_Safe('parent_id'));

        if ($id > 0) {
            $rows = $this->getAllCategoryRows();
            $rowsById = $this->categoryRowsById($rows);
            if (!isset($rowsById[$id])) {
                echo $this->lang['mod_event_category']['errors']['record'];
                return;
            }

            $movingRow = $rowsById[$id];
            if (!$this->validateCategoryParent($movingRow['lang'] ?? '', $parentId, $id, $movingRow)) {
                echo $this->lastErrorMessage !== '' ? $this->lastErrorMessage : $this->lang['mod_event_category']['errors']['parent_language_mismatch'];
                return;
            }

            if ($this->db->query("UPDATE `mod_event_category` SET `parent_id` = '$parentId' WHERE `id` = '$id'")) {
                if ($this->_order_change()) {
                    echo 'Success';
                } else {
                    echo $this->lang['mod_event_category']['errors']['order_update_failed'];
                }
            } else {
                echo $this->lang['mod_event_category']['error_messages']['server'];
            }
        } else {
            echo $this->lang['mod_event_category']['errors']['record'];
        }
    }
}
