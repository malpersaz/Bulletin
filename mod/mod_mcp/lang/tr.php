<?php
/**
 * Amed Framework
 * MCP Module Turkish Language Pack
 * mod/mod_mcp/lang/tr.php
 */

$lang['mod_mcp'] = [
    'module_title' => 'MCP API Anahtarları',
    'page_intro' => 'Model Context Protocol uç noktası için API anahtarları oluşturun ve yönetin. İstemciler bu anahtarlarla kimlik doğrulayarak içerikleri ve içerik kategorilerini uzaktan yönetir.',
    'endpoint_label' => 'MCP Uç Noktası',
    'no_keys' => 'Henüz oluşturulmuş bir API anahtarı yok.',
    'key_name' => 'Anahtar Adı',
    'key_prefix_label' => 'Anahtar Ön Eki',
    'group_label' => 'Yetki Grubu',
    'status_label' => 'Durum',
    'created_at_label' => 'Oluşturma Tarihi',
    'last_used_label' => 'Son Kullanım',
    'creator_label' => 'Oluşturan',
    'active' => 'Etkin',
    'revoked' => 'İptal Edildi',
    'never_used' => 'Henüz kullanılmadı',
    'create_key_title' => 'Yeni API Anahtarı Oluştur',
    'name_placeholder' => 'örn. İçerik otomasyon istemcisi',
    'submit_create' => 'Anahtar Oluştur',
    'key_created_title' => 'API Anahtarı Oluşturuldu',
    'key_created_warning' => 'Bu anahtarı şimdi kopyalayın. Bir daha gösterilmeyecek.',
    'copy_key' => 'Anahtarı Kopyala',
    'key_copied' => 'API anahtarı panoya kopyalandı.',
    'reveal_close' => 'Tamam',
    'delete_confirm' => 'Bu API anahtarını kalıcı olarak silmek istediğinizden emin misiniz? Anahtarı kullanan istemciler erişimi hemen kaybedecek.',
    'toggle_activate' => 'Anahtarı Etkinleştir',
    'toggle_revoke' => 'Anahtarı İptal Et',
    'delete_key' => 'Anahtarı Sil',
    'oauth_section_title' => 'OAuth 2.1 İstemciler',
    'oauth_hint' => '/oauth uçları üzerinden bağlanan istemciler (ChatGPT kendi imzalı istemci kimliğini kullandığı için burada görünmez). İstemciyi devre dışı bırakmak tokenlarını anında geçersiz kılar; silmek kodlarını ve tokenlarını da kaldırır.',
    'oauth_no_clients' => 'Henüz OAuth istemcisi kayıtlı değil. İstemciler bağlanırken /oauth/register üzerinden kendilerini kaydeder.',
    'oauth_tokens_active' => 'Aktif token',
    'oauth_deactivate' => 'İstemciyi Devre Dışı Bırak',
    'oauth_activate' => 'İstemciyi Etkinleştir',
    'oauth_delete' => 'İstemciyi Sil',
    'oauth_delete_confirm' => 'Bu OAuth istemcisini kalıcı olarak silmek istediğine emin misin? Yetkilendirme kodları ve tokenları anında kaldırılacak.',
    'errors' => [
        'invalid_name' => 'Lütfen 1 ile 100 karakter arasında bir anahtar adı girin.',
        'invalid_group' => 'Lütfen geçerli bir yetki grubu seçin.',
        'record' => 'API anahtarı kaydı bulunamadı.'
    ],
    'role_errors' => [
        'list' => 'MCP API anahtarlarını görüntüleme izniniz yok.',
        'add' => 'MCP API anahtarı oluşturma izniniz yok.',
        'edit' => 'MCP API anahtarlarını düzenleme izniniz yok.',
        'delete' => 'MCP API anahtarlarını silme izniniz yok.'
    ],
    'message' => [
        'success' => [
            'add' => 'API anahtarı başarıyla oluşturuldu.',
            'edit' => 'API anahtarı başarıyla güncellendi.',
            'delete' => 'API anahtarı başarıyla silindi.'
        ],
        'error' => [
            'add' => 'API anahtarı oluşturulamadı.',
            'edit' => 'API anahtarı güncellenemedi.',
            'delete' => 'API anahtarı silinemedi.'
        ]
    ],
    'audit_title' => 'MCP Denetim Kayıtları ve Metrikler',
    'audit_intro' => 'Araç yürütme kayıtlarını, çağrı yapan etkinliklerini, yanıt gecikmesini ve günlük token tüketimini takip edin.',
    'audit_filter_all' => 'Tüm Çağrılar',
    'audit_filter_api_key' => 'API Anahtarı',
    'audit_filter_oauth' => 'OAuth',
    'audit_filter_agent_chat' => 'Agent Chat',
    'audit_filter_internal' => 'Dahili',
    'audit_daily_tokens' => 'Günlük Kullanılan Token',
    'audit_total_calls' => 'Toplam Çağrı',
    'audit_error_calls' => 'Hatalar',
    'audit_no_logs' => 'Henüz kaydedilmiş bir denetim kaydı yok.',
    'audit_tool_name' => 'Araç Adı',
    'audit_caller' => 'Çağrı Yapan',
    'audit_latency' => 'Gecikme',
    'audit_status' => 'Durum',
    'audit_timestamp' => 'Zaman',
    'nav_keys' => 'API Anahtarları ve OAuth',
    'nav_audit' => 'Denetim Kayıtları',
    'role' => [
        'name' => 'MCP API Anahtarı Rolleri',
        'list' => 'MCP API Anahtarlarını Görüntüleyebilir',
        'add' => 'MCP API Anahtarı Oluşturabilir',
        'edit' => 'MCP API Anahtarlarını Düzenleyebilir',
        'delete' => 'MCP API Anahtarlarını Silebilir'
    ]
];
