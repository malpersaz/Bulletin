<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_faq'] = [
    'title' => 'Persê ke zaf yenê perskerdene (PZP)',
    'module_title' => 'Persê ke zaf yenê perskerdene (PZP)',
    'add_question' => 'Persêke cı ke',
    'edit_question' => 'Perse newe ke',
    'delete_question' => 'Perse wedarne',
    'update' => 'Newekerdış',
    'save_all' => 'Vurnayışan qeyd ke',
    'question' => 'Pers',
    'answer' => 'Cewab',
    'sort_order' => 'Rêze',
    'status' => 'Weziyet',
    'published' => 'Vıla biye',
    'draft' => 'Ver-nuşte',
    'empty_state' => 'Hela tu perse cı nêbiyaye. Qandê perseke cı kerdene goceka + bıtıkne.',
    'character_limit' => 'Hedo herfan',
    'limit_reached' => 'Hedê 25 persan temam bi.',
    'question_placeholder' => 'Perse ita bınuse...',
    'answer_placeholder' => 'Cewabi ita bınuse...',
    'drag_to_reorder' => 'Qandê rêzekerdene banci',
    'confirm_delete' => 'Ti emel î ke wazenî na perse wedarnî?',
    'admin' => [
        'faq_management' => 'İdarekerdena Persan',
        'manage_faq' => 'Persê ke zaf yenê perskerdene',
    ],
    'errors' => [
        'missing_module_name' => 'Qandê hewayê persan melumatê moduli kêm o.',
        'missing_record_id' => 'Qandê hewayê persan melumatê tomari kêm o.',
        'invalid_module' => 'Qandê na tomare modula persan çina.',
        'module_faq_permission_missing' => 'Qandê nê moduli destûrê persan çino.',
        'invalid_record' => 'Tomara elaqedare nêbiyaye.',
        'invalid_faq' => 'Tomara persa elaqedare nêbiyaye.',
        'invalid_record_title_field' => 'Qada mod_value qandê nê moduli nêvêrena.',
    ],
    'role' => [
        'name' => 'Rolê Persan',
        'add' => 'Şeno persan cı kero.',
        'edit' => 'Şeno persan newe kero.',
        'delete' => 'Şeno persan wedarno.',
        'list' => 'Şeno idareyê persan bıvêno.',
        'moderate_edit' => 'Şeno persê merdumanê bînan newe kero.',
        'moderate_delete' => 'Şeno persê merdumanê bînan wedarno.',
    ],
    'role_errors' => [
        'faq' => 'İznê şıma qandê idarekerdena persan çino.',
        'add' => 'İznê şıma qandê cıkerdena persan çino.',
        'edit' => 'İznê şıma qandê newekerdena persan çino.',
        'delete' => 'İznê şıma qandê wedarnayena persan çino.',
        'list' => 'İznê şıma qandê vênayena idareyê persan çino.',
        'moderate_edit' => 'İznê şıma qandê newekerdena persê merdumanê bînan çino.',
        'moderate_delete' => 'İznê şıma qandê wedarnayena persê merdumanê bînan çino.',
    ],
    'message' => [
        'success' => [
            'add' => 'Pers bi serkewti cı biye.',
            'edit' => 'Pers bi serkewti newe biye.',
            'delete' => 'Pers bi serkewti wedariyê.',
            'reorder' => 'Rêza persan newe biye.',
            'batch' => 'Pêro persi qeyd biyê.',
            'status' => 'Weziyetê perse vurniya.',
        ],
        'error' => [
            'add' => 'Pers cı nêbiye.',
            'edit' => 'Pers newe nêbiye.',
            'delete' => 'Pers wedarniyaye nêbiye.',
            'reorder' => 'Rêze newe nêbiye.',
            'batch' => 'Qeydkerdışo komki ser nêkewt.',
            'status' => 'Vurnayışê weziyeti ser nêkewt.',
            'max_limit' => 'Şıma nêşenê hedê 25 persan ra ravêrê.',
            'invalid_record' => 'Tomar ya zi modul vês niyo.',
            'permission_denied' => 'İznê şıma qandê idarekerdena persan çino.',
            'duplicate_question' => 'Pirseke be eynî nuşte ra ca de esta.',
        ],
    ],
    'validation' => [
        'question_required' => 'Gereke cayê perse thal nêmano.',
        'question_max' => 'Pers nêşeno 255 herfan ra zêde bo.',
        'question_duplicate' => 'Na perse verê cı biya.',
        'answer_required' => 'Gereke cayê cewabi thal nêmano.',
        'answer_max' => 'Cewab nêşeno 5000 herfan ra zêde bo.',
    ]
];
