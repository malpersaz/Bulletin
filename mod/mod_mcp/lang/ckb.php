<?php
/**
 * Amed Framework
 * MCP Module Kurdish (Sorani / Central Kurdish) Language Pack
 * mod/mod_mcp/lang/ckb.php
 */

$lang['mod_mcp'] = [
    'module_title' => 'کلیلەکانی APIی MCP',
    'page_intro' => 'بۆ بەستنەوەی Model Context Protocol (MCP) کلیلەکانی API دروست بکە و بەڕێوەیان ببە. ئەپ و بریکارە دەرەکییەکان بەم کلیلانە ناسنامەی خۆیان دەسەلمێنن و لە دوورەوە ناوەڕۆکی ماڵپەڕ بەڕێوە دەبەن.',
    'endpoint_label' => 'بەستەری ڕاژەی MCP (Endpoint)',
    'copy_endpoint' => 'کۆپیکردنی بەستەر',
    'endpoint_copied' => 'بەستەری ڕاژەی MCP کۆپی کرایە کلیپبۆرد.',
    'stat_total_keys' => 'کۆی کلیلەکان',
    'stat_active_keys' => 'چالاک',
    'stat_oauth_clients' => 'ڕاژەخوازەکانی OAuth',
    'keys_section_title' => 'کلیلە دروستکراوەکانی API',
    'no_keys' => 'هێشتا هیچ کلیلێکی API دروست نەکراوە.',
    'key_name' => 'ناوی کلیل',
    'key_prefix_label' => 'پێشگری کلیل',
    'group_label' => 'گرووپی دەسەڵات',
    'status_label' => 'دۆخ',
    'created_at_label' => 'ڕێکەوتی دروستکردن',
    'last_used_label' => 'دوایین بەکارهێنان',
    'creator_label' => 'دروستکەر',
    'active' => 'چالاک',
    'revoked' => 'هەڵوەشاوەتەوە',
    'never_used' => 'هەرگیز بەکارنەهێنراوە',
    'create_key_title' => 'دروستکردنی کلیلی نوێی API',
    'name_placeholder' => 'بۆ نموونە: بریکاری خۆکارکردنی هەواڵ',
    'submit_create' => 'دروستکردنی کلیل',
    'key_created_title' => 'کلیلی API دروست کرا',
    'key_created_warning' => 'ئێستا ئەم کلیلە کۆپی بکە. لەبەر پاراستنی ئاسایش جارێکی تر پیشان نادرێتەوە.',
    'copy_key' => 'کۆپیکردنی کلیل',
    'key_copied' => 'کلیلی API کۆپی کرایە کلیپبۆرد.',
    'reveal_close' => 'تەواو',
    'delete_confirm' => 'دڵنیایت کە دەتەوێت ئەم کلیلەی API بۆ هەمیشە بسڕیتەوە؟ هەموو ئەو ڕاژەخوازانەی بەکاریدەهێنن دەستبەجێ مافی دەستگەیشتنیان لەدەست دەدەن.',
    'toggle_activate' => 'چالاککردن',
    'toggle_revoke' => 'هەڵوەشاندنەوە',
    'delete_key' => 'سڕینەوە',
    'oauth_section_title' => 'ڕاژەخوازە بەستراوەکانی OAuth 2.1',
    'oauth_hint' => 'ڕاژەخوازەکانی OAuth لە ڕێگەی خاڵەکانی /oauth پەیوەست دەبن (Gemini و ChatGPT ناسنامەی واژۆکراوی خۆیان بەکاردەهێنن). ناچالاککردنی ڕاژەخوازێک دەستبەجێ هەموو تۆکنەکانی هەڵدەوەشێنێتەوە؛ سڕینەوەش کۆد و تۆکنەکانی بە تەواوی لادەبات.',
    'oauth_no_clients' => 'هێشتا هیچ ڕاژەخوازێکی OAuth تۆمار نەکراوە. ڕاژەخوازەکان لە کاتی پەیوەستبووندا لە ڕێگەی /oauth/register خۆکارانە خۆیان تۆمار دەکەن.',
    'oauth_tokens_active' => 'تۆکنە چالاکەکان',
    'oauth_deactivate' => 'ناچالاککردن',
    'oauth_activate' => 'چالاککردن',
    'oauth_delete' => 'سڕینەوە',
    'oauth_delete_confirm' => 'دڵنیایت کە دەتەوێت ئەم ڕاژەخوازەی OAuth بۆ هەمیشە بسڕیتەوە؟ کۆدەکانی ڕێگەپێدان و تۆکنەکانی دەستبەجێ دەسڕدرێنەوە.',
    'errors' => [
        'invalid_name' => 'تکایە ناوێکی کلیل لە نێوان ١ بۆ ١٠٠ نووسەدا بنووسە.',
        'invalid_group' => 'تکایە گرووپێکی دەسەڵاتی دروست هەڵبژێرە.',
        'record' => 'تۆماری کلیلی API نەدۆزرایەوە.'
    ],
    'role_errors' => [
        'list' => 'دەسەڵاتی بینینی کلیلەکانی APIی MCPت نییە.',
        'add' => 'دەسەڵاتی دروستکردنی کلیلەکانی APIی MCPت نییە.',
        'edit' => 'دەسەڵاتی دەستکاریکردنی کلیلەکانی APIی MCPت نییە.',
        'delete' => 'دەسەڵاتی سڕینەوەی کلیلەکانی APIی MCPت نییە.'
    ],
    'message' => [
        'success' => [
            'add' => 'کلیلی API بە سەرکەوتوویی دروست کرا.',
            'edit' => 'دۆخی کلیلی API بە سەرکەوتوویی نوێ کرایەوە.',
            'delete' => 'کلیلی API بە سەرکەوتوویی سڕایەوە.'
        ],
        'error' => [
            'add' => 'دروستکردنی کلیلی API سەرکەوتوو نەبوو.',
            'edit' => 'نوێکردنەوەی کلیلی API سەرکەوتوو نەبوو.',
            'delete' => 'سڕینەوەی کلیلی API سەرکەوتوو نەبوو.'
        ]
    ],
    'audit_title' => 'تۆمارەکانی چاودێری و ئامارەکانی MCP',
    'audit_intro' => 'چاودێریی تۆمارەکانی بەکارخستنی ئامرازەکان، جموجۆڵی داواکاران، ماوەی وەڵامدانەوە و بەکاربردنی ڕۆژانەی تۆکنەکان بکە.',
    'audit_filter_all' => 'هەموو سەرچاوەکان',
    'audit_filter_api_key' => 'کلیلی API',
    'audit_filter_oauth' => 'OAuth 2.1',
    'audit_filter_agent_chat' => 'چاتی بریکار',
    'audit_filter_internal' => 'ناوخۆیی',
    'audit_filter_submit' => 'پاڵاوتن',
    'audit_daily_tokens' => 'تۆکنی ڕۆژانە',
    'audit_total_calls' => 'کۆی داواکارییەکان',
    'audit_error_calls' => 'هەڵەکان',
    'audit_no_logs' => 'هێشتا هیچ تۆمارێکی چاودێری تۆمار نەکراوە.',
    'audit_tool_name' => 'ناوی ئامراز',
    'audit_caller' => 'داواکار',
    'audit_latency' => 'ماوەی وەڵام',
    'audit_status' => 'دۆخ',
    'audit_timestamp' => 'کات',
    'nav_keys' => 'کلیلەکانی API و OAuth',
    'nav_audit' => 'تۆمارەکانی چاودێری',
    'role' => [
        'name' => 'دەسەڵاتەکانی کلیلی MCP',
        'list' => 'دەتوانێت کلیلەکانی MCP ببینێت',
        'add' => 'دەتوانێت کلیلی MCP دروست بکات',
        'edit' => 'دەتوانێت کلیلەکانی MCP دەستکاری بکات',
        'delete' => 'دەتوانێت کلیلەکانی MCP بسڕێتەوە'
    ]
];
