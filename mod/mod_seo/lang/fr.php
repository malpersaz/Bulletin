<?php
$lang['mod_seo'] = [
  'title' => 'Contrôle SEO',
  'list' => 'Classement Google (SEO)',
  'settings' => 'Paramètres SEO',
  'button' => [
    'save' => 'Enregistrer les paramètres',
    'back' => 'Retour',
    'upload' => 'Choisir un fichier JSON',
    'setup_api' => 'Configurer l\'API',
    'refresh' => 'Actualiser',
  ],
  'admin' => [
    'columns' => [
      'url' => 'Chemin URL',
      'name' => 'Titre de page',
      'type' => 'Type',
      'clicks' => 'Clics',
      'impressions' => 'Impressions',
      'ctr' => 'CTR (%)',
      'position' => 'Position moyenne',
    ],
    'labels' => [
      'property_url' => 'Domaine du site Google Search Console',
      'property_url_placeholder' => 'votredomaine.com',
      'json_file' => 'Fichier de clé Google API (JSON)',
      'credentials_status' => 'Statut API',
      'credentials_ok' => 'Clé API JSON est téléchargée',
      'credentials_missing' => 'Clé API JSON est manquante! Veuillez la télécharger depuis la page des paramètres.',
      'settings_credentials_missing' => 'La clé API JSON n\'est pas encore téléchargée. Veuillez choisir le fichier ci-dessous et enregistrer.',
      'onboarding_title' => 'Configuration API Requise',
      'onboarding_desc' => 'Pour obtenir les analyses de recherche Google en temps réel (Clics, Impressions et Positions), veuillez téléverser vos identifiants JSON.',
    ]
  ],
  'messages' => [
    'success' => 'Paramètres mis à jour avec succès.',
    'error' => 'Une erreur est survenue.',
    'json_invalid' => 'Fichier JSON invalide!',
  ],
  'role' => [
    'name' => 'Rôles de gestion SEO',
    'roles' => 'Contrôle SEO',
    'list' => 'Peut afficher le contrôle et le classement SEO',
    'settings' => 'Peut gérer les paramètres SEO',
    'google_settings' => 'Peut gérer les paramètres Google Cloud et Gemini API',
  ],
  'role_errors' => [
    'list' => 'Vous n\'avez pas la permission de voir le Contrôle SEO',
    'settings' => 'Vous n\'avez pas la permission de gérer les Paramètres SEO',
    'google_settings' => 'Vous n\'avez pas la permission de gérer les services Google',
  ],
  'google_settings' => 'Google Services Hub',
  'google' => [
    'bucket_name' => 'Nom du bucket GCS (Google Cloud Storage)',
    'gemini_api_key' => 'Clé API Gemini',
    'credentials_file' => 'Fichier de compte de service Google (credentials.json)',
    'credentials_status' => 'Statut des identifiants',
    'credentials_ok' => 'Le fichier credentials.json est téléversé',
    'credentials_missing' => 'Le fichier credentials.json est manquant! Veuillez le téléverser.',
    'settings_ok' => 'Les paramètres Google ont été mis à jour avec succès.',
  ]
];
