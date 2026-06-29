<?php
$lang['mod_seo'] = array(
  'title' => 'Contrôle SEO',
  'list' => 'Classement Google (SEO)',
  'settings' => 'Paramètres SEO',
  'button' => array(
    'save' => 'Enregistrer les paramètres',
    'back' => 'Retour',
    'upload' => 'Choisir un fichier JSON',
  ),
  'admin' => array(
    'columns' => array(
      'url' => 'Chemin URL',
      'name' => 'Titre de page',
      'type' => 'Type',
      'clicks' => 'Clics',
      'impressions' => 'Impressions',
      'ctr' => 'CTR (%)',
      'position' => 'Position moyenne',
    ),
    'labels' => array(
      'property_url' => 'URL de propriété Google Search Console (ex. sc-domain:naturalworld.tech)',
      'json_file' => 'Fichier de clé Google API (JSON)',
      'credentials_status' => 'Statut API',
      'credentials_ok' => 'Clé API JSON est téléchargée',
      'credentials_missing' => 'Clé API JSON est manquante! Veuillez la télécharger depuis la page des paramètres.',
    )
  ),
  'messages' => array(
    'success' => 'Paramètres mis à jour avec succès.',
    'error' => 'Une erreur est survenue.',
    'json_invalid' => 'Fichier JSON invalide!',
  ),
  'role_errors' => array(
    'list' => 'Vous n\'avez pas la permission de voir le Contrôle SEO',
    'settings' => 'Vous n\'avez pas la permission de gérer les Paramètres SEO',
  )
);
