<?php
/**
 * Content Management Module - Kurdish Language File
 *
 * @package AKM Business
 * @module  mod_content
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Content Management Module Language Translations
 */
$lang['mod_content'] = [
    'total_content' => 'Hemû Naverok',
    'draft_content' => 'Pêşnûme',

    // === Basic Actions ===
    'add' => 'Naverokê Lêke',
    'edit' => 'Biguhere',
    'list' => 'Naverok',
    'search_placeholder' => 'Li naverokê bigere (sernav, kurte)...',
    'information' => 'Agahî!',
    'success' => 'Çêbû!',
    'error' => 'Çewtiya Giran Çêbû!',
    'remove_image' => 'Ma tu bawer î ku dixwazî vî wêneyî jêbibî?',
    'remove_image_success' => 'Wêne hate rakirin',
    'choose_wish' => 'Beşekê Hilbijêre',
    'remove_record' => 'Ma tu bawer î, ku dixwazî vê naverokê jêbibî?',
    'delete_confirm' => 'Ma tu bawer î ku dixwazî vê naverokê jêbibî?',
    'quick_unpublish_confirm' => 'Tu bawer î ku dixwazî vê naverokê ji weşanê rakî?',
    'quick_publish_confirm' => 'Ma tu bawer î, ku dixwazî vê naverokê biweşînî?',
    'add_image' => 'Wêne Lêke',
    'upload_change_image' => 'Wêne Lê Bar Bike/Biguherîne',
    'local' => 'Lokal',
    'embed' => 'Embed',

    // === Filter Menu ===
    'filter' => [
        'title' => 'Fîltre & Rêzkirin',
        'close' => 'Bigire',
        'sort_label' => 'Rêzkirin',
        'newest' => 'Yên Nû',
        'oldest' => 'Yên Kevn',
        'title_az' => 'Sernav (A-Z)',
        'title_za' => 'Sernav (Z-A)',
        'view_count' => 'Hejmara Jixwendinê',
        'limit_label' => 'Hejmara Nîşandayînê',
        'lang_label' => 'Zimanekî Hilbijêre',
        'selected_lang' => 'Zimanê Hilbijartî',
        'date_label' => 'Rêjeya Dîrokê',
        'start_date' => 'Destpêk',
        'end_date' => 'Dawî',
        'clear' => 'Paqij bike',
        'apply' => 'Encaman Bibîne',
        'select_lang' => 'Ziman Hilbijêre',
        'all_langs' => 'Hemû Ziman',
        'select_label' => 'Hilbijêre',
        'delete_error' => 'Jêbirin biserneket!',
        'video_success_sim' => 'Vîdeo bi Serkeftî Hat Lêkirin (Simulasyon)',
        'video_embed_error' => 'Koda Embed Şaş e',
        'video_title_error' => 'Sernav Şaş e',
    ],

    // === Messages ===
    'message' => [
        'success' => [
            'add' => 'Naverok Hat Lêkirin!',
            'edit' => 'Naverok Hat Nûjenkirin!',
        ],
        'error' => [
            'add' => 'Naverok Nehat Lêkirin!',
            'edit' => 'Naverok Nehat Nûjenkirin!',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'Tomara ku texîs kirî nehat dîtin!',
    ],

    'wizard_loading' => 'Sihêrbaz tê barkirin...',

    // === Buttons ===
    'button' => [
        'next' => 'Pêşve',
        'prev' => 'Vegere',
        'add' => 'Lêke',
        'edit' => 'Biguhere',
        'refresh' => 'Nûjen Bike',
        'delete' => 'Jêbibe',
        'gallery' => 'Galeriya Wêneyan',
        'file' => 'Rêveberê Pelan',
        'cancel' => 'Betal',
        'save' => 'Tomar bike',
        'remove' => 'Rake',
        'yes' => 'Erê',
        'no' => 'Na',
    ],

    // === Data Grid Messages ===
    'grid' => [
        'showing_results' => 'Ji {total} encamên {from} heta {to} tên nîşandan.',
        'page' => 'Rûpel',
        'page_count' => 'Hejmara Rûpelan',
        'loading' => 'Daneyên têne barkirin, jî kerema xwe bisekinin...',
        'no_data' => 'Tu dane nehat dîtin',
        'connection_error' => 'Çewtiya Girêdanê',
        'search' => 'Lêgerîn',
        'select_at_least_one' => 'Ji kerema xwe herî kêm rêzekê hilbijêrin',
        'select_only_one' => 'Ji kerema xwe tenê rêzekê hilbijêrin',
        'loading' => 'Tê barkirin...',
        'no_data' => 'Tu naverok nehat dîtin',
        'approve' => [
            'key' => 'Weşandin',
            'label' => 'Bipejirîne',
            'ask' => 'Ma tu bawer î ku dixwazî vê naverokê biweşînî?',
            'title' => 'Naverokê Biweşîne',
        ],
        'unapprove' => [
            'key' => 'Veşêre',
            'label' => 'Ji Weşanê Rake',
            'ask' => 'Ma tu bawer î ku dixwazî vê naverokê ji weşanê rakî?',
            'title' => 'Naverokê Veşêre',
        ],
        'load_more' => 'Pirtir Nîşan Bide',
    ],

    // === Form Validation ===
    'validator' => [
        'required' => 'Qada %s ne dikare vala be',
        'length' => 'Qada %s divê di navbera %s û %s karakteran de be',
        'email' => 'Ji kerema xwe re di qada %s de navnîşana e-postayê ya derbasdar binivîsin',
        'unique' => '%s ku nivîsandî di pergalê de hatî tomarkirin. Ji kerema xwe re %s-ek din specîfî bikin!',
        'inarray' => 'Qada %s tenê nirxên \' %s \' dipejirîne',
        'title_length' => 'Divê tu sernavekî herî kêm ji 1 tîpê û herî pir ji 70 tîpan pêk tê binivîsî.',
        'title_exists' => 'Ev sernav jixwe heye, ji kerema xwe biguherînin',
        'title_unique' => 'Sernavê we bêhempa ye',
        'deck_length' => 'Ji kerema xwe nivîsek destpêkê ya ku di navbera 90 û 120 tîpan de be binivîsin.',
    ],

    // === Permission Roles ===
    'role' => [
        'name' => 'Rolên Rêveberiya Naverokê',
        'add' => 'Dikare naverokan lêke',
        'edit' => 'Dikare naverokan biguhere',
        'delete' => 'Dikare naverokan jêbibe',
        'list' => 'Dikare rêveberiya naverokê bibîne',
        'publish' => 'Dikare naverokan weşîne',
        'unpublish' => 'Dikare naverokan ji weşanê rake',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'add' => 'Ji bo lêkirina naverokê destûra te tune',
        'edit' => 'Ji bo sererastkirina naverokê destûra te tune',
        'list' => 'Ji bo dîtina rêveberiya naverokê destûra te tune',
        'delete' => 'Ji bo jêbirina naverokê destûra te tune',
        'approval' => 'Ji bo pejirandina naverokê destûra te tune',
        'moderate_edit' => 'Tu nikarî tomarên naverokê yên kesên din sererast bikî',
        'moderate_delete' => 'Tu nikarî tomarên naverokê yên kesên din jêbibî',
        'gallery' => 'Ji bo rêveberiya galeriyê destûra te tune',
        'file' => 'Ji bo rêveberiya pelan destûra te tune',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Ziman',
            'name' => 'Sernav',
            'category_id' => 'Kategorî',
            'created_at' => 'Dîrok',
            'updated_at' => 'Dîroka Sererastkirinê',
            'content' => 'Naverok',
            'summary' => 'Destpêk',
            'seo' => 'SEO URL',
            'title' => 'Sernavê Rûpelê',
            'keywords' => 'Nirxên Mifteyê',
            'description' => 'Ravekirin',
            'status' => 'Rewşa Weşanê',
            'deck' => 'Destpêk',
            'image' => 'Wêne',
            'gallery_id' => 'Galerî',
            'file_id' => 'Pel',
            'acl_id' => 'Kontrola Têketinê',
            'video' => 'Galeriya Vîdeoyê',
        ],
        'radio' => [
            'publish_yes' => 'Hate Weşandin',
            'publish_no' => 'Nehat Weşandin',
            'deck_yes' => 'Destpêkê Veneşêre',
            'deck_no' => 'Destpêkê Veşêre',
        ],
    ],

    // === UI Elements ===
    'ui' => [
        'record_management' => 'Rêveberiya Tomaran',
        'operation' => 'Operasyon',
        'apply' => 'Bisepîne',
        'action_column' => 'Çalakî',
        'select' => 'Hilbijêre',
        'from' => 'Ji Vir',
        'to' => 'Bo Vir',
        'search' => 'Lêbigere',
        'reset' => 'Sifir Bike',
        'approval' => 'Bipejirîne',
        'processing' => 'Têkirîn',
        'record_selected' => 'Tomar hate hilbijartin',
        'select_operation' => 'Ji kerema xwe operasyonekê hilbijêrin',
        'all' => 'Hemû',
        'pdf' => 'PDF',
        'csv' => 'CSV',
        'excel' => 'Excel',
        'print' => 'Çap Bike',
        'print_info' => 'Agahiya Çapê',
        'creator' => 'Afirîner',
        'copy' => 'Ji Ber Bigire',
        'default_sort' => 'Yên Nû',
        'filter_cleared' => 'Parzûn hatin paqijkirin û vegeriyan mîhengên xwerû.',
        'filter_applied' => 'Parzûn hatin sepandin',
        'sort_label' => 'Rêzkirin',
        'limit_label' => 'Hejmar',
        'lang_label' => 'Ziman',
    ],

    // === DataTable Configuration ===
    'datatable' => [
        'aria' => [
            'sortAscending' => ': ji bo rêzkirina zêdeve çalak bike',
            'sortDescending' => ': ji bo rêzkirina kêmde çalak bike',
        ],
        'emptyTable' => 'Di tabloyê de tu dane tune',
        'info' => '_START_ heta _END_ ji _TOTAL_ tên nîşandan',
        'infoEmpty' => '0 heta 0 ji 0 têne nîşandan',
        'infoFiltered' => '(ji _MAX_ tomarên giştî hat parvekirin)',
        'lengthMenu' => '_MENU_ tomar nîşan bide',
        'search' => 'Lêbigere:',
        'zeroRecords' => 'Tu tomarên ku li hev bigirin nehatin dîtin',
        'loadingRecords' => 'Tê daxistin...',
        'processing' => 'Têkirîn...',
        'paginate' => [
            'first' => 'Yekem',
            'last' => 'Dawî',
            'next' => 'Paş',
            'previous' => 'Berê',
        ],
    ],

    // === Tokenizing Configuration ===
    'tokenizing' => [
        'hintText' => 'Termekê lêgerînê binivîse',
        'noResultsText' => 'Encam tune',
        'searchingText' => 'Tê lêgerîn...',
    ],

    // === AnyTime Picker ===
    'anytime' => [
        'labelDayOfMonth' => 'Rojên Mehê',
        'labelHour' => 'Saet',
        'labelMinute' => 'Xulek',
        'labelMonth' => 'Meh',
        'labelTitle' => 'Dîrok û Demê Hilbijêre',
        'labelYear' => 'Sal',
        'dayAbbreviations' => ['Yş', 'Dş', 'Sş', 'Çş', 'Pş', 'În', 'Şm'],
        'dayNames' => ['Yekşem', 'Duşem', 'Sêşem', 'Çarşem', 'Pêncşem', 'În', 'Şemî'],
        'monthAbbreviations' => ['Rêb', 'Reş', 'Ada', 'Avr', 'Gul', 'Pûş', 'Tîr', 'Gel', 'Rez', 'Kew', 'Ser', 'Ber'],
        'monthNames' => ['Rêbendan', 'Reşemî', 'Adar', 'Avrêl', 'Gulan', 'Pûşper', 'Tîrmeh', 'Gelawêj', 'Rezber', 'Kewçêr', 'Sermawez', 'Berfanbar'],
    ],

    // === Status Badges ===
    'status_badge' => [
        'active' => 'Weşandî',
        'draft' => 'Pêşnûma',
        'general' => 'Giştî',
    ],

    // === Sheet Labels ===
    'sheet' => [
        'lang_title' => 'Zimanekî Hilbijêre',
        'ok' => 'Temam',
        'search_placeholder' => 'Zimanekî bigere (mînak: Îngilîzî, JP)...',
        'category_title' => 'Kategorî Hilbijêre',
        'all' => 'Hemû (Bê Fîltre)',
    ],

    // === Wizard ===
    'wizard' => [
        'step' => [
            'language' => 'Zimanekî Hilbijêre',
            'category' => 'Kategorî',
            'title' => 'Sernav',
            'date' => 'Dîroka Weşanê',
            'intro' => 'Destpêka naverokê',
            'content' => 'Firehiya naverokê',
            'image' => 'Lêbarkirina Wêne',
            'finish' => 'Dawî',
        ],
        'subtitle' => [
            'language' => 'Zimanê naverokê hilbijêre.',
            'category' => 'Beşa naverokê lê were weşandin hilbijêre.',
            'title' => 'Sernavekî balkêş binivîse. (Herî kin: 1, herî pir: 70 tîp)',
            'date' => 'Dîrok û dema nivîsê dikare li gor pêşerojê be jî.',
            'intro' => 'Kin û zelal binivîse. (Herî kêm: 110 - herî zêde: 120 tîp)',
            'content' => 'Edîtorê nivîsa dewlemend.',
            'image' => 'Tu dikarî wêneyekî tenê hilbijêrî.',
            'finish' => 'Saxtîkirina dawî û şandin.',
        ],
        'label' => [
            'content_title' => 'Sernavê naverokê',
            'intro_text' => 'Destpêka naverokê',
            'upload_text' => 'Ji bo barkirina wêne vir bitikîne',
            'published' => 'Tê Şandin!',
            'published_desc' => 'Dema gerînende naverokê biweşîne dê ji aliyê her kesî ve were dîtin.',
            'category_select' => 'Beşa Naverokê...',
            'change_image' => 'Wêne biguherîne',
            'editor_placeholder' => 'Dest bi nivîsandina naveroka xwe bikin...',
        ],
        'image' => [
            'drop_title' => 'Wêneyekî kaşke ser vir an jî',
            'drop_description' => 'Bar bike, pêve bike an jî wêneyekî berde vir.',
            'upload_link' => 'lê bar bike',
            'link_separator' => 'AN JÎ',
            'link_placeholder' => 'Lînka wêne binivîse',
            'link_action' => 'Bîne',
            'hint_loading' => 'Wêne tê anîn...',
            'hint_invalid_type' => 'Tenê pelên wêneyan li vir têne piştgirî kirin.',
            'hint_no_drop_data' => 'Di naveroka hatî berdan de tu wêneyekî bikêr nehat dîtin.',
            'hint_remote_failed' => 'Wêne ji wî çavkanî nehat anîn.',
        ],
        'messages' => [
            'success_sim' => 'Naverok bi serketî hat afirandin! (Simulasyon)',
            'video_success_sim' => 'Vîdeo bi serketî hat lêkirin! (Simulasyon)',
            'video_embed_error' => 'Ji kerema xwe kodek embedê ya derbasdar binivîsin (iframe).',
            'video_title_error' => 'Divê sernavê vîdeoyê kêmzêde 3 tîp be.',
            'card_click' => 'Qert hat tikandin: %s',
            'no_category_for_lang' => 'Di vî zimanî de kategorî nehatine dîtin. Ji kerema xwe berê kategoriyan lêkin.',
        ],
        'summary' => [
            'title' => 'Kurtasî',
            'selected_none' => 'Nehatiye Hilbijartin',
        ],
        'button' => [
            'save' => 'Tomar bike',
            'next' => 'Pêşve',
            'prev' => 'Vegere',
            'finish' => 'Bişîne',
        ]
    ],

    // === Context Menu ===
    'context_menu' => [
        'title' => 'Operasyonên Naverokê',
        'edit' => 'Biguhere',
        'gallery' => 'Operasyonên Galeriyê',
        'files' => 'Operasyonên Pelan',
        'video' => 'Vîdeo li Naverokê Lêke',
        'status' => 'Rewşê Biguherîne',
        'delete' => 'Rake',
        'publish' => 'Biweşîne',
        'unpublish' => 'Neweşîne',
        'published_msg' => 'Hat Weşandin',
        'unpublished_msg' => 'Ji Weşanê Hat Rakirin',
        'confirm' => 'Erê',
        'cancel' => 'Betal',
        'edit_alert' => "MODA SERERASTKIRINÊ:\nID: %s\nSernav: %s",
        'gallery_alert' => "RÊVEBERIYA GALERIYÊ:\nID: %s\nSernav: %s\nGaleriya wêneyan tê vekirin.",
        'files_alert' => "RÊVEBERIYA PELAN:\nID: %s\nSernav: %s\nDîmendera barkirina pelan tê vekirin.",
        'status_alert' => "REWŞ HAT NÛJENKIRIN:\n%s",
        'delete_success' => 'Naverok hat jêbirin!',
    ],

    'hero_slider_add' => 'Bişîne Sliderê',
    'hero_slider_remove' => 'Ji Sliderê Rake',
    'hero_slider_added' => 'Naverok bi serkeftî bo Slider hate şandin.',
    'hero_slider_removed' => 'Naverok ji Slider hate rakirin.',
    'hero_slider_error' => 'Rewşa Slider nehat nûjenkirin!',
    'hero_slider_badge' => 'Slider',
    'hero_slider_unavailable' => 'Sitûna sliderê hîna nehatiye sazkirin.',

    'picker' => [
        'file' => 'Pelê Heyî Hilbijêre',
    ],

    'empty_state' => [
        'title' => 'Hêj ti naverok nehatiye lêkirin',
        'description' => 'Ji bo destpêkirinê, naveroka xwe ya yekem lêkin.',
        'search_title' => 'Tu encam nehatin dîtin',
        'search_description' => 'Ji bo pîvanên lêgerîna we ti naverok nehat dîtin. Ji kerema xwe peyveke din biceribînin.',
        'clear_search' => 'Lêgerînê Paqij Bike',
    ],

    'ajax' => [
        'database_error' => 'Çewtiya Database',
        'record_not_found' => 'Naverok nehat dîtin!',
        'unknown_type' => 'Cureyê peywendiya nenas',
        'relation_updated' => 'Peywendî bi serkeftî hate nûjenkirin',
    ],
];
