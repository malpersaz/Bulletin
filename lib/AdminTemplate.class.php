<?php
/**
 * Amed Framework Admin Template Engine
 * Extends TwigTemplate for administrative views
 */
if (defined('root_dir') === false) {
    exit(':)');
}

require_once __DIR__ . '/TwigTemplate.class.php';
class AdminTemplate extends TwigTemplate
{
    public function __construct()
    {
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            @header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
            @header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
            @header('Pragma: no-cache');
        }
        parent::__construct();
        $this->lang(Load::lang('management'));
        $this->lang(Load::lang('mod_newsletter'));
        $this->lang(Load::lang('mod_settings'));
        $this->lang(Load::lang('mod_user'));
        $isAjax = Req::is_Ajax() || (isset($_GET['ajax']) && (string)$_GET['ajax'] === '1');
        $this->set('is_ajax', $isAjax);
    }
}
