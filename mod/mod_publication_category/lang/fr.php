<?php
/**
 * Publication Category Module - French Language File
 *
 * @package AKM ARTIST CMS
 * @module  mod_publication_category
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Category Module Language Translations
 */
$lang['mod_publication_category'] = [

    // === Basic Navigation ===
    'list' => 'Liste des catégories',
    'add' => 'Ajouter une catégorie',
    'edit' => 'Modifier la catégorie',
    'information' => 'Informations',
    'success' => 'Succès',
    'error' => 'Une erreur est survenue',
    'remove_record' => 'Êtes-vous sûr de vouloir supprimer cet enregistrement ?',
    'choose_wish' => 'Choisissez selon vos souhaits',
    'remove_image' => 'Êtes-vous sûr de vouloir supprimer cette image ?',

    // === Message Structure ===
    'message' => [
        'success' => [
            'add' => 'Catégorie ajoutée avec succès !',
            'edit' => 'Catégorie mise à jour avec succès !',
        ],
        'error' => [
            'add' => 'Échec de l\'ajout de la catégorie !',
            'edit' => 'Échec de la mise à jour de la catégorie !',
        ],
        'information' => [
            'moderate' => 'Double-cliquez sur la catégorie que vous souhaitez modifier',
            'move' => 'Glissez-déposez la catégorie pour modifier son ordre',
        ],
    ],

    'errors' => [
        'record' => 'L\'enregistrement spécifié est introuvable !',
    ],

    'button' => [
        'add' => 'Ajouter',
        'edit' => 'Modifier',
        'refresh' => 'Actualiser',
        'delete' => 'Supprimer',
        'cancel' => 'Annuler',
        'remove' => 'Retirer',
        'yes' => 'Oui',
        'no' => 'Non',
        'save' => 'Enregistrer',
        'gallery' => 'Galerie',
        'file' => 'Fichier',
    ],

    'grid' => [
        'showing_results' => 'Affichage de {from} à {to} sur {total} résultats',
        'page' => 'Page',
        'page_count' => 'Nombre de pages',
        'loading' => 'Chargement des données, veuillez patienter...',
        'no_data' => 'Aucune donnée trouvée',
        'connection_error' => 'Erreur de connexion',
        'search' => 'Rechercher',
        'select_at_least_one' => 'Veuillez sélectionner au moins une ligne',
        'select_only_one' => 'Veuillez sélectionner une seule ligne',
    ],

    'validator' => [
        'required' => 'Veuillez ne pas laisser le champ %s vide',
        'length' => 'Le champ %s doit contenir entre %s et %s caractères',
        'email' => 'Veuillez saisir une adresse e-mail valide dans le champ %s',
        'unique' => 'La valeur %s saisie est déjà enregistrée dans le système !',
        'inarray' => 'Le champ %s accepte uniquement les valeurs \' %s \'',
    ],

    'role' => [
        'name' => 'Rôles de gestion des catégories de publications',
        'add' => 'Peut ajouter une catégorie',
        'edit' => 'Peut modifier une catégorie',
        'delete' => 'Peut supprimer une catégorie',
        'list' => 'Peut afficher la gestion des catégories',
        'moderate_edit' => 'Peut modifier les catégories d\'autres utilisateurs',
        'moderate_delete' => 'Peut supprimer les catégories d\'autres utilisateurs',
    ],

    'role_errors' => [
        'moderate_edit' => 'Vous ne pouvez pas modifier les catégories d\'autres utilisateurs',
        'moderate_delete' => 'Vous ne pouvez pas supprimer les catégories d\'autres utilisateurs',
        'add' => 'Vous n\'avez pas la permission d\'ajouter des catégories',
        'edit' => 'Vous n\'avez pas la permission de modifier des catégories',
        'list' => 'Vous n\'avez pas la permission d\'afficher la gestion des catégories',
        'delete' => 'Vous n\'avez pas la permission de supprimer des catégories',
    ],

    'actions' => [
        'add' => 'Ajouter une catégorie',
        'edit' => 'Modifier la catégorie',
        'list' => 'Liste des catégories',
        'delete' => 'Supprimer la catégorie',
        'cancel' => 'Annuler',
        'save' => 'Enregistrer',
        'update' => 'Mettre à jour',
        'remove' => 'Retirer',
    ],

    'messages' => [
        'success' => [
            'added' => 'Catégorie ajoutée avec succès !',
            'updated' => 'Catégorie mise à jour avec succès !',
            'deleted' => 'Catégorie supprimée avec succès !',
            'reordered' => 'Ordre des catégories mis à jour !',
            'image_removed' => 'Image retirée avec succès !',
        ],
        'error' => [
            'not_added' => 'Échec de l\'ajout de la catégorie !',
            'not_updated' => 'Échec de la mise à jour de la catégorie !',
            'not_deleted' => 'Échec de la suppression de la catégorie !',
            'not_found' => 'La catégorie spécifiée est introuvable !',
            'image_not_removed' => 'Échec du retrait de l\'image !',
            'validation' => 'Veuillez remplir correctement les champs requis !',
        ],
        'info' => [
            'edit_double_click' => 'Double-cliquez sur la catégorie à modifier',
            'drag_reorder' => 'Glissez et déposez la catégorie pour réordonner',
        ],
    ],

    'options' => [
        'yes' => 'Oui',
        'no' => 'Non',
    ],

    'form' => [
        'basic_info' => 'Informations de base',
        'seo_settings' => 'Paramètres SEO',
        'display_settings' => 'Paramètres d\'affichage',
        'advanced_settings' => 'Paramètres avancés',
        'id' => 'ID',
        'name' => 'Nom de la catégorie',
        'parent_category' => 'Catégorie parente',
        'order' => 'Ordre',
        'language' => 'Langue d\'affichage',
        'is_root' => 'Catégorie racine (masquée à l\'affichage)',
        'seo_url' => 'URL SEO',
        'meta_title' => 'Titre Meta (pour moteurs de recherche)',
        'meta_keywords' => 'Mots-clés Meta (pour moteurs de recherche)',
        'meta_description' => 'Description Meta (pour moteurs de recherche)',
        'add_to_menu' => 'Ajouter la catégorie au menu',
        'image' => 'Image de la catégorie',
        'description' => 'Description de la catégorie',
        'access_control' => 'Contrôle d\'accès',
    ],

    'columns' => [
        'id' => 'ID',
        'language' => 'Langue d\'affichage',
        'name' => 'Nom de la catégorie',
        'parent_category' => 'Catégorie parente',
        'order' => 'Ordre',
        'root_category' => 'Catégorie racine',
        'seo_url' => 'URL SEO',
        'meta_title' => 'Titre Meta',
        'meta_keywords' => 'Mots-clés Meta',
        'meta_description' => 'Description Meta',
        'add_to_menu' => 'Ajouter au menu',
        'image' => 'Image',
        'status' => 'Statut',
        'actions' => 'Actions',
    ],

    'buttons' => [
        'add' => 'Ajouter une nouvelle catégorie',
        'edit' => 'Modifier la sélection',
        'delete' => 'Supprimer la sélection',
        'refresh' => 'Actualiser',
        'cancel' => 'Annuler',
        'remove' => 'Retirer',
        'yes' => 'Oui',
        'no' => 'Non',
    ],

    'validation' => [
        'required' => 'Veuillez ne pas laisser le champ %s vide',
        'length' => 'Le champ %s doit contenir entre %s et %s caractères',
        'email' => 'Veuillez saisir une adresse e-mail valide',
        'unique' => 'La valeur %s est déjà enregistrée',
        'inarray' => 'Le champ %s accepte uniquement les valeurs \' %s \'',
        'url' => 'Veuillez saisir une URL valide',
        'slug' => 'Veuillez saisir un slug URL valide',
    ],

    'permissions' => [
        'title' => 'Permissions de gestion des catégories',
        'add' => 'Peut ajouter une catégorie',
        'edit' => 'Peut modifier une catégorie',
        'delete' => 'Peut supprimer une catégorie',
        'list' => 'Peut afficher la gestion des catégories',
        'edit_others' => 'Peut modifier les catégories d\'autres utilisateurs',
        'delete_others' => 'Peut supprimer les catégories d\'autres utilisateurs',
    ],

    'permission_errors' => [
        'edit_others' => 'Vous ne pouvez pas modifier les catégories d\'autres utilisateurs',
        'delete_others' => 'Vous ne pouvez pas supprimer les catégories d\'autres utilisateurs',
        'add' => 'Vous n\'avez pas la permission d\'ajouter des catégories',
        'edit' => 'Vous n\'avez pas la permission de modifier des catégories',
        'list' => 'Vous n\'avez pas la permission d\'afficher la gestion des catégories',
        'delete' => 'Vous n\'avez pas la permission de supprimer des catégories',
    ],

    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Langue d\'affichage',
            'name' => 'Nom de la catégorie',
            'parent_id' => 'Catégorie parente',
            'order' => 'Ordre',
            'root' => 'Catégorie racine (masquée à l\'affichage)',
            'seo' => 'URL de la catégorie',
            'title' => 'Titre (pour moteurs de recherche)',
            'keywords' => 'Mots-clés (pour moteurs de recherche)',
            'description' => 'Description (pour moteurs de recherche)',
            'menu' => 'Envoyer la catégorie au menu',
            'image' => 'Image',
            'acl_id' => 'Contrôle d\'accès',
        ],
        'select_value' => [
            'root_e' => 'Oui',
            'root_h' => 'Non',
        ],
    ],

    'confirmations' => [
        'remove_image' => 'Êtes-vous sûr de vouloir supprimer cette image ?',
        'remove_record' => 'Êtes-vous sûr de vouloir supprimer cet enregistrement ?',
        'delete_single' => 'Êtes-vous sûr de vouloir supprimer cette catégorie ?',
        'delete_multiple' => 'Êtes-vous sûr de vouloir supprimer les :count catégories sélectionnées ?',
        'make_root' => 'Voulez-vous définir cette catégorie comme racine ?',
        'remove_root' => 'Voulez-vous retirer le statut de catégorie racine ?',
    ],

    'success_messages' => [
        'operation_completed' => 'Opération terminée avec succès !',
        'changes_saved' => 'Modifications enregistrées !',
        'category_published' => 'Catégorie publiée avec succès !',
        'category_unpublished' => 'Publication de la catégorie annulée !',
    ],

    'error_messages' => [
        'general' => 'Une erreur est survenue. Veuillez réessayer.',
        'network' => 'Erreur réseau. Veuillez vérifier votre connexion Internet.',
        'server' => 'Erreur serveur. Veuillez réessayer plus tard.',
        'fatal' => 'Une erreur fatale est survenue !',
        'has_subcategories' => 'Impossible de supprimer une catégorie contenant des sous-catégories',
        'has_content' => 'Impossible de supprimer une catégorie contenant du contenu',
    ],

    'help' => [
        'category_name' => 'Saisissez un nom clair pour cette catégorie',
        'parent_category' => 'Sélectionnez la catégorie parente',
        'order' => 'Détermine l\'ordre d\'affichage',
        'root_category' => 'Les catégories racines sont organisationnelles et non affichées publiquement',
        'seo_url' => 'Version lisible du nom de catégorie pour l\'URL',
        'meta_title' => 'Titre affiché dans les résultats de recherche',
        'meta_description' => 'Description affichée dans les résultats de recherche',
        'add_to_menu' => 'Ajouter automatiquement cette catégorie au menu de navigation',
        'access_control' => 'Spécifier les rôles autorisés à accéder',
    ],

    'placeholders' => [
        'category_name' => 'Entrez le nom de la catégorie...',
        'seo_url' => 'slug-url-categorie',
        'meta_title' => 'Entrez le titre meta...',
        'meta_description' => 'Entrez la description meta...',
        'meta_keywords' => 'motcle1, motcle2',
        'category_description' => 'Entrez la description (optionnel)...',
    ],

    'empty' => [
        'no_categories' => 'Aucune catégorie n\'a encore été créée',
        'no_results' => 'Aucune catégorie ne correspond à votre recherche',
        'add_first' => 'Cliquez sur le bouton pour créer la première catégorie',
        'no_subcategories' => 'Aucune sous-catégorie trouvée',
        'no_content' => 'Aucun contenu dans cette catégorie pour le moment',
    ],

    'loading' => [
        'saving' => 'Enregistrement...',
        'loading' => 'Chargement...',
        'processing' => 'Traitement...',
        'deleting' => 'Suppression...',
    ],

    'tree_view' => [
        'expand_all' => 'Tout développer',
        'collapse_all' => 'Tout réduire',
        'show_hierarchy' => 'Afficher la hiérarchie',
        'show_flat' => 'Vue plate',
        'root_only' => 'Catégories racines uniquement',
        'leaf_only' => 'Sous-catégories uniquement',
    ],

    'seo' => [
        'auto_generate_slug' => 'Générer automatiquement le slug URL',
        'preview_snippet' => 'Aperçu du résultat de recherche',
        'character_count' => 'Nombre de caractères : :count',
        'recommended_length' => 'Recommandé : :min-:max caractères',
        'good' => 'Bon',
        'too_short' => 'Trop court',
        'too_long' => 'Trop long',
    ],

    'bulk_operations' => [
        'bulk_edit' => 'Modification en masse',
        'bulk_delete' => 'Suppression en masse',
        'bulk_move' => 'Déplacer vers la catégorie',
        'bulk_publish' => 'Publier la sélection',
        'bulk_unpublish' => 'Dépublier la sélection',
        'select_all' => 'Tout sélectionner',
        'deselect_all' => 'Tout désélectionner',
    ],

    'ajax' => [
        'success' => 'Succès',
        'error' => 'Erreur',
    ],

    'responses' => [
        'success' => 'Success',
        'error' => 'Error',
        'fatal_error' => 'ERROR',
    ],

];
