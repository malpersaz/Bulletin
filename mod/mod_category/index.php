<?php
/**
 * Amed Framework
 * mod_category Index
 */

class mod_category_mod extends indexMain
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
    ];

    public function __construct()
    {
        parent::__construct();
        $this->db = Load::lib('Database');
        $this->user = Load::model('mod_user.user');
        $this->view = Load::lib('AdminTemplate');
        $this->session = Load::lib('Session');
        $this->lang = Load::lang('mod_category');
        
        $this->view->lang($this->lang);
    }

    public function index()
    {
        $this->list();
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

    private function resolveDisplayName($row, array $visibleLanguages = [])
    {
        if (!isset($row['name']) || strpos($row['name'], '##') !== 0) {
            return $row['name'] ?? '';
        }

        if ((int) ($row['parent_id'] ?? -1) === 0 && !empty($visibleLanguages)) {
            $langCode = isset($row['lang']) ? $row['lang'] : '';
            foreach ($visibleLanguages as $language) {
                if (($language['seo'] ?? '') === $langCode) {
                    return $language['name'];
                }
            }
        }

        $internalName = substr($row['name'], 2);
        return str_replace('_', ' ', $internalName);
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
        $this->db->fetch_array("SELECT * FROM `mod_category` ORDER BY `order` ASC, `id` ASC");
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

    private function categoryRowsById(array $rows)
    {
        $rowsById = [];
        foreach ($rows as $row) {
            $rowsById[(int) ($row['id'] ?? 0)] = $row;
        }

        return $rowsById;
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

    public function list()
    {
        if ($this->user->hasPermission('mod_category', 'list', $this->lang['mod_category']['role_errors']['list'], 1) === False) return False;

        $visibleLanguages = $this->getVisibleLanguages();
        $visibleLangs = $this->getVisibleLanguageCodes();
        $visibleRows = [];
        foreach ($this->getAllCategoryRows() as $row) {
            if (!isset($row['lang']) || in_array((string) $row['lang'], $visibleLangs, TRUE)) {
                $row['display_name'] = $this->resolveDisplayName($row, $visibleLanguages);
                $row['is_container'] = $this->isCategoryContainerRow($row);
                $visibleRows[] = $row;
            }
        }

        Run::import('management', '_index');
        $this->view->veri('category_tree', $this->buildNestedRows($visibleRows));
        $this->view->tpl('admin/mod_category/list');
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
        $image->mod = 'mod_category';
        $image->name = $fieldName;

        if ($image->_resize($this->imageOptions())) {
            return $image->url;
        }

        $this->view->veri('error', true);
        $this->view->veri('mesaj', $image->error);

        return false;
    }

    private function rollbackUploadedImage($imageUrl)
    {
        if (empty($imageUrl)) {
            return;
        }

        $image = Load::mod('image');
        $image->mod = 'mod_category';
        $image->url = $imageUrl;
        $image->_delete_files($this->imageOptions());
    }

    private function categoryDataFromPost(array $base = [])
    {
        $base['name'] = Req::Post_Safe('name');
        $base['lang'] = Req::Post_Safe('lang');
        $base['parent_id'] = intval(Req::Post_Safe('parent_id'));
        $base['root'] = Req::Post_Safe('root');
        $base['seo'] = Req::Post_Safe('seo');
        $base['title'] = Req::Post_Safe('title');
        $base['keywords'] = Req::Post_Safe('keywords');
        $base['description'] = Req::Post('description');
        $base['menu'] = intval(Req::Post_Safe('menu'));
        $base['acl_id'] = intval(Req::Post_Safe('acl_id'));

        return $base;
    }

    private function setFormError($message)
    {
        $this->lastErrorMessage = $message;
        $this->view->veri('error', true);
        $this->view->veri('mesaj', $message);
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

            $this->setFormError($this->lang['mod_category']['errors']['parent_selection_required']);
            return false;
        }

        if (!isset($rowsById[$parentId])) {
            $this->setFormError($this->lang['mod_category']['errors']['record']);
            return false;
        }

        if ($currentId !== null && $this->isDescendantOf($rowsById, $parentId, $currentId)) {
            $this->setFormError($this->lang['mod_category']['errors']['parent_cycle']);
            return false;
        }

        $parentLang = $this->resolveCategoryLanguageFromAncestry($rowsById, $rowsById[$parentId]);
        if ($parentLang !== '' && $parentLang !== $lang) {
            $this->setFormError($this->lang['mod_category']['errors']['parent_language_mismatch']);
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
            $this->setFormError($this->lang['mod_category']['errors']['menu_parent_not_found']);
            return false;
        }

        $parentLang = $this->resolveMenuLanguageFromAncestry($rowsById, $rowsById[$menuParentId]);
        if ($parentLang !== '' && $parentLang !== $lang) {
            $this->setFormError($this->lang['mod_category']['errors']['menu_language_mismatch']);
            return false;
        }

        return true;
    }

    private function nextCategorySiblingOrder($parentId)
    {
        $this->db->fetch_array("SELECT MAX(`order`) AS max_order FROM `mod_category` WHERE parent_id=" . intval($parentId));
        $rows = $this->db->getResult()->toArray();
        $maxOrder = isset($rows[0]['max_order']) && Req::isNumeric((string) $rows[0]['max_order']) ? (int) $rows[0]['max_order'] : 0;

        return $maxOrder + 1;
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
            $languageRootLabel = str_replace(':language', $resolvedLanguageName, $this->lang['mod_category']['parent_labels']['language_root']);
        }

        $selectedParentId = $selectedParentId !== null ? (int) $selectedParentId : null;
        $allowSystemParent = $currentRow !== null && $this->isLanguageRootRow($currentRow);
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

    private function syncMenuOnCreate($category_model)
    {
        if ((int) Req::Post_Safe('menu') === 0) {
            return;
        }

        $parentId = (int) Req::Post_Safe('menu');
        $menu_model = Load::model('mod_menu');
        $menu_model->parent_id = $parentId;
        $menu_model->name = $category_model->name;
        $menu_model->lang = $category_model->lang;
        $menu_model->url = $category_model->lang . '/kat/' . $category_model->seo;
        $menu_model->target = 'n';
        $menu_model->status = 'y';
        $menu_model->order = $this->nextMenuSiblingOrder($parentId, $category_model->lang);
        $menu_model->acl_id = $category_model->acl_id;
        $menu_model->user_id = $this->session->get('id');
        $menu_model->save();
    }

    private function syncMenuOnUpdate($previous_url, $category_model)
    {
        $menu_model = Load::model('mod_menu');
        $requestedParentId = (int) Req::Post_Safe('menu');
        if ($menu_model->exists_url($previous_url)) {
            $menu_model->getResultOne()->sync();
            if ($requestedParentId === 0) {
                $menu_model->delete();
                return;
            }

            $previousParentId = (int) $menu_model->parent_id;
            $menu_model->name = $category_model->name;
            $menu_model->lang = $category_model->lang;
            $menu_model->url = $category_model->lang . '/kat/' . $category_model->seo;
            $menu_model->acl_id = $category_model->acl_id;
            $menu_model->parent_id = $requestedParentId;
            if ($previousParentId !== $requestedParentId) {
                $menu_model->order = $this->nextMenuSiblingOrder($requestedParentId, $category_model->lang);
            }
            $menu_model->update();
            return;
        }

        if ($requestedParentId !== 0) {
            $this->syncMenuOnCreate($category_model);
        }
    }

    private function deleteCategoryTree($id)
    {
        Load::model('mod_category');
        $mod_category_model = new mod_category_model();
        $mod_category_model->getid($id)->getResultOne(TRUE, TRUE);
        if ($mod_category_model->getSize() === 0) {
            print($this->lang['mod_category']['errors']['record']);
            return FALSE;
        }
        $mod_category_model->sync();

        if ($this->user->checkPermission('mod_category', 'moderate_delete', $mod_category_model->user_id, $this->lang['mod_category']['role_errors']['moderate_delete']) === False) {
            return FALSE;
        }

        $children_model = new mod_category_model();
        if ($children_model->exists_parent_id($id)) {
            foreach ($children_model->toArray() as $child) {
                if ($this->deleteCategoryTree($child['id']) !== TRUE) {
                    return FALSE;
                }
            }
        }

        if ($mod_category_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_category';
            $image->url = $mod_category_model->image;
            $image->_delete_files($this->imageOptions());
        }

        $gallery_mod = Load::model('mod_gallery_mod');
        if ($gallery_mod->exists_mod_name('mod_category') === TRUE) {
            Run::import('mod_gallery', '_remove', ['mod_category', $mod_category_model->id]);
        }

        $attachment_mod = Load::model('mod_attachment_mod');
        if ($attachment_mod->exists_mod_name('mod_category') === TRUE) {
            Run::import('mod_attachment', '_remove', ['mod_category', $mod_category_model->id]);
        }

        $menu_url = $mod_category_model->lang . '/kat/' . $mod_category_model->seo;
        $menu_model = Load::model('mod_menu');
        if ($menu_model->exists_url($menu_url)) {
            $menu_model->getResultOne()->delete();
        }

        $mod_category_model->delete();
        return TRUE;
    }

    public function add()
    {
        if ($this->user->hasPermission('mod_category', 'add', $this->lang['mod_category']['role_errors']['add'], 1) === False) return False;

        if (Req::is_Post('add')) {
            $this->val = Load::validator('mod_category.add');
            if ($this->val->check()) {
                foreach ($_POST as $key => $val) {
                    $this->view->veri($key, Req::Post_Safe($key));
                }
                $this->view->veri('hatalar', $this->val->hatalar);
                $this->setFormError($this->lang['mod_category']['messages']['error']['validation']);
            } else {
                Load::model('mod_category');
                $mod_category_model = new mod_category_model();
                $formData = $this->categoryDataFromPost();

                $mod_category_model->name = $formData['name'];
                $mod_category_model->lang = $formData['lang'];
                $mod_category_model->parent_id = $formData['parent_id'];
                $mod_category_model->root = $formData['root'];
                $mod_category_model->seo = $formData['seo'];
                $mod_category_model->title = $formData['title'];
                $mod_category_model->keywords = $formData['keywords'];
                $mod_category_model->description = $formData['description'];
                $mod_category_model->menu = $formData['menu'];
                $mod_category_model->acl_id = $formData['acl_id'];
                $mod_category_model->user_id = $this->session->get('id');

                if (!$this->validateCategoryParent($mod_category_model->lang, $mod_category_model->parent_id) || !$this->validateMenuParent($mod_category_model->lang, $mod_category_model->menu)) {
                    foreach ($formData as $key => $value) {
                        $this->view->veri($key, $value);
                    }
                } else {
                    $mod_category_model->order = $this->nextCategorySiblingOrder($mod_category_model->parent_id);

                    $uploadedImage = $this->uploadImageOrFail('image');
                    if ($uploadedImage === false) {
                        foreach ($formData as $key => $value) {
                            $this->view->veri($key, $value);
                        }
                    } else {
                        if ($uploadedImage !== null) {
                            $mod_category_model->image = $uploadedImage;
                        }

                        if ($mod_category_model->save()) {
                            $this->syncMenuOnCreate($mod_category_model);
                            $this->view->veri('oke', true);
                            $this->view->veri('mesaj', $this->lang['mod_category']['messages']['success']['added']);
                            Helper::yonlendir($this->site['saddress'] . 'mod_category/list', 1);
                            return;
                        } else {
                            $this->rollbackUploadedImage($uploadedImage);
                            foreach ($formData as $key => $value) {
                                $this->view->veri($key, $value);
                            }
                            $this->setFormError($this->lang['mod_category']['messages']['error']['not_added']);
                        }
                    }
                }
            }
        }

        $selectedParentId = Req::is_Post('parent_id') ? Req::Post_Safe('parent_id') : null;
        $this->loadFormMetadata($this->resolveSelectedLanguage(), null, $selectedParentId);
        Run::import('management', '_index');
        $this->view->tpl('admin/mod_category/add');
    }

    public function edit($id)
    {
        if ($this->user->hasPermission('mod_category', 'edit', $this->lang['mod_category']['role_errors']['edit'], 1) === False) return False;

        Load::model('mod_category');
        $mod_category_model = new mod_category_model();
        $id = Req::Url_Clear($id);
        if ($mod_category_model->exists_id($id) === FALSE) {
            $this->view->veri('error', true);
            $this->view->veri('mesaj', $this->lang['mod_category']['errors']['record']);
            $this->loadFormMetadata();
            Run::import('management', '_index');
            $this->view->tpl('admin/mod_category/edit');
            return;
        }

        $existing = $mod_category_model->getResultOne()->toArray();
        $viewData = $existing;
        $reloadFromDb = true;
        if ($this->user->checkPermission('mod_category', 'moderate_edit', $existing['user_id'], $this->lang['mod_category']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->val = Load::validator('mod_category.edit');
            if ($this->val->check($id)) {
                $this->view->veri('hatalar', $this->val->hatalar);
                $this->setFormError($this->lang['mod_category']['messages']['error']['validation']);
                $viewData = $this->categoryDataFromPost($existing);
                $reloadFromDb = false;
            } else {
                $mod_category_model = new mod_category_model();
                $mod_category_model->id = $id;
                $formData = $this->categoryDataFromPost(['id' => $id]);

                $mod_category_model->name = $formData['name'];
                $mod_category_model->lang = $formData['lang'];
                $mod_category_model->parent_id = $formData['parent_id'];
                $mod_category_model->root = $formData['root'];
                $mod_category_model->seo = $formData['seo'];
                $mod_category_model->title = $formData['title'];
                $mod_category_model->keywords = $formData['keywords'];
                $mod_category_model->description = $formData['description'];
                $mod_category_model->menu = $formData['menu'];
                $mod_category_model->acl_id = $formData['acl_id'];

                if (!$this->validateCategoryParent($mod_category_model->lang, $mod_category_model->parent_id, $id, $formData) || !$this->validateMenuParent($mod_category_model->lang, $mod_category_model->menu)) {
                    $viewData = array_merge($existing, $formData);
                    $reloadFromDb = false;
                } else {
                    $uploadedImage = $this->uploadImageOrFail('image');
                    if ($uploadedImage === false) {
                        $viewData = array_merge($existing, $formData);
                        $reloadFromDb = false;
                    } else {
                        if ($uploadedImage !== null) {
                            $mod_category_model->image = $uploadedImage;
                        }

                        if ($mod_category_model->update('id')) {
                            $this->syncMenuOnUpdate($existing['lang'] . '/kat/' . $existing['seo'], $mod_category_model);
                            $this->view->veri('oke', true);
                            $this->view->veri('mesaj', $this->lang['mod_category']['messages']['success']['updated']);
                        } else {
                            $this->rollbackUploadedImage($uploadedImage);
                            $this->setFormError($this->lang['mod_category']['messages']['error']['not_updated']);
                        }
                    }
                }
            }
        }

        $res = $reloadFromDb ? $mod_category_model->where_id($id)->gRo()->toArray() : $viewData;
        $selectedLang = Req::is_Post('lang') ? Req::Post_Safe('lang') : ($res['lang'] ?? $existing['lang']);
        $selectedParentId = Req::is_Post('parent_id') ? Req::Post_Safe('parent_id') : ($res['parent_id'] ?? $existing['parent_id']);
        $this->loadFormMetadata($selectedLang, $id, $selectedParentId, $existing['lang']);

        Run::import('management', '_index');
        $this->view->veri('d', $res);
        $this->view->tpl('admin/mod_category/edit');
    }

    public function image_remove_image()
    {
        if ($this->user->hasPermission('mod_category', 'edit', $this->lang['mod_category']['role_errors']['edit'], 1) === False) return FALSE;

        $id = intval(Req::Post_Safe('id'));
        Load::model('mod_category');
        $mod_category_model = new mod_category_model();
        $mod_category_model->where_id($id)->gRo();
        if ($mod_category_model->getSize() === 0) {
            echo $this->lang['mod_category']['errors']['record'];
            return FALSE;
        }

        $mod_category_model->sync();
        if ($this->user->checkPermission('mod_category', 'moderate_edit', $mod_category_model->user_id, $this->lang['mod_category']['role_errors']['moderate_edit']) === False) {
            echo $this->lang['mod_category']['role_errors']['moderate_edit'];
            return FALSE;
        }

        if ($mod_category_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_category';
            $image->url = $mod_category_model->image;
            if ($image->_delete_files($this->imageOptions())) {
                $mod_category_model->image = '';
                $mod_category_model->update('id');
                echo 'Oke';
            } else {
                echo $image->error;
                return FALSE;
            }
        } else {
            echo 'Oke';
        }
    }

    public function sil()
    {
        if ($this->user->hasPermission('mod_category',  'delete', $this->lang['mod_category']['role_errors'][ 'delete']) === False) return False;

        $id_param = Req::Post_Safe('id');
        if ($id_param === NULL || $id_param === '') {
            $id_param = Req::Post_Safe('delete');
        }
        if ($id_param === NULL || $id_param === '') {
            $id_param = Req::Post_Safe('sil');
        }
        
        $ids = is_array($id_param) ? $id_param : [$id_param];
        
        foreach ($ids as $id) {
            $id = intval($id);
            if ($id > 0) {
                if ($this->deleteCategoryTree($id) !== TRUE) {
                    echo 'ERROR';
                    return FALSE;
                }
            }
        }
        echo 'Oke';
    }

    // Modern drag-drop delete endpoint compatibility
    public function dragdrop_delete()
    {
        $this->sil();
    }

    public function _order_change()
    {
        $order_array = Req::Post('order');
        if (empty($order_array) || !is_array($order_array)) return TRUE;

        $x = 1;
        foreach ($order_array as $category_id) {
            $id = intval($category_id);
            if ($id > 0) {
                $this->db->query("UPDATE `mod_category` SET `order` = '$x' WHERE `id` = '$id'");
            }
            $x++;
        }
        return TRUE;
    }

    public function tasi()
    {
        if ($this->user->hasPermission('mod_category', 'edit', $this->lang['mod_category']['role_errors']['edit']) === False) return False;

        $id = intval(Req::Post_Safe('id'));
        $parent_id = intval(Req::Post_Safe('parent_id'));

        if ($id > 0) {
            $rows = $this->getAllCategoryRows();
            $rowsById = $this->categoryRowsById($rows);
            if (!isset($rowsById[$id])) {
                echo $this->lang['mod_category']['errors']['record'];
                return;
            }

            $movingRow = $rowsById[$id];
            if (!$this->validateCategoryParent($movingRow['lang'] ?? '', $parent_id, $id, $movingRow)) {
                echo $this->lastErrorMessage !== '' ? $this->lastErrorMessage : $this->lang['mod_category']['errors']['parent_language_mismatch'];
                return;
            }

            if ($this->db->query("UPDATE `mod_category` SET `parent_id` = '$parent_id' WHERE `id` = '$id'")) {
                if ($this->_order_change()) {
                    echo 'Oke';
                } else {
                    echo $this->lang['mod_category']['errors']['order_update_failed'];
                }
            } else {
                echo $this->lang['mod_category']['error_messages']['server'];
            }
        } else {
            echo $this->lang['mod_category']['errors']['record'];
        }
    }
}
