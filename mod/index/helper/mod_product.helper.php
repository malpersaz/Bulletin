<?php

class mod_product_helper extends indexBase
{
    public function __construct()
    {
        parent::__construct();
        $this->tema->ctx_set('product_lang', Load::lang('mod_product'));
    }

    public function product($seo)
    {
        $seo = trim(html_entity_decode($seo, ENT_QUOTES, 'UTF-8'));
        $product = Load::model('mod_product');
        $product->where_seo_and_status_and_lang($seo, 'y', $this->site['lang']);
        $product->limit(1)->gRo();

        if ($product->getSize() === 0) {
            return $this->_404();
        }

        $product->sync();
        if ($this->_permission_kontrol($product->acl_id)) {
            return;
        }

        $record = $product->toArray();

        $category = Load::model('mod_product_category');
        $category->where_id($record['category_id'])->limit(1)->gRo();
        if ($category->getSize() !== 0) {
            $category->sync();
            $record['category_name'] = $category->name;
            $record['category_seo'] = $category->seo;
        }

        $db = Load::lib('Database');
        $related = $db->fetch_array("
            SELECT cs.name, cs.seo, c.name AS client_name, c.seo AS client_seo
            FROM mod_reference cs
            LEFT JOIN mod_client c ON c.id = cs.client_id
            WHERE cs.product_id = " . intval($record['id']) . "
              AND cs.status = 'y'
              AND cs.lang = '" . $this->site['lang'] . "'
            ORDER BY cs.created_at DESC
            LIMIT 6
        ")->getResult()->toArray();

        if (isset($record['video']) && $record['video'] != '') {
            $this->_mod_video($record['video']);
        }

        if (intval($record['gallery_id']) !== 0) {
            $this->_exact_gallery($record['gallery_id']);
        } else {
            $this->_gallery_resimgetir('mod_product', $record['id']);
        }

        if (intval($record['file_id']) !== 0) {
            $this->_exact_attachment($record['file_id']);
        } else {
            $this->_mod_attachment('mod_product', $record['id']);
        }

        $this->tema->ctx_set('d', $record);
        $this->tema->ctx_set('related_references', $related);
        $this->tema->tpl('index/mod_product/product');
        $this->_index();
    }

    public function products($category = null, $page = 1)
    {
        $page = intval($page) === 0 ? 1 : intval($page);
        $page_limit = 12;
        $pager_limit = 5;
        $offset = ($page - 1) * $page_limit;
        $db = Load::lib('Database');

        $category_filter = '';
        $category_name = Load::lang('mod_product')['mod_product']['frontend']['all'];
        $category_seo = 'all';

        if ($category !== null && $category !== 'all') {
            $cat = Load::model('mod_product_category');
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
            SELECT c.id, c.name, c.seo, COUNT(s.id) AS product_count
            FROM mod_product_category c
            LEFT JOIN mod_product s
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
            FROM mod_product s
            WHERE s.status = 'y'
              AND s.lang = '" . $this->site['lang'] . "'
              " . $category_filter;
        $count_rows = $db->fetch_array($count_sql)->getResult()->toArray();
        $total_item = isset($count_rows[0]['total_count']) ? intval($count_rows[0]['total_count']) : 0;

        $products = $db->fetch_array("
            SELECT s.*, c.name AS category_name, c.seo AS category_seo
            FROM mod_product s
            LEFT JOIN mod_product_category c ON c.id = s.category_id
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
        $this->tema->ctx_set('products', $products);
        $this->tema->ctx_set('pagination', $pager->Result() ?: []);
        $this->tema->ctx_set('categoryname', $category_name);
        $this->tema->ctx_set('category_seo', $category_seo);
        $this->tema->ctx_set('page', $page);
        $this->tema->tpl('index/mod_product/products');
        $this->_index();
    }

    public function _latest_products($limit, $select)
    {
        $product = Load::model('mod_product');
        $product->select($select)->where_lang_and_status($this->site['lang'], 'y')->limit($limit)->order('created_at', 'DESC')->gR();
        return $product->getSize() !== 0 ? $product->toArray() : [];
    }
}
