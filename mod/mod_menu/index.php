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
        'header' => ['##HEADER MENU', 'HEADER MENU', '##HEADER', 'HEADER', 'UST MENU', 'TOP MENU', 'MAIN MENU'],
        'footer' => ['##FOOTER MENU', 'FOOTER MENU', '##FOOTER', 'FOOTER', 'ALT MENU', 'BOTTOM MENU'],
        'sidebar' => ['##SIDEBAR', 'SIDEBAR', 'SOL MENU', 'SIDE MENU'],
    ];
    function __construct()
    {
        parent::__construct();
        // Load Template Library
        $this->tema = Load::lib('AdminTemplate');
        // Load Database Library
        $this->db = Load::lib('Database');
        // Get site settings
        //$this->site = Kayit::getir('site_config');
        // Load Language File
        $this->lang = Load::lang('mod_menu');
        // Send Language File to Template
        $this->tema->lang($this->lang);
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
        
        if ($this->isContainerRow($row) && (int)($row['parent_id'] ?? -1) === 0) {
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
        if (empty($rows)) {
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

        $selectedParentId = $selectedParentId !== null ? (int) $selectedParentId : null;
        $showLanguageRootOption = $languageRootId > 0 && $selectedParentId === $languageRootId;
        $allowSystemParent = $currentRow !== null && $this->isLanguageRootRow($currentRow);

        return [
            'tree' => $this->buildNestedRows($filtered),
            'root_parent_id' => $languageRootId,
            'default_parent_id' => '',
            'language_root_label' => $languageRootLabel,
            'show_language_root_option' => $showLanguageRootOption,
            'allow_system_parent' => $allowSystemParent,
        ];
    }

    private function prepareMenuFormContext($selectedLang, $currentId = null, $selectedParentId = null)
    {
        $parentContext = $this->getParentMenuContext($selectedLang, $currentId, $selectedParentId);
        $resolvedSelectedParentId = $selectedParentId !== null && trim((string) $selectedParentId) !== ''
            ? (string) $selectedParentId
            : (string) $parentContext['default_parent_id'];
        $this->tema->veri('selected_lang', $selectedLang);
        $this->tema->veri('select_nested_parent_id_result', $parentContext['tree']);
        $this->tema->veri('menu_parent_root_id', $parentContext['root_parent_id']);
        $this->tema->veri('menu_parent_default_id', $parentContext['default_parent_id']);
        $this->tema->veri('menu_selected_parent_id', $resolvedSelectedParentId);
        $this->tema->veri('menu_language_root_label', $parentContext['language_root_label']);
        $this->tema->veri('menu_show_language_root_option', $parentContext['show_language_root_option']);
        $this->tema->veri('menu_allow_system_parent', $parentContext['allow_system_parent']);
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
        $this->tema->tpl('mod_menu/add');
        $this->tema->veri('site', $this->site);
        $selectedLang = $this->resolveSelectedLanguage();
        $this->tema->veri('lang_lang', $this->getVisibleLanguages());
        $this->prepareMenuFormContext($selectedLang);
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY name DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
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
            $this->tema->tpl('mod_menu/edit');
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
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $selectedLang = Req::is_Post('lang') ? Req::Post_Safe('lang') : $d['lang'];
        $selectedParentId = Req::is_Post('parent_id') ? Req::Post_Safe('parent_id') : $d['parent_id'];
        $this->tema->tpl('mod_menu/edit');
        $this->tema->veri('lang_lang', $this->getVisibleLanguages($d['lang']));
        $this->prepareMenuFormContext($selectedLang, $d['id'], $selectedParentId);
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY name DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
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
        $this->tema->tpl('mod_menu/list');
        $this->tema->veri('site', $this->site);
        $visibleLangsFull = $this->getVisibleLanguages();
        $visibleLangs = $this->getVisibleLanguageCodes();
        $mod_menu_nested = Load::model('mod_menu');
        $mod_menu_nested->order('order', 'ASC')->all();
        $visibleRows = [];
        foreach ($mod_menu_nested->toArray() as $row) {
            if (!isset($row['lang']) || in_array((string) $row['lang'], $visibleLangs, TRUE)) {
                $row['display_name'] = $this->resolveDisplayName($row, $visibleLangsFull);
                $visibleRows[] = $row;
            }
        }
        $select_nested_mod_menu_result = $this->buildNestedRows($visibleRows);
        $this->tema->veri('select_nested_mod_menu_result', $select_nested_mod_menu_result);
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
     * mod_menu Move
     */
    public function tasi()
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
                echo 'Oke';
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
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
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
                $this->_oke_message($this->lang['mod_menu']['message']['oke']['add']);
                Helper::yonlendir($this->site['saddress'] . 'mod_menu/list', 1);
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
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
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
                $this->_oke_message($this->lang['mod_menu']['message']['oke']['edit']);
                Helper::yonlendir($this->site['saddress'] . 'mod_menu/list', 1);
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
        $sil = Req::Post_Safe( 'delete');
        if ($this->_dragdrop_delete($sil) === TRUE) {
            echo 'Oke';
        } else {
            echo 'ERROR';
            //print_r($this->mod_menu_model);
        }
    }

    /**
     * Delete Function
     */
    private function _dragdrop_delete($sil = NULL)
    {
        // Load mod_menu Model
        $this->mod_menu_model = Load::model('mod_menu');

        $this->mod_menu_model->getid($sil)->getResultOne(TRUE, TRUE);
        if ($this->mod_menu_model->getSize() === 0) {
            print($this->lang['mod_menu']['errors']['record']);
            return FALSE;
        }
        $this->mod_menu_model->sync();

        // If record belongs to someone else, check permission
        if ($this->user->checkPermission('mod_menu', 'moderate_delete', $this->mod_menu_model->user_id, $this->lang['mod_menu']['role_errors']['moderate_delete']) === False) {
            return False;
        }
        $new_model = new mod_menu_model();
        if ($new_model->exists_parent_id($sil)) {
            foreach ($new_model->toArray() as $s) {
                if ($this->_dragdrop_delete($s['id']) === TRUE) {
                    continue;
                }
                return FALSE;
            }
        }


        $this->mod_menu_model->getid($sil);
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
    private function _oke_message($mesaj)
    {
        $this->tema->veri('oke', True);
        $this->tema->veri('mesaj', $mesaj);
    }

    /**
     * Error Message
     */
    private function _error_message($mesaj)
    {
        $this->tema->veri('hata', True);
        $this->tema->veri('hatalar', [$mesaj]);
    }

    /**
     * Error Message
     */
    /**
     * Error Message
     */
    private function _error($mesaj)
    {
        $this->tema->veri('error', TRUE);
        $this->tema->veri('mesaj', $mesaj);
    }

    /**
     * Approve Menu Items (Publish)
     */
    public function approve()
    {
        if ($this->user->hasPermission('mod_menu', 'edit', $this->lang['mod_menu']['role_errors']['edit']) === False) {
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
        $this->mod_menu_model = Load::model('mod_menu');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_menu_model->id = $s;
                $this->mod_menu_model->status = 'y';
                if ($this->mod_menu_model->update('id')) {
                    continue;
                }
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Unapprove Menu Items (Unpublish)
     */
    public function unapprove()
    {
        if ($this->user->hasPermission('mod_menu', 'edit', $this->lang['mod_menu']['role_errors']['edit']) === False) {
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
        $this->mod_menu_model = Load::model('mod_menu');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_menu_model->id = $s;
                $this->mod_menu_model->status = 'n';
                if ($this->mod_menu_model->update('id')) {
                    continue;
                }
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
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

        $defaultLang = Kayit::getir('default_lang');
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
