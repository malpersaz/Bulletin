<?php
/**
 * Amed Framework
 * MCP Module German Language Pack
 * mod/mod_mcp/lang/de.php
 */

$lang['mod_mcp'] = [
    'module_title' => 'MCP-API-Schlüssel',
    'page_intro' => 'Erstellen und verwalten Sie API-Schlüssel für den Model Context Protocol-Endpunkt. Clients authentifizieren sich mit diesen Schlüsseln und verwalten Inhalte sowie Inhaltskategorien aus der Ferne.',
    'endpoint_label' => 'MCP-Endpunkt',
    'no_keys' => 'Es wurden noch keine API-Schlüssel erstellt.',
    'key_name' => 'Schlüsselname',
    'key_prefix_label' => 'Schlüsselpräfix',
    'group_label' => 'Berechtigungsgruppe',
    'status_label' => 'Status',
    'created_at_label' => 'Erstellt am',
    'last_used_label' => 'Zuletzt verwendet',
    'creator_label' => 'Erstellt von',
    'active' => 'Aktiv',
    'revoked' => 'Widerrufen',
    'never_used' => 'Noch nie verwendet',
    'create_key_title' => 'Neuen API-Schlüssel erstellen',
    'name_placeholder' => 'z. B. Client für Inhaltsautomatisierung',
    'submit_create' => 'Schlüssel erstellen',
    'key_created_title' => 'API-Schlüssel erstellt',
    'key_created_warning' => 'Kopieren Sie diesen Schlüssel jetzt. Er wird nie wieder angezeigt.',
    'copy_key' => 'Schlüssel kopieren',
    'key_copied' => 'API-Schlüssel wurde in die Zwischenablage kopiert.',
    'reveal_close' => 'Fertig',
    'delete_confirm' => 'Sind Sie sicher, dass Sie diesen API-Schlüssel dauerhaft löschen möchten? Clients, die ihn verwenden, verlieren sofort den Zugriff.',
    'toggle_activate' => 'Schlüssel aktivieren',
    'toggle_revoke' => 'Schlüssel widerrufen',
    'delete_key' => 'Schlüssel löschen',
    'oauth_section_title' => 'OAuth 2.1-Clients',
    'oauth_hint' => 'Clients, die über die /oauth-Endpunkte verbinden (ChatGPT nutzt seine eigene signierte Client-Identität und erscheint hier nicht). Das Deaktivieren eines Clients widerruft sofort seine Tokens; das Löschen entfernt auch dessen Codes und Tokens.',
    'oauth_no_clients' => 'Noch keine OAuth-Clients registriert. Clients registrieren sich beim Verbinden selbst über /oauth/register.',
    'oauth_tokens_active' => 'Aktive Tokens',
    'oauth_deactivate' => 'Client deaktivieren',
    'oauth_activate' => 'Client aktivieren',
    'oauth_delete' => 'Client löschen',
    'oauth_delete_confirm' => 'Möchtest du diesen OAuth-Client endgültig löschen? Seine Autorisierungscodes und Tokens werden sofort entfernt.',
    'errors' => [
        'invalid_name' => 'Bitte geben Sie einen Schlüsselnamen zwischen 1 und 100 Zeichen ein.',
        'invalid_group' => 'Bitte wählen Sie eine gültige Berechtigungsgruppe.',
        'record' => 'API-Schlüssel-Datensatz nicht gefunden.'
    ],
    'role_errors' => [
        'list' => 'Sie haben keine Berechtigung, MCP-API-Schlüssel einzusehen.',
        'add' => 'Sie haben keine Berechtigung, MCP-API-Schlüssel zu erstellen.',
        'edit' => 'Sie haben keine Berechtigung, MCP-API-Schlüssel zu bearbeiten.',
        'delete' => 'Sie haben keine Berechtigung, MCP-API-Schlüssel zu löschen.'
    ],
    'message' => [
        'success' => [
            'add' => 'API-Schlüssel erfolgreich erstellt.',
            'edit' => 'API-Schlüssel erfolgreich aktualisiert.',
            'delete' => 'API-Schlüssel erfolgreich gelöscht.'
        ],
        'error' => [
            'add' => 'API-Schlüssel konnte nicht erstellt werden.',
            'edit' => 'API-Schlüssel konnte nicht aktualisiert werden.',
            'delete' => 'API-Schlüssel konnte nicht gelöscht werden.'
        ]
    ],
    'audit_title' => 'MCP-Audit-Protokolle & Metriken',
    'audit_intro' => 'Verfolgen Sie Tool-Ausführungsprotokolle, Aufruferaktivitäten, Antwortlatenz und den täglichen Token-Verbrauch.',
    'audit_filter_all' => 'Alle Aufrufer',
    'audit_filter_api_key' => 'API-Schlüssel',
    'audit_filter_oauth' => 'OAuth',
    'audit_filter_agent_chat' => 'Agenten-Chat',
    'audit_filter_internal' => 'Intern',
    'audit_daily_tokens' => 'Täglich verbrauchte Tokens',
    'audit_total_calls' => 'Aufrufe gesamt',
    'audit_error_calls' => 'Fehler',
    'audit_no_logs' => 'Noch keine Audit-Protokolleinträge aufgezeichnet.',
    'audit_tool_name' => 'Tool-Name',
    'audit_caller' => 'Aufrufer',
    'audit_latency' => 'Latenz',
    'audit_status' => 'Status',
    'audit_timestamp' => 'Zeit',
    'nav_keys' => 'API-Schlüssel & OAuth',
    'nav_audit' => 'Audit-Protokolle',
    'role' => [
        'name' => 'MCP-API-Schlüssel-Rollen',
        'list' => 'Kann MCP-API-Schlüssel einsehen',
        'add' => 'Kann MCP-API-Schlüssel erstellen',
        'edit' => 'Kann MCP-API-Schlüssel bearbeiten',
        'delete' => 'Kann MCP-API-Schlüssel löschen'
    ]
];
