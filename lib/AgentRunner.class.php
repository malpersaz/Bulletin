<?php

require_once __DIR__ . '/GeminiTransport.php';

if (!class_exists('Obuilder', false) && defined('lib_dir')) {
    require_once lib_dir . 'Orm' . DS . 'Obuilder.php';
}

/**
 * AgentRunner - Decoupled Agent Orchestration Service
 *
 * Encapsulates the Gemini API execution loop, dynamic tool pruning,
 * model fallback sequence, human-in-the-loop safeguards, token budgeting,
 * and conversational context management for Amed CMS.
 */
class AgentRunner
{
    private int $userId;
    private int $groupId;
    private string $activeLang;
    private string $apiKey;
    private float $deadline;
    private McpServer $mcpServer;
    private array $executedTools = [];
    private int $totalTokensUsed = 0;
    private ?string $activeModel = null;
    private array $failedModels = [];
    private float $perCallCapSeconds = 30.0;
    private float $summaryReserveSeconds = 10.0;
    private ?GeminiTransport $transport = null;
    private $eventSink = null;
    private array $modelTimeoutStrikes = [];
    private int $maxTokensPerRun = 400000;
    private static bool $stateTableEnsured = false;
    private bool $readOnly = false;
    private ?string $lockToken = null;

    public const FLASH_MODELS = [
        'gemini-3.8-flash',
        'gemini-3.6-flash',
        'gemini-3.5-flash',
        'gemini-3.1-flash-lite',
    ];

    /**
     * Domain keyword dictionary for fast pre-flight classification (Bulletin-scoped)
     */
    private const INTENT_KEYWORDS = [
        'menu' => ['menu', 'pêşek', 'pesek', 'navîgasyon', 'menü', 'link', 'lînk', 'header', 'footer'],
        'news' => ['news', 'nûçe', 'nuce', 'haber', 'gotar', 'post', 'article', 'articles', 'kategori', 'nivîs', 'nivis', 'makale', 'yazı'],
        'headline' => ['headline', 'manşet', 'manset', 'sernûçe', 'sernuce', 'cuff', 'sürmanşet'],
        'publication' => ['publication', 'weşan', 'wesan', 'belavok', 'dergi', 'kovar', 'bulten'],
        'announcement' => ['announcement', 'daxuyanî', 'daxuyani', 'duyuru', 'îlan', 'ilan'],
        'page' => ['page', 'rûpel', 'rupel', 'sayfa'],
        'gallery' => ['gallery', 'galeri', 'wêne', 'wene', 'resim', 'albûm', 'album', 'foto'],
        'slider' => ['slider', 'slayt', 'carousel'],
        'faq' => ['faq', 'pirs', 'bersiv', 'soru', 'cevap'],
        'video' => ['video', 'vîdyo', 'vidyo', 'embed'],
        'document' => ['document', 'belge', 'dokuman', 'döküman', 'dosya'],
        'attachment' => ['attachment', 'pêvek', 'pevek', 'ek', 'pel'],
        'imagecategories' => ['image_category', 'imagecategory', 'kategoriyên wêneyan', 'resim kategorisi'],
        'survey' => ['survey', 'anket', 'rapirsî', 'rapirsi', 'poll'],
        'popup' => ['popup', 'pencere', 'modal'],
        'redirect' => ['redirect', 'beralîkirin', 'beralikirin', 'yonlendirme', 'yönlendirme', '301', 'old_url', 'new_url'],
        'seo' => ['seo', 'meta', 'title', 'description', 'etîket', 'etiket', 'slug', 'optimize', 'optimîze', 'rehber', 'rêber', 'guide', 'lêkolîn', 'lekolin', 'araştır', 'curate', 'serp'],
        'settings' => ['setting', 'mîheng', 'miheng', 'ayar', 'site', 'email', 'navnîşan', 'peywendî', 'social', 'cookie', 'affiliation', 'hevgirêdan', 'hevgirêdanî', 'sazî', 'partner'],
        'acl' => ['acl', 'destûr', 'destur', 'bikarhêner', 'bikarhener', 'user', 'permission', 'rol', 'yetki'],
    ];

    /**
     * Timeout ladder profile configurations (§3.1)
     */
    public static function profile(string $name): array
    {
        switch ($name) {
            case 'stream':
                return [
                    'per_call_cap' => 45.0,
                    'summary_reserve' => 12.0,
                    'deadline' => 240.0,
                    'time_limit' => 270,
                    'client_watchdog' => 45,
                ];
            case 'job':
                return [
                    'per_call_cap' => 60.0,
                    'summary_reserve' => 15.0,
                    'deadline' => 600.0,
                    'time_limit' => 0,
                    'client_watchdog' => 0,
                ];
            case 'blocking':
            default:
                return [
                    'per_call_cap' => 30.0,
                    'summary_reserve' => 10.0,
                    'deadline' => 75.0,
                    'time_limit' => 90,
                    'client_watchdog' => 85,
                ];
        }
    }

    public function __construct(
        int $userId,
        int $groupId,
        string $activeLang = 'ku',
        ?string $apiKey = null,
        float|string $timeoutOrProfile = 'blocking',
        ?string $preferredModel = null,
        ?GeminiTransport $transport = null
    ) {
        $this->userId = $userId;
        $this->groupId = $groupId;
        $this->activeLang = $activeLang;
        $this->activeModel = $preferredModel;
        $this->apiKey = $apiKey ?: self::resolveApiKey();

        if (is_string($timeoutOrProfile) && in_array($timeoutOrProfile, ['blocking', 'stream', 'job'], true)) {
            $p = self::profile($timeoutOrProfile);
            $timeoutSeconds = (float) $p['deadline'];
            $this->perCallCapSeconds = (float) $p['per_call_cap'];
            $this->summaryReserveSeconds = (float) $p['summary_reserve'];
        } else {
            $timeoutSeconds = is_numeric($timeoutOrProfile) ? (float) $timeoutOrProfile : 75.0;
            $this->perCallCapSeconds = 30.0;
            $this->summaryReserveSeconds = 10.0;
        }

        $this->deadline = microtime(true) + $timeoutSeconds;
        $this->transport = $transport;

        require_once __DIR__ . '/McpServer.class.php';
        $this->mcpServer = new McpServer();
        $this->mcpServer->initForUser($userId, $groupId);
    }

    public function setTransport(GeminiTransport $transport): self
    {
        $this->transport = $transport;
        return $this;
    }

    public function getTransport(): GeminiTransport
    {
        if ($this->transport === null) {
            $this->transport = new CurlGeminiTransport();
        }
        return $this->transport;
    }

    public function setEventSink(?callable $sink): self
    {
        $this->eventSink = $sink;
        return $this;
    }

    public function setMaxTokensPerRun(int $maxTokens): self
    {
        $this->maxTokensPerRun = $maxTokens;
        return $this;
    }

    public function setReadOnly(bool $readOnly): self
    {
        $this->readOnly = $readOnly;
        return $this;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function setLockToken(?string $lockToken): self
    {
        $this->lockToken = $lockToken;
        return $this;
    }

    public function emitEvent(string $type, array $data): void
    {
        if ($this->lockToken !== null) {
            static $lastHeartbeat = 0;
            $now = time();
            if ($now - $lastHeartbeat >= 10) {
                $lastHeartbeat = $now;
                self::renewLock($this->userId, $this->lockToken, 45);
            }
        }

        if ($this->eventSink !== null) {
            $payload = array_merge(['type' => $type], $data);
            try {
                $reflection = new \ReflectionFunction($this->eventSink);
                if ($reflection->getNumberOfParameters() === 1) {
                    call_user_func($this->eventSink, $payload);
                } else {
                    call_user_func($this->eventSink, $type, $data);
                }
            } catch (\Throwable $e) {
                call_user_func($this->eventSink, $payload);
            }
        }
    }

    /**
     * Resolves Gemini API key from environment, configuration, or data store
     */
    public static function resolveApiKey(): string
    {
        // 1. Environment variable priority
        $apiKey = (string) (getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? ''));

        // 2. Registry configuration
        if (empty($apiKey)) {
            $googleConfig = Registry::get('google_config') ?? [];
            $apiKey = $googleConfig['gemini_api_key'] ?? '';
        }

        // 3. Local data settings fallback
        if (empty($apiKey)) {
            $settingsPath = root_dir . 'data' . DS . 'google_settings.json';
            if (file_exists($settingsPath)) {
                $raw = json_decode((string) file_get_contents($settingsPath), true);
                $apiKey = $raw['gemini_api_key'] ?? '';
            }
        }

        return $apiKey;
    }

    private static bool $jobTableEnsured = false;

    /**
     * Ensures the background job table exists
     */
    public static function ensureJobTable(): void
    {
        if (self::$jobTableEnsured) {
            return;
        }

        $db = Load::lib('Database');
        $adapter = (string) $db->getAdapterType();
        $isSqlite = (strpos($adapter, 'PDO_SQLITE') !== false);
        $pdo = $db->get_connection();

        if ($isSqlite) {
            $sql = "CREATE TABLE IF NOT EXISTS mod_agent_jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                job_id VARCHAR(64) NOT NULL UNIQUE,
                user_id INT NOT NULL,
                group_id INT NOT NULL,
                lang VARCHAR(10) NOT NULL DEFAULT 'en',
                prompt TEXT NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'queued',
                stop_reason VARCHAR(64) NULL,
                current_turn INT NOT NULL DEFAULT 0,
                progress_text VARCHAR(255) NULL,
                result_json TEXT NULL,
                tokens_used INT NOT NULL DEFAULT 0,
                worker_id VARCHAR(64) NULL,
                heartbeat_at DATETIME NULL,
                completed_at DATETIME NULL,
                request_id VARCHAR(64) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            );";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS mod_agent_jobs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                job_id VARCHAR(64) NOT NULL UNIQUE,
                user_id INT UNSIGNED NOT NULL,
                group_id INT UNSIGNED NOT NULL,
                lang VARCHAR(10) NOT NULL DEFAULT 'en',
                prompt TEXT NOT NULL,
                status ENUM('queued', 'running', 'awaiting_confirmation', 'done', 'failed', 'cancelled') NOT NULL DEFAULT 'queued',
                stop_reason VARCHAR(64) NULL,
                current_turn INT NOT NULL DEFAULT 0,
                progress_text VARCHAR(255) NULL,
                result_json MEDIUMTEXT NULL,
                tokens_used INT NOT NULL DEFAULT 0,
                worker_id VARCHAR(64) NULL,
                heartbeat_at DATETIME NULL,
                completed_at DATETIME NULL,
                request_id VARCHAR(64) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_user_status (user_id, status),
                INDEX idx_created_at (created_at),
                INDEX idx_worker_heartbeat (worker_id, heartbeat_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }

        try {
            if ($pdo instanceof \PDO) {
                $pdo->exec($sql);
                if ($isSqlite) {
                    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_agent_jobs_user_status ON mod_agent_jobs (user_id, status)");
                    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_agent_jobs_created_at ON mod_agent_jobs (created_at)");
                    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_agent_jobs_worker_heartbeat ON mod_agent_jobs (worker_id, heartbeat_at)");
                }
            } else {
                $db->query($sql);
                if ($isSqlite) {
                    $db->query("CREATE INDEX IF NOT EXISTS idx_agent_jobs_user_status ON mod_agent_jobs (user_id, status)");
                    $db->query("CREATE INDEX IF NOT EXISTS idx_agent_jobs_created_at ON mod_agent_jobs (created_at)");
                    $db->query("CREATE INDEX IF NOT EXISTS idx_agent_jobs_worker_heartbeat ON mod_agent_jobs (worker_id, heartbeat_at)");
                }
            }
            self::$jobTableEnsured = true;

            // Migration M-09: Add missing columns if upgrading an existing job table
            $alterCols = [
                'worker_id' => 'VARCHAR(64) NULL',
                'heartbeat_at' => 'DATETIME NULL',
                'completed_at' => 'DATETIME NULL',
                'request_id' => 'VARCHAR(64) NULL',
            ];
            $existingColumns = [];
            try {
                if ($pdo instanceof \PDO) {
                    if ($isSqlite) {
                        $stmt = $pdo->query("PRAGMA table_info(mod_agent_jobs)");
                        $colRows = $stmt ? ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
                        foreach ($colRows as $colRow) {
                            if (!empty($colRow['name'])) {
                                $existingColumns[strtolower($colRow['name'])] = true;
                            }
                        }
                    } else {
                        $stmt = $pdo->query("SHOW COLUMNS FROM mod_agent_jobs");
                        $colRows = $stmt ? ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
                        foreach ($colRows as $colRow) {
                            if (!empty($colRow['Field'])) {
                                $existingColumns[strtolower($colRow['Field'])] = true;
                            }
                        }
                    }
                } else {
                    if ($isSqlite) {
                        $db->fetch_array("PRAGMA table_info(mod_agent_jobs)");
                        foreach (($db->toArray() ?: []) as $colRow) {
                            if (!empty($colRow['name'])) {
                                $existingColumns[strtolower($colRow['name'])] = true;
                            }
                        }
                    } else {
                        $db->fetch_array("SHOW COLUMNS FROM mod_agent_jobs");
                        foreach (($db->toArray() ?: []) as $colRow) {
                            if (!empty($colRow['Field'])) {
                                $existingColumns[strtolower($colRow['Field'])] = true;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore inspection failure
            }

            foreach ($alterCols as $col => $colType) {
                if (isset($existingColumns[strtolower($col)])) {
                    continue;
                }
                try {
                    if ($pdo instanceof \PDO) {
                        $pdo->exec("ALTER TABLE mod_agent_jobs ADD COLUMN {$col} {$colType}");
                    } else {
                        $db->query("ALTER TABLE mod_agent_jobs ADD COLUMN {$col} {$colType}");
                    }
                } catch (\Throwable $ignored) {
                    // Column already exists
                }
            }
        } catch (\Throwable $e) {
            error_log('[AgentRunner] ensureJobTable failed: ' . $e->getMessage());
        }
    }

    /**
     * Migration M-09: Ensures the database-backed agent state table exists (§4 A4)
     */
    public static function ensureStateTable(): void
    {
        if (self::$stateTableEnsured) {
            return;
        }

        $db = Load::lib('Database');
        $adapter = (string) $db->getAdapterType();
        $isSqlite = (strpos($adapter, 'PDO_SQLITE') !== false);
        $pdo = $db->get_connection();

        try {
            if ($isSqlite) {
                $createTableSql = "CREATE TABLE IF NOT EXISTS mod_agent_state (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INT NOT NULL,
                    kind VARCHAR(32) NOT NULL,
                    state_key VARCHAR(64) NOT NULL,
                    payload_json TEXT NOT NULL,
                    expires_at DATETIME NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL
                )";
                $duplicateCleanupSql = "DELETE FROM mod_agent_state
                    WHERE id NOT IN (
                        SELECT MAX(id) FROM mod_agent_state GROUP BY user_id, kind, state_key
                    )";
                $uniqueIdxSql = "CREATE UNIQUE INDEX IF NOT EXISTS uk_agent_state_user_kind_key ON mod_agent_state (user_id, kind, state_key)";
                $expiresIdxSql = "CREATE INDEX IF NOT EXISTS idx_agent_state_expires ON mod_agent_state (expires_at)";

                if ($pdo instanceof \PDO) {
                    $pdo->exec($createTableSql);

                    $hasUniqueIndex = false;
                    $idxStmt = $pdo->query("PRAGMA index_list(mod_agent_state)");
                    $idxRows = $idxStmt ? ($idxStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
                    foreach ($idxRows as $idxRow) {
                        if (($idxRow['name'] ?? '') === 'uk_agent_state_user_kind_key') {
                            $hasUniqueIndex = true;
                            break;
                        }
                    }

                    if (!$hasUniqueIndex) {
                        $pdo->exec($duplicateCleanupSql);
                    }
                    $pdo->exec($uniqueIdxSql);
                    $pdo->exec($expiresIdxSql);
                } else {
                    $db->query($createTableSql);
                    $db->query($duplicateCleanupSql);
                    $db->query($uniqueIdxSql);
                    $db->query($expiresIdxSql);
                }
            } else {
                $sql = "CREATE TABLE IF NOT EXISTS mod_agent_state (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    kind VARCHAR(32) NOT NULL,
                    state_key VARCHAR(64) NOT NULL,
                    payload_json MEDIUMTEXT NOT NULL,
                    expires_at DATETIME NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    UNIQUE KEY uk_user_kind_key (user_id, kind, state_key),
                    INDEX idx_expires (expires_at),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

                if ($pdo instanceof \PDO) {
                    $pdo->exec($sql);
                } else {
                    $db->query($sql);
                }
            }

            self::$stateTableEnsured = true;
        } catch (\Throwable $e) {
            error_log('[AgentRunner] ensureStateTable failed: ' . $e->getMessage());
        }
    }

    /**
     * Sets a state record in mod_agent_state with optional TTL
     */
    public static function setState(int $userId, string $kind, string $stateKey, array $payload, ?int $ttlSeconds = null): bool
    {
        self::ensureStateTable();
        $db = Load::lib('Database');
        $now = date('Y-m-d H:i:s');
        $expiresAt = ($ttlSeconds !== null) ? date('Y-m-d H:i:s', time() + $ttlSeconds) : null;
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $safeUserId = (int) $userId;
        $adapter = (string) $db->getAdapterType();
        $isSqlite = (strpos($adapter, 'PDO_SQLITE') !== false);

        $pdo = $db->get_connection();
        if ($pdo instanceof \PDO) {
            try {
                if ($isSqlite) {
                    $stmt = $pdo->prepare("INSERT INTO mod_agent_state (user_id, kind, state_key, payload_json, expires_at, created_at, updated_at)
                            VALUES (:user_id, :kind, :state_key, :payload_json, :expires_at, :created_at, :updated_at)
                            ON CONFLICT(user_id, kind, state_key) DO UPDATE SET
                            payload_json = excluded.payload_json,
                            expires_at = excluded.expires_at,
                            updated_at = excluded.updated_at");
                } else {
                    $stmt = $pdo->prepare("INSERT INTO mod_agent_state (user_id, kind, state_key, payload_json, expires_at, created_at, updated_at)
                            VALUES (:user_id, :kind, :state_key, :payload_json, :expires_at, :created_at, :updated_at)
                            ON DUPLICATE KEY UPDATE
                            payload_json = VALUES(payload_json),
                            expires_at = VALUES(expires_at),
                            updated_at = VALUES(updated_at)");
                }
                return $stmt->execute([
                    ':user_id' => $safeUserId,
                    ':kind' => $kind,
                    ':state_key' => $stateKey,
                    ':payload_json' => $payloadJson,
                    ':expires_at' => $expiresAt,
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ]);
            } catch (\Throwable $e) {
                error_log('[AgentRunner] setState PDO failed: ' . $e->getMessage());
                return false;
            }
        }

        // Fallback for non-PDO adapters
        $safeKind = Obuilder::cc($kind);
        $safeKey = Obuilder::cc($stateKey);
        $quotedJson = $db->quote($payloadJson);
        $safeExpiresAt = ($expiresAt !== null) ? "'{$expiresAt}'" : "NULL";

        if ($isSqlite) {
            $sql = "INSERT INTO mod_agent_state (user_id, kind, state_key, payload_json, expires_at, created_at, updated_at)
                    VALUES ({$safeUserId}, '{$safeKind}', '{$safeKey}', {$quotedJson}, {$safeExpiresAt}, '{$now}', '{$now}')
                    ON CONFLICT(user_id, kind, state_key) DO UPDATE SET
                    payload_json = excluded.payload_json,
                    expires_at = excluded.expires_at,
                    updated_at = excluded.updated_at";
        } else {
            $sql = "INSERT INTO mod_agent_state (user_id, kind, state_key, payload_json, expires_at, created_at, updated_at)
                    VALUES ({$safeUserId}, '{$safeKind}', '{$safeKey}', {$quotedJson}, {$safeExpiresAt}, '{$now}', '{$now}')
                    ON DUPLICATE KEY UPDATE
                    payload_json = VALUES(payload_json),
                    expires_at = VALUES(expires_at),
                    updated_at = VALUES(updated_at)";
        }

        try {
            return (bool) $db->query($sql);
        } catch (\Throwable $e) {
            error_log('[AgentRunner] setState fallback failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets a state record from mod_agent_state; auto-purges if expired
     */
    public static function getState(int $userId, string $kind, string $stateKey): ?array
    {
        self::ensureStateTable();
        $db = Load::lib('Database');
        $now = date('Y-m-d H:i:s');
        $safeUserId = (int) $userId;

        $pdo = $db->get_connection();
        if ($pdo instanceof \PDO) {
            try {
                $stmt = $pdo->prepare("SELECT payload_json, expires_at FROM mod_agent_state
                        WHERE user_id = :user_id AND kind = :kind AND state_key = :state_key
                        LIMIT 1");
                $stmt->execute([
                    ':user_id' => $safeUserId,
                    ':kind' => $kind,
                    ':state_key' => $stateKey,
                ]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$row) {
                    return null;
                }
                if (!empty($row['expires_at']) && $row['expires_at'] < $now) {
                    self::deleteState($userId, $kind, $stateKey);
                    return null;
                }
                $rawJson = (string) $row['payload_json'];
                $decoded = json_decode($rawJson, true);
                if ($decoded === null && $rawJson !== '' && json_last_error() === JSON_ERROR_CTRL_CHAR) {
                    $cleaned = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $rawJson);
                    $decoded = json_decode((string) $cleaned, true);
                }
                return is_array($decoded) ? $decoded : null;
            } catch (\Throwable $e) {
                error_log('[AgentRunner] getState PDO failed: ' . $e->getMessage());
                return null;
            }
        }

        $safeKind = Obuilder::cc($kind);
        $safeKey = Obuilder::cc($stateKey);

        $sql = "SELECT payload_json, expires_at FROM mod_agent_state
                WHERE user_id = {$safeUserId} AND kind = '{$safeKind}' AND state_key = '{$safeKey}'
                LIMIT 1";

        try {
            $db->fetch_array($sql);
            $rows = $db->toArray() ?: [];
            if (empty($rows[0])) {
                return null;
            }
            $row = $rows[0];
            if (!empty($row['expires_at']) && $row['expires_at'] < $now) {
                self::deleteState($userId, $kind, $stateKey);
                return null;
            }
            $rawJson = (string) $row['payload_json'];
            $decoded = json_decode($rawJson, true);
            if ($decoded === null && $rawJson !== '' && json_last_error() === JSON_ERROR_CTRL_CHAR) {
                $cleaned = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $rawJson);
                $decoded = json_decode((string) $cleaned, true);
            }
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            error_log('[AgentRunner] getState failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Deletes a state record
     */
    public static function deleteState(int $userId, string $kind, string $stateKey): bool
    {
        self::ensureStateTable();
        $db = Load::lib('Database');
        $safeUserId = (int) $userId;

        $pdo = $db->get_connection();
        if ($pdo instanceof \PDO) {
            try {
                $stmt = $pdo->prepare("DELETE FROM mod_agent_state WHERE user_id = :user_id AND kind = :kind AND state_key = :state_key");
                return $stmt->execute([
                    ':user_id' => $safeUserId,
                    ':kind' => $kind,
                    ':state_key' => $stateKey,
                ]);
            } catch (\Throwable $e) {
                error_log('[AgentRunner] deleteState PDO failed: ' . $e->getMessage());
                return false;
            }
        }

        $safeKind = Obuilder::cc($kind);
        $safeKey = Obuilder::cc($stateKey);

        try {
            return (bool) $db->query(
                "DELETE FROM mod_agent_state WHERE user_id = {$safeUserId} AND kind = '{$safeKind}' AND state_key = '{$safeKey}'"
            );
        } catch (\Throwable $e) {
            error_log('[AgentRunner] deleteState failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Concurrency control: acquires an in-flight single-run lock for a user (§4 A6)
     * Returns a string lock token on success, or false if already locked.
     *
     * @return string|false
     */
    public static function acquireLock(int $userId, int $timeoutSeconds = 45)
    {
        self::ensureStateTable();
        $db = Load::lib('Database');
        $now = date('Y-m-d H:i:s');
        $safeUserId = (int) $userId;
        $adapter = (string) $db->getAdapterType();
        $isSqlite = (strpos($adapter, 'PDO_SQLITE') !== false);
        $pdo = $db->get_connection();

        $token = bin2hex(random_bytes(16));
        $ttl = max(15, $timeoutSeconds);
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
        $payloadJson = json_encode([
            'token' => $token,
            'locked_at' => $now,
            'timeout' => $timeoutSeconds,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($pdo instanceof \PDO) {
            try {
                // Purge expired locks first
                $purgeStmt = $pdo->prepare(
                    "DELETE FROM mod_agent_state WHERE user_id = :user_id AND kind = 'lock' AND state_key = 'active_run' AND expires_at < :now"
                );
                $purgeStmt->execute([
                    ':user_id' => $safeUserId,
                    ':now' => $now,
                ]);

                if ($isSqlite) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO mod_agent_state (user_id, kind, state_key, payload_json, expires_at, created_at, updated_at)
                         VALUES (:user_id, 'lock', 'active_run', :payload_json, :expires_at, :created_at, :updated_at)
                         ON CONFLICT(user_id, kind, state_key) DO UPDATE SET
                             payload_json = excluded.payload_json,
                             expires_at = excluded.expires_at,
                             updated_at = excluded.updated_at
                         WHERE mod_agent_state.expires_at < :now"
                    );
                    $ok = $stmt->execute([
                        ':user_id' => $safeUserId,
                        ':payload_json' => $payloadJson,
                        ':expires_at' => $expiresAt,
                        ':created_at' => $now,
                        ':updated_at' => $now,
                        ':now' => $now,
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT IGNORE INTO mod_agent_state (user_id, kind, state_key, payload_json, expires_at, created_at, updated_at)
                         VALUES (:user_id, 'lock', 'active_run', :payload_json, :expires_at, :created_at, :updated_at)"
                    );
                    $ok = $stmt->execute([
                        ':user_id' => $safeUserId,
                        ':payload_json' => $payloadJson,
                        ':expires_at' => $expiresAt,
                        ':created_at' => $now,
                        ':updated_at' => $now,
                    ]);
                }

                return ($ok && $stmt->rowCount() === 1) ? $token : false;
            } catch (\Throwable $e) {
                error_log('[AgentRunner] acquireLock PDO failed: ' . $e->getMessage());
                return false;
            }
        }

        // Purge expired locks first (non-PDO fallback)
        try {
            $db->query("DELETE FROM mod_agent_state WHERE user_id = {$safeUserId} AND kind = 'lock' AND state_key = 'active_run' AND expires_at < '{$now}'");
        } catch (\Throwable $e) {
            error_log('[AgentRunner] purge expired lock failed: ' . $e->getMessage());
        }

        // Check if there is already an active non-expired lock
        $existing = self::getState($userId, 'lock', 'active_run');
        if ($existing !== null) {
            return false;
        }

        $ok = self::setState($userId, 'lock', 'active_run', [
            'token' => $token,
            'locked_at' => $now,
            'timeout' => $timeoutSeconds,
        ], $ttl);

        return $ok ? $token : false;
    }

    /**
     * Renews an active run lock heartbeat
     */
    public static function renewLock(int $userId, string $token, int $extendSeconds = 45): bool
    {
        $existing = self::getState($userId, 'lock', 'active_run');
        if (!$existing || ($existing['token'] ?? '') !== $token) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        return self::setState($userId, 'lock', 'active_run', array_merge($existing, [
            'heartbeat_at' => $now,
        ]), $extendSeconds);
    }

    /**
     * Releases an active run lock. If token is provided, ensures only the owner can release.
     */
    public static function releaseLock(int $userId, ?string $token = null): bool
    {
        if ($token !== null) {
            $existing = self::getState($userId, 'lock', 'active_run');
            if ($existing && !empty($existing['token']) && $existing['token'] !== $token) {
                return false;
            }
        }
        return self::deleteState($userId, 'lock', 'active_run');
    }

    /**
     * Counts currently active agent runs across all users
     */
    public static function countActiveRuns(): int
    {
        self::ensureStateTable();
        $db = Load::lib('Database');
        $now = date('Y-m-d H:i:s');
        $pdo = $db->get_connection();
        if ($pdo instanceof \PDO) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM mod_agent_state WHERE kind = 'lock' AND (expires_at IS NULL OR expires_at > :now)");
                $stmt->execute([':now' => $now]);
                return (int) $stmt->fetchColumn();
            } catch (\Throwable $e) {
                return 0;
            }
        }
        $sql = "SELECT COUNT(*) FROM mod_agent_state WHERE kind = 'lock' AND (expires_at IS NULL OR expires_at > '{$now}')";
        try {
            $db->fetch_array($sql);
            $rows = $db->toArray() ?: [];
            if (empty($rows[0])) {
                return 0;
            }
            return (int) current($rows[0]);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * State accessors for active model, chat history, pending actions, and request deduplication
     */
    public static function getActiveModel(int $userId): ?string
    {
        $state = self::getState($userId, 'model_pin', 'default');
        return $state['model'] ?? null;
    }

    public static function setActiveModel(int $userId, string $model): void
    {
        // 10-minute TTL for model pinning (§3.3)
        self::setState($userId, 'model_pin', 'default', ['model' => $model], 600);
    }

    public static function getChatHistory(int $userId): array
    {
        $state = self::getState($userId, 'history', 'chat');
        return is_array($state) ? $state : [];
    }

    public static function setChatHistory(int $userId, array $history): void
    {
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }
        self::setState($userId, 'history', 'chat', $history, 86400 * 7);
    }

    public static function clearChatHistory(int $userId): void
    {
        self::deleteState($userId, 'history', 'chat');
        self::deleteState($userId, 'model_pin', 'default');
        self::clearPendingAction($userId);
    }

    public static function setPendingAction(int $userId, string $confirmationId, array $pendingAction): void
    {
        // TTL 15 minutes (§4 A4)
        self::setState($userId, 'pending_action', $confirmationId, $pendingAction, 900);
        self::setState($userId, 'pending_action', 'latest', ['id' => $confirmationId], 900);
    }

    public static function getPendingAction(int $userId, ?string $confirmationId = null): ?array
    {
        if ($confirmationId !== null && $confirmationId !== '') {
            $action = self::getState($userId, 'pending_action', $confirmationId);
            if ($action !== null) {
                return $action;
            }
        }
        $latest = self::getState($userId, 'pending_action', 'latest');
        if (!empty($latest['id']) && is_string($latest['id'])) {
            return self::getState($userId, 'pending_action', $latest['id']);
        }
        return null;
    }

    public static function clearPendingAction(int $userId, ?string $confirmationId = null): void
    {
        if ($confirmationId !== null) {
            self::deleteState($userId, 'pending_action', $confirmationId);
        }
        $latest = self::getState($userId, 'pending_action', 'latest');
        if (!empty($latest['id'])) {
            self::deleteState($userId, 'pending_action', (string) $latest['id']);
        }
        self::deleteState($userId, 'pending_action', 'latest');
    }

    public static function recordRequestId(int $userId, string $requestId, ?array $result = null): bool
    {
        // TTL 10 minutes (§4 A5)
        return self::setState($userId, 'idempotency', 'req:' . $requestId, $result ?? ['status' => 'in_progress'], 600);
    }

    public static function getRequestResult(int $userId, string $requestId): ?array
    {
        return self::getState($userId, 'idempotency', 'req:' . $requestId);
    }

    /**
     * Redacts secret parameters in preview logs & SSE streams (§4 B3)
     */
    public static function redactArgs(array $args): array
    {
        $redacted = [];
        foreach ($args as $k => $v) {
            if (preg_match('/(key|secret|token|password|credential)/i', (string) $k)) {
                $redacted[$k] = '***';
            } elseif (is_array($v)) {
                $redacted[$k] = self::redactArgs($v);
            } elseif (is_string($v) && mb_strlen($v) > 200) {
                $redacted[$k] = mb_substr($v, 0, 200) . '...';
            } else {
                $redacted[$k] = $v;
            }
        }
        return $redacted;
    }

    /**
     * Creates a new background agent job record
     */
    public static function createJob(int $userId, int $groupId, string $lang, string $prompt, ?string $requestId = null, ?array $history = null): string
    {
        self::ensureJobTable();
        $jobId = 'job_' . bin2hex(random_bytes(16));
        $now = date('Y-m-d H:i:s');

        $db = Load::lib('Database');
        $pdo = $db->get_connection();
        if ($pdo instanceof \PDO) {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO mod_agent_jobs (job_id, user_id, group_id, lang, prompt, status, request_id, created_at, updated_at)
                     VALUES (:job_id, :user_id, :group_id, :lang, :prompt, 'queued', :request_id, :created_at, :updated_at)"
                );
                $stmt->execute([
                    ':job_id' => $jobId,
                    ':user_id' => (int) $userId,
                    ':group_id' => (int) $groupId,
                    ':lang' => $lang,
                    ':prompt' => $prompt,
                    ':request_id' => $requestId,
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ]);
            } catch (\Throwable $e) {
                error_log('[AgentRunner] createJob PDO failed: ' . $e->getMessage());
            }
        } else {
            $safeJobId = Obuilder::cc($jobId);
            $safeReqId = ($requestId !== null) ? "'" . Obuilder::cc($requestId) . "'" : "NULL";
            $db->query(
                "INSERT INTO mod_agent_jobs (job_id, user_id, group_id, lang, prompt, status, request_id, created_at, updated_at)
                 VALUES ('{$safeJobId}', " . (int) $userId . ", " . (int) $groupId . ", '" . Obuilder::cc($lang) . "', '" . Obuilder::cc($prompt) . "', 'queued', {$safeReqId}, '{$now}', '{$now}')"
            );
        }

        if (!empty($history)) {
            self::setState($userId, 'history', 'job:' . $jobId, $history, 86400);
        }

        return $jobId;
    }

    /**
     * Fetches job status and payload
     */
    public static function getJob(string $jobId): ?array
    {
        self::ensureJobTable();
        $db = Load::lib('Database');
        $pdo = $db->get_connection();
        if ($pdo instanceof \PDO) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM mod_agent_jobs WHERE job_id = :job_id LIMIT 1");
                $stmt->execute([':job_id' => $jobId]);
                $job = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$job) {
                    return null;
                }
                if (!empty($job['result_json'])) {
                    $job['result'] = json_decode((string) $job['result_json'], true);
                }
                return $job;
            } catch (\Throwable $e) {
                error_log('[AgentRunner] getJob PDO failed: ' . $e->getMessage());
                return null;
            }
        }

        $db->fetch_array("SELECT * FROM mod_agent_jobs WHERE job_id = '" . Obuilder::cc($jobId) . "' LIMIT 1");
        $rows = $db->toArray() ?: [];
        if (empty($rows[0])) {
            return null;
        }
        $job = $rows[0];
        if (!empty($job['result_json'])) {
            $job['result'] = json_decode((string) $job['result_json'], true);
        }
        return $job;
    }

    /**
     * Cancels a running or queued job
     */
    public static function cancelJob(string $jobId, int $userId): bool
    {
        self::ensureJobTable();
        $now = date('Y-m-d H:i:s');
        $db = Load::lib('Database');
        $pdo = $db->get_connection();

        if ($pdo instanceof \PDO) {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE mod_agent_jobs SET status = 'cancelled', updated_at = :now, completed_at = :completed_at
                     WHERE job_id = :job_id AND user_id = :user_id AND status IN ('queued', 'running', 'awaiting_confirmation')"
                );
                $res = $stmt->execute([
                    ':now' => $now,
                    ':completed_at' => $now,
                    ':job_id' => $jobId,
                    ':user_id' => (int) $userId,
                ]);
            } catch (\Throwable $e) {
                error_log('[AgentRunner] cancelJob PDO failed: ' . $e->getMessage());
                $res = false;
            }
        } else {
            $safeJobId = Obuilder::cc($jobId);
            $res = (bool) $db->query(
                "UPDATE mod_agent_jobs SET status = 'cancelled', updated_at = '{$now}', completed_at = '{$now}'
                 WHERE job_id = '{$safeJobId}' AND user_id = " . (int) $userId . " AND status IN ('queued', 'running', 'awaiting_confirmation')"
            );
        }

        self::releaseLock($userId);
        return $res;
    }

    /**
     * Atomically claims the next queued job for a worker (§4 C1)
     */
    public static function claimNextJob(string $workerId): ?array
    {
        self::ensureJobTable();
        $db = Load::lib('Database');
        $now = date('Y-m-d H:i:s');
        $pdo = $db->get_connection();

        if ($pdo instanceof \PDO) {
            try {
                $stmt = $pdo->query("SELECT job_id FROM mod_agent_jobs WHERE status = 'queued' ORDER BY id ASC LIMIT 1");
                $row = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : false;
                if (empty($row['job_id'])) {
                    return null;
                }
                $jobId = (string) $row['job_id'];
                $claimStmt = $pdo->prepare(
                    "UPDATE mod_agent_jobs
                     SET status = 'running', worker_id = :worker_id, heartbeat_at = :heartbeat_at, updated_at = :updated_at
                     WHERE job_id = :job_id AND status = 'queued'"
                );
                $ok = $claimStmt->execute([
                    ':worker_id' => $workerId,
                    ':heartbeat_at' => $now,
                    ':updated_at' => $now,
                    ':job_id' => $jobId,
                ]);
                if (!$ok || $claimStmt->rowCount() !== 1) {
                    return null;
                }
                return self::getJob($jobId);
            } catch (\Throwable $e) {
                error_log('[AgentRunner] claimNextJob PDO failed: ' . $e->getMessage());
                return null;
            }
        }

        $db->fetch_array("SELECT job_id FROM mod_agent_jobs WHERE status = 'queued' ORDER BY id ASC LIMIT 1");
        $rows = $db->toArray() ?: [];
        if (empty($rows[0]['job_id'])) {
            return null;
        }
        $jobId = (string) $rows[0]['job_id'];

        $safeJobId = Obuilder::cc($jobId);
        $safeWorkerId = Obuilder::cc($workerId);

        $claimed = $db->query(
            "UPDATE mod_agent_jobs 
             SET status = 'running', worker_id = '{$safeWorkerId}', heartbeat_at = '{$now}', updated_at = '{$now}'
             WHERE job_id = '{$safeJobId}' AND status = 'queued'"
        );

        if (!$claimed || $db->affected_rows() !== 1) {
            return null;
        }

        return self::getJob($jobId);
    }

    /**
     * Reaps stale jobs that crashed or lost their worker (§4 C3)
     */
    public static function reapStaleJobs(int $timeoutSeconds = 120): int
    {
        self::ensureJobTable();
        $db = Load::lib('Database');
        $now = date('Y-m-d H:i:s');
        $staleThreshold = date('Y-m-d H:i:s', time() - $timeoutSeconds);
        $pdo = $db->get_connection();

        if ($pdo instanceof \PDO) {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE mod_agent_jobs
                     SET status = 'failed', stop_reason = 'worker_lost', updated_at = :now, completed_at = :completed_at
                     WHERE status = 'running' AND heartbeat_at < :stale_threshold"
                );
                $stmt->execute([
                    ':now' => $now,
                    ':completed_at' => $now,
                    ':stale_threshold' => $staleThreshold,
                ]);
                return $stmt->rowCount();
            } catch (\Throwable $e) {
                error_log('[AgentRunner] reapStaleJobs PDO failed: ' . $e->getMessage());
                return 0;
            }
        }

        $db->query(
            "UPDATE mod_agent_jobs
             SET status = 'failed', stop_reason = 'worker_lost', updated_at = '{$now}', completed_at = '{$now}'
             WHERE status = 'running' AND heartbeat_at < '{$staleThreshold}'"
        );

        return (int) $db->affected_rows();
    }

    /**
     * Executes a background job from CLI worker (§4 C2, C4)
     */
    public static function runJob(string $jobId, string $workerId = ''): bool
    {
        self::ensureJobTable();
        $job = self::getJob($jobId);
        if (!$job || !in_array($job['status'], ['queued', 'running', 'awaiting_confirmation'], true)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $db = Load::lib('Database');
        $pdo = $db->get_connection();
        $resolvedWorkerId = $workerId ?: ('worker_' . getmypid());
        $safeJobId = Obuilder::cc($jobId);
        $safeWorkerId = Obuilder::cc($resolvedWorkerId);

        if ($pdo instanceof \PDO) {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE mod_agent_jobs
                     SET status = 'running', worker_id = :worker_id, heartbeat_at = :heartbeat_at, updated_at = :updated_at
                     WHERE job_id = :job_id"
                );
                $stmt->execute([
                    ':worker_id' => $resolvedWorkerId,
                    ':heartbeat_at' => $now,
                    ':updated_at' => $now,
                    ':job_id' => $jobId,
                ]);
            } catch (\Throwable $e) {
                error_log('[AgentRunner] runJob init PDO failed: ' . $e->getMessage());
            }
        } else {
            $db->query(
                "UPDATE mod_agent_jobs 
                 SET status = 'running', worker_id = '{$safeWorkerId}', heartbeat_at = '{$now}', updated_at = '{$now}' 
                 WHERE job_id = '{$safeJobId}'"
            );
        }

        try {
            $runner = new self((int) $job['user_id'], (int) $job['group_id'], (string) $job['lang'], null, 'job');

            // Wire job event sink for throttled progress and heartbeat updates (§4 C2)
            $lastHeartbeat = 0.0;
            $runner->setEventSink(function (string $type, array $data) use ($jobId, $safeJobId, $db, $pdo, &$lastHeartbeat) {
                $now = microtime(true);
                if (($now - $lastHeartbeat) < 3.0 && $type !== 'final' && $type !== 'confirmation_required') {
                    return;
                }
                $lastHeartbeat = $now;
                $dateNow = date('Y-m-d H:i:s');
                $turn = (int) ($data['turn'] ?? 0);
                $progressMessage = '';
                if (!empty($data['tool'])) {
                    $progressMessage = 'Executing ' . $data['tool'];
                } elseif (!empty($data['phase'])) {
                    $progressMessage = 'Phase: ' . $data['phase'];
                }
                $trimmedProgress = mb_substr($progressMessage, 0, 255);
                if ($pdo instanceof \PDO) {
                    try {
                        $setClauses = ['heartbeat_at = :heartbeat_at', 'updated_at = :updated_at'];
                        $params = [
                            ':heartbeat_at' => $dateNow,
                            ':updated_at' => $dateNow,
                            ':job_id' => $jobId,
                        ];
                        if ($turn > 0) {
                            $setClauses[] = 'current_turn = :current_turn';
                            $params[':current_turn'] = $turn;
                        }
                        if ($trimmedProgress !== '') {
                            $setClauses[] = 'progress_text = :progress_text';
                            $params[':progress_text'] = $trimmedProgress;
                        }
                        $heartbeatStmt = $pdo->prepare(
                            "UPDATE mod_agent_jobs SET " . implode(', ', $setClauses) . " WHERE job_id = :job_id"
                        );
                        $heartbeatStmt->execute($params);
                    } catch (\Throwable $ignored) {
                    }
                } else {
                    $safeProgressMessage = Obuilder::cc($trimmedProgress);
                    $db->query(
                        "UPDATE mod_agent_jobs
                         SET heartbeat_at = '{$dateNow}', updated_at = '{$dateNow}'" .
                         ($turn > 0 ? ", current_turn = {$turn}" : "") .
                         ($safeProgressMessage !== '' ? ", progress_text = '{$safeProgressMessage}'" : "") .
                         " WHERE job_id = '{$safeJobId}'"
                    );
                }
            });

            // Pass saved history if available
            $jobHistory = self::getState((int) $job['user_id'], 'history', 'job:' . $jobId);
            if (!empty($jobHistory)) {
                $result = $runner->execute((string) $job['prompt'], $jobHistory);
            } else {
                $result = $runner->execute((string) $job['prompt']);
            }

            $status = 'done';
            $stopReason = $result['stop_reason'] ?? 'completed';

            if (!empty($result['requires_confirmation'])) {
                $status = 'awaiting_confirmation';
                $stopReason = 'awaiting_confirmation';
            } elseif (empty($result['success'])) {
                $status = 'failed';
                $stopReason = $result['stop_reason'] ?? 'execution_failed';
            }

            $now = date('Y-m-d H:i:s');
            $resJson = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $tokensUsed = (int) ($result['tokens_used'] ?? 0);
            $currentTurn = (int) ($result['current_turn'] ?? 0);

            $pdo = $db->get_connection();
            if ($pdo instanceof \PDO) {
                $stmt = $pdo->prepare("UPDATE mod_agent_jobs 
                     SET status = :status, stop_reason = :stop_reason, result_json = :result_json, 
                         tokens_used = :tokens_used, current_turn = :current_turn, completed_at = :completed_at, updated_at = :updated_at
                     WHERE job_id = :job_id");
                $stmt->execute([
                    ':status' => $status,
                    ':stop_reason' => $stopReason,
                    ':result_json' => $resJson,
                    ':tokens_used' => $tokensUsed,
                    ':current_turn' => $currentTurn,
                    ':completed_at' => $now,
                    ':updated_at' => $now,
                    ':job_id' => $jobId,
                ]);
            } else {
                $quotedResJson = $db->quote($resJson);
                $db->query(
                    "UPDATE mod_agent_jobs 
                     SET status = '{$status}', stop_reason = '{$stopReason}', result_json = {$quotedResJson}, 
                         tokens_used = {$tokensUsed}, current_turn = {$currentTurn}, completed_at = '{$now}', updated_at = '{$now}'
                     WHERE job_id = '{$safeJobId}'"
                );
            }

            if (!empty($job['request_id'])) {
                self::recordRequestId((int) $job['user_id'], (string) $job['request_id'], $result);
            }

            return true;
        } catch (\Throwable $e) {
            $now = date('Y-m-d H:i:s');
            $errResult = json_encode(['success' => false, 'error' => $e->getMessage()]);
            $pdo = $db->get_connection();
            if ($pdo instanceof \PDO) {
                $stmt = $pdo->prepare("UPDATE mod_agent_jobs 
                     SET status = 'failed', stop_reason = 'exception', result_json = :result_json, 
                         completed_at = :completed_at, updated_at = :updated_at
                     WHERE job_id = :job_id");
                $stmt->execute([
                    ':result_json' => $errResult,
                    ':completed_at' => $now,
                    ':updated_at' => $now,
                    ':job_id' => $jobId,
                ]);
            } else {
                $quotedErrResult = $db->quote($errResult);
                $db->query(
                    "UPDATE mod_agent_jobs 
                     SET status = 'failed', stop_reason = 'exception', result_json = {$quotedErrResult}, 
                         completed_at = '{$now}', updated_at = '{$now}'
                     WHERE job_id = '{$safeJobId}'"
                );
            }
            return false;
        }
    }

    /**
     * Compacts older conversation history turns to stay within token budgets while preserving function call invariants
     */
    public static function compactHistoryContents(array $contents): array
    {
        $total = count($contents);
        if ($total <= 6) {
            return $contents;
        }

        // Leave the most recent 4 turns untouched
        $cutoff = $total - 4;

        for ($i = 0; $i < $cutoff; $i++) {
            if (!isset($contents[$i]['parts']) || !is_array($contents[$i]['parts'])) {
                continue;
            }

            foreach ($contents[$i]['parts'] as &$part) {
                if (!empty($part['functionResponse']['response']['content'])) {
                    $c = $part['functionResponse']['response']['content'];
                    if (is_array($c) && count($c) > 5) {
                        $part['functionResponse']['response']['content'] = [
                            'summary' => 'Retrieved ' . count($c) . ' items (compacted)',
                            'preview' => array_slice($c, 0, 3),
                        ];
                    } elseif (is_string($c) && mb_strlen($c) > 600) {
                        $part['functionResponse']['response']['content'] = mb_substr($c, 0, 400) . '... [compacted]';
                    }
                }
            }
            unset($part);
        }

        return $contents;
    }

    /**
     * Normalizes request payload for Google Gemini API protocol buffer schema requirements.
     * Guarantees functionCall.args is serialized as an object ({}), never an empty list ([]).
     */
    public static function normalizePayloadForGemini(array $payload): array
    {
        if (!empty($payload['contents']) && is_array($payload['contents'])) {
            foreach ($payload['contents'] as &$content) {
                if (!empty($content['parts']) && is_array($content['parts'])) {
                    foreach ($content['parts'] as &$part) {
                        if (isset($part['functionCall'])) {
                            if (!isset($part['functionCall']['args']) || (is_array($part['functionCall']['args']) && empty($part['functionCall']['args']))) {
                                $part['functionCall']['args'] = (object) [];
                            }
                        }
                    }
                    unset($part);
                }
            }
            unset($content);
        }

        if (!empty($payload['tools']) && is_array($payload['tools'])) {
            foreach ($payload['tools'] as &$toolGroup) {
                if (!empty($toolGroup['functionDeclarations']) && is_array($toolGroup['functionDeclarations'])) {
                    foreach ($toolGroup['functionDeclarations'] as &$decl) {
                        if (isset($decl['parameters']['properties']) && is_array($decl['parameters']['properties']) && empty($decl['parameters']['properties'])) {
                            $decl['parameters']['properties'] = (object) [];
                        }
                    }
                    unset($decl);
                }
            }
            unset($toolGroup);
        }

        return $payload;
    }

    /**
     * Classifies prompt into relevant domains for dynamic tool pruning (Bulletin-scoped)
     */
    public function classifyIntentDomains(string $prompt): array
    {
        $promptLower = mb_strtolower($prompt, 'UTF-8');
        $matchedDomains = [];

        // Check if user specifically requested full access
        if (preg_match('/\b(hemû|hemu|all|tümü|her tişt)\b/u', $promptLower)) {
            return []; // Empty indicates all domains
        }

        foreach (self::INTENT_KEYWORDS as $domain => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($promptLower, $kw, 0, 'UTF-8') !== false) {
                    $matchedDomains[] = $domain;
                    break;
                }
            }
        }

        $matchedDomains = array_unique($matchedDomains);

        if (in_array('seo', $matchedDomains, true)) {
            $matchedDomains = array_values(array_unique(array_merge($matchedDomains, [
                'news',
                'publication',
                'headline',
                'announcement',
                'page',
                'faq',
                'redirect',
                'gallery',
                'attachment',
            ])));
        }

        // If specific matches found, return all matched domains without arbitrary truncation
        if (!empty($matchedDomains)) {
            return array_values($matchedDomains);
        }

        // Default fallback to core essential Bulletin domains
        return ['news', 'menu', 'page', 'settings'];
    }

    /**
     * Builds multi-lingual system instructions adapting to the user's language and active CMS context
     */
    private function buildSystemInstruction(): string
    {
        return "You are the administrative assistant of Amed CMS (Amed Bulletin).
Core directives:
1. Dynamic Conversational Language vs. Target CMS Record Language:
   - Always respond to the administrator in the EXACT SAME language that the user uses in their prompt or conversation (e.g. if the user addresses you in Kurmanji Kurdish, reply in standard Kurmanji Kurdish; if Turkish, reply in fluent Turkish; if English, reply in English).
   - Strictly separate your conversational language from the target CMS record language (`record.lang`). Every database field saved into a CMS record (`name`, `deck`, `summary`, `content`, `title`, `description`, `keywords`, `seo`, and attached `mod_faq` `question`/`answer`) MUST be written 100% in the target active site language (`record.lang` where `lang_status = 'y'`). Never write Kurmanji Kurdish text into a `lang = 'tr'` record or Turkish text into a `lang = 'ku'` record!
   - The current active administration panel language is '{$this->activeLang}'. When creating new records or querying existing records without an explicitly specified language, verify active site languages via `list_site_settings(active_only='y')` (`active_languages` / `default_language`) and use '{$this->activeLang}' by default if active. If the user requests content in an inactive language (`lang_status = 'n'`), halt and ask the user whether to publish in the active default language instead.
2. Language-Specific Terminology Conventions:
   - When communicating in Kurdish (Kurmancî): use standard Latin orthography; use 'nûjenkirin' for 'update', 'tikandin' for 'click', and 'lêke' for 'add' (e.g. 'gotarekê lêke', 'nûçeyekê lêke', 'pêşekekê lêke').
   - When communicating in Turkish: use standard Turkish administrative terminology (e.g. 'güncelle', 'tıkla', 'ekle').
   - When communicating in English: use clear, professional English (e.g. 'update', 'click', 'add').
3. System Tool Execution & Scope Boundaries:
   - You have official system authority to query and manage administrative data via Amed Bulletin MCP tools (`mod_news`, `mod_headline`, `mod_publication`, `mod_announcements`, `mod_page`, `mod_video`, `mod_gallery`, `mod_menu`, `mod_faq`, `mod_survey`, `mod_settings`, `mod_seo`, `mod_redirect`, `mod_acl`, `mod_popup`, `mod_slider`, `mod_attachment`, `mod_document`, `mod_imagecategories`).
   - You CANNOT generate raster images, draw pictures, or perform graphics rendering. If the administrator asks for image generation or visual asset drawing (e.g. 'resim oluştur', 'çiz', 'draw an image'), politely explain that Amed CMS does not have built-in raster image drawing capabilities and suggest uploading images via the Gallery/Media tools or setting up an image file. NEVER execute unrelated menu, news, or database mutations when asked for image generation!
   - If an administrator prompt is ambiguous, unclear, or does not explicitly specify targets: DO NOT guess, DO NOT extrapolate targets, and DO NOT execute mutations on unrelated items. Instead, ask a clear clarifying question first.
4. Mutation & Operational Safety:
   - Fulfill the administrator's request accurately and safely.
   - If an item position, placement, or ID is needed, first look it up with read tools (list_* or get_*).
   - NEVER make unilateral, unrequested changes to menus, categories, or newsroom content structures.
   - For ordering/sorting items, never delete other existing items! Simply assign the target order index. Never delete system records without explicit instructions!
   - Structural changes (reparenting, batch updates, deletions, site settings) require human confirmation before execution.
5. Strict Operational Truthfulness:
   - NEVER state or imply that you updated, moved, created, or deleted records if you only queried or listed them using read-only tools (like list_* or get_*).
   - Only confirm an action as completed if the actual mutating tool (update_*, create_*, delete_*, etc.) was successfully executed.
   - If you reach the turn limit or cannot complete all requested modifications, honestly state what was inspected, what was modified, and what remains pending. Never fabricate completion!
6. Summary and Communication:
   - After tools have executed, immediately provide a concise, polite, helpful summary to the administrator confirming the result in the user's conversational language.
7. Editorial Newsroom, SEO & Content Pipeline:
   - Grounded Research & Zero Hallucination: Before writing or optimizing news articles, publications, or pages, inspect existing records via get_*/list_* tools and verify facts. Never invent statistics, dates, quotes, or unverifiable claims.
   - Preservation Modes: Default to Surgical Enhancement (preserve valid existing text, facts, image, gallery_id, file_id, and video while expanding structure); use Clean-Slate Rewrite or Additive Append when requested by the administrator.
   - Semantic HTML & Depth: Structure articles with clean `<p>`, `<h2>`/`<h3>`, `<ul>`/`<ol>`, `<blockquote>`, and semantic `<table>` elements where appropriate, plus native `mod_faq` entries via `create_faq` when requested. Strictly forbid `<h1>`, inline `style=\"...\"`, `<style>`, or `<script>` tags inside body HTML.
   - Metadata & Slug Governance: Synchronize `summary` and `description` to 120-155 plain-text characters (valid range: 90-160 chars), keep `title` 45-60 characters, format slugs in Wikipedia style (preserving original alphabet casing and replacing spaces with underscores), and ensure HTTP 301 redirects are recorded in `mod_redirect` whenever a slug changes.";
    }

    /**
     * Identifies whether a tool executes a state-modifying action vs a read-only query
     */
    public static function isMutatingTool(string $toolName): bool
    {
        return str_starts_with($toolName, 'create_')
            || str_starts_with($toolName, 'update_')
            || str_starts_with($toolName, 'delete_')
            || str_starts_with($toolName, 'clear_')
            || str_starts_with($toolName, 'set_')
            || str_starts_with($toolName, 'batch_')
            || str_starts_with($toolName, 'upload_')
            || str_starts_with($toolName, 'rename_')
            || str_starts_with($toolName, 'revoke_')
            || str_starts_with($toolName, 'reparent_')
            || str_starts_with($toolName, 'move_');
    }

    /**
     * Determines whether a tool call requires human-in-the-loop confirmation before execution.
     * Enforces default-deny for unknown tools and strict gates on destructive/structural/bulk mutations.
     */
    public function requiresConfirmation(string $toolName, array $toolArgs = []): bool
    {
        // 1. Pure read-only queries never require confirmation
        if (!self::isMutatingTool($toolName)) {
            if (str_starts_with($toolName, 'list_')
                || str_starts_with($toolName, 'get_')
                || str_starts_with($toolName, 'search_')
                || str_starts_with($toolName, 'find_')
                || str_starts_with($toolName, 'view_')) {
                return false;
            }
        }

        // 2. Explicitly destructive or clear prefixes always require confirmation
        if (str_starts_with($toolName, 'delete_')
            || str_starts_with($toolName, 'clear_')
            || str_starts_with($toolName, 'revoke_')
            || str_starts_with($toolName, 'truncate_')
            || str_starts_with($toolName, 'drop_')) {
            return true;
        }

        // 3. Known high-impact or structural mutations
        if (in_array($toolName, [
            'reparent_menu_items_by_name',
            'batch_update_menu_items',
            'update_site_settings',
            'update_acl',
            'update_seo_settings',
            'update_google_settings',
            'set_default_survey',
            'update_redirect',
        ], true)) {
            // Note: if reparent is purely a dry_run preview, it does not mutate and can proceed without confirmation
            if (!empty($toolArgs['dry_run'])) {
                return false;
            }
            return true;
        }

        // 4. Check tool declaration annotations in McpServer
        $toolDef = $this->mcpServer->getToolDefinition($toolName);
        if ($toolDef !== null && !empty($toolDef['annotations']['destructiveHint'])) {
            return true;
        }

        // 5. Bulk/batch arguments always require confirmation
        if (!empty($toolArgs['items']) && is_array($toolArgs['items'])) {
            return true;
        }
        if (!empty($toolArgs['ids']) && is_array($toolArgs['ids'])) {
            return true;
        }
        if (!empty($toolArgs['source_names'])) {
            return true;
        }
        if (isset($toolArgs['scope']) && $toolArgs['scope'] === 'all') {
            return true;
        }

        // 6. Structural tree mutations (e.g. reparenting via parent_id or changing roles)
        if (array_key_exists('parent_id', $toolArgs) || array_key_exists('role_id', $toolArgs)) {
            return true;
        }

        // 7. Default-deny: if the tool is mutating and NOT recognized in the MCP server, require confirmation
        if (self::isMutatingTool($toolName) && $toolDef === null) {
            return true;
        }

        return false;
    }

    /**
     * Executes the agent loop for a user prompt
     */
    public function execute(string $prompt, array $history = [], array $options = []): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => $this->getLocalizedMessage('api_key_missing')
            ];
        }

        require_once __DIR__ . '/McpAuditLogger.class.php';

        // Check daily token budget
        if (!McpAuditLogger::checkDailyTokenBudget($this->userId, $this->groupId)) {
            return [
                'success' => false,
                'error' => $this->getLocalizedMessage('daily_budget_exceeded')
            ];
        }

        // Support read-only mode option
        if (!empty($options['read_only'])) {
            $this->readOnly = true;
        }

        // Apply dynamic tool pruning
        $targetDomains = $this->classifyIntentDomains($prompt);
        $geminiTools = $this->mcpServer->getToolsForGemini(!empty($targetDomains) ? $targetDomains : null, $this->readOnly);

        // Build conversation messages array
        $contents = [];
        foreach ($history as $msg) {
            if (isset($msg['role'], $msg['text']) && is_string($msg['text']) && trim($msg['text']) !== '') {
                $contents[] = [
                    'role' => ($msg['role'] === 'user') ? 'user' : 'model',
                    'parts' => [
                        ['text' => trim($msg['text'])]
                    ]
                ];
            }
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $prompt]
            ]
        ];

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $this->buildSystemInstruction()]
                ]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 8192,
            ]
        ];

        if (!empty($geminiTools)) {
            $payload['tools'] = [
                ['functionDeclarations' => $geminiTools]
            ];
        }

        // Multi-turn tool execution loop (§3.1, §4 B2, B3)
        $maxToolTurns = 30;
        $summaryReserveSeconds = $this->summaryReserveSeconds;
        $currentTurn = 0;
        $replyText = '';
        $usedModel = $this->activeModel ?: 'gemini-3.8-flash';
        $lastError = '';
        $stopReason = 'completed';

        while ($currentTurn < $maxToolTurns) {
            $remaining = $this->deadline - microtime(true);
            if ($remaining <= $summaryReserveSeconds) {
                $stopReason = 'time_budget_exhausted';
                $lastError = $this->getLocalizedMessage('time_budget_exhausted');
                break;
            }

            // A7: Token ceiling check
            if ($this->totalTokensUsed >= $this->maxTokensPerRun) {
                $stopReason = 'token_budget_exhausted';
                $lastError = $this->getLocalizedMessage('token_budget_exhausted');
                break;
            }

            // A5: Disconnect check between turns
            if (connection_aborted()) {
                $stopReason = 'client_disconnected';
                break;
            }

            $currentTurn++;
            $this->emitEvent('status', [
                'phase' => 'thinking',
                'turn' => $currentTurn,
                'model' => $usedModel,
            ]);

            $payload['contents'] = self::compactHistoryContents($payload['contents']);
            $turnRes = $this->callGeminiApi($payload);

            if (!$turnRes['success']) {
                $stopReason = $turnRes['stop_reason'] ?? 'api_error';
                $lastError = $turnRes['error'];
                break;
            }

            $usedModel = $turnRes['model'];
            $resData = $turnRes['data'];
            $this->trackTokenUsage($resData, $usedModel);

            $candidate = $resData['candidates'][0] ?? null;
            $parts = $candidate['content']['parts'] ?? [];

            // Detect function calls
            $functionCalls = [];
            foreach ($parts as $p) {
                if (!empty($p['functionCall'])) {
                    $functionCalls[] = $p['functionCall'];
                }
            }

            if (!empty($functionCalls)) {
                // Human-in-the-loop confirmation for destructive and sensitive operations
                $destructivePending = null;

                foreach ($functionCalls as $fc) {
                    $toolName = (string) ($fc['name'] ?? '');
                    $toolArgs = is_array($fc['args'] ?? null) ? $fc['args'] : [];

                    if ($this->requiresConfirmation($toolName, $toolArgs)) {
                        $destructivePending = [
                            'name' => $toolName,
                            'args' => $toolArgs,
                            'call' => $fc,
                        ];
                        break;
                    }
                }

                if ($destructivePending !== null) {
                    $confirmationId = bin2hex(random_bytes(16));
                    $pendingActionData = [
                        'id' => $confirmationId,
                        'user_id' => $this->userId,
                        'tool_name' => $destructivePending['name'],
                        'tool_args' => $destructivePending['args'],
                        'call' => $destructivePending['call'],
                        'parts' => $parts,
                        'payload' => $payload,
                        'current_turn' => $currentTurn,
                        'model' => $usedModel,
                        'prompt' => $prompt,
                        'executed_tools' => $this->executedTools,
                        'created_at' => time(),
                    ];
                    self::setPendingAction($this->userId, $confirmationId, $pendingActionData);

                    $confirmationResult = [
                        'success' => true,
                        'requires_confirmation' => true,
                        'confirmation_id' => $confirmationId,
                        'pending_tool' => $destructivePending['name'],
                        'pending_args' => self::redactArgs($destructivePending['args']),
                        'prompt' => $prompt,
                        'reply' => $this->getConfirmationMessage($destructivePending['name'], false),
                        'model' => $usedModel,
                        'executed_tools' => $this->executedTools,
                        'stop_reason' => 'awaiting_confirmation',
                        'current_turn' => $currentTurn,
                    ];

                    $this->emitEvent('confirmation_required', $confirmationResult);
                    $this->emitEvent('done', $confirmationResult);

                    return $confirmationResult;
                }

                $payload['contents'][] = [
                    'role' => 'model',
                    'parts' => $parts
                ];

                $this->emitEvent('status', [
                    'phase' => 'executing_tool',
                    'turn' => $currentTurn,
                    'model' => $usedModel,
                ]);

                $functionResponseParts = [];
                foreach ($functionCalls as $fc) {
                    $toolName = (string) ($fc['name'] ?? '');
                    $toolArgs = is_array($fc['args'] ?? null) ? $fc['args'] : [];

                    $this->executedTools[] = $toolName;
                    $callId = $fc['id'] ?? ('call_' . bin2hex(random_bytes(6)));

                    $this->emitEvent('tool_call', [
                        'call_id' => $callId,
                        'tool' => $toolName,
                        'args_preview' => self::redactArgs($toolArgs),
                    ]);

                    if ($this->readOnly && self::isMutatingTool($toolName)) {
                        $toolResult = [
                            'isError' => true,
                            'error' => 'Operation blocked: Agent is running in read-only mode.'
                        ];
                    } else {
                        $toolResult = $this->mcpServer->executeToolDirect($toolName, $toolArgs);
                    }

                    $summary = is_string($toolResult) ? $toolResult : json_encode($toolResult, JSON_UNESCAPED_UNICODE);
                    if (mb_strlen($summary) > 300) {
                        $summary = mb_substr($summary, 0, 300) . '...';
                    }

                    $this->emitEvent('tool_result', [
                        'call_id' => $callId,
                        'tool' => $toolName,
                        'success' => !empty($toolResult['success']) || (!isset($toolResult['isError']) && !isset($toolResult['error'])),
                        'summary' => $summary,
                    ]);

                    $frItem = [
                        'name' => $toolName,
                        'response' => [
                            'content' => $toolResult
                        ]
                    ];
                    if (!empty($fc['id'])) {
                        $frItem['id'] = $fc['id'];
                    }

                    $functionResponseParts[] = [
                        'functionResponse' => $frItem
                    ];
                }

                $payload['contents'][] = [
                    'role' => 'user',
                    'parts' => $functionResponseParts
                ];

                continue;
            }

            // Extract candidate text
            $candidateText = '';
            foreach ($parts as $p) {
                if (!empty($p['thought'])) {
                    continue;
                }
                if (!empty($p['text'])) {
                    $candidateText .= $p['text'];
                }
            }
            if ($candidateText === '' && !empty($parts[0]['text'])) {
                $candidateText = $parts[0]['text'];
            }

            if ($candidateText !== '') {
                $replyText = $candidateText;
                $stopReason = 'completed';
                break;
            }
        }

        if ($currentTurn >= $maxToolTurns && $replyText === '') {
            $stopReason = 'turn_limit';
        }

        // Generate summary if tools executed but no text produced yet
        if ($replyText === '' && !empty($this->executedTools)) {
            $this->emitEvent('status', [
                'phase' => 'synthesizing',
                'turn' => $currentTurn,
                'model' => $usedModel,
            ]);

            $mutatingTools = array_values(array_filter($this->executedTools, [self::class, 'isMutatingTool']));

            $summaryPayload = $payload;
            unset($summaryPayload['tools']);
            $summaryPayload['is_summary_call'] = true;

            if (!empty($mutatingTools)) {
                $instruction = "The requested system modifications were executed using: " . implode(', ', array_unique($mutatingTools)) . ". ";
                if ($stopReason === 'time_budget_exhausted') {
                    $instruction .= "However, the operation ran close to the time limit before completing further steps. ";
                } elseif ($stopReason === 'turn_limit') {
                    $instruction .= "However, the operation reached the maximum step limit (30) before completing further steps. ";
                }
                $instruction .= "Please provide a brief, clear, accurate summary to the administrator confirming what was completed and what remains pending, using the same language as the user's conversation.";

                $summaryPayload['contents'][] = [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $instruction]
                    ]
                ];
            } else {
                $instruction = "The assistant only inspected or queried information using read-only tools (" . implode(', ', array_unique($this->executedTools)) . ") and ";
                if ($stopReason === 'time_budget_exhausted') {
                    $instruction .= "the time limit was reached before making any modifications or updates. ";
                } elseif ($stopReason === 'api_error') {
                    $instruction .= "an API communication error occurred before making any modifications or updates. ";
                } else {
                    $instruction .= "reached the execution turn limit before making any modifications or updates. ";
                }
                $instruction .= "Please inform the administrator honestly and clearly in their conversation language that only inspection was performed, no modifications were saved or applied yet, and summarize what was found. Do NOT claim the request was completed!";

                $summaryPayload['contents'][] = [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $instruction]
                    ]
                ];
            }

            $summaryRes = $this->callGeminiApi($summaryPayload);
            if ($summaryRes['success']) {
                $sParts = $summaryRes['data']['candidates'][0]['content']['parts'] ?? [];
                foreach ($sParts as $sp) {
                    if (!empty($sp['text'])) {
                        $replyText .= $sp['text'];
                    }
                }
            }
            if ($replyText === '') {
                if (!empty($mutatingTools)) {
                    $replyText = $this->getLocalizedMessage('tools_executed_success', ['tools' => implode(', ', array_unique($mutatingTools))]);
                } else {
                    if ($stopReason === 'time_budget_exhausted') {
                        $replyText = $this->getLocalizedMessage('tools_read_only_time_exhausted', ['tools' => implode(', ', array_unique($this->executedTools))]);
                    } elseif ($stopReason === 'api_error') {
                        $replyText = $this->getLocalizedMessage('tools_read_only_api_error', ['tools' => implode(', ', array_unique($this->executedTools))]);
                    } else {
                        $replyText = $this->getLocalizedMessage('tools_read_only_incomplete', ['tools' => implode(', ', array_unique($this->executedTools))]);
                    }
                }
            }
        }

        if ($replyText === '') {
            $errResult = [
                'success' => false,
                'error' => !empty($lastError) ? $lastError : $this->getLocalizedMessage('no_response'),
                'stop_reason' => $stopReason,
                'current_turn' => $currentTurn,
            ];
            $this->emitEvent('error', [
                'code' => $stopReason,
                'message' => $errResult['error'],
            ]);
            $this->emitEvent('done', $errResult);
            return $errResult;
        }

        $finalResult = [
            'success' => true,
            'reply' => $replyText,
            'model' => $usedModel,
            'executed_tools' => $this->executedTools,
            'tokens_used' => $this->totalTokensUsed,
            'pruned_domains' => $targetDomains,
            'stop_reason' => $stopReason,
            'current_turn' => $currentTurn,
        ];
        if ($stopReason === 'time_budget_exhausted') {
            $finalResult['continuation_available'] = true;
        }

        $this->emitEvent('done', $finalResult);
        return $finalResult;
    }

    /**
     * Resumes the agent loop after human confirmation of a pending tool call (§4 C4)
     */
    public function resumeConfirmation(array $pendingAction): array
    {
        $payload = $pendingAction['payload'];
        $parts = $pendingAction['parts'];
        $toolName = (string) $pendingAction['tool_name'];
        $toolArgs = is_array($pendingAction['tool_args']) ? $pendingAction['tool_args'] : [];
        $currentTurn = (int) ($pendingAction['current_turn'] ?? 1);
        $this->executedTools = is_array($pendingAction['executed_tools'] ?? null) ? $pendingAction['executed_tools'] : [];
        $usedModel = (string) ($pendingAction['model'] ?? ($this->activeModel ?: 'gemini-3.8-flash'));
        $prompt = (string) ($pendingAction['prompt'] ?? '');

        // 1. Add model turn requesting this action
        $payload['contents'][] = [
            'role' => 'model',
            'parts' => $parts
        ];

        $this->emitEvent('status', [
            'phase' => 'executing_tool',
            'turn' => $currentTurn,
            'model' => $usedModel,
        ]);

        // 2. Execute approved tool with exact saved args
        $this->executedTools[] = $toolName;
        $callId = $pendingAction['call']['id'] ?? ('call_' . bin2hex(random_bytes(6)));

        $this->emitEvent('tool_call', [
            'call_id' => $callId,
            'tool' => $toolName,
            'args_preview' => self::redactArgs($toolArgs),
        ]);

        $toolResult = $this->mcpServer->executeToolDirect($toolName, $toolArgs);

        $summary = is_string($toolResult) ? $toolResult : json_encode($toolResult, JSON_UNESCAPED_UNICODE);
        if (mb_strlen($summary) > 300) {
            $summary = mb_substr($summary, 0, 300) . '...';
        }

        $this->emitEvent('tool_result', [
            'call_id' => $callId,
            'tool' => $toolName,
            'success' => !empty($toolResult['success']) || (!isset($toolResult['isError']) && !isset($toolResult['error'])),
            'summary' => $summary,
        ]);

        $frItem = [
            'name' => $toolName,
            'response' => [
                'content' => $toolResult
            ]
        ];
        if (!empty($pendingAction['call']['id'])) {
            $frItem['id'] = $pendingAction['call']['id'];
        }

        $payload['contents'][] = [
            'role' => 'user',
            'parts' => [
                ['functionResponse' => $frItem]
            ]
        ];

        // Clear resumed pending action from state store
        self::clearPendingAction($this->userId, $pendingAction['id'] ?? null);

        // 3. Resume the loop from current turn
        $maxToolTurns = 30;
        $summaryReserveSeconds = $this->summaryReserveSeconds;
        $replyText = '';
        $lastError = '';
        $stopReason = 'completed';

        while ($currentTurn < $maxToolTurns) {
            $remaining = $this->deadline - microtime(true);
            if ($remaining <= $summaryReserveSeconds) {
                $stopReason = 'time_budget_exhausted';
                $lastError = $this->getLocalizedMessage('time_budget_exhausted');
                break;
            }

            if ($this->totalTokensUsed >= $this->maxTokensPerRun) {
                $stopReason = 'token_budget_exhausted';
                $lastError = $this->getLocalizedMessage('token_budget_exhausted');
                break;
            }

            if (connection_aborted()) {
                $stopReason = 'client_disconnected';
                break;
            }

            $currentTurn++;
            $this->emitEvent('status', [
                'phase' => 'thinking',
                'turn' => $currentTurn,
                'model' => $usedModel,
            ]);

            $payload['contents'] = self::compactHistoryContents($payload['contents']);
            $turnRes = $this->callGeminiApi($payload);

            if (!$turnRes['success']) {
                $stopReason = $turnRes['stop_reason'] ?? 'api_error';
                $lastError = $turnRes['error'];
                break;
            }

            $usedModel = $turnRes['model'];
            $resData = $turnRes['data'];
            $this->trackTokenUsage($resData, $usedModel);

            $candidate = $resData['candidates'][0] ?? null;
            $parts = $candidate['content']['parts'] ?? [];

            // Detect any subsequent function calls
            $functionCalls = [];
            foreach ($parts as $p) {
                if (!empty($p['functionCall'])) {
                    $functionCalls[] = $p['functionCall'];
                }
            }

            if (!empty($functionCalls)) {
                $destructivePending = null;
                foreach ($functionCalls as $fc) {
                    $tName = (string) ($fc['name'] ?? '');
                    $tArgs = is_array($fc['args'] ?? null) ? $fc['args'] : [];

                    if ($this->requiresConfirmation($tName, $tArgs)) {
                        $destructivePending = [
                            'name' => $tName,
                            'args' => $tArgs,
                            'call' => $fc,
                        ];
                        break;
                    }
                }

                if ($destructivePending !== null) {
                    $confirmationId = bin2hex(random_bytes(16));
                    $pendingActionData = [
                        'id' => $confirmationId,
                        'user_id' => $this->userId,
                        'tool_name' => $destructivePending['name'],
                        'tool_args' => $destructivePending['args'],
                        'call' => $destructivePending['call'],
                        'parts' => $parts,
                        'payload' => $payload,
                        'current_turn' => $currentTurn,
                        'model' => $usedModel,
                        'prompt' => $prompt,
                        'executed_tools' => $this->executedTools,
                        'created_at' => time(),
                    ];
                    self::setPendingAction($this->userId, $confirmationId, $pendingActionData);

                    $confirmationResult = [
                        'success' => true,
                        'requires_confirmation' => true,
                        'confirmation_id' => $confirmationId,
                        'pending_tool' => $destructivePending['name'],
                        'pending_args' => self::redactArgs($destructivePending['args']),
                        'prompt' => $prompt,
                        'reply' => $this->getConfirmationMessage($destructivePending['name'], true),
                        'model' => $usedModel,
                        'executed_tools' => $this->executedTools,
                        'stop_reason' => 'awaiting_confirmation',
                        'current_turn' => $currentTurn,
                    ];

                    $this->emitEvent('confirmation_required', $confirmationResult);
                    $this->emitEvent('done', $confirmationResult);

                    return $confirmationResult;
                }

                $payload['contents'][] = [
                    'role' => 'model',
                    'parts' => $parts
                ];

                $this->emitEvent('status', [
                    'phase' => 'executing_tool',
                    'turn' => $currentTurn,
                    'model' => $usedModel,
                ]);

                $functionResponseParts = [];
                foreach ($functionCalls as $fc) {
                    $tName = (string) ($fc['name'] ?? '');
                    $tArgs = is_array($fc['args'] ?? null) ? $fc['args'] : [];

                    $this->executedTools[] = $tName;
                    $callId = $fc['id'] ?? ('call_' . bin2hex(random_bytes(6)));

                    $this->emitEvent('tool_call', [
                        'call_id' => $callId,
                        'tool' => $tName,
                        'args_preview' => self::redactArgs($tArgs),
                    ]);

                    if ($this->readOnly && self::isMutatingTool($tName)) {
                        $toolResult = [
                            'isError' => true,
                            'error' => 'Operation blocked: Agent is running in read-only mode.'
                        ];
                    } else {
                        $toolResult = $this->mcpServer->executeToolDirect($tName, $tArgs);
                    }

                    $summary = is_string($toolResult) ? $toolResult : json_encode($toolResult, JSON_UNESCAPED_UNICODE);
                    if (mb_strlen($summary) > 300) {
                        $summary = mb_substr($summary, 0, 300) . '...';
                    }

                    $this->emitEvent('tool_result', [
                        'call_id' => $callId,
                        'tool' => $tName,
                        'success' => !empty($toolResult['success']) || (!isset($toolResult['isError']) && !isset($toolResult['error'])),
                        'summary' => $summary,
                    ]);

                    $frItem = [
                        'name' => $tName,
                        'response' => [
                            'content' => $toolResult
                        ]
                    ];
                    if (!empty($fc['id'])) {
                        $frItem['id'] = $fc['id'];
                    }

                    $functionResponseParts[] = [
                        'functionResponse' => $frItem
                    ];
                }

                $payload['contents'][] = [
                    'role' => 'user',
                    'parts' => $functionResponseParts
                ];

                continue;
            }

            // Extract candidate text
            $candidateText = '';
            foreach ($parts as $p) {
                if (!empty($p['thought'])) {
                    continue;
                }
                if (!empty($p['text'])) {
                    $candidateText .= $p['text'];
                }
            }
            if ($candidateText === '' && !empty($parts[0]['text'])) {
                $candidateText = $parts[0]['text'];
            }

            if ($candidateText !== '') {
                $replyText = $candidateText;
                $stopReason = 'completed';
                break;
            }
        }

        if ($currentTurn >= $maxToolTurns && $replyText === '') {
            $stopReason = 'turn_limit';
        }

        if ($replyText === '' && !empty($this->executedTools)) {
            $replyText = $this->getLocalizedMessage('action_confirmed_success', ['tool' => $toolName]);
        }

        if ($replyText === '') {
            $errResult = [
                'success' => false,
                'error' => !empty($lastError) ? $lastError : $this->getLocalizedMessage('no_response'),
                'stop_reason' => $stopReason,
                'current_turn' => $currentTurn,
            ];
            $this->emitEvent('error', [
                'code' => $stopReason,
                'message' => $errResult['error'],
            ]);
            $this->emitEvent('done', $errResult);
            return $errResult;
        }

        $finalResult = [
            'success' => true,
            'reply' => $replyText,
            'model' => $usedModel,
            'executed_tools' => $this->executedTools,
            'tokens_used' => $this->totalTokensUsed,
            'stop_reason' => $stopReason,
            'current_turn' => $currentTurn,
        ];
        if ($stopReason === 'time_budget_exhausted') {
            $finalResult['continuation_available'] = true;
        }

        $this->emitEvent('done', $finalResult);
        return $finalResult;
    }

    /**
     * Executes resilient Gemini API calls with automated model fallback and deadline awareness (§3.2, §3.3)
     */
    private function callGeminiApi(array $payload): array
    {
        $payload = self::normalizePayloadForGemini($payload);

        $candidateModels = self::FLASH_MODELS;
        if (!empty($this->activeModel) && in_array($this->activeModel, $candidateModels, true)) {
            // Prioritize the known working model from previous turns
            $modelsToTry = array_unique(array_merge([$this->activeModel], $candidateModels));
        } else {
            $modelsToTry = $candidateModels;
        }

        // Deprioritize or exclude models that failed during this run
        $healthyModels = array_values(array_filter($modelsToTry, fn($m) => empty($this->failedModels[$m])));
        if (!empty($healthyModels)) {
            $modelsToTry = $healthyModels;
        }

        $lastError = '';
        $transport = $this->getTransport();

        // Support test-only base URL override (§4 A1)
        $baseOverride = (getenv('APP_ENV') !== 'production') ? getenv('AGENT_GEMINI_BASE_URL') : false;
        $baseUrl = $baseOverride ?: 'https://generativelanguage.googleapis.com';

        foreach ($modelsToTry as $modelName) {
            $apiUrl = rtrim($baseUrl, '/') . '/v1beta/models/' . $modelName . ':generateContent';

            $maxAttempts = (count($modelsToTry) === 1) ? 2 : 1;

            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                // Section 3.2 cURL Timeout formula
                $isSummaryCall = !empty($payload['is_summary_call']);
                $budget = $isSummaryCall
                    ? ($this->deadline - microtime(true)) - 1.0
                    : ($this->deadline - microtime(true)) - $this->summaryReserveSeconds;

                if ($budget < 3.0) {
                    $lastError = $this->getLocalizedMessage('time_budget_exhausted');
                    break 2;
                }

                if ($attempt > 0) {
                    usleep(300000); // 300ms pause before retry
                }

                $timeout = (int) floor(min($this->perCallCapSeconds, $budget));

                $headers = [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'x-goog-api-key: ' . $this->apiKey,
                ];

                // Real-time tick callback for heartbeats and early abort on client disconnect (§4 A5, B2)
                $lastTickHeartbeat = 0.0;
                $onTick = function ($handle, int $downloadTotal, int $downloadNow, int $uploadTotal, int $uploadNow) use (&$lastTickHeartbeat) {
                    if (connection_aborted()) {
                        return 1; // Early abort in-flight cURL
                    }
                    $now = microtime(true);
                    if ($this->eventSink !== null && ($now - $lastTickHeartbeat) >= 10.0) {
                        $lastTickHeartbeat = $now;
                        $this->emitEvent('heartbeat', []);
                    }
                    return 0;
                };

                $resp = $transport->send($apiUrl, $headers, $payload, $timeout, $onTick);
                $httpCode = $resp['http_code'];
                $curlErrorCode = $resp['curl_errno'];
                $curlErr = $resp['curl_error'];

                // Section 3.3 Model Health Policy:
                // cURL error code 28 (timeout): One strike. Fail over for this turn, but demote only after 2 consecutive timeouts
                if ($curlErrorCode === 28) {
                    $this->modelTimeoutStrikes[$modelName] = ($this->modelTimeoutStrikes[$modelName] ?? 0) + 1;
                    if ($this->modelTimeoutStrikes[$modelName] >= 2) {
                        $this->failedModels[$modelName] = true;
                    }
                    $lastError = $this->getLocalizedMessage('curl_error', ['extra' => "Timeout ({$curlErr})"]);
                    error_log('[AgentRunner] Timeout for model ' . $modelName . ': ' . $curlErr);
                    continue; // Fail over
                }

                // cURL connect error (code 6/7/35): Network-level, fail over without demotion
                if ($curlErrorCode === 6 || $curlErrorCode === 7 || $curlErrorCode === 35) {
                    $lastError = $this->getLocalizedMessage('curl_error', ['extra' => $curlErr]);
                    error_log('[AgentRunner] Network connect error for model ' . $modelName . ': ' . $curlErr);
                    continue; // Fail over
                }

                if ($curlErr) {
                    $lastError = $this->getLocalizedMessage('curl_error', ['extra' => $curlErr]);
                    $this->failedModels[$modelName] = true;
                    error_log('[AgentRunner] cURL error for model ' . $modelName . ': ' . $curlErr);
                    continue;
                }

                $resData = json_decode((string) $resp['response'], true);

                if ($httpCode === 200 && !empty($resData['candidates'])) {
                    $this->activeModel = $modelName; // Pin working model
                    $this->modelTimeoutStrikes[$modelName] = 0; // Reset strike count
                    self::setActiveModel($this->userId, $modelName);
                    return [
                        'success' => true,
                        'data' => $resData,
                        'model' => $modelName,
                    ];
                }

                // If 503 (high demand) or 404 (deprecated/not found), immediately mark failed and switch to next model
                if ($httpCode === 503 || $httpCode === 404) {
                    $this->failedModels[$modelName] = true;
                    $msg = !empty($resData['error']['message']) ? $resData['error']['message'] : "HTTP {$httpCode}";
                    $lastError = "[{$modelName}] {$msg}";
                    error_log('[AgentRunner] Model failover (' . $modelName . ' HTTP ' . $httpCode . '): ' . $msg);
                    break; // Switch to next model
                }

                // If transient 429 (rate limit), retry once after capped backoff only if sufficient budget remains
                if ($httpCode === 429 && $attempt === 0 && ($this->deadline - microtime(true)) > 10.0) {
                    error_log('[AgentRunner] Rate limit 429 encountered for ' . $modelName . ', backing off 1s...');
                    usleep(1000000); // 1.0s backoff
                    continue;
                }

                if (!empty($resData['error']['message'])) {
                    $lastError = '[' . $modelName . '] ' . $resData['error']['message'];
                } elseif ($httpCode !== 200) {
                    $lastError = '[' . $modelName . '] Google API Error (HTTP ' . $httpCode . ')';
                }
                if ($httpCode !== 200) {
                    error_log('[AgentRunner] callGeminiApi failed (' . $modelName . ' HTTP ' . $httpCode . '): ' . $lastError);
                }
                break;
            }
        }

        if ($lastError !== '') {
            error_log('[AgentRunner] callGeminiApi all attempts exhausted. Last error: ' . $lastError);
        }

        return [
            'success' => false,
            'error' => !empty($lastError) ? $lastError : $this->getLocalizedMessage('no_response'),
            'stop_reason' => (($budget ?? 0) < 3.0) ? 'time_budget_exhausted' : 'api_error',
        ];
    }

    /**
     * Estimates and tracks tokens consumed from usage metadata and persists to audit log
     */
    private function trackTokenUsage(array $resData, string $modelName = ''): void
    {
        $tokens = (int) ($resData['usageMetadata']['totalTokenCount'] ?? 0);
        if ($tokens <= 0) {
            $tokens = 500;
        }
        $this->totalTokensUsed += $tokens;

        require_once __DIR__ . '/McpAuditLogger.class.php';
        McpAuditLogger::log([
            'caller_type' => 'agent_chat',
            'caller_id' => 'user_' . $this->userId,
            'user_id' => $this->userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'tool_name' => 'gemini:' . ($modelName ?: 'flash'),
            'tokens_used' => $tokens,
            'status_code' => 200,
            'is_error' => 0,
        ]);
    }

    /**
     * Resolves localized confirmation message for destructive operations
     */
    private function getConfirmationMessage(string $toolName, bool $isSubsequent = false): string
    {
        $safeTool = htmlspecialchars($toolName, ENT_QUOTES, 'UTF-8');
        return match ($this->activeLang) {
            'ku' => $isSubsequent
                ? "Amûra '{$safeTool}' dixwaze çalakiyeke din a hesas/wêranker pêk bîne. Ji bo ewlehiyê, ji kerema xwe vê çalakiyê jî bipejirîne."
                : "Amûra '{$safeTool}' dixwaze çalakiyeke hesas/wêranker pêk bîne. Ji bo ewlehiyê, ji kerema xwe vê çalakiyê bipejirîne.",
            'tr' => $isSubsequent
                ? "'{$safeTool}' aracı hassas/yıkıcı başka bir işlem gerçekleştirmek istiyor. Güvenlik için lütfen bu işlemi de onaylayın."
                : "'{$safeTool}' aracı hassas/yıkıcı bir işlem gerçekleştirmek istiyor. Güvenlik için lütfen bu işlemi onaylayın.",
            'de' => $isSubsequent
                ? "Das Werkzeug '{$safeTool}' fordert einen weiteren sensiblen/destruktiven Vorgang an. Bitte bestätigen Sie diese Aktion aus Sicherheitsgründen."
                : "Das Werkzeug '{$safeTool}' fordert einen sensiblen/destruktiven Vorgang an. Bitte bestätigen Sie diese Aktion aus Sicherheitsgründen.",
            'fr' => $isSubsequent
                ? "L'outil '{$safeTool}' demande une autre opération sensible/destructrice. Par mesure de sécurité, veuillez confirmer cette action."
                : "L'outil '{$safeTool}' demande une opération sensible/destructrice. Par mesure de sécurité, veuillez confirmer cette action.",
            'ar' => $isSubsequent
                ? "تطلب الأداة '{$safeTool}' عملية حساسة/مدمرة أخرى. لدواعي الأمان، يرجى تأكيد هذا الإجراء."
                : "تطلب الأداة '{$safeTool}' عملية حساسة/مدمرة. لدواعي الأمان، يرجى تأكيد هذا الإجراء.",
            'ru' => $isSubsequent
                ? "Инструмент '{$safeTool}' запрашивает еще одну важную/разрушительную операцию. В целях безопасности подтвердите это действие."
                : "Инструмент '{$safeTool}' запрашивает важную/разрушительную операцию. В целях безопасности подтвердите это действие.",
            'sv' => $isSubsequent
                ? "Verktyget '{$safeTool}' begär ytterligare en känslig/destruktiv åtgärd. Bekräfta denna åtgärd av säkerhetsskäl."
                : "Verktyget '{$safeTool}' begär en känslig/destruktiv åtgärd. Bekräfta denna åtgärd av säkerhetsskäl.",
            'ckb' => $isSubsequent
                ? "ئامرازی '{$safeTool}' داوای کردارێکی تری هەستیار/سڕینەوە دەکات. لەبەر پاراستنی ئاسایش، تکایە ئەم کردارەش پشتڕاست بکەرەوە."
                : "ئامرازی '{$safeTool}' داوای کردارێکی هەستیار/سڕینەوە دەکات. لەبەر پاراستنی ئاسایش، تکایە ئەم کردارە پشتڕاست بکەرەوە.",
            'zza' => $isSubsequent
                ? "Hacetê '{$safeTool}' wazeno yewna çalakîya hesase/wêrankere bîyaro ca. Semedê pawitişî, kerem kerê na çalakîye zî tesdîq kerê."
                : "Hacetê '{$safeTool}' wazeno yew çalakîya hesase/wêrankere bîyaro ca. Semedê pawitişî, kerem kerê na çalakîye tesdîq kerê.",
            default => $isSubsequent
                ? "The tool '{$safeTool}' requests another sensitive/destructive operation. For security, please confirm this action."
                : "The tool '{$safeTool}' requests a sensitive/destructive operation. For security, please confirm this action.",
        };
    }

    /**
     * Resolves localized system/error messages based on active administration language
     */
    private function getLocalizedMessage(string $key, array $params = []): string
    {
        $lang = in_array($this->activeLang, ['ku', 'tr', 'en', 'ar', 'de', 'fr', 'ru', 'sv', 'ckb', 'zza'], true) ? $this->activeLang : 'en';

        $messages = [
            'api_key_missing' => [
                'en' => 'Gemini API key is not configured. Please save your API key in Google Services Hub.',
                'ku' => 'Mifteya Gemini API nehatiye tomarkirin. Ji kerema xwe ji Google Services Hubê mifteya xwe tomar bike.',
                'tr' => 'Gemini API anahtarı yapılandırılmamış. Lütfen Google Services Hub üzerinden API anahtarınızı kaydedin.',
                'de' => 'Gemini-API-Schlüssel ist nicht konfiguriert. Bitte speichern Sie Ihren API-Schlüssel im Google Services Hub.',
                'fr' => 'La clé API Gemini n\'est pas configurée. Veuillez enregistrer votre clé API dans Google Services Hub.',
                'ar' => 'مفتاح Gemini API غير مهيأ. يرجى حفظ مفتاح API في Google Services Hub.',
                'ru' => 'Ключ Gemini API не настроен. Пожалуйста, сохраните свой ключ API в Google Services Hub.',
                'sv' => 'Gemini API-nyckel är inte konfigurerad. Spara din API-nyckel i Google Services Hub.',
                'ckb' => 'کلیلی Gemini API دانەمەزراوە. تکایە کلیلی API لە Google Services Hub پاشەکەوت بکە.',
                'zza' => 'Kelîta Gemini API nêameya mîhengkerdene. Kerem kerê Google Services Hub ra kelîta xo qeyd kerê.',
            ],
            'daily_budget_exceeded' => [
                'en' => 'Daily token budget exceeded. Please try again tomorrow.',
                'ku' => 'Kotaya rojane ya tokenan qediya ye (daily token budget exceeded). Ji kerema xwe sibê dîsa biceribîne.',
                'tr' => 'Günlük token kotası aşıldı. Lütfen yarın tekrar deneyin.',
                'de' => 'Tägliches Token-Budget überschritten. Bitte versuchen Sie es morgen erneut.',
                'fr' => 'Budget quotidien de jetons dépassé. Veuillez réessayer demain.',
                'ar' => 'تم تجاوز ميزانية الرموز اليومية. يرجى المحاولة مرة أخرى غدًا.',
                'ru' => 'Дневной лимит токенов исчерпан. Пожалуйста, повторите попытку завтра.',
                'sv' => 'Den dagliga tokenbudgeten har överskridits. Försök igen imorgon.',
                'ckb' => 'بودجەی ڕۆژانەی تۆکنەکان تەواو بوو. تکایە بەیانی دووبارە هەوڵ بدەرەوە.',
                'zza' => 'Kotaya rojane ya tokenan qediya. Kerem kerê meşte reyna biceribnê.',
            ],
            'token_budget_exhausted' => [
                'en' => 'Run token limit reached. Please continue in a new message.',
                'ku' => 'Sînorê tokenan ê vê danişînê tijî bû. Ji kerema xwe bi peyameke nû bidomîne.',
                'tr' => 'Bu işlem için token sınırına ulaşıldı. Lütfen yeni bir mesajla devam edin.',
                'de' => 'Token-Limit für diesen Durchlauf erreicht. Bitte fahren Sie in einer neuen Nachricht fort.',
                'fr' => 'Limite de jetons atteinte pour cette exécution. Veuillez continuer dans un nouveau message.',
                'ar' => 'تم الوصول إلى حد الرموز لهذه العملية. يرجى المتابعة في رسالة جديدة.',
                'ru' => 'Достигнут лимит токенов для этого запуска. Пожалуйста, продолжите в новом сообщении.',
                'sv' => 'Tokengränsen för denna körning har nåtts. Fortsätt i ett nytt meddelande.',
                'ckb' => 'سنووری تۆکن بۆ ئەم پرۆسەیە تەواو بوو. تکایە بە نامەیەکی نوێ بەردەوام بە.',
                'zza' => 'Sînorê tokenan ê na xebate qediya. Kerem kerê bi yew mesaco newe dewam kerê.',
            ],
            'concurrent_run' => [
                'en' => 'Another operation is currently in progress. Please wait for it to finish.',
                'ku' => 'Pêvajoyeke din niha berdewam dike. Ji kerema xwe li benda qedandina wê bimîne.',
                'tr' => 'Başka bir işlem şu anda devam ediyor. Lütfen tamamlanmasını bekleyin.',
                'de' => 'Ein anderer Vorgang wird derzeit ausgeführt. Bitte warten Sie, bis er abgeschlossen ist.',
                'fr' => 'Une autre opération est en cours. Veuillez attendre qu\'elle se termine.',
                'ar' => 'هناك عملية أخرى قيد التنفيذ حاليًا. يرجى الانتظار حتى تنتهي.',
                'ru' => 'Другая операция в данный момент выполняется. Пожалуйста, дождитесь её завершения.',
                'sv' => 'En annan åtgärd pågår just nu. Vänta tills den är klar.',
                'ckb' => 'پرۆسەیەکی تر لە ئێستادا لە جێبەجێکردندایە. تکایە چاوەڕێ بکە تا تەواو دەبێت.',
                'zza' => 'Yewna proses nika dewam keno. Kerem kerê qedîyayîşê ey bipawe.',
            ],
            'deadline_exceeded' => [
                'en' => 'Operation exceeded deadline.',
                'ku' => 'Demjimêra pêvajoyê qediya (operation exceeded deadline).',
                'tr' => 'İşlem zaman aşımına uğradı (deadline exceeded).',
                'de' => 'Vorgang hat die Frist überschritten.',
                'fr' => 'L\'opération a dépassé le délai imparti.',
                'ar' => 'تجاوزت العملية المهلة المحددة.',
                'ru' => 'Превышено время ожидания операции.',
                'sv' => 'Åtgärden överskred tidsgränsen.',
                'ckb' => 'کاتی دیاریکراوی پرۆسەکە تەواو بوو.',
                'zza' => 'Wextê prosesî qediya.',
            ],
            'time_budget_exhausted' => [
                'en' => 'Time budget exhausted.',
                'ku' => 'Demjimêra pêvajoyê ber bi qedandinê ve çû (time budget exhausted).',
                'tr' => 'İşlem süresi tükendi (time budget exhausted).',
                'de' => 'Zeitbudget erschöpft.',
                'fr' => 'Budget de temps épuisé.',
                'ar' => 'تم استنفاد الميزانية الزمنية.',
                'ru' => 'Лимит времени исчерпан.',
                'sv' => 'Tidsbudgeten är slut.',
                'ckb' => 'کاتی پرۆسەکە تەواو بوو.',
                'zza' => 'Wextê prosesî qediya.',
            ],
            'no_response' => [
                'en' => 'No response received from the model.',
                'ku' => 'Tu bersiv ji modelê nehat standin.',
                'tr' => 'Modelden yanıt alınamadı.',
                'de' => 'Keine Antwort vom Modell erhalten.',
                'fr' => 'Aucune réponse reçue du modèle.',
                'ar' => 'لم يتم تلقي أي رد من النموذج.',
                'ru' => 'Ответ от модели не получен.',
                'sv' => 'Inget svar mottogs från modellen.',
                'ckb' => 'هیچ وەڵامێک لە مۆدێلەکەوە وەرنەگیرا.',
                'zza' => 'Model ra qet cewab nêame girewtene.',
            ],
            'tools_executed_success' => [
                'en' => 'Your request was successfully executed via tools ({tools}).',
                'ku' => 'Daxwaza te bi serkeftî di pergalê de bi riya amûran ({tools}) hat pêkanîn.',
                'tr' => 'İsteğiniz sistemde araçlar ({tools}) aracılığıyla başarıyla gerçekleştirildi.',
                'de' => 'Ihre Anfrage wurde erfolgreich über Werkzeuge ({tools}) ausgeführt.',
                'fr' => 'Votre demande a été exécutée avec succès via les outils ({tools}).',
                'ar' => 'تم تنفيذ طلبك بنجاح عبر الأدوات ({tools}).',
                'ru' => 'Ваш запрос успешно выполнен с помощью инструментов ({tools}).',
                'sv' => 'Din begäran utfördes framgångsrikt via verktyg ({tools}).',
                'ckb' => 'داواکارییەکەت بە سەرکەوتوویی لە ڕێگەی ئامرازەکانەوە ({tools}) ئەنجامدرا.',
                'zza' => 'Waştişê to bi serkewtiş bi rayîrê hewayan ({tools}) ame caardene.',
            ],
            'tools_read_only_incomplete' => [
                'en' => 'The assistant inspected the system ({tools}) but reached the step limit before applying modifications. No changes were saved.',
                'ku' => 'Asîstanê di pergalê de tenê agahî lêkolîn kir ({tools}), lê berî pêkanîna guhertinan sînorê gavan qediya. Tu guhertin nehat tomarkirin.',
                'tr' => 'Asistan sistemde yalnızca inceleme yaptı ({tools}), ancak değişiklikleri uygulamadan önce adım sınırına ulaştı. Hiçbir değişiklik kaydedilmedi.',
                'de' => 'Der Assistent hat das System überprüft ({tools}), erreichte jedoch das Schrittlimit vor dem Anwenden von Änderungen. Es wurden keine Änderungen gespeichert.',
                'fr' => 'L\'assistant a inspecté le système ({tools}) mais a atteint la limite d\'étapes avant d\'appliquer des modifications. Aucune modification n\'a été enregistrée.',
                'ar' => 'قام المساعد بفحص النظام ({tools}) لكنه وصل إلى الحد الأقصى للخطوات قبل تطبيق التعديلات. لم يتم حفظ أي تغييرات.',
                'ru' => 'Ассистент проверил систему ({tools}), но достиг предела шагов до внесения изменений. Никаких изменений не было сохранено.',
                'sv' => 'Assistenten inspekterade systemet ({tools}) men nådde steggränsen innan några ändringar gjordes. Inga ändringar sparades.',
                'ckb' => 'یاریدەدەر تەنها زانیارییەکانی پشکنی ({tools}) بەڵام پێش ئەنجامدانی گۆڕانکاری سنووری هەنگاوەکان تەواو بوو. هیچ گۆڕانکارییەک پاشەکەوت نەکرا.',
                'zza' => 'Asîstanî tenya sîstem kontrol kerd ({tools}), labelê verê vurnayîşan sînorê gaman qediya. Qet vurnayîşêk nêame qeydkerdene.',
            ],
            'tools_read_only_time_exhausted' => [
                'en' => 'The assistant inspected the system ({tools}) but the time limit was reached before modifications could be applied. No changes were saved.',
                'ku' => 'Asîstanê di pergalê de agahî lêkolîn kir ({tools}), lê berî pêkanîna guhertinan demjimêr qediya. Tu guhertin nehat tomarkirin.',
                'tr' => 'Asistan sistemde inceleme yaptı ({tools}), ancak değişiklikler uygulanmadan önce süre doldu. Hiçbir değişiklik kaydedilmedi.',
                'de' => 'Der Assistent hat das System überprüft ({tools}), aber das Zeitlimit wurde erreicht, bevor Änderungen angewendet werden konnten. Es wurden keine Änderungen gespeichert.',
                'fr' => 'L\'assistant a inspecté le système ({tools}) mais la limite de temps a été atteinte avant que les modifications ne puissent être appliquées. Aucune modification n\'a été enregistrée.',
                'ar' => 'قام المساعد بفحص النظام ({tools}) ولكن انتهى الوقت المتاح قبل تطبيق التعديلات. لم يتم حفظ أي تغييرات.',
                'ru' => 'Ассистент проверил систему ({tools}), но время истекло до внесения изменений. Никаких изменений не было сохранено.',
                'sv' => 'Assistenten inspekterade systemet ({tools}) men tidsgränsen nåddes innan ändringar kunde tillämpas. Inga ändringar sparades.',
                'ckb' => 'یاریدەدەر زانیارییەکانی پشکنی ({tools}) بەڵام پێش ئەنجامدانی گۆڕانکارییەکان کات تەواو بوو. هیچ گۆڕانکارییەک پاشەکەوت نەکرا.',
                'zza' => 'Asîstanî sîstem kontrol kerd ({tools}), labelê verê vurnayîşan wext qediya. Qet vurnayîşêk nêame qeydkerdene.',
            ],
            'tools_read_only_api_error' => [
                'en' => 'The assistant queried the system ({tools}) but encountered an API communication error before modifications could be made.',
                'ku' => 'Asîstanê di pergalê de agahî lêkolîn kir ({tools}), lê berî guhertinan di girêdana APIyê de şaşiyek derket.',
                'tr' => 'Asistan sistemde inceleme yaptı ({tools}), ancak değişiklikler yapılmadan önce bir API iletişim hatasıyla karşılaştı.',
                'de' => 'Der Assistent hat das System abgefragt ({tools}), aber es trat ein API-Kommunikationsfehler auf, bevor Änderungen vorgenommen werden konnten.',
                'fr' => 'L\'assistant a interrogé le système ({tools}) mais a rencontré une erreur de communication API avant que les modifications ne puissent être effectuées.',
                'ar' => 'استعلم المساعد عن النظام ({tools}) ولكنه واجه خطأ في اتصال API قبل إجراء التعديلات.',
                'ru' => 'Ассистент запросил систему ({tools}), но произошла ошибка API до внесения изменений.',
                'sv' => 'Assistenten frågade systemet ({tools}) men stötte på ett API-kommunikationsfel innan ändringar kunde göras.',
                'ckb' => 'یاریدەدەر زانیارییەکانی پشکنی ({tools}) بەڵام پێش ئەنجامدانی گۆڕانکاری هەڵەیەک لە پەیوەندی API ڕوویدا.',
                'zza' => 'Asîstanî sîstem persa ({tools}), labelê verê vurnayîşan yew xetaya APIyî vejîya.',
            ],
            'action_confirmed_success' => [
                'en' => "The confirmed action ('{tool}') was successfully executed in the system.",
                'ku' => "Çalakiya pejirandî ('{tool}') bi serkeftî di pergalê de hat pêkanîn.",
                'tr' => "Onaylanan işlem ('{tool}') sistemde başarıyla gerçekleştirildi.",
                'de' => "Die bestätigte Aktion ('{tool}') wurde erfolgreich im System ausgeführt.",
                'fr' => "L'action confirmée ('{tool}') a été exécutée avec succès dans le système.",
                'ar' => "تم تنفيذ الإجراء المؤكد ('{tool}') بنجاح في النظام.",
                'ru' => "Подтвержденное действие ('{tool}') было успешно выполнено в системе.",
                'sv' => "Den bekräftade åtgärden ('{tool}') utfördes framgångsrikt i systemet.",
                'ckb' => "کرداری پشتڕاستکراوە ('{tool}') بە سەرکەوتوویی لە سیستەمەکەدا جێبەجێ کرا.",
                'zza' => "Çalakiya tesdîqkerdiye ('{tool}') bi serkewtiş sîstem de ameye caardene.",
            ],
            'curl_error' => [
                'en' => 'Connection error (cURL): {extra}',
                'ku' => 'Çewtiya girêdanê (cURL): {extra}',
                'tr' => 'Bağlantı hatası (cURL): {extra}',
                'de' => 'Verbindungsfehler (cURL): {extra}',
                'fr' => 'Erreur de connexion (cURL): {extra}',
                'ar' => 'خطأ في الاتصال (cURL): {extra}',
                'ru' => 'Ошибка подключения (cURL): {extra}',
                'sv' => 'Anslutningsfel (cURL): {extra}',
                'ckb' => 'هەڵەی پەیوەندی (cURL): {extra}',
                'zza' => 'Xetaya girêdayîşî (cURL): {extra}',
            ],
        ];

        $defaultMessage = isset($messages[$key]['en']) ? $messages[$key]['en'] : '';
        $messageTemplate = isset($messages[$key][$lang]) ? $messages[$key][$lang] : $defaultMessage;
        foreach ($params as $paramKey => $paramValue) {
            $messageTemplate = str_replace('{' . $paramKey . '}', (string) $paramValue, $messageTemplate);
        }
        return $messageTemplate;
    }
}
