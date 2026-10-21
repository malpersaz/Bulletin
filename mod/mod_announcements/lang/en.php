<?php
/**
 * Announcement Module English Language File
 * Amed Framework CMS - Multilingual Content Management System
 *
 * This file contains all English translations for the announcement management module.
 * Professional translations with modern CMS terminology.
 */

$lang['mod_announcements'] = [
    'total_announcements' => 'Total Announcements',
    'draft_announcements' => 'Drafts',
    'status_badge' => [
        'active' => 'Active',
        'draft' => 'Draft',
        'general' => 'General',
    ],
    'empty_state' => [
        'title' => 'No Announcements Found',
        'description' => 'No announcements have been created yet or none match your search criteria.',
        'search_title' => 'No Results Found',
        'search_description' => 'No announcements match your search query.',
        'clear_search' => 'Clear Search',
    ],
    'wizard' => [
        'step' => [
            'language' => 'Language Selection',
            'title' => 'Announcement Title',
            'date' => 'Announcement Date',
            'intro' => 'Intro Text (Deck)',
            'content' => 'Announcement Content',
            'image' => 'Cover Image',
            'featured' => 'Display in Featured',
            'acl' => 'Access Control',
            'finish' => 'Publish Status',
        ],
        'subtitle' => [
            'language' => 'Select the primary language for this announcement.',
            'title' => 'Enter an engaging title. (Min: 1, Max: 70)',
            'date' => 'Date and time of the announcement publication.',
            'intro' => 'Write a concise summary. (90 - 120 Characters)',
            'content' => 'Rich text details of the announcement.',
            'image' => 'Upload a cover image for the announcement (3:4 ratio).',
            'featured' => 'Choose whether to highlight this announcement in the showcase section.',
            'acl' => 'Choose which user groups can access this announcement.',
            'finish' => 'Review details and set the publish status.',
        ],
        'label' => [
            'announcement_title' => 'Announcement Title',
            'intro_text' => 'Intro Text',
            'featured_yes' => 'Display in Featured Showcase',
            'featured_yes_desc' => 'The announcement will be highlighted prominently on the site.',
            'featured_no' => 'Standard Listing Only',
            'featured_no_desc' => 'The announcement will appear only in the standard announcements feed.',
            'acl_select' => 'Select Access Level...',
            'acl_public' => 'Public (All Visitors)',
            'acl_public_desc' => 'Visible to everyone without access restrictions.',
            'publish_yes' => 'Published',
            'publish_yes_desc' => 'The announcement will be published immediately.',
            'publish_no' => 'Draft / Unpublished',
            'publish_no_desc' => 'Saved as draft, visible only to administrators.',
            'change_image' => 'Change Image',
        ],
        'summary' => [
            'title' => 'Announcement Summary',
            'selected_none' => 'Not Selected',
            'lang' => 'Language',
            'name' => 'Title',
            'date' => 'Date',
            'featured' => 'Featured',
            'acl' => 'Access Control',
            'status' => 'Publish Status',
        ],
        'button' => [
            'save' => 'Save',
            'next' => 'Continue',
            'prev' => 'Back',
            'finish' => 'Save & Finish',
        ],
        'image' => [
            'drop_title' => 'Drop Image Here or Click to Upload',
            'drop_description' => 'PNG, JPG, WEBP (Max 5MB)',
            'upload_link' => 'Browse Image',
            'link_separator' => 'or import via link',
            'link_placeholder' => 'https://example.com/image.jpg',
            'link_action' => 'Import Image',
            'hint_loading' => 'Uploading image...',
            'hint_invalid_type' => 'Invalid file type',
            'hint_no_drop_data' => 'No image data received',
            'hint_remote_failed' => 'Remote image fetch failed',
        ],
        'cropper' => [
            'title' => 'Crop Image (3:4)',
            'instruction' => 'Adjust and crop the image to the 3:4 announcement aspect ratio.',
            'zoom_in' => 'Zoom In',
            'zoom_out' => 'Zoom Out',
            'rotate_left' => 'Rotate Left',
            'rotate_right' => 'Rotate Right',
            'reset' => 'Reset',
            'apply' => 'Crop & Save Image',
            'cancel' => 'Cancel',
            'error_plugin' => 'Cropper plugin failed to load',
        ],
    ],
    'add_image' => 'Add Image',
    'upload_change_image' => 'Upload / Change Image',
    'quick_publish_confirm' => 'Do you want to publish this announcement?',
    'quick_unpublish_confirm' => 'Do you want to unpublish this announcement?',
    'delete_confirm' => 'Are you sure you want to delete this announcement?',
    'image_updated' => 'Image updated successfully',
    // Basic Operations
    'add' => 'Add Announcement',
    'edit' => 'Edit Announcement',
    'list' => 'Announcements',

    // System Messages
    'information' => 'Information',
    'success' => 'Operation Successful',
    'error' => 'Critical Error',

    // Confirmation Messages
    'remove_image' => 'Are you sure you want to remove this image?',
    'remove_image_success' => 'Image Removed Successfully',
    'choose_wish' => 'Please Select if Desired',
    'remove_record' => 'Are you sure you want to delete this record?',

    // Operation Messages
    'message' => [
        'success' => [
            'add' => 'Announcement Added Successfully',
            'edit' => 'Announcement Updated Successfully',
        ],
        'error' => [
            'add' => 'Failed to Add Announcement',
            'edit' => 'Failed to Update Announcement',
        ],
        'information' => [
            'moderate' => 'Double-click on the content you want to edit',
            'move' => 'Drag and drop content to change its order',
        ],
    ],

    // Error Messages
    'errors' => [
        'record' => 'The specified record was not found',
    ],

    // Button Labels
    'button' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'refresh' => 'Refresh',
        'delete' => 'Delete',
        'gallery' => 'Image Gallery',
        'file' => 'File Manager',
        'cancel' => 'Cancel',
        'remove' => 'Remove',
        'yes' => 'Yes',
        'no' => 'No',
    ],

    // Data Grid Messages
    'grid' => [
        'showing_results' => 'Showing {from} to {to} of {total} entries',
        'page' => 'Page',
        'page_count' => 'Total Pages',
        'loading' => 'Loading data, please wait...',
        'no_data' => 'No data available',
        'connection_error' => 'Connection Error',
        'search' => 'Search',
        'select_at_least_one' => 'Please select at least one row',
        'select_only_one' => 'Please select only one row',
        'publish' => [
            'key' => 'Publish',
            'value' => 'Are you sure you want to publish this announcement?',
        ],
        'unpublish' => [
            'key' => 'Unpublish',
            'value' => 'Are you sure you want to unpublish this announcement?',
        ],
    ],

    // Form Validation Messages
    'validator' => [
        'required' => 'Please fill in the %s field',
        'length' => 'The %s field must be between %s and %s characters',
        'email' => 'Please enter a valid email address for the %s field',
        'unique' => 'The %s you entered is already registered in the system. Please choose a different %s',
        'inarray' => 'The %s field can only contain the following values: %s',
        'title_length' => 'You must enter a title consisting of at least 1 and at most 70 characters.',
        'deck_length' => 'Please enter an introductory text of at least 90 characters and a maximum of 120 characters.',
    ],

    // Role Management
    'role' => [
        'name' => 'Announcement Management Roles',
        'add' => 'Can Add Announcements',
        'edit' => 'Can Edit Announcements',
        'delete' => 'Can Delete Announcements',
        'list' => 'Can View Announcement Management',
        'gallery' => 'Can Add Gallery to Announcements',
        'file' => 'Can Add Files to Announcements',
        'moderate_edit' => 'Can Edit Announcement Records Belonging to Others',
        'moderate_delete' => 'Can Delete Announcement Records Belonging to Others',
    ],

    // Role-based Error Messages
    'role_errors' => [
        'moderate_edit' => 'You cannot edit announcement records that do not belong to you',
        'moderate_delete' => 'You cannot delete announcement records that do not belong to you',
        'add' => 'You do not have permission to add announcements',
        'edit' => 'You do not have permission to edit announcements',
        'list' => 'You do not have permission to view announcement management',
        'delete' => 'You do not have permission to delete announcements',
        'gallery' => 'You do not have permission to add gallery to announcements',
        'file' => 'You do not have permission to add files to announcements',
    ],

    // Admin Panel Labels
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Language',
            'name' => 'Announcement Title',
            'title' => 'Announcement Title',
            'seo' => 'SEO URL',
            'date' => 'Date',
            'created_at' => 'Date',
            'status' => 'Publish Status',
            'deck' => 'Display in Featured',
            'summary' => 'Introduction (Featured)',
            'content' => 'Content',
            'image' => 'Image',
            'acl_id' => 'Who can view this announcement? ( <a href=\'mod_acl/list/\' target=\'_blank\'>ACL - Access Control List</a> )',
        ],
        'radio' => [
            'publish_yes' => 'Yes',
            'publish_no' => 'No',
            'deck_yes' => 'Yes',
            'deck_no' => 'No',
        ],
    ],
];