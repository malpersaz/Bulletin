<?php

class mod_hero_slider_helper extends indexBase
{
    private function hasConfiguredDatabase()
    {
        $database = Registry::get('database');
        $dbConfig = Registry::get('db_config');

        if (!is_string($database) || $database === '') {
            return false;
        }

        if (!is_array($dbConfig) || !isset($dbConfig[$database]) || !is_array($dbConfig[$database])) {
            return false;
        }

        return isset($dbConfig[$database]['adapter'], $dbConfig[$database]['conf']) && is_array($dbConfig[$database]['conf']);
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function hero_slider($limit = 6)
    {
        if (!$this->hasConfiguredDatabase()) {
            return [];
        }

        $limit = max(1, (int) $limit);

        $items = array_merge(
            $this->loadNewsItems($limit),
            $this->loadPublicationItems($limit)
        );

        if (empty($items)) {
            return [];
        }

        usort($items, function ($left, $right) {
            return strtotime($right['created_at']) <=> strtotime($left['created_at']);
        });

        return array_slice($items, 0, $limit);
    }

    private function loadNewsItems(int $limit)
    {
        if (!$this->isFeatureReady('news.front')) {
            return [];
        }

        if (!$this->hasSliderColumn('mod_news')) {
            return [];
        }

        $currentLang = strtolower(Req::Clear($this->site['lang']));
        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $currentLang);
        $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';
        $aclCondition = $this->getAllowedAclCondition('');

        $news_model = Load::model('mod_news.newses');
        $news_model->flush();
        $news_model->select([
            'id',
            'name',
            'seo',
            'image',
            'summary',
            'created_at',
            'slider',
            'category_name',
            'category_seo',
            'user_id',
            'u_name',
            'u_last_name',
            'username',
            'lang',
        ]);
        $news_model->addWhere("status='y' AND slider='y' AND LOWER(lang)='" . $currentLang . "'");
        $news_model->addWhere($aclCondition);
        $news_model->addWhere("category_id IN ({$allowedCatList})");
        $news_model->order('created_at', 'DESC');
        $news_model->limit($limit);
        $news_model->gR();

        $records = $news_model->toArray();
        if (!is_array($records) || empty($records)) {
            return [];
        }

        $user_image_map = $this->loadUserImageMap($records);
        $items = [];

        foreach ($records as $record) {
            $items[] = $this->normalizeItem(
                'mod_news',
                $record,
                isset($user_image_map[(int) $record['user_id']]) ? $user_image_map[(int) $record['user_id']] : ''
            );
        }

        return array_values(array_filter($items));
    }

    private function loadPublicationItems(int $limit)
    {
        if (!$this->isFeatureReady('publication.front')) {
            return [];
        }

        if (!$this->hasSliderColumn('mod_publication')) {
            return [];
        }

        $currentLang = strtolower(Req::Clear($this->site['lang']));
        $allowedCatIds = $this->getAllowedCategoryIds('mod_publication_category', $currentLang);
        $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';
        $aclCondition = $this->getAllowedAclCondition('');

        $publication_model = Load::model('mod_publication.publications');
        $publication_model->flush();
        $publication_model->select([
            'id',
            'name',
            'seo',
            'image',
            'summary',
            'created_at',
            'slider',
            'category_name',
            'category_seo',
            'user_id',
            'u_name',
            'u_last_name',
            'username',
            'user_image',
            'lang',
        ]);
        $publication_model->addWhere("status='y' AND slider='y' AND LOWER(lang)='" . $currentLang . "'");
        $publication_model->addWhere($aclCondition);
        $publication_model->addWhere("category_id IN ({$allowedCatList})");
        $publication_model->order('created_at', 'DESC');
        $publication_model->limit($limit);
        $publication_model->gR();

        $records = $publication_model->toArray();
        if (!is_array($records) || empty($records)) {
            return [];
        }

        $items = [];
        foreach ($records as $record) {
            $items[] = $this->normalizeItem(
                'mod_publication',
                $record,
                isset($record['user_image']) ? (string) $record['user_image'] : ''
            );
        }

        return array_values(array_filter($items));
    }


    private function loadUserImageMap(array $records)
    {
        $user_ids = [];
        foreach ($records as $record) {
            $user_id = isset($record['user_id']) ? (int) $record['user_id'] : 0;
            if ($user_id > 0) {
                $user_ids[] = $user_id;
            }
        }

        if (empty($user_ids)) {
            return [];
        }

        $user_model = Load::model('mod_user.user');
        $user_model->flush();
        $user_model->select('id, image');
        $user_model->addWhere("id IN (" . $this->buildIdList($user_ids) . ")");
        $user_model->gR();

        $user_map = [];
        $users = $user_model->toArray();
        if (is_array($users)) {
            foreach ($users as $user) {
                $user_map[(int) $user['id']] = isset($user['image']) ? (string) $user['image'] : '';
            }
        }

        return $user_map;
    }

    private function normalizeItem($source_mod, array $record, $user_image)
    {
        $source_id = isset($record['id']) ? (int) $record['id'] : 0;
        if ($source_id <= 0) {
            return null;
        }

        $category_name = '';
        if (isset($record['category_name']) && $record['category_name'] !== '') {
            $category_name = (string) $record['category_name'];
        } elseif (isset($record['categoryname']) && $record['categoryname'] !== '') {
            $category_name = (string) $record['categoryname'];
        }

        $author_name = trim(
            (isset($record['u_name']) ? $record['u_name'] : '') . ' ' .
            (isset($record['u_last_name']) ? $record['u_last_name'] : '')
        );
        if ($author_name === '') {
            $author_name = trim(isset($record['username']) ? (string) $record['username'] : '');
        }
        if ($author_name === '') {
            $author_name = 'Staff';
        }

        $detail_path = 'news/';
        $category_path = 'newses/';
        $image_base = 'mod_news/';
        $thumb_base = 'mod_news/thumb/';

        if ($source_mod === 'mod_publication') {
            $detail_path = 'publication/';
            $category_path = 'publications/';
            $image_base = 'mod_publication/';
            $thumb_base = 'mod_publication/thumb/';
        }
        $image = isset($record['image']) ? (string) $record['image'] : '';
        $category_seo = isset($record['category_seo']) ? (string) $record['category_seo'] : '';
        $created_at = isset($record['created_at']) ? (string) $record['created_at'] : '';

        $raw_name = isset($record['name']) ? (string) $record['name'] : '';
        $name = html_entity_decode($raw_name, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $raw_summary = isset($record['summary']) ? (string) $record['summary'] : '';
        $summary = html_entity_decode($raw_summary, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $category_name = html_entity_decode($category_name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $author_name = html_entity_decode($author_name, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return [
            'source_mod' => $source_mod,
            'source_id' => $source_id,
            'name' => $name,
            'seo' => isset($record['seo']) ? (string) $record['seo'] : '',
            'summary' => $summary,
            'lang' => isset($record['lang']) ? (string) $record['lang'] : $this->site['lang'],
            'created_at' => $created_at,
            'published_ago' => $this->formatRelativeTime($created_at),
            'category_name' => $category_name,
            'category_seo' => $category_seo,
            'category_url' => $category_seo !== '' ? $this->site['saddress'] . $category_path . $category_seo : '',
            'detail_url' => $this->site['saddress'] . $detail_path . (isset($record['seo']) ? $record['seo'] : ''),
            'image' => $image,
            'image_url' => $image !== '' ? $this->site['iaddress'] . $image_base . $image : '',
            'thumb_url' => $image !== '' ? $this->site['iaddress'] . $thumb_base . $image : '',
            'author_name' => $author_name,
            'author_initials' => $this->buildAuthorInitials($author_name),
            'user_image' => $user_image,
            'author_image_url' => $user_image !== '' ? $this->site['iaddress'] . 'mod_user/thumb/' . $user_image : '',
        ];
    }

    private function formatRelativeTime($datetime)
    {
        return Helper::time_ago($datetime, $this->lang['index'] ?? null);
    }

    private function buildAuthorInitials($author_name)
    {
        $parts = preg_split('/\s+/', trim($author_name));
        if (!is_array($parts) || empty($parts)) {
            return 'S';
        }

        $first = isset($parts[0][0]) ? $parts[0][0] : '';
        $last = isset($parts[1][0]) ? $parts[1][0] : $first;

        return strtoupper($first . $last);
    }

    private function hasSliderColumn($table)
    {
        if (!$this->hasConfiguredDatabase()) {
            return false;
        }

        $db = Load::lib('Database');
        $table_check = false;
        if ($db->getAdapterType() && strpos($db->getAdapterType(), 'PDO_SQLITE') !== false) {
            $db->fetch_array("SELECT name FROM sqlite_master WHERE type='table' AND name='" . addslashes($table) . "'");
            $result = $db->toArray();
            if (!empty($result)) {
                $table_check = true;
            }
        } else {
            $db->fetch_array("SHOW TABLES LIKE '" . addslashes($table) . "'");
            $result = $db->getResult()->toArray();
            if (!empty($result)) {
                $table_check = true;
            }
        }

        if (!$table_check) {
            return false;
        }

        if ($db->getAdapterType() && strpos($db->getAdapterType(), 'PDO_SQLITE') !== false) {
            $db->fetch_array("PRAGMA table_info('" . addslashes($table) . "')");
            $result = $db->toArray();
            if (is_array($result)) {
                foreach ($result as $row) {
                    if (isset($row['name']) && $row['name'] === 'slider') {
                        return true;
                    }
                }
            }
            return false;
        }

        $db->fetch_array("SHOW COLUMNS FROM `" . $table . "` LIKE 'slider'");
        $result = $db->getResult()->toArray();
        return is_array($result) && !empty($result);
    }

    private function buildIdList(array $ids)
    {
        $clean_ids = array_unique(array_map('intval', $ids));
        $clean_ids = array_filter($clean_ids, function ($id) {
            return $id > 0;
        });

        return empty($clean_ids) ? '0' : implode(',', $clean_ids);
    }
}
