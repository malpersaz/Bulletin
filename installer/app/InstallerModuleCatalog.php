<?php

class InstallerModuleCatalog
{
    private $modules = array(
        'lang' => array('required' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mods' => array('required' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_settings' => array('required' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_user' => array('required' => true, 'project_specific' => false, 'dependencies' => array('mod_user_group'), 'supports_sample_content' => false),
        'mod_user_group' => array('required' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_gallery_mod' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_gallery' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_menu' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_slider' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_headline' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_news_category' => array('required' => false, 'project_specific' => true, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_publication_category' => array('required' => false, 'project_specific' => true, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_acl' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_survey' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_announcements' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_attachment' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_attachment_mod' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_video_embed_category' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_document' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_popup' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_video' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_page' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => true),
        'mod_imagecategories' => array('required' => false, 'project_specific' => false, 'dependencies' => array('mod_gallery'), 'supports_sample_content' => false),
        'mod_user_pm' => array('required' => false, 'project_specific' => false, 'dependencies' => array('mod_user'), 'supports_sample_content' => false),
        'mod_video_embed' => array('required' => false, 'project_specific' => false, 'dependencies' => array('mod_user', 'mod_video_embed_category'), 'supports_sample_content' => false),
        'mod_news' => array('required' => false, 'project_specific' => true, 'dependencies' => array('mod_news_category', 'mod_gallery', 'mod_user'), 'supports_sample_content' => true),
        'mod_publication' => array('required' => false, 'project_specific' => true, 'dependencies' => array('mod_publication_category', 'mod_gallery', 'mod_user'), 'supports_sample_content' => true),
        'mod_category' => array('required' => false, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false),
        'mod_content' => array('required' => false, 'project_specific' => false, 'dependencies' => array('mod_category', 'mod_gallery', 'mod_user'), 'supports_sample_content' => true),
    );

    private $installOrder = array(
        'lang',
        'mods',
        'mod_settings',
        'mod_user',
        'mod_user_group',
        'mod_gallery_mod',
        'mod_gallery',
        'mod_menu',
        'mod_slider',
        'mod_headline',
        'mod_news_category',
        'mod_publication_category',
        'mod_acl',
        'mod_survey',
        'mod_announcements',
        'mod_attachment',
        'mod_attachment_mod',
        'mod_video_embed_category',
        'mod_document',
        'mod_popup',
        'mod_video',
        'mod_page',
        'mod_imagecategories',
        'mod_user_pm',
        'mod_video_embed',
        'mod_news',
        'mod_publication',
        'mod_category',
        'mod_content'
    );

    public function getModules()
    {
        return $this->modules;
    }

    public function getInstallOrder()
    {
        return $this->installOrder;
    }

    public function getDefaultSelectedModules()
    {
        return $this->installOrder;
    }

    public function getSummary(array $selectedModules)
    {
        $summary = array(
            'required' => 0,
            'optional' => 0,
            'project_specific' => 0,
            'sample_content_capable' => 0,
            'total' => 0,
        );

        foreach ($selectedModules as $module) {
            if (!isset($this->modules[$module])) {
                continue;
            }

            $summary['total']++;
            if ($this->modules[$module]['required'] === true) {
                $summary['required']++;
            } else {
                $summary['optional']++;
            }

            if ($this->modules[$module]['project_specific'] === true) {
                $summary['project_specific']++;
            }

            if ($this->modules[$module]['supports_sample_content'] === true) {
                $summary['sample_content_capable']++;
            }
        }

        return $summary;
    }
}
