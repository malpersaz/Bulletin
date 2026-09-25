<?php
/**
 * Amed Framework
 * MCP Announcement Tools - CRUD implementations for mod_announcements
 * lib/McpAnnouncementTools.class.php
 */

class McpAnnouncementTools extends McpCrudToolsBase
{
    private const ANNOUNCEMENT_IMAGE_SIZES = [
        '/' => ['width' => 600, 'height' => 780, 'option' => 'width'],
        '/thumb' => ['width' => 300, 'height' => 390, 'option' => 'width'],
        '/original' => ['width' => null, 'height' => null, 'option' => 'original'],
    ];

    /**
     * Lists announcements with optional filters
     */
    public function listAnnouncements(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_announcements');
        $model->select('id, lang, name, status, deck, created_at');
        $this->applyAnnouncementFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_announcements');
        $countModel->select('id');
        $this->applyAnnouncementFilters($countModel, $args);
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
     * Returns a single announcement with all fields
     */
    public function getAnnouncement(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadAnnouncementById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Announcement record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates an announcement (mirrors the admin add validator)
     */
    public function createAnnouncement(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 70) {
            throw new McpToolException("Field 'name' is required (1-70 characters).");
        }

        $lang = $this->requireActiveLang($args);

        $content = (string) ($args['content'] ?? '');
        if (trim(strip_tags($content)) === '') {
            throw new McpToolException("Field 'content' is required.");
        }

        $summary = trim((string) ($args['summary'] ?? ''));
        if ($summary === '') {
            throw new McpToolException("Field 'summary' is required.");
        }
        $this->assertSummaryLength($summary);

        $model = $this->freshModel('mod_announcements');
        $model->name = $name;
        $model->lang = $lang;
        $model->content = $content;
        $model->summary = $summary;
        $model->status = $this->optionalEnum($args, 'status', 'y', ['y', 'n']);
        $model->deck = $this->optionalEnum($args, 'deck', 'n', ['y', 'n']);
        $model->created_at = $this->optionalDateTime($args, 'created_at', date('Y-m-d H:i:s'));
        $model->acl_id = (int) ($args['acl_id'] ?? 0);
        $model->gallery_id = (int) ($args['gallery_id'] ?? 0);
        $model->file_id = (int) ($args['file_id'] ?? 0);
        $model->user_id = $this->actingUserId;

        $imageUrl = null;
        if (array_key_exists('image', $args)) {
            $imageUrl = $this->resolveImageArgument('mod_announcements', $args, self::ANNOUNCEMENT_IMAGE_SIZES, null);
            $model->image = (string) $imageUrl;
        }

        if (!$model->save(true)) {
            if ($imageUrl !== null && $imageUrl !== '') {
                $this->deleteImageFiles('mod_announcements', $imageUrl, self::ANNOUNCEMENT_IMAGE_SIZES);
            }
            throw new McpToolException('Failed to create the announcement record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getAnnouncement(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates an announcement
     */
    public function updateAnnouncement(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadAnnouncementById($id);

        $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;

        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if ($name === '' || mb_strlen($name) > 70) {
                throw new McpToolException("Field 'name' must be 1-70 characters.");
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

        foreach (['status', 'deck'] as $field) {
            if (isset($args[$field])) {
                $model->{$field} = $this->optionalEnum($args, $field, (string) $model->{$field}, ['y', 'n']);
            }
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
            $newUrl = $this->resolveImageArgument('mod_announcements', $args, self::ANNOUNCEMENT_IMAGE_SIZES, $previousUrl);
            if ($newUrl !== null) {
                $model->image = $newUrl;
            }
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the announcement record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getAnnouncement(['id' => $id])['item']
        ];
    }

    /**
     * Deletes an announcement with admin parity cleanup
     */
    public function deleteAnnouncement(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadAnnouncementById($id);

        $this->assertModerateDelete('mod_announcements', (int) $model->user_id);

        $this->removeBridgeRelations('mod_announcements', $id);

        if ((string) $model->image !== '') {
            $this->deleteImageFiles('mod_announcements', (string) $model->image, self::ANNOUNCEMENT_IMAGE_SIZES);
        }

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // Announcement helpers
    // ------------------------------------------------------------------

    private function loadAnnouncementById(int $id): mod_announcements_model
    {
        $model = $this->freshModel('mod_announcements');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Announcement record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyAnnouncementFilters($model, array $args): void
    {
        if (isset($args['lang']) && trim((string) $args['lang']) !== '') {
            $model->where_lang(trim((string) $args['lang']));
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
