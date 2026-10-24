<?php

/**
 * Amed Framework
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * Class: Dbuilder
 */
class Dbuilder
{
    public static $symbol;

    public static function cc($val)
    {
        if ($val === null) {
            return '';
        }
        return str_replace("'", "''", (string)$val);
    }

    public static function table($arg)
    {
        return $arg;
    }

    public static function table_join($joinConfig)
    {
        if (is_array($joinConfig) === false) {
            throw new DbuilderException("Table JOIN error: parameter is not an array!");
        }
        $join = "";
        $select = [];
        foreach ($joinConfig as $data) {
            foreach ($data['table']['1'] as $column) {
                $select[] = $data['table']['0'] . "." . $column;
            }
            if (isset($data['alias'])) {
                foreach ($data['alias']['1'] as $column) {
                    $select[] = $data['alias']['0'] . "." . $column;
                }
                $join .= $data['table']['0'] . " " . $data['type'] . " JOIN ";
                $join .= $data['alias']['0'];
                $join .= " ON ";
                $join .= $data['condition']['0'] . "=" . $data['condition']['1'];
            } else {
                $join .= " " . $data['type'] . " JOIN " . $data['table']['0'];
                $join .= " ON ";
                $join .= $data['condition']['0'] . "=" . $data['condition']['1'];
            }
            $join .= "\n";
        }
        return [
            'table' => $join,
            'select' => $select
        ];
    }

    public static function coloms($arg)
    {
        if ($arg === null || $arg === '') {
            return '';
        }

        if (is_array($arg)) {
            $escaped_cols = array_map([self::class, 'wrapSymbol'], $arg);
            return implode(',', $escaped_cols);
        }
        
        if ($arg === '*') {
            return '*';
        }

        $argStr = (string)$arg;
        if (strpos($argStr, ',') !== false) {
            $parts = explode(',', $argStr);
            $escaped_parts = array_map([self::class, 'wrapSymbol'], $parts);
            return implode(',', $escaped_parts);
        }

        return self::wrapSymbol($arg);
    }

    private static function wrapSymbol($val)
    {
        if ($val === null || $val === '') {
            return '';
        }

        $val = trim((string)$val);
        if ($val === '' || $val === '*' || strpos($val, '(') !== false || strpos($val, ' ') !== false) {
            return $val;
        }

        $quoteSymbol = self::$symbol ?? '';
        
        // Handle table-prefixed columns (e.g., table.column)
        if (strpos($val, '.') !== false) {
            $parts = explode('.', $val);
            $escaped_parts = array_map(function($part) use ($quoteSymbol) {
                if ($part === '*' || ($quoteSymbol !== '' && strpos($part, $quoteSymbol) === 0 && substr($part, -1) === $quoteSymbol)) {
                    return $part;
                }
                return $quoteSymbol . $part . $quoteSymbol;
            }, $parts);
            return implode('.', $escaped_parts);
        }

        // If already wrapped with symbol, do not add again
        if ($quoteSymbol !== '' && strpos($val, $quoteSymbol) === 0 && substr($val, -1) === $quoteSymbol) {
            return $val;
        }
        return $quoteSymbol . $val . $quoteSymbol;
    }

    public static function limit($limit, $offset = null)
    {
        return $offset == null ? "LIMIT " . $limit : "LIMIT $limit OFFSET $offset";
    }

    public static function order($column, $type)
    {
        if (is_array($column)) {
            $escaped_cols = array_map([self::class, 'wrapSymbol'], $column);
            $column = implode(',', $escaped_cols);
        } elseif (strpos((string)$column, ',') !== false) {
            $parts = explode(',', (string)$column);
            $escaped_cols = array_map([self::class, 'wrapSymbol'], $parts);
            $column = implode(',', $escaped_cols);
        } else {
            $column = self::wrapSymbol($column);
        }
        return " ORDER BY " . $column . " " . $type;
    }

    public static function where($data)
    {
        return self::create_where($data);
    }

    /*
     * Build insert column keys
     * @param array $data
     * @return string
     */
    public static function create_insert_key($data)
    {
        if (is_array($data)) {
            $keys = array_keys($data);
            $escaped_keys = array_map([self::class, 'wrapSymbol'], $keys);
            return '(' . implode(',', $escaped_keys) . ')';
        }
        throw new DbuilderException("Insert builder error: parameter is not an array!");
    }

    /*
     * Build insert values
     * @param array $data
     * @return string
     */
    public static function create_insert_value($data)
    {
        if (is_array($data)) {
            return ' VALUES (\'' . implode('\',\'', array_map(['Dbuilder', 'cc'], array_values($data))) . '\')';
        }
        throw new DbuilderException("Insert builder error: parameter is not an array!");
    }

    /*
     * Create insert SQL string
     * @param string $table
     * @param array $array
     * @param bool|null $replace
     * @return string
     */
    public static function create_insert($table, $array, $replace = null)
    {
        $val = $replace ? 'REPLACE INTO ' : 'INSERT INTO ';
        try {
            $key = self::create_insert_key($array);
        } catch (DbuilderException $e) {
            $e->show();
            exit();
        }
        try {
            $value = self::create_insert_value($array);
        } catch (DbuilderException $e) {
            $e->show();
            exit();
        }
        return $val . $table . $key . $value;
    }

    /*
     * Create update SQL string
     * @param string $table
     * @param array $array
     * @param mixed $where
     * @return string
     */
    public static function create_update($table, $array, $where)
    {
        $table = self::table($table);
        $update = self::create_update_string($array);
        $where = self::where($where);
        return "UPDATE " . $table . " SET " . $update . " WHERE " . $where;
    }

    public static function create_update_string($data)
    {
        $val = '';
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $val .= self::wrapSymbol($key) . "='" . self::cc($value) . "',";
            }
            return rtrim($val, ', ');
        }
        return (string)$data;
    }

    public static function create_where($data)
    {
        if (is_array($data)) {
            $clauses = [];
            foreach ($data as $key => $value) {
                if ($key === 'condition') {
                    continue;
                }
                $clauses[] = self::wrapSymbol($key) . " = '" . self::cc($value) . "'";
            }
            $condition = isset($data['condition']) ? ' ' . trim($data['condition']) . ' ' : ' AND ';
            return implode($condition, $clauses);
        }
        return (string)$data;
    }

    public static function create_delete($table = null, $where = null)
    {
        if ($table === null || $table === '') {
            throw new DbuilderException("Table parameter cannot be null or empty!");
        }
        $table = self::table($table);
        return $where !== null ? "DELETE FROM " . $table . " WHERE " . self::where($where) : "DELETE FROM " . $table;
    }
}
