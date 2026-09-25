<?php

namespace E2E\Fixtures;

class ContentFixtures
{
    public static function getCategories(): array
    {
        return [
            [
                'id' => 1,
                'parent_id' => 0,
                'category_name' => 'Politics & Society',
                'slug' => 'Politics_and_Society',
                'description' => 'Political analysis and public society coverage',
                'sort_order' => 1,
                'active' => 1,
            ],
            [
                'id' => 2,
                'parent_id' => 0,
                'category_name' => 'Culture & Arts',
                'slug' => 'Culture_and_Arts',
                'description' => 'Kurdish literature, theater and music reports',
                'sort_order' => 2,
                'active' => 1,
            ],
            [
                'id' => 3,
                'parent_id' => 1,
                'category_name' => 'Regional News',
                'slug' => 'Regional_News',
                'description' => 'Local developments and municipal reports',
                'sort_order' => 1,
                'active' => 1,
            ],
        ];
    }

    public static function getContents(): array
    {
        return [
            [
                'id' => 1,
                'category_id' => 1,
                'title' => 'Historic Peace Agreement Celebrations in Diyarbakir',
                'slug' => 'Historic_Peace_Agreement_Celebrations_in_Diyarbakir',
                'summary' => 'Thousands gathered to celebrate the signing of cultural autonomy protocols.',
                'body' => '<p>The public gathered at the historical square with banners and musical performances.</p>',
                'image' => 'news_diyarbakir_peace.webp',
                'slider' => 1,
                'hero_slider' => 1,
                'featured' => 1,
                'status' => 'published',
                'author_id' => 1,
                'views_count' => 1520,
            ],
            [
                'id' => 2,
                'category_id' => 2,
                'title' => 'Kurdish Cinema Week Launches International Premiere',
                'slug' => 'Kurdish_Cinema_Week_Launches_International_Premiere',
                'summary' => 'Over thirty feature documentaries and short films scheduled for screening.',
                'body' => '<p>Filmmakers from all four regions presented their restored classics.</p>',
                'image' => 'kurdish_cinema_week.webp',
                'slider' => 1,
                'hero_slider' => 0,
                'featured' => 0,
                'status' => 'published',
                'author_id' => 2,
                'views_count' => 840,
            ],
            [
                'id' => 3,
                'category_id' => 3,
                'title' => 'New Ecological Tramway Infrastructure Announced',
                'slug' => 'New_Ecological_Tramway_Infrastructure_Announced',
                'summary' => 'Zero-emission public transport network to connect eastern suburbs.',
                'body' => '<p>Municipal engineers confirmed timeline starting next spring.</p>',
                'image' => 'tramway_infra.webp',
                'slider' => 0,
                'hero_slider' => 0,
                'featured' => 0,
                'status' => 'draft',
                'author_id' => 1,
                'views_count' => 12,
            ],
        ];
    }
}
