<?php
/**
 * Menu Module - German Language File
 *
 * @package AKM Business
 * @module  mod_menu
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Menu Module Language Translations
 */
$lang['mod_menu'] = [

    // === Page Titles ===
    'list' => 'Menüverwaltung',
    'add' => 'Menü hinzufügen',
    'edit' => 'Menü bearbeiten',
    'information' => 'Information',

    // === Legacy Message Keys (used by index.php) ===
    'message' => [
        'oke' => [
            'add' => 'Menü erfolgreich hinzugefügt!',
            'edit' => 'Menü erfolgreich aktualisiert!',
        ],
        'error' => [
            'add' => 'Menü konnte nicht hinzugefügt werden!',
            'edit' => 'Menü konnte nicht aktualisiert werden!',
        ],
    ],

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Hinzufügen',
        'edit' => 'Bearbeiten',
        'list' => 'Listen',
        'delete' => 'Löschen',
        'cancel' => 'Abbrechen',
        'save' => 'Speichern',
        'update' => 'Aktualisieren',
        'remove' => 'Entfernen',
        'refresh' => 'Aktualisieren',
        'export' => 'Exportieren',
        'import' => 'Importieren',
        'bulk_actions' => 'Massenaktionen',
        'gallery' => 'Galerie',
        'file' => 'Dateien',
        'unlock_reorder' => 'Drag-and-Drop-Modus aktivieren',
        'lock_reorder' => 'Drag-and-Drop-Modus deaktivieren',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Menü erfolgreich hinzugefügt!',
            'updated' => 'Menü erfolgreich aktualisiert!',
            'deleted' => 'Menü erfolgreich gelöscht!',
            'reordered' => 'Menüreihenfolge aktualisiert!',
        ],
        'error' => [
            'not_added' => 'Menü konnte nicht hinzugefügt werden!',
            'not_updated' => 'Menü konnte nicht aktualisiert werden!',
            'not_deleted' => 'Menü konnte nicht gelöscht werden!',
            'not_found' => 'Der angegebene Menüeintrag konnte nicht gefunden werden!',
        ],
        'warning' => [
            'delete' => 'Sind Sie sicher, dass Sie dieses Menü löschen möchten?',
            'reorder' => 'Möchten Sie die Menüreihenfolge ändern?',
        ],
        'info' => [
            'edit_double_click' => 'Doppelklicken Sie auf das Menü, das Sie bearbeiten möchten',
            'drag_reorder' => 'Ziehen Sie das Menü an die gewünschte Stelle, um die Reihenfolge zu ändern',
            'block_system_removed' => 'Block-System entfernt - diese Funktion ist nicht mehr verfügbar',
            'move' => 'Sie können Menüs per Drag-and-Drop neu anordnen.',
            'reorder_mode' => 'Modus: Neuanordnung (Entsperrt)',
        ],
    ],

    // === Status Types ===
    'status' => [
        'published' => 'Veröffentlicht',
        'draft' => 'Entwurf',
        'archived' => 'Archiviert',
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Ja',
        'no' => 'Nein',
        'ok' => 'OK',
    ],

    // === Target Options ===
    'targets' => [
        'same' => 'Gleiche Seite',
        'new' => 'Neue Seite',
        'blank' => 'Neuer Tab',
    ],

    // === Form Labels ===
    'form' => [
        'basic_info' => 'Basisinformationen',
        'display_settings' => 'Anzeigeeinstellungen',
        'advanced_settings' => 'Erweiterte Einstellungen',
        'acl_settings' => 'Berechtigungseinstellungen (ACL)',

        'id' => 'ID',
        'name' => 'Menüname',
        'url' => 'Menü-URL',
        'parent' => 'Übergeordnetes Menü',
        'order' => 'Reihenfolge',
        'language' => 'Anzeigesprache',
        'target' => 'Ziel',
        'published' => 'Status',
        'html_class' => 'HTML-Klasse',
        'icon' => 'Icon',
        'access_control' => 'Zugriffskontrolle',
        'description' => 'Beschreibung',
        'file' => 'Datei',
    ],

    // === Table Columns ===
    'columns' => [
        'id' => 'ID',
        'language' => 'Sprache',
        'name' => 'Menüname',
        'url' => 'URL',
        'parent_menu' => 'Untermenü',
        'order' => 'Reihenfolge',
        'target' => 'Ziel',
        'status' => 'Status',
        'icon' => 'Icon',
        'access_level' => 'Zugriffsebene',
        'actions' => 'Aktionen',
        'parent_id' => 'Untermenü',
        'html' => 'HTML',
        'acl_id' => 'Zugriffsrecht',
        'file' => 'Datei',
    ],

    'admin' => [
        'columns' => [
            'language' => 'Sprache',
            'name' => 'Menüname',
            'url' => 'URL',
            'file' => 'Datei',
            'parent_id' => 'Untermenü',
            'target' => 'Öffnungsart',
            'status' => 'Veröffentlichungsstatus',
            'html' => 'HTML',
            'icon' => 'Icon',
            'acl_id' => 'Zugriffsrecht',
        ],
        'select_value' => [
            'target_e' => 'Neues Fenster (_blank)',
            'target_h' => 'Gleiches Fenster (_self)',
        ],
        'radio' => [
            'publish_yes' => 'Veröffentlicht',
            'publish_no' => 'Entwurf',
        ],
    ],



    // === Validation Messages ===
    'validation' => [
        'required' => 'Das Feld :field ist erforderlich',
        'min_length' => 'Das Feld :field muss mindestens :min Zeichen lang sein',
        'max_length' => 'Das Feld :field darf nicht länger als :max Zeichen sein',
        'unique' => 'Das Feld :field muss eindeutig sein',
        'url' => 'Bitte geben Sie eine gültige URL-Adresse ein',
        'numeric' => 'Das Feld :field muss numerisch sein',
        'email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein',
    ],

    // === Access Control (ACL) ===
    'choose_wish' => 'Bitte wählen',
    'permissions' => [
        'title' => 'Berechtigungen der Menüverwaltung',
        'view' => 'Menüs anzeigen',
        'add' => 'Menü hinzufügen',
        'edit_own' => 'Eigenes Menü bearbeiten',
        'edit_all' => 'Alle Menüs bearbeiten',
        'delete_own' => 'Eigenes Menü löschen',
        'delete_all' => 'Alle Menüs löschen',
        'manage_access' => 'Zugriff verwalten',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'view_denied' => 'Sie haben keine Berechtigung, die Menüverwaltung einzusehen',
        'add_denied' => 'Sie haben keine Berechtigung, ein Menü hinzuzufügen',
        'edit_denied' => 'Sie haben keine Berechtigung, ein Menü zu bearbeiten',
        'delete_denied' => 'Sie haben keine Berechtigung, ein Menü zu löschen',
        'access_denied' => 'Sie haben keine Berechtigung, dieses Menü zu bearbeiten/löschen (gehört nicht Ihnen)',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'delete_single' => 'Sind Sie sicher, dass Sie dieses Menü löschen möchten?',
        'delete_multiple' => 'Sind Sie sicher, dass Sie :count ausgewählte Menüs löschen möchten?',
        'publish' => 'Sind Sie sicher, dass Sie dieses Menü veröffentlichen möchten?',
        'unpublish' => 'Sind Sie sicher, dass Sie die Veröffentlichung dieses Menüs aufheben möchten?',
        'reorder_single' => 'Möchten Sie dieses Menü an den neuen Ort verschieben?',
    ],

    // === Helper Text ===
    'help' => [
        'parent_menu' => 'Wählen Sie aus, unter welchem Menü dieses Menü angezeigt werden soll',
        'access_control' => 'Geben Sie an, welche Benutzerrollen dieses Menü sehen können',
        'url' => 'Verwenden Sie das Format /start für interne Links, beginnen Sie mit https:// für externe Links',
        'order' => 'Bestimmt die Reihenfolge des Menüs (niedrigere Werte erscheinen oben)',
        'icon' => 'Geben Sie den Namen der Bootstrap-Icon-Klasse ein (z. B. bi-house)',
    ],

    // === Placeholders ===
    'placeholders' => [
        'menu_name' => 'Menünamen eingeben',
        'menu_url' => '/beispiel-link oder https://beispiel.de',
        'menu_file' => 'menu-partial.twig',
        'menu_html' => 'Eigener HTML-Inhalt',
        'icon_class' => 'bi-house',
    ],

    // === Empty States ===
    'empty' => [
        'no_menus' => 'Bisher wurden keine Menüs hinzugefügt',
        'no_results' => 'Keine Menüs gefunden, die Ihren Suchkriterien entsprechen',
        'add_first' => 'Klicken Sie auf die Schaltfläche, um das erste Menü hinzuzufügen',
    ],

    // === Loading States ===
    'loading' => [
        'saving' => 'Speichern...',
        'loading' => 'Laden...',
        'processing' => 'Verarbeiten...',
    ],

    // === Success Messages ===
    'success' => [
        'operation_completed' => 'Vorgang erfolgreich abgeschlossen!',
        'changes_saved' => 'Änderungen gespeichert!',
    ],

    // === Error Messages ===
    'errors' => [
        'general' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.',
        'network' => 'Netzwerkfehler. Bitte überprüfen Sie Ihre Internetverbindung.',
        'server' => 'Server error. Bitte versuchen Sie es später erneut.',
        'record' => 'Der angegebene Menüeintrag konnte nicht gefunden werden.',
        'parent_selection_required' => 'Sie müssen auswählen, welchem Menü dieser Eintrag zugeordnet wird.',
        'parent_language_mismatch' => 'Die Sprache des übergeordneten Menüs muss mit der Menüsprache übereinstimmen.',
    ],

    // === AJAX Response Messages ===
    'ajax' => [
        'success' => 'Erfolg',
        'error' => 'Fehler',
    ],

    // === Permission Roles ===
    'roll' => [
        'ad' => 'Rollen der Menüverwaltung',
        'add' => 'Kann Menüs hinzufügen',
        'edit' => 'Kann Menüs bearbeiten',
        'delete' => 'Kann Menüs löschen',
        'list' => 'Kann die Menüverwaltung einsehen',
        'acl' => 'Kann Menüberechtigungen bearbeiten',
        'setting' => 'Kann Menüeinstellungen ändern',
        'link' => 'Kann Menülinks ansehen'
    ],

    // === Permission Errors ===
    'role_errors' => [
        'add' => 'Sie haben keine Berechtigung, Menüs hinzuzufügen',
        'edit' => 'Sie haben keine Berechtigung, Menüs zu bearbeiten',
        'delete' => 'Sie haben keine Berechtigung, Menüs zu löschen',
        'list' => 'Sie haben keine Berechtigung, die Menüverwaltung einzusehen',
        'moderate_edit' => 'Sie haben keine Berechtigung, Menüs anderer Benutzer zu bearbeiten',
        'moderate_delete' => 'Sie haben keine Berechtigung, Menüs anderer Benutzer zu löschen',
    ],
];
$lang['mod_menu']['slots'] = [
    'header' => 'Kopfmenü',
    'footer' => 'Fußmenü',
];
$lang['mod_menu']['parent_labels'] = [
    'select_parent' => 'Übergeordnetes Menü wählen',
    'language_root' => 'Oberste Ebene unter :language',
    'system_root' => 'Systemwurzel',
];
