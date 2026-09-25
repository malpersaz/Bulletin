<?php

namespace E2E\Fixtures;

class LanguageFixtures
{
    public static function getSupportedLanguages(): array
    {
        return ['ar', 'ckb', 'de', 'en', 'es', 'fr', 'ku', 'sv', 'tr'];
    }

    public static function getCorporateFields(): array
    {
        return [
            'legal_company_name',
            'legal_trading_name',
            'company_registration_number',
            'tax_office',
            'tax_number',
            'trade_registry_number',
            'central_registration_number',
            'registered_email',
            'corporate_address',
            'managing_director',
            'editor_in_chief',
            'dpo_email',
            'press_code_license',
            'hosting_provider',
            'vat_number',
            'compliance_jurisdiction',
        ];
    }

    public static function getKurdishCanonicalVerbs(): array
    {
        return [
            'add' => 'Lêke',
            'add_full' => 'Lêkirin',
            'save' => 'Tomar bike',
            'delete' => 'Jê bibe',
            'edit' => 'Biguherîne',
            'update' => 'Nûjenkirin',
            'click' => 'Bitikîne',
            'clicked' => 'Hat tikandin',
        ];
    }

    public static function getForbiddenTurkishKeys(): array
    {
        return [
            'vazgec',
            'kaydet',
            'sil',
            'hata',
            'hatalar',
            'guncelle',
            'ekle',
            'processSil',
            'processDosya',
        ];
    }

    public static function getExcludedModules(): array
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

    public static function loadModuleLang(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }
        $lang = [];
        include $file;
        return is_array($lang) ? $lang : [];
    }

    public static function loadInstallerLang(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }
        if (!defined('AKM_LOADED')) {
            define('AKM_LOADED', true);
        }
        $installerLang = [];
        include $file;
        return is_array($installerLang) ? $installerLang : [];
    }
}
