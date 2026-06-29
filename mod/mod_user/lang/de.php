<?php
/**
 * User Module - German Language File
 *
 * @package AKM Business
 * @module  mod_user
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_user'] = [
    'add' => 'Benutzer hinzufügen',
    'edit' => 'Benutzer bearbeiten',
    'list' => 'Benutzerliste',
    'information' => 'Information!',
    'oke' => 'Vorgang erfolgreich!',
    'success' => 'Erfolg!',
    'last_step' => 'Letzter Schritt',
    'error_title' => 'Fehler aufgetreten!',
    'error_label' => 'Fehler!',
    'password_strength' => [
        'weak' => 'Schwach',
        'fair' => 'Mittel',
        'good' => 'Gut',
        'strong' => 'Stark',
    ],
    'password_match' => [
        'success' => 'Passwörter stimmen überein',
        'error' => 'Passwörter stimmen nicht überein',
    ],
    'js' => [
        'updating' => 'Wird aktualisiert...',
        'warning' => 'Warnung',
        'weak_password' => 'Schwaches Passwort',
        'password_len_req' => 'Mindestens 6 Zeichen',
        'password_complex_req' => 'Groß-/Kleinschreibung und Zahlen',
    ],
    'error_fatal' => 'Ein kritischer Fehler ist aufgetreten!',
    'database_error' => 'Der Datensatz konnte nicht gespeichert werden. Bitte versuchen Sie es erneut.',
    'remove_image' => 'Sind Sie sicher, dass Sie dieses Bild entfernen möchten?',
    'remove_image_oke' => 'Bild entfernt',
    'choose_wish' => 'Bitte wählen Sie aus, falls gewünscht',
    'remove_record' => 'Sind Sie sicher, dass Sie diesen Datensatz löschen möchten?',
    'message' =>
        [
            'oke' =>
                [
                    'add' => 'Benutzer hinzugefügt!',
                    'edit' => 'Benutzer aktualisiert!',
                ],
            'error' =>
                [
                    'add' => 'Benutzer konnte nicht hinzugefügt werden!',
                    'edit' => 'Benutzer konnte nicht aktualisiert werden!',
                ],
        ],
    'errors' =>
        [
            'record' => 'Der angegebene Datensatz konnte nicht gefunden werden!',
        ],
    'error' =>
        [
            'req_username' => 'Benutzername ist ein Pflichtfeld',
            'req_password' => 'Passwort ist ein Pflichtfeld',
            'req_email' => 'E-Mail ist ein Pflichtfeld',
            'invalid_email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein',
            'min_password' => 'Das Passwort muss mindestens 6 Zeichen lang sein',
            'current_password_required' => 'Das aktuelle Passwort ist erforderlich',
            'new_password_required' => 'Das neue Passwort ist erforderlich',
            'confirm_password_required' => 'Die Passwortbestätigung ist erforderlich',
            'password_length_error' => 'Das Passwort muss zwischen 6 und 20 Zeichen lang sein',
            'passwords_mismatch' => 'Die Passwörter stimmen nicht überein',
            'current_password_incorrect' => 'Das aktuelle Passwort ist falsch',
            'username_taken' => 'Benutzername ist bereits vergeben',
            'email_taken' => 'E-Mail-Adresse wird bereits verwendet',
            'invalid_credentials' => 'Benutzername oder Passwort ist falsch',
            'user_not_found' => 'Benutzer nicht gefunden',
            'account_inactive' => 'Ihr Konto ist noch nicht aktiv',
            'permission_error' => 'Berechtigungsfehler',
            'fill_all_fields' => 'Bitte füllen Sie alle Felder aus',
            'ip_protection_error' => 'Für dieses Konto ist ein IP-Schutz definiert und Ihre IP-Adresse stimmt nicht überein',
            'multi_login_error' => 'Dieses Konto ist bereits von einem anderen Computer aus angemeldet!',
            'system_error' => 'Systemfehler',
            'name_required' => 'Bitte geben Sie Ihren Namen an',
            'surname_required' => 'Bitte geben Sie Ihren Nachnamen an',
            'specify_email' => 'Bitte geben Sie Ihre E-Mail-Adresse an',
            'specify_username' => 'Bitte geben Sie Ihren Benutzernamen an',
            'specify_password' => 'Bitte geben Sie Ihr Passwort an',
            'username_invalid_chars' => 'Ihr Benutzername darf nur aus a-z A-Z 0-9 Zeichen bestehen',
            'username_length_error' => 'Ihr Benutzername muss zwischen 2 und 20 Zeichen lang sein',
            'email_invalid_format' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein',
            'email_registered' => 'Diese E-Mail-Adresse ist bereits registriert',
            'username_registered' => 'Dieser Benutzername ist bereits registriert',
        ],
    'button' =>
        [
            'add' => 'Hinzufügen',
            'edit' => 'Bearbeiten',
            'refresh' => 'Aktualisieren',
            'delete' => 'Löschen',
            'gallery' => 'Bildergalerie',
            'file' => 'Dateimanager',
            'cancel' => 'Abbrechen',
            'deactivate' => 'Deaktivieren',
            'yes' => 'Ja',
            'no' => 'Nein',
            'select_image' => 'Bild auswählen',
        ],
    'username' => 'Benutzername',
    'password' => 'Passwort',
    'email' => 'E-Mail',
    'admin_panel' => 'Admin-Panel',
    'user_panel' => 'Benutzerpanel',
    'login_success_wait' => 'Anmeldung erfolgreich. Bitte warten Sie, während Sie weitergeleitet werden..',
    'no_permission' => 'Sie haben keine Berechtigung, diesen Vorgang auszuführen',
    'please_login' => 'Bitte melden Sie sich an.',
    'login' => 'Anmelden',
    'logout' => 'Abmelden',
    'password_change' => 'Passwort ändern',
    'change' => 'Ändern',
    'current_password_label' => 'Aktuelles Passwort',
    'current_password' => 'Aktuelles Passwort',
    'new_password' => 'Neues Passwort',
    'confirm_new_password' => 'Neues Passwort bestätigen',
    'password_changed_success' => 'Ihr Passwort wurde erfolgreich geändert!',
    'info_change' => 'Informationen ändern',
    'your_name' => 'Ihr Name',
    'your_surname' => 'Ihr Nachname',
    'your_email' => 'Ihre E-Mail-Adresse',
    'your_dob' => 'Ihr Geburtsdatum',
    'info_updated_success' => 'Ihre Informationen wurden erfolgreich aktualisiert!',
    'mobile_phone' => 'Mobiltelefon',
    'profile_picture' => 'Profilbild',
    'forgot_password' => 'Passwort vergessen',
    'account_activation' => 'Kontoaktivierung',
    'register' => 'Registrieren',
    'city' => 'Stadt',
    'blog_name' => 'Blogname',
    'about_me' => 'Über mich',
    'max_chars_255' => 'Maximal 255 Zeichen.',
    'unread_messages_count' => 'Sie haben %s ungelesene Nachrichten.',
    'membership_date' => 'Mitgliedschaftsdatum',
    'update_date' => 'Aktualisierungsdatum',
    'groups' => [
        'site_manager' => 'Website-Manager',
        'editors' => 'Redakteure',
        'normal_member' => 'Mitglied'
    ],
    'installer' => [
        'sql_error' => 'Fehler beim Laden von users.sql',
        'settings_added' => '%s wurde zur Tabelle mod_user_settings hinzugefügt',
        'settings_error' => 'Fehler beim Laden der Mitgliedschaftseinstellungen',
        'settings_exists' => '%s existiert bereits in der Tabelle mod_user_settings',
        'user_added' => '-----<b>%s</b> - Benutzer wurde der Datenbank hinzugefügt!',
        'user_error' => '-----<b>%s</b> - Benutzer konnte nicht zur Datenbank hinzugefügt werden!',
        'user_exists' => '----- <b>%s</b> - Benutzer existiert bereits in der Datenbank',
    ]
];

$lang['mod_user']['email_templates'] = [
    'greeting' => 'Hallo <b>%s %s</b>',
    'click_here' => 'Hier klicken',
    'copy_link_notice' => 'Wenn Sie den Link nicht anklicken können, kopieren Sie bitte den folgenden Link und fügen Sie ihn in Ihren Browser ein.',
    'account_activation_intro' => 'Bitte öffnen Sie den folgenden Link, um Ihren Mitgliedsantrag bei %s abzuschließen.',
    'password_reset_intro' => 'Bitte öffnen Sie den folgenden Link, um das Passwort für Ihr Konto bei %s zurückzusetzen.',
    'password_reset_followup' => 'Nachdem Sie den Link geöffnet haben, wählen Sie ein neues Passwort, um das Zurücksetzen abzuschließen.',
    'password_reset_request_ip' => 'IP-Adresse der Passwortanfrage: <b>%s</b>',
    'password_reset_completed' => 'Das Passwort für Ihr Konto bei %s wurde zurückgesetzt.',
    'login_after_reset' => 'um sich in Ihr Konto einzuloggen.',
    'new_password_label' => 'Ihr neues Passwort',
];

$lang['mod_user']['admin'] = [
    'add_area' => 'Bereich zum Hinzufügen von Benutzern',
    'add_user' => 'Benutzer hinzufügen',
    'added' => 'Benutzer hinzugefügt!',
    'add_new_user' => 'Neuen Benutzer hinzufügen',
    'list' => 'Benutzerliste',
    'edit_area' => 'Bereich zum Bearbeiten von Benutzern',
    'edit_user' => 'Benutzer bearbeiten',
    'updated' => 'Benutzer erfolgreich aktualisiert',
    'acl' => 'Benutzergruppen-Rollenkontrolle (ACL)',
    'roles' => 'Rollen',
    'refresh_permissions' => 'Berechtigungen aktualisieren',
    'permissions_updated' => 'Benutzerberechtigungen erfolgreich aktualisiert',
    'user_group' => 'Benutzergruppe',
    'management' => 'Mitgliedschaftsverwaltung',
    'membership_settings' => 'Mitgliedschaftseinstellungen',
    'site_settings' => 'Website-Einstellungen',
    'social_media' => 'Soziale Medien',
    'update_membership_settings' => 'Mitgliedschaftseinstellungen aktualisieren',
    'settings_updated' => 'Mitgliedschaftseinstellungen aktualisiert!',
    'yes' => 'Ja',
    'no' => 'Nein',
    'delete' => 'Löschen',
    'confirm_remove_image' => 'Sind Sie sicher, dass Sie Ihr Profilbild entfernen möchten?',
    'info_updated_image_error' => 'Ihre Informationen wurden erfolgreich aktualisiert!<br>Aber wir konnten Ihr Profilbild nicht ändern!',
    'image_removed_success' => 'Ihr Profilbild wurde entfernt',
    'select_image' => 'Bild auswählen',
    'columns' => [
        'id' => 'ID',
        'username' => 'Benutzername',
        'email' => 'E-Mail',
        'name' => 'Name',
        'last_name' => 'Nachname',
        'status' => 'Kontostatus',
        'group_id' => 'Benutzergruppe',
        'image' => 'Bild',
        'city' => 'Stadt',
        'blog_name' => 'Blogname',
        'about' => 'Über mich',
    ],
    'radio' => [
        'publish_yes' => 'Ja',
        'publish_no' => 'Nein',
        'deck_yes' => 'Ja',
        'deck_no' => 'Nein',
    ],
    'active' => [
        'hello' => 'Hallo liebe/r <b>%s %s</b>.',
        'account_activated' => 'Ihr Konto unter %s wurde vom Administrator aktiviert.<br/>Sie können sich nun mit Ihrem Benutzernamen und Passwort anmelden.'
    ]
];

$lang['mod_user']['admin']['settings'] = [
    'open' => 'Offen',
    'closed' => 'Geschlossen',
    'new_user_group' => 'Neue Benutzergruppe',
    'membership_open' => 'Ist die Mitgliedschaft offen?',
    'membership_activation' => 'Mitgliedschaftsaktivierung',
    'automatic_approval' => 'Automatische Genehmigung',
    'email_approval' => 'E-Mail-Genehmigung',
    'admin_approval' => 'Admin-Genehmigung',
    'allow_multiple_logins' => 'Gleichzeitige Anmeldungen mit demselben Konto zulassen?',
    'yes' => 'Ja',
    'no' => 'Nein'
];

$lang['mod_user']['grid'] = [
    'showing_results' => 'Anzeige von {from} bis {to} von insgesamt {total} Ergebnissen.',
    'page' => 'Seite',
    'page_count' => 'Seitenanzahl',
    'loading_data' => 'Daten werden geladen, bitte warten...',
    'no_data_found' => 'Keine Daten gefunden',
    'connection_error' => 'Verbindungsfehler',
    'search' => 'Suche',
    'username' => 'Benutzername',
    'email' => 'E-Mail',
    'add_new_user' => 'Neuen Benutzer hinzufügen',
    'edit' => 'Bearbeiten',
    'refresh' => 'Aktualisieren',
    'delete' => 'Löschen',
    'user_list' => 'Benutzerliste',
    'select_row_edit' => 'Bitte wählen Sie eine Zeile zum Bearbeiten aus',
    'select_row_delete' => 'Bitte wählen Sie mindestens eine Zeile zum Löschen aus!',
    'confirm_delete' => "Insgesamt <font color=red>' + $('.trSelected',grid).length + '</font> Benutzer werden gelöscht. <br/>Sind Sie sicher, dass Sie diesen Vorgang ausführen möchten?</br> Denken Sie daran, dass alle von diesen Benutzern hinzugefügten Inhalte gelöscht werden!",
    'list_refreshed' => 'Benutzerliste aktualisiert',
    'user_deleted' => 'Benutzer gelöscht',
    'yes' => 'Ja',
    'cancel' => 'Abbrechen',
    'user_group' => 'Benutzergruppe',
    'activate' => 'Aktivieren',
    'deactivate' => 'Deaktivieren',
    'select_account_activate' => 'Bitte wählen Sie das Konto aus, das Sie aktivieren möchten',
    'confirm_activate' => 'Sind Sie sicher, dass Sie die ausgewählten Konten aktivieren möchten?',
    'accounts_activated' => 'Konten aktiviert!',
    'active' => 'Aktiv',
    'waiting_email_activation' => 'Wartet auf E-Mail-Aktivierung',
    'waiting_admin_activation' => 'Wartet auf Admin-Aktivierung.',
    'account_status' => 'Kontostatus',
    'select_at_least_one' => 'Bitte wählen Sie mindestens eine Zeile aus',
    'status' => [
        'key' => 'Aktivieren',
        'value' => 'Sind Sie sicher, dass Sie diesen Benutzer aktivieren möchten?',
    ],
    'deactivate_confirm' => [
        'key' => 'Deaktivieren',
        'value' => 'Sind Sie sicher, dass Sie diesen Benutzer deaktivieren möchten?',
    ],
];

$lang['mod_user']['register'] = [
    'registration_stopped' => 'Die Benutzerregistrierung wurde vorübergehend gestoppt.<br/>Vielen Dank für Ihr Verständnis.',
    'form_title' => 'Benutzer-Registrierungsformular',
    'your_name' => 'Ihr Name',
    'your_surname' => 'Ihr Nachname',
    'your_username' => 'Ihr Benutzername',
    'email' => 'E-Mail',
    'your_password' => 'Ihr Passwort',
    'confirm_password' => 'Passwort bestätigen',
    'your_phone' => 'Ihre Telefonnummer',
    'security_code' => 'Sicherheitscode',
    'register_button' => 'Registrieren',
    'fill_form' => 'Bitte füllen Sie das Formular aus, um sich zu registrieren.',
    'registration_success' => 'Ihre Mitgliedschaft wurde erfolgreich abgeschlossen! Sie können sich nun anmelden.',
    'registration_success_activation' => 'Ihre Mitgliedschaft wurde erfolgreich abgeschlossen. <br/>Bitte verifizieren Sie Ihr Konto, indem Sie auf den Aktivierungslink klicken, der an Ihre E-Mail gesendet wurde.',
    'membership_activation' => 'Mitgliedschaftsaktivierung',
    'password_reset_request' => 'Anfrage zum Zurücksetzen des Passworts',
    'your_new_password' => 'Ihr neues Passwort',
    'membership_registration' => 'Mitgliedschaftsregistrierung',
    'registration_success_admin' => 'Ihr Konto wurde erstellt. <br/>Es muss von einem Administrator genehmigt werden, bevor Sie sich anmelden können.',
    'error' => [
        'name_required' => 'Bitte geben Sie Ihren Namen an',
        'surname_required' => 'Bitte geben Sie Ihren Nachnamen an',
        'username_required' => 'Bitte geben Sie Ihren Benutzernamen an',
        'email_required' => 'Bitte geben Sie Ihre E-Mail-Adresse an',
        'phone_required' => 'Bitte geben Sie Ihre Mobiltelefonnummer an',
        'name_invalid_chars' => 'Ihr Name enthält ungültige Zeichen',
        'surname_invalid_chars' => 'Ihr Nachname enthält ungültige Zeichen',
        'username_length' => 'Ihr Benutzername muss zwischen 2 und 12 Zeichen lang sein',
        'username_length_error' => 'Ihr Benutzername muss zwischen 2 und 20 Zeichen lang sein',
        'username_invalid_chars' => 'Ihr Benutzername darf nur aus a-z A-Z 0-9 Zeichen bestehen',
        'password_length' => 'Ihr Passwort muss zwischen 6 und 16 Zeichen lang sein',
        'invalid_email' => 'Bitte geben Sie eine gültige E-Mail-Adresse an',
        'invalid_phone' => 'Bitte geben Sie eine gültige Mobiltelefonnummer an.',
        'username_registered' => 'Der angegebene Benutzername ist bereits im System registriert',
        'email_registered' => 'Die angegebene E-Mail-Adresse ist bereits im System registriert',
        'invalid_captcha' => 'Sie haben den Sicherheitscode falsch eingegeben.',
        'activation_email_error' => 'Beim Senden der Aktivierungs-E-Mail ist ein Fehler aufgetreten.'
    ],
    'js' => [
        'checking' => '⏳ Prüfung läuft...',
        'username_available' => '✅ Benutzername verfügbar',
        'username_exists' => '❌ Benutzername bereits vergeben',
        'username_short' => 'Benutzername muss mindestens 2 Zeichen lang sein',
        'email_available' => '✅ E-Mail verfügbar',
        'email_exists' => '❌ E-Mail bereits registriert',
        'check_failed' => '⚠️ Prüfung fehlgeschlagen',
        'register_success' => 'Mitgliedschaft erfolgreich erstellt!',
        'errors_title' => 'Fehler:',
        'validation_required' => 'Dieses Feld ist erforderlich',
        'validation_required_name' => 'Bitte geben Sie Ihren Namen ein',
        'validation_required_surname' => 'Bitte geben Sie Ihren Nachnamen ein',
        'validation_required_username' => 'Bitte geben Sie einen Benutzernamen ein',
        'validation_username_short' => 'Der Benutzername muss mindestens 2 Zeichen lang sein',
        'validation_username_long' => 'Der Benutzername darf höchstens 20 Zeichen lang sein',
        'validation_required_email' => 'Bitte geben Sie Ihre E-Mail-Adresse ein',
        'validation_invalid_email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein',
        'validation_required_password' => 'Bitte geben Sie ein Passwort ein',
        'validation_password_short' => 'Das Passwort muss mindestens 6 Zeichen lang sein',
        'validation_password_long' => 'Das Passwort darf höchstens 20 Zeichen lang sein',
        'validation_confirm_password' => 'Bitte bestätigen Sie Ihr Passwort',
        'validation_required_phone' => 'Bitte geben Sie Ihre Telefonnummer ein',
        'validation_required_captcha' => 'Bitte geben Sie den Sicherheitscode ein'
    ],
    'captcha_refresh' => 'Klicken Sie auf das Bild, um den Code zu aktualisieren',
    'country_codes' => [
        'select' => 'Land auswählen',
        'phone' => 'Telefon'
    ]
];

$lang['mod_user']['activation'] = [
    'title' => 'Kontoaktivierung',
    'write_code' => 'Bitte geben Sie Ihren Aktivierungscode in das untenstehende Feld ein.',
    'activation_code' => 'Aktivierungscode',
    'security_code' => 'Sicherheitscode',
    'activate_button' => 'Aktivieren',
    'lost_code' => 'Wenn Sie Ihren Code verloren haben, <a href="resend-activation">klicken Sie hier</a>.',
    'success_activated' => 'Konto erfolgreich aktiviert! <a href="login">Hier anmelden</a>.',
    'membership_activation' => 'Mitgliedschaftsaktivierung',
    'error' => [
        'invalid_captcha' => 'Sicherheitscode ist falsch.',
        'code_required' => 'Aktivierungscode ist erforderlich.',
        'invalid_code' => 'Ungültiger Aktivierungscode.'
    ]
];

$lang['mod_user']['resend_activation'] = [
    'code_sent' => 'Aktivierungscode gesendet. Bitte prüfen Sie Ihre E-Mails.',
    'write_email' => 'Geben Sie Ihre registrierte E-Mail-Adresse ein, um einen neuen Code zu erhalten.',
    'activation_code' => 'Aktivierungscode',
    'email' => 'E-Mail',
    'security_code' => 'Sicherheitscode',
    'send_button' => 'Senden',
    'activation_code_label' => 'Aktivierungscode',
    'error' => [
        'activation_send_error' => 'Beim Senden des Codes ist ein Fehler aufgetreten.',
        'invalid_captcha' => 'Sicherheitscode ist falsch.',
        'email_required' => 'E-Mail-Adresse ist erforderlich.',
        'invalid_email_format' => 'Ungültiges E-Mail-Format.',
        'email_not_registered' => 'E-Mail ist nicht registriert.',
        'account_already_active' => 'Konto ist bereits aktiv!',
        'admin_activation_required' => 'Konto erfordert eine Genehmigung durch den Administrator.',
        'no_activation_code_found' => 'Kein Aktivierungscode für diese E-Mail gefunden.'
    ]
];

$lang['mod_user']['forgot_password'] = [
    'info_1' => 'Wenn die E-Mail-Adresse zu einem Konto passt, werden die Anweisungen zum Zurücksetzen des Passworts in Kürze gesendet.',
    'info_2' => 'Geben Sie die E-Mail-Adresse Ihres Kontos ein. Wenn das Konto die Passwortwiederherstellung nutzen kann, senden wir einen Reset-Link.',
    'title' => 'Passwort zurücksetzen',
    'email' => 'E-Mail',
    'email_placeholder' => 'name@example.com',
    'security_code' => 'Sicherheitscode',
    'captcha_placeholder' => 'Code eingeben',
    'captcha_refresh' => 'Sicherheitscode aktualisieren',
    'send_button' => 'Senden',
    'back_to_login' => 'Zur Anmeldung',
    'forgot_password_title' => 'Passwort vergessen',
    'security_note' => 'Zu Ihrer Sicherheit können Reset-Links nur einmal verwendet werden.',
    'redirecting' => 'Ihre Anfrage wird geprüft...',
    'error' => [
        'reset_code_send_error' => 'Beim Senden des Reset-Codes ist ein Fehler aufgetreten.',
        'invalid_captcha' => 'Sicherheitscode ist falsch.',
        'captcha_required' => 'Sicherheitscode ist erforderlich.',
        'email_required' => 'E-Mail-Adresse ist erforderlich.',
        'invalid_email_format' => 'Ungültiges E-Mail-Format.',
        'email_not_registered' => 'E-Mail ist nicht registriert.'
    ]
];

$lang['mod_user']['password_reset'] = [
    'title' => 'Passwort zurücksetzen',
    'info' => 'Wählen Sie ein neues Passwort, um das Zurücksetzen abzuschließen.',
    'activation_code' => 'Aktivierungscode',
    'security_code' => 'Sicherheitscode',
    'send_button' => 'Senden',
    'success_message' => 'Ihr Passwort wurde zurückgesetzt. Sie können sich nun mit Ihrem neuen Passwort anmelden.',
    'error' => [
        'invalid_captcha' => 'Sicherheitscode ist falsch.',
        'code_required' => 'Reset-Code ist erforderlich.',
        'invalid_code' => 'Ungültiger Reset-Code.',
        'expired_code' => 'Dieser Reset-Link ist abgelaufen. Bitte fordern Sie einen neuen Link an.',
        'email_send_error' => 'Beim Senden der Passwort-E-Mail ist ein Fehler aufgetreten.'
    ]
];

$lang['mod_user']['panel'] = [
    'welcome' => 'Hallo %s',
    'description' => 'Sie können Ihr Konto und Ihre Einstellungen über dieses Panel verwalten.',
    'pending_content' => 'Ausstehend',
    'view_reports' => 'Berichte anzeigen',
    'welcome_main' => 'Willkommen,',
    'panel_title' => 'Admin-Panel',
    'goto_content' => 'Zum Inhalt gehen',
    'search_placeholder' => 'Suche...',
    'quick_access' => 'Schnellzugriff',
    'products_metrics_title' => 'Produkt-Snapshot',
    'metrics_active' => '%s aktive Datensätze',
    'metrics_products' => '%s Produktdatensätze',
    'metrics_clients' => '%s Kundendatensätze',
    'metrics_homepage' => '%s Homepage-Datensätze',
    'metrics_references' => '%s Referenzdatensätze',
    'metrics_product_categories' => '%s Produktkategorie-Datensätze',
    'family_quick_access' => 'Arbeitsbereich-Zugriff',
    'products_spotlight_title' => 'Produkte-Arbeitsbereich',
    'products_spotlight_copy' => 'Verwalten Sie Produkte, Kundenreferenzen und die unterstützende Taxonomie über eine zentrale Oberfläche.',
    'pinned_modules' => 'Angepinnte Module',
    'recent_workspace' => 'Letzter Arbeitsbereich',
    'add_product' => 'Produkt hinzufügen',
    'add_reference' => 'Referenz hinzufügen',
    'add_client' => 'Kunde hinzufügen',
    'add_service' => 'Dienstleistung hinzufügen',
    'manage_product_categories' => 'Kategorie-Arbeitsbereich öffnen',
    'manage_service_categories' => 'Dienstleistungskategorien öffnen',
    'workspace_nav_dashboard' => 'Dashboard',
    'workspace_nav_content' => 'Inhalt',
    'workspace_nav_media' => 'Medien',
    'workspace_nav_products' => 'Produkte',
    'workspace_nav_more' => 'Mehr',
    'more_sheet_title' => 'Weitere Tools',
    'more_sheet_copy' => 'Öffnen Sie seltener genutzte System- und Verwaltungsoberflächen, ohne die Hauptleiste zu überfüllen.',
    'more_sheet_open_drawer' => 'Vollständiges Modulverzeichnis öffnen',
    'home_title' => 'Startseite',
    'admin_brand' => 'AKM Business Admin'
];

$lang['mod_user']['fab'] = [
    'add_announcement' => 'Ankündigung hinzufügen',
    'add_product' => 'Produkt hinzufügen',
    'add_service' => 'Dienstleistung hinzufügen',
    'add_page' => 'Seite hinzufügen',
    'add_video_embed' => 'Video-Einbettung hinzufügen',
    'add_video' => 'Video hinzufügen',
    'add_content' => 'Nachricht hinzufügen',
    'add_publication' => 'Veröffentlichung hinzufügen',
    'add_announcement_alert' => 'Ankündigungsformular',
    'add_video_alert' => 'Videoformular',
    'add_content_alert' => 'Nachrichtenassistent',
    'add_publication_alert' => 'Veröffentlichungsassistent'
];

$lang['mod_user']['profile_menu'] = [
    'edit_profile' => 'Profil bearbeiten',
    'change_password' => 'Passwort ändern',
    'inbox' => 'Posteingang',
    'sent_box' => 'Gesendet',
    'new_message' => 'Neue Nachricht',
    'to_multiple_users' => 'An mehrere Benutzer',
    'to_specific_group' => 'An bestimmte Gruppe',
    'to_all_members' => 'An alle Mitglieder',
];

$lang['mod_user']['ajax'] = [
    'login_success' => 'Anmeldung erfolgreich',
    'login_error' => 'Ungültiger Benutzername oder Passwort',
    'validation_error' => 'Bitte beheben Sie die Fehler',
    'ssid_invalid' => 'Ungültige Sitzungs-ID',
    'membership_deleted' => 'Konto gelöscht',
    'success' => 'Erfolg',
    'error' => 'Ein Fehler ist aufgetreten',
    'copy_errors' => 'Fehler kopieren',
    'errors_copied' => 'Fehlerdetails kopiert',
    'unexpected_response' => 'Der Server hat eine unerwartete Antwort gesendet',
    'connection_error' => 'Verbindungsfehler',
    'invalid_credentials' => 'Ungültige Zugangsdaten',
    'account_inactive' => 'Konto inaktiv',
    'permission_denied' => 'Zugriff verweigert',
];

$lang['mod_user']['role_errors'] = [
    'add' => 'Keine Berechtigung zum Hinzufügen von Benutzern',
    'delete' => 'Keine Berechtigung zum Löschen von Benutzern',
    'edit' => 'Keine Berechtigung zum Bearbeiten von Benutzern',
    'list' => 'Keine Berechtigung zum Anzeigen der Benutzerliste',
    'profil' => 'Keine Berechtigung zum Anzeigen von Profilen',
    'settings' => 'Keine Berechtigung zum Zugriff auf Einstellungen',
    'register' => 'Keine Berechtigung zur Registrierung',
    'login' => 'Keine Berechtigung zur Anmeldung',
    'logout' => 'Keine Berechtigung zur Abmeldung',
    'activation' => 'Keine Berechtigung zur Kontoaktivierung',
    'forgot_password' => 'Keine Berechtigung zur Passwortwiederherstellung',
    'profil_resim' => 'Keine Berechtigung zum Ändern von Bildern',
    'acl' => 'Keine Berechtigung zur Verwaltung von Berechtigungen',
    'moderate_edit' => 'Keine Berechtigung, andere zu bearbeiten',
    'moderate_delete' => 'Keine Berechtigung, andere zu löschen',
];

$lang['mod_user']['roll'] = [
    'roles' => 'Benutzerrollen',
    'add' => 'Benutzer hinzufügen',
    'edit' => 'Benutzer bearbeiten',
    'delete' => 'Benutzer löschen',
    'list' => 'Verwaltung anzeigen',
    'acl' => 'Berechtigungen bearbeiten',
    'settings' => 'Einstellungen ändern',
    'link' => 'Admin-Links anzeigen',
    'login' => 'Anmelden',
    'logout' => 'Abmelden',
    'register' => 'Registrieren',
    'activation' => 'Konto aktivieren',
    'forgot_password' => 'Passwort zurücksetzen',
    'profil_resim' => 'Bild ändern',
    'moderate_edit' => 'Andere bearbeiten',
    'moderate_delete' => 'Andere löschen',
    'profil' => 'Profil anzeigen'
];

$lang['mod_user']['list'] = [
    'management' => 'Verwaltung',
    'action' => 'Aktion...',
    'apply' => 'Anwenden',
    'actions' => 'Aktionen',
    'select' => 'Auswählen...',
    'search' => 'Suchen',
    'reset' => 'Zurücksetzen',
];

$lang['mod_user']['datatable'] = [
    'sortAscending' => ': aufsteigend sortieren',
    'sortDescending' => ': absteigend sortieren',
    'emptyTable' => 'Keine Daten',
    'info' => 'Anzeige von _START_ bis _END_ von _TOTAL_',
    'infoEmpty' => 'Keine Einträge',
    'infoFiltered' => '(gefiltert)',
    'lengthMenu' => 'Zeige _MENU_',
    'search' => 'Suche:',
    'zeroRecords' => 'Keine Treffer',
    'loadingRecords' => 'Wird geladen...',
    'processing' => 'Verarbeitung...',
    'first' => 'Erste',
    'last' => 'Letzte',
    'next' => 'Nächste',
    'previous' => 'Vorherige',
    'selected_records' => '_TOTAL_ ausgewählt',
    'ajax_error' => 'Anfrage fehlgeschlagen',
    'print' => 'Drucken',
    'print_info' => 'Drücken Sie STRG+P',
    'copy' => 'Kopieren',
    'select_action' => 'Aktion auswählen',
    'loading_message' => 'Laden...',
];

$lang['mod_user']['filter'] = [
    'title' => 'Filtern & Sortieren',
    'sort_label' => 'Sortieren',
    'close' => 'Schließen',
    'newest' => 'Neueste',
    'oldest' => 'Älteste',
    'title_az' => 'A-Z',
    'title_za' => 'Z-A',
    'view_count' => 'Aufrufe'
];
