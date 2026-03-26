<?php
/**
 * News Management Module - Swedish Language File
 *
 * @package Rojname
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
    'add' => 'Lägg till Nyheter',
    'edit' => 'Redigera Nyheter',
    'list' => 'Nyhetslista',
    'information' => 'Information!',
    'oke' => 'Operationen Lyckades!',
    'hata' => 'Ett Fel Uppstod!',
    'error' => 'Allvarligt Fel Uppstod!',
    'remove_image' => 'Är du säker på att du vill ta bort den här bilden?',
    'remove_image_oke' => 'Bilden har tagits bort',
    'choose_wish' => 'Vänligen välj om du vill',
    'remove_record' => 'Är du säker på att du vill radera denna post?',
    'delete_confirm' => 'Är du säker på att du vill radera denna nyhetsartikel?',
    'quick_unpublish_confirm' => 'Är du säker på att du vill avpublicera denna nyhetsartikel?',
    'quick_publish_confirm' => 'Är du säker på att du vill publicera denna nyhetsartikel?',
    'wizard_loading' => 'Guiden laddas...',
    'resim_ekle' => 'Lägg till Bild',
    'resim_yukle_degistir' => 'Ladda upp/Ändra Bild',
    'lokal' => 'Lokal',
    'embed' => 'Bädda in',

    // === Filter Menu ===
    'filter' => [
        'title' => 'Filtrera & Sortera',
        'close' => 'Stäng',
        'sort_label' => 'Sortera',
        'newest' => 'Nyaste',
        'oldest' => 'Äldsta',
        'title_az' => 'Titel (A-Ö)',
        'title_za' => 'Titel (Ö-A)',
        'view_count' => 'Antal Visningar',
        'limit_label' => 'Visningsgräns',
        'lang_label' => 'Språkval',
        'selected_lang' => 'Valt Språk',
        'date_label' => 'Datumintervall',
        'start_date' => 'Start',
        'end_date' => 'Slut',
        'clear' => 'Rensa',
        'apply' => 'Visa Resultat',
        'select_lang' => 'Välj Språk',
        'all_langs' => 'Alla Språk',
        'select_label' => 'Välj',
    ],

    // === Messages ===
    'message' => [
        'oke' => [
            'add' => 'Nyheter Tillagda!',
            'edit' => 'Nyheter Uppdaterade!',
        ],
        'error' => [
            'add' => 'Nyheter Kunde Inte Läggas Till!',
            'edit' => 'Nyheter Kunde Inte Uppdateras!',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'Den angivna posten kunde inte hittas!',
    ],

    // === Buttons ===
    'button' => [
        'next' => 'Nästa',
        'prev' => 'Föregående',
        'send' => 'Skicka',
        'add' => 'Lägg till',
        'edit' => 'Redigera',
        'yenile' => 'Uppdatera',
        'delete' => 'Radera',
        'gallery' => 'Bildgalleri',
        'file' => 'Filhanterare',
        'vazgec' => 'Avbryt',
        'kaldir' => 'Ta bort',
        'tamam' => 'OK',
        'evet' => 'Ja',
        'hayir' => 'Nej',
    ],

    // === Data Grid Messages ===
    'grid' => [
        0 => 'Visar {from} till {to} av {total} resultat.',
        1 => 'Sida',
        2 => 'Sidantal',
        3 => 'Laddar data, vänligen vänta...',
        4 => 'Ingen data hittades',
        5 => 'Anslutningsfel',
        6 => 'Sök',
        7 => 'Vänligen välj minst en rad',
        8 => 'Vänligen välj endast en rad',
        104 => 'Laddar...',
        105 => 'Inga Nyheter Hittades',
        'approve' => [
            'key' => 'Publicera',
            'value' => 'Är du säker på att du vill publicera denna nyhetsartikel?',
        ],
        'unapprove' => [
            'key' => 'Avpublicera',
            'value' => 'Är du säker på att du vill avpublicera denna nyhetsartikel?',
        ],
        'load_more' => 'Ladda Mer',
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Fältet %s får inte lämnas tomt',
        'length' => '%s kan vara minst %s och högst %s tecken',
        'email' => 'Vänligen ange en giltig e-postadress för fältet %s.',
        'unique' => '%s som du angav är redan registrerad i systemet. Vänligen ange en annan %s!',
        'inarray' => 'Fältet %s kan endast ta värdena \' %s \'',
        'title_length' => 'Du måste ange en titel på minst 1 och högst 70 tecken.',
        'title_exists' => 'Denna titel finns redan, vänligen ändra den',
        'title_unique' => 'Din titel är unik',
        'deck_length' => 'Vänligen ange en introduktionstext på minst 110 tecken och högst 280 tecken.',
    ],

    // === Permission Roles ===
    'roll' => [
        'name' => 'Roller för Nyhetshantering',
        'add' => 'Kan Lägga till Nyheter',
        'edit' => 'Kan Redigera Nyheter',
        'delete' => 'Kan Radera Nyheter',
        'list' => 'Kan Visa Nyhetshantering',
        'publish' => 'Kan Publicera Nyheter',
        'unpublish' => 'Kan Avpublicera Nyheter',
        'change_status' => 'Kan Ändra Publiceringsstatus',
        'edit_status' => 'Kan Ändra Detaljerad Publiceringsstatus',
        'moderate_edit' => 'Kan Redigera Andras Nyheter',
        'moderate_delete' => 'Kan Radera Andras Nyheter',
        'gallery' => 'Kan Hantera Galleri',
        'file' => 'Kan Hantera Filer',
        'approval' => 'Kan Godkänna Nyheter',
        'video_download' => 'Kan Ladda Ner Video',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'add' => 'Du har inte behörighet att lägga till nyheter',
        'edit' => 'Du har inte behörighet att redigera nyheter',
        'list' => 'Du har inte behörighet att visa nyhetshantering',
        'delete' => 'Du har inte behörighet att radera nyheter',
        'approval' => 'Du har inte behörighet att godkänna nyheter',
        'moderate_edit' => 'Du kan inte redigera andras nyheter',
        'moderate_delete' => 'Du kan inte radera andras nyheter',
        'gallery' => 'Du har inte behörighet att hantera galleri',
        'file' => 'Du har inte behörighet att hantera filer',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Språk',
            'name' => 'Titel',
            'category_id' => 'Kategori',
            'created_at' => 'Datum',
            'updated_at' => 'Redigeringsdatum',
            'news' => 'Nyheter',
            'summary' => 'Introduktion',
            'seo' => 'SEO URL',
            'title' => 'Sidtitel',
            'keywords' => 'Nyckelord',
            'description' => 'Beskrivning',
            'status' => 'Publiceringsstatus',
            'deck' => 'Utvald',
            'image' => 'Bild',
            'gallery_id' => 'Galleri',
            'file_id' => 'Fil',
            'acl_id' => 'Åtkomstkontroll',
            'video' => 'Videogalleri',
        ],
        'radio' => [
            'publish_yes' => 'Publicerad',
            'publish_no' => 'Spara Endast',
            'deck_yes' => 'Utvald',
            'deck_no' => 'Ej Utvald',
        ],
    ],

    // === UI Elements ===
    'ui' => [
        'kayit_yonetimi' => 'Posthantering',
        'islem' => 'Operation',
        'uygula' => 'Verkställ',
        'islem_column' => 'Åtgärd',
        'select' => 'Välj',
        'burdan' => 'Från',
        'buraya' => 'Till',
        'ara' => 'Sök',
        'sifirla' => 'Återställ',
        'approval' => 'Bekräfta',
        'isleniyor' => 'Bearbetar',
        'record_selected' => 'Post vald',
        'islem_gerceklestirilemedi' => 'Operationen kunde inte utföras',
        'islem_secin' => 'Vänligen välj en operation',
        'all' => 'Alla',
        'pdf' => 'PDF',
        'csv' => 'CSV',
        'excel' => 'Excel',
        'yazdir' => 'Skriv ut',
        'yazdir_bilgi' => 'Utskriftsinfo',
        'olusturan' => 'Skapad av',
        'kopyala' => 'Kopiera',
        'default_sort' => 'Nyaste',
        'default_lang' => 'SV',
        'date_locale' => 'sv-SE',
        'all' => 'Alla',
        'filter_cleared' => 'Filter har rensats och återställts.',
        'filter_applied' => 'Filter Tillämpade',
        'sort_label' => 'Sortering',
        'limit_label' => 'Antal',
        'lang_label' => 'Språk',
    ],

    // === DataTable Configuration ===
    'datatable' => [
        'aria' => [
            'sortAscending' => ': aktivera för att sortera kolumnen stigande',
            'sortDescending' => ': aktivera för att sortera kolumnen fallande',
        ],
        'emptyTable' => 'Ingen data tillgänglig i tabellen',
        'info' => 'Visar _START_ till _END_ av _TOTAL_ poster',
        'infoEmpty' => 'Visar 0 till 0 av 0 poster',
        'infoFiltered' => '(filtrerad från totalt _MAX_ poster)',
        'lengthMenu' => 'Visa _MENU_ poster',
        'search' => 'Sök:',
        'zeroRecords' => 'Inga matchande poster hittades',
        'loadingRecords' => 'Laddar...',
        'processing' => 'Bearbetar...',
        'paginate' => [
            'first' => 'Första',
            'last' => 'Sista',
            'next' => 'Nästa',
            'previous' => 'Föregående',
        ],
    ],

    // === Tokenizing Configuration ===
    'tokenizing' => [
        'hintText' => 'Skriv in en sökterm',
        'noResultsText' => 'Inga resultat',
        'searchingText' => 'Söker...',
    ],

    // === AnyTime Picker ===
    'anytime' => [
        'labelDayOfMonth' => 'Dag i Månaden',
        'labelHour' => 'Timme',
        'labelMinute' => 'Minut',
        'labelMonth' => 'Månad',
        'labelTitle' => 'Välj Datum och Tid',
        'labelYear' => 'År',
        'dayAbbreviations' => ['Sön', 'Mån', 'Tis', 'Ons', 'Tor', 'Fre', 'Lör'],
        'dayNames' => ['Söndag', 'Måndag', 'Tisdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lördag'],
        'monthAbbreviations' => ['Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],
        'monthNames' => ['Januari', 'Februari', 'Mars', 'April', 'Maj', 'Juni', 'Juli', 'Augusti', 'September', 'Oktober', 'November', 'December'],
    ],

    // === Status Badges ===
    'status_badge' => [
        'active' => 'Publicerad',
        'draft' => 'Utkast',
        'general' => 'Allmän',
    ],

    // === Sheet Labels ===
    'sheet' => [
        'lang_title' => 'Språkval',
        'ok' => 'OK',
        'search_placeholder' => 'Sök språk (t.ex. Svenska, Engelska)...',
        'category_title' => 'Välj Kategori',
        'all' => 'Alla (Ofiltrerat)',
    ],

    // === Wizard ===
    'wizard' => [
        'step' => [
            'language' => 'Språkval',
            'category' => 'Kategori',
            'title' => 'Titel',
            'date' => 'Publiceringsdatum',
            'intro' => 'Introtid (Deck)',
            'news' => 'Nyhetsdetaljer',
            'image' => 'Bildval',
            'finish' => 'Slutför',
        ],
        'subtitle' => [
            'language' => 'Välj huvudspråk för nyhetsartikeln.',
            'category' => 'Välj vilken kategori nyhetsartikeln ska tillhöra.',
            'title' => 'Ange en engagerande titel. (Min: 1, Max: 70)',
            'date' => 'Datum och tid för publicering.',
            'intro' => 'Skriv en kort och koncis sammanfattning. (110 - 280 Tecken)',
            'news' => 'Rich text-redigerare.',
            'image' => 'Ladda upp en omslagsbild.',
            'finish' => 'Slutkontroll och publicering.',
        ],
        'label' => [
            'news_title' => 'Nyhetstitel',
            'intro_text' => 'Introtext',
            'upload_text' => 'Tryck för att Ladda upp Bild',
            'published' => 'Publicerad',
            'published_desc' => 'Nyheten kommer att vara synlig för alla när den har lagts till.',
            'category_select' => 'Välj Kategori...',
            'change_image' => 'Byt Bild',
            'editor_placeholder' => 'Börja skriva din nyhet här...',
        ],
        'messages' => [
            'success_sim' => 'Nyheten har skapats framgångsrikt! (Simulering)',
            'video_success_sim' => 'Video har lagts till framgångsrikt! (Simulering)',
            'video_embed_error' => 'Vänligen ange en giltig inbäddningskod (iframe).',
            'video_title_error' => 'Videotiteln måste vara minst 3 tecken.',
            'card_click' => 'Kort klickat: %s',
        ],
        'summary' => [
            'title' => 'Sammanfattning',
            'selected_none' => 'Ej Valt',
        ],
        'button' => [
            'save' => 'Spara',
            'next' => 'Fortsätt',
            'prev' => 'Bakåt',
            'finish' => 'Spara och Slutför',
        ]
    ],

    // === Context Menu ===
    'context_menu' => [
        'title' => 'Nyhetsåtgärder',
        'edit' => 'Redigera',
        'gallery' => 'Galleriåtgärder',
        'files' => 'Lägg till Fil',
        'status' => 'Ändra Status',
        'delete' => 'Radera',
        'delete_error' => 'Radering misslyckades!',
        'video_success_sim' => 'Video Tillagd (Simulering)',
        'video_embed_error' => 'Ogiltig Inbäddningskod',
        'video_title_error' => 'Ogiltig Titel',
        'publish' => 'Publicera',
        'unpublish' => 'Avpublicera',
        'published_msg' => 'Publicerad',
        'unpublished_msg' => 'Avpublicerad',
        'confirm' => 'Ja',
        'cancel' => 'Avbryt',
        'edit_alert' => "REDIGERINGSLÄGE:\nID: %s\nTitel: %s",
        'gallery_alert' => "GALLERIHANTERING:\nID: %s\nTitel: %s\nÖppnar bildgalleri för nyheter.",
        'files_alert' => "FILHANTERING:\nID: %s\nTitel: %s\nÖppnar filuppladdningsskärm för nyheter.",
        'status_alert' => "STATUS UPPDATERAD:\n%s",
        'delete_confirm' => 'Ja, Radera!',
        'delete_success' => 'Nyhet raderad!',
    ],

    'mock' => [
        'titles' => [
            "Ny era börjar i kurdisk press",
            "Diyarbakir kultur- och konstfestival schema tillkännagivet",
            "Lokal journalistikworkshop avslutad",
            "Anteckningar om AI och medierelationer",
            "Övergång från traditionell journalistik till digitala medier",
            "Statistik över användning av sociala medier 2024",
            "Veckans tekniska sammanfattning: Vad hände?",
            "Författarförbundet väljer ny styrelse",
            "Fotoworkshop registreringar startade",
            "Rojname mobilapp uppdaterad"
        ],
        'categories' => ["Agenda", "Kultur & Konst", "Teknik", "Media", "Livsstil"],
        'authors' => [],
    ],
];
