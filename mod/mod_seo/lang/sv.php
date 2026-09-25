<?php
$lang['mod_seo'] = [
  'title' => 'SEO-kontroll',
  'list' => 'Google-rankningar (SEO)',
  'settings' => 'SEO-inställningar',
  'button' => [
    'save' => 'Spara inställningar',
    'back' => 'Tillbaka',
    'upload' => 'Välj JSON-fil',
    'setup_api' => 'Konfigurera API',
    'refresh' => 'Uppdatera',
  ],
  'admin' => [
    'columns' => [
      'url' => 'URL-sökväg',
      'name' => 'Sidtitel',
      'type' => 'Typ',
      'clicks' => 'Klick',
      'impressions' => 'Visningar',
      'ctr' => 'CTR (%)',
      'position' => 'Medelposition',
    ],
    'labels' => [
      'property_url' => 'Google Search Console Domän',
      'property_url_placeholder' => 'dindoman.com',
      'json_file' => 'Google API-nyckelfil (JSON)',
      'credentials_status' => 'API-status',
      'credentials_ok' => 'JSON API-nyckel har laddats upp',
      'credentials_missing' => 'JSON API-nyckel saknas! Vänligen ladda upp den från inställningssidan.',
      'settings_credentials_missing' => 'JSON API-nyckel har inte laddats upp ännu. Välj nyckelfilen nedan och spara.',
      'onboarding_title' => 'API-konfiguration krävs',
      'onboarding_desc' => 'För att hämta realtidsanalyser från Google-sökning (Klick, Visningar och Rankningar), ladda upp dina JSON API-uppgifter.',
    ]
  ],
  'messages' => [
    'success' => 'Inställningarna har uppdaterats.',
    'error' => 'Ett fel uppstod.',
    'json_invalid' => 'Ogiltig JSON-fil!',
  ],
  'role' => [
    'name' => 'SEO-hanteringsroller',
    'roles' => 'SEO-kontroll',
    'list' => 'Kan visa SEO-kontroll och rankningar',
    'settings' => 'Kan hantera SEO-inställningar',
    'google_settings' => 'Kan hantera inställningar för Google Cloud och Gemini API',
  ],
  'role_errors' => [
    'list' => 'Du har inte behörighet att visa SEO-kontroll',
    'settings' => 'Du har inte behörighet att hantera SEO-inställningar',
    'google_settings' => 'Du har inte behörighet att hantera Google-tjänster',
  ],
  'google_settings' => 'Google Services Hub',
  'google' => [
    'bucket_name' => 'GCS (Google Cloud Storage) Bucket-namn',
    'gemini_api_key' => 'Gemini API-nyckel',
    'credentials_file' => 'Google Service Account-fil (credentials.json)',
    'credentials_status' => 'Uppgiftsstatus',
    'credentials_ok' => 'credentials.json-filen har laddats upp',
    'credentials_missing' => 'credentials.json-fil saknas! Vänligen ladda upp den.',
    'settings_ok' => 'Google-inställningarna har sparats.',
  ]
];
