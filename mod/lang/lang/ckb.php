<?php
/**
 * Language Module - Central Kurdish Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['lang'] = [
    'installer' => [
        'table_exists' => "خشتەی 'lang' پێشتر لە بنکەدراوەدا دامەزراوە",
        'sql_added' => "فایلی 'lang.sql' بۆ بنکەدراوە زیادکرا",
        'lang_added' => '<b>%s</b> زیادکرا بۆ بنکەدراوە',
        'lang_exists' => '<b>%s</b> پێشتر لە بنکەدراوەدا هەیە',
    ],
    'system' => [
        'starting' => 'دامەزراندن دەست پێ دەکات...',
        'installing' => 'دادەمەزرێت: %s',
    ],
    'admin' => [
        'title' => 'بەڕێوەبردنی زمان',
        'headline' => 'زمانەکان',
        'body' => 'زمانەکان چالاک یان ناچالاک بکە و زمانی بنەڕەتیی ماڵپەڕ لە یەک پەینەلی گونجاو بۆ مۆبایل بەڕێوەببە.',
        'status_active' => 'چالاک',
        'status_inactive' => 'ناچالاک',
        'default_badge' => 'بنەڕەتی',
        'current_badge' => 'ڕووکارەکەی ئێستا',
        'code_label' => 'کۆد',
        'activate' => 'چالاک بکە',
        'deactivate' => 'ناچالاک بکە',
        'set_default' => 'بیکە بنەڕەتی',
        'empty_state' => 'هیچ زمانێک نەدۆزرایەوە.',
        'permission_denied' => 'دەسەڵاتی بەڕێوەبردنی زمانەکانت نییە.',
        'back_panel' => 'پەینەل',
        'back_home' => 'ماڵپەڕ',
        'back' => 'گەڕانەوە',
    ],
    'messages' => [
        'activated' => '%s ئێستا چالاکە.',
        'deactivated' => '%s ئێستا ناچالاکە.',
        'default_changed' => '%s ئێستا زمانی بنەڕەتییە.',
    ],
    'errors' => [
        'language_not_found' => 'زمانی هەڵبژێردراو نەدۆزرایەوە.',
        'unknown_action' => 'کرداری خوازراوی زمان دروست نییە.',
        'cannot_deactivate_default' => 'پێش ناچالاککردنی ئەم زمانە، زمانێکی بنەڕەتیی تر هەڵبژێرە.',
        'cannot_deactivate_last_active' => 'دەبێت لانیکەم یەک زمان چالاک بمێنێت.',
        'cannot_deactivate_current_interface' => 'پێش ناچالاککردنی ئەم زمانە، زمانی ڕووکاری بەڕێوەبردن بگۆڕە.',
        'update_failed' => 'دۆخی زمان نوێ نەکرایەوە.',
    ],
];
