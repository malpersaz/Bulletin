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
        parent::__construct();
        $this->lang(Load::lang('management'));
        $this->lang(Load::lang('mod_newsletter'));
        $this->lang(Load::lang('mod_settings'));
        $this->lang(Load::lang('mod_user'));
    }
}
