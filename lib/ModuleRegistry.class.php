<?php

class ModuleRegistry
{
    private $featureMap = [
        'news.front' => [
            'modules' => ['mod_news', 'mod_news_category'],
            'tables' => ['mod_news', 'mod_news_category'],
        ],
        'news.latest' => [
            'modules' => ['mod_news'],
            'tables' => ['mod_news'],
        ],
        'news.archive' => [
            'modules' => ['mod_news', 'mod_news_category'],
            'tables' => ['mod_news', 'mod_news_category'],
        ],
        'news.homepage_categories' => [
            'modules' => ['mod_news', 'mod_news_category'],
            'tables' => ['mod_news', 'mod_news_category'],
        ],
        'news.feed' => [
            'modules' => ['mod_news'],
            'tables' => ['mod_news'],
        ],
        'publication.front' => [
            'modules' => ['mod_publication', 'mod_publication_category'],
            'tables' => ['mod_publication', 'mod_publication_category'],
        ],
        'publication.latest' => [
            'modules' => ['mod_publication'],
            'tables' => ['mod_publication'],
        ],
        'publication.authors' => [
            'modules' => ['mod_publication'],
            'tables' => ['mod_publication'],
        ],
        'publication.archive' => [
            'modules' => ['mod_publication', 'mod_publication_category'],
            'tables' => ['mod_publication', 'mod_publication_category'],
        ],
        'video_embed.front' => [
            'modules' => ['mod_video_embed', 'mod_video_embed_category'],
            'tables' => ['mod_video_embed', 'mod_video_embed_category'],
        ],
        'video_embed.latest' => [
            'modules' => ['mod_video_embed'],
            'tables' => ['mod_video_embed'],
        ],
    ];

    private $installDependencies = [
        'mod_news' => [
            'modules' => ['mod_news_category', 'mod_gallery', 'mod_user'],
            'tables' => ['mod_news_category', 'mod_gallery', 'mod_user'],
        ],
        'mod_publication' => [
            'modules' => ['mod_publication_category', 'mod_gallery', 'mod_user'],
            'tables' => ['mod_publication_category', 'mod_gallery', 'mod_user'],
        ],
        'mod_video_embed' => [
            'modules' => ['mod_video_embed_category', 'mod_user'],
            'tables' => ['mod_video_embed_category', 'mod_user'],
        ],
    ];

    private $uninstallDependents = [
        'mod_news_category' => ['mod_news'],
        'mod_publication_category' => ['mod_publication'],
        'mod_video_embed_category' => ['mod_video_embed'],
    ];

    public function getFeatureConfig($feature)
    {
        return isset($this->featureMap[$feature]) ? $this->featureMap[$feature] : null;
    }

    public function getInstallDependencyConfig($module)
    {
        return isset($this->installDependencies[$module]) ? $this->installDependencies[$module] : null;
    }

    public function getUninstallDependents($module)
    {
        return isset($this->uninstallDependents[$module]) ? $this->uninstallDependents[$module] : [];
    }
}
