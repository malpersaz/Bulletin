<?php
/**
 * News Management Module - German Language File
 *
 * @package AKM Association
 * @module  mod_news
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * News Management Module Language Translations
 */
$lang['mod_news'] = [

    // === Basic Actions ===
    'add' => 'Nachricht hinzufügen',
    'edit' => 'Nachricht bearbeiten',
    'list' => 'Nachrichtenliste',
    'information' => 'Information!',
    'success' => 'Vorgang erfolgreich!',
    'error_occurred' => 'Ein Fehler ist aufgetreten!',
    'error' => 'Schwerwiegender Fehler aufgetreten!',
    'remove_image_title' => 'Bild entfernen',
    'remove_image' => 'Möchten Sie dieses Bild wirklich entfernen?',
    'remove_image_success' => 'Bild entfernt',
    'choose_wish' => 'Bitte wählen Sie bei Bedarf aus',
    'remove_record' => 'Möchten Sie diesen Datensatz wirklich löschen?',
    'delete_confirm' => 'Möchten Sie diese Nachricht wirklich löschen?',
    'quick_unpublish_confirm' => 'Möchten Sie die Veröffentlichung dieser Nachricht wirklich aufheben?',
    'quick_publish_confirm' => 'Möchten Sie diese Nachricht wirklich veröffentlichen?',
    'wizard_loading' => 'Assistent wird geladen...',
    'add_image' => 'Bild hinzufügen',
    'upload_image' => 'Bild hochladen/ändern',
    'local' => 'Lokal',
    'embed' => 'Einbetten',

    // === Filter Menu ===
    'filter' => [
        'title' => 'Filtern & Sortieren',
        'close' => 'Schließen',
        'sort_label' => 'Sortierung',
        'newest' => 'Neueste',
        'oldest' => 'Älteste',
        'title_az' => 'Titel (A-Z)',
        'title_za' => 'Titel (Z-A)',
        'view_count' => 'Aufrufe',
        'limit_label' => 'Anzeigelimit',
        'lang_label' => 'Sprachauswahl',
        'selected_lang' => 'Ausgewählte Sprache',
        'date_label' => 'Datumsbereich',
        'start_date' => 'Startdatum',
        'end_date' => 'Enddatum',
        'clear' => 'Zurücksetzen',
        'apply' => 'Ergebnisse anzeigen',
        'select_lang' => 'Sprache auswählen',
        'all_langs' => 'Alle Sprachen',
        'select_label' => 'Auswählen',
    ],

    // === Messages ===
    'message' => [
        'success' => [
            'add' => 'Nachricht hinzugefügt!',
            'edit' => 'Nachricht aktualisiert!',
        ],
        'error' => [
            'add' => 'Nachricht konnte nicht hinzugefügt werden!',
            'edit' => 'Nachricht konnte nicht aktualisiert werden!',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'Der angegebene Datensatz wurde nicht gefunden!',
    ],

    // === Buttons ===
    'button' => [
        'next' => 'Weiter',
        'prev' => 'Zurück',
        'send' => 'Senden',
        'add' => 'Hinzufügen',
        'edit' => 'Bearbeiten',
        'refresh' => 'Aktualisieren',
        'delete' => 'Löschen',
        'gallery' => 'Bildergalerie',
        'file' => 'Dateiverwaltung',
        'cancel' => 'Abbrechen',
        'remove' => 'Entfernen',
        'ok' => 'OK',
        'yes' => 'Ja',
        'no' => 'Nein',
        'seo_meta' => '(SEO, Meta)',
    ],

    // === Data Grid Messages ===
    'grid' => [
        'showing_results' => 'Zeige {from} bis {to} von {total} Ergebnissen.',
        'page' => 'Seite',
        'page_count' => 'Seitenzahl',
        'connection_error' => 'Verbindungsfehler',
        'search' => 'Suchen',
        'select_at_least_one' => 'Bitte wählen Sie mindestens eine Zeile aus',
        'select_only_one' => 'Bitte wählen Sie nur eine Zeile aus',
        'loading' => 'Wird geladen...',
        'no_data' => 'Keine Nachrichten gefunden',
        'approve' => [
            'key' => 'Veröffentlichen',
            'value' => 'Möchten Sie diese Nachricht wirklich veröffentlichen?',
        ],
        'unapprove' => [
            'key' => 'Veröffentlichung aufheben',
            'value' => 'Möchten Sie die Veröffentlichung dieser Nachricht wirklich aufheben?',
        ],
        'load_more' => 'Mehr laden',
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Bitte lassen Sie das Feld %s nicht leer',
        'length' => '%s muss mindestens %s und darf höchstens %s Zeichen lang sein',
        'email' => 'Bitte geben Sie eine gültige E-Mail-Adresse für das Feld %s ein.',
        'unique' => 'Der eingegebene Wert für %s ist bereits im System registriert. Bitte geben Sie einen anderen Wert für %s an!',
        'inarray' => 'Das Feld %s darf nur die Werte \'%s\' annehmen',
        'title_length' => 'Sie müssen einen Titel mit mindestens 1 und höchstens 70 Zeichen eingeben.',
        'title_exists' => 'Dieser Titel existiert bereits, bitte ändern Sie ihn',
        'title_unique' => 'Ihr Titel ist eindeutig',
        'deck_length' => 'Bitte geben Sie einen Einleitungstext zwischen 90 und 120 Zeichen ein.',
        'invalid_lang' => 'Die ausgewählte Sprache ist im System nicht aktiv.',
        'category_not_found' => 'Die ausgewählte Kategorie wurde nicht gefunden.',
        'category_root_forbidden' => 'Nachrichten können nicht direkt einer Hauptkategorie zugewiesen werden. Bitte wählen Sie eine Unterkategorie.',
        'category_lang_mismatch' => 'Die Sprache der ausgewählten Kategorie stimmt nicht mit der Sprache der Nachricht überein.',
    ],

    // === Permission Roles ===
    'role' => [
        'name' => 'Nachrichtenverwaltung – Berechtigungen',
        'add' => 'Darf Nachrichten hinzufügen',
        'edit' => 'Darf Nachrichten bearbeiten',
        'delete' => 'Darf Nachrichten löschen',
        'list' => 'Darf Nachrichtenverwaltung anzeigen',
        'publish' => 'Darf Nachrichten veröffentlichen',
        'unpublish' => 'Darf Veröffentlichung aufheben',
        'change_status' => 'Darf Veröffentlichungsstatus ändern',
        'edit_status' => 'Darf detaillierten Veröffentlichungsstatus ändern',
        'moderate_edit' => 'Darf fremde Nachrichten bearbeiten',
        'moderate_delete' => 'Darf fremde Nachrichten löschen',
        'gallery' => 'Darf Galerie verwalten',
        'file' => 'Darf Dateien verwalten',
        'approval' => 'Darf Nachrichten freigeben',
        'video_download' => 'Darf Videos herunterladen',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'add' => 'Sie haben keine Berechtigung zum Hinzufügen von Nachrichten',
        'edit' => 'Sie haben keine Berechtigung zum Bearbeiten von Nachrichten',
        'list' => 'Sie haben keine Berechtigung zum Anzeigen der Nachrichtenverwaltung',
        'delete' => 'Sie haben keine Berechtigung zum Löschen von Nachrichten',
        'approval' => 'Sie haben keine Berechtigung zum Freigeben von Nachrichten',
        'moderate_edit' => 'Sie können keine fremden Nachrichtendatensätze bearbeiten',
        'moderate_delete' => 'Sie können keine fremden Nachrichtendatensätze löschen',
        'gallery' => 'Sie haben keine Berechtigung zur Verwaltung der Galerie',
        'file' => 'Sie haben keine Berechtigung zur Verwaltung von Dateien',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Sprache',
            'name' => 'Titel',
            'category_id' => 'Kategorie',
            'created_at' => 'Datum',
            'updated_at' => 'Bearbeitungsdatum',
            'news' => 'Nachricht',
            'summary' => 'Einleitung',
            'seo' => 'SEO-URL',
            'title' => 'Seitentitel',
            'keywords' => 'Schlüsselwörter',
            'description' => 'Beschreibung',
            'status' => 'Veröffentlichungsstatus',
            'deck' => 'Hervorgehoben',
            'image' => 'Bild',
            'gallery_id' => 'Galerie',
            'file_id' => 'Datei',
            'acl_id' => 'Zugriffskontrolle',
            'menu' => 'Zum Menü hinzufügen',
            'video' => 'Videogalerie',
            'video_help' => 'Suchen Sie nach dem Namen des Videos, das in der Nachricht erscheinen soll',
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
        'record_management' => 'Datensatzverwaltung',
        'operation' => 'Vorgang',
        'apply' => 'Anwenden',
        'action_column' => 'Aktion',
        'select' => 'Auswählen',
        'from' => 'Von',
        'to' => 'Bis',
        'search' => 'Suchen',
        'reset' => 'Zurücksetzen',
        'approval' => 'Bestätigen',
        'processing' => 'Wird verarbeitet',
        'record_selected' => 'Datensatz ausgewählt',
        'operation_failed' => 'Vorgang konnte nicht ausgeführt werden',
        'select_operation' => 'Bitte wählen Sie einen Vorgang aus',
        'all' => 'Alle',
        'pdf' => 'PDF',
        'csv' => 'CSV',
        'excel' => 'Excel',
        'print' => 'Drucken',
        'print_info' => 'Druckinfo',
        'created_by' => 'Erstellt von',
        'copy' => 'Kopieren',
        'default_sort' => 'Neueste',
        'default_lang' => 'DE',
        'date_locale' => 'de-DE',
        'filter_cleared' => 'Filter zurückgesetzt.',
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
        'info' => 'Zeige _START_ bis _END_ von _TOTAL_ Einträgen',
        'infoEmpty' => 'Zeige 0 bis 0 von 0 Einträgen',
        'infoFiltered' => '(gefiltert aus _MAX_ Einträgen insgesamt)',
        'lengthMenu' => '_MENU_ Einträge anzeigen',
        'search' => 'Suchen:',
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
        'hintText' => 'Suchbegriff eingeben',
        'noResultsText' => 'Keine Ergebnisse',
        'searchingText' => 'Suche läuft...',
    ],

    // === AnyTime Picker ===
    'anytime' => [
        'labelDayOfMonth' => 'Tag des Monats',
        'labelHour' => 'Stunde',
        'labelMinute' => 'Minute',
        'labelMonth' => 'Monat',
        'labelTitle' => 'Datum und Uhrzeit auswählen',
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
        'search_placeholder' => 'Sprache suchen (z. B. Deutsch, EN)...',
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
            'intro' => 'Einleitungstext (Vorspann)',
            'news' => 'Nachrichteninhalt',
            'image' => 'Bildauswahl',
            'finish' => 'Abschließen',
        ],
        'subtitle' => [
            'language' => 'Wählen Sie die Hauptsprache für die Nachricht aus.',
            'category' => 'Bestimmen Sie die Kategorie für diese Nachricht.',
            'title' => 'Geben Sie einen ansprechenden Titel ein. (Min: 1, Max: 70)',
            'date' => 'Datum und Uhrzeit der Veröffentlichung.',
            'intro' => 'Schreiben Sie eine kurze Zusammenfassung. (90 - 120 Zeichen)',
            'news' => 'Rich-Text-Editor.',
            'image' => 'Laden Sie ein Titelbild hoch.',
            'finish' => 'Abschlussprüfung und Veröffentlichung.',
        ],
        'label' => [
            'news_title' => 'Nachrichtentitel',
            'intro_text' => 'Einleitungstext',
            'upload_text' => 'Tippen, um ein Bild hochzuladen',
            'published' => 'Veröffentlicht',
            'published_desc' => 'Die Nachricht ist nach dem Hinzufügen für alle sichtbar.',
            'category_select' => 'Kategorie auswählen...',
            'change_image' => 'Bild ändern',
            'editor_placeholder' => 'Beginnen Sie hier mit dem Schreiben Ihrer Nachricht...',
        ],
        'image' => [
            'drop_title' => 'Bild hierher ziehen',
            'drop_description' => 'Bild hochladen, einfügen oder ablegen.',
            'upload_link' => 'Datei hochladen',
            'link_separator' => 'ODER',
            'link_placeholder' => 'Bildlink einfügen',
            'link_action' => 'Importieren',
            'hint_loading' => 'Bild wird importiert...',
            'hint_invalid_type' => 'Hier werden nur Bilddateien unterstützt.',
            'hint_no_drop_data' => 'Im abgelegten Inhalt wurde kein verwendbares Bild gefunden.',
            'hint_remote_failed' => 'Das Bild konnte von dieser Quelle nicht importiert werden.',
        ],
        'messages' => [
            'success_sim' => 'Nachricht erfolgreich erstellt! (Simulation)',
            'video_success_sim' => 'Video erfolgreich hinzugefügt! (Simulation)',
            'video_embed_error' => 'Bitte geben Sie einen gültigen Einbettungscode (iframe) ein.',
            'video_title_error' => 'Der Videotitel muss mindestens 3 Zeichen lang sein.',
            'card_click' => 'Karte angeklickt: %s',
            'no_category_for_lang' => 'Für diese Sprache wurde keine Unterkategorie gefunden. Bitte erstellen Sie zuerst eine Unterkategorie.',
        ],
        'summary' => [
            'title' => 'Zusammenfassung',
            'selected_none' => 'Nicht ausgewählt',
        ],
        'button' => [
            'save' => 'Speichern',
            'next' => 'Weiter',
            'prev' => 'Zurück',
            'finish' => 'Speichern und Beenden',
        ]
    ],

    // === Context Menu ===
    'context_menu' => [
        'title' => 'Nachrichtenaktionen',
        'edit' => 'Bearbeiten',
        'gallery' => 'Galerie zur Nachricht hinzufügen',
        'files' => 'Datei zur Nachricht hinzufügen',
        'video' => 'Video zur Nachricht hinzufügen',
        'status' => 'Status ändern',
        'delete' => 'Löschen',
        'delete_error' => 'Löschen fehlgeschlagen!',
        'video_success_sim' => 'Video erfolgreich hinzugefügt (Simulation)',
        'video_embed_error' => 'Ungültiger Einbettungscode',
        'video_title_error' => 'Ungültiger Titel',
        'publish' => 'Veröffentlichen',
        'unpublish' => 'Veröffentlichung aufheben',
        'published_msg' => 'Veröffentlicht',
        'unpublished_msg' => 'Unveröffentlicht',
        'confirm' => 'Ja',
        'cancel' => 'Abbrechen',
        'edit_alert' => "BEARBEITUNGSMODUS:\nID: %s\nTitel: %s",
        'gallery_alert' => "GALERIEVERWALTUNG:\nID: %s\nTitel: %s\nBildergalerie für Nachricht wird geöffnet.",
        'files_alert' => "DATEIVERWALTUNG:\nID: %s\nTitel: %s\nDateiverwaltung für Nachricht wird geöffnet.",
        'status_alert' => "STATUS AKTUALISIERT:\n%s",
        'delete_success' => 'Nachricht gelöscht!',
        'add_video' => 'Video hinzufügen',
        'add_file' => 'Datei hinzufügen',
    ],

    // === Hero Slider & Featured Deck ===
    'hero_slider_add' => 'An Slider senden',
    'hero_slider_remove' => 'Aus Slider entfernen',
    'hero_slider_added' => 'An Slider gesendet.',
    'hero_slider_removed' => 'Aus Slider entfernt.',
    'hero_slider_error' => 'Slider-Aktualisierung fehlgeschlagen.',
    'hero_slider_badge' => 'Slider',
    'deck_add' => 'Als Hervorgehoben markieren',
    'deck_remove' => 'Aus Hervorgehoben entfernen',
    'deck_added' => 'Als Hervorgehoben markiert.',
    'deck_removed' => 'Aus Hervorgehoben entfernt.',
    'deck_error' => 'Status für Hervorgehoben konnte nicht aktualisiert werden.',
    'deck_badge' => 'Hervorgehoben',

    'picker' => [
        'gallery' => 'Vorhandene Galerie auswählen',
        'file' => 'Vorhandene Datei auswählen',
        'video' => 'Vorhandenes Video auswählen',
    ],

    // === Latest News ===
    'latest_news' => [
        'title' => 'Neueste Nachrichten',
        'subtitle' => 'Alle Nachrichten anzeigen',
        'description' => 'Noch keine Nachrichten veröffentlicht.',
    ],

    // === AJAX & Relation Messages ===
    'ajax' => [
        'success' => 'Erfolgreich',
        'error' => 'Fehler aufgetreten',
        'database_error' => 'Datenbankfehler',
        'record_not_found' => 'Nachricht nicht gefunden!',
        'unknown_type' => 'Unbekannter Beziehungstyp',
        'relation_updated' => 'Beziehung erfolgreich aktualisiert',
    ],

    // === AI Assistant ===
    'ai' => [
        'generate_deck' => 'Vorspann mit KI generieren',
        'suggest_headlines' => 'Schlagzeilen mit KI vorschlagen',
        'generate_seo' => 'SEO mit KI generieren',
        'generating' => 'Wird generiert...',
        'missing_key' => 'Gemini API-Schlüssel ist im Google Services Hub nicht konfiguriert.',
        'content_required' => 'Titel oder Inhalt des Artikels für die KI-Generierung erforderlich.',
        'select_headline' => 'Schlagzeile auswählen',
        'deck_success' => 'Vorspann erfolgreich generiert (%d Zeichen)',
        'deck_error' => 'Vorspann konnte nicht generiert werden.',
        'headline_success' => 'Schlagzeilenvorschläge erfolgreich erstellt.',
        'headline_error' => 'Schlagzeilenvorschläge konnten nicht erstellt werden.',
        'warning_title' => 'Warnung',
        'error_title' => 'Fehler',
        'invalid_action' => 'Ungültige KI-Aktion.',
        'copy' => 'Verwenden',
    ],
];
