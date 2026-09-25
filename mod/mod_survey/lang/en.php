<?php
/**
 * Poll/Survey Module - English Language File
 *
 * @package AKM Business
 * @module  mod_survey
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Poll/Survey Module Language Translations
 */
$lang['mod_survey'] = [
    'add' => 'Add Poll',
    'choose_wish' => 'Please choose if you want',

    // === Navigation & Actions ===
    'actions' => [
        'add' => 'Add Poll',
        'edit' => 'Edit Poll',
        'list' => 'Poll List',
        'delete' => 'Delete Poll',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'update' => 'Update',
        'remove' => 'Remove',
    ],

    // === System Messages ===
    'messages' => [
        'success' => [
            'added' => 'Poll successfully added!',
            'updated' => 'Poll successfully updated!',
            'deleted' => 'Poll successfully deleted!',
            'set_default' => 'Poll set as default!',
        ],
        'error' => [
            'not_added' => 'Failed to add poll!',
            'not_updated' => 'Failed to update poll!',
            'not_deleted' => 'Failed to delete poll!',
            'not_found' => 'The specified poll record could not be found!',
        ],
        'info' => [
            'edit_double_click' => 'Double-click the poll you want to edit',
            'drag_reorder' => 'Drag and drop the poll to reorder',
        ],
    ],

    // === Status Types ===
    'status' => [
        'published' => 'Published',
        'draft' => 'Draft',
        'archived' => 'Archived',
        'default' => 'Default Poll',
    ],

    // === Yes/No Options ===
    'options' => [
        'yes' => 'Yes',
        'no' => 'No',
    ],

    // === Form Labels ===
    'form' => [
        'basic_info' => 'Basic Information',
        'display_settings' => 'Display Settings',
        'advanced_settings' => 'Advanced Settings',

        'id' => 'ID',
        'question' => 'Poll Question',
        'language' => 'Display Language',
        'is_default' => 'Set as Default Poll',
        'access_control' => 'Access Control',
        'options' => 'Poll Options',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'multiple_choice' => 'Allow Multiple Selections',
    ],

    // === Table Columns ===
    'columns' => [
        'id' => 'ID',
        'language' => 'Display Language',
        'question' => 'Poll Question',
        'is_default' => 'Default Poll',
        'status' => 'Status',
        'created_at' => 'Created Date',
        'access_level' => 'Access Level',
        'actions' => 'Actions',
    ],

    // === Buttons ===
    'buttons' => [
        'add' => 'Add New Poll',
        'edit' => 'Edit Selected',
        'delete' => 'Delete Selected',
        'refresh' => 'Refresh',
        'set_default' => 'Set as Default',
        'view_results' => 'View Results',
        'export' => 'Export Results',
    ],

    // === Validation Messages ===
    'validation' => [
        'required' => ':field field is required',
        'min_length' => ':field field must be at least :min characters',
        'max_length' => ':field field may not be greater than :max characters',
        'unique' => ':field field must be unique',
        'email' => 'Please enter a valid email address',
        'min_options' => 'Poll must have at least 2 options',
        'max_options' => 'Poll cannot have more than :max options',
    ],

    // === Access Control (ACL) ===
    'role' => [
        'name' => 'Poll Management Permissions',
        'list' => 'Can View Polls',
        'add' => 'Can Add Poll',
        'edit' => 'Can Edit Own Poll',
        'moderate_edit' => 'Can Edit All Polls',
        'delete' => 'Can Delete Own Poll',
        'moderate_delete' => 'Can Delete All Polls',
        'manage_access' => 'Can Manage Access',
        'view_results' => 'Can View Poll Results',
    ],

    // === Permission Errors ===
    'permission_errors' => [
        'view_denied' => 'You do not have permission to view poll management',
        'add_denied' => 'You do not have permission to add poll',
        'edit_denied' => 'You do not have permission to edit poll',
        'delete_denied' => 'You do not have permission to delete poll',
        'results_denied' => 'You do not have permission to view poll results',
        'access_denied' => 'You do not have permission to edit/delete this poll (does not belong to you)',
    ],

    // === Confirmation Messages ===
    'confirmations' => [
        'delete_single' => 'Are you sure you want to delete this poll?',
        'delete_multiple' => 'Are you sure you want to delete :count selected polls?',
        'set_default' => 'Are you sure you want to set this poll as the default?',
        'publish' => 'Are you sure you want to publish this poll?',
        'unpublish' => 'Are you sure you want to unpublish this poll?',
    ],

    // === Helper Text ===
    'help' => [
        'poll_question' => 'Enter a clear and concise question for your poll',
        'access_control' => 'Specify which user roles can see and vote in this poll',
        'default_poll' => 'Only one poll can be set as default at a time',
        'multiple_choice' => 'Allow users to select more than one option',
        'poll_options' => 'Add voting options that users can choose from',
    ],

    // === Placeholders ===
    'placeholders' => [
        'poll_question' => 'Enter your poll question here...',
        'poll_option' => 'Enter poll option...',
        'start_date' => 'Select start date...',
        'end_date' => 'Select end date (optional)...',
    ],

    // === Empty States ===
    'empty' => [
        'no_polls' => 'No polls have been created yet',
        'no_results' => 'No polls found matching your search criteria',
        'add_first' => 'Click the button to create the first poll',
        'no_votes' => 'No votes have been cast yet',
    ],

    // === Loading States ===
    'loading' => [
        'saving' => 'Saving...',
        'loading' => 'Loading...',
        'processing' => 'Processing votes...',
        'counting' => 'Counting results...',
    ],

    // === Success Messages ===
    'success' => [
        'operation_completed' => 'Operation completed successfully!',
        'changes_saved' => 'Changes saved!',
        'poll_published' => 'Poll published successfully!',
        'poll_unpublished' => 'Poll unpublished successfully!',
    ],

    // === Error Messages ===
    'errors' => [
        'general' => 'An error occurred. Please try again.',
        'network' => 'Network error. Please check your internet connection.',
        'server' => 'Server error. Please try again later.',
        'no_default_poll' => 'No default poll has been set',
        'multiple_defaults' => 'Multiple default polls detected',
    ],

    // === Results Display ===
    'results' => [
        'total_votes' => 'Total Votes',
        'vote_count' => ':count votes',
        'vote_saved' => 'Your vote has been saved!',
        'percentage' => ':percentage%',
        'no_votes_yet' => 'No votes yet',
        'view_detailed_results' => 'View Detailed Results',
        'export_to_csv' => 'Export to CSV',
        'reset_results' => 'Reset Results',
    ],

    // === Poll Options Management ===
    'options_management' => [
        'add_option' => 'Add Option',
        'remove_option' => 'Remove Option',
        'option_text' => 'Option Text',
        'vote_count' => 'Vote Count',
        'reorder_options' => 'Reorder Options',
    ],

];