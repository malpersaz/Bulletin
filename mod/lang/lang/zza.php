<?php
/**
 * Language Module - Zazaki Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['lang'] = [
    'installer' => [
        'table_exists' => "Tabloya 'lang' danegrot de barkerde ya",
        'sql_added' => "Dosyaya 'lang.sql' danegrot de zêde bîya",
        'lang_added' => "<b>%s</b> - danegrot de zêde bîya!",
        'lang_exists' => "<b>%s</b> - danegrot de berê qeyd bîya!",
    ],
    'system' => [
        'starting' => "Sazkerdiş dest pê keno...",
        'installing' => "tê sazkerdene: %s",
    ],
    'installer_mobile' => [
        'hero' => ['badge' => 'Sazkero Rojname', 'title' => 'Qalibo sazkerdışi yê mobîl', 'body' => 'Na sazkerdiş de rayeka mobîl de xebitena, qaydkerdışê gavı, kontrolê zû yê ortamî û girêdana kontrollî bi sazkerê kevin ra.'],
        'steps' => ['welcome' => 'Xêr ameyê', 'language' => 'Ziwan', 'checks' => 'Kontrol', 'setup' => 'Mîheng', 'review' => 'Vurnayış', 'result' => 'Encam'],
        'welcome' => ['title' => 'Rayeka sazkerdışi ya armancdar', 'body' => 'Qalib sazkerdışi rayeka telefona de cı karo, pêro dewamkerdışi qeyd keno û şertê lazıman de sazkırdiş gıreno.', 'project_label' => 'Proje', 'project_detail' => 'Brandê Rojname hema armanca default a sazkerdışi yê na depo yo.', 'language_scope_label' => 'Qadê ziwanî', 'language_scope_detail' => 'Defaultê sazkerdışi hema derxistışê zıwananê taybetê repo diparêze.'],
        'language' => ['title' => 'Ziwanê sazkerdışi', 'body' => 'Verê ke şıma bıri şımayê dewam bıkê ziwanê sessionê sazkerdışi weçine. Na rewş di session de qeyd beno.'],
        'environment' => ['title' => 'Kontrolê ortamî', 'body' => 'Raya pêşberdışi heta kontrolanê lazıman raşt nêbın gıreya.', 'ready' => 'Hazır', 'blocked' => 'Gıreya'],
        'setup' => ['title' => 'Mîhengê proje', 'body' => 'Niha rewşa herî kêm a sazkerdışi qeyd bike. Qonaxo mevcud hema modulan rêçikê kevin ra dewam dıkero.', 'project_name_label' => 'Nameyê proje', 'project_default_language_label' => 'Ziwanê bingeyîn ê proje', 'sample_content_label' => 'Zerreyo nimûne', 'sample_content_no' => 'Nê, sazkerdiş pak bımane.', 'sample_content_yes' => 'Erê, niyeta zerreyê nimûneyî de state de qeyd bıbo.', 'sample_content_notice' => 'Weçinayışê zerreyê nimûne niha de state de qeyd beno. Girêdayışê meşandışi hema qonaxekê din lazımo.'],
        'review' => ['title' => 'Vurnayış û sazkerdış', 'body' => 'Qalibo mobîl niha rewşa sazkerdışi kurte keno û sonra rêçika modulanê heyî dewam keno.', 'project_name_label' => 'Nameyê proje', 'installer_language_label' => 'Ziwanê sazkerdışi', 'project_default_language_label' => 'Ziwanê bingeyîn ê proje', 'module_summary_label' => 'Kurteya modulan', 'module_summary_detail' => '%d lazim, %d weçarnayîş, %d taybetê proje, %d supportê zerreyê nimûne dıkeno.', 'sample_content_label' => 'Biryara zerreyê nimûne', 'sample_content_enabled' => 'Vajiayışê paşê de çalak qeyd biyo.', 'sample_content_disabled' => 'Neçalak qeyd biyo.'],
        'result' => ['success_title' => 'Sazkerdış qediya', 'failure_title' => 'Sazkerdış rawestîya', 'success_body' => 'Sazkero kevin ê modulan di nav qalibo mobîl de qediya.', 'failure_body' => 'Sazkerdış bi ser nêket. Verê tekrar kerdenî derketışa bıvinê.', 'installed_at' => 'Demê sazkerdışi', 'no_output' => 'Hîç derketışê sazkerdışi qeyd nêbiyo.'],
        'actions' => ['start_setup' => 'Dest pê ke', 'continue' => 'Dewam ke', 'back' => 'Peyser', 'continue_to_setup' => 'Bıçı mîhengan', 'continue_to_review' => 'Bıçı vurnayışi', 'run_installer' => 'Sazkero bımeşne', 'reset_state' => 'Stateyê sazkerdışi sıfır ke'],
        'errors' => ['resolve_environment' => 'Verê dewam kerdenî pirsgirêkanê kontrolê ortamî çareser ke.', 'blocked_install' => 'Dema kontrolanê ortamî gıreyayî ne, sazkerdış dest nêkeno.'],
        'checks' => [
            'php_version' => ['label' => 'PHP 8.0+', 'detail' => 'Runtime a heyî: %s'],
            'extensions' => ['label' => 'Extensionê lazıman', 'ok' => 'Hemo extensionê lazıman ameyê.', 'missing' => 'Kêmî: %s'],
            'directories' => ['label' => 'Rêçê nivîsbari', 'ok' => 'Rêçê depokerdışi yê sazkerdışi nivîsbarê.', 'blocked' => 'Gıreya: %s'],
            'database' => ['label' => 'Mîhengê database', 'missing' => 'Mîhengê database yo çalak nêvêniyayo.', 'detail' => 'Adaptero çalak: %s (%s)'],
        ],
    ],
];
