<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_socialmedia_post_mod'] = [
    'named' => 'Modules de Publications Sociales',
    'list' => 'Liste des Modules de Publications Sociales',
    'add' => 'Ajouter un Module de Publication Sociale',
    'edit' => 'Modifier un Module de Publication Sociale',
    'information' => 'Information',
    'success' => 'Succès',
    'error' => 'Une erreur s\'est produite',
    'remove_record' => 'Voulez-vous vraiment supprimer cet enregistrement ?',
    'message' => [
        'success' => [
            'add' => 'Module ajouté avec succès !',
            'edit' => 'Module mis à jour avec succès !',
            'delete' => 'Module supprimé avec succès !',
        ],
        'error' => [
            'add' => 'Échec de l\'ajout du module !',
            'edit' => 'Échec de la mise à jour du module !',
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
        'length' => '%s doit contenir entre %s et %s caractères',
        'unique' => 'La valeur %s saisie est déjà enregistrée. Veuillez en choisir une autre !',
    ],
    'role' => [
        'name' => 'Droits des Modules de Publications Sociales',
        'add' => 'Peut ajouter un module de publication sociale',
        'edit' => 'Peut modifier un module de publication sociale',
        'delete' => 'Peut supprimer un module de publication sociale',
        'list' => 'Peut consulter les modules de publications sociales',
        'moderate_edit' => 'Peut modifier les enregistrements d\'autres utilisateurs',
        'moderate_delete' => 'Peut supprimer les enregistrements d\'autres utilisateurs',
    ],
    'role_errors' => [
        'moderate_edit' => 'Vous ne pouvez pas modifier les enregistrements d\'autres utilisateurs',
        'moderate_delete' => 'Vous ne pouvez pas supprimer les enregistrements d\'autres utilisateurs',
        'add' => 'Vous n\'avez pas l\'autorisation d\'ajouter des modules',
        'edit' => 'Vous n\'avez pas l\'autorisation de modifier des modules',
        'list' => 'Vous n\'avez pas l\'autorisation de consulter les modules',
        'delete' => 'Vous n\'avez pas l\'autorisation de supprimer des modules',
    ],
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'Nom du module',
            'mod_name' => 'Identifiant du module',
            'mod_key' => 'Clé du module (ID)',
            'mod_value' => 'Valeur du module (Titre)',
        ],
    ],
];
