<?php
/**
 * User Module - German Language File
 *
 * @package Rojname
 * @module  mod_user
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direkter Zugriff auf diese Datei ist nicht erlaubt.');
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
    'error_fatal' => 'Schwerwiegender Fehler aufgetreten!',
    'remove_image' => 'Sind Sie sicher, dass Sie dieses Bild entfernen möchten?',
    'remove_image_oke' => 'Bild entfernt',
    'choose_wish' => 'Bitte wählen Sie aus, was Sie möchten',
    'remove_record' => 'Sind Sie sicher, dass Sie diesen Datensatz löschen möchten?',
    'message' => [
        'oke' => [
            'add' => 'Benutzer hinzugefügt!',
            'edit' => 'Benutzer aktualisiert!',
        ],
        'error' => [
            'add' => 'Benutzer konnte nicht hinzugefügt werden!',
            'edit' => 'Benutzer konnte nicht aktualisiert werden!',
        ],
    ],
    'errors' => [
        'record' => 'Der angegebene Datensatz konnte nicht gefunden werden!',
    ],
    'error' => [
        'req_username' => 'Das Feld Benutzername ist erforderlich',
        'req_password' => 'Das Feld Passwort ist erforderlich',
        'req_email' => 'Das Feld E-Mail ist erforderlich',
        'invalid_email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein',
        'min_password' => 'Das Passwort muss mindestens 6 Zeichen lang sein',
        'passwords_mismatch' => 'Die Passwörter stimmen nicht überein',
        'username_taken' => 'Benutzername bereits vergeben',
        'email_taken' => 'E-Mail-Adresse wird bereits verwendet',
        'invalid_credentials' => 'Benutzername oder Passwort ist falsch',
        'user_not_found' => 'Kein passender Benutzer gefunden',
        'account_inactive' => 'Ihr Konto ist noch nicht aktiv',
        'permission_error' => 'Berechtigungsfehler',
        'fill_all_fields' => 'Bitte füllen Sie alle Felder aus',
    ],
    'button' => [
        'add' => 'Hinzufügen',
        'edit' => 'Bearbeiten',
        'refresh' => 'Aktualisieren',
        'delete' => 'Löschen',
        'gallery' => 'Bildergalerie',
        'file' => 'Dateimanager',
        'cancel' => 'Abbrechen',
        'deactivate' => 'Entfernen',
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
        'normal_member' => 'Mitglied',
    ],
    'installer' => [
        'sql_error' => 'Beim Laden von users.sql ist ein Fehler aufgetreten',
        'settings_added' => '%s wurde zur Tabelle mod_user_settings hinzugefügt',
        'settings_error' => 'Beim Laden der Mitgliedschaftseinstellungen ist ein Fehler aufgetreten',
        'settings_exists' => '%s existiert bereits in der Tabelle mod_user_settings',
        'user_added' => '-----<b>%s</b> - Benutzer wurde der Datenbank hinzugefügt!',
        'user_error' => '-----<b>%s</b> - Benutzer konnte nicht zur Datenbank hinzugefügt werden!',
        'user_exists' => '----- <b>%s</b> - Benutzer existiert bereits in der Datenbank',
    ],
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
    'info_updated_image_error' => 'Ihre Informationen wurden erfolgreich aktualisiert!<br>
						Aber Ihr Profilbild konnte nicht geändert werden!',
    'image_removed_success' => 'Ihr Profilbild wurde entfernt',
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
];

$lang['mod_user']['admin']['settings'] = [
    'open' => 'Offen',
    'closed' => 'Geschlossen',
    'new_user_group' => 'Neue Benutzergruppe',
    'membership_open' => 'Ist die Mitgliedschaft geöffnet?',
    'membership_activation' => 'Mitgliedschaftsaktivierung',
    'automatic_approval' => 'Automatische Freigabe',
    'email_approval' => 'Freigabe per E-Mail',
    'admin_approval' => 'Freigabe durch Admin',
    'allow_multiple_logins' => 'Können sich zwei Personen gleichzeitig mit demselben Konto am System anmelden?',
    'yes' => 'Ja',
    'no' => 'Nein',
];

$lang['mod_user']['grid'] = [
    'showing_results' => 'Zeige {from} bis {to} von {total} Ergebnissen.',
    'page' => 'Seite',
    'page_count' => 'Seitenanzahl',
    'loading_data' => 'Daten werden geladen, bitte warten...',
    'no_data_found' => 'Keine Daten gefunden',
    'connection_error' => 'Verbindungsfehler',
    'search' => 'Suchen',
    'username' => 'Benutzername',
    'email' => 'E-Mail',
    'add_new_user' => 'Neuen Benutzer hinzufügen',
    'edit' => 'Bearbeiten',
    'refresh' => 'Aktualisieren',
    'delete' => 'Löschen',
    'user_list' => 'Benutzerliste',
    'select_row_edit' => 'Bitte wählen Sie eine Zeile zum Bearbeiten aus',
    'select_row_delete' => 'Bitte wählen Sie mindestens eine Zeile zum Löschen aus!',
    'confirm_delete' => "Insgesamt werden <font color=red>' + $('.trSelected',grid).length + '</font> Benutzer gelöscht. <br/>Möchten Sie diesen Vorgang wirklich durchführen?</br> Denken Sie daran, dass alle von diesen Benutzern hinzugefügten Inhalte, Kategorien usw. ebenfalls gelöscht werden!",
    'list_refreshed' => 'Benutzerliste aktualisiert',
    'user_deleted' => 'Benutzer gelöscht',
    'yes' => 'Ja',
    'cancel' => 'Abbrechen',
    'user_group' => 'Benutzergruppe',
    'activate' => 'Aktivieren',
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
    'deactivate' => [
        'key' => 'Deaktivieren',
        'value' => 'Sind Sie sicher, dass Sie diesen Benutzer deaktivieren möchten?',
    ],
];

$lang['mod_user']['error'] = [
    'specify_username' => 'Bitte geben Sie einen Benutzernamen an',
    'specify_password' => 'Bitte geben Sie ein Passwort an',
    'specify_email' => 'Bitte geben Sie eine E-Mail-Adresse an',
    'invalid_email' => 'Bitte geben Sie eine gültige E-Mail-Adresse an',
    'username_registered' => 'Der angegebene Benutzername ist bereits im System registriert',
    'email_registered' => 'Die angegebene E-Mail-Adresse ist bereits im System registriert',
    'username_invalid_chars' => 'Ihr Benutzername darf aus a-z A-Z 0-9 bestehen',
    'username_length_error' => 'Ihr Benutzername darf 3 bis 12 Zeichen lang sein',
    'password_length_error' => 'Ihr Passwort darf 6 bis 16 Zeichen lang sein',
    'username_not_found' => 'Der eingegebene Benutzername wurde nicht gefunden',
    'invalid_credentials' => 'Benutzername oder Passwort ist falsch.',
    'permission_error' => 'Berechtigungsfehler',
    'current_password_required' => 'Bitte geben Sie Ihr aktuelles Passwort ein',
    'new_password_required' => 'Bitte geben Sie Ihr neues Passwort ein',
    'confirm_password_required' => 'Bitte wiederholen Sie Ihr neues Passwort',
    'passwords_mismatch' => 'Neues Passwort und Passwortbestätigung müssen übereinstimmen.',
    'current_password_incorrect' => 'Das angegebene Passwort ist falsch',
    'name_required' => 'Bitte geben Sie Ihren Namen an',
    'surname_required' => 'Bitte geben Sie Ihren Nachnamen an',
    'dob_required' => 'Bitte geben Sie Ihr Geburtsdatum an',
    'name_length_error' => 'Ihr Name darf 2 bis 50 Zeichen lang sein',
    'surname_length_error' => 'Ihr Nachname darf 2 bis 50 Zeichen lang sein',
    'name_invalid_chars' => 'Ihr Name darf nur aus [^a-zA-Z] Zeichen bestehen',
    'surname_invalid_chars' => 'Ihr Nachname darf nur aus [^a-zA-Z] Zeichen bestehen',
    'date_invalid' => 'Bitte geben Sie ein gültiges Datum ein. Das Datumsformat muss YYYY-MM-DD sein.',
    'email_invalid_format' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
    'phone_invalid' => 'Die eingegebene Mobiltelefonnummer ist ungültig.',
    'account_not_active' => 'Ihr Konto wurde gefunden, ist aber nicht aktiv.<br/> Bitte aktivieren Sie Ihre Mitgliedschaft
						<a href="activation/" style="color:#000">hier</a>.',
    'ip_protection_error' => 'Für dieses Konto ist ein IP-Schutz definiert und Ihre IP-Adresse stimmt nicht überein',
    'multi_login_error' => 'Dieses Konto scheint derzeit nicht mit dem System verbunden zu sein. Möglicherweise haben Sie vergessen, Ihr Konto auf einem anderen Computer abzumelden!',
    'user_not_found' => 'Benutzer nicht gefunden!',
    'system_error' => 'Systemfehler',
];

$lang['mod_user']['admin']['active'] = [
    'hello' => 'Hallo lieber <b>%s %s</b>.',
    'account_activated' => 'Ihr Konto bei %s wurde vom Administrator aktiviert.<br/>
    Sie können sich jetzt mit Ihrem Benutzernamen und Passwort anmelden, wenn Sie möchten.',
];

$lang['mod_user']['register'] = [
    'registration_stopped' => 'Die Benutzerregistrierung wurde vorübergehend gestoppt.<br/>
		Vielen Dank für Ihr Verständnis.',
    'form_title' => 'Benutzerregistrierungsformular',
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
    'registration_success' => 'Ihre Mitgliedschaft wurde erfolgreich abgeschlossen! Sie können sich jetzt in Ihr Konto einloggen.',
    'registration_success_activation' => 'Ihre Mitgliedschaft wurde erfolgreich abgeschlossen. <br/>Ihr Konto ist jedoch noch nicht aktiv. Bitte bestätigen Sie Ihr Konto über den Aktivierungslink, der an Ihre E-Mail-Adresse gesendet wurde.',
    'membership_activation' => 'Mitgliedschaftsaktivierung',
    'password_reset_request' => 'Anfrage zum Zurücksetzen des Passworts',
    'your_new_password' => 'Ihr neues Passwort',
    'membership_registration' => 'Mitgliedschaftsregistrierung',
    'registration_success_admin' => 'Ihr Konto wurde erfolgreich erstellt. <br/>Aus Sicherheitsgründen muss Ihr Konto von einem Administrator freigegeben werden. Nach der Freigabe werden Sie per E-Mail benachrichtigt.',
];

$lang['mod_user']['register']['error'] = [
    'name_required' => 'Bitte geben Sie Ihren Namen an',
    'surname_required' => 'Bitte geben Sie Ihren Nachnamen an',
    'username_required' => 'Bitte geben Sie Ihren Benutzernamen an',
    'email_required' => 'Bitte geben Sie Ihre E-Mail-Adresse an',
    'phone_required' => 'Bitte geben Sie Ihre Mobiltelefonnummer an',
    'name_invalid_chars' => 'Ihr Name enthält ungültige Zeichen',
    'surname_invalid_chars' => 'Ihr Nachname enthält ungültige Zeichen',
    'username_length' => 'Ihr Benutzername darf 2 bis 12 Zeichen lang sein',
    'username_invalid_chars' => 'Ihr Benutzername darf aus a-z A-Z 0-9 bestehen',
    'password_length' => 'Ihr Passwort darf 6 bis 16 Zeichen lang sein',
    'invalid_email' => 'Bitte geben Sie eine gültige E-Mail-Adresse an',
    'invalid_phone' => 'Bitte geben Sie eine gültige Mobiltelefonnummer an.',
    'username_registered' => 'Der angegebene Benutzername ist bereits im System registriert',
    'email_registered' => 'Die angegebene E-Mail-Adresse ist bereits im System registriert',
    'invalid_captcha' => 'Sie haben den Sicherheitscode falsch eingegeben. Bitte prüfen Sie ihn und versuchen Sie es erneut.',
    'activation_email_error' => 'Beim Senden der Aktivierungs-E-Mail ist ein Fehler aufgetreten.<br/>
	 Bitte kontaktieren Sie den Systemadministrator. ',
];

$lang['mod_user']['register']['js'] = [
    'checking' => '⏳ Prüfung läuft...',
    'username_available' => '✅ Benutzername verfügbar',
    'username_exists' => '❌ Dieser Benutzername ist bereits vergeben',
    'username_short' => 'Der Benutzername muss mindestens 2 Zeichen lang sein',
    'email_available' => '✅ E-Mail verfügbar',
    'email_exists' => '❌ Diese E-Mail ist bereits registriert',
    'check_failed' => '⚠️ Prüfung konnte nicht durchgeführt werden',
    'register_success' => 'Ihre Mitgliedschaft wurde erfolgreich erstellt! Sie können sich nun anmelden.',
    'errors_title' => 'Fehler:',
    'validation_required' => 'Dieses Feld ist erforderlich',
    'validation_required_name' => 'Bitte geben Sie Ihren Namen ein',
    'validation_required_surname' => 'Bitte geben Sie Ihren Nachnamen ein',
    'validation_required_username' => 'Bitte geben Sie einen Benutzernamen ein',
    'validation_username_short' => 'Der Benutzername muss mindestens 2 Zeichen lang sein',
    'validation_username_long' => 'Der Benutzername darf nicht länger als 20 Zeichen sein',
    'validation_required_email' => 'Bitte geben Sie Ihre E-Mail-Adresse ein',
    'validation_invalid_email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein',
    'validation_required_password' => 'Bitte geben Sie ein Passwort ein',
    'validation_password_short' => 'Das Passwort muss mindestens 6 Zeichen lang sein',
    'validation_password_long' => 'Das Passwort darf nicht länger als 20 Zeichen sein',
    'validation_confirm_password' => 'Bitte bestätigen Sie Ihr Passwort',
    'validation_required_phone' => 'Bitte geben Sie Ihre Telefonnummer ein',
    'validation_required_captcha' => 'Bitte geben Sie den Sicherheitscode ein',
];

$lang['mod_user']['register']['captcha_refresh'] = 'Klicken Sie auf das Bild, um den Code zu aktualisieren';

$lang['mod_user']['register']['country_codes'] = [
    'select' => 'Land auswählen',
    'phone' => 'Telefon',
];

$lang['mod_user']['activation'] = [
    'title' => 'Kontoaktivierung',
    'write_code' => 'Bitte geben Sie Ihren Aktivierungscode in das Feld unten ein.',
    'activation_code' => 'Aktivierungscode',
    'security_code' => 'Sicherheitscode',
    'activate_button' => 'Aktivieren',
    'lost_code' => 'Falls Sie Ihren Aktivierungscode verloren haben,
	<a href="re_activation" style="color:#000">klicken Sie hier</a>, um einen neuen Aktivierungscode anzufordern.',
    'success_activated' => 'Ihr Konto wurde erfolgreich aktiviert. Wenn Sie möchten,
	<a href="mod_user/login" style="color:#000">klicken Sie hier</a>, um sich in Ihr Konto einzuloggen.',
    'membership_activation' => 'Mitgliedschaftsaktivierung',
];

$lang['mod_user']['activation']['error'] = [
    'invalid_captcha' => 'Sie haben den Sicherheitscode falsch eingegeben. Bitte überprüfen Sie Ihren Aktivierungscode
		und versuchen Sie es erneut.',
    'code_required' => 'Bitte geben Sie Ihren Aktivierungscode ein.',
    'invalid_code' => 'Der angegebene Aktivierungscode ist ungültig,
		bitte überprüfen Sie ihn und versuchen Sie es erneut.',
];

$lang['mod_user']['re_activation'] = [
    'code_sent' => 'Ihr Aktivierungscode wurde gesendet. Bitte prüfen Sie Ihre E-Mail, um Ihr Konto zu aktivieren',
    'write_email' => 'Damit wir Ihnen einen Aktivierungscode senden können,<br/> geben Sie bitte die mit Ihrem Konto verknüpfte E-Mail-Adresse in das Feld unten ein.',
    'activation_code' => 'Aktivierungscode',
    'email' => 'E-Mail',
    'security_code' => 'Sicherheitscode',
    'send_button' => 'Senden',
    'activation_code_label' => 'Aktivierungscode',
];

$lang['mod_user']['re_activation']['error'] = [
    'activation_send_error' => 'Beim Senden des Aktivierungscodes ist ein Fehler aufgetreten. Bitte kontaktieren Sie den Systemadministrator.',
    'invalid_captcha' => 'Sie haben den Sicherheitscode falsch eingegeben. Bitte überprüfen Sie ihn und versuchen Sie es erneut.',
    'email_required' => 'Bitte geben Sie die mit Ihrem Konto verknüpfte E-Mail-Adresse ein.',
    'invalid_email_format' => 'Sie haben eine ungültige E-Mail-Adresse eingegeben.',
    'email_not_registered' => 'Die angegebene E-Mail-Adresse ist keinem Konto zugeordnet.',
    'account_already_active' => 'Das Konto zu der eingegebenen E-Mail-Adresse ist bereits aktiv!',
    'admin_activation_required' => 'Das Konto zu der eingegebenen E-Mail-Adresse kann nur durch einen Administrator aktiviert werden.',
    'no_activation_code_found' => 'Für die eingegebene E-Mail-Adresse wurde kein Aktivierungscode gefunden!',
];

$lang['mod_user']['forgot_password'] = [
    'info_1' => 'Wir haben Ihnen eine E-Mail gesendet, damit Sie Ihr Passwort zurücksetzen können.<br/>Bitte prüfen Sie Ihre E-Mail und folgen Sie den dort beschriebenen Schritten.',
    'info_2' => 'Damit wir Ihr Passwort zurücksetzen können,<br/> geben Sie bitte die mit Ihrem Konto verknüpfte E-Mail-Adresse in das Feld unten ein.',
    'title' => 'Passwort zurücksetzen',
    'email' => 'E-Mail',
    'security_code' => 'Sicherheitscode',
    'send_button' => 'Senden',
    'forgot_password_title' => 'Passwort vergessen',
];

$lang['mod_user']['forgot_password']['error'] = [
    'reset_code_send_error' => 'Beim Senden des Passwort-Reset-Codes ist ein Fehler aufgetreten. Bitte kontaktieren Sie den Systemadministrator.',
    'invalid_captcha' => 'Sie haben den Sicherheitscode falsch eingegeben. Bitte überprüfen Sie ihn und versuchen Sie es erneut.',
    'email_required' => 'Bitte geben Sie die mit Ihrem Konto verknüpfte E-Mail-Adresse ein.',
    'invalid_email_format' => 'Sie haben eine ungültige E-Mail-Adresse eingegeben.',
    'email_not_registered' => 'Die angegebene E-Mail-Adresse ist nicht im System registriert.',
];

$lang['mod_user']['password_reset'] = [
    'title' => 'Passwort zurücksetzen',
    'info' => 'Um ein neues Passwort zu erzeugen, geben Sie bitte Ihren Aktivierungscode für das Zurücksetzen des Passworts in das Feld unten ein.',
    'activation_code' => 'Aktivierungscode',
    'security_code' => 'Sicherheitscode',
    'send_button' => 'Senden',
    'success_message' => 'Herzlichen Glückwunsch, Ihr neues Passwort wurde an Ihre E-Mail-Adresse gesendet.<br/>Bitte prüfen Sie Ihr E-Mail-Postfach.',
];

$lang['mod_user']['password_reset']['error'] = [
    'invalid_captcha' => 'Sie haben den Sicherheitscode falsch eingegeben. Bitte überprüfen Sie ihn und versuchen Sie es erneut.',
    'code_required' => 'Bitte geben Sie Ihren Aktivierungscode ein.',
    'invalid_code' => 'Der angegebene Aktivierungscode ist ungültig. Bitte überprüfen Sie ihn und versuchen Sie es erneut.',
    'email_send_error' => 'Beim Senden der E-Mail ist ein Fehler aufgetreten. Bitte kontaktieren Sie den Systemadministrator!',
];

$lang['mod_user']['panel'] = [
    'welcome' => 'Hallo lieber %s',
    'description' => 'Sie können über die Menüs auswählen, was Sie tun möchten. Ihre Mitgliedschaftseinstellungen können Sie oben rechts ändern.',
    'total_content' => 'Gesamter Inhalt',
    'pending_content' => 'Ausstehend',
    'draft_content' => 'Entwürfe',
    'view_reports' => 'Detaillierte Berichte anzeigen',
    'welcome_main' => 'Willkommen,',
    'panel_title' => 'Admin-Panel',
    'goto_content' => 'Zur Inhaltsverwaltung',
    'search_placeholder' => 'Inhalte suchen...',
    'quick_access' => 'Schnellzugriff',
    'home_title' => 'Startseite',
    'admin_brand' => 'Rojname Admin',
];

$lang['mod_user']['fab'] = [
    'add_announcement' => 'Ankündigung hinzufügen',
    'add_video' => 'Video hinzufügen',
    'add_content' => 'Inhalt hinzufügen',
    'add_announcement_alert' => 'Ankündigungsformular',
    'add_video_alert' => 'Videoformular',
    'add_content_alert' => 'Inhaltsassistent'
];

$lang['mod_user']['profile_menu'] = [
    'edit_profile' => 'Profil bearbeiten',
    'change_password' => 'Passwort ändern',
    'inbox' => 'Posteingang',
    'sent_box' => 'Gesendet',
    'new_message' => 'Neue Nachricht',
    'to_multiple_users' => 'An mehrere Benutzer',
    'to_specific_group' => 'An eine bestimmte Gruppe',
    'to_all_members' => 'An alle Mitglieder',
];

$lang['mod_user']['ajax'] = [
    'login_success' => 'Anmeldung erfolgreich',
    'login_error' => 'Benutzername oder Passwort ist falsch',
    'validation_error' => 'Bitte korrigieren Sie die Fehler und versuchen Sie es erneut',
    'ssid_invalid' => 'Ungültige SSID',
    'membership_deleted' => 'Die Mitgliedschaft wurde möglicherweise gelöscht',
    'success' => 'Erfolg',
    'error' => 'Beim Anmelden ist ein Fehler aufgetreten',
    'connection_error' => 'Verbindungsfehler. Bitte versuchen Sie es erneut.',
    'invalid_credentials' => 'Ungültiger Benutzername oder Passwort',
    'account_inactive' => 'Ihr Konto ist nicht aktiv',
    'permission_denied' => 'Zugriff verweigert',
];

$lang['mod_user']['role_errors'] = [
    'add' => 'Sie haben keine Berechtigung, Benutzer hinzuzufügen',
    'delete' => 'Sie haben keine Berechtigung, Benutzer zu löschen',
    'edit' => 'Sie haben keine Berechtigung, Benutzer zu bearbeiten',
    'list' => 'Sie haben keine Berechtigung, die Benutzerliste anzuzeigen',
    'profil' => 'Sie haben keine Berechtigung, Benutzerprofile anzuzeigen',
    'settings' => 'Sie haben keine Berechtigung, auf Benutzereinstellungen zuzugreifen',
    'register' => 'Sie haben keine Berechtigung, auf die Benutzerregistrierung zuzugreifen',
    'login' => 'Sie haben keine Berechtigung, auf die Benutzeranmeldung zuzugreifen',
    'logout' => 'Sie haben keine Berechtigung, auf die Benutzerabmeldung zuzugreifen',
    'activation' => 'Sie haben keine Berechtigung, auf die Benutzeraktivierung zuzugreifen',
    'forgot_password' => 'Sie haben keine Berechtigung, auf die Passwortwiederherstellung zuzugreifen',
    'profil_resim' => 'Sie haben keine Berechtigung, Profilbilder zu ändern',
    'acl' => 'Sie haben keine Berechtigung, Benutzerberechtigungen zu verwalten',
    'moderate_edit' => 'Sie haben keine Berechtigung, andere Benutzer zu bearbeiten',
    'moderate_delete' => 'Sie haben keine Berechtigung, andere Benutzer zu löschen',
];

$lang['mod_user']['validation']['username_min_length'] = 'Der Benutzername muss mindestens 2 Zeichen lang sein';

$lang['mod_user']['js_modal'] = [
    'pleaseFillAll' => 'Bitte füllen Sie alle Felder aus',
    'connectionError' => 'Verbindungsfehler. Bitte versuchen Sie es erneut.',
    'timeoutError' => 'Zeitüberschreitung der Anfrage',
    'networkError' => 'Netzwerkfehler - bitte überprüfen Sie Ihre Verbindung',
    'serverError' => 'Fehler in der Serverantwort',
    'signingIn' => 'Anmeldung läuft...',
    'loginSuccess' => 'Anmeldung erfolgreich!',
    'redirecting' => 'Weiterleitung...',
    'loginFailed' => 'Anmeldung fehlgeschlagen',
    'alternativeOptions' => 'Alternative Optionen',
];

$lang['mod_user']['list'] = [
    'management' => 'Datensatzverwaltung',
    'action' => 'Aktion...',
    'apply' => 'Anwenden',
    'actions' => 'Aktionen',
    'select' => 'Auswählen...',
    'search' => 'Suchen',
    'reset' => 'Zurücksetzen',
];

$lang['mod_user']['datatable'] = [
    'sortAscending' => ': aktivieren, um die Spalte aufsteigend zu sortieren',
    'sortDescending' => ': aktivieren, um die Spalte absteigend zu sortieren',
    'emptyTable' => 'Keine Daten in der Tabelle verfügbar',
    'info' => '<span class=\'seperator\'>|</span> Zeige <b>_START_</b> bis <b>_END_</b> von <b>_TOTAL_</b> Einträgen',
    'infoEmpty' => 'Keine Einträge gefunden',
    'infoFiltered' => '(gefiltert aus _MAX_ Gesamteinträgen)',
    'lengthMenu' => '<span class=\'seperator\'>|</span> Zeige _MENU_ Einträge',
    'search' => 'Suchen:',
    'zeroRecords' => 'Keine passenden Datensätze gefunden',
    'loadingRecords' => 'Laden...',
    'processing' => 'Verarbeitung...',
    'first' => 'Erste',
    'last' => 'Letzte',
    'next' => 'Nächste',
    'previous' => 'Vorherige',
    'selected_records' => '_TOTAL_ Datensätze ausgewählt:  ',
    'ajax_error' => 'Anfrage konnte nicht abgeschlossen werden. Bitte überprüfen Sie Ihre Internetverbindung.',
    'print' => 'Drucken',
    'print_info' => 'Bitte drücken Sie "STRG+P" zum Drucken und "ESC" zum Beenden.',
    'copy' => 'Kopieren',
    'select_action' => 'Bitte wählen Sie eine Aktion aus',
    'loading_message' => 'Verarbeitung...',
];

$lang['mod_user']['roll'] = [
    'roles' => 'Benutzerrollen',
    'add' => 'Kann Benutzer hinzufügen',
    'edit' => 'Kann Benutzer bearbeiten',
    'delete' => 'Kann Benutzer löschen',
    'list' => 'Kann Benutzerverwaltung anzeigen',
    'acl' => 'Kann Benutzerberechtigungen bearbeiten',
    'settings' => 'Kann Benutzereinstellungen ändern',
    'link' => 'Kann Admin-Links anzeigen',
    'login' => 'Kann sich anmelden',
    'logout' => 'Kann sich abmelden',
    'register' => 'Kann sich registrieren',
    'activation' => 'Kann Konto aktivieren',
    'forgot_password' => 'Kann Passwort zurücksetzen',
    'profil_resim' => 'Kann Profilbild ändern',
    'moderate_edit' => 'Kann andere Benutzer bearbeiten',
    'moderate_delete' => 'Kann andere Benutzer löschen',
    'profil' => 'Kann Profil anzeigen',
];

$lang['mod_user']['filter'] = [
    'title' => 'Filtern & Sortieren',
    'sort_label' => 'Sortierreihenfolge',
    'close' => 'Schließen',
    'newest' => 'Neueste',
    'oldest' => 'Älteste',
    'title_az' => 'Titel (A-Z)',
    'title_za' => 'Titel (Z-A)',
    'view_count' => 'Aufrufanzahl',
];


// --- Otomatîk Zêdekirin (Senkronîzasyon) ---
$lang['mod_user']['forgot_password']['1'] = 'Şifrenizi Sıfırlayabilmeniz İçin Size Bir E-Posta Gönderdik.<br/>Lütfen E-Posta\'nızı Kontrol Ederek E-Posta\'da  Belirtilen Adımları İzleyin.';
$lang['mod_user']['forgot_password']['password'] = 'Şifrenizi Sıfırlayabilmemiz İçin.<br/> Aşağıdaki Alana Hesabınızın Bağlı Olduğu E-Posta Adresini Yazın.';
$lang['mod_user']['forgot_password']['3'] = 'Şifre Sıfırlama';
$lang['mod_user']['forgot_password']['4'] = 'E-Posta';
$lang['mod_user']['forgot_password']['5'] = 'Güvelik Kodu';
$lang['mod_user']['forgot_password']['6'] = 'Gönder';
$lang['mod_user']['forgot_password']['7'] = 'Şifremi Unuttum';
$lang['mod_user']['forgot_password']['error']['1'] = 'Şifre Sıfırlama Kodu Gönderilirken Hata Oluştu Lütfen Sistem Yöneticisi İle İrtibata Geçin.';
$lang['mod_user']['forgot_password']['error']['2'] = 'Güvenlik Kodunu Hatalı Girdiniz, Lütfen Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['forgot_password']['error']['3'] = 'Lütfen Hesabınızın Bağlı Olduğu E-Posta Adresini Yazın';
$lang['mod_user']['forgot_password']['error']['4'] = 'Geçersiz Bir E-Posta Adresi Yazdınız.';
$lang['mod_user']['forgot_password']['error']['5'] = 'Belirtmiş Olduğunuz E-Posta Adresi Herhangi Bir Hesaba Kayıtlı Değil.';
$lang['mod_user']['register']['1'] = 'Geçici Bir Süre İçin Üye Alımı Durdurulmuştur.<br/>
		Anlayışınız İçin Teşekkür Ederiz.';
$lang['mod_user']['register']['2'] = 'Üye Kayıt Formu';
$lang['mod_user']['register']['3'] = 'Adınız';
$lang['mod_user']['register']['4'] = 'Soyadınız';
$lang['mod_user']['register']['5'] = 'Kullanıcı Adınız';
$lang['mod_user']['register']['6'] = 'E-Posta';
$lang['mod_user']['register']['7'] = 'Şifreniz';
$lang['mod_user']['register']['8'] = 'Şifre Tekrar';
$lang['mod_user']['register']['9'] = 'Telefon Numaranız';
$lang['mod_user']['register']['10'] = 'Güvenlik Kodu';
$lang['mod_user']['register']['11'] = 'Kayıt Ol';
$lang['mod_user']['register']['12'] = 'Lütfen kayıt olmak için formu doldurun.';
$lang['mod_user']['register']['13'] = 'Üyeliğiniz Başarıyla Tamamlandı! Hesabınıza şimdi giriş yapabilirsiniz.';
$lang['mod_user']['register']['14'] = 'Üyeliğiniz Başarıyla Tamamlandı. <br/>Ancak hesabınız henüz aktif değil. Lütfen e-posta adresinize gönderdiğimiz aktivasyon bağlantısına tıklayarak hesabınızı doğrulayın.';
$lang['mod_user']['register']['15'] = 'Üyelik Aktivasyonu';
$lang['mod_user']['register']['16'] = 'Şifre Sıfırlama İsteği';
$lang['mod_user']['register']['17'] = 'Yeni Şifreniz';
$lang['mod_user']['register']['18'] = 'Üyelik Kaydı';
$lang['mod_user']['register']['19'] = 'Hesabınız başarıyla oluşturuldu. <br/>Güvenlik nedeniyle hesabınızın bir yönetici tarafından approvenması gerekmektedir. Onay işleminden sonra size e-posta ile bilgi verilecektir.';
$lang['mod_user']['register']['error']['1'] = 'Lütfen Adınızı Belirtin';
$lang['mod_user']['register']['error']['2'] = 'Lütfen Soyadınızı Belirtin';
$lang['mod_user']['register']['error']['3'] = 'Lütfen Kullanıcı Adınızı Belirtin';
$lang['mod_user']['register']['error']['4'] = 'Lütfen E-Posta Adresinizi Belirtin';
$lang['mod_user']['register']['error']['5'] = 'Lütfen Cep Telefon Numaranızı Belirtin';
$lang['mod_user']['register']['error']['6'] = 'Adınızda Geçersiz Karekterler Bulunmakta';
$lang['mod_user']['register']['error']['7'] = 'Soyadınızda Geçersiz Karekterler Bulunmakta';
$lang['mod_user']['register']['error']['8'] = 'Kullanıcı Adınız En Az 2 En Fazla 12 Karekterden Oluşabilir';
$lang['mod_user']['register']['error']['9'] = 'Kullanıcı Adınız a-z A-Z 0-9 Karekterlerinden Oluşabilir';
$lang['mod_user']['register']['error']['10'] = 'Şifreniz En Az 6 En Fazla 16 Karekterden Oluşabilir';
$lang['mod_user']['register']['error']['11'] = 'Lütfen Geçerli Bir E-Posta Adresi Belirtin';
$lang['mod_user']['register']['error']['12'] = 'Lütfen Geçerli Bir Cep Telefon Numarası Belirtin.';
$lang['mod_user']['register']['error']['13'] = 'Belirtmiş Olduğunuz Kullanıcı Adı Sistemde Kayıtlı';
$lang['mod_user']['register']['error']['14'] = 'Belirtmiş Olduğunuz E-Posta Adresi Sistemde Kayıtlı';
$lang['mod_user']['register']['error']['15'] = 'Güvenlik Kodunu Hatalı Girdiniz, Lütfen Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['register']['error']['16'] = 'Aktivasyon E-Maili Gönderilirken Hata Oluştu.<br/>
	 Lütfen Sistem Yöneticisi İle İrtibata Geçin. ';
$lang['mod_user']['32'] = '%s okunmamış mesajınız var.';
$lang['mod_user']['admin']['2'] = 'Üyeyi Ekle';
$lang['mod_user']['admin']['4'] = 'Yeni Üye Ekle';
$lang['mod_user']['admin']['7'] = 'Üyeyi Düzenle';
$lang['mod_user']['admin']['10'] = 'Roller';
$lang['mod_user']['admin']['11'] = 'Permissionleri Yenile';
$lang['mod_user']['admin']['13'] = 'Üye Grubu *';
$lang['mod_user']['admin']['15'] = 'Üyelik Ayarları';
$lang['mod_user']['admin']['16'] = 'Üyelik Ayarlarını Güncelle';
$lang['mod_user']['admin']['18'] = 'Evet';
$lang['mod_user']['admin']['19'] = 'Hayır';
$lang['mod_user']['admin']['21'] = 'Profil Resminizi Kaldırmak İstediğinize Eminmisiniz?';
$lang['mod_user']['admin']['22'] = 'Bilgileriniz Başarıyla Güncellendi!<br>
						Fakat Profil Resminizi Değiştiremedik!';
$lang['mod_user']['admin']['23'] = 'Profil Resminiz Kaldırıldı';
$lang['mod_user']['admin']['settings']['1'] = 'Açık';
$lang['mod_user']['admin']['settings']['2'] = 'Kapalı';
$lang['mod_user']['admin']['settings']['3'] = 'Yeni Üye Grubu';
$lang['mod_user']['admin']['settings']['4'] = 'Üyelik Açıkmı';
$lang['mod_user']['admin']['settings']['5'] = 'Üyelik Aktivasyonu';
$lang['mod_user']['admin']['settings']['6'] = 'Otomatik Onaylı';
$lang['mod_user']['admin']['settings']['7'] = 'E-Posta Onaylı';
$lang['mod_user']['admin']['settings']['8'] = 'Admin Onaylı';
$lang['mod_user']['admin']['settings']['9'] = 'Bir Hesapla Aynı Anda İki Kişi Sisteme Bağlanabilsinmi ?';
$lang['mod_user']['admin']['settings']['10'] = 'Evet';
$lang['mod_user']['admin']['settings']['11'] = 'Hayır';
$lang['mod_user']['admin']['active']['1'] = 'Merhabalar Sayın <b>%s %s</b>.';
$lang['mod_user']['admin']['active']['2'] = '%s Adresinde Bulunan Hesabınızın Yönetici Tarafından Aktifleştirildi<br/>
Dilerseniz Artık Kullanıcı Adı Ve Şifrenizle Giriş Yapabilirsiniz.';
$lang['mod_user']['grid']['1'] = 'Toplam {total} Sonuçtan {from} den {to}\\\'a Kadar Olanlar Görüntüleniyor.';
$lang['mod_user']['grid']['2'] = 'Sayfa';
$lang['mod_user']['grid']['3'] = 'Sayfa Sayısı';
$lang['mod_user']['grid']['4'] = 'Veriler Yükleniyor, Lütfen Bekleyin ...';
$lang['mod_user']['grid']['5'] = 'Hiçbir Veri Bulunamadı';
$lang['mod_user']['grid']['6'] = 'Bağlantı Hatası';
$lang['mod_user']['grid']['7'] = 'Arama';
$lang['mod_user']['grid']['8'] = 'Kullanıcı Adı';
$lang['mod_user']['grid']['9'] = 'E-Posta';
$lang['mod_user']['grid']['10'] = 'Yeni Üye Ekle';
$lang['mod_user']['grid']['11'] = 'Edit';
$lang['mod_user']['grid']['12'] = 'Yenile';
$lang['mod_user']['grid']['13'] = 'Sil';
$lang['mod_user']['grid']['14'] = 'Üye Listesi';
$lang['mod_user']['grid']['15'] = 'Düzenleme yapmak için lütfen bir satır seçin';
$lang['mod_user']['grid']['16'] = 'Lütfen Silmek İçin En Az Bir Satır Seçin!';
$lang['mod_user']['grid']['17'] = 'Toplam <font color=red>\' + $(\'.trSelected\',grid).length + \'</font> Üye Silinecek. <br/>Bu İşlemi Yapmak İstediğinize Emin misiniz?</br> Unutmayınki Bu Üyelerin Ekledikleri İçerikler Kategoriler vs.. Herşey Silinecek!';
$lang['mod_user']['grid']['18'] = 'Üye Listesi Yenilendi';
$lang['mod_user']['grid']['19'] = 'Üye Silindi';
$lang['mod_user']['grid']['20'] = 'Evet';
$lang['mod_user']['grid']['21'] = 'Vazgeç';
$lang['mod_user']['grid']['22'] = 'Üye Grubu';
$lang['mod_user']['grid']['23'] = 'Aktifleştir';
$lang['mod_user']['grid']['24'] = 'Lütfen Aktifleştirmek İstediğiniz Hesabı Seçin';
$lang['mod_user']['grid']['25'] = 'Seçili Hesapları Aktifleştirmek İstediğinize Eminmisiniz?';
$lang['mod_user']['grid']['26'] = 'Hesaplar Aktifleştirildi!';
$lang['mod_user']['grid']['27'] = 'Aktif';
$lang['mod_user']['grid']['28'] = 'E-Posta Aktivasyonu Bekleniyor';
$lang['mod_user']['grid']['29'] = 'Yönetici Aktivasyonu Bekleniyor.';
$lang['mod_user']['grid']['30'] = 'Hesap Durumu';
$lang['mod_user']['grid']['31'] = 'Lütfen En Az Bir Satır Seçin';
$lang['mod_user']['activation']['1'] = 'Hesap Aktivasyonu';
$lang['mod_user']['activation']['2'] = 'Lütfen Aşağıdaki Alana Aktivasyon Kodunuzu Yazınız.';
$lang['mod_user']['activation']['3'] = 'Aktivasyon Kodu';
$lang['mod_user']['activation']['4'] = 'Güvenlik Kodu';
$lang['mod_user']['activation']['5'] = 'Aktifleştir';
$lang['mod_user']['activation']['6'] = 'Eğer Aktifleştme Kodunuzu Kaybettiyseniz
	<a href="re_activation" style="color:#000">Buradan</a> Tekrar Aktivasyon Kodu Talep Edebilirsiniz.';
$lang['mod_user']['activation']['7'] = 'Hesabınız Başarıyla Aktifleştirildi Dilerseniz
	<a href="mod_user/login" style="color:#000">Buradan</a> Hesabınıza Giriş Yapabilirsiniz.';
$lang['mod_user']['activation']['8'] = 'Üyelik Aktivasyonu';
$lang['mod_user']['activation']['error']['1'] = 'Güvenlik Kodunu Hatalı Girdiniz, Lütfen Aktivasyon Kodunuzu
		Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['activation']['error']['2'] = 'Lütfen Aktivasyon Kodunuzu Yazın.';
$lang['mod_user']['activation']['error']['3'] = 'Belirttiğiniz Aktivasyon Kodu Geçersiz,
		Lütfen Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['re_activation']['1'] = 'Aktivasyon Kodunuz Gönderildi Lütfen E-Posta\'nızı Kontrol Ederek Hesabınızı Aktifleştirin';
$lang['mod_user']['re_activation']['2'] = 'Size Aktivasyon Kodu Göndere Bilmemiz İçin.<br/> Aşağıdaki Alana Hesabınızın Bağlı Olduğu E-Posta Adresini Yazın.';
$lang['mod_user']['re_activation']['3'] = 'Aktivasyon Kodu';
$lang['mod_user']['re_activation']['4'] = 'E-Posta';
$lang['mod_user']['re_activation']['5'] = 'Güvelik Kodu';
$lang['mod_user']['re_activation']['6'] = 'Gönder';
$lang['mod_user']['re_activation']['7'] = 'Aktivasyon Kodu';
$lang['mod_user']['re_activation']['error']['1'] = 'Aktivasyon Kodu Gönderilirken Hata Oluştu Lütfen Sistem Yöneticisi İle İrtibata Geçin.';
$lang['mod_user']['re_activation']['error']['2'] = 'Güvenlik Kodunu Hatalı Girdiniz, Lütfen Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['re_activation']['error']['3'] = 'Lütfen Hesabınızın Bağlı Olduğu E-Posta Adresini Yazın';
$lang['mod_user']['re_activation']['error']['4'] = 'Geçersiz Bir E-Posta Adresi Yazdınız.';
$lang['mod_user']['re_activation']['error']['5'] = 'Belirtmiş Olduğunuz E-Posta Adresi Herhangi Bir Hesaba Kayıtlı Değil.';
$lang['mod_user']['re_activation']['error']['6'] = 'Yazmış Olduğunuz E-Posta Adresine Bağlı Hesap Zaten Aktif Durumda!';
$lang['mod_user']['re_activation']['error']['7'] = 'Yazmış Olduğunuz E-Posta Adresine Bağlı Hesap Sadece Yönetici Tarafından Aktifleştirilebilinir.';
$lang['mod_user']['re_activation']['error']['8'] = 'Yazmış Olduğunuz E-Posta Adresine Ait Aktivasyon Kodu Bulunmuyor!';
$lang['mod_user']['password_reset']['1'] = 'Şifre Sıfırlama';
$lang['mod_user']['password_reset']['2'] = 'Yeni Şifrenizi Üretebilmemiz İçin,Lütfen Aşağıdaki Alana Şifre Sıfırlama Aktivasyon Kodunuzu Yazınız.';
$lang['mod_user']['password_reset']['3'] = 'Aktivasyon Kodu';
$lang['mod_user']['password_reset']['4'] = 'Güvenlik Kodu';
$lang['mod_user']['password_reset']['5'] = 'Gönder';
$lang['mod_user']['password_reset']['6'] = 'Tebrikler,Yeni Şifreniz E-Posta Adresinize Gönderildi.<br/>Lütfen E-Posta Adresinizi Kontrol Ediniz.';
$lang['mod_user']['password_reset']['error']['1'] = 'Güvenlik Kodunu Hatalı Girdiniz,Lütfen Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['password_reset']['error']['2'] = 'Lütfen Aktivasyon Kodunu Yazın.';
$lang['mod_user']['password_reset']['error']['3'] = 'Belirttiğiniz Aktivasyon Kodu Geçersiz,
		Lütfen Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['password_reset']['error']['4'] = 'E-Posta Gönderilirken Hata Oluştur Lütfen Sistem Yöneticisi İle İrtibata Geçin!';
