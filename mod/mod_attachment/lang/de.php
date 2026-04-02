<?php

if (!defined('AKM_LOADED')) {
    die('Direkter Zugriff auf diese Datei ist nicht erlaubt.');
}

$lang['mod_attachment'] = [];

$lang['mod_attachment']['admin'] = [
    1 => 'Datei-Upload-Bereich',
    2 => 'Wählen Sie eine Datei aus, indem Sie unten auf die Schaltfläche Durchsuchen klicken!',
    3 => 'Sie können mehrere Dateien auswählen und hochladen, indem Sie die <b>STRG</b>-Taste auf Ihrer Tastatur gedrückt halten.',
    4 => 'Wird hochgeladen...',
    5 => 'Ansehen',
    6 => 'In der Upload-Warteschlange warten...',
    7 => 'Datei',
    8 => 'Anzahl der ausgewählten Dateien',
    9 => 'Anzahl der in der Warteschlange wartenden Dateien',
    10 => 'Die von Ihnen ausgewählte Datei \'+file.name+\' ist zu groß.',
    11 => 'Durchsuchen',
    12 => 'Zurück',
    14 => 'Dateiverwaltung',
    17 => 'Sind Sie sicher, dass Sie diese Datei löschen möchten?',
    18 => 'Datei löschen',
    19 => 'Ja',
    20 => 'Abbrechen',
    21 => 'Speichern',
    22 => 'Datei-Upload-Bereich für %s Inhalt',
    23 => 'Dateiname',
    24 => 'Doppelklicken Sie auf die Datei, die Sie umbenennen möchten.',
    25 => 'Keine Dateien gefunden!',
    26 => '--- Alle Dateien ---',
    27 => 'Keine registrierten Dateien gefunden!',
    28 => 'Kategorie auswählen und Dateien hinzufügen',
    29 => 'Datei herunterladen',
    30 => 'Dateiname bearbeiten',
    31 => 'Datei entfernen',
    35 => '\'Datei %s wurde auf den Server hochgeladen!\'',
    36 => 'Datei gelöscht',
    37 => 'Dateien zugehörig zu "%s"',
    38 => 'Datei hinzufügen',
    39 => 'Datei bearbeitet',
    40 => 'Dateiname "+original+" wurde in "+text+" geändert',
    41 => 'Datei-Upload fehlgeschlagen.',
    42 => 'Statistik',
    43 => 'Neu',
    44 => 'Panel',
    'warning' => 'Warnung!',
    'info' => 'Info!',
    'message' => 'Nachricht!',
    'help' => 'Hilfe!'
];

$lang['mod_attachment']['hata'] = [
    1 => 'Dateiquelle konnte nicht abgerufen werden',
    2 => 'Die angegebene Datei ist keine gültige Datei',
    3 => 'Bitte geben Sie die Datei an, in die geschrieben werden soll',
    4 => 'Nur png, jpg, jpeg, gif, bmp Dateien können als Ausgabe generiert werden',
    5 => 'Datei konnte nicht erstellt werden',
    6 => 'Dateiausgabe konnte nicht generiert werden'
];

$lang['mod_attachment']['errors'] = [
    'missing_module_name' => 'Modulinformationen fehlen für den Dateivorgang.',
    'missing_record_id' => 'Datensatzinformationen fehlen für den Dateivorgang.',
    'invalid_module' => 'Für diesen Datensatz ist kein Dateimodul definiert.',
    'module_file_permission_missing' => 'Für dieses Modul ist keine Dateiberechtigung definiert.',
    'invalid_record' => 'Der zugehörige Datensatz konnte nicht gefunden werden.',
    'invalid_file' => 'Der zugehörige Dateidatensatz konnte nicht gefunden werden.',
    'invalid_record_title_field' => 'Das Feld mod_value ist für dieses Modul ungültig. Korrigieren Sie das Anzeigefeld manuell.',
    'upload_field_missing' => 'Informationen zum Upload-Feld fehlen.',
    'uploaded_file_missing' => 'Es wurde keine Datei zum Hochladen übermittelt.',
];

$lang['mod_attachment']['roll'] = [
    'ad' => 'Galerierollen',
    'add' => 'Kann Dateien hinzufügen.',
    'edit' => 'Kann hochgeladene Dateien bearbeiten',
    'delete' => 'Kann hochgeladene Dateien löschen',
    'list' => 'Kann Dateiverwaltung ansehen',
    'moderate' => 'Kann Dateien von anderen verwalten'
];

$lang['mod_attachment']['module'] = [
    'description' => 'Dateimodul',
];

$lang['mod_attachment']['ajax'] = [
    'success' => 'Erfolg',
    'error' => 'Fehler',
];

$lang['mod_attachment']['role_errors'] = [
    1 => 'Sie haben keine Berechtigung, eine Datei zu bearbeiten, die jemand anderem gehört',
    2 => 'Sie haben keine Berechtigung, eine Datei zu löschen, die jemand anderem gehört.',
    'add' => 'Sie haben keine Berechtigung, Dateien hinzuzufügen',
    'delete' => 'Sie haben keine Berechtigung, Dateien zu löschen',
    'edit' => 'Sie haben keine Berechtigung, Dateien zu bearbeiten',
    'list' => 'Sie haben keine Berechtigung, die Dateiverwaltung anzusehen'
];
