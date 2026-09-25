<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_faq'] = [
    'title' => 'Foire aux questions (FAQ)',
    'module_title' => 'Foire aux questions (FAQ)',
    'add_question' => 'Ajouter une question',
    'edit_question' => 'Modifier la question',
    'delete_question' => 'Supprimer la question',
    'update' => 'Mettre à jour',
    'save_all' => 'Enregistrer les modifications',
    'question' => 'Question',
    'answer' => 'Réponse',
    'sort_order' => 'Ordre',
    'status' => 'Statut',
    'published' => 'Publié',
    'draft' => 'Brouillon',
    'empty_state' => 'Aucune question ajoutée pour le moment. Appuyez sur + pour ajouter la première question.',
    'character_limit' => 'Limite de caractères',
    'limit_reached' => 'Limite maximale de 25 questions atteinte.',
    'question_placeholder' => 'Saisissez la question ici...',
    'answer_placeholder' => 'Saisissez la réponse ici...',
    'drag_to_reorder' => 'Glisser pour réorganiser',
    'confirm_delete' => 'Êtes-vous sûr de vouloir supprimer cette question ?',
    'admin' => [
        'faq_management' => 'Gestion de la FAQ',
        'manage_faq' => 'Foire aux questions',
    ],
    'errors' => [
        'missing_module_name' => 'Les informations du module sont manquantes pour l\'opération FAQ.',
        'missing_record_id' => 'Les informations de l\'enregistrement sont manquantes pour l\'opération FAQ.',
        'invalid_module' => 'Aucun module FAQ défini pour cet enregistrement.',
        'module_faq_permission_missing' => 'Aucune permission FAQ définie pour ce module.',
        'invalid_record' => 'Enregistrement associé introuvable.',
        'invalid_faq' => 'Enregistrement FAQ associé introuvable.',
        'invalid_record_title_field' => 'Le champ mod_value est invalide pour ce module.',
    ],
    'role' => [
        'name' => 'Rôles FAQ',
        'add' => 'Peut ajouter des questions.',
        'edit' => 'Peut modifier des questions.',
        'delete' => 'Peut supprimer des questions.',
        'list' => 'Peut afficher la gestion de la FAQ.',
        'moderate_edit' => 'Peut modifier les questions des autres utilisateurs.',
        'moderate_delete' => 'Peut supprimer les questions des autres utilisateurs.',
    ],
    'role_errors' => [
        'faq' => 'Vous n\'avez pas la permission de gérer la FAQ.',
        'add' => 'Vous n\'avez pas la permission d\'ajouter des questions.',
        'edit' => 'Vous n\'avez pas la permission de modifier des questions.',
        'delete' => 'Vous n\'avez pas la permission de supprimer des questions.',
        'list' => 'Vous n\'avez pas la permission d\'afficher la gestion de la FAQ.',
        'moderate_edit' => 'Vous n\'avez pas la permission de modifier les questions d\'autres personnes.',
        'moderate_delete' => 'Vous n\'avez pas la permission de supprimer les questions d\'autres personnes.',
    ],
    'message' => [
        'success' => [
            'add' => 'Question ajoutée avec succès.',
            'edit' => 'Question mise à jour avec succès.',
            'delete' => 'Question supprimée avec succès.',
            'reorder' => 'Ordre mis à jour avec succès.',
            'batch' => 'Toutes les questions ont été enregistrées avec succès.',
            'status' => 'Statut modifié avec succès.',
        ],
        'error' => [
            'add' => 'Impossible d\'ajouter la question.',
            'edit' => 'Impossible de mettre à jour la question.',
            'delete' => 'Impossible de supprimer la question.',
            'reorder' => 'Impossible de mettre à jour l\'ordre.',
            'batch' => 'Échec de l\'enregistrement groupé.',
            'status' => 'Échec de la modification du statut.',
            'max_limit' => 'Vous ne pouvez pas dépasser la limite de 25 questions.',
            'invalid_record' => 'Enregistrement ou module non valide.',
            'permission_denied' => 'Vous n\'avez pas la permission de gérer la FAQ.',
            'duplicate_question' => 'Une question avec le même texte existe déjà.',
        ],
    ],
    'validation' => [
        'question_required' => 'Le champ question est obligatoire.',
        'question_max' => 'La question ne peut pas dépasser 255 caractères.',
        'question_duplicate' => 'Cette question a déjà été ajoutée.',
        'answer_required' => 'Le champ réponse est obligatoire.',
        'answer_max' => 'La réponse ne peut pas dépasser 5000 caractères.',
    ]
];
