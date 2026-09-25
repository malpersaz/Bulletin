<?php

/**
 * Amed Framework Content Management System
 * File: Obuilder.php
 * Description: ORM Query Builder Class
 */

class Obuilder
{
    // Equations mapping
    public static $equations = [
        'eq' => '=',
        'not' => '<>',
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
        'like' => 'LIKE',
        'og' => '(',
        'cg' => ')',
        'and' => 'AND',
        'or' => 'OR'
    ];

    public static $disable_keys = [
        'and',
        'or',
        'og',
        'cg'
    ];

    public static $valid_after_key = [
        'and',
        'or',
    ];

    public static $og_key = 'og';
    public static $cg_key = 'cg';

    public static function where($keys, $values, $_orm_coloms, $_orm_table)
    {
        $colomuns = explode('\_', $keys);

        $key_array = array_diff($colomuns, array_keys(self::$equations));

        $function = str_replace('\_', '_', $keys);

        try {
            self::check_where_colom_size($key_array, $values, $keys);
        } catch (OrmException $e) {
            $e->show();
        }

        try {
            self::check_key_exists($key_array, $_orm_coloms);
        } catch (OrmException $e) {
            $e->show();
        }

        $check_and_or = false;
        $check_equations = false;
        $check_colom = false;
        $check_g = false;

        $lates_key = null;

        $generated_sql = "";
        $vi = 0;
        $operator = null;

        foreach ($colomuns as $colom) {
            // Cannot write logical operator immediately after another logical operator
            if ($check_and_or === true && in_array($colom, self::$valid_after_key)) {
                throw new OrmException(
                    "ORM Builder Exception!\n" .
                    "Cannot write '" . implode("', '", self::$valid_after_key) . "' after '" . $lates_key . "'. " .
                    "Found <font color='red'>'$colom'</font> immediately after <font color='red'>'$lates_key'</font>.\n" .
                    "SQL: <font color='red'>" . $generated_sql . "</font>\n" .
                    "Query: 'where_" . str_replace($lates_key . "_" . $colom, "<font color='red'>" . $lates_key . "_" . $colom . "</font>", $function) . "'"
                );
            }

            // Cannot write comparison keyword immediately after another comparison keyword
            if ($check_equations === true && in_array($colom, array_keys(self::$equations))) {
                throw new OrmException(
                    "ORM Builder Exception!\n" .
                    "Cannot write comparison operators after '" . $lates_key . "'. " .
                    "Found <font color='red'>'$colom'</font> immediately after <font color='red'>'$lates_key'</font>.\n" .
                    "SQL: <font color='red'>" . $generated_sql . "</font>\n" .
                    "Query: 'where_" . str_replace($lates_key . "_" . $colom, "<font color='red'>" . $lates_key . "_" . $colom . "</font>", $function) . "'"
                );
            }

            // Ensure a column is not immediately followed by another column
            if ($check_colom === true && in_array($colom, $key_array)) {
                throw new OrmException(
                    "ORM Builder Exception!\n" .
                    "Cannot place column name '" . $colom . "' immediately after column '" . $lates_key . "'. " .
                    "Expected an operator from: '" . implode("', '", array_keys(self::$equations)) . "'.\n" .
                    "SQL: <font color='red'>" . $generated_sql . "</font>\n" .
                    "Query: 'where_" . str_replace($lates_key . "_" . $colom, "<font color='red'>" . $lates_key . "_" . $colom . "</font>", $function) . "'"
                );
            }

            // Track latest processed key
            $lates_key = $colom;

            $check_and_or = false;
            $check_equations = false;
            $check_colom = false;

            if ($colom === self::$og_key) {
                $check_g = true;
            }

            if ($colom === self::$cg_key) {
                if ($check_g !== true) {
                    throw new OrmException(
                        "ORM Builder Exception!\n" .
                        "Cannot use closing parenthesis <font color='red'>" . self::$cg_key . "</font> without opening parenthesis <font color='red'>" . self::$og_key . "</font>.\n" .
                        "SQL: <font color='red'>" . $generated_sql . "</font>\n" .
                        "Query: 'where_" . str_replace(self::$cg_key, "<font color='red'>" . self::$cg_key . "</font>", $function) . "'"
                    );
                }
                $check_g = false;
            }

            // Check if column exists in equations mapping
            if (isset(self::$equations[$colom])) {
                // Check if column is a reserved SQL key ('and', 'or', '(', ')')
                if (in_array($colom, self::$disable_keys)) {
                    // Append SQL logical operator
                    $generated_sql .= self::$equations[$colom] . " ";
                    $check_and_or = true;
                } else {
                    // Assign comparison operator ('<>', '=', '>', etc.)
                    $operator = self::$equations[$colom];
                    $check_equations = true;
                }

                // Proceed to next column
                continue;
            }

            // Verify column sequence validity
            $check_colom = true;
            // Append column to generated SQL string
            $generated_sql .= " " . $colom;
            // Handle array values with SQL IN / NOT IN clause
            if (is_array($values[$vi])) {
                // Use NOT IN for <> operator, otherwise use IN
                if ($operator === '<>') {
                    $generated_sql .= " NOT IN('" . implode("','", array_map('Obuilder::cc', $values[$vi])) . "')";
                } else {
                    $generated_sql .= " IN('" . implode("','", array_map('Obuilder::cc', $values[$vi])) . "')";
                }
            } else {
                // Default operator to '=' if null
                $e = $operator === null ? " = " : " $operator ";
                // Append sanitized value string
                $generated_sql .= $e . "'" . self::cc($values[$vi]) . "'";
                // Reset operator to null
                $operator = null;
            }
            // Increment value index pointer
            $vi++;

            $generated_sql .= " ";
        }

        if ($check_g === true) {
            throw new OrmException(
                "ORM Builder Exception!\n" .
                "Unclosed opening parenthesis <font color='red'>(</font>. A closing parenthesis <font color='red'>" . self::$cg_key . "</font> is required.\n" .
                "SQL: <font color='red'>" . $generated_sql . "</font>\n" .
                "Query: 'where_" . str_replace(self::$og_key, "<font color='red'>" . self::$og_key . "</font>", $function) . "'"
            );
        }

        return $generated_sql;
    }

    // Check if key exists in model schema definition (with case-insensitive resolution)
    public static function check_key_exists($key, $_orm_coloms)
    {
        if (!is_array($_orm_coloms)) {
            $_orm_coloms = [];
        }
        $lower_cols = array_map('strtolower', $_orm_coloms);

        if (is_array($key) === false) {
            if (!in_array($key, $_orm_coloms) && !in_array(strtolower($key), $lower_cols)) {
                throw new OrmException(
                    "ORM Builder Exception!\n" .
                    "Column '<b>" . $key . "</b>' is invalid or not defined in model schema.\n" .
                    "Valid columns: " . (is_array($_orm_coloms) ? implode(', ', $_orm_coloms) : print_r($_orm_coloms, true))
                );
            }
        } else {
            foreach ($key as $k) {
                if (!in_array($k, $_orm_coloms) && !in_array(strtolower($k), $lower_cols)) {
                    throw new OrmException(
                        "ORM Builder Exception!\n" .
                        "Column '<b>" . $k . "</b>' is invalid or not defined in model schema.\n" .
                        "Valid columns: " . (is_array($_orm_coloms) ? implode(', ', $_orm_coloms) : print_r($_orm_coloms, true))
                    );
                }
            }
        }
    }

    // Check if the number of provided keys matches the number of values.
    public static function check_where_colom_size($key_array, $value, $keys)
    {
        $value_size = count($value);
        $key_size = count($key_array);

        // If key and value counts are not equal
        if ($key_size !== $value_size) {
            $keys = str_replace('\_', '_', $keys);
            $values = "(";
            $i = 0;
            foreach ($value as $v) {
                if (!is_array($v)) {
                    $values .= "'" . $v . "'";
                } else {
                    $values .= print_r($v, true);
                }
                $i++;
                $values .= $value_size > $i ? "," : "";
            }
            $values .= ")";
            unset($i);

            throw new OrmException(
                "ORM Builder Exception!\n" .
                "WHERE clause key count (" . $key_size . ") does not match value count (" . $value_size . ").\n" .
                "Function: 'where_" . $keys . $values . "'\n" .
                "Keys: " . print_r($key_array, true) . "\n" .
                "Values: " . print_r($value, true)
            );
        }
        return true;
    }

    public static function cc($str)
    {
        return str_replace("'", "''", (string)$str);
    }
}
