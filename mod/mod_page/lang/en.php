<?php
/**
 * Page Management Module English Language File
 * Amed Framework CMS - Multilingual Content Management System
 *
 * This file contains all English translations for the page management module.
 * Professional translations with modern CMS terminology.
 */

$lang['mod_page'] = [
    // === Basic Operations ===
    'add' => 'Add Page',
    'edit' => 'Edit Page',
    'list' => 'Page List',
    'total_pages' => 'Total Pages',
    'published_pages' => 'Published',
    'draft_pages' => 'Drafts',
    'expand_all' => 'Expand All',
    'collapse_all' => 'Collapse All',
    'search_placeholder' => 'Search pages...',

    // === System Messages ===
    'information' => 'Information',
    'success' => 'Operation Successful',
    'error' => 'Error Occurred',

    // === Confirmation Messages ===
    'remove_image' => 'Remove Image',
    'remove_image_question' => 'Are you sure you want to remove this image?',
    'remove_image_success' => 'Image Removed Successfully',
    'choose_wish' => 'Please Select if Desired',
    'empty' => [
        'no_pages' => 'No Pages Found',
        'description' => 'No pages found yet. You can start building your page hierarchy by adding a new page.',
    ],
    'menu_options' => [
        'none' => 'Do Not Add to Menu',
        'top_level' => 'Add as Top-Level Menu Item',
        'slot_root' => 'Add Under Selected Menu Surface',
    ],
    'menu_slot_options' => [
        'none' => 'No Menu Surface',
        'header' => 'Header Menu',
        'footer' => 'Footer Menu',
    ],
    'remove_record' => 'Are you sure you want to delete this record?',
    'seo_meta' => 'SEO Metadata',

    // === System Messages ===
    'messages' => [
        'success' => [
            'add' => 'Page Added Successfully',
            'edit' => 'Page Updated Successfully',
            'delete' => 'Page deleted successfully',
        ],
        'error' => [
            'add' => 'Failed to Add Page',
            'edit' => 'Failed to Update Page',
        ],
        'information' => [
            'moderate' => 'Double-click on the page you want to edit',
            'move' => 'Drag and drop pages to change their order',
        ],
        'warning' => [
            'reorder' => 'Change page order?',
            'delete' => 'Are you sure you want to delete this page?',
        ],
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'reorder_single' => 'Do you want to change the order of this page?',
        'delete_single' => 'Are you sure you want to delete this page?',
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Yes',
        'no' => 'No',
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'The specified record was not found',
        'slot_branch_missing' => 'The selected menu surface could not be resolved for this language',
    ],

    // === Button Labels ===
    'button' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'cancel' => 'Cancel',
        'remove' => 'Remove',
        'yes' => 'Yes',
        'no' => 'No',
        'gallery' => 'Image Gallery',
        'file' => 'File Manager',
        'ok' => 'OK',
        'upload' => 'Upload',
    ],

    // === Actions ===
    'actions' => [
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'unlock_reorder' => 'Reorder',
        'lock_reorder' => 'Lock',
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Please do not leave the %s field empty',
        'length' => '%s must be between %s and %s characters',
        'email' => 'Please enter a valid email address in the %s field',
        'unique' => 'The %s you entered is already registered, please choose another %s',
        'inarray' => 'The %s field only accepts values: \' %s \'',
        'deck_length' => 'Please enter an introductory text between 90 and 120 characters.',
    ],

    // === Role Permissions ===
    'role' => [
        'name' => 'Page Management Roles',
        'add' => 'Can Add Page',
        'edit' => 'Can Edit Page',
        'delete' => 'Can Remove Page',
        'list' => 'Can View Page Management',
        'gallery' => 'Can Add Gallery?',
        'file' => 'Can Add File?',
        'moderate_edit' => 'Can Edit Pages Not Belonging to Them',
        'moderate_delete' => 'Can Remove Pages Not Belonging to Them',
        'approval' => 'Can Publish/Unpublish Pages?',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'moderate_edit' => 'You Cannot Edit Pages That Are Not Yours',
        'moderate_delete' => 'You Cannot Remove Pages That Are Not Yours',
        'add' => 'You Do Not Have Permission To Add Pages',
        'edit' => 'You Do Not Have Permission To Edit Pages',
        'list' => 'You Do Not Have Permission To View Page Management',
        'delete' => 'You Do Not Have Permission To Remove Pages',
        'gallery' => 'You Do Not Have Permission To Add Gallery',
        'file' => 'You Do Not Have Permission To Add Files',
        'approval' => 'You Do Not Have Permission To Publish/Unpublish Pages',
    ],

    // === Admin Panel ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Display Language',
            'name' => 'Page Name',
            'parent_id' => 'Sub Page',
            'order' => 'Order',
            'root' => 'Is this a root page that should not be displayed?',
            'status' => 'Publish?',
            'publish_desc' => 'Do you want to publish this content or save it as a draft?',
            'deck' => 'Show Introduction in Header?',
            'summary' => 'Introduction (Deck)',
            'content' => 'Content',
            'gallery_id' => 'Gallery ID (To display images from another page, enter that page\'s ID)',
            'file_id' => 'File ID (To display files from another page, enter that page\'s ID)',
            'seo' => 'Page URL (Slug)',
            'title' => 'Title (For Search Engines)',
            'keywords' => 'Keywords (For Search Engines)',
            'description' => 'Description (For Search Engines)',
            'image' => 'Image',
            'acl_id' => 'Who Can See This Page?',
            'category_id' => 'Related Content Category',
            'menu' => 'Send Page to Menu',
            'menu_slot' => 'Menu Surface',
        ],
        'select_value' => [
            'root_e' => 'Yes',
            'root_h' => 'No',
        ],
        'radio' => [
            'publish_yes' => 'Publish',
            'publish_no' => 'Save as Draft',
            'deck_yes' => 'Yes',
            'deck_no' => 'No',
        ],
    ],
    'wizard' => [
        'steps' => [
            'language' => 'Language',
            'structure' => 'Structure',
            'title' => 'Title',
            'summary' => 'Summary',
            'content' => 'Content',
            'display' => 'Display',
            'relations' => 'Relations',
            'seo_finish' => 'SEO & Finish',
        ],
        'descriptions' => [
            'language' => 'Choose the language before building the page tree and related options.',
            'structure' => 'Choose where this page belongs and whether it should connect to category and menu trees.',
            'title' => 'Define the page name that will drive the primary page identity.',
            'summary' => 'Write the short intro/deck text shown ahead of the page body.',
            'content' => 'Compose the actual page body.',
            'display' => 'Define structural display behavior for the page.',
            'relations' => 'Connect optional related records and media references.',
            'seo_finish' => 'Review SEO values, choose publish status, and finish the page setup.',
        ],
        'page_summary' => 'Page Summary',
        'previous' => 'Previous',
        'next' => 'Next',
        'image' => [
            'drop_title' => 'Upload, paste, or move',
            'drop_description' => 'Upload, paste, or move',
            'upload_link' => 'Upload Image',
            'link_separator' => 'OR',
            'link_placeholder' => 'Paste image link',
            'link_action' => 'Import',
            'hint_loading' => 'Importing image...',
            'hint_invalid_type' => 'Only image files are supported here.',
            'hint_no_drop_data' => 'No usable image was found in the dropped content.',
            'hint_remote_failed' => 'The image could not be imported from that source.',
        ],
    ],
    'quick_unpublish_confirm' => 'Are you sure you want to unpublish this page?',
    'quick_publish_confirm' => 'Are you sure you want to publish this page?',
    'published_msg' => 'Page Published Successfully',
    'unpublished_msg' => 'Page Unpublished Successfully',
    'status_badge' => [
        'active' => 'Published',
        'draft' => 'Draft',
    ],
    'picker' => [
        'file' => 'Select Existing File',
    ],
    'ajax' => [
        'database_error' => 'Database Error',
        'record_not_found' => 'Page not found!',
        'unknown_type' => 'Unknown relation type',
        'relation_updated' => 'Relation successfully updated',
    ],
];
