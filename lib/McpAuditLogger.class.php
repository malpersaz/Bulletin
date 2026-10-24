<?php

/**
 * MCP and Agent Audit Logger
 *
 * Provides central audit logging, rate limiting verification,
 * and daily token budget tracking across all MCP entry points.
 */
class McpAuditLogger
{
    private static ?\PDO $pdo = null;
    private static bool $tableEnsured = false;

    /**
     * Obtains or initializes the PDO connection from Database library
     */
    private static function getPdo(): ?\PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        try {
            $db = Load::lib('Database');
            if (method_exists($db, 'get_connection')) {
                $conn = $db->get_connection();
                if ($conn instanceof \PDO) {
                    self::$pdo = $conn;
                }
            }
        } catch (\Throwable $e) {
            error_log('[McpAuditLogger] Failed to obtain PDO instance: ' . $e->getMessage());
        }

        return self::$pdo;
    }

    /**
     * Ensures the audit log table exists (executed via bootstrap or lazily)
     */
    public static function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }

        $pdo = self::getPdo();
        if (!$pdo) {
            return;
        }

        try {
            $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `mod_mcp_audit_log` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `caller_type` TEXT NOT NULL,
                    `caller_id` VARCHAR(128) NULL,
                    `user_id` INTEGER NULL,
                    `ip_address` VARCHAR(45) NOT NULL,
                    `tool_name` VARCHAR(128) NOT NULL,
                    `parameters_hash` VARCHAR(64) NULL,
                    `execution_time_ms` REAL NOT NULL DEFAULT 0.00,
                    `tokens_used` INTEGER NOT NULL DEFAULT 0,
                    `status_code` INTEGER NOT NULL DEFAULT 200,
                    `is_error` INTEGER NOT NULL DEFAULT 0,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                );");
            } else {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `mod_mcp_audit_log` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `caller_type` ENUM('api_key', 'oauth', 'agent_chat', 'internal') NOT NULL,
                    `caller_id` VARCHAR(128) NULL,
                    `user_id` INT UNSIGNED NULL,
                    `ip_address` VARCHAR(45) NOT NULL,
                    `tool_name` VARCHAR(128) NOT NULL,
                    `parameters_hash` VARCHAR(64) NULL,
                    `execution_time_ms` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                    `tokens_used` INT NOT NULL DEFAULT 0,
                    `status_code` INT NOT NULL DEFAULT 200,
                    `is_error` TINYINT(1) NOT NULL DEFAULT 0,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_created_at` (`created_at`),
                    INDEX `idx_caller` (`caller_type`, `caller_id`),
                    INDEX `idx_tool` (`tool_name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            }
            self::$tableEnsured = true;
        } catch (\Throwable $e) {
            error_log('[McpAuditLogger] ensureTable error: ' . $e->getMessage());
        }
    }

    /**
     * Records an audit log entry with secret redaction and parameters hashing
     */
    public static function log(array $data): bool
    {
        $pdo = self::getPdo();
        if (!$pdo) {
            return false;
        }

        try {
            self::ensureTable();
            $callerType = $data['caller_type'] ?? 'internal';
            $callerId = isset($data['caller_id']) ? (string) $data['caller_id'] : null;
            $userId = isset($data['user_id']) ? (int) $data['user_id'] : null;
            $ipAddress = (string) ($data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
            $toolName = (string) ($data['tool_name'] ?? 'unknown');
            $executionTimeMs = (float) ($data['execution_time_ms'] ?? 0.00);
            $tokensUsed = (int) ($data['tokens_used'] ?? 0);
            $statusCode = (int) ($data['status_code'] ?? 200);
            $isError = !empty($data['is_error']) ? 1 : 0;

            // Redact and hash parameters
            $paramsHash = null;
            if (isset($data['parameters'])) {
                $sanitized = self::redactSecrets($data['parameters']);
                $paramsHash = hash('sha256', json_encode($sanitized));
            }

            $createdAt = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare(
                "INSERT INTO mod_mcp_audit_log (
                    caller_type, caller_id, user_id, ip_address, tool_name,
                    parameters_hash, execution_time_ms, tokens_used, status_code, is_error, created_at
                ) VALUES (
                    :caller_type, :caller_id, :user_id, :ip_address, :tool_name,
                    :parameters_hash, :execution_time_ms, :tokens_used, :status_code, :is_error, :created_at
                )"
            );

            return $stmt->execute([
                ':caller_type' => $callerType,
                ':caller_id' => $callerId,
                ':user_id' => $userId,
                ':ip_address' => $ipAddress,
                ':tool_name' => $toolName,
                ':parameters_hash' => $paramsHash,
                ':execution_time_ms' => $executionTimeMs,
                ':tokens_used' => $tokensUsed,
                ':status_code' => $statusCode,
                ':is_error' => $isError,
                ':created_at' => $createdAt,
            ]);
        } catch (\Throwable $e) {
            error_log('[McpAuditLogger] Log insertion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Checks rate limits per caller within a 60-second sliding window
     */
    public static function checkRateLimit(string $callerType, ?string $callerId, int $maxPerMinute = 120, ?string $toolName = null): bool
    {
        if (empty($callerId)) {
            return true;
        }

        $pdo = self::getPdo();
        if (!$pdo) {
            return true;
        }

        try {
            self::ensureTable();
            $windowStart = date('Y-m-d H:i:s', time() - 60);
            $sql = "SELECT COUNT(*) FROM mod_mcp_audit_log
                    WHERE caller_type = :caller_type
                      AND caller_id = :caller_id
                      AND created_at >= :window_start";
            $params = [
                ':caller_type' => $callerType,
                ':caller_id' => $callerId,
                ':window_start' => $windowStart,
            ];

            if ($toolName !== null) {
                $sql .= " AND tool_name = :tool_name";
                $params[':tool_name'] = $toolName;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $count = (int) $stmt->fetchColumn();
            return $count < $maxPerMinute;
        } catch (\Throwable $e) {
            error_log('[McpAuditLogger] Rate limit check failed: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Calculates total daily token usage, optionally filtered by user ID
     */
    public static function getDailyTokenUsage(?int $userId = null): int
    {
        $pdo = self::getPdo();
        if (!$pdo) {
            return 0;
        }

        try {
            self::ensureTable();
            $todayStart = date('Y-m-d 00:00:00');
            $sql = "SELECT COALESCE(SUM(tokens_used), 0) FROM mod_mcp_audit_log
                    WHERE created_at >= :today_start";
            $params = [':today_start' => $todayStart];

            if ($userId !== null && $userId > 0) {
                $sql .= " AND user_id = :user_id";
                $params[':user_id'] = $userId;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            error_log('[McpAuditLogger] Token usage calculation failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verifies that the daily token consumption does not exceed the quota
     * Root administrator (group 1) is granted unconstrained quota
     */
    public static function checkDailyTokenBudget(?int $userId = null, ?int $groupId = null, int $maxTokensPerDay = 10000000): bool
    {
        // Root administrator group (1) has unlimited operational budget
        if ($groupId === 1) {
            return true;
        }

        return self::getDailyTokenUsage($userId) < $maxTokensPerDay;
    }

    /**
     * Queries audit log records for the administrative dashboard
     */
    public static function queryLogs(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $pdo = self::getPdo();
        if (!$pdo) {
            return [];
        }

        try {
            self::ensureTable();
            $conditions = [];
            $params = [];

            if (!empty($filters['caller_type'])) {
                $conditions[] = 'caller_type = :caller_type';
                $params[':caller_type'] = $filters['caller_type'];
            }
            if (!empty($filters['tool_name'])) {
                $conditions[] = 'tool_name LIKE :tool_name';
                $params[':tool_name'] = '%' . $filters['tool_name'] . '%';
            }
            if (isset($filters['is_error']) && $filters['is_error'] !== '') {
                $conditions[] = 'is_error = :is_error';
                $params[':is_error'] = (int) $filters['is_error'];
            }

            $whereSql = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            $limit = max(1, min($limit, 200));
            $offset = max(0, $offset);

            $sql = "SELECT id, caller_type, caller_id, user_id, ip_address, tool_name,
                           parameters_hash, execution_time_ms, tokens_used, status_code, is_error, created_at
                    FROM mod_mcp_audit_log
                    {$whereSql}
                    ORDER BY id DESC
                    LIMIT {$limit} OFFSET {$offset}";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('[McpAuditLogger] queryLogs failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Redacts known sensitive fields from an array
     */
    public static function redactSecrets($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $sensitiveKeys = [
            'password', 'pass', 'secret', 'token', 'api_key', 'key',
            'client_secret', 'access_token', 'refresh_token', 'code_verifier'
        ];

        $sanitized = [];
        foreach ($data as $key => $val) {
            if (is_string($key) && in_array(strtolower($key), $sensitiveKeys, true)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($val)) {
                $sanitized[$key] = self::redactSecrets($val);
            } else {
                $sanitized[$key] = $val;
            }
        }

        return $sanitized;
    }
}
