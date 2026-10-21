<?php
/**
 * Publication Management Module - Swedish Language File
 *
 * @package AKM Bulletin CMS
 * @module  mod_publication
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Publication Management Module Language Translations
 */
$lang['mod_publication'] = [

    // === Basic Actions ===
    'add' => 'Lägg till publikation',
    'edit' => 'Redigera publikation',
    'list' => 'Lista över publikationer',
    'information' => 'Information!',
    'success' => 'Operationen lyckades!',
    'error' => 'Ett fel uppstod!',
    'error_occurred' => 'Ett Fel Uppstod!',
    'wizard_loading' => 'Guiden laddas...',
    'fatal_error' => 'Fatalt fel uppstod!',
    'remove_image' => 'Är du säker på att du vill ta bort den här bilden?',
    'remove_image_success' => 'Bilden togs bort',
    'choose_wish' => 'Välj om du vill',
    'remove_record' => 'Är du säker på att du vill ta bort den här posten?',
    'delete_confirm' => 'Är du säker på att du vill ta bort den här publikationen?',
    'quick_unpublish_confirm' => 'Är du säker på att du vill avpublicera den här publikationen?',
    'quick_publish_confirm' => 'Är du säker på att du vill publicera den här publikationen?',
    'add_image' => 'Lägg till bild',
    'upload_change_image' => 'Ladda upp/Ändra bild',
    'local' => 'Lokal',
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
        'view_count' => 'Visningar',
        'limit_label' => 'Visningsgräns',
        'lang_label' => 'Språkval',
        'selected_lang' => 'Valt språk',
        'date_label' => 'Datumintervall',
        'start_date' => 'Start',
        'end_date' => 'Slut',
        'clear' => 'Rensa',
        'apply' => 'Visa resultat',
        'select_lang' => 'Välj språk',
        'all_langs' => 'Alla språk',
        'select_label' => 'Välj',
        'delete_error' => 'Borttagning misslyckades!',
        'video_success_sim' => 'Video tillagd (simulering)',
        'video_embed_error' => 'Ogiltig inbäddningskod',
        'video_title_error' => 'Ogiltig titel',
    ],

    // === Messages ===
    'message' => [
        'success' => [
            'add' => 'Publikation tillagd!',
            'edit' => 'Publikation uppdaterad!',
        ],
        'error' => [
            'add' => 'Publikation kunde inte läggas till!',
            'edit' => 'Publikation kunde inte uppdateras!',
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
        'refresh' => 'Uppdatera',
        'delete' => 'Ta bort',
        'gallery' => 'Bildgalleri',
        'file' => 'Filhanterare',
        'cancel' => 'Avbryt',
        'remove' => 'Ta bort',
        'yes' => 'Ja',
        'no' => 'Nej',
    ],

    // === Data Grid Messages ===
    'grid' => [
        'showing_results' => 'Visar {from} till {to} av {total} resultat.',
        'page' => 'Sida',
        'page_count' => 'Sidantal',
        'loading' => 'Laddar data, vänta...',
        'no_data' => 'Ingen data hittades',
        'connection_error' => 'Anslutningsfel',
        'search' => 'Sök',
        'select_at_least_one' => 'Välj minst en rad',
        'select_only_one' => 'Välj endast en rad',
        'approve' => [
            'key' => 'Publicera',
            'value' => 'Är du säker på att du vill publicera den här publikationen?',
        ],
        'unapprove' => [
            'key' => 'Avpublicera',
            'value' => 'Är du säker på att du vill avpublicera den här publikationen?',
        ],
        'load_more' => 'Ladda fler',
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Vänligen lämna inte fältet %s tomt',
        'length' => '%s kan vara minst %s och högst %s tecken',
        'email' => 'Ange en giltig e-postadress i fältet %s.',
        'unique' => '%s du angav är redan registrerad i systemet. Ange en annan %s!',
        'inarray' => 'Fältet %s kan endast ta värdena \' %s \'',
        'title_length' => 'Du måste ange en titel på minst 1 och högst 70 tecken.',
        'title_exists' => 'Denna titel finns redan, vänligen ändra den',
        'title_unique' => 'Din titel är unik',
        'deck_length' => 'Vänligen ange en introduktionstext på mellan 90 och 120 tecken.',
    ],

    // === Permission Roles ===
    'role' => [
        'name' => 'Roller för publikationshantering',
        'add' => 'Kan lägga till publikationer',
        'edit' => 'Kan redigera publikationer',
        'delete' => 'Kan ta bort publikationer',
        'list' => 'Kan se publikationshantering',
        'publish' => 'Kan publicera publikationer',
        'unpublish' => 'Kan avpublicera publikationer',
        'change_status' => 'Kan ändra publiceringsstatus',
        'edit_status' => 'Kan ändra detaljerad publiceringsstatus',
        'moderate_edit' => 'Kan redigera andras publikationer',
        'moderate_delete' => 'Kan ta bort andras publikationer',
        'gallery' => 'Kan hantera galleri',
        'file' => 'Kan hantera filer',
        'approval' => 'Kan godkänna publikationer',
        'video_download' => 'Kan ladda ner video',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'add' => 'Du har inte behörighet att lägga till publikationer',
        'edit' => 'Du har inte behörighet att redigera publikationer',
        'list' => 'Du har inte behörighet att se publikationshantering',
        'delete' => 'Du har inte behörighet att ta bort publikationer',
        'approval' => 'Du har inte behörighet att godkänna publikationer',
        'moderate_edit' => 'Du kan inte redigera andras publikationsposter',
        'moderate_delete' => 'Du kan inte ta bort andras publikationsposter',
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
            'publication' => 'Publikation',
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
            'menu' => 'Lägg till i menyn',
            'video' => 'Videogalleri',
            'video_help' => 'Sök efter videon du vill visa i publikationen',
        ],
        'radio' => [
            'publish_yes' => 'Publicerad',
            'publish_no' => 'Spara endast',
            'deck_yes' => 'Utvald',
            'deck_no' => 'Inte utvald',
        ],
    ],

    // === UI Elements ===
    'ui' => [
        'record_management' => 'Posthantering',
        'operation' => 'Åtgärd',
        'apply' => 'Verkställ',
        'action_column' => 'Åtgärd',
        'select' => 'Välj',
        'from' => 'Från',
        'to' => 'Till',
        'search' => 'Sök',
        'reset' => 'Återställ',
        'approval' => 'Bekräfta',
        'processing' => 'Bearbetar',
        'record_selected' => 'Post vald',
        'operation_failed' => 'Åtgärden kunde inte utföras',
        'select_operation' => 'Välj en åtgärd',
        'all' => 'Alla',
        'pdf' => 'PDF',
        'csv' => 'CSV',
        'excel' => 'Excel',
        'print' => 'Skriv ut',
        'print_info' => 'Utskriftsinfo',
        'created_by' => 'Skapad av',
        'copy' => 'Kopiera',
        'default_sort' => 'Nyaste',
        'default_lang' => 'SV',
        'date_locale' => 'sv-SE',
        'filter_cleared' => 'Filter rensade och återställda till standardvärden.',
        'filter_applied' => 'Filter tillämpade',
        'sort_label' => 'Sortering',
        'limit_label' => 'Antal',
        'lang_label' => 'Språk',
    ],

    // === DataTable Configuration ===
    'datatable' => [
        'aria' => [
            'sortAscending' => ': aktivera för att sortera kolumn stigande',
            'sortDescending' => ': aktivera för att sortera kolumn fallande',
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
        'hintText' => 'Skriv ett sökord',
        'noResultsText' => 'Inga resultat',
        'searchingText' => 'Söker...',
    ],

    // === AnyTime Picker ===
    'anytime' => [
        'labelDayOfMonth' => 'Dag i månaden',
        'labelHour' => 'Timme',
        'labelMinute' => 'Minut',
        'labelMonth' => 'Månad',
        'labelSecond' => 'Sekund',
        'labelTitle' => 'Välj datum och tid',
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
        'general' => 'Allmänt',
    ],

    // === Sheet Labels ===
    'sheet' => [
        'lang_title' => 'Språkval',
        'ok' => 'OK',
        'search_placeholder' => 'Sök språk (t.ex. Engelska, JP)...',
        'category_title' => 'Välj kategori',
        'all' => 'Alla (ofiltrerad)',
    ],

    // === Wizard ===
    'wizard' => [
        'step' => [
            'lang' => 'Språkval',
            'language' => 'Språkval',
            'category' => 'Kategori',
            'title' => 'Titel',
            'date' => 'Publiceringsdatum',
            'intro' => 'Introduktionstext (Deck)',
            'publication' => 'Publikationsdetaljer',
            'image' => 'Bildval',
            'finish' => 'Slutför',
        ],
        'subtitle' => [
            'language' => 'Välj huvudspråk för publikationen.',
            'category' => 'Välj kategori som denna publikation tillhör.',
            'title' => 'Ange en engagerande titel. (Min: 1, Max: 70)',
            'date' => 'Datum och tid för publicering.',
            'intro' => 'Skriv en kort och koncis sammanfattning. (90 - 120 Tecken)',
            'publication' => 'Rich text editor.',
            'image' => 'Ladda upp en omslagsbild.',
            'finish' => 'Slutkontroller och publicering.',
        ],
        'label' => [
            'publication_title' => 'Publikationstitel',
            'intro_text' => 'Introduktionstext',
            'upload_text' => 'Tryck för att ladda upp bild',
            'published' => 'Publicerad',
            'published_desc' => 'Publikationen kommer att vara synlig för alla när den har lagts till.',
            'category_select' => 'Välj kategori...',
            'change_image' => 'Ändra bild',
            'editor_placeholder' => 'Börja skriva din publikation här...',
        ],
        'messages' => [
            'success_sim' => 'Publikation skapad! (Simulering)',
            'video_success_sim' => 'Video tillagd! (Simulering)',
            'video_embed_error' => 'Ange en giltig inbäddningskod (iframe).',
            'video_title_error' => 'Videotiteln måste vara minst 3 tecken.',
            'card_click' => 'Kort klickat: %s',
            'no_category_for_lang' => 'Inga kategorier hittades på detta språk. Lägg till kategorier först.',
        ],
        'summary' => [
            'title' => 'Sammanfattning',
            'selected_none' => 'Ej vald',
        ],
        'button' => [
            'save' => 'Spara',
            'next' => 'Fortsätt',
            'prev' => 'Bakåt',
            'finish' => 'Spara och slutför',
        ]
    ],

    // === Context Menu ===
    'context_menu' => [
        'title' => 'Publikationsåtgärder',
        'edit' => 'Redigera',
        'gallery' => 'Lägg till galleri i publikationen',
        'files' => 'Lägg till fil i publikationen',
        'video' => 'Lägg till inbäddad video i publikationen',
        'status' => 'Ändra status',
        'delete' => 'Ta bort',
        'delete_error' => 'Borttagning misslyckades!',
        'video_success_sim' => 'Video tillagd (simulering)',
        'video_embed_error' => 'Ogiltig inbäddningskod',
        'video_title_error' => 'Ogiltig titel',
        'publish' => 'Publicera',
        'unpublish' => 'Avpublicera',
        'published_msg' => 'Publicerad',
        'unpublished_msg' => 'Avpublicerad',
        'confirm' => 'Ja',
        'cancel' => 'Avbryt',
        'edit_alert' => "REDIGERINGSLÄGE:\nID: %s\nTitel: %s",
        'gallery_alert' => "GALLERIHANTERING:\nID: %s\nTitel: %s\nÖppnar bildgalleri för publikation.",
        'files_alert' => "FILHANTERING:\nID: %s\nTitel: %s\nÖppnar filuppladdningsskärm för publikation.",
        'status_alert' => "STATUS UPPDATERAD:\n%s",
        'delete_success' => 'Publikation borttagen!',
        'video_unavailable' => 'Videohantering är inte tillgänglig för publikationer ännu.',
    ],

    'mock' => [
        'titles' => [
            "Ny era börjar i kurdisk press",
            "Programmet för Diyarbakirs kultur- och konstfestival meddelat",
            "Workshop för lokal journalistik avslutad",
            "Anteckningar om förhållandet mellan AI och media",
            "Övergång från traditionell journalistik till digitala media",
            "Statistik över användning av sociala medier 2024",
            "Veckans tekniska sammanfattning: Vad hände?",
            "Författarförbundet väljer ny styrelse",
            "Anmälningar till fotoworkshop har börjat",
            "AKM Bulletin CMS mobilapp uppdaterad"
        ],
        'categories' => ["Agenda", "Kultur & Konst", "Teknik", "Media", "Livet"],
        'authors' => [],
    ],

    // === AJAX & Relation Messages ===
    'ajax' => [
        'success' => 'Framgång',
        'error' => 'Ett fel uppstod',
        'database_error' => 'Databasfel',
        'record_not_found' => 'Publikationspost hittades inte!',
        'unknown_type' => 'Okänd relationstyp',
        'file_added' => 'En fil har lagts till i publikationen',
        'file_removed' => 'Filen har tagits bort från publikationen',
        'gallery_added' => 'Ett galleri har lagts till i publikationen',
        'gallery_removed' => 'Galleriet har tagits bort från publikationen',
        'relation_updated' => 'Relationen uppdaterades framgångsrikt',
    ],

    // === Hero Slider ===
    'hero_slider_add' => 'Skicka till slider',
    'hero_slider_remove' => 'Ta bort från slider',
    'hero_slider_added' => 'Skickat till slider.',
    'hero_slider_removed' => 'Borttaget från slider.',
    'hero_slider_error' => 'Slider-uppdatering misslyckades.',
    'hero_slider_badge' => 'Slider',

    // === Media Picker UI ===
    'picker' => [
        'gallery' => 'Välj befintligt galleri',
        'file' => 'Välj befintlig fil',
        'video' => 'Välj befintlig video',
    ],
];
