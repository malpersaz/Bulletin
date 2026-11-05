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

    public function clear_cache($target = null)
    {
        $cache = Load::helper("index.cache_manager");
        $cache->render($target);
        unset($cache);
    }

    public function cron($target = null)
    {
        $cron = Load::helper("index.cron_manager");
        $cron->render($target);
        unset($cron);
    }

    public function us_elections()
    {
        require_once lib_dir . 'UsElectionDataService.class.php';
        $electionData = UsElectionDataService::getElectionData((string) ($this->site['lang'] ?? 'tr'));

        $pageTitle = '2026 ABD Ara Seçim Sonuçları - Canlı Güncellemeler';
        $d = [
            'name' => $pageTitle,
            'title' => $pageTitle . ' | ' . ($this->site['title'] ?? ''),
            'keywords' => 'ABD seçimleri, 2026 ABD ara seçim sonuçları, Senato, Temsilciler Meclisi, canlı sonuçlar, US Midterm elections',
            'description' => '2026 ABD ara seçimleri canlı sonuçları: Senato ve Temsilciler Meclisi oy sayımı, sandalye dağılımı, kritik eyaletler ve anlık gelişmeler.',
        ];

        $this->theme->ctx_set('d', $d);
        $this->theme->ctx_set('us_elections', $electionData);
        $this->theme->tpl('index/mod_news/us_elections_live');
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

    public function event($seo = null)
    {
        if (!$this->guardFeatureOr404('event.front')) {
            return;
        }

        Load::lib("Session");
        $event = Load::helper("index.mod_event");
        $event->event($seo);
        unset($event);
    }

    public function events($category = null, $page = 1)
    {
        if (!$this->guardFeatureOr404('event.front')) {
            return;
        }

        $event = Load::helper("index.mod_event");
        $event->events($category, $page);
        unset($event);
    }

    public function event_calendar()
    {
        if (!$this->guardFeatureOr404('event.calendar')) {
            return;
        }

        $event = Load::helper("index.mod_event");
        $event->calendar();
        unset($event);
    }

    public function event_calendar_json()
    {
        if (!$this->guardFeatureOr404('event.calendar')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            return;
        }

        $event = Load::helper("index.mod_event");
        $event->calendar_json();
        unset($event);
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



    public function _latest_news($t, $limit = 6, $select = 'id,image,name,seo,keywords,summary,created_at,video,gallery_id,file_id,category_id,category_name,category_seo', $excludeCategories = null, bool $onlyToday = false)
    {
        if (!$this->isFeatureReady('news.latest')) {
            $t->ctx_set('latest_news', []);
            return;
        }

        if ($excludeCategories === null) {
            $excludeCategories = [
                'Spor', 'Werzîş', 'Werzis', 'Sports', 'Sport', 'رياضة', 'وەرزش',
                'Dünya', 'Dunya', 'Cîhan', 'Cihan', 'World', 'Welt', 'Monde', 'Världen', 'العالم', 'جیهان',
                'Ortadoğu', 'Ortadogu', 'Rojhilata_Navîn', 'Rojhilata Navîn', 'Middle_East', 'Middle East', 'Naher_Osten', 'Naher Osten', 'Moyen-Orient', 'Mellanöstern', 'الشرق_الأوسط', 'ڕۆژهەڵاتی_ناوەڕاست',
                'Ekonomi', 'Aborî', 'Abori', 'Economy', 'Wirtschaft', 'Économie', 'Economie', 'Экономика', 'اقتصاد', 'ئابووری', 'Ekonomiye',
            ];
        }

        $helper = Load::helper('index.mod_news');
        $result = $helper->_latest_news($limit, $select, (array) $excludeCategories, $onlyToday);
        $t->ctx_set('latest_news', $result);
        unset($helper);
    }

    public function _featured_news($t, $limit = 6, $select = 'id,image,name,seo,keywords,summary,created_at,video,gallery_id,file_id,category_id,category_name,category_seo')
    {
        if (!$this->isFeatureReady('news.latest')) {
            $t->ctx_set('featured_news', []);
            return;
        }

        $helper = Load::helper('index.mod_news');
        $result = $helper->_featured_news((int) $limit, $select);
        $t->ctx_set('featured_news', $result);
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

    public function _world_news($t, $limit = 3)
    {
        if (!$this->isFeatureReady('news.latest')) {
            $t->ctx_set('world_news', []);
            $t->ctx_set('world_category_links', [
                'world_url' => $this->site['saddress'] . 'newses',
                'middle_east_url' => $this->site['saddress'] . 'newses',
            ]);
            return;
        }

        $excludeIds = [];
        $heroSlider = (is_object($t) && isset($t->ctx) && is_object($t->ctx)) ? $t->ctx->get('hero_slider') : null;
        if (is_array($heroSlider)) {
            foreach ($heroSlider as $slide) {
                if (($slide['source_mod'] ?? '') === 'mod_news' && !empty($slide['source_id'])) {
                    $excludeIds[] = (int) $slide['source_id'];
                }
            }
        }

        $helper = Load::helper('index.mod_news');
        $worldGroup = ['Dünya', 'Dunya', 'Cîhan', 'Cihan', 'World', 'Welt', 'Monde', 'Världen', 'العالم', 'جیهان'];
        $middleEastGroup = ['Ortadoğu', 'Ortadogu', 'Rojhilata_Navîn', 'Rojhilata Navîn', 'Middle_East', 'Middle East', 'Naher_Osten', 'Naher Osten', 'Moyen-Orient', 'Mellanöstern', 'الشرق_الأوسط', 'ڕۆژهەڵاتی_ناوەڕاست'];

        $result = $helper->_category_news(
            array_merge($worldGroup, $middleEastGroup),
            (int) $limit,
            'id,image,name,seo,summary,created_at,video,gallery_id,file_id,deck,category_id,category_name,category_seo',
            $excludeIds
        );

        $worldUrl = $this->site['saddress'] . 'newses';
        $middleEastUrl = $this->site['saddress'] . 'newses';
        $worldLookup = array_map('mb_strtolower', $worldGroup);
        $middleEastLookup = array_map('mb_strtolower', $middleEastGroup);

        foreach (($result['matched_categories'] ?? []) as $cat) {
            $cName = mb_strtolower(trim((string) ($cat['name'] ?? '')));
            $cSeo = mb_strtolower(trim((string) ($cat['seo'] ?? '')));
            if ((in_array($cName, $worldLookup, true) || in_array($cSeo, $worldLookup, true)) && !empty($cat['seo']) && $cat['seo'] !== '#') {
                $worldUrl = $this->site['saddress'] . 'newses/' . $cat['seo'];
            }
            if ((in_array($cName, $middleEastLookup, true) || in_array($cSeo, $middleEastLookup, true)) && !empty($cat['seo']) && $cat['seo'] !== '#') {
                $middleEastUrl = $this->site['saddress'] . 'newses/' . $cat['seo'];
            }
        }

        $t->ctx_set('world_news', $result['items']);
        $t->ctx_set('world_category_links', [
            'world_url' => $worldUrl,
            'middle_east_url' => $middleEastUrl,
        ]);
        unset($helper);
    }

    public function _market_rates($t)
    {
        require_once lib_dir . 'MarketDataService.class.php';
        $t->ctx_set('market_rates', MarketDataService::getRates((string) ($this->site['lang'] ?? 'tr')));
    }

    public function _super_league($t)
    {
        require_once lib_dir . 'SportsDataService.class.php';
        $t->ctx_set('super_league', SportsDataService::getSuperLeagueData((string) ($this->site['lang'] ?? 'tr')));
    }

    public function _us_elections($t)
    {
        require_once lib_dir . 'UsElectionDataService.class.php';
        $t->ctx_set('us_elections', UsElectionDataService::getElectionData((string) ($this->site['lang'] ?? 'tr')));
    }

    public function _economy_news($t, $limit = 3)
    {
        if (!$this->isFeatureReady('news.latest')) {
            $t->ctx_set('economy_news', []);
            $t->ctx_set('economy_category_seo', '');
            $t->ctx_set('economy_category_name', '');
            $t->ctx_set('economy_category_url', $this->site['saddress'] . 'newses');
            return;
        }

        $helper = Load::helper('index.mod_news');
        $economyGroup = [
            'Ekonomi', 'Aborî', 'Abori', 'Economy', 'Wirtschaft',
            'Économie', 'Economie', 'Экономика', 'اقتصاد', 'ئابووری', 'Ekonomiye'
        ];

        $result = $helper->_category_news(
            $economyGroup,
            (int) $limit,
            'id,image,name,seo,summary,created_at,video,gallery_id,file_id,deck,category_id,category_name,category_seo',
            [],
            false
        );

        $categorySeo = (string) ($result['category_seo'] ?? '');
        $categoryName = (string) ($result['category_name'] ?? '');
        $t->ctx_set('economy_news', $result['items']);
        $t->ctx_set('economy_category_seo', $categorySeo);
        $t->ctx_set('economy_category_name', $categoryName);
        $t->ctx_set(
            'economy_category_url',
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

    public function _event_agenda($t, $limit = 5)
    {
        if (!$this->isFeatureReady('event.front')) {
            $t->ctx_set('event_agenda', []);
            return;
        }

        $helper = Load::helper('index.mod_event');
        $result = $helper->_event_agenda($limit);
        $t->ctx_set('event_agenda', $result);
        unset($helper);
    }

    public function _latest_publications($t, $limit, $select = 'seo,name,image,created_at')
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
        $news->addWhere($this->getPublishedDateCondition(''));
        $news->addWhere("category_id IN ({$allowedCatList})");
        $news->order('created_at', 'DESC');
        $news->select(['title', 'summary', 'created_at', 'seo', 'lang', 'image']);
        $news->limit(50);
        $news->gR();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
        <channel>
            <title>' . htmlspecialchars((string) $this->site['title']) . '</title>
            <link>' . Helper::sitemap_url($this->site['address'] . $this->site['lang'] . '/') . '</link>
            <description>' . htmlspecialchars((string) $this->site['description']) . '</description>
            <language>' . $this->site['lang'] . '</language>
            <lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>
            <atom:link href="' . Helper::sitemap_url($this->site['address'] . $this->site['lang'] . '/rss.xml') . '" rel="self" type="application/rss+xml" />';

        if ($news->getSize() != 0) {
            foreach ($news->toArray() as $o) {
                $link = Helper::sitemap_url($this->site['address'] . $o['lang'] . '/news/' . $o['seo']);
                $imageUrl = "";
                $thumbUrl = "";
                $imageMime = "image/jpeg";
                if (!empty($o['image'])) {
                    $imageUrl = Helper::sitemap_url($this->site['iaddress'] . 'mod_news/' . $o['image']);
                    $thumbUrl = Helper::sitemap_url($this->site['iaddress'] . 'mod_news/thumb/' . $o['image']);
                    $ext = strtolower((string) pathinfo((string) $o['image'], PATHINFO_EXTENSION));
                    if ($ext === 'webp') {
                        $imageMime = 'image/webp';
                    } elseif ($ext === 'png') {
                        $imageMime = 'image/png';
                    } elseif ($ext === 'gif') {
                        $imageMime = 'image/gif';
                    }
                }
                
                echo '
            <item>
                <title>' . htmlspecialchars((string) $o['title']) . '</title>
                <link>' . $link . '</link>
                <description><![CDATA[' . ($thumbUrl ? '<img src="' . $thumbUrl . '" align="left" hspace="10" vspace="10" />' : '') . mb_substr(strip_tags((string) $o['summary']), 0, 500) . '...]]></description>
                <pubDate>' . date(DATE_RSS, strtotime((string) $o['created_at'])) . '</pubDate>
                <guid isPermaLink="true">' . $link . '</guid>';
                if ($imageUrl) {
                    echo '
                <media:content url="' . $imageUrl . '" medium="image" type="' . $imageMime . '" width="1280" height="720" />
                <enclosure url="' . $imageUrl . '" type="' . $imageMime . '" />';
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
        header("content-type: text/xml; charset=utf-8");
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        $active_langs = Registry::get('active_langs');
        if (!is_array($active_langs)) {
            $active_langs = array();
        }

        foreach ($active_langs as $l) {
            $latestLangTime = time();
            if ($this->isFeatureReady('news.latest')) {
                $allowedNewsCats = $this->getAllowedCategoryIds('mod_news_category', (string) $l, true);
                $allowedNewsCatList = !empty($allowedNewsCats) ? implode(',', array_map('intval', $allowedNewsCats)) : '0';
                $newsCheck = Load::model('mod_news.newses');
                $newsCheck->flush();
                $newsCheck->where_status_and_lang('y', $l);
                $newsCheck->addWhere('(acl_id = 0 OR acl_id IS NULL)');
                $newsCheck->addWhere($this->getPublishedDateCondition(''));
                $newsCheck->addWhere("category_id IN ({$allowedNewsCatList})");
                $newsCheck->order('created_at', 'DESC');
                $newsCheck->select(['created_at', 'updated_at']);
                $newsCheck->limit(1)->gR();
                $newsLastmod = date('c', $latestLangTime);
                if ($newsCheck->getSize() > 0) {
                    $newsRow = $newsCheck->toArray()[0];
                    $newsTs = strtotime((string) (!empty($newsRow['updated_at']) ? $newsRow['updated_at'] : $newsRow['created_at']));
                    if ($newsTs) {
                        $latestLangTime = $newsTs;
                        $newsLastmod = date('c', $newsTs);
                    }
                }
                echo '<sitemap>
                        <loc>' . Helper::sitemap_url($this->site['address'] . $l . '/newsSitemap.xml') . '</loc>
                        <lastmod>' . $newsLastmod . '</lastmod>
                      </sitemap>';
            }
            if ($this->isFeatureReady('publication.latest')) {
                $allowedPubCats = $this->getAllowedCategoryIds('mod_publication_category', (string) $l, true);
                $allowedPubCatList = !empty($allowedPubCats) ? implode(',', array_map('intval', $allowedPubCats)) : '0';
                $pubCheck = Load::model('mod_publication');
                $pubCheck->flush();
                $pubCheck->where_status_and_lang('y', $l);
                $pubCheck->addWhere('(acl_id = 0 OR acl_id IS NULL)');
                $pubCheck->addWhere("category_id IN ({$allowedPubCatList})");
                $pubCheck->order('created_at', 'DESC');
                $pubCheck->select(['created_at', 'updated_at']);
                $pubCheck->limit(1)->gR();
                if ($pubCheck->getSize() > 0) {
                    $pubRow = $pubCheck->toArray()[0];
                    $pubTs = strtotime((string) (!empty($pubRow['updated_at']) ? $pubRow['updated_at'] : $pubRow['created_at'])) ?: $latestLangTime;
                    echo '<sitemap>
                            <loc>' . Helper::sitemap_url($this->site['address'] . $l . '/publicationSitemap.xml') . '</loc>
                            <lastmod>' . date('c', $pubTs) . '</lastmod>
                          </sitemap>';
                }
            }
            if ($this->isFeatureReady('event.front')) {
                $allowedEventCats = $this->getAllowedCategoryIds('mod_event_category', (string) $l, true);
                $allowedEventCatList = !empty($allowedEventCats) ? implode(',', array_map('intval', $allowedEventCats)) : '0';
                $eventCheck = Load::model('mod_event');
                $eventCheck->flush();
                $eventCheck->where_status_and_lang('y', $l);
                $eventCheck->addWhere('(acl_id = 0 OR acl_id IS NULL)');
                $eventCheck->addWhere("category_id IN ({$allowedEventCatList})");
                $eventCheck->order('created_at', 'DESC');
                $eventCheck->select(['created_at', 'updated_at']);
                $eventCheck->limit(1)->gR();
                if ($eventCheck->getSize() > 0) {
                    $eventRow = $eventCheck->toArray()[0];
                    $eventTs = strtotime((string) (!empty($eventRow['updated_at']) ? $eventRow['updated_at'] : $eventRow['created_at'])) ?: $latestLangTime;
                    echo '<sitemap>
                            <loc>' . Helper::sitemap_url($this->site['address'] . $l . '/eventSitemap.xml') . '</loc>
                            <lastmod>' . date('c', $eventTs) . '</lastmod>
                          </sitemap>';
                }
            }

            $annCheck = Load::model('mod_announcements');
            $annCheck->flush();
            $annCheck->where_status_and_lang('y', $l);
            $annCheck->addWhere('(acl_id = 0 OR acl_id IS NULL)');
            $annCheck->order('created_at', 'DESC');
            $annCheck->select(['created_at']);
            $annCheck->limit(1)->gR();
            if ($annCheck->getSize() > 0) {
                $annRow = $annCheck->toArray()[0];
                $annTs = strtotime((string) $annRow['created_at']) ?: $latestLangTime;
                echo '<sitemap>
                        <loc>' . Helper::sitemap_url($this->site['address'] . $l . '/announcementSitemap.xml') . '</loc>
                        <lastmod>' . date('c', $annTs) . '</lastmod>
                      </sitemap>';
            }

            $hasVideo = false;
            $vidCheck = Load::model('mod_video');
            $vidCheck->flush();
            $vidCheck->where_status_and_lang('y', $l);
            $vidCheck->addWhere('(acl_id = 0 OR acl_id IS NULL)');
            $vidCheck->limit(1)->gR();
            if ($vidCheck->getSize() > 0) {
                $hasVideo = true;
            } elseif ($this->isFeatureReady('video_embed.latest')) {
                $vidEmbedCheck = Load::model('mod_video_embed');
                $vidEmbedCheck->flush();
                $vidEmbedCheck->where_status_and_lang('y', $l);
                $vidEmbedCheck->addWhere('(acl_id = 0 OR acl_id IS NULL)');
                $vidEmbedCheck->limit(1)->gR();
                if ($vidEmbedCheck->getSize() > 0) {
                    $hasVideo = true;
                }
            }
            if ($hasVideo) {
                echo '<sitemap>
                        <loc>' . Helper::sitemap_url($this->site['address'] . $l . '/videoSitemap.xml') . '</loc>
                        <lastmod>' . date('c', $latestLangTime) . '</lastmod>
                      </sitemap>';
            }

            echo '<sitemap>
                    <loc>' . Helper::sitemap_url($this->site['address'] . $l . '/gallerySitemap.xml') . '</loc>
                    <lastmod>' . date('c', $latestLangTime) . '</lastmod>
                  </sitemap>';
            echo '<sitemap>
                    <loc>' . Helper::sitemap_url($this->site['address'] . $l . '/categorySitemap.xml') . '</loc>
                    <lastmod>' . date('c', $latestLangTime) . '</lastmod>
                  </sitemap>';
            echo '<sitemap>
                    <loc>' . Helper::sitemap_url($this->site['address'] . $l . '/pageSitemap.xml') . '</loc>
                    <lastmod>' . date('c', $latestLangTime) . '</lastmod>
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

        header("content-type: text/xml; charset=utf-8");
        $page_limit = 50000;
        $page = (intval($page) === 0) ? 1 : intval($page);
        $offset = ($page - 1) * $page_limit;

        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', (string) $this->site['lang'], true);
        $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';

        $news = Load::model('mod_news.newses');
        $news->where_status_and_lang('y', $this->site['lang']);
        $news->addWhere('(acl_id = 0 OR acl_id IS NULL)');
        $news->addWhere($this->getPublishedDateCondition(''));
        $news->addWhere("category_id IN ({$allowedCatList})");
        $news->order('created_at', 'DESC');
        $news->select(['name', 'seo', 'lang', 'created_at', 'updated_at']);
        $news->limit($page_limit, $offset);
        $news->gR();

        $siteTitle = htmlspecialchars(html_entity_decode((string) ($this->site['title'] ?? 'Amed Bülten'), ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $langCode = htmlspecialchars((string) ($this->site['lang'] ?? 'tr'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $nowTime = time();
        $recentNewsCutoff = $nowTime - (48 * 3600);
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">';
        if ($news->getSize() != 0) {
            foreach ($news->toArray() as $o) {
                $createdTime = !empty($o['created_at']) ? strtotime($o['created_at']) : $nowTime;
                $updatedTime = !empty($o['updated_at']) ? strtotime($o['updated_at']) : $createdTime;
                if ($updatedTime < $createdTime) {
                    $updatedTime = $createdTime;
                }
                $lastmod = date('c', $updatedTime);
                $cleanTitle = htmlspecialchars(html_entity_decode(strip_tags((string) $o['name']), ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $pubDate = date('c', $createdTime);

                echo '<url>
                          <loc>' . Helper::sitemap_url($this->site['address'] . $o['lang'] . '/news/' . $o['seo']) . '</loc>
                          <lastmod>' . $lastmod . '</lastmod>';
                if ($createdTime >= $recentNewsCutoff) {
                    echo '
                          <news:news>
                            <news:publication>
                              <news:name>' . $siteTitle . '</news:name>
                              <news:language>' . $langCode . '</news:language>
                            </news:publication>
                            <news:publication_date>' . $pubDate . '</news:publication_date>
                            <news:title>' . $cleanTitle . '</news:title>
                          </news:news>';
                }
                echo '
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

        header("content-type: text/xml; charset=utf-8");
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
        $pub->select(['seo', 'lang', 'created_at', 'updated_at']);
        $pub->limit($page_limit, $offset);
        $pub->gR();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        if ($pub->getSize() != 0) {
            foreach ($pub->toArray() as $o) {
                $lastmod = date('c', strtotime($o['updated_at'] ?: $o['created_at']));
                echo '<url>
                          <loc>' . Helper::sitemap_url($this->site['address'] . $o['lang'] . '/publication/' . $o['seo']) . '</loc>
                          <lastmod>' . $lastmod . '</lastmod>
                          <priority>0.80</priority>
                    </url>';
            }
        }
        echo '</urlset>';
        exit;
    }

    public function eventSitemap($page = 1)
    {
        if (!$this->guardFeatureOr404('event.front')) {
            return;
        }

        header("content-type: text/xml; charset=utf-8");
        $page_limit = 50000;
        $page = (intval($page) === 0) ? 1 : intval($page);
        $offset = ($page - 1) * $page_limit;

        $allowedCatIds = $this->getAllowedCategoryIds('mod_event_category', (string) $this->site['lang'], true);
        $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';

        $event = Load::model('mod_event');
        $event->where_status_and_lang('y', $this->site['lang']);
        $event->addWhere('(acl_id = 0 OR acl_id IS NULL)');
        $event->addWhere("category_id IN ({$allowedCatList})");
        $event->order('start_date', 'DESC');
        $event->select(['seo', 'lang', 'created_at', 'updated_at']);
        $event->limit($page_limit, $offset);
        $event->gR();

        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        if ($event->getSize() != 0) {
            foreach ($event->toArray() as $o) {
                $lastmod = date('c', strtotime($o['updated_at'] ?: $o['created_at']));
                echo '<url>
                          <loc>' . Helper::sitemap_url($this->site['address'] . $o['lang'] . '/event/' . $o['seo']) . '</loc>
                          <lastmod>' . $lastmod . '</lastmod>
                          <priority>0.80</priority>
                    </url>';
            }
        }
        echo '</urlset>';
        exit;
    }

    public function announcementSitemap($page = 1)
    {
        header("content-type: text/xml; charset=utf-8");
        $page_limit = 50000;
        $page = (intval($page) === 0) ? 1 : intval($page);
        $offset = ($page - 1) * $page_limit;
        
        $model = Load::model('mod_announcements');
        $model->where_status_and_lang('y', $this->site['lang']);
        $model->addWhere('(acl_id = 0 OR acl_id IS NULL)');
        $model->order('created_at', 'DESC');
        $model->select(['id', 'name', 'lang', 'created_at']);
        $model->limit($page_limit, $offset);
        $model->gR();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        if ($model->getSize() != 0) {
            foreach ($model->toArray() as $o) {
                $slug = Helper::legacy_seo($o['name']);
                $lastmod = date('c', strtotime($o['created_at']));
                echo '<url>
                          <loc>' . Helper::sitemap_url($this->site['address'] . $o['lang'] . '/announcement/' . $o['id'] . '-' . $slug) . '</loc>
                          <lastmod>' . $lastmod . '</lastmod>
                          <priority>0.70</priority>
                    </url>';
            }
        }
        echo '</urlset>';
        exit;
    }

    public function videoSitemap($page = 1)
    {
        header("content-type: text/xml; charset=utf-8");
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
        $model_local->select(['id', 'name', 'lang', 'created_at']);
        $model_local->limit($page_limit, $offset);
        $model_local->gR();
        if ($model_local->getSize() != 0) {
            foreach ($model_local->toArray() as $o) {
                $slug = Helper::legacy_seo($o['name']);
                $lastmod = date('c', strtotime($o['created_at']));
                echo '<url>
                          <loc>' . Helper::sitemap_url($this->site['address'] . $o['lang'] . '/video/' . $o['id'] . '-' . $slug) . '</loc>
                          <lastmod>' . $lastmod . '</lastmod>
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
            $model_embed->select(['id', 'name', 'lang', 'created_at']);
            $model_embed->limit($page_limit, $offset);
            $model_embed->gR();
            if ($model_embed->getSize() != 0) {
                foreach ($model_embed->toArray() as $o) {
                    $slug = Helper::legacy_seo($o['name']);
                    $lastmod = date('c', strtotime($o['created_at']));
                    echo '<url>
                              <loc>' . Helper::sitemap_url($this->site['address'] . $o['lang'] . '/video/' . $o['id'] . '-' . $slug) . '</loc>
                              <lastmod>' . $lastmod . '</lastmod>
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
        header("content-type: text/xml; charset=utf-8");
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
                  <loc>' . Helper::sitemap_url($this->site['address'] . $this->site['lang'] . '/gallery') . '</loc>
                  <priority>0.70</priority>
            </url>';
            
        if ($model->getSize() != 0) {
            foreach ($model->toArray() as $o) {
                echo '<url>
                          <loc>' . Helper::sitemap_url($this->site['address'] . $o['lang'] . '/gallery/' . $o['seo']) . '</loc>
                          <priority>0.65</priority>
                    </url>';
            }
        }
        echo '</urlset>';
        exit;
    }

    public function categorySitemap($page = 1)
    {
        header("content-type: text/xml; charset=utf-8");
        
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
                                      <loc>' . Helper::sitemap_url($this->site['address'] . $o['lang'] . '/newses/' . $o['seo']) . '</loc>
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
                                      <loc>' . Helper::sitemap_url($this->site['address'] . $o['lang'] . '/publications/' . $o['seo']) . '</loc>
                                      <priority>0.60</priority>
                                </url>';
                        }
                    }
                }
            }
        }

        if ($this->isFeatureReady('event.front')) {
            $allowedEventCats = $this->getAllowedCategoryIds('mod_event_category', (string) $this->site['lang'], true);
            if (!empty($allowedEventCats)) {
                $catList = implode(',', array_map('intval', $allowedEventCats));
                $cat_event = Load::model('mod_event_category');
                $cat_event->where_lang_and_root($this->site['lang'], 'n');
                $cat_event->addWhere("id IN ({$catList})");
                $cat_event->select(['seo', 'lang']);
                $cat_event->gR();
                if ($cat_event->getSize() != 0) {
                    foreach ($cat_event->toArray() as $o) {
                        if ($o['seo'] !== '#' && $o['seo'] !== '') {
                            echo '<url>
                                      <loc>' . Helper::sitemap_url($this->site['address'] . $o['lang'] . '/events/' . $o['seo']) . '</loc>
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
        header("content-type: text/xml; charset=utf-8");
        $page_limit = 50000;
        $page = (intval($page) === 0) ? 1 : intval($page);
        $offset = ($page - 1) * $page_limit;
        
        $pages = Load::model('mod_page');
        $pages->flush();
        $pages->where_status_and_lang('y', $this->site['lang']);
        $pages->addWhere("root != 'y'");
        $pages->addWhere('(acl_id = 0 OR acl_id IS NULL)');
        $pages->select(['seo', 'lang']);
        $pages->limit($page_limit, $offset);
        $pages->gR();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $homeLastmod = date('c');
        if ($this->isFeatureReady('news.latest')) {
            $latestNewsModel = Load::model('mod_news.newses');
            $latestNewsModel->flush();
            $latestNewsModel->where_status_and_lang('y', $this->site['lang']);
            $latestNewsModel->addWhere('(acl_id = 0 OR acl_id IS NULL)');
            $latestNewsModel->addWhere($this->getPublishedDateCondition(''));
            $latestNewsModel->order('created_at', 'DESC');
            $latestNewsModel->select(['created_at', 'updated_at']);
            $latestNewsModel->limit(1)->gR();
            if ($latestNewsModel->getSize() > 0) {
                $latestNewsRow = $latestNewsModel->toArray()[0];
                $latestNewsTs = strtotime((string) (!empty($latestNewsRow['updated_at']) ? $latestNewsRow['updated_at'] : $latestNewsRow['created_at']));
                if ($latestNewsTs) {
                    $homeLastmod = date('c', $latestNewsTs);
                }
            }
        }

        if ($this->site['lang'] == Registry::get('default_lang')) {
            echo '<url>
                      <loc>' . Helper::sitemap_url($this->site['saddress']) . '</loc>
                      <lastmod>' . $homeLastmod . '</lastmod>
                      <priority>1.0</priority>
                </url>';
        }
        
        if ($pages->getSize() != 0) {
            foreach ($pages->toArray() as $o) {
                $rawSeo = (string) $o['seo'];
                if (Helper::resolve_statutory_slug($rawSeo) !== null) {
                    continue;
                }
                echo '<url>
                          <loc>' . Helper::sitemap_url($this->site['address'] . $o['lang'] . '/page/' . $o['seo']) . '</loc>
                          <priority>0.50</priority>
                    </url>';
            }
        }

        if ($page === 1 && $this->site['lang'] == Registry::get('default_lang')) {
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
            $baseAddress = rtrim((string) $this->site['address'], '/') . '/';
            foreach ($policySlugs as $pslug) {
                echo '<url>
                          <loc>' . Helper::sitemap_url($baseAddress . $pslug) . '</loc>
                          <priority>0.60</priority>
                    </url>';
            }
        }
        echo '</urlset>';
        exit;
    }

    public function legacyRedirect($seo = null)
    {
        $base = !empty($this->site['saddress']) ? $this->site['saddress'] : $this->site['address'];
        if ($seo) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . Helper::sitemap_url($base . 'news/' . $seo));
            exit();
        }
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . Helper::sitemap_url($base));
        exit();
    }

    public function legacyRedirectPublication($seo = null)
    {
        $base = !empty($this->site['saddress']) ? $this->site['saddress'] : $this->site['address'];
        if ($seo) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . Helper::sitemap_url($base . 'publication/' . $seo));
            exit();
        }
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . Helper::sitemap_url($base));
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
