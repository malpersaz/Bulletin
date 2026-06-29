<?php

class mod_client_helper extends indexBase
{
    public function __construct()
    {
        parent::__construct();
        $this->tema->ctx_set('client_lang', Load::lang('mod_client'));
    }

    public function client($seo)
    {
        $seo = trim(html_entity_decode($seo, ENT_QUOTES, 'UTF-8'));
        $client = Load::model('mod_client');
        $client->where_seo_and_status_and_lang($seo, 'y', $this->site['lang']);
        $client->limit(1)->gRo();

        if ($client->getSize() === 0) {
            return $this->_404();
        }

        $client->sync();
        $record = $client->toArray();

        $db = Load::lib('Database');
        $related = $db->fetch_array("
            SELECT cs.name, cs.seo, s.name AS product_name, s.seo AS product_seo
            FROM mod_reference cs
            LEFT JOIN mod_product s ON s.id = cs.product_id
            WHERE cs.client_id = " . intval($record['id']) . "
              AND cs.status = 'y'
              AND cs.lang = '" . $this->site['lang'] . "'
            ORDER BY cs.created_at DESC
            LIMIT 6
        ")->getResult()->toArray();

        $this->tema->ctx_set('d', $record);
        $this->tema->ctx_set('related_references', $related);
        $this->tema->tpl('index/mod_client/client');
        $this->_index();
    }

    public function clients()
    {
        $client = Load::model('mod_client');
        $client->where_lang_and_status($this->site['lang'], 'y')->order('created_at', 'DESC')->gR();

        $this->tema->ctx_set('clients', $client->getSize() !== 0 ? $client->toArray() : []);
        $this->tema->tpl('index/mod_client/clients');
        $this->_index();
    }

    public function _latest_clients($limit, $select)
    {
        $db = Load::lib('Database');
        $items = $db->fetch_array("
            SELECT " . $select . "
            FROM mod_client
            WHERE lang = '" . $this->site['lang'] . "'
              AND status = 'y'
              AND show_on_homepage = 'y'
            ORDER BY created_at DESC, id DESC
            LIMIT " . intval($limit)
        )->getResult()->toArray();

        return !empty($items) ? $items : [];
    }
}
