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
            'title' => 'Qalikê sazkirinê yê mobîl',
            'body' => 'Ev sazker di nav rêçika mobîl de bi hilanîna gav, kontrola zû ya hawirdorê û girêdana kontrolkirî bi sazkerê kevn dixebite.',
        ],
        'steps' => ['welcome' => 'Bi xêr hatî', 'language' => 'Ziman', 'checks' => 'Kontrol', 'setup' => 'Mîheng', 'review' => 'Nirxandin', 'result' => 'Encam'],
        'welcome' => [
            'title' => 'Rêça sazkirinê ya armancdar',
            'body' => 'Qalik sazkerî di nav rêça mobîl de dihêle, pêşketinê di nûvekirinan de diparêze û dema şertên pêwîst kêm bin sazkirinê digire.',
            'project_label' => 'Projeyê',
            'project_detail' => 'Brandinga Rojname ji bo vê depoyê hîn jî armanca standard a sazkerê ye.',
            'language_scope_label' => 'Berfirehiya zimanê',
            'language_scope_detail' => 'Mîhengên default ên sazkerê hîn jî derxistinên zimanê taybet ên repo diparêzin.',
        ],
        'language' => ['title' => 'Zimanê sazkerê', 'body' => 'Berî ku tu biçî nav gavên din, zimanê danişînê yê sazkerê hilbijêre. Ev rewş di session de tê parastin.'],
        'environment' => ['title' => 'Kontrola hawirdorê', 'body' => 'Gava pêşve dihatin heya ku hemû kontrolên pêwîst derbas bibin tê girtin.', 'ready' => 'Amade', 'blocked' => 'Girtî'],
        'setup' => [
            'title' => 'Mîhenga projeyê',
            'body' => 'Niha rewşa herî kêm a sazkirinê tomar bike. Qonaxa mevcûd hîn jî meşandina modulan radide rêçika kevn.',
            'project_name_label' => 'Navê projeyê',
            'project_default_language_label' => 'Zimanê bingehîn ê projeyê',
            'sample_content_label' => 'Naveroka nimûneyî',
            'sample_content_no' => 'Na, sazker paqij bimîne.',
            'sample_content_yes' => 'Erê, niyeta naveroka nimûneyî di state de were parastin.',
            'sample_content_notice' => 'Hilbijartina naveroka nimûneyî niha di state de tê parastin. Girêdana rastîn a meşandinê hîn qonaxek din dixwaze.',
        ],
        'review' => [
            'title' => 'Nirxandin û sazkirin',
            'body' => 'Qalikê mobîl niha rewşa sazkerê kurt dike û paşê rêçika modulan a heyî dimeşîne.',
            'project_name_label' => 'Navê projeyê',
            'installer_language_label' => 'Zimanê sazkerê',
            'project_default_language_label' => 'Zimanê bingehîn ê projeyê',
            'module_summary_label' => 'Kurteya modulan',
            'module_summary_detail' => '%d pêwîst, %d vebijarkî, %d taybetê proje, %d yên naveroka nimûneyî destek dikin.',
            'sample_content_label' => 'Biryara naveroka nimûneyî',
            'sample_content_enabled' => 'Ji bo girêdana meşandinê ya paşerojê wek çalak hate tomar kirin.',
            'sample_content_disabled' => 'Wek neçalak hate tomar kirin.',
        ],
        'result' => [
            'success_title' => 'Sazkirin qediya',
            'failure_title' => 'Sazkirin rawestiya',
            'success_body' => 'Sazkerê kevn ê modulan di nav qalikê mobîl de qediya.',
            'failure_body' => 'Sazkirin bi ser neket. Berî ku careke din biceribînî derketinê binêre.',
            'installed_at' => 'Dema sazkirinê',
            'no_output' => 'Tu derketina sazkerê nehat tomarkirin.',
        ],
        'actions' => ['start_setup' => 'Dest pê bike', 'continue' => 'Bidomîne', 'back' => 'Vegere', 'continue_to_setup' => 'Biçe mîhengan', 'continue_to_review' => 'Biçe nirxandinê', 'run_installer' => 'Sazkerê bimeşîne', 'reset_state' => 'State-ê sazkerê nûve bike'],
        'errors' => ['resolve_environment' => 'Berî berdewamkirinê pirsgirêkên kontrola hawirdorê çareser bike.', 'blocked_install' => 'Dema kontrolên hawirdorê girtî ne, sazkirin nikare dest pê bike.'],
        'checks' => [
            'php_version' => ['label' => 'PHP 8.0+', 'detail' => 'Runtime a heyî: %s'],
            'extensions' => ['label' => 'Extensionên pêwîst', 'ok' => 'Hemû extensionên pêwîst hene.', 'missing' => 'Kêm in: %s'],
            'directories' => ['label' => 'Pelrêçên nivîsbar', 'ok' => 'Pelrêçên depokirinê yên sazkerê nivîsbar in.', 'blocked' => 'Girtî: %s'],
            'database' => ['label' => 'Mîhenga danegehê', 'missing' => 'Tu mîhenga danegehê ya çalak nehatiye dîtin.', 'detail' => 'Adaptera çalak: %s (%s)'],
        ],
    ],
];
