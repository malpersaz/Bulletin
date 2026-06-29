<?php
$lang['mod_settings'] = [
    // Page & Section Titles
    'title' => 'Paramètres Généraux du Site',
    'description' => 'Gérer les paramètres à l\'échelle du site pour toutes les langues.',
    'lang_settings' => 'Paramètres de Langue',
    'social' => 'Liens des Médias Sociaux',
    'add_social' => 'Ajouter un Nouveau Lien',

    // Placeholders
    'platform_ph' => 'Plateforme (ex: Twitter)',
    'url_ph' => 'URL (https://...)',
    'icon_ph' => 'Classe d\'Icône (ex: bi bi-twitter-x)',

    // Form Labels
    'title' => 'Titre du Site',
    'description' => 'Description du Site',
    'owner_name' => 'Nom du Propriétaire',
    'established_year' => 'Année de Création',
    'phone' => 'Téléphone',
    'whatsapp' => 'WhatsApp',
    'email' => 'E-mail',
    'office_address' => 'Adresse du Bureau',
    'work_address' => 'Adresse de Travail',

    // Buttons & Actions
    'save' => 'Enregistrer',
    'list' => 'Liste',
    'button' => [
        'cancel' => 'Annuler',
        'close' => 'Fermer',
        'edit' => 'Modifier',
        'save_and_close' => 'Enregistrer et fermer',
        'generate_assets' => 'Generer les Icones',
        'confirm' => 'OK',
        'leave_page' => 'Quitter la page',
        'stay' => 'Rester',
    ],

    // Messages & Status
    'updated' => 'Paramètres mis à jour avec succès.',

    // Errors
    'perm_edit' => 'Vous n\'avez pas la permission de modifier les paramètres généraux.',
    'error' => 'Erreur',

    // === Access Control (ACL) ===
    'roll' => [
      'ad' => 'Rôles de Gestion des Paramètres du Site',
      'list' => 'Peut Voir les Paramètres du Site',
      'edit' => 'Peut Modifier les Paramètres du Site',
      'add' => 'Peut Ajouter de Nouveaux Paramètres du Site (pour les Langues)',
      'delete' => 'Peut Supprimer les Paramètres du Site'
    ],

    // === Permission Error Messages ===
    'permission_errors' => [
        'settings_edit' => 'Vous n\'avez pas la permission de modifier les paramètres du site.',
        'settings_view' => 'Vous n\'avez pas la permission de voir les paramètres du site.',
    ],

    // === Settings Page ===
    'settings' => [
        'title' => 'Gestion des Paramètres du Site',
    ],
    'sections' => [
        'seo_title' => 'Meta et Vérification',
        'seo_description' => 'Codes de vérification, métadonnées et champs d’intégration.',
        'assets_title' => 'Fichiers',
        'assets_description' => 'Televersez un logo PNG; le systeme generera automatiquement les icones necessaires.',
        'social_title' => 'Médias Sociaux',
        'social_description' => 'Gérer les liens des médias sociaux.',
    ],

    // === Success Messages ===
    'messages' => [
        'success' => [
            'updated' => 'Paramètres mis à jour avec succès.',
            'assets_generated' => 'Les fichiers d icones ont ete generes avec succes.',
        ],
    ],

    // === Additional Form Labels ===
    'map_embed_code' => 'Code d\'Intégration de Carte',
    'google_search_console' => 'Balise Google Search',
    'google_analytics' => 'Code Google Analytics',
    'meta_tags' => 'Balises Meta Supplémentaires',
    'show_logo_title' => 'Afficher le logo',
    'show_logo_desc' => 'Afficher le logo au lieu du titre du site',
    'logo_path' => 'Logo Principal (PNG)',
    'favicon_path' => 'Chemin du Favicon',

    // === UI Labels ===
    'select_language' => 'Sélectionner une Langue',
    'site_name' => 'Nom du Site',
    'site_description' => 'Description du Site',

    // === File Upload & Management ===
    'current_logo' => 'Current Logo:',
    'logo_not_uploaded' => 'Logo not uploaded.',
    'logo_help_text' => 'Televersez un PNG transparent. Minimum 512x512 px, recommande 1024x1024 px ou plus. Le systeme generera automatiquement les fichiers favicon, icone et manifest.',
    'favicon_not_uploaded' => 'Favicon not uploaded.',
    'favicon_help_text' => 'Format ICO ou PNG. Redimensionne en 32x32.',
    'not_uploaded' => 'Not uploaded.',
    'png_96_help' => 'PNG format. Will be resized to 96x96.',
    'png_180_help' => 'PNG format. Will be resized to 180x180.',
    'view_file' => 'View File',
    'webmanifest_help' => '.webmanifest or .json format.',
    'png_192_help' => 'PNG format. Will be resized to 192x192.',
    'png_512_help' => 'PNG format. Will be resized to 512x512.',
    'delete' => 'Delete',
    'confirm_delete' => 'Are you sure you want to delete this item?',
    'delete_error' => 'Could not delete:',
    'delete_success' => 'Deleted',
    'generic_error' => 'An error occurred.',
    'invalid_extension' => 'Invalid file extension. Allowed: %s',
    'manifest_icon_192' => 'Manifest Icon 192x192',
    'manifest_icon_512' => 'Manifest Icon 512x512',
    'favicon_96' => 'Favicon 96x96',
    'apple_touch_icon' => 'Apple Touch Icon',
    'web_manifest' => 'Web Manifest',
    'choose_file' => 'Choisir un fichier',
    'no_file_selected' => 'Aucun fichier sélectionné',
    'save_error' => 'An error occurred while saving settings',
    'loading_message' => 'Chargement...',
    'invalid_params' => 'Missing or invalid parameters.',
    'settings_not_found' => 'Settings not found for the specified language.',
    'update_error' => 'Database record could not be updated.',
    'delete_fail' => 'File could not be deleted.',
    'delete_unknown_error' => 'Erreur inconnue lors de la suppression du fichier.',
    'unsaved_changes' => 'Vous avez des modifications non enregistrees. Changer de langue les supprimera.',
    'asset_generation' => [
        'minimum_size' => 'Le logo principal doit faire au moins 512x512 pixels.',
        'requires_imagick' => 'Imagick est requis pour la generation automatique des assets.',
        'output_not_writable' => 'Le repertoire de sortie des assets n est pas accessible en ecriture.',
        'missing_upload' => 'Selectionnez d abord un logo PNG.',
    ],
    'missing_language' => 'Informations linguistiques manquantes.',
    'settings_required_fields' => 'La plateforme et l\'URL sont requises.',
    'no_records_found' => 'Aucun enregistrement trouvé.',
    'no_url_configured' => 'Aucune URL configurée',
    'header_style_title' => 'Style d’affichage de l’en-tête',
    'header_style_desc' => 'Choisissez comment le logo et le nom du site apparaîtront dans l’en-tête.',
    'header_style_square' => 'Logo carré uniquement',
    'header_style_rect' => 'Logo rectangulaire uniquement',
    'header_style_square_name' => 'Logo carré et nom du site',
    'header_style_rect_name' => 'Logo rectangulaire et nom du site',
    'logo_rect_title' => 'Logo rectangulaire (3:1)',
    'logo_square_title' => 'Logo carré (1:1)',
    'logo_rect_help_text' => 'Logo horizontal au ratio 3:1 utilisé sur le site.',
    'logo_square_help_text' => 'Logo carré au ratio 1:1. Utilisé pour générer les icônes.',
    'crop_square' => 'Recadrer le logo carré (1:1)',
    'crop_rect' => 'Recadrer le logo rectangulaire (3:1)',
    'crop_symbol' => 'Recadrer le symbole (1:1)',
    'logo_symbol_title' => 'Icône du site (1:1)',
    'logo_symbol_help_text' => 'Symbole carré contenant uniquement l’emblème. Utilisé en priorité pour générer les icônes.',
];
