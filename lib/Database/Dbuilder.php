<?php

/**
 * Koberz Bilişim İçerik Yönetim Sistemi
 * Web: www.koberz.com
 * E-Mail: info@koberz.com
 * Kullanıcı: Kobim
 * Zaman: 16.04.2011- 21:20
 * Sınıfı : Dbuilder
 */
class Dbuilder
{
    public static $symbol;



    public static function cc($val)
    {
        return str_replace("'", "''", $val);
    }

    public static function table($arg)
    {
        return $arg;
    }

    public static function table_join($conf)
    {
        if (is_array($conf) === false) {
            throw new DbuilderException(
                "Table JOIN PROBLEM :" . $conf . " is not array!" .
                    "TABLO JOIN PROBLEMI : " . $conf . " bir dizi değil!"
            );
        }
        $join = "";
        $select = [];
        foreach ($conf as $veri) {
            foreach ($veri['table']['1'] as $colom) {
                $select[] = $veri['table']['0'] . "." . $colom;
            }
            if (isset($veri['alias'])) {
                foreach ($veri['alias']['1'] as $colom) {
                    $select[] = $veri['alias']['0'] . "." . $colom;
                }
                $join .= $veri['table']['0'] . " " . $veri['type'] . " JOIN ";
                $join .= $veri['alias']['0'];
                $join .= " ON ";
                $join .= $veri['condition']['0'] . "=" . $veri['condition']['1'];
            } else {
                $join .= " " . $veri['type'] . " JOIN " . $veri['table']['0'];
                $join .= " ON ";
                $join .= $veri['condition']['0'] . "=" . $veri['condition']['1'];
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
        if (is_array($arg)) {
            $escaped_cols = array_map([self::class, 'wrapSymbol'], $arg);
            return implode(',', $escaped_cols);
        }
        
        if ($arg === '*') {
            return '*';
        }

        if (strpos($arg, ',') !== false) {
            $parts = explode(',', $arg);
            $escaped_parts = array_map([self::class, 'wrapSymbol'], $parts);
            return implode(',', $escaped_parts);
        }

        return self::wrapSymbol($arg);
    }

    private static function wrapSymbol($val)
    {
        $val = trim($val);
        if ($val === '*' || strpos($val, '(') !== false || strpos($val, ' ') !== false) {
            return $val;
        }
        
        // Handle table-prefixed columns (e.g., table.column)
        if (strpos($val, '.') !== false) {
            $parts = explode('.', $val);
            $escaped_parts = array_map(function($part) {
                if ($part === '*' || (strpos($part, self::$symbol) === 0 && substr($part, -1) === self::$symbol)) {
                    return $part;
                }
                return self::$symbol . $part . self::$symbol;
            }, $parts);
            return implode('.', $escaped_parts);
        }

        // Eğer zaten sembolle sarılmışsa ekleme
        if (strpos($val, self::$symbol) === 0 && substr($val, -1) === self::$symbol) {
            return $val;
        }
        return self::$symbol . $val . self::$symbol;
    }

    public static function limit($limit, $offset = null)
    {
        return $offset == null ? "LIMIT " . $limit : "LIMIT $limit OFFSET $offset";
    }

    public static function order($colom, $type)
    {
        if (is_array($colom)) {
            $escaped_cols = array_map([self::class, 'wrapSymbol'], $colom);
            $colom = implode(',', $escaped_cols);
        } else {
            $colom = self::wrapSymbol($colom);
        }
        return " ORDER BY " . $colom . " " . $type;
    }

    public static function where($data)
    {
        return self::create_where($data);
    }


    /*
    * Make İnsert Key
    * Veritabanı insert işlemi için keyleri oluşturur.
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
        throw new DbuilderException(
            "insert builder problem :" . $data . " is not array!" .
                "insert builder problemi : " . $data . " bir dizi değil!"
        );
    }

    /*
    * Make İnsert Value
    * Veritabanı insert işlemi için value kısmını oluşturur.
    * @param array $data
    * @return string
    */
    public static function create_insert_value($data)
    {
        if (is_array($data)) {
            return ' VALUES (\'' . implode('\',\'', array_map(['Dbuilder', 'cc'], array_values($data))) . '\')';
        }
        throw new DbuilderException(
            "insert builder problem :" . $data . " is not array!" .
                "insert builder problemi : " . $data . " bir dizi değil!"
        );
    }

    /*
    * Make insert sql string
    * Veritabanı insert işlemi için sql stringi oluşturur
    * @param string $table
    * @param array $array
    * @param bool $bool
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
    * Make update sql string
    * Veritabanı update işlemi için sql stringi oluşturur
    * @param string $table
    * @param array $array
    * @param string $where
    * @return string
    */
    public static function create_update($tablo, $array, $where)
    {
        $tablo = self::table($tablo);
        $update = self::create_update_string($array);
        $where = self::where($where);
        return "UPDATE " . $tablo . " SET " . $update . " WHERE " . $where;
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
        return $data;
    }

    public static function create_where($data)
    {
        if (is_array($data)) {
            $val = '';
            foreach ($data as $key => $value) {
                if ($key === 'condition') {
                    $val .= " " . $value . " ";
                    continue;
                }
                $val .= " " . self::wrapSymbol($key) . " = '" . self::cc($value) . "' ";
            }
            return $val;
        }
        return $data;
    }

    public static function create_delete($table = null, $where = null)
    {
        if ($table === null) {
            throw new DbuilderException("\$table not is null\n \$table boş Olamaz");
            return false;
        }
        $table = self::table($table);
        return $where !== null ? "DELETE FROM " . $table . " WHERE " . self::where($where) : "DELETE FROM " . $table;
    }
}
