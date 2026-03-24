<?php
/**
 * Menu Module - Kurdish Language File
 *
 * @package Rojname
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

    // === Page Titles ===
    'list' => 'Rêveberîya Menûyan',
    'add' => 'Zêde Bike',
    'edit' => 'Biguhere',
    'information' => 'Agahdarî',

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Menû Zêde Bike',
        'edit' => 'Biguhere',
        'list' => 'Lîsteya Menûyan',
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
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Menû bi serkeftî hate zêdekirin!',
            'updated' => 'Menû bi serkeftî hate guhertin!',
            'deleted' => 'Menû bi serkeftî hate jêbirin!',
            'reordered' => 'Rêza menû hate guhertin!',
            'delete' => 'Menû hate jêbirin!',
        ],
        'error' => [
            'not_added' => 'Zêdekirina menû biserneket!',
            'not_updated' => 'guhertina menû biserneket!',
            'not_deleted' => 'Jêbirina menû biserneket!',
            'not_found' => 'Tomara menûya ku hatî destnîşankirin nehat dîtin!',
        ],
        'warning' => [
            'delete' => 'Hûn ewle ne ku dixwazin vê menûyê jê bibin?',
            'reorder' => 'Cihê menûyê were guhertin?',
        ],
        'info' => [
            'edit_double_click' => 'Ji bo sererastkirinê ser menûyê ku tu dixwazî du caran bitikîne',
            'drag_reorder' => 'Menûyê bikşîne û berde ji bo guherandina rêzê',
            'block_system_removed' => 'Pergala blok hate rakirin - ev taybetî naha tune ye',
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

        'id' => 'ID',
        'name' => 'Navê Menûyê',
        'url' => 'URL ya Menûyê',
        'parent' => 'Menûya Dayîk',
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
        'parent_menu' => 'Serpêşek',
        'order' => 'Rêz',
        'target' => 'Bila çawa vebe?',
        'status' => 'Rewş',
        'icon' => 'Îkona Bootstrapê',
        'access_level' => 'Destûra Dîtinê',
        'actions' => 'Çalakî',
        'name' => 'Navê Menûyê',
        'parent_id' => 'Serpêşek',
        'status' => 'Weşandin',
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
            'parent_id' => 'Serpêşek',
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
    'add' => 'Zêde Bike',
    'edit' => 'Biguhere',
    'oke' => 'Serkeftî!',
    'error' => 'Çewtî!',
    'hata' => 'Çewtî!',
    'remove_record' => 'Tomar Jê Bibe',

    // === Legacy Message Keys (used by index.php) ===
    'message' => [
        'oke' => [
            'add' => 'Menû bi serkeftî hate zêdekirin!',
            'edit' => 'Menû bi serkeftî hate guhertin!',
        ],
        'error' => [
            'add' => 'Zêdekirina menûyê biserneket!',
            'edit' => 'guhertina menûyê biserneket!',
        ],
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Menûya Nû Zêde Bike',
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
        'title' => 'Destûrên Rêveberiya Menûyan',
        'view' => 'Menûyan Bibîne',
        'add' => 'Menû Zêde Bike',
        'edit_own' => 'Menûya Xwe Biguhere',
        'edit_all' => 'Hemû Menûyan Biguhere',
        'delete_own' => 'Menûya Xwe Jê Bibe',
        'delete_all' => 'Hemû Menûyan Jê Bibe',
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
        'parent_menu' => 'Hilbijêre ku vê menû di bin kîjan menûyê de were nîşandan',
        'access_control' => 'Destnîşan bike ku kîjan rolên bikarhêner dikarin vê menûyê bibînin',
        'url' => 'Ji bo girêdanên navxwe /formatê bikar bînin, ji bo girêdanên derve bi https:// dest pê bikin',
        'order' => 'Rêza menûyê diyar dike (nirxên nizm jorîn tên nîşandan)',
        'icon' => 'Navê Îkona Bootstrapê binivîse (mînak: bi-house)',
        'file' => 'Navê pel an modulê (mînak: mod_page, index.php). Dema URL tunebe ev pel tê xebitandin.',
        'html' => 'Taybetmendiyên HTML an navên class\'ên wekî CSS (mînak: class="text-danger")',
    ],

    // === Placeholders ===
    'placeholders' => [
        'menu_name' => 'Navê pêşekê binivîse...',
        'menu_url' => 'https://example.com an /page',
        'menu_description' => 'Ravekirina menûyê (bijarte)',
        'html_class' => 'Mifteya CSS',
        'icon_class' => 'Îkona Bootstrapê',
    ],

    // === Empty States ===
    'empty' => [
        'no_menus' => 'Hîn tu menû nehatiye zêdekirin',
        'no_results' => 'Tu menûya ku li gorî lêgerîna te hebe nehat dîtin',
        'add_first' => 'Ji bo zêdekirina yekem menûyê bişkoja bitikîne',
    ],

    // === Loading States ===
    'loading' => [
        'saving' => 'Tomarkirin...',
        'loading' => 'Lêbarkirin...',
        'processing' => 'Tê bicihanîn...',
    ],

    // === Success Messages ===
    'success' => [
        'operation_completed' => 'Çalakî bi serkeftî hate xwestin!',
        'changes_saved' => 'Guhertinên hatin tomar!',
    ],

    // === Error Messages ===
    'errors' => [
        'general' => 'Çewtî çêbû. Ji kerema xwe dîsa biceribîne.',
        'network' => 'Çewtiya torê. Ji kerema xwe girêdana înternetê xwe kontrol bike.',
        'server' => 'Çewtiya serverê. Ji kerema xwe paşê dîsa biceribîne.',
        'record' => 'Tomar Nehat Dîtin',
    ],
    
    // === ACL Roles ===
    'roll' => [
        'ad' => 'Rolên Rêveberiya Menûyan',
        'add' => 'Dikare Menû Zêde Bike',
        'edit' => 'Dikare Menû Biguhere',
        'delete' => 'Dikare Menû Jê Bibe',
        'list' => 'Dikare Rêveberiya Menûyan Bibîne',
        'moderate_edit' => 'Dikare Menûyên Kesên Din Biguhere',
        'moderate_delete' => 'Dikare Menûyên Kesên Din Jê Bibe',
    ],

    // === ACL Error Messages ===
    'role_errors' => [
        'add' => 'Destûra te ji bo zêdekirina menûyê tune',
        'edit' => 'Destûra te ji bo sererastkirina menûyê tune',
        'delete' => 'Destûra te ji bo jêbirina menûyê tune',
        'list' => 'Destûra te ji bo dîtina rêveberiya menûyan tune',
        'moderate_edit' => 'Destûra te ji bo sererastkirina menûyên kesên din tune',
        'moderate_delete' => 'Destûra te ji bo jêbirina menûyên kesên din tune',
    ],

];