<?php
/**
 * Amed Framework
 * MCP Module English Language Pack
 * mod/mod_mcp/lang/en.php
 */

$lang['mod_mcp'] = [
    'module_title' => 'MCP API Keys',
    'page_intro' => 'Create and manage API keys for the Model Context Protocol endpoint. Clients authenticate with these keys to manage contents and content categories remotely.',
    'endpoint_label' => 'MCP Endpoint',
    'no_keys' => 'No API keys created yet.',
    'key_name' => 'Key Name',
    'key_prefix_label' => 'Key Prefix',
    'group_label' => 'Permission Group',
    'status_label' => 'Status',
    'created_at_label' => 'Created At',
    'last_used_label' => 'Last Used',
    'creator_label' => 'Created By',
    'active' => 'Active',
    'revoked' => 'Revoked',
    'never_used' => 'Never used',
    'create_key_title' => 'Create New API Key',
    'name_placeholder' => 'e.g. Content automation client',
    'submit_create' => 'Generate Key',
    'key_created_title' => 'API Key Created',
    'key_created_warning' => 'Copy this key now. It will never be shown again.',
    'copy_key' => 'Copy Key',
    'key_copied' => 'API key copied to clipboard.',
    'reveal_close' => 'Done',
    'delete_confirm' => 'Are you sure you want to permanently delete this API key? Clients using it will immediately lose access.',
    'toggle_activate' => 'Activate Key',
    'toggle_revoke' => 'Revoke Key',
    'delete_key' => 'Delete Key',
    'oauth_section_title' => 'OAuth 2.1 Clients',
    'oauth_hint' => 'OAuth clients connect through the /oauth endpoints (ChatGPT uses its own signed client identity and does not appear here). Deactivating a client immediately revokes its tokens; deleting also removes its codes and tokens.',
    'oauth_no_clients' => 'No OAuth clients registered yet. Clients register themselves via /oauth/register when connecting.',
    'oauth_tokens_active' => 'Active tokens',
    'oauth_deactivate' => 'Deactivate Client',
    'oauth_activate' => 'Activate Client',
    'oauth_delete' => 'Delete Client',
    'oauth_delete_confirm' => 'Are you sure you want to permanently delete this OAuth client? Its authorization codes and tokens will be removed immediately.',
    'errors' => [
        'invalid_name' => 'Please provide a key name between 1 and 100 characters.',
        'invalid_group' => 'Please select a valid permission group.',
        'record' => 'API key record not found.'
    ],
    'role_errors' => [
        'list' => 'You do not have permission to view MCP API keys.',
        'add' => 'You do not have permission to create MCP API keys.',
        'edit' => 'You do not have permission to edit MCP API keys.',
        'delete' => 'You do not have permission to delete MCP API keys.'
    ],
    'message' => [
        'success' => [
            'add' => 'API key created successfully.',
            'edit' => 'API key updated successfully.',
            'delete' => 'API key deleted successfully.'
        ],
        'error' => [
            'add' => 'Failed to create API key.',
            'edit' => 'Failed to update API key.',
            'delete' => 'Failed to delete API key.'
        ]
    ],
    'role' => [
        'name' => 'MCP API Key Roles',
        'list' => 'Can View MCP API Keys',
        'add' => 'Can Create MCP API Keys',
        'edit' => 'Can Edit MCP API Keys',
        'delete' => 'Can Delete MCP API Keys'
    ]
];
