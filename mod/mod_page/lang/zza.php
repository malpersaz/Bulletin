<?php
/**
 * Page Management Module Kurdish Language File
 * Amed Framework CMS - Sîstema Rêveberiya Naverokê Pirzimanî
 *
 * Ev dosya hemû wergerên kurdî ji bo modula rêveberiya rûpelan dihewîne.
 * Wergerên profesyonel bi termînolojiya CMS-ya nûjen.
 */

$lang['mod_page'] = [
    // === Core Operations ===
    'add' => 'Rûpel Lê Zêde Bike',
    'edit' => 'Rûpel Biguherîne',
    'list' => 'Lîsteya Rûpelan',
    'total_pages' => 'Pêroyî Rûpelî',
    'published_pages' => 'Weşîyaye',
    'draft_pages' => 'Pêşnivîs',
    'expand_all' => 'Pêroyan Ake',
    'collapse_all' => 'Pêroyan Biqefilne',
    'search_placeholder' => 'Rûpelan de bigêre...',

    // === System Messages ===
    'information' => 'Agahdarî',
    'success' => 'Çalakî Bi Serkeftin',
    'error' => 'Çewtî Çêbû',

    // === Confirmation Messages ===
    'remove_image' => 'Bi rastî jî dixwazî vê wêneyê rakî?',
    'remove_image_success' => 'Wêne Bi Serkeftin Hate Rakirin',
    'choose_wish' => 'Ji kerema xwe re, ger bixwazî hilbijêre',
    'empty' => [
        'no_pages' => 'Peli Nêvêneyay',
    ],
    'menu_options' => [
        'none' => 'Meni de cı nêke',
        'top_level' => 'Wek hêmana sero menu de ke',
        'slot_root' => 'Bınê rûye menuye ke bıser',
    ],
    'menu_slot_options' => [
        'none' => 'Rûye menu niyo',
        'header' => 'Menuya Cor',
        'footer' => 'Menuya Bın',
    ],
    'remove_record' => 'Bi rastî jî dixwazî vê tomarê rakî?',
    'seo_meta' => 'Daneyê SEO',

    // === System Messages ===
    'messages' => [
        'success' => [
            'add' => 'Rûpel Bi Serkeftin Hate Zêdekirin',
            'edit' => 'Rûpel Bi Serkeftin Hate guhertin',
            'delete' => 'Rûpel bi serkeftî hate jêbirin',
        ],
        'error' => [
            'add' => 'Zêdekirina Rûpelê Serneket',
            'edit' => 'guhertina Rûpelê Serneket',
        ],
        'information' => [
            'moderate' => 'Li ser rûpela ku dixwazî sererast bikî du caran bitikîne',
            'move' => 'Ji bo guherandina rêzê rûpelan bikişîne û berde',
        ],
        'warning' => [
            'reorder' => 'Cihê rûpelê were guhertin?',
            'delete' => 'Ma tu bawer î ku dixwazî vê rûpelê jê bibî?',
        ],
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'reorder_single' => 'Tu dixwazî cihê vê rûpelê biguherînî?',
        'delete_single' => 'Ma tu bawer î ku dixwazî vê rûpelê jê bibî?',
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Erê',
        'no' => 'Na',
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'Tomara ku hate nîşankirin nehate dîtin',
        'slot_branch_missing' => 'Rûye menuye ke zımanê nê de çareser nêbiyo',
    ],

    // === Button Labels ===
    'button' => [
        'add' => 'Zêde Bike',
        'edit' => 'Biguhere',
        'delete' => 'Rake',
        'cancel' => 'Betal',
        'remove' => 'Rake',
        'yes' => 'Erê',
        'no' => 'Na',
        'gallery' => 'Galeriya Wêneyan',
        'file' => 'Rêveberiya Pelan',
        'ok' => 'Temam',
        'upload' => 'Bar Ke',
    ],

    // === Actions ===
    'actions' => [
        'cancel' => 'Betal',
        'edit' => 'Biguhere',
        'delete' => 'Jêbibe',
        'unlock_reorder' => 'Moda Kaşkirinê Veke',
        'lock_reorder' => 'Moda Kaşkirinê Bigire',
    ],

    // === Confirmation Messages ===
    'validator' => [
        'required' => 'Ji kerema xwe re qada %s vala nekin',
        'length' => '%s dikare herî kêm %s û herî zêde %s tîpan be',
        'email' => 'Ji kerema xwe re navnîşana e-postayê ya derbasdar li qada %s binivîsin',
        'unique' => '%s ku nivîsandî di pergala de hate tomar ji kerema xwe re %s-ekê din hilbijêre',
        'inarray' => 'Qada %s tenê nirxên \' %s \' digire',
        'deck_length' => 'Ji kerema xwe nivîsek destpêkê ya ku di navbera 90 û 120 tîpan de be binivîsin.',
    ],

    // === Column Permissions ===
    'role' => [
        'name' => 'Rêzên Rêveberiya Rûpelan',
        'add' => 'Dikare Rûpel Zêde Bike',
        'edit' => 'Dikare Rûpel Biguhere',
        'delete' => 'Dikare Rûpel Rake',
        'list' => 'Dikare Rêveberiya Rûpelan Bibîne',
        'gallery' => 'Dikare Galerî Zêde Bike?',
        'file' => 'Dikare Pel Zêde Bike?',
        'moderate_edit' => 'Dikare Tomara Rûpelên Ku Ne Xwe Ne Biguhere',
        'moderate_delete' => 'Dikare Tomara Rûpelên Ku Ne Xwe Ne Rake',
        'approval' => 'Şeno Rûpelan Bıweşîno/Nêweşîno?',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'moderate_edit' => 'Tu Nikarî Tomara Rûpelên Ku Ne TeNe Biguhere',
        'moderate_delete' => 'Tu Nikarî Tomara Rûpelên Ku Ne TeNe Rake',
        'add' => 'Destûra Te Ji Bo Zêdekirina Rûpelan Tune',
        'edit' => 'Destûra Te Ji Bo Sererastkirina Rûpelan Tune',
        'list' => 'Destûra Te Ji Bo Dîtinê Rêveberiya Rûpelan Tune',
        'delete' => 'Destûra Te Ji Bo Rakirina Rûpelan Tune',
        'gallery' => 'Destûra Te Ji Bo Zêdekirina Galeriyê Tune',
        'file' => 'Destûra Te Ji Bo Zêdekirina Pelan Tune',
        'approval' => 'Destura Şıma Qandê Weşanayışê Rûpelan Çıniya',
    ],

    // === Admin Panel ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Ziman',
            'name' => 'Navê Rûpelê',
            'parent_id' => 'Rûpela Jêrîn',
            'order' => 'Rêz',
            'root' => 'Ma Ev Rûpela Root E Ku Ne Divê Were Nîşandan?',
            'status' => 'Ma Were Weşandin?',
            'publish_desc' => 'Ma tu dixwazî vê naverokê biweşînî, an wekî Pêşnûma tomar bikî?',
            'deck' => 'Ma bila beşa destpêk di naverokê de xwiya bike?',
            'summary' => 'Destpêk (Deck)',
            'content' => 'Naverok',
            'gallery_id' => 'ID ya Galeriyê (Ji bo nîşandina wêneyên galeriyê ya rûpelek din ID ya wê rûpelê binivîse)',
            'file_id' => 'ID ya Pelê (Ji bo nîşandina pelên rûpelek din ID ya wê rûpelê binivîse)',
            'seo' => 'Girêdana Rûpelê',
            'title' => 'Sernav (Ji Bo Motora Lêgerînê)',
            'keywords' => 'Kilîtvanan (Ji Bo Motora Lêgerînê)',
            'description' => 'Rave (Ji Bo Motora Lêgerînê)',
            'image' => 'Wêne',
            'acl_id' => 'Kî Dikare Vê Rûpelê Bibîne?',
            'category_id' => 'Kategoriya Naveroka Têkildar',
            'menu' => 'Rûpelê Bişîne Menûyê',
            'menu_slot' => 'Rûye Menuyi',
        ],
        'select_value' => [
            'root_e' => 'Erê',
            'root_h' => 'Na',
        ],
        'radio' => [
            'publish_yes' => 'Biweşîne',
            'publish_no' => 'Tomar Bike',
            'deck_yes' => 'Erê',
            'deck_no' => 'Na',
        ],
    ],
    'wizard' => [
        'steps' => [
            'language' => 'Ziman',
            'structure' => 'Avabûn',
            'title' => 'Sernav',
            'summary' => 'Kurte',
            'content' => 'Naverok',
            'display' => 'Nîşandan',
            'relations' => 'Têkilî',
            'seo_finish' => 'SEO û Dawî',
        ],
        'descriptions' => [
            'language' => 'Verê avakirışê dara rûpelî û vebijarkeyên têkildar, zıwan bıweçne.',
            'structure' => 'Bıweçne ke no rûpel koca de yo û eke gerekeno ke bi darê kategori û menûyî re girêdaye biyo.',
            'title' => 'Nameyo rûpelî vırazne ke nasnameya sereke ya rûpelî saz keno.',
            'summary' => 'Nusneya kurteya destpêkî ke verê naverokî de aseno bınuse.',
            'content' => 'Naveroka sereke ya rûpelî amade ke.',
            'display' => 'Tevgera avahî ya nîşandışî ya rûpelî bıyar ke.',
            'relations' => 'Tomar û medya yê têkildar ê vebijarkî bıgirêde.',
            'seo_finish' => 'Nirxey SEO bıvurne, rewşa weşanî bıweçne û sazkerdışê rûpelî biqedîne.',
        ],
        'page_summary' => 'Kurteya Rûpelî',
        'previous' => 'Peyser',
        'next' => 'Pêser',
        'image' => [
            'drop_title' => 'Wêneyekî kaşke ser vir an jî',
            'drop_description' => 'Bar bike, pêve bike an jî wêneyekî berde vir.',
            'upload_link' => 'pelekê bar bikin',
            'link_separator' => 'AN JÎ',
            'link_placeholder' => 'Lînka wêneyê binivîse',
            'link_action' => 'Bîne',
            'hint_loading' => 'Wêne tê anîn...',
            'hint_invalid_type' => 'Tenê pelên wêneyan li vir têne piştgirî kirin.',
            'hint_no_drop_data' => 'Di naveroka hatî berdan de tu wêneyekî bikêr nehat dîtin.',
            'hint_remote_failed' => 'Wêne ji wî çavkanî nehat anîn.',
        ],
    ],
    'quick_unpublish_confirm' => 'Şıma bi raştî wazenê nê rûpelî weşan ra wedarê?',
    'quick_publish_confirm' => 'Şıma bi raştî wazenê nê rûpelî bıweşînê?',
    'published_msg' => 'Rûpel Bi Serkeftin Hate Weşandin',
    'unpublished_msg' => 'Rûpel Ji Weşanê Hate Rakirin',
    'status_badge' => [
        'active' => 'Weşandî',
        'draft' => 'Pêşnûma',
    ],
    'picker' => [
        'file' => 'Dosyaya Mewcude Weçine',
    ],
    'ajax' => [
        'database_error' => 'Xetaya Database',
        'record_not_found' => 'Pele nêame diyene!',
        'unknown_type' => 'Babetê munasabetî nêzanaye',
        'relation_updated' => 'Munasabet bi serkewtiş ame newekerdene',
    ],
];
