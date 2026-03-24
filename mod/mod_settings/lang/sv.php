<?php
$lang['mod_settings'] = [
    // Page & Section Titles
    'title' => 'Allmänna Webbplatsinställningar',
    'description' => 'Hantera webbplatsinställningar för alla språk.',
    'lang_settings' => 'Språkinställningar',
    'social' => 'Sociala Medier Länkar',
    'add_social' => 'Lägg till Ny Länk',

    // Placeholders
    'platform_ph' => 'Plattform (t.ex. Twitter)',
    'url_ph' => 'URL (https://...)',
    'icon_ph' => 'Ikon Klass (t.ex. bi bi-twitter-x)',

    // Form Labels
    'title' => 'Webbplats Titel',
    'description' => 'Webbplats Beskrivning',
    'owner_name' => 'Ägarens Namn',
    'established_year' => 'Grundat År',
    'phone' => 'Telefon',
    'whatsapp' => 'WhatsApp',
    'email' => 'E-post',
    'office_address' => 'Kontorsadress',
    'work_address' => 'Arbetsadress',

    // Buttons & Actions
    'save' => 'Spara',
    'list' => 'Lista',
    'button' => [
        'cancel' => 'Avbryt',
        'close' => 'Stang',
        'edit' => 'Redigera',
        'save_and_close' => 'Spara och stäng',
        'generate_assets' => 'Generera Ikoner',
        'confirm' => 'OK',
        'leave_page' => 'Lämna sidan',
        'stay' => 'Stanna',
    ],

    // Messages & Status
    'updated' => 'Inställningar uppdaterades framgångsrikt.',

    // Errors
    'perm_edit' => 'Du har inte behörighet att redigera allmänna inställningar.',
    'error' => 'Fel',

    // === Access Control (ACL) ===
    'roll' => [
      'ad' => 'Webbplatsinställningar Hanteringsroller',
      'list' => 'Kan Visa Webbplatsinställningar',
      'edit' => 'Kan Redigera Webbplatsinställningar',
      'add' => 'Kan Lägga till Nya Webbplatsinställningar (för Språk)',
      'delete' => 'Kan Ta Bort Webbplatsinställningar'
    ],

    // === Permission Error Messages ===
    'permission_errors' => [
        'settings_edit' => 'Du har inte behörighet att redigera webbplatsinställningar.',
        'settings_view' => 'Du har inte behörighet att visa webbplatsinställningar.',
    ],

    // === Settings Page ===
    'settings' => [
        'title' => 'Webbplatsinställningar Hantering',
    ],
    'sections' => [
        'seo_title' => 'Meta och Verifiering',
        'seo_description' => 'Verifieringskoder, metadata och inbäddningsfält.',
        'assets_title' => 'Filer',
        'assets_description' => 'Ladda upp en PNG-logotyp sa genererar systemet ikonerna automatiskt.',
        'social_title' => 'Sociala Medier',
        'social_description' => 'Hantera sociala medielänkar.',
    ],

    // === Success Messages ===
    'messages' => [
        'success' => [
            'updated' => 'Inställningar uppdaterades framgångsrikt.',
            'assets_generated' => 'Ikonfiler genererades framgångsrikt.',
        ],
    ],

    // === Additional Form Labels ===
    'map_embed_code' => 'Kartinbäddningskod',
    'google_search_console' => 'Google Search-tagg',
    'google_analytics' => 'Google Analytics Kod',
    'meta_tags' => 'Ytterligare Meta-taggar',
    'show_logo_title' => 'Visa Logotyp',
    'show_logo_desc' => 'Visa logotyp istället för sidtitel',
    'logo_path' => 'Huvudlogotyp (PNG)',
    'favicon_path' => 'Favicon Sökväg',

    // === UI Labels ===
    'select_language' => 'Välj Språk',
    'site_name' => 'Webbplats Namn',
    'site_description' => 'Webbplats Beskrivning',

    // === File Upload & Management ===
    'current_logo' => 'Nuvarande logotyp:',
    'logo_not_uploaded' => 'Logotyp har inte laddats upp.',
    'logo_help_text' => 'Ladda upp en transparent PNG. Minst 512x512 px, rekommenderat 1024x1024 px eller storre. Systemet genererar favicon-, ikon- och manifestfiler automatiskt.',
    'favicon_not_uploaded' => 'Favicon har inte laddats upp.',
    'favicon_help_text' => 'ICO- eller PNG-format. Kommer att storleksändras till 32x32.',
    'not_uploaded' => 'Inte uppladdad.',
    'png_96_help' => 'PNG-format. Kommer att storleksändras till 96x96.',
    'png_180_help' => 'PNG-format. Kommer att storleksändras till 180x180.',
    'view_file' => 'Visa fil',
    'webmanifest_help' => '.webmanifest eller .json-format.',
    'png_192_help' => 'PNG-format. Kommer att storleksändras till 192x192.',
    'png_512_help' => 'PNG-format. Kommer att storleksändras till 512x512.',
    'delete' => 'Ta bort',
    'confirm_delete' => 'Är du säker på att du vill ta bort detta objekt?',
    'delete_error' => 'Kunde inte ta bort:',
    'delete_success' => 'Borttagen',
    'generic_error' => 'Ett fel inträffade.',
    'invalid_extension' => 'Invalid file extension. Allowed: %s',
    'manifest_icon_192' => 'Manifest Icon 192x192',
    'manifest_icon_512' => 'Manifest Icon 512x512',
    'favicon_96' => 'Favicon 96x96',
    'apple_touch_icon' => 'Apple Touch Icon',
    'web_manifest' => 'Web Manifest',
    'choose_file' => 'Välj fil',
    'no_file_selected' => 'Ingen fil vald',
    'save_error' => 'An error occurred while saving settings',
    'invalid_params' => 'Missing or invalid parameters.',
    'settings_not_found' => 'Settings not found for the specified language.',
    'update_error' => 'Database record could not be updated.',
    'delete_fail' => 'File could not be deleted.',
    'delete_unknown_error' => 'Okänt fel vid borttagning av fil.',
    'unsaved_changes' => 'Du har osparade andringar. Om du byter sprak forloras de.',
    'asset_generation' => [
        'minimum_size' => 'Huvudlogotypen måste vara minst 512x512 pixlar.',
        'requires_imagick' => 'Imagick krävs för automatisk asset-generering.',
        'output_not_writable' => 'Katalogen för asset-utdata är inte skrivbar.',
        'missing_upload' => 'Välj först en PNG-logotyp.',
    ],
    'missing_language' => 'Språkinformation saknas.',
    'settings_required_fields' => 'Plattform och URL krävs.',
    'no_records_found' => 'Inga poster hittades.',
    'no_url_configured' => 'Ingen URL konfigurerad',
];
