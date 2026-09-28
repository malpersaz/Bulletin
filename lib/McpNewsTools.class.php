<?php
/**
 * Amed Framework
 * MCP News Tools - CRUD implementations for mod_news and mod_news_category
 * lib/McpNewsTools.class.php
 */

class McpNewsTools extends McpCrudToolsBase
{
    private const NEWS_IMAGE_SIZES = [
        '/' => ['width' => 1600, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 600, 'height' => null, 'option' => 'width'],
    ];

    private const CATEGORY_IMAGE_SIZES = [
        '/' => ['width' => 200, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 100, 'height' => null, 'option' => 'width'],
    ];

    // ------------------------------------------------------------------
    // mod_news tools
    // ------------------------------------------------------------------

    /**
     * Lists news articles with optional filters
     */
    public function listNews(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_news');
        $model->select('id, lang, name, category_id, status, deck, slider, seo, created_at, updated_at');
        $this->applyNewsFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_news');
        $countModel->select('id');
        $this->applyNewsFilters($countModel, $args);
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
     * Returns a single news record with all fields
     */
    public function getNews(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadNewsById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("News record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a news record (mirrors the admin add validator)
     */
    public function createNews(array $args): array
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
        $this->assertRecordMatchesLang('mod_news_category', $categoryId, $lang, 'Category');

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
        if ($this->recordSeoExists('mod_news', $seo, $lang)) {
            throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
        }

        $model = $this->freshModel('mod_news');
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

        $authorUserId = $this->actingUserId;
        if (!empty($args['user_id'])) {
            $authorUserId = (int) $args['user_id'];
        } elseif (!empty($args['username'])) {
            $targetUsername = trim((string) $args['username']);
            $db = Load::lib('Database');
            $db->fetch_array("SELECT id FROM mod_user WHERE username = '" . Obuilder::cc($targetUsername) . "' AND status = 'y' LIMIT 1");
            if ($db->getSize() > 0) {
                $userRow = $db->get_row();
                $authorUserId = (int) $userRow['id'];
            }
        }
        $model->user_id = $authorUserId;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_news', $args, self::NEWS_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_news', $imageUrl, self::NEWS_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the news record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getNews(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a news record
     */
    public function updateNews(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadNewsById($id);

        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;
        $categoryId = isset($args['category_id']) ? (int) $args['category_id'] : (int) $model->category_id;

        if ($categoryId !== (int) $model->category_id || $lang !== (string) $model->lang) {
            $this->assertRecordMatchesLang('mod_news_category', $categoryId, $lang, 'Category');
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
            if ($this->recordSeoExists('mod_news', $seo, $lang, $id)) {
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

        if (isset($args['gallery_id'])) {
            $model->gallery_id = (int) $args['gallery_id'];
        }

        if (isset($args['file_id'])) {
            $model->file_id = (int) $args['file_id'];
        }

        if (isset($args['acl_id'])) {
            $model->acl_id = (int) $args['acl_id'];
        }

        if (isset($args['user_id'])) {
            $model->user_id = (int) $args['user_id'];
        } elseif (isset($args['username'])) {
            $targetUsername = trim((string) $args['username']);
            $db = Load::lib('Database');
            $db->fetch_array("SELECT id FROM mod_user WHERE username = '" . Obuilder::cc($targetUsername) . "' AND status = 'y' LIMIT 1");
            if ($db->getSize() > 0) {
                $userRow = $db->get_row();
                $model->user_id = (int) $userRow['id'];
            }
        }

        $model->lang = $lang;
        $model->category_id = $categoryId;
        $model->updated_at = date('Y-m-d H:i:s');

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_news', $args, self::NEWS_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the news record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getNews(['id' => $id])['item']
        ];
    }

    /**
     * Deletes a news record with admin parity cleanup
     */
    public function deleteNews(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadNewsById($id);

        $this->assertModerateDelete('mod_news', (int) $model->user_id);

        $this->removeBridgeRelations('mod_news', $id);

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_news', (string) $model->image, self::NEWS_IMAGE_SIZES);
        }

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // mod_news_category tools
    // ------------------------------------------------------------------

    /**
     * Lists news categories with optional filters
     */
    public function listNewsCategories(array $args): array
    {
        return $this->listCategoriesGeneric('mod_news_category', $args, 20);
    }

    /**
     * Returns a single news category record with all fields
     */
    public function getNewsCategory(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadCategoryById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("News category record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a news category record
     */
    public function createNewsCategory(array $args): array
    {
        $fields = $this->buildCategoryFields($args, 'mod_news_category');

        $seo = $fields['seo'];
        if ($this->recordSeoExists('mod_news_category', $seo, $fields['lang'])) {
            throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
        }

        $model = $this->freshModel('mod_news_category');
        foreach ($fields as $key => $val) {
            $model->{$key} = $val;
        }

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_news_category', $args, self::CATEGORY_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_news_category', $imageUrl, self::CATEGORY_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the news category record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getNewsCategory(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a news category and syncs related menu entries
     */
    public function updateNewsCategory(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadCategoryById($id);

        $previousMenuUrl = $model->lang . '/newses/' . $model->seo;
        $this->applyCategoryUpdate($model, $args);

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_news_category', $args, self::CATEGORY_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the news category record.');
        }

        $this->syncCategoryMenuOnUpdate($previousMenuUrl, $model);

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getNewsCategory(['id' => $id])['item']
        ];
    }

    /**
     * Recursively deletes a news category tree with admin parity cleanup
     */
    public function deleteNewsCategory(array $args): array
    {
        $id = $this->requireId($args);
        $deletedIds = [];
        $this->deleteCategoryTree($id, $deletedIds);

        return ['deleted' => true, 'ids' => $deletedIds];
    }

    // ------------------------------------------------------------------
    // Shared category logic ({lang}/newses/{seo} menu URL pattern)
    // ------------------------------------------------------------------

    private function deleteCategoryTree(int $id, array &$deletedIds): void
    {
        $model = $this->loadCategoryById($id);

        $this->assertModerateDelete('mod_news_category', (int) $model->user_id);

        $childrenModel = $this->freshModel('mod_news_category');
        $childrenModel->where_parent_id($id)->getResult(true, true);
        foreach ($childrenModel->toArray() as $child) {
            $this->deleteCategoryTree((int) $child['id'], $deletedIds);
        }

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_news_category', (string) $model->image, self::CATEGORY_IMAGE_SIZES);
        }

        $this->removeBridgeRelations('mod_news_category', $id);

        $menuUrl = $model->lang . '/newses/' . $model->seo;
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
            $menuModel->url = $categoryModel->lang . '/newses/' . $categoryModel->seo;
            $menuModel->acl_id = $categoryModel->acl_id;
            $menuModel->update();
        }
    }

    private function loadCategoryById(int $id): mod_news_category_model
    {
        $model = $this->freshModel('mod_news_category');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("News category record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function loadNewsById(int $id): mod_news_model
    {
        $model = $this->freshModel('mod_news');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("News record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyNewsFilters($model, array $args): void
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
                $parent = $this->loadRecordById('mod_news_category', $parentId, 'Parent category');
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
