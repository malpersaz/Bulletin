<?php
/**
 * Amed Framework
 * MCP Popup Tools - CRUD implementations for mod_popup
 * lib/McpPopupTools.class.php
 */

class McpPopupTools extends McpCrudToolsBase
{
    /**
     * Image size pipeline mirroring the mod_popup admin controller
     */
    private const POPUP_IMAGE_SIZES = [
        '/' => ['width' => 900, 'height' => null, 'option' => 'width'],
        '/thumb' => ['width' => 100, 'height' => null, 'option' => 'width'],
    ];

    private const VIDEO_SOURCES = ['url', 'youtube', 'vimeo', 'instagram'];

    // ------------------------------------------------------------------
    // mod_popup tools
    // ------------------------------------------------------------------

    /**
     * Lists popups with optional filters
     */
    public function listPopups(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_popup');
        $model->select('id, lang, name, image, publish_at, expired_at, status');
        $this->applyPopupFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_popup');
        $countModel->select('id');
        $this->applyPopupFilters($countModel, $args);
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
     * Returns a single popup record with all fields
     */
    public function getPopup(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadPopupById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Popup record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a popup record (mirrors the admin add validator and flow)
     */
    public function createPopup(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }

        $lang = $this->requireActiveLang($args);

        if (!isset($args['publish_at']) || trim((string) $args['publish_at']) === '') {
            throw new McpToolException("Field 'publish_at' is required.");
        }
        $publishAt = $this->optionalDateTime($args, 'publish_at', '');

        if (!isset($args['expired_at']) || trim((string) $args['expired_at']) === '') {
            throw new McpToolException("Field 'expired_at' is required.");
        }
        $expiredAt = $this->optionalDateTime($args, 'expired_at', '');

        $model = $this->freshModel('mod_popup');
        $model->lang = $lang;
        $model->name = $name;
        $model->publish_at = $publishAt;
        $model->expired_at = $expiredAt;
        // The admin add flow always creates popups unpublished
        $model->status = 'n';
        $model->content = (string) ($args['content'] ?? '');
        $model->duration = $this->optionalString($args, 'duration', '', 255);
        $model->video = $this->optionalEnum($args, 'video', 'url', self::VIDEO_SOURCES);
        $model->link = $this->optionalString($args, 'link', '', 255);
        $model->user_id = $this->actingUserId;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_popup', $args, self::POPUP_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_popup', $imageUrl, self::POPUP_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the popup record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getPopup(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates a popup record (mirrors the admin edit validator and flow)
     */
    public function updatePopup(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadPopupById($id);

        $this->assertModerateEdit('mod_popup', (int) $model->user_id);

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

        if (isset($args['publish_at'])) {
            $model->publish_at = $this->optionalDateTime($args, 'publish_at', (string) $model->publish_at);
        }

        if (isset($args['expired_at'])) {
            $model->expired_at = $this->optionalDateTime($args, 'expired_at', (string) $model->expired_at);
        }

        if (isset($args['content'])) {
            $model->content = (string) $args['content'];
        }

        if (isset($args['duration'])) {
            $model->duration = $this->optionalString($args, 'duration', (string) $model->duration, 255);
        }

        if (isset($args['video'])) {
            $model->video = $this->optionalEnum($args, 'video', (string) $model->video, self::VIDEO_SOURCES);
        }

        if (isset($args['link'])) {
            $model->link = $this->optionalString($args, 'link', (string) $model->link, 255);
        }

        if (array_key_exists('image', $args)) {
            $previousUrl = (string) ($model->image ?? '');
            $newUrl = $this->resolveImageArgument('mod_popup', $args, self::POPUP_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the popup record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getPopup(['id' => $id])['item']
        ];
    }

    /**
     * Deletes a popup record with full admin parity cleanup
     */
    public function deletePopup(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadPopupById($id);

        $this->assertModerateDelete('mod_popup', (int) $model->user_id);

        $this->removeBridgeRelations('mod_popup', $id);

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_popup', (string) $model->image, self::POPUP_IMAGE_SIZES);
        }

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // Popup helpers
    // ------------------------------------------------------------------

    private function loadPopupById(int $id): mod_popup_model
    {
        $model = $this->freshModel('mod_popup');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Popup record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyPopupFilters($model, array $args): void
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
