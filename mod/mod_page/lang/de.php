<?php
/**
 * Page Management Module German Language File
 * Amed Framework CMS - Multilingual Content Management System
 *
 * This file contains all German translations for the page management module.
 * Professional translations with modern CMS terminology.
 */

$lang['mod_page'] = [
    // === Basic Operations ===
    'add' => 'Seite hinzufügen',
    'edit' => 'Seite bearbeiten',
    'list' => 'Seitenliste',

    // === System Messages ===
    'information' => 'Information',
    'oke' => 'Vorgang erfolgreich',
    'hata' => 'Fehler aufgetreten',
    'error' => 'Kritischer Fehler',

    // === Confirmation Messages ===
    'remove_image' => 'Sind Sie sicher, dass Sie dieses Bild entfernen möchten?',
    'remove_image_oke' => 'Bild erfolgreich entfernt',
    'choose_wish' => 'Bitte falls gewünscht auswählen',
    'menu_options' => [
        'none' => 'Nicht zum Menü hinzufügen',
        'top_level' => 'Als Menüpunkt auf oberster Ebene hinzufügen',
        'slot_root' => 'Unter der ausgewählten Menüfläche hinzufügen',
    ],
    'menu_slot_options' => [
        'none' => 'Keine Menüfläche',
        'header' => 'Kopfmenü',
        'footer' => 'Fußmenü',
    ],
    'remove_record' => 'Sind Sie sicher, dass Sie diesen Datensatz löschen möchten?',
    'seo_meta' => 'SEO-Metadaten',

    // === System Messages ===
    'messages' => [
        'oke' => [
            'add' => 'Seite erfolgreich hinzugefügt',
            'edit' => 'Seite erfolgreich aktualisiert',
        ],
        'error' => [
            'add' => 'Seite konnte nicht hinzugefügt werden',
            'edit' => 'Seite konnte nicht aktualisiert werden',
        ],
        'information' => [
            'moderate' => 'Doppelklicken Sie auf die Seite, die Sie bearbeiten möchten',
            'move' => 'Ziehen Sie Seiten per Drag-and-Drop, um ihre Reihenfolge zu ändern',
        ],
        'warning' => [
            'reorder' => 'Seitenreihenfolge ändern?',
            'delete' => 'Sind Sie sicher, dass Sie diese Seite löschen möchten?',
        ],
        'success' => [
            'delete' => 'Seite erfolgreich gelöscht',
        ],
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'reorder_single' => 'Möchten Sie die Reihenfolge dieser Seite ändern?',
        'delete_single' => 'Sind Sie sicher, dass Sie diese Seite löschen möchten?',
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Ja',
        'no' => 'Nein',
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'Der angegebene Datensatz wurde nicht gefunden',
        'slot_branch_missing' => 'Die ausgewählte Menüfläche konnte für diese Sprache nicht aufgelöst werden',
    ],

    // === Button Labels ===
    'button' => [
        'add' => 'Hinzufügen',
        'edit' => 'Bearbeiten',
        'delete' => 'Löschen',
        'vazgec' => 'Abbrechen',
        'kaldir' => 'Entfernen',
        'evet' => 'Ja',
        'hayir' => 'No',
        'gallery' => 'Bildergalerie',
        'file' => 'Dateimanager',
        'tamam' => 'OK',
        'upload' => 'Hochladen',
    ],

    // === Actions ===
    'actions' => [
        'cancel' => 'Abbrechen',
        'edit' => 'Bearbeiten',
        'delete' => 'Löschen',
        'unlock_reorder' => 'Drag & Drop freischalten',
        'lock_reorder' => 'Drag & Drop sperren',
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Bitte lassen Sie das Feld %s nicht leer',
        'length' => '%s muss zwischen %s und %s Zeichen lang sein',
        'email' => 'Bitte geben Sie eine gültige E-Mail-Adresse im Feld %s ein',
        'unique' => 'Das von Ihnen eingegebene %s ist bereits registriert, bitte wählen Sie ein anderes %s',
        'inarray' => 'Das Feld %s akzeptiert nur die Werte: \' %s \'',
    ],

    // === Role Permissions ===
    'roll' => [
        'ad' => 'Rollen der Seitenverwaltung',
        'add' => 'Kann Seiten hinzufügen',
        'edit' => 'Kann Seiten bearbeiten',
        'delete' => 'Kann Seiten entfernen',
        'list' => 'Kann die Seitenverwaltung einsehen',
        'gallery' => 'Kann Galerien hinzufügen?',
        'file' => 'Kann Dateien hinzufügen?',
        'moderate_edit' => 'Kann Seiten bearbeiten, die anderen gehören',
        'moderate_delete' => 'Kann Seiten entfernen, die anderen gehören',
        'approval' => 'Kann Seiten veröffentlichen/depublizieren?',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'moderate_edit' => 'Sie können keine Seiten bearbeiten, die Ihnen nicht gehören',
        'moderate_delete' => 'Sie können keine Seiten entfernen, die Ihnen nicht gehören',
        'add' => 'Sie haben keine Berechtigung, Seiten hinzuzufügen',
        'edit' => 'Sie haben keine Berechtigung, Seiten zu bearbeiten',
        'list' => 'Sie haben keine Berechtigung, die Seitenverwaltung einzusehen',
        'delete' => 'Sie haben keine Berechtigung, Seiten zu entfernen',
        'gallery' => 'Sie haben keine Berechtigung, Galerien hinzuzufügen',
        'file' => 'Sie haben keine Berechtigung, Dateien hinzuzufügen',
        'approval' => 'Sie haben keine Berechtigung, Seiten zu veröffentlichen/depublizieren',
    ],

    // === Admin Panel ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Anzeigesprache',
            'name' => 'Seitenname',
            'parent_id' => 'Unterseite',
            'order' => 'Reihenfolge',
            'root' => 'Ist dies eine Stammseite, die nicht angezeigt werden soll?',
            'status' => 'Veröffentlichen?',
            'publish_desc' => 'Möchten Sie diesen Inhalt veröffentlichen oder als Entwurf speichern?',
            'deck' => 'Einleitung im Header anzeigen?',
            'summary' => 'Einleitung (Deck)',
            'content' => 'Inhalt',
            'gallery_id' => 'Galerie-ID (Um Bilder von einer anderen Seite anzuzeigen, geben Sie die ID dieser Seite ein)',
            'file_id' => 'Datei-ID (Um Dateien von einer anderen Seite anzuzeigen, geben Sie die ID dieser Seite ein)',
            'seo' => 'Seiten-URL (Slug)',
            'title' => 'Titel (für Suchmaschinen)',
            'keywords' => 'Keywords (für Suchmaschinen)',
            'description' => 'Beschreibung (für Suchmaschinen)',
            'image' => 'Bild',
            'acl_id' => 'Wer kann diese Seite sehen?',
            'category_id' => 'Verwandte Inhaltskategorie',
            'menu' => 'Seite zum Menü senden',
            'menu_slot' => 'Menüfläche',
        ],
        'select_value' => [
            'root_e' => 'Ja',
            'root_h' => 'Nein',
        ],
        'radio' => [
            'publish_yes' => 'Veröffentlichen',
            'publish_no' => 'Als Entwurf speichern',
            'deck_yes' => 'Ja',
            'deck_no' => 'Nein',
        ],
    ],
    'wizard' => [
        'steps' => [
            'language' => 'Sprache',
            'structure' => 'Struktur',
            'title' => 'Titel',
            'summary' => 'Zusammenfassung',
            'content' => 'Inhalt',
            'display' => 'Anzeige',
            'relations' => 'Verknüpfungen',
            'seo_finish' => 'SEO & Fertigstellen',
        ],
        'descriptions' => [
            'language' => 'Wählen Sie die Sprache aus, bevor Sie den Seitenbaum und die zugehörigen Optionen erstellen.',
            'structure' => 'Wählen Sie aus, wohin diese Seite gehört und ob sie mit Kategorie- und Menübäumen verknüpft werden soll.',
            'title' => 'Legen Sie den Seitennamen fest, der die primäre Identität der Seite bestimmt.',
            'summary' => 'Schreiben Sie den kurzen Einleitungs-/Decktext, der vor dem Seiteninhalt angezeigt wird.',
            'content' => 'Erstellen Sie den eigentlichen Seiteninhalt.',
            'display' => 'Legen Sie das strukturelle Anzeigeverhalten für die Seite fest.',
            'relations' => 'Verknüpfen Sie optionale verwandte Datensätze und Medienreferenzen.',
            'seo_finish' => 'Überprüfen Sie die SEO-Werte, wählen Sie den Veröffentlichungsstatus aus und schließen Sie die Seiteneinrichtung ab.',
        ],
        'page_summary' => 'Zusammenfassung der Seite',
        'previous' => 'Zurück',
        'next' => 'Weiter',
    ],
    'quick_unpublish_confirm' => 'Sind Sie sicher, dass Sie diese Seite depublizieren möchten?',
    'quick_publish_confirm' => 'Sind Sie sicher, dass Sie diese Seite veröffentlichen möchten?',
    'published_msg' => 'Seite erfolgreich veröffentlicht',
    'unpublished_msg' => 'Seite erfolgreich depubliziert',
    'status_badge' => [
        'active' => 'Veröffentlicht',
        'draft' => 'Entwurf',
    ],
];
