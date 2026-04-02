<?php

/* AKM Software
 * @ 2010
 * index Modül Sayfası
 * mod/index/index.php
 */
require_once(__DIR__ . '/indexBase.php');

class index_mod extends indexBase
{
    public function __construct()
    {
        parent::__construct();
    }

    // İndex
    public function index()
    {
        $this->_index();
        $this->tema->tpl('index/index');
    }



    # mod_page
    public function page($seo = null)
    {
        $page = Load::helper("index.mod_page");
        $page->page($seo);
        unset($page);
    }

    # mod_video_embed
    public function videos($category = null, $sayfa = 1)
    {
        $vid = Load::helper("index.mod_video_embed");
        $vid->videos($category, $sayfa);
        unset($vid);
    }

    public function video($id = null)
    {
        $vid = Load::helper("index.mod_video_embed");
        $vid->video($id);
        unset($vid);
    }

    # mod_pictures
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



    # mod_content
    public function content($seo = null)
    {
        Load::lib("Session");
        $content = Load::helper("index.mod_content");
        $content->content($seo);
        unset($content);
    }

    public function contents($category = null, $sayfa = 1)
    {
        $content = Load::helper("index.mod_content");
        $content->contents($category, $sayfa);
        unset($content);
    }

    public function arsiv()
    {
        $content = Load::helper('index.mod_content');
        $content->arsiv();
        unset($content);
    }

    public function news($seo = null)
    {
        Load::lib("Session");
        $news = Load::helper("index.mod_news");
        $news->news($seo);
        unset($news);
    }

    public function newses($category = null, $sayfa = 1)
    {
        $news = Load::helper("index.mod_news");
        $news->newses($category, $sayfa);
        unset($news);
    }

    public function news_archive()
    {
        $news = Load::helper('index.mod_news');
        $news->arsiv();
        unset($news);
    }

    public function publication($seo = null)
    {
        Load::lib("Session");
        $publication = Load::helper("index.mod_publication");
        $publication->publication($seo);
        unset($publication);
    }

    public function publications($category = null, $sayfa = 1)
    {
        $publication = Load::helper("index.mod_publication");
        $publication->publications($category, $sayfa);
        unset($publication);
    }

    public function publication_archive()
    {
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
        // unset($fullmanset);
    }



    // Aktif Anket
    public function _anket($t)
    {
        $anket = Load::helper("index.mod_survey");
        $result = $anket->_anket();
        $t->ctx_set('anket', $result);
        unset($anket);
    }

    // Anket Oy ver
    public function oyver()
    {
        $anket = Load::helper("index.mod_survey");
        return $anket->oyver();
    }

    # search
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

    #mod_menu
    public function _menu($t)
    {
        $menu = Load::helper("index.mod_menu");
        $result = $menu->menu();
        $t->ctx_set('menu', $result);
    }

    #mod_slider
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
        $helper = Load::helper('index.mod_content');
        $result = $helper->_latest_content($limit, $select);
        $t->ctx_set('latest_content', $result);
        unset($helper);
    }

    public function _latest_news($t, $limit, $select)
    {
        $helper = Load::helper('index.mod_news');
        $result = $helper->_latest_news($limit, $select);
        $t->ctx_set('latest_news', $result);
        unset($helper);
    }

    public function _latest_publications($t, $limit, $select)
    {
        $helper = Load::helper('index.mod_publication');
        $result = $helper->_latest_publications($limit, $select);
        $t->ctx_set('latest_publications', $result);
        unset($helper);
    }

    public function _latest_authors($t, $limit)
    {
        $helper = Load::helper('index.mod_publication');
        $result = $helper->_latest_authors($limit);
        $t->ctx_set('latest_authors', $result);
        unset($helper);
    }

    public function authors($page = 1)
    {
        $publication = Load::helper("index.mod_publication");
        $publication->authors($page);
        unset($publication);
    }

    public function author($username = null, $sayfa = 1)
    {
        $publication = Load::helper("index.mod_publication");
        $publication->author($username, $sayfa);
        unset($publication);
    }

    public function newsSitemap($sayfa = 1)
    {
        header("content-type: text/xml");
        $sayfa_limit = 50000;
        if (intval($sayfa) === 0) {
            $sayfa = 1;
        }
        $offset = ($sayfa - 1) * $sayfa_limit;
        $news = Load::model('mod_news');
        $news->where_status('y');
        $news->order('created_at', 'DESC');
        $news->select(['seo', 'lang']);
        $news->limit($sayfa_limit, $offset);
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

    public function publicationSitemap($sayfa = 1)
    {
        header("content-type: text/xml");
        $sayfa_limit = 50000;
        if (intval($sayfa) === 0) {
            $sayfa = 1;
        }
        $offset = ($sayfa - 1) * $sayfa_limit;
        $pub = Load::model('mod_publication');
        $pub->where_status('y');
        $pub->order('created_at', 'DESC');
        $pub->select(['seo', 'lang']);
        $pub->limit($sayfa_limit, $offset);
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

    public function categorySitemap($sayfa = 1)
    {
        header("content-type: text/xml");
        $sayfa_limit = 50000;
        if (intval($sayfa) === 0) {
            $sayfa = 1;
        }
        $offset = ($sayfa - 1) * $sayfa_limit;
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // News Categories
        $cat_news = Load::model('mod_news_category');
        $cat_news->select(['seo', 'lang']);
        $cat_news->gR();
        if ($cat_news->getSize() != 0) {
            foreach ($cat_news->toArray() as $o) {
                echo '<url>
                          <loc>' . $this->site['adres'] . $o['lang'] . '/newses/' . $o['seo'] . '</loc>
                          <priority>0.60</priority>
                    </url>';
            }
        }

        // Publication Categories
        $cat_pub = Load::model('mod_publication_category');
        $cat_pub->select(['seo', 'lang']);
        $cat_pub->gR();
        if ($cat_pub->getSize() != 0) {
            foreach ($cat_pub->toArray() as $o) {
                echo '<url>
                          <loc>' . $this->site['adres'] . $o['lang'] . '/publications/' . $o['seo'] . '</loc>
                          <priority>0.60</priority>
                    </url>';
            }
        }

        echo '</urlset>';
    }

    public function pageSitemap($page = 1)
    {
        header("content-type: text/xml");
        $pageLimit = 50000;
        if (intval($page) === 0) {
            $page = 1;
        }
        $offset = ($page - 1) * $pageLimit;
        $oyun = Load::model('mod_page');
        $oyun->where_status('y');
        $oyun->select(['seo', 'lang']);
        $oyun->limit($pageLimit, $offset);
        $oyun->gR();
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        echo '<url>
                  <loc>' . $this->site['adres'] . '</loc>
                  <priority>1.0</priority>
            </url>';
        
        if ($oyun->getSize() != 0) {
            foreach ($oyun->toArray() as $o) {
                echo '<url>
                          <loc>' . $this->site['adres'] . $o['lang'] . '/page/' . $o['seo'] . '</loc>
                          <priority>0.50</priority>
                    </url>';
            }
        }
        echo '</urlset>';
    }
}
