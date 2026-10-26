<?php
class mod_news_helper extends indexBase
{
    use PublicHelperTrait;

    public function __construct()
    {
        parent::__construct();
    }

    private function _cleanText(?string $value): string
    {
        return html_entity_decode(
            html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
    }

    private function _cleanNewsRow(array $row): array
    {
        foreach (['name', 'title', 'summary', 'description', 'keywords', 'category_name'] as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $row[$field] = $this->_cleanText($row[$field]);
            }
        }
        if (!empty($row['image'])) {
            $imageFile = root_dir . 'static' . DS . 'mod_news' . DS . $row['image'];
            if (!file_exists($imageFile) || !is_file($imageFile)) {
                $row['image'] = '';
            }
        }
        $row['has_video'] = (!empty($row['video']) && $row['video'] !== 'a:0:{}');
        $row['has_gallery'] = (isset($row['gallery_id']) && (int) $row['gallery_id'] !== 0);
        $row['has_file'] = (isset($row['file_id']) && (int) $row['file_id'] !== 0);
        $row['has_deck'] = (isset($row['deck']) && $row['deck'] === 'y');
        if (isset($row['created_at'])) {
            $row['published_ago'] = $this->formatRelativeTime($row['created_at']);
        }
        return $row;
    }

    private function _cleanNewsRows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        foreach ($rows as $index => $row) {
            if (is_array($row)) {
                $rows[$index] = $this->_cleanNewsRow($row);
            }
        }
        return $rows;
    }

    private function _checkCategoryHierarchyPermission(int $categoryId): bool
    {
        if ($categoryId <= 0) {
            return false;
        }

        $lang = (string) ($this->site['lang'] ?? '');
        $byId = $this->getCategoryHierarchyMap('mod_news_category', $lang);
        if (!isset($byId[$categoryId])) {
            $byId = $this->getCategoryHierarchyMap('mod_news_category', '');
        }

        $currentId = $categoryId;
        $visited = [];
        while ($currentId > 0 && !isset($visited[$currentId])) {
            $visited[$currentId] = true;
            if (!isset($byId[$currentId])) {
                break;
            }
            $categoryRow = $byId[$currentId];
            if ($this->_permission_check((int) $categoryRow['acl_id'])) {
                return true;
            }
            $currentId = (int) $categoryRow['parent_id'];
        }

        return false;
    }

    public function _categories()
    {
        if (!$this->isFeatureReady('news.front')) {
            $this->theme->ctx_set('categories', []);
            return;
        }

        $lang = (string) $this->site['lang'];
        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $lang);
        if (empty($allowedCatIds)) {
            $this->theme->ctx_set('categories', []);
            return;
        }

        $allowedCatList = implode(',', array_map('intval', $allowedCatIds));
        $mod_news_category_nested = Load::model('mod_news_category');
        $mod_news_category_nested->flush();
        $mod_news_category_nested->where_lang_and_root($lang, 'n');
        $mod_news_category_nested->addWhere("id IN ({$allowedCatList})");
        $mod_news_category_nested->order('order', 'ASC')->gR();

        $categories = $mod_news_category_nested->toArray();

        $countsByCategoryId = [];
        $db = Load::lib('Database');
        $safeLang = addslashes($lang);
        $aclCondition = $this->getAllowedAclCondition('');
        $db->fetch_array("SELECT `category_id`, COUNT(*) FROM `mod_news` WHERE `status` = 'y' AND `lang` = '{$safeLang}' AND {$aclCondition} AND `category_id` IN ({$allowedCatList}) GROUP BY `category_id`");
        $countRows = $db->toArray();
        if (is_array($countRows)) {
            foreach ($countRows as $cRow) {
                if (isset($cRow['category_id'])) {
                    $countsByCategoryId[(int) $cRow['category_id']] = (int) end($cRow);
                }
            }
        }

        if (is_array($categories)) {
            foreach ($categories as &$categoryItem) {
                $categoryItem['name'] = $this->_cleanText($categoryItem['name'] ?? '');
                $catId = isset($categoryItem['id']) ? (int) $categoryItem['id'] : 0;
                $categoryItem['news_count'] = $countsByCategoryId[$catId] ?? 0;
            }
            unset($categoryItem);
        }

        $this->theme->ctx_set('categories', $categories);
    }

    public function news($seo)
    {
        if (!$this->guardFeatureOr404('news.front')) {
            return;
        }

        $this->_categories();
        $seo = trim(html_entity_decode((string) $seo, ENT_QUOTES, 'UTF-8'));
        $news = Load::model('mod_news.newses');
        $news->flush();
        $news->where_seo_and_status_and_lang($seo, 'y', $this->site['lang']);
        $news->limit(1);
        $news->gRo();
        if ($news->getSize() !== 0) {
            $news->sync();
            if ($this->_permission_check((int) $news->acl_id)) {
                return;
            }
            if ($this->_checkCategoryHierarchyPermission((int) $news->category_id)) {
                return;
            }
            $this->_update_view_count('mod_news', $news->id);

            if (isset($news->video) && $news->video != '') {
                $this->_mod_video($news->video);
            }
            if (isset($news->gallery_id) && $news->gallery_id != 0) {
                $this->_exact_gallery($news->gallery_id);
            } else {
                $this->_fetch_gallery_images('mod_news', $news->id);
            }
            if (isset($news->file_id) && $news->file_id != 0) {
                $this->_exact_attachment($news->file_id);
            } else {
                $this->_mod_attachment('mod_news', $news->id);
            }

            $this->_mod_faq('mod_news', $news->id);

            $news_data = $this->_cleanNewsRow($news->toArray());
            $this->theme->ctx_set('d', $news_data);
            $this->theme->ctx_set('detail', $news_data);

            $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', (string) $this->site['lang']);
            $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';
            $aclCondition = $this->getAllowedAclCondition('');

            $news_24h_model = Load::model('mod_news');
            $news_24h_model->flush();
            $news_24h_model->select('id,image,name,seo,keywords,summary,created_at,video,gallery_id,file_id,deck')
                ->where_lang_and_status($this->site['lang'], 'y');
            if (!empty($news->id)) {
                $news_24h_model->addWhere("id != '" . (int) $news->id . "'");
            }
            $news_24h_model->addWhere($aclCondition);
            $news_24h_model->addWhere("category_id IN ({$allowedCatList})");
            $yesterday = date('Y-m-d H:i:s', time() - 86400);
            $news_24h_model->addWhere("created_at >= '" . $yesterday . "'");
            $news_24h_model->limit(8)->order('created_at', 'DESC');
            $news_24h_model->gR();
            $news_24h = [];
            if ($news_24h_model->getSize() !== 0) {
                $news_24h = $this->_cleanNewsRows($news_24h_model->toArray());
            }
            $this->theme->ctx_set('news_24h', $news_24h);
        } else {
            return $this->_404();
        }
        $this->theme->tpl('index/mod_news/news');
        $this->_index();
        unset($news);
    }

    public function newses($category = null, $page = 1)
    {
        if (!$this->guardFeatureOr404('news.front')) {
            return;
        }

        if ($category !== null && is_numeric($category)) {
            $page = intval($category);
            $category = null;
        }

        if ($page !== null && $page !== '' && !is_numeric($page)) {
            return $this->_404();
        }

        $this->_categories();

        $page_limit = 80;
        $pager_limit = 4;

        $page = max(1, intval($page ?: 1));
        $offset = ($page - 1) * $page_limit;

        $lang = (string) $this->site['lang'];
        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $lang);
        $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';
        $aclCondition = $this->getAllowedAclCondition('');

        $news = Load::model('mod_news.newses');
        $news->flush();
        $category_model = Load::model('mod_news_category');
        $category_model->flush();

        $allNewsLabel = $this->lang['index']['mod_news']['all_news'];

        if ($category === null || $category === '' || $category === 'all') {
            $news->where_status_and_lang('y', $lang);
            $news->addWhere($aclCondition);
            $news->addWhere("category_id IN ({$allowedCatList})");
            $news->all();
            $total_item = $news->getSize();

            $news->flush();
            $news->where_status_and_lang('y', $lang);
            $news->addWhere($aclCondition);
            $news->addWhere("category_id IN ({$allowedCatList})");
            $news->limit($page_limit, $offset)->order('created_at', 'DESC')->gR();

            if ($news->getSize() != 0) {
                $news_items = $this->_cleanNewsRows($news->toArray());
                $this->theme->ctx_set('newses', $news_items);
            }
            $category_seo = 'all';
            $categoryname = $allNewsLabel;
        } else {
            $category_model->where_seo_and_lang($category, $lang)->limit(1)->gRo();
            if ($category_model->getSize() === 0) {
                return $this->_404();
            }
            $category_model->sync();
            $categoryId = (int) $category_model->id;
            $categoryRecord = $category_model->toArray();
            if (isset($categoryRecord['name'])) {
                $categoryRecord['name'] = $this->_cleanText($categoryRecord['name']);
            }
            $categoryname = $this->_cleanText($category_model->name);
            $category_seo = $category_model->seo;

            if ($this->_checkCategoryHierarchyPermission($categoryId)) {
                return;
            }

            $descendantIds = $this->getCategoryDescendantIds($categoryId, $lang, 'mod_news_category');
            $targetCategoryIds = array_values(array_intersect($descendantIds, $allowedCatIds));
            $idList = !empty($targetCategoryIds) ? implode(',', array_map('intval', $targetCategoryIds)) : '0';

            $news->where_status_and_lang('y', $lang);
            $news->addWhere($aclCondition);
            $news->addWhere("category_id IN ({$idList})");
            $news->order('created_at', 'DESC')->gR();
            $total_item = $news->getSize();
            $news->getLimit($page_limit, $offset);
            if ($news->getSize() !== 0) {
                $news_items = $this->_cleanNewsRows($news->toArray());
                $this->theme->ctx_set('newses', $news_items);
            }

            $this->theme->ctx_set('category_record', $categoryRecord);
        }

        $p = Load::lib('Pager');
        $p->total_item = $total_item;
        $p->page_limit = $page_limit;
        $p->pager_limit = $pager_limit;
        $p->page = $page;

        if ($p->check() === false) {
            return $this->_404();
        }

        if ($p->Result() !== false) {
            $this->theme->ctx_set('pagination', $p->Result());
        }

        $categorySlug = ($category === null || $category === '') ? 'all' : $category;
        if ($page > 1) {
            $d['link'] = $this->site['saddress'] . 'newses/' . $categorySlug . '/' . $page;
        } else {
            $d['link'] = $this->site['saddress'] . 'newses/' . $categorySlug;
        }

        $archivePageTemplate = $this->lang['index']['mod_news']['archive_page_title'];
        $singlePageTemplate = $this->lang['index']['mod_news']['title_format'];

        if ($page <= 1) {
            $d['title'] = trim(sprintf($singlePageTemplate, $categoryname));
        } else {
            $d['title'] = trim(sprintf($archivePageTemplate, $categoryname, $page));
        }

        $d['description'] = $allNewsLabel;
        $d['keywords'] = $this->lang['index']['mod_news']['keywords'];

        $this->theme->ctx_set('d', $d);
        $this->theme->ctx_set('categoryname', $categoryname);
        $this->theme->ctx_set('category_seo', $category_seo);
        $this->theme->ctx_set('page', $page);
        $this->theme->tpl('index/mod_news/newses');

        $this->_index();
    }

    public function _latest_news($limit, $select)
    {
        if (!$this->isFeatureReady('news.latest')) {
            return [];
        }

        $lang = (string) $this->site['lang'];
        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $lang);
        $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';
        $aclCondition = $this->getAllowedAclCondition('');

        $m = Load::model('mod_news.newses');
        $m->flush();
        $m->select($select)->where_lang_and_status($lang, 'y');
        $m->addWhere("slider='n'");
        $m->addWhere($aclCondition);
        $m->addWhere("category_id IN ({$allowedCatList})");
        $m->limit($limit)->order('created_at', 'DESC');
        $m->gR();
        if ($m->getSize() !== 0) {
            return $this->_cleanNewsRows($m->toArray());
        }

        return [];
    }

    public function _category_news(array $categoryCandidates, int $limit = 3, string $select = 'id,image,name,seo,summary,created_at,video,gallery_id,file_id,deck,category_id,category_name,category_seo', array $excludeIds = []): array
    {
        if (!$this->isFeatureReady('news.latest')) {
            return ['items' => [], 'category_name' => '', 'category_seo' => '', 'matched_categories' => []];
        }

        $lang = (string) $this->site['lang'];
        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $lang);
        if (empty($allowedCatIds)) {
            return ['items' => [], 'category_name' => '', 'category_seo' => '', 'matched_categories' => []];
        }

        $allowedCatList = implode(',', array_map('intval', $allowedCatIds));
        $catModel = Load::model('mod_news_category');
        $catModel->flush();
        $catModel->where_lang($lang);
        $catModel->addWhere("id IN ({$allowedCatList})");
        $catModel->gR();

        $matchedCategoryIds = [];
        $matchedCategories = [];
        $matchedCategoryName = '';
        $matchedCategorySeo = '';

        if ($catModel->getSize() > 0) {
            $categories = $catModel->toArray();
            $candidateLookup = array_map(function ($c) {
                return mb_strtolower(trim((string) $c));
            }, $categoryCandidates);

            foreach ($categories as $cat) {
                $cName = mb_strtolower(trim((string) ($cat['name'] ?? '')));
                $cSeo = mb_strtolower(trim((string) ($cat['seo'] ?? '')));
                if (in_array($cName, $candidateLookup, true) || in_array($cSeo, $candidateLookup, true)) {
                    $catId = (int) $cat['id'];
                    $catNameClean = $this->_cleanText((string) ($cat['name'] ?? ''));
                    $catSeoClean = (string) ($cat['seo'] ?? '');
                    $matchedCategoryIds[] = $catId;
                    $matchedCategories[] = [
                        'id' => $catId,
                        'name' => $catNameClean,
                        'seo' => $catSeoClean,
                    ];
                    if ($matchedCategoryName === '') {
                        $matchedCategoryName = $catNameClean;
                        $matchedCategorySeo = $catSeoClean;
                    }
                }
            }
        }

        if (empty($matchedCategoryIds)) {
            return ['items' => [], 'category_name' => '', 'category_seo' => '', 'matched_categories' => []];
        }

        $targetCategoryIds = $matchedCategoryIds;
        foreach ($matchedCategoryIds as $matchedCatId) {
            $descendants = $this->getCategoryDescendantIds($matchedCatId, $lang, 'mod_news_category');
            if (!empty($descendants)) {
                $targetCategoryIds = array_merge($targetCategoryIds, $descendants);
            }
        }
        $targetCategoryIds = array_values(array_unique($targetCategoryIds));
        $targetCategoryIds = array_intersect($targetCategoryIds, $allowedCatIds);
        if (empty($targetCategoryIds)) {
            return [
                'items' => [],
                'category_name' => $matchedCategoryName,
                'category_seo' => $matchedCategorySeo,
                'matched_categories' => $matchedCategories,
            ];
        }

        $targetCatList = implode(',', array_map('intval', $targetCategoryIds));
        $aclCondition = $this->getAllowedAclCondition('');

        $m = Load::model('mod_news.newses');
        $m->flush();
        $m->select($select)->where_lang_and_status($lang, 'y');
        $m->addWhere("slider='n'");
        $cleanExcludeIds = array_values(array_unique(array_filter(array_map('intval', $excludeIds), function ($id) {
            return $id > 0;
        })));
        if (!empty($cleanExcludeIds)) {
            $m->addWhere("id NOT IN (" . implode(',', $cleanExcludeIds) . ")");
        }
        $m->addWhere($aclCondition);
        $m->addWhere("category_id IN ({$targetCatList})");
        $m->limit($limit)->order('created_at', 'DESC');
        $m->gR();

        $items = [];
        if ($m->getSize() !== 0) {
            $rawRows = $this->_cleanNewsRows($m->toArray());
            foreach ($rawRows as $row) {
                $summaryPlain = trim(strip_tags((string) ($row['summary'] ?? '')));
                $row['summary_short'] = $summaryPlain;
                $items[] = $row;
            }
        }

        return [
            'items' => $items,
            'category_name' => $matchedCategoryName,
            'category_seo' => $matchedCategorySeo,
            'matched_categories' => $matchedCategories,
        ];
    }

    public function _homepage_categories($limit = 4, $select = 'image,name,seo,summary,created_at,deck')
    {
        if (!$this->isFeatureReady('news.homepage_categories')) {
            return [];
        }

        $lang = (string) $this->site['lang'];
        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $lang);
        if (empty($allowedCatIds)) {
            return [];
        }

        $allowedCatList = implode(',', array_map('intval', $allowedCatIds));
        $aclCondition = $this->getAllowedAclCondition('');

        $cat_model = Load::model('mod_news_category');
        $cat_model->flush();
        $cat_model->where_lang_and_show_on_homepage_and_root($lang, 'y', 'n');
        $cat_model->addWhere("id IN ({$allowedCatList})");
        $cat_model->order('order', 'ASC')->gR();

        if ($cat_model->getSize() === 0) {
            return [];
        }

        $categories_raw = $cat_model->toArray();
        $news_model = Load::model('mod_news.newses');

        $categories = [];
        $seen_ids = [];

        foreach ($categories_raw as $categoryItem) {
            if (in_array($categoryItem['id'], $seen_ids)) {
                continue;
            }
            if (empty($categoryItem['seo'])) {
                continue;
            }
            $seen_ids[] = $categoryItem['id'];

            $news_model->flush();
            $news_model->where_category_seo_and_status_and_lang($categoryItem['seo'], 'y', $lang);
            $news_model->addWhere("slider='n'");
            $news_model->addWhere($aclCondition);
            $news_model->select($select)->order('created_at', 'DESC')->limit($limit);
            $news_model->gR();

            $items = $this->_cleanNewsRows($news_model->toArray());
            $categoryItem['name'] = $this->_cleanText($categoryItem['name'] ?? '');
            $categoryItem['news'] = $items;
            $categories[] = $categoryItem;
        }

        return $categories;
    }

    private function formatRelativeTime($datetime)
    {
        return Helper::time_ago($datetime, $this->lang['index'] ?? null);
    }

    public function archive()
    {
        if (!$this->guardFeatureOr404('news.archive')) {
            return;
        }

        $lang = (string) $this->site['lang'];
        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $lang);
        $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';
        $aclCondition = $this->getAllowedAclCondition('');

        $news = Load::model('mod_news');
        $news->flush();
        $sql = "SELECT MAX(created_at), MIN(created_at) FROM `mod_news` WHERE 1";
        $news->fetch_array($sql);
        $row = $news->getResultOne();
        $row_data = is_object($row) ? $row->toArray() : (array) $row;
        $values = array_values($row_data);
        $max_date = $values[0] ?? null;
        $min_date = $values[1] ?? null;
        $min_year = $min_date ? (int) date('Y', strtotime($min_date)) : 2020;
        $max_year = $max_date ? (int) date('Y', strtotime($max_date)) : (int) date('Y');
        $archiveYears = [
            'min_year' => $min_year,
            'max_year' => $max_year
        ];
        $this->theme->ctx_set('archive_years', $archiveYears);

        $category = Load::model('mod_news_category');
        $category->flush();
        $category->where_lang_and_root($lang, 'n');
        $category->addWhere("id IN ({$allowedCatList})");
        $category->order('parent_id, order', 'DESC');
        $category->gR();
        if ($category->getSize() !== 0) {
            $archiveCategories = $category->toArray();
            if (is_array($archiveCategories)) {
                foreach ($archiveCategories as &$catRow) {
                    $catRow['name'] = $this->_cleanText($catRow['name'] ?? '');
                }
                unset($catRow);
            }
            $this->theme->ctx_set('archive_categories', $archiveCategories);
        }

        $archiveTitle = $this->lang['index']['mod_news']['archive_title'];
        $d['name'] = $archiveTitle;
        $d['title'] = $archiveTitle;
        $d['keywords'] = $archiveTitle;
        $d['description'] = $archiveTitle;
        $this->theme->ctx_set('d', $d);
        $this->theme->tpl('index/mod_news/archive');

        if (Req::is_Post('category_id') && Req::is_Post('year') && Req::is_Post('month')) {
            $rawYear = Req::Post_Safe('year');
            $rawMonth = Req::Post_Safe('month');
            $rawCategoryId = Req::Post_Safe('category_id');

            $year = ($rawYear === '*' || $rawYear === '') ? null : intval($rawYear);
            $month = ($rawMonth === '*' || $rawMonth === '') ? null : intval($rawMonth);
            $categoryId = ($rawCategoryId === '*' || $rawCategoryId === '') ? '*' : intval($rawCategoryId);

            $safeLang = addslashes($lang);
            if ($categoryId === '*') {
                $categoryWhere = " AND category_id IN ({$allowedCatList})";
            } elseif (in_array((int) $categoryId, $allowedCatIds, true)) {
                $categoryWhere = " AND category_id='" . intval($categoryId) . "'";
            } else {
                $categoryWhere = " AND category_id=0";
            }

            $this->theme->ctx_set('category_id', $categoryId);

            $news->flush();
            $news->select('seo,name,created_at,summary');
            if ($year !== null && $month !== null) {
                $news->where(
                    "YEAR(created_at)='" . $year . "' AND MONTH(created_at)='" . $month . "' AND lang='" . $safeLang . "' AND status = 'y' AND " . $aclCondition . $categoryWhere
                );
            } elseif ($year !== null) {
                $news->where(
                    "YEAR(created_at)='" . $year . "' AND lang='" . $safeLang . "' AND status = 'y' AND " . $aclCondition . $categoryWhere
                );
            } elseif ($month !== null) {
                $news->where(
                    "MONTH(created_at)='" . $month . "' AND lang='" . $safeLang . "' AND status = 'y' AND " . $aclCondition . $categoryWhere
                );
            } else {
                $news->where("status = 'y' AND lang='" . $safeLang . "' AND " . $aclCondition . $categoryWhere);
            }

            $news->gR();
            if ($news->getSize() !== 0) {
                $this->theme->ctx_set('newses', $this->_cleanNewsRows($news->toArray()));
            } else {
                $this->theme->ctx_set('not_found', true);
            }

            if ($year !== null) {
                $this->theme->ctx_set('year', $year);
            }
            if ($month !== null) {
                $this->theme->ctx_set('month', $month);
            }
        }
        $this->theme->tpl('index/mod_news/archive');
        $this->_index();
    }
}
