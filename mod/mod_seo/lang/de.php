<?php
$lang['mod_seo'] = [
  'title' => 'SEO Control',
  'list' => 'Google Rankings (SEO)',
  'settings' => 'SEO Settings',
  'button' => [
    'save' => 'Save Settings',
    'back' => 'Back',
    'upload' => 'Choose JSON File',
    'setup_api' => 'Configure API Settings',
    'refresh' => 'Refresh',
  ],
  'admin' => [
    'columns' => [
      'url' => 'URL Path',
      'name' => 'Page Title',
      'type' => 'Type',
      'clicks' => 'Clicks',
      'impressions' => 'Impressions',
      'ctr' => 'CTR (%)',
      'position' => 'Avg Position',
    ],
    'labels' => [
      'property_url' => 'Google Search Console Domain',
      'property_url_placeholder' => 'ihredomain.de',
      'json_file' => 'Google API Key File (JSON)',
      'credentials_status' => 'API Status',
      'credentials_ok' => 'JSON API key is uploaded',
      'credentials_missing' => 'JSON API key is missing! Please upload it from the settings page.',
      'settings_credentials_missing' => 'JSON API key is not uploaded yet. Please choose the key file below and save.',
      'onboarding_title' => 'API Configuration Required',
      'onboarding_desc' => 'To fetch real-time Google search analytics (Clicks, Impressions, and Rankings), please upload your JSON API credentials.',
    ]
  ],
  'messages' => [
    'success' => 'Settings updated successfully.',
    'error' => 'An error occurred.',
    'json_invalid' => 'Invalid JSON file!',
  ],
  'role' => [
    'name' => 'SEO-Verwaltungsrollen',
    'roles' => 'SEO-Verwaltung',
    'list' => 'Kann SEO-Übersicht und Rankings ansehen',
    'settings' => 'Kann SEO-Einstellungen verwalten',
    'google_settings' => 'Kann Google Cloud- und Gemini API-Einstellungen verwalten',
  ],
  'role_errors' => [
    'list' => 'You do not have permission to view SEO Control',
    'settings' => 'You do not have permission to manage SEO Settings',
    'google_settings' => 'Sie haben keine Berechtigung zur Verwaltung der Google-Dienste',
  ],
  'google_settings' => 'Google Services Hub',
  'google' => [
    'bucket_name' => 'GCS (Google Cloud Storage) Bucket Name',
    'gemini_api_key' => 'Gemini API Key',
    'credentials_file' => 'Google Service Account File (credentials.json)',
    'credentials_status' => 'Credentials Status',
    'credentials_ok' => 'credentials.json file is uploaded',
    'credentials_missing' => 'credentials.json file is missing! Please upload it.',
    'settings_ok' => 'Google settings updated successfully.',
  ]
];
