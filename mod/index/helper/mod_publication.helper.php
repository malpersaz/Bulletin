<?php

class mod_publication_helper extends indexBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function _categories()
    {
        if (!$this->isFeatureReady('publication.front')) {
            $this->tema->ctx_set('categories', []);
            return;
        }

        $category_model = Load::model('mod_publication_category');
        $category_model->where_lang_and_root($this->site['lang'], 'n');
        $category_model->order('order', 'ASC')->gR();

        $categories = $category_model->toArray();
        $publication_model = Load::model('mod_publication.publications');

        if (is_array($categories)) {
            foreach ($categories as &$category) {
                $publication_model->flush();
                $category['publication_count'] = $publication_model
                    ->where_category_seo_and_status_and_lang($category['seo'], 'y', $this->site['lang'])
                    ->getCount();
            }
        }

        $this->tema->ctx_set('categories', $categories);
    }

    public function publication($seo)
    {
        if (!$this->guardFeatureOr404('publication.front')) {
            return;
        }

        $this->_categories();
        $seo = trim(html_entity_decode($seo, ENT_QUOTES, 'UTF-8'));
        $publication = Load::model('mod_publication.publications');
        $publication->where_seo_and_status_and_lang($seo, 'y', $this->site['lang']);
        $publication->limit(1);
        $publication->gRo();

        if ($publication->getSize() === 0) {
            return $this->_404();
        }

        $publication->sync();

        if ($this->_permission_kontrol($publication->acl_id)) {
            return;
        }

        $this->_update_view_count('mod_publication', $publication->id);

        if (isset($publication->video) && $publication->video != '') {
            $this->_mod_video($publication->video);
        }

        if (isset($publication->gallery_id) && $publication->gallery_id != 0) {
            $this->_exact_gallery($publication->gallery_id);
        } else {
            $this->_gallery_resimgetir('mod_publication', $publication->id);
        }

        if (isset($publication->file_id) && $publication->file_id != 0) {
            $this->_exact_attachment($publication->file_id);
        } else {
            $this->_mod_attachment('mod_publication', $publication->id);
        }

        $this->tema->ctx_set('d', $publication->toArray());
        $this->tema->tpl('index/mod_publication/publication');
        $this->_index();
        unset($publication);
    }

    public function publications($category = NULL, $page = 1)
    {
        if (!$this->guardFeatureOr404('publication.front')) {
            return;
        }

        $this->_categories();

        if ($page == '' || $page == NULL) {
            $page = 1;
        }

        $page = intval($page);
        $page_limit = 12;
        $pager_limit = 5;
        $offset = ($page - 1) * $page_limit;

        $publication = Load::model('mod_publication.publications');
        $category_model = Load::model('mod_publication_category');

        if ($category == NULL || $category == 'all') {
            $publication->where_status_and_lang('y', $this->site['lang'])->all();
            $total_item = $publication->getSize();
            $publication->where_status_and_lang('y', $this->site['lang']);
            $publication->limit($page_limit, $offset)->order('created_at', 'DESC')->gR();

            if ($publication->getSize() != 0) {
                $this->tema->ctx_set('publications', $publication->toArray());
            }

            $category_seo = 'all';
            $category_name = $this->lang['index']['mod_publication']['2'];
        } elseif ($category_model->exists_seo($category) === FALSE) {
            return $this->_404();
        } else {
            $publication->where_category_seo_and_status_and_lang($category, 'y', $this->site['lang']);
            $publication->order('created_at', 'DESC')->gR();
            $total_item = $publication->getSize();
            $publication->getLimit($page_limit, $offset);

            if ($publication->getSize() !== 0) {
                $this->tema->ctx_set('publications', $publication->toArray());
            }

            $category_model->where_seo($category);
            $category_model->getResultOne();
            $category_model->sync();

            $category_name = $category_model->name;
            $category_seo = $category_model->seo;
        }

        $pager = Load::lib('Pager');
        $pager->total_item = $total_item;
        $pager->page_limit = $page_limit;
        $pager->pager_limit = $pager_limit;
        $pager->page = $page;

        if ($pager->check() === FALSE) {
            return $this->_404();
        }

        if ($pager->Result() !== FALSE) {
            $this->tema->ctx_set('pagination', $pager->Result());
        }

        if ($page != '1') {
            $d['link'] = $this->site['saddress'] . 'publications/' . $category . '/' . $page;
        } else {
            $d['link'] = $this->site['saddress'] . 'publications/' . $category;
        }

        if ($page <= 1) {
            $d['title'] = sprintf($this->lang['index']['mod_publication']['1'], $category_name);
        } else {
            $d['title'] = sprintf($this->lang['index']['mod_publication']['5'], $category_name, $page);
        }

        $d['description'] = $this->lang['index']['mod_publication']['2'];
        $d['keywords'] = $this->lang['index']['mod_publication']['3'];

        $this->tema->ctx_set('d', $d);
        $this->tema->ctx_set('categoryname', $category_name);
        $this->tema->ctx_set('category_seo', $category_seo);
        $this->tema->ctx_set('page', $page);
        $this->tema->tpl('index/mod_publication/publications');
        $this->_index();
    }

    public function _latest_publications($limit, $select)
    {
        if (!$this->isFeatureReady('publication.latest')) {
            return [];
        }

        $publication = Load::model('mod_publication')
            ->select($select)
            ->where_lang_and_status($this->site['lang'], 'y')
            ->limit($limit)
            ->order('created_at', 'DESC');
        $publication->gR();

        if ($publication->getSize() !== 0) {
            return $publication->toArray();
        }

        return [];
    }

    public function _latest_authors($limit)
    {
        if (!$this->isFeatureReady('publication.authors')) {
            return [];
        }

        $publication = Load::model('mod_publication.publications');
        $sql = "SELECT p.*
            FROM publications p
            INNER JOIN (
                SELECT mod_publication.user_id, MAX(mod_publication.created_at) AS latest_created_at
                FROM mod_publication
                LEFT JOIN mod_user ON mod_user.id = mod_publication.user_id
                WHERE mod_publication.status = 'y'
                  AND mod_publication.lang = '" . $this->site['lang'] . "'
                  AND mod_user.group_id = '4'
                GROUP BY mod_publication.user_id
            ) latest
                ON p.user_id = latest.user_id
               AND p.created_at = latest.latest_created_at
            WHERE p.status = 'y'
              AND p.lang = '" . $this->site['lang'] . "'
            ORDER BY p.created_at DESC
            LIMIT " . intval($limit);
        $publication->fetch_array($sql);

        if ($publication->getSize() !== 0) {
            $items = $publication->toArray();
            if (is_array($items)) {
                foreach ($items as &$item) {
                    $item['published_ago'] = $this->formatRelativeTime($item['created_at'] ?? '');
                }
                unset($item);
            }
            return $items;
        }

        return [];
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

    public function authors($page = 1, $page_limit = 12)
    {
        if (!$this->guardFeatureOr404('publication.authors')) {
            return;
        }

        if ($page == '' || $page == null) {
            $page = 1;
        }

        $page = intval($page);
        $page_limit = intval($page_limit);
        $pager_limit = 5;
        $offset = ($page - 1) * $page_limit;

        $authors = Load::model('mod_publication.publications');
        $count_sql = "SELECT COUNT(*) AS total_count
            FROM (
                SELECT latest_publications.user_id
                FROM publications AS latest_publications
                INNER JOIN (
                    SELECT mod_publication.user_id, MAX(mod_publication.created_at) AS latest_created_at
                    FROM mod_publication
                    LEFT JOIN mod_user ON mod_user.id = mod_publication.user_id
                    WHERE mod_publication.status = 'y'
                      AND mod_publication.lang = '" . $this->site['lang'] . "'
                      AND mod_user.group_id = '4'
                    GROUP BY mod_publication.user_id
                ) author_latest
                    ON latest_publications.user_id = author_latest.user_id
                   AND latest_publications.created_at = author_latest.latest_created_at
                WHERE latest_publications.status = 'y'
                  AND latest_publications.lang = '" . $this->site['lang'] . "'
            ) AS paged_authors";
        $authors->fetch_array($count_sql);
        $count_rows = $authors->toArray();
        $total_item = isset($count_rows[0]['total_count']) ? intval($count_rows[0]['total_count']) : 0;

        $authors->flush();
        $sql = "SELECT latest_publications.*
            FROM publications AS latest_publications
            INNER JOIN (
                SELECT mod_publication.user_id, MAX(mod_publication.created_at) AS latest_created_at
                FROM mod_publication
                LEFT JOIN mod_user ON mod_user.id = mod_publication.user_id
                WHERE mod_publication.status = 'y'
                  AND mod_publication.lang = '" . $this->site['lang'] . "'
                  AND mod_user.group_id = '4'
                GROUP BY mod_publication.user_id
            ) author_latest
                ON latest_publications.user_id = author_latest.user_id
               AND latest_publications.created_at = author_latest.latest_created_at
            WHERE latest_publications.status = 'y'
              AND latest_publications.lang = '" . $this->site['lang'] . "'
            ORDER BY latest_publications.created_at DESC
            LIMIT " . $page_limit . " OFFSET " . $offset;
        $authors->fetch_array($sql);

        if ($authors->getSize() !== 0) {
            $this->tema->ctx_set('authors', $authors->toArray());
        }

        $pager = Load::lib('Pager');
        $pager->total_item = $total_item;
        $pager->page_limit = $page_limit;
        $pager->pager_limit = $pager_limit;
        $pager->page = $page;

        if ($pager->check() === FALSE) {
            return $this->_404();
        }

        if ($pager->Result() !== FALSE) {
            $this->tema->ctx_set('pagination', $pager->Result());
        }

        $d['name'] = $this->lang['index']['mod_publication']['authors'];
        $d['title'] = $d['name'];
        $d['keywords'] = $d['name'];
        $d['description'] = $d['name'];
        $this->tema->ctx_set('d', $d);
        $this->tema->ctx_set('page', $page);

        $this->tema->tpl('index/mod_publication/authors');
        $this->_index();
    }

    public function author($username = null, $page = 1)
    {
        if (!$this->guardFeatureOr404('publication.authors')) {
            return;
        }

        if ($username == null || $username === '') {
            return $this->_404();
        }

        if ($page == '' || $page == null) {
            $page = 1;
        }

        $page = intval($page);
        $page_limit = 20;
        $pager_limit = 4;
        $offset = ($page - 1) * $page_limit;

        $author = Load::model('mod_publication.publications');
        $author_sql = "SELECT p.*
            FROM publications p
            WHERE p.username = '" . addslashes($username) . "'
              AND p.status = 'y'
              AND p.lang = '" . $this->site['lang'] . "'
            ORDER BY p.created_at DESC
            LIMIT 1";
        $author->fetch_array($author_sql);

        if ($author->getSize() === 0) {
            return $this->_404();
        }

        $author_data = $author->toArray()[0];
        $social_data = $this->loadAuthorSocialLinks($author_data['user_id'] ?? 0);
        $author_data['twitter'] = $social_data['map']['twitter'];
        $author_data['facebook'] = $social_data['map']['facebook'];
        $this->tema->ctx_set('author', $author_data);
        $this->tema->ctx_set('author_social_links', $social_data['items']);
        $this->tema->ctx_set('username', $username);

        $publication = Load::model('mod_publication.publications');
        $count_sql = "SELECT COUNT(*) AS total_count
            FROM publications
            WHERE username = '" . addslashes($username) . "'
              AND status = 'y'
              AND lang = '" . $this->site['lang'] . "'";
        $publication->fetch_array($count_sql);
        $total_rows = $publication->toArray();
        $total_item = isset($total_rows[0]['total_count']) ? intval($total_rows[0]['total_count']) : 0;

        $publication_sql = "SELECT *
            FROM publications
            WHERE username = '" . addslashes($username) . "'
              AND status = 'y'
              AND lang = '" . $this->site['lang'] . "'
            ORDER BY created_at DESC
            LIMIT " . intval($page_limit) . " OFFSET " . intval($offset);
        $publication->fetch_array($publication_sql);

        if ($publication->getSize() !== 0) {
            $this->tema->ctx_set('publications', $publication->toArray());
        }

        $pager = Load::lib('Pager');
        $pager->total_item = $total_item;
        $pager->page_limit = $page_limit;
        $pager->pager_limit = $pager_limit;
        $pager->page = $page;

        if ($pager->check() === FALSE) {
            return $this->_404();
        }

        if ($pager->Result() !== FALSE) {
            $this->tema->ctx_set('pagination', $pager->Result());
        }

        $this->tema->ctx_set('page', $page);

        $d['name'] = trim(($author_data['u_name'] ?? '') . ' ' . ($author_data['u_last_name'] ?? ''));
        $d['title'] = $d['name'];
        $d['keywords'] = $d['name'];
        $d['description'] = $d['name'];
        $this->tema->ctx_set('d', $d);

        $sidebar_authors = $this->_latest_authors(3);
        if (!empty($sidebar_authors)) {
            $this->tema->ctx_set('authors', $sidebar_authors);
        }

        $this->tema->tpl('index/mod_publication/author');
        $this->_index();
    }

    private function loadAuthorSocialLinks($userId)
    {
        $payload = [
            'map' => [
                'twitter' => '',
                'facebook' => '',
                'instagram' => '',
                'linkedin' => '',
                'youtube' => '',
                'spotify' => '',
                'tiktok' => '',
            ],
            'items' => [],
        ];

        if (intval($userId) === 0) {
            return $payload;
        }

        $platformLabels = [
            'twitter' => 'X / Twitter',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'linkedin' => 'LinkedIn',
            'youtube' => 'YouTube',
            'spotify' => 'Spotify',
            'tiktok' => 'TikTok',
        ];

        $social = Load::model('mod_user.mod_user_social');
        $social->flush();
        $social->where_user_id_and_is_active(intval($userId), 'y')->order('display_order', 'ASC')->gR();

        if ($social->getSize() === 0) {
            return $payload;
        }

        foreach ($social->toArray() as $row) {
            if (!isset($platformLabels[$row['platform']])) {
                continue;
            }

            $payload['map'][$row['platform']] = $row['url'];
            $payload['items'][] = array_merge($row, [
                'label' => $platformLabels[$row['platform']],
            ]);
        }

        return $payload;
    }

    public function archive()
    {
        if (!$this->guardFeatureOr404('publication.archive')) {
            return;
        }

        $publication = Load::model('mod_publication');
        $sql = "SELECT YEAR(MAX(created_at)) AS max_yil, YEAR(MIN(created_at)) AS min_yil FROM `mod_publication` WHERE 1";
        $publication->fetch_array($sql);
        $publication->getResultOne();
        $this->tema->ctx_set('arsiv_yil', $publication->toArray());

        $category = Load::model('mod_publication_category');
        $category->where_lang_and_root($this->site['lang'], 'n');
        $category->order('parent_id, order', 'DESC');
        $category->gR();
        if ($category->getSize() !== 0) {
            $this->tema->ctx_set('archive_categories', $category->toArray());
        }

        $d['name'] = $this->lang['index']['mod_publication']['6'];
        $d['title'] = $this->lang['index']['mod_publication']['6'];
        $d['keywords'] = $this->lang['index']['mod_publication']['6'];
        $d['description'] = $this->lang['index']['mod_publication']['6'];
        $this->tema->ctx_set('d', $d);

        if (Req::is_Post('category_id') and Req::is_Post('yil') and Req::is_Post('ay')) {
            $yil = Req::Post_Safe('yil');
            $ay = Req::Post_Safe('ay');
            $category_id = Req::Post_Safe('category_id');

            if ($yil === '*') {
                $yil = NULL;
            }

            if ($ay === '*') {
                $ay = NULL;
            }

            $category_where = $category_id === '*' ? '' : " AND category_id='" . $category_id . "'";
            $this->tema->ctx_set('category_id', $category_id);

            $publication->select('seo,name,created_at,summary');
            if ($yil !== NULL && $ay !== NULL) {
                $publication->where(
                    "YEAR(created_at)='" . $yil . "' AND
                    MONTH(created_at)='" . $ay . "' AND
                    lang='" . $this->site['lang'] . "' AND
                    status='y'" . $category_where
                );
            } elseif ($yil !== NULL) {
                $publication->where(
                    "YEAR(created_at)='" . $yil . "' AND
                    lang='" . $this->site['lang'] . "' AND
                    status='y'" . $category_where
                );
            } elseif ($ay !== NULL) {
                $publication->where(
                    "MONTH(created_at)='" . $ay . "' AND
                    lang='" . $this->site['lang'] . "' AND
                    status='y'" . $category_where
                );
            } else {
                $publication->where("status='y' AND lang='" . $this->site['lang'] . "'" . $category_where);
            }

            $publication->gR();
            if ($publication->getSize() !== 0) {
                $this->tema->ctx_set('publications', $publication->toArray());
            } else {
                $this->tema->ctx_set('bulunamadi', TRUE);
            }

            if ($yil !== NULL) {
                $this->tema->ctx_set('yil', $yil);
            }
            if ($ay !== NULL) {
                $this->tema->ctx_set('ay', $ay);
            }
        }

        $this->tema->tpl('index/mod_publication/archive');
        $this->_index();
    }
}
