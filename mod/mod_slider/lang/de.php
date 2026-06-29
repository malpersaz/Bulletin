<?php
$lang['mod_slider']= [
  'add' => 'Slider hinzufügen',
  'edit' => 'Slider bearbeiten',
  'list' => 'Slider-Verwaltung',
  'information' => 'Information!',
  'oke' => 'Vorgang erfolgreich!',
  'hata' => 'Ein Fehler ist aufgetreten!',
  'error' => 'Ein kritischer Fehler ist aufgetreten!',
  'remove_image' => 'Sind Sie sicher, dass Sie dieses Bild entfernen möchten?',
  'remove_image_oke' => 'Bild entfernt',
  'choose_wish' => 'Wählen Sie aus, falls gewünscht',
  'remove_record' => 'Sind Sie sicher, dass Sie diesen Datensatz löschen möchten?',
  'messages' => [
    'confirm_delete' => 'Sind Sie sicher, dass Sie dies löschen möchten?',
    'success_delete' => 'Datensatz erfolgreich gelöscht!',
  ],
  'message' => [
    'oke' => 
     [
      'add' => 'Slider hinzugefügt!',
      'edit' => 'Slider bearbeitet!',
    ],
    'error' => 
     [
      'add' => 'Slider konnte nicht hinzugefügt werden!',
      'edit' => 'Slider konnte nicht bearbeitet werden!',
    ],
    'information' => 
     [
      'moderate' => 'Doppelklicken Sie auf den Inhalt, den Sie bearbeiten möchten',
      'move' => 'Ziehen Sie den Inhalt per Drag & Drop, um die Reihenfolge zu ändern',
    ],
  ],
  'errors' => 
   [
    'record' => 'Der angegebene Datensatz konnte nicht gefunden werden!',
  ],
  'button' => [
    'ok' => 'OK',
    'cancel' => 'Abbrechen',
    'save' => 'Speichern',
    'yes' => 'Ja',
    'no' => 'Nein',
    'add' => 'Hinzufügen',
    'edit' => 'Bearbeiten',
    'yenile' => 'Aktualisieren',
    'delete' => 'Löschen',
    'gallery' => 'Bildergalerie',
    'vazgec' => 'Abbrechen',
    'kaldir' => 'Entfernen',
    'evet' => 'Ja',
    'hayir' => 'Nein',
  ],
  'grid' => 
   [
    0 => 'Anzeige von {from} bis {to} von insgesamt {total} Ergebnissen.',
    1 => 'Seite',
    2 => 'Seitenanzahl',
    3 => 'Daten werden geladen, bitte warten...',
    4 => 'Keine Daten gefunden',
    5 => 'Verbindungsfehler',
    6 => 'Suche',
    7 => 'Bitte wählen Sie mindestens eine Zeile aus',
    8 => 'Bitte wählen Sie nur eine Zeile aus',
    'publish' => 
     [
      'key' => 'Veröffentlichen',
      'value' => 'Sind Sie sicher, dass Sie diesen Slider veröffentlichen möchten?',
    ],
    'unpublish' => 
     [
      'key' => 'Veröffentlichung aufheben',
      'value' => 'Sind Sie sicher, dass Sie die Veröffentlichung dieses Sliders aufheben möchten?',
    ],
  ],
  'validator' => 
   [
    'required' => 'Bitte lassen Sie das Feld %s nicht leer',
    'length' => '%s muss zwischen %s und %s Zeichen lang sein',
    'email' => 'Bitte geben Sie eine gültige E-Mail-Adresse in das Feld %s ein.',
    'unique' => 'Das eingegebene %s ist bereits registriert. Bitte wählen Sie ein anderes %s!',
    'inarray' => 'Das Feld %s kann nur die Werte \'%s\' annehmen',
  ],
  'roll' => 
   [
    'ad' => 'Slider-Verwaltungs-Rollen',
    'add' => 'Kann Slider hinzufügen',
    'edit' => 'Kann Slider bearbeiten',
    'delete' => 'Kann Slider löschen',
    'list' => 'Kann Slider-Verwaltung einsehen',
    'change_status' => 'Soll der hinzugefügte Slider sofort veröffentlicht werden?',
    'edit_status' => 'Soll der bearbeitete Slider sofort veröffentlicht werden?',
    'approval' => 'Kann Slider genehmigen, die auf Freigabe warten?',
    'gallery' => 'Kann Galerie zum Inhalt hinzufügen?',
    'moderate_edit' => 'Kann Slider-Einträge bearbeiten, die nicht dem Benutzer gehören',
    'moderate_delete' => 'Kann Slider-Einträge löschen, die nicht dem Benutzer gehören',
  ],
  'role_errors' => 
   [
    'moderate_edit' => 'Sie können keine Slider-Einträge bearbeiten, die Ihnen nicht gehören',
    'moderate_delete' => 'Sie können keine Slider-Einträge löschen, die Ihnen nicht gehören',
    'add' => 'Sie haben keine Berechtigung, einen Slider hinzuzufügen',
    'edit' => 'Sie haben keine Berechtigung, einen Slider zu bearbeiten',
    'list' => 'Sie haben keine Berechtigung, die Slider-Verwaltung aufzurufen',
    'delete' => 'Sie haben keine Berechtigung, einen Slider zu löschen',
  ],
  'admin' => 
   [
    'columns' => 
     [
      'id' => 'ID',
      'lang' => 'Sprache',
      'name' => 'Slider-Name',
      'url' => 'Slider-URL',
      'status' => 'Veröffentlichen?',
      'target' => 'Bei Klick',
      'created_at' => 'Datum',
      'description' => 'Beschreibung',
      'image' => 'Bild',
      'image_desktop' => 'Desktop - 16:9',
      'image_mobile' => 'Mobil - 3:4',
      'acl_id' => 'Wer kann diesen Inhalt sehen? ( <a href=\'mod_acl/list/\' target=\'_blank\'>ACL - Zugriffskontrolle</a> )',
    ],
    'radio' => 
     [
      'publish_yes' => 'Ja',
      'publish_no' => 'Nein',
      'target_e' => 'Im gleichen Fenster öffnen',
      'target_h' => 'In neuem Fenster öffnen',
    ],
  ],
  'datatable' => ['emptyTable' => 'Keine Datensätze gefunden', 'loadingRecords' => 'Wird geladen...'],
  'wizard' => ['label' => ['upload_text' => 'Klicken zum Hochladen eines Bildes']],
  'cropper' => [
    'title_desk' => 'Desktop - 16:9',
    'title_mob' => 'Mobil - 3:4',
    'error_plugin' => 'Cropper.js oder jQuery-Plugin nicht geladen.',
    'error_title' => 'Fehler',
    'finish' => 'Abschluss',
    'error_canvas' => 'Fehler beim Zuschneiden (Canvas).',
  ],
  'acl_all' => 'Öffentlich',
  'empty' => ['title' => 'Keine Datensätze gefunden', 'no_data' => 'Es wurden noch keine Slider hinzugefügt.', 'no_search_results' => 'Keine Slider gefunden, die Ihren Suchkriterien entsprechen.'],
  'search_placeholder' => 'Slider suchen...',
  'modules' => [
    'news' => 'Nachrichten',
    'publication' => 'Publikation',
    'content' => 'Inhalt',
    'product' => 'Produkt',
  ],
];
