<?php
/**
 * Amed Framework
 * MCP Page Tools - CRUD implementations for mod_page
 * lib/McpPageTools.class.php
 */

class McpPageTools extends McpCrudToolsBase
{
    private const PAGE_IMAGE_SIZES = [
        '/' => ['width' => 1600, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 600, 'height' => null, 'option' => 'width'],
    ];

    // ------------------------------------------------------------------
    // mod_page tools
    // ------------------------------------------------------------------

    /**
     * Lists pages with optional filters, ordered like the admin page tree
     */
    public function listPages(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_page');
        $model->select('id, lang, name, parent_id, `order`, root, status, deck, seo, category_id, acl_id, menu, image');
        $this->applyPageFilters($model, $args);
        $model->order('`order`', 'ASC')->order('id', 'ASC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_page');
        $countModel->select('id');
        $this->applyPageFilters($countModel, $args);
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
     * Returns a single page record with all fields
     */
    public function getPage(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadPageById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Page record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a page record (mirrors the admin add validator)
     */
    public function createPage(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (1-255 characters).");
        }

        $lang = $this->requireActiveLang($args);

        $content = (string) ($args['content'] ?? '');
        if (trim(strip_tags($content)) === '') {
            throw new McpToolException("Field 'content' is required.");
        }

        $summary = trim(strip_tags((string) ($args['summary'] ?? '')));
        if ($summary === '') {
            throw new McpToolException("Field 'summary' is required.");
        }
        $this->assertSummaryLength($summary);

        $seo = trim((string) ($args['seo'] ?? ''));
        if ($seo === '') {
            $seo = self::makeSeo($name, $lang);
        }
        if ($seo === '' || mb_strlen($seo) > 255) {
            throw new McpToolException("Field 'seo' must be 1-255 characters.");
        }
        if ($this->recordSeoExists('mod_page', $seo, $lang)) {
            throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
        }

        $parentId = (int) ($args['parent_id'] ?? 0);
        if ($parentId > 0) {
            $this->assertRecordMatchesLang('mod_page', $parentId, $lang, 'Parent page');
            $parentRow = $this->getPage(['id' => $parentId]);
            if ((string) $parentRow['item']['root'] === 'y') {
                throw new McpToolException("A page cannot be nested under a language root container row.");
            }
        }

        $model = $this->freshModel('mod_page');
        $model->lang = $lang;
        $model->name = $name;
        $model->parent_id = $parentId;
        $model->order = $this->nextSiblingOrder('mod_page', $parentId);
        $model->root = $this->optionalEnum($args, 'root', 'n', ['y', 'n']);
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        $model->deck = $this->optionalEnum($args, 'deck', 'n', ['y', 'n']);
        $model->summary = $summary;
        $model->content = $content;
        $model->gallery_id = (int) ($args['gallery_id'] ?? 0);
        $model->file_id = (int) ($args['file_id'] ?? 0);
        $model->seo = $seo;
        $model->title = $this->optionalString($args, 'title', $name, 255);
        $model->keywords = $this->optionalString($args, 'keywords', '', 255);
        $model->description = $this->optionalString($args, 'description', $summary, 255);
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $model->category_id = (int) ($args['category_id'] ?? 0);
        $model->menu = 0;
        $model->user_id = $this->actingUserId;

        if ($model->category_id > 0) {
            $this->assertRecordMatchesLang('mod_content_category', $model->category_id, $lang, 'Content category');
        }
        if ($model->gallery_id > 0) {
            $this->assertRecordMatchesLang('mod_page', $model->gallery_id, $lang, 'Gallery page');
        }
        if ($model->file_id > 0) {
            $this->assertRecordMatchesLang('mod_page', $model->file_id, $lang, 'File page');
        }

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_page', $args, self::PAGE_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_page', $imageUrl, self::PAGE_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the page record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getPage(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a page record and syncs its menu entry URL
     */
    public function updatePage(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadPageById($id);

        $this->assertModerateEdit('mod_page', (int) $model->user_id);

        $previousMenuUrl = (string) $model->lang . '/page/' . (string) $model->seo;

        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;

        if (isset($args['parent_id'])) {
            $parentId = (int) $args['parent_id'];
            if ($parentId === $id) {
                throw new McpToolException("A page cannot be its own parent.");
            }
            if ($parentId > 0) {
                $parentRow = $this->getPage(['id' => $parentId]);
                if ((string) $parentRow['item']['lang'] !== $lang) {
                    throw new McpToolException("Parent page '{$parentId}' belongs to language '" . $parentRow['item']['lang'] . "', content language is '{$lang}'. They must match.");
                }
                if ((string) $parentRow['item']['root'] === 'y') {
                    throw new McpToolException("A page cannot be nested under a language root container row.");
                }
            }
            $model->parent_id = $parentId;
        }

        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if ($name === '' || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 1-255 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['content'])) {
            $content = (string) $args['content'];
            if (trim(strip_tags($content)) === '') {
                throw new McpToolException("Field 'content' cannot be empty.");
            }
            $model->content = $content;
        }

        if (isset($args['summary'])) {
            $summary = trim(strip_tags((string) $args['summary']));
            if ($summary === '') {
                throw new McpToolException("Field 'summary' cannot be empty.");
            }
            $this->assertSummaryLength($summary);
            $model->summary = $summary;
        }

        if (isset($args['seo'])) {
            $seo = trim((string) $args['seo']);
            if ($seo === '' || mb_strlen($seo) > 255) {
                throw new McpToolException("Field 'seo' must be 1-255 characters.");
            }
            if ($this->recordSeoExists('mod_page', $seo, $lang, $id)) {
                throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
            }
            $model->seo = $seo;
        }

        foreach (['title', 'keywords', 'description'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalString($args, $field, (string) $model->{$field}, 255);
            }
        }

        foreach (['status', 'root', 'deck'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalEnum($args, $field, (string) $model->{$field}, ['y', 'n']);
            }
        }

        if (isset($args['gallery_id'])) {
            $model->gallery_id = (int) $args['gallery_id'];
        }
        if (isset($args['file_id'])) {
            $model->file_id = (int) $args['file_id'];
        }
        if (isset($args['acl_id'])) {
            $model->acl_id = (int) $args['acl_id'];
        }
        if (isset($args['category_id'])) {
            $model->category_id = (int) $args['category_id'];
        }

        $model->lang = $lang;

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_page', $args, self::PAGE_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the page record.');
        }

        $this->syncPageMenuOnUpdate($previousMenuUrl, $this->loadPageById($id));

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getPage(['id' => $id])['item']
        ];
    }

    /**
     * Deletes a page record with admin parity cleanup
     */
    public function deletePage(array $args): array
    {
        $id = $this->requireId($args);
        $this->deletePageRow($id);

        return ['deleted' => true, 'ids' => [$id]];
    }

    // ------------------------------------------------------------------
    // Page helpers
    // ------------------------------------------------------------------

    /**
     * Deletes a page and all of its child pages recursively (admin parity),
     * refusing to delete structural language root container rows
     */
    private function deletePageRow(int $id): void
    {
        $model = $this->loadPageById($id);

        if ((string) $model->root === 'y') {
            throw new McpToolException("Page '{$id}' is a structural language root container row and cannot be deleted.");
        }

        $this->assertModerateDelete('mod_page', (int) $model->user_id);

        $childrenModel = $this->freshModel('mod_page');
        $childrenModel->where_parent_id($id)->getResult(true, true);
        foreach ($childrenModel->toArray() as $child) {
            $this->deletePageRow((int) $child['id']);
        }

        $this->removeBridgeRelations('mod_page', $id);

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_page', (string) $model->image, self::PAGE_IMAGE_SIZES);
        }

        $menuUrl = (string) $model->lang . '/page/' . (string) $model->seo;
        $menuModel = $this->freshModel('mod_menu');
        if ($menuModel->exists_url($menuUrl)) {
            $menuModel->getResultOne(true, true)->delete();
        }

        $model->delete();
    }

    /**
     * When the page name, language, seo, or acl changes, an existing menu
     * entry pointing at the old page URL is updated automatically
     */
    private function syncPageMenuOnUpdate(string $previousMenuUrl, $pageModel): void
    {
        $menuModel = $this->freshModel('mod_menu');
        if ($menuModel->exists_url($previousMenuUrl)) {
            $menuModel->getResultOne(true, true)->sync();
            $menuModel->name = $pageModel->name;
            $menuModel->lang = $pageModel->lang;
            $menuModel->status = $pageModel->status;
            $menuModel->acl_id = $pageModel->acl_id;
            $menuModel->url = $pageModel->lang . '/page/' . $pageModel->seo;
            $menuModel->update();
        }
    }

    private function loadPageById(int $id): mod_page_model
    {
        $model = $this->freshModel('mod_page');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Page record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyPageFilters($model, array $args): void
    {
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
        }
        if (isset($args['parent_id']) && (int) $args['parent_id'] > 0) {
            $model->where_parent_id((int) $args['parent_id']);
        }
        if (isset($args['root']) && in_array($args['root'], ['y', 'n'], true)) {
            $model->where_root($args['root']);
        }
        if (isset($args['status']) && in_array($args['status'], ['y', 'n'], true)) {
            $model->where_status($args['status']);
        }
        if (isset($args['deck']) && in_array($args['deck'], ['y', 'n'], true)) {
            $model->where_deck($args['deck']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $model->where('name LIKE \'%' . Obuilder::cc(trim((string) $args['search'])) . '%\'');
        }
    }
}
