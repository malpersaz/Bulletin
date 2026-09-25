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
}

