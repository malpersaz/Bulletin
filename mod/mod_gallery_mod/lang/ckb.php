<?php
/**
 * Gallery Module Integration - Kurdish Language File
 *
 * @package AKM Business
 * @module  mod_gallery_mod
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Gallery Module Integration Language Translations
 */
$lang['mod_gallery_mod'] = [

    // === Basic Navigation ===
    'list' => 'Lîsteya Pêvekên Galeriyê',
    'add' => 'Pêveka Galeriyê Lêke',
    'edit' => 'Pêveka Galeriyê Biguhere',
    'information' => 'Agahî',
    'success' => 'Çalakî Bi Ser Keft!',
    'error' => 'Çewtiya Giran Çêbû!',
    'remove_record' => 'Ji bo jêbirina vê tomarê tu pê bawer î?',
    'choose_wish' => 'Hilbijêre',

    // === Message Structure ===
    'message' => [
        'success' => [
            'add' => 'Pêveka Galeriyê bi serkeftî hate lêkirin!',
            'edit' => 'Pêveka Galeriyê bi serkeftî hate guhartin!',
        ],
        'error' => [
            'add' => 'Pêveka Galeriyê nehat lêkirin!',
            'edit' => 'Pêveka Galeriyê nehat guhartin!',
        ],
    ],

    'errors' => [
        'record' => 'Tomara ku te diyar kiriye nehat dîtin!',
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
        'gallery' => 'Galeri',
        'file' => 'Pel',
    ],

    'grid' => [
        'showing_results' => 'Ji nav {total} encaman ji {from} heta {to} tê xwiyakirin.',
        'page' => 'Rûpel',
        'page_count' => 'Hejmara Rûpelan',
        'loading' => 'Daneyên tê barkirin, ji kerema xwe li hêviyê bimîne ...',
        'no_data' => 'Tu dane nehatin dîtin',
        'connection_error' => 'Şaşiya Pêwendiyê',
        'search' => 'Lêgerîn',
        'select_at_least_one' => 'Ji kerema xwe herî kêm rêzikekê hilbijêre',
        'select_only_one' => 'Ji kerema xwe tenê rêzikekê hilbijêre',
    ],

    'validator' => [
        'required' => 'Ji kerema xwe qada %s vala nehêle',
        'length' => '%s divê herî kêm %s û herî zêde %s tîpan pêk wore',
        'email' => 'Ji kerema xwe ji bo qada %s navnîşaneke e-poste ya derbasdar binivîse.',
        'unique' => '%s ku te nivîsî di pergalê de qeydkirî ye. Ji kerema xwe %s-ekê din destnîşan bikin!',
        'inarray' => 'Qada %s dikare tenê nirxên \' %s \' bigire',
    ],

    'role' => [
        'name' => 'Rolenên Rêveberiya Gallery Mod',
        'add' => 'Gallery Mod dikare zêde bike',
        'edit' => 'Gallery Mod dikare Biguhere',
        'delete' => 'Gallery Mod dikare jê bibe',
        'list' => 'Dikare Rêveberiya Gallery Mod bibîne',
        'moderate_edit' => 'Dikare tomarên Gallery Mod yên ku ne yên wî ne Biguhere',
        'moderate_delete' => 'Dikare tomarên Gallery Mod yên ku ne yên wî ne rake',
    ],

    'role_errors' => [
        'moderate_edit' => 'Tu nikarî tomarên Gallery Mod yên ku ne yên te ne sererast bikî',
        'moderate_delete' => 'Tu nikarî tomarên Gallery Mod yên ku ne yên te ne rakî',
        'add' => 'Te destûra zêdekirina Gallery Modan nîne',
        'edit' => 'Te destûra sererastkirina Gallery Modan nîne',
        'list' => 'Te destûra dîtina Rêveberiya Gallery Mod nîne',
        'delete' => 'Te destûra jêbirina Gallery Modan nîne',
    ],

    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'Navê Gallery Mod',
            'title' => 'Navê Gallery Mod',
            'mod_name' => 'Navê Mod',
            'mod_key' => 'Mifteya Mod (ID)',
            'mod_value' => 'Nirxa Mod (Nav)',
        ],
    ],

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Gallery Mod Zêde Bike',
        'edit' => 'Gallery Mod Biguhere',
        'list' => 'Lîsteya Gallery Mod',
        'delete' => 'Gallery Mod Jê Bibe',
        'cancel' => 'Betal',
        'save' => 'Tomar Bike',
        'update' => 'Rojane Bike',
        'remove' => 'Rake',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Gallery Mod bi serkeftî hate zêdekirin!',
            'updated' => 'Gallery Mod bi serkeftî hate guhertin!',
            'deleted' => 'Gallery Mod bi serkeftî hate jêbirin!',
            'configured' => 'Gallery Mod bi serkeftî hate mîhengkirin!',
        ],
        'error' => [
            'not_added' => 'Gallery Mod ne hate zêdekirin!',
            'not_updated' => 'Gallery Mod ne hate guhertin!',
            'not_deleted' => 'Gallery Mod ne hate jêbirin!',
            'not_found' => 'Gallery Modê ku hate nîşankirin ne hate dîtin!',
            'configuration_failed' => 'Mîhengkirina Gallery Mod biserneket!',
        ],
        'info' => [
            'module_intro' => 'Gallery modên din pêvekên pergalê de jêrînên wêneyan peyda dikin',
            'configuration_info' => 'Ji bo çalakkirina operasyonên wêneyan di modên taybet de mîhengên gallery modê mîheng bike',
        ],
    ],

    // === Gallery Module Configuration ===
    'configuration' => [
        'basic_settings' => 'Mîhengên Bingehîn',
        'display_settings' => 'Mîhengên Nîşankirinê',
        'advanced_settings' => 'Mîhengên Pêşkeftî',
        'integration_settings' => 'Mîhengên Yekbûnê',

        'module_name' => 'Navê Mod',
        'module_description' => 'Daxuyaniya Mod',
        'gallery_type' => 'Cureyê Galeriyê',
        'display_mode' => 'Moda Nîşankirinê',
        'image_size' => 'Mezinahiya Wêneyê',
        'thumbnail_size' => 'Mezinahiya Wêneyê Biçûk',
        'columns_per_row' => 'Stûnên Li Rêzekê',
        'auto_slideshow' => 'Xuyangkirina Otomatîk',
        'slideshow_interval' => 'Navbera Xuyangkirinê (çirke)',
        'show_captions' => 'Sernivîsan Nîşan Bide',
        'enable_lightbox' => 'Lightbox Çalak Bike',
        'enable_zoom' => 'Nêzîkbûnê Çalak Bike',
        'enable_download' => 'Daxistinê Çalak Bike',
    ],

    // === Gallery Types ===
    'gallery_types' => [
        'image_gallery' => 'Galeriya Wêneyan',
        'photo_album' => 'Albuma Wêneyan',
        'portfolio' => 'Portfolyo',
        'product_gallery' => 'Galeriya Hilberan',
        'showcase' => 'Pêşangeh',
        'slider_gallery' => 'Galeriya Slayderê',
        'masonry' => 'Rêbaza Mason',
        'grid' => 'Rêbaza Torê',
    ],

    // === Display Modes ===
    'display_modes' => [
        'grid' => 'Tor',
        'list' => 'Lîste',
        'masonry' => 'Mason',
        'carousel' => 'Karusel',
        'slider' => 'Slayder',
        'lightbox' => 'Lightbox',
        'modal' => 'Modal',
        'inline' => 'Di Rêzê de',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'remove_record' => 'Ji rakirana vê tomarê bawer î?',
        'delete_single' => 'Bi rastî jî vê Gallery Modê jê bibin?',
        'delete_multiple' => 'Bi rastî jî :count Gallery Modên hilbijartî jê bibin?',
        'reset_configuration' => 'Mîhengkirina modê ji nû ve saz bikin?',
        'clear_gallery' => 'Hemû wêneyan ji vê galeriyê paqij bikin?',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Zêde Bike',
        'edit' => 'Biguhere',
        'refresh' => 'Nû Bike',
        'delete' => 'Jê Bibe',
        'cancel' => 'Betal',
        'remove' => 'Rake',
        'yes' => 'Erê',
        'no' => 'Na',
        'configure' => 'Mîheng Bike',
        'preview' => 'Pêşdîtin',
        'test' => 'Test Bike',
        'reset' => 'Ji Nû Ve Saz Bike',
    ],

    // === Data Grid Messages ===
    'grid_messages' => [
        'pagination' => '{from} heta {to} ji {total} encaman tê xuyakirin',
        'page' => 'Rûpel',
        'page_count' => 'Hejmara Rûpelan',
        'loading' => 'Daneyên têne barkirin, ji kerema xwe bisekinin...',
        'no_data' => 'Daneyek nehat dîtin',
        'connection_error' => 'Çewtiya Girêdanê',
        'search' => 'Lêbigere',
        'select_at_least_one' => 'Ji kerema xwe re kêmînek rêzek hilbijêrin',
        'select_only_one' => 'Ji kerema xwe re tenê rêzek hilbijêrin',
    ],

    // === Validation Messages ===
    'validation' => [
        'required' => 'Ji kerema xwe re qada %s vala nebin',
        'length' => '%s divê navbera %s û %s karakteran de be',
        'email' => 'Ji kerema xwe re di qada %s de navnîşana e-postayê ya derbasdar binivîsin',
        'unique' => '%s ku nivîsandiye di pergalê de qeydkirî ye. Ji kerema xwe re %s-ekê cuda bidin!',
        'inarray' => 'Qada %s tenê nirxên \' %s \' dipejirîne',
        'module_name_required' => 'Navê mod pêwîst e',
        'gallery_type_required' => 'Hilbijartina cureyê galeriyê pêwîst e',
        'invalid_image_size' => 'Mezinahiya wêneya nederbasdar hate diyarkirin',
        'configuration_invalid' => 'Mîhengkirina modê nederbasdar e',
    ],

    // === Access Control (ACL) ===
    'permissions' => [
        'title' => 'Destûrên Rêveberiya Gallery Mod',
        'add' => 'Gallery Mod dikare zêde bike',
        'edit' => 'Gallery Mod dikare Biguhere',
        'delete' => 'Gallery Mod dikare jê bibe',
        'list' => 'Dikare Rêveberiya Gallery Mod bibîne',
        'configure' => 'Gallery modên dikare mîheng bike',
        'preview' => 'Gallery modên dikare pêşdîtîne bike',
        'test' => 'Gallery modên dikare test bike',
        'edit_others' => 'Dikare tomarên Gallery Mod yên ku ên din in Biguhere',
        'delete_others' => 'Dikare tomarên Gallery Mod yên ku ên din in jê bibe',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'edit_others' => 'Tu nikarî tomarên Gallery Mod yên ên din sererast bikî',
        'delete_others' => 'Tu nikarî tomarên Gallery Mod yên ên din jê bibe',
        'add' => 'Destûra te ji bo zêdekirina Gallery Modan tune',
        'edit' => 'Destûra te ji bo sererastkirina Gallery Modan tune',
        'list' => 'Destûra te ji bo dîtina Rêveberiya Gallery Mod tune',
        'delete' => 'Destûra te ji bo jêbirina Gallery Modan tune',
        'configure' => 'Destûra te ji bo mîhengkirina Gallery Modan tune',
        'preview' => 'Destûra te ji bo pêşdîtina Gallery Modan tune',
        'test' => 'Destûra te ji bo testkirina Gallery Modan tune',
    ],

    // === Admin Panel Configuration ===
    'admin_columns' => [
        'id' => 'ID',
        'name' => 'Navê Gallery Mod',
        'mod_name' => 'Navê Mod',
        'mod_key' => 'Mifteya Mod (ID)',
        'mod_value' => 'Nirxa Mod (Nav)',
        'gallery_type' => 'Cureyê Galeriyê',
        'display_mode' => 'Moda Nîşankirinê',
        'status' => 'Rewş',
        'created_date' => 'Dîroka Afirandinê',
        'modified_date' => 'Dîroka Guherandinê',
        'actions' => 'Çalakî',
    ],

    // === Image Management ===
    'image_management' => [
        'upload_images' => 'Wêneyan Bar Bike',
        'manage_images' => 'Wêneyan Rêve Bike',
        'bulk_upload' => 'Barberiya Girî',
        'drag_drop' => 'Wêneyan Kişîne û Bavêje',
        'reorder_images' => 'Wêneyan Ji Nû Ve Rêz Bike',
        'crop_images' => 'Wêneyan Jê Bike',
        'resize_images' => 'Mezinahiya Wêneyan Biguherîne',
        'optimize_images' => 'Wêneyan Optîmîze Bike',
        'image_quality' => 'Kalîteya Wêneyê',
        'compression_level' => 'Astê Sînorkirinê',
        'auto_optimize' => 'Otomatîk Optîmîze Bike',
    ],

    // === Gallery Features ===
    'features' => [
        'lightbox_enabled' => 'Lightbox Çalak',
        'zoom_enabled' => 'Nêzîkbûn Çalak',
        'fullscreen_enabled' => 'Ekvana Tevahî Çalak',
        'thumbnails_enabled' => 'Wêneyên Biçûk Çalak',
        'captions_enabled' => 'Sernivîsan Çalak',
        'navigation_enabled' => 'Navîgasyon Çalak',
        'keyboard_navigation' => 'Navîgasyonê Klavyeyê',
        'touch_gestures' => 'Destanê Dotikandinê',
        'lazy_loading' => 'Barkirina Westar',
        'infinite_scroll' => 'Lêçûna Bêdawî',
    ],

    // === Display Settings ===
    'display_settings' => [
        'gallery_width' => 'Firehiya Galeriyê',
        'gallery_height' => 'Dirêjahiya Galeriyê',
        'image_spacing' => 'Navbera Wêneyan',
        'border_radius' => 'Nîv-çargoşeya Sînoran',
        'shadow_effect' => 'Derasa Siyayî',
        'hover_effect' => 'Derasa Serê',
        'transition_effect' => 'Derasa Derbasbûnê',
        'animation_speed' => 'Leza Anîmasyonê',
        'background_color' => 'Rengê Paşperdeyê',
        'border_color' => 'Rengê Sînoran',
    ],

    // === Success Messages ===
    'success_messages' => [
        'operation_completed' => 'Çalakî bi serkeftî hate kirin!',
        'changes_saved' => 'Guherîn bi serkeftî hatin tomarkirin!',
        'images_uploaded' => ':count wêne bi serkeftî hatin barkirin!',
        'gallery_configured' => 'Galerî bi serkeftî hate mîhengkirin!',
        'test_passed' => 'Testa Galeriyê bi serkeftî hate derbas kirin!',
        'preview_ready' => 'Pêşdîtina Galeriyê amade ye!',
    ],

    // === Error Messages ===
    'error_messages' => [
        'general' => 'Çewtîyek çêbû. Ji kerema xwe re dîsa biceribîne.',
        'network' => 'Çewtiya torê. Ji kerema xwe re girêdana xwe ya internetê kontrol bike.',
        'server' => 'Çewtiya serverê. Ji kerema xwe re paşê dîsa biceribîne.',
        'upload_failed' => 'Barkirina wêneyê biserneket',
        'configuration_error' => 'Çewtiya mîhengkirinê çêbû',
        'test_failed' => 'Testa Galeriyê biserneket',
        'module_not_found' => 'Gallery Mod nehat dîtin',
        'invalid_configuration' => 'Mîhengkirina nederbasdar',
    ],

    // === Helper Text ===
    'help' => [
        'module_intro' => 'Gallery modên din pêvekên pergalê de jêrînên wêneyan peyda dikin',
        'configuration_help' => 'Ji bo yekbûna wêneya galeriyê ya guncaw mîhengên modê mîheng bike',
        'upload_help' => 'Ji bo afirandina galeriyê xwe wêneyan bar bikin',
        'display_help' => 'Bikarhêneran galeriya we çawa dê xuya bûn taybet bike',
        'feature_help' => 'Ji bo başkirina ceribandina bikarhêner taybetmendiyên pêşkeftî çalak bike',
    ],

    // === Empty States ===
    'empty_states' => [
        'no_modules' => 'Hîn jî Gallery Moda mîhengkirî tune',
        'no_results' => 'Li gorî krîterên lêgerînê mod nehat dîtin',
        'no_images' => 'Di vê galeriyê de hîn jî wêne tune',
        'add_first_module' => 'Ji bo destpêkirinê Gallery Moda xwe ya yekem zêde bikin',
        'upload_first_image' => 'Ji bo afirandina galeriyê wêneya xwe ya yekem bar bikin',
    ],

    // === Loading States ===
    'loading_states' => [
        'loading_modules' => 'Mod tên barkirin...',
        'uploading_images' => 'Wêney tên barkirin...',
        'processing_images' => 'Wêney tên process kirin...',
        'configuring' => 'Tê mîhengkirin...',
        'previewing' => 'Pêşdîtin tê çêkirin...',
        'testing' => 'Galerî tê testkirin...',
    ],

];
