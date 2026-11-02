<?php
/**
 * Amed Framework
 * MCP Module Kurdish (Kurmanji) Language Pack
 * mod/mod_mcp/lang/ku.php
 */

$lang['mod_mcp'] = [
    'module_title' => 'Mifteyên API yên MCP',
    'page_intro' => 'Ji bo girêdana Model Context Protocol (MCP) mifteyên APIyê çêke û birêve bibe. Sepan û ajansên derveyî bi van mifteyan nasnameya xwe piştrast dikin û naveroka malperê ji dûr ve birêve dibin.',
    'endpoint_label' => 'Girêdana Servera MCP (Endpoint)',
    'copy_endpoint' => 'Girêdanê Kopî Bike',
    'endpoint_copied' => 'Girêdana MCPyê li panoyê hat kopîkirin.',
    'stat_total_keys' => 'Tevahiya Mifteyan',
    'stat_active_keys' => 'Çalak',
    'stat_oauth_clients' => 'Sepanên OAuth',
    'keys_section_title' => 'Mifteyên API yên Çêkirî',
    'no_keys' => 'Hîn tu mifteyeke APIyê nehatiye çêkirin.',
    'key_name' => 'Navê Mifteyê',
    'key_prefix_label' => 'Pêşgira Mifteyê',
    'group_label' => 'Koma Rayeyan',
    'status_label' => 'Rewş',
    'created_at_label' => 'Dîroka Çêkirinê',
    'last_used_label' => 'Bikaranîna Dawî',
    'creator_label' => 'Çêker',
    'active' => 'Çalak',
    'revoked' => 'Hat Betalkirin',
    'never_used' => 'Qet nehatiye bikaranîn',
    'create_key_title' => 'Mifteyeke Nû ya APIyê Çêke',
    'name_placeholder' => 'mînak: Ajansa otomasyona nûçeyan',
    'submit_create' => 'Mifteyê Çêke',
    'key_created_title' => 'Mifteya APIyê Hat Çêkirin',
    'key_created_warning' => 'Vê mifteyê niha kopî bike. Ji bo ewlehiyê careke din nayê nîşandan.',
    'copy_key' => 'Mifteyê Kopî Bike',
    'key_copied' => 'Mifteya APIyê li panoyê hat kopîkirin.',
    'reveal_close' => 'Temam',
    'delete_confirm' => 'Tu bawer î ku dixwazî vê mifteya APIyê bi temamî jê bibî? Hemû sepanên ku vê mifteyê bi kar tînin dê tavilê gihîştina xwe winda bikin.',
    'toggle_activate' => 'Çalak Bike',
    'toggle_revoke' => 'Betal Bike',
    'delete_key' => 'Jê Bibe',
    'oauth_section_title' => 'Sepanên Girêdayî yên OAuth 2.1',
    'oauth_hint' => 'Sepanên OAuth bi rêya navnîşanên /oauth têkilî datînin (Gemini û ChatGPT nasnameya xwe ya îmzekirî bi kar tînin). Bêçalakkirina sepanekê hemû tokenên wê tavilê betal dike; jêbirin jî kod û tokenên wê bi temamî radike.',
    'oauth_no_clients' => 'Hîn tu sepaneke OAuth nehatiye tomarkirin. Sepan dema girêdanê xwe bi rêya /oauth/register bixweber tomar dikin.',
    'oauth_tokens_active' => 'Tokenên çalak',
    'oauth_deactivate' => 'Bêçalak Bike',
    'oauth_activate' => 'Çalak Bike',
    'oauth_delete' => 'Jê Bibe',
    'oauth_delete_confirm' => 'Tu bawer î ku dixwazî vê sepana OAuth bi temamî jê bibî? Kodên destûrdanê û tokenên wê dê tavilê werin jêbirin.',
    'errors' => [
        'invalid_name' => 'Ji kerema xwe navekî mifteyê yê di navbera 1 û 100 karakteran de binivîse.',
        'invalid_group' => 'Ji kerema xwe komeke rayeyan a derbasdar hilbijêre.',
        'record' => 'Tomara mifteya APIyê nehat dîtin.'
    ],
    'role_errors' => [
        'list' => 'Rayeya te ya dîtina mifteyên API yên MCPyê tune ye.',
        'add' => 'Rayeya te ya çêkirina mifteyên API yên MCPyê tune ye.',
        'edit' => 'Rayeya te ya guhertina mifteyên API yên MCPyê tune ye.',
        'delete' => 'Rayeya te ya jêbirina mifteyên API yên MCPyê tune ye.'
    ],
    'message' => [
        'success' => [
            'add' => 'Mifteya APIyê bi serkeftî hat çêkirin.',
            'edit' => 'Rewşa mifteya APIyê bi serkeftî hat nûjenkirin.',
            'delete' => 'Mifteya APIyê bi serkeftî hat jêbirin.'
        ],
        'error' => [
            'add' => 'Mifteya APIyê nehat çêkirin.',
            'edit' => 'Mifteya APIyê nehat nûjenkirin.',
            'delete' => 'Mifteya APIyê nehat jêbirin.'
        ]
    ],
    'audit_title' => 'Tomarên Kontrolê û Amarên MCP',
    'audit_intro' => 'Tomarên xebitandina amûran, tevgerên daxwazkaran, maweya bersivdayînê û xerckirina rojane ya tokenan bişopîne.',
    'audit_filter_all' => 'Hemû Çavkanî',
    'audit_filter_api_key' => 'Mifteya API',
    'audit_filter_oauth' => 'OAuth 2.1',
    'audit_filter_agent_chat' => 'Sohbeta Ajansê',
    'audit_filter_internal' => 'Navxweyî',
    'audit_filter_submit' => 'Parzûn Bike',
    'audit_filter_status_all' => 'Hemû Encam',
    'audit_filter_status_success' => 'Serkeftî (2xx)',
    'audit_filter_status_error' => 'Çewtî (4xx / 5xx)',
    'audit_caller_anon' => 'Anonîm',
    'audit_caller_internal' => 'Pergala Navxweyî',
    'audit_ip_copied' => 'Navnîşana IP li bîrgehê hate kopîkirin',
    'audit_daily_tokens' => 'Tokenên Rojane',
    'audit_total_calls' => 'Tevahiya Daxwazan',
    'audit_error_calls' => 'Çewtî',
    'audit_no_logs' => 'Hîn tu tomareke kontrolê nehatiye tomarkirin.',
    'audit_tool_name' => 'Navê Amûrê',
    'audit_caller' => 'Daxwazkar',
    'audit_latency' => 'Maweya Bersivê',
    'audit_status' => 'Rewş',
    'audit_timestamp' => 'Dem',
    'nav_keys' => 'Mifteyên API & OAuth',
    'nav_audit' => 'Tomarên Kontrolê',
    'role' => [
        'name' => 'Rayeyên Mifteyên MCP',
        'list' => 'Dikare Mifteyên MCP Bibîne',
        'add' => 'Dikare Mifteyên MCP Çêke',
        'edit' => 'Dikare Mifteyên MCP Biguherîne',
        'delete' => 'Dikare Mifteyên MCP Jê Bibe'
    ]
];
