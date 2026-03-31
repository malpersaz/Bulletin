<?php
/**
 * User Module - Swedish Language File
 *
 * @package Rojname
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
    'add' => 'Lägg till användare',
    'edit' => 'Redigera användare',
    'list' => 'Användarlista',
    'information' => 'Information!',
    'oke' => 'Åtgärden lyckades!',
    'success' => 'Framgång!',
    'last_step' => 'Sista steget',
    'error_title' => 'Ett fel uppstod!',
    'error_label' => 'Fel!',
    'error' => 'Allvarligt fel uppstod!',
    'remove_image' => 'Är du säker på att du vill ta bort den här bilden?',
    'remove_image_oke' => 'Bilden borttagen',
    'choose_wish' => 'Välj om du vill',
    'remove_record' => 'Är du säker på att du vill radera den här posten?',
    'message' =>
        [
            'oke' =>
                [
                    'add' => 'Användare tillagd!',
                    'edit' => 'Användare uppdaterad!',
                ],
            'error' =>
                [
                    'add' => 'Användaren kunde inte läggas till!',
                    'edit' => 'Användaren kunde inte uppdateras!',
                ],
        ],
    'errors' =>
        [
            'record' => 'Den angivna posten kunde inte hittas!',
        ],
    'hata' =>
        [
            '1' => 'Användarnamn krävs',
            '2' => 'Lösenord krävs',
            '3' => 'E-post krävs',
            '4' => 'Ange en giltig e-postadress',
            '5' => 'Lösenordet måste vara minst 6 tecken',
            '6' => 'Lösenorden matchar inte',
            '7' => 'Användarnamnet är redan upptaget',
            '8' => 'E-postadressen används redan',
            '9' => 'Användarnamn eller lösenord är felaktigt',
            '10' => 'Ingen sådan användare hittades',
            '11' => 'Ditt konto är inte aktivt än',
            '12' => 'Behörighetsfel',
            '13' => 'Fyll i alla fält',
        ],
    'button' =>
        [
            'add' => 'Lägg till',
            'edit' => 'Redigera',
            'refresh' => 'Uppdatera',
            'delete' => 'Radera',
            'gallery' => 'Bildgalleri',
            'file' => 'Filhanterare',
            'cancel' => 'Avbryt',
            'deactivate' => 'Ta bort',
            'yes' => 'Ja',
            'no' => 'Nej',
            'select_image' => 'Välj bild',
        ],
    '1' => 'Användarnamn',
    '2' => 'Lösenord',
    '3' => 'E-post',
    '4' => 'Adminpanel',
    '5' => 'Användarpanel',
    '6' => 'Inloggningen lyckades. Vänta medan du omdirigeras..',
    '7' => 'Du har inte behörighet att utföra denna åtgärd',
    '8' => 'Logga in.',
    '9' => 'Logga in',
    '10' => 'Logga ut',
    '11' => 'Lösenordsbyte',
    '12' => 'Ändra',
    'current_password' => 'Nuvarande lösenord',
    '14' => 'Nytt lösenord',
    '15' => 'Bekräfta nytt lösenord',
    '16' => 'Ditt lösenord har ändrats!',
    '17' => 'Ändra information',
    '18' => 'Ditt namn',
    '19' => 'Ditt efternamn',
    '20' => 'Din e-postadress',
    '21' => 'Ditt födelsedatum',
    '22' => 'Din information har uppdaterats!',
    '23' => 'Mobiltelefon',
    '24' => 'Profilbild',
    '25' => 'Glömt lösenord',
    '26' => 'Kontoaktivering',
    '27' => 'Registrera',
    '28' => 'Stad',
    '29' => 'Bloggnamn',
    '30' => 'Om mig',
    '31' => 'Max 255 tecken.',
    'unread_messages_count' => 'Du har %s olästa meddelanden.',
    'groups' => [
        'Site Yöneticisi' => 'Webbplatsansvarig',
        'Editörler' => 'Redaktörer',
        'Normal Üye' => 'Medlem'
    ]
];

$lang['mod_user']['admin'] = [
    'add_area' => 'Område för att lägga till användare',
    '2' => 'Lägg till användare',
    'added' => 'Användare tillagd!',
    '4' => 'Lägg till ny användare',
    'list' => 'Användarlista',
    'edit_area' => 'Användarredigering',
    '7' => 'Redigera användare',
    'updated' => 'Användaren har uppdaterats',
    'acl' => 'Rollkontroll för användargrupper (ACL)',
    '10' => 'Roller',
    '11' => 'Uppdatera behörigheter',
    'permissions_updated' => 'Användarbehörigheter har uppdaterats',
    '13' => 'Användargrupp',
    'management' => 'Medlemshantering',
    '15' => 'Medlemsinställningar',
    'site_settings' => 'Webbplatsinställningar',
    'social_media' => 'Sociala Medier',
    '16' => 'Uppdatera medlemsinställningar',
    'settings_updated' => 'Medlemsinställningar uppdaterade!',
    '18' => 'Ja',
    '19' => 'Nej',
    'delete' => 'Radera',
    '21' => 'Är du säker på att du vill ta bort din profilbild?',
    '22' => 'Din information har uppdaterats!<br>Men vi kunde inte ändra din profilbild!',
    '23' => 'Din profilbild har tagits bort',
    'columns' =>
        [
            'id' => 'ID',
            'username' => 'Användarnamn',
            'email' => 'E-post',
            'name' => 'Namn',
            'last_name' => 'Efternamn',
            'status' => 'Kontostatus',
            'group_id' => 'Användargrupp',
            'image' => 'Bild',
            'city' => 'Stad',
            'blog_name' => 'Bloggnamn',
            'about' => 'Om mig',
        ],
    'radio' =>
        [
            'publish_yes' => 'Ja',
            'publish_no' => 'Nej',
            'deck_yes' => 'Ja',
            'deck_no' => 'Nej',
        ],
];

$lang['mod_user']['admin']['settings'] = [
    '1' => 'Öppen',
    '2' => 'Stängd',
    '3' => 'Ny användargrupp',
    '4' => 'Är medlemskap öppet?',
    '5' => 'Medlemskapsaktivering',
    '6' => 'Automatiskt godkännande',
    '7' => 'E-postgodkännande',
    '8' => 'Admin-godkännande',
    '9' => 'Kan två personer ansluta till systemet med samma konto samtidigt?',
    '10' => 'Ja',
    '11' => 'Nej'
];

// Flexi Grid Required
$lang['mod_user']['grid'] = [
    '1' => 'Visar {from} till {to} av {total} resultat.',
    '2' => 'Sida',
    '3' => 'Sidantal',
    '4' => 'Laddar data, vänligen vänta...',
    '5' => 'Inga data hittades',
    '6' => 'Anslutningsfel',
    '7' => 'Sök',
    '8' => 'Användarnamn',
    '9' => 'E-post',
    '10' => 'Lägg till ny användare',
    '11' => 'Redigera',
    '12' => 'Uppdatera',
    'delete' => 'Radera',
    '14' => 'Användarlista',
    '15' => 'Välj en rad att redigera',
    '16' => 'Välj minst en rad att radera!',
    '17' => "Totalt <font color=red>' + $('.trSelected',grid).length + '</font> användare kommer att raderas. <br/>Är du säker på att du vill utföra denna åtgärd?</br> Kom ihåg att allt innehåll som lagts till av dessa användare, kategorier etc.. Allt kommer att raderas!",
    '18' => 'Användarlistan uppdaterad',
    '19' => 'Användare raderad',
    '20' => 'Ja',
    '21' => 'Avbryt',
    '22' => 'Användargrupp',
    '23' => 'Aktivera',
    '24' => 'Välj det konto du vill aktivera',
    '25' => 'Är du säker på att du vill aktivera de valda kontona?',
    '26' => 'Konton aktiverade!',
    '27' => 'Aktiv',
    '28' => 'Väntar på e-postaktivering',
    '29' => 'Väntar på admin-aktivering.',
    '30' => 'Kontostatus',
    '31'=>  'Välj minst en rad',
    'status' =>
        [
            'key' => 'Aktivera',
            'value' => 'Är du säker på att du vill aktivera den här användaren?',
        ],
    'deactivate' =>
        [
            'key' => 'Inaktivera',
            'value' => 'Är du säker på att du vill inaktivera den här användaren?',
        ],
];

// Errors
$lang['mod_user']['error'] = [
    '1' => 'Ange ett användarnamn',
    '2' => 'Ange ett lösenord',
    '3' => 'Ange en e-postadress',
    '4' => 'Ange en giltig e-postadress',
    '5' => 'Det angivna användarnamnet är registrerat i systemet',
    '6' => 'Den angivna e-postadressen är registrerad i systemet',
    '7' => 'Ditt användarnamn kan bestå av tecknen a-z A-Z 0-9',
    '8' => 'Ditt användarnamn kan vara 3 till 12 tecken',
    '9' => 'Ditt lösenord kan vara 6 till 16 tecken',
    '10' => 'Användarnamnet du angav kunde inte hittas',
    '11' => 'Användarnamn eller lösenord är felaktigt.',
    '12' => 'Behörighetsfel',
    '13' => 'Skriv ditt nuvarande lösenord',
    '14' => 'Skriv ditt nya lösenord',
    '15' => 'Upprepa ditt nya lösenord',
    '16' => 'Ditt nya lösenord och bekräftelsen av det nya lösenordet måste vara desamma.',
    '17' => 'Det angivna lösenordet är felaktigt',
    '18' => 'Ange ditt namn',
    '19' => 'Ange ditt efternamn',
    '20' => 'Ange ditt födelsedatum',
    '21' => 'Ditt namn kan vara 2 till 50 tecken',
    '22' => 'Ditt efternamn kan vara 2 till 50 tecken',
    '23' => 'Ditt namn kan innehålla tecknen [^a-zA-Z]',
    '24' => 'Ditt efternamn kan innehålla tecknen [^a-zA-Z]',
    '25' => 'Ange ett giltigt datum. Datumformat ska vara ÅÅÅÅ-MM-DD.',
    '26' => 'Skriv en giltig e-postadress.',
    '27' => 'Mobiltelefonnumret du angav är ogiltigt.',
    '28' => 'Ditt konto hittades men är inte aktivt.<br/> Aktivera ditt medlemskap <a href="activation/" style="color:#000">här</a>.',
    '29' => 'IP-skydd är definierat för detta konto och din IP-adress matchar inte',
    '30' => 'Detta konto verkar inte vara anslutet till systemet just nu, du kan ha glömt att öppna ditt konto på en annan dator!',
    '31' => 'Användaren hittades inte!',
    'unread_messages_count' => 'Systemfel'
];

$lang['mod_user']['admin']['active'] = [
    '1' => 'Hej kära <b>%s %s</b>.',
    '2' => 'Ditt konto på %s har aktiverats av administratören<br/>Du kan nu logga in med ditt användarnamn och lösenord om du vill.'
];

// User Registration
$lang['mod_user']['register'] = [
    '1' => 'Användarregistrering har tillfälligt stoppats.<br/>Tack för din förståelse.',
    '2' => 'Registreringsformulär',
    '3' => 'Ditt namn',
    '4' => 'Ditt efternamn',
    '5' => 'Ditt användarnamn',
    '6' => 'E-post',
    '7' => 'Ditt lösenord',
    '8' => 'Bekräfta lösenord',
    '9' => 'Ditt telefonnummer',
    '10' => 'Säkerhetskod',
    '11' => 'Registrera',
    '12' => 'Fyll i formuläret för att registrera dig.',
    '13' => 'Ditt medlemskap har skapats! Du kan nu logga in på ditt konto.',
    '14' => 'Ditt medlemskap har skapats. <br/>Ditt konto är dock inte aktivt än. Verifiera ditt konto genom att klicka på aktiveringslänken som skickats till din e-postadress.',
    '15' => 'Medlemskapsaktivering',
    '16' => 'Begäran om återställning av lösenord',
    '17' => 'Ditt nya lösenord',
    '18' => 'Medlemsregistrering',
    '19' => 'Ditt konto har skapats. <br/>Av säkerhetsskäl måste ditt konto godkännas av en administratör. Du kommer att meddelas via e-post efter godkännande.'
];

$lang['mod_user']['register']['error'] = [
    '1' => 'Ange ditt namn',
    '2' => 'Ange ditt efternamn',
    '3' => 'Ange ditt användarnamn',
    '4' => 'Ange din e-postadress',
    '5' => 'Ange ditt mobiltelefonnummer',
    '6' => 'Det finns ogiltiga tecken i ditt namn',
    '7' => 'Det finns ogiltiga tecken i ditt efternamn',
    '8' => 'Ditt användarnamn kan vara 2 till 12 tecken',
    '9' => 'Ditt användarnamn kan bestå av tecknen a-z A-Z 0-9',
    '10' => 'Ditt lösenord kan vara 6 till 16 tecken',
    '11' => 'Ange en giltig e-postadress',
    '12' => 'Ange ett giltigt mobiltelefonnummer.',
    '13' => 'Det angivna användarnamnet är registrerat i systemet',
    '14' => 'Den angivna e-postadressen är registrerad i systemet',
    '15' => 'Du angav säkerhetskoden felaktigt, kontrollera och försök igen.',
    '16' => 'Ett fel uppstod när aktiverings-e-postmeddelandet skickades.<br/> Kontakta systemadministratören.'
];

// JavaScript validation messages
$lang['mod_user']['register']['js'] = [
    'checking' => '⏳ Kontrollerar...',
    'username_available' => '✅ Användarnamn tillgängligt',
    'username_exists' => '❌ Detta användarnamn är redan upptaget',
    'username_short' => 'Användarnamnet måste vara minst 2 tecken',
    'email_available' => '✅ E-post tillgänglig',
    'email_exists' => '❌ Denna e-postadress är redan registrerad',
    'check_failed' => '⚠️ Kontroll kunde inte utföras',
    'register_success' => 'Ditt medlemskap har skapats! Du kan logga in.',
    'errors_title' => 'Fel:',
    'validation_required' => 'Detta fält är obligatoriskt',
    'validation_required_name' => 'Ange ditt namn',
    'validation_required_surname' => 'Ange ditt efternamn',
    'validation_required_username' => 'Ange ett användarnamn',
    'validation_username_short' => 'Användarnamnet måste vara minst 2 tecken',
    'validation_username_long' => 'Användarnamnet får inte överstiga 20 tecken',
    'validation_required_email' => 'Ange din e-postadress',
    'validation_invalid_email' => 'Ange en giltig e-postadress',
    'validation_required_password' => 'Ange ett lösenord',
    'validation_password_short' => 'Lösenordet måste vara minst 6 tecken',
    'validation_password_long' => 'Lösenordet får inte överstiga 20 tecken',
    'validation_confirm_password' => 'Bekräfta ditt lösenord',
    'validation_required_phone' => 'Ange ditt telefonnummer',
    'validation_required_captcha' => 'Ange säkerhetskoden'
];

// Captcha helper text
$lang['mod_user']['register']['captcha_refresh'] = 'Klicka på bilden för att uppdatera koden';

// Country codes
$lang['mod_user']['register']['country_codes'] = [
    'select' => 'Välj land',
    'phone' => 'Telefon'
];

// Activation
$lang['mod_user']['activation'] = [
    '1' => 'Kontoaktivering',
    '2' => 'Skriv din aktiveringskod i fältet nedan.',
    '3' => 'Aktiveringskod',
    '4' => 'Säkerhetskod',
    '5' => 'Aktivera',
    '6' => 'Om du har förlorat din aktiveringskod <a href="resend-activation" style="color:#000">Klicka här</a> för att begära en ny aktiveringskod.',
    '7' => 'Ditt konto har aktiverats. Om du vill <a href="login" style="color:#000">Klicka här</a> för att logga in på ditt konto.',
    '8' => 'Medlemskapsaktivering'
];
$lang['mod_user']['activation']['error'] = [
    '1' => 'Du angav säkerhetskoden felaktigt, kontrollera din aktiveringskod och försök igen.',
    '2' => 'Skriv din aktiveringskod.',
    '3' => 'Den angivna aktiveringskoden är ogiltig, kontrollera och försök igen.'
];

$lang['mod_user']['resend_activation'] = [
    '1' => 'Din aktiveringskod har skickats. Kontrollera din e-post för att aktivera ditt konto',
    '2' => 'Så att vi kan skicka en aktiveringskod till dig.<br/> Skriv e-postadressen som är kopplad till ditt konto i fältet nedan.',
    '3' => 'Aktiveringskod',
    '4' => 'E-post',
    '5' => 'Säkerhetskod',
    '6' => 'Skicka',
    '7' => 'Aktiveringskod'
];
$lang['mod_user']['resend_activation']['error'] = [
    '1' => 'Du angav säkerhetskoden felaktigt, kontrollera din aktiveringskod och försök igen.',
    '2' => 'Skriv din e-postadress.',
    '3' => 'Den angivna aktiveringskoden är ogiltig, kontrollera och försök igen.'
];

// Password Reset
$lang['mod_user']['forgot_password'] = [
    '1' => 'Återställning av lösenord',
    '2' => 'Skriv din e-postadress i fältet nedan.',
    '3' => 'E-postadress',
    '4' => 'Skicka',
    '5' => 'Avbryt',
    '6' => 'E-postadress krävs!',
    '7' => 'Ogiltig e-postadress!',
    '8' => 'Skriv en giltig e-postadress.'
];
$lang['mod_user']['forgot_password']['error'] = [
    '1' => 'Den angivna e-postadressen är inte registrerad i systemet!',
    '2' => 'E-posten du angav är inte registrerad i systemet!',
    '3' => 'Den angivna e-postadressen är ogiltig, kontrollera och försök igen.'
];

$lang['mod_user']['sifre'] = [
    '1' => 'Återställning av lösenord',
    '2' => 'Skriv ditt nya lösenord i fältet nedan.',
    '3' => 'Nytt lösenord',
    '4' => 'Bekräfta nytt lösenord',
    '5' => 'Ändra',
    '6' => 'Lösenord krävs!',
    '7' => 'Bekräftelse av lösenord krävs!',
    '8' => 'Ditt nya lösenord måste vara minst 6 tecken.',
    '9' => 'Lösenorden matchar inte!',
    '10' => 'Lösenordsåterställningslänken du använde är ogiltig eller har löpt ut. Begär en ny återställning.',
    '11' => 'Ditt lösenord har ändrats. Du kan nu logga in med ditt nya lösenord.'
];
$lang['mod_user']['sifre']['error'] = [
    '1' => 'Lösenordsåterställningslänken du använde är ogiltig eller har löpt ut. Begär en ny återställning.'
];

// Panel Welcome Messages
$lang['mod_user']['panel'] = [
    'welcome' => 'Hej kära %s',
    'description' => 'Du kan välja vad du vill göra från menyerna. Du kan ändra dina medlemsinställningar uppe till höger.',
    'pending_content' => 'Väntande',
    'view_reports' => 'Visa detaljerade rapporter',
    'welcome_main' => 'Välkommen,',
    'panel_title' => 'Adminpanel',
    'goto_content' => 'Gå till innehållshantering',
    'search_placeholder' => 'Sök innehåll...',
    'quick_access' => 'Snabbåtkomst',
    'home_title' => 'Startsida',
    'admin_brand' => 'Rojname Admin',
];

// User Profile Menu Items
$lang['mod_user']['profile_menu'] = [
    'edit_profile' => 'Redigera profil',
    'change_password' => 'Ändra lösenord',
    'inbox' => 'Inkorg',
    'sent_box' => 'Skickat',
    'new_message' => 'Nytt meddelande',
    'to_multiple_users' => 'Till flera användare',
    'to_specific_group' => 'Till en specifik grupp',
    'to_all_members' => 'Till alla medlemmar',
];

// AJAX Responses
$lang['mod_user']['ajax'] = [
    'login_success' => 'Inloggningen lyckades',
    'login_error' => 'Användarnamn eller lösenord är felaktigt',
    'validation_error' => 'Korrekt felen och försök igen',
    'ssid_invalid' => 'Ogiltigt SSID',
    'membership_deleted' => 'Medlemskapet kan ha raderats',
    'success' => 'Framgång',
    'error' => 'Fel uppstod under inloggningen',
    'connection_error' => 'Anslutningsfel. Försök igen.',
    'invalid_credentials' => 'Ogiltigt användarnamn eller lösenord',
    'account_inactive' => 'Ditt konto är inte aktivt',
    'permission_denied' => 'Åtkomst nekad',
];

// === Restriction Errors  ===
$lang['mod_user']['role_errors'] = [
    'add' => 'Du har inte behörighet att lägga till användare',
    'delete' => 'Du har inte behörighet att radera användare',
    'edit' => 'Du har inte behörighet att redigera användare',
    'list' => 'Du har inte behörighet att se användarlistan',
    'profil' => 'Du har inte behörighet att se användarprofiler',
    'settings' => 'Du har inte behörighet att komma åt användarinställningar',
    'register' => 'Du har inte behörighet att komma åt användarregistrering',
    'login' => 'Du har inte behörighet att komma åt användarinloggning',
    'logout' => 'Du har inte behörighet att komma åt användarutloggning',
    'activation' => 'Du har inte behörighet att komma åt kontoaktivering',
    'forgot_password' => 'Du har inte behörighet att komma åt lösenordsåterställning',
    'profil_resim' => 'Du har inte behörighet att ändra profilbilder',
    'acl' => 'Du har inte behörighet att hantera användarbehörigheter',
    'moderate_edit' => 'Du har inte behörighet att redigera andra användare',
    'moderate_delete' => 'Du har inte behörighet att radera andra användare',
];

// Additional Validation Messages
$lang['mod_user']['validation']['username_min_length'] = 'Användarnamnet måste vara minst 2 tecken';

// JavaScript Modal Messages
$lang['mod_user']['js_modal'] = [
    'pleaseFillAll' => 'Fyll i alla fält',
    'connectionError' => 'Anslutningsfel. Försök igen.',
    'timeoutError' => 'Begäran tog för lång tid',
    'networkError' => 'Nätverksfel - kontrollera din anslutning',
    'serverError' => 'Servern svarade med ett fel',
    'signingIn' => 'Loggar in...',
    'loginSuccess' => 'Inloggningen lyckades!',
    'redirecting' => 'Omdirigerar...',
    'loginFailed' => 'Inloggningen misslyckades',
    'alternativeOptions' => 'Alternativa alternativ',
];

// List Page
$lang['mod_user']['list'] = [
    'management' => 'Posthantering',
    'action' => 'Åtgärd...',
    'apply' => 'Verkställ',
    'actions' => 'Åtgärder',
    'select' => 'Välj...',
    'search' => 'Sök',
    'reset' => 'Återställ',
];

// DataTable
$lang['mod_user']['datatable'] = [
    'sortAscending' => ': aktivera för att sortera kolumnen stigande',
    'sortDescending' => ': aktivera för att sortera kolumnen fallande',
    'emptyTable' => 'Inga data tillgängliga i tabellen',
    'info' => '<span class=\'seperator\'>|</span> Visar <b>_START_</b> till <b>_END_</b> av <b>_TOTAL_</b> poster',
    'infoEmpty' => 'Inga poster hittades',
    'infoFiltered' => '(filtrerad från _MAX_ totala poster)',
    'lengthMenu' => '<span class=\'seperator\'>|</span> Visa _MENU_ poster',
    'search' => 'Sök:',
    'zeroRecords' => 'Inga matchande poster hittades',
    'loadingRecords' => 'Laddar...',
    'processing' => 'Bearbetar...',
    'first' => 'Första',
    'last' => 'Sista',
    'next' => 'Nästa',
    'previous' => 'Föregående',
    'selected_records' => '_TOTAL_ poster valda: ',
    'ajax_error' => 'Kunde inte slutföra begäran. Kontrollera din internetanslutning.',
    'print' => 'Skriv ut',
    'print_info' => 'Tryck på "CTRL+P" för att skriva ut och "ESC" för att avsluta.',
    'copy' => 'Kopiera',
    'select_action' => 'Välj en åtgärd',
    'loading_message' => 'Bearbetar...',
];

// Roles
$lang['mod_user']['roll'] = [
    'roles' => 'Användarroller',
    'add' => 'Kan lägga till användare',
    'edit' => 'Kan redigera användare',
    'delete' => 'Kan radera användare',
    'list' => 'Kan se användarhantering',
    'acl' => 'Kan redigera användarbehörigheter',
    'settings' => 'Kan ändra användarinställningar',
    'link' => 'Kan se adminlänkar',
    'login' => 'Kan logga in',
    'logout' => 'Kan logga ut',
    'register' => 'Kan registrera sig',
    'activation' => 'Kan aktivera konto',
    'forgot_password' => 'Kan återställa lösenord',
    'profil_resim' => 'Kan ändra profilbild',
    'moderate_edit' => 'Kan redigera andra användare',
    'moderate_delete' => 'Kan radera andra användare',
    'profil' => 'Kan se profil'
];

// Filter & Sort Generic Keys
$lang['mod_user']['filter'] = [
    'title' => 'Filtrera & Sortera',
    'sort_label' => 'Sorteringsordning',
    'close' => 'Stäng',
    'newest' => 'Nyast',
    'oldest' => 'Äldst',
    'title_az' => 'Titel (A-Ö)',
    'title_za' => 'Titel (Ö-A)',
    'view_count' => 'Antal visningar'
];

// FAB Menu
$lang['mod_user']['fab'] = [
    'add_announcement' => 'Add Announcement',
    'add_video' => 'Add Video',
    'add_content' => 'Add Content',
    'add_publication' => 'Add Publication',
    'add_announcement_alert' => 'Add Announcement Form',
    'add_video_alert' => 'Add Video Form',
    'add_content_alert' => 'Add Content Wizard',
    'add_publication_alert' => 'Add Publication Wizard'
];

// --- Otomatîk Zêdekirin (Senkronîzasyon) ---
$lang['mod_user']['password_strength']['weak'] = 'Zayıf';
$lang['mod_user']['password_strength']['fair'] = 'Orta';
$lang['mod_user']['password_strength']['good'] = 'İyi';
$lang['mod_user']['password_strength']['strong'] = 'Güçlü';
$lang['mod_user']['password_match']['success'] = 'Şifreler eşleşti';
$lang['mod_user']['password_match']['error'] = 'Şifreler uyuşmuyor';
$lang['mod_user']['js']['updating'] = 'Güncelleniyor...';
$lang['mod_user']['js']['warning'] = 'Uyarı';
$lang['mod_user']['js']['weak_password'] = 'Zayıf Şifre';
$lang['mod_user']['js']['password_len_req'] = 'En az 6 karakter';
$lang['mod_user']['js']['password_complex_req'] = 'Büyük, küçük harf ve rakamlar';
$lang['mod_user']['error_fatal'] = 'Ölümcül Hata Oluştu!';
$lang['mod_user']['username'] = 'Kullanıcı Adı *';
$lang['mod_user']['password'] = 'Şifre *';
$lang['mod_user']['email'] = 'E-posta *';
$lang['mod_user']['admin_panel'] = 'Yönetim Paneli';
$lang['mod_user']['user_panel'] = 'Üye Paneli';
$lang['mod_user']['login_success_wait'] = 'Giriş Başarılı. Lütfen Bekleyin Yönlendiriliyorsunuz..';
$lang['mod_user']['no_permission'] = 'Bu İşlemi Yapmaya Permissionniz Yok';
$lang['mod_user']['please_login'] = 'Lütfen Giriş yapın.';
$lang['mod_user']['login'] = 'Giriş';
$lang['mod_user']['logout'] = 'Çıkış';
$lang['mod_user']['password_change'] = 'Şifre Değişikliği';
$lang['mod_user']['change'] = 'Değiştir';
$lang['mod_user']['current_password_label'] = 'Şimdiki Şifreniz';
$lang['mod_user']['new_password'] = 'Yeni Şifreniz';
$lang['mod_user']['confirm_new_password'] = 'Yeni Şifre Tekrar';
$lang['mod_user']['password_changed_success'] = 'Şifreniz Başarıyla Değiştirildi!';
$lang['mod_user']['info_change'] = 'Bilgi Değişikliği';
$lang['mod_user']['your_name'] = 'Adınız *';
$lang['mod_user']['your_surname'] = 'Soyadınız *';
$lang['mod_user']['your_email'] = 'E-Posta Adresiniz';
$lang['mod_user']['your_dob'] = 'Doğum Tarihiniz';
$lang['mod_user']['info_updated_success'] = 'Bilgileriniz başarıyla güncellendi!';
$lang['mod_user']['mobile_phone'] = 'Cep No *';
$lang['mod_user']['profile_picture'] = 'Profil Resmi';
$lang['mod_user']['forgot_password']['info_2'] = 'Şifrenizi Sıfırlayabilmemiz İçin.<br/> Aşağıdaki Alana Hesabınızın Bağlı Olduğu E-Posta Adresini Yazın.';
$lang['mod_user']['forgot_password']['error']['4'] = 'Geçersiz Bir E-Posta Adresi Yazdınız.';
$lang['mod_user']['forgot_password']['error']['5'] = 'Belirtmiş Olduğunuz E-Posta Adresi Herhangi Bir Hesaba Kayıtlı Değil.';
$lang['mod_user']['forgot_password']['error']['reset_code_send_error'] = 'Error occurred while sending password reset code. Please contact the system administrator.';
$lang['mod_user']['forgot_password']['error']['invalid_captcha'] = 'You entered the security code incorrectly, please check and try again.';
$lang['mod_user']['forgot_password']['error']['email_required'] = 'Please write the e-mail address associated with your account.';
$lang['mod_user']['forgot_password']['error']['invalid_email_format'] = 'You entered an invalid e-mail address.';
$lang['mod_user']['forgot_password']['error']['email_not_registered'] = 'The specified e-mail address is not registered to any account.';
$lang['mod_user']['forgot_password']['info_1'] = 'We have sent you an e-mail so you can reset your password.<br/>Please check your e-mail and follow the steps specified in the e-mail.';
$lang['mod_user']['forgot_password']['info_2'] = 'So that we can reset your password.<br/> Write the e-mail address associated with your account in the field below.';
$lang['mod_user']['forgot_password']['title'] = 'Password Reset';
$lang['mod_user']['forgot_password']['email'] = 'E-mail';
$lang['mod_user']['forgot_password']['security_code'] = 'Security Code';
$lang['mod_user']['forgot_password']['send_button'] = 'Send';
$lang['mod_user']['forgot_password']['forgot_password_title'] = 'Forgot Password';
$lang['mod_user']['account_activation'] = 'Hesap Aktifleştirme';
$lang['mod_user']['register']['error']['name_required'] = 'Please specify your name';
$lang['mod_user']['register']['error']['surname_required'] = 'Please specify your surname';
$lang['mod_user']['register']['error']['username_required'] = 'Please specify your username';
$lang['mod_user']['register']['error']['email_required'] = 'Please specify your e-mail address';
$lang['mod_user']['register']['error']['phone_required'] = 'Please specify your mobile phone number';
$lang['mod_user']['register']['error']['name_invalid_chars'] = 'There are invalid characters in your name';
$lang['mod_user']['register']['error']['surname_invalid_chars'] = 'There are invalid characters in your surname';
$lang['mod_user']['register']['error']['username_length'] = 'Your username can be 2 to 12 characters';
$lang['mod_user']['register']['error']['username_invalid_chars'] = 'Your username can consist of a-z A-Z 0-9 characters';
$lang['mod_user']['register']['error']['password_length'] = 'Your password can be 6 to 16 characters';
$lang['mod_user']['register']['error']['invalid_email'] = 'Please specify a valid e-mail address';
$lang['mod_user']['register']['error']['invalid_phone'] = 'Please specify a valid mobile phone number.';
$lang['mod_user']['register']['error']['username_registered'] = 'The specified username is registered in the system';
$lang['mod_user']['register']['error']['email_registered'] = 'The specified e-mail address is registered in the system';
$lang['mod_user']['register']['error']['invalid_captcha'] = 'You entered the security code incorrectly, please check and try again.';
$lang['mod_user']['register']['error']['activation_email_error'] = 'An error occurred while sending activation e-mail.<br/>
	 Please contact the system administrator. ';
$lang['mod_user']['register']['registration_stopped'] = 'User registration has been temporarily stopped.<br/>
		Thank you for your understanding.';
$lang['mod_user']['register']['form_title'] = 'User Registration Form';
$lang['mod_user']['register']['your_name'] = 'Your Name';
$lang['mod_user']['register']['your_surname'] = 'Your Surname';
$lang['mod_user']['register']['your_username'] = 'Your Username';
$lang['mod_user']['register']['email'] = 'E-mail';
$lang['mod_user']['register']['your_password'] = 'Your Password';
$lang['mod_user']['register']['confirm_password'] = 'Confirm Password';
$lang['mod_user']['register']['your_phone'] = 'Your Phone Number';
$lang['mod_user']['register']['security_code'] = 'Security Code';
$lang['mod_user']['register']['register_button'] = 'Register';
$lang['mod_user']['register']['fill_form'] = 'Please fill out the form to register.';
$lang['mod_user']['register']['registration_success'] = 'Your membership has been successfully completed! You can now log in to your account.';
$lang['mod_user']['register']['registration_success_activation'] = 'Your membership has been successfully completed. <br/>However, your account is not yet active. Please verify your account by clicking the activation link sent to your email address.';
$lang['mod_user']['register']['membership_activation'] = 'Membership Activation';
$lang['mod_user']['register']['password_reset_request'] = 'Password Reset Request';
$lang['mod_user']['register']['your_new_password'] = 'Your New Password';
$lang['mod_user']['register']['membership_registration'] = 'Membership Registration';
$lang['mod_user']['register']['registration_success_admin'] = 'Your account has been successfully created. <br/>For security reasons, your account must be approved by an administrator. You will be notified via email after approval.';
$lang['mod_user']['city'] = 'Şehir';
$lang['mod_user']['blog_name'] = 'Blog Adı';
$lang['mod_user']['about_me'] = 'Hakkımda';
$lang['mod_user']['max_chars_255'] = 'En Fazla 255 Karekter Olabilir.';
$lang['mod_user']['groups']['site_manager'] = 'Site Yöneticisi';
$lang['mod_user']['groups']['editors'] = 'Editörler';
$lang['mod_user']['groups']['normal_member'] = 'Normal Üye';
$lang['mod_user']['membership_date'] = 'Üyelik Tarihi';
$lang['mod_user']['update_date'] = 'Güncelleme Tarihi';
$lang['mod_user']['installer']['sql_error'] = 'users.sql Yüklenirken Hata Oluştu';
$lang['mod_user']['installer']['settings_added'] = '%s mod_user_settings tablosuna eklendi';
$lang['mod_user']['installer']['settings_error'] = 'Üyelik Ayarları Yüklenirken Hata Oluştu';
$lang['mod_user']['installer']['settings_exists'] = '%s mod_user_settings tablosunda Kayıtlı';
$lang['mod_user']['installer']['user_added'] = '-----<b>%s</b> - Kullanıcısı Veritabanına Eklendi!';
$lang['mod_user']['installer']['user_error'] = '-----<b>%s</b> - Kullanıcısı Veritabanına Eklenemedi!';
$lang['mod_user']['installer']['user_exists'] = '----- <b>%s</b> - Kullanıcısı Veritabanında Kayıtlı';
$lang['mod_user']['admin']['settings']['open'] = 'Open';
$lang['mod_user']['admin']['settings']['closed'] = 'Closed';
$lang['mod_user']['admin']['settings']['new_user_group'] = 'New User Group';
$lang['mod_user']['admin']['settings']['membership_open'] = 'Is Membership Open?';
$lang['mod_user']['admin']['settings']['membership_activation'] = 'Membership Activation';
$lang['mod_user']['admin']['settings']['automatic_approval'] = 'Automatic Approval';
$lang['mod_user']['admin']['settings']['email_approval'] = 'Email Approval';
$lang['mod_user']['admin']['settings']['admin_approval'] = 'Admin Approval';
$lang['mod_user']['admin']['settings']['allow_multiple_logins'] = 'Can two people connect to the system with the same account at the same time?';
$lang['mod_user']['admin']['settings']['yes'] = 'Yes';
$lang['mod_user']['admin']['settings']['no'] = 'No';
$lang['mod_user']['admin']['active']['hello'] = 'Hello Dear <b>%s %s</b>.';
$lang['mod_user']['admin']['active']['account_activated'] = 'Your account located at %s has been activated by the administrator<br/>
    You can now log in with your username and password if you wish.';
$lang['mod_user']['admin']['add_user'] = 'Add User';
$lang['mod_user']['admin']['add_new_user'] = 'Add New User';
$lang['mod_user']['admin']['edit_user'] = 'Edit User';
$lang['mod_user']['admin']['roles'] = 'Roles';
$lang['mod_user']['admin']['refresh_permissions'] = 'Refresh Permissions';
$lang['mod_user']['admin']['user_group'] = 'User Group';
$lang['mod_user']['admin']['membership_settings'] = 'Membership Settings';
$lang['mod_user']['admin']['update_membership_settings'] = 'Update Membership Settings';
$lang['mod_user']['admin']['yes'] = 'Yes';
$lang['mod_user']['admin']['no'] = 'No';
$lang['mod_user']['admin']['confirm_remove_image'] = 'Are you sure you want to remove your profile picture?';
$lang['mod_user']['admin']['info_updated_image_error'] = 'Your information has been successfully updated!<br>
						But we could not change your profile picture!';
$lang['mod_user']['admin']['image_removed_success'] = 'Your profile picture has been removed';
$lang['mod_user']['grid']['delete'] = 'Sil';
$lang['mod_user']['grid']['showing_results'] = 'Showing {from} to {to} of {total} results.';
$lang['mod_user']['grid']['page'] = 'Page';
$lang['mod_user']['grid']['page_count'] = 'Page Count';
$lang['mod_user']['grid']['loading_data'] = 'Data loading, please wait...';
$lang['mod_user']['grid']['no_data_found'] = 'No data found';
$lang['mod_user']['grid']['connection_error'] = 'Connection Error';
$lang['mod_user']['grid']['search'] = 'Search';
$lang['mod_user']['grid']['username'] = 'Username';
$lang['mod_user']['grid']['email'] = 'E-mail';
$lang['mod_user']['grid']['add_new_user'] = 'Add New User';
$lang['mod_user']['grid']['edit'] = 'Edit';
$lang['mod_user']['grid']['refresh'] = 'Refresh';
$lang['mod_user']['grid']['user_list'] = 'User List';
$lang['mod_user']['grid']['select_row_edit'] = 'Please select a row to edit';
$lang['mod_user']['grid']['select_row_delete'] = 'Please select at least one row to delete!';
$lang['mod_user']['grid']['confirm_delete'] = 'Total <font color=red>\' + $(\'.trSelected\',grid).length + \'</font> users will be deleted. <br/>Are you sure you want to perform this operation?</br> Remember that all content added by these users, categories etc.. Everything will be deleted!';
$lang['mod_user']['grid']['list_refreshed'] = 'User list refreshed';
$lang['mod_user']['grid']['user_deleted'] = 'User deleted';
$lang['mod_user']['grid']['yes'] = 'Yes';
$lang['mod_user']['grid']['cancel'] = 'Cancel';
$lang['mod_user']['grid']['user_group'] = 'User Group';
$lang['mod_user']['grid']['activate'] = 'Activate';
$lang['mod_user']['grid']['select_account_activate'] = 'Please select the account you want to activate';
$lang['mod_user']['grid']['confirm_activate'] = 'Are you sure you want to activate the selected accounts?';
$lang['mod_user']['grid']['accounts_activated'] = 'Accounts activated!';
$lang['mod_user']['grid']['active'] = 'Active';
$lang['mod_user']['grid']['waiting_email_activation'] = 'Waiting for Email Activation';
$lang['mod_user']['grid']['waiting_admin_activation'] = 'Waiting for Admin Activation.';
$lang['mod_user']['grid']['account_status'] = 'Account Status';
$lang['mod_user']['grid']['select_at_least_one'] = 'Please select at least one row';
$lang['mod_user']['error']['specify_username'] = 'Lütfen Bir Kullanıcı Adı Belirtin';
$lang['mod_user']['error']['specify_password'] = 'Lütfen Bir Şifre Belirtin';
$lang['mod_user']['error']['specify_email'] = 'Lütfen Bir E-Posta Belirtin';
$lang['mod_user']['error']['invalid_email'] = 'Lütfen Geçerli Bir Mail Adresi Belirtin';
$lang['mod_user']['error']['username_registered'] = 'Belirtmiş Olduğunuz Kullanıcı Adı Sistemde Kayıtlı';
$lang['mod_user']['error']['email_registered'] = 'Belirtmiş Olduğunuz E-Mail Sistemde Kayıtlı';
$lang['mod_user']['error']['username_invalid_chars'] = 'Kullanıcı Adınız a-z A-Z 0-9 Karekterlerinden Oluşabilir';
$lang['mod_user']['error']['username_length_error'] = 'Kullanıcı Adınız En Az 3 En Fazla 12 Karekterden Oluşabilir';
$lang['mod_user']['error']['password_length_error'] = 'Şifreniz En Az 6 En Fazla 16 Karekterden Oluşabilir';
$lang['mod_user']['error']['username_not_found'] = 'Yazdığınız Kullanıcı Adı Bulunamadı';
$lang['mod_user']['error']['invalid_credentials'] = 'Kullanıcı Adı veya Şifre Yanlış.';
$lang['mod_user']['error']['permission_error'] = 'Permission Hatası';
$lang['mod_user']['error']['current_password_required'] = 'Lütfen Şimdi Kullandığınız Şifrenizi Yazın';
$lang['mod_user']['error']['new_password_required'] = 'Lütfen Yeni Şifrenizi Yazın';
$lang['mod_user']['error']['confirm_password_required'] = 'Lütfen Yeni Şifrenizi Tekrarlayın';
$lang['mod_user']['error']['passwords_mismatch'] = 'Yeni Şifreniz İle Yeni Şifrenizin Tekrarı Aynı Olmalıdır.';
$lang['mod_user']['error']['current_password_incorrect'] = 'Belirtmiş Olduğunuz Şifre Yanlış';
$lang['mod_user']['error']['name_required'] = 'Lütfen Adınızı Belirtin';
$lang['mod_user']['error']['surname_required'] = 'Lütfen Soyadınızı Belirtin';
$lang['mod_user']['error']['dob_required'] = 'Lütfen Doğum Tarihinizi Belirtin';
$lang['mod_user']['error']['name_length_error'] = 'Adınız En Az 2 En Fazla 50 Karekter Olabilir';
$lang['mod_user']['error']['surname_length_error'] = 'Soyadınız En Az 2 En Fazla 50 Karekter Olabilir';
$lang['mod_user']['error']['name_invalid_chars'] = 'Adınız [^a-zA-ZığüşöçİĞÜŞÖÇîêûÎÊÛ ] Karekterlerinden Oluşabilir';
$lang['mod_user']['error']['surname_invalid_chars'] = 'Soyadınız [^a-zA-ZığüşöçİĞÜŞÖÇîêûÎÊÛ ] Karekterlerinden Oluşabilir';
$lang['mod_user']['error']['date_invalid'] = 'Lütfen Geçerli Bir Tarih Girin.Tarih Formatı YYYY-MM-DD Şeklinde Olmalıdır.';
$lang['mod_user']['error']['email_invalid_format'] = 'Lütfen Geçerli Bir E-Posta Adresi Yazın.';
$lang['mod_user']['error']['phone_invalid'] = 'Yazdığınız Cep Telefonu Numarası Geçersiz.';
$lang['mod_user']['error']['account_not_active'] = 'Kaydınız bulundu, fakat mailinize gelen aktivasyonu gerçekleştirmemişsiniz.<br/> Lütfen e-posta kutunuzu kontrol edin ve aktivasyon linkine tıklayın.';
$lang['mod_user']['error']['ip_protection_error'] = 'Bu Hesap İçin İp Koruması Tanımlanmış ve İp adresiniz Uyuşmuyor';
$lang['mod_user']['error']['multi_login_error'] = 'Bu Hesap Şu Anda Sisteme Bağlı Görünüyor,Hesabınızı Başka Bir Bilgisayarda Açık Unutmuş Olabilirsiniz!';
$lang['mod_user']['error']['user_not_found'] = 'Üye Bulunamadı!';
$lang['mod_user']['error']['system_error'] = 'Sistem Hatası';
$lang['mod_user']['activation']['error']['invalid_captcha'] = 'You entered the security code incorrectly, please check your activation code
		and try again.';
$lang['mod_user']['activation']['error']['code_required'] = 'Please write your activation code.';
$lang['mod_user']['activation']['error']['invalid_code'] = 'The specified activation code is invalid,
		Please check and try again.';
$lang['mod_user']['activation']['title'] = 'Account Activation';
$lang['mod_user']['activation']['write_code'] = 'Please write your activation code in the field below.';
$lang['mod_user']['activation']['activation_code'] = 'Activation Code';
$lang['mod_user']['activation']['security_code'] = 'Security Code';
$lang['mod_user']['activation']['activate_button'] = 'Activate';
$lang['mod_user']['activation']['lost_code'] = 'If you have lost your activation code
	<a href="resend-activation" style="color:#000">Click here</a> to request a new activation code.';
$lang['mod_user']['activation']['success_activated'] = 'Your account has been successfully activated. If you wish
	<a href="login" style="color:#000">Click here</a> to log in to your account.';
$lang['mod_user']['activation']['membership_activation'] = 'Membership Activation';
$lang['mod_user']['resend_activation']['error']['invalid_email_format'] = 'Geçersiz Bir E-Posta Adresi Yazdınız.';
$lang['mod_user']['resend_activation']['error']['email_not_registered'] = 'Belirtmiş Olduğunuz E-Posta Adresi Herhangi Bir Hesaba Kayıtlı Değil.';
$lang['mod_user']['resend_activation']['error']['account_already_active'] = 'Yazmış Olduğunuz E-Posta Adresine Bağlı Hesap Zaten Aktif Durumda!';
$lang['mod_user']['resend_activation']['error']['admin_activation_required'] = 'Yazmış Olduğunuz E-Posta Adresine Bağlı Hesap Sadece Yönetici Tarafından Aktifleştirilebilinir.';
$lang['mod_user']['resend_activation']['error']['no_activation_code_found'] = 'Yazmış Olduğunuz E-Posta Adresine Ait Aktivasyon Kodu Bulunmuyor!';
$lang['mod_user']['resend_activation']['error']['activation_send_error'] = 'Error occurred while sending activation code. Please contact the system administrator.';
$lang['mod_user']['resend_activation']['error']['invalid_captcha'] = 'You entered the security code incorrectly, please check and try again.';
$lang['mod_user']['resend_activation']['error']['email_required'] = 'Please write the e-mail address associated with your account.';
$lang['mod_user']['resend_activation']['error']['invalid_email_format'] = 'You entered an invalid e-mail address.';
$lang['mod_user']['resend_activation']['error']['email_not_registered'] = 'The specified e-mail address is not registered to any account.';
$lang['mod_user']['resend_activation']['error']['account_already_active'] = 'The account associated with the e-mail address you entered is already active!';
$lang['mod_user']['resend_activation']['error']['admin_activation_required'] = 'The account associated with the e-mail address you entered can only be activated by an administrator.';
$lang['mod_user']['resend_activation']['error']['no_activation_code_found'] = 'No activation code found for the e-mail address you entered!';
$lang['mod_user']['resend_activation']['code_sent'] = 'Your activation code has been sent. Please check your e-mail to activate your account';
$lang['mod_user']['resend_activation']['write_email'] = 'So that we can send you an activation code.<br/> Write the e-mail address associated with your account in the field below.';
$lang['mod_user']['resend_activation']['activation_code'] = 'Activation Code';
$lang['mod_user']['resend_activation']['email'] = 'E-mail';
$lang['mod_user']['resend_activation']['security_code'] = 'Security Code';
$lang['mod_user']['resend_activation']['send_button'] = 'Send';
$lang['mod_user']['resend_activation']['activation_code_label'] = 'Activation Code';
$lang['mod_user']['password_reset']['title'] = 'Şifre Sıfırlama';
$lang['mod_user']['password_reset']['info'] = 'Yeni Şifrenizi Üretebilmemiz İçin,Lütfen Aşağıdaki Alana Şifre Sıfırlama Aktivasyon Kodunuzu Yazınız.';
$lang['mod_user']['password_reset']['activation_code'] = 'Aktivasyon Kodu';
$lang['mod_user']['password_reset']['security_code'] = 'Güvenlik Kodu';
$lang['mod_user']['password_reset']['send_button'] = 'Gönder';
$lang['mod_user']['password_reset']['success_message'] = 'Tebrikler,Yeni Şifreniz E-Posta Adresinize Gönderildi.<br/>Lütfen E-Posta Adresinizi Kontrol Ediniz.';
$lang['mod_user']['password_reset']['error']['invalid_captcha'] = 'Güvenlik Kodunu Hatalı Girdiniz,Lütfen Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['password_reset']['error']['code_required'] = 'Lütfen Aktivasyon Kodunu Yazın.';
$lang['mod_user']['password_reset']['error']['invalid_code'] = 'Belirttiğiniz Aktivasyon Kodu Geçersiz,
		Lütfen Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['password_reset']['error']['email_send_error'] = 'E-Posta Gönderilirken Hata Oluştur Lütfen Sistem Yöneticisi İle İrtibata Geçin!';
$lang['mod_user']['password_reset']['error']['invalid_captcha'] = 'You entered the security code incorrectly, please check and try again.';
$lang['mod_user']['password_reset']['error']['code_required'] = 'Please write your activation code.';
$lang['mod_user']['password_reset']['error']['invalid_code'] = 'The specified activation code is invalid, please check and try again.';
$lang['mod_user']['password_reset']['error']['email_send_error'] = 'Error occurred while sending e-mail. Please contact the system administrator!';
$lang['mod_user']['password_reset']['title'] = 'Password Reset';
$lang['mod_user']['password_reset']['info'] = 'To generate your new password, please write your password reset activation code in the field below.';
$lang['mod_user']['password_reset']['activation_code'] = 'Activation Code';
$lang['mod_user']['password_reset']['security_code'] = 'Security Code';
$lang['mod_user']['password_reset']['send_button'] = 'Send';
$lang['mod_user']['password_reset']['success_message'] = 'Congratulations, your new password has been sent to your e-mail address.<br/>Please check your e-mail address.';
$lang['mod_user']['unread_messages_count'] = 'You have %s unread messages.';
