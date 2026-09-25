<?php

namespace E2E\Fixtures;

class ModuleFixtures
{
    public static function getActiveModules(): array
    {
        return [
            'mod_content' => [
                'name' => 'mod_content',
                'title' => 'Content Management',
                'controller' => 'mod_content_mod',
                'model' => 'mod_content_model',
                'table' => 'mod_content',
                'has_faq' => true,
                'has_slider' => true,
            ],
            'mod_content_category' => [
                'name' => 'mod_content_category',
                'title' => 'Content Categories',
                'controller' => 'mod_content_category_mod',
                'model' => 'mod_content_category_model',
                'table' => 'mod_content_category',
            ],
            'mod_seo' => [
                'name' => 'mod_seo',
                'title' => 'Search Engine Optimization',
                'controller' => 'mod_seo_mod',
                'table' => 'mod_seo',
            ],
            'mod_faq' => [
                'name' => 'mod_faq',
                'title' => 'Frequently Asked Questions',
                'controller' => 'mod_faq_mod',
                'table' => 'mod_faq',
            ],
            'mod_newsletter' => [
                'name' => 'mod_newsletter',
                'title' => 'Newsletter Subscriptions',
                'controller' => 'mod_newsletter_mod',
                'model' => 'mod_newsletter_model',
                'table' => 'mod_newsletter',
            ],
            'mod_settings' => [
                'name' => 'mod_settings',
                'title' => 'System Settings',
                'controller' => 'mod_settings_mod',
                'table' => 'mod_settings',
            ],
            'mod_user' => [
                'name' => 'mod_user',
                'title' => 'User Management',
                'controller' => 'mod_user_mod',
                'table' => 'mod_user',
            ],
            'mod_acl' => [
                'name' => 'mod_acl',
                'title' => 'Access Control List',
                'controller' => 'mod_acl_mod',
                'table' => 'mod_acl_roles',
            ],
        ];
    }

    public static function getDeprecatedModules(): array
    {
        return [
            'mod_client',
            'mod_service',
            'mod_service_category',
            'mod_product',
            'mod_product_category',
            'mod_reference',
            'mod_reference_category',
        ];
    }

    public static function getModernizedModuleList(): array
    {
        return array_keys(self::getActiveModules());
    }
}
