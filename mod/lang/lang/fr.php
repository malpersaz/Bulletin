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
        'lang_added' => "<b>%s</b> - ajouté à la base de données !",
        'lang_exists' => "<b>%s</b> - existe déjà dans la base de données !",
    ],
    'system' => [
        'starting' => "Démarrage de l'installation...",
        'installing' => "Installation : %s",
    ],
    'installer_mobile' => [
        'hero' => ['badge' => 'Installateur Rojname', 'title' => 'Interface d’installation pensée pour téléphone', 'body' => 'Cet installateur fonctionne désormais dans un flux mobile avec persistance des étapes, vérifications précoces de l’environnement et transition contrôlée vers l’installateur existant.'],
        'steps' => ['welcome' => 'Accueil', 'language' => 'Langue', 'checks' => 'Contrôles', 'setup' => 'Réglages', 'review' => 'Revue', 'result' => 'Résultat'],
        'welcome' => ['title' => 'Flux d’installation ciblé', 'body' => 'L’interface garde la configuration dans un parcours mobile, mémorise la progression et bloque l’installation lorsque les prérequis manquent.', 'project_label' => 'Projet', 'project_detail' => 'La marque Rojname reste la cible par défaut de cet installateur spécifique au dépôt.', 'language_scope_label' => 'Portée linguistique', 'language_scope_detail' => 'Les valeurs par défaut de l’installateur conservent les exclusions linguistiques spécifiques au projet.'],
        'language' => ['title' => 'Langue de l’installateur', 'body' => 'Sélectionnez la langue de la session d’installation avant d’avancer. Cet état est conservé dans la session courante.'],
        'environment' => ['title' => 'Contrôle de l’environnement', 'body' => 'La progression reste bloquée tant que tous les contrôles requis ne sont pas validés.', 'ready' => 'Prêt', 'blocked' => 'Bloqué'],
        'setup' => ['title' => 'Configuration du projet', 'body' => 'Capturez maintenant l’état minimal d’installation. La phase d’exécution actuelle délègue encore l’installation des modules au pipeline existant.', 'project_name_label' => 'Nom du projet', 'project_default_language_label' => 'Langue par défaut du projet', 'sample_content_label' => 'Contenu d’exemple', 'sample_content_no' => 'Non, garder une installation propre.', 'sample_content_yes' => 'Oui, enregistrer l’intention de contenu d’exemple dans l’état de l’installateur.', 'sample_content_notice' => 'Le choix de contenu d’exemple est maintenant conservé. Le câblage d’exécution réel viendra dans une phase suivante.'],
        'review' => ['title' => 'Revoir et installer', 'body' => 'L’interface mobile résume l’état de l’installateur puis lance le pipeline de modules existant.', 'project_name_label' => 'Nom du projet', 'installer_language_label' => 'Langue de l’installateur', 'project_default_language_label' => 'Langue par défaut du projet', 'module_summary_label' => 'Résumé des modules', 'module_summary_detail' => '%d requis, %d optionnels, %d spécifiques au projet, %d compatibles avec le contenu d’exemple.', 'sample_content_label' => 'Décision de contenu d’exemple', 'sample_content_enabled' => 'Enregistré comme activé pour un câblage ultérieur.', 'sample_content_disabled' => 'Enregistré comme désactivé.'],
        'result' => ['success_title' => 'Installation terminée', 'failure_title' => 'Installation arrêtée', 'success_body' => 'L’installateur de modules existant s’est terminé dans l’interface mobile.', 'failure_body' => 'L’installation ne s’est pas terminée correctement. Consultez la sortie avant de réessayer.', 'installed_at' => 'Installé le', 'no_output' => 'Aucune sortie de l’installateur n’a été capturée.'],
        'actions' => ['start_setup' => 'Démarrer', 'continue' => 'Continuer', 'back' => 'Retour', 'continue_to_setup' => 'Continuer vers la configuration', 'continue_to_review' => 'Continuer vers la revue', 'run_installer' => 'Lancer l’installateur', 'reset_state' => 'Réinitialiser l’état'],
        'errors' => ['resolve_environment' => 'Corrigez les contrôles d’environnement bloquants avant de continuer.', 'blocked_install' => 'L’installation ne peut pas démarrer tant que les contrôles d’environnement sont bloquants.'],
        'checks' => [
            'php_version' => ['label' => 'PHP 8.0+', 'detail' => 'Runtime actuel : %s'],
            'extensions' => ['label' => 'Extensions requises', 'ok' => 'Toutes les extensions requises sont disponibles.', 'missing' => 'Manquantes : %s'],
            'directories' => ['label' => 'Répertoires inscriptibles', 'ok' => 'Les répertoires de stockage de l’installateur sont inscriptibles.', 'blocked' => 'Bloqués : %s'],
            'database' => ['label' => 'Configuration de la base de données', 'missing' => 'Aucune configuration active de base de données trouvée.', 'detail' => 'Adaptateur actif : %s (%s)'],
        ],
    ],
];
