<?php

class mod_hero_slider_helper extends indexBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function hero_slider($limit = 6)
    {
        $limit = max(1, (int) $limit);
        if (!$this->hasSliderColumn('mod_news') && !$this->hasSliderColumn('mod_publication')) {
            return [];
        }

        $items = array_merge(
            $this->loadNewsItems(),
            $this->loadPublicationItems()
        );

        if (empty($items)) {
            return [];
        }

        usort($items, function ($left, $right) {
            return strtotime($right['created_at']) <=> strtotime($left['created_at']);
        });

        return array_slice($items, 0, $limit);
    }

    private function loadNewsItems()
    {
        if (!$this->hasSliderColumn('mod_news')) {
            return [];
        }

        $news_model = Load::model('mod_news.newses');
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
        $news_model->addWhere("status='y' AND slider='y' AND lang='" . Req::Clear($this->site['lang']) . "'");
        $news_model->order('created_at', 'DESC');
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

    private function loadPublicationItems()
    {
        if (!$this->hasSliderColumn('mod_publication')) {
            return [];
        }

        $publication_model = Load::model('mod_publication.publications');
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
        $publication_model->addWhere("status='y' AND slider='y' AND lang='" . Req::Clear($this->site['lang']) . "'");
        $publication_model->order('created_at', 'DESC');
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

        $detail_path = $source_mod === 'mod_news' ? 'news/' : 'publication/';
        $category_path = $source_mod === 'mod_news' ? 'newses/' : 'publications/';
        $image_base = $source_mod === 'mod_news' ? 'mod_news/' : 'mod_publication/';
        $thumb_base = $source_mod === 'mod_news' ? 'mod_news/thumb/' : 'mod_publication/thumb/';
        $image = isset($record['image']) ? (string) $record['image'] : '';
        $category_seo = isset($record['category_seo']) ? (string) $record['category_seo'] : '';
        $created_at = isset($record['created_at']) ? (string) $record['created_at'] : '';

        return [
            'source_mod' => $source_mod,
            'source_id' => $source_id,
            'name' => isset($record['name']) ? (string) $record['name'] : '',
            'seo' => isset($record['seo']) ? (string) $record['seo'] : '',
            'summary' => isset($record['summary']) ? (string) $record['summary'] : '',
            'lang' => isset($record['lang']) ? (string) $record['lang'] : $this->site['lang'],
            'created_at' => $created_at,
            'published_ago' => $this->formatRelativeTime($created_at),
            'category_name' => $category_name,
            'category_seo' => $category_seo,
            'category_url' => $category_seo !== '' ? $this->site['sadres'] . $category_path . $category_seo : '',
            'detail_url' => $this->site['sadres'] . $detail_path . (isset($record['seo']) ? $record['seo'] : ''),
            'image' => $image,
            'image_url' => $image !== '' ? $this->site['radres'] . $image_base . $image : '',
            'thumb_url' => $image !== '' ? $this->site['radres'] . $thumb_base . $image : '',
            'author_name' => $author_name,
            'author_initials' => $this->buildAuthorInitials($author_name),
            'user_image' => $user_image,
            'author_image_url' => $user_image !== '' ? $this->site['radres'] . 'mod_user/thumb/' . $user_image : '',
        ];
    }

    private function formatRelativeTime($datetime)
    {
        if (empty($datetime)) {
            return '';
        }

        try {
            $published_at = new DateTime($datetime);
            $now = new DateTime('now');
            $diff = $published_at->diff($now);
        } catch (Exception $e) {
            return '';
        }

        if ($diff->y > 0) {
            return $diff->y . ' ' . ($diff->y === 1 ? 'year ago' : 'years ago');
        }
        if ($diff->m > 0) {
            return $diff->m . ' ' . ($diff->m === 1 ? 'month ago' : 'months ago');
        }
        if ($diff->d > 0) {
            return $diff->d . ' ' . ($diff->d === 1 ? 'day ago' : 'days ago');
        }
        if ($diff->h > 0) {
            return $diff->h . ' ' . ($diff->h === 1 ? 'hour ago' : 'hours ago');
        }
        if ($diff->i > 0) {
            return $diff->i . ' ' . ($diff->i === 1 ? 'minute ago' : 'minutes ago');
        }

        return 'Just now';
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
        $db = Load::lib('Database');
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
