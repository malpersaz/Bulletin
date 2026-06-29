<?php
$lang['mod_seo'] = array(
  'title' => 'SEO-Kontrolle',
  'list' => 'Google-Rankings (SEO)',
  'settings' => 'SEO-Einstellungen',
  'button' => array(
    'save' => 'Einstellungen speichern',
    'back' => 'Zurück',
    'upload' => 'JSON-Datei auswählen',
  ),
  'admin' => array(
    'columns' => array(
      'url' => 'URL-Pfad',
      'name' => 'Seitentitel',
      'type' => 'Typ',
      'clicks' => 'Klicks',
      'impressions' => 'Impressionen',
      'ctr' => 'CTR (%)',
      'position' => 'Mittlere Position',
    ),
    'labels' => array(
      'property_url' => 'Google Search Console-Property-URL (z. B. sc-domain:naturalworld.tech)',
      'json_file' => 'Google API-Schlüsseldatei (JSON)',
      'credentials_status' => 'API-Status',
      'credentials_ok' => 'JSON API-Schlüssel hochgeladen',
      'credentials_missing' => 'JSON API-Schlüssel fehlt! Bitte über die Einstellungsseite hochladen.',
    )
  ),
  'messages' => array(
    'success' => 'Einstellungen erfolgreich aktualisiert.',
    'error' => 'Ein Fehler ist aufgetreten.',
    'json_invalid' => 'Ungültige JSON-Datei!',
  ),
  'role_errors' => array(
    'list' => 'Sie haben keine Berechtigung zum Anzeigen der SEO-Kontrolle',
    'settings' => 'Sie haben keine Berechtigung zum Verwalten der SEO-Einstellungen',
  )
);
