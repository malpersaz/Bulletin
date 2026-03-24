<?php
/**
 * Video Category Management Module - Kurdish Language File
 * Amed Framework CMS - Multilingual Content Management System
 *
 * This file contains all Kurdish translations for the video category management module.
 * Professional translations with modern CMS terminology and Kurdish grammar compliance.
 */

$lang['mod_video_embed_category'] = [

    // === Navigation & Actions ===
    'add' => 'Kategorîya Vîdeoyê Zêde Bike',
    'edit' => 'Kategorîya Vîdeoyê Biguhere',
    'list' => 'Lîsteya Kategorîya Vîdeoyê',

    // === System Messages ===
    'information' => 'Agahdarî!',
    'ok' => 'Operasyon Serkeft!',
    'hata' => 'Çewtî Çêbû!',
    'error' => 'Çewtiya Giran Çêbû!',

    // === Confirmation Messages ===
    'remove_image' => 'Ji derxistina vê wêneyê bawer î?',
    'remove_image_ok' => 'Wêne hate rakirin',
    'choose_wish' => 'Heke bixwazî hilbijêre',
    'remove_record' => 'Ji rakirana vê tomarê bawer î?',

    // === Success/Error Messages ===
    'message' => [
        'ok' => [
            'add' => 'Kategorîya Vîdeoyê Hate Zêdekirin!',
            'edit' => 'Kategorîya Vîdeoyê Hate Sererastkirin!',
        ],
        'error' => [
            'add' => 'Kategorîya Vîdeoyê Ne Hate Zêdekirin!',
            'edit' => 'Kategorîya Vîdeoyê Ne Hate Sererastkirin!',
        ],
        'information' => [
            'moderate' => 'Ji bo sererastkirina Kategorîya Vîdeoyê ku dixwazî du car bitikîne',
            'move' => 'Kategorîya Vîdeoyê ku dixwazî rêzeya wê biguherînî û bihêlin',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'Tomara ku texwîs kirî nehate dîtin!',
        'has_videos' => 'Nikarî kategorîya ku vîdeoyên tê de rakî!',
        'has_subcategories' => 'Nikarî kategorîya ku jêkategoriyên tê de rakî!',
        'circular_reference' => 'Referansa çargoşeyî di hiyerarşîya kategoriyê de hate dîtin',
        'depth_exceeded' => 'Kûrahiya herî zêde ya kategoriyê derbas bû',
        'invalid_parent' => 'Kategorîya dê yên derbasdar nehat hilbijartin',
    ],

    // === Buttons ===
    'button' => [
        'add' => 'Zêde Bike',
        'edit' => 'Biguhere',
        'delete' => 'Rake',
        'vazgec' => 'Betal',
        'kaldir' => 'Rake',
        'evet' => 'Erê',
        'hayir' => 'Na',
        'save' => 'Tomar Bike',
        'cancel' => 'Betal',
        'update' => 'Rojane Bike',
        'remove' => 'Rake',
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Ji kerema xwe qada %s vala nebinin',
        'length' => '%s dikare herî kurt %s herî dirêj %s tîpan be',
        'email' => 'Di qada %s de ji kerema xwe navnîşana e-postayê derbasdar binivîsin.',
        'unique' => '%s ku nivîsandîn di pergalê de tomar kirin. Ji kerema xwe %s din ake!',
        'inarray' => 'Qada %s tenê nirxên \' %s \' qebûl dike',
        'category_name_required' => 'Navê kategoriyê pêwîst e',
        'slug_required' => 'URL slug pêwîst e',
        'parent_category_invalid' => 'Kategorîya dê ya nerast derbasdar e',
    ],

    // === Access Control (ACL) ===
    'roll' => [
        'ad' => 'Rolenên Rêveberiya Kategorîya Vîdeoyê',
        'add' => 'Dikare Kategorîya Vîdeoyê zêde bike',
        'edit' => 'Dikare Kategorîya Vîdeoyê Biguhere',
        'delete' => 'Dikare Kategorîya Vîdeoyê rake',
        'list' => 'Dikare rêveberiya Kategorîya Vîdeoyê bibîne',
        'moderate_edit' => 'Dikare tomarên Kategorîya Vîdeoyê yên ku ne yên wî ne Biguhere',
        'moderate_delete' => 'Dikare tomarên Kategorîya Vîdeoyê yên ku ne yên wî ne rake',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'moderate_edit' => 'Tu nikarî tomarên Kategorîya Vîdeoyê yên ku ne yên te ne sererast bikî',
        'moderate_delete' => 'Tu nikarî tomarên Kategorîya Vîdeoyê yên ku ne yên te ne rakî',
        'add' => 'Te destûra zêdekirina Kategorîya Vîdeoyê nîne',
        'edit' => 'Te destûra sererastkirina Kategorîya Vîdeoyê nîne',
        'list' => 'Te destûra dîtina rêveberiya Kategorîya Vîdeoyê nîne',
        'delete' => 'Te destûra rakirina Kategorîya Vîdeoyê nîne',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Zimanê Xuyakirina Kategorîya Vîdeoyê',
            'name' => 'Navê Kategorîya Vîdeoyê',
            'parent_id' => 'Jê-Kategorîya Vîdeoyê',
            'order' => 'Rêz',
            'root' => 'Kategorîya Sereke ya ku divê neyê xuyakirin?',
            'seo' => 'Girêdana Kategorîya Vîdeoyê',
            'title' => 'Sernav ( ji bo Motora Lêgerînê )',
            'keywords' => 'Keywords ( ji bo Motora Lêgerînê )',
            'description' => 'Ravekirin ( ji bo Motora Lêgerînê )',
            'menu' => 'Kategoriyê Bişîne Menûyê',
            'image' => 'Wêne',
        ],
        'select_value' => [
            'root_e' => 'Erê',
            'root_h' => 'Na',
        ],
    ],

    // === Status Types ===
    'status' => [
        'published' => 'Hate Weşandin',
        'draft' => 'Bername',
        'archived' => 'Arşîvkirî',
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Erê',
        'no' => 'Na',
    ],

    // === Category Hierarchy ===
    'hierarchy' => [
        'parent_child' => 'Peywendîên Dê-Zarok',
        'category_tree' => 'Dara Kategoriyê',
        'nested_categories' => 'Kategoriyên Cih',
        'category_level' => ' asta Kategoriyê',
        'root_categories' => 'Kategoriyên Sereke',
        'sub_categories' => 'Jê-Kategorî',
        'category_depth' => 'Kûrahiya Kategoriyê',
        'max_depth' => 'Kûrahiya Herî Zêde ya Kategoriyê',
        'breadcrumb' => 'Navigasyona Breadcrumb',
    ],

    // === Video Count and Statistics ===
    'statistics' => [
        'video_count' => 'Hejmara Vîdeoyan',
        'total_videos' => 'Giştî Vîdeo',
        'published_videos' => 'Vîdeoyên Weşandî',
        'draft_videos' => 'Vîdeoyên Bername',
        'total_views' => 'Giştî Dîtinan',
        'total_duration' => 'Demên Giştî',
        'recent_videos' => 'Vîdeoyên Nûjen',
        'popular_videos' => 'Vîdeoyên Populer',
        'featured_videos' => 'Vîdeoyên Taybet',
    ],

    // === Category Display Options ===
    'display_options' => [
        'list_view' => 'Dîtina Lîsteyê',
        'grid_view' => 'Dîtina Grid',
        'tree_view' => 'Dîtina Darê',
        'show_video_count' => 'Hejmara Vîdeoyan Nîşan Bide',
        'show_empty_categories' => 'Kategoriyên Vala Nîşan Bide',
        'show_thumbnail' => 'Wêneya Thumbnail a Kategoriyê Nîşan Bide',
        'show_description' => 'Ravekirina Kategoriyê Nîşan Bide',
        'sort_alphabetically' => 'Bi Alfabe Rêz Bike',
        'sort_by_order' => 'Li Gorî Rêzê Rêz Bike',
        'sort_by_date' => 'Li Gorî Dîrokê Rêz Bike',
        'sort_by_video_count' => 'Li Gorî Hejmara Vîdeoyan Rêz Bike',
    ],

    // === Category Features ===
    'features' => [
        'enable_comments' => 'Şîroveyan Çalak Bike',
        'enable_ratings' => 'Rêjeyan Çalak Bike',
        'enable_sharing' => 'Parvekirinê Çalak Bike',
        'show_related_videos' => 'Vîdeoyên Têkildar Nîşan Bide',
        'auto_play' => 'Lêdana Otomatîk a Vîdeoyan',
        'show_video_thumbnails' => 'Wêneyên Thumbnail yên Vîdeoyan Nîşan Bide',
        'enable_video_upload' => 'Barkirina Vîdeoyan Çalak Bike',
        'require_approval' => 'Erêkirina Vîdeoyan Bixwaze',
        'enable_subcategories' => 'Jê-Kategorî Çalak Bike',
    ],

    // === Video Management ===
    'video_management' => [
        'add_videos' => 'Vîdeo Zêde Bike',
        'manage_videos' => 'Vîdeoyan Rêvebiri Bike',
        'bulk_operations' => 'Operasyonên Giştî',
        'move_videos' => 'Vîdeoyan Biguheşîne',
        'copy_videos' => 'Vîdeoyan Jê Berbike',
        'publish_videos' => 'Vîdeoyan Weşîne',
        'unpublish_videos' => 'Vîdeoyan Ji Weşandinê Bibe',
        'feature_videos' => 'Vîdeoyan Taybet Bike',
        'archive_videos' => 'Vîdeoyan Arşîv Bike',
    ],

    // === SEO and Meta Data ===
    'seo' => [
        'friendly_urls' => 'URLên Dostane',
        'auto_generate_slug' => 'URL Slugê Otomatîk Çêke',
        'meta_description' => 'Meta Ravekirin',
        'meta_keywords' => 'Meta Keywords',
        'og_tags' => 'Tagsên Open Graph',
        'twitter_cards' => 'Kartên Twitter',
        'structured_data' => 'Daneyên Strukturkirî',
        'schema_markup' => 'Schema Markup',
        'canonical_url' => 'URLya Canonical',
    ],

    // === Category Operations ===
    'operations' => [
        'create_category' => 'Kategorî Çêke',
        'edit_category' => 'Kategoriyê Biguhere',
        'delete_category' => 'Kategoriyê Rake',
        'move_category' => 'Kategoriyê Biguheşîne',
        'merge_categories' => 'Kategorîyan Yek Bike',
        'duplicate_category' => 'Kategoriyê Dublore Bike',
        'export_category' => 'Kategoriyê Derxîne',
        'import_categories' => 'Kategorîyan Têxe',
        'reorder_categories' => 'Kategorîan Nû Rêz Bike',
        'rebuild_tree' => 'Dara Kategoriyê Nû Çêke',
    ],

    // === Bulk Operations ===
    'bulk_operations' => [
        'select_all' => 'Hemûyan Hilbijêre',
        'deselect_all' => 'Hemûan Rake Hilbijartin',
        'delete' => 'Hilbijartî Rake',
        'publish_selected' => 'Hilbijartî Weşîne',
        'unpublish_selected' => 'Hilbijartî Ji Weşandinê Bibe',
        'move_selected' => 'Hilbijartî Biguheşîne',
        'export_selected' => 'Hilbijartî Derxîne',
        'bulk_edit' => 'Sererastkirina Giştî',
        'merge_selected' => 'Hilbijartî Yek Bike',
    ],

    // === Search and Filter ===
    'search_filter' => [
        'search_categories' => 'Lêgerîna kategorî...',
        'filter_by_status' => 'Li Gorî Rêjeyê Parzûn Bike',
        'filter_by_language' => 'Li Gorî Zimanê Parzûn Bike',
        'filter_by_parent' => 'Li Gorî Dê Parzûn Bike',
        'filter_by_video_count' => 'Li Gorî Hejmara Vîdeoyan Parzûn Bike',
        'filter_by_date' => 'Li Gorî Dîrokê Parzûn Bike',
        'show_all' => 'Hemûyan Nîşan Bide',
        'active_only' => 'Tenê yên Çalak',
        'root_only' => 'Tenê yên Sereke',
        'with_videos' => 'Tenê yên Vîdeo Digehînin',
        'empty_categories' => 'Tenê Kategoriyên Vala',
    ],

    // === Import/Export ===
    'import_export' => [
        'export_categories' => 'Kategorî Derxîne',
        'import_categories' => 'Kategorî Têxe',
        'csv_format' => 'Formatê CSV',
        'json_format' => 'Formatê JSON',
        'xml_format' => 'Formatê XML',
        'export_selected' => 'Hilbijartî Derxîne',
        'export_all' => 'Hemûyan Derxîne',
        'import_file' => 'Pelê Têxîne',
        'preview_import' => 'Pêşdîtina Têxînê',
        'validate_data' => 'Daneyên Bawer Bike',
        'import_progress' => 'Pêşveçûna Têxînê',
    ],

    // === Success Messages ===
    'success_messages' => [
        'operation_completed' => 'Operasyon bi serkeftî qediya!',
        'changes_saved' => 'Guherînan bi serkeftî hatin tomarkirin!',
        'category_published' => 'Kategorî bi serkeftî hate weşandin!',
        'category_unpublished' => 'Kategorî ji weşandinê hate rakirin!',
        'videos_moved' => ':count vîdeo bi serkeftî hate guheztin!',
        'categories_merged' => 'Kategorî bi serkeftî hate yekkirin!',
        'tree_rebuilt' => 'Dara kategoriyê bi serkeftî hate nûkirin!',
    ],

    // === Error Messages ===
    'error_messages' => [
        'general' => 'Çewtiyek çêbû. Ji kerema xwe nûve biceribîne.',
        'network' => 'Çewtiya torê. Ji kerema xwe girêdana înternetê xwe kontrol bike.',
        'server' => 'Çewtiya serverê. Ji kerema xwe paşê nûve biceribîne.',
        'category_not_found' => 'Kategorî nehate dîtin',
        'invalid_parent' => 'Kategorîya dê ya nerast hate hilbijartin',
        'depth_exceeded' => 'Kûrahiya herî zêde ya kategoriyê derbas bû',
        'circular_reference' => 'Referansa çargoşeyî di hiyerarşîya kategoriyê de hate dîtin',
        'import_failed' => 'Têxîn serneket',
        'export_failed' => 'Derxistin serneket',
        'validation_failed' => 'Bawerkirina daneyan serneket',
    ],

    // === Helper Text ===
    'help' => [
        'category_name' => 'Ji bo vê kategorîya vîdeoyê navekî raveker binivîse',
        'parent_category' => 'Kategorîya dê hilbijêre ( ji bo asta sereke vala bihêle )',
        'category_order' => 'Rêza xuyakirinê diyar dike ( nirxên kûrttir pêş tên xuyakirin )',
        'root_category' => 'Kategoriyên sereke yên rêxistinî û ne giştî nayên xuyakirin',
        'seo_url' => 'Versiyona URL-ya dostane a navê kategoriyê ( di girêdanan de tê bikaranîn )',
        'meta_description' => 'Ravekirina ku di encamên lêgerîna motora lêgerînê de tê xuyakirin',
        'category_image' => 'Wêneyekê barkirin ku vê kategoriyê nîşan dide',
        'video_management' => 'Vîdeoyên di vê kategoriyê de rêvebiri bike',
        'hierarchy_help' => 'Jê-kategorî çêke ku vîdeoyan baştir rêxistin bike',
    ],

    // === Placeholders ===
    'placeholders' => [
        'category_name' => 'Navê kategoriyê binivîse...',
        'category_description' => 'Ravekirina kategoriyê binivîse...',
        'seo_url' => 'url-slug-a-kategoriya-vîdeoyê',
        'meta_title' => 'Meta sernavê binivîse...',
        'meta_description' => 'Meta ravekirinê binivîse...',
        'meta_keywords' => 'keyword1, keyword2, keyword3',
        'search_categories' => 'Lêgerîna kategorîya vîdeoyê...',
    ],

    // === Empty States ===
    'empty_states' => [
        'no_categories' => 'Heta niha kategorîya vîdeoyê nehatiye çêkirin',
        'no_results' => 'Kategorîyên ku li gorî krîterên lêgerîna we digihîjin nehate dîtin',
        'no_videos' => 'Di vê kategoriyê de heta niha vîdeo tune',
        'add_first_category' => 'Ji bo çêkirina yekemîn kategorîya vîdeoyê bişkoja bitikîne',
        'upload_first_video' => 'Ji bo destpêkirinê yekemîn vîdeoya xwe barkirin',
        'create_subcategory' => 'Jê-kategorî çêke ku vîdeoyên xwe rêxistin bikin',
    ],

    // === Loading States ===
    'loading_states' => [
        'loading_categories' => 'Kategorî tên barkirin...',
        'saving_changes' => 'Guherînan tên tomarkirin...',
        'deleting_category' => 'Kategorî tê rakirin...',
        'uploading_image' => 'Wêne tên barkirin...',
        'processing_request' => 'Daxwaz tên xebitandin...',
        'importing_data' => 'Daneyên tên têxîn...',
        'exporting_data' => 'Daneyên tên derxistin...',
    ],

    // === Category Tree View ===
    'tree_view' => [
        'expand_all' => 'Hemûyan Fireh Bike',
        'collapse_all' => 'Hemûan Girj Bike',
        'show_hierarchy' => 'Hîerarşî Nîşan Bide',
        'show_flat' => 'Dîtina Flat Nîşan Bide',
        'drag_drop_reorder' => 'Ji bo nûvekirina rêzê bikşîne û bihêle',
        'indent_children' => 'Zarokên Bike Qul',
        'show_counts' => 'Hejmara Vîdeoyan Nîşan Bide',
    ],

];