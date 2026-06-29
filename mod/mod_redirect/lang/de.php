<?php

$lang['mod_redirect'] = [
    'list' => 'Weiterleitungsverwaltung',
    'add' => 'Neue Weiterleitung hinzufügen',
    'edit' => 'Weiterleitung bearbeiten',
    'information' => 'Information',
    'oke' => 'Erfolgreich',
    'hata' => 'Fehler aufgetreten',
    'error' => 'Kritischer Fehler',
    'remove_record' => 'Sind Sie sicher, dass Sie diese Weiterleitung löschen möchten?',
    'message' => [
        'oke' => [
            'add' => 'Weiterleitung erfolgreich hinzugefügt!',
            'edit' => 'Weiterleitung erfolgreich aktualisiert!',
            'delete' => 'Weiterleitung erfolgreich gelöscht!',
        ],
        'error' => [
            'add' => 'Weiterleitung konnte nicht hinzugefügt werden!',
            'edit' => 'Weiterleitung konnte nicht aktualisiert werden!',
        ],
    ],
    'errors' => [
        'record' => 'Die angegebene Weiterleitung wurde nicht gefunden!',
    ],
    'button' => [
        'add' => 'Hinzufügen',
        'edit' => 'Bearbeiten',
        'delete' => 'Löschen',
        'cancel' => 'Abbrechen',
        'yes' => 'Ja',
        'no' => 'Nein',
    ],
    'validator' => [
        'required' => 'Bitte lassen Sie das Feld %s nicht leer',
        'length' => 'Die Länge von %s muss zwischen %s und %s Zeichen liegen',
        'unique_old_url' => 'Diese Quell-URL ist bereits im System registriert!',
        'inarray' => 'Das Feld %s darf nur \'%s\' Werte annehmen',
    ],
    'roll' => [
        'ad' => 'Rollen der Weiterleitungsverwaltung',
        'add' => 'Kann neue Weiterleitungen hinzufügen',
        'edit' => 'Kann Weiterleitungen bearbeiten',
        'delete' => 'Kann Weiterleitungen löschen',
        'list' => 'Kann die Weiterleitungsverwaltung anzeigen',
    ],
    'role_errors' => [
        'add' => 'Sie haben keine Berechtigung, Weiterleitungen hinzuzufügen',
        'edit' => 'Sie haben keine Berechtigung, Weiterleitungen zu bearbeiten',
        'list' => 'Sie haben keine Berechtigung, die Weiterleitungsverwaltung anzuzeigen',
        'delete' => 'Sie haben keine Berechtigung, Weiterleitungen zu löschen',
    ],
    'admin' => [
        'search_placeholder' => 'Weiterleitungen suchen...',
        'intro_add' => 'Geben Sie die Quell-URL, die Ziel-URL und den HTTP-Statuscode ein, um eine Weiterleitung zu erstellen.',
        'intro_edit' => 'Ändern Sie die Quell-URL, die Ziel-URL oder den HTTP-Statuscode dieser Weiterleitung.',
        'empty_description' => 'Keine registrierten Weiterleitungen gefunden.',
        'dashboard' => [
            'active_redirects' => 'Aktive Weiterleitungen',
            'standard_type' => 'Standardtyp',
        ],
        'columns' => [
            'id' => 'ID',
            'old_url' => 'Quell-URL (Alt)',
            'new_url' => 'Ziel-URL (Neu)',
            'status_code' => 'Statuscode',
            'created_at' => 'Erstellungsdatum',
        ],
    ],
];
