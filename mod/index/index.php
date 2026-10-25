<?php

require_once(__DIR__ . '/indexBase.php');

class index_mod extends indexBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->_index();
        $this->tema->tpl('index/index');
    }

    public function page($seo = null)
    {
        $page = Load::helper("index.mod_page");
        $page->page($seo);
        unset($page);
    }

    public function legal($type = null)
    {
        $legal = Load::helper("index.legal");
        $legal->render($type);
        unset($legal);
    }

    public function cookie_policy()
    {
        $legal = Load::helper("index.legal");
        $legal->render('cookies');
        unset($legal);
    }

    public function privacy_policy()
    {
        $legal = Load::helper("index.legal");
        $legal->render('privacy');
        unset($legal);
    }

    public function terms_of_service()
    {
        $legal = Load::helper("index.legal");
        $legal->render('terms');
        unset($legal);
    }

    public function editorial_policy()
    {
        $legal = Load::helper("index.legal");
        $legal->render('editorial');
        unset($legal);
    }

    public function accessibility()
    {
        $legal = Load::helper("index.legal");
        $legal->render('accessibility');
        unset($legal);
    }

    public function security_policy()
    {
        $legal = Load::helper("index.legal");
        $legal->render('security');
        unset($legal);
    }

    public function compliance()
    {
        $legal = Load::helper("index.legal");
        $legal->render('compliance');
        unset($legal);
    }

    public function contact()
    {
        $legal = Load::helper("index.legal");
        $legal->render('contact');
        unset($legal);
    }

    public function videos($category = null, $page = 1)
    {
        if (!$this->guardFeatureOr404('video_embed.front')) {
            return;
        }

        $vid = Load::helper("index.mod_video_embed");
        $vid->videos($category, $page);
        unset($vid);
    }

    public function video($id = null)
    {
        if (!$this->guardFeatureOr404('video_embed.front')) {
            return;
        }

        $vid = Load::helper("index.mod_video_embed");
        $vid->video($id);
        unset($vid);
    }

    public function gallery($category = null)
    {
        $res = Load::helper("index.mod_imagecategories");
        $res->gallery($category);
        unset($res);
    }

    public function _gallery_pictures($id = null, $limit = null)
    {
        $res = Load::helper("index.mod_imagecategories");
        $res->_gallery_pictures($id, $limit);
        unset($res);
    }



    public function news($seo = null)
    {
        if (!$this->guardFeatureOr404('news.front')) {
            return;
        }

        Load::lib("Session");
        $news = Load::helper("index.mod_news");
        $news->news($seo);
        unset($news);
    }

    public function newses($category = null, $page = 1)
    {
        if (!$this->guardFeatureOr404('news.front')) {
            return;
        }

        $news = Load::helper("index.mod_news");
        $news->newses($category, $page);
        unset($news);
    }

    public function news_archive()
    {
        if (!$this->guardFeatureOr404('news.archive')) {
            return;
        }

        $news = Load::helper('index.mod_news');
        $news->archive();
        unset($news);
    }

    public function publication($seo = null)
    {
        if (!$this->guardFeatureOr404('publication.front')) {
            return;
        }

        Load::lib("Session");
        $publication = Load::helper("index.mod_publication");
        $publication->publication($seo);
        unset($publication);
    }

    public function publications($category = null, $page = 1)
    {
        if (!$this->guardFeatureOr404('publication.front')) {
            return;
        }

        $publication = Load::helper("index.mod_publication");
        $publication->publications($category, $page);
        unset($publication);
    }

    public function publication_archive()
    {
        if (!$this->guardFeatureOr404('publication.archive')) {
            return;
        }

        $publication = Load::helper('index.mod_publication');
        $publication->archive();
        unset($publication);
    }


    public function announcement($id = null)
    {
        $announcement = Load::helper("index.mod_announcements");
        $announcement->announcement($id);
        unset($announcement);
    }

    public function announcements()
    {
        $announcement = Load::helper("index.mod_announcements");
        $announcement->announcements();
        unset($announcement);
    }

    public function _latest_announcements($t, $limit, $select)
    {
        $helper = Load::helper('index.mod_announcements');
        $result = $helper->_latest_announcements($limit, $select);
        unset($helper);
        $t->ctx_set('latest_announcements', $result);
    }

    public function _latest_videos($t, $limit, $select)
    {
        $latest = [];

        // 1. Fetch from Local Videos (mod_video) if the module is active
        $video_model = Load::model('mod_video');
        $video_model->select('id, name, image, duration, lang, created_at, status, description, flv');
        $video_model->where_lang_and_status($this->site['lang'], 'y');
        $video_model->limit($limit)->order('created_at', 'DESC');
        $video_model->gR();

        if ($video_model->getSize() > 0) {
            foreach ($video_model->toArray() as $row) {
                $latest[] = [
                    'id' => $row['id'],
                    'name' => html_entity_decode($row['name'], ENT_QUOTES, 'UTF-8'),
                    'image' => '../../mod_video/image/' . $row['image'],
                    'content' => isset($row['description']) ? html_entity_decode($row['description'], ENT_QUOTES, 'UTF-8') : '',
                    'keywords' => html_entity_decode($row['name'], ENT_QUOTES, 'UTF-8'),
                    'duration' => $row['duration'],
                    'created_at' => $row['created_at'],
                    'is_local' => true,
                    'flv' => $row['flv'],
                    'embed' => '',
                    'original' => ''
                ];
            }
        }
        unset($video_model);

        // 2. Fetch from Embedded Videos (mod_video_embed) if enabled and we need more records
        if (count($latest) < $limit && $this->isFeatureReady('video_embed.latest')) {
            $helper = Load::helper('index.mod_video_embed');
            $select_embed = $select;
            if (is_string($select_embed)) {
                if (strpos($select_embed, 'created_at') === false) {
                    $select_embed .= ',created_at';
                }
                $select_embed .= ',embed,original';
            }
            
            $embed_results = $helper->_latest_videos($limit - count($latest), $select_embed);
            unset($helper);

            foreach ($embed_results as $row) {
                $latest[] = [
                    'id' => $row['id'],
                    'name' => html_entity_decode($row['name'], ENT_QUOTES, 'UTF-8'),
                    'image' => $row['image'],
                    'content' => isset($row['content']) ? html_entity_decode($row['content'], ENT_QUOTES, 'UTF-8') : '',
                    'keywords' => isset($row['keywords']) ? html_entity_decode($row['keywords'], ENT_QUOTES, 'UTF-8') : '',
                    'duration' => isset($row['duration']) ? $row['duration'] : '',
                    'created_at' => isset($row['created_at']) ? $row['created_at'] : null,
                    'is_local' => false,
                    'embed' => isset($row['embed']) ? $row['embed'] : '',
                    'original' => isset($row['original']) ? $row['original'] : '',
                    'flv' => ''
                ];
            }
        }

        // Sort combined results by created_at DESC safely
        usort($latest, function($a, $b) {
            $dateA = isset($a['created_at']) ? (string)$a['created_at'] : '';
            $dateB = isset($b['created_at']) ? (string)$b['created_at'] : '';
            return strcmp($dateB, $dateA);
        });

        // Limit final combined results
        $latest = array_slice($latest, 0, $limit);

        $t->ctx_set('latest_videos', $latest);
    }

    public function _latest_pictures($t, $limit, $select)
    {
        $helper = Load::helper('index.mod_imagecategories');
        $result = $helper->_latest_pictures($limit, $select);
        unset($helper);
        $t->ctx_set('latest_pictures', $result);
    }

    public function _fullmanset($t, $limit)
    {
        $fullmanset = Load::helper("index.mod_headline");
        $result = $fullmanset->all($limit);
        $t->ctx_set('fullmanset', $result);
    }

    public function _anket($t)
    {
        $anket = Load::helper("index.mod_survey");
        $result = $anket->_anket();
        $t->ctx_set('anket', $result);
        unset($anket);
    }

    public function oyver()
    {
        $anket = Load::helper("index.mod_survey");
        return $anket->oyver();
    }

    public function search($query = null)
    {
        $ara = Load::helper('index.search');
        $ara->search($query);
        unset($ara);
    }


    public function _popup($t)
    {
        $popup = Load::helper('index.mod_popup');
        $result = $popup->getLast();
        $t->ctx_set('pop', $result);
        unset($popup);
    }

    public function _menu($t)
    {
        $menu = Load::helper("index.mod_menu");
        $result = $menu->menu();
        $t->ctx_set('menu', $result);
        $headerBranch = $menu->menuBranch('header');
        $footerBranch = $menu->menuBranch('footer');
        $t->ctx_set('menu_header', $headerBranch['tree']);
        $t->ctx_set('menu_header_root_id', $headerBranch['root_id']);
        $t->ctx_set('menu_footer', $footerBranch['tree']);
        $t->ctx_set('menu_footer_root_id', $footerBranch['root_id']);
    }

    public function _slider($t, $limit)
    {
        $slider = Load::helper("index.mod_slider");
        $result = $slider->enson_slider($limit);
        $t->ctx_set('slider', $result);
    }

    public function _hero_slider($t, $limit)
    {
        $slider = Load::helper("index.mod_hero_slider");
        $result = $slider->hero_slider($limit);
        $t->ctx_set('hero_slider', $result);
    }



    public function _latest_news($t, $limit, $select)
    {
        if (!$this->isFeatureReady('news.latest')) {
            $t->ctx_set('latest_news', []);
            return;
        }

        $helper = Load::helper('index.mod_news');
        $result = $helper->_latest_news($limit, $select);
        $t->ctx_set('latest_news', $result);
        unset($helper);
    }

    public function _sports_news($t, $limit = 2)
    {
        if (!$this->isFeatureReady('news.latest')) {
            $t->ctx_set('sports_news', []);
            $t->ctx_set('sports_category_seo', '');
            $t->ctx_set('sports_category_url', $this->site['saddress'] . 'newses');
            return;
        }

        $helper = Load::helper('index.mod_news');
        $result = $helper->_category_news(
            ['Spor', 'Werzîş', 'Werzis', 'Sports', 'Sport', 'رياضة', 'وەرزش'],
            (int) $limit
        );
        $categorySeo = (string) ($result['category_seo'] ?? '');
        $t->ctx_set('sports_news', $result['items']);
        $t->ctx_set('sports_category_seo', $categorySeo);
        $t->ctx_set(
            'sports_category_url',
            $categorySeo !== '' ? $this->site['saddress'] . 'newses/' . $categorySeo : $this->site['saddress'] . 'newses'
        );
        unset($helper);
    }

    public function _homepage_news_categories($t, $limit, $select)
    {
        if (!$this->isFeatureReady('news.homepage_categories')) {
            $t->ctx_set('homepage_news_categories', []);
            return;
        }

        $helper = Load::helper('index.mod_news');
        $result = $helper->_homepage_categories($limit, $select);
        $t->ctx_set('homepage_news_categories', $result);
        unset($helper);
    }

    public function _latest_publications($t, $limit, $select)
    {
        if (!$this->isFeatureReady('publication.latest')) {
            $t->ctx_set('latest_publications', []);
            return;
        }

        $helper = Load::helper('index.mod_publication');
        $result = $helper->_latest_publications($limit, $select);
        $t->ctx_set('latest_publications', $result);
        unset($helper);
    }


    public function _latest_authors($t, $limit)
    {
        if (!$this->isFeatureReady('publication.authors')) {
            $t->ctx_set('latest_authors', []);
            return;
        }

        $helper = Load::helper('index.mod_publication');
        $result = $helper->_latest_authors($limit);
        $t->ctx_set('latest_authors', $result);
        unset($helper);
    }

    public function authors($page = 1)
    {
        if (!$this->guardFeatureOr404('publication.authors')) {
            return;
        }

        $publication = Load::helper("index.mod_publication");
        $publication->authors($page);
        unset($publication);
    }

    public function author($username = null, $page = 1)
    {
        if (!$this->guardFeatureOr404('publication.authors')) {
            return;
        }

        $publication = Load::helper("index.mod_publication");
        $publication->author($username, $page);
        unset($publication);
    }

    public function rss()
    {
        if (!$this->guardFeatureOr404('news.feed')) {
            return;
        }

        header("content-type: text/xml; charset=utf-8");

        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', (string) $this->site['lang'], true);
        $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';

        $news = Load::model('mod_news.newses');
        $news->where_status_and_lang('y', $this->site['lang']);
        $news->addWhere('(acl_id = 0 OR acl_id IS NULL)');
        $news->addWhere("category_id IN ({$allowedCatList})");
        $news->order('created_at', 'DESC');
        $news->select(['title', 'summary', 'created_at', 'seo', 'lang', 'image']);
        $news->limit(50);
        $news->gR();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
        <channel>
            <title>' . htmlspecialchars($this->site['title']) . '</title>
            <link>' . $this->site['address'] . $this->site['lang'] . '</link>
            <description>' . htmlspecialchars($this->site['description']) . '</description>
            <language>' . $this->site['lang'] . '</language>
            <lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>
            <atom:link href="' . $this->site['address'] . $this->site['lang'] . '/rss.xml" rel="self" type="application/rss+xml" />';

        if ($news->getSize() != 0) {
            foreach ($news->toArray() as $o) {
                $link = $this->site['address'] . $o['lang'] . '/news/' . $o['seo'];
                $imageUrl = "";
                if (!empty($o['image'])) {
                    $imageUrl = $this->site['iaddress'] . 'mod_news/thumb/' . $o['image'];
                }
                
                echo '
            <item>
                <title>' . htmlspecialchars($o['title']) . '</title>
                <link>' . $link . '</link>
                <description><![CDATA[' . ($imageUrl ? '<img src="' . $imageUrl . '" align="left" hspace="10" vspace="10" />' : '') . mb_substr(strip_tags($o['summary']), 0, 500) . '...]]></description>
                <pubDate>' . date(DATE_RSS, strtotime($o['created_at'])) . '</pubDate>
                <guid isPermaLink="true">' . $link . '</guid>';
                if ($imageUrl) {
                    echo '
                <media:content url="' . $imageUrl . '" medium="image" />
                <enclosure url="' . $imageUrl . '" type="image/jpeg" />';
                }
                echo '
            </item>';
            }
        }
        echo '
        </channel>
        </rss>';
        exit;
    }

    public function sitemapIndex()
    {
        header("content-type: text/xml");
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        $active_langs = Registry::get('active_langs');
        if (!is_array($active_langs)) {
            $active_langs = array();
        }

        foreach ($active_langs as $l) {
            if ($this->isFeatureReady('news.latest')) {
                echo '<sitemap>
                        <loc>' . $this->site['address'] . $l . '/newsSitemap.xml</loc>
                      </sitemap>';
            }
            if ($this->isFeatureReady('publication.latest')) {
                echo '<sitemap>
                        <loc>' . $this->site['address'] . $l . '/publicationSitemap.xml</loc>
                      </sitemap>';
            }

            echo '<sitemap>
                    <loc>' . $this->site['address'] . $l . '/announcementSitemap.xml</loc>
                  </sitemap>';
            echo '<sitemap>
                    <loc>' . $this->site['address'] . $l . '/videoSitemap.xml</loc>
                  </sitemap>';
            echo '<sitemap>
                    <loc>' . $this->site['address'] . $l . '/gallerySitemap.xml</loc>
                  </sitemap>';
            echo '<sitemap>
                    <loc>' . $this->site['address'] . $l . '/categorySitemap.xml</loc>
                  </sitemap>';
            echo '<sitemap>
                    <loc>' . $this->site['address'] . $l . '/pageSitemap.xml</loc>
                  </sitemap>';
        }
        
        echo '</sitemapindex>';
        exit;
    }

    public function newsSitemap($page = 1)
    {
        if (!$this->guardFeatureOr404('news.latest')) {
            return;
        }

        header("content-type: text/xml");
        $page_limit = 50000;
        $page = (intval($page) === 0) ? 1 : intval($page);
        $offset = ($page - 1) * $page_limit;

        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', (string) $this->site['lang'], true);
        $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';

        $news = Load::model('mod_news.newses');
        $news->where_status_and_lang('y', $this->site['lang']);
        $news->addWhere('(acl_id = 0 OR acl_id IS NULL)');
        $news->addWhere("category_id IN ({$allowedCatList})");
        $news->order('created_at', 'DESC');
        $news->select(['seo', 'lang']);
        $news->limit($page_limit, $offset);
        $news->gR();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        if ($news->getSize() != 0) {
            foreach ($news->toArray() as $o) {
                echo '<url>
                          <loc>' . $this->site['address'] . $o['lang'] . '/news/' . $o['seo'] . '</loc>
                          <priority>0.80</priority>
                    </url>';
            }
        }
        echo '</urlset>';
        exit;
    }

    public function publicationSitemap($page = 1)
    {
        if (!$this->guardFeatureOr404('publication.latest')) {
            return;
        }

        header("content-type: text/xml");
        $page_limit = 50000;
        $page = (intval($page) === 0) ? 1 : intval($page);
        $offset = ($page - 1) * $page_limit;

        $allowedCatIds = $this->getAllowedCategoryIds('mod_publication_category', (string) $this->site['lang'], true);
        $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';

        $pub = Load::model('mod_publication');
        $pub->where_status_and_lang('y', $this->site['lang']);
        $pub->addWhere('(acl_id = 0 OR acl_id IS NULL)');
        $pub->addWhere("category_id IN ({$allowedCatList})");
        $pub->order('created_at', 'DESC');
        $pub->select(['seo', 'lang']);
        $pub->limit($page_limit, $offset);
        $pub->gR();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        if ($pub->getSize() != 0) {
            foreach ($pub->toArray() as $o) {
                echo '<url>
                          <loc>' . $this->site['address'] . $o['lang'] . '/publication/' . $o['seo'] . '</loc>
                          <priority>0.80</priority>
                    </url>';
            }
        }
        echo '</urlset>';
        exit;
    }



    public function announcementSitemap($page = 1)
    {
        header("content-type: text/xml");
        $page_limit = 50000;
        $page = (intval($page) === 0) ? 1 : intval($page);
        $offset = ($page - 1) * $page_limit;
        
        $model = Load::model('mod_announcements');
        $model->where_status_and_lang('y', $this->site['lang']);
        $model->addWhere('(acl_id = 0 OR acl_id IS NULL)');
        $model->order('created_at', 'DESC');
        $model->select(['id', 'name', 'lang']);
        $model->limit($page_limit, $offset);
        $model->gR();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        if ($model->getSize() != 0) {
            foreach ($model->toArray() as $o) {
                $slug = Helper::sefurl($o['name']);
                echo '<url>
                          <loc>' . $this->site['address'] . $o['lang'] . '/announcement/' . $o['id'] . '-' . $slug . '</loc>
                          <priority>0.70</priority>
                    </url>';
            }
        }
        echo '</urlset>';
        exit;
    }

    public function videoSitemap($page = 1)
    {
        header("content-type: text/xml");
        $page_limit = 25000;
        $page = (intval($page) === 0) ? 1 : intval($page);
        $offset = ($page - 1) * $page_limit;

        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // 1. Local videos
        $model_local = Load::model('mod_video');
        $model_local->where_status_and_lang('y', $this->site['lang']);
        $model_local->addWhere('(acl_id = 0 OR acl_id IS NULL)');
        $model_local->order('created_at', 'DESC');
        $model_local->select(['id', 'name', 'lang']);
        $model_local->limit($page_limit, $offset);
        $model_local->gR();
        if ($model_local->getSize() != 0) {
            foreach ($model_local->toArray() as $o) {
                $slug = Helper::sefurl($o['name']);
                echo '<url>
                          <loc>' . $this->site['address'] . $o['lang'] . '/video/' . $o['id'] . '-' . $slug . '</loc>
                          <priority>0.65</priority>
                    </url>';
            }
        }

        // 2. Embedded videos
        if ($this->isFeatureReady('video_embed.latest')) {
            $model_embed = Load::model('mod_video_embed');
            $model_embed->where_status_and_lang('y', $this->site['lang']);
            $model_embed->addWhere('(acl_id = 0 OR acl_id IS NULL)');
            $model_embed->order('created_at', 'DESC');
            $model_embed->select(['id', 'name', 'lang']);
            $model_embed->limit($page_limit, $offset);
            $model_embed->gR();
            if ($model_embed->getSize() != 0) {
                foreach ($model_embed->toArray() as $o) {
                    $slug = Helper::sefurl($o['name']);
                    echo '<url>
                              <loc>' . $this->site['address'] . $o['lang'] . '/video/' . $o['id'] . '-' . $slug . '</loc>
                              <priority>0.65</priority>
                        </url>';
                }
            }
        }

        echo '</urlset>';
        exit;
    }

    public function gallerySitemap($page = 1)
    {
        header("content-type: text/xml");
        $page_limit = 50000;
        $page = (intval($page) === 0) ? 1 : intval($page);
        $offset = ($page - 1) * $page_limit;
        
        $model = Load::model('mod_imagecategories');
        $model->where_root_and_lang('n', $this->site['lang']);
        $model->addWhere('(acl_id = 0 OR acl_id IS NULL)');
        $model->order('order', 'ASC');
        $model->select(['seo', 'lang']);
        $model->limit($page_limit, $offset);
        $model->gR();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        echo '<url>
                  <loc>' . $this->site['address'] . $this->site['lang'] . '/gallery</loc>
                  <priority>0.70</priority>
            </url>';
            
        if ($model->getSize() != 0) {
            foreach ($model->toArray() as $o) {
                echo '<url>
                          <loc>' . $this->site['address'] . $o['lang'] . '/gallery/' . $o['seo'] . '</loc>
                          <priority>0.65</priority>
                    </url>';
            }
        }
        echo '</urlset>';
        exit;
    }

    public function categorySitemap($page = 1)
    {
        header("content-type: text/xml");
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        if ($this->isFeatureReady('news.front')) {
            $allowedNewsCats = $this->getAllowedCategoryIds('mod_news_category', (string) $this->site['lang'], true);
            if (!empty($allowedNewsCats)) {
                $catList = implode(',', array_map('intval', $allowedNewsCats));
                $cat_news = Load::model('mod_news_category');
                $cat_news->where_lang_and_root($this->site['lang'], 'n');
                $cat_news->addWhere("id IN ({$catList})");
                $cat_news->select(['seo', 'lang']);
                $cat_news->gR();
                if ($cat_news->getSize() != 0) {
                    foreach ($cat_news->toArray() as $o) {
                        if ($o['seo'] !== '#' && $o['seo'] !== '') {
                            echo '<url>
                                      <loc>' . $this->site['address'] . $o['lang'] . '/newses/' . $o['seo'] . '</loc>
                                      <priority>0.60</priority>
                                </url>';
                        }
                    }
                }
            }
        }

        if ($this->isFeatureReady('publication.front')) {
            $allowedPubCats = $this->getAllowedCategoryIds('mod_publication_category', (string) $this->site['lang'], true);
            if (!empty($allowedPubCats)) {
                $catList = implode(',', array_map('intval', $allowedPubCats));
                $cat_pub = Load::model('mod_publication_category');
                $cat_pub->where_lang_and_root($this->site['lang'], 'n');
                $cat_pub->addWhere("id IN ({$catList})");
                $cat_pub->select(['seo', 'lang']);
                $cat_pub->gR();
                if ($cat_pub->getSize() != 0) {
                    foreach ($cat_pub->toArray() as $o) {
                        if ($o['seo'] !== '#' && $o['seo'] !== '') {
                            echo '<url>
                                      <loc>' . $this->site['address'] . $o['lang'] . '/publications/' . $o['seo'] . '</loc>
                                      <priority>0.60</priority>
                                </url>';
                        }
                    }
                }
            }
        }


        echo '</urlset>';
        exit;
    }

    public function pageSitemap($page = 1)
    {
        header("content-type: text/xml");
        $page_limit = 50000;
        $page = (intval($page) === 0) ? 1 : intval($page);
        $offset = ($page - 1) * $page_limit;
        
        $pages = Load::model('mod_page');
        $pages->where_status_and_lang('y', $this->site['lang']);
        $pages->addWhere('(acl_id = 0 OR acl_id IS NULL)');
        $pages->select(['seo', 'lang']);
        $pages->limit($page_limit, $offset);
        $pages->gR();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        if ($this->site['lang'] == Registry::get('default_lang')) {
            echo '<url>
                      <loc>' . $this->site['address'] . '</loc>
                      <priority>1.0</priority>
                </url>';
        }
        
        if ($pages->getSize() != 0) {
            foreach ($pages->toArray() as $o) {
                echo '<url>
                          <loc>' . $this->site['address'] . $o['lang'] . '/page/' . $o['seo'] . '</loc>
                          <priority>0.50</priority>
                    </url>';
            }
        }

        if ($page === 1) {
            $policySlugs = [
                'legal',
                'editorial-policy',
                'privacy-policy',
                'cookie-policy',
                'terms-of-service',
                'accessibility',
                'security-policy',
                'compliance',
                'contact',
            ];
            foreach ($policySlugs as $pslug) {
                echo '<url>
                          <loc>' . $this->site['saddress'] . $pslug . '</loc>
                          <priority>0.60</priority>
                    </url>';
            }
        }
        echo '</urlset>';
        exit;
    }

    public function legacyRedirect($seo = null)
    {
        if ($seo) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $this->site['address'] . 'news/' . $seo);
            exit();
        }
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $this->site['address']);
        exit();
    }

    public function legacyRedirectPublication($seo = null)
    {
        if ($seo) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $this->site['address'] . 'publication/' . $seo);
            exit();
        }
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $this->site['address']);
        exit();
    }

    public function llms()
    {
        /** @var LlmsService $llmsService */
        $llmsService = Load::lib('LlmsService');
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=3600, stale-while-revalidate=86400');
        header('X-Robots-Tag: all');
        echo $llmsService->render($this->site['lang'] ?? '', false);
        exit;
    }

    public function llmsFull()
    {
        /** @var LlmsService $llmsService */
        $llmsService = Load::lib('LlmsService');
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=3600, stale-while-revalidate=86400');
        header('X-Robots-Tag: all');
        echo $llmsService->render($this->site['lang'] ?? '', true);
        exit;
    }
}
