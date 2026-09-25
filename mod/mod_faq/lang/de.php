<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_faq'] = [
    'title' => 'Häufig gestellte Fragen (FAQ)',
    'module_title' => 'Häufig gestellte Fragen (FAQ)',
    'add_question' => 'Frage hinzufügen',
    'edit_question' => 'Frage bearbeiten',
    'delete_question' => 'Frage löschen',
    'update' => 'Aktualisieren',
    'save_all' => 'Änderungen speichern',
    'question' => 'Frage',
    'answer' => 'Antwort',
    'sort_order' => 'Reihenfolge',
    'status' => 'Status',
    'published' => 'Veröffentlicht',
    'draft' => 'Entwurf',
    'empty_state' => 'Noch keine Fragen hinzugefügt. Tippen Sie auf +, um die erste Frage hinzuzufügen.',
    'character_limit' => 'Zeichenlimit',
    'limit_reached' => 'Maximales Limit von 25 Fragen erreicht.',
    'question_placeholder' => 'Geben Sie hier die Frage ein...',
    'answer_placeholder' => 'Geben Sie hier die Antwort ein...',
    'drag_to_reorder' => 'Zum Neuanordnen ziehen',
    'confirm_delete' => 'Sind Sie sicher, dass Sie diese Frage löschen möchten?',
    'admin' => [
        'faq_management' => 'FAQ-Verwaltung',
        'manage_faq' => 'Häufig gestellte Fragen',
    ],
    'errors' => [
        'missing_module_name' => 'Modulinformationen für die FAQ-Operation fehlen.',
        'missing_record_id' => 'Datensatzinformationen für die FAQ-Operation fehlen.',
        'invalid_module' => 'Kein FAQ-Modul für diesen Datensatz definiert.',
        'module_faq_permission_missing' => 'Keine FAQ-Berechtigung für dieses Modul definiert.',
        'invalid_record' => 'Zugehöriger Datensatz konnte nicht gefunden werden.',
        'invalid_faq' => 'Zugehöriger FAQ-Datensatz konnte nicht gefunden werden.',
        'invalid_record_title_field' => 'Das Feld mod_value ist für dieses Modul ungültig.',
    ],
    'role' => [
        'name' => 'FAQ-Rollen',
        'add' => 'Kann Fragen hinzufügen.',
        'edit' => 'Kann Fragen bearbeiten.',
        'delete' => 'Kann Fragen löschen.',
        'list' => 'Kann FAQ-Verwaltung anzeigen.',
        'moderate_edit' => 'Kann Fragen anderer Benutzer bearbeiten.',
        'moderate_delete' => 'Kann Fragen anderer Benutzer löschen.',
    ],
    'role_errors' => [
        'faq' => 'Keine Berechtigung zur FAQ-Verwaltung.',
        'add' => 'Keine Berechtigung zum Hinzufügen von Fragen.',
        'edit' => 'Keine Berechtigung zum Bearbeiten von Fragen.',
        'delete' => 'Keine Berechtigung zum Löschen von Fragen.',
        'list' => 'Keine Berechtigung zum Anzeigen der FAQ-Verwaltung.',
        'moderate_edit' => 'Keine Berechtigung zum Bearbeiten fremder Fragen.',
        'moderate_delete' => 'Keine Berechtigung zum Löschen fremder Fragen.',
    ],
    'message' => [
        'success' => [
            'add' => 'Frage erfolgreich hinzugefügt.',
            'edit' => 'Frage erfolgreich aktualisiert.',
            'delete' => 'Frage erfolgreich gelöscht.',
            'reorder' => 'Reihenfolge erfolgreich aktualisiert.',
            'batch' => 'Alle Fragen erfolgreich gespeichert.',
            'status' => 'Status erfolgreich geändert.',
        ],
        'error' => [
            'add' => 'Frage konnte nicht hinzugefügt werden.',
            'edit' => 'Frage konnte nicht aktualisiert werden.',
            'delete' => 'Frage konnte nicht gelöscht werden.',
            'reorder' => 'Reihenfolge konnte nicht aktualisiert werden.',
            'batch' => 'Stapelspeicherung fehlgeschlagen.',
            'status' => 'Statusänderung fehlgeschlagen.',
            'max_limit' => 'Das Limit von 25 Fragen darf nicht überschritten werden.',
            'invalid_record' => 'Ungültiger Datensatz oder Modul.',
            'permission_denied' => 'Keine Berechtigung zur FAQ-Verwaltung.',
            'duplicate_question' => 'Eine Frage mit demselben Text ist bereits vorhanden.',
        ],
    ],
    'validation' => [
        'question_required' => 'Frage ist erforderlich.',
        'question_max' => 'Frage darf 255 Zeichen nicht überschreiten.',
        'question_duplicate' => 'Diese Frage wurde bereits hinzugefügt.',
        'answer_required' => 'Antwort ist erforderlich.',
        'answer_max' => 'Antwort darf 5000 Zeichen nicht überschreiten.',
    ]
];
