<?php
/**
 * Publication Management Module - Kurdish Language File
 *
 * @package AKM Bulletin CMS
 * @module  mod_publication
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Publication Management Module Language Translations
 */
$lang['mod_publication'] = [

    // === Basic Actions ===
    'add' => 'Lê Zêde Bike',
    'edit' => 'Biguhere',
    'list' => 'Lîsteya Weşanan',
    'information' => 'Agahî!',
    'success' => 'Çêbû!',
    'error' => 'Çewtî Çêbû!',
    'error_occurred' => 'Çewtî Çêbû!',
    'wizard_loading' => 'Sêrbaz tê barkirin...',
    'fatal_error' => 'Çewtiya Giran Çêbû!',
    'remove_image' => 'Ma tu bawer î ku dixwazî vî wêneyî jêbibî?',
    'remove_image_success' => 'Wêne hate rakirin',
    'choose_wish' => 'Beşekê Hilbijêre',
    'remove_record' => 'Ma tu bawer î ku dixwazî vî weşanî jê bibî?',
    'delete_confirm' => 'Ma tu bawer î ku dixwazî vî weşanî jê bibî?',
    'quick_unpublish_confirm' => 'Tu bawer î ku dixwazî vî weşanî ji weşanê rakî?',
    'quick_publish_confirm' => 'Ma tu bawer î ku dixwazî vî weşanî biweşînî?',
    'add_image' => 'Wêne lê zêde bike',
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
        'video_success_sim' => 'Vîdeo bi Serkeftî Hat Zêdekirin (Simulasyon)',
        'video_embed_error' => 'Koda Embed Şaş e',
        'video_title_error' => 'Sernav Şaş e',
    ],

    // === Messages ===
    'message' => [
        'success' => [
            'add' => 'Weşan Hat Zêdekirin!',
            'edit' => 'Weşan Hat Sererastkirin!',
        ],
        'error' => [
            'add' => 'Weşan Nehat Zêdekirin!',
            'edit' => 'Weşan Nehat Sererastkirin!',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'Tomara ku texîs kirî nehat dîtin!',
    ],

    // === Buttons ===
    'button' => [
        'next' => 'Pêşve',
        'prev' => 'Vegere',
        'send' => 'Bişîne',
        'add' => 'Zêde Bike',
        'edit' => 'Biguhere',
        'refresh' => 'Nû Bike',
        'delete' => 'Jêbibe',
        'gallery' => 'Galeriya Wêneyan',
        'file' => 'Rêveberê Pelan',
        'cancel' => 'Betal',
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
        'approve' => [
            'key' => 'Weşandin',
            'value' => 'Ma tu bawer î ku dixwazî vî weşanî biweşînî?',
        ],
        'unapprove' => [
            'key' => 'Ji Weşandinê Bikişîne',
            'value' => 'Ma tu bawer î ku dixwazî vî weşanî ji weşanê rakî?',
        ],
        'load_more' => 'Pirtir Nîşan Bide',
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Ji kerema xwe qada %s vala nehêlin!',
        'length' => '%s dikare herî kurt %s û herî dirêj %s tîpan be',
        'email' => 'Ji kerema xwe navekî e-postayê yê derbasdar li qada %s binivîsin.',
        'unique' => '%s ku te nivîsî di pergalê de qeydkirî ye. Ji kerema xwe %s-ekê din destnîşan bikin!',
        'inarray' => 'Qada %s dikare tenê nirxên \' %s \' bigire',
        'title_length' => 'Divê tu sernavekî herî kêm ji 1 tîpê û herî pir ji 70 tîpan pêk tê binivîsî.',
        'title_exists' => 'Ev sernav jixwe heye, ji kerema xwe biguherînin',
        'title_unique' => 'Sernavê we bêhempa ye',
        'deck_length' => 'تکایە کورتەیەک لە نێوان ۹۰ تا ۱۲۰ پیت بنووسە.',
    ],

    // === Permission Roles ===
    // === Permission Roles ===
    'role' => [
        'name' => 'Erkên Rêveberiya Weşanan',
        'add' => 'Dikare weşanekê lê zêde bike',
        'edit' => 'Dikare weşana xwe biguhere',
        'delete' => 'Dikare weşana xwe jêbibe',
        'list' => 'Dikare lîsteya weşanan bibîne',
        'publish' => 'Dikare weşana xwe biweşîne',
        'unpublish' => 'Dikare weşana xwe ji weşanê rake',
        'change_status' => 'Dikare rewşa weşanê biguherîne',
        'edit_status' => 'Dikare rewşa weşanê ya berfireh biguherîne',
        'moderate_edit' => 'Dikare weşanên kesên din biguhere',
        'moderate_delete' => 'Dikare weşanên kesên din jêbibe',
        'gallery' => 'Dikare rêveberiya Wênedankê bibîne',
        'file' => 'Dikare rêveberiya Pelan bibîne',
        'approval' => 'Dikare weşanê bipejirîne',
        'video_download' => 'Dikare vîdeoyan daxîne',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'add' => 'Ji bo zêdekirina weşaneke nû destûra te tune',
        'edit' => 'Ji bo sererastkirina weşana xwe destûra te tune',
        'list' => 'Ji bo dîtina lîsteya weşanan destûra te tune',
        'delete' => 'Ji bo jêbirina weşana xwe destûra te tune',
        'approval' => 'Ji bo pejirandina weşanê destûra te tune',
        'moderate_edit' => 'Tu nikarî tomarên weşanê yên ku yên te ne sererast bikî',
        'moderate_delete' => 'Tu nikarî tomarên weşanê yên ku yên te ne rakî',
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
            'publication' => 'Weşan',
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
            'menu' => 'Têxe Menuyê',
            'video' => 'Galeriya Vîdeoyê',
            'video_help' => 'Navê vîdeoya ku dixwazî di weşanê de xuya bibe lê bigere',
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
        'operation_failed' => 'Operasyon nehat pêkanîn',
        'select_operation' => 'Ji kerema xwe operasyonekê hilbijêrin',
        'all' => 'Hemû',
        'pdf' => 'PDF',
        'csv' => 'CSV',
        'excel' => 'Excel',
        'print' => 'Çap Bike',
        'print_info' => 'Agahiya Çapê',
        'created_by' => 'Afirîner',
        'copy' => 'Ji Ber Bigire',
        'default_sort' => 'Yên Nû',
        'default_lang' => 'KU',
        'date_locale' => 'ku-TR',
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
        'labelSecond' => 'Çirke',
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
            'lang' => 'Zimanekî Hilbijêre',
            'language' => 'Zimanekî Hilbijêre',
            'category' => 'Kategorî',
            'title' => 'Sernav',
            'date' => 'Dîroka Weşanê',
            'intro' => 'Destpêka weşanê',
            'publication' => 'Naveroka weşanê',
            'image' => 'Lêbarkirina Wêne',
            'finish' => 'Dawî',
        ],
        'subtitle' => [
            'language' => 'Zimanê weşanê hilbijêre.',
            'category' => 'Beşa ku ev weşan tê de were weşandin hilbijêre.',
            'title' => 'Sernavekî balkêş binivîse. (Herî kin: 1, herî pir: 70 tîp)',
            'date' => 'Dîrok û dema nivîsê dikare li gor pêşerojê be jî.',
            'intro' => 'Kin û zelal binivîse. (90 - 120 tîp)',
            'publication' => 'Edîtorê nivîsa dewlemend.',
            'image' => 'Tu dikarî wêneyekî tenê hilbijêrî.',
            'finish' => 'Saxtîkirina dawî û şandin.',
        ],
        'label' => [
            'publication_title' => 'Sernavê weşanê',
            'intro_text' => 'Destpêka weşanê',
            'upload_text' => 'Ji bo barkirina wêne vir bitikîne',
            'published' => 'Tê Şandin!',
            'published_desc' => 'Dema ku weşan were zêdekirin, wê ji aliyê her kesî ve were dîtin.',
            'category_select' => 'Beşa Weşanê...',
            'change_image' => 'Wêne biguherîne',
            'editor_placeholder' => 'Dest bi nivîsandina weşana xwe bikin...',
        ],
        'messages' => [
            'success_sim' => 'Weşan bi serketî hat afirandin! (Simulasyon)',
            'video_success_sim' => 'Vîdeo bi serketî hat zêdekirin! (Simulasyon)',
            'video_embed_error' => 'Ji kerema xwe kodek embedê ya derbasdar binivîsin (iframe).',
            'video_title_error' => 'Divê sernavê vîdeoyê kêmzêde 3 tîp be.',
            'card_click' => 'Qert hat tikandin: %s',
            'no_category_for_lang' => 'Di vî zimanî de kategorî nehatine dîtin. Ji kerema xwe berê kategoriyan zêde bikin.',
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
        'title' => 'Operasyonên Weşanan',
        'edit' => 'Biguhere',
        'gallery' => 'Wênedanka Weşanê Çêke',
        'files' => 'Pelan li Weşanê Bike',
        'video' => 'Vîdewo Embed li Weşanê Bike',
        'status' => 'Rewşê Biguherîne',
        'delete' => 'Jêbibe',
        'delete_error' => 'Jêbirin bi ser neket!',
        'video_success_sim' => 'Vîdeo bi serketî hat zêdekirin! (Simulasyon)',
        'video_embed_error' => 'Koda embedê nederbasdar e',
        'video_title_error' => 'Sernav nederbasdar e',
        'publish' => 'Biweşîne',
        'unpublish' => 'Neweşîne',
        'published_msg' => 'Hat Weşandin',
        'unpublished_msg' => 'Ji Weşanê Hat Rakirin',
        'confirm' => 'Erê',
        'cancel' => 'Betal',
        'edit_alert' => "MODA SERERASTKIRINÊ:\nID: %s\nSernav: %s",
        'gallery_alert' => "RÊVEBERIYA GALERIYÊ:\nID: %s\nSernav: %s\nGaleriya wêneyan tê vekirin.",
        'files_alert' => "RÊVEBERIYA PELAN:\nID: %s\nSernav: %s\nDîmendera barkirina pelan tê vekirin.",
        'status_alert' => "REWŞ HAT NÛKIRIN:\n%s",
        'delete_success' => 'Weşan hat jêbirin!',
        'video_unavailable' => 'Rêveberiya vîdeoyan ji bo weşanan hîn ne amade ye.',
    ],

    'mock' => [
        'titles' => [
            "Serdema Nû di Çapemeniya Kurdî de Dest pê dike",
            "Bernameya Festîvala Çand û Hunerê ya Amedê hat eşkerekirin",
            "Komxebata Rojnamegeriya Herêmî bi dawî bû",
            "Nîşeyên li ser Têkiliya Aqilê Çêkirî û Medyayê",
            "Derbasbûna ji Rojnamegeriya Kevneşopî bo Medyaya Dijîtal",
            "Amarên Bikaranîna Medyaya Civakî 2024",
            "Kurteya Teknolojiya Heftane: Çi Qewimî?",
            "Yekîtiya Nivîskaran Rêveberiya Nû Hilbijart",
            "Qeydên Atolyeya Wênekêşiyê Dest pê kirin",
            "Serlêdana Mobîl a AKM Bulletin CMS hat nûjenkirin"
        ],
        'categories' => ["Rojev", "Çand & Huner", "Teknolojî", "Medya", "Jiyan"],
        'authors' => [],
    ],

    // === AJAX & Contact Messages ===
    'ajax' => [
        'success' => 'Serkeftî',
        'error' => 'Çewtî Çêbû',
        'database_error' => 'Çewtiya Danegehê',
        'record_not_found' => 'Tomarî nehat dîtin!',
        'unknown_type' => 'Cureyê têkiliya nenas',
        'file_added' => 'Pel hat lêzêdekirin',
        'file_removed' => 'Pel hat jêqetandin',
        'gallery_added' => 'Galerî hat lêzêdekirin',
        'gallery_removed' => 'Galerî hat jêqetandin',
        'relation_updated' => 'Têkilî hat nûkirin',
    ],

    // === Hero Slider ===
    'hero_slider_add' => 'Bişîne Slaydirê',
    'hero_slider_remove' => 'Ji Slaydirê Rake',
    'hero_slider_added' => 'Weşan bi serkeftî li Slaydirê hate zêdekirin.',
    'hero_slider_removed' => 'Weşan ji Slaydirê hat rakirin.',
    'hero_slider_error' => 'Rewşa Slaydirê nehat nûkirin!',
    'hero_slider_badge' => 'Slaydir',

    // === Media Picker UI ===
    'picker' => [
        'gallery' => 'Wênedankeke Heyî Hilbijêre',
        'file' => 'Pelelke Heyî Hilbijêre',
        'video' => 'Vîdeoyeke Heyî Hilbijêre',
    ],
];
