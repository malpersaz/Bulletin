<?php

namespace E2E\Fixtures;

class SettingsFixtures
{
    public static function getSettings(): array
    {
        return [
            'site_title' => 'Bulletin News Portal',
            'site_status' => 'online',
            'maintenance_message' => 'System modernization in progress. Returning shortly.',
            'legal_company_name' => 'Bulletin Media Publishing Ltd.',
            'legal_tax_number' => 'BUL-9823419-M',
            'legal_address' => 'Sur District, Diyarbakir / Amed',
            'legal_email' => 'contact@bulletin.org',
            'legal_phone' => '+90 412 555 0199',
            'social_twitter' => 'https://twitter.com/bulletin_news',
            'social_facebook' => 'https://facebook.com/bulletin.official',
            'social_youtube' => 'https://youtube.com/@bulletin',
            'theme_logo' => 'templates/amida/assets/img/logo.svg',
            'theme_favicon' => 'templates/amida/assets/img/favicon.ico',
        ];
    }

    public static function getLegalInfoPayload(): array
    {
        return [
            'company_name' => 'Bulletin Media Publishing Ltd.',
            'tax_number' => 'BUL-9823419-M',
            'address' => 'Sur District, Diyarbakir / Amed',
            'email' => 'legal@bulletin.org',
            'phone' => '+90 412 555 0199',
            'managing_director' => 'Rezan Azad',
        ];
    }
}
