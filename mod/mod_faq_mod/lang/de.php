<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_faq_mod'] = [
    'list' => 'FAQ-Modulliste',
    'add' => 'FAQ-Modul hinzufügen',
    'edit' => 'FAQ-Modul bearbeiten',
    'information' => 'Information',
    'success' => 'Erfolg',
    'error' => 'Fehler aufgetreten',
    'remove_record' => 'Sind Sie sicher, dass Sie diesen Datensatz löschen möchten?',
    'message' => [
        'success' => [
            'add' => 'FAQ-Modul erfolgreich hinzugefügt!',
            'edit' => 'FAQ-Modul erfolgreich aktualisiert!',
            'delete' => 'FAQ-Modul erfolgreich gelöscht!',
        ],
        'error' => [
            'add' => 'FAQ-Modul konnte nicht hinzugefügt werden!',
            'edit' => 'FAQ-Modul konnte nicht aktualisiert werden!',
        ],
    ],
    'errors' => [
        'record' => 'Der angegebene Datensatz wurde nicht gefunden!',
    ],
    'button' => [
        'add' => 'Hinzufügen',
        'edit' => 'Bearbeiten',
        'delete' => 'Löschen',
        'refresh' => 'Aktualisieren',
        'cancel' => 'Abbrechen',
        'remove' => 'Entfernen',
        'yes' => 'Ja',
        'no' => 'Nein',
    ],
    'validator' => [
        'required' => 'Bitte lassen Sie das Feld %s nicht leer',
        'length' => '%s muss zwischen %s und %s Zeichen lang sein',
        'unique' => 'Das eingegebene %s ist bereits im System registriert. Bitte geben Sie ein anderes %s an!',
    ],
    'role' => [
        'name' => 'Rollen der FAQ-Modulverwaltung',
        'add' => 'Kann FAQ-Modul hinzufügen',
        'edit' => 'Kann FAQ-Modul bearbeiten',
        'delete' => 'Kann FAQ-Modul löschen',
        'list' => 'Kann FAQ-Modulverwaltung anzeigen',
        'moderate_edit' => 'Kann FAQ-Moduldatensätze anderer bearbeiten',
        'moderate_delete' => 'Kann FAQ-Moduldatensätze anderer löschen',
    ],
    'role_errors' => [
        'moderate_edit' => 'Sie können keine FAQ-Moduldatensätze anderer bearbeiten',
        'moderate_delete' => 'Sie können keine FAQ-Moduldatensätze anderer löschen',
        'add' => 'Keine Berechtigung zum Hinzufügen von FAQ-Modulen',
        'edit' => 'Keine Berechtigung zum Bearbeiten von FAQ-Modulen',
        'list' => 'Keine Berechtigung zum Anzeigen der FAQ-Modulverwaltung',
        'delete' => 'Keine Berechtigung zum Löschen von FAQ-Modulen',
    ],
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'FAQ-Modulname',
            'mod_name' => 'Modulname',
            'mod_key' => 'Modulschlüssel (ID)',
            'mod_value' => 'Modulwert (Titel)',
        ],
    ],
];
