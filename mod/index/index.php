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


    public function content($seo = null)
    {
        if (!$this->guardFeatureOr404('content.front')) {
            return;
        }

        Load::lib("Session");
        $content = Load::helper("index.mod_content");
        $content->content($seo);
        unset($content);
    }

    public function contents($category = null, $page = 1)
    {
        if (!$this->guardFeatureOr404('content.front')) {
            return;
        }

        $content = Load::helper("index.mod_content");
        $content->contents($category, $page);
        unset($content);
    }

    public function arsiv()
    {
        if (!$this->guardFeatureOr404('content.archive')) {
            return;
        }

        $content = Load::helper('index.mod_content');
        $content->arsiv();
        unset($content);
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
        $news->arsiv();
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
        if (!$this->isFeatureReady('video_embed.latest')) {
            $t->ctx_set('latest_videos', []);
            return;
        }

        $helper = Load::helper('index.mod_video_embed');
        $result = $helper->_latest_videos($limit, $select);
        unset($helper);
        $t->ctx_set('latest_videos', $result);
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

    public function search($kelime = null)
    {
        $ara = Load::helper('index.search');
        $ara->search($kelime);
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


    public function _latest_content($t, $limit, $select)
    {
        if (!$this->isFeatureReady('content.latest')) {
            $t->ctx_set('latest_content', []);
            return;
        }

        $helper = Load::helper('index.mod_content');
        $result = $helper->_latest_content($limit, $select);
        $t->ctx_set('latest_content', $result);
        unset($helper);
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
        
        $news = Load::model('mod_news.newses');
        $news->where_status_and_lang('y', $this->site['lang']);
        $news->order('created_at', 'DESC');
        $news->select(['title', 'summary', 'created_at', 'seo', 'lang', 'image']);
        $news->limit(50);
        $news->gR();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
        <channel>
            <title>' . htmlspecialchars($this->site['title']) . '</title>
            <link>' . $this->site['adres'] . $this->site['lang'] . '</link>
            <description>' . htmlspecialchars($this->site['description']) . '</description>
            <language>' . $this->site['lang'] . '</language>
            <lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>
            <atom:link href="' . $this->site['adres'] . $this->site['lang'] . '/rss.xml" rel="self" type="application/rss+xml" />';

        if ($news->getSize() != 0) {
            foreach ($news->toArray() as $o) {
                $link = $this->site['adres'] . $o['lang'] . '/news/' . $o['seo'];
                $imageUrl = "";
                if (!empty($o['image'])) {
                    $imageUrl = $this->site['radres'] . 'mod_news/thumb/' . $o['image'];
                }
                
                echo '
            <item>
                <title>' . htmlspecialchars($o['title']) . '</title>
                <link>' . $link . '</link>
                <description><![CDATA[' . ($imageUrl ? '<img src="' . $imageUrl . '" align="left" style="margin-right:10px; margin-bottom:10px;" />' : '') . mb_substr(strip_tags($o['summary']), 0, 500) . '...]]></description>
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
    }

    public function sitemapIndex()
    {
        header("content-type: text/xml");
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        $active_langs = Kayit::getir('active_langs');
        if (!is_array($active_langs)) {
            $active_langs = array();
        }

        foreach ($active_langs as $l) {
            if ($this->isFeatureReady('news.latest')) {
                echo '<sitemap>
                        <loc>' . $this->site['adres'] . $l . '/newsSitemap.xml</loc>
                      </sitemap>';
            }
            if ($this->isFeatureReady('publication.latest')) {
                echo '<sitemap>
                        <loc>' . $this->site['adres'] . $l . '/publicationSitemap.xml</loc>
                      </sitemap>';
            }
            if ($this->isFeatureReady('news.front') || $this->isFeatureReady('publication.front')) {
                echo '<sitemap>
                        <loc>' . $this->site['adres'] . $l . '/categorySitemap.xml</loc>
                      </sitemap>';
            }
            echo '<sitemap>
                    <loc>' . $this->site['adres'] . $l . '/pageSitemap.xml</loc>
                  </sitemap>';
        }
        
        echo '</sitemapindex>';
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
        
        $news = Load::model('mod_news.newses');
        $news->where_status_and_lang('y', $this->site['lang']);
        $news->order('created_at', 'DESC');
        $news->select(['seo', 'lang']);
        $news->limit($page_limit, $offset);
        $news->gR();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        if ($news->getSize() != 0) {
            foreach ($news->toArray() as $o) {
                echo '<url>
                          <loc>' . $this->site['adres'] . $o['lang'] . '/news/' . $o['seo'] . '</loc>
                          <priority>0.80</priority>
                    </url>';
            }
        }
        echo '</urlset>';
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
        
        $pub = Load::model('mod_publication');
        $pub->where_status_and_lang('y', $this->site['lang']);
        $pub->order('created_at', 'DESC');
        $pub->select(['seo', 'lang']);
        $pub->limit($page_limit, $offset);
        $pub->gR();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        if ($pub->getSize() != 0) {
            foreach ($pub->toArray() as $o) {
                echo '<url>
                          <loc>' . $this->site['adres'] . $o['lang'] . '/publication/' . $o['seo'] . '</loc>
                          <priority>0.80</priority>
                    </url>';
            }
        }
        echo '</urlset>';
    }

    public function categorySitemap($page = 1)
    {
        header("content-type: text/xml");
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        if ($this->isFeatureReady('news.front')) {
            $cat_news = Load::model('mod_news_category');
            $cat_news->where_lang($this->site['lang'])->select(['seo', 'lang']);
            $cat_news->gR();
            if ($cat_news->getSize() != 0) {
                foreach ($cat_news->toArray() as $o) {
                    echo '<url>
                              <loc>' . $this->site['adres'] . $o['lang'] . '/newses/' . $o['seo'] . '</loc>
                              <priority>0.60</priority>
                        </url>';
                }
            }
        }

        if ($this->isFeatureReady('publication.front')) {
            $cat_pub = Load::model('mod_publication_category');
            $cat_pub->where_lang($this->site['lang'])->select(['seo', 'lang']);
            $cat_pub->gR();
            if ($cat_pub->getSize() != 0) {
                foreach ($cat_pub->toArray() as $o) {
                    echo '<url>
                              <loc>' . $this->site['adres'] . $o['lang'] . '/publications/' . $o['seo'] . '</loc>
                              <priority>0.60</priority>
                        </url>';
                }
            }
        }

        echo '</urlset>';
    }

    public function pageSitemap($page = 1)
    {
        header("content-type: text/xml");
        $page_limit = 50000;
        $page = (intval($page) === 0) ? 1 : intval($page);
        $offset = ($page - 1) * $page_limit;
        
        $pages = Load::model('mod_page');
        $pages->where_status_and_lang('y', $this->site['lang']);
        $pages->select(['seo', 'lang']);
        $pages->limit($page_limit, $offset);
        $pages->gR();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        if ($this->site['lang'] == Kayit::getir('default_lang')) {
            echo '<url>
                      <loc>' . $this->site['adres'] . '</loc>
                      <priority>1.0</priority>
                </url>';
        }
        
        if ($pages->getSize() != 0) {
            foreach ($pages->toArray() as $o) {
                echo '<url>
                          <loc>' . $this->site['adres'] . $o['lang'] . '/page/' . $o['seo'] . '</loc>
                          <priority>0.50</priority>
                    </url>';
            }
        }
        echo '</urlset>';
    }

    public function legacyRedirect($seo = null)
    {
        if ($seo) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $this->site['adres'] . 'news/' . $seo);
            exit();
        }
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $this->site['adres']);
        exit();
    }

    public function legacyRedirectPublication($seo = null)
    {
        if ($seo) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $this->site['adres'] . 'publication/' . $seo);
            exit();
        }
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $this->site['adres']);
        exit();
    }
}
