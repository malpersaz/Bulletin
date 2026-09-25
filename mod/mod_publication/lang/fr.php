<?php
/**
 * Publication Management Module - French Language File
 *
 * @package AKM ARTIST CMS
 * @module  mod_publication
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Publication Management Module Language Translations
 */
$lang['mod_publication'] = [

    // === Basic Actions ===
    'add' => 'Ajouter une publication',
    'edit' => 'Modifier la publication',
    'list' => 'Liste des publications',
    'information' => 'Information !',
    'success' => 'Opération réussie !',
    'error' => 'Une erreur est survenue !',
    'error_occurred' => 'Une erreur est survenue !',
    'wizard_loading' => 'L\'assistant est en cours de chargement...',
    'fatal_error' => 'Une erreur fatale est survenue !',
    'remove_image' => 'Êtes-vous sûr de vouloir supprimer cette image ?',
    'remove_image_success' => 'Image retirée',
    'choose_wish' => 'Veuillez choisir selon vos souhaits',
    'remove_record' => 'Êtes-vous sûr de vouloir supprimer cet enregistrement ?',
    'delete_confirm' => 'Êtes-vous sûr de vouloir supprimer cette publication ?',
    'quick_unpublish_confirm' => 'Êtes-vous sûr de vouloir dépublier cette publication ?',
    'quick_publish_confirm' => 'Êtes-vous sûr de vouloir publier cette publication ?',
    'add_image' => 'Ajouter une image',
    'upload_change_image' => 'Téléverser / Changer l\'image',
    'local' => 'Local',
    'embed' => 'Intégrer',

    // === Filter Menu ===
    'filter' => [
        'title' => 'Filtrer et trier',
        'close' => 'Fermer',
        'sort_label' => 'Trier',
        'newest' => 'Plus récents',
        'oldest' => 'Plus anciens',
        'title_az' => 'Titre (A-Z)',
        'title_za' => 'Titre (Z-A)',
        'view_count' => 'Nombre de vues',
        'limit_label' => 'Limite d\'affichage',
        'lang_label' => 'Sélection de langue',
        'selected_lang' => 'Langue sélectionnée',
        'date_label' => 'Plage de dates',
        'start_date' => 'Début',
        'end_date' => 'Fin',
        'clear' => 'Effacer',
        'apply' => 'Afficher les résultats',
        'select_lang' => 'Sélectionner la langue',
        'all_langs' => 'Toutes les langues',
        'select_label' => 'Sélectionner',
        'delete_error' => 'Échec de la suppression !',
        'video_success_sim' => 'Vidéo ajoutée avec succès (Simulation)',
        'video_embed_error' => 'Code d\'intégration invalide',
        'video_title_error' => 'Titre invalide',
    ],

    // === Messages ===
    'message' => [
        'success' => [
            'add' => 'Publication ajoutée !',
            'edit' => 'Publication mise à jour !',
        ],
        'error' => [
            'add' => 'Impossible d\'ajouter la publication !',
            'edit' => 'Impossible de mettre à jour la publication !',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'L\'enregistrement spécifié est introuvable !',
    ],

    // === Buttons ===
    'button' => [
        'next' => 'Suivant',
        'prev' => 'Précédent',
        'send' => 'Envoyer',
        'add' => 'Ajouter',
        'edit' => 'Modifier',
        'refresh' => 'Actualiser',
        'delete' => 'Supprimer',
        'gallery' => 'Galerie d\'images',
        'file' => 'Gestionnaire de fichiers',
        'cancel' => 'Annuler',
        'remove' => 'Retirer',
        'yes' => 'Oui',
        'no' => 'Non',
        'save' => 'Enregistrer',
    ],

    // === Admin Grid Columns ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Langue',
            'title' => 'Titre de la publication',
            'category_id' => 'Catégorie',
            'status' => 'Statut',
            'created_at' => 'Date de création',
            'actions' => 'Actions',
        ],
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Veuillez ne pas laisser le champ %s vide.',
        'length' => 'Le champ %s doit contenir entre %s et %s caractères.',
        'unique' => 'La valeur %s existe déjà. Veuillez choisir une autre valeur pour %s.',
        'inarray' => 'Le champ %s ne peut accepter que ces valeurs : %s.',
        'deck_length' => 'Veuillez saisir un texte d\'introduction compris entre 90 et 120 caractères.',
    ],

];
