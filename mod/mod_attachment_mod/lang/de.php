<?php
/**
 * Dateimodul-Integration - Deutsche Sprachdatei
 *
 * @package AKM Business CMS
 * @module  mod_attachment_mod
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Direkten Zugriff verhindern
if (!defined('AKM_LOADED')) {
    die('Direkter Zugriff auf diese Datei ist nicht erlaubt.');
}

/**
 * Dateimodul-Integration Sprachübersetzungen
 */
$lang['mod_attachment_mod'] = [

    // === Grundlegende Navigation ===
    'list' => 'Dateimodulliste',
    'add' => 'Dateimodul hinzufügen',
    'edit' => 'Dateimodul bearbeiten',
    'information' => 'Information',
    'oke' => 'Erfolg',
    'hata' => 'Fehler aufgetreten',
    'error' => 'Fataler Fehler aufgetreten',
    'remove_record' => 'Sind Sie sicher, dass Sie diesen Datensatz löschen möchten?',

    // === Nachrichtenstruktur ===
    'message' => [
        'oke' => [
            'add' => 'Dateimodul erfolgreich hinzugefügt!',
            'edit' => 'Dateimodul erfolgreich aktualisiert!',
        ],
        'error' => [
            'add' => 'Dateimodul konnte nicht hinzugefügt werden!',
            'edit' => 'Dateimodul konnte nicht aktualisiert werden!',
        ],
    ],

    'errors' => [
        'record' => 'Der angegebene Datensatz konnte nicht gefunden werden!',
    ],

    'button' => [
        'add' => 'Hinzufügen',
        'edit' => 'Bearbeiten',
        'delete' => 'Löschen',
        'save' => 'Speichern',
        'cancel' => 'Abbrechen',
        'remove' => 'Entfernen',
        'yes' => 'Ja',
        'no' => 'Nein',
        'refresh' => 'Aktualisieren',
        'download' => 'Herunterladen',
        'upload' => 'Hochladen',
        'yenile' => 'Aktualisieren',
        'vazgec' => 'Abbrechen',
        'kaldir' => 'Entfernen',
        'evet' => 'Ja',
        'hayir' => 'Nein',
    ],

    'grid' => [
        0 => 'Zeige {from} bis {to} von {total} Ergebnissen',
        1 => 'Seite',
        2 => 'Seitenzahl',
        3 => 'Daten werden geladen, bitte warten...',
        4 => 'Keine Daten gefunden',
        5 => 'Verbindungsfehler',
        6 => 'Suche',
        7 => 'Bitte wählen Sie mindestens eine Zeile aus',
        8 => 'Bitte wählen Sie nur eine Zeile aus',
    ],

    'validator' => [
        'required' => 'Bitte lassen Sie das Feld %s nicht leer',
        'length' => '%s muss zwischen %s und %s Zeichen lang sein',
        'email' => 'Bitte geben Sie eine gültige E-Mail-Adresse im Feld %s ein',
        'unique' => 'Das eingegebene %s ist bereits im System registriert. Bitte geben Sie ein anderes %s an!',
        'inarray' => 'Das Feld %s kann nur die Werte \' %s \' akzeptieren',
    ],

    'roll' => [
        'ad' => 'Dateimodul-Verwaltungsrollen',
        'add' => 'Kann Dateimodul hinzufügen',
        'edit' => 'Kann Dateimodul bearbeiten',
        'delete' => 'Kann Dateimodul löschen',
        'list' => 'Kann Dateimodul-Verwaltung ansehen',
        'moderate_edit' => 'Kann Dateimodul-Datensätze von anderen bearbeiten',
        'moderate_delete' => 'Kann Dateimodul-Datensätze von anderen löschen',
    ],

    'role_errors' => [
        'moderate_edit' => 'Sie können keine Dateimodul-Datensätze von anderen bearbeiten',
        'moderate_delete' => 'Sie können keine Dateimodul-Datensätze von anderen löschen',
        'add' => 'Sie haben keine Berechtigung, Dateimodule hinzuzufügen',
        'edit' => 'Sie haben keine Berechtigung, Dateimodule zu bearbeiten',
        'list' => 'Sie haben keine Berechtigung, die Dateimodul-Verwaltung anzusehen',
        'delete' => 'Sie haben keine Berechtigung, Dateimodule zu löschen',
    ],

    'admin' => [
        'columns' => [
            'id' => 'ID',
            'ad' => 'Dateimodulname',
            'mod_name' => 'Modulname',
            'mod_key' => 'Modulschlüssel (ID)',
            'mod_value' => 'Modulwert (Name)',
        ],
    ],

    // === Navigation & Aktionen ===
    'actions' => [
        'add' => 'Dateimodul hinzufügen',
        'edit' => 'Dateimodul bearbeiten',
        'list' => 'Dateimodulliste',
        'delete' => 'Dateimodul löschen',
        'cancel' => 'Abbrechen',
        'save' => 'Speichern',
        'update' => 'Aktualisieren',
        'remove' => 'Entfernen',
    ],

    // === Systemnachrichten ===
    'messages' => [
        'success' => [
            'added' => 'Dateimodul erfolgreich hinzugefügt!',
            'updated' => 'Dateimodul erfolgreich aktualisiert!',
            'deleted' => 'Dateimodul erfolgreich gelöscht!',
            'configured' => 'Dateimodul erfolgreich konfiguriert!',
        ],
        'error' => [
            'not_added' => 'Dateimodul konnte nicht hinzugefügt werden!',
            'not_updated' => 'Dateimodul konnte nicht aktualisiert werden!',
            'not_deleted' => 'Dateimodul konnte nicht gelöscht werden!',
            'not_found' => 'Der angegebene Dateimodul-Datensatz konnte nicht gefunden werden!',
            'configuration_failed' => 'Dateimodul-Konfiguration fehlgeschlagen!',
        ],
        'info' => [
            'module_intro' => 'Dateimodule ermöglichen die Integration von Dateiverwaltungsfunktionen in andere Systemkomponenten',
            'configuration_info' => 'Konfigurieren Sie die Dateimodul-Einstellungen, um Dateivorgänge in bestimmten Modulen zu ermöglichen',
        ],
    ],

    // === Modulkonfiguration ===
    'configuration' => [
        'basic_settings' => 'Grundeinstellungen',
        'advanced_settings' => 'Erweiterte Einstellungen',
        'integration_settings' => 'Integrations-Einstellungen',
        'security_settings' => 'Sicherheits-Einstellungen',

        'module_name' => 'Modulname',
        'module_description' => 'Modulbeschreibung',
        'module_version' => 'Modulversion',
        'module_status' => 'Modulstatus',
        'target_module' => 'Zielmodul',
        'integration_type' => 'Integrationstyp',
        'file_permissions' => 'Dateiberechtigungen',
        'access_level' => 'Zugriffsebene',
        'auto_sync' => 'Automatische Synchronisierung',
    ],

    // === Bestätigungsnachrichten ===
    'confirmations' => [
        'remove_record' => 'Sind Sie sicher, dass Sie diesen Datensatz löschen möchten?',
        'delete_single' => 'Sind Sie sicher, dass Sie dieses Dateimodul löschen möchten?',
        'delete_multiple' => 'Sind Sie sicher, dass Sie :count ausgewählte Dateimodule löschen möchten?',
        'enable_module' => 'Sind Sie sicher, dass Sie dieses Dateimodul aktivieren möchten?',
        'disable_module' => 'Sind Sie sicher, dass Sie dieses Dateimodul deaktivieren möchten?',
        'reset_configuration' => 'Sind Sie sicher, dass Sie die Modulkonfiguration zurücksetzen möchten?',
    ],

    // === Schaltflächen ===
    'buttons' => [
        'add' => 'Hinzufügen',
        'edit' => 'Bearbeiten',
        'refresh' => 'Aktualisieren',
        'delete' => 'Löschen',
        'cancel' => 'Abbrechen',
        'remove' => 'Entfernen',
        'yes' => 'Ja',
        'no' => 'Nein',
        'enable' => 'Aktivieren',
        'disable' => 'Deaktivieren',
        'configure' => 'Konfigurieren',
        'test' => 'Verbindung testen',
        'sync' => 'Jetzt synchronisieren',
        'reset' => 'Zurücksetzen',
    ],

    // === Daten-Grid Nachrichten ===
    'grid' => [
        'pagination' => 'Zeige {from} bis {to} von {total} Ergebnissen',
        'page' => 'Seite',
        'page_count' => 'Seitenzahl',
        'loading' => 'Daten werden geladen, bitte warten...',
        'no_data' => 'Keine Daten gefunden',
        'connection_error' => 'Verbindungsfehler',
        'search' => 'Suche',
        'select_at_least_one' => 'Bitte wählen Sie mindestens eine Zeile aus',
        'select_only_one' => 'Bitte wählen Sie nur eine Zeile aus',
    ],

    // === Validierungsnachrichten ===
    'validation' => [
        'required' => 'Bitte lassen Sie das Feld %s nicht leer',
        'length' => '%s muss zwischen %s und %s Zeichen lang sein',
        'email' => 'Bitte geben Sie eine gültige E-Mail-Adresse im Feld %s ein',
        'unique' => 'Das eingegebene %s ist bereits im System registriert. Bitte geben Sie ein anderes %s an!',
        'inarray' => 'Das Feld %s kann nur die Werte \' %s \' akzeptieren',
        'module_name_required' => 'Modulname ist erforderlich',
        'target_module_required' => 'Zielmodul-Auswahl ist erforderlich',
        'invalid_integration_type' => 'Ungültiger Integrationstyp ausgewählt',
        'configuration_invalid' => 'Modulkonfiguration ist ungültig',
    ],

    // === Zugriffskontrolle (ACL) ===
    'permissions' => [
        'title' => 'Dateimodul-Verwaltungsberechtigungen',
        'add' => 'Kann Dateimodul hinzufügen',
        'edit' => 'Kann Dateimodul bearbeiten',
        'delete' => 'Kann Dateimodul löschen',
        'list' => 'Kann Dateimodul-Verwaltung ansehen',
        'configure' => 'Kann Dateimodule konfigurieren',
        'test' => 'Kann Modulverbindungen testen',
        'sync' => 'Kann Module synchronisieren',
        'edit_others' => 'Kann Dateimodul-Datensätze von anderen bearbeiten',
        'delete_others' => 'Kann Dateimodul-Datensätze von anderen löschen',
    ],

    // === Berechtigungsfehler ===
    'permission_errors' => [
        'edit_others' => 'Sie können keine Dateimodul-Datensätze von anderen bearbeiten',
        'delete_others' => 'Sie können keine Dateimodul-Datensätze von anderen löschen',
        'add' => 'Sie haben keine Berechtigung, Dateimodule hinzuzufügen',
        'edit' => 'Sie haben keine Berechtigung, Dateimodule zu bearbeiten',
        'list' => 'Sie haben keine Berechtigung, die Dateimodul-Verwaltung anzusehen',
        'delete' => 'Sie haben keine Berechtigung, Dateimodule zu löschen',
        'configure' => 'Sie haben keine Berechtigung, Dateimodule zu konfigurieren',
        'test' => 'Sie haben keine Berechtigung, Modulverbindungen zu testen',
        'sync' => 'Sie haben keine Berechtigung, Module zu synchronisieren',
    ],

    // === Admin-Panel Konfiguration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'Dateimodulname',
            'mod_name' => 'Modulname',
            'mod_key' => 'Modulschlüssel (ID)',
            'mod_value' => 'Modulwert (Name)',
            'status' => 'Status',
            'target_module' => 'Zielmodul',
            'integration_type' => 'Integrationstyp',
            'created_date' => 'Erstellungsdatum',
            'modified_date' => 'Änderungsdatum',
            'actions' => 'Aktionen',
        ],
    ],

    // === Modultypen ===
    'module_types' => [
        'file_upload' => 'Datei-Upload-Modul',
        'file_manager' => 'Dateiverwaltungs-Modul',
        'image_gallery' => 'Bildergalerie-Modul',
        'document_library' => 'Dokumentenbibliotheks-Modul',
        'media_player' => 'Media-Player-Modul',
        'file_browser' => 'Datei-Browser-Modul',
        'custom_integration' => 'Benutzerdefinierte Integration',
    ],

    // === Integrationstypen ===
    'integration_types' => [
        'standalone' => 'Eigenständig',
        'embedded' => 'Eingebettet',
        'popup' => 'Popup-Modal',
        'overlay' => 'Overlay',
        'iframe' => 'iFrame',
        'api_based' => 'API-basiert',
        'webhook' => 'Webhook',
    ],

    // === Modulstatus ===
    'status' => [
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'testing' => 'Testen',
        'error' => 'Fehler',
        'maintenance' => 'Wartung',
        'deprecated' => 'Veraltet',
    ],

    // === Synchronisierungsfunktionen ===
    'synchronization' => [
        'auto_sync' => 'Automatische Synchronisierung',
        'sync_now' => 'Jetzt synchronisieren',
        'sync_status' => 'Sync-Status',
        'last_sync' => 'Letzte Synchronisierung',
        'sync_in_progress' => 'Synchronisierung läuft...',
        'sync_completed' => 'Synchronisierung erfolgreich abgeschlossen',
        'sync_failed' => 'Synchronisierung fehlgeschlagen',
        'sync_settings' => 'Sync-Einstellungen',
        'sync_interval' => 'Sync-Intervall',
        'sync_method' => 'Sync-Methode',
    ],

    // === Testfunktionen ===
    'testing' => [
        'test_connection' => 'Verbindung testen',
        'test_upload' => 'Upload testen',
        'test_download' => 'Download testen',
        'test_permissions' => 'Berechtigungen testen',
        'test_configuration' => 'Konfiguration testen',
        'test_results' => 'Testergebnisse',
        'test_passed' => 'Test bestanden',
        'test_failed' => 'Test fehlgeschlagen',
        'test_in_progress' => 'Test läuft...',
        'detailed_report' => 'Detaillierter Testbericht',
    ],

    // === Erweiterte Konfiguration ===
    'advanced_config' => [
        'file_size_limit' => 'Dateigrößenbeschränkung',
        'allowed_extensions' => 'Erlaubte Erweiterungen',
        'upload_path' => 'Upload-Pfad',
        'temp_path' => 'Temporärer Pfad',
        'backup_enabled' => 'Backup aktiviert',
        'backup_interval' => 'Backup-Intervall',
        'compression_enabled' => 'Komprimierung aktiviert',
        'encryption_enabled' => 'Verschlüsselung aktiviert',
        'virus_scanning' => 'Virenscan',
        'logging_enabled' => 'Protokollierung aktiviert',
    ],

    // === Sicherheitseinstellungen ===
    'security' => [
        'access_control' => 'Zugriffskontrolle',
        'user_permissions' => 'Benutzerberechtigungen',
        'role_permissions' => 'Rollenberechtigungen',
        'ip_restrictions' => 'IP-Beschränkungen',
        'rate_limiting' => 'Ratenbegrenzung',
        'audit_trail' => 'Audit-Trail',
        'secure_upload' => 'Sicherer Upload',
        'file_validation' => 'Dateivalidierung',
        'security_headers' => 'Sicherheits-Header',
    ],

    // === Erfolgsmeldungen ===
    'success_messages' => [
        'operation_completed' => 'Vorgang erfolgreich abgeschlossen!',
        'changes_saved' => 'Änderungen erfolgreich gespeichert!',
        'module_enabled' => 'Dateimodul erfolgreich aktiviert!',
        'module_disabled' => 'Dateimodul erfolgreich deaktiviert!',
        'configuration_saved' => 'Konfiguration erfolgreich gespeichert!',
        'sync_completed' => 'Synchronisierung erfolgreich abgeschlossen!',
        'test_passed' => 'Alle Tests erfolgreich bestanden!',
    ],

    // === Fehlermeldungen ===
    'error_messages' => [
        'general' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.',
        'network' => 'Netzwerkfehler. Bitte überprüfen Sie Ihre Internetverbindung.',
        'server' => 'Serverfehler. Bitte versuchen Sie es später erneut.',
        'configuration_error' => 'Konfigurationsfehler aufgetreten',
        'connection_failed' => 'Verbindung fehlgeschlagen',
        'sync_failed' => 'Synchronisierung fehlgeschlagen',
        'test_failed' => 'Test fehlgeschlagen',
        'module_not_found' => 'Dateimodul nicht gefunden',
        'invalid_configuration' => 'Ungültige Konfiguration',
    ],

    // === Hilfetext ===
    'help' => [
        'module_intro' => 'Dateimodule bieten Dateiverwaltungsfunktionen für andere Systemkomponenten',
        'configuration_help' => 'Konfigurieren Sie die Moduleinstellungen, um eine ordnungsgemäße Integration zu ermöglichen',
        'security_help' => 'Konfigurieren Sie Sicherheitseinstellungen, um Ihre Dateien und Ihr System zu schützen',
        'testing_help' => 'Testen Sie Ihre Modulkonfiguration, um sicherzustellen, dass alles korrekt funktioniert',
        'sync_help' => 'Synchronisieren Sie Module, um die Datenkonsistenz im gesamten System zu gewährleisten',
    ],

    // === Leere Zustände ===
    'empty_states' => [
        'no_modules' => 'Noch keine Dateimodule konfiguriert',
        'no_results' => 'Keine Module gefunden, die Ihren Suchkriterien entsprechen',
        'no_sync_data' => 'Keine Synchronisierungsdaten verfügbar',
        'add_first_module' => 'Fügen Sie Ihr erstes Dateimodul hinzu, um loszulegen',
    ],

    // === Ladezustände ===
    'loading_states' => [
        'loading_modules' => 'Module werden geladen...',
        'testing_connection' => 'Verbindung wird getestet...',
        'synchronizing' => 'Synchronisierung läuft...',
        'configuring' => 'Konfiguration läuft...',
        'processing_request' => 'Anfrage wird verarbeitet...',
    ],

    // === AJAX Antwortnachrichten ===
    'ajax' => [
        'success' => 'Erfolg',
        'error' => 'Fehler',
    ],

];
