<?php
$lang['mod_settings'] = [
    // Page & Section Titles
    'settings' => [
        'title' => 'General Site Settings',
        'description' => 'Manage site-wide settings for all languages.',
    ],
    'lang_settings' => 'Language Settings',
    'social' => 'Social Media Links',
    'add_social' => 'Add New Link',

    // Messages
    'messages' => [
        'success' => [
            'updated' => 'Settings successfully updated.',
        ]
    ],

    // Placeholders
    'platform_ph' => 'Platform (e.g., Twitter)',
    'url_ph' => 'URL (https://...)',
    'icon_ph' => 'Icon Class (e.g., bi bi-twitter-x)',

    // Form Labels
    'title' => 'Site Title',
    'description' => 'Site Description',
    'owner_name' => 'Owner Name',
    'established_year' => 'Established Year',
    'phone' => 'Phone',
    'whatsapp' => 'WhatsApp',
    'email' => 'Email',
    'office_address' => 'Office Address',
    'work_address' => 'Work Address',

    // Buttons & Actions
    'save' => 'Save',
    'list' => 'List',
    'button' => [
        'cancel' => 'Cancel',
        'close' => 'Close',
        'edit' => 'Edit',
        'save_and_close' => 'Save and Close',
        'generate_assets' => 'Generate Icons',
        'confirm' => 'OK',
        'leave_page' => 'Leave Page',
        'stay' => 'Stay',
    ],

  
    // Errors
    'perm_edit' => 'You do not have permission to edit general settings.',
    'error' => 'Error',

    // === Access Control (ACL) ===
    'roll' => [
      'ad' => 'Site Settings Management Roles',
      'list' => 'Can View Site Settings',
      'edit' => 'Can Edit Site Settings',
      'add' => 'Can Add New Site Settings (for Languages)',
      'delete' => 'Can Delete Site Settings'
    ],

    // === Permission Error Messages ===
    'permission_errors' => [
        'settings_edit' => 'You do not have permission to edit site settings.',
        'settings_view' => 'You do not have permission to view site settings.',
    ],

    // === Settings Page ===
    'settings' => [
        'title' => 'Site Settings Management',
    ],
    'sections' => [
        'seo_title' => 'Meta and Verification',
        'seo_description' => 'Verification codes, metadata, and embed fields.',
        'assets_title' => 'Assets',
        'assets_description' => 'Upload one PNG logo and let the system generate the required icons automatically.',
        'social_title' => 'Social Media',
        'social_description' => 'Manage website social media links.',
    ],

    // === Success Messages ===
    'messages' => [
        'success' => [
            'updated' => 'Settings updated successfully.',
            'assets_generated' => 'Icon files generated successfully.',
        ],
    ],

    // === Additional Form Labels ===
    'map_embed_code' => 'Map Embed Code',
    'google_search_console' => 'Google Search Tag',
    'google_analytics' => 'Google Analytics Code',
    'meta_tags' => 'Additional Meta Tags',
    'show_logo_title' => 'Show Logo',
    'show_logo_desc' => 'Display logo instead of site title',
    'logo_path' => 'Master Logo (PNG)',
    'favicon_path' => 'Favicon Path',

    // === UI Labels ===
    'select_language' => 'Select Language',
    'site_name' => 'Site Name',
    'site_description' => 'Site Description',

    // === File Upload & Management ===
    'current_logo' => 'Current Logo:',
    'logo_not_uploaded' => 'Logo not uploaded.',
    'logo_help_text' => 'Upload a transparent PNG. Minimum 512x512 px, recommended 1024x1024 px or larger. The system will generate favicon, icon, and manifest files automatically.',
    'favicon_not_uploaded' => 'Favicon not uploaded.',
    'favicon_help_text' => 'ICO or PNG format. Will be resized to 32x32.',
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
    'choose_file' => 'Choose File',
    'no_file_selected' => 'No file selected',
    'save_error' => 'An error occurred while saving settings',
    'missing_language' => 'Language information is missing.',
    'invalid_params' => 'Missing or invalid parameters.',
    'settings_not_found' => 'Settings not found for the specified language.',
    'update_error' => 'Database record could not be updated.',
    'delete_fail' => 'File could not be deleted.',
    'delete_unknown_error' => 'Unknown file deletion error.',
    'unsaved_changes' => 'You have unsaved changes. Switching languages will discard them.',
    'asset_generation' => [
        'minimum_size' => 'The master logo must be at least 512x512 pixels.',
        'requires_imagick' => 'Imagick is required for automatic asset generation.',
        'output_not_writable' => 'The asset output directory is not writable.',
        'missing_upload' => 'Select a PNG logo first.',
    ],
    'settings_required_fields' => 'Platform and URL are required.',
    'no_records_found' => 'No records found.',
    'no_url_configured' => 'No URL configured',
];
