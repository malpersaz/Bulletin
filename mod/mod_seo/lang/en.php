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
      'property_url' => 'Google Search Console Site Domain',
      'property_url_placeholder' => 'yourdomain.com',
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
    'name' => 'SEO Management Roles',
    'roles' => 'SEO Control',
    'list' => 'Can view SEO control and rankings',
    'settings' => 'Can manage SEO settings',
    'google_settings' => 'Can manage Google Cloud and Gemini API settings',
  ],
  'role_errors' => [
    'list' => 'You do not have permission to view SEO Control',
    'settings' => 'You do not have permission to manage SEO Settings',
    'google_settings' => 'You do not have permission to manage Google Services',
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
