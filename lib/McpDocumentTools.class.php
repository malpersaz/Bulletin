<?php
/**
 * Amed Framework
 * MCP Document Tools - CRUD implementations for mod_document
 * lib/McpDocumentTools.class.php
 */

class McpDocumentTools extends McpCrudToolsBase
{
    /**
     * Image size pipeline mirroring the mod_document admin controller
     */
    private const DOCUMENT_IMAGE_SIZES = [
        '/' => ['width' => 1200, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 300, 'height' => null, 'option' => 'width'],
    ];

    // ------------------------------------------------------------------
    // mod_document tools
    // ------------------------------------------------------------------

    /**
     * Lists documents with optional filters
     */
    public function listDocuments(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_document');
        $model->select('id, lang, name, seo, status, image');
        $this->applyDocumentFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_document');
        $countModel->select('id');
        $this->applyDocumentFilters($countModel, $args);
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
     * Returns a single document record with all fields
     */
    public function getDocument(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadDocumentById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Document record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a document record (mirrors the admin add validator and flow)
     */
    public function createDocument(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }

        $lang = $this->requireActiveLang($args);

        $seo = trim((string) ($args['seo'] ?? ''));
        if ($seo === '') {
            $seo = self::makeSeo($name, $lang);
        }
        if ($seo === '' || mb_strlen($seo) > 255) {
            throw new McpToolException("Field 'seo' must be 1-255 characters.");
        }
        if ($this->recordSeoExists('mod_document', $seo, $lang)) {
            throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
        }

        $title = trim((string) ($args['title'] ?? ''));
        if ($title === '') {
            $title = $name;
        }
        if (mb_strlen($title) > 255) {
            throw new McpToolException("Field 'title' must be at most 255 characters.");
        }

        $keywords = trim((string) ($args['keywords'] ?? ''));
        if ($keywords === '' || mb_strlen($keywords) > 255) {
            throw new McpToolException("Field 'keywords' is required (1-255 characters).");
        }

        $description = trim((string) ($args['description'] ?? ''));
        if ($description === '' || mb_strlen($description) > 500) {
            throw new McpToolException("Field 'description' is required (1-500 characters).");
        }

        $model = $this->freshModel('mod_document');
        $model->lang = $lang;
        $model->name = $name;
        $model->seo = $seo;
        $model->title = $title;
        $model->keywords = $keywords;
        $model->description = $description;
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        $model->user_id = $this->actingUserId;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_document', $args, self::DOCUMENT_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_document', $imageUrl, self::DOCUMENT_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the document record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getDocument(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a document record (mirrors the admin edit validator and flow)
     */
    public function updateDocument(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadDocumentById($id);

        $this->assertModerateEdit('mod_document', (int) $model->user_id);

        if (isset($args['lang'])) {
            $model->lang = $this->requireActiveLang($args);
        }

        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 2-255 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['seo'])) {
            $seo = trim((string) $args['seo']);
            if ($seo === '' || mb_strlen($seo) > 255) {
                throw new McpToolException("Field 'seo' must be 1-255 characters.");
            }
            if ($this->recordSeoExists('mod_document', $seo, (string) $model->lang, $id)) {
                throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
            }
            $model->seo = $seo;
        }

        if (isset($args['title'])) {
            $model->title = $this->optionalString($args, 'title', (string) $model->title, 255);
        }

        if (isset($args['keywords'])) {
            $keywords = trim((string) $args['keywords']);
            if ($keywords === '' || mb_strlen($keywords) > 255) {
                throw new McpToolException("Field 'keywords' must be 1-255 characters.");
            }
            $model->keywords = $keywords;
        }

        if (isset($args['description'])) {
            $description = trim((string) $args['description']);
            if ($description === '' || mb_strlen($description) > 500) {
                throw new McpToolException("Field 'description' must be 1-500 characters.");
            }
            $model->description = $description;
        }

        if (isset($args['status'])) {
            $model->status = $this->optionalEnum($args, 'status', (string) $model->status, ['y', 'n']);
        }

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_document', $args, self::DOCUMENT_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the document record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getDocument(['id' => $id])['item']
        ];
    }

    /**
     * Deletes a document record with full admin parity cleanup
     */
    public function deleteDocument(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadDocumentById($id);

        $this->assertModerateDelete('mod_document', (int) $model->user_id);

        $this->removeBridgeRelations('mod_document', $id);

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_document', (string) $model->image, self::DOCUMENT_IMAGE_SIZES);
        }

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // Document helpers
    // ------------------------------------------------------------------

    private function loadDocumentById(int $id): mod_document_model
    {
        $model = $this->freshModel('mod_document');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Document record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyDocumentFilters($model, array $args): void
    {
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
}
