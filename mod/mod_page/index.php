<?php
/* AKM Software.
 * @2015
 * mod_page Module Page
 * mod/mod_page/index.php
 */
class mod_page_mod extends BaseModuleController
{
    public $mod_page_model;

    protected $has_gallery = true;
    protected $has_files = true;

    protected $need_simple_data = [
        'lang' => 'lang_lang',
        'mod_acl' => 'mod_acl_acl_id'
    ];

    protected $image_fields = [
        [
            'name' => 'image',
            'sizes' => [
                '/' => [
                    'width' => 1200,
                    'height' => NULL,
                    'option' => 'width',
                ],
                '/thumb' => [
                    'width' => 250,
                    'height' => NULL,
                    'option' => 'width',
                ],
            ]
        ]
    ];

    protected function getDatatableColumns()
    {
        return [
            'id',
            'name|bold',
            'image|image',
            'lang|badge',
            'status|badge',
            'parent_id|custom'
        ];
    }

    private const MENU_SELECTION_NONE = '__none__';
    private const MENU_SELECTION_TOP = '__top__';
    private $menuSlotAliases = [
        'header' => ['##HEADER MENU', 'HEADER MENU', '##HEADER', 'HEADER', 'UST MENU', 'UST MENÜ', 'ÜST MENU', 'ÜST MENÜ', 'TOP MENU', 'MAIN MENU'],
        'footer' => ['##FOOTER MENU', 'FOOTER MENU', '##FOOTER', 'FOOTER', 'ALT MENU', 'ALT MENÜ', 'BOTTOM MENU'],
        'sidebar' => ['##SIDEBAR', 'SIDEBAR', 'SOL MENU', 'SOL MENÜ', 'SIDE MENU'],
    ];

    public function __construct()
    {
        parent::__construct('mod_page');
    }

    private function resolveSelectedLang($fallback = '')
    {
        if (Req::is_Post('lang')) {
            return Req::Post_Safe('lang');
        }

        if (Req::is_Get('lang')) {
            return Req::Get_Safe('lang');
        }

        return $fallback;
    }

    private function getOptionalInt($name, $default = 0)
    {
        $value = Req::Post_Safe($name);
        if ($value === '' || !Req::isNumeric((string) $value)) {
            return $default;
        }

        return (int) $value;
    }

    private function getPostedMenuSelectionValue($fallback = self::MENU_SELECTION_NONE)
    {
        if (!Req::is_Post('menu')) {
            return $fallback;
        }

        $value = trim((string) Req::Post_Safe('menu'));
        if ($value === '' || $value === self::MENU_SELECTION_NONE) {
            return self::MENU_SELECTION_NONE;
        }

        if ($value === self::MENU_SELECTION_TOP) {
            return self::MENU_SELECTION_TOP;
        }

        if (Req::isNumeric((string) $value)) {
            return (string) ((int) $value);
        }

        return $fallback;
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

    private function getAllMenuRows()
    {
        $modMenuModel = Load::model('mod_menu');
        $modMenuModel->order('order', 'ASC')->all();
        $rows = $modMenuModel->toArray();

        return is_array($rows) ? $rows : [];
    }

    private function findMenuLanguageRootId(array $rows, $lang)
    {
        foreach ($rows as $row) {
            if (!$this->isMenuContainerRow($row)) {
                continue;
            }

            if ((int) ($row['parent_id'] ?? -1) !== 0) {
                continue;
            }

            if (($row['lang'] ?? '') !== $lang) {
                continue;
            }

            $isSlotRoot = false;
            foreach (array_keys($this->menuSlotAliases) as $slot) {
                if ($this->rowMatchesMenuSlotAlias($row, $slot)) {
                    $isSlotRoot = true;
                    break;
                }
            }

            if ($isSlotRoot) {
                continue;
            }

            return (int) $row['id'];
        }

        return null;
    }

    private function findMenuSlotBranchRootId(array $rows, $slot, $lang)
    {
        $languageRootId = $this->findMenuLanguageRootId($rows, $lang);
        if ($languageRootId === null) {
            return null;
        }

        foreach ($rows as $row) {
            if (!$this->isMenuContainerRow($row)) {
                continue;
            }

            if ((int) ($row['parent_id'] ?? -1) !== $languageRootId) {
                continue;
            }

            if (($row['lang'] ?? '') !== $lang) {
                continue;
            }

            if ($this->rowMatchesMenuSlotAlias($row, $slot)) {
                return (int) $row['id'];
            }
        }

        return null;
    }

    private function resolveSelectedMenuSlot($default = 'none')
    {
        if (Req::is_Post('menu_slot')) {
            return Req::Post_Safe('menu_slot');
        }

        if (Req::is_Get('menu_slot')) {
            return Req::Get_Safe('menu_slot');
        }

        return $default;
    }

    private function getMenuSlotOptions()
    {
        return [
            ['value' => 'none', 'label' => $this->lang['mod_page']['menu_slot_options']['none']],
            ['value' => 'header', 'label' => $this->lang['mod_page']['menu_slot_options']['header']],
            ['value' => 'footer', 'label' => $this->lang['mod_page']['menu_slot_options']['footer']],
        ];
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

    private function buildNestedRows($rows)
    {
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        $nested = Helper::nested($rows, 'parent_id', 'id');
        return is_array($nested) ? $nested : [];
    }

    private function getMenuParentContext($lang, $slot)
    {
        $rows = $this->getAllMenuRows();

        if ($slot === 'none') {
            $filtered = [];
            foreach ($rows as $row) {
                if (($row['lang'] ?? '') !== $lang) {
                    continue;
                }

                if ($this->isMenuContainerRow($row)) {
                    continue;
                }

                $filtered[] = $row;
            }

            return [
                'tree' => $this->buildNestedRows($filtered),
                'root_parent_id' => 0,
            ];
        }

        $branchRootId = $this->findMenuSlotBranchRootId($rows, $slot, $lang);
        if ($branchRootId === null) {
            return [
                'tree' => [],
                'root_parent_id' => 0,
            ];
        }

        $filtered = [];
        foreach ($rows as $row) {
            if (($row['lang'] ?? '') !== $lang) {
                continue;
            }

            if ((int) ($row['id'] ?? 0) === $branchRootId) {
                continue;
            }

            if ($this->isMenuContainerRow($row)) {
                continue;
            }

            $filtered[] = $row;
        }

        return [
            'tree' => $this->buildNestedRows($filtered),
            'root_parent_id' => $branchRootId,
        ];
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
            foreach (array_keys($this->menuSlotAliases) as $slot) {
                if ($this->rowMatchesMenuSlotAlias($current, $slot)) {
                    return $slot;
                }
            }
        }

        return 'none';
    }

    private function getMenuRecordByUrl($url)
    {
        if ($url === '') {
            return NULL;
        }

        $mod_menu_model = Load::model('mod_menu');
        if ($mod_menu_model->exists_url($url) === FALSE) {
            return NULL;
        }

        return $mod_menu_model->getResultOne();
    }

    private function resolveMenuBindingContext(array $pageData)
    {
        $pageUrl = $pageData['lang'] . '/page/' . $pageData['seo'];
        $rows = $this->getAllMenuRows();
        $rowsById = [];
        $menuRow = null;
        foreach ($rows as $row) {
            $rowsById[(int) $row['id']] = $row;
            if ($menuRow === null && isset($row['url']) && (string) $row['url'] === $pageUrl) {
                $menuRow = $row;
            }
        }

        if ($menuRow !== null) {
            $slot = $this->resolveMenuSlotFromRecord($menuRow, $rowsById);
            $parentId = isset($menuRow['parent_id']) ? (int) $menuRow['parent_id'] : 0;
            $branchRootId = $slot !== 'none'
                ? $this->findMenuSlotBranchRootId($rows, $slot, $pageData['lang'])
                : null;

            if ($parentId === 0 || ($branchRootId !== null && $parentId === (int) $branchRootId)) {
                return [
                    'slot' => $slot,
                    'value' => self::MENU_SELECTION_TOP,
                ];
            }

            return [
                'slot' => $slot,
                'value' => (string) $parentId,
            ];
        }

        if (isset($pageData['menu']) && Req::isNumeric((string) $pageData['menu']) && (int) $pageData['menu'] > 0) {
            $menuId = (int) $pageData['menu'];
            $slot = isset($rowsById[$menuId]) ? $this->resolveMenuSlotFromRecord($rowsById[$menuId], $rowsById) : 'none';
            $branchRootId = $slot !== 'none'
                ? $this->findMenuSlotBranchRootId($rows, $slot, $pageData['lang'])
                : null;

            return [
                'slot' => $slot,
                'value' => ($branchRootId !== null && $menuId === (int) $branchRootId)
                    ? self::MENU_SELECTION_TOP
                    : (string) $menuId,
            ];
        }

        return [
            'slot' => 'none',
            'value' => self::MENU_SELECTION_NONE,
        ];
    }

    private function parseMenuSelection($slot = 'none', $lang = '', $value = NULL)
    {
        if ($value === NULL) {
            $value = Req::Post_Safe('menu');
        }

        $value = trim((string) $value);
        if ($value === '' || $value === self::MENU_SELECTION_NONE) {
            return [
                'include' => FALSE,
                'parent_id' => 0,
                'legacy_value' => 0,
                'selection_value' => self::MENU_SELECTION_NONE,
            ];
        }

        if ($value === self::MENU_SELECTION_TOP) {
            if ($slot !== 'none') {
                $branchRootId = $this->findMenuSlotBranchRootId($this->getAllMenuRows(), $slot, $lang);
                if ($branchRootId === null) {
                    return FALSE;
                }
            } else {
                $branchRootId = 0;
            }

            return [
                'include' => TRUE,
                'parent_id' => (int) $branchRootId,
                'legacy_value' => (int) $branchRootId,
                'selection_value' => self::MENU_SELECTION_TOP,
            ];
        }

        if (!Req::isNumeric((string) $value)) {
            return [
                'include' => FALSE,
                'parent_id' => 0,
                'legacy_value' => 0,
                'selection_value' => self::MENU_SELECTION_NONE,
            ];
        }

        return [
            'include' => TRUE,
            'parent_id' => (int) $value,
            'legacy_value' => (int) $value,
            'selection_value' => (string) ((int) $value),
        ];
    }

    private function loadFormLookups($selectedLang = '', $excludePageId = 0, $menuBindingValue = self::MENU_SELECTION_NONE, $selectedMenuSlot = 'none')
    {
        $langs = $this->getVisibleLanguages($selectedLang);
        if ($selectedLang === '') {
            $availableLangs = array_column($langs, 'seo');
            $siteLang = isset($this->site['lang']) ? $this->site['lang'] : '';

            if ($siteLang !== '' && in_array($siteLang, $availableLangs, TRUE)) {
                $selectedLang = $siteLang;
            } elseif (isset($langs[0]['seo'])) {
                $selectedLang = $langs[0]['seo'];
            }
        }

        $this->tema->veri('lang_lang', $langs);
        $this->tema->veri('selected_lang', $selectedLang);

        $mod_page_nested = Load::model('mod_page');
        $mod_page_nested->flush();
        if ($selectedLang !== '') {
            $mod_page_nested->where_lang($selectedLang);
        }
        $mod_page_nested->order('order', 'ASC')->all();
        $this->tema->veri('select_nested_parent_id_result', $this->buildNestedRows($mod_page_nested->toArray()));

        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());

        $mod_category_nested = Load::model('mod_category');
        $mod_category_nested->flush();
        if ($selectedLang !== '') {
            $mod_category_nested->where_lang($selectedLang);
        }
        $mod_category_nested->order('order', 'ASC')->all();
        $this->tema->veri('select_nested_category_id_result', $this->buildNestedRows($mod_category_nested->toArray()));

        $menuContext = $this->getMenuParentContext($selectedLang, $selectedMenuSlot);
        $this->tema->veri('select_nested_menu_result', $menuContext['tree']);
        $this->tema->veri('menu_parent_root_id', $menuContext['root_parent_id']);
        $this->tema->veri('selected_menu_slot', $selectedMenuSlot);
        $this->tema->veri('menu_slot_options', $this->getMenuSlotOptions());
        $this->tema->veri('menu_binding_value', $this->getPostedMenuSelectionValue($menuBindingValue));

        $pageQuery = "SELECT id, name, lang FROM `mod_page`";
        $where = [];
        if ($selectedLang !== '') {
            $where[] = "lang='" . $selectedLang . "'";
        }
        if ((int) $excludePageId !== 0) {
            $where[] = "id!='" . (int) $excludePageId . "'";
        }
        if (!empty($where)) {
            $pageQuery .= " WHERE " . implode(' AND ', $where);
        }
        $pageQuery .= " ORDER BY id DESC";
        $this->db->fetch_array($pageQuery);
        $pageReferences = $this->db->getResult()->toArray();
        $this->tema->veri('mod_page_gallery_id', $pageReferences);
        $this->tema->veri('mod_page_file_id', $pageReferences);
    }

    private function syncMenuRecord(array $menuBinding, $oldUrl = '', $menuUserId = 0)
    {
        $menuRecord = $this->getMenuRecordByUrl($oldUrl);
        $menuExists = $menuRecord !== NULL;

        if (!$menuBinding['include']) {
            if (!$menuExists) {
                return TRUE;
            }

            return $menuRecord->delete();
        }

        if (!$menuExists) {
            $mod_menu_model = Load::model('mod_menu');
            $mod_menu_model->user_id = (int) $menuUserId;
            $nextOrderQuery = "SELECT MAX(`order`) AS max_order FROM `mod_menu` WHERE parent_id='" . (int) $menuBinding['parent_id'] . "'";
            if ($this->mod_page_model->lang !== '') {
                $nextOrderQuery .= " AND lang='" . $this->mod_page_model->lang . "'";
            }
            $this->db->fetch_array($nextOrderQuery);
            $orderRows = $this->db->getResult()->toArray();
            $orderResult = isset($orderRows[0]) ? $orderRows[0] : [];
            $maxOrder = isset($orderResult['max_order']) && Req::isNumeric((string) $orderResult['max_order']) ? (int) $orderResult['max_order'] : 0;
            $mod_menu_model->order = $maxOrder + 1;
            $menuRecord = $mod_menu_model;
        }

        $menuRecord->parent_id = (int) $menuBinding['parent_id'];
        $menuRecord->name = $this->mod_page_model->name;
        $menuRecord->lang = $this->mod_page_model->lang;
        $menuRecord->status = $this->mod_page_model->status;
        $menuRecord->acl_id = $this->mod_page_model->acl_id;
        $menuRecord->url = $this->mod_page_model->lang . '/page/' . $this->mod_page_model->seo;

        if ($menuExists) {
            return $menuRecord->update();
        }

        return $menuRecord->save();
    }

    // Add Page
    public function add()
    {
        // Check add permission.
        if ($this->user->hasPermission('mod_page', 'add', $this->lang['mod_page']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (Req::is_Post('add')) {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_page']['add'];
        $this->tema->tpl('mod_page/add');
        $this->tema->veri('site', $this->site);
        $selectedLang = $this->resolveSelectedLang();
        $selectedMenuSlot = $this->resolveSelectedMenuSlot('none');
        $this->loadFormLookups($selectedLang, 0, self::MENU_SELECTION_NONE, $selectedMenuSlot);
    }

    // Edit Page
    public function edit($id = NULL)
    {
        // Check edit permission.
        if ($this->user->hasPermission('mod_page', 'edit', $this->lang['mod_page']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_page_model = Load::model('mod_page');
        $id = Req::Url_Clear($id);
        if ($this->mod_page_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_page']['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl('mod_page/edit');
            return;
        }

        $d = $this->mod_page_model->getResultOne()->toArray();

        // Check moderate permission if the page belongs to another user.
        if ($this->user->checkPermission('mod_page', 'moderate_edit', $d['user_id'], $this->lang['mod_page']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
            // Keep posted values in the form after validation errors.
            if (isset($this->val) && $this->val->check($id)) {
                foreach ($_POST as $key => $val) {
                    if ($key == 'content') {
                        $d[$key] = Req::Post('content');
                    } else {
                        $d[$key] = Req::Post_Safe($key);
                    }
                }
            }
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_page']['edit'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->tpl('mod_page/edit');
        $menuBindingContext = $this->resolveMenuBindingContext($d);
        $selectedMenuSlot = $this->resolveSelectedMenuSlot($menuBindingContext['slot']);
        $this->loadFormLookups(
            $this->resolveSelectedLang($d['lang']),
            (int) $d['id'],
            $menuBindingContext['value'],
            $selectedMenuSlot
        );
    }

    // List Page
    public function list()
    {
        // Check list permission
        if ($this->user->hasPermission('mod_page', 'list', $this->lang['mod_page']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_page']['list'];
        $this->tema->tpl('mod_page/list');
        $this->tema->veri('site', $this->site);
        $visibleLangs = $this->getVisibleLanguageCodes();
        $mod_page_nested = Load::model('mod_page');
        $mod_page_nested->order('order', 'ASC')->all();
        $visibleRows = [];
        foreach ($mod_page_nested->toArray() as $row) {
            if (!isset($row['lang']) || in_array((string) $row['lang'], $visibleLangs, TRUE)) {
                $visibleRows[] = $row;
            }
        }
        $select_nested_mod_page_result = $this->buildNestedRows($visibleRows);
        $this->tema->veri('select_nested_mod_page_result', $select_nested_mod_page_result);
    }
    // mod_page order change
    public function _sira_degis()
    {
        $b = Req::Post_Safe('order');
        $mod_page_model = new mod_page_model();
        $x = 1;
        foreach ($b as $a) {
            $mod_page_model->id = $a;
            $mod_page_model->order = $x;
            if ($mod_page_model->update('id') === False) {
                //print_r($mod_page_model,TRUE);
                return FALSE;
            }
            $x++;
        }
        return TRUE;
    }


    // mod_page drag/drop move
    public function tasi()
    {
        // Check move permission
        if ($this->user->hasPermission('mod_page', 'edit', $this->lang['mod_page']['role_errors']['edit']) === False) {
            return False;
        }
        $mod_page_model = Load::model('mod_page');
        $mod_page_model->id = Req::Post_Safe('id');
        $mod_page_model->parent_id = Req::Post_Safe('parent_id');
        if ($mod_page_model->update('id')  and $this->_sira_degis()) {
            echo 'Oke';
        } else {
            echo 'Error';
            //print_r($mod_page_model);
        }
    }
    public function image_remove_image()
    {
        // Check edit permission
        if ($this->user->hasPermission('mod_page', 'edit', $this->lang['mod_page']['role_errors']['edit'], 1) === False) {
            return FALSE;
        }

        $id = intval(Req::Post_Safe('id'));

        $this->mod_page_model = Load::model('mod_page');
        $this->mod_page_model->where_id($id)->gRo();
        if ($this->mod_page_model->getSize() === 0) {
            echo $this->lang['mod_page']['errors']['record'];
            return FALSE;
        }


        $this->mod_page_model->sync();
        // Check moderate permission if content belongs to another user
        if ($this->user->checkPermission('mod_page', 'moderate_edit', $this->mod_page_model->user_id, $this->lang['mod_page']['role_errors']['moderate_edit']) === False) {
            echo $this->lang['mod_page']['role_errors']['moderate_edit'];
            return False;
        }

        if ($this->mod_page_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_page';
            $image->url = $this->mod_page_model->image;
            $option = [
                '/' =>
                [
                    'width' => 1200,
                    'height' => NULL,
                ],
                '/thumb' =>
                [
                    'width' => 250,
                    'height' => NULL,
                    'option' => 'width',
                ],
            ];
            try {
                if ($image->_delete_files($option)) {
                    $this->mod_page_model->image = '';
                    $this->mod_page_model->update('id');
                    echo 'Oke';
                } else {
                    echo $image->error;
                    return FALSE;
                }
            } catch (Throwable $e) {
                echo $e->getMessage();
                return FALSE;
            }
        } else {
            echo 'Oke';
        }
    }

    // Add Function
    protected function _add()
    {
        $postedWizardStep = $this->getOptionalInt('wizard_step', 1);
        $this->tema->veri('wizard_step', $postedWizardStep);

        $this->val = Load::validator('mod_page.add');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                if ($key == 'content') {
                    $this->tema->veri($key, Req::Post('content'));
                } elseif ($key == 'lang') {
                    $this->tema->veri('selected_lang', Req::Post_Safe('lang'));
                } else {
                    $this->tema->veri($key, Req::Post_Safe($key));
                }
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_page_model = Load::model('mod_page');

            $this->mod_page_model->lang = Req::Post_Safe('lang');
            $this->mod_page_model->name = Req::Post_Safe('name');









            $this->mod_page_model->parent_id = $this->getOptionalInt('parent_id');
            $this->mod_page_model->root = Req::Post_Safe('root');
            $this->mod_page_model->status = Req::Post_Safe('status');

            $this->mod_page_model->deck = Req::Post_Safe('deck');

            $this->mod_page_model->summary = strip_tags(Req::Post('summary'));

            $this->mod_page_model->content = Req::Post('content');
            $this->mod_page_model->content = Helper::clear_html($this->mod_page_model->content);

            $this->mod_page_model->gallery_id = $this->getOptionalInt('gallery_id');
            $this->mod_page_model->file_id = $this->getOptionalInt('file_id');
            $this->mod_page_model->seo = Req::Post_Safe('seo');
            $this->mod_page_model->title = Req::Post_Safe('title');
            $this->mod_page_model->keywords = Req::Post_Safe('keywords');
            $this->mod_page_model->description = Req::Post_Safe('description');
            $this->mod_page_model->acl_id = $this->getOptionalInt('acl_id');
            $this->mod_page_model->category_id = $this->getOptionalInt('category_id');
            $selectedMenuSlot = $this->resolveSelectedMenuSlot('none');
            $menuBinding = $this->parseMenuSelection($selectedMenuSlot, $this->mod_page_model->lang);
            if ($menuBinding === FALSE) {
                $this->_error_message($this->lang['mod_page']['errors']['slot_branch_missing']);
                return FALSE;
            }
            $this->mod_page_model->menu = $menuBinding['legacy_value'];
            $this->mod_page_model->user_id = $this->session->get('id');

            // If image exists
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_page';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 1200,
                        'height' => NULL,
                    ],
                    '/thumb' =>
                    [
                        'width' => 250,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                ];
                if ($image->_resize($option)) {
                    $this->mod_page_model->image = $image->url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_page_model->image)) {
                    unset($this->mod_page_model->image);
                }
            }

            if ($this->mod_page_model->save(TRUE)) {
                if ($this->syncMenuRecord($menuBinding, '', (int) $this->mod_page_model->user_id) === FALSE) {
                    if (isset($image->url)) {
                        $image->_rollback();
                    }
                    $this->_error_message($this->lang['mod_page']['messages']['error']['add']);
                    return FALSE;
                }
                $this->_oke_message($this->lang['mod_page']['messages']['oke']['add']);
                Helper::yonlendir($this->site['saddress'] . 'mod_page/list', 1);
            } else {
                // If image exists
                if (isset($image->url)) {
                    $image->_rollback();
                }

                $this->_error_message($this->lang['mod_page']['messages']['error']['add']);
            }
        }
    }

    // Edit Function
    protected function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_page.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                if ($key == 'content') {
                    $this->tema->veri($key, Req::Post('content'));
                } else {
                    $this->tema->veri($key, Req::Post_Safe($key));
                }
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_page_model = Load::model('mod_page');

            $this->mod_page_model->id = $id;
            $this->mod_page_model->lang = Req::Post_Safe('lang');
            $this->mod_page_model->name = Req::Post_Safe('name');





            $this->mod_page_model->parent_id = $this->getOptionalInt('parent_id');
            $this->mod_page_model->root = Req::Post_Safe('root');
            $this->mod_page_model->status = Req::Post_Safe('status');

            $this->mod_page_model->deck = Req::Post_Safe('deck');

            $this->mod_page_model->summary = strip_tags(Req::Post('summary'));

            $this->mod_page_model->content = Req::Post('content');
            $this->mod_page_model->content = Helper::clear_html($this->mod_page_model->content);

            $this->mod_page_model->gallery_id = $this->getOptionalInt('gallery_id');
            $this->mod_page_model->file_id = $this->getOptionalInt('file_id');
            $this->mod_page_model->seo = Req::Post_Safe('seo');
            $this->mod_page_model->title = Req::Post_Safe('title');
            $this->mod_page_model->keywords = Req::Post_Safe('keywords');
            $this->mod_page_model->description = Req::Post_Safe('description');
            $this->mod_page_model->acl_id = $this->getOptionalInt('acl_id');
            $this->mod_page_model->category_id = $this->getOptionalInt('category_id');
            $selectedMenuSlot = $this->resolveSelectedMenuSlot('none');
            $menuBinding = $this->parseMenuSelection($selectedMenuSlot, $this->mod_page_model->lang);
            if ($menuBinding === FALSE) {
                $this->_error_message($this->lang['mod_page']['errors']['slot_branch_missing']);
                return FALSE;
            }
            $this->mod_page_model->menu = $menuBinding['legacy_value'];
            // Update related menu record if already sent to menu
            $new_mod_page = new mod_page_model();
            $new_mod_page->where_id($id)->gRo()->sync();
            $url = $new_mod_page->lang . '/page/' . $new_mod_page->seo;
            $menuUserId = (int) $new_mod_page->user_id;

            if (isset($this->mod_page_model->user_id)) {
                unset($this->mod_page_model->user_id);
            }

            // If image exists
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_page';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 1200,
                        'height' => NULL,
                    ],
                    '/thumb' =>
                    [
                        'width' => 250,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                ];
                if ($image->_resize($option)) {
                    $this->mod_page_model->image = $image->url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_page_model->image)) {
                    unset($this->mod_page_model->image);
                }
            }

            $this->mod_page_model->id = $id;
            if ($this->mod_page_model->update('id')) {
                if ($this->syncMenuRecord($menuBinding, $url, $menuUserId) === FALSE) {
                    if (isset($image->url)) {
                        $image->_rollback();
                    }
                    $this->_error_message($this->lang['mod_page']['messages']['error']['edit']);
                    return FALSE;
                }


                $this->_oke_message($this->lang['mod_page']['messages']['oke']['edit']);
                Helper::yonlendir($this->site['saddress'] . 'mod_page/list', 1);
            } else {
                // If image exists
                if (isset($image->url)) {
                    $image->_rollback();
                }
                $this->_error_message($this->lang['mod_page']['messages']['error']['edit']);
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


    // Delete Page
    public function dragdrop_delete()
    {
        // Check delete permission
        if ($this->user->hasPermission('mod_page',  'delete', $this->lang['mod_page']['role_errors'][ 'delete'], 1) === False) {
            return False;
        }
        $sil = Req::Post_Safe( 'delete');
        if ($this->_dragdrop_delete($sil) === TRUE) {
            echo 'Oke';
        } else {
            echo 'ERROR';
            //print_r($this->mod_page_model);
        }
    }

    // Delete Function
    protected function _dragdrop_delete($sil = NULL)
    {
        // Load mod_page model
        $this->mod_page_model = Load::model('mod_page');

        $this->mod_page_model->getid($sil)->getResultOne(TRUE, TRUE);
        if ($this->mod_page_model->getSize() === 0) {
            print($this->lang['mod_page']['errors']['record']);
            return FALSE;
        }
        $this->mod_page_model->sync();

        // Check permission if record belongs to someone else
        if ($this->user->checkPermission('mod_page', 'moderate_delete', $this->mod_page_model->user_id, $this->lang['mod_page']['role_errors']['moderate_delete']) === False) {
            return False;
        }
        $new_model = new mod_page_model();
        if ($new_model->exists_parent_id($sil)) {
            foreach ($new_model->toArray() as $s) {
                if ($this->_dragdrop_delete($s['id']) === TRUE) {
                    continue;
                }
                return FALSE;
            }
        }


        if ($this->mod_page_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_page';
            $image->name = 'image';
            $image->url = $this->mod_page_model->image;
            $option = [
                '/' =>
                [
                    'width' => 1200,
                    'height' => NULL,
                ],
                '/thumb' =>
                [
                    'width' => 250,
                    'height' => NULL,
                    'option' => 'width',
                ],
            ];
            if ($image->_delete_files($option) !== TRUE) {
                echo $image->error;
            }
        }


        $this->mod_page_model->getid($sil);
        // Check if gallery module is mapped
        $this->mod = Load::model('mod_gallery_mod');
        if ($this->mod->exists_mod_name("mod_page") === TRUE) {
            // Delete related gallery if exists
            Run::import('mod_gallery', '_remove', ['mod_page', $this->mod_page_model->id]);
        }
        // Check if file module is mapped
        $this->mod = Load::model('mod_attachment_mod');
        if ($this->mod->exists_mod_name("mod_page") === TRUE) {
            // Delete related files if exists
            Run::import('mod_attachment', '_remove', ['mod_page', $this->mod_page_model->id]);
        }

        $this->mod_page_model->delete();
        return TRUE;
    }

    // Approve Page
    public function approve()
    {
        // Check Approve Permission
        if ($this->user->hasPermission('mod_page', 'approval', $this->lang['mod_page']['role_errors']['approval']) === False) {
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
        if (is_array($query)) {
            foreach ($query as $s) {
                $page = Load::model('mod_page');
                $page->where_id($s)->gRo();
                if ($page->getSize() === 0) {
                    continue;
                }
                $page->sync();
                $page->status = 'y';
                if ($page->update('id')) {
                    // Update related menu record if it exists
                    $url = $page->lang . '/page/' . $page->seo;
                    $menuRecord = $this->getMenuRecordByUrl($url);
                    if ($menuRecord !== NULL) {
                        $menuRecord->status = 'y';
                        $menuRecord->update();
                    }
                    continue;
                }
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
    }

    // Disapprove Page
    public function unapprove()
    {
        // Check Approve Permission
        if ($this->user->hasPermission('mod_page', 'approval', $this->lang['mod_page']['role_errors']['approval']) === False) {
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
        if (is_array($query)) {
            foreach ($query as $s) {
                $page = Load::model('mod_page');
                $page->where_id($s)->gRo();
                if ($page->getSize() === 0) {
                    continue;
                }
                $page->sync();
                $page->status = 'n';
                if ($page->update('id')) {
                    // Update related menu record if it exists
                    $url = $page->lang . '/page/' . $page->seo;
                    $menuRecord = $this->getMenuRecordByUrl($url);
                    if ($menuRecord !== NULL) {
                        $menuRecord->status = 'n';
                        $menuRecord->update();
                    }
                    continue;
                }
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
    }

    // Valid Message
    protected function _oke_message($mesaj)
    {
        $this->tema->veri('oke', True);
        $this->tema->veri('mesaj', $mesaj);
    }

    // Error Message
    protected function _error_message($mesaj)
    {
        $this->tema->veri('hata', True);
        $this->tema->veri('hatalar', [$mesaj]);
    }

    // Error Message
    protected function _error($mesaj)
    {
        $this->tema->veri('error', TRUE);
        $this->tema->veri('mesaj', $mesaj);
    }
}