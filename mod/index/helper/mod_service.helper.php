<?php

class mod_service_helper extends indexBase
{
    public function __construct()
    {
        parent::__construct();
        $this->tema->ctx_set('service_lang', Load::lang('mod_service'));
    }

    public function service($seo)
    {
        $seo = trim(html_entity_decode($seo, ENT_QUOTES, 'UTF-8'));
        $service = Load::model('mod_service');
        $service->where_seo_and_status_and_lang($seo, 'y', $this->site['lang']);
        $service->limit(1)->gRo();

        if ($service->getSize() === 0) {
            return $this->_404();
        }

        $service->sync();
        if ($this->_permission_kontrol($service->acl_id)) {
            return;
        }

        $record = $service->toArray();

        $category = Load::model('mod_service_category');
        $category->where_id($record['category_id'])->limit(1)->gRo();
        if ($category->getSize() !== 0) {
            $category->sync();
            $record['category_name'] = $category->name;
            $record['category_seo'] = $category->seo;
        }

        if (isset($record['video']) && $record['video'] != '') {
            $this->_mod_video($record['video']);
        }

        if (intval($record['gallery_id']) !== 0) {
            $this->_exact_gallery($record['gallery_id']);
        } else {
            $this->_gallery_resimgetir('mod_service', $record['id']);
        }

        if (isset($record['file_id']) && intval($record['file_id']) !== 0) {
            $this->_exact_attachment($record['file_id']);
        } else {
            $this->_mod_attachment('mod_service', $record['id']);
        }

        $this->tema->ctx_set('d', $record);
        $this->tema->ctx_set('related_references', []);
        $this->tema->tpl('index/mod_service/service');
        $this->_index();
    }

    public function services($category = null, $page = 1)
    {
        $page = intval($page) === 0 ? 1 : intval($page);
        $page_limit = 12;
        $pager_limit = 5;
        $offset = ($page - 1) * $page_limit;
        $db = Load::lib('Database');

        $category_filter = '';
        $category_name = Load::lang('mod_service')['mod_service']['frontend']['all'];
        $category_seo = 'all';

        if ($category !== null && $category !== 'all') {
            $cat = Load::model('mod_service_category');
            $cat->where_seo_and_lang($category, $this->site['lang'])->limit(1)->gRo();
            if ($cat->getSize() === 0) {
                return $this->_404();
            }
            $cat->sync();
            $category_name = $cat->name;
            $category_seo = $cat->seo;
            $category_filter = " AND s.category_id = " . intval($cat->id) . " ";
            $this->tema->ctx_set('category_record', $cat->toArray());
        }

        $categories = $db->fetch_array("
            SELECT c.id, c.name, c.seo, COUNT(s.id) AS service_count
            FROM mod_service_category c
            LEFT JOIN mod_service s
              ON s.category_id = c.id
             AND s.status = 'y'
             AND s.lang = '" . $this->site['lang'] . "'
            WHERE c.lang = '" . $this->site['lang'] . "'
              AND c.root = 'n'
            GROUP BY c.id
            ORDER BY c.`order` ASC
        ")->getResult()->toArray();

        $count_sql = "
            SELECT COUNT(*) AS total_count
            FROM mod_service s
            WHERE s.status = 'y'
              AND s.lang = '" . $this->site['lang'] . "'
              " . $category_filter;
        $count_rows = $db->fetch_array($count_sql)->getResult()->toArray();
        $total_item = isset($count_rows[0]['total_count']) ? intval($count_rows[0]['total_count']) : 0;

        $services = $db->fetch_array("
            SELECT s.*, c.name AS category_name, c.seo AS category_seo
            FROM mod_service s
            LEFT JOIN mod_service_category c ON c.id = s.category_id
            WHERE s.status = 'y'
              AND s.lang = '" . $this->site['lang'] . "'
              " . $category_filter . "
            ORDER BY s.created_at DESC, s.id DESC
            LIMIT " . intval($page_limit) . " OFFSET " . intval($offset)
        )->getResult()->toArray();

        $pager = Load::lib('Pager');
        $pager->total_item = $total_item;
        $pager->page_limit = $page_limit;
        $pager->pager_limit = $pager_limit;
        $pager->page = $page;

        if ($pager->check() === false) {
            return $this->_404();
        }

        $this->tema->ctx_set('categories', $categories);
        $this->tema->ctx_set('services', $services);
        $this->tema->ctx_set('pagination', $pager->Result() ?: []);
        $this->tema->ctx_set('categoryname', $category_name);
        $this->tema->ctx_set('category_seo', $category_seo);
        $this->tema->ctx_set('page', $page);
        $this->tema->tpl('index/mod_service/services');
        $this->_index();
    }

    public function _latest_services($limit, $select)
    {
        $db = Load::lib('Database');
        $services = $db->fetch_array("
            SELECT
                s.id,
                s.name,
                s.seo,
                s.summary,
                s.image,
                s.created_at,
                s.highlighted,
                s.show_on_homepage,
                c.name AS category_name,
                c.seo AS category_seo
            FROM mod_service s
            LEFT JOIN mod_service_category c ON c.id = s.category_id
            WHERE s.status = 'y'
              AND s.lang = '" . $this->site['lang'] . "'
              AND s.show_on_homepage = 'y'
            ORDER BY
                CASE WHEN s.highlighted = 'y' THEN 0 ELSE 1 END ASC,
                s.created_at DESC,
                s.id DESC
            LIMIT " . intval($limit) . "
        ")->getResult()->toArray();

        return is_array($services) ? $services : [];
    }
}
