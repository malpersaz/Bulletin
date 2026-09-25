<?php
/**
 * Amed Framework
 * MCP Module French Language Pack
 * mod/mod_mcp/lang/fr.php
 */

$lang['mod_mcp'] = [
    'module_title' => 'Clés API MCP',
    'page_intro' => 'Créez et gérez des clés API pour le point de terminaison Model Context Protocol. Les clients s\'authentifient avec ces clés pour gérer les contenus et les catégories de contenus à distance.',
    'endpoint_label' => 'Point de terminaison MCP',
    'no_keys' => 'Aucune clé API n\'a encore été créée.',
    'key_name' => 'Nom de la clé',
    'key_prefix_label' => 'Préfixe de la clé',
    'group_label' => 'Groupe d\'autorisation',
    'status_label' => 'Statut',
    'created_at_label' => 'Date de création',
    'last_used_label' => 'Dernière utilisation',
    'creator_label' => 'Créée par',
    'active' => 'Active',
    'revoked' => 'Révoquée',
    'never_used' => 'Jamais utilisée',
    'create_key_title' => 'Créer une nouvelle clé API',
    'name_placeholder' => 'ex. Client d\'automatisation de contenu',
    'submit_create' => 'Générer la clé',
    'key_created_title' => 'Clé API créée',
    'key_created_warning' => 'Copiez cette clé maintenant. Elle ne sera plus jamais affichée.',
    'copy_key' => 'Copier la clé',
    'key_copied' => 'Clé API copiée dans le presse-papiers.',
    'reveal_close' => 'Terminé',
    'delete_confirm' => 'Êtes-vous sûr de vouloir supprimer définitivement cette clé API ? Les clients qui l\'utilisent perdront immédiatement l\'accès.',
    'toggle_activate' => 'Activer la clé',
    'toggle_revoke' => 'Révoquer la clé',
    'delete_key' => 'Supprimer la clé',
    'oauth_section_title' => 'Clients OAuth 2.1',
    'oauth_hint' => 'Clients se connectant via les points de terminaison /oauth (ChatGPT utilise sa propre identité de client signée et n\'apparaît pas ici). Désactiver un client révoque immédiatement ses tokens ; la suppression retire aussi ses codes et tokens.',
    'oauth_no_clients' => 'Aucun client OAuth enregistré pour le moment. Les clients s\'inscrivent eux-mêmes via /oauth/register lors de la connexion.',
    'oauth_tokens_active' => 'Tokens actifs',
    'oauth_deactivate' => 'Désactiver le client',
    'oauth_activate' => 'Activer le client',
    'oauth_delete' => 'Supprimer le client',
    'oauth_delete_confirm' => 'Voulez-vous vraiment supprimer définitivement ce client OAuth ? Ses codes d\'autorisation et tokens seront immédiatement supprimés.',
    'errors' => [
        'invalid_name' => 'Veuillez saisir un nom de clé entre 1 et 100 caractères.',
        'invalid_group' => 'Veuillez sélectionner un groupe d\'autorisation valide.',
        'record' => 'Enregistrement de clé API introuvable.'
    ],
    'role_errors' => [
        'list' => 'Vous n\'avez pas la permission de consulter les clés API MCP.',
        'add' => 'Vous n\'avez pas la permission de créer des clés API MCP.',
        'edit' => 'Vous n\'avez pas la permission de modifier des clés API MCP.',
        'delete' => 'Vous n\'avez pas la permission de supprimer des clés API MCP.'
    ],
    'message' => [
        'success' => [
            'add' => 'Clé API créée avec succès.',
            'edit' => 'Clé API mise à jour avec succès.',
            'delete' => 'Clé API supprimée avec succès.'
        ],
        'error' => [
            'add' => 'Échec de la création de la clé API.',
            'edit' => 'Échec de la mise à jour de la clé API.',
            'delete' => 'Échec de la suppression de la clé API.'
        ]
    ],
    'role' => [
        'name' => 'Rôles des clés MCP',
        'list' => 'Peut consulter les clés MCP',
        'add' => 'Peut créer des clés MCP',
        'edit' => 'Peut modifier des clés MCP',
        'delete' => 'Peut supprimer des clés MCP'
    ]
];
