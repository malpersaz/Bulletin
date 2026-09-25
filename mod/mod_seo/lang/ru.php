<?php
/**
 * SEO Control - Russian Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_seo'] = [
    'title' => 'Управление SEO',
    'list' => 'Рейтинги Google (SEO)',
    'settings' => 'Настройки SEO',
    'button' => [
        'save' => 'Сохранить настройки',
        'back' => 'Назад',
        'upload' => 'Выбрать JSON-файл',
        'setup_api' => 'Настроить параметры API',
        'refresh' => 'Обновить',
    ],
    'admin' => [
        'columns' => [
            'url' => 'Путь URL',
            'name' => 'Заголовок страницы',
            'type' => 'Тип',
            'clicks' => 'Клики',
            'impressions' => 'Показы',
            'ctr' => 'CTR (%)',
            'position' => 'Средняя позиция',
        ],
        'labels' => [
            'property_url' => 'Домен сайта в Google Search Console',
            'property_url_placeholder' => 'yourdomain.com',
            'json_file' => 'Файл ключа Google API (JSON)',
            'credentials_status' => 'Статус API',
            'credentials_ok' => 'Ключ JSON API загружен',
            'credentials_missing' => 'Ключ JSON API отсутствует! Пожалуйста, загрузите его со страницы настроек.',
            'settings_credentials_missing' => 'Ключ JSON API еще не загружен. Пожалуйста, выберите файл ключа ниже и сохраните.',
            'onboarding_title' => 'Требуется настройка API',
            'onboarding_desc' => 'Чтобы получать аналитику поиска Google в реальном времени (клики, показы и позиции), загрузите учетные данные JSON API.',
        ],
    ],
    'messages' => [
        'success' => 'Настройки успешно обновлены.',
        'error' => 'Произошла ошибка.',
        'json_invalid' => 'Неверный JSON-файл!',
    ],
    'role' => [
        'name' => 'Роли управления SEO',
        'roles' => 'Управление SEO',
        'list' => 'Может просматривать SEO и рейтинги',
        'settings' => 'Может управлять настройками SEO',
        'google_settings' => 'Может управлять настройками Google Cloud и Gemini API',
    ],
    'role_errors' => [
        'list' => 'У вас нет прав на просмотр управления SEO',
        'settings' => 'У вас нет прав на управление настройками SEO',
        'google_settings' => 'У вас нет прав на управление службами Google',
    ],
    'google_settings' => 'Центр служб Google',
    'google' => [
        'bucket_name' => 'Имя бакета GCS (Google Cloud Storage)',
        'gemini_api_key' => 'Ключ Gemini API',
        'credentials_file' => 'Файл сервисного аккаунта Google (credentials.json)',
        'credentials_status' => 'Статус учетных данных',
        'credentials_ok' => 'Файл credentials.json загружен',
        'credentials_missing' => 'Файл credentials.json отсутствует! Пожалуйста, загрузите его.',
        'settings_ok' => 'Настройки Google успешно обновлены.',
    ],
];
