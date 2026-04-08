<?php

/**
 * SqliteSqlTransformer - MySQL to SQLite SQL Converter
 * 
 * This class provides utility methods to transform MySQL-flavored DDL
 * into SQLite-compatible SQL statements.
 */
class SqliteSqlTransformer
{
    /**
     * Transforms a MySQL SQL string into SQLite compatible SQL.
     *
     * @param string $sql
     * @return string
     */
    public static function transform($sql)
    {
        if (trim($sql) === '') {
            return $sql;
        }

        $sql = self::transformViews($sql);
        $inlineIndexes = self::extractInlineIndexes($sql);

        $sql = preg_replace('/\)\s*ENGINE=[^;]+;/i', ');', $sql);
        $sql = preg_replace('/\)\s*DEFAULT CHARSET=[^;]+;/i', ');', $sql);
        $sql = preg_replace('/COLLATE [^ ]+/i', '', $sql);
        $sql = preg_replace('/DEFAULT CHARSET=[^;\s]+/i', '', $sql);

        $sql = preg_replace('/`(\w+)`\s+int(?:\(\d+\))?\s+NOT NULL\s+AUTO_INCREMENT\s+PRIMARY KEY/i', '`$1` INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = preg_replace('/`(\w+)`\s+int(?:\(\d+\))?\s+NOT NULL\s+AUTO_INCREMENT/i', '`$1` INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = preg_replace('/\bint\(\d+\)\b/i', 'INTEGER', $sql);
        $sql = preg_replace('/\bint\b/i', 'INTEGER', $sql);
        $sql = preg_replace('/\byear\b/i', 'INTEGER', $sql);

        if (preg_match('/PRIMARY KEY \(`([^`]+)`\)/i', $sql, $matches)) {
            $pkField = $matches[1];
            if (preg_match('/`' . preg_quote($pkField) . '` INTEGER PRIMARY KEY/i', $sql)) {
                $sql = preg_replace('/,\s*PRIMARY KEY \(`' . preg_quote($pkField) . '`\)/i', '', $sql);
                $sql = preg_replace('/PRIMARY KEY \(`' . preg_quote($pkField) . '`\),\s*/i', '', $sql);
            }
        }

        $sql = preg_replace('/enum\([^)]+\)/i', 'VARCHAR(255)', $sql);
        $sql = preg_replace('/ON UPDATE CURRENT_TIMESTAMP/i', '', $sql);
        $sql = preg_replace('/UNIQUE KEY `?([^` ]+)?`? \(([^)]+)\)/i', 'UNIQUE($2)', $sql);
        $sql = preg_replace('/,\s*(?:INDEX|KEY)(?:\s+`?[^` ()]+`?)?\s*\([^)]+\)/i', '', $sql);
        $sql = preg_replace('/,\s*\)/m', "\n)", $sql);
        $sql = preg_replace('/\s+;/', ';', $sql);

        if (!empty($inlineIndexes)) {
            $sql = rtrim($sql) . "\n" . implode("\n", $inlineIndexes) . "\n";
        }

        return $sql;
    }

    /**
     * SQLite does not support CREATE OR REPLACE VIEW and repeated installer
     * runs should be able to recreate views safely.
     *
     * @param string $sql
     * @return string
     */
    protected static function transformViews($sql)
    {
        return preg_replace_callback(
            '/CREATE\s+(?:OR\s+REPLACE\s+)?VIEW\s+`?([^`\s]+)`?\s+AS\s+/i',
            function ($match) {
                $viewName = $match[1];
                return 'DROP VIEW IF EXISTS `' . $viewName . '`;' . "\n" . 'CREATE VIEW `' . $viewName . '` AS ';
            },
            $sql
        );
    }

    /**
     * Converts MySQL inline indexes declared inside CREATE TABLE into
     * standalone SQLite CREATE INDEX statements.
     *
     * @param string $sql
     * @return array<int, string>
     */
    protected static function extractInlineIndexes($sql)
    {
        if (!preg_match('/CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+`?([^`\s(]+)`?/i', $sql, $tableMatch)) {
            return array();
        }

        $table = $tableMatch[1];
        $primaryKeyColumns = self::extractConstraintColumns($sql, 'PRIMARY\s+KEY');
        preg_match_all('/^\s*(INDEX|KEY)(?:\s+`?([^` ()]+)`?)?\s*\(([^)]+)\)/im', $sql, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return array();
        }

        $statements = array();
        foreach ($matches as $position => $match) {
            $rawColumns = array_map('trim', explode(',', $match[3]));
            $columns = array();
            foreach ($rawColumns as $column) {
                $column = preg_replace('/\s+(ASC|DESC)\s*$/i', '', $column);
                $column = trim((string) $column, " \t\n\r\0\x0B`");
                if ($column !== '') {
                    $columns[] = $column;
                }
            }

            if (empty($columns)) {
                continue;
            }

            if ($columns === $primaryKeyColumns) {
                continue;
            }

            if (isset($match[2]) && $match[2] !== '') {
                $indexName = $match[2];
            } else {
                $nameParts = array();
                foreach ($columns as $column) {
                    $nameParts[] = $column;
                }
                $indexName = $table . '_' . implode('_', $nameParts) . '_' . ($position + 1);
            }

            $quotedColumns = array();
            foreach ($columns as $column) {
                $quotedColumns[] = '`' . $column . '`';
            }

            $statements[] = 'CREATE INDEX IF NOT EXISTS `' . $indexName . '` ON `' . $table . '` (' . implode(', ', $quotedColumns) . ');';
        }

        return $statements;
    }

    /**
     * @param string $sql
     * @param string $constraintPattern
     * @return array<int, string>
     */
    protected static function extractConstraintColumns($sql, $constraintPattern)
    {
        if (!preg_match('/' . $constraintPattern . '\s*\(([^)]+)\)/i', $sql, $match)) {
            return array();
        }

        $columns = array();
        foreach (explode(',', $match[1]) as $column) {
            $column = preg_replace('/\s+(ASC|DESC)\s*$/i', '', $column);
            $column = trim((string) $column, " \t\n\r\0\x0B`");
            if ($column !== '') {
                $columns[] = $column;
            }
        }

        return $columns;
    }
}
