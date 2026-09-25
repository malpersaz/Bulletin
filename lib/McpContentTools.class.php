<?php
/**
 * Amed Framework
 * MCP Content Tools - CRUD implementations for mod_content and mod_content_category
 * lib/McpContentTools.class.php
 */

class McpContentTools extends McpCrudToolsBase
{
    private const CONTENT_IMAGE_SIZES = [
        '/' => ['width' => 1600, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 600, 'height' => null, 'option' => 'width'],
    ];

    private const CATEGORY_IMAGE_SIZES = [
        '/' => ['width' => 200, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 100, 'height' => null, 'option' => 'width'],
    ];

    // ------------------------------------------------------------------
    // mod_content tools
    // ------------------------------------------------------------------

    /**
     * Lists contents with optional filters
     */
    public function listContents(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_content');
        $model->select('id, lang, name, category_id, status, deck, slider, seo, created_at, updated_at');
        $this->applyContentFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_content');
        $countModel->select('id');
        $this->applyContentFilters($countModel, $args);
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
     * Returns a single content record with all fields
     */
    public function getContent(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadContentById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Content record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a content record (mirrors the admin add validator)
     */
    public function createContent(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (1-255 characters).");
        }

        $lang = $this->requireActiveLang($args);
        $categoryId = (int) ($args['category_id'] ?? 0);
        if ($categoryId <= 0) {
            throw new McpToolException("Field 'category_id' is required.");
        }
        $this->assertRecordMatchesLang('mod_content_category', $categoryId, $lang, 'Category');

        $content = (string) ($args['content'] ?? '');
        if (trim(strip_tags($content)) === '') {
            throw new McpToolException("Field 'content' is required.");
        }

        $summary = trim((string) ($args['summary'] ?? ''));
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
        if ($this->recordSeoExists('mod_content', $seo, $lang)) {
            throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
        }

        $model = $this->freshModel('mod_content');
        $model->name = $name;
        $model->lang = $lang;
        $model->category_id = $categoryId;
        $model->content = $content;
        $model->summary = $summary;
        $model->seo = $seo;
        $model->title = $this->optionalString($args, 'title', $name, 255);
        $model->keywords = $this->optionalString($args, 'keywords', '', 255);
        $model->description = $this->optionalString($args, 'description', $summary, 255);
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        $model->deck = $this->optionalEnum($args, 'deck', 'n', ['y', 'n']);
        $model->slider = $this->optionalEnum($args, 'slider', 'n', ['y', 'n']);
        $model->created_at = $this->optionalDateTime($args, 'created_at', date('Y-m-d H:i:s'));
        $model->updated_at = date('Y-m-d H:i:s');
        $model->video = $this->serializeVideoRefs($args['video'] ?? '');
        $model->gallery_id = (int) ($args['gallery_id'] ?? 0);
        $model->file_id = (int) ($args['file_id'] ?? 0);
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $model->user_id = $this->actingUserId;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_content', $args, self::CONTENT_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_content', $imageUrl, self::CONTENT_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the content record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getContent(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a content record
     */
    public function updateContent(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadContentById($id);

        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;
        $categoryId = isset($args['category_id']) ? (int) $args['category_id'] : (int) $model->category_id;

        if ($categoryId !== (int) $model->category_id || $lang !== (string) $model->lang) {
            $this->assertRecordMatchesLang('mod_content_category', $categoryId, $lang, 'Category');
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
            $summary = trim((string) $args['summary']);
            $this->assertSummaryLength($summary);
            $model->summary = $summary;
        }

        if (isset($args['seo'])) {
            $seo = trim((string) $args['seo']);
            if ($seo === '' || mb_strlen($seo) > 255) {
                throw new McpToolException("Field 'seo' must be 1-255 characters.");
            }
            if ($this->recordSeoExists('mod_content', $seo, $lang, $id)) {
                throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
            }
            $model->seo = $seo;
        }

        foreach (['title', 'keywords', 'description'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalString($args, $field, (string) $model->{$field}, 255);
            }
        }

        foreach (['status', 'deck', 'slider'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalEnum($args, $field, (string) $model->{$field}, ['y', 'n']);
            }
        }

        if (isset($args['created_at'])) {
            $model->created_at = $this->optionalDateTime($args, 'created_at', (string) $model->created_at);
        }

        if (isset($args['video'])) {
            $model->video = $this->serializeVideoRefs($args['video']);
        }

        $model->lang = $lang;
        $model->category_id = $categoryId;
        $model->updated_at = date('Y-m-d H:i:s');

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_content', $args, self::CONTENT_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the content record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getContent(['id' => $id])['item']
        ];
    }

    /**
     * Deletes a content record with full admin parity cleanup
     */
    public function deleteContent(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadContentById($id);

        $this->assertModerateDelete('mod_content', (int) $model->user_id);

        $this->removeBridgeRelations('mod_content', $id);

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_content', (string) $model->image, self::CONTENT_IMAGE_SIZES);
        }

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // mod_content_category tools
    // ------------------------------------------------------------------

    /**
     * Lists content categories
     */
    public function listContentCategories(array $args): array
    {
        return $this->listCategoriesGeneric('mod_content_category', $args, 50);
    }

    /**
     * Returns a single content category with all fields
     */
    public function getContentCategory(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadCategoryById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Content category record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a content category
     */
    public function createContentCategory(array $args): array
    {
        $fields = $this->buildCategoryFields($args, 'mod_content_category');

        $model = $this->freshModel('mod_content_category');
        foreach ($fields as $field => $value) {
            $model->{$field} = $value;
        }
        $model->menu = 0;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_content_category', $args, self::CATEGORY_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_content_category', $imageUrl, self::CATEGORY_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the content category record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getContentCategory(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a content category and syncs related menu entries
     */
    public function updateContentCategory(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadCategoryById($id);

        $previousMenuUrl = $model->lang . '/kat/' . $model->seo;
        $this->applyCategoryUpdate($model, $args);

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_content_category', $args, self::CATEGORY_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the content category record.');
        }

        $this->syncCategoryMenuOnUpdate($previousMenuUrl, $model);

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getContentCategory(['id' => $id])['item']
        ];
    }

    /**
     * Recursively deletes a content category tree with admin parity cleanup
     */
    public function deleteContentCategory(array $args): array
    {
        $id = $this->requireId($args);
        $deletedIds = [];
        $this->deleteCategoryTree($id, $deletedIds);

        return ['deleted' => true, 'ids' => $deletedIds];
    }

    // ------------------------------------------------------------------
    // Shared category logic ({lang}/kat/{seo} menu URL pattern)
    // ------------------------------------------------------------------

    private function deleteCategoryTree(int $id, array &$deletedIds): void
    {
        $model = $this->loadCategoryById($id);

        $this->assertModerateDelete('mod_content_category', (int) $model->user_id);

        $childrenModel = $this->freshModel('mod_content_category');
        $childrenModel->where_parent_id($id)->getResult(true, true);
        foreach ($childrenModel->toArray() as $child) {
            $this->deleteCategoryTree((int) $child['id'], $deletedIds);
        }

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_content_category', (string) $model->image, self::CATEGORY_IMAGE_SIZES);
        }

        $this->removeBridgeRelations('mod_content_category', $id);

        $menuUrl = $model->lang . '/kat/' . $model->seo;
        $menuModel = $this->freshModel('mod_menu');
        if ($menuModel->exists_url($menuUrl)) {
            $menuModel->getResultOne(true, true)->delete();
        }

        $model->delete();
        $deletedIds[] = $id;
    }

    private function syncCategoryMenuOnUpdate(string $previousMenuUrl, $categoryModel): void
    {
        $menuModel = $this->freshModel('mod_menu');
        if ($menuModel->exists_url($previousMenuUrl)) {
            $menuModel->getResultOne(true, true)->sync();
            $menuModel->name = $categoryModel->name;
            $menuModel->lang = $categoryModel->lang;
            $menuModel->url = $categoryModel->lang . '/kat/' . $categoryModel->seo;
            $menuModel->acl_id = $categoryModel->acl_id;
            $menuModel->update();
        }
    }

    private function loadCategoryById(int $id): mod_content_category_model
    {
        $model = $this->freshModel('mod_content_category');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Content category record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function loadContentById(int $id): mod_content_model
    {
        $model = $this->freshModel('mod_content');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Content record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyContentFilters($model, array $args): void
    {
        if (isset($args['category_id']) && (int) $args['category_id'] > 0) {
            $model->where_category_id((int) $args['category_id']);
        }
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
        }
        if (isset($args['status']) && in_array($args['status'], ['y', 'n'], true)) {
            $model->where_status($args['status']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $model->where('name LIKE \'%' . Obuilder::cc(trim((string) $args['search'])) . '%\'');
        }
    }

    /**
     * Validates create arguments and returns the category field set
     *
     * @return array<string, mixed>
     */
    protected function buildCategoryFields(array $args, string $modelName): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }

        $lang = $this->requireActiveLang($args);

        $parentId = (int) ($args['parent_id'] ?? 0);
        if ($parentId > 0) {
            $this->assertRecordMatchesLang($modelName, $parentId, $lang, 'Parent category');
        }

        return [
            'name' => $name,
            'lang' => $lang,
            'parent_id' => $parentId,
            'root' => $this->optionalEnum($args, 'root', 'n', ['y', 'n']),
            'seo' => $this->optionalString($args, 'seo', self::makeSeo($name, $lang), 255),
            'title' => $this->optionalString($args, 'title', $name, 255),
            'keywords' => $this->optionalString($args, 'keywords', '', 255),
            'description' => $this->optionalString($args, 'description', '', 255),
            'acl_id' => (int) ($args['acl_id'] ?? 0),
            'user_id' => $this->actingUserId,
            'order' => $this->nextSiblingOrder($modelName, $parentId),
        ];
    }

    /**
     * Applies partial category updates with parent/language validation
     */
    protected function applyCategoryUpdate($model, array $args): void
    {
        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 2-255 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['lang'])) {
            $model->lang = $this->requireActiveLang($args);
        }

        if (isset($args['parent_id'])) {
            $parentId = (int) $args['parent_id'];
            if ($parentId > 0) {
                if ($parentId === (int) $model->id) {
                    throw new McpToolException("A category cannot be its own parent.");
                }
                $parent = $this->loadRecordById('mod_content_category', $parentId, 'Parent category');
                if ((string) $parent->lang !== (string) $model->lang) {
                    throw new McpToolException("Parent category '{$parentId}' belongs to language '" . $parent->lang . "', cannot nest a '" . $model->lang . "' category under it.");
                }
            }
            $model->parent_id = $parentId;
        }

        if (isset($args['root'])) {
            $model->root = $this->optionalEnum($args, 'root', (string) $model->root, ['y', 'n']);
        }

        if (isset($args['seo'])) {
            $model->seo = $this->optionalString($args, 'seo', (string) $model->seo, 255);
        }

        foreach (['title', 'keywords', 'description'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalString($args, $field, (string) $model->{$field}, 255);
            }
        }

        if (isset($args['acl_id'])) {
            $model->acl_id = (int) $args['acl_id'];
        }

        if (isset($args['order'])) {
            $order = (int) $args['order'];
            if ($order < 0) {
                throw new McpToolException("Field 'order' must be a non-negative integer.");
            }
            $model->order = $order;
        }
    }
}
