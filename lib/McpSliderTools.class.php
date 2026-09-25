<?php
/**
 * Amed Framework
 * MCP Slider Tools - CRUD implementations for mod_slider
 * lib/McpSliderTools.class.php
 */

class McpSliderTools extends McpCrudToolsBase
{
    /**
     * Desktop image sizes, mirrors the admin controller option array
     */
    private const SLIDER_IMAGE_SIZES = [
        '/' => ['width' => 1920, 'height' => 1080, 'option' => 'width'],
        '/thumb' => ['width' => 100, 'height' => null, 'option' => 'width'],
    ];

    /**
     * Mobile image sizes, mirrors the admin controller option array
     */
    private const SLIDER_MOBILE_IMAGE_SIZES = [
        '/' => ['width' => 1080, 'height' => null, 'option' => 'width'],
    ];

    // ------------------------------------------------------------------
    // mod_slider tools
    // ------------------------------------------------------------------

    /**
     * Lists slider items with optional filters
     */
    public function listSliders(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_slider');
        $model->select('id, lang, name, url, status, target, created_at');
        $this->applySliderFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_slider');
        $countModel->select('id');
        $this->applySliderFilters($countModel, $args);
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
     * Returns a single slider item with all fields
     */
    public function getSlider(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadSliderById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Slider record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a slider item (mirrors the admin add validator)
     */
    public function createSlider(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }

        $url = trim((string) ($args['url'] ?? ''));
        if (mb_strlen($url) < 2 || mb_strlen($url) > 255) {
            throw new McpToolException("Field 'url' is required (2-255 characters).");
        }

        $lang = $this->requireActiveLang($args);

        $model = $this->freshModel('mod_slider');
        $model->name = $name;
        $model->url = $url;
        $model->lang = $lang;
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        $model->target = $this->optionalEnum($args, 'target', 'y', ['y', 'n']);
        $model->created_at = $this->optionalDateTime($args, 'created_at', date('Y-m-d H:i:s'));
        $model->description = $this->optionalString($args, 'description', '', 65535);
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $model->user_id = $this->actingUserId;

        // The database column is NOT NULL without a default: always set it
        $model->image = '';
        $model->image_mobile = '';

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_slider', $args, self::SLIDER_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        $mobileImageUrl = null;
        if (array_key_exists('image_mobile', $args)) {
            $mobileArgs = ['image' => $args['image_mobile']];
            $mobileImageUrl = $this->resolveImageArgument('mod_slider', $mobileArgs, self::SLIDER_MOBILE_IMAGE_SIZES, null);
            $model->image_mobile = (string) $mobileImageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_slider', $imageUrl, self::SLIDER_IMAGE_SIZES);
            }
            if ($mobileImageUrl !== null && $mobileImageUrl !== '') {
                $this->deleteImageFiles('mod_slider', $mobileImageUrl, self::SLIDER_MOBILE_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the slider record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getSlider(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a slider item
     */
    public function updateSlider(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadSliderById($id);

        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;

        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 2-255 characters.");
            }
            $model->name = $name;
        }

        if (isset($args['url'])) {
            $url = trim((string) $args['url']);
            if (mb_strlen($url) < 2 || mb_strlen($url) > 255) {
                throw new McpToolException("Field 'url' must be 2-255 characters.");
            }
            $model->url = $url;
        }

        foreach (['status', 'target'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalEnum($args, $field, (string) $model->{$field}, ['y', 'n']);
            }
        }

        if (isset($args['description'])) {
            $model->description = $this->optionalString($args, 'description', (string) $model->description, 65535);
        }

        if (isset($args['created_at'])) {
            $model->created_at = $this->optionalDateTime($args, 'created_at', (string) $model->created_at);
        }

        if (isset($args['acl_id'])) {
            $model->acl_id = (int) $args['acl_id'];
        }

        $model->lang = $lang;

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_slider', $args, self::SLIDER_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (array_key_exists('image_mobile', $args)) {
            $previousUrl = (string) ($model->image_mobile ?? '');
            $mobileArgs = ['image' => $args['image_mobile']];
            $newUrl = $this->resolveImageArgument('mod_slider', $mobileArgs, self::SLIDER_MOBILE_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image_mobile = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the slider record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getSlider(['id' => $id])['item']
        ];
    }

    /**
     * Deletes a slider item with image cleanup
     */
    public function deleteSlider(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadSliderById($id);

        $this->assertModerateDelete('mod_slider', (int) $model->user_id);

        $this->removeBridgeRelations('mod_slider', $id);

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_slider', (string) $model->image, self::SLIDER_IMAGE_SIZES);
        }
        if ((string) ($model->image_mobile ?? '') !== '') {
            $this->deleteImageFiles('mod_slider', (string) $model->image_mobile, self::SLIDER_MOBILE_IMAGE_SIZES);
        }

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // Slider helpers
    // ------------------------------------------------------------------

    private function loadSliderById(int $id): mod_slider_model
    {
        $model = $this->freshModel('mod_slider');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Slider record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applySliderFilters($model, array $args): void
    {
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
        }
        if (isset($args['status']) && in_array($args['status'], ['y', 'n'], true)) {
            $model->where_status($args['status']);
        }
        if (isset($args['target']) && in_array($args['target'], ['y', 'n'], true)) {
            $model->where_target($args['target']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $model->where('name LIKE \'%' . Obuilder::cc(trim((string) $args['search'])) . '%\'');
        }
    }
}
