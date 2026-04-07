<?php
/**
 * Language Module - Swedish Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['lang'] = [
    'installer' => [
        'table_exists' => "Tabellen 'lang' är redan installerad i databasen",
        'sql_added' => "Filen 'lang.sql' har lagts till i databasen",
        'lang_added' => "<b>%s</b> - har lagts till i databasen!",
        'lang_exists' => "<b>%s</b> - finns redan i databasen!",
    ],
    'system' => [
        'starting' => "Startar installation...",
        'installing' => "installerar: %s",
    ],
    'installer_mobile' => [
        'hero' => ['badge' => 'Rojname-installation', 'title' => 'Telefonanpassat installationsskal', 'body' => 'Installationen kör nu i ett mobilfokuserat flöde med sparade steg, tidiga miljökontroller och kontrollerad överlämning till den befintliga installatören.'],
        'steps' => ['welcome' => 'Start', 'language' => 'Språk', 'checks' => 'Kontroller', 'setup' => 'Inställning', 'review' => 'Granska', 'result' => 'Resultat'],
        'welcome' => ['title' => 'Fokuserat installationsflöde', 'body' => 'Skalet håller installationen i ett telefonvänligt flöde, sparar framsteg och blockerar installation när förkrav saknas.', 'project_label' => 'Projekt', 'project_detail' => 'Rojname-varumärket är fortsatt standardmål för denna repositoriespecifika installatör.', 'language_scope_label' => 'Språkomfång', 'language_scope_detail' => 'Installatörens standardval behåller de projektspecifika språkundantagen.'],
        'language' => ['title' => 'Installatörsspråk', 'body' => 'Välj språk för installationssessionen innan du går vidare. Detta tillstånd sparas i sessionen.'],
        'environment' => ['title' => 'Miljökontroll', 'body' => 'Fortsättning blockeras tills alla obligatoriska kontroller är godkända.', 'ready' => 'Klar', 'blocked' => 'Blockerad'],
        'setup' => ['title' => 'Projektinställning', 'body' => 'Spara nu det minsta installationsläget. Den aktuella körfasen lämnar fortfarande modulinstallationen till den äldre pipelinen.', 'project_name_label' => 'Projektnamn', 'project_default_language_label' => 'Projektets standardspråk', 'sample_content_label' => 'Exempelinnehåll', 'sample_content_no' => 'Nej, håll installationen ren.', 'sample_content_yes' => 'Ja, spara beslutet om exempelinnehåll i installatörens tillstånd.', 'sample_content_notice' => 'Valet för exempelinnehåll sparas nu. Faktisk körkoppling kommer i en senare fas.'],
        'review' => ['title' => 'Granska och installera', 'body' => 'Det mobila skalet sammanfattar installatörens tillstånd och kör sedan den befintliga modulpipelinen.', 'project_name_label' => 'Projektnamn', 'installer_language_label' => 'Installatörsspråk', 'project_default_language_label' => 'Projektets standardspråk', 'module_summary_label' => 'Modulsammanfattning', 'module_summary_detail' => '%d obligatoriska, %d valfria, %d projektspecifika, %d med stöd för exempelinnehåll.', 'sample_content_label' => 'Val av exempelinnehåll', 'sample_content_enabled' => 'Sparat som aktiverat för senare körkoppling.', 'sample_content_disabled' => 'Sparat som inaktiverat.'],
        'result' => ['success_title' => 'Installationen är klar', 'failure_title' => 'Installationen stoppades', 'success_body' => 'Den befintliga modulinstallatören slutfördes i det mobila skalet.', 'failure_body' => 'Installationen slutfördes inte korrekt. Granska utdata innan du försöker igen.', 'installed_at' => 'Installerad', 'no_output' => 'Ingen installationsutdata fångades upp.'],
        'actions' => ['start_setup' => 'Starta', 'continue' => 'Fortsätt', 'back' => 'Tillbaka', 'continue_to_setup' => 'Fortsätt till inställning', 'continue_to_review' => 'Fortsätt till granskning', 'run_installer' => 'Kör installatör', 'reset_state' => 'Återställ tillstånd'],
        'errors' => ['resolve_environment' => 'Åtgärda blockerande miljökontroller innan du fortsätter.', 'blocked_install' => 'Installationen kan inte starta medan miljökontroller är blockerade.'],
        'checks' => [
            'php_version' => ['label' => 'PHP 8.0+', 'detail' => 'Aktuell runtime: %s'],
            'extensions' => ['label' => 'Obligatoriska tillägg', 'ok' => 'Alla obligatoriska tillägg är tillgängliga.', 'missing' => 'Saknas: %s'],
            'directories' => ['label' => 'Skrivbara kataloger', 'ok' => 'Installatörens lagringskataloger är skrivbara.', 'blocked' => 'Blockerade: %s'],
            'database' => ['label' => 'Databaskonfiguration', 'missing' => 'Ingen aktiv databaskonfiguration hittades.', 'detail' => 'Aktiv adapter: %s (%s)'],
        ],
    ],
];
