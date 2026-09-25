<?php
/**
 * Gallery Module Integration - Zazaki Language File
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
    'list' => 'Lîsteya Modulê Galeriye',
    'add' => 'Modulê Galeriye Dekere',
    'edit' => 'Modulê Galeriye Bivurne',
    'information' => 'Zanaye',
    'success' => 'Serkewtî',
    'error' => 'Xeta Bîye',

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Modulê Galeriye Dekere',
        'edit' => 'Modulê Galeriye Bivurne',
        'list' => 'Lîsteya Modulê Galeriye',
        'delete' => 'Modulê Galeriye Bestere',
        'cancel' => 'Betal Bike',
        'save' => 'Qeyd Bike',
        'update' => 'Newe Bike',
        'remove' => 'Bestere',
    ],

    // === Message Structure ===
    'message' => [
        'success' => [
            'add' => 'Modulê Galeriye bi serkewtî ame dekerdene!',
            'edit' => 'Modulê Galeriye bi serkewtî ame newekerdene!',
        ],
        'error' => [
            'add' => 'Modulê Galeriye nêame dekerdene!',
            'edit' => 'Modulê Galeriye nêame newekerdene!',
        ],
    ],

    'errors' => [
        'record' => 'Qeydo ke şima mojna nêame dîyene!',
    ],

    'button' => [
        'add' => 'Dekere',
        'edit' => 'Bivurne',
        'refresh' => 'Newe Bike',
        'delete' => 'Bestere',
        'cancel' => 'Betal Bike',
        'remove' => 'Wedare',
        'yes' => 'Eya',
        'no' => 'Nê',
        'gallery' => 'Galeriye',
        'file' => 'Pelî',
    ],

    'grid' => [
        'showing_results' => 'Mîyanê {total} encaman ra {from} heta {to} yeno mojnayene',
        'page' => 'Pele',
        'page_count' => 'Amarê Pelan',
        'loading' => 'Melumatî yenê barkerdene, kerem kerê pawê...',
        'no_data' => 'Melumat nêame dîyene',
        'connection_error' => 'Xetaya Girêdayîşî',
        'search' => 'Bigêre',
        'select_at_least_one' => 'Kerem kerê qe nêbo yew rêze weçînê',
        'select_only_one' => 'Kerem kerê tenya yew rêze weçînê',
    ],

    'validator' => [
        'required' => 'Kerem kerê cayê %s veng mevazê',
        'length' => '%s tewr kêm %s û tewr zaf %s herfan ra hewce keno',
        'email' => 'Kerem kerê cayê %s de adresê e-postayê raşt binusê',
        'unique' => '%s o ke şima nuşto verê cû qeyd bîyo. Kerem kerê %s-ê bîn binusê!',
        'inarray' => 'Cayê %s tenya erjê \' %s \' qebul keno',
    ],

    'role' => [
        'name' => 'Rolê Îdareyê Modulê Galeriye',
        'add' => 'Şeno Modulê Galeriye Dekero',
        'edit' => 'Şeno Modulê Galeriye Bivurno',
        'delete' => 'Şeno Modulê Galeriye Bestero',
        'list' => 'Şeno Îdareyê Modulê Galeriye Bivêno',
        'moderate_edit' => 'Şeno qeydê modulê galeriye yê kesê bînî bivurno',
        'moderate_delete' => 'Şeno qeydê modulê galeriye yê kesê bînî bestero',
    ],

    'role_errors' => [
        'moderate_edit' => 'Destûrê şima nêdano ke şima qeydê modulê galeriye yê kesê bînî bivurnê',
        'moderate_delete' => 'Destûrê şima nêdano ke şima qeydê modulê galeriye yê kesê bînî besterê',
        'add' => 'Destûrê şima çini yo ke şima Modulê Galeriye dekerê',
        'edit' => 'Destûrê şima çini yo ke şima Modulê Galeriye bivurnê',
        'list' => 'Destûrê şima çini yo ke şima Îdareyê Modulê Galeriye bivênê',
        'delete' => 'Destûrê şima çini yo ke şima Modulê Galeriye besterê',
    ],

    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'Nameyê Modulê Galeriye',
            'mod_name' => 'Nameyê Modulî',
            'mod_key' => 'Kilîta Modulî (ID)',
            'mod_value' => 'Erjê Modulî (Name)',
            'gallery_type' => 'Babetê Galeriye',
            'display_mode' => 'Babetê Mojnayene',
            'status' => 'Wezîyet',
            'created_date' => 'Tarixê Viraştişî',
            'modified_date' => 'Tarixê Vurnayene',
            'actions' => 'Kerdene',
        ],
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Modulê Galeriye bi serkewtî ame dekerdene!',
            'updated' => 'Modulê Galeriye bi serkewtî ame newekerdene!',
            'deleted' => 'Modulê Galeriye bi serkewtî ame besterdene!',
            'configured' => 'Mîhengê Modulê Galeriye bi serkewtî temam bîyî!',
        ],
        'error' => [
            'not_added' => 'Modulê Galeriye nêame dekerdene!',
            'not_updated' => 'Modulê Galeriye nêame newekerdene!',
            'not_deleted' => 'Modulê Galeriye nêame besterdene!',
            'not_found' => 'Modulê Galeriye yo ke hate nîşankerdene nêame dîyene!',
            'configuration_failed' => 'Mîhengkerdişê Modulê Galeriye sernêkewt!',
        ],
        'info' => [
            'module_intro' => 'Modulê galeriye pêvekanê bînan rê îmkanê galeriya resiman danê',
            'configuration_info' => 'Seba çalakkerdişê xebatanê resimî modulê taybetî de mîhenganê modulê galeriye eyar bikerê',
        ],
    ],

    // === Gallery Module Configuration ===
    'configuration' => [
        'basic_settings' => 'Mîhengê Bingeyênî',
        'display_settings' => 'Mîhengê Mojnayene',
        'advanced_settings' => 'Mîhengê Resaye',
        'integration_settings' => 'Mîhengê Pêkardişî',

        'module_name' => 'Nameyê Modulî',
        'module_description' => 'Şiroveyê Modulî',
        'gallery_type' => 'Babetê Galeriye',
        'display_mode' => 'Babetê Mojnayene',
        'image_size' => 'Girdîya Resimî',
        'thumbnail_size' => 'Girdîya Resimê Qickekî',
        'columns_per_row' => 'Rêze de Amara Sitûnan',
        'auto_slideshow' => 'Slayto Xo bi Xo',
        'slideshow_interval' => 'Mabênê Slaytî (saniye)',
        'show_captions' => 'Sernameyan Bimocne',
        'enable_lightbox' => 'Lightbox Çalak Bike',
        'enable_zoom' => 'Nêzîkkerdişî Çalak Bike',
        'enable_download' => 'Ronayene Çalak Bike',
    ],

    // === Gallery Types ===
    'gallery_types' => [
        'image_gallery' => 'Galeriya Resiman',
        'photo_album' => 'Albumê Resiman',
        'portfolio' => 'Portfolyo',
        'product_gallery' => 'Galeriya Berheman',
        'showcase' => 'Pêşengeh',
        'slider_gallery' => 'Galeriya Slayderî',
        'masonry' => 'Rêbaza Masonry',
        'grid' => 'Rêbaza Torî',
    ],

    // === Display Modes ===
    'display_modes' => [
        'grid' => 'Tor',
        'list' => 'Lîste',
        'masonry' => 'Masonry',
        'carousel' => 'Karusel',
        'slider' => 'Slayder',
        'lightbox' => 'Lightbox',
        'modal' => 'Modal',
        'inline' => 'Rêze de',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'remove_record' => 'Şima reya xo ser o yê ke no qeyd besterê?',
        'delete_single' => 'Bi rastî zî şima wazenê nê Modulê Galeriye besterê?',
        'delete_multiple' => 'Bi rastî zî şima wazenê :count Modulê Galeriye yê weçînitî besterê?',
        'reset_configuration' => 'Şima wazenê mîhengê modulî seranser reyna saz bikerê?',
        'clear_gallery' => 'Şima wazenê hemû resiman na galeriye ra hewnakerê?',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Dekere',
        'edit' => 'Bivurne',
        'refresh' => 'Newe Bike',
        'delete' => 'Bestere',
        'cancel' => 'Betal Bike',
        'remove' => 'Wedare',
        'yes' => 'Eya',
        'no' => 'Nê',
        'configure' => 'Mîheng Bike',
        'preview' => 'Pêşdîyene',
        'test' => 'Test Bike',
        'reset' => 'Reyna Saz Bike',
    ],

    // === Validation Messages ===
    'validation' => [
        'required' => 'Kerem kerê cayê %s veng mevazê',
        'length' => '%s tewr kêm %s û tewr zaf %s herfan ra hewce keno',
        'email' => 'Kerem kerê cayê %s de adresê e-postayê raşt binusê',
        'unique' => '%s o ke şima nuşto verê cû qeyd bîyo. Kerem kerê %s-ê bîn binusê!',
        'inarray' => 'Cayê %s tenya erjê \' %s \' qebul keno',
        'module_name_required' => 'Nameyê modulî hewce keno',
        'gallery_type_required' => 'Weçînitişê babetê galeriye hewce keno',
        'invalid_image_size' => 'Girdîya resimî ya nerewa dîyar bîye',
        'configuration_invalid' => 'Mîhengê modulî nerewa yo',
    ],

    // === Access Control (ACL) ===
    'permissions' => [
        'title' => 'Destûrê Îdareyê Modulê Galeriye',
        'add' => 'Şeno Modulê Galeriye Dekero',
        'edit' => 'Şeno Modulê Galeriye Bivurno',
        'delete' => 'Şeno Modulê Galeriye Bestero',
        'list' => 'Şeno Îdareyê Modulê Galeriye Bivêno',
        'configure' => 'Şeno Modulê Galeriye mîheng bikero',
        'preview' => 'Şeno Modulê Galeriye pêşdîyen bikero',
        'test' => 'Şeno Modulê Galeriye test bikero',
        'edit_others' => 'Şeno qeydê modulê galeriye yê kesê bînî bivurno',
        'delete_others' => 'Şeno qeydê modulê galeriye yê kesê bînî bestero',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'edit_others' => 'Destûrê şima nêdano ke şima qeydê modulê galeriye yê kesê bînî bivurnê',
        'delete_others' => 'Destûrê şima nêdano ke şima qeydê modulê galeriye yê kesê bînî besterê',
        'add' => 'Destûrê şima çini yo ke şima Modulê Galeriye dekerê',
        'edit' => 'Destûrê şima çini yo ke şima Modulê Galeriye bivurnê',
        'list' => 'Destûrê şima çini yo ke şima Îdareyê Modulê Galeriye bivênê',
        'delete' => 'Destûrê şima çini yo ke şima Modulê Galeriye besterê',
        'configure' => 'Destûrê şima çini yo ke şima Modulê Galeriye mîheng bikerê',
        'preview' => 'Destûrê şima çini yo ke şima Modulê Galeriye pêşdîyen bikerê',
        'test' => 'Destûrê şima çini yo ke şima Modulê Galeriye test bikerê',
    ],

    // === Image Management ===
    'image_management' => [
        'upload_images' => 'Resiman Bar Bike',
        'manage_images' => 'Resiman Îdare Bike',
        'bulk_upload' => 'Barkerdişê Pêroyî',
        'drag_drop' => 'Resiman Biantê û Bierzê',
        'reorder_images' => 'Resiman Reyna Rêz Bike',
        'crop_images' => 'Resiman Bibirnê',
        'resize_images' => 'Girdîya Resiman Bivurnê',
        'optimize_images' => 'Resiman Optîmîze Bike',
        'image_quality' => 'Kalîteya Resimî',
        'compression_level' => 'Asta Zextkerdişî',
        'auto_optimize' => 'Xo bi Xo Optîmîze Bike',
    ],

    // === Gallery Features ===
    'features' => [
        'lightbox_enabled' => 'Lightbox Çalak o',
        'zoom_enabled' => 'Nêzîkkerdiş Çalak o',
        'fullscreen_enabled' => 'Ekrano Pêro Çalak o',
        'thumbnails_enabled' => 'Resimê Qickekî Çalak ê',
        'captions_enabled' => 'Sernameyî Çalak ê',
        'navigation_enabled' => 'Navîgasyon Çalak o',
        'keyboard_navigation' => 'Navîgasyonê Klavyeye',
        'touch_gestures' => 'Hereketê Têdestkerdişî',
        'lazy_loading' => 'Barkerdişê Hêdî (Lazy)',
        'infinite_scroll' => 'Rêzbîyayîşê Bêpeynî',
    ],

    // === Display Settings ===
    'display_settings' => [
        'gallery_width' => 'Hîrayîya Galeriye',
        'gallery_height' => 'Berzîya Galeriye',
        'image_spacing' => 'Mabênê Resiman',
        'border_radius' => 'Çewtîya Çarçoweyî',
        'shadow_effect' => 'Sîyaya Tesîrî',
        'hover_effect' => 'Tesîrê Serkewtişî',
        'transition_effect' => 'Tesîrê Vurîyayîşî',
        'animation_speed' => 'Lezê Anîmasyonî',
        'background_color' => 'Rengê Eslî',
        'border_color' => 'Rengê Çarçoweyî',
    ],

    // === Success Messages ===
    'success_messages' => [
        'operation_completed' => 'Kar bi serkewtî qedîya!',
        'changes_saved' => 'Vurnayîşî bi serkewtî qeyd bîyî!',
        'images_uploaded' => ':count resimî bi serkewtî ameyî barkerdene!',
        'gallery_configured' => 'Galeriye bi serkewtî ameye eyarkerdene!',
        'test_passed' => 'Testê galeriye serkewtî derbas bî!',
        'preview_ready' => 'Pêşdîyayîşê galeriye hazir o!',
    ],

    // === Error Messages ===
    'error_messages' => [
        'general' => 'Xetayêk bîye. Kerem kerê reyna biceribnê.',
        'network' => 'Xetaya torî. Kerem kerê girêdayîşê înternetî kontrol bikerê.',
        'server' => 'Xetaya pêşkêşkerî. Kerem kerê dima reyna biceribnê.',
        'upload_failed' => 'Barkerdişê resimî sernêkewt',
        'configuration_error' => 'Xetaya mîhengan pêda bîye',
        'test_failed' => 'Testê galeriye sernêkewt',
        'module_not_found' => 'Modulê Galeriye nêame dîyene',
        'invalid_configuration' => 'Mîhengê nerewayî',
    ],

    // === Helper Text ===
    'help' => [
        'module_intro' => 'Modulê galeriye parçeyanê pergalî yê bînan rê îmkanê galeriya resiman danê',
        'configuration_help' => 'Seba pêkardişê hewcehîya galeriya resiman mîhengê modulî eyar bikerê',
        'upload_help' => 'Seba viraştişê galeriya xo resiman bar bikerê',
        'display_help' => 'Galeriya xo senî asena xwerû bikerê',
        'feature_help' => 'Seba weşkerdişê ceribnawişê bikarhêneran taybetmendiyanê pêkewteyan çalak bikerê',
    ],

    // === Empty States ===
    'empty_states' => [
        'no_modules' => 'Heta nika tu Modulê Galeriye nêame eyarkerdene',
        'no_results' => 'Goreyê lêgerîna şima tu modul nêame dîyene',
        'no_images' => 'Na galeriye de heta nika tu resim çini yo',
        'add_first_module' => 'Seba destpêkî yewin Modulê Galeriye yê xo dekerê',
        'upload_first_image' => 'Seba viraştişê galeriye yewin resimê xo bar bikerê',
    ],

    // === Loading States ===
    'loading_states' => [
        'loading_modules' => 'Modulî yenê barkerdene...',
        'uploading_images' => 'Resimî yenê barkerdene...',
        'processing_images' => 'Resimî yenê xebitnayene...',
        'configuring' => 'Yeno eyarkerdene...',
        'previewing' => 'Pêşdîyayîş yeno viraştene...',
        'testing' => 'Galeriye yena testkerdene...',
    ],

];
