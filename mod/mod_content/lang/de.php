<?php
/**
 * Content Management Module - German Language File
 *
 * @package AKM Business
 * @module  mod_content
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Content Management Module Language Translations
 */
$lang['mod_content'] = [

    // === Basic Actions ===
    'add' => 'Inhalt hinzufügen',
    'edit' => 'Inhalt bearbeiten',
    'list' => 'Inhaltsliste',
    'information' => 'Information!',
    'oke' => 'Vorgang erfolgreich!',
    'hata' => 'Ein Fehler ist aufgetreten!',
    'error' => 'Ein kritischer Fehler ist aufgetreten!',
    'remove_image' => 'Sind Sie sicher, dass Sie dieses Bild entfernen möchten?',
    'remove_image_oke' => 'Bild entfernt',
    'choose_wish' => 'Bitte wählen Sie aus, falls gewünscht',
    'remove_record' => 'Sind Sie sicher, dass Sie diesen Datensatz löschen möchten?',
    'delete_confirm' => 'Sind Sie sicher, dass Sie diesen Inhalt löschen möchten?',
    'quick_unpublish_confirm' => 'Möchten Sie die Veröffentlichung dieses Inhalts aufheben?',
    'quick_publish_confirm' => 'Möchten Sie diesen Inhalt veröffentlichen?',
    'resim_ekle' => 'Bild hinzufügen',
    'resim_yukle_degistir' => 'Bild hochladen/ändern',
    'lokal' => 'Lokal',
    'embed' => 'Einbetten',

    // === Filter Menu ===
    'filter' => [
        'title' => 'Filtern & Sortieren',
        'close' => 'Schließen',
        'sort_label' => 'Sortieren',
        'newest' => 'Neueste',
        'oldest' => 'Älteste',
        'title_az' => 'Titel (A-Z)',
        'title_za' => 'Titel (Z-A)',
        'view_count' => 'Aufrufe',
        'limit_label' => 'Anzeigelimit',
        'lang_label' => 'Sprachauswahl',
        'selected_lang' => 'Ausgewählte Sprache',
        'date_label' => 'Zeitraum',
        'start_date' => 'Start',
        'end_date' => 'Ende',
        'clear' => 'Leeren',
        'apply' => 'Ergebnisse anzeigen',
        'select_lang' => 'Sprache wählen',
        'all_langs' => 'Alle Sprachen',
        'select_label' => 'Auswählen',
    ],

    // === Messages ===
    'message' => [
        'oke' => [
            'add' => 'Inhalt hinzugefügt!',
            'edit' => 'Inhalt aktualisiert!',
        ],
        'error' => [
            'add' => 'Inhalt konnte nicht hinzugefügt werden!',
            'edit' => 'Inhalt konnte nicht aktualisiert werden!',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'Der angegebene Datensatz konnte nicht gefunden werden!',
    ],

    'wizard_loading' => 'Assistent wird geladen...',

    // === Buttons ===
    'button' => [
        'next' => 'Weiter',
        'prev' => 'Zurück',
        'send' => 'Senden',
        'add' => 'Hinzufügen',
        'edit' => 'Bearbeiten',
        'yenile' => 'Aktualisieren',
        'delete' => 'Löschen',
        'gallery' => 'Bildergalerie',
        'file' => 'Dateimanager',
        'vazgec' => 'Abbrechen',
        'kaldir' => 'Entfernen',
        'evet' => 'Ja',
        'hayir' => 'Nein',
    ],

    // === Data Grid Messages ===
    'grid' => [
        0 => 'Anzeige {from} bis {to} von insgesamt {total} Ergebnissen.',
        1 => 'Seite',
        2 => 'Seitenanzahl',
        3 => 'Daten werden geladen, bitte warten...',
        4 => 'Keine Daten gefunden',
        5 => 'Verbindungsfehler',
        6 => 'Suche',
        7 => 'Bitte wählen Sie mindestens eine Zeile aus',
        8 => 'Bitte wählen Sie nur eine Zeile aus',
        'approve' => [
            'key' => 'Veröffentlichen',
            'value' => 'Sind Sie sicher, dass Sie diesen Inhalt veröffentlichen möchten?',
        ],
        'unapprove' => [
            'key' => 'Veröffentlichung aufheben',
            'value' => 'Sind Sie sicher, dass Sie die Veröffentlichung dieses Inhalts aufheben möchten?',
        ],
        'load_more' => 'Mehr laden',
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Bitte lassen Sie das Feld %s nicht leer',
        'length' => '%s muss zwischen %s und %s Zeichen lang sein',
        'email' => 'Bitte geben Sie eine gültige E-Mail-Adresse für das Feld %s ein.',
        'unique' => 'Das eingegebene %s ist bereits im System registriert. Bitte wählen Sie ein anderes %s!',
        'inarray' => 'Das Feld %s kann nur die Werte \'%s\' annehmen',
        'title_length' => 'Sie müssen einen Titel mit mindestens 1 und höchstens 70 Zeichen eingeben.',
        'title_exists' => 'Dieser Titel existiert bereits, bitte ändern Sie ihn',
        'title_unique' => 'Ihr Titel ist einzigartig',
        'deck_length' => 'Bitte geben Sie einen Einleitungstext von mindestens 110 bis maximal 280 Zeichen ein.',
    ],

    // === Permission Roles ===
    'roll' => [
        'name' => 'Inhaltsverwaltungs-Rollen',
        'add' => 'Kann Inhalt hinzufügen',
        'edit' => 'Kann Inhalt bearbeiten',
        'delete' => 'Kann Inhalt löschen',
        'list' => 'Kann Inhaltsverwaltung einsehen',
        'publish' => 'Kann Inhalt veröffentlichen',
        'unpublish' => 'Kann Veröffentlichung aufheben',
        'change_status' => 'Kann Veröffentlichungsstatus ändern',
        'edit_status' => 'Kann detaillierten Status ändern',
        'moderate_edit' => 'Kann Inhalte anderer bearbeiten',
        'moderate_delete' => 'Kann Inhalte anderer löschen',
        'gallery' => 'Kann Galerie verwalten',
        'file' => 'Kann Dateien verwalten',
        'approval' => 'Kann Inhalt genehmigen',
        'video_download' => 'Kann Videos herunterladen',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'add' => 'Sie haben keine Berechtigung, Inhalt hinzuzufügen',
        'edit' => 'Sie haben keine Berechtigung, Inhalt zu bearbeiten',
        'list' => 'Sie haben keine Berechtigung, die Inhaltsverwaltung aufzurufen',
        'delete' => 'Sie haben keine Berechtigung, Inhalt zu löschen',
        'approval' => 'Sie haben keine Berechtigung, Inhalte zu genehmigen',
        'moderate_edit' => 'Sie können keine Einträge anderer Benutzer bearbeiten',
        'moderate_delete' => 'Sie können keine Einträge anderer Benutzer löschen',
        'gallery' => 'Sie haben keine Berechtigung, die Galerie zu verwalten',
        'file' => 'Sie haben keine Berechtigung, den Dateimanager zu verwalten',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Sprache',
            'name' => 'Titel',
            'category_id' => 'Kategorie',
            'created_at' => 'Erstellt am',
            'updated_at' => 'Bearbeitet am',
            'content' => 'Inhalt',
            'summary' => 'Einleitung',
            'seo' => 'SEO URL',
            'title' => 'Seitentitel',
            'keywords' => 'Schlüsselwörter',
            'description' => 'Beschreibung',
            'status' => 'Veröffentlichungsstatus',
            'deck' => 'Hervorgehoben',
            'image' => 'Bild',
            'gallery_id' => 'Galerie',
            'file_id' => 'Datei',
            'acl_id' => 'Zugriffskontrolle',
            'video' => 'Videogalerie',
        ],
        'radio' => [
            'publish_yes' => 'Veröffentlicht',
            'publish_no' => 'Nur speichern',
            'deck_yes' => 'Hervorgehoben',
            'deck_no' => 'Nicht hervorgehoben',
        ],
    ],

    // === UI Elements ===
    'ui' => [
        'kayit_yonetimi' => 'Datensatzverwaltung',
        'islem' => 'Vorgang',
        'uygula' => 'Anwenden',
        'islem_column' => 'Aktion',
        'select' => 'Auswählen',
        'burdan' => 'Von',
        'buraya' => 'Bis',
        'ara' => 'Suche',
        'sifirla' => 'Zurücksetzen',
        'approval' => 'Bestätigen',
        'isleniyor' => 'Verarbeitung',
        'record_selected' => 'Datensatz ausgewählt',
        'islem_gerceklestirilemedi' => 'Vorgang konnte nicht ausgeführt werden',
        'islem_secin' => 'Bitte wählen Sie einen Vorgang aus',
        'all' => 'Alle',
        'pdf' => 'PDF',
        'csv' => 'CSV',
        'excel' => 'Excel',
        'yazdir' => 'Drucken',
        'yazdir_bilgi' => 'Druckinfo',
        'olusturan' => 'Erstellt von',
        'kopyala' => 'Kopieren',
        'default_sort' => 'Neueste',
        'default_lang' => 'DE',
        'date_locale' => 'de-DE',
        'all' => 'Alle',
        'filter_cleared' => 'Filter gelöscht und auf Standardwerte zurückgesetzt.',
        'filter_applied' => 'Filter angewendet',
        'sort_label' => 'Sortierung',
        'limit_label' => 'Anzahl',
        'lang_label' => 'Sprache',
    ],

    // === DataTable Configuration ===
    'datatable' => [
        'aria' => [
            'sortAscending' => ': aktivieren, um Spalte aufsteigend zu sortieren',
            'sortDescending' => ': aktivieren, um Spalte absteigend zu sortieren',
        ],
        'emptyTable' => 'Keine Daten in der Tabelle verfügbar',
        'info' => 'Anzeige von _START_ bis _END_ von insgesamt _TOTAL_ Einträgen',
        'infoEmpty' => '0 bis 0 von 0 Einträgen angezeigt',
        'infoFiltered' => '(gefiltert aus insgesamt _MAX_ Einträgen)',
        'lengthMenu' => '_MENU_ Einträge anzeigen',
        'search' => 'Suche:',
        'zeroRecords' => 'Keine passenden Datensätze gefunden',
        'loadingRecords' => 'Wird geladen...',
        'processing' => 'Wird verarbeitet...',
        'paginate' => [
            'first' => 'Erste',
            'last' => 'Letzte',
            'next' => 'Nächste',
            'previous' => 'Vorherige',
        ],
    ],

    // === Tokenizing Configuration ===
    'tokenizing' => [
        'hintText' => 'Geben Sie einen Suchbegriff ein',
        'noResultsText' => 'Keine Ergebnisse',
        'searchingText' => 'Suche läuft...',
    ],

    // === AnyTime Picker ===
    'anytime' => [
        'labelDayOfMonth' => 'Tag des Monats',
        'labelHour' => 'Stunde',
        'labelMinute' => 'Minute',
        'labelMonth' => 'Monat',
        'labelTitle' => 'Datum und Zeit auswählen',
        'labelYear' => 'Jahr',
        'dayAbbreviations' => ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
        'dayNames' => ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],
        'monthAbbreviations' => ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
        'monthNames' => ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
    ],

    // === Status Badges ===
    'status_badge' => [
        'active' => 'Veröffentlicht',
        'draft' => 'Entwurf',
        'general' => 'Allgemein',
    ],

    // === Sheet Labels ===
    'sheet' => [
        'lang_title' => 'Sprachauswahl',
        'ok' => 'OK',
        'search_placeholder' => 'Sprache suchen (z.B. Deutsch, Englisch)...',
        'category_title' => 'Kategorie auswählen',
        'all' => 'Alle (Ungefiltert)',
    ],

    // === Wizard ===
    'wizard' => [
        'step' => [
            'language' => 'Sprachauswahl',
            'category' => 'Kategorie',
            'title' => 'Titel',
            'date' => 'Veröffentlichungsdatum',
            'intro' => 'Einleitungstext (Teaser)',
            'content' => 'Inhaltsdetails',
            'image' => 'Bildauswahl',
            'finish' => 'Abschluss',
        ],
        'subtitle' => [
            'language' => 'Wählen Sie die Hauptsprache für den Inhalt.',
            'category' => 'Legen Sie die Kategorie für den Inhalt fest.',
            'title' => 'Geben Sie einen ansprechenden Titel ein. (Min: 1, Max: 70)',
            'date' => 'Datum und Uhrzeit der Veröffentlichung.',
            'intro' => 'Schreiben Sie eine kurze Zusammenfassung. (110 - 280 Zeichen)',
            'content' => 'Rich-Text-Editor.',
            'image' => 'Laden Sie ein Vorschaubild hoch.',
            'finish' => 'Letzte Prüfungen und Veröffentlichung.',
        ],
        'label' => [
            'content_title' => 'Inhaltstitel',
            'intro_text' => 'Einleitungstext',
            'upload_text' => 'Tippen zum Hochladen eines Bildes',
            'published' => 'Veröffentlicht',
            'published_desc' => 'Inhalt wird nach dem Speichern für jeden sichtbar sein.',
            'category_select' => 'Kategorie auswählen...',
            'change_image' => 'Bild ändern',
            'editor_placeholder' => 'Schreiben Sie hier Ihren Inhalt...',
        ],
        'image' => [
            'drop_title' => 'Ziehen Sie ein Bild hierher oder',
            'drop_description' => 'Laden Sie ein Bild hoch, fügen Sie es ein oder ziehen Sie es hierher.',
            'upload_link' => 'eine Datei hochladen',
            'link_separator' => 'ODER',
            'link_placeholder' => 'Bildlink einfügen',
            'link_action' => 'Importieren',
            'hint_loading' => 'Bild wird importiert...',
            'hint_invalid_type' => 'Hier werden nur Bilddateien unterstützt.',
            'hint_no_drop_data' => 'Im abgelegten Inhalt wurde kein brauchbares Bild gefunden.',
            'hint_remote_failed' => 'Das Bild konnte nicht aus dieser Quelle importiert werden.',
        ],
        'messages' => [
            'success_sim' => 'Inhalt erfolgreich erstellt! (Simulation)',
            'video_success_sim' => 'Video erfolgreich hinzugefügt! (Simulation)',
            'video_embed_error' => 'Bitte geben Sie einen gültigen Einbettungscode (Iframe) ein.',
            'video_title_error' => 'Videoname muss mindestens 3 Zeichen lang sein.',
            'card_click' => 'Karte angeklickt: %s',
        ],
        'summary' => [
            'title' => 'Zusammenfassung',
            'selected_none' => 'Nichts ausgewählt',
        ],
        'button' => [
            'save' => 'Speichern',
            'next' => 'Weiter',
            'prev' => 'Zurück',
            'finish' => 'Speichern und abschließen',
        ]
    ],

    // === Context Menu ===
    'context_menu' => [
        'title' => 'Inhaltsaktionen',
        'edit' => 'Bearbeiten',
        'gallery' => 'Galerieaktionen',
        'files' => 'Dateiaktionen',
        'video' => 'Video zum Inhalt hinzufügen',
        'status' => 'Status ändern',
        'delete' => 'Löschen',
        'delete_error' => 'Löschen fehlgeschlagen!',
        'video_success_sim' => 'Video erfolgreich hinzugefügt (Simulation)',
        'video_embed_error' => 'Ungültiger Einbettungscode',
        'video_title_error' => 'Ungültiger Titel',
        'publish' => 'Veröffentlichen',
        'unpublish' => 'Veröffentlichung aufheben',
        'published_msg' => 'Veröffentlicht',
        'unpublished_msg' => 'Nicht veröffentlicht',
        'confirm' => 'Ja',
        'cancel' => 'Abbrechen',
        'edit_alert' => "BEARBEITUNGSMODUS:\nID: %s\nTitel: %s",
        'gallery_alert' => "GALERIEVERWALTUNG:\nID: %s\nTitel: %s\nBildergalerie wird geöffnet.",
        'files_alert' => "DATEIVERWALTUNG:\nID: %s\nTitel: %s\nDateivermaltung wird geöffnet.",
        'status_alert' => "STATUS AKTUALISIERT:\n%s",
        'delete_confirm' => 'Ja, löschen!',
        'delete_success' => 'Inhalt gelöscht!',
    ],

    'hero_slider_add' => 'Zum Slider hinzufügen',
    'hero_slider_remove' => 'Vom Slider entfernen',
    'hero_slider_added' => 'Zum Slider hinzugefügt.',
    'hero_slider_removed' => 'Vom Slider entfernt.',
    'hero_slider_error' => 'Slider-Update fehlgeschlagen.',
    'hero_slider_badge' => 'Slider',
    'hero_slider_unavailable' => 'Slider-Spalte ist noch nicht installiert.',

    'picker' => [
        'file' => 'Vorhandene Datei auswählen',
    ],

    'ajax' => [
        'database_error' => 'Datenbankfehler',
        'record_not_found' => 'Inhaltselement nicht gefunden!',
        'unknown_type' => 'Unbekannter Beziehungstyp',
        'relation_updated' => 'Beziehung erfolgreich aktualisiert',
    ],
];
