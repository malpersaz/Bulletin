<?php
/**
 * Amed Framework
 * MCP Redirect Tools - CRUD implementations for mod_redirect
 * lib/McpRedirectTools.class.php
 */

class McpRedirectTools extends McpCrudToolsBase
{
    private const STATUS_CODES = [301, 302];

    // ------------------------------------------------------------------
    // mod_redirect tools
    // ------------------------------------------------------------------

    /**
     * Lists redirects with an optional search filter and summary stats
     */
    public function listRedirects(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_redirect');
        $model->select('id, old_url, new_url, status_code, created_at');
        $this->applyRedirectFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_redirect');
        $countModel->select('id');
        $this->applyRedirectFilters($countModel, $args);
        $countModel->getResult(true, true);
        $total = $countModel->getSize();

        return [
            'items' => is_array($items) ? $items : [],
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'stats' => $this->redirectStats(),
        ];
    }

    /**
     * Returns a single redirect record with all fields
     */
    public function getRedirect(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadRedirectById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("Redirect record '{$id}' not found.");
        }

        return ['item' => $row];
    }

    /**
     * Creates a redirect record (mirrors the admin add validator)
     */
    public function createRedirect(array $args): array
    {
        $oldUrl = $this->requireRedirectUrl($args, 'old_url', true);
        $newUrl = $this->requireRedirectUrl($args, 'new_url', false);
        $statusCode = $this->resolveStatusCode($args, 301);

        if ($this->redirectOldUrlExists($oldUrl)) {
            throw new McpToolException("Field 'old_url' must be unique: '{$oldUrl}' is already used by another redirect.");
        }

        $model = $this->freshModel('mod_redirect');
        $model->old_url = $oldUrl;
        $model->new_url = $newUrl;
        $model->status_code = $statusCode;

        if (!$model->save(true)) {
            throw new McpToolException('Failed to create the redirect record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getRedirect(['id' => (int) $model->id])['item'],
        ];
    }

    /**
     * Partially updates a redirect record (mirrors the admin edit validator)
     */
    public function updateRedirect(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadRedirectById($id);

        if (array_key_exists('old_url', $args)) {
            $oldUrl = $this->requireRedirectUrl($args, 'old_url', true);
            if ($this->redirectOldUrlExists($oldUrl, $id)) {
                throw new McpToolException("Field 'old_url' must be unique: '{$oldUrl}' is already used by another redirect.");
            }
            $model->old_url = $oldUrl;
        }

        if (array_key_exists('new_url', $args)) {
            $model->new_url = $this->requireRedirectUrl($args, 'new_url', false);
        }

        if (array_key_exists('status_code', $args)) {
            $model->status_code = $this->resolveStatusCode($args, (int) $model->status_code);
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the redirect record.');
        }

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getRedirect(['id' => $id])['item'],
        ];
    }

    /**
     * Deletes a redirect record
     */
    public function deleteRedirect(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadRedirectById($id);
        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // Redirect helpers
    // ------------------------------------------------------------------

    /**
     * Validates old_url/new_url against the admin validator rules
     * (old_url: 2-250 characters, new_url: 1-250 characters)
     */
    private function requireRedirectUrl(array $args, string $key, bool $minTwo): string
    {
        $value = trim((string) ($args[$key] ?? ''));
        $min = $minTwo ? 2 : 1;
        if (mb_strlen($value) < $min || mb_strlen($value) > 250) {
            throw new McpToolException("Field '{$key}' must be {$min}-250 characters.");
        }
        return $value;
    }

    /**
     * Resolves the status code; only 301 and 302 are valid
     */
    private function resolveStatusCode(array $args, int $default): int
    {
        $raw = $args['status_code'] ?? null;
        if ($raw === null || (string) $raw === '') {
            return $default;
        }
        $statusCode = (int) $raw;
        if (!in_array($statusCode, self::STATUS_CODES, true)) {
            throw new McpToolException("Field 'status_code' must be one of: " . implode(', ', self::STATUS_CODES) . '.');
        }
        return $statusCode;
    }

    private function redirectOldUrlExists(string $oldUrl, int $excludeId = 0): bool
    {
        $model = $this->freshModel('mod_redirect');
        $model->where_old_url($oldUrl);
        if ($excludeId > 0) {
            $model->addWhere('id <> ' . (int) $excludeId);
        }
        $model->limit(1)->getResultOne(true, true);
        return $model->getSize() > 0;
    }

    private function loadRedirectById(int $id): mod_redirect_model
    {
        $model = $this->freshModel('mod_redirect');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("Redirect record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyRedirectFilters($model, array $args): void
    {
        if (isset($args['status_code']) && in_array((int) $args['status_code'], self::STATUS_CODES, true)) {
            $model->where_status_code((int) $args['status_code']);
        }
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $search = Obuilder::cc(trim((string) $args['search']));
            $model->where("(old_url LIKE '%" . $search . "%' OR new_url LIKE '%" . $search . "%')");
        }
    }

    /**
     * Unfiltered totals mirroring the admin dashboard stats
     */
    private function redirectStats(): array
    {
        $stats = ['total' => 0, 'permanent' => 0, 'temporary' => 0];
        foreach (self::STATUS_CODES as $statusCode) {
            $model = $this->freshModel('mod_redirect');
            $model->select('id');
            $model->where_status_code($statusCode);
            $model->getResult(true, true);
            $count = $model->getSize();
            $stats['total'] += $count;
            if ($statusCode === 301) {
                $stats['permanent'] = $count;
            } else {
                $stats['temporary'] = $count;
            }
        }
        return $stats;
    }
}
