<?php

class legal_helper extends indexBase
{
    public function __construct()
    {
        parent::__construct();
        $this->db = Load::lib('Database');
    }

    public function render($type = null)
    {
        $type = $type !== null ? strtolower(trim((string) $type)) : 'info';
        $type = Req::Url_Clear($type);

        $validTypes = ['info', 'cookies', 'privacy', 'terms', 'warranty'];
        if (!in_array($type, $validTypes, true)) {
            $type = 'info';
        }

        $template = 'index/legal/index';
        if ($type === 'cookies') {
            $title = isset($this->lang['index']['cookie_policy']) && !empty($this->lang['index']['cookie_policy'])
                ? $this->lang['index']['cookie_policy']
                : 'Cookie Policy';
            $description = isset($this->lang['index']['cookie_policy_subtitle']) && !empty($this->lang['index']['cookie_policy_subtitle'])
                ? $this->lang['index']['cookie_policy_subtitle']
                : '';
            $canonicalUrl = $this->site['saddress'] . 'cookie-policy';
            $template = 'index/legal/cookie_policy';
        } else {
            $title = isset($this->lang['index']['legal_info']) && !empty($this->lang['index']['legal_info'])
                ? $this->lang['index']['legal_info']
                : 'Legal Information';

            $description = isset($this->lang['index']['legal_notice_desc']) && !empty($this->lang['index']['legal_notice_desc'])
                ? $this->lang['index']['legal_notice_desc']
                : (isset($this->lang['index']['legal_info_pending']) && !empty($this->lang['index']['legal_info_pending'])
                    ? $this->lang['index']['legal_info_pending']
                    : '');

            $canonicalUrl = $this->site['saddress'] . 'legal' . ($type !== 'info' ? '/' . $type : '');
        }

        $this->tema->ctx_set('legal_type', $type);
        $this->tema->ctx_set('canonical_url', $canonicalUrl);

        $this->site['title'] = $title;
        if (!empty($description)) {
            $this->site['description'] = $description;
        }

        $this->tema->set('site', $this->site);
        $this->tema->tpl($template);
        $this->_index();
    }
}
