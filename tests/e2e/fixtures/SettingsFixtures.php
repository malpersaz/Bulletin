<?php

namespace E2E\Fixtures;

class SettingsFixtures
{
    public static function getSettings(): array
    {
        return [
            'site_title' => 'Rojname News Portal',
            'site_status' => 'online',
            'maintenance_message' => 'System modernization in progress. Returning shortly.',
            'legal_company_name' => 'Rojname Media Publishing Ltd.',
            'legal_tax_number' => 'ROJ-9823419-M',
            'legal_address' => 'Sur District, Diyarbakir / Amed',
            'legal_email' => 'contact@rojname.org',
            'legal_phone' => '+90 412 555 0199',
            'social_twitter' => 'https://twitter.com/rojname_news',
            'social_facebook' => 'https://facebook.com/rojname.official',
            'social_youtube' => 'https://youtube.com/@rojname',
            'theme_logo' => 'templates/amida/assets/img/logo.svg',
            'theme_favicon' => 'templates/amida/assets/img/favicon.ico',
        ];
    }

    public static function getLegalInfoPayload(): array
    {
        return [
            'company_name' => 'Rojname Media Publishing Ltd.',
            'tax_number' => 'ROJ-9823419-M',
            'address' => 'Sur District, Diyarbakir / Amed',
            'email' => 'legal@rojname.org',
            'phone' => '+90 412 555 0199',
            'managing_director' => 'Rezan Azad',
        ];
    }
}
