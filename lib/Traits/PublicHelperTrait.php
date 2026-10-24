<?php

/**
 * PublicHelperTrait - Shared helper trait for public controllers and helpers
 * 
 * Provides:
 * - Cached getAllowedAclCondition() for ACL permission SQL filtering
 * - Recursive getCategoryDescendantIds()
 * - Strict single-language lookup (no cross-language fallback)
 * - Safe deserialization with allowed_classes disabled
 */
trait PublicHelperTrait
{
    /**
     * Resolves and caches the list of ACL IDs accessible to the current session user.
     *
     * @return int[]
     */
    protected function getAllowedAclIds(): array
    {
        static $cachedAllowedAclIds = null;
        if ($cachedAllowedAclIds === null) {
            $session = Load::lib('Session');
            $groupId = $session->get('group_id') !== false ? (int) $session->get('group_id') : 0;
            $allowedAclIds = [0];

            if ($groupId > 0) {
                $db = Load::lib('Database');
                $db->fetch_array('SELECT `id`, `acl` FROM `mod_acl`');
                $aclRows = $db->toArray();
                if (is_array($aclRows)) {
                    foreach ($aclRows as $row) {
                        $groups = $this->safeUnserialize($row['acl'] ?? null, []);
                        if (is_array($groups) && in_array($groupId, $groups)) {
                            $allowedAclIds[] = (int) $row['id'];
                        }
                    }
                }
            }

            $cachedAllowedAclIds = array_values(array_unique(array_map('intval', $allowedAclIds)));
        }

        return $cachedAllowedAclIds;
    }

    /**
     * Loads and caches the lightweight category hierarchy map (id, parent_id, acl_id) per table and language
     * without mutating any ORM model singleton state.
     *
     * @param string $categoryModelName
     * @param string $lang
     * @return array<int, array{id: int, parent_id: int, acl_id: int}>
     */
    protected function getCategoryHierarchyMap(string $categoryModelName = 'mod_news_category', string $lang = ''): array
    {
        static $hierarchyCache = [];
        $cacheKey = $categoryModelName . ':' . $lang;
        if (isset($hierarchyCache[$cacheKey])) {
            return $hierarchyCache[$cacheKey];
        }

        $catModel = Load::model($categoryModelName);
        $table = !empty($catModel->_table) ? $catModel->_table : $categoryModelName;
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);

        $sql = "SELECT `id`, `parent_id`, `acl_id` FROM `{$safeTable}`";
        if ($lang !== '') {
            $safeLang = addslashes($lang);
            $sql .= " WHERE `lang` = '{$safeLang}'";
        }

        $db = Load::lib('Database');
        $db->fetch_array($sql);
        $rows = $db->toArray();

        $byId = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id > 0) {
                    $byId[$id] = [
                        'id' => $id,
                        'parent_id' => (int) ($row['parent_id'] ?? 0),
                        'acl_id' => (int) ($row['acl_id'] ?? 0),
                    ];
                }
            }
        }

        $hierarchyCache[$cacheKey] = $byId;
        return $byId;
    }

    /**
     * Builds and caches the SQL condition for records the current user is allowed to see.
     *
     * @param string $tablePrefix Optional alias prefix (e.g. 's' -> 's.acl_id IN (...)')
     * @param string $columnName Optional column name (defaults to 'acl_id')
     * @return string
     */
    public function getAllowedAclCondition(string $tablePrefix = 's', string $columnName = 'acl_id'): string
    {
        $idList = implode(',', $this->getAllowedAclIds());
        $prefix = $tablePrefix !== '' ? $tablePrefix . '.' : '';
        return "{$prefix}{$columnName} IN ({$idList})";
    }

    /**
     * Retrieves all allowed category IDs taking full ancestor ACL hierarchy into account.
     *
     * @param string $categoryModelName
     * @param string $lang
     * @param bool $anonymousOnly If true, only categories where acl_id is 0 and all ancestors have acl_id 0 are allowed.
     * @return int[]
     */
    public function getAllowedCategoryIds(string $categoryModelName = 'mod_news_category', string $lang = '', bool $anonymousOnly = false): array
    {
        static $allowedCategoryCache = [];
        $cacheKey = $categoryModelName . ':' . $lang . ':' . ($anonymousOnly ? '1' : '0');
        if (isset($allowedCategoryCache[$cacheKey])) {
            return $allowedCategoryCache[$cacheKey];
        }

        $byId = $this->getCategoryHierarchyMap($categoryModelName, $lang);
        if (empty($byId)) {
            return $allowedCategoryCache[$cacheKey] = [];
        }

        $allowedAclIds = $anonymousOnly ? [0] : $this->getAllowedAclIds();
        $allowedAclMap = array_fill_keys(array_map('intval', $allowedAclIds), true);

        $cache = [];
        $isAllowed = function (int $id) use (&$isAllowed, &$byId, $allowedAclMap, &$cache): bool {
            if (isset($cache[$id])) {
                return $cache[$id];
            }
            if (!isset($byId[$id])) {
                return $cache[$id] = false;
            }
            $cat = $byId[$id];
            if (!isset($allowedAclMap[$cat['acl_id']])) {
                return $cache[$id] = false;
            }
            if ($cat['parent_id'] > 0 && isset($byId[$cat['parent_id']])) {
                if (!$isAllowed($cat['parent_id'])) {
                    return $cache[$id] = false;
                }
            }
            return $cache[$id] = true;
        };

        $allowedIds = [];
        foreach ($byId as $id => $data) {
            if ($isAllowed($id)) {
                $allowedIds[] = $id;
            }
        }

        return $allowedCategoryCache[$cacheKey] = $allowedIds;
    }

    /**
     * Recursively retrieves all descendant category IDs for a given parent within a language.
     *
     * @param int $categoryId
     * @param string $lang
     * @param string $categoryModelName
     * @return array
     */
    public function getCategoryDescendantIds(int $categoryId, string $lang, string $categoryModelName = 'mod_news_category'): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        $byId = $this->getCategoryHierarchyMap($categoryModelName, $lang);
        $childrenByParent = [];
        foreach ($byId as $id => $node) {
            $parentId = $node['parent_id'];
            $childrenByParent[$parentId][] = $id;
        }

        $categoryIds = [$categoryId];
        $queue = [$categoryId];

        while (!empty($queue)) {
            $currentParentId = array_shift($queue);
            if (!empty($childrenByParent[$currentParentId])) {
                foreach ($childrenByParent[$currentParentId] as $childId) {
                    if ($childId > 0 && !in_array($childId, $categoryIds, true)) {
                        $categoryIds[] = $childId;
                        $queue[] = $childId;
                    }
                }
            }
        }

        return $categoryIds;
    }

    /**
     * Look up a model record strictly by SEO slug or ID without cross-language fallback.
     *
     * @param string $modelName
     * @param string $rawSeo
     * @param string $lang
     * @param string $status
     * @return object|null Loaded model instance or null if not found
     */
    public function findItemBySeoOrIdStrict(string $modelName, string $rawSeo, string $lang, string $status = 'y')
    {
        $rawSeo = trim(html_entity_decode($rawSeo, ENT_QUOTES, 'UTF-8'));
        $rawSeo = ltrim($rawSeo, '/');

        $candidates = [$rawSeo];
        $cleanSeo = $rawSeo;
        if (strpos($cleanSeo, 'detail/') === 0) {
            $cleanSeo = substr($cleanSeo, 7);
            $candidates[] = $cleanSeo;
        }

        $idCandidate = null;
        if (preg_match('/^(\d+)-(.*)$/', $cleanSeo, $matches)) {
            $idCandidate = (int) $matches[1];
            $candidates[] = $matches[2];
        } elseif (is_numeric($cleanSeo)) {
            $idCandidate = (int) $cleanSeo;
        }

        $decodedSeo = urldecode($cleanSeo);
        if ($decodedSeo !== $cleanSeo) {
            $candidates[] = $decodedSeo;
        }

        $candidates = array_values(array_unique(array_filter($candidates)));
        $model = Load::model($modelName);

        foreach ($candidates as $candidate) {
            $model->flush();
            $model->where_seo_and_status_and_lang($candidate, $status, $lang)->limit(1)->gRo();
            if ($model->getSize() > 0) {
                return $model;
            }
        }

        if ($idCandidate !== null && $idCandidate > 0) {
            $model->flush();
            $model->where_id_and_status_and_lang($idCandidate, $status, $lang)->limit(1)->gRo();
            if ($model->getSize() > 0) {
                return $model;
            }
        }

        return null;
    }

    /**
     * Safely unserialize PHP data without allowing arbitrary class instantiation.
     *
     * @param mixed $serialized
     * @param mixed $default
     * @return mixed
     */
    public function safeUnserialize($serialized, $default = [])
    {
        if (!is_string($serialized) || $serialized === '') {
            return $default;
        }
        $data = @unserialize($serialized, ['allowed_classes' => false]);
        return ($data !== false || $serialized === 'b:0;') ? $data : $default;
    }
}
