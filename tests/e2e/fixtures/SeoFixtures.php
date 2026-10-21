<?php

namespace E2E\Fixtures;

class SeoFixtures
{
    public static function getSeoRoutes(): array
    {
        return [
            [
                'id' => 1,
                'route_path' => '/',
                'title' => 'Bulletin — Independent News and Cultural Review',
                'meta_description' => 'Daily verified reporting on culture, politics, and society across Kurdistan and diaspora.',
                'meta_keywords' => 'bulletin, news, kurdish news, diyarbakir, hewler, mahabad, qamishlo',
                'canonical_url' => 'https://bulletin.org/',
                'og_title' => 'Bulletin — Independent News and Cultural Review',
                'og_description' => 'Daily verified reporting on culture, politics, and society.',
                'og_image' => 'https://bulletin.org/assets/og_cover.webp',
                'robots' => 'index, follow',
            ],
            [
                'id' => 2,
                'route_path' => '/content/Politics_and_Society',
                'title' => 'Politics & Society News | Bulletin',
                'meta_description' => 'In-depth political analysis, municipal governance, and public sector reporting.',
                'meta_keywords' => 'politics, elections, diplomacy, human rights',
                'canonical_url' => 'https://bulletin.org/content/Politics_and_Society',
                'og_title' => 'Politics & Society News | Bulletin',
                'og_description' => 'In-depth political analysis.',
                'og_image' => 'https://bulletin.org/assets/og_politics.webp',
                'robots' => 'index, follow',
            ],
        ];
    }

    public static function getRobotsTxtContent(): string
    {
        return "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /installer/\nSitemap: https://bulletin.org/sitemap.xml\n";
    }
}
