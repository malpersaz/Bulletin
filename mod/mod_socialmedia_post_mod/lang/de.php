<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_socialmedia_post_mod'] = [
    'named' => 'Social-Post-Module',
    'list' => 'Social-Post-Modulliste',
    'add' => 'Social-Post-Modul hinzufügen',
    'edit' => 'Social-Post-Modul bearbeiten',
    'information' => 'Information',
    'success' => 'Erfolgreich',
    'error' => 'Fehler aufgetreten',
    'remove_record' => 'Möchten Sie diesen Datensatz wirklich löschen?',
    'message' => [
        'success' => [
            'add' => 'Social-Post-Modul erfolgreich hinzugefügt!',
            'edit' => 'Social-Post-Modul erfolgreich aktualisiert!',
            'delete' => 'Social-Post-Modul erfolgreich gelöscht!',
        ],
        'error' => [
            'add' => 'Social-Post-Modul konnte nicht hinzugefügt werden!',
            'edit' => 'Social-Post-Modul konnte nicht aktualisiert werden!',
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
        'unique' => 'Der eingegebene Wert %s ist bereits registriert. Bitte geben Sie einen anderen %s ein!',
    ],
    'role' => [
        'name' => 'Berechtigungen für Social-Post-Module',
        'add' => 'Kann Social-Post-Module hinzufügen',
        'edit' => 'Kann Social-Post-Module bearbeiten',
        'delete' => 'Kann Social-Post-Module löschen',
        'list' => 'Kann Social-Post-Module anzeigen',
        'moderate_edit' => 'Kann Social-Post-Module anderer Benutzer bearbeiten',
        'moderate_delete' => 'Kann Social-Post-Module anderer Benutzer löschen',
    ],
    'role_errors' => [
        'moderate_edit' => 'Sie können Datensätze anderer Benutzer nicht bearbeiten',
        'moderate_delete' => 'Sie können Datensätze anderer Benutzer nicht löschen',
        'add' => 'Sie sind nicht berechtigt, Social-Post-Module hinzuzufügen',
        'edit' => 'Sie sind nicht berechtigt, Social-Post-Module zu bearbeiten',
        'list' => 'Sie sind nicht berechtigt, Social-Post-Module anzuzeigen',
        'delete' => 'Sie sind nicht berechtigt, Social-Post-Module zu löschen',
    ],
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'Modulname',
            'mod_name' => 'Technischer Modulname',
            'mod_key' => 'Modulschlüssel (ID)',
            'mod_value' => 'Modulwert (Titel)',
        ],
    ],
];
