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

    // === System Messages ===
    'information' => 'Information',
    'oke' => 'Operation Successful',
    'hata' => 'Error Occurred',
    'error' => 'Critical Error',

    // === Confirmation Messages ===
    'remove_image' => 'Are you sure you want to remove this image?',
    'remove_image_oke' => 'Image Removed Successfully',
    'choose_wish' => 'Please Select if Desired',
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
        'oke' => [
            'add' => 'Page Added Successfully',
            'edit' => 'Page Updated Successfully',
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
        'success' => [
            'delete' => 'Page deleted successfully',
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
        'vazgec' => 'Cancel',
        'kaldir' => 'Remove',
        'evet' => 'Yes',
        'hayir' => 'No',
        'gallery' => 'Image Gallery',
        'file' => 'File Manager',
        'tamam' => 'OK',
        'upload' => 'Upload',
    ],

    // === Actions ===
    'actions' => [
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'unlock_reorder' => 'Unlock Drag & Drop',
        'lock_reorder' => 'Lock Drag & Drop',
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Please do not leave the %s field empty',
        'length' => '%s must be between %s and %s characters',
        'email' => 'Please enter a valid email address in the %s field',
        'unique' => 'The %s you entered is already registered, please choose another %s',
        'inarray' => 'The %s field only accepts values: \' %s \'',
    ],

    // === Role Permissions ===
    'roll' => [
        'ad' => 'Page Management Roles',
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
    ],
    'quick_unpublish_confirm' => 'Are you sure you want to unpublish this page?',
    'quick_publish_confirm' => 'Are you sure you want to publish this page?',
    'published_msg' => 'Page Published Successfully',
    'unpublished_msg' => 'Page Unpublished Successfully',
    'status_badge' => [
        'active' => 'Published',
        'draft' => 'Draft',
    ],
];
