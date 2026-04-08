<?php
/**
 * Language Module - French Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['lang'] = [
    'installer' => [
        'table_exists' => "La table 'lang' est déjà installée dans la base de données",
        'sql_added' => "Le fichier 'lang.sql' a été ajouté à la base de données",
        'lang_added' => '<b>%s</b> a été ajouté à la base de données',
        'lang_exists' => '<b>%s</b> existe déjà dans la base de données',
    ],
    'system' => [
        'starting' => "L'installation démarre...",
        'installing' => 'installation : %s',
    ],
    'admin' => [
        'title' => 'Gestion des langues',
        'headline' => 'Langues',
        'body' => 'Activez, désactivez et choisissez la langue par défaut du site depuis un panneau adapté au téléphone.',
        'status_active' => 'Actif',
        'status_inactive' => 'Inactif',
        'default_badge' => 'Par défaut',
        'current_badge' => 'Interface actuelle',
        'code_label' => 'Code',
        'activate' => 'Activer',
        'deactivate' => 'Désactiver',
        'set_default' => 'Définir par défaut',
        'empty_state' => "Aucune langue n'a été trouvée.",
        'permission_denied' => "Vous n'avez pas l'autorisation de gérer les langues.",
        'back_panel' => 'Panneau',
        'back_home' => 'Site',
        'back' => 'Retour',
    ],
    'messages' => [
        'activated' => '%s est maintenant active.',
        'deactivated' => '%s est maintenant inactive.',
        'default_changed' => '%s est maintenant la langue par défaut.',
    ],
    'errors' => [
        'language_not_found' => "La langue sélectionnée est introuvable.",
        'unknown_action' => "L'action de langue demandée n'est pas valide.",
        'cannot_deactivate_default' => 'Choisissez une autre langue par défaut avant de désactiver celle-ci.',
        'cannot_deactivate_last_active' => 'Au moins une langue doit rester active.',
        'cannot_deactivate_current_interface' => "Changez d'abord la langue actuelle de l'administration avant de la désactiver.",
        'update_failed' => "L'état de la langue n'a pas pu être mis à jour.",
    ],
];
