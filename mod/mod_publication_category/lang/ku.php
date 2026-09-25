<?php
/**
 * Publication Category Module - Kurdish Language File
 *
 * @package AKM ARTIST CMS
 * @module  mod_publication_category
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Category Module Language Translations
 */
$lang['mod_publication_category'] = [

    // === Basic Navigation ===
    'list' => 'Lîsteya Kategorîyan',
    'add' => 'Kategorî Zêde Bike',
    'edit' => 'Biguhere',
    'information' => 'Agahî',
    'success' => 'Çalakî Serkeftî',
    'error' => 'Çewtiya Giran Çêbû',
    'remove_record' => 'Ji vê tomarê jê bibin tu pê bawer î?',
    'choose_wish' => 'Hilbijêre',

    // === Message Structure ===
    'message' => [
        'success' => [
            'add' => 'Kategorî bi serkeftî hat zêdekirin!',
            'edit' => 'Kategorî bi serkeftî hat guhertin!',
        ],
        'error' => [
            'add' => 'Zêdekirina kategoriyan serneket!',
            'edit' => 'guhertina kategoriyan serneket!',
        ],
        'information' => [
            'moderate' => 'Ji bo sererastkirinê dubare li kategoriya xwe bitikîne',
            'move' => 'Ji bo guherandina rêzê kategoriya xwe bişîvîne û biavêje',
        ],
    ],

    'errors' => [
        'record' => 'Toma hatiye spartin nehate dîtin!',
    ],

    'button' => [
        'add' => 'Zêde Bike',
        'edit' => 'Biguhere',
        'refresh' => 'Nû Bike',
        'delete' => 'Jê Bibe',
        'cancel' => 'Betal',
        'remove' => 'Rake',
        'yes' => 'Erê',
        'no' => 'Na',
        'save' => 'Tomar Bike',
    ],

    'grid' => [
        'showing_results' => 'Ji {total} encaman {from} - {to} tê xuyan',
        'page' => 'Rûpel',
        'page_count' => 'Hejmara Rûpelan',
        'loading' => 'Daneyên tê barkirin, ji kerema xwe re bisekinin...',
        'no_data' => 'Daneyên nehate dîtin',
        'connection_error' => 'Çewtiya Girêdanê',
        'search' => 'Lêgerîn',
        'select_at_least_one' => 'Ji kerema xwe re kêmî tayek hilbijêrin',
        'select_only_one' => 'Ji kerema xwe re tenê tayek hilbijêrin',
    ],

    'validator' => [
        'required' => 'Ji kerema xwe re qada %s vala nebinin',
        'length' => 'Qada %s divê di navbera %s û %s karakteran de be',
        'email' => 'Ji kerema xwe re di qada %s de navnîşana e-postayê ya derbasdar binivîsin',
        'unique' => '%s ku nivîsandî di pergalê de hatî tomarkirin. Ji kerema xwe re %s-ek din specîfî bikin!',
        'inarray' => 'Qada %s tenê nirxên \' %s \' dipejirîne',
    ],

    'role' => [
        'name' => 'Rênîşana Rola Rêveberiya Kategoriyan',
        'add' => 'Dikare Kategoriyan Zêde Bike',
        'edit' => 'Dikare Kategoriyan biguhere',
        'delete' => 'Dikare Kategoriyan Jê Bibe',
        'list' => 'Dikare Rêveberiya Kategoriyan Bibîne',
        'moderate_edit' => 'Dikare tomarên kategoriyan ên endamên din biguhere',
        'moderate_delete' => 'Dikare tomarên kategoriyan ên yên din jê bibe',
    ],

    'role_errors' => [
        'moderate_edit' => 'Tu nikarî tomarên kategoriyan ên yên din sererast bikî',
        'moderate_delete' => 'Tu nikarî tomarên kategoriyan ên yên din jê bibî',
        'add' => 'Destûra te ji bo zêdekirina kategoriyan tuneye',
        'edit' => 'Destûra te ji bo sererastkirina kategoriyan tuneye',
        'list' => 'Destûra te ji bo dîtina rêveberiya kategoriyan tuneye',
        'delete' => 'Destûra te ji bo jêbirina kategoriyan tuneye',
    ],

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Kategorî Zêde Bike',
        'edit' => 'Biguhere',
        'list' => 'Lîsteya Kategorîyan',
        'delete' => 'Kategoriyê Jê Bibe',
        'cancel' => 'Betal',
        'save' => 'Tomar Bike',
        'update' => 'Rojane Bike',
        'remove' => 'Rake',
        'unlock_reorder' => 'Moda Kaşkirinê Veke',
        'lock_reorder' => 'Moda Kaşkirinê Bigire',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Kategorî bi serkeftî hat zêdekirin!',
            'updated' => 'Kategorî bi serkeftî hat guhertin!',
            'deleted' => 'Kategorî bi serkeftî hat jêbirin!',
            'reordered' => 'Rêza kategoriyan hate guhertin!',
            'image_removed' => 'Wêne bi serkeftî hate rakirin!',
        ],
        'error' => [
            'not_added' => 'Zêdekirina kategoriyan serneket!',
            'not_updated' => 'guhertina kategoriyan serneket!',
            'not_deleted' => 'Jêbirina kategoriyan serneket!',
            'not_found' => 'Toma kategoriya hatiye spartin nehate dîtin!',
            'image_not_removed' => 'Rakirina wêneyê serneket!',
            'validation' => 'Ji kerema xwe re qadên pêwîst rast dagirin!',
        ],
        'info' => [
            'edit_double_click' => 'Ji bo sererastkirinê dubare li kategoriya xwe bitikîne',
            'drag_reorder' => 'Ji bo guherandina rêzê kategoriya xwe bişîvîne û biavêje',
        ],
        'warning' => [
            'reorder' => 'Cihê kategoriyê were guhertin?',
        ],
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Erê',
        'no' => 'Na',
    ],

    // === Form Labels ===
    'form' => [
        'basic_info' => 'Agahiya Bingehîn',
        'seo_settings' => 'Mîhengên SEO',
        'display_settings' => 'Mîhengên Nîşandanê',
        'advanced_settings' => 'Mîhengên Pêşkeftî',

        'id' => 'ID',
        'name' => 'Navê Kategoriyê',
        'parent_category' => 'Kategoriya Jorîn',
        'order' => 'Rêz',
        'language' => 'Ziman',
        'is_root' => 'Kategoriya Root (Nayê Nîşandan)',
        'seo_url' => 'URL ya SEO',
        'meta_title' => 'Meta Sernav (Ji bo Motora Lêgerînê)',
        'meta_keywords' => 'Meta Peyvên Mifşeyan (Ji bo Motora Lêgerînê)',
        'meta_description' => 'Meta Rave (Ji bo Motora Lêgerînê)',
        'add_to_menu' => 'Kategoriyê Li Pêşekê Lêke',
        'image' => 'Wêneya Kategoriya',
        'description' => 'Raveya Kategoriya',
        'access_control' => 'Kontrola Gihîştinê',
    ],

    // === Table Columns ===
    'columns' => [
        'id' => 'ID',
        'language' => 'Ziman',
        'name' => 'Navê Kategoriyê',
        'parent_category' => 'Kategoriya Jorîn',
        'order' => 'Rêz',
        'root_category' => 'Kategoriya Root',
        'seo_url' => 'URL ya SEO',
        'meta_title' => 'Meta Sernav',
        'meta_keywords' => 'Meta Peyvên Mifşeyan',
        'meta_description' => 'Meta Rave',
        'add_to_menu' => 'Li Pêşekê Lêke',
        'image' => 'Wêne',
        'status' => 'Rewş',
        'actions' => 'Çalakî',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Kategoriya Nû Zêde Bike',
        'edit' => 'Hilbijartinê Biguhere',
        'delete' => 'Hilbijartinan Jê Bibe',
        'refresh' => 'Nû Bike',
        'cancel' => 'Betal',
        'remove' => 'Rake',
        'yes' => 'Erê',
        'no' => 'Na',
    ],

    // === Validation Messages ===
    'validation' => [
        'required' => 'Ji kerema xwe re qada %s vala nebinin',
        'length' => 'Qada %s divê di navbera %s û %s karakteran de be',
        'email' => 'Ji kerema xwe re di qada %s de navnîşana e-postayê ya derbasdar binivîsin',
        'unique' => '%s ku nivîsandî di pergalê de hatî tomarkirin. Ji kerema xwe re %s-ek din specîfî bikin!',
        'inarray' => 'Qada %s tenê nirxên \' %s \' dipejirîne',
        'url' => 'Ji kerema xwe re URL ya derbasdar binivîsin',
        'slug' => 'Ji kerema xwe re URL slug ya derbasdar binivîsin',
    ],

    // === Access Control (ACL) ===
    'permissions' => [
        'title' => 'Destûrên Rêveberiya Kategoriyan',
        'add' => 'Dikare kategoriyan lê zêde bike',
        'edit' => 'Dikare kategoriyan biguhere',
        'delete' => 'Dikare Kategoriyan Jê Bibe',
        'list' => 'Dikare Rêveberiya Kategoriyan Bibîne',
        'edit_others' => 'Dikare tomarên kategoriyan ên yên din biguhere',
        'delete_others' => 'Dikare tomarên kategoriyan ên yên din jê bibe',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'edit_others' => 'Tu nikarî tomarên kategoriyan ên yên din sererast bikî',
        'delete_others' => 'Tu nikarî tomarên kategoriyan ên yên din jê bibî',
        'add' => 'Destûra te ji bo zêdekirina kategoriyan tuneye',
        'edit' => 'Destûra te ji bo sererastkirina kategoriyan tuneye',
        'list' => 'Destûra te ji bo dîtina rêveberiya kategoriyan tuneye',
        'delete' => 'Destûra te ji bo jêbirina kategoriyan tuneye',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Ziman',
            'name' => 'Navê Kategoriyê',
            'parent_id' => 'Kategoriya Jorîn',
            'order' => 'Rêz',
            'root' => 'Kategoriya Root (Nayê Nîşandan)',
            'seo' => 'URL ya Kategoriya',
            'title' => 'Sernav (Ji bo Motora Lêgerînê)',
            'keywords' => 'Peyvên Mifşeyan (Ji bo Motora Lêgerînê)',
            'description' => 'Ji bo mîhengên SEO ravekirin (Description)',
            'menu' => 'Kategoriyê Bişîne Pêşekê',
            'image' => 'Wêne',
            'acl_id' => 'Kî dikare bibîne?',
        ],
        'select_values' => [
            'root_yes' => 'Erê',
            'root_no' => 'Na',
        ],
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'remove_image' => 'Ma tu bawer î ku dixwazî vî wêneyî jêbibî?',
        'remove_record' => 'Ma tu bawer î ku dixwazî vê tomariyê jêbibî?',
        'delete_single' => 'Ma tu bawer î ku dixwazî vê beşê jêbibî?',
        'delete_multiple' => 'Ma tu bawer î ku dixwazî :count kategoriya hilbijartî jêbibî?',
        'make_root' => 'Ma tu bawer î ku dixwazî vê kategoriyê bikî kategoriya root?',
        'remove_root' => 'Ma tu bawer î ku dixwazî vê kategoriyê ji rewşa root derxî?',
        'reorder_single' => 'Tu dixwazî vê kategoriyê bibî ciyekî nû?',
    ],

    // === Success Messages ===
    'success_messages' => [
        'operation_completed' => 'Daxwaza te bi serkeftin hate temamkirin!',
        'changes_saved' => 'Guhartin hatin tomarkirin!',
        'category_published' => 'Kategorî bi serkeftin hate weşandin!',
        'category_unpublished' => 'Kategorî bi serkeftin ji weşanê hate derxistin!',
    ],

    // === Error Messages ===
    'error_messages' => [
        'general' => 'Çewtiyek çêbû. Ji kerema xwe re dîsa biceribîne.',
        'network' => 'Çewtiya torê. Girêdana înterneta xwe kontrol bike.',
        'server' => 'Çewtiya serverê. Ji kerema xwe re paşê dîsa biceribîne.',
        'fatal' => 'Çewtiya giran çêbû!',
        'has_subcategories' => 'Kategoriya ku kategoriya binê lê heye nayê jêbirin',
        'has_content' => 'Kategoriya ku naverok lê heye nayê jêbirin',
    ],

    // === Helper Text ===
    'help' => [
        'category_name' => 'Ji bo vê kategoriya navekî zelal û raveker binivîse',
        'parent_category' => 'Kategoriya jorîn hilbijêre (ji bo asta jorîn vala bihêle)',
        'order' => 'Rêza nîşandanê diyar dike (nirxên nizmî pêş tên)',
        'root_category' => 'Kategoriya root ji bo armancên rêkxistinê ne û ne rastî ne hene ku were xuyakirin',
        'seo_url' => 'Versiyona URL-hev a navê kategoriya (di girêdanan de tê bikaranîn)',
        'meta_title' => 'Sernavê ku di encamên lêgerîna motorê de tê xuyakirin',
        'meta_description' => 'Raveya ku di encamên lêgerîna motorê de tê xuyakirin',
        'add_to_menu' => 'Vê kategoriyê bixweber li pêşeka navîgasyonê lêke',
        'access_control' => 'Kîjan rolên bikarhêner ên dikarin bigihîjin vê kategoriya specîfî bike',
    ],

    // === Placeholders ===
    'placeholders' => [
        'category_name' => 'Navê kategoriyê binivîse...',
        'seo_url' => 'kategoriya-url-slug',
        'meta_title' => 'Meta sernavê binivîse...',
        'meta_description' => 'Meta ravesê binivîse...',
        'meta_keywords' => 'mifaşe1, mifaşe2, mifaşe3',
        'category_description' => 'Raveya kategoriya binivîse (bijarte)...',
    ],

    // === Empty States ===
    'empty' => [
        'no_categories' => 'Heta niha tu kategoriya nehatiye avakirin',
        'no_results' => 'Li gorî krîterên lêgerîna te kategoriya nehate dîtin',
        'add_first' => 'Ji bo afirandina kategoriya yekem li bişkojka bitikîne',
        'no_subcategories' => 'Kategoriya binê nehate dîtin',
        'no_content' => 'Di vê kategoriya de heta niha naverok tune',
    ],

    // === Loading States ===
    'loading' => [
        'saving' => 'Tê tomarkirin...',
        'loading' => 'Tê daxistin...',
        'processing' => 'Tê bicihanîn...',
        'deleting' => 'Tê jêbirin...',
    ],

    // === Category Tree View ===
    'tree_view' => [
        'expand_all' => 'Hemûyan Fireh Bike',
        'collapse_all' => 'Hemûan Kûr Bike',
        'show_hierarchy' => 'Hiyerarşî Nîşan Bide',
        'show_flat' => 'Xuyakirina Rast',
        'root_only' => 'Tenê Kategoriya Root',
        'leaf_only' => 'Tenê Kategoriya Binê',
    ],

    // === SEO Features ===
    'seo' => [
        'auto_generate_slug' => 'URL slug bixweber biafirîne',
        'preview_snippet' => 'Encama Lêgerînê Pêş bibîne',
        'character_count' => 'Hejmara karakteran: :count',
        'recommended_length' => 'Pêşniyazkirî: :min-:max karakter',
        'good' => 'Baş',
        'too_short' => 'Gelek kurt',
        'too_long' => 'Gelek dirêj',
    ],

    // === Bulk Operations ===
    'bulk_operations' => [
        'bulk_edit' => 'Bi Tiştî Biguhere',
        'bulk_delete' => 'Bi Tiştî Jêbibe',
        'bulk_move' => 'Bişîne Kategoriya',
        'bulk_publish' => 'Hilbijartinan Weşîne',
        'bulk_unpublish' => 'Weşana Hilbijartinan Rake',
        'select_all' => 'Hemûyan Hilbijêre',
        'deselect_all' => 'Hilbijartina Hemûyan Rake',
    ],

];