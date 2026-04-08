<?php
/**
 * Language Module - Zazaki Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['lang'] = [
    'installer' => [
        'table_exists' => "Tabloya 'lang' danegrot de barkerde ya",
        'sql_added' => "Dosyaya 'lang.sql' danegrot de zêde bîya",
        'lang_added' => '<b>%s</b> danegrot de zêde bîya',
        'lang_exists' => '<b>%s</b> danegrot de xora esto',
    ],
    'system' => [
        'starting' => 'Sazkerdış dest pê kena...',
        'installing' => 'tê sazkerdış: %s',
    ],
    'admin' => [
        'title' => 'Rêveberiya Ziwanan',
        'headline' => 'Ziwan',
        'body' => 'Ziwanan çalak kerê, pasif kerê û ziwanê bingeyênê malperî de panelo telefoni ra rêve biberê.',
        'status_active' => 'Çalak',
        'status_inactive' => 'Pasif',
        'default_badge' => 'Bingeyên',
        'current_badge' => 'Arayışê Nıka',
        'code_label' => 'Kod',
        'activate' => 'Çalak Ke',
        'deactivate' => 'Pasif Ke',
        'set_default' => 'Bingeyên Ke',
        'empty_state' => 'Hîç ziwan pey nêbîya.',
        'permission_denied' => 'Şıma rêzayışê rêveberiya ziwanan nêna.',
        'back_panel' => 'Panel',
        'back_home' => 'Malper',
        'back' => 'Peyser',
    ],
    'messages' => [
        'activated' => '%s niya çalak o.',
        'deactivated' => '%s niya pasif o.',
        'default_changed' => '%s niya ziwanê bingeyên o.',
    ],
    'errors' => [
        'language_not_found' => 'Ziwanê weçarnayışî pey nêbîya.',
        'unknown_action' => 'Karê ziwanî yê xwestışî derbasdar nêo.',
        'cannot_deactivate_default' => 'Verê pasif kerdenê nê de ziwanekê bingeyênê bîn weçinê.',
        'cannot_deactivate_last_active' => 'Pêwıst o ke herî kêm yew ziwan çalak bımanê.',
        'cannot_deactivate_current_interface' => 'Verê pasif kerdenê nê de ziwanê rêveberiyê yê nıka bıvurne.',
        'update_failed' => 'Rewşa ziwanî nêvurniya.',
    ],
];
