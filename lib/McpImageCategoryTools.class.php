<?php
/**
 * Amed Framework
 * MCP Image Category Tools - CRUD implementations for mod_imagecategories
 * lib/McpImageCategoryTools.class.php
 */

class McpImageCategoryTools extends McpCrudToolsBase
{
    /**
     * Image sizes mirror mod_imagecategories_mod::imageOptions()
     */
    private const IMAGE_SIZES = [
        '/' => ['width' => 400, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 200, 'height' => null, 'option' => 'width'],
    ];

    private const MENU_URL_SEGMENT = 'gallery';

    // ------------------------------------------------------------------
    // Tools
    // ------------------------------------------------------------------

    /**
     * Lists image categories with optional filters
     */
    public function listImageCategories(array $args): array
    {
        return $this->listCategoriesGeneric('mod_imagecategories', $args, 50);
    }

    /**
     * Returns a single image category record with all fields
     */
    public function getImageCategory(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadImageCategoryById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Image category record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates an image category record (mirrors the admin add validator)
     */
    public function createImageCategory(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }

        $lang = $this->requireActiveLang($args);
        $root = $this->optionalEnum($args, 'root', 'n', ['y', 'n']);
        $parentId = (int) ($args['parent_id'] ?? 0);

        $this->assertParentRules($parentId, $lang, $name, $root, 0);

        $seo = trim((string) ($args['seo'] ?? ''));
        if ($seo === '') {
            $seo = self::makeSeo($name, $lang);
        }
        if ($seo === '' || mb_strlen($seo) > 255) {
            throw new McpToolException("Field 'seo' must be 1-255 characters.");
        }
        if ($this->recordSeoExists('mod_imagecategories', $seo, $lang)) {
            throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
        }

        $title = $this->optionalString($args, 'title', $name, 255);
        $keywords = $this->optionalString($args, 'keywords', $name, 255);
        $description = $this->optionalString($args, 'description', mb_substr($name, 0, 255), 255);
        if ($title === '' || $keywords === '' || $description === '') {
            throw new McpToolException("Fields 'title', 'keywords' and 'description' are required (1-255 characters).");
        }

        $menuParentId = (int) ($args['menu_parent_id'] ?? 0);
        $this->assertMenuParent($menuParentId, $lang);

        $model = $this->freshModel('mod_imagecategories');
        $model->name = $name;
        $model->lang = $lang;
        $model->content = (string) ($args['content'] ?? '');
        $model->parent_id = $parentId;
        $model->root = $root;
        $model->seo = $seo;
        $model->title = $title;
        $model->keywords = $keywords;
        $model->description = $description;
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $model->user_id = $this->actingUserId;
        $model->order = $this->nextSiblingOrder('mod_imagecategories', $parentId);

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_imagecategories', $args, self::IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_imagecategories', $imageUrl, self::IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the image category record.');
        }

        if ($menuParentId > 0) {
            $this->createMenuEntry($menuParentId, $model);
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getImageCategory(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates an image category record and syncs related menu entries
     */
    public function updateImageCategory(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadImageCategoryById($id);

        $this->assertModerateEdit('mod_imagecategories', (int) $model->user_id);

        $previousMenuUrl = $this->categoryMenuUrl((string) $model->lang, (string) $model->seo);
        $currentMenuParentId = $this->findMenuParentId($previousMenuUrl);

        if (isset($args['lang'])) {
            $model->lang = $this->requireActiveLang($args);
        }
        $lang = (string) $model->lang;

        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 2-255 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['content'])) {
            $model->content = (string) $args['content'];
        }

        if (isset($args['root'])) {
            $model->root = $this->optionalEnum($args, 'root', (string) $model->root, ['y', 'n']);
        }

        $parentChanged = false;
        if (isset($args['parent_id'])) {
            $parentId = (int) $args['parent_id'];
            if ($parentId !== (int) $model->parent_id) {
                $parentChanged = true;
            }
            $this->assertParentRules($parentId, $lang, (string) $model->name, (string) $model->root, $id);
            $model->parent_id = $parentId;
        }

        if (isset($args['lang']) && !isset($args['parent_id']) && (int) $model->parent_id > 0) {
            $this->assertParentRules((int) $model->parent_id, $lang, (string) $model->name, (string) $model->root, $id);
        }

        if (isset($args['seo'])) {
            $seo = trim((string) $args['seo']);
            if ($seo === '' || mb_strlen($seo) > 255) {
                throw new McpToolException("Field 'seo' must be 1-255 characters.");
            }
            if ($this->recordSeoExists('mod_imagecategories', $seo, $lang, $id)) {
                throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
            }
            $model->seo = $seo;
        }

        foreach (['title', 'keywords', 'description'] as $field) {
            if (isset($args[$field])) {
                $value = $this->optionalString($args, $field, (string) $model->{$field}, 255);
                if ($value === '') {
                    throw new McpToolException("Field '{$field}' must be 1-255 characters.");
                }
                $model->{$field} = $value;
            }
        }

        if (isset($args['acl_id'])) {
            $model->acl_id = (int) $args['acl_id'];
        }

        if ($parentChanged) {
            $model->order = $this->nextSiblingOrder('mod_imagecategories', (int) $model->parent_id);
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
            $newUrl = $this->resolveImageArgument('mod_imagecategories', $args, self::IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        $menuParentId = isset($args['menu_parent_id']) ? (int) $args['menu_parent_id'] : $currentMenuParentId;
        $this->assertMenuParent($menuParentId, $lang);

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the image category record.');
        }

        $this->syncMenuOnUpdate($previousMenuUrl, $menuParentId, $model);

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getImageCategory(['id' => $id])['item']
        ];
    }

    /**
     * Recursively deletes an image category tree with admin parity cleanup
     */
    public function deleteImageCategory(array $args): array
    {
        $id = $this->requireId($args);
        $deletedIds = [];
        $this->deleteCategoryTree($id, $deletedIds);

        return ['deleted' => true, 'ids' => $deletedIds];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function loadImageCategoryById(int $id): mod_imagecategories_model
    {
        $model = $this->freshModel('mod_imagecategories');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Image category record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    /**
     * Mirrors the admin validateCategoryParent rules: parent existence,
     * parent cycles, parent language match and language-root constraints
     */
    private function assertParentRules(int $parentId, string $lang, string $name, string $root, int $currentId): void
    {
        $isLanguageRootCandidate = strpos(trim($name), '##') === 0 && $root === 'y';

        if ($parentId !== 0 && $isLanguageRootCandidate) {
            throw new McpToolException("A language root category ('##' name with root 'y') cannot have a parent.");
        }

        if ($parentId === 0) {
            $existingRootId = $this->findLanguageRootId($lang, $currentId);
            if ($existingRootId !== 0) {
                throw new McpToolException("Language '{$lang}' already has a root category ('{$existingRootId}'); new top-level categories must nest under it.");
            }
            return;
        }

        if ($currentId !== 0 && $parentId === $currentId) {
            throw new McpToolException("A category cannot be its own parent.");
        }

        $parent = $this->loadImageCategoryById($parentId);

        if ($currentId !== 0 && $this->isDescendantOf($parentId, $currentId)) {
            throw new McpToolException("Parent category '{$parentId}' is a descendant of category '{$currentId}'; parent cycles are not allowed.");
        }

        if ((string) $parent->lang !== $lang) {
            throw new McpToolException("Parent category '{$parentId}' belongs to language '" . $parent->lang . "', cannot nest a '{$lang}' category under it.");
        }
    }

    /**
     * Finds the language root row ('##' name, root 'y', parent_id 0) for a language
     */
    private function findLanguageRootId(string $lang, int $excludeId): int
    {
        $model = $this->freshModel('mod_imagecategories');
        $model->where_lang_and_root($lang, 'y')->getResult(true, true);
        foreach ($model->toArray() as $row) {
            if (!is_array($row) || (int) ($row['parent_id'] ?? -1) !== 0) {
                continue;
            }
            if ($excludeId > 0 && (int) ($row['id'] ?? 0) === $excludeId) {
                continue;
            }
            if (strpos(trim((string) ($row['name'] ?? '')), '##') === 0) {
                return (int) $row['id'];
            }
        }
        return 0;
    }

    private function isDescendantOf(int $candidateId, int $ancestorId): bool
    {
        $visited = [];
        $currentId = $candidateId;
        while ($currentId > 0 && !isset($visited[$currentId])) {
            if ($currentId === $ancestorId) {
                return true;
            }
            $visited[$currentId] = true;
            $row = $this->loadImageCategoryById($currentId);
            $currentId = (int) $row->parent_id;
        }
        return false;
    }

    private function assertMenuParent(int $menuParentId, string $lang): void
    {
        if ($menuParentId === 0) {
            return;
        }

        $menuModel = $this->freshModel('mod_menu');
        $menuModel->where_id($menuParentId)->limit(1)->getResultOne(true, true);
        if ($menuModel->getSize() === 0) {
            throw new McpToolException("Field 'menu_parent_id' references menu entry '{$menuParentId}' which does not exist.");
        }
        $menuModel->sync();
        $menuLang = trim((string) $menuModel->lang);
        if ($menuLang !== '' && $menuLang !== $lang) {
            throw new McpToolException("Field 'menu_parent_id' references a menu entry of language '{$menuLang}', cannot attach a '{$lang}' category to it.");
        }
    }

    private function categoryMenuUrl(string $lang, string $seo): string
    {
        return $lang . '/' . self::MENU_URL_SEGMENT . '/' . $seo;
    }

    private function findMenuParentId(string $menuUrl): int
    {
        $menuModel = $this->freshModel('mod_menu');
        if (!$menuModel->exists_url($menuUrl)) {
            return 0;
        }
        $menuModel->getResultOne(true, true)->sync();
        return (int) $menuModel->parent_id;
    }

    private function nextMenuSiblingOrder(int $parentId, string $lang): int
    {
        $menuModel = $this->freshModel('mod_menu');
        $menuModel->where_parent_id_and_lang($parentId, $lang)->order('`order`', 'DESC')->limit(1)->getResultOne(true, true);
        if ($menuModel->getSize() === 0) {
            return 1;
        }
        $menuModel->sync();
        return (int) $menuModel->order + 1;
    }

    private function createMenuEntry(int $menuParentId, mod_imagecategories_model $category): void
    {
        $menuModel = $this->freshModel('mod_menu');
        $menuModel->parent_id = $menuParentId;
        $menuModel->name = (string) $category->name;
        $menuModel->lang = (string) $category->lang;
        $menuModel->url = $this->categoryMenuUrl((string) $category->lang, (string) $category->seo);
        $menuModel->acl_id = (int) $category->acl_id;
        $menuModel->target = 'n';
        $menuModel->status = 'y';
        $menuModel->order = $this->nextMenuSiblingOrder($menuParentId, (string) $category->lang);
        $menuModel->user_id = $this->actingUserId;
        $menuModel->save();
    }

    /**
     * Mirrors the admin syncMenuOnUpdate: detach, update or attach the menu entry
     */
    private function syncMenuOnUpdate(string $previousMenuUrl, int $menuParentId, mod_imagecategories_model $category): void
    {
        if ($menuParentId === 0) {
            $this->deleteMenuByUrl($previousMenuUrl);
            return;
        }

        $menuModel = $this->freshModel('mod_menu');
        if ($menuModel->exists_url($previousMenuUrl)) {
            $menuModel->getResultOne(true, true)->sync();
            $previousParentId = (int) $menuModel->parent_id;
            $menuModel->name = (string) $category->name;
            $menuModel->lang = (string) $category->lang;
            $menuModel->url = $this->categoryMenuUrl((string) $category->lang, (string) $category->seo);
            $menuModel->acl_id = (int) $category->acl_id;
            $menuModel->parent_id = $menuParentId;
            $menuModel->target = 'n';
            $menuModel->status = 'y';
            if ($previousParentId !== $menuParentId) {
                $menuModel->order = $this->nextMenuSiblingOrder($menuParentId, (string) $category->lang);
            }
            $menuModel->update();
            return;
        }

        $this->createMenuEntry($menuParentId, $category);
    }

    private function deleteMenuByUrl(string $menuUrl): void
    {
        $menuModel = $this->freshModel('mod_menu');
        if ($menuModel->exists_url($menuUrl)) {
            $menuModel->getResultOne(true, true)->delete();
        }
    }

    private function deleteCategoryTree(int $id, array &$deletedIds): void
    {
        $model = $this->loadImageCategoryById($id);

        $this->assertModerateDelete('mod_imagecategories', (int) $model->user_id);

        $childrenModel = $this->freshModel('mod_imagecategories');
        $childrenModel->where_parent_id($id)->getResult(true, true);
        foreach ($childrenModel->toArray() as $child) {
            $this->deleteCategoryTree((int) $child['id'], $deletedIds);
        }

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_imagecategories', (string) $model->image, self::IMAGE_SIZES);
        }

        $removeBridgeRelations = function(string $module, int $recordId) {
            $bridges = [
                'mod_gallery_mod' => 'mod_gallery',
                'mod_attachment_mod' => 'mod_attachment',
                'mod_faq_mod' => 'mod_faq',
            ];
            foreach ($bridges as $bridgeTable => $bridgeModule) {
                $bridgeModel = $this->freshModel($bridgeTable);
                if ($bridgeModel->exists_mod_name($module) === true) {
                    Run::import($bridgeModule, '_remove', [$module, $recordId]);
                }
            }
        };
        $removeBridgeRelations('mod_imagecategories', $id);

        $this->deleteMenuByUrl($this->categoryMenuUrl((string) $model->lang, (string) $model->seo));

        $model->delete();
        $deletedIds[] = $id;
    }
}
