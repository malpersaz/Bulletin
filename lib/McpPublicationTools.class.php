<?php
/**
 * Amed Framework
 * MCP Publication Tools - CRUD implementations for mod_publication and mod_publication_category
 * lib/McpPublicationTools.class.php
 *
 * Implemented for mod_publication/mod_publication_category by explicit user authorization
 * despite the module boundary guard policy.
 */

class McpPublicationTools extends McpCrudToolsBase
{
    private const PUBLICATION_IMAGE_SIZES = [
        '/' => ['width' => 1600, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 600, 'height' => null, 'option' => 'width'],
    ];

    private const PUBLICATION_CATEGORY_IMAGE_SIZES = [
        '/' => ['width' => 200, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 150, 'height' => 90, 'option' => 'extract'],
    ];

    // ------------------------------------------------------------------
    // mod_publication tools
    // ------------------------------------------------------------------

    /**
     * Lists publications with optional filters
     */
    public function listPublications(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_publication');
        $model->select('id, lang, name, category_id, status, deck, slider, seo, created_at, updated_at');
        $this->applyPublicationFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_publication');
        $countModel->select('id');
        $this->applyPublicationFilters($countModel, $args);
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
     * Returns a single publication record with all fields
     */
    public function getPublication(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadPublicationById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Publication record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a publication record (mirrors the admin add validator)
     */
    public function createPublication(array $args): array
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
        $this->assertRecordMatchesLang('mod_publication_category', $categoryId, $lang, 'Publication category');

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
        if ($this->recordSeoExists('mod_publication', $seo, $lang)) {
            throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
        }

        $model = $this->freshModel('mod_publication');
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
                $authorUserId = (int) ($userRow['id'] ?? $this->actingUserId);
            }
        }
        $model->user_id = $authorUserId;
        $model->menu = 0;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_publication', $args, self::PUBLICATION_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_publication', $imageUrl, self::PUBLICATION_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the publication record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getPublication(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a publication record
     */
    public function updatePublication(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadPublicationById($id);

        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;
        $categoryId = isset($args['category_id']) ? (int) $args['category_id'] : (int) $model->category_id;

        if ($categoryId !== (int) $model->category_id || $lang !== (string) $model->lang) {
            $this->assertRecordMatchesLang('mod_publication_category', $categoryId, $lang, 'Publication category');
        }

        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 2-255 characters.");
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
            if ($this->recordSeoExists('mod_publication', $seo, $lang, $id)) {
                throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
            }
            $model->seo = $seo;
        }

        if (isset($args['specifications'])) {
            $model->specifications = (string) $args['specifications'];
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
            $newUrl = $this->resolveImageArgument('mod_publication', $args, self::PUBLICATION_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the publication record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getPublication(['id' => $id])['item']
        ];
    }

    /**
     * Deletes a publication record with full admin parity cleanup
     */
    public function deletePublication(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadPublicationById($id);

        $this->assertModerateDelete('mod_publication', (int) $model->user_id);

        $this->removeBridgeRelations('mod_publication', $id);

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_publication', (string) $model->image, self::PUBLICATION_IMAGE_SIZES);
        }

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // mod_publication_category tools
    // ------------------------------------------------------------------

    /**
     * Lists publication categories
     */
    public function listPublicationCategories(array $args): array
    {
        return $this->listCategoriesGeneric('mod_publication_category', $args, 50);
    }

    /**
     * Returns a single Publication category with all fields
     */
    public function getPublicationCategory(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadPublicationCategoryById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Publication category record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a Publication category
     */
    public function createPublicationCategory(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }

        $lang = $this->requireActiveLang($args);

        $parentId = (int) ($args['parent_id'] ?? 0);
        if ($parentId > 0) {
            $parent = $this->loadPublicationCategoryById($parentId);
            if ((string) $parent->lang !== $lang) {
                throw new McpToolException("Parent category '{$parentId}' belongs to language '" . $parent->lang . "', cannot nest a '{$lang}' category under it.");
            }
        }

        $model = $this->freshModel('mod_publication_category');
        $model->name = $name;
        $model->lang = $lang;
        $model->parent_id = $parentId;
        $model->root = $this->optionalEnum($args, 'root', 'n', ['y', 'n']);
        $model->seo = $this->optionalString($args, 'seo', self::makeSeo($name, $lang), 255);
        $model->title = $this->optionalString($args, 'title', $name, 255);
        $model->keywords = $this->optionalString($args, 'keywords', '', 255);
        $model->description = $this->optionalString($args, 'description', '', 255);
        $model->show_on_homepage = $this->optionalEnum($args, 'show_on_homepage', 'n', ['y', 'n']);
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $model->menu = 0;
        $model->user_id = $this->actingUserId;
        $model->order = $this->nextSiblingOrder('mod_publication_category', $parentId);

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_publication_category', $args, self::PUBLICATION_CATEGORY_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_publication_category', $imageUrl, self::PUBLICATION_CATEGORY_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the publication category record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getPublicationCategory(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a Publication category and syncs related menu entries
     */
    public function updatePublicationCategory(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadPublicationCategoryById($id);

        $previousMenuUrl = $model->lang . '/publications/' . $model->seo;

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
                if ($parentId === $id) {
                    throw new McpToolException("A category cannot be its own parent.");
                }
                $parent = $this->loadPublicationCategoryById($parentId);
                if ((string) $parent->lang !== (string) $model->lang) {
                    throw new McpToolException("Parent category '{$parentId}' belongs to language '" . $parent->lang . "', cannot nest a '" . $model->lang . "' category under it.");
                }
            }
            $model->parent_id = $parentId;
        }

        if (isset($args['root'])) {
            $model->root = $this->optionalEnum($args, 'root', (string) $model->root, ['y', 'n']);
        }

        if (isset($args['show_on_homepage'])) {
            $model->show_on_homepage = $this->optionalEnum($args, 'show_on_homepage', (string) $model->show_on_homepage, ['y', 'n']);
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

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_publication_category', $args, self::PUBLICATION_CATEGORY_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the publication category record.');
        }

        $this->syncPublicationMenuOnUpdate($previousMenuUrl, $model);

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getPublicationCategory(['id' => $id])['item']
        ];
    }

    /**
     * Recursively deletes a Publication category tree with admin parity cleanup
     */
    public function deletePublicationCategory(array $args): array
    {
        $id = $this->requireId($args);
        $deletedIds = [];
        $this->deletePublicationCategoryTree($id, $deletedIds);

        return ['deleted' => true, 'ids' => $deletedIds];
    }

    // ------------------------------------------------------------------
    // Publication helpers
    // ------------------------------------------------------------------

    private function deletePublicationCategoryTree(int $id, array &$deletedIds): void
    {
        $model = $this->loadPublicationCategoryById($id);

        $this->assertModerateDelete('mod_publication_category', (int) $model->user_id);

        $childrenModel = $this->freshModel('mod_publication_category');
        $childrenModel->where_parent_id($id)->getResult(true, true);
        foreach ($childrenModel->toArray() as $child) {
            $this->deletePublicationCategoryTree((int) $child['id'], $deletedIds);
        }

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_publication_category', (string) $model->image, self::PUBLICATION_CATEGORY_IMAGE_SIZES);
        }

        // Publication category parity: gallery/attachment bridge relations are cleaned up too
        $this->removeBridgeRelations('mod_publication_category', $id);


        $menuUrl = $model->lang . '/publications/' . $model->seo;
        $menuModel = $this->freshModel('mod_menu');
        if ($menuModel->exists_url($menuUrl)) {
            $menuModel->getResultOne(true, true)->delete();
        }

        $model->delete();
        $deletedIds[] = $id;
    }

    private function syncPublicationMenuOnUpdate(string $previousMenuUrl, $categoryModel): void
    {
        $menuModel = $this->freshModel('mod_menu');
        if ($menuModel->exists_url($previousMenuUrl)) {
            $menuModel->getResultOne(true, true)->sync();
            $menuModel->name = $categoryModel->name;
            $menuModel->lang = $categoryModel->lang;
            $menuModel->url = $categoryModel->lang . '/publications/' . $categoryModel->seo;
            $menuModel->acl_id = $categoryModel->acl_id;
            $menuModel->update();
        }
    }

    private function loadPublicationById(int $id): mod_publication_model
    {
        $model = $this->freshModel('mod_publication');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Publication record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function loadPublicationCategoryById(int $id): mod_publication_category_model
    {
        $model = $this->freshModel('mod_publication_category');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Publication category record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyPublicationFilters($model, array $args): void
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
        if (isset($args['highlighted']) && in_array($args['highlighted'], ['y', 'n'], true)) {
            $model->where_highlighted($args['highlighted']);
        }
        if (isset($args['show_on_homepage']) && in_array($args['show_on_homepage'], ['y', 'n'], true)) {
            $model->where_show_on_homepage($args['show_on_homepage']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $model->where('name LIKE \'%' . Obuilder::cc(trim((string) $args['search'])) . '%\'');
        }
    }
}
