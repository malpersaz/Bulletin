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
    protected $has_faq = true;

    protected $need_simple_data = [
        'lang' => 'lang_lang',
        'mod_acl' => 'mod_acl_acl_id'
    ];

    protected $image_fields = [
        [
            'name' => 'image',
            'sizes' => [
                '/' => [
                    'width' => 1600,
                    'height' => NULL,
                    'option' => 'width',
                ],
                '/thumb' => [
                    'width' => 600,
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
        'action' => ['##ACTION MENU', 'ACTION MENU', '##ACTION', 'ACTION', 'CTA MENU', '##CTA MENU', 'ACTIONMENU', 'ACTION_MENU', 'PESEKA CALAKIYE', 'ÇALAKÎ', 'AKTIONSMENU', 'AKTIONSMENÜ', 'EYLEM MENUSU'],
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
        if (!is_array($rows) || $rows === []) {
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

        $this->theme->set('lang_lang', $langs);
        $this->theme->set('selected_lang', $selectedLang);

        $mod_page_nested = Load::model('mod_page');
        $mod_page_nested->flush();
        if ($selectedLang !== '') {
            $mod_page_nested->where_lang($selectedLang);
        }
        $mod_page_nested->order('order', 'ASC')->all();
        $this->theme->set('select_nested_parent_id_result', $this->buildNestedRows($mod_page_nested->toArray()));

        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY id DESC");
        $this->theme->set('mod_acl_acl_id', $this->db->getResult()->toArray());

        $this->theme->set('select_nested_category_id_result', []);

        $menuContext = $this->getMenuParentContext($selectedLang, $selectedMenuSlot);
        $this->theme->set('select_nested_menu_result', $menuContext['tree']);
        $this->theme->set('menu_parent_root_id', $menuContext['root_parent_id']);
        $this->theme->set('selected_menu_slot', $selectedMenuSlot);
        $this->theme->set('menu_slot_options', $this->getMenuSlotOptions());
        $this->theme->set('menu_binding_value', $this->getPostedMenuSelectionValue($menuBindingValue));

        $pageQuery = "SELECT id, name, lang FROM `mod_page`";
        $where = [];
        if ($selectedLang !== '') {
            $where[] = "lang='" . $selectedLang . "'";
        }
        if ((int) $excludePageId !== 0) {
            $where[] = "id!='" . (int) $excludePageId . "'";
        }
        if ($where !== []) {
            $pageQuery .= " WHERE " . implode(' AND ', $where);
        }
        $pageQuery .= " ORDER BY id DESC";
        $this->db->fetch_array($pageQuery);
        $pageReferences = $this->db->getResult()->toArray();
        $this->theme->set('mod_page_gallery_id', $pageReferences);
        $this->theme->set('mod_page_file_id', $pageReferences);
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
            
            $maxMenu = Load::model('mod_menu');
            $maxMenu->select('`order`');
            $maxMenu->addWhere("parent_id='" . (int) $menuBinding['parent_id'] . "'");
            if ($this->mod_page_model->lang !== '') {
                $maxMenu->addWhere("lang='" . $this->mod_page_model->lang . "'");
            }
            $maxMenu->order('`order`', 'DESC')->limit(1)->all();
            $maxRows = $maxMenu->toArray();
            $maxOrder = isset($maxRows[0]['order']) && Req::isNumeric((string) $maxRows[0]['order']) ? (int) $maxRows[0]['order'] : 0;
            
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
        $this->theme->tpl('mod_page/add');
        $this->theme->set('site', $this->site);
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
            $this->theme->tpl('mod_page/edit');
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
                    $d[$key] = $key == 'content' ? Req::Post('content') : Req::Post_Safe($key);
                }
            }
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_page']['edit'];
        $this->theme->set('site', $this->site);
        $this->theme->set('d', $d);
        $this->theme->tpl('mod_page/edit');
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
        $this->theme->tpl('mod_page/list');
        $this->theme->set('site', $this->site);
        $this->theme->set('module_lang_key', 'mod_page');
        $visibleLangs = $this->getVisibleLanguageCodes();
        $mod_page_nested = Load::model('mod_page');
        $mod_page_nested->order('order', 'ASC')->all();
        $visibleRows = [];
        $pageRows = $mod_page_nested->toArray();
        if (is_array($pageRows)) {
            foreach ($pageRows as $row) {
                if (!isset($row['lang']) || in_array((string) $row['lang'], $visibleLangs, TRUE)) {
                    $visibleRows[] = $row;
                }
            }
        }
        $select_nested_mod_page_result = $this->buildNestedRows($visibleRows);
        $this->theme->set('select_nested_mod_page_result', $select_nested_mod_page_result);

        $totalPages = count($visibleRows);
        $publishedPages = 0;
        $draftPages = 0;
        foreach ($visibleRows as $row) {
            if (isset($row['status']) && $row['status'] === 'y') {
                $publishedPages++;
            } else {
                $draftPages++;
            }
        }
        $this->theme->set('total_pages', $totalPages);
        $this->theme->set('published_pages', $publishedPages);
        $this->theme->set('draft_pages', $draftPages);
    }
    // mod_page order change
    public function _update_order()
    {
        $b = Req::Post_Safe('order');
        $mod_page_model = new mod_page_model();
        $x = 1;
        if (is_array($b)) {
            foreach ($b as $a) {
                $mod_page_model->id = $a;
                $mod_page_model->order = $x;
                if ($mod_page_model->update('id') === False) {
                    return FALSE;
                }
                $x++;
            }
        }
        return TRUE;
    }


    // mod_page drag/drop move
    public function reorder()
    {
        // Check move permission
        if ($this->user->hasPermission('mod_page', 'edit', $this->lang['mod_page']['role_errors']['edit']) === False) {
            return False;
        }
        $mod_page_model = Load::model('mod_page');
        $mod_page_model->id = Req::Post_Safe('id');
        $mod_page_model->parent_id = Req::Post_Safe('parent_id');
        if ($mod_page_model->update('id') && $this->_update_order()) {
            echo 'Success';
        } else {
            echo 'Error';
        }
    }

    // Add Function
    protected function _add()
    {
        $postedWizardStep = $this->getOptionalInt('wizard_step', 1);
        $this->theme->set('wizard_step', $postedWizardStep);

        $this->val = Load::validator('mod_page.add');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                if ($key == 'content') {
                    $this->theme->set($key, Req::Post('content'));
                } elseif ($key == 'lang') {
                    $this->theme->set('selected_lang', Req::Post_Safe('lang'));
                } else {
                    $this->theme->set($key, Req::Post_Safe($key));
                }
            }
            $this->theme->set('errors', $this->val->errors);
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
                        'width' => 1600,
                        'height' => NULL,
                    ],
                    '/thumb' =>
                    [
                        'width' => 600,
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
                $this->_success_message($this->lang['mod_page']['messages']['success']['add']);
                
                // Notify Google Indexing API of new page
                if ($this->mod_page_model->status === 'y') {
                    $site_config = Registry::get('site_config');
                    $baseUrl = isset($site_config['address']) ? $site_config['address'] : '';
                    if (!empty($baseUrl)) {
                        $absoluteUrl = rtrim($baseUrl, '/') . '/' . $this->mod_page_model->lang . '/page/' . $this->mod_page_model->seo;
                        $indexingService = new GoogleIndexingService();
                        $indexingService->notifyUpdate($absoluteUrl);
                    }
                }

                Helper::redirect($this->site['saddress'] . 'mod_page/list', 1);
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
                    $this->theme->set($key, Req::Post('content'));
                } else {
                    $this->theme->set($key, Req::Post_Safe($key));
                }
            }
            $this->theme->set('errors', $this->val->errors);
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
                        'width' => 1600,
                        'height' => NULL,
                    ],
                    '/thumb' =>
                    [
                        'width' => 600,
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

                $newUrl = $this->mod_page_model->lang . '/page/' . $this->mod_page_model->seo;
                if ($url !== $newUrl) {
                    $this->db->query("UPDATE `mod_menu` SET `url` = " . $this->db->quote($newUrl) . " WHERE `url` = " . $this->db->quote($url));
                }

                $this->_success_message($this->lang['mod_page']['messages']['success']['edit']);

                // Notify Google Indexing API of page update
                $site_config = Registry::get('site_config');
                $baseUrl = isset($site_config['address']) ? $site_config['address'] : '';
                if (!empty($baseUrl)) {
                    $indexingService = new GoogleIndexingService();
                    $newUrl = $this->mod_page_model->lang . '/page/' . $this->mod_page_model->seo;
                    $absoluteNewUrl = rtrim($baseUrl, '/') . '/' . $newUrl;
                    
                    if ($this->mod_page_model->status === 'y') {
                        $indexingService->notifyUpdate($absoluteNewUrl);
                    } elseif ($this->mod_page_model->status === 'n') {
                        $indexingService->notifyDelete($absoluteNewUrl);
                    }
                    
                    // If URL changed and it was published, notify deletion of old URL
                    if ($url !== $newUrl && $this->mod_page_model->status === 'y') {
                        $absoluteOldUrl = rtrim($baseUrl, '/') . '/' . $url;
                        $indexingService->notifyDelete($absoluteOldUrl);
                    }
                }

                Helper::redirect($this->site['saddress'] . 'mod_page/list', 1);
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
        if ($this->user->hasPermission('mod_page', 'delete', $this->lang['mod_page']['role_errors']['delete'], 1) === False) {
            return False;
        }
        $deleteId = Req::Post_Safe('delete');
        if ($this->_dragdrop_delete($deleteId) === TRUE) {
            echo 'Success';
        } else {
            echo 'ERROR';
        }
    }

    // Delete Function
    protected function _dragdrop_delete($deleteId = NULL)
    {
        // Load mod_page model
        $this->mod_page_model = Load::model('mod_page');

        $this->mod_page_model->getid($deleteId)->getResultOne(TRUE, TRUE);
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


        if ($this->mod_page_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_page';
            $image->name = 'image';
            $image->url = $this->mod_page_model->image;
            $option = [
                '/' =>
                [
                    'width' => 1600,
                    'height' => NULL,
                ],
                '/thumb' =>
                [
                    'width' => 600,
                    'height' => NULL,
                    'option' => 'width',
                ],
            ];
            if ($image->_delete_files($option) !== TRUE) {
                echo $image->error;
            }
        }


        $this->mod_page_model->getid($deleteId);
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

        // Notify Google Indexing API of deletion
        $site_config = Registry::get('site_config');
        $baseUrl = isset($site_config['address']) ? $site_config['address'] : '';
        if (!empty($baseUrl)) {
            $absoluteUrl = rtrim($baseUrl, '/') . '/' . $this->mod_page_model->lang . '/page/' . $this->mod_page_model->seo;
            $indexingService = new GoogleIndexingService();
            $indexingService->notifyDelete($absoluteUrl);
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
                    // Notify Google Indexing API of approval
                    $site_config = Registry::get('site_config');
                    $baseUrl = isset($site_config['address']) ? $site_config['address'] : '';
                    if (!empty($baseUrl)) {
                        $absoluteUrl = rtrim($baseUrl, '/') . '/' . $page->lang . '/page/' . $page->seo;
                        $indexingService = new GoogleIndexingService();
                        $indexingService->notifyUpdate($absoluteUrl);
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
                    // Notify Google Indexing API of unapproval
                    $site_config = Registry::get('site_config');
                    $baseUrl = isset($site_config['address']) ? $site_config['address'] : '';
                    if (!empty($baseUrl)) {
                        $absoluteUrl = rtrim($baseUrl, '/') . '/' . $page->lang . '/page/' . $page->seo;
                        $indexingService = new GoogleIndexingService();
                        $indexingService->notifyDelete($absoluteUrl);
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
    protected function _success_message($message)
    {
        $this->theme->set('Success', True);
        $this->theme->set('message', $message);
    }

    // Error Message
    protected function _error_message($message)
    {
        $this->theme->set('error', True);
        $this->theme->set('errors', [$message]);
    }

    // Error Message
    protected function _error($message)
    {
        $this->theme->set('error', TRUE);
        $this->theme->set('message', $message);
    }

    public function ajax_get_files()
    {
        if ($this->user->hasPermission('mod_page', 'file', $this->lang['mod_page']['role_errors']['file'] ?? 'Permission denied', 1) === false && Role::hasPermission('mod_page', 'edit') === false) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            return false;
        }

        $q_param = Req::is_Post('q') ? Req::Post_Safe('q') : null;
        $model = Load::model('mod_attachment');
        $model->select('id, original_name as name');
        if ($q_param) {
            $model->addWhere("original_name LIKE '%" . $q_param . "%'");
        }
        $model->order('id', 'DESC')->gR();

        $results = [];
        if ($model->getSize() > 0) {
            foreach ($model->toArray() as $item) {
                $results[] = [
                    'id'   => intval($item['id']),
                    'name' => $item['name'],
                    'text' => $item['name']
                ];
            }
        }

        if (ob_get_level() > 0) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($results);
        return true;
    }

    public function ajax_get_current_media()
    {
        $id = intval(Req::Post_Safe('id'));
        if ($id <= 0) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'file_id' => 0,
                'gallery_id' => 0,
                'video' => [],
            ]);
            return false;
        }

        $page_model = Load::model('mod_page');
        $page_model->select('id, file_id, gallery_id')->where_id($id)->gRo();
        if ($page_model->getSize() === 0) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'file_id' => 0,
                'gallery_id' => 0,
                'video' => [],
            ]);
            return false;
        }
        $page_model->sync();

        if (ob_get_level() > 0) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'file_id' => intval($page_model->file_id ?? 0),
            'gallery_id' => intval($page_model->gallery_id ?? 0),
            'video' => [],
        ]);
        return true;
    }

    public function ajax_update_relation()
    {
        if ($this->user->hasPermission('mod_page', 'file', $this->lang['mod_page']['role_errors']['file'] ?? 'Permission denied', 1) === false && Role::hasPermission('mod_page', 'edit') === false) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'message' => $this->lang['mod_page']['role_errors']['file'] ?? ($this->lang['mod_page']['role_errors']['edit'] ?? 'Permission denied'),
            ]);
            return false;
        }

        $id = intval(Req::Post_Safe('id'));
        $type = Req::Post_Safe('type');
        $value = Req::Post('value');

        if ($id <= 0) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'message' => $this->lang['mod_page']['errors']['record'] ?? 'Record not found',
            ]);
            return false;
        }

        $page_model = Load::model('mod_page');
        $page_model->where_id($id)->gRo();
        if ($page_model->getSize() === 0) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'message' => $this->lang['mod_page']['errors']['record'] ?? 'Record not found',
            ]);
            return false;
        }
        $page_model->sync();

        if ($type === 'gallery' || $type === 'files') {
            $column = ($type === 'gallery') ? 'gallery_id' : 'file_id';
            $page_model->$column = intval($value);
            $page_model->update('id');

            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'success',
                'message' => $this->lang['mod_page']['ajax']['relation_updated'] ?? ($this->lang['mod_page']['messages']['success']['edit'] ?? 'Relation updated successfully'),
            ]);
            return true;
        }

        if (ob_get_level() > 0) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => $this->lang['mod_page']['ajax']['unknown_type'] ?? 'Unknown type',
        ]);
        return false;
    }
}