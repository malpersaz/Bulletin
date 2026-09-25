<?php
/**
 * Amed Framework
 * MCP Headline Tools - CRUD implementations for mod_headline
 * lib/McpHeadlineTools.class.php
 */

class McpHeadlineTools extends McpCrudToolsBase
{
    /**
     * Headline image sizes, mirrors the admin controller option array
     */
    private const HEADLINE_IMAGE_SIZES = [
        '/' => ['width' => 2000, 'height' => 750, 'option' => 'width'],
        '/thumb' => ['width' => 100, 'height' => null, 'option' => 'width'],
    ];

    // ------------------------------------------------------------------
    // mod_headline tools
    // ------------------------------------------------------------------

    /**
     * Lists headline items with optional filters
     */
    public function listHeadlines(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_headline');
        $model->select('id, lang, name, status, created_at');
        $this->applyHeadlineFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_headline');
        $countModel->select('id');
        $this->applyHeadlineFilters($countModel, $args);
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
     * Returns a single headline item with all fields
     */
    public function getHeadline(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadHeadlineById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Headline record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a headline item (mirrors the admin add validator)
     */
    public function createHeadline(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }

        $lang = $this->requireActiveLang($args);

        $model = $this->freshModel('mod_headline');
        $model->name = $name;
        $model->lang = $lang;
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        // The admin controller always stamps the creation time on add
        $model->created_at = date('Y-m-d H:i:s');
        $model->user_id = $this->actingUserId;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_headline', $args, self::HEADLINE_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_headline', $imageUrl, self::HEADLINE_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the headline record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getHeadline(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a headline item
     */
    public function updateHeadline(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadHeadlineById($id);

        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;

        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 2-255 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['status'])) {
            $model->status = $this->optionalEnum($args, 'status', (string) $model->status, ['y', 'n']);
        }

        if (isset($args['created_at'])) {
            $model->created_at = $this->optionalDateTime($args, 'created_at', (string) $model->created_at);
        }

        $model->lang = $lang;

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_headline', $args, self::HEADLINE_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the headline record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getHeadline(['id' => $id])['item']
        ];
    }

    /**
     * Deletes a headline item with admin parity cleanup
     */
    public function deleteHeadline(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadHeadlineById($id);

        $this->assertModerateDelete('mod_headline', (int) $model->user_id);

        $this->removeBridgeRelations('mod_headline', $id);

        if ((string) ($model->image ?? '') !== '') {
            $this->deleteImageFiles('mod_headline', (string) $model->image, self::HEADLINE_IMAGE_SIZES);
        }

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // Headline helpers
    // ------------------------------------------------------------------

    private function loadHeadlineById(int $id): mod_headline_model
    {
        $model = $this->freshModel('mod_headline');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Headline record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyHeadlineFilters($model, array $args): void
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
