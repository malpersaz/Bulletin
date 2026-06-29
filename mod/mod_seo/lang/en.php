<?php
$lang['mod_seo'] = array(
  'title' => 'SEO Control',
  'list' => 'Google Rankings (SEO)',
  'settings' => 'SEO Settings',
  'button' => array(
    'save' => 'Save Settings',
    'back' => 'Back',
    'upload' => 'Choose JSON File',
  ),
  'admin' => array(
    'columns' => array(
      'url' => 'URL Path',
      'name' => 'Page Title',
      'type' => 'Type',
      'clicks' => 'Clicks',
      'impressions' => 'Impressions',
      'ctr' => 'CTR (%)',
      'position' => 'Avg Position',
    ),
    'labels' => array(
      'property_url' => 'Google Search Console Property URL (e.g. sc-domain:naturalworld.tech)',
      'json_file' => 'Google API Key File (JSON)',
      'credentials_status' => 'API Status',
      'credentials_ok' => 'JSON API key is uploaded',
      'credentials_missing' => 'JSON API key is missing! Please upload it from the settings page.',
    )
  ),
  'messages' => array(
    'success' => 'Settings updated successfully.',
    'error' => 'An error occurred.',
    'json_invalid' => 'Invalid JSON file!',
  ),
  'role_errors' => array(
    'list' => 'You do not have permission to view SEO Control',
    'settings' => 'You do not have permission to manage SEO Settings',
  )
);
