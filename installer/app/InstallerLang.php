<?php

class InstallerLang
{
    const CACHE_KEY_PREFIX = 'installer-lang-';

    public static function load($languageCode = null)
    {
        $languageCode = $languageCode ?: self::current();
        $cacheKey = self::CACHE_KEY_PREFIX . $languageCode;

        if (Registry::has($cacheKey) === TRUE) {
            return Registry::get($cacheKey);
        }

        $file = installer_dir . 'lang' . DS . $languageCode . '.php';
        if (file_exists($file) === FALSE) {
            throw new Exception("Installer language file '" . $languageCode . "' was not found");
        }

        $installerLang = [];
        require($file);

        if (empty($installerLang) || is_array($installerLang) === FALSE) {
            throw new Exception("Installer language file '" . $languageCode . "' is invalid");
        }

        $installerLang = installer_replace_build_name($installerLang);

        Registry::add($cacheKey, $installerLang);
        return $installerLang;
    }

    public static function current()
    {
        if (Registry::has('lang') === TRUE) {
            return (string) Registry::get('lang');
        }

        return 'en';
    }

    public static function reset($languageCode = null)
    {
        if ($languageCode !== null) {
            Registry::remove(self::CACHE_KEY_PREFIX . $languageCode);
            return;
        }

        foreach (array_keys(self::availableLanguages()) as $code) {
            Registry::remove(self::CACHE_KEY_PREFIX . $code);
        }
    }

    public static function availableLanguages()
    {
        return array(
            'ar' => array('label' => 'العربية', 'code' => 'AR'),
            'de' => array('label' => 'Deutsch', 'code' => 'DE'),
            'en' => array('label' => 'English', 'code' => 'EN'),
            'fa' => array('label' => 'فارسی', 'code' => 'FA'),
            'fr' => array('label' => 'Français', 'code' => 'FR'),
            'ku' => array('label' => 'Kurmancî', 'code' => 'KU'),
            'ckb' => array('label' => 'سۆرانی', 'code' => 'CKB'),
            'sv' => array('label' => 'Svenska', 'code' => 'SV'),
            'tr' => array('label' => 'Türkçe', 'code' => 'TR'),
            'zza' => array('label' => 'Zazakî', 'code' => 'ZZA'),
            'ru' => array('label' => 'Русский', 'code' => 'RU'),
        );
    }
}
