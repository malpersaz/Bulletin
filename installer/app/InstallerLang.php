<?php

class InstallerLang
{
    const CACHE_KEY_PREFIX = 'installer-lang-';

    public static function load($languageCode = null)
    {
        $languageCode = $languageCode ?: self::current();
        $cacheKey = self::CACHE_KEY_PREFIX . $languageCode;

        if (Kayit::varmi($cacheKey) === TRUE) {
            return Kayit::getir($cacheKey);
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

        Kayit::ekle($cacheKey, $installerLang);
        return $installerLang;
    }

    public static function current()
    {
        if (Kayit::varmi('lang') === TRUE) {
            return (string) Kayit::getir('lang');
        }

        return 'en';
    }

    public static function reset($languageCode = null)
    {
        if ($languageCode !== null) {
            Kayit::sil(self::CACHE_KEY_PREFIX . $languageCode);
            return;
        }

        foreach (array_keys(self::availableLanguages()) as $code) {
            Kayit::sil(self::CACHE_KEY_PREFIX . $code);
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
            'ckb' => array('label' => 'کوردی', 'code' => 'CKB'),
            'sv' => array('label' => 'Svenska', 'code' => 'SV'),
            'tr' => array('label' => 'Türkçe', 'code' => 'TR'),
            'zza' => array('label' => 'Zazakî', 'code' => 'ZZA'),
        );
    }
}
