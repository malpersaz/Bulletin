<?php

namespace E2E\Harness;

use Exception;

class MockDbException extends Exception {}

class MockStatement
{
    private MockDb $db;
    private string $sql;
    private array $boundParams = [];
    private array $resultSet = [];
    private int $cursor = 0;

    public function __construct(MockDb $db, string $sql)
    {
        $this->db = $db;
        $this->sql = trim($sql);
    }

    public function execute(array $params = []): bool
    {
        $this->boundParams = $params;
        $this->resultSet = $this->db->executeParsedSql($this->sql, $this->boundParams);
        $this->cursor = 0;
        return true;
    }

    public function fetch(): ?array
    {
        if ($this->cursor >= count($this->resultSet)) {
            return null;
        }
        return $this->resultSet[$this->cursor++];
    }

    public function fetchAll(): array
    {
        return $this->resultSet;
    }

    public function fetchColumn(int $columnNumber = 0): mixed
    {
        $row = $this->fetch();
        if ($row === null) {
            return false;
        }
        $values = array_values($row);
        return $values[$columnNumber] ?? false;
    }
}

class MockDb
{
    private array $tables = [];
    private array $autoIncrements = [];
    private array $uniqueConstraints = [];
    private ?array $transactionSnapshot = null;

    public function __construct()
    {
        $this->initSchema();
    }

    public function initSchema(): void
    {
        $this->tables = [
            'mods' => [],
            'mod_content_category' => [],
            'mod_content' => [],
            'mod_faq' => [],
            'mod_newsletter' => [],
            'mod_seo' => [],
            'mod_settings' => [],
            'mod_user_group' => [],
            'mod_acl_roles' => [],
            'mod_acl_permissions' => [],
        ];

        $this->autoIncrements = [
            'mods' => 1,
            'mod_content_category' => 1,
            'mod_content' => 1,
            'mod_faq' => 1,
            'mod_newsletter' => 1,
            'mod_seo' => 1,
            'mod_settings' => 1,
            'mod_user_group' => 1,
            'mod_acl_roles' => 1,
            'mod_acl_permissions' => 1,
        ];

        $this->uniqueConstraints = [
            'mods' => ['mod_name'],
            'mod_content_category' => ['slug'],
            'mod_content' => ['slug'],
            'mod_newsletter' => ['email'],
            'mod_seo' => ['route_path'],
            'mod_settings' => ['setting_key'],
        ];
    }

    public function getPdo(): self
    {
        return $this;
    }

    public function beginTransaction(): bool
    {
        $this->transactionSnapshot = [
            'tables' => $this->tables,
            'autoIncrements' => $this->autoIncrements,
        ];
        return true;
    }

    public function commit(): bool
    {
        $this->transactionSnapshot = null;
        return true;
    }

    public function rollBack(): bool
    {
        if ($this->transactionSnapshot !== null) {
            $this->tables = $this->transactionSnapshot['tables'];
            $this->autoIncrements = $this->transactionSnapshot['autoIncrements'];
            $this->transactionSnapshot = null;
        }
        return true;
    }

    public function exec(string $sql): int
    {
        $statements = $this->splitSqlStatements($sql);
        $count = 0;
        foreach ($statements as $stmtSql) {
            if (empty($stmtSql)) continue;
            $res = $this->executeParsedSql($stmtSql, []);
            $count += count($res);
        }
        return $count;
    }

    public function query(string $sql): MockStatement
    {
        $stmt = new MockStatement($this, $sql);
        $stmt->execute([]);
        return $stmt;
    }

    public function prepare(string $sql): MockStatement
    {
        return new MockStatement($this, $sql);
    }

    public function lastInsertId(?string $table = null): int
    {
        if ($table && isset($this->autoIncrements[$table])) {
            return $this->autoIncrements[$table] - 1;
        }
        return 1;
    }

    public function seedCoreModules(): void
    {
        $mods = [
            ['mod_content', 'Content Management', 1, 0, 1],
            ['mod_content_category', 'Content Categories', 1, 0, 2],
            ['mod_seo', 'Search Engine Optimization', 1, 1, 3],
            ['mod_faq', 'Frequently Asked Questions', 1, 0, 4],
            ['mod_newsletter', 'Newsletter Subscriptions', 1, 0, 5],
            ['mod_settings', 'System Settings', 1, 1, 6],
            ['mod_user', 'User Management', 1, 1, 7],
            ['mod_acl', 'Access Control List', 1, 1, 8],
        ];

        foreach ($mods as $m) {
            $this->insertOrReplace('mods', [
                'mod_name' => $m[0],
                'mod_title' => $m[1],
                'active' => $m[2],
                'is_system' => $m[3],
                'sort_order' => $m[4],
            ]);
        }
    }

    public function executeParsedSql(string $sql, array $params = []): array
    {
        $sql = trim($sql);

        // CREATE TABLE / CREATE VIEW
        if (stripos($sql, 'CREATE TABLE') === 0 || stripos($sql, 'CREATE VIEW') === 0) {
            return [];
        }

        // INSERT INTO or INSERT OR REPLACE
        if (preg_match('/^INSERT (?:OR REPLACE )?INTO `?([a-zA-Z0-9_]+)`?\s*(?:\(([^)]+)\))?\s*VALUES\s*(.+)$/is', $sql, $m)) {
            $table = $m[1];
            $colStr = $m[2];
            $valStr = $m[3];

            $cols = $colStr ? array_map(fn($c) => trim($c, " `"), explode(',', $colStr)) : [];
            return $this->handleInsert($table, $cols, $valStr, $params);
        }

        // UPDATE
        if (preg_match('/^UPDATE `?([a-zA-Z0-9_]+)`?\s+SET\s+(.+?)(?:\s+WHERE\s+(.+))?$/is', $sql, $m)) {
            $table = $m[1];
            $setClause = $m[2];
            $whereClause = $m[3] ?? null;
            return $this->handleUpdate($table, $setClause, $whereClause, $params);
        }

        // DELETE
        if (preg_match('/^DELETE FROM `?([a-zA-Z0-9_]+)`?(?:\s+WHERE\s+(.+))?$/is', $sql, $m)) {
            $table = $m[1];
            $whereClause = $m[2] ?? null;
            return $this->handleDelete($table, $whereClause, $params);
        }

        // SELECT
        if (stripos($sql, 'SELECT') === 0) {
            return $this->handleSelect($sql, $params);
        }

        return [];
    }

    private function splitSqlStatements(string $sql): array
    {
        $stmts = [];
        $len = strlen($sql);
        $curr = '';
        $inQuote = false;
        $quoteChar = '';

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];
            if (($char === "'" || $char === '"') && ($i === 0 || $sql[$i - 1] !== '\\')) {
                if (!$inQuote) {
                    $inQuote = true;
                    $quoteChar = $char;
                } elseif ($quoteChar === $char) {
                    $inQuote = false;
                }
            }

            if ($char === ';' && !$inQuote) {
                $trimmed = trim($curr);
                if ($trimmed !== '') {
                    $stmts[] = $trimmed;
                }
                $curr = '';
            } else {
                $curr .= $char;
            }
        }

        $trimmed = trim($curr);
        if ($trimmed !== '') {
            $stmts[] = $trimmed;
        }

        return $stmts;
    }

    private function splitSqlTokens(string $str): array
    {
        $tokens = [];
        $len = strlen($str);
        $curr = '';
        $inQuote = false;
        $quoteChar = '';

        for ($i = 0; $i < $len; $i++) {
            $char = $str[$i];
            if (($char === "'" || $char === '"') && ($i === 0 || $str[$i - 1] !== '\\')) {
                if (!$inQuote) {
                    $inQuote = true;
                    $quoteChar = $char;
                } elseif ($quoteChar === $char) {
                    $inQuote = false;
                }
            }

            if ($char === ',' && !$inQuote) {
                $tokens[] = trim($curr);
                $curr = '';
            } else {
                $curr .= $char;
            }
        }

        $tokens[] = trim($curr);
        return $tokens;
    }

    private function extractValueTuples(string $valStr): array
    {
        $tuples = [];
        $len = strlen($valStr);
        $inTuple = false;
        $inQuote = false;
        $quoteChar = '';
        $curr = '';

        for ($i = 0; $i < $len; $i++) {
            $char = $valStr[$i];
            if (($char === "'" || $char === '"') && ($i === 0 || $valStr[$i - 1] !== '\\')) {
                if (!$inQuote) {
                    $inQuote = true;
                    $quoteChar = $char;
                } elseif ($quoteChar === $char) {
                    $inQuote = false;
                }
            }

            if ($char === '(' && !$inQuote) {
                $inTuple = true;
                $curr = '';
                continue;
            }

            if ($char === ')' && !$inQuote) {
                $inTuple = false;
                $tuples[] = $curr;
                $curr = '';
                continue;
            }

            if ($inTuple) {
                $curr .= $char;
            }
        }

        return $tuples;
    }

    private function handleInsert(string $table, array $cols, string $valStr, array $params): array
    {
        if (!isset($this->tables[$table])) {
            $this->tables[$table] = [];
            $this->autoIncrements[$table] = 1;
        }

        $tuples = $this->extractValueTuples($valStr);
        $paramIdx = 0;
        $insertedRow = [];

        foreach ($tuples as $tuple) {
            $rawVals = $this->splitSqlTokens($tuple);
            $row = [];

            for ($i = 0; $i < count($rawVals); $i++) {
                $rawVal = $rawVals[$i];
                $colName = $cols[$i] ?? "col_{$i}";

                if ($rawVal === '?') {
                    $val = $params[$paramIdx++] ?? null;
                } elseif (preg_match("/^'([^']*)'$/", $rawVal, $strMatch)) {
                    $val = stripslashes($strMatch[1]);
                } elseif (is_numeric($rawVal)) {
                    $val = strpos($rawVal, '.') !== false ? (float)$rawVal : (int)$rawVal;
                } elseif (strtoupper($rawVal) === 'NULL') {
                    $val = null;
                } elseif (strtoupper($rawVal) === 'CURRENT_TIMESTAMP') {
                    $val = date('Y-m-d H:i:s');
                } else {
                    $val = trim($rawVal, "'\"");
                }

                $row[$colName] = $val;
            }

            if (!isset($row['id'])) {
                $row['id'] = $this->autoIncrements[$table]++;
            } else {
                if ($row['id'] >= $this->autoIncrements[$table]) {
                    $this->autoIncrements[$table] = $row['id'] + 1;
                }
            }

            // Defaults
            if ($table === 'mod_content' && !isset($row['views_count'])) $row['views_count'] = 0;
            if ($table === 'mod_content' && !isset($row['hero_slider'])) $row['hero_slider'] = 0;
            if ($table === 'mod_content' && !isset($row['updated_at'])) $row['updated_at'] = date('Y-m-d H:i:s');
            if ($table === 'mod_content_category' && !isset($row['active'])) $row['active'] = 1;
            if ($table === 'mod_content_category' && !isset($row['updated_at'])) $row['updated_at'] = date('Y-m-d H:i:s');

            // Unique constraint check
            if (isset($this->uniqueConstraints[$table])) {
                foreach ($this->uniqueConstraints[$table] as $uniqueCol) {
                    if (isset($row[$uniqueCol])) {
                        foreach ($this->tables[$table] as $existingIdx => $existing) {
                            if ($existing[$uniqueCol] === $row[$uniqueCol]) {
                                if (isset($existing['id']) && $existing['id'] === $row['id']) {
                                    $this->tables[$table][$existingIdx] = array_merge($existing, $row);
                                    return [$this->tables[$table][$existingIdx]];
                                }
                                throw new MockDbException("UNIQUE constraint failed: {$table}.{$uniqueCol} = '{$row[$uniqueCol]}'");
                            }
                        }
                    }
                }
            }

            $this->tables[$table][] = $row;
            $insertedRow = $row;
        }

        return [$insertedRow];
    }

    private function insertOrReplace(string $table, array $data): void
    {
        if (!isset($this->tables[$table])) {
            $this->tables[$table] = [];
            $this->autoIncrements[$table] = 1;
        }

        $uniqueKeys = $this->uniqueConstraints[$table] ?? [];
        foreach ($this->tables[$table] as $idx => $existing) {
            foreach ($uniqueKeys as $uk) {
                if (isset($data[$uk]) && isset($existing[$uk]) && $existing[$uk] === $data[$uk]) {
                    $this->tables[$table][$idx] = array_merge($existing, $data);
                    return;
                }
            }
        }

        if (!isset($data['id'])) {
            $data['id'] = $this->autoIncrements[$table]++;
        }
        $this->tables[$table][] = $data;
    }

    private function handleUpdate(string $table, string $setClause, ?string $whereClause, array $params): array
    {
        if (!isset($this->tables[$table])) {
            return [];
        }

        $setPlaceholders = substr_count($setClause, '?');
        $setParams = array_slice($params, 0, $setPlaceholders);
        $whereParams = array_slice($params, $setPlaceholders);

        $sets = [];
        foreach ($this->splitSqlTokens($setClause) as $setPair) {
            $parts = explode('=', $setPair, 2);
            $col = trim($parts[0], " `");
            $expr = trim($parts[1] ?? '');
            $sets[$col] = $expr;
        }

        $updatedRows = [];
        foreach ($this->tables[$table] as &$row) {
            $whereParamIdx = 0;
            if ($this->evalWhere($row, $whereClause, $whereParams, $whereParamIdx)) {
                $setParamIdx = 0;
                foreach ($sets as $col => $expr) {
                    if ($expr === '?') {
                        $row[$col] = $setParams[$setParamIdx++] ?? null;
                    } elseif ($expr === 'CURRENT_TIMESTAMP') {
                        $row[$col] = date('Y-m-d H:i:s');
                    } elseif (preg_match('/^' . preg_quote($col, '/') . '\s*\+\s*(\d+)$/', $expr, $incM)) {
                        $row[$col] = ((int)($row[$col] ?? 0)) + (int)$incM[1];
                    } else {
                        $row[$col] = trim($expr, "'\"");
                    }
                }
                $updatedRows[] = $row;
            }
        }

        return $updatedRows;
    }

    private function handleDelete(string $table, ?string $whereClause, array $params): array
    {
        if (!isset($this->tables[$table])) {
            return [];
        }

        $paramIdx = 0;
        $deleted = [];
        $remaining = [];

        foreach ($this->tables[$table] as $row) {
            if ($this->evalWhere($row, $whereClause, $params, $paramIdx)) {
                $deleted[] = $row;
            } else {
                $remaining[] = $row;
            }
        }

        $this->tables[$table] = $remaining;
        return $deleted;
    }

    private function handleSelect(string $sql, array $params): array
    {
        if (stripos($sql, 'UNION ALL') !== false) {
            $parts = explode('UNION ALL', $sql);
            $combined = [];
            foreach ($parts as $part) {
                $combined = array_merge($combined, $this->handleSelect(trim($part), $params));
            }
            return $combined;
        }

        $fromTable = 'mod_content';
        if (preg_match('/FROM\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $m)) {
            $fromTable = $m[1];
        }

        $rows = [];
        if ($fromTable === 'contents') {
            $contentRows = $this->tables['mod_content'] ?? [];
            $categories = [];
            foreach ($this->tables['mod_content_category'] ?? [] as $cat) {
                $categories[$cat['id']] = $cat;
            }

            foreach ($contentRows as $c) {
                $catId = $c['category_id'] ?? 0;
                $cat = $categories[$catId] ?? [];
                $row = $c;
                $row['category_name'] = $cat['category_name'] ?? null;
                $row['category_slug'] = $cat['slug'] ?? null;
                $rows[] = $row;
            }
        } else {
            $rows = $this->tables[$fromTable] ?? [];
        }

        $whereClause = null;
        if (preg_match('/WHERE\s+(.+?)(?:\s+ORDER BY|\s+LIMIT|\s*$)/i', $sql, $wm)) {
            $whereClause = $wm[1];
        }

        $paramIdx = 0;
        $filtered = [];
        foreach ($rows as $r) {
            $pIdxCopy = $paramIdx;
            if ($this->evalWhere($r, $whereClause, $params, $pIdxCopy)) {
                $filtered[] = $r;
            }
        }

        // ORDER BY
        if (preg_match('/ORDER BY\s+([a-zA-Z0-9_]+)\s*(ASC|DESC)?/i', $sql, $om)) {
            $orderCol = $om[1];
            $orderDir = strtoupper($om[2] ?? 'ASC');
            usort($filtered, function($a, $b) use ($orderCol, $orderDir) {
                $valA = $a[$orderCol] ?? 0;
                $valB = $b[$orderCol] ?? 0;
                return $orderDir === 'DESC' ? ($valB <=> $valA) : ($valA <=> $valB);
            });
        }

        // COUNT(*)
        if (preg_match('/SELECT\s+COUNT\(\*\)\s+FROM/i', $sql)) {
            return [['COUNT(*)' => count($filtered)]];
        }

        // Columns projection
        if (preg_match('/SELECT\s+(.+?)\s+FROM/i', $sql, $cm)) {
            $cols = array_map(fn($c) => trim($c, " `"), explode(',', $cm[1]));
            if ($cols[0] !== '*') {
                $projected = [];
                foreach ($filtered as $r) {
                    $pRow = [];
                    foreach ($cols as $col) {
                        $pRow[$col] = $r[$col] ?? null;
                    }
                    $projected[] = $pRow;
                }
                return $projected;
            }
        }

        return $filtered;
    }

    private function evalWhere(array $row, ?string $whereClause, array $params, int &$paramIdx): bool
    {
        if ($whereClause === null || trim($whereClause) === '') {
            return true;
        }

        $conditions = preg_split('/\s+AND\s+/i', $whereClause);
        foreach ($conditions as $cond) {
            $cond = trim($cond);
            if (preg_match('/^`?([a-zA-Z0-9_]+)`?\s*=\s*(.+)$/', $cond, $m)) {
                $col = $m[1];
                $expectedExpr = trim($m[2]);

                if ($expectedExpr === '?') {
                    $expected = $params[$paramIdx++] ?? null;
                } else {
                    $expected = trim($expectedExpr, "'\"");
                }

                $actual = $row[$col] ?? null;
                if ((string)$actual !== (string)$expected) {
                    return false;
                }
            } elseif (preg_match('/^`?([a-zA-Z0-9_]+)`?\s+LIKE\s+(.+)$/i', $cond, $m)) {
                $col = $m[1];
                $likeExpr = trim($m[2]);
                if ($likeExpr === '?') {
                    $likePattern = $params[$paramIdx++] ?? '';
                } else {
                    $likePattern = trim($likeExpr, "'\"");
                }

                $cleanNeedle = trim($likePattern, '%');
                $actual = (string)($row[$col] ?? '');
                if (stripos($actual, $cleanNeedle) === false) {
                    return false;
                }
            }
        }

        return true;
    }
}
