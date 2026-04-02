<?php
/**
 * Content Management Module - Kurdish Language File
 *
 * @package Rojname
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

    // === Basic Actions ===
    'add' => 'Lê Zêde Bike',
    'edit' => 'Biguhere',
    'list' => 'Naverok',
    'information' => 'Agahî!',
    'oke' => 'Çêbû!',
    'hata' => 'Çewtî Çêbû!',
    'error' => 'Çewtiya Giran Çêbû!',
    'remove_image' => 'Ma tu bawer î ku dixwazî vî wêneyî jêbibî?',
    'remove_image_oke' => 'Wêne hate rakirin',
    'choose_wish' => 'Beşekê Hilbijêre',
    'remove_record' => 'Ma tu bawer î, ku dixwazî vê naverokê jêbibî?',
    'delete_confirm' => 'Ma tu bawer î ku dixwazî vê naverokê jêbibî?',
    'quick_unpublish_confirm' => 'Tu bawer î ku dixwazî vê naverokê ji weşanê rakî?',
    'quick_publish_confirm' => 'Ma tu bawer î, ku dixwazî vê naverokê biweşînî?',
    'resim_ekle' => 'Wêne lê zêde bike',
    'resim_yukle_degistir' => 'Wêne Lê Bar Bike/Biguherîne',
    'lokal' => 'Lokal',
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
        'oke' => [
            'add' => 'Naverok Hate Zêdekirin!',
            'edit' => 'Naverok Hate Sererastkirin!',
        ],
        'error' => [
            'add' => 'Naverok Nehat Zêdekirin!',
            'edit' => 'Naverok Nehat Sererastkirin!',
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
        'add' => 'Zêde Bike',
        'edit' => 'Biguhere',
        'yenile' => 'Nû Bike',
        'delete' => 'Jêbibe',
        'gallery' => 'Galeriya Wêneyan',
        'file' => 'Rêveberê Pelan',
        'vazgec' => 'Betal',
        'kaldir' => 'Rake',
        'evet' => 'Erê',
        'hayir' => 'Na',
    ],

    // === Data Grid Messages ===
    'grid' => [
        0 => 'Ji {total} encamên {from} heta {to} tên nîşandan.',
        1 => 'Rûpel',
        2 => 'Hejmara Rûpelan',
        3 => 'Daneyên têne barkirin, jî kerema xwe bisekinin...',
        4 => 'Tu dane nehat dîtin',
        5 => 'Çewtiya Girêdanê',
        6 => 'Lêgerîn',
        7 => 'Ji kerema xwe herî kêm rêzekê hilbijêrin',
        8 => 'Ji kerema xwe tenê rêzekê hilbijêrin',
        'approve' => [
            'key' => 'Weşandin',
            'value' => 'Ma tu bawer î, ku dixwazî vê naverokê ji weşanê rakî?',
        ],
        'unapprove' => [
            'key' => 'Ji Weşandinê Bikişîne',
            'value' => 'Ma tu bawer î, ku dixwazî vê naverokê ji weşanê rakî?',
        ],
        'load_more' => 'Pirtir Nîşan Bide',
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Naverok bê %s nayê şandin!',
        'length' => '%s dikare herî kurt %s û herî dirêj %s tîpan be',
        'email' => 'Ji kerema xwe navekî e-postayê yê derbasdar li qada %s binivîsin.',
        'unique' => '%s ku te nivîsî di pergalê de qeydkirî ye. Ji kerema xwe %s-ekê din destnîşan bikin!',
        'inarray' => 'Qada %s dikare tenê nirxên \' %s \' bigire',
        'title_length' => 'Divê tu sernavekî herî kêm ji 1 tîpê û herî pir ji 70 tîpan pêk tê binivîsî.',
        'title_exists' => 'Ev sernav jixwe heye, ji kerema xwe biguherînin',
        'title_unique' => 'Sernavê we bêhempa ye',
        'deck_length' => 'Ji kerema xwe nivîsek destpêkê ya ku herî kêm 110 tîp û herî pir 280 tîp pêk tê binivîsin.',
    ],

    // === Permission Roles ===
    // === Permission Roles ===
    'roll' => [
        'ad' => 'Rolenên Rêveberiya Naverokê',
        'add' => 'Dikare naverokan lê zêde bike',
        'edit' => 'Dikare naverokan biguhere',
        'delete' => 'Dikare naverokan rake',
        'list' => 'Dikare rêveberiya naverokê bibîne',
        'publish' => 'Dikare naverokan weşîne',
        'unpublish' => 'Dikare naverokan ji weşanê rake',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'add' => 'Ji bo zêdekirina naverokê destûra te tune',
        'edit' => 'Ji bo sererastkirina naverokê destûra te tune',
        'list' => 'Ji bo dîtina rêveberiya naverokê destûra te tune',
        'delete' => 'Ji bo rakirana naverokê destûra te tune',
        'approval' => 'Ji bo pejirandina naverokê destûra te tune',
        'moderate_edit' => 'Tu nikarî tomarên naverokê yên ku yên te ne sererast bikî',
        'moderate_delete' => 'Tu nikarî tomarên naverokê yên ku yên te ne rakî',
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
        'kayit_yonetimi' => 'Rêveberiya Tomaran',
        'islem' => 'Operasyon',
        'uygula' => 'Bisepîne',
        'islem_column' => 'Çalakî',
        'select' => 'Hilbijêre',
        'burdan' => 'Ji Vir',
        'buraya' => 'Bo Vir',
        'ara' => 'Lêbigere',
        'sifirla' => 'Sifir Bike',
        'approval' => 'Bipejirîne',
        'isleniyor' => 'Têkirîn',
        'record_selected' => 'Tomar hate hilbijartin',
        'islem_gerceklestirilemedi' => 'Operasyon nehat pêkanîn',
        'islem_secin' => 'Ji kerema xwe operasyonekê hilbijêrin',
        'all' => 'Hemû',
        'pdf' => 'PDF',
        'csv' => 'CSV',
        'excel' => 'Excel',
        'yazdir' => 'Çap Bike',
        'yazdir_bilgi' => 'Agahiya Çapê',
        'olusturan' => 'Afirîner',
        'kopyala' => 'Ji Ber Bigire',
        'default_sort' => 'Yên Nû',
        'all' => 'Hemû',
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
            'intro' => 'Kin û zelal binivîse. (Herî kin: 110 - herî pir: 255 tîp)',
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
        'messages' => [
            'success_sim' => 'Naverok bi serketî hat afirandin! (Simulasyon)',
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
        'title' => 'Operasyonên Naverokê',
        'edit' => 'Biguhere',
        'gallery' => 'Operasyonên Galeriyê',
        'files' => 'Operasyonên Pelan',
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
        'status_alert' => "REWŞ HAT NÛKIRIN:\n%s",
        'delete_confirm' => 'Erê, Jêbibe!',
        'delete_success' => 'Naverok hat jêbirin!',
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
            "Serlêdana Mobîl a Rojname hat nûkirin"
        ],
        'categories' => ["Rojev", "Çand & Huner", "Teknolojî", "Medya", "Jiyan"],
        'authors' => [],
    ],
];
