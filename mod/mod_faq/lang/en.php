<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_faq'] = [
    'title' => 'Frequently Asked Questions (FAQ)',
    'module_title' => 'Frequently Asked Questions (FAQ)',
    'add_question' => 'Add Question',
    'edit_question' => 'Edit Question',
    'delete_question' => 'Delete Question',
    'update' => 'Update',
    'save_all' => 'Save Changes',
    'question' => 'Question',
    'answer' => 'Answer',
    'sort_order' => 'Sort Order',
    'status' => 'Status',
    'published' => 'Published',
    'draft' => 'Draft',
    'empty_state' => 'No questions added yet. Tap + to add the first question.',
    'character_limit' => 'Character Limit',
    'limit_reached' => 'Maximum limit of 25 questions reached.',
    'question_placeholder' => 'Enter the question here...',
    'answer_placeholder' => 'Enter the answer here...',
    'drag_to_reorder' => 'Drag to reorder',
    'confirm_delete' => 'Are you sure you want to delete this question?',
    'admin' => [
        'faq_management' => 'FAQ Management',
        'manage_faq' => 'Manage FAQs',
    ],
    'errors' => [
        'missing_module_name' => 'Module information is missing for the FAQ operation.',
        'missing_record_id' => 'Record information is missing for the FAQ operation.',
        'invalid_module' => 'No FAQ module is defined for this record.',
        'module_faq_permission_missing' => 'No FAQ permission is defined for this module.',
        'invalid_record' => 'The related record could not be found.',
        'invalid_faq' => 'The related FAQ record could not be found.',
        'invalid_record_title_field' => 'The mod_value field for this module is invalid. Correct the display field manually.',
    ],
    'role' => [
        'name' => 'FAQ Roles',
        'add' => 'Can add FAQs.',
        'edit' => 'Can edit FAQs.',
        'delete' => 'Can delete FAQs.',
        'list' => 'Can view FAQ management.',
        'moderate_edit' => 'Can manage FAQs belonging to others.',
        'moderate_delete' => 'Can delete FAQs belonging to others.',
    ],
    'role_errors' => [
        'faq' => 'You do not have permission to manage FAQs.',
        'add' => 'You do not have permission to add FAQs.',
        'edit' => 'You do not have permission to edit FAQs.',
        'delete' => 'You do not have permission to delete FAQs.',
        'list' => 'You do not have permission to view FAQ management.',
        'moderate_edit' => 'You do not have permission to edit FAQs belonging to someone else.',
        'moderate_delete' => 'You do not have permission to delete FAQs belonging to someone else.',
    ],
    'message' => [
        'success' => [
            'add' => 'Question added successfully.',
            'edit' => 'Question updated successfully.',
            'delete' => 'Question deleted successfully.',
            'reorder' => 'Sort order updated successfully.',
            'batch' => 'All questions saved successfully.',
            'status' => 'Status changed successfully.',
        ],
        'error' => [
            'add' => 'Question could not be added.',
            'edit' => 'Question could not be updated.',
            'delete' => 'Question could not be deleted.',
            'reorder' => 'Sort order could not be updated.',
            'batch' => 'Batch save failed.',
            'status' => 'Status change failed.',
            'max_limit' => 'You cannot exceed the limit of 25 questions.',
            'invalid_record' => 'Invalid entity or record ID.',
            'permission_denied' => 'You do not have permission to manage FAQs.',
            'duplicate_question' => 'A question with the same text already exists.',
        ],
    ],
    'validation' => [
        'question_required' => 'Question is required.',
        'question_max' => 'Question cannot exceed 255 characters.',
        'question_duplicate' => 'This question has already been added.',
        'answer_required' => 'Answer is required.',
        'answer_max' => 'Answer cannot exceed 5000 characters.',
    ]
];
