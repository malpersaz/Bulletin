<?php
$lang['mod_seo'] = array(
  'title' => 'SEO-kontroll',
  'list' => 'Google-rankningar (SEO)',
  'settings' => 'SEO-inställningar',
  'button' => array(
    'save' => 'Spara inställningar',
    'back' => 'Tillbaka',
    'upload' => 'Välj JSON-fil',
  ),
  'admin' => array(
    'columns' => array(
      'url' => 'URL-sökväg',
      'name' => 'Sidtitel',
      'type' => 'Typ',
      'clicks' => 'Klick',
      'impressions' => 'Visningar',
      'ctr' => 'CTR (%)',
      'position' => 'Medelposition',
    ),
    'labels' => array(
      'property_url' => 'Google Search Console Egendoms-URL (t.ex. sc-domain:naturalworld.tech)',
      'json_file' => 'Google API-nyckelfil (JSON)',
      'credentials_status' => 'API-status',
      'credentials_ok' => 'JSON API-nyckel har laddats upp',
      'credentials_missing' => 'JSON API-nyckel saknas! Vänligen ladda upp den från inställningssidan.',
    )
  ),
  'messages' => array(
    'success' => 'Inställningarna har uppdaterats.',
    'error' => 'Ett fel uppstod.',
    'json_invalid' => 'Ogiltig JSON-fil!',
  ),
  'role_errors' => array(
    'list' => 'Du har inte behörighet att visa SEO-kontroll',
    'settings' => 'Du har inte behörighet att hantera SEO-inställningar',
  )
);
