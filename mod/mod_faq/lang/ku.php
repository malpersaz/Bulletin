<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_faq'] = [
    'title' => 'Pirsên Pir Tên Pirsîn (PPP)',
    'module_title' => 'Pirsên Pir Tên Pirsîn (PPP)',
    'add_question' => 'Pirsekê Lêke',
    'edit_question' => 'Pirsê Nûjen Bike',
    'delete_question' => 'Pirsê Jê Bibe',
    'update' => 'Nûjenkirin',
    'save_all' => 'Guhertinan Tomar Bike',
    'question' => 'Pirs',
    'answer' => 'Bersiv',
    'sort_order' => 'Rêz',
    'status' => 'Rewş',
    'published' => 'Weşandî',
    'draft' => 'Pêşnivîs',
    'empty_state' => 'Hêj tu pirs nehatine lêkirin. Ji bo lêkirina pirsekê pê li bişkoka + bike.',
    'character_limit' => 'Sînorê tîpan',
    'limit_reached' => 'Hûn nikarin ji 25 pirsan zêdetir lêkin.',
    'question_placeholder' => 'Pirsê li vir binivîse...',
    'answer_placeholder' => 'Bersivê li vir binivîse...',
    'drag_to_reorder' => 'Ji bo rêzkirinê bikişîne',
    'confirm_delete' => 'Ma tu piştrast î ku dixwazî vê pirsê jê bibî?',
    'admin' => [
        'faq_management' => 'Birêvebirina Pirsan',
        'manage_faq' => 'Pirsên Pir Tên Pirsîn',
    ],
    'errors' => [
        'missing_module_name' => 'Agahiya modulê ji bo çalakiya pirsan kêm e.',
        'missing_record_id' => 'Agahiya tomarê ji bo çalakiya pirsan kêm e.',
        'invalid_module' => 'Ji bo vê tomarê tu modula pirsan nehatiye diyarkirin.',
        'module_faq_permission_missing' => 'Ji bo vê modulê destûra pirsan nehatiye diyarkirin.',
        'invalid_record' => 'Tomara têkildar nehate dîtin.',
        'invalid_faq' => 'Tomara pirsa têkildar nehate dîtin.',
        'invalid_record_title_field' => 'Qada mod_value ji bo vê modulê nederbasdar e.',
    ],
    'role' => [
        'name' => 'Rolên Pirsan',
        'add' => 'Dikare pirsan lêke.',
        'edit' => 'Dikare pirsan nûjen bike.',
        'delete' => 'Dikare pirsan jê bibe.',
        'list' => 'Dikare rêveberiya pirsan bibîne.',
        'moderate_edit' => 'Dikare pirsên kesên din nûjen bike.',
        'moderate_delete' => 'Dikare pirsên kesên din jê bibe.',
    ],
    'role_errors' => [
        'faq' => 'Destûra we ya birêvebirina pirsan nîne.',
        'add' => 'Destûra we ya lêkirina pirsan nîne.',
        'edit' => 'Destûra we ya nûjenkirina pirsan nîne.',
        'delete' => 'Destûra we ya jêbirina pirsan nîne.',
        'list' => 'Destûra we ya dîtina birêvebiriya pirsan nîne.',
        'moderate_edit' => 'Destûra we ya nûjenkirina pirsên kesên din nîne.',
        'moderate_delete' => 'Destûra we ya jêbirina pirsên kesên din nîne.',
    ],
    'message' => [
        'success' => [
            'add' => 'Pirs bi serkeftî hate lêkirin.',
            'edit' => 'Pirs bi serkeftî hate nûjenkirin.',
            'delete' => 'Pirs bi serkeftî hate jêbirin.',
            'reorder' => 'Rêza pirsan hate nûjenkirin.',
            'batch' => 'Hemû pirs bi serkeftî hatin tomarkirin.',
            'status' => 'Rewşa pirsê hate guhertin.',
        ],
        'error' => [
            'add' => 'Pirs nehate lêkirin.',
            'edit' => 'Pirs nehate nûjenkirin.',
            'delete' => 'Pirs nehate jêbirin.',
            'reorder' => 'Rêz nehate nûjenkirin.',
            'batch' => 'Tomarkirina komî bi ser neket.',
            'status' => 'Guhertina rewşê bi ser neket.',
            'max_limit' => 'Hûn nikarin ji sînorê 25 pirsan biborin.',
            'invalid_record' => 'Nasnameya tomarê an modulê nederbasdar e.',
            'permission_denied' => 'Destûra we ya birêvebirina pirsan nîne.',
            'duplicate_question' => 'Pirseke bi heman nivîsê jixwe heye.',
        ],
    ],
    'validation' => [
        'question_required' => 'Divê qada pirsê vala nemîne.',
        'question_max' => 'Pirs nikare ji 255 tîpan zêdetir be.',
        'question_duplicate' => 'Ev pirs berê hatiye lêkirin.',
        'answer_required' => 'Divê qada bersivê vala nemîne.',
        'answer_max' => 'Bersiv nikare ji 5000 tîpan zêdetir be.',
    ]
];
