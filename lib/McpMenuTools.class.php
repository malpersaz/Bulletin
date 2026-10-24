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
    /**
     * Return array of tool definitions provided by this domain tool class.
     *
     * @return array<string, array>
     */
    public static function definitions(): array
    {
        return [
            'list_menu_items' => [
                'module' => 'mod_menu',
                'role' => 'list',
                'domain' => 'menu',
                'handler' => 'listMenuItems',
                'description' => 'List menu items with optional filters, ordered by display order. Structural container rows (language roots named ##<lang> and menu slots named ##Header_Menu, ##Footer_Menu, ##Action_Menu) carry the ## prefix and cannot be deleted or renamed.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Filter by language code (e.g. ku, en, tr).',
                        ],
                        'parent_id' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'description' => 'Filter by parent item ID (0 = top level structural rows).',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'Filter by active status: y or n.',
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'Search term matched against menu names.',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 250,
                            'default' => 100,
                            'description' => 'Page size (1-250, default 100).',
                        ],
                        'offset' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'default' => 0,
                            'description' => 'Number of items to skip for pagination.',
                        ],
                        'compact' => [
                            'type' => 'boolean',
                            'default' => true,
                            'description' => 'Return compact projection (id, lang, name, url, parent_id, order, status) to optimize token efficiency.',
                        ],
                    ],
                ],
                'title' => 'List Menu Items',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'get_menu_item' => [
                'module' => 'mod_menu',
                'role' => 'list',
                'domain' => 'menu',
                'handler' => 'getMenuItem',
                'description' => 'Get a single menu item with all fields.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Menu item record ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Get Menu Item',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'create_menu_item' => [
                'module' => 'mod_menu',
                'role' => 'add',
                'domain' => 'menu',
                'handler' => 'createMenuItem',
                'description' => 'Create a menu item under an existing parent (a menu slot root or another item). The parent ancestry language must match the item language. The sort order defaults to the end of the sibling list. Structural container names starting with ## are reserved.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Language code of an active site language (e.g. ku, en, tr).',
                        ],
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Menu label (2-255 characters; cannot start with ##).',
                        ],
                        'parent_id' => [
                            'type' => 'integer',
                            'description' => 'Parent slot root or parent item ID with a matching ancestry language.',
                        ],
                        'url' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Link target URL or route (e.g. tr/newses/my_category). Optional when html is used.',
                        ],
                        'file' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Custom controller file reference.',
                        ],
                        'target' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'default' => 'n',
                            'description' => 'Open in new window: y or n.',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'default' => 'y',
                            'description' => 'Active status: y or n.',
                        ],
                        'html' => [
                            'type' => 'string',
                            'description' => 'Raw HTML content instead of a link.',
                        ],
                        'icon' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Icon class (e.g. bi bi-house-door).',
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'default' => 0,
                            'description' => 'Access control group ID (0 = public).',
                        ],
                    ],
                    'required' => [
                        'lang',
                        'name',
                        'parent_id',
                    ],
                ],
                'title' => 'Create Menu Item',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => false,
                ],
            ],
            'update_menu_item' => [
                'module' => 'mod_menu',
                'role' => 'edit',
                'domain' => 'menu',
                'handler' => 'updateMenuItem',
                'description' => 'Partially update a menu item. Only provided fields change. Moving between slots re-validates the parent ancestry language. Editing a record owned by another admin requires the moderate_edit role.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Menu item record ID.',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'New language code of an active site language.',
                        ],
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New menu label.',
                        ],
                        'parent_id' => [
                            'type' => 'integer',
                            'description' => 'New parent slot root or parent item ID.',
                        ],
                        'url' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New link target URL or route.',
                        ],
                        'file' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New custom controller file reference.',
                        ],
                        'target' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'New window target.',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'y',
                                'n',
                            ],
                            'description' => 'New active status.',
                        ],
                        'html' => [
                            'type' => 'string',
                            'description' => 'New raw HTML content.',
                        ],
                        'icon' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'New icon class.',
                        ],
                        'acl_id' => [
                            'type' => 'integer',
                            'description' => 'New access control group ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Update Menu Item',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
            'delete_menu_item' => [
                'module' => 'mod_menu',
                'role' => 'delete',
                'domain' => 'menu',
                'handler' => 'deleteMenuItem',
                'description' => 'Delete a menu item and all of its child items recursively. Structural container rows (language roots and menu slots) are protected and cannot be deleted. Gallery/attachment relations are cleaned up.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'Menu item record ID.',
                        ],
                    ],
                    'required' => [
                        'id',
                    ],
                ],
                'title' => 'Delete Menu Item',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
            'reparent_menu_items_by_name' => [
                'module' => 'mod_menu',
                'role' => 'edit',
                'domain' => 'menu',
                'handler' => 'reparentMenuItemsByName',
                'description' => 'Semantic cross-language reparenting tool. Relocates matching menu items under a target parent item with strict slot isolation (Header items stay in Header, Footer items stay in Footer) inside an ACID transaction.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'source_names' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'List of menu names/translations to move (e.g. ["Haberler", "Videolar", "News", "Video"]).',
                        ],
                        'target_parent_names' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'List of candidate labels for the target parent across languages (e.g. ["Hakkımızda", "About Us", "Derbarê me"]).',
                        ],
                        'languages' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Optional filter for specific language codes. Defaults to all active languages.',
                        ],
                        'slot' => [
                            'type' => 'string',
                            'enum' => ['all', 'header', 'footer', 'sidebar', 'action'],
                            'default' => 'all',
                            'description' => 'Slot scope to affect. Slot isolation is strictly preserved.',
                        ],
                        'dry_run' => [
                            'type' => 'boolean',
                            'default' => false,
                            'description' => 'If true, simulates the reparenting without committing changes, returning items that would be affected.',
                        ],
                    ],
                    'required' => [
                        'source_names',
                        'target_parent_names',
                    ],
                ],
                'title' => 'Reparent Menu Items By Name',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
            'batch_update_menu_items' => [
                'module' => 'mod_menu',
                'role' => 'edit',
                'domain' => 'menu',
                'handler' => 'batchUpdateMenuItems',
                'description' => 'Atomic batch update tool for multiple menu records. Validates permissions, container constraints, and language ancestry in an ACID transaction.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'items' => [
                            'type' => 'array',
                            'maxItems' => 100,
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'parent_id' => ['type' => 'integer'],
                                    'name' => ['type' => 'string'],
                                    'order' => ['type' => 'integer'],
                                    'status' => ['type' => 'string', 'enum' => ['y', 'n']],
                                    'url' => ['type' => 'string'],
                                ],
                                'required' => ['id'],
                            ],
                            'description' => 'Array of item update objects (max 100).',
                        ],
                    ],
                    'required' => ['items'],
                ],
                'title' => 'Batch Update Menu Items',
                'annotations' => [
                    'readOnlyHint' => false,
                    'destructiveHint' => true,
                    'idempotentHint' => true,
                ],
            ],
        ];
    }

    private const SLOT_ALIASES = [
        'header' => ['##HEADER_MENU', 'HEADER_MENU', '##HEADER MENU', 'HEADER MENU', '##HEADER', 'HEADER', 'UST MENU', 'TOP MENU', 'MAIN MENU', 'MAINMENU', 'ANA MENU', 'ANAMENU', 'SERPESEK', 'SER PESEK', 'HAUPTMENU', 'HAUPT MENU', 'MENU PRINCIPAL', 'MENUPRINCIPAL'],
        'footer' => ['##FOOTER_MENU', 'FOOTER_MENU', '##FOOTER MENU', 'FOOTER MENU', '##FOOTER', 'FOOTER', 'ALT MENU', 'BOTTOM MENU', 'FOOTERMENU', 'ALTMENU', 'BINPESEK', 'BIN PESEK', 'FUSSMENU', 'FUSS MENU', 'MENU PIED', 'MENU DU PIED'],
        'sidebar' => ['##SIDEBAR_MENU', 'SIDEBAR_MENU', '##SIDEBAR', 'SIDEBAR', 'SOL MENU', 'SIDE MENU', 'SIDEBARMENU', 'PALPESEK'],
        'action' => ['##ACTION MENU', 'ACTION MENU', '##ACTION', 'ACTION', 'CTA MENU', '##CTA MENU', 'ACTIONMENU', 'ACTION_MENU'],
    ];

    /**
     * Lists menu items with optional filters, ordered as displayed
     */
    public function listMenuItems(array $args): array
    {
        $limit = $this->normalizeLimit($args, 100, 250);
        $offset = $this->normalizeOffset($args);
        $compact = !isset($args['compact']) || (bool) $args['compact'];

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

        if ($compact && is_array($items)) {
            $compactItems = [];
            foreach ($items as $row) {
                $compactItems[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'lang' => (string) ($row['lang'] ?? ''),
                    'name' => (string) ($row['name'] ?? ''),
                    'url' => (string) ($row['url'] ?? ''),
                    'parent_id' => (int) ($row['parent_id'] ?? 0),
                    'order' => (int) ($row['order'] ?? 0),
                    'status' => (string) ($row['status'] ?? 'y'),
                ];
            }
            $items = $compactItems;
        }

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
        $nameSpaced = str_replace('_', ' ', $name);
        foreach (self::SLOT_ALIASES as $aliases) {
            if (in_array($name, $aliases, true) || in_array($nameSpaced, $aliases, true)) {
                return true;
            }
        }
        return false;
    }

    private function identifySlotType(array $row): string
    {
        $name = strtoupper(trim((string) ($row['name'] ?? '')));
        $nameSpaced = str_replace('_', ' ', $name);
        foreach (self::SLOT_ALIASES as $type => $aliases) {
            if (in_array($name, $aliases, true) || in_array($nameSpaced, $aliases, true)) {
                return $type;
            }
        }
        return 'other';
    }

    private function resolveSlotRootId(array $rowsById, int $itemId): int
    {
        if (!isset($rowsById[$itemId])) {
            return 0;
        }

        $current = $rowsById[$itemId];
        $visited = [];

        while (isset($current['id'])) {
            $currentId = (int) $current['id'];
            if (isset($visited[$currentId])) {
                break;
            }
            $visited[$currentId] = true;

            if ($this->isSlotRoot($current)) {
                return $currentId;
            }

            $parentId = isset($current['parent_id']) ? (int) $current['parent_id'] : 0;
            if ($parentId === 0 || !isset($rowsById[$parentId])) {
                break;
            }
            $current = $rowsById[$parentId];
        }

        return 0;
    }

    /**
     * Semantic cross-language reparenting tool with ACID transaction and slot isolation
     */
    public function reparentMenuItemsByName(array $args): array
    {
        $this->assertModerateEdit('mod_menu', 0);

        $sourceNames = is_array($args['source_names'] ?? null)
            ? $args['source_names']
            : (isset($args['source_name']) ? [(string) $args['source_name']] : (isset($args['child_name']) ? [(string) $args['child_name']] : []));
        if (empty($sourceNames)) {
            throw new McpToolException("Field 'source_names' is required and must not be empty.");
        }
        $targetParentNames = is_array($args['target_parent_names'] ?? null)
            ? $args['target_parent_names']
            : (isset($args['target_parent_name']) ? [(string) $args['target_parent_name']] : []);
        if (empty($targetParentNames)) {
            throw new McpToolException("Field 'target_parent_names' is required and must not be empty.");
        }

        $sourceNamesNorm = array_map(fn($s) => mb_strtolower(trim((string) $s), 'UTF-8'), $sourceNames);
        $targetNamesNorm = array_map(fn($t) => mb_strtolower(trim((string) $t), 'UTF-8'), $targetParentNames);

        $targetSlot = strtolower(trim((string) ($args['slot'] ?? 'all')));
        $targetLangs = is_array($args['languages'] ?? null) ? array_map('trim', $args['languages']) : [];

        $allRows = $this->getAllMenuRows();

        $slotGroups = [];
        $slotTypes = [];

        foreach ($allRows as $id => $row) {
            $lang = (string) ($row['lang'] ?? '');
            if (!empty($targetLangs) && !in_array($lang, $targetLangs, true)) {
                continue;
            }

            $slotRootId = $this->resolveSlotRootId($allRows, (int) $id);
            if ($slotRootId === 0) {
                continue;
            }

            if (!isset($slotTypes[$slotRootId])) {
                $slotTypes[$slotRootId] = $this->identifySlotType($allRows[$slotRootId] ?? []);
            }
            $slotType = $slotTypes[$slotRootId];

            if ($targetSlot !== 'all' && $slotType !== $targetSlot) {
                continue;
            }

            $groupKey = "{$lang}_{$slotRootId}";
            $slotGroups[$groupKey][] = $row;
        }

        $isDryRun = !empty($args['dry_run']);

        $db = Load::lib('Database');
        $db->start_transaction();

        $updatedIds = [];
        $affectedLangs = [];
        $unmatchedGroups = [];

        try {
            foreach ($slotGroups as $groupKey => $rowsInSlot) {
                $targetParentRow = null;
                foreach ($rowsInSlot as $r) {
                    if ($this->isContainerRow($r)) {
                        continue;
                    }
                    $rNameNorm = mb_strtolower(trim((string) ($r['name'] ?? '')), 'UTF-8');
                    if (in_array($rNameNorm, $targetNamesNorm, true)) {
                        $targetParentRow = $r;
                        break;
                    }
                }

                if (!$targetParentRow) {
                    $unmatchedGroups[] = $groupKey;
                    continue;
                }

                $targetParentId = (int) $targetParentRow['id'];
                $targetParentLang = (string) $targetParentRow['lang'];

                foreach ($rowsInSlot as $r) {
                    if ($this->isContainerRow($r)) {
                        continue;
                    }
                    $rId = (int) $r['id'];
                    if ($rId === $targetParentId) {
                        continue;
                    }

                    $rNameNorm = mb_strtolower(trim((string) ($r['name'] ?? '')), 'UTF-8');
                    if (in_array($rNameNorm, $sourceNamesNorm, true)) {
                        $this->assertParentLanguage($targetParentLang, $targetParentId, $rId);

                        if (!$isDryRun) {
                            $m = $this->loadMenuItemById($rId);
                            $m->parent_id = $targetParentId;
                            $m->order = $this->nextMenuSiblingOrder($targetParentId, $targetParentLang);
                            if (!$m->update('id')) {
                                throw new McpToolException("Failed to update menu item ID '{$rId}'.");
                            }
                        }
                        $updatedIds[] = $rId;
                        $affectedLangs[$targetParentLang] = true;
                    }
                }
            }

            if ($isDryRun) {
                $db->rollback();
            } else {
                $db->commit();
            }
        } catch (\Throwable $e) {
            $db->rollback();
            throw new McpToolException('Reparenting failed and was rolled back: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'dry_run' => $isDryRun,
            'reparented_count' => count($updatedIds),
            'updated_ids' => $updatedIds,
            'affected_languages' => array_keys($affectedLangs),
            'unmatched_groups' => $unmatchedGroups,
            'summary' => ($isDryRun ? '[PREVIEW - NO CHANGES COMMITTED] ' : '') . 'Reparented ' . count($updatedIds) . ' items across ' . count($affectedLangs) . " languages under '" . implode('/', $targetParentNames) . "' with slot isolation preserved.",
        ];
    }

    /**
     * Atomic batch update for multiple menu items
     */
    public function batchUpdateMenuItems(array $args): array
    {
        $items = is_array($args['items'] ?? null) ? $args['items'] : (is_array($args['updates'] ?? null) ? $args['updates'] : []);
        if (empty($items)) {
            throw new McpToolException("Field 'items' is required and must not be empty.");
        }

        $db = Load::lib('Database');
        $db->start_transaction();

        $updatedIds = [];
        try {
            foreach ($items as $itemData) {
                if (!is_array($itemData) || empty($itemData['id'])) {
                    throw new McpToolException("Each item in 'items' must have an 'id'.");
                }
                $this->updateMenuItem($itemData);
                $updatedIds[] = (int) $itemData['id'];
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw new McpToolException('Batch update failed and was rolled back: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'updated_count' => count($updatedIds),
            'updated_ids' => $updatedIds,
        ];
    }
}
