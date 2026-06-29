<?php
class mod_menu_helper extends indexBase
{
    private $syntheticMenuId = -1;
    private $slotAliases = [
        'header' => ['##HEADER MENU', 'HEADER MENU', '##HEADER', 'HEADER', 'UST MENU', 'TOP MENU', 'MAIN MENU'],
        'footer' => ['##FOOTER MENU', 'FOOTER MENU', '##FOOTER', 'FOOTER', 'ALT MENU', 'BOTTOM MENU'],
        'sidebar' => ['##SIDEBAR', 'SIDEBAR', 'SOL MENU', 'SIDE MENU'],
    ];

    private function normalizeRootContainerRows(array $menuRows)
    {
        foreach ($menuRows as &$row) {
            if (!is_array($row)) {
                continue;
            }

            $isRootContainer = isset($row['parent_id'], $row['name'])
                && (int) $row['parent_id'] === 0
                && strpos((string) $row['name'], '##') === 0;

            if (!$isRootContainer) {
                continue;
            }

            $url = isset($row['url']) ? trim((string) $row['url']) : '';
            if ($url === '') {
                $row['url'] = '##';
            }
        }
        unset($row);

        return $menuRows;
    }

    private function loadMenuRows()
    {
        $modelClass = Load::modelfile('mod_menu');
        $menuModel = new $modelClass();
        $menuModel->order('order', 'ASC')->all();
        $menuRows = $menuModel->toArray();

        if (!is_array($menuRows)) {
            return [];
        }

        return $this->normalizeRootContainerRows($menuRows);
    }

    private function isContainerRow(array $row)
    {
        $url = isset($row['url']) ? trim((string) $row['url']) : '';
        $name = isset($row['name']) ? trim((string) $row['name']) : '';

        return $url === '##' || strpos($name, '##') === 0;
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

    private function findLanguageRootId(array $menuRows)
    {
        foreach ($menuRows as $row) {
            if (!$this->isContainerRow($row)) {
                continue;
            }

            if ((int) ($row['parent_id'] ?? -1) !== 0) {
                continue;
            }

            if (($row['lang'] ?? '') !== $this->site['lang']) {
                continue;
            }

            $isSlotRoot = false;
            foreach (array_keys($this->slotAliases) as $slot) {
                if ($this->rowMatchesSlotAlias($row, $slot)) {
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

    private function findSlotRootId(array $menuRows, $slot)
    {
        $languageRootId = $this->findLanguageRootId($menuRows);
        if ($languageRootId === null) {
            return null;
        }

        foreach ($menuRows as $row) {
            if (!$this->isContainerRow($row)) {
                continue;
            }

            if ((int) ($row['parent_id'] ?? -1) !== (int) $languageRootId) {
                continue;
            }

            if (($row['lang'] ?? '') !== $this->site['lang']) {
                continue;
            }

            if ($this->rowMatchesSlotAlias($row, $slot)) {
                return (int) $row['id'];
            }
        }

        return null;
    }

    private function collectBranchRows(array $nestedMenu, $rootId, array &$collected)
    {
        if (!isset($nestedMenu[$rootId]) || !is_array($nestedMenu[$rootId])) {
            return;
        }

        foreach ($nestedMenu[$rootId] as $row) {
            $collected[] = $row;
            $this->collectBranchRows($nestedMenu, (int) $row['id'], $collected);
        }
    }

    public function menuBranch($slot)
    {
        $menuRows = $this->loadMenuRows();
        if (empty($menuRows)) {
            return ['tree' => [], 'root_id' => null];
        }

        $menuRows = $this->mergePageChildrenIntoMenu($menuRows);

        $branchRootId = $this->findSlotRootId($menuRows, $slot);

        if ($branchRootId === null) {
            return ['tree' => [], 'root_id' => null];
        }

        $nestedMenu = Helper::nested($menuRows, 'parent_id', 'id');
        $branchRoot = null;
        foreach ($menuRows as $row) {
            if ((int) ($row['id'] ?? 0) === (int) $branchRootId) {
                $branchRoot = $row;
                break;
            }
        }

        if ($branchRoot === null) {
            return ['tree' => [], 'root_id' => null];
        }

        $branchRows = [$branchRoot];
        $this->collectBranchRows($nestedMenu, $branchRootId, $branchRows);

        return [
            'tree' => Helper::nested($branchRows, 'parent_id', 'id'),
            'root_id' => $branchRootId,
        ];
    }

    private function hasConfiguredDatabase()
    {
        $database = Kayit::getir('database');
        $dbConfig = Kayit::getir('db_config');

        if (!is_string($database) || $database === '') {
            return false;
        }

        if (!is_array($dbConfig) || !isset($dbConfig[$database]) || !is_array($dbConfig[$database])) {
            return false;
        }

        return isset($dbConfig[$database]['adapter'], $dbConfig[$database]['conf']) && is_array($dbConfig[$database]['conf']);
    }

    private function extractPageSeoFromUrl($url)
    {
        $prefix = $this->site['lang'] . '/page/';

        if (!is_string($url) || strpos($url, $prefix) !== 0) {
            return null;
        }

        $seo = trim(substr($url, strlen($prefix)), '/');
        return $seo !== '' ? $seo : null;
    }

    private function createMenuRowFromPage(array $page, $parentId)
    {
        return [
            'id' => $this->syntheticMenuId--,
            'lang' => $page['lang'],
            'name' => $page['name'],
            'url' => $page['lang'] . '/page/' . $page['seo'],
            'file' => '',
            'parent_id' => $parentId,
            'order' => (int) $page['order'],
            'target' => 'n',
            'status' => $page['status'],
            'html' => '',
            'icon' => '',
            'acl_id' => 0,
            'user_id' => isset($page['user_id']) ? (int) $page['user_id'] : 0,
        ];
    }

    private function appendPageChildren(array $page, array $pagesByParent, array &$menuRows, array &$menuByParentUrl)
    {
        if (!isset($pagesByParent[$page['id']]) || !is_array($pagesByParent[$page['id']])) {
            return;
        }

        foreach ($pagesByParent[$page['id']] as $childPage) {
            $childUrl = $childPage['lang'] . '/page/' . $childPage['seo'];
            $parentId = $page['menu_parent_id'];

            if (isset($menuByParentUrl[$parentId][$childUrl])) {
                $childMenuId = $menuByParentUrl[$parentId][$childUrl];
            } else {
                $menuRow = $this->createMenuRowFromPage($childPage, $parentId);
                $menuRows[] = $menuRow;
                $childMenuId = $menuRow['id'];
                $menuByParentUrl[$parentId][$childUrl] = $childMenuId;
            }

            $childPage['menu_parent_id'] = $childMenuId;
            $this->appendPageChildren($childPage, $pagesByParent, $menuRows, $menuByParentUrl);
        }
    }

    private function mergePageChildrenIntoMenu(array $menuRows)
    {
        $pageModel = Load::model('mod_page');
        $pageModel->where_lang($this->site['lang']);
        $pageModel->where_status('y');
        $pageModel->order('order', 'ASC')->all();
        $pages = $pageModel->toArray();

        if (!is_array($pages) || empty($pages)) {
            return $menuRows;
        }

        $pagesBySeo = [];
        $pagesByParent = [];
        foreach ($pages as $page) {
            if (!isset($page['id'], $page['seo'], $page['parent_id'])) {
                continue;
            }

            $pagesBySeo[$page['seo']] = $page;
            $pagesByParent[$page['parent_id']][] = $page;
        }

        $menuByParentUrl = [];
        foreach ($menuRows as $row) {
            if (!isset($row['parent_id'], $row['url'], $row['id'])) {
                continue;
            }

            $menuByParentUrl[$row['parent_id']][$row['url']] = $row['id'];
        }

        foreach ($menuRows as $row) {
            if (!isset($row['id'], $row['url'])) {
                continue;
            }

            $pageSeo = $this->extractPageSeoFromUrl($row['url']);
            if ($pageSeo === null || !isset($pagesBySeo[$pageSeo])) {
                continue;
            }

            $page = $pagesBySeo[$pageSeo];
            $page['menu_parent_id'] = $row['id'];
            $this->appendPageChildren($page, $pagesByParent, $menuRows, $menuByParentUrl);
        }

        return $menuRows;
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function menu()
    {
        if (!$this->hasConfiguredDatabase()) {
            return [];
        }

        $menuRows = $this->loadMenuRows();
        if (empty($menuRows)) {
            return [];
        }

        $menuRows = $this->mergePageChildrenIntoMenu($menuRows);
        return Helper::nested($menuRows, 'parent_id', 'id');
    }
}
