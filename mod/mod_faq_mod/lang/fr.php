<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_faq_mod'] = [
    'list' => 'Liste des modules FAQ',
    'add' => 'Ajouter un module FAQ',
    'edit' => 'Modifier le module FAQ',
    'information' => 'Information',
    'success' => 'Succès',
    'error' => 'Une erreur est survenue',
    'remove_record' => 'Êtes-vous sûr de vouloir supprimer cet enregistrement ?',
    'message' => [
        'success' => [
            'add' => 'Module FAQ ajouté avec succès !',
            'edit' => 'Module FAQ mis à jour avec succès !',
            'delete' => 'Module FAQ supprimé avec succès !',
        ],
        'error' => [
            'add' => 'Échec de l\'ajout du module FAQ !',
            'edit' => 'Échec de la mise à jour du module FAQ !',
        ],
    ],
    'errors' => [
        'record' => 'L\'enregistrement spécifié est introuvable !',
    ],
    'button' => [
        'add' => 'Ajouter',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'refresh' => 'Actualiser',
        'cancel' => 'Annuler',
        'remove' => 'Retirer',
        'yes' => 'Oui',
        'no' => 'Non',
    ],
    'validator' => [
        'required' => 'Veuillez ne pas laisser le champ %s vide',
        'length' => '%s doit comporter entre %s et %s caractères',
        'unique' => 'Le %s que vous avez saisi est déjà enregistré dans le système. Veuillez spécifier un autre %s!',
    ],
    'role' => [
        'name' => 'Rôles de gestion des modules FAQ',
        'add' => 'Peut ajouter un module FAQ',
        'edit' => 'Peut modifier un module FAQ',
        'delete' => 'Peut supprimer un module FAQ',
        'list' => 'Peut afficher la gestion des modules FAQ',
        'moderate_edit' => 'Peut modifier les enregistrements de modules FAQ appartenant à d\'autres',
        'moderate_delete' => 'Peut supprimer les enregistrements de modules FAQ appartenant à d\'autres',
    ],
    'role_errors' => [
        'moderate_edit' => 'Vous n\'avez pas la permission de modifier les enregistrements de modules FAQ appartenant à d\'autres',
        'moderate_delete' => 'Vous n\'avez pas la permission de supprimer les enregistrements de modules FAQ appartenant à d\'autres',
        'add' => 'Vous n\'avez pas la permission d\'ajouter des modules FAQ',
        'edit' => 'Vous n\'avez pas la permission de modifier des modules FAQ',
        'list' => 'Vous n\'avez pas la permission d\'afficher la gestion des modules FAQ',
        'delete' => 'Vous n\'avez pas la permission de supprimer des modules FAQ',
    ],
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'Nom du module FAQ',
            'mod_name' => 'Nom du module',
            'mod_key' => 'Clé du module (ID)',
            'mod_value' => 'Valeur du module (Titre)',
        ],
    ],
];
