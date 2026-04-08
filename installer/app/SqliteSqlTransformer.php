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

        $sql = preg_replace('/\)\s*ENGINE=[^;]+;/i', ');', $sql);
        $sql = preg_replace('/\)\s*DEFAULT CHARSET=[^;]+;/i', ');', $sql);
        $sql = preg_replace('/COLLATE [^ ]+/i', '', $sql);
        $sql = preg_replace('/DEFAULT CHARSET=[^;\s]+/i', '', $sql);

        $sql = preg_replace('/`(\w+)`\s+int\(\d+\)\s+NOT NULL\s+AUTO_INCREMENT\s+PRIMARY KEY/i', '`$1` INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = preg_replace('/`(\w+)`\s+int\(\d+\)\s+NOT NULL\s+AUTO_INCREMENT/i', '`$1` INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = preg_replace('/\bint\(\d+\)\b/i', 'INTEGER', $sql);

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
        $sql = preg_replace('/,\s*(INDEX|KEY) `?[^` ]+`? \([^)]+\)/i', '', $sql);
        $sql = preg_replace('/,\s*\)/m', "\n)", $sql);
        $sql = preg_replace('/\s+;/', ';', $sql);

        return $sql;
    }
}
