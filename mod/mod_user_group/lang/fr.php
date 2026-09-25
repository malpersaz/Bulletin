<?php
$lang['mod_user_group']=[
	'add'=>'Ajouter un groupe d\'utilisateurs',
	'management_title'=>'Gestion des Groupes d\'Utilisateurs',
	'user_groups'=>'Groupes d\'Utilisateurs',
	'select_optional'=>'---Veuillez sélectionner--- ',
	'confirm_delete'=>'Êtes-vous sûr de vouloir supprimer ce Groupe d\'Utilisateurs?<br/> Si vous supprimez ce groupe, tous ses sous-groupes seront également supprimés!',
	'group_deleted'=>'Groupe d\'Utilisateurs Supprimé!',
	'order_changed'=>'Ordre des Groupes d\'Utilisateurs Modifié!',
	'delete_group'=>'Supprimer le Groupe d\'Utilisateurs',
	'edit_group'=>'Modifier le Groupe d\'Utilisateurs',
	'edit'=>'Modifier',
	'yes'=>'Oui',
	'cancel'=>'Annuler',
	'group_list'=>'Liste des Groupes d\'Utilisateurs',
	'double_click_instruction'=>'Double-cliquez sur le nom du groupe que vous voulez supprimer ou modifier',
	'drag_drop_instruction'=>'Glissez-déposez les groupes que vous voulez réorganiser',
	'add_new_group'=>'Ajouter un Nouveau Groupe d\'Utilisateurs'
];

$lang['mod_user_group']['admin']=[
	'add_area'=>'Zone d\'Ajout de Groupe d\'Utilisateurs',
	'edit_area'=>'Zone de Modification de Groupe d\'Utilisateurs',
	'management'=>'Gestion des Groupes d\'Utilisateurs',
	'add_form'=>'Formulaire d\'Ajout de Groupe d\'Utilisateurs',
	'edit_form'=>'Formulaire de Modification de Groupe d\'Utilisateurs',
	'group_name'=>'Nom du Groupe d\'Utilisateurs',
	'add'=>'Ajouter',
	'edit'=>'Modifier',
	'group_list'=>'Liste des Groupes d\'Utilisateurs',
	'list'=>'Liste des Groupes d\'Utilisateurs',
	'group_added'=>'Groupe d\'Utilisateurs Ajouté!',
	'group_edited'=>'Groupe d\'Utilisateurs Mis à Jour',
	'sub_group'=>'Sous-groupe d\'Utilisateurs'
];

$lang['mod_user_group']['hata']=[
	'name_required'=>'Veuillez spécifier un nom de groupe d\'utilisateurs',
	'link_required'=>'Veuillez spécifier un lien de groupe d\'utilisateurs.',
	'link_unique'=>'Veuillez spécifier un autre lien de groupe d\'utilisateurs <br/>Car le lien de groupe d\'utilisateurs spécifié est déjà enregistré'
];

// Modern language keys (not numbered)
$lang['mod_user_group']['buttons'] = [
    'edit' => 'Modifier',
    'cancel' => 'Annuler',
    'add' => 'Ajouter',
    'delete' => 'Supprimer',
    'save' => 'Enregistrer'
];

$lang['mod_user_group']['role']=[
	'name'=>'Rôles des Groupes d\'Utilisateurs',
	'add' =>'Peut Ajouter un Groupe d\'Utilisateurs',
	'edit' =>'Peut Modifier le Groupe d\'Utilisateurs Ajouté.',
	'delete'=>'Peut Supprimer le Groupe d\'Utilisateurs Ajouté.',
	'list' =>'Peut Voir la Gestion des Groupes d\'Utilisateurs.',
	'moderate'=>'Peut Gérer les Groupes d\'Utilisateurs Ajoutés par d\'Autres.',
	'move'=>'Peut Changer l\'Ordre des Groupes d\'Utilisateurs'
];

// === AJAX Response Messages ===
$lang['mod_user_group']['ajax'] = [
    'success' => 'Succès',
    'error' => 'Erreur',
];

$lang['mod_user_group']['role_errors']=[
	'management_title'=>'Vous n\'avez pas la permission de modifier les groupes d\'utilisateurs appartenant aux autres.',
	'user_groups'=>'Vous n\'avez pas la permission de supprimer les groupes d\'utilisateurs appartenant aux autres.',
	'move'=>'Vous n\'avez pas la permission de changer l\'ordre des groupes d\'utilisateurs.',
	'add' =>'Vous n\'avez pas la permission d\'ajouter des groupes d\'utilisateurs.',
	'delete'=>'Vous n\'avez pas la permission de supprimer des groupes d\'utilisateurs.',
	'edit' =>'Vous n\'avez pas la permission de modifier des groupes d\'utilisateurs.',
	'list' =>'Vous n\'avez pas la permission de voir la gestion des groupes d\'utilisateurs.'
];
?>
