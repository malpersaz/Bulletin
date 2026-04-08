<?php
/**
 * Language Module - German Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['lang'] = [
    'installer' => [
        'table_exists' => "Die Tabelle 'lang' ist bereits in der Datenbank installiert",
        'sql_added' => "Die Datei 'lang.sql' wurde zur Datenbank hinzugefügt",
        'lang_added' => '<b>%s</b> wurde zur Datenbank hinzugefügt',
        'lang_exists' => '<b>%s</b> ist bereits in der Datenbank vorhanden',
    ],
    'system' => [
        'starting' => 'Die Installation startet...',
        'installing' => 'wird installiert: %s',
    ],
    'admin' => [
        'title' => 'Sprachverwaltung',
        'headline' => 'Sprachen',
        'body' => 'Aktivieren, deaktivieren und setzen Sie die Standardsprache der Website in einem telefonfreundlichen Verwaltungsbereich.',
        'status_active' => 'Aktiv',
        'status_inactive' => 'Inaktiv',
        'default_badge' => 'Standard',
        'current_badge' => 'Aktuelle Oberfläche',
        'code_label' => 'Code',
        'activate' => 'Aktivieren',
        'deactivate' => 'Deaktivieren',
        'set_default' => 'Als Standard setzen',
        'empty_state' => 'Es wurden keine Sprachen gefunden.',
        'permission_denied' => 'Sie haben keine Berechtigung, Sprachen zu verwalten.',
        'back_panel' => 'Panel',
        'back_home' => 'Website',
        'back' => 'Zurück',
    ],
    'messages' => [
        'activated' => '%s ist jetzt aktiv.',
        'deactivated' => '%s ist jetzt inaktiv.',
        'default_changed' => '%s ist jetzt die Standardsprache.',
    ],
    'errors' => [
        'language_not_found' => 'Die ausgewählte Sprache wurde nicht gefunden.',
        'unknown_action' => 'Die angeforderte Sprachaktion ist ungültig.',
        'cannot_deactivate_default' => 'Wählen Sie zuerst eine andere Standardsprache, bevor Sie diese deaktivieren.',
        'cannot_deactivate_last_active' => 'Mindestens eine Sprache muss aktiv bleiben.',
        'cannot_deactivate_current_interface' => 'Wechseln Sie zuerst die aktuelle Verwaltungssprache, bevor Sie sie deaktivieren.',
        'update_failed' => 'Der Sprachstatus konnte nicht aktualisiert werden.',
    ],
];
