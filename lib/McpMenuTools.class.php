<?php
/**
 * Amed Framework
 * MCP Menu Tools - CRUD implementations for mod_menu
 * lib/McpMenuTools.class.php
 *
 * Mirrors the admin panel: tree validation (parent ancestry language),
 * structural container protection, and recursive deletion with bridge cleanup.
 */

class McpMenuTools extends McpCrudToolsBase
{
    private const SLOT_ALIASES = [
        'header' => ['##HEADER MENU', 'HEADER MENU', '##HEADER', 'HEADER', 'UST MENU', 'TOP MENU', 'MAIN MENU', 'MAINMENU', 'ANA MENU', 'ANAMENU', 'SERPESEK', 'SER PESEK', 'HAUPTMENU', 'HAUPT MENU', 'MENU PRINCIPAL', 'MENUPRINCIPAL'],
        'footer' => ['##FOOTER MENU', 'FOOTER MENU', '##FOOTER', 'FOOTER', 'ALT MENU', 'BOTTOM MENU', 'FOOTERMENU', 'ALTMENU', 'BINPESEK', 'BIN PESEK', 'FUSSMENU', 'FUSS MENU', 'MENU PIED', 'MENU DU PIED'],
        'sidebar' => ['##SIDEBAR', 'SIDEBAR', 'SOL MENU', 'SIDE MENU', 'SIDEBARMENU', 'PALPESEK'],
        'action' => ['##ACTION MENU', 'ACTION MENU', '##ACTION', 'ACTION', 'CTA MENU', '##CTA MENU', 'ACTIONMENU', 'ACTION_MENU'],
    ];

    /**
     * Lists menu items with optional filters, ordered as displayed
     */
    public function listMenuItems(array $args): array
    {
        $limit = $this->normalizeLimit($args, 100);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_menu');
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
        }
        if (isset($args['parent_id']) && (int) $args['parent_id'] >= 0) {
            $model->where_parent_id((int) $args['parent_id']);
        }
        if (isset($args['status']) && in_array($args['status'], ['y', 'n'], true)) {
            $model->where_status($args['status']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $model->where('name LIKE \'%' . Obuilder::cc(trim((string) $args['search'])) . '%\'');
        }
        $model->order('`order`', 'ASC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_menu');
        $countModel->select('id');
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $countModel->where_lang(trim((string) $args['lang']));
        }
        if (isset($args['parent_id']) && (int) $args['parent_id'] >= 0) {
            $countModel->where_parent_id((int) $args['parent_id']);
        }
        if (isset($args['status']) && in_array($args['status'], ['y', 'n'], true)) {
            $countModel->where_status($args['status']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $countModel->where('name LIKE \'%' . Obuilder::cc(trim((string) $args['search'])) . '%\'');
        }
        $countModel->getResult(true, true);
        $total = $countModel->getSize();

        return [
            'items' => is_array($items) ? $items : [],
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Returns a single menu item with all fields
     */
    public function getMenuItem(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadMenuItemById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Menu item '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a menu item under an existing parent
     */
    public function createMenuItem(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }
        if ($this->isContainerName($name)) {
            throw new McpToolException("Field 'name' cannot start with '##' - structural container names are reserved for the system.");
        }

        $lang = $this->requireActiveLang($args);

        $parentId = (int) ($args['parent_id'] ?? 0);
        if ($parentId <= 0) {
            throw new McpToolException("Field 'parent_id' is required - items must live under a slot root or another menu item.");
        }

        $this->assertParentLanguage($lang, $parentId);

        $model = $this->freshModel('mod_menu');
        $model->lang = $lang;
        $model->name = $name;
        $model->url = $this->optionalString($args, 'url', '', 255);
        $model->file = $this->optionalString($args, 'file', '', 255);
        $model->parent_id = $parentId;
        $model->target = $this->optionalEnum($args, 'target', 'n', ['y', 'n']);
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        $model->html = (string) ($args['html'] ?? '');
        $model->icon = $this->optionalString($args, 'icon', '', 255);
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $model->user_id = $this->actingUserId;
        $model->order = $this->nextMenuSiblingOrder($parentId, $lang);

        if (!$model->save(true)) {
            throw new McpToolException('Failed to create the menu item record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getMenuItem(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a menu item
     */
    public function updateMenuItem(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadMenuItemById($id);

        $this->assertModerateEdit('mod_menu', (int) $model->user_id);

        if ((string) ($args['lang'] ?? '') !== '' || array_key_exists('parent_id', $args)) {
            $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;
            $parentId = array_key_exists('parent_id', $args) ? (int) $args['parent_id'] : (int) $model->parent_id;

            if ($parentId === $id) {
                throw new McpToolException('A menu item cannot be its own parent.');
            }
            if ($parentId <= 0) {
                throw new McpToolException('Menu items cannot be moved to the top level; use an existing slot root or parent item.');
            }
            $this->assertParentLanguage($lang, $parentId, $id);

            $model->lang = $lang;
            $model->parent_id = $parentId;
        }

        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 2-255 characters.");
            }
            if ($this->isContainerName($name) && !$this->isContainerName((string) $model->name)) {
                throw new McpToolException("Field 'name' cannot start with '##' - structural container names are reserved for the system.");
            }
            $model->name = $name;
        }

        if (isset($args['url'])) {
            $model->url = $this->optionalString($args, 'url', '', 255);
        }
        if (isset($args['file'])) {
            $model->file = $this->optionalString($args, 'file', '', 255);
        }
        foreach (['target', 'status'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalEnum($args, $field, (string) $model->{$field}, ['y', 'n']);
            }
        }
        if (isset($args['html'])) {
            $model->html = (string) $args['html'];
        }
        if (isset($args['icon'])) {
            $model->icon = $this->optionalString($args, 'icon', '', 255);
        }
        if (isset($args['acl_id'])) {
            $model->acl_id = (int) $args['acl_id'];
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the menu item record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getMenuItem(['id' => $id])['item']
        ];
    }

    /**
     * Recursively deletes a menu subtree with container protection
     */
    public function deleteMenuItem(array $args): array
    {
        $id = $this->requireId($args);
        $deletedIds = [];
        $this->deleteMenuTree($id, $deletedIds);

        return ['deleted' => true, 'ids' => $deletedIds];
    }

    // ------------------------------------------------------------------
    // Menu helpers
    // ------------------------------------------------------------------

    private function deleteMenuTree(int $id, array &$deletedIds): void
    {
        $model = $this->loadMenuItemById($id);

        $row = $model->toArray();
        if (is_array($row) && $this->isContainerRow($row)) {
            throw new McpToolException("Menu item '{$id}' is a structural container row (language root or menu slot) and cannot be deleted.");
        }

        $this->assertModerateDelete('mod_menu', (int) $model->user_id);

        $childrenModel = $this->freshModel('mod_menu');
        $childrenModel->where_parent_id($id)->getResult(true, true);
        foreach (($childrenModel->toArray() ?? []) as $child) {
            $this->deleteMenuTree((int) $child['id'], $deletedIds);
        }

        $this->removeBridgeRelations('mod_menu', $id);

        $model->delete();
        $deletedIds[] = $id;
    }

    private function loadMenuItemById(int $id): mod_menu_model
    {
        $model = $this->freshModel('mod_menu');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Menu item '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function nextMenuSiblingOrder(int $parentId, string $lang): int
    {
        $model = $this->freshModel('mod_menu');
        $model->where_parent_id($parentId)->where_lang($lang)->order('`order`', 'DESC')->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            return 1;
        }
        $model->sync();
        return (int) $model->order + 1;
    }

    private function isContainerName(string $name): bool
    {
        return strpos($name, '##') === 0;
    }

    private function isContainerRow(array $row): bool
    {
        $url = isset($row['url']) ? trim((string) $row['url']) : '';
        $name = isset($row['name']) ? trim((string) $row['name']) : '';

        return $url === '##' || strpos($name, '##') === 0;
    }

    /**
     * Resolves the language of a parent by walking its ancestry,
     * mirroring resolveMenuLanguageFromAncestry in the admin controller
     */
    private function assertParentLanguage(string $lang, int $parentId, int $currentId = 0): void
    {
        $rowsById = $this->getAllMenuRows();

        if (!isset($rowsById[$parentId])) {
            throw new McpToolException("Parent menu item '{$parentId}' not found.");
        }

        $resolvedLang = $this->resolveMenuLanguageFromAncestry($rowsById, $rowsById[$parentId]);
        if ($resolvedLang !== '' && $resolvedLang !== $lang) {
            throw new McpToolException("Parent item '{$parentId}' belongs to menu language '{$resolvedLang}', cannot attach a '{$lang}' item under it.");
        }
    }

    private function getAllMenuRows(): array
    {
        $db = Load::lib('Database');
        $db->fetch_array('SELECT * FROM mod_menu');
        $rows = $db->getResult()->toArray();
        $rowsById = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $rowsById[(int) ($row['id'] ?? 0)] = $row;
            }
        }
        return $rowsById;
    }

    private function resolveMenuLanguageFromAncestry(array $rowsById, array $startRow): string
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

            if ($isTopLevel && $isContainer && !$this->isSlotRoot($current) && $currentLang !== '') {
                return $currentLang;
            }

            $parentId = isset($current['parent_id']) ? (int) $current['parent_id'] : 0;
            if ($parentId === 0 || !isset($rowsById[$parentId])) {
                break;
            }

            $current = $rowsById[$parentId];
        }

        return isset($startRow['lang']) ? trim((string) $startRow['lang']) : '';
    }

    private function isSlotRoot(array $row): bool
    {
        $name = strtoupper(trim((string) ($row['name'] ?? '')));
        foreach (self::SLOT_ALIASES as $aliases) {
            if (in_array($name, $aliases, true)) {
                return true;
            }
        }
        return false;
    }
}
