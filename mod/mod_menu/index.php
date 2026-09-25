<?php
/* 
 * AKM Software.
 * @2015
 * Menu Module Page
 * mod/mod_menu/index.php
 */
class mod_menu_mod extends indexMain
{
    public $mod_menu_model;
    private $slotAliases = [
        'header' => [
            '##HEADER MENU', 'HEADER MENU', '##HEADER', 'HEADER', 'UST MENU', 'TOP MENU', 'MAIN MENU',
            'MAINMENU', 'ANA MENU', 'ANAMENU', 'SERPESEK', 'SER PESEK', 'HAUPTMENU', 'HAUPT MENU',
            'MENU PRINCIPAL', 'MENUPRINCIPAL', 'MENUYOSEREN', 'MENUYO SEREN', '##القائمةالرئيسية', 'القائمةالرئيسية', 'القائمة الرئيسية'
        ],
        'footer' => [
            '##FOOTER MENU', 'FOOTER MENU', '##FOOTER', 'FOOTER', 'ALT MENU', 'BOTTOM MENU',
            'FOOTERMENU', 'ALTMENU', 'BINPESEK', 'BIN PESEK', 'FUSSMENU', 'FUSS MENU',
            'MENU PIED', 'MENU DU PIED'
        ],
        'sidebar' => ['##SIDEBAR', 'SIDEBAR', 'SOL MENU', 'SIDE MENU', 'SIDEBARMENU', 'PALPESEK'],
        'action' => [
            '##ACTION MENU', 'ACTION MENU', '##ACTION', 'ACTION', 'CTA MENU', '##CTA MENU',
            'ACTIONMENU', 'ACTION_MENU', 'PESEKA CALAKIYE', 'ÇALAKÎ', 'AKTIONSMENU', 'AKTIONSMENÜ',
            'EYLEM MENUSU', 'EYLEMMENUSU', 'قائمة الإجراءات'
        ],
    ];
    function __construct()
    {
        parent::__construct();
        // Load Template Library
        $this->theme = Load::lib('AdminTemplate');
        // Load Database Library
        $this->db = Load::lib('Database');
        // Get site settings
        //$this->site = Registry::get('site_config');
        // Load Language File
        $this->lang = Load::lang('mod_menu');
        // Send Language File to Template
        $this->theme->lang($this->lang);
        // Session Library
        $this->session = Load::lib('Session');
        // User Model
        $this->user = Load::model('mod_user.user');
    }

    private function normalizeSlotName($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = ltrim($value, '#');
        if (function_exists('iconv')) {
            $transliteratedValue = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($transliteratedValue !== false) {
                $value = $transliteratedValue;
            }
        }

        $value = str_replace(['_', '-'], ' ', $value);
        $value = strtoupper($value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }

    private function isContainerRow(array $row)
    {
        $url = isset($row['url']) ? trim((string) $row['url']) : '';
        $name = isset($row['name']) ? trim((string) $row['name']) : '';

        return $url === '##' || strpos($name, '##') === 0;
    }

    private function isLanguageRootRow(array $row)
    {
        if (!$this->isContainerRow($row) || (int) ($row['parent_id'] ?? -1) !== 0) {
            return false;
        }

        foreach (array_keys($this->slotAliases) as $slot) {
            if ($this->rowMatchesSlotAlias($row, $slot)) {
                return false;
            }
        }

        return true;
    }

    private function resolveDisplayName(array $row, array $visibleLanguages)
    {
        $name = isset($row['name']) ? $row['name'] : '';
        
        if ($this->isLanguageRootRow($row)) {
            $langCode = isset($row['lang']) ? $row['lang'] : '';
            foreach ($visibleLanguages as $l) {
                if ($l['seo'] === $langCode) {
                    return $l['name'];
                }
            }
            return ltrim($name, '#');
        }

        foreach (array_keys($this->slotAliases) as $slot) {
            if ($this->rowMatchesSlotAlias($row, $slot)) {
                return isset($this->lang['mod_menu']['slots'][$slot]) ? $this->lang['mod_menu']['slots'][$slot] : ltrim($name, '#');
            }
        }

        if ($this->isContainerRow($row)) {
            return ltrim($name, '#');
        }

        return $name;
    }

    private function rowMatchesSlotAlias(array $row, $slot)
    {
        if (!isset($this->slotAliases[$slot])) {
            return false;
        }

        $normalizedName = $this->normalizeSlotName(isset($row['name']) ? $row['name'] : '');
        foreach ($this->slotAliases[$slot] as $alias) {
            if ($normalizedName === $this->normalizeSlotName($alias)) {
                return true;
            }
        }

        return false;
    }

    private function getAllMenuRows()
    {
        $modelClass = Load::modelfile('mod_menu');
        $mod_menu_nested = new $modelClass();
        $mod_menu_nested->order('order', 'ASC')->all();
        $rows = $mod_menu_nested->toArray();

        return is_array($rows) ? $rows : [];
    }

    private function findLanguageRootId(array $rows, $lang)
    {
        $languageRootRow = $this->findLanguageRootRow($rows, $lang);
        return $languageRootRow !== null ? (int) $languageRootRow['id'] : null;
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

    private function findSlotBranchRootId(array $rows, $slot, $lang)
    {
        $languageRootId = $this->findLanguageRootId($rows, $lang);
        if ($languageRootId !== null) {
            foreach ($rows as $row) {
                if (!$this->isContainerRow($row)) {
                    continue;
                }

                if ((int) ($row['parent_id'] ?? -1) !== $languageRootId) {
                    continue;
                }

                if (($row['lang'] ?? '') !== $lang) {
                    continue;
                }

                if ($this->rowMatchesSlotAlias($row, $slot)) {
                    return (int) $row['id'];
                }
            }
        }

        foreach ($rows as $row) {
            if (!$this->isContainerRow($row)) {
                continue;
            }

            if (($row['lang'] ?? '') !== $lang) {
                continue;
            }

            if ($this->rowMatchesSlotAlias($row, $slot)) {
                return (int) $row['id'];
            }
        }

        return null;
    }

    private function resolveMenuSlotFromRecord(array $record, array $rowsById)
    {
        $current = $record;
        while (isset($current['parent_id']) && (int) $current['parent_id'] !== 0) {
            $parentId = (int) $current['parent_id'];
            if (!isset($rowsById[$parentId])) {
                break;
            }

            $current = $rowsById[$parentId];
            foreach (array_keys($this->slotAliases) as $slot) {
                if ($this->rowMatchesSlotAlias($current, $slot)) {
                    return $slot;
                }
            }
        }

        return 'none';
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

            $isTopLevel = isset($current['parent_id']) && (int) $current['parent_id'] === 0;
            $isContainer = $this->isContainerRow($current);
            $currentLang = isset($current['lang']) ? trim((string) $current['lang']) : '';

            if ($isTopLevel && $isContainer) {
                $isSlotRoot = false;
                foreach (array_keys($this->slotAliases) as $slot) {
                    if ($this->rowMatchesSlotAlias($current, $slot)) {
                        $isSlotRoot = true;
                        break;
                    }
                }

                if (!$isSlotRoot && $currentLang !== '') {
                    return $currentLang;
                }
            }

            $parentId = isset($current['parent_id']) ? (int) $current['parent_id'] : 0;
            if ($parentId === 0 || !isset($rowsById[$parentId])) {
                break;
            }

            $current = $rowsById[$parentId];
        }

        return isset($startRow['lang']) ? trim((string) $startRow['lang']) : '';
    }

    private function normalizeMoveParentId(array $rowsById, array $movingRow, $newParentId)
    {
        $newParentId = (int) $newParentId;
        if ($newParentId === 0 || !isset($rowsById[$newParentId])) {
            return $newParentId;
        }

        $newParentRow = $rowsById[$newParentId];
        if (!$this->isContainerRow($newParentRow) || (int) ($newParentRow['parent_id'] ?? -1) !== 0) {
            return $newParentId;
        }

        $rows = array_values($rowsById);
        $currentSlot = $this->resolveMenuSlotFromRecord($movingRow, $rowsById);
        if ($currentSlot === 'none') {
            return $newParentId;
        }

        $targetLang = isset($newParentRow['lang']) ? (string) $newParentRow['lang'] : '';
        if ($targetLang === '') {
            return $newParentId;
        }

        $branchRootId = $this->findSlotBranchRootId($rows, $currentSlot, $targetLang);
        return $branchRootId !== null ? (int) $branchRootId : $newParentId;
    }

    public function ensureSlotRootsForLanguage($lang)
    {
        $lang = trim((string) $lang);
        if ($lang === '') {
            return;
        }

        $rows = $this->getAllMenuRows();
        $languageRootRow = $this->findLanguageRootRow($rows, $lang);

        if ($languageRootRow === null) {
            $visibleLangs = $this->getVisibleLanguages($lang);
            $langName = strtoupper($lang);
            foreach ($visibleLangs as $l) {
                if (isset($l['seo']) && (string) $l['seo'] === $lang) {
                    $langName = isset($l['name']) ? (string) $l['name'] : strtoupper($lang);
                    break;
                }
            }

            $modelClass = Load::modelfile('mod_menu');
            $langModel = new $modelClass();
            $langModel->lang = $lang;
            $langModel->name = '##' . $langName;
            $langModel->url = '##';
            $langModel->parent_id = 0;
            $langModel->order = 0;
            $langModel->target = 'n';
            $langModel->status = 'y';
            $langModel->user_id = 1;
            $langModel->save(true);

            $rows = $this->getAllMenuRows();
            $languageRootRow = $this->findLanguageRootRow($rows, $lang);
        }

        if ($languageRootRow === null) {
            return;
        }

        $languageRootId = (int) $languageRootRow['id'];

        $requiredSlots = [
            'header' => '##Header_Menu',
            'footer' => '##Footer_Menu',
            'action' => '##Action_Menu',
        ];

        foreach ($requiredSlots as $slotKey => $slotName) {
            $slotRootId = $this->findSlotBranchRootId($rows, $slotKey, $lang);
            if ($slotRootId === null) {
                $modelClass = Load::modelfile('mod_menu');
                $slotModel = new $modelClass();
                $slotModel->lang = $lang;
                $slotModel->name = $slotName;
                $slotModel->url = '##';
                $slotModel->parent_id = $languageRootId;
                $slotModel->order = 0;
                $slotModel->target = 'n';
                $slotModel->status = 'y';
                $slotModel->user_id = 1;
                $slotModel->save(true);

                $rows = $this->getAllMenuRows();
            }
        }
    }

    private function getParentMenuContext($lang, $currentId = null, $selectedParentId = null)
    {
        $rows = $this->getAllMenuRows();
        $languageRootRow = $this->findLanguageRootRow($rows, $lang);
        $languageRootId = $languageRootRow !== null ? (int) $languageRootRow['id'] : 0;
        $currentRow = null;
        if ($currentId !== null) {
            foreach ($rows as $row) {
                if ((int) ($row['id'] ?? 0) === (int) $currentId) {
                    $currentRow = $row;
                    break;
                }
            }
        }

        $filtered = [];
        foreach ($rows as $row) {
            if (($row['lang'] ?? '') !== $lang) {
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

            $filtered[] = $row;
        }

        $visibleLangs = $this->getVisibleLanguages($lang);
        foreach ($filtered as &$fRow) {
            $fRow['display_name'] = $this->resolveDisplayName($fRow, $visibleLangs);
        }
        unset($fRow);

        $languageRootLabel = '';
        if ($languageRootRow !== null) {
            $resolvedLanguageName = $this->resolveDisplayName($languageRootRow, $visibleLangs);
            $languageRootLabel = str_replace(':language', $resolvedLanguageName, $this->lang['mod_menu']['parent_labels']['language_root']);
        }

        $tree = $this->buildNestedRows($filtered);

        $defaultParentId = '';
        if ($selectedParentId !== null && trim((string) $selectedParentId) !== '') {
            $defaultParentId = (string) $selectedParentId;
        } elseif (!empty($tree[$languageRootId])) {
            foreach ($tree[$languageRootId] as $topItem) {
                if ($this->isContainerRow($topItem) || !empty($tree[(int)$topItem['id']])) {
                    $defaultParentId = (string) $topItem['id'];
                    break;
                }
            }
            if ($defaultParentId === '' && !empty($tree[$languageRootId][0]['id'])) {
                $defaultParentId = (string) $tree[$languageRootId][0]['id'];
            }
        }

        $selectedParentIdNum = $selectedParentId !== null ? (int) $selectedParentId : null;
        $showLanguageRootOption = $languageRootId > 0 && $selectedParentIdNum === $languageRootId;
        $allowSystemParent = $currentRow !== null && $this->isLanguageRootRow($currentRow);

        return [
            'tree' => $tree,
            'root_parent_id' => $languageRootId,
            'default_parent_id' => $defaultParentId,
            'language_root_label' => $languageRootLabel,
            'show_language_root_option' => $showLanguageRootOption,
            'allow_system_parent' => $allowSystemParent,
        ];
    }

    private function prepareMenuFormContext($selectedLang, $currentId = null, $selectedParentId = null)
    {
        $this->ensureSlotRootsForLanguage($selectedLang);
        $parentContext = $this->getParentMenuContext($selectedLang, $currentId, $selectedParentId);
        $resolvedSelectedParentId = $selectedParentId !== null && trim((string) $selectedParentId) !== ''
            ? (string) $selectedParentId
            : (string) $parentContext['default_parent_id'];
        $this->theme->set('selected_lang', $selectedLang);
        $this->theme->set('select_nested_parent_id_result', $parentContext['tree']);
        $this->theme->set('menu_parent_root_id', $parentContext['root_parent_id']);
        $this->theme->set('menu_parent_default_id', $parentContext['default_parent_id']);
        $this->theme->set('menu_selected_parent_id', $resolvedSelectedParentId);
        $this->theme->set('menu_language_root_label', $parentContext['language_root_label']);
        $this->theme->set('menu_show_language_root_option', $parentContext['show_language_root_option']);
        $this->theme->set('menu_allow_system_parent', $parentContext['allow_system_parent']);
    }

    private function ensureEditParentPostValue(array $record)
    {
        if (array_key_exists('parent_id', $_POST) && trim((string) $_POST['parent_id']) !== '') {
            return;
        }

        if (!array_key_exists('parent_id', $record)) {
            return;
        }

        $_POST['parent_id'] = (string) $record['parent_id'];
        $_REQUEST['parent_id'] = (string) $record['parent_id'];
    }

    private function nextSiblingOrder($parentId, $lang)
    {
        $query = "SELECT `order` FROM `mod_menu` WHERE parent_id=" . intval($parentId);
        $lang = trim((string) $lang);
        if ($lang !== '') {
            $query .= " AND lang='" . Req::Clear($lang) . "'";
        }
        $query .= " ORDER BY `order` DESC LIMIT 1";

        $this->db->fetch_array($query);
        $rows = $this->db->getResult()->toArray();
        $rows = is_array($rows) ? $rows : [];
        $maxOrder = isset($rows[0]['order']) && Req::isNumeric((string) $rows[0]['order']) ? (int) $rows[0]['order'] : 0;

        return $maxOrder + 1;
    }
    /**
     * Add Page
     */
    public function add()
    {
        // Check Add Permission
        if ($this->user->hasPermission('mod_menu', 'add', $this->lang['mod_menu']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (Req::is_Post('add')) {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_menu']['add'];
        $this->theme->tpl('mod_menu/add');
        $this->theme->set('site', $this->site);
        $selectedLang = $this->resolveSelectedLanguage();
        $this->theme->set('lang_lang', $this->getVisibleLanguages());
        $this->prepareMenuFormContext($selectedLang);
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY name DESC");
        $this->theme->set('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    /**
     * Edit Page
     */
    public function edit($id = NULL)
    {
        // Check Edit Permission
        if ($this->user->hasPermission('mod_menu', 'edit', $this->lang['mod_menu']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_menu_model = Load::model('mod_menu');
        $id = Req::Url_Clear($id);
        if ($this->mod_menu_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_menu']['errors']['record']);
            Run::import('management', '_index');
            $this->theme->tpl('mod_menu/edit');
            return;
        }

        $d = $this->mod_menu_model->getResultOne()->toArray();

        // Check Moderate Permission if content belongs to another user
        if ($this->user->checkPermission('mod_menu', 'moderate_edit', $d['user_id'], $this->lang['mod_menu']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->ensureEditParentPostValue($d);
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_menu']['edit'];
        $this->theme->set('site', $this->site);
        $this->theme->set('d', $d);
        $selectedLang = Req::is_Post('lang') ? Req::Post_Safe('lang') : $d['lang'];
        $selectedParentId = Req::is_Post('parent_id') ? Req::Post_Safe('parent_id') : $d['parent_id'];
        $this->theme->tpl('mod_menu/edit');
        $this->theme->set('lang_lang', $this->getVisibleLanguages($d['lang']));
        $this->prepareMenuFormContext($selectedLang, $d['id'], $selectedParentId);
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY name DESC");
        $this->theme->set('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    /**
     * List Page
     */
    public function list()
    {
        // Check List Permission
        if ($this->user->hasPermission('mod_menu', 'list', $this->lang['mod_menu']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_menu']['list'];
        $this->theme->tpl('mod_menu/list');
        $this->theme->set('site', $this->site);
        $this->theme->set('module_lang_key', 'mod_menu');
        $visibleLangsFull = $this->getVisibleLanguages();
        $visibleLangs = $this->getVisibleLanguageCodes();
        foreach ($visibleLangs as $langCode) {
            $this->ensureSlotRootsForLanguage($langCode);
        }
        $visibleRows = [];
        foreach ($this->getAllMenuRows() as $row) {
            if (!isset($row['lang']) || in_array((string) $row['lang'], $visibleLangs, TRUE)) {
                $row['display_name'] = $this->resolveDisplayName($row, $visibleLangsFull);
                $slotKey = '';
                foreach (array_keys($this->slotAliases) as $s) {
                    if ($this->rowMatchesSlotAlias($row, $s)) {
                        $slotKey = $s;
                        break;
                    }
                }
                $row['slot_key'] = $slotKey;
                $visibleRows[] = $row;
            }
        }
        $select_nested_mod_menu_result = $this->buildNestedRows($visibleRows);
        $this->theme->set('select_nested_mod_menu_result', $select_nested_mod_menu_result);

        $totalMenus = count($visibleRows);
        $publishedMenus = 0;
        $draftMenus = 0;
        foreach ($visibleRows as $row) {
            if (isset($row['status']) && $row['status'] === 'y') {
                $publishedMenus++;
            } else {
                $draftMenus++;
            }
        }
        $this->theme->set('total_menus', $totalMenus);
        $this->theme->set('published_menus', $publishedMenus);
        $this->theme->set('draft_menus', $draftMenus);
    }
    public function _order_change()
    {
        $b = Req::Post_Safe('order');
        $mod_menu_model = new mod_menu_model();
        $x = 1;
        foreach ($b as $a) {
            $mod_menu_model->id = $a;
            $mod_menu_model->order = $x;
            if ($mod_menu_model->update('id') === False) {
                //print_r($mod_menu_model,TRUE);
                return FALSE;
            }
            $x++;
        }
        return TRUE;
    }


    /**
     * mod_menu Move / Reorder
     */
    public function reorder()
    {
        // Check Move Permission
        if ($this->user->hasPermission('mod_menu', 'edit', $this->lang['mod_menu']['role_errors']['edit']) === False) {
            return False;
        }

        // Standard Support (Matches mod_menu legacy & mod_category logic)
        $mod_menu_model = Load::model('mod_menu');
        $targetId = (int) Req::Post_Safe('id');
        $newParentId = (int) Req::Post_Safe('parent_id');

        $rows = $this->getAllMenuRows();
        $rowsById = [];
        foreach ($rows as $row) {
            $rowsById[(int) ($row['id'] ?? 0)] = $row;
        }

        if (!isset($rowsById[$targetId])) {
            echo 'Error: Record';
            return;
        }

        $mod_menu_model->id = $targetId;
        $mod_menu_model->parent_id = $this->normalizeMoveParentId($rowsById, $rowsById[$targetId], $newParentId);
        
        // Update Parent
        if ($mod_menu_model->update('id')) {
            // Update Order
            if ($this->_order_change()) {
                echo 'Success';
            } else {
                echo 'Error: Order';
            }
        } else {
            echo 'Error: Update';
        }
    }

    /**
     * Add Function
     */
    private function _add()
    {
        $this->val = Load::validator('mod_menu.add');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->theme->set($key, Req::Post_Safe($key));
            }
            $this->theme->set('errors', $this->val->errors);
        } else {
            $this->mod_menu_model = Load::model('mod_menu');

            $this->mod_menu_model->lang = Req::Post_Safe('lang');
            $this->mod_menu_model->name = Req::Post_Safe('name');
            $this->mod_menu_model->url = Req::Post_Safe('url');
            $this->mod_menu_model->file = Req::Post_Safe('file');
            $this->mod_menu_model->parent_id = (int) Req::Post_Safe('parent_id');
            $this->mod_menu_model->target = Req::Post_Safe('target');
            $this->mod_menu_model->status = Req::Post_Safe('status');
            $this->mod_menu_model->html = Req::Post('html');
            $this->mod_menu_model->icon = Req::Post_Safe('icon');
            $this->mod_menu_model->acl_id = Req::Post_Safe('acl_id');
            $this->mod_menu_model->user_id = $this->session->get('id');

            if ($this->validateParentLanguage($this->mod_menu_model->lang, $this->mod_menu_model->parent_id) === false) {
                return;
            }

            $this->mod_menu_model->order = $this->nextSiblingOrder($this->mod_menu_model->parent_id, $this->mod_menu_model->lang);

            if ($this->mod_menu_model->save(TRUE)) {
                $this->_success_message($this->lang['mod_menu']['message']['success']['add']);
                Helper::redirect($this->site['saddress'] . 'mod_menu/list', 1);
            } else {
                $this->_error_message($this->lang['mod_menu']['message']['error']['add']);
            }
        }
    }

    /**
     * Edit Function
     */
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_menu.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->theme->set($key, Req::Post_Safe($key));
            }
            $this->theme->set('errors', $this->val->errors);
        } else {
            $this->mod_menu_model = Load::model('mod_menu');

            $this->mod_menu_model->id = $id;
            $this->mod_menu_model->lang = Req::Post_Safe('lang');
            $this->mod_menu_model->name = Req::Post_Safe('name');
            $this->mod_menu_model->url = Req::Post_Safe('url');
            $this->mod_menu_model->file = Req::Post_Safe('file');
            $this->mod_menu_model->parent_id = (int) Req::Post_Safe('parent_id');
            $this->mod_menu_model->target = Req::Post_Safe('target');
            $this->mod_menu_model->status = Req::Post_Safe('status');
            $this->mod_menu_model->html = Req::Post('html');
            $this->mod_menu_model->icon = Req::Post_Safe('icon');
            $this->mod_menu_model->acl_id = Req::Post_Safe('acl_id');
            if (isset($this->mod_menu_model->user_id)) {
                unset($this->mod_menu_model->user_id);
            }

            $candidateRow = [
                'lang' => $this->mod_menu_model->lang,
                'name' => $this->mod_menu_model->name,
                'url' => $this->mod_menu_model->url,
                'parent_id' => $this->mod_menu_model->parent_id,
            ];

            if ($this->validateParentLanguage($this->mod_menu_model->lang, $this->mod_menu_model->parent_id, $id, $candidateRow) === false) {
                return;
            }

            // If the record exists
            $this->mod_menu_model->id = $id;
            if ($this->mod_menu_model->update('id')) {
                $this->_success_message($this->lang['mod_menu']['message']['success']['edit']);
                Helper::redirect($this->site['saddress'] . 'mod_menu/list', 1);
            } else {
                // If the update fails
                $this->_error_message($this->lang['mod_menu']['message']['error']['edit']);
            }
        }
    }



    public function _valid_date($data = '')
    {
        if ($data == '') {
            return false;
        }
        return preg_match('@(\d{4})-(\d{2})-(\d{2})$@', $data);
    }


    /**
     * Delete Page
     */
    public function dragdrop_delete()
    {
        // Check Delete Permission
        if ($this->user->hasPermission('mod_menu',  'delete', $this->lang['mod_menu']['role_errors'][ 'delete'], 1) === False) {
            return False;
        }
        $deleteId = Req::Post_Safe('delete');
        if (is_array($deleteId)) {
            $deleteId = reset($deleteId);
        }
        if ($this->_dragdrop_delete($deleteId) === TRUE) {
            echo 'Success';
        } else {
            echo 'ERROR';
        }
    }

    /**
     * Delete Function
     */
    private function _dragdrop_delete($deleteId = NULL)
    {
        // Load mod_menu Model
        $this->mod_menu_model = Load::model('mod_menu');

        $this->mod_menu_model->getid($deleteId)->getResultOne(TRUE, TRUE);
        if ($this->mod_menu_model->getSize() === 0) {
            print($this->lang['mod_menu']['errors']['record']);
            return FALSE;
        }
        $this->mod_menu_model->sync();

        $menuRecord = $this->mod_menu_model->toArray();
        if (is_array($menuRecord) && $this->isContainerRow($menuRecord)) {
            return FALSE;
        }

        // If record belongs to someone else, check permission
        if ($this->user->checkPermission('mod_menu', 'moderate_delete', $this->mod_menu_model->user_id, $this->lang['mod_menu']['role_errors']['moderate_delete']) === False) {
            return False;
        }
        $new_model = new mod_menu_model();
        if ($new_model->exists_parent_id($deleteId)) {
            $childRows = $new_model->toArray();
            if (is_array($childRows)) {
                foreach ($childRows as $s) {
                    if ($this->_dragdrop_delete($s['id']) === TRUE) {
                        continue;
                    }
                    return FALSE;
                }
            }
        }

        $this->mod_menu_model->getid($deleteId);
        // Check if module exists and Gallery is being added
        $this->mod = Load::model('mod_gallery_mod');
        if ($this->mod->exists_mod_name("mod_menu") === TRUE) {
            // Delete Gallery if exists for content
            Run::import('mod_gallery', '_remove', ['mod_menu', $this->mod_menu_model->id]);
        }
        // Check if module exists and Attachment is being added
        $this->mod = Load::model('mod_attachment_mod');
        if ($this->mod->exists_mod_name("mod_menu") === TRUE) {
            // Delete Attachment if exists for content
            Run::import('mod_attachment', '_remove', ['mod_menu', $this->mod_menu_model->id]);
        }

        $this->mod_menu_model->delete();
        return TRUE;
    }



    /**
     * Success Message
     */
    private function _success_message($message)
    {
        $this->theme->set('Success', True);
        $this->theme->set('message', $message);
    }

    /**
     * Error Message
     */
    private function _error_message($message)
    {
        $this->theme->set('error', True);
        $this->theme->set('errors', [$message]);
    }

    /**
     * Error Message
     */
    /**
     * Error Message
     */
    private function _error($message)
    {
        $this->theme->set('error', TRUE);
        $this->theme->set('message', $message);
    }

    /**
     * Approve Menu Items (Publish)
     */
    public function approve()
    {
        if ($this->user->hasPermission('mod_menu', 'edit', $this->lang['mod_menu']['role_errors']['edit']) === False) {
            http_response_code(403);
            echo 'Permission Denied';
            return False;
        }
        $query = Req::Post_Safe('query');
        if ($this->_approve($query) === TRUE) {
            echo 'Success';
        } else {
            echo 'ERROR';
        }
    }

    private function _approve($query = NULL)
    {
        if (!is_array($query)) {
            $query = ($query !== null && $query !== '') ? [$query] : [];
        }
        if (empty($query)) {
            return FALSE;
        }
        foreach ($query as $s) {
            $m = Load::model('mod_menu');
            $m->id = (int)$s;
            $m->status = 'y';
            if (!$m->update('id')) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Unapprove Menu Items (Unpublish)
     */
    public function unapprove()
    {
        if ($this->user->hasPermission('mod_menu', 'edit', $this->lang['mod_menu']['role_errors']['edit']) === False) {
            http_response_code(403);
            echo 'Permission Denied';
            return False;
        }
        $query = Req::Post_Safe('query');
        if ($this->_unapprove($query) === TRUE) {
            echo 'Success';
        } else {
            echo 'ERROR';
        }
    }

    private function _unapprove($query = NULL)
    {
        if (!is_array($query)) {
            $query = ($query !== null && $query !== '') ? [$query] : [];
        }
        if (empty($query)) {
            return FALSE;
        }
        foreach ($query as $s) {
            $m = Load::model('mod_menu');
            $m->id = (int)$s;
            $m->status = 'n';
            if (!$m->update('id')) {
                return FALSE;
            }
        }
        return TRUE;
    }

    private function resolveSelectedLanguage()
    {
        if (Req::is_Post('lang')) {
            return Req::Post_Safe('lang');
        }

        if (isset($_GET['lang'])) {
            return trim((string) $_GET['lang']);
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

    private function validateParentLanguage($lang, $parentId, $currentId = null, array $candidateRow = [])
    {
        $parentId = intval($parentId);
        $rows = $this->getAllMenuRows();
        $rowsById = [];
        foreach ($rows as $row) {
            $rowsById[(int) ($row['id'] ?? 0)] = $row;
        }

        if ($parentId === 0) {
            if ($currentId !== null && isset($rowsById[(int) $currentId])) {
                $existingRow = $rowsById[(int) $currentId];
                $currentRow = array_merge($existingRow, $candidateRow);
                $currentRow['parent_id'] = 0;
                if ($this->isLanguageRootRow($existingRow) && $this->isLanguageRootRow($currentRow)) {
                    if (($existingRow['lang'] ?? '') === $lang) {
                        return true;
                    }

                    $this->_error_message($this->lang['mod_menu']['errors']['parent_language_mismatch']);
                    return false;
                }
            }

            $this->_error_message($this->lang['mod_menu']['errors']['parent_selection_required']);
            return false;
        }

        if ($currentId !== null && $parentId === intval($currentId)) {
            $this->_error_message($this->lang['mod_menu']['errors']['parent_language_mismatch']);
            return false;
        }

        if (!isset($rowsById[$parentId])) {
            $this->_error_message($this->lang['mod_menu']['errors']['record']);
            return false;
        }

        $parentData = $rowsById[$parentId];
        $resolvedLang = $this->resolveMenuLanguageFromAncestry($rowsById, $parentData);
        if ($resolvedLang !== '' && $resolvedLang !== $lang) {
            $this->_error_message($this->lang['mod_menu']['errors']['parent_language_mismatch']);
            return false;
        }

        return true;
    }
}
