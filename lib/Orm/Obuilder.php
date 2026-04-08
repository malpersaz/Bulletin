<?php

/**
 * Koberz Bilişim İçerik Yönetim Sistemi
 * Web: www.koberz.com
 * E-Mail: info@koberz.com
 * Kullanıcı: Kobim
 * Zaman: 13.04.2011 - 20:33
 */

class Obuilder
{
    // Eşitlikler -- Equations
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


    /*
    // Disable Where Key -- Where içindeki geçersiz keyler
    public static $disable_where_key = array(
        'and',
        'or'
    );
    public static $group_key = array(
        'og' => '(',
        'cg' => ')'
    );
    // Validate After Key -- Bir colomdan sonra gelebilecek keyler
    public static $valid_after_key = array(
        'and',
        'or'
    );*/

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


        $koberz_sql = "";
        $vi = 0;
        $operator = null; // content $operator '1' $operator < <> = vs.. gibi değerler alabilir.
        foreach ($colomuns as $colom) {
            // and or dan sonra tekrar and or gelemez
            if ($check_and_or === true && in_array($colom, self::$valid_after_key)) {
                throw new OrmException(
                    "ORM Builder Exception!
                After '" . $lates_key . "' to '" . implode("','", self::$valid_after_key) . "' can not write.
                you <font color='red'>'$lates_key'</font> after <font color='red'>'$colom'</font> Written\n." .
                        "\n
                    ORM Builder Hatası!
                '" . $lates_key . "'' kelimesinden sonra  '" . implode("','", self::$valid_after_key) . "' yazamazsınız.
                <font color='red'>'$lates_key'</font> dan sonra <font color='red'>'$colom'</font> yazmışsınız!" .
                        "\nSQL: <font color='red'>" . $koberz_sql . "</font>\n" .
                        "Query: 'where_" . str_replace($lates_key . "_" . $colom, "<font color='red'>" . $lates_key . "_" . $colom . "</font>", $function)
                );
            }


            // not eq og cg den sonra benzer keywordlar gelemez.
            if ($check_equations === true && in_array($colom, array_keys(self::$equations))) {
                throw new OrmException(
                    "ORM Builder Exception!
                After '" . $lates_key . "' to '" . implode("','", array_keys(self::$equations)) . "' can not write.
                you <font color='red'>'$lates_key'</font> after <font color='red'>'$colom'</font> Written\n" .
                        "\nORM Builder Hatası!
                '" . $lates_key . "'' kelimesinden sonra  '" . implode("','", array_keys(self::$equations)) . "' yazamazsınız.
                <font color='red'>'$lates_key'</font> dan sonra <font color='red'>'$colom'</font> yazmışsınız!\n" .
                        "\nSQL: <font color='red'>" . $koberz_sql . "</font>\n" .
                        "Query: 'where_" . str_replace($lates_key . "_" . $colom, "<font color='red'>" . $lates_key . "_" . $colom . "</font>", $function)
                );
            }

            // colomlardan sonra colom gelmemesini check edelim
            if ($check_colom === true && in_array($colom, $key_array)) {
                throw new OrmException(
                    "ORM Builder Exception!\n
                After '" . $lates_key . "' to '" . implode("','", $key_array) . "' can not write.
                you <font color='red'>'$lates_key'</font> after <font color='red'>'$colom'</font> Written\n" .
                        "\nORM Builder Hatası!
                '" . $lates_key . "'' kelimesinden sonra  '" . implode("','", $key_array) . "' yazamazsınız.
                Yazabileceğiniz değerler '" . implode("','", array_keys(self::$equations)) . "'

                <font color='red'>'$lates_key'</font> dan sonra <font color='red'>'$colom'</font> yazmışsınız!" .


                        "\nSQL: <font color='red'>" . $koberz_sql . "</font>\n" .
                        "Query: 'where_" . str_replace($lates_key . "_" . $colom, "<font color='red'>" . $lates_key . "_" . $colom . "</font>", $function)
                );
            }

            // en son çağrılan key
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
                        "ORM Builder Exception!" .
                            "<font color='red'>" . self::$og_key . "</font> yazmadan <font color='red'>" . self::$cg_key . "</font> Yazamazsınız\n" .
                            "ORM Builder Hatası!" .
                            "<font color='red'>" . self::$og_key . "</font> yazmadan <font color='red'>" . self::$cg_key . "</font> Yazamazsınız\n" .

                            "SQL: <font color='red'>" . $koberz_sql . "</font>\n" .
                            "function 'where_" . str_replace(self::$cg_key, "<font color='red'>" . self::$cg_key . "</font>", $function) . "\n"
                    );
                }
                $check_g = false;
            }

            // Eğer $equations dizisinde colom varsa
            if (isset(self::$equations[$colom])) {
                // Disable keylerden birimi peki? 'and' 'or' '(' ')'
                if (in_array($colom, self::$disable_keys)) {
                    // Eğer onlardan biriyse sql ekleme yap
                    $koberz_sql .= self::$equations[$colom] . " ";
                    $check_and_or = true;
                } else {
                    // Eğer onlardan biri değilse '<>' '=' '>' vb.. ise $operator değişkenine ata
                    $operator = self::$equations[$colom];
                    $check_equations = true;
                }


                // diğer coloma geç
                continue;
            }

            // colomlardan sonra colom gelmemesini check et
            $check_colom = true;
            // colomu koberz_sql'e ekle ' content'
            $koberz_sql .= " " . $colom;
            // Eğer karşılık gelen değer array ise IN ifadesini kullan
            if (is_array($values[$vi])) {
                // Eğer operator <> not ise NOT IN Kullan değilse IN kullan
                if ($operator === '<>') {
                    $koberz_sql .= " NOT IN('" . implode("','", array_map('Obuilder::cc', $values[$vi])) . "')";
                } else {
                    $koberz_sql .= " IN('" . implode("','", array_map('Obuilder::cc', $values[$vi])) . "')";
                }
            } else {
                // eğer operator null ise '=' yap
                $e = $operator === null ? " = " : " $operator ";
                // <>'1' gibi
                $koberz_sql .= $e . "'" . self::cc($values[$vi]) . "'";
                // Operatoru NULL yap
                $operator = null;
            }
            // value indexini bir arttır.
            $vi++;


            $koberz_sql .= " ";
        }

        if ($check_g === true) {
            throw new OrmException(
                "ORM Builder Exception!" .
                    "Açılan <font color='red'>(</font> Kapatılmamış\n" .
                    "<font color='red'>" . self::$cg_key . "</font> yazmanız gerekiyor" .
                    "ORM Builder Hatası!" .
                    "Açılan <font color='red'>(</font> Kapatılmamış\n" .
                    "<font color='red'>" . self::$cg_key . "</font> yazmanız gerekiyor" .
                    "SQL: <font color='red'>" . $koberz_sql . "</font>\n" .
                    "function 'where_" . str_replace(self::$og_key, "<font color='red'>" . self::$og_key . "</font>", $function) . "\n"
            );
        }

        return $koberz_sql;
    }


    // Check key is exists
    // Key model dosyasında tanımlanmışmı?
    public static function check_key_exists($key, $_orm_coloms)
    {
        if (is_array($key) === false) {
            if (!in_array($key, $_orm_coloms)) {
                throw new OrmException(
                    "'<b>" . $key . "</b>' colom is not defined!\n" .
                        "Valid Keys:" . print_r($_orm_coloms, true) . "\n" .
                        "<b>'.$key.'</b> colom'u geçerli değil\n" .
                        "Çünkü model dosyasında tanımlanmamış!\n" .
                        "Geçerli keyler:" . print_r($_orm_coloms, true)
                );
            }
        } else {
            foreach ($key as $k) {
                //var_dump(in_array($k,$_orm_coloms));
                if (!in_array($k, $_orm_coloms)) {
                    throw new OrmException(
                        "'<b>" . $k . "</b>' colom is not defined!\n" .
                            "Valid Keys:" . print_r($_orm_coloms, true) . "\n" .
                            "<b>'.$k.'</b> colom'u geçerli değil\n" .
                            "Çünkü model dosyasında tanımlanmamış!\n" .
                            "Geçerli keyler:" . print_r($_orm_coloms, true)
                    );
                }
            }
        }
    }

    // Check key and value size
    // Gönderilen Key sayısı ile value sayısı aynımı kontrol eder.
    public static function check_where_colom_size($key_array, $value, $keys)
    {
        $value_size = count($value); // Value sayısı
        $key_size = count($key_array); // Key Sayısı

        // Eğer key ve value sayısı eşit değilse
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
                "ORM Builder Exception!
                    \nwhere function key and value size not equal
                    \nfunction 'where_" . $keys . $values . "
            \nKey size: " . $key_size . " - Value size: " . $value_size . "
            \nKeys:" . print_r($key_array, true) . "
            \nValues: " . print_r($value, true) . "\n" .

                    "ORM Builder Hatası!
                    \nwhere fonksiyonunda kullanılan key ve value sayısı eşit değil!
                    \nKullanılan Fonksiyon 'where_" . $keys . $values . "
            \nKey Sayısı: " . $key_size . " - Value Sayısı: " . $value_size . "
            \nKeyler:" . print_r($key_array, true) . "
            \nValue'ler: " . print_r($value, true)
            );
        }
        return true;
    }

    public static function cc($str)
    {
        return str_replace("'", "''", (string)$str);
    }
}
