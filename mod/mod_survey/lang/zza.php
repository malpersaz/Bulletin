<?php
/**
 * Poll/Survey Module - Kurdish Language File
 *
 * @package AKM Business
 * @module  mod_survey
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Poll/Survey Module Language Translations
 */
$lang['mod_survey'] = [
    'add' => 'Rave Zêde Bike',

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Rave Lîstikê Zêde Bike',
        'edit' => 'Rave Lîstikê Biguhere',
        'list' => 'Lîsteya Raveyan',
        'delete' => 'Rave Lîstikê Rake',
        'cancel' => 'Betal',
        'save' => 'Tomar Bike',
        'update' => 'Rojane Bike',
        'remove' => 'Rake',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Rave lîstik bi serkeftin hat zêdekirin!',
            'updated' => 'Rave lîstik bi serkeftin hat guhertin!',
            'deleted' => 'Rave lîstik bi serkeftin hate rakirin!',
            'set_default' => 'Rave lîstik weke DEFAULT hate sazkirin!',
        ],
        'error' => [
            'not_added' => 'Zêdekirina rave lîstik serneket!',
            'not_updated' => 'guhertina rave lîstik serneket!',
            'not_deleted' => 'Rakirina rave lîstik serneket!',
            'not_found' => 'Tomara rave lîstikê ku hatiye nîşankirin nehate dîtin!',
        ],
        'info' => [
            'edit_double_click' => 'Lîstika ku dixwazî sererast bikî du caran bitikîne',
            'drag_reorder' => 'Lîstika ku dixwazî rêz bike bikişîne û berde',
        ],
    ],

    // === Status Types ===
    'status' => [
        'published' => 'Weşandî',
        'draft' => 'Mêvandar',
        'archived' => 'Arşîvkirî',
        'default' => 'Rave Lîstika Default',
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Erê',
        'no' => 'Na',
    ],

    // === Form Labels ===
    'form' => [
        'basic_info' => 'Agahiyên Bingehîn',
        'display_settings' => 'Mîhengên Nîşandayî',
        'advanced_settings' => 'Mîhengên Pêşkeftî',

        'id' => 'ID',
        'question' => 'Pirsê Rave Lîstikê',
        'language' => 'Ziman',
        'is_default' => 'Weke Rave Lîstika Default Bike',
        'access_control' => 'Kontrola Têketinê',
        'options' => 'Vebijêrkên Rave Lîstikê',
        'start_date' => 'Dîroka Destpêkê',
        'end_date' => 'Dîroka Dawî',
        'multiple_choice' => 'Hilbijartina Pirrengî Destûr Bike',
    ],

    // === Table Columns ===
    'columns' => [
        'id' => 'ID',
        'language' => 'Ziman',
        'question' => 'Pirsê Rave Lîstikê',
        'is_default' => 'Default Lîstik',
        'status' => 'Rewş',
        'created_at' => 'Dîroka Afirandinê',
        'access_level' => 'Astê Têketinê',
        'actions' => 'Çalakî',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Rave Lîstika Nû Zêde Bike',
        'edit' => 'Hilbijartinê Biguhere',
        'delete' => 'Hilbijartinê Rake',
        'refresh' => 'Nû Bike',
        'set_default' => 'Weke Default Bike',
        'view_results' => 'Encaman Bibîne',
        'export' => 'Encaman Derxîne',
    ],

    // === Validation Messages ===
    'validation' => [
        'required' => 'Qada :field pêwîst e',
        'min_length' => 'Qada :field divê herî kêm :min tîpan be',
        'max_length' => 'Qada :law nabe ku ji :max tîpan zêdetir be',
        'unique' => 'Qada :field divê yekane be',
        'email' => 'Ji kerema xwe re navnîşana e-posteyê ya derbasdar binivîsin',
        'min_options' => 'Rave lîstik divê herî kêm 2 veavijêrk hebe',
        'max_options' => 'Rave lîstik nabe ku ji :max veavijêrkan zêdetir hebe',
    ],

    // === Access Control (ACL) ===
    'permissions' => [
        'title' => 'Destûrên Rêveberiya Rave Lîstikan',
        'view' => 'Rave Lîstikan Bibîne',
        'add' => 'Rave Lîstik Zêde Bike',
        'edit_own' => 'Rave Lîstika Xwe Biguhere',
        'edit_all' => 'Hemû Rave Lîstikan Biguhere',
        'delete_own' => 'Rave Lîstika Xwe Rake',
        'delete_all' => 'Hemû Rave Lîstikan Rake',
        'manage_access' => 'Têketinê Rêve Bike',
        'view_results' => 'Encamên Rave Lîstikan Bibîne',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'view_denied' => 'Destûra te tune ye ku rêveberiya rave lîstikan bibînî',
        'add_denied' => 'Destûra te tune ye ku rave lîstik zêde bikî',
        'edit_denied' => 'Destûra te tune ye ku rave lîstik sererast bikî',
        'delete_denied' => 'Destûra te tune ye ku rave lîstik rakî',
        'results_denied' => 'Destûra te tune ye ku encamên rave lîstikan bibînî',
        'access_denied' => 'Destûra te tune ye ku vê rave lîstikê sererast bikî/rakî (ne teye)',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'delete_single' => 'Bi rastî jî dixwazî vê rave lîstikê rakî?',
        'delete_multiple' => 'Bi rastî jî dixwazî :count rave lîstikên hilbijartî rakî?',
        'set_default' => 'Bi rastî jî dixwazî vê rave lîstikê weke default bikevî?',
        'publish' => 'Bi rastî jî dixwazî vê rave lîstikê biweşînî?',
        'unpublish' => 'Bi rastî jî dixwazî weşana vê rave lîstikê rawestînî?',
    ],

    // === Helper Text ===
    'help' => [
        'poll_question' => 'Ji bo rave lîstika xwe pirsêk a eşkere û kurt binivîse',
        'access_control' => 'Cureyên rêzên ku dibin vê rave lîstikê bibînin û deng bidin diyar bike',
        'default_poll' => 'Tenê yek rave lîstik dikare weke default were sazkirin',
        'multiple_choice' => 'Bikarhêneran destûr bidin ku ji yek zêdetir veavijêrk hilbijêrin',
        'poll_options' => 'Veavijêrkên dengdanê yên ku bikarhêner dikarin hilbijêrin zêde bike',
    ],

    // === Placeholders ===
    'placeholders' => [
        'poll_question' => 'Pirsê rave lîstika xwe li vir binivîse...',
        'poll_option' => 'Veavijêrka rave lîstikê binivîse...',
        'start_date' => 'Dîroka destpêkê hilbijêre...',
        'end_date' => 'Dîroka dawînê hilbijêre (bijare)...',
    ],

    // === Empty States ===
    'empty' => [
        'no_polls' => 'Heta niha tu rave lîstik nehatine afirandin',
        'no_results' => 'Tu rave lîstikên li gorî mêjsona lêgerînê nehatin dîtin',
        'add_first' => 'Ji bo afirandina rave lîstika pêşîn li ser bişkojê bitikîne',
        'no_votes' => 'Heta niha tu deng nehatine dayîn',
    ],

    // === Loading States ===
    'loading' => [
        'saving' => 'Tomar kirin...',
        'loading' => 'Bar kirin...',
        'processing' => 'Pêvajoya dengdanê...',
        'counting' => 'Hesabkirina encaman...',
    ],

    // === Success Messages ===
    'success' => [
        'operation_completed' => 'Çalakî bi serkeftin hat temamkirin!',
        'changes_saved' => 'Guhartinan hate tomar kirin!',
        'poll_published' => 'Rave lîstik bi serkeftin hate weşandin!',
        'poll_unpublished' => 'Weşana rave lîstikê bi serkeftin hate rawestandin!',
    ],

    // === Error Messages ===
    'errors' => [
        'general' => 'Çewtî çêbû. Ji kerema xwe re dîsa biceribîne.',
        'network' => 'Çewtiya torê. Ji kerema xwe re girêdana înternetê kontrol bike.',
        'server' => 'Çewtiya serverê. Ji kerema xwe re paşê dîsa biceribîne.',
        'no_default_poll' => 'Tu rave lîstika default nehatiye sazkirin',
        'multiple_defaults' => 'Gelek rave lîstikên default hatin dîtin',
    ],

    // === Results Display ===
    'results' => [
        'total_votes' => 'Dengên Giştî',
        'vote_count' => ':count deng',
        'percentage' => ':percentage%',
        'no_votes_yet' => 'Heta niha deng tune',
        'view_detailed_results' => 'Encamên Kûrtir Bibîne',
        'export_to_csv' => 'Derxe CSV',
        'reset_results' => 'Encaman Vegerîne',
    ],

    // === Poll Options Management ===
    'options_management' => [
        'add_option' => 'Veavijêrk Zêde Bike',
        'remove_option' => 'Veavijêrk Rake',
        'option_text' => 'Nivîsa Veavijêrkê',
        'vote_count' => 'Hejmara Dengan',
        'reorder_options' => 'Veavijêrkan Bike Rêz',
    ],

];