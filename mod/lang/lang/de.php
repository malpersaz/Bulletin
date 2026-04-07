<?php
/**
 * Language Module - German Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['lang'] = [
    'installer' => [
        'table_exists' => "Tabelle 'lang' ist bereits in der Datenbank installiert",
        'sql_added' => "Datei 'lang.sql' wurde der Datenbank hinzugefügt",
        'lang_added' => "<b>%s</b> - zur Datenbank hinzugefügt!",
        'lang_exists' => "<b>%s</b> - existiert bereits in der Datenbank!",
    ],
    'system' => [
        'starting' => "Installation wird gestartet...",
        'installing' => "Installiere: %s",
    ],
    'installer_mobile' => [
        'hero' => ['badge' => 'Rojname Installer', 'title' => 'Telefonorientierte Installationsoberfläche', 'body' => 'Dieser Installer läuft jetzt in einem mobilen Ablauf mit persistenten Schritten, frühen Umgebungsprüfungen und kontrollierter Übergabe an den bestehenden Installer.'],
        'steps' => ['welcome' => 'Start', 'language' => 'Sprache', 'checks' => 'Prüfungen', 'setup' => 'Setup', 'review' => 'Prüfen', 'result' => 'Ergebnis'],
        'welcome' => ['title' => 'Fokussierter Installationsablauf', 'body' => 'Die Oberfläche hält die Einrichtung auf einem telefonfreundlichen Pfad, merkt sich den Fortschritt und blockiert die Installation bei fehlenden Voraussetzungen.', 'project_label' => 'Projekt', 'project_detail' => 'Rojname bleibt das Standardziel dieses repository-spezifischen Installers.', 'language_scope_label' => 'Sprachumfang', 'language_scope_detail' => 'Die Installer-Standards behalten die projektspezifischen Sprachausschlüsse bei.'],
        'language' => ['title' => 'Installersprache', 'body' => 'Wählen Sie die Sprache der Installer-Sitzung, bevor Sie mit dem Setup fortfahren. Dieser Zustand bleibt in der Sitzung erhalten.'],
        'environment' => ['title' => 'Umgebungsprüfung', 'body' => 'Die Weiterleitung bleibt blockiert, bis alle erforderlichen Prüfungen bestanden sind.', 'ready' => 'Bereit', 'blocked' => 'Blockiert'],
        'setup' => ['title' => 'Projekteinrichtung', 'body' => 'Erfassen Sie jetzt den minimalen Installationszustand. Die aktuelle Ausführungsphase übergibt die Modulinstallation weiterhin an die bestehende Pipeline.', 'project_name_label' => 'Projektname', 'project_default_language_label' => 'Standardprojektsprache', 'sample_content_label' => 'Beispielinhalte', 'sample_content_no' => 'Nein, die Installation sauber halten.', 'sample_content_yes' => 'Ja, die Beispielinhalt-Entscheidung im Installer-Zustand speichern.', 'sample_content_notice' => 'Die Beispielinhalt-Entscheidung wird jetzt gespeichert. Die eigentliche Ausführungsverdrahtung folgt in einer späteren Phase.'],
        'review' => ['title' => 'Prüfen und installieren', 'body' => 'Die mobile Oberfläche fasst den Installer-Zustand zusammen und startet dann die bestehende Modulpipeline.', 'project_name_label' => 'Projektname', 'installer_language_label' => 'Installersprache', 'project_default_language_label' => 'Standardprojektsprache', 'module_summary_label' => 'Modulübersicht', 'module_summary_detail' => '%d erforderlich, %d optional, %d projektspezifisch, %d mit Beispielinhalt.', 'sample_content_label' => 'Beispielinhalt-Entscheidung', 'sample_content_enabled' => 'Für spätere Ausführungsverdrahtung als aktiviert gespeichert.', 'sample_content_disabled' => 'Als deaktiviert gespeichert.'],
        'result' => ['success_title' => 'Installation abgeschlossen', 'failure_title' => 'Installation gestoppt', 'success_body' => 'Der bestehende Modulinstaller wurde innerhalb der mobilen Oberfläche abgeschlossen.', 'failure_body' => 'Die Installation wurde nicht erfolgreich abgeschlossen. Prüfen Sie die Ausgabe vor einem erneuten Versuch.', 'installed_at' => 'Installiert am', 'no_output' => 'Keine Installer-Ausgabe wurde erfasst.'],
        'actions' => ['start_setup' => 'Setup starten', 'continue' => 'Weiter', 'back' => 'Zurück', 'continue_to_setup' => 'Zum Setup weiter', 'continue_to_review' => 'Zur Prüfung weiter', 'run_installer' => 'Installer ausführen', 'reset_state' => 'Installer-Zustand zurücksetzen'],
        'errors' => ['resolve_environment' => 'Beheben Sie die blockierenden Umgebungsprüfungen, bevor Sie fortfahren.', 'blocked_install' => 'Die Installation kann nicht starten, solange Umgebungsprüfungen blockiert sind.'],
        'checks' => [
            'php_version' => ['label' => 'PHP 8.0+', 'detail' => 'Aktuelle Laufzeit: %s'],
            'extensions' => ['label' => 'Erforderliche Erweiterungen', 'ok' => 'Alle erforderlichen Erweiterungen sind verfügbar.', 'missing' => 'Fehlend: %s'],
            'directories' => ['label' => 'Beschreibbare Verzeichnisse', 'ok' => 'Die Installer-Verzeichnisse sind beschreibbar.', 'blocked' => 'Blockiert: %s'],
            'database' => ['label' => 'Datenbankkonfiguration', 'missing' => 'Keine aktive Datenbankkonfiguration gefunden.', 'detail' => 'Aktiver Adapter: %s (%s)'],
        ],
    ],
];
