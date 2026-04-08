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
        'lang_added' => '<b>%s</b> har lagts till i databasen',
        'lang_exists' => '<b>%s</b> finns redan i databasen',
    ],
    'system' => [
        'starting' => 'Installationen startar...',
        'installing' => 'installerar: %s',
    ],
    'admin' => [
        'title' => 'Sprakhantering',
        'headline' => 'Sprak',
        'body' => 'Aktivera, inaktivera och valj webbplatsens standardsprak i en telefonanpassad panel.',
        'status_active' => 'Aktiv',
        'status_inactive' => 'Inaktiv',
        'default_badge' => 'Standard',
        'current_badge' => 'Nuvarande gränssnitt',
        'code_label' => 'Kod',
        'activate' => 'Aktivera',
        'deactivate' => 'Inaktivera',
        'set_default' => 'Sätt som standard',
        'empty_state' => 'Inga språk hittades.',
        'permission_denied' => 'Du har inte behörighet att hantera språk.',
        'back_panel' => 'Panel',
        'back_home' => 'Webbplats',
        'back' => 'Tillbaka',
    ],
    'messages' => [
        'activated' => '%s är nu aktiv.',
        'deactivated' => '%s är nu inaktiv.',
        'default_changed' => '%s är nu standardspråket.',
    ],
    'errors' => [
        'language_not_found' => 'Det valda språket kunde inte hittas.',
        'unknown_action' => 'Den begärda språkåtgärden är ogiltig.',
        'cannot_deactivate_default' => 'Välj ett annat standardspråk innan du inaktiverar detta.',
        'cannot_deactivate_last_active' => 'Minst ett språk måste vara aktivt.',
        'cannot_deactivate_current_interface' => 'Byt nuvarande adminsprit först innan du inaktiverar det.',
        'update_failed' => 'Språkstatusen kunde inte uppdateras.',
    ],
];
