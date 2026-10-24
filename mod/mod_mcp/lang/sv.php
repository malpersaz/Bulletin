<?php
/**
 * Amed Framework
 * MCP Module Swedish Language Pack
 * mod/mod_mcp/lang/sv.php
 */

$lang['mod_mcp'] = [
    'module_title' => 'MCP API-nycklar',
    'page_intro' => 'Skapa och hantera API-nycklar för Model Context Protocol-slutpunkten. Klienter autentiseras med dessa nycklar för att hantera innehåll och innehållskategorier på distans.',
    'endpoint_label' => 'MCP-slutpunkt',
    'no_keys' => 'Inga API-nycklar har skapats ännu.',
    'key_name' => 'Nyckelnamn',
    'key_prefix_label' => 'Nyckelprefix',
    'group_label' => 'Behörighetsgrupp',
    'status_label' => 'Status',
    'created_at_label' => 'Skapad',
    'last_used_label' => 'Senast använd',
    'creator_label' => 'Skapad av',
    'active' => 'Aktiv',
    'revoked' => 'Återkallad',
    'never_used' => 'Aldrig använd',
    'create_key_title' => 'Skapa ny API-nyckel',
    'name_placeholder' => 't.ex. Klient för innehållsautomation',
    'submit_create' => 'Generera nyckel',
    'key_created_title' => 'API-nyckel skapad',
    'key_created_warning' => 'Kopiera den här nyckeln nu. Den visas aldrig igen.',
    'copy_key' => 'Kopiera nyckel',
    'key_copied' => 'API-nyckeln kopierades till urklipp.',
    'reveal_close' => 'Klar',
    'delete_confirm' => 'Är du säker på att du vill ta bort denna API-nyckel permanent? Klienter som använder den förlorar omedelbart åtkomsten.',
    'toggle_activate' => 'Aktivera nyckel',
    'toggle_revoke' => 'Återkalla nyckel',
    'delete_key' => 'Ta bort nyckel',
    'oauth_section_title' => 'OAuth 2.1-klienter',
    'oauth_hint' => 'Klienter som ansluter via /oauth-ändpunkterna (ChatGPT använder sin egen signerade klientidentitet och visas inte här). Att avaktivera en klient återkaller omedelbart dess tokens; borttagning tar även bort koder och tokens.',
    'oauth_no_clients' => 'Inga OAuth-klienter registrerade ännu. Klienter registrerar sig själva via /oauth/register vid anslutning.',
    'oauth_tokens_active' => 'Aktiva tokens',
    'oauth_deactivate' => 'Avaktivera klient',
    'oauth_activate' => 'Aktivera klient',
    'oauth_delete' => 'Ta bort klient',
    'oauth_delete_confirm' => 'Är du säker på att du vill ta bort denna OAuth-klient permanent? Dess auktoriseringskoder och tokens tas bort omedelbart.',
    'errors' => [
        'invalid_name' => 'Ange ett nyckelnamn mellan 1 och 100 tecken.',
        'invalid_group' => 'Välj en giltig behörighetsgrupp.',
        'record' => 'API-nyckelpost hittades inte.'
    ],
    'role_errors' => [
        'list' => 'Du har inte behörighet att visa MCP API-nycklar.',
        'add' => 'Du har inte behörighet att skapa MCP API-nycklar.',
        'edit' => 'Du har inte behörighet att redigera MCP API-nycklar.',
        'delete' => 'Du har inte behörighet att ta bort MCP API-nycklar.'
    ],
    'message' => [
        'success' => [
            'add' => 'API-nyckel skapades.',
            'edit' => 'API-nyckel uppdaterades.',
            'delete' => 'API-nyckel togs bort.'
        ],
        'error' => [
            'add' => 'Det gick inte att skapa API-nyckeln.',
            'edit' => 'Det gick inte att uppdatera API-nyckeln.',
            'delete' => 'Det gick inte att ta bort API-nyckeln.'
        ]
    ],
    'audit_title' => 'MCP-granskningsloggar och mätvärden',
    'audit_intro' => 'Spåra verktygskörningsloggar, anroparaktiviteter, svarslatens och daglig tokenförbrukning.',
    'audit_filter_all' => 'Alla anropare',
    'audit_filter_api_key' => 'API-nyckel',
    'audit_filter_oauth' => 'OAuth',
    'audit_filter_agent_chat' => 'Agentchatt',
    'audit_filter_internal' => 'Intern',
    'audit_daily_tokens' => 'Dagliga tokens använda',
    'audit_total_calls' => 'Totalt antal anrop',
    'audit_error_calls' => 'Fel',
    'audit_no_logs' => 'Inga granskningsloggar har registrerats ännu.',
    'audit_tool_name' => 'Verktygsnamn',
    'audit_caller' => 'Anropare',
    'audit_latency' => 'Latens',
    'audit_status' => 'Status',
    'audit_timestamp' => 'Tid',
    'nav_keys' => 'API-nycklar & OAuth',
    'nav_audit' => 'Granskningsloggar',
    'role' => [
        'name' => 'MCP-nyckelroller',
        'list' => 'Kan visa MCP-nycklar',
        'add' => 'Kan skapa MCP-nycklar',
        'edit' => 'Kan redigera MCP-nycklar',
        'delete' => 'Kan ta bort MCP-nycklar'
    ]
];
