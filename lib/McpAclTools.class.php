<?php
/**
 * Amed Framework
 * MCP ACL Tools - CRUD implementations for mod_acl (access control groups)
 * lib/McpAclTools.class.php
 */

class McpAclTools extends McpCrudToolsBase
{
    // ------------------------------------------------------------------
    // mod_acl tools
    // ------------------------------------------------------------------

    /**
     * Lists ACL groups with optional filters
     */
    public function listAclGroups(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_acl');
        $model->select('id, name, user_id');
        $this->applyAclFilters($model, $args);
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_acl');
        $countModel->select('id');
        $this->applyAclFilters($countModel, $args);
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
     * Returns a single ACL group with all fields and the decoded group list
     */
    public function getAclGroup(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadAclGroupById($id);

        $row = $model->toArray();
        if (!is_array($row) || !isset($row['id'])) {
            throw new McpToolException("ACL group record '{$id}' not found.");
        }
        $row['groups'] = $this->decodeAclGroups((string) ($row['acl'] ?? ''));

        return ['item' => $row];
    }

    /**
     * Creates an ACL group (mirrors the admin add validator)
     */
    public function createAclGroup(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            throw new McpToolException("Field 'name' is required (2-255 characters).");
        }

        $groups = $this->resolveGroupsArgument($args);

        $model = $this->freshModel('mod_acl');
        $model->name = $name;
        $model->acl = serialize($groups);
        $model->user_id = $this->actingUserId;

        if (!$model->save(true)) {
            throw new McpToolException('Failed to create the ACL group record.');
        }

        return [
            'created' => true,
            'id' => (int) $model->id,
            'item' => $this->getAclGroup(['id' => (int) $model->id])['item']
        ];
    }

    /**
     * Partially updates an ACL group and invalidates its permission cache
     */
    public function updateAclGroup(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadAclGroupById($id);

        $this->assertModerateEdit('mod_acl', (int) $model->user_id);

        if (isset($args['name'])) {
            $name = trim((string) $args['name']);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
                throw new McpToolException("Field 'name' must be 2-255 characters.");
            }
            $model->name = $name;
        }

        if (array_key_exists('groups', $args)) {
            $model->acl = serialize($this->resolveGroupsArgument($args));
        }

        if (!$model->update('id')) {
            throw new McpToolException('Failed to update the ACL group record.');
        }

        $this->clearPermissionCache($id);

        return [
            'updated' => true,
            'id' => $id,
            'item' => $this->getAclGroup(['id' => $id])['item']
        ];
    }

    /**
     * Deletes an ACL group with admin parity ownership check
     */
    public function deleteAclGroup(array $args): array
    {
        $id = $this->requireId($args);
        $model = $this->loadAclGroupById($id);

        $this->assertModerateDelete('mod_acl', (int) $model->user_id);

        $model->delete();

        return ['deleted' => true, 'id' => $id];
    }

    // ------------------------------------------------------------------
    // ACL helpers
    // ------------------------------------------------------------------

    /**
     * Validates and normalizes the 'groups' argument (user group IDs, 0 = guest)
     *
     * @return array<string> Group IDs as strings, mirroring the admin form payload
     */
    private function resolveGroupsArgument(array $args): array
    {
        $groups = $args['groups'] ?? [];
        if (!is_array($groups)) {
            throw new McpToolException("Field 'groups' must be an array of user group IDs.");
        }

        $normalized = [];
        foreach ($groups as $groupId) {
            if (is_int($groupId) || (is_string($groupId) && preg_match('/^\d+$/', $groupId) === 1)) {
                $groupId = (int) $groupId;
            } else {
                throw new McpToolException("Field 'groups' must contain only integer user group IDs.");
            }
            if ($groupId < 0) {
                throw new McpToolException("Field 'groups' cannot contain negative user group IDs.");
            }
            if ($groupId > 0) {
                $groupModel = $this->freshModel('mod_user_group');
                if (!$groupModel->exists_group_id($groupId)) {
                    throw new McpToolException("Field 'groups' references unknown user group '{$groupId}'.");
                }
            }
            $normalized[] = (string) $groupId;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Decodes the serialized ACL column into a list of group IDs
     *
     * @return array<int>
     */
    private function decodeAclGroups(string $acl): array
    {
        if ($acl === '') {
            return [];
        }
        $decoded = @unserialize($acl);
        if (!is_array($decoded)) {
            return [];
        }
        $groups = [];
        foreach ($decoded as $groupId) {
            if (is_numeric($groupId)) {
                $groups[] = (int) $groupId;
            }
        }
        return $groups;
    }

    /**
     * Invalidates the cached permission group list for an ACL record (admin edit parity)
     */
    private function clearPermissionCache(int $id): void
    {
        if (Cache::exists('permission-acl-' . $id)) {
            Cache::delete('permission-acl-' . $id);
        }
    }

    private function loadAclGroupById(int $id): mod_acl_model
    {
        $model = $this->freshModel('mod_acl');
        $model->where_id($id)->limit(1)->getResultOne(true, true);
        if ($model->getSize() === 0) {
            throw new McpToolException("ACL group record '{$id}' not found.");
        }
        $model->sync();
        return $model;
    }

    private function applyAclFilters($model, array $args): void
    {
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $model->where('name LIKE \'%' . Obuilder::cc(trim((string) $args['search'])) . '%\'');
        }
    }
}
