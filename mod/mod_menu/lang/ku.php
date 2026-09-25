<?php
/**
 * Menu Module - Kurdish Language File
 *
 * @package AKM Business
 * @module  mod_menu
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Menu Module Language Translations
 */
$lang['mod_menu'] = [

    // === Page Titles & Statistics ===
    'list' => 'Rêveberiya Pêşekê',
    'add' => 'Pêşekekê Lêke',
    'edit' => 'Sererast Bike',
    'information' => 'Agahdarî',
    'total_menus' => 'Hemû Pêşek',
    'published_menus' => 'Weşandî',
    'draft_menus' => 'Pêşnivîs',
    'expand_all' => 'Hemûyan Berfireh Bike',
    'collapse_all' => 'Hemûyan Biqurmiçîne',
    'search_placeholder' => 'Di pêşekan de lê bigere...',

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Pêşekê Lêke',
        'edit' => 'Biguhere',
        'list' => 'Lîsteya Pêşekan',
        'delete' => 'Jêbibe',
        'cancel' => 'Betal',
        'save' => 'Tomar Bike',
        'update' => 'Rojane Bike',
        'remove' => 'Rake',
        'refresh' => 'Nû Bike',
        'export' => 'Derxîne',
        'import' => 'Têxe',
        'bulk_actions' => 'Çalakiyên Giştî',
        'gallery' => 'Wênedank',
        'file' => 'Pel',
        'unlock_reorder' => 'Moda Kaşkirinê Veke',
        'lock_reorder' => 'Moda Kaşkirinê Bigire',
        'publish_toggle' => 'Rewşa Weşanê',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Pêşek bi serkeftî hate lêkirin!',
            'updated' => 'Pêşek bi serkeftî hate guhertin!',
            'deleted' => 'Pêşek bi serkeftî hate jêbirin!',
            'reordered' => 'Rêza pêşekê hate guhertin!',
            'delete' => 'Pêşek hate jêbirin!',
            'published' => 'Pêşek bi serkeftî hate weşandin!',
            'unpublished' => 'Pêşek ji weşanê hate rakirin!',
        ],
        'error' => [
            'not_added' => 'Lêkirina pêşekê biserneket!',
            'not_updated' => 'Guhertina pêşekê biserneket!',
            'not_deleted' => 'Jêbirina pêşekê biserneket!',
            'not_found' => 'Tomara pêşeka ku hatî destnîşankirin nehat dîtin!',
        ],
        'warning' => [
            'delete' => 'Hûn ewle ne ku dixwazin vê pêşekê jê bibin?',
            'reorder' => 'Cihê pêşekê were guhertin?',
        ],
        'info' => [
            'edit_double_click' => 'Ji bo sererastkirinê ser pêşeka ku tu dixwazî du caran bitikîne',
            'drag_reorder' => 'Pêşekê bikşîne û berde ji bo guherandina rêzê',
            'block_system_removed' => 'Pergala blok hate rakirin - ev taybetî naha tune ye',
            'reorder_mode' => 'Mode: Rêzkirin (Unlocked)',
        ],
    ],

    // === Status Types ===
    'status' => [
        'published' => 'Hate Weşandin',
        'draft' => 'Çapemenî',
        'archived' => 'Arşîvkirî',
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Erê',
        'no' => 'Na',
        'ok' => 'Temam',
    ],

    // === Target Options ===
    'targets' => [
        'same' => 'Heman Rûpel',
        'new' => 'Rûpela Nû',
        'blank' => 'Hilpekîna Nû',
    ],

    // === Form Labels ===
    'form' => [
        'basic_info' => 'Agahdariya Bingehîn',
        'display_settings' => 'Mîhengên Nîşandanê',
        'advanced_settings' => 'Mîhengên Pêşketî',
        'acl_settings' => 'Mîhengên Destûrê (ACL)',

        'id' => 'ID',
        'name' => 'Navê Pêşekê',
        'url' => 'URL ya Pêşekê',
        'parent' => 'Pêşeka Dayîk',
        'order' => 'Rêz',
        'language' => 'Ziman',
        'target' => 'Armanca Vekirinê',
        'published' => 'Rewş',
        'html_class' => 'Mifteya HTML',
        'icon' => 'Îkona Bootstrapê',
        'access_control' => 'Kontrola Têketinê',
        'description' => 'Ravekirin',
        'file' => 'Pel',
    ],

    // === Table Columns ===
    'columns' => [
        'id' => 'ID',
        'language' => 'Ziman',
        'name' => 'Navê Pêşekê',
        'url' => 'URL',
        'parent_menu' => 'Pêşeka Jor',
        'order' => 'Rêz',
        'target' => 'Bila çawa vebe?',
        'status' => 'Rewş',
        'icon' => 'Îkona Bootstrapê',
        'access_level' => 'Destûra Dîtinê',
        'actions' => 'Çalakî',
        'parent_id' => 'Pêşeka Jor',
        'html' => 'HTML',
        'acl_id' => 'Destûra Têketinê',
        'file' => 'Pel',
    ],
    
    'admin' => [
        'columns' => [
            'language' => 'Ziman',
            'name' => 'Nav',
            'url' => 'URL',
            'file' => 'Pel',
            'parent_id' => 'Pêşeka Jor',
            'target' => 'Hedef',
            'status' => 'Rewşa Weşanê',
            'html' => 'HTML',
            'icon' => 'Îkona Bootstrapê',
            'acl_id' => 'Kî dikare bibîne?'
        ],
        'select_value' => [
            'target_e' => 'Pencereya Nû (_blank)',
            'target_h' => 'Heman Pencere (_self)'
        ],
        'radio' => [
            'publish_yes' => 'Biweşîne',
            'publish_no' => 'Neweşîne'
        ]
    ],
    
    'choose_wish' => 'Hilbijêre',
    'add' => 'Lêke',
    'edit' => 'Biguhere',
    'success' => 'Serkeftî!',
    'error' => 'Çewtî!',
    'success_title' => 'Serkeftî!',
    'error_title' => 'Çewtî!',
    'remove_record' => 'Tomar Jê Bibe',

    // === Message Keys (used by index.php) ===
    'message' => [
        'success' => [
            'add' => 'Pêşek bi serkeftî hate lêkirin!',
            'edit' => 'Pêşek bi serkeftî hate guhertin!',
        ],
        'error' => [
            'add' => 'Lêkirina pêşekê biserneket!',
            'edit' => 'Guhertina pêşekê biserneket!',
        ],
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Pêşeka Nû Lêke',
        'edit' => 'Hilbijartinê Biguhere',
        'delete' => 'Hilbijartinê Jê Bibe',
        'refresh' => 'Nû Bike',
        'export' => 'Derxîne',
        'import' => 'Têxe',
        'bulk_actions' => 'Çalakiyên Giştî',
    ],

    // === Validation Messages ===
    'validation' => [
        'required' => 'Qada :field pêwist e',
        'min_length' => 'Qada :field divê herî kêm :min tîpan be',
        'max_length' => 'Qada :field dikare herî zêde :max tîpan be',
        'unique' => 'Qada :field divê yekane be',
        'url' => 'Ji kerema xwe navekî URL yê derbasdar binivîsin',
        'numeric' => 'Qada :field divê hejmar be',
        'email' => 'Ji kerema xwe navekî e-postayê yê derbasdar binivîsin',
    ],

    // === Access Control (ACL) ===
    'permissions' => [
        'title' => 'Destûrên Rêveberiya Pêşekan',
        'view' => 'Pêşekan Bibîne',
        'add' => 'Pêşekê Lêke',
        'edit_own' => 'Pêşeka Xwe Biguhere',
        'edit_all' => 'Hemû Pêşekan Biguhere',
        'delete_own' => 'Pêşeka Xwe Jê Bibe',
        'delete_all' => 'Hemû Pêşekan Jê Bibe',
        'manage_access' => 'Têketinan Rêve Bike',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'view_denied' => 'Destûra te ji bo dîtina rêveberiya pêşekan tune',
        'add_denied' => 'Destûra te ji bo zêdekirina pêşekê tune',
        'edit_denied' => 'Destûra te ji bo sererastkirina pêşekê tune',
        'delete_denied' => 'Destûra te ji bo jêbirina pêşekê tune',
        'access_denied' => 'Destûra te ji bo sererastkirin/jêbirina vê pêşekê tune (ne te ye)',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'delete_single' => 'Ji jêbirina vê pêşekê bawer î?',
        'delete_multiple' => 'Tu bawerî ku dixwazî pêşekê ji :count pêşekên hilbijartî jêbibî?',
        'publish' => 'Tu bawer î ku dixwazî ev pêşek were weşandin?',
        'unpublish' => 'Tu bawer î ku dixwazî vê pêşekê ji weşanê rakî?',
        'reorder_single' => 'Tu dixwazî vê pêşekê bibî ciyekî nû?',
    ],

    // === Helper Text ===
    'help' => [
        'parent_menu' => 'Hilbijêre ku ev pêşek di bin kîjan pêşekê de were nîşandan',
        'access_control' => 'Destnîşan bike ku kîjan rolên bikarhêner dikarin vê pêşekê bibînin',
        'url' => 'Ji bo girêdanên navxwe /formatê bikar bînin, ji bo girêdanên derve bi https:// dest pê bikin',
        'order' => 'Rêza pêşekê diyar dike (nirxên nizm jorîn tên nîşandan)',
        'icon' => 'Navê Îkona Bootstrapê binivîse (mînak: bi-house)',
        'file' => 'Navê pel an modulê (mînak: mod_page, index.php). Dema URL tunebe ev pel tê xebitandin.',
        'html' => 'Taybetmendiyên HTML an navên class\'ên wekî CSS (mînak: class="text-danger")',
    ],

    // === Placeholders ===
    'placeholders' => [
        'menu_name' => 'Navê pêşekê binivîse...',
        'menu_url' => 'https://example.com an /page',
        'menu_description' => 'Ravekirina pêşekê (bijarte)',
        'html_class' => 'Mifteya CSS',
        'icon_class' => 'Îkona Bootstrapê',
    ],

    // === Empty States ===
    'empty' => [
        'no_menus' => 'Hîn tu pêşek nehatiye lêkirin',
        'no_results' => 'Tu pêşeka ku li gorî lêgerîna te hebe nehat dîtin',
        'add_first' => 'Ji bo lêkirina yekem pêşekê bişkojê bitikîne',
    ],

    // === Loading States ===
    'loading' => [
        'saving' => 'Tomarkirin...',
        'loading' => 'Lêbarkirin...',
        'processing' => 'Tê bicihanîn...',
    ],

    // === Success Messages ===
    'success_messages' => [
        'operation_completed' => 'Çalakî bi serkeftî hate xwestin!',
        'changes_saved' => 'Guhertinên hatin tomar!',
    ],

    // === Error Messages ===
    'errors' => [
        'general' => 'Çewtî çêbû. Ji kerema xwe dîsa biceribîne.',
        'network' => 'Çewtiya torê. Ji kerema xwe girêdana înternetê xwe kontrol bike.',
        'server' => 'Çewtiya serverê. Ji kerema xwe paşê dîsa biceribîne.',
        'record' => 'Tomar Nehat Dîtin',
        'parent_selection_required' => 'Divê pêşeka ku ev tomara nû dê tê de be hilbijêrî.',
        'parent_language_mismatch' => 'Zimanê pêşeka dayîk divê bi zimanê pêşekê re wekhev be.',
    ],
    
    // === ACL Roles ===
    'role' => [
        'name' => 'Rolên Rêveberiya Pêşekan',
        'add' => 'Dikare Pêşekê Lêke',
        'edit' => 'Dikare Pêşekê Biguhere',
        'delete' => 'Dikare Pêşekê Jê Bibe',
        'list' => 'Dikare Rêveberiya Pêşekan Bibîne',
        'moderate_edit' => 'Dikare Pêşekên Kesên Din Biguhere',
        'moderate_delete' => 'Dikare Pêşekên Kesên Din Jê Bibe',
    ],

    // === ACL Error Messages ===
    'role_errors' => [
        'add' => 'Destûra te ji bo lêkirina pêşekê tune',
        'edit' => 'Destûra te ji bo sererastkirina pêşekê tune',
        'delete' => 'Destûra te ji bo jêbirina pêşekê tune',
        'list' => 'Destûra te ji bo dîtina rêveberiya pêşekan tune',
        'moderate_edit' => 'Destûra te ji bo sererastkirina pêşekên kesên din tune',
        'moderate_delete' => 'Destûra te ji bo jêbirina pêşekên kesên din tune',
    ],

    // === Mobile Wizard ===
    'wizard' => [
        'title' => 'Lêzêdekirina Pêşekê',
        'steps' => [
            'step_1' => 'Ziman',
            'step_2' => 'Navê Pêşekê',
            'step_3' => 'Pêşeka Dayîk',
            'step_4' => 'Girêdan (URL)',
            'step_5' => 'Nîşan (Îkon)',
            'step_6' => 'Pelê Pêşekê',
            'step_7' => 'Koda HTML',
            'step_8' => 'Armanca Vekirinê',
            'step_9' => 'Destûra Têketinê',
            'step_10' => 'Rewşa Weşanê û Kurte',
        ],
        'subtitles' => [
            'step_1' => 'Zimanê ku ev pêşek tê de were weşandin hilbijêre.',
            'step_2' => 'Navê pêşekê binivîse.',
            'step_3' => 'Pêşeka dayîk û asta wê ya hiyerarşiyê diyar bike.',
            'step_4' => 'Navnîşana girêdana URL a pêşekê binivîse.',
            'step_5' => 'Ji bo pêşekê îkoneke guncaw diyar bike.',
            'step_6' => 'Pelê çavkaniyê yê taybet binivîse (vebijarkî ye).',
            'step_7' => 'Koda xwerû ya HTMLê lêke (vebijarkî ye).',
            'step_8' => 'Diyar bike bê ka rûpel di heman pencereyê de yan di ya nû de vebe.',
            'step_9' => 'Asta destûra gihîştinê ji bo vê pêşekê diyar bike.',
            'step_10' => 'Rewşa weşanê diyar bike, kurteyê kontrol bike û pêşekê lêke.',
        ],
        'buttons' => [
            'next' => 'Pêşve',
            'prev' => 'Vegere',
            'finish' => 'Pêşekê Lêke',
            'cancel' => 'Betal',
        ],
        'summary' => [
            'title' => 'Kurteya Pêşekê',
            'language' => 'Ziman',
            'name' => 'Navê Pêşekê',
            'parent' => 'Pêşeka Dayîk',
            'url' => 'Girêdan (URL)',
            'target' => 'Armanca Vekirinê',
            'status' => 'Rewşa Weşanê',
            'acl' => 'Destûra Têketinê',
            'none' => 'Tune',
            'root' => 'Asta jor (Koka pêşekan)',
            'same_window' => 'Heman Pencere (_self)',
            'new_window' => 'Pencereya Nû (_blank)',
            'published' => 'Weşandî',
            'draft' => 'Pêşnivîs',
        ],
        'labels' => [
            'popular_icons' => 'Îkonên Berbiçav',
            'advanced_settings' => 'Mîhengên Pêşketî (Pel û HTML)',
            'placement_preview' => 'Pêşdîtina Cihbûnê',
            'will_be_added_here' => 'Dê li vir were lêkirin',
            'live_preview' => 'Pêşdîtina Zindî',
            'quick_links' => 'Girêdanên Bilez',
            'preset_home' => 'Malper (/)',
            'preset_external' => 'Derve (https://)',
            'preset_header_only' => 'Tenê Serdêr (#)',
            'target_same_desc' => 'Girêdan di heman hilpekînê de vedibe (_self)',
            'target_blank_desc' => 'Girêdan di hilpekîna nû de vedibe (_blank)',
            'status_published_desc' => 'Pêşek di cih de li ser malperê tê dîtin',
            'status_draft_desc' => 'Tenê di panela rêveberiyê de tê parastin',
            'step_prefix' => 'Gav',
        ],
        'validation' => [
            'name_required' => 'Navê pêşekê pêwîst e.',
            'name_min' => 'Navê pêşekê divê herî kêm 2 tîp be.',
            'parent_required' => 'Divê pêşeka dayîk were hilbijartin.',
        ],
    ],
];
$lang['mod_menu']['slots'] = [
    'header' => 'Pêşeka Jor',
    'footer' => 'Pêşeka Jêr',
    'action' => 'Pêşeka Çalakiyê',
];
$lang['mod_menu']['parent_labels'] = [
    'select_parent' => 'Pêşeka dayîk hilbijêre',
    'language_root' => 'Asta jor di bin :language de',
    'system_root' => 'Koka pergalê',
];
