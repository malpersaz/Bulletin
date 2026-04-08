<?php
/**
 * Language Module - Kurdish Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['lang'] = [
    'installer' => [
        'table_exists' => "Tabloya 'lang' jixwe di danegehê de barkirî ye",
        'sql_added' => "Pelê 'lang.sql' li danegehê hate zêdekirin",
        'lang_added' => '<b>%s</b> li danegehê hate zêdekirin',
        'lang_exists' => '<b>%s</b> jixwe di danegehê de heye',
    ],
    'system' => [
        'starting' => 'Sazkirin dest pê dike...',
        'installing' => 'tê sazkirin: %s',
    ],
    'admin' => [
        'title' => 'Rêveberiya Ziman',
        'headline' => 'Ziman',
        'body' => 'Ji yek panela telefon-hevgirtî zimanan çalak bike, neçalak bike û zimanê bingehîn ê malperê hilbijêre.',
        'status_active' => 'Çalak',
        'status_inactive' => 'Neçalak',
        'default_badge' => 'Bingehîn',
        'current_badge' => 'Arayûza Niha',
        'code_label' => 'Kod',
        'activate' => 'Çalak Bike',
        'deactivate' => 'Neçalak Bike',
        'set_default' => 'Bingehîn Bike',
        'empty_state' => 'Tu ziman nehat dîtin.',
        'permission_denied' => 'Tu destûra rêveberiya zimanan nînî.',
        'back_panel' => 'Panel',
        'back_home' => 'Malper',
        'back' => 'Vegere',
    ],
    'messages' => [
        'activated' => '%s niha çalak e.',
        'deactivated' => '%s niha neçalak e.',
        'default_changed' => '%s niha zimanê bingehîn e.',
    ],
    'errors' => [
        'language_not_found' => 'Zimanê hatî hilbijartin nehat dîtin.',
        'unknown_action' => 'Karê zimanê ku hat xwestin derbasdar nîne.',
        'cannot_deactivate_default' => 'Berî neçalak kirina vê zimanê zimanekî bingehîn yê din hilbijêre.',
        'cannot_deactivate_last_active' => 'Pêdivî ye ku herî kêm zimanek çalak bimîne.',
        'cannot_deactivate_current_interface' => 'Berî neçalak kirina vê zimanê, zimanê rêveberiyê yê niha biguherîne.',
        'update_failed' => 'Rewşa zimanê nehat nûvekirin.',
    ],
];
