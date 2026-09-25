<?php

class InstallerModuleCatalog
{
    private $modules = array(
        'lang' => array('group' => 'core_system', 'required' => true, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mods' => array('group' => 'core_system', 'required' => true, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_settings' => array('group' => 'core_system', 'required' => true, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array('mod_user'), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_user' => array('group' => 'user_access', 'required' => true, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array('mod_user_group'), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_user_group' => array('group' => 'user_access', 'required' => true, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_gallery_mod' => array('group' => 'media', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_gallery' => array('group' => 'media', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_menu' => array('group' => 'content', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => true, 'sample_content_mode' => 'module_bootstrap_optional'),
        'mod_slider' => array('group' => 'media', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_headline' => array('group' => 'content', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_news_category' => array('group' => 'project_specific', 'required' => false, 'default_selected' => true, 'project_specific' => true, 'dependencies' => array(), 'supports_sample_content' => true, 'sample_content_mode' => 'module_bootstrap_optional'),
        'mod_publication_category' => array('group' => 'project_specific', 'required' => false, 'default_selected' => true, 'project_specific' => true, 'dependencies' => array(), 'supports_sample_content' => true, 'sample_content_mode' => 'module_bootstrap_optional'),
        'mod_acl' => array('group' => 'user_access', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_survey' => array('group' => 'communication', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_announcements' => array('group' => 'communication', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_newsletter' => array('group' => 'communication', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_attachment' => array('group' => 'media', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_attachment_mod' => array('group' => 'media', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_faq_mod' => array('group' => 'content', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_faq' => array('group' => 'content', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array('mod_faq_mod', 'mod_user'), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_video_embed_category' => array('group' => 'media', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => true, 'sample_content_mode' => 'module_bootstrap_optional'),
        'mod_document' => array('group' => 'content', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_popup' => array('group' => 'communication', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_video' => array('group' => 'media', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_page' => array('group' => 'content', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => true, 'sample_content_mode' => 'legacy_inline'),
        'mod_imagecategories' => array('group' => 'media', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array('mod_gallery'), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_user_pm' => array('group' => 'communication', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array('mod_user'), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_video_embed' => array('group' => 'media', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array('mod_user', 'mod_video_embed_category'), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_news' => array('group' => 'project_specific', 'required' => false, 'default_selected' => true, 'project_specific' => true, 'dependencies' => array('mod_news_category', 'mod_gallery', 'mod_user'), 'supports_sample_content' => true, 'sample_content_mode' => 'not_available'),
        'mod_publication' => array('group' => 'project_specific', 'required' => false, 'default_selected' => true, 'project_specific' => true, 'dependencies' => array('mod_publication_category', 'mod_gallery', 'mod_user'), 'supports_sample_content' => true, 'sample_content_mode' => 'not_available'),
        'mod_content_category' => array('group' => 'content', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => true, 'sample_content_mode' => 'module_bootstrap_optional'),
        'mod_content' => array('group' => 'content', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array('mod_content_category', 'mod_gallery', 'mod_user'), 'supports_sample_content' => true, 'sample_content_mode' => 'module_bootstrap_optional'),
        'mod_seo' => array('group' => 'core_system', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
        'mod_redirect' => array('group' => 'core_system', 'required' => false, 'default_selected' => true, 'project_specific' => false, 'dependencies' => array(), 'supports_sample_content' => false, 'sample_content_mode' => 'none'),
    );

    private $installOrder = array(
        'lang',
        'mods',
        'mod_user',
        'mod_user_group',
        'mod_settings',
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
        'mod_newsletter',
        'mod_attachment',
        'mod_attachment_mod',
        'mod_faq_mod',
        'mod_faq',
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
        'mod_content_category',
        'mod_content',
        'mod_seo',
        'mod_redirect'
    );

    public function __construct()
    {
        $filteredModules = array();
        $filteredInstallOrder = array();
        $installerDir = dirname(__DIR__) . DIRECTORY_SEPARATOR;

        foreach ($this->modules as $name => $config) {
            $isCore = ($name === 'lang' || $name === 'mods' || $name === 'mod_settings');
            if ($isCore || is_dir($installerDir . $name)) {
                $filteredModules[$name] = $config;
            }
        }

        foreach ($this->installOrder as $name) {
            if (isset($filteredModules[$name])) {
                $filteredInstallOrder[] = $name;
            }
        }

        $this->modules = $filteredModules;
        $this->installOrder = $filteredInstallOrder;
    }

    public function getModules()
    {
        return $this->modules;
    }

    public function getInstallOrder()
    {
        return $this->installOrder;
    }

    public function getModule($module)
    {
        return isset($this->modules[$module]) ? $this->modules[$module] : null;
    }

    public function getAllModuleNames()
    {
        return array_keys($this->modules);
    }

    public function getRequiredModules()
    {
        $required = array();

        foreach ($this->installOrder as $module) {
            if (!empty($this->modules[$module]['required'])) {
                $required[] = $module;
            }
        }

        return $required;
    }

    public function getDefaultRequestedModules()
    {
        $selected = array();

        foreach ($this->installOrder as $module) {
            if (!empty($this->modules[$module]['default_selected']) && empty($this->modules[$module]['required'])) {
                $selected[] = $module;
            }
        }

        return $selected;
    }

    public function filterRequestedModules(array $requestedModules)
    {
        $filtered = array();
        $allowed = array_fill_keys($this->getAllModuleNames(), true);

        foreach ($requestedModules as $module) {
            if (!isset($allowed[$module])) {
                continue;
            }

            if (!empty($this->modules[$module]['required'])) {
                continue;
            }

            $filtered[$module] = true;
        }

        return array_values(array_keys($filtered));
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

            if (isset($this->modules[$module]['group'])) {
                if (!isset($summary['groups'][$this->modules[$module]['group']])) {
                    $summary['groups'][$this->modules[$module]['group']] = 0;
                }
                $summary['groups'][$this->modules[$module]['group']]++;
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
