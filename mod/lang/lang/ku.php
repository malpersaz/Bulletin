<?php
/**
 * Language Module - Kurdish Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['lang'] = [
    'installer' => [
        'table_exists' => 'Tabloya \'lang\' li danegehê barkirî ye',
        'sql_added' => 'Pelê \'lang.sql\' li danegehê hate zêdekirin',
        'lang_added' => '<b>%s</b> - li danegehê hate zêdekirin!',
        'lang_exists' => '<b>%s</b> - berê li danegehê tomarkirî bû!',
    ],
    'system' => [
        'starting' => 'Sazkirin dest pê dike...',
        'installing' => 'tê sazkirin: %s',
    ],
    'installer_mobile' => [
        'hero' => [
            'badge' => 'Sazkera Rojname',
            'title' => 'Rêça sazkirinê ya phone-first',
            'body' => 'Ev sazker niha rêçikek mobîl a rêberdar bi hilanîna gav, hilbijartina modulan a li ser girêdanan û rewşên qedandina sazûmanî bi kar tîne.',
        ],
        'steps' => ['welcome' => 'Bi xêr hatî', 'language' => 'Ziman', 'checks' => 'Kontrol', 'setup' => 'Mîheng', 'modules' => 'Modul', 'sample' => 'Nimûne', 'progress' => 'Pêşketin', 'review' => 'Nirxandin', 'finish' => 'Dawî'],
        'welcome' => [
            'title' => 'Rêça sazkirinê ya armancdar',
            'body' => 'Qalik sazkerî rêça telefonê diparêze, hilbijartinan di nûvekirinan de diparêze û biryarên modulan berî destpêkirina sazkirinê zelal dike.',
            'project_label' => 'Projeyê',
            'project_detail' => 'Brandinga Rojname ji bo vê depoyê hîn jî armanca standard a sazkerê ye.',
            'language_scope_label' => 'Berfirehiya zimanê',
            'language_scope_detail' => 'Mîhengên default ên sazkerê hîn jî derxistinên zimanê taybet ên repo diparêzin.',
            'module_scope_label' => 'Berfirehiya modulan',
            'module_scope_detail' => 'Modulên pêwîst, vebijarkî, yên girêdayî û yên taybetê Rojname berî meşandinê ji hev têne veqetandin.',
        ],
        'language' => ['title' => 'Zimanê sazkerê', 'body' => 'Berî ku tu biçî nav gavên din, zimanê danişînê yê sazkerê hilbijêre. Ev rewş di session de tê parastin.'],
        'language_options' => ['de' => ['label' => 'Deutsch', 'code' => 'DE'], 'en' => ['label' => 'English', 'code' => 'EN'], 'fr' => ['label' => 'Français', 'code' => 'FR'], 'ku' => ['label' => 'Kurmancî', 'code' => 'KU'], 'ckb' => ['label' => 'کوردی', 'code' => 'CKB'], 'sv' => ['label' => 'Svenska', 'code' => 'SV'], 'zza' => ['label' => 'Zazakî', 'code' => 'ZZA']],
        'environment' => ['title' => 'Kontrola hawirdorê', 'body' => 'Gava pêşve dihatin heya ku hemû kontrolên pêwîst derbas bibin tê girtin.', 'ready' => 'Amade', 'blocked' => 'Girtî'],
        'setup' => [
            'title' => 'Mîhengên Malperê',
            'body' => 'Berî ku tu biçî nav hilbijartina modulan, rewşa herî kêm a projeyê tomar bike.',
            'project_name_label' => 'Navê projeyê',
            'project_default_language_label' => 'Zimanê bingehîn ê projeyê',
            'language_notice' => 'Zimanê sazkerê tenê ev rêçik diguhezîne. Zimanê bingehîn ê projeyê hilbijartinek cuda ya runtime ye.',
        ],
        'module_selection' => ['title' => 'Hilbijartina modulan', 'body' => 'Berî destpêkirina sazkirinê şiyanên vebijarkî hilbijêre. Modulên pêwîst girtî dimînin û girêdan li cihê xwe têne ravekirin.', 'required_title' => 'Modulên pêwîst', 'required_body' => 'Ev modul her tim tên sazkirin da bingeh ewle bimîne.', 'optional_body' => 'Modulên vebijarkî ji bo nirxandina telefona hatine komkirin.', 'dependencies_label' => 'Girêdayî ye bi: %s', 'required_by_label' => 'Pêwîst e ji bo: %s', 'required_chip' => 'Pêwîst', 'optional_chip' => 'Vebijarkî', 'auto_chip' => 'Bi hilbijartinê pêwîst bûye', 'project_specific_chip' => 'Tenê Rojname', 'sample_chip' => 'Ji bo nimûneyê amade', 'locked_notice' => 'Modulên ku bixweber pêwîst dibin, heya ku hilbijartina girêdayî were rakirin hildijêrin dimînin.'],
        'sample' => ['title' => 'Hilbijartina naveroka nimûneyî', 'body' => 'Hilbijêre ka sazker divê ji bo modulên hatine hilbijartin xebata naveroka nimûneyî biceribîne an na.', 'summary_label' => 'Modulên hatine hilbijartin', 'summary_detail' => '%d pêwîst, %d vebijarkî, bi tevahî %d modul dê bêne sazkirin.', 'sample_ready_label' => 'Modulên nimûneyî-amade', 'sample_ready_detail' => '%d modulên hatine hilbijartin piştgiriya naveroka nimûneyî nîşan didin.', 'sample_content_label' => 'Naveroka nimûneyî', 'sample_content_no' => 'Na, bi pergalek paqij dest pê bike.', 'sample_content_yes' => 'Erê, li cihên destûrdayî naveroka nimûneyî çalak bihêle.', 'sample_content_notice' => 'Ev hilbijartin berî meşandinê tê tomar kirin da ku rêçik biryarek erê an na ya eşkere hebe.', 'sample_content_warning' => 'Rojname hêlên nimûneyî yên menu, kategorî û naverokê bi default neçalak dihêle heya ku ev qaîdeya taybetê proje were guhertin.'],
        'progress' => ['title' => 'Pêşketina sazkirinê', 'body' => 'Niha meşandina modulan li şûna derketina raw rewşên sazûmanî nîşan dide.', 'success_title' => 'Sazkirin qediya', 'failure_title' => 'Sazkirin rawestiya', 'success_body' => 'Modulên hatine hilbijartin di nav rêçika rêberdar de qediya.', 'failure_body' => 'Modulek têk çû. Berî dubarekirinê kartên rewşê yên sazûmanî binêre.', 'current_state_label' => 'Rewşa sazkerê', 'failed_module_label' => 'Modula têkçûyî', 'failure_reason_label' => 'Rewşa xilasbûnê', 'reason_bootstrap_blocked' => 'Modulek bootstrapê ya pêwîst têk çû berî ku sazker bigihîje xala xilasbûnê ya ewle.', 'reason_resume_available' => 'Bootstrapa bingehîn qediya, ji ber vê yekê tu dikarî pirsgirêkê rast bikî û tenê modulên mayî dîsa biceribînî.', 'resume_label' => 'Rewşa domandinê', 'resume_detail' => '%d modul ji meşandina berê hatin parastin: %s', 'sample_content_label' => 'Rêvebirina naveroka nimûneyî', 'logs_label' => 'Hûrguliyên teknîkî', 'no_logs' => 'Tu derketina zêde ya modulan nehat tomarkirin.', 'state_completed' => 'Qediya', 'state_failed' => 'Têk çû', 'state_skipped' => 'Hat derbas kirin', 'state_warning' => 'Hişyarî', 'state_pending' => 'Li bendê ye', 'state_running' => 'Didome', 'state_degraded' => 'Kêmkirî', 'state_completed_with_warnings' => 'Bi hişyariyan qediya', 'state_resumed_completed' => 'Hat domandin û qediya', 'state_resumed_completed_with_warnings' => 'Hat domandin bi hişyariyan', 'state_recoverable_failure' => 'Têkçûna dikare were xilas kirin'],
        'post_install' => ['title' => 'Nirxandina piştî sazkirinê', 'body' => 'Berî ku tu ji sazkerê derkeve, binêre ka çi hat sazkirin.', 'installed_modules_label' => 'Modulên hatine sazkirin', 'skipped_modules_label' => 'Modulên hatine derbas kirin', 'warnings_label' => 'Hişyarî', 'sample_content_label' => 'Naveroka nimûneyî', 'no_warnings' => 'Tu hişyarî nehat tomarkirin.'],
        'result_messages' => ['module_not_selected' => 'Ji bo sazkirinê nehat hilbijartin.', 'module_resumed_completed' => 'Ji gava serkeftî ya berê hate parastin û di domandinê de dîsa nehat meşandin.', 'user_finalize_failed' => 'Sazkirina bikarhêneran nekarî gava dawîkirinê temam bike.', 'user_group_sync_complete' => 'Bi hevrêzkirina komê hate sazkirin.', 'module_installed' => 'Bi serkeftî hate sazkirin.', 'module_execute_failed' => 'Sazker nekarî vê modulê bar bike an bixebitîne.', 'module_reported_failure' => 'Modul di dema sazkirinê de rewşeke têkçûnê ragihand.', 'sample_note_no_capable_modules' => 'Tu modulên hatine hilbijartin piştgiriya naveroka nimûneyî nîşan nadin.', 'sample_note_disabled_selected' => 'Naveroka nimûneyî ji bo modulên hatine hilbijartin neçalak ma.', 'sample_note_evaluated_selected' => 'Rêvebirina naveroka nimûneyî ji bo koma sazkirinê ya hilbijartî modul bi modul hate nirxandin.', 'sample_module_disabled' => 'Naveroka nimûneyî ji bo vê modulê neçalak ma.', 'sample_module_legacy_inline' => 'Logîka legacy ya sazkerê hîn jî tomaran mîna nimûne ji bo vê modulê rêve dibe, ji ber vê yekê têkera eşkere ya nimûneyî hîn bi tevahî nehat girêdan.', 'sample_module_not_available' => 'Ji bo vê modulê hîn karek naveroka nimûneyî ya taybet nehatiye tomar kirin.', 'sample_module_project_rule_excluded' => 'Rojname vê modulê ji toxbûna naveroka nimûneyî ya standard derdixe.', 'sample_module_not_exposed' => 'Ev modul karek naveroka nimûneyî pêşkêş nake.', 'page_seed_kept_enabled' => 'Toxbûna legacy ya rûpelan ji bo vê modulê çalak ma çimkî naveroka nimûneyî çalak bû.', 'page_seed_removed' => 'Hilbijartina sazkirina paqij piştî sazkirinê %d toma rûpelê nimûneyî yên legacy jêbir.', 'page_seed_not_found' => 'Hilbijartina sazkirina paqij tu toma rûpelê nimûneyî yên legacy li pey xwe nehêşt.', 'content_seed_removed' => 'Qaîdeyên projeya Rojname piştî sazkirinê %d toma naveroka standard jêbirin.', 'content_seed_disabled' => 'Qaîdeyên projeya Rojname toxbûna naveroka standard ji bo vê modulê neçalak dihêlin.', 'category_seed_removed' => 'Qaîdeyên projeya Rojname piştî sazkirinê %d toma kategoriya standard jêbirin.', 'category_seed_disabled' => 'Qaîdeyên projeya Rojname toxbûna kategoriya standard ji bo vê modulê neçalak dihêlin.', 'module_failure_log' => 'Modula %s rewşeke têkçûnê vegerand.', 'user_finalize_log_failure' => 'Gava dawîkirina bikarhêneran rewşeke têkçûnê vegerand.'],
        'finish' => ['title' => 'Radestkirina dawî', 'body' => 'Sazker bi şopek nirxandinê ya dostê telefonê û gavê din ê zelal qediye.', 'reminder_title' => 'Bîranîna ewlehiyê', 'reminder_body' => 'Piştî rastandinê, heke modela we ya deploymentê dixwaze, gihîştina sazkerê bigire an rake.'],
        'actions' => ['start_setup' => 'Dest pê bike', 'continue' => 'Bidomîne', 'back' => 'Vegere', 'continue_to_setup' => 'Here mîhengan', 'continue_to_modules' => 'Here modulan', 'run_installer' => 'Sazkerê bimeşîne', 'retry_install' => 'Dîsa biceribîne', 'edit_modules' => 'Modulan biguherîne', 'exit_failure' => 'Vegere hilbijartina modulan', 'view_review' => 'Sazkirinê binêre', 'continue_to_finish' => 'Sazkirinê biqedîne', 'reset_state' => 'State-ê sazkerê nûve bike', 'open_admin' => 'Admin veke'],
        'errors' => ['resolve_environment' => 'Berî berdewamkirinê pirsgirêkên kontrola hawirdorê çareser bike.', 'blocked_install' => 'Dema kontrolên hawirdorê girtî ne, sazkirin nikare dest pê bike.'],
        'groups' => ['core_system' => 'Pergala bingehîn', 'content' => 'Naverok', 'media' => 'Medya', 'user_access' => 'Bikarhêner û gihîştin', 'communication' => 'Têkilî', 'project_specific' => 'Modulên taybetê proje'],
        'module_labels' => ['lang' => 'Pakêta zimanê', 'mods' => 'Tomara pergale', 'mod_settings' => 'Mîheng', 'mod_user' => 'Bikarhêner', 'mod_user_group' => 'Komên bikarhêneran', 'mod_gallery_mod' => 'Girêdanên galeriya', 'mod_gallery' => 'Galeri', 'mod_menu' => 'Menû', 'mod_slider' => 'Slider', 'mod_headline' => 'Sernav', 'mod_news_category' => 'Kategoriyên nûçeyan', 'mod_publication_category' => 'Kategoriyên weşanan', 'mod_acl' => 'Kontrola gihîştinê', 'mod_survey' => 'Lêpirsîn', 'mod_announcements' => 'Ragihandin', 'mod_attachment' => 'Pêvekan', 'mod_attachment_mod' => 'Girêdanên pêvekan', 'mod_video_embed_category' => 'Kategoriyên vîdyoyan', 'mod_document' => 'Belge', 'mod_popup' => 'Pacek', 'mod_video' => 'Vîdyo', 'mod_page' => 'Rûpel', 'mod_imagecategories' => 'Kategoriyên wêneyan', 'mod_user_pm' => 'Peyamên taybet', 'mod_video_embed' => 'Cihgirtina vîdyoyê', 'mod_news' => 'Nûçe', 'mod_publication' => 'Weşan', 'mod_category' => 'Kategorî', 'mod_content' => 'Naverok'],
        'checks' => [
            'php_version' => ['label' => 'PHP 8.0+', 'detail' => 'Runtime a heyî: %s'],
            'extensions' => ['label' => 'Extensionên pêwîst', 'ok' => 'Hemû extensionên pêwîst hene.', 'missing' => 'Kêm in: %s', 'items' => ['pdo' => 'PDO', 'mbstring' => 'Rêzimanên pir-byte', 'json' => 'JSON']],
            'directories' => ['label' => 'Pelrêçên nivîsbar', 'ok' => 'Pelrêçên depokirinê yên sazkerê nivîsbar in.', 'blocked' => 'Girtî: %s', 'items' => ['cache' => 'Cache', 'config' => 'Mîheng', 'data' => 'Data', 'templates_c' => 'Cache-a şablonan']],
            'database' => ['label' => 'Mîhengên danegehê', 'missing' => 'Tu mîhengek danegeha çalak nehate dîtin.', 'detail' => 'Adaptera çalak: %s (%s)'],
        ],
    ],
];
