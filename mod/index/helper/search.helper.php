<?php

class search_helper extends indexBase
{

    public function __construct()
    {
        parent::__construct();
    }

    public function search($query = null)
    {
        $url = Load::lib('Url');
        $url->parse($query);

        $keyword = null;

        if ($url->exists_get('query')) {
            $keyword = $url->get_clear('query');
        } elseif ($url->exists_get('keyword')) {
            $keyword = $url->get_clear('keyword');
        } elseif ($url->exists_get('q')) {
            $keyword = $url->get_clear('q');
        } elseif (Req::is_Get('query')) {
            $keyword = Req::Get_Safe('query');
        } elseif (Req::is_Get('q')) {
            $keyword = Req::Get_Safe('q');
        } elseif (Req::is_Get('keyword')) {
            $keyword = Req::Get_Safe('keyword');
        } elseif (Req::is_Post('query')) {
            $keyword = Req::Post_Safe('query');
        } elseif (Req::is_Post('q')) {
            $keyword = Req::Post_Safe('q');
        } elseif (Req::is_Post('keyword')) {
            $keyword = Req::Post_Safe('keyword');
        } elseif (!empty($query) && is_string($query) && strpos($query, '=') === false) {
            $keyword = Req::Url_Clear($query);
        }

        if ($keyword === null || mb_strlen($keyword, 'UTF-8') < 3) {
            $searchLang = Load::lang('index')['index']['search'] ?? [];
            $noticeKey = $keyword === null ? 'searched_word' : 'keyword_too_short';
            if (!empty($searchLang[$noticeKey])) {
                $this->theme->set('search_notice', $searchLang[$noticeKey]);
            }
            $this->theme->set('search_term', '');
            $this->theme->set('keyword', $keyword ?? '');
            $this->theme->set('query', $keyword ?? '');
            $this->theme->set('search_query', $keyword ?? '');
            $this->theme->tpl('index/search');
            $this->_index();
            return;
        }

        $like_keyword = "%" . $keyword . "%";
        Load::libfile('Cache');
        Cache::$ttl = 3600;

        if (Cache::exists($keyword . "-search-mod_authors")) {
            $cached_data = Cache::get($keyword . "-search-mod_authors");
            if ($cached_data !== FALSE && !empty($cached_data)) {
                $this->theme->set('authors', $cached_data);
                $this->theme->ctx_set('authors', $cached_data);
            }
        } else {
            $authors = $this->searchAuthors($keyword);
            if (!empty($authors)) {
                $this->theme->set('authors', $authors);
                $this->theme->ctx_set('authors', $authors);
            }
            Cache::set($authors, $keyword . "-search-mod_authors");
        }

        if (Cache::exists($keyword . "-search-mod_images")) {
            $cached_data = Cache::get($keyword . "-search-mod_images");
            if ($cached_data !== FALSE && !empty($cached_data)) {
                $this->theme->set('images', $cached_data);
                $this->theme->ctx_set('images', $cached_data);
            }
        } else {
            $images = $this->searchImages($keyword);
            if (!empty($images)) {
                $this->theme->set('images', $images);
                $this->theme->ctx_set('images', $images);
            }
            Cache::set($images, $keyword . "-search-mod_images");
        }

        if (Cache::exists($keyword . "-search-mod_news")) {
            $cached_data = Cache::get($keyword . "-search-mod_news");
            if ($cached_data !== FALSE) {
                $this->theme->set('news', $cached_data);
            }
        } else {
            $news = Load::model('mod_news');
            $news->select('summary,name,seo');
            $news->where_lang_and_status_and_og_like_content_or_like_summary_or_like_name_cg($this->site['lang'], 'y', $like_keyword, $like_keyword, $like_keyword);
            $news->gR();
            if ($news->getSize() !== 0) {
                $this->theme->set('news', $news->toArray());
            }
            Cache::set($news->toArray(), $keyword . "-search-mod_news");
        }

        if (Cache::exists($keyword . "-search-mod_publication")) {
            $cached_data = Cache::get($keyword . "-search-mod_publication");
            if ($cached_data !== FALSE) {
                $this->theme->set('publications', $cached_data);
            }
        } else {
            $publication = Load::model('mod_publication');
            $publication->select('summary,name,seo');
            $publication->where_lang_and_status_and_og_like_content_or_like_summary_or_like_name_cg($this->site['lang'], 'y', $like_keyword, $like_keyword, $like_keyword);
            $publication->gR();
            if ($publication->getSize() !== 0) {
                $this->theme->set('publications', $publication->toArray());
            }
            Cache::set($publication->toArray(), $keyword . "-search-mod_publication");
        }

        if (Cache::exists($keyword . "-search-mod_announcements")) {
            $cached_data = Cache::get($keyword . "-search-mod_announcements");
            if ($cached_data !== FALSE) {
                $this->theme->set('announcements', $cached_data);
            }
        } else {
            $announcement = Load::model('mod_announcements');
            $announcement->select('name,id');
            $announcement->where_lang_and_status_and_og_like_content_or_like_summary_or_like_name_cg($this->site['lang'], 'y', $like_keyword, $like_keyword, $like_keyword);
            $announcement->gR();
            if ($announcement->getSize() !== 0) {
                $this->theme->set('announcements', $announcement->toArray());
            }
            Cache::set($announcement->toArray(), $keyword . "-search-mod_announcements");
        }

        if (Cache::exists($keyword . "-search-mod_page")) {
            $cached_data = Cache::get($keyword . "-search-mod_page");
            if ($cached_data !== FALSE) {
                $this->theme->set('sayfalar', $cached_data);
                $this->theme->set('pages', $cached_data);
            }
        } else {
            $page = Load::model('mod_page');
            $page->select('name,seo');
            $page->where_lang_and_status_and_root_and_og_like_content_or_like_summary_or_like_name_cg($this->site['lang'], 'y', 'n', $like_keyword, $like_keyword, $like_keyword);
            $page->gR();
            if ($page->getSize() !== 0) {
                $this->theme->set('sayfalar', $page->toArray());
                $this->theme->set('pages', $page->toArray());
            }
            Cache::set($page->toArray(), $keyword . "-search-mod_page");
        }

        if (Cache::exists($keyword . "-search-mod_video_embed")) {
            $cached_data = Cache::get($keyword . "-search-mod_video_embed");
            if ($cached_data !== FALSE) {
                $this->theme->set('videos', $cached_data);
            }
        } else {
            $vid = Load::model('mod_video_embed');
            $vid->select('name,id');
            $vid->where_lang_and_status_and_og_like_content_or_like_name_cg($this->site['lang'], 'y', $like_keyword, $like_keyword);
            $vid->gR();
            if ($vid->getSize() !== 0) {
                $this->theme->set('videos', $vid->toArray());
            }
            Cache::set($vid->toArray(), $keyword . "-search-mod_video_embed");
        }
        $this->theme->set('search_term', $keyword);
        $this->theme->set('keyword', $keyword);
        $this->theme->set('query', $keyword);
        $this->theme->set('search_query', $keyword);
        $this->theme->tpl('index/search');
        $this->_index();
    }

    private function searchAuthors($keyword)
    {
        if (empty($keyword)) {
            return [];
        }

        $safeKeyword = addslashes($keyword);
        $likeKeyword = '%' . $safeKeyword . '%';

        $words = preg_split('/\s+/', trim($keyword));
        $whereConditions = [
            "u.name LIKE '{$likeKeyword}'",
            "u.last_name LIKE '{$likeKeyword}'",
            "CONCAT(TRIM(u.name), ' ', TRIM(u.last_name)) LIKE '{$likeKeyword}'",
            "u.username LIKE '{$likeKeyword}'",
            "u.blog_name LIKE '{$likeKeyword}'"
        ];

        if (count($words) > 1) {
            $wordConditions = [];
            foreach ($words as $w) {
                $wSafe = addslashes($w);
                $wordConditions[] = "(u.name LIKE '%{$wSafe}%' OR u.last_name LIKE '%{$wSafe}%' OR u.username LIKE '%{$wSafe}%')";
            }
            $whereConditions[] = '(' . implode(' AND ', $wordConditions) . ')';
        }

        $whereClause = implode(' OR ', $whereConditions);

        $sql = "SELECT u.id, u.group_id, u.name, u.last_name, u.username, u.image, u.city, u.blog_name, u.about,
                       g.name AS group_name,
                       (SELECT COUNT(*) FROM mod_publication WHERE user_id = u.id AND status = 'y' AND lang = '{$this->site['lang']}') AS total_publications,
                       (SELECT COUNT(*) FROM mod_news WHERE user_id = u.id AND status = 'y' AND lang = '{$this->site['lang']}') AS total_news
                FROM mod_user u
                LEFT JOIN mod_user_group g ON u.group_id = g.group_id
                WHERE u.status = 'y'
                  AND ({$whereClause})
                  AND (
                    u.group_id IN (1, 2, 4)
                    OR EXISTS (SELECT 1 FROM mod_publication WHERE user_id = u.id AND status = 'y')
                    OR EXISTS (SELECT 1 FROM mod_news WHERE user_id = u.id AND status = 'y')
                  )
                ORDER BY (total_publications + total_news) DESC, u.id ASC
                LIMIT 6";

        $userModel = Load::model('mod_user');
        $userModel->fetch_array($sql);

        if ($userModel->getSize() === 0) {
            return [];
        }

        $results = [];
        $pubModel = Load::model('mod_publication');
        $newsModel = Load::model('mod_news');

        foreach ($userModel->toArray() as $row) {
            $userId = (int) $row['id'];
            $groupId = (int) ($row['group_id'] ?? 4);

            $roleLabel = $this->lang['index']['mod_publication']['author'] ?? 'Author';
            if ($groupId === 1) {
                $roleLabel = $this->lang['index']['mod_publication']['site_management'] ?? ($row['group_name'] ?? 'Site Management');
            } elseif ($groupId === 2) {
                $roleLabel = $this->lang['index']['mod_publication']['editor'] ?? ($row['group_name'] ?? 'Editor');
            } elseif ($groupId === 3) {
                $roleLabel = $this->lang['index']['mod_publication']['member'] ?? ($row['group_name'] ?? 'Member');
            } elseif ($groupId === 4) {
                $roleLabel = $this->lang['index']['mod_publication']['author'] ?? ($row['group_name'] ?? 'Author');
            } elseif (!empty($row['group_name'])) {
                $roleLabel = $row['group_name'];
            }

            $fullName = trim(($row['name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            if ($fullName === '') {
                $fullName = $row['username'];
            }

            $nameFirst = !empty($row['name']) ? mb_substr($row['name'], 0, 1, 'UTF-8') : mb_substr($row['username'], 0, 1, 'UTF-8');
            $nameSecond = !empty($row['last_name']) ? mb_substr($row['last_name'], 0, 1, 'UTF-8') : '';
            $initials = mb_strtoupper($nameFirst . $nameSecond, 'UTF-8');

            $imageFile = $row['image'] ?? '';
            $hasImage = false;
            $userImageUrl = '';
            if (!empty($imageFile)) {
                $fullThumb = root_dir . 'static/mod_user/thumb/' . $imageFile;
                $fullImg = root_dir . 'static/mod_user/' . $imageFile;
                if (file_exists($fullThumb) && is_file($fullThumb)) {
                    $hasImage = true;
                    $userImageUrl = $this->site['iaddress'] . 'mod_user/thumb/' . $imageFile;
                } elseif (file_exists($fullImg) && is_file($fullImg)) {
                    $hasImage = true;
                    $userImageUrl = $this->site['iaddress'] . 'mod_user/' . $imageFile;
                }
            }

            $siteIcon = !empty($this->site['site_icon']) ? $this->site['site_icon'] : (!empty($this->site['logo_symbol']) ? $this->site['logo_symbol'] : 'logo_square.png');
            $siteIconUrl = $this->site['iaddress'] . 'mod_settings/' . $siteIcon;

            $rawAbout = $row['about'] ?? '';
            $cleanAbout = html_entity_decode(strip_tags($rawAbout), ENT_QUOTES, 'UTF-8');
            $aboutExcerpt = '';
            if (!empty($cleanAbout)) {
                $aboutExcerpt = mb_strlen($cleanAbout, 'UTF-8') > 240 ? mb_substr($cleanAbout, 0, 237, 'UTF-8') . '...' : $cleanAbout;
            }

            // Latest publication or article
            $latestPub = null;
            if ((int) $row['total_publications'] > 0) {
                $pubModel->flush();
                $pubSql = "SELECT name, seo, created_at
                           FROM mod_publication
                           WHERE user_id = {$userId}
                             AND status = 'y'
                             AND lang = '{$this->site['lang']}'
                           ORDER BY created_at DESC
                           LIMIT 1";
                $pubModel->fetch_array($pubSql);
                if ($pubModel->getSize() !== 0) {
                    $pubData = $pubModel->toArray()[0];
                    $latestPub = [
                        'name' => $pubData['name'],
                        'seo' => $pubData['seo'],
                        'is_news' => false,
                        'timeago' => Helper::time_ago($pubData['created_at'] ?? '', $this->lang['index'] ?? null),
                    ];
                }
            } elseif ((int) $row['total_news'] > 0) {
                $newsModel->flush();
                $newsSql = "SELECT name, seo, created_at
                            FROM mod_news
                            WHERE user_id = {$userId}
                              AND status = 'y'
                              AND lang = '{$this->site['lang']}'
                            ORDER BY created_at DESC
                            LIMIT 1";
                $newsModel->fetch_array($newsSql);
                if ($newsModel->getSize() !== 0) {
                    $newsData = $newsModel->toArray()[0];
                    $latestPub = [
                        'name' => $newsData['name'],
                        'seo' => $newsData['seo'],
                        'is_news' => true,
                        'timeago' => Helper::time_ago($newsData['created_at'] ?? '', $this->lang['index'] ?? null),
                    ];
                }
            }

            $results[] = [
                'id' => $userId,
                'user_id' => $userId,
                'group_id' => $groupId,
                'role_label' => $roleLabel,
                'name' => $row['name'] ?? '',
                'last_name' => $row['last_name'] ?? '',
                'full_name' => $fullName,
                'username' => $row['username'],
                'user_image' => $hasImage ? $imageFile : '',
                'has_image' => $hasImage,
                'user_image_url' => $userImageUrl,
                'site_icon_url' => $siteIconUrl,
                'initials' => $initials,
                'city' => $row['city'] ?? '',
                'blog_name' => $row['blog_name'] ?? '',
                'about_excerpt' => $aboutExcerpt,
                'total_publications' => (int) ($row['total_publications'] ?? 0),
                'total_news' => (int) ($row['total_news'] ?? 0),
                'latest_publication' => $latestPub,
            ];
        }

        return $results;
    }

    private function searchImages($keyword)
    {
        if (empty($keyword)) {
            return [];
        }

        $safeKeyword = addslashes($keyword);
        $likeKeyword = '%' . $safeKeyword . '%';

        $words = preg_split('/\s+/', trim($keyword));
        $hasMultipleWords = count($words) > 1;

        // 1. mod_gallery conditions
        $galWhere = "(name LIKE '{$likeKeyword}' OR url LIKE '{$likeKeyword}')";
        if ($hasMultipleWords) {
            $galWordConds = [];
            foreach ($words as $w) {
                $wSafe = addslashes($w);
                $galWordConds[] = "(name LIKE '%{$wSafe}%' OR url LIKE '%{$wSafe}%')";
            }
            $galWhere .= ' OR (' . implode(' AND ', $galWordConds) . ')';
        }

        // 2. mod_news conditions
        $newsWhere = "(name LIKE '{$likeKeyword}' OR image LIKE '{$likeKeyword}')";
        if ($hasMultipleWords) {
            $newsWordConds = [];
            foreach ($words as $w) {
                $wSafe = addslashes($w);
                $newsWordConds[] = "(name LIKE '%{$wSafe}%' OR image LIKE '%{$wSafe}%')";
            }
            $newsWhere .= ' OR (' . implode(' AND ', $newsWordConds) . ')';
        }

        // 3. mod_publication conditions
        $pubWhere = "(name LIKE '{$likeKeyword}' OR image LIKE '{$likeKeyword}')";
        if ($hasMultipleWords) {
            $pubWordConds = [];
            foreach ($words as $w) {
                $wSafe = addslashes($w);
                $pubWordConds[] = "(name LIKE '%{$wSafe}%' OR image LIKE '%{$wSafe}%')";
            }
            $pubWhere .= ' OR (' . implode(' AND ', $pubWordConds) . ')';
        }

        $results = [];
        $seenImages = [];

        // Query mod_gallery
        $galModel = Load::model('mod_gallery');
        $galSql = "SELECT id, name, url, mod_name, vid 
                   FROM mod_gallery 
                   WHERE url != '' AND ({$galWhere}) 
                   ORDER BY id DESC 
                   LIMIT 12";
        $galModel->fetch_array($galSql);

        if ($galModel->getSize() > 0) {
            $newsModel = Load::model('mod_news');
            $pubModel = Load::model('mod_publication');
            $pageModel = Load::model('mod_page');

            foreach ($galModel->toArray() as $row) {
                $modName = $row['mod_name'] ?? '';
                $vid = (int) ($row['vid'] ?? 0);
                $targetUrl = '';
                $cleanTitle = trim(str_replace(['_', '-'], ' ', $row['name'] ?? ''));
                if ($cleanTitle === '' || $cleanTitle === '#') {
                    $cleanTitle = $row['url'];
                }

                // Resolve target URL according to parent module
                if (($modName === 'mod_news' || $modName === 'mod_haber') && $vid > 0) {
                    $newsModel->flush();
                    $newsModel->select('seo, name');
                    $newsModel->where_id($vid);
                    $newsModel->gRo();
                    if ($newsModel->getSize() > 0) {
                        $nData = $newsModel->toArray();
                        $targetUrl = $this->site['saddress'] . 'news/' . ($nData['seo'] ?? '');
                        if ($row['name'] === '#' || empty($row['name'])) {
                            $cleanTitle = $nData['name'] ?? $cleanTitle;
                        }
                    }
                } elseif ($modName === 'mod_publication' && $vid > 0) {
                    $pubModel->flush();
                    $pubModel->select('seo, name');
                    $pubModel->where_id($vid);
                    $pubModel->gRo();
                    if ($pubModel->getSize() > 0) {
                        $pData = $pubModel->toArray();
                        $targetUrl = $this->site['saddress'] . 'publication/' . ($pData['seo'] ?? '');
                        if ($row['name'] === '#' || empty($row['name'])) {
                            $cleanTitle = $pData['name'] ?? $cleanTitle;
                        }
                    }
                } elseif ($modName === 'mod_page' && $vid > 0) {
                    $pageModel->flush();
                    $pageModel->select('seo, name');
                    $pageModel->where_id($vid);
                    $pageModel->gRo();
                    if ($pageModel->getSize() > 0) {
                        $pgData = $pageModel->toArray();
                        $targetUrl = $this->site['saddress'] . 'page/' . ($pgData['seo'] ?? '');
                        if ($row['name'] === '#' || empty($row['name'])) {
                            $cleanTitle = $pgData['name'] ?? $cleanTitle;
                        }
                    }
                }

                $galleryFolder = ($modName === 'mod_haber') ? 'mod_haber' : $modName;
                $imageUrl = $this->site['iaddress'] . 'mod_gallery/' . $galleryFolder . '/original/' . $row['url'];
                $thumbUrl = $this->site['iaddress'] . 'mod_gallery/' . $galleryFolder . '/thumb/' . $row['url'];

                if (empty($targetUrl)) {
                    $targetUrl = $imageUrl;
                }

                $imageKey = $row['url'];
                if (isset($seenImages[$imageKey])) {
                    continue;
                }
                $seenImages[$imageKey] = true;

                $results[] = [
                    'id' => (int) $row['id'],
                    'title' => $cleanTitle,
                    'raw_name' => $row['name'],
                    'image_url' => $imageUrl,
                    'thumb_url' => $thumbUrl,
                    'target_url' => $targetUrl,
                    'source_type' => 'gallery',
                    'source_label' => $this->lang['index']['search']['gallery'] ?? 'Galerî',
                ];
            }
        }

        // Query mod_news
        $newsModel = Load::model('mod_news');
        $newsSql = "SELECT id, name, seo, image 
                    FROM mod_news 
                    WHERE status = 'y' 
                      AND lang = '{$this->site['lang']}' 
                      AND image != '' 
                      AND ({$newsWhere}) 
                    ORDER BY id DESC 
                    LIMIT 12";
        $newsModel->fetch_array($newsSql);

        if ($newsModel->getSize() > 0) {
            foreach ($newsModel->toArray() as $row) {
                $imageKey = $row['image'];
                if (isset($seenImages[$imageKey])) {
                    continue;
                }
                $seenImages[$imageKey] = true;

                $results[] = [
                    'id' => (int) $row['id'],
                    'title' => $row['name'],
                    'raw_name' => $row['image'],
                    'image_url' => $this->site['iaddress'] . 'mod_news/' . $row['image'],
                    'thumb_url' => $this->site['iaddress'] . 'mod_news/thumb/' . $row['image'],
                    'target_url' => $this->site['saddress'] . 'news/' . $row['seo'],
                    'source_type' => 'news',
                    'source_label' => $this->lang['index']['search']['news'] ?? 'Nûçe',
                ];
            }
        }

        // Query mod_publication
        $pubModel = Load::model('mod_publication');
        $pubSql = "SELECT id, name, seo, image 
                   FROM mod_publication 
                   WHERE status = 'y' 
                     AND lang = '{$this->site['lang']}' 
                     AND image != '' 
                     AND ({$pubWhere}) 
                   ORDER BY id DESC 
                   LIMIT 12";
        $pubModel->fetch_array($pubSql);

        if ($pubModel->getSize() > 0) {
            foreach ($pubModel->toArray() as $row) {
                $imageKey = $row['image'];
                if (isset($seenImages[$imageKey])) {
                    continue;
                }
                $seenImages[$imageKey] = true;

                $results[] = [
                    'id' => (int) $row['id'],
                    'title' => $row['name'],
                    'raw_name' => $row['image'],
                    'image_url' => $this->site['iaddress'] . 'mod_publication/' . $row['image'],
                    'thumb_url' => $this->site['iaddress'] . 'mod_publication/thumb/' . $row['image'],
                    'target_url' => $this->site['saddress'] . 'publication/' . $row['seo'],
                    'source_type' => 'publication',
                    'source_label' => $this->lang['index']['search']['articles'] ?? 'Gotar',
                ];
            }
        }

        return $results;
    }
}

