<?php

class mod_reference_helper extends indexBase
{
    public function __construct()
    {
        parent::__construct();
        $this->tema->ctx_set('reference_lang', Load::lang('mod_reference'));
    }

    public function reference($seo)
    {
        $seo = trim(html_entity_decode($seo, ENT_QUOTES, 'UTF-8'));
        $db = Load::lib('Database');
        $result = $db->fetch_array("
            SELECT cs.*, c.name AS client_name, c.seo AS client_seo, p.name AS product_name, p.seo AS product_seo, sv.name AS service_name, sv.seo AS service_seo
            FROM mod_reference cs
            LEFT JOIN mod_client c ON c.id = cs.client_id
            LEFT JOIN mod_product p ON p.id = cs.product_id
            LEFT JOIN mod_service sv ON sv.id = cs.service_id
            WHERE cs.seo = '" . addslashes($seo) . "'
              AND cs.status = 'y'
              AND cs.lang = '" . $this->site['lang'] . "'
            LIMIT 1
        ")->getResult()->toArray();

        if (empty($result)) {
            return $this->_404();
        }

        $record = $result[0];

        if (intval($record['gallery_id']) !== 0) {
            $this->_gallery_resimgetir('mod_reference', $record['gallery_id']);
        } else {
            $this->_gallery_resimgetir('mod_reference', $record['id']);
        }

        $this->tema->ctx_set('d', $record);
        $this->tema->tpl('index/mod_reference/reference');
        $this->_index();
    }

    public function references($page = 1)
    {
        $page = intval($page) === 0 ? 1 : intval($page);
        $page_limit = 12;
        $pager_limit = 5;
        $offset = ($page - 1) * $page_limit;
        $db = Load::lib('Database');

        $count_rows = $db->fetch_array("
            SELECT COUNT(*) AS total_count
            FROM mod_reference
            WHERE status = 'y'
              AND lang = '" . $this->site['lang'] . "'
        ")->getResult()->toArray();
        $total_item = isset($count_rows[0]['total_count']) ? intval($count_rows[0]['total_count']) : 0;

        $items = $db->fetch_array("
            SELECT cs.*, c.name AS client_name, c.seo AS client_seo, p.name AS product_name, p.seo AS product_seo, sv.name AS service_name, sv.seo AS service_seo
            FROM mod_reference cs
            LEFT JOIN mod_client c ON c.id = cs.client_id
            LEFT JOIN mod_product p ON p.id = cs.product_id
            LEFT JOIN mod_service sv ON sv.id = cs.service_id
            WHERE cs.status = 'y'
              AND cs.lang = '" . $this->site['lang'] . "'
            ORDER BY cs.created_at DESC, cs.id DESC
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

        $this->tema->ctx_set('references', $items);
        $this->tema->ctx_set('pagination', $pager->Result() ?: []);
        $this->tema->ctx_set('page', $page);
        $this->tema->tpl('index/mod_reference/references');
        $this->_index();
    }

    public function _latest_references($limit, $select)
    {
        $db = Load::lib('Database');
        $items = $db->fetch_array("
            SELECT " . $select . "
            FROM mod_reference
            WHERE lang = '" . $this->site['lang'] . "'
              AND status = 'y'
              AND show_on_homepage = 'y'
            ORDER BY created_at DESC, id DESC
            LIMIT " . intval($limit)
        )->getResult()->toArray();

        return !empty($items) ? $items : [];
    }
}
